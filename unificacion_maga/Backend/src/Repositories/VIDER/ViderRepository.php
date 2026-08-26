<?php
namespace App\Repositories\VIDER;

use App\Utils\Database;
use App\Services\Audit\AuditService;
use PDO;

class ViderRepository
{
    private $db;
    private $audit;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->audit = new AuditService();
    }

    public function getCatalogos($dependenciaId = null)
    {
        $stmtDep = $this->db->prepare("SELECT id, nombre, siglas FROM vider_dependencias ORDER BY nombre ASC");
        $stmtDep->execute();
        $dependencias = $stmtDep->fetchAll(PDO::FETCH_ASSOC);

        $stmtAct = $this->db->prepare("SELECT id, nombre FROM vider_actividades ORDER BY nombre ASC");
        $stmtAct->execute();
        $actividades = $stmtAct->fetchAll(PDO::FETCH_ASSOC);

        $stmtProd = $this->db->prepare("SELECT id, nombre FROM vider_productos ORDER BY nombre ASC");
        $stmtProd->execute();
        $productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

        $stmtInt = $this->db->prepare("SELECT id, nombre FROM vider_intervenciones ORDER BY nombre ASC");
        $stmtInt->execute();
        $intervenciones = $stmtInt->fetchAll(PDO::FETCH_ASSOC);

        $stmtDepto = $this->db->prepare("SELECT id, nombre FROM vider_departamentos ORDER BY nombre ASC");
        $stmtDepto->execute();
        $departamentos = $stmtDepto->fetchAll(PDO::FETCH_ASSOC);

        return [
            'dependencias'  => $dependencias,
            'actividades'   => $actividades,
            'productos'     => $productos,
            'intervenciones'=> $intervenciones,
            'departamentos' => $departamentos
        ];
    }

    public function getDashboardStats($filters = [])
    {
        $where = " WHERE 1=1";
        $params = [];

        if (!empty($filters['departamento'])) {
            $where .= " AND dep.nombre = :depto";
            $params['depto'] = $filters['departamento'];
        }
        if (!empty($filters['dependencia_id'])) {
            $where .= " AND e.dependencia_id = :dep_id";
            $params['dep_id'] = $filters['dependencia_id'];
        }
        if (!empty($filters['actividad_id'])) {
            $where .= " AND e.actividad_id = :act_id";
            $params['act_id'] = $filters['actividad_id'];
        }
        if (!empty($filters['producto_id'])) {
            $where .= " AND e.producto_id = :prod_id";
            $params['prod_id'] = $filters['producto_id'];
        }
        if (!empty($filters['intervencion_id'])) {
            $where .= " AND e.intervencion_id = :int_id";
            $params['int_id'] = $filters['intervencion_id'];
        }

        // ─── Stats Globales desde vider_datos (importaciones masivas) ───────
        $sqlGlobal = "
            SELECT 
                SUM(e.beneficiarios)                    as total_beneficiarios,
                SUM(e.hombres)                          as total_hombres,
                SUM(e.mujeres)                          as total_mujeres,
                COUNT(DISTINCT e.departamento_id)       as total_departamentos,
                COUNT(DISTINCT e.municipio_id)          as total_municipios,
                SUM(e.vigente_financiera)               as total_vigente,
                SUM(e.financiera_ejecutado)             as total_financiero_ejecutado,
                SUM(e.ejecutado)                        as total_ejecutado
            FROM vider_datos e
            LEFT JOIN vider_departamentos dep ON e.departamento_id = dep.id
            $where
        ";
        $stmt = $this->db->prepare($sqlGlobal);
        $stmt->execute($params);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // ─── Físico por tipo de medida ────────────────────────────────────
        $stats['fisico'] = [
            'personas'  => ['planificado' => 0, 'ejecutado' => 0],
            'hectareas' => ['planificado' => 0, 'ejecutado' => 0],
            'metros'    => ['planificado' => 0, 'ejecutado' => 0],
            'm2'        => ['planificado' => 0, 'ejecutado' => 0]
        ];

        $sqlFisico = "
            SELECT m.nombre as tipo, SUM(e.programado) as plan, SUM(e.ejecutado) as ejec 
            FROM vider_datos e 
            LEFT JOIN vider_departamentos dep ON e.departamento_id = dep.id
            JOIN vider_medidas m ON e.medida_id = m.id 
            $where 
            GROUP BY m.nombre
        ";
        $stmt = $this->db->prepare($sqlFisico);
        $stmt->execute($params);
        $resFisico = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($resFisico as $row) {
            $tipo = strtolower(trim(str_replace('²','2', $row['tipo'])));
            if (strpos($tipo, 'persona') !== false)  $tipo = 'personas';
            if (strpos($tipo, 'hect') !== false)     $tipo = 'hectareas';
            if (strpos($tipo, 'metro') !== false && strpos($tipo, '2') === false) $tipo = 'metros';
            if (strpos($tipo, 'm2') !== false || strpos($tipo, 'cuadrad') !== false) $tipo = 'm2';

            if (isset($stats['fisico'][$tipo])) {
                $stats['fisico'][$tipo] = [
                    'planificado' => $row['plan'] ?? 0,
                    'ejecutado'   => $row['ejec'] ?? 0
                ];
            }
        }

        // ─── Por Dependencia ─────────────────────────────────────────────
        $sqlDep = "
            SELECT d.siglas, 
                   SUM(e.beneficiarios)          as beneficiarios,
                   SUM(e.hombres)                as hombres,
                   SUM(e.mujeres)                as mujeres,
                   SUM(e.programado)             as programado,
                   SUM(e.ejecutado)              as ejecutado,
                   SUM(e.financiera_ejecutado)   as fin_ejecutado
            FROM vider_datos e
            JOIN vider_dependencias d ON e.dependencia_id = d.id
            LEFT JOIN vider_departamentos dep ON e.departamento_id = dep.id
            $where
            GROUP BY d.siglas
        ";
        $stmt = $this->db->prepare($sqlDep);
        $stmt->execute($params);
        $stats['por_dependencia'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ─── Por Departamento (Top 10) ────────────────────────────────────
        $sqlDepto = "
            SELECT dep.nombre, 
                   SUM(e.beneficiarios) as beneficiarios
            FROM vider_datos e
            JOIN vider_departamentos dep ON e.departamento_id = dep.id
            $where
            GROUP BY dep.nombre
            ORDER BY beneficiarios DESC
            LIMIT 10
        ";
        $stmt = $this->db->prepare($sqlDepto);
        $stmt->execute($params);
        $stats['por_departamento'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $stats;
    }

    public function getMapData($filters = [])
    {
        $where = " WHERE 1=1";
        $params = [];
        
        $isMunicipio = false;
        if (!empty($filters['departamento'])) { 
            $where .= " AND dep.nombre = :depto"; 
            $params['depto'] = $filters['departamento']; 
            $isMunicipio = true;
        }
        
        if ($isMunicipio) {
            $sql = "
                SELECT mun.nombre as municipio, 
                       SUM(e.beneficiarios) as total_beneficiarios,
                       SUM(e.beneficiarios) as beneficiarios,
                       SUM(e.hombres) as hombres,
                       SUM(e.mujeres) as mujeres
                FROM vider_datos e 
                JOIN vider_departamentos dep ON e.departamento_id = dep.id
                JOIN vider_municipios mun ON e.municipio_id = mun.id
                $where 
                GROUP BY mun.nombre
            ";
        } else {
            $sql = "
                SELECT dep.nombre as departamento, 
                       SUM(e.beneficiarios) as beneficiarios,
                       SUM(e.hombres) as hombres,
                       SUM(e.mujeres) as mujeres
                FROM vider_datos e 
                JOIN vider_departamentos dep ON e.departamento_id = dep.id
                $where 
                GROUP BY dep.nombre
            ";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REGISTROS: UNION de vider_datos (importados) + vider_ejecucion (manuales)
    // ─────────────────────────────────────────────────────────────────────────
    public function getRecords($filters = [])
    {
        $whereData = " WHERE 1=1";
        $whereEjec = " WHERE 1=1";
        $paramsData = [];
        $paramsEjec = [];

        if (!empty($filters['departamento'])) {
            $whereData .= " AND dep.nombre = :depto";
            $paramsData['depto'] = $filters['departamento'];
            $whereEjec .= " AND UPPER(ej.departamento) = UPPER(:depto_e)";
            $paramsEjec['depto_e'] = $filters['departamento'];
        }
        if (!empty($filters['dependencia_id'])) {
            $whereData .= " AND e.dependencia_id = :dep_id";
            $paramsData['dep_id'] = $filters['dependencia_id'];
            $whereEjec .= " AND ej.dependencia_id = :dep_id_e";
            $paramsEjec['dep_id_e'] = $filters['dependencia_id'];
        }
        if (!empty($filters['actividad_id'])) {
            $whereData .= " AND e.actividad_id = :act_id";
            $paramsData['act_id'] = $filters['actividad_id'];
            $whereEjec .= " AND ej.actividad_id = :act_id_e";
            $paramsEjec['act_id_e'] = $filters['actividad_id'];
        }
        if (!empty($filters['producto_id'])) {
            $whereData .= " AND e.producto_id = :prod_id";
            $paramsData['prod_id'] = $filters['producto_id'];
            $whereEjec .= " AND ej.producto_id = :prod_id_e";
            $paramsEjec['prod_id_e'] = $filters['producto_id'];
        }
        if (!empty($filters['search'])) {
            $whereData .= " AND (c1.nombre LIKE :search OR c2.nombre LIKE :search OR c3.nombre LIKE :search)";
            $paramsData['search'] = "%{$filters['search']}%";
            $whereEjec .= " AND (c1e.nombre LIKE :search_e OR c2e.nombre LIKE :search_e OR c3e.nombre LIKE :search_e)";
            $paramsEjec['search_e'] = "%{$filters['search']}%";
        }

        // Parte 1: registros importados (vider_datos)
        $sqlData = "
            SELECT e.id, 'importado' as fuente,
                   dep.nombre as departamento, mun.nombre as municipio, d.siglas,
                   c1.nombre as actividad, c2.nombre as producto, c3.nombre as intervencion,
                   e.programado, e.ejecutado, e.porcentaje_ejecucion,
                   e.hombres, e.mujeres, e.total_personas,
                   e.vigente_financiera, e.financiera_ejecutado, e.financiera_porcentaje,
                   e.created_at
            FROM vider_datos e
            LEFT JOIN vider_dependencias d   ON e.dependencia_id = d.id
            LEFT JOIN vider_departamentos dep ON e.departamento_id = dep.id
            LEFT JOIN vider_municipios mun   ON e.municipio_id = mun.id
            LEFT JOIN vider_actividades c1   ON e.actividad_id = c1.id
            LEFT JOIN vider_productos c2     ON e.producto_id = c2.id
            LEFT JOIN vider_intervenciones c3 ON e.intervencion_id = c3.id
            $whereData
        ";

        // Parte 2: registros manuales (vider_ejecucion)
        $sqlEjec = "
            SELECT ej.id, 'manual' as fuente,
                   ej.departamento, ej.municipio, d.siglas,
                   c1e.nombre as actividad, c2e.nombre as producto, c3e.nombre as intervencion,
                   ej.fisico_planificado as programado,
                   ej.fisico_ejecutado as ejecutado,
                   CASE WHEN ej.fisico_planificado > 0 
                        THEN ROUND((ej.fisico_ejecutado / ej.fisico_planificado) * 100, 2)
                        ELSE 0 END as porcentaje_ejecucion,
                   0 as hombres, 0 as mujeres, 0 as total_personas,
                   ej.financiero_vigente as vigente_financiera,
                   ej.financiero_ejecutado as financiera_ejecutado,
                   CASE WHEN ej.financiero_vigente > 0
                        THEN ROUND((ej.financiero_ejecutado / ej.financiero_vigente) * 100, 2)
                        ELSE 0 END as financiera_porcentaje,
                   ej.created_at as created_at
            FROM vider_ejecucion ej
            LEFT JOIN vider_dependencias d    ON ej.dependencia_id = d.id
            LEFT JOIN vider_actividades c1e   ON ej.actividad_id = c1e.id
            LEFT JOIN vider_productos c2e     ON ej.producto_id = c2e.id
            LEFT JOIN vider_intervenciones c3e ON ej.intervencion_id = c3e.id
            $whereEjec
        ";

        $allParams = array_merge($paramsData, $paramsEjec);
        $unionSql = "($sqlData UNION ALL $sqlEjec) AS combined ORDER BY created_at DESC LIMIT 500";

        $stmt = $this->db->prepare("SELECT * FROM $unionSql");
        foreach ($allParams as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CRUD — escribe en vider_ejecucion con las columnas correctas
    // ─────────────────────────────────────────────────────────────────────────
    public function create(array $data)
    {
        $sql = "INSERT INTO vider_ejecucion 
                    (fecha, departamento, municipio, dependencia_id, actividad_id, 
                     producto_id, intervencion_id, genero, fisico_tipo, 
                     fisico_planificado, fisico_ejecutado, financiero_vigente, financiero_ejecutado) 
                VALUES 
                    (:fecha, :departamento, :municipio, :dependencia_id, :actividad_id, 
                     :producto_id, :intervencion_id, :genero, :fisico_tipo, 
                     :fisico_planificado, :fisico_ejecutado, :financiero_vigente, :financiero_ejecutado)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        $id = $this->db->lastInsertId();

        // Registrar auditoría
        $this->audit->log(1, 'CREATE', 'vider_ejecucion', $id, null, $data);
        return $id;
    }

    public function update($id, array $data)
    {
        // Obtener valores anteriores para auditoría
        $stmtOld = $this->db->prepare("SELECT * FROM vider_ejecucion WHERE id = ?");
        $stmtOld->execute([$id]);
        $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

        $sql = "UPDATE vider_ejecucion 
                SET fecha = :fecha, 
                    departamento = :departamento, 
                    municipio = :municipio, 
                    dependencia_id = :dependencia_id, 
                    actividad_id = :actividad_id, 
                    producto_id = :producto_id, 
                    intervencion_id = :intervencion_id, 
                    genero = :genero, 
                    fisico_tipo = :fisico_tipo, 
                    fisico_planificado = :fisico_planificado, 
                    fisico_ejecutado = :fisico_ejecutado, 
                    financiero_vigente = :financiero_vigente, 
                    financiero_ejecutado = :financiero_ejecutado 
                WHERE id = :id";
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute($data);

        if ($success) {
            $this->audit->log(1, 'UPDATE', 'vider_ejecucion', $id, $oldData, $data);
        }
        return $success;
    }

    public function delete($id)
    {
        // Obtener valores anteriores para auditoría
        $stmtOld = $this->db->prepare("SELECT * FROM vider_ejecucion WHERE id = ?");
        $stmtOld->execute([$id]);
        $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare("DELETE FROM vider_ejecucion WHERE id = ?");
        $success = $stmt->execute([$id]);

        if ($success) {
            $this->audit->log(1, 'DELETE', 'vider_ejecucion', $id, $oldData, null);
        }
        return $success;
    }
}

