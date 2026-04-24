/* ═══════════════════════════════════════════════════════════════
   GeoPanel app.js — Municipalidad Sanarate, El Progreso
═══════════════════════════════════════════════════════════════ */

/* ── MAPA ────────────────────────────────────────────────────── */
const map = L.map('map', { zoomControl: false }).setView([14.85, -89.87], 11);
L.control.zoom({ position: 'bottomright' }).addTo(map);
const markerGroup = L.layerGroup().addTo(map);

/* ── BASEMAPS ────────────────────────────────────────────────── */
const MB_TOKEN = 'pk.eyJ1IjoiYm9sYXMyMDAyIiwiYSI6ImNtbTFma2ZyNTA5Zzcyc29jeDFodzN2eHgifQ.2pUJZRq3roeX6mSb12ImUw';
const MB_HYB  = `https://api.mapbox.com/styles/v1/mapbox/satellite-streets-v12/tiles/{z}/{x}/{y}?access_token=${MB_TOKEN}`;
const MB_NAV  = `https://api.mapbox.com/styles/v1/mapbox/navigation-day-v1/tiles/{z}/{x}/{y}?access_token=${MB_TOKEN}`;
const MB_SAT  = `https://api.mapbox.com/styles/v1/mapbox/satellite-v9/tiles/{z}/{x}/{y}?access_token=${MB_TOKEN}`;

const BASEMAPS = {
    hibrido:  { url: MB_HYB, attr: '© Mapbox', tileSize: 512, zoomOffset: -1 },
    satellite: { url: MB_NAV, attr: '© Mapbox', tileSize: 512, zoomOffset: -1 },
    topo:     { url: 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', attr: '© OpenTopoMap' },
    calor:    { url: MB_HYB, attr: '© Mapbox', tileSize: 512, zoomOffset: -1 },
};

let currentTile   = L.tileLayer(BASEMAPS.hibrido.url, { attribution: BASEMAPS.hibrido.attr, maxZoom: 22, tileSize: 512, zoomOffset: -1 }).addTo(map);
let overlayTile   = null;
let heatLayer     = null;
let currentBMName = 'hibrido';

function buildHeatLayer() {
    if (heatLayer) { map.removeLayer(heatLayer); heatLayer = null; }
    const pts = allBoletas.filter(b => b._coords).map(b => [b._coords.lat, b._coords.lon, 1]);
    if (!pts.length) return;
    heatLayer = L.heatLayer(pts, { radius: 28, blur: 22, maxZoom: 15,
        gradient: { 0.2: '#ffffb2', 0.4: '#fecc5c', 0.6: '#fd8d3c', 0.8: '#f03b20', 1.0: '#bd0026' }
    }).addTo(map);
}

function switchBasemap(name) {
    const bm = BASEMAPS[name]; if (!bm) return;
    currentBMName = name;

    if (heatLayer) { map.removeLayer(heatLayer); heatLayer = null; }
    if (overlayTile) { map.removeLayer(overlayTile); overlayTile = null; }
    map.removeLayer(currentTile);

    const opts = { attribution: bm.attr, maxZoom: 22 };
    if (bm.tileSize   != null) opts.tileSize   = bm.tileSize;
    if (bm.zoomOffset != null) opts.zoomOffset = bm.zoomOffset;
    currentTile = L.tileLayer(bm.url, opts).addTo(map);
    if (bm.overlay) overlayTile = L.tileLayer(bm.overlay, { maxZoom: 22 }).addTo(map);

    markerGroup.eachLayer(m => m.setOpacity && m.setOpacity(name === 'calor' ? 0 : 1));
    document.querySelectorAll('.bm-btn').forEach(b => b.classList.toggle('active', b.dataset.bm === name));
    if (name === 'calor') buildHeatLayer();
}
document.querySelectorAll('.bm-btn').forEach(btn =>
    btn.addEventListener('click', () => switchBasemap(btn.dataset.bm))
);

/* ── LÍMITE DEPARTAMENTAL EL PROGRESO ────────────────────────── */
(function loadDeptBoundary() {
    fetch('limites_departamentales.geojson')
        .then(r => r.json())
        .then(data => {
            const feature = data.features.find(f =>
                /progreso/i.test(f.properties?.name || f.properties?.NAME || '')
            );
            if (!feature) return;
            L.geoJSON(feature, {
                style: {
                    color:       '#4ade80',
                    weight:      2.5,
                    opacity:     0.9,
                    fillColor:   '#4ade80',
                    fillOpacity: 0.06,
                    dashArray:   '8 5',
                    lineCap:     'round',
                    lineJoin:    'round',
                },
                interactive: false,
            }).addTo(map);
        })
        .catch(() => {});
})();

/* ── ESTADO GLOBAL ───────────────────────────────────────────── */
let allBoletas      = [];
let filteredBoletas = [];
let boletaMarkers   = [];
let activeFilters   = {};
let activeListTab   = 'all';
let listSearchQ     = '';
let modalImages     = [];
let modalImageIdx   = 0;
let miniMap         = null;

/* ── TIPO → EMOJI ────────────────────────────────────────────── */
const TIPO_KW = [
    { kw: ['bolsa','viver','vivere','aliment','comida','vituall','racion','ración'], em: '🍎' },
    { kw: ['medic','jornada','salud','clinica'],                                     em: '💉' },
    { kw: ['lamin','techo','zinc'],                                                  em: '🏠' },
];
const TIPO_FIELDS = ['boleta_type','benefit','tipo','tipo_boleta','tipo_beneficio','beneficio','type','categoria','category','servicio'];

function getEmoji(b) {
    for (const f of TIPO_FIELDS) {
        if (!b[f]) continue;
        const v = String(b[f]).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
        for (const { kw, em } of TIPO_KW) {
            if (kw.some(k => v.includes(k))) return em;
        }
    }
    return '📍';
}

/* ── DOM ─────────────────────────────────────────────────────── */
const exportBtn     = document.getElementById('exportBtn');
const loadingBar    = document.getElementById('loadingBar');
const loadingText   = document.getElementById('loadingText');
const boletaList    = document.getElementById('boletaList');
const listCountEl   = document.getElementById('listCount');
const mapInfobar    = document.getElementById('mapInfobar');
const bottomPanel   = document.getElementById('bottomPanel');
const bottomOverlay = document.getElementById('bottomPanelOverlay');
const bottomContent = document.getElementById('bottomPanelContent');
const bottomTitle   = document.getElementById('bottomPanelTitle');
const dynFilters    = document.getElementById('dynamicFilters');
const imageModal    = document.getElementById('imageModal');
const modalImage    = document.getElementById('modalImage');

/* ── HELPERS ─────────────────────────────────────────────────── */
const isInGT = (la, lo) => la >= 13.5 && la <= 18.0 && lo >= -92.5 && lo <= -88.0;

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
    const ok = !isNaN(lan)&&!isNaN(lon)&&lan!==0&&lon!==0
             &&lan>=-90&&lan<=90&&lon>=-180&&lon<=180;
    return ok ? { lat: lan, lon } : null;
}

