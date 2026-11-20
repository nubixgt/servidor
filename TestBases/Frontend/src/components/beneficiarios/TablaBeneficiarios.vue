<template>
    <div class="relative max-h-[calc(100vh-22rem)] min-h-[16rem] overflow-auto bg-white">
        <table class="w-full min-w-[980px] border-collapse text-left text-[13px]">
            <thead class="sticky top-0 z-10 bg-ink-800 text-xs uppercase tracking-wide text-ink-300">
                <tr>
                    <th class="w-14 px-3 py-2.5 text-right font-medium">No.</th>
                    <th class="px-3 py-2.5 font-medium">Comunidad</th>
                    <th class="px-3 py-2.5 font-medium">Beneficiario</th>
                    <th class="px-3 py-2.5 font-medium">CUI</th>
                    <th class="px-3 py-2.5 font-medium">Teléfono</th>
                    <th class="px-3 py-2.5 font-medium">Comunidad base</th>
                    <th class="min-w-[16rem] px-3 py-2.5 font-medium">Seguimiento</th>
                    <th class="w-12 px-3 py-2.5"></th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="f in filas" :key="f.id" class="cursor-pointer border-b transition-colors"
                    :class="[claseFila(f), { 'outline outline-2 -outline-offset-2 outline-ink-900': f.id === seleccionadoId && f.resaltado !== 'ninguno' }]"
                    :title="f.resaltado === 'ninguno' ? null : `Fila marcada en ${f.resaltado === 'rojo' ? 'rojo' : 'ámbar'} en el Excel`"
                    @click="$emit('abrir', f)">
                    <td class="px-3 py-2 text-right font-mono text-ink-500">{{ f.numero }}</td>
                    <td class="px-3 py-2 text-ink-600">{{ f.comunidad }}</td>
                    <td class="px-3 py-2 font-medium text-ink-900">{{ f.nombre }}</td>
                    <td class="whitespace-nowrap px-3 py-2 font-mono text-ink-700">{{ cui(f.cui) }}</td>
                    <td class="whitespace-nowrap px-3 py-2 font-mono text-ink-700">
                        {{ telefono(f.telefono) }}<span v-if="!f.telefono" class="text-ink-300">—</span>
                    </td>
                    <td class="px-3 py-2 text-ink-600">
                        {{ f.comunidad_base }}<span v-if="!f.comunidad_base" class="text-ink-300">—</span>
                    </td>
                    <td class="px-3 py-2">
                        <div v-if="f.seguimiento.comentario || f.seguimiento.tiene_imagen" class="flex items-center gap-2">
                            <PhotoIcon v-if="f.seguimiento.tiene_imagen" class="h-4 w-4 shrink-0 text-brand-600"
                                title="Tiene imagen adjunta" />
                            <p v-if="f.seguimiento.comentario" class="line-clamp-1 max-w-[22rem] text-ink-700"
                                :title="f.seguimiento.comentario">
                                {{ f.seguimiento.comentario }}
                            </p>
                            <p v-else class="text-ink-500">Imagen adjunta</p>
                        </div>
                        <span v-else class="text-ink-300">Sin seguimiento</span>
                    </td>
                    <td class="px-3 py-2 text-right">
                        <button class="rounded p-1 text-ink-400 hover:bg-black/10 hover:text-brand-700"
                            :aria-label="`Abrir seguimiento de ${f.nombre}`" @click.stop="$emit('abrir', f)">
                            <PencilSquareIcon class="h-4 w-4" />
                        </button>
                    </td>
                </tr>

                <tr v-if="!cargando && filas.length === 0">
                    <td colspan="8" class="px-3 py-16 text-center text-ink-500">
                        No hay beneficiarios que coincidan con los filtros.
                    </td>
                </tr>
            </tbody>
        </table>

        <div v-if="cargando"
            class="absolute inset-0 z-20 grid place-items-start justify-center bg-white/60 pt-24">
            <span class="h-6 w-6 animate-spin rounded-full border-2 border-rule border-t-brand-600"></span>
        </div>
    </div>
</template>

<script setup>
import { PencilSquareIcon, PhotoIcon } from '@heroicons/vue/20/solid';
import { cui, telefono } from '../../utils/formato';

const props = defineProps({
    filas: { type: Array, required: true },
    cargando: { type: Boolean, default: false },
    seleccionadoId: { type: Number, default: null },
});
defineEmits(['abrir']);

// Las filas marcadas en el Excel se pintan completas. Rojo: texto blanco (como en el Excel);
// todo lo de adentro se fuerza a blanco para que los grises no se pierdan sobre el rojo.
const claseFila = (f) => {
    if (f.resaltado === 'rojo') {
        return 'border-[#8f0000] bg-[#c00000] hover:bg-[#a80000] [&_*]:!text-white';
    }
    if (f.resaltado === 'ambar') {
        return 'border-amber/30 bg-[#ffc000]/45 hover:bg-[#ffc000]/65';
    }
    return ['border-rule/70 hover:bg-brand-50/60', f.id === props.seleccionadoId ? 'bg-brand-50' : ''];
};
</script>
