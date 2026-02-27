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
            // CREAR nuevo proyecto
            $nombre = limpiarDato($_POST['nombre'] ?? '');
            $tipo = limpiarDato($_POST['tipo'] ?? '');
            $ubicacion = limpiarDato($_POST['ubicacion'] ?? '');
            $descripcion = limpiarDato($_POST['descripcion'] ?? '');
            $estado = limpiarDato($_POST['estado'] ?? 'activo');
            $fecha_inicio = limpiarDato($_POST['fecha_inicio'] ?? null);
            $fecha_fin_estimada = limpiarDato($_POST['fecha_fin_estimada'] ?? null);
            $fecha_fin_real = limpiarDato($_POST['fecha_fin_real'] ?? null);
            $cliente = limpiarDato($_POST['cliente'] ?? '');
            
            // Nuevos campos de moneda
            $consejo = limpiarDato($_POST['consejo'] ?? null);
            $muni = limpiarDato($_POST['muni'] ?? null);
            $odc = limpiarDato($_POST['odc'] ?? null);
            $presupuesto = limpiarDato($_POST['presupuesto'] ?? null);
            
            // Limpiar y convertir campos de moneda
            if ($consejo !== null && $consejo !== '') {
                $consejo = preg_replace('/[^0-9.]/', '', $consejo);
                $consejo = floatval($consejo);
            } else {
                $consejo = null;
            }
            
            if ($muni !== null && $muni !== '') {
                $muni = preg_replace('/[^0-9.]/', '', $muni);
                $muni = floatval($muni);
            } else {
                $muni = null;
            }
            
            if ($odc !== null && $odc !== '') {
                $odc = preg_replace('/[^0-9.]/', '', $odc);
                $odc = floatval($odc);
            } else {
                $odc = null;
            }
            
            if ($presupuesto !== null && $presupuesto !== '') {
                $presupuesto = preg_replace('/[^0-9.]/', '', $presupuesto);
                $presupuesto = floatval($presupuesto);
            } else {
                $presupuesto = null;
            }
            
            // Validar campos obligatorios
            if (empty($nombre)) {
                throw new Exception('El nombre del proyecto es obligatorio');
            }
            
            if (empty($tipo)) {
                throw new Exception('El tipo de proyecto es obligatorio');
            }
            
            // Validar tipo de proyecto
            $tiposValidos = ['Edificio Residencial', 'Edificio Comercial', 'Carretera', 'Puente', 'Infraestructura Hidráulica', 'Otro'];
            if (!in_array($tipo, $tiposValidos)) {
                throw new Exception('Tipo de proyecto no válido');
            }
            
            // Insertar proyecto
            $stmt = $db->prepare("
                INSERT INTO proyectos 
                (nombre, tipo, ubicacion, descripcion, estado, fecha_inicio, fecha_fin_estimada, 
                 fecha_fin_real, presupuesto, consejo, muni, odc, cliente, fecha_creacion, fecha_modificacion) 
                VALUES 
                (:nombre, :tipo, :ubicacion, :descripcion, :estado, :fecha_inicio, :fecha_fin_estimada,
                 :fecha_fin_real, :presupuesto, :consejo, :muni, :odc, :cliente, NOW(), NOW())
            ");
            
            $resultado = $stmt->execute([
                'nombre' => $nombre,
                'tipo' => $tipo,
                'ubicacion' => $ubicacion,
                'descripcion' => $descripcion,
                'estado' => $estado,
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin_estimada' => $fecha_fin_estimada,
                'fecha_fin_real' => $fecha_fin_real,
                'presupuesto' => $presupuesto,
                'consejo' => $consejo,
                'muni' => $muni,
                'odc' => $odc,
                'cliente' => $cliente
            ]);
            
            if (!$resultado) {
                throw new Exception('Error al insertar en la base de datos');
            }
            
            $response['success'] = true;
            $response['message'] = 'Proyecto creado exitosamente';
            $response['id'] = $db->lastInsertId();
            break;
            
        case 'PUT':
            // ACTUALIZAR proyecto existente
            parse_str(file_get_contents("php://input"), $_PUT);
            
            $id = limpiarDato($_PUT['id'] ?? '');
            $nombre = limpiarDato($_PUT['nombre'] ?? '');
            $tipo = limpiarDato($_PUT['tipo'] ?? '');
            $ubicacion = limpiarDato($_PUT['ubicacion'] ?? '');
            $descripcion = limpiarDato($_PUT['descripcion'] ?? '');
            $estado = limpiarDato($_PUT['estado'] ?? 'activo');
            $fecha_inicio = limpiarDato($_PUT['fecha_inicio'] ?? null);
            $fecha_fin_estimada = limpiarDato($_PUT['fecha_fin_estimada'] ?? null);
            $fecha_fin_real = limpiarDato($_PUT['fecha_fin_real'] ?? null);
            $cliente = limpiarDato($_PUT['cliente'] ?? '');
            
            // Nuevos campos de moneda
            $consejo = limpiarDato($_PUT['consejo'] ?? null);
            $muni = limpiarDato($_PUT['muni'] ?? null);
            $odc = limpiarDato($_PUT['odc'] ?? null);
            $presupuesto = limpiarDato($_PUT['presupuesto'] ?? null);
            
            // Limpiar y convertir campos de moneda
            if ($consejo !== null && $consejo !== '') {
                $consejo = preg_replace('/[^0-9.]/', '', $consejo);
                $consejo = floatval($consejo);
            } else {
                $consejo = null;
            }
            
            if ($muni !== null && $muni !== '') {
                $muni = preg_replace('/[^0-9.]/', '', $muni);
                $muni = floatval($muni);
            } else {
                $muni = null;
            }
            
            if ($odc !== null && $odc !== '') {
                $odc = preg_replace('/[^0-9.]/', '', $odc);
                $odc = floatval($odc);
            } else {
                $odc = null;
            }
            
            if ($presupuesto !== null && $presupuesto !== '') {
                $presupuesto = preg_replace('/[^0-9.]/', '', $presupuesto);
                $presupuesto = floatval($presupuesto);
            } else {
                $presupuesto = null;
            }
            
            // Validar campos obligatorios
            if (empty($id)) {
                throw new Exception('ID no proporcionado');
            }
            
            if (empty($nombre)) {
                throw new Exception('El nombre del proyecto es obligatorio');
            }
            
            if (empty($tipo)) {
                throw new Exception('El tipo de proyecto es obligatorio');
            }
            
            // Verificar que el proyecto existe
            $stmtCheck = $db->prepare("SELECT id FROM proyectos WHERE id = :id");
            $stmtCheck->execute(['id' => $id]);
            if (!$stmtCheck->fetch()) {
                throw new Exception('El proyecto no existe');
            }
            
            // Validar tipo de proyecto
            $tiposValidos = ['Edificio Residencial', 'Edificio Comercial', 'Carretera', 'Puente', 'Infraestructura Hidráulica', 'Otro'];
            if (!in_array($tipo, $tiposValidos)) {
                throw new Exception('Tipo de proyecto no válido');
            }
            
            // Actualizar proyecto
            $stmt = $db->prepare("
                UPDATE proyectos 
                SET nombre = :nombre,
                    tipo = :tipo,
                    ubicacion = :ubicacion,
                    descripcion = :descripcion,
                    estado = :estado,
                    fecha_inicio = :fecha_inicio,
                    fecha_fin_estimada = :fecha_fin_estimada,
                    fecha_fin_real = :fecha_fin_real,
                    presupuesto = :presupuesto,
                    consejo = :consejo,
                    muni = :muni,
                    odc = :odc,
                    cliente = :cliente,
                    fecha_modificacion = NOW()
                WHERE id = :id
            ");
            
            $resultado = $stmt->execute([
                'id' => $id,
                'nombre' => $nombre,
                'tipo' => $tipo,
                'ubicacion' => $ubicacion,
                'descripcion' => $descripcion,
                'estado' => $estado,
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin_estimada' => $fecha_fin_estimada,
                'fecha_fin_real' => $fecha_fin_real,
                'presupuesto' => $presupuesto,
                'consejo' => $consejo,
                'muni' => $muni,
                'odc' => $odc,
                'cliente' => $cliente
            ]);
            
            if (!$resultado) {
                throw new Exception('Error al actualizar en la base de datos');
            }
            
            $response['success'] = true;
            $response['message'] = 'Proyecto actualizado exitosamente';
            break;
            
        case 'DELETE':
            // ELIMINAR proyecto
            $id = limpiarDato($_GET['id'] ?? '');
            
            if (empty($id)) {
                throw new Exception('ID no proporcionado');
            }
            
            // Verificar que el proyecto existe
            $stmtCheck = $db->prepare("SELECT id FROM proyectos WHERE id = :id");
            $stmtCheck->execute(['id' => $id]);
            if (!$stmtCheck->fetch()) {
                throw new Exception('El proyecto no existe');
            }
            
            // Eliminar proyecto
            $stmt = $db->prepare("DELETE FROM proyectos WHERE id = :id");
            $resultado = $stmt->execute(['id' => $id]);
            
            if (!$resultado) {
                throw new Exception('Error al eliminar de la base de datos');
            }
            
            $response['success'] = true;
            $response['message'] = 'Proyecto eliminado exitosamente';
            break;
            
        case 'GET':
            // OBTENER un proyecto específico
            $id = limpiarDato($_GET['id'] ?? '');
            
            if (empty($id)) {
                throw new Exception('ID no proporcionado');
            }
            
            $stmt = $db->prepare("
                SELECT * FROM proyectos WHERE id = :id
            ");
            $stmt->execute(['id' => $id]);
            $proyecto = $stmt->fetch();
            
            if (!$proyecto) {
                throw new Exception('Proyecto no encontrado');
            }
            
            $response['success'] = true;
            $response['data'] = $proyecto;
            break;
            
        default:
            throw new Exception('Método no permitido: ' . $method);
    }
    
} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    error_log('Error PDO en API proyectos: ' . $e->getMessage());
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log('Error en API proyectos: ' . $e->getMessage());
}

echo json_encode($response);
exit;