function extractImages(b) {
    if (b._image_urls && Array.isArray(b._image_urls))
        return b._image_urls.map(x => x.url);
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
    return b.nombre||b.vecino||b.name||b.nombre_completo
        ||b.solicitante||b.citizen?.name
        ||`Boleta #${b.boleta_number||b.id||i+1}`;
}
const FIELD_ES = {
    nombre: 'Nombre', name: 'Nombre', nombre_completo: 'Nombre completo',
    vecino: 'Vecino', solicitante: 'Solicitante', propietario: 'Propietario', titular: 'Titular',
    dpi: 'DPI', cedula: 'Cédula', identificacion: 'Identificación', documento: 'Documento',
    id: 'ID', boleta_number: 'No. Boleta', boleta_type: 'Tipo de boleta',
    tipo: 'Tipo', tipo_boleta: 'Tipo de boleta', tipo_beneficio: 'Tipo de beneficio',
    benefit: 'Beneficio', beneficio: 'Beneficio', servicio: 'Servicio',
    description: 'Descripción', descripcion: 'Descripción',
    status: 'Estado', estado: 'Estado', estatus: 'Estado', state: 'Estado',
    created_at: 'Fecha de creación', date_formatted: 'Fecha',
    fecha: 'Fecha', fecha_creacion: 'Fecha de creación', date: 'Fecha', created: 'Creado',
    aldea: 'Aldea', comunidad: 'Comunidad', municipio: 'Municipio',
    departamento: 'Departamento', direccion: 'Dirección', address: 'Dirección',
    creator: 'Creador', usuario: 'Usuario', user: 'Usuario',
    aldea_comunidad: 'Aldea / Comunidad',
};
function fmtKey(k) {
    return FIELD_ES[k] ?? FIELD_ES[k.toLowerCase()]
        ?? k.charAt(0).toUpperCase() + k.slice(1).replace(/_/g,' ');
}
function isImg(v)  { return typeof v==='string'&&/\.(jpg|jpeg|png|gif|webp|bmp|svg)(\?.*)?$/i.test(v); }
function esc(s)    { return String(s).replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
function hl(txt,q) {
    if (!q) return txt;
    return String(txt).replace(new RegExp(`(${q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')})`, 'gi'),
        '<mark class="hl">$1</mark>');
}
function searchText(b) {
    return Object.values(b).filter(v=>typeof v==='string'||typeof v==='number').join(' ').toLowerCase();
}

/* ── STATUS DETECTION ────────────────────────────────────────── */
const PEND_KW = ['pendiente','pending','en proceso','revisión','espera','waiting','open','abierto'];
const APPR_KW = ['aprobado','approved','autorizado','completado','completed','cerrado','finalizado','done'];

function detectStatusField() {
    const cands = ['status','estado','estatus','state','condition','situacion'];
    for (const k of cands) if (allBoletas.length>0 && k in allBoletas[0]) return k;
    for (const b of allBoletas) {
        for (const [k,v] of Object.entries(b)) {
            if (typeof v!=='string') continue;
            const vl = v.toLowerCase();
            if (PEND_KW.some(x=>vl.includes(x))||APPR_KW.some(x=>vl.includes(x))) return k;
        }
    }
    return null;
}

function getStatus(b) {
    const sf = detectStatusField();
    if (!sf) return null;
    const v = String(b[sf]??'').toLowerCase();
    if (PEND_KW.some(k=>v.includes(k))) return 'pending';
    if (APPR_KW.some(k=>v.includes(k))) return 'approved';
    return null;
}

/* ── CARGAR DATOS ────────────────────────────────────────────── */
async function loadFromAPI() {
    try {
        loadingBar.classList.add('active');
        loadingText.textContent = '⏳ Conectando con la API...';

        const r = await fetch('api-proxy.php?all_pages=true');
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        loadingText.textContent = '📊 Procesando...';
        const res = await r.json();
        if (res.error) throw new Error(res.error);
        const raw = res.data || res.boletas || [];
        if (!raw.length) throw new Error('Sin boletas en la respuesta');

        console.log('📋 Primera boleta:', JSON.stringify(raw[0], null, 2));

        allBoletas = raw.map((b,i) => ({
            ...b,
            _idx: i, _name: getName(b,i),
            _imgs: extractImages(b),
            _coords: extractCoords(b),
        }));

        buildFilters();
        applyAll();
        if (heatLayer) buildHeatLayer();

        // Si se navegó desde la landing con un pin específico, volar a esa coordenada
        const gotoRaw = localStorage.getItem('geopanel_goto');
        if (gotoRaw) {
            try {
                const g = JSON.parse(gotoRaw);
                localStorage.removeItem('geopanel_goto');
                setTimeout(() => {
                    map.setView([g.lat, g.lon], 17);
                    const hit = boletaMarkers.find(m =>
                        m.boleta._coords &&
                        Math.abs(m.boleta._coords.lat - g.lat) < 0.001 &&
                        Math.abs(m.boleta._coords.lon - g.lon) < 0.001
                    );
                    if (hit) hit.marker.openPopup();
                }, 400);
            } catch(e) { localStorage.removeItem('geopanel_goto'); }
        }
        loadingText.textContent = `✅ ${allBoletas.length} boletas cargadas`;
        setTimeout(() => loadingBar.classList.remove('active'), 2200);
    } catch(e) {
        console.error(e);
        loadingText.textContent = `❌ ${e.message}`;
        setTimeout(() => loadingBar.classList.remove('active'), 4000);
    } finally { /* nothing */ }
}

/* ── FILTROS DINÁMICOS ───────────────────────────────────────── */
const SKIP = new Set([
    'nombre','name','vecino','solicitante','nombre_completo','propietario',
    'direccion','address','domicilio',
    'descripcion','description','descripcion_problema','descripcion_solicitud',
    'detalle','detalles','detail','details',
    'observacion','observaciones','observation','observations',
    'nota','notas','note','notes','comentario','comentarios','comment','comments',
    'justificacion','motivo','razon','reason',
    '_idx','_name','_imgs','_image_urls','_coords',
    'lat','lon','lng','latitude','longitude','latitud','longitud',
    'coordenada_lat','coordenada_lng','coord_lat','coord_lng','x','y','location','coordinates',
]);

function buildFilters() {
    const cands = {};
    allBoletas.forEach(b => {
        Object.entries(b).forEach(([k,v]) => {
            if (SKIP.has(k.toLowerCase())) return;
            if (isImg(String(v??''))) return;
            if (typeof v==='object'||v===null||v===undefined||v==='') return;
            if (!cands[k]) cands[k] = {};
            const sv = String(v);
            cands[k][sv] = (cands[k][sv] || 0) + 1;
        });
    });
    const fields = Object.entries(cands)
        .filter(([,counts]) => { const s = Object.keys(counts).length; return s >= 2 && s <= 30; })
        .sort((a,b) => a[0].localeCompare(b[0]));

    if (!fields.length) {
        dynFilters.innerHTML = '<div class="filter-placeholder">No se encontraron campos filtrables</div>';
        return;
    }

    dynFilters.innerHTML = fields.map(([field, counts]) => {
        const vals = Object.entries(counts).sort((a,b) => b[1] - a[1]);
        const showSearch = vals.length > 7;
        const items = vals.map(([v, cnt]) => `
            <label class="fec-item">
                <input type="checkbox" class="fec-check" data-field="${esc(field)}" value="${esc(v)}">
                <span class="fec-label" title="${esc(v)}">${v.length > 26 ? v.slice(0,24)+'…' : v}</span>
                <span class="fec-count">${cnt}</span>
            </label>`).join('');
        return `<div class="fec-section">
            <div class="fec-head">
                <span class="fec-title">${fmtKey(field)}</span>
                <span class="fec-badge" id="fecb-${esc(field)}"></span>
                <span class="fec-arrow">▾</span>
            </div>
            <div class="fec-body-wrap">
                ${showSearch ? `<input type="text" class="fec-search-inp" placeholder="Buscar…" data-grp="${esc(field)}">` : ''}
                <div class="fec-body">${items}</div>
            </div>
        </div>`;
    }).join('');

    // Collapsible section toggle
    dynFilters.querySelectorAll('.fec-head').forEach(head => {
        head.addEventListener('click', e => {
            if (e.target.closest('input, label')) return;
            head.closest('.fec-section').classList.toggle('open');
        });
    });

    // Immediate apply on check/uncheck
    dynFilters.querySelectorAll('.fec-check').forEach(cb => {
        cb.addEventListener('change', () => {
            activeFilters = {};
            dynFilters.querySelectorAll('.fec-check:checked').forEach(c => {
                if (!activeFilters[c.dataset.field]) activeFilters[c.dataset.field] = new Set();
                activeFilters[c.dataset.field].add(c.value);
            });
            const n = dynFilters.querySelectorAll(`.fec-check[data-field="${cb.dataset.field}"]:checked`).length;
            const badge = document.getElementById(`fecb-${cb.dataset.field}`);
            if (badge) { badge.textContent = n || ''; badge.classList.toggle('vis', n > 0); }
            applyAll();
        });
    });

    // Search within group
    dynFilters.querySelectorAll('.fec-search-inp').forEach(inp => {
        inp.addEventListener('input', () => {
            const q = inp.value.toLowerCase();
            dynFilters.querySelectorAll(`.fec-check[data-field="${inp.dataset.grp}"]`).forEach(cb => {
                cb.closest('.fec-item').style.display = q && !cb.value.toLowerCase().includes(q) ? 'none' : '';
            });
        });
    });
}

/* ── RESTABLECER / RESET ─────────────────────────────────────── */
document.getElementById('resetFilters').addEventListener('click', () => {
    activeFilters = {};
    dynFilters.querySelectorAll('.fec-check:checked').forEach(cb => { cb.checked = false; });
    dynFilters.querySelectorAll('.fec-badge').forEach(b => { b.textContent=''; b.classList.remove('vis'); });
    dynFilters.querySelectorAll('.fec-search-inp').forEach(i => { i.value=''; });
    dynFilters.querySelectorAll('.fec-item').forEach(i => { i.style.display=''; });
    applyAll();
});

function fullReset() {
    activeFilters = {};
    listSearchQ   = '';
    dynFilters.querySelectorAll('.fec-check:checked').forEach(cb => { cb.checked = false; });
    dynFilters.querySelectorAll('.fec-badge').forEach(b => { b.textContent=''; b.classList.remove('vis'); });
    dynFilters.querySelectorAll('.fec-search-inp').forEach(i => { i.value=''; });
    dynFilters.querySelectorAll('.fec-item').forEach(i => { i.style.display=''; });
    const li = document.getElementById('listSearch');
    if (li) { li.value=''; listSearchQ=''; }
    applyAll();
}

/* ── APPLY ALL FILTERS ───────────────────────────────────────── */
function applyAll() {
    filteredBoletas = allBoletas.filter(b => {
        for (const [field, vals] of Object.entries(activeFilters)) {
            if (!vals.has(String(b[field]??''))) return false;
        }
        return true;
    });

    renderMarkers();
    renderList();
}

/* ── MARCADORES ──────────────────────────────────────────────── */
function renderMarkers() {
    markerGroup.clearLayers();
    boletaMarkers = [];
    const bounds = [];

    filteredBoletas.filter(b => b._coords).forEach(b => {
        const {lat, lon} = b._coords;
        const emoji = getEmoji(b);

        const icon = L.divIcon({
            className: '',
            html: `<div class="emoji-pin">${emoji}</div>`,
            iconSize: [32, 32], iconAnchor: [16, 16], popupAnchor: [0, -18]
        });

        const marker = L.marker([lat, lon], { icon })
            .bindPopup(buildPopup(b), { maxWidth: 520, className: 'custom-popup' })
            .addTo(markerGroup);
        marker.on('click', () => highlightCard(b._idx));
        boletaMarkers.push({ boleta: b, marker });
        bounds.push([lat, lon]);
    });

    if (bounds.length > 0) map.fitBounds(bounds, { padding: [60, 60], maxZoom: 13 });
    mapInfobar.textContent = `${boletaMarkers.length} boleta${boletaMarkers.length!==1?'s':''} en el mapa · ${allBoletas.length} totales`;
}

function getFixedFields(b) {
    const coords = b._coords;
    const defs = [
        { label: 'Nombre',        val: b.citizen?.name || b.nombre || b.name || b.nombre_completo || b.solicitante },
        { label: 'DPI',           val: b.citizen?.dpi  || b.dpi   || b.cedula || b.identificacion },
        { label: 'No. de Boleta', val: b.boleta_number || b.no_boleta || b.numero_boleta },
        { label: 'Tipo de Boleta',val: b.boleta_type   || b.tipo_boleta || b.tipo },
        { label: 'Fecha',         val: b.date_formatted|| b.fecha || b.date || b.created_at },
        { label: 'Aldea',         val: b.aldea         || b.village || b.comunidad },
        { label: 'Estado',        val: b.status        || b.estado || b.estatus },
        { label: 'Beneficio',     val: b.benefit       || b.beneficio || b.tipo_beneficio },
        { label: 'Creador',       val: b.creator       || b.creador || b.created_by || b.usuario },
        { label: 'Coordenadas',   val: coords ? `${coords.lat.toFixed(6)}, ${coords.lon.toFixed(6)}` : null },
    ];
    return defs;
}

function buildPopup(b) {
    const status = getStatus(b);
    const badge  = status==='approved' ? '<span class="popup-badge approved">✅ Aprobado</span>'
                 : status==='pending'  ? '<span class="popup-badge pending">⏳ Pendiente</span>'
                 :                      `<span class="popup-badge">${getEmoji(b)} ${b.tipo||b.tipo_boleta||b.tipo_beneficio||'Boleta'}</span>`;

    const gallery = b._imgs.length ? `<div class="popup-gallery">${
        b._imgs.slice(0,6).map((u,i)=>`<div class="popup-img-wrap" onclick="openImgs(${b._idx},${i})">
            <img src="${u}" alt="" onerror="this.parentElement.style.display='none'">
        </div>`).join('')
    }</div>` : '';

    const fields = getFixedFields(b)
        .filter(f => f.val !== null && f.val !== undefined && f.val !== '')
        .map(f => `<div class="popup-field"><span class="popup-field-label">${f.label}</span><span class="popup-field-value">${f.val}</span></div>`)
        .join('');

    return `<div class="popup-inner">
        <div class="popup-title">${b._name} ${badge}</div>
        ${gallery}
        <div class="popup-fields">${fields||'<div style="color:#aaa;font-size:12px;text-align:center;padding:8px">Sin datos adicionales</div>'}</div>
        <div class="popup-actions">
            <button class="popup-btn popup-btn-primary" onclick="openDetail(${b._idx})">📋 Ver Encuesta</button>
            ${b._coords ? `
            <a class="popup-btn popup-btn-maps popup-btn-icon" href="https://www.google.com/maps?q=${b._coords.lat},${b._coords.lon}" target="_blank" rel="noopener" title="Abrir en Google Maps"><img src="https://t1.gstatic.com/faviconV2?client=SOCIAL&type=FAVICON&fallback_opts=TYPE,SIZE,URL&url=https://maps.google.com&size=128" width="20" height="20"></a>
            <a class="popup-btn popup-btn-waze popup-btn-icon" href="https://waze.com/ul?ll=${b._coords.lat},${b._coords.lon}&navigate=yes" target="_blank" rel="noopener" title="Navegar con Waze"><img src="https://cdn.simpleicons.org/waze/ffffff" width="20" height="20"></a>
            ` : ''}
        </div>
    </div>`;
}

/* ── LISTADO ─────────────────────────────────────────────────── */
function renderList() {
    // Apply internal list search on top of filteredBoletas
    const lq = listSearchQ.toLowerCase();
    let show = lq
        ? filteredBoletas.filter(b => searchText(b).includes(lq))
        : filteredBoletas;

    // Tab filter
    if (activeListTab === 'nocoord')  show = show.filter(b => !b._coords);
    if (activeListTab === 'pending')  show = show.filter(b => getStatus(b) === 'pending');

    // Update tab counts
    const all     = filteredBoletas.length;
    const nocoord = filteredBoletas.filter(b=>!b._coords).length;
    const pending = filteredBoletas.filter(b=>getStatus(b)==='pending').length;
    document.getElementById('listCount').textContent  = show.length;
    document.getElementById('tabAll').textContent     = all;
    document.getElementById('tabNocoord').textContent = nocoord;
    document.getElementById('tabPending').textContent = pending;

    if (!show.length) {
        boletaList.innerHTML = `<div class="list-empty"><div class="empty-ico">🔍</div>
            <p>${!allBoletas.length ? 'Carga las boletas para ver el listado' : 'Ninguna boleta coincide'}</p></div>`;
        return;
    }

    boletaList.innerHTML = show.slice(0,300).map((b,i) => {
        const noCoord = !b._coords;
        const outside = b._coords && !isInGT(b._coords.lat, b._coords.lon);
        const status  = getStatus(b);

        let cardCls = '';
        if (noCoord) cardCls = 'no-coord';
        else if (outside) cardCls = 'outside';
        else if (status==='pending') cardCls = 'pending';
        else if (status==='approved') cardCls = 'approved';

        let tag;
        if (noCoord)               tag = '<span class="bc-tag red">Sin coords</span>';
        else if (outside)          tag = '<span class="bc-tag orange">Fuera GT</span>';
        else if (status==='pending')  tag = '<span class="bc-tag purple">⏳ Pendiente</span>';
        else if (status==='approved') tag = '<span class="bc-tag teal">✅ Aprobado</span>';
        else                       tag = '<span class="bc-tag">📍 En mapa</span>';

        const info = Object.entries(b)
            .filter(([k,v])=>!['_idx','_name','_imgs','_image_urls','_coords'].includes(k)&&!k.startsWith('_')&&typeof v==='string'&&!isImg(v)&&v)
            .slice(0,2).map(([k,v])=>`${fmtKey(k)}: ${v.slice(0,28)}`).join(' · ');

        return `<div class="boleta-card ${cardCls}" data-idx="${b._idx}"
                     style="animation-delay:${Math.min(i*.02,.3)}s"
                     onclick="selectBoleta(${b._idx})">
            <div class="bc-title">${b._name}</div>
            ${info?`<div class="bc-info">${info}</div>`:''}
            ${tag}
        </div>`;
    }).join('');
}

// Tabs
document.querySelectorAll('.rp-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.rp-tab').forEach(t=>t.classList.remove('active'));
        tab.classList.add('active');
        activeListTab = tab.dataset.tab;
        renderList();
    });
});

