<template>
  <div class="space-y-8 max-w-4xl mx-auto">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
      <div class="text-center sm:text-left">
        <h1 class="text-3xl font-sans font-bold text-on-surface tracking-tight">Panel de Control</h1>
        <p class="text-base text-on-surface-variant mt-2">¿Qué acción deseas realizar hoy?</p>
      </div>
      <button 
        @click="handleLogout"
        class="flex items-center gap-2 px-6 py-2.5 bg-error-container text-[var(--color-error)] font-semibold rounded-2xl hover:bg-[var(--color-error)] hover:text-white transition-all duration-300 shadow-sm border border-[var(--color-error)]/20"
      >
        <ArrowRightOnRectangleIcon class="w-5 h-5" />
        Cerrar Sesión
      </button>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
      <button 
        @click="navigate('/tech/new-ingreso')"
        class="group relative overflow-hidden rounded-3xl bg-white border border-outline-variant/30 shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 text-left p-8"
      >
        <div class="absolute top-0 right-0 w-32 h-32 bg-[var(--color-secondary-container)] rounded-bl-full opacity-20 group-hover:opacity-40 transition-opacity"></div>
        <div class="relative z-10">
          <div class="w-16 h-16 bg-[var(--color-secondary-container)] text-[var(--color-on-secondary-container)] rounded-2xl flex items-center justify-center mb-6 shadow-sm group-hover:scale-110 transition-transform">
            <ArrowDownTrayIcon class="w-8 h-8" />
          </div>
          <h2 class="text-2xl font-bold text-on-surface mb-2">Registrar Ingreso</h2>
          <p class="text-on-surface-variant mb-6">Añade nuevos fondos o ingresos al sistema.</p>
          <div class="flex items-center text-[var(--color-secondary)] font-semibold group-hover:gap-2 transition-all">
            Comenzar <ChevronRightIcon class="w-5 h-5 ml-1" />
          </div>
        </div>
      </button>

      <button 
        @click="navigate('/tech/new-egreso')"
        class="group relative overflow-hidden rounded-3xl bg-white border border-outline-variant/30 shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 text-left p-8"
      >
        <div class="absolute top-0 right-0 w-32 h-32 bg-[var(--color-error-container)] rounded-bl-full opacity-20 group-hover:opacity-40 transition-opacity"></div>
        <div class="relative z-10">
          <div class="w-16 h-16 bg-[var(--color-error-container)] text-[var(--color-on-error-container)] rounded-2xl flex items-center justify-center mb-6 shadow-sm group-hover:scale-110 transition-transform">
            <ArrowUpTrayIcon class="w-8 h-8" />
          </div>
          <h2 class="text-2xl font-bold text-on-surface mb-2">Registrar Egreso</h2>
          <p class="text-on-surface-variant mb-6">Registra gastos, pagos u otras salidas de dinero.</p>
          <div class="flex items-center text-[var(--color-error)] font-semibold group-hover:gap-2 transition-all">
            Comenzar <ChevronRightIcon class="w-5 h-5 ml-1" />
          </div>
        </div>
      </button>
    </div>

    <!-- Recent Activity -->
    <div class="glass-card overflow-hidden mt-8">
      <div class="p-6 border-b border-outline-variant/20 flex items-center justify-between bg-[var(--color-surface-container-lowest)]">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-[var(--color-primary-fixed)] text-[var(--color-on-primary-fixed)] rounded-xl flex items-center justify-center">
            <ClockIcon class="w-5 h-5" />
          </div>
          <div>
            <h2 class="text-lg font-bold text-on-surface">Mi Actividad de Hoy</h2>
            <p class="text-xs text-on-surface-variant">Movimientos registrados solo hoy</p>
          </div>
        </div>
        <button 
          @click="navigate('/tech/history')"
          class="text-primary text-sm font-medium hover:underline hidden sm:block"
        >
          Ver historial de hoy
        </button>
      </div>
      
      <div class="divide-y divide-outline-variant/20">
        <div v-for="activity in recentActivity" :key="activity.id" class="p-4 sm:p-6 hover:bg-surface-container-lowest transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-start sm:items-center gap-4">
            <div :class="[
              'w-12 h-12 rounded-full flex items-center justify-center shrink-0 shadow-sm',
              activity.type === 'ingreso' ? 'bg-[var(--color-secondary-container)] text-[var(--color-on-secondary-container)]' : 'bg-[var(--color-error-container)] text-[var(--color-on-error-container)]'
            ]">
              <component :is="activity.type === 'ingreso' ? ArrowDownTrayIcon : ArrowUpTrayIcon" class="w-5 h-5" />
            </div>
            <div>
              <div class="flex items-center gap-2 mb-1">
                <span class="font-bold text-on-surface text-lg">{{ activity.asset }}</span>
                <span :class="[
                  'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider',
                  activity.type === 'ingreso' ? 'bg-[var(--color-secondary)]/10 text-[var(--color-secondary)]' : 'bg-[var(--color-error)]/10 text-[var(--color-error)]'
                ]">
                  {{ activity.type }}
                </span>
              </div>
              <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-on-surface-variant">
                <span class="flex items-center gap-1"><MapPinIcon class="w-4 h-4 text-outline" /> {{ activity.location }}</span>
                <span class="flex items-center gap-1"><ClockIcon class="w-4 h-4 text-outline" /> {{ activity.time }}</span>
                <span class="text-outline font-mono text-xs">ID: {{ activity.id }}</span>
              </div>
            </div>
          </div>
          
          <div class="flex items-center justify-between sm:justify-end w-full sm:w-auto mt-2 sm:mt-0 pl-16 sm:pl-0">
            <button class="sm:hidden text-primary text-sm font-medium">Ver detalles</button>
          </div>
        </div>
      </div>
      
      <div class="p-4 bg-[var(--color-surface-container-lowest)] border-t border-outline-variant/20 sm:hidden">
        <button 
          @click="navigate('/tech/history')"
          class="w-full py-2 text-center text-[var(--color-primary)] text-sm font-medium hover:bg-primary-fixed/50 rounded-lg transition-colors"
        >
          Ver historial de hoy
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { 
  ArrowDownTrayIcon, 
  ArrowUpTrayIcon, 
  ClockIcon, 
  CubeIcon, 
  MapPinIcon, 
  ChevronRightIcon,
  ArrowRightOnRectangleIcon
} from '@heroicons/vue/24/outline';
import api from '../../services/api';

const router = useRouter();

const navigate = (path) => {
  router.push(path);
};

const handleLogout = () => {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  router.push('/login');
};

const recentActivity = ref([]);

onMounted(async () => {
  try {
    const res = await api.get('/tech/dashboard');
    recentActivity.value = res.data.data.recentActivity.map(act => ({
      ...act,
      asset: `GTQ ${act.amount} - ${act.category}`,
      location: act.location_name,
      time: act.transaction_date,
      status: act.status || 'Completado' 
    }));
  } catch (err) {
    console.error(err);
  }
});
</script>
