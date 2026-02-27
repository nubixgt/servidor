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

    // Iniciar transacción
    $conn->beginTransaction();

    // Manejar armonización "Otro"
    $armonizacion = $_POST['armonizacion'];
    $armonizacion_otro = null;
    if ($armonizacion === 'Otro' && isset($_POST['armonizacionOtro'])) {
        $armonizacion_otro = $_POST['armonizacionOtro'];
    }

    // Insertar datos del contrato
    $sql = "INSERT INTO contratos (
        numero_contrato, servicios, iva, fondos, armonizacion, armonizacion_otro, fecha_contrato,
        nombre_completo, edad, estado_civil, profesion, domicilio, dpi,
        termino1, termino2, termino3, termino4, termino5,
        termino6, termino7, termino8, termino9, termino10,
        fecha_inicio, fecha_fin,
        monto_total, numero_pagos, monto_pago,
        termino_contratacion,
        usuario_id, fecha_registro
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?,
        ?, ?, ?,
        ?,
        ?, NOW()
    )";

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
        $_SESSION['usuario_id']
    ]);

    $contrato_id = $conn->lastInsertId();

    // Crear directorio para archivos si no existe
    $upload_dir = '../uploads/contratos/' . $contrato_id . '/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Procesar archivos
    $archivos = ['cv', 'titulo', 'colegiadoActivo', 'cuentaBanco', 'dpiArchivo', 'otro'];

    foreach ($archivos as $archivo) {
        if (isset($_FILES[$archivo]) && $_FILES[$archivo]['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES[$archivo]['tmp_name'];
            $file_name = $_FILES[$archivo]['name'];
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_file_name = $archivo . '_' . time() . '.' . $file_ext;
            $file_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $file_path)) {
                // Guardar información del archivo en la base de datos
                $sql_archivo = "INSERT INTO contrato_archivos (contrato_id, tipo_archivo, nombre_archivo, ruta_archivo, fecha_subida) 
                               VALUES (?, ?, ?, ?, NOW())";
                $stmt_archivo = $conn->prepare($sql_archivo);
                $stmt_archivo->execute([
                    $contrato_id,
                    $archivo,
                    $new_file_name,
                    $file_path
                ]);
            }
        }
    }

    // Confirmar transacción
    $conn->commit();

    // URL para descargar el PDF
    $pdfUrl = "/Oirsa/api/generar_pdf.php?id=" . $contrato_id;

    echo json_encode([
        'success' => true,
        'message' => 'Contrato registrado exitosamente',
        'contrato_id' => $contrato_id,
        'pdf_url' => $pdfUrl
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($conn)) {
        $conn->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar el formulario: ' . $e->getMessage()
    ]);
}
?>