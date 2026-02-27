<!-- web/includes/navbar_admin.php - Sidebar Lateral Moderno -->
<aside class="sidebar">
    <!-- Logo y Título -->
    <div class="sidebar-header">
        <div class="logo-container">
            <i class="fas fa-paw"></i>
        </div>
        <div class="logo-text">
            <h2>AppUBA</h2>
            <p>MAGA</p>
        </div>
    </div>

    <!-- Navegación Principal -->
    <nav class="sidebar-nav">
        <a href="/app-uba/web/modules/admin/dashboard.php"
            class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>

        <a href="/app-uba/web/modules/admin/noticias/index.php"
            class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], 'noticias') !== false ? 'active' : ''; ?>">
            <i class="fas fa-newspaper"></i>
            <span>Noticias</span>
        </a>

        <a href="/app-uba/web/modules/admin/servicios/index.php"
            class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], 'servicios') !== false ? 'active' : ''; ?>">
            <i class="fas fa-store"></i>
            <span>Servicios</span>
        </a>

        <!-- Áreas Técnicas con Submenu -->
        <div class="nav-item-group">
            <div class="nav-item nav-item-dropdown">
                <i class="fas fa-tasks"></i>
                <span>Áreas Técnicas</span>
                <i class="fas fa-chevron-down dropdown-icon"></i>
            </div>
            <div class="submenu">
                <a href="/app-uba/web/modules/admin/area_legal/index.php" class="submenu-item">
                    <i class="fas fa-balance-scale"></i>
                    <span>Área Legal</span>
                </a>
                <a href="/app-uba/web/modules/admin/area_tecnica/index.php" class="submenu-item">
                    <i class="fas fa-tools"></i>
                    <span>Área Técnica</span>
                </a>
                <a href="/app-uba/web/modules/admin/emitir_dictamen/index.php" class="submenu-item">
                    <i class="fas fa-file-signature"></i>
                    <span>Emitir Dictamen</span>
                </a>
                <a href="/app-uba/web/modules/admin/opinion_legal/index.php" class="submenu-item">
                    <i class="fas fa-gavel"></i>
                    <span>Opinión Legal</span>
                </a>
                <a href="/app-uba/web/modules/admin/resolucion_final/index.php" class="submenu-item">
                    <i class="fas fa-stamp"></i>
                    <span>Resolución Final</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Usuario y Logout -->
    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-details">
                <span class="user-name">
                    <?php echo obtenerNombreUsuario(); ?>
                </span>
                <span class="user-role">Administrador</span>
            </div>
        </div>
        <button class="btn-logout" onclick="cerrarSesion()">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar Sesión</span>
        </button>
    </div>
</aside>

<!-- Top Bar (Barra superior con información adicional) -->
<div class="topbar">
    <div class="topbar-left">
        <button class="btn-menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="breadcrumb">
            <i class="fas fa-home"></i>
            <span>/ Dashboard</span>
        </div>
    </div>
    <div class="topbar-right">
        <div class="topbar-date">
            <i class="fas fa-calendar-alt"></i>
            <span id="currentDate"></span>
        </div>
        <div class="topbar-time">
            <i class="fas fa-clock"></i>
            <span id="currentTime"></span>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
    // Función para cerrar sesión
    function cerrarSesion() {
        Swal.fire({
            title: '¿Cerrar sesión?',
            text: "¿Estás seguro de que deseas salir?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, salir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '/app-uba/logout.php';
            }
        });
    }

    // Toggle sidebar en móvil
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('active');
    }

    // Dropdown de Áreas Técnicas
    document.querySelector('.nav-item-dropdown').addEventListener('click', function () {
        this.parentElement.classList.toggle('active');
    });

    // Actualizar fecha y hora
    function updateDateTime() {
        const now = new Date();

        // Fecha
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const dateStr = now.toLocaleDateString('es-GT', options);
        document.getElementById('currentDate').textContent = dateStr;

        // Hora
        const timeStr = now.toLocaleTimeString('es-GT', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        document.getElementById('currentTime').textContent = timeStr;
    }

    // Actualizar cada segundo
    updateDateTime();
    setInterval(updateDateTime, 1000);
</script>