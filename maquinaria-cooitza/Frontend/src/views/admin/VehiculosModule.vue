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
          <div class="bg-white border border-[#cbd5e1] p-6 relative overflow-hidden rounded-2xl">
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
                  class="w-full border border-[#cbd5e1] focus:border-[#0054A3] text-xs py-2 px-3 outline-none text-slate-800 rounded-2xl"
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
                  class="w-full border border-[#cbd5e1] focus:border-[#0054A3] text-xs py-2 px-3 outline-none text-slate-800 rounded-2xl"
                  required
                />
              </div>

              <div class="flex flex-col gap-1">
                <label class="font-display text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
                  Tipo de Vehículo
                </label>
                <select 
                  v-model="tipo"
                  class="w-full border border-[#cbd5e1] focus:border-[#0054A3] text-xs py-2 px-3 bg-white outline-none cursor-pointer text-slate-800 rounded-2xl"
                >
                  <option value="Camión">Camión</option>
                  <option value="Pickup">Pickup</option>
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
                  class="w-full border border-[#cbd5e1] focus:border-[#0054A3] text-xs py-2 px-3 outline-none text-slate-800 rounded-2xl"
                />
              </div>

              <div class="flex flex-col gap-1 md:col-span-2">
                <label class="font-display text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
                  Piloto Asignado
                </label>
                <select 
                  v-model="piloto_id"
                  class="w-full border border-[#cbd5e1] focus:border-[#0054A3] text-xs py-2 px-3 bg-white outline-none cursor-pointer text-slate-800 rounded-2xl"
                >
                  <option value="">-- Sin piloto asignado --</option>
                  <option v-for="p in pilotos" :key="p.id" :value="p.id">
                    {{ p.nombre }}
                  </option>
                </select>
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
                    class="w-full border border-[#cbd5e1] focus:border-[#0054A3] text-xs py-2 pl-3 pr-12 outline-none text-slate-800 rounded-2xl"
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
                  class="w-full border border-[#cbd5e1] focus:border-[#0054A3] text-xs py-2 px-3 bg-white outline-none cursor-pointer text-slate-800 rounded-2xl"
                >
                  <option value="activo">Operativo / Activo</option>
                  <option value="inactivo">Taller / Inactivo</option>
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
                  class="border-2 border-dashed p-8 flex flex-col items-center justify-center gap-3 transition-colors cursor-pointer group rounded-lg"
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
                        class="absolute top-1 right-1 bg-red-600 text-white p-0.5 rounded-lg hover:bg-red-700"
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
                  class="w-full md:w-auto px-6 py-2.5 bg-[#FFD200] text-[#0054A3] font-display text-xs font-black uppercase tracking-widest hover:brightness-105 transition-all cursor-pointer shadow-sm rounded-xl"
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
              class="bg-white border border-[#cbd5e1] p-3 flex items-center gap-3 hover:border-[#0054A3] transition-all cursor-pointer group shadow-sm select-none rounded-2xl"
              @click="handleEditVehicle(v)"
              title="Haga clic para editar los datos de este vehículo"
            >
              <!-- Photo container -->
              <div class="w-16 h-16 bg-slate-100 flex-shrink-0 overflow-hidden relative border border-slate-100">
                <img 
                  :src="getPhotoUrl(v.foto)" 
                  :alt="v.modelo" 
                  class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-300"
                />
              </div>

              <div class="flex-grow min-w-0">
                <p class="font-display text-[10px] font-bold text-slate-400 uppercase truncate">
                  {{ v.marca }} ({{ v.modelo }})
                </p>
                <p class="font-display text-sm font-black text-slate-800 tracking-tight mt-0.5 truncate">
                  {{ v.placa }}
                </p>
                <p class="font-display text-[9px] text-[#0054A3] font-bold mt-0.5 uppercase truncate">
                  Piloto: {{ getPilotName(v.piloto_id) }}
                </p>
                <div class="flex items-center gap-1.5 mt-1.5">
                  <span class="w-2 h-2 rounded-full"
                        :class="v.status === 'activo' ? 'bg-emerald-500' : 'bg-red-500'">
                  </span>
                  <span class="font-display text-[9px] font-extrabold text-slate-600 uppercase tracking-wider">
                    {{ v.status === "activo" ? "Operativo" : "Inactivo" }}
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
            class="w-full mt-2 py-3 border border-[#cbd5e1] hover:border-[#0054A3] font-display text-[10px] font-black uppercase text-slate-600 hover:text-[#0054A3] transition-all flex items-center justify-center gap-2 cursor-pointer bg-white rounded-2xl"
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
                    class="pl-9 pr-3 py-1.5 bg-white border border-[#cbd5e1] text-xs font-semibold outline-none focus:border-[#0054A3] w-full text-slate-800 rounded-2xl"
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
                      <td class="py-3 px-3 font-mono font-bold text-[#0054A3]">{{ v.id }}</td>
                      <td class="py-3 px-3">
                        <div class="flex items-center gap-2">
                          <img v-if="v.foto" :src="getPhotoUrl(v.foto)" alt="" class="w-6 h-6 object-cover bg-slate-100" />
                          <span class="font-semibold">{{ v.marca }}</span>
                        </div>
                      </td>
                      <td class="py-3 px-3 font-mono font-bold">{{ v.placa }}</td>
                      <td class="py-3 px-3">
                        <span class="uppercase text-slate-500 font-bold">{{ v.modelo }}</span>
                        <span class="ml-1 text-[10px] bg-slate-100 px-1 py-0.5 uppercase border rounded-lg font-semibold text-slate-600">
                          {{ v.tipo }}
                        </span>
                      </td>
                      <td class="py-3 px-3 font-mono font-bold">
                        {{ v.kilometraje_registro.toLocaleString() }} <span class="text-[10px] text-slate-400">KM</span>
                      </td>
                      <td class="py-3 px-3">
                        <span class="px-2 py-0.5 rounded text-[9px] font-extrabold uppercase"
                              :class="v.status === 'activo' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'">
                          {{ v.status === "activo" ? "Operativo" : "Inactivo" }}
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
import Swal from 'sweetalert2';

