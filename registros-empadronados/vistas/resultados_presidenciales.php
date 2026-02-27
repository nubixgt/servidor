<?php
/**
 * vistas/resultados_electorales.php
 * Vista de Resultados Electorales
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

// Determinar el título específico para esta página
$tituloEspecifico = "Resultados Electorales - Presidenciable";
if ($rol === ROL_DIPUTADO) {
    $tituloEspecifico = "Resultados Electorales - Presidenciable " . $departamento;
} elseif ($rol === ROL_ALCALDE) {
    $tituloEspecifico = "Resultados Electorales - Presidenciable " . $municipio;
}
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
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
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
                            echo "Vista completa de todos los resultados electorales";
                        } elseif ($rol === ROL_DIPUTADO) {
                            echo "Resultados del departamento de " . $departamento;
                        } else {
                            echo "Resultados del municipio de " . $municipio;
                        }
                        ?>
                    </p>
                </div>
            </div>
            
            <div class="topbar-right">
                <div class="topbar-actions">
                    <?php if (tienePermiso('exportar_resultados_electorales')): ?>
                    <button class="btn-icon" title="Exportar datos" onclick="exportarDatos()">
                        <i class="bi bi-download"></i>
                    </button>
                    <?php endif; ?>
                    
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
                            <div class="stat-icon blue">
                                <i class="bi bi-inbox"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Total Votos Emitidos</div>
                            <div class="stat-value" id="totalEmitidos">0</div>
                            <div class="stat-change positive">
                                <i class="bi bi-arrow-up"></i>
                                <span>Actualizados</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon green">
                                <i class="bi bi-percent"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Participación Electoral</div>
                            <div class="stat-value" id="porcentajeParticipacion">0%</div>
                            <div class="stat-change positive">
                                <i class="bi bi-graph-up"></i>
                                <span>Del padrón</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon purple">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Votos Válidos</div>
                            <div class="stat-value" id="totalValidos">0</div>
                            <div class="stat-change positive">
                                <i class="bi bi-check2"></i>
                                <span>Contabilizados</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon orange">
                                <i class="bi bi-x-circle"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Votos Nulos</div>
                            <div class="stat-value" id="totalNulos">0</div>
                            <div class="stat-change negative">
                                <i class="bi bi-x"></i>
                                <span>Descartados</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Gráficas -->
            <div class="row g-4 mb-4">
                <!-- Gráfica Top Partidos -->
                <div class="col-12 col-lg-8" data-aos="fade-up" data-aos-delay="100">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="bi bi-bar-chart-fill"></i>
                                Top 10 Partidos Políticos
                            </h5>
                        </div>
                        <div class="card-body">
                            <div style="height: 350px; position: relative;">
                                <canvas id="chartTopPartidos"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Gráfica Distribución de Votos -->
                <div class="col-12 col-lg-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="bi bi-pie-chart-fill"></i>
                                Distribución de Votos
                            </h5>
                        </div>
                        <div class="card-body">
                            <div style="height: 350px; position: relative;">
                                <canvas id="chartVotos"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabla de Resultados -->
            <div class="row" data-aos="fade-up" data-aos-delay="300">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <h5 class="card-title m-0">
                                <i class="bi bi-table"></i>
                                <?php 
                                if ($rol === ROL_ADMINISTRADOR || $rol === ROL_PRESIDENTE) {
                                    echo "Resultados por Departamento";
                                } elseif ($rol === ROL_DIPUTADO) {
                                    echo "Resultados por Municipio - " . $departamento;
                                } else {
                                    echo "Resultados de " . $municipio;
                                }
                                ?>
                            </h5>
                            
                            <?php if ($rol === ROL_ADMINISTRADOR || $rol === ROL_PRESIDENTE): ?>
                            <div class="d-flex gap-2 align-items-center">
                                <label class="text-sm text-secondary mb-0">Filtrar:</label>
                                <select id="filtroDepartamento" class="form-select form-select-sm" style="width: auto;">
                                    <option value="">Todos los departamentos</option>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tablaResultados" class="table table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th><?php echo ($rol === ROL_ALCALDE) ? 'Municipio' : (($rol === ROL_DIPUTADO) ? 'Municipio' : 'Departamento'); ?></th>
                                            <th>Padrón</th>
                                            <th>Emitidos</th>
                                            <th>Válidos</th>
                                            <th>Nulos</th>
                                            <th>Blanco</th>
                                            <th>Participación</th>
                                            <th><?php echo ($rol === ROL_ALCALDE) ? 'Mesas' : (($rol === ROL_DIPUTADO) ? 'Mesas' : 'Municipios'); ?></th>
                                            <?php if ($rol === ROL_ADMINISTRADOR || $rol === ROL_PRESIDENTE || $rol === ROL_DIPUTADO): ?>
                                            <th>Acciones</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaResultadosBody">
                                        <!-- Se llena dinámicamente con JavaScript -->
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

<!-- AOS Animation -->
<script src="https://unpkg.com/aos@next/dist/aos.js"></script>

<!-- Feather Icons -->
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>

<!-- Custom Scripts -->
<script src="../assets/js/dashboard_interactivo.js"></script>
<script src="../assets/js/resultados_electorales.js"></script>

<script>
// Inicializar AOS
AOS.init({
    duration: 800,
    easing: 'ease-in-out',
    once: true,
    offset: 50
});

// Inicializar Feather Icons
feather.replace();

// Función para confirmar cierre de sesión
function confirmarCerrarSesion(event) {
    event.preventDefault();
    
    if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        window.location.href = '../cerrar_sesion.php';
    }
}

// Función para exportar datos
function exportarDatos() {
    <?php if (tienePermiso('exportar_resultados_electorales')): ?>
    // Obtener parámetros según el rol
    let params = '';
    <?php if ($rol === ROL_DIPUTADO): ?>
    params = '?departamento=<?php echo urlencode($departamento); ?>';
    <?php elseif ($rol === ROL_ALCALDE): ?>
    params = '?departamento=<?php echo urlencode($departamento); ?>&municipio=<?php echo urlencode($municipio); ?>';
    <?php else: ?>
    const filtro = document.getElementById('filtroDepartamento');
    if (filtro && filtro.value) {
        params = '?departamento=' + encodeURIComponent(filtro.value);
    }
    <?php endif; ?>
    
    // Implementar exportación (puedes crear un endpoint específico)
    window.location.href = '../ajax/exportar_resultados.php' + params;
    <?php else: ?>
    alert('No tienes permisos para exportar datos');
    <?php endif; ?>
}

// Configuración específica según el rol
<?php if ($rol === ROL_DIPUTADO): ?>
// Para diputados: cargar solo su departamento
document.addEventListener('DOMContentLoaded', function() {
    // Las funciones en resultados_electorales.js ya manejan esto con los filtros del backend
    console.log('Vista de Diputado: <?php echo $departamento; ?>');
});
<?php elseif ($rol === ROL_ALCALDE): ?>
// Para alcaldes: cargar solo su municipio
document.addEventListener('DOMContentLoaded', function() {
    console.log('Vista de Alcalde: <?php echo $municipio; ?>, <?php echo $departamento; ?>');
});
<?php endif; ?>
</script>

</body>
</html>