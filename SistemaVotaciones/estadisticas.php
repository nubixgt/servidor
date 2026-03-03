<?php
require_once 'config.php';
$db = getDB();

// Filtros
$congresistaId = isset($_GET['congresista']) ? (int)$_GET['congresista'] : null;
$bloqueId = isset($_GET['bloque']) ? (int)$_GET['bloque'] : null;
$eventoId = isset($_GET['evento']) ? (int)$_GET['evento'] : null;
$fechaDesde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fechaHasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

// Construir filtros de fecha para las consultas
$fechaWhere = '1=1';
$fechaParams = [];
if ($fechaDesde) {
    $fechaWhere .= " AND DATE(e.fecha_hora) >= :fecha_desde";
    $fechaParams[':fecha_desde'] = $fechaDesde;
}
if ($fechaHasta) {
    $fechaWhere .= " AND DATE(e.fecha_hora) <= :fecha_hasta";
    $fechaParams[':fecha_hasta'] = $fechaHasta;
}

// Listas para filtros
$stmt = $db->query("SELECT id, nombre FROM congresistas ORDER BY nombre LIMIT 100");
$congresistas = $stmt->fetchAll();

$stmt = $db->query("SELECT id, nombre FROM bloques WHERE activo = 1 ORDER BY nombre");
$bloques = $stmt->fetchAll();

$stmt = $db->query("SELECT id, numero_evento, titulo FROM eventos_votacion ORDER BY fecha_hora DESC LIMIT 100");
$eventos = $stmt->fetchAll();

