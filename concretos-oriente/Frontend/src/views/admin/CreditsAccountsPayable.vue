<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">

    <!-- Upper header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Créditos y Cuentas por Pagar</h2>
        <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Visión consolidada de obligaciones financieras y líneas de crédito</p>
      </div>
      <button
        @click="showAddInvoiceModal = true"
        class="glass-button-primary bg-primary border-primary border text-white px-8 py-5 rounded-3xl text-xs font-black uppercase tracking-widest shadow-2xl flex items-center gap-3 hover:scale-105 active:scale-95 transition-all"
      >
        <PlusIcon class="w-4 h-4" /> Registrar Factura
      </button>
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
          <div class="flex items-center mt-4">
            <span class="flex items-center text-[10px] font-black bg-white/20 px-3 py-1 rounded-xl gap-1">
              <ArrowTrendingUpIcon class="w-3.5 h-3.5" />
              +4.2% MES ANTERIOR
            </span>
          </div>
        </div>
        <div class="flex justify-between items-end border-t border-white/15 pt-6 mt-4">
          <div>
            <p class="text-[10px] font-black opacity-60 uppercase tracking-widest">Próximo Vencimiento</p>
            <p class="text-sm font-black italic mt-1">14 OCT, 2026</p>
          </div>
          <CurrencyDollarIcon class="w-10 h-10 opacity-30" />
        </div>
      </div>

      <!-- Credit Lines Container -->
      <div class="col-span-12 lg:col-span-8 grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Line 1 -->
        <div class="glass-card p-10 rounded-[44px] border border-white/5 flex flex-col justify-between h-[260px]">
          <div>
            <div class="flex justify-between items-center">
              <span class="text-[10px] font-black text-white/40 uppercase tracking-[0.25em]">Línea: Banco Central</span>
              <span class="px-3 py-1 bg-primary/20 text-primary border border-primary/20 rounded-full text-[9px] font-black uppercase tracking-widest">Corporativo</span>
            </div>
            <div class="mt-8">
              <div class="flex justify-between text-xs font-bold mb-3.5">
                <span class="text-white/40">Crédito Utilizado</span>
                <span class="text-primary font-black">65%</span>
              </div>
              <div class="w-full bg-white/5 h-2.5 rounded-full overflow-hidden">
                <div class="bg-primary h-full rounded-full" style="width: 65%"></div>
              </div>
            </div>
          </div>
          <div class="flex justify-between border-t border-white/5 pt-5 mt-4">
            <div>
              <p class="text-[10px] font-black text-white/30 uppercase tracking-widest">Disponible</p>
              <p class="text-lg font-black italic mt-1">Q350,000.00</p>
            </div>
            <div class="text-right">
              <p class="text-[10px] font-black text-white/30 uppercase tracking-widest">Límite</p>
              <p class="text-lg font-black text-white/50 italic mt-1">Q1.0M</p>
            </div>
          </div>
        </div>

        <!-- Line 2 -->
        <div class="glass-card p-10 rounded-[44px] border border-white/5 flex flex-col justify-between h-[260px]">
          <div>
            <div class="flex justify-between items-center">
              <span class="text-[10px] font-black text-white/40 uppercase tracking-[0.25em]">Línea: Proveedores S.A.</span>
              <span class="px-3 py-1 bg-indigo-500/20 text-indigo-400 border border-indigo-500/20 rounded-full text-[9px] font-black uppercase tracking-widest">Crédito Comercial</span>
            </div>
            <div class="mt-8">
              <div class="flex justify-between text-xs font-bold mb-3.5">
                <span class="text-white/40">Crédito Utilizado</span>
                <span class="text-indigo-400 font-black">22%</span>
              </div>
              <div class="w-full bg-white/5 h-2.5 rounded-full overflow-hidden">
                <div class="bg-indigo-500 h-full rounded-full" style="width: 22%"></div>
              </div>
            </div>
          </div>
          <div class="flex justify-between border-t border-white/5 pt-5 mt-4">
            <div>
              <p class="text-[10px] font-black text-white/30 uppercase tracking-widest">Disponible</p>
              <p class="text-lg font-black italic mt-1">Q780,000.00</p>
            </div>
            <div class="text-right">
              <p class="text-[10px] font-black text-white/30 uppercase tracking-widest">Límite</p>
              <p class="text-lg font-black text-white/50 italic mt-1">Q1.0M</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Grid Content -->
    <div class="grid grid-cols-12 gap-10">

      <!-- Left column sidebar details -->
      <div class="col-span-12 xl:col-span-4 space-y-8">

        <!-- Upcoming Payments Calendar Widget -->
        <div class="glass-card p-8 rounded-[40px] border border-white/5 space-y-6">
          <div class="flex justify-between items-center border-b border-white/5 pb-4">
            <h4 class="text-sm font-black uppercase tracking-widest text-white/60">Vencimientos Clave</h4>
            <CalendarDaysIcon class="w-5 h-5 text-primary" />
          </div>

          <div class="space-y-4">
            <div
              v-for="(pay, pIdx) in upcomingPayments"
              :key="pIdx"
              class="flex items-center gap-4 p-4 hover:bg-white/5 rounded-3xl transition-all cursor-pointer group border border-transparent hover:border-white/5"
            >
              <div :class="[
                'w-14 h-14 rounded-2xl flex flex-col items-center justify-center font-black',
                pay.status === 'Urgente'
                  ? 'text-rose-400 bg-rose-500/10 border border-rose-500/20'
                  : 'text-primary bg-primary/10 border border-primary/20'
              ]">
                <span class="text-[9px] mt-0.5 tracking-wider font-extrabold text-white/30">OCT</span>
                <span class="text-base font-black italic -mt-1">{{ pay.date.split(' ')[0] }}</span>
              </div>

              <div class="flex-1 min-w-0">
                <h5 class="font-extrabold text-white truncate text-sm group-hover:text-primary transition-colors uppercase">{{ pay.provider }}</h5>
                <p class="text-[10px] text-white/30 font-bold mt-1 uppercase tracking-wider">{{ pay.desc }}</p>
              </div>

              <div class="text-right flex flex-col items-end">
                <span class="font-black text-sm italic">Q{{ pay.amount.toLocaleString('es-GT') }}</span>
                <span :class="[
                  'text-[8px] font-black uppercase tracking-widest mt-1.5 px-2 py-0.5 rounded',
                  pay.status === 'Urgente' ? 'bg-rose-500/20 text-rose-400' : 'bg-primary/20 text-primary'
                ]">
                  {{ pay.status }}
                </span>
              </div>
            </div>
          </div>

          <button class="w-full mt-2 py-4 text-[10px] font-black uppercase tracking-widest text-primary hover:bg-primary/15 rounded-2xl border border-primary/20 transition-all text-center block">
            Ver Calendario Completo
          </button>
        </div>

        <!-- Insights / Ideas -->
        <div class="glass-card p-10 rounded-[44px] border border-dashed border-primary/30 bg-gradient-to-br from-primary/5 to-transparent relative overflow-hidden">
          <div class="flex gap-4 items-start">
            <div class="bg-primary/20 p-3.5 rounded-2xl text-primary border border-white/10 shadow-lg shadow-primary/10">
              <LightBulbIcon class="w-6 h-6 animate-bounce" />
            </div>
            <div>
              <h5 class="text-base font-black italic uppercase tracking-wider text-white">Optimización de Caja</h5>
              <p class="text-xs text-white/50 font-medium leading-relaxed mt-2.5">
                Liquidando la factura de <strong>Acero Estructural Co.</strong> antes del día 12 genera un descuento del <strong>2% general</strong>, ahorrando de forma líquida <strong>Q249.00</strong> inmediato.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Right column: Detailed Invoice Ledger -->
      <div class="col-span-12 xl:col-span-8">
        <div class="glass-card rounded-[48px] overflow-hidden border border-white/5 shadow-2xl">
          <!-- Headers with Filters -->
          <div class="p-10 border-b border-white/5 bg-white/5 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
              <h4 class="text-2xl font-black text-white italic uppercase tracking-tighter">Listado de Facturas</h4>
              <p class="text-[10px] font-bold text-white/30 uppercase tracking-widest mt-1">Cuentas por pagar e historial indexado</p>
            </div>

            <!-- Filtering Controls -->
            <div class="flex flex-wrap items-center gap-3">
              <div class="relative">
                <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-white/40" />
                <input
                  type="text"
                  v-model="searchTerm"
                  placeholder="Buscar proveedor o ID..."
                  class="glass-input pl-10 pr-4 py-3 text-xs w-48 rounded-xl font-bold uppercase tracking-widest placeholder:text-white/20 text-white"
                />
              </div>

              <select
                v-model="statusFilter"
                class="bg-white/5 border border-white/10 p-3 text-xs rounded-xl text-white font-bold cursor-pointer uppercase focus:outline-none"
              >
                <option value="all" class="bg-slate-950 text-white">Todos</option>
                <option value="vencida" class="bg-slate-950 text-white">Vencidas</option>
                <option value="programada" class="bg-slate-950 text-white">Programadas</option>
                <option value="pendiente" class="bg-slate-950 text-white">Pendientes</option>
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
                  <th class="px-10 py-8">Proveedor</th>
                  <th class="px-10 py-8">Factura ID</th>
                  <th class="px-10 py-8 text-right">Monto</th>
                  <th class="px-10 py-8">Vencimiento</th>
                  <th class="px-10 py-8 text-center">Estado</th>
                  <th class="px-10 py-8"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr v-if="filteredInvoices.length === 0">
                  <td colspan="6" class="px-10 py-20 text-center text-white/30 font-bold uppercase tracking-widest text-xs">
                    No se encontraron facturas registradas con este criterio.
                  </td>
                </tr>
                <tr
                  v-for="inv in filteredInvoices"
                  :key="inv.id"
                  class="hover:bg-white/5 transition-all duration-300 group"
                >
                  <td class="px-10 py-6">
                    <div class="flex items-center gap-4">
                      <div class="w-9 h-9 rounded-full bg-primary/20 text-primary font-black italic flex items-center justify-center text-sm border border-white/10 shadow-md">
                        {{ inv.initials }}
                      </div>
                      <span class="font-extrabold text-white text-sm uppercase tracking-tight italic">{{ inv.provider }}</span>
                    </div>
                  </td>
                  <td class="px-10 py-6 font-semibold text-white/40 text-xs">{{ inv.invoiceId }}</td>
                  <td class="px-10 py-6 text-right font-black italic text-base text-white">
                    Q{{ inv.amount.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                  </td>
                  <td :class="[
                    'px-10 py-6 text-xs font-bold leading-none',
                    inv.status === 'vencida' ? 'text-rose-400 font-extrabold italic' : 'text-white/60'
                  ]">
                    {{ inv.dueDate }}
                  </td>
                  <td class="px-10 py-6 text-center">
                    <span :class="[
                      'px-3.5 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border',
                      inv.status === 'vencida' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' :
                      inv.status === 'programada' ? 'bg-primary/10 text-primary border-primary/20' :
                      'bg-white/5 text-white/40 border-white/10'
                    ]">
                      {{ inv.status }}
                    </span>
                  </td>
                  <td class="px-10 py-6 text-center text-white/20 group-hover:text-white transition-colors cursor-pointer">
                    <EllipsisVerticalIcon class="w-4 h-4" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination / Bottom controls -->
          <div class="p-10 border-t border-white/5 bg-black/20 flex flex-col md:flex-row justify-between items-center gap-6">
            <p class="text-[10px] font-black text-white/20 uppercase tracking-widest">
              Mostrando {{ filteredInvoices.length }} de {{ invoices.length }} facturas registradas
            </p>
            <div class="flex items-center gap-3">
              <button class="w-10 h-10 rounded-xl border border-white/10 flex items-center justify-center hover:bg-white/5 transition-all">
                <ChevronLeftIcon class="w-5 h-5 text-white/40" />
              </button>
              <button class="w-10 h-10 rounded-xl bg-primary text-white font-black italic text-sm">1</button>
              <button class="w-10 h-10 rounded-xl border border-white/10 flex items-center justify-center hover:bg-white/5 transition-all">
                <ChevronRightIcon class="w-5 h-5 text-white/40" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Register Invoice Modal -->
    <div v-if="showAddInvoiceModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
      <div class="absolute inset-0 cursor-pointer" @click="showAddInvoiceModal = false"></div>
      <div class="relative w-full max-w-xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Registrar Factura</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Capturar nueva obligación comercial o de proveedor</p>

        <form @submit.prevent="handleCreateInvoice" class="space-y-6">
          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Proveedor / Compañía</label>
            <input
              type="text"
              required
              v-model="providerName"
              placeholder="E.g. CONCRETOS DEL NORTE SA"
              class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
            />
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">ID de Factura / Referencia</label>
            <input
              type="text"
              required
              v-model="invoiceRefNum"
              placeholder="E.g. INV-99021"
              class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
            />
          </div>

          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Monto de Factura (Q)</label>
              <input
                type="number"
                required
                step="0.01"
                v-model="amountVal"
                placeholder="0.00"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white placeholder:text-white/20"
              />
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Fecha de Vencimiento</label>
              <input
                type="date"
                required
                v-model="dueDateVal"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white cursor-pointer"
              />
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Estado de Programación</label>
            <div class="flex bg-white/5 border border-white/10 rounded-2xl p-1">
              <button
                type="button"
                @click="statusVal = 'pendiente'"
                :class="[
                  'flex-1 py-3 text-[10px] font-black uppercase tracking-wider rounded-xl transition-all',
                  statusVal === 'pendiente' ? 'bg-white/10 text-white' : 'text-white/40'
                ]"
              >
                Pendiente Normal
              </button>
              <button
                type="button"
                @click="statusVal = 'programada'"
                :class="[
                  'flex-1 py-3 text-[10px] font-black uppercase tracking-wider rounded-xl transition-all',
                  statusVal === 'programada' ? 'bg-primary text-white shadow-md' : 'text-white/40'
                ]"
              >
                Programada (Para Pago)
              </button>
            </div>
          </div>

          <div class="flex gap-4 pt-4">
            <button
              type="submit"
              class="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
            >
              Confirmar Registro
            </button>
            <button
              type="button"
              @click="showAddInvoiceModal = false"
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
import { ref, computed } from 'vue';
import {
  PlusIcon,
  MagnifyingGlassIcon,
  ArrowTrendingUpIcon,
  CalendarDaysIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ArrowDownTrayIcon,
  EllipsisVerticalIcon,
  LightBulbIcon,
  CurrencyDollarIcon,
} from '@heroicons/vue/24/outline';

