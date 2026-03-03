<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\Payment;
use PDO;

class PaymentRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll()
    {
        $stmt = $this->db->query("
            SELECT p.*, n.cliente_nombre as cliente_nombre 
            FROM pagos p
            LEFT JOIN nota_envio n ON p.factura_id = n.id
            ORDER BY p.fecha_pago DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_CLASS, Payment::class);
    }

    public function getTotalCollected()
    {
        $stmt = $this->db->query("SELECT SUM(monto_pago) as total FROM pagos");
        return $stmt->fetchColumn() ?: 0;
    }

    public function getTotalDebt()
    {
        $stmt = $this->db->query("
            SELECT (SELECT COALESCE(SUM(total), 0) FROM nota_envio) - 
                   (SELECT COALESCE(SUM(monto_pago), 0) FROM pagos) AS target_debt
        ");
        return $stmt->fetchColumn() ?: 0;
    }

    public function getPendingInvoices()
    {
        $query = "SELECT 
                    ne.id,
                    ne.numero_nota,
                    ne.fecha,
                    ne.cliente_id,
                    ne.cliente_nombre,
                    ne.nit,
                    ne.total,
                    ne.dias_credito,
                    COALESCE(SUM(p.monto_pago), 0) as total_pagado,
                    (ne.total - COALESCE(SUM(p.monto_pago), 0)) as saldo_pendiente
                  FROM nota_envio ne
                  LEFT JOIN pagos p ON ne.id = p.factura_id
                  WHERE ne.tipo_venta = 'Crédito'
                  GROUP BY ne.id, ne.numero_nota, ne.fecha, ne.cliente_id, 
                           ne.cliente_nombre, ne.nit, ne.total, ne.dias_credito
                  HAVING saldo_pendiente > 0
                  ORDER BY ne.fecha DESC";

        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO pagos (factura_id, fecha_pago, banco, monto_pago, referencia_transaccion, usuario_id, fecha_creacion)
            VALUES (:factura_id, :fecha_pago, :banco, :monto_pago, :referencia, :usuario_id, NOW())
        ");

        $stmt->execute([
            ':factura_id' => $data['factura_id'],
            ':fecha_pago' => $data['fecha_pago'],
            ':banco' => $data['banco'],
            ':monto_pago' => $data['monto_pago'],
            ':referencia' => $data['referencia_transaccion'] ?? '',
            ':usuario_id' => $data['usuario_id']
        ]);

        return $this->db->lastInsertId();
    }

    public function getCollectionsLast30Days()
    {
        $stmt = $this->db->query("SELECT SUM(monto_pago) as total FROM pagos WHERE fecha_pago >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)");
        return $stmt->fetchColumn() ?: 0;
    }

    public function getPaymentsTrend6Months()
    {
        $stmt = $this->db->query("
            SELECT DATE_FORMAT(fecha_pago, '%Y-%m') as mes, SUM(monto_pago) as total 
            FROM pagos 
            WHERE fecha_pago >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(fecha_pago, '%Y-%m')
            ORDER BY mes ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentPayments($limit = 5)
    {
        $stmt = $this->db->prepare("
            SELECT p.*, n.cliente_nombre as cliente_nombre 
            FROM pagos p
            LEFT JOIN nota_envio n ON p.factura_id = n.id
            ORDER BY p.fecha_pago DESC LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
