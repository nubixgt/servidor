<script setup>
import { ref } from 'vue'
import { 
  Upload, FileSpreadsheet, AlertCircle, CheckCircle, 
  ArrowRight, Loader2, Info, X, LayoutGrid, ArrowLeft
} from 'lucide-vue-next'

const dragActive = ref(false)
const file = ref(null)
const uploading = ref(false)
const status = ref(null) // 'success' | 'error'

const onFileChange = (e) => {
  const selectedFile = e.target.files[0]
  if (selectedFile && (selectedFile.name.endsWith('.xlsx') || selectedFile.name.endsWith('.xls'))) {
    file.value = selectedFile
  } else {
    alert('Por favor selecciona un archivo Excel válido (.xlsx o .xls)')
  }
}

const simulateUpload = () => {
  if (!file.value) return
  uploading.value = true
  setTimeout(() => {
    uploading.value = false
    status.value = 'success'
  }, 2000)
}
</script>

<template>
  <div class="space-y-6 pb-12 animate-fade-in">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white/60 dark:bg-slate-800/60 backdrop-blur-xl p-6 rounded-3xl border border-white/80 dark:border-slate-700 shadow-sm">
      <div class="flex items-center gap-4">
        <RouterLink to="/admin/vider/dashboard" class="p-3 bg-indigo-500 text-white rounded-2xl shadow-lg shadow-indigo-500/20 group hover:scale-110 transition-all">
          <LayoutGrid class="w-6 h-6" />
        </RouterLink>
        <div>
          <h2 class="text-xl md:text-2xl font-black text-slate-800 dark:text-white m-0 tracking-tight uppercase">IMPORTAR <span class="text-indigo-500">VIDER</span></h2>
          <p class="text-slate-500 dark:text-slate-400 m-0 text-sm font-medium">Carga masiva de información desde archivos Excel</p>
        </div>
      </div>
      
      <RouterLink to="/admin/vider/dashboard" class="flex items-center gap-2 px-5 py-3 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-2xl font-bold text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-all shadow-sm">
        <ArrowLeft class="w-4 h-4"/>
        Volver al Dashboard
      </RouterLink>
    </div>

    <div class="max-w-4xl mx-auto">
      <!-- Upload Zone -->
      <div 
        @dragover.prevent="dragActive = true"
        @dragleave.prevent="dragActive = false"
        @drop.prevent="dragActive = false; onFileChange($event)"
        :class="[
          'relative border-4 border-dashed rounded-[2.5rem] p-12 transition-all duration-500 flex flex-col items-center justify-center text-center shadow-sm',
          dragActive ? 'border-indigo-500 bg-indigo-500/10 scale-[0.98]' : 'border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl'
        ]"
      >
        <div class="w-24 h-24 bg-indigo-500/10 rounded-full flex items-center justify-center mb-6 animate-bounce-subtle">
          <FileSpreadsheet class="w-10 h-10 text-indigo-500"/>
        </div>
        
        <h2 class="text-xl font-black text-slate-800 dark:text-white mb-2 uppercase tracking-tight">
          {{ file ? file.name : 'Arrastra tu archivo Excel aquí' }}
        </h2>
        <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-8 max-w-sm mx-auto leading-relaxed">
          Asegúrate de que el archivo siga el formato oficial de VIDER para evitar errores en la importación.
        </p>

        <label class="px-8 py-4 bg-indigo-500 hover:bg-indigo-600 text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-indigo-500/25 cursor-pointer transition-all active:scale-95">
          Seleccionar Archivo
          <input type="file" class="hidden" @change="onFileChange" accept=".xlsx, .xls">
        </label>

        <div class="mt-8 flex items-center gap-6 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">
          <div class="flex items-center gap-2">
            <CheckCircle class="w-3 h-3 text-emerald-500"/>
            Formatos: .XLSX, .XLS
          </div>
          <div class="flex items-center gap-2">
            <CheckCircle class="w-3 h-3 text-emerald-500"/>
            Máximo: 50MB
          </div>
        </div>
      </div>

      <!-- Action Button -->
      <div class="mt-8 flex justify-center">
        <button 
          @click="simulateUpload"
          :disabled="!file || uploading"
          class="group flex items-center gap-4 px-12 py-5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-[2rem] font-black text-sm uppercase tracking-widest transition-all hover:scale-105 active:scale-95 disabled:opacity-50 disabled:grayscale disabled:scale-100 shadow-xl"
        >
          <template v-if="uploading">
            <Loader2 class="w-5 h-5 animate-spin"/>
            Procesando Información...
          </template>
          <template v-else>
            Comenzar Importación
            <ArrowRight class="w-5 h-5 group-hover:translate-x-2 transition-transform"/>
          </template>
        </button>
      </div>

      <!-- Instructions & Results -->
      <div class="mt-16 grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bg-white/60 dark:bg-slate-800/60 backdrop-blur-xl p-8 rounded-3xl border border-white/80 dark:border-slate-700 shadow-sm">
          <div class="flex items-center gap-3 mb-6">
            <Info class="w-5 h-5 text-indigo-500"/>
            <h3 class="text-sm font-black text-slate-800 dark:text-white uppercase tracking-widest">Recomendaciones</h3>
          </div>
          <ul class="space-y-4">
            <li class="flex gap-3 text-xs font-medium text-slate-500 dark:text-slate-400 leading-relaxed">
              <span class="text-indigo-500 font-black">01</span>
              No modifiques los encabezados de las columnas originales.
            </li>
            <li class="flex gap-3 text-xs font-medium text-slate-500 dark:text-slate-400 leading-relaxed">
              <span class="text-indigo-500 font-black">02</span>
              Verifica que los nombres de departamentos sean exactos.
            </li>
            <li class="flex gap-3 text-xs font-medium text-slate-500 dark:text-slate-400 leading-relaxed">
              <span class="text-indigo-500 font-black">03</span>
              El sistema detectará duplicados automáticamente.
            </li>
          </ul>
        </div>

        <div v-if="status === 'success'" class="bg-emerald-500/5 dark:bg-emerald-500/10 p-8 rounded-3xl border border-emerald-500/20 animate-in zoom-in duration-500">
          <div class="flex items-center gap-4 mb-6">
            <div class="p-2 bg-emerald-500 rounded-lg shadow-lg shadow-emerald-500/20">
              <CheckCircle class="w-5 h-5 text-white"/>
            </div>
            <h3 class="text-sm font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Carga Exitosa</h3>
          </div>
          <div class="space-y-3">
            <div class="flex justify-between text-xs font-bold text-slate-500 dark:text-slate-400">
              <span>Registros Nuevos:</span>
              <span class="text-emerald-600 font-black">1,245</span>
            </div>
            <div class="flex justify-between text-xs font-bold text-slate-500 dark:text-slate-400">
              <span>Duplicados Omitidos:</span>
              <span class="text-amber-500 font-black">12</span>
            </div>
          </div>
          <button @click="status = null; file = null" class="mt-8 w-full py-4 bg-emerald-500 hover:bg-emerald-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest transition-all">Entendido</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes bounce-subtle { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
.animate-bounce-subtle { animation: bounce-subtle 3s infinite ease-in-out; }
.animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
