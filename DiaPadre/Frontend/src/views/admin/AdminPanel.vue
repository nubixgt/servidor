<template>
  <div class="min-h-screen bg-[#f0f2f7] text-gray-800 flex flex-col font-sans selection:bg-maga-light-blue selection:text-white">

    <!-- Toast -->
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="-translate-y-10 opacity-0 scale-90"
      enter-to-class="translate-y-0 opacity-100 scale-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="translate-y-0 opacity-100 scale-100"
      leave-to-class="-translate-y-8 opacity-0 scale-95"
    >
      <div v-if="toastMessage" class="fixed top-4 left-1/2 -translate-x-1/2 z-50 bg-maga-blue text-white py-3 px-6 rounded-2xl shadow-2xl flex items-center gap-3 border border-white/10 max-w-md w-[90%] md:w-auto backdrop-blur-sm">
        <Sparkles class="w-4 h-4 text-amber-300 flex-shrink-0 animate-pulse" />
        <span class="text-sm font-medium">{{ toastMessage }}</span>
      </div>
    </Transition>

    <!-- Top bar -->
    <header class="bg-maga-blue border-b border-white/10 px-6 py-3.5">
      <div class="max-w-7xl mx-auto flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center">
            <Database class="w-4 h-4 text-white" />
          </div>
          <div>
            <p class="text-white font-bold text-sm leading-none">Panel de Administración</p>
            <p class="text-white/50 text-[11px] mt-0.5">MAGA · Día del Padre 2026</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span class="flex h-2 w-2 relative">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
          </span>
          <span class="text-white/60 text-xs font-medium">En línea</span>
        </div>
      </div>
    </header>

    <div class="flex-grow max-w-7xl mx-auto w-full px-4 md:px-6 py-8">

      <!-- Page heading + actions -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-5 mb-8">
        <div>
          <p class="text-xs font-bold text-maga-light-blue uppercase tracking-widest mb-1">Gestión de participantes</p>
          <h1 class="text-2xl md:text-3xl font-black text-maga-blue">Registros Día del Padre</h1>
          <p class="text-gray-500 text-sm mt-1">Direcciones de VISAR y Despacho Superior</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <button
            @click="handleExportCSV"
            class="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2 px-4 rounded-xl text-sm transition-all shadow-md cursor-pointer"
          >
            <FileSpreadsheet class="w-3.5 h-3.5" />
            Exportar CSV
          </button>
        </div>
      </div>

      <!-- Stat cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">

        <div class="relative overflow-hidden bg-maga-blue text-white rounded-2xl p-6 shadow-lg">
          <div class="absolute -top-4 -right-4 w-24 h-24 bg-white/5 rounded-full"></div>
          <div class="absolute -bottom-6 -right-2 w-32 h-32 bg-white/5 rounded-full"></div>
          <p class="text-white/60 text-xs font-bold uppercase tracking-widest">Total registrados</p>
          <p class="text-5xl font-black mt-2 leading-none">{{ statsTotal }}</p>
          <p class="text-white/50 text-xs mt-2">padres en el sistema</p>
          <div class="absolute top-5 right-6 opacity-20">
            <User class="w-10 h-10" />
          </div>
        </div>

        <div class="relative overflow-hidden bg-white rounded-2xl p-6 shadow-sm border border-gray-100 sm:col-span-2">
          <div class="absolute top-0 right-0 w-1 h-full bg-amber-400 rounded-r-2xl"></div>
          <p class="text-gray-400 text-xs font-bold uppercase tracking-widest">Mayor participación</p>
          <p class="text-2xl font-extrabold text-maga-blue mt-2 leading-tight truncate">{{ topDirection }}</p>
          <p class="text-gray-400 text-xs mt-1.5">dirección con más inscritos</p>
          <div class="mt-3 inline-flex items-center gap-1.5 bg-amber-50 text-amber-600 text-[11px] font-bold px-2.5 py-1 rounded-full border border-amber-200/50">
            <Grid class="w-3 h-3" />
            Dirección líder
          </div>
        </div>

      </div>

      <!-- Search & filters -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-5">
        <div class="flex flex-col md:flex-row gap-3 items-center">
          <div class="relative flex-1 w-full">
            <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input
              type="text"
              placeholder="Buscar por nombre, teléfono o correo..."
              v-model="searchTerm"
              class="w-full pl-10 pr-9 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm outline-none focus:bg-white focus:border-maga-blue focus:ring-2 focus:ring-maga-blue/10 transition-all"
            />
            <button v-if="searchTerm" @click="searchTerm = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
              <X class="w-3.5 h-3.5" />
            </button>
          </div>

          <select
            v-model="adminDirFilter"
            class="w-full md:w-52 py-2.5 px-3 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold outline-none focus:bg-white focus:border-maga-blue transition-all cursor-pointer"
          >
            <option value="">Todas las direcciones</option>
            <option v-for="dir in DIRECTIONS" :key="dir" :value="dir">{{ dir }}</option>
          </select>

          <select
            v-model="adminDeptFilter"
            class="w-full md:w-52 py-2.5 px-3 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold outline-none focus:bg-white focus:border-maga-blue transition-all cursor-pointer"
          >
            <option value="">Todos los departamentos</option>
            <option v-for="dept in uniqueDepartments" :key="dept" :value="dept">{{ dept }}</option>
          </select>

          <button
            v-if="searchTerm || adminDirFilter || adminDeptFilter"
            @click="clearFilters"
            class="flex-shrink-0 inline-flex items-center gap-1.5 text-xs font-bold text-rose-500 bg-rose-50 hover:bg-rose-100 py-2.5 px-4 rounded-xl transition-colors border border-rose-100 cursor-pointer"
          >
            <X class="w-3 h-3" />
            Limpiar
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="border-b border-gray-100">
                <th class="py-3.5 px-5 text-[11px] font-bold text-gray-400 uppercase tracking-widest">Participante</th>
                <th class="py-3.5 px-5 text-[11px] font-bold text-gray-400 uppercase tracking-widest">Contacto</th>
                <th class="py-3.5 px-5 text-[11px] font-bold text-gray-400 uppercase tracking-widest">Dirección</th>
                <th class="py-3.5 px-5 text-[11px] font-bold text-gray-400 uppercase tracking-widest">Departamento</th>
                <th class="py-3.5 px-5 text-[11px] font-bold text-gray-400 uppercase tracking-widest">Fecha</th>
                <th class="py-3.5 px-5 text-[11px] font-bold text-gray-400 uppercase tracking-widest text-center">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <template v-if="filteredRegistrations.length > 0">
                <tr
                  v-for="reg in filteredRegistrations"
                  :key="reg.id"
                  class="hover:bg-blue-50/40 transition-colors group"
                >
                  <!-- Name + avatar -->
                  <td class="py-4 px-5">
                    <div class="flex items-center gap-3">
                      <div class="w-9 h-9 rounded-xl bg-maga-blue/10 flex items-center justify-center flex-shrink-0 text-maga-blue font-black text-sm">
                        {{ reg.fullName.charAt(0).toUpperCase() }}
                      </div>
                      <div>
                        <p class="font-bold text-gray-800 text-sm leading-tight">{{ reg.fullName }}</p>
                        <p class="text-[11px] text-gray-400 font-mono mt-0.5">{{ reg.code }}</p>
                      </div>
                    </div>
                  </td>

                  <!-- Contact -->
                  <td class="py-4 px-5">
                    <div class="flex items-center gap-1.5 text-gray-700 text-sm font-medium">
                      <Phone class="w-3.5 h-3.5 text-gray-300 flex-shrink-0" />
                      {{ reg.phone }}
                    </div>
                    <div class="flex items-center gap-1.5 text-gray-400 text-xs mt-0.5">
                      <Mail class="w-3.5 h-3.5 flex-shrink-0" />
                      <span class="truncate max-w-[180px]">{{ reg.email }}</span>
                    </div>
                  </td>

                  <!-- Direction badge -->
                  <td class="py-4 px-5">
                    <span class="inline-flex items-center bg-maga-blue/8 text-maga-blue text-[11px] font-bold px-2.5 py-1 rounded-lg border border-maga-blue/10">
                      {{ reg.direction }}
                    </span>
                  </td>

                  <!-- Department -->
                  <td class="py-4 px-5">
                    <div class="flex items-center gap-1.5 text-gray-600 text-sm">
                      <MapIcon class="w-3.5 h-3.5 text-gray-300 flex-shrink-0" />
                      {{ reg.department }}
                    </div>
                  </td>

                  <!-- Date -->
                  <td class="py-4 px-5 text-xs text-gray-400 font-medium whitespace-nowrap">
                    {{ reg.dateCreated }}
                  </td>

                  <!-- Actions -->
                  <td class="py-4 px-5">
                    <div class="flex items-center justify-center gap-1">
                      <button
                        @click="handleOpenEdit(reg)"
                        class="p-2 rounded-lg text-gray-400 hover:text-amber-500 hover:bg-amber-50 transition-all cursor-pointer"
                        title="Editar"
                      >
                        <Edit class="w-4 h-4" />
                      </button>
                      <button
                        @click="handleDeleteRegistration(reg.id, reg.fullName)"
                        class="p-2 rounded-lg text-gray-400 hover:text-rose-500 hover:bg-rose-50 transition-all cursor-pointer"
                        title="Eliminar"
                      >
                        <Trash2 class="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              </template>

              <template v-else>
                <tr>
                  <td colspan="6" class="py-16 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-3">
                      <AlertCircle class="w-7 h-7 text-gray-300" />
                    </div>
                    <p class="font-bold text-gray-500">No se encontraron registros</p>
                    <p class="text-xs text-gray-400 mt-1">Prueba con otros términos de búsqueda o limpia los filtros.</p>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <div class="px-5 py-3 border-t border-gray-50 flex justify-between items-center">
          <span class="text-xs text-gray-400 font-medium">
            <span class="font-bold text-gray-600">{{ filteredRegistrations.length }}</span> de {{ registrations.length }} registros
          </span>
          <span v-if="adminDirFilter || adminDeptFilter || searchTerm" class="inline-flex items-center gap-1 text-[11px] font-bold text-maga-blue bg-maga-blue/8 px-2.5 py-1 rounded-full">
            Filtro activo
          </span>
        </div>
      </div>

    </div>

    <!-- MODAL: EDIT -->
    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="isEditingModalOpen && editingReg" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="isEditingModalOpen = false" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl relative z-10 overflow-hidden">
          <div class="bg-maga-blue px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center">
                <Edit class="w-4 h-4 text-white" />
              </div>
              <div>
                <p class="text-white font-bold text-sm">Editar registro</p>
                <p class="text-white/50 text-[11px] font-mono">{{ editingReg.code }}</p>
              </div>
            </div>
            <button @click="isEditingModalOpen = false" class="text-white/60 hover:text-white bg-white/10 hover:bg-white/20 rounded-lg p-1.5 transition-all cursor-pointer">
              <X class="w-4 h-4" />
            </button>
          </div>
          <form @submit.prevent="handleSaveEdit" class="p-6 space-y-4 text-sm">
            <div>
              <label class="block text-xs font-bold text-gray-600 mb-1.5">Nombre Completo</label>
              <input type="text" v-model="editingReg.fullName" class="w-full border border-gray-200 rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white focus:border-maga-blue outline-none focus:ring-2 focus:ring-maga-blue/10 transition-all font-semibold text-gray-800" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 mb-1.5">Teléfono</label>
              <input type="tel" v-model="editingReg.phone" class="w-full border border-gray-200 rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white focus:border-maga-blue outline-none focus:ring-2 focus:ring-maga-blue/10 transition-all font-semibold text-gray-800" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 mb-1.5">Correo Electrónico</label>
              <input type="email" v-model="editingReg.email" class="w-full border border-gray-200 rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white focus:border-maga-blue outline-none focus:ring-2 focus:ring-maga-blue/10 transition-all font-semibold text-gray-800" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 mb-1.5">Dirección</label>
              <select v-model="editingReg.direction" class="w-full border border-gray-200 rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white focus:border-maga-blue outline-none focus:ring-2 focus:ring-maga-blue/10 transition-all font-semibold text-gray-800 cursor-pointer">
                <option v-for="dir in DIRECTIONS" :key="dir" :value="dir">{{ dir }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 mb-1.5">Departamento</label>
              <input type="text" v-model="editingReg.department" class="w-full border border-gray-200 rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white focus:border-maga-blue outline-none focus:ring-2 focus:ring-maga-blue/10 transition-all font-semibold text-gray-800" />
            </div>
            <div class="flex gap-3 pt-2">
              <button type="button" @click="isEditingModalOpen = false" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2.5 rounded-xl font-bold transition-all cursor-pointer">Cancelar</button>
              <button type="submit" class="flex-1 bg-maga-blue hover:bg-maga-light-blue text-white py-2.5 rounded-xl font-bold transition-all shadow-md cursor-pointer">Guardar cambios</button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- MODAL: ADD -->
    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="isAddModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="isAddModalOpen = false" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl relative z-10 overflow-hidden">
          <div class="bg-emerald-600 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center">
                <Plus class="w-4 h-4 text-white" />
              </div>
              <p class="text-white font-bold text-sm">Nuevo registro manual</p>
            </div>
            <button @click="isAddModalOpen = false" class="text-white/60 hover:text-white bg-white/10 hover:bg-white/20 rounded-lg p-1.5 transition-all cursor-pointer">
              <X class="w-4 h-4" />
            </button>
          </div>
          <form @submit.prevent="handleCreateAdminRecord" class="p-6 space-y-4 text-sm">
            <div>
              <label class="block text-xs font-bold text-gray-600 mb-1.5">Nombre Completo *</label>
              <input type="text" v-model="newAdminName" @input="adminFormErrors.fullName = ''" :class="['w-full border rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white outline-none focus:ring-2 transition-all', adminFormErrors.fullName ? 'border-rose-400 focus:ring-rose-200' : 'border-gray-200 focus:border-maga-blue focus:ring-maga-blue/10']" placeholder="Nombre completo" />
              <span v-if="adminFormErrors.fullName" class="text-rose-500 text-[11px] mt-1 block">{{ adminFormErrors.fullName }}</span>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 mb-1.5">Teléfono *</label>
              <input type="tel" v-model="newAdminPhone" @input="adminFormErrors.phone = ''" :class="['w-full border rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white outline-none focus:ring-2 transition-all', adminFormErrors.phone ? 'border-rose-400 focus:ring-rose-200' : 'border-gray-200 focus:border-maga-blue focus:ring-maga-blue/10']" placeholder="0000-0000" />
              <span v-if="adminFormErrors.phone" class="text-rose-500 text-[11px] mt-1 block">{{ adminFormErrors.phone }}</span>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 mb-1.5">Correo Electrónico *</label>
              <input type="email" v-model="newAdminEmail" @input="adminFormErrors.email = ''" :class="['w-full border rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white outline-none focus:ring-2 transition-all', adminFormErrors.email ? 'border-rose-400 focus:ring-rose-200' : 'border-gray-200 focus:border-maga-blue focus:ring-maga-blue/10']" placeholder="correo@ejemplo.com" />
              <span v-if="adminFormErrors.email" class="text-rose-500 text-[11px] mt-1 block">{{ adminFormErrors.email }}</span>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 mb-1.5">Dirección *</label>
              <select v-model="newAdminDir" @change="adminFormErrors.direction = ''" :class="['w-full border rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white outline-none focus:ring-2 transition-all cursor-pointer', adminFormErrors.direction ? 'border-rose-400 focus:ring-rose-200' : 'border-gray-200 focus:border-maga-blue focus:ring-maga-blue/10']">
                <option value="">Selecciona una dirección</option>
                <option v-for="dir in DIRECTIONS" :key="dir" :value="dir">{{ dir }}</option>
              </select>
              <span v-if="adminFormErrors.direction" class="text-rose-500 text-[11px] mt-1 block">{{ adminFormErrors.direction }}</span>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 mb-1.5">Departamento *</label>
              <input type="text" v-model="newAdminDept" @input="adminFormErrors.department = ''" :class="['w-full border rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white outline-none focus:ring-2 transition-all', adminFormErrors.department ? 'border-rose-400 focus:ring-rose-200' : 'border-gray-200 focus:border-maga-blue focus:ring-maga-blue/10']" placeholder="Departamento" />
              <span v-if="adminFormErrors.department" class="text-rose-500 text-[11px] mt-1 block">{{ adminFormErrors.department }}</span>
            </div>
            <div class="flex gap-3 pt-2">
              <button type="button" @click="isAddModalOpen = false" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2.5 rounded-xl font-bold transition-all cursor-pointer">Cancelar</button>
              <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-2.5 rounded-xl font-bold transition-all shadow-md cursor-pointer">Guardar registro</button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { 
  User, 
  Phone, 
  Mail, 
  Map as MapIcon,
  Trash2, 
  Edit, 
  Search, 
  FileSpreadsheet, 
  Plus, 
  X, 
  Sparkles,
  Database,
  Grid,
  AlertCircle
} from 'lucide-vue-next';

