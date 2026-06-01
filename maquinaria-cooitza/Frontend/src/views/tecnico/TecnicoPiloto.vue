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
    <main class="w-full max-w-[640px] bg-surface-container-lowest border border-outline-variant p-8 shadow-sm">
      
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
              class="w-full bg-surface-container-lowest border border-outline-variant py-3 px-4 font-sans text-on-surface focus:outline-none appearance-none opacity-70 cursor-not-allowed font-medium"
            />
          </div>
        </div>

        <!-- Machine Type Grid -->
        <div class="flex flex-col gap-2">
          <label class="label-caps text-on-surface-variant">Tipo de Maquinaria</label>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-1">
            <button
              v-for="m in machines"
              :key="m.id"
              type="button"
              @click="form.maquina_id = m.id"
              :class="[
                'border p-4 text-center flex flex-col items-center gap-1 transition-all',
                form.maquina_id === m.id 
                  ? 'border-primary bg-primary/10 ring-1 ring-primary' 
                  : 'border-outline-variant hover:border-primary bg-white'
              ]"
            >
              <svg 
                xmlns="http://www.w3.org/2000/svg" 
                width="24" 
                height="24" 
                viewBox="0 0 24 24" 
                fill="none" 
                stroke="currentColor" 
                stroke-width="2" 
                stroke-linecap="round" 
                stroke-linejoin="round"
                :class="form.maquina_id === m.id ? 'text-primary' : 'text-on-surface-variant'"
                v-html="m.iconPath"
              ></svg>
              <span :class="['font-display text-sm font-medium', form.maquina_id === m.id ? 'text-primary font-bold' : 'text-on-surface']">
                {{ m.label }}
              </span>
            </button>
          </div>
        </div>

        <!-- Registration Type Toggle -->
        <div class="flex flex-col gap-2">
          <label class="label-caps text-on-surface-variant">Tipo de Registro</label>
          <div class="flex bg-surface-container p-1 gap-1">
            <button
              type="button"
              @click="form.tipo_registro = 'inicial'"
              :class="[
                'flex-1 py-2 text-center font-display text-sm font-medium transition-all',
                form.tipo_registro === 'inicial' 
                  ? 'bg-white border border-outline-variant text-primary shadow-sm' 
                  : 'text-on-surface-variant opacity-60 hover:opacity-100'
              ]"
            >
              Horometro Inicial
            </button>
            <button
              type="button"
              @click="form.tipo_registro = 'final'"
              :class="[
                'flex-1 py-2 text-center font-display text-sm font-medium transition-all',
                form.tipo_registro === 'final' 
                  ? 'bg-white border border-outline-variant text-primary shadow-sm' 
                  : 'text-on-surface-variant opacity-60 hover:opacity-100'
              ]"
            >
              Horometro Final
            </button>
          </div>
        </div>

        <!-- Value Inputs -->
        <Transition name="fade-slide" mode="out-in">
          <div
            :key="form.tipo_registro"
            class="flex flex-col gap-6 p-6 bg-surface-container-low border-l-4 border-primary"
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
                  class="w-full bg-white border border-outline-variant py-3 px-4 font-display font-medium text-on-surface outline-none focus:border-primary"
                />
              </div>
            </div>

            <div class="flex flex-col gap-2">
              <label class="label-caps text-on-surface-variant">Foto del horometro</label>
              <div 
                class="relative border-2 border-dashed border-outline-variant py-8 px-4 flex flex-col items-center gap-2 hover:border-primary focus-within:border-primary cursor-pointer transition-colors bg-white group"
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
            :disabled="isSubmitting"
            class="w-full bg-primary-container py-4 text-on-primary-container font-display text-xl font-bold uppercase tracking-wider hover:brightness-95 transition-all active:scale-[0.98] cursor-pointer disabled:opacity-50"
          >
            {{ isSubmitting ? 'Registrando...' : 'Registrar Operación' }}
          </button>
          <p v-if="submitMessage" :class="['text-center font-display text-xs mt-4 font-bold uppercase', submitError ? 'text-error' : 'text-primary']">
            {{ submitMessage }}
          </p>
        </div>
      </form>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watchEffect } from 'vue';
import { useRouter } from 'vue-router';
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

const props = defineProps<{
  currentUserFullName?: string
}>();

