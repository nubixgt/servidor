<template>
  <div id="historial-view-container" class="space-y-7 pb-20 select-none animate-fade-in-up">
    <header class="pb-5 border-b border-white/10">
      <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">Historial</h2>
      <!-- Una frase y un enlace (revisión del 2026-10-21): «archivados», «activo» y «Eliminar plan» contaban cómo
           está montado, a quien sólo quería mirar. -->
      <p class="text-sm text-white/80 mt-1 font-medium">
        Tus semanas y temporadas de antes. La de ahora está en
        <router-link to="/admin/plan" class="text-[#caf300] font-bold underline underline-offset-2">Plan</router-link>.
      </p>
    </header>

    <div v-if="loading" class="p-8 text-center glass-card rounded-3xl text-[#c5c9ac] text-sm border border-white/10">
      Cargando…
    </div>

    <div
      v-else-if="!entries.length"
      class="p-8 text-center glass-card rounded-3xl text-[#c5c9ac] text-sm border border-white/10 leading-relaxed"
    >
      Todavía no hay planes anteriores. Cuando llegue tu próxima semana, la de ahora aparecerá aquí.
    </div>

    <template v-else>
    <section v-for="g in grupos" :key="g.id" class="space-y-3">
      <h3 class="text-[12px] font-black text-white/70 uppercase tracking-widest">{{ g.nombre }}</h3>
      <div
        v-for="e in g.items"
        :key="e.id"
        class="glass-card rounded-3xl border border-white/10 shadow-2xl overflow-hidden"
      >
        <!-- Toda la fila abre (revisión del 2026-10-21) y dice QUÉ semanas cubría, no a qué hora se escribió.
             Borrar va dentro, con su texto, lejos de «abrir». -->
        <button type="button" @click="toggle(e.id)" :aria-expanded="String(openId === e.id)"
                class="w-full p-4 sm:p-5 flex items-center gap-3 text-left min-h-[64px]">
          <span class="w-10 h-10 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center text-[#caf300] shrink-0">
            <span class="material-symbols-outlined text-[22px]">{{ e.kind === 'week' ? 'calendar_view_week' : 'timeline' }}</span>
          </span>
          <span class="flex-1 min-w-0">
            <span class="block text-[15px] font-black text-white">{{ tituloDe(e) }}</span>
            <span class="block text-[12px] text-white/70 mt-0.5">{{ detalleDe(e) }}</span>
            <!-- Lo que hiciste, del expediente de esa semana (sin IA): un historial es también lo hecho. -->
            <!-- Una semana que no ha terminado no tiene veredicto: «hasta hoy», sin color de bien o mal. -->
            <span v-if="e.resultado?.en_curso" class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[12px] font-bold text-white/75">
              <span class="material-symbols-outlined text-[15px]">schedule</span>
              En curso · hasta hoy {{ e.resultado.hechas }} {{ e.resultado.hechas === 1 ? 'sesión hecha' : 'sesiones hechas' }}<template v-if="e.resultado.horas_plan"> · {{ num(e.resultado.horas) }} h de {{ num(e.resultado.horas_plan) }} h</template>
            </span>
            <span v-else-if="e.resultado" class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[12px] font-bold"
                  :class="e.resultado.evaluadas && e.resultado.hechas / e.resultado.evaluadas >= 0.7 ? 'text-[#caf300]' : 'text-[#ffd166]'">
              <span class="material-symbols-outlined text-[15px]">{{ e.resultado.evaluadas && e.resultado.hechas / e.resultado.evaluadas >= 0.7 ? 'task_alt' : 'error_outline' }}</span>
              {{ e.resultado.hechas }} de {{ e.resultado.evaluadas }} sesiones hechas<template v-if="e.resultado.clave"> · {{ e.resultado.clave_hechas }} de {{ e.resultado.clave }} clave</template><template v-if="e.resultado.horas_plan"> · {{ num(e.resultado.horas) }} h de {{ num(e.resultado.horas_plan) }} h</template>
            </span>
            <span v-if="store.isAdmin && e.total_cost_usd != null" class="block text-[12px] text-[#a5eeff] font-bold">${{ Number(e.total_cost_usd).toFixed(2) }}</span>
          </span>
          <span class="material-symbols-outlined text-white/60 shrink-0 transition-transform" :class="openId === e.id ? 'rotate-180' : ''">expand_more</span>
        </button>

        <!-- Expanded detail -->
        <div v-if="openId === e.id" class="px-5 sm:px-6 pb-6 border-t border-white/10 pt-5 space-y-5">
          <div v-if="detailLoading" class="text-sm text-[#c5c9ac]">Cargando plan…</div>
          <template v-else-if="detail">
            <!-- La semana que se pulsó, primero y sola; las de antes de esa versión, plegadas (revisión del
                 2026-10-21: abría «Semana 1» de septiembre y la pulsada salía tres pantallas más abajo). El porqué
                 lo escribió el coach para esa semana y va plegado: es largo y ya pasó. -->
            <template v-for="(week, wi) in semanasEnOrden(detail)" :key="week.week_number">
              <details v-if="wi === 1" class="group rounded-xl border border-white/10 px-3" @toggle="antesAbiertas = $event.target.open">
                <summary class="cursor-pointer list-none flex items-center gap-1.5 min-h-[44px] text-[13px] font-bold text-white/85 hover:text-[#caf300]">
                  <span class="material-symbols-outlined text-[18px] transition-transform group-open:rotate-90">chevron_right</span>
                  {{ semanasEnOrden(detail).length - 1 === 1 ? 'La semana anterior, como estaba entonces' : `Las ${semanasEnOrden(detail).length - 1} semanas anteriores, como estaban entonces` }}
                </summary>
              </details>
              <div v-if="wi === 0 || antesAbiertas" class="space-y-2">
                <p class="text-[12px] font-black text-[#caf300] uppercase tracking-widest">{{ rangoDe(week) }}</p>
                <ul class="divide-y divide-white/10 rounded-xl border border-white/10 overflow-hidden">
                  <li v-for="(day, di) in (week.days || [])" :key="di" class="flex items-center gap-3 px-3 py-2.5">
                    <span class="material-symbols-outlined text-[20px] shrink-0" :style="{ color: COLOR_DEL_DEPORTE[deporteVisible(day)] || '#c5c9ac' }">{{ ICONO_DEL_DEPORTE[deporteVisible(day)] || 'event' }}</span>
                    <span class="w-[68px] shrink-0 text-[13px] font-bold text-white">{{ diaCorto(day.date) }}</span>
                    <span class="flex-1 min-w-0 text-[13px] text-white/85 leading-snug">{{ day.is_rest ? 'Descanso' : (day.focus || sportEs(day.sport)) }}</span>
                    <span v-if="!day.is_rest && day.total_duration_minutes" class="shrink-0 text-[13px] text-white/70 tabular-nums">{{ duracion(day.total_duration_minutes) }}</span>
                    <span v-if="marcaDe(day)" class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center" :class="MARCAS[marcaDe(day)].clase"
                          :title="MARCAS[marcaDe(day)].texto" :aria-label="MARCAS[marcaDe(day)].texto">
                      <span class="material-symbols-outlined text-[16px] font-black">{{ MARCAS[marcaDe(day)].icono }}</span>
                    </span>
                  </li>
                </ul>
                <!-- El resumen de la tarjeta se contó al cerrar la semana; las marcas, con lo que tu Garmin tiene hoy:
                     una actividad que el reloj subió tarde las separa (revisión del 2026-10-22). -->
                <p v-if="wi === 0 && hayMarcas(week)" class="text-[12px] text-white/60">✓ hecha · ✗ no está en tu reloj, según tu Garmin de hoy.<template v-if="e.resultado && !e.resultado.en_curso"> El resumen de arriba se contó al cerrar la semana: si tu reloj subió algo después, pueden no coincidir.</template></p>
                <details v-if="week.week_rationale" class="group rounded-xl border border-white/10 px-3">
                  <summary class="cursor-pointer list-none flex items-center gap-1.5 min-h-[44px] text-[13px] font-bold text-white/85 hover:text-[#caf300]">
                    <span class="material-symbols-outlined text-[18px] transition-transform group-open:rotate-90">chevron_right</span>
                    Por qué esta semana es así
                  </summary>
                  <div class="text-[13px] text-white/80 leading-relaxed pb-2 coach-md" v-html="renderMarkdown(week.week_rationale)"></div>
                </details>
              </div>
            </template>

            <!-- El texto de la temporada, plegado: abierto arriba eran dos pantallas de prosa antes de las sesiones. -->
            <details v-if="detail.season_plan_md" class="group rounded-xl border border-white/10 px-3">
              <summary class="cursor-pointer list-none flex items-center gap-1.5 min-h-[44px] text-[13px] font-bold text-white/85 hover:text-[#caf300]">
                <span class="material-symbols-outlined text-[18px] transition-transform group-open:rotate-90">chevron_right</span>
                {{ e.kind === 'week' ? 'Tu plan de temporada de entonces' : 'Tu plan de temporada' }}
              </summary>
              <div class="text-sm text-[#e5e2e1] leading-relaxed coach-md pb-3" v-html="renderMarkdown(detail.season_plan_md)"></div>
            </details>
            <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-white/10">
              <button type="button" @click="remove(e.id)"
                      class="flex items-center gap-1.5 px-2 min-h-[44px] text-[12px] font-bold text-[#ffb4ab]/90 hover:text-[#ffb4ab]">
                <span class="material-symbols-outlined text-[16px]">delete</span>Quitar del historial
              </button>
              <button type="button" @click="toggle(e.id)"
                      class="px-4 min-h-[44px] rounded-xl border border-white/20 text-[13px] font-bold text-white/85 hover:text-white">Cerrar</button>
            </div>
          </template>
        </div>
      </div>
    </section>
    </template>
  </div>
