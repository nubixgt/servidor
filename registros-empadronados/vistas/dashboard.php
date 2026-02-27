<?php
// vistas/dashboard.php

require_once '../config/db.php';
require_once '../includes/funciones.php';
require_once '../includes/permisos.php';

// Verificar acceso
verificarAcceso();

$pdo = obtenerConexion();

// Obtener datos según el rol del usuario
$rol = obtenerRolUsuario();
$departamento = obtenerDepartamentoUsuario();
$municipio = obtenerMunicipioUsuario();

// Construir consulta base según el rol
$whereClause = "WHERE 1=1";
$params = [];

if ($rol === ROL_ALCALDE) {
    $whereClause .= " AND departamento = :departamento AND municipio = :municipio";
    $params[':departamento'] = $departamento;
    $params[':municipio'] = $municipio;
} elseif ($rol === ROL_DIPUTADO) {
    $whereClause .= " AND departamento = :departamento";
    $params[':departamento'] = $departamento;
}

// Obtener estadísticas generales
try {
    $stmt = $pdo->prepare("SELECT SUM(total) as total_personas FROM empadronados $whereClause");
    $stmt->execute($params);
    $totalPersonas = $stmt->fetch()['total_personas'] ?? 0;

    $stmt = $pdo->prepare("SELECT SUM(total_mujeres) as total FROM empadronados $whereClause");
    $stmt->execute($params);
    $totalMujeres = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->prepare("SELECT SUM(total_hombres) as total FROM empadronados $whereClause");
    $stmt->execute($params);
    $totalHombres = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM empadronados $whereClause");
    $stmt->execute($params);
    $totalMunicipios = $stmt->fetch()['total'] ?? 0;

    $porcentajeMujeres = $totalPersonas > 0 ? round(($totalMujeres / $totalPersonas) * 100, 1) : 0;
    $porcentajeHombres = $totalPersonas > 0 ? round(($totalHombres / $totalPersonas) * 100, 1) : 0;

    $stmt = $pdo->prepare("
        SELECT municipio, departamento, total 
        FROM empadronados 
        $whereClause 
        ORDER BY total DESC 
        LIMIT 5
    ");
    $stmt->execute($params);
    $topMunicipios = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT 
            SUM(edad_18a25) as edad_18a25,
            SUM(edad_26a30) as edad_26a30,
            SUM(edad_31a35) as edad_31a35,
            SUM(edad_36a40) as edad_36a40,
            SUM(edad_41a45) as edad_41a45,
            SUM(edad_46a50) as edad_46a50,
            SUM(edad_51a55) as edad_51a55,
            SUM(edad_56a60) as edad_56a60,
            SUM(edad_61a65) as edad_61a65,
            SUM(edad_66a70) as edad_66a70,
            SUM(edad_mayoroigual70) as edad_mayoroigual70
        FROM empadronados 
        $whereClause
    ");
    $stmt->execute($params);
    $datosEdades = $stmt->fetch();

    $stmt = $pdo->prepare("
        SELECT 
            SUM(mujeres_alfabetas) as mujeres_alfabetas,
            SUM(mujeres_analfabetas) as mujeres_analfabetas,
            SUM(hombres_alfabetas) as hombres_alfabetas,
            SUM(hombres_analfabetas) as hombres_analfabetas
        FROM empadronados 
        $whereClause
    ");
    $stmt->execute($params);
    $datosAlfabetismo = $stmt->fetch();

} catch (PDOException $e) {
    error_log("Error en dashboard: " . $e->getMessage());
    $totalPersonas = 0;
    $totalMujeres = 0;
    $totalHombres = 0;
    $totalMunicipios = 0;
}

// Iniciales del usuario
$iniciales = obtenerIniciales(obtenerNombreUsuario());
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard - Sistema SICO GT</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

  <!-- 🔥 SISTEMA DE DISEÑO PREMIUM - SICO GT -->
  <link rel="stylesheet" href="../assets/css/base.css">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <link rel="stylesheet" href="../assets/css/components.css">

  <style>
    /* Ajustes específicos del dashboard */
    #mapaGuatemala {
      height: 560px !important;
      width: 100%;
      border-radius: var(--radius-md);
      z-index: 1;
    }

    .mapa-container {
      background: var(--bg-glass);
      backdrop-filter: blur(20px);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: var(--spacing-lg);
      box-shadow: var(--shadow-md);
      transition: all var(--transition-smooth);
    }

    .mapa-container:hover {
      box-shadow: var(--shadow-lg);
      border-color: var(--color-primary-light);
    }

    .mapa-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: var(--spacing-md);
    }

    .mapa-title {
      font-weight: 700;
      margin: 0;
      color: var(--text-primary);
      font-size: var(--font-size-lg);
      display: flex;
      align-items: center;
      gap: var(--spacing-sm);
    }

    .mapa-title i {
      color: var(--color-primary);
    }

    .mapa-leyenda {
      display: flex;
      align-items: center;
      gap: var(--spacing-md);
      font-size: var(--font-size-sm);
    }

    .leyenda-item {
      display: flex;
      align-items: center;
      gap: var(--spacing-sm);
      color: var(--text-secondary);
    }

    .leyenda-color {
      width: 18px;
      height: 18px;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border-color);
    }

    .leaflet-tooltip {
      background: rgba(31, 41, 55, 0.92) !important;
      color: #fff !important;
      border: none !important;
      border-radius: 10px !important;
      padding: 12px !important;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.35) !important;
      font-family: var(--font-family);
    }

    /* 🎨 GRID 2x2 PARA TARJETAS CON DISEÑO MEJORADO */
    .stats-grid-vertical {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: var(--spacing-md);
      height: 100%;
      align-content: center; /* Centrar verticalmente */
    }

    /* 🎨 STAT CARD CON CUADRO DE COLOR - COMPACTA */
    .stat-card {
      background: var(--bg-glass);
      backdrop-filter: blur(20px);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 1rem; /* Padding reducido */
      transition: all var(--transition-smooth);
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      min-height: auto; /* Altura automática */
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      width: 100px;
      height: 100px;
      background: radial-gradient(circle, rgba(212, 165, 116, 0.08), transparent);
      border-radius: 50%;
      transform: translate(30%, -30%);
    }

    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-lg);
      border-color: var(--color-primary);
    }

    /* Header con icono de color */
    .stat-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 0.75rem; /* Margen reducido */
    }

    .stat-icon {
      width: 48px; /* Tamaño reducido */
      height: 48px;
      border-radius: var(--radius-md);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.75rem; /* Tamaño de emoji más grande */
      box-shadow: var(--shadow-md);
      flex-shrink: 0;
      transition: all var(--transition-base);
    }

    .stat-card:hover .stat-icon {
      transform: scale(1.05);
    }

    /* Colores de iconos */
    .stat-icon.blue {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: white;
    }

    .stat-icon.pink {
      background: linear-gradient(135deg, #ec4899, #db2777);
      color: white;
    }

    .stat-icon.purple {
      background: linear-gradient(135deg, #3b82f6, #2563eb); /* Cambiado a azul para hombres */
      color: white;
    }

    .stat-icon.gold {
      background: var(--gradient-primary);
      color: white;
    }

    /* Contenido de la tarjeta */
    .stat-content {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .stat-label {
      font-size: 0.75rem; /* Tamaño reducido */
      color: var(--text-secondary);
      font-weight: 600;
      margin-bottom: 0.35rem; /* Margen reducido */
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .stat-value {
      font-size: clamp(1.5rem, 2.5vw, 1.875rem); /* Tamaño reducido */
      font-weight: 800;
      color: var(--text-primary);
      line-height: 1.2;
      margin-bottom: 0.35rem; /* Margen reducido */
    }

    .stat-change {
      display: inline-flex;
      align-items: center;
      gap: var(--spacing-xs);
      font-size: 0.75rem; /* Tamaño reducido */
      color: var(--text-secondary);
      font-weight: 600;
    }

    .stat-change i {
      color: var(--color-primary);
    }

    @media (max-width: 576px) {
      .stats-grid-vertical {
        grid-template-columns: 1fr;
      }
      
      .stat-value {
        font-size: clamp(1.5rem, 2.5vw, 2rem);
      }
    }

    /* Gráficas */
    .chart-container {
      background: var(--bg-glass);
      backdrop-filter: blur(20px);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: var(--spacing-lg);
      box-shadow: var(--shadow-md);
      margin-bottom: var(--spacing-lg);
      transition: all var(--transition-smooth);
    }

    .chart-container:hover {
      box-shadow: var(--shadow-lg);
      border-color: var(--color-primary-light);
    }

    .chart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: var(--spacing-md);
    }

    .chart-title {
      font-size: var(--font-size-lg);
      font-weight: 600;
      color: var(--text-primary);
      margin: 0;
      display: flex;
      align-items: center;
      gap: var(--spacing-sm);
    }

    .chart-title i {
      color: var(--color-primary);
    }

    /* Animación de entrada */
    .animate-in {
      animation: fadeInUp 0.6s ease-out;
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* ===== 🎨 AJUSTES DE ALTURA PARA GRÁFICAS ===== */
    
    /* 🔧 Altura reducida para Distribución por Rangos de Edad */
    #grafica_edades {
      max-height: 320px !important;
      height: 320px !important;
    }

    /* 🔧 Misma altura para Alfabetismo y Género */
    #grafica_alfabetismo,
    #grafica_genero {
      max-height: 380px !important;
      height: 380px !important;
    }

    /* 📊 Contenedores de gráficas con altura consistente */
    .chart-container canvas {
      width: 100% !important;
      height: auto !important;
    }

    /* Asegurar que los contenedores de las gráficas de abajo tengan la misma altura */
    .row .col-lg-8,
    .row .col-lg-4 {
      display: flex;
    }

    .row .col-lg-8 .chart-container,
    .row .col-lg-4 .chart-container {
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    /* 📱 Responsive: En móviles mantener altura automática */
    @media (max-width: 768px) {
      #grafica_edades,
      #grafica_alfabetismo,
      #grafica_genero {
        max-height: none !important;
        height: auto !important;
        min-height: 250px !important;
      }
    }
  </style>
</head>
<body>
  <div class="dashboard-wrapper">
    
    <!-- Sidebar -->
    <?php include '../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
      
      <!-- Topbar -->
      <header class="topbar">
        <div class="topbar-left">
          <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="bi bi-list"></i>
          </button>
          <div class="page-title-wrapper">
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">Visualización general de datos del sistema</p>
          </div>
        </div>
        
        <div class="topbar-right">
          <div class="topbar-actions">
            <button class="btn-icon" title="Actualizar datos" onclick="actualizarDatos()">
              <i class="bi bi-arrow-clockwise"></i>
            </button>
            <button class="btn-icon" title="Exportar" onclick="exportarDatos()">
              <i class="bi bi-download"></i>
            </button>
          </div>
          
          <div class="user-profile">
            <div class="user-avatar"><?php echo $iniciales; ?></div>
            <div class="user-info">
              <p class="user-name"><?php echo obtenerNombreUsuario(); ?></p>
              <p class="user-role"><?php echo obtenerRolUsuario(); ?></p>
            </div>
          </div>
        </div>
      </header>
      
      <!-- Content -->
      <div class="content">
        
        <!-- Fila principal: Mapa + Tarjetas -->
        <div class="row mb-4">
          <!-- COLUMNA IZQUIERDA: MAPA (60%) -->
          <div class="col-lg-7 mb-4">
            <div class="mapa-container animate-in">
              <div class="mapa-header">
                <h3 class="mapa-title">
                  <i class="bi bi-map-fill"></i>
                  Mapa de Guatemala
                </h3>
                <div class="mapa-leyenda">
                  <div class="leyenda-item">
                    <div class="leyenda-color" style="background:#7C6036"></div>
                    <span>Alta</span>
                  </div>
                  <div class="leyenda-item">
                    <div class="leyenda-color" style="background:#a58452"></div>
                    <span>Media</span>
                  </div>
                  <div class="leyenda-item">
                    <div class="leyenda-color" style="background:#d7b887"></div>
                    <span>Baja</span>
                  </div>
                </div>
              </div>
              <div id="mapaGuatemala"></div>
            </div>
          </div>

          <!-- COLUMNA DERECHA: 4 TARJETAS (40%) -->
          <div class="col-lg-5 mb-4">
            <div class="stats-grid-vertical animate-in">
              
              <!-- Tarjeta 1: Total Empadronados -->
              <div class="stat-card">
                <div class="stat-header">
                  <div class="stat-icon blue">
                    <i class="bi bi-people-fill"></i>
                  </div>
                </div>
                <div class="stat-content">
                  <p class="stat-label">Total Empadronados</p>
                  <h3 class="stat-value" id="totalEmpadronados"><?php echo number_format($totalPersonas); ?></h3>
                  <div class="stat-change">
                    <i class="bi bi-arrow-up"></i>
                    <span>100% del total</span>
                  </div>
                </div>
              </div>

              <!-- Tarjeta 2: Total Mujeres -->
              <div class="stat-card">
                <div class="stat-header">
                  <div class="stat-icon pink">
                    <i class="fa-solid fa-person-dress"></i>
                  </div>
                </div>
                <div class="stat-content">
                  <p class="stat-label">Total Mujeres</p>
                  <h3 class="stat-value" id="totalMujeres"><?php echo number_format($totalMujeres); ?></h3>
                  <div class="stat-change">
                    <i class="bi bi-arrow-up"></i>
                    <span><?php echo $porcentajeMujeres; ?>% del total</span>
                  </div>
                </div>
              </div>

              <!-- Tarjeta 3: Total Hombres -->
              <div class="stat-card">
                <div class="stat-header">
                  <div class="stat-icon purple">
                    <i class="fa-solid fa-person"></i>
                  </div>
                </div>
                <div class="stat-content">
                  <p class="stat-label">Total Hombres</p>
                  <h3 class="stat-value" id="totalHombres"><?php echo number_format($totalHombres); ?></h3>
                  <div class="stat-change">
                    <i class="bi bi-arrow-up"></i>
                    <span><?php echo $porcentajeHombres; ?>% del total</span>
                  </div>
                </div>
              </div>

              <!-- Tarjeta 4: Municipios -->
              <div class="stat-card">
                <div class="stat-header">
                  <div class="stat-icon gold">
                    <i class="bi bi-pin-map-fill"></i>
                  </div>
                </div>
                <div class="stat-content">
                  <p class="stat-label">Municipios</p>
                  <h3 class="stat-value" id="totalMunicipios"><?php echo number_format($totalMunicipios); ?></h3>
                  <div class="stat-change">
                    <i class="bi bi-check-circle"></i>
                    <span>Registrados</span>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- Indicador de filtro activo -->
        <div id="indicadorFiltro" style="display:none" class="mb-3"></div>

        <!-- 🆕 FILA 1: Distribución por Rangos de Edad (Ancho completo) -->
        <div class="row">
          <div class="col-12 mb-4">
            <div class="chart-container animate-in">
              <div class="chart-header">
                <h3 class="chart-title">
                  <i class="bi bi-bar-chart-fill"></i>
                  Distribución por Rangos de Edad
                </h3>
              </div>
              <canvas id="grafica_edades"></canvas>
            </div>
          </div>
        </div>

        <!-- 🆕 FILA 2: Alfabetismo (66%) + Género (33%) -->
        <div class="row">
          <!-- Alfabetismo por Género (más ancha) -->
          <div class="col-lg-8 mb-4">
            <div class="chart-container animate-in">
              <div class="chart-header">
                <h3 class="chart-title">
                  <i class="bi bi-book-fill"></i>
                  Alfabetismo por Género
                </h3>
              </div>
              <canvas id="grafica_alfabetismo"></canvas>
            </div>
          </div>

          <!-- Distribución por Género (más estrecha) -->
          <div class="col-lg-4 mb-4">
            <div class="chart-container animate-in">
              <div class="chart-header">
                <h3 class="chart-title">
                  <i class="bi bi-pie-chart-fill"></i>
                  Distribución por Género
                </h3>
              </div>
              <canvas id="grafica_genero"></canvas>
            </div>
          </div>
        </div>

        <!-- 🆕 FILA 3: Top 5 Municipios (Ancho completo) -->
        <div class="row">
          <div class="col-12 mb-4">
            <div class="chart-container animate-in">
              <div class="chart-header">
                <h3 class="chart-title">
                  <i class="bi bi-trophy-fill"></i>
                  Top 5 Municipios
                </h3>
              </div>
              <div class="table-container">
                <table class="table" id="tablaTop5">
                  <thead>
                    <tr>
                      <th style="width: 80px;">#</th>
                      <th>Municipio</th>
                      <th>Departamento</th>
                      <th style="width: 150px; text-align: right;">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($topMunicipios as $index => $municipio): ?>
                    <tr>
                      <td>
                        <span class="badge badge-primary" style="font-size: 1rem; padding: 0.5rem 0.75rem;">
                          <?php echo $index + 1; ?>
                        </span>
                      </td>
                      <td>
                        <strong style="font-size: 1rem;"><?php echo htmlspecialchars($municipio['municipio']); ?></strong>
                      </td>
                      <td>
                        <span class="text-muted" style="font-size: 0.9rem;"><?php echo htmlspecialchars($municipio['departamento']); ?></span>
                      </td>
                      <td style="text-align: right;">
                        <strong style="font-size: 1.1rem; color: var(--color-primary);"><?php echo number_format($municipio['total']); ?></strong>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

      </div> <!-- Cierra .content -->
    </main> <!-- Cierra .main-content -->
  </div> <!-- Cierra .dashboard-wrapper -->

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>
  <script src="../assets/js/cerrar_sesion.js"></script>
  <script src="../assets/js/dashboard_interactivo.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
// ===== Datos iniciales =====
const datosEdades = <?php echo json_encode($datosEdades); ?>;
const datosAlfabetismo = <?php echo json_encode($datosAlfabetismo); ?>;

window.datosIniciales = {
  edades: [
    parseInt(datosEdades.edad_18a25) || 0,
    parseInt(datosEdades.edad_26a30) || 0,
    parseInt(datosEdades.edad_31a35) || 0,
    parseInt(datosEdades.edad_36a40) || 0,
    parseInt(datosEdades.edad_41a45) || 0,
    parseInt(datosEdades.edad_46a50) || 0,
    parseInt(datosEdades.edad_51a55) || 0,
    parseInt(datosEdades.edad_56a60) || 0,
    parseInt(datosEdades.edad_61a65) || 0,
    parseInt(datosEdades.edad_66a70) || 0,
    parseInt(datosEdades.edad_mayoroigual70) || 0
  ],
  genero: [<?php echo $totalMujeres; ?>, <?php echo $totalHombres; ?>],
  alfabetismo: {
    alfabetas: [
      parseInt(datosAlfabetismo.mujeres_alfabetas) || 0,
      parseInt(datosAlfabetismo.hombres_alfabetas) || 0
    ],
    analfabetas: [
      parseInt(datosAlfabetismo.mujeres_analfabetas) || 0,
      parseInt(datosAlfabetismo.hombres_analfabetas) || 0
    ]
  }
};
console.log('📊 Datos iniciales cargados:', window.datosIniciales);

// ===== Mapa =====
const FiltroDepartamento = { activo:false, departamento:null };
let mapaGuatemala=null, capaDepartamentos=null;

document.addEventListener('DOMContentLoaded', function(){
  // Inicializar Dashboard Interactivo
  if (typeof DashboardInteractivo !== 'undefined') {
    try { DashboardInteractivo.inicializar(); } catch(e){ console.error(e); }
  }
  
  // Inicializar Mapa
  inicializarMapa();
});

function inicializarMapa(){
  mapaGuatemala = L.map('mapaGuatemala', {
    center:[15.5,-90.25], 
    zoom:7, 
    minZoom:6, 
    maxZoom:10,
    zoomControl:true, 
    scrollWheelZoom:false
  });
  cargarDatosMapa();
}

async function cargarDatosMapa(){
  try{
    const response = await fetch('../ajax/obtener_datos.php?accion=datos_mapa_departamentos', { 
      headers:{'X-Requested-With':'XMLHttpRequest'} 
    });
    if(!response.ok) throw new Error('Error al cargar datos del mapa');
    const datosPopulacion = await response.json();

    const geoResponse = await fetch('../assets/data/guatemala.geojson');
    const geoData = await geoResponse.json();

    const poblaciones = Object.values(datosPopulacion.data).map(d => d.total);

    // --- calcular umbrales por cuantiles (33% y 66%) ---
    const valores = Object.values(datosPopulacion.data)
      .map(d => Number(d.total) || 0)
      .sort((a,b) => a - b);

    const n = valores.length;
    const q = p => valores[Math.floor(Math.max(0, Math.min(n - 1, (n - 1) * p)))];

    const Q33 = n ? q(0.33) : 0;
    const Q66 = n ? q(0.66) : 0;

    // Debug y leyenda dinámica
    console.log({ Q33, Q66 });

    const leyenda = document.querySelector('.mapa-leyenda');
    if (leyenda) {
      // Formateo GT con separadores
      const fmt = n => new Intl.NumberFormat('es-GT').format(n);

      leyenda.innerHTML = `
        <div class="leyenda-item">
          <div class="leyenda-color" style="background:#7C6036"></div>
          <span>Alta (≥ ${fmt(Q66)})</span>
        </div>
        <div class="leyenda-item">
          <div class="leyenda-color" style="background:#a58452"></div>
          <span>Media (≥ ${fmt(Q33)} y &lt; ${fmt(Q66)})</span>
        </div>
        <div class="leyenda-item">
          <div class="leyenda-color" style="background:#d7b887"></div>
          <span>Baja (&lt; ${fmt(Q33)})</span>
        </div>
      `;
    }


    // Colores leyenda:
    // Alta  -> #7C6036
    // Media -> #a58452
    // Baja  -> #d7b887
    function obtenerColor(p) {
      if (p >= Q66) return '#7C6036';   // Alta
      if (p >= Q33) return '#a58452';   // Media
      return '#d7b887';                 // Baja
    }

    function estiloDepartamento(feature){
      let nombreDept = (feature.properties.NAME_1 || feature.properties.name || '')
        .replace(/([a-z])([A-Z])/g,'$1 $2').toUpperCase();
      const datosDept = datosPopulacion.data[nombreDept] || { total:0 };
      return { 
        fillColor:obtenerColor(datosDept.total), 
        weight:2, 
        opacity:1, 
        color:'#fff', 
        fillOpacity:.85 
      };
    }

    function paraCadaDepartamento(feature, layer){
      let nombreDept = (feature.properties.NAME_1 || feature.properties.name || '')
        .replace(/([a-z])([A-Z])/g,'$1 $2').toUpperCase();
      const datosDept = datosPopulacion.data[nombreDept] || { total:0, mujeres:0, hombres:0 };
      const pct = datosPopulacion.total_general>0 ? ((datosDept.total/datosPopulacion.total_general)*100).toFixed(1) : 0;
      const nombreMostrar = nombreDept.toLowerCase().replace(/\b\w/g,l=>l.toUpperCase());

      layer.bindTooltip(`
        <div style="font-family: Inter, system-ui;">
          <strong style="font-size:16px; color:#aebdff">${nombreMostrar}</strong><br>
          <hr style="margin:8px 0; border-color:rgba(255,255,255,.25)">
          <strong>Total:</strong> ${formatearNumero(datosDept.total)}<br>
          <strong>Mujeres:</strong> ${formatearNumero(datosDept.mujeres)}<br>
          <strong>Hombres:</strong> ${formatearNumero(datosDept.hombres)}<br>
          <strong>% del total:</strong> ${pct}%<br>
          <hr style="margin:8px 0; border-color:rgba(255,255,255,.25)">
          <em style="font-size:12px; color:#c7d2fe">👆 Click para filtrar</em>
        </div>
      `, { sticky:true, direction:'top' });

      layer.on({
        mouseover: e => { 
          e.target.setStyle({ weight:3, color:'#d4a574', fillOpacity:1 }); 
          e.target.bringToFront(); 
        },
        mouseout: e => { 
          capaDepartamentos.resetStyle(e.target); 
        },
        click: e => { 
          aplicarFiltroDesdeMapa(nombreMostrar); 
        }
      });
    }

    capaDepartamentos = L.geoJSON(geoData, { 
      style:estiloDepartamento, 
      onEachFeature:paraCadaDepartamento 
    }).addTo(mapaGuatemala);
    
    mapaGuatemala.fitBounds(capaDepartamentos.getBounds());

  }catch(err){
    console.error('❌ Error al cargar el mapa:', err);
    Swal.fire({ 
      title:'Error', 
      text:'No se pudo cargar el mapa de Guatemala', 
      icon:'error', 
      confirmButtonColor:'#d4a574' 
    });
  }
}

async function aplicarFiltroDesdeMapa(nombreDepartamento){
  const nombreMay = (nombreDepartamento||'').toUpperCase();
  FiltroDepartamento.activo = true; 
  FiltroDepartamento.departamento = nombreMay;

  Swal.fire({ 
    title:'Aplicando filtro...', 
    text:`Filtrando por ${nombreDepartamento}`, 
    icon:'info', 
    allowOutsideClick:false, 
    showConfirmButton:false, 
    didOpen: () => Swal.showLoading() 
  });

  try{
    const response = await fetch(
      `../ajax/graficas_datos.php?tipo=filtro_departamento&departamento=${nombreMay}`, 
      { headers:{'X-Requested-With':'XMLHttpRequest'} }
    );
    if(!response.ok) throw new Error('Error al aplicar filtro');
    const datos = await response.json();

    if (typeof actualizarDashboard === 'function') { 
      actualizarDashboard(datos); 
    }
    resaltarDepartamentoEnMapa(nombreMay);
    Swal.close();

    Swal.mixin({ 
      toast:true, 
      position:'top-end', 
      showConfirmButton:false, 
      timer:2000, 
      timerProgressBar:true 
    }).fire({ 
      icon:'success', 
      title:`Filtro aplicado: ${nombreDepartamento}` 
    });

  }catch(e){
    Swal.fire({ 
      title:'Error', 
      text:'No se pudo aplicar el filtro', 
      icon:'error', 
      confirmButtonColor:'#d4a574' 
    });
  }
}

function resaltarDepartamentoEnMapa(nombreDepartamento){
  if(!capaDepartamentos) return;
  capaDepartamentos.eachLayer(l => capaDepartamentos.resetStyle(l));
  capaDepartamentos.eachLayer(layer => {
    let name = (layer.feature.properties.NAME_1 || layer.feature.properties.name || '')
      .replace(/([a-z])([A-Z])/g,'$1 $2').toUpperCase();
    if (name === nombreDepartamento){
      layer.setStyle({ weight:4, color:'#d4a574', fillOpacity:1 });
      layer.bringToFront();
      mapaGuatemala.fitBounds(layer.getBounds(), { padding:[50,50] });
    }
  });
}

function formatearNumero(n){ 
  return new Intl.NumberFormat('es-GT').format(n); 
}

// Utilidades
function actualizarDatos(){
  Swal.fire({ 
    title:'Actualizando datos...', 
    text:'Por favor espere', 
    icon:'info', 
    allowOutsideClick:false, 
    didOpen:()=>Swal.showLoading() 
  });
  setTimeout(()=> location.reload(), 800);
}

function exportarDatos(){
  Swal.fire({
    title:'Exportar Datos', 
    text:'¿En qué formato desea exportar?', 
    icon:'question',
    showCancelButton:true, 
    confirmButtonText:'Excel', 
    cancelButtonText:'PDF', 
    confirmButtonColor:'#d4a574'
  }).then(r=>{
    if(r.isConfirmed){ 
      Swal.fire('Exportando...','Generando archivo Excel','success'); 
    }
    else if(r.dismiss === Swal.DismissReason.cancel){ 
      Swal.fire('Exportando...','Generando archivo PDF','success'); 
    }
  });
}
</script>

<script>
// Animaciones de entrada
document.addEventListener('DOMContentLoaded', () => {
  // Focus visible al tabular
  document.body.addEventListener('keydown', e=>{
    if(e.key === 'Tab') document.documentElement.classList.add('show-focus');
  }, { once:true });
});
</script>

</body>
</html>