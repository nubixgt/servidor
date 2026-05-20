<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class CalendarioModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT id, title, date, category, description, files, created_at FROM calendario_eventos ORDER BY date ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT id, title, date, category, description, files, created_at FROM calendario_eventos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("INSERT INTO calendario_eventos (title, date, category, description, files) VALUES (:title, :date, :category, :description, :files)");
        $success = $stmt->execute([
            ':title' => $data['title'],
            ':date' => $data['date'],
            ':category' => $data['category'],
            ':description' => $data['description'] ?? null,
            ':files' => $data['files'] ?? null
        ]);
        if ($success) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("UPDATE calendario_eventos SET title = :title, date = :date, category = :category, description = :description, files = :files WHERE id = :id");
        return $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':date' => $data['date'],
            ':category' => $data['category'],
            ':description' => $data['description'] ?? null,
            ':files' => $data['files'] ?? null
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM calendario_eventos WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
