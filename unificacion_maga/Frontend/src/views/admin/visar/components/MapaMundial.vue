<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { Maximize2, Minimize2, RefreshCcw, Map as MapIcon } from 'lucide-vue-next'

const props = defineProps({
  data: {
    type: Array,
    default: () => []
  },
  type: {
    type: String,
    default: 'export' // 'export' or 'import'
  }
})

const mapContainer = ref(null)
let map = null
let geojsonLayer = null

const normalizeText = (text) => {
  if (!text) return ''
  return text.toString().normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toUpperCase()
    .trim()
}

const COUNTRY_MAP = {
    'ESTADOS UNIDOS': 'UNITED STATES OF AMERICA', 'USA': 'UNITED STATES OF AMERICA',
    'CANADA': 'CANADA', 'MEXICO': 'MEXICO',
    'COSTA RICA': 'COSTA RICA', 'PANAMA': 'PANAMA', 'NICARAGUA': 'NICARAGUA',
    'HONDURAS': 'HONDURAS', 'EL SALVADOR': 'EL SALVADOR', 'GUATEMALA': 'GUATEMALA',
    'REPUBLICA DOMINICANA': 'DOMINICAN REPUBLIC', 'ECUADOR': 'ECUADOR', 'PERU': 'PERU', 
    'BRASIL': 'BRAZIL', 'ARGENTINA': 'ARGENTINA', 'CHILE': 'CHILE', 'COLOMBIA': 'COLOMBIA', 
    'VENEZUELA': 'VENEZUELA', 'ESPANA': 'SPAIN', 'FRANCIA': 'FRANCE', 'ITALIA': 'ITALY', 
    'ALEMANIA': 'GERMANY', 'REINO UNIDO': 'UNITED KINGDOM', 'HOLANDA': 'NETHERLANDS',
    'PAISES BAJOS': 'NETHERLANDS', 'BELGICA': 'BELGIUM', 'SUIZA': 'SWITZERLAND', 
    'CHINA': 'CHINA', 'JAPON': 'JAPAN', 'COREA DEL SUR': 'SOUTH KOREA',
    'INDIA': 'INDIA', 'TAILANDIA': 'THAILAND', 'AUSTRALIA': 'AUSTRALIA',
    'RUSIA': 'RUSSIA', 'NUEVA ZELANDA': 'NEW ZEALAND', 'TAIWAN': 'TAIWAN',
    'TURQUIA': 'TURKEY', 'EGIPTO': 'EGYPT', 'SUDAFRICA': 'SOUTH AFRICA',
    'MARRUECOS': 'MOROCCO', 'SUECIA': 'SWEDEN', 'NORUEGA': 'NORWAY',
    'DINAMARCA': 'DENMARK', 'FINLANDIA': 'FINLAND', 'POLONIA': 'POLAND',
    'AUSTRIA': 'AUSTRIA', 'BELICE': 'BELIZE', 'PUERTO RICO': 'PUERTO RICO',
    'JAMAICA': 'JAMAICA', 'HAITI': 'HAITI', 'CUBA': 'CUBA', 'VIETNAM': 'VIETNAM',
    'FILIPINAS': 'PHILIPPINES', 'INDONESIA': 'INDONESIA', 'MALASIA': 'MALAYSIA',
    'SINGAPUR': 'SINGAPORE', 'ISRAEL': 'ISRAEL', 'PAKISTAN': 'PAKISTAN',
    'KENIA': 'KENYA', 'NIGERIA': 'NIGERIA', 'ETIOPIA': 'ETHIOPIA',
    'EMIRATOS ARABES UNIDOS': 'UNITED ARAB EMIRATES', 'ARABIA SAUDITA': 'SAUDI ARABIA',
    'ARABIA SAUDI': 'SAUDI ARABIA', 'PORTUGAL': 'PORTUGAL', 'IRLANDA': 'IRELAND',
    'GRECIA': 'GREECE', 'UCRANIA': 'UKRAINE', 'BOLIVIA': 'BOLIVIA',
    'PARAGUAY': 'PARAGUAY', 'URUGUAY': 'URUGUAY', 'HONG KONG': 'HONG KONG'
};

const ENG_TO_ES = {
    'UNITED STATES OF AMERICA': 'Estados Unidos', 'CANADA': 'Canadá', 'MEXICO': 'México',
    'DOMINICAN REPUBLIC': 'República Dominicana', 'PERU': 'Perú', 'BRAZIL': 'Brasil',
    'SPAIN': 'España', 'FRANCE': 'Francia', 'ITALY': 'Italia', 'GERMANY': 'Alemania',
    'UNITED KINGDOM': 'Reino Unido', 'NETHERLANDS': 'Países Bajos', 'BELGIUM': 'Bélgica',
    'SWITZERLAND': 'Suiza', 'JAPAN': 'Japón', 'SOUTH KOREA': 'Corea del Sur',
    'RUSSIA': 'Rusia', 'NEW ZEALAND': 'Nueva Zelanda', 'TURKEY': 'Turquía',
    'EGYPT': 'Egipto', 'SOUTH AFRICA': 'Sudáfrica', 'MOROCCO': 'Marruecos',
    'SWEDEN': 'Suecia', 'NORWAY': 'Noruega', 'DENMARK': 'Dinamarca',
    'FINLAND': 'Finlandia', 'POLAND': 'Polonia', 'BELIZE': 'Belice',
    'HAITI': 'Haití', 'PHILIPPINES': 'Filipinas', 'MALAYSIA': 'Malasia',
    'SINGAPORE': 'Singapur', 'PAKISTAN': 'Pakistán', 'KENYA': 'Kenia',
    'ETHIOPIA': 'Etiopía', 'UNITED ARAB EMIRATES': 'Emiratos Árabes Unidos',
    'SAUDI ARABIA': 'Arabia Saudita', 'IRELAND': 'Irlanda', 'GREECE': 'Grecia',
    'UKRAINE': 'Ucrania'
};

