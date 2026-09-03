<template>
  <button
    type="button"
    :aria-label="label"
    :title="label"
    class="rounded-full flex items-center justify-center transition-colors shadow-sm shrink-0"
    :class="[sizeClass, variantClass]"
  >
    <span class="material-symbols-outlined" :class="iconSizeClass">{{ icon }}</span>
  </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  icon: { type: String, required: true },
  label: { type: String, default: '' },
  variant: { type: String, default: 'neutral' }, // neutral | primary | danger
  size: { type: String, default: 'md' } // sm | md
});

const sizeClass = computed(() => (props.size === 'sm' ? 'w-8 h-8' : 'w-10 h-10'));
const iconSizeClass = computed(() => (props.size === 'sm' ? 'text-[16px]' : 'text-[20px]'));

const variantClass = computed(() => ({
  neutral: 'bg-surface-container hover:bg-surface-variant text-secondary hover:text-on-surface',
  primary: 'bg-surface-container hover:bg-primary-container hover:text-on-primary-fixed text-secondary',
  danger: 'bg-error-container/30 text-error hover:bg-error hover:text-on-error'
}[props.variant] || 'bg-surface-container text-secondary'));
</script>
