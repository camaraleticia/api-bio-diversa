<?php
/**
 * Script de treinamento do modelo RubixML para classificação de espécies
 *
 * Este script treina um modelo de classificação de imagens usando RubixML
 * para identificar espécies da fauna e flora da Região Carbonífera.
 *
 * Uso: php train_model.php [--epochs=100] [--batch=32] [--output=model.rbx]
 */

// Aumenta o limite de memória para o processo atual
ini_set('memory_limit', '16G');
// Limite de tempo de execução
set_time_limit(0);
// mostrar todos os erros
// Ligue relatórios de erro no CLI
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', '/tmp/php_cli_errors.log');



// Definir constante ROOT_DIR
define('ROOT_DIR', dirname(__DIR__));

// Carregar o autoloader
require_once ROOT_DIR . '/../vendor/autoload.php';

// Importar classes do RubixML
use Rubix\ML\Classifiers\MultilayerPerceptron;
use Rubix\ML\NeuralNet\Layers\Dense;
use Rubix\ML\NeuralNet\Layers\Activation;
use Rubix\ML\NeuralNet\ActivationFunctions\LeakyReLU;
use Rubix\ML\NeuralNet\Layers\Dropout;
use Rubix\ML\NeuralNet\Optimizers\Adam;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Persisters\Filesystem;
use Rubix\ML\PersistentModel;
use Rubix\ML\Pipeline;
use Rubix\ML\Transformers\ZScaleStandardizer;

// Processar argumentos da linha de comando
$options = getopt('', ['epochs:', 'batch:', 'output:', 'kfold::']);
$epochs = $options['epochs'] ?? 100;
$batchSize = $options['batch'] ?? 32;
$outputPath = $options['output'] ?? ROOT_DIR . '/app/ML/models/species_classifier.rbx';
$kfold = isset($options['kfold']) ? (int)$options['kfold'] : 0; // 0 = desativado

// Garantir que o diretório de saída existe
$outputDir = dirname($outputPath);
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

echo "Iniciando treinamento do modelo de classificação de espécies\n";
echo "Épocas: $epochs\n";
echo "Tamanho do batch: $batchSize\n";
echo "Arquivo de saída: $outputPath\n";
if ($kfold > 1) {
    echo "K-Fold CV: {$kfold} folds\n";
}
echo "\n";

