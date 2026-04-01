<?php
// src/app/ML/DataCollector.php

namespace App\ML;

class DataCollector
{
    public function downloadImagesFromSources($species, $outputDir, $limit = 100)
    {
        // Exemplo: buscar imagens do iNaturalist API
        $apiUrl = "https://api.inaturalist.org/v1/observations";
        $params = [
            "taxon_name" => $species,
            "per_page" => $limit,
            "photos" => true,
            "quality_grade" => "research"
        ];

        $response = file_get_contents($apiUrl . "?" . http_build_query($params ));
        $data = json_decode($response, true);

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        foreach ($data['results'] as $index => $observation) {
            if (isset($observation['photos'][0]['url'])) {
                $imageUrl = $observation['photos'][0]['url'];
                $imagePath = $outputDir . "/" . $species . "_" . sprintf("%03d", $index + 1) . ".jpg";
                file_put_contents($imagePath, file_get_contents($imageUrl));
                echo "Baixada imagem: $imagePath\n";
            }
        }

        return count($data['results']);
    }
}