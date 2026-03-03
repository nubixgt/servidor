<?php
require_once 'config.php';
$db = getDB();
$evento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($evento_id <= 0) die("Evento no válido");

// Obtener información del evento
$stmt = $db->prepare("
    SELECT e.*, r.total_votos, r.votos_favor, r.votos_contra, r.votos_ausentes,
           r.votos_licencia, r.votos_abstencion, r.resultado
    FROM eventos_votacion e
    LEFT JOIN resumen_eventos r ON e.id = r.evento_id
    WHERE e.id = ?
");
$stmt->execute([$evento_id]);
$evento = $stmt->fetch();

if (!$evento) die("Evento no encontrado");

// Filtros para votos
$filtroBloque = isset($_GET['bloque']) ? (int)$_GET['bloque'] : 0;
$filtroVoto = isset($_GET['voto']) ? $_GET['voto'] : '';
$busquedaNombre = isset($_GET['busqueda']) ? sanitizar($_GET['busqueda']) : '';

// WHERE para votos
$whereVotos = ['v.evento_id = ?'];
$paramsVotos = [$evento_id];

if ($filtroBloque > 0) {
    $whereVotos[] = "v.bloque_id = ?";
    $paramsVotos[] = $filtroBloque;
}

if ($filtroVoto) {
    $whereVotos[] = "v.voto = ?";
    $paramsVotos[] = $filtroVoto;
}

if ($busquedaNombre) {
    $whereVotos[] = "(c.nombre LIKE ? OR c.nombre_normalizado LIKE ?)";
    $paramsVotos[] = "%$busquedaNombre%";
    $paramsVotos[] = "%$busquedaNombre%";
}

$whereClauseVotos = implode(' AND ', $whereVotos);

// Obtener votos
$stmt = $db->prepare("
    SELECT v.id, v.numero_orden, c.nombre as congresista_nombre,
           b.nombre as bloque_nombre, v.voto, b.id as bloque_id
    FROM votos v
    INNER JOIN congresistas c ON v.congresista_id = c.id
    INNER JOIN bloques b ON v.bloque_id = b.id
    WHERE $whereClauseVotos
    ORDER BY v.numero_orden ASC
");
$stmt->execute($paramsVotos);
$votos = $stmt->fetchAll();

// Votos por bloque
$stmt = $db->prepare("
    SELECT b.nombre as bloque, COUNT(*) as total,
           SUM(CASE WHEN v.voto = 'A FAVOR' THEN 1 ELSE 0 END) as favor,
           SUM(CASE WHEN v.voto = 'EN CONTRA' THEN 1 ELSE 0 END) as contra,
           SUM(CASE WHEN v.voto = 'AUSENTE' THEN 1 ELSE 0 END) as ausente,
           SUM(CASE WHEN v.voto = 'LICENCIA' THEN 1 ELSE 0 END) as licencia,
           SUM(CASE WHEN v.voto IN ('ABSTENCION', 'ABSTENCIÓN') THEN 1 ELSE 0 END) as abstencion
    FROM votos v
    JOIN bloques b ON v.bloque_id = b.id
    WHERE v.evento_id = ?
    GROUP BY b.id, b.nombre
    ORDER BY favor DESC
");
$stmt->execute([$evento_id]);
$votosPorBloque = $stmt->fetchAll();

// Bloques para filtro
$stmt = $db->query("SELECT id, nombre FROM bloques WHERE activo = 1 ORDER BY nombre");
$bloques = $stmt->fetchAll();

$porcentajeFavor = $evento['total_votos'] > 0 ? ($evento['votos_favor'] / $evento['total_votos']) * 100 : 0;
$porcentajeContra = $evento['total_votos'] > 0 ? ($evento['votos_contra'] / $evento['total_votos']) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Evento #<?php echo $evento['numero_evento']; ?> - <?php echo APP_NAME; ?></title>
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
            <div class="col-lg-2 sidebar" id="sidebar">
                <div class="logo text-center">
                    <img src="logo-congreso.jpg" alt="Congreso de Guatemala" style="max-width: 120px; height: auto; margin-bottom: 1rem;">
                    <h5 class="mb-1">Congreso de la República</h5>
                    <small class="text-muted d-block">Sistema de Votaciones</small>
                </div>
                <nav class="nav flex-column mt-4">
                    <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a class="nav-link active" href="eventos.php"><i class="bi bi-calendar-event"></i> Eventos</a>
                    <a class="nav-link" href="congresistas.php"><i class="bi bi-people"></i> Congresistas</a>
                    <a class="nav-link" href="bloques.php"><i class="bi bi-diagram-3"></i> Bloques</a>
                    <a class="nav-link" href="estadisticas.php"><i class="bi bi-bar-chart"></i> Estadísticas</a>
                    <a class="nav-link" href="cargar.php"><i class="bi bi-upload"></i> Cargar PDF</a>
                </nav>
            </div>
            
            <div class="col-lg-10 main-content">
                <div class="page-header">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
                        <div class="flex-grow-1">
                            <h2 class="mb-2">
                                <i class="bi bi-file-text me-2"></i>Evento #<?php echo htmlspecialchars($evento['numero_evento']); ?>
                            </h2>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($evento['titulo']); ?></p>
                        </div>
                        <a href="eventos.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
                
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center p-3">
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary mx-auto mb-2" >
                                    <i class="bi bi-check-all"></i>
                                </div>
                                <div class="fw-bold fs-4"><?php echo $evento['total_votos']; ?></div>
                                <small class="text-muted">Total</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center p-3">
                                <div class="stat-icon bg-success bg-opacity-10 text-success mx-auto mb-2" >
                                    <i class="bi bi-hand-thumbs-up"></i>
                                </div>
                                <div class="fw-bold fs-4 text-success"><?php echo $evento['votos_favor']; ?></div>
                                <small class="text-muted">A Favor</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center p-3">
                                <div class="stat-icon bg-danger bg-opacity-10 text-danger mx-auto mb-2" >
                                    <i class="bi bi-hand-thumbs-down"></i>
                                </div>
                                <div class="fw-bold fs-4 text-danger"><?php echo $evento['votos_contra']; ?></div>
                                <small class="text-muted">En Contra</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center p-3">
                                <div class="stat-icon bg-warning bg-opacity-10 text-warning mx-auto mb-2" >
                                    <i class="bi bi-person-x"></i>
                                </div>
                                <div class="fw-bold fs-4 text-warning"><?php echo $evento['votos_ausentes']; ?></div>
                                <small class="text-muted">Ausentes</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center p-3">
                                <div class="stat-icon bg-info bg-opacity-10 text-info mx-auto mb-2" >
                                    <i class="bi bi-file-medical"></i>
                                </div>
                                <div class="fw-bold fs-4 text-info"><?php echo $evento['votos_licencia']; ?></div>
                                <small class="text-muted">Licencia</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card stat-card h-100">
                            <div class="card-body text-center p-3">
                                <div class="stat-icon bg-secondary bg-opacity-10 text-secondary mx-auto mb-2" >
                                    <i class="bi bi-dash-circle"></i>
                                </div>
                                <div class="fw-bold fs-4 text-secondary"><?php echo $evento['votos_abstencion']; ?></div>
                                <small class="text-muted">Abstención</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="mb-3"><i class="bi bi-graph-up me-2"></i>Distribución de Votos</h5>
                        <div class="progress" style="height: 35px;">
                            <div class="progress-bar bg-success" style="width: <?php echo $porcentajeFavor; ?>%">
                                <?php echo number_format($porcentajeFavor, 1); ?>% A Favor
                            </div>
                            <div class="progress-bar bg-danger" style="width: <?php echo $porcentajeContra; ?>%">
                                <?php echo number_format($porcentajeContra, 1); ?>% En Contra
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="mb-3"><i class="bi bi-diagram-3 me-2"></i>Votación por Bloque</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Bloque</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">Favor</th>
                                        <th class="text-center">Contra</th>
                                        <th class="text-center">Ausente</th>
                                        <th class="text-center">Licencia</th>
                                        <th class="text-center">Abstención</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($votosPorBloque as $vb): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($vb['bloque']); ?></strong></td>
                                        <td class="text-center"><?php echo $vb['total']; ?></td>
                                        <td class="text-center"><span class="badge bg-success"><?php echo $vb['favor']; ?></span></td>
                                        <td class="text-center"><span class="badge bg-danger"><?php echo $vb['contra']; ?></span></td>
                                        <td class="text-center"><span class="badge bg-warning"><?php echo $vb['ausente']; ?></span></td>
                                        <td class="text-center"><span class="badge bg-info"><?php echo $vb['licencia']; ?></span></td>
                                        <td class="text-center"><span class="badge bg-secondary"><?php echo $vb['abstencion']; ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="filter-section">
                    <h5 class="mb-3">
                        <i class="bi bi-funnel me-2"></i>Filtrar Votos
                    </h5>
                    <form method="GET" action="detalle_evento.php">
                        <input type="hidden" name="id" value="<?php echo $evento_id; ?>">
                        <div class="row g-3">
                            <div class="col-12 col-md-6 col-lg-4">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-search me-2"></i>Buscar Congresista
                                </label>
                                <input type="text" 
                                       name="busqueda" 
                                       class="form-control" 
                                       placeholder="Nombre del congresista..." 
                                       value="<?php echo htmlspecialchars($busquedaNombre); ?>">
                            </div>
                            <div class="col-6 col-md-6 col-lg-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-diagram-3 me-2"></i>Bloque
                                </label>
                                <select name="bloque" class="form-select">
                                    <option value="0">Todos los bloques</option>
                                    <?php foreach ($bloques as $b): ?>
                                    <option value="<?php echo $b['id']; ?>" <?php echo $filtroBloque == $b['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($b['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-6 col-lg-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-filter me-2"></i>Tipo de Voto
                                </label>
                                <select name="voto" class="form-select">
                                    <option value="">Todos los votos</option>
                                    <option value="A FAVOR" <?php echo $filtroVoto === 'A FAVOR' ? 'selected' : ''; ?>>A Favor</option>
                                    <option value="EN CONTRA" <?php echo $filtroVoto === 'EN CONTRA' ? 'selected' : ''; ?>>En Contra</option>
                                    <option value="AUSENTE" <?php echo $filtroVoto === 'AUSENTE' ? 'selected' : ''; ?>>Ausente</option>
                                    <option value="LICENCIA" <?php echo $filtroVoto === 'LICENCIA' ? 'selected' : ''; ?>>Licencia</option>
                                    <option value="ABSTENCION" <?php echo $filtroVoto === 'ABSTENCION' ? 'selected' : ''; ?>>Abstención</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6 col-lg-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search me-2"></i>Filtrar
                                </button>
                            </div>
                        </div>
                        
                        <?php if ($filtroBloque || $filtroVoto || $busquedaNombre): ?>
                        <div class="mt-3">
                            <div class="alert alert-info d-flex align-items-center">
                                <i class="bi bi-info-circle me-2"></i>
                                <div class="flex-grow-1">
                                    <strong>Filtros activos:</strong>
                                    <?php 
                                    $filtrosActivos = [];
                                    if ($busquedaNombre) $filtrosActivos[] = "Búsqueda: '$busquedaNombre'";
                                    if ($filtroBloque) {
                                        $bloqueNombre = array_filter($bloques, fn($b) => $b['id'] == $filtroBloque);
                                        if ($bloqueNombre) {
                                            $filtrosActivos[] = "Bloque: " . reset($bloqueNombre)['nombre'];
                                        }
                                    }
                                    if ($filtroVoto) $filtrosActivos[] = "Voto: $filtroVoto";
                                    echo implode(' | ', $filtrosActivos);
                                    ?>
                                </div>
                                <a href="detalle_evento.php?id=<?php echo $evento_id; ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-x-circle me-1"></i>Limpiar
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">
                            <i class="bi bi-list-check me-2"></i>
                            Detalle de Votos (<?php echo count($votos); ?> registros)
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Congresista</th>
                                        <th class="d-none d-md-table-cell" style="width: 200px;">Bloque</th>
                                        <th style="width: 150px;">Voto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($votos)): ?>
                                    <tr>
                                        <td colspan="3">
                                            <div class="empty-state">
                                                <i class="bi bi-inbox"></i>
                                                <h5 class="mt-3 mb-2">No se encontraron votos</h5>
                                                <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($votos as $index => $v): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($v['congresista_nombre']); ?></strong>
                                            <small class="d-block d-md-none text-muted">
                                                <?php echo htmlspecialchars($v['bloque_nombre']); ?>
                                            </small>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($v['bloque_nombre']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php
                                            $votoClass = match($v['voto']) {
                                                'A FAVOR' => 'bg-success',
                                                'EN CONTRA' => 'bg-danger',
                                                'AUSENTE' => 'bg-warning text-dark',
                                                'LICENCIA' => 'bg-info',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?php echo $votoClass; ?>">
                                                <?php echo htmlspecialchars($v['voto']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="responsive.js"></script>
    </script>
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