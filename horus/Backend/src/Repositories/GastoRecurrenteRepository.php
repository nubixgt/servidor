<?php

namespace App\Repositories;

use App\Utils\Database;
use App\Entities\GastoRecurrenteEntity;
use PDO;

class GastoRecurrenteRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM gastos_recurrentes ORDER BY estado ASC, dia_pago ASC");
        $gastos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $gastos[] = new GastoRecurrenteEntity(
                $row['id'],
                $row['concepto'],
                $row['descripcion'],
                $row['monto'],
                $row['dia_pago'],
                $row['estado'],
                $row['created_at']
            );
        }
        return $gastos;
    }

    public function getById(int $id): ?GastoRecurrenteEntity {
        $stmt = $this->db->prepare("SELECT * FROM gastos_recurrentes WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) return null;
        
        return new GastoRecurrenteEntity(
            $row['id'],
            $row['concepto'],
            $row['descripcion'],
            $row['monto'],
            $row['dia_pago'],
            $row['estado'],
            $row['created_at']
        );
    }

    public function save(GastoRecurrenteEntity $gasto): int {
        $stmt = $this->db->prepare("
            INSERT INTO gastos_recurrentes (concepto, descripcion, monto, dia_pago, estado) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $gasto->getConcepto(),
            $gasto->getDescripcion(),
            $gasto->getMonto(),
            $gasto->getDiaPago(),
            $gasto->getEstado()
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(GastoRecurrenteEntity $gasto): bool {
        $stmt = $this->db->prepare("
            UPDATE gastos_recurrentes 
            SET concepto = ?, descripcion = ?, monto = ?, dia_pago = ?, estado = ? 
            WHERE id = ?
        ");
        return $stmt->execute([
            $gasto->getConcepto(),
            $gasto->getDescripcion(),
            $gasto->getMonto(),
            $gasto->getDiaPago(),
            $gasto->getEstado(),
            $gasto->getId()
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM gastos_recurrentes WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
