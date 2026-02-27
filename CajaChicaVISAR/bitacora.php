<?php
require_once 'config.php';
require_once 'auth.php';

// Requerir autenticación
requiereLogin();

// Obtener usuario actual
$usuarioActual = getUsuarioActual();

$vale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($vale_id <= 0) {
    $_SESSION['error'] = "ID de vale no válido";
    header("Location: listar_vales.php");
    exit();
}

// Configuración de archivos
define('UPLOAD_DIR', __DIR__ . '/uploads/bitacora/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
$allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'];
$allowed_mimes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'image/jpeg',
    'image/png',
    'image/gif'
];

try {
    $db = getDB();
    
    // Obtener información del vale
    $stmt = $db->prepare("SELECT * FROM vales WHERE id = ?");
    $stmt->execute([$vale_id]);
    $vale = $stmt->fetch();
    
    if (!$vale) {
        $_SESSION['error'] = "Vale no encontrado";
        header("Location: listar_vales.php");
        exit();
    }
    
    // Procesar nueva observación con archivos
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_observacion'])) {
        $observacion = trim($_POST['nueva_observacion']);
        $archivos_subidos = [];
        $errores_archivos = [];
        
        // Insertar la observación primero
        if (!empty($observacion) || (isset($_FILES['archivos']) && $_FILES['archivos']['error'][0] !== UPLOAD_ERR_NO_FILE)) {
            
            $obs_texto = !empty($observacion) ? $observacion : 'Archivos adjuntados';
            
            $stmt = $db->prepare("
                INSERT INTO bitacora_vales (vale_id, numero_vale, usuario, accion, observacion)
                VALUES (?, ?, ?, 'OBSERVACION', ?)
            ");
            $stmt->execute([$vale_id, $vale['numero_vale'], $usuarioActual['nombre_completo'], $obs_texto]);
            $bitacora_id = $db->lastInsertId();
            
            // Procesar archivos si hay
            if (isset($_FILES['archivos']) && is_array($_FILES['archivos']['name'])) {
                
                // Verificar/crear carpeta de uploads una vez
                if (!file_exists(UPLOAD_DIR)) {
                    if (!mkdir(UPLOAD_DIR, 0755, true)) {
                        $errores_archivos[] = "No se pudo crear la carpeta de uploads. Contacte al administrador.";
                    }
                }
                
                // Solo procesar si la carpeta existe y es escribible
                if (file_exists(UPLOAD_DIR) && is_writable(UPLOAD_DIR)) {
                    $total_files = count($_FILES['archivos']['name']);
                
                    for ($i = 0; $i < $total_files; $i++) {
                        if ($_FILES['archivos']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                            continue;
                        }
                        
                        if ($_FILES['archivos']['error'][$i] !== UPLOAD_ERR_OK) {
                            $errores_archivos[] = "Error al subir: " . $_FILES['archivos']['name'][$i];
                            continue;
                        }
                        
                        $nombre_original = $_FILES['archivos']['name'][$i];
                        $tamano = $_FILES['archivos']['size'][$i];
                        $tmp_name = $_FILES['archivos']['tmp_name'][$i];
                        $tipo = $_FILES['archivos']['type'][$i];
                        
                        // Validar tamaño
                        if ($tamano > MAX_FILE_SIZE) {
                            $errores_archivos[] = "$nombre_original excede el límite de 10MB";
                            continue;
                        }
                        
                        // Obtener extensión
                        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
                        
                        // Validar extensión
                        if (!in_array($extension, $allowed_extensions)) {
                            $errores_archivos[] = "$nombre_original: tipo de archivo no permitido";
                            continue;
                        }
                        
                        // Generar nombre único
                        $nombre_archivo = 'vale_' . $vale_id . '_' . $bitacora_id . '_' . time() . '_' . $i . '.' . $extension;
                        $ruta_destino = UPLOAD_DIR . $nombre_archivo;
                        
                        // Mover archivo
                        if (move_uploaded_file($tmp_name, $ruta_destino)) {
                            // Guardar en base de datos
                            $stmt = $db->prepare("
                                INSERT INTO bitacora_archivos (bitacora_id, vale_id, nombre_original, nombre_archivo, tipo_archivo, extension, tamano, subido_por)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $bitacora_id, 
                                $vale_id, 
                                $nombre_original, 
                                $nombre_archivo, 
                                $tipo, 
                                $extension, 
                                $tamano,
                                $usuarioActual['nombre_completo']
                            ]);
                            
                            $archivos_subidos[] = $nombre_original;
                        } else {
                            $errores_archivos[] = "No se pudo guardar: $nombre_original (verificar permisos)";
                        }
                    }
                } else {
                    $errores_archivos[] = "Carpeta de uploads no disponible o sin permisos";
                }
            }
            
            // Mensaje de resultado
            $msg = "Observación agregada exitosamente";
            if (count($archivos_subidos) > 0) {
                $msg .= ". " . count($archivos_subidos) . " archivo(s) adjuntado(s)";
            }
            if (count($errores_archivos) > 0) {
                $msg .= ". Errores: " . implode(", ", $errores_archivos);
            }
            
            $_SESSION['success'] = $msg;
            header("Location: bitacora.php?id=" . $vale_id);
            exit();
        }
    }
    
    // Obtener bitácora con archivos
    $stmt = $db->prepare("
        SELECT b.*, 
               (SELECT COUNT(*) FROM bitacora_archivos WHERE bitacora_id = b.id) as num_archivos
        FROM bitacora_vales b
        WHERE b.vale_id = ? 
        ORDER BY b.fecha_registro DESC
    ");
    $stmt->execute([$vale_id]);
    $registros = $stmt->fetchAll();
    
    // Obtener archivos por cada registro de bitácora
    $archivos_por_registro = [];
    foreach ($registros as $reg) {
        $stmt = $db->prepare("SELECT * FROM bitacora_archivos WHERE bitacora_id = ? ORDER BY fecha_subida ASC");
        $stmt->execute([$reg['id']]);
        $archivos_por_registro[$reg['id']] = $stmt->fetchAll();
    }
    
} catch(Exception $e) {
    $_SESSION['error'] = "Error al cargar bitácora: " . $e->getMessage();
    header("Location: listar_vales.php");
    exit();
}

