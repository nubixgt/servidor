/**
 * Profile helpers: mapping between the backend/PaginaLocal profile shape,
 * the Pinia `athlete` object the rest of the app reads, and the ProfileView form.
 */

import {
  perfilDeFuerzaAlFormulario,
  perfilDeFuerzaParaGuardar,
  rutinaAlFormulario,
  rutinaParaGuardar,
} from './fuerza.js';

export const WEEK_DAYS = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
export const BLOCK_SPORTS = ['Correr', 'Trail', 'Bici', 'Nadar', 'Fuerza', 'Cualquiera'];

/**
 * Los deportes de la rejilla de disponibilidad. Sin "Cualquiera": la rejilla
 * pregunta por deporte, y "cualquiera" era la respuesta que hacia falta cuando
 * lo que se declaraba era una FRANJA HORARIA y no un deporte.
 */
export const GRID_SPORTS = ['Correr', 'Trail', 'Bici', 'Nadar', 'Fuerza'];

/**
 * Minimos SUGERIDOS por deporte, nunca bloqueantes.
 *
 * Salen de la constancia que cada deporte necesita, no de una regla del
 * sistema: correr dos dias por semana no construye nada y la natacion pierde
 * sensacion de agua enseguida. Se le enseñan al atleta como consejo y el
 * decide -- la app no sabe mas que el sobre su vida.
 */
export const MINIMOS_POR_DEPORTE = {
  Correr: { min: 3, rec: 4 },
  Nadar: { min: 2, rec: 3 },
  Bici: { min: 2, rec: 3 },
};

/** Los tres estados de una casilla. `null` (ausente) es el tercero. */
export const ESTADO_FIJO = 'fijo';
export const ESTADO_POSIBLE = 'posible';

export const SPORT_LABELS = {
  triathlon: 'Triatleta',
  run: 'Corredor',
  swim: 'Nadador',
  bike: 'Ciclista',
};

/**
 * Las marcas del atleta dejan de ser seis campos fijos.
 *
 * Eran `run_5k`, `run_10k`, `run_half_marathon`, `run_marathon`,
 * `ftp_test_20min` y `swim_1500m`: cuatro de carrera en asfalto, una de bici y
 * una de piscina. Un triatleta con un olimpico hecho, o un nadador con una 5K
 * de aguas abiertas, no tenia donde ponerlo -- y esas son justo las marcas que
 * dicen de que es capaz.
 *
 * Ahora es una LISTA y el formato sale del catalogo de 38 (`public/carreras.json`,
 * el mismo del que salen las competiciones objetivo), con la puerta abierta a
 * escribir uno que no este.
 *
 * `PR_HEREDADOS` traduce la forma vieja al leer, igual que DISTANCIAS_HEREDADAS
 * con las competiciones: nadie pierde lo que escribio y no hace falta migrar
 * la base de datos.
 */
const PR_HEREDADOS = {
  run_5k: { key: '5K', label: '5K' },
  run_10k: { key: '10K', label: '10K' },
  run_half_marathon: { key: 'half_marathon', label: 'Media maratón (21K)' },
  run_marathon: { key: 'marathon', label: 'Maratón (42K)' },
  swim_1500m: { key: 'swim_1500', label: '1500 m (piscina)' },
  // No es una carrera, asi que no tiene clave de catalogo. Se conserva porque
  // es la referencia de potencia de cualquier ciclista.
  ftp_test_20min: { key: '', label: 'FTP (test de 20 min)' },
};

/** Una marca vacía, para el botón de añadir. */
export const marcaVacia = () => ({ key: '', label: '', time: '', date: '', libre: false });

/**
 * Las marcas guardadas, venga el perfil en la forma nueva o en la vieja.
 *
 * @returns {{marks: Array, notes: string}}
 */
export function leerMarcas(bruto) {
  const salida = { marks: [], notes: '' };
  if (!bruto || typeof bruto !== 'object') return salida;
  salida.notes = typeof bruto.notes === 'string' ? bruto.notes : '';

  if (Array.isArray(bruto.marks)) {
    salida.marks = bruto.marks
      .filter((m) => m && (m.time || m.label))
      .map((m) => ({
        key: m.key || '',
        label: m.label || '',
        time: m.time || '',
        date: m.date || '',
        // Con etiqueta y sin clave, la fila es de texto libre. Sin esto salia
        // con el desplegable en "Elige la prueba" y el nombre invisible:
        // parecia vacia, y al guardar se perdia.
        libre: !m.key && !!m.label,
      }));
    return salida;
  }

  // Forma vieja: un diccionario plano de seis claves.
  Object.entries(PR_HEREDADOS).forEach(([viejo, meta]) => {
    const v = String(bruto[viejo] ?? '').trim();
    if (v) salida.marks.push({ ...meta, time: v, date: '', libre: !meta.key });
  });
  return salida;
}

