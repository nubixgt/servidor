<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { Table as TableIcon, MapPin, Building, ChevronRight, Eye, Filter, X, Upload, AlertCircle, CheckCircle } from 'lucide-vue-next'

const loading = ref(true)
const payload = ref(null)
const expandedDepts = ref([])
const selectedMuni = ref(null)

// Importación
const importModal   = ref(false)
const importFile    = ref(null)
const importLoading = ref(false)
const importResult  = ref(null)

const fetchTableData = async () => {
  try {
    const res = await api.get('/visan/tabla')
    payload.value = res.data
  } catch (err) {
    console.error('Error cargando datos tabla:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchTableData()
  window.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal() })
})

const formatNum = (num) => num != null ? Number(num).toLocaleString() : '0'

const toggleDept = (deptName) => {
  if (expandedDepts.value.includes(deptName)) {
    expandedDepts.value = expandedDepts.value.filter(d => d !== deptName)
  } else {
    expandedDepts.value.push(deptName)
  }
}

const expandAll   = () => { if (payload.value?.datos) expandedDepts.value = Object.keys(payload.value.datos) }
const collapseAll = () => expandedDepts.value = []
const openModal   = (muni) => selectedMuni.value = muni
const closeModal  = () => selectedMuni.value = null

// Importación
const onFileChange = (e) => {
  importFile.value = e.target.files[0] || null
  importResult.value = null
}

