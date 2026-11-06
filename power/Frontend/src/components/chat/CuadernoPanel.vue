<template>
  <!-- El cuaderno del coach (Beta 3.0, F9): lo mismo en su página y en la pestaña «Lo que sé
       de ti» del chat. `compacto` lo pone en una columna para el chat. -->
  <div class="grid gap-6 items-start" :class="compacto ? 'grid-cols-1' : 'grid-cols-1 lg:grid-cols-12 lg:gap-8'">
    <!-- Añadir -->
    <section class="glass-card rounded-3xl space-y-4 border border-white/10"
             :class="compacto ? 'p-4' : 'lg:col-span-5 p-6 sm:p-7 sticky top-24 shadow-2xl'">
      <h3 class="text-base font-black text-white tracking-tight flex items-center gap-2">
        <span class="material-symbols-outlined text-[#caf300]">edit_note</span>
        <span>Añadir nota</span>
      </h3>
      <form @submit.prevent="handleAddNote" class="space-y-3">
        <label class="block text-[13px] font-bold text-white/80" for="nota-nueva">Qué debe recordar tu coach</label>
        <textarea
          id="nota-nueva"
          rows="3"
          v-model="newText"
          class="w-full glass-input rounded-2xl p-3.5 text-white text-sm focus:outline-none transition-all resize-none placeholder:text-white/40 -mt-1"
          placeholder="Ej. prefiero la tirada larga el domingo"
          required
        />
        <!-- Los ejemplos, pegados al campo (revisión del 2026-10-22: estaban en la otra columna, lejos de donde se
             escribe). Tocar uno lo escribe en la nota con su tipo. -->
        <div v-if="!newText.trim()" class="flex flex-wrap gap-2 -mt-1">
          <span class="w-full text-[12px] text-white/65">Ideas (toca una para escribirla):</span>
          <button v-for="e in EJEMPLOS" :key="e.texto" type="button" @click="usarEjemplo(e)"
                  class="px-3 min-h-[40px] rounded-xl border border-white/15 text-[13px] text-white/85 hover:border-[#caf300]/50 hover:text-white">
            {{ e.texto }}
          </button>
        </div>
        <!-- El tipo decide cómo se usa (una preferencia llega a tu semana como petición): con su etiqueta. -->
        <label class="block text-[13px] font-bold text-white/80" for="nota-tipo">Tipo de nota</label>
        <select id="nota-tipo" v-model="newCategory"
                class="w-full glass-input rounded-2xl px-4 min-h-[48px] text-white text-sm focus:outline-none -mt-1">
          <option v-for="(label, key) in categories" :key="key" :value="key" class="bg-[#121700] text-white">{{ label }}</option>
        </select>
        <p v-if="PARA_QUE[newCategory]" class="text-[12px] text-white/70 -mt-1 leading-snug">{{ PARA_QUE[newCategory] }}</p>
        <button type="submit" :disabled="isBusy || !newText.trim()"
                class="w-full min-h-[44px] bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl font-black text-xs uppercase tracking-wider flex items-center justify-center gap-2 disabled:bg-white/10 disabled:text-white/40">
          <span class="material-symbols-outlined text-[20px]">add_circle</span>
          <span>Guardar nota</span>
        </button>
      </form>
    </section>

    <section class="space-y-6" :class="compacto ? '' : 'lg:col-span-7'">
      <div v-if="!store.notebookLoaded" class="p-6 text-center glass-card rounded-3xl text-[#c5c9ac] text-sm border border-white/10">
        Cargando cuaderno...
      </div>
      <div v-else-if="!store.coachNotes.length"
           class="p-6 text-center glass-card rounded-3xl text-[#c5c9ac] text-sm border border-white/10 leading-relaxed">
        <p>Todavía no hay notas. Aquí queda lo que le cuentas a tu coach en el chat y lo que escribas tú; lo tiene
          en cuenta al preparar tu próxima semana.</p>
        <p class="mt-2 text-[12px] text-white/70">¿Es sólo para una semana (un viaje, una carrera con amigos)? Escríbelo en
          el cuestionario de esa semana, al generarla.</p>
      </div>

      <template v-else>
        <!-- «Revisa mi cuaderno» (Beta 3.0, F9; decisión del responsable del 2026-10-11): el coach
             lee el cuaderno y el perfil, pregunta lo que no sabe y propone el cuaderno ordenado
             y lo que hay que cambiar en el perfil. Cada cambio lo acepta el atleta. -->
        <div v-if="vigentes.length >= 2 || propuesta" class="glass-card rounded-2xl border border-white/10 p-4 space-y-3">
          <div class="flex items-center justify-between gap-3 flex-wrap">
            <p class="text-xs text-[#c5c9ac] flex-1 min-w-[200px]">
              ¿Repetidas, viejas o que no cuadran con tu perfil? Tu coach lo lee todo, te pregunta lo que no sepa y te propone cómo dejarlo. Tú decides cada cambio.
            </p>
            <button type="button" @click="reordenar()" :disabled="reordenando"
                    class="flex items-center gap-1.5 px-3.5 py-2 glass-pill rounded-xl text-[11px] font-black uppercase tracking-wider text-[#a5eeff] disabled:opacity-50">
              <span class="material-symbols-outlined text-[16px]" :class="reordenando ? 'animate-spin' : ''">{{ reordenando ? 'progress_activity' : 'auto_awesome' }}</span>
              {{ reordenando ? 'Leyendo tu cuaderno…' : 'Revisa mi cuaderno' }}
            </button>
          </div>

          <!-- 1. Lo que el coach necesita saber antes de ordenar. -->
          <form v-if="propuesta?.preguntas?.length" class="space-y-3" @submit.prevent="responder">
            <p class="text-xs font-black uppercase tracking-wider text-[#caf300]">Antes, unas preguntas</p>
            <div v-for="(q, i) in propuesta.preguntas" :key="i" class="rounded-xl border border-white/10 p-3 space-y-2">
              <p class="text-sm text-white">{{ q.texto }}</p>
              <ul v-if="q.ids?.length" class="space-y-0.5">
                <li v-for="id in q.ids" :key="id" class="text-[11px] text-[#8f9378]">«{{ textoDe(id) }}»</li>
              </ul>
              <div v-if="q.opciones?.length" class="flex flex-wrap gap-1.5">
                <button v-for="o in q.opciones" :key="o" type="button" @click="q.respuesta = o"
                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold border transition-colors"
                        :class="q.respuesta === o ? 'bg-[#caf300] text-[#121700] border-[#caf300]' : 'border-white/15 text-[#c5c9ac]'">{{ o }}</button>
              </div>
              <input v-model="q.respuesta" type="text" maxlength="300" :aria-label="q.texto"
                     class="w-full glass-input rounded-xl px-3 py-2 text-sm text-white" placeholder="Tu respuesta" />
            </div>
            <button type="submit" :disabled="reordenando || !algunaRespuesta"
                    class="w-full py-2.5 bg-[#caf300] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider disabled:opacity-40">
              Seguir con estas respuestas
            </button>
          </form>

          <!-- 2. Lo que hay que cambiar en el perfil: no va al cuaderno, lo cambia el atleta. -->
          <div v-if="propuesta?.avisos?.length" class="rounded-xl border border-[#ffd166]/30 bg-[#ffd166]/5 p-3 space-y-2">
            <p class="text-xs font-black uppercase tracking-wider text-[#ffd166]">Cambia esto en tu perfil</p>
            <div v-for="(a, i) in propuesta.avisos" :key="i" class="space-y-1">
              <p class="text-sm text-white">{{ a.que_hacer }}</p>
              <p class="text-[11px] text-[#8f9378]">{{ a.donde }}</p>
              <router-link :to="{ path: RUTA_DEL_PERFIL, query: { tab: PESTANA[a.pestana] || 'objetivos' } }"
                           class="inline-block text-[11px] font-black uppercase tracking-wider text-[#a5eeff] hover:underline">Ir a mi perfil</router-link>
            </div>
          </div>

          <p v-if="propuesta && !propuesta.preguntas?.length && !propuesta.acciones.length && !propuesta.avisos?.length" class="text-xs text-[#8f9378]">
            {{ propuesta.aviso || 'Tu cuaderno está bien así y cuadra con tu perfil: no hay nada que cambiar.' }}
          </p>
          <ul v-else-if="propuesta?.acciones?.length" class="space-y-2.5">
            <li v-if="propuesta.aviso" class="text-[11px] text-[#ffd166]">{{ propuesta.aviso }}</li>
            <li v-for="(a, i) in propuesta.acciones" :key="i" class="rounded-xl border border-white/10 p-3 space-y-2">
              <p class="text-[10px] font-black uppercase tracking-wider" :class="COLOR[a.accion]">{{ ACCION[a.accion] }}</p>
              <ul class="space-y-0.5">
                <li v-for="o in a.originales" :key="o.id" class="text-xs text-[#8f9378] line-through decoration-white/20">{{ o.text }}</li>
              </ul>
              <p v-if="a.texto" class="text-sm text-white">→ {{ a.texto }}</p>
              <p v-if="a.accion === 'al_perfil'" class="text-xs text-[#a5eeff]">Va en {{ a.donde }}. Cópialo allí; aquí sólo se retira del cuaderno.</p>
              <p class="text-[11px] text-[#c5c9ac]">{{ a.motivo }}</p>
              <div class="flex gap-2 pt-1">
                <button type="button" @click="aceptar(a, i)" :disabled="!!a.estado"
                        class="px-3 py-1.5 rounded-lg bg-[#caf300] text-[#121700] text-[11px] font-black uppercase tracking-wider disabled:opacity-40">
                  {{ a.estado === 'aceptada' ? 'Hecho' : a.accion === 'al_perfil' ? 'Ya está en mi perfil' : 'Aceptar' }}
                </button>
                <button v-if="!a.estado" type="button" @click="a.estado = 'descartada'"
                        class="px-3 py-1.5 glass-pill rounded-lg text-[11px] font-black uppercase tracking-wider text-[#c5c9ac]">
                  Dejarla como está
                </button>
                <button v-if="a.accion === 'al_perfil' && !a.estado" type="button" @click="copiar(a)"
                        class="px-3 py-1.5 glass-pill rounded-lg text-[11px] font-black uppercase tracking-wider text-[#a5eeff]">
                  Copiar texto
                </button>
              </div>
            </li>
          </ul>
        </div>

        <div v-if="propuestas.length" class="space-y-3">
          <h3 class="text-xs font-black text-[#caf300] uppercase tracking-widest">Tu coach quiere anotar esto</h3>
          <NoteCard v-for="note in propuestas" :key="note.id" :note="note" :label="categories[note.category] || note.category"
                    :date="noteDate(note.created_at)" :compacto="compacto"
                    @confirm="store.updateNote(note.id, { status: 'vigente' })" @delete="store.deleteNote(note.id)" />
        </div>
        <div v-if="vigentes.length" class="space-y-3">
          <h3 class="text-xs font-black text-[#a5eeff] uppercase tracking-widest">En uso ({{ vigentes.length }}) — esto llega a tu plan</h3>
          <NoteCard v-for="note in vigentes" :key="note.id" :note="note" :label="categories[note.category] || note.category"
                    :date="noteDate(note.created_at)" :compacto="compacto"
                    @edit="handleEdit(note)" @retire="handleRetire(note)" />
        </div>
        <div v-if="retiradas.length" class="space-y-3">
          <h3 class="text-xs font-black text-[#8f9378] uppercase tracking-widest">Retiradas ({{ retiradas.length }}) — ya no se aplican</h3>
          <NoteCard v-for="note in retiradas" :key="note.id" :note="note" :label="categories[note.category] || note.category"
                    :date="noteDate(note.created_at)" :compacto="compacto"
                    @restore="store.updateNote(note.id, { status: 'vigente' })" @delete="borrarParaSiempre(note)" />
        </div>
      </template>
    </section>
  </div>
