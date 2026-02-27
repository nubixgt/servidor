<?php
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Obtener la página actual para marcar el menú activo
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar Navigation -->
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <div class="logo-icon">
                <i class="fa-solid fa-seedling"></i>
            </div>
            <h2 class="logo-text">OIRSA</h2>
        </div>
    </div>

    <nav class="sidebar-nav">
        <p class="nav-section-title">Menú Principal</p>
        <ul class="nav-menu">
            <li>
                <a href="dashboard.php"
                    class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="contratos.php"
                    class="nav-link <?php echo ($current_page == 'contratos.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-file-contract"></i>
                    <span>Contratos</span>
                </a>
            </li>
            <li>
                <a href="formulario.php"
                    class="nav-link <?php echo ($current_page == 'formulario.php' || $current_page == 'editar_contrato.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-plus-circle"></i>
                    <span>Nuevo Contrato</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar">
                <i class="fa-solid fa-user-circle"></i>
            </div>
            <div class="user-details">
                <p class="user-name"><?php echo htmlspecialchars($_SESSION['usuario']); ?></p>
                <p class="user-role">Administrador</p>
            </div>
        </div>
        <button onclick="confirmarLogout()" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Cerrar Sesión</span>
        </button>
    </div>
</aside>

<script>
    function confirmarLogout() {
        Swal.fire({
            title: '¿Cerrar sesión?',
            text: '¿Estás seguro de que deseas salir del sistema?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1A73E8',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cerrar sesión',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../../logout.php?confirm=true';
            }
        });
    }
</script>