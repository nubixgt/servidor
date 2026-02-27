<?php
// includes/navbar.php
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon">
                <img src="assets/images/Logo Ceiba-2.png" alt="Logo Ceiba" class="navbar-logo-image">
            </div>
            <div class="sidebar-logo-text">
                <h2>Lotificación</h2>
                <p><?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></p>
                <small>Administrador</small>
            </div>
        </div>
    </div>

    <div class="sidebar-menu">
        <a href="formulario.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'formulario.php' ? 'active' : ''; ?>">
            <span class="menu-icon">📊</span>
            <span>Dashboard</span>
        </a>
        
        <a href="formulario.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'formulario.php' ? 'active' : ''; ?>">
            <span class="menu-icon">➕</span>
            <span>Nuevo Registro</span>
        </a>
        
        <a href="ver_registros.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'ver_registros.php' ? 'active' : ''; ?>">
            <span class="menu-icon">📋</span>
            <span>Ver Registros</span>
        </a>
    </div>

    <div class="sidebar-footer">
        <button class="menu-item logout" id="btnCerrarSesion">
            <span class="menu-icon">🚪</span>
            <span>Cerrar Sesión</span>
        </button>
    </div>
</aside>

<!-- Botón hamburguesa para móvil -->
<button class="menu-toggle" id="menuToggle" onclick="toggleSidebar()">☰</button>

<!-- Overlay para cerrar menú en móvil -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
// ⭐ Toggle sidebar en móvil - MEJORADO
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const body = document.body;
    
    const isActive = sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
    
    // ⭐ Prevenir scroll del body cuando el menú está abierto
    if (isActive) {
        body.style.overflow = 'hidden';
    } else {
        body.style.overflow = '';
    }
}

// ⭐ Inicializar eventos cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('sidebarOverlay');
    const sidebar = document.getElementById('sidebar');
    
    // Cerrar sidebar al hacer clic en el overlay
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            // Solo cerrar si el overlay está activo
            if (this.classList.contains('active')) {
                toggleSidebar();
            }
        });
    }
    
    // ⭐ Cerrar sidebar después de hacer clic en un link en móvil
    const menuItems = document.querySelectorAll('.menu-item:not(.logout)');
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            if (window.innerWidth <= 1024 && sidebar.classList.contains('active')) {
                // Pequeño delay para que se vea la transición
                setTimeout(() => {
                    toggleSidebar();
                }, 150);
            }
        });
    });
    
    // ⭐ Cerrar sidebar al cambiar orientación en móvil
    window.addEventListener('orientationchange', function() {
        if (window.innerWidth <= 1024 && sidebar.classList.contains('active')) {
            setTimeout(() => {
                toggleSidebar();
            }, 200);
        }
    });
    
    // ⭐ Cerrar sidebar al redimensionar a desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024 && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});
</script>