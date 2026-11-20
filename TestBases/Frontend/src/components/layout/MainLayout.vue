<template>
    <div class="flex min-h-screen flex-col">
        <header class="bg-ink-800 text-white">
            <div class="mx-auto flex h-14 max-w-[1400px] items-center justify-between px-5">
                <div class="flex items-center gap-3">
                    <span class="grid h-7 w-7 place-items-center rounded bg-white/10">
                        <TableCellsIcon class="h-4 w-4 text-brand-200" />
                    </span>
                    <span class="text-sm font-semibold tracking-wide">Registro de Beneficiarios</span>
                </div>

                <div class="flex items-center gap-4">
                    <div class="hidden text-right leading-tight sm:block">
                        <p class="text-sm font-medium">{{ auth.usuario?.nombre }}</p>
                        <p class="text-xs text-ink-300">{{ auth.etiquetaRol }}</p>
                    </div>
                    <button
                        class="inline-flex items-center gap-1.5 rounded border border-white/15 px-3 py-1.5 text-sm text-ink-300 transition-colors hover:bg-white/10 hover:text-white"
                        @click="salir">
                        <ArrowRightOnRectangleIcon class="h-4 w-4" />
                        Salir
                    </button>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-[1400px] flex-1 px-5 py-6">
            <router-view></router-view>
        </main>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { ArrowRightOnRectangleIcon, TableCellsIcon } from '@heroicons/vue/20/solid';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const router = useRouter();

const salir = () => {
    auth.limpiar();
    router.push({ name: 'Login' });
};
</script>
