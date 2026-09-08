<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class ContractorRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
        $this->autoMigrate();
    }

    private function autoMigrate(): void
    {
        try {
            $cols = $this->pdo->query("SHOW COLUMNS FROM contractors")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('empresa', $cols)) {
                $this->pdo->exec("ALTER TABLE contractors ADD COLUMN empresa VARCHAR(255) NULL AFTER id");
                $this->pdo->exec("UPDATE contractors SET empresa = nombre WHERE empresa IS NULL OR empresa = ''");
            }
            if (!in_array('representante', $cols)) {
                $this->pdo->exec("ALTER TABLE contractors ADD COLUMN representante VARCHAR(255) NULL AFTER empresa");
            }
            if (!in_array('encargado_id', $cols)) {
                $this->pdo->exec("ALTER TABLE contractors ADD COLUMN encargado_id INT NULL");
            }
            if (!in_array('encargado_nombre', $cols)) {
                $this->pdo->exec("ALTER TABLE contractors ADD COLUMN encargado_nombre TEXT NULL");
            } else {
                $this->pdo->exec("ALTER TABLE contractors MODIFY COLUMN encargado_nombre TEXT NULL");
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function findAll(): array
    {
        $sql = "SELECT c.*,
                       COALESCE(c.empresa, c.nombre) AS empresa,
                       c.representante,
                       COALESCE(NULLIF(c.encargado_nombre, ''), CONCAT(p.nombres, ' ', p.apellidos)) AS encargado_asignado,
                       p.puesto AS encargado_puesto,
                       (
                           SELECT COUNT(DISTINCT pc.project_id) FROM project_contractors pc WHERE pc.contractor_id = c.id
                       ) AS proyectos_count,
                       (
                           SELECT COALESCE(SUM(pc.monto_contratado), 0) FROM project_contractors pc WHERE pc.contractor_id = c.id
                       ) AS total_contratado,
                       (
                           SELECT COALESCE(SUM(e.monto), 0) FROM expenses e WHERE e.contratista_id = c.id AND (e.tipo_egreso = 'Contratista' OR e.tipo_egreso = 'Subcontratista' OR e.contratista_id IS NOT NULL)
                       ) AS total_pagado
                FROM contractors c
                LEFT JOIN personnel p ON p.id = c.encargado_id
                ORDER BY COALESCE(c.empresa, c.nombre) ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*,
                   COALESCE(c.empresa, c.nombre) AS empresa,
                   COALESCE(NULLIF(c.encargado_nombre, ''), CONCAT(p.nombres, ' ', p.apellidos)) AS encargado_asignado,
                   p.puesto AS encargado_puesto
            FROM contractors c
            LEFT JOIN personnel p ON p.id = c.encargado_id
            WHERE c.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function create(array $data): int
    {
        $empresa = !empty($data['empresa']) ? $data['empresa'] : ($data['nombre'] ?? '');
        $sql = "INSERT INTO contractors (nombre, empresa, representante, telefono, correo_electronico, encargado_id, encargado_nombre)
                VALUES (:nombre, :empresa, :representante, :telefono, NULL, :encargado_id, :encargado_nombre)";

        $this->pdo->prepare($sql)->execute([
            'nombre'           => $empresa,
            'empresa'          => $empresa,
            'representante'    => $data['representante'] ?? null,
            'telefono'         => $data['telefono'] ?? null,
            'encargado_id'     => !empty($data['encargado_id']) ? (int)$data['encargado_id'] : null,
            'encargado_nombre' => $data['encargado_nombre'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $empresa = !empty($data['empresa']) ? $data['empresa'] : ($data['nombre'] ?? '');
        $sql = "UPDATE contractors SET
                    nombre           = :nombre,
                    empresa          = :empresa,
                    representante    = :representante,
                    telefono         = :telefono,
                    encargado_id     = :encargado_id,
                    encargado_nombre = :encargado_nombre
                WHERE id = :id";

        $this->pdo->prepare($sql)->execute([
            'nombre'           => $empresa,
            'empresa'          => $empresa,
            'representante'    => $data['representante'] ?? null,
            'telefono'         => $data['telefono'] ?? null,
            'encargado_id'     => !empty($data['encargado_id']) ? (int)$data['encargado_id'] : null,
            'encargado_nombre' => $data['encargado_nombre'] ?? null,
            'id'               => $id
        ]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM contractors WHERE id = :id")->execute(['id' => $id]);
    }

    public function getProjectAssignments(int $contractorId): array
    {
        $sql = "SELECT pc.*, p.nombre as proyecto_nombre
                FROM project_contractors pc
                JOIN projects p ON pc.project_id = p.id
                WHERE pc.contractor_id = :contractor_id
                ORDER BY pc.fecha_asignacion ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['contractor_id' => $contractorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPaymentsByContractor(int $contractorId): array
    {
        $sql = "SELECT e.id, e.proyecto_id, p.nombre as proyecto_nombre, e.fecha_egreso,
                       e.numero_cheque, e.cuenta_origen, e.monto, e.descripcion
                FROM expenses e
                LEFT JOIN projects p ON e.proyecto_id = p.id
                WHERE e.contratista_id = :contractor_id AND e.tipo_egreso = 'Contratista'
                ORDER BY e.fecha_egreso ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['contractor_id' => $contractorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function assignProject(int $contractorId, int $projectId, float $montoContratado, string $fechaAsignacion, ?string $observaciones): void
    {
        $sql = "INSERT INTO project_contractors (project_id, contractor_id, monto_contratado, fecha_asignacion, observaciones)
                VALUES (:project_id, :contractor_id, :monto_contratado, :fecha_asignacion, :observaciones)
                ON DUPLICATE KEY UPDATE
                    monto_contratado = VALUES(monto_contratado),
                    fecha_asignacion = VALUES(fecha_asignacion),
                    observaciones = VALUES(observaciones),
                    updated_at = CURRENT_TIMESTAMP";

        $this->pdo->prepare($sql)->execute([
            'project_id'       => $projectId,
            'contractor_id'    => $contractorId,
            'monto_contratado' => $montoContratado,
            'fecha_asignacion' => $fechaAsignacion,
            'observaciones'    => $observaciones
        ]);
    }

    public function removeAssignment(int $contractorId, int $projectId): void
    {
        $sql = "DELETE FROM project_contractors WHERE contractor_id = :contractor_id AND project_id = :project_id";
        $this->pdo->prepare($sql)->execute([
            'contractor_id' => $contractorId,
            'project_id'    => $projectId
        ]);
    }

    public function getMonthlyHistory(int $contractorId): array
    {
        // 1. Fetch expenses (payments made to this contractor)
        $sql = "SELECT e.id, e.fecha_egreso as fecha, e.monto, e.descripcion, e.numero_cheque, e.cuenta_origen, e.proyecto_id,
                       p.nombre as proyecto_nombre, 'Pago / Egreso' as tipo, e.id as referencia_id
                FROM expenses e
                LEFT JOIN projects p ON e.proyecto_id = p.id
                WHERE (e.contratista_id = :contractor_id OR e.beneficiario_id = :contractor_id2)
                ORDER BY e.fecha_egreso DESC, e.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'contractor_id'  => $contractorId,
            'contractor_id2' => $contractorId
        ]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group by YYYY-MM
        $monthlyMap = [];
        $totalGeneral = 0.0;
        $currentMonth = date('Y-m');
        $currentYear = date('Y');
        $totalEsteMes = 0.0;
        $totalEsteAno = 0.0;

        $mesesNombres = [
            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
            '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
            '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
        ];

        foreach ($transactions as $t) {
            $monto = (float)($t['monto'] ?? 0);
            $totalGeneral += $monto;

            $fecha = $t['fecha'] ?? '';
            $ym = substr($fecha, 0, 7);
            $y = substr($fecha, 0, 4);
            $m = substr($fecha, 5, 2);

            if ($ym === $currentMonth) {
                $totalEsteMes += $monto;
            }
            if ($y === $currentYear) {
                $totalEsteAno += $monto;
            }

            if (!empty($ym)) {
                if (!isset($monthlyMap[$ym])) {
                    $monthLabel = ($mesesNombres[$m] ?? $m) . ' ' . $y;
                    $monthlyMap[$ym] = [
                        'mes'            => $ym,
                        'mes_nombre'     => $monthLabel,
                        'ano'            => (int)$y,
                        'total'          => 0.0,
                        'count'          => 0,
                        'transacciones'  => []
                    ];
                }
                $monthlyMap[$ym]['total'] += $monto;
                $monthlyMap[$ym]['count']++;
                $monthlyMap[$ym]['transacciones'][] = $t;
            }
        }

        // Convert monthly map to list sorted by mes DESC
        krsort($monthlyMap);
        $meses = array_values($monthlyMap);

        return [
            'total_general'       => $totalGeneral,
            'total_este_mes'      => $totalEsteMes,
            'total_este_ano'      => $totalEsteAno,
            'transacciones_count' => count($transactions),
            'meses'               => $meses,
            'transacciones'       => $transactions
        ];
    }
}
