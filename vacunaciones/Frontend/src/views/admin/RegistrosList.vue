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
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        
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
              <td class="px-6 py-4 text-right">
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
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/appStore';
import { formatCurrency, formatNumber, formatCompactDate, SERVICIOS_PRESTADOS } from '../../utils/data';
import { 
  MagnifyingGlassIcon, 
  ArrowDownTrayIcon, 
  ArrowsUpDownIcon, 
  TrashIcon, 
  PlusIcon 
} from '@heroicons/vue/24/outline';

const store = useAppStore();
const router = useRouter();

const searchTerm = ref('');
const selectedService = ref('Todos');
const selectedStatus = ref('Todos');
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

      return matchesSearch && matchesService && matchesStatus;
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

const handleStatusChange = (id, newStatus) => {
  store.handleUpdateStatus(id, newStatus);
};

const handleDeleteClick = (id, cliente) => {
  if (confirm(`¿Está seguro de eliminar el registro de ${cliente}?`)) {
    store.handleDeleteRecord(id);
  }
};
</script>
