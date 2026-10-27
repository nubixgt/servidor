<template>
    <Teleport to="body">
        <div class="modal-w" role="dialog" aria-modal="true" @mousedown.self="$emit('cerrar')">
            <form ref="formulario" class="modal gl" :style="ancho ? { width: `min(${ancho}px,100%)` } : null" novalidate
                @submit.prevent="enviar">
                <h3>{{ titulo }}</h3>
                <slot></slot>
                <div class="err" role="alert">{{ error }}</div>
                <div class="acciones">
                    <button type="button" class="btn" @click="$emit('cerrar')">{{ guardar ? 'Cancelar' : 'Cerrar' }}</button>
                    <button v-if="guardar" class="btn pri" type="submit" :disabled="enviando">{{ boton }}</button>
                </div>
            </form>
        </div>
    </Teleport>
</template>

<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    titulo: { type: String, required: true },
    boton: { type: String, default: 'Guardar' },
    ancho: { type: Number, default: 0 },
    /** async () => void; si lanza, se muestra el mensaje. Sin esta función el modal es solo informativo. */
    guardar: { type: Function, default: null }
});
defineEmits(['cerrar']);

const formulario = ref(null);
const error = ref('');
const enviando = ref(false);

async function enviar() {
    if (!props.guardar) return;
    enviando.value = true;
    error.value = '';
    try {
        await props.guardar();
    } catch (e) {
        error.value = e.message || 'No se pudo guardar';
    }
    enviando.value = false;
}

onMounted(() => {
    const primero = formulario.value?.querySelector('input,select,textarea');
    if (primero) primero.focus();
});
</script>
