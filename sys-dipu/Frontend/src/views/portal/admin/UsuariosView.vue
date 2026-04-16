<template>
    <div class="space-y-8">
        <header class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
            <div class="max-w-2xl">
                <h1 class="text-[2.75rem] leading-[1.2] font-extrabold text-on-surface tracking-tight mb-2 font-headline">Gestión de Usuarios</h1>
                <p class="text-on-surface-variant text-lg leading-relaxed">Administración de accesos, roles y permisos para el personal del despacho y equipo técnico.</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="showUserModal = true" class="px-6 py-2.5 bg-gradient-to-br from-primary to-primary-dim text-on-primary font-semibold rounded-lg flex items-center gap-2 shadow-lg shadow-primary/10 transition-all hover:shadow-xl active:scale-95">
                    <span class="material-symbols-outlined text-xl">person_add</span> Nuevo Usuario
                </button>
            </div>
        </header>

        <!-- KPI Cards -->
        <div class="grid grid-cols-12 gap-6 mb-10">
            <div class="col-span-12 lg:col-span-4 bg-surface-container-low rounded-2xl p-6 flex flex-col justify-between min-h-[160px]">
                <span class="text-on-surface-variant font-medium text-sm uppercase tracking-wider">Total Usuarios</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-5xl font-extrabold text-on-surface font-headline">{{ filteredUsuarios.length }}</span>
                    <span class="text-primary font-bold text-sm">Mostrados</span>
                </div>
            </div>
            <div class="col-span-12 lg:col-span-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Administradores</span>
                        <span class="w-2 h-2 rounded-full bg-tertiary"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">{{ administradoresCount }}</span>
                </div>
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Técnicos</span>
                        <span class="w-2 h-2 rounded-full bg-primary"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">{{ tecnicosCount }}</span>
                </div>
                <div class="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="text-on-surface-variant font-medium text-sm">Inactivos</span>
                        <span class="w-2 h-2 rounded-full bg-error"></span>
                    </div>
                    <span class="text-3xl font-bold text-on-surface font-headline">{{ inactivosCount }}</span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-4 mb-8">
            <div class="relative flex-1 min-w-[300px]">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                <input v-model="searchQuery" class="w-full pl-12 pr-4 py-3 bg-surface-container-low border-none rounded-xl text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/20 transition-all outline-none" placeholder="Buscar por usuario o nombre..." type="text" />
            </div>
            <div class="flex items-center bg-surface-container-low p-1.5 rounded-xl gap-1 overflow-x-auto">
                <button @click="filterRole = 'Todos'" :class="filterRole === 'Todos' ? 'bg-surface-container-lowest text-on-surface shadow-sm' : 'text-on-surface-variant hover:text-on-surface'" class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors whitespace-nowrap">Todos</button>
                <button @click="filterRole = 'administrador'" :class="filterRole === 'administrador' ? 'bg-surface-container-lowest text-on-surface shadow-sm' : 'text-on-surface-variant hover:text-on-surface'" class="px-4 py-2 text-sm font-medium transition-colors rounded-lg whitespace-nowrap">Administradores</button>
                <button @click="filterRole = 'tecnico'" :class="filterRole === 'tecnico' ? 'bg-surface-container-lowest text-on-surface shadow-sm' : 'text-on-surface-variant hover:text-on-surface'" class="px-4 py-2 text-sm font-medium transition-colors rounded-lg whitespace-nowrap">Técnicos</button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-surface-container-lowest rounded-2xl overflow-hidden shadow-sm">
            <div class="w-full overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="bg-surface-container text-on-surface-variant text-xs uppercase tracking-widest font-bold">
                            <th class="px-8 py-5">Usuario</th>
                            <th class="px-8 py-5">Rol</th>
                            <th class="px-8 py-5">Categoría Asignada</th>
                            <th class="px-8 py-5">Último Acceso</th>
                            <th class="px-8 py-5">Estado</th>
                            <th class="px-8 py-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="paginatedUsuarios.length === 0">
                            <td colspan="6" class="px-8 py-12 text-center">
                                <span class="material-symbols-outlined text-4xl text-outline-variant mb-2 block">person_search</span>
                                <p class="text-on-surface-variant font-medium">No se encontraron usuarios que coincidan con la búsqueda...</p>
                            </td>
                        </tr>
                        <tr v-for="user in paginatedUsuarios" :key="user.id" class="group hover:bg-surface-container-low transition-colors border-b border-outline-variant/10 last:border-0" :class="{'opacity-60 grayscale': user.estado == 0}">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-secondary-container flex items-center justify-center text-on-secondary-container font-bold text-sm shadow-sm ring-1 ring-secondary-container/50">
                                        {{ getInitials(user.nombre_completo) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-on-surface">{{ user.nombre_completo }}</p>
                                        <p class="text-xs text-on-surface-variant font-mono">{{ user.usuario }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <span v-if="user.rol === 'administrador'" class="px-3 py-1 bg-tertiary-container text-on-tertiary-container text-xs font-bold rounded-full capitalize">{{ user.rol }}</span>
                                <span v-else class="px-3 py-1 bg-surface-container-highest text-on-surface-variant text-xs font-bold rounded-full capitalize">{{ user.rol }}</span>
                            </td>
                            <td class="px-8 py-6">
                                <span v-if="!user.categoria_asignada || user.rol === 'administrador'" class="text-sm text-on-surface-variant italic">Todas</span>
                                <span v-else class="px-3 py-1 bg-primary-container text-primary text-[10px] uppercase font-bold rounded tracking-wider shadow-sm">{{ user.categoria_asignada }}</span>
                            </td>
                            <td class="px-8 py-6">
                                <p class="text-sm text-on-surface font-medium">{{ user.ultimo_acceso || 'Nunca' }}</p>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2 bg-surface px-3 py-1.5 rounded-lg inline-flex ring-1 ring-outline-variant/20">
                                    <span class="w-2.5 h-2.5 rounded-full shadow-sm" :class="user.estado == 1 ? 'bg-primary' : 'bg-error'"></span>
                                    <span class="text-xs font-bold text-on-surface">{{ user.estado == 1 ? 'Activo' : 'Inactivo' }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex items-center justify-end gap-1 md:opacity-0 md:group-hover:opacity-100 transition-opacity">
                                    <button @click="toggleStatus(user)" class="p-2 text-on-surface-variant transition-colors rounded-lg hover:bg-surface-container" :class="user.estado == 1 ? 'hover:text-error' : 'hover:text-primary'" :title="user.estado == 1 ? 'Desactivar Usuario' : 'Activar Usuario'">
                                        <span class="material-symbols-outlined text-[1.3rem]">{{ user.estado == 1 ? 'block' : 'check_circle' }}</span>
                                    </button>
                                    <button class="p-2 text-on-surface-variant hover:text-primary transition-colors rounded-lg hover:bg-surface-container" title="Editar">
                                        <span class="material-symbols-outlined text-[1.3rem]">edit</span>
                                    </button>
                                    <button @click="confirmDelete(user)" class="p-2 text-on-surface-variant hover:text-error transition-colors rounded-lg hover:bg-error-container/50" title="Eliminar definitivamente">
                                        <span class="material-symbols-outlined text-[1.3rem]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Controls -->
            <div class="px-8 py-4 bg-surface-container border-t border-outline-variant/10 flex items-center justify-between">
                <span class="text-sm text-on-surface-variant font-medium">
                    Mostrando 
                    <span class="text-on-surface font-bold">{{ filteredUsuarios.length === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1 }}</span>
                    a 
                    <span class="text-on-surface font-bold">{{ Math.min(currentPage * itemsPerPage, filteredUsuarios.length) }}</span> 
                    de 
                    <span class="text-on-surface font-bold">{{ filteredUsuarios.length }}</span> registros
                </span>
                <div class="flex items-center gap-2">
                    <button @click="currentPage--" :disabled="currentPage === 1" class="w-9 h-9 flex items-center justify-center rounded-lg bg-surface-container-lowest text-on-surface hover:text-primary hover:bg-white shadow-sm disabled:opacity-30 disabled:shadow-none transition-all active:scale-95 border border-outline-variant/10">
                        <span class="material-symbols-outlined text-sm font-bold">arrow_back_ios_new</span>
                    </button>
                    <span class="text-sm font-extrabold text-on-surface px-3">{{ currentPage }} / {{ totalPages || 1 }}</span>
                    <button @click="currentPage++" :disabled="currentPage >= totalPages" class="w-9 h-9 flex items-center justify-center rounded-lg bg-surface-container-lowest text-on-surface hover:text-primary hover:bg-white shadow-sm disabled:opacity-30 disabled:shadow-none transition-all active:scale-95 border border-outline-variant/10">
                        <span class="material-symbols-outlined text-sm font-bold">arrow_forward_ios</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Create User Modal -->
        <div v-if="showUserModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-surface-container-lowest rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-200">
                <div class="p-6 border-b border-outline-variant/20 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-on-surface font-headline">Nuevo Usuario</h3>
                    <button @click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-full bg-surface-container-high text-on-surface-variant hover:text-on-surface transition-colors">
                        <span class="material-symbols-outlined text-sm">close</span>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto flex-1">
                    <form id="createUserForm" @submit.prevent="submitUsuario" class="space-y-5">
                        <div>
                            <label class="text-sm font-bold text-on-surface-variant mb-1 block">Nombre Completo</label>
                            <input v-model="newUser.nombre_completo" required class="w-full px-4 py-3 bg-surface-container-low border-none rounded-xl text-on-surface placeholder:text-on-surface-variant/50 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="Ej. Carlos Mendoza" type="text" />
                        </div>
                        <div>
                            <label class="text-sm font-bold text-on-surface-variant mb-1 block">Nombre de Usuario (Para acceder)</label>
                            <input v-model="newUser.usuario" required class="w-full px-4 py-3 bg-surface-container-low border-none rounded-xl text-on-surface placeholder:text-on-surface-variant/50 focus:ring-2 focus:ring-primary/20 outline-none font-mono" placeholder="Ej. carlos_m" type="text" />
                        </div>
                        <div>
                            <label class="text-sm font-bold text-on-surface-variant mb-1 block">Contraseña Temporal</label>
                            <input v-model="newUser.password_hash" required class="w-full px-4 py-3 bg-surface-container-low border-none rounded-xl text-on-surface placeholder:text-on-surface-variant/50 focus:ring-2 focus:ring-primary/20 outline-none" placeholder="••••••••" type="password" />
                        </div>
                        <div>
                            <label class="text-sm font-bold text-on-surface-variant mb-1 block">Rol de Sistema</label>
                            <select v-model="newUser.rol" class="w-full px-4 py-3 bg-surface-container-low border-none rounded-xl text-on-surface focus:ring-2 focus:ring-primary/20 outline-none font-medium">
                                <option value="tecnico">Técnico</option>
                                <option value="administrador">Administrador (Puede ver todo)</option>
                            </select>
                        </div>
                        <div v-if="newUser.rol === 'tecnico'">
                            <label class="text-sm font-bold text-on-surface-variant mb-1 block">Categoría Asignada</label>
                            <select v-model="newUser.categoria_asignada" required class="w-full px-4 py-3 bg-surface-container-low border-none rounded-xl text-on-surface focus:ring-2 focus:ring-primary/20 outline-none text-sm font-medium">
                                <option value="" disabled selected>Seleccione el módulo permitido...</option>
                                <option value="iniciativas">Iniciativas de Ley</option>
                                <option value="citaciones">Citaciones</option>
                                <option value="comisiones">Comisiones y Gabinetes</option>
                                <option value="fiscalizacion">Fiscalización Constante</option>
                                <option value="compromisos">Manejo de Compromisos</option>
                                <option value="actividades">Actividades Web</option>
                                <option value="redes">Redes Sociales</option>
                                <option value="afiliaciones">Afiliaciones</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="p-6 border-t border-outline-variant/20 bg-surface-container flex justify-end gap-3">
                    <button @click="closeModal" type="button" class="px-6 py-2.5 bg-surface-container-high text-on-surface font-semibold rounded-lg transition-colors hover:bg-surface-container-highest">Cancelar</button>
                    <button type="submit" form="createUserForm" :disabled="isSubmitting" class="px-6 py-2.5 bg-primary text-on-primary font-semibold rounded-lg flex items-center gap-2 shadow-md transition-all hover:shadow-lg disabled:opacity-50 active:scale-95">
                        <span v-if="isSubmitting" class="material-symbols-outlined animate-spin text-sm">progress_activity</span>
                        <span>Guardar Usuario</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import Swal from 'sweetalert2';
import { useAuthStore } from '../../../stores/authStore.js';

const authStore = useAuthStore();
const API_URL = import.meta.env.VITE_API_URL || '/sys-dipu/Backend/api/v1';

// Estado Reactivo
const usuarios = ref([]);
const searchQuery = ref('');
const filterRole = ref('Todos');
const currentPage = ref(1);
const itemsPerPage = 10;

// Estado del Modal de Creación
const showUserModal = ref(false);
const isSubmitting = ref(false);
const newUser = ref({
    nombre_completo: '',
    usuario: '',
    password_hash: '',
    rol: 'tecnico',
    categoria_asignada: ''
});

// Obtener Usuarios (REST GET)
const fetchUsuarios = async () => {
    try {
        const response = await fetch(`${API_URL}/usuarios`, {
            headers: { 'Authorization': `Bearer ${authStore.token || ''}` }
        });
        const result = await response.json();
        if(result.status === 'success') {
            usuarios.value = result.data;
        }
    } catch(err) {
        console.error('Error fetching usuarios:', err);
    }
};

onMounted(() => {
    fetchUsuarios();
});

// Reseteo de Paginación al usar el buscador o filtros
watch([searchQuery, filterRole], () => {
    currentPage.value = 1;
});

// Cerrar y limpiar modal
const closeModal = () => {
    showUserModal.value = false;
    newUser.value = {
        nombre_completo: '', usuario: '', password_hash: '', rol: 'tecnico', categoria_asignada: ''
    };
};

// ============================================
// LÓGICA DE MUTACIONES HTTP (POST / PUT / DELETE)
// ============================================

const submitUsuario = async () => {
    isSubmitting.value = true;
    try {
        const payload = { ...newUser.value };
        const response = await fetch(`${API_URL}/usuarios`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${authStore.token || ''}` 
            },
            body: JSON.stringify(payload)
        });
        const resData = await response.json();
        
        if (response.ok && resData.status === 'success') {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Usuario Creado', showConfirmButton: false, timer: 3000 });
            closeModal();
            fetchUsuarios();
        } else {
            Swal.fire('Atención', resData.error || 'No se pudo crear el usuario.', 'warning');
        }
    } catch(e) {
        Swal.fire('Error', 'Falla en la conexión.', 'error');
    } finally {
        isSubmitting.value = false;
    }
};

const toggleStatus = async (user) => {
    const newState = user.estado == 1 ? 0 : 1;
    const actionText = newState == 1 ? 'activar' : 'desactivar';
    const confirmBtnColor = newState == 1 ? '#005D6B' : '#BA1A1A';

    Swal.fire({
        title: `¿${actionText.charAt(0).toUpperCase() + actionText.slice(1)} acceso?`,
        html: `Estás a punto de ${actionText} los accesos de <b>${user.nombre_completo}</b>.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: confirmBtnColor,
        cancelButtonColor: '#40484C',
        confirmButtonText: `Sí, ${actionText}`,
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await fetch(`${API_URL}/usuarios/${user.id}/toggle`, {
                    method: 'PUT',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${authStore.token || ''}` 
                    },
                    body: JSON.stringify({ estado: newState })
                });
                const resData = await response.json();
                
                if (response.ok && resData.status === 'success') {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Estado actualizado', showConfirmButton: false, timer: 2000 });
                    fetchUsuarios();
                } else {
                    Swal.fire('Error', resData.error || 'No se pudo actualizar.', 'error');
                }
            } catch(e) {
                Swal.fire('Falla de red', 'No hay conexión con el servidor.', 'error');
            }
        }
    });
};

const confirmDelete = (user) => {
    Swal.fire({
        title: 'Borrado Definitivo',
        html: `Estás a punto de eliminar a <b>${user.nombre_completo}</b> (@${user.usuario}).<br/><br/>Esta acción borrará físicamente el registro y no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#BA1A1A',
        cancelButtonColor: '#40484C', 
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'No, cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await fetch(`${API_URL}/usuarios/${user.id}`, {
                    method: 'DELETE',
                    headers: { 'Authorization': `Bearer ${authStore.token || ''}` }
                });
                const resData = await response.json();
                
                if (response.ok && resData.status === 'success') {
                    Swal.fire({ title: '¡Eliminado!', text: 'El usuario ha sido erradicado del sistema.', icon: 'success', confirmButtonColor: '#005D6B' });
                    fetchUsuarios();
                } else {
                    Swal.fire('Error', resData.error || 'No se pudo procesar.', 'error');
                }
            } catch(e) {
                Swal.fire('Falla de red', 'No hay conexión con el servidor MySQL.', 'error');
            }
        }
    });
};

// ============================================
// LÓGICA COMPUTADA (CLIENT-SIDE RENDERING)
// ============================================

const getInitials = (name) => {
    if(!name) return 'U';
    const parts = name.trim().split(' ').filter(n => n.length > 0);
    if(parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0,2).toUpperCase();
};

const filteredUsuarios = computed(() => {
    return usuarios.value.filter(user => {
        const matchesSearch = user.nombre_completo.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                              user.usuario.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesRole = filterRole.value === 'Todos' || user.rol === filterRole.value;
        return matchesSearch && matchesRole;
    });
});

const totalPages = computed(() => Math.ceil(filteredUsuarios.value.length / itemsPerPage) || 1);

const paginatedUsuarios = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredUsuarios.value.slice(start, end);
});

const administradoresCount = computed(() => usuarios.value.filter(u => u.rol === 'administrador').length);
const tecnicosCount = computed(() => usuarios.value.filter(u => u.rol === 'tecnico').length);
const inactivosCount = computed(() => usuarios.value.filter(u => u.estado == 0).length);

</script>
