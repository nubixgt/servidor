<?php
/**
 * API REST - MANEJO DE INVENTARIO
 * Sistema de Supervisión v6.0.6
 * Gestión de Salidas e Ingresos de Bodega
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

// Verificar autenticación
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Usar la variable de sesión correcta
$usuarioId = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;

// Obtener conexión a la base de datos
$db = Database::getInstance()->getConnection();

// Obtener método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// ⭐ IMPORTANTE: Si viene _method=PUT en POST, cambiar el método a PUT
if ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
    $method = 'PUT';
}

// Directorio de uploads
$uploadDir = dirname(__DIR__) . '/public/uploads/manejo_inventario/';

// Crear directorio si no existe
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

try {
    switch ($method) {
        case 'GET':
            handleGet($db);
            break;
        
        case 'POST':
            handlePost($db, $uploadDir, $usuarioId);
            break;
        
        case 'PUT':
            handlePut($db, $uploadDir);
            break;
        
        case 'DELETE':
            handleDelete($db, $uploadDir);
            break;
        
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}

/**
 * GET - Obtener movimientos
 */
function handleGet($db) {
    if (isset($_GET['id'])) {
        // Obtener un movimiento específico con sus fotos
        $id = (int)$_GET['id'];
        
        // Datos del movimiento
        $stmt = $db->prepare("
            SELECT 
                mi.*,
                p.nombre as proyecto_nombre,
                t.nombre as trabajador_nombre,
                u.usuario as usuario_creador
            FROM manejo_inventario mi
            LEFT JOIN proyectos p ON mi.proyecto_id = p.id
            LEFT JOIN trabajadores t ON mi.trabajador_id = t.id
            LEFT JOIN usuarios u ON mi.usuario_id = u.id
            WHERE mi.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $movimiento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$movimiento) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Movimiento no encontrado']);
            return;
        }
        
        // Obtener fotos
        $stmtFotos = $db->prepare("
            SELECT * FROM manejo_inventario_fotografias 
            WHERE manejo_id = :manejo_id 
            ORDER BY orden ASC
        ");
        $stmtFotos->execute(['manejo_id' => $id]);
        $movimiento['fotografias'] = $stmtFotos->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $movimiento
        ]);
    } else {
        // Obtener todos los movimientos
        $stmt = $db->query("
            SELECT 
                mi.id,
                mi.producto,
                mi.tipo_gestion,
                mi.fecha_entrega,
                mi.observaciones,
                mi.fecha_creacion,
                p.nombre as proyecto_nombre,
                t.nombre as trabajador_nombre,
                u.usuario as usuario_creador,
                COUNT(mf.id) as total_fotos
            FROM manejo_inventario mi
            LEFT JOIN proyectos p ON mi.proyecto_id = p.id
            LEFT JOIN trabajadores t ON mi.trabajador_id = t.id
            LEFT JOIN usuarios u ON mi.usuario_id = u.id
            LEFT JOIN manejo_inventario_fotografias mf ON mi.id = mf.manejo_id
            GROUP BY mi.id
            ORDER BY mi.fecha_creacion DESC
        ");
        $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $movimientos
        ]);
    }
}

/**
 * POST - Crear nuevo movimiento
 */
function handlePost($db, $uploadDir, $usuarioId) {
    // Validar campos obligatorios
    $requiredFields = ['producto', 'tipo_gestion', 'proyecto_id', 'trabajador_id', 'fecha_entrega'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "El campo {$field} es obligatorio"
            ]);
            return;
        }
    }
    
    // Validar que haya al menos 1 foto
    if (!isset($_FILES['fotografias']) || empty($_FILES['fotografias']['name'][0])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Debe subir al menos 1 fotografía'
        ]);
        return;
    }
    
    // Validar máximo 2 fotos
    $totalFotos = count(array_filter($_FILES['fotografias']['name']));
    if ($totalFotos > 2) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Máximo 2 fotografías permitidas'
        ]);
        return;
    }
    
    // Capturar datos
    $producto = trim($_POST['producto']);
    $tipoGestion = trim($_POST['tipo_gestion']);
    $proyectoId = (int)$_POST['proyecto_id'];
    $trabajadorId = (int)$_POST['trabajador_id'];
    $fechaEntrega = $_POST['fecha_entrega'];
    $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : null;
    
    try {
        $db->beginTransaction();
        
        // Insertar movimiento
        $stmt = $db->prepare("
            INSERT INTO manejo_inventario 
            (usuario_id, producto, tipo_gestion, proyecto_id, trabajador_id, fecha_entrega, observaciones)
            VALUES 
            (:usuario_id, :producto, :tipo_gestion, :proyecto_id, :trabajador_id, :fecha_entrega, :observaciones)
        ");
        
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'producto' => $producto,
            'tipo_gestion' => $tipoGestion,
            'proyecto_id' => $proyectoId,
            'trabajador_id' => $trabajadorId,
            'fecha_entrega' => $fechaEntrega,
            'observaciones' => $observaciones
        ]);
        
        $manejoId = $db->lastInsertId();
        
        // Procesar fotografías
        $fotosSubidas = procesarFotografias($_FILES['fotografias'], $manejoId, $uploadDir, $db);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Movimiento registrado exitosamente',
            'id' => $manejoId,
            'fotos_subidas' => $fotosSubidas
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * PUT - Actualizar movimiento
 */
