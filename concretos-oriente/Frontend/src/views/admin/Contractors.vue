<template>
  <div class="pt-20 pb-10 px-4 md:px-10 md:pb-20 max-w-7xl mx-auto space-y-10">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Contratistas</h2>
        <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Resumen de pagos por contratista y proyecto</p>
      </div>
      <button @click="openContractorModal" class="glass-button-primary text-white py-4 px-10 rounded-2xl font-black text-xs uppercase tracking-widest flex items-center justify-center gap-3 shadow-2xl shadow-primary/20 hover:scale-105 transition-all">
        <PlusIcon class="w-5 h-5" />
        Añadir Contratista
      </button>
    </div>

    <section class="glass-card rounded-[56px] overflow-hidden border border-white/5 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
      <div class="p-12 border-b border-white/5 bg-white/5 backdrop-blur-3xl flex flex-col md:flex-row md:items-center justify-between gap-8">
        <div class="relative flex-1 max-w-lg">
          <MagnifyingGlassIcon class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-white/30" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Buscar contratistas por nombre..."
            class="w-full glass-input rounded-2xl pl-14 pr-6 py-4 text-sm font-medium text-white outline-none focus:ring-2 focus:ring-primary/40 transition-all"
          />
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-0 divide-x divide-y divide-white/5">
        <div v-if="loading" class="p-12 text-center text-white/40 col-span-3">Cargando contratistas...</div>
        <div v-else-if="filteredContractors.length === 0" class="p-12 text-center text-white/40 col-span-3">No hay contratistas registrados.</div>

        <div
          v-for="c in filteredContractors"
          :key="c.id"
          @click="selectContractor(c)"
          class="p-12 cursor-pointer transition-all hover:bg-white/[0.02] group relative"
        >
          <div class="absolute top-6 right-6 flex gap-2">
            <button @click.stop="openEditContractor(c)" class="p-2 bg-white/5 hover:bg-white/10 rounded-xl text-white/40 hover:text-white transition-all"><PencilIcon class="w-4 h-4"/></button>
            <button @click.stop="deleteContractor(c.id)" class="p-2 bg-white/5 hover:bg-white/10 rounded-xl text-white/40 hover:text-tertiary transition-all"><TrashIcon class="w-4 h-4"/></button>
          </div>

          <div class="flex justify-between items-start mb-8">
            <div class="w-16 h-16 rounded-3xl bg-white/5 border border-white/10 flex items-center justify-center text-primary group-hover:scale-110 transition-transform shadow-2xl">
              <UserGroupIcon class="w-8 h-8" />
            </div>
          </div>

          <h4 class="text-2xl font-black text-white italic uppercase tracking-tighter mb-2 truncate" :title="c.nombre">{{ c.nombre }}</h4>
          <p class="text-[10px] font-bold text-white/30 uppercase tracking-[0.2em] mb-8">Contratista</p>

          <div class="space-y-4">
            <div class="flex items-center gap-4 text-white/60">
              <PhoneIcon class="w-4 h-4 text-primary" />
              <span class="text-sm font-medium">{{ c.telefono || 'Sin teléfono' }}</span>
            </div>
            <div class="flex items-center gap-4 text-white/60">
              <EnvelopeIcon class="w-4 h-4 text-primary" />
              <span class="text-sm font-medium truncate">{{ c.correo_electronico || 'Sin correo' }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Detail / Summary Modal -->
    <transition name="fade">
      <div v-if="selectedContractor" class="fixed inset-0 z-50 flex items-center justify-center p-6">
        <div @click="closeDetail" class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-5xl max-h-[90vh] overflow-y-auto custom-scrollbar glass-card rounded-[56px] p-12 border border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.5)]" data-aos="zoom-in-up" data-aos-duration="1000">
          <div class="flex flex-col md:flex-row items-start justify-between gap-6 mb-10">
            <div class="flex gap-8">
              <div class="w-24 h-24 rounded-[32px] bg-primary/20 flex items-center justify-center text-primary border border-white/10 shrink-0">
                <UserGroupIcon class="w-12 h-12" />
              </div>
              <div>
                <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">{{ selectedContractor.nombre }}</h2>
                <div class="flex flex-wrap items-center gap-6 mt-4">
                  <span class="text-sm font-bold text-white/60">{{ selectedContractor.telefono || 'Sin teléfono' }}</span>
                  <span class="text-sm font-bold text-white/60">{{ selectedContractor.correo_electronico || 'Sin correo' }}</span>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <button @click="openAssignModal" class="glass-button-primary text-white py-3 px-6 rounded-2xl font-black text-xs uppercase tracking-widest flex items-center gap-2 shadow-xl whitespace-nowrap">
                <PlusIcon class="w-4 h-4" /> Agregar Proyecto
              </button>
              <button @click="closeDetail" class="w-14 h-14 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-all border border-white/5 text-white/40 hover:text-white shrink-0">
                <XMarkIcon class="w-8 h-8" />
              </button>
            </div>
          </div>

          <div v-if="loadingSummary" class="p-12 text-center text-white/40">Cargando resumen...</div>
          <div v-else-if="summaryProjects.length === 0" class="p-12 text-center text-white/40">Este contratista aún no tiene proyectos asignados.</div>

          <div v-else class="space-y-8">
            <div v-for="p in summaryProjects" :key="p.project_id" class="rounded-[32px] overflow-hidden border border-white/10">
              <div class="bg-black px-8 py-5 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                  <h3 class="text-lg font-black text-white uppercase tracking-wider">{{ p.proyecto_nombre }}</h3>
                  <button v-if="p.project_contractor_id" @click="removeAssignment(p)" class="p-1.5 hover:bg-white/10 rounded-lg text-white/30 hover:text-tertiary transition-all" title="Quitar proyecto">
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
                <span class="text-white font-black text-xl italic whitespace-nowrap">Q {{ formatMoney(p.monto_contratado) }}</span>
              </div>

              <div class="overflow-x-auto">
                <table class="w-full min-w-[480px] text-left">
                  <thead>
                    <tr class="text-[10px] font-black text-white/20 uppercase tracking-[0.2em] bg-white/5">
                      <th class="px-8 py-4">Fecha</th>
                      <th class="px-8 py-4">No.</th>
                      <th class="px-8 py-4">Banco / Cuenta</th>
                      <th class="px-8 py-4 text-right">Monto</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-white/5">
                    <tr v-if="p.pagos.length === 0">
                      <td colspan="4" class="px-8 py-6 text-center text-white/30 text-sm">Sin abonos registrados.</td>
                    </tr>
                    <tr v-for="pago in p.pagos" :key="pago.id" class="hover:bg-white/5 transition-all">
                      <td class="px-8 py-4 text-sm text-white/70">{{ formatDate(pago.fecha_egreso) }}</td>
                      <td class="px-8 py-4 text-sm text-white/50">{{ pago.numero_cheque || '-' }}</td>
                      <td class="px-8 py-4 text-sm text-white/50">{{ pago.cuenta_origen || '-' }}</td>
                      <td class="px-8 py-4 text-right text-sm font-bold text-white">Q {{ formatMoney(pago.monto) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="bg-white/5 px-8 py-6 flex flex-wrap items-center justify-between gap-6 border-t border-white/5">
                <div>
                  <p class="text-[10px] font-black text-white/30 uppercase tracking-widest">Pagado</p>
                  <p class="text-xl font-black text-emerald-400 italic">Q {{ formatMoney(p.total_pagado) }}</p>
                </div>
                <div>
                  <p class="text-[10px] font-black text-white/30 uppercase tracking-widest">Por Pagar</p>
                  <p class="text-xl font-black text-amber-400 italic">Q {{ formatMoney(p.por_pagar) }}</p>
                </div>
                <div class="flex-1 min-w-[180px]">
                  <div class="flex items-center justify-between mb-1">
                    <p class="text-[10px] font-black text-white/30 uppercase tracking-widest">Avance</p>
                    <p class="text-xs font-black text-white">{{ Number(p.porcentaje).toFixed(2) }}%</p>
                  </div>
                  <div class="w-full h-2 bg-black/30 rounded-full overflow-hidden">
                    <div class="h-full bg-primary rounded-full" :style="{ width: Math.min(p.porcentaje, 100) + '%' }"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Contractor Create/Edit Modal -->
    <div v-if="showContractorModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeContractorModal"></div>
      <div class="glass-card w-full max-w-2xl rounded-[32px] p-4 md:p-8 relative z-10 border border-white/10 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-2xl font-bold text-white">{{ isEditing ? 'Editar Contratista' : 'Añadir Contratista' }}</h3>
          <button @click="closeContractorModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all"><XMarkIcon class="w-6 h-6" /></button>
        </div>

        <form @submit.prevent="submitContractor" class="space-y-6">
          <div class="space-y-2">
            <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Nombre *</label>
            <input v-model="formContractor.nombre" type="text" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Teléfono</label>
              <input v-model="formContractor.telefono" @input="formatPhone" type="text" maxlength="9" placeholder="0000-0000" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Correo Electrónico</label>
              <input v-model="formContractor.correo_electronico" type="email" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>
          </div>

          <div class="pt-4 flex justify-end gap-4 border-t border-white/5">
            <button type="button" @click="closeContractorModal" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">Cancelar</button>
            <button type="submit" :disabled="isSubmitting" class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 disabled:opacity-50 transition-all">
              {{ isEditing ? 'Actualizar' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Assign Project Modal -->
    <div v-if="showAssignModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeAssignModal"></div>
      <div class="glass-card w-full max-w-2xl rounded-[32px] p-4 md:p-8 relative z-10 border border-white/10 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-2xl font-bold text-white">Agregar Proyecto{{ selectedContractor ? ' a ' + selectedContractor.nombre : '' }}</h3>
          <button @click="closeAssignModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all"><XMarkIcon class="w-6 h-6" /></button>
        </div>

        <form @submit.prevent="submitAssign" class="space-y-6">
          <div class="space-y-2">
            <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Proyecto *</label>
            <select v-model="formAssign.project_id" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none">
              <option value="" disabled>Seleccione...</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.nombre }}</option>
            </select>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Monto Contratado (Q) *</label>
              <input v-model="formAssign.monto_contratado" type="number" step="0.01" min="0" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Fecha de Asignación *</label>
              <input v-model="formAssign.fecha_asignacion" type="date" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Observaciones</label>
            <textarea v-model="formAssign.observaciones" rows="2" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50"></textarea>
          </div>

          <div class="pt-4 flex justify-end gap-4 border-t border-white/5">
            <button type="button" @click="closeAssignModal" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">Cancelar</button>
            <button type="submit" :disabled="isSubmitting" class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 disabled:opacity-50 transition-all">
              Guardar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import {
  UserGroupIcon, PhoneIcon, EnvelopeIcon, PlusIcon, MagnifyingGlassIcon,
  XMarkIcon, PencilIcon, TrashIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const BASE_URL = '/concretos-oriente/Backend/api/v1';
const swalBase = { background: '#0f172a', color: '#fff' };

const contractors = ref([]);
const projects = ref([]);
const loading = ref(true);
const isSubmitting = ref(false);
const searchQuery = ref('');

const fetchContractors = async () => {
  loading.value = true;
  try {
    const res = await fetch(`${BASE_URL}/contractors`);
    const data = await res.json();
    if (data.status === 'success') contractors.value = data.data;
  } catch (e) {}
  loading.value = false;
};

const fetchProjects = async () => {
  try {
    const res = await fetch(`${BASE_URL}/projects`);
    const data = await res.json();
    if (data.status === 'success') projects.value = data.data;
  } catch (e) {}
};

onMounted(() => {
  fetchContractors();
  fetchProjects();
});

const filteredContractors = computed(() => {
  if (!searchQuery.value) return contractors.value;
  return contractors.value.filter(c => c.nombre.toLowerCase().includes(searchQuery.value.toLowerCase()));
});

const formatDate = (val) => {
  if (!val) return '';
  const [y, m, d] = val.split('-');
  return `${d}/${m}/${y}`;
};

const formatMoney = (val) => Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });

const formatPhone = (e) => {
  let val = e.target.value.replace(/\D/g, '');
  if (val.length > 4) {
    val = val.substring(0, 4) + '-' + val.substring(4, 8);
  }
  formContractor.value.telefono = val;
};

// CONTRACTOR CRUD
const showContractorModal = ref(false);
const isEditing = ref(false);
const editContractorId = ref(null);
const formContractor = ref({ nombre: '', telefono: '', correo_electronico: '' });

const openContractorModal = () => {
  isEditing.value = false;
  formContractor.value = { nombre: '', telefono: '', correo_electronico: '' };
  showContractorModal.value = true;
};

const openEditContractor = (c) => {
  isEditing.value = true;
  editContractorId.value = c.id;
  formContractor.value = { nombre: c.nombre, telefono: c.telefono || '', correo_electronico: c.correo_electronico || '' };
  showContractorModal.value = true;
};

const closeContractorModal = () => showContractorModal.value = false;

const submitContractor = async () => {
  isSubmitting.value = true;
  const fd = new FormData();
  Object.keys(formContractor.value).forEach(k => {
    if (formContractor.value[k] !== null && formContractor.value[k] !== '') fd.append(k, formContractor.value[k]);
  });

  try {
    const url = isEditing.value ? `${BASE_URL}/contractors/${editContractorId.value}` : `${BASE_URL}/contractors`;
    const res = await fetch(url, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.status === 'success') {
      await fetchContractors();
      closeContractorModal();
      Swal.fire({ ...swalBase, icon: 'success', title: 'Guardado' });
    } else {
      Swal.fire({ ...swalBase, icon: 'error', text: json.message });
    }
  } catch (e) {}
  isSubmitting.value = false;
};

const deleteContractor = async (id) => {
  const { isConfirmed } = await Swal.fire({
    ...swalBase, title: '¿Eliminar contratista?', icon: 'warning', showCancelButton: true
  });
  if (isConfirmed) {
    try {
      const res = await fetch(`${BASE_URL}/contractors/${id}`, { method: 'DELETE' });
      const json = await res.json();
      if (json.status === 'success') {
        await fetchContractors();
        if (selectedContractor.value?.id === id) closeDetail();
        Swal.fire({ ...swalBase, icon: 'success', title: 'Eliminado' });
      } else {
        Swal.fire({ ...swalBase, icon: 'error', text: json.message });
      }
    } catch (e) {}
  }
};

// DETAIL / SUMMARY
const selectedContractor = ref(null);
const summaryProjects = ref([]);
const loadingSummary = ref(false);

const selectContractor = async (c) => {
  selectedContractor.value = c;
  await fetchSummary();
};

const fetchSummary = async () => {
  if (!selectedContractor.value) return;
  loadingSummary.value = true;
  try {
    const res = await fetch(`${BASE_URL}/contractors/${selectedContractor.value.id}/summary`);
    const data = await res.json();
    if (data.status === 'success') summaryProjects.value = data.data.projects;
  } catch (e) {}
  loadingSummary.value = false;
};

const closeDetail = () => {
  selectedContractor.value = null;
  summaryProjects.value = [];
};

// ASSIGN PROJECT
const showAssignModal = ref(false);
const formAssign = ref({ project_id: '', monto_contratado: '', fecha_asignacion: new Date().toISOString().slice(0, 10), observaciones: '' });

const openAssignModal = () => {
  formAssign.value = { project_id: '', monto_contratado: '', fecha_asignacion: new Date().toISOString().slice(0, 10), observaciones: '' };
  showAssignModal.value = true;
};

const closeAssignModal = () => showAssignModal.value = false;

const submitAssign = async () => {
  isSubmitting.value = true;
  const fd = new FormData();
  fd.append('project_id', formAssign.value.project_id);
  fd.append('monto_contratado', formAssign.value.monto_contratado);
  fd.append('fecha_asignacion', formAssign.value.fecha_asignacion);
  if (formAssign.value.observaciones) fd.append('observaciones', formAssign.value.observaciones);

  try {
    const res = await fetch(`${BASE_URL}/contractors/${selectedContractor.value.id}/projects`, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.status === 'success') {
      closeAssignModal();
      await fetchSummary();
      Swal.fire({ ...swalBase, icon: 'success', title: 'Proyecto asignado' });
    } else {
      Swal.fire({ ...swalBase, icon: 'error', text: json.message });
    }
  } catch (e) {}
  isSubmitting.value = false;
};

const removeAssignment = async (p) => {
  const { isConfirmed } = await Swal.fire({
    ...swalBase, title: '¿Quitar este proyecto del contratista?', icon: 'warning', showCancelButton: true
  });
  if (!isConfirmed) return;
  try {
    const res = await fetch(`${BASE_URL}/contractors/${selectedContractor.value.id}/projects/${p.project_id}`, { method: 'DELETE' });
    const json = await res.json();
    if (json.status === 'success') {
      await fetchSummary();
    } else {
      Swal.fire({ ...swalBase, icon: 'error', text: json.message });
    }
  } catch (e) {}
};
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.02); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
</style>
