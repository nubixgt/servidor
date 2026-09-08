<?php
namespace App\Repositories;

use App\Utils\Database;
use Exception;
use PDO;

class EmployeePayrollRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
        $this->ensureTableExists();
    }

    private function ensureTableExists(): void
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS `employee_payroll_payments` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `personnel_id` int(10) UNSIGNED NOT NULL,
              `periodo` varchar(100) NOT NULL,
              `fecha_pago` date NOT NULL,
              `salario_base` decimal(15,2) NOT NULL DEFAULT 0.00,
              `dias_trabajados` int(11) NOT NULL DEFAULT 30,
              `sueldo_calculado` decimal(15,2) NOT NULL DEFAULT 0.00,
              `tiene_horas_extras` tinyint(1) NOT NULL DEFAULT 0,
              `horas_extras` decimal(10,2) NOT NULL DEFAULT 0.00,
              `tarifa_hora_extra` decimal(10,2) NOT NULL DEFAULT 0.00,
              `monto_horas_extras` decimal(15,2) NOT NULL DEFAULT 0.00,
              `tiene_viaticos` tinyint(1) NOT NULL DEFAULT 0,
              `monto_viaticos` decimal(15,2) NOT NULL DEFAULT 0.00,
              `observaciones_viaticos` text DEFAULT NULL,
              `total_pagar` decimal(15,2) NOT NULL DEFAULT 0.00,
              `metodo_pago` varchar(50) DEFAULT 'Transferencia',
              `observaciones` text DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `fk_pay_personnel` (`personnel_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

            $this->pdo->exec($sql);
        } catch (Exception $e) {
            error_log('Error en auto-migración employee_payroll_payments: ' . $e->getMessage());
        }
    }

    public function findAll(): array
    {
        $sql = "SELECT
                    p.*,
                    CONCAT(per.nombres, ' ', per.apellidos) AS empleado_nombre,
                    per.puesto AS empleado_puesto,
                    per.tipo_empleado,
                    per.foto_path
                FROM employee_payroll_payments p
                JOIN personnel per ON per.id = p.personnel_id
                ORDER BY p.fecha_pago DESC, p.id DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM employee_payroll_payments WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO employee_payroll_payments
                    (personnel_id, periodo, fecha_pago, salario_base, dias_trabajados, sueldo_calculado,
                     tiene_horas_extras, horas_extras, tarifa_hora_extra, monto_horas_extras,
                     tiene_viaticos, monto_viaticos, observaciones_viaticos,
                     total_pagar, metodo_pago, observaciones)
                VALUES
                    (:personnel_id, :periodo, :fecha_pago, :salario_base, :dias_trabajados, :sueldo_calculado,
                     :tiene_horas_extras, :horas_extras, :tarifa_hora_extra, :monto_horas_extras,
                     :tiene_viaticos, :monto_viaticos, :observaciones_viaticos,
                     :total_pagar, :metodo_pago, :observaciones)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'personnel_id'           => $data['personnel_id'],
            'periodo'                => $data['periodo'],
            'fecha_pago'             => $data['fecha_pago'],
            'salario_base'           => $data['salario_base'],
            'dias_trabajados'        => $data['dias_trabajados'],
            'sueldo_calculado'       => $data['sueldo_calculado'],
            'tiene_horas_extras'     => $data['tiene_horas_extras'],
            'horas_extras'           => $data['horas_extras'],
            'tarifa_hora_extra'      => $data['tarifa_hora_extra'],
            'monto_horas_extras'     => $data['monto_horas_extras'],
            'tiene_viaticos'         => $data['tiene_viaticos'],
            'monto_viaticos'         => $data['monto_viaticos'],
            'observaciones_viaticos' => $data['observaciones_viaticos'],
            'total_pagar'            => $data['total_pagar'],
            'metodo_pago'            => $data['metodo_pago'] ?? 'Transferencia',
            'observaciones'          => $data['observaciones'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM employee_payroll_payments WHERE id = :id")->execute(['id' => $id]);
    }
}
