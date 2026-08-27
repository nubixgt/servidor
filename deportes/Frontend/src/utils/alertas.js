import Swal from 'sweetalert2'

// Instancia de SweetAlert2 con el estilo oscuro/verde lima que ya
// se usa en el resto de la app, para que todas las notificaciones
// (crear, editar, eliminar, login/logout) se vean consistentes.
const swalTema = Swal.mixin({
  background: '#121212',
  color: '#ffffff',
  confirmButtonColor: '#b9f63f',
  cancelButtonColor: '#353534',
  customClass: {
    popup: 'rounded-2xl border border-white/10',
    confirmButton: 'rounded-lg',
    cancelButton: 'rounded-lg'
  },
  buttonsStyling: true
})

export function alertaExito(title, text) {
  return swalTema.fire({ icon: 'success', title, text, confirmButtonText: 'Aceptar' })
}

export function alertaError(title, text) {
  return swalTema.fire({ icon: 'error', title, text, confirmButtonText: 'Entendido' })
}

export function alertaAdvertencia(title, text) {
  return swalTema.fire({ icon: 'warning', title, text, confirmButtonText: 'Entendido' })
}

export function toastExito(title) {
  return swalTema.fire({
    icon: 'success',
    title,
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 1800,
    timerProgressBar: true
  })
}

export function confirmarAccion({ title, text, confirmButtonText = 'Confirmar', icon = 'warning' }) {
  return swalTema.fire({
    icon,
    title,
    text,
    showCancelButton: true,
    confirmButtonText,
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  })
}

export function confirmarEliminar({ title = '¿Eliminar?', text, confirmButtonText = 'Sí, eliminar' } = {}) {
  return swalTema.fire({
    icon: 'warning',
    title,
    text,
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    confirmButtonText,
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  })
}

export default swalTema
