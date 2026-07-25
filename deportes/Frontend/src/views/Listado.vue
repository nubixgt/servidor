<template>
  <div class="container mx-auto px-4 py-8">
    
    <!-- Search Bar & Filters -->
    <div class="flex flex-col md:flex-row gap-4 mb-8">
      <div class="flex-grow relative">
        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-500">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        </div>
        <input v-model="searchQuery" type="text" class="w-full bg-[#161616] border border-gray-800 focus:border-primary text-white py-3 pl-12 pr-4 rounded-none outline-none text-sm placeholder-gray-600" placeholder="BUSCAR EQUIPO O LIGA...">
      </div>
      <div class="flex gap-4 shrink-0">
        <button class="bg-[#161616] border border-gray-800 text-gray-300 font-bold py-3 px-6 text-xs uppercase tracking-widest hover:border-gray-600 transition-colors">Todas las ligas</button>
        <button class="bg-[#161616] border border-gray-800 text-gray-300 font-bold py-3 px-6 text-xs uppercase tracking-widest hover:border-gray-600 transition-colors">Estado</button>
      </div>
    </div>
    
    <!-- Header Title -->
    <div class="flex justify-between items-end mb-8 border-b border-gray-800 pb-4">
      <div>
        <h2 class="text-primary font-bold text-xs tracking-widest mb-1 uppercase">
          Rendimiento Pro
        </h2>
        <h1 class="text-3xl md:text-4xl font-black uppercase italic tracking-tighter">Equipos <span class="text-primary">Registrados</span></h1>
      </div>
      <div class="hidden md:flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-widest cursor-pointer hover:text-white transition-colors">
        Ordenar por
        <svg class="w-4 h-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="16" y2="6"></line><line x1="4" y1="12" x2="20" y2="12"></line><line x1="4" y1="18" x2="12" y2="18"></line></svg>
      </div>
    </div>
    
    <!-- Loading / Empty states -->
    <div v-if="isLoading" class="text-center py-12 text-primary">Cargando equipos...</div>
    <div v-else-if="filteredEquipos.length === 0" class="text-center py-12 text-gray-500">No se encontraron equipos registrados.</div>
    
    <!-- Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 relative pb-20">
      
      <!-- Watermark text in background -->
      <div class="absolute -bottom-10 left-0 right-0 text-center pointer-events-none z-0 overflow-hidden">
        <span class="text-[150px] font-black italic tracking-tighter text-[#161616] whitespace-nowrap leading-none select-none">PERFORMANCE</span>
      </div>
      
      <div v-for="equipo in filteredEquipos" :key="equipo.id" class="bg-panel border border-transparent hover:border-gray-700 transition-all duration-300 group relative z-10 flex flex-col">
        
        <!-- Top Image Area -->
        <div class="h-48 bg-[#121212] relative overflow-hidden flex items-center justify-center p-4">
          <!-- The user UI has specific background images or icons here. We use the uploaded photo -->
          <img v-if="equipo.foto_ruta" :src="IMAGE_BASE_URL + equipo.foto_ruta" class="w-full h-full object-contain filter group-hover:scale-105 transition-transform duration-500" :alt="equipo.nombre" @error="equipo.foto_ruta = null" />
          <div v-else class="text-4xl font-black text-gray-800 italic flex items-center justify-center">
            <svg class="w-16 h-16 mr-2 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            {{ equipo.nombre?.substring(0,2).toUpperCase() || 'EQ' }}
          </div>
          
          <div class="absolute top-4 right-4 bg-primary text-black w-10 h-10 rounded-lg flex items-center justify-center shadow-lg">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5.5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
          </div>
          <div class="absolute bottom-4 left-4 bg-[#ccff00]/20 text-primary border border-primary text-[10px] font-bold px-2 py-1 uppercase tracking-widest backdrop-blur-sm">
            Liga Pro
          </div>
        </div>
        
        <!-- Info Area -->
        <div class="p-6 flex-grow flex flex-col">
          <div class="flex justify-between items-start mb-4">
            <h3 class="text-xl font-black uppercase tracking-wider group-hover:text-primary transition-colors">{{ equipo.nombre }}</h3>
            <div class="text-right shrink-0">
              <div class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Jugadores</div>
              <div class="text-primary font-black text-xl leading-none">{{ equipo.jugadores ? equipo.jugadores.length : 0 }}</div>
            </div>
          </div>
          
          <div class="flex items-center gap-3 mb-6 mt-auto">
            <div class="w-10 h-10 rounded-lg bg-gray-800 border border-gray-700 overflow-hidden shrink-0 flex items-center justify-center text-gray-500 text-xs font-bold uppercase">
              {{ equipo.representante?.substring(0,2) || 'RP' }}
            </div>
            <div>
              <div class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Representante</div>
              <div class="text-sm font-bold text-gray-300 uppercase">{{ equipo.representante || 'Sin asignar' }}</div>
            </div>
          </div>
          
          <!-- Dropdown/Toggle for players -->
          <div class="border-t border-gray-800 pt-4">
            <button @click="togglePlantilla(equipo.id)" class="w-full flex justify-between items-center text-xs font-bold text-gray-400 hover:text-white uppercase tracking-widest transition-colors">
              Ver Plantilla
              <svg class="w-4 h-4 transition-transform duration-300" :class="{'rotate-180': expandedEquipo === equipo.id}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </button>
            
            <!-- Players List -->
            <div v-if="expandedEquipo === equipo.id" class="mt-4 space-y-3 animate-fadeIn">
              <div v-if="!equipo.jugadores || equipo.jugadores.length === 0" class="text-xs text-gray-500 italic">No hay jugadores registrados.</div>
              <div v-for="jugador in equipo.jugadores" :key="jugador.id" class="flex items-center gap-3 bg-[#161616] p-2">
                <img v-if="jugador.foto_ruta" :src="IMAGE_BASE_URL + jugador.foto_ruta" class="w-8 h-8 rounded-full object-cover border border-gray-700" />
                <div v-else class="w-8 h-8 rounded-full bg-gray-800 flex items-center justify-center text-[10px] font-bold">{{ jugador.nombre?.substring(0,2) || 'JG' }}</div>
                <div class="flex-grow">
                  <div class="text-xs font-bold text-gray-300 uppercase truncate">{{ jugador.nombre }}</div>
                  <div class="text-[10px] text-gray-500 tracking-widest">{{ jugador.dpi }}</div>
                </div>
                <div class="text-[10px] font-bold px-2 py-1 bg-[#1e1e1e] border border-gray-700 text-gray-400">
                  {{ jugador.posicion || 'N/A' }}
                </div>
              </div>
            </div>
          </div>
        </div>
        
      </div>
    </div>
    
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api, { IMAGE_BASE_URL } from '../services/api'

const equipos = ref([])
const isLoading = ref(true)
const searchQuery = ref('')
const expandedEquipo = ref(null)

onMounted(async () => {
  try {
    const response = await api.get('/equipos')
    equipos.value = response.data
  } catch (err) {
    console.error('Error al cargar equipos', err)
  } finally {
    isLoading.value = false
  }
})

const filteredEquipos = computed(() => {
  if (!searchQuery.value) return equipos.value
  const query = searchQuery.value.toLowerCase()
  return equipos.value.filter(e => e.nombre.toLowerCase().includes(query))
})

const togglePlantilla = (id) => {
  if (expandedEquipo.value === id) {
    expandedEquipo.value = null
  } else {
    expandedEquipo.value = id
  }
}
</script>

<style scoped>
.animate-fadeIn {
  animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-5px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
