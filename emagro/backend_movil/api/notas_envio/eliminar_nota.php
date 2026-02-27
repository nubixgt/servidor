<?php
/**
 * Endpoint para eliminar una nota de envío y restaurar el inventario
 * Método: POST
 */

require_once '../../config/database.php';
require_once '../../config/cors.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

try {
    // Leer datos JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'El ID de la nota es requerido'
        ]);
        exit;
    }

    $notaId = intval($data['id']);
    $conn = getConnection();
    $conn->beginTransaction();

    try {
        // 1. Obtener los detalles de la nota para restaurar el inventario
        $sqlDetalles = "SELECT producto, presentacion, cantidad FROM detalle_nota_envio WHERE nota_envio_id = :nota_id";
        $stmtDetalles = $conn->prepare($sqlDetalles);
        $stmtDetalles->bindParam(':nota_id', $notaId);
        $stmtDetalles->execute();
        $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);

        if (empty($detalles)) {
            // Es posible que la nota exista pero no tenga detalles (aunque inusual en un sistema íntegro)
            // O que la nota no exista. Verificamos si la nota existe.
            $sqlCheck = "SELECT id FROM nota_envio WHERE id = :id";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bindParam(':id', $notaId);
            $stmtCheck->execute();
            if (!$stmtCheck->fetch()) {
                throw new Exception('Nota de envío no encontrada');
            }
        }

        // 2. Restaurar stock
        $sqlRestaurar = "UPDATE productos_precios SET cantidad = cantidad + :cantidad WHERE producto = :producto AND presentacion = :presentacion";
        $stmtRestaurar = $conn->prepare($sqlRestaurar);

        foreach ($detalles as $detalle) {
            $stmtRestaurar->bindParam(':cantidad', $detalle['cantidad']);
            $stmtRestaurar->bindParam(':producto', $detalle['producto']);
            $stmtRestaurar->bindParam(':presentacion', $detalle['presentacion']);
            $stmtRestaurar->execute();
        }

        // 3. Eliminar la nota de envío
        // Gracias a ON DELETE CASCADE en las tablas hijas (detalle_nota_envio y pagos), esto eliminará todo lo relacionado
        $sqlDelete = "DELETE FROM nota_envio WHERE id = :id";
        $stmtDelete = $conn->prepare($sqlDelete);
        $stmtDelete->bindParam(':id', $notaId);
        $stmtDelete->execute();

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Nota de envío eliminada y stock restaurado correctamente'
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar la nota: ' . $e->getMessage()
    ]);
}
?>