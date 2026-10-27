<?php
namespace App\Repositories;

use App\DTOs\FiltrosDTO;
use App\Utils\Database;
use PDO;
use PDOStatement;

class ReporteRepository
{
    private const EST = '(SELECT nombre FROM ref_cat_estado WHERE orden = x.e)';
    private const DEP = 'JOIN ref_departamento d ON d.id = (x.municipio_id DIV 100)';
    private const COLS_RES = "SUM(x.c) AS registros, SUM(x.p) AS personas,
        SUM(CASE WHEN x.n = 1 THEN x.c ELSE 0 END) AS en_nomina, SUM(CASE WHEN x.e = 1 THEN x.c ELSE 0 END) AS en_tramite,
        SUM(CASE WHEN x.v = 1 THEN x.c ELSE 0 END) AS visita_positiva, SUM(CASE WHEN x.v = 2 THEN x.c ELSE 0 END) AS visita_negativa,
        SUM(CASE WHEN x.e = 4 THEN x.c ELSE 0 END) AS suspendidos, SUM(CASE WHEN x.f = 1 THEN x.c ELSE 0 END) AS fallecidos";

    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function limiteDetalle(): array
    {
        $v = $this->db->scalar("SELECT valor FROM ref_parametro WHERE clave = 'export_max_filas_detalle'");
        return $v ? (json_decode($v, true) ?: []) : [];
    }

    /** Reportes agregados (leen el cubo). Devuelve el cursor de la consulta. */
    public function agregado(string $tipo, string $aw, array $ap, FiltrosDTO $fl): ?PDOStatement
    {
        [$cw, $cp] = $this->filtrosCubo($fl);
        $W = "WHERE 1=1 $aw $cw";
        $P = array_merge($ap, $cp);
        $dep = self::DEP;
        $est = self::EST;
        $cols = self::COLS_RES;

        $sql = match ($tipo) {
            'resumen_departamental' => "SELECT d.nombre AS departamento, $cols FROM mart_cubo x $dep $W GROUP BY d.id, d.nombre ORDER BY d.id",
            'resumen_municipal' => "SELECT d.nombre AS departamento, m.codigo, m.nombre AS municipio, $cols FROM mart_cubo x $dep
                JOIN ref_municipio m ON m.id = x.municipio_id $W GROUP BY d.id, d.nombre, m.id, m.codigo, m.nombre ORDER BY m.id",
            'estados' => "SELECT d.nombre AS departamento, $est AS estado, SUM(x.c) AS registros FROM mart_cubo x $dep $W GROUP BY d.id, d.nombre, x.e ORDER BY d.id, x.e",
            'edad' => "SELECT ELT(x.a + 1, '65-69', '70-74', '75-79', '80-84', '85-89', '90+') AS rango_edad, $est AS estado, SUM(x.c) AS registros
                FROM mart_cubo x $W GROUP BY x.a, x.e ORDER BY x.a, x.e",
            'antiguedad' => "SELECT d.nombre AS departamento, ELT(x.t, '0-30', '31-60', '61-90', '91-180', '181-365', 'Más de 365') AS dias_en_tramite, SUM(x.c) AS expedientes
                FROM mart_cubo x $dep $W AND x.t > 0 GROUP BY d.id, d.nombre, x.t ORDER BY d.id, x.t",
            'resoluciones' => "SELECT d.nombre AS departamento, COALESCE((SELECT nombre FROM ref_cat_familia_resolucion WHERE orden = x.r), 'Sin resolución') AS familia, SUM(x.c) AS registros
                FROM mart_cubo x $dep $W GROUP BY d.id, d.nombre, x.r ORDER BY d.id, x.r",
            'calidad' => "SELECT d.nombre AS departamento, SUM(x.c) AS registros, SUM(x.qd) AS documento_sin_cui_valido, SUM(x.qu) AS dpi_repetido, SUM(x.qx) AS expediente_repetido,
                SUM(x.qf) AS fechas_inconsistentes, SUM(x.qe) AS edad_fuera_de_rango, SUM(x.qa) AS fallecido_activo, SUM(x.qs) AS suspendido_activo, SUM(x.qm) AS sin_telefono
                FROM mart_cubo x $dep $W GROUP BY d.id, d.nombre ORDER BY d.id",
            default => null,
        };
        return $sql === null ? null : $this->db->run($sql, $P);
    }

