<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class DesviacionModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO desviaciones_laboratorio (
                inspector_id, fecha_resultado, codigo_muestra, establecimiento, 
                tipo_analisis, resultado_obtenido, parametro_fuera_norma, 
                accion_tomada, estado_seguimiento, observaciones
            ) 
            VALUES (
                :inspector_id, :fecha_resultado, :codigo_muestra, :establecimiento, 
                :tipo_analisis, :resultado_obtenido, :parametro_fuera_norma, 
                :accion_tomada, :estado_seguimiento, :observaciones
            )
        ");

        $params = [
            ':inspector_id'         => $data['inspector_id'] ?? null,
            ':fecha_resultado'      => $data['fecha_resultado'],
            ':codigo_muestra'       => $data['codigo_muestra'],
            ':establecimiento'      => $data['establecimiento'],
            ':tipo_analisis'        => $data['tipo_analisis'],
            ':resultado_obtenido'   => $data['resultado_obtenido'],
            ':parametro_fuera_norma'=> $data['parametro_fuera_norma'],
            ':accion_tomada'        => $data['accion_tomada'],
            ':estado_seguimiento'   => $data['estado_seguimiento'] ?? 'Abierto',
            ':observaciones'        => $data['observaciones'] ?? null
        ];

        if ($stmt->execute($params)) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    public function addDocument($desviacionId, $nombre, $ruta)
    {
        $stmt = $this->db->prepare("
            INSERT INTO desviaciones_documentos (desviacion_id, nombre_archivo, ruta_archivo) 
            VALUES (:desviacion_id, :nombre_archivo, :ruta_archivo)
        ");
        
        return $stmt->execute([
            ':desviacion_id'   => $desviacionId,
            ':nombre_archivo'  => $nombre,
            ':ruta_archivo'    => $ruta
        ]);
    }

    public function updateEstado($id, $estado)
    {
        $stmt = $this->db->prepare("
            UPDATE desviaciones_laboratorio 
            SET estado_seguimiento = :estado 
            WHERE id = :id
        ");
        
        return $stmt->execute([
            ':id'     => $id,
            ':estado' => $estado
        ]);
    }

    public function getAllWithDetails()
    {
        $stmt = $this->db->prepare("
            SELECT d.id, d.fecha_resultado, d.codigo_muestra, d.establecimiento, 
                   d.tipo_analisis, d.resultado_obtenido, d.parametro_fuera_norma, 
                   d.accion_tomada, d.estado_seguimiento, d.observaciones, d.fecha_creacion,
                   COALESCE(i.nombre, 'Administrador') AS inspector_nombre, 
                   COALESCE(i.codigo, 'N/A') AS inspector_codigo, 
                   COALESCE(i.area, 'Oficina Central') AS inspector_area,
                   (SELECT COUNT(*) FROM desviaciones_documentos doc WHERE doc.desviacion_id = d.id) AS total_adjuntos
            FROM desviaciones_laboratorio d
            LEFT JOIN inspectores i ON d.inspector_id = i.id
            ORDER BY d.fecha_resultado DESC, d.id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT d.id, d.fecha_resultado, d.codigo_muestra, d.establecimiento, 
                   d.tipo_analisis, d.resultado_obtenido, d.parametro_fuera_norma, 
                   d.accion_tomada, d.estado_seguimiento, d.observaciones, d.fecha_creacion,
                   COALESCE(i.nombre, 'Administrador') AS inspector_nombre, 
                   COALESCE(i.codigo, 'N/A') AS inspector_codigo, 
                   COALESCE(i.area, 'Oficina Central') AS inspector_area
            FROM desviaciones_laboratorio d
            LEFT JOIN inspectores i ON d.inspector_id = i.id
            WHERE d.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getDocumentsByDesviacionId($desviacionId)
    {
        $stmt = $this->db->prepare("
            SELECT id, nombre_archivo, ruta_archivo, fecha_subida 
            FROM desviaciones_documentos 
            WHERE desviacion_id = :desviacion_id 
            ORDER BY fecha_subida DESC
        ");
        $stmt->execute([':desviacion_id' => $desviacionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
