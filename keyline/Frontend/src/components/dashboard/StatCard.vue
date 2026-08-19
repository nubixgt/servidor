<template>
    <div class="bg-white rounded-lg shadow p-4">
        <span class="text-2xl">{{ icon }}</span>
        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide mt-2">{{ label }}</div>
        <div class="text-2xl font-bold mt-1">{{ value }}</div>
        <div class="text-xs text-slate-400 mt-1">{{ sub }}</div>
        <div v-if="tendencia && tendencia.valor > 0" class="text-xs font-semibold mt-2" :class="tendencia.cambioPct >= 0 ? 'text-emerald-600' : 'text-rose-600'">
            {{ tendencia.cambioPct >= 0 ? '▲' : '▼' }} {{ Math.abs(tendencia.cambioPct) }}% · últimos 30 días
        </div>
    </div>
</template>

<script setup>
defineProps({
    icon: { type: String, required: true },
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    sub: { type: String, default: '' },
    tendencia: { type: Object, default: null },
});
</script>
