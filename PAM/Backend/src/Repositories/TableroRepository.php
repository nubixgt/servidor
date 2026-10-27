<?php
namespace App\Repositories;

use App\Utils\Database;

/**
 * Lee solo los agregados mart_*, nunca el detalle.
 */
class TableroRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function version(): array
    {
        return [
            (string) ($this->db->scalar("SELECT valor FROM mart_meta WHERE clave = 'cache_version'") ?? '0'),
            $this->db->scalar("SELECT valor FROM mart_meta WHERE clave = 'refrescado_en'"),
        ];
    }

    public function municipiosEnCubo(string $wsql, array $wp): array
    {
        $ids = array_map('intval', array_column(
            $this->db->all("SELECT DISTINCT x.municipio_id FROM mart_cubo x WHERE 1=1 $wsql ORDER BY 1", $wp), 'municipio_id'));
        if (!$ids) {
            return [];
        }
        return $this->db->all('SELECT id, codigo, nombre FROM ref_municipio WHERE id IN (' . implode(',', $ids) . ') ORDER BY id');
    }

    public function nombresEstados(): array
    {
        return array_column($this->db->all('SELECT nombre FROM ref_cat_estado ORDER BY orden'), 'nombre');
    }

    public function nombresFamilias(): array
    {
        return array_column($this->db->all('SELECT nombre FROM ref_cat_familia_resolucion ORDER BY orden'), 'nombre');
    }

    public function anios(string $wsql, array $wp): array
    {
        return array_map('intval', array_column($this->db->all("SELECT DISTINCT x.anio FROM mart_cubo x WHERE 1=1 $wsql ORDER BY 1", $wp), 'anio'));
    }

    public function cubo(string $wsql, array $wp): \PDOStatement
    {
        return $this->db->run("SELECT x.* FROM mart_cubo x WHERE 1=1 $wsql", $wp);
    }

    public function serieAnual(string $wsql, array $wp): array
    {
        return $this->db->all(
            "SELECT x.municipio_id, x.e, x.tipo, YEAR(x.mes) AS anio, SUM(x.n) AS n
             FROM mart_serie x WHERE 1=1 $wsql GROUP BY x.municipio_id, x.e, x.tipo, YEAR(x.mes)", $wp);
    }

    public function serieMensual(string $wsql, array $wp): array
    {
        return $this->db->all(
            "SELECT (x.municipio_id DIV 100) - 1 AS dg, x.e, x.tipo,
                    (YEAR(x.mes) - 2018) * 12 + MONTH(x.mes) - 1 AS mi, SUM(x.n) AS n
             FROM mart_serie x WHERE x.mes >= '2018-01-01' $wsql
             GROUP BY (x.municipio_id DIV 100) - 1, x.e, x.tipo, (YEAR(x.mes) - 2018) * 12 + MONTH(x.mes) - 1", $wp);
    }

    public function tiempos(): array
    {
        return $this->db->all('SELECT dep, cohorte, tramo, n, media, mediana, p90 FROM mart_tiempos');
    }

    public function cohorteReciente(): ?string
    {
        return $this->db->scalar("SELECT JSON_UNQUOTE(valor) FROM ref_parametro WHERE clave = 'cohorte_reciente_desde'");
    }

    public function fechaCorte(): ?string
    {
        return $this->db->scalar('SELECT MAX(fecha_estado) FROM core_expediente WHERE fecha_estado <= CURDATE()');
    }
}
