<?php
/**
 * vistas/detalle_departamento.php
 * Detalle de resultados por municipio de un departamento
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
$departamentoUsuario = obtenerDepartamentoUsuario();
$municipioUsuario = obtenerMunicipioUsuario();

// Obtener departamento de la URL
$departamento = $_GET['departamento'] ?? '';

if (empty($departamento)) {
    redirigir('resultados_electorales.php?error=departamento_invalido');
}

// Verificar permisos según rol
if ($rol === ROL_DIPUTADO && $departamento !== $departamentoUsuario) {
    redirigir('resultados_electorales.php?error=sin_permisos');
}

if ($rol === ROL_ALCALDE) {
    redirigir('resultados_electorales.php?error=sin_permisos');
}

$titulo = "Detalle de Resultados - " . $departamento;
$iniciales = obtenerIniciales(obtenerNombreUsuario());
$nombreUsuario = obtenerNombreUsuario();
$rolUsuario = obtenerRolUsuario();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?> - SICO GT</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    
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
                    <h1 class="page-title" id="pageTitle"><?php echo $titulo; ?></h1>
                    <p class="page-subtitle">
                        <a href="resultados_presidenciales.php" class="text-primary">
                            <i class="bi bi-arrow-left"></i> Volver a Resultados Electorales
                        </a>
                    </p>
                </div>
            </div>
            
            <div class="topbar-right">
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
            
            <!-- Filtro de Departamento -->
            <div class="row mb-4" data-aos="fade-up">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div>
                                    <h6 class="mb-1 text-secondary">
                                        <i class="bi bi-funnel"></i> Filtrar por Departamento
                                    </h6>
                                    <p class="mb-0 text-muted" style="font-size: 0.875rem;">
                                        Selecciona un departamento para ver sus resultados
                                    </p>
                                </div>
                                <div>
                                    <select id="filtroDepartamento" class="form-select" style="min-width: 250px;">
                                        <option value="">Cargando departamentos...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="row g-3 mb-4" data-aos="fade-up" data-aos-delay="50">
                <!-- 1. TOTAL DE VOTOS -->
                <div class="col-12 col-sm-6 col-lg-4 col-xl-2-4">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon green">
                                <i class="bi bi-calculator"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Total de Votos</div>
                            <div class="stat-value" id="totalVotosCard">0</div>
                            <div class="stat-change positive">
                                <i class="bi bi-plus-circle"></i>
                                <span>Contabilizados</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 2. TOTAL VOTOS EMITIDOS -->
                <div class="col-12 col-sm-6 col-lg-4 col-xl-2-4">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon blue">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Total Votos Emitidos</div>
                            <div class="stat-value" id="totalEmitidos">0</div>
                            <div class="stat-change positive">
                                <i class="bi bi-check-circle"></i>
                                <span>100% del total</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 3. VOTOS VÁLIDOS -->
                <div class="col-12 col-sm-6 col-lg-4 col-xl-2-4">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon purple">
                                <i class="bi bi-person-check"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Votos Válidos</div>
                            <div class="stat-value" id="totalValidos">0</div>
                            <div class="stat-change positive">
                                <i class="bi bi-arrow-up"></i>
                                <span id="porcentajeValidos">0%</span> del total
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 4. TOTAL PADRÓN 23 -->
                <div class="col-12 col-sm-6 col-lg-4 col-xl-2-4">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon gold">
                                <i class="bi bi-person"></i>
                            </div>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Total Padrón 23</div>
                            <div class="stat-value" id="totalPadron23Card">0</div>
                            <div class="stat-change positive">
                                <i class="bi bi-info-circle"></i>
                                <span>Registrados</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 5. CENTROS DE VOTACIÓN -->
                <div class="col-12 col-sm-6 col-lg-4 col-xl-2-4">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon orange">
                                <i class="bi bi-pin-map"></i>
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
            </div>
            
            <!-- Top 5 Partidos -->
            <div class="row mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="bi bi-trophy-fill"></i>
                                Top 5 Partidos Políticos
                            </h5>
                        </div>
                        <div class="card-body">
                            <div style="height: 300px; position: relative;">
                                <canvas id="chartTopPartidos"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Gráfica Comparativa -->
            <div class="row mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <h5 class="card-title m-0">
                                <i class="bi bi-bar-chart-line-fill"></i>
                                Comparativa por Municipio
                            </h5>
                            
                            <!-- Filtro de Partido -->
                            <div class="d-flex align-items-center gap-2">
                                <label class="text-sm text-secondary mb-0">Filtrar por partido:</label>
                                <select id="filtroPartido" class="form-select form-select-sm" style="min-width: 200px;">
                                    <option value="">Todos (Votos Válidos)</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <div style="height: 500px; position: relative;">
                                <canvas id="chartComparativa"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabla de Datos -->
            <div class="row" data-aos="fade-up" data-aos-delay="300">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title m-0">
                                <i class="bi bi-table"></i>
                                Resultados Detallados por Municipio
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tablaDetalle" class="table table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Municipio</th>
                                            <th>Votos</th>
                                            <th>Padrón 23</th>
                                            <th>Padrón 25</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaDetalleBody">
                                        <!-- Se llena dinámicamente -->
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-primary">
                                            <th><strong>Total</strong></th>
                                            <th id="totalVotos"><strong>0</strong></th>
                                            <th id="totalPadron23"><strong>0</strong></th>
                                            <th id="totalPadron25"><strong>0</strong></th>
                                        </tr>
                                    </tfoot>
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

<!-- AOS Animation -->
<script src="https://unpkg.com/aos@next/dist/aos.js"></script>

<!-- Custom Scripts -->
<script src="../assets/js/dashboard_interactivo.js"></script>

<script>
let departamentoActual = "<?php echo htmlspecialchars($departamento); ?>";
let chartComparativa = null;
let chartTopPartidos = null;
let tablaDetalle = null;

// Colores para partidos
const coloresPartidos = ['#d4a574', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'];

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    console.log('Cargando detalle de:', departamentoActual);
    AOS.init({ duration: 800, once: true });
    
    cargarListaDepartamentos();
    cargarListaPartidos();
    cargarDatosDepartamento(departamentoActual);
    
    // Event listener para cambio de departamento
    document.getElementById('filtroDepartamento').addEventListener('change', function() {
        const nuevoDepartamento = this.value;
        if (nuevoDepartamento) {
            departamentoActual = nuevoDepartamento;
            
            // Actualizar título
            document.getElementById('pageTitle').textContent = 'Detalle de Resultados - ' + nuevoDepartamento;
            
            // Actualizar URL sin recargar
            const newUrl = `detalle_departamento.php?departamento=${encodeURIComponent(nuevoDepartamento)}`;
            window.history.pushState({departamento: nuevoDepartamento}, '', newUrl);
            
            // Recargar datos
            cargarDatosDepartamento(nuevoDepartamento);
        }
    });
    
    // Event listener para cambio de partido
    document.getElementById('filtroPartido').addEventListener('change', function() {
        const partido = this.value;
        if (partido) {
            cargarDatosPartido(departamentoActual, partido);
        } else {
            cargarDatosMunicipios(departamentoActual);
        }
    });
});

/**
 * Cargar lista de departamentos
 */
