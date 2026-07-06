<?php
namespace App\Repositories\VISAR;

use App\Utils\Database;
use PDO;
use Exception;

class LicenciasFitosanitariasRepository
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
                no_recibo_osu LIKE ? OR 
                licencia LIKE ? OR 
                nombre_empresa LIKE ? OR 
                propietario LIKE ?
            )";
            $searchParam = "%{$filters['search']}%";
            for ($i = 0; $i < 4; $i++) {
                $params[] = $searchParam;
            }
        }

        if (!empty($filters['categoria'])) {
            $whereConditions[] = "categoria = ?";
            $params[] = $filters['categoria'];
        }

        if (!empty($filters['departamento'])) {
            $whereConditions[] = "departamento = ?";
            $params[] = $filters['departamento'];
        }
        
        if (!empty($filters['municipio'])) {
            $whereConditions[] = "municipio = ?";
            $params[] = $filters['municipio'];
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

        $countSql = "SELECT COUNT(*) as total FROM visar_licencias_fitosanitarias" . $whereClause;
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetch()['total'];

        $sql = "SELECT * FROM visar_licencias_fitosanitarias" . $whereClause . " ORDER BY fecha_emision DESC LIMIT ? OFFSET ?";
        
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
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM visar_licencias_fitosanitarias");
        $total = $stmt->fetch()['total'];
        
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM visar_licencias_fitosanitarias 
                             WHERE fecha_vencimiento >= CURDATE()");
        $vigentes = $stmt->fetch()['total'];
        
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM visar_licencias_fitosanitarias 
                             WHERE fecha_vencimiento < CURDATE()");
        $vencidas = $stmt->fetch()['total'];
        
        $stmt = $this->db->query("SELECT departamento, COUNT(*) as cantidad FROM visar_licencias_fitosanitarias 
                             WHERE departamento IS NOT NULL AND departamento != '' GROUP BY departamento ORDER BY cantidad DESC LIMIT 5");
        $topDeptos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Gráficas
        $deptosSql = "SELECT departamento, COUNT(*) as total FROM visar_licencias_fitosanitarias 
                      WHERE departamento IS NOT NULL AND departamento != '' 
                      GROUP BY departamento ORDER BY total DESC LIMIT 10";
        $deptosData = $this->db->query($deptosSql)->fetchAll(PDO::FETCH_ASSOC);

        $muniSql = "SELECT municipio, COUNT(*) as total FROM visar_licencias_fitosanitarias 
                    WHERE municipio IS NOT NULL AND municipio != '' 
                    GROUP BY municipio ORDER BY total DESC LIMIT 15";
        $muniData = $this->db->query($muniSql)->fetchAll(PDO::FETCH_ASSOC);

        $catSql = "SELECT categoria, COUNT(*) as total FROM visar_licencias_fitosanitarias 
                   WHERE categoria IS NOT NULL AND categoria != '' 
                   GROUP BY categoria ORDER BY total DESC";
        $catData = $this->db->query($catSql)->fetchAll(PDO::FETCH_ASSOC);

        $mesesSql = "SELECT MONTH(fecha_emision) as mes, COUNT(*) as total FROM visar_licencias_fitosanitarias 
                     WHERE fecha_emision IS NOT NULL AND YEAR(fecha_emision) = YEAR(CURDATE()) 
                     GROUP BY mes ORDER BY mes ASC";
        $mesesData = $this->db->query($mesesSql)->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total' => $total,
            'vigentes' => $vigentes,
            'vencidas' => $vencidas,
            'topDeptos' => $topDeptos,
            'graficas' => [
                'departamentos' => $deptosData,
                'municipios' => $muniData,
                'categorias' => $catData,
                'meses' => $mesesData
            ]
        ];
    }

    public function getFilters()
    {
        $categorias = $this->db->query("SELECT DISTINCT categoria FROM visar_licencias_fitosanitarias WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);
        $departamentos = $this->db->query("SELECT DISTINCT departamento FROM visar_licencias_fitosanitarias WHERE departamento IS NOT NULL AND departamento != '' ORDER BY departamento")->fetchAll(PDO::FETCH_COLUMN);
        
        // Municipios agrupados por departamento
        $municipiosRaw = $this->db->query("SELECT DISTINCT departamento, municipio FROM visar_licencias_fitosanitarias WHERE municipio IS NOT NULL AND municipio != '' AND departamento IS NOT NULL AND departamento != ''")->fetchAll(PDO::FETCH_ASSOC);
        
        $municipiosPorDepto = [];
        foreach ($municipiosRaw as $row) {
            $municipiosPorDepto[$row['departamento']][] = $row['municipio'];
        }

        return [
            'categorias' => $categorias,
            'departamentos' => $departamentos,
            'municipios' => $municipiosPorDepto
        ];
    }

    public function createMany(array $records)
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO visar_licencias_fitosanitarias (
                no_recibo_osu, licencia, nombre_empresa, propietario, categoria, 
                clasificacion, departamento, municipio, fecha_emision, fecha_vencimiento
            ) VALUES (
                :no_recibo_osu, :licencia, :nombre_empresa, :propietario, :categoria, 
                :clasificacion, :departamento, :municipio, :fecha_emision, :fecha_vencimiento
            )");

            foreach ($records as $record) {
                $stmt->execute([
                    ':no_recibo_osu' => $record['no_recibo_osu'] ?? null,
                    ':licencia' => $record['licencia'] ?? null,
                    ':nombre_empresa' => $record['nombre_empresa'] ?? null,
                    ':propietario' => $record['propietario'] ?? null,
                    ':categoria' => $record['categoria'] ?? null,
                    ':clasificacion' => $record['clasificacion'] ?? null,
                    ':departamento' => $record['departamento'] ?? null,
                    ':municipio' => $record['municipio'] ?? null,
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
