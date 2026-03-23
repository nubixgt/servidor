<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-sans font-bold text-on-surface tracking-tight">Reportes y Consolidación</h1>
        <p class="text-sm text-on-surface-variant mt-1">Genera reportes financieros por locación o portafolio completo.</p>
      </div>
      <div class="flex items-center gap-3">
        <button @click="exportCSV" class="flex items-center gap-2 px-4 py-2 bg-[var(--color-surface-container-low)] text-on-surface-variant rounded-xl text-sm font-medium hover:bg-[var(--color-surface-container)] transition-colors border border-outline-variant/30">
          <ArrowDownTrayIcon class="w-4 h-4" />
          Exportar Excel
        </button>
        <button @click="exportPDF" class="flex items-center gap-2 px-4 py-2 bg-[var(--color-primary)] text-white rounded-xl text-sm font-medium hover:bg-[var(--color-primary-container)] transition-colors shadow-sm">
          <DocumentTextIcon class="w-4 h-4" />
          Generar PDF
        </button>
      </div>
    </div>

    <!-- Filters Bar -->
    <div class="glass-card p-4 flex flex-wrap gap-4 items-end">
      <!-- Date Range -->
      <div class="flex-1 min-w-[180px] space-y-1.5">
        <label class="text-xs font-semibold text-outline uppercase tracking-wider">Rango de Fechas</label>
        <div class="relative">
          <CalendarIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-outline" />
          <select
            v-model="dateRange"
            class="w-full pl-10 pr-4 py-2.5 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 transition-all appearance-none text-on-surface"
          >
            <option value="hoy">Hoy</option>
            <option value="semana">Esta Semana</option>
            <option value="mes">Este Mes</option>
            <option value="mes_anterior">Mes Anterior</option>
            <option value="año">Este Año</option>
            <option value="todos">Todos</option>
          </select>
        </div>
      </div>

      <!-- Location -->
      <div class="flex-1 min-w-[180px] space-y-1.5">
        <label class="text-xs font-semibold text-outline uppercase tracking-wider">Locación</label>
        <div class="relative">
          <MapPinIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-outline" />
          <select
            v-model="selectedLocation"
            class="w-full pl-10 pr-4 py-2.5 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 transition-all appearance-none text-on-surface"
          >
            <option value="">Todas las locaciones</option>
            <option v-for="loc in locations" :key="loc.id" :value="loc.id">
              {{ loc.name || loc.code }}
            </option>
          </select>
        </div>
      </div>

      <!-- Transaction Type -->
      <div class="flex-1 min-w-[180px] space-y-1.5">
        <label class="text-xs font-semibold text-outline uppercase tracking-wider">Tipo de Transacción</label>
        <div class="relative">
          <FunnelIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-outline" />
          <select
            v-model="selectedType"
            class="w-full pl-10 pr-4 py-2.5 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 transition-all appearance-none text-on-surface"
          >
            <option value="">Todos</option>
            <option value="ingreso">Solo Ingresos</option>
            <option value="egreso">Solo Egresos</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Summary Cards (dynamic) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="glass-panel p-5 border-l-4 border-l-[var(--color-secondary)]">
        <div class="flex items-center gap-2 text-on-surface-variant mb-2">
          <ArrowTrendingUpIcon class="w-4 h-4 text-[var(--color-secondary)]" />
          <span class="text-sm font-medium">Total Ingresos</span>
        </div>
        <div class="text-2xl font-bold text-on-surface font-mono">GTQ {{ totalIngresos }}</div>
      </div>
      <div class="glass-panel p-5 border-l-4 border-l-[var(--color-error)]">
        <div class="flex items-center gap-2 text-on-surface-variant mb-2">
          <ArrowTrendingDownIcon class="w-4 h-4 text-[var(--color-error)]" />
          <span class="text-sm font-medium">Total Egresos</span>
        </div>
        <div class="text-2xl font-bold text-on-surface font-mono">GTQ {{ totalEgresos }}</div>
      </div>
      <div class="glass-panel p-5 border-l-4 border-l-[var(--color-primary)]">
        <div class="flex items-center gap-2 text-on-surface-variant mb-2">
          <DocumentTextIcon class="w-4 h-4 text-[var(--color-primary)]" />
          <span class="text-sm font-medium">Balance Neto</span>
        </div>
        <div class="text-2xl font-bold text-on-surface font-mono">GTQ {{ balanceNeto }}</div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="glass-card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-center text-sm">
          <thead class="bg-[var(--color-surface-container-low)] text-on-surface-variant font-medium">
            <tr>
              <th class="px-6 py-4 font-medium text-center">Fecha</th>
              <th class="px-6 py-4 font-medium text-center">Locación</th>
              <th class="px-6 py-4 font-medium text-center">Categoría</th>
              <th class="px-6 py-4 font-medium text-center">Monto</th>
              <th class="px-6 py-4 font-medium text-center">Estado</th>
              <th class="px-6 py-4 font-medium text-center">Usuario</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/20">
            <tr v-for="report in paginatedReports" :key="report.id" class="hover:bg-[var(--color-surface-container-lowest)] transition-colors">
              <td class="px-6 py-4 text-on-surface-variant whitespace-nowrap">{{ report.date }}</td>
              <td class="px-6 py-4 font-semibold text-on-surface">{{ report.location }}</td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-center gap-2">
                  <span :class="['w-2 h-2 rounded-full flex-shrink-0', report.type === 'Ingreso' ? 'bg-[var(--color-secondary)]' : 'bg-[var(--color-error)]']" />
                  <span class="text-on-surface-variant">{{ report.category }}</span>
                </div>
              </td>
              <td :class="['px-6 py-4 font-mono font-semibold', report.type === 'Ingreso' ? 'text-[var(--color-secondary)]' : 'text-[var(--color-error)]']">
                {{ report.type === 'Ingreso' ? '+' : '-' }}{{ report.amount }}
              </td>
              <td class="px-6 py-4">
                <span :class="[
                  'px-2.5 py-1 rounded-full text-xs font-medium inline-flex items-center gap-1.5',
                  report.status === 'Aprobado' ? 'bg-[var(--color-secondary-container)] text-[var(--color-on-secondary-container)]' :
                  report.status === 'Pendiente' ? 'bg-yellow-100 text-yellow-700' :
                  'bg-[var(--color-error-container)] text-[var(--color-on-error-container)]'
                ]">
                  <CheckCircleIcon v-if="report.status === 'Aprobado'" class="w-3 h-3" />
                  <ClockIcon v-else-if="report.status === 'Pendiente'" class="w-3 h-3" />
                  {{ report.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-on-surface-variant">{{ report.user }}</td>
            </tr>
            <tr v-if="paginatedReports.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-on-surface-variant">
                <DocumentTextIcon class="w-10 h-10 mx-auto text-outline-variant mb-2" />
                <p>No se encontraron transacciones con los filtros aplicados.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="p-4 border-t border-outline-variant/20 flex items-center justify-between bg-[var(--color-surface-container-lowest)] text-sm text-on-surface-variant">
        <div>Mostrando {{ paginatedReports.length }} de {{ filteredReports.length }} registros</div>
        <div class="flex gap-1">
          <button @click="prevPage" :disabled="currentPage === 1" class="px-3 py-1 border border-outline-variant/30 rounded-lg hover:bg-[var(--color-surface-container)] disabled:opacity-50 transition-colors">Anterior</button>
          <button class="px-3 py-1 bg-[var(--color-primary)] text-white rounded-lg cursor-default">{{ currentPage }} de {{ totalPages || 1 }}</button>
          <button @click="nextPage" :disabled="currentPage === totalPages || totalPages === 0" class="px-3 py-1 border border-outline-variant/30 rounded-lg hover:bg-[var(--color-surface-container)] disabled:opacity-50 transition-colors">Siguiente</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import {
  DocumentTextIcon,
  ArrowDownTrayIcon,
  FunnelIcon,
  CalendarIcon,
  MapPinIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  CheckCircleIcon,
  ClockIcon
} from '@heroicons/vue/24/outline';
import api from '../../services/api';

const dateRange = ref('todos');
const selectedLocation = ref('');
const selectedType = ref('');
const allReports = ref([]);
const locations = ref([]);
const currentPage = ref(1);
const itemsPerPage = 10;

onMounted(async () => {
  try {
    const [reportsRes, locsRes] = await Promise.all([
      api.get('/reports'),
      api.get('/locations')
    ]);

    allReports.value = reportsRes.data.data.map(item => ({
      ...item,
      date: item.transaction_date,
      location: item.location_name,
      user: item.user_name,
      amount: parseFloat(item.amount).toLocaleString('es-GT', { minimumFractionDigits: 2 }),
      type: item.type.charAt(0).toUpperCase() + item.type.slice(1)
    }));

    locations.value = locsRes.data.data;
  } catch (e) {
    console.error(e);
  }
});

const filteredReports = computed(() => {
  const now = new Date();
  const todayStr = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');

  return allReports.value.filter(r => {
    const txDateStr = r.date; // Already a string like "2026-03-23"

    if (dateRange.value === 'hoy') {
      if (txDateStr !== todayStr) return false;
    } else if (dateRange.value === 'semana') {
      const startOfWeek = new Date(now);
      startOfWeek.setDate(now.getDate() - now.getDay());
      startOfWeek.setHours(0, 0, 0, 0);
      if (new Date(txDateStr + 'T12:00:00') < startOfWeek) return false;
    } else if (dateRange.value === 'mes') {
      if (!txDateStr.startsWith(todayStr.substring(0, 7))) return false;
    } else if (dateRange.value === 'mes_anterior') {
      const prevDate = new Date(now.getFullYear(), now.getMonth() - 1, 1);
      const prevStr = prevDate.getFullYear() + '-' + String(prevDate.getMonth() + 1).padStart(2, '0');
      if (!txDateStr.startsWith(prevStr)) return false;
    } else if (dateRange.value === 'año') {
      if (!txDateStr.startsWith(String(now.getFullYear()))) return false;
    }

    if (selectedLocation.value && String(r.location_id) !== String(selectedLocation.value)) return false;
    if (selectedType.value && r.type.toLowerCase() !== selectedType.value) return false;

    return true;
  });
});

// KPI cards computed from filtered data
const totalIngresos = computed(() => {
  const sum = filteredReports.value
    .filter(r => r.type === 'Ingreso')
    .reduce((acc, r) => acc + parseFloat(r.amount.replace(/,/g, '')), 0);
  return sum.toLocaleString('es-GT', { minimumFractionDigits: 2 });
});

const totalEgresos = computed(() => {
  const sum = filteredReports.value
    .filter(r => r.type === 'Egreso')
    .reduce((acc, r) => acc + parseFloat(r.amount.replace(/,/g, '')), 0);
  return sum.toLocaleString('es-GT', { minimumFractionDigits: 2 });
});

const balanceNeto = computed(() => {
  const ing = filteredReports.value.filter(r => r.type === 'Ingreso').reduce((acc, r) => acc + parseFloat(r.amount.replace(/,/g, '')), 0);
  const egr = filteredReports.value.filter(r => r.type === 'Egreso').reduce((acc, r) => acc + parseFloat(r.amount.replace(/,/g, '')), 0);
  return (ing - egr).toLocaleString('es-GT', { minimumFractionDigits: 2 });
});

// Pagination
const totalPages = computed(() => Math.max(1, Math.ceil(filteredReports.value.length / itemsPerPage)));

const paginatedReports = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredReports.value.slice(start, start + itemsPerPage);
});

