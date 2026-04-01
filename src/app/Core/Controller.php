<?php
namespace App\Core;

/**
 * Classe base para todos os controladores
 */
class Controller
{
    /**
     * Renderiza uma view
     * 
     * @param string $view Nome da view
     * @param array $data Dados a serem passados para a view
     * @return void
     */
    protected function render($view, $data = [])
    {
        // Extrai os dados para que fiquem disponíveis como variáveis na view
        extract($data);
        
        // Caminho completo para o arquivo da view
        $viewFile = ROOT_DIR . "/app/Views/{$view}.php";
        
        // Verifica se o arquivo da view existe
        if (!file_exists($viewFile)) {
            throw new \Exception("View {$view} não encontrada");
        }
        
        // Inicia o buffer de saída
        ob_start();
        
        // Inclui o arquivo da view
        require $viewFile;
        
        // Obtém o conteúdo do buffer e limpa
        $content = ob_get_clean();
        
        // Exibe o conteúdo
        echo $content;
    }
    
    /**
     * Retorna uma resposta JSON
     * 
     * @param mixed $data Dados a serem convertidos para JSON
     * @param int $statusCode Código de status HTTP
     * @return void
     */
    protected function json($data, $statusCode = 200)
    {
        // Define o código de status HTTP
        http_response_code($statusCode);
        
        // Define o cabeçalho Content-Type
       // header('Content-Type: application/json');
       header('Content-Type: application/json; charset=utf-8');

        
        // Converte os dados para JSON e exibe
        //echo json_encode($data);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }
    
    /**
     * Redireciona para outra URL
     * 
     * @param string $url URL de destino
     * @return void
     */
    protected function redirect($url)
    {
        header("Location: {$url}");
        exit;
    }
}
