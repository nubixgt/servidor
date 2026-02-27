/**
 * js/graficas.js
 * Configuraciones y funciones para gráficas
 * Sistema de Registro de Empadronados
 */

// Colores del tema
const COLORES = {
    primario: '#667eea',
    secundario: '#764ba2',
    exito: '#28a745',
    peligro: '#dc3545',
    advertencia: '#ffc107',
    info: '#17a2b8',
    azul: 'rgba(102, 126, 234, 0.8)',
    morado: 'rgba(118, 75, 162, 0.8)',
    verde: 'rgba(40, 167, 69, 0.8)',
    rojo: 'rgba(220, 53, 69, 0.8)',
    naranja: 'rgba(255, 193, 7, 0.8)',
    celeste: 'rgba(23, 162, 184, 0.8)'
};

// Configuración base para todas las gráficas
const CONFIG_BASE = {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
        legend: {
            display: true,
            position: 'bottom',
            labels: {
                padding: 15,
                font: {
                    size: 12,
                    family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                },
                usePointStyle: true
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
            displayColors: true,
            borderColor: 'rgba(255, 255, 255, 0.1)',
            borderWidth: 1
        }
    }
};

/**
 * Crear gráfica de barras
 */
function crearGraficaBarras(canvasId, datos, opciones = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    const config = {
        type: 'bar',
        data: {
            labels: datos.labels,
            datasets: [{
                label: datos.label || 'Datos',
                data: datos.values,
                backgroundColor: datos.backgroundColor || COLORES.azul,
                borderColor: datos.borderColor || COLORES.primario,
                borderWidth: 2,
                borderRadius: 5
            }]
        },
        options: {
            ...CONFIG_BASE,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return value.toLocaleString('es-GT');
                        }
                    }
                }
            },
            ...opciones
        }
    };

    return new Chart(ctx, config);
}

/**
 * Crear gráfica de líneas
 */
function crearGraficaLineas(canvasId, datos, opciones = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    const config = {
        type: 'line',
        data: {
            labels: datos.labels,
            datasets: [{
                label: datos.label || 'Datos',
                data: datos.values,
                backgroundColor: datos.backgroundColor || 'rgba(102, 126, 234, 0.1)',
                borderColor: datos.borderColor || COLORES.primario,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: COLORES.primario,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            ...CONFIG_BASE,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return value.toLocaleString('es-GT');
                        }
                    }
                }
            },
            ...opciones
        }
    };

    return new Chart(ctx, config);
}

/**
 * Crear gráfica de pie/dona
 */
function crearGraficaPie(canvasId, datos, tipo = 'doughnut', opciones = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    const config = {
        type: tipo,
        data: {
            labels: datos.labels,
            datasets: [{
                data: datos.values,
                backgroundColor: datos.backgroundColor || [
                    COLORES.azul,
                    COLORES.morado,
                    COLORES.verde,
                    COLORES.rojo,
                    COLORES.naranja,
                    COLORES.celeste
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            ...CONFIG_BASE,
            plugins: {
                ...CONFIG_BASE.plugins,
                tooltip: {
                    ...CONFIG_BASE.plugins.tooltip,
                    callbacks: {
                        label: function (context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const porcentaje = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value.toLocaleString('es-GT')} (${porcentaje}%)`;
                        }
                    }
                }
            },
            ...opciones
        }
    };

    return new Chart(ctx, config);
}

/**
 * Crear gráfica de barras horizontales
 */
function crearGraficaBarrasHorizontales(canvasId, datos, opciones = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    const config = {
        type: 'bar',
        data: {
            labels: datos.labels,
            datasets: [{
                label: datos.label || 'Datos',
                data: datos.values,
                backgroundColor: datos.backgroundColor || COLORES.azul,
                borderColor: datos.borderColor || COLORES.primario,
                borderWidth: 2,
                borderRadius: 5
            }]
        },
        options: {
            ...CONFIG_BASE,
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return value.toLocaleString('es-GT');
                        }
                    }
                }
            },
            ...opciones
        }
    };

    return new Chart(ctx, config);
}

/**
 * Crear gráfica de barras agrupadas
 */
function crearGraficaBarrasAgrupadas(canvasId, datos, opciones = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    const config = {
        type: 'bar',
        data: {
            labels: datos.labels,
            datasets: datos.datasets.map((dataset, index) => ({
                label: dataset.label,
                data: dataset.data,
                backgroundColor: dataset.backgroundColor || [COLORES.azul, COLORES.morado, COLORES.verde][index],
                borderColor: dataset.borderColor || [COLORES.primario, COLORES.secundario, COLORES.exito][index],
                borderWidth: 2,
                borderRadius: 5
            }))
        },
        options: {
            ...CONFIG_BASE,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return value.toLocaleString('es-GT');
                        }
                    }
                }
            },
            ...opciones
        }
    };

    return new Chart(ctx, config);
}

/**
 * Crear gráfica de barras apiladas
 */
function crearGraficaBarrasApiladas(canvasId, datos, opciones = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    const config = {
        type: 'bar',
        data: {
            labels: datos.labels,
            datasets: datos.datasets.map((dataset, index) => ({
                label: dataset.label,
                data: dataset.data,
                backgroundColor: dataset.backgroundColor || [COLORES.azul, COLORES.morado, COLORES.verde][index],
                borderColor: dataset.borderColor || [COLORES.primario, COLORES.secundario, COLORES.exito][index],
                borderWidth: 2,
                borderRadius: 5
            }))
        },
        options: {
            ...CONFIG_BASE,
            scales: {
                x: {
                    stacked: true
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return value.toLocaleString('es-GT');
                        }
                    }
                }
            },
            ...opciones
        }
    };

    return new Chart(ctx, config);
}

/**
 * Actualizar datos de una gráfica existente
 */
function actualizarGrafica(grafica, nuevosDatos) {
    if (!grafica) return;

    grafica.data.labels = nuevosDatos.labels;
    if (nuevosDatos.datasets) {
        grafica.data.datasets = nuevosDatos.datasets;
    } else {
        grafica.data.datasets[0].data = nuevosDatos.values;
    }

    grafica.update('active');
}

/**
 * Destruir gráfica
 */
function destruirGrafica(grafica) {
    if (grafica) {
        grafica.destroy();
    }
}

/**
 * Obtener gradiente para gráficas
 */
function obtenerGradiente(ctx, colorInicio, colorFin) {
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, colorInicio);
    gradient.addColorStop(1, colorFin);
    return gradient;
}

/**
 * Exportar gráfica como imagen
 */
function exportarGraficaImagen(canvasId, nombreArchivo = 'grafica') {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    const url = canvas.toDataURL('image/png');
    const link = document.createElement('a');
    link.download = `${nombreArchivo}.png`;
    link.href = url;
    link.click();
}

// Exponer funciones globalmente
window.Graficas = {
    crearGraficaBarras,
    crearGraficaLineas,
    crearGraficaPie,
    crearGraficaBarrasHorizontales,
    crearGraficaBarrasAgrupadas,
    crearGraficaBarrasApiladas,
    actualizarGrafica,
    destruirGrafica,
    obtenerGradiente,
    exportarGraficaImagen,
    COLORES
};