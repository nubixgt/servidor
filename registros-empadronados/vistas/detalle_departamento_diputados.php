<?php
/**
 * vistas/detalle_departamento_diputados.php
 * Detalle por Departamento (Diputaciones) basado en CSV
 */

require_once '../config/db.php';
require_once '../includes/funciones.php';
require_once '../includes/permisos.php';

verificarAcceso();
if (!tienePermiso('ver_resultados_electorales')) {
  redirigir('dashboard.php?error=sin_permisos');
}

$departamento = $_GET['departamento'] ?? '';
if ($departamento === '') {
  redirigir('resultados_diputaciones.php?error=departamento_invalido');
}

$titulo = "Detalle Diputaciones — " . $departamento;
$iniciales     = $_SESSION['iniciales']      ?? 'U';
$nombreUsuario = $_SESSION['usuario_nombre'] ?? 'Usuario';
$rolUsuario    = $_SESSION['rol_nombre']     ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($titulo) ?> - SICO GT</title>

  <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>

  <!-- Icons / DataTables -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"/>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css"/>
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css"/>

  <!-- AOS + estilos -->
  <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css"/>
  <link rel="stylesheet" href="../assets/css/base.css"/>
  <link rel="stylesheet" href="../assets/css/componentes.css"/>
  <link rel="stylesheet" href="../assets/css/dashboard.css"/>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

  <style>
    /* Ajuste para 5 cards en pantallas grandes */
    @media (min-width: 1200px)  { .col-xl-2-4 { width:20%; } }
    @media (max-width: 1199px)  { .col-xl-2-4 { width:33.3333%; } }
    @media (max-width: 991px)   { .col-xl-2-4 { width:50%; } }
  </style>
</head>
<body class="show-focus">
<div class="dashboard-wrapper">

  <?php include '../includes/sidebar.php'; ?>

  <main class="main-content">

    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
          <i class="bi bi-list"></i>
        </button>
        <div class="page-title-wrapper">
          <h1 class="page-title" id="pageTitle"><?= htmlspecialchars($titulo) ?></h1>
          <p class="page-subtitle">
            <a href="resultados_diputaciones.php" class="text-primary">
              <i class="bi bi-arrow-left"></i> Volver a Resultados de Diputaciones
            </a>
          </p>
        </div>
      </div>
      <div class="topbar-right">
        <div class="user-profile">
          <div class="user-avatar"><?= htmlspecialchars($iniciales) ?></div>
          <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($nombreUsuario) ?></div>
            <div class="user-role"><?= htmlspecialchars($rolUsuario) ?></div>
          </div>
        </div>
      </div>
    </header>

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
                  <p class="mb-0 text-muted" style="font-size:0.875rem">Elige el departamento para ver su detalle</p>
                </div>
                <div>
                  <select id="filtroDepartamento" class="form-select" style="min-width:250px;">
                    <option value="">Cargando departamentos...</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- KPIs -->
      <div class="row g-3 mb-4" data-aos="fade-up" data-aos-delay="50">
        <div class="col-12 col-sm-6 col-lg-4 col-xl-2-4">
          <div class="stat-card">
            <div class="stat-header"><div class="stat-icon green"><i class="bi bi-calculator"></i></div></div>
            <div class="stat-content">
              <div class="stat-label">Total de Votos</div>
              <div class="stat-value" id="k_total">0</div>
              <div class="stat-change positive"><i class="bi bi-plus-circle"></i><span>Contabilizados</span></div>
            </div>
          </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-4 col-xl-2-4">
          <div class="stat-card">
            <div class="stat-header"><div class="stat-icon blue"><i class="bi bi-people"></i></div></div>
            <div class="stat-content">
              <div class="stat-label">Votos Emitidos</div>
              <div class="stat-value" id="k_emitidos">0</div>
              <div class="stat-change positive"><i class="bi bi-check-circle"></i><span>Del padrón</span></div>
            </div>
          </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-4 col-xl-2-4">
          <div class="stat-card">
            <div class="stat-header"><div class="stat-icon purple"><i class="bi bi-person-check"></i></div></div>
            <div class="stat-content">
              <div class="stat-label">Votos Válidos</div>
              <div class="stat-value" id="k_validos">0</div>
              <div class="stat-change positive"><i class="bi bi-arrow-up"></i><span id="k_validos_pct">0%</span></div>
            </div>
          </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-4 col-xl-2-4">
          <div class="stat-card">
            <div class="stat-header"><div class="stat-icon gold"><i class="bi bi-person"></i></div></div>
            <div class="stat-content">
              <div class="stat-label">Padrón</div>
              <div class="stat-value" id="k_padron">0</div>
              <div class="stat-change positive"><i class="bi bi-info-circle"></i><span>Registrados</span></div>
            </div>
          </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-4 col-xl-2-4">
          <div class="stat-card">
            <div class="stat-header"><div class="stat-icon orange"><i class="bi bi-pin-map"></i></div></div>
            <div class="stat-content">
              <div class="stat-label">Mesas</div>
              <div class="stat-value" id="k_mesas">0</div>
              <div class="stat-change positive"><i class="bi bi-geo-alt"></i><span>En el depto.</span></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Top 5 Partidos -->
      <div class="row mb-4" data-aos="fade-up" data-aos-delay="100">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title"><i class="bi bi-trophy-fill"></i> Top 5 Partidos Políticos</h5>
            </div>
            <div class="card-body">
              <div style="height:300px;position:relative">
                <canvas id="chartTopPartidos"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Comparativa por Municipio (Total / Válidos / Rechazados) -->
      <div class="row mb-4" data-aos="fade-up" data-aos-delay="200">
        <div class="col-12">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
              <h5 class="card-title m-0">
                <i class="bi bi-bar-chart-line-fill"></i> Comparativa por Municipio
                <small class="text-muted d-block">Total de votos, Votos válidos y Votos rechazados</small>
              </h5>
            </div>
            <div class="card-body">
              <div style="height:500px;position:relative">
                <canvas id="chartComparativa"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla Detallada por Municipio -->
      <div class="row" data-aos="fade-up" data-aos-delay="300">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title m-0"><i class="bi bi-table"></i> Resultados Detallados por Municipio</h5>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table id="tablaDetalle" class="table table-hover" style="width:100%">
                  <thead>
                    <tr>
                      <th>Municipio</th>
                      <th>Total de votos</th>
                      <th>Votos válidos</th>
                      <th>Votos rechazados</th>
                      <th>Padrón</th>
                      <th>Mesas</th>
                    </tr>
                  </thead>
                  <tbody id="tablaDetalleBody"></tbody>
                  <tfoot>
                    <tr class="table-primary">
                      <th><strong>Total</strong></th>
                      <th id="t_total"><strong>0</strong></th>
                      <th id="t_validos"><strong>0</strong></th>
                      <th id="t_rechazados"><strong>0</strong></th>
                      <th id="t_padron"><strong>0</strong></th>
                      <th id="t_mesas"><strong>0</strong></th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /content -->

  </main>
