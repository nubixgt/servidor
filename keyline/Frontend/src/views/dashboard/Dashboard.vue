<template>
    <div>
        <div v-if="loading" class="py-12 text-center hint">Cargando…</div>

        <template v-else-if="data">
            <div class="stat-row">
                <StatCard icon="🗺️" label="Parcelas registradas" :value="data.totales.parcelas" sub="Total en la base actual" :tendencia="data.tendencias?.parcelas" />
                <StatCard icon="🌾" label="Área acumulada" :value="`${fmtNum(data.totales.areaHa)} ha`" sub="Hectáreas bajo diagnóstico o intervención" :tendencia="data.tendencias?.areaHa" />
                <StatCard icon="📍" label="Cobertura territorial" :value="`${data.totales.coberturaPct}%`" :sub="`${data.totales.departamentos} de ${data.totales.metaDepartamentos} departamentos`" />
                <StatCard icon="👨‍👩‍👧‍👦" label="Familias beneficiadas" :value="data.totales.familiasBeneficiadas" :sub="`${data.totales.municipios} municipios alcanzados`" />
            </div>

            <section v-if="data.insights?.length" class="panel glass">
                <div class="panel-head">
                    <div><h3>Inteligencia del sistema</h3><p>Alertas y lecturas generadas automáticamente a partir de los datos cargados.</p></div>
                </div>
                <div class="insight-list">
                    <div v-for="(ins, i) in data.insights" :key="i" class="insight-card" :class="ins.tipo">
                        <span class="ic-icon">{{ { alert: '⚠️', recommendation: '💡', trend: '📈' }[ins.tipo] || '🔎' }}</span>
                        <div>
                            <p class="ic-tag">{{ { alert: 'Alerta', recommendation: 'Recomendación', trend: 'Tendencia' }[ins.tipo] }}</p>
                            <p>{{ ins.texto }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid2">
                <section class="panel glass">
                    <div class="panel-head"><div><h3>Semáforo de seguimiento</h3><p>Visión rápida del estado del portafolio.</p></div></div>
                    <div class="grid3">
                        <div class="traffic-card">
                            <div class="traffic-head"><span class="light green"></span>Ejecutadas</div>
                            <div class="mini-value">{{ data.semaforo.verde }}</div>
                            <div class="hint">Parcelas implementadas.</div>
                        </div>
                        <div class="traffic-card">
                            <div class="traffic-head"><span class="light yellow"></span>En proceso</div>
                            <div class="mini-value">{{ data.semaforo.amarillo }}</div>
                            <div class="hint">Diseño y levantamiento en curso.</div>
                        </div>
                        <div class="traffic-card">
                            <div class="traffic-head"><span class="light red"></span>Pendientes</div>
                            <div class="mini-value">{{ data.semaforo.rojo }}</div>
                            <div class="hint">Requieren seguimiento.</div>
                        </div>
                    </div>
                    <p class="hint" style="margin-top:14px;">
                        El proyecto cubre <strong>{{ data.totales.departamentos }} departamentos</strong>
                        ({{ data.totales.coberturaPct }}% de cobertura nacional) con <strong>{{ data.totales.validadas }}</strong>
                        parcelas validadas y <strong>{{ data.totales.pendientesValidacion }}</strong> pendientes de revisión.
                    </p>
                </section>

                <section class="panel glass">
                    <div class="panel-head"><div><h3>Validación técnica</h3><p>Revisión de calidad de la información cargada.</p></div></div>
                    <canvas ref="chartValidacionRef" height="180"></canvas>
                </section>
            </div>

            <section class="panel glass">
                <div class="panel-head">
                    <div><h3>Mapa nacional de parcelas</h3><p>Ubicación georreferenciada de las parcelas con coordenadas registradas.</p></div>
                    <span class="badge">{{ data.puntosMapa.length }} puntos con GPS</span>
                </div>
                <div ref="mapRef" class="leaflet-map"></div>
            </section>

            <div class="grid2">
                <section class="panel glass">
                    <div class="panel-head"><div><h3>Parcelas por departamento</h3><p>Ranking según registros ingresados.</p></div></div>
                    <div v-if="!data.porDepartamento.length" class="hint">Aún no hay datos para mostrar el ranking por departamento.</div>
                    <div v-else class="bar-chart">
                        <div v-for="it in data.porDepartamento" :key="it.departamento" class="bar-row">
                            <div><strong>{{ it.departamento }}</strong><br /><span class="hint">{{ fmtNum(it.area) }} ha</span></div>
                            <div class="bar-track"><div class="bar-fill" :style="{ width: (it.cantidad / data.porDepartamento[0].cantidad * 100) + '%' }"></div></div>
                            <div><strong>{{ it.cantidad }}</strong></div>
                        </div>
                    </div>
                </section>
                <section class="panel glass">
                    <div class="panel-head"><div><h3>Estado del proceso</h3><p>Distribución por fase técnica.</p></div></div>
                    <canvas ref="chartEstadoRef" height="220"></canvas>
                </section>
            </div>

            <section class="panel glass">
                <div class="panel-head"><div><h3>Diagnóstico físico del suelo</h3><p>Talpetate, encharcamiento, profundidad y bioindicadores.</p></div></div>
                <div class="grid3">
                    <div class="traffic-card">
                        <div class="traffic-head">🪨 Talpetate</div>
                        <div class="mini-value">{{ data.diagnosticoFisico.conTalpetate }}</div>
                        <div class="hint">de {{ data.totales.parcelas }} parcelas. Sin talpetate: {{ data.diagnosticoFisico.sinTalpetate }}.</div>
                    </div>
                    <div class="traffic-card">
                        <div class="traffic-head">💧 Encharcamiento</div>
                        <div class="mini-value">{{ data.diagnosticoFisico.conEncharca }}</div>
                        <div class="hint">Sin encharcamiento: {{ data.diagnosticoFisico.sinEncharca }}.</div>
                    </div>
                    <div class="traffic-card">
                        <div class="traffic-head">📏 Profundidad de suelo</div>
                        <div class="mini-value">{{ data.diagnosticoFisico.profundidadProm.toFixed(1) }} cm</div>
                        <div class="hint">Promedio ({{ data.diagnosticoFisico.muestras }} mediciones).</div>
                    </div>
                    <div class="traffic-card" style="grid-column: 1 / -1;">
                        <div class="traffic-head">🌱 Bioindicadores de suelo</div>
                        <div class="chip-list">
                            <span v-if="!data.topBioindicadores.length" class="hint">Aún no hay bioindicadores capturados.</span>
                            <span v-for="b in data.topBioindicadores" :key="b.nombre" class="chip">{{ b.nombre }} · {{ b.conteo }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="panel glass">
                <div class="panel-head"><div><h3>Registros recientes</h3><p>Últimas parcelas cargadas por el equipo técnico.</p></div></div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Parcela</th><th>Código</th><th>Técnico</th><th>Departamento</th><th>Estado</th></tr>
                        </thead>
                        <tbody>
                            <tr v-if="!data.registrosRecientes.length"><td colspan="5" class="empty-state">Sin registros aún.</td></tr>
                            <tr v-for="r in data.registrosRecientes" :key="r.id">
                                <td><strong>{{ r.nombre }}</strong></td>
                                <td>{{ r.codigo }}</td>
                                <td>{{ r.tecnico }}</td>
                                <td>{{ r.departamento }}</td>
                                <td><span class="tag" :class="ESTADO_COLORS[r.estado]">{{ r.estado }}</span></td>
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
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap &copy; CARTO', maxZoom: 18,
    }).addTo(map);

    const colorFor = { Implementado: '#34d17c', 'Diseño': '#f5b93f', Levantamiento: '#3fb6e0', Pendiente: '#ef5757' };
    puntos.forEach((p) => {
        const marker = L.circleMarker([p.lat, p.lng], {
            radius: 8, color: colorFor[p.estado] || '#34d17c', fillColor: colorFor[p.estado] || '#34d17c', fillOpacity: 0.75, weight: 2,
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
                backgroundColor: ['#34d17c', '#f5b93f', 'rgba(255,255,255,0.15)'],
                borderWidth: 0,
            }],
        },
        options: { plugins: { legend: { position: 'bottom', labels: { color: '#eef7f0', font: { size: 11 } } } }, cutout: '68%' },
    });
}

function renderChartEstado(items) {
    if (!chartEstadoRef.value) return;
    const colors = { Levantamiento: '#3fb6e0', 'Diseño': '#f5b93f', Implementado: '#34d17c', Pendiente: '#ef5757' };
    new Chart(chartEstadoRef.value, {
        type: 'bar',
        data: {
            labels: items.map((i) => i.estado),
            datasets: [{ data: items.map((i) => i.cantidad), backgroundColor: items.map((i) => colors[i.estado] || '#34d17c'), borderRadius: 8 }],
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: '#eef7f0', font: { size: 11 } }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { color: '#eef7f0', font: { size: 11 }, stepSize: 1 }, grid: { color: 'rgba(255,255,255,0.08)' } },
            },
        },
    });
}
</script>
