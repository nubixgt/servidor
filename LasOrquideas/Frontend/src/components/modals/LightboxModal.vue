<template>
  <div v-if="currentIndex !== null && items[currentIndex]" className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-slate-950/90 backdrop-blur-md animate-fadeIn">
    <!-- Close button -->
    <button
      @click="$emit('close')"
      aria-label="Cerrar visor"
      className="absolute top-5 right-5 z-20 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition cursor-pointer"
    >
      <X className="w-5 h-5" />
    </button>

    <!-- Prev button -->
    <button
      @click="$emit('prev')"
      aria-label="Foto anterior"
      className="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition cursor-pointer"
    >
      <ChevronLeft className="w-6 h-6" />
    </button>

    <!-- Next button -->
    <button
      @click="$emit('next')"
      aria-label="Foto siguiente"
      className="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition cursor-pointer"
    >
      <ChevronRight className="w-6 h-6" />
    </button>

    <!-- Image & Caption Container -->
    <div className="relative max-w-5xl w-full flex flex-col items-center">
      <div className="relative max-h-[75vh] rounded-2xl overflow-hidden shadow-2xl border border-white/20">
        <img
          :src="items[currentIndex].imageUrl"
          :alt="items[currentIndex].title"
          referrerPolicy="no-referrer"
          className="w-full h-auto max-h-[75vh] object-contain"
        />
      </div>

      <!-- Caption -->
      <div className="mt-4 text-center text-white max-w-xl">
        <span className="text-[10px] font-bold uppercase tracking-wider bg-brand-forest text-emerald-300 px-3 py-1 rounded-full">
          {{ items[currentIndex].tag }}
        </span>
        <h4 className="text-lg font-bold mt-2">{{ items[currentIndex].title }}</h4>
        <p className="text-xs text-slate-300 mt-1">{{ items[currentIndex].description }}</p>
        <span className="text-[11px] text-slate-400 mt-2 block">
          {{ currentIndex + 1 }} de {{ items.length }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { X, ChevronLeft, ChevronRight } from 'lucide-vue-next';

defineProps({
  currentIndex: { type: Number, default: null },
  items: { type: Array, required: true }
});

defineEmits(['close', 'prev', 'next']);
</script>
