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
          @click="showNewFolderModal = true"
          class="flex items-center gap-2.5 px-6 py-4 rounded-2xl border border-white/5 bg-white/5 text-white/80 font-black text-xs uppercase tracking-widest hover:bg-white/10 hover:scale-105 active:scale-95 transition-all"
        >
          <FolderPlusIcon class="w-4 h-4 text-primary" /> Nueva Carpeta
        </button>
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
          ]"
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

        <!-- Create Folder Card -->
        <div
          @click="showNewFolderModal = true"
          class="bg-white/5 border-2 border-dashed border-white/5 p-8 rounded-3xl hover:bg-white/10 transition-all cursor-pointer flex flex-col items-center justify-center text-center h-44"
        >
          <FolderPlusIcon class="w-8 h-8 text-white/30 mb-2" />
          <p class="text-[10px] font-black uppercase tracking-widest text-white/40">Nueva Carpeta de Proyecto</p>
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
          class="glass-card rounded-3xl overflow-hidden border border-white/5 group hover:border-white/10 transition-all shadow-xl flex flex-col justify-between"
        >
          <div class="h-44 bg-white/5 relative overflow-hidden flex items-center justify-center">
            <img
              v-if="file.thumbnail"
              :src="file.thumbnail"
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
                  @click="handleDownload(file.name)"
                  class="p-2 bg-white/5 hover:bg-white/10 text-white/70 hover:text-white rounded-lg transition-colors border border-white/5"
                >
                  <ArrowDownTrayIcon class="w-3.5 h-3.5" />
                </button>
                <button
                  @click="handleShare(file.name)"
                  class="p-2 bg-white/5 hover:bg-white/10 text-white/70 hover:text-white rounded-lg transition-colors border border-white/5"
                >
                  <ShareIcon class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- List View -->
      <div v-else class="glass-card rounded-[32px] overflow-hidden border border-white/5 shadow-2xl">
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
              <tr v-for="file in filteredFiles" :key="file.id" class="hover:bg-white/5 transition-all">
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
                  <button @click="handleDownload(file.name)" class="p-2 hover:bg-white/5 text-white/40 hover:text-white rounded-xl transition-colors inline-block">
                    <ArrowDownTrayIcon class="w-4 h-4" />
                  </button>
                  <button @click="handleShare(file.name)" class="p-2 hover:bg-white/5 text-white/40 hover:text-white rounded-xl transition-colors inline-block">
                    <ShareIcon class="w-4 h-4" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- Licenses & Permits Section -->
    <section class="space-y-6">
      <h3 class="text-xl font-black italic uppercase tracking-wider flex items-center gap-2">
        <ShieldCheckIcon class="w-5 h-5 text-primary" /> Licencias y Permisos Próximos a Vencer
      </h3>

      <div class="glass-card rounded-[40px] overflow-hidden border border-white/5 shadow-2xl">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="text-[10px] font-extrabold text-white/30 uppercase tracking-widest border-b border-white/5 bg-white/5">
                <th class="px-10 py-5">Documento</th>
                <th class="px-10 py-5">Proyecto Asociado</th>
                <th class="px-10 py-5 text-center">Estado</th>
                <th class="px-10 py-5 text-center">Vencimiento oficial</th>
                <th class="px-10 py-5 text-right">Acción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-for="p in permits" :key="p.id" class="hover:bg-white/5 transition-all">
                <td class="px-10 py-6">
                  <div class="flex items-center gap-3">
                    <ShieldCheckIcon class="w-5 h-5 text-emerald-400" />
                    <span class="font-extrabold text-base text-white tracking-tight uppercase italic">{{ p.name }}</span>
                  </div>
                </td>
                <td class="px-10 py-6 text-sm text-white/60 font-black uppercase">{{ p.project }}</td>
                <td class="px-10 py-6 text-center">
                  <span :class="[
                    'px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border',
                    p.status === 'vence_pronto'
                      ? 'bg-amber-500/10 text-amber-400 border-amber-500/20'
                      : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
                  ]">
                    {{ p.status === 'vence_pronto' ? 'Vence Pronto' : 'Vigente' }}
                  </span>
                </td>
                <td class="px-10 py-6 text-center text-xs font-black text-rose-400">{{ p.expiresAt }}</td>
                <td class="px-10 py-6 text-right">
                  <button
                    @click="handleDownload(p.name)"
                    class="p-2.5 bg-white/5 border border-white/5 rounded-xl text-white/60 hover:text-white transition-all hover:scale-105 active:scale-95 inline-block"
                  >
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
      <div class="relative w-full max-w-xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Cargar Archivo</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Arrastre o seleccione el archivo digital para su almacenamiento</p>

        <form @submit.prevent="handleUploadFile" class="space-y-6">
          <div class="border-2 border-dashed border-white/10 rounded-3xl p-8 flex flex-col items-center justify-center text-center bg-white/[0.01] hover:bg-white/[0.03] transition-colors cursor-pointer">
            <ArrowUpTrayIcon class="w-10 h-10 text-primary/60 mb-3" />
            <p class="text-xs font-black uppercase tracking-widest text-white/50">Arrastra tus archivos aquí</p>
            <p class="text-[10px] font-bold text-white/20 uppercase tracking-wider mt-1">O haz clic para explorar en el disco local</p>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Nombre De Referencia o Archivo</label>
            <input
              type="text"
              required
              v-model="fileName"
              placeholder="E.g. Plano de Planta Baja"
              class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
            />
          </div>

          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Categoría General</label>
              <select
                v-model="fileCat"
                class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold uppercase text-white focus:outline-none"
              >
                <option value="planos" class="bg-slate-950 text-white">Planos</option>
                <option value="contratos" class="bg-slate-950 text-white">Contratos</option>
                <option value="licencias" class="bg-slate-950 text-white">Licencias</option>
                <option value="evidencia" class="bg-slate-950 text-white">Evidencia</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Extensión / Tipo</label>
              <select
                v-model="fileExtension"
                class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold uppercase text-white focus:outline-none"
              >
                <option value="pdf" class="bg-slate-950 text-white">PDF Document</option>
                <option value="dwg" class="bg-slate-950 text-white">DWG Blueprint</option>
                <option value="docx" class="bg-slate-950 text-white">Microsoft Word</option>
                <option value="xlsx" class="bg-slate-950 text-white">Excel Spreadsheet</option>
                <option value="png" class="bg-slate-950 text-white">PNG Image</option>
              </select>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Asignar a Carpeta de Destino</label>
            <select
              v-model="fileFolder"
              class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold uppercase text-white focus:outline-none"
            >
              <option v-for="f in folders" :key="f.id" :value="f.id" class="bg-slate-950 text-white">{{ f.name }}</option>
            </select>
          </div>

          <div class="flex gap-4 pt-4">
            <button
              type="submit"
              class="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
            >
              Confirmar Guardado
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

    <!-- New Folder Modal -->
    <div v-if="showNewFolderModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
      <div class="absolute inset-0 cursor-pointer" @click="showNewFolderModal = false"></div>
      <div class="relative w-full max-w-md glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Nueva Carpeta</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Ingrese el nombre identificador para el nuevo directorio lógico de obra</p>

        <form @submit.prevent="handleCreateFolder" class="space-y-6">
          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Nombre de la Carpeta</label>
            <input
              type="text"
              required
              v-model="folderName"
              placeholder="E.g. Condominio El Prado - Fase Final"
              class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
            />
          </div>

          <div class="flex gap-4 pt-4">
            <button
              type="submit"
              class="flex-grow glass-button-primary bg-primary border border-primary text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
            >
              Crear Directorio
            </button>
            <button
              type="button"
              @click="showNewFolderModal = false"
              class="px-8 py-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-black text-white/50"
            >
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
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
  category: 'all' | 'planos' | 'contratos' | 'licencias' | 'evidencia';
  folderId: string;
  size: string;
  modifiedAt: string;
  modifiedBy: string;
  ext: 'pdf' | 'dwg' | 'docx' | 'xlsx' | 'png';
  thumbnail?: string;
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

