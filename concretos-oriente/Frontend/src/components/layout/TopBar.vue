<template>
  <header class="md:ml-[280px] h-20 sticky top-0 glass-topbar z-40 px-10 flex items-center justify-between">
    <div class="flex items-center gap-4">
      <h2 class="text-xl font-bold text-white tracking-tight">{{ title }}</h2>
    </div>

    <div class="flex items-center gap-6 flex-grow max-w-xl mx-8">
      <div class="relative w-full">
        <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/40" />
        <input
          type="text"
          placeholder="Buscar proyectos, personal o datos..."
          class="w-full glass-input rounded-xl pl-11 pr-6 py-2.5 text-sm text-white focus:ring-2 focus:ring-primary/40 transition-all outline-none placeholder:text-white/20"
        />
      </div>
    </div>

    <div class="flex items-center gap-4">
      <button class="relative p-2.5 text-white/60 hover:text-white hover:bg-white/10 rounded-xl transition-all cursor-pointer">
        <BellIcon class="w-5 h-5" />
        <span class="absolute top-2.5 right-2.5 w-2 h-2 bg-primary rounded-full border-2 border-black/20 shadow-[0_0_8px_#6366f1]"></span>
      </button>
      <button class="p-2.5 text-white/60 hover:text-white hover:bg-white/10 rounded-xl transition-all cursor-pointer">
        <Cog8ToothIcon class="w-5 h-5" />
      </button>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { MagnifyingGlassIcon, BellIcon, Cog8ToothIcon } from '@heroicons/vue/24/outline';

const route = useRoute();

const title = computed(() => {
  switch (route.name) {
    case 'Dashboard': return 'Vista General del Panel';
    case 'Personnel': return 'Gestión de Personal';
    case 'Machinery': return 'Gestión de Maquinaria';
    case 'Projects': return 'Portafolio de Proyectos';
    case 'Finance': return 'Vista General de Finanzas';
    case 'Inventory': return 'Gestión de Inventario';
    case 'Suppliers': return 'Directorio de Proveedores';
    case 'Purchases': return 'Módulo de Compras';
    case 'MachineryStatus': return 'Estatus de Maquinaria';
    case 'TechProjects': return 'Mis Asignaciones';
    default: return 'Concretos del Oriente';
  }
});
</script>
