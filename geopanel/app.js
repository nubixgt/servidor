/* ═══════════════════════════════════════════════════════════════
   GeoPanel app.js — Municipalidad Sanarate, El Progreso
   Powered by Mapbox GL JS (3D Globe Experience)
═══════════════════════════════════════════════════════════════ */

if (window.location.protocol === 'file:') {
    alert('CORS Warning: El mapa y los datos no cargarán si abres el archivo directamente (file://). Por favor usa un servidor local (http://localhost/...) o XAMPP.');
}

/* ── MAPA (MAPBOX GL JS) ─────────────────────────────────────── */
const MB_TOKEN = 'pk.eyJ1IjoiYm9sYXMyMDAyIiwiYSI6ImNtbTFma2ZyNTA5Zzcyc29jeDFodzN2eHgifQ.2pUJZRq3roeX6mSb12ImUw';
mapboxgl.accessToken = MB_TOKEN;

const map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/satellite-streets-v12',
    center: [-90.1, 14.8],
    zoom: 8.5,
    projection: 'globe', // Google Earth look
    antialias: true
});

map.on('style.load', () => {
    map.setFog({
        'color': 'rgb(186, 210, 235)', // Lower atmosphere
        'high-color': 'rgb(36, 92, 223)', // Upper atmosphere
        'horizon-blend': 0.02, // Atmosphere thickness
        'space-color': 'rgb(11, 11, 25)', // Background color
        'star-intensity': 0.6 // Background star brightness
    });
    
    // Add terrain for 3D mountains
    map.addSource('mapbox-dem', {
        'type': 'raster-dem',
        'url': 'mapbox://mapbox.mapbox-terrain-dem-v1',
        'tileSize': 512,
        'maxzoom': 14
    });
    map.setTerrain({ 'source': 'mapbox-dem', 'exaggeration': 1.5 });
});

/* ── ESTADO GLOBAL ───────────────────────────────────────────── */
let allBoletas      = [];
let filteredBoletas = [];
let activeFilters   = {};
let listSearchQ     = '';
let modalImages     = [];
let modalImageIdx   = 0;

/* ── TILT & CAMERA ── */
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

/* ── DOM ELEMENTS ── */
const globalSearch  = document.getElementById('globalSearch');
const welcomePanel  = document.getElementById('welcomePanel');
const analysisPanel = document.getElementById('analysisPanel');
const layersPanel   = document.getElementById('layersPanel');
const dynamicFilters = document.getElementById('dynamicFilters');
const infoCoords    = document.getElementById('infoCoords');
const infoElevation = document.getElementById('infoElevation');
const loadingStatus = document.getElementById('loadingStatus');
const statusText    = document.getElementById('statusText');

/* ── BASEMAPS ── */
const BASEMAPS = {
    hibrido:   'mapbox://styles/mapbox/satellite-streets-v12',
    satellite: 'mapbox://styles/mapbox/satellite-v9',
    streets:   'mapbox://styles/mapbox/streets-v12',
    topo:      'mapbox://styles/mapbox/outdoors-v12'
};

function switchBasemap(name) {
    const style = BASEMAPS[name];
    if (style) map.setStyle(style);
    document.querySelectorAll('.pill-btn[data-bm]').forEach(b => b.classList.toggle('active', b.dataset.bm === name));
}

document.querySelectorAll('.pill-btn[data-bm]').forEach(btn =>
    btn.addEventListener('click', () => switchBasemap(btn.dataset.bm))
);

/* ── NAVIGATION ── */
window.showPanel = function(panelId) {
    [welcomePanel, analysisPanel, layersPanel].forEach(p => { if (p) p.style.display = 'none'; });
    const target = document.getElementById(panelId);
    if (target) target.style.display = 'flex';
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.toggle('active', item.getAttribute('onclick')?.includes(panelId));
    });
};

/* ── HELPERS ── */
function extractCoords(b) {
    let la = b.location?.lat ?? b.location?.latitude ?? b.lat ?? b.latitude
          ?? b.latitud ?? b.coordenada_lat ?? b.coord_lat ?? b.y ?? null;
    let lo = b.location?.lng ?? b.location?.lon ?? b.location?.longitude
          ?? b.lng ?? b.lon ?? b.longitude ?? b.longitud
          ?? b.coordenada_lng ?? b.coord_lng ?? b.x ?? null;
    if ((la===null||lo===null) && Array.isArray(b.coordinates) && b.coordinates.length>=2) {
        lo = b.coordinates[0]; la = b.coordinates[1];
    }
    const lan = parseFloat(la), lon = parseFloat(lo);
    const ok = !isNaN(lan)&&!isNaN(lon)&&lan!==0&&lon!==0 &&lan>=-90&&lan<=90&&lon>=-180&&lon<=180;
    return ok ? { lat: lan, lon } : null;
}

