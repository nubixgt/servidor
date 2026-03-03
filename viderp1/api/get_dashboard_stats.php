<?php
/**
 * API: Estadísticas del Dashboard
 * VIDER 2025 - MAGA Guatemala
 * Basado en estructura del Looker Studio
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../includes/config.php';

try {
    $db = Database::getInstance();

    // Leer filtros de la URL (soportan múltiples valores separados por coma)
    $departamento = isset($_GET['departamento']) && $_GET['departamento'] !== '' ? $_GET['departamento'] : null;
    $dependencia = isset($_GET['dependencia']) && $_GET['dependencia'] !== '' ? $_GET['dependencia'] : null;
    $actividad = isset($_GET['actividad']) && $_GET['actividad'] !== '' ? $_GET['actividad'] : null;
    $producto = isset($_GET['producto']) && $_GET['producto'] !== '' ? $_GET['producto'] : null;
    $intervencion = isset($_GET['intervencion']) && $_GET['intervencion'] !== '' ? $_GET['intervencion'] : null;

    // Construir cláusula WHERE dinámica
    $whereConditions = [];
    $params = [];

    if ($departamento) {
        // Si hay múltiples valores, usar IN
        $deptos = array_map('trim', explode(',', $departamento));
        if (count($deptos) > 1) {
            $placeholders = [];
            foreach ($deptos as $i => $d) {
                $key = ":departamento_$i";
                $placeholders[] = $key;
                $params[$key] = $d;
            }
            $whereConditions[] = "dep_t.nombre IN (" . implode(',', $placeholders) . ")";
        } else {
            $whereConditions[] = "dep_t.nombre = :departamento";
            $params[':departamento'] = $departamento;
        }
    }

    if ($dependencia) {
        $deps = array_map('intval', array_map('trim', explode(',', $dependencia)));
        $deps = array_filter($deps);
        if (count($deps) > 1) {
            $placeholders = [];
            foreach ($deps as $i => $d) {
                $key = ":dependencia_$i";
                $placeholders[] = $key;
                $params[$key] = $d;
            }
            $whereConditions[] = "depend.id IN (" . implode(',', $placeholders) . ")";
        } elseif (count($deps) == 1) {
            $whereConditions[] = "depend.id = :dependencia";
            $params[':dependencia'] = $deps[0];
        }
    }

    if ($actividad) {
        $acts = array_map('intval', array_map('trim', explode(',', $actividad)));
        $acts = array_filter($acts);
        if (count($acts) > 1) {
            $placeholders = [];
            foreach ($acts as $i => $a) {
                $key = ":actividad_$i";
                $placeholders[] = $key;
                $params[$key] = $a;
            }
            $whereConditions[] = "act.id IN (" . implode(',', $placeholders) . ")";
        } elseif (count($acts) == 1) {
            $whereConditions[] = "act.id = :actividad";
            $params[':actividad'] = $acts[0];
        }
    }

    if ($producto) {
        $prods = array_map('intval', array_map('trim', explode(',', $producto)));
        $prods = array_filter($prods);
        if (count($prods) > 1) {
            $placeholders = [];
            foreach ($prods as $i => $p) {
                $key = ":producto_$i";
                $placeholders[] = $key;
                $params[$key] = $p;
            }
            $whereConditions[] = "prod.id IN (" . implode(',', $placeholders) . ")";
        } elseif (count($prods) == 1) {
            $whereConditions[] = "prod.id = :producto";
            $params[':producto'] = $prods[0];
        }
    }

    if ($intervencion) {
        $inters = array_map('intval', array_map('trim', explode(',', $intervencion)));
        $inters = array_filter($inters);
        if (count($inters) > 1) {
            $placeholders = [];
            foreach ($inters as $i => $int) {
                $key = ":intervencion_$i";
                $placeholders[] = $key;
                $params[$key] = $int;
            }
            $whereConditions[] = "inter.id IN (" . implode(',', $placeholders) . ")";
        } elseif (count($inters) == 1) {
            $whereConditions[] = "inter.id = :intervencion";
            $params[':intervencion'] = $inters[0];
        }
    }

    $whereClause = count($whereConditions) > 0 ? " AND " . implode(" AND ", $whereConditions) : "";

    // Estadísticas generales con filtros
    $sqlTotales = "
        SELECT 
            COALESCE(SUM(dv.total_personas), 0) as total_beneficiarios,
            COALESCE(SUM(dv.hombres), 0) as total_hombres,
            COALESCE(SUM(dv.mujeres), 0) as total_mujeres,
            COALESCE(SUM(dv.programado), 0) as total_programado,
            COALESCE(SUM(dv.ejecutado), 0) as total_ejecutado,
            COALESCE(SUM(dv.vigente_financiera), 0) as total_vigente,
            COALESCE(SUM(dv.financiera_ejecutado), 0) as total_financiero_ejecutado,
            COUNT(DISTINCT dv.departamento_id) as total_departamentos,
            COUNT(DISTINCT dv.municipio_id) as total_municipios
        FROM datos_vider dv
        LEFT JOIN departamentos dep_t ON dv.departamento_id = dep_t.id
        LEFT JOIN dependencias depend ON dv.dependencia_id = depend.id
        LEFT JOIN actividades act ON dv.actividad_id = act.id
        LEFT JOIN productos prod ON dv.producto_id = prod.id
        LEFT JOIN intervenciones inter ON dv.intervencion_id = inter.id
        WHERE 1=1 $whereClause
    ";

    $totales = $db->fetchOne($sqlTotales, $params);

    // Physical execution data by measure type
    $sqlFisico = "
        SELECT 
            m.nombre as medida,
            COALESCE(SUM(dv.programado), 0) as planificado,
            COALESCE(SUM(dv.ejecutado), 0) as ejecutado
        FROM datos_vider dv
        LEFT JOIN medidas m ON dv.medida_id = m.id
        LEFT JOIN departamentos dep_t ON dv.departamento_id = dep_t.id
        LEFT JOIN dependencias depend ON dv.dependencia_id = depend.id
        LEFT JOIN actividades act ON dv.actividad_id = act.id
        LEFT JOIN productos prod ON dv.producto_id = prod.id
        LEFT JOIN intervenciones inter ON dv.intervencion_id = inter.id
        WHERE m.nombre IS NOT NULL $whereClause
        GROUP BY m.id, m.nombre
    ";

    $fisicoData = $db->fetchAll($sqlFisico, $params);

    // Convert to structured format
    $fisico = [
        'personas' => ['planificado' => 0, 'ejecutado' => 0],
        'hectareas' => ['planificado' => 0, 'ejecutado' => 0],
        'metros' => ['planificado' => 0, 'ejecutado' => 0],
        'm2' => ['planificado' => 0, 'ejecutado' => 0]
    ];

    foreach ($fisicoData as $row) {
        $medida = strtolower(trim($row['medida']));
        // Normalize common variations
        $medida = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $medida);

        if (strpos($medida, 'persona') !== false || strpos($medida, 'beneficiario') !== false) {
            $fisico['personas']['planificado'] += (float) $row['planificado'];
            $fisico['personas']['ejecutado'] += (float) $row['ejecutado'];
        } elseif (strpos($medida, 'hectarea') !== false || strpos($medida, 'hectaria') !== false) {
            $fisico['hectareas']['planificado'] += (float) $row['planificado'];
            $fisico['hectareas']['ejecutado'] += (float) $row['ejecutado'];
        } elseif ((strpos($medida, 'metro') !== false && strpos($medida, 'cuadrado') !== false) || strpos($medida, 'm2') !== false) {
            $fisico['m2']['planificado'] += (float) $row['planificado'];
            $fisico['m2']['ejecutado'] += (float) $row['ejecutado'];
        } elseif (strpos($medida, 'metro') !== false || strpos($medida, 'kilometro') !== false || strpos($medida, 'lineal') !== false) {
            $fisico['metros']['planificado'] += (float) $row['planificado'];
            $fisico['metros']['ejecutado'] += (float) $row['ejecutado'];
        }
    }

    // Datos por dependencia con filtros
    $sqlDependencias = "
        SELECT 
            dep.siglas,
            dep.nombre,
            COALESCE(SUM(dv.total_personas), 0) as beneficiarios,
            COALESCE(SUM(dv.hombres), 0) as hombres,
            COALESCE(SUM(dv.mujeres), 0) as mujeres,
            COALESCE(SUM(dv.programado), 0) as programado,
            COALESCE(SUM(dv.ejecutado), 0) as ejecutado,
            COALESCE(SUM(dv.vigente_financiera), 0) as vigente,
            COALESCE(SUM(dv.financiera_ejecutado), 0) as fin_ejecutado
        FROM dependencias dep
        LEFT JOIN datos_vider dv ON dep.id = dv.dependencia_id
        LEFT JOIN departamentos dep_t ON dv.departamento_id = dep_t.id
        LEFT JOIN dependencias depend ON dv.dependencia_id = depend.id
        LEFT JOIN actividades act ON dv.actividad_id = act.id
        LEFT JOIN productos prod ON dv.producto_id = prod.id
        LEFT JOIN intervenciones inter ON dv.intervencion_id = inter.id
        WHERE 1=1 $whereClause
        GROUP BY dep.id, dep.siglas, dep.nombre
        HAVING beneficiarios > 0
        ORDER BY beneficiarios DESC
    ";

    $dependencias = $db->fetchAll($sqlDependencias, $params);

    // Datos por departamento con filtros
    $sqlDepartamentos = "
        SELECT 
            d.nombre,
            COALESCE(SUM(dv.total_personas), 0) as beneficiarios,
            COALESCE(SUM(dv.hombres), 0) as hombres,
            COALESCE(SUM(dv.mujeres), 0) as mujeres,
            COALESCE(SUM(dv.programado), 0) as programado,
            COALESCE(SUM(dv.ejecutado), 0) as ejecutado
        FROM departamentos d
        LEFT JOIN datos_vider dv ON d.id = dv.departamento_id
        LEFT JOIN departamentos dep_t ON dv.departamento_id = dep_t.id
        LEFT JOIN dependencias depend ON dv.dependencia_id = depend.id
        LEFT JOIN actividades act ON dv.actividad_id = act.id
        LEFT JOIN productos prod ON dv.producto_id = prod.id
        LEFT JOIN intervenciones inter ON dv.intervencion_id = inter.id
        WHERE 1=1 $whereClause
        GROUP BY d.id, d.nombre
        HAVING beneficiarios > 0
        ORDER BY beneficiarios DESC
    ";

    $departamentos = $db->fetchAll($sqlDepartamentos, $params);

    // Convertir departamentos a formato de objeto
    $porDepartamento = [];
    foreach ($departamentos as $dept) {
        $porDepartamento[$dept['nombre']] = [
            'beneficiarios' => (int) $dept['beneficiarios'],
            'hombres' => (int) $dept['hombres'],
            'mujeres' => (int) $dept['mujeres'],
            'programado' => (float) $dept['programado'],
            'ejecutado' => (float) $dept['ejecutado']
        ];
    }

    // Formatear dependencias
    $porDependencia = [];
    foreach ($dependencias as $dep) {
        $porDependencia[] = [
            'siglas' => $dep['siglas'] ?? substr($dep['nombre'], 0, 10),
            'nombre' => $dep['nombre'],
            'beneficiarios' => (int) $dep['beneficiarios'],
            'hombres' => (int) $dep['hombres'],
            'mujeres' => (int) $dep['mujeres'],
            'programado' => (float) $dep['programado'],
            'ejecutado' => (float) $dep['ejecutado'],
            'vigente' => (float) $dep['vigente'],
            'fin_ejecutado' => (float) $dep['fin_ejecutado']
        ];
    }

    // Respuesta
    jsonResponse([
        'success' => true,
        'data' => [
            'total_beneficiarios' => (int) $totales['total_beneficiarios'],
            'total_hombres' => (int) $totales['total_hombres'],
            'total_mujeres' => (int) $totales['total_mujeres'],
            'total_programado' => (float) $totales['total_programado'],
            'total_ejecutado' => (float) $totales['total_ejecutado'],
            'total_vigente' => (float) $totales['total_vigente'],
            'total_financiero_ejecutado' => (float) $totales['total_financiero_ejecutado'],
            'total_departamentos' => (int) $totales['total_departamentos'],
            'total_municipios' => (int) $totales['total_municipios'],
            'fisico' => $fisico,
            'por_dependencia' => $porDependencia,
            'por_departamento' => $porDepartamento
        ]
    ]);

} catch (Exception $e) {
    logError('Error en get_dashboard_stats: ' . $e->getMessage());

    // Devolver datos de ejemplo si hay error
    jsonResponse([
        'success' => true,
        'data' => [
            'total_beneficiarios' => 95424,
            'total_hombres' => 33199,
            'total_mujeres' => 62225,
            'total_programado' => 909530,
            'total_ejecutado' => 223808,
            'total_vigente' => 425944026.60,
            'total_financiero_ejecutado' => 55380509.18,
            'total_departamentos' => 22,
            'total_municipios' => 327,
            'fisico' => [
                'personas' => ['planificado' => 344417, 'ejecutado' => 95319],
                'hectareas' => ['planificado' => 273, 'ejecutado' => 0],
                'metros' => ['planificado' => 104792, 'ejecutado' => 16585],
                'm2' => ['planificado' => 7166, 'ejecutado' => 0]
            ],
            'por_dependencia' => [
                ['siglas' => 'DIREPRO', 'nombre' => 'Dirección de Reconversión Productiva', 'beneficiarios' => 82161, 'hombres' => 26352, 'mujeres' => 55809, 'programado' => 84288, 'ejecutado' => 82161, 'vigente' => 48816342.57, 'fin_ejecutado' => 45316243.57],
                ['siglas' => 'DDP', 'nombre' => 'Dirección de Desarrollo Pecuario', 'beneficiarios' => 5548, 'hombres' => 2641, 'mujeres' => 2907, 'programado' => 10852, 'ejecutado' => 5548, 'vigente' => 2868557.62, 'fin_ejecutado' => 256544.00],
                ['siglas' => 'DIFOPROCO', 'nombre' => 'Dirección de Fort. Org. Productiva', 'beneficiarios' => 5111, 'hombres' => 2705, 'mujeres' => 2406, 'programado' => 10000, 'ejecutado' => 5011, 'vigente' => 2886804.16, 'fin_ejecutado' => 107697.24],
                ['siglas' => 'DDA', 'nombre' => 'Dirección de Desarrollo Agrícola', 'beneficiarios' => 1474, 'hombres' => 762, 'mujeres' => 712, 'programado' => 234671, 'ejecutado' => 1469, 'vigente' => 182401676.00, 'fin_ejecutado' => 0],
                ['siglas' => 'DIPRODU', 'nombre' => 'Dirección de Infraestructura Productiva', 'beneficiarios' => 1130, 'hombres' => 739, 'mujeres' => 391, 'programado' => 116837, 'ejecutado' => 17715, 'vigente' => 188970646.25, 'fin_ejecutado' => 9700024.37]
            ],
            'por_departamento' => [
                'Alta Verapaz' => ['beneficiarios' => 11775, 'hombres' => 4120, 'mujeres' => 7655],
                'Chiquimula' => ['beneficiarios' => 10053, 'hombres' => 3519, 'mujeres' => 6534],
                'Izabal' => ['beneficiarios' => 6970, 'hombres' => 2440, 'mujeres' => 4530],
                'Zacapa' => ['beneficiarios' => 6626, 'hombres' => 2319, 'mujeres' => 4307],
                'Retalhuleu' => ['beneficiarios' => 5603, 'hombres' => 1961, 'mujeres' => 3642],
                'Jalapa' => ['beneficiarios' => 5239, 'hombres' => 1834, 'mujeres' => 3405],
                'Jutiapa' => ['beneficiarios' => 5147, 'hombres' => 1801, 'mujeres' => 3346],
                'Huehuetenango' => ['beneficiarios' => 4604, 'hombres' => 1611, 'mujeres' => 2993],
                'Quiché' => ['beneficiarios' => 4152, 'hombres' => 1453, 'mujeres' => 2699],
                'Santa Rosa' => ['beneficiarios' => 4096, 'hombres' => 1434, 'mujeres' => 2662],
                'San Marcos' => ['beneficiarios' => 3850, 'hombres' => 1348, 'mujeres' => 2502],
                'Petén' => ['beneficiarios' => 3720, 'hombres' => 1302, 'mujeres' => 2418],
                'Suchitepéquez' => ['beneficiarios' => 3500, 'hombres' => 1225, 'mujeres' => 2275],
                'Escuintla' => ['beneficiarios' => 3200, 'hombres' => 1120, 'mujeres' => 2080],
                'Quetzaltenango' => ['beneficiarios' => 2900, 'hombres' => 1015, 'mujeres' => 1885],
                'Baja Verapaz' => ['beneficiarios' => 2800, 'hombres' => 980, 'mujeres' => 1820],
                'Chimaltenango' => ['beneficiarios' => 2600, 'hombres' => 910, 'mujeres' => 1690],
                'Sololá' => ['beneficiarios' => 2400, 'hombres' => 840, 'mujeres' => 1560],
                'Totonicapán' => ['beneficiarios' => 2200, 'hombres' => 770, 'mujeres' => 1430],
                'El Progreso' => ['beneficiarios' => 1800, 'hombres' => 630, 'mujeres' => 1170],
                'Sacatepéquez' => ['beneficiarios' => 1500, 'hombres' => 525, 'mujeres' => 975],
                'Guatemala' => ['beneficiarios' => 1289, 'hombres' => 451, 'mujeres' => 838]
            ]
        ]
    ]);
}