const MOCK_REGISTRATIONS = [
  { id: 'mock-1', fullName: 'Carlos Enrique Fuentes Rivera', phone: '58412039', email: 'carlos.fuentes@maga.gob.gt', direction: 'SANIDAD ANIMAL', department: 'Guatemala', dateCreated: '24/06/2026 10:15', code: 'MAGA-PADRE-2026-9481' },
  { id: 'mock-2', fullName: 'José Mario Alvarez Castillo', phone: '41203948', email: 'jose.alvarez@gmail.com', direction: 'VICEDESPACHO', department: 'Sacatepéquez', dateCreated: '24/06/2026 11:30', code: 'MAGA-PADRE-2026-1029' },
  { id: 'mock-3', fullName: 'Ramiro Antonio Morales Arriola', phone: '30291827', email: 'ramiro_morales@gmail.com', direction: 'SANIDAD VEGETAL', department: 'Chimaltenango', dateCreated: '24/06/2026 12:05', code: 'MAGA-PADRE-2026-4739' },
  { id: 'mock-4', fullName: 'Luis Francisco Ortiz Estrada', phone: '55443322', email: 'lortiz@maga.gob.gt', direction: 'INOCUIDAD', department: 'Escuintla', dateCreated: '24/06/2026 13:40', code: 'MAGA-PADRE-2026-8821' },
  { id: 'mock-5', fullName: 'Manuel de Jesús Galdámez', phone: '47881122', email: 'manuel.galdamez@outlook.com', direction: 'RRHH', department: 'Alta Verapaz', dateCreated: '24/06/2026 14:02', code: 'MAGA-PADRE-2026-2938' }
];

