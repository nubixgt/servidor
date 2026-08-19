<template>
    <div class="stat-card glass">
        <span class="s-icon">{{ icon }}</span>
        <div class="s-label">{{ label }}</div>
        <div class="s-value">{{ value }}</div>
        <div class="s-sub">{{ sub }}</div>
        <div v-if="tendencia && tendencia.valor > 0" class="trend-badge" :class="tendencia.cambioPct >= 0 ? 'up' : 'down'">
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
