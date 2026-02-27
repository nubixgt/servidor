<?php
/**
 * API REST - Proveedores
 * Sistema de Supervisión v6.0.5
 * 
 * Métodos soportados:
 * - GET: Obtener todos los proveedores o uno específico
 * - POST: Crear nuevo proveedor
 * - PUT: Actualizar proveedor existente
 * - DELETE: Eliminar proveedor
 */

// Activar reporte de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar en pantalla
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Determinar la ruta base del proyecto (solo 1 nivel arriba desde /api/)
$baseDir = dirname(__DIR__); // Sube 1 nivel desde /api/ hasta /SistemaSupervision/

// Verificar que el archivo config existe
$configPath = $baseDir . '/config/config.php';

if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: No se encontró el archivo de configuración',
        'debug' => [
            'buscando_en' => $configPath,
            'directorio_actual' => __DIR__,
            'base_dir_calculado' => $baseDir,
            'existe_config_dir' => is_dir($baseDir . '/config'),
            'archivos_en_config' => is_dir($baseDir . '/config') ? scandir($baseDir . '/config') : 'directorio no existe'
        ]
    ]);
    exit;
}

require_once $configPath;

// Verificar que el usuario esté autenticado y sea admin
try {
    if (!function_exists('requireAdmin')) {
        throw new Exception('Función requireAdmin no existe. Verifica config.php');
    }
    requireAdmin();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado: ' . $e->getMessage(),
        'debug' => [
            'file' => __FILE__,
            'session_started' => session_status() === PHP_SESSION_ACTIVE
        ]
    ]);
    exit;
}

