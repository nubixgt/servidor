<template>
    <dialog ref="dialog" @cancel="onCancel" @click="onBackdrop">
        <div class="dialog-body">
            <button class="close-modal" aria-label="Cerrar ventana" :disabled="busy" @click="$emit('close')"><X :size="20" /></button>

            <template v-if="modal?.kind === 'confirm'">
                <span class="eyebrow">UN PASO MÁS</span>
                <h2>Dale nombre a tu noche.</h2>
                <p>Reservarás {{ selected.map((i) => 'A' + i).join(', ') }} para {{ selected.length * 8 }} personas.</p>
                <form @submit.prevent="$emit('book')">
                    <label class="name-field">Nombre de la reserva
                        <input v-model="name" autofocus minlength="2" maxlength="100" required placeholder="Nombre y apellido" autocomplete="name" :disabled="busy" />
                    </label>
                    <div class="confirm-total">
                        <span>{{ selected.length }} {{ selected.length === 1 ? 'mesa' : 'mesas' }} · 8 entradas por mesa</span>
                        <strong>{{ money(total) }}</strong>
                    </div>
                    <p class="dialog-note">Al confirmar, estas mesas quedarán ocupadas. No se procesa ningún pago.</p>
                    <button class="primary" :disabled="busy || !selected.length || name.trim().length < 2 || !!error">
                        {{ busy ? 'Confirmando…' : 'Confirmar reserva' }}<ArrowRight :size="18" />
                    </button>
                </form>
            </template>

            <template v-else-if="modal?.kind === 'help'">
                <span class="eyebrow">ASÍ DE FÁCIL</span>
                <h2>Elige. Reserva. Disfruta.</h2>
                <div class="help-steps">
                    <p><strong>1. Encuentra tu lugar.</strong>Los colores distinguen Platino, VIP y Preferencia. Puedes filtrar por localidad o buscar el número de mesa.</p>
                    <p><strong>2. Selecciona tus mesas.</strong>Cada mesa incluye 8 entradas. Las mesas rayadas están ocupadas y no se pueden seleccionar.</p>
                    <p><strong>3. Confirma tu reserva.</strong>Ingresa un nombre. Tus mesas se guardarán y quedarán ocupadas. Puedes cancelarlas desde Reservas.</p>
                </div>
                <button class="primary" @click="$emit('close')">Entendido<Check :size="18" /></button>
            </template>
        </div>
    </dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import { ArrowRight, Check, X } from 'lucide-vue-next';
import { money } from '../../utils/tables';

const props = defineProps({
    modal: { type: Object, default: null }, // { kind: 'confirm' | 'help' }
    selected: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
    busy: Boolean,
    error: { type: String, default: '' },
});
const emit = defineEmits(['close', 'book']);
const name = defineModel('name', { type: String, default: '' });

const dialog = ref(null);

watch(
    () => props.modal,
    (modal) => {
        if (modal) {
            if (!dialog.value.open) dialog.value.showModal();
        } else if (dialog.value?.open) {
            dialog.value.close();
        }
    },
    { flush: 'post' } // el contenido ya está renderizado cuando se abre (para que `autofocus` funcione)
);

const onCancel = (e) => {
    if (props.busy) e.preventDefault();
    else emit('close');
};
const onBackdrop = (e) => {
    if (e.target === e.currentTarget && !props.busy) emit('close');
};
</script>
