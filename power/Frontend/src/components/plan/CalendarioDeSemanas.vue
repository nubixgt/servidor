<template>
  <div class="space-y-3">
    <!-- Qué semana se ve y cómo moverse entre ellas. El deslizamiento lateral manda; las flechas
         dicen a dónde van (en pantalla ancha) y son para quien no desliza. -->
    <div class="flex items-center gap-2">
      <button type="button" @click="irA(indice - 1)" :disabled="indice === 0"
              :aria-label="indice > 0 ? `Semana anterior: ${rangoDe(semanas[indice - 1])}` : 'No hay semana anterior'"
              class="h-11 sm:h-9 min-w-[44px] sm:min-w-[36px] px-2 shrink-0 rounded-xl glass-pill flex items-center justify-center gap-1 text-[#c5c9ac] hover:text-[#caf300] disabled:opacity-25 disabled:pointer-events-none">
        <span class="material-symbols-outlined text-[20px]">chevron_left</span>
        <span v-if="indice > 0" class="hidden lg:inline text-[11px] font-mono-data">{{ rangoDe(semanas[indice - 1]) }}</span>
      </button>
      <div class="flex-1 min-w-0 text-center" aria-live="polite">
        <p class="text-sm font-black text-white leading-tight">
          <span class="px-2 py-0.5 rounded-md text-[10px] uppercase tracking-wider align-middle mr-1"
                :class="etiquetaDe(indice).clase">{{ etiquetaDe(indice).texto }}</span>
          <span class="font-mono-data text-[12px] text-white/85 align-middle">{{ rangoDe(semanas[indice]) }}</span>
        </p>
        <p class="text-xs text-white/70 mt-0.5 tabular-nums">
          {{ sesionesDe(semanas[indice]) }} sesiones · {{ horasDe(semanas[indice]) }}
        </p>
      </div>
      <button type="button" @click="irA(indice + 1)" :disabled="indice >= semanas.length - 1"
              :aria-label="indice < semanas.length - 1 ? `Semana siguiente: ${rangoDe(semanas[indice + 1])}` : 'No hay semana siguiente'"
              class="h-11 sm:h-9 min-w-[44px] sm:min-w-[36px] px-2 shrink-0 rounded-xl glass-pill flex items-center justify-center gap-1 text-[#c5c9ac] hover:text-[#caf300] disabled:opacity-25 disabled:pointer-events-none"
              :class="mover && destinosEn(indice + 1) ? 'ring-2 ring-[#caf300] text-[#caf300] animate-pulse' : ''">
        <span v-if="indice < semanas.length - 1" class="hidden lg:inline text-[11px] font-mono-data">{{ rangoDe(semanas[indice + 1]) }}</span>
        <span class="material-symbols-outlined text-[20px]">chevron_right</span>
      </button>
    </div>

    <!-- Las semanas, una al lado de la otra como un calendario. La altura es la de la que se ve:
         una semana de 9 días tiene dos filas y no puede estirar a las demás. -->
    <div ref="pista" class="flex items-start overflow-x-auto overflow-y-hidden snap-x snap-mandatory hide-scrollbar overscroll-x-contain transition-[height] duration-200"
         :style="altura ? { height: `${altura}px` } : undefined"
         @scroll.passive="alDesplazar">
      <section v-for="(w, wi) in semanas" :key="w.clave" :ref="(el) => { secciones[wi] = el; }"
               class="w-full shrink-0 snap-center px-0.5 pt-0.5 pb-1"
               :aria-label="`${etiquetaDe(wi).texto} ${rangoDe(w)}`" :aria-hidden="wi !== indice ? 'true' : undefined">
        <div class="grid grid-cols-7 gap-1 sm:gap-2 mb-1.5">
          <span v-for="n in INICIALES" :key="n" class="text-center text-xs font-black uppercase tracking-wider text-white/60">{{ n }}</span>
        </div>
        <div class="grid grid-cols-7 gap-1 sm:gap-2">
          <template v-for="c in w.celdas" :key="c.fecha">
            <div v-if="!c.enLaSemana" class="rounded-xl border border-dashed border-white/5 min-h-[84px] sm:min-h-[112px] flex items-start justify-center pt-1.5">
              <span class="text-[10px] font-mono-data text-white/25">{{ c.dia }}</span>
            </div>
            <button
              v-else
              type="button"
              :data-fecha="c.fecha"
              :tabindex="wi === indice ? 0 : -1"
              :title="c.sesiones.map((s) => s.titulo).join(' + ') || 'Descanso'"
              :draggable="!!movible(c.fecha) && !mover"
              @dragstart="alArrastrar($event, c.fecha)"
              @dragend="sobre = ''"
              @dragover="alPasarPorEncima($event, c.fecha)"
              @dragleave="sobre === c.fecha && (sobre = '')"
              @drop.prevent="alSoltar(c.fecha)"
              @click="alTocar(c.fecha)"
              :aria-pressed="!mover ? String(c.fecha === diaElegido) : undefined"
              :aria-disabled="mover && !esDestino(c.fecha) && c.fecha !== mover.fecha ? 'true' : undefined"
              :aria-label="etiquetaAccesible(c)"
              class="relative rounded-xl border text-left p-1.5 sm:p-2 min-h-[84px] sm:min-h-[112px] flex flex-col transition-all duration-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#caf300]"
              :class="[claseDeCelda(c), resaltado === c.fecha ? 'ring-2 ring-[#caf300] scale-105' : '']"
            >
              <!-- Arriba: el día (hoy en lima), y las marcas de movida y sin enviar. -->
              <span class="flex items-center justify-between gap-0.5 h-5">
                <span class="font-mono-data text-[12px] sm:text-sm font-black leading-none"
                      :class="c.fecha === hoy ? 'text-[#121700] bg-[#caf300] rounded-md px-1 py-0.5' : 'text-white'">{{ c.dia }}</span>
                <span class="flex items-center gap-0.5">
                  <!-- ✓/✗: lo que dice su reloj de lo que hizo (utils/hecho.js). -->
                  <!-- En pantalla ancha, junto al número; en el teléfono no cabe y va en el pie. -->
                  <span v-if="!mover && marca(c)" :title="MARCAS[marca(c)].texto"
                        class="hidden sm:flex w-5 h-5 rounded-full items-center justify-center shrink-0" :class="MARCAS[marca(c)].clase">
                    <span class="material-symbols-outlined text-[14px] font-black">{{ MARCAS[marca(c)].icono }}</span>
                  </span>
                  <!-- El icono dentro de su caja: la clase del icono pone su propio `display` y le ganaba a `hidden`. -->
                  <span v-if="c.movida" class="hidden sm:inline-flex" aria-hidden="true"><span class="material-symbols-outlined text-[13px] text-[#a5eeff]">edit_calendar</span></span>
                  <span v-if="pendientes.includes(c.fecha)" class="w-1.5 h-1.5 rounded-full bg-[#a5eeff]" aria-hidden="true"></span>
                </span>
              </span>

              <!-- En medio: las sesiones. -->
              <span class="flex-1 flex flex-col gap-0.5 py-1 min-w-0">
                <template v-if="c.sesiones.length">
                  <span v-for="(s, si) in c.sesiones.slice(0, 2)" :key="si" class="flex items-start gap-1 min-w-0">
                    <span class="material-symbols-outlined shrink-0 icono-sesion" :class="s.sport === 'race' ? 'text-[22px]' : 'text-[16px] sm:text-[18px]'"
                          :style="{ color: COLOR_DEL_DEPORTE[s.sport] || '#c5c9ac' }">{{ ICONO_DEL_DEPORTE[s.sport] || 'exercise' }}</span>
                    <span class="hidden sm:block min-w-0"><span class="text-[11px] text-white/90 leading-tight line-clamp-2 break-words">{{ s.titulo }}</span></span>
                  </span>
                </template>
                <span v-else class="flex items-center gap-1 text-white/50">
                  <span class="material-symbols-outlined text-[16px] icono-sesion">bedtime</span>
                  <span class="hidden sm:inline text-[10px] font-bold">Descanso</span>
                </span>
              </span>

              <!-- Abajo, un pie fijo: los minutos o, en modo mover, lo que pasa aquí. Nunca tapa nada. -->
              <span class="h-5 flex items-center">
                <span v-if="mover && c.fecha === mover.fecha"
                      class="w-full h-5 flex items-center justify-center gap-0.5 rounded-md bg-[#a5eeff] text-[#121700] text-[9px] font-black uppercase tracking-wider">
                  <span class="material-symbols-outlined text-[12px]">open_with</span><span class="hidden sm:inline">Mover</span>
                </span>
                <span v-else-if="mover && esDestino(c.fecha)"
                      class="w-full h-5 flex items-center justify-center gap-0.5 rounded-md bg-[#caf300] text-[#121700] text-[9px] font-black uppercase tracking-wider">
                  <span class="material-symbols-outlined text-[12px]">{{ c.sesiones.length ? 'swap_horiz' : 'add' }}</span>
                  <span class="hidden sm:inline">{{ c.sesiones.length ? 'Cambiar' : 'Aquí' }}</span>
                </span>
                <template v-else>
                  <span v-if="marca(c)" class="sm:hidden w-5 h-5 rounded-full flex items-center justify-center" :class="MARCAS[marca(c)].clase">
                    <span class="material-symbols-outlined text-[14px] font-black">{{ MARCAS[marca(c)].icono }}</span>
                  </span>
                  <span v-if="c.carrera" class="hidden sm:inline text-[9px] font-black uppercase tracking-wider text-[#ffd166]">Carrera</span>
                  <span v-else class="font-mono-data font-black text-white/75 tabular-nums text-[11px]" :class="marca(c) ? 'hidden sm:inline' : ''">{{ minutos(c) }}</span>
                </template>
              </span>
            </button>
          </template>
        </div>
      </section>
    </div>

    <!-- Dónde estás entre las semanas; cada punto es un blanco de 24 px. -->
    <div v-if="semanas.length > 1" class="flex justify-center" role="tablist" aria-label="Semanas">
      <button v-for="(w, wi) in semanas" :key="w.clave" type="button" role="tab" :aria-selected="wi === indice"
              :aria-label="`${etiquetaDe(wi).texto} ${rangoDe(w)}`" @click="irA(wi)" class="p-2 group">
        <span class="block h-1.5 rounded-full transition-colors"
              :class="wi === indice ? 'w-6 bg-[#caf300]' : (w.activa ? 'w-1.5 bg-white/50 ring-1 ring-[#caf300]/60 group-hover:bg-white/80' : 'w-1.5 bg-white/20 group-hover:bg-white/50')"></span>
      </button>
    </div>
  </div>
