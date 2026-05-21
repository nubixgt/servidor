<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">

    <!-- Toast Notification -->
    <div
      v-if="notification"
      class="fixed top-24 right-10 z-50 bg-primary/20 border border-primary text-white backdrop-blur-xl px-6 py-4 rounded-2xl flex items-center gap-3 shadow-lg"
    >
      <CheckCircleIcon class="w-5 h-5 text-primary" />
      <span class="text-xs font-black uppercase tracking-wider">{{ notification }}</span>
    </div>

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Planillas y Pagos</h2>
        <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Periodo Actual: 01 Oct — 15 Oct, 2026</p>
      </div>
      <div class="flex flex-wrap gap-4">
        <button
          @click="showToast('Planilla exportada a Excel')"
          class="flex items-center gap-2 px-6 py-4 rounded-2xl border border-white/5 bg-white/5 text-white/80 font-black text-xs uppercase tracking-widest hover:bg-white/10 hover:scale-105 active:scale-95 transition-all"
        >
          <TableCellsIcon class="w-4 h-4 text-primary" /> Exportar Reporte
        </button>

        <button
          @click="showAddEmployeeModal = true"
          class="flex items-center gap-2 px-6 py-4 rounded-2xl border border-white/5 bg-white/5 text-white/80 font-black text-xs uppercase tracking-widest hover:bg-white/10 hover:scale-105 active:scale-95 transition-all"
        >
          <PlusIcon class="w-4 h-4 text-primary" /> Agregar Empleado
        </button>

        <button
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
        <div class="flex justify-between items-start">
          <span class="text-[10px] font-black opacity-80 uppercase tracking-[0.25em]">TOTAL NÓMINA NETO</span>
          <div class="bg-white/10 p-2.5 rounded-xl text-white">
            <CurrencyDollarIcon class="w-5 h-5" />
          </div>
        </div>
        <div>
          <h3 class="text-4xl font-black italic tracking-tighter">
            Q{{ totalPayrollGross.toLocaleString('es-GT', { minimumFractionDigits: 2 }) }}
          </h3>
          <div class="flex items-center gap-1.5 text-white/70 font-black text-[10px] tracking-wider uppercase mt-2">
            <ArrowTrendingUpIcon class="w-3.5 h-3.5" />
            <span>+4.2% frente a quincena anterior</span>
          </div>
        </div>
      </div>

      <!-- Bonuses -->
      <div class="glass-card p-10 rounded-[44px] border border-white/5 flex flex-col justify-between h-52">
        <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.25em]">Bonos y Extras</span>
        <div>
          <h3 class="text-3xl font-black italic tracking-tighter">Q{{ totalBonuses.toLocaleString('es-GT', { minimumFractionDigits: 2 }) }}</h3>
          <p class="text-[10px] font-bold text-white/40 tracking-wider uppercase mt-2">Total bonos indexados</p>
        </div>
      </div>

      <!-- Deductions -->
      <div class="glass-card p-10 rounded-[44px] border border-white/5 flex flex-col justify-between h-52">
        <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.25em]">Deducciones Totales</span>
        <div>
          <h3 class="text-3xl font-black italic tracking-tighter text-rose-400">Q{{ totalDeductions.toLocaleString('es-GT', { minimumFractionDigits: 2 }) }}</h3>
          <p class="text-[10px] font-bold text-white/40 tracking-wider uppercase mt-2">Seguros, impuestos, préstamos</p>
        </div>
      </div>

    </div>

    <!-- Employee Table -->
    <div class="glass-card rounded-[48px] overflow-hidden border border-white/5 shadow-2xl">

      <!-- Table Header Filters -->
      <div class="p-10 border-b border-white/5 bg-white/5 flex flex-col sm:flex-row justify-between items-center gap-6">
        <div>
          <h3 class="text-2xl font-black text-white italic uppercase tracking-tighter">Listado de Empleados</h3>
          <p class="text-[10px] font-bold text-white/30 uppercase tracking-widest mt-1">Nómina individual habilitada para este periodo</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <div class="relative">
            <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/40" />
            <input
              type="text"
              v-model="searchTerm"
              placeholder="Buscar por rol o nombre..."
              class="glass-input pl-11 pr-5 py-3 rounded-xl text-xs uppercase font-extrabold tracking-wider w-56 text-white placeholder:text-white/20"
            />
          </div>

          <select
            v-model="deptFilter"
            class="bg-white/5 border border-white/10 p-3 text-xs rounded-xl text-white font-bold cursor-pointer uppercase focus:outline-none"
          >
            <option class="bg-slate-950 text-white">Todos los departamentos</option>
            <option class="bg-slate-950 text-white">Obra Civil</option>
            <option class="bg-slate-950 text-white">Maquinaria y Logística</option>
            <option class="bg-slate-950 text-white">Arquitectura &amp; Diseño</option>
            <option class="bg-slate-950 text-white">Administración</option>
          </select>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="text-[10px] font-extrabold text-white/30 uppercase tracking-widest border-b border-white/5">
              <th class="px-10 py-6">Empleado</th>
              <th class="px-10 py-6">Horas</th>
              <th class="px-10 py-6 text-right">Sueldo Base</th>
              <th class="px-10 py-6 text-right">Bonos</th>
              <th class="px-10 py-6 text-right text-rose-400">Deducciones</th>
              <th class="px-10 py-6 text-right">Total Neto</th>
              <th class="px-10 py-6 text-center">Estado</th>
              <th class="px-10 py-6"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-if="filteredEmployees.length === 0">
              <td colspan="8" class="px-10 py-16 text-center text-white/30 font-bold uppercase tracking-widest text-xs">
                No se encontraron empleados registrados bajo este criterio.
              </td>
            </tr>
            <tr
              v-for="emp in filteredEmployees"
              :key="emp.id"
              class="hover:bg-white/5 transition-all duration-300"
            >
              <!-- Name -->
              <td class="px-10 py-6">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 rounded-full bg-primary/20 text-primary font-black italic border border-white/10 shadow flex items-center justify-center text-sm">
                    {{ emp.initials }}
                  </div>
                  <div>
                    <h5 class="font-extrabold text-base text-white tracking-tight uppercase italic">{{ emp.name }}</h5>
                    <p class="text-[10px] font-bold text-white/35 tracking-wider uppercase mt-1">
                      {{ emp.role }} • <span class="text-primary">{{ emp.department }}</span>
                    </p>
                  </div>
                </div>
              </td>

              <!-- Hours -->
              <td class="px-10 py-6 text-xs text-white/60 font-extrabold">{{ emp.hours }}h</td>

              <!-- Base Salary -->
              <td class="px-10 py-6 text-right font-black italic text-sm text-white/80">
                Q{{ emp.baseSalary.toLocaleString('es-GT') }}
              </td>

              <!-- Bonuses -->
              <td class="px-10 py-6 text-right font-black italic text-sm text-emerald-400">
                +Q{{ emp.bonuses.toLocaleString('es-GT') }}
              </td>

              <!-- Deductions -->
              <td class="px-10 py-6 text-right font-black italic text-sm text-rose-400">
                -Q{{ emp.deductions.toLocaleString('es-GT') }}
              </td>

              <!-- Net Total -->
              <td class="px-10 py-6 text-right font-black italic text-base text-white">
                Q{{ (emp.baseSalary + emp.bonuses - emp.deductions).toLocaleString('es-GT', { minimumFractionDigits: 2 }) }}
              </td>

              <!-- Status -->
              <td class="px-10 py-6 text-center">
                <span :class="[
                  'px-3.5 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border',
                  emp.status === 'Procesado'
                    ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
                    : 'bg-amber-500/10 text-amber-400 border-amber-500/20'
                ]">
                  {{ emp.status }}
                </span>
              </td>

              <!-- Actions -->
              <td class="px-10 py-6 text-right">
                <button
                  @click="showToast(`Detalle de planilla para: ${emp.name}`)"
                  class="p-2 text-white/30 hover:text-white transition-colors"
                >
                  <EyeIcon class="w-4 h-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination footer -->
      <div class="p-8 border-t border-white/5 bg-black/10 flex justify-between items-center text-[10px] font-black text-white/20 uppercase tracking-widest">
        <span>Mostrando {{ filteredEmployees.length }} de {{ employees.length }} empleados</span>
        <div class="flex gap-2">
          <button class="w-10 h-10 rounded-xl border border-white/5 bg-white/5 flex items-center justify-center">
            <ChevronLeftIcon class="w-4 h-4" />
          </button>
          <button class="w-10 h-10 rounded-xl bg-primary text-white font-black italic text-xs">1</button>
          <button class="w-10 h-10 rounded-xl border border-white/5 bg-white/5 flex items-center justify-center">
            <ChevronRightIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Dept Distribution + History -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">

      <!-- Department Progress -->
      <div class="glass-card p-10 rounded-[44px] border border-white/5 space-y-6">
        <h4 class="text-sm font-black uppercase tracking-widest text-white/60">Distribución por Departamento</h4>
        <div class="space-y-6">
          <div class="space-y-2">
            <div class="flex justify-between font-bold text-xs uppercase tracking-wide">
              <span class="text-white/50">Obra Civil</span>
              <span class="font-extrabold text-white">Q82,400.00</span>
            </div>
            <div class="w-full bg-white/5 h-2.5 rounded-full overflow-hidden">
              <div class="bg-primary h-full rounded-full" style="width: 65%"></div>
            </div>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between font-bold text-xs uppercase tracking-wide">
              <span class="text-white/50">Maquinaria y Logística</span>
              <span class="font-extrabold text-white">Q34,120.00</span>
            </div>
            <div class="w-full bg-white/5 h-2.5 rounded-full overflow-hidden">
              <div class="bg-primary/60 h-full rounded-full" style="width: 35%"></div>
            </div>
          </div>
          <div class="space-y-2">
            <div class="flex justify-between font-bold text-xs uppercase tracking-wide">
              <span class="text-white/50">Arquitectura &amp; Diseño</span>
              <span class="font-extrabold text-white">Q18,060.00</span>
            </div>
            <div class="w-full bg-white/5 h-2.5 rounded-full overflow-hidden">
              <div class="bg-primary/30 h-full rounded-full" style="width: 20%"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment History -->
      <div class="glass-card p-10 rounded-[44px] border border-white/5 flex flex-col justify-between">
        <div>
          <div class="flex justify-between items-center mb-6">
            <h4 class="text-sm font-black uppercase tracking-widest text-white/60">Historial de Pagos</h4>
            <ClockIcon class="w-4 h-4 text-white/40" />
          </div>
          <div class="space-y-6">
            <div v-for="item in paymentHistory" :key="item.id" class="flex items-center gap-4">
              <div class="w-10 h-10 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/20 flex items-center justify-center">
                <CheckCircleIcon class="w-5 h-5" />
              </div>
              <div class="flex-grow">
                <p class="font-extrabold text-sm uppercase tracking-tight text-white">{{ item.title }}</p>
                <p class="text-[10px] text-white/30 font-bold tracking-wider mt-1 uppercase">{{ item.date }} • {{ item.count }} empleados</p>
              </div>
              <div class="text-right">
                <p class="font-black italic text-sm text-white">Q{{ item.amount.toLocaleString('es-GT') }}</p>
              </div>
            </div>
          </div>
        </div>
        <button
          @click="showToast('Cargando historial completo de planillas y egresos de ConstructPro.')"
          class="w-full mt-8 py-4 border border-white/10 rounded-2xl text-[10px] font-black uppercase tracking-widest text-primary hover:bg-primary/10 transition-colors"
        >
          Ver Historial Completo
        </button>
      </div>

    </div>

    <!-- Mass Pay Confirmation Modal -->
    <div v-if="showPayModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
      <div class="absolute inset-0 cursor-pointer" @click="showPayModal = false"></div>

      <div class="relative w-full max-w-md glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Confirmar Pagos</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">
          Estás por emitir pagos a {{ employees.length }} empleados correspondientes al periodo actual
        </p>

        <div class="bg-white/5 border border-white/10 rounded-3xl p-6 mb-8 space-y-4 font-bold text-xs uppercase tracking-wide">
          <div class="flex justify-between">
            <span class="text-white/40">Subtotal Nómina Básica</span>
            <span class="text-white font-black">Q{{ totalBase.toLocaleString('es-GT') }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-white/40">Bonificaciones</span>
            <span class="text-emerald-400 font-black">+Q{{ totalBonuses.toLocaleString('es-GT') }}</span>
          </div>
          <div class="flex justify-between text-rose-400">
            <span>Deducciones Aplicadas</span>
            <span class="font-black">-Q{{ totalDeductions.toLocaleString('es-GT') }}</span>
          </div>
          <div class="pt-4 border-t border-white/10 flex justify-between items-baseline">
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

    <!-- Add Employee Modal -->
    <div v-if="showAddEmployeeModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
      <div class="absolute inset-0 cursor-pointer" @click="showAddEmployeeModal = false"></div>

      <div class="relative w-full max-w-xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Agregar Empleado</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Ingresar nuevo colaborador a la planilla de ConstructPro</p>

        <form @submit.prevent="handleCreateEmployee" class="space-y-6">

          <!-- Full name -->
          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Nombre Completo</label>
            <input
              type="text"
              required
              v-model="newName"
              placeholder="E.g. Juan Carlos López"
              class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
            />
          </div>

          <!-- Role and Dept -->
          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Puesto / Rol administrativo</label>
              <input
                type="text"
                required
                v-model="newRole"
                placeholder="E.g. Supervisor de Obra"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
              />
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Departamento</label>
              <select
                v-model="newDept"
                class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold uppercase text-white focus:outline-none"
              >
                <option class="bg-slate-950 text-white">Obra Civil</option>
                <option class="bg-slate-950 text-white">Maquinaria y Logística</option>
                <option class="bg-slate-950 text-white">Arquitectura &amp; Diseño</option>
                <option class="bg-slate-950 text-white">Administración</option>
              </select>
            </div>
          </div>

          <!-- Hours & Base Salary -->
          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Horas laboradas</label>
              <input
                type="number"
                required
                v-model="newHours"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white placeholder:text-white/20"
              />
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Sueldo Base (Q)</label>
              <input
                type="number"
                required
                step="0.01"
                v-model="newBase"
                placeholder="0.00"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white placeholder:text-white/20"
              />
            </div>
          </div>

          <!-- Bonuses and Deductions -->
          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Bonos extraordinarios</label>
              <input
                type="number"
                step="0.01"
                v-model="newBonuses"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white"
              />
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Deducciones (Anticipos, ISR, IHSS)</label>
              <input
                type="number"
                step="0.01"
                v-model="newDeductions"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white"
              />
            </div>
          </div>

          <!-- Action CTA -->
          <div class="flex gap-4 pt-4">
            <button
              type="submit"
              class="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
            >
              Confirmar Empleado
            </button>
            <button
              type="button"
              @click="showAddEmployeeModal = false"
              class="px-8 py-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-black text-white/50"
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
  CheckCircleIcon,
  ArrowTrendingUpIcon,
  CurrencyDollarIcon,
  MagnifyingGlassIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  EyeIcon,
  ClockIcon,
  PlusIcon,
  TableCellsIcon
} from '@heroicons/vue/24/outline';

