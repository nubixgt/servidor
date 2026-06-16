<?php
namespace App\Repositories;

use PDO;

class ReportRepository {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getDashboardStats() {
        // Total Capital Prestado (desde tabla clientes)
        $stmt1 = $this->db->query("SELECT SUM(capital) as total FROM clientes");
        $capital_prestado = $stmt1->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // Total Pagos Recibidos (desde tabla pagos)
        $stmt2 = $this->db->query("SELECT SUM(monto_pagado) as total FROM pagos");
        $pagos_recibidos = $stmt2->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // Total Invertido (desde tabla inversionistas)
        $stmt3 = $this->db->query("SELECT SUM(capital) as total FROM inversionistas");
        $capital_inversionistas = $stmt3->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // Total Egresos
        $stmt4 = $this->db->query("SELECT SUM(monto) as total FROM egreso_registros");
        $egresos = $stmt4->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // Total Gastos Recurrentes
        $stmt5 = $this->db->query("SELECT SUM(monto) as total FROM gastos_recurrentes");
        $gastos_recurrentes = $stmt5->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        return [
            'capital_prestado' => (float) $capital_prestado,
            'pagos_recibidos' => (float) $pagos_recibidos,
            'capital_inversionistas' => (float) $capital_inversionistas,
            'total_egresos' => (float) $egresos + (float) $gastos_recurrentes,
        ];
    }
    
    public function getCapitalPorInversionista() {
        $stmt = $this->db->query("SELECT nombre, capital FROM inversionistas ORDER BY capital DESC LIMIT 5");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
