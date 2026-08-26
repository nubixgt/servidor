<script setup>
import { ref, onMounted, computed } from 'vue'
import ViderService from '@/services/vider/ViderService'
import { 
  Bar, Pie, Doughnut 
} from 'vue-chartjs'
import MapaLeaflet from './components/MapaLeaflet.vue'
import { 
  Chart as ChartJS, Title, Tooltip, Legend, BarElement, 
  CategoryScale, LinearScale, ArcElement, PointElement, LineElement 
} from 'chart.js'
import { 
  Users, TrendingUp, DollarSign, Map as MapIcon, 
  Building2, Filter, Download, ChevronRight,
  Activity, Settings2, LayoutGrid, FileText, Handshake, History,
  Info, PieChart, BarChart2, UserCheck, Search, ChevronDown,
  FileDown, ArrowLeft, X, Trophy, Target, BarChart
} from 'lucide-vue-next'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement, PointElement, LineElement)

const loading = ref(true)
const stats = ref(null)
const mapData = ref([])
const filtersOpen = ref(false)
const userRole = ref('PROFESIONAL')

try {
  const user = JSON.parse(localStorage.getItem('user'))
  if (user && user.role) {
    userRole.value = user.role
  }
} catch (e) {}

const catalogos = ref({
  departamentos: [],
  dependencias: [],
  actividades: [],
  productos: [],
  intervenciones: []
})

const filtros = ref({
  departamento: '',
  dependencia_id: '',
  actividad_id: '',
  producto_id: '',
  intervencion_id: ''
})

const selectedAreaInfo = ref(null)

const loadCatalogos = async (dependenciaId = null) => {
  try {
    const response = await ViderService.getCatalogos(dependenciaId)
    if (response.success) {
      if (!dependenciaId) {
        catalogos.value = response.data
      } else {
        catalogos.value.actividades = response.data.actividades
        catalogos.value.productos = response.data.productos
        catalogos.value.intervenciones = response.data.intervenciones
      }
    }
  } catch (error) {
    console.error('Error loading catalogs:', error)
  }
}

const loadData = async () => {
  loading.value = true
  try {
    const statsRes = await ViderService.getDashboardStats(filtros.value)
    if (statsRes.success) {
      stats.value = statsRes.stats
      
      // Actualizar información del área seleccionada automáticamente
      if (filtros.value.departamento) {
        selectedAreaInfo.value = {
          name: filtros.value.departamento,
          total: stats.value.total_beneficiarios,
          hombres: stats.value.total_hombres,
          mujeres: stats.value.total_mujeres
        }
      } else {
        selectedAreaInfo.value = {
          name: 'Guatemala',
          total: stats.value.total_beneficiarios,
          hombres: stats.value.total_hombres,
          mujeres: stats.value.total_mujeres
        }
      }
    }

    const mapRes = await ViderService.getMapData(filtros.value)
    if (mapRes.success) {
      mapData.value = mapRes.data
    }
  } catch (error) {
    console.error('Error loading dashboard data:', error)
  } finally {
    loading.value = false
  }
}

const onDependenciaChange = () => {
  filtros.value.actividad_id = ''
  filtros.value.producto_id = ''
  filtros.value.intervencion_id = ''
  loadCatalogos(filtros.value.dependencia_id)
  loadData()
}

onMounted(() => {
  loadCatalogos()
  loadData()
})

const handleSelectDept = async (dept) => {
  if (!dept) {
    filtros.value.departamento = ''
  } else {
    filtros.value.departamento = dept.name
  }
  await loadData() 
}

const handleSelectMuni = (muni) => {
  if (muni) {
    selectedAreaInfo.value = {
      name: muni.name,
      total: muni.total,
      // Nota: Para municipios individuales no tenemos desglose de sexo en el mapa actual
      // pero podríamos obtenerlo del backend si fuera necesario
      hombres: 'N/D',
      mujeres: 'N/D'
    }
  }
}

const formatNum = (n) => new Intl.NumberFormat().format(Math.round(n || 0))
const formatCurrency = (n) => new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'GTQ', maximumFractionDigits: 0 }).format(n || 0)
const formatPercent = (n) => (n || 0).toFixed(1) + '%'

