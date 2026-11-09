import Swal from 'sweetalert2';
import { money } from './tables';

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

// Ayuda "Cómo funciona".
export const helpBox = () =>
    base.fire({
        title: 'Elige. Reserva. Disfruta.',
        html: `<div class="swal-steps">
            <p><strong>1. Encuentra tu lugar.</strong>Los colores distinguen Platino, VIP y Preferencia. Puedes filtrar por localidad o buscar el número de mesa.</p>
            <p><strong>2. Selecciona tus mesas.</strong>Cada mesa incluye 8 entradas. Las mesas rayadas están ocupadas y no se pueden seleccionar.</p>
            <p><strong>3. Confirma tu reserva.</strong>Ingresa un nombre. Tus mesas se guardarán y quedarán ocupadas. Puedes cancelarlas desde Reservas.</p>
        </div>`,
        confirmButtonText: 'Entendido',
    });

// Pide el nombre y confirma la reserva. `submit(name)` hace la llamada al backend
// (con spinner mientras tanto) y devuelve su resultado.
// Devuelve { cancelled: true } o { cancelled: false, result }.
export const bookingBox = async ({ ids, total, name = '', submit }) => {
    const res = await base.fire({
        title: 'Dale nombre a tu noche.',
        html: `<p class="swal-text">Reservarás ${idsText(ids)} para ${ids.length * 8} personas.</p>
            <div class="swal-total"><span>${ids.length} ${ids.length === 1 ? 'mesa' : 'mesas'} · 8 entradas por mesa</span><strong>${money(total)}</strong></div>
            <p class="swal-note">Al confirmar, estas mesas quedarán ocupadas. No se procesa ningún pago.</p>`,
        input: 'text',
        inputValue: name,
        inputLabel: 'Nombre de la reserva',
        inputPlaceholder: 'Nombre y apellido',
        inputAttributes: { maxlength: 100, autocomplete: 'name' },
        showCancelButton: true,
        confirmButtonText: 'Confirmar reserva',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        allowOutsideClick: () => !Swal.isLoading(),
        allowEscapeKey: () => !Swal.isLoading(),
        showLoaderOnConfirm: true,
        preConfirm: async (value) => {
            const n = (value || '').trim();
            if (n.length < 2) {
                Swal.showValidationMessage('Escribe un nombre de al menos 2 caracteres.');
                return false;
            }
            return { name: n, result: await submit(n) };
        },
    });
    return res.isConfirmed ? { cancelled: false, ...res.value } : { cancelled: true, name };
};

// Reserva confirmada. Devuelve true si el usuario elige "Ver mi reserva".
export const successBox = async (ids, bookingId) => {
    const { isConfirmed } = await base.fire({
        icon: 'success',
        title: '¡Nos vemos en la pista!',
        html: `<p class="swal-text">Las mesas ${idsText(ids)} quedaron reservadas y aparecen como ocupadas.</p>
            <div class="swal-code">RESERVA<span>#${bookingId.slice(0, 8).toUpperCase()}</span></div>`,
        showCancelButton: true,
        confirmButtonText: 'Ver mi reserva',
        cancelButtonText: 'Volver al mapa',
        reverseButtons: true,
    });
    return isConfirmed;
};

// Confirma la cancelación; `submit()` hace la llamada al backend.
// Devuelve null si el usuario no confirma, o el resultado de `submit`.
export const cancelBookingBox = async (ids, submit) => {
    const res = await base.fire({
        icon: 'question',
        title: '¿Liberar estas mesas?',
        text: `Cancelarás la reserva de ${idsText(ids)}. Volverán a estar disponibles para seleccionarlas.`,
        showCancelButton: true,
        confirmButtonText: 'Sí, liberar mesas',
        cancelButtonText: 'Conservar reserva',
        reverseButtons: true,
        allowOutsideClick: () => !Swal.isLoading(),
        allowEscapeKey: () => !Swal.isLoading(),
        showLoaderOnConfirm: true,
        preConfirm: () => submit(),
    });
    return res.isConfirmed ? res.value : null;
};

export default base;
