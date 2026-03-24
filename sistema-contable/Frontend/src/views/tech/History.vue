<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-sans font-bold text-on-surface tracking-tight">Historial de Movimientos</h1>
        <p class="text-sm text-on-surface-variant mt-1">Revisa y busca entre todas las transacciones que has registrado.</p>
      </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="glass-card p-6 flex items-center justify-between group hover:shadow-lg transition-all duration-300">
        <div>
          <p class="text-on-surface-variant text-sm font-medium mb-1">Ingresos (Mes Actual)</p>
          <div class="flex items-baseline gap-2">
            <span class="text-3xl font-bold font-mono text-[var(--color-secondary)]">GTQ {{ formatNumber(kpis.ingresos_mes) }}</span>
          </div>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-[var(--color-secondary-container)] text-[var(--color-on-secondary-container)] flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
          <ArrowDownTrayIcon class="w-6 h-6" />
        </div>
      </div>

      <div class="glass-card p-6 flex items-center justify-between group hover:shadow-lg transition-all duration-300">
        <div>
          <p class="text-on-surface-variant text-sm font-medium mb-1">Egresos (Mes Actual)</p>
          <div class="flex items-baseline gap-2">
            <span class="text-3xl font-bold font-mono text-[var(--color-error)]">GTQ {{ formatNumber(kpis.egresos_mes) }}</span>
          </div>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-[var(--color-error-container)] text-[var(--color-on-error-container)] flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
          <ArrowUpTrayIcon class="w-6 h-6" />
        </div>
      </div>

      <div class="glass-card p-6 flex items-center justify-between group hover:shadow-lg transition-all duration-300">
        <div>
          <p class="text-on-surface-variant text-sm font-medium mb-1">Total Registros (Mes)</p>
          <div class="flex items-baseline gap-2">
            <span class="text-3xl font-bold font-mono text-on-surface">{{ kpis.total_transacciones }}</span>
          </div>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-[var(--color-primary-container)] text-[var(--color-on-primary-container)] flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
          <DocumentDuplicateIcon class="w-6 h-6" />
        </div>
      </div>
    </div>

    <!-- Filtros y Tabla -->
    <div class="glass-card overflow-hidden">
      <!-- Toolbar -->
      <div class="p-4 border-b border-outline-variant/20 flex flex-col sm:flex-row gap-4 justify-between items-center bg-[var(--color-surface-container-lowest)]">
        <div class="relative w-full sm:w-96">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-outline" />
          <input 
            type="text" 
            placeholder="Buscar por categoría, proveedor o descripción..." 
            v-model="searchTerm"
            class="w-full pl-10 pr-4 py-2 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 transition-all font-sans"
          />
        </div>
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
          <div class="relative min-w-[130px]">
            <select v-model="selectedType" class="w-full appearance-none pl-4 pr-10 py-2 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-sm text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 cursor-pointer">
              <option value="Todos">Tipo: Todos</option>
              <option value="ingreso">Ingresos</option>
              <option value="egreso">Egresos</option>
            </select>
          </div>
          <div class="relative min-w-[130px]">
            <select v-model="selectedStatus" class="w-full appearance-none pl-4 pr-10 py-2 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-sm text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 cursor-pointer">
              <option value="Todos">Estado: Todos</option>
              <option value="Pendiente">Pendiente</option>
              <option value="Aprobado">Aprobado</option>
              <option value="Rechazado">Rechazado</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-[var(--color-surface-container-low)] text-on-surface-variant font-medium">
            <tr>
              <th class="px-6 py-4 font-medium">Fecha</th>
              <th class="px-6 py-4 font-medium">Detalle</th>
              <th class="px-6 py-4 font-medium text-center">Tipo</th>
              <th class="px-6 py-4 font-medium text-right">Monto</th>
              <th class="px-6 py-4 font-medium text-center">Estado</th>
              <th class="px-6 py-4 font-medium text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/20">
            <tr v-for="tx in paginatedTransactions" :key="tx.id" class="hover:bg-[var(--color-surface-container-lowest)] transition-colors group">
              <td class="px-6 py-4 font-mono text-on-surface-variant">{{ tx.transaction_date }}</td>
              <td class="px-6 py-4">
                <div class="font-semibold text-on-surface capitalize">{{ tx.category }}</div>
                <div class="text-xs text-on-surface-variant truncate max-w-[200px]" :title="tx.description">{{ tx.description }}</div>
              </td>
              <td class="px-6 py-4 text-center">
                <span :class="[
                  'px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider',
                  tx.type === 'ingreso' ? 'bg-[var(--color-secondary)]/10 text-[var(--color-secondary)]' : 'bg-[var(--color-error)]/10 text-[var(--color-error)]'
                ]">
                  {{ tx.type }}
                </span>
              </td>
              <td class="px-6 py-4 text-right font-mono font-medium text-on-surface">GTQ {{ formatNumber(tx.amount) }}</td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-center gap-2">
                  <div :class="[
                    'w-2 h-2 rounded-full',
                    tx.status === 'Aprobado' ? 'bg-[var(--color-secondary)]' : (tx.status === 'Rechazado' ? 'bg-[var(--color-error)]' : 'bg-[var(--color-tertiary-fixed-dim)]')
                  ]" />
                  <span class="text-on-surface-variant text-xs">{{ tx.status }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-center">
                  <button @click="openDetails(tx)" class="p-2 text-outline hover:text-[var(--color-primary)] hover:bg-[var(--color-primary-fixed)]/50 rounded-lg transition-colors" title="Ver Detalles">
                    <EyeIcon class="w-5 h-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        
        <div v-if="paginatedTransactions.length === 0" class="p-8 text-center text-on-surface-variant">
          <DocumentTextIcon class="w-12 h-12 mx-auto text-outline-variant mb-3" />
          <p class="font-medium">No hay movimientos en el historial</p>
          <p class="text-sm mt-1">Intenta ajustando los filtros de búsqueda.</p>
        </div>
      </div>
      
      <!-- Pagination -->
      <div class="p-4 border-t border-outline-variant/20 flex items-center justify-between bg-[var(--color-surface-container-lowest)] text-sm text-on-surface-variant">
        <div>Mostrando {{ paginatedTransactions.length }} de {{ filteredTransactions.length }} resultados</div>
        <div class="flex gap-1">
          <button @click="prevPage" :disabled="currentPage === 1" class="px-3 py-1 border border-outline-variant/30 rounded-lg hover:bg-[var(--color-surface-container)] disabled:opacity-50 transition-colors">Anterior</button>
          <button class="px-3 py-1 bg-[var(--color-primary)] text-white rounded-lg cursor-default">{{ currentPage }} de {{ totalPages || 1 }}</button>
          <button @click="nextPage" :disabled="currentPage === totalPages || totalPages === 0" class="px-3 py-1 border border-outline-variant/30 rounded-lg hover:bg-[var(--color-surface-container)] disabled:opacity-50 transition-colors">Siguiente</button>
        </div>
      </div>
    </div>

    <!-- Detalle Modal -->
    <div v-if="selectedTx" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeDetails"></div>
      
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all relative z-10 flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
          <div class="flex items-center gap-3">
            <div :class="[
              'w-10 h-10 rounded-xl flex items-center justify-center shadow-sm',
              selectedTx.type === 'ingreso' ? 'bg-[var(--color-secondary-container)] text-[var(--color-on-secondary-container)]' : 'bg-[var(--color-error-container)] text-[var(--color-on-error-container)]'
            ]">
              <component :is="selectedTx.type === 'ingreso' ? ArrowDownTrayIcon : ArrowUpTrayIcon" class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-lg font-bold text-on-surface capitalize">Detalle de {{ selectedTx.type }}</h3>
              <p class="text-xs text-on-surface-variant font-mono">ID: #{{ selectedTx.id }}</p>
            </div>
          </div>
          <button @click="closeDetails" class="p-2 text-gray-400 hover:text-gray-600 bg-white rounded-full hover:bg-gray-100 transition-colors shadow-sm">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Content -->
        <div class="p-6 space-y-6 overflow-y-auto">
          <!-- Monto Fuerte -->
          <div class="text-center pb-6 border-b border-dashed border-gray-200">
            <div class="text-sm font-medium text-gray-500 mb-1">Total Movimiento</div>
            <div class="text-4xl font-mono font-bold text-gray-900">GTQ {{ formatNumber(selectedTx.amount) }}</div>
          </div>

          <!-- Campos Grid -->
          <div class="grid grid-cols-2 gap-y-4 gap-x-6 text-sm">
            <div>
              <p class="text-gray-500 font-medium mb-1">Fecha</p>
              <p class="font-mono text-gray-900">{{ selectedTx.transaction_date }}</p>
            </div>
            <div>
              <p class="text-gray-500 font-medium mb-1">Estado</p>
              <div class="flex items-center gap-2">
                <div :class="[
                  'w-2 h-2 rounded-full',
                  selectedTx.status === 'Aprobado' ? 'bg-[var(--color-secondary)]' : (selectedTx.status === 'Rechazado' ? 'bg-[var(--color-error)]' : 'bg-[var(--color-tertiary-fixed-dim)]')
                ]" />
                <span class="font-medium text-gray-900">{{ selectedTx.status }}</span>
              </div>
            </div>
            <div>
              <p class="text-gray-500 font-medium mb-1">Categoría</p>
              <p class="font-medium text-gray-900 capitalize">{{ selectedTx.category }}</p>
            </div>
            <div>
              <p class="text-gray-500 font-medium mb-1">Locación Aut.</p>
              <p class="font-medium text-gray-900">{{ selectedTx.location_name || 'N/A' }}</p>
            </div>
            <div v-if="selectedTx.type === 'egreso' && selectedTx.provider" class="col-span-2">
              <p class="text-gray-500 font-medium mb-1">Proveedor / Beneficiario</p>
              <p class="font-medium text-gray-900">{{ selectedTx.provider }}</p>
            </div>
            <div class="col-span-2">
              <p class="text-gray-500 font-medium mb-1">Descripción de Actividad</p>
              <p class="text-gray-800 bg-gray-50 p-3 rounded-lg border border-gray-100">{{ selectedTx.description || 'Sin descripción provista.' }}</p>
            </div>
          </div>

          <!-- Comprobante -->
          <div v-if="selectedTx.receipt_path" class="pt-4 border-t border-gray-100">
            <p class="text-gray-500 font-medium text-sm mb-3">Comprobante Adjunto</p>
            <a :href="'http://localhost:8080' + selectedTx.receipt_path" target="_blank" 
               class="flex items-center justify-between p-3 bg-[var(--color-primary-fixed)]/20 border border-[var(--color-primary)]/30 rounded-xl hover:bg-[var(--color-primary-fixed)]/40 transition-colors group">
              <div class="flex items-center gap-3">
                <div class="p-2 bg-[var(--color-primary)] text-white rounded-lg">
                  <DocumentTextIcon class="w-5 h-5" />
                </div>
                <div>
                  <p class="text-sm font-semibold text-[var(--color-primary)]">Ver Comprobante</p>
                  <p class="text-xs text-[var(--color-primary)]/70">Haz clic para abrir</p>
                </div>
              </div>
              <ArrowTopRightOnSquareIcon class="w-5 h-5 text-[var(--color-primary)] opacity-50 group-hover:opacity-100 transition-opacity" />
            </a>
          </div>
        </div>

        <!-- Footer -->
        <div class="p-4 bg-gray-50 flex justify-end">
          <button @click="closeDetails" class="px-5 py-2 text-sm font-medium bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors text-gray-700 shadow-sm">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { 
  ArrowDownTrayIcon, 
  ArrowUpTrayIcon, 
  MagnifyingGlassIcon, 
  DocumentTextIcon,
  EyeIcon,
  XMarkIcon,
  ArrowTopRightOnSquareIcon,
  DocumentDuplicateIcon
} from '@heroicons/vue/24/outline';
import api from '../../services/api';

