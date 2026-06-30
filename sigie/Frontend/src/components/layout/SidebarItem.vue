<template>
    <router-link
        :to="to"
        custom
        v-slot="{ isActive, href, navigate }"
    >
        <a
            :href="href"
            @click="navigate"
            :class="[
                'w-full flex items-center gap-3.5 px-4 py-2.5 rounded-xl transition-all duration-200 group font-body',
                isActive
                    ? 'bg-blue-500 text-white font-semibold shadow-md'
                    : 'text-slate-400 hover:glass-card hover:text-slate-200'
            ]"
        >
            <span :class="['material-symbols-outlined text-lg transition-transform duration-300', isActive ? '' : 'group-hover:scale-105']">
                {{ icon }}
            </span>
            <span :class="['text-xs font-medium tracking-wide', isActive ? 'font-semibold' : 'opacity-90 group-hover:opacity-100']">
                {{ label }}
            </span>
        </a>
    </router-link>
</template>

<script setup>
defineProps({
    icon: { type: String, required: true },
    label: { type: String, required: true },
    to: { type: String, required: true },
});
</script>
