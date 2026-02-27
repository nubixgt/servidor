<?php
/**
 * modules/tecnico/dashboard.php
 * Dashboard para Técnicos con Niveles de Acceso
 * Sistema de Supervisión v6.0.4
 * ACTUALIZADO: Removido "Tu Nivel de Acceso", "Supervisiones Hoy" y "Equipos Activos"
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

requireLogin();
requireTecnico();

// Obtener nivel de acceso
$nivelAcceso = getNivelAcceso();
$nombreUsuario = $_SESSION['usuario'] ?? 'Usuario';
$tieneInventario = tieneAccesoModulo('inventario');
$usuarioId = $_SESSION['user_id'] ?? null;

// Obtener estadísticas
$db = Database::getInstance()->getConnection();

// ⭐ Total de supervisiones DEL TÉCNICO
$stmt = $db->prepare("SELECT COUNT(*) as total FROM supervisiones WHERE usuario_id = :usuario_id");
$stmt->execute(['usuario_id' => $usuarioId]);
$totalSupervisiones = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// ⭐ Total de equipos DEL TÉCNICO (si es técnico completo)
$totalInventario = 0;

if ($tieneInventario) {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM inventario WHERE usuario_id = :usuario_id");
    $stmt->execute(['usuario_id' => $usuarioId]);
    $totalInventario = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

// Configurar página
$pageTitle = 'Dashboard - Técnico';
$extraCSS = [
    'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
    SITE_URL . '/assets/css/pages/dashboard-tecnico.css'
];
$extraJS = [
    'https://code.jquery.com/jquery-3.7.0.min.js',
    'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    SITE_URL . '/assets/js/navbar_tecnico.js', // ⭐ IMPORTANTE
    SITE_URL . '/assets/js/pages/dashboard-tecnico.js'
];

include '../../includes/header.php';
include '../../includes/navbar_tecnico.php';
?>

<main class="main-content">
    <!-- Page Header -->
    <div class="page-header glass-header">
        <div>
            <h1>👋 Bienvenido, <?php echo htmlspecialchars($nombreUsuario); ?></h1>
            <p>Dashboard - Técnico <?php echo $nivelAcceso === NIVEL_COMPLETO ? 'Completo' : 'Básico'; ?></p>
        </div>
        <?php echo getBadgeNivel(); ?>
    </div>

    <!-- Estadísticas -->
    <div class="dashboard-grid">
        <!-- ⭐ Total Supervisiones (solo esta tarjeta) -->
        <div class="stat-card glass-card" data-color="purple">
            <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
            <div class="stat-info">
                <h3>Total Supervisiones</h3>
                <p class="stat-number" data-target="<?php echo $totalSupervisiones; ?>">0</p>
            </div>
        </div>

        <?php if ($tieneInventario): ?>
        <!-- ⭐ Total Equipos (solo técnicos completos) -->
        <div class="stat-card glass-card" data-color="red">
            <div class="stat-icon" style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path>
                </svg>
            </div>
            <div class="stat-info">
                <h3>Total Equipos</h3>
                <p class="stat-number" data-target="<?php echo $totalInventario; ?>">0</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Accesos Rápidos -->
    <div class="section-card glass-card">
        <div class="section-header">
            <h2>⚡ Accesos Rápidos</h2>
        </div>
        <div class="quick-actions-grid">
            <a href="<?php echo SITE_URL; ?>/modules/tecnico/nueva-supervision.php" class="quick-action-card">
                <div class="action-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14m-7-7h14"></path>
                    </svg>
                </div>
                <h3>Nueva Supervisión</h3>
                <p>Registrar una nueva supervisión</p>
            </a>

            <a href="<?php echo SITE_URL; ?>/modules/tecnico/supervisiones.php" class="quick-action-card">
                <div class="action-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"></path>
                    </svg>
                </div>
                <h3>Ver Supervisiones</h3>
                <p>Consultar todas las supervisiones</p>
            </a>

            <?php if ($tieneInventario): ?>
            <a href="<?php echo SITE_URL; ?>/modules/tecnico/inventario.php" class="quick-action-card">
                <div class="action-icon" style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path>
                    </svg>
                </div>
                <h3>Ver Inventario</h3>
                <p>Gestionar equipos e inventario</p>
                <span class="action-badge">Completo</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>