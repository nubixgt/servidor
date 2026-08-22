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

    <div v-if="loading" class="flex flex-col items-center justify-center py-20">
      <i class="fas fa-spinner fa-spin text-4xl text-[#ccff00] mb-4"></i>
      <p class="text-gray-400 animate-pulse">Cargando estadísticas...</p>
    </div>

    <div v-else class="space-y-12">
      
      <!-- SECCIÓN GOLEADORES -->
      <section>
        <h2 class="text-2xl font-black italic border-b border-gray-800 pb-2 mb-6 flex items-center gap-2">
          <i class="fas fa-futbol text-[#ccff00]"></i> MÁXIMOS GOLEADORES
        </h2>
        
        <div class="mb-6">
          <h3 class="text-lg font-bold text-gray-300 mb-4">👑 Top 5 Global</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
            <div v-for="(jugador, index) in goleadoresGlobal" :key="jugador.id"
                 class="bg-[#121212] border border-gray-800 rounded-2xl p-4 flex flex-col items-center text-center relative hover:border-[#ccff00]/50 transition-colors">
              <div v-if="index === 0" class="absolute -top-3 bg-yellow-400 text-black text-[10px] font-black px-2 py-0.5 rounded-full z-10">PICHICHI</div>
              <div v-if="index === 1" class="absolute -top-3 bg-gray-300 text-black text-[10px] font-black px-2 py-0.5 rounded-full z-10">#2</div>
              <div v-if="index === 2" class="absolute -top-3 bg-amber-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full z-10">#3</div>
              
              <div class="w-16 h-16 rounded-full overflow-hidden border-2 border-gray-700 mb-3" :class="{'border-yellow-400': index===0}">
                <img :src="getFotoUrl(jugador.foto_ruta)" class="w-full h-full object-cover" @error="handleImageError" />
              </div>
              <h4 class="font-bold text-sm leading-tight mb-1">{{ jugador.nombre }}</h4>
              <p class="text-xs text-gray-500 mb-3 truncate w-full">{{ jugador.equipo_nombre }}</p>
              
              <div class="mt-auto flex items-center gap-2 bg-[#ccff00]/10 px-3 py-1 rounded-full">
                <i class="fas fa-futbol text-[#ccff00] text-sm"></i>
                <span class="text-[#ccff00] font-black text-lg">{{ jugador.total_goles }}</span>
              </div>
            </div>
          </div>
        </div>

        <div>
          <h3 class="text-lg font-bold text-gray-300 mb-4">🏆 Mejor de cada equipo</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div v-for="jugador in goleadoresPorEquipo" :key="jugador.id"
                 class="bg-gray-900/50 border border-gray-800 rounded-xl p-3 flex items-center gap-4">
              <div class="w-12 h-12 rounded-full overflow-hidden border border-gray-700 shrink-0">
                <img :src="getFotoUrl(jugador.foto_ruta)" class="w-full h-full object-cover" @error="handleImageError" />
              </div>
              <div class="flex-grow min-w-0">
                <h4 class="font-bold text-sm truncate">{{ jugador.nombre }}</h4>
                <p class="text-[10px] text-gray-500 truncate flex items-center gap-1">
                  <img :src="getFotoUrl(jugador.equipo_foto)" class="w-3 h-3 rounded-full" @error="handleImageError" />
                  {{ jugador.equipo_nombre }}
                </p>
              </div>
              <div class="text-right shrink-0">
                <span class="text-white font-black text-lg">{{ jugador.total_goles }}</span>
                <span class="text-[10px] text-gray-500 block -mt-1">goles</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- SECCIÓN PORTEROS -->
      <section>
        <h2 class="text-2xl font-black italic border-b border-gray-800 pb-2 mb-6 flex items-center gap-2">
          <i class="fas fa-hands text-gray-300"></i> MEJORES PORTEROS
        </h2>
        
        <div class="mb-6">
          <h3 class="text-lg font-bold text-gray-300 mb-4">🛡️ Top 5 Global (Menos vencidos)</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
            <div v-for="(jugador, index) in porterosGlobal" :key="jugador.id"
                 class="bg-[#121212] border border-gray-800 rounded-2xl p-4 flex flex-col items-center text-center relative hover:border-white/50 transition-colors">
              <div v-if="index === 0" class="absolute -top-3 bg-white text-black text-[10px] font-black px-2 py-0.5 rounded-full z-10">ZAMORA</div>
              
              <div class="w-16 h-16 rounded-full overflow-hidden border-2 border-gray-700 mb-3" :class="{'border-white': index===0}">
                <img :src="getFotoUrl(jugador.foto_ruta)" class="w-full h-full object-cover" @error="handleImageError" />
              </div>
              <h4 class="font-bold text-sm leading-tight mb-1">{{ jugador.nombre }}</h4>
              <p class="text-xs text-gray-500 mb-3 truncate w-full">{{ jugador.equipo_nombre }}</p>
              
              <div class="mt-auto w-full bg-gray-900 rounded-lg p-2 text-center">
                <div class="text-xs text-gray-500">Goles Recibidos</div>
                <div class="text-white font-black text-lg">{{ jugador.total_goles_recibidos }}</div>
                <div class="text-[9px] text-gray-600 mt-1">en {{ jugador.partidos_jugados }} partidos</div>
              </div>
            </div>
          </div>
        </div>

        <div>
          <h3 class="text-lg font-bold text-gray-300 mb-4">🧱 Muro de cada equipo</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div v-for="jugador in porterosPorEquipo" :key="jugador.id"
                 class="bg-gray-900/50 border border-gray-800 rounded-xl p-3 flex items-center gap-4">
              <div class="w-12 h-12 rounded-full overflow-hidden border border-gray-700 shrink-0">
                <img :src="getFotoUrl(jugador.foto_ruta)" class="w-full h-full object-cover" @error="handleImageError" />
              </div>
              <div class="flex-grow min-w-0">
                <h4 class="font-bold text-sm truncate">{{ jugador.nombre }}</h4>
                <p class="text-[10px] text-gray-500 truncate flex items-center gap-1">
                  <img :src="getFotoUrl(jugador.equipo_foto)" class="w-3 h-3 rounded-full" @error="handleImageError" />
                  {{ jugador.equipo_nombre }}
                </p>
              </div>
              <div class="text-right shrink-0">
                <span class="text-white font-black text-lg">{{ jugador.total_goles_recibidos }}</span>
                <span class="text-[10px] text-gray-500 block -mt-1">goles</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- SECCIÓN TARJETAS -->
      <section>
        <h2 class="text-2xl font-black italic border-b border-gray-800 pb-2 mb-6 flex items-center gap-2">
          <div class="flex gap-1"><div class="w-3 h-4 bg-yellow-400 rounded-sm"></div><div class="w-3 h-4 bg-red-500 rounded-sm"></div></div> 
          FAIR PLAY (Tarjetas por Equipo)
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="(equipo, index) in tarjetasEquipos" :key="equipo.id"
               class="bg-[#121212] border border-gray-800 rounded-xl p-4 flex items-center gap-4 relative">
             <div class="text-2xl font-black text-gray-800 absolute -left-2 top-2">{{ index + 1 }}</div>
             <div class="w-12 h-12 rounded-full overflow-hidden border border-gray-700 shrink-0 ml-4">
               <img :src="getFotoUrl(equipo.foto_ruta)" class="w-full h-full object-cover" @error="handleImageError" />
             </div>
             <div class="flex-grow">
               <h4 class="font-bold text-sm">{{ equipo.nombre }}</h4>
               <p class="text-[10px] text-gray-500">{{ equipo.total_tarjetas }} tarjetas en total</p>
             </div>
             <div class="flex gap-3 shrink-0">
               <div class="text-center">
                 <div class="w-3 h-4 bg-yellow-400 rounded-sm mx-auto mb-1"></div>
                 <span class="text-xs font-bold text-gray-400">{{ equipo.total_amarillas }}</span>
               </div>
               <div class="text-center">
                 <div class="w-3 h-4 bg-red-500 rounded-sm mx-auto mb-1"></div>
                 <span class="text-xs font-bold text-gray-400">{{ equipo.total_rojas }}</span>
               </div>
             </div>
          </div>
        </div>
      </section>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api, { IMAGE_BASE_URL } from '../services/api';

const loading = ref(true);

const goleadoresGlobal = ref([]);
const porterosGlobal = ref([]);
const tarjetasEquipos = ref([]);

const goleadoresPorEquipo = ref([]);
const porterosPorEquipo = ref([]);

const defaultAvatar = 'https://ui-avatars.com/api/?name=Jugador&background=1f2937&color=fff';

const getFotoUrl = (ruta) => {
  if (!ruta) return defaultAvatar;
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
