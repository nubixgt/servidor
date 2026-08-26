<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import L from 'leaflet'
import * as topojson from 'topojson-client'
import 'leaflet/dist/leaflet.css'
import { Maximize2, Minimize2, RefreshCcw, ArrowLeft, Map as MapIcon } from 'lucide-vue-next'

const props = defineProps({
  data: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['select-dept', 'select-muni'])

const normalizeText = (text) => {
  if (!text) return ''
  return text.toString().normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toUpperCase()
    .trim()
}

const formatNum = (val) => new Intl.NumberFormat('es-GT').format(val || 0)

const getTooltipContent = (name, data) => {
  return `
    <div class="text-[10px] font-black uppercase mb-1 border-b border-white/20 pb-1">${name.toUpperCase()}</div>
    <div class="flex flex-col gap-1">
      <div class="flex justify-between gap-4">
        <span class="opacity-70">Total:</span>
        <span class="font-bold">${formatNum(data.beneficiarios)}</span>
      </div>
      <div class="flex justify-between gap-4">
        <span class="opacity-70">Hombres:</span>
        <span class="font-bold text-blue-300">${formatNum(data.hombres)}</span>
      </div>
      <div class="flex justify-between gap-4">
        <span class="opacity-70">Mujeres:</span>
        <span class="font-bold text-pink-300">${formatNum(data.mujeres)}</span>
      </div>
    </div>
  `
}

const mapContainer = ref(null)
let map = null
let deptosLayer = null
let munisLayer = null

const guatemalaBounds = [[13.737, -92.235], [17.825, -88.225]]
const guatemalaCenter = [15.78, -90.23]

const currentView = ref('national') // 'national' or 'department'
const selectedArea = ref('Guatemala')

const getColor = (d) => {
  return d > 5000 ? '#fb923c' :
         d > 2000 ? '#facc15' :
         d > 1000 ? '#a3e635' :
         d > 500  ? '#4ade80' :
                    '#22c55e';
}

const deptStyle = (feature) => {
  const nameNorm = normalizeText(feature.properties.Departamento || feature.properties.NOMBRE || feature.properties.nombre)
  const deptData = props.data.find(d => normalizeText(d.departamento) === nameNorm)
  const total = deptData ? (parseInt(deptData.beneficiarios) || 0) : 0
  
  return {
    fillColor: getColor(total),
    weight: 1.5,
    opacity: 1,
    color: 'rgba(255,255,255,0.4)',
    fillOpacity: 0.7
  }
}

const highlightFeature = (e) => {
  const layer = e.target
  layer.setStyle({
    weight: 3,
    color: '#fff',
    fillOpacity: 0.9
  })
  layer.bringToFront()
}

const resetHighlight = (e) => {
  deptosLayer.resetStyle(e.target)
}

const onDeptClick = (e) => {
  const feature = e.target.feature
  const name = feature.properties.Departamento || feature.properties.NOMBRE || feature.properties.nombre
  const code = feature.properties.id || feature.properties.CODIGO || feature.properties.codigo
  
  map.fitBounds(e.target.getBounds(), { padding: [50, 50] })
  currentView.value = 'department'
  selectedArea.value = name
  emit('select-dept', { name, code })
  
  loadMunicipalities(code)
}

const loadMunicipalities = async (deptCode) => {
  try {
    const response = await fetch(`${import.meta.env.BASE_URL}assets/vider/maps/munis.json`)
    const topoData = await response.json()
    const key = Object.keys(topoData.objects)[0]
    const geoData = topojson.feature(topoData, topoData.objects[key])
    
    if (munisLayer) map.removeLayer(munisLayer)
    if (deptosLayer) map.removeLayer(deptosLayer)
    
    const filtered = {
      type: 'FeatureCollection',
      features: geoData.features.filter(f => f.properties.id_depto == deptCode)
    }
    
    munisLayer = L.geoJSON(filtered, {
      style: (feature) => {
        const name = feature.properties.Municipio || feature.properties.nombre
        const muniData = props.data.find(d => d.municipio === name)
        const total = muniData ? (parseInt(muniData.total_beneficiarios) || 0) : 0
        return {
          fillColor: getColor(total),
          weight: 1.5,
          opacity: 1,
          color: 'rgba(255,255,255,0.4)',
          fillOpacity: 0.7
        }
      },
      onEachFeature: (feature, layer) => {
        const name = feature.properties.Municipio || feature.properties.nombre || ''
        const data = props.data.find(d => normalizeText(d.municipio) === normalizeText(name)) || {}
        layer.bindTooltip(getTooltipContent(name, data), { className: 'map-tooltip', sticky: true })

        layer.on({
          mouseover: highlightFeature,
          mouseout: (ev) => munisLayer.resetStyle(ev.target),
          click: (ev) => {
            const total = data.beneficiarios || 0
            selectedArea.value = name
            emit('select-muni', { name, total })
            map.flyToBounds(ev.target.getBounds(), { padding: [100, 100] })
          }
        })
      }
    }).addTo(map)
  } catch (error) {
    console.error('Error loading municipalities:', error)
  }
}

const resetMap = () => {
  if (munisLayer) map.removeLayer(munisLayer)
  if (deptosLayer) deptosLayer.addTo(map)
  map.fitBounds(guatemalaBounds)
  currentView.value = 'national'
  selectedArea.value = 'Guatemala'
  emit('select-dept', null)
}

onMounted(async () => {
  const bounds = [[13.0, -93.0], [18.5, -87.5]] // Límites estrictos para Guatemala
  
  map = L.map(mapContainer.value, {
    center: guatemalaCenter,
    zoom: 7,
    minZoom: 7,
    maxZoom: 10,
    maxBounds: bounds,
    maxBoundsViscosity: 1.0,
    zoomControl: false,
    attributionControl: false
  })

  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap'
  }).addTo(map)
  
  L.control.zoom({ position: 'topright' }).addTo(map)

  try {
    const response = await fetch(`${import.meta.env.BASE_URL}assets/vider/maps/deptos.json`)
    const topoData = await response.json()
    const key = Object.keys(topoData.objects)[0]
    const geoData = topojson.feature(topoData, topoData.objects[key])
    
    deptosLayer = L.geoJSON(geoData, {
      style: deptStyle,
      onEachFeature: (feature, layer) => {
        layer.on({
          mouseover: highlightFeature,
          mouseout: resetHighlight,
          click: onDeptClick
        })
        const name = feature.properties.Departamento || feature.properties.NOMBRE || feature.properties.nombre || ''
        const data = props.data.find(d => normalizeText(d.departamento) === normalizeText(name)) || {}
        layer.bindTooltip(getTooltipContent(name, data), { className: 'map-tooltip', sticky: true })
      }
    }).addTo(map)
    
    map.fitBounds(guatemalaBounds)
  } catch (error) {
    console.error('Error loading departments:', error)
  }
})

