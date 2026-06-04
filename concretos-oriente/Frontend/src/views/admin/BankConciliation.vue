<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">

    <!-- Search & Intro -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Bancos</h2>
        <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Gestión de cuentas bancarias</p>
      </div>
      <div class="flex items-center gap-4 relative z-20 flex-wrap">
        <button @click="showAccountModal = true" class="px-6 py-4 rounded-2xl border border-white/5 bg-white/5 text-white/80 font-black text-xs uppercase tracking-widest hover:bg-white/10 hover:scale-105 transition-all">Nueva Cuenta</button>
      </div>
    </div>

    <!-- Summary Row -->
    <section class="grid grid-cols-1 md:grid-cols-2 gap-8">
      
      <!-- Dynamic Accounts Cards -->
      <template v-if="accounts.length > 0">
        <div v-for="(acc, index) in accounts" :key="acc.id" @click="openHistory(acc)" class="glass-card p-10 rounded-[40px] flex flex-col justify-between h-auto min-h-[14rem] cursor-pointer group relative overflow-hidden" data-aos="zoom-in-up" data-aos-duration="1000">
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
            <h3 class="text-3xl font-black tracking-tighter italic text-white mt-2">Q{{ Number(acc.saldo_actual).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</h3>
            <div class="text-[10px] font-bold text-white/30 uppercase tracking-widest mt-1">Saldo Actual</div>
            <div :class="['mt-4 flex items-center px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest inline-flex gap-2 border', index % 2 === 0 ? 'bg-primary/15 text-primary border-primary/20' : 'bg-orange-500/15 text-orange-400 border-orange-500/20']">
              <CheckIcon class="w-3.5 h-3.5" />
              Moneda: {{ acc.moneda }}
            </div>
          </div>
        </div>
      </template>
      <template v-else>
        <!-- Skeleton / Empty State Cards -->
        <div class="glass-card p-10 rounded-[40px] flex flex-col justify-center items-center h-auto min-h-[14rem] col-span-2 text-white/30" data-aos="zoom-in-up" data-aos-duration="1000">
          <BuildingLibraryIcon class="w-10 h-10 mb-4 opacity-50" />
          <p class="font-bold uppercase tracking-widest text-xs">Sin Cuentas Registradas</p>
        </div>
      </template>
    </section>

    <!-- Modals -->
    
    <!-- Add Account Modal -->
    <div v-if="showAccountModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-md">
      <div class="absolute inset-0 cursor-pointer" @click="showAccountModal = false"></div>
      <div class="relative w-full max-w-xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.2)]" data-aos="zoom-in-up" data-aos-duration="1000">
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
          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Saldo Inicial</label>
            <input type="number" step="0.01" required v-model="newAccount.saldo_inicial" placeholder="0.00" class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 text-white" />
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

    <!-- History Modal -->
    <div v-if="showHistoryModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-md">
      <div class="absolute inset-0 cursor-pointer" @click="showHistoryModal = false"></div>
      <div class="relative w-full max-w-4xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.2)] max-h-[90vh] overflow-y-auto custom-scrollbar" data-aos="zoom-in-up" data-aos-duration="1000">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Historial Bancario</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Transacciones de la cuenta: {{ selectedAccount?.nombre_banco }} - {{ selectedAccount?.numero_cuenta }}</p>

        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="text-[11px] font-bold text-white/20 uppercase tracking-[0.3em] border-b border-white/5">
                <th class="px-6 py-4">Fecha</th>
                <th class="px-6 py-4">Tipo</th>
                <th class="px-6 py-4">Descripción</th>
                <th class="px-6 py-4 text-right">Monto</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-if="historyTransactions.length === 0">
                <td colspan="4" class="px-6 py-10 text-center text-white/40">No hay transacciones registradas.</td>
              </tr>
              <tr v-for="tx in historyTransactions" :key="tx.id + tx.type" class="hover:bg-white/5 transition-all">
                <td class="px-6 py-4 text-xs font-bold text-white/60">{{ tx.date }}</td>
                <td class="px-6 py-4 text-xs font-black uppercase" :class="tx.type === 'in' ? 'text-primary' : 'text-rose-400'">{{ tx.bankDesc }}</td>
                <td class="px-6 py-4 text-xs text-white/80">{{ tx.detail }}</td>
                <td class="px-6 py-4 text-right font-black italic text-lg" :class="tx.type === 'in' ? 'text-primary' : 'text-rose-400'">
                  {{ tx.type === 'in' ? '+' : '-' }}Q{{ Number(tx.amount).toLocaleString('en-US', {minimumFractionDigits: 2}) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '../../services/api'
import Swal from 'sweetalert2'
import {
  BuildingLibraryIcon,
  CreditCardIcon,
  CheckIcon
} from '@heroicons/vue/24/outline'



const showAccountModal = ref(false)
const showHistoryModal = ref(false)
const selectedAccount = ref<any>(null)
const historyTransactions = ref<any[]>([])

const accounts = ref<any[]>([])

const newAccount = ref({
  nombre_banco: '',
  numero_cuenta: '',
  tipo_cuenta: '',
  moneda: 'GTQ',
  saldo_inicial: 0,
  activa: true
})

const fetchAccounts = async () => {
  try {
    const res = await api.get(`/bank-accounts`)
    if (res.data.status === 'success') {
      accounts.value = res.data.data
    }
  } catch (error) {
    console.error('Error fetching accounts', error)
  }
}

onMounted(() => {
  fetchAccounts()
})

const handleCreateAccount = async () => {
  try {
    const payload = {
      ...newAccount.value,
      activa: newAccount.value.activa ? 1 : 0
    }
    const res = await api.post(`/bank-accounts`, payload)
    if (res.data.status === 'success') {
      showAccountModal.value = false
      newAccount.value = { nombre_banco: '', numero_cuenta: '', tipo_cuenta: '', moneda: 'GTQ', saldo_inicial: 0, activa: true }
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


const openHistory = async (acc: any) => {
  selectedAccount.value = acc;
  historyTransactions.value = [];
  showHistoryModal.value = true;
  
  try {
    const res = await api.get(`/bank-accounts/${acc.id}/history`)
    if (res.data.status === 'success') {
      historyTransactions.value = res.data.data
    }
  } catch (error) {
    console.error('Error fetching history', error)
  }
}
</script>
