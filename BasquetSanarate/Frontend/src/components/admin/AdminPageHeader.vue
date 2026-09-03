<template>
  <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-space-md mb-space-lg">
    <div class="flex flex-col">
      <div v-if="eyebrow" class="flex items-center gap-space-xs mb-space-2xs">
        <span class="w-2.5 h-2.5 rounded-full bg-primary-container shadow-[0_0_10px_#ccff00]"></span>
        <span class="font-label-meta text-label-meta uppercase tracking-widest text-primary font-bold">{{ eyebrow }}</span>
      </div>
      <h1 class="font-headline-xl text-headline-xl uppercase text-on-surface tracking-tight">{{ title }}</h1>
      <p v-if="subtitle" class="font-body-md text-body-md text-on-surface-variant max-w-2xl mt-space-2xs">{{ subtitle }}</p>
    </div>
    <div v-if="$slots.actions" class="flex items-center gap-space-sm flex-wrap shrink-0">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  eyebrow: { type: String, default: '' },
  subtitle: { type: String, default: '' }
});
</script>
