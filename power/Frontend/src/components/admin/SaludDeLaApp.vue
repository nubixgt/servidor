<template>
  <!-- LA SALUD DE LA APP (Beta 3.0, F1). Lo que antes sólo se veía por SSH: el
       servicio, la cola, cada trabajo con su log, los errores de proveedores y de
       PHP, y cuántas veces corrige algo cada red. Carga y se refresca sola, aparte
       del resto del panel: si MySQL falla, esto se sigue viendo, y al revés. -->
  <section class="glass-panel border border-white/15 rounded-3xl p-5 sm:p-6 space-y-5">
    <div class="flex items-start justify-between gap-3 flex-wrap">
      <div>
        <h3 class="flex items-center gap-2 text-sm font-black text-white uppercase tracking-wider">
          <span class="material-symbols-outlined text-[20px]" :class="hayProblemas ? 'text-[#ffb4ab]' : 'text-[#caf300]'">monitor_heart</span>
          <span>Salud de la app</span>
        </h3>
        <p class="text-[11px] text-[#8f9378] mt-1">
          Se actualiza cada 30 s mientras la pestaña está abierta<span v-if="leidoA"> · leído a las {{ leidoA }}</span>.
        </p>
      </div>
      <button type="button" @click="cargar" :disabled="cargando"
              class="flex items-center gap-1.5 px-3 py-1.5 glass-pill rounded-xl text-[11px] font-black uppercase tracking-wider text-[#c5c9ac] hover:text-white disabled:opacity-50">
        <span class="material-symbols-outlined text-[16px]" :class="cargando ? 'animate-spin' : ''">refresh</span>
        Actualizar
      </button>
    </div>

    <!-- El servicio -->
    <div v-if="servicio && !servicio.available" class="rounded-2xl border border-[#ffb4ab]/40 bg-[#ffb4ab]/5 px-4 py-3">
      <p class="text-sm font-black text-[#ffb4ab]">El AiService no responde</p>
      <p class="text-[11px] text-[#c5c9ac] mt-0.5">{{ servicio.error }}. Las generaciones, el chat y las subidas a Garmin no funcionan mientras tanto.</p>
    </div>
    <div v-else-if="servicio" class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
      <div v-for="c in tarjetas" :key="c.label" class="rounded-2xl border border-white/10 bg-white/[0.02] px-3.5 py-3">
        <p class="text-[10px] uppercase font-black tracking-wider text-[#8f9378]">{{ c.label }}</p>
        <p class="text-xl font-black font-mono-data mt-0.5" :class="c.alerta ? 'text-[#ffb4ab]' : 'text-white'">{{ c.valor }}</p>
        <p class="text-[10px] text-[#8f9378] mt-0.5">{{ c.pie }}</p>
      </div>
    </div>

    <!-- La alerta de gasto (Beta 3.0, F2): más de 1 USD de un atleta en un día. -->
    <div v-if="gasto?.alerts?.length" class="rounded-2xl border border-[#ffd166]/40 bg-[#ffd166]/5 px-4 py-3 space-y-1.5">
      <p class="text-sm font-black text-[#ffd166]">
        Gasto alto: más de {{ num(gasto.threshold_usd, 2) }} USD en un día (últimos {{ gasto.days }} días)
      </p>
      <p v-for="a in gasto.alerts" :key="`${a.user_id}-${a.dia}`" class="text-[11px] text-[#c5c9ac]">
        <span class="font-mono-data text-white">{{ cuando(`${a.dia}T12:00:00`).split(',')[0] }}</span>
        · {{ a.name }} · <span class="font-mono-data text-[#ffd166]">${{ num(a.cost_usd, 2) }}</span>
        · {{ usos(a.usos) }}
      </p>
    </div>

    <!-- Lo que hay que mirar -->
    <ul v-if="alertas.length" class="space-y-1.5">
      <li v-for="a in alertas" :key="a" class="flex items-start gap-2 text-xs text-[#ffd166]">
        <span class="material-symbols-outlined text-[16px] leading-4">warning</span><span>{{ a }}</span>
      </li>
    </ul>

    <!-- Los trabajos -->
    <div>
      <div class="flex items-baseline justify-between gap-3 flex-wrap mb-2">
        <h4 class="text-xs font-black uppercase tracking-wider text-white">Trabajos recientes</h4>
        <p v-if="trabajos?.conservados" class="text-[10px] text-[#8f9378]">El servidor conserva {{ trabajos.conservados }}.</p>
      </div>
      <p v-if="trabajos && !trabajos.available" class="text-xs text-[#8f9378]">No se pudieron leer: {{ trabajos.error }}</p>
      <p v-else-if="!listaDeTrabajos.length" class="text-xs text-[#8f9378]">Todavía no hay trabajos guardados.</p>
      <div v-else class="overflow-x-auto rounded-2xl border border-white/10">
        <table class="w-full text-xs min-w-[640px]">
          <thead>
            <tr class="text-left text-[10px] uppercase tracking-wider text-[#8f9378] border-b border-white/10">
              <th class="px-3 py-2 font-black">Cuándo</th>
              <th class="px-3 py-2 font-black">Qué</th>
              <th class="px-3 py-2 font-black">Atleta</th>
              <th class="px-3 py-2 font-black">Estado</th>
              <th class="px-3 py-2 font-black text-right">Duración</th>
              <th class="px-3 py-2 font-black text-right">Costo</th>
              <th class="px-3 py-2 font-black"></th>
            </tr>
          </thead>
          <tbody>
            <template v-for="t in trabajosVisibles" :key="t.job_id">
              <tr class="border-b border-white/5 align-top">
                <td class="px-3 py-2 font-mono-data text-[#c5c9ac] whitespace-nowrap">{{ cuando(t.inicio) }}</td>
                <td class="px-3 py-2 text-white">{{ TIPOS[t.kind] || t.kind }}</td>
                <td class="px-3 py-2 text-[#c5c9ac]">{{ nombreDe(t.user_id) }}</td>
                <td class="px-3 py-2">
                  <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider" :class="CLASE_ESTADO[t.estado] || 'bg-white/10 text-white'">
                    {{ ESTADOS[t.estado] || t.estado }}
                  </span>
                  <p v-if="t.proveedor" class="text-[10px] text-[#ffb4ab] mt-1">{{ PROVEEDOR[t.proveedor] || t.proveedor }}</p>
                  <p v-if="t.error" class="text-[10px] text-[#c5c9ac] mt-1 max-w-[280px] break-words">{{ t.error }}</p>
                </td>
                <td class="px-3 py-2 text-right font-mono-data text-[#c5c9ac]">{{ duracion(t.duracion_s) }}</td>
                <td class="px-3 py-2 text-right font-mono-data text-[#c5c9ac]">{{ t.costo_usd != null ? `$${num(t.costo_usd, 3)}` : '—' }}</td>
                <td class="px-3 py-2 text-right">
                  <button v-if="t.tiene_log" type="button" @click="alternarLog(t.job_id)"
                          class="px-2 py-1 glass-pill rounded-lg text-[10px] font-black uppercase tracking-wider text-[#a5eeff]">
                    {{ logAbierto === t.job_id ? 'Cerrar' : 'Log' }}
                  </button>
                </td>
              </tr>
              <tr v-if="logAbierto === t.job_id">
                <td colspan="7" class="px-3 pb-3">
                  <p v-if="cargandoLog" class="text-[11px] text-[#8f9378]">Leyendo el log…</p>
                  <p v-else-if="log && !log.available" class="text-[11px] text-[#ffb4ab]">{{ log.error }}</p>
                  <template v-else-if="log">
                    <p v-if="log.aviso" class="text-[10px] text-[#ffd166] mb-1">{{ log.aviso }}</p>
                    <pre class="max-h-80 overflow-auto rounded-xl bg-black/40 border border-white/10 p-3 text-[10px] leading-relaxed text-[#c5c9ac] whitespace-pre-wrap break-words">{{ (log.lineas || []).join('\n') }}</pre>
                  </template>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
      <button v-if="listaDeTrabajos.length > visibles" type="button" @click="visibles += 20"
              class="mt-2 text-[11px] font-black uppercase tracking-wider text-[#a5eeff]">
        Ver más ({{ listaDeTrabajos.length - visibles }})
      </button>
    </div>

    <!-- Las redes deterministas -->
    <div>
      <h4 class="text-xs font-black uppercase tracking-wider text-white mb-1">Redes que corrigen al modelo</h4>
      <p class="text-[11px] text-[#8f9378] mb-2">
        Cada vez que una regla del código corrige lo que escribió la IA. Una red que no corrige nada en
        muchas generaciones se puede retirar (Fase C).
        <template v-if="redes?.available">
          <span v-if="redes.midiendo_desde"> Midiendo desde el {{ cuando(redes.midiendo_desde) }}: {{ redes.generaciones }} {{ redes.generaciones === 1 ? 'generación pagada' : 'generaciones pagadas' }} en los últimos {{ redes.days }} días.</span>
          <span v-else> Todavía no se ha medido ninguna generación: el libro empieza con la primera.</span>
        </template>
      </p>
      <p v-if="redes && !redes.available" class="text-xs text-[#8f9378]">No se pudieron leer: {{ redes.error }}</p>
      <div v-else-if="redes" class="overflow-x-auto rounded-2xl border border-white/10">
        <table class="w-full text-xs">
          <tbody>
            <tr v-for="r in redes.redes || []" :key="r.red" class="border-b border-white/5" :class="r.correcciones ? '' : 'opacity-60'">
              <td class="px-3 py-2 text-white">{{ r.que_hace || r.red }}</td>
              <td class="px-3 py-2 text-right font-mono-data whitespace-nowrap" :class="r.correcciones ? 'text-[#ffd166]' : 'text-[#8f9378]'">
                {{ r.correcciones }} {{ r.correcciones === 1 ? 'vez' : 'veces' }}
              </td>
              <td class="px-3 py-2 text-[#8f9378] whitespace-nowrap">
                <span v-if="r.correcciones">{{ r.atletas }} {{ r.atletas === 1 ? 'atleta' : 'atletas' }} · {{ cuando(r.ultima) }}</span>
                <span v-else-if="r.sin_corregir_nada">0 de {{ r.generaciones }} generaciones</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Errores de la pantalla de los atletas (Beta 3.0, F3): uno por minuto y atleta. -->
    <details class="rounded-2xl border border-white/10 px-4 py-3" :open="(erroresDePantalla || []).length > 0">
      <summary class="text-xs font-black uppercase tracking-wider text-white cursor-pointer">
        Errores del navegador (7 días){{ (erroresDePantalla || []).length ? ` · ${erroresDePantalla.length}` : '' }}
      </summary>
      <div class="mt-2">
        <p v-if="!(erroresDePantalla || []).length" class="text-[11px] text-[#8f9378]">Ninguno.</p>
        <ul v-else class="space-y-1 max-h-60 overflow-auto">
          <li v-for="(e, i) in erroresDePantalla" :key="i" class="text-[11px] text-[#c5c9ac] break-words">
            <span class="font-mono-data text-white">{{ cuando(String(e.created_at).replace(' ', 'T')) }}</span>
            · {{ e.user_name || '—' }}{{ e.role === 'admin' ? ' (admin)' : '' }} · {{ e.detail }}
          </li>
        </ul>
      </div>
    </details>

    <!-- Errores de PHP -->
    <details class="rounded-2xl border border-white/10 px-4 py-3">
      <summary class="text-xs font-black uppercase tracking-wider text-white cursor-pointer">Errores del servidor PHP</summary>
      <div class="mt-2">
        <p v-if="php && !php.available" class="text-[11px] text-[#8f9378]">{{ php.error }}</p>
        <template v-else-if="php">
          <p v-if="php.aviso" class="text-[10px] text-[#ffd166] mb-1">{{ php.aviso }}</p>
          <p v-if="!(php.lineas || []).length" class="text-[11px] text-[#8f9378]">Sin errores.</p>
          <pre v-else class="max-h-72 overflow-auto rounded-xl bg-black/40 border border-white/10 p-3 text-[10px] leading-relaxed text-[#c5c9ac] whitespace-pre-wrap break-words">{{ php.lineas.join('\n') }}</pre>
        </template>
      </div>
    </details>
  </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { getAppHealth, getClientErrors, getJobLog, getJobs, getNets, getSpendingAlerts } from '../../services/metrics';
