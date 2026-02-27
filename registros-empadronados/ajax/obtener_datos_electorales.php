<?php
/**
 * ajax/obtener_datos_electorales.php
 * Endpoint AJAX para obtener datos electorales
 * Sistema de Registro de Empadronados
 */

require_once '../config/db.php';
require_once '../includes/funciones.php';
require_once '../includes/permisos.php';

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
            // Obtener estadísticas generales de votos
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(padron) as total_padron,
                    SUM(validos) as total_validos,
                    SUM(nulos) as total_nulos,
                    SUM(blanco) as total_blanco,
                    SUM(emitidos) as total_emitidos,
                    SUM(total) as total_votos,
                    COUNT(DISTINCT mesa) as total_mesas,
                    COUNT(DISTINCT departamento) as total_departamentos,
                    COUNT(DISTINCT municipio) as total_municipios
                FROM resultados_electorales 
                $whereClause
            ");
            $stmt->execute($params);
            $estadisticas = $stmt->fetch();
            
            // Calcular porcentajes
            $padron = (int)$estadisticas['total_padron'];
            $emitidos = (int)$estadisticas['total_emitidos'];
            $estadisticas['porcentaje_participacion'] = $padron > 0 ? round(($emitidos / $padron) * 100, 2) : 0;
            
            echo json_encode([
                'success' => true,
                'data' => $estadisticas
            ]);
            break;
            
        case 'votos_por_partido':
            // Obtener votos totales por partido político
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(une) as une,
                    SUM(azul) as azul,
                    SUM(valor_unionista) as valor_unionista,
                    SUM(cabal) as cabal,
                    SUM(todos) as todos,
                    SUM(vamos) as vamos,
                    SUM(phg) as phg,
                    SUM(pr) as pr,
                    SUM(pin) as pin,
                    SUM(elefante) as elefante,
                    SUM(victoria) as victoria,
                    SUM(semilla) as semilla,
                    SUM(fcn_nacion) as fcn_nacion,
                    SUM(ppn) as ppn,
                    SUM(ur) as ur,
                    SUM(urng_maiz_winaq) as urng_maiz_winaq,
                    SUM(creo) as creo,
                    SUM(bien) as bien,
                    SUM(viva) as viva,
                    SUM(mi_familia) as mi_familia,
                    SUM(cambio) as cambio,
                    SUM(vos) as vos
                FROM resultados_electorales 
                $whereClause
            ");
            $stmt->execute($params);
            $votos = $stmt->fetch();
            
            // Formatear datos para gráfica
            $partidos = [];
            foreach ($votos as $partido => $total) {
                if ($total > 0) {
                    $partidos[] = [
                        'partido' => strtoupper(str_replace('_', ' ', $partido)),
                        'votos' => (int)$total
                    ];
                }
            }
            
            // Ordenar por votos descendente
            usort($partidos, function($a, $b) {
                return $b['votos'] - $a['votos'];
            });
            
            echo json_encode([
                'success' => true,
                'data' => $partidos
            ]);
            break;
            
        case 'top_partidos':
            // Top N partidos con más votos
            $limite = $_GET['limite'] ?? 5;
            
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(une) as une,
                    SUM(azul) as azul,
                    SUM(valor_unionista) as valor_unionista,
                    SUM(cabal) as cabal,
                    SUM(todos) as todos,
                    SUM(vamos) as vamos,
                    SUM(phg) as phg,
                    SUM(pr) as pr,
                    SUM(pin) as pin,
                    SUM(elefante) as elefante,
                    SUM(victoria) as victoria,
                    SUM(semilla) as semilla,
                    SUM(fcn_nacion) as fcn_nacion,
                    SUM(ppn) as ppn,
                    SUM(ur) as ur,
                    SUM(urng_maiz_winaq) as urng_maiz_winaq,
                    SUM(creo) as creo,
                    SUM(bien) as bien,
                    SUM(viva) as viva,
                    SUM(mi_familia) as mi_familia,
                    SUM(cambio) as cambio,
                    SUM(vos) as vos
                FROM resultados_electorales 
                $whereClause
            ");
            $stmt->execute($params);
            $votos = $stmt->fetch();
            
            // Convertir a array y ordenar
            $partidos = [];
            foreach ($votos as $partido => $total) {
                if ($total > 0) {
                    $partidos[] = [
                        'partido' => strtoupper(str_replace('_', ' ', $partido)),
                        'votos' => (int)$total
                    ];
                }
            }
            
            usort($partidos, function($a, $b) {
                return $b['votos'] - $a['votos'];
            });
            
            // Limitar resultados
            $partidos = array_slice($partidos, 0, (int)$limite);
            
            echo json_encode([
                'success' => true,
                'data' => $partidos
            ]);
            break;
            
        case 'resultados_por_departamento':
            // Resultados agrupados por departamento
            $stmt = $pdo->prepare("
                SELECT 
                    departamento,
                    SUM(padron) as padron,
                    SUM(validos) as validos,
                    SUM(nulos) as nulos,
                    SUM(blanco) as blanco,
                    SUM(emitidos) as emitidos,
                    SUM(total) as total,
                    COUNT(DISTINCT mesa) as total_mesas,
                    COUNT(DISTINCT municipio) as total_municipios
                FROM resultados_electorales 
                $whereClause
                GROUP BY departamento
                ORDER BY emitidos DESC
            ");
            $stmt->execute($params);
            $datos = $stmt->fetchAll();
            
            // Calcular porcentaje de participación
            foreach ($datos as &$row) {
                $padron = (int)$row['padron'];
                $emitidos = (int)$row['emitidos'];
                $row['participacion'] = $padron > 0 ? round(($emitidos / $padron) * 100, 2) : 0;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $datos
            ]);
            break;
            
        case 'resultados_por_municipio':
            // Resultados agrupados por municipio
            $departamentoFiltro = $_GET['departamento'] ?? null;
            
            if ($departamentoFiltro) {
                $whereClause .= " AND departamento = :dept_filtro";
                $params[':dept_filtro'] = $departamentoFiltro;
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    municipio,
                    departamento,
                    SUM(padron) as padron,
                    SUM(validos) as validos,
                    SUM(nulos) as nulos,
                    SUM(blanco) as blanco,
                    SUM(emitidos) as emitidos,
                    SUM(total) as total,
                    COUNT(DISTINCT mesa) as total_mesas
                FROM resultados_electorales 
                $whereClause
                GROUP BY municipio, departamento
                ORDER BY emitidos DESC
            ");
            $stmt->execute($params);
            $datos = $stmt->fetchAll();
            
            // Calcular porcentaje de participación
            foreach ($datos as &$row) {
                $padron = (int)$row['padron'];
                $emitidos = (int)$row['emitidos'];
                $row['participacion'] = $padron > 0 ? round(($emitidos / $padron) * 100, 2) : 0;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $datos
            ]);
            break;
            
        case 'partido_ganador_por_departamento':
            // Partido con más votos por departamento
            $stmt = $pdo->prepare("
                SELECT 
                    departamento,
                    SUM(une) as une,
                    SUM(semilla) as semilla,
                    SUM(cabal) as cabal,
                    SUM(vamos) as vamos,
                    SUM(valor_unionista) as valor_unionista
                FROM resultados_electorales 
                $whereClause
                GROUP BY departamento
            ");
            $stmt->execute($params);
            $datos = $stmt->fetchAll();
            
            // Determinar ganador por departamento
            $resultados = [];
            foreach ($datos as $row) {
                $votos = [
                    'UNE' => (int)$row['une'],
                    'SEMILLA' => (int)$row['semilla'],
                    'CABAL' => (int)$row['cabal'],
                    'VAMOS' => (int)$row['vamos'],
                    'VALOR UNIONISTA' => (int)$row['valor_unionista']
                ];
                
                arsort($votos);
                $ganador = array_key_first($votos);
                
                $resultados[] = [
                    'departamento' => $row['departamento'],
                    'ganador' => $ganador,
                    'votos' => $votos[$ganador]
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $resultados
            ]);
            break;
            
        case 'comparativa_partidos_departamento':
            // Comparar votos de partidos principales en un departamento
            $departamentoFiltro = $_GET['departamento'] ?? null;
            
            if (!$departamentoFiltro) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Debe especificar un departamento'
                ]);
                break;
            }
            
            $whereClause .= " AND departamento = :dept_filtro";
            $params[':dept_filtro'] = $departamentoFiltro;
            
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(une) as une,
                    SUM(semilla) as semilla,
                    SUM(cabal) as cabal,
                    SUM(vamos) as vamos,
                    SUM(valor_unionista) as valor_unionista,
                    SUM(vos) as vos
                FROM resultados_electorales 
                $whereClause
            ");
            $stmt->execute($params);
            $datos = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'data' => $datos
            ]);
            break;
            
        case 'detalle_mesa':
            // Detalle completo de una mesa específica
            $mesa = $_GET['mesa'] ?? null;
            
            if (!$mesa) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Debe especificar una mesa'
                ]);
                break;
            }
            
            $whereClause .= " AND mesa = :mesa";
            $params[':mesa'] = $mesa;
            
            $stmt = $pdo->prepare("
                SELECT * 
                FROM resultados_electorales 
                $whereClause
                LIMIT 1
            ");
            $stmt->execute($params);
            $datos = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'data' => $datos
            ]);
            break;
            
        case 'lista_departamentos':
            // Lista de departamentos disponibles
            $stmt = $pdo->prepare("
                SELECT DISTINCT departamento 
                FROM resultados_electorales 
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
                FROM resultados_electorales 
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
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Acción no válida'
            ]);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Error en AJAX Electoral: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al procesar la solicitud'
    ]);
} catch (Exception $e) {
    error_log("Error general en AJAX Electoral: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>