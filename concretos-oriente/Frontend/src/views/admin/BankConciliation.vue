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
        <div v-for="(acc, index) in accounts.slice(0, 2)" :key="acc.id" class="glass-card p-10 rounded-[40px] flex flex-col justify-between h-auto min-h-[14rem] cursor-pointer group relative overflow-hidden" data-aos="zoom-in-up" data-aos-duration="1000">
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
        <div class="glass-card p-10 rounded-[40px] flex flex-col justify-center items-center h-auto min-h-[14rem] col-span-2 text-white/30" data-aos="zoom-in-up" data-aos-duration="1000">
          <BuildingLibraryIcon class="w-10 h-10 mb-4 opacity-50" />
          <p class="font-bold uppercase tracking-widest text-xs">Sin Cuentas Registradas</p>
        </div>
      </template>
    </section>

    <!-- Modals -->

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

const accounts = ref<any[]>([])

const newAccount = ref({
  nombre_banco: '',
  numero_cuenta: '',
  tipo_cuenta: '',
  moneda: 'GTQ',
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


</script>
