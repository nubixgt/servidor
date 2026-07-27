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
        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-400 hover:bg-gray-900 hover:text-white transition-colors">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
          Inicio
        </a>
        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-lg bg-[#ccff00]/10 text-[#ccff00] font-bold transition-colors border border-[#ccff00]/20">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          Jugadores
        </a>
      </nav>

      <div class="p-4 border-t border-gray-800">
        <div class="flex items-center justify-between">
           <div class="flex items-center gap-2">
             <div class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center overflow-hidden">
               <img v-if="equipo?.foto_representante_ruta" :src="IMAGE_BASE_URL + equipo.foto_representante_ruta" class="w-full h-full object-cover">
               <svg v-else class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
             </div>
             <div>
               <p class="text-xs font-bold">{{ equipo?.representante || 'Encargado' }}</p>
               <p class="text-[10px] text-gray-500">Administrador</p>
             </div>
           </div>
           <button @click="logout" class="text-gray-500 hover:text-red-500 transition-colors" title="Cerrar sesión">
             <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
           </button>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-grow p-4 md:p-8 overflow-y-auto">
      <!-- Top header -->
      <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
          <p class="text-[#ccff00] text-xs font-bold tracking-widest uppercase flex items-center gap-2 mb-1">
            <span class="w-6 h-px bg-[#ccff00]"></span> Gestión de Jugadores
          </p>
          <h1 class="text-4xl font-black italic tracking-tight">JUGADORES</h1>
          <p class="text-gray-400 text-sm mt-1">Administra la información de los jugadores de tu equipo.</p>
        </div>
        <router-link to="/inscripcion-jugador" class="bg-[#ccff00] hover:bg-[#b3e600] text-black font-bold py-3 px-6 rounded-lg flex items-center gap-2 transition-colors uppercase text-sm">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          Agregar Jugador
        </router-link>
      </header>

      <div v-if="isLoading" class="text-center py-12 text-gray-500">Cargando información...</div>
      
      <div v-else-if="error" class="bg-red-500/10 border border-red-500/50 p-4 rounded-lg text-red-400 mb-6">
        {{ error }}
      </div>

      <div v-else>
        <!-- Stats Row -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
          <div class="bg-[#1e1e1e] border border-gray-800 rounded-xl p-6 flex flex-col items-center justify-center text-center">
            <div class="text-3xl font-black mb-1">{{ equipo.jugadores?.length || 0 }}</div>
            <div class="text-xs text-gray-400 uppercase tracking-wider">Jugadores Registrados</div>
          </div>
          <div class="bg-[#1e1e1e] border border-gray-800 rounded-xl p-6 flex flex-col items-center justify-center text-center">
             <svg class="w-8 h-8 text-[#ccff00] mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
             <div class="text-xs text-gray-400 uppercase tracking-wider">Activos Disponibles</div>
          </div>
        </div>

        <!-- Table -->
        <div class="bg-[#1e1e1e] border border-gray-800 rounded-xl overflow-hidden">
          <div class="p-4 border-b border-gray-800 flex justify-between items-center">
            <h3 class="font-bold text-sm uppercase tracking-wider">Lista de Jugadores</h3>
          </div>
          
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-gray-800 text-[10px] uppercase tracking-widest text-gray-500 bg-[#161616]">
                  <th class="p-4 font-bold">Jugador</th>
                  <th class="p-4 font-bold text-center">Posición</th>
                  <th class="p-4 font-bold">DPI / ID</th>
                  <th class="p-4 font-bold text-center">Estado</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!equipo.jugadores || equipo.jugadores.length === 0">
                  <td colspan="4" class="p-8 text-center text-gray-500 text-sm">
                    No tienes jugadores registrados.
                  </td>
                </tr>
                <tr v-for="jugador in equipo.jugadores" :key="jugador.id" class="border-b border-gray-800 hover:bg-[#252525] transition-colors">
                  <td class="p-4">
                    <div class="flex items-center gap-3">
                      <img v-if="jugador.foto_ruta" :src="IMAGE_BASE_URL + jugador.foto_ruta" class="w-10 h-10 rounded-full object-cover border border-gray-700">
                      <div v-else class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center border border-gray-700">
                         <svg class="w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                      </div>
                      <span class="font-bold text-sm">{{ jugador.nombre }}</span>
                    </div>
                  </td>
                  <td class="p-4 text-center">
                    <span class="inline-block px-2 py-1 bg-gray-800 text-gray-300 text-[10px] font-bold uppercase rounded border border-gray-700">
                      {{ jugador.posicion }}
                    </span>
                  </td>
                  <td class="p-4 text-sm text-gray-400 font-mono">{{ jugador.dpi }}</td>
                  <td class="p-4 text-center">
                    <span class="inline-block px-3 py-1 bg-[#ccff00]/10 text-[#ccff00] text-[10px] font-bold uppercase rounded-full border border-[#ccff00]/30">
                      Activo
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api, { IMAGE_BASE_URL } from '../services/api'

const router = useRouter()
const equipo = ref(null)
const isLoading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    const token = localStorage.getItem('deportes_token')
    if (!token) {
      router.push('/login')
      return
    }

    const response = await api.get('/mi-equipo', {
      headers: {
        Authorization: `Bearer ${token}`
      }
    })
    
    equipo.value = response.data
  } catch (err) {
    if (err.response?.status === 401) {
      logout()
    } else {
      error.value = 'Error al cargar la información del equipo.'
    }
  } finally {
    isLoading.value = false
  }
})

const logout = () => {
  localStorage.removeItem('deportes_token')
  localStorage.removeItem('deportes_equipo')
  router.push('/')
}
</script>
