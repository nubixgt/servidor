<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'
import { FileDown, Search, Filter, Upload, X, CheckCircle, AlertCircle, ChevronLeft, ChevronRight, Leaf, Building, ShieldCheck, ShieldAlert, Shield, BarChart3, List } from 'lucide-vue-next'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Filler } from 'chart.js'
import { Bar, Line, Doughnut } from 'vue-chartjs'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Filler)

const activeTab = ref('registros')
const chartStatsData = ref(null)
const loadingStats = ref(false)

const loading = ref(true)
const records = ref([])
const stats = ref({})
const filtersData = ref({
  categorias: [],
  departamentos: [],
  municipios: {}
})

// Pagination & Filtering state
const page = ref(1)
const totalPages = ref(1)
const totalRecords = ref(0)
const perPage = ref(20)

const filterParams = ref({
  search: '',
  categoria: '',
  departamento: '',
  municipio: '',
  fechaDesde: '',
  fechaHasta: ''
})

// Modal Importación
const importModal = ref(false)
const importFile = ref(null)
const importLoading = ref(false)
const importResult = ref(null)

const fetchFiltersAndStats = async () => {
  try {
    const res = await api.get('/visar/licencias-fitosanitarias?get_filters=true')
    if (res.data.success) {
      filtersData.value = {
        categorias: res.data.categorias,
        departamentos: res.data.departamentos,
        municipios: res.data.municipios
      }
      stats.value = res.data.stats
    }
  } catch (err) {
    console.error('Error cargando filtros/estadísticas:', err)
  }
}

const fetchData = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: page.value,
      ...filterParams.value
    })
    const res = await api.get(`/visar/licencias-fitosanitarias?${params.toString()}`)
    if (res.data.success) {
      records.value = res.data.records
      totalRecords.value = res.data.total_records
      totalPages.value = res.data.total_pages
      page.value = res.data.current_page
    }
  } catch (err) {
    console.error('Error cargando licencias fitosanitarias:', err)
  } finally {
    loading.value = false
  }
}

const fetchChartStats = async () => {
  loadingStats.value = true
  try {
    const res = await api.get('/visar/licencias-fitosanitarias/stats')
    if (res.data.success) {
      chartStatsData.value = res.data.data
    }
  } catch (err) {
    console.error('Error cargando estadísticas fitosanitarias:', err)
  } finally {
    loadingStats.value = false
  }
}

onMounted(() => {
  fetchFiltersAndStats()
  fetchData()
  fetchChartStats()
  window.addEventListener('keydown', (e) => { 
    if (e.key === 'Escape') importModal.value = false 
  })
})

// Chart Data Computed Properties
const chartDeptosData = computed(() => {
  if (!chartStatsData.value?.graficas?.departamentos) return null
  return {
    labels: chartStatsData.value.graficas.departamentos.map(d => d.departamento),
    datasets: [{
      label: 'Licencias',
      data: chartStatsData.value.graficas.departamentos.map(d => d.total),
      backgroundColor: ['#8b5cf6', '#a78bfa', '#f093fb', '#f5576c', '#10b981', '#3b82f6', '#f59e0b', '#ec4899', '#22c55e', '#fb923c'],
      borderRadius: 4
    }]
  }
})

const chartMuniData = computed(() => {
  if (!chartStatsData.value?.graficas?.municipios) return null
  return {
    labels: chartStatsData.value.graficas.municipios.map(m => m.municipio),
    datasets: [{
      label: 'Licencias',
      data: chartStatsData.value.graficas.municipios.map(m => m.total),
      backgroundColor: '#10b981',
      borderRadius: 4
    }]
  }
})

const chartCatData = computed(() => {
  if (!chartStatsData.value?.graficas?.categorias) return null
  return {
    labels: chartStatsData.value.graficas.categorias.map(c => c.categoria),
    datasets: [{
      data: chartStatsData.value.graficas.categorias.map(c => c.total),
      backgroundColor: ['#8b5cf6', '#a78bfa', '#f093fb', '#f5576c', '#10b981', '#60a5fa', '#f59e0b', '#ef4444', '#ec4899', '#14b8a6', '#f97316', '#6366f1'],
      borderWidth: 2,
      borderColor: '#ffffff'
    }]
  }
})

