<template>
    <div>
        <div class="mx-auto max-w-[1480px] px-3.5 sm:px-6 py-4 sm:py-6 pb-10">
            <header class="mb-4">
                <h1 class="text-[28px] sm:text-[34px] leading-tight font-extrabold" style="color: var(--green-dark);">Registros</h1>
                <p class="text-[13px] sm:text-sm" style="color: var(--navy);">{{ esTecnico ? 'Los registros que has realizado' : 'Registros realizados por cada técnico' }}</p>
            </header>

            <section class="glass p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <span class="icon-badge" style="background: var(--green-dark);"><TableCellsIcon class="w-6 h-6" /></span>
                        <div>
                            <h2 class="section-title">Registros guardados</h2>
                            <p class="section-desc">
                                {{ registros.length }} registro(s) · {{ totalHa.toFixed(2) }} ha afectadas · Pérdida estimada acumulada {{ totalPerdida.toFixed(2) }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <router-link to="/registros/crear" class="btn btn-outline btn-sm"><PlusIcon class="w-5 h-5" /> Crear registro</router-link>
                        <button type="button" class="btn btn-primary btn-sm" @click="exportarCSV"><ArrowDownTrayIcon class="w-5 h-5" /> Exportar CSV</button>
                    </div>
                </div>

                <div v-if="loading" class="py-10 text-center text-sm" style="color: var(--muted);">Cargando…</div>
                <div v-else-if="!registros.length" class="py-10 text-center text-sm" style="color: var(--muted);">Aún no hay registros guardados.</div>
                <div v-else class="overflow-x-auto">
                    <table class="tabla w-full">
                        <thead>
                            <tr>
                                <th>Fecha</th><th>Técnico</th><th>Productor</th><th>Ubicación</th><th>RUA</th><th>Lote</th><th>Cultivo</th><th>Etapa</th><th>Causa</th>
                                <th>ha afect.</th><th>% daño</th><th>Pérdida est.</th>
                                <th>GPS</th><th>Fotos</th><th>Notas</th><th v-if="puedeEliminar"><span class="sr-only">Eliminar</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in registros" :key="r.id">
                                <td class="whitespace-nowrap">{{ r.fecha }}</td>
                                <td>{{ r.registradoPor || '—' }}</td>
                                <td>{{ r.tecnico }}</td>
                                <td>{{ ubicacion(r) }}</td>
                                <td class="whitespace-nowrap">{{ r.agricultorRua ? `Sí (${r.codigoRua || '—'})` : 'No' }}</td>
                                <td>{{ r.lote }}</td>
                                <td>{{ cultivos(r) }}</td>
                                <td>{{ r.etapa || '—' }}</td>
                                <td>{{ causa(r) }}</td>
                                <td>{{ r.areaAfectada }}</td>
                                <td>{{ r.danoPorc }}%</td>
                                <td class="whitespace-nowrap">{{ r.perdidaEstimada ? `${r.perdidaEstimada} ${r.unidad}` : '—' }}</td>
                                <td class="whitespace-nowrap font-mono text-[11px]">{{ r.gps || '—' }}</td>
                                <td>{{ r.fotos?.length || '—' }}</td>
                                <td class="max-w-[220px] truncate" :title="r.notas">{{ r.notas || '—' }}</td>
                                <td v-if="puedeEliminar">
                                    <button type="button" class="p-2 rounded-lg hover:bg-red-50" title="Eliminar" @click="eliminar(r)">
                                        <TrashIcon class="w-5 h-5" style="color: var(--red);" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import danoCultivoService from '../../services/danoCultivoService';
import authService from '../../services/authService';
import { toastError, confirmDialog } from '../../utils/alerts';
import { TableCellsIcon, ArrowDownTrayIcon, TrashIcon, PlusIcon } from '@heroicons/vue/24/outline';

const rol = authService.user()?.rol;
const esTecnico = rol === 'tecnico';
const puedeEliminar = rol === 'admin' || rol === 'supervisor';

const registros = ref([]);
const loading = ref(true);

const totalHa = computed(() => registros.value.reduce((s, r) => s + (parseFloat(r.areaAfectada) || 0), 0));
const totalPerdida = computed(() => registros.value.reduce((s, r) => s + (parseFloat(r.perdidaEstimada) || 0), 0));

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
    if (!registros.value.length) return toastError('No hay registros para exportar.');
    const filas = [
        ['REPORTE DE DAÑOS A CULTIVOS'],
        ['Generado', new Date().toLocaleString('es')],
        [],
        ['N°', 'Fecha', 'Registrado por', 'Productor', 'Departamento', 'Municipio', 'Centro poblado', 'Agricultor en RUA', 'Código RUA',
            'Lote/Parcela', 'Cultivo', 'Etapa fenológica', 'Cultivos asociados (cultivo | etapa | sup. lote | sup. afectada)', 'Causa del daño',
            'Sup. total lote (ha)', 'Sup. afectada (ha)', '% daño', 'Rend. esperado', 'Unidad rend.', 'Pérdida estimada',
            'Latitud, Longitud (precisión m)', 'N° fotos', 'Nombres de fotos', 'Notas'],
    ];
    registros.value.forEach((r, i) => {
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

<style scoped>
.tabla thead th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #7a928b; font-weight: 600; padding: 12px 13px; border-bottom: 1px solid #d8e6e4; background: #f0f6f2; white-space: nowrap; }
.tabla tbody td { padding: 15px 13px; font-size: 12px; color: #0b3446; border-bottom: 1px solid #e0eae4; }
.tabla tbody tr:hover { background: rgba(238,247,241,.43); }
.tabla tbody tr:last-child td { border-bottom: none; }
</style>