</template>

<script setup>
/**
 * Las semanas del plan como un calendario que se desliza a los lados (decisión del responsable,
 * 2026-10-21: «idealmente una corrediza hacia los lados, donde las semanas se apilen como un
 * calendario»). Cada semana es una fila de lunes a domingo; tocar un día lo abre debajo.
 *
 * Para mover una sesión: el modo mover (`mover`) marca los días a los que puede ir (`destinos`), en
 * esta semana o en la otra activa; en pantalla ancha también se arrastra. Las reglas son del cerebro
 * (`edicion_de_sesion.mover`): aquí sólo se enseñan. `prepararVuelo` hace que la sesión se vea ir de
 * un día a otro (revisión de UX del 2026-10-21: «el ojo no sigue a dónde fue»).
 */
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { COLOR_DEL_DEPORTE, ICONO_DEL_DEPORTE, deporteVisible, etiquetaDeLaSesion } from '../../utils/deportes';
import { MARCAS, marcaDelDia } from '../../utils/hecho';

const props = defineProps({
  weeks: { type: Array, required: true },
  hoy: { type: String, required: true },
  diaElegido: { type: String, default: '' },
  // {fecha, clave, titulo} mientras se elige el día nuevo de una sesión.
  mover: { type: Object, default: null },
  destinos: { type: Array, default: () => [] },
  // Fechas con cambios que el reloj todavía no tiene.
  pendientes: { type: Array, default: () => [] },
  // (fecha) => clave de la sesión que se puede arrastrar ese día, o null.
  movible: { type: Function, default: () => null },
  titulo: { type: Function, default: (d) => d.focus || '' },
  // ✓/✗ por sesión ("fecha|franja" -> {estado}), de su Garmin y sin IA (2026-10-21).
  estados: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['elegir', 'destino', 'arrastrar', 'semana']);

const INICIALES = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];

