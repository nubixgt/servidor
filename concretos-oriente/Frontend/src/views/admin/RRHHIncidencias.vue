<template>
  <div class="pt-20 pb-10 px-4 md:px-10 md:pb-20 max-w-7xl mx-auto space-y-10 relative">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h2 class="text-4xl font-bold tracking-tight text-white mb-2">Incidencias de Personal</h2>
        <p class="text-white/60">Control y seguimiento de amonestaciones, faltas, retardos y evidencias de colaboradores.</p>
      </div>

      <div class="flex gap-3">
        <button
          @click="openIncidentModal()"
          class="glass-button text-white py-4 px-8 rounded-2xl font-bold flex items-center justify-center gap-2 border border-amber-400/40 text-amber-300 hover:bg-amber-400/10 hover:-translate-y-0.5 active:translate-y-0 transition-all shadow-xl shadow-amber-500/10"
        >
          <ExclamationTriangleIcon class="w-5 h-5" />
          Registrar Incidencia
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div
        v-for="(stat, i) in stats"
        :key="i"
        class="glass-card p-8 rounded-[32px] flex flex-col justify-between h-44 cursor-pointer group hover:-translate-y-2 hover:shadow-[0_20px_40px_-15px_rgba(245,158,11,0.3)] transition-all duration-500 border border-white/5"
      >
        <div class="flex items-center justify-between mb-4">
          <div :class="`p-3 rounded-2xl ${stat.bgColor} ${stat.color} border border-white/10 shadow-lg`">
            <component :is="stat.icon" class="w-6 h-6" />
          </div>
          <span :class="`text-[10px] font-bold px-3 py-1.5 rounded-full ${stat.color} ${stat.bgColor} border border-white/5 tracking-wider uppercase`">
            {{ stat.badge }}
          </span>
        </div>
        <div>
          <p class="text-white/40 text-[10px] font-bold uppercase tracking-[0.2em]">{{ stat.label }}</p>
          <h3 class="text-3xl font-black italic text-white mt-1 group-hover:text-amber-400 transition-colors">{{ stat.value }}</h3>
        </div>
      </div>
    </div>

    <!-- Incidents Table -->
    <div class="glass-card rounded-[40px] overflow-hidden border border-white/10">
      <div class="p-8 border-b border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 flex-1">
          <!-- Search -->
          <div class="flex items-center gap-2 bg-black/20 border border-white/10 rounded-2xl px-4 py-3 flex-1 min-w-[220px]">
            <MagnifyingGlassIcon class="w-4 h-4 text-white/30 flex-shrink-0" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Buscar por colaborador, motivo o detalle..."
              class="bg-transparent flex-1 text-sm text-white placeholder-white/30 focus:outline-none"
            />
          </div>

          <!-- Filtro Motivo -->
          <select v-model="filterMotivo" class="bg-black/20 border border-white/10 rounded-2xl px-4 py-3 text-sm text-white focus:outline-none focus:border-primary/50 appearance-none min-w-[180px]">
            <option value="">Todos los Motivos</option>
            <option value="Falta Injustificada">Falta Injustificada</option>
            <option value="Llegada Tardía">Llegada Tardía</option>
            <option value="Llamada de Atención">Llamada de Atención</option>
            <option value="Amonestación Verbal">Amonestación Verbal</option>
            <option value="Amonestación Escrita">Amonestación Escrita</option>
            <option value="Incumplimiento de Funciones">Incumplimiento de Funciones</option>
            <option value="Conducta Inapropiada">Conducta Inapropiada</option>
            <option value="Suspensión">Suspensión</option>
            <option value="Otro">Otro</option>
          </select>
        </div>

        <div class="flex items-center gap-3">
          <span class="text-xs font-bold text-white/40 uppercase tracking-widest">
            {{ filteredIncidents.length }} {{ filteredIncidents.length === 1 ? 'Incidencia' : 'Incidencias' }}
          </span>
        </div>
      </div>

      <!-- Table Content -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-white/5 bg-white/[0.02]">
              <th class="py-5 px-8 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Colaborador</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Fecha</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Motivo</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Detalle / Descripción</th>
              <th class="py-5 px-6 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40">Evidencias</th>
              <th class="py-5 px-8 text-[11px] font-bold uppercase tracking-[0.2em] text-white/40 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-if="loading" class="text-center">
              <td colspan="6" class="py-16 text-white/40 font-semibold">Cargando incidencias...</td>
            </tr>
            <tr v-else-if="filteredIncidents.length === 0" class="text-center">
              <td colspan="6" class="py-16 text-white/40">
                <ExclamationTriangleIcon class="w-12 h-12 text-white/10 mx-auto mb-3" />
                <p class="font-bold text-base">No hay incidencias registradas</p>
                <p class="text-xs text-white/30 mt-1">Todas las amonestaciones y faltas se mostrarán en esta lista.</p>
              </td>
            </tr>
            <tr
              v-for="inc in paginatedIncidents"
              :key="inc.id"
              class="hover:bg-white/[0.03] transition-colors group"
            >
              <!-- Colaborador -->
              <td class="py-5 px-8">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-2xl bg-amber-500/20 flex items-center justify-center font-bold text-amber-400 text-sm flex-shrink-0">
                    {{ (inc.nombres || 'E')[0] }}{{ (inc.apellidos || '')[0] }}
                  </div>
                  <div>
                    <p class="font-bold text-white text-sm leading-tight">{{ inc.nombres }} {{ inc.apellidos }}</p>
                    <p class="text-xs text-white/40 mt-0.5">{{ inc.puesto || 'Colaborador' }}</p>
                  </div>
                </div>
              </td>

              <!-- Fecha -->
              <td class="py-5 px-6 font-bold text-xs text-white/80 whitespace-nowrap">
                {{ formatDate(inc.fecha) }}
              </td>

              <!-- Motivo -->
              <td class="py-5 px-6">
                <span :class="['px-3 py-1 rounded-xl text-xs font-bold border inline-block whitespace-nowrap', getMotivoBadge(inc.motivo)]">
                  {{ inc.motivo }}
                </span>
              </td>

              <!-- Detalle -->
              <td class="py-5 px-6 text-xs text-white/70 max-w-xs">
                <p class="line-clamp-2">{{ inc.texto }}</p>
              </td>

              <!-- Evidencias Múltiples -->
              <td class="py-5 px-6">
                <div class="flex flex-wrap items-center gap-1.5">
                  <template v-if="getAdjuntosList(inc.adjunto_path).length > 0">
                    <a
                      v-for="(adj, idx) in getAdjuntosList(inc.adjunto_path)"
                      :key="idx"
                      :href="getFileUrl(adj)"
                      target="_blank"
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white/5 hover:bg-white/15 border border-white/10 text-[11px] font-bold text-amber-300 transition-all hover:scale-105"
                      :title="'Abrir evidencia ' + (idx + 1)"
                    >
                      <PaperClipIcon class="w-3.5 h-3.5" />
                      <span>Adjunto {{ idx + 1 }}</span>
                    </a>
                  </template>
                  <span v-else class="text-xs text-white/20">Sin evidencia</span>
                </div>
              </td>

              <!-- Acciones -->
              <td class="py-5 px-8 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="viewIncidentDetail(inc)"
                    class="p-2.5 rounded-xl bg-white/5 hover:bg-primary/20 hover:text-primary text-white/60 transition-all border border-white/5"
                    title="Ver Detalle"
                  >
                    <EyeIcon class="w-4 h-4" />
                  </button>
                  <button
                    @click="deleteIncident(inc.id)"
                    class="p-2.5 rounded-xl bg-white/5 hover:bg-rose-500/20 hover:text-rose-400 text-white/60 transition-all border border-white/5"
                    title="Eliminar incidencia"
                  >
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
          <button
            @click="currentPage--"
            :disabled="currentPage === 1"
            class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-white text-xs font-bold disabled:opacity-30 disabled:pointer-events-none transition-all"
          >
            Anterior
          </button>
          <button
            @click="currentPage++"
            :disabled="currentPage === totalPages"
            class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-white text-xs font-bold disabled:opacity-30 disabled:pointer-events-none transition-all"
          >
            Siguiente
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: REGISTRAR INCIDENCIA -->
    <Transition name="fade">
      <div v-if="showIncidentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="closeIncidentModal" class="absolute inset-0 bg-black/80 backdrop-blur-sm cursor-pointer"></div>
        <div class="relative w-full max-w-xl bg-slate-950/95 border border-white/10 rounded-[36px] p-8 shadow-2xl overflow-y-auto max-h-[92vh] text-white z-10 space-y-6">

          <div class="flex items-center justify-between border-b border-white/5 pb-4">
            <div class="flex items-center gap-3">
              <div class="p-3 rounded-2xl bg-amber-500/20 text-amber-400 border border-amber-500/30">
                <ExclamationTriangleIcon class="w-6 h-6" />
              </div>
              <div>
                <h3 class="text-xl font-black italic uppercase">Registrar Incidencia</h3>
                <p class="text-xs text-white/50">Reporta amonestaciones, faltas o conductas.</p>
              </div>
            </div>
            <button @click="closeIncidentModal" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-white/40 hover:text-white transition-all">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <form @submit.prevent="submitIncident" class="space-y-5">
            <!-- Colaborador -->
            <div class="space-y-2">
              <label class="text-[10px] font-black uppercase tracking-widest text-white/40">Colaborador <span class="text-rose-400">*</span></label>
              <select
                v-model="incidentForm.personnel_id"
                required
                class="w-full h-12 px-4 rounded-xl bg-slate-900 border border-white/10 text-sm font-bold text-white focus:outline-none focus:border-primary"
              >
                <option value="" disabled>Seleccione un colaborador...</option>
                <option v-for="emp in activePersonnelList" :key="emp.id" :value="emp.id">
                  {{ emp.nombres }} {{ emp.apellidos }} ({{ emp.puesto }})
                </option>
              </select>
            </div>

            <!-- Fecha y Motivo -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-white/40">Fecha de Incidencia <span class="text-rose-400">*</span></label>
                <input
                  v-model="incidentForm.fecha"
                  type="date"
                  required
                  class="w-full h-12 px-4 rounded-xl bg-slate-900 border border-white/10 text-sm font-bold text-white focus:outline-none focus:border-primary"
                />
              </div>
              <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-white/40">Motivo <span class="text-rose-400">*</span></label>
                <select
                  v-model="incidentForm.motivo"
                  required
                  class="w-full h-12 px-4 rounded-xl bg-slate-900 border border-white/10 text-sm font-bold text-white focus:outline-none focus:border-primary"
                >
                  <option value="" disabled>Seleccione el motivo...</option>
                  <option value="Falta Injustificada">Falta Injustificada</option>
                  <option value="Llegada Tardía">Llegada Tardía</option>
                  <option value="Llamada de Atención">Llamada de Atención</option>
                  <option value="Amonestación Verbal">Amonestación Verbal</option>
                  <option value="Amonestación Escrita">Amonestación Escrita</option>
                  <option value="Incumplimiento de Funciones">Incumplimiento de Funciones</option>
                  <option value="Conducta Inapropiada">Conducta Inapropiada</option>
                  <option value="Suspensión">Suspensión</option>
                  <option value="Otro">Otro</option>
                </select>
              </div>
            </div>

            <!-- Detalle -->
            <div class="space-y-2">
              <label class="text-[10px] font-black uppercase tracking-widest text-white/40">Descripción de los Hechos <span class="text-rose-400">*</span></label>
              <textarea
                v-model="incidentForm.texto"
                required
                rows="4"
                placeholder="Describa detalladamente lo sucedido, llamados de atención o acuerdos..."
                class="w-full p-4 rounded-xl bg-slate-900 border border-white/10 text-sm text-white focus:outline-none focus:border-primary resize-none"
              ></textarea>
            </div>

            <!-- Evidencias Múltiples -->
            <div class="space-y-2">
              <label class="text-[10px] font-black uppercase tracking-widest text-white/40 flex items-center justify-between">
                <span>Adjuntar Evidencias (Múltiples Archivos/Fotos)</span>
                <span class="text-primary font-normal">PDF, JPG, PNG, DOC</span>
              </label>
              <input
                type="file"
                multiple
                accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.webp"
                @change="handleIncidentFileChange"
                class="w-full text-xs text-white/60 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-amber-500/20 file:text-amber-300 hover:file:bg-amber-500/30 file:transition-all cursor-pointer bg-slate-900 border border-white/10 rounded-xl p-1.5"
              />
              <p v-if="incidentForm.adjuntos.length > 0" class="text-[11px] text-amber-300 font-bold mt-1">
                ✓ {{ incidentForm.adjuntos.length }} {{ incidentForm.adjuntos.length === 1 ? 'archivo seleccionado' : 'archivos seleccionados' }}
              </p>
            </div>

            <!-- Botones -->
            <div class="flex gap-3 pt-4 border-t border-white/5">
              <button
                type="submit"
                :disabled="isSubmitting"
                class="flex-1 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-slate-950 py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-amber-500/20 transition-all flex items-center justify-center gap-2"
              >
                <span v-if="isSubmitting">Guardando...</span>
                <span v-else>Guardar Incidencia</span>
              </button>
              <button
                type="button"
                @click="closeIncidentModal"
                class="px-6 py-4 rounded-2xl bg-white/5 hover:bg-white/10 text-white/50 hover:text-white text-xs font-bold transition-all"
              >
                Cancelar
              </button>
            </div>
          </form>

        </div>
      </div>
    </Transition>

    <!-- MODAL: DETALLE DE INCIDENCIA -->
    <Transition name="fade">
      <div v-if="selectedIncident" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="selectedIncident = null" class="absolute inset-0 bg-black/80 backdrop-blur-sm cursor-pointer"></div>
        <div class="relative w-full max-w-xl bg-slate-950/95 border border-white/10 rounded-[36px] p-8 shadow-2xl overflow-y-auto max-h-[92vh] text-white z-10 space-y-6">

          <div class="flex items-center justify-between border-b border-white/5 pb-4">
            <div>
              <span :class="['px-3 py-1 rounded-xl text-xs font-bold border inline-block mb-1', getMotivoBadge(selectedIncident.motivo)]">
                {{ selectedIncident.motivo }}
              </span>
              <h3 class="text-xl font-black italic uppercase text-white">{{ selectedIncident.nombres }} {{ selectedIncident.apellidos }}</h3>
              <p class="text-xs text-white/50">{{ selectedIncident.puesto }} • Fecha: {{ formatDate(selectedIncident.fecha) }}</p>
            </div>
            <button @click="selectedIncident = null" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-white/40 hover:text-white transition-all">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <div class="space-y-4">
            <div>
              <p class="text-[10px] font-black uppercase tracking-widest text-white/30 mb-2">Descripción</p>
              <div class="bg-black/30 p-4 rounded-2xl border border-white/5 text-sm text-white/90 whitespace-pre-wrap leading-relaxed">
                {{ selectedIncident.texto }}
              </div>
            </div>

            <!-- Evidencias -->
            <div>
              <p class="text-[10px] font-black uppercase tracking-widest text-white/30 mb-2">Evidencias y Documentos</p>
              <div v-if="getAdjuntosList(selectedIncident.adjunto_path).length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <a
                  v-for="(adj, idx) in getAdjuntosList(selectedIncident.adjunto_path)"
                  :key="idx"
                  :href="getFileUrl(adj)"
                  target="_blank"
                  class="flex items-center gap-2 p-3 rounded-xl bg-white/5 hover:bg-primary/20 hover:border-primary/40 border border-white/10 text-xs font-bold text-white transition-all"
                >
                  <DocumentIcon class="w-4 h-4 text-amber-400 shrink-0" />
                  <span class="truncate flex-1">Evidencia {{ idx + 1 }}</span>
                  <span class="text-[10px] text-primary uppercase font-black">Abrir ↗</span>
                </a>
              </div>
              <p v-else class="text-xs text-white/30 italic">No hay archivos adjuntos para esta incidencia.</p>
            </div>
          </div>

          <div class="pt-4 border-t border-white/5 flex justify-end">
            <button
              @click="selectedIncident = null"
              class="px-6 py-3 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs"
            >
              Cerrar
            </button>
          </div>

        </div>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import {
  ExclamationTriangleIcon, PlusIcon, MagnifyingGlassIcon, TrashIcon,
  EyeIcon, XMarkIcon, PaperClipIcon, DocumentIcon,
  UserGroupIcon, ShieldExclamationIcon, ClockIcon, FolderIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const BASE_URL = '/concretos-oriente/Backend/api/v1';

const swalBase = {
  background: '#0f172a',
  color: '#ffffff',
  confirmButtonColor: '#f59e0b',
  cancelButtonColor: '#475569',
  customClass: {
    popup: 'rounded-3xl border border-white/10 shadow-2xl',
    confirmButton: 'rounded-xl px-6 py-3 font-bold text-sm text-slate-950',
    cancelButton: 'rounded-xl px-6 py-3 font-bold text-sm'
  }
};

// State
const incidents = ref([]);
const activePersonnelList = ref([]);
const loading = ref(true);
const isSubmitting = ref(false);

const searchQuery = ref('');
const filterMotivo = ref('');

const showIncidentModal = ref(false);
const selectedIncident = ref(null);

const incidentForm = ref({
  personnel_id: '',
  texto: '',
  fecha: '',
  motivo: '',
  adjuntos: []
});

// Stats
const stats = computed(() => {
  const total = incidents.value.length;
  const uniqueEmps = new Set(incidents.value.map(i => i.personnel_id)).size;
  const conEvidencias = incidents.value.filter(i => getAdjuntosList(i.adjunto_path).length > 0).length;

  return [
    { label: 'Total Incidencias', value: total.toString(), badge: 'Histórico', icon: ExclamationTriangleIcon, color: 'text-amber-400', bgColor: 'bg-amber-500/10' },
    { label: 'Colaboradores Afectados', value: uniqueEmps.toString(), badge: 'Personal', icon: UserGroupIcon, color: 'text-rose-400', bgColor: 'bg-rose-500/10' },
    { label: 'Con Evidencias', value: conEvidencias.toString(), badge: 'Archivos', icon: FolderIcon, color: 'text-primary', bgColor: 'bg-primary/10' },
    { label: 'Amonestaciones Escritas', value: incidents.value.filter(i => i.motivo === 'Amonestación Escrita').length.toString(), badge: 'Graves', icon: ShieldExclamationIcon, color: 'text-orange-400', bgColor: 'bg-orange-500/10' },
  ];
});

// Filtered & Paginated
const filteredIncidents = computed(() => {
  return incidents.value.filter(i => {
    const q = searchQuery.value.toLowerCase();
    const matchSearch = (i.nombres && i.nombres.toLowerCase().includes(q)) ||
                        (i.apellidos && i.apellidos.toLowerCase().includes(q)) ||
                        (i.texto && i.texto.toLowerCase().includes(q)) ||
                        (i.motivo && i.motivo.toLowerCase().includes(q));
    const matchMotivo = !filterMotivo.value || i.motivo === filterMotivo.value;
    return matchSearch && matchMotivo;
  });
});

const currentPage = ref(1);
const itemsPerPage = 10;
const totalPages = computed(() => Math.ceil(filteredIncidents.value.length / itemsPerPage));
const paginatedIncidents = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredIncidents.value.slice(start, start + itemsPerPage);
});

