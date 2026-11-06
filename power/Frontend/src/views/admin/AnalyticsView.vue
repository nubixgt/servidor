<template>
  <div class="space-y-7 pb-20 select-none animate-fade-in-up">
    <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-5 border-b border-white/10">
      <div>
        <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">Progreso</h2>
        <!-- Sin «(sin IA)» (revisión del 2026-10-21): contaba cómo funciona por dentro. Actualizar va junto a su fecha,
             como algo secundario: era el botón más grande de la pantalla. -->
        <p class="text-sm text-white/80 mt-1 font-medium">Lo que has entrenado, con los datos de tu reloj.</p>
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1 text-[12px] text-white/65">
          <span v-if="lastRefresh">Actualizado el {{ fechaConAnio(lastRefresh) }}</span>
          <button type="button" @click="doRefresh" :disabled="refreshing"
                  class="inline-flex items-center gap-1 min-h-[44px] px-2 rounded-lg font-bold text-[#caf300] hover:bg-white/5 disabled:opacity-50">
            <span class="material-symbols-outlined text-[16px]" :class="refreshing ? 'animate-spin' : ''">refresh</span>
            {{ refreshing ? 'Actualizando…' : 'Actualizar' }}
          </button>
        </div>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
      <!-- Sólo el administrador: las gráficas técnicas (ACWR, Load Focus, VFC, estrés…). -->
      <button
        v-if="store.isAdmin"
        type="button"
        @click="alternarVistaTecnica"
        :aria-pressed="String(vistaTecnica)"
        class="flex items-center gap-1.5 px-3.5 py-2.5 glass-pill rounded-xl text-[11px] font-black uppercase tracking-wider transition-colors"
        :class="vistaTecnica ? 'text-[#caf300]' : 'text-[#8f9378] hover:text-white'"
      >
        <span class="material-symbols-outlined text-[16px]">science</span>
        Vista técnica
      </button>
      </div>
    </header>

    <p v-if="msg" class="glass-card rounded-2xl p-4 border text-sm font-bold" :class="msgError ? 'border-[#ffb4ab]/40 text-[#ffb4ab]' : 'border-[#caf300]/40 text-[#caf300]'">
      {{ msg }}
    </p>

    <div v-if="loading" class="p-8 text-center glass-card rounded-3xl text-[#c5c9ac] text-sm border border-white/10">Cargando…</div>

    <div
      v-else-if="!hasAny"
      class="p-10 text-center glass-card rounded-3xl text-[#c5c9ac] text-sm border border-white/10 leading-relaxed space-y-2"
    >
      <span class="material-symbols-outlined text-4xl text-[#8f9378] block">monitoring</span>
      <p>Todavía no hay suficientes datos para tus gráficas.</p>
      <p class="text-xs">
        Se llenan con cada semana que entrenas, y con «Actualizar» para tu forma y tus tiempos de carrera.
      </p>
    </div>

    <template v-else>
      <!-- Racha, nivel y medallas (Beta 3.0, F8), antes que las gráficas. -->
      <TuProgreso v-if="charts.progreso" :progreso="charts.progreso" />
      <section class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <ChartCard v-for="(c, i) in visibles" :key="c.key" :spec="c"
                   :class="visibles.length % 2 === 1 && i === visibles.length - 1 ? 'lg:col-span-2' : ''" />
      </section>
    </template>
  </div>
</template>

<script setup>
import { estadoDeGarmin, num } from '../../utils/garmin';
import { SPORT_ES, DISCIPLINA_ES, fechaCorta, COLOR_DEL_DEPORTE } from '../../utils/deportes';
import { ref, computed, onMounted, h } from 'vue';
import { RouterLink } from 'vue-router';
import swal, { textoDe } from '../../utils/swal';
import { useAppStore } from '../../stores/appStore';
import { getCharts, refreshCharts } from '../../services/plan';
import { getGarminPasswordStatus } from '../../services/profile';
import { TARJETAS_DEL_ATLETA, guardarVistaTecnica, vistaTecnicaGuardada } from '../../utils/progreso';
import TuProgreso from '../../components/progreso/TuProgreso.vue';

const store = useAppStore();

const data = ref({ charts: {} });
const loading = ref(true);
const refreshing = ref(false);
const msg = ref('');
const msgError = ref(false);