const transactions = ref([]);
const kpis = ref({ ingresos_mes: 0, egresos_mes: 0, total_transacciones: 0 });

const searchTerm = ref('');
const selectedType = ref('Todos');
const selectedStatus = ref('Todos');

const selectedTx = ref(null);

const currentPage = ref(1);
const itemsPerPage = 8;

const fetchHistory = async () => {
  try {
    const res = await api.get('/tech/history');
    transactions.value = res.data.data.transactions;
    if (res.data.data.kpis) {
      kpis.value = {
        ingresos_mes: res.data.data.kpis.ingresos_mes || 0,
        egresos_mes: res.data.data.kpis.egresos_mes || 0,
        total_transacciones: res.data.data.kpis.total_transacciones || 0
      };
    }
  } catch (error) {
    console.error('Error fetching history:', error);
  }
};

onMounted(() => {
  fetchHistory();
});

const formatNumber = (num) => {
  return parseFloat(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const openDetails = (tx) => {
  selectedTx.value = tx;
};

const closeDetails = () => {
  selectedTx.value = null;
};

const filteredTransactions = computed(() => {
  return transactions.value.filter(tx => {
    const searchLow = searchTerm.value.toLowerCase();
    const catStr = tx.category ? tx.category.toLowerCase() : '';
    const descStr = tx.description ? tx.description.toLowerCase() : '';
    const provStr = tx.provider ? tx.provider.toLowerCase() : '';
    
    const matchesSearch = catStr.includes(searchLow) || descStr.includes(searchLow) || provStr.includes(searchLow);
    const matchesType = selectedType.value === 'Todos' || tx.type === selectedType.value;
    const matchesStatus = selectedStatus.value === 'Todos' || tx.status === selectedStatus.value;
    
    return matchesSearch && matchesType && matchesStatus;
  });
});

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredTransactions.value.length / itemsPerPage));
});

const paginatedTransactions = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  return filteredTransactions.value.slice(start, end);
});

const prevPage = () => {
  if (currentPage.value > 1) currentPage.value--;
};

const nextPage = () => {
  if (currentPage.value < totalPages.value) currentPage.value++;
};

// Reset pagination when filters change
watch([searchTerm, selectedType, selectedStatus], () => {
  currentPage.value = 1;
});

</script>