/** seconds/km → "m:ss min/km" (or per 100m). Blank when not set. */
export function secToPace(sec, unitLabel) {
  const n = Number(sec);
  if (!n || n <= 0) return '';
  const m = Math.floor(n / 60);
  const s = Math.round(n % 60).toString().padStart(2, '0');
  return `${m}:${s} ${unitLabel}`;
}

/**
 * "5:47" → 347 segundos. El inverso de `secToPace`.
 *
 * El atleta escribe el ritmo como lo lee en su reloj y como lo dice cualquier
 * entrenador (min/km); por dentro se sigue guardando en SEGUNDOS, que es lo
 * que la base de datos, el esquema del plan y el constructor de entrenos
 * esperan. La conversión vive aquí, en un solo sitio, y no en cada formulario.
 *
 * Un número suelto se interpreta como segundos: es lo que había antes en este
 * campo, así que un perfil viejo pegado a mano sigue funcionando. Cualquier
 * otra cosa devuelve '' en vez de un cero — un ritmo de cero no existe, y
 * guardarlo daría bandas de entrenamiento absurdas.
 */
export function paceToSec(texto) {
  const t = String(texto ?? '').trim().replace(',', ':');
  if (!t) return '';
  if (t.includes(':')) {
    const [min, seg = '0'] = t.split(':');
    const m = Number(min);
    const s = Number(seg);
    if (!Number.isFinite(m) || !Number.isFinite(s) || m < 0 || s < 0 || s >= 60) return '';
    const total = Math.round(m * 60 + s);
    return total > 0 ? total : '';
  }
  const n = Number(t);
  return Number.isFinite(n) && n > 0 ? Math.round(n) : '';
}

/**
 * Populate the keys of `store.athlete` that other sections read, from a loaded
 * profile. Keys with no profile equivalent (hrv, maxHeartRate, nextTargetRace)
 * are intentionally left untouched so their mock fallback keeps rendering.
 */

/** El nombre de columna, escrito como el atleta lo ve en el formulario. */
export const ETIQUETAS_DE_CAMPO = {
  full_name: 'Nombre',
  sports_preference: 'Deporte principal',
  garmin_email: 'Correo de Garmin',
  gender: 'Sexo',
  birth_date: 'Fecha de nacimiento',
  weight_kg: 'Peso',
  height_cm: 'Estatura',
  resting_heart_rate: 'FC en reposo',
  training_age_level: 'Nivel de entrenamiento',
  years_training: 'Años entrenando',
  current_weekly_volume_hours: 'Volumen semanal',
  occupation_type: 'Tipo de ocupación',
  avg_sleep_hours: 'Horas de sueño',
  stress_level_self_report: 'Nivel de estrés',
  climate: 'Clima',
  terrain: 'Terreno',
  preferred_rest_day: 'Día de descanso preferido',
  lactate_threshold_pace_sec_per_km: 'Ritmo umbral',
  vo2max_running: 'VO2max de carrera',
  bike_setup: 'Configuración de bici',
  swim_threshold_pace_sec_per_100m: 'Ritmo umbral de natación',
  pool_access: 'Acceso a piscina',
};

/** En qué pestaña se rellena cada uno, para poder llevar al atleta allí. */
export const PESTANA_DE_CAMPO = {
  full_name: 'identidad', sports_preference: 'identidad', garmin_email: 'identidad',
  training_age_level: 'historial', years_training: 'historial',
  // Los mide Garmin al conectarlo. Se enseñan en Avanzado para poder
  // corregirlos, pero el atleta no tiene que escribirlos nunca.
  current_weekly_volume_hours: 'historial',
  gender: 'avanzado', birth_date: 'avanzado', weight_kg: 'avanzado',
  height_cm: 'avanzado', resting_heart_rate: 'avanzado',
  vo2max_running: 'avanzado', lactate_threshold_pace_sec_per_km: 'avanzado',
  swim_threshold_pace_sec_per_100m: 'avanzado',
  // Los DECIDE el atleta y condicionan el plan, asi que viven en el alta
  // basica, junto a la rejilla: sin piscina no hay natacion que recetar por
  // mucho que marque Nadar el martes.
  pool_access: 'objetivos', bike_setup: 'objetivos',
  climate: 'avanzado', terrain: 'avanzado',
  occupation_type: 'avanzado', avg_sleep_hours: 'avanzado',
  stress_level_self_report: 'avanzado',
  preferred_rest_day: 'objetivos',
};

/** El minimo de perfil para generar. Espejo de PlanService::MINIMO_PERFIL. */
export const MINIMO_PERFIL = 80;

/** Lo que el atleta eligió en Mi experiencia → Nivel, escrito para leerse. */
const NIVELES = {
  principiante: 'Atleta principiante',
  intermedio: 'Atleta intermedio',
  avanzado: 'Atleta avanzado',
  elite: 'Atleta elite',
};

