<template>
  <transition name="fade" appear>
    <div class="w-full max-w-[1280px] mx-auto px-6 py-8 flex flex-col gap-8 text-[#191c1d]">
      
      <!-- Dynamic Header Row WITHOUT standard platform navbar -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 border border-slate-200">
        <div class="flex items-center gap-3">
          <!-- Logo element representing Cooitzá Industrial Core -->
          <div class="w-12 h-12 bg-[#0054A3] flex items-center justify-center font-display font-black text-xl text-white italic">
            C
          </div>
          <div>
            <h2 class="font-display text-xl font-bold tracking-tight text-[#0054A3]">Cooitzá IndustrialMS</h2>
            <p class="text-[10px] uppercase font-mono-label font-bold text-slate-500 tracking-wider">
              Estación de Telemetría v4.2.1 • Vista Exclusiva de Técnico
            </p>
          </div>
        </div>

        <!-- User Badge & Logout Option -->
        <div class="flex items-center gap-4 self-stretch md:self-auto justify-between md:justify-end border-t md:border-t-0 pt-4 md:pt-0 border-slate-100">
          <div class="flex items-center gap-2">
            <div class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse"></div>
            <span class="font-sans text-xs font-semibold text-slate-700">Técnico Analista</span>
          </div>
          
          <button
            type="button"
            @click="handleLogout"
            class="flex items-center gap-1.5 px-3.5 py-1.5 border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 font-display text-[10px] font-bold uppercase tracking-wider transition-colors cursor-pointer"
          >
            <LogOut :size="12" />
            <span>Cerrar Sesión</span>
          </button>
        </div>
      </div>

      <!-- Main Title Section -->
      <div class="flex flex-col lg:flex-row lg:justify-between lg:items-end gap-6">
        <div>
          <div class="flex items-center gap-1.5 mb-2">
            <span class="w-2 h-2 rounded-full bg-[#f5a623] animate-pulse"></span>
            <span class="font-display text-[10px] font-bold text-slate-500 uppercase tracking-widest">
              SISTEMA OPERATIVO • MODO LECTURA
            </span>
          </div>
          <h1 class="font-display text-4xl font-extrabold text-[#191c1d] tracking-tight">
            Historial de Registros
          </h1>
          <p class="text-slate-500 font-sans text-sm max-w-2xl mt-1.5 leading-relaxed">
            Supervisión técnica de eventos del sistema, telemetría y logs de mantenimiento para la Estación 04-B de la división de Maquinaria Pesada Cooitzá.
          </p>
        </div>
      </div>

      <!-- Bento Grid Layout (Row 1: Summary Statistics) -->
      <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
        
        <!-- Stat Box 1: Total Maquinaria -->
        <div class="col-span-12 md:col-span-3 bg-white border border-slate-200 p-6 flex flex-col justify-between shadow-sm relative overflow-hidden group">
          <div class="absolute right-0 top-0 p-4 opacity-5 translate-x-3 -translate-y-3">
            <Construction :size="80" />
          </div>
          <div>
            <span class="font-display text-[10px] font-bold text-slate-500 uppercase tracking-widest block mb-4">
              MAQUINARIA REGISTRADA
            </span>
            <span class="font-display text-4xl font-extrabold text-[#191c1d]">
              {{ machineryCount }}
            </span>
          </div>
          <div class="mt-6 pt-4 border-t border-slate-100 flex justify-between items-center text-xs">
            <span class="text-[#524534]">Inventario Total</span>
            <span class="font-display font-semibold text-[#0054A3]">Cooitzá R.L.</span>
          </div>
        </div>

        <!-- Stat Box 2: Total Vehiculos -->
        <div class="col-span-12 md:col-span-3 bg-white border border-slate-200 p-6 flex flex-col justify-between shadow-sm relative overflow-hidden">
          <div class="absolute right-0 top-0 p-4 opacity-5 translate-x-3 -translate-y-3">
            <Truck :size="80" />
          </div>
          <div>
            <span class="font-display text-[10px] font-bold text-slate-500 uppercase tracking-widest block mb-4">
              VEHÍCULOS DE FLOTA
            </span>
            <span class="font-display text-4xl font-extrabold text-[#0054A3]">
              {{ vehiclesCount }}
            </span>
          </div>
          <div class="mt-6 pt-4 border-t border-slate-100 flex justify-between items-center text-xs">
            <span class="text-[#524534]">Transporte</span>
            <span class="font-display italic text-slate-500 font-bold">Activos</span>
          </div>
        </div>

        <!-- Stat Box 3: Bar Distribution Chart of Activity -->
        <div class="col-span-12 md:col-span-6 bg-white border border-slate-200 p-6 shadow-sm flex flex-col justify-between">
          <div class="flex justify-between items-center mb-4">
            <span class="font-display text-[10px] font-bold text-slate-500 uppercase tracking-widest">
              DISTRIBUCIÓN DE ACTIVIDAD DE MAQUINARIA
            </span>
            <span class="text-[10px] font-mono bg-[#0054A3]/10 text-[#0054A3] px-2 py-0.5 font-bold">
              ESTADO SATISFACTORIO
            </span>
          </div>

          <!-- Simple premium dynamic css grid bar chart representation -->
          <div class="h-24 flex items-end gap-1.5 pt-2">
            <div 
              v-for="(h, i) in [40, 60, 80, 50, 95, 45, 70, 85, 30, 55, 75, 100, 60, 40]" 
              :key="i" 
              class="bg-[#0054A3]/25 group-hover:bg-[#f5a623] hover:bg-[#0054A3] transition-all duration-300 w-full relative group"
              :style="{ height: `${h}%` }"
              :title="`Hora ${12-i}h atrás: ${h}% carga`"
            >
              <!-- Micro tooltip -->
              <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block bg-black text-white text-[8px] p-1 rounded whitespace-nowrap z-10">
                {{ h }}%
              </div>
            </div>
          </div>

          <div class="flex justify-between items-center mt-3 text-[10px] text-slate-400 font-mono">
            <span>HACE 12 HORAS</span>
            <span>TIEMPO REAL</span>
          </div>
        </div>

      </div>

      <!-- Row 3: Hardware Health & Asset snapshot (Side-by-Side widgets) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Hardware Status Widget -->
        <div class="col-span-12 lg:col-span-4 bg-white border border-slate-200 p-6 shadow-sm flex flex-col justify-between">
          <div>
            <span class="font-display text-[10px] font-bold text-slate-500 uppercase tracking-widest block mb-5">
              ESTADO DE HARDWARE PRINCIPAL
            </span>
            <div class="divide-y divide-slate-100">
              
              <!-- Proc monitor -->
              <div class="flex justify-between items-center py-3">
                <span class="font-sans text-xs text-slate-700 flex items-center gap-2">
                  <Cpu :size="14" class="text-[#0054A3]" />
                  Procesador Central
                </span>
                <span class="font-mono text-xs font-bold text-emerald-600">
                  {{ cpuLoad }}% (OPTIMAL)
                </span>
              </div>

              <!-- Buffer memory -->
              <div class="flex justify-between items-center py-3">
                <span class="font-sans text-xs text-slate-700 flex items-center gap-2">
                  <Layers :size="14" class="text-[#0054A3]" />
                  Memoria del Buffer
                </span>
                <span class="font-mono text-xs font-bold text-emerald-600">
                  {{ freeMemory }}% LIBRE
                </span>
              </div>

              <!-- Server Latency -->
              <div class="flex justify-between items-center py-3">
                <span class="font-sans text-xs text-slate-700 flex items-center gap-2">
                  <Activity :size="14" class="text-[#0054A3]" />
                  Conectividad Uplink
                </span>
                <span class="font-mono text-xs font-bold text-[#0054A3]">
                  LATENCIA {{ latency }}ms
                </span>
              </div>

              <!-- Node-B Sensors -->
              <div class="flex justify-between items-center py-3">
                <span class="font-sans text-xs text-slate-700 flex items-center gap-2">
                  <AlertTriangle :size="14" class="text-red-500" />
                  Sensores Nodo-B
                </span>
                <span class="font-mono text-xs font-bold text-red-600 animate-pulse">
                  REVISIÓN REQ.
                </span>
              </div>

            </div>
          </div>
          <div class="mt-6 pt-4 border-t border-slate-100">
            <p class="text-[9px] font-mono text-slate-400 uppercase tracking-wider text-center">
              Frecuencia de telemetría de bus: 1 Hz
            </p>
          </div>
        </div>

        <!-- Dynamic Asset Preview Widget -->
        <div class="col-span-12 lg:col-span-8 bg-white border border-slate-200 p-6 shadow-sm flex flex-col">
          <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-4">
            <span class="font-display text-[10px] font-bold text-slate-500 uppercase tracking-widest block">
              VISTA PREVIA DE ASSET OPERATIVO
            </span>

            <!-- Selector list for assets -->
            <div class="flex gap-1 bg-slate-100 p-0.5 rounded">
              <button
                v-for="(a, idx) in assetsList"
                :key="a.id"
                type="button"
                @click="selectedAssetId = a.id"
                class="px-3 py-1 text-[9px] font-bold font-display uppercase tracking-widest transition-all"
                :class="selectedAssetId === a.id ? 'bg-white text-[#0054A3] shadow-sm' : 'text-slate-500 hover:text-slate-800'"
              >
                ACTIVO {{ idx + 1 }}
              </button>
            </div>
          </div>

          <!-- Active selection info layout -->
          <div class="flex flex-col sm:flex-row gap-6 mt-2 flex-grow">
            <div class="w-full sm:w-1/3 bg-slate-100 aspect-square rounded overflow-hidden shadow-inner border border-slate-100 shrink-0">
              <img 
                :src="activeAsset?.imageUrl" 
                :alt="activeAsset?.name" 
                referrerpolicy="no-referrer"
                class="w-full h-full object-cover grayscale opacity-80 hover:grayscale-0 hover:opacity-100 transition-all duration-500" 
              />
            </div>
            
            <div class="w-full sm:w-2/3 flex flex-col gap-3 justify-center">
              <div class="bg-slate-50 p-4 border-l-4 border-[#0054A3]">
                <span class="font-display text-[8px] font-black text-[#0054A3] uppercase tracking-widest block mb-1">
                  IDENTIFICADOR DE ASSET ACTIVO
                </span>
                <span class="font-display text-sm font-bold text-slate-700">
                  {{ activeAsset?.name }} ({{ activeAsset?.category }})
                </span>
              </div>

              <div class="bg-slate-50 p-4 border-l-4 border-amber-300">
                <span class="font-display text-[8px] font-black text-slate-600 uppercase tracking-widest block mb-1">
                  ÚLTIMA INSPECCIÓN CERTIFICADA El {{ new Date().toLocaleDateString("es-GT") }}
                </span>
                <p class="font-sans text-xs text-slate-700 font-medium">
                  {{ activeAsset?.lastInspection }}
                </p>
              </div>

              <div class="bg-slate-50 p-4 border-l-4 border-slate-300 flex-grow">
                <span class="font-display text-[8px] font-black text-slate-600 uppercase tracking-widest block mb-1">
                  NOTA ADJUNTA DE BITÁCORA
                </span>
                <p class="font-sans text-xs text-slate-600 italic">
                  "{{ activeAsset?.note }}"
                </p>
              </div>
            </div>
          </div>

        </div>

      </div>

      <!-- Styled Footer for verification -->
      <footer class="w-full mt-4 bg-white border border-slate-200">
        <div class="flex flex-col sm:flex-row justify-between items-center p-6 gap-4">
          <span class="font-display text-[9px] font-bold text-slate-400 uppercase tracking-widest text-center sm:text-left">
            © 2026 COOITZÁ R.L. CLINICAL EFFICIENCY PROTOCOL. SISTEMAS CENTRALIZADOS.
          </span>
          <div class="flex gap-4">
            <span class="flex items-center gap-1.5 font-display text-[9px] font-bold text-slate-500 uppercase tracking-wider">
              <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
              SISTEMA INTEGRADO ONLINE
            </span>
          </div>
        </div>
      </footer>

    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { 
  Filter, Download, Search, LogOut, ChevronLeft, ChevronRight, Cpu, 
  Activity, Clock, AlertTriangle, CheckCircle, Info, Layers, Wrench, 
  Construction, Compass
} from "lucide-vue-next";