const router = useRouter();

const handleLogout = () => {
  router.push('/login');
};

const isSubmitting = ref(false);
const isFetchingGps = ref(false);
const submitMessage = ref('');
const submitError = ref(false);
const photoPreview = ref(null);
const fileInput = ref<HTMLInputElement | null>(null);

const form = reactive({
  operador: '',
  maquina_id: 'excavadora',
  tipo_registro: 'inicial',
  valor_horometro: null as number | null,
  foto_horometro: null as File | null,
  latitud: 14.6349,
  longitud: -90.5069
});

watchEffect(() => {
  if (props.currentUserFullName) {
    form.operador = props.currentUserFullName;
  }
});

let map: L.Map | null = null;
let marker: L.Marker | null = null;

const machines = [
  { id: 'tractor', label: 'Tractor', iconPath: '<path d="m10 11 11 .9c.6 0 .9.5.8 1.1l-.8 4.5c-.1.5-.6.9-1.1.9H3c-.5 0-1-.4-1.1-.9L1 13c-.1-.6.3-1.1.8-1.1l11-.9"/><path d="M15 11V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v6"/><path d="m8 11V5"/><path d="m15 11 3 5"/><path d="M2 15h19"/><path d="M2 18h19"/><circle cx="5" cy="18" r="2"/><circle cx="19" cy="18" r="2"/>' },
  { id: 'excavadora', label: 'Excavadora', iconPath: '<path d="M2 21h15"/><path d="M5 21v-2a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v2"/><path d="M18 21v-2a1 1 0 0 0-1-1h-1a1 1 0 0 0-1 1v2"/><path d="M8 18V5a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v13"/><path d="m11 8 5 2 4-2v6l-4 2-5-2"/><line x1="15" x2="15" y1="9" y2="17"/>' },
  { id: 'retro', label: 'Retro Excavadora', iconPath: '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.77 3.77z"/>' },
  { id: 'rodo', label: 'Rodo', iconPath: '<circle cx="12" cy="12" r="10"/><line x1="4.93" x2="19.07" y1="4.93" y2="19.07"/>' },
  { id: 'pipa', label: 'Pipa', iconPath: '<path d="M7 7c0-1.1.9-2 2-2s2 .9 2 2c0 2.1-4 4.6-4 4.6S3 9.1 3 7c0-1.1.9-2 2-2s2 .9 2 2Z"/><path d="M12 18c0-1.1.9-2 2-2s2 .9 2 2c0 2.1-4 4.6-4 4.6S8 20.1 8 18c0-1.1.9-2 2-2s2 .9 2 2Z"/><path d="M17 7c0-1.1.9-2 2-2s2 .9 2 2c0 2.1-4 4.6-4 4.6S13 9.1 13 7c0-1.1.9-2 2-2s2 .9 2 2Z"/>' },
  { id: 'volteo', label: 'Camion Volteo', iconPath: '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-5h-4l-2-3h-3.5"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>' },
];

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
  const operadorName = form.operador || props.currentUserFullName || '';

  isSubmitting.value = true;
  submitMessage.value = '';
  submitError.value = false;

  const formData = new FormData();
  formData.append('operador', operadorName);
  formData.append('maquina_id', form.maquina_id);
  formData.append('tipo_registro', form.tipo_registro);
  if (form.valor_horometro !== null) formData.append('valor_horometro', form.valor_horometro.toString());
  if (form.foto_horometro) formData.append('foto_horometro', form.foto_horometro);
  formData.append('latitud', form.latitud.toString());
  formData.append('longitud', form.longitud.toString());

  try {
    const response = await fetch('Backend/api/v1/maquinaria/registro', {
      method: 'POST',
      body: formData
    });

    const result = await response.json();

    if (response.ok) {
      submitMessage.value = 'Registro completado con éxito';
      form.valor_horometro = null;
      form.foto_horometro = null;
      photoPreview.value = null;
    } else {
      submitError.value = true;
      submitMessage.value = result.message || 'Error al enviar el registro';
    }
  } catch (error) {
    submitError.value = true;
    submitMessage.value = 'Error de conexión con el servidor';
    console.error("Submission error:", error);
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(() => {
  if (props.currentUserFullName) {
    form.operador = props.currentUserFullName;
  }
  initMap();
  getGeolocation();
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
