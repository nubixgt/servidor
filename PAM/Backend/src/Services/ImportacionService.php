<?php
namespace App\Services;

use App\Core\HttpException;
use App\Entities\Usuario;
use App\Repositories\ImportacionRepository;
use App\Utils\CsvLector;
use App\Utils\Response;
use Throwable;

/**
 * Importación masiva: archivo CSV → stg_matriz → core_* → agregados del tablero → reporte de conciliación.
 */
class ImportacionService
{
    private const LOTE_INSERT = 500;
    private const EXTENSIONES = ['csv', 'txt'];

    private $repository;
    private $auditoria;
    private $config;

    public function __construct()
    {
        $this->repository = new ImportacionRepository();
        $this->auditoria = new AuditoriaService();
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    /** Guarda el archivo subido y registra el lote como PENDIENTE. */
    public function subir(Usuario $u, ?array $archivo, string $modo): array
    {
        if (!in_array($modo, ['INCREMENTAL', 'VALIDAR'], true)) {
            throw new HttpException('Modo no válido', 400);
        }
        if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                throw new HttpException('El archivo supera el tamaño permitido por el servidor (revise upload_max_filesize y post_max_size en php.ini)', 413);
            }
            throw new HttpException('No se recibió el archivo', 400);
        }

        $nombre = (string) $archivo['name'];
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        if (!in_array($ext, self::EXTENSIONES, true)) {
            throw new HttpException('Formato no admitido. Use un archivo .csv (en Excel: Guardar como → CSV UTF-8)', 400);
        }
        $bytes = (int) $archivo['size'];
        if ($bytes > $this->config['max_upload_mb'] * 1024 * 1024) {
            throw new HttpException("El archivo supera {$this->config['max_upload_mb']} MB", 413);
        }

