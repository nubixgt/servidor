<template>
  <router-link
    :to="to"
    class="group relative overflow-hidden rounded-lg bg-surface p-space-md transition-all duration-200 hover:bg-inverse-surface hover:text-inverse-on-surface hover:shadow-lg flex flex-col justify-between h-36"
  >
    <div class="flex items-center justify-between">
      <div class="w-10 h-10 rounded-full bg-primary-container text-on-primary-fixed flex items-center justify-center group-hover:scale-110 transition-transform">
        <span class="material-symbols-outlined text-[20px]">{{ icon }}</span>
      </div>
      <span class="material-symbols-outlined text-outline group-hover:text-primary-container text-[18px]">arrow_outward</span>
    </div>
    <div>
      <span class="block font-headline-md text-headline-md uppercase leading-tight text-on-surface group-hover:text-surface-bright">{{ title }}</span>
      <span class="block font-label-meta text-label-meta text-secondary group-hover:text-surface-container-high mt-1">{{ desc }}</span>
    </div>
  </router-link>
</template>

<script setup>
defineProps({
  to: { type: String, required: true },
  icon: { type: String, required: true },
  title: { type: String, required: true },
  desc: { type: String, default: '' }
});
</script>
