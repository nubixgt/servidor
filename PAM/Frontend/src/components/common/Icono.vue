<template>
    <svg viewBox="0 0 24 24" :width="size" :height="size" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" v-html="ICON[name]"></svg>
</template>

<script setup>
import { ICON } from '../../utils/iconos';

defineProps({
    name: { type: String, required: true },
    size: { type: Number, default: 18 }
});
</script>
