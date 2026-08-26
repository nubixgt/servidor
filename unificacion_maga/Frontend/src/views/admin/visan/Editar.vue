<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import api from '@/services/api'
import {
  Edit, MapPin, Building, Search, Save, X,
  Calendar, MessageSquare, AlertTriangle, AlertCircle,
  CheckCircle, Calculator
} from 'lucide-vue-next'

const departamentos      = ref([])
const municipiosPorDept  = ref({})
const registro           = ref(null)
const mensaje            = ref(null)
const loading            = ref(false)
const loadingLists       = ref(true)

const selDept  = ref('')
const selMuni  = ref('')
const observacionCambio = ref('')

const formObj = ref({
  fecha_periodo: '', observaciones: '',
  nda_nacional_r: 0, nda_nacional_f: 0,
  insan_r: 0, insan_f: 0,
  medida_transitoria_r: 0, medida_transitoria_f: 0,
  total_aa_r: 0, total_aa_f: 0, apa_f: 0
})

const totalCalculado = computed(() =>
  Number(formObj.value.total_aa_r) + Number(formObj.value.apa_f)
)

// Municipios disponibles según el depto seleccionado
const municipiosDisponibles = computed(() => {
  if (!selDept.value) return []
  // Buscar clave exacta o insensible a variaciones
  const key = Object.keys(municipiosPorDept.value).find(
    k => k === selDept.value || k.trim().toLowerCase() === selDept.value.trim().toLowerCase()
  )
  return key ? municipiosPorDept.value[key] : []
})

watch(selDept, () => { selMuni.value = '' })

const loadLists = async () => {
  loadingLists.value = true
  try {
    const res = await api.get('/visan/editar')
    departamentos.value     = res.data.listas?.departamentos || []
    municipiosPorDept.value = res.data.listas?.municipios_por_dept || {}
  } catch (e) {
    console.error('Error cargando listas:', e)
  } finally {
    loadingLists.value = false
  }
}

const cargarRegistro = async () => {
  if (!selDept.value || !selMuni.value) return
  loading.value = true
  mensaje.value = null
  try {
    const fd = new FormData()
    fd.append('accion', 'cargar')
    fd.append('departamento', selDept.value)
    fd.append('municipio', selMuni.value)
    const res = await api.post('/visan/editar', fd)
    if (res.data.registro_seleccionado) {
      registro.value = res.data.registro_seleccionado
      Object.keys(formObj.value).forEach(k => {
        formObj.value[k] = registro.value[k] ?? formObj.value[k]
      })
      if (res.data.mensaje) mensaje.value = { text: res.data.mensaje, type: res.data.tipo_mensaje }
    } else {
      mensaje.value = { text: 'No se encontró registro para esta ubicación.', type: 'warning' }
      registro.value = null
    }
  } catch (e) {
    mensaje.value = { text: 'Error de conexión al cargar registro.', type: 'danger' }
    console.error(e)
  } finally {
    loading.value = false
  }
}

const guardarCambios = async () => {
  if (!observacionCambio.value.trim()) return
  loading.value = true
  try {
    const fd = new FormData()
    fd.append('accion', 'actualizar')
    fd.append('id', registro.value.id)
    fd.append('observacion_cambio', observacionCambio.value)
    Object.keys(formObj.value).forEach(k => fd.append(k, formObj.value[k] ?? ''))
    const res = await api.post('/visan/editar', fd)
    if (res.data.mensaje) {
      mensaje.value = { text: res.data.mensaje, type: res.data.tipo_mensaje }
      if (res.data.registro_seleccionado) registro.value = res.data.registro_seleccionado
      observacionCambio.value = ''
      window.scrollTo({ top: 0, behavior: 'smooth' })
    }
  } catch (e) {
    mensaje.value = { text: 'Error de conexión al guardar.', type: 'danger' }
  } finally {
    loading.value = false
  }
}

const cancelar = () => {
  registro.value = null
  mensaje.value  = null
  selMuni.value  = ''
}

const formatNum = (num) => num != null ? Number(num).toLocaleString() : '0'

onMounted(loadLists)
</script>

