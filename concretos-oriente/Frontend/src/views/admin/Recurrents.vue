<template>
  <div class="pt-20 pb-10 px-4 md:px-10 md:pb-20 max-w-7xl mx-auto space-y-12 text-white">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Recurrentes</h2>
        <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Gestión de Egresos Recurrentes</p>
      </div>
      <div class="flex items-center gap-4 relative z-20 flex-wrap">
        <input v-model="searchQuery" @input="handleSearch" type="text" placeholder="Buscar concepto..." class="bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-sm font-bold placeholder:text-white/20 text-white focus:outline-none focus:border-primary/50" />
        <button @click="openModal()" class="px-6 py-4 rounded-2xl border border-white/5 bg-white/5 text-white/80 font-black text-xs uppercase tracking-widest hover:bg-white/10 hover:scale-105 transition-all">Nuevo Recurrente</button>
      </div>
    </div>

    <!-- KPI -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6" data-aos="fade-up" data-aos-duration="1000">
      <div class="glass-card p-6 rounded-[30px] relative overflow-hidden group">
        <div class="absolute inset-0 bg-gradient-to-br from-primary/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
        <div class="relative z-10 flex flex-col justify-between h-full space-y-4">
          <p class="text-white/40 text-[10px] font-black uppercase tracking-[0.2em]">Total Gastos Recurrentes</p>
          <div class="flex items-baseline gap-2">
            <span class="text-3xl font-black text-white italic tracking-tighter">
              Q{{ totalRecurrentsAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <section class="glass-card p-10 rounded-[40px]" data-aos="zoom-in-up" data-aos-duration="1000">
      <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full min-w-[640px] text-left border-collapse">
          <thead>
            <tr class="text-[11px] font-bold text-white/20 uppercase tracking-[0.3em] border-b border-white/5">
              <th class="p-6">Concepto</th>
              <th class="p-6">Descripción</th>
              <th class="p-6">Monto</th>
              <th class="p-6">Día de Pago</th>
              <th class="p-6 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-if="recurrents.length === 0">
               <td colspan="5" class="p-10 text-center text-white/40 font-bold uppercase tracking-widest text-xs">No hay recurrentes registrados.</td>
            </tr>
            <tr v-for="item in paginatedRecurrents" :key="item.id" class="hover:bg-white/5 transition-all">
              <td class="p-6 font-black uppercase text-sm">{{ item.concepto }}</td>
              <td class="p-6 text-white/50 text-xs">{{ item.descripcion || '-' }}</td>
              <td class="p-6 text-primary font-bold">
                {{ item.monto ? `Q${Number(item.monto).toLocaleString('en-US', {minimumFractionDigits:2})}` : '-' }}
              </td>
              <td class="p-6 text-white/80 font-bold">{{ item.dia_pago || '-' }}</td>
              <td class="p-6 text-right space-x-4">
                <button @click="openModal(item)" class="text-white/40 hover:text-primary transition-colors text-xs font-bold uppercase tracking-widest">Editar</button>
                <button @click="deleteRecurrent(item.id)" class="text-white/40 hover:text-red-500 transition-colors text-xs font-bold uppercase tracking-widest">Eliminar</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div v-if="totalPages > 1" class="mt-8 flex items-center justify-between border-t border-white/5 pt-6">
        <p class="text-xs font-bold text-white/40 uppercase tracking-widest">Página {{ currentPage }} de {{ totalPages }}</p>
        <div class="flex gap-2">
          <button @click="prevPage" :disabled="currentPage === 1" class="px-4 py-2 rounded-xl border border-white/5 bg-white/5 text-white/60 hover:text-white hover:bg-white/10 transition-all text-xs font-bold uppercase disabled:opacity-50">Anterior</button>
          <button @click="nextPage" :disabled="currentPage === totalPages" class="px-4 py-2 rounded-xl border border-white/5 bg-white/5 text-white/60 hover:text-white hover:bg-white/10 transition-all text-xs font-bold uppercase disabled:opacity-50">Siguiente</button>
        </div>
      </div>
    </section>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-md">
      <div class="absolute inset-0 cursor-pointer" @click="closeModal"></div>
      <div class="relative w-full max-w-xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.2)]" data-aos="zoom-in-up" data-aos-duration="1000">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">{{ isEditing ? 'Editar' : 'Nuevo' }} Recurrente</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Completa los datos del recurrente</p>

        <form @submit.prevent="saveRecurrent" class="space-y-6">
          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Concepto *</label>
            <input type="text" required v-model="form.concepto" placeholder="Ej. Pago de Luz" class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 text-white uppercase" />
          </div>
          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Descripción</label>
            <input type="text" v-model="form.descripcion" placeholder="Opcional" class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 text-white" />
          </div>
          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Monto (Q)</label>
              <input type="text" :value="form.monto_display" @input="handleCurrencyInput" placeholder="Q0.00" class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 text-white" />
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Día de Pago</label>
              <input type="number" min="1" max="31" v-model="form.dia_pago" placeholder="Ej. 15" class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 text-white" />
            </div>
          </div>
          <div class="flex gap-4 pt-4">
            <button type="submit" class="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all">Guardar</button>
            <button type="button" @click="closeModal" class="px-8 py-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-sm font-bold text-white/50">Cancelar</button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../../services/api';
import Swal from 'sweetalert2';

const recurrents = ref([]);
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = ref({
  concepto: '',
  descripcion: '',
  monto: 0,
  monto_display: '',
  dia_pago: ''
});

const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = 10;

const handleSearch = () => {
  currentPage.value = 1;
};

const totalRecurrentsAmount = computed(() => {
  return recurrents.value.reduce((sum, item) => sum + (Number(item.monto) || 0), 0);
});

const filteredRecurrents = computed(() => {
  let filtered = recurrents.value;
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    filtered = filtered.filter(item => 
      item.concepto.toLowerCase().includes(q) || 
      (item.descripcion && item.descripcion.toLowerCase().includes(q))
    );
  }
  return filtered;
});

const totalPages = computed(() => Math.ceil(filteredRecurrents.value.length / itemsPerPage) || 1);

const paginatedRecurrents = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  return filteredRecurrents.value.slice(start, end);
});

