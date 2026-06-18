<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class InventoryKardexRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAllWithDetails(): array
    {
        $sql = "SELECT 
                    k.*, 
                    i.nombre as item_nombre, i.codigo_sku as item_sku, i.unidad_medida as item_unidad,
                    p1.nombre as proyecto_origen,
                    p2.nombre as proyecto_destino
                FROM inventory_kardex k
                JOIN inventory_items i ON k.item_id = i.id
                LEFT JOIN projects p1 ON k.proyecto_origen_id = p1.id
                LEFT JOIN projects p2 ON k.proyecto_destino_id = p2.id
                ORDER BY k.fecha_movimiento DESC, k.id DESC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO inventory_kardex
                    (tipo_movimiento, item_id, proyecto_origen_id, proyecto_destino_id, cantidad, costo_unitario, referencia_documento, notas, fecha_movimiento)
                VALUES
                    (:tipo_movimiento, :item_id, :proyecto_origen_id, :proyecto_destino_id, :cantidad, :costo_unitario, :referencia_documento, :notas, :fecha_movimiento)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'tipo_movimiento'      => $data['tipo_movimiento'],
            'item_id'              => $data['item_id'],
            'proyecto_origen_id'   => $data['proyecto_origen_id'] ?? null,
            'proyecto_destino_id'  => $data['proyecto_destino_id'] ?? null,
            'cantidad'             => $data['cantidad'],
            'costo_unitario'       => $data['costo_unitario'] ?? 0.00,
            'referencia_documento' => $data['referencia_documento'] ?? null,
            'notas'                => $data['notas'] ?? null,
            'fecha_movimiento'     => $data['fecha_movimiento']
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateFotos(int $id, string $json): void
    {
        $stmt = $this->pdo->prepare("UPDATE inventory_kardex SET fotos = :fotos WHERE id = :id");
        $stmt->execute(['fotos' => $json, 'id' => $id]);
    }
}
