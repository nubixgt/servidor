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
        <router-link to="/mi-equipo" class="flex items-center gap-3 px-4 py-3 rounded-lg text-on-surface-variant hover:bg-primary-container/10 transition-all duration-200">
          <span class="material-symbols-outlined">groups</span>
          <span class="text-label-sm font-label-sm font-bold">Volver a Mi Equipo</span>
        </router-link>
        <div class="flex items-center gap-3 px-4 py-3 rounded-lg bg-primary-container text-on-primary-container ring-1 ring-primary-fixed/50">
          <span class="material-symbols-outlined">person_off</span>
          <span class="text-label-sm font-label-sm font-bold">Jugadores Inactivos</span>
        </div>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 md:ml-64 p-container-margin flex flex-col gap-stack-lg">
      <header class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-white/10 pb-stack-md">
        <div>
          <p class="text-error text-label-sm font-label-sm uppercase tracking-widest flex items-center gap-2 mb-1">
            <span class="w-6 h-px bg-error"></span> Historial de Bajas
          </p>
          <h1 class="text-headline-lg font-headline-lg text-white uppercase tracking-tight">Jugadores Inactivos</h1>
          <p class="text-on-surface-variant text-body-md font-body-md mt-1">Consulta los jugadores que han sido dados de baja de tu equipo.</p>
        </div>
      </header>

      <div v-if="isLoading" class="text-center py-12 text-on-surface-variant">Cargando información...</div>

      <div v-else-if="error" class="bg-error-container/20 border border-error-container/50 p-4 rounded-lg text-error">
        {{ error }}
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-gutter">
        <div
          v-for="jugador in jugadoresInactivos"
          :key="jugador.id"
          @click="verRazon(jugador)"
          class="card-gradient rounded-xl p-4 flex flex-col gap-3 group cursor-pointer hover:border-primary-fixed/50 transition-colors"
        >
          <div class="flex justify-between items-start">
            <div class="w-16 h-16 rounded-lg bg-surface-container-high border border-white/10 flex items-center justify-center overflow-hidden shrink-0 grayscale group-hover:grayscale-0 transition-all">
              <img v-if="jugador.foto_ruta" :src="IMAGE_BASE_URL + jugador.foto_ruta" class="w-full h-full object-cover" alt="Jugador" />
              <span v-else class="material-symbols-outlined text-on-surface-variant">person</span>
            </div>
            <span class="px-2 py-1 rounded-full bg-error/10 text-error text-[10px] font-bold uppercase tracking-wider border border-error/20 flex items-center gap-1 shrink-0">
              <span class="w-1.5 h-1.5 rounded-full bg-error"></span> Baja
            </span>
          </div>
          <div>
            <h3 class="text-title-md font-title-md text-on-surface mb-1 truncate">{{ jugador.nombre }}</h3>
            <p class="text-label-sm font-label-sm text-on-surface-variant font-mono truncate">DPI: {{ jugador.dpi }}</p>
            <p class="text-label-sm font-label-sm text-error flex items-center gap-1 mt-2">
              <span class="material-symbols-outlined text-[14px]">event</span> {{ formatearFecha(jugador.fecha_baja) }}
            </p>
          </div>
        </div>

        <div v-if="!jugadoresInactivos || jugadoresInactivos.length === 0" class="col-span-full text-center py-12 card-gradient rounded-xl">
          <span class="material-symbols-outlined text-on-surface-variant text-5xl mb-4 block">person_off</span>
          <p class="text-on-surface-variant font-title-md font-bold uppercase tracking-wider text-sm">No hay jugadores inactivos</p>
        </div>
      </div>
    </main>

    <!-- Modal Razón -->
    <div v-if="jugadorSeleccionado" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <div class="glass-panel relative w-full max-w-md rounded-xl p-stack-md flex flex-col gap-4 shadow-2xl">
        <button @click="jugadorSeleccionado = null" class="absolute top-4 right-4 text-on-surface-variant hover:text-primary-fixed transition-colors">
          <span class="material-symbols-outlined">close</span>
        </button>

        <div class="flex items-center gap-4">
          <div class="w-16 h-16 rounded-lg overflow-hidden border border-primary-fixed/30 grayscale bg-surface-container-high flex items-center justify-center shrink-0">
            <img v-if="jugadorSeleccionado.foto_ruta" :src="IMAGE_BASE_URL + jugadorSeleccionado.foto_ruta" class="w-full h-full object-cover" alt="Jugador" />
            <span v-else class="material-symbols-outlined text-on-surface-variant">person</span>
          </div>
          <div>
            <h2 class="text-headline-lg-mobile font-headline-lg-mobile text-on-surface uppercase tracking-tight">{{ jugadorSeleccionado.nombre }}</h2>
            <span class="text-label-sm font-label-sm text-error flex items-center gap-1 mt-1">
              <span class="material-symbols-outlined text-[14px]">warning</span> Dado de baja el {{ formatearFecha(jugadorSeleccionado.fecha_baja) }}
            </span>
          </div>
        </div>

        <div class="bg-[#1A1A1A]/80 rounded-lg p-4 border border-white/5">
          <h4 class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider mb-2">Motivo registrado</h4>
          <p class="text-body-md font-body-md text-on-surface leading-relaxed whitespace-pre-line">{{ jugadorSeleccionado.razon_baja || 'Sin motivo especificado.' }}</p>
        </div>

        <div class="mt-2 pt-4 border-t border-white/10 flex justify-end">
          <button
            @click="jugadorSeleccionado = null"
            class="px-4 py-2 rounded-lg border border-white/20 text-on-surface text-label-sm font-label-sm hover:bg-white/5 transition-colors"
          >Cerrar</button>
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
