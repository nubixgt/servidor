<template>
  <!-- Elegir un ejercicio o un drill del catálogo (2026-10-23): para cambiar uno de la fuerza por otro, añadir
       uno, o decir qué drill es un paso de natación o de carrera. Sale de public/ejercicios/catalogo.json, la
       misma lista que usa el cerebro (exercise_catalog.py): lo que no está en él no se elige aquí. -->
  <Teleport to="body">
    <div class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center sm:p-4 bg-black/75 backdrop-blur-xl"
         role="dialog" aria-modal="true" aria-labelledby="catalogo-titulo" @keydown.esc="$emit('cerrar')">
      <div class="glass-panel border border-white/20 rounded-t-3xl sm:rounded-3xl max-w-2xl w-full p-4 sm:p-5 space-y-3 max-h-[92vh] flex flex-col">
        <div class="flex justify-between items-start gap-3 shrink-0">
          <div>
            <h3 id="catalogo-titulo" class="text-lg font-black text-white">{{ titulo }}</h3>
            <p v-if="enVezDe" class="text-xs text-[#c5c9ac]">En vez de «{{ enVezDe }}»</p>
          </div>
          <button type="button" @click="$emit('cerrar')" aria-label="Cerrar" class="p-2 text-[#c5c9ac] hover:text-white rounded-xl hover:bg-white/10">
            <span class="material-symbols-outlined text-2xl">close</span>
          </button>
        </div>

        <label class="relative block shrink-0">
          <span class="sr-only">Buscar</span>
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-[#8f9378]">search</span>
          <input ref="buscador" v-model="busqueda" type="search" :placeholder="ejemploDeBusqueda"
                 class="w-full rounded-xl bg-[#1b1d22] border border-white/25 text-white text-sm pl-9 pr-3 py-2.5 focus:outline-none focus:border-[#caf300]/70" />
        </label>

        <div class="flex gap-1.5 overflow-x-auto hide-scrollbar pb-1 shrink-0" role="group" aria-label="Familias">
          <button v-for="f in familias" :key="f" type="button" @click="familia = familia === f ? '' : f"
                  class="shrink-0 px-2.5 py-1.5 rounded-lg text-[11px] font-bold border min-h-[34px]"
                  :class="familia === f ? 'border-[#caf300] text-[#caf300] bg-[#caf300]/10' : 'border-white/15 text-[#c5c9ac] hover:text-white'">
            {{ f }}
          </button>
        </div>

        <p v-if="grupos.some((g) => ['fuerza', 'movilidad'].includes(g))" class="text-[11px] text-[#8f9378] flex items-center gap-1 shrink-0">
          <span class="material-symbols-outlined text-[15px] text-[#a5eeff]" aria-hidden="true">watch</span>
          está en la lista de tu reloj; los demás llegan como un paso con su nombre escrito.
        </p>
        <p v-if="cargando" class="text-sm text-[#8f9378]">Cargando el catálogo…</p>
        <p v-else-if="!lista.length" class="text-sm text-[#c5c9ac]">Nada con «{{ busqueda }}». Prueba con otra palabra.</p>

        <div v-else class="overflow-y-auto hide-scrollbar -mx-1 px-1 space-y-3">
          <section v-if="sugeridos.length" aria-labelledby="catalogo-sugeridos">
            <h4 id="catalogo-sugeridos" class="text-[10px] uppercase font-black tracking-wider text-[#8f9378] mb-1.5">
              De la misma familia
            </h4>
            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <li v-for="e in sugeridos" :key="'s' + e.slug"><FilaDeEjercicio :e="e" @elegir="elegir" /></li>
            </ul>
          </section>
          <section aria-label="Catálogo">
            <h4 v-if="sugeridos.length" class="text-[10px] uppercase font-black tracking-wider text-[#8f9378] mb-1.5">Todos</h4>
            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <li v-for="e in lista" :key="e.slug"><FilaDeEjercicio :e="e" @elegir="elegir" /></li>
            </ul>
          </section>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, defineComponent, h, nextTick, onMounted, ref } from 'vue';
