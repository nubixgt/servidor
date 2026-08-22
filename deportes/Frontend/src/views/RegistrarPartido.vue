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

    <form @submit.prevent="validarYGuardar" class="max-w-5xl mx-auto space-y-8">
      
      <!-- SECCIÓN A: Datos Generales -->
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
              <input type="number" min="0" v-model.number="form.goles_local" required
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
              <input type="number" min="0" v-model.number="form.goles_visitante" required
                     class="w-20 text-center font-black text-3xl bg-black border border-gray-700 rounded-lg py-2 text-white focus:border-gray-500 outline-none" />
            </div>
          </div>
        </div>
      </div>

      <div v-if="todosLosJugadores.length > 0" class="space-y-8">
        
        <!-- SECCIÓN C: Goleadores -->
        <div class="bg-[#121212] border border-gray-800 p-6 rounded-2xl shadow-xl">
          <h2 class="text-xl font-bold mb-4 flex items-center gap-2 border-b border-gray-800 pb-3">
            <i class="fas fa-futbol text-[#ccff00]"></i> Goleadores del Partido
          </h2>
          
          <div class="space-y-3 mb-4">
            <div v-for="(item, index) in goleadores" :key="index" class="flex flex-col sm:flex-row items-start sm:items-center gap-3 bg-gray-900/30 p-3 rounded-xl border border-gray-800">
              <div class="flex-grow w-full">
                <select v-model="item.jugador_id" class="w-full bg-black border border-gray-700 rounded-lg px-3 py-2 text-white focus:border-[#ccff00] outline-none text-sm">
                  <option value="" disabled>Seleccionar jugador...</option>
                  <option v-for="j in todosLosJugadores" :key="j.id" :value="j.id">
                    {{ j.nombre }} ({{ getNombreEquipo(j.equipo_id) }})
                  </option>
                </select>
              </div>
              <div class="flex items-center gap-3 w-full sm:w-auto">
                <input type="number" min="1" v-model.number="item.goles" placeholder="Goles"
                       class="w-24 bg-black border border-gray-700 rounded-lg px-3 py-2 text-white text-center focus:border-[#ccff00] outline-none text-sm" />
                <button type="button" @click="eliminarFila(goleadores, index)" class="p-2 text-gray-500 hover:text-red-500 transition-colors">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
          </div>
          
          <button type="button" @click="agregarFila(goleadores, {jugador_id: '', goles: 1})" class="text-sm font-bold text-[#ccff00] hover:text-[#b3e600] flex items-center gap-1 transition-colors">
            <i class="fas fa-plus-circle"></i> Agregar goleador
          </button>
        </div>

        <!-- SECCIÓN D: Porteros -->
        <div class="bg-[#121212] border border-gray-800 p-6 rounded-2xl shadow-xl">
          <h2 class="text-xl font-bold mb-4 flex items-center gap-2 border-b border-gray-800 pb-3">
            <i class="fas fa-hands text-gray-300"></i> Porteros del Partido
          </h2>
          
          <div class="space-y-3 mb-4">
            <div v-for="(item, index) in porteros" :key="index" class="flex flex-col sm:flex-row items-start sm:items-center gap-3 bg-gray-900/30 p-3 rounded-xl border border-gray-800">
              <div class="flex-grow w-full">
                <select v-model="item.jugador_id" class="w-full bg-black border border-gray-700 rounded-lg px-3 py-2 text-white focus:border-gray-400 outline-none text-sm">
                  <option value="" disabled>Seleccionar portero...</option>
                  <option v-for="j in porterosDisponibles" :key="j.id" :value="j.id">
                    {{ j.nombre }} ({{ getNombreEquipo(j.equipo_id) }})
                  </option>
                </select>
              </div>
              <div class="flex items-center gap-3 w-full sm:w-auto">
                <div class="flex items-center bg-black border border-gray-700 rounded-lg overflow-hidden">
                  <span class="px-3 py-2 text-gray-500 text-xs font-bold bg-gray-800/50">Recibidos</span>
                  <input type="number" min="0" v-model.number="item.goles_recibidos"
                         class="w-20 bg-transparent px-3 py-2 text-white text-center focus:outline-none text-sm" />
                </div>
                <button type="button" @click="eliminarFila(porteros, index)" class="p-2 text-gray-500 hover:text-red-500 transition-colors">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
          </div>
          
          <button type="button" @click="agregarFila(porteros, {jugador_id: '', goles_recibidos: 0})" class="text-sm font-bold text-gray-300 hover:text-white flex items-center gap-1 transition-colors">
            <i class="fas fa-plus-circle"></i> Agregar portero
          </button>
        </div>

        <!-- SECCIÓN E: Tarjetas -->
        <div class="bg-[#121212] border border-gray-800 p-6 rounded-2xl shadow-xl">
          <h2 class="text-xl font-bold mb-4 flex items-center gap-2 border-b border-gray-800 pb-3">
            <div class="flex gap-1"><div class="w-3 h-4 bg-yellow-400 rounded-sm"></div><div class="w-3 h-4 bg-red-500 rounded-sm"></div></div> 
            Tarjetas del Partido
          </h2>
          
          <div class="space-y-3 mb-4">
            <div v-for="(item, index) in tarjetas" :key="index" class="flex flex-col sm:flex-row items-start sm:items-center gap-3 bg-gray-900/30 p-3 rounded-xl border border-gray-800">
              <div class="flex-grow w-full">
                <select v-model="item.jugador_id" class="w-full bg-black border border-gray-700 rounded-lg px-3 py-2 text-white focus:border-yellow-500 outline-none text-sm">
                  <option value="" disabled>Seleccionar jugador...</option>
                  <option v-for="j in todosLosJugadores" :key="j.id" :value="j.id">
                    {{ j.nombre }} ({{ getNombreEquipo(j.equipo_id) }})
                  </option>
                </select>
              </div>
              <div class="flex items-center gap-4 w-full sm:w-auto ml-2">
                <label class="flex items-center gap-2 cursor-pointer text-sm">
                  <input type="radio" :name="`tipo_tarjeta_${index}`" value="amarilla" v-model="item.tipo" class="accent-yellow-400 w-4 h-4" />
                  <span class="text-yellow-400 font-bold">Amarilla</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-sm">
                  <input type="radio" :name="`tipo_tarjeta_${index}`" value="roja" v-model="item.tipo" class="accent-red-500 w-4 h-4" />
                  <span class="text-red-500 font-bold">Roja</span>
                </label>
                <button type="button" @click="eliminarFila(tarjetas, index)" class="p-2 text-gray-500 hover:text-red-500 transition-colors ml-2">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
          </div>
          
          <button type="button" @click="agregarFila(tarjetas, {jugador_id: '', tipo: 'amarilla'})" class="text-sm font-bold text-gray-400 hover:text-white flex items-center gap-1 transition-colors">
            <i class="fas fa-plus-circle"></i> Agregar tarjeta
          </button>
        </div>

      </div>

      <div class="flex justify-end gap-4" v-if="todosLosJugadores.length > 0">
        <button type="submit" :disabled="submitting"
                class="px-8 py-4 bg-[#ccff00] text-black font-black rounded-lg hover:bg-[#b3e600] disabled:opacity-50 transition-colors shadow-[0_0_20px_rgba(204,255,0,0.3)]">
          <i v-if="submitting" class="fas fa-spinner fa-spin mr-2"></i>
          <span v-else><i class="fas fa-save mr-2"></i>GUARDAR PARTIDO</span>
        </button>
      </div>

    </form>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';