</template>

<script setup>
import { computed, h, onMounted, ref } from 'vue';
import { useAppStore } from '../../stores/appStore';
import swal from '../../utils/swal';
import { fechaCorta } from '../../utils/deportes';
import { aplicarReorganizacion, reorganizarCuaderno } from '../../services/notebook';

defineProps({ compacto: { type: Boolean, default: false } });
const store = useAppStore();

const FALLBACK_CATEGORIES = {
  medicacion: 'Medicación y suplementos',
  sueno: 'Sueño y horarios',
  logistica: 'Logística y disponibilidad',
  preferencia: 'Preferencias de entrenamiento',
  molestia: 'Molestias y antecedentes',
  nutricion: 'Nutrición',
  vida: 'Trabajo, viajes y vida',
  otro: 'Otros',
};
const ACCION = { reescribir: 'Reescribirla', fusionar: 'Juntarlas en una', al_perfil: 'Pasarla a tu perfil', retirar: 'Retirarla: ya no aplica' };
const COLOR = { reescribir: 'text-[#a5eeff]', fusionar: 'text-[#a5eeff]', al_perfil: 'text-[#caf300]', retirar: 'text-[#ffd166]' };

const newText = ref('');
const EJEMPLOS = [
  { texto: 'Prefiero la tirada larga el domingo', categoria: 'preferencia' },
  { texto: 'Los jueves entreno de noche por trabajo', categoria: 'logistica' },
  { texto: 'No me gustan las series en cuesta', categoria: 'preferencia' },
];
// Para qué sirve cada tipo, en una línea: el tipo decide cómo lo usa tu coach.
const PARA_QUE = {
  preferencia: 'Algo que pides de tus entrenos: tu coach lo respeta al preparar cada semana.',
  logistica: 'Cuándo y dónde puedes entrenar.',
  molestia: 'Algo que te molesta o te pasó: tu coach lo tiene en cuenta, en proporción a lo que dices.',
  medicacion: 'Lo que tomas y puede influir en tu entreno.',
};
const usarEjemplo = (e) => { newText.value = e.texto; newCategory.value = e.categoria; };
const newCategory = ref('preferencia');
const isBusy = ref(false);
const reordenando = ref(false);
const propuesta = ref(null);

