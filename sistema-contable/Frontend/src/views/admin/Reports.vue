<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-sans font-bold text-on-surface tracking-tight">Reportes y Consolidación</h1>
        <p class="text-sm text-on-surface-variant mt-1">Genera reportes financieros por locación o portafolio completo.</p>
      </div>
      <div class="flex items-center gap-3">
        <button class="flex items-center gap-2 px-4 py-2 bg-[var(--color-surface-container-low)] text-on-surface-variant rounded-xl text-sm font-medium hover:bg-[var(--color-surface-container)] transition-colors border border-outline-variant/30">
          <ArrowDownTrayIcon class="w-4 h-4" />
          Exportar Excel
        </button>
        <button class="flex items-center gap-2 px-4 py-2 bg-[var(--color-primary)] text-white rounded-xl text-sm font-medium hover:bg-[var(--color-primary-container)] transition-colors shadow-sm">
          <DocumentTextIcon class="w-4 h-4" />
          Generar PDF
        </button>
      </div>
    </div>

    <!-- Filters Bar -->
    <div class="glass-card p-4 flex flex-wrap gap-4 items-end">
      <div class="flex-1 min-w-[200px] space-y-1.5">
        <label class="text-xs font-semibold text-outline uppercase tracking-wider">Rango de Fechas</label>
        <div class="relative">
          <CalendarIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-outline" />
          <select 
            class="w-full pl-10 pr-4 py-2.5 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 transition-all appearance-none text-on-surface"
            v-model="dateRange"
          >
            <option>Hoy</option>
            <option>Esta Semana</option>
            <option>Este Mes</option>
            <option>Mes Anterior</option>
            <option>Este Año</option>
            <option>Personalizado...</option>
          </select>
        </div>
      </div>

      <div class="flex-1 min-w-[200px] space-y-1.5">
        <label class="text-xs font-semibold text-outline uppercase tracking-wider">Locación</label>
        <div class="relative">
          <MapPinIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-outline" />
          <select class="w-full pl-10 pr-4 py-2.5 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 transition-all appearance-none text-on-surface">
            <option>Todas las locaciones</option>
            <option>Heladerías (Todas)</option>
            <option>Casas (Todas)</option>
            <option>Propiedad Comercial (Todas)</option>
          </select>
        </div>
      </div>

      <div class="flex-1 min-w-[200px] space-y-1.5">
        <label class="text-xs font-semibold text-outline uppercase tracking-wider">Tipo de Transacción</label>
        <div class="relative">
          <FunnelIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-outline" />
          <select class="w-full pl-10 pr-4 py-2.5 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 transition-all appearance-none text-on-surface">
            <option>Todos</option>
            <option>Solo Ingresos</option>
            <option>Solo Egresos</option>
          </select>
        </div>
      </div>

      <button class="px-6 py-2.5 bg-[var(--color-surface-container-high)] text-on-surface rounded-xl text-sm font-medium hover:bg-[var(--color-surface-container-highest)] transition-colors h-[42px]">
        Aplicar Filtros
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="glass-panel p-5 border-l-4 border-l-[var(--color-secondary)]">
        <div class="flex items-center gap-2 text-on-surface-variant mb-2">
          <ArrowTrendingUpIcon class="w-4 h-4 text-[var(--color-secondary)]" />
          <span class="text-sm font-medium">Total Ingresos</span>
        </div>
        <div class="text-2xl font-bold text-on-surface font-mono">GTQ 13,000.00</div>
      </div>
      <div class="glass-panel p-5 border-l-4 border-l-[var(--color-error)]">
        <div class="flex items-center gap-2 text-on-surface-variant mb-2">
          <ArrowTrendingDownIcon class="w-4 h-4 text-[var(--color-error)]" />
          <span class="text-sm font-medium">Total Egresos</span>
        </div>
        <div class="text-2xl font-bold text-on-surface font-mono">GTQ 2,050.00</div>
      </div>
      <div class="glass-panel p-5 border-l-4 border-l-[var(--color-primary)]">
        <div class="flex items-center gap-2 text-on-surface-variant mb-2">
          <DocumentTextIcon class="w-4 h-4 text-[var(--color-primary)]" />
          <span class="text-sm font-medium">Balance Neto</span>
        </div>
        <div class="text-2xl font-bold text-on-surface font-mono">GTQ 10,950.00</div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="glass-card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-[var(--color-surface-container-low)] text-on-surface-variant font-medium">
            <tr>
              <th class="px-6 py-4 font-medium">Fecha</th>
              <th class="px-6 py-4 font-medium">Locación</th>
              <th class="px-6 py-4 font-medium">Categoría</th>
              <th class="px-6 py-4 font-medium">Monto</th>
              <th class="px-6 py-4 font-medium">Estado</th>
              <th class="px-6 py-4 font-medium">Usuario</th>
              <th class="px-6 py-4 font-medium text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/20">
            <tr v-for="report in reports" :key="report.id" class="hover:bg-[var(--color-surface-container-lowest)] transition-colors group">
              <td class="px-6 py-4 text-on-surface-variant whitespace-nowrap">{{ report.date }}</td>
              <td class="px-6 py-4">
                <div class="font-semibold text-on-surface">{{ report.location }}</div>
                <div class="text-xs text-outline font-mono">{{ report.id }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <span :class="[
                    'w-2 h-2 rounded-full',
                    report.type === 'Ingreso' ? 'bg-[var(--color-secondary)]' : 'bg-[var(--color-error)]'
                  ]" />
                  <span class="text-on-surface-variant">{{ report.category }}</span>
                </div>
              </td>
              <td :class="[
                'px-6 py-4 font-mono font-medium',
                report.type === 'Ingreso' ? 'text-[var(--color-secondary)]' : 'text-[var(--color-error)]'
              ]">
                {{ report.type === 'Ingreso' ? '+' : '-' }}{{ report.amount }}
              </td>
              <td class="px-6 py-4">
                <span :class="[
                  'px-2.5 py-1 rounded-full text-xs font-medium flex items-center gap-1.5 w-fit',
                  report.status === 'Aprobado' ? 'bg-[var(--color-secondary-container)] text-[var(--color-on-secondary-container)]' : 
                  report.status === 'Pendiente' ? 'bg-[var(--color-tertiary-fixed-dim)]/20 text-[var(--color-tertiary)]' : 
                  'bg-[var(--color-error-container)] text-[var(--color-on-error-container)]'
                ]">
                  <CheckCircleIcon v-if="report.status === 'Aprobado'" class="w-3 h-3" />
                  <ClockIcon v-else-if="report.status === 'Pendiente'" class="w-3 h-3" />
                  {{ report.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-on-surface-variant">{{ report.user }}</td>
              <td class="px-6 py-4 text-right">
                <button class="p-2 text-outline hover:text-on-surface hover:bg-[var(--color-surface-container)] rounded-lg transition-colors opacity-0 group-hover:opacity-100">
                  <EllipsisVerticalIcon class="w-4 h-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div class="p-4 border-t border-outline-variant/20 flex items-center justify-between bg-[var(--color-surface-container-lowest)] text-sm text-on-surface-variant">
        <div>Mostrando 5 de 128 registros</div>
        <div class="flex gap-1">
          <button class="px-3 py-1 border border-outline-variant/30 rounded-lg hover:bg-[var(--color-surface-container)] disabled:opacity-50">Anterior</button>
          <button class="px-3 py-1 bg-[var(--color-primary)] text-white rounded-lg">1</button>
          <button class="px-3 py-1 border border-outline-variant/30 rounded-lg hover:bg-[var(--color-surface-container)]">2</button>
          <button class="px-3 py-1 border border-outline-variant/30 rounded-lg hover:bg-[var(--color-surface-container)]">3</button>
          <button class="px-3 py-1 border border-outline-variant/30 rounded-lg hover:bg-[var(--color-surface-container)]">Siguiente</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { 
  DocumentTextIcon, 
  ArrowDownTrayIcon, 
  FunnelIcon, 
  CalendarIcon,
  MapPinIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  EllipsisVerticalIcon,
  CheckCircleIcon,
  ClockIcon
} from '@heroicons/vue/24/outline';
import api from '../../services/api';

const dateRange = ref('Este Mes');
const reports = ref([]);

onMounted(async () => {
  try {
    const res = await api.get('/reports');
    reports.value = res.data.data.map(item => ({
      ...item,
      date: item.transaction_date,
      location: item.location_name,
      user: item.user_name,
      amount: `GTQ ${item.amount}`,
      type: item.type.charAt(0).toUpperCase() + item.type.slice(1) // Capitalize
    }));
  } catch(e) {
    console.error(e);
  }
});
</script>
