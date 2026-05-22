<?php
namespace App\Models;

use App\Utils\Database;
use Exception;
use PDO;

class ArchivoModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTableExists();
    }

    private function ensureTableExists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS archivo_central (
            id INT AUTO_INCREMENT PRIMARY KEY,
            expediente_id VARCHAR(50) NOT NULL UNIQUE,
            titulo VARCHAR(255) NOT NULL,
            tipo VARCHAR(50) NOT NULL,
            fecha DATE NOT NULL,
            modulo VARCHAR(100) NOT NULL,
            estado VARCHAR(50) NOT NULL,
            file_url VARCHAR(500) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $this->db->exec($sql);
    }

    public function getAll($filters = [])
    {
        $sql = "SELECT *, DATE_FORMAT(fecha, '%Y-%m-%d') as fecha_formateada FROM archivo_central WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (expediente_id LIKE :search OR titulo LIKE :search OR modulo LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['tipo']) && $filters['tipo'] !== 'Todos los Tipos') {
            // Support both singular and plural (Leyes vs Ley)
            $tipoSingular = rtrim($filters['tipo'], 's');
            if ($filters['tipo'] === 'Leyes') $tipoSingular = 'Ley';
            
            $sql .= " AND (tipo = :tipo OR tipo = :tipoSingular)";
            $params[':tipo'] = $filters['tipo'];
            $params[':tipoSingular'] = $tipoSingular;
        }

        if (!empty($filters['year']) && $filters['year'] !== 'Cualquier Año') {
            $sql .= " AND YEAR(fecha) = :year";
            $params[':year'] = (int)$filters['year'];
        }

        $sql .= " ORDER BY fecha DESC, id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM archivo_central WHERE id = :id");
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByExpedienteId($expedienteId)
    {
        $stmt = $this->db->prepare("SELECT * FROM archivo_central WHERE expediente_id = :expediente_id");
        $stmt->bindValue(':expediente_id', $expedienteId, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $sql = "INSERT INTO archivo_central (expediente_id, titulo, tipo, fecha, modulo, estado, file_url) 
                VALUES (:expediente_id, :titulo, :tipo, :fecha, :modulo, :estado, :file_url)";
        $stmt = $this->db->prepare($sql);
        
        $success = $stmt->execute([
            ':expediente_id' => $data['expediente_id'],
            ':titulo' => $data['titulo'],
            ':tipo' => $data['tipo'],
            ':fecha' => $data['fecha'],
            ':modulo' => $data['modulo'],
            ':estado' => $data['estado'],
            ':file_url' => $data['file_url'] ?? null
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

        $fields = ['expediente_id', 'titulo', 'tipo', 'fecha', 'modulo', 'estado', 'file_url'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $sql = "UPDATE archivo_central SET " . implode(", ", $sets) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM archivo_central WHERE id = :id");
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