const categories = computed(() => {
  const c = store.notebookCategories;
  return c && Object.keys(c).length ? c : FALLBACK_CATEGORIES;
});
const byStatus = (s) => store.coachNotes.filter((n) => n.status === s);
const propuestas = computed(() => byStatus('propuesta'));
const vigentes = computed(() => byStatus('vigente'));
const retiradas = computed(() => byStatus('retirada'));

const noteDate = (iso) => {
  if (!iso) return '';
  const d = new Date(iso.replace(' ', 'T'));
  if (Number.isNaN(d.getTime())) return iso;
  const days = Math.floor((Date.now() - d.getTime()) / 86400000);
  let rel = 'hoy';
  if (days === 1) rel = 'ayer';
  else if (days > 1 && days < 30) rel = `hace ${days} días`;
  else if (days >= 30) {
    const m = Math.floor(days / 30);
    rel = m === 1 ? 'hace 1 mes' : `hace ${m} meses`;
  }
  return `${d.toLocaleDateString('es', { day: '2-digit', month: 'short' })} · ${rel}`;
};

const handleAddNote = async () => {
  if (!newText.value.trim() || isBusy.value) return;
  isBusy.value = true;
  try {
    // Una nota escrita por el atleta es suya: nace vigente.
    await store.addNote({ text: newText.value.trim(), category: newCategory.value, status: 'vigente' });
    newText.value = '';
  } catch (e) {
    // el store ya lo dice
  } finally {
    isBusy.value = false;
  }
};

