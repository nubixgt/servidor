<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\Piloto;
use PDO;

class PilotoRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll()
    {
        $sql = "SELECT p.*, GROUP_CONCAT(pm.maquina_id) as maquinas 
                FROM pilotos p 
                LEFT JOIN piloto_maquinas pm ON p.id = pm.piloto_id 
                GROUP BY p.id 
                ORDER BY p.created_at DESC";
        $stmt = $this->db->query($sql);
        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = new Piloto($row);
        }
        return $results;
    }

    public function findById($id)
    {
        $sql = "SELECT p.*, GROUP_CONCAT(pm.maquina_id) as maquinas 
                FROM pilotos p 
                LEFT JOIN piloto_maquinas pm ON p.id = pm.piloto_id 
                WHERE p.id = :id 
                GROUP BY p.id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? new Piloto($row) : null;
    }

    public function create(Piloto $piloto)
    {
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO pilotos (nombre, telefono, status) VALUES (:nombre, :telefono, :status)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'nombre' => $piloto->nombre,
                'telefono' => $piloto->telefono,
                'status' => $piloto->status
            ]);
            $id = $this->db->lastInsertId();

            if (!empty($piloto->maquinas)) {
                $this->insertMaquinas($id, $piloto->maquinas);
            }

            $this->db->commit();
            return (int)$id;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function update(Piloto $piloto)
    {
        try {
            $this->db->beginTransaction();

            $sql = "UPDATE pilotos SET nombre = :nombre, telefono = :telefono, status = :status WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'nombre' => $piloto->nombre,
                'telefono' => $piloto->telefono,
                'status' => $piloto->status,
                'id' => $piloto->id
            ]);

            $stmtDel = $this->db->prepare("DELETE FROM piloto_maquinas WHERE piloto_id = :id");
            $stmtDel->execute(['id' => $piloto->id]);

            if (!empty($piloto->maquinas)) {
                $this->insertMaquinas($piloto->id, $piloto->maquinas);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM pilotos WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    private function insertMaquinas($piloto_id, $maquinas)
    {
        $sql = "INSERT INTO piloto_maquinas (piloto_id, maquina_id) VALUES (:pid, :mid)";
        $stmt = $this->db->prepare($sql);
        foreach ($maquinas as $m_id) {
            $stmt->execute(['pid' => $piloto_id, 'mid' => $m_id]);
        }
    }
}
