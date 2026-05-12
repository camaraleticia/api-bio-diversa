<?php
// src/app/ML/DataPreprocessor.php

namespace App\ML;

use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Transformers\ImageResizer;
use Rubix\ML\Transformers\ImageRotator;
use Rubix\ML\Transformers\ImageFlipper;

class DataPreprocessor
{
    public function processDataset($datasetDir, $outputFile)
    {
        $samples = [];
        $labels = [];

        // Percorrer diretórios de espécies
        foreach (glob($datasetDir . "/*", GLOB_ONLYDIR) as $typeDir) {
            $type = basename($typeDir); // fauna ou flora

            foreach (glob($typeDir . "/*", GLOB_ONLYDIR) as $speciesDir) {
                $species = basename($speciesDir);

                foreach (glob($speciesDir . "/*.{jpg,jpeg,png,JPG,JPEG,PNG}", GLOB_BRACE) as $imagePath) {
                    // Carregar imagem
                    $image = imagecreatefromstring(file_get_contents($imagePath));
                    if (!$image){ // adicionado em 01/04
                        echo "Imagem ignorada: $imagePath \n";
                        continue;
                    }

                    // Aplicar transformações básicas
                    $resizer = new ImageResizer(32, 32); // atualizado para 32
                    $image = $resizer->transform([$image])[0];

                    // Adicionar à lista de amostras
                    $samples[] = $image;
                    $labels[] = $species;

                    // Aumento de dados: rotação
                    $rotator = new ImageRotator(90);
                    $rotatedImage = $rotator->transform([$image])[0];
                    $samples[] = $rotatedImage;
                    $labels[] = $species;

                    // Aumento de dados: espelhamento horizontal
                    $flipper = new ImageFlipper(true, false);
                    $flippedImage = $flipper->transform([$image])[0];
                    $samples[] = $flippedImage;
                    $labels[] = $species;
                }

                echo "Processadas " . count(glob($speciesDir . "/*.{jpg,jpeg,png}", GLOB_BRACE)) . " imagens de $species\n";
            }
        }

        // Criar dataset rotulado
        $dataset = new Labeled($samples, $labels);

        // Salvar dataset processado
        file_put_contents($outputFile, serialize($dataset));

        echo "Dataset processado e salvo em $outputFile\n";
        echo "Total de amostras: " . count($samples) . "\n";

        return $dataset;
    }
}