<template>
  <PageLayout title="Tablero Principal" subtitle="Información general de ventas, cobros y rendimiento">
    <div v-if="loading" class="flex justify-center items-center h-64">
      <svg class="animate-spin h-8 w-8 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>
    
    <div v-else class="space-y-6">
      <!-- KPIs -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Ventas del Mes -->
        <div class="bg-white p-6 rounded-2xl shadow-[4px_0_24px_rgba(0,0,0,0.02)] border border-gray-100 flex items-center justify-between hover:-translate-y-1 transition-transform duration-300 group">
          <div>
            <p class="text-sm font-semibold text-gray-500 mb-1">Ventas (30 Días)</p>
            <h3 class="text-2xl font-bold text-gray-800 tracking-tight">Q {{ formatMoney(stats.ventas_mes) }}</h3>
            <div class="mt-2 inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full bg-green-50 text-green-700">
              Período Actual
            </div>
          </div>
          <div class="w-14 h-14 rounded-2xl bg-green-50 text-[#2E7D32] flex items-center justify-center transform group-hover:rotate-12 transition-transform duration-300 border border-green-100 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
          </div>
        </div>

        <!-- Cobros del Mes -->
        <div class="bg-white p-6 rounded-2xl shadow-[4px_0_24px_rgba(0,0,0,0.02)] border border-gray-100 flex items-center justify-between hover:-translate-y-1 transition-transform duration-300 group">
          <div>
            <p class="text-sm font-semibold text-gray-500 mb-1">Cobros (30 Días)</p>
            <h3 class="text-2xl font-bold text-gray-800 tracking-tight">Q {{ formatMoney(stats.cobros_mes) }}</h3>
            <div class="mt-2 inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">
              Recaudado
            </div>
          </div>
          <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center transform group-hover:-rotate-12 transition-transform duration-300 border border-blue-100 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
          </div>
        </div>

        <!-- Deuda Total Pendiente -->
        <div class="bg-white p-6 rounded-2xl shadow-[4px_0_24px_rgba(0,0,0,0.02)] border border-gray-100 flex items-center justify-between hover:-translate-y-1 transition-transform duration-300 group">
          <div>
            <p class="text-sm font-semibold text-gray-500 mb-1">Cuentas por Cobrar</p>
            <h3 class="text-2xl font-bold text-gray-800 tracking-tight">Q {{ formatMoney(stats.deuda_total) }}</h3>
            <div class="mt-2 inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full bg-orange-50 text-orange-700">
              Global
            </div>
          </div>
          <div class="w-14 h-14 rounded-2xl bg-orange-50 text-orange-500 flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300 border border-orange-100 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
          </div>
        </div>

        <!-- Clientes Activos -->
        <div class="bg-white p-6 rounded-2xl shadow-[4px_0_24px_rgba(0,0,0,0.02)] border border-gray-100 flex items-center justify-between hover:-translate-y-1 transition-transform duration-300 group">
          <div>
            <p class="text-sm font-semibold text-gray-500 mb-1">Clientes Activos</p>
            <h3 class="text-2xl font-bold text-gray-800 tracking-tight">{{ stats.clientes_activos }}</h3>
            <div class="mt-2 inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full bg-purple-50 text-purple-700">
              Cartera
            </div>
          </div>
          <div class="w-14 h-14 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center transform group-hover:-translate-y-1 transition-transform duration-300 border border-purple-100 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          </div>
        </div>
      </div>

      <!-- Charts & Tables Row -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Tendencias (6 Meses) -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-[4px_0_24px_rgba(0,0,0,0.02)] border border-gray-100 flex flex-col">
          <div class="mb-8 flex items-center justify-between">
            <div>
              <h3 class="text-lg font-bold text-gray-800">Tendencia de Ventas vs Cobros</h3>
              <p class="text-sm text-gray-500">Últimos 6 meses</p>
            </div>
            <div class="flex gap-4 items-center">
              <div class="flex items-center gap-2 text-sm"><span class="w-3 h-3 rounded-full bg-[#2E7D32]"></span>Ventas</div>
              <div class="flex items-center gap-2 text-sm"><span class="w-3 h-3 rounded-full bg-blue-500"></span>Cobros</div>
            </div>
          </div>
          
          <div class="flex-1 flex items-end justify-between gap-4 h-64 relative mt-auto z-10">
            <div v-for="(mesAbrev, i) in ultimos6MesesAbrev" :key="i" class="flex-1 flex flex-col items-center justify-end h-full group relative z-10">
                  <!-- Barras superpuestas / juntas -->
                  <div class="w-full flex justify-center items-end h-[85%] gap-1">
                      <div class="w-1/3 bg-blue-500 rounded-t-sm transition-all hover:brightness-110 relative" 
                           :style="{ height: getBarHeight(mergedTrend[i]?.cobros) + '%' }">
                        <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs font-bold py-1 px-2 rounded opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity whitespace-nowrap z-50">Q{{ formatMoney(mergedTrend[i]?.cobros) }}</div>
                      </div>
                      <div class="w-1/3 bg-[#2E7D32] rounded-t-sm transition-all hover:brightness-110 relative" 
                           :style="{ height: getBarHeight(mergedTrend[i]?.ventas) + '%' }">
                        <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs font-bold py-1 px-2 rounded opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity whitespace-nowrap z-50">Q{{ formatMoney(mergedTrend[i]?.ventas) }}</div>
                      </div>
                  </div>
                  <span class="text-xs font-semibold text-gray-500 mt-3 whitespace-nowrap">{{ mesAbrev }}</span>
            </div>
            <!-- Líneas de fondo -->
            <div class="absolute inset-0 flex flex-col justify-between pointer-events-none pb-[15%]">
              <div class="border-b border-gray-100 w-full h-0"></div>
              <div class="border-b border-gray-100 w-full h-0"></div>
              <div class="border-b border-gray-100 w-full h-0"></div>
              <div class="border-b border-gray-100 w-full h-0"></div>
            </div>
          </div>
        </div>

        <!-- Ventas por Categoría -->
        <div class="bg-white p-6 rounded-2xl shadow-[4px_0_24px_rgba(0,0,0,0.02)] border border-gray-100 flex flex-col h-full">
          <h3 class="text-lg font-bold text-gray-800 mb-6">Top Productos Vendidos</h3>
          <div class="flex-1 flex flex-col justify-center">
            <div class="space-y-4">
              <template v-if="stats.ventas_por_categoria && stats.ventas_por_categoria.length">
                <div v-for="(cat, i) in stats.ventas_por_categoria" :key="i" class="group">
                  <div class="flex items-center justify-between text-sm mb-1">
                    <span class="font-medium text-gray-700 truncate pr-4">{{ cat.category }}</span>
                    <span class="font-bold text-gray-900 border bg-gray-50 px-2 py-0.5 rounded-md">{{ cat.items_sold }} un.</span>
                  </div>
                  <div class="w-full bg-gray-100 rounded-full h-2">
                    <div :class="['h-2 rounded-full transition-all duration-700 delay-100', getCategoryColor(i)]" :style="{ width: Math.min(100, (cat.items_sold / maxCategorySold) * 100) + '%' }"></div>
                  </div>
                </div>
              </template>
              <div v-else class="text-center text-gray-500 py-8">
                No hay datos de productos vendidos aún.
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Second Row of Tables -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Últimas Ventas -->
        <div class="bg-white rounded-2xl shadow-[4px_0_24px_rgba(0,0,0,0.02)] border border-gray-100 overflow-hidden flex flex-col">
          <div class="px-6 py-5 border-b border-gray-50 flex justify-between items-center bg-white">
            <h3 class="text-base font-bold text-gray-800">Últimas Ventas Registradas</h3>
            <router-link to="/ventas" class="text-sm font-semibold text-[#2E7D32] hover:text-[#1B5E20] transition-colors hover:underline">Ir a Ventas</router-link>
          </div>
          <div class="flex-1 p-0 overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
              <tbody>
                <tr v-for="sale in stats.ventas_recientes" :key="sale.id" class="border-b border-gray-50 last:border-0 hover:bg-gray-50/50 transition-colors">
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-full bg-green-50 text-[#2E7D32] border border-green-100 flex items-center justify-center font-bold text-xs uppercase shadow-sm">
                        {{ sale.cliente_nombre?.substring(0,2) || 'CL' }}
                      </div>
                      <div>
                        <p class="font-bold text-gray-900 leading-tight truncate max-w-[150px]">{{ sale.cliente_nombre || 'Consumidor Final' }}</p>
                        <p class="text-xs text-gray-500 mt-0.5 font-medium">Nota: {{ sale.numero_nota }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-gray-500">{{ sale.fecha.substring(0,10) }}</td>
                  <td class="px-6 py-4 text-right font-bold text-gray-900">Q {{ formatMoney(sale.total) }}</td>
                </tr>
                <tr v-if="!stats.ventas_recientes?.length">
                  <td colspan="3" class="px-6 py-8 text-center text-gray-500 italic">No hay ventas registradas.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Últimos Pagos -->
        <div class="bg-white rounded-2xl shadow-[4px_0_24px_rgba(0,0,0,0.02)] border border-gray-100 overflow-hidden flex flex-col">
          <div class="px-6 py-5 border-b border-gray-50 flex justify-between items-center bg-white">
            <h3 class="text-base font-bold text-gray-800">Últimos Pagos Recibidos</h3>
            <router-link to="/pagos" class="text-sm font-semibold text-blue-600 hover:text-blue-800 transition-colors hover:underline">Ir a Pagos</router-link>
          </div>
          <div class="flex-1 p-0 overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
              <tbody>
                <tr v-for="pago in stats.pagos_recientes" :key="pago.id" class="border-b border-gray-50 last:border-0 hover:bg-gray-50/50 transition-colors">
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center font-bold text-xs shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
                      </div>
                      <div>
                        <p class="font-bold text-gray-900 leading-tight truncate max-w-[150px]">{{ pago.cliente_nombre || 'Varios' }}</p>
                        <p class="text-xs text-blue-600 mt-0.5 font-semibold bg-blue-50 inline-block px-1.5 rounded">{{ pago.banco }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-gray-500">{{ pago.fecha_pago.substring(0,10) }}</td>
                  <td class="px-6 py-4 text-right font-bold text-gray-900 whitespace-nowrap">
                    <span class="text-green-600">+</span> Q {{ formatMoney(pago.monto_pago) }}
                  </td>
                </tr>
                <tr v-if="!stats.pagos_recientes?.length">
                  <td colspan="3" class="px-6 py-8 text-center text-gray-500 italic">No hay pagos registrados.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
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
  ventas_mes: 0,
  cobros_mes: 0,
  deuda_total: 0,
  clientes_activos: 0,
  ventas_por_categoria: [],
  ventas_recientes: [],
  pagos_recientes: [],
  tendencia_ventas: [],
  tendencia_cobros: []
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
    const colors = ['bg-[#2E7D32]', 'bg-[#81C784]', 'bg-[#FFB74D]', 'bg-blue-400', 'bg-gray-300'];
    return colors[i % colors.length];
};

const maxCategorySold = computed(() => {
    if (!stats.value.ventas_por_categoria?.length) return 1;
    return Math.max(...stats.value.ventas_por_categoria.map(c => Number(c.items_sold)));
});

// Chart Logic
const ultimos6MesesAbrev = computed(() => {
    const nombres = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    const result = [];
    const d = new Date();
    d.setDate(1);
    for (let i = 5; i >= 0; i--) {
        const past = new Date(d);
        past.setMonth(d.getMonth() - i);
        result.push(nombres[past.getMonth()]);
    }
    return result;
});

const mergedTrend = computed(() => {
    // Return an array of 6 elements corresponding to the last 6 months
    const d = new Date();
    d.setDate(1);
    const monthsKeys = [];
    for (let i = 5; i >= 0; i--) {
        const past = new Date(d);
        past.setMonth(d.getMonth() - i);
        const yyyy = past.getFullYear();
        const mm = String(past.getMonth() + 1).padStart(2, '0');
        monthsKeys.push(`${yyyy}-${mm}`);
    }

    const ventasMap = {};
    (stats.value.tendencia_ventas || []).forEach(v => ventasMap[v.mes] = Number(v.total));

    const cobrosMap = {};
    (stats.value.tendencia_cobros || []).forEach(c => cobrosMap[c.mes] = Number(c.total));

    return monthsKeys.map(k => ({
        mes: k,
        ventas: ventasMap[k] || 0,
        cobros: cobrosMap[k] || 0
    }));
});

const maxTrendValue = computed(() => {
    let max = 0;
    mergedTrend.value.forEach(t => {
        if (t.ventas > max) max = t.ventas;
        if (t.cobros > max) max = t.cobros;
    });
    return max > 0 ? max : 1; 
});

const getBarHeight = (value) => {
    const pct = (Number(value) / maxTrendValue.value) * 100;
    return Math.max(5, Math.min(100, pct)); // Min 5% so it's visible, Max 100%
};
</script>