const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
const lunesDe = (fecha) => {
  const d = new Date(`${fecha}T12:00:00`);
  d.setDate(d.getDate() - ((d.getDay() + 6) % 7));
  return d;
};

const semanas = computed(() => [...props.weeks]
  .map((w) => ({ w, fechas: (w.days || []).map((d) => d.date).filter(Boolean).sort() }))
  .filter((x) => x.fechas.length)
  .sort((a, b) => a.fechas[0].localeCompare(b.fechas[0]))
  .map(({ w, fechas }) => {
    const enLaSemana = new Set(fechas);
    const lunes = lunesDe(fechas[0]);
    // Lunes a domingo; una semana que empieza un miércoles (la primera) deja huecos al principio.
    const ultimas = Math.ceil((((new Date(`${fechas[fechas.length - 1]}T12:00:00`) - lunes) / 86400000) + 1) / 7) * 7;
    const celdas = Array.from({ length: Math.max(7, ultimas) }, (_, i) => {
      const d = new Date(lunes);
      d.setDate(lunes.getDate() + i);
      const fecha = iso(d);
      const delDia = (w.days || []).filter((x) => x.date === fecha);
      const sesiones = delDia.filter((x) => !x.is_rest).map((x) => ({
        sport: deporteVisible(x),
        titulo: props.titulo(x) || etiquetaDeLaSesion(x),
        minutos: Number(x.total_duration_minutes) || 0,
      }));
      const claves = delDia.filter((x) => !x.is_rest).map((x) => `${x.date}|${x.session_slot || 'unica'}`);
      return { fecha, dia: d.getDate(), enLaSemana: enLaSemana.has(fecha), sesiones, claves,
        carrera: sesiones.some((s) => s.sport === 'race'),
        movida: delDia.some((x) => x.edited?.moved_from) };
    });
    return { w, clave: `${w.week_number}-${fechas[0]}`, fechas, celdas, activa: fechas[fechas.length - 1] >= props.hoy };
  }));

