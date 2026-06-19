<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class NoConformidadModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO no_conformidades (
                inspector_id, fecha_inspeccion, establecimiento, hallazgos_detectados, 
                norma_especifica, observaciones, estado_hallazgo, fecha_cumplimiento, 
                verificacion_oficial
            ) 
            VALUES (
                :inspector_id, :fecha_inspeccion, :establecimiento, :hallazgos_detectados, 
                :norma_especifica, :observaciones, :estado_hallazgo, :fecha_cumplimiento, 
                :verificacion_oficial
            )
        ");

        $params = [
            ':inspector_id'         => $data['inspector_id'] ?? null,
            ':fecha_inspeccion'     => $data['fecha_inspeccion'],
            ':establecimiento'      => $data['establecimiento'],
            ':hallazgos_detectados' => $data['hallazgos_detectados'],
            ':norma_especifica'     => $data['norma_especifica'] ?? null,
            ':observaciones'        => $data['observaciones'] ?? null,
            ':estado_hallazgo'      => $data['estado_hallazgo'] ?? 'Abierto',
            ':fecha_cumplimiento'   => $data['fecha_cumplimiento'] ?? null,
            ':verificacion_oficial' => $data['verificacion_oficial'] ?? null
        ];

        if ($stmt->execute($params)) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    public function addDocument($noConformidadId, $nombre, $ruta)
    {
        $stmt = $this->db->prepare("
            INSERT INTO no_conformidades_documentos (no_conformidad_id, nombre_archivo, ruta_archivo) 
            VALUES (:no_conformidad_id, :nombre_archivo, :ruta_archivo)
        ");
        
        return $stmt->execute([
            ':no_conformidad_id' => $noConformidadId,
            ':nombre_archivo'    => $nombre,
            ':ruta_archivo'      => $ruta
        ]);
    }

    public function updateSeguimiento($id, $estado, $fechaCumplimiento, $verificacion)
    {
        $stmt = $this->db->prepare("
            UPDATE no_conformidades 
            SET estado_hallazgo = :estado,
                fecha_cumplimiento = :fecha_cumplimiento,
                verificacion_oficial = :verificacion 
            WHERE id = :id
        ");
        
        return $stmt->execute([
            ':id'                 => $id,
            ':estado'             => $estado,
            ':fecha_cumplimiento' => empty($fechaCumplimiento) ? null : $fechaCumplimiento,
            ':verificacion'       => empty($verificacion) ? null : $verificacion
        ]);
    }

    public function getAllWithDetails()
    {
        $stmt = $this->db->prepare("
            SELECT nc.id, nc.fecha_inspeccion, nc.establecimiento, nc.hallazgos_detectados, 
                   nc.norma_especifica, nc.observaciones, nc.estado_hallazgo, nc.fecha_cumplimiento,
                   nc.verificacion_oficial, nc.fecha_creacion,
                   COALESCE(i.nombre, 'Administrador') AS inspector_nombre, 
                   COALESCE(i.codigo, 'N/A') AS inspector_codigo, 
                   COALESCE(i.area, 'Oficina Central') AS inspector_area,
                   (SELECT COUNT(*) FROM no_conformidades_documentos doc WHERE doc.no_conformidad_id = nc.id) AS total_adjuntos
            FROM no_conformidades nc
            LEFT JOIN inspectores i ON nc.inspector_id = i.id
            ORDER BY nc.fecha_inspeccion DESC, nc.id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT nc.id, nc.fecha_inspeccion, nc.establecimiento, nc.hallazgos_detectados, 
                   nc.norma_especifica, nc.observaciones, nc.estado_hallazgo, nc.fecha_cumplimiento,
                   nc.verificacion_oficial, nc.fecha_creacion,
                   COALESCE(i.nombre, 'Administrador') AS inspector_nombre, 
                   COALESCE(i.codigo, 'N/A') AS inspector_codigo, 
                   COALESCE(i.area, 'Oficina Central') AS inspector_area
            FROM no_conformidades nc
            LEFT JOIN inspectores i ON nc.inspector_id = i.id
            WHERE nc.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getDocumentsByNoConformidadId($noConformidadId)
    {
        $stmt = $this->db->prepare("
            SELECT id, nombre_archivo, ruta_archivo, fecha_subida 
            FROM no_conformidades_documentos 
            WHERE no_conformidad_id = :no_conformidad_id 
            ORDER BY fecha_subida DESC
        ");
        $stmt->execute([':no_conformidad_id' => $noConformidadId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
