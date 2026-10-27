<template>
    <section class="kpis" aria-label="Indicadores principales">
        <div class="hero">
            <span class="orb"></span><img class="wm" :src="emblema" alt="">
            <div class="kt">Personas únicas<span class="ki" style="width:32px;height:32px;border-radius:10px;display:grid;place-items:center;background:var(--blue1);color:var(--blue7)"><Icono name="users" /></span></div>
            <CountUp class="big" tag="div" :value="T.p" ckey="hero" />
            <div class="sub">Identificadas en <b>{{ fmt(T.c) }}</b> registros · <b>{{ nDep }}</b> departamentos · <b>{{ fmt(nMun) }}</b> municipios · {{ tablero.ambito }}</div>
            <div>
                <div class="note" style="margin-bottom:6px">Composición por estado actual · clic para filtrar</div>
                <div class="stackbar">
                    <button v-for="i in order.filter((i) => ag.byE[i])" :key="i" :aria-label="`${EST[i]}: ${fmt(ag.byE[i])}`"
                        :style="{ flexGrow: ag.byE[i], background: stc(i), opacity: F.e != null && F.e !== i ? 0.3 : null }"
                        @click="filtros.setF('e', i)" @mousemove="tip($event, i)" @mouseleave="ocultar"></button>
                </div>
            </div>
            <div class="stackleg">
                <span v-for="i in order.slice(0, 6)" :key="i"><i :style="{ background: stc(i) }"></i>{{ EST[i] }}<b>{{ pc(ag.byE[i], tot, 0) }}%</b></span>
            </div>
        </div>

        <button v-for="k in K" :key="k.k" class="kpi" :class="{ on: on[k.k] }" :style="{ '--tone': k.tone, cursor: k.click ? null : 'default' }"
            :tabindex="k.click ? null : -1" @click="k.click && k.click()">
            <span class="kt">{{ k.t }}<span class="ki"><Icono :name="k.i" :size="15" /></span></span>
            <CountUp class="kv" :value="k.v" :ckey="k.k" />
            <span class="kd">{{ k.d }} <span v-if="k.delta" class="delta" :class="k.delta.cls">{{ k.delta.texto }}</span></span>
            <Spark :data="k.sp" :tone="k.tone" :gid="'sp' + k.k" />
        </button>

        <div class="kpi" style="cursor:default">
            <span class="kt">Cobertura activa<span class="ki" style="--tone:var(--ok)"><Icono name="pulse" :size="15" /></span></span>
            <div class="gauge">
                <Ring :p="T.nom / Math.max(1, T.c)" tone="var(--ok)" />
                <div>
                    <div class="gv">{{ pc(T.nom, T.c, 1) }}%</div>
                    <div class="kd">de las solicitudes reciben hoy el aporte</div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import CountUp from './CountUp.vue';
import Ring from './Ring.vue';
import Spark from './Spark.vue';
import Icono from '../common/Icono.vue';
import emblema from '../../assets/img/escudo-color.png';
import { useTooltip } from '../../composables/useTooltip';
import { useFiltrosStore } from '../../stores/filtros';
import { useTableroStore } from '../../stores/tablero';
import { seriesYear, ytd } from '../../utils/cubo';
import { ANIO, MESC, PERIODO, fmt, pc, stc } from '../../utils/formato';

const filtros = useFiltrosStore();
const tablero = useTableroStore();
const { mostrar, ocultar } = useTooltip();
const F = filtros.f;

const ag = computed(() => tablero.ag);
const T = computed(() => ag.value.T);
const EST = computed(() => tablero.cube.EST);
const tot = computed(() => ag.value.byE.reduce((a, b) => a + b, 0) || 1);
const order = computed(() => EST.value.map((e, i) => i).sort((a, b) => ag.value.byE[b] - ag.value.byE[a]));
const nDep = computed(() => ag.value.byDep.filter((a) => a.c).length);
const nMun = computed(() => ag.value.byMun.size);

