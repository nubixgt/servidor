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
        <router-link to="/estadisticas" class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-variant/50 hover:text-on-surface transition-all duration-200 rounded-lg">
          <span class="material-symbols-outlined font-light">bar_chart</span>
          <span class="text-label-sm font-label-sm uppercase">Estadísticas</span>
        </router-link>
        <span class="flex items-center gap-3 px-4 py-3 bg-primary-container text-on-primary-container rounded-lg ring-1 ring-primary-fixed/50 scale-[0.98]">
          <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">sports_soccer</span>
          <span class="text-label-sm font-label-sm uppercase font-bold">Historial</span>
        </span>
      </nav>
      <div class="mt-auto pt-stack-md border-t border-outline-variant/30">
        <router-link to="/login" class="w-full flex items-center justify-center gap-2 py-3 bg-transparent border border-outline-variant text-on-surface hover:border-primary-fixed hover:text-primary-fixed transition-colors rounded-lg">
          <span class="material-symbols-outlined font-light text-sm">login</span>
          <span class="text-label-sm font-label-sm uppercase">Login</span>
        </router-link>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col max-w-full overflow-hidden">
      <!-- Mobile Top Nav -->
      <header class="md:hidden flex justify-between items-center px-container-margin py-4 w-full bg-background/80 backdrop-blur-lg border-b border-white/10 sticky top-0 z-50">
        <button @click="$router.go(-1)" class="flex items-center gap-2 text-on-surface-variant hover:text-primary-fixed transition-colors">
          <span class="material-symbols-outlined">arrow_back</span>
        </button>
        <h1 class="text-headline-lg-mobile font-headline-lg-mobile text-primary-fixed tracking-tighter uppercase">DEPORTES</h1>
      </header>

      <div class="px-container-margin md:px-stack-lg py-stack-lg flex-1 overflow-y-auto">
        <div class="flex justify-between items-end mb-stack-lg">
          <div>
            <h2 class="text-headline-lg font-headline-lg md:text-display-lg md:font-display-lg uppercase tracking-tighter">Historial de Partidos</h2>
            <p class="text-on-surface-variant text-body-md mt-2 max-w-xl">Revisa resultados pasados y estadísticas de jugadores de la temporada.</p>
          </div>
          <button @click="$router.go(-1)" class="hidden md:flex items-center gap-2 px-4 py-2 rounded-full border border-white/10 hover:border-primary-fixed/50 text-label-sm font-label-sm transition-colors text-on-surface bg-[#252525]">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            Volver
          </button>
        </div>

        <div v-if="loading" class="flex justify-center py-20">
          <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-primary-fixed"></div>
        </div>

        <div v-else-if="!partidos.length" class="text-center py-20 glass-card rounded-2xl border border-outline-variant/30">
          <span class="material-symbols-outlined text-4xl text-on-surface-variant mb-4">event_busy</span>
          <h2 class="text-title-md font-title-md text-on-surface-variant">No hay partidos registrados</h2>
          <p class="text-on-surface-variant/70 text-body-md mt-2">Los resultados aparecerán aquí una vez que se registren.</p>
        </div>

        <!-- Match Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-stack-md">
          <article
            v-for="partido in partidos"
            :key="partido.id"
            class="glass-card rounded-xl p-5 relative overflow-hidden group cursor-pointer neon-glow transition-all flex flex-col justify-between h-full"
            tabindex="0"
            @click="verDetalle(partido.id)"
          >
            <div class="flex justify-between items-start mb-4">
              <span class="text-label-sm font-label-sm text-on-surface-variant">{{ formatDate(partido.fecha) }}</span>
              <button
                v-if="userRole === 'admin'"
                @click.stop="confirmDelete(partido.id)"
                class="text-error hover:text-error p-1 bg-error/10 rounded-full w-7 h-7 flex items-center justify-center transition-colors"
                title="Eliminar partido"
              >
                <span class="material-symbols-outlined text-[16px]">delete</span>
              </button>
              <span v-else class="material-symbols-outlined text-on-surface-variant group-hover:text-primary-fixed transition-colors">arrow_forward</span>
            </div>
            <div class="flex justify-between items-center mb-6">
              <div class="flex items-center gap-3 min-w-0">
                <img :src="getFotoUrl(partido.equipo_local_foto)" class="w-10 h-10 rounded-full object-cover border border-outline-variant/50 shrink-0" @error="handleImageError" />
                <span class="font-bold text-on-surface truncate">{{ partido.equipo_local_nombre }}</span>
              </div>
              <span class="text-title-md font-bold text-on-surface shrink-0 ml-2">{{ partido.goles_local }}</span>
            </div>
            <div class="flex justify-between items-center">
              <div class="flex items-center gap-3 min-w-0">
                <img :src="getFotoUrl(partido.equipo_visitante_foto)" class="w-10 h-10 rounded-full object-cover border border-outline-variant/50 shrink-0" @error="handleImageError" />
                <span class="font-bold text-on-surface truncate">{{ partido.equipo_visitante_nombre }}</span>
              </div>
              <span class="text-title-md font-bold text-primary-fixed shrink-0 ml-2">{{ partido.goles_visitante }}</span>
            </div>
            <div class="mt-4 pt-3 border-t border-white/5 text-center">
              <span class="text-[10px] text-primary-fixed uppercase tracking-widest font-bold">Ver Estadísticas</span>
            </div>
          </article>
        </div>
      </div>

      <!-- Footer -->
      <footer class="mt-auto border-t border-white/5 bg-surface-container-lowest py-stack-md px-container-margin w-full flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="text-title-md font-title-md text-on-surface">DEPORTES GUATEMALA</div>
        <div class="text-label-sm font-label-sm text-on-surface-variant opacity-50">© 2026 DEPORTES GUATEMALA. TODOS LOS DERECHOS RESERVADOS.</div>
      </footer>
    </main>

    <!-- Modal Detalle -->
    <div v-if="partidoSeleccionado" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" @click.self="partidoSeleccionado = null">
      <div class="glass-card w-full max-w-3xl rounded-xl shadow-2xl flex flex-col max-h-[90vh] overflow-hidden">
        <!-- Modal Header -->
        <div class="p-6 border-b border-white/10 flex justify-between items-center bg-[#1A1A1A]">
          <h3 class="text-headline-lg font-headline-lg uppercase text-white tracking-tight">Detalle del Partido</h3>
          <div class="flex items-center gap-4">
            <button
              v-if="userRole === 'admin'"
              @click="confirmDelete(partidoSeleccionado.id)"
              class="text-error hover:text-error bg-error/10 px-3 py-1.5 rounded-lg text-label-sm font-label-sm font-bold flex items-center gap-2 transition-colors uppercase"
            >
              <span class="material-symbols-outlined text-[18px]">delete</span> Eliminar
            </button>
            <button @click="partidoSeleccionado = null" class="text-on-surface-variant hover:text-white p-2 rounded-full hover:bg-white/5 transition-colors">
              <span class="material-symbols-outlined">close</span>
            </button>
          </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto flex-1">
          <div class="flex items-center justify-between mb-8 bg-[#252525] p-6 rounded-lg border border-white/5">
            <div class="flex flex-col items-center gap-2 w-1/3">
              <img :src="getFotoUrl(partidoSeleccionado.equipo_local_foto)" class="w-16 h-16 rounded-full object-cover" @error="handleImageError" />
              <span class="font-bold text-center text-on-surface truncate w-full">{{ partidoSeleccionado.equipo_local_nombre }}</span>
            </div>
            <div class="text-center shrink-0">
              <div class="text-display-lg font-display-lg text-primary-fixed leading-none">{{ partidoSeleccionado.goles_local }} - {{ partidoSeleccionado.goles_visitante }}</div>
              <div class="text-label-sm font-label-sm text-on-surface-variant mt-2 uppercase">Final</div>
            </div>
            <div class="flex flex-col items-center gap-2 w-1/3">
              <img :src="getFotoUrl(partidoSeleccionado.equipo_visitante_foto)" class="w-16 h-16 rounded-full object-cover" @error="handleImageError" />
              <span class="font-bold text-center text-on-surface truncate w-full">{{ partidoSeleccionado.equipo_visitante_nombre }}</span>
            </div>
          </div>

          <!-- Estadísticas por Equipo -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Local Stats -->
            <div>
              <h4 class="text-title-md font-title-md text-primary-fixed mb-3 border-b border-white/5 pb-2">Estadísticas {{ partidoSeleccionado.equipo_local_nombre }}</h4>
              <div class="space-y-2">
                <div v-for="est in getEstadisticasEquipo(partidoSeleccionado.equipo_local_id)" :key="est.id" class="bg-[#1A1A1A] p-3 rounded-lg border border-white/5 flex items-center justify-between">
                  <div class="flex items-center gap-3 min-w-0">
                    <img :src="getFotoUrl(est.jugador_foto)" class="w-8 h-8 rounded-full shrink-0" @error="handleImageError" />
                    <div class="min-w-0">
                      <p class="text-body-md font-bold text-on-surface truncate">{{ est.jugador_nombre }}</p>
                      <p class="text-[10px] text-on-surface-variant">{{ est.posicion }}</p>
                    </div>
                  </div>
                  <div class="flex gap-3 text-label-sm font-label-sm text-on-surface-variant shrink-0">
                    <span v-if="est.goles > 0" title="Goles" class="text-primary-fixed flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">sports_soccer</span>{{ est.goles }}</span>
                    <span v-if="est.tarjetas_amarillas > 0" title="Amarillas" class="flex items-center gap-1 text-yellow-500"><span class="material-symbols-outlined text-[16px]">square</span>{{ est.tarjetas_amarillas }}</span>
                    <span v-if="est.tarjetas_rojas > 0" title="Rojas" class="flex items-center gap-1 text-error"><span class="material-symbols-outlined text-[16px]">square</span>{{ est.tarjetas_rojas }}</span>
                    <span v-if="est.jugo_como_portero" title="Goles Recibidos" class="flex items-center gap-1">-{{ est.goles_recibidos }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Visitante Stats -->
            <div>
              <h4 class="text-title-md font-title-md text-on-surface mb-3 border-b border-white/5 pb-2">Estadísticas {{ partidoSeleccionado.equipo_visitante_nombre }}</h4>
              <div class="space-y-2">
                <div v-for="est in getEstadisticasEquipo(partidoSeleccionado.equipo_visitante_id)" :key="est.id" class="bg-[#1A1A1A] p-3 rounded-lg border border-white/5 flex items-center justify-between">
                  <div class="flex items-center gap-3 min-w-0">
                    <img :src="getFotoUrl(est.jugador_foto)" class="w-8 h-8 rounded-full shrink-0" @error="handleImageError" />
                    <div class="min-w-0">
                      <p class="text-body-md font-bold text-on-surface truncate">{{ est.jugador_nombre }}</p>
                      <p class="text-[10px] text-on-surface-variant">{{ est.posicion }}</p>
                    </div>
                  </div>
                  <div class="flex gap-3 text-label-sm font-label-sm text-on-surface-variant shrink-0">
                    <span v-if="est.goles > 0" title="Goles" class="flex items-center gap-1 text-white"><span class="material-symbols-outlined text-[16px]">sports_soccer</span>{{ est.goles }}</span>
                    <span v-if="est.tarjetas_amarillas > 0" title="Amarillas" class="flex items-center gap-1 text-yellow-500"><span class="material-symbols-outlined text-[16px]">square</span>{{ est.tarjetas_amarillas }}</span>
                    <span v-if="est.tarjetas_rojas > 0" title="Rojas" class="flex items-center gap-1 text-error"><span class="material-symbols-outlined text-[16px]">square</span>{{ est.tarjetas_rojas }}</span>
                    <span v-if="est.jugo_como_portero" title="Goles Recibidos" class="flex items-center gap-1">-{{ est.goles_recibidos }}</span>
                  </div>
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
import { ref, onMounted } from 'vue';
import api, { IMAGE_BASE_URL } from '../services/api';
import { confirmarEliminar, alertaExito, alertaError } from '../utils/alertas';

const loading = ref(true);
const partidos = ref([]);
const partidoSeleccionado = ref(null);
const defaultAvatar = 'https://ui-avatars.com/api/?name=Team&background=random';

const userRole = localStorage.getItem('deportes_rol');
const token = localStorage.getItem('deportes_token');

const getAuthHeaders = () => {
  return {
    'Authorization': `Bearer ${token}`
  };
};

const getFotoUrl = (ruta) => {
  if (!ruta) return defaultAvatar;
  return `${IMAGE_BASE_URL}${ruta}`;
};

const handleImageError = (e) => {
  e.target.src = defaultAvatar;
};

const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute:'2-digit' };
  return new Date(dateString).toLocaleDateString('es-ES', options);
};

