<template>
  <transition name="fade-up" appear>
    <div class="flex flex-col gap-6 pb-12">
      <!-- Command Center Title -->
      <div class="mb-2 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <span class="font-display text-[10px] font-black text-[#0054A3] tracking-widest uppercase">RESUMEN GENERAL</span>
          <h2 class="font-display text-3xl font-black text-slate-800 tracking-tight mt-0.5">Control de Telemetría</h2>
        </div>
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

        <!-- Machinery Count -->
        <div class="bg-white border border-[#cbd5e1] p-6 shadow-sm hover:border-[#0054A3] transition-all">
          <div class="flex justify-between items-start mb-4">
            <Tractor class="text-[#0054A3] w-6 h-6" />
            <span class="font-display text-[10px] font-black text-[#0054A3] bg-[#0054A3]/5 px-2 py-0.5 rounded">MAQUINARIAS</span>
          </div>
          <div class="font-display text-4xl font-extrabold text-[#0054A3]">{{ machineryCount }}</div>
          <p class="font-sans text-xs text-slate-600 font-medium mt-1">
            Total Registradas en Inventario
          </p>
        </div>
      </div>

      <!-- Graphic charts & core interactive telemetry lists -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Horizontal CSS Chart -->
        <div class="lg:col-span-8 bg-white border border-[#cbd5e1] p-6 flex flex-col justify-between shadow-sm">
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
            <div class="absolute inset-x-0 top-1/4 border-t border-slate-100"></div>
            <div class="absolute inset-x-0 top-2/4 border-t border-slate-100"></div>
            <div class="absolute inset-x-0 top-3/4 border-t border-slate-100"></div>

            <div v-for="item in horasPorTipo" :key="item.tipo" class="flex-1 flex flex-col items-center justify-end h-full group cursor-pointer z-10" :title="item.horas + ' HRS'">
              <div class="w-full bg-[#cbd5e1] h-1/2 relative">
                <div class="absolute bottom-0 w-full bg-[#0054A3] group-hover:bg-[#004586] transition-all" :style="{ height: item.porcentaje + '%' }"></div>
              </div>
              <span class="font-sans text-[10px] font-bold text-slate-600 mt-2 text-center truncate w-full">{{ item.tipo }}</span>
            </div>
          </div>
          
          <div class="mt-4 text-[10px] font-sans font-medium text-slate-600 text-center uppercase tracking-wide">
            Las unidades se autoajustan de acuerdo a las últimas lecturas enviadas por el personal de obra.
          </div>
        </div>

        <!-- Health diagnostics overview inside Dashboard -->
        <div class="lg:col-span-4 flex flex-col gap-6">
          <div class="bg-white border border-[#cbd5e1] p-5 shadow-sm h-full">
            <h3 class="font-display text-xs font-black text-[#0054A3] uppercase tracking-wider mb-4">MÁQUINAS CON MAYOR DESGASTE</h3>
            <div class="space-y-3 font-sans text-xs font-medium">
              <div v-if="topMaquinas.length === 0" class="text-slate-400 italic">No hay registros suficientes.</div>
              <div v-for="(maq, index) in topMaquinas" :key="maq.id" class="flex justify-between items-center text-slate-600 border-b border-slate-100 pb-2 last:border-0">
                <div>
                  <span class="font-bold">{{ index + 1 }}. {{ maq.marca }}</span>
                  <span class="text-[9px] text-slate-400 ml-1">({{ maq.identificador || maq.modelo }})</span>
                </div>
                <span class="text-amber-600 bg-amber-50 border border-amber-200 px-1.5 font-bold font-mono text-[10px]">{{ maq.horas_acumuladas || 0 }} HRS</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- MAIN TABLE: Registros de Maquinaria -->
      <div class="bg-white border border-[#cbd5e1] shadow-sm flex flex-col mt-4">
        <!-- Table Filter Topbar -->
        <div class="px-6 py-4 bg-slate-50 border-b border-[#cbd5e1] flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <span class="font-display text-xs font-black uppercase tracking-widest text-[#0054A3]">
            Bitácora General de Operaciones
          </span>
        </div>

        <!-- Data Table -->
        <div class="overflow-x-auto">
          <table class="w-full border-collapse text-left">
            <thead>
              <tr class="border-b border-[#cbd5e1] bg-slate-50 select-none">
                <th class="p-4 font-display text-[10px] font-bold uppercase tracking-wider text-slate-600">ID / Fecha</th>
                <th class="p-4 font-display text-[10px] font-bold uppercase tracking-wider text-slate-600">Operador</th>
                <th class="p-4 font-display text-[10px] font-bold uppercase tracking-wider text-slate-600">Máquina</th>
                <th class="p-4 font-display text-[10px] font-bold uppercase tracking-wider text-slate-600">Lectura Horómetro</th>
                <th class="p-4 font-display text-[10px] font-bold uppercase tracking-wider text-slate-600 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#cbd5e1]">
              <tr v-if="currentRegistros.length === 0">
                <td colspan="5" class="p-12 text-center text-slate-400 font-medium italic">
                  No hay registros disponibles en este momento.
                </td>
              </tr>
              <tr 
                v-else 
                v-for="r in currentRegistros" 
                :key="r.id" 
                class="hover:bg-slate-50/50 transition-all group"
              >
                <td class="p-4">
                  <div class="font-mono text-xs font-bold text-slate-800">#{{ r.id }}</div>
                  <div class="text-[10px] text-slate-500 font-mono">{{ formatDate(r.fecha_registro) }}</div>
                </td>
                <td class="p-4">
                  <div class="font-sans text-xs font-bold text-[#0054A3] uppercase">{{ r.creador_nombre || r.operador }}</div>
                  <div class="text-[9px] text-slate-400 font-bold uppercase">Registrado por: {{ r.operador }}</div>
                </td>
                <td class="p-4 text-xs font-bold uppercase text-slate-700">
                  {{ r.maquina_id }}
                </td>
                <td class="p-4">
                  <div class="flex items-center gap-2">
                    <span 
                      class="px-2 py-0.5 rounded-sm text-[10px] font-bold uppercase border"
                      :class="r.tipo_registro === 'inicial' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'"
                    >
                      {{ r.tipo_registro }}
                    </span>
                    <span class="font-mono text-sm font-black text-slate-800">{{ r.valor_horometro }}</span>
                  </div>
                </td>
                <td class="p-4 text-right">
                  <div class="flex justify-end gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                    <button 
                      @click="handleViewRegistro(r)"
                      class="p-1.5 text-emerald-600 hover:bg-emerald-50 border border-[#cbd5e1] bg-white transition-all cursor-pointer"
                      title="Ver Detalles (Foto y Mapa)"
                    >
                      <Eye :size="13" />
                    </button>
                    <button 
                      @click="handleEditRegistro(r)"
                      class="p-1.5 text-[#0054A3] hover:bg-[#0054A3]/10 border border-[#cbd5e1] bg-white transition-all cursor-pointer"
                      title="Editar Registro"
                    >
                      <Edit2 :size="13" />
                    </button>
                    <button 
                      @click="handleDeleteRegistro(r.id)"
                      class="p-1.5 text-red-600 hover:bg-red-50 border border-[#cbd5e1] bg-white transition-all cursor-pointer"
                      title="Eliminar permanentemente"
                    >
                      <Trash2 :size="13" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Bar -->
        <div class="px-6 py-4 bg-slate-50 border-t border-[#cbd5e1] flex flex-col sm:flex-row justify-between items-center gap-3 select-none">
          <span class="font-display text-[10px] font-bold uppercase tracking-wider text-slate-500">
            Mostrando {{ indexOfFirstItem + 1 }}-{{ Math.min(indexOfLastItem, totalItems) }} de {{ totalItems }} registros
          </span>

          <div class="flex gap-1.5">
            <button 
              :disabled="currentPage === 1"
              @click="currentPage = Math.max(currentPage - 1, 1)"
              class="w-8 h-8 flex items-center justify-center border border-[#cbd5e1] bg-white hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer transition-colors text-slate-800"
            >
              <ChevronLeft :size="16" />
            </button>
            
            <button
              v-for="pageNum in visiblePages"
              :key="pageNum"
              @click="currentPage = pageNum"
              class="w-8 h-8 flex items-center justify-center border font-display text-xs font-black transition-colors"
              :class="currentPage === pageNum ? 'bg-[#0054A3] text-white border-[#0054A3]' : 'border-[#cbd5e1] bg-white hover:bg-slate-50 cursor-pointer text-slate-800'"
            >
              {{ pageNum }}
            </button>

            <button 
              :disabled="currentPage === totalPages"
              @click="currentPage = Math.min(currentPage + 1, totalPages)"
              class="w-8 h-8 flex items-center justify-center border border-[#cbd5e1] bg-white hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer transition-colors text-slate-800"
            >
              <ChevronRight :size="16" />
            </button>
          </div>
        </div>
      </div>

      <!-- EDIT MODAL -->
      <transition name="fade">
        <div v-if="isEditFormOpen" class="fixed inset-0 bg-slate-900/40 z-[60] flex items-center justify-center p-4">
          <div class="bg-white border-2 border-[#cbd5e1] w-full max-w-sm shadow-2xl relative flex flex-col overflow-hidden">
            <div class="w-full h-1 bg-[#FFD200]"></div>
            <div class="flex justify-between items-center bg-slate-50 px-6 py-4 border-b border-[#cbd5e1]">
              <span class="font-display text-xs font-black text-[#0054A3] uppercase tracking-wider">
                Editar Registro #{{ editForm.id }}
              </span>
              <button @click="isEditFormOpen = false" class="p-1 hover:bg-slate-200 text-slate-500 cursor-pointer">
                <X :size="18" />
              </button>
            </div>

            <form @submit.prevent="submitEditForm" class="p-6 space-y-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-slate-600 text-[10px] font-bold uppercase tracking-wider">Valor de Horómetro</label>
                <input 
                  type="number" step="0.01" required v-model="editForm.valor_horometro"
                  class="w-full bg-slate-50 border border-[#cbd5e1] p-2.5 font-mono text-sm font-bold focus:ring-1 focus:ring-[#FFD200] outline-none text-slate-800"
                />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-slate-600 text-[10px] font-bold uppercase tracking-wider">Tipo de Registro</label>
                <select v-model="editForm.tipo_registro" class="w-full bg-slate-50 border border-[#cbd5e1] p-2.5 font-sans text-xs focus:ring-1 outline-none text-slate-800">
                  <option value="inicial">Inicial</option>
                  <option value="final">Final</option>
                </select>
              </div>

              <div class="pt-4 flex justify-end gap-3 border-t border-[#cbd5e1]/60 mt-6">
                <button type="button" @click="isEditFormOpen = false" class="px-4 py-2 text-xs font-black uppercase text-slate-500 hover:bg-slate-100 cursor-pointer">Cancelar</button>
                <button type="submit" class="bg-[#0054A3] hover:bg-[#004586] text-white px-5 py-2 text-xs font-black uppercase flex items-center gap-1.5 cursor-pointer">
                  Guardar
                </button>
              </div>
            </form>
          </div>
        </div>
      </transition>

      <!-- VIEW MODAL (PHOTO + MAP) -->
      <transition name="fade">
        <div v-if="isViewModalOpen" class="fixed inset-0 bg-slate-900/80 z-[60] flex items-center justify-center p-4">
          <div class="bg-white border border-[#cbd5e1] w-full max-w-4xl shadow-2xl relative flex flex-col md:flex-row h-[80vh] md:h-[600px] overflow-hidden">
            
            <button @click="closeViewModal" class="absolute top-2 right-2 p-1.5 bg-black/50 text-white rounded-full hover:bg-red-600 z-10 cursor-pointer transition-colors">
              <X :size="18" />
            </button>

            <!-- Photo Section -->
            <div class="w-full md:w-1/2 bg-black flex items-center justify-center relative">
              <img :src="getPhotoUrl(selectedViewRegistro?.foto_horometro)" class="max-h-full max-w-full object-contain" />
              <div class="absolute bottom-4 left-4 right-4 bg-black/60 text-white p-3 text-xs font-mono">
                <div class="font-bold text-[#FFD200]">INFO CAPTURA</div>
                <div>Horómetro: {{ selectedViewRegistro?.valor_horometro }}</div>
                <div>Máquina: {{ selectedViewRegistro?.maquina_id }}</div>
              </div>
            </div>

            <!-- Map Section -->
            <div class="w-full md:w-1/2 flex flex-col">
              <div class="bg-slate-50 p-4 border-b border-[#cbd5e1]">
                <h3 class="font-display text-sm font-black text-[#0054A3] uppercase">Ubicación GPS</h3>
                <p class="text-[10px] text-slate-500 font-mono">LAT: {{ selectedViewRegistro?.latitud }} | LNG: {{ selectedViewRegistro?.longitud }}</p>
              </div>
              <div class="flex-grow relative">
                <div id="admin-map" class="absolute inset-0 z-0"></div>
              </div>
            </div>

          </div>
        </div>
      </transition>

    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, onMounted, watch } from 'vue';
import { 
  Users, Truck, Tractor, ChevronLeft, ChevronRight, Eye, Edit2, Trash2, X
} from "lucide-vue-next";
import Swal from 'sweetalert2';

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
  pilotsCount: number;
  pilotsActiveCount: number;
  pilotsRestingCount: number;
  vehiclesCount: number;
  vehiclesActiveCount: number;
  vehiclesMaintenanceCount: number;
  machineryCount: number;
  maquinas?: any[];
  registros?: any[];
}>();

