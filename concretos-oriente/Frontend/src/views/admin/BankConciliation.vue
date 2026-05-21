<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">

    <!-- Search & Intro -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Bancos y Conciliación</h2>
        <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Gestión inteligente de saldos y extractos contables</p>
      </div>
      <div class="flex items-center gap-4 relative z-20">
        <div class="relative">
          <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/40" />
          <input
            v-model="searchTerm"
            class="glass-input pl-12 pr-6 py-4 rounded-2xl text-xs w-64 uppercase tracking-widest placeholder:text-white/20 text-white font-bold"
            placeholder="Buscar transacción..."
            type="text"
          />
        </div>
      </div>
    </div>

    <!-- Summary Row -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <!-- Account Card 1 -->
      <div class="glass-card p-10 rounded-[40px] flex flex-col justify-between h-52 cursor-pointer group relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="flex justify-between items-start">
          <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.3em]">Banco Santander</span>
          <div class="bg-primary/20 p-4 rounded-2xl text-primary border border-white/10 shadow-lg shadow-primary/20">
            <BuildingLibraryIcon class="w-6 h-6" />
          </div>
        </div>
        <div class="mt-4">
          <p class="text-white/40 text-[10px] font-bold uppercase tracking-widest">Cuenta Corriente Operativa</p>
          <h3 class="text-4xl font-black tracking-tighter italic text-white mt-2">Q450,230.00</h3>
          <div class="mt-4 flex items-center bg-primary/15 text-primary border border-primary/20 px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest inline-flex gap-2">
            <CheckIcon class="w-3.5 h-3.5" />
            Conciliado hace 2h
          </div>
        </div>
      </div>

      <!-- Account Card 2 -->
      <div class="glass-card p-10 rounded-[40px] flex flex-col justify-between h-52 cursor-pointer group relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-orange-500/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="flex justify-between items-start">
          <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.3em]">BBVA Corporate</span>
          <div class="bg-orange-500/20 p-4 rounded-2xl text-orange-400 border border-white/10 shadow-lg shadow-orange-500/10">
            <CreditCardIcon class="w-6 h-6" />
          </div>
        </div>
        <div class="mt-4">
          <p class="text-white/40 text-[10px] font-bold uppercase tracking-widest">Tarjeta de Compras Materiales</p>
          <h3 class="text-4xl font-black tracking-tighter italic text-white mt-2">Q12,450.15</h3>
          <div class="mt-4 flex items-center bg-orange-500/15 text-orange-400 border border-orange-500/20 px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest inline-flex gap-2">
            <ExclamationTriangleIcon class="w-3.5 h-3.5" />
            {{ pendingCount }} Pendientes
          </div>
        </div>
      </div>

      <!-- Bento Upload -->
      <div class="glass-card p-10 rounded-[40px] flex flex-col items-center justify-center text-center cursor-pointer border-2 border-dashed border-white/10 hover:border-primary/40 hover:bg-white/5 transition-all group">
        <div class="bg-primary/20 text-primary p-5 rounded-full mb-4 shadow-lg shadow-primary/10 group-hover:scale-110 transition-transform duration-500">
          <CloudArrowUpIcon class="w-8 h-8" />
        </div>
        <h4 class="text-lg font-black italic uppercase tracking-wider text-white">Cargar Extracto</h4>
        <p class="text-xs text-white/40 mt-2 max-w-xs font-medium">
          Sube o arrastra archivos PDF o CSV para iniciar la conciliación automatizada.
        </p>
      </div>
    </section>

    <!-- Main Reconciliation Engine -->
    <section class="grid grid-cols-1 lg:grid-cols-4 gap-10 items-start">
      <!-- Sidebar Controls -->
      <div class="space-y-8 lg:col-span-1">
        <!-- Filters Card -->
        <div class="glass-card p-8 rounded-[36px] border border-white/5 space-y-6">
          <h5 class="text-xs font-black uppercase tracking-widest text-white/50 border-b border-white/5 pb-3">Filtros</h5>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Periodo Evaluado</label>
            <select
              v-model="selectedPeriod"
              class="w-full bg-white/5 border border-white/10 rounded-2xl text-xs font-bold py-3.5 px-4 text-white focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-transparent cursor-pointer"
            >
              <option value="Últimos 30 días" class="bg-slate-900 text-white">Últimos 30 días</option>
              <option value="Mes actual" class="bg-slate-900 text-white">Mes actual</option>
              <option value="Trimestre anterior" class="bg-slate-900 text-white">Trimestre anterior</option>
            </select>
          </div>

          <div class="space-y-4 pt-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1 block">Estado del Flujo</label>
            <div class="flex flex-col gap-2">
              <button
                v-for="st in statusFilters"
                :key="st.id"
                @click="selectedStatus = st.id"
                :class="['w-full flex items-center justify-between px-5 py-3.5 rounded-2xl text-xs font-black uppercase tracking-wider transition-all duration-300', selectedStatus === st.id ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'bg-white/5 text-white/40 hover:text-white hover:bg-white/10']"
              >
                <span>{{ st.label }}</span>
                <span class="text-[10px] bg-white/10 px-2 py-0.5 rounded-lg">{{ st.count }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- AI Feature Component -->
        <div class="glass-card p-10 rounded-[40px] border border-primary/20 relative overflow-hidden bg-gradient-to-br from-primary/10 via-transparent to-transparent flex flex-col justify-between">
          <div class="flex justify-between items-start mb-6">
            <div class="bg-primary/20 p-3 rounded-2xl text-primary border border-white/15">
              <SparklesIcon class="w-5 h-5 animate-pulse" />
            </div>
            <span class="text-[9px] font-black text-primary uppercase tracking-[0.25em] bg-primary/10 border border-primary/20 px-2.5 py-1 rounded-full">Inteligente</span>
          </div>
          <div>
            <h5 class="text-xl font-black italic uppercase tracking-tighter text-white">Contabilidad Smart Match</h5>
            <p class="text-xs font-medium text-white/50 mt-3 leading-relaxed">
              Nuestra IA analizó su cartola e indexó <strong>{{ pendingLinkedCount }} coincidencias precisas</strong> pendientes según montos e instructivo.
            </p>
            <button
              @click="handleAutoConciliation"
              :disabled="pendingLinkedCount === 0"
              class="mt-8 w-full glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl disabled:opacity-30 disabled:pointer-events-none hover:shadow-primary/30 hover:-translate-y-0.5 active:translate-y-0 transition-all text-center"
            >
              Conciliar Automáticamente
            </button>
          </div>
        </div>
      </div>

      <!-- Transactions Table Column -->
      <div class="lg:col-span-3 space-y-8">
        <div class="glass-card rounded-[56px] overflow-hidden border border-white/5 shadow-2xl">
          <!-- Table Header -->
          <div class="p-10 border-b border-white/5 bg-white/5 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
              <h4 class="text-2xl font-black text-white italic uppercase tracking-tighter">Transacciones</h4>
              <p class="text-[10px] font-bold text-white/30 uppercase tracking-widest mt-1">
                Mostrando registros según el filtro: {{ selectedStatus.toUpperCase() }}
              </p>
            </div>
            <button class="px-6 py-3.5 border border-white/15 rounded-2xl text-xs font-black uppercase tracking-widest flex items-center gap-3 text-white/60 hover:text-white hover:bg-white/5 transition-all">
              <ArrowDownTrayIcon class="w-4 h-4" /> Exportar
            </button>
          </div>

          <!-- Content Table -->
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="text-[10px] font-extrabold text-white/30 uppercase tracking-widest border-b border-white/5">
                  <th class="px-10 py-8">Fecha</th>
                  <th class="px-10 py-8">Descripción de Banco</th>
                  <th class="px-10 py-8">Vínculo al Sistema</th>
                  <th class="px-10 py-8 text-right">Monto</th>
                  <th class="px-10 py-8 text-center">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr v-if="filteredTransactions.length === 0">
                  <td colspan="5" class="px-10 py-20 text-center text-white/30 font-bold uppercase tracking-widest text-xs">
                    Ninguna transacción pendiente coincide con el filtro.
                  </td>
                </tr>
                <tr
                  v-for="tx in filteredTransactions"
                  :key="tx.id"
                  class="hover:bg-white/5 transition-all duration-300 group"
                >
                  <td class="px-10 py-8 font-semibold text-white/50 text-xs">{{ tx.date }}</td>
                  <td class="px-10 py-8">
                    <h5 class="font-extrabold text-base text-white tracking-tight uppercase italic">{{ tx.bankDesc }}</h5>
                    <p class="text-[10px] font-bold text-white/30 tracking-wider uppercase mt-1">ID: {{ tx.id }} • {{ tx.detail }}</p>
                  </td>
                  <td class="px-10 py-8">
                    <div v-if="tx.isLinked" class="inline-flex items-center gap-2 bg-primary/10 border border-primary/20 px-3.5 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest text-primary">
                      <CheckCircleIcon class="w-3.5 h-3.5" />
                      {{ tx.refSystem }}
                    </div>
                    <div v-else class="inline-flex items-center gap-2 bg-rose-500/10 border border-rose-500/25 px-3.5 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest text-rose-400">
                      <ExclamationTriangleIcon class="w-3.5 h-3.5" />
                      Sin sugerencia
                    </div>
                  </td>
                  <td :class="['px-10 py-8 text-right font-black italic text-xl', tx.type === 'in' ? 'text-primary' : 'text-white']">
                    {{ tx.type === 'in' ? '+' : '-' }}Q{{ tx.amount.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                  </td>
                  <td class="px-10 py-8 text-center">
                    <div v-if="tx.status === 'pending'" class="flex items-center justify-center gap-3">
                      <button
                        v-if="tx.isLinked"
                        @click="handleConfirmMatch(tx.id)"
                        class="bg-primary hover:bg-primary-container text-white text-[9px] font-black uppercase tracking-widest px-4 py-2 rounded-xl border border-white/5 transition-all hover:shadow-lg hover:shadow-primary/20"
                      >
                        Confirmar Match
                      </button>
                      <button
                        v-else
                        @click="handleManualAction(tx.id)"
                        class="bg-white/5 hover:bg-white/10 text-white/60 hover:text-white text-[9px] font-black uppercase tracking-widest px-4 py-2 rounded-xl border border-white/10 transition-all"
                      >
                        Asignar Manual
                      </button>
                    </div>
                    <div v-else class="inline-flex items-center gap-2 text-primary bg-primary/10 px-3.5 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border border-primary/25">
                      <CheckIcon class="w-3.5 h-3.5" />
                      {{ tx.status === 'matched' ? 'Match Exitoso' : 'Conciliado Manual' }}
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Table Footer Pagination -->
          <div class="p-10 border-t border-white/5 bg-black/20 flex flex-col md:flex-row justify-between items-center gap-6">
            <p class="text-[10px] font-black text-white/20 uppercase tracking-widest">
              Mostrando 1-{{ filteredTransactions.length }} de {{ filteredTransactions.length }} registros cargados
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
    </section>

    <!-- FAB Button -->
    <button
      @click="showAddModal = true"
      class="fixed bottom-12 right-12 w-20 h-20 rounded-[32px] glass-button-primary bg-primary border border-primary text-white shadow-2xl shadow-primary/40 flex items-center justify-center hover:scale-110 active:scale-95 transition-all z-40 group"
    >
      <PlusIcon class="w-10 h-10 group-hover:rotate-90 transition-transform duration-500" />
    </button>

    <!-- Manual Transaction Modal -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-md">
      <div class="absolute inset-0 cursor-pointer" @click="showAddModal = false"></div>
      <div class="relative w-full max-w-xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.2)]">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Nuevo Registro Manual</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Introducir transacción al flujo de conciliación</p>

        <form @submit.prevent="handleCreateTransaction" class="space-y-6">
          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Descripción del Movimiento</label>
            <input
              type="text"
              required
              v-model="newDesc"
              placeholder="E.g. COMPRA CEMENTOS GUATEMALA"
              class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase"
            />
          </div>

          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Monto en Quetzales (Q)</label>
              <input
                type="number"
                required
                step="0.01"
                v-model="newAmount"
                placeholder="0.00"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white placeholder:text-white/20"
              />
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Tipo de Operación</label>
              <div class="flex bg-white/5 border border-white/10 rounded-2xl p-1">
                <button type="button" @click="newType = 'out'" :class="['flex-1 py-3 text-[10px] font-black uppercase tracking-wider rounded-xl transition-all', newType === 'out' ? 'bg-white/10 text-white' : 'text-white/40']">
                  Salida (EGRESO)
                </button>
                <button type="button" @click="newType = 'in'" :class="['flex-1 py-3 text-[10px] font-black uppercase tracking-wider rounded-xl transition-all', newType === 'in' ? 'bg-primary text-white shadow-md' : 'text-white/40']">
                  Entrada (INGRESO)
                </button>
              </div>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Detalle / ID de Referencia</label>
            <input
              type="text"
              required
              v-model="newDetail"
              class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white uppercase"
            />
          </div>

          <div class="flex gap-4 pt-4">
            <button type="submit" class="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all">
              Guardar Registro
            </button>
            <button type="button" @click="showAddModal = false" class="px-8 py-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-sm font-bold text-white/50">
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  BuildingLibraryIcon,
  CreditCardIcon,
  CloudArrowUpIcon,
  SparklesIcon,
  MagnifyingGlassIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  PlusIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ArrowDownTrayIcon,
  CheckIcon
} from '@heroicons/vue/24/outline'

const searchTerm = ref('')
const selectedPeriod = ref('Últimos 30 días')
const selectedStatus = ref('pending')
const showAddModal = ref(false)
const newDesc = ref('')
const newDetail = ref('MANUAL SUITE')
const newAmount = ref('')
const newType = ref('out')

const transactions = ref([
  { id: '982130021', date: '12 Oct 2026', bankDesc: 'CONCRETOS DEL NORTE SA', detail: 'ID: 982130021', refSystem: 'Factura #F-4421', isLinked: true, amount: 2450.00, type: 'out', status: 'pending' },
  { id: 'SUC-055', date: '11 Oct 2026', bankDesc: 'TRANSFERENCIA DEPÓSITO', detail: 'SUCURSAL 055', refSystem: 'No se encontró coincidencia', isLinked: false, amount: 15000.00, type: 'in', status: 'pending' },
  { id: 'PAY-41', date: '10 Oct 2026', bankDesc: 'PAGO NOMINA SEM 41', detail: 'OPERACIONES CAMPO', refSystem: 'Planilla de Sueldos', isLinked: true, amount: 8230.10, type: 'out', status: 'pending' },
  { id: 'OC-2023-99', date: '09 Oct 2026', bankDesc: 'FERRETERIA INDUSTRIAL', detail: 'COMPRA HERRAMIENTAS', refSystem: 'OC #2023-99', isLinked: true, amount: 125.50, type: 'out', status: 'pending' },
  { id: 'SUP-108', date: '05 Oct 2026', bankDesc: 'ACEROS DE GUATE', detail: 'SUMINISTRO VARILLAS', refSystem: 'Factura #F-9021', isLinked: true, amount: 15400.00, type: 'out', status: 'matched' },
  { id: 'CLI-992', date: '02 Oct 2026', bankDesc: 'ANTICIPO SKYLINE TOWER', detail: 'TRANSF. CLIENTE', refSystem: 'Contrato #H-12', isLinked: true, amount: 75000.00, type: 'in', status: 'matched' }
])

const filteredTransactions = computed(() => {
  return transactions.value.filter(tx => {
    const matchesSearch = tx.bankDesc.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
      tx.refSystem.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
      tx.detail.toLowerCase().includes(searchTerm.value.toLowerCase())
    if (selectedStatus.value === 'all') return matchesSearch
    return tx.status === selectedStatus.value && matchesSearch
  })
})

const pendingCount = computed(() => transactions.value.filter(t => t.status === 'pending').length)
const matchedCount = computed(() => transactions.value.filter(t => t.status === 'matched').length)
const manualCount = computed(() => transactions.value.filter(t => t.status === 'manual').length)
const pendingLinkedCount = computed(() => transactions.value.filter(t => t.status === 'pending' && t.isLinked).length)

const statusFilters = computed(() => [
  { id: 'pending', label: 'Pendientes', count: pendingCount.value },
  { id: 'matched', label: 'Sugeridos (Match)', count: matchedCount.value },
  { id: 'manual', label: 'Manuales', count: manualCount.value },
  { id: 'all', label: 'Ver Todos', count: transactions.value.length },
])

function handleConfirmMatch(id: string) {
  const tx = transactions.value.find(t => t.id === id)
  if (tx) tx.status = 'matched'
}

function handleManualAction(id: string) {
  const tx = transactions.value.find(t => t.id === id)
  if (tx) { tx.status = 'manual'; tx.refSystem = 'Asignación Manual OK' }
}

function handleAutoConciliation() {
  transactions.value.forEach(tx => {
    if (tx.status === 'pending' && tx.isLinked) tx.status = 'matched'
  })
}

function handleCreateTransaction() {
  if (!newDesc.value || !newAmount.value) return
  transactions.value.unshift({
    id: Math.random().toString(36).substring(2, 9).toUpperCase(),
    date: 'Hoy, ' + new Date().toLocaleDateString('es-GT', { day: 'numeric', month: 'short' }),
    bankDesc: newDesc.value,
    detail: newDetail.value,
    refSystem: 'Registro Manual',
    isLinked: false,
    amount: parseFloat(newAmount.value),
    type: newType.value,
    status: 'manual'
  })
  showAddModal.value = false
  newDesc.value = ''
  newDetail.value = 'MANUAL SUITE'
  newAmount.value = ''
  newType.value = 'out'
}
</script>
