<template>
  <div class="min-h-screen bg-[#121212] flex flex-col md:flex-row text-white">
    <!-- Sidebar -->
    <aside class="w-full md:w-64 bg-[#0a0a0a] border-r border-gray-800 flex flex-col">
      <div class="p-6 border-b border-gray-800">
        <div class="flex items-center gap-3">
          <div class="w-10 h-12 bg-gray-900 border border-purple-500 rounded flex items-center justify-center overflow-hidden">
             <svg class="w-6 h-6 text-purple-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
          </div>
          <div>
            <h2 class="font-bold text-sm uppercase">ADMIN PANEL</h2>
            <p class="text-[10px] text-purple-500 uppercase tracking-widest">Sistema DEPORTES</p>
          </div>
        </div>
      </div>
      
      <nav class="flex-grow p-4 space-y-2">
        <button @click="equipoSeleccionado = null" :class="['w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-bold', !equipoSeleccionado ? 'bg-purple-500/10 text-purple-500 border border-purple-500/20' : 'text-gray-400 hover:bg-gray-900 hover:text-white']">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          Directorio Equipos
        </button>
      </nav>

      <div class="p-4 border-t border-gray-800">
        <div class="flex items-center justify-between">
           <div class="flex items-center gap-2">
             <div class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center overflow-hidden">
               <svg class="w-4 h-4 text-purple-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
             </div>
             <div>
               <p class="text-xs font-bold">Administrador</p>
               <p class="text-[10px] text-gray-500">Superusuario</p>
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
      <div v-if="isLoading" class="text-center py-12 text-gray-500">Cargando información...</div>
      
      <div v-else-if="error" class="bg-red-500/10 border border-red-500/50 p-4 rounded-lg text-red-400 mb-6">
        {{ error }}
      </div>

      <div v-else>
        <!-- Vista Directorio de Equipos -->
        <div v-if="!equipoSeleccionado">
          <header class="mb-8">
            <p class="text-purple-500 text-xs font-bold tracking-widest uppercase flex items-center gap-2 mb-1">
              <span class="w-6 h-px bg-purple-500"></span> Directorio General
            </p>
            <h1 class="text-4xl font-black italic tracking-tight">EQUIPOS REGISTRADOS</h1>
          </header>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="eq in equipos" :key="eq.id" @click="verEquipo(eq.id)" class="bg-[#1e1e1e] border border-gray-800 rounded-xl p-6 flex items-center gap-4 hover:border-purple-500/50 cursor-pointer transition-colors group">
              <div class="w-16 h-16 bg-gray-900 border border-gray-700 rounded-lg flex items-center justify-center overflow-hidden shrink-0 group-hover:border-purple-500/30 transition-colors">
                 <img v-if="eq.foto_ruta" :src="IMAGE_BASE_URL + eq.foto_ruta" class="w-full h-full object-cover">
                 <svg v-else class="w-8 h-8 text-gray-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l9 4v6c0 5.55-3.84 10.74-9 12-5.16-1.26-9-6.45-9-12V6l9-4z"/></svg>
              </div>
              <div class="overflow-hidden">
                <h3 class="font-bold text-sm uppercase truncate text-white">{{ eq.nombre }}</h3>
                <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-widest truncate">Rep: {{ eq.representante }}</p>
                <div class="mt-2 inline-block px-2 py-1 bg-gray-900 text-purple-400 text-[10px] font-bold uppercase rounded border border-gray-800">
                  {{ eq.cantidad_jugadores || 0 }} Jugadores Activos
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Vista Detalle de Equipo -->
        <div v-else>
          <button @click="equipoSeleccionado = null" class="mb-6 flex items-center text-sm font-bold text-gray-400 hover:text-white transition-colors">
            <svg class="w-4 h-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Volver a Equipos
          </button>

          <header class="mb-8">
            <div class="flex items-center gap-4 mb-4">
              <div class="w-16 h-16 bg-gray-900 border border-gray-700 rounded-lg overflow-hidden shrink-0">
                 <img v-if="equipoSeleccionado.foto_ruta" :src="IMAGE_BASE_URL + equipoSeleccionado.foto_ruta" class="w-full h-full object-cover">
              </div>
              <div>
                <h1 class="text-3xl font-black italic tracking-tight">{{ equipoSeleccionado.nombre }}</h1>
                <p class="text-sm text-gray-400">Representante: {{ equipoSeleccionado.representante }}</p>
              </div>
            </div>
          </header>

          <div class="bg-[#1e1e1e] border border-gray-800 rounded-xl overflow-hidden mb-8">
            <div class="p-4 border-b border-gray-800 flex justify-between items-center bg-[#1a1a1a]">
              <h3 class="font-bold text-sm uppercase tracking-wider text-purple-400">Jugadores Activos</h3>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="border-b border-gray-800 text-[10px] uppercase tracking-widest text-gray-500 bg-[#161616]">
                    <th class="p-4 font-bold">Jugador</th>
                    <th class="p-4 font-bold text-center">Posición</th>
                    <th class="p-4 font-bold text-center">Estado</th>
                    <th class="p-4 font-bold text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!equipoSeleccionado.jugadores_activos?.length">
                    <td colspan="4" class="p-8 text-center text-gray-500 text-sm">No hay jugadores activos.</td>
                  </tr>
                  <tr v-for="jugador in equipoSeleccionado.jugadores_activos" :key="jugador.id" @click="verDetalle(jugador)" class="border-b border-gray-800 hover:bg-[#252525] transition-colors cursor-pointer">
                    <td class="p-4">
                      <div class="flex items-center gap-3">
                        <img v-if="jugador.foto_ruta" :src="IMAGE_BASE_URL + jugador.foto_ruta" class="w-8 h-8 rounded-full object-cover border border-gray-700">
                        <span class="font-bold text-sm">{{ jugador.nombre }}</span>
                      </div>
                    </td>
                    <td class="p-4 text-center">
                      <span class="inline-block px-2 py-1 bg-gray-800 text-gray-300 text-[10px] font-bold uppercase rounded border border-gray-700">{{ jugador.posicion }}</span>
                    </td>
                    <td class="p-4 text-center">
                      <span class="inline-block px-3 py-1 bg-purple-500/10 text-purple-400 text-[10px] font-bold uppercase rounded-full border border-purple-500/30">Activo</span>
                    </td>
                    <td class="p-4 text-center">
                      <button @click.stop="abrirModalBaja(jugador)" class="text-gray-500 hover:text-red-500 transition-colors" title="Dar de baja">
                        <svg class="w-5 h-5 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="bg-[#1e1e1e] border border-gray-800 rounded-xl overflow-hidden opacity-80">
            <div class="p-4 border-b border-gray-800 flex justify-between items-center bg-[#1a1a1a]">
              <h3 class="font-bold text-sm uppercase tracking-wider text-red-400">Jugadores Inactivos</h3>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="border-b border-gray-800 text-[10px] uppercase tracking-widest text-gray-500 bg-[#161616]">
                    <th class="p-4 font-bold">Jugador</th>
                    <th class="p-4 font-bold text-center">Fecha Baja</th>
                    <th class="p-4 font-bold">Razón</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!equipoSeleccionado.jugadores_inactivos?.length">
                    <td colspan="3" class="p-8 text-center text-gray-500 text-sm">No hay jugadores inactivos.</td>
                  </tr>
                  <tr v-for="jugador in equipoSeleccionado.jugadores_inactivos" :key="jugador.id" @click="verDetalle(jugador)" class="border-b border-gray-800 hover:bg-[#252525] transition-colors cursor-pointer">
                    <td class="p-4">
                      <div class="flex items-center gap-3 grayscale">
                        <img v-if="jugador.foto_ruta" :src="IMAGE_BASE_URL + jugador.foto_ruta" class="w-8 h-8 rounded-full object-cover border border-gray-700">
                        <span class="font-bold text-sm text-gray-400">{{ jugador.nombre }}</span>
                      </div>
                    </td>
                    <td class="p-4 text-center text-xs text-gray-500">{{ formatearFecha(jugador.fecha_baja) }}</td>
                    <td class="p-4 text-xs text-gray-400 max-w-xs truncate" :title="jugador.razon_baja">{{ jugador.razon_baja }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Modal Baja -->
    <div v-if="showModalBaja" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
      <div class="bg-[#1a1a1a] border border-gray-800 p-6 rounded-xl w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Dar de baja a jugador (Admin)</h3>
        <p class="text-sm text-gray-400 mb-4">¿Deseas forzar la baja de <strong class="text-white">{{ jugadorABajar?.nombre }}</strong>?</p>
        
        <div class="mb-4">
          <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Razón de la baja *</label>
          <textarea v-model="razonBaja" rows="3" class="w-full bg-[#2a2a2a] border border-gray-700 rounded-lg p-3 text-white focus:outline-none focus:border-purple-500" placeholder="Escribe el motivo aquí..." required></textarea>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button @click="showModalBaja = false" class="px-4 py-2 rounded-lg font-bold text-gray-400 hover:text-white transition-colors">Cancelar</button>
          <button @click="confirmarBaja" :disabled="!razonBaja.trim()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-bold transition-colors disabled:opacity-50">Confirmar baja</button>
        </div>
      </div>
    </div>

    <!-- Modal Detalle -->
    <div v-if="jugadorDetalle" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50" @click="jugadorDetalle = null">
      <div class="bg-[#1a1a1a] border border-gray-800 p-6 rounded-xl w-full max-w-md relative" @click.stop>
        <button @click="jugadorDetalle = null" class="absolute top-4 right-4 text-gray-500 hover:text-white transition-colors">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        
        <div class="flex flex-col items-center mb-6">
          <div :class="['w-24 h-24 rounded-full overflow-hidden mb-4 border-2', jugadorDetalle.estado === 'inactivo' ? 'border-red-500 grayscale' : 'border-purple-500']">
            <img v-if="jugadorDetalle.foto_ruta" :src="IMAGE_BASE_URL + jugadorDetalle.foto_ruta" class="w-full h-full object-cover">
            <svg v-else class="w-full h-full text-gray-500 bg-gray-800 p-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          </div>
          <h3 class="font-black text-2xl uppercase tracking-tight text-center">{{ jugadorDetalle.nombre }}</h3>
          <span :class="['mt-2 inline-block px-3 py-1 text-[10px] font-bold uppercase rounded-full border', jugadorDetalle.estado === 'inactivo' ? 'bg-red-500/10 text-red-500 border-red-500/30' : 'bg-purple-500/10 text-purple-500 border-purple-500/30']">
            {{ jugadorDetalle.estado }}
          </span>
        </div>
        
        <div class="space-y-3 bg-[#2a2a2a] p-4 rounded-lg border border-gray-700">
          <div class="flex justify-between border-b border-gray-700 pb-2">
            <span class="text-xs text-gray-500 uppercase font-bold">DPI</span>
            <span class="text-sm font-mono text-gray-300">{{ jugadorDetalle.dpi }}</span>
          </div>
          <div class="flex justify-between border-b border-gray-700 pb-2">
            <span class="text-xs text-gray-500 uppercase font-bold">Teléfono</span>
            <span class="text-sm text-gray-300">{{ jugadorDetalle.telefono }}</span>
          </div>
          <div class="flex justify-between border-b border-gray-700 pb-2">
            <span class="text-xs text-gray-500 uppercase font-bold">Posición</span>
            <span class="text-sm text-gray-300">{{ jugadorDetalle.posicion || 'N/A' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-xs text-gray-500 uppercase font-bold">Equipo</span>
            <span class="text-sm text-purple-400 font-bold truncate max-w-[150px]">{{ equipoSeleccionado?.nombre }}</span>
          </div>
        </div>

        <div v-if="jugadorDetalle.estado === 'inactivo'" class="mt-4 bg-red-500/10 p-4 rounded-lg border border-red-500/30">
          <label class="block text-[10px] font-bold text-red-400 uppercase tracking-wider mb-2">Detalles de Baja ({{ formatearFecha(jugadorDetalle.fecha_baja) }}):</label>
          <p class="text-gray-300 text-sm whitespace-pre-line">{{ jugadorDetalle.razon_baja || 'Sin motivo especificado.' }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api, { IMAGE_BASE_URL } from '../services/api'

const router = useRouter()
const equipos = ref([])
const equipoSeleccionado = ref(null)
const isLoading = ref(true)
const error = ref('')

const showModalBaja = ref(false)
const jugadorABajar = ref(null)
const razonBaja = ref('')

const jugadorDetalle = ref(null)

const verDetalle = (jugador) => {
  jugadorDetalle.value = jugador
}

onMounted(async () => {
  await cargarEquipos()
})

const cargarEquipos = async () => {
  isLoading.value = true
  try {
    const token = localStorage.getItem('deportes_token')
    const response = await api.get('/admin/equipos', {
      headers: { Authorization: `Bearer ${token}` }
    })
    equipos.value = response.data
  } catch (err) {
    handleError(err)
  } finally {
    isLoading.value = false
  }
}

const verEquipo = async (id) => {
  isLoading.value = true
  try {
    const token = localStorage.getItem('deportes_token')
    const response = await api.get(`/admin/equipos/${id}/jugadores`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    equipoSeleccionado.value = response.data
  } catch (err) {
    handleError(err)
  } finally {
    isLoading.value = false
  }
}

const abrirModalBaja = (jugador) => {
  jugadorABajar.value = jugador
  razonBaja.value = ''
  showModalBaja.value = true
}

const confirmarBaja = async () => {
  if (!razonBaja.value.trim()) return

  try {
    const token = localStorage.getItem('deportes_token')
    await api.patch(`/jugadores/${jugadorABajar.value.id}/baja`, {
      razon_baja: razonBaja.value
    }, {
      headers: { Authorization: `Bearer ${token}` }
    })
    
    showModalBaja.value = false
    
    // Refresh equipo actual
    await verEquipo(equipoSeleccionado.value.id)
  } catch (err) {
    alert('Error al dar de baja: ' + (err.response?.data?.error || err.message))
  }
}

const logout = () => {
  localStorage.removeItem('deportes_token')
  localStorage.removeItem('deportes_equipo')
  localStorage.removeItem('deportes_rol')
  router.push('/')
}

const formatearFecha = (fechaString) => {
  if (!fechaString) return 'N/A'
  const date = new Date(fechaString)
  return new Intl.DateTimeFormat('es-ES', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric'
  }).format(date)
}

const handleError = (err) => {
  if (err.response?.status === 401 || err.response?.status === 403) {
    logout()
  } else {
    error.value = 'Error de conexión.'
  }
}
</script>
