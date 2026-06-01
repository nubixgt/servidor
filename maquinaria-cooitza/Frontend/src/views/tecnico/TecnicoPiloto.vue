<template>
  <div class="min-h-screen flex flex-col items-center py-12 px-6 md:px-8">
    <!-- Header with Cooitzá Logo -->
    <header class="mb-10 text-center flex flex-col items-center">
      <div class="mb-4 max-w-[280px]">
        <img src="../../assets/images/image.png" alt="Cooitzá Logo" class="w-full h-auto object-contain" />
      </div>
      <p class="font-mono-label text-sm text-on-surface-variant mt-6 font-bold uppercase tracking-widest">
        Bitácora de Operación v4.2.1
      </p>
    </header>

    <!-- Main Registration Card -->
    <main class="w-full max-w-[640px] bg-surface-container-lowest border border-outline-variant p-8 shadow-sm rounded-2xl">
      
      <!-- Session state header matching Cooitzá exact design -->
      <div class="flex justify-between items-center bg-slate-100 -mx-8 -mt-8 mb-8 px-8 py-3 border-b border-[#cbd5e1] select-none">
        <div class="flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full animate-pulse bg-emerald-500"></span>
          <span class="font-display text-xs font-black text-[#0054A3] uppercase">
            Rol: Técnico Piloto
          </span>
        </div>
        <div class="flex items-center gap-4">
          <span class="font-sans text-xs text-[#004586] max-w-[150px] truncate text-right font-medium">
            {{ currentUserFullName }}
          </span>
          <button 
            type="button"
            @click="handleLogout"
            class="font-display text-[11px] font-black uppercase text-red-600 hover:underline cursor-pointer"
          >
            Cerrar Sesión
          </button>
        </div>
      </div>

      <form class="flex flex-col gap-8" @submit.prevent="submitForm">
        
        <!-- Pilot Selection (Readonly for Tecnico Piloto) -->
        <div class="flex flex-col gap-2">
          <label class="label-caps text-on-surface-variant">Operador Asignado</label>
          <div class="relative">
            <input 
              type="text" 
              :value="currentUserFullName"
              readonly
              class="w-full bg-surface-container-lowest border border-outline-variant py-3 px-4 font-sans text-on-surface focus:outline-none appearance-none opacity-70 cursor-not-allowed font-medium rounded-xl"
            />
          </div>
        </div>

        <!-- Assigned Asset -->
        <div class="flex flex-col gap-2">
          <label class="label-caps text-on-surface-variant">Equipo Asignado</label>
          <div class="relative">
            <template v-if="assignedAssets.length > 1">
              <select 
                v-model="form.maquina_id"
                class="w-full bg-white border border-outline-variant py-3 px-4 font-sans text-on-surface focus:outline-none focus:border-primary font-medium rounded-xl cursor-pointer"
              >
                <option value="" disabled>Seleccione el equipo a registrar...</option>
                <option v-for="asset in assignedAssets" :key="asset.id" :value="asset.id">
                  {{ asset.label }}
                </option>
              </select>
            </template>
            <template v-else>
              <input 
                type="text" 
                :value="assignedAssets.length === 1 ? assignedAssets[0].label : (isLoadingVehicle ? 'Buscando equipos...' : 'Sin equipo asignado')"
                readonly
                class="w-full bg-surface-container-lowest border border-outline-variant py-3 px-4 font-sans text-on-surface focus:outline-none appearance-none opacity-70 cursor-not-allowed font-medium rounded-xl"
              />
            </template>
          </div>
        </div>

        <!-- Registration Type Toggle -->
        <div class="flex flex-col gap-2">
          <label class="label-caps text-on-surface-variant">Tipo de Registro</label>
          <div class="flex bg-surface-container p-1 gap-1 rounded-xl">
            <button
              type="button"
              @click="form.tipo_registro = 'inicial'"
              :class="[ 'flex-1 py-2 text-center font-display text-sm font-medium transition-all rounded-xl', form.tipo_registro === 'inicial' ? 'bg-white border border-outline-variant text-primary shadow-sm' : 'text-on-surface-variant opacity-60 hover:opacity-100' ]"
            >
              Horometro Inicial
            </button>
            <button
              type="button"
              @click="form.tipo_registro = 'final'"
              :class="[ 'flex-1 py-2 text-center font-display text-sm font-medium transition-all rounded-xl', form.tipo_registro === 'final' ? 'bg-white border border-outline-variant text-primary shadow-sm' : 'text-on-surface-variant opacity-60 hover:opacity-100' ]"
            >
              Horometro Final
            </button>
          </div>
        </div>

        <!-- Value Inputs -->
        <Transition name="fade-slide" mode="out-in">
          <div
            :key="form.tipo_registro"
            class="flex flex-col gap-6 p-6 bg-surface-container-low border-l-4 border-primary rounded-2xl"
          >
            <div class="flex flex-col gap-2">
              <label class="label-caps text-on-surface-variant">
                {{ form.tipo_registro === 'inicial' ? 'Horometro Inicial (Value)' : 'Horometro Final (Value)' }}
              </label>
              <div class="flex items-center">
                <input
                  v-model="form.valor_horometro"
                  type="number"
                  placeholder="00000.0"
                  step="0.1"
                  required
                  class="w-full bg-white border border-outline-variant py-3 px-4 font-display font-medium text-on-surface outline-none focus:border-primary rounded-xl"
                />
              </div>
            </div>

            <div class="flex flex-col gap-2">
              <label class="label-caps text-on-surface-variant">Foto del horometro</label>
              <div 
                class="relative border-2 border-dashed border-outline-variant py-8 px-4 flex flex-col items-center gap-2 hover:border-primary focus-within:border-primary cursor-pointer transition-colors bg-white group rounded-2xl"
                @click="fileInput?.click()"
              >
                <!-- capture="environment" forces the rear camera on mobile devices -->
                <input 
                  ref="fileInput"
                  type="file" 
                  accept="image/*" 
                  capture="environment" 
                  class="hidden" 
                  @change="handleFileUpload"
                  required
                />
                
                <div v-if="!photoPreview" class="flex flex-col items-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-on-surface-variant group-hover:text-primary transition-colors"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                  <span class="font-sans text-sm text-on-surface-variant group-hover:text-on-surface text-center">
                    Toca para abrir la cámara <br>
                    <small class="opacity-70">(Solo permite captura en vivo)</small>
                  </span>
                </div>
                <div v-else class="w-full h-32 relative">
                  <img :src="photoPreview" class="w-full h-full object-cover rounded border border-outline" />
                  <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                    <span class="text-white text-xs font-bold uppercase">Cambiar Foto</span>
                  </div>
                </div>
                
                <span class="font-display text-[10px] uppercase tracking-widest text-outline">
                  SOLO CAPTURA DE CÁMARA
                </span>
              </div>
            </div>
          </div>
        </Transition>

        <!-- Current Location -->
        <div class="flex flex-col gap-2">
          <div class="flex justify-between items-end">
            <label class="label-caps text-on-surface-variant">Ubicación Actual</label>
            <div class="flex flex-col items-end gap-1">
              <span class="font-display text-[10px] text-primary flex items-center gap-1 font-bold">
                <span :class="['w-2 h-2 rounded-full bg-primary', isFetchingGps ? 'animate-ping' : 'animate-pulse']" />
                {{ isFetchingGps ? 'BUSCANDO GPS...' : 'GPS ACTIVO' }}
              </span>
              <button 
                type="button" 
                @click="getGeolocation"
                class="text-[9px] text-primary underline uppercase font-bold tracking-tighter hover:opacity-70 transition-opacity"
              >
                Refrescar mi ubicación
              </button>
            </div>
          </div>
          <div class="h-64 w-full border border-outline-variant bg-surface-container relative overflow-hidden group">
            <!-- Leaflet Map Container -->
            <div id="map" class="h-full w-full z-0"></div>
            
            <div class="absolute bottom-2 right-2 bg-surface-container-lowest/80 backdrop-blur-sm px-2 py-1 border border-outline-variant z-10 pointer-events-none">
              <p class="font-display text-[10px] font-bold text-on-surface">
                {{ form.latitud.toFixed(6) }}° N, {{ form.longitud.toFixed(6) }}° W
              </p>
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="mt-4">
          <button
            type="submit"
            :disabled="isSubmitting || !form.maquina_id"
            class="w-full bg-primary-container py-4 text-on-primary-container font-display text-xl font-bold uppercase tracking-wider hover:brightness-95 transition-all active:scale-[0.98] cursor-pointer disabled:opacity-50"
          >
            {{ isSubmitting ? 'Registrando...' : 'Registrar Operación' }}
          </button>
        </div>
      </form>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watchEffect } from 'vue';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';
