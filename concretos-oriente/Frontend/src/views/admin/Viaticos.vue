<template>
  <div class="space-y-6 pt-20 pb-10 px-4 md:px-10 max-w-7xl mx-auto relative">
    
    <!-- Header -->
    <header class="flex flex-col md:flex-row md:items-end justify-between gap-4 relative z-10">
      <div>
        <h1 class="text-3xl md:text-4xl font-black text-white tracking-tight flex items-center gap-3">
          <svg class="w-8 h-8 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" /></svg>
          Módulo de Viáticos
        </h1>
        <p class="text-sm md:text-base text-white/50 mt-1">Control y asignación de tiempos de viáticos por colaborador.</p>
      </div>

      <!-- Tab Switcher -->
      <div class="flex gap-2 bg-black/30 border border-white/10 rounded-2xl p-1 w-fit">
        <button 
          @click="activeTab = 'list'" 
          :class="[ 'px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all', activeTab === 'list' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-white/50 hover:text-white' ]"
        >Listado</button>
        <button 
          @click="openRegister()" 
          :class="[ 'px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all', activeTab === 'register' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-white/50 hover:text-white' ]"
        >{{ isEditing ? 'Editar Viáticos' : 'Registrar Viáticos' }}</button>
      </div>
    </header>

    <!-- LIST TAB -->
    <template v-if="activeTab === 'list'">
      <div class="glass-card p-6 rounded-3xl border border-white/5 relative z-10">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
          <h2 class="text-lg font-bold text-white flex items-center gap-2">
            Registros Mensuales
          </h2>
          <input v-model="searchQuery" type="text" placeholder="Buscar..." class="w-full md:w-64 px-4 h-10 rounded-xl glass-input border-white/10 text-sm text-white focus:border-primary" />
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-white/5 bg-white/[0.02]">
                <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Colaborador</th>
                <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Período</th>
                <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40 text-center">Tiempos</th>
                <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40 text-right">Total Pagar</th>
                <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-if="filteredRecords.length === 0">
                <td colspan="5" class="py-12 text-center text-white/40 font-semibold">No se encontraron registros.</td>
              </tr>
              <tr v-for="record in filteredRecords" :key="record.id" class="hover:bg-white/[0.03] transition-colors group">
                <td class="py-5 px-6">
                  <p class="font-bold text-white text-sm">{{ record.empleado_nombre }}</p>
                  <p class="text-[11px] text-white/40 mt-0.5">{{ record.empleado_puesto }}</p>
                </td>
                <td class="py-5 px-6 font-bold text-white/80 uppercase">{{ record.periodo }}</td>
                <td class="py-5 px-6 text-center font-black text-sky-400 text-lg">{{ record.total_tiempos }}</td>
                <td class="py-5 px-6 text-right font-black text-emerald-400">Q {{ formatCurrency(record.monto) }}</td>
                <td class="py-5 px-6 text-right">
                  <div class="flex items-center justify-end gap-2 opacity-50 group-hover:opacity-100 transition-opacity">
                    <button @click="editRecord(record)" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 text-white flex items-center justify-center transition-colors" title="Editar">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" /></svg>
                    </button>
                    <button @click="deleteRecord(record.id)" class="w-8 h-8 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 flex items-center justify-center transition-colors" title="Eliminar">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- REGISTER TAB -->
    <template v-else-if="activeTab === 'register'">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Left: Form -->
        <div class="lg:col-span-8 space-y-6">
          <section class="glass-card p-8 rounded-3xl border border-white/5 relative overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
              <div>
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest block mb-2">Seleccionar Empleado</label>
                <select v-model="form.personnel_id" @change="onEmployeeSelect" class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-bold text-white appearance-none">
                  <option value="" disabled>Seleccione un colaborador...</option>
                  <option v-for="p in personnelList" :key="p.id" :value="p.id" class="bg-slate-900 text-white">{{ p.nombres }} {{ p.apellidos }} ({{ p.puesto }})</option>
                </select>
              </div>
              <div>
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest block mb-2">Período (Mes/Año)</label>
                <input v-model="form.periodo" @change="generateCalendar" type="month" class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-bold text-white" />
              </div>
            </div>

            <!-- Calendar -->
            <div class="bg-black/30 rounded-2xl p-6 border border-white/5">
              <h3 class="text-center text-sm font-black uppercase tracking-widest text-white/70 mb-6">{{ calendarTitle }}</h3>
              
              <div class="grid grid-cols-7 gap-2 mb-2">
                <div v-for="day in ['L','M','M','J','V','S','D']" :key="day" class="text-center text-[10px] font-black text-white/30 uppercase">{{ day }}</div>
              </div>
              
              <div class="grid grid-cols-7 gap-2">
                <div v-for="blank in blankDays" :key="'blank-'+blank" class="aspect-square rounded-xl bg-transparent"></div>
                
                <div 
                  v-for="day in daysInMonth" 
                  :key="day.date"
                  @click="promptTiempos(day)"
                  :class="[
                    'relative aspect-square rounded-xl flex items-center justify-center text-sm font-bold cursor-pointer transition-all border',
                    form.dias_detalle[day.date] ? 'bg-primary/20 border-primary text-white shadow-lg shadow-primary/20' : 'bg-white/5 border-white/5 text-white/50 hover:bg-white/10 hover:text-white hover:border-white/20'
                  ]"
                >
                  {{ day.dayNum }}
                  
                  <span v-if="form.dias_detalle[day.date]" class="absolute -top-2 -right-2 w-6 h-6 rounded-lg bg-sky-500 text-white text-[10px] font-black flex items-center justify-center shadow-lg border border-white/20">
                    {{ form.dias_detalle[day.date] }}
                  </span>
                </div>
              </div>
            </div>

            <div class="mt-8 space-y-4">
              <div>
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest block mb-2">Observaciones Viáticos</label>
                <textarea v-model="form.observaciones" rows="3" class="w-full p-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm text-white" placeholder="Notas o justificaciones..."></textarea>
              </div>
            </div>

          </section>
        </div>

        <!-- Right: Summary -->
        <div class="lg:col-span-4 space-y-6">
          <div class="glass-card p-6 rounded-3xl border border-white/5 space-y-6 sticky top-24">
            <h3 class="text-xs font-black uppercase tracking-widest text-primary flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              Resumen de Viáticos
            </h3>

            <div class="space-y-4">
              <div class="flex justify-between items-center py-2 border-b border-white/5">
                <span class="text-xs font-bold text-white/50 uppercase tracking-wider">Total Tiempos</span>
                <span class="text-lg font-black text-sky-400">{{ totalTiempos }}</span>
              </div>
              <div class="flex justify-between items-center py-2 border-b border-white/5">
                <span class="text-xs font-bold text-white/50 uppercase tracking-wider">Valor por Viático</span>
                <span class="text-base font-black text-white">Q {{ formatCurrency(form.valor_viatico) }}</span>
              </div>
              <div class="flex justify-between items-center pt-2">
                <span class="text-sm font-black text-white uppercase tracking-wider">Monto Total</span>
                <span class="text-2xl font-black text-emerald-400 italic">Q {{ formatCurrency(montoTotal) }}</span>
              </div>
            </div>

            <p class="text-[10px] text-white/30 uppercase text-center mt-4">El dato se reflejará en la planilla del colaborador para este período.</p>

            <button 
              @click="submitForm" 
              :disabled="isSubmitting || !form.personnel_id || montoTotal <= 0"
              class="w-full h-14 rounded-2xl bg-primary text-white font-black uppercase tracking-widest text-xs hover:bg-primary-hover shadow-xl shadow-primary/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed mt-6"
            >
              {{ isSubmitting ? 'Guardando...' : 'Guardar Viáticos' }}
            </button>
          </div>
        </div>

      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import Swal from 'sweetalert2';

