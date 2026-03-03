<template>
  <PageLayout title="Gestión de Pagos" subtitle="Seguimiento de cobros y abonos realizados">
    
    <!-- Filters Panel -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar pago o factura</label>
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input v-model="searchQuery" class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 bg-gray-50 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] transition-all sm:text-sm" placeholder="Buscar por cliente, ID de nota o referencia..." type="text" />
            </div>
        </div>
        <div class="md:col-span-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Banco</label>
            <select v-model="filterBanco" class="block w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] transition-all sm:text-sm">
                <option value="">Todos los bancos</option>
                <option v-for="b in bancosDisponibles" :key="b" :value="b">{{ b }}</option>
            </select>
        </div>
        <div class="md:col-span-1 flex justify-end">
            <button @click="openNewPaymentModal" class="flex-1 md:flex-none px-4 py-2.5 bg-[#2E7D32] text-white rounded-lg text-sm font-semibold hover:bg-[#1B5E20] transition-colors flex items-center justify-center gap-2 shadow-sm w-full">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg> Registrar Pago
            </button>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm flex flex-col justify-center items-center text-center transition-transform hover:-translate-y-1 hover:shadow-md">
          <div class="w-12 h-12 rounded-full bg-green-50 text-green-600 flex items-center justify-center mb-3">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline><polyline points="16 7 22 7 22 13"></polyline></svg>
          </div>
          <h4 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Abonos Recaudados</h4>
          <p class="text-3xl font-black text-gray-900 mt-1">Q {{ formatMoney(totalAbonos) }}</p>
      </div>
      <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm flex flex-col justify-center items-center text-center transition-transform hover:-translate-y-1 hover:shadow-md">
          <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mb-3">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
          </div>
          <h4 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Cantidad de Pagos</h4>
          <p class="text-3xl font-black text-gray-900 mt-1">{{ filteredPayments.length }}</p>
      </div>
      <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm flex flex-col justify-center items-center text-center transition-transform hover:-translate-y-1 hover:shadow-md">
          <div class="w-12 h-12 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center mb-3">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          </div>
          <h4 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Deuda Total Restante</h4>
          <p class="text-3xl font-black text-gray-900 mt-1">Q {{ formatMoney(deudaTotal) }}</p>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <!-- Table content -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50 text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-gray-100">
              <th class="px-6 py-4">Ref. / ID</th>
              <th class="px-6 py-4">Cliente y Nota</th>
              <th class="px-6 py-4">Banco</th>
              <th class="px-6 py-4">Fecha Pago</th>
              <th class="px-6 py-4 text-right">Monto</th>
              <th class="px-6 py-4 text-center">Estado</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
             <tr v-for="payment in filteredPayments" :key="payment.id" class="hover:bg-gray-50 transition-colors group">
                <td class="px-6 py-4">
                  <span class="text-sm text-gray-500 font-mono font-medium">{{ payment.referencia_transaccion || payment.id }}</span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-700 font-bold text-xs shrink-0">
                        {{ String(payment.cliente_nombre || 'N/A').substring(0, 2).toUpperCase() }}
                    </div>
                    <div>
                        <div class="font-bold text-gray-900 leading-tight">{{ payment.cliente_nombre || 'Cliente Desconocido' }}</div>
                        <div class="text-xs text-gray-500">Nota Envío: #{{ payment.factura_id }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600 font-medium">{{ payment.banco }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ (payment.fecha_pago || '').substring(0, 10) }}</td>
                <td class="px-6 py-4 text-right">
                    <span class="font-bold text-gray-900">Q {{ formatMoney(payment.monto_pago) }}</span>
                </td>
                <td class="px-6 py-4 text-center">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border bg-green-50 text-green-700 border-green-200">
                    Abonado
                  </span>
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
              <tr v-if="!loading && filteredPayments.length === 0">
                <td colspan="6" class="px-6 py-8 text-center text-gray-500">No hay pagos que coincidan con los filtros.</td>
              </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Nuevo Pago -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                
                <div class="relative h-32 bg-gradient-to-b from-[#1B5E20] to-[#2E7D32] flex items-center justify-center rounded-t-2xl overflow-hidden">
                    <div class="absolute inset-x-0 bottom-0">
                        <svg viewBox="0 0 224 12" fill="currentColor" class="w-full -mb-1 text-white" preserveAspectRatio="none">
                            <path d="M0,0 C48,12 144,12 224,0 L224,12 L0,12 Z"></path>
                        </svg>
                    </div>
                    <div class="text-center z-10 text-white">
                        <p class="text-green-100 text-xs font-bold tracking-widest uppercase mb-1">Nuevo Registro</p>
                        <h3 class="text-2xl font-bold">Registrar Pago / Abono</h3>
                    </div>
                    <button @click="closeModal" class="absolute top-4 right-4 text-white/70 hover:text-white bg-black/20 rounded-full p-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="px-6 py-6 pb-8">
                    <form @submit.prevent="savePayment" class="space-y-4">
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Factura de Referencia <span class="text-red-500">*</span></label>
                                <select v-model="facturaSeleccionada" @change="onFacturaChange" required class="block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 px-4 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all">
                                    <option :value="null" disabled>Seleccione una factura</option>
                                    <option v-for="f in pendingInvoices" :key="f.id" :value="f">
                                        Factura {{ f.numero_nota }} - {{ f.cliente_nombre }} (Pendiente: Q {{ formatMoney(f.saldo_pendiente) }})
                                    </option>
                                </select>
                                <p v-if="pendingInvoices.length === 0" class="text-xs text-orange-600 mt-1">Cargando o no hay facturas pendientes.</p>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Monto del Pago (Q) <span class="text-red-500">*</span></label>
                                <input v-model.number="form.monto_pago" type="number" step="0.01" min="0" required class="block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 px-4 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all" placeholder="0.00">
                                <p v-if="facturaSeleccionada" class="text-xs text-green-700 mt-1">Saldo pendiente: Q {{ formatMoney(facturaSeleccionada.saldo_pendiente) }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Banco <span class="text-red-500">*</span></label>
                            <select v-model="form.banco" required class="block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 px-4 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all">
                                <option value="" disabled>Seleccione un banco</option>
                                <option v-for="b in bancosDisponibles" :key="b" :value="b">{{ b }}</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Referencia Transacción</label>
                                <input v-model="form.referencia_transaccion" type="text" class="block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 px-4 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all" placeholder="No. de boleta">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Pago <span class="text-red-500">*</span></label>
                                <input v-model="form.fecha_pago" type="date" required class="block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 px-4 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all">
                            </div>
                        </div>

                        <div class="flex gap-4 pt-4 mt-4 border-t border-gray-100">
                            <button type="button" @click="closeModal" class="flex-1 py-3 px-4 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="isSaving" class="flex-1 py-3 px-4 rounded-xl text-sm font-medium text-white bg-[#2E7D32] hover:bg-[#1B5E20] shadow-md transition-colors flex justify-center items-center">
                                <svg v-if="isSaving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ isSaving ? 'Guardando...' : 'Guardar Pago' }}
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

const payments = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const filterBanco = ref('');
const deudaTotal = ref(0); // Cifra proveniente de toda la tabla

onMounted(async () => {
    try {
        const res = await api.get('/payments');
        if (res.data.status === 'success') {
            if (res.data.data && Array.isArray(res.data.data.data)) {
                payments.value = res.data.data.data;
                deudaTotal.value = res.data.data.meta?.deuda_total || 0;
            } else {
                payments.value = res.data.data;
            }
        }
    } catch (error) {
        console.error("Failed to load payments", error);
    } finally {
        loading.value = false;
    }
});



const filteredPayments = computed(() => {
    let result = payments.value;
    
    // Filtro por texto
    if (searchQuery.value) {
        const q = searchQuery.value.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().trim();
        result = result.filter(p => {
            const ref = String(p.referencia_transaccion || '').toLowerCase();
            const cliente = String(p.cliente_nombre || '').normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
            const nota = String(p.factura_id || '').toLowerCase();
            return ref.includes(q) || cliente.includes(q) || nota.includes(q);
        });
    }

    // Filtro por Banco
    if (filterBanco.value) {
        result = result.filter(p => p.banco === filterBanco.value);
    }

    return result;
});

const totalAbonos = computed(() => {
    return filteredPayments.value.reduce((sum, p) => sum + Number(p.monto_pago || 0), 0);
});

const formatMoney = (val) => {
  return parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// --- MODAL LOGIC ---
const isModalOpen = ref(false);
const isSaving = ref(false);
const pendingInvoices = ref([]);
const facturaSeleccionada = ref(null);

const form = ref({
    factura_id: null,
    fecha_pago: new Date().toISOString().split('T')[0],
    banco: '',
    monto_pago: null,
    referencia_transaccion: ''
});

const bancosDisponibles = [
    'Banco G&T Continental',
    'Banco Industrial',
    'BAC Credomatic',
    'Banrural',
    'Bantrab'
];

const loadPendingInvoices = async () => {
    try {
        const res = await api.get('/payments/pending');
        if (res.data.status === 'success') {
            pendingInvoices.value = res.data.data;
        }
    } catch (e) {
        console.error("Failed to load pending invoices", e);
    }
};

const openNewPaymentModal = async () => {
    form.value = {
        factura_id: null,
        fecha_pago: new Date().toISOString().split('T')[0],
        banco: '',
        monto_pago: null,
        referencia_transaccion: ''
    };
    facturaSeleccionada.value = null;
    isModalOpen.value = true;
    await loadPendingInvoices();
};

const onFacturaChange = () => {
    if (facturaSeleccionada.value) {
        form.value.factura_id = facturaSeleccionada.value.id;
        form.value.monto_pago = null; // Reset user input when changing factura
    }
};

const closeModal = () => {
    isModalOpen.value = false;
};

const savePayment = async () => {
    if (!form.value.factura_id || !form.value.monto_pago || !form.value.banco || !form.value.fecha_pago) {
        alert("Complete todos los campos obligatorios.");
        return;
    }
    
    if (facturaSeleccionada.value && form.value.monto_pago > Number(facturaSeleccionada.value.saldo_pendiente)) {
        alert("El monto excede el saldo pendiente.");
        return;
    }

    isSaving.value = true;
    try {
        const payload = {
            ...form.value,
            usuario_id: 1 // TODO: get from logged user token / context later
        };
        const res = await api.post('/payments', payload);
        if (res.data.status === 'success') {
            closeModal();
            // Reload table
            const getRes = await api.get('/payments');
            if (getRes.data.status === 'success') {
                if (getRes.data.data && Array.isArray(getRes.data.data.data)) {
                    payments.value = getRes.data.data.data;
                    deudaTotal.value = getRes.data.data.meta?.deuda_total || 0;
                } else {
                    payments.value = getRes.data.data;
                }
            }
        } else {
            alert(res.data.message || 'Error al guardar');
        }
    } catch (e) {
        console.error(e);
        alert(e.response?.data?.message || 'Error al registrar el pago');
    } finally {
        isSaving.value = false;
    }
};
</script>
