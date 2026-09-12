<?php
namespace App\Services;

use App\Utils\Database;
use PDO;
use DateTime;

class DashboardService
{
    private PDO $pdo;

    private const MESES = [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
        7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
    ];

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function getSummary(): array
    {
        return [
            'kpis'              => $this->getKpis(),
            'financial_chart'   => $this->getFinancialChart(),
            'projects_status'   => $this->getProjectsStatus(),
            'featured_projects' => $this->getFeaturedProjects(),
            'inventory_alerts'  => $this->getInventoryAlerts(),
            'recent_activity'   => $this->getRecentActivity(),
        ];
    }

    private function sumForRange(string $table, string $dateCol, string $start, string $endExclusive): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(monto), 0) FROM `$table` WHERE `$dateCol` >= :start AND `$dateCol` < :end"
        );
        $stmt->execute(['start' => $start, 'end' => $endExclusive]);
        return (float) $stmt->fetchColumn();
    }

    private function pctChange(float $previous, float $current): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function getKpis(): array
    {
        $totalIncome = (float) $this->pdo->query("SELECT COALESCE(SUM(monto), 0) FROM incomes")->fetchColumn();
        $totalExpense = (float) $this->pdo->query("SELECT COALESCE(SUM(monto), 0) FROM expenses")->fetchColumn();

        $curStart = (new DateTime('first day of this month'))->format('Y-m-d');
        $nextStart = (new DateTime('first day of next month'))->format('Y-m-d');
        $prevStart = (new DateTime('first day of last month'))->format('Y-m-d');

        $curIncome = $this->sumForRange('incomes', 'fecha_ingreso', $curStart, $nextStart);
        $prevIncome = $this->sumForRange('incomes', 'fecha_ingreso', $prevStart, $curStart);
        $curExpense = $this->sumForRange('expenses', 'fecha_egreso', $curStart, $nextStart);
        $prevExpense = $this->sumForRange('expenses', 'fecha_egreso', $prevStart, $curStart);

        $fleet = $this->pdo->query(
            "SELECT
                (SELECT COUNT(*) FROM vehicles) +
                (SELECT COUNT(*) FROM heavy_transport) +
                (SELECT COUNT(*) FROM machinery) +
                (SELECT COUNT(*) FROM special_machinery) AS total,
                (SELECT COUNT(*) FROM vehicles WHERE estatus = 'En Funcionamiento') +
                (SELECT COUNT(*) FROM heavy_transport WHERE estado = 'En Funcionamiento') +
                (SELECT COUNT(*) FROM machinery WHERE estado = 'Activo') +
                (SELECT COUNT(*) FROM special_machinery WHERE estado = 'En Funcionamiento') AS active
            "
        )->fetch(PDO::FETCH_ASSOC);

        $incidentsThisMonth = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM employee_incidents WHERE MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())"
        )->fetchColumn();

        return [
            'total_income'         => $totalIncome,
            'total_expense'        => $totalExpense,
            'income_change_pct'    => $this->pctChange($prevIncome, $curIncome),
            'expense_change_pct'   => $this->pctChange($prevExpense, $curExpense),
            'fleet_active'         => (int) ($fleet['active'] ?? 0),
            'fleet_total'          => (int) ($fleet['total'] ?? 0),
            'incidents_this_month' => $incidentsThisMonth,
        ];
    }

    private function getFinancialChart(): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $dt = new DateTime("first day of -$i month");
            $start = $dt->format('Y-m-01');
            $end = (clone $dt)->modify('first day of next month')->format('Y-m-01');

            $income = $this->sumForRange('incomes', 'fecha_ingreso', $start, $end);
            $expense = $this->sumForRange('expenses', 'fecha_egreso', $start, $end);

            $months[] = [
                'month'   => self::MESES[(int) $dt->format('n')],
                'income'  => $income,
                'expense' => $expense,
            ];
        }
        return $months;
    }

    private function getProjectsStatus(): array
    {
        $rows = $this->pdo->query("SELECT estado, COUNT(*) as total FROM projects GROUP BY estado")
            ->fetchAll(PDO::FETCH_KEY_PAIR);

        $result = [
            'Activo'     => 0,
            'Pausado'    => 0,
            'Completado' => 0,
            'Cancelado'  => 0,
            'Borrador'   => 0,
        ];

        foreach ($rows as $estado => $total) {
            $result[$estado] = (int) $total;
        }

        return $result;
    }

    private function getFeaturedProjects(): array
    {
        $sql = "SELECT p.id, p.nombre, p.codigo, p.estado, p.foto, p.presupuesto,
                       p.fecha_inicio, p.fecha_fin_estimada, p.updated_at,
                       COALESCE(SUM(e.monto), 0) as gasto_total
                FROM projects p
                LEFT JOIN expenses e ON e.proyecto_id = p.id
                GROUP BY p.id
                ORDER BY (p.estado = 'Activo') DESC, p.updated_at DESC
                LIMIT 3";

        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            $presupuesto = (float) $row['presupuesto'];
            $gasto = (float) $row['gasto_total'];
            $avance = $presupuesto > 0 ? min(100, round(($gasto / $presupuesto) * 100, 1)) : 0.0;

            return [
                'id'                 => (int) $row['id'],
                'nombre'             => $row['nombre'],
                'codigo'             => $row['codigo'],
                'estado'             => $row['estado'],
                'foto'               => $row['foto'],
                'presupuesto'        => $presupuesto,
                'fecha_inicio'       => $row['fecha_inicio'],
                'fecha_fin_estimada' => $row['fecha_fin_estimada'],
                'avance_financiero'  => $avance,
            ];
        }, $rows);
    }

    private function getInventoryAlerts(): array
    {
        $sql = "SELECT id, nombre, tipo_item, unidad_medida, stock_actual, stock_minimo
                FROM inventory_items
                WHERE stock_minimo > 0 AND stock_actual <= stock_minimo
                ORDER BY (stock_actual / stock_minimo) ASC
                LIMIT 5";

        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $items = array_map(function ($row) {
            $stockActual = (float) $row['stock_actual'];
            $stockMinimo = (float) $row['stock_minimo'];
            $pct = $stockMinimo > 0 ? round(($stockActual / $stockMinimo) * 100, 0) : 0;

            return [
                'id'            => (int) $row['id'],
                'nombre'        => $row['nombre'],
                'tipo_item'     => $row['tipo_item'],
                'unidad_medida' => $row['unidad_medida'],
                'stock_actual'  => $stockActual,
                'stock_minimo'  => $stockMinimo,
                'pct_restante'  => $pct,
                'critico'       => $pct <= 50,
            ];
        }, $rows);

        $critical = count(array_filter($items, fn($i) => $i['critico']));

        return [
            'critical_count' => $critical,
            'items'          => $items,
        ];
    }

    private function getRecentActivity(): array
    {
        $sql = "SELECT * FROM (
                    (SELECT 'income' as type, monto as amount, tipo_ingreso as label, pagador as who, proyecto_id, created_at as date FROM incomes)
                    UNION ALL
                    (SELECT 'expense' as type, monto as amount, tipo_egreso as label, beneficiario as who, proyecto_id, created_at as date FROM expenses)
                    UNION ALL
                    (SELECT 'project' as type, NULL as amount, estado as label, nombre as who, id as proyecto_id, COALESCE(updated_at, created_at) as date FROM projects)
                    UNION ALL
                    (SELECT 'document' as type, NULL as amount, tipo_documento as label, nombre_documento as who, project_id as proyecto_id, created_at as date FROM digital_documents)
                ) t
                WHERE date IS NOT NULL
                ORDER BY date DESC
                LIMIT 8";

        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $projectNames = $this->pdo->query("SELECT id, nombre FROM projects")->fetchAll(PDO::FETCH_KEY_PAIR);

        return array_map(function ($row) use ($projectNames) {
            $proyectoNombre = $row['proyecto_id'] ? ($projectNames[$row['proyecto_id']] ?? null) : null;

            return [
                'type'             => $row['type'],
                'amount'           => $row['amount'] !== null ? (float) $row['amount'] : null,
                'label'            => $row['label'],
                'who'              => $row['who'],
                'proyecto_nombre'  => $proyectoNombre,
                'date'             => $row['date'],
            ];
        }, $rows);
    }
}
