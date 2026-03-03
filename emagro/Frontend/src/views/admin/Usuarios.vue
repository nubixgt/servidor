<template>
  <PageLayout title="Gestión de Usuarios" subtitle="Administra los accesos y roles del sistema">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
      <div class="flex flex-col md:flex-row gap-4 justify-between items-center">
          <div class="relative w-full md:w-96">
              <input v-model="searchQuery" class="block w-full pl-4 pr-3 py-2.5 border border-gray-200 bg-gray-50 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] transition-all sm:text-sm" placeholder="Buscar usuario..." type="text" />
          </div>
          <button @click="openNewUserModal" class="flex items-center justify-center gap-2 px-5 py-2.5 bg-[#2E7D32] hover:bg-[#1B5E20] text-white rounded-lg text-sm font-semibold transition-colors shadow-sm w-full md:w-auto">
              <!-- UserPlus Icon -->
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>
              Nuevo Usuario
          </button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
       <div v-for="user in filteredUsers" :key="user.id" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col items-center text-center hover:shadow-md transition-shadow">
          <div class="w-20 h-20 rounded-full bg-gray-200 mb-4 overflow-hidden">
              <img :src="`https://ui-avatars.com/api/?name=${user.nombre}&background=random`" alt="User" class="w-full h-full object-cover" />
          </div>
          <h3 class="font-bold text-gray-900 text-lg">{{ user.nombre }}</h3>
          <p class="text-sm text-gray-500 mb-4">{{ user.usuario }}</p>
          <span :class="['px-3 py-1 text-xs font-semibold rounded-full border mb-4', user.rol === 'admin' ? 'bg-[#2E7D32]/10 text-[#2E7D32] border-[#2E7D32]/20' : 'bg-green-50 text-green-700 border-green-200']">
              {{ user.rol === 'admin' ? 'Administrador' : 'Vendedor' }}
          </span>
          <div class="flex gap-2 w-full mt-auto pt-4 border-t border-gray-100">
              <button @click="openEditUserModal(user)" class="flex-1 py-2 text-sm font-medium text-gray-600 hover:text-[#2E7D32] hover:bg-green-50 rounded-lg transition-colors">Editar</button>
              <button @click="toggleUserStatus(user)" class="flex-1 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                  {{ user.estado === 'activo' ? 'Bloquear' : 'Desbloquear' }}
              </button>
          </div>
      </div>
      
      <div v-if="!loading && filteredUsers.length === 0" class="col-span-full text-center py-12 text-gray-500">
        No se encontraron usuarios.
      </div>
      
      <div v-if="loading" class="col-span-full flex justify-center py-12">
        <svg class="animate-spin h-8 w-8 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>
    </div>
    <!-- Modal Formulario de Usuario -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeModal"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Panel -->
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <!-- Header with background -->
                <div class="relative h-32 bg-gradient-to-b from-[#1B5E20] to-[#2E7D32] flex items-center justify-center">
                   <div class="absolute inset-x-0 bottom-0">
                        <svg viewBox="0 0 224 12" fill="currentColor" class="w-full -mb-1 text-white" preserveAspectRatio="none">
                            <path d="M0,0 C48,12 144,12 224,0 L224,12 L0,12 Z"></path>
                        </svg>
                    </div>
                    <div class="text-center z-10 text-white">
                        <p class="text-green-100 text-xs font-bold tracking-widest uppercase mb-1">{{ isEditing ? 'Actualizar Datos' : 'Registrar' }}</p>
                        <h3 class="text-2xl font-bold">{{ isEditing ? 'Editar Usuario' : 'Nuevo Usuario' }}</h3>
                    </div>
                    <button @click="closeModal" class="absolute top-4 right-4 text-white/70 hover:text-white bg-black/20 rounded-full p-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="px-6 py-6 pb-8">
                    <form @submit.prevent="saveUser" class="space-y-5">
                        
                        <!-- Avatar Preview -->
                        <div class="flex justify-center mb-6">
                            <div class="w-20 h-20 rounded-full border-4 flex items-center justify-center shadow-lg" :class="form.rol === 'admin' ? 'border-purple-200 bg-purple-50 text-purple-600' : 'border-blue-200 bg-blue-50 text-blue-600'">
                                <span class="text-3xl font-bold">{{ form.nombre ? form.nombre.charAt(0).toUpperCase() : 'U' }}</span>
                            </div>
                        </div>

                        <!-- Nombre Completo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </div>
                                <input v-model="form.nombre" type="text" required class="pl-10 block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all" placeholder="Juan Pérez">
                            </div>
                        </div>

                        <!-- Usuario -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Usuario <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" /></svg>
                                </div>
                                <input v-model="form.usuario" type="text" required class="pl-10 block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all" placeholder="juanp">
                            </div>
                        </div>

                        <!-- Contraseña -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ isEditing ? 'Nueva Contraseña' : 'Contraseña' }} <span v-if="!isEditing" class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                </div>
                                <input v-model="form.contrasena" type="password" :required="!isEditing" class="pl-10 block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all" placeholder="••••••••">
                            </div>
                            <p v-if="isEditing" class="mt-1 text-xs text-gray-500">Dejar vacío para mantener la actual</p>
                        </div>

                        <!-- Rol y Estado en 2 columnas -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                    </div>
                                    <select v-model="form.rol" class="pl-10 block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all appearance-none cursor-pointer">
                                        <option value="admin">Administrador</option>
                                        <option value="vendedor">Vendedor</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" /></svg>
                                    </div>
                                    <select v-model="form.estado" class="pl-10 block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all appearance-none cursor-pointer">
                                        <option value="activo">Activo</option>
                                        <option value="De Baja">Inactivo / Baja</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="flex gap-4 pt-4 mt-6 border-t border-gray-100">
                            <button type="button" @click="closeModal" class="flex-1 py-3 px-4 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="isSaving" class="flex-1 py-3 px-4 rounded-xl text-sm font-medium text-white bg-[#2E7D32] hover:bg-[#1B5E20] shadow-md transition-colors flex justify-center items-center">
                                <svg v-if="isSaving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ isSaving ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Guardar Usuario') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
  </PageLayout>
</template>

<script setup>
import PageLayout from '../../components/layout/PageLayout.vue';
import { ref, onMounted, computed } from 'vue';
import api from '../../services/api';

const users = ref([]);
const loading = ref(true);
const searchQuery = ref('');

// Modal state
const isModalOpen = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);

