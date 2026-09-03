<template>
  <span
    class="inline-flex items-center gap-1 px-space-xs py-0.5 rounded-full font-label-meta text-label-meta uppercase font-bold"
    :class="toneClass"
  >
    <span v-if="dot" class="w-1.5 h-1.5 rounded-full" :class="dotClass"></span>
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: { type: String, required: true },
  tone: { type: String, default: 'neutral' }, // success | danger | neutral | info | primary
  dot: { type: Boolean, default: false }
});

const toneClass = computed(() => ({
  success: 'bg-tertiary-container/40 text-on-tertiary-container',
  danger: 'bg-error-container text-on-error-container',
  neutral: 'bg-surface-container text-secondary',
  info: 'bg-secondary-container text-on-secondary-container',
  primary: 'bg-primary-container text-on-primary-fixed'
}[props.tone] || 'bg-surface-container text-secondary'));

const dotClass = computed(() => ({
  success: 'bg-tertiary',
  danger: 'bg-error',
  neutral: 'bg-secondary',
  info: 'bg-secondary',
  primary: 'bg-on-primary-fixed'
}[props.tone] || 'bg-secondary'));
</script>