const emit = defineEmits(['refresh-registros']);

// Graph Data
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
    return { tipo, horas: agrupado[tipo] };
  });
  return resultado.map(r => ({
    ...r,
    porcentaje: maxHoras > 0 ? (r.horas / maxHoras) * 100 : 0
  }));
});

const topMaquinas = computed(() => {
  if (!props.maquinas) return [];
  return [...props.maquinas].sort((a, b) => (parseFloat(b.horas_acumuladas) || 0) - (parseFloat(a.horas_acumuladas) || 0)).slice(0, 5);
});

// Pagination
const currentPage = ref(1);
const itemsPerPage = 10;
const registrosData = computed(() => props.registros || []);

const totalItems = computed(() => registrosData.value.length);
const totalPages = computed(() => Math.ceil(totalItems.value / itemsPerPage) || 1);
const indexOfLastItem = computed(() => currentPage.value * itemsPerPage);
const indexOfFirstItem = computed(() => indexOfLastItem.value - itemsPerPage);
const currentRegistros = computed(() => registrosData.value.slice(indexOfFirstItem.value, indexOfLastItem.value));

const visiblePages = computed(() => {
  const pages = [];
  for (let i = 1; i <= totalPages.value; i++) {
    pages.push(i);
  }
  return pages;
});

// Helpers
const formatDate = (dateStr: string) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleString('es-GT', { dateStyle: 'short', timeStyle: 'short' });
};

