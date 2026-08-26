<?php
namespace App\Repositories\Clima;

use App\Utils\Database;
use PDO;

class AlertaRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, u.NombreCompleto as creador 
                FROM clima_alertas a
                LEFT JOIN clima_usuarios u ON a.id_usuario_creador = u.id
                ORDER BY a.fecha_creacion DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("
            SELECT a.*, u.NombreCompleto as creador 
            FROM clima_alertas a
            LEFT JOIN clima_usuarios u ON a.id_usuario_creador = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO clima_alertas (titulo, descripcion_corta, descripcion_detallada, tipo_alerta, nivel_severidad, region, icono, fecha_emision, fecha_vigencia, estado, id_usuario_creador) 
                VALUES (:titulo, :descripcion_corta, :descripcion_detallada, :tipo_alerta, :nivel_severidad, :region, :icono, :fecha_emision, :fecha_vigencia, :estado, :id_usuario_creador)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function update($id, array $data)
    {
        $sql = "UPDATE clima_alertas 
                SET titulo = :titulo, descripcion_corta = :descripcion_corta, descripcion_detallada = :descripcion_detallada, 
                    tipo_alerta = :tipo_alerta, nivel_severidad = :nivel_severidad, region = :region, 
                    icono = :icono, fecha_emision = :fecha_emision, fecha_vigencia = :fecha_vigencia, estado = :estado 
                WHERE id = :id";
        
        $data['id'] = $id;
        // Quitar id_usuario_creador de los datos a actualizar si no viene o no debe cambiar
        unset($data['id_usuario_creador']);

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM clima_alertas WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