function cargarListaDepartamentos() {
    fetch('../ajax/obtener_datos_detalle.php?accion=lista_departamentos')
        .then(response => response.json())
        .then(data => {
            console.log('Departamentos:', data);
            
            if (data.success) {
                const select = document.getElementById('filtroDepartamento');
                select.innerHTML = '<option value="">Seleccione un departamento</option>';
                
                data.data.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept;
                    option.textContent = dept;
                    if (dept === departamentoActual) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

/**
 * Cargar lista de partidos
 */
function cargarListaPartidos() {
    fetch('../ajax/obtener_datos_detalle.php?accion=lista_partidos')
        .then(response => response.json())
        .then(data => {
            console.log('Partidos:', data);
            
            if (data.success) {
                const select = document.getElementById('filtroPartido');
                
                data.data.forEach(partido => {
                    const option = document.createElement('option');
                    option.value = partido;
                    option.textContent = partido;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

/**
 * Cargar todos los datos de un departamento
 */
function cargarDatosDepartamento(departamento) {
    cargarEstadisticas(departamento);
    cargarTopPartidos(departamento);
    cargarDatosMunicipios(departamento);
}

/**
 * Cargar estadísticas generales
 */
function cargarEstadisticas(departamento) {
    fetch(`../ajax/obtener_datos_detalle.php?accion=estadisticas_departamento&departamento=${encodeURIComponent(departamento)}`)
        .then(response => response.json())
        .then(data => {
            console.log('Estadísticas:', data);
            
            if (data.success) {
                const stats = data.data;
                
                document.getElementById('totalEmitidos').textContent = formatearNumero(stats.total_emitidos || 0);
                document.getElementById('totalValidos').textContent = formatearNumero(stats.total_validos || 0);
                document.getElementById('totalPadron23Card').textContent = formatearNumero(stats.total_padron_23 || 0);
                document.getElementById('totalVotosCard').textContent = formatearNumero(stats.total_votos || 0);
                document.getElementById('totalCentros').textContent = formatearNumero(stats.total_centros_votacion || 0);
                document.getElementById('porcentajeValidos').textContent = (stats.porcentaje_validos || 0) + '%';
                
                animarNumeros();
            }
        })
        .catch(error => console.error('Error:', error));
}

/**
 * Cargar Top 5 Partidos
 */
function cargarTopPartidos(departamento) {
    fetch(`../ajax/obtener_datos_detalle.php?accion=top_partidos_departamento&departamento=${encodeURIComponent(departamento)}`)
        .then(response => response.json())
        .then(data => {
            console.log('Top partidos:', data);
            
            if (data.success) {
                crearGraficaTopPartidos(data.data);
            }
        })
        .catch(error => console.error('Error:', error));
}

/**
 * Crear gráfica Top 5 Partidos
 */
function crearGraficaTopPartidos(datos) {
    const ctx = document.getElementById('chartTopPartidos');
    if (!ctx) return;
    
    if (chartTopPartidos) {
        chartTopPartidos.destroy();
    }
    
    const labels = datos.map(d => d.partido);
    const votos = datos.map(d => d.votos);
    
    chartTopPartidos = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Votos',
                data: votos,
                backgroundColor: coloresPartidos,
                borderRadius: 8,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    callbacks: {
                        label: context => 'Votos: ' + formatearNumero(context.parsed.x)
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: 'rgba(212, 165, 116, 0.1)' },
                    ticks: {
                        callback: value => formatearNumero(value),
                        font: { size: 11 }
                    }
                },
                y: {
                    grid: { display: false },
                    ticks: {
                        font: { size: 12, weight: '600' },
                        color: '#1a1a1a'
                    }
                }
            }
        }
    });
}

/**
 * Cargar datos de municipios (votos válidos)
 */
function cargarDatosMunicipios(departamento) {
    fetch(`../ajax/obtener_datos_detalle.php?accion=datos_municipios_departamento&departamento=${encodeURIComponent(departamento)}`)
        .then(response => response.json())
        .then(data => {
            console.log('Datos municipios:', data);
            
            if (data.success) {
                crearGrafica(data.data, 'Votos Válidos');
                crearTabla(data.data);
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los datos');
        });
}

/**
 * Cargar datos de un partido específico
 */
function cargarDatosPartido(departamento, partido) {
    // Primero cargamos los datos generales
    fetch(`../ajax/obtener_datos_detalle.php?accion=datos_municipios_departamento&departamento=${encodeURIComponent(departamento)}`)
        .then(response => response.json())
        .then(datosGenerales => {
            if (!datosGenerales.success) {
                throw new Error('Error al cargar datos generales');
            }
            
            // Luego cargamos los datos del partido
            return fetch(`../ajax/obtener_datos_detalle.php?accion=votos_partido_por_municipio&departamento=${encodeURIComponent(departamento)}&partido=${encodeURIComponent(partido)}`)
                .then(response => response.json())
                .then(datosPartido => {
                    console.log('Datos combinados:', datosGenerales, datosPartido);
                    
                    if (datosPartido.success) {
                        // Combinar ambos conjuntos de datos
                        const datosCombinados = datosGenerales.data.map(item => {
                            const partidoData = datosPartido.data.find(p => p.municipio === item.municipio);
                            return {
                                municipio: item.municipio,
                                votos_validos: parseInt(item.votos),
                                votos_partido: partidoData ? parseInt(partidoData.votos_partido) : 0,
                                padron_23: parseInt(item.padron_23),
                                padron_25: parseInt(item.padron_25)
                            };
                        });
                        
                        crearGraficaConPartido(datosCombinados, partido);
                        crearTablaConPartido(datosCombinados, partido);
                    }
                });
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los datos del partido');
        });
}

/**
 * Crear gráfica con partido específico (4 barras)
 */
function crearGraficaConPartido(datos, nombrePartido) {
    const ctx = document.getElementById('chartComparativa');
    if (!ctx) return;
    
    if (chartComparativa) {
        chartComparativa.destroy();
    }
    
    const municipios = datos.map(d => d.municipio);
    const votosValidos = datos.map(d => d.votos_validos);
    const votosPartido = datos.map(d => d.votos_partido);
    const padron23 = datos.map(d => d.padron_23);
    const padron25 = datos.map(d => d.padron_25);
    
    chartComparativa = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: municipios,
            datasets: [
                {
                    label: nombrePartido,
                    data: votosPartido,
                    backgroundColor: '#10b981',  // ← CAMBIA ESTA LÍNEA (era '#d4a574')
                    borderRadius: 6
                },
                {
                    label: 'Votos Válidos',
                    data: votosValidos,
                    backgroundColor: '#3b82f6',
                    borderRadius: 6
                },
                {
                    label: 'Padrón 23',
                    data: padron23,
                    backgroundColor: '#92400e',
                    borderRadius: 6
                },
                {
                    label: 'Padrón 25',
                    data: padron25,
                    backgroundColor: '#f59e0b',
                    borderRadius: 6
                }
            ]
        },
        options: {
            indexAxis: 'y', // HORIZONTAL
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: { size: 13, weight: '600' },
                        padding: 15,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    callbacks: {
                        label: context => context.dataset.label + ': ' + formatearNumero(context.parsed.x)
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: 'rgba(212, 165, 116, 0.1)' },
                    ticks: {
                        callback: value => formatearNumero(value),
                        font: { size: 11 }
                    }
                },
                y: {
                    grid: { display: false },
                    ticks: {
                        font: { size: 11, weight: '600' },
                        color: '#1a1a1a'
                    }
                }
            }
        }
    });
}

/**
 * Crear gráfica comparativa (sin partido - horizontal)
 */
function crearGrafica(datos, etiquetaVotos = 'Votos') {
    const ctx = document.getElementById('chartComparativa');
    if (!ctx) return;
    
    if (chartComparativa) {
        chartComparativa.destroy();
    }
    
    const municipios = datos.map(d => d.municipio);
    const votos = datos.map(d => parseInt(d.votos));
    const padron23 = datos.map(d => parseInt(d.padron_23));
    const padron25 = datos.map(d => parseInt(d.padron_25));
    
    chartComparativa = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: municipios,
            datasets: [
                {
                    label: etiquetaVotos,
                    data: votos,
                    backgroundColor: '#3b82f6',
                    borderRadius: 6
                },
                {
                    label: 'Padrón 23',
                    data: padron23,
                    backgroundColor: '#92400e',
                    borderRadius: 6
                },
                {
                    label: 'Padrón 25',
                    data: padron25,
                    backgroundColor: '#f59e0b',
                    borderRadius: 6
                }
            ]
        },
        options: {
            indexAxis: 'y', // HORIZONTAL
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: { size: 13, weight: '600' },
                        padding: 15,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    callbacks: {
                        label: context => context.dataset.label + ': ' + formatearNumero(context.parsed.x)
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: 'rgba(212, 165, 116, 0.1)' },
                    ticks: {
                        callback: value => formatearNumero(value),
                        font: { size: 11 }
                    }
                },
                y: {
                    grid: { display: false },
                    ticks: {
                        font: { size: 11, weight: '600' },
                        color: '#1a1a1a'
                    }
                }
            }
        }
    });
}