const folderName = ref('');
const fileName = ref('');
const fileCat = ref('planos');
const fileExtension = ref('pdf');
const fileFolder = ref('Alpha');

const categories = [
  { value: 'all', label: 'Todos' },
  { value: 'planos', label: 'Planos (PDF/DWG)' },
  { value: 'contratos', label: 'Contratos' },
  { value: 'licencias', label: 'Licencias' },
  { value: 'evidencia', label: 'Evidencia Obra' },
];

const folders = ref<ProjectFolder[]>([
  { id: 'Alpha', name: 'Torre Residencial Alpha', filesCount: 2450, size: '1.2 GB', colorClass: 'text-primary' },
  { id: 'Sur', name: 'Puente Interconector Sur', filesCount: 890, size: '450 MB', colorClass: 'text-indigo-400' },
  { id: 'SkyPort', name: 'Centro Logístico SkyPort', filesCount: 156, size: '89 MB', colorClass: 'text-emerald-400' },
]);

const files = ref<DigitalFile[]>([
  {
    id: 'DOC-1',
    name: 'Plano_Estructural_V2.pdf',
    category: 'planos',
    folderId: 'Alpha',
    size: '18.4 MB',
    modifiedAt: 'hace 2h',
    modifiedBy: 'Ing. Carlos Ruiz',
    ext: 'pdf',
    thumbnail: 'https://images.unsplash.com/photo-1504917595217-d4dc5ebe6122?auto=format&fit=crop&w=400&q=80',
  },
  {
    id: 'DOC-2',
    name: 'Instalaciones_Hidrosanitarias.dwg',
    category: 'planos',
    folderId: 'Alpha',
    size: '45.2 MB',
    modifiedAt: 'ayer',
    modifiedBy: 'Arq. M. Lopez',
    ext: 'dwg',
  },
  {
    id: 'DOC-3',
    name: 'Contrato_Suministros_Aceros.docx',
    category: 'contratos',
    folderId: 'Sur',
    size: '1.4 MB',
    modifiedAt: 'hace 3d',
    modifiedBy: 'Jurídico',
    ext: 'docx',
    thumbnail: 'https://images.unsplash.com/photo-1450133064473-71024230f91b?auto=format&fit=crop&w=400&q=80',
  },
  {
    id: 'DOC-4',
    name: 'Presupuesto_General_Estructuras.xlsx',
    category: 'contratos',
    folderId: 'SkyPort',
    size: '4.8 MB',
    modifiedAt: 'hace 1 semana',
    modifiedBy: 'Aux. Contable',
    ext: 'xlsx',
  },
  {
    id: 'DOC-5',
    name: 'Permiso_Ambiental_Zona_Sur.pdf',
    category: 'licencias',
    folderId: 'Sur',
    size: '2.1 MB',
    modifiedAt: 'hace 5 días',
    modifiedBy: 'Ing. Carlos Ruiz',
    ext: 'pdf',
  },
]);

