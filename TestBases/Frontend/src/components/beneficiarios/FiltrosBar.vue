<template>
    <div class="flex flex-wrap items-end gap-3 border-b border-rule bg-white p-4">
        <div class="min-w-[14rem] flex-1">
            <label class="etiqueta" for="f-q">Buscar</label>
            <div class="relative">
                <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-400" />
                <input id="f-q" class="campo pl-9" type="search" placeholder="Nombre, CUI o teléfono"
                    :value="modelValue.q" maxlength="80" @input="cambiar('q', $event.target.value)" />
            </div>
        </div>

        <div class="w-full sm:w-56">
            <label class="etiqueta" for="f-com">Comunidad</label>
            <select id="f-com" class="campo" :value="modelValue.comunidad_id ?? ''"
                @change="cambiar('comunidad_id', $event.target.value)">
                <option value="">Todas</option>
                <option v-for="c in catalogos.comunidades" :key="c.id" :value="c.id">{{ c.nombre }}</option>
            </select>
        </div>

        <div class="w-full sm:w-52">
            <label class="etiqueta" for="f-base">Comunidad base</label>
            <select id="f-base" class="campo" :value="modelValue.comunidad_base_id ?? ''"
                @change="cambiar('comunidad_base_id', $event.target.value)">
                <option value="">Todas</option>
                <option value="0">(Sin comunidad base)</option>
                <option v-for="c in catalogos.comunidades_base" :key="c.id" :value="c.id">{{ c.nombre }}</option>
            </select>
        </div>

        <div class="w-full sm:w-44">
            <label class="etiqueta" for="f-estado">Seguimiento</label>
            <select id="f-estado" class="campo" :value="modelValue.estado ?? ''"
                @change="cambiar('estado', $event.target.value)">
                <option value="">Todos</option>
                <option value="sin_seguimiento">Sin seguimiento</option>
                <option value="con_seguimiento">Con seguimiento</option>
                <option value="con_comentario">Con comentario</option>
                <option value="con_imagen">Con imagen</option>
            </select>
        </div>

        <div class="w-full sm:w-40">
            <label class="etiqueta" for="f-res">Marca del Excel</label>
            <select id="f-res" class="campo" :value="modelValue.resaltado ?? ''"
                @change="cambiar('resaltado', $event.target.value)">
                <option value="">Todas</option>
                <option value="rojo">Rojo</option>
                <option value="ambar">Ámbar</option>
            </select>
        </div>

        <button v-if="hayFiltros" class="btn-neutro" @click="$emit('limpiar')">
            <XMarkIcon class="h-4 w-4" />
            Limpiar
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    modelValue: { type: Object, required: true },
    catalogos: { type: Object, required: true },
});
const emit = defineEmits(['update:modelValue', 'limpiar']);

const hayFiltros = computed(() => Object.values(props.modelValue).some(v => v !== '' && v !== null && v !== undefined));

const cambiar = (campo, valor) => {
    emit('update:modelValue', { ...props.modelValue, [campo]: valor === '' ? '' : valor });
};
</script>
