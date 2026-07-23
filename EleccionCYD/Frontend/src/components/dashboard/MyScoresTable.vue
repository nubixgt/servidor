<script setup>
import { useRouter } from 'vue-router';
import { useModelStore } from '../../stores/modelStore';
import { ROUNDS } from '../../utils/rubrics';

const props = defineProps({
  title: { type: String, required: true },
  models: { type: Array, required: true },
});

const store = useModelStore();
const router = useRouter();

// Mi propio total por participante (suma de los totales que YO le puse en cada ronda).
function myTotalFor(participantId) {
  return ROUNDS.reduce((acc, round) => {
    const score = store.myScoreFor(participantId, round.key);
    return acc + (score ? score.total : 0);
  }, 0);
}

function hasAnyScore(participantId) {
  return ROUNDS.some((round) => !!store.myScoreFor(participantId, round.key));
}

const goRate = (participant) => {
  store.setSelectedModel(participant.id);
  router.push({ name: 'LiveJudging' });
};
</script>

<template>
  <section class="mb-12">
    <h2 class="text-lg font-light tracking-tight text-white mb-4 uppercase border-b border-white/10 pb-3">{{ title }}</h2>
    <div class="bg-white/5 backdrop-blur-xl overflow-x-auto border border-white/10 rounded-2xl">
      <table class="w-full border-collapse text-left">
        <thead>
          <tr class="border-b border-white/10 bg-white/5">
            <th class="py-4 px-4 text-[10px] font-bold tracking-widest text-white/40 uppercase">Participante</th>
            <th v-for="round in ROUNDS" :key="round.key" class="py-4 px-3 text-[10px] font-bold tracking-widest text-white/40 uppercase text-center">{{ round.label }}</th>
            <th class="py-4 px-4 text-[10px] font-bold tracking-widest text-white/40 uppercase text-right w-28">Mi Total</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="model in props.models"
            :key="model.id"
            @click="goRate(model)"
            class="border-b border-white/5 last:border-b-0 hover:bg-white/5 transition-all cursor-pointer"
          >
            <td class="py-4 px-4">
              <div class="flex items-center gap-3">
                <img :src="model.imageUrl" class="w-9 h-9 rounded-lg object-cover shrink-0" alt="" />
                <span class="text-[11px] font-bold tracking-widest text-white uppercase">{{ model.name }}</span>
              </div>
            </td>
            <td v-for="round in ROUNDS" :key="round.key" class="py-4 px-3 text-center text-xs text-white/50 font-medium">
              {{ store.myScoreFor(model.id, round.key)?.total ?? '—' }}
            </td>
            <td class="py-4 px-4 text-right">
              <span :class="['text-xs font-bold tracking-widest', hasAnyScore(model.id) ? 'text-amber-400' : 'text-white/25']">
                {{ hasAnyScore(model.id) ? myTotalFor(model.id) : 'PENDIENTE' }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
