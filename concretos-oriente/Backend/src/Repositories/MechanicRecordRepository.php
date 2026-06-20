<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class MechanicRecordRepository
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
                    mr.*,
                    s.razon_social AS proveedor_nombre,
                    COALESCE(SUM(i.monto), 0)  AS total_monto,
                    COUNT(i.id)                 AS items_count
                FROM mechanic_records mr
                LEFT JOIN suppliers s            ON s.id  = mr.proveedor_id
                LEFT JOIN mechanic_record_items i ON i.mechanic_record_id = mr.id
                GROUP BY mr.id
                ORDER BY mr.fecha DESC, mr.id DESC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM mechanic_records WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findItemsByRecordId(int $id): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT producto, monto FROM mechanic_record_items WHERE mechanic_record_id = :id ORDER BY id ASC"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllPlates(): array
    {
        $sql = "
            SELECT placa, 'Vehiculo' AS tipo_unidad,
                   CONCAT(marca, ' ', modelo) AS descripcion
            FROM vehicles WHERE placa IS NOT NULL AND placa != ''

            UNION

            SELECT placa, 'Transporte Pesado' AS tipo_unidad,
                   CONCAT(marca, ' ', modelo) AS descripcion
            FROM heavy_transport WHERE placa IS NOT NULL AND placa != ''

            UNION

            SELECT placa, 'Maquinaria' AS tipo_unidad,
                   CONCAT(marca, ' ', modelo) AS descripcion
            FROM machinery WHERE placa IS NOT NULL AND placa != ''

            ORDER BY placa ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO mechanic_records (fecha, placa, tipo_unidad, proveedor_id)
                VALUES (:fecha, :placa, :tipo_unidad, :proveedor_id)";
        $this->pdo->prepare($sql)->execute([
            'fecha'        => $data['fecha'],
            'placa'        => $data['placa'],
            'tipo_unidad'  => $data['tipo_unidad'],
            'proveedor_id' => $data['proveedor_id'] ?: null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function createItem(int $recordId, string $producto, float $monto): void
    {
        $this->pdo->prepare(
            "INSERT INTO mechanic_record_items (mechanic_record_id, producto, monto) VALUES (:rid, :producto, :monto)"
        )->execute(['rid' => $recordId, 'producto' => $producto, 'monto' => $monto]);
    }

    public function deleteItems(int $recordId): void
    {
        $this->pdo->prepare("DELETE FROM mechanic_record_items WHERE mechanic_record_id = :id")
                  ->execute(['id' => $recordId]);
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE mechanic_records SET
                    fecha        = :fecha,
                    placa        = :placa,
                    tipo_unidad  = :tipo_unidad,
                    proveedor_id = :proveedor_id
                WHERE id = :id";
        $this->pdo->prepare($sql)->execute([
            'id'           => $id,
            'fecha'        => $data['fecha'],
            'placa'        => $data['placa'],
            'tipo_unidad'  => $data['tipo_unidad'],
            'proveedor_id' => $data['proveedor_id'] ?: null,
        ]);
    }

    public function updatePhotos(int $id, array $photos): void
    {
        $updates = [];
        $params  = ['id' => $id];
        foreach (['foto_1','foto_2','foto_3','foto_4','foto_5'] as $field) {
            if (isset($photos[$field])) {
                $updates[]      = "{$field} = :{$field}";
                $params[$field] = $photos[$field];
            }
        }
        if (empty($updates)) return;
        $this->pdo->prepare(
            "UPDATE mechanic_records SET " . implode(', ', $updates) . " WHERE id = :id"
        )->execute($params);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM mechanic_records WHERE id = :id")->execute(['id' => $id]);
    }
}
