<template>
  <div class="min-h-screen bg-background text-on-background font-body-md p-container-margin md:p-stack-lg">
    <header class="max-w-5xl mx-auto mb-stack-lg flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <h1 class="text-headline-lg-mobile md:text-headline-lg font-headline-lg-mobile md:font-headline-lg text-primary-fixed tracking-tight uppercase mb-2">Registrar Partido</h1>
        <p class="text-on-surface-variant text-body-md font-body-md">Registra los resultados y estadísticas de los jugadores.</p>
      </div>
      <button @click="$router.go(-1)" class="flex items-center gap-2 px-4 py-2 border border-white/10 rounded-lg hover:border-primary-fixed transition-colors text-on-surface bg-surface-container-low text-label-sm font-label-sm uppercase">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span> Volver
      </button>
    </header>

    <form @submit.prevent="validarYGuardar" class="max-w-5xl mx-auto space-y-stack-md">

      <!-- SECCIÓN A: Datos Generales -->
      <section class="glass-card rounded-xl p-stack-md">
        <h2 class="text-title-md font-title-md text-on-surface mb-stack-sm flex items-center gap-2 border-b border-white/10 pb-3">
          <span class="material-symbols-outlined text-primary-fixed">event</span> Datos del Encuentro
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-gutter mb-stack-md">
          <div class="space-y-2">
            <label class="text-label-sm text-on-surface-variant block">Fecha y Hora</label>
            <input type="datetime-local" v-model="form.fecha" required
                   class="w-full bg-surface-container-low border-b border-white/10 focus:border-primary-fixed focus:ring-0 focus:outline-none py-2 px-3 text-on-surface rounded-t-md" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-[1fr,auto,1fr] gap-gutter items-center">
          <!-- Local -->
          <div class="bg-surface-container-low p-4 rounded-xl border border-white/5">
            <label class="text-label-sm text-primary-fixed uppercase tracking-wider block mb-2">Equipo Local</label>
            <select v-model="form.equipo_local_id" required @change="cargarJugadores"
                    class="w-full bg-surface border border-white/10 rounded-lg px-4 py-2.5 text-on-surface focus:border-primary-fixed transition-colors outline-none mb-4">
              <option value="">Seleccione equipo local...</option>
              <option v-for="eq in equiposDisponibles" :key="eq.id" :value="eq.id" :disabled="form.equipo_visitante_id === eq.id">{{ eq.nombre }}</option>
            </select>
            <div class="text-center">
              <label class="text-label-sm text-on-surface-variant block mb-1">Goles</label>
              <input type="number" min="0" v-model.number="form.goles_local" required
                     class="w-20 text-center text-display-lg font-display-lg bg-surface border border-white/10 rounded-lg py-2 text-on-surface focus:border-primary-fixed outline-none" />
            </div>
          </div>

          <div class="text-headline-lg font-headline-lg text-surface-variant px-4 text-center">VS</div>

          <!-- Visitante -->
          <div class="bg-surface-container-low p-4 rounded-xl border border-white/5">
            <label class="text-label-sm text-on-surface-variant uppercase tracking-wider block mb-2">Equipo Visitante</label>
            <select v-model="form.equipo_visitante_id" required @change="cargarJugadores"
                    class="w-full bg-surface border border-white/10 rounded-lg px-4 py-2.5 text-on-surface focus:border-primary-fixed transition-colors outline-none mb-4">
              <option value="">Seleccione equipo visitante...</option>
              <option v-for="eq in equiposDisponibles" :key="eq.id" :value="eq.id" :disabled="form.equipo_local_id === eq.id">{{ eq.nombre }}</option>
            </select>
            <div class="text-center">
              <label class="text-label-sm text-on-surface-variant block mb-1">Goles</label>
              <input type="number" min="0" v-model.number="form.goles_visitante" required
                     class="w-20 text-center text-display-lg font-display-lg bg-surface border border-white/10 rounded-lg py-2 text-on-surface focus:border-primary-fixed outline-none" />
            </div>
          </div>
        </div>
      </section>

      <div v-if="todosLosJugadores.length > 0" class="space-y-stack-md">

        <!-- SECCIÓN C: Goleadores -->
        <section class="glass-card rounded-xl p-stack-md">
          <h2 class="text-title-md font-title-md text-on-surface mb-stack-sm flex items-center gap-2 border-b border-white/10 pb-3">
            <span class="material-symbols-outlined text-primary-fixed">sports_soccer</span> Goleadores del Partido
          </h2>

          <div class="space-y-3 mb-4">
            <div v-for="(item, index) in goleadores" :key="index" class="flex flex-col sm:flex-row items-start sm:items-center gap-3 bg-surface-container-low p-2 rounded-lg border border-white/5">
              <select v-model="item.jugador_id" class="flex-1 w-full bg-transparent border-none text-on-surface focus:ring-0 text-sm">
                <option value="" disabled>Seleccionar jugador...</option>
                <option v-for="j in todosLosJugadores" :key="j.id" :value="j.id">
                  {{ j.nombre }} ({{ getNombreEquipo(j.equipo_id) }})
                </option>
              </select>
              <div class="flex items-center gap-3 w-full sm:w-auto">
                <input type="number" min="1" v-model.number="item.goles" placeholder="Goles"
                       class="w-20 bg-transparent border-l border-white/10 text-on-surface focus:ring-0 text-sm placeholder:text-on-surface-variant text-center" />
                <button type="button" @click="eliminarFila(goleadores, index)" class="text-on-surface-variant hover:text-error transition-colors p-1">
                  <span class="material-symbols-outlined text-sm">close</span>
                </button>
              </div>
            </div>
          </div>

          <button type="button" @click="agregarFila(goleadores, {jugador_id: '', goles: 1})" class="w-full py-2 border border-dashed border-white/20 rounded-lg text-on-surface-variant hover:border-primary-fixed hover:text-primary-fixed transition-colors text-sm flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-sm">add</span> Agregar goleador
          </button>
        </section>

        <!-- SECCIÓN D: Porteros -->
        <section class="glass-card rounded-xl p-stack-md">
          <h2 class="text-title-md font-title-md text-on-surface mb-stack-sm flex items-center gap-2 border-b border-white/10 pb-3">
            <span class="material-symbols-outlined text-primary-fixed">sports_handball</span> Porteros del Partido
          </h2>

          <div class="space-y-3 mb-4">
            <div v-for="(item, index) in porteros" :key="index" class="flex flex-col sm:flex-row items-start sm:items-center gap-3 bg-surface-container-low p-2 rounded-lg border border-white/5">
              <select v-model="item.jugador_id" class="flex-1 w-full bg-transparent border-none text-on-surface focus:ring-0 text-sm">
                <option value="" disabled>Seleccionar portero...</option>
                <option v-for="j in porterosDisponibles" :key="j.id" :value="j.id">
                  {{ j.nombre }} ({{ getNombreEquipo(j.equipo_id) }})
                </option>
              </select>
              <div class="flex items-center gap-3 w-full sm:w-auto">
                <div class="flex items-center bg-surface border border-white/10 rounded-lg overflow-hidden">
                  <span class="px-3 py-2 text-on-surface-variant text-xs font-bold bg-surface-container-high">Recibidos</span>
                  <input type="number" min="0" v-model.number="item.goles_recibidos"
                         class="w-16 bg-transparent px-3 py-2 text-on-surface text-center focus:outline-none text-sm" />
                </div>
                <button type="button" @click="eliminarFila(porteros, index)" class="text-on-surface-variant hover:text-error transition-colors p-1">
                  <span class="material-symbols-outlined text-sm">close</span>
                </button>
              </div>
            </div>
          </div>

          <button type="button" @click="agregarFila(porteros, {jugador_id: '', goles_recibidos: 0})" class="w-full py-2 border border-dashed border-white/20 rounded-lg text-on-surface-variant hover:border-primary-fixed hover:text-primary-fixed transition-colors text-sm flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-sm">add</span> Agregar portero
          </button>
        </section>

        <!-- SECCIÓN E: Tarjetas -->
        <section class="glass-card rounded-xl p-stack-md">
          <h2 class="text-title-md font-title-md text-on-surface mb-stack-sm flex items-center gap-2 border-b border-white/10 pb-3">
            <span class="material-symbols-outlined text-error">style</span> Tarjetas del Partido
          </h2>

          <div class="space-y-3 mb-4">
            <div v-for="(item, index) in tarjetas" :key="index" class="flex flex-col sm:flex-row items-start sm:items-center gap-3 bg-surface-container-low p-2 rounded-lg border border-white/5">
              <select v-model="item.jugador_id" class="flex-1 w-full bg-transparent border-none text-on-surface focus:ring-0 text-sm">
                <option value="" disabled>Seleccionar jugador...</option>
                <option v-for="j in todosLosJugadores" :key="j.id" :value="j.id">
                  {{ j.nombre }} ({{ getNombreEquipo(j.equipo_id) }})
                </option>
              </select>
              <div class="flex items-center gap-4 w-full sm:w-auto ml-2">
                <label class="flex items-center gap-2 cursor-pointer text-sm">
                  <input type="radio" :name="`tipo_tarjeta_${index}`" value="amarilla" v-model="item.tipo" class="accent-yellow-400 w-4 h-4" />
                  <span class="text-yellow-400 font-bold">Amarilla</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-sm">
                  <input type="radio" :name="`tipo_tarjeta_${index}`" value="roja" v-model="item.tipo" class="accent-red-500 w-4 h-4" />
                  <span class="text-red-500 font-bold">Roja</span>
                </label>
                <button type="button" @click="eliminarFila(tarjetas, index)" class="text-on-surface-variant hover:text-error transition-colors p-1">
                  <span class="material-symbols-outlined text-sm">close</span>
                </button>
              </div>
            </div>
          </div>

          <button type="button" @click="agregarFila(tarjetas, {jugador_id: '', tipo: 'amarilla'})" class="w-full py-2 border border-dashed border-white/20 rounded-lg text-on-surface-variant hover:border-yellow-400 hover:text-yellow-400 transition-colors text-sm flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-sm">add</span> Agregar tarjeta
          </button>
        </section>

      </div>

      <div class="flex justify-end gap-4" v-if="todosLosJugadores.length > 0">
        <button type="submit" :disabled="submitting"
                class="px-8 py-4 bg-primary-fixed text-on-primary font-bold rounded-lg hover:brightness-110 disabled:opacity-50 transition-all shadow-[0_0_20px_rgba(185,246,63,0.3)] flex items-center gap-2 uppercase text-title-md font-title-md">
          <span v-if="submitting">Guardando...</span>
          <template v-else>
            <span class="material-symbols-outlined">save</span> Guardar Partido
          </template>
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
