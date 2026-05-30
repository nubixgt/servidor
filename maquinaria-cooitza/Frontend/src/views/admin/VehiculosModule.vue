<template>
  <transition name="fade-up" appear>
    <div class="flex flex-col gap-6">
      <!-- Dynamic top heading & status -->
      <div class="flex md:flex-row flex-col justify-between md:items-center gap-2 border-b border-slate-200 pb-3">
        <div class="border-l-4 border-[#0054A3] pl-3">
          <span class="font-display text-[10px] font-black text-[#0054A3] tracking-widest uppercase">LOGÍSTICA INTEGRAL</span>
          <h2 class="font-display text-3xl font-black text-slate-800 mt-0.5">Control de Flota Operativa</h2>
          <p class="text-xs text-slate-600 font-medium mt-1">
            Registro, control de kilometraje y taller de vehículos de transporte Cooitzá.
          </p>
        </div>
        <div class="flex items-center gap-2 bg-emerald-50 text-emerald-800 border border-emerald-200 px-3 py-1 self-start">
          <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></div>
          <span class="font-display text-[10px] font-bold uppercase tracking-widest">Módulo de Flotas Activo</span>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row gap-8 items-start">
        
        <!-- Left Form Column (Registro de Vehículos) -->
        <section class="flex-1 space-y-4 w-full">
          <div class="bg-white border border-[#cbd5e1] p-6 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-[3px] bg-[#0054A3]"></div>
            
            <h3 class="font-display text-xs font-black text-[#0054A3] uppercase tracking-wider mb-6 border-b pb-2">
              {{ editVehicleId ? "Modificar Vehículo Registrado" : "Registro de Vehículos" }}
            </h3>

            <form @submit.prevent="handleSaveVehicle" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              
              <div class="flex flex-col gap-1">
                <label class="font-display text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
                  Marca / Modelo
                </label>
                <input 
                  type="text"
                  v-model="marca"
                  placeholder="Ej. Freightliner o Volvo FH16"
                  class="w-full border border-[#cbd5e1] focus:border-[#0054A3] text-xs py-2 px-3 outline-none text-slate-800"
                  required
                />
              </div>

              <div class="flex flex-col gap-1">
                <label class="font-display text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
                  Placa / Matrícula
                </label>
                <input 
                  type="text"
                  v-model="placa"
                  placeholder="Ej. ABC-1234"
                  class="w-full border border-[#cbd5e1] focus:border-[#0054A3] text-xs py-2 px-3 outline-none text-slate-800"
                  required
                />
              </div>

              <div class="flex flex-col gap-1">
                <label class="font-display text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
                  Tipo de Vehículo
                </label>
                <select 
                  v-model="tipo"
                  class="w-full border border-[#cbd5e1] focus:border-[#0054A3] text-xs py-2 px-3 bg-white outline-none cursor-pointer text-slate-800"
                >
                  <option value="camion">Camión</option>
                  <option value="pickup">Pickup</option>
                  <option value="pipa">Pipa Cisterna</option>
                  <option value="otros">Otros Equipos</option>
                </select>
              </div>

              <div class="flex flex-col gap-1">
                <label class="font-display text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
                  Modelo / Año
                </label>
                <input 
                  type="text"
                  v-model="yearModel"
                  placeholder="Ej. 2024"
                  class="w-full border border-[#cbd5e1] focus:border-[#0054A3] text-xs py-2 px-3 outline-none text-slate-800"
                />
              </div>

              <div class="flex flex-col gap-1 md:col-span-2">
                <label class="font-display text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
                  Kilometraje de Registro
                </label>
                <div class="relative">
                  <input 
                    type="number"
                    v-model="mileage"
                    placeholder="000,000"
                    class="w-full border border-[#cbd5e1] focus:border-[#0054A3] text-xs py-2 pl-3 pr-12 outline-none text-slate-800"
                    required
                  />
                  <span class="absolute right-3 top-1/2 -translate-y-1/2 font-display text-[10px] font-black text-slate-500">
                    KM
                  </span>
                </div>
              </div>

              <div class="flex flex-col gap-1 md:col-span-2">
                <label class="font-display text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
                  Estado de Flota
                </label>
                <select
                  v-model="status"
                  class="w-full border border-[#cbd5e1] focus:border-[#0054A3] text-xs py-2 px-3 bg-white outline-none cursor-pointer text-slate-800"
                >
                  <option value="Activo">Operativo</option>
                  <option value="Mantenimiento">Taller / Mantenimiento</option>
                  <option value="Standby">En Reserva (Standby)</option>
                </select>
              </div>

              <!-- Upload Area -->
              <div class="md:col-span-2 flex flex-col gap-1">
                <label class="font-display text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
                  Fotografía del Vehículo
                </label>
                
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
                  class="border-2 border-dashed p-8 flex flex-col items-center justify-center gap-3 transition-colors cursor-pointer group rounded-sm"
                  :class="isDragOver ? 'border-[#0054A3] bg-[#0054A3]/5' : 'border-[#cbd5e1] bg-slate-50 hover:bg-slate-100'"
                >
                  <template v-if="photoPreview">
                    <div class="relative w-full max-w-[200px] h-24 overflow-hidden border border-slate-200">
                      <img 
                        :src="photoPreview" 
                        alt="Preview" 
                        class="w-full h-full object-cover" 
                      />
                      <button 
                        type="button"
                        @click.stop="photoPreview = null"
                        class="absolute top-1 right-1 bg-red-600 text-white p-0.5 rounded-sm hover:bg-red-700"
                        title="Quitar foto"
                      >
                        <X :size="12" />
                      </button>
                    </div>
                  </template>
                  <template v-else>
                    <CloudUpload class="w-12 h-12 text-slate-400 group-hover:text-[#0054A3] transition-colors" />
                    <div class="text-center">
                      <p class="text-xs text-slate-600 font-medium">
                        Arrastre la imagen o <span class="text-[#0054A3] font-bold underline">examine</span>
                      </p>
                      <p class="font-display text-[10px] text-slate-400 uppercase mt-1 tracking-wider">
                        Formatos aceptados: PNG, JPG (Max 5MB)
                      </p>
                    </div>
                  </template>
                </div>
              </div>

              <div class="md:col-span-2 pt-2 flex flex-col md:flex-row gap-4 items-center">
                <button 
                  type="submit"
                  class="w-full md:w-auto px-6 py-2.5 bg-[#FFD200] text-[#0054A3] font-display text-xs font-black uppercase tracking-widest hover:brightness-105 transition-all cursor-pointer shadow-sm rounded-none"
                >
                  {{ editVehicleId ? "Aplicar Cambios" : "Registrar Vehículo" }}
                </button>

                <button v-if="editVehicleId"
                  type="button"
                  @click="cancelEdit"
                  class="w-full md:w-auto text-xs font-bold text-red-600 hover:underline mt-2 md:mt-0"
                >
                  Cancelar Edición
                </button>
              </div>

            </form>
          </div>
        </section>

        <!-- Right Column (Registrados Recientemente sidebar list) -->
        <section class="w-full lg:w-80 flex flex-col gap-4">
          <div class="flex items-center justify-between border-b border-slate-300 pb-2">
            <h3 class="font-display text-[11px] font-black text-slate-600 uppercase tracking-widest">
              Registrados Recientemente
            </h3>
            <span class="font-display text-[11px] font-extrabold text-[#0054A3]">
              {{ vehicles.length < 10 ? `0${vehicles.length}` : vehicles.length }} TOTAL
            </span>
          </div>

          <div class="flex flex-col gap-2.5 max-h-[440px] overflow-y-auto pr-1">
            <div 
              v-for="v in recentVehicles"
              :key="v.id" 
              class="bg-white border border-[#cbd5e1] p-3 flex items-center gap-3 hover:border-[#0054A3] transition-all cursor-pointer group shadow-sm select-none"
              @click="handleEditVehicle(v)"
              title="Haga clic para editar los datos de este vehículo"
            >
              <!-- Photo container -->
              <div class="w-16 h-16 bg-slate-100 flex-shrink-0 overflow-hidden relative border border-slate-100">
                <img 
                  :src="v.photoUrl || DEFAULT_VEHICLE_PHOTOS[0]" 
                  :alt="v.model" 
                  class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-300"
                />
              </div>

              <div class="flex-grow min-w-0">
                <p class="font-display text-[10px] font-bold text-slate-400 uppercase truncate">
                  {{ v.model }} ({{ v.year || "2023" }})
                </p>
                <p class="font-display text-sm font-black text-slate-800 tracking-tight mt-0.5 truncate">
                  {{ v.plate }}
                </p>
                <div class="flex items-center gap-1.5 mt-1.5">
                  <span class="w-2 h-2 rounded-full"
                        :class="v.status === 'Activo' ? 'bg-emerald-500' : v.status === 'Mantenimiento' ? 'bg-amber-500' : 'bg-slate-400'">
                  </span>
                  <span class="font-display text-[9px] font-extrabold text-slate-600 uppercase tracking-wider">
                    {{ v.status === "Activo" ? "Operativo" : v.status === "Mantenimiento" ? "Mantenimiento" : "Standby" }}
                  </span>
                </div>
              </div>

              <div class="flex flex-col gap-1 shrink-0 opacity-40 group-hover:opacity-100 transition-opacity">
                <button 
                  type="button" 
                  @click.stop="handleDeleteVehicle(v.id)"
                  class="text-red-600 hover:bg-red-50 p-1 rounded"
                  title="Eliminar unidad"
                >
                  <Trash2 :size="13" />
                </button>
              </div>
            </div>
          </div>

          <button 
            type="button"
            @click="showInvoiceModal = true"
            class="w-full mt-2 py-3 border border-[#cbd5e1] hover:border-[#0054A3] font-display text-[10px] font-black uppercase text-slate-600 hover:text-[#0054A3] transition-all flex items-center justify-center gap-2 cursor-pointer bg-white"
          >
            <span>Ver Inventario Completo</span>
            <ArrowRight :size="13" />
          </button>
        </section>

      </div>

      <!-- FULL FLEET SEARCH / CRUD MODAL GRID -->
      <transition name="fade">
        <div v-if="showInvoiceModal" class="fixed inset-0 bg-slate-900/80 z-50 flex items-center justify-center p-4">
          <transition name="scale" appear>
            <div class="bg-white max-w-4xl w-full p-6 flex flex-col gap-4 border-t-4 border-[#0054A3]">
              <div class="flex justify-between items-center border-b pb-3 border-slate-200">
                <div class="flex items-center gap-2">
                  <Truck class="text-[#0054A3]" :size="20" />
                  <h3 class="font-display text-base font-black text-[#0054A3] uppercase">
                    Inventario Completo de Vehículos Cooitzá
                  </h3>
                </div>
                <button 
                  type="button"
                  @click="showInvoiceModal = false"
                  class="text-slate-400 hover:text-slate-600 transition-colors"
                >
                  <X :size="20" />
                </button>
              </div>

              <!-- Filter and Search box in Modal -->
              <div class="flex flex-col sm:flex-row gap-3 items-center bg-slate-50 p-3 border border-slate-200">
                <div class="relative flex-grow w-full">
                  <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                  <input 
                    type="text" 
                    placeholder="Filtrar por placa, marca, modelo o código..."
                    v-model="searchTerm"
                    class="pl-9 pr-3 py-1.5 bg-white border border-[#cbd5e1] text-xs font-semibold outline-none focus:border-[#0054A3] w-full text-slate-800"
                  />
                </div>
                <div class="text-[11px] font-bold text-slate-500 shrink-0 uppercase tracking-widest">
                  Resultados: {{ filteredVehicles.length }} de {{ vehicles.length }}
                </div>
              </div>

              <!-- Data Table -->
              <div class="overflow-y-auto max-h-[350px] border border-slate-200">
                <table class="w-full text-left text-xs font-sans">
                  <thead class="sticky top-0 bg-slate-100 font-display font-black text-[10px] text-slate-600 uppercase tracking-wider border-b border-slate-200 z-10 select-none">
                    <tr>
                      <th class="py-2 px-3">Código</th>
                      <th class="py-2 px-3">Descripción / Marca</th>
                      <th class="py-2 px-3">Placa</th>
                      <th class="py-2 px-3">Año / Tipo</th>
                      <th class="py-2 px-3">Kilometraje</th>
                      <th class="py-2 px-3">Estado</th>
                      <th class="py-2.5 px-3 text-center">Acción</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 bg-white">
                    <tr v-if="filteredVehicles.length === 0">
                      <td colspan="7" class="py-8 text-center text-slate-400 italic">
                        No se encontraron unidades con los términos especificados
                      </td>
                    </tr>
                    <tr 
                      v-else
                      v-for="v in filteredVehicles" 
                      :key="v.id" 
                      class="hover:bg-slate-50 transition-colors text-slate-800"
                    >
                      <td class="py-3 px-3 font-mono font-bold text-[#0054A3]">{{ v.code }}</td>
                      <td class="py-3 px-3">
                        <div class="flex items-center gap-2">
                          <img v-if="v.photoUrl" :src="v.photoUrl" alt="" class="w-6 h-6 object-cover bg-slate-100" />
                          <span class="font-semibold">{{ v.model }}</span>
                        </div>
                      </td>
                      <td class="py-3 px-3 font-mono font-bold">{{ v.plate }}</td>
                      <td class="py-3 px-3">
                        <span class="uppercase text-slate-500 font-bold">{{ v.year }}</span>
                        <span class="ml-1 text-[10px] bg-slate-100 px-1 py-0.5 uppercase border rounded-sm font-semibold text-slate-600">
                          {{ v.type }}
                        </span>
                      </td>
                      <td class="py-3 px-3 font-mono font-bold">
                        {{ v.mileage.toLocaleString() }} <span class="text-[10px] text-slate-400">KM</span>
                      </td>
                      <td class="py-3 px-3">
                        <span class="px-2 py-0.5 rounded text-[9px] font-extrabold uppercase"
                              :class="v.status === 'Activo' ? 'bg-emerald-100 text-emerald-800' :
                                      v.status === 'Mantenimiento' ? 'bg-amber-100 text-amber-800' :
                                      'bg-slate-100 text-slate-800'">
                          {{ v.status === "Activo" ? "Operativo" : v.status === "Mantenimiento" ? "Taller" : "Standby" }}
                        </span>
                      </td>
                      <td class="py-3 px-3">
                        <div class="flex justify-center items-center gap-1">
                          <button 
                            type="button" 
                            @click="handleEditVehicle(v); showInvoiceModal = false;"
                            class="p-1 hover:bg-slate-100 text-[#0054A3] rounded"
                            title="Editar datos"
                          >
                            <Edit :size="13" />
                          </button>
                          <button 
                            type="button" 
                            @click="handleDeleteVehicle(v.id)"
                            class="p-1 hover:bg-red-50 text-red-600 rounded"
                            title="Remover de la flota"
                          >
                            <Trash2 :size="13" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="flex justify-end pt-2 border-t border-slate-200">
                <button 
                  type="button"
                  @click="showInvoiceModal = false"
                  class="px-5 py-2 border border-slate-300 font-display text-[10px] font-bold uppercase tracking-wider hover:bg-slate-50 cursor-pointer text-slate-600"
                >
                  Cerrar Vista de Inventario
                </button>
              </div>
            </div>
          </transition>
        </div>
      </transition>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Truck, CloudUpload, Search, Edit, Trash2, ArrowRight, X } from "lucide-vue-next";