export function adaptProfileToAthlete(p, current = {}) {
  if (!p) return {};
  const out = {};

  if (p.name) out.name = p.name;
  if (p.sports_preference) out.primarySport = p.sports_preference;
  if (p.garmin_email != null) out.garminEmail = p.garmin_email;
  if (p.resting_heart_rate != null) out.restingHeartRate = p.resting_heart_rate;

  const vo2 = p.vo2max_running ?? p.vo2max_cycling;
  if (vo2 != null) out.vo2Max = vo2;

  if (p.ftp_watts != null) out.ftpWatts = p.ftp_watts;
  if (p.weight_kg != null) out.weightKg = p.weight_kg;
  if (p.height_cm != null) out.heightCm = p.height_cm;

  const runPace = secToPace(p.lactate_threshold_pace_sec_per_km, 'min/km');
  if (runPace) out.runThresholdPace = runPace;
  const swimPace = secToPace(p.swim_threshold_pace_sec_per_100m, 'min/100m');
  if (swimPace) out.swimThresholdPace = swimPace;

  if (p.completionRate != null) out.completionRate = p.completionRate;

  // QUE le falta, no solo cuanto. Los cinco atletas de la beta se quedaron
  // entre el 95% y el 96% -- a cada uno le faltaba exactamente UN campo -- y
  // ninguno tenia forma de saber cual. Un porcentaje que no dice que hacer es
  // un porcentaje que frustra. La lista la calcula el backend
  // (ProfileService::missingFields), que es donde vive la regla.
  if (Array.isArray(p.missing_fields)) out.missingFields = p.missing_fields;

  // El nivel que el atleta declaro, que hasta ahora se pedia en el alta y no se
  // enseñaba en ningun sitio: todo el mundo veia "Atleta Elite" bajo su nombre
  // porque era una cadena fija en el store. De ahi salio el comentario de la
  // beta pidiendo "ir subiendo de nivel" -- estaba leyendo una etiqueta
  // decorativa. El sistema de niveles por logros es otra conversacion; esto
  // solo deja de mentir.
  if (p.training_age_level) out.levelLabel = NIVELES[p.training_age_level] || '';

  out.garminConnected = Array.isArray(p.garmin_autofilled_fields)
    ? p.garmin_autofilled_fields.length > 0
    : Boolean(p.garmin_email);

  return out;
}


/** La rejilla guardada, quedandose solo con lo que se entiende. */
function normalizarRejilla(bruto) {
  const salida = {};
  if (!bruto || typeof bruto !== 'object') return salida;
  GRID_SPORTS.forEach((dep) => {
    const fila = bruto[dep];
    if (!fila || typeof fila !== 'object') return;
    WEEK_DAYS.forEach((d) => {
      const estado = fila[d];
      if (estado === ESTADO_FIJO || estado === ESTADO_POSIBLE) {
        salida[dep] = salida[dep] || {};
        salida[dep][d] = estado;
      }
    });
  });
  return salida;
}

/**
 * Un perfil viejo leido como rejilla.
 *
 * Un bloque de 18:00-21:00 con "Correr, Nadar" el lunes significa que ese lunes
 * PUEDE correr o nadar. Se traduce a dos casillas `posible`, nunca `fijo`: el
 * atleta no ha pedido nada todavia, solo declaro cuando esta libre.
 *
 * Un bloque sin deportes -- o con "Cualquiera" -- abre los tres deportes de
 * resistencia, que es lo que hacia el camino viejo. La fuerza no se abre sola:
 * eso se decide en Preferencias.
 */
function rejillaDesdeBloques(bloques, diasDisponibles) {
  const salida = {};
  const marcar = (dep, dia) => {
    if (!GRID_SPORTS.includes(dep)) return;
    salida[dep] = salida[dep] || {};
    salida[dep][dia] = ESTADO_POSIBLE;
  };
  const bloquesPorDia = (bloques && typeof bloques === 'object') ? bloques : {};
  const disponibles = Array.isArray(diasDisponibles) ? diasDisponibles : [];

  WEEK_DAYS.forEach((d) => {
    const delDia = Array.isArray(bloquesPorDia[d]) ? bloquesPorDia[d] : [];

    // Un dia DISPONIBLE SIN FRANJAS es el camino viejo de `hours_per_day`, y
    // existe en los perfiles de la beta. Sin esta rama desaparecia de la
    // rejilla y, como la rejilla recalcula `available_days` al guardar, el
    // atleta perdia ese dia de entrenamiento sin que nada lo dijera. La clase
    // de fallo que este repo persigue: no da error, el resultado esta mal.
    if (!delDia.length) {
      if (disponibles.includes(d)) ['Correr', 'Bici', 'Nadar'].forEach((dep) => marcar(dep, d));
      return;
    }

    delDia.forEach((b) => {
      const declarados = Array.isArray(b.sports) ? b.sports : [];
      const abiertos = !declarados.length || declarados.includes('Cualquiera');
      (abiertos ? ['Correr', 'Bici', 'Nadar'] : declarados).forEach((dep) => marcar(dep, d));
    });
  });
  return salida;
}

