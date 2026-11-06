/**
 * Invitaciones (Beta 3.0, Database/033): el enlace es `<la app>/?ref=CÓDIGO`.
 *
 * Quien llega con el enlace todavía no tiene cuenta: el código se guarda en ESTE navegador
 * hasta que entra con Google, y viaja con ese primer login (`referral_code`). El servidor
 * sólo lo usa al CREAR la cuenta (`ReferralService::alCrearLaCuenta`); a quien ya existe no
 * le cambia nada.
 */
const CLAVE = 'pc.invitacion';
const FORMA = /^[A-Za-z0-9]{4,16}$/;

/** Lee `?ref=` de la dirección y lo guarda. Lo llama `main.js` al arrancar. */
export function capturarCodigo(search = window.location.search) {
  try {
    const codigo = new URLSearchParams(search).get('ref');
    if (codigo && FORMA.test(codigo)) localStorage.setItem(CLAVE, codigo.toUpperCase());
  } catch (e) { /* sin almacenamiento se entra igual, sólo que sin invitación */ }
}

/**
 * El código ESCRITO a mano en la pantalla de entrada (quien lo vio en una tarjeta compartida o se lo
 * dijeron): se guarda igual que el del enlace. Devuelve false si no tiene la forma de un código.
 */
export function guardarCodigo(codigo) {
  const c = String(codigo || '').trim();
  if (!FORMA.test(c)) return false;
  try { localStorage.setItem(CLAVE, c.toUpperCase()); return true; } catch (e) { return false; }
}

export function codigoGuardado() {
  try {
    const c = localStorage.getItem(CLAVE);
    return c && FORMA.test(c) ? c : null;
  } catch (e) { return null; }
}

export function olvidarCodigo() {
  try { localStorage.removeItem(CLAVE); } catch (e) { /* nada que olvidar */ }
}

/** El enlace que comparte el atleta. */
export function enlaceDeInvitacion(codigo, origen = window.location.origin) {
  return `${origen}${import.meta.env.BASE_URL}?ref=${encodeURIComponent(codigo)}`;
}
