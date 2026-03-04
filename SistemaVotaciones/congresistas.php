
<?php
require_once 'config.php';
require_once 'auth.php';

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_foto']) && isset($_POST['congresista_id']) && esAdmin()) {
    $id = (int)$_POST['congresista_id'];
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['foto']['tmp_name'];
        $fileName = $_FILES['foto']['name'];
        $fileSize = $_FILES['foto']['size'];
        $fileType = $_FILES['foto']['type'];
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($fileType, $allowedTypes) && $fileSize <= 2000000) { // 2MB max
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = 'congresista_' . $id . '_' . time() . '.' . $extension;
            $uploadDir = 'uploads/congresistas/';
            $destPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $pdo = getDB();
                // Get old photo to delete it
                $stmt = $pdo->prepare('SELECT foto FROM congresistas WHERE id = ?');
                $stmt->execute([$id]);
                $oldPhoto = $stmt->fetchColumn();
                if ($oldPhoto && file_exists($uploadDir . $oldPhoto)) {
                    unlink($uploadDir . $oldPhoto);
                }
                
                // Update DB
                $stmt = $pdo->prepare('UPDATE congresistas SET foto = ? WHERE id = ?');
                if ($stmt->execute([$newFileName, $id])) {
                    $response['success'] = true;
                    $response['message'] = 'Foto subida correctamente.';
                } else {
                    $response['message'] = 'Error al actualizar la base de datos.';
                }
            } else {
                $response['message'] = 'Error al guardar el archivo.';
            }
        } else {
            $response['message'] = 'Archivo no válido o demasiado grande. Usa JPG, PNG o GIF (Max 2MB).';
        }
    } else {
        $response['message'] = 'Error en la subida del archivo. Detalle: ' . $_FILES['foto']['error'];
    }
    
    // Redirect back to avoid resubmitting form
    header('Location: congresistas.php?msg=' . urlencode($response['message']) . '&type=' . ($response['success'] ? 'success' : 'danger'));
    exit;
}

$db = getDB();

// Filtros
$busqueda = isset($_GET['busqueda']) ? sanitizar($_GET['busqueda']) : '';
$minAusencias = isset($_GET['min_ausencias']) ? (int)$_GET['min_ausencias'] : 0;
$bloqueId = isset($_GET['bloque']) ? (int)$_GET['bloque'] : 0;
$minVotaciones = isset($_GET['min_votaciones']) ? (int)$_GET['min_votaciones'] : 0;
$ordenar = isset($_GET['ordenar']) ? $_GET['ordenar'] : 'nombre';

// NUEVO: Filtros de fecha
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$usarFiltroFechas = !empty($fecha_inicio) && !empty($fecha_fin);

// Paginación
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$porPagina = 25;
$offset = ($pagina - 1) * $porPagina;

// Obtener lista de bloques para el filtro
$stmtBloques = $db->query("SELECT id, nombre FROM bloques WHERE activo = 1 ORDER BY nombre");
$bloques = $stmtBloques->fetchAll();

// Ordenamiento
$orderBy = match($ordenar) {
    'ausencias_desc' => 'vc.porcentaje_ausencias DESC',
    'ausencias_asc' => 'vc.porcentaje_ausencias ASC',
    'votaciones_desc' => 'vc.total_votaciones DESC',
    'favor_desc' => 'vc.votos_favor DESC',
    'contra_desc' => 'vc.votos_contra DESC',
    default => 'c.nombre ASC'
};

