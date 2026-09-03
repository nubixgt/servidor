import Swal from 'sweetalert2';

export function useConfirm() {
    /**
     * Muestra un diálogo de confirmación. Devuelve true si el usuario confirma.
     */
    async function confirm({
        title = '¿Confirmar acción?',
        text = '',
        confirmText = 'Sí, continuar',
        cancelText = 'Cancelar',
        icon = 'warning'
    } = {}) {
        const result = await Swal.fire({
            title,
            text,
            icon,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            reverseButtons: true,
            confirmButtonColor: '#ba1a1a',
            cancelButtonColor: '#565e74'
        });
        return result.isConfirmed;
    }

    return { confirm };
}
