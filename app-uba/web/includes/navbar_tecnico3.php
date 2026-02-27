<!-- web/includes/navbar_tecnico3.php - Sidebar Lateral -->
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
        <a href="/AppUBA/web/modules/tecnico_3/dashboard.php"
            class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
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
                <span class="user-role">Técnico Emitir Dictamen</span>
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
                window.location.href = '/AppUBA/logout.php';
            }
        });
    }

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