<template>
    <VistaHeader eyebrow="Control" titulo="Calidad de datos" desc="Los registros con problemas no se eliminan: se marcan y se revisan desde el Expediente 360°." />
    <div class="qgrid">
        <div v-for="[t, d, v, c] in dims" :key="t" class="card qd">
            <div class="note" style="font-weight:700;text-transform:uppercase;letter-spacing:.08em">{{ t }}</div>
            <div class="qv">{{ pc(v, T) }}%</div>
            <div class="meter"><i :style="{ width: v / T * 100 + '%', background: c }"></i></div>
            <div class="note">{{ d }} · {{ fmt(v) }} de {{ fmt(T) }}</div>
        </div>
    </div>
    <div class="card">
        <div class="ch">
            <div>
                <h2>Hallazgos para revisión</h2>
                <p>{{ tablero.ambito }} · sobre {{ fmt(T) }} registros</p>
            </div>
        </div>
        <div class="tw">
            <table>
                <thead><tr><th>Regla</th><th>Dimensión</th><th class="n">Registros</th><th class="n">%</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="[k, l, n, dm] in rows" :key="l">
                        <td class="wrap">{{ l }}</td><td>{{ dm }}</td>
                        <td class="n"><b>{{ n == null ? '…' : fmt(n) }}</b></td>
                        <td class="n">{{ n == null ? '' : pc(n, T, 2) + '%' }}</td>
                        <td><button v-if="k" class="btn" @click="revisar(k)">Revisar casos</button><span v-else class="note">Nacional</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-if="imp" class="note" style="margin:12px 0 0">
            Última importación: <b>{{ imp.archivo }}</b> ({{ fts(imp.finalizado_en) }}) · advertencias: {{ advertencias || 'ninguna' }}.
        </p>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import VistaHeader from '../../components/common/VistaHeader.vue';
import catalogoService from '../../services/catalogoService';
import { useTableroStore } from '../../stores/tablero';
import { useUiStore } from '../../stores/ui';
import { fmt, fts, pc } from '../../utils/formato';

const router = useRouter();
const tablero = useTableroStore();
const ui = useUiStore();
const extra = ref(null);

const T = computed(() => tablero.ag.T.c || 1);
const a = computed(() => tablero.ag.al);

const dims = computed(() => [
    ['Identidad', 'CUI válido de 13 dígitos', T.value - a.value.qd, 'var(--blue5)'],
    ['Unicidad', 'Registros sin DPI repetido', T.value - a.value.qu, 'var(--blue5)'],
    ['Coherencia temporal', 'Sin fechas inconsistentes', T.value - a.value.qf, 'var(--ok)'],
    ['Completitud de contacto', 'Con teléfono registrado', T.value - a.value.qm, 'var(--warnf)'],
]);

const rows = computed(() => [
    ['qd', 'Documento sin CUI válido (cédula antigua o dígito verificador incorrecto)', a.value.qd, 'Identidad'],
    ['qu', 'DPI registrado en más de un expediente', a.value.qu, 'Unicidad'],
    ['qx', 'Número de expediente duplicado', a.value.qx, 'Unicidad'],
    [null, 'Personas posiblemente duplicadas (mismo nombre y fecha de nacimiento, distinto documento)', extra.value ? extra.value.personas_posible_duplicado : null, 'Unicidad'],
    ['qf', 'Fechas inconsistentes', a.value.qf, 'Coherencia'],
    ['qa', 'Fallecido y activo en nómina', a.value.qa, 'Coherencia'],
    ['qs', 'Suspendido y activo en nómina', a.value.qs, 'Coherencia'],
    ['qe', 'Edad imposible o fuera de rango', a.value.qe, 'Validez'],
    ['qm', 'Sin teléfono', a.value.qm, 'Completitud'],
]);

const imp = computed(() => extra.value && extra.value.ultima_importacion);
const advertencias = computed(() => Object.entries(imp.value?.reporte?.advertencias || {})
    .map(([k, v]) => `${k.replace(/_/g, ' ').toLowerCase()} ${fmt(v)}`).join(' · '));

function revisar(regla) {
    ui.bRule = regla;
    router.push('/beneficiarios');
}

onMounted(async () => {
    try { extra.value = await catalogoService.calidad(); } catch (e) { /* los indicadores del cubo se muestran igual */ }
});
</script>