import Swal from 'sweetalert2';

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

// Secciones iterables
const goleadores = ref([]);
const porteros = ref([]);
const tarjetas = ref([]);

const submitting = ref(false);

const getAuthHeaders = () => {
  return {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  };
};

const equiposDisponibles = computed(() => {
  if (userRole === 'admin') return equipos.value;
  return equipos.value;
});

const todosLosJugadores = computed(() => {
  return [...jugadoresLocal.value, ...jugadoresVisitante.value];
});

const porterosDisponibles = computed(() => {
  return todosLosJugadores.value.filter(j => j.posicion === 'Portero');
});

const getNombreEquipo = (id) => {
  const eq = equipos.value.find(e => e.id === id);
  return eq ? eq.nombre : '';
};

const fetchEquipos = async () => {
  try {
    const res = await api.get(`/admin/equipos`, { headers: getAuthHeaders() });
    equipos.value = res.data;
  } catch (e) {
    console.error(e);
  }
};

const cargarJugadores = async () => {
  if (form.equipo_local_id) {
    try {
      const res = await api.get(`/admin/equipos/${form.equipo_local_id}/jugadores`, { headers: getAuthHeaders() });
      jugadoresLocal.value = res.data.jugadores_activos.map(j => ({...j, equipo_id: form.equipo_local_id}));
    } catch (e) { console.error(e); }
  } else {
    jugadoresLocal.value = [];
  }

  if (form.equipo_visitante_id) {
    try {
      const res = await api.get(`/admin/equipos/${form.equipo_visitante_id}/jugadores`, { headers: getAuthHeaders() });
      jugadoresVisitante.value = res.data.jugadores_activos.map(j => ({...j, equipo_id: form.equipo_visitante_id}));
    } catch (e) { console.error(e); }
  } else {
    jugadoresVisitante.value = [];
  }
  
  // Limpiar selecciones si se cambian los equipos
  goleadores.value = [];
  porteros.value = [];
  tarjetas.value = [];
};