</div><!-- /wrapper -->

<!-- JS libs -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://unpkg.com/aos@next/dist/aos.js"></script>


<script>
const API = '../ajax/datos_Diputados.php';
let DEP  = <?= json_encode($departamento) ?>;

const fmt = n => new Intl.NumberFormat('es-GT').format(n||0);
const DT_ES = {
  sProcessing:"Procesando...", sLengthMenu:"Mostrar _MENU_",
  sZeroRecords:"Sin resultados", sEmptyTable:"Sin datos",
  sInfo:"Mostrando _START_ a _END_ de _TOTAL_", sInfoEmpty:"Mostrando 0 a 0 de 0",
  sInfoFiltered:"(de _MAX_ total)", sSearch:"Buscar:",
  oPaginate:{ sFirst:"Primero", sLast:"Último", sNext:"Siguiente", sPrevious:"Anterior" }
};

let chartTop=null, chartCmp=null, dt=null;

async function api(accion, params={}){
  const qs = new URLSearchParams({accion, ...params});
  const r  = await fetch(`${API}?${qs.toString()}`);
  const j  = await r.json();
  if(!j.resultado) throw new Error(j.mensaje||'Error');
  return j.data;
}

function destroyByCanvasId(id){
  const el = document.getElementById(id);
  const inst = window.Chart && window.Chart.getChart ? window.Chart.getChart(el) : null;
  if (inst) inst.destroy();
}

/* ======= UI loaders ======= */
async function cargarDepartamentos(){
  const deps = await api('filtros_departamentos');
  const sel = document.getElementById('filtroDepartamento');
  sel.innerHTML = '<option value="">Seleccione un departamento</option>' +
    deps.map(d=>`<option value="${d}">${d}</option>`).join('');
  sel.value = DEP;
  sel.addEventListener('change', async () => {
    if(!sel.value) return;
    DEP = sel.value;
    document.getElementById('pageTitle').textContent = 'Detalle Diputaciones — ' + DEP;
    const newUrl = `detalle_departamento_diputados.php?departamento=${encodeURIComponent(DEP)}`;
    window.history.pushState({departamento:DEP}, '', newUrl);
    await renderTodo(); // recarga todo con el nuevo DEP
  });
}

/* ======= Cargas con filas de municipios compartidas ======= */
/* Hacemos UNA sola consulta a tabla(scope=municipios) y la reutilizamos */
async function cargarKPIs(munRows){
  const k = await api('totales', {departamento: DEP});

  const total = (k.emitidos||0);                // total = emitidos
  const validos = (k.validos||0);
  const rechazados = (k.nulos||0) + (k.blanco||0);

  document.getElementById('k_total').textContent    = fmt(total);
  document.getElementById('k_emitidos').textContent = fmt(k.emitidos||0);
  document.getElementById('k_validos').textContent  = fmt(validos);
  document.getElementById('k_validos_pct').textContent = (total>0? (validos*100/total).toFixed(1):'0.0') + '%';
  document.getElementById('k_padron').textContent   = fmt(k.padron||0);

  const totalMesas = munRows.reduce((acc,r)=> acc + (parseInt(r.mesas||0)||0), 0);
  document.getElementById('k_mesas').textContent = fmt(totalMesas);
}

