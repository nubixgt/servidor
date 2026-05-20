<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class PersonalModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT id, ministerio_id, nombre, tipo_puesto as tipoPuesto, sueldo, fecha_posesion as fechaPosesion, foto_nombre as fotoNombre, foto_preview as fotoPreview, created_at FROM fiscalizacion_personal ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("INSERT INTO fiscalizacion_personal (ministerio_id, nombre, tipo_puesto, sueldo, fecha_posesion, foto_nombre, foto_preview) VALUES (:ministerio_id, :nombre, :tipo_puesto, :sueldo, :fecha_posesion, :foto_nombre, :foto_preview)");
        return $stmt->execute([
            ':ministerio_id' => $data['ministerio_id'],
            ':nombre' => $data['nombre'],
            ':tipo_puesto' => $data['tipo_puesto'],
            ':sueldo' => $data['sueldo'] ?? null,
            ':fecha_posesion' => !empty($data['fecha_posesion']) ? $data['fecha_posesion'] : null,
            ':foto_nombre' => $data['foto_nombre'] ?? null,
            ':foto_preview' => $data['foto_preview'] ?? null
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM fiscalizacion_personal WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
