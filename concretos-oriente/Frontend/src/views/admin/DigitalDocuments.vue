<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">

    <!-- Toast Notification -->
    <div
      v-if="notification"
      class="fixed top-24 right-10 z-50 bg-[#0052ff]/20 border border-primary text-white backdrop-blur-xl px-6 py-4 rounded-2xl flex items-center gap-3 shadow-2xl shadow-primary/20"
    >
      <CheckCircleIcon class="w-5 h-5 text-primary" />
      <span class="text-xs font-black uppercase tracking-wider">{{ notification }}</span>
    </div>

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Documentos Digitales</h2>
        <div class="flex items-center gap-2 text-white/40 text-xs font-extrabold uppercase tracking-widest">
          <span class="cursor-pointer hover:text-white" @click="selectedFolderId = null">Mi Unidad</span>
          <template v-if="selectedFolderId">
            <ChevronRightIcon class="w-3.5 h-3.5" />
            <span class="text-primary tracking-wider">{{ folders.find(f => f.id === selectedFolderId)?.name }}</span>
          </template>
        </div>
      </div>

      <div class="flex flex-wrap gap-4">
        <button
          @click="showUploadModal = true"
          class="glass-button-primary bg-primary border-primary border text-white px-8 py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl flex items-center gap-2.5 hover:scale-105 active:scale-95 transition-all shadow-primary/20"
        >
          <ArrowUpTrayIcon class="w-4 h-4" /> Subir Archivo
        </button>
      </div>
    </div>

    <!-- Filters & View Toggles -->
    <div class="flex flex-col md:flex-row justify-between items-center gap-6 border-b border-white/5 pb-6">
      <div class="flex gap-2 bg-white/5 p-1.5 rounded-2xl border border-white/5 overflow-x-auto w-full md:w-auto">
        <button
          v-for="cat in categories"
          :key="cat.value"
          @click="selectedCategory = cat.value; if (cat.value === 'all') selectedFolderId = null"
          :class="[
            'px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap',
            selectedCategory === cat.value ? 'bg-primary text-white shadow-lg shadow-primary/25' : 'text-white/40 hover:text-white'
          ]"
        >
          {{ cat.label }}
        </button>
      </div>

      <div class="flex items-center gap-4 w-full md:w-auto justify-end">
        <div class="relative w-full md:w-64">
          <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-white/40" />
          <input
            type="text"
            v-model="searchTerm"
            placeholder="Buscar documentos..."
            class="glass-input pl-10 pr-4 py-3 rounded-xl text-xs uppercase tracking-wider font-extrabold w-full text-white placeholder:text-white/20"
          />
        </div>

        <div class="flex gap-1.5 bg-white/5 p-1 rounded-xl">
          <button
            @click="viewMode = 'grid'"
            :class="['p-2 rounded-lg transition-transform', viewMode === 'grid' ? 'bg-primary text-white' : 'text-white/30']"
          >
            <Squares2X2Icon class="w-4 h-4" />
          </button>
          <button
            @click="viewMode = 'list'"
            :class="['p-2 rounded-lg transition-transform', viewMode === 'list' ? 'bg-primary text-white' : 'text-white/30']"
          >
            <ListBulletIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Folder Grid -->
    <section class="space-y-6">
      <h3 class="text-xl font-black italic uppercase tracking-wider flex items-center gap-2">
        <FolderIcon class="w-5 h-5 text-primary" /> Carpetas de Proyecto
      </h3>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <div
          v-for="fold in folders"
          :key="fold.id"
          @click="selectedFolderId = selectedFolderId === fold.id ? null : fold.id"
          :class="[
            'glass-card p-8 rounded-3xl border transition-all cursor-pointer relative group flex flex-col justify-between h-44',
            selectedFolderId === fold.id ? 'bg-primary/10 border-primary' : 'border-white/5 hover:border-white/10 hover:bg-white/5'
          ]" data-aos="zoom-in-up" data-aos-duration="1000"
        >
          <div class="flex justify-between items-start">
            <FolderIcon :class="['w-10 h-10', fold.colorClass]" />
            <button class="text-white/35 group-hover:text-white transition-colors" @click.stop>
              <EllipsisVerticalIcon class="w-4 h-4" />
            </button>
          </div>
          <div>
            <h4 class="font-extrabold text-white text-base tracking-tight uppercase italic group-hover:text-primary transition-colors">{{ fold.name }}</h4>
            <p class="text-[10px] font-bold text-white/30 tracking-wider uppercase mt-1">
              {{ fold.filesCount.toLocaleString() }} archivos • {{ fold.size }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Documents Section -->
    <section class="space-y-6">
      <div class="flex justify-between items-center pb-2">
        <h3 class="text-xl font-black italic uppercase tracking-wider flex items-center gap-2">
          <BookOpenIcon class="w-5 h-5 text-primary" /> Archivos Registrados
        </h3>
        <button
          v-if="selectedFolderId"
          @click="selectedFolderId = null"
          class="text-primary font-black uppercase tracking-widest text-[9.5px] hover:text-white transition-colors"
        >
          Cerrar Filtro de Carpeta
        </button>
      </div>

      <!-- Grid View -->
      <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div v-if="filteredFiles.length === 0" class="col-span-full py-16 text-center text-white/30 font-black uppercase tracking-widest text-xs">
          No se han encontrado archivos en este directorio con los filtros provistos.
        </div>
        <div
          v-for="file in filteredFiles"
          :key="file.id"
          @click="openDetails(file)"
          class="glass-card rounded-3xl overflow-hidden border border-white/5 group hover:border-white/10 transition-all shadow-xl flex flex-col justify-between cursor-pointer" data-aos="zoom-in-up" data-aos-duration="1000"
        >
          <div class="h-44 bg-white/5 relative overflow-hidden flex items-center justify-center">
            <img
              v-if="['jpg','jpeg','png'].includes(file.ext)"
              :src="getBackendUrl(file.path)"
              :alt="file.name"
              class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-transform duration-500 group-hover:scale-105"
            />
            <div v-else class="flex flex-col items-center gap-2 text-white/30">
              <DocumentTextIcon class="w-12 h-12 text-primary/60" />
              <span class="text-[10px] font-black uppercase tracking-[0.2em]">{{ file.ext }}</span>
            </div>
            <span class="absolute top-4 left-4 bg-slate-950/80 border border-white/10 text-white font-black text-[9px] px-3 py-1 rounded-xl uppercase tracking-widest">
              {{ file.ext }}
            </span>
          </div>

          <div class="p-6 space-y-4">
            <div class="min-w-0">
              <h4 class="font-extrabold text-white text-sm tracking-tight truncate uppercase italic">{{ file.name }}</h4>
              <p class="text-[10px] font-bold text-white/30 tracking-wider uppercase mt-1">
                Modificado {{ file.modifiedAt }} • {{ file.modifiedBy }}
              </p>
            </div>
            <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-widest border-t border-white/5 pt-4">
              <span class="text-white/40">{{ file.size }}</span>
              <div class="flex gap-2">
                <button
                  @click.stop="handleDownload(file.path)"
                  class="p-2 bg-white/5 hover:bg-white/10 text-white/70 hover:text-white rounded-lg transition-colors border border-white/5"
                >
                  <ArrowDownTrayIcon class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- List View -->
      <div v-else class="glass-card rounded-[32px] overflow-hidden border border-white/5 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="text-[10px] font-extrabold text-white/30 uppercase tracking-widest border-b border-white/5 bg-white/5">
                <th class="px-10 py-5">Nombre del archivo</th>
                <th class="px-10 py-5">Modificado</th>
                <th class="px-10 py-5">Ubicación</th>
                <th class="px-10 py-5 text-right">Tamaño</th>
                <th class="px-10 py-5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-for="file in filteredFiles" :key="file.id" @click="openDetails(file)" class="hover:bg-white/5 transition-all cursor-pointer">
                <td class="px-10 py-5">
                  <div class="flex items-center gap-3">
                    <DocumentTextIcon class="w-4 h-4 text-primary" />
                    <span class="font-extrabold uppercase italic tracking-tight text-white">{{ file.name }}</span>
                  </div>
                </td>
                <td class="px-10 py-5 text-xs font-bold text-white/50 lowercase">{{ file.modifiedAt }} por {{ file.modifiedBy }}</td>
                <td class="px-10 py-5">
                  <span class="text-[10px] font-black tracking-wider uppercase text-primary bg-primary/20 px-2.5 py-1 rounded">{{ file.folderId }}</span>
                </td>
                <td class="px-10 py-5 text-right font-black italic text-sm text-white/70">{{ file.size }}</td>
                <td class="px-10 py-5 text-right space-x-1">
                  <button @click.stop="handleDownload(file.path)" class="p-2 hover:bg-white/5 text-white/40 hover:text-white rounded-xl transition-colors inline-block">
                    <ArrowDownTrayIcon class="w-4 h-4" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- FAB Upload Button -->
    <div
      @click="showUploadModal = true"
      class="fixed bottom-10 right-10 w-16 h-16 bg-primary rounded-full shadow-[0_4px_32px_rgba(0,82,255,0.4)] hover:shadow-[0_4px_32px_rgba(0,82,255,0.6)] flex items-center justify-center cursor-pointer hover:scale-110 active:scale-95 transition-all group z-40"
    >
      <ArrowUpTrayIcon class="w-6 h-6 text-white group-hover:rotate-12 transition-transform" />
      <span class="absolute right-20 bg-slate-900 border border-white/10 text-white font-black text-[9px] px-3 py-1.5 rounded-xl uppercase tracking-widest pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
        Cargar Nuevo Archivo
      </span>
    </div>

    <!-- Upload Modal -->
    <div v-if="showUploadModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
      <div class="absolute inset-0 cursor-pointer" @click="showUploadModal = false"></div>
      <div class="relative w-full max-w-xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white" data-aos="zoom-in-up" data-aos-duration="1000">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Cargar Archivo</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Arrastre o seleccione el archivo digital para su almacenamiento</p>
        <form @submit.prevent="handleUploadFile" class="space-y-6">
          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Tipo de documento</label>
              <select
                v-model="form.tipo_documento"
                class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold uppercase text-white focus:outline-none"
              >
                <option value="Contrato" class="bg-slate-950 text-white">Contrato</option>
                <option value="Factura" class="bg-slate-950 text-white">Factura</option>
                <option value="Cheque" class="bg-slate-950 text-white">Cheque</option>
                <option value="Fotografía" class="bg-slate-950 text-white">Fotografía</option>
                <option value="Licencia" class="bg-slate-950 text-white">Licencia</option>
                <option value="Manual" class="bg-slate-950 text-white">Manual</option>
                <option value="Otro" class="bg-slate-950 text-white">Otro</option>
              </select>
            </div>
            <div class="space-y-2" v-if="form.tipo_documento === 'Otro'">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Especificar Otro</label>
              <input
                type="text"
                v-model="form.tipo_documento_otro"
                required
                placeholder="Escriba el tipo de documento"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold uppercase text-white"
              />
            </div>
            <div class="space-y-2" v-else>
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Módulo relacionado (Opcional)</label>
              <select
                v-model="form.modulo_relacionado"
                class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold uppercase text-white focus:outline-none"
              >
                <option value="" class="bg-slate-950 text-white">Ninguno</option>
                <option value="Finanzas" class="bg-slate-950 text-white">Finanzas</option>
                <option value="RRHH" class="bg-slate-950 text-white">Recursos Humanos</option>
                <option value="Mantenimiento" class="bg-slate-950 text-white">Mantenimiento</option>
                <option value="Inventario" class="bg-slate-950 text-white">Inventario</option>
                <option value="Proyectos" class="bg-slate-950 text-white">Proyectos</option>
                <option value="General" class="bg-slate-950 text-white">General</option>
              </select>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Proyecto asociado (Opcional)</label>
            <select
              v-model="form.project_id"
              class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold uppercase text-white focus:outline-none"
            >
              <option value="" class="bg-slate-950 text-white">Ninguno</option>
              <option v-for="f in folders" :key="f.id" :value="f.id" class="bg-slate-950 text-white">{{ f.name }}</option>
            </select>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Nombre o descripción del documento</label>
            <input
              type="text"
              required
              v-model="form.nombre_documento"
              placeholder="Ej. Contrato de Arrendamiento"
              class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
            />
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Etiquetas o palabras clave (Opcional)</label>
            <input
              type="text"
              v-model="form.etiquetas"
              placeholder="Ej. legal, enero, torre alfa"
              class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
            />
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Archivo Digital (PDF, Imagen, Excel)</label>
            <input
              type="file"
              required
              @change="handleFileSelected"
              class="w-full bg-white/5 border border-dashed border-white/20 rounded-2xl p-4 text-xs font-bold text-white file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-primary file:text-white hover:file:bg-primary/80 cursor-pointer"
            />
          </div>

          <div class="flex gap-4 pt-4">
            <button
              type="submit"
              class="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all disabled:opacity-50"
            >
              Subir Archivo Seguro
            </button>
            <button
              type="button"
              @click="showUploadModal = false"
              class="px-8 py-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-black text-white/50"
            >
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>
    <!-- Document Details Modal -->
    <div v-if="showDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
      <div class="absolute inset-0 cursor-pointer" @click="showDetailsModal = false"></div>
      <div class="relative w-full max-w-lg glass-card rounded-[40px] p-10 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white" data-aos="zoom-in-up" data-aos-duration="1000">
        <h3 class="text-2xl font-black text-white italic uppercase tracking-tighter mb-6">Detalles del Documento</h3>
        
        <div v-if="selectedDocument" class="space-y-4">
          <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-white/30">Nombre</p>
            <p class="text-sm font-extrabold uppercase text-white">{{ selectedDocument.name }}</p>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-[10px] font-black uppercase tracking-widest text-white/30">Tipo / Categoría</p>
              <p class="text-xs font-bold uppercase text-white">{{ selectedDocument.category }}</p>
            </div>
            <div>
              <p class="text-[10px] font-black uppercase tracking-widest text-white/30">Extensión</p>
              <p class="text-xs font-bold uppercase text-white">{{ selectedDocument.ext }}</p>
            </div>
            <div>
              <p class="text-[10px] font-black uppercase tracking-widest text-white/30">Proyecto</p>
              <p class="text-xs font-bold uppercase text-primary">{{ selectedDocument.projectName }}</p>
            </div>
            <div>
              <p class="text-[10px] font-black uppercase tracking-widest text-white/30">Módulo Relacionado</p>
              <p class="text-xs font-bold uppercase text-white">{{ selectedDocument.moduleRel }}</p>
            </div>
            <div>
              <p class="text-[10px] font-black uppercase tracking-widest text-white/30">Peso</p>
              <p class="text-xs font-bold uppercase text-white">{{ selectedDocument.size }}</p>
            </div>
            <div>
              <p class="text-[10px] font-black uppercase tracking-widest text-white/30">Fecha</p>
              <p class="text-xs font-bold uppercase text-white">{{ selectedDocument.modifiedAt }}</p>
            </div>
          </div>
          <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-white/30">Etiquetas</p>
            <p class="text-xs font-bold uppercase text-emerald-400">{{ selectedDocument.tags }}</p>
          </div>

          <div class="pt-6">
            <button
              @click="showDetailsModal = false"
              class="w-full glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
            >
              Cerrar Detalles
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '../../services/api'
import Swal from 'sweetalert2';
import { useAuthStore } from '../../stores/auth';
import {
  CheckCircleIcon,
  ChevronRightIcon,
  FolderIcon,
  FolderPlusIcon,
  DocumentTextIcon,
  MagnifyingGlassIcon,
  Squares2X2Icon,
  ListBulletIcon,
  ArrowUpTrayIcon,
  ArrowDownTrayIcon,
  ShareIcon,
  EllipsisVerticalIcon,
  ShieldCheckIcon,
  BookOpenIcon,
} from '@heroicons/vue/24/outline';



const authStore = useAuthStore();

const getBackendUrl = (path: string) => {
  if (!path) return '';
  return `/concretos-oriente/Backend/${path}`;
};

interface ProjectFolder {
  id: string;
  name: string;
  filesCount: number;
  size: string;
  colorClass: string;
}

interface DigitalFile {
  id: string;
  name: string;
  category: string;
  folderId: string;
  projectName: string;
  moduleRel: string;
  tags: string;
  size: string;
  modifiedAt: string;
  modifiedBy: string;
  ext: string;
  path: string;
}

interface Permit {
  id: string;
  name: string;
  project: string;
  status: 'vence_pronto' | 'vigente';
  expiresAt: string;
}

const viewMode = ref<'grid' | 'list'>('grid');
const selectedCategory = ref('all');
const searchTerm = ref('');
const selectedFolderId = ref<string | null>(null);

const showUploadModal = ref(false);
const showNewFolderModal = ref(false);
const notification = ref('');
const showDetailsModal = ref(false);
const selectedDocument = ref<DigitalFile | null>(null);

const form = ref({
  tipo_documento: 'Contrato',
  tipo_documento_otro: '',
  project_id: '',
  modulo_relacionado: '',
  nombre_documento: '',
  etiquetas: '',
  archivo: null as File | null
});

const folderName = ref('');

const categories = [
  { value: 'all', label: 'Todos' },
  { value: 'Contrato', label: 'Contratos' },
  { value: 'Factura', label: 'Facturas' },
  { value: 'Cheque', label: 'Cheques' },
  { value: 'Licencia', label: 'Licencias' },
  { value: 'Fotografía', label: 'Fotografías' },
  { value: 'Manual', label: 'Manuales' },
  { value: 'Otro', label: 'Otros' },
];

const folders = ref<ProjectFolder[]>([]);
const files = ref<DigitalFile[]>([]);
const permits = ref<Permit[]>([]);

onMounted(() => {
  fetchProjects();
  fetchDocuments();
});

const fetchProjects = async () => {
  try {
    const res = await api.get(`/projects`);
    if (res.data.status === 'success') {
      folders.value = res.data.data.map((p: any) => ({
        id: p.id,
        name: p.nombre,
        filesCount: 0, // Placeholder
        size: '0 MB', // Placeholder
        colorClass: 'text-indigo-400'
      }));
    }
  } catch (error) { console.error(error); }
};

const fetchDocuments = async () => {
  try {
    const res = await api.get(`/documents`);
    if (res.data.status === 'success') {
      files.value = res.data.data.map((d: any) => ({
        id: d.id,
        name: d.nombre_documento,
        category: d.tipo_documento,
        folderId: d.project_id,
        projectName: d.proyecto_nombre || 'Ninguno',
        moduleRel: d.modulo_relacionado || 'Ninguno',
        tags: d.etiquetas || 'Sin etiquetas',
        size: (d.peso_archivo / 1024 / 1024).toFixed(2) + ' MB',
        modifiedAt: new Date(d.created_at).toLocaleDateString(),
        modifiedBy: 'Sistema',
        ext: d.tipo_archivo,
        path: d.archivo_path
      }));
    }
  } catch (error) { console.error(error); }
};

const filteredFiles = computed(() =>
  files.value.filter((f) => {
    const matchesSearch = f.name.toLowerCase().includes(searchTerm.value.toLowerCase());
    const matchesCategory = selectedCategory.value === 'all' || f.category === selectedCategory.value;
    const matchesFolder = selectedFolderId.value === null || f.folderId === selectedFolderId.value;
    return matchesSearch && matchesCategory && matchesFolder;
  })
);

function showToast(msg: string) {
  notification.value = msg;
  setTimeout(() => {
    notification.value = '';
  }, 4500);
}

function handleCreateFolder() {
  if (!folderName.value.trim()) return;
  Swal.fire('Aviso', 'Para crear un proyecto nuevo, por favor vaya al módulo de Proyectos.', 'info');
  showNewFolderModal.value = false;
}

const handleFileSelected = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    form.value.archivo = target.files[0];
  }
};

