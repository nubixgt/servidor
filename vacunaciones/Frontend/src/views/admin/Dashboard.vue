<template>
  <div class="space-y-8 animate-fade-in">
    <!-- Welcome Title -->
    <section class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
      <div>
        <h2 class="text-3xl font-extrabold text-[#3455b9] mb-1">Resumen Clínico</h2>
        <p class="text-gray-500 font-medium text-sm">Monitoreo de vacunación y salud avícola.</p>
      </div>
      <div class="flex gap-3 w-full sm:w-auto">
        <button
          @click="navigateToRegistros"
          class="flex-1 sm:flex-initial bg-white/50 backdrop-blur-md border border-white/50 px-4 py-2.5 rounded-xl flex items-center justify-center gap-2 text-gray-700 hover:bg-white hover:text-[#3455b9] transition-all font-bold cursor-pointer"
        >
          <FunnelIcon class="w-4 h-4" />
          <span class="text-xs">Filtrar Historial</span>
        </button>
        <button
          @click="navigateToNuevo"
          class="flex-1 sm:flex-initial bg-[#3455b9] text-white px-4 py-2.5 rounded-xl shadow-md hover:scale-105 hover:shadow-lg transition-all font-bold flex items-center justify-center gap-2 cursor-pointer"
        >
          <PlusIcon class="w-4 h-4" />
          <span class="text-xs">Nuevo Registro</span>
        </button>
      </div>
    </section>

    <!-- Persistent Temporal Filter Panel -->
    <div class="glass-panel p-6 rounded-[32px]">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
        <!-- Header left area -->
        <div class="flex items-center gap-3 text-[#3455b9]">
          <div class="w-10 h-10 rounded-2xl bg-[#3455b9]/10 flex items-center justify-center shrink-0">
            <CalendarIcon class="w-5 h-5 text-[#3455b9] animate-pulse" />
          </div>
          <div>
            <h3 class="text-sm font-black uppercase tracking-wider">Control Temporal Clínico</h3>
            <p class="text-[11px] text-slate-500 font-medium tracking-wide">
              Seleccione el año y el mes para filtrar el rendimiento clínico general.
            </p>
          </div>
        </div>

        <!-- Clean Select Interactivity Area -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 shrink-0">
          <!-- Year selector Dropdown -->
          <div class="flex items-center gap-2.5 bg-white/65 border border-slate-200 rounded-2xl px-3.5 py-2 hover:bg-white transition-colors duration-200">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Año:</span>
            <select
              v-model="selectedYear"
              class="bg-transparent border-none text-xs font-bold text-slate-800 focus:outline-none cursor-pointer pr-1 font-sans"
            >
              <option value="all">Todos los años</option>
              <option v-for="yr in availableYears" :key="yr" :value="yr">{{ yr }}</option>
            </select>
          </div>

          <!-- Month selector Dropdown -->
          <div class="flex items-center gap-2.5 bg-white/65 border border-slate-200 rounded-2xl px-3.5 py-2 hover:bg-white transition-colors duration-200">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Mes:</span>
            <select
              v-model="selectedMonth"
              class="bg-transparent border-none text-xs font-bold text-slate-800 focus:outline-none cursor-pointer pr-1 font-sans"
            >
              <option :value="0">Todos los meses</option>
              <option v-for="mes in meses" :key="mes.value" :value="mes.value">{{ mes.label }}</option>
            </select>
          </div>

          <!-- Selected active state pill info -->
          <div class="bg-[#3455b9]/10 text-[#3455b9] text-[11px] font-extrabold px-3.5 py-2 rounded-xl text-center self-center border border-[#3455b9]/10 shadow-3xs">
            Período: <span class="text-blue-900 font-extrabold">
              {{ selectedMonth === 0 ? 'Anual Completo' : meses.find(m => m.value === selectedMonth)?.label }} 
              {{ selectedYear === 'all' ? '(Histórico)' : ` - ${selectedYear}` }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- KPI Stats Cards row -->
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-6">
      <!-- KPI 1 - Aves Vacunadas -->
      <div class="glass-panel glass-panel-hover p-6 rounded-[32px]">
        <div class="flex justify-between items-start mb-4">
          <div class="w-10 h-10 rounded-2xl bg-emerald-500/15 flex items-center justify-center text-emerald-700">
            <BoltIcon class="w-5 h-5" />
          </div>
          <span class="text-emerald-700 font-bold text-xs bg-emerald-500/10 px-2 py-0.5 rounded-lg">+12%</span>
        </div>
        <p class="text-[#475569] text-xs font-bold uppercase tracking-wider mb-1">Aves Vacunadas</p>
        <h3 class="text-2xl font-black text-[#1e293b]">{{ formatNumber(stats.avesVacunadas || 125400) }}</h3>
      </div>

      <!-- KPI 2 - Ingresos Totales -->
      <div class="glass-panel glass-panel-hover p-6 rounded-[32px]">
        <div class="flex justify-between items-start mb-4">
          <div class="w-10 h-10 rounded-2xl bg-[#3455b9]/15 flex items-center justify-center text-[#3455b9]">
            <ArrowTrendingUpIcon class="w-5 h-5" />
          </div>
          <span class="text-[#3455b9] font-bold text-xs bg-[#3455b9]/10 px-2 py-0.5 rounded-lg">+8.4%</span>
        </div>
        <p class="text-[#475569] text-xs font-bold uppercase tracking-wider mb-1">Ingresos Totales</p>
        <h3 class="text-2xl font-black text-[#1e293b]">{{ formatCurrency(stats.ingresosTotales || 84200) }}</h3>
      </div>

      <!-- KPI 3 - Eficiencia Promedio -->
      <div class="glass-panel glass-panel-hover p-6 rounded-[32px]">
        <div class="flex justify-between items-start mb-4">
          <div class="w-10 h-10 rounded-2xl bg-teal-500/15 flex items-center justify-center text-[#006a63]">
            <TrophyIcon class="w-5 h-5" />
          </div>
        </div>
        <p class="text-[#475569] text-xs font-bold uppercase tracking-wider mb-1">Eficiencia promedio</p>
        <h3 class="text-2xl font-black text-[#1e293b]">{{ store.efficiencyRate }}%</h3>
      </div>
    </section>

    <!-- Charts Section -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Tendencias (Bar) -->
      <div class="glass-panel p-6 rounded-[32px] lg:col-span-2 flex flex-col">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="text-lg font-bold text-gray-800">Tendencias de Vacunación</h3>
            <p class="text-xs text-gray-500">
              Proyección de aplicaciones por {{ selectedMonth === 0 ? 'mes' : 'semana' }} (Aves) 
              <span class="font-bold text-[#3455b9]">[{{ selectedYear === 'all' ? 'Todos los años' : selectedYear }}]</span>
            </p>
          </div>
        </div>
        <div class="flex-1 min-h-[250px] relative w-full">
          <Bar v-if="barChartData.datasets[0].data.some(v => v > 0)" :data="barChartData" :options="barChartOptions" />
          <div v-else class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm font-semibold">
            No hay datos completados para este período
          </div>
        </div>
      </div>

      <!-- Servicios Prestados (Doughnut) -->
      <div class="glass-panel p-6 rounded-[32px] flex flex-col">
        <div class="mb-4">
          <h3 class="text-lg font-bold text-gray-800">Servicios Prestados</h3>
          <p class="text-xs text-gray-500">Distribución por categoría (Dosis)</p>
        </div>
        <div class="flex-1 min-h-[250px] relative w-full flex items-center justify-center">
          <Doughnut v-if="doughnutChartData.datasets[0].data.length > 0" :data="doughnutChartData" :options="doughnutChartOptions" />
          <div v-else class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm font-semibold">
            No hay datos completados para este período
          </div>
        </div>
      </div>
    </section>

    <!-- Main Records Table Section -->
    <section class="w-full">
      <!-- Recent records table -->
      <div class="glass-panel overflow-hidden flex flex-col w-full">
        <div class="p-6 md:p-8 border-b border-slate-100 flex justify-between items-center bg-white/10">
          <div>
            <h4 class="text-lg font-bold text-gray-800">Registros Recientes</h4>
            <p class="text-xs text-gray-500">Últimos servicios de salud registrados</p>
          </div>
          <button
            @click="navigateToRegistros"
            class="text-[#3455b9] font-bold text-xs flex items-center gap-1.5 hover:underline cursor-pointer bg-white/40 border border-white/60 px-3 py-1.5 rounded-xl transition-all"
          >
            Ver todo <ArrowRightIcon class="w-4 h-4" />
          </button>
        </div>

        <div class="overflow-x-auto">
          <div v-if="recentRecords.length === 0" class="p-12 text-center text-gray-500">
            <p class="font-semibold">No hay registros cargados</p>
            <p class="text-xs mt-1">Haga clic en 'Nuevo Registro' para registrar vacunaciones rurales.</p>
          </div>
          <table v-else class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-white/20">
                <th class="px-6 py-4 text-xs font-bold text-gray-600 uppercase tracking-wider">Fecha</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-600 uppercase tracking-wider">Cliente</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-600 uppercase tracking-wider">Servicio</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-600 uppercase tracking-wider">Cantidad</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-600 uppercase tracking-wider">Costo U.</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-600 uppercase tracking-wider">Total</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-600 uppercase tracking-wider">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100/30">
              <tr
                v-for="rec in recentRecords"
                :key="rec.id"
                @click="navigateToRegistros"
                class="hover:bg-white/30 transition-colors cursor-pointer"
              >
                <td class="px-6 py-4.5">
                  <div class="flex flex-col">
                     <span class="text-gray-800 font-bold text-xs">{{ formatCompactDate(rec.fecha) }}</span>
                    <span class="text-[10px] text-gray-500 font-medium">{{ rec.hora }}</span>
                  </div>
                </td>
                <td class="px-6 py-4.5">
                  <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg bg-white/70 border border-gray-200/50 flex items-center justify-center font-extrabold text-[#3455b9] text-[10px]">
                      {{ rec.clienteIniciales }}
                    </span>
                    <span class="text-gray-800 font-bold text-xs truncate max-w-[120px]">{{ rec.cliente }}</span>
                  </div>
                </td>
                <td class="px-6 py-4.5 text-xs font-semibold text-gray-700">{{ rec.servicio }}</td>
                <td class="px-6 py-4.5 text-xs text-gray-600 font-medium">{{ formatNumber(rec.cantidad) }} aves</td>
                <td class="px-6 py-4.5 text-xs text-gray-500 font-mono">Q {{ rec.costoPorAve.toFixed(4) }}</td>
                <td class="px-6 py-4.5 text-xs font-extrabold text-[#3455b9]">{{ formatCurrency(rec.total) }}</td>
                <td class="px-6 py-4.5 text-xs">
                  <span :class="`px-2.5 py-1 rounded-full text-[10px] font-extrabold ${
                    rec.estado === 'Completado'
                      ? 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20'
                      : rec.estado === 'Pendiente'
                        ? 'bg-rose-500/10 text-[#a8295b] border border-rose-500/20'
                        : 'bg-gray-500/10 text-gray-600 border border-gray-500/20'
                  }`">
                    {{ rec.estado }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAppStore } from '../../stores/appStore';
import { Bar, Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);
import { useRouter } from 'vue-router';
import { formatCurrency, formatNumber, formatCompactDate } from '../../utils/data';
import { 
  BoltIcon, 
  ArrowTrendingUpIcon, 
  TrophyIcon, 
  FunnelIcon, 
  PlusIcon, 
  CalendarIcon, 
  ArrowRightIcon 
} from '@heroicons/vue/24/outline';

const store = useAppStore();
const router = useRouter();

onMounted(() => {
  store.fetchRecords();
});

const selectedMonth = ref(0);
const selectedYear = ref('all');

const availableYears = computed(() => {
  const years = new Set(['2024', '2025', '2026']);
  store.records.forEach(r => {
    const parts = String(r.fecha).split('-');
    if (parts.length >= 1) {
      const yr = parts[0];
      if (yr && yr.length === 4) {
        years.add(yr);
      }
    }
  });
  return Array.from(years).sort();
});

const monthlyFilteredRecords = computed(() => {
  return store.records.filter(r => {
    const parts = String(r.fecha).split('-');
    if (parts.length >= 3) {
      const recordYear = parts[0];
      const recordMonth = parseInt(parts[1], 10);
      
      const matchesYear = selectedYear.value === 'all' || recordYear === selectedYear.value;
      const matchesMonth = selectedMonth.value === 0 || recordMonth === selectedMonth.value;
      
      return matchesYear && matchesMonth;
    }
    return false;
  });
});

const barChartData = computed(() => {
  const labels = selectedMonth.value === 0 
    ? meses.map(m => m.label.substring(0, 3)) // Ene, Feb, Mar...
    : ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4', 'Semana 5'];
    
  const data = new Array(labels.length).fill(0);
  
  monthlyFilteredRecords.value.forEach(r => {
    if (r.estado !== 'Completado') return;
    const parts = String(r.fecha).split('-');
    if (parts.length >= 3) {
      if (selectedMonth.value === 0) {
        const mIdx = parseInt(parts[1], 10) - 1;
        if (mIdx >= 0 && mIdx < 12) data[mIdx] += r.cantidad;
      } else {
        const day = parseInt(parts[2], 10);
        const wIdx = Math.min(Math.floor((day - 1) / 7), 4);
        data[wIdx] += r.cantidad;
      }
    }
  });

  return {
    labels,
    datasets: [
      {
        label: 'Aves Vacunadas',
        backgroundColor: '#3455b9',
        borderRadius: 4,
        data
      }
    ]
  };
});

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false }
  },
  scales: {
    y: { beginAtZero: true }
  }
};

const doughnutChartData = computed(() => {
  const serviceCounts = {};
  monthlyFilteredRecords.value.forEach(r => {
    if (r.estado !== 'Completado') return;
    serviceCounts[r.servicio] = (serviceCounts[r.servicio] || 0) + r.cantidad;
  });
  
  const labels = Object.keys(serviceCounts);
  const data = Object.values(serviceCounts);
  
  const backgroundColors = ['#3455b9', '#0f766e', '#be123c', '#475569', '#ca8a04', '#7e22ce'];
  
  return {
    labels,
    datasets: [
      {
        backgroundColor: labels.map((_, i) => backgroundColors[i % backgroundColors.length]),
        data
      }
    ]
  };
});

const doughnutChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom' }
  }
};

const stats = computed(() => {
  const avesVacunadas = monthlyFilteredRecords.value.reduce((sum, r) => sum + (r.estado === 'Completado' ? r.cantidad : 0), 0);
  const ingresosTotales = monthlyFilteredRecords.value.reduce((sum, r) => sum + (r.estado === 'Completado' ? r.total : 0), 0);
  
  return {
    avesVacunadas,
    ingresosTotales
  };
});

const recentRecords = computed(() => {
  return monthlyFilteredRecords.value.slice(0, 4);
});

const meses = [
  { value: 1, label: 'Enero' },
  { value: 2, label: 'Febrero' },
  { value: 3, label: 'Marzo' },
  { value: 4, label: 'Abril' },
  { value: 5, label: 'Mayo' },
  { value: 6, label: 'Junio' },
  { value: 7, label: 'Julio' },
  { value: 8, label: 'Agosto' },
  { value: 9, label: 'Septiembre' },
  { value: 10, label: 'Octubre' },
  { value: 11, label: 'Noviembre' },
  { value: 12, label: 'Diciembre' },
];

const navigateToRegistros = () => router.push('/admin/registros');
const navigateToNuevo = () => router.push('/admin/nuevo-registro');

</script>
