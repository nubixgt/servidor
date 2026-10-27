<?php
/**
 * Importación de la matriz desde la línea de comandos.
 *
 *   php bin/importar.php "BD PAM.csv" [--validar] [--usuario=admin] [--sin-refrescar]
 *   php bin/importar.php --worker        procesa los lotes PENDIENTES subidos desde la web (archivos grandes)
 */
require_once __DIR__ . '/../autoload.php';

use App\Repositories\ImportacionRepository;
use App\Services\ImportacionService;
use App\Utils\Database;

$config = require __DIR__ . '/../config/app.php';
date_default_timezone_set($config['timezone']);
set_time_limit(0);

$servicio = new ImportacionService();

if (in_array('--worker', $argv, true)) {
    echo 'Lotes procesados: ' . $servicio->procesarPendientes() . "\n";
    exit(0);
}

$archivo = null;
foreach (array_slice($argv, 1) as $a) {
    if ($a[0] !== '-') {
        $archivo = $a;
    }
}
if ($archivo === null || !is_readable($archivo)) {
    fwrite(STDERR, "Uso: php bin/importar.php <archivo.csv> [--validar] [--usuario=admin]\n");
    exit(2);
}

$opt = getopt('', ['usuario::', 'validar', 'sin-refrescar']);
$db = Database::getInstance();
$usuarioId = isset($opt['usuario']) ? $db->scalar('SELECT id FROM seg_usuario WHERE usuario = ?', [$opt['usuario']]) : null;

// copia a la carpeta de importaciones para conservar el original del lote
if (!is_dir($config['import_dir'])) {
    mkdir($config['import_dir'], 0775, true);
}
$destino = rtrim($config['import_dir'], '/\\') . '/' . date('Ymd_His') . '_' . basename($archivo);
copy($archivo, $destino);

$repo = new ImportacionRepository();
// estado LEYENDO desde el inicio: así `--worker` no lo toma
$lote = $repo->crearLote(basename($archivo), $destino, isset($opt['validar']) ? 'VALIDAR' : 'INCREMENTAL', $usuarioId !== null ? (int) $usuarioId : null, 'LEYENDO');
echo "Lote $lote: procesando " . basename($archivo) . " …\n";
$reporte = $servicio->ejecutarLote($lote);
echo json_encode($reporte, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
