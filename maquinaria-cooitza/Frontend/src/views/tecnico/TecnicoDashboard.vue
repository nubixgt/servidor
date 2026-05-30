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

        <!-- Top Search and Export widgets -->
        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
          <!-- Real-time search inside the view -->
          <div class="relative flex-1 lg:flex-initial min-w-[240px]">
            <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
            <input 
              type="text"
              placeholder="Buscar registros específicos..."
              v-model="searchTerm"
              class="w-full pl-9 pr-3 py-2 border border-slate-200 outline-none bg-white text-xs focus:border-[#0054A3] transition-colors focus:ring-0"
            />
          </div>

          <button 
            type="button"
            @click="handleExportData"
            style="cursor: pointer"
            class="flex items-center gap-1.5 px-4 py-2 border border-slate-200 bg-white hover:bg-slate-50 transition-colors font-display text-[10px] font-bold uppercase tracking-wider"
          >
            <Download :size="14" class="text-[#0054A3]" />
            <span>EXPORTAR JSON</span>
          </button>
        </div>
      </div>

      <!-- Bento Grid Layout (Row 1: Summary Statistics) -->
      <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
        
        <!-- Stat Box 1: Total Events -->
        <div class="col-span-12 md:col-span-3 bg-white border border-slate-200 p-6 flex flex-col justify-between shadow-sm relative overflow-hidden group">
          <div class="absolute right-0 top-0 p-4 opacity-5 translate-x-3 -translate-y-3">
            <Activity :size="80" />
          </div>
          <div>
            <span class="font-display text-[10px] font-bold text-slate-500 uppercase tracking-widest block mb-4">
              EVENTOS TOTALES
            </span>
            <span class="font-display text-4xl font-extrabold text-[#191c1d]">
              {{ totalSystemEventsCount.toLocaleString() }}
            </span>
          </div>
          <div class="mt-6 pt-4 border-t border-slate-100 flex justify-between items-center text-xs">
            <span class="text-[#524534]">Últimas 24h</span>
            <span class="font-display font-semibold text-[#0054A3]">+{{ filteredEvents.length * 4 }} activos</span>
          </div>
        </div>

        <!-- Stat Box 2: Critical alerts -->
        <div class="col-span-12 md:col-span-3 bg-white border border-slate-200 p-6 flex flex-col justify-between shadow-sm relative overflow-hidden">
          <div>
            <span class="font-display text-[10px] font-bold text-slate-500 uppercase tracking-widest block mb-4">
              CRÍTICOS
            </span>
            <span class="font-display text-4xl font-extrabold text-red-600">
              {{ criticalEventsCount < 10 ? `0${criticalEventsCount}` : criticalEventsCount }}
            </span>
          </div>
          <div class="mt-6 pt-4 border-t border-slate-100 flex justify-between items-center text-xs">
            <span class="text-[#524534]">Estado de alertas</span>
            <span class="font-display italic text-slate-500 font-bold">Monitoreado en vivo</span>
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

      <!-- Main Table Panel Log detailed views -->
      <div class="bg-white border border-slate-200 overflow-hidden shadow-sm">
        
        <!-- Table header control row -->
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex flex-wrap justify-between items-center gap-4">
          <div class="flex items-center gap-2">
            <span class="font-display text-[10px] font-bold text-[#524534] uppercase tracking-wider">
              REGISTRO DETALLADO DE EVENTOS
            </span>
          </div>

          <!-- Active Level Color Filters -->
          <div class="flex flex-wrap items-center gap-3">
            <!-- All -->
            <button 
              type="button"
              @click="filterLevel = 'ALL'; currentPage = 1"
              class="px-2.5 py-1 text-[10px] font-bold font-display transition-all"
              :class="filterLevel === 'ALL' ? 'bg-[#0054A3] text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'"
            >
              TODOS
            </button>
            
            <!-- Info Filter -->
            <button 
              type="button"
              @click="filterLevel = 'INFO'; currentPage = 1"
              class="flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold font-display transition-all"
              :class="filterLevel === 'INFO' ? 'bg-emerald-600 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
              INFO
            </button>

            <!-- Warning Filter -->
            <button 
              type="button"
              @click="filterLevel = 'WARN'; currentPage = 1"
              class="flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold font-display transition-all"
              :class="filterLevel === 'WARN' ? 'bg-amber-500 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-amber-400 inline-block"></span>
              WARN
            </button>

            <!-- Error Filter -->
            <button 
              type="button"
              @click="filterLevel = 'ERROR'; currentPage = 1"
              class="flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold font-display transition-all"
              :class="filterLevel === 'ERROR' ? 'bg-red-600 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-red-600 inline-block"></span>
              ERROR
            </button>
          </div>
        </div>

        <!-- Responsive Table Grid -->
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-200 bg-slate-50/50">
                <th class="p-4 font-display text-[10px] font-bold text-[#524534] uppercase tracking-wider">ID EVENTO</th>
                <th class="p-4 font-display text-[10px] font-bold text-[#524534] uppercase tracking-wider">TIMESTAMP</th>
                <th class="p-4 font-display text-[10px] font-bold text-[#524534] uppercase tracking-wider">CATEGORÍA</th>
                <th class="p-4 font-display text-[10px] font-bold text-[#524534] uppercase tracking-wider">MENSAJE DEL SISTEMA</th>
                <th class="p-4 font-display text-[10px] font-bold text-[#524534] uppercase tracking-wider text-center">NIVEL</th>
                <th class="p-4 font-display text-[10px] font-bold text-[#524534] uppercase tracking-wider">OPERADOR</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="paginatedEvents.length === 0">
                <td colspan="6" class="p-8 text-center text-slate-400 italic font-sans text-xs">
                  No se encontraron registros activos con los filtros indicados.
                </td>
              </tr>
              <tr 
                v-else
                v-for="event in paginatedEvents" 
                :key="event.id" 
                class="hover:bg-slate-50 transition-colors group cursor-pointer border-l-2 border-transparent hover:border-[#0054A3]"
              >
                <td class="p-4 font-mono text-xs font-bold text-slate-700">
                  {{ event.id }}
                </td>
                <td class="p-4 font-mono text-xs text-slate-500 whitespace-nowrap">
                  {{ event.timestamp }}
                </td>
                <td class="p-4">
                  <span class="bg-slate-100 inline-block px-2.5 py-0.5 rounded text-[10px] font-display font-medium text-slate-700">
                    {{ event.category }}
                  </span>
                </td>
                <td class="p-4 text-xs font-sans text-slate-700">
                  {{ event.message }}
                </td>
                <td class="p-4 text-center">
                  <span class="w-2.5 h-2.5 rounded-full inline-block"
                        :class="event.level === 'INFO' ? 'bg-emerald-500 shadow-sm' :
                               event.level === 'WARN' ? 'bg-amber-400 shadow-sm' : 
                               'bg-red-500 shadow-sm animate-pulse'" 
                        :title="event.level"></span>
                </td>
                <td class="p-4 font-mono text-xs text-slate-600 font-medium">
                  {{ event.operator }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Footer of log table including Pagination -->
        <div class="px-6 py-4 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50/50">
          <span class="text-xs font-sans text-[#524534]">
            Mostrando {{ paginatedEvents.length }} de {{ filteredEvents.length }} entradas ({{ totalSystemEventsCount.toLocaleString() }} totales en sistema)
          </span>

          <div class="flex items-center gap-1">
            <button 
              type="button"
              :disabled="currentPage === 1"
              @click="currentPage = Math.max(1, currentPage - 1)"
              class="w-8 h-8 flex items-center justify-center border border-slate-200 disabled:opacity-45 bg-white hover:bg-slate-50 transition-colors text-slate-500"
            >
              <ChevronLeft :size="16" />
            </button>

            <button
              v-for="pNum in totalPages"
              :key="pNum"
              type="button"
              @click="currentPage = pNum"
              class="w-8 h-8 flex items-center justify-center text-xs font-mono font-bold transition-all"
              :class="currentPage === pNum ? 'bg-[#0054A3] text-white border border-[#0054A3]' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'"
            >
              {{ pNum }}
            </button>

            <button 
              type="button"
              :disabled="currentPage === totalPages"
              @click="currentPage = Math.min(totalPages, currentPage + 1)"
              class="w-8 h-8 flex items-center justify-center border border-slate-200 disabled:opacity-45 bg-white hover:bg-slate-50 transition-colors text-slate-500"
            >
              <ChevronRight :size="16" />
            </button>
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

interface SystemEvent {
  id: string;
  timestamp: string;
  category: string; // "Telemetría" | "Acceso" | "Mantenimiento" | "Crítico"
  message: string;
  level: "INFO" | "WARN" | "ERROR";
  operator: string;
}

interface AssetDetails {
  id: string;
  name: string;
  category: string;
  imageUrl: string;
  lastInspection: string;
  note: string;
}

const searchTerm = ref("");
const filterLevel = ref<"ALL" | "INFO" | "WARN" | "ERROR">("ALL");
const filterCategory = ref("todos");
const currentPage = ref(1);
const itemsPerPage = 5;

const selectedAssetId = ref("asset-1");
const systemEvents = ref<SystemEvent[]>([]);
const totalSystemEventsCount = ref(4281);

const cpuLoad = ref(24);
const freeMemory = ref(82);
const latency = ref(4);

const assetsList = ref<AssetDetails[]>([
  {
    id: "asset-1",
    name: "TURBINA T-04 (PRINCIPAL)",
    category: "Generador de Fuerza",
    imageUrl: "https://lh3.googleusercontent.com/aida-public/AB6AXuAS9Jhi3aHz6hvNHkZbsh4wSd8jUg4j7ENX9-hTRqUo_iSI3bYWUZtb26N9Y3Qhzoqkg6DNp0zDe4XgWc0RBeEgb6Teccq_HtPHYiAbhl16rD3LHMDTENTs4BxJR-bSQAufpm2osCcCCNWL9XHrIrcVTb1y2FMGekiztxtlRL7T0Xm3O_dgYi9If8_3rCPCgL468tvInm-FJs6nxjRB_g4wwbpYEt-mVyZPAvrWQ459IoIFHuVxkWQM4MAJ8ltaj4qUU9uTbzjuTdfO",
    lastInspection: "Mayo 20, 2024 - Operador Técnico J. Doe",
    note: "Rendimiento nominal estable. Se recomienda seguimiento de vibración en el próximo ciclo."
  },
  {
    id: "asset-2",
    name: "EXCAVADORA CAT 320-B",
    category: "Movimiento de Tierras",
    imageUrl: "https://lh3.googleusercontent.com/aida-public/AB6AXuCW4-n0Uts09omCrK9hJAsromc1Y7GCJOZdeCvW2-u5sfFJXqom9XcKgbiq51rcx0mQNuEyHdpIGs8r9yViHSIpGwQ-Z7-RPkZ35ItK-6bQfr3kdlj5PT9e5KXQB3gtC7eSS279VcUjQS7-RNR9MPbwf5ypPuTg4CgEMIhyaVTzA00eWFBzQ74Pu3JtOSHdgJcFGtuDXY8l6dlcOrHUitHLowY1QP3UIFOg92Wkg41214T-JmcBsgoF8wyXoCRHv3C6SVyFE96IGl5b",
    lastInspection: "Mayo 24, 2024 - Inspector Miguel Fuentes",
    note: "Presión hidráulica en rangos estándar de operación. Pérdida menor detectada en manguera de retorno, ya resuelta."
  },
  {
    id: "asset-3",
    name: "TRACTOR JOHN DEERE 8R",
    category: "Preparación de Campo",
    imageUrl: "https://lh3.googleusercontent.com/aida-public/AB6AXuCtWEyh1Z-AVKb8m7r1Xd4QhYcVyxqNgGs7-QDVKHd0JvWlxT5MCJ0EPmyeydptGOjpmTw3CVlGGHGm53HGi_fza4tXXmiVp3tTR6S2n7gc02D3GN7Ko5Lc8Gv-BkHjm2F9kcmNC5ezQd7YofIuYhuYnHs-50gaNnQv7Livvi7M1RvyouOyT0-aegn6hvLevJh28ZSBMI76QCDIx27OkhuzjNPbxMQu8-cl0ANrBMiXuPsIX7-OsUgTo7TgPkIZQCwhWHSIMCSQg7PB",
    lastInspection: "Mayo 18, 2024 - Operador R. Andersson",
    note: "Calibración del GPS de piloto automático certificada. Filtros de transmisión reemplazados."
  }
]);

const activeAsset = computed(() => assetsList.value.find(a => a.id === selectedAssetId.value) || assetsList.value[0]);

watch(searchTerm, () => {
  currentPage.value = 1;
});

const filteredEvents = computed(() => {
  return systemEvents.value.filter(event => {
    const matchesSearch = 
      event.id.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
      event.message.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
      event.operator.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
      event.category.toLowerCase().includes(searchTerm.value.toLowerCase());

    const matchesLevel = filterLevel.value === "ALL" || event.level === filterLevel.value;
    const matchesCategory = filterCategory.value === "todos" || event.category.toLowerCase() === filterCategory.value.toLowerCase();

    return matchesSearch && matchesLevel && matchesCategory;
  });
});

const totalPages = computed(() => Math.ceil(filteredEvents.value.length / itemsPerPage) || 1);

const paginatedEvents = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredEvents.value.slice(start, start + itemsPerPage);
});

