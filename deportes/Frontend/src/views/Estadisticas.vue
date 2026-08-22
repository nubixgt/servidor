<template>
  <div class="p-4 md:p-8">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-3xl font-black italic tracking-tight">ESTADÍSTICAS</h1>
        <p class="text-gray-400 text-sm mt-1">Líderes y rankings del torneo</p>
      </div>
      <button @click="$router.go(-1)" class="text-gray-400 hover:text-white transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>Volver
      </button>
    </div>

    <!-- TABS NAV -->
    <div class="flex overflow-x-auto gap-3 pb-4 mb-8 snap-x hide-scrollbar scroll-smooth">
      <button @click="activeTab = 'goleadoresGlobal'" :class="['shrink-0 px-6 py-3 rounded-full font-bold text-sm transition-all snap-start', activeTab === 'goleadoresGlobal' ? 'bg-[#ccff00] text-black shadow-[0_0_15px_rgba(204,255,0,0.4)]' : 'bg-[#121212] text-gray-400 border border-gray-800 hover:border-[#ccff00]/50']">
        🏆 Goleadores Global
      </button>
      <button @click="activeTab = 'goleadoresEquipo'" :class="['shrink-0 px-6 py-3 rounded-full font-bold text-sm transition-all snap-start', activeTab === 'goleadoresEquipo' ? 'bg-[#ccff00] text-black shadow-[0_0_15px_rgba(204,255,0,0.4)]' : 'bg-[#121212] text-gray-400 border border-gray-800 hover:border-[#ccff00]/50']">
        ⚽ Goleadores por Equipo
      </button>
      <button @click="activeTab = 'porterosGlobal'" :class="['shrink-0 px-6 py-3 rounded-full font-bold text-sm transition-all snap-start', activeTab === 'porterosGlobal' ? 'bg-white text-black shadow-[0_0_15px_rgba(255,255,255,0.4)]' : 'bg-[#121212] text-gray-400 border border-gray-800 hover:border-white/50']">
        🧤 Porteros Global
      </button>
      <button @click="activeTab = 'porterosEquipo'" :class="['shrink-0 px-6 py-3 rounded-full font-bold text-sm transition-all snap-start', activeTab === 'porterosEquipo' ? 'bg-white text-black shadow-[0_0_15px_rgba(255,255,255,0.4)]' : 'bg-[#121212] text-gray-400 border border-gray-800 hover:border-white/50']">
        🥅 Porteros por Equipo
      </button>
      <button @click="activeTab = 'tarjetas'" :class="['shrink-0 px-6 py-3 rounded-full font-bold text-sm transition-all snap-start', activeTab === 'tarjetas' ? 'bg-red-500 text-white shadow-[0_0_15px_rgba(239,68,68,0.4)]' : 'bg-[#121212] text-gray-400 border border-gray-800 hover:border-red-500/50']">
        🟥 Equipos con Más Tarjetas
      </button>
    </div>

    <div v-if="loading" class="flex flex-col items-center justify-center py-20">
      <i class="fas fa-spinner fa-spin text-4xl text-[#ccff00] mb-4"></i>
      <p class="text-gray-400 animate-pulse">Cargando estadísticas...</p>
    </div>

    <div v-else class="relative min-h-[400px]">
      <transition name="fade" mode="out-in">
        <div :key="activeTab">
          
          <!-- TAB 1: GOLEADORES GLOBAL -->
          <section v-if="activeTab === 'goleadoresGlobal'">
            <div class="flex items-center gap-3 mb-8">
              <i class="fas fa-trophy text-3xl text-yellow-400"></i>
              <h2 class="text-2xl font-black italic">TOP 5 GOLEADORES</h2>
            </div>
            
            <!-- Podium para Top 3 -->
            <div class="flex flex-col sm:flex-row items-end justify-center gap-4 sm:gap-6 mb-12 mt-12 px-4" v-if="goleadoresGlobal.length >= 3">
              <!-- Plata (2) -->
              <div class="w-full sm:w-1/3 max-w-[200px] flex flex-col items-center order-2 sm:order-1 relative">
                <div class="absolute -top-12 w-20 h-20 rounded-full border-4 border-gray-300 overflow-hidden shadow-[0_0_20px_rgba(209,213,219,0.5)] z-10 bg-[#121212]">
                  <img :src="getFotoUrl(goleadoresGlobal[1].foto_ruta)" class="w-full h-full object-cover" @error="handleImageError" />
                </div>
                <div class="bg-gray-300 w-full h-24 sm:h-32 rounded-t-lg flex flex-col items-center justify-end pb-4 shadow-lg text-black mt-8 pt-10">
                  <span class="text-3xl font-black text-gray-800">2</span>
                  <span class="font-bold text-sm truncate w-11/12 text-center">{{ goleadoresGlobal[1].nombre }}</span>
                  <span class="font-black text-lg">{{ goleadoresGlobal[1].total_goles }} <span class="text-[10px]">GOLES</span></span>
                </div>
              </div>
              
              <!-- Oro (1) -->
              <div class="w-full sm:w-1/3 max-w-[220px] flex flex-col items-center order-1 sm:order-2 relative z-20">
                <div class="absolute -top-16 w-24 h-24 rounded-full border-4 border-yellow-400 overflow-hidden shadow-[0_0_30px_rgba(250,204,21,0.6)] z-10 bg-[#121212]">
                  <img :src="getFotoUrl(goleadoresGlobal[0].foto_ruta)" class="w-full h-full object-cover" @error="handleImageError" />
                </div>
                <div class="absolute -top-20 text-yellow-400 text-3xl animate-bounce">👑</div>
                <div class="bg-yellow-400 w-full h-32 sm:h-44 rounded-t-lg flex flex-col items-center justify-end pb-4 shadow-2xl text-black mt-8 pt-12">
                  <span class="text-4xl font-black text-yellow-700">1</span>
                  <span class="font-bold text-sm truncate w-11/12 text-center">{{ goleadoresGlobal[0].nombre }}</span>
                  <span class="font-black text-2xl">{{ goleadoresGlobal[0].total_goles }} <span class="text-xs">GOLES</span></span>
                </div>
              </div>

              <!-- Bronce (3) -->
              <div class="w-full sm:w-1/3 max-w-[200px] flex flex-col items-center order-3 relative">
                <div class="absolute -top-12 w-20 h-20 rounded-full border-4 border-amber-600 overflow-hidden shadow-[0_0_20px_rgba(217,119,6,0.5)] z-10 bg-[#121212]">
                  <img :src="getFotoUrl(goleadoresGlobal[2].foto_ruta)" class="w-full h-full object-cover" @error="handleImageError" />
                </div>
                <div class="bg-amber-600 w-full h-20 sm:h-24 rounded-t-lg flex flex-col items-center justify-end pb-4 shadow-lg text-white mt-8 pt-10">
                  <span class="text-2xl font-black text-amber-900">3</span>
                  <span class="font-bold text-sm truncate w-11/12 text-center">{{ goleadoresGlobal[2].nombre }}</span>
                  <span class="font-black text-lg">{{ goleadoresGlobal[2].total_goles }} <span class="text-[10px]">GOLES</span></span>
                </div>
              </div>
            </div>

            <!-- Resto del Top 5 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-8" v-if="goleadoresGlobal.length > 3">
              <div v-for="(jugador, index) in goleadoresGlobal.slice(3, 5)" :key="jugador.id"
                   class="bg-[#121212] border border-gray-800 rounded-xl p-4 flex items-center gap-4 hover:border-[#ccff00]/30 transition-colors">
                <div class="text-2xl font-black text-gray-700 w-8 text-center">{{ index + 4 }}</div>
                <div class="w-14 h-14 rounded-full overflow-hidden border border-gray-700 shrink-0">
                  <img :src="getFotoUrl(jugador.foto_ruta)" class="w-full h-full object-cover" @error="handleImageError" />
                </div>
                <div class="flex-grow min-w-0">
                  <h4 class="font-bold text-sm truncate">{{ jugador.nombre }}</h4>
                  <p class="text-xs text-gray-500 truncate flex items-center gap-2 mt-1">
                    <img :src="getFotoUrl(jugador.equipo_foto)" class="w-4 h-4 rounded-full" @error="handleImageError" />
                    {{ jugador.equipo_nombre }}
                  </p>
                </div>
                <div class="text-right shrink-0 bg-gray-900 px-4 py-2 rounded-lg">
                  <span class="text-[#ccff00] font-black text-xl">{{ jugador.total_goles }}</span>
                  <span class="text-[10px] text-gray-500 block -mt-1">goles</span>
                </div>
              </div>
            </div>
          </section>

          <!-- TAB 2: GOLEADORES POR EQUIPO -->
          <section v-if="activeTab === 'goleadoresEquipo'">
            <div class="flex items-center gap-3 mb-8">
              <i class="fas fa-bullseye text-3xl text-[#ccff00]"></i>
              <h2 class="text-2xl font-black italic">MÁXIMO GOLEADOR POR EQUIPO</h2>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
              <div v-for="jugador in goleadoresPorEquipo" :key="jugador.id"
                   class="bg-[#121212] border border-gray-800 rounded-2xl overflow-hidden hover:border-[#ccff00]/50 transition-all hover:-translate-y-1 group">
                <div class="h-16 bg-gray-900 relative">
                  <!-- Logo equipo fondo -->
                  <div class="absolute inset-0 opacity-20 bg-center bg-cover blur-sm" :style="{ backgroundImage: `url(${getFotoUrl(jugador.equipo_foto)})` }"></div>
                  <!-- Jugador Foto -->
                  <div class="absolute -bottom-8 left-1/2 -translate-x-1/2 w-20 h-20 rounded-full border-4 border-[#121212] overflow-hidden z-10 bg-gray-800 shadow-lg group-hover:border-[#ccff00] transition-colors">
                    <img :src="getFotoUrl(jugador.foto_ruta)" class="w-full h-full object-cover" @error="handleImageError" />
                  </div>
                </div>
                <div class="pt-10 pb-4 px-4 text-center">
                  <h4 class="font-bold text-md truncate">{{ jugador.nombre }}</h4>
                  <div class="flex items-center justify-center gap-1.5 mt-1 mb-4">
                    <img :src="getFotoUrl(jugador.equipo_foto)" class="w-3 h-3 rounded-full" @error="handleImageError" />
                    <p class="text-xs text-gray-400 truncate">{{ jugador.equipo_nombre }}</p>
                  </div>
                  <div class="inline-flex items-center gap-2 bg-[#ccff00]/10 px-4 py-1.5 rounded-full border border-[#ccff00]/20">
                    <i class="fas fa-futbol text-[#ccff00]"></i>
                    <span class="text-[#ccff00] font-black text-xl">{{ jugador.total_goles }}</span>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- TAB 3: PORTEROS GLOBAL -->
          <section v-if="activeTab === 'porterosGlobal'">
            <div class="flex items-center gap-3 mb-8">
              <i class="fas fa-shield-alt text-3xl text-white"></i>
              <h2 class="text-2xl font-black italic">TOP 5 PORTEROS (MENOS VENCIDOS)</h2>
            </div>
            
            <!-- Podium para Top 3 -->
            <div class="flex flex-col sm:flex-row items-end justify-center gap-4 sm:gap-6 mb-12 mt-12 px-4" v-if="porterosGlobal.length >= 3">
              <!-- Plata (2) -->
              <div class="w-full sm:w-1/3 max-w-[200px] flex flex-col items-center order-2 sm:order-1 relative">
                <div class="absolute -top-12 w-20 h-20 rounded-full border-4 border-gray-300 overflow-hidden shadow-[0_0_20px_rgba(209,213,219,0.5)] z-10 bg-[#121212]">
                  <img :src="getFotoUrl(porterosGlobal[1].foto_ruta)" class="w-full h-full object-cover" @error="handleImageError" />
                </div>
                <div class="bg-gray-300 w-full h-24 sm:h-32 rounded-t-lg flex flex-col items-center justify-end pb-4 shadow-lg text-black mt-8 pt-10">
                  <span class="text-3xl font-black text-gray-800">2</span>
                  <span class="font-bold text-sm truncate w-11/12 text-center">{{ porterosGlobal[1].nombre }}</span>
                  <span class="font-black text-lg">{{ porterosGlobal[1].total_goles_recibidos }} <span class="text-[10px]">EN CONTRA</span></span>
                </div>
              </div>
              
              <!-- Oro (1) -->
              <div class="w-full sm:w-1/3 max-w-[220px] flex flex-col items-center order-1 sm:order-2 relative z-20">
                <div class="absolute -top-16 w-24 h-24 rounded-full border-4 border-white overflow-hidden shadow-[0_0_30px_rgba(255,255,255,0.6)] z-10 bg-[#121212]">
                  <img :src="getFotoUrl(porterosGlobal[0].foto_ruta)" class="w-full h-full object-cover" @error="handleImageError" />
                </div>
                <div class="absolute -top-20 text-white text-3xl animate-pulse">🧤</div>
                <div class="bg-white w-full h-32 sm:h-44 rounded-t-lg flex flex-col items-center justify-end pb-4 shadow-2xl text-black mt-8 pt-12">
                  <span class="text-4xl font-black text-gray-300">1</span>
                  <span class="font-bold text-sm truncate w-11/12 text-center">{{ porterosGlobal[0].nombre }}</span>
                  <span class="font-black text-2xl">{{ porterosGlobal[0].total_goles_recibidos }} <span class="text-xs">EN CONTRA</span></span>
                </div>
              </div>

              <!-- Bronce (3) -->
              <div class="w-full sm:w-1/3 max-w-[200px] flex flex-col items-center order-3 relative">
                <div class="absolute -top-12 w-20 h-20 rounded-full border-4 border-amber-600 overflow-hidden shadow-[0_0_20px_rgba(217,119,6,0.5)] z-10 bg-[#121212]">
                  <img :src="getFotoUrl(porterosGlobal[2].foto_ruta)" class="w-full h-full object-cover" @error="handleImageError" />
                </div>
                <div class="bg-amber-600 w-full h-20 sm:h-24 rounded-t-lg flex flex-col items-center justify-end pb-4 shadow-lg text-white mt-8 pt-10">
                  <span class="text-2xl font-black text-amber-900">3</span>
                  <span class="font-bold text-sm truncate w-11/12 text-center">{{ porterosGlobal[2].nombre }}</span>
                  <span class="font-black text-lg">{{ porterosGlobal[2].total_goles_recibidos }} <span class="text-[10px]">EN CONTRA</span></span>
                </div>
              </div>
            </div>

            <!-- Resto del Top 5 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-8" v-if="porterosGlobal.length > 3">
              <div v-for="(jugador, index) in porterosGlobal.slice(3, 5)" :key="jugador.id"
                   class="bg-[#121212] border border-gray-800 rounded-xl p-4 flex items-center gap-4 hover:border-white/30 transition-colors">
                <div class="text-2xl font-black text-gray-700 w-8 text-center">{{ index + 4 }}</div>
                <div class="w-14 h-14 rounded-full overflow-hidden border border-gray-700 shrink-0">
                  <img :src="getFotoUrl(jugador.foto_ruta)" class="w-full h-full object-cover" @error="handleImageError" />
                </div>
                <div class="flex-grow min-w-0">
                  <h4 class="font-bold text-sm truncate">{{ jugador.nombre }}</h4>
                  <p class="text-xs text-gray-500 truncate flex items-center gap-2 mt-1">
                    <img :src="getFotoUrl(jugador.equipo_foto)" class="w-4 h-4 rounded-full" @error="handleImageError" />
                    {{ jugador.equipo_nombre }}
                  </p>
                </div>
                <div class="text-right shrink-0 bg-gray-900 px-4 py-2 rounded-lg border border-gray-800">
                  <span class="text-white font-black text-xl">{{ jugador.total_goles_recibidos }}</span>
                  <span class="text-[10px] text-gray-500 block -mt-1">en contra</span>
                </div>
              </div>
            </div>
          </section>

          <!-- TAB 4: PORTEROS POR EQUIPO -->
          <section v-if="activeTab === 'porterosEquipo'">
            <div class="flex items-center gap-3 mb-8">
              <i class="fas fa-hands text-3xl text-white"></i>
              <h2 class="text-2xl font-black italic">EL MURO DE CADA EQUIPO</h2>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
              <div v-for="jugador in porterosPorEquipo" :key="jugador.id"
                   class="bg-[#121212] border border-gray-800 rounded-2xl overflow-hidden hover:border-white/50 transition-all hover:-translate-y-1 group">
                <div class="h-16 bg-gray-900 relative">
                  <!-- Logo equipo fondo -->
                  <div class="absolute inset-0 opacity-20 bg-center bg-cover blur-sm" :style="{ backgroundImage: `url(${getFotoUrl(jugador.equipo_foto)})` }"></div>
                  <!-- Jugador Foto -->
                  <div class="absolute -bottom-8 left-1/2 -translate-x-1/2 w-20 h-20 rounded-full border-4 border-[#121212] overflow-hidden z-10 bg-gray-800 shadow-lg group-hover:border-white transition-colors">
                    <img :src="getFotoUrl(jugador.foto_ruta)" class="w-full h-full object-cover" @error="handleImageError" />
                  </div>
                </div>
                <div class="pt-10 pb-4 px-4 text-center">
                  <h4 class="font-bold text-md truncate">{{ jugador.nombre }}</h4>
                  <div class="flex items-center justify-center gap-1.5 mt-1 mb-4">
                    <img :src="getFotoUrl(jugador.equipo_foto)" class="w-3 h-3 rounded-full" @error="handleImageError" />
                    <p class="text-xs text-gray-400 truncate">{{ jugador.equipo_nombre }}</p>
                  </div>
                  <div class="inline-flex items-center gap-2 bg-gray-800 px-4 py-1.5 rounded-full border border-gray-700">
                    <i class="fas fa-shield-alt text-white"></i>
                    <span class="text-white font-black text-xl">{{ jugador.total_goles_recibidos }}</span>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- TAB 5: TARJETAS (FAIR PLAY) -->
          <section v-if="activeTab === 'tarjetas'">
            <div class="flex items-center gap-3 mb-8">
              <div class="flex gap-1"><div class="w-3 h-5 bg-yellow-400 rounded-sm"></div><div class="w-3 h-5 bg-red-500 rounded-sm"></div></div> 
              <h2 class="text-2xl font-black italic uppercase">Equipos con Más Tarjetas</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div v-for="(equipo, index) in tarjetasEquipos" :key="equipo.id"
                   class="bg-[#121212] border border-gray-800 rounded-2xl p-5 flex flex-col relative overflow-hidden group hover:border-red-500/30 transition-colors">
                
                <!-- Rank number background -->
                <div class="absolute -right-4 -bottom-6 text-8xl font-black text-gray-900/50 z-0 select-none">
                  {{ index + 1 }}
                </div>
                
                <div class="relative z-10 flex items-center gap-4 mb-5">
                  <div class="w-14 h-14 rounded-full overflow-hidden border-2 border-gray-700 bg-black shrink-0">
                    <img :src="getFotoUrl(equipo.foto_ruta)" class="w-full h-full object-cover p-1" @error="handleImageError" />
                  </div>
                  <div>
                    <h4 class="font-bold text-lg leading-tight">{{ equipo.nombre }}</h4>
                    <span class="text-xs text-gray-500">{{ equipo.total_tarjetas }} amonestaciones</span>
                  </div>
                </div>
                
                <div class="relative z-10 flex gap-2 w-full mt-auto">
                  <div class="flex-1 bg-gray-900 rounded-lg p-2 flex flex-col items-center justify-center border border-gray-800">
                    <div class="w-4 h-5 bg-yellow-400 rounded-sm mb-1 shadow-sm shadow-yellow-400/20"></div>
                    <span class="text-sm font-bold text-gray-300">{{ equipo.total_amarillas }}</span>
                  </div>
                  <div class="flex-1 bg-gray-900 rounded-lg p-2 flex flex-col items-center justify-center border border-gray-800">
                    <div class="w-4 h-5 bg-red-500 rounded-sm mb-1 shadow-sm shadow-red-500/20"></div>
                    <span class="text-sm font-bold text-gray-300">{{ equipo.total_rojas }}</span>
                  </div>
                </div>
              </div>
            </div>
          </section>
          
        </div>
      </transition>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api, { IMAGE_BASE_URL } from '../services/api';