const charts = computed(() => data.value?.charts || {});
const lastRefresh = computed(() => data.value?.last_refresh || '');
const hasAny = computed(() => visibles.value.length > 0 || !!charts.value.progreso);

// Beta 3.0, F4: el atleta ve las que entiende; el administrador puede ver todas.
const vistaTecnica = ref(vistaTecnicaGuardada());
const alternarVistaTecnica = () => {
  vistaTecnica.value = !vistaTecnica.value;
  guardarVistaTecnica(vistaTecnica.value);
};

const load = async () => {
  loading.value = true;
  try { data.value = await getCharts(); } catch (e) { data.value = { charts: {} }; }
  loading.value = false;
  refrescarSiTocaYSePuede();
};

/**
 * El ACWR y la forma, del dia de HOY, sin pedir nada.
 *
 * Estas series se archivaban una vez por generacion, asi que entre semana el
 * panel ensenaba numeros de hace dias -- y el ACWR de hace tres dias no dice
 * nada util sobre si hoy toca apretar. `dashboard_refresh` ya sabia
 * refrescarlas con tres llamadas baratas a Garmin, pero solo corria si el
 * atleta pulsaba el boton Y escribia su contrasena.
 *
 * Desde que la contrasena se guarda cifrada eso deja de hacer falta: si la
 * foto no es de hoy y hay contrasena guardada, se refresca sola al entrar.
 *
 * Silencioso a proposito. Es una mejora del dato, no una accion del atleta:
 * si Garmin no contesta se queda lo que ya habia -- que es exactamente lo que
 * habria visto -- y el boton manual sigue ahi para intentarlo a la vista.
 * `stale` viene del servicio y sigue mostrandose, asi que un dato viejo nunca
 * se hace pasar por fresco.
 */
const refrescarSiTocaYSePuede = async () => {
  if (!data.value?.stale || refreshing.value) return;
  try {
    if (!(await getGarminPasswordStatus())?.saved) return;
    refreshing.value = true;
    data.value = await refreshCharts('');   // '' = usa la guardada
  } catch (e) {
    console.warn('No se pudo refrescar el panel desde Garmin:', e?.message || e);
  } finally {
    refreshing.value = false;
  }
};

/**
 * Actualizar a mano. Sólo pide la contraseña si NO hay una guardada.
 *
 * Seguía preguntándola siempre, incluso con la contraseña cifrada en el
 * perfil -- que es exactamente lo que se guardó para no tener que escribirla.
 * El diálogo sólo aparece cuando de verdad hace falta.
 */
const doRefresh = async () => {
  let pw = '';
  const guardada = await getGarminPasswordStatus().catch(() => null);
  if (!guardada?.saved) {
    const r = await swal.fire({
      title: 'Actualizar desde Garmin',
      text: 'Hace 3 consultas rápidas a Garmin (estado de entrenamiento, predicciones y recuperación).',
      input: 'password',
      inputPlaceholder: 'Contraseña de Garmin Connect',
      showCancelButton: true,
      confirmButtonText: 'Actualizar',
      cancelButtonText: 'Cancelar',
    });
    if (!r.isConfirmed) return;
    pw = textoDe(r);
    if (!pw) return;
  }
  refreshing.value = true;
  msg.value = '';
  try {
    data.value = await refreshCharts(pw);
    msgError.value = false;
    msg.value = 'Datos de Garmin actualizados.';
  } catch (e) {
    msgError.value = true;
    msg.value = e?.response?.data?.message || 'No se pudo conectar con Garmin.';
  } finally {
    refreshing.value = false;
  }
};

onMounted(load);

