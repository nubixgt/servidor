<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;

class SupplierRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(): array
    {
        $sql = "SELECT s.*,
                       (
                           COALESCE((SELECT SUM(po.total) FROM purchase_orders po WHERE po.proveedor_id = s.id), 0) +
                           COALESCE((SELECT SUM(mri.monto) FROM mechanic_record_items mri WHERE mri.proveedor_id = s.id), 0) +
                           COALESCE((SELECT SUM(mr.mano_obra_monto) FROM mechanic_records mr WHERE mr.mano_obra_proveedor_id = s.id), 0)
                       ) AS total_historico,
                       (
                           COALESCE((SELECT COUNT(po.id) FROM purchase_orders po WHERE po.proveedor_id = s.id), 0) +
                           COALESCE((SELECT COUNT(mri.id) FROM mechanic_record_items mri WHERE mri.proveedor_id = s.id), 0) +
                           COALESCE((SELECT COUNT(mr.id) FROM mechanic_records mr WHERE mr.mano_obra_proveedor_id = s.id AND mr.mano_obra_monto > 0), 0)
                       ) AS transacciones_count
                FROM suppliers s 
                ORDER BY s.razon_social ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM suppliers WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function findByNit(string $nit, ?int $excludeId = null): ?array
    {
        $sql = "SELECT id FROM suppliers WHERE nit = :nit";
        $params = ['nit' => $nit];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function getMonthlyHistory(int $supplierId): array
    {
        // 1. Fetch from purchase_orders
        $sqlPO = "SELECT 
                    'Orden de Compra' AS origen,
                    po.id AS referencia_id,
                    CONCAT('ORD-', LPAD(po.id, 4, '0')) AS documento,
                    po.fecha_orden AS fecha,
                    CAST(po.total AS DECIMAL(10,2)) AS monto,
                    COALESCE(p.nombre, 'General / Inventario') AS proyecto_unidad,
                    po.condicion_pago,
                    po.archivo_adjunto,
                    po.observaciones
                  FROM purchase_orders po
                  LEFT JOIN projects p ON p.id = po.proyecto_id
                  WHERE po.proveedor_id = :id";
        $stmtPO = $this->pdo->prepare($sqlPO);
        $stmtPO->execute(['id' => $supplierId]);
        $orders = $stmtPO->fetchAll(PDO::FETCH_ASSOC);

        // 2. Fetch from mechanic_record_items
        $sqlMecItems = "SELECT 
                            'Mecánica (Repuestos)' AS origen,
                            mr.id AS referencia_id,
                            CONCAT('MEC-', LPAD(mr.id, 4, '0'), ' · ', mr.placa) AS documento,
                            mr.fecha AS fecha,
                            CAST(mri.monto AS DECIMAL(10,2)) AS monto,
                            CONCAT(mr.placa, ' · ', mri.producto) AS proyecto_unidad,
                            'Contado' AS condicion_pago,
                            mri.foto_factura AS archivo_adjunto,
                            mr.observaciones
                        FROM mechanic_record_items mri
                        JOIN mechanic_records mr ON mr.id = mri.mechanic_record_id
                        WHERE mri.proveedor_id = :id";
        $stmtMecItems = $this->pdo->prepare($sqlMecItems);
        $stmtMecItems->execute(['id' => $supplierId]);
        $mecItems = $stmtMecItems->fetchAll(PDO::FETCH_ASSOC);

        // 3. Fetch from mechanic_records (Mano de obra)
        $sqlMecMO = "SELECT 
                        'Mecánica (Mano de Obra)' AS origen,
                        mr.id AS referencia_id,
                        CONCAT('MEC-', LPAD(mr.id, 4, '0'), ' · ', mr.placa) AS documento,
                        mr.fecha AS fecha,
                        CAST(mr.mano_obra_monto AS DECIMAL(10,2)) AS monto,
                        CONCAT(mr.placa, ' · Mano de Obra (', COALESCE(mr.tipo_trabajo, 'Servicio'), ')') AS proyecto_unidad,
                        'Contado' AS condicion_pago,
                        mr.mano_obra_factura AS archivo_adjunto,
                        mr.observaciones
                    FROM mechanic_records mr
                    WHERE mr.mano_obra_proveedor_id = :id AND mr.mano_obra_monto > 0";
        $stmtMecMO = $this->pdo->prepare($sqlMecMO);
        $stmtMecMO->execute(['id' => $supplierId]);
        $mecMO = $stmtMecMO->fetchAll(PDO::FETCH_ASSOC);

        // Merge all transactions
        $transactions = array_merge($orders, $mecItems, $mecMO);

        // Sort by fecha DESC, referencia_id DESC
        usort($transactions, function($a, $b) {
            $cmp = strcmp($b['fecha'] ?? '', $a['fecha'] ?? '');
            if ($cmp !== 0) return $cmp;
            return ($b['referencia_id'] ?? 0) <=> ($a['referencia_id'] ?? 0);
        });

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

    public function create(array $data): void
    {
        $sql = "INSERT INTO suppliers 
                    (razon_social, nit, direccion, telefono, correo_electronico, contacto_principal, condicion_pago, dias_credito)
                VALUES 
                    (:razon_social, :nit, :direccion, :telefono, :correo_electronico, :contacto_principal, :condicion_pago, :dias_credito)";
        
        $this->pdo->prepare($sql)->execute([
            'razon_social'       => $data['razon_social'],
            'nit'                => $data['nit'],
            'direccion'          => $data['direccion'],
            'telefono'           => $data['telefono'],
            'correo_electronico' => $data['correo_electronico'] ?? null,
            'contacto_principal' => $data['contacto_principal'] ?? null,
            'condicion_pago'     => $data['condicion_pago'] ?? 'Contado',
            'dias_credito'       => $data['dias_credito'] ?? null
        ]);
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE suppliers SET 
                    razon_social = :razon_social,
                    nit = :nit,
                    direccion = :direccion,
                    telefono = :telefono,
                    correo_electronico = :correo_electronico,
                    contacto_principal = :contacto_principal,
                    condicion_pago = :condicion_pago,
                    dias_credito = :dias_credito
                WHERE id = :id";
        
        $data['id'] = $id;
        $this->pdo->prepare($sql)->execute([
            'razon_social'       => $data['razon_social'],
            'nit'                => $data['nit'],
            'direccion'          => $data['direccion'],
            'telefono'           => $data['telefono'],
            'correo_electronico' => $data['correo_electronico'] ?? null,
            'contacto_principal' => $data['contacto_principal'] ?? null,
            'condicion_pago'     => $data['condicion_pago'] ?? 'Contado',
            'dias_credito'       => $data['dias_credito'] ?? null,
            'id'                 => $id
        ]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("DELETE FROM suppliers WHERE id = :id")->execute(['id' => $id]);
    }
}
