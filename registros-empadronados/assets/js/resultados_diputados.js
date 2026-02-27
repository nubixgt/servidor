/* assets/js/resultados_diputados.js */
(function(){
  if (window.__RD_INIT__) return;  // evita doble carga
  window.__RD_INIT__ = true;

  const $ = window.jQuery;
  const API = (typeof API_DIP !== 'undefined') ? API_DIP : '../ajax/datos_Diputados.php';
  const ROL_USR = (typeof ROL !== 'undefined') ? ROL : '';
  const DEP_DEF = (typeof DEP_USR !== 'undefined') ? DEP_USR : '';
  const MUN_DEF = (typeof MUN_USR !== 'undefined') ? MUN_USR : '';

  // === Mapeo CSV por departamento (ajusta si tu nomenclatura difiere) ===
const DEP_TO_CODES = {
  "guatemala": ["00","01"],
  "el progreso": ["02"],
  "sacatepequez": ["03"],
  "chimaltenango": ["04"],
  "escuintla": ["05"],
  "santa rosa": ["06"],
  "solola": ["07"],
  "totonicapan": ["08"],
  "quetzaltenango": ["09"],
  "suchitepequez": ["10"],
  "retalhuleu": ["11"],
  "san marcos": ["12"],
  "huehuetenango": ["13"],
  "quiche": ["14"],
  "baja verapaz": ["15"],
  "alta verapaz": ["16"],
  "peten": ["17"],
  "izabal": ["18"],
  "zacapa": ["19"],
  "chiquimula": ["20"],
  "jalapa": ["21"],
  "jutiapa": ["22"]
};


// Paleta igual que presidentes (con acento oro)
const coloresPartidos = [
  '#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444',
  '#06b6d4','#84cc16','#ec4899','#6366f1','#14b8a6',
  '#0ea5e9','#22c55e','#eab308','#dc2626','#7c3aed',
  '#d4a574' // acento oro
];


function normDep(s){
  return (s||'')
    .toString()
    .normalize('NFD').replace(/[\u0300-\u036f]/g,'') // quita acentos
    .toLowerCase().trim();
}
function getCodesForDepartment(dep){
  return DEP_TO_CODES[normDep(dep)] || [];
}
function buildDetalleURL(dep){
  const params = new URLSearchParams({ departamento: dep });
  const codes = getCodesForDepartment(dep);
  if (codes.length) params.set('codes', codes.join(','));
  return `detalle_departamento_diputados.php?${params.toString()}`;
}


  // Idioma embebido (evita CORS a cdn.datatables.net)
  const DATATABLES_ES = {
    sProcessing:   "Procesando...",
    sLengthMenu:   "Mostrar _MENU_ registros",
    sZeroRecords:  "No se encontraron resultados",
    sEmptyTable:   "Ningún dato disponible en esta tabla",
    sInfo:         "Mostrando _START_ a _END_ de _TOTAL_ registros",
    sInfoEmpty:    "Mostrando 0 a 0 de 0 registros",
    sInfoFiltered: "(filtrado de _MAX_ registros totales)",
    sSearch:       "Buscar:",
    oPaginate: {
      sFirst:    "Primero",
      sLast:     "Último",
      sNext:     "Siguiente",
      sPrevious: "Anterior"
    },
    oAria: {
      sSortAscending:  ": Activar para ordenar la columna ascendente",
      sSortDescending: ": Activar para ordenar la columna descendente"
    }
  };

  let chartTop=null, chartPie=null, dt=null;

  const fmt = n => new Intl.NumberFormat('es-GT').format(n||0);
  const pct = x => (x||0).toFixed(1) + '%';

  const PRETTY = {
    'valor_unionista': 'VALOR/UNIONISTA',
    'ur_u_r_n_g_maiz': 'URNG-MAÍZ',
    'fcn_nacion': 'FCN-NACIÓN',
    'mi_familia': 'MI FAMILIA',
    'p_a_n': 'PAN',
  };
  const pretty = k => {
    const kk = (k||'').toLowerCase().replace(/\s+/g,'_');
    if (PRETTY[kk]) return PRETTY[kk];
    return (k||'').toString().replace(/_/g,' ').toUpperCase();
  };

  async function api(accion, params={}){
    const qs = new URLSearchParams({accion, ...params});
    const r = await fetch(`${API}?${qs.toString()}`);
    const j = await r.json();
    if(!j.resultado) throw new Error(j.mensaje||'Error');
    return j.data;
  }

  async function cargarDepartamentos(){
    const sel = document.getElementById('filtroDepartamento');
    if(!sel) return;
    const deps = await api('filtros_departamentos');
    sel.innerHTML = `<option value="">Todos los departamentos</option>` +
      deps.map(d=>`<option value="${d}">${d}</option>`).join('');
    if(ROL_USR.includes('DIPUTADO') && DEP_DEF){
      sel.value = DEP_DEF;
    }
    sel.addEventListener('change', renderTodo);
  }

  async function renderKPIs(params){
    const d = await api('totales', params);
    document.getElementById('totalEmitidos').textContent = fmt(d.emitidos);
    document.getElementById('totalValidos').textContent  = fmt(d.validos);
    document.getElementById('totalNulos').textContent    = fmt(d.nulos);
    document.getElementById('porcentajeParticipacion').textContent = pct(d.participacion);
  }

  function destroyChartByCanvasId(id){
    const canvas = document.getElementById(id);
    if (!canvas) return;
    const inst = window.Chart && window.Chart.getChart ? window.Chart.getChart(canvas) : null;
    if (inst) inst.destroy();
  }

  async function renderPie(params){
  const d = await api('distribucion', params);
  destroyChartByCanvasId('chartVotos');

  const ctx = document.getElementById('chartVotos');
  if(!ctx) return;

  const validos = d.validos||0, nulos = d.nulos||0, blanco = d.blanco||0;
  const total = validos + nulos + blanco;

  chartPie = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Votos Válidos','Votos Nulos','Votos en Blanco'],
      datasets: [{
        data: [validos, nulos, blanco],
        backgroundColor: ['#10b981','#ef4444','#f59e0b'],
        borderWidth: 0,
        hoverOffset: 15
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '70%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            padding: 20,
            font: { size:13, weight:'600' },
            color: '#1a1a1a',
            usePointStyle: true,
            pointStyle: 'circle'
          }
        },
        tooltip: {
          backgroundColor: 'rgba(0,0,0,.85)',
          padding: 12,
          titleFont: { size:14, weight:'bold' },
          bodyFont: { size:13 },
          callbacks: {
            label: (ctx) => {
              const val = ctx.parsed;
              const perc = total ? ((val/total)*100).toFixed(2) : '0.00';
              return `${ctx.label}: ${fmt(val)} (${perc}%)`;
            }
          }
        }
      },
      animation: {
        animateRotate: true, animateScale: true,
        duration: 1500, easing: 'easeInOutQuart'
      }
    }
  });
}