const fetchPartidos = async () => {
  loading.value = true;
  try {
    const res = await api.get(`/partidos`);
    partidos.value = res.data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const verDetalle = async (id) => {
  try {
    const res = await api.get(`/partidos/${id}`);
    partidoSeleccionado.value = res.data;
  } catch (e) {
    console.error(e);
  }
};

const getEstadisticasEquipo = (equipoId) => {
  if (!partidoSeleccionado.value || !partidoSeleccionado.value.estadisticas) return [];
  return partidoSeleccionado.value.estadisticas.filter(e => e.equipo_id === equipoId);
};

const confirmDelete = async (id) => {
  const result = await confirmarEliminar({
    title: '¿Eliminar partido?',
    text: 'Esta acción no se puede deshacer. Se eliminarán también todas las estadísticas asociadas.'
  });

  if (!result.isConfirmed) return;

  try {
    await api.post(`/partidos/${id}/eliminar`, {}, { headers: getAuthHeaders() });
    partidoSeleccionado.value = null; // Cerrar modal si está abierto
    await fetchPartidos(); // Recargar lista
    alertaExito('Eliminado', 'El partido ha sido eliminado.');
  } catch (error) {
    console.error(error);
    alertaError('Error', 'Hubo un problema al eliminar el partido.');
  }
};

onMounted(() => {
  fetchPartidos();
});
</script>
