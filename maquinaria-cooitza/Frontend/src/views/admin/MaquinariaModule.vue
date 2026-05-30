<template>
  <transition name="fade-up" appear>
    <div class="flex flex-col gap-6 w-full text-slate-800">
      <!-- Header and Add Button Section -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 border-b border-[#cbd5e1] pb-6">
        <div>
          <h2 class="font-display text-[32px] font-bold text-slate-800 tracking-tight">Gestión de Maquinaria</h2>
          <p class="font-sans text-sm text-slate-600 mt-2">Control centralizado de activos industriales y equipos pesados.</p>
        </div>
        
        <button 
          type="button"
          @click="openAddModal"
          class="bg-[#f5a623] hover:bg-[#e09212] text-[#291800] font-display text-xs font-bold uppercase tracking-wider px-6 py-3 flex items-center gap-2 transition-all cursor-pointer border border-[#d7c3ae]"
        >
          <Plus :size="16" />
          <span>Nueva Maquinaria</span>
        </button>
      </div>

      <!-- Filters and Stats Bento Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-stretch">
        
        <!-- KPI: Total Activos -->
        <div class="bg-white p-5 border border-[#cbd5e1] flex flex-col justify-between shadow-sm">
          <span class="font-display text-[11px] font-bold text-slate-600 uppercase tracking-wider">Total Activos</span>
          <span class="font-display text-4xl font-extrabold text-[#835500] mt-4">{{ machinery.length }}</span>
        </div>

        <!-- KPI: En Operación -->
        <div class="bg-white p-5 border border-[#cbd5e1] flex flex-col justify-between shadow-sm">
          <span class="font-display text-[11px] font-bold text-slate-600 uppercase tracking-wider">En Operación</span>
          <div class="flex items-center gap-2 mt-4">
            <div class="w-2.5 h-2.5 rounded-full bg-[#f5a623] animate-pulse"></div>
            <span class="font-display text-4xl font-extrabold text-slate-800">
              {{ machinery.filter(m => m.status === 'Operativo').length }}
            </span>
          </div>
        </div>

        <!-- KPI: En Taller / Mantenimiento -->
        <div class="bg-white p-5 border border-[#cbd5e1] flex flex-col justify-between shadow-sm">
          <span class="font-display text-[11px] font-bold text-slate-600 uppercase tracking-wider">En Taller / Mando</span>
          <div class="flex items-center gap-2 mt-4">
            <div class="w-2.5 h-2.5 rounded-full bg-[#ba1a1a]"></div>
            <span class="font-display text-4xl font-extrabold text-[#ba1a1a]">
              {{ machinery.filter(m => m.status === 'Mantenimiento').length }}
            </span>
          </div>
        </div>

        <!-- Action Toggle Filter Box -->
        <button 
          type="button"
          @click="showFilters = !showFilters"
          class="p-5 flex flex-col justify-center items-center border-2 border-dashed transition-all cursor-pointer text-center"
          :class="showFilters ? 'bg-[#835500]/5 border-[#835500] text-[#835500]' : 'border-[#cbd5e1] bg-slate-50 hover:bg-slate-100 text-slate-600'"
        >
          <Filter :size="20" :class="showFilters ? 'text-[#835500]' : 'text-slate-600'" />
          <p class="font-display text-xs font-bold uppercase tracking-wider mt-2">Filtrar Listado</p>
        </button>

      </div>

      <!-- FILTER DRAWER SLIDE DOWN PANEL -->
      <transition name="expand">
        <div v-if="showFilters" class="overflow-hidden bg-white border border-[#cbd5e1] shadow-sm p-5 grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Search Input -->
          <div class="flex flex-col gap-1">
            <label class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Buscar por Texto</label>
            <div class="relative">
              <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
              <input 
                type="text"
                placeholder="Ej. Caterpillar, WA200..."
                v-model="searchTerm"
                class="w-full pl-9 pr-3 py-2 border border-[#cbd5e1] focus:border-[#835500] text-xs outline-none bg-white transition-colors"
              />
            </div>
          </div>

          <!-- Category Filter -->
          <div class="flex flex-col gap-1">
            <label class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Filtrar por Tipo</label>
            <select 
              v-model="filterCategory"
              class="w-full border border-[#cbd5e1] px-3 py-2 text-xs bg-white outline-none cursor-pointer focus:border-[#835500]"
            >
              <option value="todos">Todos los Equipos</option>
              <option value="Tractor">Tractor</option>
              <option value="Excavadora">Excavadora</option>
              <option value="Retro Excavadora">Retro Excavadora</option>
              <option value="Rodo">Rodo</option>
              <option value="Pipa">Pipa</option>
              <option value="Camion Volteo">Camion Volteo</option>
            </select>
          </div>

          <!-- Status Filter -->
          <div class="flex flex-col gap-1">
            <label class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Estado Técnico</label>
            <select 
              v-model="filterStatus"
              class="w-full border border-[#cbd5e1] px-3 py-2 text-xs bg-white outline-none cursor-pointer focus:border-[#835500]"
            >
              <option value="todos">Cualquier Estado</option>
              <option value="Operativo">Operativo (En Campo)</option>
              <option value="Mantenimiento">Taller de Mantenimiento</option>
              <option value="Fuera de Servicio">Fuera de Servicio</option>
            </select>
          </div>
        </div>
      </transition>

      <!-- Machinery Cards Grid -->
      <div v-if="filteredMachinery.length === 0" class="bg-white border border-[#cbd5e1] p-12 text-center text-slate-400 italic">
        No se encontraron activos industriales según los criterios especificados.
      </div>
      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div 
          v-for="m in filteredMachinery" 
          :key="m.id" 
          class="bg-white border border-[#cbd5e1] group hover:border-[#835500] transition-colors overflow-hidden flex flex-col md:flex-row h-full shadow-sm relative"
        >
          
          <!-- Photo Box container -->
          <div class="w-full md:w-48 h-48 md:h-auto bg-slate-100 relative overflow-hidden shrink-0">
            <img 
              :src="m.photoUrl || DEFAULT_MACHINERY_PHOTOS[m.category]" 
              :alt="m.name" 
              referrerpolicy="no-referrer"
              class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-500" 
            />
            <div class="absolute top-3 left-3 bg-[#f5a623] text-[#291800] border border-[#d7c3ae] px-2 py-0.5 font-display text-[10px] font-bold uppercase tracking-wider">
              {{ m.category }}
            </div>
          </div>

          <!-- Machinery Core Details Panel -->
          <div class="p-5 flex-1 flex flex-col justify-between relative">
            
            <div>
              <div class="flex justify-between items-start gap-4">
                <div>
                  <span class="font-display text-[10px] font-bold text-[#835500] uppercase tracking-wider">
                    {{ m.brand }}
                  </span>
                  <h3 class="font-display text-lg font-black text-slate-800 mt-1 line-clamp-2">
                    {{ m.name }}
                  </h3>
                </div>
                <span class="font-display text-[10px] font-bold text-[#835500] bg-[#f5a623]/10 border border-[#f5a623]/25 px-2.5 py-0.5">
                  {{ m.serialId }}
                </span>
              </div>

              <!-- Status Badge -->
              <div class="mt-3">
                <span class="inline-block px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-widest"
                      :class="m.status === 'Operativo' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' :
                              m.status === 'Mantenimiento' ? 'bg-amber-50 text-amber-800 border border-amber-200' :
                              'bg-red-50 text-red-800 border border-red-200'">
                  ● {{ m.status === "Operativo" ? "Operativo" : m.status === "Mantenimiento" ? "Taller" : "Fuera de Servicio" }}
                </span>
              </div>
            </div>

            <!-- Counter & Actions Bottom Row -->
            <div class="mt-5 pt-4 flex items-end justify-between border-t border-[#cbd5e1]">
              <div class="flex gap-6">
                <div class="flex flex-col">
                  <span class="text-[9px] font-bold text-slate-600 uppercase tracking-wider">Horas Uso</span>
                  <span class="font-display text-xs font-black text-slate-800 mt-0.5">
                    {{ m.accumulatedHours?.toLocaleString() || "0" }} h
                  </span>
                </div>

                <div class="flex flex-col">
                  <span class="text-[9px] font-bold text-slate-600 uppercase tracking-wider">Próx. Service</span>
                  <span class="font-display text-xs font-black text-slate-800 mt-0.5">
                    {{ m.nextService || "Sin Programar" }}
                  </span>
                </div>
              </div>

              <!-- dropdown action button -->
              <div class="relative">
                <button 
                  type="button"
                  @click.stop="activeDropdownId = activeDropdownId === m.id ? null : m.id"
                  class="p-1.5 hover:bg-slate-100 border border-transparent hover:border-slate-200 transition-colors cursor-pointer text-slate-600"
                  title="Opciones de activo"
                >
                  <MoreVertical :size="16" />
                </button>

                <!-- Popover floating action panel -->
                <transition name="fade">
                  <div v-if="activeDropdownId === m.id" class="absolute right-0 bottom-full mb-1 w-44 bg-white border border-[#cbd5e1] shadow-xl z-20 font-sans text-xs">
                    <button 
                      type="button"
                      @click="openEditModal(m)"
                      class="w-full px-3 py-2.5 hover:bg-slate-50 text-left flex items-center gap-2 cursor-pointer border-b border-slate-100"
                    >
                      <Edit :size="12" class="text-[#835500]" />
                      <span>Editar Maquinaria</span>
                    </button>
                    <button 
                      type="button"
                      @click="handleDelete(m.id)"
                      class="w-full px-3 py-2.5 hover:bg-red-50 text-left flex items-center gap-2 text-red-600 cursor-pointer"
                    >
                      <Trash2 :size="12" class="text-red-600" />
                      <span>Remover de Flota</span>
                    </button>
                  </div>
                </transition>
              </div>
            </div>

          </div>

        </div>
      </div>

      <!-- MODAL: REGISTRAR O EDITAR MAQUINARIA -->
      <transition name="fade">
        <div v-if="isModalOpen" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="isModalOpen = false">
          <transition name="scale" appear>
            <div class="bg-white border border-[#cbd5e1] w-full max-w-2xl p-6 shadow-2xl relative overflow-y-auto max-h-[90vh]">
              
              <!-- Modal Header -->
              <div class="flex justify-between items-start mb-6 border-b border-[#cbd5e1] pb-4">
                <div>
                  <h3 class="font-display text-xl font-bold text-slate-800">
                    {{ editId ? "Modificar Activo de Maquinaria" : "Registro de Maquinaria" }}
                  </h3>
                  <p class="font-sans text-xs text-slate-600 mt-1">Complete los datos técnicos oficiales para el activo.</p>
                </div>
                
                <button 
                  type="button"
                  @click="isModalOpen = false"
                  class="p-1 hover:bg-slate-100 rounded-full cursor-pointer text-slate-600"
                >
                  <X :size="18" />
                </button>
              </div>

              <!-- Form Input fields -->
              <form @submit.prevent="handleSaveMachinery" class="space-y-4">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <!-- Brand of fabricator -->
                  <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Marca del Fabricante</label>
                    <input 
                      type="text"
                      v-model="brand"
                      placeholder="Ej. Caterpillar, John Deere or Komatsu"
                      class="p-2.5 bg-white border border-[#cbd5e1] focus:border-[#835500] text-xs outline-none font-sans transition-colors text-slate-800"
                      required
                    />
                  </div>

                  <!-- Type/Category -->
                  <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Tipo de Equipo</label>
                    <select 
                      v-model="category"
                      class="p-2.5 bg-white border border-[#cbd5e1] focus:border-[#835500] text-xs outline-none cursor-pointer font-sans transition-colors text-slate-800"
                    >
                      <option value="Tractor">Tractor</option>
                      <option value="Excavadora">Excavadora</option>
                      <option value="Retro Excavadora">Retro Excavadora</option>
                      <option value="Rodo">Rodo</option>
                      <option value="Pipa">Pipa</option>
                      <option value="Camion Volteo">Camion Volteo</option>
                    </select>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <!-- Model / Name -->
                  <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Modelo / Nombre</label>
                    <input 
                      type="text"
                      v-model="name"
                      placeholder="Ej. Excavator 320 GC o 8R 370"
                      class="p-2.5 bg-white border border-[#cbd5e1] focus:border-[#835500] text-xs outline-none font-sans transition-colors text-slate-800"
                      required
                    />
                  </div>

                  <!-- Serial/ID -->
                  <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Identificador Único (Serial/ID)</label>
                    <input 
                      type="text"
                      v-model="serialId"
                      placeholder="Ej. ID-7742-XP"
                      class="p-2.5 bg-white border border-[#cbd5e1] focus:border-[#835500] text-xs outline-none font-sans transition-colors text-slate-800"
                      required
                    />
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <!-- Current Hours -->
                  <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Horas Acumuladas</label>
                    <input 
                      type="number"
                      v-model="accumulatedHours"
                      placeholder="Ej. 1240"
                      class="p-2.5 bg-white border border-[#cbd5e1] focus:border-[#835500] text-xs outline-none font-sans transition-colors text-slate-800"
                      min="0"
                      step="0.1"
                      required
                    />
                  </div>

                  <!-- Next Service -->
                  <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Próximo Service Fija</label>
                    <input 
                      type="date"
                      v-model="nextService"
                      class="p-2.5 bg-white border border-[#cbd5e1] focus:border-[#835500] text-xs outline-none font-sans transition-colors text-slate-800 cursor-text"
                    />
                  </div>

                  <!-- Operational status -->
                  <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Condición del Activo</label>
                    <select 
                      v-model="status"
                      class="p-2.5 bg-white border border-[#cbd5e1] focus:border-[#835500] text-xs outline-none cursor-pointer font-sans transition-colors text-slate-800"
                    >
                      <option value="Operativo">Operativo (En Campo)</option>
                      <option value="Mantenimiento">Mantenimiento / Taller</option>
                      <option value="Fuera de Servicio">Fuera de Servicio</option>
                    </select>
                  </div>
                </div>

                <!-- Photo Drag drop upload area -->
                <div class="flex flex-col gap-1">
                  <label class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Fotografía del Activo</label>
                  
                  <input 
                    type="file"
                    ref="fileInputRef"
                    @change="handleFileChange"
                    accept="image/*"
                    class="hidden"
                  />

                  <div 
                    @click="fileInputRef?.click()"
                    @dragover.prevent="isDragOver = true"
                    @dragleave.prevent="isDragOver = false"
                    @drop.prevent="handleDrop"
                    class="border-2 border-dashed p-10 flex flex-col items-center justify-center gap-3 transition-colors cursor-pointer group rounded-sm"
                    :class="isDragOver ? 'border-[#835500] bg-[#835500]/5' : 'border-[#cbd5e1] bg-slate-50 hover:bg-slate-100'"
                  >
                    <template v-if="photoPreview">
                      <div class="relative w-full max-w-[200px] h-28 overflow-hidden border border-slate-200">
                        <img 
                          :src="photoPreview" 
                          alt="Machinery Preview" 
                          referrerpolicy="no-referrer"
                          class="w-full h-full object-cover" 
                        />
                        <button 
                          type="button"
                          @click.stop="clearFile"
                          class="absolute top-1.5 right-1.5 bg-[#ba1a1a] text-white p-1 rounded hover:bg-red-700 transition-colors"
                          title="Remover foto"
                        >
                          <X :size="12" />
                        </button>
                      </div>
                    </template>
                    <template v-else>
                      <CloudUpload class="w-12 h-12 text-[#857462] group-hover:text-[#835500] transition-colors" />
                      <div class="text-center">
                        <p class="font-sans text-xs text-slate-600 font-medium">
                          Arrastre la imagen del activo o <span class="text-[#835500] font-bold underline">examine</span>
                        </p>
                        <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-wider">
                          Formatos: JPG, PNG (Max 5MB)
                        </p>
                      </div>
                    </template>
                  </div>
                </div>

                <!-- Modal actions -->
                <div class="pt-4 flex justify-end gap-3 border-t border-[#cbd5e1]">
                  <button 
                    type="button"
                    @click="isModalOpen = false"
                    class="px-6 py-2.5 border border-[#cbd5e1] hover:bg-slate-50 font-display text-[10px] font-bold uppercase tracking-wider transition-colors cursor-pointer text-slate-600"
                  >
                    Cancelar
                  </button>
                  <button 
                    type="submit"
                    class="px-6 py-2.5 bg-[#f5a623] text-[#291800] border border-[#d7c3ae] font-display text-[10px] font-extrabold uppercase tracking-widest hover:brightness-105 transition-all cursor-pointer"
                  >
                    Confirmar Registro
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
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Tractor, Construction, Wrench, CircleSlash, Plus, Check, Edit, Trash2, X, CloudUpload, Search, MoreVertical, Filter, Forklift } from "lucide-vue-next";
import Swal from 'sweetalert2';

