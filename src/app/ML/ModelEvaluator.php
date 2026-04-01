<?php
// src/app/ML/ModelEvaluator.php

namespace App\ML;

use Rubix\ML\CrossValidation\Reports\ConfusionMatrix;
use Rubix\ML\CrossValidation\Reports\MulticlassBreakdown;
use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;

class ModelEvaluator
{
    public function evaluateModel($modelPath, $testDatasetFile)
    {
        // Carregar modelo
        $model = PersistentModel::load(new Filesystem($modelPath));

        // Carregar dataset de teste
        $testDataset = unserialize(file_get_contents($testDatasetFile));

        // Fazer predições
        $predictions = $model->predict($testDataset);

        // Gerar matriz de confusão
        $report = new ConfusionMatrix();
        $matrix = $report->generate($predictions, $testDataset->labels());

        echo "Matriz de Confusão:\n";
        print_r($matrix);

        // Gerar métricas detalhadas
        $breakdown = new MulticlassBreakdown();
        $metrics = $breakdown->generate($predictions, $testDataset->labels());

        echo "\nMétricas por Classe:\n";
        foreach ($metrics as $class => $values) {
            echo "$class:\n";
            echo "  Precisão: " . number_format($values['precision'] * 100, 2) . "%\n";
            echo "  Recall: " . number_format($values['recall'] * 100, 2) . "%\n";
            echo "  F1 Score: " . number_format($values['f1 score'] * 100, 2) . "%\n";
            echo "  Amostras: " . $values['support'] . "\n";
        }

        return $metrics;
    }

    public function findOptimalParameters($datasetFile)
    {
        // Implementação de busca em grade para hiperparâmetros
        // Testar diferentes configurações de camadas, taxas de aprendizado, etc.

        echo "Iniciando busca por parâmetros ótimos...\n";

        // Exemplo simplificado
        $learningRates = [0.1, 0.01, 0.001];
        $batchSizes = [32, 64, 128];
        $bestAccuracy = 0;
        $bestParams = [];

        foreach ($learningRates as $lr) {
            foreach ($batchSizes as $batchSize) {
                echo "Testando: lr=$lr, batch_size=$batchSize\n";

                // Treinar modelo com estes parâmetros
                // Avaliar e registrar resultados

                $accuracy = 0.75 + (mt_rand(0, 100) / 1000); // Simulação

                if ($accuracy > $bestAccuracy) {
                    $bestAccuracy = $accuracy;
                    $bestParams = ['learning_rate' => $lr, 'batch_size' => $batchSize];
                }
            }
        }

        echo "Melhores parâmetros encontrados:\n";
        print_r($bestParams);
        echo "Melhor acurácia: " . number_format($bestAccuracy * 100, 2) . "%\n";

        return $bestParams;
    }
}