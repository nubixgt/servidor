<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class PayrollRepository
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

    public function findActivePersonnel(): array
    {
        $stmt = $this->pdo->query("SELECT id, nombres, apellidos, puesto, salario_base, tipo_planilla FROM personnel WHERE fecha_baja IS NULL OR fecha_baja = '0000-00-00' OR fecha_baja >= CURDATE()");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAllPayrolls(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM payrolls ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createPayroll(string $periodo, string $fechaCorte): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO payrolls (periodo, fecha_corte, estado) VALUES (:periodo, :fecha_corte, 'Borrador')");
        $stmt->execute(['periodo' => $periodo, 'fecha_corte' => $fechaCorte]);
        return (int) $this->pdo->lastInsertId();
    }

    public function createPayrollDetailsForEmployees(int $payrollId, array $empleadosIds): void
    {
        $stmtDet = $this->pdo->prepare("
            INSERT INTO payroll_details (payroll_id, personnel_id, salario_base_aplicado, horas_trabajadas, total_neto)
            SELECT :payroll_id, id, salario_base, 160, salario_base
            FROM personnel WHERE id = :emp_id
        ");

        foreach ($empleadosIds as $emp_id) {
            $stmtDet->execute(['payroll_id' => $payrollId, 'emp_id' => $emp_id]);
        }
    }

    public function findPayrollDetails(int $payrollId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT pd.*, p.nombres, p.apellidos, p.puesto, p.tipo_planilla
            FROM payroll_details pd
            JOIN personnel p ON pd.personnel_id = p.id
            WHERE pd.payroll_id = :payroll_id
        ");
        $stmt->execute(['payroll_id' => $payrollId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDetailBaseSalary(int $detailId): ?float
    {
        $stmtSel = $this->pdo->prepare("SELECT salario_base_aplicado FROM payroll_details WHERE id = :id");
        $stmtSel->execute(['id' => $detailId]);
        $det = $stmtSel->fetch(PDO::FETCH_ASSOC);
        
        return $det ? (float)$det['salario_base_aplicado'] : null;
    }

    public function updatePayrollDetail(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE payroll_details 
            SET horas_trabajadas = :horas_trabajadas, 
                horas_extras = :horas_extras, 
                monto_horas_extras = :monto_horas_extras, 
                bonificaciones = :bonificaciones, 
                deducciones = :deducciones, 
                total_neto = :total_neto, 
                observaciones = :observaciones
            WHERE id = :id
        ");
        $stmt->execute([
            'horas_trabajadas'   => $data['horas_trabajadas'],
            'horas_extras'       => $data['horas_extras'],
            'monto_horas_extras' => $data['monto_horas_extras'],
            'bonificaciones'     => $data['bonificaciones'],
            'deducciones'        => $data['deducciones'],
            'total_neto'         => $data['total_neto'],
            'observaciones'      => $data['observaciones'],
            'id'                 => $id
        ]);
    }

    public function getPayrollTotalNeto(int $payrollId): float
    {
        $stmtSum = $this->pdo->prepare("SELECT SUM(total_neto) as total FROM payroll_details WHERE payroll_id = :id");
        $stmtSum->execute(['id' => $payrollId]);
        $res = $stmtSum->fetch(PDO::FETCH_ASSOC);
        
        return $res ? (float)$res['total'] : 0.0;
    }

    public function updatePayrollStatusToPaid(int $payrollId, float $totalPagado): void
    {
        $stmt = $this->pdo->prepare("UPDATE payrolls SET estado = 'Pagado', total_pagado = :total WHERE id = :id");
        $stmt->execute(['total' => $totalPagado, 'id' => $payrollId]);
    }
}
