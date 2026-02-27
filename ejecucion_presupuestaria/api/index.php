<?php
/**
 * API REST - Endpoints
 * Sistema de Ejecución Presupuestaria - MAGA
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// Obtener método y endpoint
$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Función para responder JSON
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Función para obtener datos del body
function getRequestData() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

try {
    $db = getDB();
    
    switch ($endpoint) {
        
        // ===============================================
        // DASHBOARD - Totales generales
        // ===============================================
        case 'dashboard':
            if ($method === 'GET') {
                // Totales generales
                $totales = $db->query("
                    SELECT 
                        SUM(asignado) as total_asignado,
                        SUM(modificado) as total_modificado,
                        SUM(vigente) as total_vigente,
                        SUM(devengado) as total_devengado,
                        SUM(saldo_por_devengar) as total_saldo
                    FROM ejecucion_principal 
                    WHERE tipo_ejecucion_id = 1
                ")->fetch();
                
                // Por tipo de ejecución
                $porTipo = $db->query("
                    SELECT te.nombre as tipo, COUNT(*) as cantidad,
                           SUM(ep.devengado) as total_devengado
                    FROM ejecucion_principal ep
                    JOIN tipos_ejecucion te ON ep.tipo_ejecucion_id = te.id
                    GROUP BY te.nombre
                ")->fetchAll();
                
                jsonResponse([
                    'success' => true,
                    'data' => [
                        'totales' => $totales,
                        'por_tipo' => $porTipo
                    ]
                ]);
            }
            break;
        
        // ===============================================
        // EJECUCIÓN PRINCIPAL
        // ===============================================
        case 'ejecucion':
            if ($method === 'GET') {
                $tipo = $_GET['tipo'] ?? null;
                
                $sql = "SELECT * FROM v_ejecucion_principal";
                $params = [];
                
                if ($tipo) {
                    $sql .= " WHERE tipo_ejecucion = ?";
                    $params[] = $tipo;
                }
                
                $sql .= " ORDER BY asignado DESC";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                
                jsonResponse(['success' => true, 'data' => $data, 'total' => count($data)]);
            }
            
            if ($method === 'PUT' && $id) {
                $datos = getRequestData();
                
                // Obtener datos anteriores
                $stmt = $db->prepare("SELECT * FROM ejecucion_principal WHERE id = ?");
                $stmt->execute([$id]);
                $anterior = $stmt->fetch();
                
                if (!$anterior) {
                    jsonResponse(['success' => false, 'message' => 'Registro no encontrado'], 404);
                }
                
                // Actualizar
                $campos = ['asignado', 'modificado', 'vigente', 'devengado', 'saldo_por_devengar', 'porcentaje_ejecucion', 'porcentaje_relativo'];
                $updates = [];
                $valores = [];
                
                foreach ($campos as $campo) {
                    if (isset($datos[$campo])) {
                        $updates[] = "$campo = ?";
                        $valores[] = $datos[$campo];
                    }
                }
                
                if (empty($updates)) {
                    jsonResponse(['success' => false, 'message' => 'No hay campos para actualizar'], 400);
                }
                
                $valores[] = $id;
                $sql = "UPDATE ejecucion_principal SET " . implode(', ', $updates) . " WHERE id = ?";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($valores);
                
                // Registrar en bitácora
                registrarBitacora('ejecucion_principal', $id, 'UPDATE', $anterior, $datos);
                
                jsonResponse(['success' => true, 'message' => 'Registro actualizado']);
            }
            break;
        
        // ===============================================
        // UNIDADES EJECUTORAS
        // ===============================================
        case 'unidades':
            if ($method === 'GET') {
                $data = $db->query("
                    SELECT ue.*, 
                           SUM(ep.devengado) as total_devengado,
                           AVG(ep.porcentaje_ejecucion) as promedio_ejecucion
                    FROM unidades_ejecutoras ue
                    LEFT JOIN ejecucion_principal ep ON ue.id = ep.unidad_ejecutora_id
                    WHERE ue.activo = 1
                    GROUP BY ue.id
                    ORDER BY ue.codigo
                ")->fetchAll();
                
                jsonResponse(['success' => true, 'data' => $data]);
            }
            break;
        
        // ===============================================
        // DETALLE POR UNIDAD
        // ===============================================
        case 'detalle':
            if ($method === 'GET') {
                $unidadId = $_GET['unidad_id'] ?? null;
                $tipoRegistro = $_GET['tipo_registro'] ?? null;
                
                $sql = "SELECT * FROM v_ejecucion_detalle WHERE 1=1";
                $params = [];
                
                if ($unidadId) {
                    $sql .= " AND unidad_codigo = ?";
                    $params[] = $unidadId;
                }
                
                if ($tipoRegistro) {
                    $sql .= " AND tipo_registro = ?";
                    $params[] = $tipoRegistro;
                }
                
                $sql .= " ORDER BY vigente DESC";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                
                jsonResponse(['success' => true, 'data' => $data]);
            }
            break;
        
        // ===============================================
        // MINISTERIOS
        // ===============================================
        case 'ministerios':
            if ($method === 'GET') {
                $data = $db->query("
                    SELECT * FROM v_ejecucion_ministerios 
                    ORDER BY porcentaje_ejecucion DESC
                ")->fetchAll();
                
                jsonResponse(['success' => true, 'data' => $data]);
            }
            break;
        
        // ===============================================
        // PROGRAMAS
        // ===============================================
        case 'programas':
            if ($method === 'GET') {
                $data = $db->query("SELECT * FROM programas WHERE activo = 1 ORDER BY codigo")->fetchAll();
                jsonResponse(['success' => true, 'data' => $data]);
            }
            break;
        
        // ===============================================
        // GRUPOS DE GASTO
        // ===============================================
        case 'grupos-gasto':
            if ($method === 'GET') {
                $data = $db->query("SELECT * FROM grupos_gasto WHERE activo = 1 ORDER BY codigo")->fetchAll();
                jsonResponse(['success' => true, 'data' => $data]);
            }
            break;
        
        // ===============================================
        // FUENTES DE FINANCIAMIENTO
        // ===============================================
        case 'fuentes':
            if ($method === 'GET') {
                $data = $db->query("SELECT * FROM fuentes_financiamiento WHERE activo = 1 ORDER BY codigo")->fetchAll();
                jsonResponse(['success' => true, 'data' => $data]);
            }
            break;
        
        // ===============================================
        // CONFIGURACIÓN (Solo lectura desde constantes)
        // ===============================================
        case 'config':
            if ($method === 'GET') {
                // Devolver configuración desde constantes PHP
                $data = [
                    'nombre_sistema' => APP_NAME,
                    'institucion' => INSTITUCION,
                    'siglas_institucion' => INSTITUCION_SIGLAS,
                    'periodo_actual' => PERIODO_ACTUAL,
                    'meta_ejecucion_dia' => getMetaEjecucionAlDia(),
                    'umbral_verde' => UMBRAL_VERDE,
                    'umbral_amarillo' => UMBRAL_AMARILLO
                ];
                jsonResponse(['success' => true, 'data' => $data]);
            }
            break;
        
        // ===============================================
        // BITÁCORA
        // ===============================================
        case 'bitacora':
            if ($method === 'GET') {
                $limit = intval($_GET['limit'] ?? 50);
                $offset = intval($_GET['offset'] ?? 0);
                
                $data = $db->query("
                    SELECT b.*, u.nombre as usuario_nombre
                    FROM bitacora b
                    LEFT JOIN usuarios u ON b.usuario_id = u.id
                    ORDER BY b.created_at DESC
                    LIMIT $limit OFFSET $offset
                ")->fetchAll();
                
                $total = $db->query("SELECT COUNT(*) FROM bitacora")->fetchColumn();
                
                jsonResponse([
                    'success' => true,
                    'data' => $data,
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset
                ]);
            }
            break;
        
        // ===============================================
        // ESTADÍSTICAS
        // ===============================================
        case 'estadisticas':
            if ($method === 'GET') {
                // Por Unidad Ejecutora
                $porUnidad = $db->query("
                    SELECT ue.nombre_corto as nombre, 
                           SUM(ep.devengado) as devengado,
                           AVG(ep.porcentaje_ejecucion) as porcentaje
                    FROM ejecucion_principal ep
                    JOIN unidades_ejecutoras ue ON ep.unidad_ejecutora_id = ue.id
                    WHERE ep.tipo_ejecucion_id = 1
                    GROUP BY ue.id
                    ORDER BY devengado DESC
                ")->fetchAll();
                
                // Por Grupo de Gasto
                $porGrupo = $db->query("
                    SELECT gg.nombre, 
                           SUM(ep.vigente) as vigente,
                           SUM(ep.devengado) as devengado
                    FROM ejecucion_principal ep
                    JOIN grupos_gasto gg ON ep.grupo_gasto_id = gg.id
                    WHERE ep.tipo_ejecucion_id = 3
                    GROUP BY gg.id
                    ORDER BY vigente DESC
                ")->fetchAll();
                
                // Por Fuente de Financiamiento
                $porFuente = $db->query("
                    SELECT ff.nombre, 
                           SUM(ep.vigente) as vigente,
                           SUM(ep.devengado) as devengado
                    FROM ejecucion_principal ep
                    JOIN fuentes_financiamiento ff ON ep.fuente_financiamiento_id = ff.id
                    WHERE ep.tipo_ejecucion_id = 4
                    GROUP BY ff.id
                    ORDER BY vigente DESC
                ")->fetchAll();
                
                jsonResponse([
                    'success' => true,
                    'data' => [
                        'por_unidad' => $porUnidad,
                        'por_grupo_gasto' => $porGrupo,
                        'por_fuente' => $porFuente
                    ]
                ]);
            }
            break;
        
        default:
            jsonResponse([
                'success' => false,
                'message' => 'Endpoint no encontrado',
                'available_endpoints' => [
                    'dashboard', 'ejecucion', 'unidades', 'detalle', 
                    'ministerios', 'programas', 'grupos-gasto', 'fuentes',
                    'config', 'bitacora', 'estadisticas'
                ]
            ], 404);
    }
    
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => DEBUG_MODE ? $e->getMessage() : null
    ], 500);
}
