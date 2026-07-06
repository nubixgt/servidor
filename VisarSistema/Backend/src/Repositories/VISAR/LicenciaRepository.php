<?php
namespace App\Repositories\VISAR;

use App\Utils\Database;
use PDO;

class LicenciaRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getDb(): \PDO
    {
        return $this->db;
    }

    public function getAll($filters = [])
    {
        $sql = "SELECT * FROM visar_licencias WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (titular LIKE :search OR documento LIKE :search OR identificacion LIKE :search)";
            $params['search'] = "%{$filters['search']}%";
        }

        if (!empty($filters['estado'])) {
            $sql .= " AND estado = :estado";
            $params['estado'] = $filters['estado'];
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM visar_licencias WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO visar_licencias (documento, tipo, subtipo, titular, identificacion, fecha_emision, fecha_vencimiento, estado) 
                VALUES (:documento, :tipo, :subtipo, :titular, :identificacion, :fecha_emision, :fecha_vencimiento, :estado)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function update($id, array $data)
    {
        $sql = "UPDATE visar_licencias 
                SET documento = :documento, tipo = :tipo, subtipo = :subtipo, 
                    titular = :titular, identificacion = :identificacion, 
                    fecha_emision = :fecha_emision, fecha_vencimiento = :fecha_vencimiento, 
                    estado = :estado 
                WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM visar_licencias WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