// Función para formatear tamaño
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Función para obtener icono según extensión
function getFileIcon($extension) {
    $icons = [
        'pdf' => '📄',
        'doc' => '📝',
        'docx' => '📝',
        'xls' => '📊',
        'xlsx' => '📊',
        'jpg' => '🖼️',
        'jpeg' => '🖼️',
        'png' => '🖼️',
        'gif' => '🖼️'
    ];
    return $icons[$extension] ?? '📎';
}

// Función para verificar si es imagen
function isImage($extension) {
    return in_array($extension, ['jpg', 'jpeg', 'png', 'gif']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitácora del Vale - MAGA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --maga-azul-oscuro: #1e3a5f;
            --maga-azul-medio: #2a4a6f;
            --maga-cyan: #0abde3;
            --maga-cyan-claro: #48d1ff;
            --maga-cyan-oscuro: #0097c7;
            --color-primario: #1e3a5f;
            --color-acento: #0abde3;
            --color-exito: #10b981;
            --color-advertencia: #f39c12;
            --color-peligro: #ef4444;
            --color-texto: #2d3748;
            --color-texto-claro: #718096;
            --bg-blanco: #ffffff;
            --sombra-grande: 0 10px 40px rgba(0,0,0,0.15);
            --transicion: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--maga-azul-oscuro) 0%, var(--maga-azul-medio) 50%, var(--maga-cyan-oscuro) 100%);
            min-height: 100vh;
            padding: 20px;
            animation: gradientShift 15s ease infinite;
            background-size: 200% 200%;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* BARRA DE USUARIO */
        .user-bar {
            background: var(--bg-blanco);
            border-radius: 12px;
            padding: 15px 25px;
            margin-bottom: 20px;
            box-shadow: var(--sombra-grande);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            animation: slideDown 0.4s ease-out;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--maga-cyan) 0%, var(--maga-cyan-claro) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            box-shadow: 0 4px 12px rgba(10, 189, 227, 0.3);
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--color-texto);
            font-size: 15px;
        }
        
        .user-role {
            font-size: 12px;
            color: var(--color-texto-claro);
        }
        
        .role-badge {
            background: linear-gradient(135deg, var(--maga-azul-oscuro) 0%, var(--maga-azul-medio) 100%);
            color: white;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .role-badge.admin {
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
        }
        
        .user-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transicion);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            background: #f8fafc;
            color: var(--maga-azul-oscuro);
            border: 2px solid #e2e8f0;
        }
        
        .btn-action:hover {
            background: var(--maga-azul-oscuro);
            color: white;
            border-color: var(--maga-azul-oscuro);
            transform: translateY(-1px);
        }

        .header-card {
            background: var(--bg-blanco);
            border-radius: 16px;
            padding: 30px 40px;
            margin-bottom: 30px;
            box-shadow: var(--sombra-grande);
            position: relative;
            overflow: hidden;
        }

        .header-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--color-primario) 0%, var(--color-acento) 100%);
        }

        .header-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--color-texto-claro);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--color-primario);
        }

        .estado-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .estado-pendiente {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .estado-liquidado {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            animation: shake 0.5s ease-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0.08) 100%);
            color: #047857;
            border: 2px solid #10b981;
        }

        .alert-icon {
            font-size: 20px;
            margin-right: 12px;
        }

        .observacion-form {
            background: var(--bg-blanco);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--sombra-grande);
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--color-primario);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
            transition: var(--transicion);
            background: #fafafa;
        }

        textarea:focus {
            outline: none;
            border-color: var(--color-acento);
            background: white;
            box-shadow: 0 0 0 4px rgba(10, 189, 227, 0.1);
        }

        /* UPLOAD AREA */
        .upload-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed #e2e8f0;
        }

        .upload-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--color-texto);
            margin-bottom: 12px;
            display: block;
        }

        .upload-area {
            border: 2px dashed #cbd5e0;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            transition: var(--transicion);
            cursor: pointer;
            background: #fafafa;
        }

        .upload-area:hover,
        .upload-area.dragover {
            border-color: var(--maga-cyan);
            background: rgba(10, 189, 227, 0.05);
        }

        .upload-area input[type="file"] {
            display: none;
        }

        .upload-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .upload-text {
            font-size: 15px;
            color: var(--color-texto);
            margin-bottom: 8px;
        }

        .upload-hint {
            font-size: 13px;
            color: var(--color-texto-claro);
        }

        .file-list {
            margin-top: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .file-item {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #e2e8f0;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
        }

        .file-item .remove-file {
            cursor: pointer;
            color: var(--color-peligro);
            font-weight: bold;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transicion);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--maga-cyan) 0%, var(--maga-cyan-claro) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(10, 189, 227, 0.4);
        }

        .btn-secondary {
            background: #f8fafc;
            color: var(--color-texto);
            border: 2px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            transform: translateY(-1px);
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .timeline-container {
            background: var(--bg-blanco);
            border-radius: 16px;
            padding: 30px;
            box-shadow: var(--sombra-grande);
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }

        .timeline-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--color-primario);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .timeline {
            position: relative;
            padding-left: 40px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(180deg, var(--maga-cyan) 0%, var(--maga-cyan-claro) 100%);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            border-left: 4px solid var(--color-acento);
            transition: var(--transicion);
        }

        .timeline-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -43px;
            top: 25px;
            width: 14px;
            height: 14px;
            background: var(--maga-cyan);
            border: 3px solid white;
            border-radius: 50%;
            box-shadow: 0 0 0 4px rgba(10, 189, 227, 0.2);
        }

        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .accion-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .accion-creado {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .accion-editado {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
        }

        .accion-cambio-estado, .accion-cambio_estado {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
        }

        .accion-observacion {
            background: linear-gradient(135deg, #0abde3 0%, #48d1ff 100%);
            color: white;
        }

        .timeline-fecha {
            font-size: 13px;
            color: var(--color-texto-claro);
            font-weight: 500;
        }

        .timeline-content {
            color: var(--color-texto);
            line-height: 1.6;
        }

        .timeline-usuario {
            font-size: 13px;
            color: var(--color-texto-claro);
            margin-top: 8px;
            font-style: italic;
        }

        .cambio-detalle {
            background: white;
            padding: 12px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 14px;
        }

        .cambio-label {
            font-weight: 600;
            color: var(--color-primario);
            margin-right: 8px;
        }

        .valor-anterior {
            color: #ef4444;
            text-decoration: line-through;
        }

        .valor-nuevo {
            color: #10b981;
            font-weight: 600;
        }

        /* ARCHIVOS EN TIMELINE */
        .archivos-adjuntos {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .archivos-titulo {
            font-size: 13px;
            font-weight: 600;
            color: var(--color-texto-claro);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .archivos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }

        .archivo-card {
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            transition: var(--transicion);
            cursor: pointer;
        }

        .archivo-card:hover {
            border-color: var(--maga-cyan);
            box-shadow: 0 4px 12px rgba(10, 189, 227, 0.15);
            transform: translateY(-2px);
        }

        .archivo-icon {
            font-size: 28px;
            flex-shrink: 0;
        }

        .archivo-info {
            flex: 1;
            min-width: 0;
        }

        .archivo-nombre {
            font-size: 13px;
            font-weight: 600;
            color: var(--color-texto);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .archivo-meta {
            font-size: 11px;
            color: var(--color-texto-claro);
            margin-top: 2px;
        }

        .no-registros {
            text-align: center;
            padding: 60px 40px;
            color: var(--color-texto-claro);
        }

        .no-registros h3 {
            font-size: 24px;
            color: var(--color-primario);
            margin-bottom: 12px;
        }

        /* MODAL */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            padding: 20px;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 900px;
            max-height: 90vh;
            overflow: hidden;
            transform: scale(0.9) translateY(20px);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .modal-overlay.active .modal {
            transform: scale(1) translateY(0);
        }

        .modal-header {
            padding: 20px 25px;
            background: linear-gradient(135deg, var(--maga-azul-oscuro) 0%, var(--maga-azul-medio) 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .modal-header h3 {
            color: white;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transicion);
            flex-shrink: 0;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            flex: 1;
            overflow: auto;
            background: #f1f5f9;
        }

        .modal-body iframe {
            width: 100%;
            height: 70vh;
            border: none;
        }

        .modal-body img {
            max-width: 100%;
            max-height: 70vh;
            display: block;
            margin: 20px auto;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        .modal-body .no-preview {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 40px;
            text-align: center;
        }

        .no-preview .icon {
            font-size: 72px;
            margin-bottom: 20px;
        }

        .no-preview h4 {
            font-size: 20px;
            color: var(--color-primario);
            margin-bottom: 10px;
        }

        .no-preview p {
            color: var(--color-texto-claro);
            margin-bottom: 20px;
        }

        .modal-footer {
            padding: 15px 25px;
            background: white;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            body { padding: 15px; }
            .user-bar {
                flex-direction: column;
                text-align: center;
            }
            .user-info {
                flex-direction: column;
            }
            .header-card { padding: 20px 25px; }
            .timeline { padding-left: 30px; }
            .timeline::before { left: 10px; }
            .timeline-item::before { left: -38px; }
            .archivos-grid { grid-template-columns: 1fr; }
            .modal { max-width: 100%; margin: 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <!-- BARRA DE USUARIO -->
        <div class="user-bar">
            <div class="user-info">
                <div class="user-avatar">👤</div>
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($usuarioActual['nombre_completo']); ?></span>
                    <span class="user-role">
                        <span class="role-badge <?php echo $usuarioActual['rol'] === 'ADMIN' ? 'admin' : ''; ?>">
                            <?php echo $usuarioActual['rol'] === 'ADMIN' ? 'Administrador' : 'Usuario'; ?>
                        </span>
                    </span>
                </div>
            </div>
            <div class="user-actions">
                <a href="index.php" class="btn-action">Inicio</a>
                <a href="listar_vales.php" class="btn-action">Listado</a>
                <a href="logout.php" class="btn-action">Cerrar Sesión</a>
            </div>
        </div>
        
        <!-- HEADER -->
        <div class="header-card">
            <h1 style="font-size: 28px; font-weight: 700; color: var(--color-primario); margin-bottom: 8px;">
                Bitácora del Vale
            </h1>
            <p style="font-size: 16px; color: var(--color-texto-claro); margin-bottom: 20px;">
                Historial de cambios, observaciones y documentos adjuntos
            </p>
            
            <div class="header-info">
                <div class="info-item">
                    <span class="info-label">Número de Vale</span>
                    <span class="info-value"><?php echo htmlspecialchars($vale['numero_vale']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Solicitante</span>
                    <span class="info-value"><?php echo htmlspecialchars($vale['nombre_solicitante']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Departamento</span>
                    <span class="info-value"><?php echo htmlspecialchars($vale['departamento']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Estado Actual</span>
                    <span class="info-value">
                        <?php
                        $estado = isset($vale['estado']) ? $vale['estado'] : 'PENDIENTE';
                        $clase_estado = ($estado === 'LIQUIDADO') ? 'estado-liquidado' : 'estado-pendiente';
                        ?>
                        <span class="estado-badge <?php echo $clase_estado; ?>">
                            <?php echo $estado; ?>
                        </span>
                    </span>
                </div>
            </div>
        </div>

        <!-- MENSAJES -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <span class="alert-icon">✅</span>
                <strong><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></strong>
            </div>
        <?php endif; ?>

        <!-- FORMULARIO DE NUEVA OBSERVACIÓN -->
        <div class="observacion-form">
            <div class="form-title">
                <span>✍️</span>
                Agregar Nueva Observación
            </div>
            <form method="POST" enctype="multipart/form-data" id="formObservacion">
                <textarea name="nueva_observacion" placeholder="Escriba aquí su observación..."></textarea>
                
                <div class="upload-section">
                    <label class="upload-label">📎 Adjuntar Archivos (opcional)</label>
                    <div class="upload-area" id="uploadArea">
                        <input type="file" name="archivos[]" id="inputArchivos" multiple 
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                        <div class="upload-icon">📁</div>
                        <div class="upload-text">Arrastra archivos aquí o haz clic para seleccionar</div>
                        <div class="upload-hint">PDF, Word, Excel, Imágenes • Máximo 10MB por archivo</div>
                    </div>
                    <div class="file-list" id="fileList"></div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="listar_vales.php" class="btn btn-secondary">Volver al Listado</a>
                </div>
            </form>
        </div>

        <!-- TIMELINE DE BITÁCORA -->
        <div class="timeline-container">
            <div class="timeline-title">
                <span>📜</span>
                Historial de Cambios
            </div>
            
            <?php if (count($registros) > 0): ?>
                <div class="timeline">
                    <?php foreach ($registros as $registro): ?>
                        <div class="timeline-item">
                            <div class="timeline-header">
                                <span class="accion-badge accion-<?php echo strtolower(str_replace('_', '-', $registro['accion'])); ?>">
                                    <?php echo htmlspecialchars($registro['accion']); ?>
                                </span>
                                <span class="timeline-fecha">
                                    <?php echo date('d/m/Y H:i:s', strtotime($registro['fecha_registro'])); ?>
                                </span>
                            </div>
                            
                            <div class="timeline-content">
                                <?php if ($registro['accion'] === 'OBSERVACION'): ?>
                                    <p><?php echo nl2br(htmlspecialchars($registro['observacion'])); ?></p>
                                
                                <?php elseif ($registro['accion'] === 'CAMBIO_ESTADO'): ?>
                                    <div class="cambio-detalle">
                                        <span class="cambio-label">Estado cambió de:</span>
                                        <span class="valor-anterior"><?php echo htmlspecialchars($registro['estado_anterior']); ?></span>
                                        →
                                        <span class="valor-nuevo"><?php echo htmlspecialchars($registro['estado_nuevo']); ?></span>
                                    </div>
                                    <?php if ($registro['observacion']): ?>
                                        <p style="margin-top: 10px;"><strong>Nota:</strong> <?php echo htmlspecialchars($registro['observacion']); ?></p>
                                    <?php endif; ?>
                                
                                <?php elseif ($registro['accion'] === 'EDITADO'): ?>
                                    <?php if ($registro['campo_modificado']): ?>
                                        <div class="cambio-detalle">
                                            <span class="cambio-label"><?php echo htmlspecialchars($registro['campo_modificado']); ?>:</span>
                                            <span class="valor-anterior"><?php echo htmlspecialchars($registro['valor_anterior']); ?></span>
                                            →
                                            <span class="valor-nuevo"><?php echo htmlspecialchars($registro['valor_nuevo']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($registro['observacion']): ?>
                                        <p style="margin-top: 10px;"><?php echo htmlspecialchars($registro['observacion']); ?></p>
                                    <?php endif; ?>
                                
                                <?php elseif ($registro['accion'] === 'CREADO'): ?>
                                    <p>Vale creado en el sistema</p>
                                    <?php if ($registro['observacion']): ?>
                                        <p style="margin-top: 8px;"><?php echo htmlspecialchars($registro['observacion']); ?></p>
                                    <?php endif; ?>
                                
                                <?php else: ?>
                                    <?php if ($registro['observacion']): ?>
                                        <p><?php echo htmlspecialchars($registro['observacion']); ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            
                            <!-- ARCHIVOS ADJUNTOS -->
                            <?php if (isset($archivos_por_registro[$registro['id']]) && count($archivos_por_registro[$registro['id']]) > 0): ?>
                                <div class="archivos-adjuntos">
                                    <div class="archivos-titulo">
                                        <span>📎</span>
                                        Archivos adjuntos (<?php echo count($archivos_por_registro[$registro['id']]); ?>)
                                    </div>
                                    <div class="archivos-grid">
                                        <?php foreach ($archivos_por_registro[$registro['id']] as $archivo): ?>
                                            <div class="archivo-card" onclick="abrirModal('<?php echo htmlspecialchars($archivo['nombre_archivo']); ?>', '<?php echo htmlspecialchars($archivo['nombre_original']); ?>', '<?php echo $archivo['extension']; ?>')">
                                                <span class="archivo-icon"><?php echo getFileIcon($archivo['extension']); ?></span>
                                                <div class="archivo-info">
                                                    <div class="archivo-nombre"><?php echo htmlspecialchars($archivo['nombre_original']); ?></div>
                                                    <div class="archivo-meta"><?php echo formatFileSize($archivo['tamano']); ?> • <?php echo strtoupper($archivo['extension']); ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="timeline-usuario">
                                👤 <?php echo htmlspecialchars($registro['usuario']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-registros">
                    <h3>No hay registros en la bitácora</h3>
                    <p>Este vale aún no tiene historial de cambios</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL PARA VER ARCHIVOS -->
    <div class="modal-overlay" id="modalArchivo">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalTitulo">
                    <span id="modalIcon">📄</span>
                    <span id="modalNombre">Archivo</span>
                </h3>
                <button type="button" class="modal-close" onclick="cerrarModal()">×</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <a href="#" id="btnDescargar" class="btn btn-primary" download>Descargar</a>
                <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        // === UPLOAD AREA ===
        const uploadArea = document.getElementById('uploadArea');
        const inputArchivos = document.getElementById('inputArchivos');
        const fileList = document.getElementById('fileList');
        let archivosSeleccionados = [];

        uploadArea.addEventListener('click', () => inputArchivos.click());

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });

        inputArchivos.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });

        function handleFiles(files) {
            const maxSize = 10 * 1024 * 1024; // 10MB
            const allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'];
            
            Array.from(files).forEach(file => {
                const ext = file.name.split('.').pop().toLowerCase();
                
                if (!allowedTypes.includes(ext)) {
                    alert(`Tipo de archivo no permitido: ${file.name}`);
                    return;
                }
                
                if (file.size > maxSize) {
                    alert(`El archivo ${file.name} excede el límite de 10MB`);
                    return;
                }
                
                archivosSeleccionados.push(file);
            });
            
            renderFileList();
            updateInputFiles();
        }

        function renderFileList() {
            fileList.innerHTML = archivosSeleccionados.map((file, index) => `
                <div class="file-item">
                    <span>${getIcon(file.name)}</span>
                    <span>${file.name}</span>
                    <span>(${formatSize(file.size)})</span>
                    <span class="remove-file" onclick="removeFile(${index})">✕</span>
                </div>
            `).join('');
        }

        function removeFile(index) {
            archivosSeleccionados.splice(index, 1);
            renderFileList();
            updateInputFiles();
        }

        function updateInputFiles() {
            const dt = new DataTransfer();
            archivosSeleccionados.forEach(file => dt.items.add(file));
            inputArchivos.files = dt.files;
        }

        function getIcon(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            const icons = {
                'pdf': '📄', 'doc': '📝', 'docx': '📝',
                'xls': '📊', 'xlsx': '📊',
                'jpg': '🖼️', 'jpeg': '🖼️', 'png': '🖼️', 'gif': '🖼️'
            };
            return icons[ext] || '📎';
        }

        function formatSize(bytes) {
            if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
            if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
            return bytes + ' bytes';
        }

        // === MODAL ===
        function abrirModal(nombreArchivo, nombreOriginal, extension) {
            const modal = document.getElementById('modalArchivo');
            const modalBody = document.getElementById('modalBody');
            const modalNombre = document.getElementById('modalNombre');
            const modalIcon = document.getElementById('modalIcon');
            const btnDescargar = document.getElementById('btnDescargar');
            
            const ruta = 'uploads/bitacora/' + nombreArchivo;
            
            modalNombre.textContent = nombreOriginal;
            btnDescargar.href = ruta;
            
            const icons = {
                'pdf': '📄', 'doc': '📝', 'docx': '📝',
                'xls': '📊', 'xlsx': '📊',
                'jpg': '🖼️', 'jpeg': '🖼️', 'png': '🖼️', 'gif': '🖼️'
            };
            modalIcon.textContent = icons[extension] || '📎';
            
            // Contenido según tipo
            if (extension === 'pdf') {
                modalBody.innerHTML = `<iframe src="${ruta}"></iframe>`;
            } else if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
                modalBody.innerHTML = `<img src="${ruta}" alt="${nombreOriginal}">`;
            } else {
                // Word, Excel - no preview directo
                modalBody.innerHTML = `
                    <div class="no-preview">
                        <div class="icon">${icons[extension] || '📎'}</div>
                        <h4>${nombreOriginal}</h4>
                        <p>Vista previa no disponible para este tipo de archivo.</p>
                        <a href="${ruta}" class="btn btn-primary" download>Descargar Archivo</a>
                    </div>
                `;
            }
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function cerrarModal() {
            document.getElementById('modalArchivo').classList.remove('active');
            document.body.style.overflow = '';
        }

        // Cerrar con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') cerrarModal();
        });

        // Cerrar al hacer clic fuera
        document.getElementById('modalArchivo').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) cerrarModal();
        });

        // Validar formulario
        document.getElementById('formObservacion').addEventListener('submit', function(e) {
            const observacion = this.querySelector('textarea').value.trim();
            const archivos = inputArchivos.files.length;
            
            if (!observacion && archivos === 0) {
                e.preventDefault();
                alert('Debe escribir una observación o adjuntar al menos un archivo.');
            }
        });
    </script>
</body>
</html>