<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 min-h-screen text-white relative">
    <header class="flex flex-col md:flex-row md:items-end justify-between gap-10 bg-white/5 p-10 rounded-[48px] border border-white/10 backdrop-blur-xl">
      <div class="space-y-3">
        <h1 class="text-5xl font-black tracking-tighter uppercase italic">Portafolio de Proyectos</h1>
        <p class="text-white/60 text-lg font-medium leading-relaxed max-w-xl">Supervisión de ciclos de vida de construcción y desempeño de socios en todos los sitios activos.</p>
      </div>

      <div class="flex p-2 bg-black/20 rounded-[28px] shadow-inner border border-white/10 backdrop-blur-xl">
        <button 
          @click="view = 'projects'"
          :class="`px-10 py-3.5 rounded-2xl text-xs font-black uppercase tracking-widest transition-all ${
            view === 'projects' ? 'bg-primary text-white shadow-[0_0_20px_rgba(99,102,241,0.4)]' : 'text-white/40 hover:text-white'
          }`"
        >
          Proyectos
        </button>
      </div>
    </header>

    <transition name="fade-slide" mode="out-in">
      <section v-if="view === 'projects'" key="projects">
        
        <div v-if="loading" class="text-center py-20">
          <p class="text-white/50 text-xl font-bold uppercase tracking-widest animate-pulse">Cargando Proyectos...</p>
        </div>
        
        <div v-else-if="projects.length === 0" class="text-center py-20 bg-white/5 rounded-[48px] border border-white/10">
          <p class="text-white/50 text-xl font-bold uppercase tracking-widest">No hay proyectos registrados</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
          <div
            v-for="proj in projects"
            :key="proj.id"
            @click="openProjectDetails(proj)"
            class="glass-card rounded-[48px] overflow-hidden group cursor-pointer border border-white/10 shadow-[0_24px_48px_-12px_rgba(0,0,0,0.5)] flex flex-col h-full hover:-translate-y-2 transition-transform duration-300 relative"
          >
            <div class="h-64 relative overflow-hidden shrink-0">
              <img :src="getPhotoUrl(proj)" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-1000" :alt="proj.nombre" />
              <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
              
              <div class="absolute top-6 right-6 px-4 py-2 backdrop-blur-2xl bg-white/10 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] border border-white/20 shadow-xl">
                <div class="flex items-center gap-2.5">
                  <span :class="`w-2 h-2 rounded-full ${getStatusColor(proj.estado)} shadow-[0_0_10px_currentColor]`"></span>
                  {{ proj.estado }}
                </div>
              </div>
            </div>

            <div class="p-8 flex flex-col flex-1 absolute bottom-0 left-0 right-0">
              <p class="text-xs font-bold text-primary mb-2 flex items-center gap-2.5 uppercase tracking-widest">
                <BuildingOfficeIcon class="w-4 h-4" /> {{ proj.codigo }}
              </p>
              <h3 class="text-2xl font-black text-white mb-2 leading-tight uppercase italic">{{ proj.nombre }}</h3>
              
              <div class="flex items-center justify-between pt-6 border-t border-white/10 mt-4">
                <div>
                  <p class="text-[10px] text-white/50 uppercase font-bold tracking-[0.2em] mb-1">Presupuesto</p>
                  <div class="flex items-center gap-2 font-black text-sm uppercase tracking-tighter italic text-white">
                    <CurrencyDollarIcon class="w-5 h-5 text-primary" />
                    <span>Q {{ formatCurrency(proj.presupuesto_contractual) }}</span>
                  </div>
                </div>
                <button class="w-12 h-12 rounded-2xl bg-white/10 hover:bg-primary transition-all flex items-center justify-center group-hover:shadow-[0_0_20px_rgba(99,102,241,0.4)]">
                  <ChevronRightIcon class="w-6 h-6 text-white group-hover:text-white" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>
    </transition>

    <!-- Modal Detalles del Proyecto -->
    <transition name="fade">
      <div v-if="selectedProject" class="fixed inset-0 z-50 flex items-center justify-center p-6">
        <div 
          @click="closeProjectDetails"
          class="absolute inset-0 bg-black/90 backdrop-blur-md"
        ></div>
        
        <div class="relative w-full max-w-5xl glass-card rounded-[40px] overflow-hidden border border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.5)] transform scale-100 transition-all duration-300">
          <div class="absolute top-6 right-6 z-10 flex gap-3">
            <button 
              @click="openEditModal(selectedProject)"
              class="w-12 h-12 rounded-2xl bg-white/10 hover:bg-primary flex items-center justify-center transition-all border border-white/10 text-white shadow-xl hover:shadow-primary/40"
              title="Editar"
            >
              <PencilIcon class="w-5 h-5" />
            </button>
            <button 
              @click="deleteProject(selectedProject.id)"
              class="w-12 h-12 rounded-2xl bg-white/10 hover:bg-tertiary flex items-center justify-center transition-all border border-white/10 text-white shadow-xl hover:shadow-tertiary/40"
              title="Eliminar"
            >
              <TrashIcon class="w-5 h-5" />
            </button>
            <button 
              @click="closeProjectDetails"
              class="w-12 h-12 rounded-2xl bg-white/10 hover:bg-white/20 flex items-center justify-center transition-all border border-white/10 text-white shadow-xl"
              title="Cerrar"
            >
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>

          <div class="flex flex-col lg:flex-row h-full max-h-[85vh] overflow-y-auto">
            <!-- Left: Media -->
            <div class="lg:w-2/5 relative bg-black/40 min-h-[300px]">
              <img :src="getPhotoUrl(selectedProject)" class="w-full h-full object-cover" :alt="selectedProject.nombre" />
              <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent"></div>
              <div class="absolute bottom-10 left-10 right-10">
                <span class="text-[10px] font-black uppercase tracking-[0.4em] text-primary mb-2 block">Cód: {{ selectedProject.codigo }}</span>
                <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter leading-tight">{{ selectedProject.nombre }}</h2>
              </div>
            </div>

            <!-- Right: Info -->
            <div class="lg:w-3/5 p-10 bg-black/20 overflow-y-auto">
              <div class="space-y-8">
                
                <div class="flex gap-4">
                  <div class="flex-1 glass-card p-6 rounded-3xl border border-white/5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/40 mb-3 flex items-center gap-2">
                      <ChartBarIcon class="w-4 h-4" /> Estado
                    </p>
                    <div class="flex items-center gap-3">
                      <div :class="`w-3 h-3 rounded-full ${getStatusColor(selectedProject.estado)} shadow-[0_0_10px_currentColor]`"></div>
                      <span class="text-lg font-black italic uppercase text-white">{{ selectedProject.estado }}</span>
                    </div>
                  </div>
                  <div class="flex-1 glass-card p-6 rounded-3xl border border-white/5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/40 mb-3 flex items-center gap-2">
                      <DocumentTextIcon class="w-4 h-4" /> Contrato No.
                    </p>
                    <span class="text-lg font-black uppercase text-white">{{ selectedProject.numero_contrato || 'N/A' }}</span>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-6 bg-white/5 p-6 rounded-3xl border border-white/5">
                  <div class="space-y-1">
                    <p class="text-[10px] font-black text-white/40 uppercase tracking-widest">Presupuesto Contractual</p>
                    <p class="text-xl font-black italic text-primary">Q {{ formatCurrency(selectedProject.presupuesto_contractual) }}</p>
                  </div>
                  <div class="space-y-1">
                    <p class="text-[10px] font-black text-white/40 uppercase tracking-widest">Gasto Real Acumulado</p>
                    <p :class="`text-xl font-black italic ${Number(selectedProject.gasto_real_acumulado) > Number(selectedProject.presupuesto_contractual) ? 'text-tertiary' : 'text-white'}`">
                      Q {{ formatCurrency(selectedProject.gasto_real_acumulado) }}
                    </p>
                  </div>
                </div>

                <div class="space-y-6">
                  <div>
                    <p class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 flex items-center gap-2"><MapPinIcon class="w-4 h-4" /> Ubicación</p>
                    <p class="text-base font-bold text-white">{{ selectedProject.nombre_ubicacion }}</p>
                    <a v-if="selectedProject.coordenadas" :href="`https://www.google.com/maps/search/?api=1&query=${selectedProject.coordenadas}`" target="_blank" class="text-primary text-xs font-bold mt-2 inline-block hover:underline">
                      Ver en Mapa ({{ selectedProject.coordenadas }})
                    </a>
                  </div>

                  <div>
                    <p class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 flex items-center gap-2"><UserIcon class="w-4 h-4" /> Contactos</p>
                    <p class="text-sm font-medium text-white/80 whitespace-pre-line">{{ selectedProject.contactos || 'Sin contactos registrados' }}</p>
                  </div>

                  <div>
                    <p class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 flex items-center gap-2"><BriefcaseIcon class="w-4 h-4" /> Descripción</p>
                    <p class="text-sm font-medium text-white/80 whitespace-pre-line">{{ selectedProject.descripcion || 'Sin descripción' }}</p>
                  </div>

                  <!-- Archivos Adjuntos -->
                  <div v-if="selectedProject.contratos_archivos && JSON.parse(selectedProject.contratos_archivos).length > 0">
                    <p class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-4 flex items-center gap-2">
                      <PaperClipIcon class="w-4 h-4" /> Archivos de Contrato
                    </p>
                    <div class="flex flex-col gap-3">
                      <a 
                        v-for="(archivo, i) in JSON.parse(selectedProject.contratos_archivos)" 
                        :key="i"
                        :href="`/concretos-oriente/Backend/${archivo}`" 
                        target="_blank"
                        class="flex items-center gap-4 bg-white/5 hover:bg-white/10 p-4 rounded-2xl border border-white/5 transition-all group"
                      >
                        <DocumentIcon class="w-6 h-6 text-primary group-hover:scale-110 transition-transform" />
                        <span class="text-sm font-bold text-white truncate flex-1">Documento {{ i + 1 }}</span>
                        <ArrowDownTrayIcon class="w-5 h-5 text-white/40 group-hover:text-white" />
                      </a>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Botón Añadir Proyecto -->
    <button @click="openModal" class="fixed bottom-12 right-12 h-20 w-20 rounded-[32px] glass-button-primary text-white shadow-2xl shadow-primary/40 flex items-center justify-center hover:scale-110 active:scale-95 transition-all z-40 group">
      <PlusIcon class="w-10 h-10 group-hover:rotate-90 transition-transform duration-500 shadow-[0_0_20px_rgba(99,102,241,0.5)]" />
    </button>

    <!-- Modal Formulario Proyecto -->
    <transition name="fade">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="closeModal"></div>
        
        <div class="glass-card w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-[40px] p-10 relative z-10 border border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.6)]">
          <div class="flex items-center justify-between mb-8 border-b border-white/10 pb-6">
            <h3 class="text-3xl font-black text-white italic uppercase tracking-tight">{{ isEditing ? 'Editar Proyecto' : 'Registrar Nuevo Proyecto' }}</h3>
            <button @click="closeModal" class="p-3 bg-white/5 hover:bg-white/10 rounded-2xl transition-all text-white/50 hover:text-white">
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>

          <form @submit.prevent="submitForm" class="space-y-8">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Datos Principales -->
              <div class="space-y-6">
                <div>
                  <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Código del Proyecto</label>
                  <input v-model="formData.codigo" type="text" required class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold" placeholder="Ej. PRJ-2024-001" />
                </div>
                <div>
                  <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Nombre del Proyecto</label>
                  <input v-model="formData.nombre" type="text" required class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold" placeholder="Ej. Construcción Fase 1" />
                </div>
                <div>
                  <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Estado Actual</label>
                  <select v-model="formData.estado" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold appearance-none">
                    <option value="Borrador">Borrador</option>
                    <option value="Activo">Activo</option>
                    <option value="En Pausa">En Pausa</option>
                    <option value="Completado">Completado</option>
                    <option value="Cancelado">Cancelado</option>
                  </select>
                </div>
                <div>
                  <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Número de Contrato</label>
                  <input v-model="formData.numero_contrato" type="text" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold" placeholder="Opcional" />
                </div>
              </div>

              <!-- Presupuesto y Ubicación -->
              <div class="space-y-6">
                <div>
                  <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Presupuesto Contractual (GTQ)</label>
                  <input v-model="formData.presupuesto_contractual" type="number" step="0.01" required class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold text-lg" placeholder="0.00" />
                </div>
                <div>
                  <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Gasto Real Acumulado (GTQ)</label>
                  <input v-model="formData.gasto_real_acumulado" type="number" step="0.01" required class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold text-lg" placeholder="0.00" />
                </div>
                <div>
                  <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Ubicación Geográfica (Haz clic en el mapa)</label>
                  <div class="space-y-3">
                    <div id="project-map" class="h-48 w-full rounded-2xl border border-white/10 z-0 relative z-0" style="z-index: 1;"></div>
                    <div class="flex gap-2">
                      <input v-model="formData.coordenadas" type="text" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold text-xs" placeholder="Latitud, Longitud" readonly />
                      <button type="button" @click="getLocation" class="px-5 bg-white/10 hover:bg-primary rounded-2xl transition-all" title="Obtener mi ubicación actual">
                        <MapPinIcon class="w-6 h-6 text-white" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Descripción y Contactos -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Contactos</label>
                <textarea v-model="formData.contactos" rows="3" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold resize-none" placeholder="Nombres, teléfonos, etc."></textarea>
              </div>
              <div>
                <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Descripción Detallada</label>
                <textarea v-model="formData.descripcion" rows="3" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold resize-none" placeholder="Escribe los detalles aquí..."></textarea>
              </div>
            </div>

            <!-- Archivos -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-white/5 p-6 rounded-3xl border border-white/10">
              <div>
                <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">
                  Foto de Portada <span v-if="isEditing" class="text-primary normal-case">(Opcional para mantener actual)</span>
                </label>
                <input @change="handleFotoChange" type="file" accept="image/*" :required="!isEditing" class="w-full text-white/60 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-primary/20 file:text-primary hover:file:bg-primary/30 file:transition-all cursor-pointer bg-black/40 border border-white/10 rounded-2xl p-2" />
              </div>
              <div>
                <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">
                  Archivos de Contrato (Max 3. PDF/DOC) <span v-if="isEditing" class="text-primary normal-case">(Al subir nuevos, reemplazarán los anteriores)</span>
                </label>
                <input @change="handleContratosChange" type="file" multiple accept=".pdf,.doc,.docx" class="w-full text-white/60 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-primary/20 file:text-primary hover:file:bg-primary/30 file:transition-all cursor-pointer bg-black/40 border border-white/10 rounded-2xl p-2" />
                
                <div v-if="formData.contratos && formData.contratos.length > 0" class="mt-4 space-y-2">
                  <div v-for="(file, i) in formData.contratos" :key="i" class="flex items-center justify-between bg-white/5 p-3 rounded-xl border border-white/5">
                    <span class="text-xs text-white/80 font-medium truncate flex-1">{{ file.name }}</span>
                    <button type="button" @click="removeContrato(i)" class="p-1 hover:bg-tertiary/20 hover:text-tertiary text-white/40 rounded-lg transition-all">
                      <XMarkIcon class="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Acciones -->
            <div class="pt-6 flex justify-end gap-4 border-t border-white/10">
              <button type="button" @click="closeModal" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/10 transition-all">
                Cancelar
              </button>
              <button type="submit" :disabled="isSubmitting" class="glass-button-primary text-white py-4 px-12 rounded-2xl font-black uppercase tracking-widest flex items-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                <span v-if="isSubmitting">Guardando...</span>
                <span v-else>{{ isEditing ? 'Actualizar' : 'Guardar Proyecto' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { 
  BuildingOfficeIcon, ChevronRightIcon, 
  PlusIcon, XMarkIcon, CalendarIcon, MapPinIcon, 
  ChartBarIcon, BriefcaseIcon, CurrencyDollarIcon, UserIcon,
  PencilIcon, TrashIcon, DocumentTextIcon, PaperClipIcon, DocumentIcon, ArrowDownTrayIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const BASE_URL = '/concretos-oriente/Backend/api/v1';

const view = ref("projects");
const projects = ref([]);
const loading = ref(true);
const selectedProject = ref(null);
const showModal = ref(false);
const isSubmitting = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const formData = ref({
  codigo: '',
  nombre: '',
  nombre_ubicacion: '',
  coordenadas: '',
  presupuesto_contractual: '',
  gasto_real_acumulado: '',
  estado: 'Borrador',
  numero_contrato: '',
  descripcion: '',
  contactos: '',
  foto: null,
  contratos: []
});

onMounted(() => {
  fetchProjects();
});

const fetchProjects = async () => {
  loading.value = true;
  try {
    const response = await fetch(`${BASE_URL}/projects`);
    const result = await response.json();
    if (result.status === 'success') {
      const fetchTime = Date.now();
      projects.value = result.data.map(proj => ({...proj, _t: fetchTime}));
    }
  } catch (error) {
    console.error("Error fetching projects:", error);
  } finally {
    loading.value = false;
  }
};

const getStatusColor = (status) => {
  switch (status) {
    case 'Activo': return 'bg-primary text-primary';
    case 'En Pausa': return 'bg-yellow-500 text-yellow-500';
    case 'Completado': return 'bg-green-500 text-green-500';
    case 'Cancelado': return 'bg-tertiary text-tertiary';
    default: return 'bg-white/40 text-white/40';
  }
};

const formatCurrency = (value) => {
  if (!value) return "0.00";
  return parseFloat(value).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const getPhotoUrl = (proj) => {
  if (!proj || !proj.foto) return 'https://images.unsplash.com/photo-1541888946425-d81bb19480c5?auto=format&fit=crop&q=80&w=2070'; // default fallback
  const timestamp = proj._t || Date.now();
  return `/concretos-oriente/Backend/${proj.foto}?t=${timestamp}`;
};

const openProjectDetails = (proj) => {
  selectedProject.value = proj;
};

const closeProjectDetails = () => {
  selectedProject.value = null;
};

const openModal = () => {
  resetForm();
  isEditing.value = false;
  editingId.value = null;
  showModal.value = true;
  initMap();
};

const openEditModal = (proj) => {
  formData.value = {
    codigo: proj.codigo,
    nombre: proj.nombre,
    nombre_ubicacion: proj.nombre_ubicacion,
    coordenadas: proj.coordenadas,
    presupuesto_contractual: proj.presupuesto_contractual,
    gasto_real_acumulado: proj.gasto_real_acumulado,
    estado: proj.estado,
    numero_contrato: proj.numero_contrato,
    descripcion: proj.descripcion,
    contactos: proj.contactos,
    foto: null,
    contratos: []
  };
  isEditing.value = true;
  editingId.value = proj.id;
  closeProjectDetails();
  showModal.value = true;
  
  if (proj.coordenadas) {
    const parts = proj.coordenadas.split(',');
    if (parts.length === 2) {
      initMap(parseFloat(parts[0]), parseFloat(parts[1]));
    } else {
      initMap();
    }
  } else {
    initMap();
  }
};

const closeModal = () => {
  showModal.value = false;
  resetForm();
};

const resetForm = () => {
  formData.value = {
    codigo: '',
    nombre: '',
    nombre_ubicacion: '',
    coordenadas: '',
    presupuesto_contractual: '',
    gasto_real_acumulado: '',
    estado: 'Borrador',
    numero_contrato: '',
    descripcion: '',
    contactos: '',
    foto: null,
    contratos: []
  };
};

let mapInstance = null;
let mapMarker = null;

const initMap = (lat = 14.6349, lng = -90.5069) => {
  nextTick(() => {
    if (mapInstance) {
      mapInstance.remove();
      mapInstance = null;
    }
    
    // Leaflet init (L is global from CDN)
    if (typeof L !== 'undefined') {
      mapInstance = L.map('project-map').setView([lat, lng], 13);
      
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
      }).addTo(mapInstance);

      if (formData.value.coordenadas) {
        mapMarker = L.marker([lat, lng]).addTo(mapInstance);
      }

      mapInstance.on('click', function(e) {
        const clickedLat = e.latlng.lat.toFixed(6);
        const clickedLng = e.latlng.lng.toFixed(6);
        formData.value.coordenadas = `${clickedLat}, ${clickedLng}`;
        
        if (mapMarker) {
          mapInstance.removeLayer(mapMarker);
        }
        mapMarker = L.marker([clickedLat, clickedLng]).addTo(mapInstance);
      });
      
      // Invalidate size to prevent grey tiles in hidden modals
      setTimeout(() => {
        mapInstance.invalidateSize();
      }, 300);
    }
  });
};

const handleFotoChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    formData.value.foto = file;
  }
};

