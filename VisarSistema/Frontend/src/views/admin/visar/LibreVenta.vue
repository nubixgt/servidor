<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'
import { FileDown, Search, Filter, Upload, X, CheckCircle, AlertCircle, ChevronLeft, ChevronRight, FileBadge, Building, MapPin, BarChart3, List } from 'lucide-vue-next'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Filler } from 'chart.js'
import { Bar, Line, Doughnut, Pie } from 'vue-chartjs'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Filler)

const activeTab = ref('registros')
const chartStatsData = ref(null)
const loadingStats = ref(false)

const loading = ref(true)
const records = ref([])
const filtersData = ref({
  categorias: [],
  paises: [],
  emisores: []
})

// Pagination & Filtering state
const page = ref(1)
const totalPages = ref(1)
const totalRecords = ref(0)
const perPage = ref(20)

const filterParams = ref({
  search: '',
  categoria: '',
  pais: '',
  emisor: '',
  fechaDesde: '',
  fechaHasta: ''
})

// Modal Importación
const importModal = ref(false)
const importFile = ref(null)
const importLoading = ref(false)
const importResult = ref(null)

const fetchFilters = async () => {
  try {
    const res = await api.get('/visar/libre-venta?get_filters=true')
    if (res.data.success) {
      filtersData.value = {
        categorias: res.data.categorias,
        paises: res.data.paises,
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
    const res = await api.get(`/visar/libre-venta?${params.toString()}`)
    if (res.data.success) {
      records.value = res.data.records
      totalRecords.value = res.data.total_records
      totalPages.value = res.data.total_pages
      page.value = res.data.current_page
    }
  } catch (err) {
    console.error('Error cargando libre venta:', err)
  } finally {
    loading.value = false
  }
}

const fetchChartStats = async () => {
  loadingStats.value = true
  try {
    const res = await api.get('/visar/libre-venta/stats')
    if (res.data.success) {
      chartStatsData.value = res.data.data
    }
  } catch (err) {
    console.error('Error cargando estadísticas de libre venta:', err)
  } finally {
    loadingStats.value = false
  }
}

onMounted(() => {
  fetchFilters()
  fetchData()
  fetchChartStats()
  window.addEventListener('keydown', (e) => { if (e.key === 'Escape') importModal.value = false })
})

// Chart Data Computed Properties
const chartPaisesData = computed(() => {
  if (!chartStatsData.value?.top_paises) return null
  return {
    labels: chartStatsData.value.top_paises.map(p => p.pais_destino),
    datasets: [{
      label: 'Certificados',
      data: chartStatsData.value.top_paises.map(p => p.total_exportaciones),
      backgroundColor: ['#fa709a', '#fee140', '#4facfe', '#43e97b', '#fa8bff', '#2af598', '#09f1d1', '#ffcc29', '#ff6b6b', '#4ecdc4'],
      borderWidth: 0
    }]
  }
})

const chartCategoriasData = computed(() => {
  if (!chartStatsData.value?.top_categorias) return null
  return {
    labels: chartStatsData.value.top_categorias.map(c => c.categoria_producto),
    datasets: [{
      data: chartStatsData.value.top_categorias.map(c => c.total_exportaciones),
      backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe', '#43e97b', '#38f9d7', '#fa709a', '#fee140'],
      borderWidth: 2,
      borderColor: '#ffffff'
    }]
  }
})

const chartMesesData = computed(() => {
  if (!chartStatsData.value?.tendencia_meses) return null
  return {
    labels: chartStatsData.value.tendencia_meses.map(m => {
      const d = new Date(m.mes + '-01')
      return d.toLocaleDateString('es-GT', { month: 'short', year: 'numeric' })
    }),
    datasets: [
      {
        label: 'Registros por Mes',
        data: chartStatsData.value.tendencia_meses.map(m => m.total_registros),
        borderColor: '#fa709a',
        backgroundColor: 'rgba(250, 112, 154, 0.1)',
        tension: 0.4,
        fill: true
      }
    ]
  }
})

const chartEmpresasData = computed(() => {
  if (!chartStatsData.value?.top_empresas) return null
  return {
    labels: chartStatsData.value.top_empresas.map(e => e.empresa.substring(0, 20) + (e.empresa.length > 20 ? '...' : '')),
    datasets: [{
      label: 'Certificados',
      data: chartStatsData.value.top_empresas.map(e => e.total_exportaciones),
      backgroundColor: '#a78bfa',
      borderRadius: 4
    }]
  }
})

// Pagination handlers
const nextPage = () => { if (page.value < totalPages.value) { page.value++; fetchData() } }
const prevPage = () => { if (page.value > 1) { page.value--; fetchData() } }

const applyFilters = () => {
  page.value = 1
  fetchData()
}

const resetFilters = () => {
  filterParams.value = { search: '', categoria: '', pais: '', emisor: '', fechaDesde: '', fechaHasta: '' }
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
    const res = await api.post('/visar/libre-venta/importar', fd, {
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

const formatNum = (num) => num != null ? Number(num).toLocaleString() : '0'
</script>

<template>
  <div class="space-y-6 pb-12">
    <!-- Header -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <router-link to="/admin/visar/dashboard" class="p-2 hover:bg-slate-100 text-slate-400 hover:text-slate-700 rounded-xl transition-colors" title="Volver al Panel VISAR">
          <ChevronLeft class="w-6 h-6"/>
        </router-link>
        <div class="p-4 bg-fuchsia-50 rounded-xl text-fuchsia-600">
          <FileBadge class="w-8 h-8" />
        </div>
        <div>
          <h2 class="text-2xl font-bold text-slate-800 m-0">Certificados Libre Venta</h2>
          <p class="text-slate-500 m-0 text-sm font-medium">Control de LV SIIA</p>
        </div>
      </div>
      <button
        @click="importModal = true; importResult = null; importFile = null"
        class="px-5 py-2.5 bg-fuchsia-600 hover:bg-fuchsia-700 text-white font-bold rounded-xl flex items-center gap-2 active:scale-95 transition-all shadow-md shadow-fuchsia-200"
      >
        <Upload class="w-4 h-4"/> Importar Excel
      </button>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 border-b border-slate-200 mt-4">
      <button @click="activeTab = 'registros'" :class="{'border-b-2 border-fuchsia-600 text-fuchsia-600': activeTab === 'registros', 'text-slate-500 hover:text-slate-700': activeTab !== 'registros'}" class="px-5 py-3 font-bold flex items-center gap-2 transition-colors">
        <List class="w-5 h-5"/> Tabla de Registros
      </button>
      <button @click="activeTab = 'stats'" :class="{'border-b-2 border-fuchsia-600 text-fuchsia-600': activeTab === 'stats', 'text-slate-500 hover:text-slate-700': activeTab !== 'stats'}" class="px-5 py-3 font-bold flex items-center gap-2 transition-colors">
        <BarChart3 class="w-5 h-5"/> Estadísticas y Gráficas
      </button>
    </div>

    <div v-show="activeTab === 'registros'" class="space-y-6">
      <!-- Filtros -->
      <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-slate-700 flex items-center gap-2"><Filter class="w-4 h-4"/> Filtros de Búsqueda</h3>
        <button @click="resetFilters" class="text-sm font-bold text-fuchsia-600 hover:text-fuchsia-800 transition-colors">Limpiar Filtros</button>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="relative xl:col-span-1">
          <Search class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input v-model="filterParams.search" @keyup.enter="applyFilters" type="text" placeholder="Empresa, doc, producto..." class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-fuchsia-500/20 focus:border-fuchsia-500 transition-all font-medium text-sm">
        </div>
        
        <select v-model="filterParams.pais" @change="applyFilters" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-fuchsia-500/20 focus:border-fuchsia-500 transition-all font-medium text-sm text-slate-700">
          <option value="">Cualquier País Destino</option>
          <option v-for="p in filtersData.paises" :key="p" :value="p">{{ p }}</option>
        </select>
        
        <select v-model="filterParams.categoria" @change="applyFilters" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-fuchsia-500/20 focus:border-fuchsia-500 transition-all font-medium text-sm text-slate-700">
          <option value="">Cualquier Categoría</option>
          <option v-for="c in filtersData.categorias" :key="c" :value="c">{{ c }}</option>
        </select>

        <select v-model="filterParams.emisor" @change="applyFilters" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-fuchsia-500/20 focus:border-fuchsia-500 transition-all font-medium text-sm text-slate-700">
          <option value="">Cualquier Emisor</option>
          <option v-for="e in filtersData.emisores" :key="e" :value="e">{{ e }}</option>
        </select>
      </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
      <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
        <h3 class="font-bold text-slate-700 text-lg">Registros ({{ formatNum(totalRecords) }})</h3>
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
              <th class="p-4 font-semibold">No. Documento</th>
              <th class="p-4 font-semibold">Empresa</th>
              <th class="p-4 font-semibold">Producto</th>
              <th class="p-4 font-semibold">Categoría</th>
              <th class="p-4 font-semibold">País Destino</th>
              <th class="p-4 font-semibold text-center">Fecha Emisión</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in records" :key="r.id" class="border-b border-slate-50 hover:bg-slate-50/80 transition-colors">
              <td class="p-4 font-black text-fuchsia-600">{{ r.numero_documento }}</td>
              <td class="p-4">
                <div class="flex items-center gap-2">
                  <Building class="w-4 h-4 text-slate-400"/>
                  <span class="font-bold text-slate-700 truncate max-w-[200px]" :title="r.empresa">{{ r.empresa }}</span>
                </div>
              </td>
              <td class="p-4 font-medium text-slate-600 truncate max-w-[250px]" :title="r.producto">{{ r.producto }}</td>
              <td class="p-4 text-sm text-slate-500">{{ r.categoria_producto }}</td>
              <td class="p-4">
                <div class="flex items-center gap-2">
                  <MapPin class="w-4 h-4 text-rose-400"/>
                  <span class="font-bold text-slate-700">{{ r.pais_destino }}</span>
                </div>
              </td>
              <td class="p-4 text-center font-medium text-slate-600">{{ r.fecha_emision_format }}</td>
            </tr>
          </tbody>
        </table>
        
        <div v-else-if="loading" class="flex flex-col items-center justify-center py-20">
          <div class="animate-spin rounded-full h-10 w-10 border-4 border-fuchsia-200 border-t-fuchsia-600 mb-4"></div>
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
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-fuchsia-200 border-t-fuchsia-600"></div>
      </div>
      <div v-else-if="chartStatsData" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 col-span-1 lg:col-span-2">
          <h3 class="font-bold text-slate-700 mb-4">Tendencia de Registros (Últimos 12 Meses)</h3>
          <div class="h-[300px] w-full">
            <Line v-if="chartMesesData" :data="chartMesesData" :options="{responsive: true, maintainAspectRatio: false}" />
          </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
          <h3 class="font-bold text-slate-700 mb-4">Distribución por País</h3>
          <div class="h-[300px] w-full flex justify-center">
            <Doughnut v-if="chartPaisesData" :data="chartPaisesData" :options="{responsive: true, maintainAspectRatio: false}" />
          </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
          <h3 class="font-bold text-slate-700 mb-4">Distribución por Categoría</h3>
          <div class="h-[300px] w-full flex justify-center">
            <Pie v-if="chartCategoriasData" :data="chartCategoriasData" :options="{responsive: true, maintainAspectRatio: false}" />
          </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 col-span-1 lg:col-span-2">
          <h3 class="font-bold text-slate-700 mb-4">Top 10 Empresas Exportadoras</h3>
          <div class="h-[350px] w-full">
            <Bar v-if="chartEmpresasData" :data="chartEmpresasData" :options="{indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: {legend: {display: false}}}" />
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
              <Upload class="w-5 h-5 text-fuchsia-500"/> Importar Libre Venta
            </h3>
            <button @click="importModal = false" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
              <X class="w-5 h-5"/>
            </button>
          </div>

          <div class="p-6 space-y-5">
            <div class="bg-fuchsia-50 border border-fuchsia-100 rounded-xl p-4 text-sm text-fuchsia-800">
              <p class="font-bold mb-1">Carga Masiva</p>
              <p>Sube el archivo Excel de Certificados de Libre Venta. Asegúrate de incluir el emisor, empresa, documento, categoría, producto, país destino y fecha.</p>
            </div>

            <div>
              <label class="block text-sm font-bold text-slate-700 mb-2">Seleccionar archivo (.xlsx o .xls)</label>
              <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-300 rounded-xl cursor-pointer hover:border-fuchsia-400 hover:bg-fuchsia-50/50 transition-all duration-150 group">
                <div class="flex flex-col items-center text-slate-400 group-hover:text-fuchsia-600">
                  <Upload class="w-8 h-8 mb-2"/>
                  <p v-if="!importFile" class="text-sm font-medium">Haz clic o arrastra el archivo</p>
                  <p v-else class="text-sm font-bold text-fuchsia-700">{{ importFile.name }}</p>
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
              class="w-full py-3 bg-fuchsia-600 hover:bg-fuchsia-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold rounded-xl flex items-center justify-center gap-2 transition-all active:scale-95 shadow-md"
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