interface Machinery {
  id: string;
  name: string;
  category: "Tractor" | "Excavadora" | "Retro Excavadora" | "Rodo" | "Pipa" | "Camion Volteo";
  brand: string;
  serialId: string;
  accumulatedHours: number;
  nextService: string;
  status: "Operativo" | "Mantenimiento" | "Fuera de Servicio";
  photoUrl?: string;
}

const machinery = ref<Machinery[]>([]);
const isModalOpen = ref(false);
const editId = ref<string | null>(null);

const showFilters = ref(false);
const searchTerm = ref("");
const filterCategory = ref("todos");
const filterStatus = ref("todos");

const activeDropdownId = ref<string | null>(null);

const brand = ref("");
const name = ref("");
const category = ref<"Tractor" | "Excavadora" | "Retro Excavadora" | "Rodo" | "Pipa" | "Camion Volteo">("Excavadora");
const serialId = ref("");
const accumulatedHours = ref("");
const nextService = ref("");
const status = ref<"Operativo" | "Mantenimiento" | "Fuera de Servicio">("Operativo");
const photoPreview = ref<string | null>(null);
const selectedFile = ref<File | null>(null);

const isDragOver = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);

const emit = defineEmits<{
  (e: 'machineryChange', count: number): void;
}>();

const DEFAULT_MACHINERY_PHOTOS: Record<string, string> = {
  "Excavadora": "https://lh3.googleusercontent.com/aida-public/AB6AXuCW4-n0Uts09omCrK9hJAsromc1Y7GCJOZdeCvW2-u5sfFJXqom9XcKgbiq51rcx0mQNuEyHdpIGs8r9yViHSIpGwQ-Z7-RPkZ35ItK-6bQfr3kdlj5PT9e5KXQB3gtC7eSS279VcUjQS7-RNR9MPbwf5ypPuTg4CgEMIhyaVTzA00eWFBzQ74Pu3JtOSHdgJcFGtuDXY8l6dlcOrHUitHLowY1QP3UIFOg92Wkg41214T-JmcBsgoF8wyXoCRHv3C6SVyFE96IGl5b",
  "Tractor": "https://lh3.googleusercontent.com/aida-public/AB6AXuCtWEyh1Z-AVKb8m7r1Xd4QhYcVyxqNgGs7-QDVKHd0JvWlxT5MCJ0EPmyeydptGOjpmTw3CVlGGHGm53HGi_fza4tXXmiVp3tTR6S2n7gc02D3GN7Ko5Lc8Gv-BkHjm2F9kcmNC5ezQd7YofIuYhuYnHs-50gaNnQv7Livvi7M1RvyouOyT0-aegn6hvLevJh28ZSBMI76QCDIx27OkhuzjNPbxMQu8-cl0ANrBMiXuPsIX7-OsUgTo7TgPkIZQCwhWHSIMCSQg7PB",
  "Retro Excavadora": "https://lh3.googleusercontent.com/aida-public/AB6AXuCW4-n0Uts09omCrK9hJAsromc1Y7GCJOZdeCvW2-u5sfFJXqom9XcKgbiq51rcx0mQNuEyHdpIGs8r9yViHSIpGwQ-Z7-RPkZ35ItK-6bQfr3kdlj5PT9e5KXQB3gtC7eSS279VcUjQS7-RNR9MPbwf5ypPuTg4CgEMIhyaVTzA00eWFBzQ74Pu3JtOSHdgJcFGtuDXY8l6dlcOrHUitHLowY1QP3UIFOg92Wkg41214T-JmcBsgoF8wyXoCRHv3C6SVyFE96IGl5b",
  "Rodo": "https://lh3.googleusercontent.com/aida-public/AB6AXuCtWEyh1Z-AVKb8m7r1Xd4QhYcVyxqNgGs7-QDVKHd0JvWlxT5MCJ0EPmyeydptGOjpmTw3CVlGGHGm53HGi_fza4tXXmiVp3tTR6S2n7gc02D3GN7Ko5Lc8Gv-BkHjm2F9kcmNC5ezQd7YofIuYhuYnHs-50gaNnQv7Livvi7M1RvyouOyT0-aegn6hvLevJh28ZSBMI76QCDIx27OkhuzjNPbxMQu8-cl0ANrBMiXuPsIX7-OsUgTo7TgPkIZQCwhWHSIMCSQg7PB",
  "Pipa": "https://lh3.googleusercontent.com/aida-public/AB6AXuAPH_LQU15grhFazspqrpDAmtqij-Yi5u8pti4HGaVVVa49RVExnL40aoKKWYuBPOrziyt5g1P6pGgUiFYDzWpjjdg10n6G293Fahquh4OpO2eXBRu_Yl-uUBULkorbqp9oN16B5Gt4PG24HIbpbL7Z1pnrdEmXpME2Gbn2PFyNe7t7rIkkdfW_i-cqfovF0AtH9eU5NaBycx-bhwYG18aVN7t9ZCam-M94lOheq8vxN54bn5Q1FiBKhPTQXuinSWwvEfzWYeJx3D4U",
  "Camion Volteo": "https://lh3.googleusercontent.com/aida-public/AB6AXuAPH_LQU15grhFazspqrpDAmtqij-Yi5u8pti4HGaVVVa49RVExnL40aoKKWYuBPOrziyt5g1P6pGgUiFYDzWpjjdg10n6G293Fahquh4OpO2eXBRu_Yl-uUBULkorbqp9oN16B5Gt4PG24HIbpbL7Z1pnrdEmXpME2Gbn2PFyNe7t7rIkkdfW_i-cqfovF0AtH9eU5NaBycx-bhwYG18aVN7t9ZCam-M94lOheq8vxN54bn5Q1FiBKhPTQXuinSWwvEfzWYeJx3D4U"
};

