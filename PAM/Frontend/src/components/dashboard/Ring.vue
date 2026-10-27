<template>
    <svg width="68" height="68" viewBox="0 0 68 68" aria-hidden="true">
        <circle cx="34" cy="34" :r="r" fill="none" stroke="color-mix(in srgb,var(--ink3) 16%,transparent)" stroke-width="8" />
        <circle cx="34" cy="34" :r="r" fill="none" :stroke="tone" stroke-width="8" stroke-linecap="round"
            :stroke-dasharray="c" :stroke-dashoffset="c * (1 - p)" transform="rotate(-90 34 34)"
            style="transition:stroke-dashoffset .8s cubic-bezier(.2,.7,.2,1)" />
    </svg>
</template>

<script setup>
defineProps({ p: { type: Number, required: true }, tone: { type: String, required: true } });
const r = 26, c = 2 * Math.PI * r;
</script>
