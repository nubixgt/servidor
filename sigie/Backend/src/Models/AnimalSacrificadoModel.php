<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class AnimalSacrificadoModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO animales_sacrificados (
                inspector_id, fecha_sacrificio, procedencia_departamento, 
                procedencia_municipio, procedencia_finca, clasificacion, 
                lote, propietario, cantidad, decomisos, 
                muestreo_oficial, documento_path, observaciones
            ) 
            VALUES (
                :inspector_id, :fecha_sacrificio, :procedencia_departamento, 
                :procedencia_municipio, :procedencia_finca, :clasificacion, 
                :lote, :propietario, :cantidad, :decomisos, 
                :muestreo_oficial, :documento_path, :observaciones
            )
        ");

        $params = [
            ':inspector_id'            => $data['inspector_id'] ?? null,
            ':fecha_sacrificio'        => $data['fecha_sacrificio'],
            ':procedencia_departamento'=> $data['procedencia_departamento'],
            ':procedencia_municipio'   => $data['procedencia_municipio'],
            ':procedencia_finca'       => $data['procedencia_finca'],
            ':clasificacion'           => $data['clasificacion'],
            ':lote'                    => $data['lote'],
            ':propietario'             => $data['propietario'],
            ':cantidad'                => $data['cantidad'],
            ':decomisos'               => $data['decomisos'] ?? null,
            ':muestreo_oficial'        => $data['muestreo_oficial'] ? 1 : 0,
            ':documento_path'          => $data['documento_path'] ?? null,
            ':observaciones'           => $data['observaciones'] ?? null
        ];

        return $stmt->execute($params);
    }

    public function getAllWithDetails()
    {
        $stmt = $this->db->prepare("
            SELECT a.id, a.fecha_sacrificio, a.procedencia_departamento, a.procedencia_municipio, 
                   a.procedencia_finca, a.clasificacion, a.lote, a.propietario, a.cantidad, 
                   a.decomisos, a.muestreo_oficial, a.documento_path, a.observaciones, a.fecha_creacion,
                   COALESCE(i.nombre, 'Administrador') AS inspector_nombre, 
                   COALESCE(i.codigo, 'N/A') AS inspector_codigo, 
                   COALESCE(i.area, 'Oficina Central') AS inspector_area
            FROM animales_sacrificados a
            LEFT JOIN inspectores i ON a.inspector_id = i.id
            ORDER BY a.fecha_sacrificio DESC, a.id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT a.id, a.fecha_sacrificio, a.procedencia_departamento, a.procedencia_municipio, 
                   a.procedencia_finca, a.clasificacion, a.lote, a.propietario, a.cantidad, 
                   a.decomisos, a.muestreo_oficial, a.documento_path, a.observaciones, a.fecha_creacion,
                   COALESCE(i.nombre, 'Administrador') AS inspector_nombre, 
                   COALESCE(i.codigo, 'N/A') AS inspector_codigo, 
                   COALESCE(i.area, 'Oficina Central') AS inspector_area
            FROM animales_sacrificados a
            LEFT JOIN inspectores i ON a.inspector_id = i.id
            WHERE a.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
