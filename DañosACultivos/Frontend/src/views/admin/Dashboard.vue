<template>
    <div class="min-h-screen">
        <div class="app-bg" aria-hidden="true"></div>

        <div class="mx-auto max-w-[1480px] px-3.5 sm:px-6 py-4 sm:py-6 pb-10">
            <!-- Encabezado -->
            <header class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-white/80 flex items-center justify-center flex-shrink-0" style="box-shadow: 0 10px 30px rgba(11,31,77,.14);">
                        <svg viewBox="0 0 48 48" class="w-9 h-9 sm:w-10 sm:h-10" fill="none" aria-hidden="true">
                            <path d="M24 42V22" stroke="#056B37" stroke-width="3" stroke-linecap="round" />
                            <path d="M24 26C24 15 15 9 6 9c0 11 7 17 18 17Z" fill="#079447" />
                            <path d="M24 22C24 13 31 7 42 7c0 10-7 15-18 15Z" fill="#35B96B" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-[28px] sm:text-[34px] leading-tight font-extrabold" style="color: var(--green-dark);">Daños a Cultivos</h1>
                        <p class="text-[13px] sm:text-sm" style="color: var(--navy);">Dashboard · Registro de afectaciones en campo</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="glass flex items-center gap-2 px-3 py-1.5" style="border-radius: 14px;">
                        <CalendarDaysIcon class="w-5 h-5" style="color: var(--green-dark);" />
                        <input v-model="filtros.desde" type="date" class="bg-transparent text-[13px] outline-none" aria-label="Desde" />
                        <span style="color: var(--muted);">–</span>
                        <input v-model="filtros.hasta" type="date" class="bg-transparent text-[13px] outline-none" aria-label="Hasta" />
                        <button v-if="filtros.desde || filtros.hasta" class="p-1" aria-label="Limpiar fechas" @click="filtros.desde = filtros.hasta = ''"><XMarkIcon class="w-4 h-4" /></button>
                    </div>
                    <button class="btn btn-outline btn-sm" :disabled="loading" @click="cargar"><ArrowPathIcon class="w-5 h-5" :class="{ 'animate-spin': loading }" /></button>
                    <router-link to="/" class="btn btn-primary btn-sm"><PlusIcon class="w-5 h-5" />Nuevo registro</router-link>
                </div>
            </header>

            <p v-if="error" class="glass p-4 mb-4 text-center" style="color: var(--red);">{{ error }}</p>

            <!-- KPIs -->
            <section class="grid grid-cols-2 xl:grid-cols-4 gap-3 lg:gap-4 mb-4">
                <div v-for="k in kpis" :key="k.label" class="glass p-4 flex items-center gap-3" style="border-radius: 20px;">
                    <span class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl flex items-center justify-center flex-shrink-0" :style="{ background: k.bg, color: k.color }"><component :is="k.icon" class="w-7 h-7" /></span>
                    <div class="min-w-0">
                        <p class="text-[12px] sm:text-[13px] leading-tight" style="color: var(--navy);">{{ k.label }}</p>
                        <p class="text-[24px] sm:text-[30px] font-extrabold leading-tight">{{ k.value }}</p>
                        <p v-if="k.trend != null" class="text-[11px] font-semibold" :style="{ color: k.trend >= 0 ? 'var(--green)' : 'var(--red)' }">
                            {{ k.trend >= 0 ? '↑' : '↓' }} {{ Math.abs(k.trend) }}% <span class="font-normal" style="color: var(--muted);">vs. periodo anterior</span>
                        </p>
                    </div>
                </div>
            </section>

            <!-- Mapa + tipo de daño + cultivos -->
            <section class="grid grid-cols-1 lg:grid-cols-3 gap-3 lg:gap-4 mb-4">
                <div class="glass p-4 flex flex-col">
                    <h2 class="section-title">Ubicación de registros</h2>
                    <p class="section-desc mb-3">Registros con GPS · {{ conGps }} de {{ lista.length }}</p>
                    <div class="relative flex-1 min-h-[280px] rounded-2xl overflow-hidden" style="border: 1px solid var(--input-border);">
                        <div ref="mapaEl" class="absolute inset-0"></div>
                        <div class="absolute left-2 bottom-2 z-[500] card-inner px-3 py-2 text-[12px]">
                            <p class="font-semibold mb-1">Nivel de afectación</p>
                            <p v-for="n in NIVELES" :key="n.id" class="flex items-center gap-2"><span class="w-3 h-3 rounded-full" :style="{ background: n.color }"></span>{{ n.label }}</p>
                        </div>
                    </div>
                </div>

                <div class="glass p-4">
                    <h2 class="section-title">Tipo de daño</h2>
                    <p class="section-desc mb-4">Registros por causa de afectación</p>
                    <p v-if="!porCausa.length" class="py-8 text-center section-desc">Sin datos</p>
                    <div v-for="(r, i) in porCausa" :key="r.nombre" class="flex items-center gap-2 mb-3 text-[13px]">
                        <span class="w-[38%] truncate" :title="r.nombre">{{ r.nombre }}</span>
                        <div class="flex-1 h-2.5 rounded-full bg-black/5"><div class="h-full rounded-full" :style="{ width: r.pct + '%', background: PALETA[i % PALETA.length] }"></div></div>
                        <span class="w-7 text-right font-semibold">{{ r.n }}</span>
                        <span class="w-9 text-right" style="color: var(--muted);">{{ r.share }}%</span>
                    </div>
                </div>

                <div class="glass p-4">
                    <h2 class="section-title">Cultivos más afectados</h2>
                    <p class="section-desc mb-4">Registros por tipo de cultivo</p>
                    <p v-if="!porCultivo.length" class="py-8 text-center section-desc">Sin datos</p>
                    <div v-for="(r, i) in porCultivo" :key="r.nombre" class="flex items-center gap-2 mb-3 text-[13px]">
                        <span class="w-[38%] truncate" :title="r.nombre">{{ r.nombre }}</span>
                        <div class="flex-1 h-2.5 rounded-full bg-black/5"><div class="h-full rounded-full" :style="{ width: r.pct + '%', background: PALETA[(i + 2) % PALETA.length] }"></div></div>
                        <span class="w-7 text-right font-semibold">{{ r.n }}</span>
                        <span class="w-9 text-right" style="color: var(--muted);">{{ r.share }}%</span>
                    </div>
                </div>
            </section>

            <!-- Área por mes + nivel -->
            <section class="grid grid-cols-1 lg:grid-cols-3 gap-3 lg:gap-4 mb-4">
                <div class="glass p-4 lg:col-span-2">
                    <h2 class="section-title">Área afectada por mes</h2>
                    <p class="section-desc mb-3">Hectáreas registradas</p>
                    <p v-if="!porMes.length" class="py-10 text-center section-desc">Sin datos</p>
                    <div v-else class="flex items-end gap-1.5 sm:gap-3 h-[190px] overflow-x-auto pb-1">
                        <div v-for="m in porMes" :key="m.key" class="group relative flex-1 min-w-[26px] h-full flex flex-col justify-end items-center" >
                            <div class="absolute bottom-full mb-1 hidden group-hover:block z-10 rounded-lg px-2 py-1 text-[12px] text-white whitespace-nowrap" style="background: var(--navy);">
                                <b>{{ m.label }}</b> · {{ num(m.ha) }} ha · {{ m.n }} reg.
                            </div>
                            <span class="text-[11px] mb-1" style="color: var(--muted);">{{ num(m.ha) }}</span>
                            <div class="w-full rounded-t-lg" :style="{ height: Math.max(m.pct, 3) + '%', background: 'linear-gradient(180deg,#4C9BFF,#1677FF)' }"></div>
                            <span class="text-[11px] mt-1" style="color: var(--muted);">{{ m.corto }}</span>
                        </div>
                    </div>
                </div>

                <div class="glass p-4">
                    <h2 class="section-title mb-3">Nivel de afectación</h2>
                    <div class="flex items-center gap-4">
                        <div class="relative w-[130px] h-[130px] flex-shrink-0">
                            <div class="w-full h-full rounded-full" :style="{ background: donut }"></div>
                            <div class="absolute inset-[22px] rounded-full bg-white flex flex-col items-center justify-center">
                                <b class="text-[22px] leading-none">{{ lista.length }}</b>
                                <span class="text-[11px]" style="color: var(--muted);">registros</span>
                            </div>
                        </div>
                        <ul class="flex-1 text-[13px] space-y-1.5">
                            <li v-for="n in porNivel" :key="n.id" class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full flex-shrink-0" :style="{ background: n.color }"></span>
                                <span class="flex-1">{{ n.label }}</span>
                                <b>{{ n.n }}</b><span style="color: var(--muted);">({{ n.share }}%)</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Registros recientes -->
            <section class="glass p-4 sm:p-5">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <div class="flex items-center gap-3">
                        <span class="icon-badge" style="background: var(--green-dark);"><TableCellsIcon class="w-6 h-6" /></span>
                        <h2 class="section-title">{{ verTodos ? 'Todos los registros' : 'Registros recientes' }}</h2>
                    </div>
                    <button v-if="lista.length > 5" class="btn btn-sm text-white" style="background: var(--green-dark);" @click="verTodos = !verTodos">
                        {{ verTodos ? 'Ver menos' : 'Ver todos los registros' }}<ArrowRightIcon class="w-4 h-4" />
                    </button>
                </div>

                <p v-if="loading && !lista.length" class="py-10 text-center section-desc">Cargando…</p>
                <p v-else-if="!lista.length" class="py-10 text-center section-desc">No hay registros para mostrar.</p>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-[13px] min-w-[860px]">
                        <thead>
                            <tr class="text-left" style="color: var(--muted);">
                                <th class="py-2 px-2 font-medium">Fecha</th>
                                <th class="py-2 px-2 font-medium">Lote / Técnico</th>
                                <th class="py-2 px-2 font-medium">Cultivo</th>
                                <th class="py-2 px-2 font-medium">Tipo de daño</th>
                                <th class="py-2 px-2 font-medium text-right">Área (ha)</th>
                                <th class="py-2 px-2 font-medium">Nivel</th>
                                <th class="py-2 px-2 font-medium">Descripción</th>
                                <th class="py-2 px-2 font-medium">Evidencia</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="d in visibles" :key="d.id" class="border-t cursor-pointer hover:bg-white/60" style="border-color: var(--input-border);" @click="detalle = d">
                                <td class="py-2.5 px-2 whitespace-nowrap">{{ fechaCorta(d.fecha) }}</td>
                                <td class="py-2.5 px-2"><b>{{ d.lote }}</b><br /><span style="color: var(--muted);">{{ d.tecnico }}</span></td>
                                <td class="py-2.5 px-2">{{ d.cultivo }}</td>
                                <td class="py-2.5 px-2 font-semibold">{{ d.causa }}</td>
                                <td class="py-2.5 px-2 text-right">{{ num(d.areaAfectada) }}</td>
                                <td class="py-2.5 px-2">
                                    <span class="px-2 py-1 rounded-full text-[12px] font-bold whitespace-nowrap" :style="{ background: nivelDe(d).color + '26', color: nivelDe(d).texto }">● {{ nivelDe(d).label }}</span>
                                </td>
                                <td class="py-2.5 px-2 max-w-[240px] truncate" :title="d.notas">{{ d.notas || '—' }}</td>
                                <td class="py-2.5 px-2">
                                    <div class="flex gap-1">
                                        <img v-for="f in d.fotos.slice(0, 3)" :key="f.id" :src="fotoUrl(d, f)" :alt="f.nombre" loading="lazy" class="w-9 h-9 rounded-md object-cover" />
                                        <span v-if="!d.fotos.length" style="color: var(--muted);">—</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-2"><ChevronRightIcon class="w-4 h-4" style="color: var(--muted);" /></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- Detalle -->
        <div v-if="detalle" class="fixed inset-0 z-[1000] flex items-center justify-center p-4" style="background: rgba(11,31,77,.35);" @click.self="detalle = null">
            <div class="glass w-full max-w-[560px] max-h-[90vh] overflow-y-auto p-5" style="background: rgba(255,255,255,.94);">
                <div class="flex items-center justify-between gap-2 mb-4">
                    <h2 class="section-title">{{ detalle.cultivo }} · {{ detalle.lote }}</h2>
                    <div class="flex gap-2">
                        <button class="btn btn-outline btn-sm" aria-label="Eliminar" @click="eliminar(detalle)"><TrashIcon class="w-5 h-5" style="color: var(--red);" /></button>
                        <button class="btn btn-outline btn-sm" aria-label="Cerrar" @click="detalle = null"><XMarkIcon class="w-5 h-5" /></button>
                    </div>
                </div>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-[14px]">
                    <div v-for="r in filasDetalle" :key="r[0]" :class="r[2] ? 'col-span-2' : ''">
                        <dt class="label" style="margin-bottom: 0;">{{ r[0] }}</dt>
                        <dd class="font-medium">{{ r[1] }}</dd>
                    </div>
                </dl>
                <div v-if="detalle.fotos.length" class="mt-4">
                    <p class="label">Fotos</p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <a v-for="f in detalle.fotos" :key="f.id" :href="fotoUrl(detalle, f)" target="_blank" rel="noopener" :title="f.nombre">
                            <img :src="fotoUrl(detalle, f)" :alt="f.nombre" loading="lazy" class="w-full aspect-square object-cover rounded-xl" style="border: 1px solid var(--input-border);" />
                        </a>
                    </div>
                </div>
                <div v-if="detalle.puntos.length" class="mt-4">
                    <p class="label">Puntos de muestreo</p>
                    <div v-for="(p, i) in detalle.puntos" :key="i" class="card-inner px-3 py-2 mb-2 text-[13px]">
                        <b>{{ p.ref || 'Punto ' + (i + 1) }}</b> — incidencia {{ p.incidencia }}, severidad {{ p.severidad }}<span v-if="p.obs"> · {{ p.obs }}</span>
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
import { toastSuccess, toastError, confirmDialog } from '../../utils/alerts';
import {
    ArrowPathIcon, ArrowRightIcon, PlusIcon, TrashIcon, XMarkIcon, TableCellsIcon, CalendarDaysIcon,
    ChevronRightIcon, DocumentTextIcon, MapPinIcon, UserGroupIcon, ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const NIVELES = [
    { id: 'bajo', label: 'Bajo', color: '#3BB54A', texto: '#1F7A2B' },
    { id: 'medio', label: 'Medio', color: '#F5C400', texto: '#8A6D00' },
    { id: 'alto', label: 'Alto', color: '#FF7A00', texto: '#B65500' },
    { id: 'total', label: 'Pérdida total', color: '#EF3340', texto: '#B4232F' },
];
const PALETA = ['#079447', '#F5B800', '#FF7A00', '#1677FF', '#7C3AED', '#0EA5A5', '#94A3B8'];

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
        ['Fecha', d.fecha], ['Técnico / Productor', d.tecnico],
        ['Cultivo', d.cultivo], ['Etapa', d.etapa || '—'],
        ['Causa', d.causa], ['Daño', num(d.danoPorc) + '% · ' + nivelDe(d).label],
        ['Área del lote', d.areaLote != null ? num(d.areaLote) + ' ha' : '—'], ['Área afectada', num(d.areaAfectada) + ' ha'],
        ['Rendimiento esperado', d.rendEsperado != null ? num(d.rendEsperado) + ' ' + d.unidad : '—'],
        ['Pérdida estimada', d.perdidaEstimada != null ? num(d.perdidaEstimada) : '—'],
        ['GPS', d.gps || '—', true], ['Notas', d.notas || '—', true],
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
