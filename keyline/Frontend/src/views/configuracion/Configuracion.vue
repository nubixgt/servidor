<template>
    <div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <section class="bg-white rounded-lg shadow p-5">
                <h3 class="text-lg font-bold">Tu cuenta</h3>
                <p class="text-sm text-slate-500 mb-4">Información asociada a tu usuario en el sistema.</p>
                <div class="space-y-3 text-sm">
                    <div><span class="field-label">Nombre</span><div>{{ auth.user?.nombre }}</div></div>
                    <div><span class="field-label">Usuario</span><div>{{ auth.user?.usuario }}</div></div>
                    <div v-if="auth.user?.email"><span class="field-label">Correo</span><div>{{ auth.user.email }}</div></div>
                    <div><span class="field-label">Rol</span><div><span class="tag bg-slate-100 text-slate-600">{{ ROLE_LABELS[auth.role] }}</span></div></div>
                    <div><span class="field-label">Región asignada</span><div>{{ auth.user?.regionAsignada || 'Nacional / sin asignar' }}</div></div>
                </div>
            </section>

            <section class="bg-white rounded-lg shadow p-5">
                <h3 class="text-lg font-bold">Cambiar contraseña</h3>
                <p class="text-sm text-slate-500 mb-4">Usa una contraseña segura que no compartas con nadie más.</p>
                <form @submit.prevent="cambiarPassword" class="space-y-3">
                    <div>
                        <label class="field-label">Contraseña actual</label>
                        <input v-model="actual" type="password" required class="field-input" />
                    </div>
                    <div>
                        <label class="field-label">Nueva contraseña</label>
                        <input v-model="nueva" type="password" required minlength="6" placeholder="Mínimo 6 caracteres" class="field-input" />
                    </div>
                    <button type="submit" :disabled="saving" class="btn-primary">Actualizar contraseña</button>
                </form>
            </section>
        </div>

        <section class="bg-white rounded-lg shadow p-5">
            <h3 class="text-lg font-bold">Acerca del sistema</h3>
            <p class="text-sm text-slate-500">KeylineGT · Sistema Nacional de Gestión de Parcelas Keyline · Guatemala</p>
            <p class="text-sm text-slate-500 mt-2">
                Registra parcelas de diseño keyline, captura variables técnicas de suelo, agua, lluvia, bioindicadores y
                condiciones físicas del terreno, y visualiza el avance nacional mediante un dashboard ejecutivo.
            </p>
        </section>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../../stores/auth';
import authService from '../../services/authService';
import { ROLE_LABELS } from '../../constants/keyline';
import { toastSuccess, alertError } from '../../utils/alerts';

const auth = useAuthStore();
const actual = ref('');
const nueva = ref('');
const saving = ref(false);

async function cambiarPassword() {
    saving.value = true;
    try {
        await authService.cambiarPassword(actual.value, nueva.value);
        toastSuccess('Contraseña actualizada correctamente.');
        actual.value = '';
        nueva.value = '';
    } catch (err) {
        alertError(err.message || 'No se pudo actualizar la contraseña.');
    } finally {
        saving.value = false;
    }
}
</script>

<style scoped>
.field-label { @apply block mb-0.5 text-xs font-bold uppercase tracking-wide text-slate-400; }
.field-input { @apply w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary-500; }
.btn-primary { @apply px-4 py-2 bg-primary-500 text-white rounded-md text-sm font-semibold hover:bg-primary-600 disabled:opacity-60; }
.tag { @apply text-xs font-semibold px-2 py-1 rounded-full; }
</style>
