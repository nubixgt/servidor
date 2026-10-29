<template>
    <div>
        <div class="sm-tools">
            <input v-model="q" class="sm-q" type="search" placeholder="Buscar municipio…" aria-label="Buscar municipio">
            <button class="btn" type="button" @click="todos">Seleccionar todos</button>
            <button class="btn" type="button" :disabled="!modelValue.length" @click="emit('update:modelValue', [])">Limpiar</button>
            <span class="note"><b>{{ modelValue.length }}</b> de {{ municipios.length }} municipios seleccionados</span>
        </div>
        <div class="sm-grid">
            <label v-for="m in visibles" :key="m.id" class="sm-opt" :class="{ on: modelValue.includes(m.id) }">
                <input type="checkbox" :checked="modelValue.includes(m.id)" @change="alternar(m.id)">
                <span>{{ m.nombre }}</span>
            </label>
        </div>
        <div v-if="!visibles.length" class="note">Ningún municipio coincide con «{{ q }}».</div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    modelValue: { type: Array, required: true },
    municipios: { type: Array, required: true }
});
const emit = defineEmits(['update:modelValue']);

const q = ref('');
const norm = (s) => s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
const visibles = computed(() => {
    const t = norm(q.value.trim());
    return t ? props.municipios.filter((m) => norm(m.nombre).includes(t)) : props.municipios;
});

function alternar(id) {
    const v = props.modelValue;
    emit('update:modelValue', v.includes(id) ? v.filter((x) => x !== id) : [...v, id]);
}
/** Selecciona todos los municipios del departamento (no solo los que deja ver la búsqueda). */
function todos() {
    emit('update:modelValue', props.municipios.map((m) => m.id));
}
</script>

<style scoped>
.sm-tools{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:10px}
.sm-q{height:36px;min-width:220px;flex:1;max-width:320px;border-radius:10px;border:1px solid var(--line2);background:var(--glass);padding:0 10px;color:var(--ink)}
.sm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:6px;max-height:260px;overflow:auto;padding:2px}
.sm-opt{display:flex;align-items:center;gap:8px;padding:7px 10px;border:1px solid var(--line);border-radius:10px;cursor:pointer;font-size:13px;color:var(--ink2);background:var(--glass-2)}
.sm-opt.on{border-color:var(--blue5);color:var(--ink)}
.sm-opt input{accent-color:var(--blue7)}
</style>
