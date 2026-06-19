<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class SupervisionModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO supervisiones (
                inspector_id, fecha_supervision, establecimiento, hallazgos_detectados, 
                norma_especifica, observaciones, estado_hallazgo, fecha_cumplimiento, 
                verificacion_oficial
            ) 
            VALUES (
                :inspector_id, :fecha_supervision, :establecimiento, :hallazgos_detectados, 
                :norma_especifica, :observaciones, :estado_hallazgo, :fecha_cumplimiento, 
                :verificacion_oficial
            )
        ");

        $params = [
            ':inspector_id'         => $data['inspector_id'] ?? null,
            ':fecha_supervision'    => $data['fecha_supervision'],
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

    public function addDocument($supervisionId, $nombre, $ruta)
    {
        $stmt = $this->db->prepare("
            INSERT INTO supervisiones_documentos (supervision_id, nombre_archivo, ruta_archivo) 
            VALUES (:supervision_id, :nombre_archivo, :ruta_archivo)
        ");
        
        return $stmt->execute([
            ':supervision_id'  => $supervisionId,
            ':nombre_archivo'  => $nombre,
            ':ruta_archivo'    => $ruta
        ]);
    }

    public function updateSeguimiento($id, $estado, $fechaCumplimiento, $verificacion)
    {
        $stmt = $this->db->prepare("
            UPDATE supervisiones 
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
            SELECT s.id, s.fecha_supervision, s.establecimiento, s.hallazgos_detectados, 
                   s.norma_especifica, s.observaciones, s.estado_hallazgo, s.fecha_cumplimiento,
                   s.verificacion_oficial, s.fecha_creacion,
                   COALESCE(i.nombre, 'Administrador') AS inspector_nombre, 
                   COALESCE(i.codigo, 'N/A') AS inspector_codigo, 
                   COALESCE(i.area, 'Oficina Central') AS inspector_area,
                   (SELECT COUNT(*) FROM supervisiones_documentos doc WHERE doc.supervision_id = s.id) AS total_adjuntos
            FROM supervisiones s
            LEFT JOIN inspectores i ON s.inspector_id = i.id
            ORDER BY s.fecha_supervision DESC, s.id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT s.id, s.fecha_supervision, s.establecimiento, s.hallazgos_detectados, 
                   s.norma_especifica, s.observaciones, s.estado_hallazgo, s.fecha_cumplimiento,
                   s.verificacion_oficial, s.fecha_creacion,
                   COALESCE(i.nombre, 'Administrador') AS inspector_nombre, 
                   COALESCE(i.codigo, 'N/A') AS inspector_codigo, 
                   COALESCE(i.area, 'Oficina Central') AS inspector_area
            FROM supervisiones s
            LEFT JOIN inspectores i ON s.inspector_id = i.id
            WHERE s.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getDocumentsBySupervisionId($supervisionId)
    {
        $stmt = $this->db->prepare("
            SELECT id, nombre_archivo, ruta_archivo, fecha_subida 
            FROM supervisiones_documentos 
            WHERE supervision_id = :supervision_id 
            ORDER BY fecha_subida DESC
        ");
        $stmt->execute([':supervision_id' => $supervisionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