import { num } from '../../utils/garmin';

const props = defineProps({
  /** {user_id: nombre}, de la tabla de atletas del panel. */
  atletas: { type: Object, default: () => ({}) },
});

const TIPOS = { week: 'Semana', season: 'Macrociclo', 'garmin-upload': 'Subida a Garmin', generacion: 'Generación' };
const ESTADOS = {
  en_cola: 'En cola', en_curso: 'En curso', esperando_respuestas: 'Esperando respuestas',
  terminado: 'Terminado', error: 'Error', colgado: 'Colgado',
};
const CLASE_ESTADO = {
  en_cola: 'bg-[#a5eeff]/15 text-[#a5eeff]', en_curso: 'bg-[#a5eeff]/15 text-[#a5eeff]',
  esperando_respuestas: 'bg-[#ffd166]/15 text-[#ffd166]', terminado: 'bg-[#caf300]/15 text-[#caf300]',
  error: 'bg-[#ffb4ab]/15 text-[#ffb4ab]', colgado: 'bg-[#ffb4ab]/15 text-[#ffb4ab]',
};
const PROVEEDOR = {
  sin_creditos: 'Proveedor sin créditos', limite_429: 'Límite de peticiones (429)', credencial: 'Clave rechazada',
  sobrecargado: 'Proveedor saturado', tiempo_agotado: 'Tiempo agotado',
};

