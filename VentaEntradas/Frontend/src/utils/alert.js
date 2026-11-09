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

// Inicio de sesión. `submit(usuario, contraseña)` devuelve { ok, message? };
// si falla, el mensaje se muestra dentro de la misma ventana.
export const loginBox = ({ submit }) =>
    base.fire({
        title: 'Iniciar sesión',
        html: `<p class="swal-text">Ingresa con tu usuario y contraseña.</p>
            <input id="swal-user" class="swal2-input" placeholder="Usuario" autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="30">
            <input id="swal-pass" class="swal2-input" type="password" placeholder="Contraseña" autocomplete="current-password" maxlength="72">`,
        focusConfirm: false,
        confirmButtonText: 'Entrar',
        allowOutsideClick: () => !Swal.isLoading(),
        allowEscapeKey: false,
        showLoaderOnConfirm: true,
        didOpen: () => {
            const user = document.getElementById('swal-user');
            const pass = document.getElementById('swal-pass');
            [user, pass].forEach((el) =>
                el.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') Swal.clickConfirm();
                })
            );
            user.focus();
        },
        preConfirm: async () => {
            const username = document.getElementById('swal-user').value.trim();
            const password = document.getElementById('swal-pass').value;
            if (!username || !password) {
                Swal.showValidationMessage('Ingresa tu usuario y contraseña.');
                return false;
            }
            const res = await submit(username, password);
            if (!res.ok) {
                Swal.showValidationMessage(res.message);
                return false;
            }
            return res;
        },
    });

const ROLE_OPTIONS = { vendedor: 'Vendedor', admin: 'Administrador' };

// Crear usuario. `submit(datos)` devuelve { ok, message? }. Devuelve true si se creó.
export const newUserBox = async (submit) => {
    const res = await base.fire({
        title: 'Nuevo usuario',
        html: `<input id="swal-fullname" class="swal2-input" placeholder="Nombre completo" maxlength="100">
            <input id="swal-username" class="swal2-input" placeholder="Usuario (para iniciar sesión)" autocapitalize="none" spellcheck="false" maxlength="30">
            <input id="swal-newpass" class="swal2-input" type="password" placeholder="Contraseña (mínimo 8 caracteres)" autocomplete="new-password" maxlength="72">
            <select id="swal-role" class="swal2-select">${Object.entries(ROLE_OPTIONS).map(([v, l]) => `<option value="${v}">${l}</option>`).join('')}</select>`,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Crear usuario',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        allowOutsideClick: () => !Swal.isLoading(),
        showLoaderOnConfirm: true,
        preConfirm: async () => {
            const data = {
                fullName: document.getElementById('swal-fullname').value.trim(),
                username: document.getElementById('swal-username').value.trim(),
                password: document.getElementById('swal-newpass').value,
                role: document.getElementById('swal-role').value,
            };
            if (!data.fullName || !data.username || !data.password) {
                Swal.showValidationMessage('Completa todos los campos.');
                return false;
            }
            const r = await submit(data);
            if (!r.ok) {
                Swal.showValidationMessage(r.message);
                return false;
            }
            return r;
        },
    });
    return res.isConfirmed;
};

// Pide una contraseña nueva. `submit(password)` devuelve { ok, message? }. Devuelve true si se guardó.
export const passwordBox = async (fullName, submit) => {
    const res = await base.fire({
        title: 'Cambiar contraseña',
        text: `Usuario: ${fullName}`,
        input: 'password',
        inputPlaceholder: 'Nueva contraseña (mínimo 8 caracteres)',
        inputAttributes: { autocomplete: 'new-password', maxlength: 72 },
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        allowOutsideClick: () => !Swal.isLoading(),
        showLoaderOnConfirm: true,
        preConfirm: async (value) => {
            if (!value || value.length < 8) {
                Swal.showValidationMessage('La contraseña debe tener al menos 8 caracteres.');
                return false;
            }
            const r = await submit(value);
            if (!r.ok) {
                Swal.showValidationMessage(r.message);
                return false;
            }
            return r;
        },
    });
    return res.isConfirmed;
};

// Elegir rol. `submit(rol)` devuelve { ok, message? }. Devuelve true si se guardó.
export const roleBox = async (fullName, current, submit) => {
    const res = await base.fire({
        title: 'Cambiar rol',
        text: `Usuario: ${fullName}`,
        input: 'select',
        inputOptions: ROLE_OPTIONS,
        inputValue: current,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        allowOutsideClick: () => !Swal.isLoading(),
        showLoaderOnConfirm: true,
        preConfirm: async (value) => {
            const r = await submit(value);
            if (!r.ok) {
                Swal.showValidationMessage(r.message);
                return false;
            }
            return r;
        },
    });
    return res.isConfirmed;
};

export default base;