async function cargarTop(){
  const data = await api('top_partidos', {departamento: DEP, limit:5});
  destroyByCanvasId('chartTopPartidos');
  const ctx = document.getElementById('chartTopPartidos').getContext('2d');

  // Barras con colores distintos
  const palette = ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#0ea5e9', '#22c55e', '#eab308'];
  const bg = data.map((_, i) => palette[i % palette.length]);

  chartTop = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: data.map(d => (d.sigla || d.partido || '').toString()),
      datasets: [{
        label: 'Votos',
        data: data.map(d => d.votos),
        backgroundColor: bg,
        borderRadius: 8,
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      indexAxis: 'y',
      plugins: { legend: { display: false } },
      scales: {
        x: { beginAtZero: true, ticks: { callback: v => fmt(v) } },
        y: { grid: { display: false } }
      }
    }
  });
}

async function cargarComparativaYTabla(munRows){
  // Datos comparativa
  const labels = munRows.map(r=>r.unidad);
  const total   = munRows.map(r=> parseInt(r.emitidos||0));
  const validos = munRows.map(r=> parseInt(r.validos||0));
  const rechaz  = munRows.map(r=> (parseInt(r.nulos||0) + parseInt(r.blanco||0)));

  // Chart comparativa (horizontal)
  destroyByCanvasId('chartComparativa');
  const ctx = document.getElementById('chartComparativa').getContext('2d');
  chartCmp = new Chart(ctx, {
    type:'bar',
    data:{
      labels,
      datasets:[
        { label:'Total de votos',   data: total,   backgroundColor:'#3b82f6', borderRadius:6, borderWidth:0 },
        { label:'Votos válidos',    data: validos, backgroundColor:'#10b981', borderRadius:6, borderWidth:0 }, // verde
        { label:'Votos rechazados', data: rechaz,  backgroundColor:'#ef4444', borderRadius:6, borderWidth:0 }  // rojo
      ]
    },
    options:{
      indexAxis:'y', responsive:true, maintainAspectRatio:false,
      plugins:{
        legend:{ position:'top' },
        tooltip:{ callbacks:{ label: (ctx)=> `${ctx.dataset.label}: ${fmt(ctx.parsed.x)}` } }
      },
      scales:{ x:{ beginAtZero:true, ticks:{ callback:v=>fmt(v) } }, y:{ grid:{display:false} } }
    }
  });

  // Tabla detallada
  if ($.fn.DataTable.isDataTable('#tablaDetalle')) {
    $('#tablaDetalle').DataTable().clear().destroy();
    $('#tablaDetalle').find('tbody').empty();
  }

  const tbody = document.getElementById('tablaDetalleBody');
  tbody.innerHTML = '';

  let sumTotal=0, sumVal=0, sumRech=0, sumPad=0, sumMesas=0;

  munRows.forEach(r=>{
    const t = parseInt(r.emitidos||0);
    const v = parseInt(r.validos||0);
    const rech = parseInt(r.nulos||0)+parseInt(r.blanco||0);
    const pad = parseInt(r.padron||0);
    const ms  = parseInt(r.mesas||0);

    sumTotal+=t; sumVal+=v; sumRech+=rech; sumPad+=pad; sumMesas+=ms;

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><strong>${r.unidad}</strong></td>
      <td class="text-primary"><strong>${fmt(t)}</strong></td>
      <td>${fmt(v)}</td>
      <td>${fmt(rech)}</td>
      <td>${fmt(pad)}</td>
      <td>${fmt(ms)}</td>
    `;
    tbody.appendChild(tr);
  });

  document.getElementById('t_total').innerHTML      = `<strong>${fmt(sumTotal)}</strong>`;
  document.getElementById('t_validos').innerHTML    = `<strong>${fmt(sumVal)}</strong>`;
  document.getElementById('t_rechazados').innerHTML = `<strong>${fmt(sumRech)}</strong>`;
  document.getElementById('t_padron').innerHTML     = `<strong>${fmt(sumPad)}</strong>`;
  document.getElementById('t_mesas').innerHTML      = `<strong>${fmt(sumMesas)}</strong>`;

  $('#tablaDetalle').DataTable({
    language: DT_ES, pageLength: 15, order: [[1,'desc']],
    columnDefs: [
      { targets: '_all', className: 'text-center' },
      { targets: 0, className: 'text-start' }
    ],
    responsive:true
  });
}

async function renderTodo(){
  // Traemos UNA vez los municipios y reutilizamos en KPIs + Comparativa + Tabla
  const munRows = await api('tabla', {departamento: DEP, scope:'municipios'});
  await Promise.all([
    cargarKPIs(munRows),
    cargarTop(),
    cargarComparativaYTabla(munRows)
  ]);
}

document.addEventListener('DOMContentLoaded', async ()=>{
  try{
    AOS.init({ duration: 800, once: true });
    await cargarDepartamentos();
    await renderTodo();
  }catch(e){
    console.error(e);
    alert('No fue posible cargar el detalle del departamento.');
  }
});
</script>

</body>
</html>