const BASE_URL = '/concretos-oriente/Backend/api/v1';

const activeTab = ref('list');
const isEditing = ref(false);
const currentId = ref(null);
const isSubmitting = ref(false);
const searchQuery = ref('');

const records = ref([]);
const personnelList = ref([]);

const blankDays = ref([]);
const daysInMonth = ref([]);

const form = ref({
  personnel_id: '',
  periodo: new Date().toISOString().slice(0, 7),
  dias_detalle: {},
  valor_viatico: 0,
  observaciones: ''
});

const swalBase = {
  background: '#0f172a',
  color: '#ffffff',
  confirmButtonColor: '#3b82f6',
  cancelButtonColor: '#475569',
  customClass: {
    popup: 'border border-white/10 rounded-3xl shadow-2xl',
    title: 'text-xl font-black italic text-white',
    htmlContainer: 'text-white/70',
    input: 'bg-black/50 border border-white/10 text-white rounded-xl focus:border-primary'
  }
};

const formatCurrency = (val) => Number(val || 0).toLocaleString('es-GT', { minimumFractionDigits: 2 });

const totalTiempos = computed(() => {
  return Object.values(form.value.dias_detalle).reduce((acc, curr) => acc + Number(curr), 0);
});

const montoTotal = computed(() => {
  return totalTiempos.value * Number(form.value.valor_viatico);
});

const filteredRecords = computed(() => {
  if (!searchQuery.value) return records.value;
  const q = searchQuery.value.toLowerCase();
  return records.value.filter(r => 
    (r.empleado_nombre || '').toLowerCase().includes(q) ||
    (r.periodo || '').toLowerCase().includes(q)
  );
});

const calendarTitle = computed(() => {
  if (!form.value.periodo) return '';
  const [year, month] = form.value.periodo.split('-');
  const date = new Date(year, month - 1);
  return date.toLocaleString('es-ES', { month: 'long', year: 'numeric' });
});