async function renderTop(params){
  const data = await api('top_partidos', {...params, limit:10});
  const labels = data.map(x => x.display || pretty(x.sigla || x.partido));
  const votos  = data.map(x => x.votos);

  destroyChartByCanvasId('chartTopPartidos');
  const ctx = document.getElementById('chartTopPartidos');
  if(!ctx) return;

  chartTop = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Votos',
        data: votos,
        backgroundColor: coloresPartidos.slice(0, votos.length),
        borderWidth: 0,
        borderSkipped: false,
        borderRadius: 10,
        maxBarThickness: 36
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display:false },
        tooltip: {
          backgroundColor: 'rgba(0,0,0,.85)',
          padding: 12,
          titleFont: { size:14, weight:'bold' },
          bodyFont: { size:13 },
          callbacks: {
            label: (ctx) => 'Votos: ' + fmt(ctx.parsed.y)
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: { color:'rgba(212,165,116,.18)', drawBorder:false },
          ticks: { callback:v=>fmt(v), font:{size:11}, color:'#6b6b6b' }
        },
        x: {
          grid: { display:false },
          ticks: { font:{size:11, weight:'600'}, color:'#1a1a1a' }
        }
      },
      animation: { duration:1200, easing:'easeInOutQuart' }
    }
  });
}



