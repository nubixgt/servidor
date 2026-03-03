<?php
/**
 * Script de prueba para procesar el PDF de ejemplo
 * Uso: php test_pdf.php
 */

require_once 'config.php';
require_once 'procesar_pdf.php';

echo "========================================\n";
echo "Test de Procesamiento de PDF\n";
echo "========================================\n\n";

// Buscar el PDF en el directorio de uploads del usuario
$pdfPath = '/mnt/user-data/uploads/votacion_pregunta_APROBACIO_N_DEL_ACTA_DE_LA_SESIO_N_ANTERIOR-2.pdf';

if (!file_exists($pdfPath)) {
    echo "❌ Error: No se encuentra el archivo PDF\n";
    echo "   Ruta: $pdfPath\n";
    exit(1);
}

echo "✓ Archivo encontrado: " . basename($pdfPath) . "\n";
echo "  Tamaño: " . number_format(filesize($pdfPath) / 1024, 2) . " KB\n\n";

echo "Procesando PDF...\n";
echo "----------------------------------------\n";

$procesador = new ProcesadorVotacionPDF();
$resultado = $procesador->procesarPDF($pdfPath);

if ($resultado['success']) {
    echo "\n✓ ¡PDF procesado exitosamente!\n\n";
    echo "Resumen:\n";
    echo "  Evento ID: " . $resultado['evento_id'] . "\n";
    echo "  Total de votos procesados: " . $resultado['total_votos'] . "\n";
    
    // Obtener detalles del evento
    $db = getDB();
    $stmt = $db->prepare("
        SELECT 
            e.*,
            r.votos_favor,
            r.votos_contra,
            r.votos_ausentes,
            r.votos_licencia,
            r.resultado
        FROM eventos_votacion e
        LEFT JOIN resumen_eventos r ON e.id = r.evento_id
        WHERE e.id = ?
    ");
    $stmt->execute([$resultado['evento_id']]);
    $evento = $stmt->fetch();
    
    if ($evento) {
        echo "\nDetalles del Evento:\n";
        echo "  Número: " . $evento['numero_evento'] . "\n";
        echo "  Título: " . substr($evento['titulo'], 0, 60) . "...\n";
        echo "  Sesión: " . $evento['sesion_numero'] . "\n";
        echo "  Fecha: " . $evento['fecha_hora'] . "\n";
        echo "\nResultados:\n";
        echo "  A Favor: " . $evento['votos_favor'] . "\n";
        echo "  En Contra: " . $evento['votos_contra'] . "\n";
        echo "  Ausentes: " . $evento['votos_ausentes'] . "\n";
        echo "  Con Licencia: " . $evento['votos_licencia'] . "\n";
        echo "  Resultado: " . $evento['resultado'] . "\n";
    }
    
    // Mostrar algunos congresistas procesados
    echo "\nAlgunos congresistas procesados:\n";
    $stmt = $db->prepare("
        SELECT 
            c.nombre,
            v.voto,
            b.nombre as bloque
        FROM votos v
        JOIN congresistas c ON v.congresista_id = c.id
        LEFT JOIN bloques b ON v.bloque_id = b.id
        WHERE v.evento_id = ?
        LIMIT 5
    ");
    $stmt->execute([$resultado['evento_id']]);
    $votos = $stmt->fetchAll();
    
    foreach ($votos as $voto) {
        echo "  • " . $voto['nombre'] . " (" . substr($voto['bloque'], 0, 30) . ") - " . $voto['voto'] . "\n";
    }
    
    echo "\n✓ Test completado exitosamente\n";
    
} else {
    echo "\n❌ Error al procesar el PDF:\n";
    echo "   " . $resultado['error'] . "\n";
    exit(1);
}

echo "\n========================================\n";
echo "Puedes ver los resultados en:\n";
echo "  http://localhost/congreso/\n";
echo "========================================\n";
