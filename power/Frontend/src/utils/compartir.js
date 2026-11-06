/**
 * La tarjeta para compartir un logro (Beta 3.0, F8), dibujada en el navegador.
 *
 * Lleva la racha, el nivel, las semanas cumplidas y las medallas y, desde el 2026-10-11
 * (decisión del responsable, para crecer por invitaciones), el NOMBRE de pila del atleta
 * (puede quitarlo al crearla) y su código de invitación con el enlace. Nunca el VO2max, ni
 * el peso, el pulso, el sueño o nada de salud: una imagen compartida ya no es del atleta.
 *
 * Dos formatos: 1080×1350 (publicación) y 1080×1920 (historia). Se comparte con
 * `navigator.share` si el navegador sabe compartir archivos; si no, se descarga.
 */
export const FORMATOS = {
  publicacion: { ancho: 1080, alto: 1350, etiqueta: 'Publicación' },
  historia: { ancho: 1080, alto: 1920, etiqueta: 'Historia' },
};

const VERDE = '#caf300';
const FONDO = '#0f1013';
const TEXTO = '#f1f0ee';
const GRIS = '#8f9378';

function lineaCentrada(ctx, texto, y, tamano, color, peso = '900') {
  ctx.font = `${peso} ${tamano}px Inter, system-ui, sans-serif`;
  ctx.fillStyle = color;
  ctx.textAlign = 'center';
  ctx.fillText(texto, ctx.canvas.width / 2, y);
}

/**
 * Dibuja la tarjeta y devuelve un Blob PNG. `nombre` (de pila) y `codigo`/`enlace` de
 * invitación son opcionales: sin ellos la tarjeta sale como antes.
 */
export async function tarjetaDeProgreso(progreso, formato = 'publicacion', { nombre = '', codigo = '', enlace = '' } = {}) {
  const { ancho, alto } = FORMATOS[formato] || FORMATOS.publicacion;
  const lienzo = document.createElement('canvas');
  lienzo.width = ancho;
  lienzo.height = alto;
  const ctx = lienzo.getContext('2d');
  ctx.fillStyle = FONDO;
  ctx.fillRect(0, 0, ancho, alto);
  // Una franja diagonal suave, el único adorno.
  const grad = ctx.createLinearGradient(0, 0, ancho, alto);
  grad.addColorStop(0, 'rgba(202,243,0,0.18)');
  grad.addColorStop(0.6, 'rgba(202,243,0,0)');
  ctx.fillStyle = grad;
  ctx.fillRect(0, 0, ancho, alto);

  const r = progreso?.racha || {};
  const conseguidas = (progreso?.medallas || []).filter((m) => m.conseguida);
  const centro = alto / 2;
  lineaCentrada(ctx, 'POWER COACH AI', 140, 40, VERDE);
  if (nombre) lineaCentrada(ctx, `La racha de ${nombre}`, 220, 52, TEXTO, '700');
  lineaCentrada(ctx, `${r.actual || 0}`, centro - 120, 300, TEXTO);
  lineaCentrada(ctx, (r.actual || 0) === 1 ? 'semana seguida' : 'semanas seguidas', centro + 10, 64, TEXTO, '700');
  lineaCentrada(ctx, 'cumpliendo mi plan', centro + 90, 48, GRIS, '600');
  lineaCentrada(ctx, `Nivel ${r.nivel || 0} · ${r.semanas_constantes || 0} semanas cumplidas`, centro + 240, 44, VERDE, '800');
  // Las medallas que CABEN: con el código abajo, en 1080×1350 entran dos (con cuatro se
  // pisaban con «Entrena conmigo», visto el 2026-10-11).
  const tope = codigo ? alto - 250 : alto - 170;
  conseguidas.slice(0, 4).forEach((m, i) => {
    const y = centro + 340 + i * 64;
    if (y <= tope) lineaCentrada(ctx, `★ ${m.nombre}`, y, 38, TEXTO, '600');
  });
  if (codigo) {
    // La invitación: lo que convierte una imagen en una puerta (Beta 3.0, invitaciones).
    lineaCentrada(ctx, `Entrena conmigo · código ${codigo}`, alto - 190, 44, VERDE, '800');
    if (enlace) lineaCentrada(ctx, enlace.replace(/^https?:\/\//, ''), alto - 130, 30, GRIS, '600');
    lineaCentrada(ctx, 'Mi entrenador lee mi reloj', alto - 75, 30, GRIS, '600');
  } else {
    lineaCentrada(ctx, 'Mi entrenador lee mi reloj', alto - 110, 34, GRIS, '600');
  }

  return new Promise((resolve) => lienzo.toBlob(resolve, 'image/png'));
}

/** Comparte (o descarga) la tarjeta. Devuelve 'compartida' | 'descargada' | 'cancelada'. */
export async function compartirTarjeta(blob, nombre = 'mi-progreso.png') {
  const archivo = new File([blob], nombre, { type: 'image/png' });
  try {
    if (navigator.canShare?.({ files: [archivo] })) {
      await navigator.share({ files: [archivo], title: 'Mi progreso' });
      return 'compartida';
    }
  } catch (e) {
    if (e?.name === 'AbortError') return 'cancelada';
  }
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = nombre;
  a.click();
  setTimeout(() => URL.revokeObjectURL(url), 2000);
  return 'descargada';
}

// --- Las medallas que ya vio, en ESTE navegador ---------------------------------------
const CLAVE_VISTAS = 'pc.medallasVistas';

export function medallasNuevas(medallas) {
  let vistas = [];
  try { vistas = JSON.parse(localStorage.getItem(CLAVE_VISTAS) || '[]'); } catch (e) { vistas = []; }
  return (medallas || []).filter((m) => m.conseguida && !vistas.includes(m.id));
}

export function marcarVistas(medallas) {
  try {
    const ids = (medallas || []).filter((m) => m.conseguida).map((m) => m.id);
    localStorage.setItem(CLAVE_VISTAS, JSON.stringify(ids));
  } catch (e) { /* no recordarlo sólo hace que avise otra vez */ }
}
