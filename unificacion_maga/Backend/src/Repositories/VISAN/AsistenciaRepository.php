<?php
namespace App\Repositories\VISAN;

use App\Utils\Database;
use PDO;
use Exception;

class AsistenciaRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    private function buildWhereClause($filtros)
    {
        $conditions = [];
        $params = [];

        if (!empty($filtros['fecha_inicio'])) {
            $conditions[] = "DATE(fecha_registro) >= :fecha_inicio";
            $params['fecha_inicio'] = $filtros['fecha_inicio'];
        }
        if (!empty($filtros['fecha_fin'])) {
            $conditions[] = "DATE(fecha_registro) <= :fecha_fin";
            $params['fecha_fin'] = $filtros['fecha_fin'];
        }
        if (!empty($filtros['departamento'])) {
            $conditions[] = "departamento = :departamento";
            $params['departamento'] = $filtros['departamento'];
        }
        if (!empty($filtros['municipio'])) {
            $conditions[] = "municipio = :municipio";
            $params['municipio'] = $filtros['municipio'];
        }
        if (!empty($filtros['busqueda'])) {
            $conditions[] = "(departamento LIKE :busqueda_d OR municipio LIKE :busqueda_m)";
            $params['busqueda_d'] = "%" . $filtros['busqueda'] . "%";
            $params['busqueda_m'] = "%" . $filtros['busqueda'] . "%";
        }

        $clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return ['clause' => $clause, 'params' => $params];
    }

    private function executeQueryWithFilters($query, $filtros)
    {
        $where = $this->buildWhereClause($filtros);
        $fullQuery = str_replace('{WHERE}', $where['clause'], $query);
        $stmt = $this->db->prepare($fullQuery);
        $stmt->execute($where['params']);
        return $stmt;
    }

    public function getEstadisticasGenerales($filtros)
    {
        $query = "SELECT 
            SUM(total_aa_r) as total_aa_r,
            SUM(total_aa_f) as total_aa_f,
            SUM(apa_f) as total_apa_f,
            SUM(apa_huertos) as total_apa_huertos,
            SUM(total_aa_apa) as total_aa_apa,
            SUM(reserva_estrategica_r) as reserva_estrategica_r,
            SUM(reserva_estrategica_f) as reserva_estrategica_f,
            SUM(nda_severa_r + nda_severa_f) as nda_severa,
            SUM(nda_nacional_r) as nda_nacional_r,
            SUM(nda_nacional_f) as nda_nacional_f,
            SUM(medida_cautelar_r) as medida_cautelar_r,
            SUM(medida_cautelar_f) as medida_cautelar_f,
            SUM(insan_r + insan_f) as insan_total,
            SUM(insan_r) as insan_r,
            SUM(insan_f) as insan_f,
            SUM(medida_transitoria_r + medida_transitoria_f) as medida_transitoria,
            SUM(medida_transitoria_r) as medida_transitoria_r,
            SUM(medida_transitoria_f) as medida_transitoria_f,
            COUNT(DISTINCT municipio) as total_municipios,
            COUNT(DISTINCT departamento) as total_departamentos,
            COUNT(*) as total_registros,
            MIN(fecha_registro) as fecha_minima,
            MAX(fecha_registro) as fecha_maxima
        FROM visan_datos_asistencia {WHERE}";

        $stmt = $this->executeQueryWithFilters($query, $filtros);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: [];
    }

    public function getTop10Departamentos($filtros)
    {
        $query = "SELECT departamento, SUM(total_aa_apa) as total_asistencia 
            FROM visan_datos_asistencia {WHERE}
            GROUP BY departamento 
            ORDER BY total_asistencia DESC 
            LIMIT 10";
        return $this->executeQueryWithFilters($query, $filtros)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTop15Municipios($filtros)
    {
        $query = "SELECT municipio, departamento, total_aa_apa as total_asistencia 
            FROM visan_datos_asistencia {WHERE}
            ORDER BY total_asistencia DESC 
            LIMIT 15";
        return $this->executeQueryWithFilters($query, $filtros)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTop10Insan($filtros)
    {
        $query = "SELECT departamento, SUM(insan_r + insan_f) as total_insan 
            FROM visan_datos_asistencia {WHERE}
            GROUP BY departamento 
            ORDER BY total_insan DESC 
            LIMIT 10";
        return $this->executeQueryWithFilters($query, $filtros)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNdaPorDept($filtros)
    {
        $query = "SELECT departamento, 
            SUM(nda_severa_r) as nda_recursos,
            SUM(nda_severa_f) as nda_fondos
            FROM visan_datos_asistencia {WHERE}
            GROUP BY departamento 
            ORDER BY departamento";
        return $this->executeQueryWithFilters($query, $filtros)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAaPorDept($filtros)
    {
        $query = "SELECT departamento, 
            SUM(total_aa_r) as aa_recursos,
            SUM(apa_f) as apa_fondos
            FROM visan_datos_asistencia {WHERE}
            GROUP BY departamento 
            ORDER BY departamento";
        return $this->executeQueryWithFilters($query, $filtros)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDepartamentos()
    {
        $stmt = $this->db->query("SELECT DISTINCT departamento FROM visan_datos_asistencia ORDER BY departamento");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMunicipios()
    {
        $stmt = $this->db->query("SELECT DISTINCT municipio FROM visan_datos_asistencia ORDER BY municipio");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDatosTabla($filtros)
    {
        $query = "SELECT * FROM visan_datos_asistencia {WHERE} ORDER BY departamento ASC, municipio ASC";
        return $this->executeQueryWithFilters($query, $filtros)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function upsertDatosImportacion($fila)
    {
        $departamento = $fila['departamento'];
        $municipio = $fila['municipio'];

        $stmtCheck = $this->db->prepare("SELECT id FROM visan_datos_asistencia WHERE departamento = :dep AND municipio = :mun LIMIT 1");
        $stmtCheck->execute(['dep' => $departamento, 'mun' => $municipio]);
        $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Update
            $id = $row['id'];
            $sql = "UPDATE visan_datos_asistencia SET 
                nda_severa_r = :nda_severa_r, nda_severa_f = :nda_severa_f,
                nda_nacional_r = :nda_nacional_r, nda_nacional_f = :nda_nacional_f,
                nda_plan_abordaje_r = :nda_plan_abordaje_r, nda_plan_abordaje_f = :nda_plan_abordaje_f,
                insan_r = :insan_r, insan_f = :insan_f,
                insan_emergencia_r = :insan_emergencia_r, insan_emergencia_f = :insan_emergencia_f,
                medida_transitoria_r = :medida_transitoria_r, medida_transitoria_f = :medida_transitoria_f,
                medida_cautelar_r = :medida_cautelar_r, medida_cautelar_f = :medida_cautelar_f,
                total_aa_r = :total_aa_r, total_aa_f = :total_aa_f,
                apa_f = :apa_f, apa_huertos = :apa_huertos,
                reserva_estrategica_r = :reserva_estrategica_r, reserva_estrategica_f = :reserva_estrategica_f,
                total_aa_apa = :total_aa_apa,
                fecha_actualizacion = NOW()
                WHERE id = :id";
            $fila['id'] = $id;
            $stmtUpdate = $this->db->prepare($sql);
            $stmtUpdate->execute($fila);
            return 'update';
        } else {
            // Insert
            $sql = "INSERT INTO visan_datos_asistencia (
                departamento, municipio, nda_severa_r, nda_severa_f, 
                nda_nacional_r, nda_nacional_f, nda_plan_abordaje_r, 
                nda_plan_abordaje_f, insan_r, insan_f, insan_emergencia_r, 
                insan_emergencia_f, medida_transitoria_r, medida_transitoria_f,
                medida_cautelar_r, medida_cautelar_f, total_aa_r, total_aa_f,
                apa_f, apa_huertos, reserva_estrategica_r, reserva_estrategica_f,
                total_aa_apa
            ) VALUES (
                :departamento, :municipio, :nda_severa_r, :nda_severa_f,
                :nda_nacional_r, :nda_nacional_f, :nda_plan_abordaje_r,
                :nda_plan_abordaje_f, :insan_r, :insan_f, :insan_emergencia_r,
                :insan_emergencia_f, :medida_transitoria_r, :medida_transitoria_f,
                :medida_cautelar_r, :medida_cautelar_f, :total_aa_r, :total_aa_f,
                :apa_f, :apa_huertos, :reserva_estrategica_r, :reserva_estrategica_f,
                :total_aa_apa
            )";
            $stmtInsert = $this->db->prepare($sql);
            $stmtInsert->execute($fila);
            return 'insert';
        }
    }

    public function getRegistroById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM visan_datos_asistencia WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateRegistro($id, $datos)
    {
        // En AsistenciaAlimentaria, EditarController actualiza un set limitado
        $sql = "UPDATE visan_datos_asistencia SET 
            nda_nacional_r = :nda_nacional_r, nda_nacional_f = :nda_nacional_f, insan_r = :insan_r, insan_f = :insan_f,
            medida_transitoria_r = :medida_transitoria_r, medida_transitoria_f = :medida_transitoria_f, 
            total_aa_r = :total_aa_r, total_aa_f = :total_aa_f,
            apa_f = :apa_f, total_aa_apa = :total_aa_apa,
            fecha_actualizacion = NOW() WHERE id = :id";
        
        $datos['id'] = $id;
        // Clean missing fields that might not be provided in limited update
        $fields = [
            'nda_nacional_r', 'nda_nacional_f', 'insan_r', 'insan_f',
            'medida_transitoria_r', 'medida_transitoria_f', 'total_aa_r', 'total_aa_f',
            'apa_f', 'total_aa_apa', 'id'
        ];
        $params = [];
        foreach ($fields as $f) {
            $params[$f] = $datos[$f] ?? 0;
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function registrarCambio($registro_id, $departamento, $municipio, $campo, $valor_anterior, $valor_nuevo, $observacion = '')
    {
        if ($valor_anterior != $valor_nuevo) {
            $sql = "INSERT INTO visan_historial_cambios 
                    (registro_id, departamento, municipio, campo_modificado, valor_anterior, valor_nuevo, observacion_cambio) 
                    VALUES (:reg, :dep, :mun, :campo, :old, :new, :obs)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'reg' => $registro_id,
                'dep' => $departamento,
                'mun' => $municipio,
                'campo' => $campo,
                'old' => $valor_anterior,
                'new' => $valor_nuevo,
                'obs' => $observacion
            ]);
        }
        return true;
    }

    // Historial
    private function buildHistorialWhereClause($filtros)
    {
        $conditions = [];
        $params = [];

        if (!empty($filtros['fecha_inicio'])) {
            $conditions[] = "DATE(fecha_cambio) >= :fecha_inicio";
            $params['fecha_inicio'] = $filtros['fecha_inicio'];
        }
        if (!empty($filtros['fecha_fin'])) {
            $conditions[] = "DATE(fecha_cambio) <= :fecha_fin";
            $params['fecha_fin'] = $filtros['fecha_fin'];
        }
        if (!empty($filtros['departamento'])) {
            $conditions[] = "departamento = :departamento";
            $params['departamento'] = $filtros['departamento'];
        }
        if (!empty($filtros['municipio'])) {
            $conditions[] = "municipio = :municipio";
            $params['municipio'] = $filtros['municipio'];
        }
        if (!empty($filtros['campo'])) {
            $conditions[] = "campo_modificado = :campo";
            $params['campo'] = $filtros['campo'];
        }
        if (!empty($filtros['registro_id'])) {
            $conditions[] = "registro_id = :registro_id";
            $params['registro_id'] = $filtros['registro_id'];
        }

        $clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return ['clause' => $clause, 'params' => $params];
    }

    private function executeHistorialQueryWithFilters($query, $filtros)
    {
        $where = $this->buildHistorialWhereClause($filtros);
        $fullQuery = str_replace('{WHERE}', $where['clause'], $query);
        $stmt = $this->db->prepare($fullQuery);
        $stmt->execute($where['params']);
        return $stmt;
    }

    public function getHistorialStats($filtros)
    {
        $query = "SELECT 
            COUNT(*) as total_cambios,
            COUNT(DISTINCT registro_id) as registros_modificados,
            COUNT(DISTINCT departamento) as departamentos_afectados,
            COUNT(DISTINCT DATE(fecha_cambio)) as dias_con_cambios,
            MIN(fecha_cambio) as primer_cambio,
            MAX(fecha_cambio) as ultimo_cambio
        FROM visan_historial_cambios {WHERE}";

        $stmt = $this->executeHistorialQueryWithFilters($query, $filtros);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: [
            'total_cambios' => 0, 'registros_modificados' => 0,
            'departamentos_afectados' => 0, 'dias_con_cambios' => 0,
            'primer_cambio' => null, 'ultimo_cambio' => null
        ];
    }

    public function getCamposFrecuentes($filtros)
    {
        $query = "SELECT campo_modificado, COUNT(*) as total_cambios
            FROM visan_historial_cambios {WHERE}
            GROUP BY campo_modificado
            ORDER BY total_cambios DESC LIMIT 10";
        return $this->executeHistorialQueryWithFilters($query, $filtros)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHistorial($filtros)
    {
        $query = "SELECT * FROM visan_historial_cambios {WHERE} ORDER BY fecha_cambio DESC LIMIT 1000";
        return $this->executeHistorialQueryWithFilters($query, $filtros)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHistorialFiltrosOpciones()
    {
        $deps = $this->db->query("SELECT DISTINCT departamento FROM visan_historial_cambios WHERE departamento IS NOT NULL AND departamento != '' ORDER BY departamento")->fetchAll(PDO::FETCH_ASSOC);
        $muns = $this->db->query("SELECT DISTINCT municipio FROM visan_historial_cambios WHERE municipio IS NOT NULL AND municipio != '' ORDER BY municipio")->fetchAll(PDO::FETCH_ASSOC);
        $campos = $this->db->query("SELECT DISTINCT campo_modificado FROM visan_historial_cambios WHERE campo_modificado IS NOT NULL AND campo_modificado != '' ORDER BY campo_modificado")->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'departamentos' => $deps,
            'municipios' => $muns,
            'campos' => $campos
        ];
    }
}
