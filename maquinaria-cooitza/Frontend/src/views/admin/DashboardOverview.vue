<template>
  <transition name="fade-up" appear>
    <div class="flex flex-col gap-6 pb-12 font-sans w-full">
      
      <!-- Top Header & Filters -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
          <h2 class="font-display text-3xl font-black text-slate-800 tracking-tight">Dashboard</h2>
          <span class="text-sm font-medium text-slate-500">Resumen general de la flota</span>
        </div>
        
        <div class="flex flex-wrap items-center gap-3">
          <!-- Fechas -->
          <div class="flex items-center gap-2 bg-white border border-[#cbd5e1] px-3 py-2 text-sm text-slate-600 shadow-sm">
            <Calendar class="w-4 h-4 text-slate-400" />
            <select v-model="selectedDateFilter" class="bg-transparent outline-none cursor-pointer font-medium">
              <option value="Hoy">Hoy</option>
              <option value="Esta semana">Esta semana</option>
              <option value="Este mes">Este mes</option>
              <option value="Todos">Todos los registros</option>
            </select>
          </div>
          <!-- Máquinas -->
          <div class="flex items-center gap-2 bg-white border border-[#cbd5e1] px-3 py-2 text-sm text-slate-600 shadow-sm">
            <Tractor class="w-4 h-4 text-slate-400" />
            <select class="bg-transparent outline-none cursor-pointer font-medium">
              <option>Todas las máquinas</option>
              <option v-for="m in maquinas" :key="m.id" :value="m.id">{{ m.marca }} ({{ m.identificador }})</option>
            </select>
          </div>
        </div>
      </div>

      <!-- 4 Statistical Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Maquinaria Activa -->
        <div class="bg-white border border-[#cbd5e1] p-6 shadow-sm flex flex-col justify-between hover:border-[#0054A3] transition-all">
          <div class="flex justify-between items-start mb-2">
            <span class="font-display text-[10px] font-black text-slate-500 uppercase tracking-widest">Maquinaria Activa</span>
            <div class="bg-blue-50 p-2 rounded-sm"><Tractor class="text-[#0054A3] w-5 h-5" /></div>
          </div>
          <div class="font-display text-3xl font-black text-slate-800">
            {{ maquinasActivasCount }} <span class="text-sm font-medium text-slate-400">de {{ totalMaquinasCount }}</span>
          </div>
          <div class="text-[10px] font-bold text-emerald-600 mt-2 flex items-center gap-1">
            <ArrowUp class="w-3 h-3" /> 100% operativos
          </div>
        </div>

        <!-- Horas Totales -->
        <div class="bg-white border border-[#cbd5e1] p-6 shadow-sm flex flex-col justify-between hover:border-emerald-600 transition-all">
          <div class="flex justify-between items-start mb-2">
            <span class="font-display text-[10px] font-black text-slate-500 uppercase tracking-widest">Horas Totales</span>
            <div class="bg-emerald-50 p-2 rounded-sm"><Clock class="text-emerald-600 w-5 h-5" /></div>
          </div>
          <div class="font-display text-3xl font-black text-slate-800">
            {{ totalHorasFleet }} <span class="text-sm font-medium text-slate-400">h</span>
          </div>
          <div class="text-[10px] font-bold text-emerald-600 mt-2 flex items-center gap-1">
            Acumulado histórico
          </div>
        </div>

        <!-- Horómetro Promedio -->
        <div class="bg-white border border-[#cbd5e1] p-6 shadow-sm flex flex-col justify-between hover:border-[#FFD200] transition-all">
          <div class="flex justify-between items-start mb-2">
            <span class="font-display text-[10px] font-black text-slate-500 uppercase tracking-widest">Horómetro Promedio</span>
            <div class="bg-amber-50 p-2 rounded-sm"><Activity class="text-amber-500 w-5 h-5" /></div>
          </div>
          <div class="font-display text-3xl font-black text-slate-800">
            {{ promedioHorasFleet }} <span class="text-sm font-medium text-slate-400">h</span>
          </div>
          <div class="text-[10px] font-bold text-slate-500 mt-2 flex items-center gap-1">
            Promedio por unidad
          </div>
        </div>

        <!-- Alertas Activas -->
        <div class="bg-white border border-[#cbd5e1] p-6 shadow-sm flex flex-col justify-between hover:border-red-500 transition-all">
          <div class="flex justify-between items-start mb-2">
            <span class="font-display text-[10px] font-black text-slate-500 uppercase tracking-widest">Alertas Activas</span>
            <div class="bg-red-50 p-2 rounded-sm"><AlertTriangle class="text-red-500 w-5 h-5" /></div>
          </div>
          <div class="font-display text-3xl font-black text-slate-800">
            {{ alertasActivasCount }} <span class="text-sm font-medium text-slate-400">equipos</span>
          </div>
          <div class="text-[10px] font-bold text-red-600 mt-2 flex items-center gap-1 cursor-pointer hover:underline">
            Ver detalles <ArrowRight class="w-3 h-3" />
          </div>
        </div>
      </div>

      <!-- Middle Section: Chart & Map -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Uso de Horómetros Chart -->
        <div class="lg:col-span-5 bg-white border border-[#cbd5e1] p-6 shadow-sm flex flex-col">
          <div class="flex justify-between items-center mb-6">
            <h3 class="font-display text-base font-bold text-slate-800">Uso de Horómetros</h3>
            <select class="bg-white border border-[#cbd5e1] text-[10px] font-bold uppercase tracking-wider px-2 py-1 text-slate-600 shadow-sm outline-none cursor-pointer">
              <option>Esta semana</option>
              <option>Histórico</option>
            </select>
          </div>

          <div class="flex gap-4 mb-4">
            <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase text-slate-500">
              <span class="w-3 h-3 bg-[#0054A3]"></span> Horas Acumuladas
            </div>
          </div>

          <!-- Top-Tier Custom Dynamic Graph Grid -->
          <div class="w-full flex items-end justify-center gap-4 h-56 pb-2 border-b border-[#cbd5e1] relative mt-4">
            <div class="absolute inset-x-0 top-1/4 border-t border-slate-100"></div>
            <div class="absolute inset-x-0 top-2/4 border-t border-slate-100"></div>
            <div class="absolute inset-x-0 top-3/4 border-t border-slate-100"></div>

            <div v-for="item in horasPorTipo" :key="item.tipo" class="flex flex-col items-center justify-end h-full group cursor-pointer z-10 w-16" :title="item.horas + ' h'">
              <div class="w-10 bg-[#cbd5e1] h-full relative rounded-t-sm overflow-hidden">
                <div class="absolute bottom-0 w-full bg-[#0054A3] group-hover:bg-[#004586] transition-all" :style="{ height: item.porcentaje + '%' }"></div>
              </div>
              <span class="font-sans text-[9px] font-bold text-slate-600 mt-2 text-center truncate w-full">{{ item.tipo }}</span>
            </div>
          </div>
        </div>

        <!-- Ubicación de Maquinaria Map -->
        <div class="lg:col-span-7 bg-white border border-[#cbd5e1] shadow-sm flex flex-col relative h-[400px]">
          <div class="absolute top-0 left-0 right-0 p-4 bg-white/90 backdrop-blur-sm z-10 flex justify-between items-center border-b border-[#cbd5e1]">
            <h3 class="font-display text-base font-bold text-slate-800">Ubicación de Maquinaria</h3>
            <span class="text-[10px] font-bold text-[#0054A3] uppercase cursor-pointer hover:underline flex items-center gap-1">Ver mapa completo <ArrowRight class="w-3 h-3" /></span>
          </div>
          <div id="dashboard-map" class="w-full h-full z-0"></div>
        </div>
      </div>

      <!-- Bottom Section: Desempeño por Maquinaria -->
      <div class="bg-white border border-[#cbd5e1] shadow-sm flex flex-col">
        <div class="px-6 py-4 border-b border-[#cbd5e1] flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <h3 class="font-display text-base font-bold text-slate-800">Desempeño por Maquinaria</h3>
          <div class="flex gap-3">
            <div class="relative">
              <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
              <input type="text" placeholder="Buscar máquina..." class="pl-9 pr-4 py-2 text-sm border border-[#cbd5e1] outline-none focus:border-[#0054A3] w-64" />
            </div>
            <button class="flex items-center gap-2 px-4 py-2 border border-[#cbd5e1] text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
              <Filter class="w-4 h-4" /> Filtros
            </button>
            <button class="flex items-center gap-2 px-4 py-2 border border-[#cbd5e1] text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
              <Download class="w-4 h-4" /> Exportar
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 border-b border-[#cbd5e1]">
                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Maquinaria</th>
                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Proyecto</th>
                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Horómetro Inicial</th>
                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Horómetro Final</th>
                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total de Horas</th>
                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">% Trabajo</th>
                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Estado</th>
                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Ubicación</th>
                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
              <tr v-for="row in tableData" :key="row.id" class="hover:bg-slate-50/50 transition-colors group">
                <td class="p-4">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-slate-100 border border-slate-200 flex items-center justify-center rounded-sm overflow-hidden">
                      <Tractor class="w-5 h-5 text-slate-400" />
                    </div>
                    <div>
                      <div class="font-bold text-[#0054A3] uppercase">{{ row.marca }} {{ row.modelo }}</div>
                      <div class="text-[10px] font-mono text-slate-500">{{ row.identificador }}</div>
                    </div>
                  </div>
                </td>
                <td class="p-4 text-slate-600 text-xs font-medium">Sin Proyecto Asignado</td>
                <td class="p-4 font-mono font-bold text-slate-600">{{ row.h_inicial }}</td>
                <td class="p-4 font-mono font-bold text-slate-800">{{ row.h_final }}</td>
                <td class="p-4 font-mono font-bold text-[#0054A3] bg-blue-50/50 border-l border-r border-blue-100 text-center">{{ row.total_trabajado }} h</td>
                
                <td class="p-4">
                  <div class="flex items-center gap-2">
                    <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                      <div class="bg-[#0054A3] h-full" :style="{ width: row.porcentaje_trabajo + '%' }"></div>
                    </div>
                    <span class="text-xs font-bold text-slate-700 w-8">{{ row.porcentaje_trabajo }}%</span>
                  </div>
                </td>
                
                <td class="p-4">
                  <span class="px-2 py-1 text-[9px] font-bold uppercase rounded-sm border" 
                    :class="row.estado === 'Operativo' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'">
                    {{ row.estado }}
                  </span>
                </td>
                
                <td class="p-4">
                  <div class="flex items-center gap-1.5 text-xs text-slate-600">
                    <MapPin class="w-3.5 h-3.5 text-[#0054A3]" v-if="row.latitud" />
                    <span v-if="row.latitud" class="font-mono text-[10px]">{{ row.latitud }}, {{ row.longitud }}</span>
                    <span v-else class="text-slate-400 italic">Sin ubicación</span>
                  </div>
                </td>
                
                <td class="p-4 text-center">
                  <button class="p-1.5 text-slate-400 hover:text-[#0054A3] transition-colors" title="Ver Detalles">
                    <MoreHorizontal class="w-4 h-4" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div class="px-6 py-4 border-t border-[#cbd5e1] flex justify-between items-center text-xs text-slate-500">
          <span>Mostrando 1 a {{ tableData.length }} de {{ tableData.length }} máquinas</span>
          <div class="flex gap-1">
            <button class="w-8 h-8 flex items-center justify-center border border-[#cbd5e1] bg-white text-slate-400 cursor-not-allowed"><ChevronLeft class="w-4 h-4" /></button>
            <button class="w-8 h-8 flex items-center justify-center border border-[#0054A3] bg-[#0054A3] text-white font-bold">1</button>
            <button class="w-8 h-8 flex items-center justify-center border border-[#cbd5e1] bg-white text-slate-400 cursor-not-allowed"><ChevronRight class="w-4 h-4" /></button>
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
  Calendar, Search, Filter, Download, MapPin, MoreHorizontal, ChevronLeft, ChevronRight
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
const alertasActivasCount = computed(() => props.maquinas?.filter(m => m.estado === 'Mantenimiento' || m.estado === 'Fuera de Servicio').length || 0);

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
      // If only final exists, we don't know the exact initial of today unless we check yesterday, 
      // but strictly we just show 0 or undefined. Let's just show 0 for total worked if no range.
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
  
  mapInstance = L.map('dashboard-map', { zoomControl: false }).setView([14.6349, -90.5069], 7); // Default Guatemala center
  
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
    
    // Create a custom icon based on the status
    const isOperativo = m.estado === 'Operativo';
    const color = isOperativo ? '#059669' : '#dc2626'; // emerald-600 vs red-600
    
    const svgIcon = L.divIcon({
      className: 'custom-pin',
      html: `<div style="background-color: ${color}; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center;"></div>`,
      iconSize: [20, 20],
      iconAnchor: [10, 10]
    });

    const marker = L.marker([lat, lng], { icon: svgIcon })
      .bindPopup(`<strong>${m.marca} ${m.modelo}</strong><br>Estado: ${m.estado}<br>Horas Totales: ${m.horas_acumuladas}`)
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
  }, 300); // Give time for DOM and transition
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
