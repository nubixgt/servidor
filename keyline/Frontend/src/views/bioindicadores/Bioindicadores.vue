<template>
    <div>
        <div v-if="loading" class="py-12 text-center hint">Cargando…</div>

        <template v-else>
            <div class="grid3">
                <div class="stat-card glass">
                    <span class="s-icon">🌱</span>
                    <div class="s-label">Parcelas con bioindicadores</div>
                    <div class="s-value">{{ conBio.length }}</div>
                    <div class="s-sub">{{ pct }}% del total ({{ parcelas.length }} parcelas)</div>
                </div>
                <div class="stat-card glass">
                    <span class="s-icon">🪱</span>
                    <div class="s-label">Indicadores distintos registrados</div>
                    <div class="s-value">{{ resumen.topBioindicadores.length }}</div>
                    <div class="s-sub">Términos únicos reportados por técnicos</div>
                </div>
                <div class="stat-card glass">
                    <span class="s-icon">📏</span>
                    <div class="s-label">Profundidad promedio de suelo</div>
                    <div class="s-value">{{ resumen.diagnosticoFisico.profundidadProm.toFixed(1) }} cm</div>
                    <div class="s-sub">{{ resumen.diagnosticoFisico.muestras }} mediciones</div>
                </div>
            </div>

            <section class="panel glass">
                <div class="panel-head"><div><h3>Bioindicadores más frecuentes</h3><p>Lombrices, hongos, hormigas, hojarasca y otros signos de actividad biológica del suelo.</p></div></div>
                <div class="chip-list">
                    <span v-if="!resumen.topBioindicadores.length" class="hint">Aún no hay bioindicadores capturados por el equipo técnico.</span>
                    <span v-for="b in resumen.topBioindicadores" :key="b.nombre" class="chip">{{ b.nombre }} · {{ b.conteo }}</span>
                </div>
            </section>

            <section class="panel glass">
                <div class="panel-head"><div><h3>Parcelas con bioindicadores reportados</h3><p>Detalle por parcela de los signos biológicos observados en campo.</p></div></div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Parcela</th><th>Departamento</th><th>Bioindicadores</th><th>Técnico</th></tr>
                        </thead>
                        <tbody>
                            <tr v-if="!conBio.length"><td colspan="4" class="empty-state">Todavía no hay registros de bioindicadores.</td></tr>
                            <tr v-for="p in conBio" :key="p.id">
                                <td><strong>{{ p.nombreParcela }}</strong><br /><span class="hint">{{ p.codigo }}</span></td>
                                <td>{{ p.departamento }} / {{ p.municipio }}</td>
                                <td>
                                    <span v-for="(b, i) in p.bioindicadores.split(/[,;/]+/).map(s => s.trim()).filter(Boolean)" :key="i" class="chip" style="margin-right: 4px;">{{ b }}</span>
                                </td>
                                <td>{{ p.tecnicoNombre }}</td>
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