    public function serieMensual(string $aw, array $ap, FiltrosDTO $fl): PDOStatement
    {
        $sw = $aw;
        $sp = $ap;
        if ($fl->dep !== null) {
            $sw .= ' AND (x.municipio_id DIV 100) = ?';
            $sp[] = $fl->dep;
        }
        if ($fl->mun !== null) {
            $sw .= ' AND x.municipio_id = ?';
            $sp[] = $fl->mun;
        }
        if ($fl->e !== null) {
            $sw .= ' AND x.e = ?';
            $sp[] = $fl->e;
        }
        $suma = fn(string $tipo, string $alias) => "SUM(CASE WHEN x.tipo = '$tipo' THEN x.n ELSE 0 END) AS $alias";
        $sql = "SELECT DATE_FORMAT(x.mes, '%Y-%m') AS mes, " . implode(', ', [
            $suma('sol', 'solicitudes'), $suma('vpos', 'visitas_positivas'), $suma('vneg', 'visitas_negativas'), $suma('res', 'resoluciones'),
            $suma('ing', 'ingresos_nomina'), $suma('sal', 'salidas_nomina'), $suma('susp', 'suspensiones'), $suma('fall', 'fallecimientos'),
        ]) . " FROM mart_serie x WHERE 1=1 $sw GROUP BY x.mes ORDER BY x.mes";
        return $this->db->run($sql, $sp);
    }

    public function contarListado(string $where, array $params, int $tope): int
    {
        return (int) $this->db->scalar("SELECT COUNT(*) FROM (SELECT 1 FROM core_v_expediente v WHERE 1=1 $where LIMIT $tope) x", $params);
    }

    /** Listado con datos personales. Sin buffer: se va leyendo fila a fila para no llenar la memoria. */
    public function listado(string $where, array $params, int $limite): PDOStatement
    {
        $this->db->getConnection()->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        return $this->db->run(
            "SELECT v.numero AS expediente, v.nombre, v.documento_numero AS dpi, v.fecha_nacimiento, v.edad, v.departamento, v.municipio,
                    v.telefono, v.direccion, v.fecha_solicitud, v.estado, v.fecha_estado,
                    CASE v.situacion_nomina WHEN 1 THEN 'Activo' WHEN 2 THEN 'Egresado' ELSE 'Nunca ingresó' END AS nomina,
                    v.fecha_nomina_desde, v.fecha_nomina_hasta, v.ult_visita_fecha, v.ult_resolucion_codigo, v.ult_resolucion_fecha,
                    v.fecha_suspension, v.fecha_fallecimiento, v.ult_observacion
             FROM core_v_expediente v WHERE 1=1 $where ORDER BY v.departamento_id, v.municipio_id, v.nombre_norm LIMIT ?",
            array_merge($params, [$limite])
        );
    }

    public function restaurarBuffer(): void
    {
        $this->db->getConnection()->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }

    private function filtrosCubo(FiltrosDTO $fl): array
    {
        $w = [];
        $p = [];
        if ($fl->dep !== null) {
            $w[] = '(x.municipio_id DIV 100) = ?';
            $p[] = $fl->dep;
        }
        if ($fl->mun !== null) {
            $w[] = 'x.municipio_id = ?';
            $p[] = $fl->mun;
        }
        if ($fl->anio !== null) {
            $w[] = 'x.anio = ?';
            $p[] = $fl->anio;
        }
        foreach (['e', 'r', 'n', 'v', 's', 'f', 'a', 't'] as $k) {
            if ($fl->$k !== null) {
                $w[] = "x.$k = ?";
                $p[] = $fl->$k;
            }
        }
        if ($fl->am !== null) {
            $w[] = 'x.a >= ?';
            $p[] = $fl->am;
        }
        return [implode('', array_map(fn($c) => " AND $c", $w)), $p];
    }
}
