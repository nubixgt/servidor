/* ═══════════════════════════════════════════════════════════════
   Nubix GeoPanel app.js — Municipalidad Sanarate, El Progreso
   Powered by Mapbox GL JS (3D Globe Experience)
═══════════════════════════════════════════════════════════════ */

if (window.location.protocol === 'file:') {
    alert('CORS Warning: El mapa y los datos no cargarán si abres el archivo directamente (file://). Por favor usa un servidor local (http://localhost/...) o XAMPP.');
}

/* ── CONFIG ── */
const MB_TOKEN = 'pk.eyJ1IjoiYm9sYXMyMDAyIiwiYSI6ImNtbTFma2ZyNTA5Zzcyc29jeDFodzN2eHgifQ.2pUJZRq3roeX6mSb12ImUw';
mapboxgl.accessToken = MB_TOKEN;

/* ── MAP INIT ── */
const map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/satellite-streets-v12',
    center: [-90.1, 14.8],
    zoom: 8.5,
    projection: 'globe',
    antialias: true
});

map.on('style.load', () => {
    map.setFog({
        'color': 'rgba(186, 210, 235, 0.4)',
        'high-color': 'rgba(36, 92, 223, 0.4)',
        'horizon-blend': 0.02,
        'space-color': 'rgb(11, 11, 25)',
        'star-intensity': 0.6
    });
    
    map.addSource('mapbox-dem', {
        'type': 'raster-dem',
        'url': 'mapbox://mapbox.mapbox-terrain-dem-v1',
        'tileSize': 512,
        'maxzoom': 14
    });
    map.setTerrain({ 'source': 'mapbox-dem', 'exaggeration': 1.5 });
});

/* ── DATA & STATE ── */
let allBoletas = [];
let filteredBoletas = [];
const globalSearch = document.getElementById('globalSearch');
const welcomePanel = document.getElementById('welcomePanel');
const analysisPanel = document.getElementById('analysisPanel');
const layersPanel = document.getElementById('layersPanel');
const infoCoords = document.getElementById('infoCoords');
const infoElevation = document.getElementById('infoElevation');

/* ── UI NAVIGATION ── */
window.showPanel = function(panelId) {
    [welcomePanel, analysisPanel, layersPanel].forEach(p => { if (p) p.style.display = 'none'; });
    const target = document.getElementById(panelId);
    if (target) target.style.display = 'flex';
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.toggle('active', item.getAttribute('onclick')?.includes(panelId));
    });
};

/* ── DATA FETCH ── */
async function loadData() {
    try {
        const r = await fetch('api-proxy.php?all_pages=true');
        const res = await r.json();
        const raw = res.data || res.boletas || [];
        
        allBoletas = raw.map((b,i) => ({
            ...b,
            _idx: i,
            _coords: extractCoords(b),
            _name: b.nombre || b.vecino || `Boleta #${i+1}`
        }));

        initMapLayers();
    } catch(e) {
        console.error('Error loading data:', e);
    }
}

function extractCoords(b) {
    let la = b.location?.lat ?? b.lat ?? b.latitud ?? b.coordenada_lat ?? null;
    let lo = b.location?.lng ?? b.lon ?? b.longitude ?? b.longitud ?? b.coordenada_lng ?? null;
    const lan = parseFloat(la), lon = parseFloat(lo);
    return (!isNaN(lan) && !isNaN(lon)) ? { lat: lan, lon } : null;
}

/* ── MAP LAYERS & CLUSTERING ── */
function initMapLayers() {
    const geojson = {
        type: 'FeatureCollection',
        features: allBoletas.filter(b => b._coords).map(b => ({
            type: 'Feature',
            geometry: { type: 'Point', coordinates: [b._coords.lon, b._coords.lat] },
            properties: { id: b._idx, name: b._name }
        }))
    };

    if (map.getSource('boletas')) {
        map.getSource('boletas').setData(geojson);
    } else {
        map.addSource('boletas', {
            type: 'geojson',
            data: geojson,
            cluster: true,
            clusterMaxZoom: 14,
            clusterRadius: 50
        });

        // Cluster Circles
        map.addLayer({
            id: 'clusters',
            type: 'circle',
            source: 'boletas',
            filter: ['has', 'point_count'],
            paint: {
                'circle-color': 'rgba(59, 130, 246, 0.4)',
                'circle-radius': 22,
                'circle-stroke-width': 2,
                'circle-stroke-color': 'rgba(255, 255, 255, 0.4)'
            }
        });

        // Cluster Counts
        map.addLayer({
            id: 'cluster-count',
            type: 'symbol',
            source: 'boletas',
            filter: ['has', 'point_count'],
            layout: {
                'text-field': '{point_count_abbreviated}',
                'text-font': ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
                'text-size': 14
            },
            paint: {
                'text-color': '#ffffff'
            }
        });

        // Unclustered Points
        map.addLayer({
            id: 'unclustered-point',
            type: 'circle',
            source: 'boletas',
            filter: ['!', ['has', 'point_count']],
            paint: {
                'circle-color': '#3b82f6',
                'circle-radius': 8,
                'circle-stroke-width': 2,
                'circle-stroke-color': '#fff'
            }
        });

        // Add glow effect to clusters (simulated)
        map.addLayer({
            id: 'clusters-glow',
            type: 'circle',
            source: 'boletas',
            filter: ['has', 'point_count'],
            paint: {
                'circle-color': 'rgba(59, 130, 246, 0.2)',
                'circle-radius': 30,
                'circle-blur': 1
            }
        }, 'clusters');
    }
}

/* ── CAMERA & TILT ── */
const btn2D = document.getElementById('btn2D');
const btn3D = document.getElementById('btn3D');
if (btn2D && btn3D) {
    btn3D.onclick = () => {
        map.easeTo({ pitch: 60, bearing: -20, duration: 1000 });
        btn3D.classList.add('active');
        btn2D.classList.remove('active');
    };
    btn2D.onclick = () => {
        map.easeTo({ pitch: 0, bearing: 0, duration: 1000 });
        btn2D.classList.add('active');
        btn3D.classList.remove('active');
    };
}

document.getElementById('zoomIn').onclick = () => map.zoomIn();
document.getElementById('zoomOut').onclick = () => map.zoomOut();
document.getElementById('fitBounds').onclick = () => {
    const coords = allBoletas.filter(b => b._coords).map(b => [b._coords.lon, b._coords.lat]);
    if (!coords.length) return;
    const bounds = coords.reduce((acc, curr) => acc.extend(curr), new mapboxgl.LngLatBounds(coords[0], coords[0]));
    map.fitBounds(bounds, { padding: 80 });
};

/* ── TELEMETRY ── */
map.on('mousemove', (e) => {
    const { lat, lng } = e.lngLat;
    if (infoCoords) infoCoords.textContent = `${lat.toFixed(4)}° N, ${lng.toFixed(4)}° O`;
    if (infoElevation) {
        const elev = map.queryTerrainElevation(e.lngLat) || 0;
        infoElevation.textContent = `${Math.round(elev).toLocaleString()} m`;
    }
});

/* ── SEARCH ── */
if (globalSearch) {
    globalSearch.addEventListener('input', () => {
        const q = globalSearch.value.toLowerCase();
        // Implement filtering logic here if needed
    });
}

loadData();
