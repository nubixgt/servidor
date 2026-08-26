<?php
namespace App\Repositories\VISAN;

use App\Utils\Database;
use PDO;

class EntregaRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($filters = [])
    {
        $sql = "SELECT * FROM visan_entregas WHERE 1=1";
        $params = [];

        if (!empty($filters['departamento'])) {
            $sql .= " AND departamento = :departamento";
            $params['departamento'] = $filters['departamento'];
        }

        if (!empty($filters['municipio'])) {
            $sql .= " AND municipio = :municipio";
            $params['municipio'] = $filters['municipio'];
        }

        if (!empty($filters['fecha_inicio'])) {
            $sql .= " AND fecha >= :fecha_inicio";
            $params['fecha_inicio'] = $filters['fecha_inicio'];
        }

        if (!empty($filters['fecha_fin'])) {
            $sql .= " AND fecha <= :fecha_fin";
            $params['fecha_fin'] = $filters['fecha_fin'];
        }

        $sql .= " ORDER BY fecha DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM visan_entregas WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO visan_entregas (fecha, departamento, municipio, tipo_asistencia, raciones, familias, observaciones) 
                VALUES (:fecha, :departamento, :municipio, :tipo_asistencia, :raciones, :familias, :observaciones)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function update($id, array $data)
    {
        $sql = "UPDATE visan_entregas 
                SET fecha = :fecha, departamento = :departamento, municipio = :municipio, 
                    tipo_asistencia = :tipo_asistencia, raciones = :raciones, 
                    familias = :familias, observaciones = :observaciones 
                WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM visan_entregas WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getDashboardStats($filters = [])
    {
        $where = " WHERE 1=1";
        $params = [];

        if (!empty($filters['departamento'])) {
            $where .= " AND departamento = :departamento";
            $params['departamento'] = $filters['departamento'];
        }
        if (!empty($filters['municipio'])) {
            $where .= " AND municipio = :municipio";
            $params['municipio'] = $filters['municipio'];
        }
        if (!empty($filters['fecha_inicio'])) {
            $where .= " AND fecha >= :fecha_inicio";
            $params['fecha_inicio'] = $filters['fecha_inicio'];
        }
        if (!empty($filters['fecha_fin'])) {
            $where .= " AND fecha <= :fecha_fin";
            $params['fecha_fin'] = $filters['fecha_fin'];
        }

        // Stats principales
        $sqlStats = "
            SELECT 
                SUM(CASE WHEN tipo_asistencia = 'AA' THEN raciones ELSE 0 END) as total_aa_r,
                SUM(CASE WHEN tipo_asistencia = 'APA' THEN familias ELSE 0 END) as total_apa_f,
                SUM(raciones) as total_aa_apa,
                SUM(CASE WHEN tipo_asistencia = 'INSAN' THEN raciones ELSE 0 END) as insan_r,
                SUM(CASE WHEN tipo_asistencia = 'INSAN' THEN familias ELSE 0 END) as insan_f,
                SUM(CASE WHEN tipo_asistencia = 'MEDIDA TRANSITORIA' THEN raciones ELSE 0 END) as medida_transitoria_r,
                SUM(CASE WHEN tipo_asistencia = 'MEDIDA TRANSITORIA' THEN familias ELSE 0 END) as medida_transitoria_f,
                SUM(CASE WHEN tipo_asistencia = 'NDA NACIONAL' THEN raciones ELSE 0 END) as nda_nacional_r,
                SUM(CASE WHEN tipo_asistencia = 'NDA NACIONAL' THEN familias ELSE 0 END) as nda_nacional_f,
                SUM(CASE WHEN tipo_asistencia = 'MEDIDA CAUTELAR' THEN raciones ELSE 0 END) as medida_cautelar_r,
                SUM(CASE WHEN tipo_asistencia = 'MEDIDA CAUTELAR' THEN familias ELSE 0 END) as medida_cautelar_f,
                SUM(CASE WHEN tipo_asistencia = 'RESERVA ESTRATÉGICA' THEN raciones ELSE 0 END) as reserva_estrategica_r,
                COUNT(DISTINCT departamento) as total_departamentos,
                COUNT(DISTINCT municipio) as total_municipios
            FROM visan_entregas $where
        ";

        $stmt = $this->db->prepare($sqlStats);
        $stmt->execute($params);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Gráficas - Top 10 Dept
        $sqlDept = "SELECT departamento, SUM(raciones) as total FROM visan_entregas $where GROUP BY departamento ORDER BY total DESC LIMIT 10";
        $stmt = $this->db->prepare($sqlDept);
        $stmt->execute($params);
        $deptData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'stats' => $stats,
            'graficas' => [
                'top10_dept_aa_apa' => [
                    'labels' => array_column($deptData, 'departamento'),
                    'data' => array_column($deptData, 'total')
                ]
            ],
            'filtros_activos' => count($params)
        ];
    }

    public function getTableData()
    {
        $stmt = $this->db->prepare("
            SELECT departamento, municipio, 
                   SUM(raciones) as total_aa_apa,
                   SUM(CASE WHEN tipo_asistencia = 'AA' THEN familias ELSE 0 END) as total_aa_f,
                   SUM(CASE WHEN tipo_asistencia = 'INSAN' THEN raciones ELSE 0 END) as insan_total,
                   SUM(CASE WHEN tipo_asistencia = 'NDA NACIONAL' THEN raciones ELSE 0 END) as nda_severa,
                   SUM(CASE WHEN tipo_asistencia = 'MEDIDA TRANSITORIA' THEN raciones ELSE 0 END) as medida_transitoria_r,
                   SUM(CASE WHEN tipo_asistencia = 'MEDIDA TRANSITORIA' THEN familias ELSE 0 END) as medida_transitoria_f,
                   SUM(CASE WHEN tipo_asistencia = 'MEDIDA CAUTELAR' THEN raciones ELSE 0 END) as medida_cautelar_r,
                   SUM(CASE WHEN tipo_asistencia = 'MEDIDA CAUTELAR' THEN familias ELSE 0 END) as medida_cautelar_f,
                   SUM(CASE WHEN tipo_asistencia = 'RESERVA ESTRATÉGICA' THEN raciones ELSE 0 END) as reserva_estrategica_r,
                   SUM(CASE WHEN tipo_asistencia = 'RESERVA ESTRATÉGICA' THEN familias ELSE 0 END) as reserva_estrategica_f,
                   MIN(id) as id
            FROM visan_entregas 
            GROUP BY departamento, municipio
            ORDER BY departamento ASC, municipio ASC
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $structured = [];
        foreach ($rows as $row) {
            $dept = $row['departamento'];
            if (!isset($structured[$dept])) {
                $structured[$dept] = [
                    'totales' => [
                        'total_aa_apa' => 0,
                        'insan_total' => 0,
                        'nda_severa' => 0,
                        'count' => 0
                    ],
                    'municipios' => []
                ];
            }
            $structured[$dept]['totales']['total_aa_apa'] += $row['total_aa_apa'];
            $structured[$dept]['totales']['insan_total'] += $row['insan_total'];
            $structured[$dept]['totales']['nda_severa'] += $row['nda_severa'];
            $structured[$dept]['totales']['count']++;
            $structured[$dept]['municipios'][] = $row;
        }

        return [
            'total_departamentos' => count($structured),
            'total_registros' => count($rows),
            'datos' => $structured
        ];
    }

    public function getLists()
    {
        $stmt = $this->db->prepare("SELECT DISTINCT departamento, municipio FROM visan_entregas ORDER BY departamento, municipio");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $depts = [];
        $muni_by_dept = [];

        foreach ($rows as $row) {
            $d = $row['departamento'];
            $m = $row['municipio'];
            if (!in_array($d, $depts)) {
                $depts[] = $d;
            }
            if (!isset($muni_by_dept[$d])) {
                $muni_by_dept[$d] = [];
            }
            $muni_by_dept[$d][] = $m;
        }

        return [
            'departamentos' => $depts,
            'municipios_por_dept' => $muni_by_dept,
            'municipios' => array_unique(array_column($rows, 'municipio'))
        ];
    }
}
