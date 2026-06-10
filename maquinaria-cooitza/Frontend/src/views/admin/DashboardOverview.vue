<template>
  <transition name="fade-up" appear>
    <!-- Contenedor principal con Glassmorphism Background -->
    <div class="flex flex-col gap-6 p-6 font-sans w-full min-h-screen bg-gradient-to-br from-[#e0eaf5] via-[#f4f6f9] to-[#e8ebf2] rounded-3xl relative overflow-hidden">
      
      <!-- Efectos de luz de fondo (Glows) -->
      <div class="absolute top-0 left-0 w-96 h-96 bg-blue-400/10 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2 pointer-events-none"></div>
      <div class="absolute bottom-0 right-0 w-[500px] h-[500px] bg-purple-400/10 rounded-full blur-3xl translate-x-1/3 translate-y-1/3 pointer-events-none"></div>

      <div class="relative z-10 flex flex-col gap-6">
        <!-- Top Header & Filters -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <div>
            <h2 class="font-display text-3xl font-black text-slate-800 tracking-tight">Dashboard</h2>
            <span class="text-sm font-medium text-slate-500">Resumen general de la flota</span>
          </div>
          
          <div class="flex flex-wrap items-center gap-3">
            <!-- Fechas -->
            <div class="flex items-center gap-2 bg-white/70 backdrop-blur-md border border-white/50 px-4 py-2 text-sm text-slate-700 shadow-sm rounded-2xl hover:bg-white/90 transition-all">
              <Calendar class="w-4 h-4 text-slate-500" />
              <select v-model="selectedDateFilter" class="bg-transparent outline-none cursor-pointer font-medium text-slate-700">
                <option value="Hoy">Hoy</option>
                <option value="Esta semana">Esta semana</option>
                <option value="Este mes">Este mes</option>
                <option value="Todos">Todos los registros</option>
              </select>
            </div>
            <!-- Máquinas -->
            <div class="flex items-center gap-2 bg-white/70 backdrop-blur-md border border-white/50 px-4 py-2 text-sm text-slate-700 shadow-sm rounded-2xl hover:bg-white/90 transition-all">
              <Tractor class="w-4 h-4 text-slate-500" />
              <select class="bg-transparent outline-none cursor-pointer font-medium text-slate-700">
                <option>Todas las máquinas</option>
                <option v-for="m in maquinas" :key="m.id" :value="m.id">{{ m.marca }} ({{ m.identificador }})</option>
              </select>
            </div>
          </div>
        </div>

        <!-- 3 Statistical Cards (Glassmorphism) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          
          <!-- Maquinaria Activa -->
          <div class="bg-white/60 backdrop-blur-xl border border-white/60 p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex flex-col justify-between hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] hover:-translate-y-1 transition-all duration-300 rounded-3xl relative overflow-hidden group">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-400 to-blue-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex justify-between items-start mb-4">
              <span class="font-display text-[10px] font-black text-slate-500 uppercase tracking-widest">Maquinaria Activa</span>
              <div class="bg-blue-100/80 p-3 rounded-2xl shadow-[0_0_15px_rgba(59,130,246,0.3)]"><Tractor class="text-blue-600 w-6 h-6" /></div>
            </div>
            <div class="font-display text-4xl font-black text-slate-800">
              {{ maquinasActivasCount }} <span class="text-sm font-medium text-slate-400">de {{ totalMaquinasCount }}</span>
            </div>
            <div class="text-[11px] font-bold text-emerald-500 mt-3 flex items-center gap-1">
              <ArrowUp class="w-3.5 h-3.5" /> 100% operativos
            </div>
          </div>

          <!-- Horas Totales -->
          <div class="bg-white/60 backdrop-blur-xl border border-white/60 p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex flex-col justify-between hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] hover:-translate-y-1 transition-all duration-300 rounded-3xl relative overflow-hidden group">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex justify-between items-start mb-4">
              <span class="font-display text-[10px] font-black text-slate-500 uppercase tracking-widest">Horas Totales</span>
              <div class="bg-emerald-100/80 p-3 rounded-2xl shadow-[0_0_15px_rgba(16,185,129,0.3)]"><Clock class="text-emerald-600 w-6 h-6" /></div>
            </div>
            <div class="font-display text-4xl font-black text-slate-800">
              {{ totalHorasFleet }} <span class="text-sm font-medium text-slate-400">h</span>
            </div>
            <div class="text-[11px] font-bold text-emerald-500 mt-3 flex items-center gap-1">
              <ArrowUp class="w-3.5 h-3.5" /> Acumulado histórico
            </div>
          </div>

          <!-- Horómetro Promedio -->
          <div class="bg-white/60 backdrop-blur-xl border border-white/60 p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex flex-col justify-between hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] hover:-translate-y-1 transition-all duration-300 rounded-3xl relative overflow-hidden group">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-amber-400 to-amber-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex justify-between items-start mb-4">
              <span class="font-display text-[10px] font-black text-slate-500 uppercase tracking-widest">Horómetro Promedio</span>
              <div class="bg-amber-100/80 p-3 rounded-2xl shadow-[0_0_15px_rgba(245,158,11,0.3)]"><Activity class="text-amber-600 w-6 h-6" /></div>
            </div>
            <div class="font-display text-4xl font-black text-slate-800">
              {{ promedioHorasFleet }} <span class="text-sm font-medium text-slate-400">h</span>
            </div>
            <div class="text-[11px] font-bold text-emerald-500 mt-3 flex items-center gap-1">
              <ArrowUp class="w-3.5 h-3.5" /> Promedio por unidad
            </div>
          </div>
        </div>

        <!-- Middle Section: Chart & Map -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          
          <!-- Uso de Horómetros Chart -->
          <div class="lg:col-span-5 bg-white/60 backdrop-blur-xl border border-white/60 p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex flex-col rounded-3xl">
            <div class="flex justify-between items-center mb-6">
              <div class="flex items-center gap-2">
                <h3 class="font-display text-base font-bold text-slate-800">Uso de Horómetros</h3>
                <Info class="w-4 h-4 text-slate-400" />
              </div>
              <select class="bg-white/80 border border-white/50 text-[10px] font-bold uppercase tracking-wider px-3 py-1.5 text-slate-600 shadow-sm outline-none cursor-pointer rounded-xl">
                <option>Esta semana</option>
                <option>Histórico</option>
              </select>
            </div>

            <div class="flex gap-4 mb-4">
              <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase text-slate-500">
                <span class="w-3 h-3 bg-blue-500 rounded-full"></span> Horas Acumuladas
              </div>
            </div>

            <!-- Modern Graph Grid -->
            <div class="w-full flex items-end justify-center gap-5 h-56 pb-2 border-b border-slate-200/50 relative mt-4">
              <div class="absolute inset-x-0 top-1/4 border-t border-slate-200/50 dashed"></div>
              <div class="absolute inset-x-0 top-2/4 border-t border-slate-200/50 dashed"></div>
              <div class="absolute inset-x-0 top-3/4 border-t border-slate-200/50 dashed"></div>

              <div v-for="item in horasPorTipo" :key="item.tipo" class="flex flex-col items-center justify-end h-full group cursor-pointer z-10 w-12" :title="item.horas + ' h'">
                <div class="w-8 bg-blue-50/50 border border-white/50 h-full relative rounded-t-xl overflow-hidden shadow-inner">
                  <div class="absolute bottom-0 w-full bg-gradient-to-t from-blue-600 to-blue-400 group-hover:from-blue-700 group-hover:to-blue-500 transition-all duration-300 rounded-t-xl" :style="{ height: item.porcentaje + '%' }"></div>
                </div>
                <span class="font-sans text-[9px] font-bold text-slate-600 mt-3 text-center truncate w-full">{{ item.tipo }}</span>
              </div>
            </div>
          </div>

          <!-- Ubicación de Maquinaria Map -->
          <div class="lg:col-span-7 bg-white/60 backdrop-blur-xl border border-white/60 shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex flex-col relative h-[400px] rounded-3xl overflow-hidden">
            <div class="absolute top-0 left-0 right-0 p-5 bg-white/40 backdrop-blur-md z-10 flex justify-between items-center border-b border-white/40">
              <h3 class="font-display text-base font-bold text-slate-800">Ubicación de Maquinaria</h3>
              <span class="text-[10px] font-bold text-blue-600 uppercase cursor-pointer hover:underline flex items-center gap-1">Ver mapa completo <ArrowRight class="w-3 h-3" /></span>
            </div>
            <div id="dashboard-map" class="w-full h-full z-0"></div>
          </div>
        </div>

        <!-- Bottom Section: Desempeño por Maquinaria -->
        <div class="bg-white/60 backdrop-blur-xl border border-white/60 shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex flex-col rounded-3xl overflow-hidden">
          <div class="px-6 py-5 border-b border-white/50 flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white/20">
            <h3 class="font-display text-base font-bold text-slate-800">Desempeño por Maquinaria</h3>
            <div class="flex gap-3">
              <div class="relative">
                <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input type="text" placeholder="Buscar máquina..." class="pl-10 pr-4 py-2 text-sm bg-white/70 border border-white/50 outline-none focus:border-blue-400 w-64 rounded-xl shadow-sm transition-all" />
              </div>
              <button class="flex items-center gap-2 px-4 py-2 bg-white/70 border border-white/50 text-sm font-medium text-slate-700 hover:bg-white transition-colors rounded-xl shadow-sm">
                <Filter class="w-4 h-4" /> Filtros
              </button>
              <button class="flex items-center gap-2 px-4 py-2 bg-white/70 border border-white/50 text-sm font-medium text-slate-700 hover:bg-white transition-colors rounded-xl shadow-sm">
                <Download class="w-4 h-4" /> Exportar
              </button>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-white/50 text-slate-500">
                  <th class="p-5 text-[10px] font-bold uppercase tracking-widest">Maquinaria</th>
                  <th class="p-5 text-[10px] font-bold uppercase tracking-widest">Proyecto</th>
                  <th class="p-5 text-[10px] font-bold uppercase tracking-widest text-center">Horómetro Inicial</th>
                  <th class="p-5 text-[10px] font-bold uppercase tracking-widest text-center">Horómetro Final</th>
                  <th class="p-5 text-[10px] font-bold uppercase tracking-widest text-center">Total de Horas</th>
                  <th class="p-5 text-[10px] font-bold uppercase tracking-widest">% Trabajo</th>
                  <th class="p-5 text-[10px] font-bold uppercase tracking-widest text-center">Estado</th>
                  <th class="p-5 text-[10px] font-bold uppercase tracking-widest">Ubicación</th>
                  <th class="p-5 text-[10px] font-bold uppercase tracking-widest text-center">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/40 text-sm">
                <tr v-for="row in tableData" :key="row.id" class="hover:bg-white/40 transition-colors group">
                  <td class="p-5">
                    <div class="flex items-center gap-3">
                      <div class="w-12 h-12 bg-white border border-white/60 shadow-sm flex items-center justify-center rounded-xl overflow-hidden shrink-0">
                        <!-- Simulated vehicle icon based on type -->
                        <img src="https://cdn-icons-png.flaticon.com/512/2882/2882890.png" class="w-7 h-7 object-contain opacity-80" alt="icon" />
                      </div>
                      <div>
                        <div class="font-bold text-slate-800">{{ row.marca }} {{ row.modelo }}</div>
                        <div class="text-[10px] font-mono text-slate-500 mt-0.5">{{ row.identificador }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="p-5 text-slate-600 text-xs font-medium">Ampliación Vial</td>
                  <td class="p-5 text-center font-mono font-bold text-slate-600">{{ row.h_inicial }}</td>
                  <td class="p-5 text-center font-mono font-bold text-slate-800">{{ row.h_final }}</td>
                  <td class="p-5 text-center font-mono font-bold text-blue-600 bg-blue-50/30 rounded-lg">{{ row.total_trabajado }} h</td>
                  
                  <td class="p-5">
                    <div class="flex items-center gap-3">
                      <span class="text-xs font-bold text-slate-700 w-8">{{ row.porcentaje_trabajo }}%</span>
                      <div class="w-24 bg-slate-200/70 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-full rounded-full" :style="{ width: row.porcentaje_trabajo + '%' }"></div>
                      </div>
                    </div>
                  </td>
                  
                  <td class="p-5 text-center">
                    <span class="px-3 py-1 text-[10px] font-bold uppercase rounded-full" 
                      :class="row.estado === 'Operativo' ? 'bg-emerald-100/80 text-emerald-700' : 'bg-red-100/80 text-red-700'">
                      {{ row.estado === 'Operativo' ? 'Activo' : row.estado }}
                    </span>
                  </td>
                  
                  <td class="p-5">
                    <div class="flex items-center gap-2 text-xs font-medium text-slate-600">
                      <MapPin class="w-4 h-4 text-blue-500" v-if="row.latitud" />
                      <span v-if="row.latitud" class="truncate max-w-[120px]">San Pedro</span>
                      <span v-else class="text-slate-400 italic">Sin ubicación</span>
                    </div>
                  </td>
                  
                  <td class="p-5 text-center">
                    <button class="w-8 h-8 flex items-center justify-center bg-white/60 border border-white/60 shadow-sm text-slate-400 hover:text-blue-600 transition-all rounded-xl mx-auto hover:bg-white" title="Opciones">
                      <MoreHorizontal class="w-4 h-4" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div class="px-6 py-4 border-t border-white/50 flex justify-between items-center text-xs text-slate-500 bg-white/20">
            <span>Mostrando 1 a {{ tableData.length }} de {{ tableData.length }} máquinas</span>
            <div class="flex gap-2">
              <button class="w-8 h-8 flex items-center justify-center border border-white/60 bg-white/50 text-slate-400 cursor-not-allowed rounded-lg shadow-sm"><ChevronLeft class="w-4 h-4" /></button>
              <button class="w-8 h-8 flex items-center justify-center border border-blue-500 bg-blue-500 text-white font-bold rounded-lg shadow-md shadow-blue-500/30">1</button>
              <button class="w-8 h-8 flex items-center justify-center border border-white/60 bg-white/50 text-slate-400 cursor-not-allowed rounded-lg shadow-sm"><ChevronRight class="w-4 h-4" /></button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, onMounted, watch } from 'vue';