const triggerSync = (count: number) => {
  emit('machineryChange', count);
};

const loadMachinery = async () => {
  try {
    const res = await fetch('/maquinaria-cooitza/Backend/api/v1/maquinas');
    const json = await res.json();
    if (json.status === 'success') {
      machinery.value = json.data.map((item: any) => ({
        id: item.id.toString(),
        brand: item.marca,
        name: item.identificador,
        category: item.tipo,
        serialId: item.identificador,
        accumulatedHours: parseFloat(item.horas_acumuladas) || 0,
        nextService: item.proximo_servicio || "Sin Programar",
        status: item.estado,
        photoUrl: item.foto_path ? `/maquinaria-cooitza/Backend/${item.foto_path}` : DEFAULT_MACHINERY_PHOTOS[item.tipo]
      }));
      triggerSync(machinery.value.length);
    }
  } catch (error) {
    console.error("Error cargando maquinaria:", error);
  }
};

onMounted(() => {
  loadMachinery();
  window.addEventListener('click', closeDropdowns);
});

onUnmounted(() => {
  window.removeEventListener('click', closeDropdowns);
});

const closeDropdowns = () => {
  if (activeDropdownId.value !== null) {
    activeDropdownId.value = null;
  }
};

const processFile = (file: File) => {
  if (file && file.type.startsWith("image/")) {
    selectedFile.value = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      if (e.target?.result) {
        photoPreview.value = e.target.result as string;
      }
    };
    reader.readAsDataURL(file);
  }
};

