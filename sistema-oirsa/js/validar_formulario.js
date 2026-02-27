
/**
 * Validar y enviar formulario
 */
function validarFormulario(event) {
    event.preventDefault();
    
    const form = document.getElementById('contratoForm');
    const formData = new FormData(form);
    
    // Mostrar loading
    Swal.fire({
        title: 'Procesando...',
        text: 'Registrando contrato y generando PDF',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Enviar formulario
    fetch('../../api/procesar_formulario.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Contrato registrado!',
                text: data.message,
                showCancelButton: true,
                confirmButtonText: '<i class="fa-solid fa-file-pdf"></i> Descargar PDF',
                cancelButtonText: 'Ir al Dashboard',
                confirmButtonColor: '#667eea',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Descargar PDF
                    window.open(data.pdf_url, '_blank');
                }
                // Redirigir al dashboard
                window.location.href = 'dashboard.php';
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al procesar el formulario'
        });
    });
}
