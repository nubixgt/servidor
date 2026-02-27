<?php
/**
 * Endpoint para crear una nota de envío completa con sus productos
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

    // Validar datos requeridos
    $camposRequeridos = [
        'fecha',
        'vendedor',
        'cliente_id',
        'nit',
        'direccion',
        'tipo_venta',
        'productos',
        'subtotal',
        'total',
        'usuario_id'
    ];

    foreach ($camposRequeridos as $campo) {
        if (!isset($data[$campo])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "El campo '$campo' es requerido"
            ]);
            exit;
        }
    }

    // Validar que haya productos
    if (empty($data['productos']) || !is_array($data['productos'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Debe incluir al menos un producto'
        ]);
        exit;
    }

    $conn = getConnection();
    $conn->beginTransaction();

    try {
        // Obtener el siguiente número de nota
        $sqlNumero = "SELECT numero_nota FROM nota_envio ORDER BY id DESC LIMIT 1";
        $stmtNumero = $conn->prepare($sqlNumero);
        $stmtNumero->execute();
        $resultNumero = $stmtNumero->fetch(PDO::FETCH_ASSOC);

        if ($resultNumero) {
            $ultimoNumero = intval($resultNumero['numero_nota']);
            $siguienteNumero = $ultimoNumero + 1;
        } else {
            $siguienteNumero = 1;
        }

        $numeroNota = str_pad($siguienteNumero, 5, '0', STR_PAD_LEFT);

        // Obtener nombre del cliente
        $sqlCliente = "SELECT nombre FROM clientes WHERE id = :cliente_id";
        $stmtCliente = $conn->prepare($sqlCliente);
        $stmtCliente->bindParam(':cliente_id', $data['cliente_id']);
        $stmtCliente->execute();
        $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {
            throw new Exception('Cliente no encontrado');
        }

        // Insertar nota de envío
        $sqlNota = "INSERT INTO nota_envio (
            numero_nota, fecha, vendedor, cliente_id, cliente_nombre,
            nit, direccion, tipo_venta, dias_credito, subtotal,
            descuento_total, total, usuario_id
        ) VALUES (
            :numero_nota, :fecha, :vendedor, :cliente_id, :cliente_nombre,
            :nit, :direccion, :tipo_venta, :dias_credito, :subtotal,
            :descuento_total, :total, :usuario_id
        )";

        $stmtNota = $conn->prepare($sqlNota);
        $stmtNota->bindParam(':numero_nota', $numeroNota);
        $stmtNota->bindParam(':fecha', $data['fecha']);
        $stmtNota->bindParam(':vendedor', $data['vendedor']);
        $stmtNota->bindParam(':cliente_id', $data['cliente_id']);
        $stmtNota->bindParam(':cliente_nombre', $cliente['nombre']);
        $stmtNota->bindParam(':nit', $data['nit']);
        $stmtNota->bindParam(':direccion', $data['direccion']);
        $stmtNota->bindParam(':tipo_venta', $data['tipo_venta']);
        $stmtNota->bindParam(':dias_credito', $data['dias_credito']);
        $stmtNota->bindParam(':subtotal', $data['subtotal']);

        $descuentoTotal = isset($data['descuento_total']) ? $data['descuento_total'] : 0;
        $stmtNota->bindParam(':descuento_total', $descuentoTotal);
        $stmtNota->bindParam(':total', $data['total']);
        $stmtNota->bindParam(':usuario_id', $data['usuario_id']);

        $stmtNota->execute();
        $notaId = $conn->lastInsertId();

        // Insertar productos
        $sqlDetalle = "INSERT INTO detalle_nota_envio (
            nota_envio_id, producto, presentacion, precio_unitario,
            cantidad, es_bonificacion, descuento, total
        ) VALUES (
            :nota_envio_id, :producto, :presentacion, :precio_unitario,
            :cantidad, :es_bonificacion, :descuento, :total
        )";

        $stmtDetalle = $conn->prepare($sqlDetalle);

        // Validar stock y preparar actualización de inventario
        foreach ($data['productos'] as $producto) {
            // Verificar stock disponible
            $sqlStock = "SELECT cantidad FROM productos_precios 
                         WHERE producto = :producto AND presentacion = :presentacion";
            $stmtStock = $conn->prepare($sqlStock);
            $stmtStock->bindParam(':producto', $producto['producto']);
            $stmtStock->bindParam(':presentacion', $producto['presentacion']);
            $stmtStock->execute();
            $stockData = $stmtStock->fetch(PDO::FETCH_ASSOC);

            if (!$stockData) {
                throw new Exception("Producto '{$producto['producto']}' con presentación '{$producto['presentacion']}' no encontrado");
            }

            $stockDisponible = intval($stockData['cantidad']);
            $cantidadSolicitada = intval($producto['cantidad']);

            if ($stockDisponible < $cantidadSolicitada) {
                throw new Exception("Stock insuficiente para '{$producto['producto']}' ({$producto['presentacion']}). Disponible: {$stockDisponible}, Solicitado: {$cantidadSolicitada}");
            }

            // Determinar si es bonificación
            $esBonificacion = isset($producto['es_bonificacion']) && $producto['es_bonificacion'] === true ? 'si' : 'no';

            // Insertar detalle
            $stmtDetalle->bindParam(':nota_envio_id', $notaId);
            $stmtDetalle->bindParam(':producto', $producto['producto']);
            $stmtDetalle->bindParam(':presentacion', $producto['presentacion']);
            $stmtDetalle->bindParam(':precio_unitario', $producto['precio_unitario']);
            $stmtDetalle->bindParam(':cantidad', $producto['cantidad']);
            $stmtDetalle->bindParam(':es_bonificacion', $esBonificacion);
            $stmtDetalle->bindParam(':descuento', $producto['descuento']);
            $stmtDetalle->bindParam(':total', $producto['total']);
            $stmtDetalle->execute();

            // Descontar del inventario
            $sqlUpdateStock = "UPDATE productos_precios 
                               SET cantidad = cantidad - :cantidad 
                               WHERE producto = :producto AND presentacion = :presentacion";
            $stmtUpdateStock = $conn->prepare($sqlUpdateStock);
            $stmtUpdateStock->bindParam(':cantidad', $cantidadSolicitada, PDO::PARAM_INT);
            $stmtUpdateStock->bindParam(':producto', $producto['producto']);
            $stmtUpdateStock->bindParam(':presentacion', $producto['presentacion']);
            $stmtUpdateStock->execute();
        }

        $conn->commit();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Nota de envío creada exitosamente',
            'numero_nota' => $numeroNota,
            'nota_id' => $notaId
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al crear la nota de envío: ' . $e->getMessage()
    ]);
}
?>