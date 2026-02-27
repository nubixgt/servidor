<?php
// modules/admin/dashboard.php
require_once '../../config/config.php';
requireAdmin();

$db = Database::getInstance()->getConnection();

// Obtener estadísticas
try {
    // Total de usuarios
    $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios");
    $totalUsuarios = $stmt->fetch()['total'];
    
    // Total de contratistas activos
    $stmt = $db->query("SELECT COUNT(*) as total FROM contratistas WHERE estado = 'activo'");
    $totalContratistas = $stmt->fetch()['total'];
    
    // Total de trabajadores activos
    $stmt = $db->query("SELECT COUNT(*) as total FROM trabajadores WHERE estado = 'activo'");
    $totalTrabajadores = $stmt->fetch()['total'];
    
    // Total de supervisiones
    $stmt = $db->query("SELECT COUNT(*) as total FROM supervisiones");
    $totalSupervisiones = $stmt->fetch()['total'];
    
    // Total de proyectos activos
    $stmt = $db->query("SELECT COUNT(*) as total FROM proyectos WHERE estado = 'activo'");
    $totalProyectos = $stmt->fetch()['total'];
    
    // Total de equipos en inventario
    $stmt = $db->query("SELECT COUNT(*) as total FROM inventario WHERE estado IN ('activo', 'en_mantenimiento')");
    $totalEquipos = $stmt->fetch()['total'];
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    // Valores por defecto en caso de error
    $totalUsuarios = 0;
    $totalContratistas = 0;
    $totalTrabajadores = 0;
    $totalSupervisiones = 0;
    $totalProyectos = 0;
    $totalEquipos = 0;
}

$pageTitle = 'Dashboard Administrador';
$extraCSS = [
    '/assets/css/pages/dashboard-admin.css'
];
$extraJS = [
    '/assets/js/pages/dashboard-admin.js'
];
require_once '../../includes/header.php';
?>

<?php require_once '../../includes/navbar_admin.php'; ?>

<main class="main-content">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-text">
                    <h1>Dashboard</h1>
                    <p>Bienvenido de vuelta, <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong></p>
                </div>
                <div class="header-badge">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5M2 12l10 5 10-5"></path>
                    </svg>
                    <span>Panel Administrativo</span>
                </div>
            </div>
        </div>
        
        <!-- Estadísticas -->
        <div class="stats-grid">
            <!-- Usuarios Totales -->
            <div class="stat-card stat-card-blue">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Usuarios Totales</div>
                    <div class="stat-value" data-target="<?php echo $totalUsuarios; ?>">0</div>
                </div>
            </div>
            
            <!-- Contratistas Activos -->
            <div class="stat-card stat-card-green">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Contratistas Activos</div>
                    <div class="stat-value" data-target="<?php echo $totalContratistas; ?>">0</div>
                </div>
            </div>
            
            <!-- Trabajadores Activos -->
            <div class="stat-card stat-card-yellow">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <polyline points="17 11 19 13 23 9"></polyline>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Trabajadores Activos</div>
                    <div class="stat-value" data-target="<?php echo $totalTrabajadores; ?>">0</div>
                </div>
            </div>
            
            <!-- Total Supervisiones -->
            <div class="stat-card stat-card-purple">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        <path d="M9 12l2 2 4-4"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total Supervisiones</div>
                    <div class="stat-value" data-target="<?php echo $totalSupervisiones; ?>">0</div>
                </div>
            </div>
            
            <!-- Proyectos Activos -->
            <div class="stat-card stat-card-orange">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Proyectos Activos</div>
                    <div class="stat-value" data-target="<?php echo $totalProyectos; ?>">0</div>
                </div>
            </div>
            
            <!-- Total Equipos en Inventario -->
            <div class="stat-card stat-card-red">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Equipos en Inventario</div>
                    <div class="stat-value" data-target="<?php echo $totalEquipos; ?>">0</div>
                </div>
            </div>
        </div>
        
        <!-- Accesos Rápidos -->
        <div class="quick-access-section">
            <div class="section-header">
                <div class="section-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 11 12 14 22 4"></polyline>
                        <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
                    </svg>
                    <h3>Accesos Rápidos</h3>
                </div>
            </div>
            
            <div class="quick-access-grid">
                <!-- Nueva Supervisión -->
                <a href="<?php echo SITE_URL; ?>/modules/admin/nueva-supervision.php" class="quick-access-card card-purple">
                    <div class="card-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14m-7-7h14"></path>
                        </svg>
                    </div>
                    <div class="card-content">
                        <h4>Nueva Supervisión</h4>
                        <p>Crear nueva supervisión de campo</p>
                    </div>
                    <div class="card-arrow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                </a>

                <!-- Supervisiones -->
                <a href="<?php echo SITE_URL; ?>/modules/admin/supervisiones.php" class="quick-access-card card-blue">
                    <div class="card-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                    </div>
                    <div class="card-content">
                        <h4>Supervisiones</h4>
                        <p>Ver todas las supervisiones registradas</p>
                    </div>
                    <div class="card-arrow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                </a>

                <!-- Proyectos -->
                <a href="<?php echo SITE_URL; ?>/modules/admin/proyectos.php" class="quick-access-card card-orange">
                    <div class="card-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                    </div>
                    <div class="card-content">
                        <h4>Proyectos</h4>
                        <p>Gestionar proyectos de construcción</p>
                    </div>
                    <div class="card-arrow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                </a>

                <!-- Inventario -->
                <a href="<?php echo SITE_URL; ?>/modules/admin/inventario.php" class="quick-access-card card-red">
                    <div class="card-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path>
                        </svg>
                    </div>
                    <div class="card-content">
                        <h4>Inventario</h4>
                        <p>Administrar equipos y maquinaria</p>
                    </div>
                    <div class="card-arrow">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </div>
                </a>
            </div>
        </div>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>