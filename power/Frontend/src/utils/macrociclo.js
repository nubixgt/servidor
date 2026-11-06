/**
 * En qué semana del macrociclo va el atleta, y si ya se le acabó.
 *
 * ## Por qué existe
 *
 * El plan de temporada no tiene duración fija: la decide la IA según las
 * competencias del atleta (uno real salió de 24 semanas en cuatro fases, otro
 * puede salir de 16). Eso está bien. Lo que faltaba era el final.
 *
 * `compute_mesocycle_position` (PaginaLocal/services/ai/langgraph/nodes/
 * weekly_planner_node.py) ya calcula la semana para el prompt, y cuando la
 * fecha se pasa de la última fase devuelve la semana igual pero SIN fase:
 *
 *     "This is week 25 of the season — beyond the season plan's defined
 *      phases (no phase data for this week)."
 *
 * O sea que el planificador se queda sin objetivo de horas y sin énfasis, y
 * sigue escribiendo semanas como si nada. Nadie avisaba, y no había forma de
 * saber que tocaba renovar.
 *
 * ## Por qué el cálculo vive aquí y no en el servidor
 *
 * Depende de dos cosas: `season_phases`, que el cliente ya recibe entero en
 * `GET /plan`, y **qué día es hoy para el atleta** -- que es precisamente lo
 * que el navegador sabe con certeza y el servidor no (por eso el resto del
 * sistema le manda su zona horaria en cada generación). Calcularlo en PHP
 * obligaría a duplicar la aritmética de fechas en un tercer lenguaje sin
 * tests, para acabar preguntándole igual al cliente qué día es.
 *
 * Aquí es una regla de PRESENTACIÓN: cuándo enseñar un aviso. La semana que
 * de verdad manda -- la que entra en el prompt -- se sigue calculando en
 * Python, donde está probada.
 */

/** La fecha de hoy del ATLETA (su navegador), como YYYY-MM-DD. */
export function hoyDelAtleta(fecha = new Date()) {
  const dosDigitos = (n) => String(n).padStart(2, '0');
  return `${fecha.getFullYear()}-${dosDigitos(fecha.getMonth() + 1)}-${dosDigitos(fecha.getDate())}`;
}

/** Días entre dos YYYY-MM-DD, interpretadas como fechas civiles (sin husos). */
function diasEntre(desde, hasta) {
  const aUTC = (s) => {
    const [a, m, d] = String(s).split('-').map(Number);
    if (!a || !m || !d) return null;
    return Date.UTC(a, m - 1, d);
  };
  const x = aUTC(desde);
  const y = aUTC(hasta);
  if (x === null || y === null) return null;
  return Math.round((y - x) / 86400000);
}

const DIAS_DE_LA_SEMANA = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

/**
 * El arranque de sus semanas: el día en que empieza su semana (`week_start_day`)
 * de la semana que contiene `inicio`. Sin día elegido, `inicio` tal cual.
 *
 * Atleta 2, 2026-09-21: macrociclo generado el martes 15, semana que empieza el
 * lunes; el lunes 21 empezó su segunda semana y la tira seguía en la 1, porque
 * contaba bloques de 7 días desde el martes. Mismo cálculo que
 * `arranque_de_sus_semanas` (PaginaLocal/services/ai/semanas_del_macrociclo.py);
 * tests/test_semanas_del_macrociclo.py fija que dan lo mismo.
 */
export function alinearArranque(inicio, weekStartDay) {
  const i = DIAS_DE_LA_SEMANA.indexOf(weekStartDay);
  const [a, m, d] = String(inicio || '').slice(0, 10).split('-').map(Number);
  if (i < 0 || !a || !m || !d) return inicio;
  const fecha = new Date(Date.UTC(a, m - 1, d));
  const diaDeLaSemana = (fecha.getUTCDay() + 6) % 7; // lunes = 0, como weekday() de Python
  fecha.setUTCDate(fecha.getUTCDate() - ((diaDeLaSemana - i + 7) % 7));
  return fecha.toISOString().slice(0, 10);
}

