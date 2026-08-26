<template>
    <div class="bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-md rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden h-[600px] animate-fade-in flex flex-col relative">
        <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center z-10 bg-white/50 dark:bg-slate-900/50">
            <div class="flex items-center gap-3">
                <router-link to="/admin/clima/dashboard" class="p-2 bg-gray-50 dark:bg-gray-800 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" title="Volver al Dashboard">
                    <ArrowLeftIcon class="w-5 h-5" />
                </router-link>
                <div>
                    <h3 class="text-xl font-bold text-brand-dark dark:text-white">Mapa de Registros Climatológicos</h3>
                    <p class="text-xs text-gray-500">Visualización geoespacial de eventos y condiciones</p>
                </div>
            </div>
            <div class="flex gap-2">
                <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full shadow-sm">🟢 Normales</span>
                <span class="px-3 py-1 bg-amber-100 text-amber-700 text-xs font-bold rounded-full shadow-sm">🟡 Alertas</span>
                <span class="px-3 py-1 bg-red-100 text-red-700 text-xs font-bold rounded-full shadow-sm">🔴 Desastres</span>
            </div>
        </div>
        
        <div class="flex-1 relative bg-gray-100 dark:bg-gray-800 z-0">
            <div id="mapContainer" class="absolute inset-0 w-full h-full z-0"></div>
            
            <!-- Panel de Filtros Flotante -->
            <div class="absolute top-6 left-6 z-20 bg-white/95 dark:bg-slate-800/95 backdrop-blur-md p-5 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 w-72 max-h-[80%] overflow-y-auto">
                <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
                    <FilterIcon class="w-4 h-4" /> Filtros de Mapa
                </h4>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Fecha Inicio</label>
                        <input type="date" v-model="filters.startDate" @change="applyFilters" class="w-full text-sm bg-gray-50 dark:bg-slate-900 border-none rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-main" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Fecha Fin</label>
                        <input type="date" v-model="filters.endDate" @change="applyFilters" class="w-full text-sm bg-gray-50 dark:bg-slate-900 border-none rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-main" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Categoría</label>
                        <select v-model="filters.category" @change="applyFilters" class="w-full text-sm bg-gray-50 dark:bg-slate-900 border-none rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-main">
                            <option value="">Todas las Categorías</option>
                            <option value="normal">Normal</option>
                            <option value="condicion">Alertas (Condiciones)</option>
                            <option value="desastre">Desastres</option>
                        </select>
                    </div>
                    <div class="pt-4 mt-2 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex justify-between items-center text-xs mb-2">
                            <span class="text-gray-500 font-medium">Registros Totales:</span>
                            <span class="font-bold bg-gray-100 dark:bg-slate-700 px-2 py-1 rounded">{{ registros.length }}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-gray-500 font-medium">Mostrados:</span>
                            <span class="font-bold bg-brand-main/10 text-brand-main px-2 py-1 rounded">{{ filteredRegistros.length }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Controles Personalizados -->
            <div class="absolute bottom-6 right-6 z-20 flex flex-col gap-2">
                <button @click="zoomIn" class="p-3 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-slate-700 transition" title="Acercar">
                    <PlusIcon class="w-5 h-5 text-gray-700 dark:text-gray-300" />
                </button>
                <button @click="zoomOut" class="p-3 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-slate-700 transition" title="Alejar">
                    <MinusIcon class="w-5 h-5 text-gray-700 dark:text-gray-300" />
                </button>
                <button @click="centerMap" class="p-3 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-slate-700 transition" title="Centrar Mapa">
                    <LocateFixedIcon class="w-5 h-5 text-brand-main" />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { ArrowLeftIcon, PlusIcon, MinusIcon, LocateFixedIcon, FilterIcon } from 'lucide-vue-next';
import api from '@/services/api';

import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet.markercluster';

const registros = ref([]);
const filteredRegistros = ref([]);
const filters = ref({
    startDate: '',
    endDate: '',
    category: ''
});

let map = null;
let markersLayer = null;

let guatemalaLayer = null;

const initMap = () => {
    map = L.map('mapContainer', {
        zoomControl: false // Desactivamos los controles por defecto para usar los nuestros
    }).setView([15.7835, -90.2308], 7);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
        minZoom: 3
    }).addTo(map);

    markersLayer = L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true
    });

    map.addLayer(markersLayer);
};