const agregarFila = (lista, obj) => {
  lista.push({...obj});
};

const eliminarFila = (lista, index) => {
  lista.splice(index, 1);
};

const validarYGuardar = async () => {
  // 1. Validar que si es encargado, al menos uno de los equipos sea el suyo
  if (userRole !== 'admin' && form.equipo_local_id != miEquipoId && form.equipo_visitante_id != miEquipoId) {
    Swal.fire('Atención', 'Debes seleccionar tu equipo en al menos una de las opciones.', 'warning');
    return;
  }

  // 2. Limpiar filas vacías (jugador_id no seleccionado)
  const goleadoresValidos = goleadores.value.filter(g => g.jugador_id !== '');
  const porterosValidos = porteros.value.filter(p => p.jugador_id !== '');
  const tarjetasValidas = tarjetas.value.filter(t => t.jugador_id !== '');

  // 3. Validar sumatoria de goles locales y visitantes
  let sumaGolesLocal = 0;
  let sumaGolesVisitante = 0;

  goleadoresValidos.forEach(g => {
    const jugador = todosLosJugadores.value.find(j => j.id === g.jugador_id);
    if (jugador) {
      if (jugador.equipo_id === form.equipo_local_id) sumaGolesLocal += (g.goles || 0);
      if (jugador.equipo_id === form.equipo_visitante_id) sumaGolesVisitante += (g.goles || 0);
    }
  });

  if (sumaGolesLocal !== form.goles_local) {
    Swal.fire({
      icon: 'error',
      title: 'Error en goles locales',
      text: `Los goles asignados a los jugadores del equipo local suman ${sumaGolesLocal} pero el marcador indica ${form.goles_local}. Verifica antes de guardar.`,
      confirmButtonColor: '#ccff00'
    });
    return;
  }

  if (sumaGolesVisitante !== form.goles_visitante) {
    Swal.fire({
      icon: 'error',
      title: 'Error en goles visitantes',
      text: `Los goles asignados a los jugadores del equipo visitante suman ${sumaGolesVisitante} pero el marcador indica ${form.goles_visitante}. Verifica antes de guardar.`,
      confirmButtonColor: '#ccff00'
    });
    return;
  }

  // 4. Transformar todo al formato que espera el backend
  // Formato: array de objetos {jugador_id, goles, tarjetas_amarillas, tarjetas_rojas, goles_recibidos, jugo_como_portero}
  const statsMap = {}; // key: jugador_id
  
  const initJugadorEnMap = (id) => {
    if (!statsMap[id]) {
      statsMap[id] = { jugador_id: id, goles: 0, tarjetas_amarillas: 0, tarjetas_rojas: 0, goles_recibidos: 0, jugo_como_portero: false };
    }
  };

  goleadoresValidos.forEach(g => {
    initJugadorEnMap(g.jugador_id);
    statsMap[g.jugador_id].goles += g.goles;
  });

  tarjetasValidas.forEach(t => {
    initJugadorEnMap(t.jugador_id);
    if (t.tipo === 'amarilla') statsMap[t.jugador_id].tarjetas_amarillas += 1;
    if (t.tipo === 'roja') statsMap[t.jugador_id].tarjetas_rojas += 1;
  });

  porterosValidos.forEach(p => {
    initJugadorEnMap(p.jugador_id);
    statsMap[p.jugador_id].goles_recibidos += p.goles_recibidos;
    statsMap[p.jugador_id].jugo_como_portero = true;
  });

  const payload = {
    ...form,
    estadisticas: Object.values(statsMap)
  };

  submitting.value = true;
  try {
    await api.post(`/partidos`, payload, { headers: getAuthHeaders() });
    
    Swal.fire({
      icon: 'success',
      title: '¡Partido registrado!',
      text: 'El partido y sus estadísticas se guardaron exitosamente.',
      confirmButtonColor: '#ccff00',
      background: '#121212',
      color: '#fff'
    }).then(() => {
      router.push('/historial-partidos');
    });

  } catch (e) {
    Swal.fire('Error', e.response?.data?.error || 'Error al guardar el partido', 'error');
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  const now = new Date();
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
  form.fecha = now.toISOString().slice(0, 16);
  fetchEquipos();
});
</script>
