<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { Leaf, Users, UserCheck, UserX, Sprout, ClipboardList, TrendingUp } from 'lucide-vue-next'

const dapca = ref(null)
const loading = ref(true)

const fetchDapcaData = async () => {
  try {
    const res = await api.get('/visan/dapca')
    const d = res.data.dapca
    if (d) {
      // Garantizar que siempre sean arrays
      if (!Array.isArray(d.programas)) d.programas = []
      if (!Array.isArray(d.progress_bars)) d.progress_bars = []
    }
    dapca.value = d
  } catch (err) {
    console.error('Error loading DAPCA API:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchDapcaData()
})

const formatNum = (num) => {
  return num ? Number(num).toLocaleString() : '0'
}
</script>

<template>
  <div class="space-y-8 pb-12">
    <!-- Header Hero -->
    <div class="bg-gradient-to-br from-emerald-800 to-emerald-950 rounded-2xl p-8 text-white shadow-lg flex flex-col md:flex-row md:items-center justify-between gap-6 border border-emerald-700/50">
      <div>
        <h1 class="text-3xl font-extrabold flex items-center gap-3 mb-2">
          <Leaf class="w-8 h-8 text-emerald-400" /> DAPCA - Detalle de Ejecución 2025
        </h1>
        <p class="text-emerald-200/90 text-lg">Departamento de Apoyo a la Producción Comunitaria de Alimentos</p>
      </div>
      <div v-if="!loading && dapca" class="bg-white dark:bg-slate-800/10 backdrop-blur-md border border-white/20 rounded-xl px-6 py-4 flex flex-col items-center">
        <span class="text-emerald-200 text-sm font-semibold uppercase tracking-wider mb-1">Avance General</span>
        <span class="text-4xl font-black text-white">{{ Number(dapca.porcentaje_avance).toFixed(1) }}%</span>
      </div>
    </div>

    <div v-if="loading" class="animate-pulse space-y-6">
      <div class="flex gap-6"><div class="flex-1 h-32 bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl"></div><div class="flex-1 h-32 bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl"></div><div class="flex-1 h-32 bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl"></div></div>
      <div class="h-64 bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl"></div>
    </div>

    <template v-else-if="dapca">
      <!-- Indicadores -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border-b-4 border-b-blue-500 border-x border-t border-slate-100 dark:border-white/5 flex items-center justify-between hover:-translate-y-1 transition-transform">
          <div>
            <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Total Productores (Meta)</p>
            <p class="text-4xl font-extrabold text-slate-800 dark:text-white">{{ formatNum(dapca.total_productores) }}</p>
          </div>
          <div class="p-4 bg-blue-50 dark:bg-blue-900/20 text-blue-500 rounded-full"><Users class="w-8 h-8"/></div>
        </div>
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border-b-4 border-b-emerald-500 border-x border-t border-slate-100 dark:border-white/5 flex items-center justify-between hover:-translate-y-1 transition-transform">
          <div>
            <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Alcanzados</p>
            <p class="text-4xl font-extrabold text-emerald-600">{{ formatNum(dapca.productores_alcanzados) }}</p>
          </div>
          <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 rounded-full"><UserCheck class="w-8 h-8"/></div>
        </div>
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border-b-4 border-b-amber-500 border-x border-t border-slate-100 dark:border-white/5 flex items-center justify-between hover:-translate-y-1 transition-transform">
          <div>
            <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Restantes</p>
            <p class="text-4xl font-extrabold text-amber-500">{{ formatNum(dapca.productores_restantes) }}</p>
          </div>
          <div class="p-4 bg-amber-50 dark:bg-amber-900/20 text-amber-500 rounded-full"><UserX class="w-8 h-8"/></div>
        </div>
      </div>

      <!-- Barras de Progreso -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
        <div v-for="(progreso, idx) in dapca.progress_bars" :key="idx" class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-white/5">
          <div class="flex justify-between items-end mb-3">
            <h4 class="font-bold text-slate-700 dark:text-slate-200 flex items-center gap-2"><Sprout class="w-5 h-5 text-emerald-500"/> {{ progreso.nombre }}</h4>
            <span class="px-3 py-1 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 text-sm font-bold rounded-lg">{{ progreso.porcentaje }}%</span>
          </div>
          <div class="w-full bg-slate-100 dark:bg-slate-700/50 rounded-full h-4 mb-4 overflow-hidden border border-slate-200 dark:border-white/10">
            <div class="bg-emerald-50 dark:bg-emerald-900/200 h-4 rounded-full transition-all duration-1000" :style="`width: ${Math.min(100, progreso.porcentaje)}%`"></div>
          </div>
          <div class="flex justify-between text-sm font-medium">
            <span class="text-slate-500 dark:text-slate-400">Avance: <span class="text-slate-800 dark:text-white">{{ formatNum(progreso.avance) }}</span></span>
            <span class="text-slate-500 dark:text-slate-400">Meta: <span class="text-slate-800 dark:text-white">{{ formatNum(progreso.meta) }}</span></span>
          </div>
        </div>
      </div>

      <!-- Tabla de Programas -->
      <div class="bg-white/80 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl shadow-sm border border-slate-100 dark:border-white/5 p-6 sm:p-8 mt-8">
        <h3 class="text-xl font-bold flex items-center gap-3 text-slate-800 dark:text-white mb-6">
          <ClipboardList class="w-6 h-6 text-blue-500"/> Desglose por Departamento e Intervención
        </h3>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-sm uppercase tracking-wider">
                <th class="p-4 rounded-tl-xl font-semibold">Programa</th>
                <th class="p-4 font-semibold">Intervención</th>
                <th class="p-4 text-right font-semibold">Meta</th>
                <th class="p-4 text-right font-semibold">Avance</th>
                <th class="p-4 text-right rounded-tr-xl font-semibold">% Avance</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <template v-for="(programa, pIdx) in dapca.programas" :key="pIdx">
                <!-- Para evitar span multiples con Vue iterators se itera intervenciones y se verifica el index -->
                <tr v-for="(interv, iIdx) in programa.intervenciones" :key="interv.nombre" class="hover:bg-slate-50 dark:bg-slate-800/50 transition-colors">
                  <td v-if="iIdx === 0" :rowspan="programa.intervenciones.length" class="p-4 align-top w-1/3">
                    <div class="border-l-4 border-blue-500 pl-4 py-1">
                      <span class="font-bold text-blue-700 text-sm">{{ programa.departamento }}</span>
                    </div>
                  </td>
                  <td class="p-4 text-slate-700 dark:text-slate-200 font-medium text-sm flex items-center gap-2">
                    <TrendingUp class="w-4 h-4 text-slate-400"/> {{ interv.nombre }}
                  </td>
                  <td class="p-4 text-right text-slate-700 dark:text-slate-200 font-bold">{{ formatNum(interv.meta) }}</td>
                  <td class="p-4 text-right text-emerald-600 font-bold">{{ formatNum(interv.avance) }}</td>
                  <td class="p-4 text-right">
                    <span :class="['px-3 py-1 rounded-full text-xs font-bold', interv.porcentaje >= 70 ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700' : (interv.porcentaje >= 30 ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700' : 'bg-red-100 text-red-700')]">
                      {{ Number(interv.porcentaje).toFixed(1) }}%
                    </span>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>