const doImport = async () => {
  if (!importFile.value) return
  importLoading.value = true
  importResult.value  = null
  try {
    const fd = new FormData()
    fd.append('archivo', importFile.value)
    const res = await api.post('/visan/importar', fd, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    importResult.value = res.data
    if (res.data.exito) {
      // Recargar la tabla después de importar
      await fetchTableData()
    }
  } catch (e) {
    importResult.value = { error: 'Error de conexión al importar.' }
  } finally {
    importLoading.value = false
  }
}

</script>

<template>
  <div class="space-y-8 pb-12">
    <!-- Header -->
    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-white/5 flex items-center gap-4">
      <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl text-purple-600">
        <TableIcon class="w-8 h-8" />
      </div>
      <div>
        <h2 class="text-2xl font-bold text-slate-800 dark:text-white m-0">Tabla Completa de Datos</h2>
        <p class="text-slate-500 dark:text-slate-400 m-0 text-lg">Vista jerárquica por departamentos y municipios</p>
      </div>
    </div>

    <!-- Skeleton -->
    <div v-if="loading" class="animate-pulse space-y-4">
      <div class="flex gap-4">
        <div class="flex-1 h-28 bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl border border-slate-100 dark:border-white/5"></div>
        <div class="flex-1 h-28 bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl border border-slate-100 dark:border-white/5"></div>
        <div class="flex-1 h-28 bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl border border-slate-100 dark:border-white/5"></div>
      </div>
      <div class="h-96 bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl border border-slate-100 dark:border-white/5"></div>
    </div>

    <template v-else-if="payload">
      <!-- Stats rápidas -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-white/5 flex items-center gap-4 hover:-translate-y-1 transition-transform duration-200">
          <div class="p-4 bg-sky-50 text-sky-600 rounded-xl"><MapPin class="w-8 h-8"/></div>
          <div>
            <p class="text-3xl font-bold text-slate-800 dark:text-white">{{ formatNum(payload.total_departamentos) }}</p>
            <p class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Departamentos</p>
          </div>
        </div>
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-white/5 flex items-center gap-4 hover:-translate-y-1 transition-transform duration-200">
          <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 rounded-xl"><Building class="w-8 h-8"/></div>
          <div>
            <p class="text-3xl font-bold text-slate-800 dark:text-white">{{ formatNum(payload.total_registros) }}</p>
            <p class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Municipios</p>
          </div>
        </div>
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-white/5 flex items-center gap-4 hover:-translate-y-1 transition-transform duration-200">
          <div class="p-4 bg-amber-50 dark:bg-amber-900/20 text-amber-600 rounded-xl"><Filter class="w-8 h-8"/></div>
          <div>
            <p class="text-3xl font-bold text-slate-800 dark:text-white">{{ payload.filtros_activos || 0 }}</p>
            <p class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Filtros Activos</p>
          </div>
        </div>
      </div>

      <!-- Toolbar -->
      <div class="flex flex-wrap gap-3 items-center justify-between">
        <div class="flex gap-3 flex-wrap">
          <button @click="expandAll"   class="px-4 py-2.5 bg-slate-800 text-white font-bold rounded-xl hover:bg-slate-700 active:scale-95 transition-all shadow-sm text-sm">Expandir Todo</button>
          <button @click="collapseAll" class="px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 text-slate-700 dark:text-slate-200 font-bold rounded-xl hover:bg-slate-50 dark:bg-slate-800/50 active:scale-95 transition-all shadow-sm text-sm">Colapsar Todo</button>
        </div>
        <button
          @click="importModal = true; importResult = null; importFile = null"
          class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl flex items-center gap-2 active:scale-95 transition-all shadow-md text-sm"
        >
          <Upload class="w-4 h-4"/> Importar Excel/CSV
        </button>
      </div>

      <!-- Tabla -->
      <div class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl shadow-sm border border-slate-100 dark:border-white/5 overflow-hidden">
        <div class="p-5 border-b border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-slate-800/50">
          <h3 class="font-bold text-slate-700 dark:text-slate-200 text-lg">Datos por Departamento y Municipio</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider border-b border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-slate-800/50">
                <th class="p-4 w-10"></th>
                <th class="p-4 font-semibold">Ubicación</th>
                <th class="p-4 text-right font-semibold">Total AA+APA</th>
                <th class="p-4 text-right font-semibold">NDA Severa</th>
                <th class="p-4 text-right font-semibold">INSAN Total</th>
                <th class="p-4 text-center font-semibold">Acción</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="(deptData, deptName) in payload.datos" :key="deptName">

                <!-- Fila Departamento -->
                <tr
                  @click="toggleDept(deptName)"
                  class="group cursor-pointer border-b transition-colors duration-150"
                  :class="expandedDepts.includes(deptName)
                    ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-100'
                    : 'bg-white dark:bg-slate-800 hover:bg-slate-50 dark:bg-slate-800/50 border-slate-100 dark:border-white/5'"
                >
                  <td class="p-4">
                    <ChevronRight
                      class="w-5 h-5 transition-transform duration-200"
                      :class="expandedDepts.includes(deptName) ? 'rotate-90 text-blue-600' : 'text-slate-400 group-hover:text-blue-400'"
                    />
                  </td>
                  <td class="p-4">
                    <div class="flex items-center gap-3">
                      <div class="p-2 rounded-lg transition-colors duration-150"
                        :class="expandedDepts.includes(deptName) ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-600' : 'bg-slate-100 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400'">
                        <MapPin class="w-4 h-4"/>
                      </div>
                      <div>
                        <p class="font-bold text-slate-800 dark:text-white">{{ deptName }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ deptData.totales.count }} municipios</p>
                      </div>
                    </div>
                  </td>
                  <td class="p-4 text-right font-extrabold text-blue-600">{{ formatNum(deptData.totales.total_aa_apa) }}</td>
                  <td class="p-4 text-right font-bold text-orange-500">{{ formatNum(deptData.totales.nda_severa) }}</td>
                  <td class="p-4 text-right font-bold text-red-600">{{ formatNum(deptData.totales.insan_total) }}</td>
                  <td class="p-4 text-center">
                    <span class="text-[10px] uppercase font-bold tracking-widest text-slate-400">Resumen</span>
                  </td>
                </tr>

                <!-- Filas Municipios (con animación) -->
                <template v-if="expandedDepts.includes(deptName)">
                  <tr
                    v-for="(muni, mIdx) in deptData.municipios"
                    :key="muni.municipio"
                    class="border-b border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-slate-800/50/60 hover:bg-blue-50 dark:bg-blue-900/20/40 transition-colors duration-100 row-fade-in"
                    :style="`animation-delay: ${mIdx * 25}ms`"
                  >
                    <td class="p-3"></td>
                    <td class="p-3 pl-10">
                      <div class="flex items-center gap-2 text-slate-700 dark:text-slate-200">
                        <Building class="w-4 h-4 text-slate-400 shrink-0"/>
                        <span class="font-semibold text-sm">{{ muni.municipio }}</span>
                      </div>
                    </td>
                    <td class="p-3 text-right font-bold text-slate-600 dark:text-slate-300 text-sm">{{ formatNum(muni.total_aa_apa) }}</td>
                    <td class="p-3 text-right font-bold text-slate-600 dark:text-slate-300 text-sm">{{ formatNum(Number(muni.nda_severa_r) + Number(muni.nda_severa_f)) }}</td>
                    <td class="p-3 text-right font-bold text-slate-600 dark:text-slate-300 text-sm">{{ formatNum(Number(muni.insan_r) + Number(muni.insan_f)) }}</td>
                    <td class="p-3 text-center">
                      <button
                        @click.stop="openModal(muni)"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 hover:border-blue-400 hover:text-blue-600 hover:bg-blue-50 dark:bg-blue-900/20 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 shadow-sm transition-all duration-150 active:scale-95"
                      >
                        <Eye class="w-3.5 h-3.5" /> Ver
                      </button>
                    </td>
                  </tr>
                </template>

              </template>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <div v-else class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl p-12 text-center border border-slate-100 dark:border-white/5">
      <p class="text-lg font-medium text-slate-500 dark:text-slate-400">No se pudieron cargar los datos.</p>
    </div>

    <!-- Modal -->
    <Transition name="modal">
      <div v-if="selectedMuni" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="closeModal"></div>
        <div class="relative bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden flex flex-col max-h-[90vh]">
          <div class="p-5 border-b border-slate-100 dark:border-white/5 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50 shrink-0">
            <h3 class="font-bold text-lg text-slate-800 dark:text-white flex items-center gap-2">
              <Eye class="w-5 h-5 text-blue-500"/> Detalle · {{ selectedMuni.municipio }}
            </h3>
            <button @click="closeModal" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:bg-red-900/20 rounded-lg transition-colors">
              <X class="w-5 h-5"/>
            </button>
          </div>
          <div class="p-6 overflow-y-auto space-y-6">
            <div class="flex justify-between items-start border-b border-slate-100 dark:border-white/5 pb-4">
              <div>
                <p class="text-xs font-bold tracking-widest text-slate-400 uppercase">Departamento</p>
                <h4 class="text-2xl font-black text-slate-800 dark:text-white">{{ selectedMuni.departamento }}</h4>
                <p class="text-lg text-slate-600 dark:text-slate-300 font-medium flex items-center gap-1 mt-1">
                  <Building class="w-4 h-4"/> {{ selectedMuni.municipio }}
                </p>
              </div>
              <div class="text-right">
                <p class="text-xs font-bold tracking-widest text-slate-400 uppercase">ID Registro</p>
                <p class="text-slate-700 dark:text-slate-200 font-bold">#{{ selectedMuni.id }}</p>
              </div>
            </div>
            <div class="space-y-6">
              <!-- Asistencia Alimentaria (AA) y APA -->
              <div>
                <h5 class="text-sm font-bold text-slate-700 dark:text-slate-200 border-b pb-2 mb-3">Asistencia Alimentaria (AA) y APA</h5>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                  <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100">
                    <span class="text-xs uppercase font-bold text-blue-500 block mb-1">Total AA+APA</span>
                    <span class="text-2xl font-black text-blue-700">{{ formatNum(selectedMuni.total_aa_apa) }}</span>
                  </div>
                  <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-100">
                    <span class="text-xs uppercase font-bold text-emerald-500 block mb-1">AA Fondos</span>
                    <span class="text-xl font-black text-emerald-700">{{ formatNum(selectedMuni.total_aa_f) }}</span>
                  </div>
                  <div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-100">
                    <span class="text-xs uppercase font-bold text-indigo-500 block mb-1">AA Recursos</span>
                    <span class="text-xl font-black text-indigo-700">{{ formatNum(selectedMuni.total_aa_r) }}</span>
                  </div>
                  <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-100">
                    <span class="text-xs uppercase font-bold text-purple-500 block mb-1">APA Fondos</span>
                    <span class="text-xl font-black text-purple-700">{{ formatNum(selectedMuni.apa_f) }}</span>
                  </div>
                  <div class="p-4 bg-fuchsia-50 rounded-xl border border-fuchsia-100">
                    <span class="text-xs uppercase font-bold text-fuchsia-500 block mb-1">APA Huertos</span>
                    <span class="text-xl font-black text-fuchsia-700">{{ formatNum(selectedMuni.apa_huertos) }}</span>
                  </div>
                </div>
              </div>

              <!-- Niños con Desnutrición Aguda (NDA) -->
              <div>
                <h5 class="text-sm font-bold text-slate-700 dark:text-slate-200 border-b pb-2 mb-3">Niños con Desnutrición Aguda (NDA)</h5>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-100">
                    <span class="text-xs uppercase font-bold text-orange-500 block mb-1">NDA Severa (Total)</span>
                    <span class="text-xl font-black text-orange-700">{{ formatNum(Number(selectedMuni.nda_severa_r) + Number(selectedMuni.nda_severa_f)) }}</span>
                    <div class="flex justify-between mt-2 text-xs text-orange-600 font-medium">
                      <span>Recursos: {{ formatNum(selectedMuni.nda_severa_r) }}</span>
                      <span>Fondos: {{ formatNum(selectedMuni.nda_severa_f) }}</span>
                    </div>
                  </div>
                  <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-100">
                    <span class="text-xs uppercase font-bold text-orange-500 block mb-1">NDA Nacional</span>
                    <span class="text-xl font-black text-orange-700">{{ formatNum(Number(selectedMuni.nda_nacional_r) + Number(selectedMuni.nda_nacional_f)) }}</span>
                    <div class="flex justify-between mt-2 text-xs text-orange-600 font-medium">
                      <span>Recursos: {{ formatNum(selectedMuni.nda_nacional_r) }}</span>
                      <span>Fondos: {{ formatNum(selectedMuni.nda_nacional_f) }}</span>
                    </div>
                  </div>
                  <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-100">
                    <span class="text-xs uppercase font-bold text-orange-500 block mb-1">NDA Plan Abordaje</span>
                    <span class="text-xl font-black text-orange-700">{{ formatNum(Number(selectedMuni.nda_plan_abordaje_r) + Number(selectedMuni.nda_plan_abordaje_f)) }}</span>
                    <div class="flex justify-between mt-2 text-xs text-orange-600 font-medium">
                      <span>Recursos: {{ formatNum(selectedMuni.nda_plan_abordaje_r) }}</span>
                      <span>Fondos: {{ formatNum(selectedMuni.nda_plan_abordaje_f) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- INSAN -->
              <div>
                <h5 class="text-sm font-bold text-slate-700 dark:text-slate-200 border-b pb-2 mb-3">Inseguridad Alimentaria y Nutricional (INSAN)</h5>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-100">
                    <span class="text-xs uppercase font-bold text-red-500 block mb-1">INSAN Total</span>
                    <span class="text-xl font-black text-red-700">{{ formatNum(Number(selectedMuni.insan_r) + Number(selectedMuni.insan_f)) }}</span>
                    <div class="flex gap-4 mt-2 text-xs text-red-600 font-medium">
                      <span>Recursos: {{ formatNum(selectedMuni.insan_r) }}</span>
                      <span>Fondos: {{ formatNum(selectedMuni.insan_f) }}</span>
                    </div>
                  </div>
                  <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-100">
                    <span class="text-xs uppercase font-bold text-red-500 block mb-1">INSAN Emergencia</span>
                    <span class="text-xl font-black text-red-700">{{ formatNum(Number(selectedMuni.insan_emergencia_r) + Number(selectedMuni.insan_emergencia_f)) }}</span>
                    <div class="flex gap-4 mt-2 text-xs text-red-600 font-medium">
                      <span>Recursos: {{ formatNum(selectedMuni.insan_emergencia_r) }}</span>
                      <span>Fondos: {{ formatNum(selectedMuni.insan_emergencia_f) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Otras Medidas y Reservas -->
              <div>
                <h5 class="text-sm font-bold text-slate-700 dark:text-slate-200 border-b pb-2 mb-3">Medidas Transitorias, Cautelares y Reservas</h5>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-white/10">
                    <span class="text-xs uppercase font-bold text-slate-500 dark:text-slate-400 block mb-1">Medida Transitoria</span>
                    <span class="text-xl font-black text-slate-700 dark:text-slate-200">{{ formatNum(Number(selectedMuni.medida_transitoria_r) + Number(selectedMuni.medida_transitoria_f)) }}</span>
                    <div class="flex justify-between mt-2 text-xs text-slate-600 dark:text-slate-300 font-medium">
                      <span>Recursos: {{ formatNum(selectedMuni.medida_transitoria_r) }}</span>
                      <span>Fondos: {{ formatNum(selectedMuni.medida_transitoria_f) }}</span>
                    </div>
                  </div>
                  <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-white/10">
                    <span class="text-xs uppercase font-bold text-slate-500 dark:text-slate-400 block mb-1">Medida Cautelar</span>
                    <span class="text-xl font-black text-slate-700 dark:text-slate-200">{{ formatNum(Number(selectedMuni.medida_cautelar_r) + Number(selectedMuni.medida_cautelar_f)) }}</span>
                    <div class="flex justify-between mt-2 text-xs text-slate-600 dark:text-slate-300 font-medium">
                      <span>Recursos: {{ formatNum(selectedMuni.medida_cautelar_r) }}</span>
                      <span>Fondos: {{ formatNum(selectedMuni.medida_cautelar_f) }}</span>
                    </div>
                  </div>
                  <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-white/10">
                    <span class="text-xs uppercase font-bold text-slate-500 dark:text-slate-400 block mb-1">Reserva Estratégica</span>
                    <span class="text-xl font-black text-slate-700 dark:text-slate-200">{{ formatNum(Number(selectedMuni.reserva_estrategica_r) + Number(selectedMuni.reserva_estrategica_f)) }}</span>
                    <div class="flex justify-between mt-2 text-xs text-slate-600 dark:text-slate-300 font-medium">
                      <span>Recursos: {{ formatNum(selectedMuni.reserva_estrategica_r) }}</span>
                      <span>Fondos: {{ formatNum(selectedMuni.reserva_estrategica_f) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
    <!-- Modal Importar CSV -->
    <Transition name="modal">
      <div v-if="importModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="importModal = false"></div>
        <div class="relative bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
          <!-- Header -->
          <div class="p-5 border-b border-slate-100 dark:border-white/5 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
            <h3 class="font-bold text-lg text-slate-800 dark:text-white flex items-center gap-2">
              <Upload class="w-5 h-5 text-emerald-500"/> Importar Datos (Excel / CSV)
            </h3>
            <button @click="importModal = false" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:bg-red-900/20 rounded-lg transition-colors">
              <X class="w-5 h-5"/>
            </button>
          </div>

          <div class="p-6 space-y-5">
            <!-- Instrucciones -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 rounded-xl p-4 text-sm text-blue-700">
              <p class="font-bold mb-1">Formato requerido:</p>
              <p>El archivo excel o csv debe tener como mínimo las columnas <code class="bg-blue-100 dark:bg-blue-900/40 px-1 rounded">departamento</code> y <code class="bg-blue-100 dark:bg-blue-900/40 px-1 rounded">municipio</code>. Si el registro ya existe, se <strong>actualizará</strong>; si no existe, se <strong>insertará</strong>.</p>
            </div>

            <!-- Selector de archivo -->
            <div>
              <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Seleccionar archivo</label>
              <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-300 rounded-xl cursor-pointer hover:border-emerald-400 hover:bg-emerald-50 dark:bg-emerald-900/20/50 transition-all duration-150 group">
                <div class="flex flex-col items-center text-slate-400 group-hover:text-emerald-600">
                  <Upload class="w-8 h-8 mb-2"/>
                  <p v-if="!importFile" class="text-sm font-medium">Haz clic o arrastra el archivo (xlsx, csv)</p>
                  <p v-else class="text-sm font-bold text-emerald-700">{{ importFile.name }}</p>
                </div>
                <input type="file" ref="fileInput" accept=".csv, .xlsx, .xls" class="hidden" @change="onFileChange"/>
              </label>
            </div>

            <!-- Resultado -->
            <Transition name="fade">
              <div v-if="importResult">
                <div v-if="importResult.error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 rounded-xl p-4 flex items-start gap-3">
                  <AlertCircle class="w-5 h-5 text-red-500 shrink-0 mt-0.5"/>
                  <p class="text-red-700 font-medium text-sm">{{ importResult.error }}</p>
                </div>
                <div v-else class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 rounded-xl p-4">
                  <div class="flex items-start gap-3 mb-3">
                    <CheckCircle class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5"/>
                    <p class="text-emerald-700 font-bold text-sm">{{ importResult.mensaje }}</p>
                  </div>
                  <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="bg-emerald-100 dark:bg-emerald-900/40 rounded-lg p-2">
                      <p class="text-lg font-black text-emerald-700">{{ importResult.actualizados }}</p>
                      <p class="text-xs font-bold text-emerald-600">Actualizados</p>
                    </div>
                    <div class="bg-blue-100 dark:bg-blue-900/40 rounded-lg p-2">
                      <p class="text-lg font-black text-blue-700">{{ importResult.insertados }}</p>
                      <p class="text-xs font-bold text-blue-600">Insertados</p>
                    </div>
                    <div class="bg-slate-100 dark:bg-slate-700/50 rounded-lg p-2">
                      <p class="text-lg font-black text-slate-700 dark:text-slate-200">{{ importResult.total_filas }}</p>
                      <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Total Filas</p>
                    </div>
                  </div>
                  <div v-if="importResult.errores?.length" class="mt-3 text-xs text-red-600">
                    <p class="font-bold mb-1">Errores ({{ importResult.errores.length }}):</p>
                    <ul class="list-disc pl-4 space-y-0.5">
                      <li v-for="e in importResult.errores.slice(0,5)" :key="e">{{ e }}</li>
                    </ul>
                  </div>
                </div>
              </div>
            </Transition>

            <!-- Botón -->
            <button
              @click="doImport"
              :disabled="!importFile || importLoading"
              class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white font-bold rounded-xl flex items-center justify-center gap-2 transition-all active:scale-95 shadow-md"
            >
              <Upload class="w-5 h-5"/>
              {{ importLoading ? 'Procesando...' : 'Importar y Actualizar' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
@keyframes rowFadeIn {
  from { opacity: 0; transform: translateY(-5px); }
  to   { opacity: 1; transform: translateY(0); }
}
.row-fade-in {
  animation: rowFadeIn 0.2s ease both;
}
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to      { opacity: 0; }
</style>
