<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useModelStore } from '../../stores/modelStore';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';

const router = useRouter();
const store = useModelStore();

const rankedModels = computed(() => store.rankedModels);

const podium1 = computed(() => rankedModels.value[0]);
const podium2 = computed(() => rankedModels.value[1]);
const podium3 = computed(() => rankedModels.value[2]);

const selectModel = (model) => {
  store.setSelectedModel(model.id);
  router.push({ name: 'LiveJudging' });
};
</script>

<template>
  <div class="flex-grow px-6 md:px-16 py-12 max-w-5xl mx-auto w-full bg-[#f9f9f9]">
    
    <!-- Page Title -->
    <section class="mb-16">
      <h1 class="text-3xl font-light tracking-tight text-black mb-2 uppercase">Live Ranking</h1>
      <p class="text-sm text-gray-400 leading-relaxed max-w-2xl">
        Official live leaderboard for the Paris Collection 2024. Rankings are updated in real-time as judging panels submit their final evaluations.
      </p>
    </section>

    <!-- Top 3 Featured Podium Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16 items-end">
      <!-- Silver - Rank 2 -->
      <Transition appear name="fade-up" style="transition-delay: 0.1s">
        <div 
          v-if="podium2"
          @click="selectModel(podium2)"
          class="order-2 md:order-1 bg-white p-6 border-b-2 border-gray-300 relative flex flex-col items-center text-center cursor-pointer hover:shadow-md transition-all rounded-none"
        >
          <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-gray-100 text-black px-3 py-1 text-[9px] font-bold tracking-widest uppercase">
            RANK 02
          </div>
          <div class="w-24 h-24 rounded-full overflow-hidden border-2 border-gray-100 mb-4 bg-neutral-50 shadow-sm">
            <img 
              class="w-full h-full object-cover" 
              :alt="podium2.name" 
              :src="podium2.podiumUrl || podium2.imageUrl"
            />
          </div>
          <h3 class="text-xs font-bold tracking-widest text-black mb-1 uppercase">{{ podium2.name }}</h3>
          <p class="text-gray-400 text-xs italic font-light">{{ podium2.look }}</p>
          <p class="text-black font-semibold text-xs mt-2 uppercase tracking-wide">Score: {{ podium2.scores.total.toFixed(2) }}</p>
        </div>
      </Transition>

      <!-- Gold - Rank 1 (Winner) -->
      <Transition appear name="fade-up" style="transition-delay: 0.2s">
        <div 
          v-if="podium1"
          @click="selectModel(podium1)"
          class="order-1 md:order-2 bg-white p-8 border-b-4 border-black relative flex flex-col items-center text-center shadow-lg transform md:-translate-y-2 scale-105 cursor-pointer rounded-none z-10"
        >
          <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-black text-white px-4 py-1 text-[9px] font-bold tracking-widest uppercase">
            WINNER
          </div>
          <div class="w-28 h-28 rounded-full overflow-hidden border-4 border-black mb-4 bg-neutral-50 shadow-sm">
            <img 
              class="w-full h-full object-cover" 
              :alt="podium1.name" 
              :src="podium1.podiumUrl || podium1.imageUrl"
            />
          </div>
          <h3 class="text-sm font-extrabold tracking-widest text-black mb-1 uppercase">{{ podium1.name }}</h3>
          <p class="text-gray-400 text-xs italic font-light">{{ podium1.look }}</p>
          <p class="text-black font-extrabold text-sm mt-3 uppercase tracking-wider">Score: {{ podium1.scores.total.toFixed(2) }}</p>
        </div>
      </Transition>

      <!-- Bronze - Rank 3 -->
      <Transition appear name="fade-up" style="transition-delay: 0.3s">
        <div 
          v-if="podium3"
          @click="selectModel(podium3)"
          class="order-3 md:order-3 bg-white p-6 border-b-2 border-gray-200 relative flex flex-col items-center text-center cursor-pointer hover:shadow-md transition-all rounded-none"
        >
          <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-gray-50 text-gray-500 px-3 py-1 text-[9px] font-bold tracking-widest uppercase">
            RANK 03
          </div>
          <div class="w-24 h-24 rounded-full overflow-hidden border-2 border-gray-100 mb-4 bg-neutral-50 shadow-sm">
            <img 
              class="w-full h-full object-cover" 
              :alt="podium3.name" 
              :src="podium3.podiumUrl || podium3.imageUrl"
            />
          </div>
          <h3 class="text-xs font-bold tracking-widest text-black mb-1 uppercase">{{ podium3.name }}</h3>
          <p class="text-gray-400 text-xs italic font-light">{{ podium3.look }}</p>
          <p class="text-black font-semibold text-xs mt-2 uppercase tracking-wide">Score: {{ podium3.scores.total.toFixed(2) }}</p>
        </div>
      </Transition>
    </div>

    <!-- Detailed Table -->
    <div class="bg-white overflow-x-auto border border-gray-100 shadow-[0_0_30px_rgba(0,0,0,0.015)] rounded-none mb-12">
      <table class="w-full border-collapse text-left">
        <thead>
          <tr class="border-b border-gray-200 bg-gray-50/50">
            <th class="py-5 px-6 text-[10px] font-bold tracking-widest text-gray-400 uppercase w-20 text-center">Rank</th>
            <th class="py-5 px-4 text-[10px] font-bold tracking-widest text-gray-400 uppercase">Model</th>
            <th class="py-5 px-4 text-[10px] font-bold tracking-widest text-gray-400 uppercase text-center">Walk</th>
            <th class="py-5 px-4 text-[10px] font-bold tracking-widest text-gray-400 uppercase text-center">Presence</th>
            <th class="py-5 px-4 text-[10px] font-bold tracking-widest text-gray-400 uppercase text-center">Garment</th>
            <th class="py-5 px-6 text-[10px] font-bold tracking-widest text-gray-400 uppercase text-right w-28">Total</th>
          </tr>
        </thead>
        <tbody>
          <tr 
            v-for="(model, idx) in rankedModels"
            :key="model.id"
            @click="selectModel(model)"
            :class="[
              'border-b border-gray-100 transition-all duration-300 hover:bg-neutral-50/50 hover:translate-x-1 cursor-pointer',
              idx === 0 && model.scores.total > 0 ? 'border-l-[3px] border-black' : '',
              idx === 1 && model.scores.total > 0 ? 'border-l-[3px] border-neutral-400' : '',
              idx === 2 && model.scores.total > 0 ? 'border-l-[3px] border-neutral-200' : ''
            ]"
          >
            <!-- Rank -->
            <td class="py-5 px-6 text-center">
              <span :class="`text-[11px] font-bold tracking-widest ${model.scores.total > 0 ? 'text-black' : 'text-gray-300'}`">
                {{ String(idx + 1).padStart(2, '0') }}
              </span>
            </td>

            <!-- Model -->
            <td class="py-4 px-4">
              <div class="flex items-center gap-4">
                <div class="w-11 h-11 bg-neutral-100 overflow-hidden shrink-0">
                  <img 
                    class="w-full h-full object-cover" 
                    alt="" 
                    :src="model.avatarUrl || model.imageUrl" 
                  />
                </div>
                <div>
                  <span class="text-[11px] font-bold tracking-widest text-black uppercase block">
                    {{ model.name }}
                  </span>
                  <span class="text-[9px] text-gray-400 block truncate max-w-[150px] italic font-light">
                    {{ model.look }}
                  </span>
                </div>
              </div>
            </td>

            <!-- Scores columns -->
            <td class="py-5 px-4 text-center text-xs text-gray-500 font-medium">
              {{ model.scores.total > 0 ? model.scores.walk.toFixed(1) : '—' }}
            </td>
            <td class="py-5 px-4 text-center text-xs text-gray-500 font-medium">
              {{ model.scores.total > 0 ? model.scores.presence.toFixed(1) : '—' }}
            </td>
            <td class="py-5 px-4 text-center text-xs text-gray-500 font-medium">
              {{ model.scores.total > 0 ? model.scores.garment.toFixed(1) : '—' }}
            </td>

            <!-- Total -->
            <td class="py-5 px-6 text-right">
              <span :class="`text-xs font-bold tracking-widest ${model.scores.total > 0 ? 'text-black' : 'text-gray-300'}`">
                {{ model.scores.total > 0 ? model.scores.total.toFixed(2) : 'PENDING' }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Footer Pagination Status -->
    <div class="flex justify-between items-center text-gray-400 select-none">
      <div class="flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-black animate-pulse"></span>
        <span class="text-[9px] font-bold tracking-widest uppercase">Live Data Stream Active</span>
      </div>
      <div class="flex gap-3">
        <button class="p-2 border border-gray-200 hover:border-black hover:text-black transition-colors rounded-none cursor-pointer">
          <ChevronLeftIcon class="w-4 h-4 stroke-[1.5]" />
        </button>
        <button class="p-2 border border-gray-200 hover:border-black hover:text-black transition-colors rounded-none cursor-pointer">
          <ChevronRightIcon class="w-4 h-4 stroke-[1.5]" />
        </button>
      </div>
    </div>

  </div>
</template>

<style scoped>
.fade-up-enter-active {
  transition: all 0.6s ease;
}
.fade-up-enter-from {
  opacity: 0;
  transform: translateY(15px);
}
.fade-up-enter-to {
  opacity: 1;
  transform: translateY(0);
}
</style>