if ($usarFiltroFechas) {
    // CON FILTRO DE FECHAS: Calcular estadísticas en tiempo real
    $where = ['c.activo = 1'];
    $params = [];
    
    if ($busqueda) {
        $where[] = "(c.nombre LIKE ? OR c.nombre_normalizado LIKE ?)";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
    }
    
    if ($bloqueId > 0) {
        $where[] = "EXISTS (SELECT 1 FROM votos v WHERE v.congresista_id = c.id AND v.bloque_id = ?)";
        $params[] = $bloqueId;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Consulta principal con cálculo de estadísticas para el rango de fechas
    $queryBase = "
        SELECT 
            c.*,
            COUNT(v.id) as total_votaciones,
            COUNT(CASE WHEN v.voto = 'FAVOR' THEN 1 END) as votos_favor,
            COUNT(CASE WHEN v.voto = 'CONTRA' THEN 1 END) as votos_contra,
            COUNT(CASE WHEN v.voto = 'AUSENTE' THEN 1 END) as ausencias,
            COUNT(CASE WHEN v.voto = 'LICENCIA' THEN 1 END) as licencias,
            ROUND((COUNT(CASE WHEN v.voto = 'FAVOR' THEN 1 END) * 100.0 / NULLIF(COUNT(v.id), 0)), 2) as porcentaje_favor,
            ROUND((COUNT(CASE WHEN v.voto = 'AUSENTE' THEN 1 END) * 100.0 / NULLIF(COUNT(v.id), 0)), 2) as porcentaje_ausencias
        FROM congresistas c
        LEFT JOIN votos v ON c.id = v.congresista_id
        LEFT JOIN eventos_votacion e ON v.evento_id = e.id AND DATE(e.fecha_hora) BETWEEN ? AND ?
        WHERE $whereClause
        GROUP BY c.id
    ";
    
    $params[] = $fecha_inicio;
    $params[] = $fecha_fin;
    
    // Aplicar filtros HAVING
    $having = [];
    if ($minAusencias > 0) {
        $having[] = "porcentaje_ausencias >= ?";
        $params[] = $minAusencias;
    }
    if ($minVotaciones > 0) {
        $having[] = "total_votaciones >= ?";
        $params[] = $minVotaciones;
    }
    
    if (!empty($having)) {
        $queryBase .= " HAVING " . implode(' AND ', $having);
    }
    
    // Contar total de registros
    $stmtCount = $db->prepare($queryBase);
    $stmtCount->execute($params);
    $totalRegistros = count($stmtCount->fetchAll());
    $totalPaginas = ceil($totalRegistros / $porPagina);
    
    // Obtener congresistas con paginación
    $query = $queryBase . " ORDER BY $orderBy LIMIT ? OFFSET ?";
    $params[] = $porPagina;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $congresistas = $stmt->fetchAll();
    
    // Estadí­sticas generales - Respetando filtros aplicados (con fechas)
    $whereStats = ['c.activo = 1'];
    $paramsStats = [];
    
    if ($busqueda) {
        $whereStats[] = "(c.nombre LIKE ? OR c.nombre_normalizado LIKE ?)";
        $paramsStats[] = "%$busqueda%";
        $paramsStats[] = "%$busqueda%";
    }
    
    if ($bloqueId > 0) {
        $whereStats[] = "EXISTS (SELECT 1 FROM votos v WHERE v.congresista_id = c.id AND v.bloque_id = ?)";
        $paramsStats[] = $bloqueId;
    }
    
    $whereStatsClause = implode(' AND ', $whereStats);
    
    $statsQuery = "
        SELECT 
            COUNT(DISTINCT c.id) as total,
            AVG(porcentaje_ausencias) as promedio_ausencias,
            SUM(total_votaciones) as total_votaciones
        FROM (
            SELECT 
                c.id,
                ROUND((COUNT(CASE WHEN v.voto = 'AUSENTE' THEN 1 END) * 100.0 / NULLIF(COUNT(v.id), 0)), 2) as porcentaje_ausencias,
                COUNT(v.id) as total_votaciones
            FROM congresistas c
            LEFT JOIN votos v ON c.id = v.congresista_id
            LEFT JOIN eventos_votacion e ON v.evento_id = e.id AND DATE(e.fecha_hora) BETWEEN ? AND ?
            WHERE $whereStatsClause
            GROUP BY c.id
        ) as stats
    ";
    
    $paramsStats[] = $fecha_inicio;
    $paramsStats[] = $fecha_fin;
    
    $stmtStats = $db->prepare($statsQuery);
    $stmtStats->execute($paramsStats);
    $stats = $stmtStats->fetch();
    
} else {
    // SIN FILTRO DE FECHAS: Usar vista original
    // Construir WHERE clause con placeholders posicionales
    $where = ['1=1'];
    $params = [];
    
    if ($busqueda) {
        $where[] = "(c.nombre LIKE ? OR c.nombre_normalizado LIKE ?)";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
    }
    
    if ($minAusencias > 0) {
        $where[] = "COALESCE(vc.porcentaje_ausencias, 0) >= ?";
        $params[] = $minAusencias;
    }
    
    if ($bloqueId > 0) {
        $where[] = "EXISTS (SELECT 1 FROM votos v WHERE v.congresista_id = c.id AND v.bloque_id = ?)";
        $params[] = $bloqueId;
    }
    
    if ($minVotaciones > 0) {
        $where[] = "COALESCE(vc.total_votaciones, 0) >= ?";
        $params[] = $minVotaciones;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Total de registros
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM congresistas c
        LEFT JOIN vista_estadisticas_congresista vc ON c.id = vc.id
        WHERE $whereClause
    ");
    $stmt->execute($params);
    $totalRegistros = $stmt->fetch()['total'];
    $totalPaginas = ceil($totalRegistros / $porPagina);
    
    // Obtener congresistas
    $query = "
        SELECT 
            c.*,
            COALESCE(vc.total_votaciones, 0) as total_votaciones,
            COALESCE(vc.votos_favor, 0) as votos_favor,
            COALESCE(vc.votos_contra, 0) as votos_contra,
            COALESCE(vc.ausencias, 0) as ausencias,
            COALESCE(vc.licencias, 0) as licencias,
            COALESCE(vc.porcentaje_ausencias, 0) as porcentaje_ausencias
        FROM congresistas c
        LEFT JOIN vista_estadisticas_congresista vc ON c.id = vc.id
        WHERE $whereClause
        ORDER BY $orderBy
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $db->prepare($query);
    // Agregar parámetros de paginación
    $paramsConPaginacion = array_merge($params, [$porPagina, $offset]);
    $stmt->execute($paramsConPaginacion);
    $congresistas = $stmt->fetchAll();
    
    // Estadí­sticas generales - Respetando filtros aplicados
    $statsQuery = "
        SELECT 
            COUNT(*) as total,
            ROUND(AVG(COALESCE(vc.porcentaje_ausencias, 0)), 2) as promedio_ausencias,
            SUM(COALESCE(vc.total_votaciones, 0)) as total_votaciones
        FROM congresistas c
        LEFT JOIN vista_estadisticas_congresista vc ON c.id = vc.id
        WHERE $whereClause
    ";
    $stmtStats = $db->prepare($statsQuery);
    $stmtStats->execute($params);
    $stats = $stmtStats->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Congresistas - Sistema de Votaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pageTransitionIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        body {
            animation: pageTransitionIn 0.5s ease-in-out;
        }
        .main-content {
            animation: fadeIn 0.6s ease-in-out;
        }
        .card, .stat-card {
            animation: fadeIn 0.6s ease-in-out;
            animation-fill-mode: both;
        }
        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1); }
            /* Animación del Logo */
        .logo img {
            animation: logoFloat 3s ease-in-out infinite;
            transition: transform 0.3s ease;
        }

        .logo img:hover {
            animation: logoSpin 1s ease-in-out;
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-8px) scale(1.05); }
        }

        @keyframes logoSpin {
            0% { transform: rotateZ(0deg); }
            100% { transform: rotateZ(360deg); }
        }
