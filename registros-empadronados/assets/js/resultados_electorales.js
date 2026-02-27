/**
 * js/resultados_electorales.js
 * Manejo de gráficas y datos de resultados electorales
 */

// Variables globales
let chartTopPartidos = null;
let chartVotos = null;
let tablaResultados = null;

// Colores para partidos (puedes personalizarlos)
const coloresPartidos = [
    '#d4a574', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6',
    '#ef4444', '#06b6d4', '#f97316', '#84cc16', '#ec4899',
    '#6366f1', '#14b8a6', '#a855f7', '#f43f5e', '#0ea5e9',
    '#22c55e', '#eab308', '#dc2626', '#059669', '#7c3aed'
];

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    cargarEstadisticasGenerales();
    cargarGraficaTopPartidos();
    cargarGraficaVotos();
    cargarTablaResultados();
    
    // Event listener para el filtro de departamento
    const filtroDepartamento = document.getElementById('filtroDepartamento');
    if (filtroDepartamento) {
        filtroDepartamento.addEventListener('change', function() {
            cargarTablaResultados(this.value);
        });
    }
});

/**
 * Cargar estadísticas generales (cards superiores)
 */
function cargarEstadisticasGenerales() {
    fetch('../ajax/obtener_datos_electorales.php?accion=estadisticas_generales')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                
                // Actualizar cards
                document.getElementById('totalEmitidos').textContent = formatearNumero(stats.total_emitidos || 0);
                document.getElementById('porcentajeParticipacion').textContent = (stats.porcentaje_participacion || 0) + '%';
                document.getElementById('totalValidos').textContent = formatearNumero(stats.total_validos || 0);
                document.getElementById('totalNulos').textContent = formatearNumero(stats.total_nulos || 0);
                
                // Animar los números
                animarNumeros();
            }
        })
        .catch(error => {
            console.error('Error al cargar estadísticas:', error);
            mostrarError('No se pudieron cargar las estadísticas generales');
        });
}

/**
 * Cargar gráfica de top partidos
 */
function cargarGraficaTopPartidos() {
    fetch('../ajax/obtener_datos_electorales.php?accion=top_partidos&limite=10')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                crearGraficaTopPartidos(data.data);
            }
        })
        .catch(error => {
            console.error('Error al cargar top partidos:', error);
        });
}

/**
 * Crear gráfica de barras - Top Partidos
 */
function crearGraficaTopPartidos(datos) {
    const ctx = document.getElementById('chartTopPartidos');
    if (!ctx) return;
    
    // Destruir gráfica anterior si existe
    if (chartTopPartidos) {
        chartTopPartidos.destroy();
    }
    
    const labels = datos.map(item => item.partido);
    const votos = datos.map(item => item.votos);
    
    chartTopPartidos = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Votos',
                data: votos,
                backgroundColor: coloresPartidos.slice(0, datos.length),
                borderRadius: 8,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function(context) {
                            return 'Votos: ' + formatearNumero(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(212, 165, 116, 0.1)',
                        drawBorder: false
                    },
                    ticks: {
                        callback: function(value) {
                            return formatearNumero(value);
                        },
                        font: {
                            size: 11
                        },
                        color: '#6b6b6b'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11,
                            weight: '600'
                        },
                        color: '#1a1a1a'
                    }
                }
            },
            animation: {
                duration: 1500,
                easing: 'easeInOutQuart'
            }
        }
    });
}

/**
 * Cargar gráfica de votos válidos, nulos y blancos
 */
function cargarGraficaVotos() {
    fetch('../ajax/obtener_datos_electorales.php?accion=estadisticas_generales')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                crearGraficaVotos(stats);
            }
        })
        .catch(error => {
            console.error('Error al cargar gráfica de votos:', error);
        });
}

/**
 * Crear gráfica de donut - Distribución de Votos
 */
function crearGraficaVotos(stats) {
    const ctx = document.getElementById('chartVotos');
    if (!ctx) return;
    
    // Destruir gráfica anterior si existe
    if (chartVotos) {
        chartVotos.destroy();
    }
    
    const validos = parseInt(stats.total_validos) || 0;
    const nulos = parseInt(stats.total_nulos) || 0;
    const blanco = parseInt(stats.total_blanco) || 0;
    
    chartVotos = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Votos Válidos', 'Votos Nulos', 'Votos en Blanco'],
            datasets: [{
                data: [validos, nulos, blanco],
                backgroundColor: [
                    '#10b981',
                    '#ef4444',
                    '#f59e0b'
                ],
                borderWidth: 0,
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        font: {
                            size: 13,
                            weight: '600'
                        },
                        color: '#1a1a1a',
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = formatearNumero(context.parsed);
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(2);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 1500,
                easing: 'easeInOutQuart'
            },
            cutout: '70%'
        }
    });
}

/**
 * Cargar tabla de resultados por departamento
 */