const loading = ref(true);
const activeTab = ref('goleadoresGlobal'); // Default tab

const goleadoresGlobal = ref([]);
const porterosGlobal = ref([]);
const tarjetasEquipos = ref([]);
const goleadoresPorEquipo = ref([]);
const porterosPorEquipo = ref([]);

const defaultAvatar = 'https://ui-avatars.com/api/?name=Jugador&background=1f2937&color=fff';

const getFotoUrl = (ruta) => {
  if (!ruta) return defaultAvatar;
  // Si la ruta ya tiene http, retornarla, sino concatenar el baseUrl
  if (ruta.startsWith('http')) return ruta;
  return `${IMAGE_BASE_URL}${ruta}`;
};

const handleImageError = (e) => {
  e.target.src = defaultAvatar;
};

const fetchEstadisticas = async () => {
  loading.value = true;
  try {
    const [resGoles, resPorteros, resTarjetas, resGolesEq, resPorterosEq] = await Promise.all([
      api.get(`/rankings/goleadores?limit=5`),
      api.get(`/rankings/porteros?limit=5`),
      api.get(`/rankings/tarjetas-equipos?limit=10`),
      api.get(`/rankings/goleadores-por-equipo`),
      api.get(`/rankings/porteros-por-equipo`)
    ]);

    goleadoresGlobal.value = resGoles.data;
    porterosGlobal.value = resPorteros.data;
    tarjetasEquipos.value = resTarjetas.data;
    goleadoresPorEquipo.value = resGolesEq.data;
    porterosPorEquipo.value = resPorterosEq.data;

  } catch (error) {
    console.error('Error fetching stats:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchEstadisticas();
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-enter-from {
  opacity: 0;
  transform: translateY(10px);
}
.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
