<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'
import { FileDown, Search, Filter, Upload, X, CheckCircle, AlertCircle, ChevronLeft, ChevronRight, FileSpreadsheet, Building, MapPin, Eye, BarChart3, List } from 'lucide-vue-next'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Filler } from 'chart.js'
import { Bar, Line, Doughnut } from 'vue-chartjs'
import MapaMundial from './components/MapaMundial.vue'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Filler)

const activeTab = ref('registros')
const statsData = ref(null)
const loadingStats = ref(false)
const statsFechaDesde = ref('')
const statsFechaHasta = ref('')

const loading = ref(true)
const records = ref([])
const filtersData = ref({
  paises_origen: [],
  paises_procedencia: [],
  categorias: [],
  emisores: []
})

// Pagination & Filtering state
const page = ref(1)
const totalPages = ref(1)
const totalRecords = ref(0)
const perPage = ref(20)

const filterParams = ref({
  search: '',
  pais_origen: '',
  pais_procedencia: '',
  categoria: '',
  emisor: '',
  fechaDesde: '',
  fechaHasta: ''
})

// Modal Importación
const importModal = ref(false)
const importFile = ref(null)
const importLoading = ref(false)
const importResult = ref(null)

// Modal Detalle
const detailModal = ref(false)
const selectedRecord = ref(null)

const fetchFilters = async () => {
  try {
    const res = await api.get('/visar/importaciones?get_filters=true')
    if (res.data.success) {
      filtersData.value = {
        paises_origen: res.data.paises_origen,
        paises_procedencia: res.data.paises_procedencia,
        categorias: res.data.categorias,
        emisores: res.data.emisores
      }
    }
  } catch (err) {
    console.error('Error cargando filtros:', err)
  }
}

const fetchData = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: page.value,
      ...filterParams.value
    })
    const res = await api.get(`/visar/importaciones?${params.toString()}`)
    if (res.data.success) {
      records.value = res.data.records
      totalRecords.value = res.data.total_records
      totalPages.value = res.data.total_pages
      page.value = res.data.current_page
    }
  } catch (err) {
    console.error('Error cargando importaciones:', err)
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  loadingStats.value = true
  try {
    const params = new URLSearchParams({
      fechaDesde: statsFechaDesde.value,
      fechaHasta: statsFechaHasta.value
    })
    const res = await api.get(`/visar/importaciones/stats?${params.toString()}`)
    if (res.data.success) {
      statsData.value = res.data.data
    }
  } catch (err) {
    console.error('Error cargando estadísticas:', err)
  } finally {
    loadingStats.value = false
  }
}

onMounted(() => {
  fetchFilters()
  fetchData()
  fetchStats()
  window.addEventListener('keydown', (e) => { 
    if (e.key === 'Escape') {
      importModal.value = false
      detailModal.value = false
    }
  })
})

// Chart Data Computed Properties
const chartMesesData = computed(() => {
  if (!statsData.value?.por_mes) return null
  return {
    labels: statsData.value.por_mes.map(m => m.mes),
    datasets: [
      {
        label: 'Valor en Dólares ($)',
        data: statsData.value.por_mes.map(m => Number(m.total_valor) || 0),
        borderColor: '#10b981',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        tension: 0.4,
        fill: true,
        yAxisID: 'y'
      },
      {
        label: 'Cantidad',
        data: statsData.value.por_mes.map(m => Number(m.cantidad) || 0),
        borderColor: '#667eea',

        backgroundColor: 'rgba(102, 126, 234, 0.1)',
        tension: 0.4,
        fill: true,
        yAxisID: 'y1'
      }
    ]
  }
})

