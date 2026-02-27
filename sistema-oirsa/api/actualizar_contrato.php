<?php
session_start();
require_once '../config/database.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Sesión no válida'
    ]);
    exit();
}

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit();
}

header('Content-Type: application/json');

try {
    $conn = getConnection();

    // Verificar que se recibió el ID
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de contrato no proporcionado'
        ]);
        exit();
    }

    $id = $_POST['id'];

    // Iniciar transacción
    $conn->beginTransaction();

    // Manejar armonización "Otro"
    $armonizacion = $_POST['armonizacion'];
    $armonizacion_otro = null;
    if ($armonizacion === 'Otro' && isset($_POST['armonizacionOtro'])) {
        $armonizacion_otro = $_POST['armonizacionOtro'];
    }

    // Actualizar datos del contrato
    $sql = "UPDATE contratos SET
        numero_contrato = ?,
        servicios = ?,
        iva = ?,
        fondos = ?,
        armonizacion = ?,
        armonizacion_otro = ?,
        fecha_contrato = ?,
        nombre_completo = ?,
        edad = ?,
        estado_civil = ?,
        profesion = ?,
        domicilio = ?,
        dpi = ?,
        termino1 = ?,
        termino2 = ?,
        termino3 = ?,
        termino4 = ?,
        termino5 = ?,
        termino6 = ?,
        termino7 = ?,
        termino8 = ?,
        termino9 = ?,
        termino10 = ?,
        fecha_inicio = ?,
        fecha_fin = ?,
        monto_total = ?,
        numero_pagos = ?,
        monto_pago = ?,
        termino_contratacion = ?,
        fecha_actualizacion = NOW()
    WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $_POST['numeroContrato'],
        $_POST['servicios'],
        $_POST['iva'],
        $_POST['fondos'],
        $armonizacion,
        $armonizacion_otro,
        $_POST['fechaContrato'],
        $_POST['nombreCompleto'],
        $_POST['edad'],
        $_POST['estadoCivil'],
        $_POST['profesion'],
        $_POST['domicilio'],
        str_replace(' ', '', $_POST['dpi']),
        $_POST['termino1'] ?? null,
        $_POST['termino2'] ?? null,
        $_POST['termino3'] ?? null,
        $_POST['termino4'] ?? null,
        $_POST['termino5'] ?? null,
        $_POST['termino6'] ?? null,
        $_POST['termino7'] ?? null,
        $_POST['termino8'] ?? null,
        $_POST['termino9'] ?? null,
        $_POST['termino10'] ?? null,
        $_POST['fechaInicio'],
        $_POST['fechaFin'],
        str_replace(['Q', ','], '', $_POST['montoTotal']),
        $_POST['numeroPagos'],
        str_replace(['Q', ','], '', $_POST['montoPago']),
        $_POST['terminoContratacion'] ?? null,
        $id
    ]);

    // Procesar archivos nuevos si se subieron
    $archivos = ['cv', 'titulo', 'colegiadoActivo', 'cuentaBanco', 'dpiArchivo', 'otro'];
    $upload_dir = '../uploads/contratos/' . $id . '/';

    // Crear directorio si no existe
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Procesar archivos nuevos si se subieron
    foreach ($archivos as $archivo) {
        if (isset($_FILES[$archivo]) && $_FILES[$archivo]['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES[$archivo]['tmp_name'];
            $file_name = $_FILES[$archivo]['name'];
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_file_name = $archivo . '_' . time() . '.' . $file_ext;
            $file_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $file_path)) {
                // Verificar si ya existe un archivo de este tipo
                $sql_check = "SELECT id, ruta_archivo FROM contrato_archivos WHERE contrato_id = ? AND tipo_archivo = ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->execute([$id, $archivo]);
                $exists = $stmt_check->fetch();

                if ($exists) {
                    // Eliminar archivo físico antiguo
                    $ruta_antigua = $exists['ruta_archivo'];
                    // Normalizar la ruta (puede tener ../ o no)
                    if (file_exists($ruta_antigua)) {
                        unlink($ruta_antigua);
                    } elseif (file_exists('../' . $ruta_antigua)) {
                        unlink('../' . $ruta_antigua);
                    }

                    // Actualizar archivo existente
                    $sql_update = "UPDATE contrato_archivos 
                                  SET nombre_archivo = ?, ruta_archivo = ?, fecha_subida = NOW()
                                  WHERE contrato_id = ? AND tipo_archivo = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->execute([$new_file_name, $file_path, $id, $archivo]);
                } else {
                    // Insertar nuevo archivo
                    $sql_insert = "INSERT INTO contrato_archivos (contrato_id, tipo_archivo, nombre_archivo, ruta_archivo, fecha_subida) 
                                  VALUES (?, ?, ?, ?, NOW())";
                    $stmt_insert = $conn->prepare($sql_insert);
                    $stmt_insert->execute([$id, $archivo, $new_file_name, $file_path]);
                }
            }
        }
    }

    // Confirmar transacción
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Contrato actualizado exitosamente'
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($conn)) {
        $conn->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar el contrato: ' . $e->getMessage()
    ]);
}
?>  