// ---- card specs (order = most important question first, like PaginaLocal) ----
const cards = computed(() => {
  const g = charts.value;
  const list = [];
  // `vKey: 'value'` y no 'seconds': los puntos que manda charts.py son
  // `{label, value}`, así que 'seconds' no existía en ninguno. `Number(
  // undefined) || 0` daba CERO en todos, la serie salía plana en cero y la
  // tarjeta aparecía vacía con sólo las dos fechas debajo -- que es
  // exactamente lo que se veía en pantalla.
  if (g.race_goal) list.push({ key: 'race_goal', kind: 'line', title: 'Tu meta y lo que predice tu reloj', c: g.race_goal, vKey: 'value', label: labelRaceGoal(g.race_goal), note: g.race_goal.note, goal: g.race_goal.goal_seconds, goalLabel: g.race_goal.goal_clock, fmtY: clock, sub2: progresoHaciaLaMeta(g.race_goal) });
  if (g.plan_vs_reality) list.push({ key: 'pvr', kind: 'pair', title: 'Lo planeado y lo que hiciste', c: g.plan_vs_reality, a: 'prescribed', b: 'executed', la: 'Planeado', lb: 'Hecho', unit: 'h', sinJuicio: true, sub: `Las horas de tus últimas ${g.plan_vs_reality.points.length} semanas: ${num(g.plan_vs_reality.total_executed)} h de ${num(g.plan_vs_reality.total_prescribed)} h (${g.plan_vs_reality.pct ?? '—'} %). Si una semana suma a tu racha lo deciden las sesiones, arriba.` });
  if (g.quality) list.push({ key: 'quality', kind: 'pair', title: 'Entrenos clave hechos', c: g.quality, a: 'prescribed', b: 'done', la: 'Planeados', lb: 'Hechos', unit: '', sub: `Las sesiones de series y ritmo: ${g.quality.total_done} de ${g.quality.total_prescribed} en tus últimas ${g.quality.points.length} semanas.` });
  if (g.weekday) list.push({ key: 'weekday', kind: 'bars', title: 'Qué días cumples lo que toca', c: g.weekday, vKey: 'pct', unit: '%', note: g.weekday.note,
    enlace: g.weekday.note ? { to: '/admin/perfil?tab=objetivos', texto: 'Cambiar mis días' } : null,
    sub: 'En ámbar, los días por debajo del 70 %. Atenuados, los días con menos de 3 sesiones: todavía no dicen nada.' });
  // La linea gruesa es la de GARMIN (la que el atleta ve en su reloj) y la
  // tenue la nuestra. Ver load_trend en charts.py: hasta ahora solo existia la
  // nuestra, una foto por generacion, o sea dos puntos iguales.
  if (g.load) list.push({
    key: 'load', kind: 'line', c: g.load, vKey: 'acwr',
    title: g.load.source === 'garmin' ? 'Carga aguda:crónica (ACWR de tu reloj)' : 'Carga aguda:crónica (ACWR)',
    label: [
      `Actual: ${fmt(g.load.current)}`,
      g.load.garmin_status ? `Garmin lo llama ${estadoDeGarmin(g.load.garmin_status)}` : '',
      g.load.own_current != null && g.load.source === 'garmin' ? `esta app calcula ${fmt(g.load.own_current)}` : '',
    ].filter(Boolean).join(' · '),
    note: g.load.note, band: g.load.sweet_spot, bandLabel: g.load.band_label || 'referencia de esta app',
    serie2: g.load.own_points, serie2Label: 'cálculo de esta app',
  });
  // `kind: 'text'` pintaba SOLO el veredicto ("AEROBIC_LOW_FOCUS") y la nota,
  // y tiraba los datos: charts.py manda `points` con la carga de cada sistema
  // y el rango objetivo que Garmin calcula para este atleta. Nunca hubo
  // grafica -- no es que se rompiera, es que no se dibujaba.
  if (g.balance) list.push({ key: 'balance', kind: 'range', title: 'Balance de carga (Garmin)', c: g.balance, body: estadoDeGarmin(g.balance.feedback), note: g.balance.note });
  // «+7» sin periodo no decía nada (revisión del 2026-10-21).
  if (g.fitness) list.push({ key: 'fitness', kind: 'line', title: 'Tu forma aeróbica (VO2máx)', c: g.fitness, vKey: 'value', label: `${fmt(g.fitness.current)} ${g.fitness.unit || ''} · ${g.fitness.change >= 0 ? '+' : ''}${fmt(g.fitness.change)}${g.fitness.first_date ? ` desde ${fechaConAnio(g.fitness.first_date)}` : ''}`, note: g.fitness.note });
  // Recuperación son DOS series con escalas muy distintas (ms contra ppm), y
  // charts.py las manda así: `{note, hrv:{points,...}, rhr:{points,...}}`, sin
  // `points` en la raíz. Esto leía `g.recovery.points`, encontraba `undefined`
  // y pintaba "Serie demasiado corta para dibujarla todavía" SIEMPRE, con 40
  // noches de datos detrás. Son dos tarjetas porque superponerlas en un eje
  // dejaría una de las dos plana para siempre.
  if (g.recovery?.hrv) list.push({ key: 'hrv', kind: 'line', title: 'Variabilidad cardiaca (HRV nocturna)', c: g.recovery.hrv, vKey: 'value', label: etiquetaRecuperacion(g.recovery.hrv), note: g.recovery.note, band: g.recovery.hrv.band, bandLabel: 'tu rango normal' });
  if (g.recovery?.rhr) list.push({ key: 'rhr', kind: 'line', title: 'Pulso en reposo', c: g.recovery.rhr, vKey: 'value', label: etiquetaRecuperacion(g.recovery.rhr), band: g.recovery.rhr.band, bandLabel: 'tu rango normal' });
  // Las cuatro que faltaban. Garmin ya bajaba estos datos en cada generacion y
  // los expertos ya los leian; lo unico que no habia era forma de que el
  // atleta los viera. Ver charts.py (sleep_trend, stress_trend, weight_trend,
  // readiness_trend).
  if (g.sleep?.hours) list.push({ key: 'sleep_h', kind: 'line', title: 'Cuánto duermes', c: g.sleep.hours, vKey: 'value', label: etiquetaSimple(g.sleep.hours, 'h'), note: g.sleep.note });
  if (g.sleep?.score) list.push({ key: 'sleep_s', kind: 'line', title: 'Calidad del sueño (Garmin)', c: g.sleep.score, vKey: 'value', label: etiquetaSimple(g.sleep.score, ''), note: g.sleep.hours ? null : g.sleep.note });
  if (g.stress) list.push({ key: 'stress', kind: 'line', title: 'Estrés diario', c: g.stress, vKey: 'value', label: etiquetaSimple(g.stress, ''), note: g.stress.note, band: [0, 25], bandLabel: 'reposo, según Garmin' });
  if (g.readiness) list.push({ key: 'readiness', kind: 'line', title: 'Disponibilidad para entrenar', c: g.readiness, vKey: 'value', label: etiquetaSimple(g.readiness, ''), note: g.readiness.note });
  if (g.weight) list.push({ key: 'weight', kind: 'line', title: 'Peso', c: g.weight, vKey: 'value', label: etiquetaPeso(g.weight), note: g.weight.note });
  if (g.volume) list.push({ key: 'volume', kind: 'bars', title: 'Por deporte', c: g.volume, vKey: 'hours', unit: 'h', sub: `Horas de cada deporte · ${num(g.volume.total_hours)} h en tus últimas ${g.volume.weeks} semanas` });
  if (g.discipline) list.push({ key: 'discipline', kind: 'bars', title: 'Cómo se reparte tu semana', c: g.discipline, vKey: 'minutes', unit: 'min', sub: `${num(g.discipline.total_hours)} h` });
  return list;
});
const visibles = computed(() => (store.isAdmin && vistaTecnica.value
  ? cards.value
  : cards.value.filter((c) => TARJETAS_DEL_ATLETA.includes(c.key))));