const permits = ref<Permit[]>([
  { id: 'P-1', name: 'Licencia de Construcción #4502', project: 'Torre Alpha', status: 'vence_pronto', expiresAt: '15 Dic 2026' },
  { id: 'P-2', name: 'Permiso Ambiental Zona Sur', project: 'Puente Interconector', status: 'vigente', expiresAt: '02 Feb 2027' },
]);

const filteredFiles = computed(() =>
  files.value.filter((f) => {
    const matchesSearch =
      f.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
      f.modifiedBy.toLowerCase().includes(searchTerm.value.toLowerCase());
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

  const generatedId = folderName.value.replace(/\s+/g, '').substring(0, 10);
  folders.value.push({
    id: generatedId,
    name: folderName.value,
    filesCount: 0,
    size: '0 Bytes',
    colorClass: 'text-amber-400',
  });

  const name = folderName.value;
  folderName.value = '';
  showNewFolderModal.value = false;
  showToast(`Carpeta "${name}" creada exitosamente.`);
}

function handleUploadFile() {
  if (!fileName.value.trim()) return;

  const newFile: DigitalFile = {
    id: 'DOC-' + Math.floor(Math.random() * 900 + 100),
    name: fileName.value.includes('.') ? fileName.value : `${fileName.value}.${fileExtension.value}`,
    category: fileCat.value as DigitalFile['category'],
    folderId: fileFolder.value,
    size: `${(Math.random() * 10 + 1).toFixed(1)} MB`,
    modifiedAt: 'ahora mismo',
    modifiedBy: 'Admin Principal',
    ext: fileExtension.value as DigitalFile['ext'],
  };

  const targetFolder = folders.value.find((f) => f.id === fileFolder.value);
  if (targetFolder) {
    targetFolder.filesCount += 1;
    if (targetFolder.size === '0 Bytes') targetFolder.size = '5.4 MB';
  }

  const name = newFile.name;
  files.value.unshift(newFile);
  fileName.value = '';
  showUploadModal.value = false;
  showToast(`Archivo "${name}" subido a la carpeta.`);
}

function handleShare(name: string) {
  showToast(`Enlace de descarga compartido para "${name}"`);
}

function handleDownload(name: string) {
  showToast(`Iniciando descarga de "${name}"`);
}
</script>
