<template>
    <div class="card">
        <div class="ch">
            <div>
                <h2>Distribución territorial</h2>
                <div class="crumbs">
                    <button @click="filtros.quitar('dep')">Guatemala</button>
                    <template v-if="F.dep != null">› <template v-if="F.mun != null"><button @click="F.mun = null">{{ DEPS[F.dep] }}</button> › <span class="cur">{{ cube.MN[F.mun] }}</span></template><span v-else class="cur">{{ DEPS[F.dep] }}</span></template>
                </div>
            </div>
            <div class="tools">
                <label class="note" for="mapMetric">Visualizar por</label>
                <select id="mapMetric" v-model="ui.mapMetric" class="mini-sel">
                    <option v-for="[k, l] in METRICS" :key="k" :value="k">{{ l }}</option>
                </select>
            </div>
        </div>
        <div class="mapwrap">
            <div>
                <svg class="mapsvg" :viewBox="vbActual" role="img" :aria-label="`Mapa de Guatemala por departamento, coloreado por ${mlab}`" :style="big ? 'max-height:560px' : ''">
                    <path v-for="p in paths" :key="p.d" :d="p.p" :data-dep="p.d" :fill="`var(--seq${cls(vals[p.d])})`"
                        :class="F.dep === p.d ? 'sel' : F.dep != null ? 'fade' : ''" tabindex="0" :aria-label="`${DEPS[p.d]}: ${fmt(vals[p.d])} ${mlab}`"
                        @mousemove="tip($event, p.d)" @mouseleave="ocultar" @click="elegir(p.d)" @keydown.enter="elegir(p.d)"></path>
                    <template v-if="centro">
                        <circle class="ping" :cx="centro[0]" :cy="centro[1]" r="3" />
                        <circle :cx="centro[0]" :cy="centro[1]" r="3.5" fill="#fff" />
                        <text class="mlabel" :x="centro[0]" :y="centro[1] - 10" text-anchor="middle">{{ DEPS[F.dep] }}</text>
                    </template>
                </svg>
                <div class="legend">
                    <span>{{ mlab }}:</span>
                    <i v-for="(c, i) in [1, 2, 3, 4, 5]" :key="c" :style="{ background: `var(--seq${c})` }"
                        :title="i === 0 ? '≤ ' + fmt(br[0]) : i === 4 ? '> ' + fmt(br[3]) : fmt(br[i - 1]) + ' – ' + fmt(br[i])"></i>
                    <span>{{ fmt(Math.min(...vals)) }} – {{ fmt(Math.max(...vals)) }}</span>
                </div>
            </div>
            <div>
                <div class="note" style="margin-bottom:8px"><b>{{ F.dep == null ? 'Departamentos' : 'Municipios de ' + DEPS[F.dep] }}</b> por {{ mlab.toLowerCase() }} · clic para {{ F.dep == null ? 'ver municipios' : 'filtrar el municipio' }}</div>
                <div class="rank">
                    <button v-for="(r, k) in ranking" :key="r.kind + r.i" :style="r.kind === 'mun' && F.mun === r.i ? 'background:var(--blue1)' : ''"
                        @click="r.kind === 'dep' ? filtros.setF('dep', r.i) : filtros.setF('mun', r.i)">
                        <span class="rn">{{ k + 1 }}</span>
                        <span class="rb"><span>{{ r.n }}</span><i :style="{ width: r.v / rmax * 100 + '%' }"></i></span>
                        <span class="rv">{{ fmt(r.v) }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
// Última vista del mapa: permite animar el zoom aunque el componente se vuelva a montar
let ULTIMO_VB = null;
</script>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useTooltip } from '../../composables/useTooltip';
import { useFiltrosStore } from '../../stores/filtros';
import { useTableroStore } from '../../stores/tablero';
import { useUiStore } from '../../stores/ui';
import { acc, quantBreaks } from '../../utils/cubo';
import { DEPS, METRICS, fmt } from '../../utils/formato';