const handleContratosChange = (e) => {
  const newFiles = Array.from(e.target.files);
  const totalFiles = formData.value.contratos.length + newFiles.length;
  
  if (totalFiles > 3) {
    Swal.fire({
      title: 'Demasiados archivos',
      text: 'Solo puedes subir hasta 3 archivos de contrato.',
      icon: 'warning',
      background: '#0f172a',
      color: '#fff',
      confirmButtonColor: '#6366f1',
      customClass: { popup: 'border border-white/10 rounded-3xl' }
    });
    e.target.value = ''; // Limpiar input
    return;
  }
  
  formData.value.contratos = [...formData.value.contratos, ...newFiles];
  e.target.value = ''; // Resetear el input para permitir seleccionar más
};

const removeContrato = (index) => {
  formData.value.contratos.splice(index, 1);
};

const getLocation = () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition((position) => {
      const lat = position.coords.latitude.toFixed(6);
      const lng = position.coords.longitude.toFixed(6);
      formData.value.coordenadas = `${lat}, ${lng}`;
      
      if (mapInstance) {
        if (mapMarker) mapInstance.removeLayer(mapMarker);
        mapMarker = L.marker([lat, lng]).addTo(mapInstance);
        mapInstance.setView([lat, lng], 15);
      }
    }, () => {
      Swal.fire({
        title: 'Error',
        text: 'No se pudo obtener la ubicación. Revisa los permisos del navegador.',
        icon: 'error',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
      });
    });
  }
};

