<template>
    <svg class="spark" :viewBox="`0 0 ${W} ${H}`" preserveAspectRatio="none" aria-hidden="true">
        <defs>
            <linearGradient :id="gid" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" :stop-color="tone" stop-opacity=".45" />
                <stop offset="1" :stop-color="tone" stop-opacity="0" />
            </linearGradient>
        </defs>
        <path :d="`${d}L${W},${H}L0,${H}Z`" :fill="`url(#${gid})`" />
        <path :d="d" fill="none" :stroke="tone" stroke-width="1.6" vector-effect="non-scaling-stroke" stroke-linejoin="round" />
    </svg>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: { type: Array, required: true },
    tone: { type: String, required: true },
    gid: { type: String, required: true }
});

const W = 160, H = 34;
const d = computed(() => {
    const n = props.data.length, mx = Math.max(1, ...props.data);
    const x = (i) => i / (n - 1) * W, y = (v) => H - 2 - (v / mx) * (H - 6);
    return props.data.map((v, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)},${y(v).toFixed(1)}`).join('');
});
</script>