import { 
  Users, Truck, Tractor, Clock, Activity, AlertTriangle, ArrowUp, ArrowRight,
  Calendar, Search, Filter, Download, MapPin, MoreHorizontal, ChevronLeft, ChevronRight, Info
} from "lucide-vue-next";

// @ts-ignore
import L from 'leaflet';
// @ts-ignore
import 'leaflet/dist/leaflet.css';
// @ts-ignore
import markerIcon from 'leaflet/dist/images/marker-icon.png';
// @ts-ignore
import markerIconRetina from 'leaflet/dist/images/marker-icon-2x.png';
// @ts-ignore
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIconRetina,
  iconUrl: markerIcon,
  shadowUrl: markerShadow,
});

const props = defineProps<{
  maquinas?: any[];
  registros?: any[];
}>();

const emit = defineEmits(['refresh-registros']);

// Statistical Cards Computed
const totalMaquinasCount = computed(() => props.maquinas?.length || 0);
const maquinasActivasCount = computed(() => props.maquinas?.filter(m => m.estado === 'Operativo').length || 0);

const totalHorasFleet = computed(() => {
  if (!props.maquinas) return 0;
  return props.maquinas.reduce((acc, m) => acc + (parseFloat(m.horas_acumuladas) || 0), 0).toFixed(1);
});