/**
 * Dónde está el atleta dentro de su macrociclo.
 *
 * Devuelve `null` cuando no hay con qué responder -- sin plan de temporada, o
 * con uno viejo sin `season_start_date`. Un `null` es "no se sabe", y quien
 * llama no debe enseñar ningún aviso: inventar una semana 1 para un atleta
 * que no tiene plan sería peor que callar.
 *
 * `semanasQueFaltan` cuenta las que quedan INCLUIDA la actual, que es como lo
 * lee una persona: en la última semana quedan "1", no "0".
 */
export function posicionEnLaTemporada(seasonPhases, hoy = hoyDelAtleta(), weekStartDay = '') {
  const raiz = seasonPhases?.phases ? seasonPhases : { phases: seasonPhases };
  const fases = Array.isArray(raiz?.phases) ? raiz.phases : [];
  const inicio = alinearArranque(raiz?.season_start_date || seasonPhases?.season_start_date, weekStartDay);
  if (!fases.length || !inicio) return null;

  const dias = diasEntre(inicio, hoy);
  if (dias === null) return null;

  // La misma fórmula que compute_mesocycle_position: el día del arranque es la
  // semana 1, no la 0. Antes del arranque (plan generado para empezar mañana)
  // se dice semana 1 y no una semana negativa.
  const semana = Math.max(1, Math.floor(dias / 7) + 1);
  const ultimaSemana = Math.max(...fases.map((f) => Number(f.end_week) || 0));
  if (!ultimaSemana) return null;

  const fase = fases.find(
    (f) => (Number(f.start_week) || 0) <= semana && semana <= (Number(f.end_week) || 0),
  ) || null;

  return {
    semana,
    ultimaSemana,
    fase,
    faseNombre: fase?.name || '',
    esDescarga: !!(fase?.deload_weeks || []).includes(semana),
    vencido: semana > ultimaSemana,
    semanasQueFaltan: Math.max(0, ultimaSemana - semana + 1),
    // Dos semanas de margen: renovar el macrociclo es la llamada más cara del
    // producto y tarda varios minutos, así que avisar el mismo día en que se
    // acaba deja al atleta sin plan justo cuando le toca entrenar.
    porVencer: semana <= ultimaSemana && ultimaSemana - semana + 1 <= 2,
    // Más allá de las semanas firmes el macrociclo era un mapa: toca rehacerlo
    // con cómo respondió el cuerpo. Mismo número que SEMANAS_FIRMES en
    // season_planner_node.py, que es el que se lo dice al modelo.
    porRevisar: semana > SEMANAS_FIRMES && semana <= ultimaSemana && ultimaSemana - semana + 1 > 2,
  };
}

/**
 * Cuántas semanas de un macrociclo son firmes. El resto es un mapa hacia la
 * carrera A que conviene rehacer con cómo respondió el cuerpo. Tiene que valer
 * lo mismo que SEMANAS_FIRMES en season_planner_node.py.
 */
export const SEMANAS_FIRMES = 16;

