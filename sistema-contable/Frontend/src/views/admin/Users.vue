<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-sans font-bold text-on-surface tracking-tight">Gestión de Usuarios</h1>
        <p class="text-sm text-on-surface-variant mt-1">Administra los accesos y roles del sistema.</p>
      </div>
      <button class="flex items-center gap-2 px-4 py-2 bg-[var(--color-primary)] text-white rounded-xl text-sm font-medium hover:bg-[var(--color-primary-container)] transition-colors shadow-sm">
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
          <button class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-[var(--color-surface-container-low)] text-on-surface-variant rounded-xl text-sm font-medium hover:bg-[var(--color-surface-container)] transition-colors border border-outline-variant/30">
            <FunnelIcon class="w-4 h-4" />
            Filtros
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-[var(--color-surface-container-low)] text-on-surface-variant font-medium">
            <tr>
              <th class="px-6 py-4 font-medium">Usuario</th>
              <th class="px-6 py-4 font-medium">Rol</th>
              <th class="px-6 py-4 font-medium">Estado</th>
              <th class="px-6 py-4 font-medium">Último Acceso</th>
              <th class="px-6 py-4 font-medium text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/20">
            <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-[var(--color-surface-container-lowest)] transition-colors group">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
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
                <div class="flex items-center gap-2">
                  <div :class="[
                    'w-2 h-2 rounded-full',
                    user.status === 'Activo' ? 'bg-[var(--color-secondary)]' : 'bg-[var(--color-error)]'
                  ]" />
                  <span class="text-on-surface-variant">{{ user.status }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-on-surface-variant">{{ user.lastLogin }}</td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button class="p-2 text-outline hover:text-[var(--color-primary)] hover:bg-[var(--color-primary-fixed)]/50 rounded-lg transition-colors">
                    <PencilIcon class="w-4 h-4" />
                  </button>
                  <button class="p-2 text-outline hover:text-[var(--color-error)] hover:bg-[var(--color-error-container)]/50 rounded-lg transition-colors">
                    <TrashIcon class="w-4 h-4" />
                  </button>
                  <button class="p-2 text-outline hover:text-on-surface hover:bg-[var(--color-surface-container)] rounded-lg transition-colors">
                    <EllipsisVerticalIcon class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        
        <div v-if="filteredUsers.length === 0" class="p-8 text-center text-on-surface-variant">
          <UsersIcon class="w-12 h-12 mx-auto text-outline-variant mb-3" />
          <p class="font-medium">No se encontraron usuarios</p>
          <p class="text-sm mt-1">Intenta con otros términos de búsqueda.</p>
        </div>
      </div>
      
      <!-- Pagination -->
      <div class="p-4 border-t border-outline-variant/20 flex items-center justify-between bg-[var(--color-surface-container-lowest)] text-sm text-on-surface-variant">
        <div>Mostrando {{ filteredUsers.length }} de {{ initialUsers.length }} resultados</div>
        <div class="flex gap-1">
          <button class="px-3 py-1 border border-outline-variant/30 rounded-lg hover:bg-[var(--color-surface-container)] disabled:opacity-50">Anterior</button>
          <button class="px-3 py-1 bg-[var(--color-primary)] text-white rounded-lg">1</button>
          <button class="px-3 py-1 border border-outline-variant/30 rounded-lg hover:bg-[var(--color-surface-container)] disabled:opacity-50">Siguiente</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { 
  UsersIcon, 
  MagnifyingGlassIcon, 
  FunnelIcon, 
  PlusIcon, 
  EllipsisVerticalIcon,
  PencilIcon,
  TrashIcon
} from '@heroicons/vue/24/outline';
import api from '../../services/api';

const searchTerm = ref('');
const initialUsers = ref([]);

onMounted(async () => {
  try {
    const res = await api.get('/users');
    initialUsers.value = res.data.data.map(user => ({
      ...user,
      role: user.role === 'admin' ? 'Administrador' : 'Técnico',
      status: user.status === 'active' ? 'Activo' : 'Inactivo',
      lastLogin: user.last_login_at 
        ? new Date(user.last_login_at).toLocaleString() 
        : 'Nunca'
    }));
  } catch (error) {
    console.error(error);
  }
});

const filteredUsers = computed(() => {
  return initialUsers.value.filter(user => 
    user.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
    user.email.toLowerCase().includes(searchTerm.value.toLowerCase())
  );
});
</script>