const promedioHorasFleet = computed(() => {
  if (!props.maquinas || props.maquinas.length === 0) return 0;
  return (parseFloat(totalHorasFleet.value) / props.maquinas.length).toFixed(1);
});

// Chart Data (Uso de Horómetros)
const horasPorTipo = computed(() => {
  if (!props.maquinas || props.maquinas.length === 0) return [];
  const agrupado: Record<string, number> = {};
  let maxHoras = 0;
  props.maquinas.forEach(m => {
    const tipo = m.tipo || 'Otro';
    agrupado[tipo] = (agrupado[tipo] || 0) + (parseFloat(m.horas_acumuladas) || 0);
  });
  const resultado = Object.keys(agrupado).map(tipo => {
    if (agrupado[tipo] > maxHoras) maxHoras = agrupado[tipo];
    return { tipo, horas: agrupado[tipo].toFixed(1) };
  });
  return resultado.map(r => ({
    ...r,
    porcentaje: maxHoras > 0 ? (parseFloat(r.horas) / maxHoras) * 100 : 0
  }));
});

// Filters
const selectedDateFilter = ref('Hoy');

const filteredRegistros = computed(() => {
  if (!props.registros) return [];
  const now = new Date();
  
  return props.registros.filter(r => {
    if (selectedDateFilter.value === 'Todos') return true;
    
    const d = new Date(r.fecha_registro);
    if (selectedDateFilter.value === 'Hoy') {
      return d.toDateString() === now.toDateString();
    }
    if (selectedDateFilter.value === 'Esta semana') {
      const weekAgo = new Date();
      weekAgo.setDate(now.getDate() - 7);
      return d >= weekAgo;
    }
    if (selectedDateFilter.value === 'Este mes') {
      return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
    }
    return true;
  });
});

