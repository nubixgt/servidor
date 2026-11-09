import Swal from 'sweetalert2';

// SweetAlert2 con el tema azul/negro de SANAFER.
// Usar estas funciones en lugar de alert() / confirm() / prompt() del navegador.
const base = Swal.mixin({
    background: '#111d30',
    color: '#e3edfc',
    confirmButtonColor: '#286be8',
    cancelButtonColor: '#213653',
    customClass: { popup: 'swal-sanafer' },
});

const toast = base.mixin({
    toast: true,
    position: 'bottom',
    showConfirmButton: false,
    showCloseButton: true,
    timer: 4500,
    timerProgressBar: true,
});

// Aviso temporal. icon: 'success' | 'error' | 'warning' | 'info'
export const notify = (icon, title) => toast.fire({ icon, title });

// Ventana de aviso con botón "Aceptar".
export const alertBox = (icon, title, text = '') =>
    base.fire({ icon, title, text, confirmButtonText: 'Aceptar' });

// Confirmación. Devuelve true si el usuario acepta.
export const confirmBox = async (title, text = '', confirmText = 'Sí, continuar') => {
    const { isConfirmed } = await base.fire({
        icon: 'question',
        title,
        text,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
    });
    return isConfirmed;
};

export default base;
