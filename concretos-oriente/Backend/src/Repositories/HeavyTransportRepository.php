<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class HeavyTransportRepository
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
                    ht.*,
                    CONCAT(p.nombres, ' ', p.apellidos) AS piloto_nombre
                FROM heavy_transport ht
                LEFT JOIN personnel p ON p.id = ht.piloto_id
                ORDER BY ht.id DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM heavy_transport WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO heavy_transport
                    (placa, tipo_transporte, tipo_seguro, ubicacion, estado,
                     precio, kilometraje, marca, modelo, piloto_id)
                VALUES
                    (:placa, :tipo_transporte, :tipo_seguro, :ubicacion, :estado,
                     :precio, :kilometraje, :marca, :modelo, :piloto_id)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'placa'           => $data['placa'],
            'tipo_transporte' => $data['tipo_transporte'],
            'tipo_seguro'     => $data['tipo_seguro'] ?: null,
            'ubicacion'       => $data['ubicacion'] ?: null,
            'estado'          => $data['estado'] ?? 'Nuevo',
            'precio'          => $data['precio'] ?: null,
            'kilometraje'     => $data['kilometraje'] ?? 0,
            'marca'           => $data['marca'],
            'modelo'          => $data['modelo'],
            'piloto_id'       => $data['piloto_id'] ?: null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE heavy_transport SET
                    placa           = :placa,
                    tipo_transporte = :tipo_transporte,
                    tipo_seguro     = :tipo_seguro,
                    ubicacion       = :ubicacion,
                    estado          = :estado,
                    precio          = :precio,
                    kilometraje     = :kilometraje,
                    marca           = :marca,
                    modelo          = :modelo,
                    piloto_id       = :piloto_id
                WHERE id = :id";

        $this->pdo->prepare($sql)->execute([
            'id'              => $id,
            'placa'           => $data['placa'],
            'tipo_transporte' => $data['tipo_transporte'],
            'tipo_seguro'     => $data['tipo_seguro'] ?: null,
            'ubicacion'       => $data['ubicacion'] ?: null,
            'estado'          => $data['estado'] ?? 'Nuevo',
            'precio'          => $data['precio'] ?: null,
            'kilometraje'     => $data['kilometraje'] ?? 0,
            'marca'           => $data['marca'],
            'modelo'          => $data['modelo'],
            'piloto_id'       => $data['piloto_id'] ?: null,
        ]);
    }

    public function updatePhotos(int $id, array $photos): void
    {
        $updates = [];
        $params  = ['id' => $id];

        foreach (['foto_delantera', 'foto_trasera', 'foto_lateral1', 'foto_lateral2'] as $field) {
            if (isset($photos[$field]) && $photos[$field] !== null) {
                $updates[]      = "{$field} = :{$field}";
                $params[$field] = $photos[$field];
            }
        }

        if (empty($updates)) return;

        $sql = "UPDATE heavy_transport SET " . implode(', ', $updates) . " WHERE id = :id";
        $this->pdo->prepare($sql)->execute($params);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM heavy_transport WHERE id = :id")->execute(['id' => $id]);
    }
}
