<template>
  <div class="min-h-screen flex flex-col md:flex-row bg-background text-on-background">
    <!-- Desktop SideNav -->
    <aside class="hidden md:flex flex-col h-screen w-64 bg-surface-container-lowest border-r border-outline-variant/30 shadow-xl p-gutter sticky top-0 shrink-0 z-40">
      <div class="mb-stack-lg">
        <router-link to="/" class="text-headline-lg font-headline-lg text-primary-fixed uppercase tracking-tighter">DEPORTES</router-link>
      </div>
      <nav class="flex-1 space-y-2">
        <router-link to="/" class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-variant/50 hover:text-on-surface transition-all duration-200 rounded-lg">
          <span class="material-symbols-outlined font-light">dashboard</span>
          <span class="text-label-sm font-label-sm uppercase">Inicio</span>
        </router-link>
        <span class="flex items-center gap-3 px-4 py-3 bg-primary-container text-on-primary-container rounded-lg ring-1 ring-primary-fixed/50 scale-[0.98]">
          <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">groups</span>
          <span class="text-label-sm font-label-sm uppercase font-bold">Equipos</span>
        </span>
        <router-link to="/estadisticas" class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-variant/50 hover:text-on-surface transition-all duration-200 rounded-lg">
          <span class="material-symbols-outlined font-light">bar_chart</span>
          <span class="text-label-sm font-label-sm uppercase">Estadísticas</span>
        </router-link>
        <router-link to="/historial-partidos" class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-variant/50 hover:text-on-surface transition-all duration-200 rounded-lg">
          <span class="material-symbols-outlined font-light">calendar_month</span>
          <span class="text-label-sm font-label-sm uppercase">Historial</span>
        </router-link>
      </nav>
      <div class="mt-auto pt-stack-md border-t border-outline-variant/30">
        <router-link to="/login" class="w-full flex items-center justify-center gap-2 py-3 bg-transparent border border-outline-variant text-on-surface hover:border-primary-fixed hover:text-primary-fixed transition-colors rounded-lg">
          <span class="material-symbols-outlined font-light text-sm">login</span>
          <span class="text-label-sm font-label-sm uppercase">Login</span>
        </router-link>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col max-w-full overflow-hidden">
      <!-- Mobile Top Nav -->
      <header class="md:hidden flex justify-between items-center px-container-margin py-4 w-full max-w-full bg-surface-container-lowest/80 backdrop-blur-lg top-0 z-50 sticky border-b border-outline-variant/30">
        <div class="flex items-center gap-4">
          <router-link to="/" class="text-on-surface-variant p-2">
            <span class="material-symbols-outlined">arrow_back</span>
          </router-link>
          <span class="text-headline-lg-mobile font-headline-lg-mobile text-primary-fixed tracking-tighter uppercase">DEPORTES</span>
        </div>
        <router-link to="/login" class="text-on-surface-variant">
          <span class="material-symbols-outlined">account_circle</span>
        </router-link>
      </header>

      <div class="flex-1 overflow-y-auto px-container-margin py-stack-md md:py-stack-lg bg-background">
        <!-- Header & Search Section -->
        <div class="mb-stack-lg space-y-stack-md max-w-7xl mx-auto">
          <div>
            <h2 class="text-display-lg font-display-lg uppercase text-on-surface mb-2">Equipos Registrados</h2>
            <p class="text-body-md font-body-md text-on-surface-variant max-w-2xl">Explora el directorio oficial de equipos registrados y revisa sus plantillas.</p>
          </div>
          <!-- Controls: Search -->
          <div class="flex flex-col lg:flex-row gap-gutter items-start lg:items-center bg-surface-container-low p-4 rounded-xl border border-outline-variant/50 shadow-sm">
            <div class="relative w-full lg:flex-1">
              <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant font-light">search</span>
              <input
                v-model="searchQuery"
                type="text"
                class="w-full bg-surface-container-lowest text-on-surface text-body-md font-body-md py-3 pl-12 pr-4 rounded-lg border border-outline-variant/50 focus:border-primary-fixed focus:outline-none focus:ring-1 focus:ring-primary-fixed/50 transition-colors placeholder:text-on-surface-variant/50"
                placeholder="Buscar equipo..."
              />
            </div>
          </div>
        </div>

        <!-- Loading / Empty states -->
        <div v-if="isLoading" class="max-w-7xl mx-auto text-center py-12 text-primary-fixed font-title-md font-title-md">Cargando equipos...</div>
        <div v-else-if="filteredEquipos.length === 0" class="max-w-7xl mx-auto text-center py-12 text-on-surface-variant">No se encontraron equipos registrados.</div>

        <!-- Team Grid -->
        <div v-else class="max-w-7xl mx-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-gutter">
          <article
            v-for="equipo in filteredEquipos"
            :key="equipo.id"
            class="card-gradient border border-outline-variant/30 rounded-xl p-5 flex flex-col group hover:border-primary-fixed/50 hover:shadow-[0_0_15px_rgba(185,246,63,0.1)] transition-all relative overflow-hidden glass-panel shadow-sm"
          >
            <div class="relative z-10 flex items-start justify-between mb-4">
              <div class="w-16 h-16 rounded-full border-2 border-primary-fixed overflow-hidden flex-shrink-0 bg-surface-container-lowest p-1 shadow-[0_0_10px_rgba(185,246,63,0.2)] flex items-center justify-center">
                <img
                  v-if="equipo.foto_ruta"
                  :src="IMAGE_BASE_URL + equipo.foto_ruta"
                  class="w-full h-full object-contain rounded-full"
                  :alt="equipo.nombre"
                  @error="equipo.foto_ruta = null"
                />
                <span v-else class="text-title-md font-title-md text-primary-fixed">{{ equipo.nombre?.substring(0,2).toUpperCase() || 'EQ' }}</span>
              </div>
              <div class="text-right shrink-0">
                <div class="text-label-sm font-label-sm text-on-surface-variant uppercase">Jugadores</div>
                <div class="text-title-md font-title-md text-primary-fixed leading-none">{{ equipo.jugadores ? equipo.jugadores.length : 0 }}</div>
              </div>
            </div>
            <div class="relative z-10 flex-1 mb-6">
              <h3 class="text-headline-lg-mobile font-headline-lg-mobile uppercase text-on-surface mb-1 tracking-wide group-hover:text-primary-fixed transition-colors">{{ equipo.nombre }}</h3>
              <div class="flex items-center gap-2 text-on-surface-variant">
                <span class="material-symbols-outlined text-[16px] font-light text-primary-fixed">badge</span>
                <span class="text-label-sm font-label-sm">{{ equipo.representante || 'Sin representante asignado' }}</span>
              </div>
            </div>

            <!-- Toggle plantilla -->
            <div class="relative z-10 border-t border-outline-variant/30 pt-4">
              <button
                @click="togglePlantilla(equipo.id)"
                class="w-full bg-primary-fixed/10 border border-primary-fixed/40 text-primary-fixed font-title-md text-title-md py-3 rounded-lg hover:bg-primary-fixed hover:text-on-primary-fixed transition-all flex items-center justify-center gap-2 uppercase"
              >
                <span>Ver Plantilla</span>
                <span class="material-symbols-outlined text-[20px] transition-transform duration-300" :class="{ 'rotate-180': expandedEquipo === equipo.id }">expand_more</span>
              </button>

              <div v-if="expandedEquipo === equipo.id" class="mt-4 space-y-2 animate-fadeIn">
                <div v-if="!equipo.jugadores || equipo.jugadores.length === 0" class="text-label-sm font-label-sm text-on-surface-variant italic">No hay jugadores registrados.</div>
                <div v-for="jugador in equipo.jugadores" :key="jugador.id" class="flex items-center gap-3 bg-surface-container-lowest p-2 rounded-lg">
                  <img v-if="jugador.foto_ruta" :src="IMAGE_BASE_URL + jugador.foto_ruta" class="w-8 h-8 rounded-full object-cover border border-outline-variant/50" />
                  <div v-else class="w-8 h-8 rounded-full bg-surface-container-high flex items-center justify-center text-label-sm font-label-sm text-on-surface-variant">{{ jugador.nombre?.substring(0,2) || 'JG' }}</div>
                  <div class="flex-grow min-w-0">
                    <div class="text-label-sm font-label-sm text-on-surface uppercase truncate">{{ jugador.nombre }}</div>
                    <div class="text-[10px] text-on-surface-variant tracking-widest">{{ jugador.dpi }}</div>
                  </div>
                  <div class="text-[10px] font-bold px-2 py-1 bg-surface-container border border-outline-variant/50 text-on-surface-variant rounded uppercase shrink-0">
                    {{ jugador.posicion || 'N/A' }}
                  </div>
                </div>
              </div>
            </div>
          </article>
        </div>
      </div>

      <!-- Footer -->
      <footer class="bg-surface-container-lowest border-t border-outline-variant/30 flex flex-col md:flex-row justify-between items-center px-container-margin py-stack-md w-full mt-auto z-10 gap-2">
        <div class="text-title-md font-title-md text-on-surface uppercase tracking-widest">DEPORTES</div>
        <div class="text-on-surface-variant text-label-sm font-label-sm">© 2026 DEPORTES GUATEMALA. TODOS LOS DERECHOS RESERVADOS.</div>
      </footer>
    </main>
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