onUnmounted(() => {
  if (map) map.remove()
})

watch(() => props.data, (newData) => {
  if (deptosLayer) {
    deptosLayer.setStyle(deptStyle)
    deptosLayer.eachLayer(layer => {
      const name = layer.feature.properties.Departamento || layer.feature.properties.NOMBRE || layer.feature.properties.nombre || ''
      const data = newData.find(d => normalizeText(d.departamento) === normalizeText(name)) || {}
      layer.setTooltipContent(getTooltipContent(name, data))
    })
  }
  if (munisLayer) {
    munisLayer.setStyle((feature) => {
      const name = feature.properties.Municipio || feature.properties.nombre || ''
      const muniData = newData.find(d => normalizeText(d.municipio) === normalizeText(name))
      const total = muniData ? (parseInt(muniData.beneficiarios) || 0) : 0
      return {
        fillColor: getColor(total),
        weight: 1.5,
        opacity: 1,
        color: 'rgba(255,255,255,0.4)',
        fillOpacity: 0.7
      }
    })
    munisLayer.eachLayer(layer => {
      const name = layer.feature.properties.Municipio || layer.feature.properties.nombre || ''
      const data = newData.find(d => normalizeText(d.municipio) === normalizeText(name)) || {}
      layer.setTooltipContent(getTooltipContent(name, data))
    })
  }
}, { deep: true })
</script>