interface Invoice {
  id: string;
  provider: string;
  invoiceId: string;
  amount: number;
  dueDate: string;
  status: 'vencida' | 'programada' | 'pendiente';
  initials: string;
}

const searchTerm = ref('');
const statusFilter = ref('all');
const showAddInvoiceModal = ref(false);

const providerName = ref('');
const invoiceRefNum = ref('');
const amountVal = ref('');
const dueDateVal = ref('');
const statusVal = ref('pendiente');

const invoices = ref<Invoice[]>([
  {
    id: '1',
    provider: 'Soluciones Eléctricas S.A.',
    invoiceId: '#INV-98231',
    amount: 12450.0,
    dueDate: 'Ayer (Vencido)',
    status: 'vencida',
    initials: 'S',
  },
  {
    id: '2',
    provider: 'Constructora Horizon',
    invoiceId: '#INV-88712',
    amount: 45000.0,
    dueDate: '22 Oct, 2026',
    status: 'programada',
    initials: 'C',
  },
  {
    id: '3',
    provider: 'Maquinaria Pesada Ltd.',
    invoiceId: '#INV-77121',
    amount: 115200.0,
    dueDate: '05 Nov, 2026',
    status: 'pendiente',
    initials: 'M',
  },
  {
    id: '4',
    provider: 'Agregados & Mezclas',
    invoiceId: '#INV-00911',
    amount: 8900.0,
    dueDate: '14 Oct, 2026',
    status: 'pendiente',
    initials: 'A',
  },
  {
    id: '5',
    provider: 'Tuberías del Norte',
    invoiceId: '#INV-44123',
    amount: 24300.0,
    dueDate: '30 Oct, 2026',
    status: 'programada',
    initials: 'T',
  },
]);

