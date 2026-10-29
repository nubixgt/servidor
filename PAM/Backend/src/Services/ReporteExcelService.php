<?php
namespace App\Services;

use App\Core\HttpException;
use App\DTOs\FiltrosDTO;
use App\Entities\Usuario;
use App\Repositories\ReporteGeograficoRepository;
use App\Utils\Security;
use App\Utils\Validator;
use App\Utils\XlsxReader;
use App\Utils\XlsxWriter;
use PDO;

/**
 * Reportes en Excel: por departamento/municipio y por DPI (carga de .xlsx).
 * Solo consulta; respeta el ámbito territorial y los permisos del usuario y deja cada descarga en la bitácora.
 */
class ReporteExcelService
{
    public const TAM_PAGINA = 25;
    private const MAX_DPI = 200000;
    private const MAX_UPLOAD = 20 * 1024 * 1024;
    private const LOTE_DPI = 1000;
    private const MAX_MUNICIPIOS = 400;

    private $repository;
    private $filtros;
    private $auditoria;

    public function __construct()
    {
        $this->repository = new ReporteGeograficoRepository();
        $this->filtros = new FiltroService();
        $this->auditoria = new AuditoriaService();
    }

    public function columnas(Usuario $u): array
    {
        return [
            'categorias' => ReporteColumnas::catalogo(),
            'predeterminadas' => ReporteColumnas::PREDETERMINADAS,
            'dpi_completo' => $u->puede('beneficiarios.ver_dpi'),
        ];
    }

    // ================================================================================== geográfico
    public function resumen(Usuario $u, array $b): array
    {
        $t = $this->territorio($u, $b);
        $r = $this->repository->resumen($t['where'], $t['params']);
        return [
            'registros' => (int) $r['registros'],
            'beneficiarios' => (int) $r['beneficiarios'],
            'expedientes' => (int) $r['expedientes'],
            'departamentos' => (int) $r['departamentos'],
            'municipios' => (int) $r['municipios'],
            'departamentos_seleccionados' => $t['deps_sel'],
            'municipios_seleccionados' => $t['muns_sel'],
            'limite' => $this->limite($u),
        ];
    }

