<?php
define('ROOT_DIR', '/var/www/html');
require ROOT_DIR . '/vendor/autoload.php';
require ROOT_DIR . '/app/Config/config.php';

// Simular autoload
spl_autoload_register(function ($class) {
    $base = ROOT_DIR . '/app/';
    $file = $base . str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';
    if (file_exists($file)) require $file;
});

use App\Services\RubixMLClassificationService;

// Pegar uma imagem de upload real
$dir = ROOT_DIR . '/public/uploads/images/';
$images = array_merge(glob($dir . '*.jpg') ?: [], glob($dir . '*.jpeg') ?: [], glob($dir . '*.png') ?: []);

if (empty($images)) {
    echo "Nenhuma imagem encontrada em $dir" . PHP_EOL;
    exit(1);
}

$imagePath = $images[0];
echo "Testando com imagem: $imagePath" . PHP_EOL;
echo "Existe: " . (file_exists($imagePath) ? 'SIM' : 'NÃO') . PHP_EOL;
echo "Tam: " . filesize($imagePath) . " bytes" . PHP_EOL;

$service = new RubixMLClassificationService();

echo PHP_EOL . "--- Chamando classifyImage() ---" . PHP_EOL;
$result = $service->classifyImage($imagePath);
echo "Resultado:" . PHP_EOL;
print_r($result);

echo PHP_EOL . "Confiança suficiente? " . ($service->isConfidenceSufficient($result['confidence']) ? 'SIM' : 'NÃO') . PHP_EOL;