interface Vehicle {
  id: string;
  code: string;
  model: string;
  plate: string;
  type: "camion" | "pickup" | "pipa" | "otros";
  status: "Activo" | "Mantenimiento" | "Standby";
  year: string;
  mileage: number;
  photoUrl?: string;
}

const emit = defineEmits<{
  (e: 'vehiclesChange', count: number, activeCount: number, maintenanceCount: number): void
}>();

const DEFAULT_VEHICLE_PHOTOS = [
  "https://lh3.googleusercontent.com/aida-public/AB6AXuBfCQxHMoSqylJtj62363vrKS4Ai0aSb8qAVt7Vxe7OrqjIXMky93gYA8fkKJ5NI234BDTazq23zLhJnD2FS5s7l6F6n53lXwZt9ykMZ1mHgocxXB85X1OimLy6_6zeYidMZPGnl51KC3KG2QK0v-25MkkEOFHoTzq3XSaYsi8wqQQ4E9FhsapVEDRzsLqlWWh_bSjIN7hgooh7Eno7Co11U4_AFWZ5F1x6PV8KiOhzF9aAvednwsyE0P7Pmgnvfo9FIu6x7CJDYFv4",
  "https://lh3.googleusercontent.com/aida-public/AB6AXuAOS7o83jWBjyOCa7Sy-JMKLT1aChKEr5Vfl6WYCfW3UNvOpY14pVAAVDMBCCBQmGz7d0keMVoNrSENiIZJqRP0DkPySV3YzA-Y9P68wI37H_hd4KSJ9bkmbO7HGVYCbbd8Ozn6DdGkM0Ocp-Ql4RrIJhdWNG7n54xYb6NZya35YtSfcbOQTUWLG-vnUJZuNdHA76wg9lCXDzMYtlzKLfrsSh5x8l4E8BMp4NhRqRKTHDXlis4zJwNr3W9wZXpyu99CYSnDb8QVh5O-",
  "https://lh3.googleusercontent.com/aida-public/AB6AXuBtxq1_ce_lH6LS17f9tAAI4LUKez8StI7z_g8Oj1bLpZF6CKykb_8CtlBzi0Q2mvFFm-UiluRsxEeaiuy7GuU7M-0qISKI70sFsyV2IFNnPGKAcZxNObUP2lAw2kSl2jYd8ugip5qTfhkPs-UUjo_DDlJEXPas4XhJCWnNYnqvH672FKRQXY1dFDNuBjWybUR-S2f6Iqnc8CJd26B4ZLnAMInrBbXfsH0ySl88b54eftYeBhdo_bhcPM9TjAZs0ZcB0UmtJFX47wqX",
  "https://lh3.googleusercontent.com/aida-public/AB6AXuDIO4iixhRl8ks22wCvo8FVysSOADgubwEgVWVRvCXJN3VSP4BwqaBT9hBoD6obwKzk8zYlU609d8UPAgY0TvKo-sYR0eH9C9emvqKpK8CWFz8d7kpRnMPsUG-SI14qGGQ5GDvgOrNqGWbnfaBlYmeDHxMwys23tvSNVyDNzjlwfz52UVjsTYQK8FOuhL022AkGVNHKhnhc6Dv2LIJZoxJvEs3quHXMMCy2L09rqOKoq031Q0ptFawvTDHWaqTZdAgCRuqTexXOzph7"
];

