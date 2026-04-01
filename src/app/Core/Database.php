<?php
namespace App\Core;

/**
 * Classe Database para gerenciar a conexão com o banco de dados
 */
class Database
{
    /**
     * Instância única da classe (padrão Singleton)
     * @var Database
     */
    private static $instance = null;
    
    /**
     * Conexão PDO com o banco de dados
     * @var \PDO
     */
    private $connection;
    
    /**
     * Construtor privado (padrão Singleton)
     */
    private function __construct()
    {
        $config = require ROOT_DIR . '/app/Config/config.php';
        $dbConfig = $config['database'];
        
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset=utf8mb4";
        
        try {
            $this->connection = new \PDO(
                $dsn,
                $dbConfig['username'],
                $dbConfig['password'],
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (\PDOException $e) {
            die("Erro de conexão com o banco de dados: " . $e->getMessage());
        }
    }
    
    /**
     * Obtém a instância única da classe (padrão Singleton)
     * 
     * @return Database
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Obtém a conexão PDO
     * 
     * @return \PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }
    
    /**
     * Impede a clonagem (padrão Singleton)
     */
    private function __clone() {}
    
    /**
     * Impede a deserialização (padrão Singleton)
     */
    public function __wakeup() {}
}
