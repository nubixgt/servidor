<?php
require_once '../config/db.php';
require_once '../includes/funciones.php';
require_once '../includes/permisos.php';

if (!estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

header('Content-Type: application/json');

try {
    $pdo = obtenerConexion();
    $accion = $_GET['accion'] ?? '';
    $rol = obtenerRolUsuario();
    $departamentoUsuario = obtenerDepartamentoUsuario();
    $municipioUsuario = obtenerMunicipioUsuario();
    
    switch ($accion) {
        case 'estadisticas_alcaldes':
            $whereClause = "WHERE 1=1";
            $params = [];
            
            // Filtros por rol
            if ($rol === ROL_ALCALDE) {
                $whereClause .= " AND departamento = :departamento AND municipio = :municipio";
                $params[':departamento'] = $departamentoUsuario;
                $params[':municipio'] = $municipioUsuario;
            } elseif ($rol === ROL_DIPUTADO) {
                $whereClause .= " AND departamento = :departamento";
                $params[':departamento'] = $departamentoUsuario;
            }
            
            $sql = "
                SELECT 
                    COUNT(DISTINCT centro_de_votacion) as total_centros_votacion,
                    COUNT(DISTINCT mesa) as total_mesas,
                    SUM(emitidos) as total_emitidos,
                    COUNT(DISTINCT municipio) as total_municipios
                FROM resultados_electorales
                $whereClause
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $estadisticas = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'data' => $estadisticas
            ]);
            break;
            
        case 'centros_y_mesas':
            $whereClause = "WHERE 1=1";
            $params = [];
            
            // Filtros por rol
            if ($rol === ROL_ALCALDE) {
                $whereClause .= " AND departamento = :departamento AND municipio = :municipio";
                $params[':departamento'] = $departamentoUsuario;
                $params[':municipio'] = $municipioUsuario;
            } elseif ($rol === ROL_DIPUTADO) {
                $whereClause .= " AND departamento = :departamento";
                $params[':departamento'] = $departamentoUsuario;
            }
            
            // Filtros de URL
            $departamento = $_GET['departamento'] ?? '';
            $municipio = $_GET['municipio'] ?? '';
            
            if ($departamento && ($rol === ROL_ADMINISTRADOR || $rol === ROL_PRESIDENTE)) {
                $whereClause .= " AND departamento = :dept_filtro";
                $params[':dept_filtro'] = $departamento;
            }
            
            if ($municipio && ($rol === ROL_ADMINISTRADOR || $rol === ROL_PRESIDENTE)) {
                $whereClause .= " AND municipio = :mun_filtro";
                $params[':mun_filtro'] = $municipio;
            }
            
            $sql = "
                SELECT 
                    departamento,
                    municipio,
                    centro_de_votacion,
                    mesa,
                    emitidos,
                    padron
                FROM resultados_electorales
                $whereClause
                ORDER BY departamento, municipio, centro_de_votacion, mesa
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $datos
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al procesar']);
}
?>