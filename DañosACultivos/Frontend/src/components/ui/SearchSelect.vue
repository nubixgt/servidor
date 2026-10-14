<template>
    <div ref="root" class="relative">
        <input
            :id="id"
            :value="abierto ? busqueda : modelValue"
            :placeholder="abierto && modelValue ? modelValue : placeholder"
            :required="required && !modelValue"
            class="field"
            autocomplete="off"
            role="combobox"
            :aria-expanded="abierto"
            @focus="abrir"
            @input="onInput"
            @keydown.down.prevent="mover(1)"
            @keydown.up.prevent="mover(-1)"
            @keydown.enter="onEnter"
            @keydown.esc="cerrar"
        />
        <ul v-if="abierto" class="absolute z-50 mt-1 w-full max-h-60 overflow-auto rounded-xl bg-white shadow-lg py-1" style="border: 1px solid var(--input-border);" role="listbox">
            <li v-for="(o, i) in filtradas" :key="o" role="option" :aria-selected="o === modelValue"
                class="px-3 py-2 text-[14px] cursor-pointer"
                :style="{ background: i === indice ? 'rgba(7,148,71,.12)' : '', color: 'var(--navy)', fontWeight: o === modelValue ? 700 : 400 }"
                @mousedown.prevent="elegir(o)" @mousemove="indice = i">
                {{ o }}
            </li>
            <li v-if="!filtradas.length" class="px-3 py-2 text-[13px]" style="color: var(--muted);">Sin resultados</li>
        </ul>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    options: { type: Array, required: true },
    placeholder: { type: String, default: 'Buscar…' },
    id: { type: String, default: undefined },
    required: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const root = ref(null);
const abierto = ref(false);
const busqueda = ref('');
const indice = ref(0);

const normalizar = (s) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
const filtradas = computed(() => {
    const q = normalizar(busqueda.value.trim());
    return q ? props.options.filter((o) => normalizar(o).includes(q)) : props.options;
});

function abrir() { abierto.value = true; busqueda.value = ''; indice.value = 0; }
function cerrar() { abierto.value = false; }
function onInput(e) { busqueda.value = e.target.value; indice.value = 0; abierto.value = true; }
function mover(d) {
    if (!abierto.value) return abrir();
    const n = filtradas.value.length;
    if (n) indice.value = (indice.value + d + n) % n;
}
function onEnter(e) {
    if (!abierto.value) return;
    e.preventDefault();
    if (filtradas.value[indice.value]) elegir(filtradas.value[indice.value]);
}
function elegir(o) { emit('update:modelValue', o); cerrar(); }

const fuera = (e) => { if (root.value && !root.value.contains(e.target)) cerrar(); };
onMounted(() => document.addEventListener('mousedown', fuera));
onBeforeUnmount(() => document.removeEventListener('mousedown', fuera));
</script>
