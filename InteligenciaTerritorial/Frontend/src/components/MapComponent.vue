<template>
  <div class="map-section">
    <div class="map-topbar">
      <div>
        <h2><i class="fas fa-globe-americas"></i> Mapa de Guatemala</h2>
        <div class="breadcrumb">
          <span style="cursor:pointer;color:var(--muted)" @click="store.selectDept(null)">Guatemala</span>
          <span v-if="store.selectedDept" class="sep">›</span>
          <span v-if="store.selectedDept">{{ store.selectedDept }}</span>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:10px;">
        <span class="level-badge">{{ store.selectedDept ? deptMunis.length + ' municipios' : '22 Departamentos' }}</span>
        <div class="map-controls">
          <button v-if="store.selectedDept" class="map-btn back-btn" @click="store.selectDept(null)">
            <i class="fas fa-arrow-left"></i> <span>Volver</span>
          </button>
          <button class="map-btn" @click="map?.zoomIn()" title="Acercar"><i class="fas fa-plus"></i></button>
          <button class="map-btn" @click="map?.zoomOut()" title="Alejar"><i class="fas fa-minus"></i></button>
          <button class="map-btn" @click="store.selectDept(null)" title="Restablecer"><i class="fas fa-sync-alt"></i></button>
        </div>
      </div>
    </div>
    <div id="map" ref="mapContainer"></div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch, computed } from 'vue';
import { useMunicipiosStore } from '../stores/municipios';
import L from 'leaflet';
import * as topojson from 'topojson-client';

const store = useMunicipiosStore();
const mapContainer = ref(null);
let map = null;
let deptLayer = null;
let muniLayer = null;
let DEPTOS_GEO = null;
let MUNIS_GEO = null;

const GT_CENTER = [15.5, -90.25];

const deptMunis = computed(() => {
  return store.municipios.filter(m => m.departamento === store.selectedDept);
});

// Colores por partido
const PARTY_COLORS = {
  'VAMOS': '#2f81f7', 'UNE': '#f7a22f', 'CABAL': '#37d39a',
  'TODOS': '#ff6978', 'AZUL': '#48d7ff', 'PAN': '#ffca5c',
  'WINAQ': '#c084fc', 'PODEMOS': '#fb923c', 'FCN': '#f43f5e'
};

function partyColor(partido) {
  return PARTY_COLORS[partido] || '#8ea6c2';
}
function norm(s) { return (s||'').toUpperCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim(); }

onMounted(async () => {
  map = L.map(mapContainer.value, { zoomControl: false, attributionControl: false, minZoom: 6, maxZoom: 13 });
  map.setView(GT_CENTER, 7);
  
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png', {
    subdomains: 'abcd', maxZoom: 19
  }).addTo(map);

  await loadData();
});

async function loadData() {
  const [dRes, mRes] = await Promise.all([
    fetch('/js/deptos.json'),
    fetch('/js/munis.json')
  ]);
  const dTopo = await dRes.json();
  const mTopo = await mRes.json();

  const dKey = Object.keys(dTopo.objects)[0];
  const mKey = Object.keys(mTopo.objects)[0];
  DEPTOS_GEO = topojson.feature(dTopo, dTopo.objects[dKey]);
  MUNIS_GEO  = topojson.feature(mTopo, mTopo.objects[mKey]);

  drawDepts();
}

