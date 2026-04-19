<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-sans font-bold text-on-surface tracking-tight">Dashboard General</h1>
        <p class="text-sm text-on-surface-variant mt-1">Resumen financiero y estado del portafolio inmobiliario.</p>
      </div>
      <div class="flex items-center gap-3">
        <button @click="navigate('/admin/reports')" class="px-4 py-2 bg-[var(--color-surface-container-low)] text-on-surface-variant rounded-xl text-sm font-medium hover:bg-surface-container transition-colors border border-outline-variant/30">
          Ver Reportes
        </button>
        <button @click="navigate('/admin/new')" class="px-4 py-2 bg-[var(--color-primary)] text-white rounded-xl text-sm font-medium hover:bg-[var(--color-primary-container)] transition-colors shadow-sm">
          Nueva Transacción
        </button>
      </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="(kpi, index) in kpis" :key="index" class="glass-card p-5">
        <div class="flex items-center justify-between mb-4">
          <div :class="['w-10 h-10 rounded-xl flex items-center justify-center', kpi.title === 'Alertas' ? 'bg-[var(--color-error-container)] text-[var(--color-on-error-container)]' : 'bg-[var(--color-primary-fixed)] text-[var(--color-on-primary-fixed)]']">
            <component :is="kpi.icon" class="w-5 h-5" />
          </div>
          <div :class="['flex items-center text-xs font-semibold px-2 py-1 rounded-full', kpi.isPositive ? 'bg-[var(--color-secondary-container)] text-[var(--color-on-secondary-container)]' : 'bg-[var(--color-error-container)] text-[var(--color-on-error-container)]']">
            <ArrowUpRightIcon v-if="kpi.isPositive" class="w-3 h-3 mr-1" />
            <ArrowDownRightIcon v-else class="w-3 h-3 mr-1" />
            {{ kpi.change }}
          </div>
        </div>
        <h3 class="text-2xl font-bold text-on-surface mb-1">{{ kpi.value }}</h3>
        <p class="text-sm text-on-surface-variant">{{ kpi.title }}</p>
      </div>
    </div>

    <!-- Charts and Alerts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Main Chart: Ingresos vs Egresos -->
      <div class="lg:col-span-5 glass-card p-6 flex flex-col">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-bold text-on-surface">Ingresos vs Egresos</h2>
          <span class="text-xs text-on-surface-variant">Últimos 6 meses</span>
        </div>
        <div class="flex-1 min-h-[200px]">
          <canvas ref="chartCanvas"></canvas>
        </div>
      </div>

      <!-- New Chart: Sales by Location -->
      <div class="lg:col-span-4 glass-card p-6 flex flex-col">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-bold text-on-surface">Ventas por Locación</h2>
          <span class="text-xs text-on-surface-variant">Top locales</span>
        </div>
        <div class="flex-1 min-h-[200px]">
          <canvas ref="locationChartCanvas"></canvas>
        </div>
      </div>

      <!-- Alerts Section -->
      <div class="lg:col-span-3 glass-card p-6 flex flex-col bg-[var(--color-surface-container-low)]">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-bold text-on-surface flex items-center gap-2">
            <ExclamationCircleIcon class="w-5 h-5 text-error" /> Alertas
          </h2>
        </div>
        <div class="flex-1 space-y-3 overflow-y-auto max-h-[300px] pr-1">
          <div v-for="alert in pendingLeases" :key="alert.id" 
            :class="['flex items-start gap-3 p-3 rounded-xl border transition-all hover:scale-[1.02]', 
                    alert.type === 'error' ? 'bg-error-container/20 border-error/20' : 
                    alert.type === 'warning' ? 'bg-orange-100/50 border-orange-200' : 
                    'bg-surface-container border-outline-variant/30']">
            <ExclamationTriangleIcon v-if="alert.type === 'error' || alert.type === 'warning'" class="w-4 h-4 mt-0.5 flex-shrink-0 text-error" />
            <p class="text-[13px] font-medium text-on-surface leading-tight">{{ alert.message }}</p>
          </div>
          <div v-if="pendingLeases.length === 0" class="h-full flex flex-col items-center justify-center text-center opacity-40 py-8">
            <CheckCircleIcon class="w-10 h-10 text-secondary mb-2" />
            <p class="text-sm font-medium">Todo bajo control</p>
          </div>
        </div>
        <button @click="navigate('/admin/locations')" class="w-full py-2 mt-4 text-center text-primary text-sm font-medium hover:bg-primary-fixed/50 rounded-lg transition-colors border border-primary/20">
          Gestionar Locaciones
        </button>
      </div>
    </div>

    <!-- Transactions Table -->
    <div class="glass-card overflow-hidden">
      <!-- Table Toolbar -->
      <div class="p-4 border-b border-outline-variant/20 flex flex-col sm:flex-row gap-3 justify-between items-center bg-[var(--color-surface-container-lowest)]">
        <h2 class="text-lg font-bold text-on-surface">Todas las Transacciones</h2>
        <div class="flex flex-wrap gap-2 items-center">
          <!-- Search -->
          <div class="relative">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-outline" />
            <input type="text" v-model="searchTerm" placeholder="Buscar categoría o locación..." class="pl-9 pr-4 py-2 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50" />
          </div>
          <!-- Type filter -->
          <select v-model="filterType" class="px-4 py-2 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-sm appearance-none focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50">
            <option value="">Tipo: Todos</option>
            <option value="ingreso">Ingreso</option>
            <option value="egreso">Egreso</option>
          </select>
          <!-- Type filter -->
          <select v-model="filterType" class="px-4 py-2 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-sm appearance-none focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50">
            <option value="">Tipo: Todos</option>
            <option value="ingreso">Ingreso</option>
            <option value="egreso">Egreso</option>
          </select>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-center text-sm">
          <thead class="bg-[var(--color-surface-container-low)] text-on-surface-variant">
            <tr>
              <th class="px-5 py-4 font-medium text-center">ID</th>
              <th class="px-5 py-4 font-medium text-center">Tipo</th>
              <th class="px-5 py-4 font-medium text-center">Categoría</th>
              <th class="px-5 py-4 font-medium text-center">Locación</th>
              <th class="px-5 py-4 font-medium text-center">Monto</th>
              <th class="px-5 py-4 font-medium text-center">Fecha</th>
              <th class="px-5 py-4 font-medium text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/20">
            <tr v-for="trx in paginatedTransactions" :key="trx.id" class="hover:bg-[var(--color-surface-container-lowest)] transition-colors group">
              <td class="px-5 py-4 font-mono text-xs text-on-surface-variant">#{{ trx.id }}</td>
              <td class="px-5 py-4">
                <span :class="['px-2.5 py-1 rounded-full text-xs font-medium capitalize', trx.type === 'ingreso' ? 'bg-[var(--color-secondary-container)] text-[var(--color-on-secondary-container)]' : 'bg-[var(--color-error-container)] text-[var(--color-on-error-container)]']">
                  {{ trx.type }}
                </span>
              </td>
              <td class="px-5 py-4 text-on-surface-variant">{{ trx.category }}</td>
              <td class="px-5 py-4 text-on-surface-variant">{{ trx.location_name }}</td>
              <td :class="['px-5 py-4 font-mono font-semibold', trx.type === 'ingreso' ? 'text-[var(--color-secondary)]' : 'text-[var(--color-error)]']">
                {{ trx.type === 'ingreso' ? '+' : '-' }}GTQ {{ parseFloat(trx.amount).toLocaleString('es-GT', { minimumFractionDigits: 2 }) }}
              </td>
              <td class="px-5 py-4 text-on-surface-variant text-xs">{{ trx.transaction_date }}</td>
              <td class="px-5 py-4">
                <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <!-- View -->
                  <button @click="viewTransaction(trx)" class="p-1.5 text-outline hover:text-[var(--color-primary)] hover:bg-[var(--color-primary-fixed)]/50 rounded-lg transition-colors" title="Visualizar">
                    <EyeIcon class="w-4 h-4" />
                  </button>
                  <!-- Edit -->
                  <button @click="openEditModal(trx)" class="p-1.5 text-outline hover:text-[var(--color-primary)] hover:bg-[var(--color-primary-fixed)]/50 rounded-lg transition-colors" title="Editar">
                    <PencilIcon class="w-4 h-4" />
                  </button>
                  <!-- Delete -->
                  <button @click="deleteTransaction(trx.id)" class="p-1.5 text-outline hover:text-[var(--color-error)] hover:bg-[var(--color-error-container)]/50 rounded-lg transition-colors" title="Eliminar">
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="paginatedTransactions.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-on-surface-variant">
                <p>No se encontraron transacciones.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="p-4 border-t border-outline-variant/20 flex items-center justify-between bg-[var(--color-surface-container-lowest)] text-sm text-on-surface-variant">
        <div>Mostrando {{ paginatedTransactions.length }} de {{ filteredTransactions.length }} transacciones</div>
        <div class="flex gap-1">
          <button @click="prevPage" :disabled="currentPage === 1" class="px-3 py-1 border border-outline-variant/30 rounded-lg hover:bg-[var(--color-surface-container)] disabled:opacity-50 transition-colors">Anterior</button>
          <button class="px-3 py-1 bg-[var(--color-primary)] text-white rounded-lg cursor-default">{{ currentPage }} de {{ totalPages || 1 }}</button>
          <button @click="nextPage" :disabled="currentPage === totalPages || totalPages === 0" class="px-3 py-1 border border-outline-variant/30 rounded-lg hover:bg-[var(--color-surface-container)] disabled:opacity-50 transition-colors">Siguiente</button>
        </div>
      </div>
    </div>

    <!-- VIEW Modal -->
    <div v-if="viewingTransaction" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="viewingTransaction = null"></div>
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden relative z-10">
        <!-- Header gradient -->
        <div :class="['px-6 py-5 flex justify-between items-start', viewingTransaction.type === 'ingreso' ? 'bg-gradient-to-r from-emerald-600 to-teal-600' : 'bg-gradient-to-r from-red-600 to-rose-600']">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="px-2 py-0.5 bg-white/20 rounded-full text-white text-xs font-medium capitalize">{{ viewingTransaction.type }}</span>
            </div>
            <p class="text-white/80 text-xs">Transacción #{{ viewingTransaction.id }}</p>
            <h3 class="text-2xl font-bold text-white mt-1">{{ viewingTransaction.type === 'ingreso' ? '+' : '-' }}GTQ {{ parseFloat(viewingTransaction.amount).toLocaleString('es-GT', {minimumFractionDigits:2}) }}</h3>
          </div>
          <button @click="viewingTransaction = null" class="text-white/70 hover:text-white p-1"><XMarkIcon class="w-5 h-5" /></button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-4 max-h-[55vh] overflow-y-auto">
          <!-- Receipt image preview -->
          <div v-if="viewingTransaction.receipt_path && isImage(viewingTransaction.receipt_path)" class="group cursor-pointer" @click="lightboxSrc = receiptUrl(viewingTransaction.receipt_path)">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Comprobante</p>
            <div class="relative rounded-xl overflow-hidden border border-gray-100">
              <img :src="receiptUrl(viewingTransaction.receipt_path)" alt="Comprobante" class="w-full max-h-48 object-cover group-hover:scale-105 transition-transform duration-300" />
              <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                <EyeIcon class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity" />
              </div>
            </div>
          </div>
          <div v-else-if="viewingTransaction.receipt_path" class="mb-2">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Comprobante</p>
            <a :href="receiptUrl(viewingTransaction.receipt_path)" target="_blank" class="flex items-center gap-2 text-[var(--color-primary)] hover:underline text-sm">
              <DocumentTextIcon class="w-4 h-4" /> Ver PDF adjunto
            </a>
          </div>

          <!-- Details grid -->
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-gray-50 rounded-xl p-3">
              <p class="text-xs text-gray-400 mb-0.5">Fecha</p>
              <p class="text-sm font-semibold text-gray-800">{{ viewingTransaction.transaction_date }}</p>
            </div>
            <div class="bg-gray-50 rounded-xl p-3">
              <p class="text-xs text-gray-400 mb-0.5">Locación</p>
              <p class="text-sm font-semibold text-gray-800">{{ viewingTransaction.location_name }}</p>
            </div>
            <div class="bg-gray-50 rounded-xl p-3">
              <p class="text-xs text-gray-400 mb-0.5">Categoría</p>
              <p class="text-sm font-semibold text-gray-800">{{ viewingTransaction.category }}</p>
            </div>
            <div class="bg-gray-50 rounded-xl p-3">
              <p class="text-xs text-gray-400 mb-0.5">Registrado por</p>
              <p class="text-sm font-semibold text-gray-800">{{ viewingTransaction.user_name }}</p>
            </div>
            <div v-if="viewingTransaction.provider" class="col-span-2 bg-gray-50 rounded-xl p-3">
              <p class="text-xs text-gray-400 mb-0.5">Proveedor</p>
              <p class="text-sm font-semibold text-gray-800">{{ viewingTransaction.provider }}</p>
            </div>
            <div class="col-span-2 bg-gray-50 rounded-xl p-3">
              <p class="text-xs text-gray-400 mb-0.5">Descripción</p>
              <p class="text-sm text-gray-700">{{ viewingTransaction.description }}</p>
            </div>
          </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end">
          <button @click="viewingTransaction = null" class="px-5 py-2 text-sm font-medium bg-gray-100 hover:bg-gray-200 rounded-xl text-gray-700 transition-colors">Cerrar</button>
        </div>
      </div>
    </div>

    <!-- LIGHTBOX -->
    <div v-if="lightboxSrc" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/90" @click="lightboxSrc = null">
      <button class="absolute top-4 right-4 text-white/70 hover:text-white" @click.stop="lightboxSrc = null">
        <XMarkIcon class="w-8 h-8" />
      </button>
      <img :src="lightboxSrc" class="max-w-[90vw] max-h-[90vh] object-contain rounded-lg shadow-2xl" @click.stop />
      <p class="absolute bottom-4 text-white/50 text-sm">Haz clic fuera de la imagen para cerrar</p>
    </div>

    <!-- EDIT Modal -->
    <div v-if="editingTransaction" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="editingTransaction = null"></div>
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden relative z-10">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
          <h3 class="text-lg font-bold text-[#221f47]">Editar Transacción #{{ editForm.id }}</h3>
          <button @click="editingTransaction = null" class="text-gray-400 hover:text-gray-600"><XMarkIcon class="w-5 h-5" /></button>
        </div>
        <form @submit.prevent="saveEdit">
          <div class="p-6 space-y-4 max-h-[65vh] overflow-y-auto">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                <select v-model="editForm.type" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50">
                  <option value="ingreso">Ingreso</option>
                  <option value="egreso">Egreso</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Monto (GTQ)</label>
                <input v-model="editForm.amount" type="number" step="0.01" required class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                <input v-model="editForm.transaction_date" type="date" required class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Locación</label>
                <select v-model="editForm.location_id" required class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50">
                  <option v-for="loc in locations" :key="loc.id" :value="loc.id">{{ loc.name || loc.code }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                <input v-model="editForm.category" type="text" required class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50" />
              </div>
            </div>
            <div v-if="editForm.type === 'egreso'">
              <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
              <input v-model="editForm.provider" type="text" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
              <textarea v-model="editForm.description" rows="2" required class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 resize-none"></textarea>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Comprobante <span class="text-gray-400">(opcional, reemplaza el actual)</span></label>
              <input type="file" accept="image/*,.pdf" @change="e => editReceiptFile = e.target.files[0]" class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200" />
            </div>
          </div>
          <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
            <button type="button" @click="editingTransaction = null" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors">Cancelar</button>
            <button type="submit" :disabled="saving" class="px-6 py-2 bg-[var(--color-primary)] text-white text-sm font-medium rounded-xl shadow-sm hover:shadow transition-all disabled:opacity-50 flex items-center gap-2">
              <div v-if="saving" class="w-4 h-4 border-2 border-white/20 border-t-white rounded-full animate-spin"></div>
              {{ saving ? 'Guardando...' : 'Guardar Cambios' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';
import { Chart, registerables } from 'chart.js';
import {
  ArrowTrendingUpIcon, MapPinIcon,
  ArrowTopRightOnSquareIcon as ArrowUpRightIcon, ArrowDownRightIcon,
  ChartBarIcon, BuildingOffice2Icon, ExclamationCircleIcon, ExclamationTriangleIcon,
  CheckCircleIcon, XCircleIcon, ClockIcon,
  EyeIcon, PencilIcon, TrashIcon,
  MagnifyingGlassIcon, XMarkIcon, DocumentTextIcon
} from '@heroicons/vue/24/outline';
import api from '../../services/api';

Chart.register(...registerables);

const router = useRouter();
const navigate = (path) => router.push(path);

const kpis = ref([]);
const pendingLeases = ref([]);
const allTransactions = ref([]);
const locations = ref([]);
const chartCanvas = ref(null);
const locationChartCanvas = ref(null);
let chartInstance = null;
let locationChartInstance = null;

// Filters & pagination
const searchTerm = ref('');
const filterType = ref('');
const filterStatus = ref('');
const currentPage = ref(1);
const itemsPerPage = 10;

// Modals
const viewingTransaction = ref(null);
const lightboxSrc = ref(null);
const editingTransaction = ref(null);
const editForm = ref({});
const editReceiptFile = ref(null);
const saving = ref(false);

// Load data
onMounted(async () => {
  try {
    const [dashRes, trxRes, locsRes] = await Promise.all([
      api.get('/admin/dashboard'),
      api.get('/transactions'),
      api.get('/locations')
    ]);

    const d = dashRes.data.data;

    // Calculate month-over-month change from monthlyData
    const monthly = d.monthlyData ?? [];
    const calcPct = (curr, prev) => {
      if (!prev || prev === 0) return curr > 0 ? '+100%' : '0%';
      const pct = ((curr - prev) / prev * 100).toFixed(1);
      return (pct >= 0 ? '+' : '') + pct + '%';
    };

    const currMonth = monthly.length > 0 ? monthly[monthly.length - 1] : null;
    const prevMonth = monthly.length > 1 ? monthly[monthly.length - 2] : null;

    const currIng = parseFloat(currMonth?.ingresos ?? 0);
    const prevIng = parseFloat(prevMonth?.ingresos ?? 0);
    const currEgr = parseFloat(currMonth?.egresos ?? 0);
    const prevEgr = parseFloat(prevMonth?.egresos ?? 0);
    const currBal = currIng - currEgr;
    const prevBal = prevIng - prevEgr;

    const ingPct  = calcPct(currIng, prevIng);
    const egrPct  = calcPct(currEgr, prevEgr);
    const balPct  = calcPct(currBal, prevBal);

    kpis.value = [
      { title: 'Ingresos Totales', value: `GTQ ${parseFloat(d.kpis.total_ingresos).toLocaleString('es-GT', {minimumFractionDigits:2})}`, change: ingPct, isPositive: !ingPct.startsWith('-'), icon: ArrowTrendingUpIcon },
      { title: 'Egresos Totales', value: `GTQ ${parseFloat(d.kpis.total_egresos).toLocaleString('es-GT', {minimumFractionDigits:2})}`, change: egrPct, isPositive: egrPct.startsWith('-'), icon: BuildingOffice2Icon },
      { title: 'Balance Neto', value: `GTQ ${parseFloat(d.kpis.balance_neto).toLocaleString('es-GT', {minimumFractionDigits:2})}`, change: balPct, isPositive: !balPct.startsWith('-'), icon: MapPinIcon },
      { title: 'Alertas', value: String(d.alerts.length), change: `${d.alerts.length} activas`, isPositive: d.alerts.length === 0, icon: ExclamationCircleIcon },
    ];

    pendingLeases.value = d.alerts;
    allTransactions.value = trxRes.data.data;
    locations.value = locsRes.data.data;

    // Build chart
    await nextTick();
    if (chartCanvas.value && d.monthlyData?.length) {
      const labels = d.monthlyData.map(m => {
        const [y, mo] = m.month.split('-');
        return new Date(y, mo - 1).toLocaleDateString('es-GT', { month: 'short', year: '2-digit' });
      });
      chartInstance = new Chart(chartCanvas.value, {
        type: 'bar',
        data: {
          labels,
          datasets: [
            {
              label: 'Ingresos',
              data: d.monthlyData.map(m => parseFloat(m.ingresos)),
              backgroundColor: 'rgba(34,197,94,0.7)',
              borderColor: 'rgba(34,197,94,1)',
              borderWidth: 1,
              borderRadius: 6
            },
            {
              label: 'Egresos',
              data: d.monthlyData.map(m => parseFloat(m.egresos)),
              backgroundColor: 'rgba(239,68,68,0.7)',
              borderColor: 'rgba(239,68,68,1)',
              borderWidth: 1,
              borderRadius: 6
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: { legend: { position: 'top' } },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { callback: v => `Q${v.toLocaleString()}` }
            }
          }
        }
      });
    }

    // Sales by Location Chart
    if (locationChartCanvas.value && d.salesByLocation?.length) {
      locationChartInstance = new Chart(locationChartCanvas.value, {
        type: 'doughnut',
        data: {
          labels: d.salesByLocation.map(s => s.name),
          datasets: [{
            data: d.salesByLocation.map(s => parseFloat(s.total)),
            backgroundColor: [
              'rgba(34,197,94,0.7)',
              'rgba(59,130,246,0.7)',
              'rgba(168,85,247,0.7)',
              'rgba(249,115,22,0.7)',
              'rgba(236,72,153,0.7)'
            ],
            borderWidth: 0
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { 
              position: 'bottom',
              labels: { boxWidth: 10, padding: 10, font: { size: 11 } }
            }
          },
          cutout: '70%'
        }
      });
    }
  } catch (err) {
    console.error(err);
  }
});

// Filtered
const filteredTransactions = computed(() => {
  const q = searchTerm.value.toLowerCase();
  return allTransactions.value.filter(t => {
    if (filterType.value && t.type !== filterType.value) return false;
    if (filterStatus.value && t.status !== filterStatus.value) return false;
    if (q && !t.category?.toLowerCase().includes(q) && !t.location_name?.toLowerCase().includes(q)) return false;
    return true;
  });
});

// Pagination
const totalPages = computed(() => Math.max(1, Math.ceil(filteredTransactions.value.length / itemsPerPage)));
const paginatedTransactions = computed(() => filteredTransactions.value.slice((currentPage.value - 1) * itemsPerPage, currentPage.value * itemsPerPage));
const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };
watch([searchTerm, filterType, filterStatus], () => { currentPage.value = 1; });

// View
const viewTransaction = (trx) => { viewingTransaction.value = trx; };
const receiptUrl = (path) => `${api.defaults.baseURL.replace('/api/v1', '')}${path}`;
const isImage = (path) => /\.(jpg|jpeg|png|gif|webp)$/i.test(path);

// Edit
const openEditModal = (trx) => {
  editForm.value = { ...trx };
  editReceiptFile.value = null;
  editingTransaction.value = trx;
};

const saveEdit = async () => {
  saving.value = true;
  try {
    let payload;
    let headers = {};

    if (editReceiptFile.value) {
      payload = new FormData();
      Object.entries(editForm.value).forEach(([k, v]) => { if (v !== null && v !== undefined) payload.append(k, v); });
      payload.append('receipt', editReceiptFile.value);
      headers = { 'Content-Type': 'multipart/form-data' };
    } else {
      payload = { ...editForm.value };
    }

    await api.put(`/transactions/${editForm.value.id}`, payload, { headers });

    const idx = allTransactions.value.findIndex(t => t.id === editForm.value.id);
    if (idx !== -1) allTransactions.value[idx] = { ...allTransactions.value[idx], ...editForm.value };

    editingTransaction.value = null;
    Swal.fire({ icon: 'success', title: '¡Actualizado!', text: 'La transacción fue actualizada correctamente.', confirmButtonColor: 'var(--color-primary)' });
  } catch (err) {
    Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.error || 'No se pudo guardar.' });
  } finally {
    saving.value = false;
  }
};

// Approve / Reject
const changeStatus = async (id, status) => {
  try {
    await api.put(`/transactions/${id}`, { status });
    const idx = allTransactions.value.findIndex(t => t.id === id);
    if (idx !== -1) allTransactions.value[idx].status = status;
  } catch (err) {
    Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.error || 'No se pudo actualizar el estado.' });
  }
};

// Delete
const deleteTransaction = async (id) => {
  const result = await Swal.fire({
    title: '¿Eliminar transacción?',
    text: 'Esta acción no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: 'var(--color-error)',
  });
  if (!result.isConfirmed) return;
  try {
    await api.delete(`/transactions/${id}`);
    allTransactions.value = allTransactions.value.filter(t => t.id !== id);
    Swal.fire({ icon: 'success', title: 'Eliminada', text: 'La transacción fue eliminada.', confirmButtonColor: 'var(--color-primary)' });
  } catch (err) {
    Swal.fire({ icon: 'error', title: 'Error', text: err.response?.data?.error || 'No se pudo eliminar.' });
  }
};
</script>