interface EmployeePayroll {
  id: string;
  name: string;
  role: string;
  hours: number;
  baseSalary: number;
  bonuses: number;
  deductions: number;
  department: 'Obra Civil' | 'Maquinaria y Logística' | 'Arquitectura & Diseño' | 'Administración';
  status: 'Procesado' | 'Pendiente';
  initials: string;
}

const searchTerm = ref('');
const deptFilter = ref('Todos los departamentos');
const showPayModal = ref(false);
const showAddEmployeeModal = ref(false);
const notification = ref('');

// New employee form fields
const newName = ref('');
const newRole = ref('');
const newDept = ref('Obra Civil');
const newHours = ref('160');
const newBase = ref('');
const newBonuses = ref('0');
const newDeductions = ref('0');

const employees = ref<EmployeePayroll[]>([
  {
    id: '1',
    name: 'Roberto Mendoza',
    role: 'Ingeniero de Obra',
    hours: 160,
    baseSalary: 4200.00,
    bonuses: 350.00,
    deductions: 210.00,
    department: 'Obra Civil',
    status: 'Procesado',
    initials: 'RM'
  },
  {
    id: '2',
    name: 'Lucia Castillo',
    role: 'Arquitecta Senior',
    hours: 152,
    baseSalary: 3800.00,
    bonuses: 120.00,
    deductions: 190.00,
    department: 'Arquitectura & Diseño',
    status: 'Pendiente',
    initials: 'LC'
  },
  {
    id: '3',
    name: 'Jorge Salazar',
    role: 'Operador de Grúa',
    hours: 168,
    baseSalary: 2500.00,
    bonuses: 450.00,
    deductions: 125.00,
    department: 'Maquinaria y Logística',
    status: 'Procesado',
    initials: 'JS'
  },
  {
    id: '4',
    name: 'Mariana Paredes',
    role: 'Asistente Administrativa',
    hours: 160,
    baseSalary: 1800.00,
    bonuses: 0.00,
    deductions: 90.00,
    department: 'Administración',
    status: 'Pendiente',
    initials: 'MP'
  }
]);