const chartCategoriasData = computed(() => {
  if (!statsData.value?.top_categorias) return null
  return {
    labels: statsData.value.top_categorias.map(c => c.categoria_producto),
    datasets: [{
      data: statsData.value.top_categorias.map(c => Number(c.total_valor) || 0),
      backgroundColor: ['#10b981', '#667eea', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6', '#ec4899', '#22c55e', '#fb923c', '#a855f7'],
      borderWidth: 2,
      borderColor: '#ffffff'
    }]
  }
})

const openDetail = async (id) => {
  try {
    const res = await api.get(`/visar/importaciones?id=${id}`)
    if (res.data.success) {
      selectedRecord.value = res.data.record
      detailModal.value = true
    }
  } catch (err) {
    console.error(err)
  }
}

// Pagination handlers
const nextPage = () => { if (page.value < totalPages.value) { page.value++; fetchData() } }
const prevPage = () => { if (page.value > 1) { page.value--; fetchData() } }

const applyFilters = () => {
  page.value = 1
  fetchData()
}

const resetFilters = () => {
  filterParams.value = { search: '', pais_origen: '', pais_procedencia: '', categoria: '', emisor: '', fechaDesde: '', fechaHasta: '' }
  page.value = 1
  fetchData()
}

// Importación
const onFileChange = (e) => {
  importFile.value = e.target.files[0] || null
  importResult.value = null
}

const doImport = async () => {
  if (!importFile.value) return
  importLoading.value = true
  importResult.value = null
  try {
    const fd = new FormData()
    fd.append('file', importFile.value)
    const res = await api.post('/visar/importaciones/importar', fd, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    importResult.value = { success: true, message: res.data.message }
    await fetchData()
  } catch (e) {
    importResult.value = { success: false, message: e.response?.data?.message || 'Error de conexión al importar.' }
  } finally {
    importLoading.value = false
  }
}
</script>

<template>
  <div class="space-y-6 pb-12">
    <!-- Header -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <router-link to="/admin/visar/dashboard" class="p-2 hover:bg-slate-100 text-slate-400 hover:text-slate-700 rounded-xl transition-colors" title="Volver al Panel VISAR">
          <ChevronLeft class="w-6 h-6"/>
        </router-link>
        <div class="p-4 bg-blue-50 rounded-xl text-blue-600">
          <FileDown class="w-8 h-8" />
        </div>
        <div>
          <h2 class="text-2xl font-bold text-slate-800 m-0">Importaciones</h2>
          <p class="text-slate-500 m-0 text-sm font-medium">Control de Certificados de Importación VISAR</p>
        </div>
      </div>
      <button
        @click="importModal = true; importResult = null; importFile = null"
        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl flex items-center gap-2 active:scale-95 transition-all shadow-md shadow-emerald-200"
      >
        <Upload class="w-4 h-4"/> Importar Excel
      </button>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 border-b border-slate-200">
      <button @click="activeTab = 'registros'" :class="{'border-b-2 border-emerald-600 text-emerald-600': activeTab === 'registros', 'text-slate-500 hover:text-slate-700': activeTab !== 'registros'}" class="px-5 py-3 font-bold flex items-center gap-2 transition-colors">
        <List class="w-5 h-5"/> Tabla de Registros
      </button>
      <button @click="activeTab = 'stats'" :class="{'border-b-2 border-emerald-600 text-emerald-600': activeTab === 'stats', 'text-slate-500 hover:text-slate-700': activeTab !== 'stats'}" class="px-5 py-3 font-bold flex items-center gap-2 transition-colors">
        <BarChart3 class="w-5 h-5"/> Estadísticas y Gráficas
      </button>
    </div>

    <div v-show="activeTab === 'registros'" class="space-y-6">
      <!-- Filtros -->
      <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-slate-700 flex items-center gap-2"><Filter class="w-4 h-4"/> Filtros de Búsqueda</h3>
        <button @click="resetFilters" class="text-sm font-bold text-blue-600 hover:text-blue-800 transition-colors">Limpiar Filtros</button>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div class="relative">
          <Search class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input v-model="filterParams.search" @keyup.enter="applyFilters" type="text" placeholder="Buscar empresa, importación, producto..." class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-sm">
        </div>
        
        <select v-model="filterParams.pais_origen" @change="applyFilters" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-sm text-slate-700">
          <option value="">Cualquier País Origen</option>
          <option v-for="p in filtersData.paises_origen" :key="p" :value="p">{{ p }}</option>
        </select>
        
        <select v-model="filterParams.categoria" @change="applyFilters" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-sm text-slate-700">
          <option value="">Cualquier Categoría</option>
          <option v-for="c in filtersData.categorias" :key="c" :value="c">{{ c }}</option>
        </select>

        <select v-model="filterParams.emisor" @change="applyFilters" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-sm text-slate-700">
          <option value="">Cualquier Emisor</option>
          <option v-for="e in filtersData.emisores" :key="e" :value="e">{{ e }}</option>
        </select>

        <div class="flex items-center gap-2">
          <input v-model="filterParams.fechaDesde" @change="applyFilters" type="date" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-sm text-slate-700">
          <span class="text-slate-400 font-medium">a</span>
          <input v-model="filterParams.fechaHasta" @change="applyFilters" type="date" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-sm text-slate-700">
        </div>

        <button @click="applyFilters" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition-all shadow-md active:scale-95">Buscar</button>
      </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
      <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
        <h3 class="font-bold text-slate-700 text-lg">Registros ({{ totalRecords }})</h3>
        <div class="flex items-center gap-2">
          <button @click="prevPage" :disabled="page === 1" class="p-1.5 rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50"><ChevronLeft class="w-5 h-5"/></button>
          <span class="text-sm font-bold text-slate-600">Pág {{ page }} / {{ totalPages }}</span>
          <button @click="nextPage" :disabled="page === totalPages || totalPages === 0" class="p-1.5 rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50"><ChevronRight class="w-5 h-5"/></button>
        </div>
      </div>
      
      <div class="overflow-x-auto min-h-[400px]">
        <table v-if="!loading && records.length > 0" class="w-full text-left border-collapse whitespace-nowrap">
          <thead>
            <tr class="text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200 bg-slate-50">
              <th class="p-4 font-semibold">No. Importación</th>
              <th class="p-4 font-semibold">Empresa</th>
              <th class="p-4 font-semibold">Fecha Emisión</th>
              <th class="p-4 font-semibold">Origen</th>
              <th class="p-4 font-semibold">Categoría</th>
              <th class="p-4 font-semibold">Producto</th>
              <th class="p-4 text-center font-semibold">Acción</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in records" :key="r.id" class="border-b border-slate-50 hover:bg-slate-50/80 transition-colors">
              <td class="p-4 font-bold text-emerald-600">{{ r.no_importacion }}</td>
              <td class="p-4">
                <div class="flex items-center gap-2">
                  <Building class="w-4 h-4 text-slate-400"/>
                  <span class="font-medium text-slate-700 truncate max-w-[200px]" :title="r.nombre_empresa">{{ r.nombre_empresa }}</span>
                </div>
              </td>
              <td class="p-4 font-medium text-slate-600">{{ r.fecha_emision_format }}</td>
              <td class="p-4">
                <div class="flex items-center gap-2">
                  <MapPin class="w-4 h-4 text-rose-400"/>
                  <span class="font-bold text-slate-700">{{ r.pais_origen }}</span>
                </div>
              </td>
              <td class="p-4 text-sm text-slate-600 truncate max-w-[150px]" :title="r.categoria_producto">{{ r.categoria_producto }}</td>
              <td class="p-4 text-sm text-slate-600 truncate max-w-[200px]" :title="r.producto">{{ r.producto }}</td>
              <td class="p-4 text-center">
                <button
                  @click="openDetail(r.id)"
                  class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-slate-200 hover:border-emerald-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg text-xs font-bold text-slate-600 shadow-sm transition-all duration-150 active:scale-95"
                >
                  <Eye class="w-3.5 h-3.5" /> Ver Detalle
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        
        <div v-else-if="loading" class="flex flex-col items-center justify-center py-20">
          <div class="animate-spin rounded-full h-10 w-10 border-4 border-emerald-200 border-t-emerald-600 mb-4"></div>
          <p class="text-slate-500 font-medium">Cargando registros...</p>
        </div>

        <div v-else class="flex flex-col items-center justify-center py-20 text-center">
          <FileDown class="w-16 h-16 text-slate-300 mb-4" />
          <p class="text-lg font-bold text-slate-600">No se encontraron resultados</p>
          <p class="text-slate-500">Intenta cambiar los filtros de búsqueda.</p>
        </div>
      </div>
    </div>
    </div>

    <!-- Pestaña Estadísticas -->
    <div v-if="activeTab === 'stats'" class="space-y-6">
      
      <!-- Filtro Fechas Estadísticas -->
      <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
          <div class="flex-1 max-w-lg">
            <h3 class="font-bold text-slate-700 flex items-center gap-2 mb-3"><Filter class="w-4 h-4"/> Rango de Fechas</h3>
            <div class="flex items-center gap-2">
              <input v-model="statsFechaDesde" type="date" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-sm text-slate-700">
              <span class="text-slate-400 font-medium">a</span>
              <input v-model="statsFechaHasta" type="date" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-medium text-sm text-slate-700">
            </div>
          </div>
          <div class="flex items-center gap-2 shrink-0">
             <button @click="() => { statsFechaDesde = ''; statsFechaHasta = ''; fetchStats(); }" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all shadow-sm active:scale-95">Limpiar</button>
             <button @click="fetchStats" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition-all shadow-md active:scale-95">Aplicar Filtro</button>
          </div>
        </div>
      </div>

      <div v-if="loadingStats" class="flex justify-center py-20">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-emerald-200 border-t-emerald-600"></div>
      </div>
      <div v-else-if="statsData" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- KPIs Principales -->
        <div class="col-span-1 lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5 gap-4">
          <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 hover:shadow-md transition-shadow overflow-hidden">
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl shrink-0"><FileSpreadsheet class="w-6 h-6"/></div>
            <div class="min-w-0 flex-1">
              <p class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider truncate" title="Total Registros">Total Registros</p>
              <p class="text-lg sm:text-xl font-black text-slate-800 truncate" :title="Number(statsData.kpis?.total_exportaciones || 0).toLocaleString()">{{ Number(statsData.kpis?.total_exportaciones || 0).toLocaleString() }}</p>
            </div>
          </div>
          <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 hover:shadow-md transition-shadow overflow-hidden">
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl shrink-0"><BarChart3 class="w-6 h-6"/></div>
            <div class="min-w-0 flex-1">
              <p class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider truncate" title="Valor Dólares Total">Valor Dólares Total</p>
              <p class="text-lg sm:text-xl font-black text-slate-800 truncate" :title="`$` + Number(statsData.kpis?.valor_total || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})">${{ Number(statsData.kpis?.valor_total || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</p>
            </div>
          </div>
          <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 hover:shadow-md transition-shadow overflow-hidden">
            <div class="p-3 bg-rose-50 text-rose-600 rounded-xl shrink-0"><List class="w-6 h-6"/></div>
            <div class="min-w-0 flex-1">
              <p class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider truncate" title="Peso Total (KG)">Peso Total (KG)</p>
              <p class="text-lg sm:text-xl font-black text-slate-800 truncate" :title="Number(statsData.kpis?.peso_total || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})">{{ Number(statsData.kpis?.peso_total || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</p>
            </div>
          </div>
          <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 hover:shadow-md transition-shadow overflow-hidden">
            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl shrink-0"><MapPin class="w-6 h-6"/></div>
            <div class="min-w-0 flex-1">
              <p class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider truncate" title="Países Origen">Países Origen</p>
              <p class="text-lg sm:text-xl font-black text-slate-800 truncate" :title="statsData.map_data?.length || 0">{{ statsData.map_data?.length || 0 }}</p>
            </div>
          </div>
          <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 hover:shadow-md transition-shadow overflow-hidden">
            <div class="p-3 bg-amber-50 text-amber-600 rounded-xl shrink-0"><Building class="w-6 h-6"/></div>
            <div class="min-w-0 flex-1">
              <p class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider truncate" title="Empresas">Empresas</p>
              <p class="text-lg sm:text-xl font-black text-slate-800 truncate" :title="Number(statsData.kpis?.total_empresas || 0).toLocaleString()">{{ Number(statsData.kpis?.total_empresas || 0).toLocaleString() }}</p>
            </div>
          </div>
        </div>

        <!-- Mapa Mundial -->
        <div class="col-span-1 lg:col-span-2">
            <MapaMundial :data="statsData.map_data" type="import" />
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
          <h3 class="font-bold text-slate-700 mb-4">Evolución por Mes</h3>
          <div class="h-[300px] w-full">
            <Line v-if="chartMesesData" :data="chartMesesData" :options="{responsive: true, maintainAspectRatio: false, scales: { y: { position: 'left'}, y1: { position: 'right', grid: {drawOnChartArea: false} } }}" />
          </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
          <h3 class="font-bold text-slate-700 mb-4">Top Categorías (Valor)</h3>
          <div class="h-[300px] w-full flex justify-center">
            <Doughnut v-if="chartCategoriasData" :data="chartCategoriasData" :options="{responsive: true, maintainAspectRatio: false}" />
          </div>
        </div>

      </div>
    </div>

    <!-- Modal Detalle -->
    <Transition name="modal">
      <div v-if="detailModal && selectedRecord" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="detailModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden flex flex-col max-h-[90vh]">
          <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50 shrink-0">
            <h3 class="font-bold text-lg text-slate-800 flex items-center gap-2">
              <Eye class="w-5 h-5 text-emerald-500"/> Detalle de Importación
            </h3>
            <button @click="detailModal = false" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
              <X class="w-5 h-5"/>
            </button>
          </div>
          <div class="p-6 overflow-y-auto space-y-6">
            <div class="flex justify-between items-start border-b border-slate-100 pb-4">
              <div>
                <p class="text-xs font-bold tracking-widest text-slate-400 uppercase">Empresa</p>
                <h4 class="text-2xl font-black text-slate-800">{{ selectedRecord.nombre_empresa }}</h4>
                <p class="text-sm text-slate-600 font-medium mt-1">
                  Producto: {{ selectedRecord.producto }} ({{ selectedRecord.categoria_producto }})
                </p>
              </div>
              <div class="text-right">
                <p class="text-xs font-bold tracking-widest text-slate-400 uppercase">No. Importación</p>
                <p class="text-xl text-emerald-700 font-bold">{{ selectedRecord.no_importacion }}</p>
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h5 class="text-sm font-bold text-slate-700 border-b pb-2 mb-3">Información General</h5>
                <div class="space-y-2 text-sm">
                  <p><span class="font-bold text-slate-500">Fecha Emisión:</span> {{ selectedRecord.fecha_emision_format }}</p>
                  <p><span class="font-bold text-slate-500">Emisor:</span> {{ selectedRecord.emisor }}</p>
                  <p><span class="font-bold text-slate-500">Sistema:</span> {{ selectedRecord.sistema }}</p>
                  <p><span class="font-bold text-slate-500">Recibo Electrónico:</span> {{ selectedRecord.no_recibo_electronico }}</p>
                  <p><span class="font-bold text-slate-500">No. Transacción:</span> {{ selectedRecord.no_transaccion }}</p>
                  <p><span class="font-bold text-slate-500">Aduana:</span> {{ selectedRecord.aduana }}</p>
                  <p><span class="font-bold text-slate-500">Consignatario:</span> {{ selectedRecord.consignatario }}</p>
                </div>
              </div>
              <div>
                <h5 class="text-sm font-bold text-slate-700 border-b pb-2 mb-3">Detalles del Producto</h5>
                <div class="space-y-2 text-sm">
                  <p><span class="font-bold text-slate-500">País Origen:</span> {{ selectedRecord.pais_origen }}</p>
                  <p><span class="font-bold text-slate-500">País Procedencia:</span> {{ selectedRecord.pais_procedencia }}</p>
                  <p><span class="font-bold text-slate-500">Peso Neto:</span> {{ selectedRecord.peso_neto_format }}</p>
                  <p><span class="font-bold text-slate-500">Valor Dólares ({{ selectedRecord.tipo_valor }}):</span> ${{ selectedRecord.valor_dolares_format }}</p>
                  <p><span class="font-bold text-slate-500">No. Bultos:</span> {{ selectedRecord.no_bultos }}</p>
                  <p><span class="font-bold text-slate-500">Lote:</span> {{ selectedRecord.no_lote }}</p>
                  <p><span class="font-bold text-slate-500">Temperatura:</span> {{ selectedRecord.temperatura }}</p>
                </div>
              </div>
            </div>
            
            <div v-if="selectedRecord.observaciones" class="bg-slate-50 rounded-xl p-4 border border-slate-100">
              <h5 class="text-sm font-bold text-slate-700 mb-1">Observaciones</h5>
              <p class="text-sm text-slate-600">{{ selectedRecord.observaciones }}</p>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Modal Importar Excel -->
    <Transition name="modal">
      <div v-if="importModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="importModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
          <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-lg text-slate-800 flex items-center gap-2">
              <Upload class="w-5 h-5 text-emerald-500"/> Importar Importaciones
            </h3>
            <button @click="importModal = false" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
              <X class="w-5 h-5"/>
            </button>
          </div>

          <div class="p-6 space-y-5">
            <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 text-sm text-emerald-700">
              <p class="font-bold mb-1">Estructura del archivo:</p>
              <p>Sube el archivo Excel oficial de Importaciones. El sistema leerá los registros nuevos y los insertará a la base de datos.</p>
            </div>

            <div>
              <label class="block text-sm font-bold text-slate-700 mb-2">Seleccionar archivo (.xlsx o .xls)</label>
              <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-300 rounded-xl cursor-pointer hover:border-emerald-400 hover:bg-emerald-50/50 transition-all duration-150 group">
                <div class="flex flex-col items-center text-slate-400 group-hover:text-emerald-600">
                  <Upload class="w-8 h-8 mb-2"/>
                  <p v-if="!importFile" class="text-sm font-medium">Haz clic o arrastra el archivo</p>
                  <p v-else class="text-sm font-bold text-emerald-700">{{ importFile.name }}</p>
                </div>
                <input type="file" ref="fileInput" accept=".xlsx, .xls" class="hidden" @change="onFileChange"/>
              </label>
            </div>

            <Transition name="fade">
              <div v-if="importResult">
                <div v-if="!importResult.success" class="bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
                  <AlertCircle class="w-5 h-5 text-red-500 shrink-0 mt-0.5"/>
                  <p class="text-red-700 font-medium text-sm">{{ importResult.message }}</p>
                </div>
                <div v-else class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-start gap-3">
                  <CheckCircle class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"/>
                  <p class="text-emerald-700 font-bold text-sm">{{ importResult.message }}</p>
                </div>
              </div>
            </Transition>

            <button
              @click="doImport"
              :disabled="!importFile || importLoading"
              class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold rounded-xl flex items-center justify-center gap-2 transition-all active:scale-95 shadow-md"
            >
              <Upload class="w-5 h-5"/>
              {{ importLoading ? 'Procesando archivo...' : 'Importar a Base de Datos' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to      { opacity: 0; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to      { opacity: 0; }
</style>