const handleDrop = (e: DragEvent) => {
  isDragOver.value = false;
  if (e.dataTransfer?.files && e.dataTransfer.files[0]) {
    processFile(e.dataTransfer.files[0]);
  }
};

const handleFileChange = (e: Event) => {
  const input = e.target as HTMLInputElement;
  if (input.files && input.files[0]) {
    processFile(input.files[0]);
  }
};

const clearFile = () => {
  photoPreview.value = null;
  selectedFile.value = null;
  if (fileInputRef.value) {
    fileInputRef.value.value = '';
  }
};

const openAddModal = () => {
  editId.value = null;
  brand.value = "";
  name.value = "";
  category.value = "Excavadora";
  serialId.value = "";
  accumulatedHours.value = "";
  nextService.value = "";
  status.value = "Operativo";
  clearFile();
  isModalOpen.value = true;
};

const openEditModal = (item: Machinery) => {
  editId.value = item.id;
  brand.value = item.brand;
  name.value = item.name;
  category.value = item.category;
  serialId.value = item.serialId;
  accumulatedHours.value = item.accumulatedHours.toString();
  nextService.value = item.nextService;
  status.value = item.status;
  photoPreview.value = item.photoUrl || null;
  selectedFile.value = null;
  isModalOpen.value = true;
  activeDropdownId.value = null;
};