const criticalEventsCount = computed(() => systemEvents.value.filter(e => e.level === "ERROR").length);

let intervalTimer: number | null = null;

onMounted(() => {
  intervalTimer = window.setInterval(() => {
    cpuLoad.value = Math.max(12, Math.min(68, cpuLoad.value + Math.floor(Math.random() * 7) - 3));
    latency.value = Math.max(2, Math.min(15, latency.value + Math.floor(Math.random() * 3) - 1));
  }, 5000);

  const savedLogs = localStorage.getItem("cooitza_machinery_logs");
  let mappedOperatorEvents: SystemEvent[] = [];
  if (savedLogs) {
    try {
      const parsed = JSON.parse(savedLogs);
      mappedOperatorEvents = parsed.map((log: any, index: number) => {
        const isCritical = log.horometroValue > 9000;
        const isWarning = log.regType === "final";
        return {
          id: `#LOG-${(log.id?.substring(4, 10).toUpperCase()) || (8800 + index)}`,
          timestamp: log.dateTime || new Date().toLocaleString("es-GT"),
          category: "Telemetría",
          message: `Reporte de horómetro ${log.regType === "inicial" ? "Inicial" : "Final"} para ${log.machineType?.toUpperCase()}: ${log.horometroValue.toLocaleString()} HRS en ${log.location?.formattedAddress}`,
          level: isCritical ? "ERROR" : isWarning ? "WARN" : "INFO",
          operator: log.operatorName?.split(" ")[0] || "OPERADOR"
        };
      });
    } catch (err) {
      console.error("Error loading technician dashboard logs", err);
    }
  }

  const defaultStaticEvents: SystemEvent[] = [
    {
      id: "#LOG-8821",
      timestamp: "2024-05-24 14:22:10",
      category: "Telemetría",
      message: "Calibración de sensor térmico completada con éxito.",
      level: "INFO",
      operator: "SYS_AUTO"
    },
    {
      id: "#LOG-8820",
      timestamp: "2024-05-24 14:15:02",
      category: "Acceso",
      message: "Inicio de sesión detectado desde Terminal 04.",
      level: "INFO",
      operator: "R. Sanchez"
    },
    {
      id: "#LOG-8819",
      timestamp: "2024-05-24 13:58:45",
      category: "Mantenimiento",
      message: "Ciclo de lubricación preventiva programado para 16:00.",
      level: "WARN",
      operator: "SCHEDULER"
    },
    {
      id: "#LOG-8818",
      timestamp: "2024-05-24 13:40:12",
      category: "Crítico",
      message: "Fluctuación de voltaje fuera de rango en Nodo-B.",
      level: "ERROR",
      operator: "MONITOR_01"
    },
    {
      id: "#LOG-8817",
      timestamp: "2024-05-24 13:12:33",
      category: "Telemetría",
      message: "Lectura de presión hidráulica estable a 450 PSI.",
      level: "INFO",
      operator: "SYS_AUTO"
    },
    {
      id: "#LOG-8816",
      timestamp: "2022-05-24 12:45:10",
      category: "Mantenimiento",
      message: "Revisión preventiva de motor térmico realizada.",
      level: "INFO",
      operator: "M. Thorne"
    },
    {
      id: "#LOG-8815",
      timestamp: "2024-05-24 11:32:00",
      category: "Telemetría",
      message: "Nivel de combustible de tanque auxiliar de reserva verificado.",
      level: "INFO",
      operator: "E. Rodriguez"
    }
  ];

  systemEvents.value = [...mappedOperatorEvents, ...defaultStaticEvents];
  totalSystemEventsCount.value = 4281 + mappedOperatorEvents.length;
});

onUnmounted(() => {
  if (intervalTimer !== null) {
    clearInterval(intervalTimer);
  }
});

const handleExportData = () => {
  const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(filteredEvents.value, null, 2));
  const downloadAnchor = document.createElement("a");
  downloadAnchor.setAttribute("href", dataStr);
  downloadAnchor.setAttribute("download", `cooitza_industrial_logs_${new Date().toISOString().substring(0, 10)}.json`);
  document.body.appendChild(downloadAnchor);
  downloadAnchor.click();
  downloadAnchor.remove();
};
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
