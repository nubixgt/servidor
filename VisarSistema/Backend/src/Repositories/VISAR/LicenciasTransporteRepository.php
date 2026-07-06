<?php
namespace App\Repositories\VISAR;

use App\Utils\Database;
use PDO;
use Exception;

class LicenciasTransporteRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getLicencias($filters, $limit, $offset)
    {
        $whereConditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $whereConditions[] = "(
                no_licencia LIKE ? OR 
                empresa LIKE ? OR 
                nit LIKE ? OR 
                placa LIKE ? OR 
                transporte_de LIKE ? OR 
                inspector LIKE ?
            )";
            $searchParam = "%{$filters['search']}%";
            for ($i = 0; $i < 6; $i++) {
                $params[] = $searchParam;
            }
        }

        if (!empty($filters['empresa'])) {
            $whereConditions[] = "empresa LIKE ?";
            $params[] = "%{$filters['empresa']}%";
        }

        if (!empty($filters['transporte'])) {
            $whereConditions[] = "transporte_de = ?";
            $params[] = $filters['transporte'];
        }

        if (!empty($filters['inspector'])) {
            $whereConditions[] = "inspector = ?";
            $params[] = $filters['inspector'];
        }

        if (!empty($filters['fechaDesde'])) {
            $whereConditions[] = "fecha_emision >= ?";
            $params[] = $filters['fechaDesde'];
        }

        if (!empty($filters['fechaHasta'])) {
            $whereConditions[] = "fecha_emision <= ?";
            $params[] = $filters['fechaHasta'];
        }

        $whereClause = '';
        if (count($whereConditions) > 0) {
            $whereClause = " WHERE " . implode(" AND ", $whereConditions);
        }

        $countSql = "SELECT COUNT(*) as total FROM visar_licencias_transporte" . $whereClause;
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetch()['total'];

        $sql = "SELECT * FROM visar_licencias_transporte" . $whereClause . " ORDER BY fecha_emision DESC LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'records' => $records,
            'total' => $totalRecords
        ];
    }
    
    public function getStats() {
        // Stats Generales
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM visar_licencias_transporte");
        $total = $stmt->fetch()['total'];
        
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM visar_licencias_transporte 
                             WHERE fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
        $porVencer = $stmt->fetch()['total'];
        
        $stmt = $this->db->query("SELECT COUNT(DISTINCT empresa) as total FROM visar_licencias_transporte");
        $empresas = $stmt->fetch()['total'];
        
        $stmt = $this->db->query("SELECT COUNT(DISTINCT transporte_de) as total FROM visar_licencias_transporte 
                             WHERE transporte_de IS NOT NULL AND transporte_de != ''");
        $tipos = $stmt->fetch()['total'];

        // Gráficas
        $tiposSql = "SELECT transporte_de, COUNT(*) as total FROM visar_licencias_transporte 
                     WHERE transporte_de IS NOT NULL AND transporte_de != '' 
                     GROUP BY transporte_de ORDER BY total DESC";
        $tiposData = $this->db->query($tiposSql)->fetchAll(PDO::FETCH_ASSOC);

        $mesesSql = "SELECT DATE_FORMAT(fecha_emision, '%Y-%m') as mes, COUNT(*) as total 
                     FROM visar_licencias_transporte 
                     WHERE fecha_emision IS NOT NULL 
                     GROUP BY mes ORDER BY mes ASC LIMIT 12";
        $mesesData = $this->db->query($mesesSql)->fetchAll(PDO::FETCH_ASSOC);

        $empresasSql = "SELECT empresa, COUNT(*) as total FROM visar_licencias_transporte 
                        WHERE empresa IS NOT NULL AND empresa != '' 
                        GROUP BY empresa ORDER BY total DESC LIMIT 10";
        $empresasData = $this->db->query($empresasSql)->fetchAll(PDO::FETCH_ASSOC);

        $inspectoresSql = "SELECT inspector, COUNT(*) as total FROM visar_licencias_transporte 
                           WHERE inspector IS NOT NULL AND inspector != '' 
                           GROUP BY inspector ORDER BY total DESC LIMIT 10";
        $inspectoresData = $this->db->query($inspectoresSql)->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total' => $total,
            'porVencer' => $porVencer,
            'empresas' => $empresas,
            'tipos' => $tipos,
            'graficas' => [
                'tipos_transporte' => $tiposData,
                'por_mes' => $mesesData,
                'top_empresas' => $empresasData,
                'top_inspectores' => $inspectoresData
            ]
        ];
    }

    public function getFilters()
    {
        $transportes = $this->db->query("SELECT DISTINCT transporte_de FROM visar_licencias_transporte WHERE transporte_de IS NOT NULL AND transporte_de != '' ORDER BY transporte_de")->fetchAll(PDO::FETCH_COLUMN);
        $inspectores = $this->db->query("SELECT DISTINCT inspector FROM visar_licencias_transporte WHERE inspector IS NOT NULL AND inspector != '' ORDER BY inspector")->fetchAll(PDO::FETCH_COLUMN);

        return [
            'transportes' => $transportes,
            'inspectores' => $inspectores
        ];
    }

    public function createMany(array $records)
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO visar_licencias_transporte (
                no_licencia, empresa, nit, placa, transporte_de, inspector, fecha_emision, fecha_vencimiento
            ) VALUES (
                :no_licencia, :empresa, :nit, :placa, :transporte_de, :inspector, :fecha_emision, :fecha_vencimiento
            )");

            foreach ($records as $record) {
                $stmt->execute([
                    ':no_licencia' => $record['no_licencia'] ?? null,
                    ':empresa' => $record['empresa'] ?? null,
                    ':nit' => $record['nit'] ?? null,
                    ':placa' => $record['placa'] ?? null,
                    ':transporte_de' => $record['transporte_de'] ?? null,
                    ':inspector' => $record['inspector'] ?? null,
                    ':fecha_emision' => $record['fecha_emision'] ?? null,
                    ':fecha_vencimiento' => $record['fecha_vencimiento'] ?? null
                ]);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
