<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import L from 'leaflet'
import * as topojson from 'topojson-client'
import 'leaflet/dist/leaflet.css'
import { RefreshCcw, ArrowLeft, Map as MapIcon, Plus, Minus } from 'lucide-vue-next'

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
    .replace(/[̀-ͯ]/g, "")
    .toUpperCase()
    .trim()
}

const formatNum = (val) => new Intl.NumberFormat('es-GT').format(val || 0)

const getTooltipContent = (name, data) => {
  return `
    <div class="popup-name">${(name || '').toUpperCase()}</div>
    <div class="popup-dept">${data.beneficiarios !== undefined ? 'Ejecución VIDER' : 'Sin datos registrados'}</div>
    <div class="popup-row"><span class="k">Beneficiarios</span><span class="v">${formatNum(data.beneficiarios)}</span></div>
    <div class="popup-row"><span class="k">Hombres</span><span class="v">${formatNum(data.hombres)}</span></div>
    <div class="popup-row"><span class="k">Mujeres</span><span class="v">${formatNum(data.mujeres)}</span></div>
    <div class="popup-action">Click para explorar →</div>
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
const muniCount = ref(0)

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
    color: 'rgba(255,255,255,0.5)',
    fillOpacity: 0.75
  }
}

const highlightFeature = (e) => {
  const layer = e.target
  layer.setStyle({
    weight: 3,
    color: '#48d7ff',
    fillOpacity: 0.92
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
    muniCount.value = filtered.features.length

    munisLayer = L.geoJSON(filtered, {
      style: (feature) => {
        const name = feature.properties.Municipio || feature.properties.nombre
        const muniData = props.data.find(d => d.municipio === name)
        const total = muniData ? (parseInt(muniData.total_beneficiarios) || 0) : 0
        return {
          fillColor: getColor(total),
          weight: 1.5,
          opacity: 1,
          color: 'rgba(255,255,255,0.5)',
          fillOpacity: 0.75
        }
      },
      onEachFeature: (feature, layer) => {
        const name = feature.properties.Municipio || feature.properties.nombre || ''
        const data = props.data.find(d => normalizeText(d.municipio) === normalizeText(name)) || {}
        layer.bindTooltip(getTooltipContent(name, data), { className: 'map-tooltip', sticky: true, opacity: 1 })

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
    maxZoom: 12,
    maxBounds: bounds,
    maxBoundsViscosity: 1.0,
    zoomControl: false,
    attributionControl: false
  })

  // Basemap satelital (Esri World Imagery) — sin necesidad de API key
  L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    attribution: 'Tiles &copy; Esri',
    maxZoom: 19
  }).addTo(map)

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
        layer.bindTooltip(getTooltipContent(name, data), { className: 'map-tooltip', sticky: true, opacity: 1 })
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
        color: 'rgba(255,255,255,0.5)',
        fillOpacity: 0.75
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
  <div class="relative w-full h-full overflow-hidden bg-[#07111f]">
    <!-- Topbar glass (estilo Inteligencia Territorial) -->
    <div class="absolute top-4 left-4 right-4 z-[1000] flex items-center justify-between gap-4 px-5 py-3.5 bg-[rgba(10,20,35,0.65)] border border-white/10 rounded-3xl backdrop-blur-2xl shadow-2xl">
      <div>
        <h3 class="flex items-center gap-2 text-sm font-black text-white uppercase tracking-widest">
          <MapIcon class="w-4 h-4 text-cyan-400"/> Mapa de Ejecución VIDER
        </h3>
        <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-1">
          <span class="cursor-pointer hover:text-white transition-colors font-semibold" :class="currentView === 'national' ? 'text-white' : ''" @click="resetMap">Guatemala</span>
          <span v-if="currentView === 'department'" class="opacity-40">›</span>
          <span v-if="currentView === 'department'" class="text-white font-bold">{{ selectedArea }}</span>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <span class="hidden sm:inline-flex px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-cyan-400/10 border border-cyan-400/30 text-cyan-300">
          {{ currentView === 'department' ? muniCount + ' municipios' : '22 Departamentos' }}
        </span>
        <button v-if="currentView !== 'national'" @click="resetMap" class="map-btn back-btn" title="Volver al mapa nacional">
          <ArrowLeft class="w-3.5 h-3.5"/> <span class="hidden md:inline">Volver</span>
        </button>
        <button @click="map?.zoomIn()" class="map-btn" title="Acercar"><Plus class="w-3.5 h-3.5"/></button>
        <button @click="map?.zoomOut()" class="map-btn" title="Alejar"><Minus class="w-3.5 h-3.5"/></button>
        <button @click="resetMap" class="map-btn" title="Restablecer"><RefreshCcw class="w-3.5 h-3.5"/></button>
      </div>
    </div>

    <!-- Map Container -->
    <div ref="mapContainer" class="w-full h-full"></div>

    <!-- Legend -->
    <div class="absolute bottom-6 left-6 z-[1000] bg-[rgba(10,20,35,0.75)] border border-white/10 backdrop-blur-2xl p-5 rounded-3xl shadow-2xl">
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
  </div>
</template>

<style>
.map-btn {
  width: 36px; height: 36px; border-radius: 12px;
  border: 1px solid rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.05);
  color: #edf5ff;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  gap: 6px;
  font-size: 13px;
  transition: all .2s;
}
.map-btn:hover { background: #2f81f7; border-color: #2f81f7; transform: translateY(-2px); }
.back-btn { width: auto; padding: 0 14px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #48d7ff; border-color: rgba(72,215,255,0.3); }
.back-btn:hover { color: #001228; background: #48d7ff; }

.map-tooltip {
  background: rgba(11, 23, 41, 0.97) !important;
  backdrop-filter: blur(16px);
  border: 1px solid rgba(74, 144, 217, 0.22) !important;
  border-radius: 14px !important;
  color: #edf5ff !important;
  padding: 14px 18px !important;
  min-width: 190px;
  box-shadow: 0 12px 40px rgba(0,0,0,.5) !important;
}
.leaflet-tooltip-top:before, .leaflet-tooltip-bottom:before,
.leaflet-tooltip-left:before, .leaflet-tooltip-right:before { display: none !important; }
.popup-name { font-size: 14px; font-weight: 800; color: white; margin-bottom: 2px; letter-spacing: .02em; }
.popup-dept { font-size: 10px; color: #8ea6c2; margin-bottom: 10px; text-transform: uppercase; letter-spacing: .06em; }
.popup-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 5px 0; border-bottom: 1px solid rgba(255,255,255,.06); font-size: 12px; }
.popup-row:last-of-type { border-bottom: none; }
.popup-row .k { color: #8ea6c2; }
.popup-row .v { font-weight: 700; }
.popup-action { margin-top: 10px; text-align: center; color: #48d7ff; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }

.leaflet-container {
  background: #07111f !important;
  font-family: 'Outfit', 'Inter', sans-serif;
}
</style>