const handleDelete = async (id: string) => {
  const result = await Swal.fire({
    title: '¿Eliminar activo?',
    text: "¿Está seguro de remover permanentemente este activo de maquinaria pesada?",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ba1a1a',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  });

  if (result.isConfirmed) {
    try {
      const res = await fetch('/maquinaria-cooitza/Backend/api/v1/maquinas/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      if (res.ok) {
        await loadMachinery();
        Swal.fire({
          title: '¡Eliminado!',
          text: 'El activo ha sido removido de la flota.',
          icon: 'success',
          confirmButtonColor: '#f5a623'
        });
      } else {
        Swal.fire({
          title: 'Error',
          text: 'Ocurrió un problema al intentar eliminar el activo.',
          icon: 'error',
          confirmButtonColor: '#ba1a1a'
        });
      }
    } catch (e) {
      console.error(e);
      Swal.fire({
        title: 'Error',
        text: 'No se pudo conectar con el servidor.',
        icon: 'error',
        confirmButtonColor: '#ba1a1a'
      });
    }
    activeDropdownId.value = null;
  } else {
    activeDropdownId.value = null;
  }
};

const handleSaveMachinery = async () => {
  if (!brand.value.trim() || !serialId.value.trim()) return;

  const formData = new FormData();
  formData.append('marca', brand.value);
  formData.append('tipo', category.value);
  formData.append('identificador', serialId.value);
  formData.append('estado', status.value);
  formData.append('horas_acumuladas', accumulatedHours.value || '0');
  formData.append('proximo_servicio', nextService.value || 'Sin Programar');
  
  if (selectedFile.value) {
    formData.append('foto', selectedFile.value);
  }

  try {
    let url = '/maquinaria-cooitza/Backend/api/v1/maquinas';
    if (editId.value) {
      url = '/maquinaria-cooitza/Backend/api/v1/maquinas/update';
      formData.append('id', editId.value);
    }

    const res = await fetch(url, {
      method: 'POST',
      body: formData
    });

    if (res.ok) {
      await loadMachinery();
      isModalOpen.value = false;
      Swal.fire({
        title: '¡Guardado!',
        text: 'La maquinaria ha sido registrada con éxito.',
        icon: 'success',
        confirmButtonColor: '#f5a623'
      });
    } else {
      Swal.fire({
        title: 'Error',
        text: 'Error al guardar la maquinaria en la base de datos.',
        icon: 'error',
        confirmButtonColor: '#ba1a1a'
      });
    }
  } catch (error) {
    console.error("Error:", error);
    Swal.fire({
      title: 'Error de conexión',
      text: 'No se pudo conectar con el servidor.',
      icon: 'error',
      confirmButtonColor: '#ba1a1a'
    });
  }
};

const filteredMachinery = computed(() => {
  const q = searchTerm.value.toLowerCase();
  return machinery.value.filter(m => {
    const matchesSearch = 
      m.brand.toLowerCase().includes(q) ||
      m.name.toLowerCase().includes(q) ||
      m.serialId.toLowerCase().includes(q);
    
    const matchesCategory = filterCategory.value === "todos" || m.category === filterCategory.value;
    const matchesStatus = filterStatus.value === "todos" || m.status === filterStatus.value;

    return matchesSearch && matchesCategory && matchesStatus;
  });
});

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
  transform: scale(0.95);
}

.expand-enter-active,
.expand-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}
.expand-enter-from,
.expand-leave-to {
  opacity: 0;
  max-height: 0;
}
</style>
