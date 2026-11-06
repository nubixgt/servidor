<template>
  <!-- «Ayuda / Opinar» (Beta 3.0, F3). Tres cosas en un sitio: pedir ayuda con una persona
       detrás (con su diagnóstico), opinar como siempre, y ver lo que ya preguntó y lo que
       le contestamos. -->
  <div
    v-if="store.ayuda.abierta"
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/75 backdrop-blur-xl animate-scale-in"
    @keydown.esc="cerrar"
  >
    <div class="glass-panel border border-white/20 rounded-3xl max-w-xl w-full p-5 sm:p-7 space-y-5 shadow-[0_0_60px_rgba(0,0,0,0.6)] relative max-h-[88vh] overflow-y-auto hide-scrollbar"
         role="dialog" aria-labelledby="ayuda-titulo">
      <div class="flex justify-between items-start gap-3">
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 rounded-2xl bg-[#a5eeff]/15 border border-[#a5eeff]/40 flex items-center justify-center text-[#a5eeff]">
            <span class="material-symbols-outlined text-[24px]">support_agent</span>
          </div>
          <div>
            <h3 id="ayuda-titulo" class="text-lg font-black text-white">Ayuda</h3>
            <p class="text-xs text-white/75">Te contesta una persona, no la IA. La respuesta te llega en tus avisos y en «Mis consultas».</p>
          </div>
        </div>
        <button type="button" @click="cerrar" aria-label="Cerrar"
                class="p-2 text-[#c5c9ac] hover:text-white rounded-xl hover:bg-white/10">
          <span class="material-symbols-outlined text-2xl">close</span>
        </button>
      </div>

      <div class="flex gap-1.5 glass-pill rounded-xl p-1" role="tablist">
        <button v-for="p in PESTANAS" :key="p.id" type="button" role="tab" :aria-selected="pestana === p.id"
                @click="cambiarPestana(p.id)"
                class="flex-1 px-2 py-2 rounded-lg text-[11px] font-black uppercase tracking-wider transition-all"
                :class="pestana === p.id ? 'bg-[#caf300] text-[#121700]' : 'text-[#c5c9ac] hover:text-white'">
          {{ p.etiqueta }}
        </button>
      </div>

      <!-- Pedir ayuda -->
      <form v-if="pestana === 'ayuda'" class="space-y-4" @submit.prevent="enviarAyuda">
        <p v-if="store.ayuda.sesion" class="text-xs text-[#a5eeff] flex items-center gap-1.5">
          <span class="material-symbols-outlined text-[16px]">event</span>
          Sobre tu sesión: <b class="text-white">{{ store.ayuda.sesion }}</b>
        </p>
        <fieldset class="space-y-2">
          <legend class="text-[10px] uppercase font-black text-[#8f9378] tracking-wider mb-1">¿Qué te pasa?</legend>
          <label v-for="c in CATEGORIAS_DE_AYUDA" :key="c.valor"
                 class="flex items-center gap-2.5 px-3 min-h-[44px] rounded-xl border cursor-pointer text-sm transition-colors"
                 :class="categoria === c.valor ? 'border-[#caf300]/60 bg-[#caf300]/5 text-white' : 'border-white/10 text-[#c5c9ac] hover:border-white/25'">
            <input v-model="categoria" type="radio" name="ayuda-categoria" :value="c.valor" class="sr-only peer" />
            <span class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 peer-focus-visible:ring-2 peer-focus-visible:ring-[#caf300]"
                  :class="categoria === c.valor ? 'border-[#caf300]' : 'border-white/40'">
              <span v-if="categoria === c.valor" class="w-2.5 h-2.5 rounded-full bg-[#caf300]"></span>
            </span>
            {{ c.etiqueta }}
          </label>
        </fieldset>
        <label class="block">
          <span class="text-[10px] uppercase font-black text-[#8f9378] tracking-wider">Cuéntanos</span>
          <textarea v-model="mensaje" rows="4" maxlength="4000" required
                    placeholder="Qué ves, qué esperabas ver…"
                    class="mt-1.5 w-full rounded-2xl px-3.5 py-2.5 text-sm bg-[#1b1d22] text-[#f1f0ee] border border-white/15 focus:border-[#caf300]/60 outline-none resize-y"></textarea>
        </label>
        <!-- Lo que se adjunta, dicho antes de enviar y con sus palabras exactas. -->
        <details class="group rounded-2xl border border-white/10 bg-white/[0.02] px-3.5 text-[12px] text-white/75">
          <summary class="cursor-pointer list-none flex items-center gap-1.5 min-h-[44px] text-[13px] text-white/85">
            <span class="material-symbols-outlined text-[18px] transition-transform group-open:rotate-90">chevron_right</span>
            Con tu consulta van datos de tu cuenta para poder mirarlo (nunca tu contraseña). Ver cuáles
          </summary>
          <div class="pb-3 space-y-1">
          <ul class="list-disc pl-4 space-y-0.5">
            <li>tu mensaje y la pantalla desde la que escribes{{ store.ayuda.sesion ? ', y esta sesión' : '' }};</li>
            <li v-if="store.ayuda.sesion">de esa sesión: lo que decidió tu entrenador, lo que se escribió para tu reloj y si tu reloj tiene la versión de hoy;</li>
            <li>de tu cuenta: cómo de completo está tu perfil, si tus días de entreno están marcados y qué carreras caen en un día de descanso, si la contraseña de Garmin está guardada (sólo sí o no), tu semana automática, tus últimas semanas escritas y enviadas a tu reloj, los ajustes que hicimos a tus últimas semanas, lo prescrito y lo hecho de tu última semana, y si tus zonas de pulso son las de fábrica;</li>
            <li>los últimos errores de esta pantalla{{ erroresAdjuntos ? ` (${erroresAdjuntos})` : ' (ahora no hay ninguno)' }}.</li>
          </ul>
          <p>Nunca tu contraseña. Sólo lo lee quien te responde.</p>
          </div>
        </details>
        <p v-if="error" class="text-xs text-[#ffb4ab]">{{ error }}</p>
        <button type="submit" :disabled="enviando || !categoria || !mensaje.trim()"
                class="w-full py-3 rounded-xl bg-[#caf300] hover:bg-[#bbf400] text-[#121700] text-xs font-black uppercase tracking-wider disabled:opacity-50">
          {{ enviando ? 'Enviando…' : 'Enviar consulta' }}
        </button>
        <p v-if="!enviando && (!categoria || !mensaje.trim())" class="text-[12px] text-white/60 text-center -mt-2">
          {{ !categoria ? 'Elige qué te pasa' : 'Cuéntanos qué ves' }} para poder enviarla.
        </p>
      </form>

      <!-- Opinar: lo de siempre -->
      <form v-else-if="pestana === 'opinar'" class="space-y-4" @submit.prevent="enviarOpinion">
        <div class="flex gap-2 flex-wrap">
          <label v-for="t in TIPOS" :key="t.valor"
                 class="flex items-center gap-2 px-3 py-2 rounded-xl border cursor-pointer text-sm"
                 :class="tipo === t.valor ? 'border-[#caf300]/60 text-white' : 'border-white/10 text-[#c5c9ac]'">
            <input v-model="tipo" type="radio" name="opinar-tipo" :value="t.valor" class="sr-only peer" />
            <span class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 peer-focus-visible:ring-2 peer-focus-visible:ring-[#caf300]"
                  :class="tipo === t.valor ? 'border-[#caf300]' : 'border-white/40'">
              <span v-if="tipo === t.valor" class="w-2.5 h-2.5 rounded-full bg-[#caf300]"></span>
            </span>
            {{ t.etiqueta }}
          </label>
        </div>
        <textarea v-model="opinion" rows="5" maxlength="4000" required
                  placeholder="Qué pasó, qué esperabas, o qué te gustaría que hiciera tu coach…"
                  class="w-full rounded-2xl px-3.5 py-2.5 text-sm bg-[#1b1d22] text-[#f1f0ee] border border-white/15 focus:border-[#caf300]/60 outline-none resize-y"></textarea>
        <p v-if="whatsapp" class="text-xs text-[#8f9378]">¿Prefieres hablarlo?
          <a :href="whatsapp" target="_blank" rel="noopener noreferrer" class="text-[#caf300] font-bold">Escríbenos por WhatsApp</a>
        </p>
        <button type="submit" :disabled="enviando || !opinion.trim()"
                class="w-full py-3 rounded-xl bg-[#caf300] hover:bg-[#bbf400] text-[#121700] text-xs font-black uppercase tracking-wider disabled:opacity-50">
          {{ enviando ? 'Enviando…' : 'Enviar' }}
        </button>
      </form>

      <!-- Mis consultas -->
      <div v-else class="space-y-3">
        <p v-if="cargandoMias" class="text-sm text-[#8f9378]">Cargando…</p>
        <p v-else-if="!mias.length" class="text-sm text-[#8f9378]">Todavía no nos has preguntado nada.</p>
        <article v-for="c in mias" :key="c.id" class="rounded-2xl border border-white/10 bg-white/[0.02] p-3.5 space-y-2">
          <div class="flex items-center justify-between gap-2 flex-wrap">
            <span class="text-[11px] text-[#8f9378]">{{ fecha(c.created_at) }} · {{ etiquetaDe(c.category) }}</span>
            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider" :class="CLASE_ESTADO[c.status] || 'bg-white/10 text-white'">
              {{ ESTADOS_DE_AYUDA[c.status] || c.status }}
            </span>
          </div>
          <p class="text-sm text-white whitespace-pre-line break-words">{{ c.message }}</p>
          <div v-if="c.reply" class="rounded-xl bg-[#a5eeff]/5 border border-[#a5eeff]/20 px-3 py-2">
            <p class="text-[10px] uppercase font-black tracking-wider text-[#a5eeff]">Te respondimos · {{ fecha(c.replied_at) }}</p>
            <p class="text-sm text-[#f1f0ee] whitespace-pre-line break-words mt-1">{{ c.reply }}</p>
          </div>
        </article>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useAppStore } from '../../stores/appStore';
import { CATEGORIAS_DE_AYUDA, ESTADOS_DE_AYUDA, enviarFeedback, misConsultas, pedirAyuda } from '../../services/feedback';
import { ultimosErrores } from '../../utils/erroresDelNavegador';

const store = useAppStore();

const PESTANAS = [
  { id: 'ayuda', etiqueta: 'Pedir ayuda' },
  { id: 'opinar', etiqueta: 'Opinar' },
  { id: 'mias', etiqueta: 'Mis consultas' },
];
const TIPOS = [
  { valor: 'fallo', etiqueta: 'Algo falla' },
  { valor: 'sugerencia', etiqueta: 'Una idea' },
  { valor: 'me_gusta', etiqueta: 'Algo que me gusta' },
];
const CLASE_ESTADO = {
  nuevo: 'bg-[#a5eeff]/15 text-[#a5eeff]',
  investigando: 'bg-[#ffd166]/15 text-[#ffd166]',
  resuelto: 'bg-[#caf300]/15 text-[#caf300]',
};

const pestana = ref('ayuda');
const categoria = ref('');
const mensaje = ref('');
const tipo = ref('fallo');
const opinion = ref('');
const enviando = ref(false);
const error = ref('');
const mias = ref([]);
const cargandoMias = ref(false);
const erroresAdjuntos = ref(0);

// El número no es secreto: va en un enlace público. VITE_FEEDBACK_WHATSAPP lo cambia.
const NUMERO = (import.meta.env.VITE_FEEDBACK_WHATSAPP || '50230965754').replace(/\D/g, '');
const whatsapp = computed(() => {
  if (!NUMERO) return '';
  const quien = store.athlete?.name || store.authUser?.name || '';
  const texto = `Hola, soy ${quien || '(mi nombre)'} y uso Power Coach AI.\n\n— Qué pasó:\n— Qué esperaba:\n\n(pantalla: ${window.location.pathname})`;
  return `https://wa.me/${NUMERO}?text=${encodeURIComponent(texto)}`;
});

const etiquetaDe = (v) => CATEGORIAS_DE_AYUDA.find((c) => c.valor === v)?.etiqueta || 'Otra';
const fecha = (s) => {
  if (!s) return '';
  const d = new Date(String(s).replace(' ', 'T'));
  return Number.isNaN(d.getTime()) ? '' : d.toLocaleString('es', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
};

const cargarMias = async () => {
  cargandoMias.value = true;
  try {
    mias.value = (await misConsultas()).items || [];
  } catch (e) {
    mias.value = [];
  } finally {
    cargandoMias.value = false;
  }
};

const cambiarPestana = (id) => {
  pestana.value = id;
  error.value = '';
  if (id === 'mias') cargarMias();
};

// Cada vez que se abre: la categoría que trae (desde una sesión, «plan»), en blanco el resto.
watch(() => store.ayuda.abierta, (abierta) => {
  if (!abierta) return;
  pestana.value = 'ayuda';
  categoria.value = store.ayuda.categoria || '';
  // El botón de un plan en «Precios» (la tuerca) llega con el mensaje ya escrito; se puede cambiar.
  mensaje.value = store.ayuda.mensaje || '';
  error.value = '';
  erroresAdjuntos.value = ultimosErrores().length;
});

const cerrar = () => store.cerrarAyuda();

const enviarAyuda = async () => {
  if (enviando.value) return;
  enviando.value = true;
  error.value = '';
  try {
    const ok = await pedirAyuda({
      categoria: categoria.value,
      mensaje: mensaje.value.trim(),
      sessionKey: store.ayuda.sessionKey,
      erroresDelNavegador: ultimosErrores(),
    });
    if (!ok) {
      error.value = 'No se pudo enviar. Mándanoslo por WhatsApp, por favor.';
      return;
    }
    store.showToast('Recibido. Te contestamos aquí y en tus avisos.');
    cerrar();
  } finally {
    enviando.value = false;
  }
};

const enviarOpinion = async () => {
  if (enviando.value) return;
  enviando.value = true;
  try {
    const ok = await enviarFeedback({ message: opinion.value.trim(), kind: tipo.value, context: 'general' });
    store.showToast(ok ? 'Gracias. Lo leemos todo.' : 'No se pudo enviar. Mándanoslo por WhatsApp, por favor.');
    if (ok) {
      opinion.value = '';
      cerrar();
    }
  } finally {
    enviando.value = false;
  }
};
</script>