interface Vehicle {
  id: string;
  marca: string;
  placa: string;
  tipo: "Camión" | "Pickup";
  status: "activo" | "inactivo";
  modelo: string;
  kilometraje_registro: number;
  piloto_id: string | null;
  foto?: string | null;
}

const emit = defineEmits<{
  (e: 'vehiclesChange', count: number, activeCount: number, maintenanceCount: number): void
}>();

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

const vehicles = ref<Vehicle[]>([]);
const pilotos = ref<any[]>([]);
const editVehicleId = ref<string | null>(null);

const marca = ref("");
const placa = ref("");
const tipo = ref<"Camión" | "Pickup">("Camión");
const yearModel = ref("");
const mileage = ref("");
const piloto_id = ref("");
const status = ref<"activo" | "inactivo">("activo");

const photoPreview = ref<string | null>(null);
const selectedFile = ref<File | null>(null);

const isDragOver = ref(false);
const showInvoiceModal = ref(false);
const searchTerm = ref("");
const fileInputRef = ref<HTMLInputElement | null>(null);

const triggerSync = (updated: Vehicle[]) => {
  const active = updated.filter(v => v.status === "activo").length;
  const inact = updated.filter(v => v.status === "inactivo").length;
  emit('vehiclesChange', updated.length, active, inact);
};

const getPhotoUrl = (fotoPath?: string | null) => {
  if (fotoPath) {
    return `/maquinaria-cooitza/Backend/${fotoPath}`;
  }
  return "https://lh3.googleusercontent.com/aida-public/AB6AXuBfCQxHMoSqylJtj62363vrKS4Ai0aSb8qAVt7Vxe7OrqjIXMky93gYA8fkKJ5NI234BDTazq23zLhJnD2FS5s7l6F6n53lXwZt9ykMZ1mHgocxXB85X1OimLy6_6zeYidMZPGnl51KC3KG2QK0v-25MkkEOFHoTzq3XSaYsi8wqQQ4E9FhsapVEDRzsLqlWWh_bSjIN7hgooh7Eno7Co11U4_AFWZ5F1x6PV8KiOhzF9aAvednwsyE0P7Pmgnvfo9FIu6x7CJDYFv4";
};

