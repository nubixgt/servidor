<template>
  <div v-if="isOpen" class="fixed inset-0 bg-[#091426]/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click="$emit('close')">
    <div 
      class="bg-white rounded-xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-100 flex flex-col max-h-[90vh] animate-fade-in"
      @click.stop
    >
      <!-- Header -->
      <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
        <div class="flex items-center gap-2">
          <LucideIcon name="shield-alert" class="w-5 h-5 text-secondary-base" />
          <h3 class="text-base font-bold text-primary-base">{{ title }}</h3>
        </div>
        <button
          @click="$emit('close')"
          class="p-1.5 hover:bg-slate-200 text-slate-400 hover:text-slate-600 rounded transition-colors cursor-pointer"
        >
          <LucideIcon name="x" class="w-5 h-5" />
        </button>
      </div>

      <!-- Content Box -->
      <div class="p-6 overflow-y-auto text-sm text-slate-600 leading-relaxed space-y-4">
        <slot></slot>
      </div>

      <!-- Action button -->
      <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end">
        <button
          @click="$emit('close')"
          class="px-5 py-2.5 bg-primary-base text-white text-xs font-bold rounded-lg hover:bg-slate-800 transition-colors uppercase cursor-pointer"
        >
          Entendido
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import LucideIcon from './LucideIcon.vue';

defineProps({
  isOpen: Boolean,
  title: String
});

defineEmits(['close']);
</script>
