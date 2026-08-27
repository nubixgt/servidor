<template>
  <div class="min-h-screen bg-background text-on-background font-body-md flex flex-col md:flex-row">
    <!-- TopNavBar (Mobile Only) -->
    <header class="md:hidden flex justify-between items-center px-container-margin py-4 w-full bg-background/80 backdrop-blur-lg border-b border-white/10 sticky top-0 z-50">
      <div class="text-headline-lg-mobile font-headline-lg-mobile text-primary-fixed tracking-tighter uppercase">DEPORTES</div>
      <span class="material-symbols-outlined text-on-surface-variant">menu</span>
    </header>

    <!-- SideNavBar (Desktop) -->
    <aside class="hidden md:flex flex-col h-screen w-64 p-gutter bg-surface-container-lowest border-r border-white/10 shadow-xl fixed left-0 top-0 z-40">
      <div class="mb-stack-lg flex flex-col items-center gap-stack-sm mt-2">
        <div class="w-16 h-16 rounded-xl gradient-card flex items-center justify-center border border-primary-fixed/30 overflow-hidden">
          <img v-if="equipo?.foto_ruta" :src="IMAGE_BASE_URL + equipo.foto_ruta" class="w-full h-full object-cover" alt="Escudo" />
          <span v-else class="material-symbols-outlined text-primary-fixed text-3xl">shield</span>
        </div>
        <div class="text-center w-full">
          <div class="text-title-md font-title-md text-on-surface uppercase truncate">{{ equipo?.nombre || 'Mi Equipo' }}</div>
          <div class="text-label-sm font-label-sm text-primary-fixed uppercase tracking-widest">Primera División</div>
        </div>
      </div>

      <nav class="flex-1 flex flex-col gap-2">
        <button
          @click="activeTab = 'jugadores'"
          :class="['flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 text-left', activeTab === 'jugadores' ? 'bg-primary-container text-on-primary-container ring-1 ring-primary-fixed/50' : 'text-on-surface-variant hover:bg-primary-container/10']"
        >
          <span class="material-symbols-outlined">groups</span>
          <span class="text-label-sm font-label-sm font-bold">Jugadores</span>
        </button>
        <button
          @click="activeTab = 'equipos'"
          :class="['flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 text-left', activeTab === 'equipos' ? 'bg-primary-container text-on-primary-container ring-1 ring-primary-fixed/50' : 'text-on-surface-variant hover:bg-primary-container/10']"
        >
          <span class="material-symbols-outlined">emoji_events</span>
          <span class="text-label-sm font-label-sm font-bold">Equipos</span>
        </button>
        <router-link to="/mi-equipo/inactivos" class="flex items-center gap-3 px-4 py-3 rounded-lg text-on-surface-variant hover:bg-primary-container/10 transition-all duration-200">
          <span class="material-symbols-outlined">person_off</span>
          <span class="text-label-sm font-label-sm font-bold">Jugadores Inactivos</span>
        </router-link>
        <router-link to="/registrar-partido" class="flex items-center gap-3 px-4 py-3 rounded-lg text-on-surface-variant hover:bg-primary-container/10 transition-all duration-200">
          <span class="material-symbols-outlined">sports_soccer</span>
          <span class="text-label-sm font-label-sm font-bold">Registrar Partido</span>
        </router-link>
      </nav>

      <div class="mt-auto pt-4 border-t border-white/10 flex flex-col gap-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2 overflow-hidden">
            <div class="w-8 h-8 bg-surface-container-high rounded-full flex items-center justify-center overflow-hidden shrink-0">
              <img v-if="equipo?.foto_representante_ruta" :src="IMAGE_BASE_URL + equipo.foto_representante_ruta" class="w-full h-full object-cover" />
              <span v-else class="material-symbols-outlined text-on-surface-variant text-[18px]">person</span>
            </div>
            <div class="overflow-hidden">
              <p class="text-label-sm font-label-sm text-on-surface truncate">{{ equipo?.representante || 'Encargado' }}</p>
              <p class="text-[10px] text-on-surface-variant">Rep. Titular</p>
            </div>
          </div>
          <button @click="logout" class="text-on-surface-variant hover:text-error-container transition-colors" title="Cerrar sesión">
            <span class="material-symbols-outlined">logout</span>
          </button>
        </div>
        <div class="flex items-center justify-between pt-3 border-t border-white/10">
          <div class="flex items-center gap-2 overflow-hidden" v-if="equipo?.sub_representante_nombre">
            <div class="w-8 h-8 bg-surface-container-high rounded-full flex items-center justify-center overflow-hidden shrink-0">
              <img v-if="equipo?.sub_representante_foto_ruta" :src="IMAGE_BASE_URL + equipo.sub_representante_foto_ruta" class="w-full h-full object-cover" />
              <span v-else class="material-symbols-outlined text-on-surface-variant text-[18px]">person</span>
            </div>
            <div class="overflow-hidden">
              <p class="text-label-sm font-label-sm text-on-surface truncate">{{ equipo?.sub_representante_nombre }}</p>
              <p class="text-[10px] text-on-surface-variant">Sub Rep.</p>
            </div>
          </div>
          <div v-else class="text-[10px] text-on-surface-variant italic">Sin sub-representante</div>
          <button @click="abrirModalSubRep" class="text-on-surface-variant hover:text-primary-fixed transition-colors" title="Editar Sub Representante">
            <span class="material-symbols-outlined text-[20px]">edit</span>
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 md:ml-64 p-container-margin flex flex-col gap-stack-lg">
      <header v-if="activeTab === 'jugadores'" class="flex flex-col md:flex-row justify-between items-start md:items-end gap-stack-md border-b border-white/10 pb-stack-md">
        <div>
          <p class="text-primary-fixed text-label-sm font-label-sm uppercase tracking-widest flex items-center gap-2 mb-1">
            <span class="w-6 h-px bg-primary-fixed"></span> Gestión de Jugadores
          </p>
          <h1 class="text-headline-lg font-headline-lg text-white uppercase tracking-tight">Jugadores</h1>
          <p class="text-on-surface-variant text-body-md font-body-md mt-1">Administra la información de los jugadores de tu equipo.</p>
        </div>
        <router-link to="/inscripcion-jugador" class="bg-primary-container text-on-primary-fixed px-6 py-3 rounded-lg font-title-md text-title-md flex items-center gap-2 hover:brightness-110 transition-all">
          <span class="material-symbols-outlined">person_add</span>
          Agregar Jugador
        </router-link>
      </header>

      <header v-if="activeTab === 'equipos'" class="flex flex-col md:flex-row justify-between items-start md:items-end gap-stack-md border-b border-white/10 pb-stack-md">
        <div>
          <p class="text-primary-fixed text-label-sm font-label-sm uppercase tracking-widest flex items-center gap-2 mb-1">
            <span class="w-6 h-px bg-primary-fixed"></span> Directorio
          </p>
          <h1 class="text-headline-lg font-headline-lg text-white uppercase tracking-tight">Equipos</h1>
          <p class="text-on-surface-variant text-body-md font-body-md mt-1">Explora los otros equipos registrados en el sistema.</p>
        </div>
      </header>

      <div v-if="isLoading" class="text-center py-12 text-on-surface-variant">Cargando información...</div>

      <div v-else-if="error" class="bg-error-container/20 border border-error-container/50 p-4 rounded-lg text-error">
        {{ error }}
      </div>

      <div v-else class="flex flex-col gap-stack-lg">
        <!-- Tab: Jugadores -->
        <div v-if="activeTab === 'jugadores'" class="flex flex-col gap-stack-lg">
          <!-- KPI Grid -->
          <div class="grid grid-cols-2 md:grid-cols-3 gap-gutter">
            <div class="gradient-card rounded-xl p-4 relative overflow-hidden group hover:border-primary-fixed/50 transition-colors">
              <div class="absolute -right-4 -top-4 opacity-5 group-hover:opacity-10 transition-opacity">
                <span class="material-symbols-outlined text-[100px]">groups</span>
              </div>
              <div class="text-label-sm font-label-sm text-on-surface-variant mb-2 uppercase">Jugadores Registrados</div>
              <div class="text-headline-lg font-headline-lg text-white">{{ equipo.jugadores?.length || 0 }}</div>
            </div>
            <div class="gradient-card rounded-xl p-4 relative overflow-hidden group hover:border-primary-fixed/50 transition-colors">
              <div class="absolute -right-4 -top-4 opacity-5 group-hover:opacity-10 transition-opacity">
                <span class="material-symbols-outlined text-[100px]">verified</span>
              </div>
              <div class="text-label-sm font-label-sm text-on-surface-variant mb-2 uppercase">Activos Disponibles</div>
              <div class="text-headline-lg font-headline-lg text-primary-fixed">{{ equipo.jugadores?.length || 0 }}</div>
            </div>
            <div class="gradient-card rounded-xl p-4 relative overflow-hidden group hover:border-primary-fixed/50 transition-colors">
              <div class="absolute -right-4 -top-4 opacity-5 group-hover:opacity-10 transition-opacity">
                <span class="material-symbols-outlined text-[100px]">badge</span>
              </div>
              <div class="text-label-sm font-label-sm text-on-surface-variant mb-2 uppercase">Sub Representante</div>
              <div class="text-title-md font-title-md mt-2" :class="equipo.sub_representante_nombre ? 'text-primary-fixed' : 'text-error-container'">
                {{ equipo.sub_representante_nombre ? 'Registrado' : 'Pendiente' }}
              </div>
            </div>
          </div>

          <!-- Warning Banner -->
          <div v-if="!equipo.sub_representante_nombre" class="bg-surface-container-high border-l-4 border-error-container p-4 rounded-r-lg flex items-start gap-4">
            <span class="material-symbols-outlined text-error-container mt-1">warning</span>
            <div>
              <h3 class="text-title-md font-title-md text-on-surface mb-1">Sub-representante Requerido</h3>
              <p class="text-body-md font-body-md text-on-surface-variant">Falta agregar el sub representante del equipo antes del próximo partido oficial.</p>
            </div>
            <button @click="abrirModalSubRep" class="ml-auto text-primary-fixed font-label-sm text-label-sm uppercase tracking-wider hover:underline shrink-0">Resolver</button>
          </div>

          <!-- Roster Section -->
          <div class="flex flex-col gap-stack-md">
            <h2 class="text-headline-lg-mobile font-headline-lg-mobile text-white uppercase tracking-wider">Player Directory</h2>

            <div v-if="!equipo.jugadores || equipo.jugadores.length === 0" class="text-center py-12 text-on-surface-variant gradient-card rounded-xl">
              No tienes jugadores registrados.
            </div>
            <div v-else class="overflow-x-auto pb-4">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="border-b border-white/10 text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider">
                    <th class="py-3 px-4 font-normal">Jugador</th>
                    <th class="py-3 px-4 font-normal">Posición</th>
                    <th class="py-3 px-4 font-normal">DPI</th>
                    <th class="py-3 px-4 font-normal">Teléfono</th>
                    <th class="py-3 px-4 font-normal text-right">Estado</th>
                    <th class="py-3 px-4 font-normal text-right">Acciones</th>
                  </tr>
                </thead>
                <tbody class="text-body-md font-body-md">
                  <tr v-for="jugador in equipo.jugadores" :key="jugador.id" class="border-b border-white/5 hover:bg-white/5 transition-colors group">
                    <td class="py-3 px-4">
                      <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-surface-container-high overflow-hidden border border-white/10 flex items-center justify-center shrink-0">
                          <img v-if="jugador.foto_ruta" :src="IMAGE_BASE_URL + jugador.foto_ruta" class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all" alt="Jugador" />
                          <span v-else class="material-symbols-outlined text-on-surface-variant">person</span>
                        </div>
                        <span class="text-white font-medium">{{ jugador.nombre }}</span>
                      </div>
                    </td>
                    <td class="py-3 px-4 text-on-surface-variant">{{ getNombrePosicion(jugador.posicion) }}</td>
                    <td class="py-3 px-4 font-mono text-sm tracking-wider text-on-surface-variant">{{ jugador.dpi }}</td>
                    <td class="py-3 px-4 text-on-surface-variant">{{ jugador.telefono }}</td>
                    <td class="py-3 px-4 text-right">
                      <span class="inline-flex items-center px-2 py-1 rounded-full bg-primary-fixed/10 border border-primary-fixed/30 text-primary-fixed text-[10px] font-bold uppercase tracking-wider">
                        Activo
                      </span>
                    </td>
                    <td class="py-3 px-4">
                      <div class="flex items-center justify-end gap-1">
                        <button @click="abrirModalEdit(jugador)" class="p-1.5 text-on-surface-variant hover:text-primary-fixed hover:bg-white/5 rounded transition-colors" title="Editar">
                          <span class="material-symbols-outlined text-[18px]">edit</span>
                        </button>
                        <button @click="abrirModalBaja(jugador)" class="p-1.5 text-on-surface-variant hover:text-error hover:bg-white/5 rounded transition-colors" title="Dar de baja">
                          <span class="material-symbols-outlined text-[18px]">person_remove</span>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Tab: Equipos -->
        <div v-if="activeTab === 'equipos'">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-gutter">
            <div v-for="eq in todosLosEquipos" :key="eq.id" class="gradient-card rounded-xl p-6 flex items-center gap-4 hover:border-primary-fixed/50 transition-colors">
              <div class="w-16 h-16 bg-surface-container-high border border-white/10 rounded-lg flex items-center justify-center overflow-hidden shrink-0">
                <img v-if="eq.foto_ruta" :src="IMAGE_BASE_URL + eq.foto_ruta" class="w-full h-full object-cover" />
                <span v-else class="material-symbols-outlined text-on-surface-variant">shield</span>
              </div>
              <div class="overflow-hidden">
                <h3 class="text-title-md font-title-md text-on-surface uppercase truncate">{{ eq.nombre }}</h3>
                <p class="text-label-sm font-label-sm text-on-surface-variant mt-1 uppercase tracking-widest truncate">Rep: {{ eq.representante }}</p>
                <div class="mt-2 inline-block px-2 py-1 bg-surface-container-high text-primary-fixed text-[10px] font-bold uppercase rounded border border-white/10">
                  {{ eq.jugadores ? eq.jugadores.length : 0 }} Jugadores
                </div>
              </div>
            </div>

            <div v-if="!todosLosEquipos || todosLosEquipos.length === 0" class="col-span-full text-center py-8 text-on-surface-variant">
              No hay otros equipos registrados aún.
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Modal Baja -->
    <div v-if="showModalBaja" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <div class="glass-panel p-6 rounded-xl w-full max-w-md">
        <h3 class="text-title-md font-title-md text-on-surface mb-4">Dar de baja a jugador</h3>
        <p class="text-body-md font-body-md text-on-surface-variant mb-4">¿Estás seguro que deseas dar de baja a <strong class="text-white">{{ jugadorSeleccionado?.nombre }}</strong>?</p>

        <div class="mb-4">
          <label class="block text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider mb-2">Razón de la baja *</label>
          <textarea v-model="razonBaja" rows="3" class="w-full input-dark rounded-lg p-3" placeholder="Escribe el motivo aquí..." required></textarea>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button @click="showModalBaja = false" class="px-4 py-2 rounded-lg font-title-md text-on-surface-variant hover:text-white transition-colors">Cancelar</button>
          <button @click="confirmarBaja" :disabled="!razonBaja.trim()" class="bg-error text-on-error px-4 py-2 rounded-lg font-title-md transition-colors disabled:opacity-50 hover:brightness-110">Confirmar baja</button>
        </div>
      </div>
    </div>

    <!-- Modal Editar Jugador -->
    <div v-if="showModalEdit" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50 overflow-y-auto">
      <div class="glass-panel p-6 rounded-xl w-full max-w-md">
        <h3 class="text-title-md font-title-md text-on-surface mb-4">Editar Jugador</h3>
        <form @submit.prevent="guardarEdicionJugador">
          <div class="space-y-4">
            <div>
              <label class="block text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider mb-2">Nombre *</label>
              <input v-model="editJugadorForm.nombre" type="text" class="w-full input-dark rounded-lg p-3" required>
            </div>
            <div>
              <label class="block text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider mb-2">DPI *</label>
              <input v-model="editJugadorForm.dpi" type="text" class="w-full input-dark rounded-lg p-3" minlength="13" maxlength="13" required>
            </div>
            <div>
              <label class="block text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider mb-2">Foto (opcional para actualizar)</label>
              <input type="file" @change="handleEditFileChange" accept="image/*" class="w-full input-dark rounded-lg p-2">
              <div v-if="editJugadorForm.fotoUrl" class="mt-2 w-24 h-24 bg-black rounded overflow-hidden border border-white/10">
                <img :src="editJugadorForm.fotoUrl" class="w-full h-full object-cover">
              </div>
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button type="button" @click="showModalEdit = false" class="px-4 py-2 rounded-lg font-title-md text-on-surface-variant hover:text-white transition-colors">Cancelar</button>
            <button type="submit" class="btn-primary px-4 py-2 rounded-lg font-title-md">Guardar cambios</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Sub Representante -->
    <div v-if="showModalSubRep" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50 overflow-y-auto">
      <div class="glass-panel p-6 rounded-xl w-full max-w-md">
        <h3 class="text-title-md font-title-md text-on-surface mb-4">Información del Sub Representante</h3>
        <form @submit.prevent="guardarSubRep">
          <div class="space-y-4">
            <div>
              <label class="block text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider mb-2">Nombre *</label>
              <input v-model="subRepForm.nombre" type="text" class="w-full input-dark rounded-lg p-3" required>
            </div>
            <div>
              <label class="block text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider mb-2">DPI *</label>
              <input v-model="subRepForm.dpi" type="text" class="w-full input-dark rounded-lg p-3" minlength="13" maxlength="13" required>
            </div>
            <div>
              <label class="block text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider mb-2">Teléfono *</label>
              <input v-model="subRepForm.telefono" type="text" class="w-full input-dark rounded-lg p-3" minlength="8" maxlength="8" required>
            </div>
            <div>
              <label class="block text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider mb-2">Foto * (o seleccionar nueva)</label>
              <input type="file" @change="handleSubRepFileChange" accept="image/*" class="w-full input-dark rounded-lg p-2" :required="!subRepForm.fotoUrl">
              <div v-if="subRepForm.fotoUrl" class="mt-2 w-24 h-24 bg-black rounded overflow-hidden border border-white/10">
                <img :src="subRepForm.fotoUrl" class="w-full h-full object-cover">
              </div>
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-6">
            <button type="button" @click="showModalSubRep = false" class="px-4 py-2 rounded-lg font-title-md text-on-surface-variant hover:text-white transition-colors">Cancelar</button>
            <button type="submit" class="btn-primary px-4 py-2 rounded-lg font-title-md">Guardar</button>
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
