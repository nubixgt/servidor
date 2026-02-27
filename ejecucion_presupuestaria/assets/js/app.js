/**
 * Sistema de Ejecución Presupuestaria - MAGA
 * JavaScript Principal
 */

// =====================================================
// CONFIGURACIÓN GLOBAL
// =====================================================

const CONFIG = {
    animationDuration: 1500,
    chartColors: {
        primary: '#1a365d',
        secondary: '#3182ce',
        accent: '#63b3ed',
        success: '#38a169',
        warning: '#d69e2e',
        danger: '#e53e3e',
        gradient: ['#1a365d', '#2c5282', '#3182ce', '#63b3ed', '#90cdf4']
    },
    numberFormat: {
        locale: 'es-GT',
        currency: 'GTQ'
    }
};

// =====================================================
// UTILIDADES
// =====================================================

const Utils = {
    // Formatear número como moneda guatemalteca
    formatMoney(number, decimals = 2) {
        if (isNaN(number)) return 'Q 0.00';
        const formatted = new Intl.NumberFormat('es-GT', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(Math.abs(number));
        return (number < 0 ? '-Q ' : 'Q ') + formatted;
    },

    // Formatear porcentaje
    formatPercent(number, decimals = 2) {
        if (isNaN(number)) return '0,00%';
        return new Intl.NumberFormat('es-GT', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(number) + '%';
    },

    // Formatear número simple
    formatNumber(number, decimals = 2) {
        if (isNaN(number)) return '0';
        return new Intl.NumberFormat('es-GT', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(number);
    },

    // Obtener color de semáforo
    getSemaforoColor(percentage, thresholds = { green: 80, yellow: 60 }) {
        if (percentage >= thresholds.green) return 'success';
        if (percentage >= thresholds.yellow) return 'warning';
        return 'danger';
    },

    // Debounce para búsquedas
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Parsear número de string formateado
    parseFormattedNumber(str) {
        if (!str) return 0;
        return parseFloat(str.replace(/[^\d.,-]/g, '').replace(',', '.')) || 0;
    }
};

// =====================================================
// ANIMACIÓN DE CONTEO
// =====================================================

class CountUpAnimation {
    constructor(element, options = {}) {
        this.element = element;
        this.startValue = options.startValue || 0;
        this.endValue = options.endValue || parseFloat(element.dataset.value) || 0;
        this.duration = options.duration || CONFIG.animationDuration;
        this.prefix = options.prefix || element.dataset.prefix || '';
        this.suffix = options.suffix || element.dataset.suffix || '';
        this.decimals = options.decimals || parseInt(element.dataset.decimals) || 2;
        this.isMoney = element.dataset.money === 'true';
        this.isPercent = element.dataset.percent === 'true';
    }

    start() {
        const startTime = performance.now();
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / this.duration, 1);
            
            // Easing function (ease-out-cubic)
            const easeOut = 1 - Math.pow(1 - progress, 3);
            const currentValue = this.startValue + (this.endValue - this.startValue) * easeOut;
            
            this.updateDisplay(currentValue);
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    }

    updateDisplay(value) {
        let displayValue;
        
        if (this.isMoney) {
            displayValue = Utils.formatMoney(value, this.decimals);
        } else if (this.isPercent) {
            displayValue = Utils.formatPercent(value, this.decimals);
        } else {
            displayValue = this.prefix + Utils.formatNumber(value, this.decimals) + this.suffix;
        }
        
        this.element.textContent = displayValue;
    }
}

// Inicializar animaciones de conteo
function initCountUpAnimations() {
    const elements = document.querySelectorAll('[data-countup]');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const animation = new CountUpAnimation(entry.target);
                animation.start();
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    
    elements.forEach(el => observer.observe(el));
}

// =====================================================
// GRÁFICAS CON CHART.JS
// =====================================================

const ChartManager = {
    charts: {},

    // Crear gráfica de barras horizontal
    createHorizontalBar(canvasId, data, options = {}) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        if (this.charts[canvasId]) {
            this.charts[canvasId].destroy();
        }

        this.charts[canvasId] = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: options.label || 'Valor',
                    data: data.values,
                    backgroundColor: data.colors || CONFIG.chartColors.gradient,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                if (options.isMoney) {
                                    return Utils.formatMoney(context.raw);
                                } else if (options.isPercent) {
                                    return Utils.formatPercent(context.raw);
                                }
                                return context.raw;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: {
                            callback: (value) => {
                                if (options.isMoney) {
                                    return Utils.formatMoney(value, 0);
                                } else if (options.isPercent) {
                                    return value + '%';
                                }
                                return value;
                            }
                        }
                    },
                    y: {
                        grid: { display: false }
                    }
                }
            }
        });

        return this.charts[canvasId];
    },

    // Crear gráfica de dona
    createDoughnut(canvasId, data, options = {}) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        if (this.charts[canvasId]) {
            this.charts[canvasId].destroy();
        }

        this.charts[canvasId] = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: data.colors || CONFIG.chartColors.gradient,
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.raw / total) * 100).toFixed(1);
                                if (options.isMoney) {
                                    return `${context.label}: ${Utils.formatMoney(context.raw)} (${percentage}%)`;
                                }
                                return `${context.label}: ${context.raw} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        return this.charts[canvasId];
    },

    // Crear gráfica de línea
    createLineChart(canvasId, data, options = {}) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        if (this.charts[canvasId]) {
            this.charts[canvasId].destroy();
        }

        this.charts[canvasId] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: data.datasets.map((dataset, index) => ({
                    label: dataset.label,
                    data: dataset.values,
                    borderColor: dataset.color || CONFIG.chartColors.gradient[index],
                    backgroundColor: (dataset.color || CONFIG.chartColors.gradient[index]) + '20',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { usePointStyle: true }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });

        return this.charts[canvasId];
    },

    // Crear gráfica de gauge (medidor)
    createGauge(canvasId, value, options = {}) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        if (this.charts[canvasId]) {
            this.charts[canvasId].destroy();
        }

        const color = Utils.getSemaforoColor(value);
        const colorMap = {
            success: CONFIG.chartColors.success,
            warning: CONFIG.chartColors.warning,
            danger: CONFIG.chartColors.danger
        };

        this.charts[canvasId] = new Chart(ctx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [value, 100 - value],
                    backgroundColor: [colorMap[color], '#e2e8f0'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                circumference: 180,
                rotation: 270,
                cutout: '75%',
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }
                }
            }
        });

        return this.charts[canvasId];
    }
};

// =====================================================
// TABLA INTERACTIVA
// =====================================================

class DataTable {
    constructor(tableId, options = {}) {
        this.table = document.getElementById(tableId);
        if (!this.table) return;
        
        this.options = {
            sortable: true,
            filterable: true,
            paginate: true,
            pageSize: 10,
            ...options
        };
        
        this.data = [];
        this.filteredData = [];
        this.currentPage = 1;
        this.sortColumn = null;
        this.sortDirection = 'asc';
        
        this.init();
    }

    init() {
        if (this.options.sortable) {
            this.initSorting();
        }
    }

    initSorting() {
        const headers = this.table.querySelectorAll('th.sortable');
        headers.forEach((header, index) => {
            header.addEventListener('click', () => this.sort(index));
        });
    }

    sort(columnIndex) {
        const tbody = this.table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        if (this.sortColumn === columnIndex) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = columnIndex;
            this.sortDirection = 'asc';
        }

        rows.sort((a, b) => {
            const aValue = a.cells[columnIndex].textContent.trim();
            const bValue = b.cells[columnIndex].textContent.trim();
            
            // Intentar parsear como número
            const aNum = Utils.parseFormattedNumber(aValue);
            const bNum = Utils.parseFormattedNumber(bValue);
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return this.sortDirection === 'asc' ? aNum - bNum : bNum - aNum;
            }
            
            return this.sortDirection === 'asc' 
                ? aValue.localeCompare(bValue, 'es')
                : bValue.localeCompare(aValue, 'es');
        });

        // Actualizar iconos de ordenamiento
        this.table.querySelectorAll('th.sortable').forEach((th, i) => {
            th.classList.remove('sorted', 'asc', 'desc');
            if (i === columnIndex) {
                th.classList.add('sorted', this.sortDirection);
            }
        });

        // Reinsertar filas ordenadas
        rows.forEach(row => tbody.appendChild(row));
    }

    filter(searchText) {
        const tbody = this.table.querySelector('tbody');
        const rows = tbody.querySelectorAll('tr');
        const searchLower = searchText.toLowerCase();

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchLower) ? '' : 'none';
        });
    }
}

// =====================================================
// FILTROS DINÁMICOS
// =====================================================

class FilterManager {
    constructor(formId, callback) {
        this.form = document.getElementById(formId);
        if (!this.form) return;
        
        this.callback = callback;
        this.init();
    }

    init() {
        // Escuchar cambios en los selects
        this.form.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', () => this.applyFilters());
        });

        // Escuchar input en campos de texto
        this.form.querySelectorAll('input[type="text"]').forEach(input => {
            input.addEventListener('input', Utils.debounce(() => this.applyFilters(), 300));
        });

        // Botón de limpiar filtros
        const clearBtn = this.form.querySelector('[data-clear-filters]');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => this.clearFilters());
        }
    }

    applyFilters() {
        const formData = new FormData(this.form);
        const filters = {};
        
        for (let [key, value] of formData.entries()) {
            if (value) filters[key] = value;
        }
        
        if (this.callback) {
            this.callback(filters);
        }
    }

    clearFilters() {
        this.form.reset();
        if (this.callback) {
            this.callback({});
        }
    }

    getFilters() {
        const formData = new FormData(this.form);
        const filters = {};
        
        for (let [key, value] of formData.entries()) {
            if (value) filters[key] = value;
        }
        
        return filters;
    }
}

// =====================================================
// MODALES
// =====================================================

class Modal {
    constructor(modalId) {
        this.overlay = document.getElementById(modalId);
        if (!this.overlay) return;
        
        this.modal = this.overlay.querySelector('.modal');
        this.init();
    }

    init() {
        // Cerrar al hacer clic en el overlay
        this.overlay.addEventListener('click', (e) => {
            if (e.target === this.overlay) {
                this.close();
            }
        });

        // Cerrar con botón
        const closeBtn = this.overlay.querySelector('.modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.close());
        }

        // Cerrar con Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen()) {
                this.close();
            }
        });
    }

    open() {
        this.overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    close() {
        this.overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    isOpen() {
        return this.overlay.classList.contains('active');
    }
}

// =====================================================
// NOTIFICACIONES TOAST
// =====================================================

const Toast = {
    container: null,

    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
    },

    show(message, type = 'info', duration = 5000) {
        this.init();

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-times-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };

        toast.innerHTML = `
            <i class="${icons[type]}"></i>
            <span>${message}</span>
        `;

        this.container.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, duration);
    },

    success(message) { this.show(message, 'success'); },
    error(message) { this.show(message, 'error'); },
    warning(message) { this.show(message, 'warning'); },
    info(message) { this.show(message, 'info'); }
};

// =====================================================
// PETICIONES AJAX
// =====================================================

const API = {
    baseUrl: 'api/',

    async request(endpoint, options = {}) {
        const url = this.baseUrl + endpoint;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        try {
            const response = await fetch(url, { ...defaultOptions, ...options });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Error en la petición');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    async get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    },

    async post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    async put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }
};

// =====================================================
// LOADER
// =====================================================

const Loader = {
    element: null,

    show() {
        if (!this.element) {
            this.element = document.createElement('div');
            this.element.className = 'loader-overlay';
            this.element.innerHTML = '<div class="loader"></div>';
        }
        document.body.appendChild(this.element);
    },

    hide() {
        if (this.element && this.element.parentNode) {
            this.element.parentNode.removeChild(this.element);
        }
    }
};

// =====================================================
// EXPORTAR A EXCEL
// =====================================================

function exportToExcel(tableId, filename = 'reporte') {
    const table = document.getElementById(tableId);
    if (!table) return;

    const wb = XLSX.utils.table_to_book(table, { sheet: "Datos" });
    XLSX.writeFile(wb, `${filename}_${new Date().toISOString().split('T')[0]}.xlsx`);
    
    Toast.success('Archivo Excel generado correctamente');
}

// =====================================================
// IMPRIMIR
// =====================================================

function printTable(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Impresión - Sistema de Ejecución Presupuestaria</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background: #1a365d; color: white; }
                tr:nth-child(even) { background: #f9f9f9; }
                .header-print { text-align: center; margin-bottom: 20px; }
                .header-print h1 { color: #1a365d; }
                @media print { button { display: none; } }
            </style>
        </head>
        <body>
            <div class="header-print">
                <h1>Ministerio de Agricultura, Ganadería y Alimentación</h1>
                <h2>Ejecución Presupuestaria</h2>
                <p>Fecha de impresión: ${new Date().toLocaleDateString('es-GT')}</p>
            </div>
            ${element.outerHTML}
            <script>window.print();</script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

// =====================================================
// INICIALIZACIÓN GLOBAL
// =====================================================

document.addEventListener('DOMContentLoaded', () => {
    // Inicializar animaciones de conteo
    initCountUpAnimations();
    
    // Agregar clase active al nav link actual
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });

    // Inicializar tooltips si existen
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(el => {
        // Implementar tooltip simple
    });

    console.log('🚀 Sistema de Ejecución Presupuestaria iniciado');
});

// Exportar para uso global
window.Utils = Utils;
window.ChartManager = ChartManager;
window.DataTable = DataTable;
window.FilterManager = FilterManager;
window.Modal = Modal;
window.Toast = Toast;
window.API = API;
window.Loader = Loader;
window.exportToExcel = exportToExcel;
window.printTable = printTable;
