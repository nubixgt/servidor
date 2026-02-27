/**
 * assets/js/resultados_alcaldes.js
 * Manejo de datos para Centros de Votación y Mesas
 */

let tablaCentros = null;

document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando resultados alcaldes...');
    
    cargarEstadisticas();
    cargarFiltros();
    cargarDatos();
    
    // Event listeners para filtros
    const filtroDept = document.getElementById('filtroDepartamento');
    const filtroMun = document.getElementById('filtroMunicipio');
    
    if (filtroDept) {
        filtroDept.addEventListener('change', function() {
            if (this.value) {
                cargarMunicipios(this.value);
            } else {
                filtroMun.innerHTML = '<option value="">Todos</option>';
            }
            cargarDatos();
        });
    }
    
    if (filtroMun) {
        filtroMun.addEventListener('change', () => cargarDatos());
    }
});

/**
 * Cargar estadísticas generales
 */
function cargarEstadisticas() {
    fetch('../ajax/obtener_datos_alcaldes.php?accion=estadisticas_alcaldes')
        .then(response => response.json())
        .then(data => {
            console.log('Estadísticas alcaldes:', data);
            
            if (data.success) {
                const stats = data.data;
                document.getElementById('totalCentros').textContent = formatearNumero(stats.total_centros_votacion || 0);
                document.getElementById('totalMesas').textContent = formatearNumero(stats.total_mesas || 0);
                document.getElementById('totalVotantes').textContent = formatearNumero(stats.total_emitidos || 0);
                document.getElementById('totalMunicipios').textContent = formatearNumero(stats.total_municipios || 0);
                
                animarNumeros();
            }
        })
        .catch(error => console.error('Error:', error));
}

/**
 * Cargar filtros de departamentos
 */
function cargarFiltros() {
    const select = document.getElementById('filtroDepartamento');
    if (!select) return;
    
    fetch('../ajax/obtener_datos_electorales.php?accion=lista_departamentos')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                data.data.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept;
                    option.textContent = dept;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

/**
 * Cargar municipios según departamento
 */
function cargarMunicipios(departamento) {
    const select = document.getElementById('filtroMunicipio');
    if (!select) return;
    
    select.innerHTML = '<option value="">Todos</option>';
    
    fetch(`../ajax/obtener_datos_electorales.php?accion=lista_municipios&departamento=${encodeURIComponent(departamento)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                data.data.forEach(mun => {
                    const option = document.createElement('option');
                    option.value = mun;
                    option.textContent = mun;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

/**
 * Cargar datos de centros y mesas
 */
function cargarDatos() {
    let url = '../ajax/obtener_datos_alcaldes.php?accion=centros_y_mesas';
    
    const dept = document.getElementById('filtroDepartamento')?.value;
    const mun = document.getElementById('filtroMunicipio')?.value;
    
    if (dept) url += `&departamento=${encodeURIComponent(dept)}`;
    if (mun) url += `&municipio=${encodeURIComponent(mun)}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                inicializarTabla(data.data);
            }
        })
        .catch(error => console.error('Error:', error));
}


/**
 * Limpia HTML para exportaciones (Excel/PDF/Print)
 */
function limpiarHTML(data) {
  if (data == null) return '';
  if (typeof data !== 'string') return data;
  return data.replace(/<[^>]*>/g, '').trim();
}




/**
 * Inicializar DataTable
 */
function inicializarTabla(datos) {
  if (tablaCentros) {
    tablaCentros.clear().rows.add(datos).draw();
    return;
  }

  tablaCentros = $('#tablaCentros').DataTable({
    destroy: true,
    responsive: true,
    deferRender: true,
    processing: true,
    stateSave: true,
    pageLength: 25,
    order: [[4, 'desc']],
    language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },

    // Layout con botones alineados a la derecha

      dom:
  "<'row g-2 align-items-center'<'col-md-4'l><'col-md-4 text-center'f><'col-md-4 text-end'B>>" +
  "rt" +
  "<'row align-items-center'<'col-sm-5'i><'col-sm-7'p>>",
language: {
  url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
  search: "",
  searchPlaceholder: "Buscar…"
},


    buttons: [
      {
        extend: 'excelHtml5',
        title: 'Centros de Votación y Mesas',
        exportOptions: { columns: [0,1,2,3,4,5], format: { body: limpiarHTML } }
      },
      {
        extend: 'pdfHtml5',
        title: 'Centros de Votación y Mesas',
        orientation: 'landscape',
        pageSize: 'LEGAL',
        exportOptions: { columns: [0,1,2,3,4,5], format: { body: limpiarHTML } }
      },
      {
        extend: 'print',
        title: 'Centros de Votación y Mesas',
        exportOptions: { columns: [0,1,2,3,4,5], format: { body: limpiarHTML } }
      }
    ],

    data: datos,
    columns: [
      { data: 'departamento' },
      { data: 'municipio' },
      {
        data: 'centro_de_votacion',
        render: (d, type) => (type === 'display' ? `<strong>${d}</strong>` : d)
      },
      {
  data: 'mesa',
  render: (d, type) => (type === 'display'
    ? `<span class="badge rounded-pill bg-warning-subtle text-primary">${d}</span>`
    : d)
},


      {
        data: 'emitidos',
        className: 'text-end',
        render: (d, type) => (type === 'display' ? `<strong class="text-primary">${formatearNumero(d)}</strong>` : d)
      },
      {
        data: 'padron',
        className: 'text-end',
        render: (d, type) => (type === 'display' ? formatearNumero(d) : d)
      }
    ]
  });
}


function formatearNumero(numero) {
    return new Intl.NumberFormat('es-GT').format(numero);
}

function animarNumeros() {
    const elementos = document.querySelectorAll('.stat-value');
    elementos.forEach(elemento => {
        const texto = elemento.textContent.replace(/,/g, '');
        if (!isNaN(texto)) {
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