const handleEdit = async (note) => {
  const r = await swal.fire({
    title: 'Editar anotación', input: 'textarea', inputValue: note.text,
    inputAttributes: { 'aria-label': 'Texto de la anotación' },
    showCancelButton: true, confirmButtonText: 'Guardar', cancelButtonText: 'Cancelar',
  });
  const text = (r.value || '').trim();
  if (!r.isConfirmed || !text || text === note.text) return;
  await store.updateNote(note.id, { text });
};

const handleRetire = async (note) => {
  const r = await swal.fire({
    title: 'Retirar anotación',
    text: 'Deja de aplicarse pero se conserva en el historial (no se borra).',
    input: 'text', inputPlaceholder: '¿Por qué deja de aplicar? (opcional)',
    showCancelButton: true, confirmButtonText: 'Retirar', cancelButtonText: 'Cancelar',
  });
  if (!r.isConfirmed) return;
  await store.updateNote(note.id, { status: 'retirada', retired_reason: (r.value || '').trim() });
};

// Borrar retira (decisión del responsable, 2026-10-11): sólo una retirada se borra de verdad,
// y se pregunta antes porque eso sí no tiene vuelta.
const borrarParaSiempre = async (note) => {
  const r = await swal.fire({
    title: 'Borrar para siempre',
    text: 'Esta nota ya no llega a tu plan. Si la borras, desaparece también del historial y no se puede recuperar.',
    showCancelButton: true, confirmButtonText: 'Borrar', cancelButtonText: 'Cancelar',
  });
  if (r.isConfirmed) await store.deleteNote(note.id, true);
};

// Las pestañas del perfil por su nombre en pantalla (ProfileView acepta ?tab=).
const PESTANA = { Identidad: 'identidad', 'Objetivos y días': 'objetivos', Historial: 'historial', Avanzado: 'avanzado' };
const RUTA_DEL_PERFIL = '/admin/perfil';

const textoDe = (id) => store.coachNotes.find((n) => String(n.id) === String(id))?.text || '';
const algunaRespuesta = computed(() => (propuesta.value?.preguntas || []).some((q) => (q.respuesta || '').trim()));