/** Los deportes que el atleta admite ese dia, y cuales de ellos pidio. */
export function deportesDelDia(sportDays, dia) {
  const fijos = [];
  const posibles = [];
  GRID_SPORTS.forEach((dep) => {
    const estado = sportDays && sportDays[dep] && sportDays[dep][dia];
    if (estado === ESTADO_FIJO) fijos.push(dep);
    else if (estado === ESTADO_POSIBLE) posibles.push(dep);
  });
  return { fijos, posibles };
}

/** Cuantos dias tiene marcado cada deporte, para los minimos sugeridos. */
export function diasPorDeporte(sportDays, deporte) {
  const fila = (sportDays && sportDays[deporte]) || {};
  return WEEK_DAYS.filter((d) => fila[d]).length;
}

/** An empty form matching the whole model, for when there is no profile row yet. */
export function emptyFormData() {
  const days = {};
  WEEK_DAYS.forEach((d) => {
    days[d] = { enabled: false, blocks: [] };
  });

  const recent_prs = { marks: [], notes: '' };

  return {
    // A. Identidad
    name: '',
    sports_preference: 'triathlon',
    garmin_email: '',
    garmin_password: '',
    // B. Objetivos
    target_distance: '',
    competitions: [],
    sportDays: {},
    // El TECHO semanal que declara el atleta. Desde la v1.1 la rejilla no
    // lleva horas de reloj, asi que este numero ya no se puede derivar de
    // ningun sitio: hay que preguntarlo. Ver `assembleProfilePayload`.
    days,
    // C. Historial
    training_age_level: '',
    years_training: '',
    current_weekly_volume_hours: '',
    longest_recent_session_hours: '',
    recent_prs,
    // D. Fisiología
    weight_kg: '',
    height_cm: '',
    gender: '',
    birth_date: '',
    resting_heart_rate: '',
    vo2max_running: '',
    vo2max_cycling: '',
    lactate_threshold_hr: '',
    lactate_threshold_pace_sec_per_km: '',
    ftp_watts: '',
    swim_threshold_pace_sec_per_100m: '',
    // Database/022: el ritmo de SU rodaje suave, cuando el atleta lo sabe.
    easy_run_pace_fast_sec_per_km: '',
    easy_run_pace_slow_sec_per_km: '',
    threshold_tests: {},
    // Beta 2.1 (Database/025): cómo entrena la fuerza. Sin modo = no contestó.
    strength_profile: perfilDeFuerzaAlFormulario(null),
    strength_routine: rutinaAlFormulario(null),
    strength_routine_texto: '',
    // E. Equipo y Entorno
    pool_access: '',
    pool_length_m: '',
    bike_setup: '',
    treadmill_access: false,
    altitude_m: '',
    climate: '',
    terrain: '',
    // F. Estilo de Vida
    occupation_type: '',
    avg_sleep_hours: '',
    stress_level_self_report: '',
    travel_frequency: '',
    nutrition_notes: '',
    menstrual_tracking_opt_in: false,
    menstrual_cycle_notes: '',
    // G. Salud
    current_injuries: [],
    past_injuries_surgeries: '',
    chronic_conditions: '',
    medications: '',
    limitations: '',
    // H. Preferencias
    coaching_tone: '',
    preferred_rest_day: '',
    week_start_day: '',
    cross_training_open: true,
    ai_load_guardrail_enabled: true,
    // carried over untouched
    _carry: {},
  };
}

/** Seed the form from a loaded profile `p` (PaginaLocal shape). */
/**
 * Los valores viejos del desplegable de distancia, traducidos al catalogo real.
 *
 * El `<select>` tenia NUEVE opciones escritas a mano y cinco de ellas no
 * existian en `RACE_FORMATS` (services/ai/race_distances.py): Sprint, Olympic,
 * half_ironman, ironman y gran_fondo. El catalogo si sabe que un olimpico son
 * 1500 m + 40 km + 10 km, pero como la clave no casaba, ninguna meta de
 * triatlon se podia convertir en metros -- y `goal_realism` no podia decir que
 * ritmo exige el objetivo ni compararlo con las predicciones del reloj.
 *
 * Se traduce AL LEER, no con una migracion: los cinco atletas de la beta ven su
 * carrera bien puesta la proxima vez que abran el perfil, y queda guardada con
 * la clave buena en cuanto pulsen Guardar. Sin fecha de caducidad para el mapa:
 * cuesta cinco lineas y evita que un perfil viejo se quede en blanco.
 */
