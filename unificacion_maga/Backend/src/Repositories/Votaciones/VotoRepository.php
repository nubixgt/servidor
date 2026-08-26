<?php
namespace App\Repositories\Votaciones;

use App\Utils\Database;
use PDO;

class VotoRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT * FROM votaciones_votos");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countTotalVotes()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM votaciones_votos");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function getByEvento($evento_id)
    {
        $stmt = $this->db->prepare("
            SELECT v.*, c.nombre as congresista_nombre, b.nombre as bloque_nombre 
            FROM votaciones_votos v
            JOIN votaciones_congresistas c ON v.congresista_id = c.id
            LEFT JOIN votaciones_bloques b ON v.bloque_id = b.id
            WHERE v.evento_id = ?
        ");
        $stmt->execute([$evento_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveVoto($data)
    {
        $sql = "INSERT INTO votaciones_votos (evento_id, congresista_id, bloque_id, voto) 
                VALUES (:evento_id, :congresista_id, :bloque_id, :voto)
                ON DUPLICATE KEY UPDATE voto = :voto, bloque_id = :bloque_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
}