</template>

<script setup>
import { SPORT_ES, ICONO_DEL_DEPORTE, COLOR_DEL_DEPORTE, deporteVisible } from '../../utils/deportes';
import { MARCAS } from '../../utils/hecho';
import { ref, computed, onMounted } from 'vue';
import swal from '../../utils/swal';
import { useAppStore } from '../../stores/appStore';
import { renderMarkdown } from '../../utils/markdown';
import { num } from '../../utils/garmin';
import { getPlanHistory, getPlanHistoryEntry, deletePlanHistoryEntry } from '../../services/plan';

const store = useAppStore();

const entries = ref([]);
const loading = ref(true);
const openId = ref(null);
const detail = ref(null);
const detailLoading = ref(false);
const antesAbiertas = ref(false);

// La semana que AÑADIÓ esa versión (la última) primero, y las de antes de la más reciente a la más vieja.
const semanasEnOrden = (d) => [...(d?.weekly_plan?.weeks || [])].reverse();
const DIAS = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
// «sáb 26 sept»: el día y el mes como se dicen, nunca «09-26» (se leía como mes-día imposible).
const diaCorto = (iso) => {
  if (!/^\d{4}-\d{2}-\d{2}/.test(iso || '')) return iso || '';
  return `${DIAS[new Date(`${iso.slice(0, 10)}T12:00:00`).getDay()]} ${diaMes(iso.slice(0, 10))}`;
};
const rangoDe = (week) => {
  const fechas = (week.days || []).map((d) => d.date).filter(Boolean).sort();
  if (!fechas.length) return `Semana ${week.week_number}`;
  const [a, b] = [fechas[0], fechas[fechas.length - 1]];
  return a === b ? diaMes(a) : `Del ${diaMes(a, a.slice(0, 7) !== b.slice(0, 7))} al ${diaMes(b)}`;
};
// «1 h 45 min» / «30 min»: «(67m)» se leía como metros.
const duracion = (min) => {
  const m = Math.round(Number(min) || 0);
  if (m < 60) return `${m} min`;
  return m % 60 ? `${Math.floor(m / 60)} h ${m % 60} min` : `${m / 60} h`;
};

