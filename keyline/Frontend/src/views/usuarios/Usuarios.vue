<template>
    <div class="space-y-6 max-w-[1600px] mx-auto pb-4">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Usuarios y equipo de campo</h2>
                <p class="text-xs sm:text-sm text-white/80 mt-0.5">Gestión de roles técnicos, supervisores regionales y administradores KeylineGT.</p>
            </div>
            <button
                v-if="auth.role === 'administrador'"
                @click="openModal()"
                class="flex items-center space-x-2 px-4 py-2.5 bg-[#22c55e] hover:bg-[#16a34a] text-black rounded-xl text-xs font-semibold tracking-wider uppercase transition-all shadow-[0_0_20px_rgba(34,197,94,0.4)]"
            >
                <UserPlus class="w-4 h-4" />
                <span>Nuevo usuario</span>
            </button>
        </div>

        <!-- KPI stats -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5 flex flex-col justify-between">
                <span class="text-[11px] font-bold tracking-wider text-white/60 uppercase">Total usuarios</span>
                <div class="mt-2">
                    <div class="text-3xl font-bold text-white">{{ usuarios.length }}</div>
                    <p class="text-xs text-[#4ade80] font-semibold mt-1">Activos en la plataforma</p>
                </div>
            </div>
            <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5 flex flex-col justify-between">
                <span class="text-[11px] font-bold tracking-wider text-white/60 uppercase">Técnicos de campo</span>
                <div class="mt-2">
                    <div class="text-3xl font-bold text-white">{{ usuarios.filter(u => u.role === 'tecnico').length }}</div>
                    <p class="text-xs text-white/80 mt-1">Levantamiento de parcelas</p>
                </div>
            </div>
            <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-5 flex flex-col justify-between">
                <span class="text-[11px] font-bold tracking-wider text-white/60 uppercase">Supervisores &amp; admins</span>
                <div class="mt-2">
                    <div class="text-3xl font-bold text-white">{{ usuarios.filter(u => u.role === 'supervisor' || u.role === 'administrador').length }}</div>
                    <p class="text-xs text-[#38bdf8] font-semibold mt-1">Validación y control de calidad</p>
                </div>
            </div>
        </div>

        <!-- Search & role filter -->
        <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-4 flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
            <div class="flex-1 relative group">
                <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-white/40 group-focus-within:text-white transition-colors" />
                <input
                    v-model="searchTerm"
                    placeholder="Buscar por nombre, usuario o correo..."
                    class="w-full bg-white/5 border border-white/15 rounded-xl py-2 pl-10 pr-4 text-xs text-white focus:outline-none focus:border-white/50 transition-all placeholder:text-white/40"
                />
            </div>
            <div class="flex items-center gap-2 bg-white/5 border border-white/15 rounded-xl px-3 py-1.5">
                <span class="text-[10px] text-white/60 font-bold uppercase tracking-wider">Rol:</span>
                <select v-model="roleFilter" class="bg-transparent text-xs text-white focus:outline-none cursor-pointer">
                    <option value="">Todos</option>
                    <option v-for="r in ROLES" :key="r" :value="r">{{ ROLE_LABELS[r] }}</option>
                </select>
            </div>
        </div>

        <div v-if="loading" class="py-12 text-center text-xs text-white/60">Cargando…</div>
        <div v-else-if="!filteredUsuarios.length" class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] p-10 text-center text-xs text-white/60">No hay usuarios que coincidan.</div>

        <!-- User grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <div v-for="u in filteredUsuarios" :key="u.id" class="bg-white/10 border border-white/20 hover:border-white/40 rounded-2xl p-5 flex flex-col justify-between transition-all duration-200">
                <div>
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl bg-black/30 border border-white/20 flex items-center justify-center text-sm font-bold text-[#4ade80] shadow-sm flex-shrink-0">
                                {{ userInitials(u.nombre) }}
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white leading-tight">{{ u.nombre }}</h3>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full inline-block mt-1 border" :class="ROLE_BADGE[u.role]">{{ ROLE_LABELS[u.role] }}</span>
                            </div>
                        </div>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full border whitespace-nowrap" :class="u.activo === false ? 'bg-black/40 text-white/60 border-white/10' : 'bg-[#22c55e]/20 text-[#4ade80] border-[#4ade80]/30'">
                            {{ u.activo === false ? 'Inactivo' : 'Activo' }}
                        </span>
                    </div>

                    <div class="space-y-1.5 text-xs text-white/80 mt-3">
                        <div class="flex items-center gap-2">
                            <Mail class="w-3.5 h-3.5 text-white/60 flex-shrink-0" />
                            <span class="truncate">{{ u.email || 'Sin correo' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <MapPin class="w-3.5 h-3.5 text-[#38bdf8] flex-shrink-0" />
                            <span>{{ u.regionAsignada || 'Sin asignar' }}</span>
                        </div>
                        <div v-if="u.telefono" class="flex items-center gap-2">
                            <Phone class="w-3.5 h-3.5 text-[#4ade80] flex-shrink-0" />
                            <span>{{ u.telefono }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Clock class="w-3.5 h-3.5 text-white/40 flex-shrink-0" />
                            <span>{{ u.ultimoAcceso ? new Date(u.ultimoAcceso).toLocaleString('es-GT') : 'Nunca' }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-5 pt-3 border-t border-white/10 flex items-center justify-between text-xs">
                    <span class="text-[11px] text-white/60">@{{ u.usuario }}</span>
                    <div v-if="auth.role === 'administrador'" class="flex items-center gap-1.5">
                        <button @click="openModal(u)" class="p-1.5 text-white/60 hover:text-white hover:bg-white/10 rounded-lg transition-colors" title="Editar usuario">
                            <Pencil class="w-3.5 h-3.5" />
                        </button>
                        <button @click="eliminar(u)" class="p-1.5 text-white/60 hover:text-[#f87171] hover:bg-[#ef4444]/10 rounded-lg transition-colors" title="Eliminar usuario">
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 flex items-center justify-center p-4 animate-fadeIn" @click.self="showModal = false">
            <div class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex justify-between items-center pb-2 border-b border-white/15">
                    <h3 class="text-base font-bold text-white">{{ editing ? 'Editar usuario' : 'Nuevo miembro del equipo' }}</h3>
                    <button @click="showModal = false" class="text-white/60 hover:text-white"><X class="w-5 h-5" /></button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-white/80 block mb-1">Nombre completo</label>
                        <input v-model="form.nombre" placeholder="ej. Carlos Morales" class="w-full bg-white/5 border border-white/15 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-white/50" />
                    </div>
                    <div>
                        <label class="text-xs text-white/80 block mb-1">Usuario</label>
                        <input v-model="form.usuario" :disabled="editing" placeholder="nombre.usuario" class="w-full bg-white/5 border border-white/15 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-white/50 disabled:opacity-50" />
                    </div>
                    <div>
                        <label class="text-xs text-white/80 block mb-1">Correo institucional (opcional)</label>
                        <input v-model="form.email" type="email" placeholder="c.morales@keylinegt.com" class="w-full bg-white/5 border border-white/15 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-white/50" />
                    </div>
                    <div>
                        <label class="text-xs text-white/80 block mb-1">{{ editing ? 'Nueva contraseña (opcional)' : 'Contraseña' }}</label>
                        <input v-model="form.password" type="password" :placeholder="editing ? 'Dejar en blanco para no cambiar' : 'Mínimo 6 caracteres'" class="w-full bg-white/5 border border-white/15 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-white/50" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-white/80 block mb-1">Rol asignado</label>
                            <select v-model="form.role" class="w-full bg-white/5 border border-white/15 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-white/50">
                                <option v-for="r in ROLES" :key="r" :value="r">{{ ROLE_LABELS[r] }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-white/80 block mb-1">Departamento / región</label>
                            <select v-model="form.regionAsignada" class="w-full bg-white/5 border border-white/15 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-white/50">
                                <option value="">Sin asignar / nacional</option>
                                <option v-for="d in DEPARTAMENTOS" :key="d" :value="d">{{ d }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs text-white/80 block mb-1">Teléfono móvil</label>
                        <input
                            :value="form.telefono"
                            @input="onPhoneInput"
                            inputmode="numeric"
                            maxlength="9"
                            placeholder="0000-0000"
                            class="w-full bg-white/5 border border-white/15 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-white/50"
                        />
                    </div>

                    <label v-if="editing" class="flex items-center gap-2 text-xs text-white/80 cursor-pointer select-none">
                        <input v-model="form.activo" type="checkbox" class="rounded border-white/20 text-[#22c55e] focus:ring-0 bg-white/10 w-4 h-4" />
                        <span>Usuario activo</span>
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-white/15">
                    <button @click="showModal = false" class="px-4 py-2 bg-white/10 hover:bg-white/15 text-xs text-white/80 rounded-xl">Cancelar</button>
                    <button @click="save" :disabled="saving" class="px-4 py-2 bg-[#22c55e] hover:bg-[#16a34a] text-xs font-bold text-black rounded-xl shadow-lg disabled:opacity-60">
                        {{ editing ? 'Guardar cambios' : 'Crear usuario' }}
                    </button>
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
import { UserPlus, Search, Mail, MapPin, Phone, Clock, Pencil, Trash2, X } from '@lucide/vue';

const ROLE_BADGE = {
    administrador: 'bg-[#ef4444]/20 text-[#fca5a5] border-[#ef4444]/30',
    supervisor: 'bg-[#38bdf8]/20 text-[#7dd3fc] border-[#38bdf8]/30',
    tecnico: 'bg-[#22c55e]/20 text-[#4ade80] border-[#4ade80]/30',
};

const auth = useAuthStore();
const usuarios = ref([]);
const loading = ref(true);
const showModal = ref(false);
const editingUser = ref(null);
const saving = ref(false);
const editing = computed(() => !!editingUser.value);

const searchTerm = ref('');
const roleFilter = ref('');

const filteredUsuarios = computed(() => usuarios.value.filter((u) => {
    const term = searchTerm.value.toLowerCase();
    const matchesSearch = !term
        || u.nombre.toLowerCase().includes(term)
        || u.usuario.toLowerCase().includes(term)
        || (u.email || '').toLowerCase().includes(term);
    const matchesRole = !roleFilter.value || u.role === roleFilter.value;
    return matchesSearch && matchesRole;
}));

const form = reactive({ nombre: '', usuario: '', email: '', password: '', role: 'tecnico', regionAsignada: '', telefono: '', activo: true });

function userInitials(nombre) {
    const parts = (nombre || '').trim().split(/\s+/);
    return ((parts[0]?.[0] || '') + (parts[1]?.[0] || '')).toUpperCase() || '--';
}

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
    showModal.value = true;
    editingUser.value = user;
    form.nombre = user?.nombre || '';
    form.usuario = user?.usuario || '';
    form.email = user?.email || '';
    form.password = '';
    form.role = user?.role || 'tecnico';
    form.regionAsignada = user?.regionAsignada || '';
    form.telefono = user?.telefono || '';
    form.activo = user?.activo !== false;
}

function onPhoneInput(e) {
    const digits = e.target.value.replace(/\D/g, '').slice(0, 8);
    form.telefono = digits.length > 4 ? `${digits.slice(0, 4)}-${digits.slice(4)}` : digits;
    e.target.value = form.telefono;
}

async function save() {
    if (!form.nombre || (!editing.value && (!form.usuario || !form.password))) {
        alertError('Nombre, usuario y contraseña son obligatorios.');
        return;
    }
    saving.value = true;
    const wasEditing = editing.value;
    try {
        const payload = { ...form };
        if (!payload.password) delete payload.password;
        if (wasEditing) {
            await usuarioService.actualizar(editingUser.value.id, payload);
        } else {
            await usuarioService.crear(payload);
        }
        showModal.value = false;
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
