<template>
  <div class="p-4 md:p-8">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-3xl font-black italic tracking-tight">HISTORIAL DE PARTIDOS</h1>
        <p class="text-gray-400 text-sm mt-1">Todos los resultados y estadísticas registrados</p>
      </div>
      <button @click="$router.go(-1)" class="text-gray-400 hover:text-white transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>Volver
      </button>
    </div>

    <div v-if="loading" class="flex justify-center py-20">
      <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-[#ccff00]"></div>
    </div>

    <div v-else-if="!partidos.length" class="text-center py-20 bg-[#121212] rounded-2xl border border-gray-800">
      <i class="fas fa-calendar-times text-4xl text-gray-600 mb-4"></i>
      <h2 class="text-xl font-bold text-gray-400">No hay partidos registrados</h2>
      <p class="text-gray-500 mt-2">Los resultados aparecerán aquí una vez que se registren.</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="partido in partidos" :key="partido.id" 
           class="bg-[#121212] border border-gray-800 rounded-2xl overflow-hidden hover:border-[#ccff00]/50 transition-colors cursor-pointer"
           @click="verDetalle(partido.id)">
        <div class="p-3 bg-gray-900/50 flex justify-between items-center text-xs text-gray-400 border-b border-gray-800">
          <span>{{ formatDate(partido.fecha) }}</span>
          <button v-if="userRole === 'admin'" @click.stop="confirmDelete(partido.id)" class="text-red-500 hover:text-red-400 p-1 bg-red-500/10 rounded-full w-7 h-7 flex items-center justify-center transition-colors" title="Eliminar partido">
            <i class="fas fa-trash-alt"></i>
          </button>
        </div>
        <div class="p-6">
          <div class="flex items-center justify-between">
            <!-- Local -->
            <div class="flex flex-col items-center gap-2 flex-1">
              <img :src="getFotoUrl(partido.equipo_local_foto)" class="w-16 h-16 rounded-full object-cover border border-gray-700" @error="handleImageError"/>
              <span class="font-bold text-center text-sm truncate w-full">{{ partido.equipo_local_nombre }}</span>
            </div>
            
            <!-- Marcador -->
            <div class="px-4 text-center">
              <div class="bg-black border border-gray-800 rounded-lg px-4 py-2 font-black text-2xl tracking-widest text-[#ccff00] shadow-inner">
                {{ partido.goles_local }} - {{ partido.goles_visitante }}
              </div>
            </div>

            <!-- Visitante -->
            <div class="flex flex-col items-center gap-2 flex-1">
              <img :src="getFotoUrl(partido.equipo_visitante_foto)" class="w-16 h-16 rounded-full object-cover border border-gray-700" @error="handleImageError"/>
              <span class="font-bold text-center text-sm truncate w-full">{{ partido.equipo_visitante_nombre }}</span>
            </div>
          </div>
        </div>
        <div class="bg-gray-800/30 p-3 text-center text-xs text-[#ccff00] font-bold hover:bg-[#ccff00] hover:text-black transition-colors">
          VER ESTADÍSTICAS
        </div>
      </div>
    </div>

    <!-- Modal Detalle -->
    <div v-if="partidoSeleccionado" class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4 backdrop-blur-sm" @click.self="partidoSeleccionado = null">
      <div class="bg-[#121212] border border-gray-800 rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col shadow-2xl">
        <!-- Modal Header -->
        <div class="p-6 border-b border-gray-800 flex justify-between items-center bg-gray-900/80">
          <h2 class="text-xl font-black italic">DETALLE DEL PARTIDO</h2>
          <div class="flex items-center gap-4">
            <button v-if="userRole === 'admin'" @click="confirmDelete(partidoSeleccionado.id)" class="text-red-500 hover:text-red-400 bg-red-500/10 px-3 py-1.5 rounded-lg text-sm font-bold flex items-center gap-2 transition-colors">
              <i class="fas fa-trash-alt"></i> Eliminar
            </button>
            <button @click="partidoSeleccionado = null" class="text-gray-400 hover:text-white">
              <i class="fas fa-times text-xl"></i>
            </button>
          </div>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto">
          <!-- Resumen Marcador -->
          <div class="flex items-center justify-center gap-8 mb-8">
            <div class="text-center w-1/3">
              <img :src="getFotoUrl(partidoSeleccionado.equipo_local_foto)" class="w-20 h-20 rounded-full mx-auto mb-2 object-cover" @error="handleImageError"/>
              <p class="font-bold text-lg">{{ partidoSeleccionado.equipo_local_nombre }}</p>
            </div>
            <div class="font-black text-5xl text-[#ccff00]">
              {{ partidoSeleccionado.goles_local }} - {{ partidoSeleccionado.goles_visitante }}
            </div>
            <div class="text-center w-1/3">
              <img :src="getFotoUrl(partidoSeleccionado.equipo_visitante_foto)" class="w-20 h-20 rounded-full mx-auto mb-2 object-cover" @error="handleImageError"/>
              <p class="font-bold text-lg">{{ partidoSeleccionado.equipo_visitante_nombre }}</p>
            </div>
          </div>

          <!-- Estadísticas por Equipo -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Local Stats -->
            <div>
              <h3 class="font-bold text-[#ccff00] mb-3 border-b border-gray-800 pb-2">Estadísticas {{ partidoSeleccionado.equipo_local_nombre }}</h3>
              <div class="space-y-2">
                <div v-for="est in getEstadisticasEquipo(partidoSeleccionado.equipo_local_id)" :key="est.id" class="bg-gray-800/30 p-3 rounded-lg flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <img :src="getFotoUrl(est.jugador_foto)" class="w-8 h-8 rounded-full" @error="handleImageError" />
                    <div>
                      <p class="text-sm font-bold">{{ est.jugador_nombre }}</p>
                      <p class="text-[10px] text-gray-500">{{ est.posicion }}</p>
                    </div>
                  </div>
                  <div class="flex gap-3 text-xs">
                    <span v-if="est.goles > 0" title="Goles"><i class="fas fa-futbol text-[#ccff00] mr-1"></i>{{ est.goles }}</span>
                    <span v-if="est.tarjetas_amarillas > 0" title="Amarillas"><i class="fas fa-square text-yellow-500 mr-1"></i>{{ est.tarjetas_amarillas }}</span>
                    <span v-if="est.tarjetas_rojas > 0" title="Rojas"><i class="fas fa-square text-red-500 mr-1"></i>{{ est.tarjetas_rojas }}</span>
                    <span v-if="est.jugo_como_portero" title="Goles Recibidos"><i class="fas fa-hands text-gray-400 mr-1"></i>-{{ est.goles_recibidos }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Visitante Stats -->
            <div>
              <h3 class="font-bold text-white mb-3 border-b border-gray-800 pb-2">Estadísticas {{ partidoSeleccionado.equipo_visitante_nombre }}</h3>
              <div class="space-y-2">
                <div v-for="est in getEstadisticasEquipo(partidoSeleccionado.equipo_visitante_id)" :key="est.id" class="bg-gray-800/30 p-3 rounded-lg flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <img :src="getFotoUrl(est.jugador_foto)" class="w-8 h-8 rounded-full" @error="handleImageError" />
                    <div>
                      <p class="text-sm font-bold">{{ est.jugador_nombre }}</p>
                      <p class="text-[10px] text-gray-500">{{ est.posicion }}</p>
                    </div>
                  </div>
                  <div class="flex gap-3 text-xs">
                    <span v-if="est.goles > 0" title="Goles"><i class="fas fa-futbol text-white mr-1"></i>{{ est.goles }}</span>
                    <span v-if="est.tarjetas_amarillas > 0" title="Amarillas"><i class="fas fa-square text-yellow-500 mr-1"></i>{{ est.tarjetas_amarillas }}</span>
                    <span v-if="est.tarjetas_rojas > 0" title="Rojas"><i class="fas fa-square text-red-500 mr-1"></i>{{ est.tarjetas_rojas }}</span>
                    <span v-if="est.jugo_como_portero" title="Goles Recibidos"><i class="fas fa-hands text-gray-400 mr-1"></i>-{{ est.goles_recibidos }}</span>
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
import Swal from 'sweetalert2';

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

const confirmDelete = (id) => {
  Swal.fire({
    title: '¿Eliminar partido?',
    text: "Esta acción no se puede deshacer. Se eliminarán también todas las estadísticas asociadas.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#374151',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    background: '#121212',
    color: '#ffffff'
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await api.delete(`/partidos/${id}`, { headers: getAuthHeaders() });
        partidoSeleccionado.value = null; // Cerrar modal si está abierto
        await fetchPartidos(); // Recargar lista
        Swal.fire({
          title: 'Eliminado',
          text: 'El partido ha sido eliminado.',
          icon: 'success',
          background: '#121212',
          color: '#ffffff'
        });
      } catch (error) {
        console.error(error);
        Swal.fire({
          title: 'Error',
          text: 'Hubo un problema al eliminar el partido.',
          icon: 'error',
          background: '#121212',
          color: '#ffffff'
        });
      }
    }
  });
};

onMounted(() => {
  fetchPartidos();
});
</script>
