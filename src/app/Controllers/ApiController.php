<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Species;
use App\Models\Identification;
use App\Services\ImageUploadService;
use App\Services\ImageClassificationService;

/**
 * Controlador para os endpoints da API
 */
class ApiController extends Controller
{
    private $speciesModel;
    private $identificationModel;
    private $uploadService;
    private $classificationService;

    public function __construct()
    {
        $this->speciesModel = new Species();
        $this->identificationModel = new Identification();
        $this->uploadService = new ImageUploadService();
        $this->classificationService = new ImageClassificationService();

        // Verificar a autenticação por API Key
        $this->checkApiKey();
    }

    /**
     * Verifica se a requisição possui uma API Key válida
     */
    private function checkApiKey()
    {
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
        $authHeader = $headers['Authorization'] ?? ($headers['authorization'] ?? '');

        if (!preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            $this->json(['error' => 'API Key não fornecida ou formato inválido'], 401);
        }

        $apiKey = $matches[1];

        if ($apiKey !== 'cb2023-test-api-key') {
            $this->json(['error' => 'API Key inválida'], 401);
        }
    }

    /**
     * Endpoint para identificação de espécies
     */
    public function identify()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método não permitido'], 405);
        }

        try {
            if (!isset($_FILES['image']) || empty($_FILES['image']['name'])) {
                $this->json(['error' => 'Nenhuma imagem enviada'], 400);
            }

            // Processar upload
            $uploadResult = $this->uploadService->upload($_FILES['image']);

            // Classificar imagem
            $classificationResult = $this->classificationService->classifyImage($uploadResult['path']);
            $isConfident = $this->classificationService->isConfidenceSufficient($classificationResult['confidence']);

            // Preparar dados para salvar
            $identificationData = [
                'image_path' => $uploadResult['url'],
                'confidence' => $classificationResult['confidence'] * 100,
                'status' => $isConfident ? 'success' : 'pending_validation'
            ];

            if ($isConfident && $classificationResult['species']) {
                $species = $this->speciesModel->findByName($classificationResult['species']);
                if ($species) {
                    $identificationData['species_id'] = $species['id'];
                }
            }

            $identificationId = $this->identificationModel->create($identificationData);

/*             $response = [
                'identification_id' => $identificationId,
                'image_url' => $uploadResult['url'],
                'confidence' => $classificationResult['confidence'],
            ];

            if ($isConfident) {
                $response['status'] = 'success';
                $response['species'] = $classificationResult['species'];
            } else {
                $response['status'] = 'pending_validation';
                $response['message'] = 'Identificação requer análise de especialista';
                if (!empty($classificationResult['species'])) {
                    $response['suggestion'] = $classificationResult['species'];
                }
            }

            $this->json($response);
 */

            $response = [
    'status' => $isConfident ? 'success' : 'pending_validation',

    'message' => $isConfident
        ? 'Imagem processada com sucesso'
        : 'Identificação requer validação manual',

    'data' => [

        'identification_id' => $identificationId,

        'image' => [
            'name' => $_FILES['image']['name'],
            'url' => $uploadResult['url'],
            'type' => $_FILES['image']['type'],
            'size' => $_FILES['image']['size']
        ],

        'classification' => [
            'species' => $classificationResult['species'] ?? null,

            'confidence' => round(
                $classificationResult['confidence'] * 100,
                2
            ),

            'validated' => $isConfident
        ],

        'processing' => [
            'date' => date('Y-m-d H:i:s'),
            'model' => 'species_classifier.rbx',
            'api_version' => '1.0'
        ]
    ]
];

if (!$isConfident && !empty($classificationResult['species'])) {

    $response['data']['classification']['suggestion']
        = $classificationResult['species'];
}

file_put_contents(
    __DIR__ . '/../../storage/last-response-front.json',
    json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

$this->json($response);

        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Endpoint para listar espécies
     */
    public function listSpecies()
    {
        try {
            $type = $_GET['type'] ?? null;
            $species = $type ? $this->speciesModel->findByType($type) : $this->speciesModel->findAll();
            $this->json(['species' => $species]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint para listar identificações
     */
    public function listIdentifications()
    {
        try {
            $status = $_GET['status'] ?? null;
            $formato = $_GET['formato'] ?? 'json';

            $identifications = $status ? 
                $this->identificationModel->findByStatus($status) : 
                $this->identificationModel->findAll();

            if ($formato === 'excel') {
                require_once __DIR__ . '/../../../vendor/autoload.php';
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                if (!empty($identifications)) {
                    $col = 1;
                    foreach (array_keys($identifications[0]) as $key) {
                        $sheet->setCellValueByColumnAndRow($col, 1, ucfirst($key));
                        $col++;
                    }

                    $row = 2;
                    foreach ($identifications as $linha) {
                        $col = 1;
                        foreach ($linha as $valor) {
                            $sheet->setCellValueByColumnAndRow($col, $row, $valor);
                            $col++;
                        }
                        $row++;
                    }
                } else {
                    $sheet->setCellValue('A1', 'Nenhuma identificação encontrada');
                }

                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="identificacoes.xlsx"');

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save('php://output');
                exit;
            }

            $this->json(['identifications' => $identifications]);

        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listPendingIdentifications()
    {
        try {
            $pendingIdentifications = $this->identificationModel->findPending();
            $this->json(['pending_identifications' => $pendingIdentifications]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function validateIdentification()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método não permitido'], 405);
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['identification_id']) || !isset($data['species_id'])) {
                $this->json(['error' => 'Dados incompletos'], 400);
            }

            $success = $this->identificationModel->updateStatus(
                $data['identification_id'],
                'validated',
                $data['species_id']
            );

            if ($success) {
                $this->json(['message' => 'Identificação validada com sucesso']);
            } else {
                $this->json(['error' => 'Erro ao validar identificação'], 500);
            }

        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