<template>
  <div class="space-y-8 pb-12">

    <!-- Header -->
    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-white/5 flex items-center gap-4">
      <div class="p-4 bg-teal-50 rounded-xl text-teal-600"><Edit class="w-8 h-8"/></div>
      <div>
        <h2 class="text-2xl font-bold text-slate-800 dark:text-white m-0">Editar Datos de Asistencia</h2>
        <p class="text-slate-500 dark:text-slate-400 m-0">Modifica registros directamente en la base de datos nacional</p>
      </div>
    </div>

    <!-- Alerta -->
    <Transition name="fade">
      <div v-if="mensaje" :class="[
        'p-4 border-l-4 rounded-xl flex items-start gap-3 shadow-sm',
        mensaje.type === 'success' ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-500 text-emerald-800' :
        mensaje.type === 'warning' ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-500 text-amber-800' :
        'bg-red-50 dark:bg-red-900/20 border-red-500 text-red-800'
      ]">
        <CheckCircle v-if="mensaje.type === 'success'" class="w-5 h-5 mt-0.5 shrink-0"/>
        <AlertTriangle v-else-if="mensaje.type === 'warning'" class="w-5 h-5 mt-0.5 shrink-0"/>
        <AlertCircle v-else class="w-5 h-5 mt-0.5 shrink-0"/>
        <p class="font-semibold">{{ mensaje.text }}</p>
      </div>
    </Transition>

    <!-- Selector de Ubicación -->
    <div class="bg-gradient-to-r from-slate-800 to-slate-900 rounded-2xl p-6 md:p-8 shadow-md border border-slate-700">
      <p class="text-slate-400 text-xs uppercase font-bold tracking-widest mb-5">Selecciona la ubicación del registro a editar</p>
      <div class="flex flex-col md:flex-row gap-5 items-end">

        <!-- Depto -->
        <div class="flex-1 w-full">
          <label class="block text-slate-300 text-sm font-bold uppercase tracking-wider mb-2 flex items-center gap-2">
            <MapPin class="w-4 h-4"/> Departamento
          </label>
          <div v-if="loadingLists" class="h-12 bg-slate-700 animate-pulse rounded-xl"></div>
          <select
            v-else
            v-model="selDept"
            class="w-full bg-slate-800 border-2 border-slate-600 rounded-xl px-4 py-3 text-white font-semibold focus:border-blue-500 focus:outline-none transition-colors"
          >
            <option value="">-- Seleccione Departamento --</option>
            <option v-for="d in departamentos" :key="d.departamento" :value="d.departamento">
              {{ d.departamento }}
            </option>
          </select>
        </div>

        <!-- Municipio -->
        <div class="flex-1 w-full">
          <label class="block text-slate-300 text-sm font-bold uppercase tracking-wider mb-2 flex items-center gap-2">
            <Building class="w-4 h-4"/> Municipio
          </label>
          <select
            v-model="selMuni"
            :disabled="!selDept || municipiosDisponibles.length === 0"
            class="w-full bg-slate-800 border-2 border-slate-600 rounded-xl px-4 py-3 text-white font-semibold focus:border-blue-500 disabled:opacity-40 disabled:cursor-not-allowed focus:outline-none transition-colors"
          >
            <option value="">-- Seleccione Municipio --</option>
            <option v-for="m in municipiosDisponibles" :key="m" :value="m">{{ m }}</option>
          </select>
          <p v-if="selDept && municipiosDisponibles.length === 0 && !loadingLists" class="text-amber-400 text-xs mt-1">
            No hay municipios cargados para este depto.
          </p>
        </div>

        <!-- Botón -->
        <div class="w-full md:w-auto">
          <button
            @click="cargarRegistro"
            :disabled="!selDept || !selMuni || loading"
            class="w-full md:w-auto bg-blue-600 hover:bg-blue-50 dark:bg-blue-900/200 disabled:bg-slate-700 disabled:text-slate-500 dark:text-slate-400 text-white font-bold py-3 px-8 rounded-xl flex items-center justify-center gap-2 transition-all active:scale-95"
          >
            <Search class="w-5 h-5"/>
            {{ loading ? 'Cargando...' : 'Cargar Registro' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Formulario (cuando hay registro cargado) -->
    <Transition name="slide-up">
      <div v-if="registro" class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl shadow-sm border border-slate-100 dark:border-white/5 overflow-hidden">

        <!-- Header del registro -->
        <div class="bg-slate-50 dark:bg-slate-800/50 p-6 border-b border-slate-100 dark:border-white/5">
          <h3 class="text-xl font-black text-slate-800 dark:text-white flex items-center gap-2">
            <Building class="w-6 h-6 text-slate-400"/> {{ registro.municipio }}, {{ registro.departamento }}
          </h3>
          <div class="flex flex-wrap items-center gap-6 mt-4">
            <div>
              <span class="text-xs font-bold text-slate-400 uppercase block">ID</span>
              <span class="text-slate-700 dark:text-slate-200 font-semibold">#{{ registro.id }}</span>
            </div>
            <div>
              <span class="text-xs font-bold text-slate-400 uppercase block">Fecha Registro</span>
              <span class="text-slate-700 dark:text-slate-200 font-semibold">{{ registro.fecha_registro?.substring(0, 10) }}</span>
            </div>
            <div>
              <span class="text-xs font-bold text-slate-400 uppercase block">Total AA+APA Anterior</span>
              <span class="text-amber-600 font-black text-lg">{{ formatNum(registro.total_aa_apa) }}</span>
            </div>
          </div>
        </div>

        <div class="p-6 md:p-8 space-y-8">

          <!-- Fecha y Observaciones -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-6 border-b border-slate-100 dark:border-white/5">
            <div>
              <label class="font-bold text-slate-700 dark:text-slate-200 text-sm flex items-center gap-2 mb-2">
                <Calendar class="w-4 h-4 text-blue-500"/> Fecha Periodo
              </label>
              <input type="date" v-model="formObj.fecha_periodo"
                class="w-full border-2 border-slate-200 dark:border-white/10 rounded-xl p-3 focus:outline-none focus:border-blue-500 font-medium transition-colors">
            </div>
            <div>
              <label class="font-bold text-slate-700 dark:text-slate-200 text-sm flex items-center gap-2 mb-2">
                <MessageSquare class="w-4 h-4 text-blue-500"/> Observaciones
              </label>
              <textarea v-model="formObj.observaciones" rows="2"
                class="w-full border-2 border-slate-200 dark:border-white/10 rounded-xl p-3 focus:outline-none focus:border-blue-500 font-medium transition-colors resize-none"></textarea>
            </div>
          </div>

          <!-- Campos numéricos -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            <!-- Columna Izquierda: NDA / INSAN / Medidas -->
            <div>
              <h4 class="font-bold text-lg text-slate-800 dark:text-white mb-4 pb-2 border-b border-slate-100 dark:border-white/5">Insanitario / Precaución</h4>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase block mb-1">NDA NACIONAL R</label>
                  <input type="number" v-model="formObj.nda_nacional_r"
                    class="w-full bg-slate-50 dark:bg-slate-800/50 border-2 border-slate-200 dark:border-white/10 p-3 rounded-lg font-bold text-slate-700 dark:text-slate-200 focus:border-blue-500 focus:outline-none transition-colors">
                </div>
                <div>
                  <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase block mb-1">NDA NACIONAL F</label>
                  <input type="number" v-model="formObj.nda_nacional_f"
                    class="w-full bg-slate-50 dark:bg-slate-800/50 border-2 border-slate-200 dark:border-white/10 p-3 rounded-lg font-bold text-slate-700 dark:text-slate-200 focus:border-blue-500 focus:outline-none transition-colors">
                </div>
                <div>
                  <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase block mb-1">INSAN R</label>
                  <input type="number" v-model="formObj.insan_r"
                    class="w-full bg-red-50 dark:bg-red-900/20 border-2 border-red-200 text-red-700 p-3 rounded-lg font-bold focus:border-red-500 focus:outline-none transition-colors">
                </div>
                <div>
                  <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase block mb-1">INSAN F</label>
                  <input type="number" v-model="formObj.insan_f"
                    class="w-full bg-red-50 dark:bg-red-900/20 border-2 border-red-200 text-red-700 p-3 rounded-lg font-bold focus:border-red-500 focus:outline-none transition-colors">
                </div>
                <div>
                  <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase block mb-1">M. TRANSITORIA R</label>
                  <input type="number" v-model="formObj.medida_transitoria_r"
                    class="w-full bg-amber-50 dark:bg-amber-900/20 border-2 border-amber-200 text-amber-700 p-3 rounded-lg font-bold focus:border-amber-500 focus:outline-none transition-colors">
                </div>
                <div>
                  <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase block mb-1">M. TRANSITORIA F</label>
                  <input type="number" v-model="formObj.medida_transitoria_f"
                    class="w-full bg-amber-50 dark:bg-amber-900/20 border-2 border-amber-200 text-amber-700 p-3 rounded-lg font-bold focus:border-amber-500 focus:outline-none transition-colors">
                </div>
              </div>
            </div>

            <!-- Columna Derecha: Totales -->
            <div>
              <h4 class="font-bold text-lg text-slate-800 dark:text-white mb-4 pb-2 border-b border-slate-100 dark:border-white/5">Totales Asistencia</h4>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase block mb-1">TOTAL AA R</label>
                  <input type="number" v-model="formObj.total_aa_r"
                    class="w-full bg-emerald-50 dark:bg-emerald-900/20 border-2 border-emerald-200 text-emerald-700 p-3 rounded-lg font-bold focus:border-emerald-500 focus:outline-none transition-colors">
                </div>
                <div>
                  <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase block mb-1">TOTAL AA F</label>
                  <input type="number" v-model="formObj.total_aa_f"
                    class="w-full bg-emerald-50 dark:bg-emerald-900/20 border-2 border-emerald-200 text-emerald-700 p-3 rounded-lg font-bold focus:border-emerald-500 focus:outline-none transition-colors">
                </div>
                <div class="col-span-2">
                  <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase block mb-1">APA F</label>
                  <input type="number" v-model="formObj.apa_f"
                    class="w-full bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 text-blue-700 p-3 rounded-lg font-bold focus:border-blue-500 focus:outline-none transition-colors">
                </div>
                <div class="col-span-2 bg-amber-100 dark:bg-amber-900/40 border-2 border-amber-300 rounded-xl p-4 flex items-center justify-between text-amber-800">
                  <span class="font-bold flex items-center gap-2"><Calculator class="w-5 h-5"/> TOTAL AA+APA (Auto)</span>
                  <span class="text-3xl font-black">{{ formatNum(totalCalculado) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Motivo del cambio -->
          <div class="border-t border-slate-100 dark:border-white/5 pt-6">
            <label class="font-bold text-slate-700 dark:text-slate-200 text-sm flex items-center gap-2 mb-2">
              <Edit class="w-4 h-4 text-blue-500"/>
              Motivo del cambio <span class="text-red-500 ml-1">*</span>
            </label>
            <textarea
              v-model="observacionCambio"
              rows="2"
              placeholder="Ej: Corrección de captura, memo N°..."
              class="w-full border-2 border-slate-200 dark:border-white/10 rounded-xl p-3 focus:outline-none focus:border-blue-500 font-medium transition-colors resize-none"
              :class="{ 'border-red-300 bg-red-50 dark:bg-red-900/20': !observacionCambio && registro }"
            ></textarea>
            <p v-if="!observacionCambio && registro" class="text-red-500 text-xs mt-1">
              Debes indicar el motivo del cambio antes de guardar.
            </p>
          </div>

          <!-- Botonera -->
          <div class="flex justify-end gap-4 border-t border-slate-100 dark:border-white/5 pt-6">
            <button @click="cancelar"
              class="px-6 py-3 font-bold text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 rounded-xl hover:bg-slate-50 dark:bg-slate-800/50 active:scale-95 transition-all flex items-center gap-2">
              <X class="w-4 h-4"/> Cancelar
            </button>
            <button
              @click="guardarCambios"
              :disabled="loading || !observacionCambio.trim()"
              class="px-6 py-3 font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-50 dark:bg-emerald-900/200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 active:scale-95 transition-all shadow-md"
            >
              <Save class="w-5 h-5"/>
              {{ loading ? 'Guardando...' : 'Guardar Cambios' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }

.slide-up-enter-active { transition: all 0.3s ease; }
.slide-up-enter-from   { opacity: 0; transform: translateY(20px); }
</style>