</style>
</head>
<body>
    <!-- Botón Meníº Móvil -->
    <button class="mobile-menu-btn d-lg-none" id="mobileMenuBtn" aria-label="Abrir meníº">
        <i class="bi bi-list"></i>
    </button>

    <!-- Overlay para cerrar meníº -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include "sidebar.php"; ?>
            
            <!-- Contenido Principal -->
            <div class="col-lg-10 main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">
                                <i class="bi bi-people text-primary me-2"></i>
                                Congresistas
                            </h2>
                            <p class="text-muted mb-0">
                                Gestión y estadísticas de congresistas
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Estadí­sticas Generales -->
                <div class="row g-3">
                    <?php if ($busqueda || $bloqueId || $minAusencias || $minVotaciones || $usarFiltroFechas): ?>
                    <div class="col-12">
                        <div class="alert alert-info d-flex align-items-center mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            <div>
                                <strong>Filtros Activos:</strong>
                                <?php 
                                $filtrosActivos = [];
                                if ($busqueda) $filtrosActivos[] = "Bíºsqueda: '$busqueda'";
                                if ($bloqueId) {
                                    $bloqueNombre = '';
                                    foreach ($bloques as $b) {
                                        if ($b['id'] == $bloqueId) {
                                            $bloqueNombre = $b['nombre'];
                                            break;
                                        }
                                    }
                                    $filtrosActivos[] = "Bloque: $bloqueNombre";
                                }
                                if ($minAusencias) $filtrosActivos[] = "Min. Ausencias: $minAusencias%";
                                if ($minVotaciones) $filtrosActivos[] = "Min. Votaciones: $minVotaciones";
                                if ($usarFiltroFechas) $filtrosActivos[] = "Perí­odo: $fecha_inicio a $fecha_fin";
                                echo implode(' | ', $filtrosActivos);
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-primary bg-opacity-10">
                                        <i class="bi bi-people text-primary"></i>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <p class="text-muted mb-1 small">Total Congresistas</p>
                                        <h3 class="stat-number mb-0"><?php echo $stats['total'] ?? 0; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-warning bg-opacity-10">
                                        <i class="bi bi-percent text-warning"></i>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <p class="text-muted mb-1 small">Promedio Ausencias</p>
                                        <h3 class="stat-number mb-0">
                                            <?php echo number_format($stats['promedio_ausencias'] ?? 0, 1); ?>%
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-success bg-opacity-10">
                                        <i class="bi bi-check-square text-success"></i>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <p class="text-muted mb-1 small">Total Votaciones</p>
                                        <h3 class="stat-number mb-0">
                                            <?php echo number_format($stats['total_votaciones'] ?? 0); ?>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="filters-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="bi bi-funnel me-2"></i>Filtros de Bíºsqueda
                        </h5>
                    </div>
                    
                    <form method="GET" action="congresistas.php">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-search me-2"></i>Buscar por Nombre
                                </label>
                                <input type="text" name="busqueda" class="form-control" 
                                       placeholder="Nombre del congresista..." value="<?php echo htmlspecialchars($busqueda); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-diagram-3 me-2"></i>Bloque
                                </label>
                                <select name="bloque" class="form-select">
                                    <option value="0">Todos los bloques</option>
                                    <?php foreach ($bloques as $bloque): ?>
                                        <option value="<?php echo $bloque['id']; ?>" 
                                                <?php echo $bloqueId == $bloque['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($bloque['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-exclamation-triangle me-2"></i>Min. Ausencias (%)
                                </label>
                                <input type="number" name="min_ausencias" class="form-control" 
                                       placeholder="0" min="0" max="100" value="<?php echo $minAusencias; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-check-square me-2"></i>Min. Votaciones
                                </label>
                                <input type="number" name="min_votaciones" class="form-control" 
                                       placeholder="0" min="0" value="<?php echo $minVotaciones; ?>">
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-calendar-range me-2"></i>Fecha Inicio
                                </label>
                                <input type="date" name="fecha_inicio" class="form-control" 
                                       value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-calendar-range me-2"></i>Fecha Fin
                                </label>
                                <input type="date" name="fecha_fin" class="form-control" 
                                       value="<?php echo htmlspecialchars($fecha_fin); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-sort-down me-2"></i>Ordenar por
                                </label>
                                <select name="ordenar" class="form-select">
                                    <option value="nombre" <?php echo $ordenar === 'nombre' ? 'selected' : ''; ?>>Nombre A-Z</option>
                                    <option value="ausencias_desc" <?php echo $ordenar === 'ausencias_desc' ? 'selected' : ''; ?>>Mayor ausencias</option>
                                    <option value="ausencias_asc" <?php echo $ordenar === 'ausencias_asc' ? 'selected' : ''; ?>>Menor ausencias</option>
                                    <option value="votaciones_desc" <?php echo $ordenar === 'votaciones_desc' ? 'selected' : ''; ?>>Más votaciones</option>
                                    <option value="favor_desc" <?php echo $ordenar === 'favor_desc' ? 'selected' : ''; ?>>Más votos a favor</option>
                                    <option value="contra_desc" <?php echo $ordenar === 'contra_desc' ? 'selected' : ''; ?>>Más votos contra</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search me-2"></i>Buscar
                                </button>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <a href="congresistas.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-x-circle me-2"></i>Limpiar Filtros
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Tabla de Congresistas -->
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th class="text-center">Votaciones</th>
                                    <th class="text-center">A Favor</th>
                                    <th class="text-center">En Contra</th>
                                    <th class="text-center">Ausencias</th>
                                    <th class="text-center">% Ausencias</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                    <?php if (empty($congresistas)): ?>
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <i class="bi bi-person-x"></i>
                                                <h5 class="mt-3 mb-2">No se encontraron congresistas</h5>
                                                <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($congresistas as $c): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($c['foto']) && file_exists('uploads/congresistas/' . $c['foto'])): ?>
                                                    <a href="#" data-bs-toggle="modal" data-bs-target="#viewPhotoModal" class="text-decoration-none view-photo-btn" data-foto="uploads/congresistas/<?php echo htmlspecialchars($c['foto']); ?>" data-nombre="<?php echo htmlspecialchars($c['nombre']); ?>">
                                                        <img src="uploads/congresistas/<?php echo htmlspecialchars($c['foto']); ?>" alt="Foto" class="rounded-circle me-3 border-primary shadow-sm" style="width: 40px; height: 40px; object-fit: cover; border: 2px solid var(--bs-primary); cursor: zoom-in; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">
                                                    </a>
                                                <?php else: ?>
                                                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; min-width: 40px;">
                                                        <i class="bi bi-person text-primary fs-5"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($c['nombre']); ?></strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <strong><?php echo $c['total_votaciones'] ?? 0; ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><?php echo $c['votos_favor'] ?? 0; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger"><?php echo $c['votos_contra'] ?? 0; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning text-dark"><?php echo $c['ausencias'] ?? 0; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                            $pctAusencias = $c['porcentaje_ausencias'] ?? 0;
                                            $colorClase = $pctAusencias > 30 ? 'danger' : ($pctAusencias > 15 ? 'warning' : 'success');
                                            ?>
                                            <span class="badge bg-<?php echo $colorClase; ?>">
                                                <?php echo number_format($pctAusencias, 1); ?>%
                                            </span>
                                        </td>
                                        <td class="text-center">
                                                                                        <a href="estadisticas.php?congresista=<?php echo $c['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" data-tooltip="Ver estadísticas">
                                                <i class="bi bi-bar-chart"></i>
                                            </a>
                                            <?php if (esAdmin()): ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary ms-1" 
                                                    data-bs-toggle="modal" data-bs-target="#uploadPhotoModal"
                                                    data-id="<?php echo $c['id']; ?>" data-nombre="<?php echo htmlspecialchars($c['nombre']); ?>"
                                                    title="Subir fotografía">
                                                <i class="bi bi-camera"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    
                    <!-- Paginación -->
                    <?php if ($totalPaginas > 1): ?>
                    <div class="card-footer bg-white">
                        <nav>
                            <ul class="pagination justify-content-center mb-0">
                                <?php if ($pagina > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $pagina - 2); $i <= min($totalPaginas, $pagina + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($pagina < $totalPaginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="responsive.js"></script>
    <script>
        document.querySelectorAll('a:not([target="_blank"]):not([href^="#"])').forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.href.includes('#') || this.classList.contains('no-transition')) return;
                e.preventDefault();
                const href = this.href;
                document.body.style.opacity = '0';
                document.body.style.transition = 'opacity 0.4s ease-in-out';
                setTimeout(() => { window.location.href = href; }, 400);
            });
        });
        window.addEventListener('pageshow', function() { document.body.style.opacity = '1'; });
    </script>
    <!-- Photo Upload Modal -->
    <div class="modal fade" id="uploadPhotoModal" tabindex="-1" aria-labelledby="uploadPhotoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="uploadPhotoModalLabel">
                        <i class="bi bi-camera me-2"></i>Actualizar Fotografía
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="congresistas.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="congresista_id" id="modalCongresistaId" value="">
                        <input type="hidden" name="upload_foto" value="1">
                        
                        <p class="mb-3 text-muted">Seleccione una fotografía para <strong id="modalCongresistaNombre"></strong></p>
                        
                        <div class="mb-3">
                            <label for="fotoInput" class="form-label fw-semibold">Archivo de Imagen (JPG, PNG)</label>
                            <input class="form-control" type="file" id="fotoInput" name="foto" accept=".jpg,.jpeg,.png,.gif" required>
                            <div class="form-text text-muted">Tamaño máximo recomendado: 2MB. Formato 1:1 (cuadrado) sugerido.</div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-cloud-upload me-2"></i>Subir Fotografía
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Photo View Modal -->
    <div class="modal fade" id="viewPhotoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-header border-0 pb-0 justify-content-end">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="filter: drop-shadow(0px 0px 2px rgba(0,0,0,0.8));"></button>
                </div>
                <div class="modal-body text-center pt-0">
                    <img id="viewedPhotoImg" src="" alt="Fotografía" class="img-fluid rounded shadow-lg" style="max-height: 80vh; border: 3px solid white;">
                    <h5 id="viewedPhotoName" class="text-white mt-3" style="text-shadow: 1px 1px 3px rgba(0,0,0,0.8);"></h5>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // View Photo logic
            const viewButtons = document.querySelectorAll('.view-photo-btn');
            const viewedImg = document.getElementById('viewedPhotoImg');
            const viewedName = document.getElementById('viewedPhotoName');
            
            viewButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    viewedImg.src = this.getAttribute('data-foto');
                    viewedName.textContent = this.getAttribute('data-nombre');
                });
            });
            
            // Upload Photo logic            const uploadModal = document.getElementById('uploadPhotoModal');
            if (uploadModal) {
                uploadModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const id = button.getAttribute('data-id');
                    const nombre = button.getAttribute('data-nombre');
                    
                    document.getElementById('modalCongresistaId').value = id;
                    document.getElementById('modalCongresistaNombre').textContent = nombre;
                });
            }
        });
    </script>
</body>
</html>








