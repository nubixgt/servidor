<?php
/**
 * vistas/resultados_diputaciones.php
 * Vista de Resultados Electorales (Diputaciones)
 * Sistema SICO GT
 */

/* ===== Debug opcional: agrega ?debug=1 al URL para ver el error real ===== */
if (isset($_GET['debug'])) {
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
  set_error_handler(function($no,$str,$file,$line){
    echo "<pre style='padding:12px;background:#1f2937;color:#f9fafb'>[PHP ERROR] $str @ $file:$line</pre>";
    return false;
  });
  set_exception_handler(function($ex){
    echo "<pre style='padding:12px;background:#7f1d1d;color:#fff'>[PHP EXCEPTION] ".$ex->getMessage()."\n".$ex->getTraceAsString()."</pre>";
  });
}

require_once '../config/db.php';
require_once '../includes/funciones.php';
require_once '../includes/permisos.php';

/* ===== Autenticación y permisos ===== */
verificarAcceso();
if (!tienePermiso('ver_resultados_electorales')) {
  redirigir('dashboard.php?error=sin_permisos');
}

/* ===== Datos de sesión/rol con fallbacks seguros (sin funciones extra) ===== */
$pdo          = function_exists('obtenerConexion') ? obtenerConexion() : null;
$rol          = function_exists('obtenerRolUsuario') ? obtenerRolUsuario() : ($_SESSION['rol'] ?? 'OTRO');
$departamento = function_exists('obtenerDepartamentoUsuario') ? (obtenerDepartamentoUsuario() ?? '') : ($_SESSION['departamento'] ?? '');
$municipio    = function_exists('obtenerMunicipioUsuario') ? (obtenerMunicipioUsuario() ?? '') : ($_SESSION['municipio'] ?? '');

/* Normaliza el rol para comparar tanto constantes como string */
function isRol($rol, $nombreConst, $texto) {
  return (defined($nombreConst) && $rol === constant($nombreConst))
      || (is_string($rol) && strtoupper($rol) === strtoupper($texto));
}
$isAdmin       = isRol($rol,'ROL_ADMINISTRADOR','ADMINISTRADOR');
$isPresidente  = isRol($rol,'ROL_PRESIDENTE','PRESIDENTE');
$isDiputado    = isRol($rol,'ROL_DIPUTADO','DIPUTADO');
$isAlcalde     = isRol($rol,'ROL_ALCALDE','ALCALDE');

$tituloEspecifico = "Resultados Electorales - Diputaciones";
if ($isDiputado && $departamento) $tituloEspecifico .= " " . $departamento;
if ($isAlcalde && $municipio)     $tituloEspecifico .= " " . $municipio;

