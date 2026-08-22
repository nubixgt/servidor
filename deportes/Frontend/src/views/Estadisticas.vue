<template>
  <div class="min-h-screen bg-[#0a0a0a] text-white p-6 relative overflow-hidden">
    <!-- Background Accents -->
    <div class="fixed top-[-10%] right-[-5%] w-96 h-96 bg-[#ccff00] rounded-full mix-blend-overlay filter blur-[150px] opacity-10 pointer-events-none"></div>
    <div class="fixed bottom-[-10%] left-[-5%] w-96 h-96 bg-[#ccff00] rounded-full mix-blend-overlay filter blur-[150px] opacity-10 pointer-events-none"></div>

    <div class="max-w-7xl mx-auto z-10 relative">
      <!-- Header -->
      <div class="flex items-center justify-between mb-12 border-b border-gray-800 pb-6">
        <div>
          <h1 class="text-4xl md:text-5xl font-black italic tracking-tighter">ESTADÍSTICAS</h1>
          <p class="text-gray-400 mt-2 text-sm">Resumen global de todos los torneos y partidos</p>
        </div>
        <router-link to="/" class="group flex items-center gap-2 text-sm text-gray-400 hover:text-[#ccff00] transition-colors">
          <i class="fas fa-arrow-left"></i>
          Volver al Inicio
        </router-link>
      </div>

      <div v-if="loading" class="flex justify-center py-20">
        <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-[#ccff00]"></div>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Máximo Goleador -->
        <div class="bg-[#121212] border border-gray-800 rounded-2xl overflow-hidden shadow-2xl flex flex-col h-full">
          <div class="bg-gray-900/50 p-4 border-b border-gray-800 flex items-center justify-between">
            <h3 class="font-black italic text-xl tracking-tight">GOLEADORES</h3>
            <i class="fas fa-futbol text-[#ccff00]"></i>
          </div>
          <div class="p-6 flex-1 flex flex-col gap-4">
            <div v-for="(jugador, index) in goleadores" :key="jugador.id" 
                 class="flex items-center gap-4 p-3 rounded-xl bg-gray-800/30 hover:bg-gray-800/50 transition-colors"
                 :class="{'border border-[#ccff00]/30 shadow-[0_0_15px_rgba(204,255,0,0.1)]': index === 0}">
              <div class="font-black text-2xl text-gray-600 w-6 text-center" :class="{'text-[#ccff00]': index === 0}">{{ index + 1 }}</div>
              <img :src="getFotoUrl(jugador.foto_ruta)" class="w-12 h-12 rounded-full object-cover border border-gray-700" @error="handleImageError" />
              <div class="flex-1 min-w-0">
                <p class="font-bold truncate" :class="{'text-[#ccff00]': index === 0}">{{ jugador.nombre }}</p>
                <p class="text-xs text-gray-400 truncate">{{ jugador.equipo_nombre }}</p>
              </div>
              <div class="text-right">
                <p class="font-black text-xl">{{ jugador.total_goles }}</p>
                <p class="text-[10px] text-gray-500 uppercase">Goles</p>
              </div>
            </div>
            <div v-if="!goleadores.length" class="text-center text-gray-500 py-8 text-sm italic">
              No hay datos registrados aún.
            </div>
          </div>
        </div>

        <!-- Portero Menos Vencido -->
        <div class="bg-[#121212] border border-gray-800 rounded-2xl overflow-hidden shadow-2xl flex flex-col h-full">
          <div class="bg-gray-900/50 p-4 border-b border-gray-800 flex items-center justify-between">
            <h3 class="font-black italic text-xl tracking-tight">PORTEROS</h3>
            <i class="fas fa-hands text-[#ccff00]"></i>
          </div>
          <div class="p-6 flex-1 flex flex-col gap-4">
            <div v-for="(portero, index) in porteros" :key="portero.id" 
                 class="flex items-center gap-4 p-3 rounded-xl bg-gray-800/30 hover:bg-gray-800/50 transition-colors"
                 :class="{'border border-[#ccff00]/30 shadow-[0_0_15px_rgba(204,255,0,0.1)]': index === 0}">
              <div class="font-black text-2xl text-gray-600 w-6 text-center" :class="{'text-[#ccff00]': index === 0}">{{ index + 1 }}</div>
              <img :src="getFotoUrl(portero.foto_ruta)" class="w-12 h-12 rounded-full object-cover border border-gray-700" @error="handleImageError" />
              <div class="flex-1 min-w-0">
                <p class="font-bold truncate" :class="{'text-[#ccff00]': index === 0}">{{ portero.nombre }}</p>
                <p class="text-xs text-gray-400 truncate">{{ portero.equipo_nombre }}</p>
              </div>
              <div class="text-right">
                <p class="font-black text-xl">{{ portero.total_goles_recibidos }}</p>
                <p class="text-[10px] text-gray-500 uppercase">Goles Rec.</p>
              </div>
            </div>
            <div v-if="!porteros.length" class="text-center text-gray-500 py-8 text-sm italic">
              No hay datos registrados aún.
            </div>
          </div>
        </div>

        <!-- Equipos Más Tarjetas -->
        <div class="bg-[#121212] border border-gray-800 rounded-2xl overflow-hidden shadow-2xl flex flex-col h-full">
          <div class="bg-gray-900/50 p-4 border-b border-gray-800 flex items-center justify-between">
            <h3 class="font-black italic text-xl tracking-tight">TARJETAS</h3>
            <div class="flex gap-1">
              <div class="w-3 h-4 bg-yellow-400 rounded-sm rounded-tr-lg"></div>
              <div class="w-3 h-4 bg-red-500 rounded-sm rounded-tr-lg"></div>
            </div>
          </div>
          <div class="p-6 flex-1 flex flex-col gap-4">
            <div v-for="(equipo, index) in tarjetasEquipos" :key="equipo.id" 
                 class="flex items-center gap-4 p-3 rounded-xl bg-gray-800/30 hover:bg-gray-800/50 transition-colors">
              <div class="font-black text-2xl text-gray-600 w-6 text-center">{{ index + 1 }}</div>
              <img :src="getFotoUrl(equipo.foto_ruta)" class="w-12 h-12 rounded-full object-cover border border-gray-700" @error="handleImageError" />
              <div class="flex-1 min-w-0">
                <p class="font-bold truncate">{{ equipo.nombre }}</p>
                <div class="flex gap-3 text-xs mt-1">
                  <span class="text-yellow-400"><i class="fas fa-square mr-1"></i>{{ equipo.total_amarillas }}</span>
                  <span class="text-red-500"><i class="fas fa-square mr-1"></i>{{ equipo.total_rojas }}</span>
                </div>
              </div>
              <div class="text-right">
                <p class="font-black text-xl">{{ equipo.total_tarjetas }}</p>
                <p class="text-[10px] text-gray-500 uppercase">Total</p>
              </div>
            </div>
            <div v-if="!tarjetasEquipos.length" class="text-center text-gray-500 py-8 text-sm italic">
              No hay datos registrados aún.
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

const loading = ref(true);
const goleadores = ref([]);
const porteros = ref([]);
const tarjetasEquipos = ref([]);

const defaultAvatar = 'https://ui-avatars.com/api/?name=Player&background=random';

const getFotoUrl = (ruta) => {
  if (!ruta) return defaultAvatar;
  return `${IMAGE_BASE_URL}${ruta}`;
};

const handleImageError = (e) => {
  e.target.src = defaultAvatar;
};

const fetchData = async () => {
  loading.value = true;
  try {
    const [resGoles, resPorteros, resTarjetas] = await Promise.all([
      api.get(`/rankings/goleadores?limit=5`),
      api.get(`/rankings/porteros?limit=5`),
      api.get(`/rankings/tarjetas-equipos?limit=5`)
    ]);

    goleadores.value = resGoles.data;
    porteros.value = resPorteros.data;
    tarjetasEquipos.value = resTarjetas.data;

  } catch (error) {
    console.error('Error fetching stats:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchData();
});
</script>
