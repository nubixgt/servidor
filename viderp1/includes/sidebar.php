<?php
/**
 * Sidebar Component - VIDER
 * Uso: incluir en cada página y definir $currentPage antes de incluir
 * Ejemplo: $currentPage = 'datos';
 */

// Incluir autenticación si no está incluida
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/auth.php';
}

// Obtener información del usuario
$currentUserInfo = getCurrentUser();

// Definir items del menú
$menuItems = [
    'index' => ['icon' => 'fa-chart-pie', 'label' => 'Dashboard', 'file' => 'index.php'],
    'mapa' => ['icon' => 'fa-map-marked-alt', 'label' => 'Mapa', 'file' => 'mapa.php'],
    'datos' => ['icon' => 'fa-database', 'label' => 'Datos', 'file' => 'datos.php'],
    'tobanik' => ['icon' => 'fa-handshake', 'label' => 'TOBANIK', 'file' => 'tobanik.php'],
    'importar' => ['icon' => 'fa-file-import', 'label' => 'Importar', 'file' => 'importar.php', 'tecnico_only' => true],
    'reportes' => ['icon' => 'fa-file-pdf', 'label' => 'Reportes', 'file' => 'reportes.php'],
    'historial' => ['icon' => 'fa-history', 'label' => 'Historial', 'file' => 'historial.php'],
    'usuarios' => ['icon' => 'fa-users-cog', 'label' => 'Usuarios', 'file' => 'usuarios.php', 'admin_only' => true],
];

// Asegurar que $currentPage está definido
if (!isset($currentPage)) {
    $currentPage = '';
}

// Función para obtener iniciales
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper(substr($word, 0, 1));
            if (strlen($initials) >= 2) break;
        }
    }
    return $initials ?: 'U';
}

// Función para obtener color de rol
function getRoleBadgeClass($rol) {
    switch ($rol) {
        case 'admin': return 'role-admin';
        case 'tecnico': return 'role-tecnico';
        default: return 'role-tecnico';
    }
}

// Función para obtener nombre del rol
function getRoleName($rol) {
    switch ($rol) {
        case 'admin': return 'Administrador';
        case 'tecnico': return 'Técnico';
        default: return 'Técnico';
    }
}
?>

