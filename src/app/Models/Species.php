<?php
namespace App\Models;

use App\Core\Model;

/**
 * Modelo para a tabela de espécies
 */
class Species extends Model
{
    /**
     * Nome da tabela associada ao modelo
     * @var string
     */
    protected $table = 'species';
    
    /**
     * Busca espécies por tipo (fauna ou flora)
     * 
     * @param string $type Tipo de espécie (fauna ou flora)
     * @return array
     */
    public function findByType($type)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE type = :type");
        $stmt->bindParam(':type', $type, \PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca uma espécie pelo nome
     * 
     * @param string $name Nome da espécie
     * @return array|false
     */
    public function findByName($name)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE name = :name");
        $stmt->bindParam(':name', $name, \PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
