<?php
namespace App\Services;

class ImageClassificationService
{
    private $config;

    public function __construct()
    {
        $config = require ROOT_DIR . '/app/Config/config.php';
        $this->config = $config['ai_service'];
    }

    public function classifyImage($imagePath)
    {
        if (!file_exists($imagePath)) {
            throw new \Exception("Arquivo de imagem não encontrado: {$imagePath}");
        }

        $classifyScript = ROOT_DIR . '/app/ML/classify_image.php';
        $modelPath = ROOT_DIR . '/app/ML/models/species_classifier.rbx';

        $cmd = sprintf(
            'php %s --image=%s --model=%s --json',
            escapeshellarg($classifyScript),
            escapeshellarg($imagePath),
            escapeshellarg($modelPath)
        );

        file_put_contents(ROOT_DIR . '/debug_upload.txt', "Executando comando:\n$cmd\n", FILE_APPEND);

        exec($cmd . " 2>&1", $output, $status);

        $outputText = implode("\n", $output);

        file_put_contents(ROOT_DIR . '/debug_upload.txt', "Saída:\n$outputText\n", FILE_APPEND);

        if ($status !== 0) {
            throw new \Exception("Falha ao executar o classificador RubixML: " . $outputText);
        }

        $result = json_decode(trim($outputText), true);

        if (!$result) {
            throw new \Exception("Classificador retornou saída inválida: " . $outputText);
        }

        return $result;
    }

    public function isConfidenceSufficient($confidence)
    {
        return $confidence >= $this->config['confidence_threshold'];
    }
}