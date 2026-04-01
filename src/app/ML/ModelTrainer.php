<?php
// src/app/ML/ModelTrainer.php

namespace App\ML;

use Rubix\ML\PersistentModel;
use Rubix\ML\Pipeline;
use Rubix\ML\Transformers\ImageVectorizer;
use Rubix\ML\Transformers\ZScaleStandardizer;
use Rubix\ML\Classifiers\MultilayerPerceptron;
use Rubix\ML\NeuralNet\Layers\Dense;
use Rubix\ML\NeuralNet\Layers\Activation;
use Rubix\ML\NeuralNet\Layers\BatchNorm;
use Rubix\ML\NeuralNet\Layers\Dropout;
use Rubix\ML\NeuralNet\ActivationFunctions\LeakyReLU;
use Rubix\ML\NeuralNet\Optimizers\Adam;
use Rubix\ML\Persisters\Filesystem;

class ModelTrainer
{
    public function trainModel($datasetFile, $outputModelPath)
    {
        // Carregar dataset
        $dataset = unserialize(file_get_contents($datasetFile));

        // Dividir em treino (80%) e teste (20%)
        [$training, $testing] = $dataset->stratifiedSplit(0.8);

        echo "Conjunto de treino: " . count($training) . " amostras\n";
        echo "Conjunto de teste: " . count($testing) . " amostras\n";

        // Criar pipeline de transformação
        $pipeline = new Pipeline([
            new ImageVectorizer(true, true),
            new ZScaleStandardizer(),
        ]);

        // Criar rede neural
        $estimator = new MultilayerPerceptron([
            new Dense(256),
            new Activation(new LeakyReLU()),
            new BatchNorm(),
            new Dropout(0.3),
            new Dense(128),
            new Activation(new LeakyReLU()),
            new BatchNorm(),
            new Dropout(0.3),
            new Dense(64),
            new Activation(new LeakyReLU()),
            new BatchNorm(),
        ], 64, new Adam(0.001));

        // Criar modelo persistente
        $model = new PersistentModel($estimator, new Filesystem($outputModelPath));

        // Treinar o modelo
        echo "Iniciando treinamento...\n";
        $model->train($training);

        // Avaliar o modelo
        $predictions = $model->predict($testing);
        $accuracy = $this->calculateAccuracy($predictions, $testing->labels());

        echo "Treinamento concluído!\n";
        echo "Acurácia no conjunto de teste: " . number_format($accuracy * 100, 2) . "%\n";

        // Salvar o modelo
        $model->save();
        echo "Modelo salvo em: $outputModelPath\n";

        return $model;
    }

    private function calculateAccuracy($predictions, $actual)
    {
        $correct = 0;

        foreach ($predictions as $i => $prediction) {
            if ($prediction === $actual[$i]) {
                $correct++;
            }
        }

        return $correct / count($predictions);
    }
}