// Lifecycle
onMounted(() => {
  fetchIncidents();
  fetchActivePersonnel();
});

const fetchIncidents = async () => {
  loading.value = true;
  try {
    const res = await fetch(`${BASE_URL}/incidents`);
    const result = await res.json();
    if (result.status === 'success') {
      incidents.value = result.data || [];
    }
  } catch (err) {
    console.error('Error fetching incidents:', err);
  } finally {
    loading.value = false;
  }
};

const fetchActivePersonnel = async () => {
  try {
    const res = await fetch(`${BASE_URL}/payrolls/active-personnel`);
    const result = await res.json();
    if (result.status === 'success') {
      activePersonnelList.value = result.data || [];
    }
  } catch (err) {
    console.error('Error fetching active personnel:', err);
  }
};

const openIncidentModal = () => {
  incidentForm.value = {
    personnel_id: '',
    texto: '',
    fecha: new Date().toISOString().split('T')[0],
    motivo: '',
    adjuntos: []
  };
  showIncidentModal.value = true;
};

const closeIncidentModal = () => {
  showIncidentModal.value = false;
};

const handleIncidentFileChange = (e) => {
  const files = Array.from(e.target.files);
  incidentForm.value.adjuntos = files;
};

const getAdjuntosList = (adjuntoPath) => {
  if (!adjuntoPath) return [];
  try {
    if (typeof adjuntoPath === 'string' && (adjuntoPath.startsWith('[') || adjuntoPath.startsWith('{'))) {
      const parsed = JSON.parse(adjuntoPath);
      if (Array.isArray(parsed)) return parsed;
    }
    return [adjuntoPath];
  } catch {
    return [adjuntoPath];
  }
};