// List search
const listSearchEl  = document.getElementById('listSearch');
const listClearBtn  = document.getElementById('listSearchClear');
let listSearchTimer = null;

listSearchEl.addEventListener('input', () => {
    listSearchQ = listSearchEl.value.trim();
    listClearBtn.classList.toggle('visible', listSearchQ.length > 0);
    clearTimeout(listSearchTimer);
    listSearchTimer = setTimeout(renderList, 180);
});
listClearBtn.addEventListener('click', () => {
    listSearchEl.value = '';
    listSearchQ = '';
    listClearBtn.classList.remove('visible');
    renderList();
});

/* ── SELECCIONAR BOLETA ──────────────────────────────────────── */
window.selectBoleta = function(idx) {
    const b = allBoletas.find(x=>x._idx===idx);
    if (!b) return;
    const pair = boletaMarkers.find(p=>p.boleta._idx===idx);
    if (pair) {
        map.setView([b._coords.lat, b._coords.lon], 16, {animate:true});
        setTimeout(() => pair.marker.openPopup(), 400);
    } else {
        openDetail(idx);
    }
    highlightCard(idx);
};

function highlightCard(idx) {
    document.querySelectorAll('.boleta-card').forEach(c => {
        c.classList.toggle('active', parseInt(c.dataset.idx)===idx);
    });
    const el = boletaList.querySelector(`[data-idx="${idx}"]`);
    if (el) el.scrollIntoView({block:'nearest', behavior:'smooth'});
}