/** Variación porcentual contra el mismo período del año anterior. */
function delta(cur, prev, good = 'up') {
    if (!prev) return null;
    const d = (cur - prev) / prev * 100;
    const up = d >= 0;
    const cls = (good === 'nt' || Math.abs(d) < 0.5) ? 'nt' : (up === (good === 'up') ? 'up' : (good === 'warn' ? 'warnup' : 'dn'));
    return { cls, texto: `${up ? '▲' : '▼'} ${Math.abs(d).toFixed(1)}%` };
}

const K = computed(() => {
    const S = tablero.series, MDEP = tablero.cube.MDEP;
    const Y = seriesYear(S, F, MDEP, ['sol', 'ing', 'vpos', 'vneg', 'susp', 'fall']);
    let acumulado = 0;
    const cum = Y.sol.map((v) => (acumulado += v));
    const y = (k, anio) => ytd(S, F, k, anio, MESC);
    const [s26, s25, i26, i25, vp26, vp25, vn26, vn25, f26, f25] = [
        y('sol', ANIO), y('sol', ANIO - 1), y('ing', ANIO), y('ing', ANIO - 1), y('vpos', ANIO), y('vpos', ANIO - 1),
        y('vneg', ANIO), y('vneg', ANIO - 1), y('fall', ANIO), y('fall', ANIO - 1)];
    const t = T.value;
    return [
        { k: 'nom', t: 'En nómina', i: 'wallet', tone: 'var(--ok)', v: t.nom, d: `Ingresos ${PERIODO} ${ANIO}: ${fmt(i26)}`, delta: delta(i26, i25), sp: Y.ing, click: () => filtros.setF('n', 1) },
        { k: 'sol', t: 'Solicitudes', i: 'file', tone: 'var(--s1)', v: t.c, d: `${ANIO} ${PERIODO}: ${fmt(s26)}`, delta: delta(s26, s25, 'nt'), sp: Y.sol, click: () => filtros.setF('e', 1) },
        { k: 'vp', t: 'Visitas positivas', i: 'check', tone: 'var(--s3)', v: t.vp, d: `${ANIO} ${PERIODO}: ${fmt(vp26)}`, delta: delta(vp26, vp25), sp: Y.vpos, click: () => filtros.setF('v', 1) },
        { k: 'vn', t: 'Visitas negativas', i: 'x', tone: 'var(--s2)', v: t.vn, d: `${ANIO} ${PERIODO}: ${fmt(vn26)}`, delta: delta(vn26, vn25, 'dn'), sp: Y.vneg, click: () => filtros.setF('v', 2) },
        { k: 'c', t: 'Total de registros', i: 'db', tone: 'var(--s7)', v: t.c, d: `${fmt(t.c - t.p)} de personas repetidas`, delta: null, sp: cum, click: null },
        { k: 'susp', t: 'Suspendidos', i: 'pause', tone: 'var(--st4)', v: t.susp, d: `${fmt(ag.value.byE[3])} bloqueados además`, delta: null, sp: Y.susp, click: () => filtros.setF('e', 4) },
        { k: 'fall', t: 'Fallecidos', i: 'minus', tone: 'var(--dead)', v: t.fall, d: `${ANIO} ${PERIODO}: ${fmt(f26)}`, delta: delta(f26, f25, 'dn'), sp: Y.fall, click: () => filtros.setF('f', 1) },
    ];
});

const on = computed(() => ({ nom: F.n === 1, sol: F.e === 1, vp: F.v === 1, vn: F.v === 2, susp: F.e === 4, fall: F.f === 1 }));

function tip(e, i) {
    mostrar({
        titulo: EST.value[i],
        filas: [{ l: 'Registros', v: fmt(ag.value.byE[i]) }, { l: 'Participación', v: pc(ag.value.byE[i], tot.value) + '%' }]
    }, e.clientX, e.clientY);
}
</script>
