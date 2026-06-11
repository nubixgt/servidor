<?php
namespace App\Repositories;

use App\Utils\Database;
use App\Entities\PagoEntity;

class PagoRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll()
    {
        $stmt = $this->db->query("SELECT p.*, c.cliente as cliente_nombre FROM pagos p LEFT JOIN clientes c ON p.cliente_id = c.id ORDER BY p.id DESC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById(int $id)
    {
        $stmt = $this->db->prepare("SELECT p.*, c.cliente as cliente_nombre FROM pagos p LEFT JOIN clientes c ON p.cliente_id = c.id WHERE p.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) return null;

        return (object) $row;
    }

    public function create(PagoEntity $pago)
    {
        $stmt = $this->db->prepare("
            INSERT INTO pagos (fecha, cliente_id, banco, referencia, monto_pagado, foto, interes, comprobante_interes, fecha_interes, capital, comprobante_capital, fecha_capital) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $pago->fecha,
            $pago->cliente_id,
            $pago->banco,
            $pago->referencia,
            $pago->monto_pagado,
            $pago->foto,
            $pago->interes,
            $pago->comprobante_interes,
            $pago->fecha_interes,
            $pago->capital,
            $pago->comprobante_capital,
            $pago->fecha_capital
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, PagoEntity $pago)
    {
        $stmt = $this->db->prepare("
            UPDATE pagos SET 
                fecha = ?, cliente_id = ?, banco = ?, referencia = ?, monto_pagado = ?, 
                foto = ?, interes = ?, comprobante_interes = ?, fecha_interes = ?, 
                capital = ?, comprobante_capital = ?, fecha_capital = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $pago->fecha,
            $pago->cliente_id,
            $pago->banco,
            $pago->referencia,
            $pago->monto_pagado,
            $pago->foto,
            $pago->interes,
            $pago->comprobante_interes,
            $pago->fecha_interes,
            $pago->capital,
            $pago->comprobante_capital,
            $pago->fecha_capital,
            $id
        ]);
    }

    public function delete(int $id)
    {
        $stmt = $this->db->prepare("DELETE FROM pagos WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
