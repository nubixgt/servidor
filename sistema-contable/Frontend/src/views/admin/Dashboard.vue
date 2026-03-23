<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-sans font-bold text-on-surface tracking-tight">Dashboard General</h1>
        <p class="text-sm text-on-surface-variant mt-1">Resumen financiero y estado del portafolio inmobiliario.</p>
      </div>
      <div class="flex items-center gap-3">
        <button 
          @click="navigate('/admin/reports')"
          class="px-4 py-2 bg-[var(--color-surface-container-low)] text-on-surface-variant rounded-xl text-sm font-medium hover:bg-surface-container transition-colors border border-outline-variant/30"
        >
          Descargar Reporte
        </button>
        <button 
          @click="navigate('/admin/new')"
          class="px-4 py-2 bg-[var(--color-primary)] text-white rounded-xl text-sm font-medium hover:bg-[var(--color-primary-container)] transition-colors shadow-sm"
        >
          Nueva Transacción
        </button>
      </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="(kpi, index) in kpis" :key="index" class="glass-card p-5">
        <div class="flex items-center justify-between mb-4">
          <div :class="[
            'w-10 h-10 rounded-xl flex items-center justify-center',
            kpi.title === 'Alertas de Cobro' ? 'bg-[var(--color-error-container)] text-[var(--color-on-error-container)]' : 'bg-[var(--color-primary-fixed)] text-[var(--color-on-primary-fixed)]'
          ]">
            <component :is="kpi.icon" class="w-5 h-5" />
          </div>
          <div :class="[
            'flex items-center text-xs font-semibold px-2 py-1 rounded-full',
            kpi.isPositive ? 'bg-[var(--color-secondary-container)] text-[var(--color-on-secondary-container)]' : 'bg-[var(--color-error-container)] text-[var(--color-on-error-container)]'
          ]">
            <ArrowUpRightIcon v-if="kpi.isPositive" class="w-3 h-3 mr-1" />
            <ArrowDownRightIcon v-else class="w-3 h-3 mr-1" />
            {{ kpi.change }}
          </div>
        </div>
        <h3 class="text-2xl font-bold text-on-surface mb-1">{{ kpi.value }}</h3>
        <p class="text-sm text-on-surface-variant">{{ kpi.title }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Chart Placeholder -->
      <div class="lg:col-span-2 glass-card p-6 flex flex-col">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-bold text-on-surface">Ingresos vs Egresos</h2>
          <select class="bg-surface-container-low border-none text-sm rounded-lg px-3 py-1.5 text-on-surface-variant outline-none">
            <option>Este mes</option>
            <option>Mes anterior</option>
            <option>Este año</option>
          </select>
        </div>
        <div class="flex-1 bg-surface-container-lowest rounded-xl border border-outline-variant/30 flex items-center justify-center min-h-[300px]">
          <p class="text-outline text-sm flex items-center gap-2">
            <ChartBarIcon class="w-5 h-5" />
            Gráfico de tendencias de ingresos y egresos
          </p>
        </div>
      </div>

      <!-- Alerts & Pending -->
      <div class="glass-card p-6 flex flex-col">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-bold text-on-surface flex items-center gap-2">
            <ExclamationCircleIcon class="w-5 h-5 text-error" />
            Alertas y Notificaciones
          </h2>
        </div>
        <div class="flex-1 space-y-4">
          <div v-for="alert in pendingLeases" :key="alert.id" :class="[
            'flex items-start gap-4 p-3 rounded-xl border transition-colors',
            alert.type === 'error' || alert.type === 'warning' ? 'bg-error-container/30 border-[var(--color-error)]/20' : 'bg-surface-container border-outline-variant/30'
          ]">
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between mb-1">
                <p class="text-sm font-medium text-on-surface">{{ alert.message }}</p>
              </div>
            </div>
          </div>
          <button 
            @click="navigate('/admin/locations')"
            class="w-full py-2 mt-2 text-center text-primary text-sm font-medium hover:bg-primary-fixed/50 rounded-lg transition-colors border border-primary/20"
          >
            Ver todas las locaciones
          </button>
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="glass-card overflow-hidden">
      <div class="p-6 border-b border-outline-variant/20 flex items-center justify-between">
        <div>
          <h2 class="text-lg font-bold text-on-surface">Transacciones Recientes</h2>
          <p class="text-xs text-on-surface-variant mt-1">Requieren revisión y aprobación del administrador.</p>
        </div>
        <div class="flex items-center gap-2">
          <button 
            @click="navigate('/admin/reports')"
            class="text-primary text-sm font-medium hover:underline"
          >
            Ver todas
          </button>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-surface-container-low text-on-surface-variant font-medium">
            <tr>
              <th class="px-6 py-4 font-medium">ID</th>
              <th class="px-6 py-4 font-medium">Tipo</th>
              <th class="px-6 py-4 font-medium">Categoría</th>
              <th class="px-6 py-4 font-medium">Locación</th>
              <th class="px-6 py-4 font-medium">Técnico/Usuario</th>
              <th class="px-6 py-4 font-medium">Estado</th>
              <th class="px-6 py-4 font-medium text-right">Acción</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/20">
            <tr v-for="trx in recentTransactions" :key="trx.id" class="hover:bg-surface-container-lowest transition-colors group">
              <td class="px-6 py-4 font-medium text-on-surface font-mono text-xs">{{ trx.id }}</td>
              <td class="px-6 py-4">
                <span :class="[
                  'px-2.5 py-1 rounded-full text-xs font-medium capitalize',
                  trx.type === 'ingreso' ? 'bg-[var(--color-secondary-container)] text-[var(--color-on-secondary-container)]' : 'bg-[var(--color-error-container)] text-[var(--color-on-error-container)]'
                ]">
                  {{ trx.type }}
                </span>
              </td>
              <td class="px-6 py-4 text-on-surface-variant">{{ trx.category }}</td>
              <td class="px-6 py-4 text-on-surface-variant">{{ trx.location_name }}</td>
              <td class="px-6 py-4 text-on-surface-variant">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-full bg-[var(--color-primary-fixed)] text-[var(--color-on-primary-fixed)] flex items-center justify-center text-[10px] font-bold">
                    {{ trx.user_name.charAt(0) }}
                  </div>
                  {{ trx.user_name }}
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="[
                  'px-2.5 py-1 rounded-full text-xs font-medium flex items-center gap-1.5 w-fit',
                  trx.status === 'Aprobado' ? 'bg-[var(--color-secondary-container)] text-[var(--color-on-secondary-container)]' : 
                  trx.status === 'Pendiente' ? 'bg-[var(--color-tertiary-fixed-dim)]/20 text-[var(--color-tertiary)]' : 
                  'bg-[var(--color-error-container)] text-[var(--color-on-error-container)]'
                ]">
                  <CheckCircleIcon v-if="trx.status === 'Aprobado'" class="w-3 h-3" />
                  <ClockIcon v-else-if="trx.status === 'Pendiente'" class="w-3 h-3" />
                  <XCircleIcon v-else-if="trx.status === 'Rechazado'" class="w-3 h-3" />
                  {{ trx.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div v-if="trx.status === 'Pendiente'" class="flex items-center justify-end gap-2">
                  <button class="p-1.5 text-[var(--color-secondary)] hover:bg-[var(--color-secondary-container)] rounded-lg transition-colors" title="Aprobar">
                    <CheckCircleIcon class="w-5 h-5" />
                  </button>
                  <button class="p-1.5 text-[var(--color-error)] hover:bg-[var(--color-error-container)] rounded-lg transition-colors" title="Rechazar">
                    <XCircleIcon class="w-5 h-5" />
                  </button>
                </div>
                <button v-else class="p-2 text-outline hover:text-on-surface hover:bg-[var(--color-surface-container)] rounded-lg transition-colors">
                  <EllipsisVerticalIcon class="w-4 h-4" />
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
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { 
  ArrowTrendingUpIcon, 
  MapPinIcon, 
  ArrowTopRightOnSquareIcon as ArrowUpRightIcon, 
  ArrowDownRightIcon,
  EllipsisVerticalIcon,
  ChartBarIcon,
  BuildingOffice2Icon,
  ExclamationCircleIcon,
  CheckCircleIcon,
  XCircleIcon,
  ClockIcon
} from '@heroicons/vue/24/outline';
import api from '../../services/api';

const router = useRouter();

const navigate = (path) => {
  router.push(path);
};

const kpis = ref([]);
const recentTransactions = ref([]);
const pendingLeases = ref([]);

onMounted(async () => {
  try {
    const res = await api.get('/admin/dashboard');
    const data = res.data.data;

    kpis.value = [
      { title: 'Ingresos Totales', value: `GTQ ${data.kpis.total_ingresos}`, change: '+0%', isPositive: true, icon: ArrowTrendingUpIcon },
      { title: 'Egresos Totales', value: `GTQ ${data.kpis.total_egresos}`, change: '-0%', isPositive: false, icon: BuildingOffice2Icon },
      { title: 'Balance Neto', value: `GTQ ${data.kpis.balance_neto}`, change: '0%', isPositive: true, icon: MapPinIcon },
      { title: 'Alertas', value: data.alerts.length.toString(), change: '+0', isPositive: false, icon: ExclamationCircleIcon },
    ];
    
    recentTransactions.value = data.recentTransactions;
    pendingLeases.value = data.alerts;
  } catch (error) {
    console.error('Error fetching dashboard data:', error);
  }
});
</script>
