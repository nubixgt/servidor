<script setup>
import { reactive, ref } from 'vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import { useModelStore } from '../../stores/modelStore';
import { getRubrics } from '../../utils/rubrics';
import { showError, successToast } from '../../utils/alerts';

const props = defineProps({
  title: { type: String, required: true },
  models: { type: Array, required: true },
  roundKey: { type: String, required: true },
});

const store = useModelStore();

// Coreografía usa los mismos rubros para ambas categorías (a diferencia de Gala).
const rubrics = getRubrics(props.roundKey, props.models[0]?.category);

// Borrador local por participante, para que la tabla responda al instante mientras se escribe
// (el guardado real hacia la API se dispara solo al salir del campo, no en cada tecla).
const drafts = reactive({});
props.models.forEach((model) => {
  const mine = store.myScoreFor(model.id, props.roundKey);
  drafts[model.id] = {};
  rubrics.forEach((r) => {
    drafts[model.id][r.key] = mine ? mine[r.key] : '';
  });
});

const savingIds = reactive(new Set());

function clampScore(rawValue) {
  if (rawValue === '' || rawValue === null) return '';
  const n = Math.round(Number(rawValue));
  if (Number.isNaN(n)) return '';
  return Math.min(10, Math.max(1, n));
}

function totalFor(modelId) {
  const values = rubrics.map((r) => drafts[modelId][r.key]);
  if (!values.every((v) => v !== '' && v !== null)) return 0;
  return values.reduce((a, b) => a + b, 0);
}

function onInput(model, rubricKey, rawValue) {
  drafts[model.id][rubricKey] = clampScore(rawValue);
}

async function onChange(model) {
  const values = rubrics.map((r) => drafts[model.id][r.key]);
  if (!values.every((v) => v !== '' && v !== null)) return; // aún incompleto, no guarda todavía

  const rubricValues = {};
  rubrics.forEach((r) => { rubricValues[r.key] = drafts[model.id][r.key]; });

  savingIds.add(model.id);
  try {
    await store.submitScore(model.id, props.roundKey, rubricValues);
    successToast(`Calificación de ${model.name} guardada`);
  } catch (err) {
    showError('No se pudo guardar', err.response?.data?.message || `Intenta de nuevo con ${model.name}.`);
  } finally {
    savingIds.delete(model.id);
  }
}

const zoomedModel = ref(null);
const openZoom = (model) => { zoomedModel.value = model; };
const closeZoom = () => { zoomedModel.value = null; };
</script>

<template>
  <section class="mb-12">
    <h3 class="text-lg font-light tracking-tight text-white mb-4 uppercase border-b border-white/10 pb-3">{{ title }}</h3>
    <div class="bg-white/5 backdrop-blur-xl overflow-x-auto border border-white/10 rounded-2xl">
      <table class="w-full border-collapse text-left">
        <thead>
          <tr class="border-b border-white/10 bg-white/5">
            <th class="py-4 px-4 text-[10px] font-bold tracking-widest text-white/40 uppercase">Participante</th>
            <th v-for="rubric in rubrics" :key="rubric.key" class="py-4 px-3 text-[10px] font-bold tracking-widest text-white/40 uppercase text-center w-24">{{ rubric.label }}</th>
            <th class="py-4 px-4 text-[10px] font-bold tracking-widest text-white/40 uppercase text-right w-24">Total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="model in models" :key="model.id" class="border-b border-white/5 last:border-b-0">
            <td class="py-4 px-4">
              <div class="flex items-center gap-4">
                <img
                  :src="model.imageUrl"
                  class="w-20 aspect-[3/4] rounded-xl object-cover shrink-0 cursor-zoom-in hover:opacity-80 hover:ring-2 hover:ring-amber-400 transition-all"
                  alt=""
                  @click="openZoom(model)"
                />
                <span class="text-[11px] font-bold tracking-widest text-white uppercase">{{ model.name }}</span>
              </div>
            </td>
            <td v-for="rubric in rubrics" :key="rubric.key" class="py-3 px-3 text-center">
              <input
                type="number"
                min="1" max="10" step="1"
                v-model="drafts[model.id][rubric.key]"
                @input="onInput(model, rubric.key, $event.target.value)"
                @change="onChange(model)"
                placeholder="—"
                class="w-14 bg-white/5 border border-white/15 rounded-lg text-center text-sm text-white py-1.5 focus:outline-none focus:border-amber-400 placeholder:text-white/25"
              />
            </td>
            <td class="py-3 px-4 text-right">
              <span v-if="savingIds.has(model.id)" class="text-[9px] text-white/40 uppercase tracking-widest">Guardando...</span>
              <span v-else :class="['text-xs font-bold tracking-widest', totalFor(model.id) > 0 ? 'text-amber-400' : 'text-white/25']">
                {{ totalFor(model.id) > 0 ? totalFor(model.id) : '—' }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Lightbox: foto ampliada al hacer clic -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="zoomedModel"
          class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-6 z-[200]"
          @click.self="closeZoom"
        >
          <div class="relative max-w-md w-full">
            <button
              @click="closeZoom"
              class="absolute -top-12 right-0 text-white/70 hover:text-white text-xs uppercase tracking-widest flex items-center gap-2 cursor-pointer"
            >
              Cerrar
              <XMarkIcon class="w-5 h-5" />
            </button>
            <img :src="zoomedModel.imageUrl" :alt="zoomedModel.name" class="w-full rounded-2xl shadow-2xl border border-white/15" />
            <p class="text-center text-white text-sm font-bold tracking-widest uppercase mt-4">{{ zoomedModel.name }}</p>
          </div>
        </div>
      </Transition>
    </Teleport>
  </section>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
