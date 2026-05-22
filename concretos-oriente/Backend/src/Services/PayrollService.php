<?php
namespace App\Services;

use App\Repositories\PayrollRepository;
use Exception;

class PayrollService
{
    private PayrollRepository $payrollRepository;

    public function __construct()
    {
        $this->payrollRepository = new PayrollRepository();
    }

    public function getActivePersonnel(): array
    {
        return $this->payrollRepository->findActivePersonnel();
    }

    public function getAllPayrolls(): array
    {
        return $this->payrollRepository->findAllPayrolls();
    }

    public function createPayroll(array $data): void
    {
        if (empty($data['periodo']) || empty($data['fecha_corte']) || empty($data['empleados_ids'])) {
            throw new Exception('Faltan datos obligatorios', 400);
        }

        $pdo = $this->payrollRepository->getPDO();
        $pdo->beginTransaction();

        try {
            $payroll_id = $this->payrollRepository->createPayroll($data['periodo'], $data['fecha_corte']);
            $this->payrollRepository->createPayrollDetailsForEmployees($payroll_id, $data['empleados_ids']);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function getPayrollDetails(?int $payrollId): array
    {
        if (!$payrollId) {
            throw new Exception('Falta el ID de la planilla', 400);
        }

        return $this->payrollRepository->findPayrollDetails($payrollId);
    }

    public function updatePayrollDetail(array $data): void
    {
        $id = isset($data['id']) ? (int)$data['id'] : null;
        if (!$id) {
            throw new Exception('Falta ID del detalle', 400);
        }

        $horas_trabajadas = isset($data['horas_trabajadas']) ? (int)$data['horas_trabajadas'] : 0;
        $horas_extras     = isset($data['horas_extras']) ? (int)$data['horas_extras'] : 0;
        $bonificaciones   = isset($data['bonificaciones']) ? (float)$data['bonificaciones'] : 0.0;
        $deducciones      = isset($data['deducciones']) ? (float)$data['deducciones'] : 0.0;
        $observaciones    = $data['observaciones'] ?? null;

        $base = $this->payrollRepository->getDetailBaseSalary($id);
        if ($base === null) {
            throw new Exception('Detalle no encontrado', 404);
        }

        $monto_he = ($base / 160) * 1.5 * $horas_extras;
        $salario_calculado = ($base / 160) * $horas_trabajadas;

        $total_neto = $salario_calculado + $monto_he + $bonificaciones - $deducciones;

        $updateData = [
            'horas_trabajadas'   => $horas_trabajadas,
            'horas_extras'       => $horas_extras,
            'monto_horas_extras' => $monto_he,
            'bonificaciones'     => $bonificaciones,
            'deducciones'        => $deducciones,
            'total_neto'         => $total_neto,
            'observaciones'      => $observaciones
        ];

        $this->payrollRepository->updatePayrollDetail($id, $updateData);
    }

    public function payPayroll(?int $payrollId): void
    {
        if (!$payrollId) {
            throw new Exception('Falta ID de la planilla', 400);
        }

        $total = $this->payrollRepository->getPayrollTotalNeto($payrollId);

        $this->payrollRepository->updatePayrollStatusToPaid($payrollId, $total);
    }
}
