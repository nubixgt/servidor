<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class AlertRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAllConfigs(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM alerts_config ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createConfig(array $data): int
    {
        $sql = "INSERT INTO alerts_config (nombre, tipo_evento, canales, destinatarios, umbral, activa)
                VALUES (:nombre, :tipo_evento, :canales, :destinatarios, :umbral, :activa)";
        
        $this->pdo->prepare($sql)->execute([
            'nombre'        => $data['nombre'],
            'tipo_evento'   => $data['tipo_evento'],
            'canales'       => $data['canales'],
            'destinatarios' => $data['destinatarios'],
            'umbral'        => $data['umbral'],
            'activa'        => $data['activa']
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function deleteConfig(int $id): void
    {
        $this->pdo->prepare("DELETE FROM alerts_config WHERE id = :id")->execute(['id' => $id]);
    }

    public function findAllHistory(): array
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM alerts_history ORDER BY created_at DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function createAlertHistory(array $data): int
    {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `alerts_history` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(255) NOT NULL,
                `category` VARCHAR(100) NOT NULL DEFAULT 'maquinaria',
                `description` TEXT NULL,
                `is_urgent` TINYINT(1) DEFAULT 0,
                `is_read` TINYINT(1) DEFAULT 0,
                `project_or_meta` VARCHAR(255) NULL,
                `value_or_priority` VARCHAR(100) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        } catch (\Throwable $e) {}

        $sql = "INSERT INTO alerts_history (title, category, description, is_urgent, is_read, project_or_meta, value_or_priority)
                VALUES (:title, :category, :description, :is_urgent, 0, :project_or_meta, :value_or_priority)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'title'             => $data['title'],
            'category'          => $data['category'] ?? 'maquinaria',
            'description'       => $data['description'] ?? '',
            'is_urgent'         => !empty($data['is_urgent']) ? 1 : 0,
            'project_or_meta'   => $data['project_or_meta'] ?? 'General',
            'value_or_priority' => $data['value_or_priority'] ?? 'Normal',
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function deleteHistory(int $id): void
    {
        $this->pdo->prepare("DELETE FROM alerts_history WHERE id = :id")->execute(['id' => $id]);
    }
}
