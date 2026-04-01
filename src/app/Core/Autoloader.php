<?php
namespace App\Core;

/**
 * Classe de autoloading para carregar classes automaticamente
 */
class Autoloader
{
    /**
     * Registra o autoloader
     */
    public function register()
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Carrega uma classe com base no namespace
     * 
     * @param string $className Nome completo da classe com namespace
     * @return bool
     */
    public function loadClass($className)
    {
        // Converte namespace para caminho de arquivo
        $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);

        // Remove "App\" do início e adiciona ao caminho do app
        if (strpos($className, 'App' . DIRECTORY_SEPARATOR) === 0) {
            $className = substr($className, 4);
            $file = ROOT_DIR . '/app/' . $className . '.php';
        } else {
            $file = ROOT_DIR . '/' . $className . '.php';
        }

        // Verifica se o arquivo existe e o carrega
        if (file_exists($file)) {
            require_once $file;
            return true;
        }

        return false;
    }
}