<!-- Mobile Menu Toggle -->
<button class="menu-toggle" id="menu-toggle" aria-label="Abrir menú">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <!-- Sidebar Close Button -->
    <button class="sidebar-close" id="sidebar-close" aria-label="Cerrar menú">
        <i class="fas fa-times"></i>
    </button>

    <div class="sidebar-header">
        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-seedling"></i>
            </div>
            <div class="logo-text">
                <span class="logo-title">VIDER</span>
                <span class="logo-subtitle">MAGA Guatemala</span>
            </div>
        </div>
    </div>

    <?php if ($currentUserInfo): ?>
    <!-- User Profile Section -->
    <div class="user-profile-section">
        <div class="user-profile-top">
            <div class="user-avatar">
                <span class="avatar-initials"><?= getInitials($currentUserInfo['nombre_completo'] ?: $currentUserInfo['username']) ?></span>
            </div>
            <div class="user-info">
                <span class="user-name"><?= htmlspecialchars($currentUserInfo['nombre_completo'] ?: $currentUserInfo['username']) ?></span>
            </div>
        </div>
        <div class="user-profile-bottom">
            <span class="user-role <?= getRoleBadgeClass($currentUserInfo['rol']) ?>"><?= getRoleName($currentUserInfo['rol']) ?></span>
            <a href="logout.php" class="logout-btn" title="Cerrar Sesión">
                <i class="fas fa-sign-out-alt"></i> Salir
            </a>
        </div>
    </div>
    <?php endif; ?>

    <nav class="sidebar-nav">
        <?php foreach ($menuItems as $key => $item): 
            // Verificar permisos según rol
            if (isset($item['admin_only']) && $item['admin_only'] && !isAdmin()) continue;
            if (isset($item['tecnico_only']) && $item['tecnico_only'] && !isTecnico()) continue;
        ?>
            <a href="<?= $item['file'] ?>" class="nav-item <?= $currentPage === $key ? 'active' : '' ?>">
                <i class="fas <?= $item['icon'] ?>"></i>
                <span><?= $item['label'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="escudo-guatemala">
            <img src="MagaLogo.png" alt="MAGA Guatemala">
        </div>

        <!-- Theme Toggle -->
        <div class="theme-toggle-wrapper">
            <i class="fas fa-moon theme-icon" id="dark-icon"></i>
            <div class="theme-toggle" id="theme-toggle" title="Cambiar tema">
                <div class="theme-toggle-slider"></div>
            </div>
            <i class="fas fa-sun theme-icon" id="light-icon"></i>
        </div>
    </div>
</aside>

<style>
/* =====================================================
   CORRECCIÓN BUG DE SCROLL EN SIDEBAR
   ===================================================== */

/* Forzar que el sidebar no tenga scroll horizontal */
.sidebar {
    overflow-x: hidden !important;
    overflow-y: auto !important;
    scrollbar-width: none !important;
    -ms-overflow-style: none !important;
}

.sidebar::-webkit-scrollbar {
    display: none !important;
    width: 0 !important;
}

/* Contenedor de navegación sin desbordamiento */
.sidebar-nav {
    overflow: hidden !important;
    padding-right: 0 !important;
}

/* CORREGIR: Eliminar translateX en hover que causa el scroll */
.nav-item:hover {
    transform: none !important;
    background: rgba(74, 144, 217, 0.15);
    color: var(--text-primary);
}

/* Efecto hover alternativo sin mover el elemento */
.nav-item {
    position: relative;
    overflow: hidden;
}

.nav-item::after {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 3px;
    background: var(--accent, #4a90d9);
    transform: scaleY(0);
    transition: transform 0.2s ease;
}

.nav-item:hover::after {
    transform: scaleY(1);
}

.nav-item.active::after {
    transform: scaleY(1);
    background: var(--accent-light, #6bb3ff);
}

/* =====================================================
   USER PROFILE SECTION STYLES
   ===================================================== */
.user-profile-section {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 0.85rem;
    margin: 0.5rem 0.75rem 1rem;
    background: rgba(74, 144, 217, 0.1);
    border: 1px solid rgba(74, 144, 217, 0.2);
    border-radius: 12px;
    transition: all 0.3s ease;
}

.user-profile-section:hover {
    background: rgba(74, 144, 217, 0.15);
    border-color: rgba(74, 144, 217, 0.3);
}

.user-profile-top {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.user-avatar {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--primary, #1a3a5c), #60a5fa);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(74, 144, 217, 0.3);
}

.avatar-initials {
    color: white;
    font-weight: 700;
    font-size: 0.85rem;
    text-transform: uppercase;
}

.user-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    overflow: hidden;
}

.user-name {
    font-weight: 600;
    font-size: 0.8rem;
    color: var(--text-primary);
    line-height: 1.25;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.user-profile-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.user-role {
    font-size: 0.6rem;
    font-weight: 700;
    padding: 0.25rem 0.6rem;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.role-admin {
    background: rgba(239, 68, 68, 0.25);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.4);
}

.role-tecnico {
    background: rgba(74, 222, 128, 0.25);
    color: #4ade80;
    border: 1px solid rgba(74, 222, 128, 0.4);
}

.logout-btn {
    padding: 0.3rem 0.6rem;
    border-radius: 6px;
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.3rem;
    color: #fca5a5;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.65rem;
    font-weight: 600;
}

.logout-btn:hover {
    background: rgba(239, 68, 68, 0.25);
    border-color: rgba(239, 68, 68, 0.4);
    color: #f87171;
}

.logout-btn i {
    font-size: 0.7rem;
}

/* =====================================================
   LIGHT THEME
   ===================================================== */
[data-theme="light"] .user-profile-section {
    background: rgba(74, 144, 217, 0.08);
    border-color: rgba(74, 144, 217, 0.15);
}

[data-theme="light"] .user-name {
    color: #1a3a5c;
}

[data-theme="light"] .role-admin {
    background: rgba(239, 68, 68, 0.15);
    color: #dc2626;
    border-color: rgba(239, 68, 68, 0.3);
}

[data-theme="light"] .role-tecnico {
    background: rgba(74, 222, 128, 0.15);
    color: #16a34a;
    border-color: rgba(74, 222, 128, 0.3);
}

[data-theme="light"] .logout-btn {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
    border-color: rgba(239, 68, 68, 0.2);
}

/* =====================================================
   RESPONSIVE
   ===================================================== */
@media (max-width: 768px) {
    .user-profile-section {
        margin: 0.5rem;
        padding: 0.6rem;
    }
    
    .user-avatar {
        width: 34px;
        height: 34px;
    }
    
    .avatar-initials {
        font-size: 0.75rem;
    }
    
    .user-name {
        font-size: 0.75rem;
    }
    
    .user-role {
        font-size: 0.55rem;
        padding: 0.2rem 0.4rem;
    }
    
    .logout-btn {
        font-size: 0.6rem;
        padding: 0.25rem 0.5rem;
    }
}
</style>