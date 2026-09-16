<template>
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0" :class="chipClass">
            <component :is="icon" class="w-6 h-6" />
        </div>
        <div class="min-w-0">
            <h3 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest truncate">{{ label }}</h3>
            <p class="text-3xl font-black truncate" :class="valueClass">{{ value }}</p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    icon: { type: [Object, Function], required: true },
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    color: { type: String, default: 'indigo' }
});

// Clases completas y literales (no interpoladas) para que Tailwind las detecte al escanear el contenido.
const COLORS = {
    indigo:  { chip: 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-500',   value: 'text-indigo-600 dark:text-indigo-400' },
    blue:    { chip: 'bg-blue-50 dark:bg-blue-900/20 text-blue-500',        value: 'text-blue-600 dark:text-blue-400' },
    emerald: { chip: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500', value: 'text-emerald-600 dark:text-emerald-400' },
    amber:   { chip: 'bg-amber-50 dark:bg-amber-900/20 text-amber-500',     value: 'text-amber-600 dark:text-amber-400' },
    cyan:    { chip: 'bg-cyan-50 dark:bg-cyan-900/20 text-cyan-500',        value: 'text-cyan-600 dark:text-cyan-400' },
    pink:    { chip: 'bg-pink-50 dark:bg-pink-900/20 text-pink-500',        value: 'text-pink-600 dark:text-pink-400' },
    purple:  { chip: 'bg-purple-50 dark:bg-purple-900/20 text-purple-500', value: 'text-purple-600 dark:text-purple-400' },
    red:     { chip: 'bg-red-50 dark:bg-red-900/20 text-red-500',          value: 'text-red-600 dark:text-red-400' },
};

const chipClass = computed(() => (COLORS[props.color] || COLORS.indigo).chip);
const valueClass = computed(() => (COLORS[props.color] || COLORS.indigo).value);
</script>