const getPilotName = (id: string | null) => {
  if (!id) return 'N/A';
  const p = pilotos.value.find(x => x.id == id);
  return p ? p.nombre : 'N/A';
};

const loadData = async () => {
  try {
    const [resVeh, resUsu] = await Promise.all([
      fetch('/maquinaria-cooitza/Backend/api/v1/vehiculos'),
      fetch('/maquinaria-cooitza/Backend/api/v1/pilotos')
    ]);
    
    if (resVeh.ok) {
      const jsonVeh = await resVeh.json();
      vehicles.value = jsonVeh.data;
      triggerSync(vehicles.value);
    }
    
    if (resUsu.ok) {
      const jsonUsu = await resUsu.json();
      pilotos.value = jsonUsu.data;
    }
  } catch (e) {
    console.error("Error cargando datos", e);
  }
};

onMounted(() => {
  loadData();
});

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
  } else {
    Swal.fire('Formato no válido', 'Por favor selecciona una imagen válida.', 'warning');
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
  tipo.value = "Camión";
  yearModel.value = "";
  mileage.value = "";
  piloto_id.value = "";
  status.value = "activo";
  photoPreview.value = null;
  selectedFile.value = null;
  if (fileInputRef.value) fileInputRef.value.value = "";
};

const handleSaveVehicle = async () => {
  if (!marca.value.trim() || !placa.value.trim()) return;

  const formData = new FormData();
  formData.append('marca', marca.value.trim());
  formData.append('placa', placa.value.trim());
  formData.append('tipo', tipo.value);
  formData.append('modelo', yearModel.value.trim());
  formData.append('kilometraje_registro', mileage.value.toString());
  formData.append('piloto_id', piloto_id.value);
  formData.append('status', status.value);

  if (selectedFile.value) {
    formData.append('foto', selectedFile.value);
  }

  try {
    let url = '/maquinaria-cooitza/Backend/api/v1/vehiculos';
    if (editVehicleId.value) {
      url = '/maquinaria-cooitza/Backend/api/v1/vehiculos/update';
      formData.append('id', editVehicleId.value);
    }

    const res = await fetch(url, {
      method: 'POST',
      body: formData
    });

    if (res.ok) {
      await loadData();
      Toast.fire({
        icon: 'success',
        title: editVehicleId.value ? `Vehículo ${placa.value} actualizado.` : `Vehículo ${placa.value} registrado con éxito.`
      });
      cancelEdit();
    } else {
      const data = await res.json();
      Swal.fire('Error', data.message || 'Error al guardar el vehículo', 'error');
    }
  } catch (e) {
    console.error(e);
    Swal.fire('Error', 'Problema de conexión con el servidor', 'error');
  }
};

const handleEditVehicle = (v: Vehicle) => {
  editVehicleId.value = v.id;
  marca.value = v.marca;
  placa.value = v.placa;
  tipo.value = v.tipo;
  yearModel.value = v.modelo;
  mileage.value = v.kilometraje_registro.toString();
  piloto_id.value = v.piloto_id || "";
  status.value = v.status;
  photoPreview.value = getPhotoUrl(v.foto);
  selectedFile.value = null;
};

const handleDeleteVehicle = async (id: string) => {
  const result = await Swal.fire({
    title: '¿Eliminar vehículo?',
    text: "El registro de este vehículo se eliminará permanentemente.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ba1a1a',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, eliminar'
  });

  if (result.isConfirmed) {
    try {
      const res = await fetch('/maquinaria-cooitza/Backend/api/v1/vehiculos/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      if (res.ok) {
        await loadData();
        Toast.fire({ icon: 'success', title: 'Vehículo eliminado de la flota.' });
      }
    } catch (e) {
      console.error(e);
    }
  }
};

const recentVehicles = computed(() => vehicles.value.slice(0, 4));

const filteredVehicles = computed(() => {
  const query = searchTerm.value.toLowerCase();
  return vehicles.value.filter(v => 
    v.marca.toLowerCase().includes(query) ||
    v.placa.toLowerCase().includes(query) ||
    v.modelo.toLowerCase().includes(query) ||
    (getPilotName(v.piloto_id).toLowerCase().includes(query))
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
