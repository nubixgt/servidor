<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import Tabla from './Tabla.vue'
import Editar from './Editar.vue'
import Dapca from './Dapca.vue'
import {
  Activity, Utensils, Target, Package, AlertTriangle, Shield,
  Flag, Gavel, Sprout, FileText, Warehouse, MapPin, Building,
  FlagIcon, Search, X, Calendar, ChevronDown, BarChart2, Heart, Edit
} from 'lucide-vue-next'
import {
  Chart as ChartJS, CategoryScale, LinearScale, BarElement,
  Title, Tooltip, Legend, PointElement, LineElement, ArcElement,
  Filler
} from 'chart.js'
import { Bar, Line, Doughnut } from 'vue-chartjs'

ChartJS.register(
  CategoryScale, LinearScale, BarElement, Title, Tooltip,
  Legend, PointElement, LineElement, ArcElement, Filler
)

// ── Estado ────────────────────────────────────────────
// ── Estado ────────────────────────────────────────────
const router = useRouter()
const activeTab = ref('dashboard')
const rawData = ref({})
const loading = ref(true)
const filtersOpen = ref(false)

// Filtros
const filterDept  = ref('')
const filterMuni  = ref('')
const filterStart = ref('')
const filterEnd   = ref('')

const fetchDashboardData = async () => {
  loading.value = true
  try {
    const params = {}
    if (filterDept.value)  params.departamento  = filterDept.value
    if (filterMuni.value)  params.municipio     = filterMuni.value
    if (filterStart.value) params.fecha_inicio  = filterStart.value
    if (filterEnd.value)   params.fecha_fin     = filterEnd.value

    const res = await api.get('/visan/dashboard', { params })
    rawData.value = res.data || {}
  } catch (err) {
    console.error('Error cargando dashboard:', err)
  } finally {
    loading.value = false
  }
}

const clearFilters = () => {
  filterDept.value = filterMuni.value = filterStart.value = filterEnd.value = ''
  fetchDashboardData()
}

onMounted(fetchDashboardData)

// ── Computed ───────────────────────────────────────────
const stats    = computed(() => rawData.value.stats    || {})
const graficas = computed(() => rawData.value.graficas || {})
const listas   = computed(() => rawData.value.listas   || {})
const hasFilters = computed(() =>
  filterDept.value || filterMuni.value || filterStart.value || filterEnd.value
)

const municipiosDisponibles = computed(() => {
  if (!filterDept.value || !listas.value?.departamentos) return []
  // Find municipalities from stats or listas (limited info from dashboard API)
  return []
})

const formatNum = (num) => num ? Number(num).toLocaleString() : '0'

// Porcentajes de avance vs techo
const TECHO_AA   = 691547
const TECHO_APA  = 232508
const TECHO_TOTAL = 924055

const pctAA    = computed(() => stats.value.total_aa_r   ? ((Number(stats.value.total_aa_r)  / TECHO_AA)    * 100).toFixed(1) : 0)
const pctAPA   = computed(() => stats.value.total_apa_f  ? ((Number(stats.value.total_apa_f) / TECHO_APA)   * 100).toFixed(1) : 0)
const pctTotal = computed(() => stats.value.total_aa_apa ? ((Number(stats.value.total_aa_apa)/ TECHO_TOTAL) * 100).toFixed(1) : 0)

// ── Colores para Chart.js ──────────────────────────────
const makeGradient = (ctx, colors) => {
  const gradient = ctx.createLinearGradient(0, 0, 0, 400)
  gradient.addColorStop(0,   colors[0])
  gradient.addColorStop(1,   colors[1])
  return gradient
}

// Opciones base de charts con tooltips mejorados
const baseChartOpts = {
  responsive: true,
  maintainAspectRatio: false,
  animation: { duration: 800, easing: 'easeOutQuart' },
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: 'rgba(15,23,42,0.92)',
      titleFont: { size: 12, weight: 'bold' },
      bodyFont:  { size: 13 },
      padding: 12,
      cornerRadius: 10,
      callbacks: {
        label: ctx => ' ' + Number(ctx.parsed.y).toLocaleString()
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: { color: '#f1f5f9' },
      ticks: { color: '#64748b', font: { size: 11 }, callback: v => v >= 1000 ? (v/1000).toFixed(0)+'k' : v }
    },
    x: {
      grid: { display: false },
      ticks: { color: '#64748b', font: { size: 10 }, maxRotation: 40, minRotation: 30 }
    }
  }
}