// Alto maximo de una barra, en pixeles. Ver el comentario de `kind === 'bars'`.
const ALTO_BARRA = 128;
// Con coma, como el resto de la app ("0,9" y no "0.9"): ver utils/garmin.js.
const fmt = (n) => num(n);
// El color de una barra suelta: por deporte en «Por deporte» (el mismo de Plan e Historial); en los días, ámbar si
// no llega al 70 % (sólo con 3 sesiones o más: con menos no se saca conclusión).
const DEPORTE_DE_LA_BARRA = { carrera: 'run', run: 'run', trail: 'trail', bici: 'bike', bike: 'bike', 'natación': 'swim', natacion: 'swim', swim: 'swim', fuerza: 'strength', strength: 'strength', caminata: 'walk', walk: 'walk' };
const colorDeBarra = (s, p) => {
  if (s.key === 'volume') return COLOR_DEL_DEPORTE[DEPORTE_DE_LA_BARRA[String(p.label || '').toLowerCase()]] || '#c5c9ac';
  if (s.unit === '%' && Number(p[s.vKey]) < 70 && !(p.prescribed != null && p.prescribed < 3)) return '#ffd166';
  return '#caf300';
};
/** Las barras de deporte traen la clave ("brick", "swim"): su nombre en pantalla. */
const nombreDeBarra = (l) => SPORT_ES[String(l).toLowerCase()] || DISCIPLINA_ES[String(l).toLowerCase()] || l;
/** El número que trae un campo, o null. Nunca convierte la ausencia en 0. */
const numero = (v) => (v === null || v === undefined || v === '' || !Number.isFinite(Number(v)) ? null : Number(v));
const clock = (s) => {
  if (s == null || !Number.isFinite(Number(s))) return '—';
  const t = Math.round(Number(s));
  return `${Math.floor(t / 60)}:${String(t % 60).padStart(2, '0')}`;
};
/** "Ahora 72 · media de los últimos 7: 77" — el dato suelto y la tendencia.
 *  El valor de un dia ruidoso (una noche, una pesada) no dice nada solo. */
