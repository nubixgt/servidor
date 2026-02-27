<?php
/**
 * Endpoint para generar PDF de contrato
 * Uso: api/generar_pdf.php?id=123
 */

// Mostrar todos los errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';
require_once '../lib/ContratoPDF.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    die('No autorizado. Debe iniciar sesión.');
}

// Obtener ID del contrato
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    http_response_code(400);
    die('ID de contrato no válido');
}

try {
    $conn = getConnection();

    // Obtener datos del contrato
    $sql = "SELECT * FROM contratos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $contrato = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contrato) {
        http_response_code(404);
        die('Contrato no encontrado');
    }

    // Generar PDF
    $pdf = new ContratoPDF($contrato);
    $pdf->generarContrato();

    // Nombre del archivo
    $nombreArchivo = 'Contrato_' . str_replace('/', '-', $contrato['numero_contrato']) . '.pdf';

    // Limpiar cualquier salida previa
    ob_end_clean();

    // Enviar PDF al navegador para descarga
    $pdf->Output($nombreArchivo, 'D'); // 'D' = Descarga, 'I' = Ver en navegador

} catch (Exception $e) {
    http_response_code(500);
    error_log('Error generando PDF: ' . $e->getMessage());

    // Mostrar error detallado para debugging
    echo '<h1>Error al generar PDF</h1>';
    echo '<p><strong>Mensaje:</strong> ' . $e->getMessage() . '</p>';
    echo '<p><strong>Archivo:</strong> ' . $e->getFile() . '</p>';
    echo '<p><strong>Línea:</strong> ' . $e->getLine() . '</p>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
    die();
}
?>