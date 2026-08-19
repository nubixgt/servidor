<template>
    <div>
        <div v-if="auth.role === 'administrador'" style="display: flex; justify-content: flex-end;">
            <button class="btn btn-primary" @click="openModal()">+ Nuevo usuario</button>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Región asignada</th>
                        <th>Estado</th>
                        <th>Último acceso</th>
                        <th v-if="auth.role === 'administrador'">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading"><td colspan="8" class="empty-state">Cargando…</td></tr>
                    <tr v-else-if="!usuarios.length"><td colspan="8" class="empty-state">No hay usuarios registrados.</td></tr>
                    <tr v-for="u in usuarios" :key="u.id">
                        <td><strong>{{ u.nombre }}</strong></td>
                        <td>{{ u.usuario }}</td>
                        <td>{{ u.email || '—' }}</td>
                        <td><span class="badge">{{ ROLE_LABELS[u.role] }}</span></td>
                        <td>{{ u.regionAsignada || '—' }}</td>
                        <td><span class="tag" :class="u.activo === false ? 'tag-pendiente' : 'tag-implementado'">{{ u.activo === false ? 'Inactivo' : 'Activo' }}</span></td>
                        <td>{{ u.ultimoAcceso ? new Date(u.ultimoAcceso).toLocaleString('es-GT') : 'Nunca' }}</td>
                        <td v-if="auth.role === 'administrador'">
                            <div class="row-actions">
                                <button class="btn btn-secondary btn-sm" @click="openModal(u)">Editar</button>
                                <button class="btn btn-danger btn-sm" @click="eliminar(u)">Eliminar</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="modalUser !== null" class="modal-overlay" @click.self="modalUser = null">
            <div class="modal-box glass">
                <div class="modal-head">
                    <h3>{{ editing ? 'Editar usuario' : 'Nuevo usuario' }}</h3>
                    <button class="modal-close" @click="modalUser = null">✕</button>
                </div>
                <div class="field">
                    <label>Nombre completo</label>
                    <input v-model="form.nombre" />
                </div>
                <div class="field">
                    <label>Usuario</label>
                    <input v-model="form.usuario" :disabled="editing" placeholder="nombre.usuario" />
                </div>
                <div class="field">
                    <label>Correo electrónico (opcional)</label>
                    <input v-model="form.email" type="email" />
                </div>
                <div class="field">
                    <label>{{ editing ? 'Nueva contraseña (opcional)' : 'Contraseña' }}</label>
                    <input v-model="form.password" type="password" :placeholder="editing ? 'Dejar en blanco para no cambiar' : 'Mínimo 6 caracteres'" />
                </div>
                <div class="field">
                    <label>Rol</label>
                    <select v-model="form.role">
                        <option v-for="r in ROLES" :key="r" :value="r">{{ ROLE_LABELS[r] }}</option>
                    </select>
                </div>
                <div class="field">
                    <label>Región asignada (para supervisores)</label>
                    <select v-model="form.regionAsignada">
                        <option value="">Sin asignar / nacional</option>
                        <option v-for="d in DEPARTAMENTOS" :key="d" :value="d">{{ d }}</option>
                    </select>
                </div>
                <div class="field">
                    <label>Teléfono</label>
                    <input v-model="form.telefono" />
                </div>
                <label v-if="editing" style="display: flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 500;">
                    <input v-model="form.activo" type="checkbox" style="width: auto;" /> Usuario activo
                </label>
                <div class="modal-actions">
                    <button class="btn btn-secondary" @click="modalUser = null">Cancelar</button>
                    <button class="btn btn-primary" :disabled="saving" @click="save">{{ editing ? 'Guardar cambios' : 'Crear usuario' }}</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import usuarioService from '../../services/usuarioService';
import { useAuthStore } from '../../stores/auth';
import { ROLES, ROLE_LABELS, DEPARTAMENTOS } from '../../constants/keyline';
import { toastSuccess, alertError, confirmDialog, toastInfo } from '../../utils/alerts';

const auth = useAuthStore();
const usuarios = ref([]);
const loading = ref(true);
const modalUser = ref(null);
const saving = ref(false);
const editing = computed(() => !!modalUser.value);

const form = reactive({ nombre: '', usuario: '', email: '', password: '', role: 'tecnico', regionAsignada: '', telefono: '', activo: true });

async function load() {
    loading.value = true;
    try {
        const { data } = await usuarioService.listar();
        usuarios.value = data.users;
    } finally {
        loading.value = false;
    }
}
load();

function openModal(user = null) {
    modalUser.value = user || {};
    form.nombre = user?.nombre || '';
    form.usuario = user?.usuario || '';
    form.email = user?.email || '';
    form.password = '';
    form.role = user?.role || 'tecnico';
    form.regionAsignada = user?.regionAsignada || '';
    form.telefono = user?.telefono || '';
    form.activo = user?.activo !== false;
}

async function save() {
    if (!form.nombre || (!editing.value && (!form.usuario || !form.password))) {
        alertError('Nombre, usuario y contraseña son obligatorios.');
        return;
    }
    saving.value = true;
    const wasEditing = editing.value && !!modalUser.value.id;
    try {
        const payload = { ...form };
        if (!payload.password) delete payload.password;
        if (wasEditing) {
            await usuarioService.actualizar(modalUser.value.id, payload);
        } else {
            await usuarioService.crear(payload);
        }
        modalUser.value = null;
        toastSuccess(wasEditing ? 'Usuario actualizado.' : 'Usuario creado correctamente.');
        await load();
    } catch (err) {
        alertError(err.message || 'No se pudo guardar el usuario.');
    } finally {
        saving.value = false;
    }
}

async function eliminar(u) {
    const ok = await confirmDialog('Esta acción eliminará al usuario del sistema.', { title: '¿Eliminar este usuario?', danger: true, confirmText: 'Eliminar' });
    if (!ok) return;
    try {
        await usuarioService.eliminar(u.id);
        toastInfo('Usuario eliminado.');
        await load();
    } catch (err) {
        alertError(err.message || 'No se pudo eliminar.');
    }
}
</script>
