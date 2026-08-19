<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold">{{ auth.role === 'administrador' ? 'Equipo del proyecto' : 'Equipo técnico' }}</h1>
                <p class="text-sm text-slate-500">
                    {{ auth.role === 'administrador' ? 'Gestiona técnicos, supervisores y administradores del sistema.' : 'Técnicos de tu región asignada.' }}
                </p>
            </div>
            <button v-if="auth.role === 'administrador'" class="btn-primary" @click="openModal()">+ Nuevo usuario</button>
        </div>

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="p-3 text-left">Nombre</th>
                        <th class="p-3 text-left">Usuario</th>
                        <th class="p-3 text-left">Correo</th>
                        <th class="p-3 text-left">Rol</th>
                        <th class="p-3 text-left">Región asignada</th>
                        <th class="p-3 text-left">Estado</th>
                        <th class="p-3 text-left">Último acceso</th>
                        <th v-if="auth.role === 'administrador'" class="p-3 text-left">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading"><td colspan="8" class="p-8 text-center text-slate-400">Cargando…</td></tr>
                    <tr v-else-if="!usuarios.length"><td colspan="8" class="p-8 text-center text-slate-400">No hay usuarios registrados.</td></tr>
                    <tr v-for="u in usuarios" :key="u.id" class="border-t border-slate-100">
                        <td class="p-3 font-semibold">{{ u.nombre }}</td>
                        <td class="p-3">{{ u.usuario }}</td>
                        <td class="p-3">{{ u.email || '—' }}</td>
                        <td class="p-3"><span class="tag bg-slate-100 text-slate-600">{{ ROLE_LABELS[u.role] }}</span></td>
                        <td class="p-3">{{ u.regionAsignada || '—' }}</td>
                        <td class="p-3"><span class="tag" :class="u.activo === false ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'">{{ u.activo === false ? 'Inactivo' : 'Activo' }}</span></td>
                        <td class="p-3">{{ u.ultimoAcceso ? new Date(u.ultimoAcceso).toLocaleString('es-GT') : 'Nunca' }}</td>
                        <td v-if="auth.role === 'administrador'" class="p-3">
                            <div class="flex gap-2">
                                <button class="btn-secondary btn-sm" @click="openModal(u)">Editar</button>
                                <button class="btn-danger btn-sm" @click="eliminar(u)">Eliminar</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="modalUser !== null" class="modal-overlay" @click.self="modalUser = null">
            <div class="modal-box max-w-md">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-xl font-bold">{{ editing ? 'Editar usuario' : 'Nuevo usuario' }}</h3>
                    <button @click="modalUser = null" class="text-slate-400 hover:text-slate-700">✕</button>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="field-label">Nombre completo</label>
                        <input v-model="form.nombre" class="field-input" />
                    </div>
                    <div>
                        <label class="field-label">Usuario</label>
                        <input v-model="form.usuario" class="field-input" :disabled="editing" placeholder="nombre.usuario" />
                    </div>
                    <div>
                        <label class="field-label">Correo electrónico (opcional)</label>
                        <input v-model="form.email" type="email" class="field-input" />
                    </div>
                    <div>
                        <label class="field-label">{{ editing ? 'Nueva contraseña (opcional)' : 'Contraseña' }}</label>
                        <input v-model="form.password" type="password" class="field-input" :placeholder="editing ? 'Dejar en blanco para no cambiar' : 'Mínimo 6 caracteres'" />
                    </div>
                    <div>
                        <label class="field-label">Rol</label>
                        <select v-model="form.role" class="field-input">
                            <option v-for="r in ROLES" :key="r" :value="r">{{ ROLE_LABELS[r] }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Región asignada (para supervisores)</label>
                        <select v-model="form.regionAsignada" class="field-input">
                            <option value="">Sin asignar / nacional</option>
                            <option v-for="d in DEPARTAMENTOS" :key="d" :value="d">{{ d }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Teléfono</label>
                        <input v-model="form.telefono" class="field-input" />
                    </div>
                    <label v-if="editing" class="flex items-center gap-2 text-sm text-slate-700">
                        <input v-model="form.activo" type="checkbox" /> Usuario activo
                    </label>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button class="btn-secondary" @click="modalUser = null">Cancelar</button>
                    <button class="btn-primary" :disabled="saving" @click="save">{{ editing ? 'Guardar cambios' : 'Crear usuario' }}</button>
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

<style scoped>
.field-label { @apply block mb-1 text-xs font-semibold text-slate-500 uppercase tracking-wide; }
.field-input { @apply w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:bg-slate-100; }
.btn-primary { @apply px-4 py-2 bg-primary-500 text-white rounded-md text-sm font-semibold hover:bg-primary-600 disabled:opacity-60; }
.btn-secondary { @apply px-4 py-2 bg-slate-100 text-slate-700 rounded-md text-sm font-semibold hover:bg-slate-200; }
.btn-danger { @apply px-4 py-2 bg-rose-100 text-rose-700 rounded-md text-sm font-semibold hover:bg-rose-200; }
.btn-sm { @apply px-2.5 py-1 text-xs; }
.tag { @apply text-xs font-semibold px-2 py-1 rounded-full; }
.modal-overlay { @apply fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50; }
.modal-box { @apply bg-white rounded-lg shadow-xl p-6 w-full max-h-[85vh] overflow-y-auto; }
</style>