/** El aviso que toca enseñar, o null si no toca ninguno. */
export function avisoDeMacrociclo(posicion) {
  if (!posicion) return null;
  if (posicion.vencido) {
    return {
      tono: 'vencido',
      titulo: 'Tu macrociclo terminó',
      texto:
        `Vas por la semana ${posicion.semana} y tu plan de temporada llegaba hasta la `
        + `${posicion.ultimaSemana}. A partir de aquí tu entrenador escribe las semanas sin `
        + 'objetivo de horas ni énfasis de fase, porque ya no hay fase que consultar. '
        + 'Genera un macrociclo nuevo: parte de tus datos de hoy, no de los de hace meses.',
      accion: 'Generar macrociclo nuevo',
    };
  }
  if (posicion.porVencer) {
    const n = posicion.semanasQueFaltan;
    return {
      tono: 'aviso',
      titulo: n === 1 ? 'Última semana de tu macrociclo' : `Quedan ${n} semanas de tu macrociclo`,
      texto:
        `Estás en la semana ${posicion.semana} de ${posicion.ultimaSemana}. Cuando se acabe, `
        + 'conviene generar uno nuevo para que las semanas siguientes tengan fase y objetivo. '
        + 'Puedes hacerlo ya: el nuevo reemplaza al actual y no pierdes tus semanas generadas.',
      accion: 'Generar macrociclo nuevo',
    };
  }
  if (posicion.porRevisar) {
    return {
      tono: 'aviso',
      titulo: 'Toca revisar tu macrociclo',
      texto:
        `Vas por la semana ${posicion.semana}. Tu plan de temporada detallaba las primeras ${SEMANAS_FIRMES}; `
        + 'las fases siguientes quedaron por confirmar según tu progresión. Rehazlo con tus datos de hoy: '
        + 'el nuevo parte de cómo respondiste a cada fase y no pierdes tus semanas generadas.',
      accion: 'Generar macrociclo nuevo',
    };
  }
  return null;
}

/**
 * La semana vigente se acaba, y hay que generar la siguiente.
 *
 * ## Por qué hacía falta
 *
 * El macrociclo avisaba de su final; la SEMANA no avisaba de nada. El atleta
 * tenía que acordarse solo de volver a entrar y generar — y la beta dejó el
 * número que decide el MVP en **0 semanas cerradas de 37 generaciones**. Un
 * bucle que nadie cierra no se mide, y la primera sospecha no es que no
 * quisieran: es que nadie se lo recordó.
 *
 * ## Por qué avisa a DOS días y no el mismo día
 *
 * Porque `DIAS_DE_ANTELACION = 2` en server.py es exactamente cuando el
 * backend deja generarla, y avisar antes sería ofrecer un botón que devuelve
 * 409. A un día ya es tarde para quien entrena temprano: se enteraría la noche
 * antes de una sesión que aún no existe.
 *
 * El texto distingue los dos casos porque son distintos para el atleta:
 * "termina mañana" es una cosa y "ya terminó, llevas N días sin plan" es otra
 * bastante peor.
 *
 * @param {string} ultimoDia  YYYY-MM-DD del último día del plan vigente.
 * @param {string} hoy        YYYY-MM-DD del atleta.
 */
export function avisoDeSemana(ultimoDia, hoy = hoyDelAtleta()) {
  const faltan = diasEntre(hoy, ultimoDia);
  if (faltan === null) return null;

  if (faltan < 0) {
    const dias = Math.abs(faltan);
    return {
      tono: 'vencido',
      titulo: dias === 1 ? 'Tu semana terminó ayer' : `Llevas ${dias} días sin plan`,
      texto:
        'Genera la siguiente: se escribe con tus datos de HOY —tu sueño, tu variabilidad, '
        + 'lo que entrenaste— así que cuanto antes la generes, mejor te encaja.',
      accion: 'Generar la siguiente semana',
    };
  }
  // A DOS días todavía no se puede: el backend abre la ventana dos días antes
  // del ARRANQUE de la siguiente, que es el día después del último (ver
  // ventana_de_generacion). Decir "ya puedes" aquí mandaba al atleta a un
  // diálogo de "todavía no toca" -- cada semana. Auditoría previa al merge.
  if (faltan === 2) {
    return {
      tono: 'aviso',
      titulo: 'Tu semana termina en 2 días',
      texto:
        'Mañana podrás generar la siguiente. Se escribe con tus datos de ese momento, '
        + 'así que esperar un día la hace más tuya.',
      accion: null,
    };
  }
  if (faltan <= 1) {
    return {
      tono: 'aviso',
      titulo: faltan === 0 ? 'Tu semana termina hoy' : 'Tu semana termina mañana',
      texto:
        'Ya puedes generar la siguiente. Lo ideal es hacerlo el día antes de que empiece: '
        + 'el plan se escribe con tus datos de ese momento, no con los de hace una semana.',
      accion: 'Generar la siguiente semana',
    };
  }
  return null;
}

