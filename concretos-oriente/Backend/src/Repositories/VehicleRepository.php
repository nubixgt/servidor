<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class VehicleRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    public function findAllWithDetails(): array
    {
        $sql = "SELECT 
                    v.*, 
                    CONCAT(p.nombres, ' ', p.apellidos) AS piloto_nombre 
                FROM vehicles v
                LEFT JOIN personnel p ON p.id = v.piloto_id
                ORDER BY v.id DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM vehicles WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO vehicles
                    (placa, kilometraje, marca, modelo, piloto_id, observaciones, 
                     foto_frontal, foto_trasera, frecuencia_prioridad, estatus)
                VALUES
                    (:placa, :kilometraje, :marca, :modelo, :piloto_id, :observaciones, 
                     :foto_frontal, :foto_trasera, :frecuencia_prioridad, :estatus)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'placa' => $data['placa'],
            'kilometraje' => $data['kilometraje'],
            'marca' => $data['marca'],
            'modelo' => $data['modelo'],
            'piloto_id' => $data['piloto_id'] ?: null,
            'observaciones' => $data['observaciones'] ?? null,
            'foto_frontal' => $data['foto_frontal'] ?? null,
            'foto_trasera' => $data['foto_trasera'] ?? null,
            'frecuencia_prioridad' => $data['frecuencia_prioridad'],
            'estatus' => $data['estatus']
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE vehicles SET
                    placa = :placa,
                    kilometraje = :kilometraje,
                    marca = :marca,
                    modelo = :modelo,
                    piloto_id = :piloto_id,
                    observaciones = :observaciones,
                    frecuencia_prioridad = :frecuencia_prioridad,
                    estatus = :estatus
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'placa' => $data['placa'],
            'kilometraje' => $data['kilometraje'],
            'marca' => $data['marca'],
            'modelo' => $data['modelo'],
            'piloto_id' => $data['piloto_id'] ?: null,
            'observaciones' => $data['observaciones'] ?? null,
            'frecuencia_prioridad' => $data['frecuencia_prioridad'],
            'estatus' => $data['estatus']
        ]);
    }

    public function updatePhotos(int $id, ?string $frontal, ?string $trasera): void
    {
        $updates = [];
        $params = ['id' => $id];
        
        if ($frontal !== null) {
            $updates[] = "foto_frontal = :frontal";
            $params['frontal'] = $frontal;
        }
        if ($trasera !== null) {
            $updates[] = "foto_trasera = :trasera";
            $params['trasera'] = $trasera;
        }

        if (empty($updates)) {
            return;
        }

        $sql = "UPDATE vehicles SET " . implode(', ', $updates) . " WHERE id = :id";
        $this->pdo->prepare($sql)->execute($params);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM vehicles WHERE id = :id")->execute(['id' => $id]);
    }
}
