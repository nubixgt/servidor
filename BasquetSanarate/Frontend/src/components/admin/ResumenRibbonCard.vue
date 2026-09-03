<template>
  <div
    class="relative overflow-hidden rounded-lg p-space-md shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between group"
    :class="dark ? 'bg-inverse-surface text-inverse-on-surface' : 'bg-surface-container-lowest'"
  >
    <div class="flex items-start justify-between">
      <div
        class="w-10 h-10 rounded-full flex items-center justify-center transition-colors"
        :class="dark ? 'bg-surface-bright/10 text-primary-container' : 'bg-surface-container text-on-surface group-hover:bg-primary-container group-hover:text-on-primary-fixed'"
      >
        <span class="material-symbols-outlined text-[22px]">{{ icon }}</span>
      </div>
      <span
        class="font-label-meta text-label-meta font-bold uppercase tracking-wider px-2 py-0.5 rounded-full"
        :class="dark ? 'bg-primary-container text-on-primary-fixed' : 'bg-surface-container-high text-secondary'"
      >{{ hint }}</span>
    </div>
    <div class="mt-space-md">
      <div class="font-stat-display text-stat-display leading-none" :class="dark ? 'text-surface-bright' : 'text-on-surface'">{{ value }}</div>
      <div class="font-label-pill text-label-pill uppercase tracking-wide mt-1" :class="dark ? 'text-secondary-fixed-dim' : 'text-secondary'">{{ label }}</div>
    </div>
    <div class="mt-space-sm pt-space-xs flex items-center justify-end">
      <router-link
        :to="to"
        class="inline-flex items-center gap-0.5 font-label-pill text-label-pill font-bold"
        :class="dark ? 'text-primary-container hover:text-surface-bright' : 'text-primary hover:text-on-primary-container'"
      >
        <span>{{ cta }}</span>
        <span class="material-symbols-outlined text-[16px]">chevron_right</span>
      </router-link>
    </div>
  </div>
</template>

<script setup>
defineProps({
  icon: { type: String, required: true },
  value: { type: [String, Number], default: 0 },
  label: { type: String, required: true },
  hint: { type: String, default: '' },
  to: { type: String, default: '#' },
  cta: { type: String, default: 'Ver' },
  dark: { type: Boolean, default: false }
});
</script>
