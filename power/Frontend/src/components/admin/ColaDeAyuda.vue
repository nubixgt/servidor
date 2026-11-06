<template>
  <!-- Las consultas de «Pedir ayuda» (Beta 3.0, F3): cada una con lo que escribió el atleta,
       el origen probable que propone el código y el diagnóstico entero para mirarlo. Se
       responde desde aquí y la respuesta le llega como aviso a él solo. -->
  <section class="glass-panel border border-white/15 rounded-3xl p-5 sm:p-6 space-y-4">
    <div class="flex items-start justify-between gap-3 flex-wrap">
      <div>
        <h3 class="flex items-center gap-2 text-sm font-black text-white uppercase tracking-wider">
          <span class="material-symbols-outlined text-[20px] text-[#a5eeff]">support_agent</span>
          <span>Consultas de ayuda</span>
          <span v-if="pendientes" class="px-2 py-0.5 rounded-md bg-[#a5eeff]/15 text-[#a5eeff] text-[10px]">{{ pendientes }} sin resolver</span>
        </h3>
        <p class="text-[11px] text-[#8f9378] mt-1">El origen es una propuesta del código, no un veredicto: mira las señales.</p>
      </div>
      <div class="flex gap-2 flex-wrap">
        <select v-model="filtroEstado" @change="cargar" aria-label="Estado"
                class="bg-[#1b1d22] border border-white/15 rounded-xl px-2.5 py-1.5 text-[11px] text-white">
          <option value="">Todos los estados</option>
          <option v-for="(e, k) in ESTADOS" :key="k" :value="k">{{ e }}</option>
        </select>
        <select v-model="filtroCategoria" @change="cargar" aria-label="Categoría"
                class="bg-[#1b1d22] border border-white/15 rounded-xl px-2.5 py-1.5 text-[11px] text-white">
          <option value="">Todas las categorías</option>
          <option v-for="c in CATEGORIAS_DE_AYUDA" :key="c.valor" :value="c.valor">{{ c.etiqueta }}</option>
        </select>
      </div>
    </div>

    <p v-if="cola && !cola.available" class="text-xs text-[#8f9378]">
      Falta correr <code class="font-mono-data">Database/029_pedir_ayuda.sql</code>: mientras tanto las consultas llegan como mensajes de «Opinar».
    </p>
    <p v-else-if="cargando && !items.length" class="text-xs text-[#8f9378]">Cargando…</p>
    <p v-else-if="!items.length" class="text-xs text-[#8f9378]">No hay consultas con este filtro.</p>

    <article v-for="c in items" :key="c.id" class="rounded-2xl border border-white/10 bg-white/[0.02] p-4 space-y-3">
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div class="min-w-0">
          <p class="text-sm font-black text-white">{{ c.user_name }}
            <span class="font-normal text-[#8f9378] text-xs">· {{ etiquetaDe(c.category) }} · {{ fecha(c.created_at) }}</span>
          </p>
          <p v-if="c.session_key" class="text-[11px] text-[#a5eeff] mt-0.5">Sesión {{ c.session_key.replace('|', ' · ') }}</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider" :class="CLASE_ORIGEN[c.triage] || 'bg-white/10 text-white'"
                :title="'Origen probable'">{{ ORIGENES[c.triage] || c.triage || 'sin triaje' }}</span>
          <select :value="c.status" @change="cambiarEstado(c, $event.target.value)" aria-label="Estado de la consulta"
                  class="bg-[#1b1d22] border border-white/15 rounded-lg px-2 py-1 text-[11px] text-white">
            <option v-for="(e, k) in ESTADOS" :key="k" :value="k">{{ e }}</option>
          </select>
        </div>
      </div>
      <p class="text-sm text-[#f1f0ee] whitespace-pre-line break-words">{{ c.message }}</p>

      <ul v-if="senales(c).length" class="space-y-1">
        <li v-for="(s, i) in senales(c)" :key="i" class="text-[11px] text-[#ffd166] flex gap-1.5">
          <span class="material-symbols-outlined text-[14px]">flag</span><span>{{ s }}</span>
        </li>
      </ul>
      <div v-if="sesion(c)" class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px]">
        <div class="rounded-xl border border-white/10 p-2.5">
          <p class="font-black text-[#8f9378] uppercase text-[10px]">Decidió la IA</p>
          <p v-if="sesion(c).decidido" class="text-white mt-1">{{ sesion(c).decidido.sport }} · {{ sesion(c).decidido.intensity_class }} · {{ sesion(c).decidido.total_duration_minutes }} min</p>
          <p v-else class="text-[#8f9378] mt-1">Sin traza para esa sesión.</p>
        </div>
        <div class="rounded-xl border border-white/10 p-2.5">
          <p class="font-black text-[#8f9378] uppercase text-[10px]">Se codificó</p>
          <p v-if="sesion(c).codificado?.ilegible" class="text-[#ffb4ab] mt-1">{{ sesion(c).codificado.ilegible }}</p>
          <p v-else-if="sesion(c).codificado" class="text-white mt-1">{{ sesion(c).codificado.sport }} · {{ sesion(c).codificado.intensity_class }} ·
            {{ sesion(c).codificado.minutos_de_los_pasos != null ? `${Math.round(sesion(c).codificado.minutos_de_los_pasos)} min en pasos` : 'pasos por distancia o vuelta' }}</p>
          <p v-else class="text-[#8f9378] mt-1">No está en su plan.</p>
        </div>
        <div class="rounded-xl border border-white/10 p-2.5">
          <p class="font-black text-[#8f9378] uppercase text-[10px]">En su reloj</p>
          <p class="mt-1" :class="sesion(c).reloj?.estado === 'desactualizado' ? 'text-[#ffb4ab]' : 'text-white'">{{ RELOJ[sesion(c).reloj?.estado] || '—' }}</p>
        </div>
      </div>

      <p v-if="!c.diagnostic" class="text-[11px] text-[#8f9378]">El diagnóstico se borró: se guarda 90 días (condiciones de uso, punto 9).</p>
      <details v-else class="rounded-xl border border-white/10 px-3 py-2">
        <summary class="text-[11px] font-black uppercase tracking-wider text-[#c5c9ac] cursor-pointer">Diagnóstico completo</summary>
        <pre class="mt-2 max-h-80 overflow-auto text-[10px] leading-relaxed text-[#c5c9ac] whitespace-pre-wrap break-words">{{ JSON.stringify(c.diagnostic, null, 2) }}</pre>
      </details>

      <div v-if="c.reply" class="rounded-xl bg-[#a5eeff]/5 border border-[#a5eeff]/20 px-3 py-2">
        <p class="text-[10px] uppercase font-black tracking-wider text-[#a5eeff]">Respondido · {{ c.replied_by_name || '' }} · {{ fecha(c.replied_at) }}</p>
        <p class="text-sm text-[#f1f0ee] whitespace-pre-line break-words mt-1">{{ c.reply }}</p>
      </div>
      <form class="flex flex-col sm:flex-row gap-2" @submit.prevent="responder(c)">
        <textarea v-model="borradores[c.id]" rows="2" maxlength="4000" :placeholder="c.reply ? 'Responder otra vez…' : 'Tu respuesta (le llega como aviso)…'"
                  class="flex-1 rounded-xl px-3 py-2 text-sm bg-[#1b1d22] text-[#f1f0ee] border border-white/15 focus:border-[#caf300]/60 outline-none resize-y"></textarea>
        <button type="submit" :disabled="!(borradores[c.id] || '').trim() || enviando === c.id"
                class="px-4 py-2 rounded-xl bg-[#caf300] text-[#121700] text-[11px] font-black uppercase tracking-wider disabled:opacity-50 self-end">
          {{ enviando === c.id ? 'Enviando…' : 'Responder' }}
        </button>
      </form>
    </article>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useAppStore } from '../../stores/appStore';
