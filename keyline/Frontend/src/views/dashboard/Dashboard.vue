<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Panel ejecutivo</h1>
            <p class="text-sm text-slate-500">Vista general del avance nacional de parcelas Keyline.</p>
        </div>

        <div v-if="loading" class="py-12 text-center text-slate-400">Cargando…</div>

        <template v-else-if="data">
            <!-- Stat cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <StatCard icon="🗺️" label="Parcelas registradas" :value="data.totales.parcelas" sub="Total en la base actual" :tendencia="data.tendencias?.parcelas" />
                <StatCard icon="🌾" label="Área acumulada" :value="`${fmtNum(data.totales.areaHa)} ha`" sub="Hectáreas bajo diagnóstico o intervención" :tendencia="data.tendencias?.areaHa" />
                <StatCard icon="📍" label="Cobertura territorial" :value="`${data.totales.coberturaPct}%`" :sub="`${data.totales.departamentos} de ${data.totales.metaDepartamentos} departamentos`" />
                <StatCard icon="👨‍👩‍👧‍👦" label="Familias beneficiadas" :value="data.totales.familiasBeneficiadas" :sub="`${data.totales.municipios} municipios alcanzados`" />
            </div>

            <!-- Insights -->
            <section v-if="data.insights?.length" class="card mb-6">
                <h3 class="card-title">Inteligencia del sistema</h3>
                <p class="card-sub">Alertas y lecturas generadas automáticamente a partir de los datos cargados.</p>
                <div class="grid gap-3 mt-4">
                    <div v-for="(ins, i) in data.insights" :key="i" class="flex gap-3 bg-slate-50 rounded-lg p-3">
                        <span>{{ { alert: '⚠️', recommendation: '💡', trend: '📈' }[ins.tipo] || '🔎' }}</span>
                        <div>
                            <p class="text-xs font-bold uppercase text-slate-400">{{ { alert: 'Alerta', recommendation: 'Recomendación', trend: 'Tendencia' }[ins.tipo] }}</p>
                            <p class="text-sm text-slate-700">{{ ins.texto }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <section class="card">
                    <h3 class="card-title">Semáforo de seguimiento</h3>
                    <p class="card-sub">Visión rápida del estado del portafolio.</p>
                    <div class="grid grid-cols-3 gap-3 mt-4">
                        <div class="bg-slate-50 rounded-lg p-3">
                            <div class="flex items-center gap-2 text-sm font-semibold"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>Ejecutadas</div>
                            <div class="text-2xl font-bold mt-1">{{ data.semaforo.verde }}</div>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-3">
                            <div class="flex items-center gap-2 text-sm font-semibold"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>En proceso</div>
                            <div class="text-2xl font-bold mt-1">{{ data.semaforo.amarillo }}</div>
                        </div>
                        <div class="bg-slate-50 rounded-lg p-3">
                            <div class="flex items-center gap-2 text-sm font-semibold"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>Pendientes</div>
                            <div class="text-2xl font-bold mt-1">{{ data.semaforo.rojo }}</div>
                        </div>
                    </div>
                    <p class="text-sm text-slate-500 mt-4">
                        El proyecto cubre <strong>{{ data.totales.departamentos }} departamentos</strong>
                        ({{ data.totales.coberturaPct }}% de cobertura nacional) con <strong>{{ data.totales.validadas }}</strong>
                        parcelas validadas y <strong>{{ data.totales.pendientesValidacion }}</strong> pendientes de revisión.
                    </p>
                </section>

                <section class="card">
                    <h3 class="card-title">Validación técnica</h3>
                    <p class="card-sub">Revisión de calidad de la información cargada.</p>
                    <canvas ref="chartValidacionRef" height="180"></canvas>
                </section>
            </div>

            <section class="card mb-6">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="card-title">Mapa nacional de parcelas</h3>
                        <p class="card-sub">Ubicación georreferenciada de las parcelas con coordenadas registradas.</p>
                    </div>
                    <span class="tag bg-slate-100 text-slate-600">{{ data.puntosMapa.length }} puntos con GPS</span>
                </div>
                <div ref="mapRef" class="rounded-lg" style="height: 420px;"></div>
            </section>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <section class="card">
                    <h3 class="card-title">Parcelas por departamento</h3>
                    <p class="card-sub">Ranking según registros ingresados.</p>
                    <div v-if="!data.porDepartamento.length" class="text-sm text-slate-400 mt-4">Aún no hay datos para mostrar el ranking por departamento.</div>
                    <div v-else class="space-y-3 mt-4">
                        <div v-for="it in data.porDepartamento" :key="it.departamento">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-semibold">{{ it.departamento }}</span>
                                <span class="text-slate-500">{{ it.cantidad }}</span>
                            </div>
                            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-primary-500" :style="{ width: (it.cantidad / data.porDepartamento[0].cantidad * 100) + '%' }"></div>
                            </div>
                        </div>
                    </div>
                </section>
                <section class="card">
                    <h3 class="card-title">Estado del proceso</h3>
                    <p class="card-sub">Distribución por fase técnica.</p>
                    <canvas ref="chartEstadoRef" height="220"></canvas>
                </section>
            </div>

            <section class="card mb-6">
                <h3 class="card-title">Diagnóstico físico del suelo</h3>
                <p class="card-sub">Talpetate, encharcamiento, profundidad y bioindicadores.</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-4">
                    <div class="bg-slate-50 rounded-lg p-3">
                        <div class="text-sm font-semibold">🪨 Talpetate</div>
                        <div class="text-2xl font-bold mt-1">{{ data.diagnosticoFisico.conTalpetate }}</div>
                        <div class="text-xs text-slate-500 mt-1">de {{ data.totales.parcelas }} parcelas. Sin talpetate: {{ data.diagnosticoFisico.sinTalpetate }}.</div>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-3">
                        <div class="text-sm font-semibold">💧 Encharcamiento</div>
                        <div class="text-2xl font-bold mt-1">{{ data.diagnosticoFisico.conEncharca }}</div>
                        <div class="text-xs text-slate-500 mt-1">Sin encharcamiento: {{ data.diagnosticoFisico.sinEncharca }}.</div>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-3">
                        <div class="text-sm font-semibold">📏 Profundidad de suelo</div>
                        <div class="text-2xl font-bold mt-1">{{ data.diagnosticoFisico.profundidadProm.toFixed(1) }} cm</div>
                        <div class="text-xs text-slate-500 mt-1">Promedio ({{ data.diagnosticoFisico.muestras }} mediciones).</div>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-3 md:col-span-3">
                        <div class="text-sm font-semibold mb-2">🌱 Bioindicadores de suelo</div>
                        <div class="flex flex-wrap gap-2">
                            <span v-if="!data.topBioindicadores.length" class="text-sm text-slate-400">Aún no hay bioindicadores capturados.</span>
                            <span v-for="b in data.topBioindicadores" :key="b.nombre" class="tag bg-white border border-slate-200">{{ b.nombre }} · {{ b.conteo }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="card">
                <h3 class="card-title">Registros recientes</h3>
                <p class="card-sub">Últimas parcelas cargadas por el equipo técnico.</p>
                <div class="overflow-x-auto mt-4">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                            <tr><th class="p-2 text-left">Parcela</th><th class="p-2 text-left">Código</th><th class="p-2 text-left">Técnico</th><th class="p-2 text-left">Departamento</th><th class="p-2 text-left">Estado</th></tr>
                        </thead>
                        <tbody>
                            <tr v-if="!data.registrosRecientes.length"><td colspan="5" class="p-4 text-center text-slate-400">Sin registros aún.</td></tr>
                            <tr v-for="r in data.registrosRecientes" :key="r.id" class="border-t border-slate-100">
                                <td class="p-2 font-semibold">{{ r.nombre }}</td>
                                <td class="p-2">{{ r.codigo }}</td>
                                <td class="p-2">{{ r.tecnico }}</td>
                                <td class="p-2">{{ r.departamento }}</td>
                                <td class="p-2"><span class="tag" :class="ESTADO_COLORS[r.estado]">{{ r.estado }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </template>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import Chart from 'chart.js/auto';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import dashboardService from '../../services/dashboardService';
import { ESTADO_COLORS } from '../../constants/keyline';
import StatCard from '../../components/dashboard/StatCard.vue';

const loading = ref(true);
const data = ref(null);
const mapRef = ref(null);
const chartValidacionRef = ref(null);
const chartEstadoRef = ref(null);

function fmtNum(n) {
    return Number(n || 0).toLocaleString('es-GT', { maximumFractionDigits: 1 });
}

onMounted(async () => {
    try {
        const { data: resumen } = await dashboardService.resumen();
        data.value = resumen;
        await nextTick();
        renderMap(resumen.puntosMapa);
        renderChartValidacion(resumen.totales);
        renderChartEstado(resumen.porEstadoProceso);
    } finally {
        loading.value = false;
    }
});

function renderMap(puntos) {
    if (!mapRef.value) return;
    const center = puntos.length ? [puntos[0].lat, puntos[0].lng] : [15.5, -90.25];
    const map = L.map(mapRef.value, { scrollWheelZoom: false }).setView(center, puntos.length ? 7 : 6);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap &copy; CARTO', maxZoom: 18,
    }).addTo(map);

    const colorFor = { Implementado: '#10b981', 'Diseño': '#f59e0b', Levantamiento: '#3b82f6', Pendiente: '#ef4444' };
    puntos.forEach((p) => {
        const marker = L.circleMarker([p.lat, p.lng], {
            radius: 8, color: colorFor[p.estado] || '#10b981', fillColor: colorFor[p.estado] || '#10b981', fillOpacity: 0.75, weight: 2,
        }).addTo(map);
        marker.bindPopup(`<strong>${p.nombre}</strong><br>${p.codigo}<br>${p.departamento}<br>Estado: ${p.estado}`);
    });
    if (!puntos.length) {
        L.popup().setLatLng(center).setContent('Aún no hay parcelas con coordenadas GPS registradas.').openOn(map);
    }
}

function renderChartValidacion(totales) {
    if (!chartValidacionRef.value) return;
    new Chart(chartValidacionRef.value, {
        type: 'doughnut',
        data: {
            labels: ['Validadas', 'Pendientes de revisión', 'Otras'],
            datasets: [{
                data: [totales.validadas, totales.pendientesValidacion, Math.max(totales.parcelas - totales.validadas - totales.pendientesValidacion, 0)],
                backgroundColor: ['#10b981', '#f59e0b', '#e2e8f0'],
                borderWidth: 0,
            }],
        },
        options: { plugins: { legend: { position: 'bottom' } }, cutout: '68%' },
    });
}

function renderChartEstado(items) {
    if (!chartEstadoRef.value) return;
    const colors = { Levantamiento: '#3b82f6', 'Diseño': '#f59e0b', Implementado: '#10b981', Pendiente: '#ef4444' };
    new Chart(chartEstadoRef.value, {
        type: 'bar',
        data: {
            labels: items.map((i) => i.estado),
            datasets: [{ data: items.map((i) => i.cantidad), backgroundColor: items.map((i) => colors[i.estado] || '#10b981'), borderRadius: 8 }],
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
        },
    });
}
</script>

<style scoped>
.card { @apply bg-white rounded-lg shadow p-5; }
.card-title { @apply text-lg font-bold; }
.card-sub { @apply text-sm text-slate-500; }
.tag { @apply text-xs font-semibold px-2 py-1 rounded-full; }
</style>
