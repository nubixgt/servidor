<template>
    <div>
        <section>
            <div class="content-head">
                <div>
                    <h2>Registros de campo</h2>
                    <p>{{ esTecnico ? 'Los registros que has realizado.' : 'Consulta y organiza cada afectación reportada por los técnicos.' }}</p>
                </div>
                <router-link to="/registros/crear" class="btn btn-primary"><PlusIcon class="w-5 h-5" />Crear registro</router-link>
            </div>

            <div class="stats">
                <div v-for="s in stats" :key="s.title" class="panel stat">
                    <div class="stat-icon"><component :is="s.icon" class="w-5 h-5" /></div>
                    <div class="stat-title">{{ s.title }}</div>
                    <strong>{{ s.value }}<span v-if="s.unit">{{ s.unit }}</span></strong>
                    <div class="stat-foot" v-html="s.foot"></div>
                </div>
            </div>

            <article class="panel">
                <div class="panel-head">
                    <div>
                        <h3>Bitácora de afectaciones</h3>
                        <p>Encuentra un registro y consulta toda su información · Pérdida estimada acumulada {{ totalPerdida.toFixed(2) }}</p>
                    </div>
                    <button type="button" class="btn" @click="exportarCSV"><ArrowDownTrayIcon class="w-5 h-5" />Exportar CSV</button>
                </div>

                <div class="filters">
                    <div class="searchbox">
                        <MagnifyingGlassIcon />
                        <input v-model="busqueda" type="search" class="field" placeholder="Buscar lote, productor o municipio…" aria-label="Buscar registros" />
                    </div>
                    <select v-model="filtroCultivo" class="field" aria-label="Filtrar por cultivo">
                        <option value="">Todos los cultivos</option>
                        <option v-for="c in cultivosLista" :key="c">{{ c }}</option>
                    </select>
                    <select v-model="filtroNivel" class="field" aria-label="Filtrar por nivel">
                        <option value="">Todos los niveles</option>
                        <option v-for="n in NIVELES" :key="n">{{ n }}</option>
                    </select>
                    <div class="viewtoggle" aria-label="Vista de registros">
                        <button type="button" :class="{ active: vista === 'tabla' }" aria-label="Vista de tabla" :aria-pressed="vista === 'tabla'" @click="vista = 'tabla'"><TableCellsIcon class="w-5 h-5" /></button>
                        <button type="button" :class="{ active: vista === 'tarjetas' }" aria-label="Vista de tarjetas" :aria-pressed="vista === 'tarjetas'" @click="vista = 'tarjetas'"><Squares2X2Icon class="w-5 h-5" /></button>
                    </div>
                </div>

                <div v-if="loading" class="empty">Cargando…</div>
                <div v-else-if="!filtrados.length" class="empty">{{ registros.length ? 'No se encontraron registros con estos filtros.' : 'Aún no hay registros guardados.' }}</div>

                <div v-else-if="vista === 'tabla'" class="tablewrap">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Fecha</th><th>Técnico</th><th>Productor</th><th>Ubicación</th><th>RUA</th><th>Lote</th><th>Cultivo</th><th>Etapa</th><th>Causa</th>
                                <th>ha afect.</th><th>Afectación</th><th>Pérdida est.</th>
                                <th>GPS</th><th>Fotos</th><th>Notas</th><th v-if="puedeEliminar"><span class="sr-only">Eliminar</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in filtrados" :key="r.id">
                                <td>{{ r.fecha }}</td>
                                <td>{{ r.registradoPor || '—' }}</td>
                                <td>{{ r.tecnico }}</td>
                                <td>{{ ubicacion(r) }}</td>
                                <td>{{ r.agricultorRua ? `Sí (${r.codigoRua || '—'})` : 'No' }}</td>
                                <td>{{ r.lote }}</td>
                                <td><div class="cellcrop"><span class="record-indicator"><SparklesIcon class="w-4 h-4" /></span>{{ cultivos(r) }}</div></td>
                                <td>{{ r.etapa || '—' }}</td>
                                <td>{{ causa(r) }}</td>
                                <td><b>{{ r.areaAfectada }}</b></td>
                                <td><span class="badge" :class="nivelClase(r)">{{ nivel(r) }}</span><small>{{ r.danoPorc }}% de daño</small></td>
                                <td>{{ r.perdidaEstimada ? `${r.perdidaEstimada} ${r.unidad}` : '—' }}</td>
                                <td class="font-mono text-[11px]">{{ r.gps || '—' }}</td>
                                <td>{{ r.fotos?.length || '—' }}</td>
                                <td class="max-w-[220px] truncate" :title="r.notas">{{ r.notas || '—' }}</td>
                                <td v-if="puedeEliminar">
                                    <button type="button" class="iconbtn" title="Eliminar" @click="eliminar(r)"><TrashIcon class="w-4 h-4" style="color: var(--red);" /></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="cards">
                    <article v-for="r in filtrados" :key="r.id" class="record-card">
                        <div class="card-scenic" :style="{ backgroundImage: `url(${fondo})` }">
                            <div class="flex items-center gap-2"><SparklesIcon class="w-4 h-4" /><b class="text-[12px]">{{ cultivos(r) }}</b></div>
                            <span class="badge" :class="nivelClase(r)">{{ nivel(r) }}</span>
                        </div>
                        <div class="cardbody">
                            <small style="color: var(--muted);">{{ ubicacion(r) }}</small>
                            <h3>{{ r.lote }}</h3>
                            <span class="text-[12px]" style="color: var(--muted);">{{ r.tecnico }}<template v-if="r.registradoPor"> · registrado por {{ r.registradoPor }}</template></span>
                            <div class="card-metrics">
                                <div><strong>{{ r.areaAfectada }} <span class="text-[12px]">ha</span></strong><small>Área afectada</small></div>
                                <div><strong>{{ r.danoPorc }}%</strong><small>{{ causa(r) }}</small></div>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <small style="color: var(--muted);">{{ r.fecha }}</small>
                                <button v-if="puedeEliminar" type="button" class="linkbtn" style="color: var(--red);" @click="eliminar(r)">Eliminar</button>
                            </div>
                        </div>
                    </article>
                </div>

                <div class="list-footer">
                    <span>{{ filtrados.length }} de {{ registros.length }} registro(s)</span>
                    <button type="button" class="linkbtn" @click="limpiarFiltros">Restablecer filtros</button>
                </div>
            </article>
        </section>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import danoCultivoService from '../../services/danoCultivoService';