const sportEs = (s) => SPORT_ES[s] || s || '';

// «Semana del 12 al 18 oct» / «Temporada»: lo que el atleta reconoce, no la hora a la que se escribió.
const diaMes = (iso, conMes = true) => {
  const d = new Date(`${iso}T12:00:00`);
  return d.toLocaleDateString('es', conMes ? { day: 'numeric', month: 'short' } : { day: 'numeric' });
};
// Cada versión guarda también las semanas de antes: el título es la que AÑADIÓ (la última), y las de antes se
// dicen aparte. Tres tarjetas «Semanas del 26 sept al…» parecían duplicados (revisión del 2026-10-21).
const tituloDe = (e) => {
  if (e.kind !== 'week') return 'Plan de temporada';
  const desde = e.ultima_desde || e.desde;
  const hasta = e.ultima_hasta || e.hasta;
  if (!desde) return 'Semana';
  const mismoMes = desde.slice(0, 7) === (hasta || '').slice(0, 7);
  return `Semana del ${hasta && hasta !== desde ? `${diaMes(desde, !mismoMes)} al ${diaMes(hasta)}` : diaMes(desde)}`;
};
const detalleDe = (e) => {
  // Dos «Plan de temporada» sólo se distinguían por la fecha: se dice también cuándo dejó de valer.
  if (e.kind !== 'week') {
    return [e.generated_at ? `Creado el ${fmtDia(e.generated_at)}` : '', e.archived_at ? `cambiado el ${fmtDia(e.archived_at)}` : '']
      .filter(Boolean).join(' · ');
  }
  return e.generated_at ? `Creada el ${fmtDia(e.generated_at)}` : '';
};
const fmtDia = (s) => {
  try { return new Date(s.replace(' ', 'T')).toLocaleDateString('es', { day: 'numeric', month: 'short' }); } catch (x) { return s; }
};

