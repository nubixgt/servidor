/**
 * Cómo entrena la fuerza el atleta (Beta 2.1, Database/025).
 *
 * Espejo de PaginaLocal/services/ai/perfil_de_fuerza.py: las claves permitidas
 * tienen que ser las mismas, y PaginaLocal/tests/test_perfil_de_fuerza.py lo
 * fija. Sin contestar (`null`) el plan sigue exactamente como hasta hoy.
 */

export const MODOS_DE_FUERZA = {
  rutina: 'Tengo mi rutina',
  coach: 'Que el coach me arme una',
};

export const OBJETIVOS_DE_FUERZA = {
  hipertrofia: 'Ganar músculo',
  rendimiento: 'Rendir más en mi deporte',
  prevencion: 'Mantenerme sano y compensar',
};

export const EQUIPOS_DE_FUERZA = {
  gimnasio: 'Gimnasio con máquinas y barras',
  mancuernas_y_bandas: 'Mancuernas y bandas',
  peso_corporal: 'Sólo mi cuerpo',
};

export const ESTRUCTURAS_DE_FUERZA = {
  full_body: 'Cuerpo completo cada sesión',
  upper_lower: 'Torso y pierna en días distintos',
};

export const LEVANTAMIENTOS_CON_BARRA = {
  sentadilla: 'Sentadilla',
  peso_muerto: 'Peso muerto',
  press_banca: 'Press de banca',
  press_militar: 'Press militar',
  remo_con_barra: 'Remo con barra',
  hip_thrust: 'Hip thrust',
};

export const ENFASIS_DE_SESION = {
  pierna: 'Pierna',
  torso: 'Torso',
  completo: 'Cuerpo completo',
  core: 'Core y movilidad',
};

export const EJERCICIOS_POR_GRUPO = [1, 3];
export const MINUTOS_POR_SESION = [15, 180];

const esObjeto = (v) => v != null && typeof v === 'object' && !Array.isArray(v);
const unoDe = (valor, permitidos) => (typeof valor === 'string' && valor in permitidos ? valor : '');

function enteroEn(valor, [min, max]) {
  if (valor === '' || valor == null || typeof valor === 'boolean') return '';
  const n = Number(valor);
  return Number.isInteger(n) && n >= min && n <= max ? n : '';
}

/** Lo guardado, como lo edita el formulario. Un modo desconocido es "no contestó". */
export function perfilDeFuerzaAlFormulario(bruto) {
  const p = esObjeto(bruto) ? bruto : {};
  return {
    mode: unoDe(p.mode, MODOS_DE_FUERZA),
    goal: unoDe(p.goal, OBJETIVOS_DE_FUERZA),
    equipment: unoDe(p.equipment, EQUIPOS_DE_FUERZA),
    structure: unoDe(p.structure, ESTRUCTURAS_DE_FUERZA),
    exercises_per_group: enteroEn(p.exercises_per_group, EJERCICIOS_POR_GRUPO),
    session_minutes: enteroEn(p.session_minutes, MINUTOS_POR_SESION),
    barbell_lifts: Array.isArray(p.barbell_lifts)
      ? Object.keys(LEVANTAMIENTOS_CON_BARRA).filter((k) => p.barbell_lifts.includes(k))
      : [],
  };
}

/**
 * Lo que se guarda. Sin modo es `null`: el atleta no contestó.
 *
 * Lo que sólo tiene sentido en el otro modo no se manda: la estructura, los
 * ejercicios seguidos y los minutos los da su rutina, y los levantamientos con
 * barra sólo cuentan con gimnasio.
 */
export function perfilDeFuerzaParaGuardar(f) {
  if (!f || !f.mode) return null;
  const out = { mode: f.mode };
  if (f.goal) out.goal = f.goal;
  if (f.equipment) out.equipment = f.equipment;
  if (f.equipment === 'gimnasio' && f.barbell_lifts?.length) out.barbell_lifts = [...f.barbell_lifts];
  if (f.mode === 'coach') {
    if (f.structure) out.structure = f.structure;
    const grupo = enteroEn(f.exercises_per_group, EJERCICIOS_POR_GRUPO);
    if (grupo !== '') out.exercises_per_group = grupo;
    const minutos = enteroEn(f.session_minutes, MINUTOS_POR_SESION);
    if (minutos !== '') out.session_minutes = minutos;
  }
  return out;
}

/** La rutina guardada, tal cual. El texto que se edita vive aparte (ver `rutinaParaGuardar`). */
export function rutinaAlFormulario(bruto) {
  if (!esObjeto(bruto)) return { original_text: '', sessions: [], confirmed_at: null };
  return {
    ...bruto,
    original_text: typeof bruto.original_text === 'string' ? bruto.original_text : '',
    sessions: Array.isArray(bruto.sessions) ? bruto.sessions : [],
    confirmed_at: bruto.confirmed_at || null,
  };
}

const mismoTexto = (a, b) => (a || '').trim() === (b || '').trim();

/**
 * La rutina que se guarda, a partir de la guardada y del texto del recuadro.
 *
 * Si el texto ya no es el que se ordenó, la rutina se DESCONFIRMA y pierde lo
 * ordenado: ya no corresponde a lo pegado, y una semana no puede usar la vieja
 * creyendo que es la nueva. Cambiar de modo no la toca: el texto no se borra en
 * silencio.
 */
export function rutinaParaGuardar(rutina, texto) {
  const guardada = rutinaAlFormulario(rutina);
  const limpio = (texto || '').trim();
  if (!limpio && !guardada.sessions.length) return null;
  if (!mismoTexto(limpio, guardada.original_text)) {
    return { source: 'atleta', original_text: limpio, confirmed_at: null, sessions: [] };
  }
  return { ...guardada, source: guardada.source || 'atleta', original_text: limpio };
}

