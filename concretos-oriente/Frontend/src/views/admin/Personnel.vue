<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-10 relative">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h2 class="text-4xl font-bold tracking-tight text-white mb-2">Gestión de Personal</h2>
        <p class="text-white/60">Gestiona tu fuerza laboral y registra nuevos empleados.</p>
      </div>
      <button 
        @click="openModal()"
        class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center justify-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 hover:-translate-y-0.5 active:translate-y-0 transition-all"
      >
        <PlusIcon class="w-5 h-5" />
        Añadir Personal
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div
        v-for="(stat, i) in stats"
        :key="i"
        class="glass-card p-10 rounded-[32px] flex flex-col justify-between h-52 cursor-pointer group hover:-translate-y-1.5 hover:scale-[1.02] transition-all duration-300"
      >
        <div class="flex items-center justify-between mb-4">
          <div :class="`p-4 rounded-2xl ${stat.bgColor} ${stat.color} border border-white/10 shadow-lg`">
            <component :is="stat.icon" class="w-8 h-8" />
          </div>
          <span :class="`text-[11px] font-bold px-3.5 py-1.5 rounded-full ${stat.color} ${stat.bgColor} border border-white/5 tracking-wider uppercase`">
            {{ stat.change }}
          </span>
        </div>
        <div>
          <p class="text-white/40 text-[11px] font-bold uppercase tracking-[0.2em]">{{ stat.label }}</p>
          <h3 class="text-4xl font-bold text-white mt-2 group-hover:text-primary transition-colors">{{ stat.value }}</h3>
        </div>
      </div>
    </div>

    <!-- Table Section -->
    <div class="glass-card rounded-[40px] overflow-hidden border border-white/10 transition-all duration-300">
      <div class="p-10 flex flex-wrap items-center justify-between gap-6 border-b border-white/5">
        <div class="flex flex-wrap items-center gap-4">
          <div class="glass-input px-6 py-3 rounded-2xl flex items-center gap-3 text-white/60 font-bold text-xs uppercase tracking-widest cursor-pointer">
            <FunnelIcon class="w-4 h-4" />
            Filtros
          </div>
        </div>
        <button class="flex items-center gap-2 text-primary text-sm font-bold hover:bg-white/5 px-8 py-3 rounded-2xl transition-all border border-white/10">
          <ArrowDownTrayIcon class="w-5 h-5" />
          Exportar CSV
        </button>
      </div>

      <div class="overflow-x-auto px-4">
        <table class="w-full text-left">
          <thead>
            <tr class="text-[11px] font-bold text-white/40 uppercase tracking-[0.2em]">
              <th class="px-8 py-8">Nombre del Empleado</th>
              <th class="px-8 py-8">DPI / NIT</th>
              <th class="px-8 py-8">Puesto de Trabajo</th>
              <th class="px-8 py-8">Teléfono</th>
              <th class="px-8 py-8">Salario Base</th>
              <th class="px-8 py-8 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-if="loading">
               <td colspan="6" class="px-8 py-8 text-center text-white/50">Cargando personal...</td>
            </tr>
            <tr v-else-if="personnel.length === 0">
               <td colspan="6" class="px-8 py-8 text-center text-white/50">No hay personal registrado aún.</td>
            </tr>
            <tr v-for="emp in personnel" :key="emp.id" class="hover:bg-white/5 group transition-colors duration-200">
              <td class="px-8 py-8">
                <div class="flex items-center gap-5">
                  <div 
                    @click="emp.foto_path ? openImageFullScreen(getPhotoUrl(emp)) : null"
                    :class="['w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center overflow-hidden border border-white/10 shadow-lg transition-transform hover:scale-105', emp.foto_path ? 'cursor-pointer' : '']"
                  >
                    <img v-if="emp.foto_path" :src="getPhotoUrl(emp)" alt="Foto" class="w-full h-full object-cover" />
                    <span v-else class="font-bold text-primary">{{ getInitials(emp.nombres, emp.apellidos) }}</span>
                  </div>
                  <div>
                    <p class="font-bold text-white text-lg">{{ emp.nombres }} {{ emp.apellidos }}</p>
                    <p class="text-xs text-white/40 font-medium tracking-widest mt-1">ID: {{ emp.id }}</p>
                  </div>
                </div>
              </td>
              <td class="px-8 py-8">
                <p class="text-sm font-semibold text-white/90">{{ emp.dpi }}</p>
                <p class="text-xs text-white/40 mt-1">{{ emp.nit || 'N/A' }}</p>
              </td>
              <td class="px-8 py-8">
                <span class="text-sm font-semibold text-white/70">{{ emp.puesto }}</span>
              </td>
              <td class="px-8 py-8">
                <span class="text-sm font-bold text-white/90">{{ emp.telefono }}</span>
              </td>
              <td class="px-8 py-8 font-bold text-white text-base">
                Q {{ formatCurrency(emp.salario_base) }}
              </td>
              <td class="px-8 py-8">
                <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all">
                  <button @click="openViewModal(emp)" class="p-3 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all" title="Visualizar">
                    <EyeIcon class="w-5 h-5" />
                  </button>
                  <button @click="openEditModal(emp)" class="p-3 text-white/40 hover:text-primary hover:bg-white/10 rounded-xl transition-all" title="Editar">
                    <PencilIcon class="w-5 h-5" />
                  </button>
                  <button @click="deleteEmployee(emp.id)" class="p-3 text-white/40 hover:text-tertiary hover:bg-white/10 rounded-xl transition-all" title="Eliminar">
                    <TrashIcon class="w-5 h-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="px-8 py-8 flex items-center justify-between border-t border-white/5">
        <p class="text-xs font-bold text-white/30 tracking-widest uppercase">Mostrando {{ personnel.length }} empleados</p>
      </div>
    </div>

    <!-- Modal Añadir/Editar Personal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>
      
      <div class="glass-card w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-[32px] p-8 relative z-10 border border-white/10 shadow-2xl">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-2xl font-bold text-white">{{ isEditing ? 'Editar Empleado' : 'Añadir Nuevo Personal' }}</h3>
          <button @click="closeModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all">
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <form @submit.prevent="submitForm" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nombres -->
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Nombres</label>
              <input v-model="formData.nombres" type="text" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" placeholder="Ej. Juan Carlos" />
            </div>

            <!-- Apellidos -->
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Apellidos</label>
              <input v-model="formData.apellidos" type="text" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" placeholder="Ej. Pérez García" />
            </div>

            <!-- DPI -->
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">DPI</label>
              <input v-model="formData.dpi" @input="formatDpi" type="text" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" placeholder="0000 00000 0000" />
            </div>

            <!-- NIT -->
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">NIT</label>
              <input v-model="formData.nit" type="text" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" placeholder="Opcional" />
            </div>

            <!-- Puesto -->
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Puesto de Trabajo</label>
              <input v-model="formData.puesto" type="text" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" placeholder="Ej. Ingeniero Principal" />
            </div>

            <!-- Teléfono -->
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Teléfono</label>
              <input v-model="formData.telefono" @input="formatPhone" type="text" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" placeholder="0000-0000" />
            </div>

            <!-- Salario Base -->
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Salario Base (Q)</label>
              <input v-model="formData.salario_base" type="number" step="0.01" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" placeholder="0.00" />
            </div>

            <!-- Hora Extra -->
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Q por Hora Extra</label>
              <input v-model="formData.pago_hora_extra" type="number" step="0.01" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" placeholder="0.00" />
            </div>
            
            <!-- Foto -->
            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">
                Foto del Empleado (PNG, JPG, JPEG) <span v-if="isEditing" class="text-primary normal-case">- Sube una foto para reemplazarla</span>
              </label>
              <input @change="handleFileChange" type="file" accept=".png, .jpg, .jpeg" class="w-full text-white/60 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-primary/20 file:text-primary hover:file:bg-primary/30 file:transition-all cursor-pointer bg-black/20 border border-white/10 rounded-2xl p-2" />
            </div>
          </div>

          <div class="pt-6 flex justify-end gap-4 border-t border-white/5">
            <button type="button" @click="closeModal" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">
              Cancelar
            </button>
            <button type="submit" :disabled="isSubmitting" class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
              <span v-if="isSubmitting">Guardando...</span>
              <span v-else>{{ isEditing ? 'Actualizar Empleado' : 'Guardar Empleado' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Visualizar Empleado -->
    <div v-if="showViewModal && selectedEmp" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeViewModal"></div>
      
      <div class="glass-card w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-[32px] p-8 relative z-10 border border-white/10 shadow-2xl">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-2xl font-bold text-white">Detalles del Empleado</h3>
          <button @click="closeViewModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all">
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <div class="flex flex-col md:flex-row gap-8">
          <!-- Foto Grande Izquierda -->
          <div class="w-full md:w-1/3 flex flex-col items-center gap-4">
            <div 
              @click="selectedEmp.foto_path ? openImageFullScreen(getPhotoUrl(selectedEmp)) : null"
              :class="['w-40 h-40 rounded-3xl bg-white/5 flex items-center justify-center overflow-hidden border border-white/10 shadow-2xl', selectedEmp.foto_path ? 'cursor-pointer hover:scale-105 transition-transform' : '']"
            >
              <img v-if="selectedEmp.foto_path" :src="getPhotoUrl(selectedEmp)" alt="Foto" class="w-full h-full object-cover" />
              <span v-else class="font-bold text-primary text-5xl">{{ getInitials(selectedEmp.nombres, selectedEmp.apellidos) }}</span>
            </div>
            <div class="text-center">
              <span :class="`px-4 py-1.5 rounded-full text-[10px] font-extrabold uppercase tracking-widest border transition-all ${
                selectedEmp.estado === 'Activo' 
                  ? 'bg-primary/20 text-primary border-primary/20 shadow-[0_0_15px_rgba(99,102,241,0.1)]' 
                  : 'bg-white/10 text-white/60 border-white/5'
              }`">
                {{ selectedEmp.estado }}
              </span>
            </div>
          </div>

          <!-- Información Derecha -->
          <div class="w-full md:w-2/3 grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-4">
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Nombre Completo</p>
              <p class="text-lg font-bold text-white">{{ selectedEmp.nombres }} {{ selectedEmp.apellidos }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">ID Empleado</p>
              <p class="text-base font-semibold text-white/90">#{{ selectedEmp.id }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Puesto de Trabajo</p>
              <p class="text-base font-semibold text-primary">{{ selectedEmp.puesto }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Teléfono</p>
              <p class="text-base font-semibold text-white/90">{{ selectedEmp.telefono }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">DPI</p>
              <p class="text-base font-semibold text-white/90">{{ selectedEmp.dpi }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">NIT</p>
              <p class="text-base font-semibold text-white/90">{{ selectedEmp.nit || 'No registrado' }}</p>
            </div>
            <div class="bg-white/5 p-4 rounded-2xl border border-white/5">
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Salario Base</p>
              <p class="text-xl font-bold text-white">Q {{ formatCurrency(selectedEmp.salario_base) }}</p>
            </div>
            <div class="bg-white/5 p-4 rounded-2xl border border-white/5">
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Pago Extra (Hr)</p>
              <p class="text-xl font-bold text-white">Q {{ formatCurrency(selectedEmp.pago_hora_extra) }}</p>
            </div>
            <div class="sm:col-span-2">
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Registrado el</p>
              <p class="text-sm font-semibold text-white/60">{{ new Date(selectedEmp.created_at).toLocaleString() }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Fullscreen Image Viewer -->
    <div v-if="fullscreenImage" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/90 backdrop-blur-md transition-opacity" @click="fullscreenImage = null">
      <button class="absolute top-6 right-6 p-3 text-white/60 hover:text-white bg-white/10 hover:bg-white/20 rounded-full transition-all">
        <XMarkIcon class="w-8 h-8" />
      </button>
      <img :src="fullscreenImage" class="max-w-full max-h-[90vh] object-contain rounded-xl shadow-2xl scale-100 animate-[pulse_0.5s_ease-out_1]" @click.stop />
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { 
  UsersIcon, CheckCircleIcon, PlusIcon, FunnelIcon, ArrowDownTrayIcon, 
  XMarkIcon, EyeIcon, PencilIcon, TrashIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const BASE_URL = '/concretos-oriente/Backend/api/v1';

const personnel = ref([]);
const loading = ref(true);
const showModal = ref(false);
const showViewModal = ref(false);
const isSubmitting = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const selectedEmp = ref(null);
const fullscreenImage = ref(null);

const formData = ref({
  nombres: '',
  apellidos: '',
  dpi: '',
  nit: '',
  puesto: '',
  telefono: '',
  salario_base: '',
  pago_hora_extra: '',
  foto: null
});

const stats = computed(() => [
  { label: "Total de Empleados", value: personnel.value.length.toString(), change: "Actual", icon: UsersIcon, color: "text-primary", bgColor: "bg-primary/20" },
  { label: "Activos", value: personnel.value.length.toString(), change: "100%", icon: CheckCircleIcon, color: "text-primary", bgColor: "bg-white/10" },
]);

onMounted(() => {
  fetchPersonnel();
});

const fetchPersonnel = async () => {
  loading.value = true;
  try {
    const response = await fetch(`${BASE_URL}/personnel`);
    const result = await response.json();
    if (result.status === 'success') {
      const fetchTime = Date.now();
      // Agregar un identificador de tiempo local para forzar recarga de cache
      personnel.value = result.data.map(emp => ({...emp, _t: fetchTime}));
    }
  } catch (error) {
    console.error("Error fetching personnel:", error);
  } finally {
    loading.value = false;
  }
};

const openModal = () => {
  resetForm();
  isEditing.value = false;
  editingId.value = null;
  showModal.value = true;
};

const openEditModal = (emp) => {
  formData.value = {
    nombres: emp.nombres,
    apellidos: emp.apellidos,
    dpi: emp.dpi,
    nit: emp.nit,
    puesto: emp.puesto,
    telefono: emp.telefono,
    salario_base: emp.salario_base,
    pago_hora_extra: emp.pago_hora_extra,
    foto: null // Foto se debe re-subir si se quiere cambiar
  };
  isEditing.value = true;
  editingId.value = emp.id;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  resetForm();
};

const resetForm = () => {
  formData.value = {
    nombres: '',
    apellidos: '',
    dpi: '',
    nit: '',
    puesto: '',
    telefono: '',
    salario_base: '',
    pago_hora_extra: '',
    foto: null
  };
};

const openViewModal = (emp) => {
  selectedEmp.value = emp;
  showViewModal.value = true;
};

const closeViewModal = () => {
  showViewModal.value = false;
  selectedEmp.value = null;
};

const openImageFullScreen = (url) => {
  fullscreenImage.value = url;
};

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    formData.value.foto = file;
  }
};

const formatDpi = (e) => {
  let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
  if (value.length > 13) value = value.slice(0, 13);
  
  // Format: 0000 00000 0000
  let formatted = '';
  if (value.length > 0) formatted += value.substring(0, 4);
  if (value.length > 4) formatted += ' ' + value.substring(4, 9);
  if (value.length > 9) formatted += ' ' + value.substring(9, 13);
  
  formData.value.dpi = formatted;
};

const formatPhone = (e) => {
  let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
  if (value.length > 8) value = value.slice(0, 8);
  
  // Format: 0000-0000
  let formatted = '';
  if (value.length > 0) formatted += value.substring(0, 4);
  if (value.length > 4) formatted += '-' + value.substring(4, 8);
  
  formData.value.telefono = formatted;
};

const formatCurrency = (value) => {
  if (!value) return "0.00";
  return parseFloat(value).toFixed(2);
};

const getInitials = (nombres, apellidos) => {
  const n = nombres ? nombres.charAt(0).toUpperCase() : '';
  const a = apellidos ? apellidos.charAt(0).toUpperCase() : '';
  return `${n}${a}`;
};

const getPhotoUrl = (emp) => {
  if (!emp || !emp.foto_path) return '';
  // Usar el timestamp inyectado en el fetch para romper el caché
  const timestamp = emp._t || Date.now();
  return `/concretos-oriente/Backend/${emp.foto_path}?t=${timestamp}`;
};

const deleteEmployee = async (id) => {
  const result = await Swal.fire({
    title: '¿Estás seguro?',
    text: "Esta acción no se puede deshacer y borrará los datos y la foto.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#f43f5e',
    cancelButtonColor: '#475569',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    background: '#0f172a',
    color: '#fff',
    customClass: {
      popup: 'border border-white/10 rounded-3xl shadow-2xl',
      confirmButton: 'rounded-xl px-6 py-3 font-bold',
      cancelButton: 'rounded-xl px-6 py-3 font-bold'
    }
  });

  if (!result.isConfirmed) {
    return;
  }
  
  try {
    const response = await fetch(`${BASE_URL}/personnel/${id}`, {
      method: 'DELETE'
    });
    
    const res = await response.json();
    if (res.status === 'success') {
      await fetchPersonnel(); // Refresh
      Swal.fire({
        title: '¡Eliminado!',
        text: 'El empleado ha sido eliminado correctamente.',
        icon: 'success',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
        customClass: {
          popup: 'border border-white/10 rounded-3xl shadow-2xl',
          confirmButton: 'rounded-xl px-6 py-3 font-bold'
        }
      });
    } else {
      Swal.fire({
        title: 'Error',
        text: res.message || 'Error al eliminar',
        icon: 'error',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
        customClass: {
          popup: 'border border-white/10 rounded-3xl shadow-2xl',
          confirmButton: 'rounded-xl px-6 py-3 font-bold'
        }
      });
    }
  } catch (error) {
    console.error("Error deleting personnel:", error);
    Swal.fire({
      title: 'Error',
      text: 'Error de conexión al servidor',
      icon: 'error',
      background: '#0f172a',
      color: '#fff',
      confirmButtonColor: '#6366f1',
      customClass: {
        popup: 'border border-white/10 rounded-3xl shadow-2xl',
        confirmButton: 'rounded-xl px-6 py-3 font-bold'
      }
    });
  }
};

const submitForm = async () => {
  isSubmitting.value = true;
  
  const data = new FormData();
  data.append('nombres', formData.value.nombres);
  data.append('apellidos', formData.value.apellidos);
  data.append('dpi', formData.value.dpi.replace(/\s/g, ''));
  data.append('nit', formData.value.nit);
  data.append('puesto', formData.value.puesto);
  data.append('telefono', formData.value.telefono);
  data.append('salario_base', formData.value.salario_base);
  data.append('pago_hora_extra', formData.value.pago_hora_extra);
  
  if (formData.value.foto) {
    data.append('foto', formData.value.foto);
  }

  try {
    const url = isEditing.value ? `${BASE_URL}/personnel/${editingId.value}` : `${BASE_URL}/personnel`;
    
    const response = await fetch(url, {
      method: 'POST', // Usamos POST para ambos (Multipart form data compatibility in PHP)
      body: data
    });
    
    const result = await response.json();
    if (result.status === 'success') {
      await fetchPersonnel(); // Refresh the list
      closeModal();
      Swal.fire({
        title: '¡Guardado!',
        text: 'Empleado guardado correctamente.',
        icon: 'success',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
        customClass: {
          popup: 'border border-white/10 rounded-3xl shadow-2xl',
          confirmButton: 'rounded-xl px-6 py-3 font-bold'
        }
      });
    } else {
      Swal.fire({
        title: 'Error',
        text: result.message || 'Error al guardar',
        icon: 'error',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
        customClass: {
          popup: 'border border-white/10 rounded-3xl shadow-2xl',
          confirmButton: 'rounded-xl px-6 py-3 font-bold'
        }
      });
    }
  } catch (error) {
    console.error("Error submitting personnel:", error);
    Swal.fire({
      title: 'Error',
      text: 'Error de conexión al servidor',
      icon: 'error',
      background: '#0f172a',
      color: '#fff',
      confirmButtonColor: '#6366f1',
      customClass: {
        popup: 'border border-white/10 rounded-3xl shadow-2xl',
        confirmButton: 'rounded-xl px-6 py-3 font-bold'
      }
    });
  } finally {
    isSubmitting.value = false;
  }
};
</script>
