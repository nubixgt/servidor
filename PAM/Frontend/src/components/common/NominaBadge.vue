<template>
    <span class="badge" :style="{ color: color, background: `color-mix(in srgb,${color} 12%,transparent)` }">{{ texto }}</span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({ n: { type: Number, required: true } });
const color = computed(() => (props.n === 1 ? 'var(--ok)' : props.n === 2 ? 'var(--off)' : 'var(--ink3)'));
const texto = computed(() => (props.n === 1 ? 'En nómina' : props.n === 2 ? 'Egresado' : 'Sin nómina'));
</script>