const translateToSpanish = (engName) => {
    return ENG_TO_ES[engName] || engName;
}

const formatNum = (val) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val || 0)
const formatInt = (val) => new Intl.NumberFormat('en-US').format(val || 0)

const isExport = () => props.type === 'export'

const getTooltipContent = (name, data) => {
  const valueLabel = isExport() ? 'FOB' : 'CIF/Valor'
  const valField = isExport() ? data.valor_fob : data.valor_fob // ImportacionesRepository mapea valor_dolares a valor_fob en map_data para mantener consistencia
  const displayName = (data.pais || name).toUpperCase()
  
  let content = `
    <div class="text-[10px] font-black uppercase mb-2 border-b border-white/20 pb-1">${displayName}</div>
    <div class="flex flex-col gap-1.5">
      <div class="flex justify-between gap-6">
        <span class="opacity-70">Valor ${valueLabel}:</span>
        <span class="font-bold text-emerald-400">$${formatNum(valField)}</span>
      </div>
      <div class="flex justify-between gap-6">
        <span class="opacity-70">Peso Neto (KG):</span>
        <span class="font-bold text-blue-300">${formatNum(data.peso)}</span>
      </div>
      <div class="flex justify-between gap-6">
        <span class="opacity-70">Total Operaciones:</span>
        <span class="font-bold text-amber-300">${formatInt(data.exportaciones)}</span>
      </div>
    </div>
  `
  
  if (data.top_productos && data.top_productos.length > 0) {
    content += `
      <div class="mt-2 pt-2 border-t border-white/10">
        <div class="text-[8px] opacity-70 mb-1 uppercase tracking-widest">Top Productos</div>
        <div class="flex flex-col gap-0.5">
          ${data.top_productos.map(p => `
            <div class="flex justify-between text-[9px] gap-4">
              <span class="truncate max-w-[120px]" title="${p.producto}">${p.producto}</span>
              <span class="opacity-80">${p.cantidad}</span>
            </div>
          `).join('')}
        </div>
      </div>
    `
  }
  
  return content
}

const getColor = (valor) => {
    if (valor > 1000000) return '#fb923c';
    if (valor > 500000) return '#facc15';
    if (valor > 100000) return '#a3e635';
    if (valor > 50000) return '#4ade80';
    if (valor > 0) return '#22c55e';
    return '#1e293b'; // Empty state
}

const styleFeature = (feature) => {
    const name = feature.properties.ADMIN?.toUpperCase() || feature.properties.name?.toUpperCase() || ''
    const countryData = props.data.find(d => {
        const dName = normalizeText(d.pais)
        return (COUNTRY_MAP[dName] || dName) === name
    })
    
    const valor = countryData ? (parseFloat(countryData.valor_fob) || 0) : 0
    
    return {
        fillColor: getColor(valor),
        weight: 1,
        opacity: 1,
        color: 'rgba(255,255,255,0.2)',
        fillOpacity: valor > 0 ? 0.8 : 0.4
    }
}

const highlightFeature = (e) => {
  const layer = e.target
  layer.setStyle({
    weight: 2,
    color: '#fff',
    fillOpacity: 1
  })
  layer.bringToFront()
}

const resetHighlight = (e) => {
  geojsonLayer.resetStyle(e.target)
}

const initialBounds = [[-60, -120], [70, 120]]

const resetMap = () => {
    map.fitBounds(initialBounds)
}

const initMap = async () => {
    if (map) return
    
    map = L.map(mapContainer.value, {
        center: [20, 0],
        zoom: 2,
        minZoom: 2,
        maxZoom: 10,
        maxBounds: [[-90, -180], [90, 180]],
        maxBoundsViscosity: 1.0,
        zoomControl: false,
        attributionControl: false
    })

    // Dark theme map
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap &copy; CartoDB',
        maxZoom: 10,
        noWrap: true
    }).addTo(map)
    
    L.control.zoom({ position: 'topright' }).addTo(map)

    try {
        const res = await fetch('https://raw.githubusercontent.com/datasets/geo-countries/master/data/countries.geojson')
        const geoData = await res.json()
        
        renderGeoJSON(geoData)
        map.fitBounds(initialBounds)
    } catch (e) {
        console.error('Error cargando GeoJSON:', e)
    }
}

let cachedGeoData = null

