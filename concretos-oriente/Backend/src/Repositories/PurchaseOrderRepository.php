<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class PurchaseOrderRepository
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
        $sql = "SELECT po.*, s.razon_social, p.nombre as proyecto_nombre
                FROM purchase_orders po
                JOIN suppliers s ON po.proveedor_id = s.id
                LEFT JOIN projects p ON po.proyecto_id = p.id
                ORDER BY po.fecha_orden DESC, po.id DESC";
        $stmt = $this->pdo->query($sql);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlItems = "SELECT poi.*, i.nombre as item_nombre, i.codigo_sku, i.unidad_medida 
                     FROM purchase_order_items poi
                     JOIN inventory_items i ON poi.item_id = i.id
                     WHERE poi.purchase_order_id = :po_id";
        $stmtItems = $this->pdo->prepare($sqlItems);

        foreach ($orders as &$order) {
            $stmtItems->execute(['po_id' => $order['id']]);
            $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        }

        return $orders;
    }

    public function createOrder(array $data): int
    {
        $sql = "INSERT INTO purchase_orders 
                    (proveedor_id, proyecto_id, fecha_orden, condicion_pago, observaciones, archivo_adjunto, total, estado)
                VALUES 
                    (:proveedor_id, :proyecto_id, :fecha_orden, :condicion_pago, :observaciones, NULL, :total, 'Pendiente')";
        
        $this->pdo->prepare($sql)->execute([
            'proveedor_id'    => $data['proveedor_id'],
            'proyecto_id'     => $data['proyecto_id'],
            'fecha_orden'     => $data['fecha_orden'],
            'condicion_pago'  => $data['condicion_pago'],
            'observaciones'   => $data['observaciones'] ?? null,
            'total'           => $data['total']
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateAttachment(int $id, string $filePath): void
    {
        $this->pdo->prepare("UPDATE purchase_orders SET archivo_adjunto = :adj WHERE id = :id")
             ->execute(['adj' => $filePath, 'id' => $id]);
    }

    public function createOrderItem(int $orderId, array $item): void
    {
        $sql = "INSERT INTO purchase_order_items (purchase_order_id, item_id, cantidad, precio_unitario) 
                VALUES (:po_id, :item_id, :cantidad, :precio_unitario)";
        $this->pdo->prepare($sql)->execute([
            'po_id'           => $orderId,
            'item_id'         => $item['item_id'],
            'cantidad'        => $item['cantidad'],
            'precio_unitario' => $item['precio_unitario']
        ]);
    }
}
