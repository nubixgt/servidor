<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class InventoryItemRepository
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

    public function findAll(): array
    {
        $sql = "SELECT * FROM inventory_items ORDER BY nombre ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findBySku(string $sku, ?int $excludeId = null): ?array
    {
        $sql = "SELECT id FROM inventory_items WHERE codigo_sku = :sku";
        $params = ['sku' => $sku];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO inventory_items
                    (tipo_item, codigo_sku, nombre, descripcion, unidad_medida, stock_minimo, stock_actual)
                VALUES
                    (:tipo_item, :codigo_sku, :nombre, :descripcion, :unidad_medida, :stock_minimo, 0.00)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'tipo_item'      => $data['tipo_item'],
            'codigo_sku'     => $data['codigo_sku'] ?? null,
            'nombre'         => $data['nombre'],
            'descripcion'    => $data['descripcion'] ?? null,
            'unidad_medida'  => $data['unidad_medida'],
            'stock_minimo'   => $data['stock_minimo'] ?? 0.00,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE inventory_items SET
                    tipo_item      = :tipo_item,
                    codigo_sku     = :codigo_sku,
                    nombre         = :nombre,
                    descripcion    = :descripcion,
                    unidad_medida  = :unidad_medida,
                    stock_minimo   = :stock_minimo
                WHERE id = :id";

        $filteredData = [
            'id'             => $id,
            'tipo_item'      => $data['tipo_item'],
            'codigo_sku'     => $data['codigo_sku'] ?? null,
            'nombre'         => $data['nombre'],
            'descripcion'    => $data['descripcion'] ?? null,
            'unidad_medida'  => $data['unidad_medida'],
            'stock_minimo'   => $data['stock_minimo'] ?? 0.00,
        ];
        $this->pdo->prepare($sql)->execute($filteredData);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM inventory_items WHERE id = :id")->execute(['id' => $id]);
    }

    public function updateStock(int $id, float $cantidad, string $operacion): void
    {
        // $operacion debe ser '+' o '-'
        $sql = "UPDATE inventory_items SET stock_actual = stock_actual {$operacion} :qty WHERE id = :id";
        $this->pdo->prepare($sql)->execute(['qty' => $cantidad, 'id' => $id]);
    }
}
