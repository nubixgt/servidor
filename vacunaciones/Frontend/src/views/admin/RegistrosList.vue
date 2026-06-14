<template>
  <div class="space-y-6 animate-fade-in relative pb-10">
    
    <!-- Title block -->
    <section class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h2 class="text-3xl font-extrabold text-[#3455b9] mb-1">Historial Clínico de Servicios</h2>
        <p class="text-[#475569] text-sm font-bold">Consulte, filtre y modifique los registros guardados en campo.</p>
      </div>
      <div class="flex gap-2 w-full sm:w-auto shrink-0">
        <button
          @click="handleExportCSV"
          class="flex-1 sm:flex-initial bg-white/60 hover:bg-white text-[#1e293b] font-bold px-4 py-2.5 rounded-xl border border-slate-200/60 shadow-3xs flex items-center justify-center gap-1.5 transition-all text-xs cursor-pointer"
        >
          <ArrowDownTrayIcon class="w-4 h-4 text-[#3455b9]" />
          <span>Descargar CSV</span>
        </button>
        <button
          @click="navigateToNuevo"
          class="flex-1 sm:flex-initial bg-[#3455b9] hover:opacity-95 text-white font-bold px-4 py-2.5 rounded-xl shadow-xs flex items-center justify-center gap-1.5 transition-all text-xs cursor-pointer"
        >
          <PlusIcon class="w-4 h-4" />
          <span>Nuevo Registro</span>
        </button>
      </div>
    </section>

    <!-- Filter and search utilities bar -->
    <div class="glass-panel p-4 md:p-6 space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        
        <!-- Real-time search term bar -->
        <div class="relative md:col-span-2">
          <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4.5 h-4.5 text-[#3455b9]" />
          <input
            type="text"
            placeholder="Buscar por cliente, veterinario, dirección..."
            v-model="searchTerm"
            class="w-full pl-10 pr-4 py-2.5 bg-white/40 border border-slate-200 rounded-xl text-sm text-[#1e293b] placeholder-gray-500 font-medium focus:outline-none focus:ring-4 focus:ring-[#3455b9]/10 focus:bg-white/60 transition-all"
          />
        </div>

        <!-- Service Dropdown category Filter -->
        <div>
          <select
            v-model="selectedService"
            class="w-full px-3 py-2.5 bg-white/40 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-4 focus:ring-[#3455b9]/10 focus:bg-white/60 transition-all cursor-pointer font-bold text-[#1e293b]"
          >
            <option value="Todos" class="text-gray-900 font-semibold">Todos los Servicios</option>
            <option v-for="srv in SERVICIOS_PRESTADOS" :key="srv" :value="srv" class="text-gray-900 font-semibold">{{ srv }}</option>
          </select>
        </div>

        <!-- Status Dropdown category filter -->
        <div>
          <select
            v-model="selectedStatus"
            class="w-full px-3 py-2.5 bg-white/40 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-4 focus:ring-[#3455b9]/10 focus:bg-white/60 transition-all cursor-pointer font-bold text-[#1e293b]"
          >
            <option value="Todos" class="text-gray-900 font-semibold">Todos los Estados</option>
            <option value="Completado" class="text-gray-900 font-semibold">Completado</option>
            <option value="Pendiente" class="text-gray-900 font-semibold">Pendiente</option>
            <option value="Cancelado" class="text-gray-900 font-semibold">Cancelado</option>
          </select>
        </div>

        <!-- Year Dropdown category filter -->
        <div>
          <select
            v-model="selectedYear"
            class="w-full px-3 py-2.5 bg-white/40 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-4 focus:ring-[#3455b9]/10 focus:bg-white/60 transition-all cursor-pointer font-bold text-[#1e293b]"
          >
            <option value="Todos" class="text-gray-900 font-semibold">Todos los Años</option>
            <option value="2026" class="text-gray-900 font-semibold">2026</option>
            <option value="2025" class="text-gray-900 font-semibold">2025</option>
            <option value="2024" class="text-gray-900 font-semibold">2024</option>
          </select>
        </div>

      </div>

      <!-- Quick info summaries inside records list -->
      <div class="flex gap-6 text-xs text-[#475569] font-bold px-1 bg-white/20 p-2.5 rounded-lg border border-slate-200/50">
        <span>Registros Coincidentes: <strong class="text-[#3455b9] font-extrabold">{{ filteredRecords.length }}</strong></span>
        <span>Aves Totales: <strong class="text-teal-700 font-extrabold">{{ formatNumber(totalAvesFiltered) }}</strong></span>
        <span>Suma Facturación: <strong class="text-pink-700 font-extrabold">{{ formatCurrency(totalFacturacionFiltered) }}</strong></span>
      </div>
    </div>

    <!-- Main glass spreadsheet list -->
    <div class="glass-panel overflow-hidden">
      <div class="overflow-x-auto">
        <div v-if="filteredRecords.length === 0" class="p-16 text-center text-gray-500 bg-white/10">
          <MagnifyingGlassIcon class="w-10 h-10 text-gray-300 mx-auto mb-2" />
          <p class="font-bold text-base">Sin resultados coincidentes</p>
          <p class="text-xs mt-1">Pruebe modificando los términos de búsqueda o filtros generales.</p>
        </div>
        <table v-else class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-white/30 border-b border-gray-100/35">
              <th
                @click="toggleSort('fecha')"
                class="px-6 py-4.5 text-xs font-bold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-white/40 transition-colors"
              >
                <div class="flex items-center gap-1">
                  <span>Fecha</span>
                  <ArrowsUpDownIcon class="w-3.5 h-3.5 text-gray-400" />
                </div>
              </th>
              <th class="px-6 py-4.5 text-xs font-bold text-gray-600 uppercase tracking-wider">Cliente / Granja</th>
              <th class="px-6 py-4.5 text-xs font-bold text-gray-600 uppercase tracking-wider">Servicio Prestado</th>
              <th
                @click="toggleSort('cantidad')"
                class="px-6 py-4.5 text-xs font-bold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-white/40 transition-colors"
              >
                <div class="flex items-center gap-1">
                  <span>Cantidad</span>
                  <ArrowsUpDownIcon class="w-3.5 h-3.5 text-gray-400" />
                </div>
              </th>
              <th class="px-6 py-4.5 text-xs font-bold text-gray-600 uppercase tracking-wider">Unitario (Q)</th>
              <th
                @click="toggleSort('total')"
                class="px-6 py-4.5 text-xs font-bold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-white/40 transition-colors"
              >
                <div class="flex items-center gap-1">
                  <span>Total</span>
                  <ArrowsUpDownIcon class="w-3.5 h-3.5 text-gray-400" />
                </div>
              </th>
              <th class="px-6 py-4.5 text-xs font-bold text-gray-600 uppercase tracking-wider">Veterinario</th>
              <th class="px-6 py-4.5 text-xs font-bold text-gray-600 uppercase tracking-wider">Estado</th>
              <th class="px-6 py-4.5 text-xs font-bold text-gray-600 uppercase tracking-wider text-right">Acción</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100/30">
            <tr v-for="rec in filteredRecords" :key="rec.id" class="hover:bg-white/20 transition-all group">
              
              <!-- Date cell -->
              <td class="px-6 py-4">
                <div class="flex flex-col">
                  <strong class="text-gray-800 text-xs">{{ formatCompactDate(rec.fecha) }}</strong>
                  <span class="text-[10px] text-gray-500 font-semibold">{{ rec.hora }}</span>
                </div>
              </td>

              <!-- Client cell with initials -->
              <td class="px-6 py-4">
                <div class="flex items-start gap-2 max-w-[200px]">
                  <span class="w-7 h-7 rounded-md bg-white/70 border border-gray-200/50 flex items-center justify-center font-black text-[#3455b9] text-[9px] shrink-0">
                    {{ rec.clienteIniciales }}
                  </span>
                  <div>
                    <p class="text-xs font-bold text-gray-800 line-clamp-1">{{ rec.cliente }}</p>
                    <p class="text-[9px] text-gray-400 font-semibold line-clamp-1" :title="rec.direccion">{{ rec.direccion }}</p>
                  </div>
                </div>
              </td>

              <!-- Service Name -->
              <td class="px-6 py-4 text-xs font-semibold text-gray-700">{{ rec.servicio }}</td>

              <!-- Bird Quantity -->
              <td class="px-6 py-4 text-xs font-bold text-gray-800">{{ formatNumber(rec.cantidad) }} aves</td>

              <!-- Cost per unit -->
              <td class="px-6 py-4 text-xs text-gray-500 font-mono">Q {{ rec.costoPorAve.toFixed(4) }}</td>

              <!-- Estimated Total -->
              <td class="px-6 py-4 text-xs font-black text-[#3455b9]">{{ formatCurrency(rec.total) }}</td>

              <!-- Veterinarian -->
              <td class="px-6 py-4 text-xs text-gray-500 font-medium truncate max-w-[110px]">{{ rec.vacunador }}</td>

              <!-- Dynamic status selectors inline -->
              <td class="px-6 py-4">
                <div class="relative inline-block">
                  <select
                    v-model="rec.estado"
                    @change="handleStatusChange(rec.id, rec.estado)"
                    :class="`text-[10px] font-bold px-2 py-1 rounded-full cursor-pointer border-0 ring-1 focus:ring-1 focus:outline-none ${
                      rec.estado === 'Completado'
                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-500/20'
                        : rec.estado === 'Pendiente'
                          ? 'bg-amber-50 text-amber-700 ring-amber-500/20'
                          : 'bg-red-50 text-red-700 ring-red-500/20'
                    }`"
                  >
                    <option value="Completado">Completado</option>
                    <option value="Pendiente">Pendiente</option>
                    <option value="Cancelado">Cancelado</option>
                  </select>
                </div>
              </td>

              <!-- Delete actions -->
              <td class="px-6 py-4 text-right flex justify-end gap-2">
                <button
                  @click="openEditModal(rec)"
                  class="p-1.5 text-gray-400 hover:text-[#3455b9] hover:bg-[#3455b9]/10 rounded-lg opacity-0 group-hover:opacity-100 transition-all cursor-pointer"
                  title="Editar este registro"
                >
                  <PencilIcon class="w-4 h-4" />
                </button>
                <button
                  @click="handleDeleteClick(rec.id, rec.cliente)"
                  class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg opacity-0 group-hover:opacity-100 transition-all cursor-pointer"
                  title="Eliminar este registro permanentemente"
                >
                  <TrashIcon class="w-4 h-4" />
                </button>
              </td>

            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="isEditModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-[#1e293b]/60 backdrop-blur-md p-4">
      <div class="bg-white rounded-[32px] shadow-2xl w-[98%] max-w-2xl p-5 md:p-8 overflow-y-auto max-h-[90vh]">
        <div class="flex justify-between items-center mb-6">
          <div>
            <h3 class="text-2xl font-black text-[#3455b9]">Editar Registro</h3>
            <p class="text-xs text-gray-500 font-medium mt-1">Modifique los detalles del servicio prestado.</p>
          </div>
          <button @click="closeEditModal" class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-red-500 transition-all cursor-pointer">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>
        <form @submit.prevent="submitEditForm" class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
          <!-- Row 1 -->
          <div class="space-y-2">
            <label class="block text-xs font-black text-[#475569] uppercase tracking-wider">Fecha</label>
            <div class="relative group">
              <CalendarIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#3455b9]/80 transition-colors group-focus-within:text-[#3455b9]" />
              <input type="date" required v-model="editFormData.fecha" class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-3.5 md:py-4 pl-12 pr-4 text-sm font-medium text-[#1e293b] focus:outline-none focus:ring-4 focus:ring-[#3455b9]/10 focus:bg-white transition-all" />
            </div>
          </div>
          <div class="space-y-2">
            <label class="block text-xs font-black text-[#475569] uppercase tracking-wider">Vacunador</label>
            <div class="relative group">
              <UserIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#3455b9]/80 transition-colors group-focus-within:text-[#3455b9]" />
              <input type="text" required v-model="editFormData.vacunador" class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-3.5 md:py-4 pl-12 pr-4 text-sm font-medium text-[#1e293b] focus:outline-none focus:ring-4 focus:ring-[#3455b9]/10 focus:bg-white transition-all" />
            </div>
          </div>
          
          <!-- Row 2 -->
          <div class="space-y-2 md:col-span-2">
            <label class="block text-xs font-black text-[#475569] uppercase tracking-wider">Cliente / Granja</label>
            <div class="relative group">
              <BuildingOfficeIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#3455b9]/80 transition-colors group-focus-within:text-[#3455b9]" />
              <input type="text" required v-model="editFormData.cliente" class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-3.5 md:py-4 pl-12 pr-4 text-sm font-medium text-[#1e293b] focus:outline-none focus:ring-4 focus:ring-[#3455b9]/10 focus:bg-white transition-all" />
            </div>
          </div>
          
          <!-- Row 3 -->
          <div class="space-y-2 md:col-span-2">
            <label class="block text-xs font-black text-[#475569] uppercase tracking-wider">Dirección</label>
            <div class="relative group">
              <MapPinIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#3455b9]/80 transition-colors group-focus-within:text-[#3455b9]" />
              <input type="text" required v-model="editFormData.direccion" class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-3.5 md:py-4 pl-12 pr-4 text-sm font-medium text-[#1e293b] focus:outline-none focus:ring-4 focus:ring-[#3455b9]/10 focus:bg-white transition-all" />
            </div>
          </div>
          
          <!-- Row 4 -->
          <div class="space-y-2">
            <label class="block text-xs font-black text-[#475569] uppercase tracking-wider">Servicio Prestado</label>
            <div class="relative group">
              <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#3455b9]/80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
              </svg>
              <select required v-model="editFormData.servicio" @change="updateEditTotal" class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-3.5 md:py-4 pl-12 pr-10 text-sm font-bold text-[#1e293b] focus:outline-none focus:ring-4 focus:ring-[#3455b9]/10 focus:bg-white transition-all appearance-none cursor-pointer">
                <option v-for="serv in SERVICIOS_PRESTADOS" :key="serv" :value="serv">{{ serv }}</option>
              </select>
              <div class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-700 pointer-events-none">
                <svg class="w-5 h-5 text-[#3455b9]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div>
          </div>
          <div class="space-y-2">
            <label class="block text-xs font-black text-[#475569] uppercase tracking-wider">Cantidad (Aves)</label>
            <div class="relative group">
              <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#3455b9]/80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              <input type="number" required min="1" v-model.number="editFormData.cantidad" @input="updateEditTotal" class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-3.5 md:py-4 pl-12 pr-4 text-sm font-bold text-[#1e293b] focus:outline-none focus:ring-4 focus:ring-[#3455b9]/10 focus:bg-white transition-all" />
            </div>
          </div>
          
          <!-- Row 5 (Calculated values) -->
          <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 p-5 sm:p-6 mt-2 rounded-3xl bg-slate-50 border border-slate-100">
            <div class="space-y-2">
              <span class="block text-[11px] font-black text-[#475569] uppercase tracking-wider">Costo por Ave (Q)</span>
              <div class="bg-[#3455b9]/10 text-[#3455b9] px-4 sm:px-6 py-3 sm:py-4 rounded-2xl font-black text-center text-lg">
                Q {{ (editFormData.costoPorAve || 0).toFixed(4) }}
              </div>
            </div>
            <div class="space-y-2">
              <span class="block text-[11px] font-black text-[#475569] uppercase tracking-wider">Total Estimado del Servicio</span>
              <div class="bg-emerald-500/10 text-emerald-700 px-4 sm:px-6 py-3 sm:py-4 rounded-2xl font-black text-center text-2xl">
                {{ formatCurrency(editFormData.total || 0) }}
              </div>
            </div>
          </div>
          
          <!-- Buttons -->
          <div class="md:col-span-2 flex flex-col sm:flex-row justify-end gap-3 mt-4 pt-6 border-t border-slate-100">
            <button type="button" @click="closeEditModal" class="w-full sm:w-auto px-6 py-3.5 md:py-3 rounded-2xl font-bold text-gray-500 bg-gray-100 hover:bg-gray-200 cursor-pointer transition-colors text-center">Cancelar</button>
            <button type="submit" class="w-full sm:w-auto px-6 py-3.5 md:py-3 rounded-2xl bg-[#3455b9] font-bold text-white shadow-md hover:bg-[#3455b9]/90 hover:shadow-lg cursor-pointer transition-all flex items-center justify-center gap-2">
               <template v-if="isSaving">
                  <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                  </svg>
                  <span>Guardando...</span>
               </template>
               <template v-else>
                  <span>Guardar Cambios</span>
               </template>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/appStore';
import { formatCurrency, formatNumber, formatCompactDate, SERVICIOS_PRESTADOS, getServiceUnitCost } from '../../utils/data';
import Swal from 'sweetalert2';
import { 
  MagnifyingGlassIcon, 
  ArrowDownTrayIcon, 
  ArrowsUpDownIcon, 
  TrashIcon, 
  PlusIcon,
  PencilIcon,
  XMarkIcon,
  CalendarIcon,
  UserIcon,
  BuildingOfficeIcon,
  MapPinIcon
} from '@heroicons/vue/24/outline';

const store = useAppStore();
const router = useRouter();

onMounted(() => {
  store.fetchRecords();
});

const searchTerm = ref('');
const selectedService = ref('Todos');
const selectedStatus = ref('Todos');
const selectedYear = ref('Todos');
const sortBy = ref('fecha');
const sortOrder = ref('desc');

const filteredRecords = computed(() => {
  return store.records
    .filter(rec => {
      const searchLow = searchTerm.value.toLowerCase();
      const matchesSearch =
        rec.cliente.toLowerCase().includes(searchLow) ||
        rec.vacunador.toLowerCase().includes(searchLow) ||
        rec.direccion.toLowerCase().includes(searchLow);
      
      const matchesService = selectedService.value === 'Todos' || rec.servicio === selectedService.value;
      const matchesStatus = selectedStatus.value === 'Todos' || rec.estado === selectedStatus.value;
      const matchesYear = selectedYear.value === 'Todos' || String(rec.fecha).startsWith(selectedYear.value);

      return matchesSearch && matchesService && matchesStatus && matchesYear;
    })
    .sort((a, b) => {
      let valA = a.fecha;
      let valB = b.fecha;

      if (sortBy.value === 'cantidad') {
        valA = a.cantidad;
        valB = b.cantidad;
      } else if (sortBy.value === 'total') {
        valA = a.total;
        valB = b.total;
      }

      if (valA < valB) return sortOrder.value === 'asc' ? -1 : 1;
      if (valA > valB) return sortOrder.value === 'asc' ? 1 : -1;
      return 0;
    });
});

const totalAvesFiltered = computed(() => filteredRecords.value.reduce((sum, r) => sum + r.cantidad, 0));
const totalFacturacionFiltered = computed(() => filteredRecords.value.reduce((sum, r) => sum + r.total, 0));

const toggleSort = (field) => {
  if (sortBy.value === field) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = field;
    sortOrder.value = 'desc';
  }
};

const handleExportCSV = () => {
  let csvContent = 'data:text/csv;charset=utf-8,';
  csvContent += 'ID,Fecha,Cliente,Servicio,Cantidad,Costo por Ave (Q),Total (Q),Estado,Vacunador,Direccion\n';

  filteredRecords.value.forEach(r => {
    csvContent += `"${r.id}","${r.fecha}","${r.cliente}","${r.servicio}",${r.cantidad},${r.costoPorAve},${r.total},"${r.estado}","${r.vacunador}","${r.direccion}"\n`;
  });

  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', 'Vacunaciones_Historial_Vacunacion.csv');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const navigateToNuevo = () => router.push('/admin/nuevo-registro');

const handleStatusChange = async (id, newStatus) => {
  try {
    await store.handleUpdateStatus(id, newStatus);
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Estado actualizado', showConfirmButton: false, timer: 1500 });
  } catch (e) {
    Swal.fire('Error', 'No se pudo actualizar el estado.', 'error');
  }
};

const handleDeleteClick = async (id, cliente) => {
  const result = await Swal.fire({
    title: '¿Eliminar registro?',
    text: `¿Está seguro de eliminar el registro de ${cliente}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  });
  
  if (result.isConfirmed) {
    try {
      await store.handleDeleteRecord(id);
      Swal.fire('¡Eliminado!', 'El registro ha sido eliminado.', 'success');
    } catch (error) {
      Swal.fire('Error', 'Ocurrió un problema al eliminar el registro.', 'error');
    }
  }
};