// Table Data (Desempeño por Maquinaria)
const tableData = computed(() => {
  if (!props.maquinas) return [];
  
  return props.maquinas.map(m => {
    // Find registros for this machine in the filtered time range
    const machineRegistros = filteredRegistros.value.filter(r => r.maquina_id === m.id) || [];
    
    // Sort chronologically
    machineRegistros.sort((a, b) => new Date(a.fecha_registro).getTime() - new Date(b.fecha_registro).getTime());
    
    let h_inicial = 0;
    let h_final = 0;
    
    if (machineRegistros.length > 0) {
      h_inicial = parseFloat(machineRegistros[0].valor_horometro) || 0;
      h_final = parseFloat(machineRegistros[machineRegistros.length - 1].valor_horometro) || 0;
    }
    
    // Calculate total hours worked in this period
    let total_trabajado = 0;
    if (machineRegistros.length > 1) {
      total_trabajado = Math.max(0, h_final - h_inicial);
    } else if (machineRegistros.length === 1 && machineRegistros[0].tipo_registro === 'final') {
      total_trabajado = 0;
    }

    // Simulate a work percentage based on accumulated hours vs fleet max
    const maxHorasFlota = parseFloat(totalHorasFleet.value) || 1;
    const porcentaje = Math.min(100, Math.max(10, Math.round(((parseFloat(m.horas_acumuladas) || 0) / (maxHorasFlota / props.maquinas!.length)) * 50)));

    return {
      ...m,
      h_inicial: h_inicial > 0 ? h_inicial.toFixed(2) : '-',
      h_final: h_final > 0 ? h_final.toFixed(2) : '-',
      total_trabajado: total_trabajado > 0 ? total_trabajado.toFixed(2) : '0.00',
      latitud: machineRegistros.length > 0 ? machineRegistros[machineRegistros.length - 1].latitud : null,
      longitud: machineRegistros.length > 0 ? machineRegistros[machineRegistros.length - 1].longitud : null,
      porcentaje_trabajo: porcentaje
    };
  });
});

