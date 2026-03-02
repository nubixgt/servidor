<template>
  <PageLayout title="Estadísticas de Ventas" subtitle="Resumen general del rendimiento comercial">
    <div v-if="loading" class="flex justify-center items-center h-64">
      <svg class="animate-spin h-8 w-8 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>
    
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <KpiCard
        title="Ventas Totales"
        :value="'Q ' + formatMoney(stats.ventas_totales)"
        trend="Calculado en vivo"
        :trendUp="true"
        color="green"
      >
        <template #icon><span class="font-bold text-xl">Q</span></template>
      </KpiCard>
      <KpiCard
        title="Clientes Activos"
        :value="stats.clientes_activos"
        trend="Registrados"
        :trendUp="true"
        color="blue"
      >
        <template #icon>
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </template>
      </KpiCard>
      <KpiCard
        title="Stock Bajo"
        :value="stats.stock_bajo + ' Items'"
        trend="Agotados"
        :trendUp="false"
        color="red"
      >
         <template #icon>
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>
        </template>
      </KpiCard>
      <KpiCard
        title="Promedio Venta"
        :value="'Q ' + formatMoney(stats.promedio_venta)"
        trend="Ticket Promedio"
        :trendUp="null"
        color="orange"
      >
        <template #icon>
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
        </template>
      </KpiCard>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-8">
          <div>
            <h3 class="text-lg font-bold text-gray-800">Tendencia de Ventas</h3>
            <p class="text-sm text-gray-500">Enero - Junio 2026</p>
          </div>
          <select class="bg-gray-50 border border-gray-200 text-sm rounded-lg py-2 px-3 focus:ring-1 focus:ring-[#2E7D32] focus:border-[#2E7D32] text-gray-600 shadow-sm cursor-pointer hover:bg-gray-100 transition-colors outline-none">
            <option>Últimos 6 meses</option>
            <option>Este año</option>
            <option>Todo el tiempo</option>
          </select>
        </div>
        <!-- Chart Placeholder -->
        <div class="h-72 flex items-end justify-between gap-2">
            <div v-for="(h, i) in [40, 55, 45, 70, 60, 85]" :key="i" class="flex-1 flex flex-col items-center gap-2 group">
                <div :class="`w-full max-w-[3rem] rounded-t-lg transition-all relative ${i === 5 ? 'bg-[#2E7D32]' : 'bg-gray-100 group-hover:bg-[#2E7D32]/60'}`" :style="`height: ${h}%`">
                    <div v-if="i === 5" class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs font-bold py-1 px-2 rounded-full">Q32k</div>
                </div>
                <span :class="`text-xs font-medium ${i === 5 ? 'text-[#2E7D32] font-bold' : 'text-gray-500'}`">
                    {{ ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'][i] }}
                </span>
            </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col">
        <h3 class="text-lg font-bold text-gray-800 mb-6">Ventas por Categoría</h3>
        <div class="flex-1 flex items-center justify-center relative py-4">
          <div class="w-48 h-48 rounded-full border-[1.5rem] border-[#2E7D32] relative flex items-center justify-center shadow-sm bg-white" style="border-right-color: #81C784; border-bottom-color: #FFB74D; border-left-color: #EEEEEE; transform: rotate(-45deg);">
          </div>
          <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
            <span class="text-3xl font-bold text-gray-800">1,204</span>
            <span class="text-xs text-gray-500 font-medium uppercase tracking-wide">Items Vendidos</span>
          </div>
        </div>
        <div class="mt-6 space-y-4">
          <CategoryStat 
            v-for="(cat, i) in stats.ventas_por_categoria || []" 
            :key="i"
            :name="cat.category" 
            :percentage="cat.items_sold + ' items'" 
            :color="getCategoryColor(i)" 
          />
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50">
        <h3 class="text-lg font-bold text-gray-800">Ventas Recientes</h3>
        <button class="text-[#2E7D32] text-sm font-semibold hover:text-[#1B5E20] transition-colors flex items-center gap-1 hover:underline">
          Ver todas <ArrowRightSVG />
        </button>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-white text-gray-500 border-b border-gray-100">
            <tr>
              <th class="px-6 py-4 font-semibold w-32 bg-gray-50/50">ID</th>
              <th class="px-6 py-4 font-semibold bg-gray-50/50">Cliente</th>
              <th class="px-6 py-4 font-semibold bg-gray-50/50">Fecha</th>
              <th class="px-6 py-4 font-semibold bg-gray-50/50">Monto</th>
              <th class="px-6 py-4 font-semibold bg-gray-50/50">Estado</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <RecentSaleRow 
              v-for="sale in stats.ventas_recientes" 
              :key="sale.id"
              :id="sale.invoice_number" 
              name="Cliente API" 
              location="Registrado" 
              :date="sale.sale_date.substring(0,10)" 
              :amount="'Q ' + formatMoney(sale.total_amount)" 
              :status="sale.status" 
              initials="CA" 
              :color="sale.status === 'Completado' ? 'green' : 'blue'" 
            />
            <tr v-if="!stats.ventas_recientes || stats.ventas_recientes.length === 0">
              <td colspan="5" class="px-6 py-8 text-center text-gray-500">No hay ventas recientes.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </PageLayout>
</template>

<script setup>
import PageLayout from '../../components/layout/PageLayout.vue';
import { ref, onMounted, computed } from 'vue';
import api from '../../services/api';

const loading = ref(true);
const stats = ref({
  ventas_totales: 0,
  clientes_activos: 0,
  stock_bajo: 0,
  promedio_venta: 0,
  ventas_por_categoria: [],
  ventas_recientes: []
});

onMounted(async () => {
  try {
    const res = await api.get('/dashboard/stats');
    if(res.data.status === 'success') {
      stats.value = res.data.data;
    }
  } catch (error) {
    console.error("Failed to fetch dashboard stats", error);
  } finally {
    loading.value = false;
  }
});

const formatMoney = (val) => {
  return parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const getCategoryColor = (i) => {
    const colors = ['bg-[#2E7D32]', 'bg-[#81C784]', 'bg-[#FFB74D]', 'bg-[#EEEEEE]'];
    return colors[i % colors.length];
};

// Subcomponents as local components or simple components within the file

const KpiCard = {
  props: ['title', 'value', 'trend', 'trendUp', 'color'],
  setup(props) {
    const colorMap = {
      green: { bg: 'bg-green-50', text: 'text-[#2E7D32]', border: 'border-green-100', trendText: 'text-green-700', trendBg: 'bg-green-50' },
      blue: { bg: 'bg-blue-50', text: 'text-blue-600', border: 'border-blue-100', trendText: 'text-blue-700', trendBg: 'bg-blue-50' },
      red: { bg: 'bg-red-50', text: 'text-red-500', border: 'border-red-100', trendText: 'text-red-700', trendBg: 'bg-red-50' },
      orange: { bg: 'bg-orange-50', text: 'text-orange-500', border: 'border-orange-100', trendText: 'text-gray-500', trendBg: 'bg-gray-100' },
    };
    const c = computed(() => colorMap[props.color]);
    return { c };
  },
  template: `
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
      <div>
        <p class="text-sm font-medium text-gray-500 mb-1">{{ title }}</p>
        <h3 class="text-2xl font-bold text-gray-800">{{ value }}</h3>
        <div class="flex items-center gap-1 mt-2">
          <svg v-if="trendUp === true" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-600"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
          <svg v-else-if="trendUp === false" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-600"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          <span :class="['text-xs font-medium px-2 py-0.5 rounded-full', c.trendBg, c.trendText]">
            {{ trend }}
          </span>
        </div>
      </div>
      <div :class="['w-12 h-12 rounded-full flex items-center justify-center shadow-sm border', c.bg, c.text, c.border]">
        <slot name="icon"></slot>
      </div>
    </div>
  `
};

const CategoryStat = {
  props: ['name', 'percentage', 'color'],
  template: `
    <div class="flex items-center justify-between text-sm group p-2 hover:bg-gray-50 rounded-lg transition-colors">
      <div class="flex items-center gap-3">
        <span :class="['w-3 h-3 rounded-full shadow-sm', color]"></span>
        <span class="text-gray-600 font-medium">{{ name }}</span>
      </div>
      <span class="font-bold text-gray-800">{{ percentage }}</span>
    </div>
  `
};

const RecentSaleRow = {
  props: ['id', 'name', 'location', 'date', 'amount', 'status', 'initials', 'color'],
  setup(props) {
    const colorMap = {
      green: 'bg-green-100 text-[#2E7D32] border-green-200',
      blue: 'bg-blue-100 text-blue-600 border-blue-200',
      purple: 'bg-purple-100 text-purple-600 border-purple-200',
    };
    const rowColor = computed(() => colorMap[props.color]);
    const statusColor = computed(() => props.status === 'Completado' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200');
    const statusDot = computed(() => props.status === 'Completado' ? 'bg-green-500' : 'bg-yellow-500');

    return { rowColor, statusColor, statusDot };
  },
  template: `
    <tr class="group hover:bg-gray-50 transition-colors">
      <td class="px-6 py-4 font-medium text-gray-900">{{ id }}</td>
      <td class="px-6 py-4">
        <div class="flex items-center gap-3">
          <div :class="['w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold border shadow-sm', rowColor]">
            {{ initials }}
          </div>
          <div>
            <div class="font-semibold text-gray-900">{{ name }}</div>
            <div class="text-xs text-gray-500">{{ location }}</div>
          </div>
        </div>
      </td>
      <td class="px-6 py-4 text-gray-500">{{ date }}</td>
      <td class="px-6 py-4 font-bold text-gray-900">{{ amount }}</td>
      <td class="px-6 py-4">
        <span :class="['px-2.5 py-1 rounded-full text-xs font-semibold border flex w-fit items-center gap-1', statusColor]">
          <span :class="['w-1.5 h-1.5 rounded-full', statusDot]"></span> {{ status }}
        </span>
      </td>
    </tr>
  `
};

const ArrowRightSVG = {
  template: `
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <line x1="5" y1="12" x2="19" y2="12"></line>
      <polyline points="12 5 19 12 12 19"></polyline>
    </svg>
  `
};
</script>
