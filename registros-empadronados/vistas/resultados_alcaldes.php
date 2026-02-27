<?php
/**
 * vistas/resultados_alcaldes.php
 * Vista de Resultados Electorales - Alcaldes (Centros de Votación y Mesas)
 * Sistema SICO GT
 */

require_once '../config/db.php';
require_once '../includes/funciones.php';
require_once '../includes/permisos.php';

// Verificar autenticación y permisos
verificarAcceso();

if (!tienePermiso('ver_resultados_electorales')) {
    redirigir('dashboard.php?error=sin_permisos');
}

$pdo = obtenerConexion();
$rol = obtenerRolUsuario();
$departamento = obtenerDepartamentoUsuario();
$municipio = obtenerMunicipioUsuario();
$titulo = obtenerTituloSegunRol();

$tituloEspecifico = "Resultados Electorales - Alcaldes";
if ($rol === ROL_DIPUTADO) {
    $tituloEspecifico .= " - " . $departamento;
} elseif ($rol === ROL_ALCALDE) {
    $tituloEspecifico .= " - " . $municipio;
}

$iniciales = obtenerIniciales(obtenerNombreUsuario());
$nombreUsuario = obtenerNombreUsuario();
$rolUsuario = obtenerRolUsuario();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tituloEspecifico; ?> - SICO GT</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    
    <!-- AOS Animation -->
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/componentes.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <!-- DataTables Buttons CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

</head>
<body class="show-focus">

<div class="dashboard-wrapper">
    
    <!-- Sidebar -->
    <?php include '../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
                    <i class="bi bi-list"></i>
                </button>
                <div class="page-title-wrapper">
                    <h1 class="page-title"><?php echo $tituloEspecifico; ?></h1>
                    <p class="page-subtitle">
                        <?php 
                        if ($rol === ROL_ADMINISTRADOR || $rol === ROL_PRESIDENTE) {
                            echo "Centros de Votación y Mesas Electorales";
                        } elseif ($rol === ROL_DIPUTADO) {
                            echo "Centros de Votación - " . $departamento;
                        } else {
                            echo "Centros de Votación - " . $municipio;
                        }
                        ?>
                    </p>
                </div>
            </div>
            
            <div class="topbar-right">
                <div class="topbar-actions">
                    <button class="btn-icon" title="Actualizar" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
                
                <div class="user-profile">
                    <div class="user-avatar"><?php echo $iniciales; ?></div>
                    <div class="user-info">
                        <div class="user-name"><?php echo $nombreUsuario; ?></div>
                        <div class="user-role"><?php echo $rolUsuario; ?></div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Content -->
        <div class="content">
            
            <!-- Stats Cards -->
            <div class="row g-3 mb-4" data-aos="fade-up">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon orange">
                                <i class="bi bi-building"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Centros de Votación</div>
                            <div class="stat-value" id="totalCentros">0</div>
                            <div class="stat-change positive">
                                <i class="bi bi-geo-alt"></i>
                                <span>Ubicaciones</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon blue">
                                <i class="bi bi-grid-3x3"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Total de Mesas</div>
                            <div class="stat-value" id="totalMesas">0</div>
                            <div class="stat-change positive">
                                <i class="bi bi-check-circle"></i>
                                <span>Activas</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon green">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Total Votantes</div>
                            <div class="stat-value" id="totalVotantes">0</div>
                            <div class="stat-change positive">
                                <i class="bi bi-person-check"></i>
                                <span>Emitieron voto</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon purple">
                                <i class="bi bi-map"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Municipios</div>
                            <div class="stat-value" id="totalMunicipios">0</div>
                            <div class="stat-change positive">
                                <i class="bi bi-pin-map"></i>
                                <span>Cubiertos</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filtros -->
            <?php if ($rol === ROL_ADMINISTRADOR || $rol === ROL_PRESIDENTE): ?>
            <div class="row mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div>
                                    <label class="text-sm text-secondary mb-1">Departamento:</label>
                                    <select id="filtroDepartamento" class="form-select form-select-sm">
                                        <option value="">Todos</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-sm text-secondary mb-1">Municipio:</label>
                                    <select id="filtroMunicipio" class="form-select form-select-sm">
                                        <option value="">Todos</option>
                                    </select>
                                </div>
                                <div class="ms-auto">
                                    <button class="btn btn-sm btn-secondary" onclick="limpiarFiltros()">
                                        <i class="bi bi-x-circle"></i> Limpiar Filtros
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Tabla de Centros y Mesas -->
            <div class="row" data-aos="fade-up" data-aos-delay="200">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title m-0">
                                <i class="bi bi-table"></i>
                                Centros de Votación y Mesas Electorales
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tablaCentros" class="table table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Departamento</th>
                                            <th>Municipio</th>
                                            <th>Centro de Votación</th>
                                            <th>Mesa</th>
                                            <th>Votos Emitidos</th>
                                            <th>Padrón</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaCentrosBody">
                                        <!-- Se llena dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        
    </main>
    
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>


<!-- DataTables Buttons + deps -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>


<!-- AOS Animation -->
<script src="https://unpkg.com/aos@next/dist/aos.js"></script>

<!-- Custom Scripts -->
<script src="../assets/js/dashboard_interactivo.js"></script>
<script src="../assets/js/resultados_alcaldes.js"></script>

<script>
// Inicializar AOS
AOS.init({
    duration: 800,
    easing: 'ease-in-out',
    once: true,
    offset: 50
});

// Confirmar cierre de sesión
function confirmarCerrarSesion(event) {
    event.preventDefault();
    if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        window.location.href = '../cerrar_sesion.php';
    }
}

function limpiarFiltros() {
    document.getElementById('filtroDepartamento').value = '';
    document.getElementById('filtroMunicipio').value = '';
    cargarDatos();
}
</script>

</body>
</html>