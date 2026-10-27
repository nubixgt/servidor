<template>
    <span class="badge" :style="{
        color: stc(i),
        background: `color-mix(in srgb,${stc(i)} 13%,transparent)`,
        borderColor: `color-mix(in srgb,${stc(i)} 30%,transparent)`
    }"><i :style="{ background: stc(i) }"></i>{{ nombre }}</span>
</template>

<script setup>
import { computed } from 'vue';
import { useTableroStore } from '../../stores/tablero';
import { EST_DEF, stc } from '../../utils/formato';

const props = defineProps({ i: { type: Number, required: true } });
const tablero = useTableroStore();
const nombre = computed(() => (tablero.cube?.EST || EST_DEF)[props.i]);
</script>
