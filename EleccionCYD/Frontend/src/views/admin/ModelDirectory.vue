<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useModelStore } from '../../stores/modelStore';
import { Bars3BottomLeftIcon, ArrowRightIcon, GlobeAltIcon, ShareIcon, UserGroupIcon } from '@heroicons/vue/24/outline';
import { FILTER_LABELS, SORT_LABELS, CATEGORY_LABELS, CATEGORY_COLORS } from '../../utils/labels';
import { ROUNDS } from '../../utils/rubrics';
import logo from '../../assets/images/Logo.png';

const router = useRouter();
const store = useModelStore();

const activeFilter = ref('ALL');
const sortBy = ref('name');

const filteredModels = computed(() => {
  if (activeFilter.value === 'ALL') return store.participants;
  return store.participants.filter((model) => model.category === activeFilter.value);
});

const sortedModels = computed(() => {
  return [...filteredModels.value].sort((a, b) => {
    if (sortBy.value === 'name') return a.name.localeCompare(b.name);
    return a.category.localeCompare(b.category);
  });
});

const toggleSort = () => {
  sortBy.value = sortBy.value === 'name' ? 'category' : 'name';
};

const navigateTo = (route, modelId = null) => {
  if (modelId) {
    store.setSelectedModel(modelId);
  }
  router.push({ name: route });
};
</script>

