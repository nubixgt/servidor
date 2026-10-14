<template>
    <div>
        <section>
            <div class="content-head">
                <div>
                    <h2>Panorama del campo</h2>
                    <p>Una mirada a los registros y las áreas afectadas.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="panel flex items-center gap-2 !px-3 !py-1.5" style="border-radius: 12px;">
                        <CalendarDaysIcon class="w-5 h-5" style="color: var(--green);" />
                        <input v-model="filtros.desde" type="date" class="bg-transparent text-[12px] outline-none" aria-label="Desde" />
                        <span style="color: var(--muted);">–</span>
                        <input v-model="filtros.hasta" type="date" class="bg-transparent text-[12px] outline-none" aria-label="Hasta" />
                        <button v-if="filtros.desde || filtros.hasta" class="p-1" aria-label="Limpiar fechas" @click="filtros.desde = filtros.hasta = ''"><XMarkIcon class="w-4 h-4" /></button>
                    </div>
                    <button class="btn btn-outline btn-sm" :disabled="loading" aria-label="Actualizar" @click="cargar"><ArrowPathIcon class="w-5 h-5" :class="{ 'animate-spin': loading }" /></button>
                </div>
            </div>

            <p v-if="error" class="panel mb-4 text-center" style="color: var(--red);">{{ error }}</p>

            <!-- KPIs -->
            <div class="stats">
                <div v-for="k in kpis" :key="k.label" class="panel stat">
                    <div class="stat-icon"><component :is="k.icon" class="w-5 h-5" /></div>
                    <div class="stat-title">{{ k.label }}</div>
                    <strong>{{ k.value }}</strong>
                    <div v-if="k.trend != null" class="stat-foot">
                        <b :style="{ color: k.trend >= 0 ? '#008359' : 'var(--red)' }">{{ k.trend >= 0 ? '↑' : '↓' }} {{ Math.abs(k.trend) }}%</b> vs. periodo anterior
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <article class="panel">
                    <div class="panel-head">
                        <div><h3>Área afectada en el tiempo</h3><p>Superficie reportada por mes · hectáreas</p></div>
                        <span class="badge">Seguimiento</span>
                    </div>
                    <p v-if="!porMes.length" class="py-10 text-center section-desc">Sin datos</p>
                    <div v-else class="chart-area" role="img" aria-label="Área afectada por mes">
                        <div class="gridlines"><span>{{ num(maxHa) }}</span><span>{{ num(maxHa / 2) }}</span><span>0</span></div>
                        <div class="bars">
                            <div v-for="m in porMes" :key="m.key" class="monthbar" :style="{ height: Math.max(m.pct, 3) + '%' }" :title="`${m.label}: ${num(m.ha)} ha · ${m.n} reg.`">
                                <span class="barvalue">{{ num(m.ha) }}</span>
                                <span class="monthlabel">{{ m.corto }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="chart-caption"><span class="dot"></span>Superficie de los registros seleccionados</div>
                </article>

                <article class="panel">
                    <div class="panel-head">
                        <div><h3>Nivel de afectación</h3><p>Distribución de los registros</p></div>
                    </div>
                    <div class="distribution">
                        <div class="donut" :style="{ background: donut }">
                            <div class="center"><strong>{{ lista.length }}</strong><small>registros</small></div>
                        </div>
                        <div class="legend">
                            <div v-for="n in porNivel" :key="n.id"><i class="dot" :style="{ background: n.color }"></i><span>{{ n.label }}</span><b>{{ n.n }}</b></div>
                        </div>
                    </div>
                </article>
            </div>

            <div class="lower-grid">
                <article class="panel">
                    <div class="panel-head"><div><h3>Principales causas</h3><p>Registros por tipo de daño</p></div></div>
                    <p v-if="!porCausa.length" class="small muted section-desc">Sin datos en este período.</p>
                    <div v-for="r in porCausa.slice(0, 5)" :key="r.nombre" class="rankrow">
                        <div class="ranktop"><span class="truncate" :title="r.nombre">{{ r.nombre }}</span><b>{{ r.n }} <small style="color: var(--muted); font-weight: 400;">· {{ r.share }}%</small></b></div>
                        <div class="track"><i :style="{ width: r.pct + '%' }"></i></div>
                    </div>
                </article>

                <article class="panel">
                    <div class="panel-head"><div><h3>Cultivos afectados</h3><p>Registros por tipo de cultivo</p></div></div>
                    <p v-if="!porCultivo.length" class="section-desc">Sin cultivos reportados.</p>
                    <div v-for="r in porCultivo.slice(0, 5)" :key="r.nombre" class="crop-row">
                        <div class="crop-symbol"><SparklesIcon class="w-4 h-4" /></div>
                        <div><strong>{{ r.nombre }}</strong><small>{{ r.n }} registros</small></div>
                        <span class="amount">{{ r.share }}<small> %</small></span>
                    </div>
                </article>

                <article class="panel coverage-panel">
                    <div class="panel-head"><div><h3>Cobertura territorial</h3><p>Departamentos con reportes</p></div></div>
                    <p v-if="!porDepartamento.length" class="section-desc">Sin departamentos reportados.</p>
                    <div class="coverage-grid">
                        <div v-for="t in porDepartamento" :key="t.nombre" class="territory">
                            <MapPinIcon />
                            <strong>{{ t.nombre }}</strong>
                            <span>{{ t.n }} reportes · {{ num(t.ha) }} ha</span>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Mapa -->
            <article class="panel mt-5 flex flex-col">
                <div class="panel-head">
                    <div><h3>Ubicación de registros</h3><p>Registros con GPS · {{ conGps }} de {{ lista.length }}</p></div>
                    <MapPinIcon class="w-5 h-5" style="color: var(--muted);" />
                </div>
                <div class="relative min-h-[320px] rounded-2xl overflow-hidden" style="border: 1px solid var(--input-border);">
                    <div ref="mapaEl" class="absolute inset-0"></div>
                    <div class="absolute left-2 bottom-2 z-[500] card-inner px-3 py-2 text-[12px]">
                        <p class="font-semibold mb-1">Nivel de afectación</p>
                        <p v-for="n in NIVELES" :key="n.id" class="flex items-center gap-2"><span class="w-3 h-3 rounded-full" :style="{ background: n.color }"></span>{{ n.label }}</p>
                    </div>
                </div>
            </article>

            <!-- Registros recientes -->
            <article class="panel mt-5">
                <div class="panel-head">
                    <div><h3>{{ verTodos ? 'Todos los registros' : 'Últimos registros' }}</h3><p>Lo más reciente del trabajo en campo</p></div>
                    <button v-if="lista.length > 5" class="linkbtn" @click="verTodos = !verTodos">{{ verTodos ? 'Ver menos' : 'Ver todos →' }}</button>
                </div>

                <p v-if="loading && !lista.length" class="py-10 text-center section-desc">Cargando…</p>
                <p v-else-if="!lista.length" class="py-10 text-center section-desc">No hay registros para mostrar.</p>

                <div v-else class="tablewrap">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Lote / Productor</th>
                                <th v-if="verRegistrante">Registrado por</th>
                                <th>Cultivo</th>
                                <th>Causa</th>
                                <th>Área</th>
                                <th>Afectación</th>
                                <th>Fecha</th>
                                <th>Descripción</th>
                                <th>Evidencia</th>
                                <th><span class="sr-only">Detalle</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="d in visibles" :key="d.id" class="cursor-pointer" @click="detalle = d">
                                <td><strong>{{ d.lote }}</strong><small>{{ d.tecnico }}</small></td>
                                <td v-if="verRegistrante">{{ d.registradoPor || '—' }}</td>
                                <td><div class="cellcrop"><span class="record-indicator"><SparklesIcon class="w-4 h-4" /></span>{{ d.cultivo }}</div></td>
                                <td>{{ d.causa }}</td>
                                <td><b>{{ num(d.areaAfectada) }}</b> <small style="display: inline;">ha</small></td>
                                <td><span class="badge" :class="nivelClase(d)">{{ nivelDe(d).label }}</span><small>{{ num(d.danoPorc) }}% de daño</small></td>
                                <td>{{ fechaCorta(d.fecha) }}</td>
                                <td class="max-w-[220px] truncate" :title="d.notas">{{ d.notas || '—' }}</td>
                                <td>
                                    <div class="flex gap-1">
                                        <img v-for="f in d.fotos.slice(0, 3)" :key="f.id" :src="fotoUrl(d, f)" :alt="f.nombre" loading="lazy" class="w-9 h-9 rounded-md object-cover" />
                                        <span v-if="!d.fotos.length" style="color: var(--muted);">—</span>
                                    </div>
                                </td>
                                <td><span class="iconbtn" aria-hidden="true"><ArrowRightIcon class="w-4 h-4" /></span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <!-- Detalle -->
        <div v-if="detalle" class="fixed inset-0 z-[1000] flex items-center justify-center p-4" style="background: rgba(8, 42, 58, .53); backdrop-filter: blur(5px);" @click.self="detalle = null">
            <div class="modal-box w-full max-w-[680px] max-h-[88vh] overflow-y-auto">
                <div class="modal-head">
                    <h2>{{ detalle.cultivo }} · {{ detalle.lote }}</h2>
                    <div class="flex gap-2">
                        <button v-if="puedeEliminar" class="iconbtn" aria-label="Eliminar" @click="eliminar(detalle)"><TrashIcon class="w-5 h-5" style="color: var(--red);" /></button>
                        <button class="iconbtn" aria-label="Cerrar" @click="detalle = null"><XMarkIcon class="w-5 h-5" /></button>
                    </div>
                </div>
                <div class="modal-content">
                    <dl class="grid grid-cols-2 gap-x-5 gap-y-5">
                        <div v-for="r in filasDetalle" :key="r[0]" :class="r[2] ? 'col-span-2' : ''">
                            <dt class="text-[10px]" style="color: #809689;">{{ r[0] }}</dt>
                            <dd class="text-[12px] font-bold break-words mt-1">{{ r[1] }}</dd>
                        </div>
                    </dl>
                    <div v-if="detalle.fotos.length" class="mt-5">
                        <p class="label">Fotos</p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <a v-for="f in detalle.fotos" :key="f.id" :href="fotoUrl(detalle, f)" target="_blank" rel="noopener" :title="f.nombre">
                                <img :src="fotoUrl(detalle, f)" :alt="f.nombre" loading="lazy" class="w-full aspect-square object-cover rounded-xl" style="border: 1px solid var(--input-border);" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import danoCultivoService from '../../services/danoCultivoService';
import authService from '../../services/authService';
import { toastSuccess, toastError, confirmDialog } from '../../utils/alerts';
import {
    ArrowPathIcon, ArrowRightIcon, PlusIcon, TrashIcon, XMarkIcon, TableCellsIcon, CalendarDaysIcon,
    ChevronRightIcon, DocumentTextIcon, MapPinIcon, UserGroupIcon, ExclamationTriangleIcon, SparklesIcon,
} from '@heroicons/vue/24/outline';

const NIVELES = [
    { id: 'bajo', label: 'Bajo', color: '#3BB54A', texto: '#1F7A2B' },
    { id: 'medio', label: 'Medio', color: '#F5C400', texto: '#8A6D00' },
    { id: 'alto', label: 'Alto', color: '#FF7A00', texto: '#B65500' },
    { id: 'total', label: 'Pérdida total', color: '#EF3340', texto: '#B4232F' },
];
const PALETA = ['#079447', '#F5B800', '#FF7A00', '#1677FF', '#7C3AED', '#0EA5A5', '#94A3B8'];

const puedeEliminar = ['admin', 'supervisor'].includes(authService.user()?.rol);
const verRegistrante = authService.user()?.rol !== 'tecnico';
const todos = ref([]);
const loading = ref(false);
const error = ref('');
const detalle = ref(null);
const verTodos = ref(false);
const filtros = reactive({ desde: '', hasta: '' });
const mapaEl = ref(null);
let mapa = null;
let capa = null;

const num = (v) => Number(v ?? 0).toLocaleString('es-GT', { maximumFractionDigits: 2 });
const fotoUrl = (d, f) => `/Da%C3%B1osACultivos/Backend/uploads/danos-cultivos/${d.id}/fotos/${encodeURIComponent(f.archivo)}`;
const fechaCorta = (f) => new Date(f + 'T00:00:00').toLocaleDateString('es-GT', { day: 'numeric', month: 'short', year: 'numeric' });

const nivelDe = (d) => {
    const p = Number(d.danoPorc) || 0;
    return NIVELES[p >= 100 ? 3 : p >= 50 ? 2 : p >= 25 ? 1 : 0];
};

const enRango = (d, desde, hasta) => (!desde || d.fecha >= desde) && (!hasta || d.fecha <= hasta);
const lista = computed(() => todos.value.filter((d) => enRango(d, filtros.desde, filtros.hasta)));
const visibles = computed(() => (verTodos.value ? lista.value : lista.value.slice(0, 5)));
const conGps = computed(() => lista.value.filter((d) => d.gpsLat != null && d.gpsLon != null).length);

// Periodo anterior de igual duración (solo si hay rango completo).
const previa = computed(() => {
    if (!filtros.desde || !filtros.hasta) return null;
    const ini = new Date(filtros.desde + 'T00:00:00');
    const fin = new Date(filtros.hasta + 'T00:00:00');
    const dias = Math.round((fin - ini) / 864e5) + 1;
    const iso = (dt) => dt.toISOString().slice(0, 10);
    const finPrev = new Date(ini.getTime() - 864e5);
    const iniPrev = new Date(finPrev.getTime() - (dias - 1) * 864e5);
    return todos.value.filter((d) => enRango(d, iso(iniPrev), iso(finPrev)));
});
const tendencia = (fn) => {
    if (!previa.value) return null;
    const a = fn(lista.value), b = fn(previa.value);
    if (!b) return null;
    return Math.round(((a - b) / b) * 100);
};

const area = (l) => l.reduce((a, d) => a + (Number(d.areaAfectada) || 0), 0);
const productores = (l) => new Set(l.map((d) => (d.tecnico || '').trim().toLowerCase())).size;
const perdidaTotal = (l) => l.filter((d) => (Number(d.danoPorc) || 0) >= 100).length;

const kpis = computed(() => [
    { label: 'Total de registros', value: lista.value.length, trend: tendencia((l) => l.length), icon: DocumentTextIcon, bg: '#E3EEFF', color: '#1677FF' },
    { label: 'Área afectada', value: num(area(lista.value)) + ' ha', trend: tendencia(area), icon: MapPinIcon, bg: '#FFE9D6', color: '#FF7A00' },
    { label: 'Productores reportados', value: productores(lista.value), trend: tendencia(productores), icon: UserGroupIcon, bg: '#DDF6E8', color: '#079447' },
    { label: 'Registros con pérdida total', value: perdidaTotal(lista.value), trend: tendencia(perdidaTotal), icon: ExclamationTriangleIcon, bg: '#FDE3E5', color: '#EF3340' },
]);

// Top 6 + "Otros".
const agrupar = (campo) => {
    const total = lista.value.length;
    const cuenta = {};
    lista.value.forEach((d) => { const k = d[campo] || 'Sin dato'; cuenta[k] = (cuenta[k] || 0) + 1; });
    let filas = Object.entries(cuenta).sort((a, b) => b[1] - a[1]);
    if (filas.length > 7) {
        const resto = filas.slice(6).reduce((a, f) => a + f[1], 0);
        filas = [...filas.slice(0, 6), ['Otros', resto]];
    }
    const max = filas[0]?.[1] || 1;
    return filas.map(([nombre, n]) => ({ nombre, n, pct: (n / max) * 100, share: Math.round((n / total) * 100) }));
};
const porCausa = computed(() => agrupar('causa'));
const nivelClase = (d) => ({ bajo: '', medio: 'amber', alto: 'red', total: 'purple' }[nivelDe(d).id]);
const maxHa = computed(() => Math.max(...porMes.value.map((m) => m.ha), 0));
const porDepartamento = computed(() => {
    const m = {};
    lista.value.forEach((d) => {
        if (!d.departamento) return;
        m[d.departamento] ||= { nombre: d.departamento, n: 0, ha: 0 };
        m[d.departamento].n++;
        m[d.departamento].ha += Number(d.areaAfectada) || 0;
    });
    return Object.values(m).sort((a, b) => b.n - a.n).slice(0, 4);
});
const porCultivo = computed(() => agrupar('cultivo'));

const porMes = computed(() => {
    const m = {};
    lista.value.forEach((d) => {
        const key = d.fecha.slice(0, 7);
        m[key] ||= { key, ha: 0, n: 0 };
        m[key].ha += Number(d.areaAfectada) || 0;
        m[key].n++;
    });
    const filas = Object.values(m).sort((a, b) => a.key.localeCompare(b.key)).slice(-12);
    const max = Math.max(...filas.map((f) => f.ha), 1);
    return filas.map((f) => {
        const dt = new Date(f.key + '-01T00:00:00');
        return {
            ...f, pct: (f.ha / max) * 78,
            corto: dt.toLocaleDateString('es-GT', { month: 'short' }).replace('.', ''),
            label: dt.toLocaleDateString('es-GT', { month: 'long', year: 'numeric' }),
        };
    });
});

const porNivel = computed(() => NIVELES.map((n) => {
    const c = lista.value.filter((d) => nivelDe(d).id === n.id).length;
    return { ...n, n: c, share: lista.value.length ? Math.round((c / lista.value.length) * 100) : 0 };
}));
const donut = computed(() => {
    const total = lista.value.length;
    if (!total) return '#E5EAF0';
    let acc = 0;
    const partes = porNivel.value.map((n) => {
        const ini = acc;
        acc += (n.n / total) * 100;
        return `${n.color} ${ini}% ${acc}%`;
    });
    return `conic-gradient(${partes.join(', ')})`;
});

const filasDetalle = computed(() => {
    const d = detalle.value;
    if (!d) return [];
    return [
        ['Fecha', d.fecha], ['Productor', d.tecnico],
        ['Ubicación', [d.centroPoblado, d.municipio, d.departamento].filter(Boolean).join(', ') || '—', true],
        ['Agricultor en RUA', d.agricultorRua ? `Sí (${d.codigoRua || '—'})` : 'No'],
        ['Cultivo', d.cultivo], ['Etapa', d.etapa || '—'],
        ...(d.asociados || []).map((a, i) => [`Cultivo asociado ${i + 1}`, `${a.cultivo}${a.etapa ? ' · ' + a.etapa : ''} · ${num(a.areaAfectada)} ha afectadas`, true]),
        ['Causa', d.causa === 'Otra' && d.causaOtra ? `Otra: ${d.causaOtra}` : d.causa], ['Daño', num(d.danoPorc) + '% · ' + nivelDe(d).label],
        ['Área del lote', d.areaLote != null ? num(d.areaLote) + ' ha' : '—'], ['Área afectada', num(d.areaAfectada) + ' ha'],
        ['Rendimiento esperado', d.rendEsperado != null ? num(d.rendEsperado) + ' ' + d.unidad : '—'],
        ['Pérdida estimada', d.perdidaEstimada != null ? num(d.perdidaEstimada) : '—'],
        ['Registrado por', d.registradoPor || '—'], ['GPS', d.gps || '—', true], ['Notas', d.notas || '—', true],
    ];
});

const cargar = async () => {
    loading.value = true;
    error.value = '';
    try {
        todos.value = await danoCultivoService.list();
    } catch (e) {
        error.value = 'No se pudieron cargar los registros.';
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const eliminar = async (d) => {
    if (!(await confirmDialog(`Se eliminará el registro de ${d.cultivo} (${d.lote}).`, { title: '¿Eliminar registro?', danger: true, confirmText: 'Eliminar' }))) return;
    try {
        await danoCultivoService.remove(d.id);
        todos.value = todos.value.filter((x) => x.id !== d.id);
        detalle.value = null;
        toastSuccess('Registro eliminado');
    } catch (e) {
        toastError('No se pudo eliminar');
    }
};

const pintarMapa = () => {
    if (!mapa) return;
    capa.clearLayers();
    const pts = [];
    lista.value.forEach((d) => {
        if (d.gpsLat == null || d.gpsLon == null) return;
        const ll = [Number(d.gpsLat), Number(d.gpsLon)];
        pts.push(ll);
        const n = nivelDe(d);
        L.circleMarker(ll, { radius: 8, color: '#fff', weight: 2, fillColor: n.color, fillOpacity: 0.95 })
            .bindTooltip(`${d.cultivo} · ${d.causa}`)
            .on('click', () => { detalle.value = d; })
            .addTo(capa);
    });
    if (pts.length) mapa.fitBounds(pts, { padding: [30, 30], maxZoom: 13 });
    else mapa.setView([15.6, -90.4], 7);
};

watch(lista, () => nextTick(pintarMapa));

onMounted(async () => {
    mapa = L.map(mapaEl.value, { zoomControl: true, attributionControl: true }).setView([15.6, -90.4], 7);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(mapa);
    capa = L.layerGroup().addTo(mapa);
    await cargar();
    pintarMapa();
});

onBeforeUnmount(() => { mapa?.remove(); mapa = null; });
</script>