const vehicles = ref<Vehicle[]>([]);
const editVehicleId = ref<string | null>(null);

const marca = ref("");
const placa = ref("");
const tipo = ref<"camion" | "pickup" | "pipa" | "otros">("camion");
const yearModel = ref("");
const mileage = ref("");
const status = ref<"Activo" | "Mantenimiento" | "Standby">("Activo");
const photoPreview = ref<string | null>(null);

const isDragOver = ref(false);
const showInvoiceModal = ref(false);
const searchTerm = ref("");
const fileInputRef = ref<HTMLInputElement | null>(null);

const triggerSync = (updated: Vehicle[]) => {
  const active = updated.filter(v => v.status === "Activo").length;
  const maint = updated.filter(v => v.status === "Mantenimiento").length;
  emit('vehiclesChange', updated.length, active, maint);
};

const persistVehicles = (updated: Vehicle[]) => {
  vehicles.value = updated;
  localStorage.setItem("cooitza_vehiculos", JSON.stringify(updated));
  triggerSync(updated);
};

onMounted(() => {
  const saved = localStorage.getItem("cooitza_vehiculos");
  if (saved) {
    const data = JSON.parse(saved);
    vehicles.value = data;
    triggerSync(data);
  } else {
    const defaultVehicles: Vehicle[] = [
      { 
        id: "v1", code: "VEH-104", model: "Volvo FH16", plate: "PLQ-9902", type: "camion", 
        status: "Activo", year: "2023", mileage: 48900, photoUrl: DEFAULT_VEHICLE_PHOTOS[0]
      },
      { 
        id: "v2", code: "VEH-88", model: "Ford Ranger", plate: "GST-5512", type: "pickup", 
        status: "Mantenimiento", year: "2022", mileage: 12400, photoUrl: DEFAULT_VEHICLE_PHOTOS[1]
      },
      { 
        id: "v3", code: "VEH-05", model: "Isuzu NPR", plate: "MXX-2281", type: "camion", 
        status: "Activo", year: "2021", mileage: 81300, photoUrl: DEFAULT_VEHICLE_PHOTOS[2]
      },
      { 
        id: "v4", code: "VEH-92", model: "Toyota Hilux", plate: "RDZ-0092", type: "pickup", 
        status: "Activo", year: "2023", mileage: 37900, photoUrl: DEFAULT_VEHICLE_PHOTOS[3]
      }
    ];
    vehicles.value = defaultVehicles;
    localStorage.setItem("cooitza_vehiculos", JSON.stringify(defaultVehicles));
    triggerSync(defaultVehicles);
  }
});