/**
 * Crear tabla con datos del partido
 */
function crearTablaConPartido(datos, nombrePartido) {
    if (tablaDetalle) {
        tablaDetalle.destroy();
    }
    
    const tbody = document.getElementById('tablaDetalleBody');
    tbody.innerHTML = '';
    
    let totalVotosValidos = 0;
    let totalVotosPartido = 0;
    let totalPadron23 = 0;
    let totalPadron25 = 0;
    
    datos.forEach(row => {
        const votosValidos = row.votos_validos;
        const votosPartido = row.votos_partido;
        const padron23 = row.padron_23;
        const padron25 = row.padron_25;
        
        totalVotosValidos += votosValidos;
        totalVotosPartido += votosPartido;
        totalPadron23 += padron23;
        totalPadron25 += padron25;
        
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>${row.municipio}</strong></td>
            <td class="text-primary"><strong>${formatearNumero(votosPartido)}</strong></td>
            <td>${formatearNumero(padron23)}</td>
            <td>${formatearNumero(padron25)}</td>
        `;
        tbody.appendChild(tr);
    });
    
    // Actualizar encabezado de la primera columna de votos
    const thVotos = document.querySelector('#tablaDetalle thead tr th:nth-child(2)');
    if (thVotos) {
        thVotos.textContent = nombrePartido;
    }
    
    // Actualizar totales
    document.getElementById('totalVotos').innerHTML = `<strong>${formatearNumero(totalVotosPartido)}</strong>`;
    document.getElementById('totalPadron23').innerHTML = `<strong>${formatearNumero(totalPadron23)}</strong>`;
    document.getElementById('totalPadron25').innerHTML = `<strong>${formatearNumero(totalPadron25)}</strong>`;
    
    // Inicializar DataTable
    tablaDetalle = $('#tablaDetalle').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
        pageLength: 10,
        order: [[1, 'desc']],
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0, className: 'text-start' }
        ]
    });
}

/**
 * Crear tabla
 */
function crearTabla(datos) {
    if (tablaDetalle) {
        tablaDetalle.destroy();
    }
    
    // Restaurar encabezado original
    const thVotos = document.querySelector('#tablaDetalle thead tr th:nth-child(2)');
    if (thVotos) {
        thVotos.textContent = 'Votos';
    }
    
    const tbody = document.getElementById('tablaDetalleBody');
    tbody.innerHTML = '';
    
    let totalVotos = 0;
    let totalPadron23 = 0;
    let totalPadron25 = 0;
    
    datos.forEach(row => {
        const votos = parseInt(row.votos);
        const padron23 = parseInt(row.padron_23);
        const padron25 = parseInt(row.padron_25);
        
        totalVotos += votos;
        totalPadron23 += padron23;
        totalPadron25 += padron25;
        
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>${row.municipio}</strong></td>
            <td class="text-primary"><strong>${formatearNumero(votos)}</strong></td>
            <td>${formatearNumero(padron23)}</td>
            <td>${formatearNumero(padron25)}</td>
        `;
        tbody.appendChild(tr);
    });
    
    // Actualizar totales
    document.getElementById('totalVotos').innerHTML = `<strong>${formatearNumero(totalVotos)}</strong>`;
    document.getElementById('totalPadron23').innerHTML = `<strong>${formatearNumero(totalPadron23)}</strong>`;
    document.getElementById('totalPadron25').innerHTML = `<strong>${formatearNumero(totalPadron25)}</strong>`;
    
    // Inicializar DataTable
    tablaDetalle = $('#tablaDetalle').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
        pageLength: 10,
        order: [[1, 'desc']],
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0, className: 'text-start' }
        ]
    });
}

