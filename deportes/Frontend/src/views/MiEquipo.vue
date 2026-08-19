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
        <button @click="activeTab = 'jugadores'" :class="['w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-bold', activeTab === 'jugadores' ? 'bg-[#ccff00]/10 text-[#ccff00] border border-[#ccff00]/20' : 'text-gray-400 hover:bg-gray-900 hover:text-white']">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          Jugadores
        </button>
        <button @click="activeTab = 'equipos'" :class="['w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-bold', activeTab === 'equipos' ? 'bg-[#ccff00]/10 text-[#ccff00] border border-[#ccff00]/20' : 'text-gray-400 hover:bg-gray-900 hover:text-white']">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          Equipos
        </button>
        <router-link to="/mi-equipo/inactivos" class="w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-bold text-gray-400 hover:bg-gray-900 hover:text-white">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="18" y1="8" x2="23" y2="13"></line><line x1="23" y1="8" x2="18" y2="13"></line></svg>
          Jugadores Inactivos
        </router-link>
      </nav>

      <div class="p-4 border-t border-gray-800">
        <div class="flex items-center justify-between mb-4">
           <div class="flex items-center gap-2">
             <div class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center overflow-hidden">
               <img v-if="equipo?.foto_representante_ruta" :src="IMAGE_BASE_URL + equipo.foto_representante_ruta" class="w-full h-full object-cover">
               <svg v-else class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
             </div>
             <div>
               <p class="text-xs font-bold">{{ equipo?.representante || 'Encargado' }}</p>
               <p class="text-[10px] text-gray-500">Rep. Titular</p>
             </div>
           </div>
           <button @click="logout" class="text-gray-500 hover:text-red-500 transition-colors" title="Cerrar sesión">
             <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
           </button>
        </div>
        <div class="flex items-center justify-between pt-4 border-t border-gray-800">
           <div class="flex items-center gap-2" v-if="equipo?.sub_representante_nombre">
             <div class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center overflow-hidden">
               <img v-if="equipo?.sub_representante_foto_ruta" :src="IMAGE_BASE_URL + equipo.sub_representante_foto_ruta" class="w-full h-full object-cover">
               <svg v-else class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
             </div>
             <div>
               <p class="text-xs font-bold truncate max-w-[100px]">{{ equipo?.sub_representante_nombre }}</p>
               <p class="text-[10px] text-gray-500">Sub Rep.</p>
             </div>
           </div>
           <div v-else class="text-[10px] text-gray-500 italic">
             Sin sub-representante
           </div>
           <button @click="abrirModalSubRep" class="text-gray-500 hover:text-[#ccff00] transition-colors" title="Editar Sub Representante">
             <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
           </button>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-grow p-4 md:p-8 overflow-y-auto">
      <!-- Top header -->
      <header v-if="activeTab === 'jugadores'" class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
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
      
      <header v-if="activeTab === 'equipos'" class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
          <p class="text-[#ccff00] text-xs font-bold tracking-widest uppercase flex items-center gap-2 mb-1">
            <span class="w-6 h-px bg-[#ccff00]"></span> Directorio
          </p>
          <h1 class="text-4xl font-black italic tracking-tight">EQUIPOS</h1>
          <p class="text-gray-400 text-sm mt-1">Explora los otros equipos registrados en el sistema.</p>
        </div>
      </header>

      <div v-if="isLoading" class="text-center py-12 text-gray-500">Cargando información...</div>
      
      <div v-else-if="error" class="bg-red-500/10 border border-red-500/50 p-4 rounded-lg text-red-400 mb-6">
        {{ error }}
      </div>

      <div v-else>
        <!-- Tab: Jugadores -->
        <div v-if="activeTab === 'jugadores'">
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

          <div v-if="!equipo.sub_representante_nombre" class="bg-yellow-500/20 border border-yellow-500/50 p-4 rounded-lg text-yellow-200 mb-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
              <span>Falta agregar el sub representante del equipo.</span>
            </div>
            <button @click="abrirModalSubRep" class="bg-yellow-500 text-black px-4 py-2 rounded-lg text-xs font-bold uppercase hover:bg-yellow-400">Completar info</button>
          </div>

          <!-- Players Grid -->
          <div v-if="!equipo.jugadores || equipo.jugadores.length === 0" class="text-center py-12 text-gray-500 bg-[#1e1e1e] border border-gray-800 rounded-xl">
            No tienes jugadores registrados.
          </div>
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
            <div v-for="jugador in equipo.jugadores" :key="jugador.id" class="relative w-48 h-72 mx-auto rounded-lg shadow-2xl overflow-hidden text-[#3b2800] font-serif transform transition-transform hover:scale-105 group" style="background: linear-gradient(135deg, #e6c875 0%, #b28a38 100%); border: 1px solid #ffe9a6;">
              <!-- Top section -->
              <div class="w-full h-40 absolute top-0 left-0 flex justify-center items-end" style="mask-image: linear-gradient(to bottom, black 75%, transparent 100%); -webkit-mask-image: linear-gradient(to bottom, black 75%, transparent 100%);">
                 <img v-if="jugador.foto_ruta" :src="IMAGE_BASE_URL + jugador.foto_ruta" class="w-full h-full object-cover object-top filter contrast-125">
                 <div v-else class="w-full h-full bg-black/20 flex items-center justify-center"><svg class="w-12 h-12 text-[#3b2800]/50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></div>
              </div>
              
              <!-- Info section -->
              <div class="absolute bottom-3 left-0 right-0 px-3 text-center">
                <h3 class="text-[11px] leading-tight font-black uppercase border-b border-[#3b2800]/30 pb-1 mb-2 mx-1 h-8 flex items-center justify-center">{{ jugador.nombre }}</h3>
                <div class="flex flex-col gap-y-1 text-[9px] font-bold px-1">
                  <div class="flex justify-between"><span>DPI</span><span class="font-mono">{{ jugador.dpi }}</span></div>
                  <div class="flex justify-between"><span>TELÉFONO</span><span>{{ jugador.telefono }}</span></div>
                  <div class="flex justify-between"><span>POSICIÓN</span><span>{{ getNombrePosicion(jugador.posicion) }}</span></div>
                </div>
                <div class="flex justify-center gap-2 mt-2 pt-2 border-t border-[#3b2800]/30 mx-1">
                   <button @click="abrirModalEdit(jugador)" class="p-1.5 hover:text-white bg-black/10 hover:bg-black/40 rounded transition-colors" title="Editar"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></button>
                   <button @click="abrirModalBaja(jugador)" class="p-1.5 hover:text-red-500 bg-black/10 hover:bg-black/40 rounded transition-colors" title="Dar de baja"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab: Equipos -->
        <div v-if="activeTab === 'equipos'">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="eq in todosLosEquipos" :key="eq.id" class="bg-[#1e1e1e] border border-gray-800 rounded-xl p-6 flex items-center gap-4 hover:border-gray-700 transition-colors">
              <div class="w-16 h-16 bg-gray-900 border border-gray-700 rounded-lg flex items-center justify-center overflow-hidden shrink-0">
                 <img v-if="eq.foto_ruta" :src="IMAGE_BASE_URL + eq.foto_ruta" class="w-full h-full object-cover">
                 <svg v-else class="w-8 h-8 text-gray-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l9 4v6c0 5.55-3.84 10.74-9 12-5.16-1.26-9-6.45-9-12V6l9-4z"/></svg>
              </div>
              <div class="overflow-hidden">
                <h3 class="font-bold text-sm uppercase truncate">{{ eq.nombre }}</h3>
                <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-widest truncate">Rep: {{ eq.representante }}</p>
                <div class="mt-2 inline-block px-2 py-1 bg-gray-900 text-[#ccff00] text-[10px] font-bold uppercase rounded border border-gray-800">
                  {{ eq.jugadores ? eq.jugadores.length : 0 }} Jugadores
                </div>
              </div>
            </div>
            
            <div v-if="!todosLosEquipos || todosLosEquipos.length === 0" class="col-span-full text-center py-8 text-gray-500">
              No hay otros equipos registrados aún.
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Modal Baja -->
    <div v-if="showModalBaja" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
      <div class="bg-[#1a1a1a] border border-gray-800 p-6 rounded-xl w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Dar de baja a jugador</h3>
        <p class="text-sm text-gray-400 mb-4">¿Estás seguro que deseas dar de baja a <strong class="text-white">{{ jugadorSeleccionado?.nombre }}</strong>?</p>
        
        <div class="mb-4">
          <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Razón de la baja *</label>
          <textarea v-model="razonBaja" rows="3" class="w-full bg-[#2a2a2a] border border-gray-700 rounded-lg p-3 text-white focus:outline-none focus:border-[#ccff00]" placeholder="Escribe el motivo aquí..." required></textarea>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button @click="showModalBaja = false" class="px-4 py-2 rounded-lg font-bold text-gray-400 hover:text-white transition-colors">Cancelar</button>
          <button @click="confirmarBaja" :disabled="!razonBaja.trim()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-bold transition-colors disabled:opacity-50">Confirmar baja</button>
        </div>
      </div>
    </div>

    <!-- Modal Editar Jugador -->
    <div v-if="showModalEdit" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50 overflow-y-auto">
      <div class="bg-[#1a1a1a] border border-gray-800 p-6 rounded-xl w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Editar Jugador</h3>
        <form @submit.prevent="guardarEdicionJugador">
          <div class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Nombre *</label>
              <input v-model="editJugadorForm.nombre" type="text" class="w-full bg-[#2a2a2a] border border-gray-700 rounded-lg p-3 text-white focus:outline-none focus:border-[#ccff00]" required>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">DPI *</label>
              <input v-model="editJugadorForm.dpi" type="text" class="w-full bg-[#2a2a2a] border border-gray-700 rounded-lg p-3 text-white focus:outline-none focus:border-[#ccff00]" minlength="13" maxlength="13" required>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Foto (opcional para actualizar)</label>
              <input type="file" @change="handleEditFileChange" accept="image/*" class="w-full bg-[#2a2a2a] border border-gray-700 rounded-lg p-2 text-white">
              <div v-if="editJugadorForm.fotoUrl" class="mt-2 w-24 h-24 bg-black rounded overflow-hidden border border-gray-700">
                 <img :src="editJugadorForm.fotoUrl" class="w-full h-full object-cover">
              </div>
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button type="button" @click="showModalEdit = false" class="px-4 py-2 rounded-lg font-bold text-gray-400 hover:text-white transition-colors">Cancelar</button>
            <button type="submit" class="bg-[#ccff00] text-black px-4 py-2 rounded-lg font-bold transition-colors hover:bg-[#b3e600]">Guardar cambios</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Sub Representante -->
    <div v-if="showModalSubRep" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50 overflow-y-auto">
      <div class="bg-[#1a1a1a] border border-gray-800 p-6 rounded-xl w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Información del Sub Representante</h3>
        <form @submit.prevent="guardarSubRep">
          <div class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Nombre *</label>
              <input v-model="subRepForm.nombre" type="text" class="w-full bg-[#2a2a2a] border border-gray-700 rounded-lg p-3 text-white focus:outline-none focus:border-[#ccff00]" required>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">DPI *</label>
              <input v-model="subRepForm.dpi" type="text" class="w-full bg-[#2a2a2a] border border-gray-700 rounded-lg p-3 text-white focus:outline-none focus:border-[#ccff00]" minlength="13" maxlength="13" required>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Teléfono *</label>
              <input v-model="subRepForm.telefono" type="text" class="w-full bg-[#2a2a2a] border border-gray-700 rounded-lg p-3 text-white focus:outline-none focus:border-[#ccff00]" minlength="8" maxlength="8" required>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Foto * (o seleccionar nueva)</label>
              <input type="file" @change="handleSubRepFileChange" accept="image/*" class="w-full bg-[#2a2a2a] border border-gray-700 rounded-lg p-2 text-white" :required="!subRepForm.fotoUrl">
              <div v-if="subRepForm.fotoUrl" class="mt-2 w-24 h-24 bg-black rounded overflow-hidden border border-gray-700">
                 <img :src="subRepForm.fotoUrl" class="w-full h-full object-cover">
              </div>
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button type="button" @click="showModalSubRep = false" class="px-4 py-2 rounded-lg font-bold text-gray-400 hover:text-white transition-colors">Cancelar</button>
            <button type="submit" class="bg-[#ccff00] text-black px-4 py-2 rounded-lg font-bold transition-colors hover:bg-[#b3e600]">Guardar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api, { IMAGE_BASE_URL } from '../services/api'

