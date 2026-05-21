<?php
define('ROOT_DIR', '/var/www/html');
require ROOT_DIR . '/vendor/autoload.php';

use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;
use Rubix\ML\Datasets\Unlabeled;

$modelBase = ROOT_DIR . '/app/ML/models/species_classifier.rbx';
$modelGz   = $modelBase . '.gz';

echo "=== Diagnóstico do Modelo ===" . PHP_EOL;
echo "Modelo .rbx existe: "   . (file_exists($modelBase) ? 'SIM' : 'NÃO') . PHP_EOL;
echo "Modelo .rbx.gz existe: " . (file_exists($modelGz) ? 'SIM' : 'NÃO') . PHP_EOL;

// Ler training_report.json
$reportPath = ROOT_DIR . '/app/ML/models/training_report.json';
if (file_exists($reportPath)) {
    $report = json_decode(file_get_contents($reportPath), true);
    echo "color_mode: "         . ($report['color_mode'] ?? 'NÃO DEFINIDO') . PHP_EOL;
    echo "features_per_sample: " . ($report['features_per_sample'] ?? 'NÃO DEFINIDO') . PHP_EOL;
    $grayscale = ($report['color_mode'] ?? 'grayscale') === 'grayscale';
} else {
    echo "training_report.json: NÃO ENCONTRADO" . PHP_EOL;
    $grayscale = true;
}
$features = $grayscale ? 256 : 768;

// Tentar carregar o modelo
echo PHP_EOL . "--- Tentando carregar modelo ---" . PHP_EOL;
try {
    $chosen = file_exists($modelGz) ? $modelGz : $modelBase;
    echo "Carregando: $chosen" . PHP_EOL;

    if (str_ends_with($chosen, '.gz')) {
        $gz  = file_get_contents($chosen);
        $raw = gzdecode($gz);
        $tmp = sys_get_temp_dir() . '/diag_model.rbx';
        file_put_contents($tmp, $raw);
        $model = PersistentModel::load(new Filesystem($tmp));
    } else {
        $model = PersistentModel::load(new Filesystem($chosen));
    }
    echo "Modelo carregado com sucesso!" . PHP_EOL;

    // Testar inferência
    $sample = array_fill(0, $features, 0.5);
    echo "Testando inferência com vetor de $features features..." . PHP_EOL;
    $ds   = new Unlabeled([$sample]);
    $pred = $model->predict($ds);
    echo "Predição: " . $pred[0] . PHP_EOL;

    if (method_exists($model, 'proba')) {
        $proba = $model->proba($ds);
        echo "Probabilidades:" . PHP_EOL;
        arsort($proba[0]);
        foreach ($proba[0] as $label => $prob) {
            echo "  $label: " . round($prob * 100, 2) . "%" . PHP_EOL;
        }
    }

} catch (Throwable $e) {
    echo "ERRO ao carregar modelo: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}

