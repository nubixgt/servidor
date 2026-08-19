<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Reportes y análisis</h1>
            <p class="text-sm text-slate-500">Centro de exportación e indicadores del proyecto.</p>
        </div>

        <div v-if="loading" class="py-12 text-center text-slate-400">Cargando…</div>

        <template v-else>
            <section class="bg-white rounded-lg shadow p-5 mb-6">
                <h3 class="text-lg font-bold">Centro de exportación</h3>
                <p class="text-sm text-slate-500 mb-4">Descarga la información consolidada del proyecto para reportes externos.</p>
                <div class="flex gap-2">
                    <button class="btn-primary" @click="exportCsv">⬇️ Exportar parcelas (CSV)</button>
                    <button class="btn-secondary" @click="exportJson">⬇️ Exportar indicadores (JSON)</button>
                </div>
            </section>

            <section class="bg-white rounded-lg shadow p-5 mb-6">
                <h3 class="text-lg font-bold">Indicadores consolidados</h3>
                <p class="text-sm text-slate-500 mb-4">Resumen ejecutivo listo para copiar en informes.</p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <Indicador label="Parcelas registradas" :value="resumen.totales.parcelas" />
                    <Indicador label="Área acumulada" :value="`${fmtNum(resumen.totales.areaHa)} ha`" />
                    <Indicador label="Cobertura territorial" :value="`${resumen.totales.coberturaPct}% (${resumen.totales.departamentos}/${resumen.totales.metaDepartamentos})`" />
                    <Indicador label="Municipios alcanzados" :value="resumen.totales.municipios" />
                    <Indicador label="Familias beneficiadas" :value="resumen.totales.familiasBeneficiadas" />
                    <Indicador label="Parcelas implementadas" :value="resumen.totales.implementadas" />
                    <Indicador label="Validadas / pendientes" :value="`${resumen.totales.validadas} / ${resumen.totales.pendientesValidacion}`" />
                    <Indicador label="Profundidad prom. de suelo" :value="`${resumen.diagnosticoFisico.profundidadProm.toFixed(1)} cm`" />
                    <Indicador label="Parcelas con talpetate" :value="resumen.diagnosticoFisico.conTalpetate" />
                </div>
            </section>

            <section class="bg-white rounded-lg shadow p-5">
                <h3 class="text-lg font-bold">Ranking por departamento</h3>
                <p class="text-sm text-slate-500 mb-4">Base para reportes de avance territorial.</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                            <tr><th class="p-2 text-left">Departamento</th><th class="p-2 text-left">Parcelas</th><th class="p-2 text-left">Área (ha)</th><th class="p-2 text-left">Implementadas</th></tr>
                        </thead>
                        <tbody>
                            <tr v-if="!resumen.porDepartamento.length"><td colspan="4" class="p-4 text-center text-slate-400">Sin datos aún.</td></tr>
                            <tr v-for="d in resumen.porDepartamento" :key="d.departamento" class="border-t border-slate-100">
                                <td class="p-2 font-semibold">{{ d.departamento }}</td>
                                <td class="p-2">{{ d.cantidad }}</td>
                                <td class="p-2">{{ fmtNum(d.area) }}</td>
                                <td class="p-2">{{ d.implementadas }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </template>
    </div>
</template>

<script setup>
import { ref, h, defineComponent } from 'vue';
import parcelaService from '../../services/parcelaService';
import dashboardService from '../../services/dashboardService';
import { alertError } from '../../utils/alerts';

const loading = ref(true);
const parcelas = ref([]);
const resumen = ref(null);

const Indicador = defineComponent({
    props: { label: String, value: [String, Number] },
    render() {
        return h('div', { class: 'bg-slate-50 rounded-lg p-3' }, [
            h('div', { class: 'text-xs font-semibold text-slate-500 uppercase' }, this.label),
            h('div', { class: 'text-xl font-bold mt-1' }, String(this.value)),
        ]);
    },
});

function fmtNum(n) {
    return Number(n || 0).toLocaleString('es-GT', { maximumFractionDigits: 1 });
}

(async () => {
    try {
        const [pRes, rRes] = await Promise.all([parcelaService.listar(), dashboardService.resumen()]);
        parcelas.value = pRes.data.parcelas;
        resumen.value = rRes.data;
    } finally {
        loading.value = false;
    }
})();

function exportCsv() {
    if (!parcelas.value.length) { alertError('No hay datos para exportar.'); return; }
    const cols = ['codigo', 'nombreParcela', 'departamento', 'municipio', 'comunidad', 'propietario', 'telefono', 'areaHa', 'estado', 'estadoValidacion', 'usoActual', 'tipoSuelo', 'pendiente', 'altitud', 'agua', 'fuenteAgua', 'riesgoErosion', 'profundidadSuelo', 'talpetate', 'encharca', 'bioindicadores', 'lluviaAnual', 'lluviaFuente', 'intervenciones', 'especiesReforestacion', 'observaciones', 'tecnicoNombre', 'fechaRegistro', 'latitud', 'longitud'];
    const esc = (v) => { const s = String(v ?? ''); return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s; };
    const rows = [cols.join(',')].concat(parcelas.value.map((p) => cols.map((c) => esc(p[c])).join(',')));
    downloadBlob(new Blob(['﻿' + rows.join('\n')], { type: 'text/csv;charset=utf-8;' }), `parcelas_keyline_${new Date().toISOString().slice(0, 10)}.csv`);
}

function exportJson() {
    downloadBlob(new Blob([JSON.stringify(resumen.value, null, 2)], { type: 'application/json' }), `indicadores_keyline_${new Date().toISOString().slice(0, 10)}.json`);
}

function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<style scoped>
.btn-primary { @apply px-4 py-2 bg-primary-500 text-white rounded-md text-sm font-semibold hover:bg-primary-600; }
.btn-secondary { @apply px-4 py-2 bg-slate-100 text-slate-700 rounded-md text-sm font-semibold hover:bg-slate-200; }
</style>
