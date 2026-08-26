<script setup>
import { ref, onMounted, computed } from 'vue'
import ViderService from '@/services/vider/ViderService'

const loading = ref(true)
const data = ref(null)
const filtros = ref({
  departamento: ''
})

const loadData = async () => {
  loading.value = true
  try {
    const response = await ViderService.getTobanikSummary(filtros.value)
    if (response.success) {
      data.value = response.data
    }
  } catch (error) {
    console.error('Error loading Tobanik data:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})

const chartDataTop = computed(() => {
  if (!data.value?.top_cooperativas) return null
  return {
    labels: data.value.top_cooperativas.map(c => c.nombre_cooperativa.substring(0, 20)),
    datasets: [{
      label: 'Monto Colocado',
      data: data.value.top_cooperativas.map(c => c.monto_colocado),
      backgroundColor: 'rgba(74, 222, 128, 0.6)',
      borderColor: '#22c55e',
      borderWidth: 2,
      borderRadius: 8
    }]
  }
})

const formatCurrency = (n) => new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'GTQ' }).format(Number(n) || 0)
const formatNum = (n) => new Intl.NumberFormat('es-GT').format(Number(n) || 0)
</script>

<template>
  <div class="space-y-6 pb-12 animate-fade-in">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-800 p-6 rounded-3xl border border-gray-100 dark:border-slate-700 shadow-sm">
      <div class="flex items-center gap-4">
        <RouterLink to="/admin/vider/dashboard" class="p-3 bg-indigo-500 text-white rounded-2xl shadow-lg shadow-indigo-500/20 group hover:scale-110 transition-all">
          <LayoutGrid class="w-6 h-6" />
        </RouterLink>
        <div>
          <h2 class="text-xl md:text-2xl font-black text-slate-800 dark:text-white m-0 tracking-tight uppercase">TOBANIK <span class="text-emerald-500">CRÉDITO</span></h2>
          <p class="text-slate-500 dark:text-slate-400 m-0 text-sm font-medium">Sistema de Apoyo a Cooperativas Agrícolas</p>
        </div>
      </div>
      
      <RouterLink to="/admin/vider/dashboard" class="flex items-center gap-2 px-5 py-3 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-2xl font-bold text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-all shadow-sm">
        <ArrowLeft class="w-4 h-4"/>
        Volver al Dashboard
      </RouterLink>
    </div>

    <!-- Hero Banner (Standardized to Unificación Glass) -->
    <div class="relative overflow-hidden bg-gradient-to-br from-emerald-600 to-teal-700 p-8 lg:p-10 rounded-[2.5rem] text-white shadow-xl shadow-emerald-500/20">
      <div class="relative z-10 flex flex-col md:flex-row items-center gap-8">
        <div class="p-4 bg-white/20 backdrop-blur-md rounded-3xl shrink-0">
          <Handshake class="w-12 h-12 text-white"/>
        </div>
        <div>
          <h1 class="text-2xl lg:text-3xl font-black tracking-tight mb-2 uppercase">Cooperativismo Agrícola</h1>
          <p class="text-sm lg:text-base font-medium text-emerald-50 max-w-2xl leading-relaxed opacity-90">
            Fortaleciendo el desarrollo rural integral a través de crédito estratégico y apoyo técnico a las cooperativas de todo el país.
          </p>
        </div>
      </div>
      <div class="absolute -right-20 -top-20 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
    </div>

    <!-- KPI Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
      <div v-for="stat in [
        { label: 'Cooperativas', val: formatNum(data?.totales.total_cooperativas), icon: Building2, color: 'emerald' },
        { label: 'Productores', val: formatNum(data?.totales.total_productores), icon: Users, color: 'blue' },
        { label: 'Monto Colocado', val: formatCurrency(data?.totales.total_monto_colocado), icon: Coins, color: 'amber' },
        { label: 'Monto Otorgado', val: formatCurrency(data?.totales.total_monto_otorgado), icon: HandCoins, color: 'purple' }
      ]" :key="stat.label" class="bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 p-6 rounded-3xl shadow-sm hover:shadow-md transition-all group">
        <div :class="[`p-3 rounded-2xl bg-${stat.color}-500/10 text-${stat.color}-500 mb-4 w-fit`]">
          <component :is="stat.icon" class="w-6 h-6"/>
        </div>
        <div class="text-2xl font-black text-slate-800 dark:text-white mb-1 leading-none">{{ loading ? '...' : stat.val }}</div>
        <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ stat.label }}</span>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <div class="bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 p-8 rounded-[2.5rem] shadow-sm">
        <div class="flex items-center gap-3 mb-8">
          <Trophy class="w-5 h-5 text-amber-500"/>
          <h3 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest">Top 10 Cooperativas</h3>
        </div>
        <div class="h-[350px]">
          <Bar v-if="chartDataTop" :data="chartDataTop" :options="{ 
            indexAxis: 'y', 
            responsive: true, 
            maintainAspectRatio: false, 
            plugins: { legend: { display: false } },
            scales: { x: { grid: { display: false } }, y: { grid: { display: false } } }
          }" />
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 p-8 rounded-[2.5rem] shadow-sm">
        <div class="flex items-center gap-3 mb-8">
          <MapPin class="w-5 h-5 text-indigo-500"/>
          <h3 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest">Distribución Geográfica</h3>
        </div>
        <div class="flex flex-col items-center justify-center h-full py-12 text-center opacity-40">
           <MapPin class="w-16 h-16 text-slate-300 dark:text-slate-600 mb-4 animate-pulse"/>
           <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Procesando mapeo regional...</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
