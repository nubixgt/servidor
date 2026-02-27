<?php
/**
 * includes/navbar_tecnico.php
 * Navbar para Técnicos con Niveles de Acceso
 * Sistema de Supervisión v6.0.4
 * ACTUALIZADO: Rutas corregidas para técnicos
 */

// Obtener nivel de acceso
$nivelAcceso = getNivelAcceso();
$tieneInventario = tieneAccesoModulo('inventario');

// Configurar badge según nivel
$badgeColor = ($nivelAcceso === NIVEL_COMPLETO) ? 'role-tecnico-completo' : 'role-tecnico-basico';
$badgeText = ($nivelAcceso === NIVEL_COMPLETO) ? 'Técnico Completo' : 'Técnico Básico';
?>

<!-- Sidebar Técnico -->
<aside class="sidebar">
    <!-- Logo y Brand -->
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="sidebar-brand">
            <h2>Supervisión</h2>
            <span class="role-badge <?php echo $badgeColor; ?>"><?php echo $badgeText; ?></span>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <!-- Dashboard -->
        <a href="<?php echo SITE_URL; ?>/modules/tecnico/dashboard.php" class="nav-item" data-page="dashboard">
            <div class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
            </div>
            <span class="nav-text">Dashboard</span>
        </a>

        <!-- Divider -->
        <div class="nav-divider">
            <span>SUPERVISIONES</span>
        </div>

        <!-- ⭐ Nueva Supervisión - RUTA CORREGIDA -->
        <a href="<?php echo SITE_URL; ?>/modules/tecnico/nueva-supervision.php" class="nav-item" data-page="nueva-supervision">
            <div class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14m-7-7h14"></path>
                </svg>
            </div>
            <span class="nav-text">Nueva Supervisión</span>
        </a>

        <!-- ⭐ Supervisiones - RUTA CORREGIDA -->
        <a href="<?php echo SITE_URL; ?>/modules/tecnico/supervisiones.php" class="nav-item" data-page="supervisiones">
            <div class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
            </div>
            <span class="nav-text">Supervisiones</span>
        </a>

        <?php if ($tieneInventario): ?>
        <!-- Divider -->
        <div class="nav-divider">
            <span>INVENTARIO</span>
        </div>

        <!-- ⭐ Inventario - RUTA CORREGIDA (solo técnicos completos) -->
        <a href="<?php echo SITE_URL; ?>/modules/tecnico/inventario.php" class="nav-item" data-page="inventario">
            <div class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path>
                </svg>
            </div>
            <span class="nav-text">Inventario</span>
            <span class="nav-badge">Completo</span>
        </a>
        <?php endif; ?>
    </nav>

    <!-- Logout Section -->
    <div class="sidebar-footer">
        <!-- User Profile -->
        <div class="user-profile">
            <div class="user-avatar">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <div class="user-info">
                <span class="user-name"><?php echo $_SESSION['usuario'] ?? 'Técnico'; ?></span>
                <span class="user-role"><?php echo $badgeText; ?></span>
            </div>
        </div>

        <!-- Logout Button -->
        <button class="logout-btn" id="logoutBtn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Cerrar Sesión</span>
        </button>
    </div>
</aside>

<!-- Overlay para móvil -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Mobile Toggle Button -->
<button class="mobile-toggle" id="mobileToggle">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
</button>