const getFileUrl = (path) => {
  if (!path) return '';
  return `/concretos-oriente/Backend/${path}?t=${Date.now()}`;
};

const viewIncidentDetail = (inc) => {
  selectedIncident.value = inc;
};

const submitIncident = async () => {
  isSubmitting.value = true;
  try {
    const fd = new FormData();
    fd.append('personnel_id', incidentForm.value.personnel_id);
    fd.append('texto',        incidentForm.value.texto);
    fd.append('fecha',        incidentForm.value.fecha);
    fd.append('motivo',       incidentForm.value.motivo);
    if (incidentForm.value.adjuntos && incidentForm.value.adjuntos.length > 0) {
      incidentForm.value.adjuntos.forEach((file) => {
        fd.append('adjuntos[]', file);
      });
    }

    const res = await fetch(`${BASE_URL}/incidents`, { method: 'POST', body: fd });
    const result = await res.json();

    if (result.status === 'success') {
      await fetchIncidents();
      closeIncidentModal();
      Swal.fire({ ...swalBase, title: '¡Guardado!', text: 'Incidencia registrada correctamente.', icon: 'success' });
    } else {
      Swal.fire({ ...swalBase, title: 'Error', text: result.message || 'Error al guardar', icon: 'error' });
    }
  } catch (err) {
    console.error('Error submitting incident:', err);
    Swal.fire({ ...swalBase, title: 'Error', text: 'Error de conexión al servidor', icon: 'error' });
  } finally {
    isSubmitting.value = false;
  }
};