// Datos del congresista
$datosCongresista = null;
$votosHistorico = [];
if ($congresistaId) {
    $stmt = $db->prepare("SELECT * FROM vista_estadisticas_congresista WHERE id = ?");
    $stmt->execute([$congresistaId]);
    $datosCongresista = $stmt->fetch();
    
    // Aplicar filtros de fecha a votos históricos
    $queryVotos = "
        SELECT e.numero_evento, e.titulo, e.sesion_numero, e.fecha_hora, v.voto
        FROM votos v
        JOIN eventos_votacion e ON v.evento_id = e.id
        WHERE v.congresista_id = :congresista_id AND $fechaWhere
        ORDER BY e.fecha_hora DESC
        LIMIT 20
    ";
    $stmt = $db->prepare($queryVotos);
    $stmt->bindValue(':congresista_id', $congresistaId, PDO::PARAM_INT);
    foreach ($fechaParams as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $votosHistorico = $stmt->fetchAll();
}

// Datos del bloque
$datosBloque = null;
if ($bloqueId) {
    $stmt = $db->prepare("SELECT * FROM vista_estadisticas_bloque WHERE id = ?");
    $stmt->execute([$bloqueId]);
    $datosBloque = $stmt->fetch();
}

// Datos del evento
$datosEvento = null;
$votosEvento = [];
if ($eventoId) {
    $stmt = $db->prepare("
        SELECT e.*, r.* FROM eventos_votacion e
        LEFT JOIN resumen_eventos r ON e.id = r.evento_id
        WHERE e.id = ?
    ");
    $stmt->execute([$eventoId]);
    $datosEvento = $stmt->fetch();
    
    $stmt = $db->prepare("
        SELECT b.nombre as bloque, COUNT(*) as total,
               SUM(CASE WHEN v.voto = 'A FAVOR' THEN 1 ELSE 0 END) as favor,
               SUM(CASE WHEN v.voto = 'EN CONTRA' THEN 1 ELSE 0 END) as contra
        FROM votos v
        JOIN bloques b ON v.bloque_id = b.id
        WHERE v.evento_id = ?
        GROUP BY b.id, b.nombre
        ORDER BY favor DESC
    ");
    $stmt->execute([$eventoId]);
    $votosEvento = $stmt->fetchAll();
}

// Estadísticas generales
$stmt = $db->query("SELECT voto, COUNT(*) as cantidad FROM votos GROUP BY voto");
$distribucionVotos = $stmt->fetchAll();

$stmt = $db->query("
    SELECT * FROM vista_estadisticas_bloque
    WHERE total_votos > 0
    ORDER BY votos_favor DESC
    LIMIT 8
");
$estadisticasBloques = $stmt->fetchAll();

$stmt = $db->prepare("
    SELECT e.id, e.numero_evento, e.sesion_numero, e.titulo, e.fecha_hora, r.votos_favor, r.votos_contra, r.resultado,
           ABS(r.votos_favor - r.votos_contra) as diferencia
    FROM eventos_votacion e
    JOIN resumen_eventos r ON e.id = r.evento_id
    WHERE r.votos_contra > 10 AND $fechaWhere
    ORDER BY r.votos_contra DESC, diferencia ASC
    LIMIT 10
");
foreach ($fechaParams as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$eventosPolemicos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Estadísticas - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
                    <a class="nav-link" href="eventos.php"><i class="bi bi-calendar-event"></i> Eventos</a>
                    <a class="nav-link" href="congresistas.php"><i class="bi bi-people"></i> Congresistas</a>
                    <a class="nav-link" href="bloques.php"><i class="bi bi-diagram-3"></i> Bloques</a>
                    <a class="nav-link active" href="estadisticas.php"><i class="bi bi-bar-chart"></i> Estadísticas</a>
                    <a class="nav-link" href="cargar.php"><i class="bi bi-upload"></i> Cargar PDF</a>
                </nav>
            </div>
            
            <div class="col-lg-10 main-content">
                <div class="page-header">
                    <h2 class="mb-1"><i class="bi bi-bar-chart me-2"></i>Estadísticas y Análisis</h2>
                    <p class="text-muted mb-0">Visualización avanzada de datos de votaciones</p>
                </div>
                
                <div class="filters-section">
                    <form method="GET" action="estadisticas.php" id="filterForm">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold"><i class="bi bi-person me-2"></i>Congresista</label>
                                <select name="congresista" class="form-select">
                                    <option value="">Seleccione un congresista</option>
                                    <?php foreach ($congresistas as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo $congresistaId == $c['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold"><i class="bi bi-diagram-3 me-2"></i>Bloque Político</label>
                                <select name="bloque" class="form-select">
                                    <option value="">Seleccione un bloque</option>
                                    <?php foreach ($bloques as $b): ?>
                                    <option value="<?php echo $b['id']; ?>" <?php echo $bloqueId == $b['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($b['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold"><i class="bi bi-calendar-event me-2"></i>Evento</label>
                                <select name="evento" class="form-select">
                                    <option value="">Seleccione un evento</option>
                                    <?php foreach ($eventos as $e): ?>
                                    <option value="<?php echo $e['id']; ?>" <?php echo $eventoId == $e['id'] ? 'selected' : ''; ?>>
                                        #<?php echo $e['numero_evento']; ?> - <?php echo substr(htmlspecialchars($e['titulo']), 0, 40); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold"><i class="bi bi-calendar me-2"></i>Fecha Desde</label>
                                <input type="date" name="fecha_desde" class="form-control" 
                                       value="<?php echo htmlspecialchars($fechaDesde); ?>">
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold"><i class="bi bi-calendar me-2"></i>Fecha Hasta</label>
                                <input type="date" name="fecha_hasta" class="form-control" 
                                       value="<?php echo htmlspecialchars($fechaHasta); ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-search me-2"></i>Buscar
                                </button>
                                <?php if ($congresistaId || $bloqueId || $eventoId || $fechaDesde || $fechaHasta): ?>
                                <a href="estadisticas.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle me-2"></i>Limpiar
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
                
                <?php if ($datosCongresista): ?>
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3"><i class="bi bi-person-circle me-2"></i><?php echo htmlspecialchars($datosCongresista['nombre']); ?></h5>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="fs-3 fw-bold text-primary"><?php echo $datosCongresista['total_votaciones']; ?></div>
                                    <small class="text-muted">Votaciones</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="fs-3 fw-bold text-success"><?php echo $datosCongresista['votos_favor']; ?></div>
                                    <small class="text-muted">A Favor</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="fs-3 fw-bold text-danger"><?php echo $datosCongresista['votos_contra']; ?></div>
                                    <small class="text-muted">En Contra</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="fs-3 fw-bold text-warning"><?php echo number_format($datosCongresista['porcentaje_ausencias'], 1); ?>%</div>
                                    <small class="text-muted">Ausencias</small>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="chart-container">
                                <canvas id="chartVotosCongresista"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3"><i class="bi bi-clock-history me-2"></i>Historial Reciente</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Evento</th>
                                        <th>Título</th>
                                        <th>Sesión</th>
                                        <th>Fecha</th>
                                        <th>Voto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($votosHistorico as $v): ?>
                                    <tr>
                                        <td><small><strong>#<?php echo $v['numero_evento']; ?></strong></small></td>
                                        <td>
                                            <small class="evento-titulo">
                                                <?php echo htmlspecialchars($v['titulo']); ?>
                                            </small>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($v['sesion_numero'] ?? 'N/A'); ?></small></td>
                                        <td><small><?php echo formatearFecha($v['fecha_hora'], 'd/m/Y'); ?></small></td>
                                        <td>
                                            <span class="badge bg-<?php echo match($v['voto']) {
                                                'A FAVOR' => 'success',
                                                'EN CONTRA' => 'danger',
                                                'AUSENTE' => 'warning',
                                                default => 'secondary'
                                            }; ?>"><?php echo $v['voto']; ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($datosBloque): ?>
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3"><i class="bi bi-diagram-3 me-2"></i><?php echo htmlspecialchars($datosBloque['nombre']); ?></h5>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="fs-3 fw-bold"><?php echo $datosBloque['total_congresistas']; ?></div>
                                    <small class="text-muted">Congresistas</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="fs-3 fw-bold text-success"><?php echo $datosBloque['votos_favor']; ?></div>
                                    <small class="text-muted">A Favor</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="fs-3 fw-bold text-danger"><?php echo $datosBloque['votos_contra']; ?></div>
                                    <small class="text-muted">En Contra</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="fs-3 fw-bold text-warning"><?php echo $datosBloque['ausencias']; ?></div>
                                    <small class="text-muted">Ausencias</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($datosEvento): ?>
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3"><i class="bi bi-calendar-event me-2"></i>Evento #<?php echo $datosEvento['numero_evento']; ?></h5>
                        <p class="text-muted mb-3"><?php echo htmlspecialchars($datosEvento['titulo']); ?></p>
                        <div class="row g-3">
                            <?php foreach ($votosEvento as $v): ?>
                            <div class="col-md-3">
                                <div class="card border">
                                    <div class="card-body text-center p-3">
                                        <h6 class="mb-2"><?php echo htmlspecialchars(substr($v['bloque'], 0, 25)); ?></h6>
                                        <div>
                                            <span class="badge bg-success me-1"><?php echo $v['favor']; ?></span>
                                            <span class="badge bg-danger"><?php echo $v['contra']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="mb-3"><i class="bi bi-pie-chart me-2"></i>Distribución General</h5>
                                <div class="chart-container">
                                    <canvas id="chartDistribucionGeneral"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="mb-3"><i class="bi bi-bar-chart me-2"></i>Top Bloques</h5>
                                <div class="chart-container">
                                    <canvas id="chartBloques"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3"><i class="bi bi-exclamation-triangle me-2"></i>Eventos Más Polémicos</h5>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th style="width: 80px;">Evento</th>
                                        <th style="width: 80px;">Sesión</th>
                                        <th style="width: 100px;">Fecha</th>
                                        <th class="text-center" style="width: 80px;">Favor</th>
                                        <th class="text-center" style="width: 80px;">Contra</th>
                                        <th class="text-center" style="width: 100px;">Diferencia</th>
                                        <th style="width: 110px;">Resultado</th>
                                        <th class="text-center" style="width: 80px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($eventosPolemicos as $ev): ?>
                                    <tr>
                                        <td class="titulo-cell">
                                            <div class="evento-titulo">
                                                <?php echo htmlspecialchars($ev['titulo']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong class="text-primary">#<?php echo $ev['numero_evento']; ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars($ev['sesion_numero'] ?? '-'); ?></small>
                                        </td>
                                        <td><?php echo formatearFecha($ev['fecha_hora'], 'd/m/Y'); ?></td>
                                        <td class="text-center"><span class="badge bg-success"><?php echo $ev['votos_favor']; ?></span></td>
                                        <td class="text-center"><span class="badge bg-danger"><?php echo $ev['votos_contra']; ?></span></td>
                                        <td class="text-center"><span class="badge bg-warning text-dark"><?php echo $ev['diferencia']; ?></span></td>
                                        <td><span class="badge bg-<?php echo $ev['resultado'] === 'APROBADO' ? 'success' : 'danger'; ?>"><?php echo $ev['resultado']; ?></span></td>
                                        <td class="text-center">
                                            <a href="detalle_evento.php?id=<?php echo $ev['id']; ?>" class="btn btn-sm btn-outline-primary" data-tooltip="Ver detalles">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript para menú móvil responsive
        document.addEventListener('DOMContentLoaded', function() {
            const menuBtn = document.getElementById('mobileMenuBtn');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (menuBtn && sidebar && overlay) {
                // Abrir/cerrar menú
                menuBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    overlay.classList.toggle('show');
                    
                    // Cambiar ícono
                    const icon = this.querySelector('i');
                    if (sidebar.classList.contains('show')) {
                        icon.className = 'bi bi-x';
                        document.body.style.overflow = 'hidden';
                    } else {
                        icon.className = 'bi bi-list';
                        document.body.style.overflow = '';
                    }
                });
                
                // Cerrar al hacer clic en overlay
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                    menuBtn.querySelector('i').className = 'bi bi-list';
                    document.body.style.overflow = '';
                });
                
                // Cerrar al hacer clic en un link
                const navLinks = sidebar.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth < 992) {
                            sidebar.classList.remove('show');
                            overlay.classList.remove('show');
                            menuBtn.querySelector('i').className = 'bi bi-list';
                            document.body.style.overflow = '';
                        }
                    });
                });
                
                // Cerrar con ESC
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && sidebar.classList.contains('show')) {
                        sidebar.classList.remove('show');
                        overlay.classList.remove('show');
                        menuBtn.querySelector('i').className = 'bi bi-list';
                        document.body.style.overflow = '';
                    }
                });
            }
        });
    </script>
    <script>
        <?php if ($datosCongresista): ?>
        new Chart(document.getElementById('chartVotosCongresista'), {
            type: 'doughnut',
            data: {
                labels: ['A Favor', 'En Contra', 'Ausencias', 'Licencias'],
                datasets: [{
                    data: [<?php echo $datosCongresista['votos_favor']; ?>, <?php echo $datosCongresista['votos_contra']; ?>, <?php echo $datosCongresista['ausencias']; ?>, <?php echo $datosCongresista['licencias'] ?? 0; ?>],
                    backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#6b7280']
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: true,
                plugins: { legend: { position: 'bottom' } } 
            }
        });
        <?php endif; ?>
        
        new Chart(document.getElementById('chartDistribucionGeneral'), {
            type: 'pie',
            data: {
                labels: [<?php echo "'" . implode("','", array_column($distribucionVotos, 'voto')) . "'"; ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($distribucionVotos, 'cantidad')); ?>],
                    backgroundColor: ['#10b981', '#ef4444', '#f59e0b', '#6b7280', '#3b82f6']
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: true,
                plugins: { legend: { position: 'bottom' } } 
            }
        });
        
        new Chart(document.getElementById('chartBloques'), {
            type: 'bar',
            data: {
                labels: [<?php echo "'" . implode("','", array_map(function($b) { return substr($b['nombre'], 0, 15); }, $estadisticasBloques)) . "'"; ?>],
                datasets: [{
                    label: 'Votos a Favor',
                    data: [<?php echo implode(',', array_column($estadisticasBloques, 'votos_favor')); ?>],
                    backgroundColor: '#667eea'
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: true,
                plugins: { legend: { display: false } }, 
                scales: { y: { beginAtZero: true } } 
            }
        });
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