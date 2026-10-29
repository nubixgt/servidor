<?php
namespace App\Repositories;

use App\Utils\Database;
use PDO;
use PDOStatement;

/**
 * Consultas de los reportes en Excel (geográfico y por DPI) sobre la vista core_v_expediente.
 * Solo lectura: no escribe en ninguna tabla.
 */
class ReporteGeograficoRepository
{
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

    /** @return array<int, array{id: int, departamento_id: int, nombre: string}> municipios existentes, indexados por id */
    public function municipios(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $out = [];
        foreach ($this->db->all("SELECT id, departamento_id, nombre FROM ref_municipio WHERE id IN ($marcas)", array_values($ids)) as $r) {
            $out[(int) $r['id']] = $r;
        }
        return $out;
    }

    public function nombreDepartamento(int $id): ?string
    {
        return $this->db->scalar('SELECT nombre FROM ref_departamento WHERE id = ?', [$id]);
    }

    public function totalDepartamentos(): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM ref_departamento');
    }

    public function totalMunicipiosDe(?int $departamentoId): int
    {
        return $departamentoId === null
            ? (int) $this->db->scalar('SELECT COUNT(*) FROM ref_municipio')
            : (int) $this->db->scalar('SELECT COUNT(*) FROM ref_municipio WHERE departamento_id = ?', [$departamentoId]);
    }

    /** Indicadores de la consulta (sin duplicidades: cuenta por expediente y por persona). */
    public function resumen(string $where, array $params): array
    {
        return $this->db->one(
            "SELECT COUNT(*) AS registros, COUNT(DISTINCT v.persona_id) AS beneficiarios, COUNT(DISTINCT v.id) AS expedientes,
                    COUNT(DISTINCT v.departamento_id) AS departamentos, COUNT(DISTINCT v.municipio_id) AS municipios
             FROM core_v_expediente v WHERE 1=1 $where",
            $params
        ) ?? ['registros' => 0, 'beneficiarios' => 0, 'expedientes' => 0, 'departamentos' => 0, 'municipios' => 0];
    }

    public function contar(string $where, array $params, ?int $tope = null): int
    {
        if ($tope !== null) {
            return (int) $this->db->scalar("SELECT COUNT(*) FROM (SELECT 1 FROM core_v_expediente v WHERE 1=1 $where LIMIT $tope) x", $params);
        }
        return (int) $this->db->scalar("SELECT COUNT(*) FROM core_v_expediente v WHERE 1=1 $where", $params);
    }

    /**
     * Filas con las columnas ya traducidas a SQL. Con $sinBuffer se leen una a una (exportaciones grandes):
     * quien lo use debe llamar a restaurarBuffer() al terminar.
     */
    public function filas(string $select, string $joins, string $where, array $params, int $limite, int $desplazamiento = 0, bool $sinBuffer = false): PDOStatement
    {
        if ($sinBuffer) {
            $this->db->getConnection()->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        }
        return $this->db->run(
            "SELECT $select FROM core_v_expediente v $joins WHERE 1=1 $where
             ORDER BY v.departamento_id, v.municipio_id, v.nombre_norm, v.id LIMIT ? OFFSET ?",
            array_merge($params, [$limite, $desplazamiento])
        );
    }

    /**
     * Expedientes de un lote de DPI. El orden es estable para que el reporte sea reproducible.
     * @param string[] $dpis
     */
    public function filasPorDpi(string $select, string $joins, array $dpis, string $ambito, array $ambitoParams): array
    {
        $marcas = implode(',', array_fill(0, count($dpis), '?'));
        return $this->db->all(
            "SELECT v.documento_numero AS _dpi, $select FROM core_v_expediente v $joins
             WHERE v.documento_numero IN ($marcas) $ambito ORDER BY v.departamento_id, v.municipio_id, v.nombre_norm, v.id",
            array_merge(array_values($dpis), $ambitoParams)
        );
    }

    /**
     * Cuántos expedientes tiene cada DPI de un lote (solo los encontrados).
     * @param string[] $dpis
     * @return array<string, int>
     */
    public function contarPorDpi(array $dpis, string $ambito, array $ambitoParams): array
    {
        $marcas = implode(',', array_fill(0, count($dpis), '?'));
        $out = [];
        foreach ($this->db->all(
            "SELECT v.documento_numero AS d, COUNT(*) AS n FROM core_v_expediente v WHERE v.documento_numero IN ($marcas) $ambito GROUP BY v.documento_numero",
            array_merge(array_values($dpis), $ambitoParams)
        ) as $r) {
            $out[(string) $r['d']] = (int) $r['n'];
        }
        return $out;
    }

    /** Bitácora de los reportes (módulo «reportes»). */
    public function bitacora(array $f, int $limite): array
    {
        $w = ["b.modulo = 'reportes'", "b.accion = 'EXPORTAR'"];
        $p = [];
        $entidades = ['dpi' => 'reporte_dpi', 'geografico' => 'reporte_geografico', 'tablero' => 'reporte'];
        if (isset($f['modalidad'])) {
            $w[] = 'b.entidad = ?';
            $p[] = $entidades[$f['modalidad']];
        }
        if (isset($f['usuario'])) {
            $w[] = 'b.usuario LIKE ?';
            $p[] = '%' . addcslashes($f['usuario'], '%_\\') . '%';
        }
        if (isset($f['estado'])) {
            $w[] = "JSON_UNQUOTE(JSON_EXTRACT(b.despues, '$.estado')) = ?";
            $p[] = $f['estado'];
        }
        if (isset($f['ubicacion'])) {
            $w[] = 'b.despues LIKE ?';
            $p[] = '%' . addcslashes($f['ubicacion'], '%_\\') . '%';
        }
        foreach (['desde' => 'b.ts >= ?', 'hasta' => 'b.ts < DATE_ADD(?, INTERVAL 1 DAY)', 'antes_de' => 'b.id < ?'] as $k => $cond) {
            if (isset($f[$k])) {
                $w[] = $cond;
                $p[] = $f[$k];
            }
        }
        $filas = $this->db->all(
            'SELECT b.id, b.ts, b.usuario, b.entidad, b.despues, b.ip FROM audit_bitacora b WHERE ' . implode(' AND ', $w) . ' ORDER BY b.id DESC LIMIT ?',
            array_merge($p, [$limite])
        );
        foreach ($filas as &$r) {
            $r['despues'] = $r['despues'] === null ? null : json_decode($r['despues'], true);
        }
        return $filas;
    }

    public function restaurarBuffer(): void
    {
        $this->db->getConnection()->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }
}
