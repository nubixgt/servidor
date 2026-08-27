<template>
    <div>
        <div v-if="loading" class="py-12 text-center text-xs text-white/60">Cargando…</div>

        <template v-else-if="data">
            <div class="space-y-5 max-w-[1600px] mx-auto pb-4">
                <!-- Greeting & controls -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight flex items-center gap-2">
                            ¡Hola, {{ primerNombre }}! <span class="text-xl">🌱</span>
                        </h1>
                        <p class="text-xs sm:text-sm text-white/60 mt-1 font-normal">
                            Aquí tienes el resumen del avance nacional de parcelas keyline.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <button
                                class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white/10 border border-white/20 text-xs font-medium text-white hover:border-white/40 transition-colors shadow-sm"
                                @click="showDateDropdown = !showDateDropdown"
                            >
                                <Calendar class="w-3.5 h-3.5 text-white/60" />
                                <span>{{ dateRange }}</span>
                                <ChevronDown class="w-3 h-3 text-white/60" />
                            </button>

                            <div v-if="showDateDropdown" class="absolute right-0 mt-1.5 w-44 bg-[#0c1e17]/95 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] shadow-2xl p-1.5 z-30 animate-fadeIn">
                                <button
                                    v-for="range in ['Este mes', 'Último trimestre', 'Año 2026', 'Todo el histórico']"
                                    :key="range"
                                    class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs transition-colors"
                                    :class="dateRange === range ? 'bg-white/20 text-white font-semibold' : 'text-white/80 hover:bg-white/15'"
                                    @click="dateRange = range; showDateDropdown = false"
                                >
                                    {{ range }}
                                </button>
                            </div>
                        </div>

                        <router-link
                            :to="{ name: 'Reportes' }"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-white/10 hover:bg-white/15 border border-white/20 hover:border-white/40 text-xs font-medium text-white transition-all shadow-sm"
                        >
                            <Download class="w-3.5 h-3.5 text-[#22c55e]" />
                            <span>Exportar reporte</span>
                        </router-link>
                    </div>
                </div>

                <!-- KPI cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <StatCard :icon="Layers" label="Parcelas registradas" :value="data.totales.parcelas" sub="Total en la base actual" :tendencia="data.tendencias?.parcelas" />
                    <StatCard :icon="Sprout" label="Área acumulada" :value="`${fmtNum(data.totales.areaHa)} ha`" sub="Hectáreas bajo diagnóstico o intervención" :tendencia="data.tendencias?.areaHa" />
                    <StatCard :icon="MapPin" label="Cobertura territorial" :value="`${data.totales.coberturaPct}%`" :sub="`${data.totales.departamentos} de ${data.totales.metaDepartamentos} departamentos`" />
                    <StatCard :icon="Users" label="Familias beneficiadas" :value="data.totales.familiasBeneficiadas" :sub="`${data.totales.municipios} municipios alcanzados`" />
                </div>

                <!-- Insights -->
                <section v-if="data.insights?.length" class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5">
                    <h3 class="text-[15px] font-bold text-white">Inteligencia del sistema</h3>
                    <p class="text-[11px] text-white/60 mb-3">Alertas y lecturas generadas automáticamente a partir de los datos cargados.</p>
                    <div class="space-y-2">
                        <div v-for="(ins, i) in data.insights" :key="i" class="flex gap-3 items-start p-3 rounded-xl bg-white/5 border border-white/15">
                            <span class="w-8 h-8 rounded-lg bg-black/30 border border-white/10 flex items-center justify-center flex-shrink-0"
                                :class="{ 'text-[#f87171]': ins.tipo === 'alert', 'text-[#facc15]': ins.tipo === 'recommendation', 'text-[#4ade80]': ins.tipo === 'trend' }">
                                <component :is="{ alert: AlertTriangle, recommendation: Lightbulb, trend: TrendingUp }[ins.tipo] || Search" class="w-4 h-4" />
                            </span>
                            <div>
                                <p class="text-[10px] uppercase tracking-wider font-bold text-white/40">{{ { alert: 'Alerta', recommendation: 'Recomendación', trend: 'Tendencia' }[ins.tipo] }}</p>
                                <p class="text-[13px] text-white/80 leading-snug mt-0.5">{{ ins.texto }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Semáforo & Validación -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5 flex flex-col justify-between">
                        <div>
                            <h3 class="text-[15px] font-bold text-white">Semáforo de seguimiento</h3>
                            <p class="text-[11px] text-white/60">Visión rápida del estado del portafolio.</p>

                            <div class="grid grid-cols-3 gap-3 mt-4">
                                <div class="bg-white/5 border border-white/15 rounded-xl p-3.5">
                                    <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#22c55e]"></span><span class="text-xs text-white font-medium">Ejecutadas</span></div>
                                    <div class="text-2xl font-bold text-white mt-2">{{ data.semaforo.verde }}</div>
                                    <p class="text-[10px] text-white/60 mt-0.5 leading-snug">Parcelas implementadas.</p>
                                </div>
                                <div class="bg-white/5 border border-white/15 rounded-xl p-3.5">
                                    <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#eab308]"></span><span class="text-xs text-white font-medium">En proceso</span></div>
                                    <div class="text-2xl font-bold text-white mt-2">{{ data.semaforo.amarillo }}</div>
                                    <p class="text-[10px] text-white/60 mt-0.5 leading-snug">Diseño y levantamiento.</p>
                                </div>
                                <div class="bg-white/5 border border-white/15 rounded-xl p-3.5">
                                    <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#ef4444]"></span><span class="text-xs text-white font-medium">Pendientes</span></div>
                                    <div class="text-2xl font-bold text-white mt-2">{{ data.semaforo.rojo }}</div>
                                    <p class="text-[10px] text-white/60 mt-0.5 leading-snug">Requieren seguimiento.</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-white/15">
                            <p class="text-[11px] text-white/60 leading-relaxed">
                                El proyecto cubre <span class="text-white font-semibold">{{ data.totales.departamentos }} departamentos</span>
                                ({{ data.totales.coberturaPct }}% de cobertura nacional) con <span class="text-white font-semibold">{{ data.totales.validadas }} parcelas validadas</span>
                                y <span class="text-[#eab308] font-semibold">{{ data.totales.pendientesValidacion }} pendientes de revisión</span>.
                            </p>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5 flex flex-col justify-between">
                        <div>
                            <h3 class="text-[15px] font-bold text-white">Validación técnica</h3>
                            <p class="text-[11px] text-white/60">Revisión de calidad de la información cargada.</p>
                        </div>
                        <div class="relative h-[200px] my-2">
                            <canvas ref="chartValidacionRef"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Mapa + rankings -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                    <div class="lg:col-span-7 bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5 flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3 class="text-[15px] font-bold text-white">Mapa nacional de parcelas</h3>
                                <p class="text-[11px] text-white/60">Ubicación georreferenciada de las parcelas con coordenadas registradas.</p>
                            </div>
                            <span class="text-[10px] px-2.5 py-1 rounded-full bg-black/40 border border-white/10 text-white/80 font-semibold whitespace-nowrap">{{ data.puntosMapa.length }} puntos con GPS</span>
                        </div>
                        <div ref="mapRef" class="w-full h-[320px] rounded-xl overflow-hidden border border-white/20"></div>
                        <div class="flex items-center gap-4 mt-3 text-xs text-white/80">
                            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#22c55e]"></span><span>Ejecutadas</span></div>
                            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#eab308]"></span><span>En proceso</span></div>
                            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#ef4444]"></span><span>Pendientes</span></div>
                        </div>
                    </div>

                    <div class="lg:col-span-5 space-y-4 flex flex-col justify-between">
                        <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5">
                            <h3 class="text-[15px] font-bold text-white">Parcelas por departamento</h3>
                            <p class="text-[11px] text-white/60 mb-3">Ranking según registros ingresados.</p>
                            <div v-if="!data.porDepartamento.length" class="text-xs text-white/60">Aún no hay datos para mostrar el ranking por departamento.</div>
                            <div v-else class="space-y-2.5">
                                <div v-for="(it, i) in data.porDepartamento" :key="it.departamento" class="flex items-center gap-3 text-xs">
                                    <span class="w-4 text-white/60 font-bold text-[11px]">{{ i + 1 }}</span>
                                    <span class="w-24 text-white truncate font-medium">{{ it.departamento }}</span>
                                    <div class="flex-1 h-2 bg-black/30 rounded-full overflow-hidden border border-white/15">
                                        <div class="h-full bg-[#22c55e] rounded-full" :style="{ width: (it.cantidad / data.porDepartamento[0].cantidad * 100) + '%' }"></div>
                                    </div>
                                    <span class="w-16 text-right text-white font-medium text-[11px]">{{ fmtNum(it.area) }} ha</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5">
                            <h3 class="text-[15px] font-bold text-white">Estado del proceso</h3>
                            <p class="text-[11px] text-white/60 mb-3">Distribución por fase técnica.</p>
                            <div class="relative h-[220px]">
                                <canvas ref="chartEstadoRef"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Diagnóstico + registros recientes -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                    <div class="lg:col-span-7 bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5">
                        <h3 class="text-[15px] font-bold text-white">Diagnóstico físico del suelo</h3>
                        <p class="text-[11px] text-white/60 mb-4">Limitantes de uso, encharcamiento, profundidad y bioindicadores.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <div class="bg-white/5 border border-white/15 rounded-xl p-3">
                                <div class="flex items-center gap-1.5 text-xs text-white/60"><AlertOctagon class="w-3.5 h-3.5 text-[#ef4444]" /><span class="font-semibold text-white">Limitantes de uso</span></div>
                                <div class="text-xl font-bold text-white mt-1">{{ data.diagnosticoFisico.conLimitantes }}</div>
                                <p class="text-[10px] text-white/60 mt-2 leading-tight" v-if="!data.topLimitantes.length">de {{ data.totales.parcelas }} parcelas.</p>
                                <div v-else class="flex flex-wrap gap-1 mt-2">
                                    <span v-for="l in data.topLimitantes.slice(0, 4)" :key="l.nombre" class="text-[9px] bg-[#ef4444]/15 text-[#fca5a5] border border-[#ef4444]/30 px-1.5 py-0.5 rounded-md font-medium">{{ l.nombre }}</span>
                                </div>
                            </div>
                            <div class="bg-white/5 border border-white/15 rounded-xl p-3">
                                <div class="flex items-center gap-1.5 text-xs text-white/60"><Droplets class="w-3.5 h-3.5 text-[#38bdf8]" /><span class="font-semibold text-white">Encharcamiento</span></div>
                                <div class="text-xl font-bold text-white mt-1">{{ data.diagnosticoFisico.conEncharca }}</div>
                                <p class="text-[10px] text-white/60 mt-0.5">Sin encharcamiento: {{ data.diagnosticoFisico.sinEncharca }}.</p>
                            </div>
                            <div class="bg-white/5 border border-white/15 rounded-xl p-3">
                                <div class="flex items-center gap-1.5 text-xs text-white/60"><Sprout class="w-3.5 h-3.5 text-[#22c55e]" /><span class="font-semibold text-white">Profundidad de suelo</span></div>
                                <div class="text-xl font-bold text-white mt-1">{{ data.diagnosticoFisico.profundidadProm.toFixed(1) }} cm</div>
                                <p class="text-[10px] text-white/60 mt-0.5">Promedio ({{ data.diagnosticoFisico.muestras }} mediciones).</p>
                            </div>
                            <div class="bg-white/5 border border-white/15 rounded-xl p-3 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center gap-1.5 text-xs text-white/60"><Sprout class="w-3.5 h-3.5 text-[#22c55e]" /><span class="font-semibold text-white">Bioindicadores</span></div>
                                    <p class="text-[10px] text-white/60 mt-2 leading-tight" v-if="!data.topBioindicadores.length">Aún no hay bioindicadores capturados.</p>
                                    <div v-else class="flex flex-wrap gap-1 mt-2">
                                        <span v-for="b in data.topBioindicadores.slice(0, 4)" :key="b.nombre" class="text-[9px] bg-[#22c55e]/15 text-[#86efac] border border-[#22c55e]/30 px-1.5 py-0.5 rounded-md font-medium">{{ b.nombre }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-5 bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5">
                        <h3 class="text-[15px] font-bold text-white">Registros recientes</h3>
                        <p class="text-[11px] text-white/60 mb-3">Últimas parcelas cargadas por el equipo técnico.</p>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="text-[10px] text-white/60 border-b border-white/15 uppercase tracking-wider font-semibold">
                                        <th class="pb-2 font-semibold">Parcela</th>
                                        <th class="pb-2 font-semibold">Técnico</th>
                                        <th class="pb-2 font-semibold">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/10">
                                    <tr v-if="!data.registrosRecientes.length"><td colspan="3" class="py-6 text-center text-white/60">Sin registros aún.</td></tr>
                                    <tr v-for="r in data.registrosRecientes" :key="r.id" class="hover:bg-white/10 transition-colors cursor-pointer" @click="openDetail(r)">
                                        <td class="py-2.5 pr-2">
                                            <p class="font-semibold text-white text-[11px] truncate">{{ r.nombre }}</p>
                                            <p class="text-[9px] text-white/60 truncate">{{ r.codigo }} · {{ r.departamento }}</p>
                                        </td>
                                        <td class="py-2.5 px-2 text-white/80 text-[11px] truncate">{{ r.tecnico }}</td>
                                        <td class="py-2.5 pl-2">
                                            <span class="tag" :class="ESTADO_COLORS[r.estado]">{{ r.estado }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <ParcelaDetailModal v-if="detail" :parcela="detail" @close="detail = null" />
        </template>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import Chart from 'chart.js/auto';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import dashboardService from '../../services/dashboardService';
import parcelaService from '../../services/parcelaService';
import StatCard from '../../components/dashboard/StatCard.vue';
import ParcelaDetailModal from '../../components/parcelas/ParcelaDetailModal.vue';
import { ESTADO_COLORS } from '../../constants/keyline';
import { useAuthStore } from '../../stores/auth';
import {
    Calendar, ChevronDown, Download, Layers, Sprout, MapPin, Users,
    AlertTriangle, Lightbulb, TrendingUp, Search, AlertOctagon, Droplets,
} from '@lucide/vue';

const auth = useAuthStore();
const primerNombre = auth.user?.nombre?.trim().split(/\s+/)[0] || 'de nuevo';
const dateRange = ref('Este mes');
const showDateDropdown = ref(false);

const loading = ref(true);
const data = ref(null);
const detail = ref(null);
const mapRef = ref(null);
const chartValidacionRef = ref(null);
const chartEstadoRef = ref(null);
let mapInstance = null;
let chartValidacionInstance = null;
let chartEstadoInstance = null;

function fmtNum(n) {
    return Number(n || 0).toLocaleString('es-GT', { maximumFractionDigits: 1 });
}

async function openDetail(r) {
    const { data } = await parcelaService.obtener(r.id);
    detail.value = data.parcela;
}

onMounted(async () => {
    try {
        const { data: resumen } = await dashboardService.resumen();
        data.value = resumen;
        // loading debe pasar a false ANTES del nextTick: el mapa y las
        // gráficas viven dentro de v-else-if="data", que solo se monta
        // cuando loading ya es false. Si no, los refs del mapa/canvas
        // siguen en null y renderMap/renderChart* retornan sin hacer nada
        // (y sin lanzar error).
        loading.value = false;
        await nextTick();
        // Cada gráfica/mapa se aísla en su propio try: si uno falla, no debe
        // impedir que los demás se dibujen.
        try { renderMap(resumen.puntosMapa); } catch (e) { console.error('Error al iniciar el mapa:', e); }
        try { renderChartValidacion(resumen.totales); } catch (e) { console.error('Error en gráfico de validación:', e); }
        try { renderChartEstado(resumen.porEstadoProceso); } catch (e) { console.error('Error en gráfico de estado:', e); }
    } catch (e) {
        loading.value = false;
    }
});

onUnmounted(() => {
    if (mapInstance) { mapInstance.remove(); mapInstance = null; }
    if (chartValidacionInstance) { chartValidacionInstance.destroy(); chartValidacionInstance = null; }
    if (chartEstadoInstance) { chartEstadoInstance.destroy(); chartEstadoInstance = null; }
});

function renderMap(puntos) {
    if (!mapRef.value) return;
    if (mapInstance) {
        mapInstance.remove();
        mapInstance = null;
    }
    const center = puntos.length ? [puntos[0].lat, puntos[0].lng] : [15.5, -90.25];
    const map = L.map(mapRef.value, { scrollWheelZoom: false }).setView(center, puntos.length ? 7 : 6);
    mapInstance = map;
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap &copy; CARTO', maxZoom: 18,
    }).addTo(map);

    const colorFor = { Implementado: '#22c55e', 'Diseño': '#eab308', Levantamiento: '#38bdf8', Pendiente: '#ef4444' };
    puntos.forEach((p) => {
        const marker = L.circleMarker([p.lat, p.lng], {
            radius: 8, color: colorFor[p.estado] || '#22c55e', fillColor: colorFor[p.estado] || '#22c55e', fillOpacity: 0.75, weight: 2,
        }).addTo(map);
        marker.bindPopup(`<strong>${p.nombre}</strong><br>${p.codigo}<br>${p.departamento}<br>Estado: ${p.estado}`);
    });
    if (!puntos.length) {
        L.popup().setLatLng(center).setContent('Aún no hay parcelas con coordenadas GPS registradas.').openOn(map);
    }
    // El contenedor puede no tener su tamaño final resuelto en el primer paint
    // (transiciones/blur del panel); esto obliga a Leaflet a recalcular.
    setTimeout(() => map.invalidateSize(), 150);
}

function renderChartValidacion(totales) {
    if (!chartValidacionRef.value) return;
    if (chartValidacionInstance) {
        chartValidacionInstance.destroy();
        chartValidacionInstance = null;
    }
    chartValidacionInstance = new Chart(chartValidacionRef.value, {
        type: 'doughnut',
        data: {
            labels: ['Validadas', 'Pendientes de revisión', 'Otras'],
            datasets: [{
                data: [totales.validadas, totales.pendientesValidacion, Math.max(totales.parcelas - totales.validadas - totales.pendientesValidacion, 0)],
                backgroundColor: ['#22c55e', '#eab308', 'rgba(255,255,255,0.15)'],
                borderWidth: 0,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { color: 'rgba(255,255,255,0.85)', font: { size: 11 } } } },
            cutout: '68%',
        },
    });
}

function renderChartEstado(items) {
    if (!chartEstadoRef.value) return;
    if (chartEstadoInstance) {
        chartEstadoInstance.destroy();
        chartEstadoInstance = null;
    }
    const colors = { Levantamiento: '#38bdf8', 'Diseño': '#eab308', Implementado: '#22c55e', Pendiente: '#ef4444' };
    chartEstadoInstance = new Chart(chartEstadoRef.value, {
        type: 'bar',
        data: {
            labels: items.map((i) => i.estado),
            datasets: [{ data: items.map((i) => i.cantidad), backgroundColor: items.map((i) => colors[i.estado] || '#22c55e'), borderRadius: 8 }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: 'rgba(255,255,255,0.85)', font: { size: 11 } }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { color: 'rgba(255,255,255,0.85)', font: { size: 11 }, stepSize: 1 }, grid: { color: 'rgba(255,255,255,0.08)' } },
            },
        },
    });
}
</script>
