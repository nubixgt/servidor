<template>
  <transition name="fade-up" appear>
    <div class="flex flex-col gap-6">
      <!-- Command Center Title -->
      <div class="mb-2 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <span class="font-display text-[10px] font-black text-[#0054A3] tracking-widest uppercase">RESUMEN GENERAL</span>
          <h2 class="font-display text-3xl font-black text-slate-800 tracking-tight mt-0.5">Control de Telemetría</h2>
        </div>
        <button 
          type="button"
          @click="$emit('addMockLog')"
          class="font-display text-[11px] font-black bg-[#FFD200] text-[#0054A3] hover:brightness-95 transition-all px-4 py-2 self-start sm:self-center uppercase tracking-wide border-2 border-transparent hover:border-[#0054A3]/20 cursor-pointer"
        >
          + SIMULAR TELEMETRÍA DE OBRA
        </button>
      </div>

      <!-- Bento Quick statistics Row -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Pilots Count -->
        <div class="bg-white border border-[#cbd5e1] p-6 shadow-sm hover:border-[#0054A3] transition-all">
          <div class="flex justify-between items-start mb-4">
            <Users class="text-[#0054A3] w-6 h-6" />
            <span class="font-display text-[10px] font-black text-[#0054A3] bg-[#0054A3]/5 px-2 py-0.5 rounded">PILOTOS</span>
          </div>
          <div class="font-display text-4xl font-extrabold text-[#0054A3]">{{ pilotsCount }}</div>
          <p class="font-sans text-xs text-slate-600 font-medium mt-1">
            {{ pilotsActiveCount }} En Turno Activo • {{ pilotsRestingCount }} En Descanso
          </p>
        </div>

        <!-- Vehicles Flota Count -->
        <div class="bg-white border border-[#cbd5e1] p-6 shadow-sm hover:border-[#0054A3] transition-all">
          <div class="flex justify-between items-start mb-4">
            <Truck class="text-[#0054A3] w-6 h-6" />
            <span class="font-display text-[10px] font-black text-[#0054A3] bg-[#0054A3]/5 px-2 py-0.5 rounded">VEHÍCULOS</span>
          </div>
          <div class="font-display text-4xl font-extrabold text-[#0054A3]">{{ vehiclesCount }}</div>
          <p class="font-sans text-xs text-slate-600 font-medium mt-1">
            {{ vehiclesActiveCount }} Rutas Asignadas / {{ vehiclesMaintenanceCount }} En Taller
          </p>
        </div>

        <!-- Logged hours accumulators -->
        <div class="bg-white border border-[#cbd5e1] p-6 shadow-sm hover:border-[#0054A3] transition-all">
          <div class="flex justify-between items-start mb-4">
            <TrendingUp class="text-[#0054A3] w-6 h-6" />
            <span class="font-display text-[10px] font-black text-[#0054A3] bg-[#0054A3]/5 px-2 py-0.5 rounded">HORÓMETRO TOTAL</span>
          </div>
          <div class="font-display text-4xl font-extrabold text-[#0054A3]">{{ totalHoursLogged }}</div>
          <p class="font-sans text-xs text-slate-600 font-medium mt-1">
            Promedio: {{ avgHours }} HRS por bitácora ({{ activeLogsCounter }} registros guardados)
          </p>
        </div>

      </div>

      <!-- Graphic charts & core interactive telemetry lists -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Horizontal CSS Chart -->
        <div class="lg:col-span-8 bg-white border border-[#cbd5e1] p-6 flex flex-col justify-between">
          <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
            <div>
              <h3 class="font-display text-base font-bold text-slate-800">Uso Operativo por Equipo</h3>
              <p class="text-xs text-slate-600 font-medium">Baches promedio acumulados en el último mes</p>
            </div>
            <div class="flex gap-4">
              <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-800">
                <span class="w-3 h-3 bg-[#0054A3]"></span>
                <span>Flota Cooitzá</span>
              </div>
              <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-800">
                <span class="w-3 h-3 bg-[#FFD200]"></span>
                <span>Sello Operador</span>
              </div>
            </div>
          </div>

          <!-- Top-Tier Custom Dynamic Graph Grid -->
          <div class="flex-1 flex items-end gap-3 h-48 pb-2 border-b border-[#cbd5e1] relative">
            <!-- Gridlines -->
            <div class="absolute inset-x-0 top-1/4 border-t border-slate-100"></div>
            <div class="absolute inset-x-0 top-2/4 border-t border-slate-100"></div>
            <div class="absolute inset-x-0 top-3/4 border-t border-slate-100"></div>

            <!-- Bar 1: Tractor -->
            <div class="flex-1 flex flex-col items-center justify-end h-full group cursor-pointer z-10">
              <div class="w-full bg-[#cbd5e1] h-1/2 relative">
                <div class="absolute bottom-0 w-full bg-[#0054A3] h-[75%] group-hover:bg-[#004586] transition-all"></div>
              </div>
              <span class="font-sans text-[10px] font-bold text-slate-600 mt-2 text-center truncate w-full">Tractores</span>
            </div>

            <!-- Bar 2: Excavadoras -->
            <div class="flex-1 flex flex-col items-center justify-end h-full group cursor-pointer z-10">
              <div class="w-full bg-[#cbd5e1] h-1/2 relative">
                <div class="absolute bottom-0 w-full bg-[#FFD200] h-[95%] group-hover:brightness-95 transition-all"></div>
              </div>
              <span class="font-sans text-[10px] font-bold text-slate-600 mt-2 text-center truncate w-full">Excavadoras</span>
            </div>

            <!-- Bar 3: Retro -->
            <div class="flex-1 flex flex-col items-center justify-end h-full group cursor-pointer z-10">
              <div class="w-full bg-[#cbd5e1] h-1/2 relative">
                <div class="absolute bottom-0 w-full bg-[#0054A3] h-[60%] group-hover:bg-[#004586] transition-all"></div>
              </div>
              <span class="font-sans text-[10px] font-bold text-slate-600 mt-2 text-center truncate w-full">Retros</span>
            </div>

            <!-- Bar 4: Volquetes -->
            <div class="flex-1 flex flex-col items-center justify-end h-full group cursor-pointer z-10">
              <div class="w-full bg-[#cbd5e1] h-1/2 relative">
                <div class="absolute bottom-0 w-full bg-[#FFD200] h-[85%] group-hover:brightness-95 transition-all"></div>
              </div>
              <span class="font-sans text-[10px] font-bold text-slate-600 mt-2 text-center truncate w-full">Volteos</span>
            </div>

            <!-- Bar 5: Pipa -->
            <div class="flex-1 flex flex-col items-center justify-end h-full group cursor-pointer z-10">
              <div class="w-full bg-[#cbd5e1] h-1/2 relative">
                <div class="absolute bottom-0 w-full bg-[#0054A3] h-[40%] group-hover:bg-[#004586] transition-all"></div>
              </div>
              <span class="font-sans text-[10px] font-bold text-slate-600 mt-2 text-center truncate w-full">Pipas</span>
            </div>
          </div>
          
          <div class="mt-4 text-[10px] font-sans font-medium text-slate-600 text-center uppercase tracking-wide">
            Las unidades se autoajustan de acuerdo a las últimas lecturas enviadas por el personal de obra.
          </div>
        </div>

        <!-- Health diagnostics overview inside Dashboard -->
        <div class="lg:col-span-4 flex flex-col gap-6">
          
          <!-- System Health checklist -->
          <div class="bg-white border border-[#cbd5e1] p-5 shadow-sm">
            <h3 class="font-display text-xs font-black text-[#0054A3] uppercase tracking-wider mb-4">ESTADO DE ENLACES</h3>
            <div class="space-y-3 font-sans text-xs font-medium">
              
              <div class="flex justify-between items-center text-slate-600">
                <span>Servidor Principal Cooitzá</span>
                <span class="text-emerald-600 bg-emerald-50 border border-emerald-200 px-1.5 font-bold uppercase text-[9px]">ONLINE</span>
              </div>
              <div class="w-full bg-slate-100 h-1.5">
                <div class="bg-[#4CAF50] h-full w-[100%]"></div>
              </div>

              <div class="flex justify-between items-center text-slate-600">
                <span>Criptografía de Sesión SSL</span>
                <span class="text-[#0054A3] font-bold font-mono">256-BIT</span>
              </div>
              <div class="w-full bg-slate-100 h-1.5">
                <div class="bg-[#0054A3] h-full w-[80%]"></div>
              </div>

              <div class="flex justify-between items-center text-slate-600">
                <span>Servicio de Geolocalización</span>
                <span class="text-emerald-600 font-bold font-mono text-[10px]">ACTIVO (99.8%)</span>
              </div>

            </div>
          </div>

          <!-- Short recent logs preview -->
          <div class="bg-white border border-[#cbd5e1] shadow-sm flex flex-col">
            <div class="px-4 py-2.5 bg-slate-100 border-b border-[#cbd5e1] font-display text-xs font-bold text-[#0054A3]">
              ÚLTIMAS PUBLICACIONES DE OBRA
            </div>
            <div class="p-4 space-y-3 max-h-[160px] overflow-y-auto">
              <div v-for="(l, i) in recentLogs" :key="l.id || i" class="flex gap-2 items-start text-xs border-b pb-2 last:border-0 last:pb-0">
                <History class="text-[#0054A3] shrink-0 mt-0.5" :size="13" />
                <div class="truncate">
                  <p class="font-bold text-slate-800 truncate">
                    {{ getMachineLabel(l.machineType) }} ({{ l.horometroValue.toFixed(1) }} hrs)
                  </p>
                  <p class="text-[10px] text-slate-600 truncate">
                    Por {{ l.operatorName.replace(" (Técnico)", "") }} • {{ l.location.formattedAddress }}
                  </p>
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>

      <!-- High resolution detailed bitácora list database below -->
      <div class="bg-white border border-[#cbd5e1] p-4 shadow-sm flex flex-col gap-4">
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b pb-3 border-slate-100">
          <div>
            <h3 class="font-display text-base font-bold text-[#0054A3]">Bitácora Completa de Horómetros</h3>
            <p class="text-xs text-slate-600 font-medium">Buscador y exportador de lecturas certificadas de Guatemala.</p>
          </div>

          <!-- Search box and exports -->
          <div class="flex flex-wrap items-center gap-3">
            <div class="relative">
              <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 w-3.5 h-3.5" />
              <input 
                type="text" 
                placeholder="Buscar por operador o lote..."
                v-model="searchTerm"
                class="pl-8 pr-3 py-1 bg-slate-50 border border-[#cbd5e1] text-xs font-medium outline-none focus:border-[#0054A3]"
              />
            </div>
            <button 
              type="button"
              @click="$emit('triggerExport', 'Excel', filteredLogs.length)"
              class="text-xs font-bold bg-[#FFD200] text-[#0054A3] border border-[#FFD200] px-3 py-1 cursor-pointer hover:bg-[#ffe040] transition-colors"
            >
              EXCEL
            </button>
            <button 
              type="button"
              @click="$emit('triggerExport', 'PDF', filteredLogs.length)"
              class="text-xs font-bold bg-slate-800 text-white border border-slate-800 px-3 py-1 cursor-pointer hover:bg-slate-700 transition-colors"
            >
              PDF
            </button>
          </div>
        </div>

        <!-- Filters selection row -->
        <div class="flex flex-wrap gap-4 items-center bg-slate-50 p-2.5 text-xs text-slate-600 border border-slate-100">
          <div class="flex items-center gap-1.5">
            <span class="font-bold">Máquina:</span>
            <select 
              v-model="selectedMachineFilter"
              class="bg-white border border-slate-200 px-2 py-0.5 outline-none focus:border-[#0054A3]"
            >
              <option value="all">Todas</option>
              <option value="tractor">Tractor</option>
              <option value="excavadora">Excavadora</option>
              <option value="retro">Retro Excavadora</option>
              <option value="rodo">Rodo</option>
              <option value="pipa">Pipa</option>
              <option value="volteo">Camión Volteo</option>
            </select>
          </div>

          <div class="flex items-center gap-1.5">
            <span class="font-bold">Registro:</span>
            <select 
              v-model="selectedRegTypeFilter"
              class="bg-white border border-slate-200 px-2 py-0.5 outline-none focus:border-[#0054A3]"
            >
              <option value="all">Todos</option>
              <option value="inicial">Inicial</option>
              <option value="final">Final</option>
            </select>
          </div>
        </div>

        <!-- Table implementation -->
        <div class="overflow-x-auto w-full">
          <table class="w-full text-left font-sans text-xs divide-y divide-slate-200">
            <thead class="bg-slate-100 font-display font-bold text-[10px] text-slate-800 uppercase tracking-wider">
              <tr>
                <th class="py-2.5 px-3">Técnico / Fecha</th>
                <th class="py-2.5 px-3">Equipo</th>
                <th class="py-2.5 px-3">Clase</th>
                <th class="py-2.5 px-3">Horómetro</th>
                <th class="py-2.5 px-3">Ubicación GPS</th>
                <th class="py-2.5 px-3">Captura</th>
                <th class="py-2.5 px-3 text-center">Gestionar</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="filteredLogs.length === 0">
                <td colspan="7" class="py-8 text-center text-slate-400 italic">
                  Ninguna bitácora coincide con los filtros establecidos
                </td>
              </tr>
              <tr 
                v-else
                v-for="log in filteredLogs" 
                :key="log.id" 
                class="hover:bg-slate-50 transition-colors"
              >
                <td class="py-2.5 px-3">
                  <p class="font-bold text-slate-800">{{ log.operatorName }}</p>
                  <p class="text-[10px] text-slate-600 font-mono">{{ log.dateTime }}</p>
                </td>

                <td class="py-2.5 px-3">
                  <span class="inline-flex items-center gap-1 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                    <component :is="machineIconsMap[log.machineType] || Tractor" :size="12" class="text-[#0054A3]" />
                    <span class="font-semibold text-[10px]">{{ getMachineLabel(log.machineType) }}</span>
                  </span>
                </td>

                <td class="py-2.5 px-3">
                  <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider"
                        :class="log.regType === 'inicial' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">
                    {{ log.regType }}
                  </span>
                </td>

                <td class="py-2.5 px-3 font-mono font-bold text-sm text-[#0054A3]">
                  {{ log.horometroValue.toFixed(1) }} <span class="text-[9px] text-slate-500">HRS</span>
                </td>

                <td class="py-2.5 px-3 max-w-[150px] truncate" :title="log.location.formattedAddress">
                  <div class="flex items-center gap-0.5 text-[10px] text-red-600 font-mono">
                    <MapPin :size="10" class="text-red-500" />
                    <span>{{ log.location.lat.toFixed(2) }}°, {{ log.location.lng.toFixed(2) }}°</span>
                  </div>
                  <p class="text-[9px] text-slate-600 truncate mt-0.5">{{ log.location.formattedAddress }}</p>
                </td>

                <td class="py-2.5 px-3">
                  <button v-if="log.photoUrl"
                    type="button"
                    @click="$emit('openPhotoModal', log.photoUrl)"
                    class="text-[10px] font-bold bg-[#0054A3]/10 text-[#0054A3] hover:bg-[#0054A3]/25 px-2 py-0.5 rounded border border-[#0054A3]/20 flex items-center gap-1 cursor-pointer transition-colors"
                  >
                    <Eye :size="10" />
                    <span>VER DIAL</span>
                  </button>
                  <span v-else class="opacity-45 italic">N/A</span>
                </td>

                <td class="py-2.5 px-3 text-center">
                  <button 
                    type="button"
                    @click="$emit('deleteLog', log.id)"
                    class="p-1.5 hover:bg-red-50 text-red-600 rounded transition-colors cursor-pointer"
                    title="Eliminar registro"
                  >
                    <Trash2 :size="13" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { 
  Activity, Users, Truck, TrendingUp, History, Search, 
  MapPin, Eye, Trash2, Tractor, Construction, Wrench, 
  CircleSlash, Droplets
} from "lucide-vue-next";

const props = defineProps<{
  logs: any[];
  pilotsCount: number;
  pilotsActiveCount: number;
  pilotsRestingCount: number;
  vehiclesCount: number;
  vehiclesActiveCount: number;
  vehiclesMaintenanceCount: number;
}>();

const emit = defineEmits<{
  (e: 'deleteLog', id: string): void;
  (e: 'addMockLog'): void;
  (e: 'openPhotoModal', url: string): void;
  (e: 'triggerExport', format: "Excel" | "PDF", count: number): void;
}>();

const searchTerm = ref("");
const selectedMachineFilter = ref("all");
const selectedRegTypeFilter = ref("all");

const machineIconsMap: Record<string, any> = {
  tractor: Tractor,
  excavadora: Construction,
  retro: Wrench,
  rodo: CircleSlash,
  pipa: Droplets,
  volteo: Truck,
};

const getMachineLabel = (type: string) => {
  switch (type) {
    case "tractor": return "Tractor";
    case "excavadora": return "Excavadora";
    case "retro": return "Retro Excavadora";
    case "rodo": return "Rodo de Presión";
    case "pipa": return "Pipa Cisterna";
    case "volteo": return "Camión Volteo";
    default: return type;
  }
};

const filteredLogs = computed(() => {
  return props.logs.filter(log => {
    const operatorMatches = log.operatorName.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
                            log.location.formattedAddress.toLowerCase().includes(searchTerm.value.toLowerCase());
    const machineMatches = selectedMachineFilter.value === "all" || log.machineType === selectedMachineFilter.value;
    const regMatches = selectedRegTypeFilter.value === "all" || log.regType === selectedRegTypeFilter.value;
    return operatorMatches && machineMatches && regMatches;
  });
});

const recentLogs = computed(() => props.logs.slice(0, 3));

const totalHoursLogged = computed(() => props.logs.reduce((acc, log) => acc + log.horometroValue, 0).toFixed(1));
const avgHours = computed(() => props.logs.length > 0 ? (parseFloat(totalHoursLogged.value) / props.logs.length).toFixed(1) : "0.0");
const activeLogsCounter = computed(() => props.logs.length);
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
