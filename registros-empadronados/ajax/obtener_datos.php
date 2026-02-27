<?php
/**
 * ajax/obtener_datos.php
 * Endpoint AJAX para obtener datos
 * Sistema de Registro de Empadronados
 */

require_once '../config/db.php';
require_once '../includes/funciones.php';
require_once '../includes/permisos.php';

// Verificar que sea una petición AJAX
/*
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die('Acceso denegado');
}
*/

// Verificar autenticación
if (!estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

// Establecer header JSON
header('Content-Type: application/json');

try {
    $pdo = obtenerConexion();
    $accion = $_GET['accion'] ?? '';
    $rol = obtenerRolUsuario();
    $departamento = obtenerDepartamentoUsuario();
    $municipio = obtenerMunicipioUsuario();
    
    // Construir WHERE según el rol
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($rol === ROL_ALCALDE) {
        $whereClause .= " AND departamento = :departamento AND municipio = :municipio";
        $params[':departamento'] = $departamento;
        $params[':municipio'] = $municipio;
    } elseif ($rol === ROL_DIPUTADO) {
        $whereClause .= " AND departamento = :departamento";
        $params[':departamento'] = $departamento;
    }
    
    switch ($accion) {
        case 'estadisticas_generales':
            // Obtener estadísticas generales
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(total) as total_personas,
                    SUM(total_mujeres) as total_mujeres,
                    SUM(total_hombres) as total_hombres,
                    COUNT(DISTINCT municipio) as total_municipios,
                    COUNT(DISTINCT departamento) as total_departamentos
                FROM empadronados 
                $whereClause
            ");
            $stmt->execute($params);
            $estadisticas = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'data' => $estadisticas
            ]);
            break;
            
        case 'datos_por_departamento':
            // Obtener datos agrupados por departamento
            $stmt = $pdo->prepare("
                SELECT 
                    departamento,
                    SUM(total) as total,
                    SUM(total_mujeres) as total_mujeres,
                    SUM(total_hombres) as total_hombres,
                    COUNT(DISTINCT municipio) as num_municipios
                FROM empadronados 
                $whereClause
                GROUP BY departamento
                ORDER BY total DESC
            ");
            $stmt->execute($params);
            $datos = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $datos
            ]);
            break;
            
        case 'datos_por_municipio':
            // Obtener datos agrupados por municipio
            $departamentoFiltro = $_GET['departamento'] ?? null;
            
            if ($departamentoFiltro) {
                $whereClause .= " AND departamento = :dept_filtro";
                $params[':dept_filtro'] = $departamentoFiltro;
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    municipio,
                    departamento,
                    total,
                    total_mujeres,
                    total_hombres
                FROM empadronados 
                $whereClause
                ORDER BY total DESC
            ");
            $stmt->execute($params);
            $datos = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $datos
            ]);
            break;
            
        case 'top_municipios':
            // Top N municipios con mayor población
            $limite = $_GET['limite'] ?? 10;
            
            $stmt = $pdo->prepare("
                SELECT 
                    municipio,
                    departamento,
                    total
                FROM empadronados 
                $whereClause
                ORDER BY total DESC
                LIMIT :limite
            ");
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $datos
            ]);
            break;
            
        case 'datos_alfabetismo':
            // Datos de alfabetismo
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(mujeres_alfabetas) as mujeres_alfabetas,
                    SUM(mujeres_analfabetas) as mujeres_analfabetas,
                    SUM(hombres_alfabetas) as hombres_alfabetas,
                    SUM(hombres_analfabetas) as hombres_analfabetas
                FROM empadronados 
                $whereClause
            ");
            $stmt->execute($params);
            $datos = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'data' => $datos
            ]);
            break;
            
        case 'datos_edades':
            // Datos de rangos de edad
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(edad_18a25) as edad_18a25,
                    SUM(edad_26a30) as edad_26a30,
                    SUM(edad_31a35) as edad_31a35,
                    SUM(edad_36a40) as edad_36a40,
                    SUM(edad_41a45) as edad_41a45,
                    SUM(edad_46a50) as edad_46a50,
                    SUM(edad_51a55) as edad_51a55,
                    SUM(edad_56a60) as edad_56a60,
                    SUM(edad_61a65) as edad_61a65,
                    SUM(edad_66a70) as edad_66a70,
                    SUM(edad_mayoroigual70) as edad_mayoroigual70
                FROM empadronados 
                $whereClause
            ");
            $stmt->execute($params);
            $datos = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'data' => $datos
            ]);
            break;
            
        case 'lista_departamentos':
            // Lista de departamentos
            $stmt = $pdo->prepare("
                SELECT DISTINCT departamento 
                FROM empadronados 
                $whereClause
                ORDER BY departamento
            ");
            $stmt->execute($params);
            $datos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo json_encode([
                'success' => true,
                'data' => $datos
            ]);
            break;
            
        case 'lista_municipios':
            // Lista de municipios de un departamento
            $departamentoFiltro = $_GET['departamento'] ?? null;
            
            if ($departamentoFiltro) {
                $whereClause .= " AND departamento = :dept_filtro";
                $params[':dept_filtro'] = $departamentoFiltro;
            }
            
            $stmt = $pdo->prepare("
                SELECT DISTINCT municipio 
                FROM empadronados 
                $whereClause
                ORDER BY municipio
            ");
            $stmt->execute($params);
            $datos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo json_encode([
                'success' => true,
                'data' => $datos
            ]);
            break;
            
        case 'datos_mapa_departamentos':
            // 🆕 NUEVO: Datos para el mapa de Guatemala
            // Devuelve población total por departamento para colorear el mapa
            
            $stmt = $pdo->prepare("
                SELECT 
                    departamento,
                    SUM(total) as total,
                    SUM(total_mujeres) as mujeres,
                    SUM(total_hombres) as hombres,
                    COUNT(DISTINCT municipio) as municipios
                FROM empadronados 
                $whereClause
                GROUP BY departamento
                ORDER BY departamento
            ");
            $stmt->execute($params);
            $resultados = $stmt->fetchAll();
            
            // Calcular total general
            $stmtTotal = $pdo->prepare("
                SELECT SUM(total) as total_general 
                FROM empadronados 
                $whereClause
            ");
            $stmtTotal->execute($params);
            $totalGeneral = $stmtTotal->fetch()['total_general'] ?? 0;
            
            // Formatear datos en un objeto indexado por nombre de departamento
            $datosDepartamentos = [];
            foreach ($resultados as $row) {
                $datosDepartamentos[$row['departamento']] = [
                    'total' => (int)$row['total'],
                    'mujeres' => (int)$row['mujeres'],
                    'hombres' => (int)$row['hombres'],
                    'municipios' => (int)$row['municipios']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $datosDepartamentos,
                'total_general' => (int)$totalGeneral
            ]);
            break;
            
        case 'buscar':
            // Búsqueda general
            $termino = $_GET['termino'] ?? '';
            
            if (empty($termino)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Debe proporcionar un término de búsqueda'
                ]);
                break;
            }
            
            $whereClause .= " AND (departamento LIKE :termino OR municipio LIKE :termino)";
            $params[':termino'] = "%$termino%";
            
            $stmt = $pdo->prepare("
                SELECT 
                    municipio,
                    departamento,
                    total,
                    total_mujeres,
                    total_hombres
                FROM empadronados 
                $whereClause
                ORDER BY total DESC
                LIMIT 20
            ");
            $stmt->execute($params);
            $datos = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $datos
            ]);
            break;
            
        case 'verificar_sesion':
            // Verificar si la sesión sigue activa
            $activa = estaAutenticado() && usuarioActivo();
            
            echo json_encode([
                'success' => true,
                'activa' => $activa
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Acción no válida'
            ]);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Error en AJAX: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al procesar la solicitud'
    ]);
} catch (Exception $e) {
    error_log("Error general en AJAX: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>