const hasFilters = computed(() => 
  filtros.value.departamento || filtros.value.dependencia_id || 
  filtros.value.actividad_id || filtros.value.intervencion_id
)

const clearFilters = () => {
  filtros.value = {
    departamento: '',
    dependencia_id: '',
    actividad_id: '',
    producto_id: '',
    intervencion_id: ''
  }
  loadData()
}

// Chart Options
const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    }
  },
  scales: {
    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, border: { display: false } },
    x: { grid: { display: false }, border: { display: false } }
  }
}))

const chartDataDepto = computed(() => {
  if (!stats.value?.por_departamento) return null
  const sorted = [...stats.value.por_departamento].sort((a,b) => b.beneficiarios - a.beneficiarios).slice(0, 10)
  return {
    labels: sorted.map(d => d.nombre),
    datasets: [{
      label: 'Beneficiarios',
      data: sorted.map(d => d.beneficiarios),
      backgroundColor: 'rgba(99, 102, 241, 0.8)',
      borderRadius: 8
    }]
  }
})

const chartDataGenero = computed(() => {
  if (!stats.value) return null
  return {
    labels: ['Hombres', 'Mujeres'],
    datasets: [{
      data: [stats.value.total_hombres, stats.value.total_mujeres],
      backgroundColor: [
        'rgba(59, 130, 246, 0.8)',
        'rgba(236, 72, 153, 0.8)'
      ],
      borderWidth: 0
    }]
  }
})

const topDepartamentos = computed(() => {
  if (!stats.value?.por_departamento) return []
  return [...stats.value.por_departamento].sort((a,b) => b.beneficiarios - a.beneficiarios).slice(0, 5)
})

const topMetrics = computed(() => [
  { label: 'Beneficiarios', val: formatNum(stats.value?.total_beneficiarios), icon: Users, color: 'emerald', sub: filtros.value.departamento || 'Nacional' },
  { label: 'Hombres', val: formatNum(stats.value?.total_hombres), icon: Users, color: 'blue', sub: filtros.value.departamento || 'Nacional' },
  { label: 'Mujeres', val: formatNum(stats.value?.total_mujeres), icon: Users, color: 'pink', sub: filtros.value.departamento || 'Nacional' },
  { label: 'Departamentos', val: (stats.value?.total_departamentos || 0) + ' / 22', icon: MapIcon, color: 'amber', sub: 'Cobertura' },
  { label: 'Municipios', val: formatNum(stats.value?.total_municipios), icon: MapIcon, color: 'purple', sub: 'Atendidos' }
])
</script>

