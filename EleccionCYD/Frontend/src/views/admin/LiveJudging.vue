<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useModelStore } from '../../stores/modelStore';
import { ArrowRightIcon, CheckCircleIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';

const router = useRouter();
const store = useModelStore();

const activeModel = computed(() => {
  return store.selectedModel || 
         store.models.find(m => m.status === 'WALKING') || 
         store.models.find(m => m.status === 'READY') || 
         store.models[0];
});

const vestimenta = ref(5);
const caminata = ref(7);
const postura = ref(8);
const originalidad = ref(6);

const isSubmitting = ref(false);
const submitSuccess = ref(false);

const averageScore = computed(() => {
  return parseFloat(((vestimenta.value + caminata.value + postura.value + originalidad.value) / 4).toFixed(1));
});

watch(activeModel, (newModel) => {
  if (newModel && newModel.scores && newModel.scores.total > 0) {
    vestimenta.value = Math.round(newModel.scores.walk) || 5;
    caminata.value = Math.round(newModel.scores.presence) || 7;
    postura.value = Math.round(newModel.scores.garment) || 8;
    originalidad.value = Math.round(newModel.scores.originality) || 6;
  } else {
    vestimenta.value = 5;
    caminata.value = 7;
    postura.value = 8;
    originalidad.value = 6;
  }
}, { immediate: true });

const handleSubmit = (e) => {
  e.preventDefault();
  isSubmitting.value = true;

  setTimeout(() => {
    isSubmitting.value = false;
    submitSuccess.value = true;
    
    const finalScores = {
      walk: vestimenta.value,
      presence: caminata.value,
      garment: postura.value,
      originality: originalidad.value,
      total: averageScore.value
    };
    
    store.updateScores(activeModel.value.id, finalScores);

    setTimeout(() => {
      submitSuccess.value = false;
      router.push({ name: 'Leaderboard' });
    }, 1500);
  }, 1200);
};

const selectModel = (model) => {
  store.setSelectedModel(model.id);
};
</script>

<template>
  <div v-if="!activeModel" class="flex-1 flex items-center justify-center bg-gray-50 p-12 h-[calc(100vh-80px)]">
    <p class="text-gray-400">No active models found in show roster.</p>
  </div>
  
  <div v-else class="flex-1 flex flex-col md:flex-row overflow-hidden bg-white select-none min-h-[calc(100vh-80px)]">
    <!-- Left Column: Large Runway Photo -->
    <section class="w-full md:w-1/2 h-[40vh] md:h-full relative group overflow-hidden">
      <div 
        class="absolute inset-0 bg-cover bg-center transition-transform duration-[1.5s] ease-out group-hover:scale-105"
        :style="{ backgroundImage: `url('${activeModel.imageUrl}')` }"
      ></div>
      
      <!-- Model Info Overlay -->
      <div class="absolute inset-x-0 bottom-0 p-8 md:p-12 bg-gradient-to-t from-black/80 via-black/45 to-transparent text-white flex flex-col justify-end h-1/2">
        <div class="flex items-center gap-2 mb-3">
          <span class="w-2 h-2 rounded-full bg-red-600 animate-pulse"></span>
          <span class="text-[10px] font-bold tracking-[0.25em] uppercase">Live on Runway</span>
        </div>
        <h2 class="font-serif text-3xl md:text-5xl tracking-tight mb-2 uppercase font-light">
          {{ activeModel.name }}
        </h2>
        <div class="flex flex-wrap gap-x-6 gap-y-1 text-xs opacity-75 font-normal tracking-wide">
          <p class="uppercase">{{ activeModel.look }}</p>
          <p class="text-gray-300">Designer: {{ activeModel.designer }}</p>
        </div>
      </div>
      
      <!-- Contrast Overlay Accent -->
      <div class="absolute inset-0 border border-white/5 pointer-events-none"></div>
    </section>

    <!-- Right Column: Judging Scorecard -->
    <section class="w-full md:w-1/2 bg-[#f9f9f9] md:h-full p-8 md:p-16 overflow-y-auto flex flex-col border-t md:border-t-0 md:border-l border-gray-100">
      <div class="max-w-md mx-auto w-full flex-1 flex flex-col justify-center">
        
        <header class="mb-10 mt-8 md:mt-0">
          <p class="text-[10px] font-bold tracking-[0.25em] text-gray-400 mb-2 uppercase">Session 04</p>
          <h3 class="text-2xl font-light tracking-tight text-black">Judging Scorecard</h3>
          <div class="w-12 h-[1px] bg-black mt-4"></div>
        </header>

        <form @submit.prevent="handleSubmit" class="space-y-10">
          <!-- Slider 1 -->
          <div class="space-y-3">
            <div class="flex justify-between items-end">
              <label class="text-[10px] font-bold tracking-widest text-black uppercase">Vestimenta</label>
              <span class="text-xs font-semibold text-gray-500">{{ vestimenta }}/10</span>
            </div>
            <div class="relative pt-1 custom-slider">
              <input 
                type="range"
                min="1" max="10" step="1"
                v-model.number="vestimenta"
                class="w-full cursor-pointer"
              />
              <div class="flex justify-between text-[9px] font-bold tracking-wider text-gray-400 mt-2">
                <span>1</span>
                <span>10</span>
              </div>
            </div>
          </div>

          <!-- Slider 2 -->
          <div class="space-y-3">
            <div class="flex justify-between items-end">
              <label class="text-[10px] font-bold tracking-widest text-black uppercase">Caminata</label>
              <span class="text-xs font-semibold text-gray-500">{{ caminata }}/10</span>
            </div>
            <div class="relative pt-1 custom-slider">
              <input 
                type="range"
                min="1" max="10" step="1"
                v-model.number="caminata"
                class="w-full cursor-pointer"
              />
              <div class="flex justify-between text-[9px] font-bold tracking-wider text-gray-400 mt-2">
                <span>1</span>
                <span>10</span>
              </div>
            </div>
          </div>

          <!-- Slider 3 -->
          <div class="space-y-3">
            <div class="flex justify-between items-end">
              <label class="text-[10px] font-bold tracking-widest text-black uppercase">Postura/Actitud</label>
              <span class="text-xs font-semibold text-gray-500">{{ postura }}/10</span>
            </div>
            <div class="relative pt-1 custom-slider">
              <input 
                type="range"
                min="1" max="10" step="1"
                v-model.number="postura"
                class="w-full cursor-pointer"
              />
              <div class="flex justify-between text-[9px] font-bold tracking-wider text-gray-400 mt-2">
                <span>1</span>
                <span>10</span>
              </div>
            </div>
          </div>

          <!-- Slider 4 -->
          <div class="space-y-3">
            <div class="flex justify-between items-end">
              <label class="text-[10px] font-bold tracking-widest text-black uppercase">Originalidad</label>
              <span class="text-xs font-semibold text-gray-500">{{ originalidad }}/10</span>
            </div>
            <div class="relative pt-1 custom-slider">
              <input 
                type="range"
                min="1" max="10" step="1"
                v-model.number="originalidad"
                class="w-full cursor-pointer"
              />
              <div class="flex justify-between text-[9px] font-bold tracking-wider text-gray-400 mt-2">
                <span>1</span>
                <span>10</span>
              </div>
            </div>
          </div>

          <!-- Summary Live Calculation -->
          <div class="pt-6 border-t border-gray-200">
            <div class="flex justify-between items-center">
              <p class="text-[10px] font-bold tracking-widest text-gray-400 uppercase">Average Score</p>
              <p class="text-4xl font-light text-black tracking-tight font-sans">
                {{ averageScore.toFixed(1) }}
              </p>
            </div>
          </div>

          <!-- Buttons & Validation -->
          <div class="pt-4">
            <button 
              type="submit"
              :disabled="isSubmitting || submitSuccess"
              class="w-full bg-black text-white py-5 text-xs font-semibold tracking-widest uppercase hover:bg-neutral-800 active:scale-[0.99] transition-all duration-300 rounded-none flex items-center justify-center gap-3 cursor-pointer disabled:bg-neutral-800"
            >
              <ArrowPathIcon v-if="isSubmitting" class="animate-spin w-4 h-4" />
              <template v-else-if="submitSuccess">
                <CheckCircleIcon class="w-4 h-4" />
                Scores Synchronized
              </template>
              <template v-else>
                Submit Final Scores
                <ArrowRightIcon class="w-4 h-4" />
              </template>
            </button>
            <p class="text-[9px] text-center text-gray-400 mt-4 uppercase tracking-wider leading-relaxed">
              Locking scores for look #{{ activeModel.id }}. This action cannot be undone.
            </p>
          </div>
        </form>

        <!-- Model Switcher Carousel -->
        <div class="mt-12 pt-8 border-t border-gray-200 pb-12 md:pb-0">
          <p class="text-[9px] font-bold tracking-widest text-gray-400 uppercase mb-4">Runway Queue</p>
          <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-none">
            <button
              v-for="m in store.models"
              :key="m.id"
              @click="selectModel(m)"
              :class="[
                'flex-shrink-0 flex items-center gap-2.5 px-3 py-2 border transition-all duration-300 cursor-pointer text-left',
                activeModel.id === m.id
                  ? 'border-black bg-white'
                  : 'border-gray-200 bg-transparent hover:border-black'
              ]"
            >
              <img :src="m.imageUrl" class="w-6 h-8 object-cover" alt="" />
              <div>
                <p class="text-[9px] font-bold uppercase text-black truncate max-w-[80px]">{{ m.name }}</p>
                <p class="text-[8px] text-gray-400 uppercase">{{ m.status }}</p>
              </div>
            </button>
          </div>
        </div>

      </div>
    </section>
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
  background: #000000;
}
.custom-slider input[type="range"]::-webkit-slider-thumb {
  height: 12px;
  width: 12px;
  border-radius: 0px;
  background: #000000;
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
  background: #000000;
}
.custom-slider input[type="range"]::-moz-range-thumb {
  height: 12px;
  width: 12px;
  border-radius: 0px;
  background: #000000;
  cursor: pointer;
}
</style>
