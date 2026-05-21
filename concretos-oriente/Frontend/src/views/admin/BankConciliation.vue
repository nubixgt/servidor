<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">

    <!-- Search & Intro -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Bancos y Conciliación</h2>
        <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Gestión inteligente de saldos y extractos contables</p>
      </div>
      <div class="flex items-center gap-4 relative z-20 flex-wrap">
        <button @click="showAccountModal = true" class="px-6 py-4 rounded-2xl border border-white/5 bg-white/5 text-white/80 font-black text-xs uppercase tracking-widest hover:bg-white/10 hover:scale-105 transition-all">Nueva Cuenta</button>
        <button @click="showConciliationModal = true" class="glass-button-primary bg-primary border-primary border text-white px-6 py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl flex items-center gap-2 hover:scale-105 transition-all">Conciliación</button>
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
    <section class="grid grid-cols-1 md:grid-cols-2 gap-8">
      
      <!-- Dynamic Accounts Cards -->
      <template v-if="accounts.length > 0">
        <div v-for="(acc, index) in accounts.slice(0, 2)" :key="acc.id" class="glass-card p-10 rounded-[40px] flex flex-col justify-between h-auto min-h-[14rem] cursor-pointer group relative overflow-hidden">
          <div class="absolute top-0 right-0 w-32 h-32 rounded-full blur-2xl pointer-events-none" :class="index % 2 === 0 ? 'bg-primary/5' : 'bg-orange-500/5'"></div>
          <div class="flex justify-between items-start">
            <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.3em]">{{ acc.nombre_banco }}</span>
            <div :class="['p-4 rounded-2xl border border-white/10 shadow-lg', index % 2 === 0 ? 'bg-primary/20 text-primary shadow-primary/20' : 'bg-orange-500/20 text-orange-400 shadow-orange-500/10']">
              <BuildingLibraryIcon v-if="index % 2 === 0" class="w-6 h-6" />
              <CreditCardIcon v-else class="w-6 h-6" />
            </div>
          </div>
          <div class="mt-4">
            <p class="text-white/40 text-[10px] font-bold uppercase tracking-widest">{{ acc.numero_cuenta }} - {{ acc.tipo_cuenta }}</p>
            <h3 class="text-3xl font-black tracking-tighter italic text-white mt-2">{{ acc.activa ? 'ACTIVA' : 'INACTIVA' }}</h3>
            <div :class="['mt-4 flex items-center px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest inline-flex gap-2 border', index % 2 === 0 ? 'bg-primary/15 text-primary border-primary/20' : 'bg-orange-500/15 text-orange-400 border-orange-500/20']">
              <CheckIcon class="w-3.5 h-3.5" />
              Moneda: {{ acc.moneda }}
            </div>
          </div>
        </div>
      </template>
      <template v-else>
        <!-- Skeleton / Empty State Cards -->
        <div class="glass-card p-10 rounded-[40px] flex flex-col justify-center items-center h-auto min-h-[14rem] col-span-2 text-white/30">
          <BuildingLibraryIcon class="w-10 h-10 mb-4 opacity-50" />
          <p class="font-bold uppercase tracking-widest text-xs">Sin Cuentas Registradas</p>
        </div>
      </template>
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
              class="w-full bg-white/5 border border-white/10 rounded-2xl text-xs font-bold py-3.5 px-4 text-white focus:outline-none focus:ring-2 focus:ring-primary/40 cursor-pointer"
            >
              <option value="Últimos 30 días" class="bg-slate-900">Últimos 30 días</option>
              <option value="Mes actual" class="bg-slate-900">Mes actual</option>
              <option value="Trimestre anterior" class="bg-slate-900">Trimestre anterior</option>
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
                <tr v-if="paginatedTransactions.length === 0">
                  <td colspan="5" class="px-10 py-20 text-center text-white/30 font-bold uppercase tracking-widest text-xs">
                    Ninguna transacción coincide con el filtro o la página actual.
                  </td>
                </tr>
                <tr
                  v-for="tx in paginatedTransactions"
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
                    {{ tx.type === 'in' ? '+' : '-' }}Q{{ Number(tx.amount).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
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
              Mostrando {{ (currentPage - 1) * itemsPerPage + 1 }}-{{ Math.min(currentPage * itemsPerPage, filteredTransactions.length) }} de {{ filteredTransactions.length }} registros cargados
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
    </section>

    <!-- Modals -->

    <!-- Add Account Modal -->
    <div v-if="showAccountModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-md">
      <div class="absolute inset-0 cursor-pointer" @click="showAccountModal = false"></div>
      <div class="relative w-full max-w-xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.2)]">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Registro de Cuenta</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Introducir una nueva cuenta bancaria al sistema</p>

        <form @submit.prevent="handleCreateAccount" class="space-y-6">
          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Nombre del banco</label>
            <input type="text" required v-model="newAccount.nombre_banco" placeholder="E.g. BANCO INDUSTRIAL" class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white" />
          </div>
          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Número de cuenta</label>
            <input type="text" required v-model="newAccount.numero_cuenta" placeholder="E.g. 1234567890" class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 text-white" />
          </div>
          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Tipo de cuenta</label>
              <input type="text" required v-model="newAccount.tipo_cuenta" placeholder="Monetaria, Ahorro..." class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 text-white uppercase" />
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Moneda</label>
              <select v-model="newAccount.moneda" class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold text-white focus:outline-none cursor-pointer">
                <option value="GTQ" class="bg-slate-900">GTQ</option>
                <option value="USD" class="bg-slate-900">USD</option>
              </select>
            </div>
          </div>
          <div class="space-y-2 flex items-center gap-3">
             <input type="checkbox" v-model="newAccount.activa" class="w-5 h-5 rounded border-white/10 bg-white/5 text-primary focus:ring-primary/20" />
             <label class="text-[10px] font-black text-white/30 uppercase tracking-widest">Cuenta Activa</label>
          </div>

          <div class="flex gap-4 pt-4">
            <button type="submit" class="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all">Guardar Cuenta</button>
            <button type="button" @click="showAccountModal = false" class="px-8 py-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-sm font-bold text-white/50">Cancelar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add Conciliation Modal -->
    <div v-if="showConciliationModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-md">
      <div class="absolute inset-0 cursor-pointer" @click="showConciliationModal = false"></div>
      <div class="relative w-full max-w-xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.2)] max-h-[90vh] overflow-y-auto custom-scrollbar">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Conciliación</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Marcar período y transacciones como conciliadas</p>

        <form @submit.prevent="handleCreateConciliation" class="space-y-6">
          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Cuenta Bancaria</label>
            <select v-model="newConciliation.bank_account_id" required class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold text-white focus:outline-none cursor-pointer">
              <option value="" disabled class="bg-slate-900 text-white/50">Seleccione una cuenta</option>
              <option v-for="acc in accounts" :key="acc.id" :value="acc.id" class="bg-slate-900">{{ acc.nombre_banco }} - {{ acc.numero_cuenta }}</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Período a conciliar</label>
              <input type="month" required v-model="newConciliation.periodo" class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 text-white" />
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Saldo según banco</label>
              <input type="number" step="0.01" required v-model="newConciliation.saldo_banco" placeholder="0.00" class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white placeholder:text-white/20" />
            </div>
          </div>
          
          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Partidas conciliatorias (Pendientes)</label>
            <div class="max-h-40 overflow-y-auto custom-scrollbar bg-white/5 rounded-2xl p-4 border border-white/10">
              <div v-if="transactions.length === 0" class="text-xs text-white/40 font-bold uppercase tracking-widest">No hay transacciones pendientes.</div>
              <div v-for="tx in transactions" :key="tx.id" class="flex items-center gap-3 mb-2">
                <input type="checkbox" :value="tx.id" v-model="newConciliation.partidas_conciliatorias" class="w-4 h-4 rounded border-white/10 bg-white/5 text-primary focus:ring-primary/20" />
                <span class="text-xs font-bold text-white uppercase">{{ tx.date }} - {{ tx.type === 'in' ? '+' : '-' }}Q{{ tx.amount }}</span>
              </div>
            </div>
          </div>

          <div class="flex gap-4 pt-4">
            <button type="submit" class="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all">Registrar</button>
            <button type="button" @click="showConciliationModal = false" class="px-8 py-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-sm font-bold text-white/50">Cancelar</button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
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

const API_URL = '/concretos-oriente/Backend/api/v1'

const searchTerm = ref('')
const selectedPeriod = ref('Últimos 30 días')
const selectedStatus = ref('pending')

const showAccountModal = ref(false)
const showConciliationModal = ref(false)

const accounts = ref<any[]>([])
const transactions = ref<any[]>([])

const newAccount = ref({
  nombre_banco: '',
  numero_cuenta: '',
  tipo_cuenta: '',
  moneda: 'GTQ',
  activa: true
})

const newConciliation = ref({
  bank_account_id: '',
  periodo: '',
  saldo_banco: '',
  partidas_conciliatorias: []
})

const fetchAccounts = async () => {
  try {
    const res = await axios.get(`${API_URL}/bank-accounts`)
    if (res.data.status === 'success') {
      accounts.value = res.data.data
    }
  } catch (error) {
    console.error('Error fetching accounts', error)
  }
}

const fetchTransactions = async () => {
  try {
    const res = await axios.get(`${API_URL}/bank-transactions`)
    if (res.data.status === 'success') {
      transactions.value = res.data.data
    }
  } catch (error) {
    console.error('Error fetching transactions', error)
  }
}

onMounted(() => {
  fetchAccounts()
  fetchTransactions()
})

const handleCreateAccount = async () => {
  try {
    const payload = {
      ...newAccount.value,
      activa: newAccount.value.activa ? 1 : 0
    }
    const res = await axios.post(`${API_URL}/bank-accounts`, payload)
    if (res.data.status === 'success') {
      showAccountModal.value = false
      newAccount.value = { nombre_banco: '', numero_cuenta: '', tipo_cuenta: '', moneda: 'GTQ', activa: true }
      fetchAccounts()
      Swal.fire({
        title: '¡Éxito!',
        text: 'Cuenta bancaria registrada correctamente.',
        icon: 'success',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
        customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
      })
    } else {
      Swal.fire({
        title: 'Error',
        text: 'Error al registrar la cuenta: ' + res.data.message,
        icon: 'error',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
        customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
      })
    }
  } catch (error) {
    console.error('Error:', error)
    Swal.fire({
      title: 'Error',
      text: 'Error al procesar la solicitud. Revisa tu conexión.',
      icon: 'error',
      background: '#0f172a',
      color: '#fff',
      confirmButtonColor: '#6366f1',
      customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
    })
  }
}

const handleCreateConciliation = async () => {
  try {
    const res = await axios.post(`${API_URL}/bank-reconciliations`, newConciliation.value)
    if (res.data.status === 'success') {
      showConciliationModal.value = false
      newConciliation.value = { bank_account_id: '', periodo: '', saldo_banco: '', partidas_conciliatorias: [] }
      Swal.fire({
        title: '¡Éxito!',
        text: 'Conciliación registrada correctamente',
        icon: 'success',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
        customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
      })
      // Refetch transactions to possibly hide reconciled ones if needed
      fetchTransactions()
    } else {
      Swal.fire({
        title: 'Error',
        text: 'Error al registrar conciliación: ' + res.data.message,
        icon: 'error',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
        customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
      })
    }
  } catch (error) {
    console.error('Error:', error)
    Swal.fire({
      title: 'Error',
      text: 'Error al procesar la solicitud. Revisa tu conexión.',
      icon: 'error',
      background: '#0f172a',
      color: '#fff',
      confirmButtonColor: '#6366f1',
      customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
    })
  }
}

const filteredTransactions = computed(() => {
  return transactions.value.filter(tx => {
    const matchesSearch = tx.bankDesc.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
      tx.refSystem.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
      tx.detail.toLowerCase().includes(searchTerm.value.toLowerCase())
    if (selectedStatus.value === 'all') return matchesSearch
    return tx.status === selectedStatus.value && matchesSearch
  })
})

const currentPage = ref(1)
const itemsPerPage = 10

const paginatedTransactions = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredTransactions.value.slice(start, end)
})

const totalPages = computed(() => {
  return Math.ceil(filteredTransactions.value.length / itemsPerPage) || 1
})

const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++ }
const prevPage = () => { if (currentPage.value > 1) currentPage.value-- }

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
</script>