defineProps({ big: { type: Boolean, default: false } });

const filtros = useFiltrosStore();
const tablero = useTableroStore();
const ui = useUiStore();
const { mostrar, ocultar } = useTooltip();
const F = filtros.f;
const RM = matchMedia('(prefers-reduced-motion: reduce)');

const cube = computed(() => tablero.cube);
const geo = tablero.geo;
const met = computed(() => ui.mapMetric);
const mlab = computed(() => METRICS.find((m) => m[0] === met.value)[1]);
const vals = computed(() => tablero.ag.geoDep.map((a) => a[met.value]));
const br = computed(() => quantBreaks(vals.value));
const cls = (v) => (v <= br.value[0] ? 1 : v <= br.value[1] ? 2 : v <= br.value[2] ? 3 : v <= br.value[3] ? 4 : 5);

const paths = Object.entries(geo.paths).map(([code, p]) => ({ d: +code - 1, p }));

const ranking = computed(() => {
    const c = cube.value;
    let items;
    if (F.dep == null) {
        items = DEPS.map((d, i) => ({ i, n: d, v: vals.value[i], kind: 'dep' }));
    } else {
        items = c.MUNI_BY_DEP[F.dep].map((m) => ({ i: m, n: c.MN[m], v: (tablero.ag.geoMun.get(m) || acc())[met.value], kind: 'mun' }));
    }
    if (c.MU.length < 340) items = items.filter((r) => r.v > 0 || (r.kind === 'dep' && c.MUNI_BY_DEP[r.i].length));
    return items.sort((a, b) => b.v - a.v);
});
const rmax = computed(() => Math.max(1, ...ranking.value.map((r) => r.v)));

// ---- zoom: el viewBox se interpola entre el departamento anterior y el nuevo
const objetivo = computed(() => {
    if (F.dep != null) {
        const b = tablero.bbox[F.dep + 1];
        const pad = Math.max(b[2] - b[0], b[3] - b[1]) * 0.35;
        return [b[0] - pad, b[1] - pad, b[2] - b[0] + 2 * pad, b[3] - b[1] + 2 * pad];
    }
    return [0, 0, geo.W, geo.H];
});
const centro = computed(() => {
    if (F.dep == null) return null;
    const b = tablero.bbox[F.dep + 1];
    return [(b[0] + b[2]) / 2, (b[1] + b[3]) / 2];
});

const vbActual = ref((ULTIMO_VB || objetivo.value).join(' '));
let raf = 0;
function animar(hasta) {
    cancelAnimationFrame(raf);
    const desde = ULTIMO_VB;
    ULTIMO_VB = hasta;
    if (!desde || RM.matches || desde.join() === hasta.join()) {
        vbActual.value = hasta.join(' ');
        return;
    }
    const t0 = performance.now();
    const paso = (t) => {
        const k = Math.min(1, (t - t0) / 550), e = k < 0.5 ? 4 * k * k * k : 1 - Math.pow(-2 * k + 2, 3) / 2;
        vbActual.value = desde.map((f, j) => f + (hasta[j] - f) * e).join(' ');
        if (k < 1) raf = requestAnimationFrame(paso);
    };
    raf = requestAnimationFrame(paso);
}
watch(objetivo, animar, { immediate: true });
onBeforeUnmount(() => cancelAnimationFrame(raf));

function tip(e, d) {
    const a = tablero.ag.geoDep[d];
    mostrar({
        titulo: DEPS[d],
        filas: [['Personas', a.p], ['Solicitudes (registros)', a.c], ['En nómina', a.nom], ['Pendientes', a.tram], ['Suspendidos', a.susp], ['Fallecidos', a.fall], ['Visitas positivas', a.vp]]
            .map(([l, v]) => ({ l, v: fmt(v) })),
        pie: 'Clic para ver municipios'
    }, e.clientX, e.clientY);
}

function elegir(d) {
    ocultar();
    filtros.setF('dep', d);
}
</script>