/** Si el texto del recuadro ya no es el de la rutina confirmada. */
export function textoCambiado(rutina, texto) {
  const guardada = rutinaAlFormulario(rutina);
  return Boolean(guardada.confirmed_at) && !mismoTexto(texto, guardada.original_text);
}

/** Letra de un grupo: A, B, ... Z, AA. La misma regla que `_letra_de_grupo` en Python. */
export function letraDeGrupo(indice) {
  let n = indice + 1;
  let letra = '';
  while (n > 0) {
    const r = (n - 1) % 26;
    letra = String.fromCharCode(65 + r) + letra;
    n = Math.floor((n - 1) / 26);
  }
  return letra;
}

let contadorDeIds = 0;
const idNuevo = (base) => `${base}_n${Date.now().toString(36)}${(contadorDeIds += 1)}`;

/** Separa un grupo en un grupo por ejercicio, con el mismo descanso entre rondas. */
export function separarGrupo(sesion, indice) {
  const grupo = sesion.groups[indice];
  if (!grupo || grupo.exercises.length < 2) return;
  const nuevos = grupo.exercises.map((ejercicio, i) => ({
    ...grupo,
    id: i === 0 ? grupo.id : idNuevo(grupo.id),
    exercises: [ejercicio],
  }));
  sesion.groups.splice(indice, 1, ...nuevos);
}

/** Une un grupo con el anterior: sus ejercicios pasan a hacerse seguidos. */
export function unirConElAnterior(sesion, indice) {
  if (indice < 1 || !sesion.groups[indice]) return;
  const [grupo] = sesion.groups.splice(indice, 1);
  sesion.groups[indice - 1].exercises.push(...grupo.exercises);
}

/**
 * El nombre cambió: lo que se resolvió para el nombre viejo (imagen, nombre en el
 * reloj) ya no vale. Mejor sin nombre en el reloj que con el de otro ejercicio.
 */
export function renombrarEjercicio(ejercicio, nombre) {
  if ((ejercicio.name || '') === nombre) return;
  ejercicio.name = nombre;
  ejercicio.garmin_category = null;
  ejercicio.garmin_exercise = null;
  ejercicio.video_key = null;
}

/** "BARBELL_BENCH_PRESS" -> "Barbell bench press": el nombre con el que lo enseña el reloj. */
export function nombreEnElReloj(ejercicio) {
  const n = ejercicio?.garmin_exercise;
  if (!n) return '';
  const texto = n.toLowerCase().replace(/_/g, ' ');
  return texto.charAt(0).toUpperCase() + texto.slice(1);
}

/** Todo lo que el atleta tiene que mirar antes de confirmar, con una clave estable por duda. */
export function pendientesDeRevisar(borrador) {
  const out = [];
  (borrador?.doubts || []).forEach((texto, i) => out.push({ clave: `general_${i}`, texto }));
  (borrador?.unread_lines || []).forEach((l) => out.push({
    clave: `linea_${l.line}`, texto: `No se leyó la línea ${l.line}: «${l.text}». ${l.reason || ''}`.trim(),
  }));
  (borrador?.sessions || []).forEach((s) => s.groups.forEach((g) => g.exercises.forEach((e) => {
    (e.doubts || []).forEach((texto, i) => out.push({ clave: `${e.id}_${i}`, texto, ejercicio: e.id }));
  })));
  return out;
}

/**
 * La rutina confirmada que se guarda: sin las dudas ni las líneas sin leer (eran
 * para revisar), sin grupos ni sesiones vacías, con la fecha de confirmación y el
 * texto del que salió.
 */
export function rutinaConfirmada(borrador, texto, ahora = new Date()) {
  const sesiones = (borrador?.sessions || []).map((s) => ({
    id: s.id,
    name: (s.name || '').trim(),
    // Por qué existe esa sesión, cuando lo escribió el coach (S6): el atleta la lee
    // semanas después, y sin esto el programa vuelve a ser una lista de ejercicios.
    why: String(s.why ?? '').trim(),
    // Qué carga la sesión: de aquí sale en qué día de la semana la coloca el coach.
    emphasis: String(s.emphasis ?? '').trim().toLowerCase(),
    minutes: Number.isInteger(Number(s.minutes)) && Number(s.minutes) > 0 ? Number(s.minutes) : null,
    source_line: s.source_line ?? null,
    groups: (s.groups || []).map((g) => ({
      id: g.id,
      rest_between_rounds_s: Number(g.rest_between_rounds_s) > 0 ? Number(g.rest_between_rounds_s) : null,
      rest_between_exercises_s: Number(g.rest_between_exercises_s) > 0 ? Number(g.rest_between_exercises_s) : null,
      exercises: (g.exercises || []).filter((e) => (e.name || '').trim()).map((e) => ({
        id: e.id,
        name: e.name.trim(),
        sets: Number.isInteger(Number(e.sets)) && Number(e.sets) > 0 ? Number(e.sets) : null,
        reps: String(e.reps ?? '').trim(),
        load: String(e.load ?? '').trim(),
        technique: String(e.technique ?? '').trim(),
        source_line: e.source_line ?? null,
        garmin_category: e.garmin_category || null,
        garmin_exercise: e.garmin_exercise || null,
        video_key: e.video_key || null,
      })),
    })).filter((g) => g.exercises.length),
  })).filter((s) => s.groups.length);
  return {
    source: borrador?.source || 'atleta',
    original_text: (texto || '').trim(),
    confirmed_at: ahora.toISOString(),
    notes: String(borrador?.notes ?? '').trim(),
    sessions: sesiones,
  };
}
