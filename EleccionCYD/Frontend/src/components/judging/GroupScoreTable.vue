<script setup>
import { useModelStore } from '../../stores/modelStore';
import { getRubrics } from '../../utils/rubrics';

const props = defineProps({
  title: { type: String, required: true },
  models: { type: Array, required: true },
  roundKey: { type: String, required: true },
});

const store = useModelStore();

// Coreografía usa los mismos rubros para ambas categorías (a diferencia de Gala).
const rubrics = getRubrics(props.roundKey, props.models[0]?.category);

function clampScore(rawValue) {
  if (rawValue === '' || rawValue === null) return 0;
  const n = Math.round(Number(rawValue));
  if (Number.isNaN(n)) return 0;
  return Math.min(10, Math.max(1, n));
}

function updateScore(model, rubricKey, rawValue) {
  const round = model.scores[props.roundKey];
  round[rubricKey] = clampScore(rawValue);

  const values = rubrics.map((r) => round[r.key]);
  round.total = values.every((v) => v > 0)
    ? parseFloat((values.reduce((a, b) => a + b, 0) / values.length).toFixed(2))
    : 0;

  store.saveModels();
}
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
                <img :src="model.imageUrl" class="w-20 aspect-[3/4] rounded-xl object-cover shrink-0" alt="" />
                <span class="text-[11px] font-bold tracking-widest text-white uppercase">{{ model.name }}</span>
              </div>
            </td>
            <td v-for="rubric in rubrics" :key="rubric.key" class="py-3 px-3 text-center">
              <input
                type="number"
                min="1" max="10" step="1"
                :value="model.scores[roundKey][rubric.key] || ''"
                @input="updateScore(model, rubric.key, $event.target.value)"
                placeholder="—"
                class="w-14 bg-white/5 border border-white/15 rounded-lg text-center text-sm text-white py-1.5 focus:outline-none focus:border-amber-400 placeholder:text-white/25"
              />
            </td>
            <td class="py-3 px-4 text-right">
              <span :class="['text-xs font-bold tracking-widest', model.scores[roundKey].total > 0 ? 'text-amber-400' : 'text-white/25']">
                {{ model.scores[roundKey].total > 0 ? model.scores[roundKey].total.toFixed(2) : '—' }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