const reordenar = async (respuestas = []) => {
  reordenando.value = true;
  try {
    const p = await reorganizarCuaderno(respuestas);
    propuesta.value = {
      ...p,
      preguntas: (p.preguntas || []).map((q) => ({ ...q, respuesta: '' })),
      acciones: (p.acciones || []).map((a) => ({ ...a, estado: '' })),
      avisos: p.avisos || [],
    };
  } catch (e) {
    store.showToast(e?.response?.data?.message || 'No se pudo revisar el cuaderno ahora.');
  } finally {
    reordenando.value = false;
  }
};

// Segunda vuelta: con lo que contestó (las que dejó en blanco no se mandan).
const responder = () => reordenar(
  propuesta.value.preguntas
    .filter((q) => (q.respuesta || '').trim())
    .map((q) => ({ pregunta: q.texto, respuesta: q.respuesta.trim() })),
);

const aceptar = async (a) => {
  try {
    await aplicarReorganizacion({ accion: a.accion, ids: a.ids, texto: a.texto, categoria: a.categoria, motivo: a.motivo, donde: a.donde });
    a.estado = 'aceptada';
    await store.loadNotebook();
  } catch (e) {
    store.showToast(e?.response?.data?.message || 'No se pudo aplicar ese cambio.');
  }
};

const copiar = async (a) => {
  const texto = a.originales.map((o) => o.text).join('\n');
  try {
    await navigator.clipboard.writeText(texto);
    store.showToast('Copiado. Pégalo en tu perfil.');
  } catch (e) {
    await swal.fire({ title: 'Cópialo', text: texto });
  }
};

onMounted(() => {
  if (!store.notebookLoaded) store.loadNotebook();
});

// --- La tarjeta de una nota --------------------------------------------------------
const NoteCard = {
  props: { note: Object, label: String, date: String, compacto: Boolean },
  emits: ['confirm', 'edit', 'retire', 'restore', 'delete'],
  setup(props, { emit }) {
    const n = () => props.note;
    return () => {
      const s = n().status;
      const borderClass = s === 'vigente' ? 'border-l-4 border-l-[#a5eeff]' : s === 'propuesta' ? 'border-l-4 border-l-[#caf300]' : '';
      const actions = [];
      const btn = (txt, ev, cls) => h('button', { onClick: () => emit(ev), class: `${cls} hover:underline` }, txt);
      if (s === 'propuesta') {
        actions.push(h('button', {
          onClick: () => emit('confirm'),
          class: 'px-4 py-1.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl transition-all',
        }, 'Confirmar'));
        actions.push(btn('Descartar', 'delete', 'text-[#c5c9ac] hover:text-[#ffb4ab]'));
      } else if (s === 'vigente') {
        actions.push(btn('Editar', 'edit', 'text-[#a5eeff]'));
        actions.push(btn('Retirar', 'retire', 'text-[#e5e2e1]'));
      } else {
        actions.push(btn('Volver a usar', 'restore', 'text-[#a5eeff]'));
        actions.push(btn('Borrar para siempre', 'delete', 'text-[#8f9378] hover:text-[#ffb4ab]'));
      }
      const fuente = { chat: 'del chat', reorden: 'al reordenar', manual: 'escrito por ti' }[n().source] || 'escrito por ti';
      const meta = [
        h('span', { class: 'text-[10px] font-black uppercase tracking-wider text-[#8f9378]' }, props.label),
        h('span', {}, `${props.date} · ${fuente}`),
      ];
      if (n().retired_at) meta.push(h('span', {}, `retirada el ${fechaCorta(String(n().retired_at).slice(0, 10))}`));
      return h('div', {
        class: `glass-card rounded-3xl border border-white/10 ${props.compacto ? 'p-4' : 'p-6 sm:p-7 shadow-2xl'} ${borderClass} ${s === 'retirada' ? 'opacity-70' : ''}`,
      }, [
        h('p', { class: 'text-sm text-[#e5e2e1] leading-relaxed font-medium whitespace-pre-wrap' }, n().text),
        n().quote ? h('p', { class: 'text-xs text-[#8f9378] italic mt-2' }, `«${n().quote}»`) : null,
        n().retired_reason ? h('p', { class: 'text-xs text-[#8f9378] mt-2' }, `Motivo: ${n().retired_reason}`) : null,
        h('div', { class: 'flex flex-wrap items-center justify-between gap-3 pt-3 mt-3 border-t border-white/10' }, [
          h('div', { class: 'flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] font-mono-data text-[#8f9378]' }, meta),
          h('div', { class: 'flex flex-wrap items-center gap-3 text-xs font-black uppercase font-mono-data' }, actions),
        ]),
      ]);
    };
  },
};
</script>
