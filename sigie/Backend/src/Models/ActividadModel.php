<?php
namespace App\Models;

use App\Utils\Database;
use PDO;
use Exception;

class ActividadModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtener inspectores activos
     */
    public function getActiveInspectors()
    {
        $stmt = $this->db->prepare("SELECT id, nombre, codigo, area FROM inspectores WHERE estado = 1 ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crear una actividad programada (Jefe de Administración)
     */
    public function create(array $data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO actividades_programadas (
                inspector_id, fecha_programada, tipo_actividad, 
                establecimiento, codigo_actividad, observaciones, 
                estado, es_programada
            ) VALUES (
                :inspector_id, :fecha_programada, :tipo_actividad, 
                :establecimiento, :codigo_actividad, :observaciones, 
                'programada', 1
            )
        ");

        $params = [
            ':inspector_id'     => (int)$data['inspector_id'],
            ':fecha_programada' => $data['fecha_programada'],
            ':tipo_actividad'   => $data['tipo_actividad'],
            ':establecimiento'  => $data['establecimiento'],
            ':codigo_actividad' => $data['codigo_actividad'],
            ':observaciones'    => $data['observaciones'] ?? null
        ];

        if ($stmt->execute($params)) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Crear una actividad espontánea (Inspector)
     */
    public function createSpontaneous(array $data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO actividades_programadas (
                inspector_id, fecha_programada, tipo_actividad, 
                establecimiento, codigo_actividad, observaciones, 
                estado, es_programada
            ) VALUES (
                :inspector_id, :fecha_programada, :tipo_actividad, 
                :establecimiento, :codigo_actividad, :observaciones, 
                'ejecutada', 0
            )
        ");

        $params = [
            ':inspector_id'     => (int)$data['inspector_id'],
            ':fecha_programada' => $data['fecha_programada'],
            ':tipo_actividad'   => $data['tipo_actividad'],
            ':establecimiento'  => $data['establecimiento'],
            ':codigo_actividad' => $data['codigo_actividad'],
            ':observaciones'    => $data['observaciones'] ?? null
        ];

        if ($stmt->execute($params)) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Obtener listado de actividades del mes, opcionalmente por inspector
     */
    public function getByMonth(string $mes, int $inspectorId = null)
    {
        $sql = "
            SELECT a.id, a.inspector_id, a.fecha_programada, a.tipo_actividad, 
                   a.establecimiento, a.codigo_actividad, a.observaciones, 
                   a.estado, a.es_programada, a.motivo_incumplimiento, a.fecha_creacion,
                   i.nombre AS inspector_nombre, i.codigo AS inspector_codigo, i.area AS inspector_area
            FROM actividades_programadas a
            INNER JOIN inspectores i ON a.inspector_id = i.id
            WHERE DATE_FORMAT(a.fecha_programada, '%Y-%m') = :mes
        ";

        if ($inspectorId !== null) {
            $sql .= " AND a.inspector_id = :inspector_id";
        }

        $sql .= " ORDER BY a.fecha_programada ASC, i.nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':mes', $mes, PDO::PARAM_STR);
        if ($inspectorId !== null) {
            $stmt->bindParam(':inspector_id', $inspectorId, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener una actividad por su ID
     */
    public function getById(int $id)
    {
        $stmt = $this->db->prepare("
            SELECT a.id, a.inspector_id, a.fecha_programada, a.tipo_actividad, 
                   a.establecimiento, a.codigo_actividad, a.observaciones, 
                   a.estado, a.es_programada, a.motivo_incumplimiento, a.fecha_creacion,
                   i.nombre AS inspector_nombre, i.codigo AS inspector_codigo, i.area AS inspector_area
            FROM actividades_programadas a
            INNER JOIN inspectores i ON a.inspector_id = i.id
            WHERE a.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Actualizar una actividad (campos generales)
     */
    public function update(int $id, array $data)
    {
        $stmt = $this->db->prepare("
            UPDATE actividades_programadas 
            SET inspector_id = :inspector_id,
                fecha_programada = :fecha_programada,
                tipo_actividad = :tipo_actividad,
                establecimiento = :establecimiento,
                codigo_actividad = :codigo_actividad,
                observaciones = :observaciones
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id'               => $id,
            ':inspector_id'     => (int)$data['inspector_id'],
            ':fecha_programada' => $data['fecha_programada'],
            ':tipo_actividad'   => $data['tipo_actividad'],
            ':establecimiento'  => $data['establecimiento'],
            ':codigo_actividad' => $data['codigo_actividad'],
            ':observaciones'    => $data['observaciones'] ?? null
        ]);
    }

    /**
     * Eliminar una actividad
     */
    public function delete(int $id)
    {
        $stmt = $this->db->prepare("DELETE FROM actividades_programadas WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Registrar la ejecución o incumplimiento de una actividad (Inspector)
     */
    public function updateEstado(int $id, string $estado, string $observaciones, string $motivoIncumplimiento = null)
    {
        $stmt = $this->db->prepare("
            UPDATE actividades_programadas 
            SET estado = :estado,
                observaciones = :observaciones,
                motivo_incumplimiento = :motivo_incumplimiento
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id'                     => $id,
            ':estado'                 => $estado,
            ':observaciones'          => $observaciones,
            ':motivo_incumplimiento'  => $motivoIncumplimiento
        ]);
    }

    /**
     * Reporte consolidado por Inspectores
     */
    public function getReporteInspectores(string $mes)
    {
        $stmt = $this->db->prepare("
            SELECT 
                i.id AS inspector_id,
                i.nombre AS inspector_nombre,
                i.codigo AS inspector_codigo,
                i.area AS inspector_area,
                COALESCE(SUM(CASE WHEN a.es_programada = 1 THEN 1 ELSE 0 END), 0) AS total_programadas,
                COALESCE(SUM(CASE WHEN a.es_programada = 1 AND a.estado = 'ejecutada' THEN 1 ELSE 0 END), 0) AS ejecutadas_programadas,
                COALESCE(SUM(CASE WHEN a.es_programada = 1 AND a.estado = 'no_ejecutada' THEN 1 ELSE 0 END), 0) AS incumplidas_programadas,
                COALESCE(SUM(CASE WHEN a.es_programada = 1 AND a.estado = 'programada' THEN 1 ELSE 0 END), 0) AS pendientes_programadas,
                COALESCE(SUM(CASE WHEN a.es_programada = 0 AND a.estado = 'ejecutada' THEN 1 ELSE 0 END), 0) AS espontaneas,
                COALESCE(SUM(CASE WHEN a.estado = 'ejecutada' THEN 1 ELSE 0 END), 0) AS total_ejecutadas
            FROM inspectores i
            LEFT JOIN actividades_programadas a ON i.id = a.inspector_id AND DATE_FORMAT(a.fecha_programada, '%Y-%m') = :mes
            WHERE i.estado = 1
            GROUP BY i.id, i.nombre, i.codigo, i.area
            ORDER BY i.nombre ASC
        ");
        $stmt->execute([':mes' => $mes]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reporte consolidado por Establecimientos
     */
    public function getReporteEstablecimientos(string $mes)
    {
        $stmt = $this->db->prepare("
            SELECT 
                a.establecimiento,
                COALESCE(SUM(CASE WHEN a.es_programada = 1 THEN 1 ELSE 0 END), 0) AS total_programadas,
                COALESCE(SUM(CASE WHEN a.es_programada = 1 AND a.estado = 'ejecutada' THEN 1 ELSE 0 END), 0) AS ejecutadas_programadas,
                COALESCE(SUM(CASE WHEN a.es_programada = 1 AND a.estado = 'no_ejecutada' THEN 1 ELSE 0 END), 0) AS incumplidas_programadas,
                COALESCE(SUM(CASE WHEN a.es_programada = 0 AND a.estado = 'ejecutada' THEN 1 ELSE 0 END), 0) AS espontaneas,
                COALESCE(SUM(CASE WHEN a.estado = 'ejecutada' THEN 1 ELSE 0 END), 0) AS total_ejecutadas
            FROM actividades_programadas a
            WHERE DATE_FORMAT(a.fecha_programada, '%Y-%m') = :mes
            GROUP BY a.establecimiento
            ORDER BY a.establecimiento ASC
        ");
        $stmt->execute([':mes' => $mes]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
