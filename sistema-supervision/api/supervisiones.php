<?php
// api/supervisiones.php
require_once '../config/config.php';

// ⭐ PERMITIR tanto admin como técnicos
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

// Función para limpiar datos
function limpiarDato($dato) {
    if (is_null($dato) || $dato === '') {
        return null;
    }
    return trim(strip_tags($dato));
}

$db = Database::getInstance()->getConnection();
$response = ['success' => false, 'message' => ''];

// Obtener información del usuario actual
$usuarioId = $_SESSION['user_id'] ?? null;
$usuarioRol = $_SESSION['rol'] ?? null;
$esAdmin = ($usuarioRol === ROLE_ADMIN);

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'POST':
            // CREAR nueva supervisión
            $proyecto_id = limpiarDato($_POST['proyecto_id'] ?? '');
            $contratista_id = limpiarDato($_POST['contratista_id'] ?? '');
            $trabajador_id = limpiarDato($_POST['trabajador_id'] ?? '');
            $telefono = limpiarDato($_POST['telefono'] ?? '');
            $observaciones = limpiarDato($_POST['observaciones'] ?? '');
            
            // Validar campos obligatorios
            if (empty($proyecto_id)) {
                throw new Exception('El proyecto es obligatorio');
            }
            
            if (empty($contratista_id)) {
                throw new Exception('El contratista es obligatorio');
            }
            
            if (empty($trabajador_id)) {
                throw new Exception('El trabajador es obligatorio');
            }
            
            if (empty($telefono)) {
                throw new Exception('El teléfono es obligatorio');
            }
            
            // Validar formato de teléfono (al menos 8 dígitos)
            $telefonoSinGuion = str_replace('-', '', $telefono);
            if (strlen($telefonoSinGuion) < 8 || !ctype_digit($telefonoSinGuion)) {
                throw new Exception('El teléfono debe tener al menos 8 dígitos');
            }
            
            // Verificar que el proyecto existe
            $stmt = $db->prepare("SELECT id FROM proyectos WHERE id = :id");
            $stmt->execute(['id' => $proyecto_id]);
            if (!$stmt->fetch()) {
                throw new Exception('El proyecto seleccionado no existe');
            }
            
            // Verificar que el contratista existe
            $stmt = $db->prepare("SELECT id FROM contratistas WHERE id = :id");
            $stmt->execute(['id' => $contratista_id]);
            if (!$stmt->fetch()) {
                throw new Exception('El contratista seleccionado no existe');
            }
            
            // Verificar que el trabajador existe
            $stmt = $db->prepare("SELECT id FROM trabajadores WHERE id = :id");
            $stmt->execute(['id' => $trabajador_id]);
            if (!$stmt->fetch()) {
                throw new Exception('El trabajador seleccionado no existe');
            }
            
            // ⭐ Insertar supervisión con el usuario_id actual
            $stmt = $db->prepare("
                INSERT INTO supervisiones 
                (proyecto_id, contratista_id, trabajador_id, usuario_id, telefono, fecha_supervision, estado, observaciones, fecha_creacion, fecha_modificacion) 
                VALUES 
                (:proyecto_id, :contratista_id, :trabajador_id, :usuario_id, :telefono, NOW(), 'pendiente', :observaciones, NOW(), NOW())
            ");
            
            $resultado = $stmt->execute([
                'proyecto_id' => $proyecto_id,
                'contratista_id' => $contratista_id,
                'trabajador_id' => $trabajador_id,
                'usuario_id' => $usuarioId, // ⭐ Guardar quién creó la supervisión
                'telefono' => $telefono,
                'observaciones' => $observaciones
            ]);
            
            if (!$resultado) {
                throw new Exception('Error al insertar en la base de datos');
            }
            
            $response['success'] = true;
            $response['message'] = 'Supervisión creada exitosamente';
            $response['id'] = $db->lastInsertId();
            
            error_log('✅ Supervisión creada: ID ' . $response['id'] . ' por usuario ' . $usuarioId);
            break;
            
        case 'GET':
            // ⭐ OBTENER supervisiones según rol
            if ($esAdmin) {
                // Admin ve TODAS las supervisiones
                $stmt = $db->query("
                    SELECT 
                        s.id,
                        s.fecha_supervision,
                        s.estado,
                        s.telefono,
                        s.observaciones,
                        s.usuario_id,
                        p.nombre as proyecto_nombre,
                        c.nombre as contratista_nombre,
                        t.nombre as trabajador_nombre,
                        u.usuario as creado_por
                    FROM supervisiones s
                    INNER JOIN proyectos p ON s.proyecto_id = p.id
                    INNER JOIN contratistas c ON s.contratista_id = c.id
                    INNER JOIN trabajadores t ON s.trabajador_id = t.id
                    LEFT JOIN usuarios u ON s.usuario_id = u.id
                    ORDER BY s.fecha_supervision DESC
                ");
            } else {
                // ⭐ Técnicos solo ven SUS supervisiones
                $stmt = $db->prepare("
                    SELECT 
                        s.id,
                        s.fecha_supervision,
                        s.estado,
                        s.telefono,
                        s.observaciones,
                        s.usuario_id,
                        p.nombre as proyecto_nombre,
                        c.nombre as contratista_nombre,
                        t.nombre as trabajador_nombre,
                        u.usuario as creado_por
                    FROM supervisiones s
                    INNER JOIN proyectos p ON s.proyecto_id = p.id
                    INNER JOIN contratistas c ON s.contratista_id = c.id
                    INNER JOIN trabajadores t ON s.trabajador_id = t.id
                    LEFT JOIN usuarios u ON s.usuario_id = u.id
                    WHERE s.usuario_id = :usuario_id
                    ORDER BY s.fecha_supervision DESC
                ");
                $stmt->execute(['usuario_id' => $usuarioId]);
            }
            
            $supervisiones = $esAdmin ? $stmt->fetchAll() : $stmt->fetchAll();
            
            $response['success'] = true;
            $response['data'] = $supervisiones;
            $response['total'] = count($supervisiones);
            $response['es_admin'] = $esAdmin;
            break;
            
        case 'PUT':
            // ⭐ EDITAR supervisión (verificar permisos)
            parse_str(file_get_contents("php://input"), $_PUT);
            
            $id = limpiarDato($_PUT['id'] ?? '');
            $proyecto_id = limpiarDato($_PUT['proyecto_id'] ?? '');
            $contratista_id = limpiarDato($_PUT['contratista_id'] ?? '');
            $trabajador_id = limpiarDato($_PUT['trabajador_id'] ?? '');
            $telefono = limpiarDato($_PUT['telefono'] ?? '');
            $observaciones = limpiarDato($_PUT['observaciones'] ?? '');
            
            if (empty($id)) {
                throw new Exception('ID no proporcionado');
            }
            
            // Verificar que la supervisión existe
            $stmt = $db->prepare("SELECT usuario_id FROM supervisiones WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $supervision = $stmt->fetch();
            
            if (!$supervision) {
                throw new Exception('La supervisión no existe');
            }
            
            // ⭐ Verificar permisos: admin o creador
            if (!$esAdmin && $supervision['usuario_id'] != $usuarioId) {
                throw new Exception('No tienes permiso para editar esta supervisión');
            }
            
            // Validar campos
            if (empty($proyecto_id) || empty($contratista_id) || empty($trabajador_id) || empty($telefono)) {
                throw new Exception('Todos los campos son obligatorios');
            }
            
            // Actualizar
            $stmt = $db->prepare("
                UPDATE supervisiones 
                SET proyecto_id = :proyecto_id,
                    contratista_id = :contratista_id,
                    trabajador_id = :trabajador_id,
                    telefono = :telefono,
                    observaciones = :observaciones,
                    fecha_modificacion = NOW()
                WHERE id = :id
            ");
            
            $resultado = $stmt->execute([
                'id' => $id,
                'proyecto_id' => $proyecto_id,
                'contratista_id' => $contratista_id,
                'trabajador_id' => $trabajador_id,
                'telefono' => $telefono,
                'observaciones' => $observaciones
            ]);
            
            if (!$resultado) {
                throw new Exception('Error al actualizar');
            }
            
            $response['success'] = true;
            $response['message'] = 'Supervisión actualizada exitosamente';
            break;
            
        case 'DELETE':
            // ⭐ ELIMINAR supervisión (verificar permisos)
            $id = limpiarDato($_GET['id'] ?? '');
            
            if (empty($id)) {
                throw new Exception('ID no proporcionado');
            }
            
            // Verificar que la supervisión existe
            $stmt = $db->prepare("SELECT usuario_id FROM supervisiones WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $supervision = $stmt->fetch();
            
            if (!$supervision) {
                throw new Exception('La supervisión no existe');
            }
            
            // ⭐ Verificar permisos: admin o creador
            if (!$esAdmin && $supervision['usuario_id'] != $usuarioId) {
                throw new Exception('No tienes permiso para eliminar esta supervisión');
            }
            
            // Eliminar supervisión
            $stmt = $db->prepare("DELETE FROM supervisiones WHERE id = :id");
            $resultado = $stmt->execute(['id' => $id]);
            
            if (!$resultado) {
                throw new Exception('Error al eliminar');
            }
            
            $response['success'] = true;
            $response['message'] = 'Supervisión eliminada exitosamente';
            
            error_log('✅ Supervisión eliminada: ID ' . $id . ' por usuario ' . $usuarioId);
            break;
            
        default:
            throw new Exception('Método no permitido: ' . $method);
    }
    
} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    error_log('❌ Error PDO en API supervisiones: ' . $e->getMessage());
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log('❌ Error en API supervisiones: ' . $e->getMessage());
}

echo json_encode($response);
exit;