const paymentHistory = ref([
  {
    id: 'H1',
    title: 'Cierre Septiembre - 2da Quincena',
    date: 'Pagado el 30 Sep, 2026',
    count: 142,
    amount: 138400.00
  },
  {
    id: 'H2',
    title: 'Cierre Septiembre - 1ra Quincena',
    date: 'Pagado el 15 Sep, 2026',
    count: 138,
    amount: 135210.00
  }
]);

const totalBase = computed(() => employees.value.reduce((s, e) => s + e.baseSalary, 0));
const totalBonuses = computed(() => employees.value.reduce((s, e) => s + e.bonuses, 0));
const totalDeductions = computed(() => employees.value.reduce((s, e) => s + e.deductions, 0));
const totalPayrollGross = computed(() => totalBase.value + totalBonuses.value - totalDeductions.value);

const filteredEmployees = computed(() =>
  employees.value.filter(emp => {
    const term = searchTerm.value.toLowerCase();
    const matchesSearch = !term ||
      emp.name.toLowerCase().includes(term) ||
      emp.role.toLowerCase().includes(term);
    const matchesDept = deptFilter.value === 'Todos los departamentos' || emp.department === deptFilter.value;
    return matchesSearch && matchesDept;
  })
);

const showToast = (msg: string) => {
  notification.value = msg;
  setTimeout(() => { notification.value = ''; }, 4000);
};