function drawDepts() {
  if (!map) return;
  if (deptLayer) map.removeLayer(deptLayer);
  if (muniLayer) { map.removeLayer(muniLayer); muniLayer = null; }

  deptLayer = L.geoJSON(DEPTOS_GEO, {
    style: { fillColor: '#1d3a5c', weight: 1.5, opacity: 1, color: '#2f81f7', fillOpacity: 0.55 },
    onEachFeature: (feature, layer) => {
      const dept = feature.properties.Departamento || feature.properties.departamento || 'Desconocido';
      const munisCount = store.municipios.filter(m => m.departamento === dept).length;
      
      layer.bindTooltip(`
        <div class="popup-name">${dept}</div>
        <div class="popup-dept">Departamento</div>
        <div class="popup-row"><span class="k">Municipios</span><span class="v">${munisCount}</span></div>
        <div class="popup-action">Click para explorar municipios →</div>
      `, { sticky: true, className: '', opacity: 1 });

      layer.on({
        mouseover: e => e.target.setStyle({ fillColor: '#2f6fd1', fillOpacity: 0.8, weight: 2, color: '#48d7ff' }),
        mouseout: e => deptLayer.resetStyle(e.target),
        click: () => store.selectDept(dept)
      });
    }
  }).addTo(map);
  
  map.setView(GT_CENTER, 7);
}

function drawMunis(dept) {
  if (!map) return;
  if (deptLayer) map.removeLayer(deptLayer);
  if (muniLayer) map.removeLayer(muniLayer);

  const feat = DEPTOS_GEO.features.find(f => norm(f.properties.Departamento || f.properties.departamento) === norm(dept));
  
  deptLayer = L.geoJSON(feat, {
    style: { fillColor: 'transparent', weight: 2, color: '#48d7ff', fillOpacity: 0 }
  }).addTo(map);

  const deptMusis = {
    type: 'FeatureCollection',
    features: MUNIS_GEO.features.filter(f => norm(f.properties.Departamento || f.properties.departamento) === norm(dept))
  };

  const extra = store.departamentos.find(d => d.departamento === dept) || {};

  muniLayer = L.geoJSON(deptMusis, {
    style: (feature) => {
      const mName = feature.properties.Municipio || feature.properties.municipio;
      const mData = store.municipios.find(m => m.municipio === mName && m.departamento === dept);
      const color = mData ? partyColor(mData.partido_alcalde) : '#1d3351';
      return { fillColor: color, weight: 1, opacity: 1, color: 'rgba(255,255,255,0.25)', fillOpacity: 0.7 };
    },
    onEachFeature: (feature, layer) => {
      const mName = feature.properties.Municipio || feature.properties.municipio;
      const mData = store.municipios.find(m => m.municipio === mName && m.departamento === dept);

      layer.bindTooltip(`
        <div class="popup-name">${mName}</div>
        <div class="popup-dept">${dept}</div>
        ${mData ? `
          <div class="popup-row"><span class="k">Alcalde</span><span class="v">${mData.alcalde}</span></div>
          <div class="popup-party">${mData.partido_alcalde}</div>
        ` : ''}
        ${extra.diputado_asignado ? `<div class="popup-row" style="margin-top:6px"><span class="k">Diputado (${dept})</span><span class="v">${extra.diputado_asignado}</span></div>` : ''}
        ${extra.gpc ? `<div class="popup-row"><span class="k">GPC (${dept})</span><span class="v">${extra.gpc}</span></div>` : ''}
        <div class="popup-action">Click para ver más →</div>
      `, { sticky: true, opacity: 1 });

      layer.on({
        mouseover: e => e.target.setStyle({ fillOpacity: 1, weight: 2, color: 'white' }),
        mouseout: e => muniLayer.resetStyle(e.target),
        click: () => { if(mData) store.selectMuni(mData) }
      });
    }
  }).addTo(map);

  if (feat) {
    const bounds = L.geoJSON(feat).getBounds();
    map.fitBounds(bounds, { padding: [30, 30], maxZoom: 11 });
  }
}

watch(() => store.selectedDept, (newDept) => {
  if (newDept) {
    drawMunis(newDept);
  } else {
    drawDepts();
  }
});

// React to data changes from API
watch(() => store.departamentos, () => {
  if (store.selectedDept) {
    drawMunis(store.selectedDept);
  } else if (DEPTOS_GEO) {
    drawDepts();
  }
}, { deep: true });

</script>
