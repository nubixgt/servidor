<template>
  <PageLayout title="Directorio de Clientes" subtitle="Administra la información de tus clientes agrícolas">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
      <div class="flex flex-col md:flex-row gap-4 justify-between items-center">
          <div class="relative w-full md:w-96">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              <input class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 bg-gray-50 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] transition-all sm:text-sm" placeholder="Buscar cliente por nombre o NIT..." type="text" />
          </div>
          <div class="flex gap-3 w-full md:w-auto">
              <button class="flex-1 md:flex-none flex items-center justify-center gap-2 px-5 py-2.5 bg-green-50 text-[#2E7D32] hover:bg-green-100 rounded-lg text-sm font-semibold transition-colors border border-green-200">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                  Filtros
              </button>
              <button class="flex-1 md:flex-none flex items-center justify-center gap-2 px-5 py-2.5 bg-[#2E7D32] hover:bg-[#1B5E20] text-white rounded-lg text-sm font-semibold transition-colors shadow-sm">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                  Nuevo Cliente
              </button>
          </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-gray-50 text-gray-500 border-b border-gray-100">
            <tr>
              <th class="px-6 py-4 font-semibold w-24">ID</th>
              <th class="px-6 py-4 font-semibold">Cliente</th>
              <th class="px-6 py-4 font-semibold">Teléfono</th>
              <th class="px-6 py-4 font-semibold">Dirección</th>
              <th class="px-6 py-4 font-semibold text-right">Límite de Crédito</th>
              <th class="px-6 py-4 font-semibold text-center">Estado</th>
              <th class="px-6 py-4"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
             <tr v-for="client in clients" :key="client.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 font-mono text-gray-500">{{ client.client_code }}</td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-200 overflow-hidden shrink-0">
                      <img :src="client.avatar_url || `https://ui-avatars.com/api/?name=${client.name}&background=random`" alt="Client" class="w-full h-full object-cover" />
                    </div>
                    <div>
                      <div class="font-semibold text-gray-900">{{ client.name }}</div>
                      <div class="text-xs text-gray-500">{{ client.company_name || 'Sin empresa' }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-gray-600">{{ client.phone }}</td>
                <td class="px-6 py-4 text-gray-600 max-w-xs truncate" :title="client.address">{{ client.address }}</td>
                <td class="px-6 py-4 font-medium text-right text-gray-900">Q {{ formatMoney(client.credit_limit) }}</td>
                <td class="px-6 py-4 text-center">
                  <span :class="['px-2.5 py-1 rounded-full text-xs font-medium border', 
                    client.status === 'Activo' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200']">
                    {{ client.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <button class="text-gray-400 hover:text-[#2E7D32] p-1 rounded-full hover:bg-green-50 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>
                  </button>
                </td>
              </tr>
              
              <tr v-if="loading">
                <td colspan="7" class="px-6 py-12 text-center">
                  <div class="flex justify-center">
                    <svg class="animate-spin h-6 w-6 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                  </div>
                </td>
              </tr>
              <tr v-if="!loading && clients.length === 0">
                <td colspan="7" class="px-6 py-8 text-center text-gray-500">No hay clientes registrados.</td>
              </tr>
          </tbody>
        </table>
      </div>
    </div>
  </PageLayout>
</template>

<script setup>
import PageLayout from '../../components/layout/PageLayout.vue';
import { ref, onMounted } from 'vue';
import api from '../../services/api';

const clients = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const res = await api.get('/clients');
        if (res.data.status === 'success') {
            clients.value = res.data.data;
        }
    } catch (error) {
        console.error("Failed to load clients", error);
    } finally {
        loading.value = false;
    }
});

const formatMoney = (val) => {
  return parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>