const upcomingPayments = [
  { provider: 'Acero Estructural Co.', date: '12 OCT', status: 'Urgente', amount: 12450, desc: 'Vence en 2 días' },
  { provider: 'Materiales CEMEX', date: '15 OCT', status: 'Normal', amount: 85200, desc: 'Programado' },
  { provider: 'Logística Global', date: '20 OCT', status: 'Normal', amount: 3120, desc: 'Pendiente' },
];

const totalConsolidatedDebt = computed(() => {
  const sum = invoices.value.reduce((acc, item) => acc + item.amount, 0);
  return 2300000 + sum;
});

const filteredInvoices = computed(() =>
  invoices.value.filter((item) => {
    const matchesSearch =
      item.provider.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
      item.invoiceId.toLowerCase().includes(searchTerm.value.toLowerCase());
    if (statusFilter.value === 'all') return matchesSearch;
    return item.status === statusFilter.value && matchesSearch;
  })
);

function handleCreateInvoice() {
  if (!providerName.value || !invoiceRefNum.value || !amountVal.value || !dueDateVal.value) return;

  const initials = providerName.value.charAt(0).toUpperCase();
  const newInvoice: Invoice = {
    id: Math.random().toString(36).substring(2, 9),
    provider: providerName.value,
    invoiceId: invoiceRefNum.value.startsWith('#') ? invoiceRefNum.value : `#${invoiceRefNum.value}`,
    amount: parseFloat(amountVal.value),
    dueDate: new Date(dueDateVal.value).toLocaleDateString('es-GT', { day: 'numeric', month: 'short', year: 'numeric' }),
    status: statusVal.value as Invoice['status'],
    initials,
  };

  invoices.value.unshift(newInvoice);
  showAddInvoiceModal.value = false;

  providerName.value = '';
  invoiceRefNum.value = '';
  amountVal.value = '';
  dueDateVal.value = '';
  statusVal.value = 'pendiente';
}
</script>
