<?php
// api/exportar_excel.php
require_once '../config/database.php';

// Verificar que el usuario esté logueado
verificarSesion();

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Obtener solo los registros del usuario actual
    $query = "SELECT id, nombre, apellido, telefono, telefono_americano, como_se_entero, correo, comentario, fecha_registro 
              FROM registros 
              WHERE usuario_id = :usuario_id 
              ORDER BY fecha_registro DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
    $stmt->execute();

    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Nombre del archivo
    $filename = "registros_" . date('Y-m-d_H-i-s') . ".csv";
    
    // Headers para descarga CSV
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    // BOM para UTF-8 (para que Excel muestre bien los acentos)
    echo "\xEF\xBB\xBF";
    
    // Abrir output como archivo
    $output = fopen('php://output', 'w');
    
    // Cabeceras de la tabla
    fputcsv($output, ['ID', 'Nombre', 'Apellido', 'Teléfono GT', 'Teléfono USA', 'Cómo se enteró', 'Correo', 'Comentario', 'Fecha de Registro'], ';');
    
    // Datos
    foreach ($registros as $registro) {
        fputcsv($output, [
            $registro['id'],
            $registro['nombre'],
            $registro['apellido'],
            $registro['telefono'] ?? '-',
            $registro['telefono_americano'] ?? '-',
            $registro['como_se_entero'],
            $registro['correo'] ?? '-',
            $registro['comentario'] ?? '-',
            date('d/m/Y H:i:s', strtotime($registro['fecha_registro']))
        ], ';');
    }
    
    fclose($output);
    
} catch (PDOException $e) {
    die('Error al exportar registros');
}
?>