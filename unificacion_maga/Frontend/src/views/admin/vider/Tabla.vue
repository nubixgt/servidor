<script setup>
import { ref, onMounted, computed, reactive } from 'vue'
import ViderService from '@/services/vider/ViderService'
import { LayoutGrid, ArrowLeft, Search, Download, FileSpreadsheet, Eye, ChevronDown, ChevronRight, ChevronLeft, X } from 'lucide-vue-next'

const loading = ref(true)
const data = ref([])
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
  search: ''
})
const formatNum = (n) => new Intl.NumberFormat('es-GT').format(Number(n) || 0)
const formatCurrency = (n) => new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'GTQ' }).format(Number(n) || 0)
const formatPercent = (n) => (parseFloat(n) || 0).toFixed(1) + '%'

const loadCatalogos = async () => {
  try {
    const response = await ViderService.getCatalogos()
    if (response.success) {
      catalogos.value = {
        ...catalogos.value,
        ...response.data
      }
    }
  } catch (error) {
    console.error('Error loading catalogs:', error)
  }
}

const onDependenciaChange = () => {
  filtros.value.actividad_id = ''
  filtros.value.producto_id = ''
  loadData()
}

const clearFilters = () => {
  filtros.value = {
    departamento: '',
    dependencia_id: '',
    actividad_id: '',
    producto_id: '',
    search: ''
  }
  loadData()
}

const loadData = async () => {
  loading.value = true
  try {
    const response = await ViderService.getRecords(filtros.value)
    if (response.success) {
      data.value = response.data
    }
  } catch (error) {
    console.error('Error loading VIDER table data:', error)
  } finally {
    loading.value = false
  }
}

const ITEMS_PER_PAGE = 25
const expandedDepts = ref(new Set())
const pageMap = reactive({})

const toggleDept = (dept) => {
  const newSet = new Set(expandedDepts.value)
  if (newSet.has(dept)) {
    newSet.delete(dept)
  } else {
    newSet.add(dept)
    if (!pageMap[dept]) pageMap[dept] = 1
  }
  expandedDepts.value = newSet
}

const groupedData = computed(() => {
  const groups = {}
  data.value.forEach(item => {
    const dept = item.departamento || 'Nacional / Sin Departamento'
    if (!groups[dept]) groups[dept] = []
    groups[dept].push(item)
  })
  return Object.keys(groups).sort().map(key => ({
    departamento: key,
    items: groups[key]
  }))
})

const getPaginatedItems = (dept, items) => {
  const page = pageMap[dept] || 1
  const start = (page - 1) * ITEMS_PER_PAGE
  return items.slice(start, start + ITEMS_PER_PAGE)
}

