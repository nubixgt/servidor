<template>
  <div class="pt-20 pb-10 px-4 md:px-10 md:pb-20 max-w-7xl mx-auto space-y-10 relative">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h2 class="text-4xl font-bold tracking-tight text-white mb-2">
          {{ activeTab === 'register' ? (editingId ? 'Modificar Maquinaria' : 'Registrar Maquinaria') : 'Maquinaria Especial' }}
        </h2>
        <p class="text-white/60">Gestión de maquinaria especializada de la empresa.</p>
      </div>

      <div class="flex gap-2 bg-black/30 border border-white/10 rounded-2xl p-1 w-fit">
        <button @click="switchTab('list')" :class="['px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all', activeTab === 'list' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-white/50 hover:text-white']">
          Ver Listado
        </button>
        <button @click="switchTab('register')" :class="['px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center gap-2', activeTab === 'register' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-white/50 hover:text-white']">
          <PlusIcon class="w-3.5 h-3.5" /> {{ editingId ? 'Editando' : 'Registrar' }}
        </button>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-primary flex items-start justify-between">
        <div class="flex flex-col justify-between h-full w-full">
          <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">Total</span>
          <div class="mt-3">
            <h3 class="text-4xl font-black italic text-white tracking-tighter">{{ stats.total }}</h3>
            <p class="text-[10px] font-bold text-white/30 uppercase tracking-wider mt-1">Registradas</p>
          </div>
        </div>
        <div class="p-3 bg-primary/10 border border-primary/20 rounded-2xl text-primary shrink-0">
          <Cog6ToothIcon class="w-5 h-5" />
        </div>
      </div>

      <div class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-emerald-500/50 flex items-start justify-between">
        <div class="flex flex-col justify-between h-full w-full">
          <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">En Funcionamiento</span>
          <div class="mt-3">
            <h3 class="text-4xl font-black italic text-emerald-400 tracking-tighter">{{ stats.enFuncionamiento }}</h3>
            <p class="text-[10px] font-bold text-emerald-400/60 uppercase tracking-wider mt-1">Operativas</p>
          </div>
        </div>
        <div class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-emerald-400 shrink-0">
          <CheckCircleIcon class="w-5 h-5" />
        </div>
      </div>

      <div class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-amber-500/50 flex items-start justify-between">
        <div class="flex flex-col justify-between h-full w-full">
          <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">En Mantenimiento</span>
          <div class="mt-3">
            <h3 class="text-4xl font-black italic text-amber-400 tracking-tighter">{{ stats.enMantenimiento }}</h3>
            <p class="text-[10px] font-bold text-amber-400/60 uppercase tracking-wider mt-1">En servicio</p>
          </div>
        </div>
        <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-2xl text-amber-400 shrink-0">
          <WrenchScrewdriverIcon class="w-5 h-5" />
        </div>
      </div>

      <div class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-rose-500/50 flex items-start justify-between">
        <div class="flex flex-col justify-between h-full w-full">
          <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">Fuera de Servicio</span>
          <div class="mt-3">
            <h3 class="text-4xl font-black italic text-rose-400 tracking-tighter">{{ stats.fueraDeServicio }}</h3>
            <p class="text-[10px] font-bold text-rose-400/60 uppercase tracking-wider mt-1">Inoperables</p>
          </div>
        </div>
        <div class="p-3 bg-rose-500/10 border border-rose-500/20 rounded-2xl text-rose-400 shrink-0">
          <ExclamationTriangleIcon class="w-5 h-5" />
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════ LISTADO ═══ -->
    <template v-if="activeTab === 'list'">
      <div class="flex flex-col lg:flex-row gap-4 items-center justify-between border-b border-white/5 pb-6">
        <div class="flex gap-1.5 bg-black/30 border border-white/10 rounded-2xl p-1 overflow-x-auto w-full lg:w-auto">
          <button v-for="f in statusOptions" :key="f.value" @click="statusFilter = f.value"
            :class="['px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap', statusFilter === f.value ? 'bg-primary text-white shadow-lg' : 'text-white/40 hover:text-white']">
            {{ f.label }}
          </button>
        </div>
        <div class="relative w-full lg:w-80">
          <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-white/40" />
          <input v-model="searchTerm" type="text" placeholder="Buscar por nombre, tipo o subtipo..."
            class="glass-input pl-10 pr-4 py-3 rounded-xl text-xs font-bold w-full text-white placeholder:text-white/20" />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <div v-if="filteredList.length === 0" class="col-span-full glass-card rounded-3xl p-16 text-center border border-white/5">
          <Cog6ToothIcon class="w-12 h-12 text-white/10 mx-auto mb-4" />
          <p class="text-white/30 font-black uppercase tracking-widest text-xs">Sin maquinaria registrada con ese filtro.</p>
        </div>

        <div v-for="m in filteredList" :key="m.id"
          class="glass-card rounded-[28px] border border-white/5 p-6 flex flex-col gap-4 hover:border-white/15 transition-all relative overflow-hidden">

          <div class="flex items-start justify-between gap-2">
            <h4 class="text-base font-black italic uppercase text-white/90 tracking-tight leading-tight flex-1">{{ m.nombre }}</h4>
            <span :class="['px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-wider border shrink-0', estadoBadge(m.estado)]">
              {{ m.estado }}
            </span>
          </div>

          <div class="flex items-center gap-2 flex-wrap">
            <span class="px-2 py-0.5 bg-primary/10 border border-primary/20 rounded text-[9px] font-black text-primary uppercase tracking-widest">
              {{ m.tipo_maquinaria }}
            </span>
            <span class="px-2 py-0.5 bg-white/5 border border-white/10 rounded text-[9px] font-black text-white/50 uppercase tracking-widest">
              {{ m.subtipo }}
            </span>
          </div>

          <div class="bg-black/20 rounded-2xl p-4 border border-white/5 space-y-2.5">
            <div class="flex items-center justify-between">
              <span class="text-[9px] font-black text-white/30 uppercase tracking-widest">Responsable</span>
              <span class="text-xs font-black text-white/80 truncate max-w-[160px]">{{ m.responsable_nombre || 'Sin asignar' }}</span>
            </div>
            <div v-if="m.marca || m.modelo" class="flex items-center justify-between">
              <span class="text-[9px] font-black text-white/30 uppercase tracking-widest">Marca / Modelo</span>
              <span class="text-xs font-black text-white/80">{{ [m.marca, m.modelo].filter(Boolean).join(' ') }}</span>
            </div>
            <div v-if="m.anio" class="flex items-center justify-between">
              <span class="text-[9px] font-black text-white/30 uppercase tracking-widest">Año</span>
              <span class="text-xs font-black text-white/80">{{ m.anio }}</span>
            </div>
            <div v-if="m.ubicacion" class="flex items-center justify-between">
              <span class="text-[9px] font-black text-white/30 uppercase tracking-widest">Ubicación</span>
              <span class="text-xs font-black text-white/70 truncate max-w-[160px]">{{ m.ubicacion }}</span>
            </div>
          </div>

          <div v-if="m.foto_1" class="rounded-2xl overflow-hidden aspect-video bg-black/20 border border-white/5">
            <img :src="photoUrl(m.foto_1)" class="w-full h-full object-cover" />
          </div>

          <div class="flex items-center justify-end gap-2 pt-2 border-t border-white/5">
            <button @click="openDetails(m)"
              class="px-4 py-2 bg-primary/10 hover:bg-primary/20 text-primary rounded-xl border border-primary/20 text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-1.5">
              <EyeIcon class="w-3.5 h-3.5" /> Detalles
            </button>
            <button @click="startEdit(m)"
              class="px-4 py-2 bg-white/5 hover:bg-white/10 text-white/60 hover:text-white rounded-xl border border-white/5 text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-1.5">
              <PencilIcon class="w-3.5 h-3.5" /> Modificar
            </button>
            <button @click="deleteItem(m.id, m.nombre)"
              class="px-4 py-2 bg-white/5 hover:bg-white/10 text-white/30 hover:text-rose-400 rounded-xl border border-white/5 text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-1.5">
              <TrashIcon class="w-3.5 h-3.5" /> Eliminar
            </button>
          </div>

          <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-primary/5 rounded-full blur-2xl pointer-events-none"></div>
        </div>
      </div>
    </template>

    <!-- ═══════════════════════════════════════════ FORMULARIO ═══ -->
    <template v-else>
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <div class="lg:col-span-8 space-y-6">

          <section class="glass-card p-8 rounded-3xl border border-white/5 relative overflow-hidden">
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>
            <h3 class="text-xs font-black uppercase tracking-widest text-primary mb-6 flex items-center gap-2">
              <InformationCircleIcon class="w-4 h-4" /> Información General
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

              <div class="space-y-2 md:col-span-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Nombre de Maquinaria <span class="text-rose-400">*</span></label>
                <input v-model="form.nombre" type="text" required placeholder="Ej. Concretera Industrial 350L"
                  class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" />
              </div>

              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Tipo de Maquinaria <span class="text-rose-400">*</span></label>
                <select v-model="form.tipo_maquinaria" required @change="form.subtipo = ''"
                  class="w-full h-12 px-4 rounded-xl bg-slate-950/65 border border-white/10 text-sm font-black uppercase text-white focus:outline-none focus:border-primary">
                  <option value="">Seleccionar tipo</option>
                  <option value="Concreto">Concreto</option>
                  <option value="Elevacion">Elevación</option>
                  <option value="Pavimentacion">Pavimentación</option>
                </select>
              </div>

              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Subtipo <span class="text-rose-400">*</span></label>
                <select v-model="form.subtipo" required :disabled="!form.tipo_maquinaria"
                  class="w-full h-12 px-4 rounded-xl bg-slate-950/65 border border-white/10 text-sm font-black uppercase text-white focus:outline-none focus:border-primary disabled:opacity-40">
                  <option value="">{{ form.tipo_maquinaria ? 'Seleccionar subtipo' : 'Primero elige el tipo' }}</option>
                  <template v-if="form.tipo_maquinaria === 'Concreto'">
                    <option value="Concretera">Concretera</option>
                    <option value="Planta de Concreto">Planta de Concreto</option>
                    <option value="Bomba de Concreto">Bomba de Concreto</option>
                    <option value="Vibrador de Concreto">Vibrador de Concreto</option>
                  </template>
                  <template v-else-if="form.tipo_maquinaria === 'Elevacion'">
                    <option value="Montacarga">Montacarga</option>
                    <option value="Plataforma Elevadora">Plataforma Elevadora</option>
                  </template>
                  <template v-else-if="form.tipo_maquinaria === 'Pavimentacion'">
                    <option value="Pavimentadora">Pavimentadora</option>
                    <option value="Fresadora">Fresadora</option>
                    <option value="Distribuidor de Asfalto">Distribuidor de Asfalto</option>
                  </template>
                </select>
              </div>

              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Marca</label>
                <input v-model="form.marca" type="text" placeholder="Caterpillar, Liebherr..."
                  class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" />
              </div>

              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Modelo</label>
                <input v-model="form.modelo" type="text" placeholder="CAT 320, LB 28..."
                  class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" />
              </div>

              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Año</label>
                <input v-model="form.anio" type="number" min="1900" max="2100" placeholder="2024"
                  class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" />
              </div>

              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Estado Actual <span class="text-rose-400">*</span></label>
                <select v-model="form.estado" required
                  class="w-full h-12 px-4 rounded-xl bg-slate-950/65 border border-white/10 text-sm font-black uppercase text-white focus:outline-none focus:border-primary">
                  <option value="En Funcionamiento">En Funcionamiento</option>
                  <option value="En Mantenimiento">En Mantenimiento</option>
                  <option value="Fuera de Servicio">Fuera de Servicio</option>
                </select>
              </div>

              <div class="space-y-2 md:col-span-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Ubicación</label>
                <input v-model="form.ubicacion" type="text" placeholder="Bodega central, Proyecto X..."
                  class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" />
              </div>
            </div>
          </section>

          <section class="glass-card p-8 rounded-3xl border border-white/5">
            <h3 class="text-xs font-black uppercase tracking-widest text-primary mb-6 flex items-center gap-2">
              <UserIcon class="w-4 h-4" /> Persona Responsable
            </h3>
            <select v-model="form.responsable_id"
              class="w-full h-12 px-4 rounded-xl bg-slate-950/65 border border-white/10 text-sm font-black uppercase text-white focus:outline-none focus:border-primary">
              <option value="">Ninguno — Sin asignar</option>
              <option v-for="p in personnel" :key="p.id" :value="p.id">
                {{ p.nombres }} {{ p.apellidos }}
              </option>
            </select>
          </section>
        </div>

        <div class="lg:col-span-4 space-y-6">

          <section class="glass-card p-6 rounded-3xl border border-white/5">
            <h3 class="text-xs font-black uppercase tracking-widest text-primary mb-5 flex items-center gap-2">
              <CameraIcon class="w-4 h-4" /> Registro Fotográfico <span class="text-white/30 font-normal normal-case tracking-normal text-[10px]">(máx. 5)</span>
            </h3>
            <div class="grid grid-cols-2 gap-3">
              <div v-for="photo in photoFields" :key="photo.key"
                :class="['group relative rounded-2xl bg-white/5 hover:bg-white/10 border-2 border-dashed border-white/10 hover:border-primary transition-all flex flex-col items-center justify-center cursor-pointer overflow-hidden text-center', photo.key === 'foto_5' ? 'col-span-2 h-20' : 'aspect-video']">
                <img v-if="photoPreviews[photo.key]" :src="photoPreviews[photo.key]"
                  class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:opacity-100 transition-opacity z-0" />
                <div class="z-10 flex flex-col items-center gap-1 p-2 rounded-xl transition-all"
                  :class="photoPreviews[photo.key] ? 'bg-slate-950/60 backdrop-blur-md opacity-0 group-hover:opacity-100' : ''">
                  <CameraIcon class="w-5 h-5 text-white/30 group-hover:text-primary transition-colors" />
                  <p class="text-[8px] font-black text-white/40 uppercase tracking-widest leading-tight">{{ photo.label }}</p>
                </div>
                <input type="file" accept="image/*" @change="onPhotoChange($event, photo.key)"
                  class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" />
              </div>
            </div>
          </section>

          <div class="bg-primary p-6 rounded-3xl text-white shadow-2xl relative overflow-hidden">
            <div class="relative z-10 space-y-4">
              <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/60">Vista Previa</p>
                <p class="text-xl font-black italic tracking-tighter uppercase mt-1 leading-tight">{{ form.nombre || 'Nombre de Maquinaria' }}</p>
              </div>
              <div class="space-y-2 pt-2 text-xs border-t border-white/20">
                <div class="flex justify-between">
                  <span class="text-white/60 text-[9px] font-black uppercase tracking-wider">Tipo</span>
                  <span class="font-black text-white/95 text-[10px]">{{ form.tipo_maquinaria || '—' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-white/60 text-[9px] font-black uppercase tracking-wider">Subtipo</span>
                  <span class="font-black text-white/90 text-[10px]">{{ form.subtipo || '—' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-white/60 text-[9px] font-black uppercase tracking-wider">Estado</span>
                  <span class="font-black text-white/90 text-[10px]">{{ form.estado }}</span>
                </div>
              </div>
            </div>
            <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
          </div>

          <div class="flex gap-3">
            <button @click="submitForm"
              class="flex-1 bg-primary hover:opacity-90 text-white py-4 rounded-xl text-xs font-black uppercase tracking-widest shadow-2xl transition-all">
              {{ editingId ? 'Guardar Cambios' : 'Registrar Maquinaria' }}
            </button>
            <button @click="switchTab('list'); resetForm()"
              class="px-5 py-4 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-black text-white/50 transition-all">
              Cancelar
            </button>
          </div>
        </div>
      </div>
    </template>

    <!-- ═══════════════════════════════ MODAL DETALLES ═══ -->
    <Transition name="modal">
      <div v-if="showDetailsModal && selectedItem" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showDetailsModal = false" class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm cursor-pointer"></div>
        <div class="relative w-full max-w-3xl bg-slate-950 border border-white/10 rounded-3xl p-8 shadow-2xl overflow-y-auto max-h-[90vh] text-white z-10">
          <div class="flex items-center justify-between border-b border-white/5 pb-4 mb-6">
            <h4 class="text-lg font-black italic uppercase flex items-center gap-2">
              <Cog6ToothIcon class="w-5 h-5 text-primary" /> {{ selectedItem.nombre }}
            </h4>
            <button @click="showDetailsModal = false" class="p-1.5 hover:bg-white/10 rounded-lg text-white/50 hover:text-white transition-all">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4">
              <h3 class="text-xs font-black uppercase tracking-widest text-primary flex items-center gap-2">
                <InformationCircleIcon class="w-4 h-4" /> Información
              </h3>
              <div class="bg-white/5 p-5 rounded-2xl border border-white/5 space-y-3">
                <div v-for="field in detailFields" :key="field.label">
                  <span class="text-[9px] font-black text-white/30 uppercase tracking-widest block">{{ field.label }}</span>
                  <span class="text-sm font-black text-white">{{ field.value }}</span>
                </div>
              </div>
            </div>

            <div class="space-y-4">
              <h3 class="text-xs font-black uppercase tracking-widest text-primary flex items-center gap-2">
                <CameraIcon class="w-4 h-4" /> Fotografías
              </h3>
              <div class="grid grid-cols-2 gap-3">
                <div v-for="photo in photoFields" :key="photo.key"
                  :class="['space-y-1', photo.key === 'foto_5' ? 'col-span-2' : '']">
                  <span class="text-[8px] font-black text-white/30 uppercase tracking-widest block">{{ photo.label }}</span>
                  <div class="aspect-video bg-white/5 rounded-xl border border-white/10 overflow-hidden flex items-center justify-center">
                    <img v-if="selectedItem[photo.key]" :src="photoUrl(selectedItem[photo.key])" class="w-full h-full object-cover" />
                    <div v-else class="text-center text-white/20 p-2">
                      <CameraIcon class="w-6 h-6 mx-auto mb-1 opacity-50" />
                      <span class="text-[8px] font-black uppercase tracking-widest">Sin foto</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import Swal from 'sweetalert2';
import {
  PlusIcon, MagnifyingGlassIcon, EyeIcon, PencilIcon, TrashIcon,
  UserIcon, CameraIcon, XMarkIcon, Cog6ToothIcon, WrenchScrewdriverIcon,
  CheckCircleIcon, ExclamationTriangleIcon, InformationCircleIcon
} from '@heroicons/vue/24/outline';

const BASE_URL = '/concretos-oriente/Backend/api/v1';

// ── State ──────────────────────────────────────────────────────────────────
const activeTab    = ref('list');
const searchTerm   = ref('');
const statusFilter = ref('all');
const items        = ref([]);
const personnel    = ref([]);
const editingId    = ref(null);

const form = ref({
  nombre: '', tipo_maquinaria: '', subtipo: '', marca: '', modelo: '',
  anio: '', estado: 'En Funcionamiento', ubicacion: '', responsable_id: ''
});

const photoFields = [
  { key: 'foto_1', label: 'Foto 1' },
  { key: 'foto_2', label: 'Foto 2' },
  { key: 'foto_3', label: 'Foto 3' },
  { key: 'foto_4', label: 'Foto 4' },
  { key: 'foto_5', label: 'Foto 5' },
];

const photoPreviews = ref({ foto_1: null, foto_2: null, foto_3: null, foto_4: null, foto_5: null });
let photoFiles = { foto_1: null, foto_2: null, foto_3: null, foto_4: null, foto_5: null };

const showDetailsModal = ref(false);
const selectedItem     = ref(null);

const statusOptions = [
  { value: 'all',               label: 'Todos'             },
  { value: 'En Funcionamiento', label: 'En Funcionamiento' },
  { value: 'En Mantenimiento',  label: 'En Mantenimiento'  },
  { value: 'Fuera de Servicio', label: 'Fuera de Servicio' },
];

// ── Computed ───────────────────────────────────────────────────────────────
const filteredList = computed(() => {
  const q = searchTerm.value.toLowerCase();
  return items.value.filter(m => {
    const matchText = m.nombre?.toLowerCase().includes(q) ||
                      m.tipo_maquinaria?.toLowerCase().includes(q) ||
                      m.subtipo?.toLowerCase().includes(q);
    if (statusFilter.value === 'all') return matchText;
    return matchText && m.estado === statusFilter.value;
  });
});

const stats = computed(() => ({
  total:            items.value.length,
  enFuncionamiento: items.value.filter(m => m.estado === 'En Funcionamiento').length,
  enMantenimiento:  items.value.filter(m => m.estado === 'En Mantenimiento').length,
  fueraDeServicio:  items.value.filter(m => m.estado === 'Fuera de Servicio').length,
}));

const detailFields = computed(() => {
  if (!selectedItem.value) return [];
  const m = selectedItem.value;
  return [
    { label: 'Nombre',         value: m.nombre },
    { label: 'Tipo',           value: m.tipo_maquinaria },
    { label: 'Subtipo',        value: m.subtipo },
    { label: 'Marca / Modelo', value: [m.marca, m.modelo].filter(Boolean).join(' ') || '—' },
    { label: 'Año',            value: m.anio || '—' },
    { label: 'Estado',         value: m.estado },
    { label: 'Ubicación',      value: m.ubicacion || '—' },
    { label: 'Responsable',    value: m.responsable_nombre || 'Sin asignar' },
  ];
});

// ── Helpers ────────────────────────────────────────────────────────────────
const estadoBadge = (estado) => {
  if (estado === 'En Funcionamiento') return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
  if (estado === 'En Mantenimiento')  return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
  if (estado === 'Fuera de Servicio') return 'bg-rose-500/10 text-rose-400 border-rose-500/20';
  return 'bg-white/5 text-white/40 border-white/10';
};

const photoUrl = (path) => {
  if (!path) return '';
  if (path.startsWith('http')) return path;
  return `/concretos-oriente/Backend/${path}?t=${Date.now()}`;
};

const toast = (msg, icon = 'success') => Swal.fire({
  toast: true, position: 'top-end', icon, title: msg,
  showConfirmButton: false, timer: 4000, timerProgressBar: true,
  background: '#0f172a', color: '#ffffff'
});

// ── Fetch ──────────────────────────────────────────────────────────────────
const fetchItems = async () => {
  try {
    const token = localStorage.getItem('token');
    const res   = await fetch(`${BASE_URL}/special-machinery`, { headers: { Authorization: `Bearer ${token}` } });
    const data  = await res.json();
    if (data.success) items.value = data.data || [];
  } catch (e) { console.error(e); }
};

const fetchPersonnel = async () => {
  try {
    const token = localStorage.getItem('token');
    const res   = await fetch(`${BASE_URL}/personnel`, { headers: { Authorization: `Bearer ${token}` } });
    const data  = await res.json();
    if (data.status === 'success' || data.success) personnel.value = data.data || [];
  } catch (e) { console.error(e); }
};

onMounted(() => { fetchItems(); fetchPersonnel(); });

// ── Form ───────────────────────────────────────────────────────────────────
const resetForm = () => {
  editingId.value = null;
  form.value = { nombre: '', tipo_maquinaria: '', subtipo: '', marca: '', modelo: '', anio: '', estado: 'En Funcionamiento', ubicacion: '', responsable_id: '' };
  photoPreviews.value = { foto_1: null, foto_2: null, foto_3: null, foto_4: null, foto_5: null };
  photoFiles = { foto_1: null, foto_2: null, foto_3: null, foto_4: null, foto_5: null };
};

const switchTab = (tab) => { activeTab.value = tab; };

const startEdit = (m) => {
  editingId.value = m.id;
  form.value = {
    nombre: m.nombre, tipo_maquinaria: m.tipo_maquinaria, subtipo: m.subtipo,
    marca: m.marca || '', modelo: m.modelo || '', anio: m.anio || '',
    estado: m.estado, ubicacion: m.ubicacion || '', responsable_id: m.responsable_id || ''
  };
  photoFiles = { foto_1: null, foto_2: null, foto_3: null, foto_4: null, foto_5: null };
  photoPreviews.value = {
    foto_1: m.foto_1 ? photoUrl(m.foto_1) : null,
    foto_2: m.foto_2 ? photoUrl(m.foto_2) : null,
    foto_3: m.foto_3 ? photoUrl(m.foto_3) : null,
    foto_4: m.foto_4 ? photoUrl(m.foto_4) : null,
    foto_5: m.foto_5 ? photoUrl(m.foto_5) : null,
  };
  activeTab.value = 'register';
};

const onPhotoChange = (e, key) => {
  const file = e.target.files?.[0];
  if (!file) return;
  photoFiles[key] = file;
  if (photoPreviews.value[key]) URL.revokeObjectURL(photoPreviews.value[key]);
  photoPreviews.value[key] = URL.createObjectURL(file);
};

const submitForm = async () => {
  if (!form.value.nombre.trim() || !form.value.tipo_maquinaria || !form.value.subtipo) {
    toast('Nombre, tipo de maquinaria y subtipo son obligatorios.', 'warning');
    return;
  }

  try {
    const token = localStorage.getItem('token');
    const fd    = new FormData();

    Object.entries(form.value).forEach(([k, v]) => fd.append(k, v ?? ''));
    photoFields.forEach(({ key }) => {
      if (photoFiles[key]) fd.append(key, photoFiles[key]);
    });

    const url = editingId.value
      ? `${BASE_URL}/special-machinery/update/${editingId.value}`
      : `${BASE_URL}/special-machinery`;

    const res    = await fetch(url, { method: 'POST', headers: { Authorization: `Bearer ${token}` }, body: fd });
    const result = await res.json();

    if (result.success) {
      toast(editingId.value ? 'Maquinaria actualizada correctamente.' : 'Maquinaria registrada correctamente.');
      resetForm();
      activeTab.value = 'list';
      fetchItems();
    } else {
      toast(result.message || 'Error al guardar.', 'error');
    }
  } catch (e) {
    toast('Error de conexión.', 'error');
  }
};

// ── Delete ─────────────────────────────────────────────────────────────────
const deleteItem = async (id, nombre) => {
  const confirm = await Swal.fire({
    title: '¿Eliminar maquinaria?',
    text: `¿Estás seguro de eliminar "${nombre}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#f43f5e',
    cancelButtonColor: '#475569',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    background: '#0f172a',
    color: '#ffffff'
  });

  if (!confirm.isConfirmed) return;

  try {
    const token  = localStorage.getItem('token');
    const res    = await fetch(`${BASE_URL}/special-machinery/${id}`, { method: 'DELETE', headers: { Authorization: `Bearer ${token}` } });
    const result = await res.json();
    if (result.success) { toast(`"${nombre}" eliminada.`); fetchItems(); }
    else toast(result.message || 'Error al eliminar.', 'error');
  } catch (e) { toast('Error de conexión.', 'error'); }
};

// ── Detalles ───────────────────────────────────────────────────────────────
const openDetails = (m) => { selectedItem.value = m; showDetailsModal.value = true; };
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
