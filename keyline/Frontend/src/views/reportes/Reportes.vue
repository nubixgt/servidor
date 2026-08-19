<template>
    <div>
        <div v-if="loading" class="py-12 text-center hint">Cargando…</div>

        <template v-else>
            <section class="panel glass">
                <div class="panel-head"><div><h3>Centro de exportación</h3><p>Descarga la información consolidada del proyecto para reportes externos.</p></div></div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-primary" @click="exportCsv">⬇️ Exportar parcelas (CSV)</button>
                    <button class="btn btn-secondary" @click="exportJson">⬇️ Exportar indicadores (JSON)</button>
                </div>
            </section>

            <section class="panel glass">
                <div class="panel-head"><div><h3>Indicadores consolidados</h3><p>Resumen ejecutivo listo para copiar en informes.</p></div></div>
                <div class="grid3">
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

            <section class="panel glass">
                <div class="panel-head"><div><h3>Ranking por departamento</h3><p>Base para reportes de avance territorial.</p></div></div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Departamento</th><th>Parcelas</th><th>Área (ha)</th><th>Implementadas</th></tr>
                        </thead>
                        <tbody>
                            <tr v-if="!resumen.porDepartamento.length"><td colspan="4" class="empty-state">Sin datos aún.</td></tr>
                            <tr v-for="d in resumen.porDepartamento" :key="d.departamento">
                                <td><strong>{{ d.departamento }}</strong></td>
                                <td>{{ d.cantidad }}</td>
                                <td>{{ fmtNum(d.area) }}</td>
                                <td>{{ d.implementadas }}</td>
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
        return h('div', { class: 'traffic-card' }, [
            h('div', { class: 's-label' }, this.label),
            h('div', { class: 'mini-value', style: 'margin-top: 6px;' }, String(this.value)),
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
