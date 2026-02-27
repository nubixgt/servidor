<?php
// web/modules/admin/opinion_legal/guardar_seguimiento.php
require_once '../../../config/database.php';
require_once '../../../config/workflow.php';
require_once '../../../includes/verificar_sesion.php';

verificarRol(['admin', 'tecnico_4']); // Permitir admin y tecnico_4

// Validar datos del formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método no permitido';
    header("Location: index.php");
    exit;
}

$id_denuncia = isset($_POST['id_denuncia']) ? intval($_POST['id_denuncia']) : 0;
$comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

// Validaciones
if ($id_denuncia <= 0) {
    $_SESSION['error'] = 'ID de denuncia inválido';
    header("Location: index.php");
    exit;
}

if (empty($comentario) || strlen($comentario) < 20) {
    $_SESSION['error'] = 'El comentario debe tener al menos 20 caracteres';
    header("Location: procesar.php?id=" . $id_denuncia);
    exit;
}

if (!in_array($accion, ['siguiente_paso', 'rechazado'])) {
    $_SESSION['error'] = 'Acción no válida';
    header("Location: procesar.php?id=" . $id_denuncia);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Iniciar transacción
    $db->beginTransaction();

    // Obtener etapa actual
    $etapaActual = WorkflowDenuncias::obtenerEtapaActual($id_denuncia, $db);

    // Determinar siguiente etapa según acción
    if ($accion == 'rechazado') {
        $etapaSiguiente = 'finalizada';
    } else {
        $etapaSiguiente = WorkflowDenuncias::$siguienteEtapa[$etapaActual] ?? 'finalizada';
    }

    // Insertar seguimiento
    $sqlSeguimiento = "INSERT INTO seguimiento_denuncias 
                      (id_denuncia, etapa, accion, comentario, etapa_actual, procesado_por, fecha_procesamiento) 
                      VALUES (?, ?, ?, ?, ?, ?, NOW())";

    $stmtSeguimiento = $db->prepare($sqlSeguimiento);
    $stmtSeguimiento->execute([
        $id_denuncia,
        'opinion_legal', // Etapa donde se procesó
        $accion,
        $comentario,
        $etapaSiguiente,
        $_SESSION['usuario_id']
    ]);

    $id_seguimiento = $db->lastInsertId();

    // Procesar archivos adjuntos si los hay
    if (isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/app-uba/backend/uploads/seguimiento/';

        // Crear directorio si no existe
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $totalArchivos = count($_FILES['archivos']['name']);

        for ($i = 0; $i < $totalArchivos; $i++) {
            if ($_FILES['archivos']['error'][$i] === UPLOAD_ERR_OK) {
                $nombreOriginal = $_FILES['archivos']['name'][$i];
                $tamanoArchivo = $_FILES['archivos']['size'][$i];
                $tmpName = $_FILES['archivos']['tmp_name'][$i];

                // Validar tamaño (max 10MB)
                if ($tamanoArchivo > 10 * 1024 * 1024) {
                    throw new Exception("El archivo {$nombreOriginal} excede el tamaño máximo de 10MB");
                }

                // Determinar tipo de archivo
                $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
                $tipoArchivo = 'documento';

                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $tipoArchivo = 'imagen';
                } elseif (in_array($extension, ['mp3', 'wav', 'ogg', 'm4a'])) {
                    $tipoArchivo = 'audio';
                } elseif (in_array($extension, ['mp4', 'avi', 'mov', 'wmv'])) {
                    $tipoArchivo = 'video';
                }

                // Validar tipo MIME
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $tmpName);
                finfo_close($finfo);

                $mimePermitidos = [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'audio/mpeg',
                    'audio/wav',
                    'audio/ogg',
                    'video/mp4',
                    'video/x-msvideo',
                    'video/quicktime'
                ];

                if (!in_array($mimeType, $mimePermitidos)) {
                    throw new Exception("Tipo de archivo no permitido: {$nombreOriginal}");
                }

                // Generar nombre único
                $nombreArchivo = 'seguimiento_' . $id_seguimiento . '_' . uniqid() . '_' . time() . '.' . $extension;
                $rutaDestino = $uploadDir . $nombreArchivo;

                // Mover archivo
                if (move_uploaded_file($tmpName, $rutaDestino)) {
                    // Guardar en base de datos
                    $sqlArchivo = "INSERT INTO archivos_seguimiento 
                                  (id_seguimiento, tipo_archivo, nombre_archivo, ruta_archivo, tamano_bytes, fecha_subida) 
                                  VALUES (?, ?, ?, ?, ?, NOW())";

                    $stmtArchivo = $db->prepare($sqlArchivo);
                    $stmtArchivo->execute([
                        $id_seguimiento,
                        $tipoArchivo,
                        $nombreOriginal,
                        'uploads/seguimiento/' . $nombreArchivo,
                        $tamanoArchivo
                    ]);
                } else {
                    // MEJOR MANEJO DE ERROR
                    $errorMsg = "Error al subir el archivo: {$nombreOriginal}. ";
                    $errorMsg .= "Ruta destino: {$rutaDestino}. ";
                    $errorMsg .= "Permisos directorio: " . (is_writable($uploadDir) ? 'Sí' : 'NO');
                    throw new Exception($errorMsg);
                }
            }
        }
    }

    // Actualizar estado de la denuncia
    WorkflowDenuncias::actualizarEstadoDenuncia($id_denuncia, $accion, $db);

    // Commit de la transacción
    $db->commit();

    // Devolver JSON en lugar de redirección
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Denuncia procesada correctamente'
    ]);
    exit;

} catch (Exception $e) {
    // Rollback en caso de error
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    // Devolver JSON de error
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar la denuncia: ' . $e->getMessage()
    ]);
    exit;
}
?>