function extractImages(b) {
    if (b._image_urls && Array.isArray(b._image_urls)) return b._image_urls.map(x => x.url);
    const imgs = [];
    const scan = o => {
        if (!o||typeof o!=='object') return;
        for (const [,v] of Object.entries(o)) {
            if (typeof v==='string' && /\.(jpg|jpeg|png|gif|webp|bmp|svg)(\?.*)?$/i.test(v)) imgs.push(v);
            else if (Array.isArray(v)) v.forEach(x => typeof x==='string' ? /\.(jpg|jpeg|png|gif|webp|bmp|svg)(\?.*)?$/i.test(x) && imgs.push(x) : scan(x));
            else if (typeof v==='object'&&v!==null) scan(v);
        }
    };
    scan(b);
    return [...new Set(imgs)];
}

function getName(b, i) {
    return b.nombre||b.vecino||b.name||b.nombre_completo||b.solicitante||b.citizen?.name||`Boleta #${b.boleta_number||b.id||i+1}`;
}

function getEmoji(b) {
    const T_KW = [
        { kw: ['bolsa','viver','vivere','aliment','comida'], em: '🍎' },
        { kw: ['medic','salud','clinica'], em: '💉' },
        { kw: ['lamin','techo','zinc'], em: '🏠' },
    ];
    for (const f of ['boleta_type','benefit','tipo']) {
        if (!b[f]) continue;
        const v = String(b[f]).toLowerCase();
        for (const { kw, em } of T_KW) if (kw.some(k => v.includes(k))) return em;
    }
    return '📍';
}

/* ── DATA LOADING ── */
async function loadFromAPI() {
    try {
        if (loadingStatus) loadingStatus.style.display = 'flex';
        const r = await fetch('api-proxy.php?all_pages=true');
        const res = await r.json();
        const raw = res.data || res.boletas || [];
        
        allBoletas = raw.map((b,i) => ({
            ...b, _idx: i, _name: getName(b,i), _imgs: extractImages(b), _coords: extractCoords(b), _emoji: getEmoji(b)
        }));

        updateMarkers();
        buildFilters();
        if (loadingStatus) loadingStatus.style.display = 'none';
    } catch(e) {
        console.error(e);
        if (statusText) statusText.textContent = 'Error';
    }
}

/* ── MARKERS & CLUSTERING ── */
function updateMarkers() {
    // Clear old markers if any
    document.querySelectorAll('.mapboxgl-marker').forEach(m => m.remove());
    
    filteredBoletas = allBoletas.filter(b => {
        if (!b._coords) return false;
        if (listSearchQ && !Object.values(b).join(' ').toLowerCase().includes(listSearchQ.toLowerCase())) return false;
        return true;
    });

    filteredBoletas.forEach(b => {
        const el = document.createElement('div');
        el.className = 'emoji-pin';
        el.innerHTML = b._emoji;
        el.onclick = () => showDetail(b);

        new mapboxgl.Marker(el)
            .setLngLat([b._coords.lon, b._coords.lat])
            .addTo(map);
    });
}

function showDetail(b) {
    console.log('Detail:', b);
    // You can implement your own popup or floating panel here
    new mapboxgl.Popup({ offset: 25, className: 'custom-popup' })
        .setLngLat([b._coords.lon, b._coords.lat])
        .setHTML(`<div class="popup-inner"><h3 class="popup-title">${b._name}</h3><button class="popup-btn" onclick="window.openPanel('analysisPanel')">Ver detalles</button></div>`)
        .addTo(map);
}

/* ── FILTERS ── */
function buildFilters() {
    if (!dynamicFilters) return;
    dynamicFilters.innerHTML = '<div class="filter-placeholder">Filtros dinámicos listos</div>';
}

function fullReset() {
    listSearchQ = '';
    if (globalSearch) globalSearch.value = '';
    updateMarkers();
}

/* ── EVENTS ── */
map.on('mousemove', (e) => {
    const { lat, lng } = e.lngLat;
    if (infoCoords) infoCoords.textContent = `${lat.toFixed(4)}° N, ${lng.toFixed(4)}° O`;
    if (infoElevation) {
        const elev = map.queryTerrainElevation(e.lngLat) || 0;
        infoElevation.textContent = `${Math.round(elev).toLocaleString()} m`;
    }
});

document.getElementById('zoomIn').onclick  = () => map.zoomIn();
document.getElementById('zoomOut').onclick = () => map.zoomOut();
document.getElementById('fitBounds').onclick = () => {
    const coords = filteredBoletas.filter(b => b._coords).map(b => [b._coords.lon, b._coords.lat]);
    if (!coords.length) return;
    const bounds = coords.reduce((acc, curr) => acc.extend(curr), new mapboxgl.LngLatBounds(coords[0], coords[0]));
    map.fitBounds(bounds, { padding: 50 });
};

document.addEventListener('keydown', e => {
    if (e.key === '/' && document.activeElement.tagName !== 'INPUT') {
        e.preventDefault();
        globalSearch?.focus();
    }
});

loadFromAPI();