const DIRECTIONS = [
  'SANIDAD ANIMAL',
  'SANIDAD VEGETAL',
  'INOCUIDAD',
  'FITOZOOGENÉTICA',
  'DIPESCA',
  'UDAFA',
  'RRHH',
  'VICEDESPACHO'
];

const registrations = ref([]);
const searchTerm = ref('');
const adminDirFilter = ref('');
const adminDeptFilter = ref('');

const isEditingModalOpen = ref(false);
const editingReg = ref(null);

const isAddModalOpen = ref(false);
const newAdminName = ref('');
const newAdminPhone = ref('');
const newAdminEmail = ref('');
const newAdminDir = ref('');
const newAdminDept = ref('');
const adminFormErrors = ref({});

const toastMessage = ref(null);

onMounted(() => {
  const stored = localStorage.getItem('maga_padre_registrations');
  if (stored) {
    try { registrations.value = JSON.parse(stored); } catch (e) {}
  } else {
    registrations.value = [...MOCK_REGISTRATIONS];
    localStorage.setItem('maga_padre_registrations', JSON.stringify(registrations.value));
  }
});

const showToast = (message) => {
  toastMessage.value = message;
  setTimeout(() => toastMessage.value = null, 3500);
};

const updateLocalStorage = (updated) => {
  registrations.value = updated;
  localStorage.setItem('maga_padre_registrations', JSON.stringify(updated));
};

