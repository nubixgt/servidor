<?php
namespace App\Models;

use App\Utils\Database;
use Exception;
use PDO;

class ModuloModel
{
    private $db;
    private $tableMap = [
        'iniciativas' => 'iniciativas',
        'citaciones' => 'citaciones',
        'comisiones' => 'comisiones',
        'compromisos' => 'compromisos_distritales',
        'actividades' => 'actividades',
        'redes' => 'redes_sociales',
        'afiliaciones' => 'afiliaciones_politicas'
    ];

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTableExists();
    }

    private function ensureTableExists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS redes_sociales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(255) NOT NULL,
            descripcion TEXT NULL,
            plataforma VARCHAR(50) NOT NULL,
            enlace VARCHAR(500) NOT NULL,
            fecha DATE NOT NULL,
            hora VARCHAR(50) NULL,
            estado VARCHAR(50) NOT NULL DEFAULT 'Publicado',
            impacto VARCHAR(100) NOT NULL DEFAULT 'Medio',
            interacciones VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );";
        $this->db->exec($sql);
    }

    private function getTableName($modulo)
    {
        if (!isset($this->tableMap[$modulo])) {
            throw new Exception("Módulo '$modulo' no válido o no configurado.");
        }
        return $this->tableMap[$modulo];
    }

    public function getAll($modulo)
    {
        $table = $this->getTableName($modulo);
        
        // Return date formatted consistently for frontend inputs
        $stmt = $this->db->prepare("SELECT * FROM $table ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($modulo, $data)
    {
        $table = $this->getTableName($modulo);
        
        // Remove id if present in data to allow auto-increment
        unset($data['id']);
        
        if (empty($data)) {
            throw new Exception("No se proporcionaron datos para insertar.");
        }

        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ":$field";
        }, $fields);

        $sql = "INSERT INTO $table (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $this->db->prepare($sql);
        
        $params = [];
        foreach ($data as $key => $val) {
            $params[":$key"] = $val;
        }

        $success = $stmt->execute($params);
        if ($success) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function update($modulo, $id, $data)
    {
        $table = $this->getTableName($modulo);
        unset($data['id']); // cannot update id

        if (empty($data)) {
            return false;
        }

        $sets = [];
        $params = [':id' => (int)$id];
        
        foreach ($data as $key => $val) {
            $sets[] = "$key = :$key";
            $params[":$key"] = $val;
        }

        $sql = "UPDATE $table SET " . implode(", ", $sets) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    public function delete($modulo, $id)
    {
        $table = $this->getTableName($modulo);
        
        $stmt = $this->db->prepare("DELETE FROM $table WHERE id = :id");
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