import {
  CATEGORIAS_DE_AYUDA, cambiarEstadoDeAyuda, getColaDeAyuda, responderAyuda,
} from '../../services/feedback';

const store = useAppStore();
const ESTADOS = { nuevo: 'Nueva', investigando: 'Investigando', resuelto: 'Resuelta' };
const ORIGENES = {
  perfil: 'Perfil', garmin: 'Garmin', codificacion: 'Codificación', decision_ia: 'Decisión de la IA',
  app: 'La app', desconocido: 'Sin señales',
};
const CLASE_ORIGEN = {
  perfil: 'bg-[#ffd166]/15 text-[#ffd166]', garmin: 'bg-[#a5eeff]/15 text-[#a5eeff]',
  codificacion: 'bg-[#ffb4ab]/15 text-[#ffb4ab]', decision_ia: 'bg-[#caf300]/15 text-[#caf300]',
  app: 'bg-[#ffb4ab]/15 text-[#ffb4ab]',
};
const RELOJ = { al_dia: 'La versión de hoy', desactualizado: 'Otra versión (anterior)', sin_subir: 'No se subió' };

const cola = ref(null);
const cargando = ref(false);
const filtroEstado = ref('');
const filtroCategoria = ref('');
const borradores = reactive({});
const enviando = ref(null);

const items = computed(() => cola.value?.items || []);
const pendientes = computed(() => items.value.filter((c) => c.status !== 'resuelto').length);
const etiquetaDe = (v) => CATEGORIAS_DE_AYUDA.find((c) => c.valor === v)?.etiqueta || 'Otra';
const sesion = (c) => c.diagnostic?.sesion || null;
const senales = (c) => Object.values(c.diagnostic?.triaje?.motivos || {}).flat();
const fecha = (s) => {
  if (!s) return '';
  const d = new Date(String(s).replace(' ', 'T'));
  return Number.isNaN(d.getTime()) ? '' : d.toLocaleString('es', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
};

async function cargar() {
  cargando.value = true;
  try {
    cola.value = await getColaDeAyuda({ status: filtroEstado.value, category: filtroCategoria.value });
  } catch (e) {
    cola.value = { available: true, items: [] };
  } finally {
    cargando.value = false;
  }
}

async function cambiarEstado(c, estado) {
  const antes = c.status;
  c.status = estado;
  try {
    await cambiarEstadoDeAyuda(c.id, estado);
  } catch (e) {
    c.status = antes;
    store.showToast('No se pudo cambiar el estado.');
  }
}

async function responder(c) {
  const texto = (borradores[c.id] || '').trim();
  if (!texto) return;
  enviando.value = c.id;
  try {
    await responderAyuda(c.id, texto);
    borradores[c.id] = '';
    store.showToast('Respuesta enviada: le llega como aviso.');
    await cargar();
  } catch (e) {
    store.showToast(e?.response?.data?.message || 'No se pudo enviar la respuesta.');
  } finally {
    enviando.value = null;
  }
}

onMounted(cargar);
</script>