        $dir = $this->config['import_dir'];
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new HttpException('No se pudo crear la carpeta de importaciones', 500);
        }
        $seguro = substr(preg_replace('/[^A-Za-z0-9._-]+/', '_', $nombre), 0, 120);
        $destino = rtrim($dir, '/\\') . '/' . date('Ymd_His') . '_' . $u->usuario . '_' . $seguro;
        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            throw new HttpException('No se pudo guardar el archivo', 500);
        }

        $lote = $this->repository->crearLote($nombre, $destino, $modo, $u->id);
        $this->auditoria->registrar($u, 'SUBIR_IMPORTACION', 'importacion', 'stg.import_lote', (string) $lote, ['archivo' => $nombre, 'bytes' => $bytes, 'modo' => $modo]);
        return ['id' => $lote, 'estado' => 'PENDIENTE', 'bytes' => $bytes];
    }

    /** ¿El archivo es lo bastante pequeño para procesarlo en la misma petición? */
    public function esInline(int $bytes): bool
    {
        return $bytes <= $this->config['import_inline_mb'] * 1024 * 1024;
    }

    public function listar(): array
    {
        return $this->repository->listar();
    }

    public function detalle(int $id): array
    {
        $lote = $this->repository->lote($id);
        if (!$lote) {
            throw new HttpException('Lote no encontrado', 404);
        }
        unset($lote['ruta']);
        $lote['reporte'] = $lote['reporte'] === null ? null : json_decode($lote['reporte'], true);
        $lote['resumen_errores'] = $this->repository->resumenErrores($id);
        return $lote;
    }

    public function errores(Usuario $u, int $id): \PDOStatement
    {
        $this->auditoria->registrar($u, 'DESCARGAR_ERRORES', 'importacion', 'stg.import_lote', (string) $id);
        return $this->repository->errores($id);
    }

    public function cancelar(int $id): array
    {
        if (!$this->repository->cancelar($id)) {
            throw new HttpException('Solo se pueden cancelar lotes pendientes', 409);
        }
        return ['ok' => true];
    }

    /** Procesa los lotes PENDIENTES uno por uno (bin/importar.php --worker). Devuelve cuántos terminó. */
    public function procesarPendientes(): int
    {
        $this->repository->marcarInterrumpidos();
        $hechos = 0;
        while (($lote = $this->repository->tomarPendiente()) !== null) {
            try {
                $this->ejecutarLote($lote);
            } catch (Throwable $e) {
                // el error queda registrado en el lote
            }
            $hechos++;
        }
        return $hechos;
    }

    /** Ejecuta un lote ya registrado. Devuelve el reporte de conciliación. */
    public function ejecutarLote(int $lote): array
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $inicio = microtime(true);
        $info = $this->repository->lote($lote);
        if (!$info) {
            throw new HttpException("Lote $lote no existe", 404);
        }
        $soloValidar = $info['modo'] === 'VALIDAR';
        $repo = $this->repository;

        try {
            $repo->actualizar($lote, ['estado' => 'LEYENDO', 'progreso' => 1, 'iniciado_en' => date('Y-m-d H:i:s'),
                'sha256' => hash_file('sha256', $info['ruta']), 'mensaje' => 'Leyendo archivo']);

            $lector = new CsvLector($info['ruta']);
            $repo->limpiarMatriz($lote);
            $total = 0;
            $buffer = [];
            foreach ($lector->filas() as $fila) {
                $buffer[] = $fila;
                $total++;
                if (count($buffer) >= self::LOTE_INSERT) {
                    $repo->insertarMatriz($lote, $buffer);
                    $buffer = [];
                }
            }
            $repo->insertarMatriz($lote, $buffer);

            $mensaje = number_format($total, 0, ',', '.') . ' filas leídas';
            if ($lector->extra) {
                $mensaje .= '; columnas no reconocidas: ' . implode(', ', $lector->extra);
            }
            $repo->actualizar($lote, ['formato' => 'csv', 'codificacion' => $lector->codificacion, 'total_filas' => $total, 'progreso' => 35, 'mensaje' => $mensaje]);
            if ($total === 0) {
                throw new \RuntimeException('El archivo no tiene filas de datos');
            }

            $repo->actualizar($lote, ['estado' => 'TRANSFORMANDO', 'progreso' => 45, 'mensaje' => 'Validando y actualizando expedientes (para 800 mil filas toma varios minutos)']);
            $repo->procesar($lote, $soloValidar);

            $reporte = $this->reporte($lote, $soloValidar);
            $repo->actualizar($lote, ['reporte' => json_encode($reporte, Response::FLAGS)]);

            if (!$soloValidar) {
                $repo->actualizar($lote, ['estado' => 'REFRESCANDO', 'progreso' => 92, 'mensaje' => 'Actualizando los agregados del tablero']);
                $repo->refrescarTablero();
                $repo->limpiarMatriz($lote);
                $repo->actualizar($lote, ['estado' => 'COMPLETADO', 'progreso' => 100, 'finalizado_en' => date('Y-m-d H:i:s'),
                    'mensaje' => 'Importación terminada en ' . number_format(microtime(true) - $inicio, 0, ',', '.') . ' s']);
            } else {
                $repo->limpiarMatriz($lote);
                $repo->invalidarCache();
            }
            return $reporte;
        } catch (Throwable $e) { // el lote queda en ERROR con el motivo visible en la web
            error_log($e);
            try {
                $repo->limpiarMatriz($lote);
            } catch (Throwable $ignorado) {
            }
            $repo->actualizar($lote, ['estado' => 'ERROR', 'mensaje' => mb_substr($e->getMessage(), 0, 2000), 'finalizado_en' => date('Y-m-d H:i:s')]);
            throw $e;
        }
    }

    /** Conciliación: conteos de la matriz contra lo que quedó en la base, por departamento × estado. */
    private function reporte(int $lote, bool $soloMatriz): array
    {
        $j = [];
        foreach ($this->repository->conteoMatriz($lote) as $r) {
            $k = $r['dep'] . '|' . $r['estado'];
            $j[$k] = ['dep' => $r['dep'] === null ? null : (int) $r['dep'], 'estado' => $r['estado'], 'matriz' => (int) $r['n'], 'base' => 0];
        }
        if (!$soloMatriz) {
            foreach ($this->repository->conteoBase($lote) as $r) {
                $k = $r['dep'] . '|' . $r['estado'];
                $j[$k] ??= ['dep' => (int) $r['dep'], 'estado' => $r['estado'], 'matriz' => 0, 'base' => 0];
                $j[$k]['base'] = (int) $r['n'];
            }
        }

        $deptos = $this->repository->nombresDepartamentos();
        $diferencias = [];
        $porEstado = [];
        foreach ($j as $f) {
            $porEstado[$f['estado']] = ($porEstado[$f['estado']] ?? 0) + $f['matriz'];
            if (!$soloMatriz && $f['matriz'] !== $f['base']) {
                $diferencias[] = ['departamento' => $deptos[$f['dep']] ?? null, 'estado' => $f['estado'], 'matriz' => $f['matriz'], 'base' => $f['base']];
            }
        }
        [$personas, $expedientes] = $this->repository->totales();

        return [
            'total_matriz' => array_sum(array_column($j, 'matriz')),
            'total_base_lote' => array_sum(array_column($j, 'base')),
            'diferencias' => $diferencias,
            'por_estado' => (object) $porEstado,
            'errores' => (object) $this->repository->erroresPorRegla($lote, 'ERROR'),
            'advertencias' => (object) $this->repository->erroresPorRegla($lote, 'ADVERTENCIA'),
            'personas_en_base' => $personas,
            'expedientes_en_base' => $expedientes,
        ];
    }
}
