<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class InspectorModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByUserId($userId)
    {
        $stmt = $this->db->prepare("SELECT id, codigo, nombre, area, estado FROM inspectores WHERE usuario_id = :userId");
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getVisitas($inspectorId)
    {
        $stmt = $this->db->prepare("SELECT id, establecimiento, direccion, fecha_programada, tipo_inspeccion, estado FROM visitas WHERE inspector_id = :inspectorId ORDER BY fecha_programada ASC");
        $stmt->bindParam(':inspectorId', $inspectorId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVisitasPendientes($inspectorId)
    {
        $stmt = $this->db->prepare("SELECT id, establecimiento, direccion, fecha_programada, tipo_inspeccion, estado FROM visitas WHERE inspector_id = :inspectorId AND estado = 'pendiente' ORDER BY fecha_programada ASC");
        $stmt->bindParam(':inspectorId', $inspectorId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateVisitaEstado($visitaId, $estado)
    {
        $stmt = $this->db->prepare("UPDATE visitas SET estado = :estado WHERE id = :visitaId");
        $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
        $stmt->bindParam(':visitaId', $visitaId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
