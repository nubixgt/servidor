<?php
// Simulación end-to-end de POST import.php?a=previsualizar contra el archivo real,
// usando una base de datos SQLite temporal aislada (VISAN_DATA_DIR).
error_reporting(E_ALL);
ini_set('display_errors', '1');

chdir('/app');
require_once '/app/api/bootstrap.php';

// Sembrar la DB temporal y loguear como admin directamente en $_SESSION.
$pdo = db();
$row = $pdo->query("SELECT id FROM usuarios WHERE usuario='admin'")->fetch();
if (!session_id()) session_start();
$_SESSION['uid'] = $row['id'];

// Simular la petición HTTP que hace el navegador.
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_VISAN'] = '1';
$_GET['a'] = 'previsualizar';

$rutaArchivo = '/app/PROGRAMACION DAAN PLAN DE ACCION EMERGENCIA 24_09_2026.xlsx';
$_FILES['archivo'] = [
    'name' => basename($rutaArchivo),
    'type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'tmp_name' => $rutaArchivo,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($rutaArchivo),
];

// responder()/fallar() hacen exit, así que capturamos la salida con output buffering.
ob_start();
try {
    require '/app/api/import.php';
} catch (Throwable $e) {
    echo "EXCEPCION NO CAPTURADA: " . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n";
}
$out = ob_get_clean();
echo "SALIDA JSON:\n$out\n";
