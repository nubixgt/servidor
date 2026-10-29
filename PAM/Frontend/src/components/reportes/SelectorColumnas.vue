<template>
    <div class="sc">
        <div class="sc-tools">
            <input v-model="q" class="sc-q" type="search" placeholder="Buscar columna…" aria-label="Buscar columna">
            <button class="btn" type="button" @click="todas">Seleccionar todas</button>
            <button class="btn" type="button" :disabled="!modelValue.length" @click="emit('update:modelValue', [])">Limpiar selección</button>
            <span class="note"><b>{{ modelValue.length }}</b> de {{ total }} columnas seleccionadas</span>
        </div>

        <div v-for="c in visibles" :key="c.id" class="sc-cat">
            <div class="sc-cat-h">
                <b>{{ c.titulo }}</b>
                <button class="sc-link" type="button" @click="alternarCategoria(c)">{{ categoriaCompleta(c) ? 'Quitar todas' : 'Marcar todas' }}</button>
            </div>
            <div class="sc-grid">
                <label v-for="col in c.columnas" :key="col.id" class="sc-opt" :class="{ on: modelValue.includes(col.id) }">
                    <input type="checkbox" :checked="modelValue.includes(col.id)" @change="alternar(col.id)">
                    <span>{{ col.etiqueta }}</span>
                </label>
            </div>
        </div>
        <div v-if="!visibles.length" class="note">Ninguna columna coincide con «{{ q }}».</div>

        <div class="sc-prev">
            <div class="note"><b>Vista previa de encabezados</b> · el orden del Excel es el que se ve aquí; puede reordenarlo con las flechas.</div>
            <div v-if="!modelValue.length" class="note" style="margin-top:6px">Seleccione al menos una columna.</div>
            <div v-else class="sc-chips">
                <span v-for="(id, i) in modelValue" :key="id" class="chip">
                    <span class="sc-n">{{ i + 1 }}</span>{{ etiqueta(id) }}
                    <button type="button" :disabled="i === 0" :aria-label="'Subir ' + etiqueta(id)" @click="mover(i, -1)">‹</button>
                    <button type="button" :disabled="i === modelValue.length - 1" :aria-label="'Bajar ' + etiqueta(id)" @click="mover(i, 1)">›</button>
                    <button type="button" :aria-label="'Quitar ' + etiqueta(id)" @click="alternar(id)"><Icono name="x" :size="12" /></button>
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import Icono from '../common/Icono.vue';

const props = defineProps({
    modelValue: { type: Array, required: true },
    categorias: { type: Array, required: true }
});
const emit = defineEmits(['update:modelValue']);

const q = ref('');
const todasCols = computed(() => props.categorias.flatMap((c) => c.columnas));
const total = computed(() => todasCols.value.length);
const etiquetas = computed(() => Object.fromEntries(todasCols.value.map((c) => [c.id, c.etiqueta])));
const etiqueta = (id) => etiquetas.value[id] || id;

const norm = (s) => s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
const visibles = computed(() => {
    const t = norm(q.value.trim());
    if (!t) return props.categorias;
    return props.categorias
        .map((c) => ({ ...c, columnas: c.columnas.filter((x) => norm(x.etiqueta).includes(t)) }))
        .filter((c) => c.columnas.length);
});

function alternar(id) {
    const v = props.modelValue;
    emit('update:modelValue', v.includes(id) ? v.filter((x) => x !== id) : [...v, id]);
}
function todas() {
    const v = props.modelValue;
    emit('update:modelValue', [...v, ...todasCols.value.map((c) => c.id).filter((id) => !v.includes(id))]);
}
const categoriaCompleta = (c) => c.columnas.every((x) => props.modelValue.includes(x.id));
function alternarCategoria(c) {
    const ids = c.columnas.map((x) => x.id);
    const v = props.modelValue;
    emit('update:modelValue', categoriaCompleta(c) ? v.filter((x) => !ids.includes(x)) : [...v, ...ids.filter((id) => !v.includes(id))]);
}
function mover(i, d) {
    const v = [...props.modelValue];
    const j = i + d;
    if (j < 0 || j >= v.length) return;
    [v[i], v[j]] = [v[j], v[i]];
    emit('update:modelValue', v);
}
</script>

<style scoped>
.sc-tools{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:12px}
.sc-q{height:36px;min-width:220px;flex:1;max-width:320px;border-radius:10px;border:1px solid var(--line2);background:var(--glass);padding:0 10px;color:var(--ink)}
.sc-cat{margin-bottom:12px}
.sc-cat-h{display:flex;justify-content:space-between;align-items:center;font-size:12.5px;color:var(--ink2);margin-bottom:6px}
.sc-link{border:0;background:none;color:var(--blue7);font-size:12px;font-weight:600;cursor:pointer}
.sc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:6px}
.sc-opt{display:flex;align-items:center;gap:8px;padding:7px 10px;border:1px solid var(--line);border-radius:10px;cursor:pointer;font-size:13px;color:var(--ink2);background:var(--glass-2)}
.sc-opt.on{border-color:var(--blue5);color:var(--ink)}
.sc-opt input{accent-color:var(--blue7)}
.sc-prev{margin-top:14px;padding-top:12px;border-top:1px solid var(--line)}
.sc-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.sc-chips .chip{padding-right:4px}
.sc-n{display:inline-grid;place-items:center;min-width:18px;height:18px;border-radius:99px;background:var(--blue7);color:#fff;font-size:10.5px}
.sc-chips .chip button:disabled{opacity:.3;cursor:default}
</style>
