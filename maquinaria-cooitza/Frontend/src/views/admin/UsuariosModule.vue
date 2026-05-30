<template>
  <transition name="fade-up" appear>
    <div class="flex flex-col gap-6 w-full font-sans pb-12">
      
      <!-- Title block with Cooitzá theme -->
      <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 border-b border-[#cbd5e1] gap-4">
        <div class="border-l-4 border-[#0054A3] pl-3">
          <span class="font-display text-[10px] font-black text-[#0054A3] tracking-widest uppercase">
            Protocolo de Eficiencia Q4
          </span>
          <h2 class="font-display text-3xl font-black text-[#191c1d] uppercase mt-0.5">
            Gestión de Usuarios
          </h2>
          <p class="text-xs text-slate-500 font-medium mt-1">
            Gestión y concesión de credenciales para el personal técnico e industrial de la estación central.
          </p>
        </div>

        <button 
          @click="handleOpenCreateForm"
          class="bg-[#0054A3] hover:bg-[#004586] text-white font-display text-xs font-black tracking-wider px-5 py-3 rounded-none uppercase flex items-center justify-center gap-2 self-start md:self-center cursor-pointer transition-all active:scale-[0.98] shadow-sm select-none"
        >
          <Plus :size="16" class="text-[#FFD200]" />
          <span>Nuevo Usuario</span>
        </button>
      </div>

      <!-- Bento Grid layout stats -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 select-none">
        
        <!-- Dynamic Card 1 -->
        <div class="bg-white border border-[#cbd5e1] p-6 flex flex-col justify-between relative overflow-hidden group shadow-sm">
          <div class="absolute top-0 left-0 w-1.5 h-full bg-[#0054A3]"></div>
          <div>
            <span class="font-display text-[10px] font-black text-slate-600 uppercase tracking-wider">
              Total base de datos
            </span>
            <div class="font-display text-4xl font-black mt-2 text-[#191c1d]">
              {{ totalGlobalDatabaseCount.toLocaleString() }}
            </div>
          </div>
          <div class="mt-4 flex items-center gap-1.5 text-xs font-bold text-slate-500">
            <TrendingUp class="text-[#0054A3]" :size="16" />
            <span>+12% este mes vs histórico</span>
          </div>
        </div>

        <!-- Dynamic Card 2 -->
        <div class="bg-white border border-[#cbd5e1] p-6 flex flex-col justify-between relative overflow-hidden group shadow-sm">
          <div class="absolute top-0 left-0 w-1.5 h-full bg-[#FFD200]"></div>
          <div>
            <span class="font-display text-[10px] font-black text-slate-600 uppercase tracking-wider">
              Administradores Generales
            </span>
            <div class="font-display text-4xl font-black mt-2 text-[#0054A3]">
              {{ totalGlobalAdminsCount }}
            </div>
          </div>
          <p class="mt-4 text-xs font-mono font-bold text-neutral-400">
            System Core Team & Log Audit
          </p>
        </div>

        <!-- Dynamic Card 3 -->
        <div class="bg-white border border-[#cbd5e1] p-6 flex flex-col justify-between relative overflow-hidden group shadow-sm">
          <div class="absolute top-0 left-0 w-1.5 h-full bg-[#10b981]"></div>
          <div>
            <span class="font-display text-[10px] font-black text-slate-600 uppercase tracking-wider">
              Técnicos Autorizados
            </span>
            <div class="font-display text-4xl font-black mt-2 text-emerald-600">
              {{ totalFieldOperatorsCount.toLocaleString() }}
            </div>
          </div>
          <p class="mt-4 text-xs font-mono font-bold text-neutral-400">
            Operaciones Activas en Cantera
          </p>
        </div>

      </div>

      <!-- Main Directory Table section -->
      <div class="bg-white border border-[#cbd5e1] overflow-hidden shadow-sm flex flex-col">
        
        <!-- Table Filter Topbar Controls -->
        <div class="px-6 py-4 bg-slate-50 border-b border-[#cbd5e1] flex flex-col lg:flex-row lg:items-center justify-between gap-4 select-none">
          <span class="font-display text-xs font-black uppercase tracking-widest text-[#0054A3]">
            Directorio del Personal Técnico
          </span>
          
          <div class="flex flex-wrap items-center gap-3">
            <!-- Search option -->
            <div class="relative min-w-[240px] flex-1 lg:flex-none">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                <Search :size="15" />
              </span>
              <input 
                type="text"
                placeholder="Buscar por nombre, ID o email..."
                v-model="searchQuery"
                @input="currentPage = 1"
                class="bg-white border border-[#cbd5e1] pl-9 pr-4 py-2 text-xs outline-none focus:border-[#0054A3] transition-all w-full placeholder:text-slate-400 text-slate-800"
              />
            </div>

            <!-- Role filter -->
            <select
              v-model="roleFilter"
              @change="currentPage = 1"
              class="bg-white border border-[#cbd5e1] px-3 py-2 text-xs outline-none focus:border-[#0054A3] cursor-pointer text-slate-800"
            >
              <option value="all">Todos los Roles</option>
              <option value="admin">Administrador</option>
              <option value="tecnico_dashboard">Panel Técnico</option>
              <option value="tecnico_piloto">Técnico Piloto</option>
              <option value="tecnico">Técnico Común</option>
            </select>

            <!-- Status filter -->
            <select
              v-model="statusFilter"
              @change="currentPage = 1"
              class="bg-white border border-[#cbd5e1] px-3 py-2 text-xs outline-none focus:border-[#0054A3] cursor-pointer text-slate-800"
            >
              <option value="all">Cualquier Estado</option>
              <option value="Active">Activo</option>
              <option value="Inactive">Inactivo</option>
            </select>

            <button 
              @click="handleExportData"
              class="bg-white border border-[#cbd5e1] hover:bg-slate-50 text-slate-700 font-display text-[10px] font-bold uppercase tracking-wider px-4 py-2.5 flex items-center gap-1.5 cursor-pointer select-none"
              title="Exportar base de datos a CSV"
            >
              <Download :size="14" class="text-[#0054A3]" />
              <span>Exportar</span>
            </button>
          </div>
        </div>

        <!-- Database table view -->
        <div class="overflow-x-auto">
          <table class="w-full border-collapse text-left">
            <thead>
              <tr class="border-b border-[#cbd5e1] bg-slate-50 select-none">
                <th class="p-4 font-display text-[10px] font-bold uppercase tracking-wider text-slate-600">Nombre de Operador</th>
                <th class="p-4 font-display text-[10px] font-bold uppercase tracking-wider text-slate-600">Rol Asignado</th>
                <th class="p-4 font-display text-[10px] font-bold uppercase tracking-wider text-slate-600">Último Acceso</th>
                <th class="p-4 font-display text-[10px] font-bold uppercase tracking-wider text-slate-600">Estado</th>
                <th class="p-4 font-display text-[10px] font-bold uppercase tracking-wider text-slate-600 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#cbd5e1]">
              <tr v-if="currentItems.length === 0">
                <td colspan="7" class="p-12 text-center text-slate-400 font-medium italic">
                  Sin resultados coincidentes con los filtros seleccionados.
                </td>
              </tr>
              <tr 
                v-else 
                v-for="u in currentItems" 
                :key="u.id" 
                class="hover:bg-slate-50/50 transition-all group"
              >
                <td class="p-4 flex items-center gap-3">
                  <div class="w-9 h-9 rounded-full bg-[#0054A3]/10 text-[#0054A3] flex items-center justify-center font-display text-xs font-black">
                    {{ getInitials(u.fullName) }}
                  </div>
                  <div>
                    <span class="font-sans text-[#191c1d] font-bold hover:text-[#0054A3] transition-colors">{{ u.fullName }}</span>
                    <span class="block text-[10px] font-mono text-[#004586] tracking-tight">@{{ u.username }}</span>
                  </div>
                </td>
                <td class="p-4 text-xs font-medium" v-html="renderRoleBadge(u.role)"></td>
                <td class="p-4 text-xs font-mono font-medium text-[#004586]">{{ u.lastAccess || "No disponible" }}</td>
                <td class="p-4">
                  <button
                    @click="handleToggleStatus(u.id, u.status, u.fullName)"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase border cursor-pointer select-none"
                    :class="u.status === 'Active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full" :class="u.status === 'Active' ? 'bg-emerald-500 animate-pulse' : 'bg-red-500'"></span>
                    <span>{{ u.status === "Active" ? "Activo" : "Inactivo" }}</span>
                  </button>
                </td>
                <td class="p-4 text-right">
                  <div class="flex justify-end gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                    <button 
                      @click="handleOpenEditForm(u)"
                      class="p-1.5 text-[#0054A3] hover:bg-[#0054A3]/10 border border-[#cbd5e1] bg-white transition-all cursor-pointer"
                      title="Editar Autorización"
                    >
                      <Edit2 :size="13" />
                    </button>
                    <button 
                      @click="handleToggleStatus(u.id, u.status, u.fullName)"
                      class="p-1.5 border border-[#cbd5e1] bg-white transition-all cursor-pointer"
                      :class="u.status === 'Active' ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50'"
                      :title="u.status === 'Active' ? 'Suspender acceso' : 'Re-habilitar acceso'"
                    >
                      <UserX :size="13" />
                    </button>
                    <button 
                      @click="handleDeleteUser(u.id, u.fullName)"
                      class="p-1.5 text-red-600 hover:bg-red-50 border border-[#cbd5e1] bg-white transition-all cursor-pointer"
                      title="Eliminar permanentemente"
                    >
                      <Trash2 :size="13" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination bar matches Cooitzá style -->
        <div class="px-6 py-4 bg-slate-50 border-t border-[#cbd5e1] flex flex-col sm:flex-row justify-between items-center gap-3 select-none">
          <span class="font-display text-[10px] font-bold uppercase tracking-wider text-slate-500">
            Mostrando {{ indexOfFirstItem + 1 }}-{{ Math.min(indexOfLastItem, totalItems) }} de {{ totalItems }} usuarios ({{ totalGlobalDatabaseCount }} registros totales en red)
          </span>

          <div class="flex gap-1.5">
            <button 
              :disabled="currentPage === 1"
              @click="currentPage = Math.max(currentPage - 1, 1)"
              class="w-8 h-8 flex items-center justify-center border border-[#cbd5e1] bg-white hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer transition-colors text-slate-800"
            >
              <ChevronLeft :size="16" />
            </button>
            
            <button
              v-for="pageNum in totalPages"
              :key="pageNum"
              @click="currentPage = pageNum"
              class="w-8 h-8 flex items-center justify-center border font-display text-xs font-black transition-colors"
              :class="currentPage === pageNum ? 'bg-[#0054A3] text-white border-[#0054A3]' : 'border-[#cbd5e1] bg-white hover:bg-slate-50 cursor-pointer text-slate-800'"
            >
              {{ pageNum }}
            </button>

            <button 
              :disabled="currentPage === totalPages"
              @click="currentPage = Math.min(currentPage + 1, totalPages)"
              class="w-8 h-8 flex items-center justify-center border border-[#cbd5e1] bg-white hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer transition-colors text-slate-800"
            >
              <ChevronRight :size="16" />
            </button>
          </div>
        </div>

      </div>

      <!-- Slide-over Overlay Dialog for Adding/Editing credentials -->
      <transition name="fade">
        <div v-if="isFormOpen" class="fixed inset-0 bg-slate-900/40 z-50 flex items-center justify-center p-4">
          
          <transition name="scale" appear>
            <div class="bg-white border-2 border-[#cbd5e1] w-full max-w-lg shadow-2xl relative flex flex-col overflow-hidden">
              
              <!-- Gold Indicator Head accent -->
              <div class="w-full h-1 bg-[#FFD200]"></div>

              <!-- Header Box -->
              <div class="flex justify-between items-center bg-slate-50 px-6 py-4 border-b border-[#cbd5e1]">
                <div class="flex items-center gap-2">
                  <ShieldCheck class="text-[#0054A3]" :size="18" />
                  <span class="font-display text-xs font-black text-[#0054A3] uppercase tracking-wider">
                    {{ editUserId ? "Modificar Autorización Cooitzá" : "Conceder Nueva Autorización" }}
                  </span>
                </div>
                <button 
                  @click="isFormOpen = false"
                  class="p-1 hover:bg-slate-200 text-slate-500 transition-colors cursor-pointer"
                >
                  <X :size="18" />
                </button>
              </div>

              <!-- Form container -->
              <form @submit.prevent="handleSaveUserSubmit" class="p-6 space-y-4">
                
                <!-- Full name description field -->
                <div class="flex flex-col gap-1.5">
                  <label class="text-slate-600 text-[10px] font-bold uppercase tracking-wider">
                    Nombre Completo del Operador
                  </label>
                  <input 
                    type="text"
                    required
                    placeholder="Ej: Alejandro Rivera de León"
                    v-model="fullName"
                    class="w-full bg-slate-50 border border-[#cbd5e1] p-2.5 font-sans text-xs focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] focus:bg-white outline-none active:bg-white text-slate-800"
                  />
                </div>

                <!-- Grid wrap for dynamic elements -->
                <div class="grid grid-cols-1 gap-4">
                  <!-- Username -->
                  <div class="flex flex-col gap-1.5">
                    <label class="text-slate-600 text-[10px] font-bold uppercase tracking-wider">
                      Usuario (ID Log-in)
                    </label>
                    <input 
                      type="text"
                      required
                      placeholder="Ej: ariveral"
                      v-model="username"
                      class="w-full bg-slate-50 border border-[#cbd5e1] p-2.5 font-sans text-xs focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] focus:bg-white outline-none text-slate-800"
                    />
                  </div>
                </div>

                <!-- Role select matching the system's exact internal logins -->
                <div class="flex flex-col gap-1.5">
                  <label class="text-slate-600 text-[10px] font-bold uppercase tracking-wider">
                    Rol Operativo & de Control
                  </label>
                  <select
                    v-model="role"
                    class="w-full bg-slate-50 border border-[#cbd5e1] p-2.5 font-sans text-xs focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] focus:bg-white outline-none cursor-pointer text-slate-800"
                  >
                    <option value="tecnico_dashboard">Técnico Horómetros (Válido para enviar lecturas)</option>
                    <option value="tecnico_piloto">Técnico Piloto (Formulario de Piloto)</option>
                    <option value="tecnico">Técnico Estándar</option>
                    <option value="admin">Administrador General de Sistemas Cooitzá</option>
                  </select>
                </div>

                <!-- Access validation status -->
                <div class="flex flex-col gap-1.5">
                  <label class="text-slate-600 text-[10px] font-bold uppercase tracking-wider">
                    Estado de Permiso Inicial
                  </label>
                  <select
                    v-model="status"
                    class="w-full bg-slate-50 border border-[#cbd5e1] p-2.5 font-sans text-xs focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] focus:bg-white outline-none cursor-pointer text-slate-800"
                  >
                    <option value="Active">Permitido (Autorización Concedida)</option>
                    <option value="Inactive">Suspendido (Remover Permisos de Acceso)</option>
                  </select>
                </div>

                <!-- Modal footer CTA -->
                <div class="pt-4 flex items-center justify-end gap-3 border-t border-[#cbd5e1]/60 mt-6 font-display">
                  <button 
                    type="button"
                    @click="isFormOpen = false"
                    class="px-4 py-2 text-xs font-black uppercase text-slate-500 hover:bg-slate-100 transition-colors cursor-pointer"
                  >
                    Cancelar
                  </button>

                  <button 
                    type="submit"
                    class="bg-[#0054A3] hover:bg-[#004586] text-white px-5 py-2.5 text-xs font-black uppercase tracking-wider flex items-center gap-1.5 cursor-pointer shadow-sm active:scale-[0.98]"
                  >
                    <Check :size="14" class="text-[#FFD200]" />
                    <span>{{ editUserId ? "Actualizar Permisos" : "Conceder Autorización" }}</span>
                  </button>
                </div>

              </form>

            </div>
          </transition>

        </div>
      </transition>

    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { 
  Users, Shield, Wrench, TrendingUp, Search, Plus, Filter, Download, 
  ChevronLeft, ChevronRight, Lock, History, Edit2, Trash2, UserX, X, Check, ShieldCheck, Info 
} from "lucide-vue-next";
import Swal from 'sweetalert2';

interface UserConfig {
  id: string;
  fullName: string;
  username: string;
  role: "admin" | "tecnico" | "tecnico_dashboard" | "tecnico_piloto";
  status: "Active" | "Inactive";
  lastAccess: string;
}

const emit = defineEmits<{
  (e: 'usersListChange', count: number): void
}>();

const usersList = ref<UserConfig[]>([]);
const searchQuery = ref("");
const roleFilter = ref<string>("all");
const statusFilter = ref<string>("all");

const currentPage = ref(1);
const itemsPerPage = 6;

const isFormOpen = ref(false);
const editUserId = ref<string | null>(null);

const fullName = ref("");
const username = ref("");
const role = ref<"admin" | "tecnico" | "tecnico_dashboard" | "tecnico_piloto">("tecnico");
const status = ref<"Active" | "Inactive">("Active");

const Toast = Swal.mixin({
  toast: true,
  position: 'bottom-start',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  background: '#191c1d',
  color: '#ffffff',
  iconColor: '#FFD200',
});

const triggerSync = (count: number) => {
  emit('usersListChange', count);
};

const loadUsers = async () => {
  try {
    const res = await fetch('/maquinaria-cooitza/Backend/api/v1/usuarios');
    const json = await res.json();
    if (json.status === 'success') {
      usersList.value = json.data.map((u: any) => ({
        id: u.id.toString(),
        fullName: u.full_name,
        username: u.username,
        role: u.role,
        status: u.status,
        lastAccess: u.last_access || "Sin accesos"
      }));
      triggerSync(usersList.value.length);
    }
  } catch (error) {
    console.error("Error cargando usuarios:", error);
  }
};

onMounted(() => {
  loadUsers();
});

const handleOpenCreateForm = () => {
  editUserId.value = null;
  fullName.value = "";
  username.value = "";
  role.value = "tecnico";
  status.value = "Active";
  isFormOpen.value = true;
};

const handleOpenEditForm = (u: UserConfig) => {
  editUserId.value = u.id;
  fullName.value = u.fullName;
  username.value = u.username;
  role.value = u.role;
  status.value = u.status;
  isFormOpen.value = true;
};

const handleSaveUserSubmit = async () => {
  if (!fullName.value.trim() || !username.value.trim()) return;

  const payload = {
    id: editUserId.value,
    full_name: fullName.value.trim(),
    username: username.value.toLowerCase().trim(),
    role: role.value,
    status: status.value
  };

  try {
    let url = '/maquinaria-cooitza/Backend/api/v1/usuarios';
    if (editUserId.value) {
      url = '/maquinaria-cooitza/Backend/api/v1/usuarios/update';
    }

    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    if (res.ok) {
      await loadUsers();
      isFormOpen.value = false;
      Toast.fire({
        icon: 'success',
        title: editUserId.value ? `Usuario "${payload.full_name}" actualizado.` : `Usuario "${payload.full_name}" registrado con clave por defecto "123".`
      });
    } else {
      Swal.fire('Error', 'Error al guardar el usuario', 'error');
    }
  } catch (e) {
    console.error(e);
    Swal.fire('Error', 'Problema de conexión con el servidor', 'error');
  }
};

const handleDeleteUser = async (id: string, name: string) => {
  const result = await Swal.fire({
    title: '¿Revocar acceso?',
    text: `¿Está seguro de revocar permanentemente la autorización para "${name}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ba1a1a',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, revocar'
  });

  if (result.isConfirmed) {
    try {
      const res = await fetch('/maquinaria-cooitza/Backend/api/v1/usuarios/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      if (res.ok) {
        await loadUsers();
        Toast.fire({ icon: 'success', title: `Autorización de "${name}" revocada.` });
      }
    } catch (e) {
      console.error(e);
    }
  }
};

const handleToggleStatus = async (id: string, currentStatus: "Active" | "Inactive", name: string) => {
  const nextStatus = currentStatus === "Active" ? "Inactive" : "Active";
  
  try {
    const res = await fetch('/maquinaria-cooitza/Backend/api/v1/usuarios/status', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, status: nextStatus })
    });
    if (res.ok) {
      await loadUsers();
      Toast.fire({ icon: 'success', title: `Estado de "${name}" cambiado.` });
    }
  } catch (e) {
    console.error(e);
  }
};

const handleExportData = () => {
  Toast.fire({ icon: 'info', title: "Exportando informe de personal a CSV..." });
  const headers = "ID,Nombre,Rol,Estado,UltimoAcceso\n";
  const rows = usersList.value.map(u => `${u.id},${u.fullName},${u.role},${u.status},${u.lastAccess}`).join("\n");
  const blob = new Blob([headers + rows], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.setAttribute('href', url);
  a.setAttribute('download', `Cooitza_Usuarios_Report.csv`);
  a.click();
};

const filteredUsers = computed(() => {
  const query = searchQuery.value.toLowerCase();
  return usersList.value.filter(u => {
    const matchesKeyword = 
      u.fullName.toLowerCase().includes(query) || 
      u.username.toLowerCase().includes(query);

    const matchesRole = roleFilter.value === "all" || u.role === roleFilter.value;
    const matchesStatus = statusFilter.value === "all" || u.status === statusFilter.value;

    return matchesKeyword && matchesRole && matchesStatus;
  });
});

const totalItems = computed(() => filteredUsers.value.length);
const totalPages = computed(() => Math.ceil(totalItems.value / itemsPerPage) || 1);
const indexOfLastItem = computed(() => currentPage.value * itemsPerPage);
const indexOfFirstItem = computed(() => indexOfLastItem.value - itemsPerPage);
const currentItems = computed(() => filteredUsers.value.slice(indexOfFirstItem.value, indexOfLastItem.value));

const totalGlobalDatabaseCount = computed(() => 1284 + (usersList.value.length - 4));
const adminsCount = computed(() => usersList.value.filter(u => u.role === "admin").length);
const totalGlobalAdminsCount = computed(() => 39 + adminsCount.value);
const totalFieldOperatorsCount = computed(() => totalGlobalDatabaseCount.value - totalGlobalAdminsCount.value);

const renderRoleBadge = (roleStr: string) => {
  switch (roleStr) {
    case "admin":
      return '<span class="inline-block px-2 py-0.5 bg-red-100 text-red-800 text-[10px] font-bold uppercase rounded-sm border border-red-200">Admin</span>';
    case "tecnico_dashboard":
      return '<span class="inline-block px-2 py-0.5 bg-indigo-100 text-[#0054A3] text-[10px] font-bold uppercase rounded-sm border border-indigo-200">Panel Técnico</span>';
    case "tecnico_piloto":
      return '<span class="inline-block px-2 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-bold uppercase rounded-sm border border-amber-200">Técnico Piloto</span>';
    default:
      return '<span class="inline-block px-2 py-0.5 bg-slate-100 text-slate-700 text-[10px] font-bold uppercase rounded-sm border border-slate-200">Técnico</span>';
  }
};

const getInitials = (nameStr: string) => {
  return nameStr.split(" ").map(n => n[0]).join("").substring(0, 2).toUpperCase();
};
</script>

<style scoped>
.fade-up-enter-active,
.fade-up-leave-active {
  transition: opacity 0.4s ease, transform 0.4s ease;
}
.fade-up-enter-from,
.fade-up-leave-to {
  opacity: 0;
  transform: translateY(15px);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.scale-enter-active,
.scale-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.scale-enter-from,
.scale-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(15px);
}

.toast-enter-active,
.toast-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(30px) scale(0.95);
}
</style>