/**
 * `true` si el valor es una clave de triatlon del catalogo (`tri_*`).
 *
 * Sirve para distinguir lo que ESTE codigo derivo de la meta de lo que un
 * atleta escribio a mano hace meses ("Olympic", "Ironman 70.3"). Lo escrito a
 * mano se conserva; lo derivado se recalcula.
 */
/**
 * Una meta escrita a mano, traducida a la clave del catalogo cuando se puede.
 *
 * Cubre los cinco valores del desplegable viejo (`DISTANCIAS_HEREDADAS`) y las
 * claves que solo difieren en mayusculas -- el atleta 2 tiene guardado `"10k"`
 * y el catalogo lo llama `"10K"`. Lo que no reconoce lo devuelve INTACTO: una
 * meta que no sabemos traducir no se borra ni se cambia por otra parecida.
 */
export function normalizarMeta(bruto) {
  const v = (bruto || '').trim();
  if (!v) return '';
  if (DISTANCIAS_HEREDADAS[v]) return DISTANCIAS_HEREDADAS[v];
  const porMayusculas = { '5k': '5K', '10k': '10K', '15k': '15K', '50k': '50K', '100k': '100K' };
  return porMayusculas[v.toLowerCase()] || v;
}

export const esClaveDeTriatlon = (v) => typeof v === 'string' && v.trim().startsWith('tri_');

export const DISTANCIAS_HEREDADAS = {
  Sprint: 'tri_sprint',
  Olympic: 'tri_olympic',
  half_ironman: 'tri_70_3',
  ironman: 'tri_ironman',
  gran_fondo: 'bike_gran_fondo',
};

export function buildFormData(p) {
  const f = emptyFormData();
  if (!p) return f;

  const copy = (keys) => keys.forEach((k) => {
    if (p[k] !== undefined && p[k] !== null) f[k] = p[k];
  });

  copy([
    'name', 'sports_preference', 'garmin_email', 'target_distance',
    'training_age_level', 'years_training', 'current_weekly_volume_hours',
    'longest_recent_session_hours', 'weight_kg', 'height_cm', 'gender', 'birth_date',
    'resting_heart_rate', 'vo2max_running', 'vo2max_cycling', 'lactate_threshold_hr',
    'lactate_threshold_pace_sec_per_km', 'ftp_watts', 'swim_threshold_pace_sec_per_100m',
    'easy_run_pace_fast_sec_per_km', 'easy_run_pace_slow_sec_per_km',
    'pool_access', 'pool_length_m', 'bike_setup', 'altitude_m', 'climate', 'terrain',
    'occupation_type', 'avg_sleep_hours', 'stress_level_self_report', 'travel_frequency',
    'nutrition_notes', 'menstrual_cycle_notes', 'past_injuries_surgeries',
    'chronic_conditions', 'medications', 'limitations', 'coaching_tone',
    'preferred_rest_day', 'week_start_day',
  ]);

  ['treadmill_access', 'menstrual_tracking_opt_in'].forEach((k) => {
    if (p[k] !== undefined) f[k] = Boolean(p[k]);
  });
  ['cross_training_open', 'ai_load_guardrail_enabled'].forEach((k) => {
    if (p[k] !== undefined && p[k] !== null) f[k] = Boolean(p[k]);
  });

  // La meta guardada, traducida al catalogo. Los cinco atletas de la beta la
  // escribieron a mano ("Olympic", "10k") cuando esto era un campo de texto, y
  // sin esto se les quedaria el desplegable en blanco -- que al primer Guardar
  // les borraria la meta. Lo que no case se conserva tal cual y el `<select>`
  // lo enseña como su propia opcion. Mismo criterio que con las competiciones.
  f.target_distance = normalizarMeta(p.target_distance);

  f.recent_prs = leerMarcas(p.recent_prs);
  // El test de umbral guardado, por deporte (Database/023).
  f.threshold_tests = (p.threshold_tests && typeof p.threshold_tests === 'object' && !Array.isArray(p.threshold_tests))
    ? { ...p.threshold_tests } : {};
  // La fuerza (Database/025). El recuadro edita una copia del texto: si deja de
  // ser el de la rutina ordenada, al guardar se desconfirma (utils/fuerza.js).
  f.strength_profile = perfilDeFuerzaAlFormulario(p.strength_profile);
  f.strength_routine = rutinaAlFormulario(p.strength_routine);
  f.strength_routine_texto = f.strength_routine.original_text;

  if (Array.isArray(p.competitions)) {
    f.competitions = p.competitions.map((c) => ({
      name: c.name || '',
      date: c.date || '',
      race_type: c.race_type || '',
      priority: c.priority || 'B',
      distance: DISTANCIAS_HEREDADAS[c.distance] || c.distance || '',
      elevation_gain_m: c.elevation_gain_m ?? '',
      target_time: c.target_time || '',
    }));
  }

  if (Array.isArray(p.current_injuries)) {
    f.current_injuries = p.current_injuries.map((i) => ({
      location: i.location || '',
      laterality: i.laterality || '',
      onset: i.onset || '',
      pain_scale: i.pain_scale ?? '',
      aggravating_factors: i.aggravating_factors || '',
      treatment: i.treatment || '',
      // Sin `status` se asume ACTIVA: es lo que significaba la fila antes de
      // que la columna existiera, y las de los cinco atletas de la beta no lo
      // traen. Ver Database/014.
      status: i.status === 'resuelta' ? 'resuelta' : 'activa',
      notes: i.notes || '',
    }));
  }

  // La rejilla deporte x dia. Si el perfil todavia no la tiene -- los cinco
  // atletas de la beta no la tienen -- se DERIVA de sus franjas horarias, y
  // todo entra como `posible`, que es exactamente lo que significaba un bloque:
  // "puedo", nunca "quiero". Asi nadie abre el perfil y se lo encuentra vacio,
  // y nadie estrena la rejilla con peticiones que no ha hecho.
  f.sportDays = normalizarRejilla(p.sport_days);
  if (!Object.keys(f.sportDays).length) {
    f.sportDays = rejillaDesdeBloques(p.day_time_blocks, p.available_days);
  }

  // Day availability from available_days + day_time_blocks
  const blocks = (p.day_time_blocks && typeof p.day_time_blocks === 'object') ? p.day_time_blocks : {};
  const availableDays = Array.isArray(p.available_days) ? p.available_days : [];
  WEEK_DAYS.forEach((d) => {
    const dayBlocks = Array.isArray(blocks[d]) ? blocks[d] : [];
    f.days[d] = {
      enabled: availableDays.includes(d) || dayBlocks.length > 0,
      blocks: dayBlocks.map((b) => ({
        start: b.start || '',
        end: b.end || '',
        sports: Array.isArray(b.sports) ? [...b.sports] : [],
      })),
    };
  });

  // Fields we keep round-tripping but don't edit here.
  f._carry = {
    planning_context: '',
    analysis_context: p.analysis_context || '',
    garmin_autofilled_fields: Array.isArray(p.garmin_autofilled_fields) ? p.garmin_autofilled_fields : [],
    ai_mode: p.ai_mode || 'pro',
    triathlon_distance: p.triathlon_distance || '',
    run_distance: p.run_distance || '',
    bike_distance: p.bike_distance || '',
  };

  return f;
}