const baseChartOptsLegend = {
  ...baseChartOpts,
  plugins: {
    ...baseChartOpts.plugins,
    legend: { display: true, position: 'top', labels: { boxWidth: 12, font: { size: 12 }, color: '#475569' } }
  }
}

// Chart data computeds
const top10DeptData = computed(() => ({
  labels: graficas.value.top10_dept_aa_apa?.labels || [],
  datasets: [{
    label: 'Total AA + APA',
    backgroundColor: '#3b82f6',
    hoverBackgroundColor: '#2563eb',
    borderRadius: 8,
    borderSkipped: false,
    data: (graficas.value.top10_dept_aa_apa?.data || []).map(v => Number(v) || 0)
  }]
}))

const top15MuniData = computed(() => ({
  labels: graficas.value.top15_municipios?.labels || [],
  datasets: [{
    label: 'Total Asistencia',
    backgroundColor: '#10b981',
    hoverBackgroundColor: '#059669',
    borderRadius: 8,
    borderSkipped: false,
    data: (graficas.value.top15_municipios?.data || []).map(v => Number(v) || 0)
  }]
}))

const top10InsanData = computed(() => ({
  labels: graficas.value.top10_insan?.labels || [],
  datasets: [{
    label: 'INSAN Total',
    backgroundColor: '#ef4444',
    hoverBackgroundColor: '#dc2626',
    borderRadius: 8,
    borderSkipped: false,
    data: (graficas.value.top10_insan?.data || []).map(v => Number(v) || 0)
  }]
}))

const ejecucionData = computed(() => ({
  labels: graficas.value.aa_por_dept?.labels || [],
  datasets: [
    {
      label: 'Raciones AA',
      backgroundColor: 'rgba(59,130,246,0.85)',
      hoverBackgroundColor: 'rgba(37,99,235,0.95)',
      borderRadius: 6,
      data: (graficas.value.aa_por_dept?.raciones || []).map(v => Number(v) || 0)
    },
    {
      label: 'Fondos APA',
      backgroundColor: 'rgba(16,185,129,0.85)',
      hoverBackgroundColor: 'rgba(5,150,105,0.95)',
      borderRadius: 6,
      data: (graficas.value.aa_por_dept?.fondos || []).map(v => Number(v) || 0)
    }
  ]
}))

// Doughnut para reserva
const reservaTotal = computed(() => Number(stats.value.reserva_estrategica_r) || 0)
const reservaTecho = 205302
const reservaData = computed(() => ({
  labels: ['Ejecutado', 'Pendiente'],
  datasets: [{
    data: [reservaTotal.value, Math.max(0, reservaTecho - reservaTotal.value)],
    backgroundColor: ['#6366f1', '#e2e8f0'],
    hoverBackgroundColor: ['#4f46e5', '#cbd5e1'],
    borderWidth: 0,
    spacing: 2
  }]
}))
const reservaPct = computed(() =>
  reservaTecho > 0 ? ((reservaTotal.value / reservaTecho) * 100).toFixed(1) : 0
)
const reservaDoughnutOpts = {
  responsive: true, maintainAspectRatio: false, cutout: '72%',
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: 'rgba(15,23,42,0.92)', padding: 10, cornerRadius: 8,
      callbacks: { label: ctx => ' ' + Number(ctx.parsed).toLocaleString() }
    }
  }
}

// Doughnuts para las 3 tarjetas hero
const makeDoughnut = (value, techo, color) => computed(() => ({
  labels: ['Ejecutado', 'Pendiente'],
  datasets: [{
    data: [Number(value.value) || 0, Math.max(0, techo - (Number(value.value) || 0))],
    backgroundColor: [color, '#e2e8f0'],
    hoverBackgroundColor: [color, '#cbd5e1'],
    borderWidth: 0, spacing: 2
  }]
}))

