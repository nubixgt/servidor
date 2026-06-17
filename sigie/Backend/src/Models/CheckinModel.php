<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class CheckinModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO check_ins (visita_id, inspector_id, latitud, longitud, observaciones, firma_path, foto_path, estado) 
            VALUES (:visita_id, :inspector_id, :latitud, :longitud, :observaciones, :firma_path, :foto_path, :estado)
        ");
        
        $params = [
            ':visita_id'     => $data['visita_id'] ?? null,
            ':inspector_id'  => $data['inspector_id'],
            ':latitud'       => $data['latitud'],
            ':longitud'      => $data['longitud'],
            ':observaciones' => $data['observaciones'] ?? null,
            ':firma_path'    => $data['firma_path'] ?? null,
            ':foto_path'     => $data['foto_path'] ?? null,
            ':estado'        => $data['estado'] ?? 'exitoso'
        ];

        return $stmt->execute($params);
    }

    public function getAllWithDetails()
    {
        $stmt = $this->db->prepare("
            SELECT c.id, c.fecha_hora, c.latitud, c.longitud, c.observaciones, c.firma_path, c.foto_path, c.estado,
                   i.nombre AS inspector_nombre, i.codigo AS inspector_codigo, i.area AS inspector_area,
                   v.establecimiento, v.direccion, v.tipo_inspeccion
            FROM check_ins c
            INNER JOIN inspectores i ON c.inspector_id = i.id
            LEFT JOIN visitas v ON c.visita_id = v.id
            ORDER BY c.fecha_hora DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats()
    {
        // 1. Total check-ins
        $totalCheckins = $this->db->query("SELECT COUNT(*) FROM check_ins")->fetchColumn();

        // 2. Check-ins con novedades
        $novedades = $this->db->query("SELECT COUNT(*) FROM check_ins WHERE estado = 'con_novedades'")->fetchColumn();

        // 3. Visitas pendientes
        $pendientes = $this->db->query("SELECT COUNT(*) FROM visitas WHERE estado = 'pendiente'")->fetchColumn();

        // 4. Inspectores activos
        $inspectoresActivos = $this->db->query("SELECT COUNT(*) FROM inspectores WHERE estado = 1")->fetchColumn();

        return [
            'total_checkins' => (int)$totalCheckins,
            'checkins_novedades' => (int)$novedades,
            'visitas_pendientes' => (int)$pendientes,
            'inspectores_activos' => (int)$inspectoresActivos
        ];
    }
}
