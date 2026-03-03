<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\Sale;
use PDO;

class SaleRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll()
    {
        $stmt = $this->db->query("SELECT * FROM nota_envio ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_CLASS, Sale::class);
    }

    public function findByIdWithDetails($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM nota_envio WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sale) {
            return null;
        }

        $stmtDetalle = $this->db->prepare("SELECT * FROM detalle_nota_envio WHERE nota_envio_id = :id");
        $stmtDetalle->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtDetalle->execute();
        $sale['productos'] = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

        return $sale;
    }

    public function getSalesByCategory()
    {
        $stmt = $this->db->query("
            SELECT producto as category, SUM(cantidad) as items_sold 
            FROM detalle_nota_envio
            GROUP BY producto
            ORDER BY items_sold DESC
            LIMIT 4
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalSalesAmount()
    {
        $stmt = $this->db->query("SELECT SUM(total) as total FROM nota_envio");
        return $stmt->fetchColumn() ?: 0;
    }

    public function getRecentSales($limit = 5)
    {
        $stmt = $this->db->prepare("SELECT * FROM nota_envio ORDER BY fecha DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSalesLast30Days()
    {
        $stmt = $this->db->query("SELECT SUM(total) as total FROM nota_envio WHERE fecha >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)");
        return $stmt->fetchColumn() ?: 0;
    }

    public function getSalesTrend6Months()
    {
        $stmt = $this->db->query("
            SELECT DATE_FORMAT(fecha, '%Y-%m') as mes, SUM(total) as total 
            FROM nota_envio 
            WHERE fecha >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(fecha, '%Y-%m')
            ORDER BY mes ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generateNextNotaNumber()
    {
        $stmt = $this->db->query("SELECT numero_nota FROM nota_envio WHERE numero_nota NOT LIKE 'NE-%' ORDER BY id DESC LIMIT 1");
        $lastNotaNum = $stmt->fetchColumn();

        if (!$lastNotaNum) {
            return '00001';
        }

        $cleaned = preg_replace('/[^0-9]/', '', $lastNotaNum);
        if ($cleaned) {
            $next = intval($cleaned) + 1;
            return str_pad($next, 5, '0', STR_PAD_LEFT);
        }

        return str_pad(rand(1, 9999), 5, '0', STR_PAD_LEFT);
    }

    public function createTransaction($data)
    {
        try {
            $this->db->beginTransaction();

            // 1. Insert into nota_envio
            $stmt = $this->db->prepare("
                INSERT INTO nota_envio
                (numero_nota, fecha, vendedor, cliente_nombre, nit, direccion, tipo_venta, subtotal, descuento_total, total)
                VALUES
                (:numero_nota, :fecha, :vendedor, :cliente_nombre, :nit, :direccion, :tipo_venta, :subtotal, :descuento_total, :total)
            ");
            $stmt->execute([
                ':numero_nota' => $data['numero_nota'],
                ':fecha' => $data['fecha'],
                ':vendedor' => $data['vendedor'],
                ':cliente_nombre' => $data['cliente_nombre'],
                ':nit' => $data['nit'],
                ':direccion' => $data['direccion'] ?? '',
                ':tipo_venta' => $data['tipo_venta'] ?? 'Contado',
                ':subtotal' => $data['subtotal'] ?? 0.0,
                ':descuento_total' => $data['descuento_total'] ?? 0.0,
                ':total' => $data['total'] ?? 0.0,
            ]);

            $notaId = $this->db->lastInsertId();

            // 2. Insert details (productos)
            if (!empty($data['productos'])) {
                $stmtDetalle = $this->db->prepare("
                    INSERT INTO detalle_nota_envio
                    (nota_envio_id, producto_id, producto, presentacion, precio_unitario, cantidad, descuento, total, es_bonificacion)
                    VALUES
                    (:nota_envio_id, :producto_id, :producto, :presentacion, :precio_unitario, :cantidad, :descuento, :total, :es_bonificacion)
                ");

                foreach ($data['productos'] as $prod) {
                    $stmtDetalle->execute([
                        ':nota_envio_id' => $notaId,
                        ':producto_id' => $prod['producto_id'],
                        ':producto' => $prod['producto'],
                        ':presentacion' => $prod['presentacion'],
                        ':precio_unitario' => $prod['precio_unitario'],
                        ':cantidad' => $prod['cantidad'],
                        ':descuento' => $prod['descuento'] ?? 0.0,
                        ':total' => $prod['total'],
                        ':es_bonificacion' => $prod['es_bonificacion'] ?? 'no'
                    ]);
                }
            }

            $this->db->commit();
            return $notaId;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            throw new \Exception("Error guardando la venta: " . $e->getMessage());
        }
    }

    public function deleteTransaction($id)
    {
        try {
            $this->db->beginTransaction();

            $stmtDetalle = $this->db->prepare("DELETE FROM detalle_nota_envio WHERE nota_envio_id = :id");
            $stmtDetalle->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtDetalle->execute();

            $stmt = $this->db->prepare("DELETE FROM nota_envio WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            throw new \Exception("Error al eliminar la venta: " . $e->getMessage());
        }
    }
}