function colsTabla(scope){
  const colUnidad = (scope==='departamentos'?'Departamento':(scope==='municipios'?'Municipio':'Mesa'));

  const cols = [
    { data:'unidad', title: colUnidad },

    { data:'padron', title:'Padrón',
      render: (v, type) => type==='display' ? fmt(v) : v
    },

    /* === Emitidos en dorado, texto simple (igual Presidentes) === */
    { data:'emitidos', title:'Emitidos',
      render: (v, type) => type==='display'
        ? `<span class="fw-semibold text-oro">${fmt(v)}</span>` : v
    },

    { data:'validos', title:'Válidos',
      render: (v, type) => type==='display'
        ? `<span class="badge rounded-pill text-bg-success">${fmt(v)}</span>` : v
    },

    { data:'nulos', title:'Nulos',
      render: (v, type) => type==='display'
        ? `<span class="badge rounded-pill text-bg-danger">${fmt(v)}</span>` : v
    },

    { data:'blanco', title:'Blanco',
      render: (v, type) => type==='display'
        ? `<span class="badge rounded-pill text-bg-warning text-dark">${fmt(v)}</span>` : v
    },

    /* === Participación como % plano, sin barra === */
    { data:'participacion', title:'Participación',
      render: (v, type) => {
        const val = parseFloat(v)||0;
        return (type==='display')
          ? `<span class="fw-semibold">${val.toFixed(2)}%</span>`
          : val; // mantiene ordenación correcta
      }
    }
  ];

  /* === Pill suave para Municipios/Mesas (gris claro) === */
  if (scope==='departamentos'){
    cols.push({
      data:'municipios', title:'Municipios',
      render: (v, type) => type==='display'
        ? `<span class="badge badge-secondary rounded-pill">${fmt(v)}</span>` : v
    });
  } else if (scope==='municipios'){
    cols.push({
      data:'mesas', title:'Mesas',
      render: (v, type) => type==='display'
        ? `<span class="badge badge-secondary rounded-pill">${fmt(v)}</span>` : v
    });
  }

  // Acciones (igual lo tenías)
  const thAcc = document.querySelector('#tablaResultados thead th:last-child');
  if (thAcc && /Acciones/i.test(thAcc.textContent||'')) {
    cols.push({
      data:null, title:'Acciones', orderable:false, searchable:false,
      render: (row) => {
        const dep = row.departamento || row.unidad || '';
        return `
          <button class="btn btn-sm btn-primary btn-ojito" data-dep="${dep}" title="Ver detalle">
            <i class="bi bi-eye"></i> Ver Detalle
          </button>`;
      }
    });
  }

  return cols;
}



  async function renderTabla(params){
  let scope = 'departamentos';
  if (ROL_USR.includes('DIPUTADO')) scope = 'municipios';
  if (ROL_USR.includes('ALCALDE'))  scope = 'mesas';

  const data = await api('tabla', {...params, scope});

  if ($.fn.DataTable.isDataTable('#tablaResultados')) {
    $('#tablaResultados').DataTable().clear().destroy();
    $('#tablaResultados').find('tbody').empty();
  }

  $('#tablaResultados').DataTable({
    data,
    columns: colsTabla(scope),
    responsive: true,
    pageLength: 25,
    // 0:unidad, 1:padron, 2:emitidos
    order: [[2,'desc']],
    language: DATATABLES_ES,
    columnDefs: [
  { targets:'_all', className:'align-middle' },
  { targets:0, className:'text-start fw-semibold' },
  { targets:[1,2,3,4,5,6,7], className:'text-end' }, // numéricas a la derecha
  { targets:-1, orderable:false, searchable:false }
],

    dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-5"i><"col-md-7"p>>'
  });
}


  async function renderTodo(){
    const depSel = document.getElementById('filtroDepartamento');
    const departamento = depSel && depSel.value ? depSel.value : (ROL_USR.includes('DIPUTADO') ? DEP_DEF : '');
    const municipio    = (ROL_USR.includes('ALCALDE') ? MUN_DEF : '');

    const params = {departamento, municipio};

    await Promise.all([
      renderKPIs(params),
      renderPie(params),
      renderTop(params),
      renderTabla(params)
    ]);
  }

  document.addEventListener('DOMContentLoaded', async ()=>{
    try{
      await cargarDepartamentos();
      await renderTodo();
    }catch(e){
      console.error(e);
      alert('No fue posible cargar los resultados de Diputaciones.');
    }
  });

// Delegado para el ojito en la tabla principal
document.addEventListener('click', function(ev){
  const btn = ev.target.closest('#tablaResultados .btn-ojito');
  if (!btn) return;
  const dep = btn.dataset.dep || '';
  if (!dep) {
    alert('No se pudo determinar el departamento.');
    return;
  }
  // Redirige con ?departamento=...&codes=... (Guatemala = 00,01)
  window.location.href = buildDetalleURL(dep);
});



})();
