<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-sans font-bold text-on-surface tracking-tight">Gestión de Usuarios</h1>
        <p class="text-sm text-on-surface-variant mt-1">Administra los accesos y roles del sistema.</p>
      </div>
      <button @click="openModal" class="flex items-center gap-2 px-4 py-2 bg-[var(--color-primary)] text-white rounded-xl text-sm font-medium hover:bg-[var(--color-primary-container)] transition-colors shadow-sm">
        <PlusIcon class="w-4 h-4" />
        Nuevo Usuario
      </button>
    </div>

    <div class="glass-card overflow-hidden">
      <!-- Toolbar -->
      <div class="p-4 border-b border-outline-variant/20 flex flex-col sm:flex-row gap-4 justify-between items-center bg-[var(--color-surface-container-lowest)]">
        <div class="relative w-full sm:w-96">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-outline" />
          <input 
            type="text" 
            placeholder="Buscar por nombre o email..." 
            v-model="searchTerm"
            class="w-full pl-10 pr-4 py-2 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 transition-all"
          />
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto">
          <!-- Role Filter -->
          <div class="relative min-w-[140px]">
            <select v-model="selectedRole" class="w-full appearance-none pl-4 pr-10 py-2 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-sm text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 cursor-pointer">
              <option value="Todos">Rol: Todos</option>
              <option value="Administrador">Administrador</option>
              <option value="Técnico">Técnico</option>
            </select>
          </div>
          <!-- Status Filter -->
          <div class="relative min-w-[140px]">
            <select v-model="selectedStatus" class="w-full appearance-none pl-4 pr-10 py-2 bg-[var(--color-surface-container-low)] border border-outline-variant/30 rounded-xl text-sm text-on-surface-variant focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 cursor-pointer">
              <option value="Todos">Estado: Todos</option>
              <option value="Activo">Activo</option>
              <option value="Inactivo">Inactivo</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-center text-sm">
          <thead class="bg-[var(--color-surface-container-low)] text-on-surface-variant font-medium">
            <tr>
              <th class="px-6 py-4 font-medium text-center">Usuario</th>
              <th class="px-6 py-4 font-medium text-center">Rol</th>
              <th class="px-6 py-4 font-medium text-center">Estado</th>
              <th class="px-6 py-4 font-medium text-center">Último Acceso</th>
              <th class="px-6 py-4 font-medium text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/20">
            <tr v-for="user in paginatedUsers" :key="user.id" class="hover:bg-[var(--color-surface-container-lowest)] transition-colors group">
              <td class="px-6 py-4">
                <div class="flex items-center justify-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-[var(--color-primary-fixed)] text-[var(--color-on-primary-fixed)] flex items-center justify-center font-bold">
                    {{ user.name.charAt(0) }}
                  </div>
                  <div>
                    <div class="font-semibold text-on-surface">{{ user.name }}</div>
                    <div class="text-xs text-on-surface-variant">{{ user.email }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="[
                  'px-2.5 py-1 rounded-full text-xs font-medium',
                  user.role === 'Administrador' ? 'bg-[var(--color-primary-container)] text-[var(--color-on-primary-container)]' : 'bg-[var(--color-surface-container-high)] text-on-surface-variant'
                ]">
                  {{ user.role }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-center gap-2">
                  <div :class="[
                    'w-2 h-2 rounded-full',
                    user.status === 'Activo' ? 'bg-[var(--color-secondary)]' : 'bg-[var(--color-error)]'
                  ]" />
                  <span class="text-on-surface-variant">{{ user.status }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-on-surface-variant text-center">{{ user.lastLogin }}</td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click="editUser(user)" class="p-2 text-outline hover:text-[var(--color-primary)] hover:bg-[var(--color-primary-fixed)]/50 rounded-lg transition-colors" title="Editar">
                    <PencilIcon class="w-4 h-4" />
                  </button>
                  <button @click="deleteUser(user.id)" class="p-2 text-outline hover:text-[var(--color-error)] hover:bg-[var(--color-error-container)]/50 rounded-lg transition-colors" title="Eliminar">
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        
        <div v-if="paginatedUsers.length === 0" class="p-8 text-center text-on-surface-variant">
          <UsersIcon class="w-12 h-12 mx-auto text-outline-variant mb-3" />
          <p class="font-medium">No se encontraron usuarios</p>
          <p class="text-sm mt-1">Intenta con otros términos de búsqueda.</p>
        </div>
      </div>
      
      <!-- Pagination -->
      <div class="p-4 border-t border-outline-variant/20 flex items-center justify-between bg-[var(--color-surface-container-lowest)] text-sm text-on-surface-variant">
        <div>Mostrando {{ paginatedUsers.length }} de {{ filteredUsers.length }} resultados (Total: {{ initialUsers.length }})</div>
        <div class="flex gap-1">
          <button @click="prevPage" :disabled="currentPage === 1" class="px-3 py-1 border border-outline-variant/30 rounded-lg hover:bg-[var(--color-surface-container)] disabled:opacity-50 transition-colors">Anterior</button>
          <button class="px-3 py-1 bg-[var(--color-primary)] text-white rounded-lg cursor-default">{{ currentPage }} de {{ totalPages || 1 }}</button>
          <button @click="nextPage" :disabled="currentPage === totalPages || totalPages === 0" class="px-3 py-1 border border-outline-variant/30 rounded-lg hover:bg-[var(--color-surface-container)] disabled:opacity-50 transition-colors">Siguiente</button>
        </div>
      </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal"></div>
      
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden transform transition-all relative z-10">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 class="text-lg font-bold text-[#221f47]">{{ newUser.id ? 'Editar Usuario' : 'Nuevo Usuario' }}</h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="saveUser">
          <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
               <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo <span class="text-red-500">*</span></label>
                  <input v-model="newUser.name" type="text" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 focus:border-[var(--color-primary)] transition-all" />
               </div>
               <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Usuario de Acceso <span class="text-red-500">*</span></label>
                  <input v-model="newUser.username" type="text" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 focus:border-[var(--color-primary)] transition-all" />
               </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico <span class="text-red-500">*</span></label>
              <input v-model="newUser.email" type="email" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 focus:border-[var(--color-primary)] transition-all" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Contraseña <span v-if="!newUser.id" class="text-red-500">*</span>
              </label>
              <input v-model="newUser.password" type="password" :required="!newUser.id" :placeholder="newUser.id ? 'Dejar en blanco para conservar actual' : ''" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 focus:border-[var(--color-primary)] transition-all" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Rol <span class="text-red-500">*</span></label>
                  <select v-model="newUser.role" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 focus:border-[var(--color-primary)] transition-all cursor-pointer">
                    <option value="admin">Administrador</option>
                    <option value="tech">Técnico</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Estado <span class="text-red-500">*</span></label>
                  <select v-model="newUser.status" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 focus:border-[var(--color-primary)] transition-all cursor-pointer">
                    <option value="Activo">Activo</option>
                    <option value="Inactivo">Inactivo</option>
                  </select>
                </div>
            </div>

            <div v-if="newUser.role === 'tech'">
              <label class="block text-sm font-medium text-gray-700 mb-1">Locación Asignada <span class="text-red-500">*</span></label>
              <select v-model="newUser.location_id" required class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/50 focus:border-[var(--color-primary)] transition-all cursor-pointer">
                <option value="">Selecciona una locación...</option>
                <option v-for="loc in locations" :key="loc.id" :value="loc.id">
                  {{ loc.name || loc.code }} ({{ loc.type }})
                </option>
              </select>
            </div>
            
          </div>

          <!-- Footer -->
          <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex justify-end gap-3">
            <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors">
              Cancelar
            </button>
            <button type="submit" :disabled="submitting" class="px-6 py-2 bg-[var(--color-primary)] hover:bg-[var(--color-primary-container)] hover:text-[var(--color-on-primary-container)] text-white text-sm font-medium rounded-xl shadow-sm hover:shadow transition-all disabled:opacity-50 flex items-center gap-2">
              <div v-if="submitting" class="w-4 h-4 border-2 border-white/20 border-t-white rounded-full animate-spin"></div>
              {{ submitting ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { 
  UsersIcon, 
  MagnifyingGlassIcon, 
  FunnelIcon, 
  PlusIcon, 
  EllipsisVerticalIcon,
  PencilIcon,
  TrashIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline';
import api from '../../services/api';

const searchTerm = ref('');
const selectedRole = ref('Todos');
const selectedStatus = ref('Todos');
const initialUsers = ref([]);
const locations = ref([]);

const isModalOpen = ref(false);
const submitting = ref(false);

const newUser = ref({
  id: null,
  name: '',
  username: '',
  email: '',
  password: '',
  role: 'admin',
  location_id: '',
  status: 'Activo'
});

const fetchLocations = async () => {
  try {
    const res = await api.get('/locations');
    locations.value = res.data.data;
  } catch (error) {
    console.error('Error fetching locations:', error);
  }
};

const fetchUsers = async () => {
  try {
    const res = await api.get('/users');
    initialUsers.value = res.data.data.map(user => ({
      ...user,
      // Map DB roles and status to human readable for display if needed
      role: user.role === 'admin' ? 'Administrador' : 'Técnico',
      // Assuming DB saves "Activo" or "Inactivo" directly
      status: user.status === 'activo' || user.status === 'Activo' ? 'Activo' : 'Inactivo', 
      lastLogin: user.last_login_at 
        ? new Date(user.last_login_at).toLocaleString() 
        : 'Nunca'
    }));
  } catch (error) {
    console.error(error);
  }
};

onMounted(() => {
  fetchLocations();
  fetchUsers();
});

const openModal = () => {
  newUser.value = { id: null, name: '', username: '', email: '', password: '', role: 'admin', location_id: '', status: 'Activo' };
  isModalOpen.value = true;
};

const editUser = (user) => {
  newUser.value = {
    id: user.id,
    name: user.name,
    username: user.username,
    email: user.email,
    password: '',
    role: user.role === 'Administrador' ? 'admin' : 'tech',
    location_id: user.location_id || '',
    status: user.status
  };
  isModalOpen.value = true;
};

const deleteUser = async (id) => {
  if (confirm('¿Estás seguro de que deseas eliminar este usuario?')) {
    try {
      await api.delete(`/users/${id}`);
      await fetchUsers();
    } catch (err) {
      console.error(err);
      alert(err.response?.data?.error || 'No se pudo eliminar el usuario.');
    }
  }
};

const closeModal = () => {
  isModalOpen.value = false;
};

const saveUser = async () => {
  submitting.value = true;
  try {
    const payload = {
      name: newUser.value.name,
      username: newUser.value.username,
      email: newUser.value.email,
      role: newUser.value.role,
      location_id: newUser.value.role === 'tech' ? newUser.value.location_id : null,
      status: newUser.value.status
    };
    
    if (newUser.value.password) {
      payload.password = newUser.value.password;
    }

    if (newUser.value.id) {
      await api.put(`/users/${newUser.value.id}`, payload);
    } else {
      await api.post('/users', payload);
    }
    
    await fetchUsers();
    closeModal();
  } catch (err) {
    console.error('Error saving user:', err);
    alert(err.response?.data?.error || 'Ocurrió un error al guardar o actualizar el usuario.');
  } finally {
    submitting.value = false;
  }
};

const filteredUsers = computed(() => {
  return initialUsers.value.filter(user => {
    const nameStr = user.name || '';
    const emailStr = user.email || '';
    const searchLow = searchTerm.value.toLowerCase();
    
    const matchesSearch = nameStr.toLowerCase().includes(searchLow) ||
                          emailStr.toLowerCase().includes(searchLow);
                          
    const matchesRole = selectedRole.value === 'Todos' || user.role === selectedRole.value;
    const matchesStatus = selectedStatus.value === 'Todos' || user.status === selectedStatus.value;
    
    return matchesSearch && matchesRole && matchesStatus;
  });
});

const currentPage = ref(1);
const itemsPerPage = 5;

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredUsers.value.length / itemsPerPage));
});

const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  return filteredUsers.value.slice(start, end);
});

const prevPage = () => {
  if (currentPage.value > 1) currentPage.value--;
};

const nextPage = () => {
  if (currentPage.value < totalPages.value) currentPage.value++;
};

// Reset pagination when filters change
watch([searchTerm, selectedRole, selectedStatus], () => {
  currentPage.value = 1;
});

</script>
