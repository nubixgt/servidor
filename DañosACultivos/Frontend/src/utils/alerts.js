import Swal from 'sweetalert2';

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3200,
    timerProgressBar: true,
});

export const toastSuccess = (message) => toast.fire({ icon: 'success', title: message });
export const toastError = (message) => toast.fire({ icon: 'error', title: message, timer: 4200 });
export const toastInfo = (message) => toast.fire({ icon: 'info', title: message });

export const alertError = (message, title = 'Ocurrió un error') =>
    Swal.fire({ icon: 'error', title, text: message });

export const confirmDialog = (message, { title = '¿Estás seguro?', danger = false, confirmText = 'Confirmar' } = {}) =>
    Swal.fire({
        icon: danger ? 'warning' : 'question',
        title,
        text: message,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancelar',
        confirmButtonColor: danger ? '#b94444' : '#00865d',
    }).then((r) => r.isConfirmed);