const submitForm = async () => {
  isSubmitting.value = true;
  
  const data = new FormData();
  data.append('codigo', formData.value.codigo);
  data.append('nombre', formData.value.nombre);
  data.append('nombre_ubicacion', formData.value.nombre_ubicacion);
  data.append('coordenadas', formData.value.coordenadas);
  data.append('presupuesto_contractual', formData.value.presupuesto_contractual);
  data.append('gasto_real_acumulado', formData.value.gasto_real_acumulado);
  data.append('estado', formData.value.estado);
  data.append('numero_contrato', formData.value.numero_contrato);
  data.append('descripcion', formData.value.descripcion);
  data.append('contactos', formData.value.contactos);
  
  if (formData.value.foto) {
    data.append('foto', formData.value.foto);
  }
  
  if (formData.value.contratos && formData.value.contratos.length > 0) {
    formData.value.contratos.forEach(file => {
      data.append('contratos[]', file); // Array in FormData
    });
  }

  try {
    const url = isEditing.value ? `${BASE_URL}/projects/${editingId.value}` : `${BASE_URL}/projects`;
    
    const response = await fetch(url, {
      method: 'POST',
      body: data
    });
    
    const result = await response.json();
    if (result.status === 'success') {
      await fetchProjects();
      closeModal();
      Swal.fire({
        title: '¡Éxito!',
        text: isEditing.value ? 'Proyecto actualizado.' : 'Proyecto creado.',
        icon: 'success',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
        customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
      });
    } else {
      throw new Error(result.message);
    }
  } catch (error) {
    console.error("Error submitting project:", error);
    Swal.fire({
      title: 'Error',
      text: error.message || 'Error de conexión al servidor',
      icon: 'error',
      background: '#0f172a',
      color: '#fff',
      confirmButtonColor: '#6366f1',
      customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
    });
  } finally {
    isSubmitting.value = false;
  }
};

const deleteProject = async (id) => {
  const result = await Swal.fire({
    title: '¿Estás seguro?',
    text: "Se eliminará el proyecto y todos sus archivos. Esta acción es irreversible.",
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

  if (!result.isConfirmed) return;
  
  try {
    const response = await fetch(`${BASE_URL}/projects/${id}`, {
      method: 'DELETE'
    });
    
    const res = await response.json();
    if (res.status === 'success') {
      await fetchProjects();
      closeProjectDetails();
      Swal.fire({
        title: '¡Eliminado!',
        text: 'El proyecto fue borrado.',
        icon: 'success',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
        customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
      });
    } else {
      throw new Error(res.message);
    }
  } catch (error) {
    Swal.fire({
      title: 'Error',
      text: error.message || 'Error al eliminar',
      icon: 'error',
      background: '#0f172a',
      color: '#fff',
      confirmButtonColor: '#6366f1',
    });
  }
};
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: opacity 0.4s ease-out, transform 0.4s ease-out;
}
.fade-slide-enter-from {
  opacity: 0;
  transform: scale(0.98);
}
.fade-slide-leave-to {
  opacity: 0;
  transform: scale(0.98);
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
