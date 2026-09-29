<template>
    <div class="min-h-screen">
        <div class="app-bg" aria-hidden="true"></div>

        <div class="mx-auto max-w-[1480px] px-3.5 sm:px-6 py-4 sm:py-6 pb-10">
            <!-- Encabezado -->
            <header class="flex items-center justify-between gap-4 mb-5">
                <div class="min-w-0">
                    <h1 class="text-[28px] sm:text-[34px] leading-tight font-extrabold" style="color: var(--green-dark);">Registros de daños</h1>
                    <p class="text-[13px] sm:text-sm" style="color: var(--navy);">Consulta de afectaciones reportadas en campo</p>
                </div>
                <div class="flex gap-2 flex-shrink-0">
                    <button class="btn btn-outline btn-sm" :disabled="loading" @click="cargar"><ArrowPathIcon class="w-5 h-5" :class="{ 'animate-spin': loading }" /><span class="hidden sm:inline">Actualizar</span></button>
                    <router-link to="/" class="btn btn-primary btn-sm"><PlusIcon class="w-5 h-5" />Nuevo registro</router-link>
                </div>
            </header>

            <!-- Resumen -->
            <section class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-5">
                <div v-for="k in kpis" :key="k.label" class="glass p-4 flex items-center gap-3" style="border-radius: 18px;">
                    <span class="icon-badge" :style="{ background: k.color }"><component :is="k.icon" class="w-6 h-6" /></span>
                    <div class="min-w-0">
                        <p class="text-[12px] sm:text-[13px]" style="color: var(--muted);">{{ k.label }}</p>
                        <p class="text-[20px] sm:text-[24px] font-extrabold leading-tight truncate">{{ k.value }}</p>
                    </div>
                </div>
            </section>

            <!-- Filtros -->
            <section class="glass p-4 mb-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                    <div class="lg:col-span-2">
                        <label class="label" for="f-q">Buscar</label>
                        <div class="relative">
                            <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--muted);" />
                            <input id="f-q" v-model="filtros.q" class="field" style="padding-left: 40px;" placeholder="Lote, cultivo, causa o técnico" @keyup.enter="cargar" />
                        </div>
                    </div>
                    <div>
                        <label class="label" for="f-desde">Desde</label>
                        <input id="f-desde" v-model="filtros.desde" type="date" class="field" />
                    </div>
                    <div>
                        <label class="label" for="f-hasta">Hasta</label>
                        <input id="f-hasta" v-model="filtros.hasta" type="date" class="field" />
                    </div>
                </div>
                <div class="flex gap-2 mt-3 justify-end">
                    <button class="btn btn-outline btn-sm" @click="limpiar">Limpiar</button>
                    <button class="btn btn-primary btn-sm" @click="cargar">Filtrar</button>
                </div>
            </section>

            <!-- Listado -->
            <section class="glass p-4 sm:p-5">
                <div class="flex items-center gap-3 mb-4">
                    <span class="icon-badge" style="background: var(--green-dark);"><TableCellsIcon class="w-6 h-6" /></span>
                    <div>
                        <h2 class="section-title">Registros</h2>
                        <p class="section-desc">{{ danos.length }} {{ danos.length === 1 ? 'registro' : 'registros' }}</p>
                    </div>
                </div>

                <p v-if="loading && !danos.length" class="py-10 text-center" style="color: var(--muted);">Cargando…</p>
                <p v-else-if="error" class="py-10 text-center" style="color: var(--red);">{{ error }}</p>
                <p v-else-if="!danos.length" class="py-10 text-center" style="color: var(--muted);">No hay registros para mostrar.</p>

                <template v-else>
                    <!-- Tabla (escritorio) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-[14px]">
                            <thead>
                                <tr class="text-left text-[13px]" style="color: var(--muted);">
                                    <th class="py-2 pr-3 font-medium">Fecha</th>
                                    <th class="py-2 pr-3 font-medium">Técnico / Productor</th>
                                    <th class="py-2 pr-3 font-medium">Lote</th>
                                    <th class="py-2 pr-3 font-medium">Cultivo</th>
                                    <th class="py-2 pr-3 font-medium">Causa</th>
                                    <th class="py-2 pr-3 font-medium text-right">Daño</th>
                                    <th class="py-2 pr-3 font-medium text-right">Pérdida est.</th>
                                    <th class="py-2 pr-3 font-medium text-center">Fotos</th>
                                    <th class="py-2 font-medium"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="d in danos" :key="d.id" class="border-t cursor-pointer hover:bg-white/60" style="border-color: var(--input-border);" @click="detalle = d">
                                    <td class="py-3 pr-3 whitespace-nowrap">{{ d.fecha }}</td>
                                    <td class="py-3 pr-3">{{ d.tecnico }}</td>
                                    <td class="py-3 pr-3">{{ d.lote }}</td>
                                    <td class="py-3 pr-3">{{ d.cultivo }}</td>
                                    <td class="py-3 pr-3">{{ d.causa }}</td>
                                    <td class="py-3 pr-3 text-right"><span class="px-2 py-1 rounded-full text-[12px] font-bold" :style="badge(d.danoPorc)">{{ num(d.danoPorc) }}%</span></td>
                                    <td class="py-3 pr-3 text-right whitespace-nowrap">{{ d.perdidaEstimada != null ? num(d.perdidaEstimada) + ' ' + (d.unidad || '').split('/')[0] : '—' }}</td>
                                    <td class="py-3 pr-3 text-center">{{ d.fotos.length }}</td>
                                    <td class="py-3 text-right">
                                        <button class="btn btn-outline btn-sm" style="min-height: 34px; padding: 4px 10px;" title="Eliminar" @click.stop="eliminar(d)"><TrashIcon class="w-4 h-4" style="color: var(--red);" /></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tarjetas (móvil) -->
                    <div class="md:hidden space-y-3">
                        <div v-for="d in danos" :key="d.id" class="card-inner p-3" @click="detalle = d">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="font-bold truncate">{{ d.cultivo }} · {{ d.lote }}</p>
                                    <p class="text-[13px]" style="color: var(--muted);">{{ d.fecha }} · {{ d.tecnico }}</p>
                                </div>
                                <span class="px-2 py-1 rounded-full text-[12px] font-bold flex-shrink-0" :style="badge(d.danoPorc)">{{ num(d.danoPorc) }}%</span>
                            </div>
                            <p class="text-[13px] mt-1">{{ d.causa }}</p>
                        </div>
                    </div>
                </template>
            </section>
        </div>

        <!-- Detalle -->
        <div v-if="detalle" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background: rgba(11,31,77,.35);" @click.self="detalle = null">
            <div class="glass w-full max-w-[560px] max-h-[90vh] overflow-y-auto p-5" style="background: rgba(255,255,255,.92);">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="section-title">{{ detalle.cultivo }} · {{ detalle.lote }}</h2>
                    <button class="btn btn-outline btn-sm" aria-label="Cerrar" @click="detalle = null"><XMarkIcon class="w-5 h-5" /></button>
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
import { ref, reactive, computed, onMounted } from 'vue';
import danoCultivoService from '../../services/danoCultivoService';
import { toastSuccess, toastError, confirmDialog } from '../../utils/alerts';
import {
    ArrowPathIcon, PlusIcon, TrashIcon, XMarkIcon, TableCellsIcon, MagnifyingGlassIcon,
    DocumentTextIcon, ChartBarIcon, MapPinIcon, ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const danos = ref([]);
const loading = ref(false);
const error = ref('');
const detalle = ref(null);
const filtros = reactive({ q: '', desde: '', hasta: '' });

const fotoUrl = (d, f) => `/Da%C3%B1osACultivos/Backend/uploads/danos-cultivos/${d.id}/fotos/${encodeURIComponent(f.archivo)}`;

const num = (v) => Number(v ?? 0).toLocaleString('es-GT', { maximumFractionDigits: 2 });

// Verde / naranja / rojo según la gravedad del daño.
const badge = (p) => {
    const n = Number(p) || 0;
    if (n >= 50) return { background: '#FDE3E5', color: '#B4232F' };
    if (n >= 25) return { background: '#FFEBD6', color: '#B65500' };
    return { background: 'var(--green-light)', color: 'var(--green-dark)' };
};

const kpis = computed(() => {
    const l = danos.value;
    const area = l.reduce((a, d) => a + (Number(d.areaAfectada) || 0), 0);
    const prom = l.length ? l.reduce((a, d) => a + (Number(d.danoPorc) || 0), 0) / l.length : 0;
    const causas = {};
    l.forEach((d) => { causas[d.causa] = (causas[d.causa] || 0) + 1; });
    const top = Object.entries(causas).sort((a, b) => b[1] - a[1])[0];
    return [
        { label: 'Registros', value: l.length, icon: DocumentTextIcon, color: 'var(--green)' },
        { label: 'Área afectada', value: num(area) + ' ha', icon: MapPinIcon, color: 'var(--blue)' },
        { label: 'Daño promedio', value: num(prom) + '%', icon: ChartBarIcon, color: 'var(--orange)' },
        { label: 'Causa principal', value: top ? top[0] : '—', icon: ExclamationTriangleIcon, color: 'var(--purple)' },
    ];
});

const filasDetalle = computed(() => {
    const d = detalle.value;
    if (!d) return [];
    return [
        ['Fecha', d.fecha], ['Técnico / Productor', d.tecnico],
        ['Cultivo', d.cultivo], ['Etapa', d.etapa || '—'],
        ['Causa', d.causa], ['Daño', num(d.danoPorc) + '%'],
        ['Área del lote', d.areaLote != null ? num(d.areaLote) + ' ha' : '—'], ['Área afectada', num(d.areaAfectada) + ' ha'],
        ['Rendimiento esperado', d.rendEsperado != null ? num(d.rendEsperado) + ' ' + d.unidad : '—'],
        ['Pérdida estimada', d.perdidaEstimada != null ? num(d.perdidaEstimada) : '—'],
        ['GPS', d.gps || '—', true], ['Fotos', d.fotos.length], ['Notas', d.notas || '—', true],
    ];
});

const cargar = async () => {
    loading.value = true;
    error.value = '';
    try {
        const params = Object.fromEntries(Object.entries(filtros).filter(([, v]) => v));
        danos.value = await danoCultivoService.list(params);
    } catch (e) {
        error.value = 'No se pudieron cargar los registros.';
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const limpiar = () => { Object.assign(filtros, { q: '', desde: '', hasta: '' }); cargar(); };

const eliminar = async (d) => {
    if (!(await confirmDialog(`Se eliminará el registro de ${d.cultivo} (${d.lote}).`, { title: '¿Eliminar registro?', danger: true, confirmText: 'Eliminar' }))) return;
    try {
        await danoCultivoService.remove(d.id);
        danos.value = danos.value.filter((x) => x.id !== d.id);
        toastSuccess('Registro eliminado');
    } catch (e) {
        toastError('No se pudo eliminar');
    }
};

onMounted(cargar);
</script>
