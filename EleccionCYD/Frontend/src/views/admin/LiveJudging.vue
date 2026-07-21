<script setup>
import { ref, computed, watch } from 'vue';
import { useModelStore } from '../../stores/modelStore';
import { ArrowRightIcon, CheckCircleIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';
import { CATEGORY_LABELS } from '../../utils/labels';
import { ROUNDS, getRubrics, roundsCompleted } from '../../utils/rubrics';
import GroupScoreTable from '../../components/judging/GroupScoreTable.vue';

const store = useModelStore();

const activeModel = computed(() => store.selectedModel || store.models[0]);
const activeRound = computed(() => store.activeRound);
const isGroupRound = computed(() => activeRound.value === 'COREOGRAFIA');
const activeRubrics = computed(() => getRubrics(activeRound.value, activeModel.value?.category));

// Valores por defecto (escalonados) cuando el rubro aún no tiene calificación.
const DEFAULTS = [5, 7, 8];

const sliderValues = ref({});

const syncSliders = () => {
  const model = activeModel.value;
  if (!model || isGroupRound.value) return;
  const roundScores = model.scores[activeRound.value];
  const values = {};
  activeRubrics.value.forEach((rubric, i) => {
    values[rubric.key] = roundScores.total > 0 ? roundScores[rubric.key] : DEFAULTS[i] ?? 5;
  });
  sliderValues.value = values;
};

watch([activeModel, activeRound], syncSliders, { immediate: true });

const isSubmitting = ref(false);
const submitSuccess = ref(false);

const averageScore = computed(() => {
  const values = activeRubrics.value.map((r) => sliderValues.value[r.key] ?? 0);
  if (values.length === 0) return 0;
  return parseFloat((values.reduce((a, b) => a + b, 0) / values.length).toFixed(1));
});

const selectRound = (roundKey) => {
  store.setActiveRound(roundKey);
};

const handleSubmit = (e) => {
  e.preventDefault();
  isSubmitting.value = true;

  setTimeout(() => {
    isSubmitting.value = false;
    submitSuccess.value = true;

    const roundScores = { total: averageScore.value };
    activeRubrics.value.forEach((r) => {
      roundScores[r.key] = sliderValues.value[r.key];
    });

    store.updateRoundScores(activeModel.value.id, activeRound.value, roundScores);

    setTimeout(() => {
      submitSuccess.value = false;
    }, 1500);
  }, 1200);
};

const selectModel = (model) => {
  store.setSelectedModel(model.id);
};
</script>

<template>
  <div class="min-h-[calc(100vh-80px)] flex flex-col">
    <!-- Round Selector (siempre visible, para poder cambiar de ronda en cualquier momento) -->
    <div class="px-6 md:px-16 pt-8 pb-2">
      <div class="flex items-center gap-2 max-w-xl">
        <button
          v-for="round in ROUNDS"
          :key="round.key"
          @click="selectRound(round.key)"
          :class="[
            'flex-1 px-3 py-2.5 text-[10px] font-bold tracking-widest border transition-all duration-300 cursor-pointer uppercase',
            activeRound === round.key
              ? 'bg-amber-400 text-black border-amber-400'
              : 'bg-white/5 text-white/60 border-white/15 hover:border-white/40 hover:text-white'
          ]"
        >
          {{ round.label }}
        </button>
      </div>
    </div>

    <!-- Coreografía: se baila en grupo, así que se califica a todos de una vez en tabla -->
    <div v-if="isGroupRound" class="px-6 md:px-16 py-8 flex-1">
      <p class="text-white/50 text-sm max-w-xl mb-10">
        La coreografía la bailan todos juntos, así que se califica a todo el grupo de una sola vez. Los cambios se guardan automáticamente.
      </p>
      <GroupScoreTable title="Señoritas" :models="store.senoritas" round-key="COREOGRAFIA" />
      <GroupScoreTable title="Jóvenes" :models="store.jovenes" round-key="COREOGRAFIA" />
    </div>

    <!-- Fashion Show / Gala: se evalúa a cada participante por separado -->
    <div v-else-if="!activeModel" class="flex-1 flex items-center justify-center p-12">
      <p class="text-white/40">No se encontraron participantes activos en el listado.</p>
    </div>

    <div v-else class="flex-1 flex flex-col md:flex-row overflow-hidden select-none">
      <!-- Left Column: Large Runway Photo -->
      <section class="w-full md:w-1/2 h-[40vh] md:h-full relative group overflow-hidden">
        <div
          class="absolute inset-0 bg-cover bg-center transition-transform duration-[1.5s] ease-out group-hover:scale-105"
          :style="{ backgroundImage: `url('${activeModel.imageUrl}')` }"
        ></div>

        <!-- Model Info Overlay -->
        <div class="absolute inset-x-0 bottom-0 p-8 md:p-12 bg-gradient-to-t from-black/90 via-black/50 to-transparent text-white flex flex-col justify-end h-1/2">
          <div class="flex items-center gap-2 mb-3">
            <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
            <span class="text-[10px] font-bold tracking-[0.25em] uppercase">En Vivo</span>
          </div>
          <h2 class="font-serif text-3xl md:text-5xl tracking-tight mb-2 uppercase font-light">
            {{ activeModel.name }}
          </h2>
          <div class="flex flex-wrap gap-x-6 gap-y-1 text-xs opacity-90 font-normal tracking-wide">
            <p class="uppercase">{{ CATEGORY_LABELS[activeModel.category] }}</p>
            <p class="text-amber-400">{{ roundsCompleted(activeModel) }}/{{ ROUNDS.length }} rondas calificadas</p>
          </div>
        </div>

        <!-- Contrast Overlay Accent -->
        <div class="absolute inset-0 border border-white/5 pointer-events-none"></div>
      </section>

      <!-- Right Column: Judging Scorecard -->
      <section class="w-full md:w-1/2 bg-black/30 backdrop-blur-xl md:h-full p-8 md:p-16 overflow-y-auto flex flex-col border-t md:border-t-0 md:border-l border-white/10">
        <div class="max-w-md mx-auto w-full flex-1 flex flex-col justify-center">

          <header class="mb-8">
            <p class="text-[10px] font-bold tracking-[0.25em] text-white/50 mb-2 uppercase">Hoja de Calificación</p>
            <h3 class="text-2xl font-light tracking-tight text-white">{{ activeModel.name }}</h3>
            <div class="w-12 h-[1px] bg-amber-400 mt-4"></div>
          </header>

          <form @submit.prevent="handleSubmit" class="space-y-10" :key="activeRound">
            <div v-for="rubric in activeRubrics" :key="rubric.key" class="space-y-3">
              <div class="flex justify-between items-end">
                <label class="text-[10px] font-bold tracking-widest text-white uppercase">{{ rubric.label }}</label>
                <span class="text-xs font-semibold text-amber-400">{{ sliderValues[rubric.key] }}/10</span>
              </div>
              <div class="relative pt-1 custom-slider">
                <input
                  type="range"
                  min="1" max="10" step="1"
                  v-model.number="sliderValues[rubric.key]"
                  class="w-full cursor-pointer"
                />
                <div class="flex justify-between text-[9px] font-bold tracking-wider text-white/40 mt-2">
                  <span>1</span>
                  <span>10</span>
                </div>
              </div>
            </div>

            <!-- Summary Live Calculation -->
            <div class="pt-6 border-t border-white/10">
              <div class="flex justify-between items-center">
                <p class="text-[10px] font-bold tracking-widest text-white/50 uppercase">Puntaje de la Ronda</p>
                <p class="text-4xl font-light text-amber-400 tracking-tight font-sans">
                  {{ averageScore.toFixed(1) }}
                </p>
              </div>
            </div>

            <!-- Buttons & Validation -->
            <div class="pt-4">
              <button
                type="submit"
                :disabled="isSubmitting || submitSuccess"
                class="w-full bg-amber-400 text-black py-5 text-xs font-semibold tracking-widest uppercase hover:bg-amber-300 active:scale-[0.99] transition-all duration-300 rounded-xl flex items-center justify-center gap-3 cursor-pointer disabled:opacity-70"
              >
                <ArrowPathIcon v-if="isSubmitting" class="animate-spin w-4 h-4" />
                <template v-else-if="submitSuccess">
                  <CheckCircleIcon class="w-4 h-4" />
                  Calificación Sincronizada
                </template>
                <template v-else>
                  Guardar Calificación
                  <ArrowRightIcon class="w-4 h-4" />
                </template>
              </button>
              <p class="text-[9px] text-center text-white/40 mt-4 uppercase tracking-wider leading-relaxed">
                Guardando la ronda "{{ ROUNDS.find(r => r.key === activeRound)?.label }}" para {{ activeModel.id }}.
              </p>
            </div>
          </form>

          <!-- Model Switcher Carousel -->
          <div class="mt-12 pt-8 border-t border-white/10 pb-12 md:pb-0">
            <p class="text-[9px] font-bold tracking-widest text-white/50 uppercase mb-4">Participantes</p>
            <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-none">
              <button
                v-for="m in store.models"
                :key="m.id"
                @click="selectModel(m)"
                :class="[
                  'flex-shrink-0 flex items-center gap-2.5 px-3 py-2 border transition-all duration-300 cursor-pointer text-left',
                  activeModel.id === m.id
                    ? 'border-amber-400 bg-white/10'
                    : 'border-white/15 bg-transparent hover:border-white/40'
                ]"
              >
                <img :src="m.imageUrl" class="w-6 h-8 object-cover rounded" alt="" />
                <div>
                  <p class="text-[9px] font-bold uppercase text-white truncate max-w-[80px]">{{ m.name }}</p>
                  <p class="text-[8px] text-white/40 uppercase">{{ roundsCompleted(m) }}/{{ ROUNDS.length }} rondas</p>
                </div>
              </button>
            </div>
          </div>

        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
/* Slider Range Custom Styles directly to support high-end minimalist design */
.custom-slider input[type="range"] {
  -webkit-appearance: none;
  width: 100%;
  background: transparent;
}
.custom-slider input[type="range"]:focus {
  outline: none;
}
.custom-slider input[type="range"]::-webkit-slider-runnable-track {
  width: 100%;
  height: 1px;
  cursor: pointer;
  background: rgba(255, 255, 255, 0.25);
}
.custom-slider input[type="range"]::-webkit-slider-thumb {
  height: 12px;
  width: 12px;
  border-radius: 999px;
  background: #fbbf24;
  cursor: pointer;
  -webkit-appearance: none;
  margin-top: -5px;
  transition: transform 0.15s ease;
}
.custom-slider input[type="range"]::-webkit-slider-thumb:hover {
  transform: scale(1.2);
}
.custom-slider input[type="range"]::-moz-range-track {
  width: 100%;
  height: 1px;
  cursor: pointer;
  background: rgba(255, 255, 255, 0.25);
}
.custom-slider input[type="range"]::-moz-range-thumb {
  height: 12px;
  width: 12px;
  border-radius: 999px;
  border: none;
  background: #fbbf24;
  cursor: pointer;
}
</style>
