<template>
    <div>
        <div class="mx-auto max-w-[1480px] px-3.5 sm:px-6 py-4 sm:py-6 pb-10">
            <header class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h1 class="text-[28px] sm:text-[34px] leading-tight font-extrabold" style="color: var(--green-dark);">Usuarios</h1>
                    <p class="text-[13px] sm:text-sm" style="color: var(--navy);">Administra quién puede acceder al sistema</p>
                </div>
                <button class="btn btn-primary btn-sm" @click="abrirNuevo"><PlusIcon class="w-5 h-5" />Nuevo usuario</button>
            </header>

            <section class="glass p-4 sm:p-5">
                <p v-if="loading && !usuarios.length" class="py-10 text-center section-desc">Cargando…</p>
                <p v-else-if="error" class="py-10 text-center" style="color: var(--red);">{{ error }}</p>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-[14px] min-w-[560px]">
                        <thead>
                            <tr class="text-left text-[13px]" style="color: var(--muted);">
                                <th class="py-2 px-2 font-medium">Usuario</th>
                                <th class="py-2 px-2 font-medium">Rol</th>
                                <th class="py-2 px-2 font-medium">Estado</th>
                                <th class="py-2 px-2 font-medium">Creado</th>
                                <th class="py-2 px-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="u in usuarios" :key="u.id" class="border-t" style="border-color: var(--input-border);">
                                <td class="py-3 px-2 font-semibold">{{ u.usuario }}<span v-if="u.id === yo" class="ml-2 text-[11px] font-normal" style="color: var(--muted);">(tú)</span></td>
                                <td class="py-3 px-2"><span class="px-2 py-1 rounded-full text-[12px] font-bold capitalize" :style="rolStyle(u.rol)">{{ u.rol }}</span></td>
                                <td class="py-3 px-2">
                                    <span class="px-2 py-1 rounded-full text-[12px] font-bold" :style="u.activo ? { background: 'var(--green-light)', color: 'var(--green-dark)' } : { background: '#EDF0F4', color: 'var(--muted)' }">{{ u.activo ? 'Activo' : 'Inactivo' }}</span>
                                </td>
                                <td class="py-3 px-2 whitespace-nowrap" style="color: var(--muted);">{{ (u.creadoEn || '').slice(0, 10) }}</td>
                                <td class="py-3 px-2">
                                    <div class="flex justify-end gap-2">
                                        <button class="btn btn-outline btn-sm" style="min-height: 36px; padding: 4px 10px;" title="Editar" @click="abrirEditar(u)"><PencilSquareIcon class="w-4 h-4" /></button>
                                        <button class="btn btn-outline btn-sm" style="min-height: 36px; padding: 4px 10px;" title="Eliminar" :disabled="u.id === yo" @click="eliminar(u)"><TrashIcon class="w-4 h-4" style="color: var(--red);" /></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- Crear / editar -->
        <div v-if="form" class="fixed inset-0 z-[1000] flex items-center justify-center p-4" style="background: rgba(11,31,77,.35);" @click.self="form = null">
            <form class="glass w-full max-w-[440px] max-h-[90vh] overflow-y-auto p-5 space-y-4" style="background: rgba(255,255,255,.94);" @submit.prevent="guardar">
                <div class="flex items-center justify-between">
                    <h2 class="section-title">{{ form.id ? 'Editar usuario' : 'Nuevo usuario' }}</h2>
                    <button type="button" class="btn btn-outline btn-sm" aria-label="Cerrar" @click="form = null"><XMarkIcon class="w-5 h-5" /></button>
                </div>
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
                <label class="flex items-center gap-2 text-[14px]">
                    <input v-model="form.activo" type="checkbox" class="w-4 h-4" :disabled="form.id === yo" />
                    Usuario activo
                </label>
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" class="btn btn-outline btn-sm" @click="form = null">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm" :disabled="guardando">{{ guardando ? 'Guardando…' : 'Guardar' }}</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { PlusIcon, PencilSquareIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import usuarioService from '../../services/usuarioService';
import authService from '../../services/authService';
import { toastSuccess, alertError, confirmDialog } from '../../utils/alerts';

const yo = authService.user()?.id;
const usuarios = ref([]);
const loading = ref(false);
const error = ref('');
const form = ref(null);
const guardando = ref(false);

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