const chartMesesData = computed(() => {
  if (!chartStatsData.value?.graficas?.meses) return null
  const mesesNombres = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic']
  return {
    labels: chartStatsData.value.graficas.meses.map(m => mesesNombres[m.mes - 1] || m.mes),
    datasets: [
      {
        label: 'Licencias Emitidas',
        data: chartStatsData.value.graficas.meses.map(m => m.total),
        borderColor: '#f093fb',
        backgroundColor: 'rgba(240, 147, 251, 0.1)',
        tension: 0.4,
        fill: true
      }
    ]
  }
})

// Pagination handlers
const nextPage = () => { if (page.value < totalPages.value) { page.value++; fetchData() } }
const prevPage = () => { if (page.value > 1) { page.value--; fetchData() } }

const applyFilters = () => {
  page.value = 1
  fetchData()
}

const onDepartamentoChange = () => {
  filterParams.value.municipio = ''
  applyFilters()
}

const resetFilters = () => {
  filterParams.value = { search: '', categoria: '', departamento: '', municipio: '', fechaDesde: '', fechaHasta: '' }
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
    const res = await api.post('/visar/licencias-fitosanitarias/importar', fd, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    importResult.value = { success: true, message: res.data.message }
    await fetchFiltersAndStats()
    await fetchData()
  } catch (e) {
    importResult.value = { success: false, message: e.response?.data?.message || 'Error de conexión al importar.' }
  } finally {
    importLoading.value = false
  }
}

const formatNum = (num) => num != null ? Number(num).toLocaleString() : '0'