// @ts-ignore
import L from 'leaflet';
// @ts-ignore
import 'leaflet/dist/leaflet.css';

// Fix for default marker icons in Leaflet + Vite
// @ts-ignore
import markerIcon from 'leaflet/dist/images/marker-icon.png';
// @ts-ignore
import markerIconRetina from 'leaflet/dist/images/marker-icon-2x.png';
// @ts-ignore
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIconRetina,
  iconUrl: markerIcon,
  shadowUrl: markerShadow,
});

const currentUserFullName = ref('');
const currentUserId = ref<number | null>(null);

const router = useRouter();

const handleLogout = () => {
  router.push('/login');
};

const isSubmitting = ref(false);
const isFetchingGps = ref(false);
const photoPreview = ref(null);
const fileInput = ref<HTMLInputElement | null>(null);

const assignedAssets = ref<{id: string, label: string}[]>([]);
const isLoadingVehicle = ref(true);

const form = reactive({
  operador: '',
  maquina_id: '',
  tipo_registro: 'inicial',
  valor_horometro: null as number | null,
  foto_horometro: null as File | null,
  latitud: 14.6349,
  longitud: -90.5069
});



let map: L.Map | null = null;
let marker: L.Marker | null = null;

const loadAssignedAssets = async () => {
  try {
    const pRes = await fetch('/maquinaria-cooitza/Backend/api/v1/pilotos');
    const pJson = await pRes.json();
    let currentPilotId = null;
    if (pJson.status === 'success') {
      const pilot = pJson.data.find((p: any) => p.nombre.toLowerCase() === currentUserFullName.value.toLowerCase());
      if (pilot) {
        currentPilotId = pilot.id;
      }
    }

    if (currentPilotId) {
      const assets: {id: string, label: string}[] = [];
      const [vRes, mRes] = await Promise.all([
        fetch('/maquinaria-cooitza/Backend/api/v1/vehiculos'),
        fetch('/maquinaria-cooitza/Backend/api/v1/maquinas')
      ]);
      const vJson = await vRes.json();
      const mJson = await mRes.json();

      if (vJson.status === 'success') {
        const vehicles = vJson.data.filter((v: any) => String(v.piloto_id) === String(currentPilotId));
        vehicles.forEach((v: any) => {
          assets.push({ id: v.placa, label: `${v.marca} - ${v.placa} (${v.tipo})` });
        });
      }
      
      if (mJson.status === 'success') {
        const machines = mJson.data.filter((m: any) => String(m.piloto_id) === String(currentPilotId));
        machines.forEach((m: any) => {
          assets.push({ id: m.identificador, label: `${m.marca} - ${m.identificador} (${m.tipo})` });
        });
      }

      assignedAssets.value = assets;

      if (assets.length === 1) {
        form.maquina_id = assets[0].id;
      } else if (assets.length > 1) {
        form.maquina_id = ""; // Force them to select one
      } else {
        form.maquina_id = "";
      }
    } else {
      form.maquina_id = "";
      assignedAssets.value = [];
    }
  } catch (e) {
    console.error(e);
    assignedAssets.value = [];
  } finally {
    isLoadingVehicle.value = false;
  }
};

