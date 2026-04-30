<?php
namespace App\Services;

use Rubix\ML\Persisters\Filesystem;
use Rubix\ML\PersistentModel;
use Rubix\ML\Datasets\Unlabeled;

/**
 * Serviço de classificação de imagens usando RubixML
 */
class RubixMLClassificationService
{
    /**
     * Caminho para o modelo treinado
     *
     * @var string
     */
    private string $modelPath;

    /**
     * Configurações do serviço
     *
     * @var array
     */
    private array $config = [];

    /**
     * Mapeamento de espécies
     *
     * @var array
     */
    private array $speciesMapping = [];

    /**
     * Cache estático do modelo para evitar recarregar por requisição
     * @var mixed
     */
    private static $cachedModel = null;

    /**
     * Construtor
     */
    public function __construct()
    {
        $config = require ROOT_DIR . '/app/Config/config.php';
    $this->config = $config['ai_service'] ?? [];
    // Preferir modelo comprimido se existir
    $defaultBase = ROOT_DIR . '/app/ML/models/species_classifier.rbx';
    $gzCandidate = $defaultBase . '.gz';
    $chosen = file_exists($gzCandidate) ? $gzCandidate : $defaultBase;
    $this->modelPath = $this->config['model_path'] ?? $chosen;

        // Mapeamento de espécies para o modelo (ajuste do rótulo "quero-quero")
        $this->speciesMapping = [
            'quero-quero' => 'Quero-quero',
            'capivara' => 'Capivara',
            'graxaim' => 'Graxaim',
            'babosa_do_campo' => 'Babosa-do-campo',
            'trevo_nativo' => 'Trevo-nativo'
        ];
    }

    /**
     * Classifica uma imagem usando o modelo RubixML
     *
     * @param string $imagePath Caminho para a imagem
     * @return array Resultado da classificação
     */
    public function classifyImage(string $imagePath): array
    {
        try {
            // Verificar se o arquivo existe
            if (!file_exists($imagePath)) {
                return [
                    'species' => null,
                    'confidence' => 0,
                    'error' => 'Arquivo de imagem não encontrado',
                    'all_labels' => []
                ];
            }

            $estimator = $this->loadModel();
            if ($estimator === null) {
                return [
                    'species' => null,
                    'confidence' => 0,
                    'error' => 'Modelo de classificação não disponível',
                    'all_labels' => []
                ];
            }

            // Preparar a imagem para classificação
            $image = $this->preprocessImage($imagePath);

            // Realizar a predição
            $inference = new Unlabeled([ $image ]);
            $pred = $estimator->predict($inference);
            $label = $pred[0] ?? null;
            $probabilities = [];
            if (method_exists($estimator, 'proba')) {
                try {
                    $proba = $estimator->proba($inference);
                    $probabilities = $proba[0] ?? [];
                } catch (\Throwable $t) {
                    $probabilities = $label ? [$label => 1.0] : [];
                }
            } elseif ($label) {
                $probabilities = [$label => 1.0];
            }
            arsort($probabilities);
            $class = key($probabilities);
            $confidence = $class !== null ? $probabilities[$class] : 0.0;

            // Mapear para o nome da espécie
            $species = $this->speciesMapping[$class] ?? $class;

            // Preparar todos os rótulos com suas probabilidades
            $allLabels = [];
            foreach ($probabilities as $label => $prob) {
                $allLabels[] = [
                    'description' => $this->speciesMapping[$label] ?? $label,
                    'confidence' => $prob
                ];
            }

            return [
                'species' => $species,
                'confidence' => $confidence,
                'all_labels' => $allLabels,
                'error' => null,
            ];
        } catch (\Exception $e) {
            // Log do erro
            if ($this->config['enable_logging'] ?? false) {
                error_log('Erro na classificação: ' . $e->getMessage());
            }

            return [
                'species' => null,
                'confidence' => 0,
                'error' => 'Erro na classificação: ' . $e->getMessage(),
                'all_labels' => []
            ];
        }
    }

    /**
     * Verifica se a confiança é suficiente para validação automática
     *
     * @param float $confidence Nível de confiança
     * @return bool
     */
    public function isConfidenceSufficient(float $confidence): bool
    {
        $threshold = $this->config['confidence_threshold'] ?? 0.7;
        return $confidence >= $threshold;
    }

    /**
     * Pré-processa a imagem para classificação.
     * ATENÇÃO: este pipeline deve ser IDÊNTICO ao de train_model.php::preprocessImage().
     * Qualquer divergência reduz a acurácia na inferência.
     *
     * @param string $imagePath Caminho para a imagem
     * @return array Vetor de 256 features (16×16 grayscale normalizado)
     */
    private function preprocessImage(string $imagePath): array
    {
        $size = 16; // 16x16 => 256 features (grayscale)

        $raw = file_get_contents($imagePath);
        if ($raw === false) {
            throw new \RuntimeException('Falha ao ler a imagem.');
        }

        $im = @imagecreatefromstring($raw);
        if (!$im) {
            throw new \RuntimeException('Formato de imagem inválido ou corrompido.');
        }

        $res = imagecreatetruecolor($size, $size);
        if (!$res) {
            imagedestroy($im);
            throw new \RuntimeException('Falha ao alocar canvas de redimensionamento.');
        }

        // Suporte a PNG com canal alpha: fundo branco antes de compor
        imagealphablending($res, false);
        imagesavealpha($res, true);
        $white = imagecolorallocate($res, 255, 255, 255);
        imagefilledrectangle($res, 0, 0, $size, $size, $white);
        imagealphablending($res, true);

        imagecopyresampled($res, $im, 0, 0, 0, 0, $size, $size, imagesx($im), imagesy($im));

        $vec = [];
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $rgb = imagecolorat($res, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                // Conversão para escala de cinza e normalização [0, 1]
                $gray = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255.0;
                $vec[] = $gray;
            }
        }

        imagedestroy($im);
        imagedestroy($res);

        if (count($vec) !== ($size * $size)) {
            throw new \RuntimeException('Dimensão inesperada do vetor de imagem: ' . count($vec) . ' features.');
        }

        return $vec;
    }

    private function loadModel()
    {
        if (self::$cachedModel !== null) {
            return self::$cachedModel;
        }
        if (!file_exists($this->modelPath)) {
            return null;
        }
        try {
            if (str_ends_with($this->modelPath, '.gz')) {
                $cmp = file_get_contents($this->modelPath);
                if ($cmp === false) throw new \RuntimeException('Falha ao ler modelo comprimido');
                $raw = gzdecode($cmp);
                if ($raw === false) throw new \RuntimeException('Falha ao descomprimir modelo');
                // Criar arquivo temporário para reutilizar mecanismo oficial de loading
                $tmpPath = sys_get_temp_dir() . '/rubix_model_' . md5($this->modelPath . filemtime($this->modelPath)) . '.rbx';
                if (!file_exists($tmpPath)) {
                    file_put_contents($tmpPath, $raw);
                }
                $model = PersistentModel::load(new Filesystem($tmpPath));
            } else {
                $model = PersistentModel::load(new Filesystem($this->modelPath));
            }
            self::$cachedModel = $model;
            return $model;
        } catch (\Throwable $e) {
            if (($this->config['enable_logging'] ?? false)) {
                error_log('[RubixML] Erro ao carregar modelo: '.$e->getMessage());
            }
            return null;
        }
    }
}