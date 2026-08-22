<template>
  <div class="p-4 md:p-8">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-3xl font-black italic tracking-tight">REGISTRAR PARTIDO</h1>
        <p class="text-gray-400 text-sm mt-1">Registra los resultados y estadísticas de los jugadores</p>
      </div>
      <button @click="$router.go(-1)" class="text-gray-400 hover:text-white transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>Volver
      </button>
    </div>

    <form @submit.prevent="guardarPartido" class="max-w-5xl mx-auto space-y-8">
      <!-- Configuración del Partido -->
      <div class="bg-[#121212] border border-gray-800 p-6 rounded-2xl shadow-xl">
        <h2 class="text-xl font-bold mb-4 flex items-center gap-2 border-b border-gray-800 pb-3">
          <i class="fas fa-cog text-[#ccff00]"></i> Datos del Encuentro
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <div>
            <label class="block text-sm text-gray-400 mb-2">Fecha y Hora</label>
            <input type="datetime-local" v-model="form.fecha" required
                   class="w-full bg-black border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:border-[#ccff00] focus:ring-1 focus:ring-[#ccff00] transition-colors outline-none" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-[1fr,auto,1fr] gap-6 items-center">
          <!-- Local -->
          <div class="bg-gray-900/50 p-4 rounded-xl border border-gray-800">
            <label class="block text-sm text-[#ccff00] font-bold mb-2">Equipo Local</label>
            <select v-model="form.equipo_local_id" required @change="cargarJugadores"
                    class="w-full bg-black border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:border-[#ccff00] transition-colors outline-none mb-4">
              <option value="">Seleccione equipo local...</option>
              <option v-for="eq in equiposDisponibles" :key="eq.id" :value="eq.id" :disabled="form.equipo_visitante_id === eq.id">{{ eq.nombre }}</option>
            </select>
            <div class="text-center">
              <label class="block text-xs text-gray-500 mb-1">Goles</label>
              <input type="number" min="0" v-model="form.goles_local" required
                     class="w-20 text-center font-black text-3xl bg-black border border-gray-700 rounded-lg py-2 text-white focus:border-[#ccff00] outline-none" />
            </div>
          </div>

          <div class="text-2xl font-black italic text-gray-600">VS</div>

          <!-- Visitante -->
          <div class="bg-gray-900/50 p-4 rounded-xl border border-gray-800">
            <label class="block text-sm text-gray-400 font-bold mb-2">Equipo Visitante</label>
            <select v-model="form.equipo_visitante_id" required @change="cargarJugadores"
                    class="w-full bg-black border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:border-gray-500 transition-colors outline-none mb-4">
              <option value="">Seleccione equipo visitante...</option>
              <option v-for="eq in equiposDisponibles" :key="eq.id" :value="eq.id" :disabled="form.equipo_local_id === eq.id">{{ eq.nombre }}</option>
            </select>
            <div class="text-center">
              <label class="block text-xs text-gray-500 mb-1">Goles</label>
              <input type="number" min="0" v-model="form.goles_visitante" required
                     class="w-20 text-center font-black text-3xl bg-black border border-gray-700 rounded-lg py-2 text-white focus:border-gray-500 outline-none" />
            </div>
          </div>
        </div>
      </div>

      <!-- Estadísticas Jugadores Local -->
      <div v-if="jugadoresLocal.length > 0" class="bg-[#121212] border border-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="bg-gray-900/50 p-4 border-b border-gray-800">
          <h2 class="text-lg font-bold text-[#ccff00]">Estadísticas - Local</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-gray-300">
            <thead class="bg-gray-800/30 text-xs uppercase text-gray-400">
              <tr>
                <th class="px-4 py-3">Jugador</th>
                <th class="px-4 py-3 text-center">Goles</th>
                <th class="px-4 py-3 text-center">Amarillas</th>
                <th class="px-4 py-3 text-center">Rojas</th>
                <th class="px-4 py-3 text-center">Portero (Goles Recibidos)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-800/50">
              <tr v-for="j in jugadoresLocal" :key="j.id" class="hover:bg-gray-800/20 transition-colors">
                <td class="px-4 py-3">
                  <div class="font-bold">{{ j.nombre }}</div>
                  <div class="text-xs text-gray-500">{{ j.posicion }}</div>
                </td>
                <td class="px-4 py-3 text-center">
                  <input type="number" min="0" v-model="estadisticas[j.id].goles" class="w-16 bg-black border border-gray-700 rounded px-2 py-1 text-center focus:border-[#ccff00] outline-none" />
                </td>
                <td class="px-4 py-3 text-center">
                  <input type="number" min="0" max="2" v-model="estadisticas[j.id].tarjetas_amarillas" class="w-16 bg-black border border-yellow-900 rounded px-2 py-1 text-center text-yellow-500 focus:border-yellow-500 outline-none" />
                </td>
                <td class="px-4 py-3 text-center">
                  <input type="number" min="0" max="1" v-model="estadisticas[j.id].tarjetas_rojas" class="w-16 bg-black border border-red-900 rounded px-2 py-1 text-center text-red-500 focus:border-red-500 outline-none" />
                </td>
                <td class="px-4 py-3 text-center">
                  <div class="flex items-center justify-center gap-2">
                    <input type="checkbox" v-model="estadisticas[j.id].jugo_como_portero" class="accent-[#ccff00]" />
                    <input type="number" min="0" v-model="estadisticas[j.id].goles_recibidos" :disabled="!estadisticas[j.id].jugo_como_portero" class="w-16 bg-black border border-gray-700 rounded px-2 py-1 text-center disabled:opacity-30 outline-none" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Estadísticas Jugadores Visitante -->
      <div v-if="jugadoresVisitante.length > 0" class="bg-[#121212] border border-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="bg-gray-900/50 p-4 border-b border-gray-800">
          <h2 class="text-lg font-bold text-gray-300">Estadísticas - Visitante</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-gray-300">
            <thead class="bg-gray-800/30 text-xs uppercase text-gray-400">
              <tr>
                <th class="px-4 py-3">Jugador</th>
                <th class="px-4 py-3 text-center">Goles</th>
                <th class="px-4 py-3 text-center">Amarillas</th>
                <th class="px-4 py-3 text-center">Rojas</th>
                <th class="px-4 py-3 text-center">Portero (Goles Recibidos)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-800/50">
              <tr v-for="j in jugadoresVisitante" :key="j.id" class="hover:bg-gray-800/20 transition-colors">
                <td class="px-4 py-3">
                  <div class="font-bold">{{ j.nombre }}</div>
                  <div class="text-xs text-gray-500">{{ j.posicion }}</div>
                </td>
                <td class="px-4 py-3 text-center">
                  <input type="number" min="0" v-model="estadisticas[j.id].goles" class="w-16 bg-black border border-gray-700 rounded px-2 py-1 text-center focus:border-white outline-none" />
                </td>
                <td class="px-4 py-3 text-center">
                  <input type="number" min="0" max="2" v-model="estadisticas[j.id].tarjetas_amarillas" class="w-16 bg-black border border-yellow-900 rounded px-2 py-1 text-center text-yellow-500 focus:border-yellow-500 outline-none" />
                </td>
                <td class="px-4 py-3 text-center">
                  <input type="number" min="0" max="1" v-model="estadisticas[j.id].tarjetas_rojas" class="w-16 bg-black border border-red-900 rounded px-2 py-1 text-center text-red-500 focus:border-red-500 outline-none" />
                </td>
                <td class="px-4 py-3 text-center">
                  <div class="flex items-center justify-center gap-2">
                    <input type="checkbox" v-model="estadisticas[j.id].jugo_como_portero" class="accent-white" />
                    <input type="number" min="0" v-model="estadisticas[j.id].goles_recibidos" :disabled="!estadisticas[j.id].jugo_como_portero" class="w-16 bg-black border border-gray-700 rounded px-2 py-1 text-center disabled:opacity-30 outline-none" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="flex justify-end gap-4" v-if="jugadoresLocal.length > 0 && jugadoresVisitante.length > 0">
        <button type="submit" :disabled="submitting"
                class="px-8 py-3 bg-[#ccff00] text-black font-bold rounded-lg hover:bg-[#b3e600] disabled:opacity-50 transition-colors shadow-[0_0_15px_rgba(204,255,0,0.3)]">
          <i v-if="submitting" class="fas fa-spinner fa-spin mr-2"></i>
          <span v-else><i class="fas fa-save mr-2"></i>Guardar Partido</span>
        </button>
      </div>

      <div v-if="error" class="bg-red-500/10 border border-red-500 text-red-500 p-4 rounded-lg mt-4 flex items-start gap-3">
        <i class="fas fa-exclamation-circle mt-0.5"></i>
        <span>{{ error }}</span>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';