/**
 * Cuántos días antes del arranque de una semana se puede generar. Tiene que valer lo
 * mismo que DIAS_DE_ANTELACION en PaginaLocal/web_app/server.py.
 */
export const DIAS_DE_ANTELACION = 2;

/**
 * Cuántas semanas por delante de la en curso puede tener escritas: una. Tiene que valer lo
 * mismo que SEMANAS_POR_DELANTE en PaginaLocal/web_app/server.py (ventana_de_generacion).
 */
export const SEMANAS_POR_DELANTE = 1;

/**
 * Si generar otra semana chocaría con el tope de dos. La misma cuenta que
 * `ventana_de_generacion`: la siguiente empieza el día después de su último día (o hoy, si
 * se quedó atrás) y no puede empezar más allá de hoy + 7 × SEMANAS_POR_DELANTE. Sirve para
 * decirlo ANTES de pedirle el check-in: preguntarle cómo llega y responder "no toca" después
 * era hacerle contestar para nada (auditoría del 2026-09-24).
 */
export function topeDeSemanas(ultimoDia, hoy = hoyDelAtleta()) {
  if (!ultimoDia) return null;
  const siguiente = sumarDias(ultimoDia, 1);
  const inicio = siguiente > hoy ? siguiente : hoy;
  if (inicio <= sumarDias(hoy, SEMANAS_POR_DELANTE * 7)) return null;
  return { siguiente: inicio, desde: sumarDias(inicio, -7 * SEMANAS_POR_DELANTE) };
}

/**
 * Si todavía no toca generar la siguiente semana (la ventana de DIAS_DE_ANTELACION). La misma
 * cuenta que `ventana_de_generacion`: se puede desde DIAS_DE_ANTELACION días antes de que empiece.
 * Sirve para decirlo ANTES del check-in, como el tope (auditoría del 2026-09-24). null si ya toca.
 */
export function antelacionDeLaSemana(ultimoDia, hoy = hoyDelAtleta()) {
  if (!ultimoDia) return null;
  const siguiente = sumarDias(ultimoDia, 1) > hoy ? sumarDias(ultimoDia, 1) : hoy;
  const desde = sumarDias(siguiente, -DIAS_DE_ANTELACION);
  return hoy >= desde ? null : { siguiente, desde };
}

function sumarDias(iso, n) {
  const [a, m, d] = String(iso || '').slice(0, 10).split('-').map(Number);
  if (!a || !m || !d) return '';
  const f = new Date(Date.UTC(a, m - 1, d));
  f.setUTCDate(f.getUTCDate() + n);
  return f.toISOString().slice(0, 10);
}

/**
 * "Tu mes": la semana en curso y las tres siguientes, tal como las prevé el macrociclo.
 *
 * Es una VISTA, sin IA (el responsable, 2026-09-24): quien quiere ver el mes por delante
 * ve la forma que tiene -- fase, descargas, carreras, en qué se centra -- sin que se
 * generen ni se suban al reloj cuatro semanas escritas con el retrato de hoy. Las sesiones
 * de verdad se siguen escribiendo semana a semana.
 *
 * `semanasGeneradas`: las fechas de los días que ya tiene escritos. `competiciones`: las
 * del perfil. Devuelve [] si no hay macrociclo con fecha de arranque.
 */
