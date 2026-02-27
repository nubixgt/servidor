/**
 * assets/js/dashboard_interactivo.js
 * Sistema de Filtros Cruzados para Dashboard Interactivo
 * v3.0 - Diseño Premium Dorado + Filtros Completos
 */

// Estado global de filtros
const FiltrosDashboard = {
    activo: false,
    tipo: null,
    valor: null,
    label: null
};

// Referencias a las gráficas
let chartEdades, chartGenero, chartAlfabetismo;

/**
 * Inicializar dashboard interactivo
 */
function inicializarDashboardInteractivo() {
    console.log('🚀 Iniciando Dashboard Interactivo v3.0 Premium...');

    // Verificar que existan los canvas
    if (!document.getElementById('grafica_edades')) {
        console.error('❌ No se encontró el canvas grafica_edades');
        return;
    }
    if (!document.getElementById('grafica_genero')) {
        console.error('❌ No se encontró el canvas grafica_genero');
        return;
    }
    if (!document.getElementById('grafica_alfabetismo')) {
        console.error('❌ No se encontró el canvas grafica_alfabetismo');
        return;
    }

    // Crear las gráficas con eventos de click
    crearGraficaEdadesInteractiva();
    crearGraficaGeneroInteractiva();
    crearGraficaAlfabetismoInteractiva();

    console.log('✅ Dashboard Interactivo inicializado correctamente');
}

/**
 * Crear gráfica de edades con interactividad - DISEÑO PREMIUM DORADO
 */
function crearGraficaEdadesInteractiva() {
    const ctx = document.getElementById('grafica_edades');
    if (!ctx) {
        console.error('❌ Canvas grafica_edades no encontrado');
        return;
    }

    chartEdades = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['18-25', '26-30', '31-35', '36-40', '41-45', '46-50', '51-55', '56-60', '61-65', '66-70', '70+'],
            datasets: [{
                label: 'Población por Edad',
                data: window.datosIniciales.edades,
                backgroundColor: 'rgba(212, 165, 116, 0.75)',
                borderColor: 'rgba(212, 165, 116, 1)',
                borderWidth: 2,
                borderRadius: 8,
                hoverBackgroundColor: 'rgba(201, 161, 103, 0.9)',
                hoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(212, 165, 116, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 12,
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 13 },
                    borderColor: 'rgba(212, 165, 116, 1)',
                    borderWidth: 2,
                    callbacks: {
                        label: function (context) {
                            return 'Población: ' + context.parsed.y.toLocaleString('es-GT');
                        },
                        afterLabel: function () {
                            return '👆 Click para filtrar';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(212, 165, 116, 0.1)'
                    },
                    ticks: {
                        color: '#6b6b6b',
                        callback: function (value) {
                            return value.toLocaleString('es-GT');
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#6b6b6b'
                    }
                }
            },
            onClick: (event, elements) => {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const rangoEdad = chartEdades.data.labels[index];
                    aplicarFiltro('edad', rangoEdad, `Rango: ${rangoEdad} años`);
                }
            },
            onHover: (event, elements) => {
                event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
            }
        }
    });
    
    console.log('✅ Gráfica de edades creada');
}

/**
 * Crear gráfica de género con interactividad - ROSADO Y AZUL
 */