const salud = ref(null);
const gasto = ref(null);
const erroresDePantalla = ref([]);
const USOS = { week: 'semana', season: 'macrociclo', chat: 'mensaje al coach', strength_routine: 'lectura de rutina' };
const usos = (u) => Object.entries(u || {}).map(([k, n]) => `${n} × ${USOS[k] || k}`).join(', ');
const trabajos = ref(null);
const redes = ref(null);
const cargando = ref(false);
const leidoA = ref('');
const visibles = ref(20);
const logAbierto = ref('');
const log = ref(null);
const cargandoLog = ref(false);

const servicio = computed(() => salud.value?.aiservice || null);
const php = computed(() => salud.value?.php || null);
const listaDeTrabajos = computed(() => (trabajos.value?.available ? trabajos.value.jobs || [] : []));
const trabajosVisibles = computed(() => listaDeTrabajos.value.slice(0, visibles.value));

const nombreDe = (id) => (id == null ? '—' : props.atletas[String(id)] || `#${id}`);

const tarjetas = computed(() => {
  const s = servicio.value;
  if (!s?.available) return [];
  return [
    { label: 'Generando', valor: `${s.generaciones_en_curso}/${s.max_simultaneas}`, pie: 'a la vez, como máximo' },
    { label: 'En cola', valor: s.generaciones_en_cola, pie: 'esperando un hueco', alerta: s.generaciones_en_cola > 0 },
    { label: 'Subidas a Garmin', valor: s.subidas_en_curso, pie: 'en curso' },
    { label: 'Disco del servicio', valor: `${num(s.runtime_mb, 1)} MB`, pie: s.desplegado_en ? `código del ${cuando(s.desplegado_en)}` : '_runtime/' },
  ];
});

