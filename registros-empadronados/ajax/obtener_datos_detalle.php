<?php
/**
 * ajax/obtener_datos_detalle.php
 * Endpoint para obtener datos combinados de resultados_electorales y empadronados
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

header('Content-Type: application/json');

try {
    $pdo = obtenerConexion();
    $accion = $_GET['accion'] ?? '';
    $rol = obtenerRolUsuario();
    $departamentoUsuario = obtenerDepartamentoUsuario();
    $municipioUsuario = obtenerMunicipioUsuario();
    
    switch ($accion) {
        case 'estadisticas_departamento':
            // Estadísticas generales del departamento
            $departamento = $_GET['departamento'] ?? '';
            
            if (empty($departamento)) {
                echo json_encode(['success' => false, 'error' => 'Departamento no especificado']);
                exit;
            }
            
            // Verificar permisos
            if ($rol === ROL_DIPUTADO && $departamento !== $departamentoUsuario) {
                echo json_encode(['success' => false, 'error' => 'Sin permisos']);
                exit;
            }
            
            $sql = "
                SELECT 
                    SUM(emitidos) as total_emitidos,
                    SUM(validos) as total_validos,
                    SUM(padron) as total_padron_23,
                    SUM(nulos) as total_nulos,
                    SUM(total) as total_votos,
                    COUNT(DISTINCT centro_de_votacion) as total_centros_votacion,
                    COUNT(DISTINCT mesa) as total_mesas
                FROM resultados_electorales
                WHERE departamento = :departamento
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':departamento' => $departamento]);
            $estadisticas = $stmt->fetch();
            
            // Calcular porcentaje de votos válidos
            $total_emitidos = (int)$estadisticas['total_emitidos'];
            $total_validos = (int)$estadisticas['total_validos'];
            $porcentaje_validos = $total_emitidos > 0 ? round(($total_validos / $total_emitidos) * 100, 1) : 0;
            
            $estadisticas['porcentaje_validos'] = $porcentaje_validos;
            
            echo json_encode([
                'success' => true,
                'data' => $estadisticas
            ]);
            break;

        case 'lista_departamentos':
        // Lista de departamentos disponibles
        $whereClause = "WHERE 1=1";
        $params = [];
        
        // Si es diputado, solo puede ver su departamento
        if ($rol === ROL_DIPUTADO) {
            $whereClause .= " AND departamento = :departamento";
            $params[':departamento'] = $departamentoUsuario;
        }
        
        $stmt = $pdo->prepare("
            SELECT DISTINCT departamento 
            FROM resultados_electorales 
            $whereClause
            ORDER BY departamento
        ");
        $stmt->execute($params);
        $departamentos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode([
            'success' => true,
            'data' => $departamentos
        ]);
        break;
            
        case 'top_partidos_departamento':
            // Top 5 partidos del departamento
            $departamento = $_GET['departamento'] ?? '';
            
            if (empty($departamento)) {
                echo json_encode(['success' => false, 'error' => 'Departamento no especificado']);
                exit;
            }
            
            // Verificar permisos
            if ($rol === ROL_DIPUTADO && $departamento !== $departamentoUsuario) {
                echo json_encode(['success' => false, 'error' => 'Sin permisos']);
                exit;
            }
            
            $sql = "
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
                WHERE departamento = :departamento
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':departamento' => $departamento]);
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
            
            // Tomar solo top 5
            $partidos = array_slice($partidos, 0, 5);
            
            echo json_encode([
                'success' => true,
                'data' => $partidos
            ]);
            break;
            
        case 'datos_municipios_departamento':
            // Obtener departamento del parámetro
            $departamento = $_GET['departamento'] ?? '';
            
            if (empty($departamento)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Departamento no especificado'
                ]);
                exit;
            }
            
            // Verificar permisos según rol
            if ($rol === ROL_DIPUTADO && $departamento !== $departamentoUsuario) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No tienes permisos para ver este departamento'
                ]);
                exit;
            }
            
            // Consulta combinada: votos de resultados_electorales + padrón 25 de empadronados
            $sql = "
                SELECT 
                    r.municipio,
                    SUM(r.validos) as votos,
                    SUM(r.padron) as padron_23,
                    COALESCE(e.total, 0) as padron_25
                FROM resultados_electorales r
                LEFT JOIN empadronados e ON r.municipio = e.municipio AND r.departamento = e.departamento
                WHERE r.departamento = :departamento
                GROUP BY r.municipio, e.total
                ORDER BY votos DESC
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':departamento' => $departamento]);
            $datos = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $datos,
                'departamento' => $departamento
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Acción no válida'
            ]);
            break;
        
        case 'votos_partido_por_municipio':
            // Votos de un partido específico por municipio
            $departamento = $_GET['departamento'] ?? '';
            $partido = $_GET['partido'] ?? '';
            
            if (empty($departamento) || empty($partido)) {
                echo json_encode(['success' => false, 'error' => 'Faltan parámetros']);
                exit;
            }
            
            // Verificar permisos
            if ($rol === ROL_DIPUTADO && $departamento !== $departamentoUsuario) {
                echo json_encode(['success' => false, 'error' => 'Sin permisos']);
                exit;
            }
            
            // Normalizar nombre del partido para la columna
            $partidoColumna = strtolower(str_replace(' ', '_', $partido));
            
            // Lista de columnas válidas de partidos
            $partidosValidos = [
                'une', 'azul', 'valor_unionista', 'cabal', 'todos', 'vamos', 'phg', 'pr', 
                'pin', 'elefante', 'victoria', 'semilla', 'fcn_nacion', 'ppn', 'ur', 
                'urng_maiz_winaq', 'creo', 'bien', 'viva', 'mi_familia', 'cambio', 'vos'
            ];
            
            if (!in_array($partidoColumna, $partidosValidos)) {
                echo json_encode(['success' => false, 'error' => 'Partido no válido']);
                exit;
            }
            
            $sql = "
                SELECT 
                    r.municipio,
                    SUM(r.{$partidoColumna}) as votos_partido,
                    SUM(r.padron) as padron_23,
                    COALESCE(e.total, 0) as padron_25
                FROM resultados_electorales r
                LEFT JOIN empadronados e ON r.municipio = e.municipio AND r.departamento = e.departamento
                WHERE r.departamento = :departamento
                GROUP BY r.municipio, e.total
                ORDER BY votos_partido DESC
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':departamento' => $departamento]);
            $datos = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $datos,
                'partido' => $partido
            ]);
            break;

        case 'lista_partidos':
            // Lista de todos los partidos políticos
            $partidos = [
                'UNE', 'AZUL', 'VALOR UNIONISTA', 'CABAL', 'TODOS', 'VAMOS', 
                'PHG', 'PR', 'PIN', 'ELEFANTE', 'VICTORIA', 'SEMILLA', 
                'FCN NACION', 'PPN', 'UR', 'URNG MAIZ WINAQ', 'CREO', 
                'BIEN', 'VIVA', 'MI FAMILIA', 'CAMBIO', 'VOS'
            ];
            
            echo json_encode([
                'success' => true,
                'data' => $partidos
            ]);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Error en AJAX Detalle: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al procesar la solicitud'
    ]);
}
?>