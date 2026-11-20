<template>
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-rule bg-white px-4 py-3 text-sm">
        <p class="text-ink-500">
            <template v-if="total > 0">
                Mostrando <span class="font-medium text-ink-800">{{ numero(desde) }}–{{ numero(hasta) }}</span>
                de <span class="font-medium text-ink-800">{{ numero(total) }}</span>
            </template>
            <template v-else>Sin resultados</template>
        </p>

        <div class="flex items-center gap-4">
            <label class="flex items-center gap-2 text-ink-500">
                Filas
                <select class="rounded border border-rule bg-white py-1 pl-2 pr-7 text-sm text-ink-800"
                    :value="porPagina" @change="$emit('cambiar-tamano', Number($event.target.value))">
                    <option v-for="n in [25, 50, 100]" :key="n" :value="n">{{ n }}</option>
                </select>
            </label>

            <div class="flex items-center gap-1">
                <button class="btn-neutro px-2 py-1" :disabled="pagina <= 1" aria-label="Primera página"
                    @click="$emit('ir', 1)">
                    <ChevronDoubleLeftIcon class="h-4 w-4" />
                </button>
                <button class="btn-neutro px-2 py-1" :disabled="pagina <= 1" aria-label="Página anterior"
                    @click="$emit('ir', pagina - 1)">
                    <ChevronLeftIcon class="h-4 w-4" />
                </button>
                <span class="min-w-[7rem] px-2 text-center text-ink-600">
                    Página <span class="font-medium text-ink-900">{{ numero(pagina) }}</span> de {{ numero(paginas) }}
                </span>
                <button class="btn-neutro px-2 py-1" :disabled="pagina >= paginas" aria-label="Página siguiente"
                    @click="$emit('ir', pagina + 1)">
                    <ChevronRightIcon class="h-4 w-4" />
                </button>
                <button class="btn-neutro px-2 py-1" :disabled="pagina >= paginas" aria-label="Última página"
                    @click="$emit('ir', paginas)">
                    <ChevronDoubleRightIcon class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { ChevronDoubleLeftIcon, ChevronDoubleRightIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/20/solid';
import { numero } from '../../utils/formato';

const props = defineProps({
    pagina: { type: Number, required: true },
    paginas: { type: Number, required: true },
    porPagina: { type: Number, required: true },
    total: { type: Number, required: true },
});
defineEmits(['ir', 'cambiar-tamano']);

const desde = computed(() => (props.total === 0 ? 0 : (props.pagina - 1) * props.porPagina + 1));
const hasta = computed(() => Math.min(props.total, props.pagina * props.porPagina));
</script>