import { useRouter } from 'vue-router';

const router = useRouter();

const handleLogout = () => {
  router.push('/login');
};

interface AssetDetails {
  id: string;
  name: string;
  category: string;
  imageUrl: string;
  lastInspection: string;
  note: string;
}

const selectedAssetId = ref<string | null>(null);
const machineryCount = ref(0);
const vehiclesCount = ref(0);

const cpuLoad = ref(24);
const freeMemory = ref(82);
const latency = ref(4);

const assetsList = ref<AssetDetails[]>([]);

const activeAsset = computed(() => assetsList.value.find(a => a.id === selectedAssetId.value) || assetsList.value[0]);

let intervalTimer: number | null = null;

const fetchDashboardData = async () => {
  try {
    // Fetch Maquinaria
    const mRes = await fetch('/maquinaria-cooitza/Backend/api/v1/maquinas');
    const mData = await mRes.json();
    if (mData.status === 'success') {
      const maquinas = mData.data;
      machineryCount.value = maquinas.length;
      if (maquinas.length > 0) {
        assetsList.value = maquinas.map((m: any, idx: number) => ({
          id: `asset-${m.id}`,
          name: `${m.marca} - ${m.identificador || m.modelo}`,
          category: m.tipo,
          imageUrl: m.foto_url || "https://lh3.googleusercontent.com/aida-public/AB6AXuAS9Jhi3aHz6hvNHkZbsh4wSd8jUg4j7ENX9-hTRqUo_iSI3bYWUZtb26N9Y3Qhzoqkg6DNp0zDe4XgWc0RBeEgb6Teccq_HtPHYiAbhl16rD3LHMDTENTs4BxJR-bSQAufpm2osCcCCNWL9XHrIrcVTb1y2FMGekiztxtlRL7T0Xm3O_dgYi9If8_3rCPCgL468tvInm-FJs6nxjRB_g4wwbpYEt-mVyZPAvrWQ459IoIFHuVxkWQM4MAJ8ltaj4qUU9uTbzjuTdfO",
          lastInspection: `Horas Acumuladas: ${m.horas_acumuladas || 0}`,
          note: `Próximo servicio: ${m.proximo_servicio || 'No definido'} - Estado: ${m.estado}`
        }));
        selectedAssetId.value = assetsList.value[0].id;
      }
    }

    // Fetch Vehiculos
    const vRes = await fetch('/maquinaria-cooitza/Backend/api/v1/vehiculos');
    const vData = await vRes.json();
    if (vData.status === 'success') {
      vehiclesCount.value = vData.data.length;
    }
  } catch (error) {
    console.error("Error fetching technician dashboard data:", error);
  }
};

onMounted(() => {
  intervalTimer = window.setInterval(() => {
    cpuLoad.value = Math.max(12, Math.min(68, cpuLoad.value + Math.floor(Math.random() * 7) - 3));
    latency.value = Math.max(2, Math.min(15, latency.value + Math.floor(Math.random() * 3) - 1));
  }, 5000);

  fetchDashboardData();
});

onUnmounted(() => {
  if (intervalTimer !== null) {
    clearInterval(intervalTimer);
  }
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.4s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
