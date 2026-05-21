<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">

    <!-- Upper header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Créditos y Cuentas por Pagar</h2>
        <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Visión consolidada de obligaciones financieras y líneas de crédito</p>
      </div>
      <div class="flex gap-4 flex-wrap">
        <button
          @click="showAddPaymentModal = true"
          class="px-6 py-4 rounded-3xl border border-white/5 bg-white/5 text-white/80 font-black text-xs uppercase tracking-widest hover:bg-white/10 hover:scale-105 transition-all flex items-center gap-2"
        >
          <CurrencyDollarIcon class="w-4 h-4" /> Registrar Abono
        </button>
        <button
          @click="showAddCreditModal = true"
          class="glass-button-primary bg-primary border-primary border text-white px-8 py-4 rounded-3xl text-xs font-black uppercase tracking-widest shadow-2xl flex items-center gap-3 hover:scale-105 active:scale-95 transition-all"
        >
          <PlusIcon class="w-4 h-4" /> Nuevo Crédito
        </button>
      </div>
    </div>

    <!-- Bento Summary Grid -->
    <div class="grid grid-cols-12 gap-8">
      <!-- Total Debt Card -->
      <div class="col-span-12 lg:col-span-4 bg-gradient-to-br from-primary via-primary/80 to-indigo-900 text-white p-10 rounded-[48px] shadow-2xl flex flex-col justify-between relative overflow-hidden h-[260px]">
        <div class="absolute -right-10 -top-10 w-44 h-44 bg-white/10 rounded-full blur-3xl"></div>
        <div>
          <p class="text-[10px] font-black opacity-80 uppercase tracking-[0.3em]">Deuda Total Consolidada</p>
          <h3 class="text-4xl font-black italic tracking-tighter mt-4">
            Q{{ totalConsolidatedDebt.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
          </h3>
        </div>
        <div class="flex justify-between items-end border-t border-white/15 pt-6 mt-4">
          <div>
            <p class="text-[10px] font-black opacity-60 uppercase tracking-widest">Próximo Vencimiento</p>
            <p class="text-sm font-black italic mt-1">{{ nextDueDate || 'NINGUNO' }}</p>
          </div>
          <CurrencyDollarIcon class="w-10 h-10 opacity-30" />
        </div>
      </div>

      <!-- Credit Lines Container -->
      <div class="col-span-12 lg:col-span-8 grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Status summary (Paid vs Pending) -->
        <div class="glass-card p-10 rounded-[44px] border border-white/5 flex flex-col justify-between h-[260px]">
          <div>
            <div class="flex justify-between items-center">
              <span class="text-[10px] font-black text-white/40 uppercase tracking-[0.25em]">Métricas de Pago</span>
              <span class="px-3 py-1 bg-primary/20 text-primary border border-primary/20 rounded-full text-[9px] font-black uppercase tracking-widest">Global</span>
            </div>
            <div class="mt-8">
              <div class="flex justify-between text-xs font-bold mb-3.5">
                <span class="text-white/40">Progreso de Pagos Totales</span>
                <span class="text-primary font-black">{{ paymentProgressPercent.toFixed(1) }}%</span>
              </div>
              <div class="w-full bg-white/5 h-2.5 rounded-full overflow-hidden">
                <div class="bg-primary h-full rounded-full transition-all duration-1000" :style="{ width: paymentProgressPercent + '%' }"></div>
              </div>
            </div>
          </div>
          <div class="flex justify-between border-t border-white/5 pt-5 mt-4">
            <div>
              <p class="text-[10px] font-black text-white/30 uppercase tracking-widest">Total Pagado</p>
              <p class="text-lg font-black italic mt-1 text-primary">Q{{ totalPaid.toLocaleString('es-GT', {minimumFractionDigits: 2}) }}</p>
            </div>
            <div class="text-right">
              <p class="text-[10px] font-black text-white/30 uppercase tracking-widest">Crédito Original</p>
              <p class="text-lg font-black text-white/50 italic mt-1">Q{{ totalOriginalCredit.toLocaleString('es-GT', {minimumFractionDigits: 2}) }}</p>
            </div>
          </div>
        </div>

        <div class="glass-card p-10 rounded-[44px] border border-white/5 flex flex-col justify-between h-[260px]">
          <div>
             <div class="flex justify-between items-center">
              <span class="text-[10px] font-black text-white/40 uppercase tracking-[0.25em]">Facturas Vencidas</span>
              <span class="px-3 py-1 bg-rose-500/20 text-rose-400 border border-rose-500/20 rounded-full text-[9px] font-black uppercase tracking-widest">Alerta</span>
            </div>
            <h3 class="text-5xl font-black italic tracking-tighter mt-6 text-rose-400">
              {{ expiredCreditsCount }}
            </h3>
          </div>
          <div class="flex justify-between border-t border-white/5 pt-5 mt-4">
            <div>
              <p class="text-[10px] font-black text-white/30 uppercase tracking-widest">Monto Vencido</p>
              <p class="text-lg font-black italic mt-1 text-rose-400">Q{{ expiredDebtAmount.toLocaleString('es-GT', {minimumFractionDigits: 2}) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Grid Content -->
    <div class="grid grid-cols-12 gap-10">

      <!-- Right column: Detailed Invoice Ledger -->
      <div class="col-span-12">
        <div class="glass-card rounded-[48px] overflow-hidden border border-white/5 shadow-2xl">
          <!-- Headers with Filters -->
          <div class="p-10 border-b border-white/5 bg-white/5 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
              <h4 class="text-2xl font-black text-white italic uppercase tracking-tighter">Listado de Créditos</h4>
              <p class="text-[10px] font-bold text-white/30 uppercase tracking-widest mt-1">Cuentas por pagar e historial indexado</p>
            </div>

            <!-- Filtering Controls -->
            <div class="flex flex-wrap items-center gap-3">
              <div class="relative">
                <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-white/40" />
                <input
                  type="text"
                  v-model="searchTerm"
                  placeholder="Buscar proveedor o Ref..."
                  class="glass-input pl-10 pr-4 py-3 text-xs w-56 rounded-xl font-bold uppercase tracking-widest placeholder:text-white/20 text-white"
                />
              </div>

              <select
                v-model="statusFilter"
                class="bg-white/5 border border-white/10 p-3 text-xs rounded-xl text-white font-bold cursor-pointer uppercase focus:outline-none"
              >
                <option value="all" class="bg-slate-950 text-white">Todos</option>
                <option value="Pagado" class="bg-slate-950 text-white">Pagados</option>
                <option value="Parcial" class="bg-slate-950 text-white">Parciales</option>
                <option value="Pendiente" class="bg-slate-950 text-white">Pendientes</option>
              </select>

              <button class="p-3.5 border border-white/10 hover:bg-white/5 text-white/50 hover:text-white rounded-xl transition-all">
                <ArrowDownTrayIcon class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Table Area -->
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="text-[10px] font-extrabold text-white/30 uppercase tracking-widest border-b border-white/5">
                  <th class="px-10 py-8">Proveedor & Ref</th>
                  <th class="px-10 py-8">Proyecto</th>
                  <th class="px-10 py-8 text-right">Monto Original</th>
                  <th class="px-10 py-8 text-right">Saldo Pendiente</th>
                  <th class="px-10 py-8">Vencimiento</th>
                  <th class="px-10 py-8 text-center">Estado</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr v-if="paginatedCredits.length === 0">
                  <td colspan="6" class="px-10 py-20 text-center text-white/30 font-bold uppercase tracking-widest text-xs">
                    No se encontraron créditos registrados con este criterio.
                  </td>
                </tr>
                <tr
                  v-for="credit in paginatedCredits"
                  :key="credit.id"
                  class="hover:bg-white/5 transition-all duration-300 group"
                >
                  <td class="px-10 py-6">
                    <div class="flex items-center gap-4">
                      <div class="w-9 h-9 rounded-full bg-primary/20 text-primary font-black italic flex items-center justify-center text-sm border border-white/10 shadow-md">
                        {{ credit.supplier_name ? credit.supplier_name.charAt(0).toUpperCase() : '?' }}
                      </div>
                      <div>
                        <span class="font-extrabold text-white text-sm uppercase tracking-tight italic block">{{ credit.supplier_name }}</span>
                        <span class="text-[10px] font-bold text-white/40 tracking-widest uppercase">Ref: {{ credit.invoice_number || 'N/A' }}</span>
                      </div>
                    </div>
                  </td>
                  <td class="px-10 py-6 font-semibold text-white/60 text-xs uppercase">{{ credit.project_name || 'N/A' }}</td>
                  <td class="px-10 py-6 text-right font-black italic text-sm text-white/70">
                    Q{{ Number(credit.amount).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                  </td>
                  <td class="px-10 py-6 text-right font-black italic text-base text-white">
                    Q{{ (Number(credit.amount) - Number(credit.total_paid)).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                  </td>
                  <td :class="[
                    'px-10 py-6 text-xs font-bold leading-none',
                    isExpired(credit.due_date, credit.status) ? 'text-rose-400 font-extrabold italic' : 'text-white/60'
                  ]">
                    {{ credit.due_date }}
                  </td>
                  <td class="px-10 py-6 text-center">
                    <span :class="[
                      'px-3.5 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border',
                      credit.status === 'Pendiente' ? 'bg-orange-500/10 text-orange-400 border-orange-500/20' :
                      credit.status === 'Pagado' ? 'bg-primary/10 text-primary border-primary/20' :
                      'bg-indigo-500/10 text-indigo-400 border-indigo-500/20'
                    ]">
                      {{ credit.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination / Bottom controls -->
          <div class="p-10 border-t border-white/5 bg-black/20 flex flex-col md:flex-row justify-between items-center gap-6">
            <p class="text-[10px] font-black text-white/20 uppercase tracking-widest">
              Mostrando {{ (currentPage - 1) * itemsPerPage + 1 }}-{{ Math.min(currentPage * itemsPerPage, filteredCredits.length) }} de {{ filteredCredits.length }} registros
            </p>
            <div class="flex items-center gap-3">
              <button @click="prevPage" :disabled="currentPage === 1" class="w-10 h-10 rounded-xl border border-white/10 flex items-center justify-center hover:bg-white/5 transition-all disabled:opacity-30 disabled:cursor-not-allowed">
                <ChevronLeftIcon class="w-5 h-5 text-white/40" />
              </button>
              <button class="w-10 h-10 rounded-xl bg-primary text-white font-black italic text-sm">{{ currentPage }}</button>
              <button @click="nextPage" :disabled="currentPage === totalPages" class="w-10 h-10 rounded-xl border border-white/10 flex items-center justify-center hover:bg-white/5 transition-all disabled:opacity-30 disabled:cursor-not-allowed">
                <ChevronRightIcon class="w-5 h-5 text-white/40" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Registro de Crédito -->
    <div v-if="showAddCreditModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
      <div class="absolute inset-0 cursor-pointer" @click="showAddCreditModal = false"></div>
      <div class="relative w-full max-w-2xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white max-h-[90vh] overflow-y-auto custom-scrollbar">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Registro de Crédito / CxP</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Capturar nueva obligación con proveedor</p>

        <form @submit.prevent="submitCredit" class="space-y-6">
          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Proveedor *</label>
              <select required v-model="newCredit.supplier_id" class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold text-white focus:outline-none cursor-pointer">
                <option value="" disabled class="bg-slate-900 text-white/50">Seleccione proveedor...</option>
                <option v-for="sup in suppliers" :key="sup.id" :value="sup.id" class="bg-slate-900">{{ sup.razon_social }}</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Proyecto asociado</label>
              <select v-model="newCredit.project_id" class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold text-white focus:outline-none cursor-pointer">
                <option value="" class="bg-slate-900 text-white/50">Ninguno (Gasto general)</option>
                <option v-for="proj in projects" :key="proj.id" :value="proj.id" class="bg-slate-900">{{ proj.nombre }}</option>
              </select>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Número de Factura o Referencia</label>
            <input
              type="text"
              v-model="newCredit.invoice_number"
              placeholder="E.g. INV-99021"
              class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
            />
          </div>

          <div class="grid grid-cols-3 gap-6">
            <div class="space-y-2 col-span-1">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Monto (GTQ) *</label>
              <input
                type="number"
                required
                step="0.01"
                min="0.01"
                v-model="newCredit.amount"
                placeholder="0.00"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white placeholder:text-white/20"
              />
            </div>
            <div class="space-y-2 col-span-1">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Fecha Compra *</label>
              <input
                type="date"
                required
                v-model="newCredit.purchase_date"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white cursor-pointer"
              />
            </div>
            <div class="space-y-2 col-span-1">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Vencimiento *</label>
              <input
                type="date"
                required
                v-model="newCredit.due_date"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white cursor-pointer"
              />
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Observaciones</label>
            <textarea
              v-model="newCredit.observations"
              rows="2"
              placeholder="Detalles adicionales..."
              class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white placeholder:text-white/20"
            ></textarea>
          </div>

          <div class="flex gap-4 pt-4">
            <button
              type="submit"
              class="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
            >
              Guardar Crédito
            </button>
            <button
              type="button"
              @click="showAddCreditModal = false"
              class="px-8 py-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-sm font-bold text-white/50"
            >
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Abono o Pago Parcial -->
    <div v-if="showAddPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
      <div class="absolute inset-0 cursor-pointer" @click="showAddPaymentModal = false"></div>
      <div class="relative w-full max-w-2xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white max-h-[90vh] overflow-y-auto custom-scrollbar">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Abono o Pago</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Registrar pago a una cuenta por pagar</p>

        <form @submit.prevent="submitPayment" class="space-y-6">
          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Crédito al que aplica *</label>
            <select required v-model="newPayment.credit_id" class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold text-white focus:outline-none cursor-pointer">
              <option value="" disabled class="bg-slate-900 text-white/50">Seleccione la deuda a pagar...</option>
              <option v-for="cred in pendingCredits" :key="cred.id" :value="cred.id" class="bg-slate-900">
                {{ cred.supplier_name }} - Ref: {{ cred.invoice_number || 'N/A' }} (Saldo: Q{{ (cred.amount - cred.total_paid).toFixed(2) }})
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Monto del abono (GTQ) *</label>
              <input
                type="number"
                required
                step="0.01"
                min="0.01"
                v-model="newPayment.amount"
                placeholder="0.00"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white placeholder:text-white/20"
              />
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Fecha del Pago *</label>
              <input
                type="date"
                required
                v-model="newPayment.payment_date"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white cursor-pointer"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Cuenta bancaria usada *</label>
              <select required v-model="newPayment.bank_account_id" class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold text-white focus:outline-none cursor-pointer">
                <option value="" disabled class="bg-slate-900 text-white/50">Seleccione cuenta...</option>
                <option v-for="acc in accounts" :key="acc.id" :value="acc.id" class="bg-slate-900">{{ acc.nombre_banco }} ({{ acc.numero_cuenta }})</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Número de cheque</label>
              <input
                type="text"
                v-model="newPayment.check_number"
                placeholder="Opcional..."
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white placeholder:text-white/20 uppercase"
              />
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Comprobante (opcional)</label>
            <input
              type="file"
              @change="handleReceiptFile"
              accept=".jpg,.jpeg,.png,.pdf"
              class="w-full text-white/60 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-primary/20 file:text-primary hover:file:bg-primary/30 file:transition-all cursor-pointer bg-black/40 border border-white/10 rounded-2xl p-2"
            />
          </div>

          <div class="flex gap-4 pt-4">
            <button
              type="submit"
              class="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
            >
              Registrar Abono
            </button>
            <button
              type="button"
              @click="showAddPaymentModal = false"
              class="px-8 py-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-sm font-bold text-white/50"
            >
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import {
  PlusIcon,
  MagnifyingGlassIcon,
  ArrowTrendingUpIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ArrowDownTrayIcon,
  EllipsisVerticalIcon,
  CurrencyDollarIcon,
} from '@heroicons/vue/24/outline';

const API_URL = '/concretos-oriente/Backend/api/v1';

const credits = ref<any[]>([]);
const suppliers = ref<any[]>([]);
const projects = ref<any[]>([]);
const accounts = ref<any[]>([]);

const searchTerm = ref('');
const statusFilter = ref('all');
const showAddCreditModal = ref(false);
const showAddPaymentModal = ref(false);

const newCredit = ref({
  supplier_id: '',
  project_id: '',
  invoice_number: '',
  purchase_date: new Date().toISOString().split('T')[0],
  amount: '',
  due_date: new Date().toISOString().split('T')[0],
  observations: ''
});

const newPayment = ref({
  credit_id: '',
  amount: '',
  payment_date: new Date().toISOString().split('T')[0],
  bank_account_id: '',
  check_number: '',
  receipt: null as File | null
});

onMounted(() => {
  fetchCredits();
  fetchSuppliers();
  fetchProjects();
  fetchAccounts();
});

const fetchCredits = async () => {
  try {
    const res = await axios.get(`${API_URL}/credits`);
    if (res.data.status === 'success') {
      credits.value = res.data.data;
    }
  } catch (error) {
    console.error(error);
  }
};

const fetchSuppliers = async () => {
  try {
    const res = await axios.get(`${API_URL}/suppliers`);
    if (res.data.status === 'success') {
      suppliers.value = res.data.data;
    }
  } catch (error) {
    console.error(error);
  }
};

const fetchProjects = async () => {
  try {
    const res = await fetch(`${API_URL}/projects`);
    const data = await res.json();
    if (data.status === 'success') {
      projects.value = data.data;
    }
  } catch (error) {
    console.error(error);
  }
};

const fetchAccounts = async () => {
  try {
    const res = await axios.get(`${API_URL}/bank-accounts`);
    if (res.data.status === 'success') {
      accounts.value = res.data.data;
    }
  } catch (error) {
    console.error(error);
  }
};

const totalOriginalCredit = computed(() => {
  return credits.value.reduce((sum, c) => sum + Number(c.amount), 0);
});

const totalPaid = computed(() => {
  return credits.value.reduce((sum, c) => sum + Number(c.total_paid), 0);
});

const totalConsolidatedDebt = computed(() => {
  return credits.value.reduce((sum, c) => {
    const remaining = Number(c.amount) - Number(c.total_paid);
    return sum + (remaining > 0 ? remaining : 0);
  }, 0);
});

const paymentProgressPercent = computed(() => {
  if (totalOriginalCredit.value === 0) return 0;
  return (totalPaid.value / totalOriginalCredit.value) * 100;
});

const expiredCredits = computed(() => {
  const today = new Date().toISOString().split('T')[0];
  return credits.value.filter(c => c.due_date < today && c.status !== 'Pagado');
});

const expiredCreditsCount = computed(() => expiredCredits.value.length);
const expiredDebtAmount = computed(() => {
  return expiredCredits.value.reduce((sum, c) => sum + (Number(c.amount) - Number(c.total_paid)), 0);
});

const nextDueDate = computed(() => {
  const pending = credits.value.filter(c => c.status !== 'Pagado');
  if (pending.length === 0) return null;
  
  const next = pending.reduce((earliest, current) => {
    if (!earliest) return current;
    return current.due_date < earliest.due_date ? current : earliest;
  }, null);

  return next ? next.due_date : null;
});

const pendingCredits = computed(() => {
  return credits.value.filter(c => c.status !== 'Pagado');
});

const isExpired = (date: string, status: string) => {
  if (status === 'Pagado') return false;
  const today = new Date().toISOString().split('T')[0];
  return date < today;
};

const filteredCredits = computed(() => {
  return credits.value.filter(c => {
    const search = searchTerm.value.toLowerCase();
    const supName = (c.supplier_name || '').toLowerCase();
    const invNum = (c.invoice_number || '').toLowerCase();
    
    const matchesSearch = supName.includes(search) || invNum.includes(search);
    
    if (statusFilter.value === 'all') return matchesSearch;
    return c.status === statusFilter.value && matchesSearch;
  });
});

const currentPage = ref(1);
const itemsPerPage = 10;

const paginatedCredits = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  return filteredCredits.value.slice(start, end);
});

const totalPages = computed(() => {
  return Math.ceil(filteredCredits.value.length / itemsPerPage) || 1;
});

const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };
const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };

const handleReceiptFile = (e: any) => {
  newPayment.value.receipt = e.target.files[0];
};

const submitCredit = async () => {
  const formData = new FormData();
  Object.entries(newCredit.value).forEach(([key, value]) => {
    formData.append(key, value as string);
  });

  try {
    const res = await axios.post(`${API_URL}/credits`, formData);
    if (res.data.status === 'success') {
      showAddCreditModal.value = false;
      newCredit.value = {
        supplier_id: '', project_id: '', invoice_number: '',
        purchase_date: new Date().toISOString().split('T')[0], amount: '',
        due_date: new Date().toISOString().split('T')[0], observations: ''
      };
      fetchCredits();
      Swal.fire({
        title: '¡Éxito!',
        text: 'Crédito registrado correctamente',
        icon: 'success',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
        customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
      });
    } else {
      throw new Error(res.data.message);
    }
  } catch (error: any) {
    Swal.fire({
      title: 'Error',
      text: error.message || 'Error de red',
      icon: 'error',
      background: '#0f172a',
      color: '#fff',
      confirmButtonColor: '#6366f1',
      customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
    });
  }
};

const submitPayment = async () => {
  const formData = new FormData();
  Object.entries(newPayment.value).forEach(([key, value]) => {
    if (key !== 'receipt') {
      formData.append(key, value as string);
    }
  });

  if (newPayment.value.receipt) {
    formData.append('receipt', newPayment.value.receipt);
  }

  try {
    const res = await axios.post(`${API_URL}/credit-payments`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    if (res.data.status === 'success') {
      showAddPaymentModal.value = false;
      newPayment.value = {
        credit_id: '', amount: '', payment_date: new Date().toISOString().split('T')[0],
        bank_account_id: '', check_number: '', receipt: null
      };
      fetchCredits();
      Swal.fire({
        title: '¡Abono Exitoso!',
        text: 'El pago ha sido registrado y el saldo actualizado',
        icon: 'success',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
        customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
      });
    } else {
      throw new Error(res.data.message);
    }
  } catch (error: any) {
    Swal.fire({
      title: 'Error',
      text: error.message || 'Error de red',
      icon: 'error',
      background: '#0f172a',
      color: '#fff',
      confirmButtonColor: '#6366f1',
      customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
    });
  }
};
</script>
