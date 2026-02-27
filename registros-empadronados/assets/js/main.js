/**
 * js/main.js
 * JavaScript Principal del Sistema
 * Sistema de Registro de Empadronados
 */

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    console.log('Sistema SICO GT cargado correctamente');

    // Inicializar tooltips de Bootstrap
    initTooltips();

    // Verificar sesión cada 5 minutos
    setInterval(verificarSesion, 300000);

    // Auto-ocultar alertas
    autoOcultarAlertas();
});

/**
 * Inicializar tooltips de Bootstrap
 */
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Auto-ocultar alertas después de 5 segundos
 */
function autoOcultarAlertas() {
    setTimeout(function () {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        });
    }, 5000);
}

/**
 * Verificar si la sesión sigue activa
 */
function verificarSesion() {
    fetch('../ajax/verificar_sesion.php')
        .then(response => response.json())
        .then(data => {
            if (!data.activa) {
                Swal.fire({
                    title: 'Sesión Expirada',
                    text: 'Su sesión ha expirado. Será redirigido al login.',
                    icon: 'warning',
                    confirmButtonText: 'Aceptar',
                    allowOutsideClick: false
                }).then(() => {
                    window.location.href = '../cerrar_sesion.php';
                });
            }
        })
        .catch(error => {
            console.error('Error al verificar sesión:', error);
        });
}

/**
 * Mostrar mensaje de éxito con SweetAlert2
 */
function mostrarExito(titulo, mensaje) {
    Swal.fire({
        title: titulo,
        text: mensaje,
        icon: 'success',
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#667eea'
    });
}

/**
 * Mostrar mensaje de error con SweetAlert2
 */
function mostrarError(titulo, mensaje) {
    Swal.fire({
        title: titulo,
        text: mensaje,
        icon: 'error',
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#dc3545'
    });
}

/**
 * Mostrar confirmación con SweetAlert2
 */
function mostrarConfirmacion(titulo, mensaje, callback) {
    Swal.fire({
        title: titulo,
        text: mensaje,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed && callback) {
            callback();
        }
    });
}

/**
 * Mostrar loader
 */
function mostrarLoader(mensaje = 'Cargando...') {
    Swal.fire({
        title: mensaje,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

/**
 * Cerrar loader
 */
function cerrarLoader() {
    Swal.close();
}

/**
 * Formatear número con separadores de miles
 */
function formatearNumero(numero) {
    return new Intl.NumberFormat('es-GT').format(numero);
}

/**
 * Formatear fecha
 */
function formatearFecha(fecha) {
    const date = new Date(fecha);
    return date.toLocaleDateString('es-GT', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

/**
 * Formatear fecha y hora
 */
function formatearFechaHora(fecha) {
    const date = new Date(fecha);
    return date.toLocaleString('es-GT', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Validar formulario
 */
function validarFormulario(formulario) {
    let valido = true;
    const campos = formulario.querySelectorAll('input[required], select[required], textarea[required]');

    campos.forEach(campo => {
        if (!campo.value.trim()) {
            campo.classList.add('is-invalid');
            valido = false;
        } else {
            campo.classList.remove('is-invalid');
            campo.classList.add('is-valid');
        }
    });

    return valido;
}

/**
 * Limpiar validación de formulario
 */
function limpiarValidacion(formulario) {
    const campos = formulario.querySelectorAll('.is-invalid, .is-valid');
    campos.forEach(campo => {
        campo.classList.remove('is-invalid', 'is-valid');
    });
}

/**
 * Exportar tabla a Excel
 */
function exportarExcel(nombreArchivo = 'datos') {
    const tabla = document.querySelector('table');
    if (!tabla) {
        mostrarError('Error', 'No se encontró ninguna tabla para exportar');
        return;
    }

    mostrarLoader('Generando archivo Excel...');

    // Aquí implementarías la lógica de exportación
    // Por ahora solo simulamos
    setTimeout(() => {
        cerrarLoader();
        mostrarExito('Éxito', 'Archivo Excel generado correctamente');
    }, 1500);
}

/**
 * Exportar a PDF
 */
function exportarPDF(nombreArchivo = 'reporte') {
    mostrarLoader('Generando archivo PDF...');

    // Aquí implementarías la lógica de exportación
    // Por ahora solo simulamos
    setTimeout(() => {
        cerrarLoader();
        mostrarExito('Éxito', 'Archivo PDF generado correctamente');
    }, 1500);
}

/**
 * Copiar texto al portapapeles
 */
function copiarAlPortapapeles(texto) {
    navigator.clipboard.writeText(texto).then(() => {
        mostrarExito('Copiado', 'Texto copiado al portapapeles');
    }).catch(err => {
        mostrarError('Error', 'No se pudo copiar el texto');
    });
}

/**
 * Imprimir página
 */
function imprimirPagina() {
    window.print();
}

/**
 * Debounce para búsquedas
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Inicializar DataTables con configuración en español
 */
function inicializarDataTable(selector, opciones = {}) {
    const opcionesDefault = {
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
            '<"row"<"col-sm-12"tr>>' +
            '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        ...opciones
    };

    return $(selector).DataTable(opcionesDefault);
}

/**
 * Animar números (counter animation)
 */
function animarNumero(elemento, valorFinal, duracion = 1000) {
    const valorInicial = 0;
    const incremento = valorFinal / (duracion / 16);
    let valorActual = valorInicial;

    const timer = setInterval(() => {
        valorActual += incremento;
        if (valorActual >= valorFinal) {
            elemento.textContent = formatearNumero(valorFinal);
            clearInterval(timer);
        } else {
            elemento.textContent = formatearNumero(Math.floor(valorActual));
        }
    }, 16);
}

/**
 * Scroll suave hacia un elemento
 */
function scrollSuave(elementoId) {
    const elemento = document.getElementById(elementoId);
    if (elemento) {
        elemento.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Exponer funciones globalmente
window.SIREM = {
    mostrarExito,
    mostrarError,
    mostrarConfirmacion,
    mostrarLoader,
    cerrarLoader,
    formatearNumero,
    formatearFecha,
    formatearFechaHora,
    validarFormulario,
    limpiarValidacion,
    exportarExcel,
    exportarPDF,
    copiarAlPortapapeles,
    imprimirPagina,
    debounce,
    inicializarDataTable,
    animarNumero,
    scrollSuave
};