const getFormattedNow = () => {
  const now = new Date();
  const day = String(now.getDate()).padStart(2, '0');
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const year = now.getFullYear();
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  return `${day}/${month}/${year} ${hours}:${minutes}`;
};

const generateUniqueCode = () => {
  const rand = Math.floor(1000 + Math.random() * 9000);
  return `MAGA-PADRE-2026-${rand}`;
};

const validateForm = (nameVal, phoneVal, emailVal, dirVal, deptVal) => {
  const errors = {};
  if (!nameVal.trim()) errors.fullName = 'El nombre completo es obligatorio';
  else if (nameVal.trim().length < 4) errors.fullName = 'Mínimo 4 caracteres';
  const cleanPhone = phoneVal.replace(/\D/g, '');
  if (!phoneVal.trim()) errors.phone = 'El teléfono es obligatorio';
  else if (cleanPhone.length < 8) errors.phone = 'Mínimo 8 dígitos';
  if (!emailVal.trim()) errors.email = 'El correo es obligatorio';
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal.trim())) errors.email = 'Formato inválido';
  if (!dirVal) errors.direction = 'Seleccione una dirección';
  if (!deptVal.trim()) errors.department = 'El departamento es obligatorio';
  return errors;
};


const handleExportCSV = () => {
  if (registrations.value.length === 0) {
    showToast('No hay registros para exportar.');
    return;
  }
  let csvContent = "data:text/csv;charset=utf-8,\uFEFF";
  csvContent += "Codigo,Nombre Completo,Telefono,Correo,Direccion,Departamento,Fecha Registro\n";
  registrations.value.forEach(r => {
    csvContent += `"${r.code}","${r.fullName}","${r.phone}","${r.email}","${r.direction}","${r.department}","${r.dateCreated}"\n`;
  });
  const link = document.createElement('a');
  link.href = encodeURI(csvContent);
  link.download = `Participantes_MAGA_${new Date().toISOString().slice(0,10)}.csv`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  showToast('Exportado a CSV exitosamente.');
};

