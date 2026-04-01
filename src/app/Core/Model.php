<?php
namespace App\Core;

/**
 * Classe base para todos os modelos
 */
class Model
{
    /**
     * Conexão com o banco de dados
     * @var \PDO
     */
    protected $db;
    
    /**
     * Nome da tabela associada ao modelo
     * @var string
     */
    protected $table;
    
    /**
     * Construtor
     */
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Busca todos os registros
     * 
     * @return array
     */
    public function findAll()
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca um registro pelo ID
     * 
     * @param int $id ID do registro
     * @return array|false
     */
    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Insere um novo registro
     * 
     * @param array $data Dados a serem inseridos
     * @return int|false ID do registro inserido ou false em caso de erro
     */
    public function create($data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $stmt = $this->db->prepare("INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})");
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Atualiza um registro
     * 
     * @param int $id ID do registro
     * @param array $data Dados a serem atualizados
     * @return bool
     */
    public function update($id, $data)
    {
        $setClause = [];
        foreach (array_keys($data) as $key) {
            $setClause[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setClause);
        
        $stmt = $this->db->prepare("UPDATE {$this->table} SET {$setClause} WHERE id = :id");
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Remove um registro
     * 
     * @param int $id ID do registro
     * @return bool
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        return $stmt->execute();
    }
}
