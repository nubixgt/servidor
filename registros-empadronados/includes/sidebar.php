<?php
/**
 * includes/sidebar.php 
 * Componente Sidebar - Menú lateral del sistema
 * Diseño Premium Dorado/Blanco
 */

$rutaBase = '';
$currentFile = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

if ($currentDir === 'vistas') {
    $rutaBase = '../';
} elseif ($currentDir === 'admin') {
    $rutaBase = '../';
} else {
    $rutaBase = '';
}

$paginaActual   = basename($_SERVER['PHP_SELF']);
$iniciales      = obtenerIniciales(obtenerNombreUsuario());
$nombreUsuario  = obtenerNombreUsuario();
$rolUsuario     = obtenerRolUsuario();
?>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar" role="navigation" aria-label="Menú lateral">
  <div class="sidebar-header">
    <i class="bi bi-bar-chart-fill sidebar-logo"></i>
    <h5 class="sidebar-title">SICO GT</h5>
    <p class="sidebar-subtitle">Sistema de Control de Guatemala</p>
  </div>

  <nav class="sidebar-nav">
    <ul class="nav flex-column">
      <li class="nav-item">
        <a href="<?php echo $rutaBase; ?>vistas/dashboard.php"
           class="nav-link <?php echo $paginaActual === 'dashboard.php' ? 'active' : ''; ?>"
           aria-current="<?php echo $paginaActual === 'dashboard.php' ? 'page' : 'false'; ?>">
           <i class="bi bi-speedometer2"></i>
           <span>Dashboard</span>
        </a>
      </li>

      <?php if (tienePermiso('ver_departamentos')): ?>
      <li class="nav-item">
        <a href="<?php echo $rutaBase; ?>vistas/departamentos.php"
           class="nav-link <?php echo $paginaActual === 'departamentos.php' ? 'active' : ''; ?>"
           aria-current="<?php echo $paginaActual === 'departamentos.php' ? 'page' : 'false'; ?>">
           <i class="bi bi-map"></i>
           <span>Departamentos</span>
        </a>
      </li>
      <?php endif; ?>

      <?php if (tienePermiso('ver_municipios')): ?>
      <li class="nav-item">
        <a href="<?php echo $rutaBase; ?>vistas/municipios.php"
           class="nav-link <?php echo $paginaActual === 'municipios.php' ? 'active' : ''; ?>"
           aria-current="<?php echo $paginaActual === 'municipios.php' ? 'page' : 'false'; ?>">
           <i class="bi bi-geo-alt"></i>
           <span>Municipios</span>
        </a>
      </li>
      <?php endif; ?>

      <?php if (tienePermiso('ver_resultados_electorales')): ?>
      <li class="nav-item">
        <a href="#" 
          class="nav-link <?php echo in_array($paginaActual, ['resultados_presidenciales.php', 'resultados_diputaciones.php', 'resultados_alcaldes.php', 'detalle_departamento.php']) ? 'active' : ''; ?>"
          data-bs-toggle="collapse" 
          data-bs-target="#submenuResultados" 
          aria-expanded="<?php echo in_array($paginaActual, ['resultados_presidenciales.php', 'resultados_diputaciones.php', 'resultados_alcaldes.php', 'detalle_departamento.php']) ? 'true' : 'false'; ?>">
          <i class="bi bi-bar-chart-line"></i>
          <span>Resultados Electorales</span>
          <i class="bi bi-chevron-down ms-auto submenu-arrow"></i>
        </a>
        
        <!-- Submenú -->
        <ul class="collapse submenu <?php echo in_array($paginaActual, ['resultados_presidenciales.php', 'resultados_diputaciones.php', 'resultados_alcaldes.php', 'detalle_departamento.php']) ? 'show' : ''; ?>" 
            id="submenuResultados">
          <li class="nav-item">
            <a href="<?php echo $rutaBase; ?>vistas/resultados_presidenciales.php"
              class="nav-link submenu-link <?php echo $paginaActual === 'resultados_presidenciales.php' ? 'active' : ''; ?>">
              <i class="bi bi-person-badge"></i>
              <span>Presidenciable</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?php echo $rutaBase; ?>vistas/resultados_diputaciones.php"
              class="nav-link submenu-link <?php echo $paginaActual === 'resultados_diputaciones.php' ? 'active' : ''; ?>">
              <i class="bi bi-people-fill"></i>
              <span>Diputaciones</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?php echo $rutaBase; ?>vistas/resultados_alcaldes.php"
              class="nav-link submenu-link <?php echo $paginaActual === 'resultados_alcaldes.php' ? 'active' : ''; ?>">
              <i class="bi bi-building"></i>
              <span>Alcaldes</span>
            </a>
          </li>
        </ul>
      </li>
      <?php endif; ?>

      <li class="nav-item">
        <a href="<?php echo $rutaBase; ?>vistas/perfil.php"
           class="nav-link <?php echo $paginaActual === 'perfil.php' ? 'active' : ''; ?>"
           aria-current="<?php echo $paginaActual === 'perfil.php' ? 'page' : 'false'; ?>">
           <i class="bi bi-person-circle"></i>
           <span>Mi Perfil</span>
        </a>
      </li>

      <?php if (tienePermiso('gestionar_usuarios')): ?>
      <li class="nav-item">
        <a href="<?php echo $rutaBase; ?>admin/usuarios.php"
           class="nav-link <?php echo $paginaActual === 'usuarios.php' ? 'active' : ''; ?>"
           aria-current="<?php echo $paginaActual === 'usuarios.php' ? 'page' : 'false'; ?>">
           <i class="bi bi-people"></i>
           <span>Usuarios</span>
        </a>
      </li>
      <?php endif; ?>

      <?php if (tienePermiso('ver_logs')): ?>
      <li class="nav-item">
        <a href="<?php echo $rutaBase; ?>admin/logs.php"
           class="nav-link <?php echo $paginaActual === 'logs.php' ? 'active' : ''; ?>"
           aria-current="<?php echo $paginaActual === 'logs.php' ? 'page' : 'false'; ?>">
           <i class="bi bi-clock-history"></i>
           <span>Logs de Actividad</span>
        </a>
      </li>
      <?php endif; ?>

      <li class="nav-item mt-4">
        <a href="#" 
           onclick="confirmarCerrarSesion?.(event) || confirmarCerrarSesion(event)"
           class="nav-link"
           role="button">
           <i class="bi bi-box-arrow-right"></i>
           <span>Cerrar Sesión</span>
        </a>
      </li>
    </ul>
  </nav>
</aside>

<!-- Overlay para cerrar sidebar en móviles -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<script>
// Toggle sidebar para móviles
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  
  sidebar.classList.toggle('active');
  overlay.classList.toggle('active');
}

// Cerrar sidebar al hacer click en un enlace (solo en móvil)
document.addEventListener('DOMContentLoaded', function() {
  const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
  
  sidebarLinks.forEach(link => {
    link.addEventListener('click', function() {
      if (window.innerWidth <= 992) {
        toggleSidebar();
      }
    });
  });
  
  // Toggle desde el botón del topbar
  const sidebarToggle = document.getElementById('sidebarToggle');
  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', toggleSidebar);
  }
});
</script>
