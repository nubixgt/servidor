<template>
    <div class="card">
        <div class="ch">
            <div>
                <h2>Flujo del programa</h2>
                <p>Grosor = expedientes que alcanzaron la etapa · en ámbar, expedientes en trámite detenidos · {{ tablero.ambito }}</p>
            </div>
        </div>
        <div class="stream">
            <svg :viewBox="`0 0 ${W} ${H}`" role="img" aria-label="Flujo del programa por etapa">
                <defs>
                    <linearGradient id="pipeG" x1="0" x2="1">
                        <stop offset="0" stop-color="var(--pipe1)" stop-opacity=".55" />
                        <stop offset="1" stop-color="var(--pipe2)" stop-opacity=".3" />
                    </linearGradient>
                </defs>
                <template v-for="(s, i) in st" :key="'d' + i">
                    <template v-if="s[2]">
                        <path :d="goteo(i)" :stroke="i === neck ? 'var(--crit)' : 'var(--warnf)'" :stroke-width="Math.max(3, s[2] / base * 170)" stroke-linecap="round" fill="none" opacity=".45" />
                        <path class="loss" :d="goteo(i)" :style="i === neck ? { stroke: 'var(--crit)' } : null" />
                    </template>
                </template>
                <path v-for="b in bandas" :key="b.i" class="band" :d="b.d" fill="url(#pipeG)" />
                <rect v-for="(x, i) in xs" :key="'n' + i" :x="x - 6" :y="mid - hs[i] / 2" width="12" :height="hs[i]" rx="4" fill="var(--pipe2)" />
                <path class="flowline" :d="`M${xs[0]},${mid} L${xs[4]},${mid}`" />
            </svg>
        </div>
        <div class="snodes">
            <div v-for="([l, v, wt, wl], i) in st" :key="i" class="snode" :class="{ neck: i === neck }">
                <span class="sl">{{ l }}</span>
                <span v-if="i === neck" class="tag">Cuello de botella</span>
                <span class="sv">{{ fmt(v) }}</span>
                <span class="conv">{{ pc(v, base) }}% de las solicitudes</span>
                <div v-if="wt != null" class="wait"><b>{{ fmt(wt) }}</b>{{ wl }}</div>
            </div>
        </div>
        <p class="note" style="margin:12px 0 0">La matriz guarda solo la última visita y la última resolución; con el historial de eventos el flujo será exacto. {{ fmt(f.ing - f.act) }} expedientes salieron de la nómina.</p>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useTableroStore } from '../../stores/tablero';
import { fmt, pc } from '../../utils/formato';

const tablero = useTableroStore();
const W = 1000, H = 230, mid = 96;

const f = computed(() => tablero.ag.fl);
const w = computed(() => tablero.ag.wait);
const base = computed(() => Math.max(1, f.value.c));
const st = computed(() => [
    ['Solicitud', f.value.c, w.value.vis, 'esperan visita'],
    ['Visita', f.value.vis, w.value.res, 'esperan resolución'],
    ['Resolución', f.value.res, w.value.nom, 'esperan ingreso a nómina'],
    ['Ingreso a nómina', f.value.ing, null, ''],
    ['Activo hoy', f.value.act, null, ''],
]);
const neck = computed(() => {
    let idx = -1, mx = 0;
    st.value.forEach((s, i) => { if (s[2] != null && s[2] > mx) { mx = s[2]; idx = i; } });
    return idx;
});
const xs = st.value.map((_, i) => (i + 0.5) * W / 5);
const hs = computed(() => st.value.map((s) => Math.max(8, s[1] / base.value * 170)));

const bandas = computed(() => [0, 1, 2, 3].map((i) => {
    const x0 = xs[i] + 6, x1 = xs[i + 1] - 6, h0 = hs.value[i] / 2, h1 = hs.value[i + 1] / 2, cx = (x0 + x1) / 2;
    return { i, d: `M${x0},${mid - h0} C${cx},${mid - h0} ${cx},${mid - h1} ${x1},${mid - h1} L${x1},${mid + h1} C${cx},${mid + h1} ${cx},${mid + h0} ${x0},${mid + h0}Z` };
}));

const goteo = (i) => `M${xs[i]},${mid + hs.value[i] / 2} C${xs[i]},${mid + hs.value[i] / 2 + 20} ${xs[i] + 40},${H - 30} ${xs[i] + 80},${H - 10}`;
</script>