async function handleUploadFile() {
  try {
    const formData = new FormData();
    formData.append('tipo_documento', form.value.tipo_documento);
    if (form.value.tipo_documento === 'Otro') formData.append('tipo_documento_otro', form.value.tipo_documento_otro);
    formData.append('project_id', form.value.project_id);
    formData.append('modulo_relacionado', form.value.modulo_relacionado);
    formData.append('nombre_documento', form.value.nombre_documento);
    formData.append('etiquetas', form.value.etiquetas);
    if (form.value.archivo) {
      formData.append('archivo', form.value.archivo);
    } else {
      return Swal.fire('Atención', 'Seleccione un archivo.', 'warning');
    }

    let userId = authStore.userRole || 'admin';
    formData.append('usuario_id', userId);

    const res = await api.post(`/documents`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    if (res.data.status === 'success') {
      showToast('Documento subido exitosamente');
      showUploadModal.value = false;
      
      // Reset form
      form.value = {
        tipo_documento: 'Contrato',
        tipo_documento_otro: '',
        project_id: '',
        modulo_relacionado: '',
        nombre_documento: '',
        etiquetas: '',
        archivo: null
      };
      
      fetchDocuments();
    } else {
      Swal.fire('Error', res.data.message, 'error');
    }
  } catch (error) {
    Swal.fire('Error', 'No se pudo cargar el archivo', 'error');
  }
}

function handleDownload(path: string) {
  window.open(getBackendUrl(path), '_blank');
}

function openDetails(file: DigitalFile) {
  selectedDocument.value = file;
  showDetailsModal.value = true;
}
</script>
