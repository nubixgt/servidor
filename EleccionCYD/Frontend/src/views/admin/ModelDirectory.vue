<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useModelStore } from '../../stores/modelStore';
import { Bars3BottomLeftIcon, ArrowRightIcon, CheckIcon, GlobeAltIcon, ShareIcon } from '@heroicons/vue/24/outline';

const router = useRouter();
const store = useModelStore();

const activeFilter = ref('ALL');
const sortBy = ref('name');

const filteredModels = computed(() => {
  if (activeFilter.value === 'ALL') return store.models;
  return store.models.filter(model => model.status === activeFilter.value);
});

const sortedModels = computed(() => {
  return [...filteredModels.value].sort((a, b) => {
    if (sortBy.value === 'name') return a.name.localeCompare(b.name);
    return a.status.localeCompare(b.status);
  });
});

const toggleSort = () => {
  sortBy.value = sortBy.value === 'name' ? 'status' : 'name';
};

const navigateTo = (route, modelId = null) => {
  if (modelId) {
    store.setSelectedModel(modelId);
  }
  router.push({ name: route });
};
</script>

<template>
  <div class="bg-[#f9f9f9] text-[#1a1c1c] min-h-screen">
    <main class="max-w-[1440px] mx-auto px-6 md:px-16 py-12">
      <!-- Header Section -->
      <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-8">
        <div class="space-y-3">
          <h1 class="text-3xl md:text-4xl font-light tracking-tight text-black">Model Directory</h1>
          <p class="text-gray-500 text-sm max-w-md leading-relaxed">
            Official lineup for the Paris Collection 2024. Live status tracking and runway assignments.
          </p>
        </div>

        <!-- Filters & Sorting -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 md:pb-0 scrollbar-none shrink-0">
          <button
            v-for="filter in ['ALL', 'READY', 'WALKING', 'JUDGED']"
            :key="filter"
            @click="activeFilter = filter"
            :class="[
              'px-5 py-2.5 text-[10px] font-bold tracking-widest border transition-all duration-300 cursor-pointer',
              activeFilter === filter
                ? 'bg-black text-white border-black'
                : 'bg-transparent text-gray-400 border-gray-200 hover:border-black hover:text-black'
            ]"
          >
            {{ filter }}
          </button>
          <div class="w-[1px] h-6 bg-gray-200 mx-2 hidden sm:block"></div>
          <button 
            @click="toggleSort"
            class="flex items-center gap-2 text-[10px] font-bold tracking-widest text-gray-500 hover:text-black cursor-pointer uppercase py-2 px-3"
          >
            <Bars3BottomLeftIcon class="w-4 h-4" />
            Sort: {{ sortBy }}
          </button>
        </div>
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
            <div class="aspect-[3/4] relative overflow-hidden bg-neutral-100">
              <img 
                :src="model.imageUrl" 
                :alt="model.name"
                loading="lazy"
                class="w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
              />
              <div class="absolute inset-0 border border-black opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
              
              <!-- Status Indicator -->
              <div v-if="model.status === 'WALKING'" class="absolute top-4 left-4 flex items-center gap-2 bg-white/95 backdrop-blur px-3 py-1.5 shadow-sm border border-neutral-100">
                <span class="w-1.5 h-1.5 rounded-full bg-black animate-pulse"></span>
                <span class="text-[9px] font-bold tracking-widest uppercase text-black">WALKING</span>
              </div>
              <div v-else-if="model.status === 'READY'" class="absolute top-4 left-4 flex items-center gap-2 bg-white/95 backdrop-blur px-3 py-1.5 shadow-sm border border-neutral-100">
                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                <span class="text-[9px] font-bold tracking-widest uppercase text-gray-500">READY</span>
              </div>
              <div v-else-if="model.status === 'JUDGED'" class="absolute top-4 left-4 flex items-center gap-1 bg-white/95 backdrop-blur px-2.5 py-1.5 shadow-sm border border-neutral-100">
                <CheckIcon class="w-3 h-3 text-black stroke-[3]" />
                <span class="text-[9px] font-bold tracking-widest uppercase text-black">JUDGED</span>
              </div>
            </div>

            <!-- Meta details -->
            <div class="space-y-1">
              <div class="flex justify-between items-start">
                <h3 class="text-[11px] font-bold tracking-widest uppercase text-black">{{ model.name }}</h3>
                <span v-if="model.scores.total > 0" class="text-[10px] font-bold text-gray-500">★ {{ model.scores.total.toFixed(2) }}</span>
              </div>
              <p class="text-xs text-gray-400 italic font-light">{{ model.look }}</p>
            </div>
          </div>
        </TransitionGroup>
      </div>

      <!-- Empty State -->
      <div v-if="sortedModels.length === 0" class="py-24 text-center border border-dashed border-gray-200 mt-8">
        <p class="text-sm text-gray-400">No models found matching the active status filter.</p>
      </div>

      <!-- Load More Section -->
      <div class="mt-20 flex justify-center">
        <button 
          @click="() => alert('Full roster is active. Custom judge assignments can be configured in your system settings.')"
          class="flex items-center gap-4 px-12 py-4 border border-black text-[10px] font-bold tracking-widest text-black hover:bg-black hover:text-white transition-all duration-300 rounded-none cursor-pointer"
        >
          VIEW FULL ROSTER
          <ArrowRightIcon class="w-4 h-4" />
        </button>
      </div>
    </main>

    <!-- Footer Decoration -->
    <footer class="mt-24 border-t border-gray-200 py-16 bg-white">
      <div class="max-w-[1440px] mx-auto px-6 md:px-16 flex flex-col md:flex-row justify-between items-start gap-12">
        <div class="space-y-4">
          <div class="font-serif text-lg tracking-[0.2em] text-black font-normal uppercase">AURA</div>
          <p class="text-[10px] text-gray-400 tracking-[0.25em] uppercase">Paris Collection 2024</p>
        </div>
        
        <div class="grid grid-cols-2 gap-16">
          <div class="flex flex-col gap-2.5">
            <span class="text-[10px] font-bold tracking-widest text-gray-400 mb-2 uppercase">PAGES</span>
            <button @click="navigateTo('ModelDirectory')" class="text-xs text-black font-semibold text-left hover:opacity-75 cursor-pointer">Directory</button>
            <button @click="navigateTo('LiveJudging')" class="text-xs text-black font-semibold text-left hover:opacity-75 cursor-pointer">Live Show</button>
            <button @click="() => alert('Press kit downloads will open shortly.')" class="text-xs text-black font-semibold text-left hover:opacity-75 cursor-pointer">Press Kit</button>
          </div>
          
          <div class="flex flex-col gap-2.5">
            <span class="text-[10px] font-bold tracking-widest text-gray-400 mb-2 uppercase">SYSTEM</span>
            <button @click="() => alert('Legal guidelines for judges.')" class="text-xs text-black font-semibold text-left hover:opacity-75 cursor-pointer">Legal</button>
            <button @click="() => alert('Privacy declaration.')" class="text-xs text-black font-semibold text-left hover:opacity-75 cursor-pointer">Privacy</button>
            <button @click="() => alert('Judiciary endpoint APIs.')" class="text-xs text-black font-semibold text-left hover:opacity-75 cursor-pointer">API</button>
          </div>
        </div>
      </div>

      <div class="max-w-[1440px] mx-auto px-6 md:px-16 mt-12 pt-8 border-t border-gray-100 flex justify-between items-center text-gray-400">
        <span class="text-[9px] tracking-widest uppercase font-semibold">© 2024 AURA FASHION GROUP</span>
        <div class="flex gap-4">
          <button class="hover:text-black transition-colors" title="Language"><GlobeAltIcon class="w-4 h-4 stroke-[1.5]" /></button>
          <button class="hover:text-black transition-colors" title="Share portal"><ShareIcon class="w-4 h-4 stroke-[1.5]" /></button>
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