const pista = ref(null);
const indice = ref(0);
// Desde el primer render, no después del primer desplazamiento: los puntos marcaban la primera semana
// mientras se veía la de hoy (revisión de UX, ronda 2).
const secciones = [];
const altura = ref(0);
let observador = null;
const medir = () => { altura.value = secciones[indice.value]?.offsetHeight || 0; };
watch(indice, () => nextTick(medir));

const indiceDeHoy = () => {
  const s = semanas.value;
  const deHoy = s.findIndex((x) => x.fechas[0] <= props.hoy && props.hoy <= x.fechas[x.fechas.length - 1]);
  if (deHoy >= 0) return deHoy;
  const futura = s.findIndex((x) => x.fechas[0] > props.hoy);
  return futura >= 0 ? futura : Math.max(0, s.length - 1);
};

indice.value = indiceDeHoy();

const irA = (i, suave = true) => {
  const n = Math.max(0, Math.min(semanas.value.length - 1, i));
  indice.value = n;
  emit('semana', semanas.value[n]?.w || null);
  const el = pista.value;
  if (el) el.scrollTo({ left: n * el.clientWidth, behavior: suave ? 'smooth' : 'auto' });
};
const indiceDe = (fecha) => semanas.value.findIndex((s) => s.fechas.includes(fecha));

// El índice sale de dónde se PARÓ el deslizamiento, no de cada píxel (los puntos se desincronizaban).
let temporizador = null;
const alDesplazar = () => {
  clearTimeout(temporizador);
  temporizador = setTimeout(() => {
    const el = pista.value;
    if (!el || !el.clientWidth) return;
    const n = Math.round(el.scrollLeft / el.clientWidth);
    if (n !== indice.value && semanas.value[n]) {
      indice.value = n;
      emit('semana', semanas.value[n].w);
    }
  }, 120);
};

const observar = () => {
  if (typeof ResizeObserver === 'undefined') return;
  observador?.disconnect();
  observador = observador || new ResizeObserver(() => medir());
  secciones.forEach((el) => el && observador.observe(el));
};
// La fuente del índice es la semana que de verdad se ve (≥60 % dentro): un `scrollTo` instantáneo no
// siempre lanza `scroll`, y entonces los puntos se quedaban en la semana anterior.
let visibles = null;
const vigilarVisibles = () => {
  if (typeof IntersectionObserver === 'undefined' || !pista.value) return;
  visibles?.disconnect();
  visibles = new IntersectionObserver((entradas) => {
    entradas.forEach((e) => {
      if (!e.isIntersecting) return;
      const n = secciones.indexOf(e.target);
      if (n >= 0 && n !== indice.value) {
        indice.value = n;
        emit('semana', semanas.value[n].w);
      }
    });
  }, { root: pista.value, threshold: 0.6 });
  secciones.forEach((el) => el && visibles.observe(el));
};
onUnmounted(() => visibles?.disconnect());
onMounted(async () => {
  await nextTick();
  irA(indiceDeHoy(), false);
  vigilarVisibles();
  medir();
  // La altura sigue a la semana que se ve cuando cambia su contenido o el ancho (girar el teléfono).
  observar();
});
onUnmounted(() => observador?.disconnect());
// Un plan nuevo (generó, borró una semana): vuelve a la de hoy.
watch(() => semanas.value.map((s) => s.clave).join(','), async () => {
  await nextTick();
  irA(indiceDeHoy(), false);
  observar();
  vigilarVisibles();
  medir();
});

