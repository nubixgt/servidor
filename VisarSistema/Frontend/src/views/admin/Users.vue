<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Gestión de Usuarios</h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm">Administra el acceso y los permisos del sistema.</p>
            </div>
            <button 
                @click="openModal()" 
                class="flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-xl shadow-lg shadow-primary/20 transition-all text-sm font-semibold min-h-[44px] touch-manipulation"
            >
                <UserPlusIcon class="w-5 h-5" />
                Nuevo Usuario
            </button>
        </div>

        <!-- Users Table -->
        <div class="bg-white dark:bg-[#1e293b] rounded-2xl shadow-sm border border-gray-100 dark:border-white/5 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-white/5 border-b border-gray-100 dark:border-white/5 text-xs uppercase text-gray-500 dark:text-gray-400">
                            <th class="px-6 py-4 font-semibold">Usuario</th>
                            <th class="px-6 py-4 font-semibold">Rol</th>
                            <th class="px-6 py-4 font-semibold">Estado</th>
                            <th class="px-6 py-4 font-semibold">Último Acceso</th>
                            <th class="px-6 py-4 font-semibold text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        <tr v-if="loading" class="animate-pulse">
                            <td colspan="4" class="px-6 py-8 text-center text-gray-400 text-sm">Cargando usuarios...</td>
                        </tr>
                        <tr v-else-if="users.length === 0">
                            <td colspan="4" class="px-6 py-8 text-center text-gray-400 text-sm">No hay usuarios registrados.</td>
                        </tr>
                        <tr 
                            v-else 
                            v-for="user in users" 
                            :key="user.id" 
                            class="hover:bg-gray-50/50 dark:hover:bg-white/5 transition-colors group"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-brand to-blue-500 flex items-center justify-center text-white font-bold text-sm">
                                        {{ getInitials(user.nombres, user.apellidos) }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ user.nombres }} {{ user.apellidos }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ user.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="`px-3 py-1 rounded-full text-xs font-medium border ${getRoleBadgeClass(user.role)}`">
                                    {{ user.role }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="`inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium ${user.status == 1 ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400'}`">
                                    <span :class="`w-1.5 h-1.5 rounded-full ${user.status == 1 ? 'bg-green-500' : 'bg-red-500'}`"></span>
                                    {{ user.status == 1 ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ formatDate(user.ultimo_acceso) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button 
                                        @click="openModal(user)"
                                        class="p-2 text-gray-400 hover:text-brand-dark dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-white/10 rounded-lg transition-colors"
                                        title="Editar"
                                    >
                                        <PencilSquareIcon class="w-5 h-5" />
                                    </button>
                                    <button 
                                        v-if="user.id !== currentUserId"
                                        @click="deleteUser(user)"
                                        class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors"
                                        title="Eliminar"
                                    >
                                        <TrashIcon class="w-5 h-5" />
                                    </button>
                                    <button 
                                        @click="openPermissionsModal(user)"
                                        class="p-2 text-gray-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-500/10 rounded-lg transition-colors"
                                        title="Permisos"
                                    >
                                        <KeyIcon class="w-5 h-5" />
                                    </button>
                                    <button 
                                        @click="resetPassword(user)"
                                        class="p-2 text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-500/10 rounded-lg transition-colors"
                                        title="Restablecer Contraseña"
                                    >
                                        <LockClosedIcon class="w-5 h-5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- User Modal (Edit/Create) -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-[#1e293b] rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden animate-fade-in relative">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-100 dark:border-white/5 flex justify-between items-center bg-gray-50/50 dark:bg-white/5">
                    <h3 class="font-bold text-lg text-gray-800 dark:text-white">
                        {{ isEditing ? 'Editar Usuario' : 'Nuevo Usuario' }}
                    </h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <XMarkIcon class="w-6 h-6" />
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 max-h-[80vh] overflow-y-auto custom-scrollbar space-y-6">
                    
                    <!-- Basic Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-500 uppercase">Nombres</label>
                            <input v-model="form.nombres" type="text" class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand/20 focus:border-brand dark:text-white outline-none" placeholder="Ej. Juan Carlos">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-500 uppercase">Apellidos</label>
                            <input v-model="form.apellidos" type="text" class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand/20 focus:border-brand dark:text-white outline-none" placeholder="Ej. Pérez López">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-500 uppercase">Email</label>
                            <input v-model="form.email" type="email" class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand/20 focus:border-brand dark:text-white outline-none" placeholder="correo@ejemplo.com">
                        </div>
                         <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-500 uppercase">Usuario</label>
                            <input v-model="form.username" type="text" class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand/20 focus:border-brand dark:text-white outline-none" placeholder="jperez">
                        </div>
                         <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-500 uppercase">Puesto Funcional</label>
                            <input v-model="form.puesto_funcional" type="text" class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand/20 focus:border-brand dark:text-white outline-none" placeholder="">
                        </div>
                         <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-500 uppercase">Ubicación / Dependencia</label>
                            <input v-model="form.ubicacion_laboral" type="text" class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand/20 focus:border-brand dark:text-white outline-none" placeholder="">
                        </div>
                    </div>

                    <!-- Security -->
                    <div class="pt-4 border-t border-gray-100 dark:border-white/5">
                        <h4 class="font-bold text-sm text-gray-800 dark:text-white mb-3">Seguridad y Acceso</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-gray-500 uppercase">Rol del Sistema</label>
                                <select v-model="form.role" class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand/20 focus:border-brand dark:text-white outline-none">
                                    <option value="ADMIN">Administrador</option>
                                    <option value="TECNICO">Técnico</option>
                                    <option value="PROFESIONAL">Profesional</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-gray-500 uppercase">Estado</label>
                                <select v-model="form.status" class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand/20 focus:border-brand dark:text-white outline-none">
                                    <option :value="1">Activo</option>
                                    <option :value="0">Inactivo</option>
                                </select>
                            </div>
                            <div class="md:col-span-2 space-y-1">
                                <label class="text-xs font-bold text-gray-500 uppercase">Contraseña <span v-if="isEditing" class="font-normal normal-case text-gray-400">(Dejar en blanco para mantener actual)</span></label>
                                <input v-model="form.password" type="password" class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand/20 focus:border-brand dark:text-white outline-none" placeholder="••••••••">
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Modal Footer -->
               <div class="px-6 py-4 border-t border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-white/5 flex justify-end gap-3">
                    <button @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10 rounded-lg transition-colors">Cancelar</button>
                    <button @click="saveUser" :disabled="saving" class="bg-brand hover:bg-brand-dark text-white px-6 py-2 rounded-lg text-sm font-medium shadow-lg shadow-brand/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ saving ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Crear Usuario') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Permissions Modal -->
        <div v-if="showPermissions" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-[#1e293b] rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden animate-fade-in relative">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-100 dark:border-white/5 flex justify-between items-center bg-gray-50/50 dark:bg-white/5">
                    <h3 class="font-bold text-lg text-gray-800 dark:text-white">
                        Permisos de Usuario
                    </h3>
                    <button @click="showPermissions = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <XMarkIcon class="w-6 h-6" />
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 max-h-[80vh] overflow-y-auto custom-scrollbar space-y-4">
                    <p class="text-sm text-gray-500 mb-2">
                        Configura los accesos adicionales para <span class="font-bold text-gray-800 dark:text-white">{{ permissionsForm.nombres }}</span>.
                    </p>

                    <div class="bg-gray-50 dark:bg-black/20 rounded-xl p-4 border border-gray-100 dark:border-white/5 space-y-3">
                        <label class="flex items-center gap-3 p-2 hover:bg-white dark:hover:bg-white/5 rounded-lg transition-colors cursor-pointer border-b border-gray-100 dark:border-white/5">
                            <input type="checkbox" v-model="permissionsForm.permissions.technical_sheet" class="w-4 h-4 text-brand rounded focus:ring-brand border-gray-300">
                            <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">Acceso a Ficha Técnica (Dashboard)</span>
                        </label>
                        
                        <div class="pt-2">
                            <h5 class="text-xs font-bold text-gray-500 uppercase mb-2">Permisos por Módulo</h5>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <label class="flex items-center gap-3 p-2 hover:bg-white dark:hover:bg-white/5 rounded-lg transition-colors cursor-pointer">
                                    <input type="checkbox" v-model="permissionsForm.permissions.modulo_visan" class="w-4 h-4 text-purple-500 rounded focus:ring-purple-500 border-gray-300">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Módulo VISAN</span>
                                </label>
                                <label class="flex items-center gap-3 p-2 hover:bg-white dark:hover:bg-white/5 rounded-lg transition-colors cursor-pointer">
                                    <input type="checkbox" v-model="permissionsForm.permissions.modulo_visar" class="w-4 h-4 text-purple-500 rounded focus:ring-purple-500 border-gray-300">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Módulo VISAR</span>
                                </label>
                                <label class="flex items-center gap-3 p-2 hover:bg-white dark:hover:bg-white/5 rounded-lg transition-colors cursor-pointer">
                                    <input type="checkbox" v-model="permissionsForm.permissions.modulo_clima" class="w-4 h-4 text-purple-500 rounded focus:ring-purple-500 border-gray-300">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Módulo de Clima</span>
                                </label>
                                <label class="flex items-center gap-3 p-2 hover:bg-white dark:hover:bg-white/5 rounded-lg transition-colors cursor-pointer">
                                    <input type="checkbox" v-model="permissionsForm.permissions.modulo_despacho" class="w-4 h-4 text-purple-500 rounded focus:ring-purple-500 border-gray-300">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Actividades Despacho</span>
                                </label>
                                <label class="flex items-center gap-3 p-2 hover:bg-white dark:hover:bg-white/5 rounded-lg transition-colors cursor-pointer">
                                    <input type="checkbox" v-model="permissionsForm.permissions.modulo_presupuesto" class="w-4 h-4 text-purple-500 rounded focus:ring-purple-500 border-gray-300">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Presupuesto Institucional</span>
                                </label>
                                <label class="flex items-center gap-3 p-2 hover:bg-white dark:hover:bg-white/5 rounded-lg transition-colors cursor-pointer">
                                    <input type="checkbox" v-model="permissionsForm.permissions.modulo_votaciones" class="w-4 h-4 text-purple-500 rounded focus:ring-purple-500 border-gray-300">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Sistema Votaciones</span>
                                </label>
                                <label class="flex items-center gap-3 p-2 hover:bg-white dark:hover:bg-white/5 rounded-lg transition-colors cursor-pointer">
                                    <input type="checkbox" v-model="permissionsForm.permissions.modulo_productores" class="w-4 h-4 text-purple-500 rounded focus:ring-purple-500 border-gray-300">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Módulo Productores</span>
                                </label>
                                <label class="flex items-center gap-3 p-2 hover:bg-white dark:hover:bg-white/5 rounded-lg transition-colors cursor-pointer">
                                    <input type="checkbox" v-model="permissionsForm.permissions.modulo_sanidad" class="w-4 h-4 text-purple-500 rounded focus:ring-purple-500 border-gray-300">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Módulo Sanidad</span>
                                </label>
                                <label class="flex items-center gap-3 p-2 hover:bg-white dark:hover:bg-white/5 rounded-lg transition-colors cursor-pointer">
                                    <input type="checkbox" v-model="permissionsForm.permissions.modulo_licencias" class="w-4 h-4 text-purple-500 rounded focus:ring-purple-500 border-gray-300">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Licencias Generales</span>
                                </label>
                                <label class="flex items-center gap-3 p-2 hover:bg-white dark:hover:bg-white/5 rounded-lg transition-colors cursor-pointer">
                                    <input type="checkbox" v-model="permissionsForm.permissions.modulo_extension" class="w-4 h-4 text-purple-500 rounded focus:ring-purple-500 border-gray-300">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Extensión Rural</span>
                                </label>
                                <label class="flex items-center gap-3 p-2 hover:bg-white dark:hover:bg-white/5 rounded-lg transition-colors cursor-pointer">
                                    <input type="checkbox" v-model="permissionsForm.permissions.modulo_reportes" class="w-4 h-4 text-purple-500 rounded focus:ring-purple-500 border-gray-300">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Módulo Reportes</span>
                                </label>
                                <label class="flex items-center gap-3 p-2 hover:bg-white dark:hover:bg-white/5 rounded-lg transition-colors cursor-pointer">
                                    <input type="checkbox" v-model="permissionsForm.permissions.modulo_vider" class="w-4 h-4 text-purple-500 rounded focus:ring-purple-500 border-gray-300">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Módulo VIDER</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <div class="flex items-center justify-between mb-2">
                                <h5 class="text-xs font-bold text-gray-500 uppercase">Formularios Disponibles</h5>
                                <button 
                                    @click="toggleAllForms"
                                    class="text-xs font-medium text-brand hover:text-brand-dark transition-colors"
                                >
                                    {{ isAllSelected ? 'Deseleccionar todos' : 'Seleccionar todos' }}
                                </button>
                            </div>
                            <div class="grid grid-cols-1 gap-2 bg-white dark:bg-black/20 rounded-lg p-2 max-h-60 overflow-y-auto custom-scrollbar">
                                <label 
                                    v-for="schema in FORM_SCHEMAS" 
                                    :key="schema.id"
                                    class="flex items-center gap-3 p-2 hover:bg-gray-50 dark:hover:bg-white/5 rounded-lg transition-colors cursor-pointer"
                                >
                                    <input 
                                        type="checkbox" 
                                        :value="schema.id" 
                                        v-model="permissionsForm.permissions.forms" 
                                        class="w-4 h-4 text-brand rounded focus:ring-brand border-gray-300"
                                    >
                                    <div>
                                        <span class="text-sm text-gray-700 dark:text-gray-300 block">{{ schema.label }}</span>
                                        <span class="text-xs text-gray-400 block">{{ schema.description }}</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
               <div class="px-6 py-4 border-t border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-white/5 flex justify-end gap-3">
                    <button @click="showPermissions = false" class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10 rounded-lg transition-colors">Cancelar</button>
                    <button @click="savePermissions" :disabled="savingPermissions" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg text-sm font-medium shadow-lg shadow-purple-600/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ savingPermissions ? 'Guardando...' : 'Guardar Permisos' }}
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { 
    UserPlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    XMarkIcon,
    KeyIcon,
    LockClosedIcon
} from '@heroicons/vue/24/outline';
import api from '../../services/api';
import Swal from 'sweetalert2';

const users = ref([]);
const loading = ref(true);
const showModal = ref(false);
const showPermissions = ref(false); // Permissions Modal State
const saving = ref(false);
const savingPermissions = ref(false); // Permissions Saving State
const isEditing = ref(false);
const currentUserId = ref(null);

const form = ref({
    id: null,
    nombres: '',
    apellidos: '',
    email: '',
    username: '',
    password: '',
    role: 'TECNICO',
    status: 1,
    puesto_funcional: '',
    ubicacion_laboral: ''
});

const permissionsForm = ref({
    id: null,
    nombres: '',
    permissions: {
        technical_sheet: false,
        modulo_visan: false,
        modulo_visar: false,
        modulo_clima: false,
        modulo_despacho: false,
        modulo_presupuesto: false,
        modulo_votaciones: false,
        modulo_productores: false,
        modulo_sanidad: false,
        modulo_licencias: false,
        modulo_extension: false,
        modulo_reportes: false,
        modulo_vider: false,
        forms: [] // Array of allowed form IDs
    }
});

import { FORM_SCHEMAS } from '../../config/FormSchemas';

const loadUsers = async () => {
    loading.value = true;
    try {
        const { data } = await api.get('/users');
        if (data.success) {
            users.value = data.data;
        }
    } catch (e) {
        console.error("Error loading users", e);
        Swal.fire('Error', 'No se pudieron cargar los usuarios', 'error');
    } finally {
        loading.value = false;
    }
};

const openModal = (user = null) => {
    if (user) {
        isEditing.value = true;
        form.value = {
            id: user.id,
            nombres: user.nombres,
            apellidos: user.apellidos,
            email: user.email,
            username: user.username,
            password: '', // Don't populate
            role: user.role || 'TECNICO',
            status: user.status,
            puesto_funcional: user.puesto_funcional || '',
            ubicacion_laboral: user.ubicacion_laboral || ''
        };
    } else {
        isEditing.value = false;
        form.value = {
            id: null,
            nombres: '',
            apellidos: '',
            email: '',
            username: '',
            password: '',
            role: 'TECNICO',
            status: 1,
            puesto_funcional: '',
            ubicacion_laboral: ''
        };
    }
    showModal.value = true;
};

const openPermissionsModal = (user) => {
    // Deep copy to disconnect from the main user list until saved
    let initialPermissions = { 
        technical_sheet: false, 
        modulo_visan: false,
        modulo_visar: false,
        modulo_clima: false,
        modulo_despacho: false,
        modulo_presupuesto: false,
        modulo_votaciones: false,
        modulo_productores: false,
        modulo_sanidad: false,
        modulo_licencias: false,
        modulo_extension: false,
        modulo_reportes: false,
        modulo_vider: false,
        forms: [] 
    };
    
    if (user.permissions) {
        // Handle if permissions comes as string (rare but possible in some setups) or object
        const perms = typeof user.permissions === 'string' ? JSON.parse(user.permissions) : user.permissions;
        initialPermissions = {
            technical_sheet: !!perms.technical_sheet,
            modulo_visan: !!perms.modulo_visan,
            modulo_visar: !!perms.modulo_visar,
            modulo_clima: !!perms.modulo_clima,
            modulo_despacho: !!perms.modulo_despacho,
            modulo_presupuesto: !!perms.modulo_presupuesto,
            modulo_votaciones: !!perms.modulo_votaciones,
            modulo_productores: !!perms.modulo_productores,
            modulo_sanidad: !!perms.modulo_sanidad,
            modulo_licencias: !!perms.modulo_licencias,
            modulo_extension: !!perms.modulo_extension,
            modulo_reportes: !!perms.modulo_reportes,
            modulo_vider: !!perms.modulo_vider,
            forms: [...(perms.forms || [])]
        };
    }

    permissionsForm.value = {
        id: user.id,
        nombres: user.nombres,
        permissions: initialPermissions
    };
    
    showPermissions.value = true;
};

const toggleAllForms = () => {
    const allFormIds = FORM_SCHEMAS.map(s => s.id);
    const currentForms = permissionsForm.value.permissions.forms;
    
    // If all are selected (or more), deselect all. Otherwise, select all.
    if (currentForms.length === allFormIds.length) {
        permissionsForm.value.permissions.forms = [];
    } else {
        permissionsForm.value.permissions.forms = [...allFormIds];
    }
};

const isAllSelected = computed(() => {
    return permissionsForm.value.permissions.forms.length === FORM_SCHEMAS.length;
});

const savePermissions = async () => {
    savingPermissions.value = true;
    try {
        // 1. Fetch current user data to ensure we have the latest state and don't overwrite other fields with null
        const { data: userData } = await api.get(`/users/${permissionsForm.value.id}`);
        
        if (!userData.success) {
            throw new Error('No se pudo obtener la información del usuario');
        }

        const currentUser = userData.data;

        // 2. Merge new permissions
        const updatedUser = {
            ...currentUser,
            permissions: permissionsForm.value.permissions
        };

        // 3. Send the full user object
        // Note: We might need to handle specific fields that shouldn't be sent (like password if empty), 
        // but usually the backend ignores null passwords or we can reconstruct the object carefully.
        const { data } = await api.put(`/users/${permissionsForm.value.id}`, updatedUser);

        if (data.success) {
            Swal.fire('Éxito', 'Permisos actualizados correctamente', 'success');
            showPermissions.value = false;
            loadUsers();
        } else {
            throw new Error(data.message);
        }
    } catch (e) {
        console.error("Error saving permissions", e);
        Swal.fire('Error', e.response?.data?.message || 'Error al guardar permisos', 'error');
    } finally {
        savingPermissions.value = false;
    }
};

const saveUser = async () => {
    if (!form.value.nombres || !form.value.apellidos || !form.value.email || !form.value.username) {
        Swal.fire('Atención', 'Por favor completa los campos obligatorios', 'warning');
        return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(form.value.email)) {
        Swal.fire('Atención', 'El correo electrónico no es válido', 'warning');
        return;
    }

    if (!isEditing.value && !form.value.password) {
        Swal.fire('Atención', 'La contraseña es obligatoria para nuevos usuarios', 'warning');
        return;
    }

    if (form.value.password && form.value.password.length < 8) {
        Swal.fire('Atención', 'La contraseña debe tener al menos 8 caracteres', 'warning');
        return;
    }

    saving.value = true;
    try {
        let response;
        if (isEditing.value) {
            response = await api.put(`/users/${form.value.id}`, form.value);
        } else {
            response = await api.post('/users', form.value);
        }

        if (response.data.success) {
            Swal.fire('Éxito', response.data.message, 'success');
            showModal.value = false;
            loadUsers();
        } else {
            throw new Error(response.data.message);
        }
    } catch (e) {
        console.error("Error saving user", e);
        Swal.fire('Error', e.response?.data?.message || 'Ocurrió un error al guardar', 'error');
    } finally {
        saving.value = false;
    }
};

const deleteUser = async (user) => {
    const result = await Swal.fire({
        title: '¿Estás seguro?',
        text: `Se eliminará al usuario ${user.nombres} ${user.apellidos}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const { data } = await api.delete(`/users/${user.id}`);
            if (data.success) {
                Swal.fire('Eliminado', 'Usuario eliminado correctamente', 'success');
                loadUsers();
            } else {
                throw new Error(data.message);
            }
        } catch (e) {
             Swal.fire('Error', e.response?.data?.message || 'No se pudo eliminar el usuario', 'error');
        }
    }
};

const resetPassword = async (user) => {
    const { value: newPassword } = await Swal.fire({
        title: 'Restablecer Contraseña',
        text: `Ingresa la nueva contraseña para ${user.nombres} ${user.apellidos}:`,
        input: 'password',
        inputPlaceholder: 'Nueva contraseña',
        showCancelButton: true,
        confirmButtonText: 'Restablecer',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (!value) {
                return '¡Debes ingresar una contraseña!';
            }
            if (value.length < 6) {
                return 'La contraseña debe tener al menos 6 caracteres';
            }
        }
    });

    if (newPassword) {
        try {
            const { data } = await api.post(`/users/${user.id}/reset-password`, { password: newPassword });
            if (data.success) {
                Swal.fire('¡Éxito!', `Contraseña de ${user.nombres} restablecida correctamente.`, 'success');
            } else {
                throw new Error(data.message);
            }
        } catch (e) {
            console.error(e);
            Swal.fire('Error', e.response?.data?.message || 'No se pudo restablecer la contraseña', 'error');
        }
    }
};

const formatDate = (dateString) => {
    if (!dateString) return 'Nunca';
    const date = new Date(dateString);
    return date.toLocaleString('es-GT', { 
        year: 'numeric', month: 'short', day: 'numeric', 
        hour: '2-digit', minute: '2-digit' 
    });
};

const getInitials = (n, a) => {
    return ((n ? n[0] : '') + (a ? a[0] : '')).toUpperCase();
};

const getRoleBadgeClass = (role) => {
    switch (role) {
        case 'ADMIN': return 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/20';
        case 'PROFESIONAL': return 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20';
        default: return 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-700/50 dark:text-gray-400 dark:border-gray-600';
    }
};

onMounted(() => {
    loadUsers();
    // Get current user ID from local storage to prevent self-deletion
    try {
        const user = JSON.parse(localStorage.getItem('user'));
        if (user && user.sub) currentUserId.value = user.sub; // 'sub' is usually the ID in JWT
    } catch(e){}
});
</script>
