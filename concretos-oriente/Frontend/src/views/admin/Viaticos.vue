<template>
  <div class="space-y-6">
    <!-- Header -->
    <header class="flex flex-col md:flex-row md:items-end justify-between gap-4 relative z-10">
      <div>
        <h1 class="text-3xl md:text-4xl font-black text-white tracking-tight flex items-center gap-3">
          <ArrowTrendingUpIcon class="w-8 h-8 text-primary" />
          Control de Viáticos
        </h1>
        <p class="text-sm md:text-base text-white/50 mt-1">Gestión de viáticos de personal.</p>
      </div>

      <div class="flex gap-3">
        <button
          @click="openModal()"
          class="h-11 px-5 rounded-2xl bg-primary text-white font-bold text-sm hover:bg-primary-hover shadow-lg shadow-primary/20 transition-all flex items-center gap-2"
        >
          <PlusIcon class="w-5 h-5" />
          Registrar Viático
        </button>
      </div>
    </header>

    <!-- Content -->
    <div class="bg-black/40 backdrop-blur-xl border border-white/10 rounded-3xl overflow-hidden shadow-2xl relative z-10">
      <div class="p-6 border-b border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/[0.02]">
        <h2 class="text-lg font-bold text-white flex items-center gap-2">
          <ClipboardDocumentListIcon class="w-5 h-5 text-primary" />
          Registros
        </h2>

        <div class="flex items-center gap-3">
          <div class="relative w-full md:w-64">
            <MagnifyingGlassIcon class="w-5 h-5 text-white/30 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Buscar..."
              class="w-full pl-10 pr-4 h-10 rounded-xl bg-black/20 border border-white/10 text-sm text-white focus:outline-none focus:border-primary transition-colors"
            />
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-white/5 bg-white/[0.02]">
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Colaborador</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Fechas</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Monto</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Motivo</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Estado</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-if="filteredRecords.length === 0">
              <td colspan="6" class="py-12 text-center text-white/40 font-semibold">No se encontraron registros.</td>
            </tr>
            <tr v-for="record in paginatedRecords" :key="record.id" class="hover:bg-white/[0.03] transition-colors group">
              <td class="py-5 px-6">
                <p class="font-bold text-white text-sm">{{ record.empleado_nombre }}</p>
                <p class="text-[11px] text-white/40 mt-0.5">{{ record.empleado_puesto }}</p>
              </td>
              <td class="py-5 px-6">
                <p class="text-sm text-white/80"><span class="text-white/40">Sol:</span> {{ record.fecha_solicitud }}</p>
                <p v-if="record.fecha_inicio" class="text-[11px] text-white/60">
                  {{ record.fecha_inicio }} a {{ record.fecha_fin }}
                </p>
              </td>
              <td class="py-5 px-6">
                <span class="font-bold text-emerald-400">Q {{ formatCurrency(record.monto) }}</span>
              </td>
              <td class="py-5 px-6">
                <p class="text-sm text-white/80 line-clamp-1 max-w-[200px]" :title="record.motivo">{{ record.motivo }}</p>
              </td>
              <td class="py-5 px-6">
                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-primary/20 text-primary border border-primary/20">
                  {{ record.estado }}
                </span>
              </td>
              <td class="py-5 px-6 text-right">
                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click="openModal(record)" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 text-white flex items-center justify-center transition-colors" title="Editar">
                    <PencilSquareIcon class="w-4 h-4" />
                  </button>
                  <button @click="deleteRecord(record.id)" class="w-8 h-8 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 flex items-center justify-center transition-colors" title="Eliminar">
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="p-6 border-t border-white/5 flex items-center justify-between">
        <p class="text-xs text-white/40">
          Página <span class="text-white font-bold">{{ currentPage }}</span> de <span class="text-white font-bold">{{ totalPages }}</span>
        </p>
        <div class="flex gap-2">
          <button @click="currentPage--" :disabled="currentPage === 1" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 disabled:opacity-50 text-xs font-bold text-white transition-all">Anterior</button>
          <button @click="currentPage++" :disabled="currentPage === totalPages" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 disabled:opacity-50 text-xs font-bold text-white transition-all">Siguiente</button>
        </div>
      </div>
    </div>

    <!-- Modal Formulario -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="closeModal"></div>
      
      <div class="relative w-full max-w-2xl bg-slate-900 border border-white/10 rounded-3xl shadow-2xl flex flex-col max-h-[90vh]">
        <div class="p-6 md:p-8 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
          <div>
            <h3 class="text-xl font-black text-white">{{ isEditing ? 'Editar Viático' : 'Nuevo Viático' }}</h3>
            <p class="text-sm text-white/40 mt-1">Completa los detalles de la solicitud de viático.</p>
          </div>
          <button @click="closeModal" class="w-10 h-10 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center text-white/60 hover:text-white transition-colors">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <div class="p-6 md:p-8 overflow-y-auto custom-scrollbar">
          <form @submit.prevent="submitForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <div class="md:col-span-2">
              <label class="text-[10px] font-black uppercase tracking-widest text-white/40 block mb-2">Colaborador</label>
              <select
                v-model="form.personnel_id"
                required
                class="w-full h-12 px-4 rounded-xl bg-black/20 border border-white/10 text-sm text-white focus:outline-none focus:border-primary transition-colors appearance-none"
              >
                <option value="" disabled>Seleccione un colaborador...</option>
                <option v-for="emp in personnelList" :key="emp.id" :value="emp.id">
                  {{ emp.nombres }} {{ emp.apellidos }} ({{ emp.puesto }})
                </option>
              </select>
            </div>

            <div>
              <label class="text-[10px] font-black uppercase tracking-widest text-white/40 block mb-2">Fecha de Solicitud</label>
              <input
                v-model="form.fecha_solicitud"
                type="date"
                required
                class="w-full h-12 px-4 rounded-xl bg-black/20 border border-white/10 text-sm text-white focus:outline-none focus:border-primary transition-colors"
              />
            </div>

            <div>
              <label class="text-[10px] font-black uppercase tracking-widest text-white/40 block mb-2">Monto (Q)</label>
              <input
                v-model.number="form.monto"
                type="number"
                step="0.01"
                min="0"
                required
                placeholder="0.00"
                class="w-full h-12 px-4 rounded-xl bg-black/20 border border-white/10 text-sm font-bold text-white focus:outline-none focus:border-primary transition-colors"
              />
            </div>

            <div>
              <label class="text-[10px] font-black uppercase tracking-widest text-white/40 block mb-2">Fecha Inicio Viaje</label>
              <input
                v-model="form.fecha_inicio"
                type="date"
                class="w-full h-12 px-4 rounded-xl bg-black/20 border border-white/10 text-sm text-white focus:outline-none focus:border-primary transition-colors"
              />
            </div>

            <div>
              <label class="text-[10px] font-black uppercase tracking-widest text-white/40 block mb-2">Fecha Fin Viaje</label>
              <input
                v-model="form.fecha_fin"
                type="date"
                class="w-full h-12 px-4 rounded-xl bg-black/20 border border-white/10 text-sm text-white focus:outline-none focus:border-primary transition-colors"
              />
            </div>

            <div class="md:col-span-2">
              <label class="text-[10px] font-black uppercase tracking-widest text-white/40 block mb-2">Motivo</label>
              <input
                v-model="form.motivo"
                type="text"
                required
                placeholder="Ej. Viaje a planta, reunión de ventas..."
                class="w-full h-12 px-4 rounded-xl bg-black/20 border border-white/10 text-sm text-white focus:outline-none focus:border-primary transition-colors"
              />
            </div>

            <div class="md:col-span-2">
              <label class="text-[10px] font-black uppercase tracking-widest text-white/40 block mb-2">Estado</label>
              <select
                v-model="form.estado"
                class="w-full h-12 px-4 rounded-xl bg-black/20 border border-white/10 text-sm text-white focus:outline-none focus:border-primary transition-colors appearance-none"
              >
                <option value="Pendiente">Pendiente</option>
                <option value="Aprobado">Aprobado</option>
                <option value="Rechazado">Rechazado</option>
                <option value="Liquidado">Liquidado</option>
              </select>
            </div>

            <div class="md:col-span-2">
              <label class="text-[10px] font-black uppercase tracking-widest text-white/40 block mb-2">Observaciones</label>
              <textarea
                v-model="form.observaciones"
                rows="3"
                placeholder="Detalles adicionales..."
                class="w-full p-4 rounded-xl bg-black/20 border border-white/10 text-sm text-white focus:outline-none focus:border-primary transition-colors resize-none"
              ></textarea>
            </div>

          </form>
        </div>

        <div class="p-6 md:p-8 border-t border-white/5 bg-white/[0.02] flex justify-end gap-3 rounded-b-3xl">
          <button @click="closeModal" type="button" class="px-6 py-3 rounded-xl bg-white/5 hover:bg-white/10 text-white text-sm font-bold transition-colors">
            Cancelar
          </button>
          <button
            @click="submitForm"
            :disabled="isSubmitting"
            class="px-6 py-3 rounded-xl bg-primary hover:bg-primary-hover text-white text-sm font-bold shadow-lg shadow-primary/20 transition-all disabled:opacity-50 flex items-center gap-2"
          >
            <div v-if="isSubmitting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
            {{ isEditing ? 'Guardar Cambios' : 'Registrar Viático' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import {
  ArrowTrendingUpIcon, PlusIcon, MagnifyingGlassIcon,
  ClipboardDocumentListIcon, PencilSquareIcon, TrashIcon, XMarkIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1';

// SweetAlert Config
const swalBase = {
  background: '#0f172a',
  color: '#fff',
  buttonsStyling: false,
  customClass: {
    confirmButton: 'bg-primary hover:bg-primary-hover text-white font-bold py-3 px-6 rounded-xl shadow-lg transition-all',
    cancelButton: 'bg-white/5 hover:bg-white/10 text-white font-bold py-3 px-6 rounded-xl transition-all ml-3'
  }
};

// States
const records = ref([]);
const personnelList = ref([]);
const isSubmitting = ref(false);
const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = 10;

// Modal States
const showModal = ref(false);
const isEditing = ref(false);
const currentId = ref(null);

const form = ref({
  personnel_id: '',
  fecha_solicitud: new Date().toISOString().split('T')[0],
  fecha_inicio: '',
  fecha_fin: '',
  monto: 0,
  motivo: '',
  estado: 'Pendiente',
  observaciones: ''
});

// Computed
const filteredRecords = computed(() => {
  if (!searchQuery.value) return records.value;
  const q = searchQuery.value.toLowerCase();
  return records.value.filter(r => 
    r.empleado_nombre?.toLowerCase().includes(q) ||
    r.motivo?.toLowerCase().includes(q)
  );
});

const totalPages = computed(() => Math.ceil(filteredRecords.value.length / itemsPerPage) || 1);
const paginatedRecords = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredRecords.value.slice(start, start + itemsPerPage);
});

// Formatters
const formatCurrency = (val) => {
  return Number(val).toLocaleString('es-GT', { minimumFractionDigits: 2 });
};

// API Calls
const fetchPersonnel = async () => {
  try {
    const res = await fetch(`${BASE_URL}/personnel`);
    const data = await res.json();
    if (data.status === 'success') {
      personnelList.value = data.data.filter(p => p.estado === 'Activo');
    }
  } catch (err) {
    console.error('Error al cargar personal:', err);
  }
};

const fetchRecords = async () => {
  try {
    const res = await fetch(`${BASE_URL}/viaticos`);
    const data = await res.json();
    if (data.status === 'success') {
      records.value = data.data;
    }
  } catch (err) {
    console.error('Error fetching viaticos:', err);
  }
};

// Actions
const openModal = (record = null) => {
  if (record) {
    isEditing.value = true;
    currentId.value = record.id;
    form.value = {
      personnel_id: record.personnel_id,
      fecha_solicitud: record.fecha_solicitud,
      fecha_inicio: record.fecha_inicio || '',
      fecha_fin: record.fecha_fin || '',
      monto: Number(record.monto),
      motivo: record.motivo,
      estado: record.estado,
      observaciones: record.observaciones || ''
    };
  } else {
    isEditing.value = false;
    currentId.value = null;
    form.value = {
      personnel_id: '',
      fecha_solicitud: new Date().toISOString().split('T')[0],
      fecha_inicio: '',
      fecha_fin: '',
      monto: 0,
      motivo: '',
      estado: 'Pendiente',
      observaciones: ''
    };
  }
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const submitForm = async () => {
  if (!form.value.personnel_id || !form.value.motivo || form.value.monto <= 0) {
    Swal.fire({ ...swalBase, title: 'Atención', text: 'Por favor complete todos los campos obligatorios correctamente.', icon: 'warning' });
    return;
  }

  isSubmitting.value = true;
  try {
    const url = isEditing.value ? `${BASE_URL}/viaticos/${currentId.value}` : `${BASE_URL}/viaticos`;
    const method = isEditing.value ? 'PUT' : 'POST';

    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form.value)
    });
    const result = await res.json();

    if (result.status === 'success') {
      Swal.fire({ ...swalBase, title: '¡Éxito!', text: result.message, icon: 'success' });
      await fetchRecords();
      closeModal();
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
    ...swalBase,
    title: '¿Eliminar Viático?',
    text: "Esta acción no se puede deshacer.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  });

  if (result.isConfirmed) {
    try {
      const res = await fetch(`${BASE_URL}/viaticos/${id}`, { method: 'DELETE' });
      const data = await res.json();
      if (data.status === 'success') {
        Swal.fire({ ...swalBase, title: 'Eliminado', text: 'El registro ha sido eliminado.', icon: 'success' });
        await fetchRecords();
      } else {
        throw new Error(data.message);
      }
    } catch (err) {
      Swal.fire({ ...swalBase, title: 'Error', text: err.message || 'No se pudo eliminar el registro.', icon: 'error' });
    }
  }
};

// Init
onMounted(() => {
  fetchPersonnel();
  fetchRecords();
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.02);
  border-radius: 8px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.2);
}
</style>
