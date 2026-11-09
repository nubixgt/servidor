<template>
    <section class="reservations-view">
        <div class="section-top">
            <div><span class="section-number">03 /</span><h2>Usuarios del sistema</h2></div>
            <div class="users-actions">
                <button class="secondary" @click="load"><RefreshCw :size="16" />Actualizar</button>
                <button class="secondary" @click="create"><UserPlus :size="16" />Nuevo usuario</button>
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
                    <button title="Cambiar contraseña" @click="changePassword(u)"><KeyRound :size="15" />Contraseña</button>
                    <button title="Cambiar rol" @click="changeRole(u)"><ShieldCheck :size="15" />Rol</button>
                    <button :title="u.active ? 'Desactivar' : 'Activar'" @click="toggleActive(u)">
                        <Power :size="15" />{{ u.active ? 'Desactivar' : 'Activar' }}
                    </button>
                </div>
            </div>
            <p v-if="!users.length && !loading" class="muted">No hay usuarios.</p>
        </div>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { KeyRound, Power, RefreshCw, ShieldCheck, UserPlus } from 'lucide-vue-next';
import userService from '../../services/userService';
import { ROLE_LABELS } from '../../stores/auth';
import { confirmBox, newUserBox, notify, passwordBox, roleBox } from '../../utils/alert';

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

// Ejecuta una llamada y devuelve { ok } o { ok: false, message } (para mostrarlo dentro del SweetAlert).
const run = async (fn, fallback) => {
    try {
        await fn();
        return { ok: true };
    } catch (err) {
        return { ok: false, message: errorMessage(err, fallback) };
    }
};

const create = async () => {
    if (await newUserBox((data) => run(() => userService.create(data), 'No se pudo crear el usuario.'))) {
        notify('success', 'Usuario creado.');
        load();
    }
};

const changePassword = async (u) => {
    if (await passwordBox(u.fullName, (password) => run(() => userService.update(u.id, { password }), 'No se pudo cambiar la contraseña.'))) {
        notify('success', 'Contraseña actualizada.');
    }
};

const changeRole = async (u) => {
    if (await roleBox(u.fullName, u.role, (role) => run(() => userService.update(u.id, { role }), 'No se pudo cambiar el rol.'))) {
        notify('success', 'Rol actualizado.');
        load();
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
    const res = await run(() => userService.update(u.id, { active: next }), 'No se pudo actualizar el usuario.');
    if (res.ok) {
        notify('success', next ? 'Usuario activado.' : 'Usuario desactivado.');
        load();
    } else {
        notify('error', res.message);
    }
};

onMounted(load);
</script>