const hace24h = () => Date.now() - 24 * 3600 * 1000;
const alertas = computed(() => {
  const salida = [];
  const recientes = listaDeTrabajos.value.filter((t) => t.inicio && new Date(t.inicio).getTime() >= hace24h());
  const fallidos = recientes.filter((t) => t.estado === 'error').length;
  const colgados = listaDeTrabajos.value.filter((t) => t.estado === 'colgado').length;
  const proveedores = [...new Set(recientes.map((t) => t.proveedor).filter(Boolean))];
  if (fallidos) salida.push(`${fallidos} ${fallidos === 1 ? 'trabajo falló' : 'trabajos fallaron'} en las últimas 24 h.`);
  if (colgados) salida.push(`${colgados} ${colgados === 1 ? 'trabajo parece colgado' : 'trabajos parecen colgados'} (sin señales de vida en 25 min).`);
  proveedores.forEach((p) => salida.push(`${PROVEEDOR[p] || p} en las últimas 24 h.`));
  return salida;
});
const hayProblemas = computed(() => (servicio.value && !servicio.value.available) || alertas.value.length > 0
  || (gasto.value?.alerts?.length || 0) > 0);

function cuando(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '—';
  return d.toLocaleString('es-ES', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
}

function duracion(s) {
  if (s == null) return '—';
  const m = Math.floor(s / 60);
  return m >= 60 ? `${Math.floor(m / 60)} h ${m % 60} min` : `${m}:${String(s % 60).padStart(2, '0')}`;
}

const sinRespuesta = (e) => ({ available: false, error: e?.response?.data?.message || e?.message || 'sin respuesta' });

async function cargar() {
  cargando.value = true;
  try {
    const [s, t, r, g, ec] = await Promise.all([
      getAppHealth().catch((e) => ({ aiservice: sinRespuesta(e), php: sinRespuesta(e) })),
      getJobs(60).catch(sinRespuesta),
      getNets(30).catch(sinRespuesta),
      getSpendingAlerts().catch(sinRespuesta),
      getClientErrors().catch(() => []),
    ]);
    salud.value = s;
    gasto.value = g;
    erroresDePantalla.value = Array.isArray(ec) ? ec : [];
    trabajos.value = t;
    redes.value = r;
    leidoA.value = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  } finally {
    cargando.value = false;
  }
}

async function alternarLog(jobId) {
  if (logAbierto.value === jobId) {
    logAbierto.value = '';
    return;
  }
  logAbierto.value = jobId;
  log.value = null;
  cargandoLog.value = true;
  try {
    log.value = await getJobLog(jobId, 200);
  } catch (e) {
    log.value = sinRespuesta(e);
  } finally {
    cargandoLog.value = false;
  }
}

// Cada 30 s, y sólo con la pestaña a la vista: nadie mira un panel escondido.
let reloj = null;
onMounted(() => {
  cargar();
  reloj = setInterval(() => {
    if (document.visibilityState === 'visible' && !cargando.value) cargar();
  }, 30000);
});
onUnmounted(() => clearInterval(reloj));
</script>
