<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once '/app/api/matriz_excel.php';

$ruta = '/app/PROGRAMACION DAAN PLAN DE ACCION EMERGENCIA 24_09_2026.xlsx';

try {
    $r = leerMatrizExcel($ruta);
    echo "OK. Filas: " . count($r['filas']) . "\n";
    echo "Fecha corte: " . ($r['fecha_corte'] ?? 'null') . "\n";
    echo "Hoja: " . $r['hoja'] . "\n";
    echo "Avisos: " . count($r['avisos']) . "\n";
    foreach (array_slice($r['avisos'], 0, 10) as $a) echo " - $a\n";
} catch (Throwable $e) {
    echo "EXCEPCION: " . get_class($e) . "\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
