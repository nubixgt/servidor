<?php
// backend/api/denuncias.php
require_once '../config/database.php';

// Manejar solicitud OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            crearDenuncia($db);
            break;
        case 'GET':
            if (isset($_GET['id'])) {
                obtenerDenuncia($db, $_GET['id']);
            } else {
                listarDenuncias($db);
            }
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

// ==================== CREAR DENUNCIA ====================
function crearDenuncia($db) {
    // Obtener datos JSON del body
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        return;
    }
    
    // Validar campos obligatorios
    $camposRequeridos = [
        'tipo_persona', 'nombre_completo', 'dpi', 'edad', 'genero', 'celular',
        'foto_dpi_frontal', 'foto_dpi_trasera', 'direccion_infraccion',
        'departamento', 'municipio', 'foto_fachada', 'especie_animal',
        'cantidad', 'descripcion_detallada', 'infracciones'
    ];
    
    foreach ($camposRequeridos as $campo) {
        if (!isset($data[$campo]) || empty($data[$campo])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Campo requerido faltante: $campo"]);
            return;
        }
    }
    
    try {
        $db->beginTransaction();
        
        // 1. Insertar denuncia principal
        $sql = "INSERT INTO denuncias (
            tipo_persona, nombre_completo, dpi, edad, genero, celular,
            foto_dpi_frontal, foto_dpi_trasera, nombre_responsable,
            direccion_infraccion, departamento, municipio, color_casa,
            color_puerta, foto_fachada, latitud, longitud, especie_animal,
            especie_otro, cantidad, raza, descripcion_detallada
        ) VALUES (
            :tipo_persona, :nombre_completo, :dpi, :edad, :genero, :celular,
            :foto_dpi_frontal, :foto_dpi_trasera, :nombre_responsable,
            :direccion_infraccion, :departamento, :municipio, :color_casa,
            :color_puerta, :foto_fachada, :latitud, :longitud, :especie_animal,
            :especie_otro, :cantidad, :raza, :descripcion_detallada
        )";
        
        $stmt = $db->prepare($sql);
        
        $stmt->bindParam(':tipo_persona', $data['tipo_persona']);
        $stmt->bindParam(':nombre_completo', $data['nombre_completo']);
        $stmt->bindParam(':dpi', $data['dpi']);
        $stmt->bindParam(':edad', $data['edad']);
        $stmt->bindParam(':genero', $data['genero']);
        $stmt->bindParam(':celular', $data['celular']);
        $stmt->bindParam(':foto_dpi_frontal', $data['foto_dpi_frontal']);
        $stmt->bindParam(':foto_dpi_trasera', $data['foto_dpi_trasera']);
        
        $nombre_responsable = $data['nombre_responsable'] ?? null;
        $stmt->bindParam(':nombre_responsable', $nombre_responsable);
        
        $stmt->bindParam(':direccion_infraccion', $data['direccion_infraccion']);
        $stmt->bindParam(':departamento', $data['departamento']);
        $stmt->bindParam(':municipio', $data['municipio']);
        
        $color_casa = $data['color_casa'] ?? null;
        $color_puerta = $data['color_puerta'] ?? null;
        $stmt->bindParam(':color_casa', $color_casa);
        $stmt->bindParam(':color_puerta', $color_puerta);
        
        $stmt->bindParam(':foto_fachada', $data['foto_fachada']);
        
        $latitud = $data['latitud'] ?? null;
        $longitud = $data['longitud'] ?? null;
        $stmt->bindParam(':latitud', $latitud);
        $stmt->bindParam(':longitud', $longitud);
        
        $stmt->bindParam(':especie_animal', $data['especie_animal']);
        
        $especie_otro = $data['especie_otro'] ?? null;
        $stmt->bindParam(':especie_otro', $especie_otro);
        
        $stmt->bindParam(':cantidad', $data['cantidad']);
        
        $raza = $data['raza'] ?? null;
        $stmt->bindParam(':raza', $raza);
        
        $stmt->bindParam(':descripcion_detallada', $data['descripcion_detallada']);
        
        $stmt->execute();
        $id_denuncia = $db->lastInsertId();
        
        // 2. Insertar infracciones
        if (isset($data['infracciones']) && is_array($data['infracciones'])) {
            $sqlInfraccion = "INSERT INTO infracciones_denuncia (id_denuncia, tipo_infraccion, infraccion_otro) 
                             VALUES (:id_denuncia, :tipo_infraccion, :infraccion_otro)";
            $stmtInfraccion = $db->prepare($sqlInfraccion);
            
            foreach ($data['infracciones'] as $infraccion) {
                $stmtInfraccion->bindParam(':id_denuncia', $id_denuncia);
                $stmtInfraccion->bindParam(':tipo_infraccion', $infraccion['tipo']);
                
                $infraccion_otro = $infraccion['otro'] ?? null;
                $stmtInfraccion->bindParam(':infraccion_otro', $infraccion_otro);
                
                $stmtInfraccion->execute();
            }
        }
        
        // 3. Insertar evidencias
        if (isset($data['evidencias']) && is_array($data['evidencias'])) {
            $sqlEvidencia = "INSERT INTO evidencias_denuncia (id_denuncia, tipo_archivo, nombre_archivo, ruta_archivo, tamanio_kb) 
                            VALUES (:id_denuncia, :tipo_archivo, :nombre_archivo, :ruta_archivo, :tamanio_kb)";
            $stmtEvidencia = $db->prepare($sqlEvidencia);
            
            foreach ($data['evidencias'] as $evidencia) {
                $stmtEvidencia->bindParam(':id_denuncia', $id_denuncia);
                $stmtEvidencia->bindParam(':tipo_archivo', $evidencia['tipo']);
                $stmtEvidencia->bindParam(':nombre_archivo', $evidencia['nombre']);
                $stmtEvidencia->bindParam(':ruta_archivo', $evidencia['ruta']);
                
                $tamanio = $evidencia['tamanio'] ?? null;
                $stmtEvidencia->bindParam(':tamanio_kb', $tamanio);
                
                $stmtEvidencia->execute();
            }
        }
        
        $db->commit();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Denuncia creada exitosamente',
            'id_denuncia' => $id_denuncia
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

