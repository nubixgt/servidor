<?php
namespace App\Controllers;

use App\Utils\Database;
use App\Attributes\Route;
use PDO;
use Exception;

class PayrollController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    private function respond($status, $message, $data = null) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    #[Route('/payrolls/active-personnel', 'GET')]
    public function getActivePersonnel() {
        try {
            $stmt = $this->db->query("SELECT id, nombres, apellidos, puesto, salario_base, tipo_planilla FROM personnel WHERE fecha_baja IS NULL");
            $personnel = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->respond('success', 'Personal activo', $personnel);
        } catch (\Exception $e) {
            $this->respond('error', 'Error al obtener personal: ' . $e->getMessage());
        }
    }

    #[Route('/payrolls', 'GET')]
    public function getPayrolls() {
        try {
            $stmt = $this->db->query("SELECT * FROM payrolls ORDER BY created_at DESC");
            $payrolls = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->respond('success', 'Planillas', $payrolls);
        } catch (\Exception $e) {
            $this->respond('error', 'Error al obtener planillas: ' . $e->getMessage());
        }
    }

    #[Route('/payrolls', 'POST')]
    public function createPayroll() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $periodo = $data['periodo'] ?? '';
            $fecha_corte = $data['fecha_corte'] ?? '';
            $empleados_ids = $data['empleados_ids'] ?? [];

            if (empty($periodo) || empty($fecha_corte) || empty($empleados_ids)) {
                return $this->respond('error', 'Faltan datos obligatorios');
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO payrolls (periodo, fecha_corte, estado) VALUES (?, ?, 'Borrador')");
            $stmt->execute([$periodo, $fecha_corte]);
            $payroll_id = $this->db->lastInsertId();

            // Insert details for each selected employee
            $stmtDet = $this->db->prepare("
                INSERT INTO payroll_details (payroll_id, personnel_id, salario_base_aplicado, horas_trabajadas, total_neto)
                SELECT ?, id, salario_base, 160, salario_base
                FROM personnel WHERE id = ?
            ");

            foreach ($empleados_ids as $emp_id) {
                $stmtDet->execute([$payroll_id, $emp_id]);
            }

            $this->db->commit();
            $this->respond('success', 'Planilla generada');
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->respond('error', 'Error: ' . $e->getMessage());
        }
    }

    #[Route('/payrolls/details', 'GET')]
    public function getPayrollDetails() {
        try {
            $payroll_id = $_GET['payroll_id'] ?? null;
            if (!$payroll_id) {
                return $this->respond('error', 'Falta el ID de la planilla');
            }

            $stmt = $this->db->prepare("
                SELECT pd.*, p.nombres, p.apellidos, p.puesto, p.tipo_planilla
                FROM payroll_details pd
                JOIN personnel p ON pd.personnel_id = p.id
                WHERE pd.payroll_id = ?
            ");
            $stmt->execute([$payroll_id]);
            $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->respond('success', 'Detalles', $details);
        } catch (\Exception $e) {
            $this->respond('error', 'Error: ' . $e->getMessage());
        }
    }

    #[Route('/payroll-details', 'POST')]
    public function updatePayrollDetail() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? null;
            $horas_trabajadas = $data['horas_trabajadas'] ?? 0;
            $horas_extras = $data['horas_extras'] ?? 0;
            $bonificaciones = $data['bonificaciones'] ?? 0;
            $deducciones = $data['deducciones'] ?? 0;
            $observaciones = $data['observaciones'] ?? '';

            if (!$id) return $this->respond('error', 'Falta ID del detalle');

            // Calcular nuevo total neto
            // Obtenemos salario base
            $stmtSel = $this->db->prepare("SELECT salario_base_aplicado FROM payroll_details WHERE id = ?");
            $stmtSel->execute([$id]);
            $det = $stmtSel->fetch(PDO::FETCH_ASSOC);
            $base = (float)$det['salario_base_aplicado'];
            
            // Asumiendo monto_horas_extras = (base / 160) * 1.5 * horas_extras
            $monto_he = ($base / 160) * 1.5 * (int)$horas_extras;
            
            // Proporcionar base si horas trabajadas != 160 (opcional, simplificamos asumiendo base fija o pago por hora)
            $salario_calculado = ($base / 160) * (int)$horas_trabajadas;

            $total_neto = $salario_calculado + $monto_he + (float)$bonificaciones - (float)$deducciones;

            $stmt = $this->db->prepare("
                UPDATE payroll_details 
                SET horas_trabajadas = ?, horas_extras = ?, monto_horas_extras = ?, bonificaciones = ?, deducciones = ?, total_neto = ?, observaciones = ?
                WHERE id = ?
            ");
            $stmt->execute([$horas_trabajadas, $horas_extras, $monto_he, $bonificaciones, $deducciones, $total_neto, $observaciones, $id]);

            $this->respond('success', 'Detalle actualizado');
        } catch (\Exception $e) {
            $this->respond('error', 'Error: ' . $e->getMessage());
        }
    }

    #[Route('/payrolls/pay', 'POST')]
    public function payPayroll() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $payroll_id = $data['payroll_id'] ?? null;
            
            if (!$payroll_id) return $this->respond('error', 'Falta ID de la planilla');

            // Calcular total a pagar
            $stmtSum = $this->db->prepare("SELECT SUM(total_neto) as total FROM payroll_details WHERE payroll_id = ?");
            $stmtSum->execute([$payroll_id]);
            $total = $stmtSum->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt = $this->db->prepare("UPDATE payrolls SET estado = 'Pagado', total_pagado = ? WHERE id = ?");
            $stmt->execute([$total, $payroll_id]);

            $this->respond('success', 'Pagos emitidos');
        } catch (\Exception $e) {
            $this->respond('error', 'Error: ' . $e->getMessage());
        }
    }
}
