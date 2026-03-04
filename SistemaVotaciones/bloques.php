<?php
require_once 'config.php';
$db = getDB();

// Filtros
$busqueda = isset($_GET['busqueda']) ? sanitizar($_GET['busqueda']) : '';
$minCongresistas = isset($_GET['min_congresistas']) ? (int)$_GET['min_congresistas'] : 0;
$ordenar = isset($_GET['ordenar']) ? $_GET['ordenar'] : 'favor_desc';

// Construir WHERE clause
$where = ['total_votos > 0'];
$params = [];

if ($busqueda) {
    $where[] = "nombre LIKE :busqueda";
    $params[':busqueda'] = "%$busqueda%";
}

if ($minCongresistas > 0) {
    $where[] = "total_congresistas >= :min_congresistas";
    $params[':min_congresistas'] = $minCongresistas;
}

$whereClause = implode(' AND ', $where);

// Ordenamiento
$orderBy = match($ordenar) {
    'nombre' => 'nombre ASC',
    'congresistas_desc' => 'total_congresistas DESC',
    'votos_desc' => 'total_votos DESC',
    'contra_desc' => 'votos_contra DESC',
    'ausencias_desc' => 'ausencias DESC',
    default => 'votos_favor DESC'
};

$stmt = $db->prepare("
    SELECT * FROM vista_estadisticas_bloque
    WHERE $whereClause
    ORDER BY $orderBy
");
$stmt->execute($params);
$bloques = $stmt->fetchAll();

// Estadísticas generales - Respetando filtros aplicados
$statsQuery = "
    SELECT 
        COUNT(*) as total_bloques,
        SUM(total_votos) as total_votos,
        SUM(votos_favor) as total_favor,
        SUM(votos_contra) as total_contra
    FROM vista_estadisticas_bloque
    WHERE $whereClause
";
$stmtStats = $db->prepare($statsQuery);
$stmtStats->execute($params);
$stats = $stmtStats->fetch();

// Contar congresistas únicos activos (no sumar por bloque)
$stmtCongresistas = $db->query("SELECT COUNT(*) as total FROM congresistas WHERE activo = 1");
$stats['total_congresistas'] = $stmtCongresistas->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Bloques - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        .bloque-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .bloque-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
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
            <div class="col-lg-10 main-content p-3">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-2">
                                <i class="bi bi-diagram-3 text-primary me-2"></i>Bloques Políticos
                            </h2>
                            <p class="text-muted mb-0">
                                Estadísticas por bloque parlamentario - Haz clic en un bloque para ver detalles
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas Generales -->
                <div class="row g-4 mb-3">
                    <?php if ($busqueda || $minCongresistas): ?>
                    <div class="col-12">
                        <div class="alert alert-info d-flex align-items-center">
                            <i class="bi bi-info-circle me-2"></i>
                            <div>
                                <strong>Filtros Activos:</strong>
                                <?php 
                                $filtrosActivos = [];
                                if ($busqueda) $filtrosActivos[] = "Búsqueda: '$busqueda'";
                                if ($minCongresistas) $filtrosActivos[] = "Min. Congresistas: $minCongresistas";
                                echo implode(' | ', $filtrosActivos);
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-primary bg-opacity-10">
                                        <i class="bi bi-diagram-3 text-primary"></i>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <p class="text-muted mb-1 small">Total Bloques</p>
                                        <h3 class="stat-number mb-0"><?php echo $stats['total_bloques'] ?? 0; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-info bg-opacity-10">
                                        <i class="bi bi-people text-info"></i>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <p class="text-muted mb-1 small">Congresistas</p>
                                        <h3 class="stat-number mb-0"><?php echo number_format($stats['total_congresistas'] ?? 0); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-success bg-opacity-10">
                                        <i class="bi bi-check-circle text-success"></i>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <p class="text-muted mb-1 small">Votos a Favor</p>
                                        <h3 class="stat-number mb-0"><?php echo number_format($stats['total_favor'] ?? 0); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon bg-danger bg-opacity-10">
                                        <i class="bi bi-x-circle text-danger"></i>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <p class="text-muted mb-1 small">Votos en Contra</p>
                                        <h3 class="stat-number mb-0"><?php echo number_format($stats['total_contra'] ?? 0); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="filter-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="bi bi-funnel me-2"></i>Filtros de Búsqueda
                        </h5>
                    </div>
                    
                    <form method="GET" action="bloques.php">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-search me-2"></i>Buscar Bloque
                                </label>
                                <input type="text" name="busqueda" class="form-control" 
                                       placeholder="Nombre del bloque..." value="<?php echo htmlspecialchars($busqueda); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-people me-2"></i>Min. Congresistas
                                </label>
                                <input type="number" name="min_congresistas" class="form-control" 
                                       placeholder="0" min="0" value="<?php echo $minCongresistas; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-sort-down me-2"></i>Ordenar por
                                </label>
                                <select name="ordenar" class="form-select">
                                    <option value="favor_desc" <?php echo $ordenar === 'favor_desc' ? 'selected' : ''; ?>>Más votos a favor</option>
                                    <option value="contra_desc" <?php echo $ordenar === 'contra_desc' ? 'selected' : ''; ?>>Más votos en contra</option>
                                    <option value="congresistas_desc" <?php echo $ordenar === 'congresistas_desc' ? 'selected' : ''; ?>>Más congresistas</option>
                                    <option value="votos_desc" <?php echo $ordenar === 'votos_desc' ? 'selected' : ''; ?>>Total de votos</option>
                                    <option value="ausencias_desc" <?php echo $ordenar === 'ausencias_desc' ? 'selected' : ''; ?>>Más ausencias</option>
                                    <option value="nombre" <?php echo $ordenar === 'nombre' ? 'selected' : ''; ?>>Nombre A-Z</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search me-2"></i>Buscar
                                </button>
                                <a href="bloques.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle me-2"></i>Limpiar Filtros
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Grid de Bloques -->
                <div class="row g-3">
                    <?php if (empty($bloques)): ?>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="empty-state">
                                    <i class="bi bi-diagram-3"></i>
                                    <h5 class="mt-3 mb-2">No se encontraron bloques</h5>
                                    <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <?php foreach ($bloques as $bloque): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 bloque-card" onclick="window.location.href='estadisticas.php?bloque=<?php echo $bloque['id']; ?>'">
                            <div class="card-body">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                                        <i class="bi bi-diagram-3"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1 text-primary">
                                            <?php echo htmlspecialchars($bloque['nombre']); ?>
                                        </h5>
                                        <small class="text-muted">
                                            <i class="bi bi-cursor-fill me-1"></i>Click para ver detalles
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <div class="text-center p-3 bg-light rounded">
                                            <div class="fw-bold fs-4 text-primary"><?php echo $bloque['total_congresistas']; ?></div>
                                            <small class="text-muted">Congresistas</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center p-3 bg-light rounded">
                                            <div class="fw-bold fs-4 text-primary"><?php echo number_format($bloque['total_votos']); ?></div>
                                            <small class="text-muted">Total Votos</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row g-2 mb-3">
                                    <div class="col-4">
                                        <div class="text-center">
                                            <span class="badge bg-success fs-6"><?php echo $bloque['votos_favor']; ?></span>
                                            <br><small class="text-muted">A Favor</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-center">
                                            <span class="badge bg-danger fs-6"><?php echo $bloque['votos_contra']; ?></span>
                                            <br><small class="text-muted">En Contra</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-center">
                                            <span class="badge bg-warning text-dark fs-6"><?php echo $bloque['ausencias']; ?></span>
                                            <br><small class="text-muted">Ausencias</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="progress mb-2" style="height: 28px;">
                                    <?php
                                    $totalEfectivos = $bloque['votos_favor'] + $bloque['votos_contra'];
                                    $pctFavor = $totalEfectivos > 0 ? ($bloque['votos_favor'] / $totalEfectivos * 100) : 0;
                                    $pctContra = $totalEfectivos > 0 ? ($bloque['votos_contra'] / $totalEfectivos * 100) : 0;
                                    ?>
                                    <div class="progress-bar bg-success" style="width: <?php echo $pctFavor; ?>%">
                                        <?php if ($pctFavor > 10): ?>
                                            <?php echo number_format($pctFavor, 1); ?>%
                                        <?php endif; ?>
                                    </div>
                                    <div class="progress-bar bg-danger" style="width: <?php echo $pctContra; ?>%">
                                        <?php if ($pctContra > 10): ?>
                                            <?php echo number_format($pctContra, 1); ?>%
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="text-center">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <?php echo number_format($pctFavor, 1); ?>% a favor | 
                                        <?php echo number_format($pctContra, 1); ?>% en contra
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
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