<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Planillas y Pagos</h2>
        <div class="flex items-center gap-4">
          <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Planilla Activa:</p>
          <select
            v-model="selectedPayrollId"
            @change="loadPayrollDetails"
            class="bg-white/5 border border-white/10 rounded-xl px-3 py-1.5 text-xs font-bold text-white uppercase focus:outline-none cursor-pointer"
          >
            <option value="" disabled class="bg-slate-900 text-white/50">Seleccione una planilla...</option>
            <option v-for="pay in payrolls" :key="pay.id" :value="pay.id" class="bg-slate-900">
              #{{ pay.id }} - {{ pay.periodo }} ({{ pay.estado }})
            </option>
          </select>
        </div>
      </div>
      <div class="flex flex-wrap gap-4">
        <button
          @click="showGenerateModal = true"
          class="flex items-center gap-2 px-6 py-4 rounded-2xl border border-white/5 bg-white/5 text-white/80 font-black text-xs uppercase tracking-widest hover:bg-white/10 hover:scale-105 active:scale-95 transition-all"
        >
          <PlusIcon class="w-4 h-4 text-primary" /> Generar Planilla
        </button>

        <button
          v-if="activePayroll && activePayroll.estado !== 'Pagado'"
          @click="showPayModal = true"
          class="glass-button-primary bg-primary border-primary border text-white px-8 py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl flex items-center gap-2.5 hover:scale-105 active:scale-95 transition-all shadow-primary/20"
        >
          <CheckCircleIcon class="w-4 h-4" /> Emitir Pagos Masivos
        </button>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-8">

      <!-- Total Payroll -->
      <div class="col-span-1 md:col-span-2 bg-gradient-to-br from-primary via-primary/80 to-indigo-950 text-white p-10 rounded-[48px] shadow-2xl flex flex-col justify-between relative overflow-hidden h-52">
        <div class="absolute -right-10 -top-10 w-44 h-44 bg-white/10 rounded-full blur-3xl"></div>
        <div class="flex justify-between items-start z-10">
          <span class="text-[10px] font-black opacity-80 uppercase tracking-[0.25em]">TOTAL NÓMINA NETO</span>
          <div class="bg-white/10 p-2.5 rounded-xl text-white">
            <CurrencyDollarIcon class="w-5 h-5" />
          </div>
        </div>
        <div class="z-10">
          <h3 class="text-4xl font-black italic tracking-tighter">
            Q{{ totalPayrollGross.toLocaleString('es-GT', { minimumFractionDigits: 2 }) }}
          </h3>
          <div class="flex items-center gap-1.5 text-white/70 font-black text-[10px] tracking-wider uppercase mt-2">
            <span v-if="activePayroll">Estado: {{ activePayroll.estado }}</span>
            <span v-else>Seleccione una planilla</span>
          </div>
        </div>
      </div>

      <!-- Bonuses -->
      <div class="glass-card p-10 rounded-[44px] border border-white/5 flex flex-col justify-between h-52">
        <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.25em]">Bonos y Extras</span>
        <div>
          <h3 class="text-3xl font-black italic tracking-tighter">Q{{ totalBonuses.toLocaleString('es-GT', { minimumFractionDigits: 2 }) }}</h3>
          <p class="text-[10px] font-bold text-white/40 tracking-wider uppercase mt-2">Total bonos y horas extras</p>
        </div>
      </div>

      <!-- Deductions -->
      <div class="glass-card p-10 rounded-[44px] border border-white/5 flex flex-col justify-between h-52">
        <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.25em]">Deducciones Totales</span>
        <div>
          <h3 class="text-3xl font-black italic tracking-tighter text-rose-400">Q{{ totalDeductions.toLocaleString('es-GT', { minimumFractionDigits: 2 }) }}</h3>
          <p class="text-[10px] font-bold text-white/40 tracking-wider uppercase mt-2">Préstamos, ISR, IGSS</p>
        </div>
      </div>

    </div>

    <!-- Employee Table -->
    <div class="glass-card rounded-[48px] overflow-hidden border border-white/5 shadow-2xl">

      <!-- Table Header Filters -->
      <div class="p-10 border-b border-white/5 bg-white/5 flex flex-col sm:flex-row justify-between items-center gap-6">
        <div>
          <h3 class="text-2xl font-black text-white italic uppercase tracking-tighter">Detalle por Empleado</h3>
          <p class="text-[10px] font-bold text-white/30 uppercase tracking-widest mt-1">Nómina individual de la planilla activa</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <div class="relative">
            <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/40" />
            <input
              type="text"
              v-model="searchTerm"
              placeholder="Buscar empleado..."
              class="glass-input pl-11 pr-5 py-3 rounded-xl text-xs uppercase font-extrabold tracking-wider w-56 text-white placeholder:text-white/20"
            />
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="text-[10px] font-extrabold text-white/30 uppercase tracking-widest border-b border-white/5">
              <th class="px-10 py-6">Empleado</th>
              <th class="px-10 py-6 text-center">Horas</th>
              <th class="px-10 py-6 text-right">Sueldo Calculado</th>
              <th class="px-10 py-6 text-right">Extras y Bonos</th>
              <th class="px-10 py-6 text-right text-rose-400">Deducciones</th>
              <th class="px-10 py-6 text-right">Total Neto</th>
              <th class="px-10 py-6"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-if="filteredDetails.length === 0">
              <td colspan="7" class="px-10 py-16 text-center text-white/30 font-bold uppercase tracking-widest text-xs">
                No hay empleados en esta planilla o no se ha seleccionado ninguna.
              </td>
            </tr>
            <tr
              v-for="emp in filteredDetails"
              :key="emp.id"
              class="hover:bg-white/5 transition-all duration-300"
            >
              <!-- Name -->
              <td class="px-10 py-6">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 rounded-full bg-primary/20 text-primary font-black italic border border-white/10 shadow flex items-center justify-center text-sm">
                    {{ emp.nombres.charAt(0) }}{{ emp.apellidos.charAt(0) }}
                  </div>
                  <div>
                    <h5 class="font-extrabold text-base text-white tracking-tight uppercase italic">{{ emp.nombres }} {{ emp.apellidos }}</h5>
                    <p class="text-[10px] font-bold text-white/35 tracking-wider uppercase mt-1">
                      {{ emp.puesto }} • <span class="text-primary">{{ emp.tipo_planilla }}</span>
                    </p>
                  </div>
                </div>
              </td>

              <!-- Hours -->
              <td class="px-10 py-6 text-center">
                <p class="text-xs text-white/60 font-extrabold">{{ emp.horas_trabajadas }}h</p>
                <p v-if="Number(emp.horas_extras) > 0" class="text-[9px] text-emerald-400 font-bold uppercase mt-1">+{{ emp.horas_extras }}h extras</p>
              </td>

              <!-- Base Salary -->
              <td class="px-10 py-6 text-right font-black italic text-sm text-white/80">
                Q{{ (Number(emp.salario_base_aplicado) / 160 * Number(emp.horas_trabajadas)).toLocaleString('es-GT', {minimumFractionDigits: 2}) }}
                <p class="text-[9px] text-white/30 uppercase mt-1 font-normal tracking-wider">Base ref: Q{{ Number(emp.salario_base_aplicado).toLocaleString('es-GT') }}</p>
              </td>

              <!-- Bonuses -->
              <td class="px-10 py-6 text-right font-black italic text-sm text-emerald-400">
                +Q{{ (Number(emp.bonificaciones) + Number(emp.monto_horas_extras)).toLocaleString('es-GT', {minimumFractionDigits: 2}) }}
              </td>

              <!-- Deductions -->
              <td class="px-10 py-6 text-right font-black italic text-sm text-rose-400">
                -Q{{ Number(emp.deducciones).toLocaleString('es-GT', {minimumFractionDigits: 2}) }}
              </td>

              <!-- Net Total -->
              <td class="px-10 py-6 text-right font-black italic text-base text-white">
                Q{{ Number(emp.total_neto).toLocaleString('es-GT', { minimumFractionDigits: 2 }) }}
              </td>

              <!-- Actions -->
              <td class="px-10 py-6 text-right">
                <button
                  v-if="activePayroll && activePayroll.estado !== 'Pagado'"
                  @click="openEditModal(emp)"
                  class="p-2 text-white/30 hover:text-white transition-colors"
                >
                  <PencilIcon class="w-4 h-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination footer -->
      <div class="p-8 border-t border-white/5 bg-black/10 flex justify-between items-center text-[10px] font-black text-white/20 uppercase tracking-widest">
        <span>Mostrando {{ filteredDetails.length }} de {{ payrollDetails.length }} empleados</span>
      </div>
    </div>

    <!-- Generate Payroll Modal -->
    <div v-if="showGenerateModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
      <div class="absolute inset-0 cursor-pointer" @click="showGenerateModal = false"></div>

      <div class="relative w-full max-w-xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Generar Planilla</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Inicia un nuevo período de pago masivo</p>

        <form @submit.prevent="generatePayroll" class="space-y-6">

          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Período de Pago</label>
              <select required v-model="newPayroll.periodo" class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold uppercase text-white focus:outline-none">
                <option value="" disabled class="bg-slate-900 text-white/50">Seleccionar...</option>
                <option value="Primera Quincena" class="bg-slate-950 text-white">Primera Quincena</option>
                <option value="Segunda Quincena" class="bg-slate-950 text-white">Segunda Quincena</option>
                <option value="Mes Completo" class="bg-slate-950 text-white">Mes Completo</option>
                <option value="Semanal" class="bg-slate-950 text-white">Semanal</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Fecha de Corte</label>
              <input
                type="date"
                required
                v-model="newPayroll.fecha_corte"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
              />
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Empleados Incluidos</label>
            <div class="bg-white/5 border border-white/10 rounded-2xl p-4 max-h-48 overflow-y-auto custom-scrollbar space-y-2">
              <div v-if="activePersonnel.length === 0" class="text-xs text-white/40">Cargando personal...</div>
              <label v-for="emp in activePersonnel" :key="emp.id" class="flex items-center gap-3 cursor-pointer p-2 hover:bg-white/5 rounded-xl transition-colors">
                <input type="checkbox" v-model="newPayroll.empleados_ids" :value="emp.id" class="w-4 h-4 rounded text-primary focus:ring-primary border-white/20 bg-slate-900" />
                <div>
                  <span class="text-xs font-bold text-white uppercase">{{ emp.nombres }} {{ emp.apellidos }}</span>
                  <p class="text-[9px] text-white/40 uppercase tracking-widest mt-0.5">{{ emp.puesto }}</p>
                </div>
              </label>
            </div>
            <button type="button" @click="selectAllEmployees" class="text-[10px] text-primary font-black uppercase mt-2 ml-1 hover:text-white">Seleccionar Todos</button>
          </div>

          <div class="flex gap-4 pt-4">
            <button
              type="submit"
              class="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
            >
              Confirmar e Iniciar
            </button>
            <button
              type="button"
              @click="showGenerateModal = false"
              class="px-8 py-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-black text-white/50"
            >
              Cancelar
            </button>
          </div>

        </form>
      </div>
    </div>

    <!-- Edit Employee Detail Modal -->
    <div v-if="showEditModal && editingDetail" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
      <div class="absolute inset-0 cursor-pointer" @click="showEditModal = false"></div>

      <div class="relative w-full max-w-xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Detalle de Planilla</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Ajuste individual para: {{ editingDetail.nombres }} {{ editingDetail.apellidos }}</p>

        <form @submit.prevent="updatePayrollDetail" class="space-y-6">

          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Horas Trabajadas</label>
              <input
                type="number"
                required
                v-model="editForm.horas_trabajadas"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white"
              />
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Horas Extras</label>
              <input
                type="number"
                required
                v-model="editForm.horas_extras"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Bonificaciones (Q)</label>
              <input
                type="number"
                step="0.01"
                v-model="editForm.bonificaciones"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white"
              />
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Deducciones (Q)</label>
              <input
                type="number"
                step="0.01"
                v-model="editForm.deducciones"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white"
              />
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Observaciones / Descuentos</label>
            <input
              type="text"
              v-model="editForm.observaciones"
              placeholder="Motivo del bono o deducción..."
              class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white"
            />
          </div>

          <!-- Action CTA -->
          <div class="flex gap-4 pt-4">
            <button
              type="submit"
              class="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
            >
              Guardar Cambios
            </button>
            <button
              type="button"
              @click="showEditModal = false"
              class="px-8 py-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-black text-white/50"
            >
              Cancelar
            </button>
          </div>

        </form>
      </div>
    </div>

    <!-- Mass Pay Confirmation Modal -->
    <div v-if="showPayModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
      <div class="absolute inset-0 cursor-pointer" @click="showPayModal = false"></div>

      <div class="relative w-full max-w-md glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Confirmar Pagos</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">
          Estás por emitir los pagos de la planilla activa
        </p>

        <div class="bg-white/5 border border-white/10 rounded-3xl p-6 mb-8 space-y-4 font-bold text-xs uppercase tracking-wide">
          <div class="pt-4 flex justify-between items-baseline">
            <span class="text-white/80 font-black">TOTAL A TRANSFERIR:</span>
            <span class="text-xl font-black italic text-primary">Q{{ totalPayrollGross.toLocaleString('es-GT', { minimumFractionDigits: 2 }) }}</span>
          </div>
        </div>

        <div class="flex gap-4">
          <button
            @click="executeMassPayments"
            class="flex-grow glass-button-primary bg-primary border border-primary text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
          >
            Procesar Ahora
          </button>
          <button
            type="button"
            @click="showPayModal = false"
            class="px-6 py-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-extrabold text-white/50"
          >
            Cancelar
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import {
  CheckCircleIcon,
  CurrencyDollarIcon,
  MagnifyingGlassIcon,
  PencilIcon,
  PlusIcon
} from '@heroicons/vue/24/outline';

