<?php
require_once 'config.php';
$db = getDB();

// Parámetros de búsqueda y filtros
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$resultado = isset($_GET['resultado']) ? $_GET['resultado'] : '';
$fechaDesde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fechaHasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
$ordenar = isset($_GET['ordenar']) ? $_GET['ordenar'] : 'fecha_desc';

// Estadísticas generales
$stmt = $db->query("SELECT COUNT(*) as total FROM eventos_votacion");
$totalEventos = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM resumen_eventos WHERE resultado = 'APROBADO'");
$totalAprobados = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM resumen_eventos WHERE resultado = 'RECHAZADO'");
$totalRechazados = $stmt->fetch()['total'];

// Construcción de la consulta con filtros
$where = ['1=1'];
$params = [];

if (!empty($busqueda)) {
    $where[] = "(e.numero_evento LIKE :busqueda1 OR e.titulo LIKE :busqueda2 OR e.sesion_numero LIKE :busqueda3)";
    $params[':busqueda1'] = "%$busqueda%";
    $params[':busqueda2'] = "%$busqueda%";
    $params[':busqueda3'] = "%$busqueda%";
}

if (!empty($resultado)) {
    $where[] = "r.resultado = :resultado";
    $params[':resultado'] = $resultado;
}

if (!empty($fechaDesde)) {
    $where[] = "DATE(e.fecha_hora) >= :fecha_desde";
    $params[':fecha_desde'] = $fechaDesde;
}

if (!empty($fechaHasta)) {
    $where[] = "DATE(e.fecha_hora) <= :fecha_hasta";
    $params[':fecha_hasta'] = $fechaHasta;
}

$whereClause = implode(' AND ', $where);

// Ordenamiento
$orderBy = 'e.fecha_hora DESC';
switch ($ordenar) {
    case 'fecha_asc':
        $orderBy = 'e.fecha_hora ASC';
        break;
    case 'fecha_desc':
        $orderBy = 'e.fecha_hora DESC';
        break;
    case 'numero_asc':
        $orderBy = 'e.numero_evento ASC';
        break;
    case 'numero_desc':
        $orderBy = 'e.numero_evento DESC';
        break;
    case 'favor_desc':
        $orderBy = 'r.votos_favor DESC';
        break;
    case 'contra_desc':
        $orderBy = 'r.votos_contra DESC';
        break;
}

// Paginación
$porPagina = 20;
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina - 1) * $porPagina;

// Obtener total de resultados
$sqlCount = "
    SELECT COUNT(*) as total
    FROM eventos_votacion e
    LEFT JOIN resumen_eventos r ON e.id = r.evento_id
    WHERE $whereClause
";

$stmtCount = $db->prepare($sqlCount);
$stmtCount->execute($params);
$totalResultados = $stmtCount->fetch()['total'];
$totalPaginas = ceil($totalResultados / $porPagina);

