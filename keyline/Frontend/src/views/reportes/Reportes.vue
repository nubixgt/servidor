<template>
    <div>
        <div v-if="loading" class="py-12 text-center text-xs text-white/60">Cargando…</div>

        <template v-else>
            <div class="space-y-6 max-w-[1600px] mx-auto pb-4">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Reportes y análisis</h2>
                        <p class="text-xs sm:text-sm text-white/60 mt-0.5">Métricas consolidadas y centro de exportación del proyecto.</p>
                    </div>
                    <button
                        @click="exportCsv"
                        class="flex items-center space-x-2 px-4 py-2.5 bg-[#22c55e] hover:bg-[#16a34a] text-black rounded-xl text-xs font-semibold tracking-wider uppercase transition-all shadow-[0_0_20px_rgba(34,197,94,0.4)]"
                    >
                        <Download class="w-4 h-4" />
                        <span>Exportar reporte CSV</span>
                    </button>
                </div>

                <!-- KPI cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5 flex flex-col justify-between">
                        <div class="flex justify-between items-start">
                            <span class="text-[11px] font-bold tracking-wider text-white/60 uppercase">Parcelas registradas</span>
                            <div class="w-8 h-8 rounded-xl bg-black/30 border border-white/10 flex items-center justify-center text-[#4ade80]"><Layers class="w-4 h-4" /></div>
                        </div>
                        <div class="mt-3">
                            <div class="text-3xl font-bold text-white font-mono">{{ resumen.totales.parcelas }}</div>
                            <p class="text-xs text-white/80 mt-1">{{ fmtNum(resumen.totales.areaHa) }} ha acumuladas</p>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5 flex flex-col justify-between">
                        <div class="flex justify-between items-start">
                            <span class="text-[11px] font-bold tracking-wider text-white/60 uppercase">Cobertura territorial</span>
                            <div class="w-8 h-8 rounded-xl bg-black/30 border border-white/10 flex items-center justify-center text-[#38bdf8]"><MapPin class="w-4 h-4" /></div>
                        </div>
                        <div class="mt-3">
                            <div class="text-3xl font-bold text-white font-mono">{{ resumen.totales.coberturaPct }}%</div>
                            <p class="text-xs text-white/80 mt-1">{{ resumen.totales.departamentos }} de {{ resumen.totales.metaDepartamentos }} departamentos</p>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5 flex flex-col justify-between">
                        <div class="flex justify-between items-start">
                            <span class="text-[11px] font-bold tracking-wider text-white/60 uppercase">Parcelas implementadas</span>
                            <div class="w-8 h-8 rounded-xl bg-black/30 border border-white/10 flex items-center justify-center text-[#facc15]"><CheckCircle2 class="w-4 h-4" /></div>
                        </div>
                        <div class="mt-3">
                            <div class="text-3xl font-bold text-white font-mono">{{ resumen.totales.implementadas }}</div>
                            <p class="text-xs text-[#4ade80] font-semibold mt-1">{{ resumen.totales.validadas }} parcelas validadas</p>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5 flex flex-col justify-between">
                        <div class="flex justify-between items-start">
                            <span class="text-[11px] font-bold tracking-wider text-white/60 uppercase">Por revisar</span>
                            <div class="w-8 h-8 rounded-xl bg-black/30 border border-white/10 flex items-center justify-center text-[#f59e0b]"><FileText class="w-4 h-4" /></div>
                        </div>
                        <div class="mt-3">
                            <div class="text-3xl font-bold text-white font-mono">{{ pendientes.length }}</div>
                            <p class="text-xs text-white/80 mt-1">En cola de supervisión</p>
                        </div>
                    </div>
                </div>

                <!-- Pending validation queue -->
                <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-6">
                    <div class="flex justify-between items-center pb-4 border-b border-white/15">
                        <div>
                            <h3 class="text-base font-bold text-white tracking-tight">Cola de validación técnica</h3>
                            <p class="text-xs text-white/80 mt-0.5">Parcelas cargadas recientemente que requieren visto bueno de supervisor.</p>
                        </div>
                        <span class="text-xs px-3 py-1 bg-[#f59e0b]/20 text-[#fbbf24] border border-[#f59e0b]/30 rounded-full font-mono whitespace-nowrap">{{ pendientes.length }} pendientes</span>
                    </div>

                    <div class="divide-y divide-white/10 mt-2">
                        <div v-for="p in pendientes.slice(0, 8)" :key="p.id" class="py-3.5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 hover:bg-white/5 px-2 rounded-xl transition-colors">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-mono text-[#38bdf8] bg-black/40 px-2 py-0.5 rounded border border-white/10">{{ p.codigo }}</span>
                                    <h4 class="text-xs font-bold text-white">{{ p.nombreParcela }}</h4>
                                </div>
                                <p class="text-xs text-white/80">
                                    Técnico: <strong class="text-white">{{ p.tecnicoNombre }}</strong> · {{ p.municipio }}, {{ p.departamento }} ({{ p.areaHa }} ha)
                                </p>
                            </div>
                        </div>

                        <div v-if="!pendientes.length" class="py-8 text-center text-xs text-white/80">
                            <CheckCircle2 class="w-8 h-8 text-[#4ade80] mx-auto mb-2 opacity-80" />
                            <span>Todas las parcelas se encuentran validadas y al día.</span>
                        </div>
                    </div>

                    <div v-if="pendientes.length" class="pt-3 mt-2 border-t border-white/15 text-right">
                        <router-link :to="{ name: 'ParcelasList' }" class="text-xs text-[#22c55e] hover:text-[#4ade80] font-medium transition-colors">Ir a revisar parcelas →</router-link>
                    </div>
                </div>

                <!-- Charts & export -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-6 space-y-4">
                        <div class="flex justify-between items-center">
                            <h3 class="text-base font-bold text-white tracking-tight">Avance por departamento</h3>
                            <select v-model="selectedDept" class="bg-black/40 border border-white/15 text-xs text-white rounded-xl px-3 py-1.5 focus:outline-none">
                                <option v-for="d in resumen.porDepartamento" :key="d.departamento" :value="d.departamento" class="bg-black/90 text-white">{{ d.departamento }}</option>
                            </select>
                        </div>

                        <div v-if="selectedDeptData" class="space-y-4 pt-2">
                            <div>
                                <div class="flex justify-between text-xs text-white/80 mb-1">
                                    <span>Parcelas registradas</span>
                                    <span class="font-mono text-white font-bold">{{ selectedDeptData.cantidad }}</span>
                                </div>
                                <div class="w-full bg-black/40 h-2.5 rounded-full overflow-hidden border border-white/10">
                                    <div class="bg-[#38bdf8] h-full" :style="{ width: pct(selectedDeptData.cantidad, maxCantidad) + '%' }"></div>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between text-xs text-white/80 mb-1">
                                    <span>Implementadas</span>
                                    <span class="font-mono text-[#4ade80] font-bold">{{ selectedDeptData.implementadas }}</span>
                                </div>
                                <div class="w-full bg-black/40 h-2.5 rounded-full overflow-hidden border border-white/10">
                                    <div class="bg-[#22c55e] h-full" :style="{ width: pct(selectedDeptData.implementadas, selectedDeptData.cantidad) + '%' }"></div>
                                </div>
                            </div>

                            <p class="text-[11px] text-white/60">{{ fmtNum(selectedDeptData.area) }} ha registradas en {{ selectedDeptData.departamento }}.</p>
                        </div>
                        <div v-else class="text-xs text-white/60">Sin datos por departamento aún.</div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-6 flex flex-col justify-between">
                        <div>
                            <h3 class="text-base font-bold text-white tracking-tight">Centro de exportación</h3>
                            <p class="text-xs text-white/80 mt-0.5">Descarga la información consolidada del proyecto para reportes externos.</p>
                        </div>

                        <div class="p-4 bg-black/30 border border-white/10 rounded-xl my-4 text-xs space-y-1.5">
                            <p class="text-white font-medium flex items-center gap-1.5">
                                <FileSpreadsheet class="w-4 h-4 text-[#4ade80]" />
                                <span>Parcelas (CSV)</span>
                            </p>
                            <p class="text-white/60">Incluye ubicación, suelo, agua, bioindicadores e intervenciones de cada parcela.</p>
                        </div>

                        <div class="flex gap-2">
                            <button @click="exportCsv" class="flex-1 py-2.5 bg-white/10 hover:bg-[#22c55e] text-white rounded-xl text-xs font-bold transition-all border border-white/15 flex items-center justify-center gap-1.5">
                                <Download class="w-3.5 h-3.5" />
                                <span>CSV parcelas</span>
                            </button>
                            <button @click="exportJson" class="flex-1 py-2.5 bg-white/10 hover:bg-[#38bdf8] hover:text-black text-white rounded-xl text-xs font-bold transition-all border border-white/15 flex items-center justify-center gap-1.5">
                                <Download class="w-3.5 h-3.5" />
                                <span>JSON indicadores</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Indicadores consolidados -->
                <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-6">
                    <h3 class="text-base font-bold text-white tracking-tight">Indicadores consolidados</h3>
                    <p class="text-xs text-white/80 mt-0.5 mb-4">Resumen ejecutivo listo para copiar en informes.</p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <div v-for="ind in indicadores" :key="ind.label" class="bg-black/30 p-3 rounded-xl border border-white/10">
                            <span class="text-[10px] uppercase font-bold text-white/60 block">{{ ind.label }}</span>
                            <span class="text-lg font-bold text-white font-mono">{{ ind.value }}</span>
                        </div>
                    </div>
                </div>

                <!-- Ranking table -->
                <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-6">
                    <h3 class="text-base font-bold text-white tracking-tight">Ranking por departamento</h3>
                    <p class="text-xs text-white/80 mt-0.5 mb-4">Base para reportes de avance territorial.</p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="text-[10px] text-white/60 border-b border-white/15 uppercase tracking-wider font-semibold">
                                    <th class="pb-2 font-semibold">Departamento</th>
                                    <th class="pb-2 font-semibold">Parcelas</th>
                                    <th class="pb-2 font-semibold">Área (ha)</th>
                                    <th class="pb-2 font-semibold">Implementadas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                <tr v-if="!resumen.porDepartamento.length"><td colspan="4" class="py-6 text-center text-white/60">Sin datos aún.</td></tr>
                                <tr v-for="d in resumen.porDepartamento" :key="d.departamento" class="hover:bg-white/10 transition-colors">
                                    <td class="py-2.5 pr-2 font-semibold text-white">{{ d.departamento }}</td>
                                    <td class="py-2.5 px-2 text-white/80">{{ d.cantidad }}</td>
                                    <td class="py-2.5 px-2 text-white/80">{{ fmtNum(d.area) }}</td>
                                    <td class="py-2.5 pl-2 text-white/80">{{ d.implementadas }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import parcelaService from '../../services/parcelaService';
import dashboardService from '../../services/dashboardService';
import { alertError } from '../../utils/alerts';
import { Download, Layers, MapPin, CheckCircle2, FileText, FileSpreadsheet } from '@lucide/vue';

const loading = ref(true);
const parcelas = ref([]);
const resumen = ref(null);
const selectedDept = ref('');

function fmtNum(n) {
    return Number(n || 0).toLocaleString('es-GT', { maximumFractionDigits: 1 });
}

function pct(value, total) {
    if (!total) return 0;
    return Math.min(100, Math.round((value / total) * 100));
}

const pendientes = computed(() => parcelas.value.filter((p) => p.estadoValidacion === 'Pendiente de revisión'));

const maxCantidad = computed(() => resumen.value?.porDepartamento?.[0]?.cantidad || 1);

const selectedDeptData = computed(() => resumen.value?.porDepartamento?.find((d) => d.departamento === selectedDept.value) || resumen.value?.porDepartamento?.[0] || null);

const indicadores = computed(() => {
    if (!resumen.value) return [];
    const r = resumen.value;
    return [
        { label: 'Municipios alcanzados', value: r.totales.municipios },
        { label: 'Familias beneficiadas', value: r.totales.familiasBeneficiadas },
        { label: 'Validadas / pendientes', value: `${r.totales.validadas} / ${r.totales.pendientesValidacion}` },
        { label: 'Profundidad prom. de suelo', value: `${r.diagnosticoFisico.profundidadProm.toFixed(1)} cm` },
        { label: 'Parcelas con talpetate', value: r.diagnosticoFisico.conTalpetate },
        { label: 'Área acumulada', value: `${fmtNum(r.totales.areaHa)} ha` },
    ];
});

(async () => {
    try {
        const [pRes, rRes] = await Promise.all([parcelaService.listar(), dashboardService.resumen()]);
        parcelas.value = pRes.data.parcelas;
        resumen.value = rRes.data;
        selectedDept.value = rRes.data.porDepartamento?.[0]?.departamento || '';
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