const router = useRouter();
const token = localStorage.getItem('deportes_token');
const userRole = localStorage.getItem('deportes_rol');
const equipoData = JSON.parse(localStorage.getItem('deportes_equipo') || '{}');
const miEquipoId = equipoData.id;

const form = reactive({
  fecha: '',
  equipo_local_id: '',
  equipo_visitante_id: '',
  goles_local: 0,
  goles_visitante: 0
});

const equipos = ref([]);
const jugadoresLocal = ref([]);
const jugadoresVisitante = ref([]);
const estadisticas = ref({});
const submitting = ref(false);
const error = ref('');

// Computed property para filtrar equipos si es encargado
const equiposDisponibles = computed(() => {
  if (userRole === 'admin') return equipos.value;
  // Si es encargado, al menos uno debe ser su equipo (simplificado: le mostramos todos, pero validaremos)
  return equipos.value;
});

const getAuthHeaders = () => {
  return {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  };
};

const fetchEquipos = async () => {
  try {
    const res = await api.get(`/admin/equipos`, {
      headers: getAuthHeaders()
    });
    equipos.value = res.data;
  } catch (e) {
    console.error(e);
  }
};

const initEstadisticasForJugadores = (jugadores) => {
  jugadores.forEach(j => {
    if (!estadisticas.value[j.id]) {
      estadisticas.value[j.id] = {
        jugador_id: j.id,
        goles: 0,
        tarjetas_amarillas: 0,
        tarjetas_rojas: 0,
        goles_recibidos: 0,
        jugo_como_portero: j.posicion === 'Portero'
      };
    }
  });
};

