import Swal from 'sweetalert2';

const toastBase = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3200,
    timerProgressBar: true,
    background: '#0c1e17',
    color: '#ffffff',
});

export function toastSuccess(message) {
    return toastBase.fire({ icon: 'success', title: message });
}

export function toastError(message) {
    return toastBase.fire({ icon: 'error', title: message, timer: 4200 });
}

export function toastInfo(message) {
    return toastBase.fire({ icon: 'info', title: message });
}

export function alertError(message, title = 'Ocurrió un error') {
    return Swal.fire({
        icon: 'error', title, text: message,
        confirmButtonColor: '#22c55e',
        background: '#0c1e17', color: '#ffffff',
    });
}

export function confirmDialog(message, { title = '¿Estás seguro?', danger = false, confirmText = 'Confirmar' } = {}) {
    return Swal.fire({
        icon: danger ? 'warning' : 'question',
        title,
        text: message,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancelar',
        confirmButtonColor: danger ? '#ef4444' : '#22c55e',
        cancelButtonColor: 'rgba(255,255,255,0.18)',
        background: '#0c1e17', color: '#ffffff',
    }).then((r) => r.isConfirmed);
}