function handlePut($db, $uploadDir) {
    // El método PUT con archivos viene como POST con _method=PUT
    // Los datos están en $_POST y los archivos en $_FILES
    
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID requerido']);
        return;
    }
    
    $id = (int)$_POST['id'];
    
    // Verificar que existe
    $stmt = $db->prepare("SELECT id FROM manejo_inventario WHERE id = :id");
    $stmt->execute(['id' => $id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Movimiento no encontrado']);
        return;
    }
    
    try {
        $db->beginTransaction();
        
        // Capturar datos
        $producto = trim($_POST['producto']);
        $tipoGestion = trim($_POST['tipo_gestion']);
        $proyectoId = (int)$_POST['proyecto_id'];
        $trabajadorId = (int)$_POST['trabajador_id'];
        $fechaEntrega = $_POST['fecha_entrega'];
        $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : null;
        
        // Actualizar datos del movimiento
        $stmt = $db->prepare("
            UPDATE manejo_inventario 
            SET producto = :producto,
                tipo_gestion = :tipo_gestion,
                proyecto_id = :proyecto_id,
                trabajador_id = :trabajador_id,
                fecha_entrega = :fecha_entrega,
                observaciones = :observaciones
            WHERE id = :id
        ");
        
        $stmt->execute([
            'id' => $id,
            'producto' => $producto,
            'tipo_gestion' => $tipoGestion,
            'proyecto_id' => $proyectoId,
            'trabajador_id' => $trabajadorId,
            'fecha_entrega' => $fechaEntrega,
            'observaciones' => $observaciones
        ]);
        
        // Procesar nuevas fotografías si se enviaron
        $fotosSubidas = 0;
        if (isset($_FILES['fotografias']) && !empty($_FILES['fotografias']['name'][0])) {
            // Contar cuántas fotos ya tiene
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM manejo_inventario_fotografias WHERE manejo_id = :id");
            $stmt->execute(['id' => $id]);
            $fotosExistentes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Validar que no exceda el máximo (2 fotos)
            $fotosNuevas = count(array_filter($_FILES['fotografias']['name']));
            if ($fotosExistentes + $fotosNuevas > 2) {
                throw new Exception('Máximo 2 fotografías permitidas. Ya tienes ' . $fotosExistentes . ' foto(s).');
            }
            
            // Procesar y subir nuevas fotos
            $fotosSubidas = procesarFotografias($_FILES['fotografias'], $id, $uploadDir, $db, $fotosExistentes);
        }
        
        $db->commit();
        
        $mensaje = 'Movimiento actualizado exitosamente';
        if ($fotosSubidas > 0) {
            $mensaje .= '. Se agregaron ' . $fotosSubidas . ' fotografía(s) nueva(s)';
        }
        
        echo json_encode([
            'success' => true,
            'message' => $mensaje
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * DELETE - Eliminar movimiento o foto individual
 */
function handleDelete($db, $uploadDir) {
    parse_str(file_get_contents("php://input"), $_DELETE);
    
    // Verificar si es eliminación de foto individual
    if (isset($_DELETE['action']) && $_DELETE['action'] === 'delete_foto') {
        eliminarFotoIndividual($_DELETE, $db, $uploadDir);
        return;
    }
    
    // Eliminación de movimiento completo
    if (!isset($_DELETE['id']) || empty($_DELETE['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID requerido']);
        return;
    }
    
    $id = (int)$_DELETE['id'];
    
    try {
        $db->beginTransaction();
        
        // Obtener fotos para eliminar archivos físicos
        $stmt = $db->prepare("
            SELECT ruta_archivo 
            FROM manejo_inventario_fotografias 
            WHERE manejo_id = :manejo_id
        ");
        $stmt->execute(['manejo_id' => $id]);
        $fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Eliminar archivos físicos
        foreach ($fotos as $foto) {
            $rutaCompleta = dirname(__DIR__) . $foto['ruta_archivo'];
            if (file_exists($rutaCompleta)) {
                unlink($rutaCompleta);
            }
        }
        
        // Eliminar registros de fotos (CASCADE lo hace automáticamente)
        // Eliminar movimiento
        $stmt = $db->prepare("DELETE FROM manejo_inventario WHERE id = :id");
        $result = $stmt->execute(['id' => $id]);
        
        $db->commit();
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Movimiento eliminado exitosamente'
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Movimiento no encontrado'
            ]);
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Eliminar una foto individual
 */
function eliminarFotoIndividual($data, $db, $uploadDir) {
    if (!isset($data['foto_id']) || empty($data['foto_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de foto requerido']);
        return;
    }
    
    $fotoId = (int)$data['foto_id'];
    
    try {
        $db->beginTransaction();
        
        // Obtener ruta de la foto
        $stmt = $db->prepare("SELECT ruta_archivo FROM manejo_inventario_fotografias WHERE id = :id");
        $stmt->execute(['id' => $fotoId]);
        $foto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$foto) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Foto no encontrada']);
            return;
        }
        
        // Eliminar archivo físico
        $rutaCompleta = dirname(__DIR__) . $foto['ruta_archivo'];
        if (file_exists($rutaCompleta)) {
            unlink($rutaCompleta);
        }
        
        // Eliminar registro de la BD
        $stmt = $db->prepare("DELETE FROM manejo_inventario_fotografias WHERE id = :id");
        $stmt->execute(['id' => $fotoId]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Foto eliminada exitosamente'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Procesar y guardar fotografías
 */
function procesarFotografias($files, $manejoId, $uploadDir, $db, $ordenInicial = 0) {
    $fotosSubidas = 0;
    $orden = $ordenInicial + 1;
    
    // Extensiones y tipos MIME permitidos
    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $tiposMimePermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $tamañoMaximo = 5 * 1024 * 1024; // 5MB
    
    foreach ($files['tmp_name'] as $key => $tmpName) {
        // Verificar si se subió el archivo
        if (empty($tmpName) || !is_uploaded_file($tmpName)) {
            continue;
        }
        
        $nombreOriginal = $files['name'][$key];
        $tamañoArchivo = $files['size'][$key];
        $tipoArchivo = $files['type'][$key];
        
        // Validar tamaño
        if ($tamañoArchivo > $tamañoMaximo) {
            throw new Exception("La foto {$nombreOriginal} excede el tamaño máximo de 5MB");
        }
        
        // Validar tipo MIME
        if (!in_array($tipoArchivo, $tiposMimePermitidos)) {
            throw new Exception("Tipo de archivo no permitido: {$nombreOriginal}");
        }
        
        // Obtener extensión
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        
        // Validar extensión
        if (!in_array($extension, $extensionesPermitidas)) {
            throw new Exception("Extensión de archivo no permitida: {$extension}");
        }
        
        // Generar nombre único
        $nombreUnico = 'manejo_' . $manejoId . '_' . time() . '_' . $orden . '.' . $extension;
        $rutaDestino = $uploadDir . $nombreUnico;
        $rutaBD = '/public/uploads/manejo_inventario/' . $nombreUnico;
        
        // Mover archivo
        if (move_uploaded_file($tmpName, $rutaDestino)) {
            // Guardar en BD
            $stmt = $db->prepare("
                INSERT INTO manejo_inventario_fotografias 
                (manejo_id, nombre_archivo, ruta_archivo, tipo_archivo, tamanio_bytes, orden)
                VALUES 
                (:manejo_id, :nombre_archivo, :ruta_archivo, :tipo_archivo, :tamanio_bytes, :orden)
            ");
            
            $stmt->execute([
                'manejo_id' => $manejoId,
                'nombre_archivo' => $nombreOriginal,
                'ruta_archivo' => $rutaBD,
                'tipo_archivo' => $tipoArchivo,
                'tamanio_bytes' => $tamañoArchivo,
                'orden' => $orden
            ]);
            
            $fotosSubidas++;
            $orden++;
        } else {
            throw new Exception("Error al subir la foto: {$nombreOriginal}");
        }
    }
    
    return $fotosSubidas;
}