const renderGeoJSON = (geoData) => {
    if (!geoData && cachedGeoData) geoData = cachedGeoData
    if (geoData) cachedGeoData = geoData
    if (!cachedGeoData) return

    if (geojsonLayer) map.removeLayer(geojsonLayer)
    
    geojsonLayer = L.geoJSON(cachedGeoData, {
        style: styleFeature,
        onEachFeature: (feature, layer) => {
            layer.on({
                mouseover: highlightFeature,
                mouseout: resetHighlight,
                click: (ev) => map.flyToBounds(ev.target.getBounds(), { padding: [50, 50] })
            })
            
            const name = feature.properties.ADMIN?.toUpperCase() || feature.properties.name?.toUpperCase() || ''
            const countryData = props.data.find(d => {
                const dName = normalizeText(d.pais)
                return (COUNTRY_MAP[dName] || dName) === name
            })
            
            if (countryData) {
                layer.bindTooltip(getTooltipContent(name, countryData), { className: 'map-tooltip', sticky: true })
            } else {
                const displayName = translateToSpanish(name).toUpperCase()
                layer.bindTooltip(`<div class="text-[10px] font-black uppercase tracking-widest text-slate-400">${displayName}</div><div class="text-xs mt-1 text-slate-500">Sin registros</div>`, { className: 'map-tooltip', sticky: true })
            }
        }
    }).addTo(map)
}

onMounted(() => {
    initMap()
})

onUnmounted(() => {
    if (map) map.remove()
})

watch(() => props.data, () => {
    renderGeoJSON()
}, { deep: true })
</script>

<template>
  <div class="relative w-full h-[500px] overflow-hidden rounded-2xl shadow-sm border border-slate-100 bg-slate-900">
    <!-- Header info Overlay -->
    <div class="absolute top-6 left-6 z-[1000] flex flex-col gap-3 pointer-events-none">
      <div class="flex items-center gap-3 bg-slate-900/80 backdrop-blur-md border border-white/10 p-3 rounded-2xl shadow-2xl">
        <div class="p-2 bg-blue-500 rounded-xl shadow-lg shadow-blue-500/30">
          <MapIcon class="w-5 h-5 text-white"/>
        </div>
        <div>
          <h3 class="text-sm font-black text-white leading-tight uppercase tracking-widest">Mapa de {{ isExport() ? 'Exportaciones' : 'Importaciones' }}</h3>
          <p class="text-[9px] font-bold text-blue-300 uppercase tracking-widest opacity-90">VISAR · Mundial</p>
        </div>
      </div>
    </div>

    <!-- Map Container -->
    <div ref="mapContainer" class="w-full h-full bg-[#0f172a] z-0"></div>

    <!-- Legend -->
    <div class="absolute bottom-6 left-6 z-[1000] bg-slate-900/80 backdrop-blur-md border border-white/10 p-5 rounded-3xl shadow-2xl pointer-events-none">
      <h4 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4">Escala Monetaria ($)</h4>
      <div class="flex gap-2">
        <div v-for="scale in [
          { color: '#1e293b', label: '0' },
          { color: '#22c55e', label: '>0' },
          { color: '#4ade80', label: '>50k' },
          { color: '#a3e635', label: '>100k' },
          { color: '#facc15', label: '>500k' },
          { color: '#fb923c', label: '>1M' }
        ]" :key="scale.label" class="flex flex-col items-center gap-2">
          <div class="w-8 h-2 rounded-full" :style="{ backgroundColor: scale.color }"></div>
          <span class="text-[7px] font-black text-slate-200 uppercase">{{ scale.label }}</span>
        </div>
      </div>
    </div>

    <!-- Controls button -->
    <div class="absolute bottom-6 right-6 z-[1000] flex flex-col gap-2">
      <button @click="resetMap" class="p-3 bg-slate-800/80 backdrop-blur-xl border border-white/10 shadow-2xl rounded-xl text-white hover:bg-slate-700 transition-all active:scale-95 cursor-pointer" title="Restaurar vista">
        <RefreshCcw class="w-4 h-4"/>
      </button>
    </div>
  </div>
</template>

<style>
.map-tooltip {
  background: rgba(15, 23, 42, 0.95) !important;
  backdrop-filter: blur(12px) !important;
  border: 1px solid rgba(255,255,255,0.1) !important;
  border-radius: 12px !important;
  color: white !important;
  padding: 12px 16px !important;
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.5) !important;
}
.leaflet-container {
  background: transparent !important;
}
.leaflet-control-zoom {
  border: none !important;
  box-shadow: 0 4px 15px rgba(0,0,0,0.3) !important;
  margin-top: 24px !important;
  margin-right: 24px !important;
}
.leaflet-control-zoom-in, .leaflet-control-zoom-out {
  background: rgba(30, 41, 59, 0.9) !important;
  backdrop-filter: blur(10px) !important;
  border: 1px solid rgba(255,255,255,0.1) !important;
  color: white !important;
}
.leaflet-control-zoom-in:hover, .leaflet-control-zoom-out:hover {
  background: rgba(51, 65, 85, 0.95) !important;
}
</style>