const router = useRouter()
const equipo = ref(null)
const todosLosEquipos = ref([])
const isLoading = ref(true)
const error = ref('')
const activeTab = ref('jugadores')

const showModalBaja = ref(false)
const jugadorSeleccionado = ref(null)
const razonBaja = ref('')

const getNombrePosicion = (pos) => {
  const posiciones = {
    'POR': 'PORTERO',
    'DEF': 'DEFENSA',
    'MED': 'MEDIOCAMPISTA',
    'DEL': 'DELANTERO'
  }
  return posiciones[pos] || pos || 'N/A'
}

// Edit Player State
const showModalEdit = ref(false)
const editJugadorForm = ref({ id: '', nombre: '', dpi: '', foto: null, fotoUrl: '' })
const editJugadorFile = ref(null)

// Sub Rep State
const showModalSubRep = ref(false)
const subRepForm = ref({ nombre: '', dpi: '', telefono: '', foto: null, fotoUrl: '' })
const subRepFile = ref(null)

const abrirModalBaja = (jugador) => {
  jugadorSeleccionado.value = jugador
  razonBaja.value = ''
  showModalBaja.value = true
}

const abrirModalEdit = (jugador) => {
  editJugadorForm.value = {
    id: jugador.id,
    nombre: jugador.nombre,
    dpi: jugador.dpi,
    fotoUrl: jugador.foto_ruta ? IMAGE_BASE_URL + jugador.foto_ruta : ''
  }
  editJugadorFile.value = null
  showModalEdit.value = true
}

const handleEditFileChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    editJugadorFile.value = file
    editJugadorForm.value.fotoUrl = URL.createObjectURL(file)
  }
}

const guardarEdicionJugador = async () => {
  try {
    const token = localStorage.getItem('deportes_token')
    const formData = new FormData()
    formData.append('nombre', editJugadorForm.value.nombre)
    formData.append('dpi', editJugadorForm.value.dpi)
    if (editJugadorFile.value) {
      formData.append('foto', editJugadorFile.value)
    }

    await api.post(`/jugadores/${editJugadorForm.value.id}/edit`, formData, {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'multipart/form-data'
      }
    })
    
    showModalEdit.value = false
    await refrescarEquipo(token)
  } catch (err) {
    alert('Error al editar jugador: ' + (err.response?.data?.error || err.message))
  }
}

const abrirModalSubRep = () => {
  subRepForm.value = {
    nombre: equipo.value?.sub_representante_nombre || '',
    dpi: equipo.value?.sub_representante_dpi || '',
    telefono: equipo.value?.sub_representante_telefono || '',
    fotoUrl: equipo.value?.sub_representante_foto_ruta ? IMAGE_BASE_URL + equipo.value.sub_representante_foto_ruta : ''
  }
  subRepFile.value = null
  showModalSubRep.value = true
}

const handleSubRepFileChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    subRepFile.value = file
    subRepForm.value.fotoUrl = URL.createObjectURL(file)
  }
}

const guardarSubRep = async () => {
  try {
    const token = localStorage.getItem('deportes_token')
    const formData = new FormData()
    formData.append('nombre', subRepForm.value.nombre)
    formData.append('dpi', subRepForm.value.dpi)
    formData.append('telefono', subRepForm.value.telefono)
    if (subRepFile.value) {
      formData.append('foto', subRepFile.value)
    }

    await api.post('/mi-equipo/sub-representante', formData, {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'multipart/form-data'
      }
    })
    
    showModalSubRep.value = false
    await refrescarEquipo(token)
  } catch (err) {
    alert('Error al guardar sub representante: ' + (err.response?.data?.error || err.message))
  }
}

const confirmarBaja = async () => {
  if (!razonBaja.value.trim()) return

  try {
    const token = localStorage.getItem('deportes_token')
    await api.patch(`/jugadores/${jugadorSeleccionado.value.id}/baja`, {
      razon_baja: razonBaja.value
    }, {
      headers: {
        Authorization: `Bearer ${token}`
      }
    })
    
    showModalBaja.value = false
    await refrescarEquipo(token)
  } catch (err) {
    alert('Error al dar de baja: ' + (err.response?.data?.error || err.message))
  }
}

const refrescarEquipo = async (token) => {
    const response = await api.get('/mi-equipo', {
      headers: { Authorization: `Bearer ${token}` }
    })
    equipo.value = response.data
}

onMounted(async () => {
  try {
    const token = localStorage.getItem('deportes_token')
    if (!token) {
      router.push('/login')
      return
    }

    await refrescarEquipo(token)

    // Fetch all teams
    const resEquipos = await api.get('/equipos')
    // Filter out the current team if desired, or show all. Let's just show all for now.
    todosLosEquipos.value = resEquipos.data
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