// Obtener eventos
$sql = "
    SELECT e.id, e.numero_evento, e.titulo, e.sesion_numero, e.fecha_hora, 
           r.votos_favor, r.votos_contra, r.resultado
    FROM eventos_votacion e
    LEFT JOIN resumen_eventos r ON e.id = r.evento_id
    WHERE $whereClause
    ORDER BY $orderBy
    LIMIT $porPagina OFFSET $offset
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$eventos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Eventos - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
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
        
        /* CORRECCIÓN: Títulos completos sin truncar */
        .evento-titulo {
            line-height: 1.5;
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            max-width: 100%;
            display: block;
            font-size: 0.9rem;
            color: #1e293b;
            padding: 0.25rem 0;
        }
        
        /* Ajuste de columnas para títulos largos */
        .table td.titulo-cell {
            min-width: 300px;
            max-width: 600px;
            width: auto;
        }
        
        @media (max-width: 991px) {
            .table td.titulo-cell {
                min-width: 200px;
                max-width: 100%;
            }
        }
        
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
    <!-- Botón Menú Móvil -->
    <button class="mobile-menu-btn d-lg-none" id="mobileMenuBtn" aria-label="Abrir menú">
        <i class="bi bi-list"></i>
    </button>

    <!-- Overlay para cerrar menú -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include "sidebar.php"; ?>
            
            <!-- Main Content -->
            <div class="col-lg-10 main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <h2 class="mb-1">
                        <i class="bi bi-calendar-event me-2"></i>Eventos de Votación
                    </h2>
                    <p class="text-muted mb-0">
                        Registro completo de eventos y votaciones del Congreso
                    </p>
                </div>

                <!-- Stats Cards -->
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                        <i class="bi bi-calendar-check"></i>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <p class="text-muted mb-1 small">Total Eventos</p>
                                        <h3 class="stat-number mb-0"><?php echo number_format($totalEventos); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
                                        <i class="bi bi-check-circle"></i>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <p class="text-muted mb-1 small">Aprobados</p>
                                        <h3 class="stat-number mb-0"><?php echo number_format($totalAprobados); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%); color: white;">
                                        <i class="bi bi-x-circle"></i>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <p class="text-muted mb-1 small">Rechazados</p>
                                        <h3 class="stat-number mb-0"><?php echo number_format($totalRechazados); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros y Búsqueda -->
                <div class="filters-section">
                    <form method="GET" action="">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">
                                    <i class="bi bi-search me-1"></i>Buscar
                                </label>
                                <input type="text" 
                                       name="busqueda" 
                                       class="form-control" 
                                       placeholder="Número, título o sesión..."
                                       value="<?php echo htmlspecialchars($busqueda); ?>">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label small text-muted mb-1">
                                    <i class="bi bi-filter me-1"></i>Resultado
                                </label>
                                <select name="resultado" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="APROBADO" <?php echo $resultado === 'APROBADO' ? 'selected' : ''; ?>>Aprobado</option>
                                    <option value="RECHAZADO" <?php echo $resultado === 'RECHAZADO' ? 'selected' : ''; ?>>Rechazado</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label small text-muted mb-1">
                                    <i class="bi bi-calendar3 me-1"></i>Desde
                                </label>
                                <input type="date" 
                                       name="fecha_desde" 
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($fechaDesde); ?>">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label small text-muted mb-1">
                                    <i class="bi bi-calendar3 me-1"></i>Hasta
                                </label>
                                <input type="date" 
                                       name="fecha_hasta" 
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($fechaHasta); ?>">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label small text-muted mb-1">
                                    <i class="bi bi-sort-down me-1"></i>Ordenar por
                                </label>
                                <select name="ordenar" class="form-select">
                                    <option value="fecha_desc" <?php echo $ordenar === 'fecha_desc' ? 'selected' : ''; ?>>Fecha ↓</option>
                                    <option value="fecha_asc" <?php echo $ordenar === 'fecha_asc' ? 'selected' : ''; ?>>Fecha ↑</option>
                                    <option value="numero_desc" <?php echo $ordenar === 'numero_desc' ? 'selected' : ''; ?>>Número ↓</option>
                                    <option value="numero_asc" <?php echo $ordenar === 'numero_asc' ? 'selected' : ''; ?>>Número ↑</option>
                                    <option value="favor_desc" <?php echo $ordenar === 'favor_desc' ? 'selected' : ''; ?>>A Favor ↓</option>
                                    <option value="contra_desc" <?php echo $ordenar === 'contra_desc' ? 'selected' : ''; ?>>En Contra ↓</option>
                                </select>
                            </div>
                            
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-funnel"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <?php if (!empty($busqueda) || !empty($resultado) || !empty($fechaDesde) || !empty($fechaHasta)): ?>
                    <div class="mt-2">
                        <a href="eventos.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Limpiar filtros
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Tabla de Eventos -->
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Evento</th>
                                    <th style="width: 80px;">Sesión</th>
                                    <th>Título</th>
                                    <th style="width: 100px;">Fecha</th>
                                    <th class="text-center" style="width: 80px;">A Favor</th>
                                    <th class="text-center" style="width: 80px;">En Contra</th>
                                    <th class="text-center" style="width: 110px;">Resultado</th>
                                    <th class="text-center" style="width: 90px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                    <?php if (empty($eventos)): ?>
                                    <tr>
                                        <td colspan="8">
                                            <div class="empty-state">
                                                <i class="bi bi-inbox"></i>
                                                <p class="mb-0">
                                                    <?php if (!empty($busqueda) || !empty($resultado) || !empty($fechaDesde) || !empty($fechaHasta)): ?>
                                                        No se encontraron eventos con los criterios de búsqueda.
                                                    <?php else: ?>
                                                        No hay eventos registrados en el sistema.
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($eventos as $evento): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-primary">#<?php echo htmlspecialchars($evento['numero_evento']); ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars($evento['sesion_numero']); ?></small>
                                        </td>
                                        <td class="titulo-cell">
                                            <div class="evento-titulo">
                                                <?php echo htmlspecialchars($evento['titulo']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <small><?php echo formatearFecha($evento['fecha_hora'], 'd/m/Y'); ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><?php echo $evento['votos_favor'] ?? 0; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger"><?php echo $evento['votos_contra'] ?? 0; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($evento['resultado']): ?>
                                            <span class="badge bg-<?php echo $evento['resultado'] === 'APROBADO' ? 'success' : 'danger'; ?>">
                                                <?php echo $evento['resultado']; ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">Sin datos</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="detalle_evento.php?id=<?php echo $evento['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary"
                                               title="Ver detalles">
                                                <i class="bi bi-eye"></i>
                                            </a>
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
                        <nav aria-label="Paginación">
                            <ul class="pagination justify-content-center mb-0">
                                <!-- Botón Anterior -->
                                <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>" aria-label="Anterior">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <!-- Páginas -->
                                <?php
                                $inicio = max(1, $pagina - 2);
                                $fin = min($totalPaginas, $pagina + 2);
                                
                                if ($inicio > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => 1])); ?>">1</a>
                                    </li>
                                    <?php if ($inicio > 2): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                                    <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($fin < $totalPaginas): ?>
                                    <?php if ($fin < $totalPaginas - 1): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $totalPaginas])); ?>"><?php echo $totalPaginas; ?></a>
                                    </li>
                                <?php endif; ?>
                                
                                <!-- Botón Siguiente -->
                                <li class="page-item <?php echo $pagina >= $totalPaginas ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>" aria-label="Siguiente">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                            
                            <div class="text-center text-muted small mt-2">
                                Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?>
                                (<?php echo number_format($totalResultados); ?> eventos en total)
                            </div>
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
</body>
</html>