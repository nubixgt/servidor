import Swal from 'sweetalert2';

export const swal = Swal.mixin({
  background: '#0b0f1a',
  color: '#ffffff',
  buttonsStyling: false,
  customClass: {
    popup: 'rounded-2xl border border-white/10 shadow-2xl font-sans',
    confirmButton: 'bg-amber-400 text-black hover:bg-amber-300 rounded-xl px-6 py-2.5 text-xs font-semibold uppercase tracking-widest transition-colors mx-2',
    cancelButton: 'bg-white/10 text-white hover:bg-white/20 border border-white/20 rounded-xl px-6 py-2.5 text-xs font-semibold uppercase tracking-widest transition-colors mx-2',
  },
});

export function confirmLogout() {
  return swal.fire({
    title: '¿Cerrar sesión?',
    text: 'Tendrás que iniciar sesión de nuevo para continuar evaluando.',
    icon: 'warning',
    iconColor: '#fbbf24',
    showCancelButton: true,
    confirmButtonText: 'Cerrar sesión',
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
  });
}

export function loginError(message) {
  return swal.fire({
    title: 'No se pudo iniciar sesión',
    text: message,
    icon: 'error',
    iconColor: '#f87171',
    confirmButtonText: 'Entendido',
  });
}

export function showError(title, message) {
  return swal.fire({
    title,
    text: message,
    icon: 'error',
    iconColor: '#f87171',
    confirmButtonText: 'Entendido',
  });
}

// Notificación pequeña que no interrumpe (no hay que darle clic a nada), para confirmar
// que se guardó sin frenar al jurado mientras sigue calificando al siguiente participante.
export function successToast(message) {
  return swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'success',
    iconColor: '#fbbf24',
    title: message,
    showConfirmButton: false,
    timer: 1800,
    timerProgressBar: true,
  });
}
