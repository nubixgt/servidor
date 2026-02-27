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
            // CREAR nuevo trabajador
            $nombre = limpiarDato($_POST['nombre'] ?? '');
            $contratista_id = limpiarDato($_POST['contratista_id'] ?? '');
            $puesto = limpiarDato($_POST['puesto'] ?? '');
            $dpi = limpiarDato($_POST['dpi'] ?? '');
            $telefono = limpiarDato($_POST['telefono'] ?? '');
            $fecha_nacimiento = limpiarDato($_POST['fecha_nacimiento'] ?? '');
            $fecha_contratacion = limpiarDato($_POST['fecha_contratacion'] ?? '');
            $salario = limpiarDato($_POST['salario'] ?? '');
            $horas_extra = limpiarDato($_POST['horas_extra'] ?? '');
            $modalidad = limpiarDato($_POST['modalidad'] ?? '');
            $estado = limpiarDato($_POST['estado'] ?? 'activo');
            
            // Validar campos obligatorios
            if (empty($nombre)) {
                throw new Exception('El nombre es obligatorio');
            }
            
            if (empty($contratista_id)) {
                throw new Exception('El contratista es obligatorio');
            }
            
            if (empty($puesto)) {
                throw new Exception('El puesto es obligatorio');
            }
            
            if (empty($dpi)) {
                throw new Exception('El DPI es obligatorio');
            }
            
            // Validar formato de DPI (13 dígitos)
            if (!preg_match('/^[0-9]{13}$/', $dpi)) {
                throw new Exception('El DPI debe tener exactamente 13 dígitos');
            }
            
            if (empty($telefono)) {
                throw new Exception('El teléfono es obligatorio');
            }
            
            // Validar formato de teléfono (8 dígitos)
            if (!preg_match('/^[0-9]{8}$/', $telefono)) {
                throw new Exception('El teléfono debe tener exactamente 8 dígitos');
            }
            
            // Validar que el contratista existe
            $stmtCheck = $db->prepare("SELECT id FROM contratistas WHERE id = :id");
            $stmtCheck->execute(['id' => $contratista_id]);
            if (!$stmtCheck->fetch()) {
                throw new Exception('El contratista seleccionado no existe');
            }
            
            // Limpiar salario (quitar formato si viene con Q, comas, etc)
            if (!empty($salario)) {
                $salario = preg_replace('/[^0-9.]/', '', $salario);
                $salario = floatval($salario);
            } else {
                $salario = null;
            }
            
            // Validar horas extra (solo números enteros positivos)
            if (!empty($horas_extra)) {
                $horas_extra = intval($horas_extra);
                if ($horas_extra < 0) {
                    $horas_extra = 0;
                }
            } else {
                $horas_extra = 0;
            }
            
            // Validar modalidad
            if (!empty($modalidad) && !in_array($modalidad, ['Plan 24', 'Mes', 'Destajo'])) {
                throw new Exception('La modalidad seleccionada no es válida');
            }
            
            // Insertar trabajador
            $stmt = $db->prepare("
                INSERT INTO trabajadores 
                (nombre, contratista_id, puesto, dpi, telefono, fecha_nacimiento, 
                 fecha_contratacion, salario, horas_extra, modalidad, estado, 
                 fechaCreacion, fechaModificacion) 
                VALUES 
                (:nombre, :contratista_id, :puesto, :dpi, :telefono, :fecha_nacimiento,
                 :fecha_contratacion, :salario, :horas_extra, :modalidad, :estado,
                 NOW(), NOW())
            ");
            
            $resultado = $stmt->execute([
                'nombre' => $nombre,
                'contratista_id' => $contratista_id,
                'puesto' => $puesto,
                'dpi' => $dpi,
                'telefono' => $telefono,
                'fecha_nacimiento' => $fecha_nacimiento ?: null,
                'fecha_contratacion' => $fecha_contratacion ?: null,
                'salario' => $salario,
                'horas_extra' => $horas_extra,
                'modalidad' => $modalidad ?: null,
                'estado' => $estado
            ]);
            
            if (!$resultado) {
                throw new Exception('Error al insertar en la base de datos');
            }
            
            $response['success'] = true;
            $response['message'] = 'Trabajador creado exitosamente';
            $response['id'] = $db->lastInsertId();
            break;
            
        case 'PUT':
            // ACTUALIZAR trabajador existente
            parse_str(file_get_contents("php://input"), $_PUT);
            
            $id = limpiarDato($_PUT['id'] ?? '');
            $nombre = limpiarDato($_PUT['nombre'] ?? '');
            $contratista_id = limpiarDato($_PUT['contratista_id'] ?? '');
            $puesto = limpiarDato($_PUT['puesto'] ?? '');
            $dpi = limpiarDato($_PUT['dpi'] ?? '');
            $telefono = limpiarDato($_PUT['telefono'] ?? '');
            $fecha_nacimiento = limpiarDato($_PUT['fecha_nacimiento'] ?? '');
            $fecha_contratacion = limpiarDato($_PUT['fecha_contratacion'] ?? '');
            $salario = limpiarDato($_PUT['salario'] ?? '');
            $horas_extra = limpiarDato($_PUT['horas_extra'] ?? '');
            $modalidad = limpiarDato($_PUT['modalidad'] ?? '');
            $estado = limpiarDato($_PUT['estado'] ?? 'activo');
            
            // Validar campos obligatorios
            if (empty($id)) {
                throw new Exception('ID no proporcionado');
            }
            
            if (empty($nombre)) {
                throw new Exception('El nombre es obligatorio');
            }
            
            if (empty($contratista_id)) {
                throw new Exception('El contratista es obligatorio');
            }
            
            if (empty($puesto)) {
                throw new Exception('El puesto es obligatorio');
            }
            
            if (empty($dpi)) {
                throw new Exception('El DPI es obligatorio');
            }
            
            // Validar formato de DPI (13 dígitos)
            if (!preg_match('/^[0-9]{13}$/', $dpi)) {
                throw new Exception('El DPI debe tener exactamente 13 dígitos');
            }
            
            if (empty($telefono)) {
                throw new Exception('El teléfono es obligatorio');
            }
            
            // Validar formato de teléfono (8 dígitos)
            if (!preg_match('/^[0-9]{8}$/', $telefono)) {
                throw new Exception('El teléfono debe tener exactamente 8 dígitos');
            }
            
            // Verificar que el trabajador existe
            $stmtCheck = $db->prepare("SELECT id FROM trabajadores WHERE id = :id");
            $stmtCheck->execute(['id' => $id]);
            if (!$stmtCheck->fetch()) {
                throw new Exception('El trabajador no existe');
            }
            
            // Limpiar salario (quitar formato si viene con Q, comas, etc)
            if (!empty($salario)) {
                $salario = preg_replace('/[^0-9.]/', '', $salario);
                $salario = floatval($salario);
            } else {
                $salario = null;
            }
            
            // Validar horas extra (solo números enteros positivos)
            if (!empty($horas_extra)) {
                $horas_extra = intval($horas_extra);
                if ($horas_extra < 0) {
                    $horas_extra = 0;
                }
            } else {
                $horas_extra = 0;
            }
            
            // Validar modalidad
            if (!empty($modalidad) && !in_array($modalidad, ['Plan 24', 'Mes', 'Destajo'])) {
                throw new Exception('La modalidad seleccionada no es válida');
            }
            
            // Actualizar trabajador
            $stmt = $db->prepare("
                UPDATE trabajadores 
                SET nombre = :nombre,
                    contratista_id = :contratista_id,
                    puesto = :puesto,
                    dpi = :dpi,
                    telefono = :telefono,
                    fecha_nacimiento = :fecha_nacimiento,
                    fecha_contratacion = :fecha_contratacion,
                    salario = :salario,
                    horas_extra = :horas_extra,
                    modalidad = :modalidad,
                    estado = :estado,
                    fechaModificacion = NOW()
                WHERE id = :id
            ");
            
            $resultado = $stmt->execute([
                'id' => $id,
                'nombre' => $nombre,
                'contratista_id' => $contratista_id,
                'puesto' => $puesto,
                'dpi' => $dpi,
                'telefono' => $telefono,
                'fecha_nacimiento' => $fecha_nacimiento ?: null,
                'fecha_contratacion' => $fecha_contratacion ?: null,
                'salario' => $salario,
                'horas_extra' => $horas_extra,
                'modalidad' => $modalidad ?: null,
                'estado' => $estado
            ]);
            
            if (!$resultado) {
                throw new Exception('Error al actualizar en la base de datos');
            }
            
            $response['success'] = true;
            $response['message'] = 'Trabajador actualizado exitosamente';
            break;
            
        case 'DELETE':
            // ELIMINAR trabajador
            $id = limpiarDato($_GET['id'] ?? '');
            
            if (empty($id)) {
                throw new Exception('ID no proporcionado');
            }
            
            // Verificar que el trabajador existe
            $stmtCheck = $db->prepare("SELECT id FROM trabajadores WHERE id = :id");
            $stmtCheck->execute(['id' => $id]);
            if (!$stmtCheck->fetch()) {
                throw new Exception('El trabajador no existe');
            }
            
            // Eliminar trabajador
            $stmt = $db->prepare("DELETE FROM trabajadores WHERE id = :id");
            $resultado = $stmt->execute(['id' => $id]);
            
            if (!$resultado) {
                throw new Exception('Error al eliminar de la base de datos');
            }
            
            $response['success'] = true;
            $response['message'] = 'Trabajador eliminado exitosamente';
            break;
            
        case 'GET':
            // OBTENER un trabajador específico
            $id = limpiarDato($_GET['id'] ?? '');
            
            if (empty($id)) {
                throw new Exception('ID no proporcionado');
            }
            
            $stmt = $db->prepare("
                SELECT 
                    t.*,
                    c.nombre as contratista_nombre
                FROM trabajadores t
                LEFT JOIN contratistas c ON t.contratista_id = c.id
                WHERE t.id = :id
            ");
            $stmt->execute(['id' => $id]);
            $trabajador = $stmt->fetch();
            
            if (!$trabajador) {
                throw new Exception('Trabajador no encontrado');
            }
            
            $response['success'] = true;
            $response['data'] = $trabajador;
            break;
            
        default:
            throw new Exception('Método no permitido: ' . $method);
    }
    
} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    error_log('Error PDO en API trabajadores: ' . $e->getMessage());
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log('Error en API trabajadores: ' . $e->getMessage());
}

echo json_encode($response);
exit;