const aaVal    = computed(() => stats.value.total_aa_r)
const apaVal   = computed(() => stats.value.total_apa_f)
const totalVal = computed(() => stats.value.total_aa_apa)

const aaData    = makeDoughnut(aaVal,    TECHO_AA,    '#3b82f6')
const apaData   = makeDoughnut(apaVal,   TECHO_APA,   '#10b981')
const totalData = makeDoughnut(totalVal, TECHO_TOTAL, '#f59e0b')

</script>

<template>
  <div class="space-y-6 pb-12">

    <!-- ── Header ─────────────────────────────────── -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white/80 dark:bg-slate-900/60 backdrop-blur-md p-5 md:p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-white/5">
      <div class="flex items-center gap-4">
        <div class="p-3 md:p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl text-blue-600 shrink-0">
          <Activity class="w-7 h-7 md:w-8 md:h-8" />
        </div>
        <div>
          <h2 class="text-xl md:text-2xl font-bold text-slate-800 dark:text-white m-0">RACIONES DE ALIMENTOS</h2>
          <p class="text-slate-500 dark:text-slate-400 m-0 text-sm md:text-base">Dashboard Principal de Distribución Nacional</p>
        </div>
      </div>
      <!-- Acciones y Filtros -->
      <div class="flex flex-wrap gap-2">
        <button @click="activeTab = 'dashboard'" :class="activeTab === 'dashboard' ? 'bg-slate-800 text-white shadow-md' : 'bg-slate-50 dark:bg-slate-800/50 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:bg-slate-700/50'" class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm transition-all border border-slate-200 dark:border-white/10">
          <Activity class="w-4 h-4"/> Resumen
        </button>
        <button @click="activeTab = 'tabla'" :class="activeTab === 'tabla' ? 'bg-blue-600 text-white shadow-md border-blue-600' : 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/40 border-blue-200'" class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm transition-all border shadow-sm">
          <FileText class="w-4 h-4"/> Tabla y Carga
        </button>
        <button @click="activeTab = 'editar'" :class="activeTab === 'editar' ? 'bg-emerald-600 text-white shadow-md border-emerald-600' : 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/40 border-emerald-200'" class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm transition-all border shadow-sm">
          <Edit class="w-4 h-4"/> Edición
        </button>
        <button @click="activeTab = 'dapca'" :class="activeTab === 'dapca' ? 'bg-rose-600 text-white shadow-md border-rose-600' : 'bg-rose-50 dark:bg-rose-900/20 text-rose-700 hover:bg-rose-100 dark:bg-rose-900/40 border-rose-200'" class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm transition-all border shadow-sm">
          <Heart class="w-4 h-4"/> DAPCA
        </button>

        <button v-show="activeTab === 'dashboard'"
          @click="filtersOpen = !filtersOpen"
          class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm transition-all border"
          :class="hasFilters ? 'bg-blue-600 text-white border-blue-600 shadow-md' : 'bg-slate-50 dark:bg-slate-800/50 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-white/10 hover:bg-slate-100 dark:bg-slate-700/50'"
        >
          <Search class="w-4 h-4"/>
          Filtros
          <span v-if="hasFilters" class="bg-white text-white-700 text-xs font-bold px-1.5 py-0.5 rounded-full">{{ rawData.filtros_activos }}</span>
          <ChevronDown class="w-4 h-4 transition-transform" :class="filtersOpen ? 'rotate-180' : ''"/>
        </button>
      </div>
    </div>

    <!-- ── Panel de Filtros ──────────────────────── -->
    <Transition name="slide-down">
      <div v-if="filtersOpen && activeTab === 'dashboard'" class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl shadow-sm border border-slate-100 dark:border-white/5 p-5 md:p-6">
        <h3 class="font-bold text-slate-700 dark:text-slate-200 mb-4 flex items-center gap-2">
          <Search class="w-4 h-4 text-blue-500"/> Filtrar Estadísticas
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase block mb-1.5">Departamento</label>
            <select v-model="filterDept"
              class="w-full border-2 border-slate-200 dark:border-white/10 rounded-xl px-3 py-2.5 text-sm font-medium focus:outline-none focus:border-blue-500 transition-colors">
              <option value="">Todos</option>
              <option v-for="d in listas.departamentos" :key="d" :value="d">{{ d }}</option>
            </select>
          </div>
          <div>
            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase block mb-1.5">Municipio</label>
            <select v-model="filterMuni" :disabled="!filterDept"
              class="w-full border-2 border-slate-200 dark:border-white/10 rounded-xl px-3 py-2.5 text-sm font-medium focus:outline-none focus:border-blue-500 disabled:opacity-50 transition-colors">
              <option value="">Todos</option>
              <option v-for="m in listas.municipios" :key="m" :value="m">{{ m }}</option>
            </select>
          </div>
          <div>
            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase block mb-1.5 flex items-center gap-1"><Calendar class="w-3 h-3"/> Fecha Inicio</label>
            <input type="date" v-model="filterStart"
              class="w-full border-2 border-slate-200 dark:border-white/10 rounded-xl px-3 py-2.5 text-sm font-medium focus:outline-none focus:border-blue-500 transition-colors">
          </div>
          <div>
            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase block mb-1.5 flex items-center gap-1"><Calendar class="w-3 h-3"/> Fecha Fin</label>
            <input type="date" v-model="filterEnd"
              class="w-full border-2 border-slate-200 dark:border-white/10 rounded-xl px-3 py-2.5 text-sm font-medium focus:outline-none focus:border-blue-500 transition-colors">
          </div>
        </div>
        <div class="flex gap-3 mt-4 pt-4 border-t border-slate-100 dark:border-white/5">
          <button @click="fetchDashboardData"
            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-sm flex items-center gap-2 active:scale-95 transition-all shadow-md">
            <Search class="w-4 h-4"/> Aplicar Filtros
          </button>
          <button v-if="hasFilters" @click="clearFilters"
            class="px-5 py-2.5 bg-slate-100 dark:bg-slate-700/50 hover:bg-slate-200 text-slate-600 dark:text-slate-300 font-bold rounded-xl text-sm flex items-center gap-2 active:scale-95 transition-all">
            <X class="w-4 h-4"/> Limpiar
          </button>
        </div>
      </div>
    </Transition>

    <!-- Contenido Principal: Dashboard -->
    <div v-show="activeTab === 'dashboard'">
      <!-- ── Skeleton ───────────────────────────────── -->
      <div v-if="loading" class="animate-pulse space-y-6 mt-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div v-for="i in 4" :key="i" class="h-36 bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl border border-slate-100 dark:border-white/5"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="i in 4" :key="i" class="h-72 bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl border border-slate-100 dark:border-white/5"></div>
        </div>
      </div>

      <template v-else>

      <!-- ── Hero Cards (4 principales) ───────────── -->
      <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 md:gap-6">

        <!-- ASISTENCIA ALIMENTARIA -->
        <div class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl shadow-sm border border-slate-100 dark:border-white/5 p-4 md:p-6 flex flex-col items-center justify-center text-center hover:-translate-y-1 transition-transform duration-200">
          <div class="relative w-24 h-24 md:w-28 md:h-28 mb-2">
            <Doughnut :data="aaData" :options="reservaDoughnutOpts" />
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <span class="text-base md:text-lg font-black text-blue-700">{{ pctAA }}%</span>
            </div>
          </div>
          <p class="text-2xl md:text-4xl font-extrabold text-slate-800 dark:text-white mb-1">{{ formatNum(stats.total_aa_r) }}</p>
          <h3 class="text-[11px] md:text-[13px] uppercase tracking-wider font-bold text-slate-500 dark:text-slate-400 mb-1">ASISTENCIA ALIMENTARIA</h3>
          <p class="text-xs text-slate-400">Techo: 691,547</p>
        </div>

        <!-- ALIMENTOS POR ACCIONES -->
        <div class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl shadow-sm border border-slate-100 dark:border-white/5 p-4 md:p-6 flex flex-col items-center justify-center text-center hover:-translate-y-1 transition-transform duration-200">
          <div class="relative w-24 h-24 md:w-28 md:h-28 mb-2">
            <Doughnut :data="apaData" :options="reservaDoughnutOpts" />
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <span class="text-base md:text-lg font-black text-emerald-700">{{ pctAPA }}%</span>
            </div>
          </div>
          <p class="text-2xl md:text-4xl font-extrabold text-slate-800 dark:text-white mb-1">{{ formatNum(stats.total_apa_f) }}</p>
          <h3 class="text-[11px] md:text-[13px] uppercase tracking-wider font-bold text-slate-500 dark:text-slate-400 mb-1">ALIMENTOS POR ACCIONES</h3>
          <p class="text-xs text-slate-400">Techo: 232,508</p>
        </div>

        <!-- TOTAL RACIONES AA -->
        <div class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl shadow-sm border border-slate-100 dark:border-white/5 p-4 md:p-6 flex flex-col items-center justify-center text-center hover:-translate-y-1 transition-transform duration-200">
          <div class="relative w-24 h-24 md:w-28 md:h-28 mb-2">
            <Doughnut :data="totalData" :options="reservaDoughnutOpts" />
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <span class="text-base md:text-lg font-black text-amber-700">{{ pctTotal }}%</span>
            </div>
          </div>
          <p class="text-2xl md:text-4xl font-extrabold text-slate-800 dark:text-white mb-1">{{ formatNum(stats.total_aa_apa) }}</p>
          <h3 class="text-[11px] md:text-[13px] uppercase tracking-wider font-bold text-slate-500 dark:text-slate-400 mb-1">TOTAL RACIONES AA</h3>
          <p class="text-xs text-slate-400">Techo: 924,055</p>
        </div>

        <!-- RESERVA ESTRATÉGICA G.B. -->
        <div class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl shadow-sm border border-slate-100 dark:border-white/5 p-4 md:p-6 flex flex-col items-center justify-center text-center hover:-translate-y-1 transition-transform duration-200">
          <div class="relative w-24 h-24 md:w-28 md:h-28 mb-2">
            <Doughnut :data="reservaData" :options="reservaDoughnutOpts" />
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <span class="text-base md:text-lg font-black text-indigo-700">{{ reservaPct }}%</span>
            </div>
          </div>
          <p class="text-xl md:text-2xl font-extrabold text-slate-800 dark:text-white mb-1">{{ formatNum(reservaTotal) }}</p>
          <h3 class="text-[11px] md:text-[13px] uppercase tracking-wider font-bold text-slate-500 dark:text-slate-400 mb-1">RESERVA ESTRATÉGICA G.B.</h3>
          <p class="text-xs text-slate-400">Techo: 205,302</p>
        </div>

      </div>

      <!-- ── Cobertura ───────────────────────────── -->
      <div class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-md p-4 rounded-xl shadow-sm border border-slate-100 dark:border-white/5 flex flex-wrap gap-3 items-center justify-center">
        <div class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-blue-700 text-sm font-semibold">
          <MapPin class="w-4 h-4"/> {{ formatNum(stats.total_departamentos) }} departamentos
        </div>
        <div class="flex items-center gap-2 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg text-emerald-700 text-sm font-semibold">
          <Building class="w-4 h-4"/> {{ formatNum(stats.total_municipios) }} municipios
        </div>
        <div class="flex items-center gap-2 px-3 py-1.5 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-amber-700 text-sm font-semibold">
          <FlagIcon class="w-4 h-4"/>
          {{ stats.total_departamentos ? Math.round((Number(stats.total_departamentos) / 22) * 100) : 0 }}% cobertura nacional
        </div>
      </div>

      <!-- ── Tarjetas de detalle (INSAN, NDA, etc) ─ -->
      <h3 class="text-lg md:text-xl font-bold text-slate-800 dark:text-white pt-2">Resumen de Ejecución y Solicitudes</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">

        <!-- INSAN -->
        <div class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-xl shadow-sm border border-slate-100 dark:border-white/5 p-4 md:p-5 hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3 mb-4 text-orange-600">
            <div class="p-2 bg-orange-50 dark:bg-orange-900/20 rounded-lg"><AlertTriangle class="w-5 h-5"/></div>
            <h4 class="font-bold uppercase tracking-wider text-sm">INSAN</h4>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-3 text-center">
              <p class="text-xl md:text-2xl font-black text-slate-800 dark:text-white">{{ formatNum(stats.insan_r) }}</p>
              <p class="text-xs font-semibold uppercase text-orange-600">Raciones</p>
            </div>
            <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-3 text-center">
              <p class="text-xl md:text-2xl font-black text-slate-800 dark:text-white">{{ formatNum(stats.insan_f) }}</p>
              <p class="text-xs font-semibold uppercase text-orange-600">Familias</p>
            </div>
          </div>
        </div>

        <!-- Medida Transitoria -->
        <div class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-xl shadow-sm border border-slate-100 dark:border-white/5 p-4 md:p-5 hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3 mb-4 text-red-600">
            <div class="p-2 bg-red-50 dark:bg-red-900/20 rounded-lg"><Shield class="w-5 h-5"/></div>
            <h4 class="font-bold uppercase tracking-wider text-sm">MEDIDA TRANSITORIA</h4>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3 text-center">
              <p class="text-xl md:text-2xl font-black text-slate-800 dark:text-white">{{ formatNum(stats.medida_transitoria_r) }}</p>
              <p class="text-xs font-semibold uppercase text-red-600">Raciones</p>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3 text-center">
              <p class="text-xl md:text-2xl font-black text-slate-800 dark:text-white">{{ formatNum(stats.medida_transitoria_f) }}</p>
              <p class="text-xs font-semibold uppercase text-red-600">Familias</p>
            </div>
          </div>
        </div>

        <!-- NDA Nacional -->
        <div class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-xl shadow-sm border border-slate-100 dark:border-white/5 p-4 md:p-5 hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3 mb-4 text-indigo-600">
            <div class="p-2 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg"><Flag class="w-5 h-5"/></div>
            <h4 class="font-bold uppercase tracking-wider text-sm">NDA NACIONAL</h4>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-3 text-center">
              <p class="text-xl md:text-2xl font-black text-slate-800 dark:text-white">{{ formatNum(stats.nda_nacional_r) }}</p>
              <p class="text-xs font-semibold uppercase text-indigo-600">Raciones</p>
            </div>
            <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-3 text-center">
              <p class="text-xl md:text-2xl font-black text-slate-800 dark:text-white">{{ formatNum(stats.nda_nacional_f) }}</p>
              <p class="text-xs font-semibold uppercase text-indigo-600">Familias</p>
            </div>
          </div>
        </div>

        <!-- Medida Cautelar -->
        <div class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-xl shadow-sm border border-slate-100 dark:border-white/5 p-4 md:p-5 hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3 mb-4 text-purple-600">
            <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg"><Gavel class="w-5 h-5"/></div>
            <h4 class="font-bold uppercase tracking-wider text-sm">MEDIDA CAUTELAR</h4>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3 text-center">
              <p class="text-xl md:text-2xl font-black text-slate-800 dark:text-white">{{ formatNum(stats.medida_cautelar_r) }}</p>
              <p class="text-xs font-semibold uppercase text-purple-600">Raciones</p>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3 text-center">
              <p class="text-xl md:text-2xl font-black text-slate-800 dark:text-white">{{ formatNum(stats.medida_cautelar_f) }}</p>
              <p class="text-xs font-semibold uppercase text-purple-600">Familias</p>
            </div>
          </div>
        </div>

        <!-- APA -->
        <div class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-xl shadow-sm border border-slate-100 dark:border-white/5 p-4 md:p-5 hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3 mb-4 text-emerald-600">
            <div class="p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg"><Sprout class="w-5 h-5"/></div>
            <h4 class="font-bold uppercase tracking-wider text-sm">ALIMENTOS POR ACCIONES</h4>
          </div>
          <div class="flex items-center justify-center py-2">
            <div class="text-center">
              <p class="text-3xl font-black text-slate-800 dark:text-white">{{ formatNum(stats.total_apa_f) }}</p>
              <p class="text-xs font-semibold text-emerald-600 uppercase mt-1">Total APA</p>
            </div>
          </div>
        </div>

        <!-- RESERVA ESTRATÉGICA - Estilo consistente con otros cards -->
        <div class="sm:col-span-2 lg:col-span-3 bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl shadow-sm border border-slate-100 dark:border-white/5 overflow-hidden hover:shadow-md transition-shadow">

          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-white/5">
            <div class="flex items-center gap-3 text-violet-600">
              <div class="p-2 bg-violet-50 dark:bg-violet-900/20 rounded-lg"><Warehouse class="w-5 h-5"/></div>
              <h4 class="font-bold uppercase tracking-wider text-sm">RESERVA ESTRATÉGICA</h4>
            </div>
            <span class="text-[10px] uppercase font-bold text-violet-600 bg-violet-50 dark:bg-violet-900/20 px-3 py-1 rounded-full flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-violet-50 dark:bg-violet-900/200 animate-pulse inline-block"></span>
              Actualizado
            </span>
          </div>

          <!-- Contenido -->
          <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-100">

            <!-- DISPONIBILIDAD -->
            <div class="p-5 md:p-6">
              <div class="flex items-center gap-2 mb-4">
                <div class="p-1.5 bg-emerald-50 dark:bg-emerald-900/20 rounded-md text-emerald-600">
                  <Package class="w-4 h-4"/>
                </div>
                <span class="text-xs uppercase font-bold tracking-wider text-slate-500 dark:text-slate-400">DISPONIBILIDAD</span>
              </div>
              <div class="flex flex-wrap gap-3">
                <!-- Disponible total -->
                <div class="flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl px-4 py-3 flex-1 min-w-[130px]">
                  <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-emerald-600 shrink-0">
                    <Package class="w-5 h-5"/>
                  </div>
                  <div>
                    <p class="text-2xl font-black text-slate-800 dark:text-white">{{ formatNum(205302) }}</p>
                    <p class="text-xs text-emerald-600 uppercase font-semibold">DISPONIBILIDAD</p>
                  </div>
                </div>
                <!-- Entregadas -->
                <div class="flex items-center gap-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl px-4 py-3 flex-1 min-w-[130px]">
                  <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-blue-600 shrink-0">
                    <Warehouse class="w-5 h-5"/>
                  </div>
                  <div>
                    <p class="text-2xl font-black text-slate-800 dark:text-white">{{ formatNum(reservaTotal) }}</p>
                    <p class="text-xs text-blue-600 uppercase font-semibold">ENTREGADAS</p>
                  </div>
                </div>
              </div>
              <!-- Barra de progreso -->
              <div class="mt-4">
                <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400 mb-1.5 font-semibold">
                  <span>Avance</span>
                  <span class="text-violet-600 font-bold">{{ reservaPct }}%</span>
                </div>
                <div class="h-2.5 bg-slate-100 dark:bg-slate-700/50 rounded-full overflow-hidden">
                  <div class="h-full bg-gradient-to-r from-violet-500 to-blue-500 rounded-full transition-all duration-1000"
                    :style="`width: ${Math.min(100, reservaPct)}%`"></div>
                </div>
              </div>
            </div>

            <!-- BODEGAS -->
            <div class="p-5 md:p-6">
              <div class="flex items-center gap-2 mb-4">
                <div class="p-1.5 bg-amber-50 dark:bg-amber-900/20 rounded-md text-amber-600">
                  <MapPin class="w-4 h-4"/>
                </div>
                <span class="text-xs uppercase font-bold tracking-wider text-slate-500 dark:text-slate-400">BODEGAS</span>
              </div>
              <div class="flex flex-wrap gap-3">
                <!-- TACTIC -->
                <div class="flex items-center gap-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl px-4 py-3 flex-1 min-w-[100px]">
                  <div class="w-9 h-9 rounded-lg bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center text-amber-600 shrink-0">
                    <MapPin class="w-4 h-4"/>
                  </div>
                  <div>
                    <p class="text-xl font-black text-slate-800 dark:text-white">1,000</p>
                    <p class="text-[10px] font-bold uppercase text-amber-600">TACTIC</p>
                  </div>
                </div>
                <!-- RETALHULEU -->
                <div class="flex items-center gap-3 bg-rose-50 dark:bg-rose-900/20 rounded-xl px-4 py-3 flex-1 min-w-[100px]">
                  <div class="w-9 h-9 rounded-lg bg-rose-100 dark:bg-rose-900/40 flex items-center justify-center text-rose-600 shrink-0">
                    <MapPin class="w-4 h-4"/>
                  </div>
                  <div>
                    <p class="text-xl font-black text-slate-800 dark:text-white">2,000</p>
                    <p class="text-[10px] font-bold uppercase text-rose-600">RETALHULEU</p>
                  </div>
                </div>
                <!-- VILLA NUEVA -->
                <div class="flex items-center gap-3 bg-cyan-50 dark:bg-cyan-900/20 rounded-xl px-4 py-3 flex-1 min-w-[100px]">
                  <div class="w-9 h-9 rounded-lg bg-cyan-100 dark:bg-cyan-900/40 flex items-center justify-center text-cyan-600 shrink-0">
                    <MapPin class="w-4 h-4"/>
                  </div>
                  <div>
                    <p class="text-xl font-black text-slate-800 dark:text-white">5,500</p>
                    <p class="text-[10px] font-bold uppercase text-cyan-600">VILLA NUEVA</p>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>

      <!-- ── Gráficas ─────────────────────────────── -->
      <div class="border-t border-slate-200 dark:border-white/10 pt-6">
        <h3 class="text-lg md:text-xl font-bold text-slate-800 dark:text-white mb-5 flex items-center gap-2">
          <BarChart2 class="w-5 h-5 text-blue-500"/> Análisis Visual de Datos
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-8">

          <!-- Top 10 Departamentos -->
          <div class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-md p-5 md:p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-white/5">
            <h4 class="font-bold text-sm text-slate-700 dark:text-slate-200 mb-1">Top 10 Departamentos</h4>
            <p class="text-xs text-slate-400 mb-4 uppercase tracking-wide">Total AA + APA distribuido</p>
            <div class="h-64 md:h-72">
              <Bar :data="top10DeptData" :options="baseChartOpts" />
            </div>
          </div>

          <!-- Top 15 Municipios -->
          <div class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-md p-5 md:p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-white/5">
            <h4 class="font-bold text-sm text-slate-700 dark:text-slate-200 mb-1">Top 15 Municipios</h4>
            <p class="text-xs text-slate-400 mb-4 uppercase tracking-wide">Mayor asistencia alimentaria</p>
            <div class="h-64 md:h-72">
              <Bar :data="top15MuniData" :options="baseChartOpts" />
            </div>
          </div>

          <!-- INSAN por Departamento -->
          <div class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-md p-5 md:p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-white/5">
            <h4 class="font-bold text-sm text-slate-700 dark:text-slate-200 mb-1">Top 10 INSAN</h4>
            <p class="text-xs text-slate-400 mb-4 uppercase tracking-wide">Inseguridad alimentaria por departamento</p>
            <div class="h-64 md:h-72">
              <Bar :data="top10InsanData" :options="baseChartOpts" />
            </div>
          </div>

          <!-- Ejecución Física (full width) -->
          <div class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-md p-5 md:p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-white/5 md:col-span-2">
            <h4 class="font-bold text-sm text-slate-700 dark:text-slate-200 mb-1">Ejecución Física por Departamento</h4>
            <p class="text-xs text-slate-400 mb-4 uppercase tracking-wide">Raciones AA vs Fondos APA</p>
            <div class="h-72 md:h-80">
              <Bar :data="ejecucionData" :options="baseChartOptsLegend" />
            </div>
          </div>

        </div>
      </div>

      </template>
    </div>

    <!-- Otras Pestañas -->
    <div v-if="activeTab === 'tabla'" class="mt-6 animate-fade-in">
      <Tabla />
    </div>
    <div v-if="activeTab === 'editar'" class="mt-6 animate-fade-in">
      <Editar />
    </div>
    <div v-if="activeTab === 'dapca'" class="mt-6 animate-fade-in">
      <Dapca />
    </div>

  </div>
</template>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.25s ease; }
.slide-down-enter-from, .slide-down-leave-to       { opacity: 0; transform: translateY(-8px); }
</style>
