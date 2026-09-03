import Swal from 'sweetalert2';

/**
 * Diálogos centrados de SweetAlert (más prominentes que los toasts).
 * Para feedback breve seguí usando useToast(); useAlert() es para
 * confirmaciones de éxito/error que el usuario debe reconocer.
 */
export function useAlert() {
    return {
        success: (title, text = '') =>
            Swal.fire({
                icon: 'success',
                title,
                text,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#506600'
            }),
        error: (title, text = '') =>
            Swal.fire({
                icon: 'error',
                title,
                text,
                confirmButtonText: 'Cerrar',
                confirmButtonColor: '#ba1a1a'
            }),
        info: (title, text = '') =>
            Swal.fire({
                icon: 'info',
                title,
                text,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#506600'
            })
    };
}
