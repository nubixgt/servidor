<template>
    <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5 flex items-center justify-between gap-3 min-w-0">
        <div class="min-w-0">
            <span class="text-[13px] text-white/60 font-normal">{{ label }}</span>
            <div class="text-2xl sm:text-3xl font-bold text-white mt-1 truncate">{{ value }}</div>
            <p class="text-[11px] text-white/60 font-normal mt-1">{{ sub }}</p>
            <p v-if="tendencia && tendencia.valor > 0" class="text-[11px] font-medium mt-1 flex items-center gap-1" :class="tendencia.cambioPct >= 0 ? 'text-[#4ade80]' : 'text-[#f87171]'">
                <span>{{ tendencia.cambioPct >= 0 ? '▲' : '▼' }} {{ Math.abs(tendencia.cambioPct) }}% vs mes anterior</span>
            </p>
        </div>
        <div class="w-12 h-12 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center flex-shrink-0">
            <component :is="icon" class="w-6 h-6 text-white/80" />
        </div>
    </div>
</template>

<script setup>
defineProps({
    icon: { type: [Object, Function], required: true },
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    sub: { type: String, default: '' },
    tendencia: { type: Object, default: null },
});
</script>