const cargarJugadores = async () => {
  if (form.equipo_local_id) {
    try {
      const res = await api.get(`/admin/equipos/${form.equipo_local_id}/jugadores`, { headers: getAuthHeaders() });
      jugadoresLocal.value = res.data;
      initEstadisticasForJugadores(jugadoresLocal.value);
    } catch (e) { console.error(e); }
  } else {
    jugadoresLocal.value = [];
  }

  if (form.equipo_visitante_id) {
    try {
      const res = await api.get(`/admin/equipos/${form.equipo_visitante_id}/jugadores`, { headers: getAuthHeaders() });
      jugadoresVisitante.value = res.data;
      initEstadisticasForJugadores(jugadoresVisitante.value);
    } catch (e) { console.error(e); }
  } else {
    jugadoresVisitante.value = [];
  }
};

const guardarPartido = async () => {
  error.value = '';
  
  if (userRole !== 'admin' && form.equipo_local_id != miEquipoId && form.equipo_visitante_id != miEquipoId) {
    error.value = 'Debes seleccionar tu equipo en al menos una de las opciones.';
    return;
  }

  submitting.value = true;
  try {
    const payload = {
      ...form,
      estadisticas: Object.values(estadisticas.value).filter(e => 
        // Solo enviar si participó en algo
        e.goles > 0 || e.tarjetas_amarillas > 0 || e.tarjetas_rojas > 0 || e.jugo_como_portero
      )
    };

    const res = await api.post(`/partidos`, payload, {
      headers: getAuthHeaders()
    });

    alert('Partido guardado exitosamente');
    router.go(-1);
  } catch (e) {
    error.value = e.response?.data?.error || 'Error al guardar';
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  // Set default date to now
  const now = new Date();
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
  form.fecha = now.toISOString().slice(0, 16);
  
  fetchEquipos();
});
</script>
