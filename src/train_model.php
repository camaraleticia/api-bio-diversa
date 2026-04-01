<?php
// src/train_model.php

require_once __DIR__ . '/public/index.php';

use App\ML\DataCollector;
use App\ML\DataPreprocessor;
use App\ML\ModelTrainer;
use App\ML\ModelEvaluator;

// Definir diretórios
$datasetDir = __DIR__ . '/app/ML/Dataset';
$processedDatasetFile = __DIR__ . '/app/ML/processed_dataset.dat';
$testDatasetFile = __DIR__ . '/app/ML/test_dataset.dat';
$modelPath = __DIR__ . '/app/ML/Models/species_classifier.rbx';

// Etapa 1: Coletar dados (opcional, pode ser feito manualmente)
echo "=== ETAPA 1: COLETA DE DADOS ===\n";
$collector = new DataCollector();

$species = [
    'Vanellus chilensis' => 'fauna/quero_quero',
    'Hydrochoerus hydrochaeris' => 'fauna/capivara',
    'Lycalopex gymnocercus' => 'fauna/graxaim',
    'Eryngium horridum' => 'flora/babosa_do_campo',
    'Trifolium riograndense' => 'flora/trevo_nativo'
];

foreach ($species as $scientificName => $dir) {
    echo "Coletando imagens para: $scientificName\n";
    $outputDir = $datasetDir . '/' . $dir;
    $count = $collector->downloadImagesFromSources($scientificName, $outputDir, 100);
    echo "Coletadas $count imagens\n\n";
}

// Etapa 2: Pré-processar dados
echo "=== ETAPA 2: PRÉ-PROCESSAMENTO DE DADOS ===\n";
$preprocessor = new DataPreprocessor();
$dataset = $preprocessor->processDataset($datasetDir, $processedDatasetFile);

// Etapa 3: Treinar modelo
echo "=== ETAPA 3: TREINAMENTO DO MODELO ===\n";
$trainer = new ModelTrainer();
$model = $trainer->trainModel($processedDatasetFile, $modelPath);

// Etapa 4: Avaliar modelo
echo "=== ETAPA 4: AVALIAÇÃO DO MODELO ===\n";
$evaluator = new ModelEvaluator();
$metrics = $evaluator->evaluateModel($modelPath, $testDatasetFile);

// Etapa 5: Ajuste fino (opcional)
echo "=== ETAPA 5: AJUSTE FINO ===\n";
$bestParams = $evaluator->findOptimalParameters($processedDatasetFile);

echo "\nTreinamento completo! O modelo está pronto para uso.\n";
echo "Você pode agora integrar o RubixMLClassificationService ao seu sistema.\n";