/* ── DETALLE / ENCUESTA ──────────────────────────────────────── */
window.openDetail = function(idx) {
    const b = allBoletas.find(x=>x._idx===idx);
    if (!b) return;
    const status  = getStatus(b);
    const coords  = b._coords;
    const outside = coords && !isInGT(coords.lat, coords.lon);

    const fixedFields = getFixedFields(b);

    const statusBadge = status==='approved' ? '✅ Aprobado'
                      : status==='pending'  ? '⏳ Pendiente'
                      : !coords ? '❌ Sin coordenadas'
                      : outside ? '⚠️ Fuera GT' : '📍 En Guatemala';

    bottomTitle.innerHTML = `📋 ${b._name} <span style="font-size:11px;opacity:.75;margin-left:6px">${statusBadge}</span>`;

    const imgsHtml = b._imgs.length ? `
        <div style="margin-bottom:18px">
            <div class="survey-images-title">📷 Fotografías (${b._imgs.length})</div>
            <div class="survey-gallery">
                ${b._imgs.map((u,i)=>`<div class="survey-img-wrap" onclick="openImgs(${idx},${i})">
                    <img src="${u}" alt="" onerror="this.parentElement.style.display='none'">
                </div>`).join('')}
            </div>
        </div>` : '';

    const fieldsHtml = `<div class="survey-grid">
        ${fixedFields.map(f=>{
            const empty = f.val===null||f.val===undefined||f.val==='';
            return `<div class="survey-card">
                <div class="survey-card-label">${f.label}</div>
                <div class="survey-card-value ${empty?'null-val':''}">${empty?'(sin datos)':f.val}</div>
            </div>`;
        }).join('')}
        ${coords ? `<div class="survey-card"><div class="survey-card-label">Ubicación</div>
            <div class="survey-card-value ${outside?'null-val':''}">${outside?'⚠ Fuera de Guatemala':'✅ Dentro de Guatemala'}</div></div>` : ''}
    </div>`;

    const nestedHtml = '';

    const mapHtml = coords ? `<div style="margin-top:16px">
        <div class="survey-images-title">📍 Ubicación</div>
        <div id="detailMiniMap" style="height:180px;border-radius:9px;overflow:hidden;border:1.5px solid var(--border)"></div>
        <div class="detail-nav-btns">
            <a class="detail-nav-btn detail-nav-maps" href="https://www.google.com/maps?q=${coords.lat},${coords.lon}" target="_blank" rel="noopener"><img src="https://t1.gstatic.com/faviconV2?client=SOCIAL&type=FAVICON&fallback_opts=TYPE,SIZE,URL&url=https://maps.google.com&size=128" width="20" height="20" style="vertical-align:middle;margin-right:6px"> Abrir en Google Maps</a>
            <a class="detail-nav-btn detail-nav-waze" href="https://waze.com/ul?ll=${coords.lat},${coords.lon}&navigate=yes" target="_blank" rel="noopener"><img src="https://cdn.simpleicons.org/waze/ffffff" width="20" height="20" style="vertical-align:middle;margin-right:6px"> Navegar con Waze</a>
        </div>
    </div>` : '';

    bottomContent.innerHTML = imgsHtml + fieldsHtml + nestedHtml + mapHtml;
    bottomPanel.classList.add('open');
    bottomOverlay.classList.add('active');

    if (coords) {
        setTimeout(() => {
            if (miniMap) { miniMap.remove(); miniMap=null; }
            const el = document.getElementById('detailMiniMap');
            if (!el) return;
            miniMap = L.map(el,{zoomControl:false,attributionControl:false}).setView([coords.lat,coords.lon],15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(miniMap);
            L.marker([coords.lat,coords.lon]).addTo(miniMap);
        }, 150);
    }
};

document.getElementById('bottomPanelClose').addEventListener('click', closeDetail);
bottomOverlay.addEventListener('click', closeDetail);
function closeDetail() {
    bottomPanel.classList.remove('open');
    bottomOverlay.classList.remove('active');
    if (miniMap) { miniMap.remove(); miniMap=null; }
}

/* ── IMAGE MODAL ─────────────────────────────────────────────── */
window.openImgs = function(bidx, start=0) {
    const b = allBoletas.find(x=>x._idx===bidx);
    if (!b||!b._imgs.length) return;
    modalImages   = b._imgs;
    modalImageIdx = start;
    showImg();
    imageModal.classList.add('active');
};
function showImg() {
    modalImage.src = modalImages[modalImageIdx];
    document.getElementById('imgCounter').textContent = `${modalImageIdx+1} / ${modalImages.length}`;
    document.getElementById('imgPrev').disabled = modalImageIdx===0;
    document.getElementById('imgNext').disabled = modalImageIdx===modalImages.length-1;
}
document.getElementById('imgPrev').addEventListener('click', ()=>{ if(modalImageIdx>0){modalImageIdx--;showImg();} });
document.getElementById('imgNext').addEventListener('click', ()=>{ if(modalImageIdx<modalImages.length-1){modalImageIdx++;showImg();} });
document.getElementById('modalClose').addEventListener('click', ()=>imageModal.classList.remove('active'));
imageModal.addEventListener('click', e=>{ if(e.target===imageModal) imageModal.classList.remove('active'); });
document.addEventListener('keydown', e=>{
    if (!imageModal.classList.contains('active')) return;
    if (e.key==='ArrowLeft'  && modalImageIdx>0) { modalImageIdx--; showImg(); }
    if (e.key==='ArrowRight' && modalImageIdx<modalImages.length-1) { modalImageIdx++; showImg(); }
    if (e.key==='Escape') imageModal.classList.remove('active');
});
window.showImageModal = url => { modalImages=[url]; modalImageIdx=0; showImg(); imageModal.classList.add('active'); };



/* ── MAP CONTROLS ────────────────────────────────────────────── */
document.getElementById('fitBoundsBtn').addEventListener('click', () => {
    const pts = boletaMarkers.map(p=>[p.boleta._coords.lat, p.boleta._coords.lon]);
    if (pts.length) map.fitBounds(pts,{padding:[60,60]});
    else map.setView([14.85,-89.87],11);
});

/* ── COPY COORDS ─────────────────────────────────────────────── */
window.copyCoords = (lat, lon) => {
    const txt = `${lat}, ${lon}`;
    navigator.clipboard.writeText(txt).then(()=>{
        mapInfobar.textContent = `✅ Copiado: ${txt}`;
        setTimeout(()=> mapInfobar.textContent=`${boletaMarkers.length} boletas en el mapa`, 2500);
    }).catch(()=>prompt('Coordenadas:',txt));
};


/* ── EXPORT PDF ──────────────────────────────────────────────── */
exportBtn.addEventListener('click', async () => {
    try {
        exportBtn.disabled = true;
        exportBtn.textContent = '⏳ Generando…';
        const {jsPDF} = window.jspdf;
        const pdf = new jsPDF({orientation:'portrait', unit:'mm', format:'a4'});
        const PW=210, PH=297, ML=14, CW=182;
        let y=0;
        const VD=[52,92,50], BL=[255,255,255], GR=[68,68,68], GS=[244,249,244];

        // Header
        pdf.setFillColor(...VD); pdf.rect(0,0,PW,22,'F');
        pdf.setTextColor(...BL); pdf.setFont('helvetica','bold'); pdf.setFontSize(14);
        pdf.text('GeoPanel — Reporte de Boletas', ML, 10);
        pdf.setFont('helvetica','normal'); pdf.setFontSize(8);
        pdf.text(`Generado: ${new Date().toLocaleString('es-GT')} | Total: ${allBoletas.length}`, ML, 17);
        y=28;

        // Map screenshot
        const canvas = await html2canvas(document.getElementById('map'),{useCORS:true,allowTaint:true,scale:1.5,logging:false});
        const mImg = canvas.toDataURL('image/jpeg',.85);
        const mH = Math.min(CW*(canvas.height/canvas.width),100);
        pdf.setDrawColor(156,172,84); pdf.setLineWidth(.4);
        pdf.rect(ML-1,y-1,CW+2,mH+2); pdf.addImage(mImg,'JPEG',ML,y,CW,mH);
        y+=mH+6;

        // Stat cards
        const cards=[
            {l:'Total',v:allBoletas.length,c:VD},
            {l:'En Mapa',v:filteredBoletas.filter(b=>b._coords).length,c:[86,171,47]},
            {l:'Sin Coord',v:allBoletas.filter(b=>!b._coords).length,c:[229,62,62]},
            {l:'Fuera GT',v:allBoletas.filter(b=>b._coords&&!isInGT(b._coords.lat,b._coords.lon)).length,c:[221,107,32]},
        ];
        const cw=(CW-6)/4;
        cards.forEach((c,i)=>{
            const cx=ML+i*(cw+2);
            pdf.setFillColor(...c.c); pdf.roundedRect(cx,y,cw,18,2,2,'F');
            pdf.setTextColor(...BL); pdf.setFont('helvetica','normal'); pdf.setFontSize(7);
            pdf.text(c.l,cx+cw/2,y+6,{align:'center'});
            pdf.setFont('helvetica','bold'); pdf.setFontSize(14);
            pdf.text(String(c.v),cx+cw/2,y+14,{align:'center'});
        }); y+=24;

        // Markers table
        if (boletaMarkers.length) {
            pdf.addPage(); y=0;
            pdf.setFillColor(...VD); pdf.rect(0,0,PW,16,'F');
            pdf.setTextColor(...BL); pdf.setFont('helvetica','bold'); pdf.setFontSize(11);
            pdf.text(`Boletas en el Mapa (${boletaMarkers.length})`, ML, 10); y=22;
            const cols=[
                {h:'Nombre',         w:46, fn:b=>b._name||'—'},
                {h:'DPI',            w:26, fn:b=>String(b.citizen?.dpi||b.dpi||b.cedula||b.identificacion||'—').slice(0,18)},
                {h:'No. Boleta',     w:22, fn:b=>String(b.boleta_number||b.no_boleta||b.numero_boleta||b.id||'—').slice(0,14)},
                {h:'Fecha',          w:22, fn:b=>String(b.fecha||b.date||b.created_at||b.fecha_solicitud||'—').slice(0,14)},
                {h:'Tipo',           w:30, fn:b=>String(b.boleta_type||b.tipo||b.benefit||b.tipo_boleta||'—').slice(0,20)},
                {h:'Coordenadas',    w:32, fn:b=>b._coords?`${b._coords.lat.toFixed(4)}, ${b._coords.lon.toFixed(4)}`:'Sin coord.'},
            ];
            pdf.setFillColor(...VD); pdf.rect(ML,y,CW,8,'F');
            pdf.setTextColor(...BL); pdf.setFont('helvetica','bold'); pdf.setFontSize(7.5);
            let cx=ML+2; cols.forEach(c=>{pdf.text(c.h,cx,y+5.5);cx+=c.w;}); y+=8;
            pdf.setFont('helvetica','normal'); pdf.setFontSize(7);
            boletaMarkers.forEach(({boleta:b},i)=>{
                if(y>PH-20){pdf.addPage();y=14;}
                pdf.setFillColor(...(i%2===0?[255,255,255]:GS)); pdf.rect(ML,y,CW,7,'F');
                pdf.setTextColor(...GR);
                let rx=ML+2;
                cols.forEach(c=>{pdf.text(c.fn(b),rx,y+5);rx+=c.w;});
                pdf.setDrawColor(220,235,220); pdf.setLineWidth(.1); pdf.line(ML,y+7,ML+CW,y+7); y+=7;
            });
        }

        // Footer
        const tp=pdf.getNumberOfPages();
        for(let p=1;p<=tp;p++){
            pdf.setPage(p); pdf.setFillColor(...VD); pdf.rect(0,PH-10,PW,10,'F');
            pdf.setTextColor(...BL); pdf.setFont('helvetica','normal'); pdf.setFontSize(7);
            pdf.text('GeoPanel — Municipalidad Sanarate, El Progreso',ML,PH-4);
            pdf.text(`Pág. ${p} de ${tp}`,PW-ML,PH-4,{align:'right'});
        }
        pdf.save(`reporte_boletas_${new Date().toISOString().slice(0,10)}.pdf`);
    } catch(e) {
        console.error(e); alert('Error PDF: '+e.message);
    } finally {
        exportBtn.disabled=false; exportBtn.innerHTML='<span class="btn-icon">📄</span> PDF';
    }
});

/* ── PANEL DRAWER ────────────────────────────────────────────── */
(function() {
    const lp       = document.getElementById('leftPanel');
    const lpToggle = document.getElementById('lpToggle');
    const backdrop = document.getElementById('panelBackdrop');

    function openPanel() {
        lp.classList.remove('collapsed');
        backdrop.classList.add('active');
        lpToggle.textContent = '‹';
    }
    function closePanel() {
        lp.classList.add('collapsed');
        backdrop.classList.remove('active');
        lpToggle.textContent = '›';
    }

    lpToggle.addEventListener('click', () =>
        lp.classList.contains('collapsed') ? openPanel() : closePanel()
    );
    const lpToggle2   = document.getElementById('lpToggle2');
    const navPanelBtn = document.getElementById('navPanelToggle');
    if (lpToggle2)   lpToggle2.addEventListener('click', closePanel);
    if (navPanelBtn) navPanelBtn.addEventListener('click', () =>
        lp.classList.contains('collapsed') ? openPanel() : closePanel()
    );
    backdrop.addEventListener('click', closePanel);
})();

/* ── INICIO AUTOMÁTICO ───────────────────────────────────────── */
loadFromAPI();