import authService from '../../services/authService';
import { toastError, confirmDialog } from '../../utils/alerts';
import fondo from '../../assets/images/Fondo.jpeg';
import {
    ArrowDownTrayIcon, TrashIcon, PlusIcon, MagnifyingGlassIcon, TableCellsIcon, Squares2X2Icon, ArrowRightIcon, SparklesIcon,
    DocumentTextIcon, MapPinIcon, UserGroupIcon, ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const rol = authService.user()?.rol;
const esTecnico = rol === 'tecnico';
const puedeEliminar = rol === 'admin' || rol === 'supervisor';

const registros = ref([]);
const loading = ref(true);

const totalHa = computed(() => registros.value.reduce((s, r) => s + (parseFloat(r.areaAfectada) || 0), 0));
const totalPerdida = computed(() => registros.value.reduce((s, r) => s + (parseFloat(r.perdidaEstimada) || 0), 0));

const busqueda = ref('');
const filtroCultivo = ref('');
const filtroNivel = ref('');
const vista = ref('tabla');
const NIVELES = ['Bajo', 'Medio', 'Alto', 'Pérdida total'];
const nivel = (r) => {
    const p = Number(r.danoPorc) || 0;
    return NIVELES[p >= 100 ? 3 : p >= 50 ? 2 : p >= 25 ? 1 : 0];
};
const nivelClase = (r) => ({ Bajo: '', Medio: 'amber', Alto: 'red', 'Pérdida total': 'purple' }[nivel(r)]);
const cultivosLista = computed(() => [...new Set(registros.value.map((r) => r.cultivo))].sort());
const filtrados = computed(() => {
    const q = busqueda.value.trim().toLowerCase();
    return registros.value.filter((r) =>
        (!q || [r.lote, r.tecnico, r.registradoPor, r.municipio, r.departamento, r.cultivo, r.causa].join(' ').toLowerCase().includes(q))
        && (!filtroCultivo.value || r.cultivo === filtroCultivo.value)
        && (!filtroNivel.value || nivel(r) === filtroNivel.value));
});
const limpiarFiltros = () => { busqueda.value = ''; filtroCultivo.value = ''; filtroNivel.value = ''; };
const dep = (r) => r.departamento || '';
const stats = computed(() => {
    const l = registros.value;
    return [
        { title: 'Registros', value: l.length, foot: `<b>${new Set(l.map(dep).filter(Boolean)).size} depto(s).</b> con reportes`, icon: DocumentTextIcon },
        { title: 'Superficie afectada', value: totalHa.value.toFixed(2), unit: 'ha', foot: 'Área acumulada reportada', icon: MapPinIcon },
        { title: 'Productores', value: new Set(l.map((r) => (r.tecnico || '').trim().toLowerCase())).size, foot: 'Personas en los registros', icon: UserGroupIcon },
        { title: 'Afectación alta o total', value: l.filter((r) => Number(r.danoPorc) >= 50).length, foot: 'Más del 50% de daño reportado', icon: ExclamationTriangleIcon },
    ];
});

const hoy = () => new Date().toISOString().slice(0, 10);
const ubicacion = (r) => [r.centroPoblado, r.municipio, r.departamento].filter(Boolean).join(', ') || '—';
const cultivos = (r) => [r.cultivo, ...(r.asociados || []).map((a) => a.cultivo)].filter(Boolean).join(', ');
const causa = (r) => (r.causa === 'Otra' && r.causaOtra ? `Otra: ${r.causaOtra}` : r.causa);

async function cargar() {
    registros.value = await danoCultivoService.list();
}

async function eliminar(r) {
    if (!(await confirmDialog('Este registro se eliminará.', { title: '¿Eliminar registro?', danger: true, confirmText: 'Eliminar' }))) return;
    try {
        await danoCultivoService.remove(r.id);
        await cargar();
    } catch (e) {
        toastError(e.message);
    }
}

function exportarCSV() {
    if (!filtrados.value.length) return toastError('No hay registros para exportar.');
    const filas = [
        ['REPORTE DE DAÑOS A CULTIVOS'],
        ['Generado', new Date().toLocaleString('es')],
        [],
        ['N°', 'Fecha', 'Registrado por', 'Productor', 'Departamento', 'Municipio', 'Centro poblado', 'Agricultor en RUA', 'Código RUA',
            'Lote/Parcela', 'Cultivo', 'Etapa fenológica', 'Cultivos asociados (cultivo | etapa | sup. lote | sup. afectada)', 'Causa del daño',
            'Sup. total lote (ha)', 'Sup. afectada (ha)', '% daño', 'Rend. esperado', 'Unidad rend.', 'Pérdida estimada',
            'Latitud, Longitud (precisión m)', 'N° fotos', 'Nombres de fotos', 'Notas'],
    ];
    filtrados.value.forEach((r, i) => {
        const aso = (r.asociados || []).map((a) => [a.cultivo, a.etapa, a.areaLote, a.areaAfectada].join(' | ')).join(' ;; ');
        filas.push([
            i + 1, r.fecha, r.registradoPor, r.tecnico, r.departamento, r.municipio, r.centroPoblado, r.agricultorRua ? 'Sí' : 'No', r.codigoRua,
            r.lote, r.cultivo, r.etapa, aso, causa(r), r.areaLote, r.areaAfectada, r.danoPorc,
            r.rendEsperado, r.unidad, r.perdidaEstimada, r.gps, (r.fotos || []).length, (r.fotos || []).map((f) => f.nombre).join(' ; '), r.notas,
        ]);
    });
    filas.push([], ['', '', '', '', '', '', '', '', '', '', '', '', '', 'TOTALES', '', totalHa.value.toFixed(2), '', '', '', totalPerdida.value.toFixed(2)]);

    const csv = '﻿' + filas.map((f) => f.map((c) => `"${String(c ?? '').replace(/"/g, '""')}"`).join(';')).join('\r\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
    a.download = `danos_cultivos_${hoy()}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(a.href);
}

onMounted(async () => {
    try {
        await cargar();
    } catch (e) {
        toastError(e.message);
    } finally {
        loading.value = false;
    }
});
</script>