// Map Integration
let mapInstance: L.Map | null = null;
const markers: L.Marker[] = [];

const initMap = () => {
  if (mapInstance) {
    mapInstance.remove();
  }
  
  mapInstance = L.map('dashboard-map', { zoomControl: false }).setView([14.6349, -90.5069], 7); 
  
  L.control.zoom({ position: 'bottomright' }).addTo(mapInstance);

  L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(mapInstance);

  updateMarkers();
};

const updateMarkers = () => {
  if (!mapInstance) return;
  
  // Clear old markers
  markers.forEach(m => mapInstance!.removeLayer(m));
  markers.length = 0;

  const validLocations = tableData.value.filter(m => m.latitud && m.longitud);
  if (validLocations.length === 0) return;

  const bounds = L.latLngBounds([]);

  validLocations.forEach(m => {
    const lat = parseFloat(m.latitud);
    const lng = parseFloat(m.longitud);
    
    const isOperativo = m.estado === 'Operativo';
    const color = isOperativo ? '#10b981' : '#f59e0b'; // Emerald or amber like mockup
    
    const svgIcon = L.divIcon({
      className: 'custom-pin',
      html: `
        <div class="relative w-10 h-10 flex items-center justify-center">
          <div class="absolute inset-0 bg-white rounded-full shadow-md border border-white"></div>
          <div class="relative z-10 w-8 h-8 rounded-full flex items-center justify-center text-white shadow-inner" style="background: ${color}">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10h12"/><path d="M7 14h12"/><path d="M3 6h18"/><path d="M3 18h18"/></svg>
          </div>
          <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-0 h-0 border-l-[6px] border-r-[6px] border-t-[8px] border-l-transparent border-r-transparent border-t-white drop-shadow-sm"></div>
        </div>`,
      iconSize: [40, 40],
      iconAnchor: [20, 40]
    });

    const marker = L.marker([lat, lng], { icon: svgIcon })
      .bindPopup(`<strong>${m.marca} ${m.modelo}</strong><br>Estado: ${m.estado}<br>Horas: ${m.horas_acumuladas}`)
      .addTo(mapInstance!);
      
    markers.push(marker);
    bounds.extend([lat, lng]);
  });

  if (bounds.isValid()) {
    mapInstance.fitBounds(bounds, { padding: [50, 50], maxZoom: 14 });
  }
};

onMounted(() => {
  setTimeout(() => {
    initMap();
  }, 300); 
});

watch(tableData, () => {
  updateMarkers();
}, { deep: true });

</script>

<style scoped>
.fade-up-enter-active,
.fade-up-leave-active {
  transition: opacity 0.4s ease, transform 0.4s ease;
}
.fade-up-enter-from,
.fade-up-leave-to {
  opacity: 0;
  transform: translateY(15px);
}
</style>
