<?php
/**
 * Ponto de entrada da aplicação
 * Inicializa o autoloader e o roteador
 */

// Define a constante de diretório raiz
define('ROOT_DIR', dirname(__DIR__));

// Carrega o autoloader
require_once ROOT_DIR . '/app/Core/Autoloader.php';

// Inicializa o autoloader
$autoloader = new \App\Core\Autoloader();
$autoloader->register();

// Carrega a configuração
$config = require_once ROOT_DIR . '/app/Config/config.php';

// Inicializa o roteador
$router = new \App\Core\Router();

// Carrega as rotas
require_once ROOT_DIR . '/app/Config/routes.php';

// Processa a requisição
$router->dispatch();
