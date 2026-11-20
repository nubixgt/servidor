<template>
    <div>
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-ink-900">Usuarios</h1>
                <p class="mt-1 text-sm text-ink-500">Cuentas con acceso al sistema y su rol.</p>
            </div>
            <button class="btn-primario" @click="abrirNuevo">
                <PlusIcon class="h-4 w-4" />
                Nuevo usuario
            </button>
        </div>

        <section class="overflow-hidden rounded border border-rule bg-white">
            <div v-if="error" class="flex items-center justify-between gap-3 bg-danger/5 px-4 py-3 text-sm text-danger">
                <span>{{ error }}</span>
                <button class="btn-neutro px-3 py-1" @click="cargar">Reintentar</button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] border-collapse text-left text-[13px]">
                    <thead class="bg-ink-800 text-xs uppercase tracking-wide text-ink-300">
                        <tr>
                            <th class="px-4 py-2.5 font-medium">Usuario</th>
                            <th class="px-4 py-2.5 font-medium">Nombre</th>
                            <th class="px-4 py-2.5 font-medium">Rol</th>
                            <th class="px-4 py-2.5 font-medium">Estado</th>
                            <th class="px-4 py-2.5 font-medium">Último acceso</th>
                            <th class="w-24 px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in usuarios" :key="u.id" class="border-b border-rule/70 hover:bg-brand-50/50">
                            <td class="px-4 py-2.5 font-mono text-ink-900">
                                {{ u.usuario }}
                                <span v-if="u.id === auth.usuario?.id"
                                    class="ml-2 rounded bg-ink-800 px-1.5 py-0.5 font-sans text-[10px] uppercase tracking-wide text-white">Tú</span>
                            </td>
                            <td class="px-4 py-2.5 text-ink-800">{{ u.nombre }}</td>
                            <td class="px-4 py-2.5 text-ink-600">{{ u.rol === 'admin' ? 'Administrador' : 'Técnico' }}</td>
                            <td class="px-4 py-2.5">
                                <span class="inline-flex items-center gap-1.5"
                                    :class="u.activo ? 'text-brand-700' : 'text-ink-400'">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="u.activo ? 'bg-brand-500' : 'bg-ink-300'"></span>
                                    {{ u.activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-ink-600">{{ fechaHora(u.ultimo_acceso) || 'Nunca' }}</td>
                            <td class="px-4 py-2.5">
                                <div class="flex justify-end gap-1">
                                    <button class="rounded p-1.5 text-ink-400 hover:bg-paper hover:text-brand-700"
                                        :aria-label="`Editar ${u.usuario}`" title="Editar" @click="abrirEditar(u)">
                                        <PencilSquareIcon class="h-4 w-4" />
                                    </button>
                                    <button class="rounded p-1.5 text-ink-400 hover:bg-danger/5 hover:text-danger disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-ink-400"
                                        :aria-label="`Eliminar ${u.usuario}`" title="Eliminar"
                                        :disabled="u.id === auth.usuario?.id" @click="porEliminar = u">
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!cargando && usuarios.length === 0">
                            <td colspan="6" class="px-4 py-12 text-center text-ink-500">No hay usuarios.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Crear / editar -->
        <div v-if="formulario" class="fixed inset-0 z-40 grid place-items-center px-4">
            <div class="absolute inset-0 bg-ink-900/40" @click="formulario = null"></div>
            <form class="relative w-full max-w-md rounded bg-white shadow-2xl" role="dialog" aria-modal="true"
                aria-labelledby="form-titulo" novalidate @submit.prevent="guardar">
                <header class="border-b border-rule bg-paper px-5 py-4">
                    <h2 id="form-titulo" class="text-lg font-semibold text-ink-900">
                        {{ formulario.id ? 'Editar usuario' : 'Nuevo usuario' }}
                    </h2>
                </header>

                <div class="space-y-4 px-5 py-5">
                    <p v-if="errorForm" class="rounded border border-danger/30 bg-danger/5 px-3 py-2 text-sm text-danger"
                        role="alert">{{ errorForm }}</p>

                    <div>
                        <label class="etiqueta" for="u-usuario">Usuario</label>
                        <input id="u-usuario" v-model.trim="formulario.usuario" class="campo font-mono" maxlength="40"
                            autocomplete="off" autocapitalize="none" spellcheck="false" />
                        <p class="mt-1 text-xs text-ink-500">Letras, números, punto, guion o guion bajo.</p>
                    </div>

                    <div>
                        <label class="etiqueta" for="u-nombre">Nombre completo</label>
                        <input id="u-nombre" v-model="formulario.nombre" class="campo" maxlength="120" autocomplete="off" />
                    </div>

                    <div>
                        <label class="etiqueta" for="u-rol">Rol</label>
                        <select id="u-rol" v-model="formulario.rol" class="campo" :disabled="esMiCuenta">
                            <option value="tecnico">Técnico</option>
                            <option value="admin">Administrador</option>
                        </select>
                        <p v-if="esMiCuenta" class="mt-1 text-xs text-ink-500">No puedes cambiar tu propio rol.</p>
                    </div>

                    <div>
                        <label class="etiqueta" for="u-clave">
                            {{ formulario.id ? 'Nueva contraseña' : 'Contraseña' }}
                        </label>
                        <div class="relative">
                            <input id="u-clave" v-model="formulario.clave" class="campo pr-10"
                                :type="verClave ? 'text' : 'password'" autocomplete="new-password" maxlength="72"
                                :placeholder="formulario.id ? 'Déjala vacía para no cambiarla' : ''" />
                            <button type="button"
                                class="absolute inset-y-0 right-0 grid w-10 place-items-center text-ink-400 hover:text-ink-700"
                                :aria-label="verClave ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                @click="verClave = !verClave">
                                <component :is="verClave ? EyeSlashIcon : EyeIcon" class="h-4 w-4" />
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-ink-500">Mínimo 8 caracteres.</p>
                    </div>

                    <label class="flex items-center gap-2.5 text-sm text-ink-800" :class="{ 'opacity-60': esMiCuenta }">
                        <input v-model="formulario.activo" type="checkbox" :disabled="esMiCuenta"
                            class="h-4 w-4 rounded border-rule text-brand-600 focus:ring-brand-500" />
                        Cuenta activa (puede iniciar sesión)
                    </label>
                </div>

                <footer class="flex items-center justify-end gap-3 border-t border-rule bg-paper px-5 py-3">
                    <button type="button" class="btn-neutro" @click="formulario = null">Cancelar</button>
                    <button type="submit" class="btn-primario min-w-[7.5rem]" :disabled="guardando">
                        <span v-if="guardando"
                            class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        {{ guardando ? 'Guardando…' : 'Guardar' }}
                    </button>
                </footer>
            </form>
        </div>

        <!-- Confirmar eliminación -->
        <div v-if="porEliminar" class="fixed inset-0 z-40 grid place-items-center px-4">
            <div class="absolute inset-0 bg-ink-900/40" @click="porEliminar = null"></div>
            <div class="relative w-full max-w-sm rounded bg-white p-5 shadow-2xl" role="alertdialog" aria-modal="true">
                <h2 class="text-lg font-semibold text-ink-900">Eliminar usuario</h2>
                <p class="mt-2 text-sm text-ink-700">
                    ¿Eliminar a <span class="font-medium">{{ porEliminar.nombre }}</span>
                    (<span class="font-mono">{{ porEliminar.usuario }}</span>)? Esta acción no se puede deshacer.
                </p>
                <p v-if="porEliminar.seguimientos > 0"
                    class="mt-3 rounded border border-amber/30 bg-amber/10 px-3 py-2 text-sm text-ink-800">
                    Este usuario ya registró seguimientos, así que no se podrá eliminar. Mejor edítalo y desactívalo.
                </p>
                <div class="mt-5 flex justify-end gap-3">
                    <button class="btn-neutro" @click="porEliminar = null">Cancelar</button>
                    <button class="btn-peligro" :disabled="guardando" @click="eliminar">Sí, eliminar</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { EyeIcon, EyeSlashIcon, PencilSquareIcon, PlusIcon, TrashIcon } from '@heroicons/vue/20/solid';
import usuarioService from '../../services/usuarioService';
import { mensajeDeError } from '../../services/api';
import { useAuthStore } from '../../stores/auth';
import { fechaHora } from '../../utils/formato';
import { toast } from '../../utils/toast';

const auth = useAuthStore();

const usuarios = ref([]);
const cargando = ref(false);
const error = ref('');
const formulario = ref(null);   // null = cerrado; con `id` = editando
const errorForm = ref('');
const verClave = ref(false);
const guardando = ref(false);
const porEliminar = ref(null);

const esMiCuenta = computed(() => formulario.value?.id && formulario.value.id === auth.usuario?.id);

const cargar = async () => {
    cargando.value = true;
    error.value = '';
    try {
        usuarios.value = (await usuarioService.listar()).items;
    } catch (e) {
        error.value = mensajeDeError(e, 'No se pudo cargar la lista de usuarios');
    } finally {
        cargando.value = false;
    }
};

const abrirForm = (datos) => {
    errorForm.value = '';
    verClave.value = false;
    formulario.value = datos;
};

const abrirNuevo = () => abrirForm({ id: null, usuario: '', nombre: '', rol: 'tecnico', clave: '', activo: true });

const abrirEditar = (u) => abrirForm({ id: u.id, usuario: u.usuario, nombre: u.nombre, rol: u.rol, clave: '', activo: u.activo });

const guardar = async () => {
    const f = formulario.value;
    errorForm.value = '';
    if (!f.usuario || !f.nombre.trim()) {
        errorForm.value = 'Completa el usuario y el nombre';
        return;
    }
    if (!f.id && !f.clave) {
        errorForm.value = 'La contraseña es obligatoria';
        return;
    }
    guardando.value = true;
    try {
        const datos = { usuario: f.usuario, nombre: f.nombre, rol: f.rol, clave: f.clave, activo: f.activo };
        if (f.id) await usuarioService.actualizar(f.id, datos);
        else await usuarioService.crear(datos);
        toast.ok(f.id ? 'Usuario actualizado' : 'Usuario creado');
        formulario.value = null;
        await cargar();
    } catch (e) {
        errorForm.value = mensajeDeError(e, 'No se pudo guardar el usuario');
    } finally {
        guardando.value = false;
    }
};

const eliminar = async () => {
    guardando.value = true;
    try {
        await usuarioService.eliminar(porEliminar.value.id);
        toast.ok('Usuario eliminado');
        porEliminar.value = null;
        await cargar();
    } catch (e) {
        toast.error(mensajeDeError(e, 'No se pudo eliminar el usuario'));
        porEliminar.value = null;
    } finally {
        guardando.value = false;
    }
};

const alPresionarTecla = (e) => {
    if (e.key === 'Escape') {
        formulario.value = null;
        porEliminar.value = null;
    }
};

onMounted(() => {
    document.addEventListener('keydown', alPresionarTecla);
    cargar();
});
onBeforeUnmount(() => document.removeEventListener('keydown', alPresionarTecla));
</script>
