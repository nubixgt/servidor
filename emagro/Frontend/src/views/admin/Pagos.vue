<template>
  <PageLayout title="Gestión de Pagos" subtitle="Seguimiento de cobros y deudas">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
          <div class="flex items-start justify-between">
              <div>
                  <p class="text-gray-500 text-sm font-medium mb-1">Total Recaudado</p>
                  <h3 class="text-2xl font-bold text-gray-900 tracking-tight mb-2">$45,200.00</h3>
              </div>
              <div class="p-2 bg-green-50 rounded-lg text-green-600">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
              </div>
          </div>
      </div>
      <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
          <div class="flex items-start justify-between">
              <div>
                  <p class="text-gray-500 text-sm font-medium mb-1">Deuda Total</p>
                  <h3 class="text-2xl font-bold text-gray-900 tracking-tight mb-2">$12,350.50</h3>
              </div>
              <div class="p-2 bg-orange-50 rounded-lg text-orange-600">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
              </div>
          </div>
      </div>
      <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
          <div class="flex items-start justify-between">
              <div>
                  <p class="text-gray-500 text-sm font-medium mb-1">Pagos Próximos</p>
                  <h3 class="text-2xl font-bold text-gray-900 tracking-tight mb-2">5 Clientes</h3>
              </div>
              <div class="p-2 bg-red-50 rounded-lg text-red-600">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
              </div>
          </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="p-5 border-b border-gray-100 flex flex-col md:flex-row gap-4 items-center justify-between">
          <div class="relative w-full md:w-96">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              <input class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] outline-none text-gray-900 placeholder-gray-400" placeholder="Buscar pago o cliente..." type="text" />
          </div>
          <div class="flex gap-3 w-full md:w-auto">
              <button class="flex-1 md:flex-none px-4 py-2 bg-green-50 text-[#2E7D32] border border-green-200 rounded-lg text-sm font-medium hover:bg-green-100 transition-colors flex items-center justify-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg> Filtros
              </button>
              <button class="flex-1 md:flex-none px-4 py-2 bg-[#2E7D32] text-white rounded-lg text-sm font-medium hover:bg-[#1B5E20] transition-colors flex items-center justify-center gap-2 shadow-sm">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg> Registrar Pago
              </button>
          </div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50 text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-gray-100">
              <th class="px-6 py-4">ID</th>
              <th class="px-6 py-4">Cliente</th>
              <th class="px-6 py-4">Concepto</th>
              <th class="px-6 py-4">Vencimiento</th>
              <th class="px-6 py-4">Monto</th>
              <th class="px-6 py-4">Estado</th>
              <th class="px-6 py-4 text-right"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
             <tr v-for="payment in payments" :key="payment.id" class="hover:bg-gray-50 transition-colors group">
                <td class="px-6 py-4 text-sm text-gray-500 font-mono">{{ payment.reference_number }}</td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-700 font-bold text-xs">{{ payment.client_name ? payment.client_name.substring(0, 2).toUpperCase() : 'NA' }}</div>
                    <span class="font-medium text-gray-900">{{ payment.client_name }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ payment.payment_method }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ (payment.payment_date || '').substring(0, 10) }}</td>
                <td class="px-6 py-4 font-medium text-gray-900">Q {{ formatMoney(payment.amount_paid) }}</td>
                <td class="px-6 py-4">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border bg-green-50 text-green-700 border-green-200">
                    Abonado
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <button class="text-gray-400 hover:text-[#2E7D32] transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>
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
              <tr v-if="!loading && payments.length === 0">
                <td colspan="7" class="px-6 py-8 text-center text-gray-500">No hay pagos registrados.</td>
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

const payments = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const res = await api.get('/payments');
        if (res.data.status === 'success') {
            payments.value = res.data.data;
        }
    } catch (error) {
        console.error("Failed to load payments", error);
    } finally {
        loading.value = false;
    }
});

const formatMoney = (val) => {
  return parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>
