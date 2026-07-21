<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useModelStore } from '../../stores/modelStore';
import { ROUNDS, computeFinalScore } from '../../utils/rubrics';

const props = defineProps({
  title: { type: String, required: true },
  models: { type: Array, required: true },
});

const router = useRouter();
const store = useModelStore();

const podium1 = computed(() => props.models[0]);
const podium2 = computed(() => props.models[1]);
const podium3 = computed(() => props.models[2]);

const selectModel = (model) => {
  store.setSelectedModel(model.id);
  router.push({ name: 'LiveJudging' });
};
</script>

<template>
  <section class="mb-20">
    <h2 class="text-xl font-light tracking-tight text-white mb-8 uppercase border-b border-white/10 pb-4">{{ title }}</h2>

    <!-- Top 3 Featured Podium Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12 items-end">
      <!-- Silver - Rank 2 -->
      <Transition appear name="fade-up" style="transition-delay: 0.1s">
        <div
          v-if="podium2"
          @click="selectModel(podium2)"
          class="order-2 md:order-1 bg-white/5 backdrop-blur-xl p-6 border border-white/10 border-b-2 border-b-slate-300 relative flex flex-col items-center text-center cursor-pointer hover:bg-white/10 transition-all rounded-2xl"
        >
          <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-slate-300 text-black px-3 py-1 text-[9px] font-bold tracking-widest uppercase rounded-md">
            PUESTO 02
          </div>
          <div class="w-24 h-24 rounded-full overflow-hidden border-2 border-white/20 mb-4 bg-white/5 shadow-sm">
            <img
              class="w-full h-full object-cover"
              :alt="podium2.name"
              :src="podium2.podiumUrl || podium2.imageUrl"
            />
          </div>
          <h3 class="text-xs font-bold tracking-widest text-white mb-1 uppercase">{{ podium2.name }}</h3>
          <p class="text-amber-400 font-semibold text-xs mt-2 uppercase tracking-wide">Puntaje: {{ computeFinalScore(podium2).toFixed(2) }}</p>
        </div>
      </Transition>

      <!-- Gold - Rank 1 (Winner) -->
      <Transition appear name="fade-up" style="transition-delay: 0.2s">
        <div
          v-if="podium1"
          @click="selectModel(podium1)"
          class="order-1 md:order-2 bg-white/10 backdrop-blur-xl p-8 border border-amber-400/40 border-b-4 border-b-amber-400 relative flex flex-col items-center text-center shadow-lg transform md:-translate-y-2 scale-105 cursor-pointer rounded-2xl z-10"
        >
          <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-amber-400 text-black px-4 py-1 text-[9px] font-bold tracking-widest uppercase rounded-md">
            GANADOR/A
          </div>
          <div class="w-28 h-28 rounded-full overflow-hidden border-4 border-amber-400 mb-4 bg-white/5 shadow-sm">
            <img
              class="w-full h-full object-cover"
              :alt="podium1.name"
              :src="podium1.podiumUrl || podium1.imageUrl"
            />
          </div>
          <h3 class="text-sm font-extrabold tracking-widest text-white mb-1 uppercase">{{ podium1.name }}</h3>
          <p class="text-amber-400 font-extrabold text-sm mt-3 uppercase tracking-wider">Puntaje: {{ computeFinalScore(podium1).toFixed(2) }}</p>
        </div>
      </Transition>

      <!-- Bronze - Rank 3 -->
      <Transition appear name="fade-up" style="transition-delay: 0.3s">
        <div
          v-if="podium3"
          @click="selectModel(podium3)"
          class="order-3 md:order-3 bg-white/5 backdrop-blur-xl p-6 border border-white/10 border-b-2 border-b-orange-700/60 relative flex flex-col items-center text-center cursor-pointer hover:bg-white/10 transition-all rounded-2xl"
        >
          <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-orange-700/70 text-white px-3 py-1 text-[9px] font-bold tracking-widest uppercase rounded-md">
            PUESTO 03
          </div>
          <div class="w-24 h-24 rounded-full overflow-hidden border-2 border-white/20 mb-4 bg-white/5 shadow-sm">
            <img
              class="w-full h-full object-cover"
              :alt="podium3.name"
              :src="podium3.podiumUrl || podium3.imageUrl"
            />
          </div>
          <h3 class="text-xs font-bold tracking-widest text-white mb-1 uppercase">{{ podium3.name }}</h3>
          <p class="text-amber-400 font-semibold text-xs mt-2 uppercase tracking-wide">Puntaje: {{ computeFinalScore(podium3).toFixed(2) }}</p>
        </div>
      </Transition>
    </div>

    <!-- Detailed Table -->
    <div class="bg-white/5 backdrop-blur-xl overflow-x-auto border border-white/10 rounded-2xl">
      <table class="w-full border-collapse text-left">
        <thead>
          <tr class="border-b border-white/10 bg-white/5">
            <th class="py-5 px-6 text-[10px] font-bold tracking-widest text-white/40 uppercase w-20 text-center">Puesto</th>
            <th class="py-5 px-4 text-[10px] font-bold tracking-widest text-white/40 uppercase">Participante</th>
            <th v-for="round in ROUNDS" :key="round.key" class="py-5 px-4 text-[10px] font-bold tracking-widest text-white/40 uppercase text-center">{{ round.label }}</th>
            <th class="py-5 px-6 text-[10px] font-bold tracking-widest text-white/40 uppercase text-right w-28">Total Final</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(model, idx) in models"
            :key="model.id"
            @click="selectModel(model)"
            :class="[
              'border-b border-white/5 transition-all duration-300 hover:bg-white/5 hover:translate-x-1 cursor-pointer',
              idx === 0 && computeFinalScore(model) > 0 ? 'border-l-[3px] border-l-amber-400' : '',
              idx === 1 && computeFinalScore(model) > 0 ? 'border-l-[3px] border-l-slate-300' : '',
              idx === 2 && computeFinalScore(model) > 0 ? 'border-l-[3px] border-l-orange-700/60' : ''
            ]"
          >
            <!-- Rank -->
            <td class="py-5 px-6 text-center">
              <span :class="`text-[11px] font-bold tracking-widest ${computeFinalScore(model) > 0 ? 'text-white' : 'text-white/25'}`">
                {{ String(idx + 1).padStart(2, '0') }}
              </span>
            </td>

            <!-- Model -->
            <td class="py-4 px-4">
              <div class="flex items-center gap-4">
                <div class="w-11 h-11 bg-white/5 overflow-hidden shrink-0 rounded-lg">
                  <img
                    class="w-full h-full object-cover"
                    alt=""
                    :src="model.avatarUrl || model.imageUrl"
                  />
                </div>
                <span class="text-[11px] font-bold tracking-widest text-white uppercase block">
                  {{ model.name }}
                </span>
              </div>
            </td>

            <!-- Round totals -->
            <td v-for="round in ROUNDS" :key="round.key" class="py-5 px-4 text-center text-xs text-white/50 font-medium">
              {{ model.scores[round.key].total > 0 ? model.scores[round.key].total.toFixed(1) : '—' }}
            </td>

            <!-- Total -->
            <td class="py-5 px-6 text-right">
              <span :class="`text-xs font-bold tracking-widest ${computeFinalScore(model) > 0 ? 'text-amber-400' : 'text-white/25'}`">
                {{ computeFinalScore(model) > 0 ? computeFinalScore(model).toFixed(2) : 'PENDIENTE' }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
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
