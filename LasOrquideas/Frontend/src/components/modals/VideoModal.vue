<template>
  <div v-if="isOpen" className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-slate-950/80 backdrop-blur-md animate-fadeIn">
    <div className="relative w-full max-w-4xl glass-card rounded-3xl overflow-hidden shadow-2xl border border-white/90 flex flex-col">
      <!-- Header Bar -->
      <div className="p-4 bg-white/90 border-b border-slate-200/80 flex items-center justify-between">
        <div className="flex items-center gap-2">
          <span className="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse" />
          <h3 className="font-extrabold text-sm sm:text-base text-slate-900">
            Recorrido Virtual 4K: Las Orquídeas
          </h3>
        </div>
        <button
          @click="$emit('close')"
          className="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition cursor-pointer"
        >
          <X className="w-4 h-4" />
        </button>
      </div>

      <!-- Video Canvas Simulated Preview -->
      <div className="relative aspect-video w-full bg-black overflow-hidden group">
        <img
          :src="chapters[activeChapter].image"
          :alt="chapters[activeChapter].title"
          referrerPolicy="no-referrer"
          className="w-full h-full object-cover filter brightness-[0.85] transition-transform duration-1000 group-hover:scale-105"
        />

        <!-- Video Scrim & Title overlay -->
        <div className="absolute top-4 left-4 glass-card rounded-xl px-3 py-1.5 text-xs font-bold text-slate-800 shadow-md">
          Capítulo: {{ chapters[activeChapter].title }}
        </div>

        <!-- Center Play/Pause Indicator when paused -->
        <div v-if="!isPlaying" className="absolute inset-0 flex items-center justify-center bg-black/30">
          <button
            @click="isPlaying = true"
            className="w-16 h-16 rounded-full bg-white/90 text-slate-900 flex items-center justify-center shadow-2xl hover:scale-110 transition cursor-pointer pl-1"
          >
            <Play className="w-7 h-7 fill-current" />
          </button>
        </div>

        <!-- Bottom Player Controls Bar -->
        <div className="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/90 via-black/40 to-transparent flex items-center justify-between text-white text-xs">
          <div className="flex items-center gap-3">
            <button
              @click="isPlaying = !isPlaying"
              className="p-1.5 rounded-lg hover:bg-white/20 transition cursor-pointer"
            >
              <Pause v-if="isPlaying" className="w-4 h-4" />
              <Play v-else className="w-4 h-4" />
            </button>
            <button
              @click="isMuted = !isMuted"
              className="p-1.5 rounded-lg hover:bg-white/20 transition cursor-pointer"
            >
              <VolumeX v-if="isMuted" className="w-4 h-4" />
              <Volume2 v-else className="w-4 h-4" />
            </button>
            <span className="text-slate-300 font-mono text-[11px]">01:45 / 03:00</span>
          </div>

          <div className="flex items-center gap-2">
            <button
              @click="activeChapter = (activeChapter + 1) % chapters.length"
              className="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white/20 hover:bg-white/30 text-white font-medium text-[11px] transition cursor-pointer"
            >
              <RotateCcw className="w-3 h-3" />
              <span>Siguiente Toma</span>
            </button>
            <button
              @click="$emit('close')"
              className="p-1.5 rounded-lg hover:bg-white/20 transition cursor-pointer"
            >
              <Maximize className="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Chapters Strip -->
      <div className="p-4 bg-white/95 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div className="flex items-center gap-2 overflow-x-auto w-full sm:w-auto">
          <button
            v-for="(ch, idx) in chapters"
            :key="ch.title"
            @click="activeChapter = idx; isPlaying = true;"
            :class="[
              'px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition cursor-pointer',
              activeChapter === idx ? 'bg-brand-forest text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'
            ]"
          >
            {{ ch.title }}
          </button>
        </div>

        <button
          @click="$emit('close'); $emit('selectLot');"
          className="w-full sm:w-auto bg-brand-forest hover:bg-brand-forest-dark text-white px-5 py-2.5 rounded-xl font-bold text-xs shadow-md transition active:scale-95 cursor-pointer whitespace-nowrap"
        >
          Ver Lotes de esta fase →
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { X, Play, Pause, Volume2, VolumeX, Maximize, RotateCcw } from 'lucide-vue-next';

defineProps({
  isOpen: { type: Boolean, default: false }
});

defineEmits(['close', 'selectLot']);

const isPlaying = ref(true);
const isMuted = ref(false);
const activeChapter = ref(0);

const chapters = [
  { title: '01. Garita Monumental', duration: '0:00 - 0:45', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuA3mfKzUNp-D4qQZaCTKJAEgPt_ZC6I4_hNRZZm4kjfe893Xdi_bUjNSdqBKgzlAZBP7Tj_O-5GweK0mrnXlo9AvuWPpCh6KaXwtUzviogP6NHUBoiwOCmkuY53NocHaz07uH9iyi29XuEUGR5qUWiQGMhVK3W2g-YfSpOXjhVeDAaCNxvwhrxdQw2QlIhpyCqFrdZy6uNeFZiETuV4UHWAcNpm8rv6LRMXMWJDBLVRK6VfevfLgw' },
  { title: '02. Bulevar Central 14m', duration: '0:45 - 1:30', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuA8xKPRr1gDdhvOa-9Mo5O9RE9T9t8raftSAfhHh4qP4vExV5lfS7_eBc-g22XIRZe2rGNAyslT4GX_RCy_OerFvQxZKoO9bSm--f8-O8CEvo6oN47lcC00KFaBQ_MxL38aG3Dmb57QK0VvD8g7YqGfYmzz2SVBODd93tFnky-RGe1wnX-Urao8nqXX4yJ_-sfufPRMe16EeXlNQxFEVm1Q8OJnlyMBhb7tYaFffKmEgP8gcf8fBA' },
  { title: '03. Lotes Manzana B', duration: '1:30 - 2:15', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuB94Y7dtdtagYRkl3k2SRaUjEIOLcKtFG4A_rnzD6os-_m0u5MivUBtUIZxsxQ9I_pfqeiKfQaPRKH7YxzVP3ltBbIo4MwR1Mjsv-tsbUIVn0qXpsMmP_zVSoobhVVNopsYTXExg8rOTVr9whd-5s0YMJyXujA2gFG5Lm3F9ObtZBlNGxKzrVue9S5uHucCJuyBnLi7a5QkXoeJVP59Dk67dYfb-tRT5x5NZfDSQmDnlzzh0lZnXQ' },
  { title: '04. Vistas a las Montañas', duration: '2:15 - 3:00', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDTKVKxv0Zu6bjoexlhKYL6yMIHwtNjrD3zsxIg4dyj4hXLeKjcJLu1RW3taJPHzyrs-A0N8yylu-fUBXhqLzjQjGbDM_NcaXuqqJAAcJbqbSiV4XhhvwtN0HPOMmC2ckD3MZDjix8T8wcOPSb2oYM4u-q9k6RMeHXeMaeoCo8Or_G5dFye8oDTb-9Bp9wZ_8aPkeeP9xVveCTHbLzbPCa0IY61X1mF_Bph_HSFY-dAA-BFwjbuzQ' },
];
</script>