const etiquetaSimple = (s, unidad) => {
  const bruta = s.unit === undefined ? unidad : s.unit;
  // "6,3 h" y "65 kg" con espacio, como en el resto de la app; "76/100" sin él.
  const u = bruta ? (/^[a-zA-Z]/.test(bruta) ? ` ${bruta}` : bruta) : '';
  const partes = [`Ahora: ${fmt(s.current)}${u}`];
  if (s.avg7 != null) partes.push(`media de los últimos 7: ${fmt(s.avg7)}${u}`);
  if (s.days) partes.push(`${s.days} días`);
  return partes.join(' · ');
};
const etiquetaPeso = (w) => {
  const partes = [etiquetaSimple(w, 'kg')];
  if (w.change != null) partes.push(`${w.change >= 0 ? '+' : ''}${fmt(w.change)} kg en la ventana`);
  return partes.join(' · ');
};
// Fechas como se leen, no en ISO; y "te faltan 1:24" es tiempo de carrera, no días (auditoría del 2026-09-24).
const fechaConAnio = (iso) => {
  const f = fechaCorta(iso);
  const anio = String(iso || '').slice(0, 4);
  return anio && anio !== String(new Date().getFullYear()) ? `${f} ${anio}` : f;
};
const labelRaceGoal = (r) => `${r.race_name} · ${fechaConAnio(r.race_date)} · tu reloj predice ${r.current_clock}, tu meta es ${r.goal_clock}`
  + (r.reached ? ' · ya estás dentro' : ` · te faltan ${r.gap_clock}`);
/**
 * Si se esta acercando, y cuanto.
 *
 * Es LA pregunta de esta grafica -- no "¿cuanto me falta?", que ya esta en el
 * titulo, sino "¿voy hacia alla?". Con una sola lectura no hay respuesta, y se
 * dice: un cero se leeria como estancamiento cuando es falta de datos.
 */
const progresoHaciaLaMeta = (r) => {
  if (r.progress_seconds == null) return 'Con más días de datos verás aquí cuánto has recortado.';
  return r.progress_seconds > 0
    ? `Has recortado ${r.progress_clock} desde la primera lectura.`
    : `Tu predicción ha subido ${r.progress_clock} desde la primera lectura.`;
};
const etiquetaRecuperacion = (s) => [
  s.current != null ? `Actual: ${fmt(s.current)} ${s.unit || ''}`.trim() : '',
  estadoDeGarmin(s.status),
].filter(Boolean).join(' · ');

