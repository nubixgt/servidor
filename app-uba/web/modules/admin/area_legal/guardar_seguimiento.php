<?php
// web/modules/admin/area_legal/guardar_seguimiento.php
require_once '../../../config/database.php';
require_once '../../../config/workflow.php';
require_once '../../../includes/verificar_sesion.php';

verificarRol(['admin', 'tecnico_1']);

// Configurar respuesta JSON
header('Content-Type: application/json');

// Validar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

// Validar datos requeridos
$id_denuncia = isset($_POST['id_denuncia']) ? intval($_POST['id_denuncia']) : 0;
$comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';
$accion = isset($_POST['accion']) ? trim($_POST['accion']) : '';

// Validaciones
if ($id_denuncia <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de denuncia inválido'
    ]);
    exit;
}

if (empty($comentario)) {
    echo json_encode([
        'success' => false,
        'message' => 'El comentario es obligatorio'
    ]);
    exit;
}

if (strlen($comentario) < 20) {
    echo json_encode([
        'success' => false,
        'message' => 'El comentario debe tener al menos 20 caracteres'
    ]);
    exit;
}

if (!in_array($accion, ['siguiente_paso', 'rechazado', 'resuelto'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Acción no válida'
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Iniciar transacción
    $db->beginTransaction();

    // Determinar la etapa actual y la siguiente
    $etapa_procesamiento = 'area_legal'; // Esta es la etapa donde estamos

    // Determinar la próxima etapa según la acción
    if ($accion === 'siguiente_paso') {
        $etapa_siguiente = WorkflowDenuncias::$siguienteEtapa['en_area_legal'];
    } else {
        $etapa_siguiente = 'finalizada';
    }

    // Insertar registro de seguimiento
    $sqlSeguimiento = "INSERT INTO seguimiento_denuncias 
                       (id_denuncia, etapa, accion, comentario, etapa_actual, procesado_por) 
                       VALUES (?, ?, ?, ?, ?, ?)";

    $stmtSeguimiento = $db->prepare($sqlSeguimiento);
    $stmtSeguimiento->execute([
        $id_denuncia,
        $etapa_procesamiento,
        $accion,
        $comentario,
        $etapa_siguiente,
        $_SESSION['usuario_id']  // ← CORRECTO
    ]);

    $id_seguimiento = $db->lastInsertId();

    // Procesar archivos adjuntos si existen
    if (isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {
        $uploadDir = __DIR__ . '/../../../../backend/uploads/seguimiento/';

        // Crear directorio si no existe
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $archivosSubidos = [];
        $erroresArchivos = [];

        foreach ($_FILES['archivos']['name'] as $key => $filename) {
            // Validar que el archivo se subió correctamente
            if ($_FILES['archivos']['error'][$key] !== UPLOAD_ERR_OK) {
                continue;
            }

            $fileSize = $_FILES['archivos']['size'][$key];
            $fileTmpName = $_FILES['archivos']['tmp_name'][$key];
            $fileType = $_FILES['archivos']['type'][$key];

            // Validar tamaño (máximo 10MB)
            $maxSize = 10 * 1024 * 1024;
            if ($fileSize > $maxSize) {
                $erroresArchivos[] = "$filename supera los 10MB";
                continue;
            }

            // Validar tipo de archivo
            $allowedTypes = [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/webp',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'audio/mpeg',
                'audio/mp3',
                'video/mp4',
                'video/mpeg'
            ];

            if (!in_array($fileType, $allowedTypes)) {
                $erroresArchivos[] = "$filename no es un tipo de archivo permitido";
                continue;
            }

            // Determinar tipo de archivo para la BD
            $tipoArchivoBD = 'documento';
            if (strpos($fileType, 'image/') === 0) {
                $tipoArchivoBD = 'imagen';
            } elseif (strpos($fileType, 'audio/') === 0) {
                $tipoArchivoBD = 'audio';
            } elseif (strpos($fileType, 'video/') === 0) {
                $tipoArchivoBD = 'video';
            }

            // Generar nombre único
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $nombreUnico = 'seguimiento_' . $id_seguimiento . '_' . uniqid() . '_' . time() . '.' . $extension;
            $rutaDestino = $uploadDir . $nombreUnico;

            // Mover archivo
            if (move_uploaded_file($fileTmpName, $rutaDestino)) {
                // Guardar en BD
                $sqlArchivo = "INSERT INTO archivos_seguimiento 
                               (id_seguimiento, tipo_archivo, nombre_archivo, ruta_archivo, tamano_bytes) 
                               VALUES (?, ?, ?, ?, ?)";

                $stmtArchivo = $db->prepare($sqlArchivo);
                $stmtArchivo->execute([
                    $id_seguimiento,
                    $tipoArchivoBD,
                    $filename,
                    'uploads/seguimiento/' . $nombreUnico,
                    $fileSize
                ]);

                $archivosSubidos[] = $filename;
            } else {
                $erroresArchivos[] = "No se pudo guardar $filename";
            }
        }
    }

    // Actualizar estado de la denuncia
    WorkflowDenuncias::actualizarEstadoDenuncia($id_denuncia, $accion, $db);

    // Confirmar transacción
    $db->commit();

    // Preparar mensaje de éxito
    $mensajeExito = '';
    switch ($accion) {
        case 'siguiente_paso':
            $mensajeExito = 'Denuncia enviada al Área Técnica exitosamente';
            break;
        case 'rechazado':
            $mensajeExito = 'Denuncia rechazada exitosamente';
            break;
        case 'resuelto':
            $mensajeExito = 'Denuncia resuelta exitosamente';
            break;
    }

    if (!empty($archivosSubidos)) {
        $mensajeExito .= '. ' . count($archivosSubidos) . ' archivo(s) adjuntado(s)';
    }

    if (!empty($erroresArchivos)) {
        $mensajeExito .= '. Advertencias: ' . implode(', ', $erroresArchivos);
    }

    echo json_encode([
        'success' => true,
        'message' => $mensajeExito
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    // Eliminar archivos subidos si hubo error
    if (isset($archivosSubidos) && !empty($archivosSubidos)) {
        foreach ($archivosSubidos as $archivo) {
            $rutaArchivo = $uploadDir . $archivo;
            if (file_exists($rutaArchivo)) {
                unlink($rutaArchivo);
            }
        }
    }

    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar la denuncia: ' . $e->getMessage()
    ]);
}
?>