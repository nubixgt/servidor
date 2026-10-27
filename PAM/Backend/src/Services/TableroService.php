<?php
namespace App\Services;

use App\Entities\Usuario;
use App\Repositories\TableroRepository;
use App\Utils\Response;

/**
 * Arma el cubo del tablero (el cliente lo filtra y agrega) a partir de los agregados mart_*.
 * El resultado se guarda comprimido en disco, por ámbito y versión de los agregados.
 */
class TableroService
{
    private const AB = ['65–69', '70–74', '75–79', '80–84', '85–89', '90+'];
    private const DIMS = ['m', 'e', 'a', 'y', 'r', 'v', 's', 'f', 'n', 't'];
    private const MEAS = ['c', 'p', 'qd', 'qu', 'qx', 'qf', 'qe', 'qa', 'qs', 'qm'];
    private const SERIES = ['sol', 'vpos', 'vneg', 'res', 'ing', 'sal', 'susp', 'fall'];
    private const Y0 = 2006;

    private $repository;

    public function __construct()
    {
        $this->repository = new TableroRepository();
    }

    /** @return array{etag: string, gz: string} */
    public function datos(Usuario $u): array
    {
        [$wsql, $wp, $clave] = $this->ambito($u);
        [$ver, $refrescado] = $this->repository->version();
        $version = md5($ver . '|' . $refrescado);
        $etag = '"' . $clave . '-' . substr($version, 0, 16) . '"';

        $dir = sys_get_temp_dir();
        $archivo = "$dir/pam_tablero_{$clave}_$version.json.gz";
        if (is_readable($archivo)) {
            return ['etag' => $etag, 'gz' => file_get_contents($archivo)];
        }

        $json = json_encode($this->construir($wsql, $wp, $clave, $refrescado), Response::FLAGS);
        $gz = gzencode($json, 6);
        foreach (glob("$dir/pam_tablero_{$clave}_*.json.gz") ?: [] as $viejo) {
            @unlink($viejo);
        }
        file_put_contents($archivo, $gz, LOCK_EX);
        return ['etag' => $etag, 'gz' => $gz];
    }

    private function ambito(Usuario $u): array
    {
        if ($u->municipioId) {
            return [' AND x.municipio_id = ?', [$u->municipioId], "m{$u->municipioId}"];
        }
        if ($u->departamentoId) {
            return [' AND (x.municipio_id DIV 100) = ?', [$u->departamentoId], "d{$u->departamentoId}"];
        }
        return ['', [], 'nac'];
    }

    private function construir(string $wsql, array $wp, string $clave, ?string $refrescado): array
    {
        $r = $this->repository;
        $mun = $r->municipiosEnCubo($wsql, $wp);
        $es = $r->nombresEstados();
        $rg = array_merge($r->nombresFamilias(), ['Sin resolución']);
        $ys = $r->anios($wsql, $wp);

        $midx = [];
        foreach ($mun as $i => $m) {
            $midx[(int) $m['id']] = $i;
        }
        $yidx = array_flip($ys);

        $cols = array_fill_keys(array_merge(self::DIMS, self::MEAS), []);
        $stmt = $r->cubo($wsql, $wp);
        while ($row = $stmt->fetch()) {
            $cols['m'][] = $midx[(int) $row['municipio_id']];
            $cols['e'][] = (int) $row['e'];
            $cols['a'][] = (int) $row['a'];
            $cols['y'][] = $yidx[(int) $row['anio']];
            $cols['r'][] = (int) $row['r'];
            $cols['v'][] = (int) $row['v'];
            $cols['s'][] = (int) $row['s'];
            $cols['f'][] = (int) $row['f'];
            $cols['n'][] = (int) $row['n'];
            $cols['t'][] = (int) $row['t'];
            foreach (self::MEAS as $med) {
                $cols[$med][] = (int) $row[$med];
            }
        }
        $cubo = [
            'dims' => self::DIMS, 'meas' => self::MEAS, 'cols' => $cols,
            'MU' => array_column($mun, 'codigo'), 'MN' => array_column($mun, 'nombre'),
            'ES' => $es, 'AB' => self::AB,
            'YS' => array_map(fn($y) => $y ? (string) $y : 's/f', $ys), 'RG' => $rg,
        ];

        $yr = array_fill_keys(self::SERIES, []);
        $mo = array_fill_keys(self::SERIES, []);
        foreach ($r->serieAnual($wsql, $wp) as $row) {
            if (isset($midx[(int) $row['municipio_id']]) && isset($yr[$row['tipo']])) {
                $yr[$row['tipo']][] = [$midx[(int) $row['municipio_id']], (int) $row['e'], (int) $row['anio'] - self::Y0, (int) $row['n']];
            }
        }
        foreach ($r->serieMensual($wsql, $wp) as $row) {
            if (isset($mo[$row['tipo']])) {
                $mo[$row['tipo']][] = [(int) $row['dg'], (int) $row['e'], (int) $row['mi'], (int) $row['n']];
            }
        }

        $tm = ['all' => [], 'rec' => []];
        foreach ($r->tiempos() as $row) {
            $dep = (int) $row['dep'] === 0 ? 'N' : (string) ((int) $row['dep'] - 1);
            $tm[$row['cohorte']][$row['tramo']][$dep] = [(int) $row['n'], (int) $row['media'], (int) $row['mediana'], (int) $row['p90']];
        }
        // objetos vacíos como {} y no como []
        foreach ($tm as $cohorte => $tramos) {
            $tm[$cohorte] = (object) array_map(fn($t) => (object) $t, $tramos);
        }

        return [
            'cubo' => $cubo,
            'series' => ['Y0' => self::Y0, 'yr' => $yr, 'mo' => $mo, 'tm' => $tm, 'cohorte_desde' => $r->cohorteReciente()],
            'meta' => ['refrescado_en' => $refrescado, 'fecha_corte' => $r->fechaCorte(), 'ambito' => $clave],
        ];
    }
}