// ---- inline chart card component ----
const ChartCard = {
  props: { spec: Object },
  setup(props) {
    return () => {
      const s = props.spec;
      const points = s.c.points || [];
      const body = [];

      if (s.kind === 'text') {
        body.push(h('p', { class: 'text-lg font-black text-[#caf300]' }, s.body || 'Sin datos de balance.'));
      } else if (s.kind === 'range') {
        // Una barra por sistema, contra el rango que Garmin le fija a ESTE
        // atleta a partir de su carga cronica. La escala es comun a los tres
        // (misma unidad de carga) o las barras no se podrian comparar entre
        // si, que es justo lo que la grafica sirve para ver.
        const tope = Math.max(...points.flatMap((p) => [
          Number(p.load) || 0, Number(p.target_max) || 0,
        ]), 1) * 1.08;
        const pct = (v) => `${Math.min(100, Math.max(0, (Number(v) || 0) * 100 / tope))}%`;
        const COLOR = { below: '#a5c6ff', within: '#caf300', above: '#ffb4ab' };
        if (s.body) {
          body.push(h('p', { class: 'text-sm font-black text-[#caf300] mb-3' }, s.body));
        }
        body.push(h('div', { class: 'flex flex-col gap-3.5' }, points.map((p) => {
          const color = COLOR[p.status] || '#caf300';
          const min = Number(p.target_min) || 0;
          const max = Number(p.target_max) || 0;
          return h('div', { class: 'flex flex-col gap-1' }, [
            h('div', { class: 'flex justify-between items-baseline gap-2' }, [
              h('span', { class: 'text-[10px] font-black uppercase text-[#c5c9ac]' }, p.label),
              h('span', { class: 'text-[10px] font-mono-data', style: `color:${color}` },
                `${p.load}` + (max ? ` · objetivo ${min}–${max}` : '')),
            ]),
            // La pista lleva la banda objetivo dibujada detras, y la barra de
            // carga encima: donde cae respecto de la banda ES la respuesta.
            h('div', { class: 'relative h-5 rounded-md bg-white/8 overflow-hidden' }, [
              max
                ? h('div', {
                    class: 'absolute inset-y-0 bg-white/15 border-x border-white/30',
                    style: `left:${pct(min)}; width:calc(${pct(max)} - ${pct(min)})`,
                  })
                : null,
              h('div', {
                class: 'absolute inset-y-0 left-0 rounded-r-md opacity-90',
                style: `width:${pct(p.load)}; background:${color}`,
              }),
            ]),
          ]);
        })));
        body.push(h('div', { class: 'flex flex-wrap gap-3 text-[10px] font-mono-data text-[#8f9378] mt-3' }, [
          h('span', {}, '▬ zona objetivo de Garmin'),
          h('span', { style: 'color:#a5c6ff' }, '▪ por debajo'),
          h('span', { style: 'color:#caf300' }, '▪ dentro'),
          h('span', { style: 'color:#ffb4ab' }, '▪ por encima'),
        ]));
      } else if (s.kind === 'bars') {
        // ALTURA EN PIXELES, no en porcentaje.
        //
        // El fallo: la barra llevaba `height:X%` y su columna padre no tiene
        // altura definida -- es un hijo flex con `items-end`, o sea que se
        // dimensiona por su contenido. Un porcentaje sin base contra la que
        // resolverse cae a `auto`, y un div vacio con altura auto mide CERO.
        // Se veian los numeros (tienen texto) y ninguna barra.
        const max = Math.max(...points.map((p) => Number(p[s.vKey]) || 0), 1);
        const alto = (v) => Math.max(4, Math.round((Number(v) || 0) / max * ALTO_BARRA));
        body.push(h('div', { class: 'flex items-end gap-2 pt-2' }, points.map((p) => h('div', { class: 'flex-1 flex flex-col items-center justify-end gap-1' }, [
          h('span', { class: 'text-[12px] font-bold text-white whitespace-nowrap' }, fmt(p[s.vKey]) + (s.unit === '%' ? ' %' : (s.unit ? ` ${s.unit}` : ''))),
          h('div', { class: 'w-full rounded-t', style: `height:${alto(p[s.vKey])}px;background:${colorDeBarra(s, p)};opacity:${s.unit === '%' && p.prescribed != null && p.prescribed < 3 ? 0.4 : 1}` }),
          h('span', { class: 'text-[11px] font-bold text-white/70 truncate w-full text-center' }, nombreDeBarra(p.label || p.full || '')),
          p.prescribed != null && p.done != null ? h('span', { class: 'text-[11px] text-white/60 whitespace-nowrap' }, `${p.done} de ${p.prescribed}`) : null,
        ]))));
      } else if (s.kind === 'pair') {
        // Mismo fallo que arriba: el `h-full` del contenedor interior tampoco
        // resolvia, porque tambien colgaba de una columna sin altura.
        const max = Math.max(...points.flatMap((p) => [Number(p[s.a]) || 0, Number(p[s.b]) || 0]), 1);
        const alto = (v) => Math.max(3, Math.round((Number(v) || 0) / max * ALTO_BARRA));
        body.push(h('div', { class: 'flex items-end gap-3 pt-2' }, points.map((p) => h('div', { class: 'flex-1 flex flex-col items-center gap-1' }, [
          // Su valor encima (hecho de planeado): sin números, «¿cuál fue mi mala semana?» no tenía respuesta.
          h('span', { class: 'text-[12px] font-bold text-white leading-none' }, `${fmt(p[s.b])}${s.unit ? ` ${s.unit}` : ''}`),
          h('span', { class: 'text-[11px] text-white/60 leading-none whitespace-nowrap' }, `de ${fmt(p[s.a])}`),
          h('div', { class: 'w-full flex items-end justify-center gap-1' }, [
            h('div', { class: 'w-1/2 bg-white/35 rounded-t', style: `height:${alto(p[s.a])}px` }),
            // Lima si llegó al 70 % de lo planeado; ámbar si no, como en «Qué días cumples».
            // Las horas, sin juicio (2026-10-22): en ámbar bajo el 70 % chocaban con la racha, que cuenta sesiones.
            h('div', { class: 'w-1/2 rounded-t', style: `height:${alto(p[s.b])}px;background:${p.dias && p.dias < 4 ? '#8f9378' : (!s.sinJuicio && (Number(p[s.b]) || 0) < 0.7 * (Number(p[s.a]) || 0) ? '#ffd166' : '#caf300')}` }),
          ]),
          h('span', { class: 'text-[11px] font-bold text-white/70 w-full text-center leading-tight' }, nombreDeBarra(p.label || '')),
          p.dias && p.dias < 4 ? h('span', { class: 'text-[11px] text-white/55 leading-tight' }, `${p.dias} ${p.dias === 1 ? 'día' : 'días'}`) : null,
        ]))));
        const muestra = (color, texto) => h('span', { class: 'inline-flex items-center gap-1.5' }, [
          h('i', { class: 'w-3 h-3 rounded-sm inline-block', style: `background:${color}` }), texto]);
        body.push(h('div', { class: 'flex flex-wrap gap-x-4 gap-y-1 text-[12px] text-white/75 mt-2' }, [
          muestra('rgba(255,255,255,0.35)', s.la), muestra('#caf300', s.lb), s.sinJuicio ? null : muestra('#ffd166', 'Menos del 70 %'),
          points.some((p) => p.dias && p.dias < 4) ? muestra('#8f9378', 'Semana de pocos días') : null,
        ]));
      } else if (s.kind === 'line') {
        const crudos = points.map((p) => Number(p[s.vKey])).filter((v) => Number.isFinite(v));
        // `numero()` y no `Number.isFinite(Number(x))`: `Number(null)` es CERO
        // y pasa el filtro, así que una meta ausente entraría en la escala
        // como un 0 y aplastaría la gráfica entera contra el borde superior.
        const meta = numero(s.goal);
        const banda = (s.band || []).map(numero).filter((v) => v !== null);
        if (crudos.length >= 2) {
          // La escala tiene que abarcar TAMBIÉN la meta y la banda, o se
          // dibujan fuera del lienzo: una meta de 26:00 bajo una predicción de
          // 27:16 caía por debajo del borde inferior y no se veía.
          // Una segunda serie opcional, dibujada mas tenue detras: el ACWR
          // que calcula esta app, junto al de Garmin. Entra en la escala o se
          // saldria del lienzo.
          const segundos = (s.serie2 || []).map((p) => Number(p[s.vKey])).filter((v) => Number.isFinite(v));
          const referencias = [
            ...crudos,
            ...segundos,
            ...(meta === null ? [] : [meta]),
            ...banda,
          ];
          let min = Math.min(...referencias);
          let max = Math.max(...referencias);
          // Una serie PLANA (dos lecturas iguales, que es lo normal al empezar)
          // daba rango 0, y con `|| 1` todos los puntos caían en y=H: la línea
          // se dibujaba justo sobre el borde inferior y la mitad del trazo
          // quedaba recortada. Se veía como una tarjeta vacía. Un margen del
          // 10% -- y medio punto de ancho si todo es igual -- la deja centrada.
          if (max - min < 1e-9) { const d = Math.abs(max) * 0.1 || 0.5; min -= d; max += d; }
          const rng = max - min;
          const W = 300;
          const H = 90;
          const PAD = 4;                       // sitio para el grosor del trazo
          const y = (v) => PAD + (H - 2 * PAD) * (1 - (Number(v) - min) / rng);
          const pts = points.map((p, i) =>
            `${((i / (points.length - 1)) * W).toFixed(1)},${y(p[s.vKey]).toFixed(1)}`).join(' ');

          const capas = [];
          // La banda propia del atleta (zona habitual de ACWR, rango normal de
          // HRV). Se pasaba en la especificación desde el principio y no se
          // dibujaba nunca, aunque la nota al pie hablara de ella.
          if (banda.length === 2) {
            const yA = y(Math.max(...banda));
            const yB = y(Math.min(...banda));
            capas.push(h('rect', { x: 0, y: yA, width: W, height: Math.max(1, yB - yA), fill: 'rgba(202,243,0,0.10)' }));
          }
          // La meta. El pie de la tarjeta dice "la raya es tu meta" desde
          // siempre; hasta ahora esa raya no existía.
          if (meta !== null) {
            capas.push(h('line', {
              x1: 0, x2: W, y1: y(meta), y2: y(meta),
              stroke: '#a5eeff', 'stroke-width': '1.5', 'stroke-dasharray': '6 4',
              'vector-effect': 'non-scaling-stroke',
            }));
          }
          if (segundos.length >= 2) {
            const pts2 = segundos.map((v, i) =>
              `${((i / (segundos.length - 1)) * W).toFixed(1)},${y(v).toFixed(1)}`).join(' ');
            capas.push(h('polyline', {
              points: pts2, fill: 'none', stroke: '#c5c9ac', 'stroke-width': '1.5',
              'stroke-dasharray': '3 3', opacity: '0.65',
              'stroke-linejoin': 'round', 'vector-effect': 'non-scaling-stroke',
            }));
          }
          capas.push(h('polyline', { points: pts, fill: 'none', stroke: '#caf300', 'stroke-width': '2', 'stroke-linejoin': 'round', 'vector-effect': 'non-scaling-stroke' }));

          body.push(h('svg', { viewBox: `0 0 ${W} ${H}`, class: 'w-full h-24 mt-2', preserveAspectRatio: 'none' }, capas));

          const eje = (v) => (s.fmtY ? s.fmtY(v) : fmt(v));
          // Las fechas en el eje y el rango aparte: «44 – 52» entre «sep 2025» y «oct 2026» se leía como fechas.
          body.push(h('div', { class: 'flex justify-between text-[12px] text-white/65 mt-1' }, [
            h('span', {}, String(points[0].label || points[0].date || '')),
            h('span', {}, String(points[points.length - 1].label || points[points.length - 1].date || '')),
          ]));
          body.push(h('p', { class: 'text-[12px] text-white/65 mt-1' },
            `Lo más bajo: ${eje(Math.min(...crudos))} · lo más alto: ${eje(Math.max(...crudos))}`));
          const leyenda = [
            meta !== null ? h('span', { class: 'text-[#a5eeff]' }, `– – meta ${s.goalLabel || eje(meta)}`) : null,
            banda.length === 2 && s.bandLabel ? h('span', { class: 'text-[#caf300]/70' }, `▮ ${s.bandLabel}`) : null,
            segundos.length >= 2 && s.serie2Label ? h('span', { class: 'text-[#c5c9ac]/70' }, `– – ${s.serie2Label}`) : null,
          ].filter(Boolean);
          if (leyenda.length) body.push(h('div', { class: 'flex flex-wrap gap-x-4 text-[12px] mt-1' }, leyenda));
        } else {
          body.push(h('p', { class: 'text-sm text-[#8f9378] py-6' },
            'Sólo hay una lectura todavía: hacen falta dos para dibujar una tendencia.'));
        }
      }

      return h('article', { class: 'glass-card rounded-3xl p-5 sm:p-6 border border-white/10 shadow-2xl' }, [
        h('h3', { class: 'text-sm font-black text-white uppercase tracking-wider' }, s.title),
        (s.sub || s.label) ? h('p', { class: 'text-[13px] text-white/75 mt-1' }, s.sub || s.label) : null,
        s.sub2 ? h('p', { class: 'text-xs font-bold text-[#caf300] mt-1 mb-2' }, s.sub2) : h('div', { class: 'mb-2' }),
        ...body,
        s.note ? h('p', { class: 'text-[12px] text-white/70 leading-relaxed mt-3 pt-3 border-t border-white/10' }, s.note) : null,
        // Un consejo con su botón (revisión del 2026-10-21): «cámbialo en tus días» no llevaba a ningún sitio.
        s.enlace ? h(RouterLink, { to: s.enlace.to, class: 'mt-2 inline-flex items-center gap-1 min-h-[40px] px-3 rounded-xl border border-[#caf300]/50 text-[13px] font-bold text-[#caf300]' },
          () => [h('span', { class: 'material-symbols-outlined text-[16px]' }, 'edit_calendar'), s.enlace.texto]) : null,
      ]);
    };
  },
};
</script>