const clearFilters = () => {
  searchTerm.value = '';
  adminDirFilter.value = '';
  adminDeptFilter.value = '';
};

const handleDeleteRegistration = (id, name) => {
  if (window.confirm(`¿Está seguro de que desea eliminar a ${name}?`)) {
    updateLocalStorage(registrations.value.filter(r => r.id !== id));
    showToast('Registro eliminado exitosamente.');
  }
};

const handleOpenEdit = (reg) => {
  editingReg.value = { ...reg };
  isEditingModalOpen.value = true;
};

const handleSaveEdit = () => {
  if (!editingReg.value) return;
  const errors = validateForm(editingReg.value.fullName, editingReg.value.phone, editingReg.value.email, editingReg.value.direction, editingReg.value.department);
  if (Object.keys(errors).length > 0) {
    showToast('Complete correctamente todos los campos.');
    return;
  }
  const updated = registrations.value.map(r => r.id === editingReg.value.id ? editingReg.value : r);
  updateLocalStorage(updated);
  isEditingModalOpen.value = false;
  editingReg.value = null;
  showToast('Registro actualizado correctamente.');
};

const handleCreateAdminRecord = () => {
  const errors = validateForm(newAdminName.value, newAdminPhone.value, newAdminEmail.value, newAdminDir.value, newAdminDept.value);
  adminFormErrors.value = errors;
  if (Object.keys(errors).length > 0) return showToast('Verifique los campos con errores.');
  const newReg = {
    id: 'reg-' + Date.now(),
    fullName: newAdminName.value.trim(),
    phone: newAdminPhone.value.trim(),
    email: newAdminEmail.value.trim().toLowerCase(),
    direction: newAdminDir.value,
    department: newAdminDept.value,
    dateCreated: getFormattedNow(),
    code: generateUniqueCode()
  };
  updateLocalStorage([newReg, ...registrations.value]);
  isAddModalOpen.value = false;
  newAdminName.value = ''; newAdminPhone.value = ''; newAdminEmail.value = ''; newAdminDir.value = ''; newAdminDept.value = '';
  adminFormErrors.value = {};
  showToast('Padre agregado manualmente de forma exitosa.');
};

const uniqueDepartments = computed(() => [...new Set(registrations.value.map(r => r.department).filter(Boolean))].sort());

const filteredRegistrations = computed(() => {
  return registrations.value.filter(r => {
    const s = searchTerm.value.toLowerCase();
    const matchSearch = r.fullName.toLowerCase().includes(s) || r.email.toLowerCase().includes(s) || r.phone.includes(s) || r.code.toLowerCase().includes(s);
    const matchDir = adminDirFilter.value ? r.direction === adminDirFilter.value : true;
    const matchDept = adminDeptFilter.value ? r.department === adminDeptFilter.value : true;
    return matchSearch && matchDir && matchDept;
  });
});

const statsTotal = computed(() => registrations.value.length);
const directionCounts = computed(() => registrations.value.reduce((acc, curr) => { acc[curr.direction] = (acc[curr.direction] || 0) + 1; return acc; }, {}));
const topDirection = computed(() => Object.entries(directionCounts.value).sort((a,b) => b[1] - a[1])[0]?.[0] || 'Ninguna');
</script>