const API_URL = '/concretos-oriente/Backend/api/v1';

const payrolls = ref<any[]>([]);
const activePersonnel = ref<any[]>([]);
const payrollDetails = ref<any[]>([]);
const selectedPayrollId = ref('');

const searchTerm = ref('');
const showGenerateModal = ref(false);
const showEditModal = ref(false);
const showPayModal = ref(false);

const newPayroll = ref({
  periodo: '',
  fecha_corte: '',
  empleados_ids: [] as string[]
});

const editingDetail = ref<any>(null);
const editForm = ref({
  id: '',
  horas_trabajadas: 0,
  horas_extras: 0,
  bonificaciones: 0,
  deducciones: 0,
  observaciones: ''
});

const activePayroll = computed(() => {
  return payrolls.value.find(p => p.id == selectedPayrollId.value);
});

const filteredDetails = computed(() => {
  return payrollDetails.value.filter(emp => {
    const term = searchTerm.value.toLowerCase();
    const fullName = `${emp.nombres} ${emp.apellidos}`.toLowerCase();
    return !term || fullName.includes(term) || (emp.puesto || '').toLowerCase().includes(term);
  });
});

const totalBonuses = computed(() => payrollDetails.value.reduce((s, e) => s + Number(e.bonificaciones) + Number(e.monto_horas_extras), 0));
const totalDeductions = computed(() => payrollDetails.value.reduce((s, e) => s + Number(e.deducciones), 0));
const totalPayrollGross = computed(() => payrollDetails.value.reduce((s, e) => s + Number(e.total_neto), 0));

