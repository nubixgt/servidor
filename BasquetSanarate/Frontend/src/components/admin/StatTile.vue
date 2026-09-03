<template>
  <div class="bg-surface-container-lowest rounded-DEFAULT p-space-md shadow-sm flex flex-col">
    <span class="font-label-meta text-label-meta uppercase text-secondary tracking-wider">{{ label }}</span>
    <div class="flex items-baseline justify-between mt-space-2xs gap-space-xs">
      <span class="font-stat-display text-stat-display text-on-surface leading-none">{{ value }}</span>
      <span
        v-if="hint"
        class="font-label-pill text-label-pill px-space-xs py-0.5 rounded-full"
        :class="hintClass"
      >{{ hint }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], default: '—' },
  hint: { type: String, default: '' },
  tone: { type: String, default: 'neutral' } // neutral | primary | tertiary
});

const hintClass = computed(() => ({
  neutral: 'text-secondary bg-surface-container',
  primary: 'text-primary bg-primary-container/40',
  tertiary: 'text-tertiary bg-tertiary-container/30'
}[props.tone] || 'text-secondary bg-surface-container'));
</script>
