<?php
namespace App\Models;

use App\Core\Model;

/**
 * Modelo para a tabela de identificações
 */
class Identification extends Model
{
    /**
     * Nome da tabela associada ao modelo
     * @var string
     */
    protected $table = 'identifications';
    
    /**
     * Busca identificações por status
     * 
     * @param string $status Status da identificação (success, pending_validation, validated)
     * @return array
     */
    public function findByStatus($status)
    {
        $stmt = $this->db->prepare("
            SELECT i.*, s.name as species_name, s.scientific_name, s.type 
            FROM {$this->table} i
            LEFT JOIN species s ON i.species_id = s.id
            WHERE i.status = :status
            ORDER BY i.created_at DESC
        ");
        $stmt->bindParam(':status', $status, \PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca identificações pendentes de validação
     * 
     * @return array
     */
    public function findPending()
    {
        return $this->findByStatus('pending_validation');
    }
    
    /**
     * Atualiza o status de uma identificação
     * 
     * @param int $id ID da identificação
     * @param string $status Novo status
     * @param int|null $speciesId ID da espécie (opcional)
     * @return bool
     */
    public function updateStatus($id, $status, $speciesId = null)
    {
        $data = ['status' => $status];
        
        if ($speciesId !== null) {
            $data['species_id'] = $speciesId;
        }
        
        return $this->update($id, $data);
    }
}
