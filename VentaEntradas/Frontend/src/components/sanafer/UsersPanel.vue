<template>
    <section class="reservations-view">
        <div class="section-top">
            <div><span class="section-number">03 /</span><h2>Usuarios del sistema</h2></div>
            <div class="users-actions">
                <button class="secondary" @click="load"><RefreshCw :size="16" />Actualizar</button>
                <button class="secondary" @click="openCreate"><UserPlus :size="16" />Nuevo usuario</button>
            </div>
        </div>
        <p class="muted">Administradores: acceso total y gestión de usuarios. Vendedores: reservan mesas y ven o cancelan solo las suyas.</p>
        <p v-if="error" role="alert" class="error">{{ error }}</p>

        <div class="users-table">
            <div v-for="u in users" :key="u.id" class="user-row" :class="{ inactive: !u.active }">
                <div class="user-main">
                    <strong>{{ u.fullName }}</strong>
                    <span>@{{ u.username }}</span>
                </div>
                <span class="role-badge" :class="u.role">{{ ROLE_LABELS[u.role] }}</span>
                <span class="user-state">{{ u.active ? 'Activo' : 'Inactivo' }}</span>
                <div class="user-buttons">
                    <button title="Cambiar contraseña" @click="openPassword(u)"><KeyRound :size="15" />Contraseña</button>
                    <button title="Cambiar rol" @click="openRole(u)"><ShieldCheck :size="15" />Rol</button>
                    <button :title="u.active ? 'Desactivar' : 'Activar'" @click="toggleActive(u)">
                        <Power :size="15" />{{ u.active ? 'Desactivar' : 'Activar' }}
                    </button>
                </div>
            </div>
            <p v-if="!users.length && !loading" class="muted">No hay usuarios.</p>
        </div>
        <UserDialog :dlg="dlg" :form="form" :busy="busy" @close="dlg = null" @submit="submit" />
    </section>
</template>

<script setup>
import { nextTick, onMounted, reactive, ref } from 'vue';
import { KeyRound, Power, RefreshCw, ShieldCheck, UserPlus } from 'lucide-vue-next';
import UserDialog from './UserDialog.vue';
import userService from '../../services/userService';
import { ROLE_LABELS } from '../../stores/auth';
import { alertBox, confirmBox, notify } from '../../utils/alert';

const users = ref([]);
const loading = ref(true);
const error = ref('');

const errorMessage = (err, fallback) => err?.response?.data?.error || fallback;

const load = async () => {
    try {
        const { data } = await userService.getAll();
        users.value = data;
        error.value = '';
    } catch (err) {
        error.value = errorMessage(err, 'No se pudieron cargar los usuarios.');
    } finally {
        loading.value = false;
    }
};

const dlg = ref(null); // { kind: 'create' | 'password' | 'role', user? }
const form = reactive({ fullName: '', username: '', password: '', role: 'vendedor' });
const busy = ref(false);

const openCreate = () => {
    Object.assign(form, { fullName: '', username: '', password: '', role: 'vendedor' });
    dlg.value = { kind: 'create' };
};
const openPassword = (u) => {
    form.password = '';
    dlg.value = { kind: 'password', user: u };
};
const openRole = (u) => {
    form.role = u.role;
    dlg.value = { kind: 'role', user: u };
};

const submit = async () => {
    if (busy.value || !dlg.value) return;
    const { kind, user } = dlg.value;
    const calls = {
        create: [() => userService.create({ fullName: form.fullName.trim(), username: form.username.trim(), password: form.password, role: form.role }), 'Usuario creado.'],
        password: [() => userService.update(user?.id, { password: form.password }), 'Contraseña actualizada.'],
        role: [() => userService.update(user?.id, { role: form.role }), 'Rol actualizado.'],
    };
    const [call, okText] = calls[kind];
    busy.value = true;
    try {
        await call();
        dlg.value = null;
        notify('success', okText);
        load();
    } catch (err) {
        // Se cierra el cuadro para que el aviso quede visible; luego se reabre con lo escrito.
        const keep = dlg.value;
        dlg.value = null;
        await nextTick();
        await alertBox('error', 'No se pudo guardar', errorMessage(err, 'Revisa los datos e intenta de nuevo.'));
        dlg.value = keep;
    } finally {
        busy.value = false;
    }
};

const toggleActive = async (u) => {
    const next = !u.active;
    const ok = await confirmBox(
        next ? '¿Activar usuario?' : '¿Desactivar usuario?',
        next ? `${u.fullName} podrá iniciar sesión de nuevo.` : `${u.fullName} ya no podrá iniciar sesión.`,
        next ? 'Sí, activar' : 'Sí, desactivar'
    );
    if (!ok) return;
    try {
        await userService.update(u.id, { active: next });
        notify('success', next ? 'Usuario activado.' : 'Usuario desactivado.');
        load();
    } catch (err) {
        notify('error', errorMessage(err, 'No se pudo actualizar el usuario.'));
    }
};

onMounted(load);
</script>