const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };

const handleCurrencyInput = (event) => {
  let val = event.target.value;
  let num = val.replace(/\D/g, '');
  if (!num) {
    form.value.monto_display = '';
    form.value.monto = 0;
    return;
  }
  let floatVal = (parseInt(num, 10) / 100);
  form.value.monto = floatVal;
  form.value.monto_display = 'Q' + floatVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
};

const fetchRecurrents = async () => {
  try {
    const res = await api.get('/recurrents');
    if (res.data.status === 'success') {
      recurrents.value = res.data.data;
    }
  } catch (error) {
    console.error('Error fetching recurrents:', error);
  }
};

onMounted(() => {
  fetchRecurrents();
});

const openModal = (item = null) => {
  if (item) {
    isEditing.value = true;
    editingId.value = item.id;
    const m = item.monto ? Number(item.monto) : 0;
    form.value = {
      concepto: item.concepto,
      descripcion: item.descripcion || '',
      monto: m,
      monto_display: m ? 'Q' + m.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '',
      dia_pago: item.dia_pago || ''
    };
  } else {
    isEditing.value = false;
    editingId.value = null;
    form.value = { concepto: '', descripcion: '', monto: 0, monto_display: '', dia_pago: '' };
  }
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  form.value = { concepto: '', descripcion: '', monto: 0, monto_display: '', dia_pago: '' };
};

const saveRecurrent = async () => {
  try {
    const payload = { ...form.value };
    let res;
    if (isEditing.value) {
      res = await api.put(`/recurrents/${editingId.value}`, payload);
    } else {
      res = await api.post('/recurrents', payload);
    }

    if (res.data.status === 'success') {
      Swal.fire({
        title: '¡Éxito!',
        text: 'Recurrente guardado correctamente.',
        icon: 'success',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
        customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
      });
      closeModal();
      fetchRecurrents();
    } else {
      Swal.fire('Error', res.data.message, 'error');
    }
  } catch (error) {
    Swal.fire('Error', 'No se pudo guardar el recurrente', 'error');
  }
};

const deleteRecurrent = async (id) => {
  const result = await Swal.fire({
    title: '¿Eliminar recurrente?',
    text: "Esta acción no se puede deshacer.",
    icon: 'warning',
    showCancelButton: true,
    background: '#0f172a',
    color: '#fff',
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, eliminar',
    customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold', cancelButton: 'rounded-xl px-6 py-3 font-bold' }
  });

  if (result.isConfirmed) {
    try {
      const res = await api.delete(`/recurrents/${id}`);
      if (res.data.status === 'success') {
        Swal.fire({
          title: 'Eliminado!',
          text: 'El recurrente ha sido eliminado.',
          icon: 'success',
          background: '#0f172a',
          color: '#fff',
          confirmButtonColor: '#6366f1',
          customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
        });
        fetchRecurrents();
      }
    } catch (error) {
      Swal.fire('Error', 'No se pudo eliminar', 'error');
    }
  }
};
</script>
