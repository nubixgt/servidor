<script setup>
import { ref, onMounted } from 'vue'
import { Maximize2, Minimize2, Map as MapIcon } from 'lucide-vue-next'

const props = defineProps({
  data: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['select'])

const selectedDept = ref(null)
const isZoomed = ref(false)
const viewBox = ref('0 0 420 400')
const hoveredDept = ref(null)
const mousePos = ref({ x: 0, y: 0 })

const departamentos = {
  Petén: { path: "M180,20 L320,20 L340,80 L320,140 L280,160 L220,150 L180,120 L160,60 Z", center: [250, 90] },
  Huehuetenango: { path: "M40,140 L100,130 L120,160 L110,200 L70,210 L30,190 Z", center: [75, 170] },
  Quiché: { path: "M100,130 L180,120 L200,150 L180,190 L140,200 L110,180 Z", center: [145, 160] },
  "Alta Verapaz": { path: "M180,120 L280,160 L290,200 L250,230 L200,220 L180,190 Z", center: [235, 180] },
  Izabal: { path: "M280,160 L380,150 L400,200 L350,240 L290,220 Z", center: [340, 195] },
  "San Marcos": { path: "M20,200 L70,210 L80,250 L50,280 L20,260 Z", center: [50, 240] },
  Quetzaltenango: { path: "M70,210 L110,200 L120,240 L100,270 L70,260 Z", center: [95, 240] },
  Totonicapán: { path: "M110,200 L140,200 L145,230 L120,240 Z", center: [125, 220] },
  Sololá: { path: "M120,240 L145,230 L155,260 L130,280 Z", center: [140, 255] },
  Retalhuleu: { path: "M50,280 L100,270 L110,310 L70,320 Z", center: [80, 295] },
  Suchitepéquez: { path: "M100,270 L130,280 L140,320 L110,330 Z", center: [120, 300] },
  Chimaltenango: { path: "M145,230 L180,220 L190,260 L160,270 L145,250 Z", center: [165, 245] },
  Sacatepéquez: { path: "M160,270 L190,260 L195,290 L170,295 Z", center: [180, 280] },
  Escuintla: { path: "M140,320 L200,300 L220,350 L160,360 Z", center: [180, 335] },
  Guatemala: { path: "M190,260 L230,250 L240,290 L210,300 L195,290 Z", center: [215, 275] },
  "Baja Verapaz": { path: "M180,190 L200,220 L240,210 L230,180 Z", center: [210, 200] },
  "El Progreso": { path: "M230,250 L270,240 L280,270 L250,280 Z", center: [255, 260] },
  Zacapa: { path: "M290,200 L350,240 L340,280 L300,270 L280,230 Z", center: [315, 245] },
  Chiquimula: { path: "M300,270 L340,280 L350,320 L310,330 Z", center: [325, 300] },
  Jalapa: { path: "M270,280 L310,270 L320,310 L280,320 Z", center: [295, 295] },
  Jutiapa: { path: "M280,320 L350,320 L360,360 L300,370 Z", center: [320, 345] },
  "Santa Rosa": { path: "M220,350 L280,320 L300,370 L250,380 Z", center: [260, 355] },
}

const selectDept = (name) => {
  if (selectedDept.value === name) {
    resetZoom()
    return
  }
  selectedDept.value = name
  const dept = departamentos[name]
  const zoomSize = 150
  viewBox.value = `${dept.center[0] - zoomSize / 2} ${dept.center[1] - zoomSize / 2} ${zoomSize} ${zoomSize}`
  isZoomed.value = true
  emit('select', name)
}

const resetZoom = () => {
  selectedDept.value = null
  viewBox.value = '0 0 420 400'
  isZoomed.value = false
  emit('select', null)
}

const getDeptColor = (name) => {
  if (selectedDept.value === name) return 'fill-indigo-500'
  const dept = props.data.find(d => d.departamento === name)
  if (!dept || dept.total_beneficiarios === 0) return 'fill-slate-200 dark:fill-slate-800'
  
  const intensity = Math.min(dept.total_beneficiarios / 5000, 1)
  return `rgba(79, 70, 229, ${0.2 + (intensity * 0.8)})`
}

const getDeptData = (name) => {
  return props.data.find(d => d.departamento === name)
}

const updateMouse = (e) => {
  mousePos.value = { x: e.clientX, y: e.clientY }
}
</script>

<template>
  <div class="relative w-full h-full min-h-[600px] flex items-center justify-center p-4 bg-white dark:bg-slate-900/50 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 overflow-hidden" @mousemove="updateMouse">
    <!-- Header info -->
    <div class="absolute top-8 left-8 z-10 flex flex-col gap-2">
      <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
        <MapIcon class="w-3 h-3"/> Mapa de Cobertura
      </h4>
      <div v-if="selectedDept" class="px-4 py-2 bg-indigo-500 text-white rounded-xl text-xs font-black shadow-lg shadow-indigo-500/30 animate-in slide-in-from-left duration-300">
        {{ selectedDept }}
      </div>
    </div>

    <!-- Tooltip -->
    <div v-if="hoveredDept" 
      class="fixed z-[100] pointer-events-none bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-700 p-4 rounded-2xl shadow-2xl animate-in fade-in zoom-in duration-200"
      :style="{ left: `${mousePos.x + 20}px`, top: `${mousePos.y + 20}px` }">
      <div class="text-xs font-black text-indigo-500 uppercase tracking-widest mb-2 border-b border-slate-200 dark:border-slate-700 pb-1">{{ hoveredDept }}</div>
      <div v-if="getDeptData(hoveredDept)" class="space-y-2 min-w-[150px]">
        <div class="flex justify-between items-center">
          <span class="text-[9px] font-black text-slate-400 uppercase">Beneficiarios</span>
          <span class="text-xs font-black text-slate-800 dark:text-white">{{ new Intl.NumberFormat().format(getDeptData(hoveredDept).total_beneficiarios) }}</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-[9px] font-black text-slate-400 uppercase">Ejecución</span>
          <span class="text-xs font-black text-emerald-500">{{ (getDeptData(hoveredDept).total_ejecutado / getDeptData(hoveredDept).total_programado * 100 || 0).toFixed(1) }}%</span>
        </div>
      </div>
      <div v-else class="text-[9px] font-bold text-slate-400 uppercase italic">Sin registros en esta zona</div>
    </div>

    <div class="absolute top-8 right-8 z-10">
      <button v-if="isZoomed" @click="resetZoom" class="p-3 bg-white dark:bg-slate-800 shadow-xl rounded-2xl text-slate-500 hover:text-indigo-500 transition-all active:scale-95 border border-slate-100 dark:border-slate-700">
        <Minimize2 class="w-5 h-5"/>
      </button>
    </div>

    <svg :viewBox="viewBox" class="w-full h-full max-h-[550px] transition-all duration-700 ease-out cursor-pointer select-none">
      <g v-for="(data, name) in departamentos" :key="name" 
         @click="selectDept(name)" 
         @mouseenter="hoveredDept = name" 
         @mouseleave="hoveredDept = null"
         class="group outline-none">
        <path 
          :d="data.path" 
          :fill="getDeptColor(name)"
          :class="[
            'transition-all duration-500 hover:opacity-80 stroke-white/20 dark:stroke-slate-700/50',
            selectedDept === name ? 'scale-[1.02]' : '',
            selectedDept && selectedDept !== name ? 'opacity-20 grayscale' : ''
          ]"
          style="transform-origin: center; transform-box: fill-box; stroke-width: 0.5;"
        />
        <text 
          v-if="!isZoomed || selectedDept === name"
          :x="data.center[0]" 
          :y="data.center[1]" 
          class="pointer-events-none fill-slate-700 dark:fill-white font-black tracking-tighter opacity-0 group-hover:opacity-100 transition-opacity"
          :class="isZoomed ? 'text-[6px]' : 'text-[4px]'"
          text-anchor="middle"
        >
          {{ name }}
        </text>
      </g>
    </svg>

    <!-- Legend -->
    <div class="absolute bottom-8 left-8 right-8 flex justify-between items-end pointer-events-none">
      <div class="space-y-2 bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm p-3 rounded-xl border border-white/20">
        <div class="flex items-center gap-3">
          <div class="w-3 h-3 bg-indigo-600 rounded-sm"></div>
          <span class="text-[9px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Alta Cobertura</span>
        </div>
        <div class="flex items-center gap-3">
          <div class="w-3 h-3 bg-indigo-200 dark:bg-indigo-900/30 rounded-sm"></div>
          <span class="text-[9px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Baja Cobertura</span>
        </div>
      </div>
      <div class="text-[10px] font-black text-slate-300 dark:text-slate-700 uppercase tracking-[0.3em] vertical-text">GUATEMALA</div>
    </div>
  </div>
</template>

<style scoped>
.vertical-text {
  writing-mode: vertical-rl;
  text-orientation: mixed;
}
</style>
