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
                'w-full flex items-center gap-4 px-4 py-3 rounded-xl transition-all duration-200 group font-body',
                isActive
                    ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold shadow-glow-blue'
                    : 'text-slate-400 hover:bg-slate-800/40 hover:text-white'
            ]"
        >
            <span :class="['material-symbols-outlined text-xl transition-transform duration-300', isActive ? 'scale-110' : 'group-hover:scale-110']">
                {{ icon }}
            </span>
            <span :class="['text-xs font-semibold tracking-wide', isActive ? '' : 'opacity-85 group-hover:opacity-100']">
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
