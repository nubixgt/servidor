<?php
namespace App\Repositories\VISAR;

use App\Utils\Database;
use PDO;

class InspeccionRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($filters = [])
    {
        $sql = "SELECT * FROM visar_inspecciones WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (productor LIKE :search OR codigo LIKE :search OR ubicacion LIKE :search)";
            $params['search'] = "%{$filters['search']}%";
        }

        if (!empty($filters['area'])) {
            $sql .= " AND area = :area";
            $params['area'] = $filters['area'];
        }

        $sql .= " ORDER BY fecha DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM visar_inspecciones WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO visar_inspecciones (codigo, area, productor, ubicacion, fecha, motivo, estado, riesgo) 
                VALUES (:codigo, :area, :productor, :ubicacion, :fecha, :motivo, :estado, :riesgo)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function update($id, array $data)
    {
        $sql = "UPDATE visar_inspecciones 
                SET codigo = :codigo, area = :area, productor = :productor, 
                    ubicacion = :ubicacion, fecha = :fecha, motivo = :motivo, 
                    estado = :estado, riesgo = :riesgo 
                WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM visar_inspecciones WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getStats()
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN riesgo = 'ALTO' THEN 1 ELSE 0 END) as alertas_criticas,
                SUM(CASE WHEN estado = 'EN INSPECCIÓN' OR estado = 'AGENDA' THEN 1 ELSE 0 END) as en_proceso,
                SUM(CASE WHEN estado = 'APROBADO' THEN 1 ELSE 0 END) as aprobados
            FROM visar_inspecciones
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
