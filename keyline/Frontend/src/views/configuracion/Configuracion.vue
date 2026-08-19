<template>
    <div>
        <div class="grid2">
            <section class="panel glass">
                <div class="panel-head"><div><h3>Tu cuenta</h3><p>Información asociada a tu usuario en el sistema.</p></div></div>
                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 13.5px;">
                    <div><span class="detail-label">Nombre</span><div>{{ auth.user?.nombre }}</div></div>
                    <div><span class="detail-label">Usuario</span><div>{{ auth.user?.usuario }}</div></div>
                    <div v-if="auth.user?.email"><span class="detail-label">Correo</span><div>{{ auth.user.email }}</div></div>
                    <div><span class="detail-label">Rol</span><div><span class="badge">{{ ROLE_LABELS[auth.role] }}</span></div></div>
                    <div><span class="detail-label">Región asignada</span><div>{{ auth.user?.regionAsignada || 'Nacional / sin asignar' }}</div></div>
                </div>
            </section>

            <section class="panel glass">
                <div class="panel-head"><div><h3>Cambiar contraseña</h3><p>Usa una contraseña segura que no compartas con nadie más.</p></div></div>
                <form @submit.prevent="cambiarPassword">
                    <div class="field">
                        <label>Contraseña actual</label>
                        <input v-model="actual" type="password" required />
                    </div>
                    <div class="field">
                        <label>Nueva contraseña</label>
                        <input v-model="nueva" type="password" required minlength="6" placeholder="Mínimo 6 caracteres" />
                    </div>
                    <button type="submit" :disabled="saving" class="btn btn-primary">Actualizar contraseña</button>
                </form>
            </section>
        </div>

        <section class="panel glass">
            <div class="panel-head"><div><h3>Acerca del sistema</h3></div></div>
            <p class="hint">KeylineGT · Sistema Nacional de Gestión de Parcelas Keyline · Guatemala</p>
            <p class="hint" style="margin-top: 8px;">
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