const etiquetaDe = (i) => {
  const s = semanas.value[i];
  if (!s) return { texto: '', clase: '' };
  const [ini, fin] = [s.fechas[0], s.fechas[s.fechas.length - 1]];
  if (ini <= props.hoy && props.hoy <= fin) return { texto: 'Esta semana', clase: 'bg-[#caf300] text-[#121700]' };
  if (ini > props.hoy) {
    const primera = semanas.value.findIndex((x) => x.fechas[0] > props.hoy);
    return { texto: primera === i ? 'La siguiente' : 'Más adelante', clase: 'bg-[#a5eeff] text-[#121700]' };
  }
  return { texto: 'Terminada', clase: 'bg-white/10 text-[#c5c9ac]' };
};
const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sept', 'oct', 'nov', 'dic'];
const corta = (f) => { const [, m, d] = f.split('-'); return `${Number(d)} ${MESES[Number(m) - 1]}`; };
const rangoDe = (s) => (s ? `${corta(s.fechas[0])} – ${corta(s.fechas[s.fechas.length - 1])}` : '');
const sesionesDe = (s) => (s ? s.celdas.reduce((n, c) => n + c.sesiones.length, 0) : 0);
const horasDe = (s) => {
  const min = s ? s.celdas.reduce((n, c) => n + c.sesiones.reduce((m, x) => m + x.minutos, 0), 0) : 0;
  const h = Math.floor(min / 60);
  const m = Math.round(min % 60);
  return h ? `${h} h${m ? ` ${m} min` : ''}` : `${m} min`;
};
const minutos = (c) => {
  const m = Math.round(c.sesiones.reduce((n, s) => n + s.minutos, 0));
  if (!m) return '';
  return m >= 60 ? `${Math.floor(m / 60)} h${m % 60 ? ` ${m % 60}'` : ''}` : `${m}'`;
};

const marca = (c) => marcaDelDia(props.estados, c.claves);
const esDestino = (fecha) => props.destinos.includes(fecha);
const destinosEn = (i) => (semanas.value[i]?.fechas || []).some(esDestino);
const sobre = ref('');

const claseDeCelda = (c) => {
  if (props.mover) {
    if (c.fecha === props.mover.fecha) return 'border-[#a5eeff] bg-[#a5eeff]/10';
    if (esDestino(c.fecha)) {
      return sobre.value === c.fecha
        ? 'border-[#caf300] bg-[#caf300]/20 scale-[1.03]'
        : 'border-dashed border-[#caf300]/70 bg-[#caf300]/5 hover:bg-[#caf300]/15 cursor-pointer';
    }
    return 'border-white/5 bg-white/[0.02] opacity-30 cursor-not-allowed';
  }
  // Dos estados, dos marcas: el elegido lleva anillo; hoy, la pastilla lima en el número.
  const elegido = c.fecha === props.diaElegido;
  const base = c.carrera
    ? `bg-[#ffd166]/15 ${elegido ? 'border-[#ffd166] ring-2 ring-[#caf300]' : 'border-[#ffd166]/60 hover:border-[#ffd166]'}`
    : (elegido ? 'border-[#caf300] ring-2 ring-[#caf300] bg-[#caf300]/10'
      : 'border-white/10 bg-white/[0.05] hover:border-white/30 hover:bg-white/[0.08]');
  // Un día pasado se atenúa poco: una sesión de ayer todavía se puede mover.
  return `${base} ${c.fecha < props.hoy ? 'opacity-60' : ''}`;
};

const etiquetaAccesible = (c) => {
  const d = new Date(`${c.fecha}T12:00:00`).toLocaleDateString('es', { weekday: 'long', day: 'numeric', month: 'long' });
  const hoy = c.fecha === props.hoy ? ', hoy' : '';
  const que = c.sesiones.length
    ? c.sesiones.map((s) => `${s.titulo}${s.minutos ? `, ${Math.round(s.minutos)} minutos` : ''}`).join(' y ')
    : 'descanso';
  let extra = '';
  if (props.mover) {
    if (c.fecha === props.mover.fecha) extra = '. Es la sesión que mueves';
    else if (esDestino(c.fecha)) extra = c.sesiones.length ? `. Intercambiar con ${c.sesiones[0].titulo}` : '. Mover aquí';
    else extra = '. No disponible';
  }
  const m = marca(c);
  return `${d}${hoy}: ${que}${m ? `. ${MARCAS[m].texto}` : ''}${extra}`;
};

// Una vibración corta al elegir el día (Android). Sin un toque real el navegador la bloquea y lo dice en la consola.
const vibrar = () => { if (navigator.userActivation?.isActive) navigator.vibrate?.(12); };

const alTocar = (fecha) => {
  if (props.mover) {
    if (esDestino(fecha)) {
      vibrar();
      emit('destino', fecha);
    }
    return;
  }
  emit('elegir', fecha);
};

// Arrastrar (pantalla ancha). En el teléfono el mismo gesto es tocar «Mover» y después el día.
const alArrastrar = (e, fecha) => {
  const clave = props.movible(fecha);
  if (!clave) { e.preventDefault(); return; }
  e.dataTransfer.effectAllowed = 'move';
  e.dataTransfer.setData('text/plain', clave);
  emit('arrastrar', { clave, fecha });
};
const alPasarPorEncima = (e, fecha) => {
  if (props.mover && esDestino(fecha)) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    sobre.value = fecha;
  }
};
const alSoltar = (fecha) => {
  sobre.value = '';
  if (props.mover && esDestino(fecha)) {
    vibrar();
    emit('destino', fecha);
  }
};

