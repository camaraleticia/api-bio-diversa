<?php
/**
 * Script de inferência para classificação de espécies usando RubixML
 * Uso: php classify_image.php --image=/caminho/imagem.jpg [--model=modelo.rbx] [--json]
 */

// Definir constante ROOT_DIR — aponta para /var/www/html (raiz do projeto)
// Consistente com public/index.php e com RubixMLClassificationService
define('ROOT_DIR', dirname(dirname(__DIR__)));

require_once ROOT_DIR . '/vendor/autoload.php';

use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;
use Rubix\ML\Datasets\Unlabeled;

// ===============================
// Ler argumentos
// ===============================
$options   = getopt('', ['image:', 'model:', 'json::']);
$jsonOutput = isset($options['json']);

// Suprimir qualquer warning/notice do PHP para não poluir a saída JSON
if ($jsonOutput) {
    error_reporting(0);
    ini_set('display_errors', '0');
}

function logOutput($text)
{
    global $jsonOutput;
    if (!$jsonOutput) {
        echo $text;
    }
}

if (!isset($options['image'])) {
    if ($jsonOutput) {
        echo json_encode(['error' => 'Caminho da imagem não especificado']);
    } else {
        echo "Erro: Caminho da imagem não especificado\n";
    }
    exit(1);
}

$imagePath = $options['image'];
$modelPath = $options['model'] ?? ROOT_DIR . '/app/ML/models/species_classifier.rbx';

// Preferir .gz se existir e nenhum modelo explícito foi passado
if (!isset($options['model']) && file_exists($modelPath . '.gz')) {
    $modelPath = $modelPath . '.gz';
}

// ===============================
// Detectar modo de cor do relatório de treinamento
// ===============================
$grayscale  = true; // padrão seguro
$reportPath = ROOT_DIR . '/app/ML/models/training_report.json';
if (file_exists($reportPath)) {
    $report    = json_decode(file_get_contents($reportPath), true);
    $grayscale = ($report['color_mode'] ?? 'grayscale') === 'grayscale';
}

// ===============================
// Verificações
// ===============================
if (!file_exists($imagePath)) {
    if ($jsonOutput) {
        echo json_encode(['error' => "Imagem não encontrada: $imagePath"]);
    } else {
        echo "Erro: Imagem não encontrada: $imagePath\n";
    }
    exit(1);
}

if (!file_exists($modelPath)) {
    if ($jsonOutput) {
        echo json_encode(['error' => "Modelo não encontrado: $modelPath"]);
    } else {
        echo "Erro: Modelo não encontrado: $modelPath\n";
    }
    exit(1);
}

try {
    logOutput("Carregando modelo...\n");
    logOutput("Modo de cor: " . ($grayscale ? "grayscale" : "RGB") . "\n");

    if (str_ends_with($modelPath, '.gz')) {
        $compressed = file_get_contents($modelPath);
        $raw        = gzdecode($compressed);
        $tmpPath    = sys_get_temp_dir() . '/rubix_cli_' . md5($modelPath . filemtime($modelPath)) . '.rbx';
        if (!file_exists($tmpPath)) {
            file_put_contents($tmpPath, $raw);
        }
        $estimator = PersistentModel::load(new Filesystem($tmpPath));
    } else {
        $estimator = PersistentModel::load(new Filesystem($modelPath));
    }

    logOutput("Processando imagem...\n");

    $vector = preprocessImage($imagePath, $grayscale);

    if (!$vector) {
        throw new \Exception("Falha ao processar imagem");
    }

    logOutput("Realizando classificação...\n");

    $dataset  = new Unlabeled([$vector]);
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
        'quero-quero'    => 'Quero-quero',
        'capivara'       => 'Capivara',
        'graxaim'        => 'Graxaim',
        'babosa_do_campo' => 'Babosa-do-campo',
        'trevo_nativo'   => 'Trevo-nativo',
    ];

    $topSpecies     = key($probabilities);
    $topProbability = current($probabilities);
    $topDisplayName = $speciesMapping[$topSpecies] ?? $topSpecies;

    $allLabels = [];
    foreach ($probabilities as $label => $prob) {
        $allLabels[] = [
            'description' => $speciesMapping[$label] ?? $label,
            'confidence'  => $prob,
        ];
    }

    if ($jsonOutput) {
        echo json_encode([
            'species'    => $topDisplayName,
            'confidence' => round($topProbability, 4),
            'all_labels' => $allLabels,
            'error'      => null,
        ]);
    } else {
        logOutput("\nResultado:\n");
        logOutput("Espécie: $topDisplayName\n");
        logOutput("Confiança: " . round($topProbability * 100, 2) . "%\n");
    }

    exit(0);

} catch (\Exception $e) {
    if ($jsonOutput) {
        echo json_encode(['error' => $e->getMessage(), 'species' => null, 'confidence' => 0]);
    } else {
        echo "Erro: " . $e->getMessage() . "\n";
    }
    exit(1);
}

// ===============================
// Pré-processamento da imagem
// ===============================
/**
 * @param string $imagePath
 * @param bool   $grayscale  true = 256 features | false = 768 features RGB
 */
function preprocessImage(string $imagePath, bool $grayscale = true): ?array
{
    try {
        $data = file_get_contents($imagePath);
        if ($data === false) return null;

        $image = @imagecreatefromstring($data);
        if (!$image) return null;

        $size    = 16;
        $resized = imagecreatetruecolor($size, $size);
        if (!$resized) { imagedestroy($image); return null; }

        // Fundo branco para PNG com transparência
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $white = imagecolorallocate($resized, 255, 255, 255);
        imagefilledrectangle($resized, 0, 0, $size, $size, $white);
        imagealphablending($resized, true);

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $size, $size, imagesx($image), imagesy($image));

        $vector = [];
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $rgb = imagecolorat($resized, $x, $y);
                $r   = ($rgb >> 16) & 0xFF;
                $g   = ($rgb >> 8) & 0xFF;
                $b   = $rgb & 0xFF;

                if ($grayscale) {
                    $vector[] = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255.0;
                } else {
                    $vector[] = $r / 255.0;
                    $vector[] = $g / 255.0;
                    $vector[] = $b / 255.0;
                }
            }
        }

        imagedestroy($image);
        imagedestroy($resized);

        $expectedSize = $size * $size * ($grayscale ? 1 : 3);
        return count($vector) === $expectedSize ? $vector : null;

    } catch (\Throwable $e) {

        return null;

    }
}