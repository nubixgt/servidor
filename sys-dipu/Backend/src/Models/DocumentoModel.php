<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class DocumentoModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT id, tipo, nombre, entidad, DATE_FORMAT(fecha, '%Y-%m-%d') as fecha, created_at FROM fiscalizacion_documentos ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("INSERT INTO fiscalizacion_documentos (tipo, nombre, entidad, fecha) VALUES (:tipo, :nombre, :entidad, :fecha)");
        return $stmt->execute([
            ':tipo' => $data['tipo'],
            ':nombre' => $data['nombre'],
            ':entidad' => $data['entidad'],
            ':fecha' => $data['fecha']
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM fiscalizacion_documentos WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
