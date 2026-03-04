<?php
// sidebar.php
if (!function_exists('esAdmin')) {
    if (file_exists('auth.php')) {
        require_once 'auth.php';
    } else {
        function esAdmin() {
            return isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin';
        }
    }
}
$current_page = basename($_SERVER['PHP_SELF']);

// Iniciar sesií³n de forma segura por si no estí¡ (aunque auth ya lo hace)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="col-lg-2 sidebar d-flex flex-column" id="sidebar">
    <div class="logo text-center py-2" style="border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
        <img src="logo-congreso.jpg" alt="Congreso de Guatemala" style="max-height: 55px; width: auto; margin-bottom: 0.25rem;" onerror="this.style.display='none'">
        <h5 class="mb-0 fs-6 fw-bold">Congreso de la Republica</h5>
        <small class="text-muted d-block mt-1" style="font-size: 0.75rem; line-height: 1;">Sistema de Votaciones</small>
    </div>
    
    <nav class="nav flex-column flex-nowrap mt-1 flex-grow-1" style="overflow-x: hidden;">
        <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="index.php">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link <?php echo $current_page == 'eventos.php' ? 'active' : ''; ?>" href="eventos.php">
            <i class="bi bi-calendar-event"></i> Eventos
        </a>
        <a class="nav-link <?php echo $current_page == 'congresistas.php' ? 'active' : ''; ?>" href="congresistas.php">
            <i class="bi bi-people"></i> Congresistas
        </a>
        <a class="nav-link <?php echo $current_page == 'bloques.php' ? 'active' : ''; ?>" href="bloques.php">
            <i class="bi bi-diagram-3"></i> Bloques
        </a>
        <a class="nav-link <?php echo $current_page == 'estadisticas.php' ? 'active' : ''; ?>" href="estadisticas.php">
            <i class="bi bi-bar-chart"></i> Estadí­sticas
        </a>
        
        <?php if (esAdmin()): ?>
            <a class="nav-link <?php echo $current_page == 'cargar.php' ? 'active' : ''; ?>" href="cargar.php">
                <i class="bi bi-upload"></i> Cargar PDF
            </a>
            <a class="nav-link <?php echo $current_page == 'usuarios.php' ? 'active' : ''; ?>" href="usuarios.php">
                <i class="bi bi-person-gear"></i> Usuarios
            </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer mt-auto pb-2">
        <hr style="border-color: rgba(255,255,255,0.1); margin: 0.25rem 1rem;">
        <div class="px-2 py-1">
            <div class="text-truncate d-flex align-items-center mb-1 user-select-none" title="<?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? 'Usuario invitado'); ?>" style="color: rgba(255,255,255,0.8); font-size: 0.8rem;">
                <i class="bi bi-person-circle me-1 fs-6"></i>
                <span class="text-truncate"><?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? 'Usuario'); ?></span>
            </div>
            <div class="d-flex align-items-center justify-content-between px-1">
                <span class="badge bg-<?php echo esAdmin() ? 'danger' : 'info'; ?> rounded-pill" style="font-size: 0.7rem;">
                    <?php echo ucfirst($_SESSION['tipo_usuario'] ?? 'Invitado'); ?>
                </span>
                <?php if(isset($_SESSION['usuario_id'])): ?>
                <a class="text-danger p-1 border-0 bg-transparent text-decoration-none" href="logout.php" title="Cerrar Sesií³n">
                    <i class="bi bi-box-arrow-right fs-5"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

