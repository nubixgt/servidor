/**
 * Los ✓/✗ de cada sesión (el responsable, 2026-10-21: «que marque un cheque cuando la sesión ya se logró
 * y una x cuando no»). Los estados los calcula el cerebro sin IA, con la misma lectura que la adherencia de
 * la generación (`services/ai/estado_de_las_sesiones.py`); aquí sólo se dibujan y se dicen.
 */

export const MARCAS = {
  hecha: { icono: 'check', clase: 'bg-[#caf300] text-[#121700]', texto: 'Hecha' },
  sin_intensidad: { icono: 'check', clase: 'bg-[#ffd166] text-[#121700]', texto: 'Hecha, sin llegar a la intensidad' },
  parcial: { icono: 'remove', clase: 'bg-[#ffd166] text-[#121700]', texto: 'Hiciste una de las dos' },
  no_hecha: { icono: 'close', clase: 'bg-[#ff8f83] text-[#121700]', texto: 'No está en tu reloj' },
  sin_sincronizar: { icono: 'sync', clase: 'bg-[#ffd166]/25 text-[#ffd166] ring-1 ring-[#ffd166]/60', texto: 'Tu reloj no ha sincronizado' },
};

/** La marca de un día con varias sesiones (`claves` = "fecha|franja"). '' = sin marca (pendiente, descanso). */
export function marcaDelDia(estados, claves) {
  const e = (claves || []).map((k) => estados?.[k]?.estado).filter(Boolean);
  if (!e.length) return '';
  const hechas = e.filter((x) => x === 'hecha' || x === 'sin_intensidad').length;
  if (e.includes('sin_sincronizar') && !hechas) return 'sin_sincronizar';
  if (hechas === e.length) return e.includes('sin_intensidad') ? 'sin_intensidad' : 'hecha';
  if (hechas && e.includes('no_hecha')) return 'parcial';
  if (e.every((x) => x === 'no_hecha')) return 'no_hecha';
  return '';
}

const DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
const coma = (n) => String(n).replace('.', ',');

/** Una línea para el detalle de la sesión: qué hizo, según su reloj. */
export function textoDeLaSesion(e, prevista = null) {
  if (!e) return '';
  if (e.estado === 'hecha' || e.estado === 'sin_intensidad') {
    const min = e.minutos && e.modificada && prevista ? `${e.minutos} de ${Math.round(prevista)} min previstos`
      : (e.minutos ? `${e.minutos} min` : '');
    const datos = [min, e.km ? `${coma(e.km)} km` : '', e.fc_media ? `FC media ${e.fc_media}` : '']
      .filter(Boolean).join(' · ');
    const dia = e.otro_dia ? ` (la hiciste el ${DIAS[new Date(`${e.otro_dia}T12:00:00`).getDay()]})` : '';
    const base = e.estado === 'sin_intensidad'
      ? 'Hecha, pero tu pulso no llegó a la intensidad de la sesión'
      : (e.con_el_entreno ? 'Hecha con el entreno de tu reloj' : 'Hecha');
    return `${base}${dia}${datos ? ` · ${datos}` : ''}`;
  }
  if (e.estado === 'no_hecha') return 'No está en tu reloj. Si la hiciste sin él, no pasa nada: tu coach lo tiene en cuenta al escribir la siguiente.';
  if (e.estado === 'sin_sincronizar') return 'Tu reloj no ha subido nada desde ese día: sincronízalo con Garmin Connect para verla.';
  return '';
}
