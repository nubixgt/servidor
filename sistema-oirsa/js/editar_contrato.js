// Validar formulario de edición
function validarFormulario(event) {
    event.preventDefault();

    const numeroContrato = document.getElementById('numeroContrato').value.trim();
    const servicios = document.getElementById('servicios').value;
    const iva = document.getElementById('iva').value;
    const fondos = document.getElementById('fondos').value;
    const armonizacion = document.getElementById('armonizacion').value;
    const fechaContrato = document.getElementById('fechaContrato').value;
    const nombreCompleto = document.getElementById('nombreCompleto').value.trim();
    const edad = document.getElementById('edad').value;
    const estadoCivil = document.getElementById('estadoCivil').value;
    const profesion = document.getElementById('profesion').value.trim();
    const domicilio = document.getElementById('domicilio').value.trim();
    const dpi = document.getElementById('dpi').value.replace(/\s/g, '');
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    const montoTotal = document.getElementById('montoTotal').value;
    const numeroPagos = document.getElementById('numeroPagos').value;
    const montoPago = document.getElementById('montoPago').value;

    // Validaciones básicas
    if (!numeroContrato || !servicios || !iva || !fondos || !armonizacion || !fechaContrato) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos incompletos',
            text: 'Por favor, complete todos los campos obligatorios del contrato',
            confirmButtonColor: '#667eea'
        });
        return false;
    }

    if (armonizacion === 'Otro') {
        const armonizacionOtro = document.getElementById('armonizacionOtro').value.trim();
        if (!armonizacionOtro) {
            Swal.fire({
                icon: 'warning',
                title: 'Campo requerido',
                text: 'Por favor, especifica la armonización personalizada',
                confirmButtonColor: '#667eea'
            });
            return false;
        }
    }

    if (!nombreCompleto || !edad || !estadoCivil || !profesion || !domicilio) {
        Swal.fire({
            icon: 'warning',
            title: 'Datos del contratista incompletos',
            text: 'Por favor, complete todos los datos del contratista',
            confirmButtonColor: '#667eea'
        });
        return false;
    }

    if (edad < 18) {
        Swal.fire({
            icon: 'warning',
            title: 'Edad inválida',
            text: 'La edad debe ser mayor a 18 años',
            confirmButtonColor: '#667eea'
        });
        return false;
    }

    if (dpi.length !== 13) {
        Swal.fire({
            icon: 'warning',
            title: 'DPI inválido',
            text: 'El DPI debe tener 13 dígitos',
            confirmButtonColor: '#667eea'
        });
        return false;
    }

    if (!fechaInicio || !fechaFin) {
        Swal.fire({
            icon: 'warning',
            title: 'Fechas requeridas',
            text: 'Por favor, ingresa las fechas de inicio y finalización',
            confirmButtonColor: '#667eea'
        });
        return false;
    }

    if (new Date(fechaFin) <= new Date(fechaInicio)) {
        Swal.fire({
            icon: 'warning',
            title: 'Fechas inválidas',
            text: 'La fecha de finalización debe ser posterior a la fecha de inicio',
            confirmButtonColor: '#667eea'
        });
        return false;
    }

    if (!montoTotal || !numeroPagos || !montoPago) {
        Swal.fire({
            icon: 'warning',
            title: 'Datos financieros incompletos',
            text: 'Por favor, complete todos los datos financieros',
            confirmButtonColor: '#667eea'
        });
        return false;
    }

    // Si todo está correcto, enviar formulario
    actualizarContrato();
}

function actualizarContrato() {
    const formData = new FormData(document.getElementById('editarContratoForm'));

    Swal.fire({
        title: 'Actualizando contrato...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('../../api/actualizar_contrato.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Contrato actualizado!',
                text: data.message,
                confirmButtonColor: '#667eea',
                confirmButtonText: 'Ir a Contratos'
            }).then(() => {
                window.location.href = 'contratos.php';
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                confirmButtonColor: '#667eea'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al actualizar el contrato',
            confirmButtonColor: '#667eea'
        });
        console.error('Error:', error);
    });
}

// Formatear DPI al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    const dpiInput = document.getElementById('dpi');
    if (dpiInput && dpiInput.value) {
        formatearDPI(dpiInput);
    }
    
    // Formatear montos al cargar
    const montoTotal = document.getElementById('montoTotal');
    if (montoTotal && montoTotal.value) {
        formatearMonto(montoTotal);
    }
    
    const montoPago = document.getElementById('montoPago');
    if (montoPago && montoPago.value) {
        formatearMontoPago(montoPago);
    }
    
    const numeroPagos = document.getElementById('numeroPagos');
    if (numeroPagos && numeroPagos.value) {
        mostrarNumeroPagos(numeroPagos);
    }
});

// Función para confirmar cancelación
function confirmarCancelar() {
    Swal.fire({
        title: '¿Cancelar edición?',
        text: 'Si sale ahora, los cambios no guardados se perderán',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, salir',
        cancelButtonText: 'Continuar editando'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'contratos.php';
        }
    });
}