    public function previa(Usuario $u, array $b): array
    {
        $t = $this->territorio($u, $b);
        $cols = ReporteColumnas::validar($b['columnas'] ?? null);
        $where = $t['where'];
        $params = $t['params'];
        $q = trim((string) ($b['q'] ?? ''));
        if ($q !== '') {
            [$w, $p] = $this->filtros->where(FiltrosDTO::fromRequest(['q' => mb_substr($q, 0, 100)]));
            $where .= $w;
            $params = array_merge($params, $p);
        }
        $pagina = max(0, min(2000, (int) ($b['pagina'] ?? 0)));
        $total = $this->repository->contar($where, $params);
        [$sel, $joins] = ReporteColumnas::sql($cols);
        $stmt = $this->repository->filas($sel, $joins, $where, $params, self::TAM_PAGINA, $pagina * self::TAM_PAGINA);
        $filas = [];
        foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $f) {
            $filas[] = $this->enmascarar($u, $cols, $f);
        }
        return [
            'columnas' => ReporteColumnas::etiquetas($cols),
            'filas' => $filas,
            'total' => $total,
            'pagina' => $pagina,
            'tam_pagina' => self::TAM_PAGINA,
        ];
    }

    /**
     * Genera el Excel en un archivo temporal. El llamador lo entrega y lo borra.
     * @return array{ruta: string, nombre: string, auditoria: array}
     */
    public function exportarGeografico(Usuario $u, array $b): array
    {
        $this->exigirExportar($u);
        $nombre = 'DISI_PAM_Reporte_Geografico_' . date('Ymd_Hi') . '.xlsx';
        $detalle = ['modalidad' => 'Departamento y Municipio', 'archivo' => $nombre];
        try {
            $cols = ReporteColumnas::validar($b['columnas'] ?? null);
            $t = $this->territorio($u, $b);
            $detalle += $t['auditoria'] + ['columnas' => ReporteColumnas::etiquetas($cols)];

            $limite = $this->limite($u);
            $total = $this->repository->contar($t['where'], $t['params'], $limite + 1);
            if ($total > $limite) {
                throw new HttpException('La consulta supera el máximo de ' . number_format($limite, 0, ',', '.') . ' filas por exportación para su rol. Reduzca los municipios seleccionados.', 413);
            }

            [$sel, $joins] = ReporteColumnas::sql($cols);
            $w = new XlsxWriter($this->dirTemporal());
            $info = $w->hoja('Información del reporte', ['Campo', 'Valor'], [34, 100], false, true);
            $g = $w->grupo('Registros', ReporteColumnas::etiquetas($cols), ReporteColumnas::anchos($cols));

            $n = 0;
            $stmt = $this->repository->filas($sel, $joins, $t['where'], $t['params'], $limite, 0, true);
            try {
                while ($f = $stmt->fetch(PDO::FETCH_NUM)) {
                    $w->filaGrupo($g, $this->enmascarar($u, $cols, $f));
                    $n++;
                }
            } finally {
                $this->repository->restaurarBuffer();
            }

            foreach ([
                ['Fecha y hora de generación', date('d/m/Y H:i:s')],
                ['Usuario responsable', "{$u->nombre} ({$u->usuario}) · {$u->rolNombre}"],
                ['Departamento(s) seleccionado(s)', $t['etiqueta_departamentos']],
                ['Municipios incluidos', $t['etiqueta_municipios']],
                ['Cantidad total de registros exportados', $n],
                ['Columnas seleccionadas', implode(', ', ReporteColumnas::etiquetas($cols))],
                ['Criterios utilizados', 'Reporte por departamento y municipio sobre los expedientes del sistema'
                    . ($u->departamentoId || $u->municipioId ? ' (limitado al ámbito territorial del usuario)' : '')
                    . '. DPI ' . ($u->puede('beneficiarios.ver_dpi') ? 'completo.' : 'enmascarado por permisos del usuario.')],
            ] as [$campo, $valor]) {
                $w->fila($info, [$campo, $valor]);
            }
            $ruta = $this->dirTemporal() . '/reporte_' . bin2hex(random_bytes(8)) . '.xlsx';
            $w->guardar($ruta);
        } catch (\Throwable $e) {
            $this->registrar($u, 'reporte_geografico', 'ERROR', $detalle + ['error' => $e->getMessage()]);
            throw $e;
        }
        return ['ruta' => $ruta, 'nombre' => $nombre, 'auditoria' => $detalle + ['filas' => $n]];
    }

    // ================================================================================== por DPI
    public function previaDpi(Usuario $u, ?array $archivo, array $columnas): array
    {
        $cols = ReporteColumnas::validar($columnas);
        $lectura = $this->leerDpi($archivo);
        [$ambito, $ap] = $this->filtros->ambito($u);
        $encontrados = [];
        $registros = 0;
        foreach (array_chunk($lectura['dpis'], self::LOTE_DPI) as $lote) {
            foreach ($this->repository->contarPorDpi($lote, $ambito, $ap) as $dpi => $n) {
                $encontrados[$dpi] = true;
                $registros += $n;
            }
        }
        [$sel, $joins] = ReporteColumnas::sql($cols);
        $muestra = [];
        foreach (array_chunk($lectura['dpis'], 100) as $lote) {
            foreach ($this->repository->filasPorDpi($sel, $joins, $lote, $ambito, $ap) as $f) {
                unset($f['_dpi']);
                $muestra[] = $this->enmascarar($u, $cols, array_values($f));
                if (count($muestra) >= self::TAM_PAGINA) {
                    break 2;
                }
            }
        }
        return [
            'dpi_cargados' => $lectura['filas'] - $lectura['vacios'],
            'dpi_unicos' => count($lectura['dpis']),
            'dpi_repetidos' => $lectura['repetidos'],
            'filas_sin_dpi' => $lectura['vacios'],
            'dpi_encontrados' => count($encontrados),
            'dpi_no_encontrados' => count($lectura['dpis']) - count($encontrados),
            'registros' => $registros,
            'columnas' => ReporteColumnas::etiquetas($cols),
            'filas' => $muestra,
            'limite' => $this->limite($u),
        ];
    }

    /** @return array{ruta: string, nombre: string, auditoria: array} */
    public function exportarDpi(Usuario $u, ?array $archivo, array $columnas): array
    {
        $this->exigirExportar($u);
        $nombre = 'DISI_PAM_Reporte_DPI_' . date('Ymd_Hi') . '.xlsx';
        $detalle = ['modalidad' => 'DPI', 'archivo' => $nombre, 'archivo_origen' => mb_substr((string) ($archivo['name'] ?? ''), 0, 120)];
        try {
            $cols = ReporteColumnas::validar($columnas);
            $detalle['columnas'] = ReporteColumnas::etiquetas($cols);
            $lectura = $this->leerDpi($archivo);
            [$ambito, $ap] = $this->filtros->ambito($u);
            [$sel, $joins] = ReporteColumnas::sql($cols);
            $limite = $this->limite($u);

            $w = new XlsxWriter($this->dirTemporal());
            $info = $w->hoja('Información del reporte', ['Campo', 'Valor'], [34, 100], false, true);
            $g = $w->grupo('Registros', ReporteColumnas::etiquetas($cols), ReporteColumnas::anchos($cols));
            $noEnc = $w->hoja('DPI no encontrados', ['DPI'], [22], true);

            $encontrados = [];
            $n = 0;
            foreach (array_chunk($lectura['dpis'], self::LOTE_DPI) as $lote) {
                foreach ($this->repository->filasPorDpi($sel, $joins, $lote, $ambito, $ap) as $f) {
                    $encontrados[$f['_dpi']] = true;
                    unset($f['_dpi']);
                    if (++$n > $limite) {
                        throw new HttpException('El resultado supera el máximo de ' . number_format($limite, 0, ',', '.') . ' filas por exportación para su rol. Divida el archivo de DPI.', 413);
                    }
                    $w->filaGrupo($g, $this->enmascarar($u, $cols, array_values($f)));
                }
            }
            $faltan = 0;
            foreach ($lectura['dpis'] as $dpi) {
                if (!isset($encontrados[$dpi])) {
                    $w->fila($noEnc, [$dpi]);
                    $faltan++;
                }
            }
            $detalle += ['dpi_unicos' => count($lectura['dpis']), 'dpi_no_encontrados' => $faltan];

            foreach ([
                ['Fecha y hora de generación', date('d/m/Y H:i:s')],
                ['Usuario responsable', "{$u->nombre} ({$u->usuario}) · {$u->rolNombre}"],
                ['Modalidad', 'Reporte por DPI (carga de archivo Excel)'],
                ['Archivo cargado', $detalle['archivo_origen']],
                ['DPI únicos en el archivo', count($lectura['dpis'])],
                ['DPI repetidos en el archivo (omitidos)', $lectura['repetidos']],
                ['DPI encontrados', count($encontrados)],
                ['DPI no encontrados' . ($u->departamentoId || $u->municipioId ? ' o fuera de su ámbito' : ''), $faltan],
                ['Cantidad total de registros exportados', $n],
                ['Columnas seleccionadas', implode(', ', ReporteColumnas::etiquetas($cols))],
                ['Criterios utilizados', 'Coincidencia exacta por número de DPI sobre los expedientes del sistema. DPI '
                    . ($u->puede('beneficiarios.ver_dpi') ? 'completo.' : 'enmascarado por permisos del usuario.')],
            ] as [$campo, $valor]) {
                $w->fila($info, [$campo, $valor]);
            }
            $ruta = $this->dirTemporal() . '/reporte_' . bin2hex(random_bytes(8)) . '.xlsx';
            $w->guardar($ruta);
        } catch (\Throwable $e) {
            $this->registrar($u, 'reporte_dpi', 'ERROR', $detalle + ['error' => $e->getMessage()]);
            throw $e;
        }
        return ['ruta' => $ruta, 'nombre' => $nombre, 'auditoria' => $detalle + ['filas' => $n]];
    }

    // ================================================================================== bitácora
    /** Registra el resultado de una generación/descarga. $estado: DESCARGADO | INTERRUMPIDO | ERROR. */
    public function registrar(Usuario $u, string $entidad, string $estado, array $detalle): void
    {
        try {
            $this->auditoria->registrar($u, 'EXPORTAR', 'reportes', $entidad, null, $detalle + ['estado' => $estado]);
        } catch (\Throwable $e) {
            error_log('No se pudo registrar la bitácora del reporte: ' . $e->getMessage());
        }
    }

    public function bitacora(array $q): array
    {
        $f = [];
        foreach (['usuario', 'estado', 'ubicacion'] as $k) {
            if (!empty($q[$k])) {
                $f[$k] = mb_substr(trim((string) $q[$k]), 0, 80);
            }
        }
        if (!empty($q['modalidad'])) {
            $f['modalidad'] = Validator::opcion((string) $q['modalidad'], ['dpi', 'geografico', 'tablero'], 'La modalidad');
        }
        foreach (['desde', 'hasta'] as $k) {
            if (!empty($q[$k])) {
                $f[$k] = Validator::fecha($q[$k], "El campo $k");
            }
        }
        if (!empty($q['antes_de'])) {
            $f['antes_de'] = Validator::entero($q['antes_de'], 'antes_de');
        }
        return $this->repository->bitacora($f, max(1, min(200, (int) ($q['limite'] ?? 50))));
    }

    /** Borra temporales de reportes abandonados (más de una hora). */
    public function limpiarTemporales(): void
    {
        foreach (glob($this->dirTemporal() . '/{reporte_*.xlsx,xlsx_*.part,xlsx_*.final}', GLOB_BRACE) ?: [] as $f) {
            if (is_file($f) && filemtime($f) < time() - 3600) {
                @unlink($f);
            }
        }
    }

    // ================================================================================== internos
    private function exigirExportar(Usuario $u): void
    {
        if (!$u->puede('reportes.exportar')) {
            throw new HttpException('Su rol no puede exportar datos personales', 403);
        }
    }

    private function limite(Usuario $u): int
    {
        return (int) ($this->repository->limiteDetalle()[$u->rol] ?? 10000);
    }

    private function dirTemporal(): string
    {
        $cfg = require __DIR__ . '/../../config/app.php';
        return rtrim(dirname($cfg['import_dir']), '/') . '/tmp';
    }

    private function enmascarar(Usuario $u, array $cols, array $fila): array
    {
        $i = array_search('dpi', $cols, true);
        if ($i !== false && !$u->puede('beneficiarios.ver_dpi')) {
            $fila[$i] = Security::enmascararDpi($fila[$i]);
        }
        return $fila;
    }

    private function leerDpi(?array $archivo): array
    {
        if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($archivo['tmp_name'])) {
            throw new HttpException('Seleccione el archivo Excel (.xlsx) con la columna DPI', 400);
        }
        if (($archivo['size'] ?? 0) > self::MAX_UPLOAD) {
            throw new HttpException('El archivo supera el máximo de ' . (self::MAX_UPLOAD / 1048576) . ' MB', 413);
        }
        if (strtolower(pathinfo((string) $archivo['name'], PATHINFO_EXTENSION)) !== 'xlsx' || !is_uploaded_file($archivo['tmp_name'])) {
            throw new HttpException('El archivo debe ser un Excel con extensión .xlsx', 400);
        }
        $lectura = XlsxReader::columna($archivo['tmp_name'], 'DPI', self::MAX_DPI);
        if (!$lectura['dpis']) {
            throw new HttpException('La columna DPI no contiene valores', 422);
        }
        return $lectura;
    }

    /**
     * Filtro geográfico validado contra el ámbito del usuario.
     * @return array{where: string, params: array, deps_sel: int, muns_sel: int, etiqueta_departamentos: string, etiqueta_municipios: string, auditoria: array}
     */
    private function territorio(Usuario $u, array $b): array
    {
        $dep = isset($b['departamento']) && (int) $b['departamento'] > 0 ? (int) $b['departamento'] : null;
        $muns = [];
        foreach (is_array($b['municipios'] ?? null) ? $b['municipios'] : [] as $m) {
            if ((int) $m > 0) {
                $muns[(int) $m] = (int) $m;
            }
        }
        $muns = array_values($muns);
        $nacional = !$u->departamentoId && !$u->municipioId;
        $depNombre = null;

        if ($dep === null) {
            if (!$nacional) {
                throw new HttpException('Su usuario no tiene permiso para consultar todos los departamentos', 403);
            }
            if ($muns) {
                throw new HttpException('Seleccione un departamento para elegir municipios', 400);
            }
        } else {
            $depNombre = $this->repository->nombreDepartamento($dep);
            if ($depNombre === null) {
                throw new HttpException('Departamento desconocido', 400);
            }
            if (($u->departamentoId && $u->departamentoId !== $dep) || ($u->municipioId && intdiv($u->municipioId, 100) !== $dep)) {
                throw new HttpException('El departamento está fuera de su ámbito territorial', 403);
            }
        }

        $nombresMuns = [];
        if ($muns) {
            if (count($muns) > self::MAX_MUNICIPIOS) {
                throw new HttpException('Demasiados municipios seleccionados', 400);
            }
            $existentes = $this->repository->municipios($muns);
            foreach ($muns as $m) {
                if (!isset($existentes[$m]) || (int) $existentes[$m]['departamento_id'] !== $dep) {
                    throw new HttpException('Hay municipios que no pertenecen al departamento seleccionado', 400);
                }
                if ($u->municipioId && $u->municipioId !== $m) {
                    throw new HttpException('El municipio está fuera de su ámbito territorial', 403);
                }
                $nombresMuns[] = $existentes[$m]['nombre'];
            }
        }

        $where = '';
        $params = [];
        if ($dep !== null) {
            $where .= ' AND v.departamento_id = ?';
            $params[] = $dep;
        }
        if ($muns) {
            $where .= ' AND v.municipio_id IN (' . implode(',', array_fill(0, count($muns), '?')) . ')';
            array_push($params, ...$muns);
        }
        [$amb, $ap] = $this->filtros->ambito($u);
        $where .= $amb;
        $params = array_merge($params, $ap);

        $depsSel = $dep === null ? $this->repository->totalDepartamentos() : 1;
        if ($muns) {
            $munsSel = count($muns);
        } elseif ($u->municipioId) {
            $munsSel = 1;
        } else {
            $munsSel = $this->repository->totalMunicipiosDe($dep);
        }

        $etiquetaDeps = $depNombre ?? 'Todos los departamentos';
        $etiquetaMuns = $muns
            ? implode(', ', $nombresMuns) . ' (' . count($muns) . ')'
            : ($dep === null ? 'Todos los municipios autorizados' : "Todos los municipios del departamento ($munsSel)");

        return [
            'where' => $where, 'params' => $params, 'deps_sel' => $depsSel, 'muns_sel' => $munsSel,
            'etiqueta_departamentos' => $etiquetaDeps, 'etiqueta_municipios' => $etiquetaMuns,
            'auditoria' => [
                'departamento' => $etiquetaDeps,
                'municipios' => $muns ? $nombresMuns : [$etiquetaMuns],
                'municipios_total' => $munsSel,
            ],
        ];
    }
}