export function semanasDelMes(seasonPhases, {
  hoy = hoyDelAtleta(), weekStartDay = '', competiciones = [], diasGenerados = [], cuantas = 4,
} = {}) {
  const posicion = posicionEnLaTemporada(seasonPhases, hoy, weekStartDay);
  if (!posicion) return [];
  const raiz = seasonPhases?.phases ? seasonPhases : { phases: seasonPhases };
  const fases = raiz.phases || [];
  const inicio = alinearArranque(raiz?.season_start_date || seasonPhases?.season_start_date, weekStartDay);
  const generados = new Set((diasGenerados || []).map((d) => String(d).slice(0, 10)));
  const salida = [];
  for (let k = 0; k < cuantas; k += 1) {
    const numero = posicion.semana + k;
    if (numero > posicion.ultimaSemana) break;
    const desde = sumarDias(inicio, (numero - 1) * 7);
    const hasta = sumarDias(desde, 6);
    const fase = fases.find((f) => (Number(f.start_week) || 0) <= numero && numero <= (Number(f.end_week) || 0)) || null;
    const carreras = (competiciones || [])
      .filter((c) => c?.date && String(c.date).slice(0, 10) >= desde && String(c.date).slice(0, 10) <= hasta)
      .map((c) => ({ nombre: c.name || 'Carrera', prioridad: c.priority || '', fecha: String(c.date).slice(0, 10) }));
    let generada = false;
    for (let i = 0; i < 7 && !generada; i += 1) generada = generados.has(sumarDias(desde, i));
    salida.push({
      numero,
      desde,
      hasta,
      actual: k === 0,
      fase: fase?.name || '',
      descarga: !!(fase?.deload_weeks || []).includes(numero),
      carreras,
      horas: Number(fase?.weekly_hours_target) || null,
      foco: (fase?.focus || []).filter(Boolean).slice(0, 2),
      generada,
      generableDesde: sumarDias(desde, -DIAS_DE_ANTELACION),
    });
  }
  return salida;
}

/**
 * El día en que empezó la fase en la que está hoy el atleta ("YYYY-MM-DD"), o null si no se sabe.
 * Misma cuenta que `posicionEnLaTemporada`: la semana N empieza (N-1)*7 días después del arranque.
 */
export function inicioDeLaFaseActual(seasonPhases, hoy = hoyDelAtleta(), weekStartDay = '') {
  const pos = posicionEnLaTemporada(seasonPhases, hoy, weekStartDay);
  if (!pos?.fase) return null;
  const raiz = seasonPhases?.phases ? seasonPhases : { phases: seasonPhases };
  const inicio = alinearArranque(raiz?.season_start_date || seasonPhases?.season_start_date, weekStartDay);
  const semana = Number(pos.fase.start_week) || 1;
  const [a, m, d] = String(inicio).split('-').map(Number);
  const f = new Date(Date.UTC(a, m - 1, d + (semana - 1) * 7));
  return f.toISOString().slice(0, 10);
}

/**
 * ¿Toca revisar el programa de fuerza? (el responsable, 2026-10-21: «la fuerza se queda ahí, ¿cuántas
 * semanas dura o es eterna?»). Sin número de semanas nuestro: cuando EMPIEZA UNA FASE nueva del
 * macrociclo y el programa se confirmó antes. Es el momento en que la periodización cambia lo que busca
 * la fuerza (`strength_quality` de cada fase) y en que la evidencia sitúa el cambio de ejercicios
 * (mesociclos; en concurrente, fuerza pesada en la base y mantenimiento cerca de la carrera). Dentro de
 * la fase el programa se mantiene a propósito: es lo que deja progresar la carga semana a semana.
 *
 * @returns {{fase: string, desde: string, confirmado: string}|null}
 */
export function fuerzaPorRevisar({ confirmadoEl, seasonPhases, hoy = hoyDelAtleta(), weekStartDay = '' }) {
  if (!confirmadoEl) return null;
  const desde = inicioDeLaFaseActual(seasonPhases, hoy, weekStartDay);
  const confirmado = String(confirmadoEl).slice(0, 10);
  if (!desde || confirmado >= desde) return null;
  const pos = posicionEnLaTemporada(seasonPhases, hoy, weekStartDay);
  return { fase: pos?.faseNombre || '', desde, confirmado };
}
