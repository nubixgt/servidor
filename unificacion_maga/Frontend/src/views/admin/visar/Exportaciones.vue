<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import api from '@/services/api'
import { FileDown, Search, Filter, Upload, X, CheckCircle, AlertCircle, ChevronLeft, ChevronRight, FileSpreadsheet, Building, MapPin, BarChart3, List, Eye, ExternalLink } from 'lucide-vue-next'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Filler } from 'chart.js'
import { Bar, Line, Doughnut } from 'vue-chartjs'
import MapaMundial from './components/MapaMundial.vue'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement, ArcElement, Filler)

const activeTab = ref('registros')
const statsData = ref(null)
const loadingStats = ref(false)

const loading = ref(true)
const records = ref([])
const filtersData = ref({
  paises: [],
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
  pais: '',
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

// Modal Detalles
const detailsModal = ref(false)
const selectedRecord = ref(null)

// Filtro Fechas Estadísticas
const statsFechaDesde = ref('')
const statsFechaHasta = ref('')

const getColor = (exportaciones) => {
    if (exportaciones > 100) return '#3d64ad';
    if (exportaciones > 50) return '#4471b7';
    if (exportaciones > 20) return '#5382c2';
    if (exportaciones > 10) return '#6e9ccf';
    return '#7db2e1';
}

const getCountryFlag = (pais) => {
    pais = pais ? pais.toUpperCase().trim() : '';
    const banderas = {
        'ESTADOS UNIDOS': '🇺🇸', 'USA': '🇺🇸', 'UNITED STATES': '🇺🇸',
        'CANADA': '🇨🇦', 'CANADÁ': '🇨🇦',
        'MEXICO': '🇲🇽', 'MÉXICO': '🇲🇽',
        'ESPAÑA': '🇪🇸', 'SPAIN': '🇪🇸',
        'ALEMANIA': '🇩🇪', 'GERMANY': '🇩🇪',
        'FRANCIA': '🇫🇷', 'FRANCE': '🇫🇷',
        'REINO UNIDO': '🇬🇧', 'UK': '🇬🇧',
        'ITALIA': '🇮🇹', 'ITALY': '🇮🇹',
        'CHINA': '🇨🇳', 'JAPON': '🇯🇵', 'JAPÓN': '🇯🇵',
        'COREA DEL SUR': '🇰🇷', 'SOUTH KOREA': '🇰🇷',
        'BRASIL': '🇧🇷', 'BRAZIL': '🇧🇷',
        'ARGENTINA': '🇦🇷', 'CHILE': '🇨🇱', 'COLOMBIA': '🇨🇴',
        'PERU': '🇵🇪', 'PERÚ': '🇵🇪', 'VENEZUELA': '🇻🇪',
        'ECUADOR': '🇪🇨', 'BOLIVIA': '🇧🇴', 'PARAGUAY': '🇵🇾', 'URUGUAY': '🇺🇾',
        'COSTA RICA': '🇨🇷', 'PANAMA': '🇵🇦', 'PANAMÁ': '🇵🇦',
        'NICARAGUA': '🇳🇮', 'HONDURAS': '🇭🇳', 'EL SALVADOR': '🇸🇻',
        'GUATEMALA': '🇬🇹', 'BELICE': '🇧🇿', 'BELIZE': '🇧🇿',
        'CUBA': '🇨🇺', 'REPUBLICA DOMINICANA': '🇩🇴', 'REPÚBLICA DOMINICANA': '🇩🇴',
        'PUERTO RICO': '🇵🇷', 'JAMAICA': '🇯🇲', 'HAITI': '🇭🇹', 'HAITÍ': '🇭🇹',
        'HOLANDA': '🇳🇱', 'NETHERLANDS': '🇳🇱', 'BELGICA': '🇧🇪', 'BÉLGICA': '🇧🇪',
        'SUIZA': '🇨🇭', 'AUSTRIA': '🇦🇹', 'SUECIA': '🇸🇪', 'NORUEGA': '🇳🇴',
        'DINAMARCA': '🇩🇰', 'FINLANDIA': '🇫🇮', 'POLONIA': '🇵🇱', 'RUSIA': '🇷🇺',
        'AUSTRALIA': '🇦🇺', 'NUEVA ZELANDA': '🇳🇿', 'INDIA': '🇮🇳', 'PAKISTAN': '🇵🇰',
        'TAILANDIA': '🇹🇭', 'VIETNAM': '🇻🇳', 'FILIPINAS': '🇵🇭', 'INDONESIA': '🇮🇩',
        'MALASIA': '🇲🇾', 'SINGAPUR': '🇸🇬', 'TAIWAN': '🇹🇼', 'ISRAEL': '🇮🇱',
        'TURQUIA': '🇹🇷', 'EGIPTO': '🇪🇬', 'SUDAFRICA': '🇿🇦', 'MARRUECOS': '🇲🇦',
        'KENIA': '🇰🇪', 'NIGERIA': '🇳🇬', 'ETIOPIA': '🇪🇹'
    };
    return banderas[pais] || '🌍';
}

const getProductIcon = (producto) => {
    producto = producto ? producto.toLowerCase() : '';
    if (producto.includes('atun') || producto.includes('pescado')) return '🐟';
    if (producto.includes('camaron') || producto.includes('camarón')) return '🦐';
    if (producto.includes('langosta')) return '🦞';
    if (producto.includes('tilapia')) return '🐠';
    if (producto.includes('banano') || producto.includes('banana')) return '🍌';
    if (producto.includes('piña') || producto.includes('pina')) return '🍍';
    if (producto.includes('mango')) return '🥭';
    if (producto.includes('papaya')) return '🍈';
    if (producto.includes('aguacate')) return '🥑';
    if (producto.includes('melon') || producto.includes('melón')) return '🍉';
    if (producto.includes('fresa')) return '🍓';
    if (producto.includes('brocoli') || producto.includes('brócoli')) return '🥦';
    if (producto.includes('ejote')) return '🫘';
    if (producto.includes('tomate')) return '🍅';
    if (producto.includes('chile')) return '🌶️';
    if (producto.includes('lechuga')) return '🥬';
    if (producto.includes('zanahoria')) return '🥕';
    if (producto.includes('cafe') || producto.includes('café')) return '☕';
    if (producto.includes('azucar') || producto.includes('azúcar')) return '🍬';
    if (producto.includes('cardamomo')) return '🌿';
    if (producto.includes('carne')) return '🥩';
    if (producto.includes('pollo')) return '🍗';
    if (producto.includes('huevo')) return '🥚';
    if (producto.includes('leche') || producto.includes('lacteo')) return '🥛';
    if (producto.includes('queso')) return '🧀';
    if (producto.includes('miel')) return '🍯';
    if (producto.includes('flor')) return '🌺';
    if (producto.includes('maiz') || producto.includes('maíz')) return '🌽';
    if (producto.includes('frijol')) return '🫘';
    return '📦';
}

const getCategoryIcon = (categoria) => {
    categoria = categoria ? categoria.toUpperCase().trim() : '';
    const iconos = {
        'HIDROBIOLOGICOS': '🐟', 'HIDROBIOLÓGICOS': '🐟', 'ACUICULTURA': '🐠',
        'FRUTAS': '🍎', 'VEGETALES': '🥬', 'HORTALIZAS': '🥕',
        'CAFE': '☕', 'CAFÉ': '☕', 'AZUCAR': '🍬', 'AZÚCAR': '🍬',
        'CARNE': '🥩', 'LACTEOS': '🥛', 'LÁCTEOS': '🥛', 'GRANOS': '🌾',
        'CEREALES': '🌾', 'FLORES': '🌺', 'PLANTAS': '🌿', 'ESPECIAS': '🌶️',
        'APICOLA': '🍯', 'APÍCOLA': '🍯', 'TEXTILES': '🧵', 'MADERA': '🪵',
        'ARTESANIAS': '🎨', 'ARTESANÍAS': '🎨'
    };
    return iconos[categoria] || '📦';
}

const fetchFilters = async () => {
  try {
    const res = await api.get('/visar/exportaciones?get_filters=true')
    if (res.data.success) {
      filtersData.value = {
        paises: res.data.paises,
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
    const res = await api.get(`/visar/exportaciones?${params.toString()}`)
    if (res.data.success) {
      records.value = res.data.records
      totalRecords.value = res.data.total_records
      totalPages.value = res.data.total_pages
      page.value = res.data.current_page
    }
  } catch (err) {
    console.error('Error cargando exportaciones:', err)
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  loadingStats.value = true
  try {
    const params = new URLSearchParams()
    if (statsFechaDesde.value) params.append('fechaDesde', statsFechaDesde.value)
    if (statsFechaHasta.value) params.append('fechaHasta', statsFechaHasta.value)
    
    const res = await api.get(`/visar/exportaciones/stats?${params.toString()}`)
    if (res.data.success) {
      statsData.value = res.data.data
    }
  } catch (err) {
    console.error('Error cargando estadísticas:', err)
  } finally {
    loadingStats.value = false
  }
}

// Fin mapa logic

onMounted(() => {
  fetchFilters()
  fetchData()
  fetchStats()
  window.addEventListener('keydown', (e) => { 
      if (e.key === 'Escape') {
          importModal.value = false;
          detailsModal.value = false;
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
        label: 'Valor FOB ($)',
        data: statsData.value.por_mes.map(m => Number(m.total_fob) || 0),
        borderColor: '#667eea',
        backgroundColor: 'rgba(102, 126, 234, 0.1)',
        tension: 0.4,
        fill: true,
        yAxisID: 'y'
      },
      {
        label: 'Cantidad',
        data: statsData.value.por_mes.map(m => Number(m.cantidad) || 0),
        borderColor: '#10b981',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        tension: 0.4,
        fill: true,
        yAxisID: 'y1'
      }
    ]
  }
})

const chartPaisesData = computed(() => {
  if (!statsData.value?.top_paises) return null
  return {
    labels: statsData.value.top_paises.map(p => p.pais_destino),
    datasets: [{
      label: 'Valor FOB ($)',
      data: statsData.value.top_paises.map(p => Number(p.total_fob) || 0),
      backgroundColor: ['#667eea', '#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6', '#ec4899', '#22c55e', '#fb923c', '#a855f7'],
      borderRadius: 4
    }]
  }
})

const chartCategoriasData = computed(() => {
  if (!statsData.value?.top_categorias) return null
  return {
    labels: statsData.value.top_categorias.map(c => c.categoria_producto),
    datasets: [{
      data: statsData.value.top_categorias.map(c => c.total_fob),
      backgroundColor: ['#667eea', '#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6', '#ec4899', '#22c55e', '#fb923c', '#a855f7'],
      borderWidth: 2,
      borderColor: '#ffffff'
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
  filterParams.value = { search: '', pais: '', categoria: '', emisor: '', fechaDesde: '', fechaHasta: '' }
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
    const res = await api.post('/visar/exportaciones/importar', fd, {
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
        <div class="p-4 bg-emerald-50 rounded-xl text-emerald-600">
          <FileUp class="w-8 h-8" />
        </div>
        <div>
          <h2 class="text-2xl font-bold text-slate-800 m-0">Exportaciones (SIIA)</h2>
          <p class="text-slate-500 m-0 text-sm font-medium">Control de Certificados de Exportación VISAR</p>
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
      <button @click="activeTab = 'registros'" :class="{'border-b-2 border-blue-600 text-blue-600': activeTab === 'registros', 'text-slate-500 hover:text-slate-700': activeTab !== 'registros'}" class="px-5 py-3 font-bold flex items-center gap-2 transition-colors">
        <List class="w-5 h-5"/> Tabla de Registros
      </button>
      <button @click="activeTab = 'stats'" :class="{'border-b-2 border-blue-600 text-blue-600': activeTab === 'stats', 'text-slate-500 hover:text-slate-700': activeTab !== 'stats'}" class="px-5 py-3 font-bold flex items-center gap-2 transition-colors">
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
          <input v-model="filterParams.search" @keyup.enter="applyFilters" type="text" placeholder="Buscar empresa, certificado, producto..." class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-sm">
        </div>
        
        <select v-model="filterParams.pais" @change="applyFilters" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-sm text-slate-700">
          <option value="">Cualquier País Destino</option>
          <option v-for="p in filtersData.paises" :key="p" :value="p">{{ p }}</option>
        </select>
        
        <select v-model="filterParams.categoria" @change="applyFilters" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-sm text-slate-700">
          <option value="">Cualquier Categoría</option>
          <option v-for="c in filtersData.categorias" :key="c" :value="c">{{ c }}</option>
        </select>

        <select v-model="filterParams.emisor" @change="applyFilters" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-sm text-slate-700">
          <option value="">Cualquier Emisor</option>
          <option v-for="e in filtersData.emisores" :key="e" :value="e">{{ e }}</option>
        </select>

        <div class="flex items-center gap-2">
          <input v-model="filterParams.fechaDesde" @change="applyFilters" type="date" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-sm text-slate-700">
          <span class="text-slate-400 font-medium">a</span>
          <input v-model="filterParams.fechaHasta" @change="applyFilters" type="date" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-sm text-slate-700">
        </div>

        <button @click="applyFilters" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-all shadow-md active:scale-95">Buscar</button>
      </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
      <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
        <h3 class="font-bold text-slate-700 text-lg">Registros ({{ totalRecords }})</h3>
        <!-- Paginación Top -->
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
              <th class="p-4 font-semibold w-10 text-center">Detalle</th>
              <th class="p-4 font-semibold">Certificado</th>
              <th class="p-4 font-semibold">Empresa</th>
              <th class="p-4 font-semibold">Fecha Emisión</th>
              <th class="p-4 font-semibold">Destino</th>
              <th class="p-4 font-semibold">Categoría</th>
              <th class="p-4 font-semibold">Producto</th>
              <th class="p-4 text-right font-semibold">Peso Neto</th>
              <th class="p-4 text-right font-semibold">Valor FOB</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in records" :key="r.id" class="border-b border-slate-50 hover:bg-blue-50/50 transition-colors group cursor-pointer" @click="selectedRecord = r; detailsModal = true">
              <td class="p-4 text-center">
                  <button class="p-1.5 bg-blue-50 text-blue-500 rounded-lg group-hover:bg-blue-600 group-hover:text-white transition-colors" @click.stop="selectedRecord = r; detailsModal = true">
                      <Eye class="w-4 h-4"/>
                  </button>
              </td>
              <td class="p-4 font-bold text-blue-600">{{ r.certificado }}</td>
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
                  <span class="font-bold text-slate-700">{{ r.pais_destino }}</span>
                </div>
              </td>
              <td class="p-4 text-sm text-slate-600 truncate max-w-[150px]" :title="r.categoria_producto">{{ r.categoria_producto }}</td>
              <td class="p-4 text-sm text-slate-600 truncate max-w-[200px]" :title="r.producto">{{ r.producto }}</td>
              <td class="p-4 text-right font-bold text-slate-700">{{ r.peso_neto_format }}</td>
              <td class="p-4 text-right font-bold text-emerald-600">${{ r.valor_fob_format }}</td>
            </tr>
          </tbody>
        </table>
        
        <div v-else-if="loading" class="flex flex-col items-center justify-center py-20">
          <div class="animate-spin rounded-full h-10 w-10 border-4 border-blue-200 border-t-blue-600 mb-4"></div>
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
              <input v-model="statsFechaDesde" type="date" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-sm text-slate-700">
              <span class="text-slate-400 font-medium">a</span>
              <input v-model="statsFechaHasta" type="date" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-sm text-slate-700">
            </div>
          </div>
          <div class="flex items-center gap-2 shrink-0">
             <button @click="() => { statsFechaDesde = ''; statsFechaHasta = ''; fetchStats(); }" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-all shadow-sm active:scale-95">Limpiar</button>
             <button @click="fetchStats" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-all shadow-md active:scale-95">Aplicar Filtro</button>
          </div>
        </div>
      </div>

      <div v-if="loadingStats" class="flex justify-center py-20">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-blue-200 border-t-blue-600"></div>
      </div>
      <div v-else-if="statsData" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- KPIs Principales -->
        <div class="col-span-1 lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5 gap-4">
          <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 hover:shadow-md transition-shadow overflow-hidden">
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl shrink-0"><FileSpreadsheet class="w-6 h-6"/></div>
            <div class="min-w-0 flex-1">
              <p class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider truncate" title="Total Registros">Total Registros</p>
              <p class="text-lg sm:text-xl font-black text-slate-800 truncate" :title="Number(statsData.kpis?.total_exportaciones || 0).toLocaleString()">{{ Number(statsData.kpis?.total_exportaciones || 0).toLocaleString() }}</p>
            </div>
          </div>
          <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 hover:shadow-md transition-shadow overflow-hidden">
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl shrink-0"><BarChart3 class="w-6 h-6"/></div>
            <div class="min-w-0 flex-1">
              <p class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider truncate" title="Valor FOB Total">Valor FOB Total</p>
              <p class="text-lg sm:text-xl font-black text-slate-800 truncate" :title="`$` + Number(statsData.kpis?.valor_total || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})">${{ Number(statsData.kpis?.valor_total || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</p>
            </div>
          </div>
          <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 hover:shadow-md transition-shadow overflow-hidden">
            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl shrink-0"><List class="w-6 h-6"/></div>
            <div class="min-w-0 flex-1">
              <p class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider truncate" title="Peso Total (KG)">Peso Total (KG)</p>
              <p class="text-lg sm:text-xl font-black text-slate-800 truncate" :title="Number(statsData.kpis?.peso_total || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})">{{ Number(statsData.kpis?.peso_total || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</p>
            </div>
          </div>
          <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 hover:shadow-md transition-shadow overflow-hidden">
            <div class="p-3 bg-rose-50 text-rose-600 rounded-xl shrink-0"><MapPin class="w-6 h-6"/></div>
            <div class="min-w-0 flex-1">
              <p class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider truncate" title="Países Destino">Países Destino</p>
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
            <MapaMundial :data="statsData.map_data" type="export" />
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
          <h3 class="font-bold text-slate-700 mb-4">Evolución por Mes</h3>
          <div class="h-[300px] w-full">
            <Line v-if="chartMesesData" :data="chartMesesData" :options="{responsive: true, maintainAspectRatio: false, scales: { y: { position: 'left'}, y1: { position: 'right', grid: {drawOnChartArea: false} } }}" />
          </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
          <h3 class="font-bold text-slate-700 mb-4">Top Países (Valor FOB)</h3>
          <div class="h-[300px] w-full">
            <Bar v-if="chartPaisesData" :data="chartPaisesData" :options="{responsive: true, maintainAspectRatio: false, plugins: {legend: {display: false}}}" />
          </div>
        </div>

        <!-- Tablas de Ranking Lado a Lado -->
        <div class="col-span-1 lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Ranking Productos -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-100">
                    <h3 class="font-bold text-slate-700">🏆 Top 10 Productos Exportados</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 border-b border-slate-100 text-xs">
                                <th class="p-3 font-semibold">#</th>
                                <th class="p-3 font-semibold">Producto</th>
                                <th class="p-3 font-semibold text-right">Cantidad</th>
                                <th class="p-3 font-semibold text-right">Valor FOB</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(prod, i) in statsData.top_productos" :key="i" class="border-b border-slate-50 hover:bg-slate-50">
                                <td class="p-3">
                                    <span :class="['w-6 h-6 flex items-center justify-center rounded-full text-xs font-bold text-white', i===0?'bg-amber-400':i===1?'bg-slate-300':i===2?'bg-amber-600':'bg-slate-200 text-slate-600']">{{ i + 1 }}</span>
                                </td>
                                <td class="p-3 font-semibold text-slate-700">
                                    <span v-if="i < 3" class="text-lg mr-1">{{ getProductIcon(prod.producto) }}</span>
                                    {{ prod.producto }}
                                </td>
                                <td class="p-3 text-right">{{ parseInt(prod.cantidad).toLocaleString() }}</td>
                                <td class="p-3 text-right font-bold text-emerald-600">${{ parseFloat(prod.total_fob).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0}) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ranking Empresas -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-100">
                    <h3 class="font-bold text-slate-700">🏢 Top 10 Empresas Exportadoras</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 border-b border-slate-100 text-xs">
                                <th class="p-3 font-semibold">#</th>
                                <th class="p-3 font-semibold">Empresa</th>
                                <th class="p-3 font-semibold text-right">Cantidad</th>
                                <th class="p-3 font-semibold text-right">Valor FOB</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(emp, i) in statsData.top_empresas" :key="i" class="border-b border-slate-50 hover:bg-slate-50">
                                <td class="p-3">
                                    <span :class="['w-6 h-6 flex items-center justify-center rounded-full text-xs font-bold text-white', i===0?'bg-amber-400':i===1?'bg-slate-300':i===2?'bg-amber-600':'bg-slate-200 text-slate-600']">{{ i + 1 }}</span>
                                </td>
                                <td class="p-3 font-semibold text-slate-700 truncate max-w-[150px]" :title="emp.nombre_empresa">
                                    {{ emp.nombre_empresa }}
                                </td>
                                <td class="p-3 text-right">{{ parseInt(emp.cantidad).toLocaleString() }}</td>
                                <td class="p-3 text-right font-bold text-emerald-600">${{ parseFloat(emp.total_fob).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0}) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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
              <Upload class="w-5 h-5 text-emerald-500"/> Importar Exportaciones
            </h3>
            <button @click="importModal = false" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
              <X class="w-5 h-5"/>
            </button>
          </div>

          <div class="p-6 space-y-5">
            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 text-sm text-blue-700">
              <p class="font-bold mb-1">Estructura del archivo:</p>
              <p>Sube el archivo Excel oficial de Exportaciones. El sistema leerá los registros nuevos y los insertará a la base de datos.</p>
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

    <!-- Modal Detalles Registro -->
    <Transition name="modal">
      <div v-if="detailsModal && selectedRecord" class="fixed inset-0 z-[120] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="detailsModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">
          <!-- Header -->
          <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-2xl">
            <div>
                <h3 class="font-black text-xl text-slate-800 flex items-center gap-2">
                  <FileDown class="w-6 h-6 text-blue-500"/>
                  Certificado: <span class="text-blue-600">{{ selectedRecord.certificado }}</span>
                </h3>
                <p class="text-sm text-slate-500 font-medium mt-1">Registrado el: {{ selectedRecord.fecha_registro }}</p>
            </div>
            <button @click="detailsModal = false" class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-200 rounded-xl transition-colors">
              <X class="w-6 h-6"/>
            </button>
          </div>

          <!-- Body -->
          <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-6">
              
              <!-- Info Principal -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl">
                      <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Empresa Exportadora</p>
                      <div class="flex items-center gap-2">
                          <Building class="w-5 h-5 text-blue-500"/>
                          <p class="font-bold text-slate-800">{{ selectedRecord.nombre_empresa || 'N/A' }}</p>
                      </div>
                  </div>
                  <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-xl">
                      <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider mb-1">País Destino</p>
                      <div class="flex items-center gap-2">
                          <span class="text-xl">{{ getCountryFlag(selectedRecord.pais_destino) }}</span>
                          <p class="font-bold text-emerald-900 text-lg">{{ selectedRecord.pais_destino || 'N/A' }}</p>
                      </div>
                  </div>
              </div>

              <!-- Detalles de Producto -->
              <div>
                  <h4 class="font-bold text-slate-800 border-b-2 border-blue-100 pb-2 mb-4 flex items-center gap-2">
                      <List class="w-5 h-5 text-blue-500"/> Detalles del Producto
                  </h4>
                  <div class="grid grid-cols-2 gap-y-4 gap-x-6">
                      <div>
                          <p class="text-xs text-slate-500 font-bold mb-1">Categoría</p>
                          <p class="font-medium text-slate-800 bg-slate-100 px-2 py-1 rounded inline-block">{{ selectedRecord.categoria_producto || 'N/A' }}</p>
                      </div>
                      <div>
                          <p class="text-xs text-slate-500 font-bold mb-1">Producto Exacto</p>
                          <p class="font-bold text-slate-700">{{ selectedRecord.producto || 'N/A' }}</p>
                      </div>
                      <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                          <p class="text-xs text-blue-600 font-bold mb-1">Peso Neto</p>
                          <p class="font-black text-blue-900 text-lg">{{ selectedRecord.peso_neto_format }} kg</p>
                      </div>
                      <div class="bg-emerald-50 p-3 rounded-lg border border-emerald-100">
                          <p class="text-xs text-emerald-600 font-bold mb-1">Valor FOB</p>
                          <p class="font-black text-emerald-900 text-lg">${{ selectedRecord.valor_fob_format }}</p>
                      </div>
                  </div>
              </div>

              <!-- Detalles Administrativos -->
              <div>
                  <h4 class="font-bold text-slate-800 border-b-2 border-purple-100 pb-2 mb-4 flex items-center gap-2">
                      <FileSpreadsheet class="w-5 h-5 text-purple-500"/> Información Administrativa
                  </h4>
                  <div class="grid grid-cols-2 gap-4">
                      <div>
                          <p class="text-xs text-slate-500 font-bold mb-1">Destinatario</p>
                          <p class="font-medium text-slate-700">{{ selectedRecord.destinatario || 'No especificado' }}</p>
                      </div>
                      <div>
                          <p class="text-xs text-slate-500 font-bold mb-1">Aduana de Salida</p>
                          <p class="font-medium text-slate-700">{{ selectedRecord.aduana || 'No especificada' }}</p>
                      </div>
                      <div>
                          <p class="text-xs text-slate-500 font-bold mb-1">Emisor (Firma)</p>
                          <p class="font-medium text-slate-700">{{ selectedRecord.emisor || 'No especificado' }}</p>
                      </div>
                      <div>
                          <p class="text-xs text-slate-500 font-bold mb-1">Fecha Emisión</p>
                          <p class="font-medium text-slate-700">{{ selectedRecord.fecha_emision_format }}</p>
                      </div>
                  </div>
              </div>

              <!-- Observaciones -->
              <div v-if="selectedRecord.observaciones">
                  <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl">
                      <p class="text-xs font-bold text-amber-700 uppercase tracking-wider mb-2 flex items-center gap-1">
                          <AlertCircle class="w-4 h-4"/> Observaciones
                      </p>
                      <p class="text-amber-900 text-sm whitespace-pre-line">{{ selectedRecord.observaciones }}</p>
                  </div>
              </div>
          </div>
          
          <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 rounded-b-2xl flex justify-end">
              <button @click="detailsModal = false" class="px-6 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold rounded-xl transition-colors">Cerrar</button>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style>
/* Leaflet Popup Styles custom overriding */
.custom-leaflet-popup .leaflet-popup-content-wrapper {
    padding: 0;
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
}
.custom-leaflet-popup .leaflet-popup-content {
    margin: 0;
    width: 100% !important;
}
.custom-leaflet-popup .leaflet-popup-tip-container {
    display: none;
}
</style>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to      { opacity: 0; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to      { opacity: 0; }
</style>