<template>
  <div class="relative w-full h-full overflow-hidden">
    <!-- Header info Overlay -->
    <div class="absolute top-6 left-6 z-[1000] flex flex-col gap-3">
      <div class="flex items-center gap-3 bg-slate-900 border border-white/20 p-3 rounded-2xl shadow-2xl">
        <div class="p-2 bg-indigo-500 rounded-xl shadow-lg shadow-indigo-500/30">
          <MapIcon class="w-5 h-5 text-white"/>
        </div>
        <div>
          <h3 class="text-sm font-black text-white leading-tight uppercase tracking-widest">Mapa de Ejecución</h3>
          <p class="text-[9px] font-bold text-indigo-300 uppercase tracking-widest opacity-90">VIDER · Guatemala</p>
        </div>
      </div>
      
      <button v-if="currentView !== 'national'" @click="resetMap" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 border border-indigo-400 rounded-xl text-white text-[10px] font-black uppercase tracking-widest cursor-pointer hover:bg-indigo-700 transition-all active:scale-95 w-fit shadow-lg shadow-indigo-500/40">
        <ArrowLeft class="w-3.5 h-3.5"/> Volver al Mapa Nacional
      </button>
    </div>

    <!-- Area info Badge -->
    <div class="absolute top-6 right-16 z-[1000] hidden md:block">
      <div class="px-6 py-2.5 bg-slate-900 border border-white/20 text-white rounded-xl font-black text-[10px] uppercase tracking-[0.2em] shadow-2xl">
        {{ selectedArea }}
      </div>
    </div>

    <!-- Map Container -->
    <div ref="mapContainer" class="w-full h-full bg-slate-900"></div>

    <!-- Legend -->
    <div class="absolute bottom-6 left-6 z-[1000] bg-slate-900 border border-white/20 p-5 rounded-3xl shadow-2xl">
      <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4">Escala de Beneficiarios</h4>
      <div class="flex gap-2">
        <div v-for="scale in [
          { color: '#22c55e', label: '0-500' },
          { color: '#4ade80', label: '1k' },
          { color: '#a3e635', label: '2k' },
          { color: '#facc15', label: '5k' },
          { color: '#fb923c', label: '5k+' }
        ]" :key="scale.label" class="flex flex-col items-center gap-2">
          <div class="w-8 h-2 rounded-full" :style="{ backgroundColor: scale.color }"></div>
          <span class="text-[7px] font-black text-slate-200 uppercase">{{ scale.label }}</span>
        </div>
      </div>
    </div>

    <!-- Controls button -->
    <div class="absolute bottom-6 right-6 z-[1000] flex flex-col gap-2">
      <button @click="resetMap" class="p-3 bg-white/10 backdrop-blur-xl border border-white/20 shadow-2xl rounded-xl text-white hover:bg-white/20 transition-all active:scale-95">
        <RefreshCcw class="w-4 h-4"/>
      </button>
    </div>
  </div>
</template>

<style>
.map-tooltip {
  background: rgba(15, 23, 42, 0.9) !important;
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255,255,255,0.15) !important;
  border-radius: 12px !important;
  color: white !important;
  padding: 8px 14px !important;
  font-weight: 900 !important;
  font-size: 10px !important;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3) !important;
}
.leaflet-container {
  background: transparent !important;
}
.leaflet-control-zoom {
  border: none !important;
  box-shadow: 0 4px 15px rgba(0,0,0,0.2) !important;
}
.leaflet-control-zoom-in, .leaflet-control-zoom-out {
  background: rgba(255, 255, 255, 0.1) !important;
  backdrop-filter: blur(10px) !important;
  border: 1px solid rgba(255,255,255,0.1) !important;
  color: white !important;
  font-weight: bold !important;
}
.leaflet-control-zoom-in:hover, .leaflet-control-zoom-out:hover {
  background: rgba(255, 255, 255, 0.2) !important;
}
</style>