<template>
  <div class="text-white min-h-screen">
    <main class="max-w-[1440px] mx-auto px-6 md:px-16 py-12">
      <!-- Header Section -->
      <div class="flex flex-col md:flex-row md:items-start justify-between mb-12 gap-8">
        <div class="space-y-3">
          <h1 class="text-3xl md:text-4xl font-light tracking-tight text-white">Directorio de <span class="text-amber-400 font-normal">Participantes</span></h1>
          <p class="text-white/50 text-sm max-w-md leading-relaxed">
            Listado oficial de candidatas y candidatos de EleccionCYD. Seguimiento en vivo de las rondas calificadas.
          </p>
        </div>

        <img :src="logo" alt="Aniversario 34 CYD" class="h-20 md:h-24 w-auto opacity-90 shrink-0" />
      </div>

      <!-- Filters & Sorting -->
      <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-10 scrollbar-none shrink-0">
        <button
          v-for="filter in ['ALL', 'SENORITA', 'JOVEN']"
          :key="filter"
          @click="activeFilter = filter"
          :class="[
            'flex items-center gap-2 px-5 py-2.5 text-[10px] font-bold tracking-widest border transition-all duration-300 cursor-pointer uppercase',
            activeFilter === filter
              ? 'bg-amber-400 text-black border-amber-400'
              : 'bg-white/5 text-white/60 border-white/15 hover:border-white/40 hover:text-white'
          ]"
        >
          <UserGroupIcon v-if="filter === 'ALL'" class="w-3.5 h-3.5" />
          {{ FILTER_LABELS[filter] }}
        </button>
        <div class="w-[1px] h-6 bg-white/15 mx-2 hidden sm:block"></div>
        <button
          @click="toggleSort"
          class="flex items-center gap-2 text-[10px] font-bold tracking-widest text-white/60 hover:text-white cursor-pointer uppercase py-2 px-3 bg-white/5 border border-white/15"
        >
          <Bars3BottomLeftIcon class="w-4 h-4" />
          Ordenar: {{ SORT_LABELS[sortBy] }}
        </button>
      </div>

      <!-- Grid of Model Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
        <TransitionGroup name="list">
          <div
            v-for="model in sortedModels"
            :key="model.id"
            @click="navigateTo('LiveJudging', model.id)"
            class="group relative flex flex-col gap-4 cursor-pointer select-none transition-all duration-300"
          >
            <!-- Image Container -->
            <div class="aspect-[3/4] relative overflow-hidden bg-white/5 rounded-xl">
              <img
                :src="model.imageUrl"
                :alt="model.name"
                loading="lazy"
                class="w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
              />
              <div class="absolute inset-0 border border-amber-400/0 group-hover:border-amber-400/60 transition-colors duration-300 pointer-events-none rounded-xl"></div>

              <!-- Rounds progress indicator (de ESTE jurado) -->
              <div class="absolute top-4 left-4 flex items-center gap-2 bg-black/60 backdrop-blur px-3 py-1.5 rounded-lg border border-white/10">
                <span
                  :class="[
                    'w-1.5 h-1.5 rounded-full',
                    store.roundsCompletedFor(model.id) === ROUNDS.length ? 'bg-amber-400' : store.roundsCompletedFor(model.id) > 0 ? 'bg-amber-400 animate-pulse' : 'bg-white/30'
                  ]"
                ></span>
                <span class="text-[9px] font-bold tracking-widest uppercase text-white">{{ store.roundsCompletedFor(model.id) }}/{{ ROUNDS.length }} RONDAS</span>
              </div>
            </div>

            <!-- Meta details -->
            <div class="space-y-1">
              <h3 class="text-[11px] font-bold tracking-widest uppercase text-white">{{ model.name }}</h3>
              <p :class="['text-xs italic font-light', CATEGORY_COLORS[model.category]]">{{ CATEGORY_LABELS[model.category] }}</p>
            </div>
          </div>
        </TransitionGroup>
      </div>

      <!-- Empty State -->
      <div v-if="sortedModels.length === 0" class="py-24 text-center border border-dashed border-white/15 mt-8">
        <p class="text-sm text-white/40">No se encontraron participantes que coincidan con el filtro activo.</p>
      </div>

      <!-- Load More Section -->
      <div class="mt-20 flex justify-center">
        <button
          @click="() => alert('El listado completo está activo. Las asignaciones personalizadas de jurado se pueden configurar en la configuración del sistema.')"
          class="flex items-center gap-4 px-12 py-4 border border-amber-400/50 text-[10px] font-bold tracking-widest text-amber-400 hover:bg-amber-400 hover:text-black transition-all duration-300 rounded-xl cursor-pointer"
        >
          VER LISTADO COMPLETO
          <ArrowRightIcon class="w-4 h-4" />
        </button>
      </div>
    </main>

    <!-- Footer Decoration -->
    <footer class="mt-24 border-t border-white/10 py-16">
      <div class="max-w-[1440px] mx-auto px-6 md:px-16 flex flex-col md:flex-row justify-between items-start gap-12">
        <div class="space-y-4">
          <div class="font-serif text-lg tracking-[0.2em] text-white font-normal uppercase">EleccionCYD</div>
          <p class="text-[10px] text-white/40 tracking-[0.25em] uppercase">Edición 2026</p>
        </div>

        <div class="grid grid-cols-2 gap-16">
          <div class="flex flex-col gap-2.5">
            <span class="text-[10px] font-bold tracking-widest text-white/40 mb-2 uppercase">Páginas</span>
            <button @click="navigateTo('ModelDirectory')" class="text-xs text-white font-semibold text-left hover:text-amber-400 cursor-pointer">Directorio</button>
            <button @click="navigateTo('LiveJudging')" class="text-xs text-white font-semibold text-left hover:text-amber-400 cursor-pointer">Show en Vivo</button>
            <button @click="() => alert('Las descargas del kit de prensa se abrirán en breve.')" class="text-xs text-white font-semibold text-left hover:text-amber-400 cursor-pointer">Kit de Prensa</button>
          </div>

          <div class="flex flex-col gap-2.5">
            <span class="text-[10px] font-bold tracking-widest text-white/40 mb-2 uppercase">Sistema</span>
            <button @click="() => alert('Lineamientos legales para jurados.')" class="text-xs text-white font-semibold text-left hover:text-amber-400 cursor-pointer">Legal</button>
            <button @click="() => alert('Declaración de privacidad.')" class="text-xs text-white font-semibold text-left hover:text-amber-400 cursor-pointer">Privacidad</button>
            <button @click="() => alert('APIs del sistema de evaluación.')" class="text-xs text-white font-semibold text-left hover:text-amber-400 cursor-pointer">API</button>
          </div>
        </div>
      </div>

      <div class="max-w-[1440px] mx-auto px-6 md:px-16 mt-12 pt-8 border-t border-white/10 flex justify-between items-center text-white/40">
        <span class="text-[9px] tracking-widest uppercase font-semibold">© 2026 EleccionCYD</span>
        <div class="flex gap-4">
          <button class="hover:text-amber-400 transition-colors" title="Idioma"><GlobeAltIcon class="w-4 h-4 stroke-[1.5]" /></button>
          <button class="hover:text-amber-400 transition-colors" title="Compartir portal"><ShareIcon class="w-4 h-4 stroke-[1.5]" /></button>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.5s ease;
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateY(15px);
}
</style>