// Verificar conexión a base de datos
try {
    $db = Database::getInstance()->getConnection();
    if (!$db) {
        throw new Exception('No se pudo conectar a la base de datos');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a BD: ' . $e->getMessage()
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

/**
 * Función para limpiar datos de entrada
 */
function limpiarDato($dato) {
    if (is_null($dato) || $dato === '') {
        return null;
    }
    return trim($dato);
}

try {
    switch ($method) {
        case 'GET':
            // Obtener proveedores
            if (isset($_GET['id'])) {
                // Obtener un proveedor específico
                $id = intval($_GET['id']);
                
                $stmt = $db->prepare("
                    SELECT 
                        id,
                        nombre,
                        nit,
                        telefono,
                        observaciones,
                        estado,
                        fechaCreacion,
                        fechaModificacion
                    FROM proveedores
                    WHERE id = :id
                ");
                
                $stmt->execute(['id' => $id]);
                $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($proveedor) {
                    echo json_encode([
                        'success' => true,
                        'data' => $proveedor
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Proveedor no encontrado'
                    ]);
                }
            } else {
                // Obtener todos los proveedores
                $stmt = $db->query("
                    SELECT 
                        id,
                        nombre,
                        nit,
                        telefono,
                        observaciones,
                        estado,
                        fechaCreacion,
                        fechaModificacion
                    FROM proveedores
                    ORDER BY fechaCreacion DESC
                ");
                
                $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'data' => $proveedores,
                    'total' => count($proveedores)
                ]);
            }
            break;
            
        case 'POST':
            // Crear nuevo proveedor
            $nombre = limpiarDato($_POST['nombre'] ?? '');
            $nit = limpiarDato($_POST['nit'] ?? '');
            $telefono = limpiarDato($_POST['telefono'] ?? '');
            $observaciones = limpiarDato($_POST['observaciones'] ?? '');
            $estado = limpiarDato($_POST['estado'] ?? 'activo');
            
            // Validaciones
            if (empty($nombre)) {
                throw new Exception('El nombre es obligatorio');
            }
            
            // Validar teléfono si se proporciona (debe tener 8 dígitos)
            if (!empty($telefono) && !preg_match('/^[0-9]{8}$/', $telefono)) {
                throw new Exception('El teléfono debe tener exactamente 8 dígitos');
            }
            
            // Insertar proveedor
            $stmt = $db->prepare("
                INSERT INTO proveedores (
                    nombre,
                    nit,
                    telefono,
                    observaciones,
                    estado
                ) VALUES (
                    :nombre,
                    :nit,
                    :telefono,
                    :observaciones,
                    :estado
                )
            ");
            
            $resultado = $stmt->execute([
                'nombre' => $nombre,
                'nit' => $nit,
                'telefono' => $telefono,
                'observaciones' => $observaciones,
                'estado' => $estado
            ]);
            
            if ($resultado) {
                $nuevoId = $db->lastInsertId();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Proveedor creado exitosamente',
                    'id' => $nuevoId
                ]);
            } else {
                throw new Exception('Error al crear el proveedor');
            }
            break;
            
        case 'PUT':
            // Actualizar proveedor existente
            parse_str(file_get_contents("php://input"), $_PUT);
            
            $id = intval($_PUT['id'] ?? $_POST['id'] ?? 0);
            $nombre = limpiarDato($_PUT['nombre'] ?? $_POST['nombre'] ?? '');
            $nit = limpiarDato($_PUT['nit'] ?? $_POST['nit'] ?? '');
            $telefono = limpiarDato($_PUT['telefono'] ?? $_POST['telefono'] ?? '');
            $observaciones = limpiarDato($_PUT['observaciones'] ?? $_POST['observaciones'] ?? '');
            $estado = limpiarDato($_PUT['estado'] ?? $_POST['estado'] ?? 'activo');
            
            // Validaciones
            if ($id <= 0) {
                throw new Exception('ID de proveedor inválido');
            }
            
            if (empty($nombre)) {
                throw new Exception('El nombre es obligatorio');
            }
            
            // Validar teléfono si se proporciona
            if (!empty($telefono) && !preg_match('/^[0-9]{8}$/', $telefono)) {
                throw new Exception('El teléfono debe tener exactamente 8 dígitos');
            }
            
            // Verificar que el proveedor existe
            $stmt = $db->prepare("SELECT id FROM proveedores WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            if (!$stmt->fetch()) {
                http_response_code(404);
                throw new Exception('Proveedor no encontrado');
            }
            
            // Actualizar proveedor
            $stmt = $db->prepare("
                UPDATE proveedores SET
                    nombre = :nombre,
                    nit = :nit,
                    telefono = :telefono,
                    observaciones = :observaciones,
                    estado = :estado
                WHERE id = :id
            ");
            
            $resultado = $stmt->execute([
                'id' => $id,
                'nombre' => $nombre,
                'nit' => $nit,
                'telefono' => $telefono,
                'observaciones' => $observaciones,
                'estado' => $estado
            ]);
            
            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Proveedor actualizado exitosamente'
                ]);
            } else {
                throw new Exception('Error al actualizar el proveedor');
            }
            break;
            
        case 'DELETE':
            // Eliminar proveedor
            $id = intval($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('ID de proveedor inválido');
            }
            
            // Verificar que el proveedor existe
            $stmt = $db->prepare("SELECT id FROM proveedores WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            if (!$stmt->fetch()) {
                http_response_code(404);
                throw new Exception('Proveedor no encontrado');
            }
            
            // NOTA: Verificar si el proveedor está siendo usado en inventario
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM inventario WHERE proveedor_id = :id");
            $stmt->execute(['id' => $id]);
            $enUso = $stmt->fetch()['total'];
            
            if ($enUso > 0) {
                throw new Exception('No se puede eliminar el proveedor porque está siendo usado en ' . $enUso . ' equipo(s) del inventario');
            }
            
            // Eliminar proveedor
            $stmt = $db->prepare("DELETE FROM proveedores WHERE id = :id");
            $resultado = $stmt->execute(['id' => $id]);
            
            if ($resultado) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Proveedor eliminado exitosamente'
                ]);
            } else {
                throw new Exception('Error al eliminar el proveedor');
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            break;
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error en API Proveedores: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>