// Form state
const currentUserId = ref(null);
const form = ref({
  nombre: '',
  usuario: '',
  contrasena: '',
  rol: 'vendedor',
  estado: 'activo'
});

const filteredUsers = computed(() => {
    if (!searchQuery.value) return users.value;
    const q = searchQuery.value.toLowerCase();
    return users.value.filter(u => 
        (u.nombre && u.nombre.toLowerCase().includes(q)) || 
        (u.usuario && u.usuario.toLowerCase().includes(q)) || 
        (u.rol && u.rol.toLowerCase().includes(q)) ||
        (u.estado && u.estado.toLowerCase().includes(q))
    );
});

const loadUsers = async () => {
    loading.value = true;
    try {
        const res = await api.get('/users');
        if (res.data.status === 'success') {
            users.value = res.data.data;
        }
    } catch (error) {
        console.error("Failed to load users", error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadUsers();
});

const openNewUserModal = () => {
    isEditing.value = false;
    currentUserId.value = null;
    form.value = {
        nombre: '',
        usuario: '',
        contrasena: '',
        rol: 'vendedor',
        estado: 'activo'
    };
    isModalOpen.value = true;
};

const openEditUserModal = (user) => {
    isEditing.value = true;
    currentUserId.value = user.id;
    form.value = {
        nombre: user.nombre,
        usuario: user.usuario,
        contrasena: '',
        rol: user.rol,
        estado: user.estado
    };
    isModalOpen.value = true;
};

const toggleUserStatus = async (user) => {
    if (!confirm(`¿Estás seguro de que deseas ${user.estado === 'activo' ? 'bloquear' : 'desbloquear'} al usuario ${user.nombre}?`)) {
        return;
    }
    
    try {
        const res = await api.patch(`/users/${user.id}/status`);
        if (res.data.status === 'success') {
            await loadUsers();
        } else {
            alert(res.data.message || "Error al actualizar el estado");
        }
    } catch (error) {
        console.error("Failed to toggle status", error);
        alert(error.response?.data?.message || "Ocurrió un error al actualizar el estado.");
    }
};

const closeModal = () => {
    isModalOpen.value = false;
};

const saveUser = async () => {
    // Form validation
    if (!form.value.nombre || !form.value.usuario) {
        alert("Por favor, complete todos los campos obligatorios.");
        return;
    }
    if (!isEditing.value && !form.value.contrasena) {
        alert("La contraseña es requerida para crear un usuario.");
        return;
    }

    isSaving.value = true;
    try {
        if (isEditing.value) {
            // Edit user
            const res = await api.put(`/users/${currentUserId.value}`, form.value);
            if (res.data.status === 'success') {
                closeModal();
                await loadUsers(); // Refresh the list
            } else {
                alert(res.data.message || "Error al actualizar usuario");
            }
        } else {
            // Create user
            const res = await api.post('/users', form.value);
            if (res.data.status === 'success') {
                closeModal();
                await loadUsers(); // Refresh the list
            } else {
                alert(res.data.message || "Error al crear usuario");
            }
        }
    } catch (error) {
        console.error("Failed to save user", error);
        alert(error.response?.data?.message || "Ocurrió un error al guardar el usuario.");
    } finally {
        isSaving.value = false;
    }
};
</script>
