<template>
  <div class="pt-20 pb-10 px-4 md:px-10 md:pb-20 max-w-7xl mx-auto space-y-10 relative">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h2 class="text-4xl font-bold tracking-tight text-white mb-2">Transporte Pesado</h2>
        <p class="text-white/60">Gestión de unidades de transporte pesado.</p>
      </div>
    </div>
    <div class="glass-card rounded-[40px] p-16 flex flex-col items-center justify-center border border-white/10 text-center gap-4">
      <TruckIcon class="w-16 h-16 text-white/20" />
      <p class="text-white/40 font-bold text-lg">Módulo en construcción</p>
      <p class="text-white/25 text-sm">Este apartado estará disponible próximamente.</p>
    </div>
  </div>
</template>

<script setup>
import { TruckIcon } from '@heroicons/vue/24/outline';
</script>
