<template>
    <div class="space-y-2">
        <div
            v-if="!readonly"
            class="flex flex-wrap items-center justify-between gap-2 text-[11px]"
        >
            <span class="text-white/60">Toca cada esquina de la parcela para dibujar su contorno.</span>
            <div class="flex gap-2">
                <button
                    type="button"
                    @click="undo"
                    :disabled="!points.length"
                    class="px-2.5 py-1 rounded-lg bg-white/10 hover:bg-white/20 text-white font-semibold disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                >
                    Deshacer punto
                </button>
                <button
                    type="button"
                    @click="clear"
                    :disabled="!points.length"
                    class="px-2.5 py-1 rounded-lg bg-white/10 hover:bg-[#ef4444]/30 text-white font-semibold disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                >
                    Limpiar
                </button>
            </div>
        </div>

        <div ref="mapRef" class="mp-map w-full rounded-xl overflow-hidden border border-white/15" :style="{ height }"></div>

        <p v-if="!readonly" class="text-[11px] text-white/60">
            <template v-if="points.length >= 3">
                {{ points.length }} puntos · área ≈ {{ areaHa.toFixed(2) }} ha
            </template>
            <template v-else-if="points.length">
                {{ points.length }} punto{{ points.length === 1 ? '' : 's' }} · agrega al menos 3 para cerrar el contorno
            </template>
            <template v-else>Sin contorno dibujado.</template>
        </p>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    readonly: { type: Boolean, default: false },
    focus: { type: Array, default: null },
    height: { type: String, default: '18rem' },
});
const emit = defineEmits(['update:modelValue', 'change']);

const GUATEMALA_CENTER = [15.5, -90.25];

const mapRef = ref(null);
const points = ref(normalize(props.modelValue));
const areaHa = ref(0);

let map = null;
let polygon = null;
let markers = [];

function normalize(arr) {
    return (Array.isArray(arr) ? arr : [])
        .filter((p) => Array.isArray(p) && p.length === 2 && Number.isFinite(+p[0]) && Number.isFinite(+p[1]))
        .map((p) => [+p[0], +p[1]]);
}

// Área geodésica (fórmula estándar de Leaflet.draw), en m².
function areaM2(latlngs) {
    if (latlngs.length < 3) return 0;
    const R = 6378137;
    const rad = (d) => (d * Math.PI) / 180;
    let s = 0;
    for (let i = 0; i < latlngs.length; i++) {
        const [la1, lo1] = latlngs[i];
        const [la2, lo2] = latlngs[(i + 1) % latlngs.length];
        s += rad(lo2 - lo1) * (2 + Math.sin(rad(la1)) + Math.sin(rad(la2)));
    }
    return Math.abs((s * R * R) / 2);
}

function centroid(latlngs) {
    if (!latlngs.length) return null;
    const sum = latlngs.reduce((acc, [la, lo]) => [acc[0] + la, acc[1] + lo], [0, 0]);
    return [sum[0] / latlngs.length, sum[1] / latlngs.length];
}

const vertexIcon = L.divIcon({
    className: '',
    html: '<span class="mp-vertex"></span>',
    iconSize: [14, 14],
    iconAnchor: [7, 7],
});

function drawPolygon() {
    if (polygon) {
        map.removeLayer(polygon);
        polygon = null;
    }
    if (points.value.length >= 2) {
        polygon = L.polygon(points.value, {
            color: '#4ade80',
            weight: 2,
            fillColor: '#4ade80',
            fillOpacity: 0.2,
        }).addTo(map);
    }
}

function drawMarkers() {
    markers.forEach((m) => map.removeLayer(m));
    markers = [];
    if (props.readonly) return;
    points.value.forEach((pt, i) => {
        const marker = L.marker(pt, { draggable: true, icon: vertexIcon }).addTo(map);
        marker.on('dragend', () => {
            const ll = marker.getLatLng();
            points.value[i] = [ll.lat, ll.lng];
            drawPolygon();
            commit();
        });
        markers.push(marker);
    });
}

function redraw() {
    drawPolygon();
    drawMarkers();
}

function commit() {
    const value = points.value.map((p) => [p[0], p[1]]);
    const m2 = areaM2(value);
    areaHa.value = m2 / 10000;
    emit('update:modelValue', value);
    if (value.length >= 3) {
        emit('change', { poligono: value, areaHa: areaHa.value, centroid: centroid(value) });
    }
}

function addPoint(latlng) {
    points.value.push([latlng.lat, latlng.lng]);
    redraw();
    commit();
}

function undo() {
    points.value.pop();
    redraw();
    commit();
}

function clear() {
    points.value = [];
    redraw();
    commit();
}

function fitToPoints() {
    if (points.value.length) {
        map.fitBounds(L.latLngBounds(points.value).pad(0.3), { maxZoom: 17 });
    } else if (props.focus && Number.isFinite(+props.focus[0])) {
        map.setView([+props.focus[0], +props.focus[1]], 16);
    } else {
        map.setView(GUATEMALA_CENTER, 6);
    }
}

onMounted(async () => {
    await nextTick();
    map = L.map(mapRef.value, { scrollWheelZoom: !props.readonly, zoomControl: !props.readonly });
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics',
        maxZoom: 19,
    }).addTo(map);

    if (!props.readonly) {
        map.on('click', (e) => addPoint(e.latlng));
    }

    fitToPoints();
    redraw();
    areaHa.value = areaM2(points.value) / 10000;

    // El contenedor puede no tener su tamaño final (modales con blur/transición).
    setTimeout(() => map && map.invalidateSize(), 200);
    setTimeout(() => map && map.invalidateSize(), 600);
});

onUnmounted(() => {
    if (map) {
        map.remove();
        map = null;
    }
});

// Cambios externos del contorno (carga en edición, detalle).
watch(() => props.modelValue, (val) => {
    const next = normalize(val);
    if (JSON.stringify(next) === JSON.stringify(points.value)) return;
    points.value = next;
    if (map) {
        redraw();
        fitToPoints();
        areaHa.value = areaM2(points.value) / 10000;
    }
});

watch(() => props.focus, () => {
    if (map && !points.value.length && props.focus && Number.isFinite(+props.focus[0])) {
        map.setView([+props.focus[0], +props.focus[1]], 16);
    }
});
</script>

<style>
.mp-vertex {
    display: block;
    width: 14px;
    height: 14px;
    border-radius: 9999px;
    background: #4ade80;
    border: 2px solid #0c1e17;
    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.6);
    cursor: grab;
}
.mp-vertex:active {
    cursor: grabbing;
}
</style>