function crearGraficaGeneroInteractiva() {
    const ctx = document.getElementById('grafica_genero');
    if (!ctx) {
        console.error('❌ Canvas grafica_genero no encontrado');
        return;
    }

    chartGenero = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Mujeres', 'Hombres'],
            datasets: [{
                data: window.datosIniciales.genero,
                backgroundColor: [
                    'rgba(236, 72, 153, 0.8)',  // 🌸 ROSADO para Mujeres
                    'rgba(59, 130, 246, 0.8)'   // 💙 AZUL para Hombres
                ],
                borderColor: [
                    'rgba(236, 72, 153, 1)',
                    'rgba(59, 130, 246, 1)'
                ],
                borderWidth: 3,
                hoverOffset: 20,
                hoverBorderWidth: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
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
                    backgroundColor: 'rgba(26, 26, 26, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 12,
                    borderColor: 'rgba(212, 165, 116, 0.5)',
                    borderWidth: 1,
                    callbacks: {
                        label: function (context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const porcentaje = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ${context.parsed.toLocaleString('es-GT')} (${porcentaje}%)`;
                        },
                        afterLabel: function () {
                            return '👆 Click para filtrar';
                        }
                    }
                }
            },
            onClick: (event, elements) => {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const genero = chartGenero.data.labels[index];
                    aplicarFiltro('genero', genero, genero);
                }
            },
            onHover: (event, elements) => {
                event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
            }
        }
    });
    
    console.log('✅ Gráfica de género creada');
}

/**
 * Crear gráfica de alfabetismo con interactividad - DISEÑO PREMIUM
 */
function crearGraficaAlfabetismoInteractiva() {
    const ctx = document.getElementById('grafica_alfabetismo');
    if (!ctx) {
        console.error('❌ Canvas grafica_alfabetismo no encontrado');
        return;
    }

    chartAlfabetismo = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Mujeres', 'Hombres'],
            datasets: [
                {
                    label: 'Alfabetas',
                    data: window.datosIniciales.alfabetismo.alfabetas,
                    backgroundColor: 'rgba(16, 185, 129, 0.75)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 2,
                    borderRadius: 8
                },
                {
                    label: 'Analfabetas',
                    data: window.datosIniciales.alfabetismo.analfabetas,
                    backgroundColor: 'rgba(239, 68, 68, 0.75)',
                    borderColor: 'rgba(239, 68, 68, 1)',
                    borderWidth: 2,
                    borderRadius: 8
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { 
                        padding: 15,
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
                    backgroundColor: 'rgba(26, 26, 26, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 12,
                    borderColor: 'rgba(212, 165, 116, 0.5)',
                    borderWidth: 1,
                    callbacks: {
                        label: function (context) {
                            return `${context.dataset.label}: ${context.parsed.y.toLocaleString('es-GT')}`;
                        },
                        afterLabel: function () {
                            return '👆 Click para filtrar';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(212, 165, 116, 0.1)'
                    },
                    ticks: {
                        color: '#6b6b6b',
                        callback: function (value) {
                            return value.toLocaleString('es-GT');
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#6b6b6b'
                    }
                }
            },
            onClick: (event, elements) => {
                if (elements.length > 0) {
                    const datasetIndex = elements[0].datasetIndex;
                    const generoIndex = elements[0].index;
                    const genero = chartAlfabetismo.data.labels[generoIndex];
                    const categoria = chartAlfabetismo.data.datasets[datasetIndex].label;

                    const valorFiltro = `${genero}-${categoria}`;
                    aplicarFiltro('alfabetismo', valorFiltro, `${genero} ${categoria}`);
                }
            },
            onHover: (event, elements) => {
                event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
            }
        }
    });
    
    console.log('✅ Gráfica de alfabetismo creada');
}

/**
 * Aplicar filtro al dashboard
 */
async function aplicarFiltro(tipo, valor, label) {
    console.log('🔍 Aplicando filtro:', { tipo, valor, label });

    // Actualizar estado
    FiltrosDashboard.activo = true;
    FiltrosDashboard.tipo = tipo;
    FiltrosDashboard.valor = valor;
    FiltrosDashboard.label = label;

    // Mostrar loading con SweetAlert2 dorado
    Swal.fire({
        title: 'Aplicando filtro...',
        text: `Filtrando por: ${label}`,
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Obtener datos filtrados
    obtenerDatosFiltrados(tipo, valor)
        .then(datos => {
            console.log('✅ Datos filtrados obtenidos:', datos);
            actualizarDashboard(datos);

            Swal.close();

            // Notificación de éxito
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                background: 'linear-gradient(135deg, #d4a574, #c9a167)',
                color: '#fff'
            });

            Toast.fire({
                icon: 'success',
                title: `Filtro aplicado: ${label}`
            });
        })
        .catch(error => {
            console.error('❌ Error al aplicar filtro:', error);
            Swal.fire({
                title: 'Error',
                text: 'No se pudo aplicar el filtro',
                icon: 'error',
                confirmButtonColor: '#d4a574'
            });
        });
}

/**
 * Obtener datos filtrados del servidor
 */
async function obtenerDatosFiltrados(tipo, valor) {
    try {
        const response = await fetch(
            `../ajax/graficas_datos.php?tipo=filtrado&filtro_tipo=${tipo}&filtro_valor=${encodeURIComponent(valor)}`,
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
        );

        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }

        const datos = await response.json();
        return datos;

    } catch (error) {
        console.error('Error al obtener datos filtrados:', error);
        throw error;
    }
}

/**
 * Actualizar todo el dashboard con datos filtrados
 */
function actualizarDashboard(datos) {
    console.log('📊 Actualizando dashboard completo con datos:', datos);

    // 1. Actualizar tarjetas estadísticas
    actualizarTarjetas(datos.estadisticas);

    // 2. Actualizar gráfica de edades
    if (chartEdades && datos.edades) {
        chartEdades.data.datasets[0].data = datos.edades.values;
        chartEdades.update('active');
    }

    // 3. Actualizar gráfica de género
    if (chartGenero && datos.genero) {
        chartGenero.data.datasets[0].data = datos.genero.values;
        chartGenero.update('active');
    }

    // 4. Actualizar gráfica de alfabetismo
    if (chartAlfabetismo && datos.alfabetismo) {
        chartAlfabetismo.data.datasets[0].data = datos.alfabetismo.alfabetas;
        chartAlfabetismo.data.datasets[1].data = datos.alfabetismo.analfabetas;
        chartAlfabetismo.update('active');
    }

    // 5. Actualizar tabla Top 5
    if (datos.top_municipios) {
        actualizarTablaTop5(datos.top_municipios);
    }
}

/**
 * Actualizar tarjetas estadísticas
 */
function actualizarTarjetas(estadisticas) {
    console.log('📊 Actualizando 4 tarjetas con:', estadisticas);

    // Tarjeta 1: Total personas
    const cardTotal = document.querySelector('.stat-card:nth-child(1) .stat-value');
    if (cardTotal) {
        cardTotal.textContent = formatearNumero(estadisticas.total_personas || 0);
    }

    // Tarjeta 2: Total mujeres
    const cardMujeres = document.querySelector('.stat-card:nth-child(2) .stat-value');
    const textMujeres = document.querySelector('.stat-card:nth-child(2) .stat-change');

    if (cardMujeres && textMujeres) {
        cardMujeres.textContent = formatearNumero(estadisticas.total_mujeres || 0);

        const porcentajeMujeres = estadisticas.total_personas > 0
            ? ((estadisticas.total_mujeres / estadisticas.total_personas) * 100).toFixed(1)
            : 0;

        textMujeres.innerHTML = `<i class="bi bi-arrow-up"></i> ${porcentajeMujeres}% del total`;
    }

    // Tarjeta 3: Total hombres
    const cardHombres = document.querySelector('.stat-card:nth-child(3) .stat-value');
    const textHombres = document.querySelector('.stat-card:nth-child(3) .stat-change');

    if (cardHombres && textHombres) {
        cardHombres.textContent = formatearNumero(estadisticas.total_hombres || 0);

        const porcentajeHombres = estadisticas.total_personas > 0
            ? ((estadisticas.total_hombres / estadisticas.total_personas) * 100).toFixed(1)
            : 0;

        textHombres.innerHTML = `<i class="bi bi-arrow-up"></i> ${porcentajeHombres}% del total`;
    }

    // Tarjeta 4: Total municipios
    const cardMunicipios = document.querySelector('.stat-card:nth-child(4) .stat-value');
    if (cardMunicipios) {
        cardMunicipios.textContent = formatearNumero(estadisticas.total_municipios || 0);
    }
}

/**
 * Actualizar tabla Top 5 Municipios - DISEÑO PREMIUM
 */
function actualizarTablaTop5(municipios) {
    const tbody = document.querySelector('#tablaTop5 tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';

    municipios.forEach((muni, index) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><span class="badge badge-primary">${index + 1}</span></td>
            <td>
                <strong>${muni.municipio}</strong><br>
                <small class="text-muted">${muni.departamento}</small>
            </td>
            <td><strong>${formatearNumero(muni.total)}</strong></td>
        `;
        tbody.appendChild(tr);
    });
}

/**
 * Limpiar todos los filtros
 */
function limpiarFiltros() {
    console.log('🧹 Limpiando filtros...');

    FiltrosDashboard.activo = false;
    FiltrosDashboard.tipo = null;
    FiltrosDashboard.valor = null;
    FiltrosDashboard.label = null;

    Swal.fire({
        title: 'Restaurando vista...',
        text: 'Cargando datos generales',
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    setTimeout(() => {
        location.reload();
    }, 500);
}

/**
 * Formatear número
 */
function formatearNumero(numero) {
    return new Intl.NumberFormat('es-GT').format(numero);
}

// Exponer funciones globalmente
window.DashboardInteractivo = {
    inicializar: inicializarDashboardInteractivo,
    aplicarFiltro,
    limpiarFiltros
};

window.limpiarFiltros = limpiarFiltros;

console.log('✅ dashboard_interactivo.js v3.0 cargado');