const getPhotoUrl = (path: string | undefined) => {
  if (!path) return '';
  if (path.startsWith('http')) return path;
  return `/maquinaria-cooitza/Backend/${path}`;
};

// CRUD Operations
const isEditFormOpen = ref(false);
const editForm = ref({ id: 0, valor_horometro: 0, tipo_registro: 'inicial' });

const handleEditRegistro = (r: any) => {
  editForm.value = {
    id: r.id,
    valor_horometro: parseFloat(r.valor_horometro),
    tipo_registro: r.tipo_registro
  };
  isEditFormOpen.value = true;
};

const submitEditForm = async () => {
  try {
    const res = await fetch('/maquinaria-cooitza/Backend/api/v1/maquinaria/registros/update', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(editForm.value)
    });
    if (res.ok) {
      isEditFormOpen.value = false;
      Swal.fire({ toast: true, position: 'bottom-start', icon: 'success', title: 'Registro actualizado', showConfirmButton: false, timer: 2500 });
      emit('refresh-registros');
    } else {
      Swal.fire('Error', 'No se pudo actualizar el registro', 'error');
    }
  } catch (e) {
    Swal.fire('Error', 'Problema de conexión con el servidor', 'error');
  }
};

const handleDeleteRegistro = async (id: number) => {
  const result = await Swal.fire({
    title: '¿Eliminar Registro?',
    text: `Esta acción no se puede deshacer.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ba1a1a',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, eliminar'
  });

  if (result.isConfirmed) {
    try {
      const res = await fetch('/maquinaria-cooitza/Backend/api/v1/maquinaria/registros/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      if (res.ok) {
        Swal.fire({ toast: true, position: 'bottom-start', icon: 'success', title: 'Registro eliminado', showConfirmButton: false, timer: 2500 });
        emit('refresh-registros');
      }
    } catch (e) {
      Swal.fire('Error', 'Problema de conexión', 'error');
    }
  }
};

// Map View Modal
const isViewModalOpen = ref(false);
const selectedViewRegistro = ref<any>(null);
let mapInstance: L.Map | null = null;
let markerInstance: L.Marker | null = null;

const handleViewRegistro = (r: any) => {
  selectedViewRegistro.value = r;
  isViewModalOpen.value = true;

  nextTick(() => {
    if (mapInstance) {
      mapInstance.remove();
      mapInstance = null;
    }
    const lat = parseFloat(r.latitud) || 14.54317;
    const lng = parseFloat(r.longitud) || -90.54946;

    mapInstance = L.map('admin-map').setView([lat, lng], 16);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(mapInstance);

    markerInstance = L.marker([lat, lng]).addTo(mapInstance);
    
    // Force recalculation after container fully opens
    setTimeout(() => {
      if (mapInstance) mapInstance.invalidateSize();
    }, 200);
  });
};

const closeViewModal = () => {
  isViewModalOpen.value = false;
  selectedViewRegistro.value = null;
  if (mapInstance) {
    mapInstance.remove();
    mapInstance = null;
  }
};

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

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
