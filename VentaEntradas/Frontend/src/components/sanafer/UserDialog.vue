<template>
    <dialog ref="dialog" @cancel="onCancel" @click="onBackdrop">
        <div class="dialog-body">
            <button class="close-modal" aria-label="Cerrar ventana" :disabled="busy" @click="$emit('close')"><X :size="20" /></button>

            <form v-if="dlg" @submit.prevent="$emit('submit')">
                <template v-if="dlg.kind === 'create'">
                    <span class="eyebrow">USUARIOS</span>
                    <h2>Nuevo usuario</h2>
                    <label class="name-field">Nombre completo
                        <input v-model="form.fullName" autofocus required minlength="2" maxlength="100" placeholder="Nombre y apellido" :disabled="busy" />
                    </label>
                    <label class="name-field">Usuario (para iniciar sesión)
                        <input v-model="form.username" required minlength="3" maxlength="30" autocapitalize="none" spellcheck="false" placeholder="usuario" :disabled="busy" />
                    </label>
                    <label class="name-field">Contraseña
                        <input v-model="form.password" type="password" required minlength="8" maxlength="72" autocomplete="new-password" placeholder="Mínimo 8 caracteres" :disabled="busy" />
                    </label>
                    <label class="name-field">Rol
                        <select v-model="form.role" :disabled="busy">
                            <option v-for="(label, value) in ROLE_LABELS" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </label>
                </template>

                <template v-else-if="dlg.kind === 'password'">
                    <span class="eyebrow">USUARIOS</span>
                    <h2>Cambiar contraseña</h2>
                    <p>Usuario: {{ dlg.user.fullName }}</p>
                    <label class="name-field">Nueva contraseña
                        <input v-model="form.password" autofocus type="password" required minlength="8" maxlength="72" autocomplete="new-password" placeholder="Mínimo 8 caracteres" :disabled="busy" />
                    </label>
                </template>

                <template v-else-if="dlg.kind === 'role'">
                    <span class="eyebrow">USUARIOS</span>
                    <h2>Cambiar rol</h2>
                    <p>Usuario: {{ dlg.user.fullName }}</p>
                    <label class="name-field">Rol
                        <select v-model="form.role" :disabled="busy">
                            <option v-for="(label, value) in ROLE_LABELS" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </label>
                </template>

                <button class="primary" :disabled="busy">{{ busy ? 'Guardando…' : 'Guardar' }}<ArrowRight :size="18" /></button>
            </form>
        </div>
    </dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import { ArrowRight, X } from 'lucide-vue-next';
import { ROLE_LABELS } from '../../stores/auth';

const props = defineProps({
    dlg: { type: Object, default: null }, // { kind: 'create' | 'password' | 'role', user? }
    form: { type: Object, required: true }, // { fullName, username, password, role }
    busy: Boolean,
});
const emit = defineEmits(['close', 'submit']);

const dialog = ref(null);

watch(
    () => props.dlg,
    (dlg) => {
        if (dlg) {
            if (!dialog.value.open) dialog.value.showModal();
        } else if (dialog.value?.open) {
            dialog.value.close();
        }
    },
    { flush: 'post' }
);

const onCancel = (e) => {
    if (props.busy) e.preventDefault();
    else emit('close');
};
const onBackdrop = (e) => {
    if (e.target === e.currentTarget && !props.busy) emit('close');
};
</script>
