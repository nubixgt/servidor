<template>
  <PageLayout title="Historial de Ventas" subtitle="Registro de transacciones y facturación">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div class="md:col-span-1">
          <label class="block text-sm font-medium text-gray-700 mb-1">Buscar venta</label>
          <div class="relative">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] sm:text-sm outline-none" placeholder="ID, Cliente o NIT..." type="text" />
          </div>
        </div>
        <div class="md:col-span-1">
          <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
          <input class="block w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] sm:text-sm outline-none" type="date" />
        </div>
        <div class="md:col-span-1">
          <label class="block text-sm font-medium text-gray-700 mb-1">Vendedor</label>
          <select class="block w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] sm:text-sm outline-none">
            <option>Todos los vendedores</option>
          </select>
        </div>
        <div class="md:col-span-1 flex justify-end gap-2">
          <button class="flex items-center justify-center px-4 py-2 border border-gray-200 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors w-full md:w-auto">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg> Filtros
          </button>
          <button class="flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#2E7D32] hover:bg-[#1B5E20] shadow-sm transition-colors w-full md:w-auto">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg> Nueva Venta
          </button>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-gray-50 text-gray-500 border-b border-gray-100 uppercase text-xs">
            <tr>
              <th class="px-6 py-4 font-semibold">ID Nota</th>
              <th class="px-6 py-4 font-semibold">Fecha</th>
              <th class="px-6 py-4 font-semibold">Cliente</th>
              <th class="px-6 py-4 font-semibold">Vendedor</th>
              <th class="px-6 py-4 font-semibold">Total</th>
              <th class="px-6 py-4 font-semibold text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="sale in sales" :key="sale.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4 font-bold text-gray-900">{{ sale.invoice_number }}</td>
              <td class="px-6 py-4">
                <div class="text-gray-900">{{ (sale.sale_date || '').substring(0, 10) }}</div>
                <div class="text-xs text-gray-500">{{ (sale.sale_date || '00:00:00').substring(11, 16) }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="font-medium text-gray-900">{{ sale.client_name }}</div>
                <div class="text-xs text-gray-500">Cliente</div>
              </td>
              <td class="px-6 py-4 text-gray-600">{{ sale.user_name }}</td>
              <td class="px-6 py-4">
                <span :class="['px-3 py-1 inline-flex text-sm font-semibold rounded-full border', sale.status === 'Completado' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200']">
                  Q {{ formatMoney(sale.total_amount) }}
                </span>
              </td>
              <td class="px-6 py-4 text-right flex justify-end gap-3">
                <button class="text-[#2E7D32] hover:text-[#1B5E20]" title="Ver PDF">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>
                </button>
                <button class="text-gray-400 hover:text-gray-600" title="Detalles">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
              </td>
            </tr>
            <tr v-if="loading">
              <td colspan="6" class="px-6 py-12 text-center">
                <div class="flex justify-center">
                  <svg class="animate-spin h-6 w-6 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                </div>
              </td>
            </tr>
            <tr v-if="!loading && sales.length === 0">
              <td colspan="6" class="px-6 py-8 text-center text-gray-500">No hay ventas registradas.</td>
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

const sales = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const res = await api.get('/sales');
        if (res.data.status === 'success') {
            sales.value = res.data.data;
        }
    } catch (error) {
        console.error("Failed to load sales", error);
    } finally {
        loading.value = false;
    }
});

const formatMoney = (val) => {
  return parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>
