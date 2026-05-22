<?php
namespace App\Models;

use App\Utils\Database;
use Exception;
use PDO;

class TareaModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTableExists();
    }

    private function ensureTableExists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS dashboard_tareas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(255) NOT NULL,
            descripcion TEXT NULL,
            asignado_a INT NULL COMMENT 'ID del usuario asignado',
            fecha_limite DATE NOT NULL,
            prioridad VARCHAR(50) NOT NULL DEFAULT 'Media',
            estado VARCHAR(50) NOT NULL DEFAULT 'Pendiente',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );";
        $this->db->exec($sql);
    }

    public function getAll()
    {
        // Join with usuarios to get the name of the assigned member
        $sql = "SELECT t.*, u.nombre_completo as asignado_nombre 
                FROM dashboard_tareas t 
                LEFT JOIN usuarios u ON t.asignado_a = u.id 
                ORDER BY t.fecha_limite ASC, t.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByUser($userId)
    {
        $sql = "SELECT t.*, u.nombre_completo as asignado_nombre 
                FROM dashboard_tareas t 
                LEFT JOIN usuarios u ON t.asignado_a = u.id 
                WHERE t.asignado_a = :userId
                ORDER BY t.fecha_limite ASC, t.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':userId' => (int)$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $sql = "INSERT INTO dashboard_tareas (titulo, descripcion, asignado_a, fecha_limite, prioridad, estado) 
                VALUES (:titulo, :descripcion, :asignado_a, :fecha_limite, :prioridad, :estado)";
        $stmt = $this->db->prepare($sql);
        
        $success = $stmt->execute([
            ':titulo' => $data['titulo'],
            ':descripcion' => $data['descripcion'] ?? null,
            ':asignado_a' => !empty($data['asignado_a']) ? (int)$data['asignado_a'] : null,
            ':fecha_limite' => $data['fecha_limite'],
            ':prioridad' => $data['prioridad'] ?? 'Media',
            ':estado' => $data['estado'] ?? 'Pendiente'
        ]);

        if ($success) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function update($id, $data)
    {
        $sets = [];
        $params = [':id' => (int)$id];

        $fields = ['titulo', 'descripcion', 'asignado_a', 'fecha_limite', 'prioridad', 'estado'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "$field = :$field";
                if ($field === 'asignado_a') {
                    $params[':asignado_a'] = !empty($data['asignado_a']) ? (int)$data['asignado_a'] : null;
                } else {
                    $params[":$field"] = $data[$field];
                }
            }
        }

        if (empty($sets)) {
            return false;
        }

        $sql = "UPDATE dashboard_tareas SET " . implode(", ", $sets) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM dashboard_tareas WHERE id = :id");
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getMemberPerformance()
    {
        // Returns completed vs total tasks for each user to calculate actual efficiency
        $sql = "SELECT u.id, u.nombre_completo, 
                       SUM(CASE WHEN t.estado = 'Completada' THEN 1 ELSE 0 END) as completadas,
                       COUNT(t.id) as total
                FROM usuarios u
                LEFT JOIN dashboard_tareas t ON t.asignado_a = u.id
                GROUP BY u.id, u.nombre_completo
                HAVING total > 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