onMounted(() => {
  fetchPayrolls();
  fetchActivePersonnel();
});

const fetchPayrolls = async () => {
  try {
    const res = await axios.get(`${API_URL}/payrolls`);
    if (res.data.status === 'success') {
      payrolls.value = res.data.data;
      if (payrolls.value.length > 0 && !selectedPayrollId.value) {
        selectedPayrollId.value = payrolls.value[0].id;
        loadPayrollDetails();
      }
    }
  } catch (error) {
    console.error(error);
  }
};

const fetchActivePersonnel = async () => {
  try {
    const res = await axios.get(`${API_URL}/personnel/active`);
    if (res.data.status === 'success') {
      activePersonnel.value = res.data.data;
    }
  } catch (error) {
    console.error(error);
  }
};

const loadPayrollDetails = async () => {
  if (!selectedPayrollId.value) return;
  try {
    const res = await axios.get(`${API_URL}/payrolls/details?payroll_id=${selectedPayrollId.value}`);
    if (res.data.status === 'success') {
      payrollDetails.value = res.data.data;
    }
  } catch (error) {
    console.error(error);
  }
};

const selectAllEmployees = () => {
  newPayroll.value.empleados_ids = activePersonnel.value.map(emp => emp.id);
};

const generatePayroll = async () => {
  try {
    const res = await axios.post(`${API_URL}/payrolls`, newPayroll.value);
    if (res.data.status === 'success') {
      showGenerateModal.value = false;
      newPayroll.value = { periodo: '', fecha_corte: '', empleados_ids: [] };
      Swal.fire({
        title: '¡Generada!',
        text: 'La planilla fue generada con los empleados seleccionados.',
        icon: 'success',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
        customClass: { popup: 'border border-white/10 rounded-3xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
      });
      selectedPayrollId.value = '';
      await fetchPayrolls(); // This will auto-select the latest and load details
    }
  } catch (error: any) {
    Swal.fire('Error', 'No se pudo generar la planilla.', 'error');
  }
};

const openEditModal = (emp: any) => {
  editingDetail.value = emp;
  editForm.value = {
    id: emp.id,
    horas_trabajadas: Number(emp.horas_trabajadas),
    horas_extras: Number(emp.horas_extras),
    bonificaciones: Number(emp.bonificaciones),
    deducciones: Number(emp.deducciones),
    observaciones: emp.observaciones || ''
  };
  showEditModal.value = true;
};

const updatePayrollDetail = async () => {
  try {
    const res = await axios.post(`${API_URL}/payroll-details`, editForm.value);
    if (res.data.status === 'success') {
      showEditModal.value = false;
      loadPayrollDetails(); // Reload table
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Detalle actualizado',
        showConfirmButton: false,
        timer: 3000,
        background: '#0f172a',
        color: '#fff'
      });
    }
  } catch (error) {
    Swal.fire('Error', 'No se pudo actualizar.', 'error');
  }
};

const executeMassPayments = async () => {
  if (!selectedPayrollId.value) return;
  try {
    const res = await axios.post(`${API_URL}/payrolls/pay`, { payroll_id: selectedPayrollId.value });
    if (res.data.status === 'success') {
      showPayModal.value = false;
      fetchPayrolls(); // Reload to get updated status
      Swal.fire({
        title: '¡Pagos Emitidos!',
        text: 'La planilla fue marcada como pagada.',
        icon: 'success',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
        customClass: { popup: 'border border-white/10 rounded-3xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
      });
    }
  } catch (error) {
    Swal.fire('Error', 'No se pudo procesar el pago masivo.', 'error');
  }
};
</script>
