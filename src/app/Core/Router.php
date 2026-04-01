<?php
namespace App\Core;

/**
 * Classe Router para gerenciar rotas e requisições
 */
class Router
{
    /**
     * Array de rotas registradas
     * @var array
     */
    private $routes = [];
    
    /**
     * Adiciona uma rota GET
     * 
     * @param string $path Caminho da URL
     * @param array $handler Controlador e método a ser chamado
     */
    public function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
    }
    
    /**
     * Adiciona uma rota POST
     * 
     * @param string $path Caminho da URL
     * @param array $handler Controlador e método a ser chamado
     */
    public function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
    }
    
    /**
     * Adiciona uma rota ao array de rotas
     * 
     * @param string $method Método HTTP
     * @param string $path Caminho da URL
     * @param array $handler Controlador e método a ser chamado
     */
    private function addRoute($method, $path, $handler)
    {
        // Converte o caminho para um padrão regex
        $pattern = $this->pathToRegex($path);
        
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }
    
    /**
     * Converte um caminho de URL em um padrão regex
     * 
     * @param string $path Caminho da URL
     * @return string Padrão regex
     */
    private function pathToRegex($path)
    {
        // Substitui parâmetros {param} por grupos de captura (?<param>[^/]+)
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?<$1>[^/]+)', $path);
        
        // Adiciona delimitadores e âncoras
        return "#^{$pattern}$#";
    }
    
    /**
     * Processa a requisição atual e chama o handler apropriado
     */
    public function dispatch()
    {
        // Obtém o método HTTP e o caminho da requisição
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove a barra final se existir
        $path = rtrim($path, '/');
        
        // Adiciona uma barra se o caminho estiver vazio
        if (empty($path)) {
            $path = '/';
        }
        
        // Procura por uma rota correspondente
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            if (preg_match($route['pattern'], $path, $matches)) {
                // Extrai os parâmetros da URL
                $params = array_filter($matches, function($key) {
                    return !is_numeric($key);
                }, ARRAY_FILTER_USE_KEY);
                
                // Obtém o controlador e o método
                list($controllerName, $methodName) = $route['handler'];
                
                // Instancia o controlador
                $controllerClass = "\\App\\Controllers\\{$controllerName}";
                $controller = new $controllerClass();
                
                // Chama o método com os parâmetros
                return call_user_func_array([$controller, $methodName], $params);
            }
        }
        
        // Se nenhuma rota corresponder, retorna 404
        header("HTTP/1.0 404 Not Found");
        echo json_encode(['error' => 'Endpoint não encontrado']);
        exit;
    }
}
