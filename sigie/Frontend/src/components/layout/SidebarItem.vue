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
                'w-full flex items-center gap-4 px-4 py-2.5 rounded-md transition-all duration-200 group',
                isActive
                    ? 'bg-primary-container text-on-primary-container font-semibold shadow-sm'
                    : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'
            ]"
        >
            <span :class="['material-symbols-outlined text-xl transition-transform duration-300', isActive ? 'scale-110' : 'group-hover:scale-110']">
                {{ icon }}
            </span>
            <span :class="['text-sm font-bold tracking-wide', isActive ? '' : 'opacity-80 group-hover:opacity-100']">
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
