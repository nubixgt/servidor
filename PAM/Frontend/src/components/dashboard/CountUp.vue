<template>
    <component :is="tag">{{ texto }}</component>
</template>

<script>
// Valor anterior de cada contador (por clave) para animar desde él al cambiar los filtros
const PREV = {};
</script>

<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';
import { fmt } from '../../utils/formato';

const props = defineProps({
    value: { type: Number, required: true },
    ckey: { type: String, required: true },
    tag: { type: String, default: 'span' }
});

const RM = matchMedia('(prefers-reduced-motion: reduce)');
const texto = ref(fmt(props.value));
let raf = 0;

function animar(to) {
    cancelAnimationFrame(raf);
    const from = PREV[props.ckey] ?? (props.ckey === 'hero' ? 0 : to * 0.6);
    PREV[props.ckey] = to;
    if (RM.matches || from === to) {
        texto.value = fmt(to);
        return;
    }
    const t0 = performance.now(), d = 750;
    const paso = (t) => {
        const k = Math.min(1, (t - t0) / d), e = 1 - Math.pow(1 - k, 3);
        texto.value = fmt(from + (to - from) * e);
        if (k < 1) raf = requestAnimationFrame(paso);
    };
    raf = requestAnimationFrame(paso);
}

watch(() => props.value, animar, { immediate: true });
onBeforeUnmount(() => cancelAnimationFrame(raf));
</script>
