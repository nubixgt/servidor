import Swal from 'sweetalert2';

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (el) => {
        el.addEventListener('mouseenter', Swal.stopTimer);
        el.addEventListener('mouseleave', Swal.resumeTimer);
    }
});

export function useToast() {
    return {
        success: (title) => Toast.fire({ icon: 'success', title }),
        error: (title) => Toast.fire({ icon: 'error', title }),
        info: (title) => Toast.fire({ icon: 'info', title }),
        warning: (title) => Toast.fire({ icon: 'warning', title })
    };
}
