<?php
namespace App\Models;

use App\Utils\Database;
use App\Models\MuestreoModel;
use PDO;

class ImportacionModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /* =========================================================================
     * IMPORTADORES
     * ========================================================================= */

    public function getAllImportadores()
    {
        $stmt = $this->db->prepare("SELECT id, nombre, nit, tipo_productos, fecha_creacion FROM importadores ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getImportadorById(int $id)
    {
        $stmt = $this->db->prepare("SELECT id, nombre, nit, tipo_productos, fecha_creacion FROM importadores WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createImportador(array $data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO importadores (nombre, nit, tipo_productos) 
            VALUES (:nombre, :nit, :tipo_productos)
        ");
        
        $params = [
            ':nombre'         => $data['nombre'],
            ':nit'            => $data['nit'],
            ':tipo_productos' => $data['tipo_productos']
        ];

        if ($stmt->execute($params)) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    public function updateImportador(int $id, array $data)
    {
        $stmt = $this->db->prepare("
            UPDATE importadores 
            SET nombre = :nombre,
                nit = :nit,
                tipo_productos = :tipo_productos
            WHERE id = :id
        ");
        
        return $stmt->execute([
            ':id'             => $id,
            ':nombre'         => $data['nombre'],
            ':nit'            => $data['nit'],
            ':tipo_productos' => $data['tipo_productos']
        ]);
    }

    public function deleteImportador(int $id)
    {
        $stmt = $this->db->prepare("DELETE FROM importadores WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /* =========================================================================
     * IMPORTACIONES
     * ========================================================================= */

    public function getAllImportaciones(int $importadorId = null, string $tipoProducto = null)
    {
        $sql = "
            SELECT i.id, i.fecha, i.importador_id, i.tipo_producto, i.volumen_kilos, i.establecimiento, i.fecha_creacion,
                   imp.nombre AS importador_nombre, imp.nit AS importador_nit
            FROM importaciones i
            INNER JOIN importadores imp ON i.importador_id = imp.id
            WHERE 1=1
        ";

        if ($importadorId !== null) {
            $sql .= " AND i.importador_id = :importador_id";
        }
        if ($tipoProducto !== null && $tipoProducto !== '') {
            $sql .= " AND i.tipo_producto = :tipo_producto";
        }

        $sql .= " ORDER BY i.fecha DESC, i.id DESC";

        $stmt = $this->db->prepare($sql);
        
        if ($importadorId !== null) {
            $stmt->bindParam(':importador_id', $importadorId, PDO::PARAM_INT);
        }
        if ($tipoProducto !== null && $tipoProducto !== '') {
            $stmt->bindParam(':tipo_producto', $tipoProducto, PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createImportacion(array $data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO importaciones (fecha, importador_id, tipo_producto, volumen_kilos, establecimiento) 
            VALUES (:fecha, :importador_id, :tipo_producto, :volumen_kilos, :establecimiento)
        ");

        $params = [
            ':fecha'          => $data['fecha'],
            ':importador_id'  => (int)$data['importador_id'],
            ':tipo_producto'  => $data['tipo_producto'],
            ':volumen_kilos'  => (float)$data['volumen_kilos'],
            ':establecimiento'=> $data['establecimiento'] ?? null
        ];

        if ($stmt->execute($params)) {
            $importacionId = (int)$this->db->lastInsertId();

            // Evaluar alarmas de volumen de inmediato para este importador y tipo de producto
            try {
                $muestreoModel = new MuestreoModel();
                $anio = (int)date('Y', strtotime($data['fecha']));
                $muestreoModel->evaluarAlarmasVolumen((int)$data['importador_id'], $data['tipo_producto'], $anio);
            } catch (\Exception $e) {
                // Evitamos que falle el registro de la importación si la evaluación falla
            }

            return $importacionId;
        }
        return false;
    }
}
