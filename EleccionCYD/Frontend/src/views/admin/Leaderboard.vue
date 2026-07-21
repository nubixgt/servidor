<script setup>
import { computed } from 'vue';
import { useModelStore } from '../../stores/modelStore';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';
import RankingSection from '../../components/leaderboard/RankingSection.vue';

const store = useModelStore();

const rankedSenoritas = computed(() => store.rankedSenoritas);
const rankedJovenes = computed(() => store.rankedJovenes);
</script>

<template>
  <div class="flex-grow px-6 md:px-16 py-12 max-w-5xl mx-auto w-full text-white">

    <!-- Page Title -->
    <section class="mb-16">
      <h1 class="text-3xl font-light tracking-tight text-white mb-2 uppercase">Clasificación en <span class="text-amber-400 font-normal">Vivo</span></h1>
      <p class="text-sm text-white/50 leading-relaxed max-w-2xl">
        Tabla de posiciones oficial de EleccionCYD. Las posiciones se actualizan en tiempo real conforme el jurado envía sus evaluaciones por ronda.
      </p>
    </section>

    <RankingSection title="Señoritas" :models="rankedSenoritas" />
    <RankingSection title="Jóvenes" :models="rankedJovenes" />

    <!-- Footer Pagination Status -->
    <div class="flex justify-between items-center text-white/40 select-none">
      <div class="flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
        <span class="text-[9px] font-bold tracking-widest uppercase">Transmisión de Datos en Vivo Activa</span>
      </div>
      <div class="flex gap-3">
        <button class="p-2 border border-white/15 hover:border-amber-400 hover:text-amber-400 transition-colors rounded-lg cursor-pointer">
          <ChevronLeftIcon class="w-4 h-4 stroke-[1.5]" />
        </button>
        <button class="p-2 border border-white/15 hover:border-amber-400 hover:text-amber-400 transition-colors rounded-lg cursor-pointer">
          <ChevronRightIcon class="w-4 h-4 stroke-[1.5]" />
        </button>
      </div>
    </div>

  </div>
</template>