const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };

// Reset pagination when filters change
watch([dateRange, selectedLocation, selectedType], () => { currentPage.value = 1; });

// --- Export Functions ---
const exportCSV = () => {
  const headers = ['Fecha', 'Locación', 'Categoría', 'Tipo', 'Monto (GTQ)', 'Estado', 'Usuario'];
  const rows = filteredReports.value.map(r => [
    r.date, r.location, r.category, r.type, r.amount.replace(/,/g, ''), r.status, r.user
  ]);
  // Semicolon separator works with Spanish-locale Excel installations
  const content = [headers, ...rows].map(row => row.map(v => `"${v ?? ''}"`).join(';')).join('\n');
  const blob = new Blob(['\uFEFF' + content], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `reporte_transacciones_${new Date().toISOString().split('T')[0]}.csv`;
  link.click();
};

const exportPDF = async () => {
  const { default: jsPDF } = await import('jspdf');
  const { default: autoTable } = await import('jspdf-autotable');

  const doc = new jsPDF({ orientation: 'landscape' });

  doc.setFontSize(16);
  doc.setTextColor(34, 31, 71);
  doc.text('Reporte de Transacciones', 14, 16);

  doc.setFontSize(9);
  doc.setTextColor(120, 120, 120);
  doc.text(`Generado el ${new Date().toLocaleDateString('es-GT')}`, 14, 23);

  autoTable(doc, {
    startY: 28,
    head: [['Fecha', 'Locación', 'Categoría', 'Tipo', 'Monto (GTQ)', 'Estado', 'Usuario']],
    body: filteredReports.value.map(r => [
      r.date, r.location, r.category, r.type,
      `${r.type === 'Ingreso' ? '+' : '-'}${r.amount}`,
      r.status, r.user
    ]),
    styles: { fontSize: 9, cellPadding: 3 },
    headStyles: { fillColor: [34, 31, 71], textColor: 255, fontStyle: 'bold' },
    alternateRowStyles: { fillColor: [245, 245, 250] },
    didDrawCell: (data) => {
      if (data.section === 'body' && data.column.index === 4) {
        const text = data.cell.text[0] || '';
        doc.setTextColor(text.startsWith('+') ? 22 : 220, text.startsWith('+') ? 163 : 38, text.startsWith('+') ? 74 : 38);
      }
    }
  });

  doc.save(`reporte_transacciones_${new Date().toISOString().split('T')[0]}.pdf`);
};
</script>