// --- Edit Modal Logic ---
const isEditModalOpen = ref(false);
const isSaving = ref(false);
const editFormData = ref({});

const openEditModal = (rec) => {
  editFormData.value = { ...rec }; // Copy data to form
  isEditModalOpen.value = true;
};

const closeEditModal = () => {
  isEditModalOpen.value = false;
  editFormData.value = {};
};

const updateEditTotal = () => {
  const qty = Number(editFormData.value.cantidad) || 0;
  const unitCost = getServiceUnitCost(editFormData.value.servicio, qty);
  editFormData.value.costoPorAve = unitCost;
  editFormData.value.total = qty * unitCost;
};

const updateEditTotalCost = () => {
  const qty = Number(editFormData.value.cantidad) || 0;
  const cost = Number(editFormData.value.costoPorAve) || 0;
  editFormData.value.total = qty * cost;
};

const submitEditForm = async () => {
  isSaving.value = true;
  try {
    await store.handleUpdateRecord(editFormData.value.id, editFormData.value);
    isSaving.value = false;
    closeEditModal();
    Swal.fire('¡Actualizado!', 'El registro se ha modificado correctamente.', 'success');
  } catch (error) {
    isSaving.value = false;
    Swal.fire('Error', 'Ocurrió un problema al editar el registro.', 'error');
  }
};
</script>
