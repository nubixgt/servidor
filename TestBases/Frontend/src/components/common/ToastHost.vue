<template>
    <div class="pointer-events-none fixed bottom-4 right-4 z-[60] flex w-80 flex-col gap-2" aria-live="polite">
        <transition-group name="toast">
            <div v-for="t in toasts" :key="t.id"
                class="pointer-events-auto flex items-start gap-3 rounded border bg-white px-4 py-3 text-sm shadow-lg"
                :class="t.tipo === 'ok' ? 'border-brand-200' : 'border-danger/40'" role="status">
                <span class="mt-1 h-2 w-2 shrink-0 rounded-full" :class="t.tipo === 'ok' ? 'bg-brand-500' : 'bg-danger'"></span>
                <p class="flex-1 text-ink-800">{{ t.texto }}</p>
                <button class="text-ink-400 hover:text-ink-700" @click="cerrar(t.id)" aria-label="Cerrar">
                    <XMarkIcon class="h-4 w-4" />
                </button>
            </div>
        </transition-group>
    </div>
</template>

<script setup>
import { XMarkIcon } from '@heroicons/vue/20/solid';
import { toasts, cerrar } from '../../utils/toast';
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: opacity 0.18s ease, transform 0.18s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(6px);
}
</style>