const handleCreateEmployee = () => {
  if (!newName.value || !newRole.value || !newBase.value) return;

  const initials = newName.value.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
  employees.value.push({
    id: Math.random().toString(36).substring(2, 9),
    name: newName.value,
    role: newRole.value,
    hours: parseFloat(newHours.value) || 0,
    baseSalary: parseFloat(newBase.value) || 0,
    bonuses: parseFloat(newBonuses.value) || 0,
    deductions: parseFloat(newDeductions.value) || 0,
    department: newDept.value as EmployeePayroll['department'],
    status: 'Pendiente',
    initials: initials || 'EE'
  });

  showAddEmployeeModal.value = false;
  showToast(`Empleado ${newName.value} agregado a la planilla.`);

  newName.value = '';
  newRole.value = '';
  newDept.value = 'Obra Civil';
  newHours.value = '160';
  newBase.value = '';
  newBonuses.value = '0';
  newDeductions.value = '0';
};

const executeMassPayments = () => {
  employees.value = employees.value.map(emp => ({ ...emp, status: 'Procesado' as const }));

  const totalAmount = employees.value.reduce((s, e) => s + (e.baseSalary + e.bonuses - e.deductions), 0);
  paymentHistory.value.unshift({
    id: Math.random().toString(),
    title: 'Cierre Octubre - 1ra Quincena',
    date: 'Pagado Hoy',
    count: employees.value.length,
    amount: totalAmount
  });

  showPayModal.value = false;
  showToast('¡Pagos masivos emitidos y procesados correctamente!');
};
</script>
