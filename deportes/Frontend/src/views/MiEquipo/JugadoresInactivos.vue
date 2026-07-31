<template>
  <div class="min-h-screen bg-[#121212] flex flex-col md:flex-row text-white">
    <!-- Sidebar -->
    <aside class="w-full md:w-64 bg-[#0a0a0a] border-r border-gray-800 flex flex-col">
      <div class="p-6 border-b border-gray-800">
        <div class="flex items-center gap-3">
          <div class="w-10 h-12 bg-gray-900 border border-[#ccff00] rounded flex items-center justify-center overflow-hidden">
             <img v-if="equipo?.foto_ruta" :src="IMAGE_BASE_URL + equipo.foto_ruta" class="w-full h-full object-cover">
             <svg v-else class="w-6 h-6 text-[#ccff00]" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l9 4v6c0 5.55-3.84 10.74-9 12-5.16-1.26-9-6.45-9-12V6l9-4z"/></svg>
          </div>
          <div>
            <h2 class="font-bold text-sm truncate uppercase">{{ equipo?.nombre || 'Mi Equipo' }}</h2>
            <p class="text-[10px] text-[#ccff00] uppercase tracking-widest">Primera División</p>
          </div>
        </div>
      </div>
      
      <nav class="flex-grow p-4 space-y-2">
        <router-link to="/mi-equipo" class="w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-bold text-gray-400 hover:bg-gray-900 hover:text-white">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          Volver a Mi Equipo
        </router-link>
        <div class="w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-bold bg-[#ccff00]/10 text-[#ccff00] border border-[#ccff00]/20">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="18" y1="8" x2="23" y2="13"></line><line x1="23" y1="8" x2="18" y2="13"></line></svg>
          Jugadores Inactivos
        </div>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-grow p-4 md:p-8 overflow-y-auto">
      <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
          <p class="text-red-500 text-xs font-bold tracking-widest uppercase flex items-center gap-2 mb-1">
            <span class="w-6 h-px bg-red-500"></span> Historial de Bajas
          </p>
          <h1 class="text-4xl font-black italic tracking-tight">JUGADORES INACTIVOS</h1>
          <p class="text-gray-400 text-sm mt-1">Consulta los jugadores que han sido dados de baja de tu equipo.</p>
        </div>
      </header>

      <div v-if="isLoading" class="text-center py-12 text-gray-500">Cargando información...</div>
      
      <div v-else-if="error" class="bg-red-500/10 border border-red-500/50 p-4 rounded-lg text-red-400 mb-6">
        {{ error }}
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="jugador in jugadoresInactivos" :key="jugador.id" @click="verRazon(jugador)" class="bg-[#1e1e1e] border border-gray-800 rounded-xl p-6 flex items-center gap-4 hover:border-gray-700 cursor-pointer transition-colors relative overflow-hidden group">
          <!-- Decorator line -->
          <div class="absolute left-0 top-0 bottom-0 w-1 bg-red-500/50 group-hover:bg-red-500 transition-colors"></div>
          
          <div class="w-16 h-16 bg-gray-900 border border-gray-700 rounded-full flex items-center justify-center overflow-hidden shrink-0 grayscale opacity-80">
             <img v-if="jugador.foto_ruta" :src="IMAGE_BASE_URL + jugador.foto_ruta" class="w-full h-full object-cover">
             <svg v-else class="w-8 h-8 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          </div>
          <div class="overflow-hidden flex-grow">
            <h3 class="font-bold text-sm uppercase truncate text-gray-300">{{ jugador.nombre }}</h3>
            <p class="text-[10px] text-gray-500 mt-1 uppercase tracking-widest font-mono">DPI: {{ jugador.dpi }}</p>
            <div class="mt-2 text-[10px] text-red-400/80 font-bold uppercase truncate">
              Baja: {{ formatearFecha(jugador.fecha_baja) }}
            </div>
          </div>
        </div>
        
        <div v-if="!jugadoresInactivos || jugadoresInactivos.length === 0" class="col-span-full text-center py-12 bg-[#1e1e1e] border border-gray-800 rounded-xl">
          <svg class="w-12 h-12 text-gray-700 mx-auto mb-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="18" y1="8" x2="23" y2="13"></line><line x1="23" y1="8" x2="18" y2="13"></line></svg>
          <p class="text-gray-500 font-bold uppercase tracking-wider text-sm">No hay jugadores inactivos</p>
        </div>
      </div>
    </main>

    <!-- Modal Razón -->
    <div v-if="jugadorSeleccionado" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
      <div class="bg-[#1a1a1a] border border-gray-800 p-6 rounded-xl w-full max-w-md relative">
        <button @click="jugadorSeleccionado = null" class="absolute top-4 right-4 text-gray-500 hover:text-white">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        
        <div class="flex items-center gap-4 mb-6">
          <div class="w-12 h-12 rounded-full overflow-hidden grayscale">
            <img v-if="jugadorSeleccionado.foto_ruta" :src="IMAGE_BASE_URL + jugadorSeleccionado.foto_ruta" class="w-full h-full object-cover">
          </div>
          <div>
            <h3 class="font-bold text-lg leading-tight">{{ jugadorSeleccionado.nombre }}</h3>
            <p class="text-xs text-red-400 uppercase tracking-wider">Dado de baja el {{ formatearFecha(jugadorSeleccionado.fecha_baja) }}</p>
          </div>
        </div>
        
        <div class="bg-[#2a2a2a] p-4 rounded-lg border border-gray-700">
          <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Motivo registrado:</label>
          <p class="text-gray-300 text-sm whitespace-pre-line">{{ jugadorSeleccionado.razon_baja || 'Sin motivo especificado.' }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api, { IMAGE_BASE_URL } from '../../services/api'

const router = useRouter()
const equipo = ref(null)
const jugadoresInactivos = ref([])
const isLoading = ref(true)
const error = ref('')
const jugadorSeleccionado = ref(null)

onMounted(async () => {
  try {
    const token = localStorage.getItem('deportes_token')
    if (!token) {
      router.push('/login')
      return
    }

    // Usamos el mismo endpoint de inactivos que devuelve el equipo con sus jugadores inactivos
    const response = await api.get('/mi-equipo/inactivos', {
      headers: {
        Authorization: `Bearer ${token}`
      }
    })
    
    equipo.value = response.data
    jugadoresInactivos.value = response.data.jugadores || []

  } catch (err) {
    if (err.response?.status === 401) {
      localStorage.removeItem('deportes_token')
      router.push('/login')
    } else {
      error.value = 'Error al cargar la lista de inactivos.'
    }
  } finally {
    isLoading.value = false
  }
})

const formatearFecha = (fechaString) => {
  if (!fechaString) return 'Desconocida'
  const date = new Date(fechaString)
  return new Intl.DateTimeFormat('es-ES', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

const verRazon = (jugador) => {
  jugadorSeleccionado.value = jugador
}
</script>