/**
 * Crear tabla para partido específico
 */
function crearTablaPartido(datos, partido) {
    if (tablaDetalle) {
        tablaDetalle.destroy();
    }
    
    const tbody = document.getElementById('tablaDetalleBody');
    tbody.innerHTML = '';
    
    let totalVotos = 0;
    let totalPadron23 = 0;
    let totalPadron25 = 0;
    
    datos.forEach(row => {
        const votos = parseInt(row.votos_partido);
        const padron23 = parseInt(row.padron_23);
        const padron25 = parseInt(row.padron_25);
        
        totalVotos += votos;
        totalPadron23 += padron23;
        totalPadron25 += padron25;
        
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>${row.municipio}</strong></td>
            <td class="text-primary"><strong>${formatearNumero(votos)}</strong></td>
            <td>${formatearNumero(padron23)}</td>
            <td>${formatearNumero(padron25)}</td>
        `;
        tbody.appendChild(tr);
    });
    
    // Actualizar totales
    document.getElementById('totalVotos').innerHTML = `<strong>${formatearNumero(totalVotos)}</strong>`;
    document.getElementById('totalPadron23').innerHTML = `<strong>${formatearNumero(totalPadron23)}</strong>`;
    document.getElementById('totalPadron25').innerHTML = `<strong>${formatearNumero(totalPadron25)}</strong>`;
    
    // Inicializar DataTable
    tablaDetalle = $('#tablaDetalle').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
        pageLength: 10,
        order: [[1, 'desc']],
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0, className: 'text-start' }
        ]
    });
}

/**
 * Formatear números
 */
function formatearNumero(numero) {
    return new Intl.NumberFormat('es-GT').format(numero);
}

/**
 * Animar números
 */
function animarNumeros() {
    const elementos = document.querySelectorAll('.stat-value');
    
    elementos.forEach(elemento => {
        const texto = elemento.textContent.replace(/,/g, '');
        const esNumero = !isNaN(texto);
        
        if (esNumero) {
            const valorFinal = parseInt(texto);
            let valorActual = 0;
            const incremento = valorFinal / 50;
            
            const intervalo = setInterval(() => {
                valorActual += incremento;
                if (valorActual >= valorFinal) {
                    valorActual = valorFinal;
                    clearInterval(intervalo);
                }
                elemento.textContent = formatearNumero(Math.round(valorActual));
            }, 20);
        }
    });
}

/**
 * Confirmar cierre de sesión
 */
function confirmarCerrarSesion(event) {
    event.preventDefault();
    if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        window.location.href = '../cerrar_sesion.php';
    }
}
</script>

<style>
/* Ajuste para 5 cards en pantallas grandes */
@media (min-width: 1200px) {
    .col-xl-2-4 {
        flex: 0 0 auto;
        width: 20%;
    }
}

@media (max-width: 1199px) {
    .col-xl-2-4 {
        flex: 0 0 auto;
        width: 33.333333%;
    }
}

@media (max-width: 991px) {
    .col-xl-2-4 {
        flex: 0 0 auto;
        width: 50%;
    }
}
</style>

</body>
</html>