<template>
  <div class="space-y-6 pb-12 animate-fade-in">
    <!-- ── Header ─────────────────────────────────── -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-800 p-5 md:p-6 rounded-3xl border border-gray-100 dark:border-slate-700 shadow-sm">
      <div class="flex items-center gap-4">
        <div class="p-3 md:p-4 bg-indigo-500 text-white rounded-2xl shadow-lg shadow-indigo-500/20 shrink-0">
          <Activity class="w-7 h-7 md:w-8 md:h-8" />
        </div>
        <div>
          <h2 class="text-xl md:text-2xl font-black text-slate-800 dark:text-white m-0 tracking-tight uppercase">VIDER <span class="text-indigo-500">2025</span></h2>
          <p class="text-slate-500 dark:text-slate-400 m-0 text-sm md:text-base font-medium">Dashboard Principal de Desarrollo Rural</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <RouterLink to="/admin/dashboard" class="flex items-center gap-2 px-5 py-3 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-2xl font-bold text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-all shadow-sm">
          <ArrowLeft class="w-4 h-4"/>
          Volver al Inicio
        </RouterLink>
        <button
          @click="filtersOpen = !filtersOpen"
          class="flex items-center gap-2 px-5 py-3 rounded-2xl font-bold text-sm transition-all border shadow-sm"
          :class="hasFilters ? 'bg-indigo-600 text-white border-indigo-600 shadow-indigo-500/30' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700'"
        >
          <Search class="w-4 h-4"/>
          Filtros Avanzados
          <ChevronDown class="w-4 h-4 transition-transform" :class="filtersOpen ? 'rotate-180' : ''"/>
        </button>
      </div>
    </div>

    <!-- ── Accesos a Otros Módulos ──────────────── -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
      <RouterLink v-for="link in [
        { name: 'Dashboard', path: '/admin/vider/dashboard', icon: LayoutGrid, active: true, color: 'indigo' },
        { name: 'Datos', path: '/admin/vider/tabla', icon: FileText, color: 'blue' },
        ...(userRole.value === 'ADMIN' || userRole.value === 'TECNICO' ? [
            { name: 'Importar', path: '/admin/vider/importar', icon: Download, color: 'emerald' }
        ] : []),
        { name: 'Tobanik', path: '/admin/vider/tobanik', icon: Handshake, color: 'amber' },
        { name: 'Reportes', path: '/admin/vider/reportes', icon: FileDown, color: 'rose' },
        { name: 'Historial', path: '/admin/vider/historial', icon: History, color: 'purple' }
      ]" :key="link.name" :to="link.path" 
      class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-gray-100 dark:border-slate-700 flex flex-col items-center gap-3 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all group shadow-sm text-center"
      :class="link.active ? 'ring-2 ring-indigo-500 ring-offset-2 dark:ring-offset-slate-900 bg-white/90 dark:bg-slate-800/90' : ''">
        <div :class="[`p-3 bg-${link.color}-500/10 text-${link.color}-600 dark:text-${link.color}-400 rounded-xl group-hover:scale-110 transition-transform`]">
          <component :is="link.icon" class="w-6 h-6" />
        </div>
        <span class="text-[10px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-300">{{ link.name }}</span>
      </RouterLink>
    </div>

    <!-- ── Panel de Filtros (Siempre Visible) ──────────────────────── -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-gray-100 dark:border-slate-700 p-6">
      <div class="flex items-center gap-2 mb-6">
        <Filter class="w-4 h-4 text-indigo-500" />
        <h3 class="text-xs font-black uppercase tracking-widest text-slate-500">Filtros de Análisis</h3>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
         <div class="space-y-2">
           <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Dependencia</label>
           <select v-model="filtros.dependencia_id" @change="onDependenciaChange" class="w-full bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-700 rounded-xl px-4 py-3 text-sm font-bold focus:outline-none focus:border-indigo-500 dark:text-white transition-all">
             <option value="">Todas las Dependencias</option>
             <option v-for="dep in catalogos.dependencias" :key="dep.id" :value="dep.id">{{ dep.nombre }}</option>
           </select>
         </div>
         <div class="space-y-2">
           <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Actividad Técnica</label>
           <select v-model="filtros.actividad_id" @change="loadData" class="w-full bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-700 rounded-xl px-4 py-3 text-sm font-bold focus:outline-none focus:border-indigo-500 dark:text-white transition-all">
             <option value="">Todas las Actividades</option>
             <option v-for="act in catalogos.actividades" :key="act.id" :value="act.id">{{ act.nombre }}</option>
           </select>
         </div>
         <div class="space-y-2">
           <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Producto</label>
           <select v-model="filtros.producto_id" @change="loadData" class="w-full bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-700 rounded-xl px-4 py-3 text-sm font-bold focus:outline-none focus:border-indigo-500 dark:text-white transition-all">
             <option value="">Todos los Productos</option>
             <option v-for="prod in catalogos.productos" :key="prod.id" :value="prod.id">{{ prod.nombre }}</option>
           </select>
         </div>
         <div class="space-y-2">
           <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Intervención</label>
           <select v-model="filtros.intervencion_id" @change="loadData" class="w-full bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-700 rounded-xl px-4 py-3 text-sm font-bold focus:outline-none focus:border-indigo-500 dark:text-white transition-all">
             <option value="">Todas las Intervenciones</option>
             <option v-for="int in catalogos.intervenciones" :key="int.id" :value="int.id">{{ int.nombre }}</option>
           </select>
         </div>
      </div>
      <div v-if="hasFilters" class="flex gap-4 mt-8 pt-6 border-t border-slate-100 dark:border-slate-700">
        <button @click="clearFilters" class="px-6 py-3 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-200 font-black rounded-xl text-xs uppercase tracking-widest flex items-center gap-2 hover:bg-slate-200 dark:hover:bg-slate-600 transition-all">
          <X class="w-4 h-4"/> Limpiar Filtros
        </button>
      </div>
    </div>

    <!-- ── Top Metrics ──────────────────────────── -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
      <div v-for="(stat, i) in topMetrics" :key="i" class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl border border-white/80 dark:border-slate-700 p-5 rounded-3xl shadow-sm">
        <div class="flex items-center gap-3 mb-2">
          <div :class="[`p-2 rounded-xl bg-${stat.color}-500/10 text-${stat.color}-500`]">
            <component :is="stat.icon" class="w-5 h-5"/>
          </div>
          <div class="flex flex-col">
            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ stat.label }}</span>
            <span class="text-[8px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-tighter">{{ stat.sub }}</span>
          </div>
        </div>
        <h2 class="text-2xl font-black text-slate-800 dark:text-white">{{ loading ? '...' : stat.val }}</h2>
      </div>
    </div>

    <!-- ── Físico & Financiero Grid ──────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Físico -->
      <div class="bg-white/60 dark:bg-slate-800/60 backdrop-blur-xl rounded-[2rem] border border-white/80 dark:border-slate-700 p-8 shadow-sm">
        <h3 class="flex items-center gap-3 text-lg font-black text-slate-800 dark:text-white uppercase mb-6">
          <Target class="w-5 h-5 text-emerald-500"/> Ejecución Física
        </h3>
        <div class="grid grid-cols-3 gap-4 mb-6">
          <div class="bg-emerald-500 text-white p-3 rounded-xl text-center text-xs font-bold uppercase tracking-widest">Planificado</div>
          <div class="bg-amber-500 text-white p-3 rounded-xl text-center text-xs font-bold uppercase tracking-widest">Ejecutado</div>
          <div class="bg-blue-500 text-white p-3 rounded-xl text-center text-xs font-bold uppercase tracking-widest">Porcentaje</div>
        </div>
        <div class="space-y-4">
          <div v-for="item in [
            { label: 'Personas', plan: stats?.fisico?.personas?.planificado, ejec: stats?.fisico?.personas?.ejecutado },
            { label: 'Hectáreas', plan: stats?.fisico?.hectareas?.planificado, ejec: stats?.fisico?.hectareas?.ejecutado },
            { label: 'Metros', plan: stats?.fisico?.metros?.planificado, ejec: stats?.fisico?.metros?.ejecutado },
            { label: 'Metros²', plan: stats?.fisico?.m2?.planificado, ejec: stats?.fisico?.m2?.ejecutado }
          ]" :key="item.label" class="grid grid-cols-3 gap-4">
            <div class="p-4 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-2xl border border-emerald-500/10">
              <span class="block text-[9px] font-black text-slate-400 uppercase mb-1">{{ item.label }}</span>
              <span class="text-lg font-black text-emerald-600 dark:text-emerald-400">{{ formatNum(item.plan) }}</span>
            </div>
            <div class="p-4 bg-amber-500/5 dark:bg-amber-500/10 rounded-2xl border border-amber-500/10">
              <span class="block text-[9px] font-black text-slate-400 uppercase mb-1">{{ item.label }}</span>
              <span class="text-lg font-black text-amber-600 dark:text-amber-400">{{ formatNum(item.ejec) }}</span>
            </div>
            <div class="p-4 bg-blue-500/5 dark:bg-blue-500/10 rounded-2xl border border-blue-500/10">
              <span class="block text-[9px] font-black text-slate-400 uppercase mb-1">{{ item.label }}</span>
              <span class="text-lg font-black text-blue-600 dark:text-blue-400">{{ item.plan > 0 ? formatPercent((item.ejec / item.plan) * 100) : '0%' }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Financiero -->
      <div class="bg-white/60 dark:bg-slate-800/60 backdrop-blur-xl rounded-[2rem] border border-white/80 dark:border-slate-700 p-8 shadow-sm flex flex-col">
        <h3 class="flex items-center gap-3 text-lg font-black text-slate-800 dark:text-white uppercase mb-6">
          <DollarSign class="w-5 h-5 text-indigo-500"/> Ejecución Financiera
        </h3>
        <div class="grid grid-cols-3 gap-6 mb-8">
          <div class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-3xl border border-slate-100 dark:border-slate-700 text-center">
            <div class="text-xl font-black text-indigo-600 dark:text-indigo-400 mb-1">{{ formatCurrency(stats?.total_vigente) }}</div>
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Vigente</span>
          </div>
          <div class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-3xl border border-slate-100 dark:border-slate-700 text-center">
            <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 mb-1">{{ formatCurrency(stats?.total_financiero_ejecutado) }}</div>
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Ejecutado</span>
          </div>
          <div class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-3xl border border-slate-100 dark:border-slate-700 text-center">
            <div class="text-xl font-black text-blue-600 dark:text-blue-400 mb-1">{{ stats?.total_vigente > 0 ? formatPercent((stats?.total_financiero_ejecutado / stats?.total_vigente) * 100) : '0%' }}</div>
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">% Avance</span>
          </div>
        </div>
        <div class="flex-1 flex flex-col justify-center gap-4">
          <div class="h-6 bg-slate-100 dark:bg-slate-900 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700">
            <div 
              class="h-full bg-gradient-to-r from-indigo-500 to-blue-500 transition-all duration-1000 flex items-center justify-center text-[10px] font-black text-white"
              :style="{ width: (stats?.total_vigente > 0 ? (stats?.total_financiero_ejecutado / stats?.total_vigente) * 100 : 0) + '%' }"
            >
              {{ stats?.total_vigente > 0 ? formatPercent((stats?.total_financiero_ejecutado / stats?.total_vigente) * 100) : '0%' }}
            </div>
          </div>
          <p class="text-[10px] font-bold text-slate-500 text-center uppercase tracking-widest italic">Avance relativo del presupuesto institucional ejecutado</p>
        </div>
      </div>
    </div>

    <!-- ── Resumen por Dependencia ──────────────── -->
    <div class="bg-white/60 dark:bg-slate-800/60 backdrop-blur-xl rounded-[2.5rem] border border-white/80 dark:border-slate-700 p-8 shadow-sm">
      <h3 class="flex items-center gap-3 text-lg font-black text-slate-800 dark:text-white uppercase mb-8">
        <Building2 class="w-5 h-5 text-indigo-500"/> Resumen por Dependencia
      </h3>
      <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full">
          <thead>
            <tr class="border-b border-slate-100 dark:border-slate-700">
              <th class="pb-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Dependencia</th>
              <th class="pb-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Beneficiarios</th>
              <th class="pb-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Hombres</th>
              <th class="pb-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Mujeres</th>
              <th class="pb-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Programado</th>
              <th class="pb-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Ejecutado</th>
              <th class="pb-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">% Ejec.</th>
              <th class="pb-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Financiero</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
            <tr v-for="dep in stats?.por_dependencia" :key="dep.siglas" class="group hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-all">
              <td class="py-5 font-black text-slate-800 dark:text-white">
                <span class="px-3 py-1 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-lg text-xs">{{ dep.siglas }}</span>
              </td>
              <td class="py-5 text-center font-bold text-slate-600 dark:text-slate-300">{{ formatNum(dep.beneficiarios) }}</td>
              <td class="py-5 text-center text-slate-500 dark:text-slate-400 text-sm">{{ formatNum(dep.hombres) }}</td>
              <td class="py-5 text-center text-slate-500 dark:text-slate-400 text-sm">{{ formatNum(dep.mujeres) }}</td>
              <td class="py-5 text-center text-slate-500 dark:text-slate-400 text-sm">{{ formatNum(dep.programado) }}</td>
              <td class="py-5 text-center text-emerald-600 dark:text-emerald-400 font-bold">{{ formatNum(dep.ejecutado) }}</td>
              <td class="py-5 text-center">
                <span class="px-2 py-1 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-md text-[10px] font-black">
                  {{ dep.programado > 0 ? formatPercent((dep.ejecutado / dep.programado) * 100) : '0%' }}
                </span>
              </td>
              <td class="py-5 text-right font-black text-indigo-500">{{ formatCurrency(dep.fin_ejecutado) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── Main Section: Map & Info ─────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      <!-- Left: Map Container -->
      <div class="lg:col-span-8 flex flex-col gap-6">
        <div class="bg-white/40 dark:bg-slate-800/40 backdrop-blur-md rounded-[2.5rem] border border-white/60 dark:border-slate-700 overflow-hidden shadow-sm h-[600px] relative">
          <MapaLeaflet :data="mapData" @select-dept="handleSelectDept" @select-muni="handleSelectMuni" />
        </div>
      </div>

      <!-- Right: Rankings & Charts -->
      <div class="lg:col-span-4 space-y-8">
        <!-- Top Departamentos -->
        <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl border border-white/80 dark:border-slate-700 rounded-[2.5rem] p-8 shadow-sm">
          <h3 class="flex items-center gap-3 text-sm font-black text-slate-800 dark:text-white uppercase mb-6 tracking-widest">
            <Trophy class="w-4 h-4 text-amber-500"/> Top Departamentos
          </h3>
          <div class="space-y-4">
            <div v-for="(dept, i) in topDepartamentos" :key="dept.nombre" class="flex items-center gap-4 group">
              <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-xs transition-all" 
                :class="i === 0 ? 'bg-amber-500 text-white' : i === 1 ? 'bg-slate-300 text-slate-700' : i === 2 ? 'bg-amber-700 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300'">
                {{ i + 1 }}
              </div>
              <div class="flex-1">
                <div class="flex justify-between items-center mb-1">
                  <span class="font-bold text-slate-700 dark:text-slate-200 uppercase text-xs">{{ dept.nombre }}</span>
                  <span class="font-black text-indigo-500 text-xs">{{ formatNum(dept.beneficiarios) }}</span>
                </div>
                <div class="h-1.5 bg-slate-100 dark:bg-slate-900 rounded-full overflow-hidden">
                  <div class="h-full bg-indigo-500 group-hover:bg-indigo-600 transition-all" :style="{ width: (dept.beneficiarios / topDepartamentos[0].beneficiarios * 100) + '%' }"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Dept Bar Chart -->
          <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl border border-white/80 dark:border-slate-700 rounded-[2.5rem] p-8 shadow-sm h-[350px]">
            <h3 class="flex items-center gap-3 text-[10px] font-black text-slate-400 uppercase mb-6 tracking-widest">
              <BarChart2 class="w-4 h-4 text-indigo-500"/> Cobertura por Depto.
            </h3>
            <div class="h-[220px]">
              <Bar v-if="chartDataDepto" :data="chartDataDepto" :options="chartOptions" />
            </div>
          </div>

          <!-- Gender Pie Chart -->
          <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl border border-white/80 dark:border-slate-700 rounded-[2.5rem] p-8 shadow-sm h-[350px]">
            <h3 class="flex items-center gap-3 text-[10px] font-black text-slate-400 uppercase mb-6 tracking-widest">
              <PieChart class="w-4 h-4 text-pink-500"/> Distribución de Género
            </h3>
            <div class="h-[220px] flex items-center justify-center">
              <Pie v-if="chartDataGenero" :data="chartDataGenero" :options="{ ...chartOptions, plugins: { legend: { display: true, position: 'bottom', labels: { usePointStyle: true, font: { size: 10, weight: 'bold' } } } } }" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.slide-down-enter-from, .slide-down-leave-to       { opacity: 0; transform: translateY(-10px); }

.animate-fade-in {
    animation: fadeIn 0.5s ease-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.1); border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); }

:deep(.map-tooltip) {
  background: rgba(15, 23, 42, 0.9) !important;
  backdrop-filter: blur(8px) !important;
  border: 1px solid rgba(255, 255, 255, 0.2) !important;
  border-radius: 12px !important;
  color: white !important;
  padding: 8px 12px !important;
  font-size: 10px !important;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
}

:deep(.leaflet-tooltip-top:before) {
  border-top-color: rgba(15, 23, 42, 0.9) !important;
}
</style>