const formatearTexto = (texto) => {
    if (!texto) return '';
    return texto.split('_').map(palabra => palabra.charAt(0).toUpperCase() + palabra.slice(1)).join(' ');
};

const createPopupContent = (registro) => {
    const fecha = new Date(registro.fecha_registro);
    const fechaFormateada = fecha.toLocaleDateString('es-GT', {
        year: 'numeric', month: 'long', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });

    let tipoEvento = 'Condición Normal';
    if (registro.categoria === 'desastre') {
        tipoEvento = formatearTexto(registro.tipo || 'Desastre');
    } else if (registro.categoria && registro.categoria !== 'normal' && registro.categoria !== 'condicion') {
        tipoEvento = formatearTexto(registro.tipo || 'Alerta');
    }

    let climaObj = registro.clima || {};

    let html = `
        <div style="font-family: 'Inter', sans-serif; min-width: 250px;">
            <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 12px;">
                <p style="margin: 0; font-size: 14px; font-weight: 700; color: #1e293b;">📍 ${registro.direccion || 'Ubicación no especificada'}</p>
                <p style="margin: 4px 0 0; font-size: 11px; color: #64748b;">${fechaFormateada}</p>
            </div>
            
            <div style="font-size: 13px; color: #334155;">
                <div style="display: flex; margin-bottom: 6px;">
                    <span style="font-weight: 600; width: 100px;">👤 Usuario:</span>
                    <span>${registro.usuario || 'Desconocido'}</span>
                </div>
                <div style="display: flex; margin-bottom: 6px;">
                    <span style="font-weight: 600; width: 100px;">🏷️ Tipo:</span>
                    <span>${tipoEvento}</span>
                </div>
    `;

    if (climaObj.temperatura) {
        html += `<div style="display: flex; margin-bottom: 6px;"><span style="font-weight: 600; width: 100px;">🌡️ Temp:</span><span>${climaObj.temperatura}°C</span></div>`;
    }
    if (climaObj.humedad) {
        html += `<div style="display: flex; margin-bottom: 6px;"><span style="font-weight: 600; width: 100px;">💧 Humedad:</span><span>${climaObj.humedad}%</span></div>`;
    }

    if (registro.observaciones) {
        html += `
            <div style="margin-top: 12px; background: #f8fafc; padding: 8px; border-radius: 6px; font-size: 12px;">
                <strong>📝 Observaciones:</strong><br/>
                ${registro.observaciones}
            </div>
        `;
    }

    html += '</div></div>';
    return html;
};

const renderMarkers = () => {
    // Limpiar marcadores existentes
    if (markersLayer) {
        markersLayer.clearLayers();
    }

    if (!filteredRegistros.value || filteredRegistros.value.length === 0) return;

    filteredRegistros.value.forEach(registro => {
        let lat = registro.coordenadas?.lat;
        let lng = registro.coordenadas?.lng;
        
        if (!lat || !lng || (lat === 0 && lng === 0)) return;

        let color = '#10b981'; // Green
        let icono = '🟢';

        if (registro.categoria === 'desastre') {
            color = '#ef4444'; // Red
            icono = '🔴';
        } else if (registro.categoria !== 'normal' && registro.categoria !== 'condicion') {
            color = '#f59e0b'; // Yellow
            icono = '🟡';
        }

        const customIcon = L.divIcon({
            className: 'custom-marker',
            html: `<div style="background: ${color}; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); border: 3px solid white; cursor: pointer;">${icono}</div>`,
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });

        const marker = L.marker([lat, lng], { icon: customIcon });

        const popupContent = createPopupContent(registro);
        marker.bindPopup(popupContent, { maxWidth: 350 });

        markersLayer.addLayer(marker);
    });

    if (guatemalaLayer) {
        const bounds = guatemalaLayer.getBounds();
        if (bounds.isValid()) {
            map.fitBounds(bounds, { padding: [20, 20] });
        }
    } else if (filteredRegistros.value.length > 0) {
        const bounds = markersLayer.getBounds();
        if (bounds.isValid()) {
            map.fitBounds(bounds, { padding: [50, 50], maxZoom: 8 });
        }
    }
};