const generateCalendar = () => {
  if (!form.value.periodo) return;
  const [year, month] = form.value.periodo.split('-');
  
  // First day of month
  const firstDay = new Date(year, month - 1, 1);
  let startingDayOfWeek = firstDay.getDay(); // 0 = Sun, 1 = Mon...
  startingDayOfWeek = startingDayOfWeek === 0 ? 6 : startingDayOfWeek - 1; // Adjust for Mon=0
  
  blankDays.value = Array.from({ length: startingDayOfWeek }, (_, i) => i);
  
  // Total days in month
  const totalDays = new Date(year, month, 0).getDate();
  
  const days = [];
  for (let i = 1; i <= totalDays; i++) {
    const dStr = i.toString().padStart(2, '0');
    days.push({
      date: `${year}-${month}-${dStr}`,
      dayNum: i
    });
  }
  daysInMonth.value = days;
};

const promptTiempos = async (day) => {
  const currentVal = form.value.dias_detalle[day.date] || '';
  
  const { value: tiempos } = await Swal.fire({
    ...swalBase,
    title: `Tiempos - Día ${day.dayNum}`,
    input: 'number',
    inputLabel: 'Ingrese la cantidad de tiempos para este día:',
    inputValue: currentVal,
    showCancelButton: true,
    confirmButtonText: 'Aceptar',
    cancelButtonText: 'Cancelar',
    inputValidator: (value) => {
      if (value && value < 0) return 'La cantidad no puede ser negativa';
    }
  });

  if (tiempos !== undefined) {
    if (tiempos === '' || Number(tiempos) === 0) {
      delete form.value.dias_detalle[day.date];
    } else {
      form.value.dias_detalle[day.date] = Number(tiempos);
    }
  }
};

const onEmployeeSelect = () => {
  const emp = personnelList.value.find(p => p.id === form.value.personnel_id);
  if (emp) {
    form.value.valor_viatico = Number(emp.monto_viaticos || 0);
  }
};

const fetchPersonnel = async () => {
  try {
    const res = await fetch(`${BASE_URL}/personnel`);
    const data = await res.json();
    if (data.status === 'success') {
      personnelList.value = data.data.filter(p => p.estado === 'Activo');
    }
  } catch (err) {}
};

const fetchRecords = async () => {
  try {
    const res = await fetch(`${BASE_URL}/viaticos`);
    const data = await res.json();
    if (data.status === 'success') {
      records.value = data.data;
    }
  } catch (err) {}
};

const openRegister = () => {
  isEditing.value = false;
  currentId.value = null;
  form.value = {
    personnel_id: '',
    periodo: new Date().toISOString().slice(0, 7),
    dias_detalle: {},
    valor_viatico: 0,
    observaciones: ''
  };
  generateCalendar();
  activeTab.value = 'register';
};

const editRecord = (record) => {
  isEditing.value = true;
  currentId.value = record.id;
  
  let diasDetalle = {};
  try {
    diasDetalle = typeof record.dias_detalle === 'string' ? JSON.parse(record.dias_detalle) : record.dias_detalle;
  } catch(e) {}
  
  form.value = {
    personnel_id: record.personnel_id,
    periodo: record.periodo || new Date().toISOString().slice(0, 7),
    dias_detalle: diasDetalle || {},
    valor_viatico: Number(record.valor_viatico || 0),
    observaciones: record.observaciones || ''
  };
  generateCalendar();
  activeTab.value = 'register';
};

const submitForm = async () => {
  if (!form.value.personnel_id || montoTotal.value <= 0) {
    Swal.fire({ ...swalBase, title: 'Atención', text: 'Seleccione un empleado y asigne tiempos.', icon: 'warning' });
    return;
  }

  isSubmitting.value = true;
  try {
    const url = isEditing.value ? `${BASE_URL}/viaticos/${currentId.value}` : `${BASE_URL}/viaticos`;
    const method = isEditing.value ? 'PUT' : 'POST';

    const payload = {
      ...form.value,
      total_tiempos: totalTiempos.value,
      monto: montoTotal.value
    };

    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const result = await res.json();

    if (result.status === 'success') {
      Swal.fire({ ...swalBase, title: '¡Éxito!', text: result.message, icon: 'success' });
      await fetchRecords();
      activeTab.value = 'list';
    } else {
      throw new Error(result.message);
    }
  } catch (err) {
    Swal.fire({ ...swalBase, title: 'Error', text: err.message || 'Error al guardar.', icon: 'error' });
  } finally {
    isSubmitting.value = false;
  }
};

const deleteRecord = async (id) => {
  const result = await Swal.fire({
    ...swalBase, title: '¿Eliminar Viático?', text: "No se puede deshacer.", icon: 'warning', showCancelButton: true, confirmButtonText: 'Eliminar'
  });
  if (result.isConfirmed) {
    try {
      const res = await fetch(`${BASE_URL}/viaticos/${id}`, { method: 'DELETE' });
      const data = await res.json();
      if (data.status === 'success') {
        Swal.fire({ ...swalBase, title: 'Eliminado', icon: 'success' });
        await fetchRecords();
      }
    } catch (err) {}
  }
};

onMounted(() => {
  fetchPersonnel();
  fetchRecords();
  generateCalendar();
});
</script>

<style scoped>
.glass-input::-webkit-calendar-picker-indicator {
  filter: invert(1);
  opacity: 0.5;
  cursor: pointer;
}
</style>
