<template>
    <div>
        <section>
            <div class="content-head">
                <div>
                    <h2>Equipo de trabajo</h2>
                    <p>Administra los usuarios y sus roles en el sistema.</p>
                </div>
                <button class="btn btn-primary" @click="abrirNuevo"><PlusIcon class="w-5 h-5" />Nuevo usuario</button>
            </div>

            <div class="stats">
                <div v-for="st in stats" :key="st.title" class="panel stat">
                    <div class="stat-icon"><component :is="st.icon" class="w-5 h-5" /></div>
                    <div class="stat-title">{{ st.title }}</div>
                    <strong>{{ st.value }}</strong>
                    <div class="stat-foot">{{ st.foot }}</div>
                </div>
            </div>

            <article class="panel">
                <div class="panel-head">
                    <div><h3>Personas y accesos</h3><p>Una vista clara del equipo y su estado.</p></div>
                </div>

                <div class="filters">
                    <div class="searchbox">
                        <MagnifyingGlassIcon />
                        <input v-model="busqueda" type="search" class="field" placeholder="Buscar por nombre de usuario…" aria-label="Buscar usuarios" />
                    </div>
                    <select v-model="filtroRol" class="field" aria-label="Filtrar por rol">
                        <option value="">Todos los roles</option>
                        <option value="admin">Administrador</option>
                        <option value="supervisor">Supervisor</option>
                        <option value="tecnico">Técnico</option>
                    </select>
                    <select v-model="filtroEstado" class="field" aria-label="Filtrar por estado">
                        <option value="">Todos los estados</option>
                        <option value="true">Activo</option>
                        <option value="false">Inactivo</option>
                    </select>
                </div>

                <p v-if="loading && !usuarios.length" class="empty">Cargando…</p>
                <p v-else-if="error" class="empty" style="color: var(--red);">{{ error }}</p>
                <div v-else class="usergrid">
                    <article v-for="u in filtrados" :key="u.id" class="usercard">
                        <div class="flex items-center justify-between gap-2">
                            <div class="avatar" :style="{ background: avatarBg(u.rol) }">{{ u.usuario.slice(0, 2).toUpperCase() }}</div>
                            <span class="badge" :class="u.activo ? '' : 'gray'">{{ u.activo ? 'Activo' : 'Inactivo' }}</span>
                        </div>
                        <h3>{{ u.usuario }}<span v-if="u.id === yo" class="ml-2 text-[11px] font-normal" style="color: var(--muted);">(tú)</span></h3>
                        <span class="badge" :class="rolBadge(u.rol)" style="text-transform: capitalize;">{{ u.rol }}</span>
                        <div class="userfoot">
                            <small>Creado el {{ fecha(u.creadoEn) }}</small>
                            <div class="flex gap-2">
                                <button class="iconbtn" title="Editar" :aria-label="`Editar ${u.usuario}`" @click="abrirEditar(u)"><PencilSquareIcon class="w-4 h-4" /></button>
                                <button class="iconbtn" title="Eliminar" :aria-label="`Eliminar ${u.usuario}`" :disabled="u.id === yo" :style="u.id === yo ? 'opacity:.45;cursor:not-allowed' : ''" @click="eliminar(u)"><TrashIcon class="w-4 h-4" style="color: var(--red);" /></button>
                            </div>
                        </div>
                    </article>
                    <div v-if="!filtrados.length" class="empty">No se encontraron usuarios.</div>
                </div>
            </article>

            <article class="panel mt-5">
                <h3 class="text-[14px] font-bold">Roles del sistema</h3>
                <div class="permissions">
                    <div><ShieldCheckIcon /><h3>Administrador</h3><p>Gestión del equipo, acceso a registros y configuración general.</p></div>
                    <div><Squares2X2Icon /><h3>Supervisor</h3><p>Consulta de indicadores, seguimiento y revisión de registros.</p></div>
                    <div><PencilSquareIcon /><h3>Técnico</h3><p>Captura de información, ubicación y evidencia de campo.</p></div>
                </div>
            </article>
        </section>

        <!-- Crear / editar -->
        <div v-if="form" class="fixed inset-0 z-[1000] flex items-center justify-center p-4" style="background: rgba(8, 42, 58, .53); backdrop-filter: blur(5px);" @click.self="form = null">
            <form class="modal-box w-full max-w-[480px] max-h-[90vh] overflow-y-auto" @submit.prevent="guardar">
                <div class="modal-head">
                    <h2>{{ form.id ? 'Editar usuario' : 'Nuevo usuario' }}</h2>
                    <button type="button" class="iconbtn" aria-label="Cerrar" @click="form = null"><XMarkIcon class="w-5 h-5" /></button>
                </div>
                <div class="modal-content space-y-4">
                    <div>
                        <label class="label" for="u-usuario">Usuario</label>
                        <input id="u-usuario" v-model.trim="form.usuario" class="field" autocomplete="off" />
                    </div>
                    <div>
                        <label class="label" for="u-pass">Contraseña</label>
                        <input id="u-pass" v-model="form.password" type="password" class="field" autocomplete="new-password" :placeholder="form.id ? 'Dejar vacío para no cambiarla' : 'Mínimo 6 caracteres'" />
                    </div>
                    <div>
                        <label class="label" for="u-rol">Rol</label>
                        <select id="u-rol" v-model="form.rol" class="field" :disabled="form.id === yo">
                            <option value="admin">Admin</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="tecnico">Técnico</option>
                        </select>
                    </div>
                    <label class="flex items-center gap-2 text-[13px]">
                        <input v-model="form.activo" type="checkbox" class="w-4 h-4" :disabled="form.id === yo" />
                        Usuario activo
                    </label>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" class="btn" @click="form = null">Cancelar</button>
                        <button type="submit" class="btn btn-primary" :disabled="guardando">{{ guardando ? 'Guardando…' : 'Guardar' }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import {
    PlusIcon, PencilSquareIcon, TrashIcon, XMarkIcon, MagnifyingGlassIcon, UsersIcon, CheckCircleIcon, ShieldCheckIcon,
    Squares2X2Icon, SparklesIcon,
} from '@heroicons/vue/24/outline';
import usuarioService from '../../services/usuarioService';
import authService from '../../services/authService';
import { toastSuccess, alertError, confirmDialog } from '../../utils/alerts';

const yo = authService.user()?.id;
const usuarios = ref([]);
const loading = ref(false);
const error = ref('');
const form = ref(null);
const guardando = ref(false);

const busqueda = ref('');
const filtroRol = ref('');
const filtroEstado = ref('');
const filtrados = computed(() => {
    const q = busqueda.value.trim().toLowerCase();
    return usuarios.value.filter((u) =>
        (!q || u.usuario.toLowerCase().includes(q))
        && (!filtroRol.value || u.rol === filtroRol.value)
        && (!filtroEstado.value || String(!!u.activo) === filtroEstado.value));
});
const stats = computed(() => {
    const l = usuarios.value;
    return [
        { title: 'Integrantes del equipo', value: l.length, foot: 'Usuarios registrados', icon: UsersIcon },
        { title: 'Usuarios activos', value: l.filter((u) => u.activo).length, foot: 'Con acceso habilitado', icon: CheckCircleIcon },
        { title: 'Supervisores', value: l.filter((u) => u.rol === 'supervisor').length, foot: 'Seguimiento del trabajo en campo', icon: ShieldCheckIcon },
        { title: 'Personal técnico', value: l.filter((u) => u.rol === 'tecnico').length, foot: 'Captura de información', icon: SparklesIcon },
    ];
});
const avatarBg = (rol) => ({ admin: '#e7eaf5', supervisor: '#e3eff4', tecnico: '#e0eddf' }[rol]);
const rolBadge = (rol) => ({ admin: 'purple', supervisor: 'blue', tecnico: '' }[rol]);
const fecha = (f) => (f ? new Date(String(f).slice(0, 10) + 'T12:00:00').toLocaleDateString('es-GT', { day: '2-digit', month: 'short', year: 'numeric' }) : '—');

const rolStyle = (rol) => ({
    admin: { background: '#FDE3E5', color: '#B4232F' },
    supervisor: { background: '#E3EEFF', color: '#1259C7' },
    tecnico: { background: 'var(--green-light)', color: 'var(--green-dark)' },
}[rol]);

const cargar = async () => {
    loading.value = true;
    error.value = '';
    try {
        usuarios.value = await usuarioService.list();
    } catch (e) {
        error.value = 'No se pudieron cargar los usuarios.';
    } finally {
        loading.value = false;
    }
};

const abrirNuevo = () => { form.value = { id: null, usuario: '', password: '', rol: 'tecnico', activo: true }; };
const abrirEditar = (u) => { form.value = { id: u.id, usuario: u.usuario, password: '', rol: u.rol, activo: u.activo }; };

const guardar = async () => {
    const f = form.value;
    if (!f.usuario) return alertError('Ingresa el nombre de usuario.', 'Datos incompletos');
    if (!f.id && !f.password) return alertError('Ingresa una contraseña para el nuevo usuario.', 'Datos incompletos');
    guardando.value = true;
    try {
        const { id, ...datos } = f;
        if (id) await usuarioService.update(id, datos);
        else await usuarioService.create(datos);
        form.value = null;
        toastSuccess(id ? 'Usuario actualizado' : 'Usuario creado');
        await cargar();
    } catch (e) {
        alertError(e.response?.data?.error || 'No se pudo guardar el usuario.', 'No se pudo guardar');
    } finally {
        guardando.value = false;
    }
};

const eliminar = async (u) => {
    if (!(await confirmDialog(`Se eliminará al usuario "${u.usuario}". Esta acción no se puede deshacer.`, { title: '¿Eliminar usuario?', danger: true, confirmText: 'Eliminar' }))) return;
    try {
        await usuarioService.remove(u.id);
        usuarios.value = usuarios.value.filter((x) => x.id !== u.id);
        toastSuccess('Usuario eliminado');
    } catch (e) {
        alertError(e.response?.data?.error || 'No se pudo eliminar el usuario.', 'No se pudo eliminar');
    }
};

onMounted(cargar);
</script>