const initMap = () => {
  map = L.map('map', {
    dragging: false,
    scrollWheelZoom: false,
    doubleClickZoom: false,
    boxZoom: false,
    tap: false,
    touchZoom: false
  }).setView([form.latitud, form.longitud], 15);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map);

  marker = L.marker([form.latitud, form.longitud], {
    draggable: false
  }).addTo(map);
};

const handleFileUpload = (event: Event) => {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  if (file) {
    form.foto_horometro = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      photoPreview.value = e.target?.result as any;
    };
    reader.readAsDataURL(file);
  }
};

const getGeolocation = () => {
  if (navigator.geolocation) {
    isFetchingGps.value = true;
    navigator.geolocation.getCurrentPosition(
      (position) => {
        const { latitude, longitude } = position.coords;
        form.latitud = latitude;
        form.longitud = longitude;
        
        if (map && marker) {
          const newPos: [number, number] = [latitude, longitude];
          map.setView(newPos, 15);
          marker.setLatLng(newPos);
        }
        isFetchingGps.value = false;
      },
      (error) => {
        console.error("Error getting geolocation:", error);
        isFetchingGps.value = false;
      },
      { 
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
      }
    );
  }
};

const submitForm = async () => {
  isSubmitting.value = true;

  const formData = new FormData();
  formData.append('operador', form.operador);
  formData.append('maquina_id', form.maquina_id);
  formData.append('tipo_registro', form.tipo_registro);
  if (form.valor_horometro !== null) formData.append('valor_horometro', form.valor_horometro.toString());
  if (form.foto_horometro) formData.append('foto_horometro', form.foto_horometro);
  formData.append('latitud', form.latitud.toString());
  formData.append('longitud', form.longitud.toString());
  if (currentUserId.value) {
    formData.append('usuario_id', currentUserId.value.toString());
  }

  try {
    const response = await fetch('Backend/api/v1/maquinaria/registro', {
      method: 'POST',
      body: formData
    });

    const result = await response.json();

    if (response.ok) {
      Swal.fire({
        title: '¡Éxito!',
        text: 'Registro completado correctamente',
        icon: 'success',
        confirmButtonColor: '#0054A3'
      });
      form.valor_horometro = null;
      form.foto_horometro = null;
      photoPreview.value = null;
    } else {
      Swal.fire({
        title: 'Error',
        text: result.message || 'Error al enviar el registro',
        icon: 'error',
        confirmButtonColor: '#0054A3'
      });
    }
  } catch (error) {
    Swal.fire({
      title: 'Error',
      text: 'Error de conexión con el servidor',
      icon: 'error',
      confirmButtonColor: '#0054A3'
    });
    console.error("Submission error:", error);
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(() => {
  const userData = localStorage.getItem('cooitza_user');
  if (userData) {
    try {
      const user = JSON.parse(userData);
      currentUserFullName.value = user.full_name || user.nombre || 'Operador Desconocido';
      currentUserId.value = user.id || null;
      form.operador = currentUserFullName.value;
    } catch (e) {
      console.error("Error parsing user data", e);
    }
  }

  initMap();
  getGeolocation();
  loadAssignedAssets();
});
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s ease;
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Ensure Leaflet controls are above the map but below modals */
:deep(.leaflet-container) {
  width: 100%;
  height: 100%;
  z-index: 1;
}
</style>
