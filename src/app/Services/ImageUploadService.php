<?php
namespace App\Services;

/**
 * Serviço para gerenciar o upload de imagens
 */
class ImageUploadService
{
    /**
     * Configuração de upload
     * @var array
     */
    private $config;

    /**
     * Construtor
     */
    public function __construct()
    {
        $config = require ROOT_DIR . '/app/Config/config.php';
        $this->config = $config['upload'];

        // garantir que o diretório existe
        if (!is_dir($this->config['directory'])) {
            mkdir($this->config['directory'], 0755, true);
        }
    }

    /**
     * Processa o upload de uma imagem
     */
    public function upload($file)
    {
        $logFile = ROOT_DIR . '/debug_upload_service.log';

        file_put_contents($logFile, "Iniciando upload()\n", FILE_APPEND);
        file_put_contents($logFile, print_r($file, true), FILE_APPEND);

        // verificar se o arquivo foi enviado
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            throw new \Exception('Nenhum arquivo foi enviado');
        }

        // verificar erro de upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Erro no upload do arquivo');
        }

        // verificar tipo MIME
        $mimeType = mime_content_type($file['tmp_name']);

        if (!in_array($mimeType, $this->config['allowed_types'])) {
            throw new \Exception('Tipo de arquivo não permitido. Apenas JPEG é aceito.');
        }

        // verificar tamanho
        if ($file['size'] > $this->config['max_size']) {
            throw new \Exception(
                'Arquivo muito grande. Máximo: ' .
                ($this->config['max_size'] / 1024 / 1024) . 'MB'
            );
        }

        // obter extensão
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // aceitar apenas jpg e jpeg
        $allowed = ['jpg', 'jpeg'];

        if (!in_array($extension, $allowed)) {
            throw new \Exception('Formato inválido. Apenas JPG ou JPEG são permitidos.');
        }

        // gerar nome único
        $filename = uniqid('img_', true) . '.' . $extension;

        // destino real no servidor
        $destination = $this->config['directory'] . '/' . $filename;

        // mover arquivo
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \Exception('Erro ao salvar o arquivo.');
        }

        file_put_contents($logFile, "Arquivo salvo em: $destination\n", FILE_APPEND);

        // retornar dados
        return [
            'filename' => $filename,
            'path' => $destination,
            'url' => '/uploads/images/' . $filename,
            'mime_type' => $mimeType,
            'size' => $file['size']
        ];
    }
}