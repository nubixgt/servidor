/**
 * Función global para confirmar cierre de sesión con SweetAlert2
 * Sistema SICO GT
 */

function confirmarCerrarSesion(event) {
    event.preventDefault();

    Swal.fire({
        title: '¿Cerrar sesión?',
        text: '¿Está seguro que desea cerrar su sesión?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, cerrar sesión',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#6c757d',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar mensaje de despedida
            Swal.fire({
                title: '¡Hasta pronto!',
                text: 'Cerrando sesión...',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                allowOutsideClick: false
            }).then(() => {
                // Obtener la ruta correcta según la ubicación actual
                const path = window.location.pathname;
                let rutaCerrarSesion = '../cerrar_sesion.php';

                // Si estamos en la raíz o en vistas
                if (path.includes('/vistas/')) {
                    rutaCerrarSesion = '../cerrar_sesion.php';
                }
                // Si estamos en admin
                else if (path.includes('/admin/')) {
                    rutaCerrarSesion = '../cerrar_sesion.php';
                }

                window.location.href = rutaCerrarSesion;
            });
        }
    });

    return false;
}