/* Fallbacks para info del usuario (evita 500 si no existen variables) */
$iniciales     = isset($iniciales)     ? $iniciales     : ($_SESSION['iniciales'] ?? 'U');
$nombreUsuario = isset($nombreUsuario) ? $nombreUsuario : ($_SESSION['usuario_nombre'] ?? 'Usuario');
$rolUsuario    = isset($rolUsuario)    ? $rolUsuario    : ($_SESSION['rol_nombre'] ?? (is_string($rol) ? $rol : 'Usuario'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($tituloEspecifico) ?> - SICO GT</title>

  
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"/>

  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css"/>
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css"/>

  <!-- AOS -->
  <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css"/>

  <!-- Custom CSS -->
  <link rel="stylesheet" href="../assets/css/base.css"/>
  <link rel="stylesheet" href="../assets/css/componentes.css"/>
  <link rel="stylesheet" href="../assets/css/dashboard.css"/>

  <style>
  :root{
    --brand-oro:#d4a574;
  }

  /* === Estilo idéntico a Presidentes === */
  .table-theme thead th{
    background:#fff;          /* sin degradado */
    color:#1f2937;            /* texto gris oscuro */
    position:static;          /* sin sticky */
    border-bottom:1px solid #eee;
  }
  .table-theme tbody tr:hover{ background:#f9fafb; }

  .text-oro{ color:var(--brand-oro); } /* para "Emitidos" */
</style>

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
          <h1 class="page-title"><?= htmlspecialchars($tituloEspecifico) ?></h1>
          <p class="page-subtitle">
            <?php
              if ($isAdmin || $isPresidente) {
                echo "Vista completa de todos los resultados electorales";
              } elseif ($isDiputado) {
                echo "Resultados del departamento de " . htmlspecialchars($departamento);
              } elseif ($isAlcalde) {
                echo "Resultados del municipio de " . htmlspecialchars($municipio);
              } else {
                echo "Resultados por defecto del sistema";
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
          <div class="user-avatar"><?= htmlspecialchars($iniciales) ?></div>
          <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($nombreUsuario) ?></div>
            <div class="user-role"><?= htmlspecialchars($rolUsuario) ?></div>
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
            <div class="stat-header"><div class="stat-icon blue"><i class="bi bi-inbox"></i></div></div>
            <div class="stat-content">
              <div class="stat-label">Total Votos Emitidos</div>
              <div class="stat-value" id="totalEmitidos">0</div>
              <div class="stat-change positive"><i class="bi bi-arrow-up"></i><span>Actualizados</span></div>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
          <div class="stat-card">
            <div class="stat-header"><div class="stat-icon green"><i class="bi bi-percent"></i></div></div>
            <div class="stat-content">
              <div class="stat-label">Participación Electoral</div>
              <div class="stat-value" id="porcentajeParticipacion">0%</div>
              <div class="stat-change positive"><i class="bi bi-graph-up"></i><span>Del padrón</span></div>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
          <div class="stat-card">
            <div class="stat-header"><div class="stat-icon purple"><i class="bi bi-check-circle"></i></div></div>
            <div class="stat-content">
              <div class="stat-label">Votos Válidos</div>
              <div class="stat-value" id="totalValidos">0</div>
              <div class="stat-change positive"><i class="bi bi-check2"></i><span>Contabilizados</span></div>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
          <div class="stat-card">
            <div class="stat-header"><div class="stat-icon orange"><i class="bi bi-x-circle"></i></div></div>
            <div class="stat-content">
              <div class="stat-label">Votos Nulos</div>
              <div class="stat-value" id="totalNulos">0</div>
              <div class="stat-change negative"><i class="bi bi-x"></i><span>Descartados</span></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráficas -->
      <div class="row g-4 mb-4">
        <div class="col-12 col-lg-8" data-aos="fade-up" data-aos-delay="100">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title"><i class="bi bi-bar-chart-fill"></i> Top 10 Partidos Políticos</h5>
            </div>
            <div class="card-body">
              <div style="height: 350px; position: relative;">
                <canvas id="chartTopPartidos"></canvas>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-4" data-aos="fade-up" data-aos-delay="200">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title"><i class="bi bi-pie-chart-fill"></i> Distribución de Votos</h5>
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
                  if ($isAdmin || $isPresidente) {
                    echo "Resultados por Departamento";
                  } elseif ($isDiputado) {
                    echo "Resultados por Municipio - " . htmlspecialchars($departamento);
                  } elseif ($isAlcalde) {
                    echo "Resultados de " . htmlspecialchars($municipio);
                  } else {
                    echo "Resultados por Departamento";
                  }
                ?>
              </h5>

              <?php if ($isAdmin || $isPresidente): ?>
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
                  <table id="tablaResultados" class="table table-hover table-striped align-middle table-theme" style="width:100%">

                  <thead>
                    <tr>
                      <th><?= ($isAlcalde || $isDiputado) ? 'Municipio' : 'Departamento'; ?></th>
                      <th>Padrón</th>
                      <th>Emitidos</th>
                      <th>Válidos</th>
                      <th>Nulos</th>
                      <th>Blanco</th>
                      <th>Participación</th>
                      <th><?= ($isAlcalde || $isDiputado) ? 'Mesas' : 'Municipios'; ?></th>
                      <?php if ($isAdmin || $isPresidente || $isDiputado): ?>
                        <th>Acciones</th>
                      <?php endif; ?>
                    </tr>
                  </thead>
                  <tbody id="tablaResultadosBody"></tbody>
                </table>
              </div>
            </div>

          </div>
        </div>
      </div>

    </div><!-- /content -->

  </main>
</div><!-- /wrapper -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<!-- AOS + Feather -->
<script src="https://unpkg.com/aos@next/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>

<!-- Script global -->
<script src="../assets/js/dashboard_interactivo.js"></script>

<!-- Variables que consume resultados_diputados.js -->
<script>
  const API_DIP = '../ajax/datos_Diputados.php';
  const ROL     = <?= json_encode($isDiputado ? 'DIPUTADO' : ($isAlcalde ? 'ALCALDE' : ($isAdmin ? 'ADMINISTRADOR' : ($isPresidente ? 'PRESIDENTE' : 'OTRO')))) ?>;
  const DEP_USR = <?= json_encode($departamento) ?>;
  const MUN_USR = <?= json_encode($municipio) ?>;

  // Inicializaciones visuales
  document.addEventListener('DOMContentLoaded', () => {
    try { AOS.init({ duration: 800, easing: 'ease-in-out', once: true, offset: 50 }); } catch(e){}
    try { feather.replace(); } catch(e){}
  });

  // Exportación (stub; apunta a tu endpoint cuando esté listo)
  function exportarDatos(){
    <?php if (tienePermiso('exportar_resultados_electorales')): ?>
      let params = '';
      if (ROL === 'DIPUTADO' && DEP_USR) {
        params = '?departamento=' + encodeURIComponent(DEP_USR);
      } else if (ROL === 'ALCALDE' && DEP_USR && MUN_USR) {
        params = '?departamento=' + encodeURIComponent(DEP_USR) + '&municipio=' + encodeURIComponent(MUN_USR);
      } else {
        const sel = document.getElementById('filtroDepartamento');
        if (sel && sel.value) params = '?departamento=' + encodeURIComponent(sel.value);
      }
      alert('Exportación activa próximamente.'); // window.location.href = '../ajax/exportar_resultados_diputados.php' + params;
    <?php else: ?>
      alert('No tienes permisos para exportar datos');
    <?php endif; ?>
  }
</script>

<!-- Lógica específica (lee CSV/XLSX vía datos_Diputados.php) -->
<script src="../assets/js/resultados_diputados.js"></script>

</body>
</html>