import { cargarCatalogo, normalizar } from '../../utils/ejercicios';

const props = defineProps({
  titulo: { type: String, default: 'Elige un ejercicio' },
  // Qué grupos del catálogo: ['fuerza', 'movilidad'] para la fuerza; ['natacion'] o ['carrera'] para drills.
  grupos: { type: Array, default: () => ['fuerza', 'movilidad'] },
  // La familia del que se cambia («Empuje»): esos van primero.
  sugerir: { type: String, default: '' },
  enVezDe: { type: String, default: '' },
  // El que se cambia: no se ofrece a sí mismo.
  excluir: { type: String, default: '' },
});
const emit = defineEmits(['elegir', 'cerrar']);

const cargando = ref(true);
const todos = ref([]);
const busqueda = ref('');
const familia = ref('');
const buscador = ref(null);

onMounted(async () => {
  const c = await cargarCatalogo();
  todos.value = Object.entries(c?.ejercicios || {})
    .filter(([slug, e]) => props.grupos.includes(e.grupo) && slug !== props.excluir)
    .map(([slug, e]) => ({ slug, ...e }))
    .sort((a, b) => a.n - b.n);
  cargando.value = false;
  await nextTick();
  // En el teléfono el teclado taparía la lista nada más abrir: el foco sólo en pantalla ancha.
  if (window.matchMedia('(min-width: 640px)').matches) buscador.value?.focus();
});

// El ejemplo del buscador, del catálogo que se está viendo (con drills de natación decía «sentadilla, remo»).
const ejemploDeBusqueda = computed(() => {
  if (props.grupos.includes('natacion')) return 'Busca: patada, catch-up, sculling…';
  if (props.grupos.includes('carrera')) return 'Busca: skipping, talones, cuestas…';
  return 'Busca: sentadilla, remo, plancha…';
});
const familias = computed(() => [...new Set(todos.value.map((e) => e.sub))]);
const coincide = (e) => {
  const q = normalizar(busqueda.value).replace(/_/g, ' ');
  if (familia.value && e.sub !== familia.value) return false;
  if (!q) return true;
  return normalizar(`${e.es} ${e.en} ${e.sub}`).replace(/_/g, ' ').includes(q);
};
const lista = computed(() => todos.value.filter(coincide));
const sugeridos = computed(() => (props.sugerir && !busqueda.value && !familia.value
  ? todos.value.filter((e) => e.sub === props.sugerir) : []));

const elegir = (e) => emit('elegir', e);

const FilaDeEjercicio = defineComponent({
  props: { e: { type: Object, required: true } },
  emits: ['elegir'],
  setup(p, { emit: emitir }) {
    return () => h('button', {
      type: 'button', onClick: () => emitir('elegir', p.e),
      class: 'w-full flex items-center gap-3 p-2 rounded-xl border border-white/10 bg-white/[0.03] hover:border-[#caf300]/50 hover:bg-[#caf300]/5 text-left min-h-[56px]',
    }, [
      p.e.img
        ? h('img', { src: `${import.meta.env.BASE_URL}ejercicios/${p.e.img}`, alt: '', loading: 'lazy', class: 'w-12 h-12 rounded-lg object-cover bg-white shrink-0' })
        : h('span', { class: 'w-12 h-12 rounded-lg bg-white/5 flex items-center justify-center shrink-0' },
          [h('span', { class: 'material-symbols-outlined text-[#8f9378]' }, 'fitness_center')]),
      h('span', { class: 'min-w-0 flex-1' }, [
        h('span', { class: 'block text-[13px] font-bold text-white leading-snug' }, p.e.es),
        h('span', { class: 'block text-[11px] text-[#8f9378] leading-snug' }, p.e.sub),
      ]),
      p.e.reloj
        ? h('span', { class: 'material-symbols-outlined text-[16px] text-[#a5eeff] shrink-0', title: 'Llega a tu reloj con su nombre', 'aria-label': 'Llega a tu reloj con su nombre' }, 'watch')
        : null,
    ]);
  },
});
</script>