const processFile = (file: File) => {
  if (file && file.type.startsWith("image/")) {
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

const cancelEdit = () => {
  editVehicleId.value = null;
  marca.value = "";
  placa.value = "";
  tipo.value = "camion";
  yearModel.value = "";
  mileage.value = "";
  status.value = "Activo";
  photoPreview.value = null;
};

const handleSaveVehicle = () => {
  if (!marca.value.trim() || !placa.value.trim()) return;

  const chosenPhoto = photoPreview.value || DEFAULT_VEHICLE_PHOTOS[Math.floor(Math.random() * DEFAULT_VEHICLE_PHOTOS.length)];

  if (editVehicleId.value) {
    const updated = vehicles.value.map(v => v.id === editVehicleId.value ? {
      ...v,
      model: marca.value,
      plate: placa.value,
      type: tipo.value,
      year: yearModel.value,
      mileage: parseFloat(mileage.value) || 0,
      status: status.value,
      photoUrl: chosenPhoto
    } : v);
    persistVehicles(updated);
  } else {
    const newVehicle: Vehicle = {
      id: "veh_" + Date.now(),
      code: "VEH-" + (100 + vehicles.value.length),
      model: marca.value,
      plate: placa.value,
      type: tipo.value,
      status: status.value,
      year: yearModel.value || "2024",
      mileage: parseFloat(mileage.value) || 0,
      photoUrl: chosenPhoto
    };
    persistVehicles([newVehicle, ...vehicles.value]);
  }

  cancelEdit();
};

const handleEditVehicle = (v: Vehicle) => {
  editVehicleId.value = v.id;
  marca.value = v.model;
  placa.value = v.plate;
  tipo.value = v.type;
  yearModel.value = v.year;
  mileage.value = v.mileage.toString();
  status.value = v.status;
  photoPreview.value = v.photoUrl || null;
};

const handleDeleteVehicle = (id: string) => {
  if (window.confirm("¿Está seguro de remover este vehículo de la flota?")) {
    const updated = vehicles.value.filter(v => v.id !== id);
    persistVehicles(updated);
  }
};

const recentVehicles = computed(() => vehicles.value.slice(0, 4));

const filteredVehicles = computed(() => {
  const query = searchTerm.value.toLowerCase();
  return vehicles.value.filter(v => 
    v.model.toLowerCase().includes(query) ||
    v.plate.toLowerCase().includes(query) ||
    v.code.toLowerCase().includes(query)
  );
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
</style>