const deleteIncident = async (id) => {
  const result = await Swal.fire({
    ...swalBase,
    title: '¿Eliminar incidencia?',
    text: 'Esta acción no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#f43f5e',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  });

  if (!result.isConfirmed) return;

  try {
    const res = await fetch(`${BASE_URL}/incidents/${id}`, { method: 'DELETE' });
    const data = await res.json();
    if (data.status === 'success') {
      await fetchIncidents();
      Swal.fire({ ...swalBase, title: '¡Eliminado!', text: 'Incidencia eliminada correctamente.', icon: 'success' });
    } else {
      Swal.fire({ ...swalBase, title: 'Error', text: data.message || 'Error al eliminar', icon: 'error' });
    }
  } catch (err) {
    Swal.fire({ ...swalBase, title: 'Error', text: 'Error de conexión al servidor', icon: 'error' });
  }
};

const getMotivoBadge = (motivo) => {
  const map = {
    'Falta Injustificada': 'bg-rose-500/10 text-rose-400 border-rose-500/20',
    'Llegada Tardía': 'bg-amber-500/10 text-amber-300 border-amber-500/20',
    'Llamada de Atención': 'bg-yellow-500/10 text-yellow-300 border-yellow-500/20',
    'Amonestación Verbal': 'bg-orange-500/10 text-orange-300 border-orange-500/20',
    'Amonestación Escrita': 'bg-rose-500/20 text-rose-300 border-rose-500/30 font-black',
    'Incumplimiento de Funciones': 'bg-purple-500/10 text-purple-300 border-purple-500/20',
    'Conducta Inapropiada': 'bg-red-500/10 text-red-400 border-red-500/20',
    'Suspensión': 'bg-red-500/20 text-red-300 border-red-500/40 font-black',
  };
  return map[motivo] || 'bg-white/10 text-white/70 border-white/10';
};

const formatDate = (val) => {
  if (!val) return '';
  const [y, m, d] = val.split('-');
  return `${d}/${m}/${y}`;
};
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