function cargarTablaResultados(departamento = '') {
    const url = departamento 
        ? `../ajax/obtener_datos_electorales.php?accion=resultados_por_municipio&departamento=${encodeURIComponent(departamento)}`
        : '../ajax/obtener_datos_electorales.php?accion=resultados_por_departamento';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                inicializarTabla(data.data, departamento);
            }
        })
        .catch(error => {
            console.error('Error al cargar tabla:', error);
            mostrarError('No se pudo cargar la tabla de resultados');
        });
}

/**
 * Inicializar DataTable
 */
function inicializarTabla(datos, departamento) {
    console.log('Inicializando tabla con', datos.length, 'registros');
    
    // Destruir tabla anterior si existe
    if (tablaResultados) {
        tablaResultados.destroy();
    }
    
    const tbody = document.getElementById('tablaResultadosBody');
    tbody.innerHTML = '';
    
    // Llenar tabla con datos
    datos.forEach(row => {
        const tr = document.createElement('tr');
        
        if (departamento) {
            // Vista de municipios (para cuando se filtra por departamento)
            tr.innerHTML = `
                <td><strong>${row.municipio}</strong></td>
                <td>${formatearNumero(row.padron)}</td>
                <td><strong class="text-primary">${formatearNumero(row.emitidos)}</strong></td>
                <td><span class="badge badge-success">${formatearNumero(row.validos)}</span></td>
                <td><span class="badge badge-danger">${formatearNumero(row.nulos)}</span></td>
                <td><span class="badge badge-warning">${formatearNumero(row.blanco)}</span></td>
                <td><strong>${row.participacion}%</strong></td>
                <td><span class="badge badge-secondary">${row.total_mesas}</span></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="verDetalleMunicipio('${escapeHtml(row.municipio)}', '${escapeHtml(row.departamento)}')">
                        <i class="bi bi-eye"></i> Ver Detalle
                    </button>
                </td>
            `;
        } else {
            // Vista de departamentos
            tr.innerHTML = `
                <td><strong>${row.departamento}</strong></td>
                <td>${formatearNumero(row.padron)}</td>
                <td><strong class="text-primary">${formatearNumero(row.emitidos)}</strong></td>
                <td><span class="badge badge-success">${formatearNumero(row.validos)}</span></td>
                <td><span class="badge badge-danger">${formatearNumero(row.nulos)}</span></td>
                <td><span class="badge badge-warning">${formatearNumero(row.blanco)}</span></td>
                <td><strong>${row.participacion}%</strong></td>
                <td><span class="badge badge-secondary">${row.total_municipios}</span></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="verDetalleDepartamento('${escapeHtml(row.departamento)}')">
                        <i class="bi bi-eye"></i> Ver Detalle
                    </button>
                </td>
            `;
        }
        
        tbody.appendChild(tr);
    });
    
    // Inicializar DataTables
    tablaResultados = $('#tablaResultados').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
        order: [[2, 'desc']], // Ordenar por votos emitidos
        responsive: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0, className: 'text-start' },
            { targets: -1, orderable: false } // Columna de acciones no ordenable
        ]
    });
    
    console.log('Tabla inicializada exitosamente');
}

/**
 * Función para escapar HTML y prevenir XSS
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Ver detalle de departamento
 */
function verDetalleDepartamento(departamento) {
    window.location.href = `detalle_departamento.php?departamento=${encodeURIComponent(departamento)}`;
}

/**
 * Ver detalle de municipio
 */
function verDetalleMunicipio(municipio, departamento) {
    window.location.href = `detalle_departamento.php?departamento=${encodeURIComponent(departamento)}&municipio=${encodeURIComponent(municipio)}`;
}

/**
 * Formatear números con separadores de miles
 */
function formatearNumero(numero) {
    return new Intl.NumberFormat('es-GT').format(numero);
}

/**
 * Animar números de las cards
 */
function animarNumeros() {
    const elementos = document.querySelectorAll('.stat-value');
    
    elementos.forEach(elemento => {
        const texto = elemento.textContent.replace(/,/g, '');
        const esNumero = !isNaN(texto.replace('%', ''));
        
        if (esNumero) {
            const esPorc = texto.includes('%');
            const valorFinal = parseFloat(texto.replace('%', ''));
            let valorActual = 0;
            const incremento = valorFinal / 50;
            
            const intervalo = setInterval(() => {
                valorActual += incremento;
                if (valorActual >= valorFinal) {
                    valorActual = valorFinal;
                    clearInterval(intervalo);
                }
                
                elemento.textContent = esPorc 
                    ? valorActual.toFixed(2) + '%'
                    : formatearNumero(Math.round(valorActual));
            }, 20);
        }
    });
}

/**
 * Mostrar mensaje de error
 */
function mostrarError(mensaje) {
    // Implementar según tu sistema de notificaciones
    console.error(mensaje);
}

/**
 * Cargar lista de departamentos en el filtro
 */
function cargarFiltroDepartamentos() {
    fetch('../ajax/obtener_datos_electorales.php?accion=lista_departamentos')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('filtroDepartamento');
                if (select) {
                    data.data.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept;
                        option.textContent = dept;
                        select.appendChild(option);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error al cargar departamentos:', error);
        });
}

// Cargar filtro de departamentos al inicio
document.addEventListener('DOMContentLoaded', cargarFiltroDepartamentos);