const fmt = (s) => {
  if (!s) return '';
  try { return new Date(s.replace(' ', 'T')).toLocaleString('es', { dateStyle: 'medium', timeStyle: 'short' }); }
  catch (e) { return s; }
};

// Semanas y temporadas aparte: mezcladas en una lista no se sabía qué era cada fila.
const grupos = computed(() => [
  { id: 'semanas', nombre: 'Semanas', items: entries.value.filter((e) => e.kind === 'week') },
  { id: 'temporadas', nombre: 'Planes de temporada', items: entries.value.filter((e) => e.kind !== 'week') },
].filter((g) => g.items.length));

// La marca de una sesión vieja: la de su fecha y franja, si la sesión del plan de ahora en ese hueco es del mismo
// deporte (las marcas se calculan sobre el plan de ahora; otra versión de esa semana podía tener otra cosa).
const sesionDelPlanDeAhora = (day) => {
  for (const w of store.plan?.weekly_plan?.weeks || []) {
    for (const d of w.days || []) {
      if (d.date === day.date && (d.session_slot || 'unica') === (day.session_slot || 'unica')) return d;
    }
  }
  return null;
};
const marcaDe = (day) => {
  if (day.is_rest) return '';
  const e = store.estadoSesiones?.sesiones?.[`${day.date}|${day.session_slot || 'unica'}`]?.estado;
  const ahora = sesionDelPlanDeAhora(day);
  if (!e || !MARCAS[e] || !ahora || ahora.sport !== day.sport) return '';
  return e;
};
const hayMarcas = (week) => (week.days || []).some((d) => marcaDe(d));

const load = async () => {
  loading.value = true;
  try { entries.value = await getPlanHistory(); } catch (e) { entries.value = []; }
  loading.value = false;
};

const toggle = async (id) => {
  if (openId.value === id) { openId.value = null; detail.value = null; return; }
  openId.value = id;
  antesAbiertas.value = false;
  detail.value = null;
  detailLoading.value = true;
  try { detail.value = await getPlanHistoryEntry(id); } catch (e) { detail.value = null; }
  detailLoading.value = false;
};

const remove = async (id) => {
  const r = await swal.fire({
    icon: 'warning',
    title: '¿Eliminar este plan del historial?',
    text: 'Se borra para siempre. Tu plan de ahora no se toca.',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
  });
  if (!r.isConfirmed) return;
  await deletePlanHistoryEntry(id);
  if (openId.value === id) { openId.value = null; detail.value = null; }
  await load();
  store.showToast('Plan eliminado del historial.');
};

onMounted(() => {
  load();
  // Las marcas: el plan de ahora y lo que dice su Garmin (se guarda 15 minutos; no bloquea la pantalla).
  if (!store.plan) store.loadPlan();
  if (!store.estadoSesiones) store.cargarEstadoSesiones();
});
</script>