// ---- Que se vea ir de un día a otro -------------------------------------------------------------
const resaltado = ref('');
const sinMovimiento = () => typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
const iconoEn = (fecha) => pista.value?.querySelector(`[data-fecha="${fecha}"] .icono-sesion`);
const pulso = (fecha) => {
  resaltado.value = fecha;
  setTimeout(() => { if (resaltado.value === fecha) resaltado.value = ''; }, 600);
};

/**
 * Antes de cambiar el plan: guarda dónde está el icono de cada día que se mueve. Devuelve la función que,
 * con el plan ya cambiado, lo hace volar a su día nuevo (`pares`: [[desde, hasta], ...]) y hace latir el
 * destino. Si el destino es de otra semana, se pasa a ella y sólo late (volar por encima de un
 * deslizamiento no se sigue con el ojo).
 */
const prepararVuelo = (pares) => {
  const salidas = pares.map(([desde, hasta]) => {
    const el = iconoEn(desde);
    return el ? { desde, hasta, rect: el.getBoundingClientRect(), html: el.outerHTML } : { desde, hasta };
  });
  return async () => {
    const [primero] = pares;
    const otraSemana = indiceDe(primero[1]) !== indice.value && indiceDe(primero[1]) >= 0;
    if (otraSemana) irA(indiceDe(primero[1]));
    await nextTick();
    if (!otraSemana && !sinMovimiento()) {
      salidas.forEach(({ hasta, rect, html }) => {
        const destino = iconoEn(hasta);
        if (!rect || !destino) return;
        const fin = destino.getBoundingClientRect();
        const caja = document.createElement('div');
        caja.innerHTML = html;
        const volador = caja.firstElementChild;
        Object.assign(volador.style, {
          position: 'fixed', left: `${rect.left}px`, top: `${rect.top}px`, margin: '0', zIndex: '60',
          pointerEvents: 'none', transition: 'transform 350ms cubic-bezier(.2,.8,.2,1)',
          filter: 'drop-shadow(0 0 8px rgba(202,243,0,.6))',
        });
        document.body.appendChild(volador);
        destino.style.opacity = '0';
        requestAnimationFrame(() => {
          volador.style.transform = `translate(${fin.left - rect.left}px, ${fin.top - rect.top}px) scale(1.25)`;
        });
        setTimeout(() => { volador.remove(); destino.style.opacity = ''; }, 380);
      });
    }
    setTimeout(() => pulso(primero[1]), otraSemana ? 350 : 300);
  };
};

defineExpose({ irA, prepararVuelo });
</script>
