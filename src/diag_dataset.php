<?php
$dir = '/var/www/html/app/ML/Dataset/fauna/capivara';

// DirectoryIterator
$count1 = 0;
foreach (new DirectoryIterator($dir) as $f) {
    if ($f->isDot() || !$f->isFile()) continue;
    $count1++;
}

// glob (GLOB_BRACE indisponível no Alpine/musl)
$count2 = count(array_merge(
    glob($dir . '/*.jpg') ?: [],
    glob($dir . '/*.jpeg') ?: [],
    glob($dir . '/*.png') ?: []
));

// scandir
$count3 = count(array_filter(scandir($dir), fn($f) => in_array(
    strtolower(pathinfo($f, PATHINFO_EXTENSION)), ['jpg','jpeg','png']
)));

echo "DirectoryIterator : $count1 arquivos\n";
echo "glob              : $count2 arquivos\n";
echo "scandir           : $count3 arquivos\n";
echo "memory_limit      : " . ini_get('memory_limit') . "\n";
echo "UID               : " . posix_geteuid() . "\n";

// Testar leitura de uma imagem
$testImg = $dir . '/capivara_021.jpeg';
if (file_exists($testImg)) {
    $data = file_get_contents($testImg);
    $img = @imagecreatefromstring($data);
    echo "capivara_021.jpeg: " . ($img ? "OK (" . imagesx($img) . "x" . imagesy($img) . ")" : "FALHOU") . "\n";
    if ($img) imagedestroy($img);
} else {
    echo "capivara_021.jpeg: arquivo nao encontrado\n";
}

