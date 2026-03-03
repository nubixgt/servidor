<?php
require_once 'config.php';
require_once 'auth.php';

$db = getDB();

// Estadísticas generales
$stmt = $db->query("SELECT COUNT(*) as total FROM eventos_votacion");
$totalEventos = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM congresistas WHERE activo = 1");
$totalCongresistas = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM bloques WHERE activo = 1");
$totalBloques = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM votos");
$totalVotos = $stmt->fetch()['total'];

// ÚLTIMOS EVENTOS - CORREGIDO con nombres de columnas reales
try {
    $stmt = $db->query("
        SELECT 
            e.id,
            e.numero_evento,
            e.titulo,
            e.sesion_numero,
            e.fecha_hora,
            COUNT(CASE WHEN v.voto = 'A FAVOR' THEN 1 END) as votos_favor,
            COUNT(CASE WHEN v.voto = 'EN CONTRA' THEN 1 END) as votos_contra,
            COUNT(CASE WHEN v.voto IN ('AUSENTE', 'LICENCIA') THEN 1 END) as ausencias,
            COUNT(v.id) as total_votos
        FROM eventos_votacion e
        LEFT JOIN votos v ON e.id = v.evento_id
        GROUP BY e.id, e.numero_evento, e.titulo, e.sesion_numero, e.fecha_hora
        ORDER BY e.fecha_hora DESC 
        LIMIT 10
    ");
    $ultimosEventos = $stmt->fetchAll();
} catch (Exception $e) {
    $ultimosEventos = [];
    error_log("Error obteniendo últimos eventos: " . $e->getMessage());
}

// CONGRESISTAS CON MÁS AUSENCIAS - CORREGIDO
try {
    $stmt = $db->query("
        SELECT 
            c.nombre,
            COUNT(CASE WHEN v.voto IN ('AUSENTE', 'LICENCIA') THEN 1 END) as ausencias,
            COUNT(*) as total_votaciones,
            ROUND((COUNT(CASE WHEN v.voto IN ('AUSENTE', 'LICENCIA') THEN 1 END) * 100.0 / COUNT(*)), 2) as porcentaje_ausencias
        FROM congresistas c
        INNER JOIN votos v ON c.id = v.congresista_id
        WHERE c.activo = 1
        GROUP BY c.id, c.nombre
        HAVING total_votaciones > 0 AND ausencias > 0
        ORDER BY porcentaje_ausencias DESC, ausencias DESC
        LIMIT 10
    ");
    $congresistasMasAusencias = $stmt->fetchAll();
} catch (Exception $e) {
    $congresistasMasAusencias = [];
    error_log("Error obteniendo congresistas con ausencias: " . $e->getMessage());
}

// BLOQUES MÁS ACTIVOS - CORREGIDO
try {
    $stmt = $db->query("
        SELECT 
            b.nombre,
            COUNT(*) as total_votos,
            COUNT(CASE WHEN v.voto = 'A FAVOR' THEN 1 END) as votos_favor,
            COUNT(CASE WHEN v.voto = 'EN CONTRA' THEN 1 END) as votos_contra
        FROM bloques b
        INNER JOIN votos v ON b.id = v.bloque_id
        WHERE b.activo = 1
        GROUP BY b.id, b.nombre
        HAVING total_votos > 0
        ORDER BY total_votos DESC
        LIMIT 5
    ");
    $bloquesActivos = $stmt->fetchAll();
} catch (Exception $e) {
    $bloquesActivos = [];
    error_log("Error obteniendo bloques activos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo APP_NAME; ?></title>
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
            <!-- Sidebar -->
            <div class="col-lg-2 sidebar" id="sidebar">
                <div class="logo text-center">
                    <img src="logo-congreso.jpg" alt="Congreso de Guatemala" style="max-width: 120px; height: auto; margin-bottom: 1rem;" onerror="this.style.display='none'">
                    <h5 class="mb-1">Congreso de la República</h5>
                    <small class="text-muted d-block">Sistema de Votaciones</small>
                </div>
                <nav class="nav flex-column mt-4">
                    <a class="nav-link active" href="index.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="eventos.php">
                        <i class="bi bi-calendar-event"></i> Eventos
                    </a>
                    <a class="nav-link" href="congresistas.php">
                        <i class="bi bi-people"></i> Congresistas
                    </a>
                    <a class="nav-link" href="bloques.php">
                        <i class="bi bi-diagram-3"></i> Bloques
                    </a>
                    <a class="nav-link" href="estadisticas.php">
                        <i class="bi bi-bar-chart"></i> Estadísticas
                    </a>
                    
                    <?php if (esAdmin()): ?>
                        <a class="nav-link" href="cargar.php">
                            <i class="bi bi-upload"></i> Cargar PDF
                        </a>
                        <a class="nav-link" href="usuarios.php">
                            <i class="bi bi-person-gear"></i> Usuarios
                        </a>
                    <?php endif; ?>
                    
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 1rem 1.5rem;">
                    
                    <div class="px-3 py-2">
                        <small class="text-muted d-block mb-1">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['nombre_completo']); ?>
                        </small>
                        <small class="text-muted d-block">
                            <span class="badge bg-<?php echo esAdmin() ? 'danger' : 'info'; ?>">
                                <?php echo ucfirst($_SESSION['tipo_usuario']); ?>
                            </span>
                        </small>
                    </div>
                    
                    <a class="nav-link text-danger" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10 main-content p-3">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="mb-3 mb-md-0">
                            <h2 class="mb-1">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </h2>
                            <p class="text-muted mb-0">
                                Panel de control del Sistema de Votaciones del Congreso
                            </p>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block">Bienvenido/a</small>
                            <strong><?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row g-3 mb-3">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                            <i class="bi bi-calendar-event text-white"></i>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="stat-number"><?php echo number_format($totalEventos); ?></div>
                                        <small class="text-muted">Eventos Registrados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                                            <i class="bi bi-people text-white"></i>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="stat-number"><?php echo number_format($totalCongresistas); ?></div>
                                        <small class="text-muted">Congresistas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                            <i class="bi bi-diagram-3 text-white"></i>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="stat-number"><?php echo number_format($totalBloques); ?></div>
                                        <small class="text-muted">Bloques Políticos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-icon" style="background: linear-gradient(135deg, #FA8BFF 0%, #2BD2FF 90%, #2BFF88 100%);">
                                            <i class="bi bi-check2-square text-white"></i>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="stat-number"><?php echo number_format($totalVotos); ?></div>
                                        <small class="text-muted">Votos Registrados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Últimos Eventos -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Últimos Eventos de Votación</h5>
                                    <a href="eventos.php" class="btn btn-sm btn-outline-primary">Ver todos</a>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 80px;">Evento</th>
                                                <th class="d-none d-md-table-cell" style="width: 80px;">Sesión</th>
                                                <th>Título</th>
                                                <th class="d-none d-lg-table-cell" style="width: 100px;">Fecha</th>
                                                <th style="width: 70px;" class="text-center">Favor</th>
                                                <th style="width: 70px;" class="text-center">Contra</th>
                                                <th class="d-none d-sm-table-cell" style="width: 70px;" class="text-center">Ausencias</th>
                                                <th style="width: 60px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($ultimosEventos)): ?>
                                            <tr>
                                                <td colspan="8">
                                                    <div class="text-center py-4">
                                                        <i class="bi bi-inbox" style="font-size: 3rem; color: #cbd5e1;"></i>
                                                        <p class="mt-3 mb-2 text-muted fs-5">No hay eventos registrados aún</p>
                                                        <p class="text-muted mb-3">Los eventos aparecerán aquí después de cargar PDFs de votación</p>
                                                        <?php if (esAdmin()): ?>
                                                        <a href="cargar.php" class="btn btn-primary">
                                                            <i class="bi bi-upload me-2"></i>Cargar Primer Evento
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php else: ?>
                                            <?php foreach ($ultimosEventos as $evento): ?>
                                            <tr>
                                                <td>
                                                    <strong class="text-primary">#<?php echo htmlspecialchars($evento['numero_evento'] ?? 'N/A'); ?></strong>
                                                </td>
                                                <td class="d-none d-md-table-cell">
                                                    <small class="text-muted"><?php echo htmlspecialchars($evento['sesion_numero'] ?? '-'); ?></small>
                                                </td>
                                                <td class="titulo-cell">
                                                    <div class="evento-titulo">
                                                        <?php echo htmlspecialchars($evento['titulo'] ?? 'Sin título'); ?>
                                                    </div>
                                                </td>
                                                <td class="d-none d-lg-table-cell">
                                                    <small><?php echo $evento['fecha_hora'] ? formatearFecha($evento['fecha_hora'], 'd/m/Y') : '-'; ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success"><?php echo (int)($evento['votos_favor'] ?? 0); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-danger"><?php echo (int)($evento['votos_contra'] ?? 0); ?></span>
                                                </td>
                                                <td class="d-none d-sm-table-cell text-center">
                                                    <span class="badge bg-warning text-dark"><?php echo (int)($evento['ausencias'] ?? 0); ?></span>
                                                </td>
                                                <td>
                                                    <a href="detalle_evento.php?id=<?php echo (int)$evento['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="Ver detalle">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
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

                <!-- Estadísticas Rápidas -->
                <div class="row">
                    <!-- Top 10 Mayor Ausentismo -->
                    <div class="col-12 col-lg-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">
                                        <i class="bi bi-person-x me-2"></i>Top 10 - Mayor Ausentismo
                                    </h5>
                                    <a href="congresistas.php" class="btn btn-sm btn-outline-primary">Ver más</a>
                                </div>
                                
                                <?php if (empty($congresistasMasAusencias)): ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-inbox" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                        <p class="mt-3 mb-0 text-muted">No hay datos de ausentismo disponibles</p>
                                        <small class="text-muted">Se mostrarán después de registrar votaciones</small>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Congresista</th>
                                                    <th class="text-center d-none d-sm-table-cell">Ausencias</th>
                                                    <th class="text-center d-none d-md-table-cell">Total</th>
                                                    <th style="width: 120px;">%</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($congresistasMasAusencias as $index => $cong): ?>
                                                <tr>
                                                    <td><span class="badge bg-secondary"><?php echo $index + 1; ?></span></td>
                                                    <td>
                                                        <div class="text-truncate" style="max-width: 200px;">
                                                            <?php echo htmlspecialchars($cong['nombre'] ?? 'N/A'); ?>
                                                        </div>
                                                    </td>
                                                    <td class="text-center d-none d-sm-table-cell">
                                                        <span class="badge bg-warning text-dark"><?php echo (int)($cong['ausencias'] ?? 0); ?></span>
                                                    </td>
                                                    <td class="text-center d-none d-md-table-cell">
                                                        <small class="text-muted"><?php echo (int)($cong['total_votaciones'] ?? 0); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $porcentaje = (float)($cong['porcentaje_ausencias'] ?? 0);
                                                        ?>
                                                        <div class="progress" style="height: 22px;">
                                                            <div class="progress-bar bg-warning" 
                                                                 style="width: <?php echo min($porcentaje, 100); ?>%"
                                                                 role="progressbar">
                                                                <small class="text-dark fw-bold"><?php echo number_format($porcentaje, 1); ?>%</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bloques Más Activos -->
                    <div class="col-12 col-lg-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">
                                        <i class="bi bi-diagram-3 me-2"></i>Bloques Más Activos
                                    </h5>
                                    <a href="bloques.php" class="btn btn-sm btn-outline-primary">Ver más</a>
                                </div>
                                
                                <?php if (empty($bloquesActivos)): ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-inbox" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                        <p class="mt-3 mb-0 text-muted">No hay datos de bloques disponibles</p>
                                        <small class="text-muted">Se mostrarán después de registrar votaciones</small>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Bloque</th>
                                                    <th class="text-center d-none d-sm-table-cell" style="width: 100px;">Total Votos</th>
                                                    <th style="width: 150px;">A Favor / Contra</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($bloquesActivos as $bloque): ?>
                                                <?php 
                                                    $totalVotosBloque = (int)($bloque['total_votos'] ?? 0);
                                                    $votosFavorBloque = (int)($bloque['votos_favor'] ?? 0);
                                                    $votosContraBloque = (int)($bloque['votos_contra'] ?? 0);
                                                    $porcentajeFavor = $totalVotosBloque > 0 ? round(($votosFavorBloque / $totalVotosBloque) * 100, 1) : 0;
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div class="text-truncate" style="max-width: 200px;" 
                                                             title="<?php echo htmlspecialchars($bloque['nombre'] ?? 'N/A'); ?>">
                                                            <small><strong><?php echo htmlspecialchars(substr($bloque['nombre'] ?? 'N/A', 0, 30)); ?><?php echo strlen($bloque['nombre'] ?? '') > 30 ? '...' : ''; ?></strong></small>
                                                        </div>
                                                    </td>
                                                    <td class="text-center d-none d-sm-table-cell">
                                                        <span class="badge bg-info"><?php echo number_format($totalVotosBloque); ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-1">
                                                            <span class="badge bg-success" style="min-width: 35px; font-size: 0.7rem;"><?php echo $votosFavorBloque; ?></span>
                                                            <div class="progress flex-grow-1" style="height: 18px;">
                                                                <div class="progress-bar bg-success" 
                                                                     style="width: <?php echo $porcentajeFavor; ?>%"
                                                                     role="progressbar">
                                                                    <small class="text-white fw-bold" style="font-size: 0.65rem;"><?php echo $porcentajeFavor; ?>%</small>
                                                                </div>
                                                            </div>
                                                            <span class="badge bg-danger" style="min-width: 35px; font-size: 0.7rem;"><?php echo $votosContraBloque; ?></span>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Script inline para menú móvil (más confiable)
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