onMounted(() => {
  loadCatalogos()
  loadData()
})


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
          <h2 class="text-xl md:text-2xl font-black text-slate-800 dark:text-white m-0 tracking-tight uppercase">REGISTROS <span class="text-indigo-500">VIDER</span></h2>
          <p class="text-slate-500 dark:text-slate-400 m-0 text-sm font-medium">Exploración detallada de ejecución física y financiera</p>
        </div>
      </div>
      
      <RouterLink to="/admin/vider/dashboard" class="flex items-center gap-2 px-5 py-3 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-2xl font-bold text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-all shadow-sm">
        <ArrowLeft class="w-4 h-4"/>
        Volver al Dashboard
      </RouterLink>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 p-8 rounded-[2rem] shadow-sm">
      <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-6">
        <div class="flex flex-col gap-2">
          <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Búsqueda Rápida</label>
          <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
            <input type="text" v-model="filtros.search" @input="loadData" placeholder="Actividad o Producto..."
              class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-700 rounded-xl text-sm font-bold outline-none focus:border-indigo-500 transition-all dark:text-white">
          </div>
        </div>

        <div class="flex flex-col gap-2">
          <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Departamento</label>
          <select v-model="filtros.departamento" @change="loadData"
            class="px-4 py-3 bg-slate-50 dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-700 rounded-xl text-sm font-bold outline-none focus:border-indigo-500 transition-all cursor-pointer uppercase dark:text-white">
            <option value="">Nacional</option>
            <option v-for="d in catalogos.departamentos" :key="d.id" :value="d.nombre">{{ d.nombre }}</option>
          </select>
        </div>

        <div class="flex flex-col gap-2">
          <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Dependencia</label>
          <select v-model="filtros.dependencia_id" @change="onDependenciaChange"
            class="px-4 py-3 bg-slate-50 dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-700 rounded-xl text-sm font-bold outline-none focus:border-indigo-500 transition-all cursor-pointer uppercase dark:text-white">
            <option value="">Todas</option>
            <option v-for="d in catalogos.dependencias" :key="d.id" :value="d.id">{{ d.nombre }}</option>
          </select>
        </div>

        <div class="flex flex-col gap-2">
          <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Actividad</label>
          <select v-model="filtros.actividad_id" @change="loadData"
            class="px-4 py-3 bg-slate-50 dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-700 rounded-xl text-sm font-bold outline-none focus:border-indigo-500 transition-all cursor-pointer uppercase dark:text-white">
            <option value="">Todas</option>
            <option v-for="a in catalogos.actividades" :key="a.id" :value="a.id">{{ a.nombre }}</option>
          </select>
        </div>

        <div class="flex flex-col gap-2">
          <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Producto</label>
          <select v-model="filtros.producto_id" @change="loadData"
            class="px-4 py-3 bg-slate-50 dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-700 rounded-xl text-sm font-bold outline-none focus:border-indigo-500 transition-all cursor-pointer uppercase dark:text-white">
            <option value="">Todos</option>
            <option v-for="p in catalogos.productos" :key="p.id" :value="p.id">{{ p.nombre }}</option>
          </select>
        </div>

        <div class="flex items-end gap-2">
          <button @click="clearFilters" title="Limpiar Filtros" class="w-full flex items-center justify-center gap-2 px-4 py-3.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-500 dark:text-slate-300 rounded-xl font-black text-xs uppercase tracking-widest transition-all active:scale-95 shadow-sm">
            <X class="w-4 h-4"/>
            Limpiar Filtros
          </button>
        </div>
      </div>
    </div>

    <!-- Table Content -->
    <div class="bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-[2.5rem] overflow-hidden shadow-sm">
      <div class="overflow-x-auto text-slate-700 dark:text-slate-300 custom-scrollbar">
        <table class="w-full border-collapse min-w-[1400px]">
          <thead>
            <tr class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700 text-[9px]">
              <th class="px-3 py-4 text-left font-black text-slate-400 uppercase tracking-widest">Departamento</th>
              <th class="px-3 py-4 text-left font-black text-slate-400 uppercase tracking-widest">Municipio</th>
              <th class="px-3 py-4 text-center font-black text-slate-400 uppercase tracking-widest">Dep.</th>
              <th class="px-3 py-4 text-left font-black text-slate-400 uppercase tracking-widest">Producto</th>
              <th class="px-3 py-4 text-right font-black text-slate-400 uppercase tracking-widest">Prog.</th>
              <th class="px-3 py-4 text-right font-black text-slate-400 uppercase tracking-widest">Ejec.</th>
              <th class="px-3 py-4 text-center font-black text-slate-400 uppercase tracking-widest">%</th>
              <th class="px-3 py-4 text-right font-black text-slate-400 uppercase tracking-widest">Hom.</th>
              <th class="px-3 py-4 text-right font-black text-slate-400 uppercase tracking-widest">Muj.</th>
              <th class="px-3 py-4 text-right font-black text-slate-400 uppercase tracking-widest">Total</th>
              <th class="px-3 py-4 text-right font-black text-slate-400 uppercase tracking-widest">Vigente Q</th>
              <th class="px-3 py-4 text-right font-black text-slate-400 uppercase tracking-widest">Ejec. Q</th>
              <th class="px-3 py-4 text-center font-black text-slate-400 uppercase tracking-widest">% Fin</th>
              <th class="px-3 py-4 text-center font-black text-slate-400 uppercase tracking-widest">Ver</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <template v-if="loading">
              <tr v-for="i in 5" :key="i" class="animate-pulse">
                <td colspan="14" class="px-8 py-6">
                  <div class="h-6 bg-slate-100 dark:bg-slate-800 rounded-xl w-full opacity-50"></div>
                </td>
              </tr>
            </template>
            <tr v-else-if="data.length === 0">
              <td colspan="14" class="px-8 py-20 text-center">
                <div class="flex flex-col items-center gap-4">
                  <div class="p-4 bg-slate-100 dark:bg-slate-900 rounded-full">
                    <FileSpreadsheet class="w-10 h-10 text-slate-300"/>
                  </div>
                  <h3 class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-tight">Sin registros</h3>
                  <p class="text-sm font-medium text-slate-400">No se encontraron registros que coincidan con los filtros aplicados.</p>
                </div>
              </td>
            </tr>
            <template v-else v-for="group in groupedData" :key="group.departamento">
              <tr class="bg-indigo-50/50 dark:bg-indigo-900/10 hover:bg-indigo-100/50 dark:hover:bg-indigo-900/20 cursor-pointer transition-colors border-b border-indigo-100 dark:border-indigo-900/50" @click="toggleDept(group.departamento)">
                <td colspan="14" class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <ChevronDown v-if="expandedDepts.has(group.departamento)" class="w-4 h-4 text-indigo-500" />
                    <ChevronRight v-else class="w-4 h-4 text-slate-400" />
                    <span class="font-black text-slate-700 dark:text-slate-200 uppercase tracking-widest text-[11px]">{{ group.departamento }}</span>
                    <span class="px-2 py-0.5 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-full text-[10px] font-bold">{{ group.items.length }} registros</span>
                  </div>
                </td>
              </tr>
              <template v-if="expandedDepts.has(group.departamento)">
                <tr v-for="item in getPaginatedItems(group.departamento, group.items)" :key="item.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors group text-[10px]">
                  <td class="px-3 py-3 font-bold text-slate-700 dark:text-slate-300">{{ item.departamento }}</td>
                  <td class="px-3 py-3 text-slate-500">{{ item.municipio }}</td>
                  <td class="px-3 py-3 text-center">
                    <span class="px-1.5 py-0.5 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded text-[9px] font-black">{{ item.siglas }}</span>
                  </td>
                  <td class="px-3 py-3 text-slate-600 dark:text-slate-400 italic truncate max-w-[150px]" :title="item.producto">{{ item.producto }}</td>
                  <td class="px-3 py-3 text-right font-medium">{{ formatNum(item.programado) }}</td>
                  <td class="px-3 py-3 text-right font-black text-indigo-500">{{ formatNum(item.ejecutado) }}</td>
                  <td class="px-3 py-3 text-center">
                    <span :class="['font-black', item.porcentaje_ejecucion >= 100 ? 'text-emerald-500' : 'text-amber-500']">
                      {{ formatPercent(item.porcentaje_ejecucion) }}
                    </span>
                  </td>
                  <td class="px-3 py-3 text-right text-slate-500">{{ formatNum(item.hombres) }}</td>
                  <td class="px-3 py-3 text-right text-slate-500">{{ formatNum(item.mujeres) }}</td>
                  <td class="px-3 py-3 text-right font-black">{{ formatNum(item.total_personas) }}</td>
                  <td class="px-3 py-3 text-right text-slate-500">{{ formatCurrency(item.vigente_financiera) }}</td>
                  <td class="px-3 py-3 text-right font-bold text-emerald-600">{{ formatCurrency(item.financiera_ejecutado) }}</td>
                  <td class="px-3 py-3 text-center font-black text-emerald-600">{{ formatPercent(item.financiera_porcentaje) }}</td>
                  <td class="px-3 py-3 text-center">
                    <button class="p-1.5 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition-all opacity-0 group-hover:opacity-100 shadow-sm shadow-indigo-500/20">
                      <Eye class="w-3.5 h-3.5" />
                    </button>
                  </td>
                </tr>
                <tr v-if="group.items.length > ITEMS_PER_PAGE" class="bg-slate-50/80 dark:bg-slate-900/80 border-b border-slate-100 dark:border-slate-800">
                  <td colspan="14" class="px-4 py-2">
                    <div class="flex items-center justify-between">
                      <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">
                        Mostrando {{ ((pageMap[group.departamento] || 1) - 1) * ITEMS_PER_PAGE + 1 }} a 
                        {{ Math.min((pageMap[group.departamento] || 1) * ITEMS_PER_PAGE, group.items.length) }} de {{ group.items.length }}
                      </span>
                      <div class="flex items-center gap-2">
                        <button @click.stop="pageMap[group.departamento]--" :disabled="(pageMap[group.departamento] || 1) === 1" class="p-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 disabled:opacity-50 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm cursor-pointer">
                          <ChevronLeft class="w-3.5 h-3.5 text-slate-600 dark:text-slate-300"/>
                        </button>
                        <span class="text-[10px] font-black text-slate-600 dark:text-slate-300 px-2">{{ pageMap[group.departamento] || 1 }}</span>
                        <button @click.stop="pageMap[group.departamento]++" :disabled="(pageMap[group.departamento] || 1) * ITEMS_PER_PAGE >= group.items.length" class="p-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 disabled:opacity-50 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm cursor-pointer">
                          <ChevronRight class="w-3.5 h-3.5 text-slate-600 dark:text-slate-300"/>
                        </button>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </template>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
