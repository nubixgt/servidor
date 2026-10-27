<template>
    <svg :id="uid" class="lc" :viewBox="`0 0 ${W} ${H}`" role="img" :aria-label="aria || 'Serie temporal'" ref="svg"
        @mousemove="mover" @mouseleave="salir">
        <defs>
            <linearGradient v-for="(s, k) in on" :key="k" :id="`${uid}g${k}`" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" :stop-color="s.c" :stop-opacity="on.length > 2 ? 0.14 : 0.28" />
                <stop offset="1" :stop-color="s.c" stop-opacity="0" />
            </linearGradient>
        </defs>
        <template v-for="g in grid" :key="g.t">
            <line class="gl" :x1="pl" :x2="W - pr" :y1="g.y" :y2="g.y" />
            <text class="ax" :x="pl - 8" :y="g.y + 4" text-anchor="end">{{ g.l }}</text>
        </template>
        <text v-for="e in ejeX" :key="e.i" class="ax" :x="e.x" :y="H - 8" text-anchor="middle">{{ e.l }}</text>
        <line class="xh" :x1="hx" :x2="hx" :y1="pt" :y2="H - pb" :style="{ display: hover == null ? 'none' : '' }" />
        <g :key="pasada">
            <template v-for="(s, k) in on" :key="s.k">
                <path class="area" :d="`${trazo(s)}L${x(n - 1)},${y(0)}L${x(0)},${y(0)}Z`" :fill="`url(#${uid}g${k})`" />
                <path class="ln" :class="{ draw: !RM.matches }" pathLength="1" :d="trazo(s)" :stroke="s.c" :style="{ animationDelay: k * 0.08 + 's' }" />
                <circle :cx="x(n - 1)" :cy="y(s.data[n - 1])" r="4.5" :fill="s.c" stroke="var(--bg)" stroke-width="2" />
            </template>
        </g>
        <g class="hd">
            <circle v-for="s in (hover == null ? [] : on)" :key="s.k" :cx="x(hover)" :cy="y(s.data[hover])" r="5" :fill="s.c" stroke="var(--bg)" stroke-width="2" />
        </g>
    </svg>
</template>

<script>
let contador = 0;
</script>

<script setup>
import { computed, ref, watch } from 'vue';
import { useTooltip } from '../../composables/useTooltip';
import { fmt } from '../../utils/formato';

const props = defineProps({
    labels: { type: Array, required: true },
    series: { type: Array, required: true }, // [{ k, l, c, data, on }]
    partial: { type: Boolean, default: false },
    aria: { type: String, default: '' }
});

const RM = matchMedia('(prefers-reduced-motion: reduce)');
const { mostrar, ocultar } = useTooltip();
const uid = 'evo' + (++contador);
const W = 680, H = 250, pl = 52, pr = 14, pt = 14, pb = 28;
const svg = ref(null);
const hover = ref(null);

const n = computed(() => props.labels.length);
const on = computed(() => props.series.filter((s) => s.on));
const top = computed(() => {
    const mx = Math.max(1, ...on.value.flatMap((s) => s.data));
    const step = Math.pow(10, Math.floor(Math.log10(mx)));
    const nice = [1, 2, 2.5, 5, 10].map((k) => k * step).find((t) => mx / t <= 5) || step * 10;
    return { nice, top: Math.ceil(mx / nice) * nice };
});

const x = (i) => pl + (W - pl - pr) * (n.value === 1 ? 0 : i / (n.value - 1));
const y = (v) => pt + (H - pt - pb) * (1 - v / top.value.top);
const trazo = (s) => s.data.map((v, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)},${y(v).toFixed(1)}`).join('');

const grid = computed(() => {
    const out = [];
    for (let t = 0; t <= top.value.top + 1e-9; t += top.value.nice) out.push({ t, y: y(t), l: t >= 1000 ? fmt(t / 1000) + ' k' : fmt(t) });
    return out;
});
const ejeX = computed(() => {
    const every = Math.ceil(n.value / 12);
    return props.labels.map((l, i) => ({ i, l, x: x(i) })).filter((e) => e.i % every === 0 || e.i === n.value - 1);
});
const hx = computed(() => (hover.value == null ? 0 : x(hover.value)));

// al cambiar los datos se vuelve a dibujar el trazo animado (igual que el diseño original)
const pasada = ref(0);
watch(() => [props.labels, props.series.map((s) => [s.k, s.on, s.data])], () => { pasada.value++; });

function mover(e) {
    const r = svg.value.getBoundingClientRect();
    const px = (e.clientX - r.left) / r.width * W;
    let i = Math.round((px - pl) / ((W - pl - pr) / Math.max(1, n.value - 1)));
    i = Math.max(0, Math.min(n.value - 1, i));
    hover.value = i;
    mostrar({
        titulo: props.labels[i] + (props.partial && i === n.value - 1 ? ' (parcial)' : ''),
        filas: on.value.map((s) => ({ l: s.l, v: fmt(s.data[i]), c: s.c }))
    }, e.clientX, e.clientY);
}

function salir() {
    hover.value = null;
    ocultar();
}
</script>
