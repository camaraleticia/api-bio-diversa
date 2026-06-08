<?php
/**
 * Script de inferência para classificação de espécies usando RubixML
 */

define('ROOT_DIR', dirname(__DIR__));

require_once ROOT_DIR . '/../vendor/autoload.php';

use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;
use Rubix\ML\Datasets\Unlabeled;

// ===============================
// Ler argumentos
// ===============================
$options = getopt('', ['image:', 'model:', 'json::']);
$jsonOutput = isset($options['json']);

function logOutput($text) {
    global $jsonOutput;
    if (!$jsonOutput) {
        echo $text;
    }
}

if (!isset($options['image'])) {
    logOutput("Erro: Caminho da imagem não especificado\n");
    exit(1);
}

$imagePath = $options['image'];
$modelPath = $options['model'] ?? ROOT_DIR . '/app/ML/models/species_classifier.rbx';

// ===============================
// Verificações
// ===============================
if (!file_exists($imagePath)) {
    logOutput("Erro: Imagem não encontrada: $imagePath\n");
    exit(1);
}

if (!file_exists($modelPath)) {
    logOutput("Erro: Modelo não encontrado: $modelPath\n");
    exit(1);
}

try {

    logOutput("Carregando modelo...\n");

    if (str_ends_with($modelPath, '.gz')) {

        $compressed = file_get_contents($modelPath);
        $data = gzdecode($compressed);
        $estimator = unserialize($data);

    } else {

        $estimator = PersistentModel::load(new Filesystem($modelPath));

    }

    logOutput("Processando imagem...\n");

    $vector = preprocessImage($imagePath);

    if (!$vector) {
        throw new Exception("Falha ao processar imagem");
    }

    logOutput("Realizando classificação...\n");

    $dataset = new Unlabeled([$vector]);

    $predLabel = $estimator->predict($dataset);

    if (method_exists($estimator, 'proba')) {

        try {
            $probabilities = $estimator->proba($dataset)[0];
        } catch (\Throwable $e) {
            $probabilities = [$predLabel[0] => 1.0];
        }

    } else {

        $probabilities = [$predLabel[0] => 1.0];

    }

    arsort($probabilities);

    $speciesMapping = [
        'quero-quero' => 'Quero-quero (Vanellus chilensis)',
        'capivara' => 'Capivara (Hydrochoerus hydrochaeris)',
        'graxaim' => 'Graxaim (Lycalopex gymnocercus)',
        'babosa_do_campo' => 'Babosa-do-campo (Eryngium horridum)',
        'trevo_nativo' => 'Trevo-nativo (Trifolium riograndense)'
    ];

    $topSpecies = key($probabilities);
    $topProbability = current($probabilities);

    $topDisplayName = $speciesMapping[$topSpecies] ?? $topSpecies;

    // ===============================
    // JSON para API
    // ===============================

    $response = [
        "species" => $topDisplayName,
        "confidence" => round($topProbability, 4)
    ];

    if ($jsonOutput) {

        echo json_encode($response);

    } else {

        logOutput("\nResultado:\n");
        logOutput("Espécie: $topDisplayName\n");
        logOutput("Confiança: " . round($topProbability * 100, 2) . "%\n");

    }

    exit(0);

} catch (Exception $e) {

    if ($jsonOutput) {

        echo json_encode([
            "error" => $e->getMessage()
        ]);

    } else {

        echo "Erro: " . $e->getMessage() . "\n";

    }

    exit(1);
}

// ===============================
// Pré-processamento da imagem
// ===============================
function preprocessImage($imagePath)
{
    try {

        $data = file_get_contents($imagePath);

        $image = imagecreatefromstring($data);

        if (!$image) return null;

        $size = 16;

        $resized = imagecreatetruecolor($size, $size);

        imagecopyresampled(
            $resized,
            $image,
            0, 0, 0, 0,
            $size, $size,
            imagesx($image),
            imagesy($image)
        );

        $vector = [];

        for ($y = 0; $y < $size; $y++) {

            for ($x = 0; $x < $size; $x++) {

                $rgb = imagecolorat($resized, $x, $y);

                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $gray = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255.0;

                $vector[] = $gray;
            }
        }

        imagedestroy($image);
        imagedestroy($resized);

        return $vector;

    } catch (\Throwable $e) {

        return null;

    }
}