const num = (v) => {
  if (v === '' || v === null || v === undefined) return null;
  const n = Number(v);
  return Number.isFinite(n) ? n : null;
};

/** Build the PaginaLocal-shaped payload the backend PUT /profile expects. */
/**
 * La distancia de la carrera A que viene antes (la más cercana desde hoy), o '' si no hay.
 *
 * Desde el 2026-10-21 (decisión del responsable) la «distancia principal» sale de ahí: era un selector aparte
 * que repetía la de la carrera A y podía contradecirla. Sin carrera A con distancia, manda el selector.
 */
export function distanciaDeLaCarreraA(competiciones, hoy = new Date()) {
  const dos = (n) => String(n).padStart(2, '0');
  const hoyIso = `${hoy.getFullYear()}-${dos(hoy.getMonth() + 1)}-${dos(hoy.getDate())}`;
  const a = (competiciones || [])
    .filter((c) => c && c.priority === 'A' && c.distance && (!c.date || c.date >= hoyIso))
    .sort((x, y) => (x.date || '9999').localeCompare(y.date || '9999'))[0];
  return a ? a.distance : '';
}

export function assembleProfilePayload(f) {
  const available_days = [];
  const day_time_blocks = {};
  const hours_per_day = {};
  const activities_per_day = {};

  WEEK_DAYS.forEach((d) => {
    const day = f.days[d];
    if (!day || !day.enabled) return;
    available_days.push(d);

    const cleanBlocks = (day.blocks || [])
      .filter((b) => b.start && b.end)
      .map((b) => ({ start: b.start, end: b.end, sports: [...(b.sports || [])] }));

    if (cleanBlocks.length) day_time_blocks[d] = cleanBlocks;

    let dayHours = 0;
    const daySports = new Set();
    cleanBlocks.forEach((b) => {
      const [sh, sm] = b.start.split(':').map(Number);
      const [eh, em] = b.end.split(':').map(Number);
      let diff = (eh * 60 + em) - (sh * 60 + sm);
      if (diff < 0) diff += 24 * 60;
      dayHours += diff / 60;
      (b.sports || []).forEach((s) => daySports.add(s));
    });
    if (dayHours > 0) hours_per_day[d] = Math.round(dayHours * 100) / 100;
    activities_per_day[d] = daySports.size ? [...daySports] : ['Cualquiera'];
  });

  // ---- La rejilla manda -----------------------------------------------------
  //
  // Cuando el atleta ha contestado la rejilla, es ELLA la que dice que dias
  // entrena y de que, y se recalculan `available_days` y `activities_per_day`
  // encima de lo derivado de las franjas. Se recalculan y no se sustituyen a
  // medias a proposito: el planificador y el validador leen esos dos campos
  // desde siempre, y si dijeran una cosa distinta de la rejilla tendriamos dos
  // verdades sobre el mismo dia -- que es como se pierde un dato en este repo.
  //
  // `day_time_blocks` y `hours_per_day` se quedan tal cual venian. Ya no se
  // editan en ningun sitio, pero son los datos que los cinco atletas de la beta
  // metieron a mano y no se tiran por un cambio de pantalla.
  const sport_days = {};
  GRID_SPORTS.forEach((dep) => {
    const fila = (f.sportDays && f.sportDays[dep]) || {};
    const marcados = {};
    WEEK_DAYS.forEach((d) => {
      if (fila[d] === ESTADO_FIJO || fila[d] === ESTADO_POSIBLE) marcados[d] = fila[d];
    });
    if (Object.keys(marcados).length) sport_days[dep] = marcados;
  });

  if (Object.keys(sport_days).length) {
    available_days.length = 0;
    Object.keys(activities_per_day).forEach((k) => delete activities_per_day[k]);
    WEEK_DAYS.forEach((d) => {
      const { fijos, posibles } = deportesDelDia(f.sportDays, d);
      const todos = [...fijos, ...posibles];
      if (!todos.length) return;
      available_days.push(d);
      activities_per_day[d] = todos;
    });
  }

  // Se guarda como LISTA. Las filas sin tiempo se tiran: una marca sin marca
  // no es nada, y dejarlas llenaria el prompt de huecos con nombre.
  const recent_prs = {
    marks: (f.recent_prs.marks || [])
      .filter((m) => (m.time || '').trim() && ((m.label || '').trim() || (m.key || '').trim()))
      // `libre` NO se guarda: es estado de la vista (que control enseñar), no
      // un dato del atleta. Al releer se deduce de si hay clave.
      .map((m) => ({
        key: (m.key || '').trim(),
        label: (m.label || '').trim(),
        time: (m.time || '').trim(),
        date: (m.date || '').trim(),
      })),
    notes: (f.recent_prs.notes || '').trim(),
  };

  const competitions = (f.competitions || [])
    .filter((c) => (c.name || '').trim() || c.date)
    .map((c) => ({
      name: (c.name || '').trim(),
      date: c.date || '',
      race_type: c.race_type || '',
      priority: c.priority || 'B',
      distance: c.distance || '',
      elevation_gain_m: num(c.elevation_gain_m),
      target_time: (c.target_time || '').trim(),
    }));

  const current_injuries = (f.current_injuries || [])
    .filter((i) => (i.location || '').trim() || (i.onset || '').trim())
    .map((i) => ({
      location: (i.location || '').trim(),
      laterality: i.laterality || '',
      onset: (i.onset || '').trim(),
      pain_scale: num(i.pain_scale),
      aggravating_factors: (i.aggravating_factors || '').trim(),
      treatment: (i.treatment || '').trim(),
      status: i.status === 'resuelta' ? 'resuelta' : 'activa',
      notes: (i.notes || '').trim(),
    }));

  const carry = f._carry || {};
  const meta = distanciaDeLaCarreraA(f.competitions) || (f.target_distance || '').trim();

  return {
    name: (f.name || '').trim(),
    sports_preference: f.sports_preference || 'triathlon',
    garmin_email: (f.garmin_email || '').trim(),
    target_distance: meta,
    // DERIVADO de la meta, no un campo aparte.
    //
    // `triathlon_distance` viajaba en el perfil y lo leia el prompt, pero
    // NINGUNA vista lo escribia: un triatleta lo tenia en NULL para siempre y
    // el prompt acababa diciendo "training for a None Triathlon". Anadir otro
    // campo al alta era la respuesta facil y la equivocada -- el alta ya hace
    // demasiado ruido (2.D). El atleta tiene UNA meta: si es un formato de
    // triatlon, esta ES su distancia de triatlon.
    //
    // Se vacia cuando la meta deja de ser un triatlon, a proposito: dejar la
    // vieja puesta haria que el prompt siguiera afirmando una distancia que el
    // atleta ya no persigue, que es el mismo fallo con otra ropa.
    triathlon_distance: meta.startsWith('tri_')
      ? meta
      : (esClaveDeTriatlon(carry.triathlon_distance) ? '' : (carry.triathlon_distance || '')),
    run_distance: carry.run_distance || '',
    bike_distance: carry.bike_distance || '',

    available_days,
    sport_days,
    day_time_blocks,
    hours_per_day,
    activities_per_day,
    // SIN `available_hours`. Al atleta ya no se le pregunta cuantas horas
    // tiene a la semana.
    //
    // El campo sumaba las horas de reloj de `day_time_blocks`, y la v1.1
    // sustituyo las franjas por la rejilla deporte x dia -- "sin horas, sin
    // minutos" -- asi que la suma daba 0 y caia en un `|| 8`. TODO atleta con
    // rejilla mandaba un techo de 8 h/semana dijera lo que dijera, y eso
    // llegaba al prompt como "Total Available Weekly Hours: 8 hours net".
    // Verificado el 2026-09-11 contra la cuenta real.
    //
    // El arreglo de la v1.2 no es rellenarlo mejor, es no preguntarlo: el
    // atleta sabe cuantas horas cree que le sobran, no cuantas necesita su
    // plan. El volumen sale de su carga cronica medida en Garmin, de la fase
    // del macrociclo y del margen de progresion.

    planning_context: '',
    analysis_context: carry.analysis_context || '',
    competitions,

    training_age_level: f.training_age_level || '',
    years_training: num(f.years_training),
    current_weekly_volume_hours: num(f.current_weekly_volume_hours),
    longest_recent_session_hours: num(f.longest_recent_session_hours),
    recent_prs,
    // El test de umbral que el atleta usó, por deporte, con su fecha (Database/023).
    threshold_tests: f.threshold_tests && Object.keys(f.threshold_tests).length ? f.threshold_tests : null,
    // Cómo entrena la fuerza y su programa (Database/025). Viajan SIEMPRE: el
    // perfil se guarda entero y un campo que no se mandara quedaría en NULL.
    strength_profile: perfilDeFuerzaParaGuardar(f.strength_profile),
    strength_routine: rutinaParaGuardar(f.strength_routine, f.strength_routine_texto),

    weight_kg: num(f.weight_kg),
    height_cm: num(f.height_cm),
    gender: f.gender || '',
    birth_date: f.birth_date || '',
    resting_heart_rate: num(f.resting_heart_rate),
    vo2max_running: num(f.vo2max_running),
    vo2max_cycling: num(f.vo2max_cycling),
    lactate_threshold_hr: num(f.lactate_threshold_hr),
    lactate_threshold_pace_sec_per_km: num(f.lactate_threshold_pace_sec_per_km),
    ftp_watts: num(f.ftp_watts),
    swim_threshold_pace_sec_per_100m: num(f.swim_threshold_pace_sec_per_100m),
    easy_run_pace_fast_sec_per_km: num(f.easy_run_pace_fast_sec_per_km),
    easy_run_pace_slow_sec_per_km: num(f.easy_run_pace_slow_sec_per_km),

    pool_access: f.pool_access || '',
    pool_length_m: num(f.pool_length_m),
    bike_setup: f.bike_setup || '',
    treadmill_access: Boolean(f.treadmill_access),
    altitude_m: num(f.altitude_m),
    climate: f.climate || '',
    terrain: f.terrain || '',

    occupation_type: f.occupation_type || '',
    avg_sleep_hours: num(f.avg_sleep_hours),
    stress_level_self_report: f.stress_level_self_report || '',
    travel_frequency: f.travel_frequency || '',
    nutrition_notes: (f.nutrition_notes || '').trim(),
    menstrual_tracking_opt_in: Boolean(f.menstrual_tracking_opt_in),
    menstrual_cycle_notes: (f.menstrual_cycle_notes || '').trim(),

    current_injuries,
    past_injuries_surgeries: (f.past_injuries_surgeries || '').trim(),
    chronic_conditions: (f.chronic_conditions || '').trim(),
    medications: (f.medications || '').trim(),
    limitations: (f.limitations || '').trim(),

    coaching_tone: f.coaching_tone || '',
    // Con tabla de días, el descanso es lo que la tabla deja vacío y este campo se vacía (decisión del
    // responsable, 2026-10-21): dejaba elegir un sábado de descanso con un «fijo» el sábado. Un perfil viejo
    // sin tabla conserva el suyo, que es lo único que dice su descanso.
    preferred_rest_day: Object.keys(sport_days).length ? '' : (f.preferred_rest_day || ''),
    week_start_day: f.week_start_day || '',
    cross_training_open: Boolean(f.cross_training_open),
    ai_load_guardrail_enabled: Boolean(f.ai_load_guardrail_enabled),

    // carried over untouched
    garmin_autofilled_fields: carry.garmin_autofilled_fields || [],
    ai_mode: carry.ai_mode || 'pro',
  };
}
