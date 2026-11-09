import Swal from 'sweetalert2';
import { bookingCode } from './receipt';

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

const idsText = (ids) => ids.map((i) => 'A' + i).join(', ');

// Éxito al reservar. Devuelve true si el usuario elige "Ver mi reserva".
export const successBox = async (ids, bookingId) => {
    const { isConfirmed } = await base.fire({
        icon: 'success',
        title: '¡Nos vemos en la pista!',
        html: `<p class="swal-text">Las mesas ${idsText(ids)} quedaron reservadas y aparecen como ocupadas.</p>
            <div class="swal-code">CÓDIGO DE RESERVA<span>${bookingCode(bookingId)}</span></div>
            <p class="swal-text">Se descargó tu comprobante en PDF. Si no, descárgalo desde Reservas.</p>`,
        showCancelButton: true,
        confirmButtonText: 'Ver mi reserva',
        cancelButtonText: 'Volver al mapa',
        reverseButtons: true,
    });
    return isConfirmed;
};

// Advertencia antes de cancelar (eliminar) una reserva. Devuelve true si confirma.
export const cancelBookingBox = async (ids) => {
    const { isConfirmed } = await base.fire({
        icon: 'warning',
        title: '¿Liberar estas mesas?',
        text: `Cancelarás la reserva de ${idsText(ids)}. Volverán a estar disponibles para seleccionarlas.`,
        showCancelButton: true,
        confirmButtonText: 'Sí, liberar mesas',
        cancelButtonText: 'Conservar reserva',
        reverseButtons: true,
    });
    return isConfirmed;
};

export default base;