const applyFilters = () => {
    filteredRegistros.value = registros.value.filter(reg => {
        let match = true;
        if (filters.value.startDate) {
            const date = new Date(reg.fecha_registro);
            const start = new Date(filters.value.startDate);
            start.setHours(0, 0, 0, 0);
            if (date < start) match = false;
        }
        if (filters.value.endDate) {
            const date = new Date(reg.fecha_registro);
            const end = new Date(filters.value.endDate);
            end.setHours(23, 59, 59, 999);
            if (date > end) match = false;
        }
        if (filters.value.category) {
            if (filters.value.category === 'normal') {
                if (reg.categoria !== 'normal') match = false;
            } else if (filters.value.category === 'condicion') {
                if (reg.categoria !== 'condicion' && reg.categoria !== 'alerta') match = false;
            } else if (filters.value.category === 'desastre') {
                if (reg.categoria !== 'desastre') match = false;
            }
        }
        return match;
    });
    
    renderMarkers();
};

// Controles Personalizados
const zoomIn = () => {
    if (map) map.zoomIn();
};

const zoomOut = () => {
    if (map) map.zoomOut();
};

const centerMap = () => {
    if (map && guatemalaLayer) {
        const bounds = guatemalaLayer.getBounds();
        if (bounds.isValid()) {
            map.flyToBounds(bounds, { padding: [20, 20], duration: 1.5 });
        }
    } else if (map) {
        map.flyTo([15.7835, -90.2308], 7, { duration: 1.5 });
    }
};

const loadGuatemalaMask = async () => {
    try {
        const resp = await fetch('/guatemala.geojson');
        const geojson = await resp.json();
        const guatemalaCoords = geojson.features[0].geometry.coordinates;

        // Crear polígono invertido: Mundo exterior - Guatemala interior
        const worldCoords = [
            [[-90, -180], [90, -180], [90, 180], [-90, 180], [-90, -180]]
        ];

        if (geojson.features[0].geometry.type === 'Polygon') {
            const hole = guatemalaCoords[0].map(coord => [coord[1], coord[0]]);
            worldCoords.push(hole);
        } else if (geojson.features[0].geometry.type === 'MultiPolygon') {
            guatemalaCoords.forEach(polygon => {
                const hole = polygon[0].map(coord => [coord[1], coord[0]]);
                worldCoords.push(hole);
            });
        }

        // Sombra exterior
        L.polygon(worldCoords, {
            color: 'transparent',
            fillColor: '#000000',
            fillOpacity: 0.6
        }).addTo(map);

        // Borde luminoso de Guatemala
        guatemalaLayer = L.geoJSON(geojson, {
            style: {
                color: '#10b981',
                weight: 2,
                fillColor: 'transparent',
                opacity: 0.8
            }
        }).addTo(map);

        // Centrar el mapa a todo el país si ya cargaron los registros
        if (map && guatemalaLayer) {
            const bounds = guatemalaLayer.getBounds();
            if (bounds.isValid()) {
                map.fitBounds(bounds, { padding: [20, 20] });
            }
        }

    } catch (e) {
        console.error('Error al cargar la máscara de Guatemala:', e);
    }
};

const loadRegistrosParaMapa = async () => {
    try {
        const resp = await api.get('/clima/registros');
        if (resp.data.status === 'success') {
            registros.value = resp.data.data;
            filteredRegistros.value = [...registros.value];
            renderMarkers();
        }
    } catch (e) {
        console.error(e);
    }
};

onMounted(() => {
    initMap();
    loadGuatemalaMask();
    loadRegistrosParaMapa();
});

onUnmounted(() => {
    if (map) {
        map.remove();
    }
});
</script>

<style>
.leaflet-popup-content-wrapper {
    border-radius: 12px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
}
.custom-marker {
    background: transparent;
    border: none;
}
/* Ensure z-index is lower than Tailwind's modals and headers */
.leaflet-container {
    z-index: 10 !important;
}
.leaflet-control-container .leaflet-top,
.leaflet-control-container .leaflet-bottom {
    z-index: 20 !important;
}
</style>
