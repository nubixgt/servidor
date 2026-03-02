<template>
  <PageLayout title="Gestión de Usuarios" subtitle="Administra los accesos y roles del sistema">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
      <div class="flex flex-col md:flex-row gap-4 justify-between items-center">
          <div class="relative w-full md:w-96">
              <input class="block w-full pl-4 pr-3 py-2.5 border border-gray-200 bg-gray-50 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] transition-all sm:text-sm" placeholder="Buscar usuario..." type="text" />
          </div>
          <button class="flex items-center justify-center gap-2 px-5 py-2.5 bg-[#2E7D32] hover:bg-[#1B5E20] text-white rounded-lg text-sm font-semibold transition-colors shadow-sm w-full md:w-auto">
              <!-- UserPlus Icon -->
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>
              Nuevo Usuario
          </button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
       <div v-for="user in users" :key="user.id" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col items-center text-center hover:shadow-md transition-shadow">
          <div class="w-20 h-20 rounded-full bg-gray-200 mb-4 overflow-hidden">
              <img :src="user.avatar_url || `https://ui-avatars.com/api/?name=${user.username}&background=random`" alt="User" class="w-full h-full object-cover" />
          </div>
          <h3 class="font-bold text-gray-900 text-lg">{{ user.username }}</h3>
          <p class="text-sm text-gray-500 mb-4">{{ user.email || 'Sin correo' }}</p>
          <span :class="['px-3 py-1 text-xs font-semibold rounded-full border mb-4', user.role_id === 1 ? 'bg-green-50 text-green-700 border-green-200' : 'bg-blue-50 text-blue-700 border-blue-200']">
              {{ user.role_id === 1 ? 'Administrador' : 'Vendedor' }}
          </span>
          <div class="flex gap-2 w-full mt-auto pt-4 border-t border-gray-100">
              <button class="flex-1 py-2 text-sm font-medium text-gray-600 hover:text-[#2E7D32] hover:bg-green-50 rounded-lg transition-colors">Editar</button>
              <button class="flex-1 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                  {{ user.status === 'Activo' ? 'Bloquear' : 'Desbloquear' }}
              </button>
          </div>
      </div>
      
      <div v-if="!loading && users.length === 0" class="col-span-full text-center py-12 text-gray-500">
        No se encontraron usuarios.
      </div>
      
      <div v-if="loading" class="col-span-full flex justify-center py-12">
        <svg class="animate-spin h-8 w-8 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>
    </div>
  </PageLayout>
</template>

<script setup>
import PageLayout from '../../components/layout/PageLayout.vue';
import { ref, onMounted } from 'vue';
import api from '../../services/api';

const users = ref([]);
const loading = ref(true);

onMounted(async () => {
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
});
</script>
