<?php
/**
 * ajax/graficas_datos.php
 * Endpoint AJAX para datos de gráficas CON SISTEMA DE FILTROS
 * VERSIÓN FINAL CORREGIDA
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
    $tipo = $_GET['tipo'] ?? '';
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

    // ============================================
    // FILTRO POR DEPARTAMENTO (NUEVO)
    // ============================================
    if ($tipo === 'filtro_departamento') {
        $departamentoFiltro = $_GET['departamento'] ?? '';
        $rangoEdadFiltro = $_GET['rango_edad'] ?? '';
        $ordenar = $_GET['ordenar'] ?? 'poblacion';
        
        // Construir WHERE
        $whereDept = "WHERE 1=1";
        $paramsDept = [];
        
        if ($departamentoFiltro) {
            $whereDept .= " AND departamento = :departamento";
            $paramsDept[':departamento'] = $departamentoFiltro;
        }
        
        // Determinar qué campos sumar según el filtro de edad
        if ($rangoEdadFiltro) {
            $rangoMap = [
                '18-25' => 'edad_18a25',
                '26-30' => 'edad_26a30',
                '31-35' => 'edad_31a35',
                '36-40' => 'edad_36a40',
                '41-45' => 'edad_41a45',
                '46-50' => 'edad_46a50',
                '51-55' => 'edad_51a55',
                '56-60' => 'edad_56a60',
                '61-65' => 'edad_61a65',
                '66-70' => 'edad_66a70',
                '70+' => 'edad_mayoroigual70'
            ];
            
            $campoEdad = $rangoMap[$rangoEdadFiltro] ?? null;
            
            if ($campoEdad) {
                // Estadísticas con filtro de edad
                $stmt = $pdo->prepare("
                    SELECT 
                        SUM($campoEdad) as total_personas,
                        SUM(mujeres_$campoEdad) as total_mujeres,
                        SUM(hombres_$campoEdad) as total_hombres,
                        COUNT(*) as total_municipios,
                        COUNT(DISTINCT departamento) as total_departamentos
                    FROM empadronados 
                    $whereDept
                ");
            } else {
                // Sin filtro de edad válido
                $stmt = $pdo->prepare("
                    SELECT 
                        SUM(total) as total_personas,
                        SUM(total_mujeres) as total_mujeres,
                        SUM(total_hombres) as total_hombres,
                        COUNT(*) as total_municipios,
                        COUNT(DISTINCT departamento) as total_departamentos
                    FROM empadronados 
                    $whereDept
                ");
            }
        } else {
            // Sin filtro de edad
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(total) as total_personas,
                    SUM(total_mujeres) as total_mujeres,
                    SUM(total_hombres) as total_hombres,
                    COUNT(*) as total_municipios,
                    COUNT(DISTINCT departamento) as total_departamentos
                FROM empadronados 
                $whereDept
            ");
        }
        
        $stmt->execute($paramsDept);
        $estadisticas = $stmt->fetch();
        
        // Datos de edades
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
            $whereDept
        ");
        $stmt->execute($paramsDept);
        $datosEdades = $stmt->fetch();
        
        // Datos de género
        $stmt = $pdo->prepare("
            SELECT 
                SUM(total_mujeres) as mujeres,
                SUM(total_hombres) as hombres
            FROM empadronados 
            $whereDept
        ");
        $stmt->execute($paramsDept);
        $datosGenero = $stmt->fetch();
        
        // Datos de alfabetismo
        $stmt = $pdo->prepare("
            SELECT 
                SUM(mujeres_alfabetas) as mujeres_alfabetas,
                SUM(mujeres_analfabetas) as mujeres_analfabetas,
                SUM(hombres_alfabetas) as hombres_alfabetas,
                SUM(hombres_analfabetas) as hombres_analfabetas
            FROM empadronados 
            $whereDept
        ");
        $stmt->execute($paramsDept);
        $datosAlfabetismo = $stmt->fetch();
        
        // Top 5 municipios
        $stmt = $pdo->prepare("
            SELECT municipio, departamento, total
            FROM empadronados 
            $whereDept
            ORDER BY total DESC
            LIMIT 5
        ");
        $stmt->execute($paramsDept);
        $topMunicipios = $stmt->fetchAll();
        
        // Respuesta
        echo json_encode([
            'estadisticas' => [
                'total_personas' => (int)($estadisticas['total_personas'] ?? 0),
                'total_mujeres' => (int)($estadisticas['total_mujeres'] ?? 0),
                'total_hombres' => (int)($estadisticas['total_hombres'] ?? 0),
                'total_municipios' => (int)($estadisticas['total_municipios'] ?? 0),
                'total_departamentos' => (int)($estadisticas['total_departamentos'] ?? 0)
            ],
            'edades' => [
                'labels' => ['18-25', '26-30', '31-35', '36-40', '41-45', '46-50', '51-55', '56-60', '61-65', '66-70', '70+'],
                'values' => [
                    (int)($datosEdades['edad_18a25'] ?? 0),
                    (int)($datosEdades['edad_26a30'] ?? 0),
                    (int)($datosEdades['edad_31a35'] ?? 0),
                    (int)($datosEdades['edad_36a40'] ?? 0),
                    (int)($datosEdades['edad_41a45'] ?? 0),
                    (int)($datosEdades['edad_46a50'] ?? 0),
                    (int)($datosEdades['edad_51a55'] ?? 0),
                    (int)($datosEdades['edad_56a60'] ?? 0),
                    (int)($datosEdades['edad_61a65'] ?? 0),
                    (int)($datosEdades['edad_66a70'] ?? 0),
                    (int)($datosEdades['edad_mayoroigual70'] ?? 0)
                ]
            ],
            'genero' => [
                'labels' => ['Mujeres', 'Hombres'],
                'values' => [
                    (int)($datosGenero['mujeres'] ?? 0),
                    (int)($datosGenero['hombres'] ?? 0)
                ]
            ],
            'alfabetismo' => [
                'alfabetas' => [
                    (int)($datosAlfabetismo['mujeres_alfabetas'] ?? 0),
                    (int)($datosAlfabetismo['hombres_alfabetas'] ?? 0)
                ],
                'analfabetas' => [
                    (int)($datosAlfabetismo['mujeres_analfabetas'] ?? 0),
                    (int)($datosAlfabetismo['hombres_analfabetas'] ?? 0)
                ]
            ],
            'top_municipios' => $topMunicipios
        ]);
        exit;
    }
    
    // ============================================
    // SISTEMA DE FILTROS CRUZADOS
    // ============================================
    if ($tipo === 'filtrado') {
        $filtroTipo = $_GET['filtro_tipo'] ?? '';
        $filtroValor = $_GET['filtro_valor'] ?? '';
        
        // DEBUG
        $debugInfo = [
            'filtro_recibido' => true,
            'filtro_tipo' => $filtroTipo,
            'filtro_valor' => $filtroValor,
            'where_inicial' => $whereClause,
            'params_iniciales' => $params
        ];
        
        $filtroAplicado = false;
        $campoEdad = null;
        
        // Identificar el tipo de filtro (NO modificar WHERE para género/alfabetismo)
        switch ($filtroTipo) {
            case 'edad':
                $rangoMap = [
                    '18-25' => 'edad_18a25',
                    '26-30' => 'edad_26a30',
                    '31-35' => 'edad_31a35',
                    '36-40' => 'edad_36a40',
                    '41-45' => 'edad_41a45',
                    '46-50' => 'edad_46a50',
                    '51-55' => 'edad_51a55',
                    '56-60' => 'edad_56a60',
                    '61-65' => 'edad_61a65',
                    '66-70' => 'edad_66a70',
                    '70+' => 'edad_mayoroigual70'
                ];
                
                $campoEdad = $rangoMap[$filtroValor] ?? null;
                if ($campoEdad) {
                    $filtroAplicado = true;
                    $debugInfo['campo_edad'] = $campoEdad;
                    $debugInfo['filtro_aplicado'] = true;
                }
                break;
                
            case 'genero':
                $filtroAplicado = true;
                $debugInfo['filtro_aplicado'] = true;
                break;
                
            case 'alfabetismo':
                $debugInfo['procesando_alfabetismo'] = true;
                $partes = explode('-', $filtroValor);
                $debugInfo['partes'] = $partes;
                
                if (count($partes) >= 2) {
                    $filtroAplicado = true;
                    $debugInfo['filtro_aplicado'] = true;
                }
                break;
        }
        
        $debugInfo['where_final'] = $whereClause;
        $debugInfo['filtro_aplicado_bool'] = $filtroAplicado;
        
        // ===== 1. ESTADÍSTICAS =====
        if ($filtroTipo === 'edad' && $campoEdad) {
            // Filtro de EDAD: sumar solo ese rango
            $stmt = $pdo->prepare("
                SELECT 
                    SUM($campoEdad) as total_personas,
                    SUM(mujeres_$campoEdad) as total_mujeres,
                    SUM(hombres_$campoEdad) as total_hombres,
                    COUNT(*) as total_municipios,
                    COUNT(DISTINCT departamento) as total_departamentos
                FROM empadronados 
                WHERE 1=1
            ");
            $stmt->execute([]);
            
        } elseif ($filtroTipo === 'genero') {
            // Filtro de GÉNERO: sumar solo ese género
            if ($filtroValor === 'Mujeres') {
                $stmt = $pdo->prepare("
                    SELECT 
                        SUM(total_mujeres) as total_personas,
                        SUM(total_mujeres) as total_mujeres,
                        0 as total_hombres,
                        COUNT(*) as total_municipios,
                        COUNT(DISTINCT departamento) as total_departamentos
                    FROM empadronados 
                    WHERE 1=1
                ");
            } else {
                $stmt = $pdo->prepare("
                    SELECT 
                        SUM(total_hombres) as total_personas,
                        0 as total_mujeres,
                        SUM(total_hombres) as total_hombres,
                        COUNT(*) as total_municipios,
                        COUNT(DISTINCT departamento) as total_departamentos
                    FROM empadronados 
                    WHERE 1=1
                ");
            }
            $stmt->execute([]);
            
        } elseif ($filtroTipo === 'alfabetismo') {
            // Filtro de ALFABETISMO: sumar solo esa categoría
            $partes = explode('-', $filtroValor);
            $generoLower = strtolower(trim($partes[0]));
            $categoriaLower = strtolower(trim($partes[1]));
            
            if ($generoLower === 'mujeres' && $categoriaLower === 'alfabetas') {
                $stmt = $pdo->prepare("
                    SELECT 
                        SUM(mujeres_alfabetas) as total_personas,
                        SUM(mujeres_alfabetas) as total_mujeres,
                        0 as total_hombres,
                        COUNT(*) as total_municipios,
                        COUNT(DISTINCT departamento) as total_departamentos
                    FROM empadronados 
                    WHERE 1=1
                ");
            } elseif ($generoLower === 'mujeres' && $categoriaLower === 'analfabetas') {
                $stmt = $pdo->prepare("
                    SELECT 
                        SUM(mujeres_analfabetas) as total_personas,
                        SUM(mujeres_analfabetas) as total_mujeres,
                        0 as total_hombres,
                        COUNT(*) as total_municipios,
                        COUNT(DISTINCT departamento) as total_departamentos
                    FROM empadronados 
                    WHERE 1=1
                ");
            } elseif ($generoLower === 'hombres' && $categoriaLower === 'alfabetas') {
                $stmt = $pdo->prepare("
                    SELECT 
                        SUM(hombres_alfabetas) as total_personas,
                        0 as total_mujeres,
                        SUM(hombres_alfabetas) as total_hombres,
                        COUNT(*) as total_municipios,
                        COUNT(DISTINCT departamento) as total_departamentos
                    FROM empadronados 
                    WHERE 1=1
                ");
            } else { // hombres analfabetas
                $stmt = $pdo->prepare("
                    SELECT 
                        SUM(hombres_analfabetas) as total_personas,
                        0 as total_mujeres,
                        SUM(hombres_analfabetas) as total_hombres,
                        COUNT(*) as total_municipios,
                        COUNT(DISTINCT departamento) as total_departamentos
                    FROM empadronados 
                    WHERE 1=1
                ");
            }
            $stmt->execute([]);
            
        } else {
            // Sin filtro: datos generales
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(total) as total_personas,
                    SUM(total_mujeres) as total_mujeres,
                    SUM(total_hombres) as total_hombres,
                    COUNT(*) as total_municipios,
                    COUNT(DISTINCT departamento) as total_departamentos
                FROM empadronados 
                $whereClause
            ");
            $stmt->execute($params);
        }
        $estadisticas = $stmt->fetch();
        
        // ===== 2. EDADES =====
        if ($filtroTipo === 'edad' && $campoEdad) {
            // Para filtro de edad: mostrar solo ese rango
            $stmt = $pdo->prepare("
                SELECT SUM($campoEdad) as valor_filtrado
                FROM empadronados 
                WHERE 1=1
            ");
            $stmt->execute([]);
            $resultado = $stmt->fetch();
            
            $datosEdades = [
                'edad_18a25' => 0,
                'edad_26a30' => 0,
                'edad_31a35' => 0,
                'edad_36a40' => 0,
                'edad_41a45' => 0,
                'edad_46a50' => 0,
                'edad_51a55' => 0,
                'edad_56a60' => 0,
                'edad_61a65' => 0,
                'edad_66a70' => 0,
                'edad_mayoroigual70' => 0
            ];
            $datosEdades[$campoEdad] = (int)($resultado['valor_filtrado'] ?? 0);
            
        } else {
            // Sin filtro de edad: mostrar todos los rangos
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
                WHERE 1=1
            ");
            $stmt->execute([]);
            $datosEdades = $stmt->fetch();
        }
        
        // ===== 3. GÉNERO =====
        if ($filtroTipo === 'edad' && $campoEdad) {
            // Para filtro de edad: mostrar mujeres/hombres de ese rango
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(mujeres_$campoEdad) as mujeres,
                    SUM(hombres_$campoEdad) as hombres
                FROM empadronados 
                WHERE 1=1
            ");
            $stmt->execute([]);
            
        } elseif ($filtroTipo === 'genero') {
            // Para filtro de género: mostrar solo ese género
            if ($filtroValor === 'Mujeres') {
                $stmt = $pdo->prepare("
                    SELECT 
                        SUM(total_mujeres) as mujeres,
                        0 as hombres
                    FROM empadronados 
                    WHERE 1=1
                ");
            } else {
                $stmt = $pdo->prepare("
                    SELECT 
                        0 as mujeres,
                        SUM(total_hombres) as hombres
                    FROM empadronados 
                    WHERE 1=1
                ");
            }
            $stmt->execute([]);
            
        } elseif ($filtroTipo === 'alfabetismo') {
            // Para filtro de alfabetismo: mostrar solo ese género
            $partes = explode('-', $filtroValor);
            $genero = strtolower($partes[0]);
            
            if ($genero === 'mujeres') {
                $stmt = $pdo->prepare("
                    SELECT 
                        SUM(total_mujeres) as mujeres,
                        0 as hombres
                    FROM empadronados 
                    WHERE 1=1
                ");
            } else {
                $stmt = $pdo->prepare("
                    SELECT 
                        0 as mujeres,
                        SUM(total_hombres) as hombres
                    FROM empadronados 
                    WHERE 1=1
                ");
            }
            $stmt->execute([]);
            
        } else {
            // Sin filtro: mostrar ambos
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(total_mujeres) as mujeres,
                    SUM(total_hombres) as hombres
                FROM empadronados 
                WHERE 1=1
            ");
            $stmt->execute([]);
        }
        $datosGenero = $stmt->fetch();
        
        // ===== 4. ALFABETISMO =====
        if ($filtroTipo === 'alfabetismo') {
            // Para filtro de alfabetismo: mostrar solo esa categoría
            $partes = explode('-', $filtroValor);
            $generoLower = strtolower(trim($partes[0]));
            $categoriaLower = strtolower(trim($partes[1]));
            
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(mujeres_alfabetas) as mujeres_alfabetas,
                    SUM(mujeres_analfabetas) as mujeres_analfabetas,
                    SUM(hombres_alfabetas) as hombres_alfabetas,
                    SUM(hombres_analfabetas) as hombres_analfabetas
                FROM empadronados 
                WHERE 1=1
            ");
            $stmt->execute([]);
            $resultado = $stmt->fetch();
            
            $datosAlfabetismo = [
                'mujeres_alfabetas' => 0,
                'mujeres_analfabetas' => 0,
                'hombres_alfabetas' => 0,
                'hombres_analfabetas' => 0
            ];
            
            if ($generoLower === 'mujeres' && $categoriaLower === 'alfabetas') {
                $datosAlfabetismo['mujeres_alfabetas'] = (int)$resultado['mujeres_alfabetas'];
            } elseif ($generoLower === 'mujeres' && $categoriaLower === 'analfabetas') {
                $datosAlfabetismo['mujeres_analfabetas'] = (int)$resultado['mujeres_analfabetas'];
            } elseif ($generoLower === 'hombres' && $categoriaLower === 'alfabetas') {
                $datosAlfabetismo['hombres_alfabetas'] = (int)$resultado['hombres_alfabetas'];
            } else {
                $datosAlfabetismo['hombres_analfabetas'] = (int)$resultado['hombres_analfabetas'];
            }
            
        } else {
            // Sin filtro: mostrar todas las categorías
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(mujeres_alfabetas) as mujeres_alfabetas,
                    SUM(mujeres_analfabetas) as mujeres_analfabetas,
                    SUM(hombres_alfabetas) as hombres_alfabetas,
                    SUM(hombres_analfabetas) as hombres_analfabetas
                FROM empadronados 
                WHERE 1=1
            ");
            $stmt->execute([]);
            $datosAlfabetismo = $stmt->fetch();
        }
        
        // ===== 5. TOP 5 MUNICIPIOS =====
        if ($filtroTipo === 'edad' && $campoEdad) {
            $stmt = $pdo->prepare("
                SELECT municipio, departamento, $campoEdad as total
                FROM empadronados 
                WHERE 1=1
                ORDER BY $campoEdad DESC
                LIMIT 5
            ");
            $stmt->execute([]);
        } else {
            $stmt = $pdo->prepare("
                SELECT municipio, departamento, total
                FROM empadronados 
                WHERE 1=1
                ORDER BY total DESC
                LIMIT 5
            ");
            $stmt->execute([]);
        }
        $topMunicipios = $stmt->fetchAll();
        
        // Agregar debug
        $debugInfo['total_real_obtenido'] = (int)($estadisticas['total_personas'] ?? 0);
        
        // Respuesta
        echo json_encode([
            'estadisticas' => [
                'total_personas' => (int)($estadisticas['total_personas'] ?? 0),
                'total_mujeres' => (int)($estadisticas['total_mujeres'] ?? 0),
                'total_hombres' => (int)($estadisticas['total_hombres'] ?? 0),
                'total_municipios' => (int)($estadisticas['total_municipios'] ?? 0),
                'total_departamentos' => (int)($estadisticas['total_departamentos'] ?? 0)
            ],
            'edades' => [
                'labels' => ['18-25', '26-30', '31-35', '36-40', '41-45', '46-50', '51-55', '56-60', '61-65', '66-70', '70+'],
                'values' => [
                    (int)($datosEdades['edad_18a25'] ?? 0),
                    (int)($datosEdades['edad_26a30'] ?? 0),
                    (int)($datosEdades['edad_31a35'] ?? 0),
                    (int)($datosEdades['edad_36a40'] ?? 0),
                    (int)($datosEdades['edad_41a45'] ?? 0),
                    (int)($datosEdades['edad_46a50'] ?? 0),
                    (int)($datosEdades['edad_51a55'] ?? 0),
                    (int)($datosEdades['edad_56a60'] ?? 0),
                    (int)($datosEdades['edad_61a65'] ?? 0),
                    (int)($datosEdades['edad_66a70'] ?? 0),
                    (int)($datosEdades['edad_mayoroigual70'] ?? 0)
                ]
            ],
            'genero' => [
                'labels' => ['Mujeres', 'Hombres'],
                'values' => [
                    (int)($datosGenero['mujeres'] ?? 0),
                    (int)($datosGenero['hombres'] ?? 0)
                ]
            ],
            'alfabetismo' => [
                'alfabetas' => [
                    (int)($datosAlfabetismo['mujeres_alfabetas'] ?? 0),
                    (int)($datosAlfabetismo['hombres_alfabetas'] ?? 0)
                ],
                'analfabetas' => [
                    (int)($datosAlfabetismo['mujeres_analfabetas'] ?? 0),
                    (int)($datosAlfabetismo['hombres_analfabetas'] ?? 0)
                ]
            ],
            'top_municipios' => $topMunicipios,
            '_debug' => $debugInfo
        ]);
        exit;
    }
    
    // ============================================
    // Endpoints originales (sin filtro)
    // ============================================
    switch ($tipo) {
        case 'genero':
            $stmt = $pdo->prepare("
                SELECT 
                    SUM(total_mujeres) as mujeres,
                    SUM(total_hombres) as hombres
                FROM empadronados 
                $whereClause
            ");
            $stmt->execute($params);
            $datos = $stmt->fetch();
            
            echo json_encode([
                'labels' => ['Mujeres', 'Hombres'],
                'values' => [
                    (int)$datos['mujeres'],
                    (int)$datos['hombres']
                ]
            ]);
            break;
            
        case 'edades':
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
                'labels' => ['18-25', '26-30', '31-35', '36-40', '41-45', '46-50', '51-55', '56-60', '61-65', '66-70', '70+'],
                'values' => [
                    (int)$datos['edad_18a25'],
                    (int)$datos['edad_26a30'],
                    (int)$datos['edad_31a35'],
                    (int)$datos['edad_36a40'],
                    (int)$datos['edad_41a45'],
                    (int)$datos['edad_46a50'],
                    (int)$datos['edad_51a55'],
                    (int)$datos['edad_56a60'],
                    (int)$datos['edad_61a65'],
                    (int)$datos['edad_66a70'],
                    (int)$datos['edad_mayoroigual70']
                ]
            ]);
            break;
            
        case 'alfabetismo':
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
                'labels' => ['Mujeres', 'Hombres'],
                'datasets' => [
                    [
                        'label' => 'Alfabetas',
                        'data' => [(int)$datos['mujeres_alfabetas'], (int)$datos['hombres_alfabetas']]
                    ],
                    [
                        'label' => 'Analfabetas',
                        'data' => [(int)$datos['mujeres_analfabetas'], (int)$datos['hombres_analfabetas']]
                    ]
                ]
            ]);
            break;
            
        default:
            echo json_encode([
                'error' => 'Tipo de gráfica no válido'
            ]);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Error en gráficas AJAX: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al obtener datos para la gráfica',
        'detalle' => $e->getMessage()
    ]);
}
?>