<?php
require_once '../config/config.php';
requireAdmin();

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

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'POST':
            // CREAR nuevo contratista
            $nombre = limpiarDato($_POST['nombre'] ?? '');
            $nit = limpiarDato($_POST['nit'] ?? '');
            $direccion = limpiarDato($_POST['direccion'] ?? '');
            $telefono = limpiarDato($_POST['telefono'] ?? '');
            $email = limpiarDato($_POST['email'] ?? '');
            $contactoPrincipal = limpiarDato($_POST['contactoPrincipal'] ?? '');
            $estado = limpiarDato($_POST['estado'] ?? 'activo');
            
            // Validar campos obligatorios
            if (empty($nombre)) {
                throw new Exception('El nombre de la empresa es obligatorio');
            }
            
            if (empty($nit)) {
                throw new Exception('El NIT es obligatorio');
            }
            
            if (empty($contactoPrincipal)) {
                throw new Exception('El contacto principal es obligatorio');
            }
            
            // Verificar si el NIT ya existe
            $stmtCheck = $db->prepare("SELECT id FROM contratistas WHERE nit = :nit");
            $stmtCheck->execute(['nit' => $nit]);
            if ($stmtCheck->fetch()) {
                throw new Exception('Ya existe un contratista con este NIT');
            }
            
            // Insertar contratista
            $stmt = $db->prepare("
                INSERT INTO contratistas 
                (nombre, nit, direccion, telefono, email, contactoPrincipal, estado, fechaCreacion, fechaModificacion) 
                VALUES 
                (:nombre, :nit, :direccion, :telefono, :email, :contactoPrincipal, :estado, NOW(), NOW())
            ");
            
            $resultado = $stmt->execute([
                'nombre' => $nombre,
                'nit' => $nit,
                'direccion' => $direccion,
                'telefono' => $telefono,
                'email' => $email,
                'contactoPrincipal' => $contactoPrincipal,
                'estado' => $estado
            ]);
            
            if (!$resultado) {
                throw new Exception('Error al insertar en la base de datos');
            }
            
            $response['success'] = true;
            $response['message'] = 'Contratista creado exitosamente';
            $response['id'] = $db->lastInsertId();
            break;
            
        case 'PUT':
            // ACTUALIZAR contratista existente
            parse_str(file_get_contents("php://input"), $_PUT);
            
            $id = limpiarDato($_PUT['id'] ?? '');
            $nombre = limpiarDato($_PUT['nombre'] ?? '');
            $nit = limpiarDato($_PUT['nit'] ?? '');
            $direccion = limpiarDato($_PUT['direccion'] ?? '');
            $telefono = limpiarDato($_PUT['telefono'] ?? '');
            $email = limpiarDato($_PUT['email'] ?? '');
            $contactoPrincipal = limpiarDato($_PUT['contactoPrincipal'] ?? '');
            $estado = limpiarDato($_PUT['estado'] ?? 'activo');
            
            // Validar campos obligatorios
            if (empty($id)) {
                throw new Exception('ID no proporcionado');
            }
            
            if (empty($nombre)) {
                throw new Exception('El nombre de la empresa es obligatorio');
            }
            
            if (empty($nit)) {
                throw new Exception('El NIT es obligatorio');
            }
            
            if (empty($contactoPrincipal)) {
                throw new Exception('El contacto principal es obligatorio');
            }
            
            // Verificar que el contratista existe
            $stmtCheck = $db->prepare("SELECT id FROM contratistas WHERE id = :id");
            $stmtCheck->execute(['id' => $id]);
            if (!$stmtCheck->fetch()) {
                throw new Exception('El contratista no existe');
            }
            
            // Verificar si el NIT ya existe en otro contratista
            $stmtCheck = $db->prepare("SELECT id FROM contratistas WHERE nit = :nit AND id != :id");
            $stmtCheck->execute(['nit' => $nit, 'id' => $id]);
            if ($stmtCheck->fetch()) {
                throw new Exception('Ya existe otro contratista con este NIT');
            }
            
            // Actualizar contratista
            $stmt = $db->prepare("
                UPDATE contratistas 
                SET nombre = :nombre,
                    nit = :nit,
                    direccion = :direccion,
                    telefono = :telefono,
                    email = :email,
                    contactoPrincipal = :contactoPrincipal,
                    estado = :estado,
                    fechaModificacion = NOW()
                WHERE id = :id
            ");
            
            $resultado = $stmt->execute([
                'id' => $id,
                'nombre' => $nombre,
                'nit' => $nit,
                'direccion' => $direccion,
                'telefono' => $telefono,
                'email' => $email,
                'contactoPrincipal' => $contactoPrincipal,
                'estado' => $estado
            ]);
            
            if (!$resultado) {
                throw new Exception('Error al actualizar en la base de datos');
            }
            
            $response['success'] = true;
            $response['message'] = 'Contratista actualizado exitosamente';
            break;
            
        case 'DELETE':
            // ELIMINAR contratista
            $id = limpiarDato($_GET['id'] ?? '');
            
            if (empty($id)) {
                throw new Exception('ID no proporcionado');
            }
            
            // Verificar que el contratista existe
            $stmtCheck = $db->prepare("SELECT id FROM contratistas WHERE id = :id");
            $stmtCheck->execute(['id' => $id]);
            if (!$stmtCheck->fetch()) {
                throw new Exception('El contratista no existe');
            }
            
            // Verificar si tiene trabajadores asignados
            $stmtCheck = $db->prepare("SELECT COUNT(*) as total FROM trabajadores WHERE contratista_id = :id");
            $stmtCheck->execute(['id' => $id]);
            $totalTrabajadores = $stmtCheck->fetch()['total'];
            
            if ($totalTrabajadores > 0) {
                // Opcional: puedes cambiar esto a CASCADE o SET NULL según necesites
                throw new Exception("No se puede eliminar el contratista porque tiene {$totalTrabajadores} trabajador(es) asignado(s). Primero reasigna o elimina los trabajadores.");
            }
            
            // Eliminar contratista
            $stmt = $db->prepare("DELETE FROM contratistas WHERE id = :id");
            $resultado = $stmt->execute(['id' => $id]);
            
            if (!$resultado) {
                throw new Exception('Error al eliminar de la base de datos');
            }
            
            $response['success'] = true;
            $response['message'] = 'Contratista eliminado exitosamente';
            break;
            
        case 'GET':
            // OBTENER un contratista específico
            $id = limpiarDato($_GET['id'] ?? '');
            
            if (empty($id)) {
                throw new Exception('ID no proporcionado');
            }
            
            $stmt = $db->prepare("
                SELECT 
                    c.*,
                    COUNT(t.id) as total_empleados
                FROM contratistas c
                LEFT JOIN trabajadores t ON c.id = t.contratista_id
                WHERE c.id = :id
                GROUP BY c.id
            ");
            $stmt->execute(['id' => $id]);
            $contratista = $stmt->fetch();
            
            if (!$contratista) {
                throw new Exception('Contratista no encontrado');
            }
            
            $response['success'] = true;
            $response['data'] = $contratista;
            break;
            
        default:
            throw new Exception('Método no permitido: ' . $method);
    }
    
} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    error_log('Error PDO en API contratistas: ' . $e->getMessage());
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log('Error en API contratistas: ' . $e->getMessage());
}

echo json_encode($response);
exit;