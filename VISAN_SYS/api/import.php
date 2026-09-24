<?php
/**
 * Importación de la matriz desde Excel (dos pasos: vista previa → confirmar).
 *   POST import.php?a=previsualizar   multipart: archivo
 *   POST import.php?a=confirmar       {token, fecha_corte}
 *   GET  import.php?a=historial
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/matriz_excel.php';

$u = exigirUsuario();
if (!$u['permisos']['importar']) fallar('No tienes permiso para importar datos.', 403);
$accion = $_GET['a'] ?? '';

const CAMPOS_TOTAL = ['ej_nda', 'ej_mc', 'ej_judicial', 'ej_apa', 'ej_reserva', 'ej_insan', 'prog_nda', 'prog_mc', 'prog_judicial', 'prog_apa', 'prog_reserva', 'prog_insan', 'conred'];

function totales(array $filas): array
{
    $t = array_fill_keys(CAMPOS_TOTAL, 0);
    foreach ($filas as $f) foreach (CAMPOS_TOTAL as $k) $t[$k] += (float)($f[$k] ?? 0);
    return $t;
}

function rutaTemporal(string $token): string
{
    if (!preg_match('/^[a-f0-9]{32}$/', $token)) fallar('Vista previa no válida. Vuelve a cargar el archivo.');
    return DATA_DIR . "/import_{$token}.json";
}

// Limpia vistas previas abandonadas (> 2 h)
foreach (glob(DATA_DIR . '/import_*.json') ?: [] as $viejo) {
    if (filemtime($viejo) < time() - 7200) @unlink($viejo);
}

switch ($accion) {
    case 'previsualizar':
        exigirPost();
        $arch = $_FILES['archivo'] ?? null;
        if (!$arch || $arch['error'] !== UPLOAD_ERR_OK) fallar('No se recibió el archivo (código ' . ($arch['error'] ?? '—') . ').');
        if ($arch['size'] > MAX_UPLOAD_BYTES) fallar('El archivo supera el tamaño máximo de 15 MB.');
        if (strtolower(pathinfo($arch['name'], PATHINFO_EXTENSION)) !== 'xlsx') fallar('Solo se aceptan archivos Excel .xlsx.');

        try {
            $r = leerMatrizExcel($arch['tmp_name']);
        } catch (InvalidArgumentException $e) {
            fallar($e->getMessage(), 422);
        } catch (Throwable $e) {
            error_log('[VISAN] leerMatrizExcel: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            fallar('No se pudo leer el archivo (' . get_class($e) . '): ' . $e->getMessage() . ' [' . basename($e->getFile()) . ':' . $e->getLine() . ']', 500);
        }

        try {
            $actuales = [];
            foreach (db()->query('SELECT cod_mun, datos FROM matriz')->fetchAll() as $x) $actuales[(int)$x['cod_mun']] = json_decode($x['datos'], true);

            // Colores: si alguna celda no trae relleno reconocible, se conserva la clasificación anterior
            $sinColor = 0;
            foreach ($r['filas'] as &$f) {
                if (!$f['color']) {
                    $f['color'] = $actuales[$f['cod_mun']]['color'] ?? null;
                    $sinColor++;
                }
            }
            unset($f);

            $nuevos = $cambiados = 0;
            $codNuevos = [];
            foreach ($r['filas'] as $f) {
                $codNuevos[$f['cod_mun']] = true;
                if (!isset($actuales[$f['cod_mun']])) { $nuevos++; continue; }
                if ($actuales[$f['cod_mun']] != $f) $cambiados++;
            }
            $eliminados = array_values(array_map(
                fn($a) => "{$a['municipio']} ({$a['departamento']})",
                array_filter($actuales, fn($a, $cod) => !isset($codNuevos[$cod]), ARRAY_FILTER_USE_BOTH)
            ));

            // Si la celda «Fecha de actualización» viene vacía, se intenta leer del nombre del archivo:
            // primero como "... 23 sep.xlsx" y, si no, como "... 24_09_2026.xlsx" (día_mes_año numérico).
            if (!$r['fecha_corte'] && preg_match('/\b(\d{1,2})\s*(?:de\s+)?(ene|feb|mar|abr|may|jun|jul|ago|sep|oct|nov|dic)[a-z]*\.?\b/iu', $arch['name'], $m)) {
                $mes = array_search(strtolower($m[2]), ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic']) + 1;
                if (checkdate($mes, (int)$m[1], (int)date('Y'))) $r['fecha_corte'] = sprintf('%s-%02d-%02d', date('Y'), $mes, $m[1]);
            }
            if (!$r['fecha_corte'] && preg_match('/\b(\d{1,2})[_.\-\/](\d{1,2})[_.\-\/](\d{4})\b/', $arch['name'], $m)) {
                [$dia, $mes, $anio] = [(int)$m[1], (int)$m[2], (int)$m[3]];
                if (checkdate($mes, $dia, $anio)) $r['fecha_corte'] = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
            }
        } catch (Throwable $e) {
            error_log('[VISAN] import previsualizar (post-proceso): ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            fallar('No se pudo procesar el archivo (' . get_class($e) . '): ' . $e->getMessage() . ' [' . basename($e->getFile()) . ':' . $e->getLine() . ']', 500);
        }

        $token = bin2hex(random_bytes(16));
        file_put_contents(rutaTemporal($token), json_encode([
            'archivo' => mb_substr(basename($arch['name']), 0, 150),
            'filas' => $r['filas'],
            'usuario' => $u['usuario'],
        ], JSON_UNESCAPED_UNICODE));

        responder([
            'ok' => true,
            'token' => $token,
            'archivo' => basename($arch['name']),
            'hoja' => $r['hoja'],
            'filas' => count($r['filas']),
            'departamentos' => count(array_unique(array_column($r['filas'], 'departamento'))),
            'fecha_corte_excel' => $r['fecha_corte'],
            'nuevos' => $nuevos,
            'cambiados' => $cambiados,
            'eliminados' => $eliminados,
            'sin_color' => $sinColor,
            'avisos' => array_slice($r['avisos'], 0, 50),
            'totales_actuales' => totales($actuales),
            'totales_nuevos' => totales($r['filas']),
        ]);

    case 'confirmar':
        exigirPost();
        $in = leerJSON();
        $ruta = rutaTemporal((string)($in['token'] ?? ''));
        if (!file_exists($ruta)) fallar('La vista previa expiró. Vuelve a cargar el archivo.', 410);
        $p = json_decode(file_get_contents($ruta), true);
        if (($p['usuario'] ?? '') !== $u['usuario']) fallar('Vista previa no válida.', 403);

        $fecha = (string)($in['fecha_corte'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) fallar('Indica la fecha de corte de los datos.');

        guardarMatriz(db(), $p['filas'], $u['usuario']);
        setMeta('fecha_corte', $fecha);
        setMeta('ultimo_import', json_encode(['archivo' => $p['archivo'], 'fecha' => date('Y-m-d H:i:s'), 'usuario' => $u['usuario'], 'filas' => count($p['filas'])], JSON_UNESCAPED_UNICODE));
        registrar($u['usuario'], 'importacion', "{$p['archivo']} · " . count($p['filas']) . " municipios · corte {$fecha}");
        @unlink($ruta);
        responder(['ok' => true, 'filas' => count($p['filas'])]);

    case 'historial':
        $st = db()->query("SELECT fecha, usuario, detalle FROM bitacora WHERE accion = 'importacion' ORDER BY id DESC LIMIT 15");
        responder(['ok' => true, 'historial' => $st->fetchAll()]);

    default:
        fallar('Acción no válida.', 404);
}
