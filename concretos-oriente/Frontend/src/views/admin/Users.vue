<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-8 relative">
    <!-- Header Principal -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 bg-secondary/30 p-8 rounded-3xl border border-white/5 backdrop-blur-sm">
      <div class="flex items-center gap-6">
        <div class="p-4 bg-primary/20 rounded-2xl border border-primary/30 shadow-[0_0_30px_-5px_rgba(79,70,229,0.3)]">
          <UserGroupIcon class="w-10 h-10 text-primary" />
        </div>
        <div>
          <h1 class="text-3xl font-black text-white tracking-tight">Gestión de Usuarios</h1>
          <p class="text-white/50 mt-1 font-medium flex items-center gap-2 text-sm uppercase tracking-widest">
            <ShieldCheckIcon class="w-4 h-4" /> Control de Accesos
          </p>
        </div>
      </div>
      <button @click="openModal()" class="group relative px-8 py-4 bg-primary text-white rounded-2xl font-black uppercase tracking-widest hover:-translate-y-3 hover:scale-105 hover:shadow-[0_20px_40px_-15px_rgba(99,102,241,0.5)] hover:shadow-[0_20px_40px_-15px_rgba(79,70,229,0.5)] transition-all duration-500 overflow-hidden flex items-center gap-3">
        <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-500"></div>
        <PlusIcon class="w-6 h-6 relative z-10" />
        <span class="relative z-10">Nuevo Usuario</span>
      </button>
    </div>

    <!-- Filtros y Búsqueda -->
    <div class="flex flex-col xl:flex-row gap-4 bg-secondary/20 p-4 rounded-3xl border border-white/5">
      <div class="flex-1 relative">
        <MagnifyingGlassIcon class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-white/40" />
        <input v-model="searchQuery" type="text" placeholder="Buscar por nombre o usuario..." class="w-full bg-black/40 border border-white/10 rounded-2xl pl-12 pr-5 py-3 text-white placeholder-white/20 focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold text-sm" />
      </div>
      
      <div class="flex flex-col md:flex-row gap-4 shrink-0">
        <div class="relative min-w-[200px]">
          <FunnelIcon class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-white/40 z-10" />
          <select v-model="filterRole" class="w-full bg-black/40 border border-white/10 rounded-2xl pl-10 pr-5 py-3 text-white focus:border-primary transition-all font-bold text-sm appearance-none relative">
            <option value="all">Todos los roles</option>
            <option value="admin">Administrador</option>
            <option value="supervisor">Supervisor</option>
            <option value="tecnico">Técnico</option>
          </select>
        </div>
        
        <div class="relative min-w-[200px]">
          <select v-model="filterStatus" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3 text-white focus:border-primary transition-all font-bold text-sm appearance-none">
            <option value="all">Todos los estados</option>
            <option value="Activo">Activos</option>
            <option value="Inactivo">Inactivos</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Lista de Usuarios -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary"></div>
    </div>
    
    <div v-else-if="filteredUsers.length === 0" class="text-center py-20 bg-secondary/30 rounded-3xl border border-white/5">
      <UserCircleIcon class="w-16 h-16 text-white/20 mx-auto mb-4" />
      <h3 class="text-xl font-bold text-white mb-2">No hay usuarios encontrados</h3>
      <p class="text-white/50">Prueba cambiando los filtros de búsqueda.</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div v-for="user in filteredUsers" :key="user.id" class="group bg-secondary/40 backdrop-blur-sm p-6 rounded-3xl border border-white/10 hover:border-primary/50 transition-all duration-500 hover:shadow-[0_0_40px_-15px_rgba(79,70,229,0.15)] flex flex-col h-full relative overflow-hidden">
        
        <!-- Estado Badge -->
        <div class="absolute top-6 right-6 z-10">
          <span :class="[
            'px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border backdrop-blur-md shadow-lg',
            user.estado === 'Activo' ? 'bg-green-500/20 text-green-400 border-green-500/30' : 'bg-red-500/20 text-red-400 border-red-500/30'
          ]">
            {{ user.estado }}
          </span>
        </div>

        <div class="flex items-center gap-5 mb-6 relative z-10">
          <div class="w-16 h-16 rounded-2xl bg-black/50 overflow-hidden border border-white/10 shrink-0 group-hover:border-primary/50 transition-colors">
            <img v-if="user.foto" :src="`/concretos-oriente/Backend/${user.foto}`" :alt="user.nombre" class="w-full h-full object-cover" />
            <div v-else class="w-full h-full flex items-center justify-center text-white/30">
              <UserCircleIcon class="w-10 h-10" />
            </div>
          </div>
          <div>
            <h3 class="text-lg font-bold text-white group-hover:text-primary transition-colors leading-tight">{{ user.nombre }}</h3>
            <p class="text-white/50 text-xs font-bold uppercase tracking-wider mt-1">{{ user.rol }}</p>
          </div>
        </div>

        <div class="bg-black/20 p-4 rounded-2xl border border-white/5 mb-6 relative z-10">
          <p class="text-white/40 text-[10px] font-black uppercase tracking-widest mb-1">Usuario de Acceso</p>
          <p class="text-white font-mono text-sm">{{ user.usuario }}</p>
        </div>

        <div class="mt-auto flex gap-3 pt-4 border-t border-white/5 relative z-10">
          <button @click="openModal(user)" class="flex-1 py-3 bg-white/5 hover:bg-primary/20 rounded-xl text-white text-xs font-bold uppercase tracking-widest transition-all border border-transparent hover:border-primary/30 flex items-center justify-center gap-2">
            <PencilSquareIcon class="w-4 h-4" /> Editar
          </button>
          <button @click="deleteUser(user.id)" class="px-4 py-3 bg-red-500/10 hover:bg-red-500/20 rounded-xl text-red-400 transition-all border border-transparent hover:border-red-500/30">
            <TrashIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Formulario -->
    <Transition
      enter-active-class="transition duration-500 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-500 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-6">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="closeModal"></div>
        
        <div class="relative bg-secondary w-full max-w-2xl rounded-3xl border border-white/10 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
          
          <div class="p-6 md:p-8 border-b border-white/5 flex justify-between items-center bg-black/20 shrink-0">
            <div>
              <h2 class="text-2xl font-black text-white tracking-tight">{{ isEditing ? 'Editar Usuario' : 'Añadir Nuevo Usuario' }}</h2>
              <p class="text-white/50 text-xs font-bold uppercase tracking-widest mt-2">
                {{ isEditing ? 'Modificar datos de acceso' : 'Registrar credenciales del sistema' }}
              </p>
            </div>
            <button @click="closeModal" class="p-2 bg-white/5 hover:bg-white/10 rounded-xl transition-all">
              <XMarkIcon class="w-6 h-6 text-white/50" />
            </button>
          </div>

          <div class="p-6 md:p-8 overflow-y-auto">
            <form @submit.prevent="submitForm" class="space-y-6">
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Nombre Completo</label>
                  <input v-model="formData.nombre" type="text" required class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold" placeholder="Ej. Juan Pérez" />
                </div>
                
                <div>
                  <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Nombre de Usuario</label>
                  <input v-model="formData.usuario" type="text" required class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold" placeholder="Ej. jperez" />
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Contraseña <span v-if="isEditing" class="text-primary/70">(Opcional)</span></label>
                  <input v-model="formData.password" type="password" :required="!isEditing" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold font-mono" placeholder="••••••••" />
                  <p v-if="isEditing" class="text-[9px] text-white/40 mt-2 uppercase tracking-wider">Dejar en blanco para mantener actual</p>
                </div>

                <div>
                  <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Rol en Sistema</label>
                  <select v-model="formData.rol" required class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold appearance-none">
                    <option value="admin">Administrador</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="tecnico">Técnico</option>
                  </select>
                </div>
              </div>

              <!-- Permisos section (solo para roles que no sean admin) -->
              <div v-if="formData.rol !== 'admin'" class="p-6 bg-black/20 rounded-2xl border border-white/5 space-y-4">
                <div class="flex justify-between items-start">
                  <div>
                    <h3 class="text-sm font-black text-white uppercase tracking-widest">Permisos de Acceso</h3>
                    <p class="text-[10px] text-white/40 mt-1 uppercase">Selecciona los módulos a los que este usuario tendrá acceso</p>
                  </div>
                  <button 
                    type="button" 
                    @click="toggleAllPermissions"
                    class="px-3 py-1.5 bg-primary/20 hover:bg-primary/40 text-primary border border-primary/30 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
                  >
                    {{ formData.permisos.length === availableModules.length ? 'Desmarcar Todos' : 'Acceso Completo' }}
                  </button>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
                  <label v-for="mod in availableModules" :key="mod.id" class="flex items-center gap-3 p-3 bg-white/5 rounded-xl border border-white/5 hover:border-primary/30 cursor-pointer transition-all group">
                    <div class="relative flex items-center justify-center w-5 h-5">
                      <input 
                        type="checkbox" 
                        :value="mod.id" 
                        v-model="formData.permisos"
                        class="peer appearance-none w-5 h-5 border-2 border-white/20 rounded bg-transparent checked:bg-primary checked:border-primary transition-all cursor-pointer"
                      />
                      <CheckIcon class="absolute w-3 h-3 text-white opacity-0 peer-checked:opacity-100 pointer-events-none transition-opacity" />
                    </div>
                    <span class="text-xs font-bold text-white/70 group-hover:text-white transition-colors">{{ mod.label }}</span>
                  </label>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Estado de la Cuenta</label>
                  <select v-model="formData.estado" required class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold appearance-none">
                    <option value="Activo">Activo</option>
                    <option value="Inactivo">Inactivo</option>
                  </select>
                </div>

                <div>
                  <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Foto de Perfil (Opcional)</label>
                  <div class="relative">
                    <input type="file" @change="handleFotoChange" accept="image/*" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3 text-white focus:border-primary transition-all font-bold text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-white/10 file:text-white hover:file:bg-white/20" />
                  </div>
                </div>
              </div>

            </form>
          </div>

          <div class="p-6 border-t border-white/5 bg-black/20 flex gap-4 shrink-0">
            <button type="button" @click="closeModal" class="flex-1 px-6 py-4 bg-white/5 hover:bg-white/10 rounded-2xl text-white font-black uppercase tracking-widest transition-all">
              Cancelar
            </button>
            <button type="button" @click="submitForm" :disabled="isSubmitting" class="flex-1 px-6 py-4 bg-primary hover:bg-primary/80 rounded-2xl text-white font-black uppercase tracking-widest transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
              <div v-if="isSubmitting" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
              <CheckIcon v-else class="w-6 h-6" />
              <span>{{ isSubmitting ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Guardar') }}</span>
            </button>
          </div>

        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import Swal from 'sweetalert2';
import { 
  UserGroupIcon, 
  ShieldCheckIcon,
  PlusIcon,
  PencilSquareIcon,
  TrashIcon,
  UserCircleIcon,
  CheckIcon,
  XMarkIcon,
  MagnifyingGlassIcon,
  FunnelIcon
} from '@heroicons/vue/24/outline';

const API_URL = '/concretos-oriente/Backend/api/v1';

const users = ref([]);
const loading = ref(true);
const showModal = ref(false);
const isSubmitting = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

// Variables de Filtro y Búsqueda
const searchQuery = ref('');
const filterRole = ref('all');
const filterStatus = ref('all');

const filteredUsers = computed(() => {
  return users.value.filter(user => {
    const matchesSearch = user.nombre.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                          user.usuario.toLowerCase().includes(searchQuery.value.toLowerCase());
    
    const matchesRole = filterRole.value === 'all' || user.rol === filterRole.value;
    const matchesStatus = filterStatus.value === 'all' || user.estado === filterStatus.value;

    return matchesSearch && matchesRole && matchesStatus;
  });
});

const formData = ref({
  nombre: '',
  usuario: '',
  password: '',
  rol: 'admin',
  estado: 'Activo',
  foto: null,
  permisos: []
});

const availableModules = [
  { id: "personnel", label: "Personal" },
  { id: "vehicles", label: "Vehículos" },
  { id: "machinery", label: "Maquinaria" },
  { id: "tech-machinery", label: "Estado Maquinaria (Técnico)" },
  { id: "projects", label: "Proyectos" },
  { id: "clients", label: "Clientes" },
  { id: "tech-projects", label: "Mis Proyectos (Técnico)" },
  { id: "inventory", label: "Inventario" },
  { id: "suppliers", label: "Proveedores" },
  { id: "project-incomes", label: "Ingresos por Proyectos" },
  { id: "finance", label: "Ingresos y Egresos" },
  { id: "recurrents", label: "Recurrentes" },
  { id: "users", label: "Usuarios" },
  { id: "bank-conciliation", label: "Bancos" },
  { id: "bitacora-mantenimiento", label: "Bitácoras y Mantenimiento" },
  { id: "budgets-estimations", label: "Presupuestos y Estimaciones" },
  { id: "credits-accounts-payable", label: "Créditos y Cuentas por Pagar" },
  { id: "digital-documents", label: "Documentos Digitales" },
  { id: "notifications-alerts", label: "Notificaciones y Alertas" },
  { id: "payroll-expenses", label: "Planilla y Gastos" },
];

const toggleAllPermissions = () => {
  if (formData.value.permisos.length === availableModules.length) {
    formData.value.permisos = [];
  } else {
    formData.value.permisos = availableModules.map(m => m.id);
  }
};

onMounted(() => {
  fetchUsers();
});

const fetchUsers = async () => {
  loading.value = true;
  try {
    const response = await fetch(`${API_URL}/users`);
    const data = await response.json();
    if (data.status === 'success') {
      users.value = data.data;
    } else {
      throw new Error(data.message || 'Error al obtener usuarios');
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.message,
      background: '#1a1b23',
      color: '#fff',
      confirmButtonColor: '#4f46e5'
    });
  } finally {
    loading.value = false;
  }
};

const handleFotoChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    formData.value.foto = file;
  }
};

