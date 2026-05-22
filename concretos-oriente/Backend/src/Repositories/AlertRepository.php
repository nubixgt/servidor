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
        $stmt = $this->pdo->query("SELECT * FROM alerts_history ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