try {
    // Carregar e preparar os dados de treinamento
    echo "Carregando dados de treinamento...\n";
    $samples = [];
    $labels = [];

    // Diretório com as imagens de treinamento organizadas por classe
    // Corrigido para respeitar capitalização (Linux é case-sensitive)
    $datasetDir = ROOT_DIR . '/ML/Dataset';

    // Verificar se o diretório existe
    if (!is_dir($datasetDir)) {
        throw new Exception("Diretório de dataset não encontrado: $datasetDir");
    }

    // Processar cada subdiretório (classe)
    foreach (new DirectoryIterator($datasetDir) as $typeDir) {
        if ($typeDir->isDot() || !$typeDir->isDir()) continue;

        $typePath = $typeDir->getPathname();
        $typeName = $typeDir->getFilename(); // fauna ou flora

        foreach (new DirectoryIterator($typePath) as $speciesDir) {
            if ($speciesDir->isDot() || !$speciesDir->isDir()) continue;

            $speciesPath = $speciesDir->getPathname();
            $speciesName = $speciesDir->getFilename(); // nome da espécie

            echo "Processando imagens de $typeName/$speciesName...\n";

            // Processar cada imagem no diretório da espécie
            // Nota: glob() é usado em vez de DirectoryIterator por compatibilidade
            // com o filesystem virtual do Docker Desktop no Windows (VirtioFS/musl libc),
            // onde DirectoryIterator retorna listagem truncada de diretórios com muitos arquivos.
            $imageCount = 0;
            $failedCount = 0;
            $imagePaths = array_merge(
                glob($speciesPath . '/*.jpg') ?: [],
                glob($speciesPath . '/*.jpeg') ?: [],
                glob($speciesPath . '/*.png') ?: [],
                glob($speciesPath . '/*.JPG') ?: [],
                glob($speciesPath . '/*.JPEG') ?: [],
                glob($speciesPath . '/*.PNG') ?: []
            );

            foreach ($imagePaths as $imagePath) {
                // Processar a imagem e extrair características
                $vector = preprocessImage($imagePath);

                if ($vector !== null) {
                    $samples[] = $vector;
                    $labels[] = $speciesName;
                    $imageCount++;
                } else {
                    $failedCount++;
                }
            }

            $falhaMsg = $failedCount > 0 ? " | \033[33m$failedCount falhas\033[0m" : '';
            echo "  - $imageCount imagens carregadas{$falhaMsg}\n";
        }
    }

    // Verificar se temos dados suficientes
    $totalSamples = count($samples);
    if ($totalSamples < 10) {
        throw new Exception("Dados insuficientes para treinamento. Encontradas apenas $totalSamples imagens.");
    }

    echo "\nTotal de imagens processadas: $totalSamples\n";
    $uniqueClasses = array_values(array_unique($labels));
    sort($uniqueClasses);
    echo "Classes encontradas: " . implode(', ', $uniqueClasses) . "\n\n";

    // Verificação: é necessário pelo menos 2 classes para classificação supervisionada
    if (count($uniqueClasses) < 2) {
        // Mostrar contagem por classe para ajudar o diagnóstico
        $counts = array_count_values($labels);
        echo "ERRO: É necessário pelo menos 2 classes para treinar o classificador.\n";
        echo "Resumo do dataset:\n";
        foreach ($counts as $cls => $cnt) {
            echo "- {$cls}: {$cnt} imagens\n";
        }
        echo "Adicione imagens de outra(s) espécie(s) em subpastas separadas dentro de $datasetDir (ex.: ML/Dataset/fauna/onca/, ML/Dataset/fauna/capivara/).\n";
        exit(1);
    }

    // Criar dataset rotulado
    $dataset = new Labeled($samples, $labels);

    // Executar K-Fold Cross Validation antes do split final (opcional)
    $kfoldMetrics = [];
    if ($kfold > 1) {
        echo "Executando Cross Validation ({$kfold}-fold)...\n";
        $kfoldMetrics = runKFold($dataset, $kfold, $epochs, $batchSize, $uniqueClasses);
        echo sprintf("CV Accuracy média: %.2f%% (σ=%.2f) | Macro F1 média: %.2f%%\n\n",
            $kfoldMetrics['accuracy_mean']*100,
            $kfoldMetrics['accuracy_std']*100,
            $kfoldMetrics['macro_f1_mean']*100
        );
    }

    // Dividir em conjuntos de treinamento e teste (80/20)
    echo "Dividindo dataset em conjuntos de treinamento e teste...\n";
    [$training, $testing] = $dataset->stratifiedSplit(0.8);

    // Imprime por classe (corrigido: calcular contagens reais)
    echo "Distribuição por classe:\n";
    $trainCounts = array_count_values($training->labels());
    $testCounts = array_count_values($testing->labels());
    foreach ($uniqueClasses as $cls) {
        $tr = $trainCounts[$cls] ?? 0;
        $te = $testCounts[$cls] ?? 0;
        echo "- {$cls}: treino={$tr}, teste={$te}\n";
    }

    echo "Conjunto de treinamento: " . $training->numSamples() . " amostras\n";
    echo "Conjunto de teste: " . $testing->numSamples() . " amostras\n\n";

    // Criar e configurar o estimador (rede neural) - reduzido para input 16x16 (256 features)
    echo "Configurando modelo de rede neural leve (32x32 grayscale)...\n";
    $estimator = new MultilayerPerceptron([
        new Dense(128),
        new Activation(new LeakyReLU()),
        new Dropout(0.2),
        new Dense(32),
        new Activation(new LeakyReLU()),
    ], $epochs, new Adam(0.001), $batchSize);

    // Criar pipeline com pré-processamento
    $pipeline = new Pipeline([
        new ZScaleStandardizer(),
    ], $estimator);

    // Criar modelo persistente
    $model = new PersistentModel($pipeline, new Filesystem($outputPath));

    // Treinar o modelo
    echo "Iniciando treinamento do modelo...\n";
    try {
        $model->train($training);
    } catch (Exception $e) {
        echo "ERRO durante o treinamento: " . $e->getMessage() . "\n";
        exit(1);
    }

    // Avaliar o modelo
    echo "Avaliando modelo no conjunto de teste...\n";
    $predictions = $model->predict($testing);
    $testEval = evaluateClassification($testing->labels(), $predictions, $uniqueClasses);
    echo "Acurácia (teste): " . number_format($testEval['accuracy'] * 100, 2) . "%\n";
    echo "Macro F1 (teste): " . number_format($testEval['macro_f1'] * 100, 2) . "%\n";
    echo "Matriz de confusão (linhas=verdadeiro, colunas=predito):\n";
    printConfusionMatrix($testEval['confusion_matrix'], $uniqueClasses);
    echo "\n";

    // Salvar o modelo
    echo "Salvando modelo em $outputPath...\n";
    $model->save();

    // Compressão gzip para reduzir I/O
    try {
        if (file_exists($outputPath)) {
            $rawSize = filesize($outputPath);
            $gzPath = $outputPath . '.gz';
            echo "Comprimindo modelo (gzip)...\n";
            $raw = file_get_contents($outputPath);
            file_put_contents($gzPath, gzencode($raw, 9));
            if (file_exists($gzPath)) {
                $gzSize = filesize($gzPath);
                $ratio = $rawSize > 0 ? (1 - ($gzSize / $rawSize)) * 100 : 0;
                echo sprintf("Tamanho original: %.2f KB | Comprimido: %.2f KB | Redução: %.1f%%\n", $rawSize/1024, $gzSize/1024, $ratio);
            }
            // Hash para integridade
            echo "SHA256 original: " . hash_file('sha256', $outputPath) . "\n";
            echo "SHA256 gzip:     " . hash_file('sha256', $gzPath) . "\n";
        }
    } catch (\Throwable $ce) {
        echo "Aviso: falha ao comprimir modelo - " . $ce->getMessage() . "\n";
    }

    // Salvar relatório JSON
    $report = [
        'timestamp' => date('c'),
        'epochs' => (int)$epochs,
        'batch_size' => (int)$batchSize,
        'kfold' => $kfold > 1 ? $kfold : null,
        'classes' => $uniqueClasses,
        'test' => [
            'accuracy' => $testEval['accuracy'],
            'macro_f1' => $testEval['macro_f1'],
            'per_class' => $testEval['per_class'],
            'confusion_matrix' => $testEval['confusion_matrix'],
        ],
        'cross_validation' => $kfoldMetrics ?: null,
    ];
    $reportPath = dirname($outputPath) . '/training_report.json';
    file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    echo "Relatório salvo em: {$reportPath}\n";

    // === Exportar também em Excel ===
try {
    echo "Gerando planilha Excel (training_report.xlsx)...\n";
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Relatório de Treinamento');

    // Cabeçalho principal
    $sheet->setCellValue('A1', 'Relatório de Treinamento RubixML');
    $sheet->mergeCells('A1:E1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

    // Dados gerais
    $sheet->fromArray([
        ['Data/Hora', $report['timestamp']],
        ['Épocas', $report['epochs']],
        ['Batch Size', $report['batch_size']],
        ['K-Fold', $report['kfold'] ?? '—'],
    ], null, 'A3');

    // Métricas globais
    $sheet->fromArray([
        ['Métrica', 'Valor'],
        ['Acurácia (teste)', $report['test']['accuracy']],
        ['Macro F1 (teste)', $report['test']['macro_f1']],
    ], null, 'A8');

    // Por classe
    $sheet->setCellValue('A12', 'Métricas por Classe');
    $sheet->getStyle('A12')->getFont()->setBold(true);

    $sheet->fromArray(['Classe', 'Precisão', 'Recall', 'F1', 'Amostras'], null, 'A13');
    $row = 14;
    foreach ($report['test']['per_class'] as $class => $vals) {
        $sheet->fromArray([
            $class,
            $vals['precision'],
            $vals['recall'],
            $vals['f1'],
            $vals['support']
        ], null, "A{$row}");
        $row++;
    }

    // Matriz de confusão
    $row += 2;
    $sheet->setCellValue("A{$row}", 'Matriz de Confusão');
    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
    $row++;

    // Cabeçalho da matriz
    $classes = array_keys($report['test']['confusion_matrix']);
    $sheet->fromArray(array_merge([''], $classes), null, "A{$row}");
    $row++;

    foreach ($classes as $trueClass) {
        $line = [$trueClass];
        foreach ($classes as $predClass) {
            $line[] = $report['test']['confusion_matrix'][$trueClass][$predClass];
        }
        $sheet->fromArray($line, null, "A{$row}");
        $row++;
    }

    // Ajustar larguras de coluna automaticamente
    foreach (range('A', 'H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Salvar o arquivo
    $excelPath = dirname($outputPath) . '/training_report.xlsx';
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($excelPath);

    echo "Planilha Excel salva em: {$excelPath}\n";
} catch (Exception $e) {
    echo "Aviso: falha ao gerar Excel - " . $e->getMessage() . "\n";
}

    echo "Treinamento concluído com sucesso!\n";

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Pré-processa uma imagem para extração de características.
 * ATENÇÃO: este pipeline deve ser IDÊNTICO ao de RubixMLClassificationService::preprocessImage().
 * Qualquer divergência reduz a acurácia na inferência.
 *
 * @param string $imagePath Caminho para a imagem
 * @return array|null Vetor de 256 features (16×16 grayscale normalizado) ou null em caso de erro
 */
function preprocessImage(string $imagePath): ?array
{
    try {
        $imageData = file_get_contents($imagePath);
        if ($imageData === false) {
            echo "  [AVISO] Falha ao ler arquivo: $imagePath\n";
            return null;
        }

        $image = @imagecreatefromstring($imageData);
        if (!$image) {
            echo "  [AVISO] Formato inválido ou corrompido: $imagePath\n";
            return null;
        }

        $size = 16; // 16x16 => 256 features (grayscale)

        $resized = imagecreatetruecolor($size, $size);
        if (!$resized) {
            imagedestroy($image);
            echo "  [AVISO] Falha ao alocar canvas de redimensionamento: $imagePath\n";
            return null;
        }

        // Suporte a PNG com canal alpha: fundo branco antes de compor
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $white = imagecolorallocate($resized, 255, 255, 255);
        imagefilledrectangle($resized, 0, 0, $size, $size, $white);
        imagealphablending($resized, true);

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
                // Conversão para escala de cinza e normalização [0, 1]
                $gray = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255.0;
                $vector[] = $gray;
            }
        }

        imagedestroy($image);
        imagedestroy($resized);

        // Validação da dimensão — deve ser sempre $size * $size
        if (count($vector) !== ($size * $size)) {
            echo "  [AVISO] Dimensão inesperada do vetor (" . count($vector) . " features): $imagePath\n";
            return null;
        }

        return $vector;

    } catch (\Throwable $e) {
        echo "  [ERRO] Falha ao processar imagem $imagePath: " . $e->getMessage() . "\n";
        return null;
    }
}

/**
 * Executa K-Fold cross validation manual para o pipeline definido
 * @return array
 */
function runKFold(Labeled $dataset, int $k, int $epochs, int $batchSize, array $classes): array
{
    $n = $dataset->numSamples();
    if ($k < 2 || $k > $n) return [];

    // Índices embaralhados mantendo estratificação simples por classe
    $indicesByClass = [];
    foreach ($dataset->labels() as $idx => $label) {
        $indicesByClass[$label][] = $idx;
    }
    foreach ($indicesByClass as &$arr) shuffle($arr);
    unset($arr);

    $folds = array_fill(0, $k, []);
    // distribuir de forma round-robin cada classe
    foreach ($indicesByClass as $label => $arr) {
        $i = 0;
        foreach ($arr as $idx) {
            $folds[$i % $k][] = $idx;
            $i++;
        }
    }

    $accs = [];
    $macroF1s = [];

    for ($fi=0; $fi<$k; $fi++) {
        $testIdx = $folds[$fi];
        $trainIdx = [];
        for ($fj=0; $fj<$k; $fj++) {
            if ($fj === $fi) continue;
            $trainIdx = array_merge($trainIdx, $folds[$fj]);
        }
        sort($testIdx); sort($trainIdx);

        $trainSamples = $trainLabels = [];
        foreach ($trainIdx as $ti) {
            $trainSamples[] = $dataset->sample($ti);
            $trainLabels[] = $dataset->label($ti);
        }
        $testSamples = $testLabels = [];
        foreach ($testIdx as $ti) {
            $testSamples[] = $dataset->sample($ti);
            $testLabels[] = $dataset->label($ti);
        }

        $trainSet = new Labeled($trainSamples, $trainLabels);
        $testSet  = new Labeled($testSamples, $testLabels);

        // Recriar o mesmo estimador leve usado no treino principal
        $estimator = new MultilayerPerceptron([
            new Dense(128),
            new Activation(new LeakyReLU()),
            new Dropout(0.2),
            new Dense(32),
            new Activation(new LeakyReLU()),
        ], $epochs, new Adam(0.001), $batchSize);
        $pipeline = new Pipeline([
            new ZScaleStandardizer(),
        ], $estimator);

        try {
            $pipeline->train($trainSet);
            $pred = $pipeline->predict($testSet);
            $eval = evaluateClassification($testSet->labels(), $pred, $classes);
            $accs[] = $eval['accuracy'];
            $macroF1s[] = $eval['macro_f1'];
            echo sprintf("Fold %d/%d - Acc: %.2f%% MacroF1: %.2f%%\n", $fi+1, $k, $eval['accuracy']*100, $eval['macro_f1']*100);
        } catch (\Throwable $e) {
            echo "[KFold] Erro no fold " . ($fi+1) . ": " . $e->getMessage() . "\n";
        }
    }

    $accMean = array_sum($accs)/max(count($accs),1);
    $accStd = stddev($accs, $accMean);
    $f1Mean = array_sum($macroF1s)/max(count($macroF1s),1);
    $f1Std = stddev($macroF1s, $f1Mean);

    return [
        'folds' => $k,
        'accuracy_mean' => $accMean,
        'accuracy_std' => $accStd,
        'macro_f1_mean' => $f1Mean,
        'macro_f1_std' => $f1Std,
    ];
}

function evaluateClassification(array $true, array $pred, array $classes): array
{
    $total = count($true);
    $correct = 0;
    $cm = [];
    foreach ($classes as $r) {
        foreach ($classes as $c) {
            $cm[$r][$c] = 0;
        }
    }

    foreach ($true as $i => $t) {
        $p = $pred[$i] ?? null;
        if ($p === $t) $correct++;
        if (isset($cm[$t][$p])) {
            $cm[$t][$p]++;
        }
    }

    // Precision/Recall/F1 por classe
    $perClass = [];
    $f1Sum = 0; $f1Count=0;
    foreach ($classes as $cls) {
        $tp = $cm[$cls][$cls];
        $fp = 0; $fn = 0;
        foreach ($classes as $other) {
            if ($other !== $cls) {
                $fp += $cm[$other][$cls]; // preditos como cls mas verdadeiros outra coisa
                $fn += $cm[$cls][$other]; // verdadeiros cls mas preditos outra coisa
            }
        }
        $precision = ($tp + $fp) > 0 ? $tp/($tp+$fp) : 0.0;
        $recall    = ($tp + $fn) > 0 ? $tp/($tp+$fn) : 0.0;
        $f1 = ($precision + $recall) > 0 ? 2*$precision*$recall/($precision+$recall) : 0.0;
        $perClass[$cls] = [
            'precision' => $precision,
            'recall' => $recall,
            'f1' => $f1,
            'support' => array_sum($cm[$cls]),
        ];
        $f1Sum += $f1; $f1Count++;
    }
    $macroF1 = $f1Count>0 ? $f1Sum/$f1Count : 0.0;

    return [
        'accuracy' => $total>0 ? $correct/$total : 0.0,
        'macro_f1' => $macroF1,
        'per_class' => $perClass,
        'confusion_matrix' => $cm,
    ];
}

function printConfusionMatrix(array $cm, array $classes): void
{
    // Header
    echo str_pad('', 14);
    foreach ($classes as $c) echo str_pad($c, 12);
    echo "\n";
    foreach ($classes as $r) {
        echo str_pad($r, 14);
        foreach ($classes as $c) {
            echo str_pad($cm[$r][$c], 12);
        }
        echo "\n";
    }
}

function stddev(array $vals, float $mean): float
{
    $n = count($vals);
    if ($n < 2) return 0.0;
    $acc = 0.0;
    foreach ($vals as $v) $acc += ($v - $mean)*($v - $mean);
    return sqrt($acc/($n-1));
}