const getStatusColor = (estado) => {
  if (estado === 'Vigente') return 'bg-emerald-100 text-emerald-700 border-emerald-200'
  if (estado === 'Vencida') return 'bg-red-100 text-red-700 border-red-200'
  return 'bg-slate-100 text-slate-700 border-slate-200'
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
        <div class="p-4 bg-teal-50 rounded-xl text-teal-600">
          <Leaf class="w-8 h-8" />
        </div>
        <div>
          <h2 class="text-2xl font-bold text-slate-800 m-0">Licencias Fitosanitarias</h2>
          <p class="text-slate-500 m-0 text-sm font-medium">Control de establecimientos y operadores</p>
        </div>
      </div>
      <button
        @click="importModal = true; importResult = null; importFile = null"
        class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-xl flex items-center gap-2 active:scale-95 transition-all shadow-md shadow-teal-200"
      >
        <Upload class="w-4 h-4"/> Importar Excel
      </button>
    </div>

    <!-- Stats Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-4 gap-6">
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 hover:-translate-y-1 transition-transform duration-200">
        <div class="p-4 bg-slate-50 text-slate-600 rounded-xl"><Shield class="w-8 h-8"/></div>
        <div>
          <p class="text-3xl font-bold text-slate-800">{{ formatNum(stats.total) }}</p>
          <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Emitidas</p>
        </div>
      </div>
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 hover:-translate-y-1 transition-transform duration-200">
        <div class="p-4 bg-emerald-50 text-emerald-600 rounded-xl"><ShieldCheck class="w-8 h-8"/></div>
        <div>
          <p class="text-3xl font-bold text-emerald-600">{{ formatNum(stats.vigentes) }}</p>
          <p class="text-xs font-bold text-emerald-500 uppercase tracking-wider">Licencias Vigentes</p>
        </div>
      </div>
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 hover:-translate-y-1 transition-transform duration-200">
        <div class="p-4 bg-red-50 text-red-600 rounded-xl"><ShieldAlert class="w-8 h-8"/></div>
        <div>
          <p class="text-3xl font-bold text-red-600">{{ formatNum(stats.vencidas) }}</p>
          <p class="text-xs font-bold text-red-500 uppercase tracking-wider">Licencias Vencidas</p>
        </div>
      </div>
      
      <!-- Top Depto (solo visible en pantallas grandes) -->
      <div v-if="stats.topDeptos?.length > 0" class="hidden xl:flex bg-white p-6 rounded-2xl shadow-sm border border-slate-100 items-center gap-4 hover:-translate-y-1 transition-transform duration-200">
        <div class="p-4 bg-indigo-50 text-indigo-600 rounded-xl"><Building class="w-8 h-8"/></div>
        <div>
          <p class="text-xl font-bold text-slate-800 truncate" :title="stats.topDeptos[0].departamento">{{ stats.topDeptos[0].departamento }}</p>
          <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mayor concentración ({{ stats.topDeptos[0].cantidad }})</p>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 border-b border-slate-200 mt-4">
      <button @click="activeTab = 'registros'" :class="{'border-b-2 border-teal-600 text-teal-600': activeTab === 'registros', 'text-slate-500 hover:text-slate-700': activeTab !== 'registros'}" class="px-5 py-3 font-bold flex items-center gap-2 transition-colors">
        <List class="w-5 h-5"/> Tabla de Registros
      </button>
      <button @click="activeTab = 'stats'" :class="{'border-b-2 border-teal-600 text-teal-600': activeTab === 'stats', 'text-slate-500 hover:text-slate-700': activeTab !== 'stats'}" class="px-5 py-3 font-bold flex items-center gap-2 transition-colors">
        <BarChart3 class="w-5 h-5"/> Estadísticas y Gráficas
      </button>
    </div>

    <div v-show="activeTab === 'registros'" class="space-y-6">
      <!-- Filtros -->
      <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-slate-700 flex items-center gap-2"><Filter class="w-4 h-4"/> Filtros de Búsqueda</h3>
        <button @click="resetFilters" class="text-sm font-bold text-teal-600 hover:text-teal-800 transition-colors">Limpiar Filtros</button>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="relative xl:col-span-1">
          <Search class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input v-model="filterParams.search" @keyup.enter="applyFilters" type="text" placeholder="Licencia, recibo, empresa..." class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all font-medium text-sm">
        </div>
        
        <select v-model="filterParams.categoria" @change="applyFilters" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all font-medium text-sm text-slate-700">
          <option value="">Categoría</option>
          <option v-for="c in filtersData.categorias" :key="c" :value="c">{{ c }}</option>
        </select>

        <select v-model="filterParams.departamento" @change="onDepartamentoChange" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all font-medium text-sm text-slate-700">
          <option value="">Cualquier Departamento</option>
          <option v-for="d in filtersData.departamentos" :key="d" :value="d">{{ d }}</option>
        </select>

        <select v-model="filterParams.municipio" @change="applyFilters" :disabled="!filterParams.departamento" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all font-medium text-sm text-slate-700 disabled:opacity-50">
          <option value="">Cualquier Municipio</option>
          <option v-if="filterParams.departamento" v-for="m in filtersData.municipios[filterParams.departamento]" :key="m" :value="m">{{ m }}</option>
        </select>
      </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
      <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
        <h3 class="font-bold text-slate-700 text-lg">Licencias Registradas ({{ formatNum(totalRecords) }})</h3>
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
              <th class="p-4 font-semibold">Licencia</th>
              <th class="p-4 font-semibold">Empresa / Propietario</th>
              <th class="p-4 font-semibold">Categoría</th>
              <th class="p-4 font-semibold">Ubicación</th>
              <th class="p-4 font-semibold text-center">Emisión</th>
              <th class="p-4 font-semibold text-center">Vencimiento</th>
              <th class="p-4 font-semibold text-center">Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in records" :key="r.id" class="border-b border-slate-50 hover:bg-slate-50/80 transition-colors">
              <td class="p-4 font-black text-teal-700">{{ r.licencia }}</td>
              <td class="p-4">
                <div class="flex flex-col">
                  <span class="font-bold text-slate-700 truncate max-w-[250px]" :title="r.nombre_empresa">{{ r.nombre_empresa }}</span>
                  <span class="text-xs text-slate-500 font-medium truncate max-w-[250px]">{{ r.propietario }}</span>
                </div>
              </td>
              <td class="p-4">
                <div class="flex flex-col">
                  <span class="text-sm font-bold text-slate-600">{{ r.categoria }}</span>
                  <span class="text-[10px] text-slate-400 uppercase tracking-widest">{{ r.clasificacion }}</span>
                </div>
              </td>
              <td class="p-4">
                <div class="flex flex-col">
                  <span class="font-medium text-slate-600 text-sm">{{ r.municipio }}</span>
                  <span class="text-xs text-slate-400">{{ r.departamento }}</span>
                </div>
              </td>
              <td class="p-4 text-center font-medium text-slate-600">{{ r.fecha_emision_format }}</td>
              <td class="p-4 text-center font-bold text-slate-700">{{ r.fecha_vencimiento_format }}</td>
              <td class="p-4 text-center">
                <span class="px-3 py-1 rounded-full text-xs font-black border uppercase tracking-wider" :class="getStatusColor(r.estado)">
                  {{ r.estado }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
        
        <div v-else-if="loading" class="flex flex-col items-center justify-center py-20">
          <div class="animate-spin rounded-full h-10 w-10 border-4 border-teal-200 border-t-teal-600 mb-4"></div>
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
      <div v-if="loadingStats" class="flex justify-center py-20">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-teal-200 border-t-teal-600"></div>
      </div>
      <div v-else-if="chartStatsData" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 lg:col-span-2">
          <h3 class="font-bold text-slate-700 mb-4">Evolución Emisión de Licencias (Este año)</h3>
          <div class="h-[300px] w-full">
            <Line v-if="chartMesesData" :data="chartMesesData" :options="{responsive: true, maintainAspectRatio: false, plugins: {legend: {display: false}}}" />
          </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
          <h3 class="font-bold text-slate-700 mb-4">Top Departamentos</h3>
          <div class="h-[300px] w-full">
            <Bar v-if="chartDeptosData" :data="chartDeptosData" :options="{responsive: true, maintainAspectRatio: false, plugins: {legend: {display: false}}}" />
          </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
          <h3 class="font-bold text-slate-700 mb-4">Top Municipios</h3>
          <div class="h-[300px] w-full">
            <Bar v-if="chartMuniData" :data="chartMuniData" :options="{indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: {legend: {display: false}}}" />
          </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 lg:col-span-2">
          <h3 class="font-bold text-slate-700 mb-4">Distribución de Licencias por Categoría</h3>
          <div class="h-[350px] w-full flex justify-center">
            <Doughnut v-if="chartCatData" :data="chartCatData" :options="{responsive: true, maintainAspectRatio: false}" />
          </div>
        </div>

      </div>
    </div>

    <!-- Modal Importar Excel -->
    <Transition name="modal">
      <div v-if="importModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="importModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
          <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-lg text-slate-800 flex items-center gap-2">
              <Upload class="w-5 h-5 text-teal-500"/> Importar Licencias Fitosanitarias
            </h3>
            <button @click="importModal = false" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
              <X class="w-5 h-5"/>
            </button>
          </div>

          <div class="p-6 space-y-5">
            <div class="bg-teal-50 border border-teal-100 rounded-xl p-4 text-sm text-teal-800">
              <p class="font-bold mb-1">Carga Masiva</p>
              <p>Sube el archivo Excel oficial. Asegúrate de tener los formatos de fecha correctos para determinar la vigencia y estatus de las licencias.</p>
            </div>

            <div>
              <label class="block text-sm font-bold text-slate-700 mb-2">Seleccionar archivo (.xlsx o .xls)</label>
              <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-300 rounded-xl cursor-pointer hover:border-teal-400 hover:bg-teal-50/50 transition-all duration-150 group">
                <div class="flex flex-col items-center text-slate-400 group-hover:text-teal-600">
                  <Upload class="w-8 h-8 mb-2"/>
                  <p v-if="!importFile" class="text-sm font-medium">Haz clic o arrastra el archivo</p>
                  <p v-else class="text-sm font-bold text-teal-700">{{ importFile.name }}</p>
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
              class="w-full py-3 bg-teal-600 hover:bg-teal-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold rounded-xl flex items-center justify-center gap-2 transition-all active:scale-95 shadow-md"
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
