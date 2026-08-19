<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Bioindicadores</h1>
            <p class="text-sm text-slate-500">Salud biológica del suelo reportada en campo.</p>
        </div>

        <div v-if="loading" class="py-12 text-center text-slate-400">Cargando…</div>

        <template v-else>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <span class="text-2xl">🌱</span>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide mt-2">Parcelas con bioindicadores</div>
                    <div class="text-2xl font-bold mt-1">{{ conBio.length }}</div>
                    <div class="text-xs text-slate-400 mt-1">{{ pct }}% del total ({{ parcelas.length }} parcelas)</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <span class="text-2xl">🪱</span>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide mt-2">Indicadores distintos registrados</div>
                    <div class="text-2xl font-bold mt-1">{{ resumen.topBioindicadores.length }}</div>
                    <div class="text-xs text-slate-400 mt-1">Términos únicos reportados por técnicos</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <span class="text-2xl">📏</span>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide mt-2">Profundidad promedio de suelo</div>
                    <div class="text-2xl font-bold mt-1">{{ resumen.diagnosticoFisico.profundidadProm.toFixed(1) }} cm</div>
                    <div class="text-xs text-slate-400 mt-1">{{ resumen.diagnosticoFisico.muestras }} mediciones</div>
                </div>
            </div>

            <section class="bg-white rounded-lg shadow p-5 mb-6">
                <h3 class="text-lg font-bold">Bioindicadores más frecuentes</h3>
                <p class="text-sm text-slate-500">Lombrices, hongos, hormigas, hojarasca y otros signos de actividad biológica del suelo.</p>
                <div class="flex flex-wrap gap-2 mt-4">
                    <span v-if="!resumen.topBioindicadores.length" class="text-sm text-slate-400">Aún no hay bioindicadores capturados por el equipo técnico.</span>
                    <span v-for="b in resumen.topBioindicadores" :key="b.nombre" class="tag bg-slate-100 text-slate-600">{{ b.nombre }} · {{ b.conteo }}</span>
                </div>
            </section>

            <section class="bg-white rounded-lg shadow p-5">
                <h3 class="text-lg font-bold">Parcelas con bioindicadores reportados</h3>
                <p class="text-sm text-slate-500">Detalle por parcela de los signos biológicos observados en campo.</p>
                <div class="overflow-x-auto mt-4">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                            <tr><th class="p-2 text-left">Parcela</th><th class="p-2 text-left">Departamento</th><th class="p-2 text-left">Bioindicadores</th><th class="p-2 text-left">Técnico</th></tr>
                        </thead>
                        <tbody>
                            <tr v-if="!conBio.length"><td colspan="4" class="p-4 text-center text-slate-400">Todavía no hay registros de bioindicadores.</td></tr>
                            <tr v-for="p in conBio" :key="p.id" class="border-t border-slate-100">
                                <td class="p-2"><strong>{{ p.nombreParcela }}</strong><br /><span class="text-xs text-slate-400">{{ p.codigo }}</span></td>
                                <td class="p-2">{{ p.departamento }} / {{ p.municipio }}</td>
                                <td class="p-2">
                                    <span v-for="(b, i) in p.bioindicadores.split(/[,;/]+/).map(s => s.trim()).filter(Boolean)" :key="i" class="tag bg-slate-100 text-slate-600 mr-1">{{ b }}</span>
                                </td>
                                <td class="p-2">{{ p.tecnicoNombre }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </template>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import parcelaService from '../../services/parcelaService';
import dashboardService from '../../services/dashboardService';

const loading = ref(true);
const parcelas = ref([]);
const resumen = ref({ topBioindicadores: [], diagnosticoFisico: { profundidadProm: 0, muestras: 0 } });

const conBio = computed(() => parcelas.value.filter((p) => (p.bioindicadores || '').trim().length > 0));
const pct = computed(() => parcelas.value.length ? Math.round((conBio.value.length / parcelas.value.length) * 100) : 0);

(async () => {
    try {
        const [pRes, rRes] = await Promise.all([parcelaService.listar(), dashboardService.resumen()]);
        parcelas.value = pRes.data.parcelas;
        resumen.value = rRes.data;
    } finally {
        loading.value = false;
    }
})();
</script>

<style scoped>
.tag { @apply text-xs font-semibold px-2 py-1 rounded-full inline-block; }
</style>