const openModal = (user = null) => {
  if (user) {
    isEditing.value = true;
    editingId.value = user.id;
    
    let parsedPermisos = [];
    if (user.permisos) {
       parsedPermisos = typeof user.permisos === 'string' ? JSON.parse(user.permisos) : user.permisos;
    }

    formData.value = {
      nombre: user.nombre,
      usuario: user.usuario,
      password: '', // No mostrar contraseña anterior
      rol: user.rol,
      estado: user.estado,
      foto: null,
      permisos: parsedPermisos || []
    };
  } else {
    isEditing.value = false;
    editingId.value = null;
    formData.value = {
      nombre: '',
      usuario: '',
      password: '',
      rol: 'admin',
      estado: 'Activo',
      foto: null,
      permisos: []
    };
  }
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const submitForm = async () => {
  if (!formData.value.nombre || !formData.value.usuario || (!isEditing.value && !formData.value.password)) {
    Swal.fire({
      icon: 'warning',
      title: 'Campos requeridos',
      text: 'Por favor completa los campos obligatorios.',
      background: '#1a1b23',
      color: '#fff',
      confirmButtonColor: '#4f46e5'
    });
    return;
  }

  isSubmitting.value = true;
  
  const data = new FormData();
  data.append('nombre', formData.value.nombre);
  data.append('usuario', formData.value.usuario);
  if (formData.value.password) {
    data.append('password', formData.value.password);
  }
  data.append('rol', formData.value.rol);
  data.append('estado', formData.value.estado);
  
  if (formData.value.rol !== 'admin') {
    data.append('permisos', JSON.stringify(formData.value.permisos));
  } else {
    // Si es admin, puede tener acceso a todo y la DB puede estar nula
    data.append('permisos', JSON.stringify([])); 
  }
  
  if (formData.value.foto) {
    data.append('foto', formData.value.foto);
  }

  try {
    const url = isEditing.value 
      ? `${API_URL}/users/${editingId.value}` 
      : `${API_URL}/users`;
      
    const response = await fetch(url, {
      method: 'POST', // Usamos POST para soportar FormData
      body: data
    });
    
    const result = await response.json();
    
    if (result.status === 'success') {
      await fetchUsers();
      closeModal();
      
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        background: '#1a1b23',
        color: '#fff'
      });
      Toast.fire({
        icon: 'success',
        title: result.message
      });
    } else {
      throw new Error(result.message || 'Error al guardar');
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.message,
      background: '#1a1b23',
      color: '#fff',
      confirmButtonColor: '#4f46e5'
    });
  } finally {
    isSubmitting.value = false;
  }
};

const deleteUser = (id) => {
  Swal.fire({
    title: '¿Estás seguro?',
    text: "El usuario será eliminado permanentemente y no podrá acceder al sistema.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#374151',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    background: '#1a1b23',
    color: '#fff'
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        const response = await fetch(`${API_URL}/users/${id}`, {
          method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
          await fetchUsers();
          Swal.fire({
            icon: 'success',
            title: 'Eliminado',
            text: 'El usuario ha sido eliminado.',
            background: '#1a1b23',
            color: '#fff',
            confirmButtonColor: '#4f46e5'
          });
        } else {
          throw new Error(data.message || 'Error al eliminar');
        }
      } catch (error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.message,
          background: '#1a1b23',
          color: '#fff',
          confirmButtonColor: '#4f46e5'
        });
      }
    }
  });
};
</script>