// ==================== OBTENER UNA DENUNCIA ====================
function obtenerDenuncia($db, $id) {
    $sql = "SELECT d.*, 
            GROUP_CONCAT(DISTINCT CONCAT(i.tipo_infraccion, '|', IFNULL(i.infraccion_otro, '')) SEPARATOR ';;') as infracciones,
            GROUP_CONCAT(DISTINCT CONCAT(e.tipo_archivo, '|', e.nombre_archivo, '|', e.ruta_archivo) SEPARATOR ';;') as evidencias
            FROM denuncias d
            LEFT JOIN infracciones_denuncia i ON d.id_denuncia = i.id_denuncia
            LEFT JOIN evidencias_denuncia e ON d.id_denuncia = e.id_denuncia
            WHERE d.id_denuncia = :id
            GROUP BY d.id_denuncia";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    $denuncia = $stmt->fetch();
    
    if ($denuncia) {
        // Procesar infracciones
        if ($denuncia['infracciones']) {
            $infracciones = [];
            foreach (explode(';;', $denuncia['infracciones']) as $inf) {
                $partes = explode('|', $inf);
                $infracciones[] = [
                    'tipo' => $partes[0],
                    'otro' => $partes[1] ?: null
                ];
            }
            $denuncia['infracciones'] = $infracciones;
        } else {
            $denuncia['infracciones'] = [];
        }
        
        // Procesar evidencias
        if ($denuncia['evidencias']) {
            $evidencias = [];
            foreach (explode(';;', $denuncia['evidencias']) as $ev) {
                $partes = explode('|', $ev);
                $evidencias[] = [
                    'tipo' => $partes[0],
                    'nombre' => $partes[1],
                    'ruta' => $partes[2]
                ];
            }
            $denuncia['evidencias'] = $evidencias;
        } else {
            $denuncia['evidencias'] = [];
        }
        
        echo json_encode(['success' => true, 'data' => $denuncia]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Denuncia no encontrada']);
    }
}

// ==================== LISTAR DENUNCIAS ====================
function listarDenuncias($db) {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $estado = $_GET['estado'] ?? null;
    
    $sql = "SELECT id_denuncia, nombre_completo, dpi, celular, departamento, municipio, 
            especie_animal, cantidad, fecha_denuncia, estado_denuncia
            FROM denuncias";
    
    if ($estado) {
        $sql .= " WHERE estado_denuncia = :estado";
    }
    
    $sql .= " ORDER BY fecha_denuncia DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    
    if ($estado) {
        $stmt->bindParam(':estado', $estado);
    }
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $denuncias = $stmt->fetchAll();
    
    // Contar total
    $sqlCount = "SELECT COUNT(*) as total FROM denuncias";
    if ($estado) {
        $sqlCount .= " WHERE estado_denuncia = :estado";
    }
    $stmtCount = $db->prepare($sqlCount);
    if ($estado) {
        $stmtCount->bindParam(':estado', $estado);
    }
    $stmtCount->execute();
    $total = $stmtCount->fetch()['total'];
    
    echo json_encode([
        'success' => true,
        'data' => $denuncias,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
}
?>