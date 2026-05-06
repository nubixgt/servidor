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
      <form class="flex flex-col gap-8" @submit.prevent="submitForm">
        
        <!-- Pilot Selection -->
        <div class="flex flex-col gap-2">
          <label class="label-caps text-on-surface-variant">Operador Asignado</label>
          <div class="relative">
            <select 
              v-model="form.operador"
              required
              class="w-full bg-surface-container-lowest border border-outline-variant py-3 px-4 font-sans text-on-surface focus:ring-1 focus:ring-primary focus:border-primary outline-none appearance-none transition-all"
            >
              <option value="">Seleccionar Operador...</option>
              <option v-for="op in operadores" :key="op" :value="op">{{ op }}</option>
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-on-surface-variant">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </div>
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
                @click="$refs.fileInput.click()"
              >
                <input 
                  ref="fileInput"
                  type="file" 
                  accept="image/*" 
                  capture="camera" 
                  class="hidden" 
                  @change="handleFileUpload"
                  required
                />
                
                <div v-if="!photoPreview" class="flex flex-col items-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-on-surface-variant group-hover:text-primary transition-colors"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                  <span class="font-sans text-sm text-on-surface-variant group-hover:text-on-surface">
                    Tap to take photo
                  </span>
                </div>
                <div v-else class="w-full h-32 relative">
                  <img :src="photoPreview" class="w-full h-full object-cover rounded border border-outline" />
                  <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                    <span class="text-white text-xs font-bold uppercase">Change Photo</span>
                  </div>
                </div>
                
                <span class="font-display text-[10px] uppercase tracking-widest text-outline">
                  CAMERA ONLY REQUIRED
                </span>
              </div>
            </div>
          </div>
        </Transition>

        <!-- Current Location -->
        <div class="flex flex-col gap-2">
          <div class="flex justify-between items-end">
            <label class="label-caps text-on-surface-variant">Ubicación Actual</label>
            <span class="font-display text-[10px] text-primary flex items-center gap-1 font-bold">
              <span class="w-2 h-2 rounded-full bg-primary animate-pulse" />
              GPS ACTIVO
            </span>
          </div>
          <div class="h-48 w-full border border-outline-variant bg-surface-container relative overflow-hidden group">
            <!-- Simple placeholder for map, ideally use Leaflet or Google Maps here if needed -->
            <img
              src="https://maps.googleapis.com/maps/api/staticmap?center=14.6349,-90.5069&zoom=15&size=600x300&key=YOUR_API_KEY"
              :src="`https://maps.googleapis.com/maps/api/staticmap?center=${form.latitud},${form.longitud}&zoom=15&size=600x300&sensor=false`"
              alt="Construction map"
              class="w-full h-full object-cover grayscale opacity-50 group-hover:opacity-70 transition-opacity duration-700"
            />
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
              <div class="relative">
                <div class="absolute inset-0 bg-primary/20 rounded-full animate-ping scale-150" />
                <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center border border-primary relative">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                </div>
              </div>
            </div>
            <div class="absolute bottom-2 right-2 bg-surface-container-lowest/80 backdrop-blur-sm px-2 py-1 border border-outline-variant">
              <p class="font-display text-[10px] font-bold text-on-surface">
                {{ form.latitud.toFixed(4) }}° N, {{ form.longitud.toFixed(4) }}° W
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

    <!-- Footer -->
    <footer class="mt-12 w-full max-w-5xl flex flex-col md:flex-row justify-between items-center opacity-70 hover:opacity-100 transition-opacity duration-300 gap-6">
      <div class="font-display text-xs font-bold text-primary tracking-tight">
        COOITZÁ - Cooperativa de Ahorro y Crédito
      </div>
      <nav class="flex flex-wrap justify-center gap-6 font-display text-[10px] uppercase font-bold tracking-widest text-on-surface-variant">
        <a href="#" class="hover:text-primary transition-colors">Términos de Operación</a>
        <a href="#" class="hover:text-primary transition-colors">Protocolos de Seguridad</a>
        <a href="#" class="hover:text-primary transition-colors">Política de Privacidad</a>
      </nav>
      <div class="font-display text-[10px] uppercase font-medium text-on-surface-variant opacity-60">
        © 2026 Maquinaria Cooitzá División de Maquinaria Pesada.
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';

const isSubmitting = ref(false);
const submitMessage = ref('');
const submitError = ref(false);
const photoPreview = ref(null);

const form = reactive({
  operador: '',
  maquina_id: 'excavadora',
  tipo_registro: 'inicial',
  valor_horometro: null,
  foto_horometro: null,
  latitud: 14.6349,
  longitud: -90.5069
});

const operadores = [
  'Henry Vasquez',
  'Oscar Choc',
  'Victor Ruano',
  'Edin Jalal',
  'Ulises Ruano',
  'Eduardo Choc',
  'Gabriel Tun',
  'Alejandro del Cid'
];

const machines = [
  { id: 'tractor', label: 'Tractor', iconPath: '<path d="m10 11 11 .9c.6 0 .9.5.8 1.1l-.8 4.5c-.1.5-.6.9-1.1.9H3c-.5 0-1-.4-1.1-.9L1 13c-.1-.6.3-1.1.8-1.1l11-.9"/><path d="M15 11V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v6"/><path d="m8 11V5"/><path d="m15 11 3 5"/><path d="M2 15h19"/><path d="M2 18h19"/><circle cx="5" cy="18" r="2"/><circle cx="19" cy="18" r="2"/>' },
  { id: 'excavadora', label: 'Excavadora', iconPath: '<path d="M2 21h15"/><path d="M5 21v-2a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v2"/><path d="M18 21v-2a1 1 0 0 0-1-1h-1a1 1 0 0 0-1 1v2"/><path d="M8 18V5a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v13"/><path d="m11 8 5 2 4-2v6l-4 2-5-2"/><line x1="15" x2="15" y1="9" y2="17"/>' },
  { id: 'retro', label: 'Retro Excavadora', iconPath: '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.77 3.77z"/>' },
  { id: 'rodo', label: 'Rodo', iconPath: '<circle cx="12" cy="12" r="10"/><line x1="4.93" x2="19.07" y1="4.93" y2="19.07"/>' },
  { id: 'pipa', label: 'Pipa', iconPath: '<path d="M7 7c0-1.1.9-2 2-2s2 .9 2 2c0 2.1-4 4.6-4 4.6S3 9.1 3 7c0-1.1.9-2 2-2s2 .9 2 2Z"/><path d="M12 18c0-1.1.9-2 2-2s2 .9 2 2c0 2.1-4 4.6-4 4.6S8 20.1 8 18c0-1.1.9-2 2-2s2 .9 2 2Z"/><path d="M17 7c0-1.1.9-2 2-2s2 .9 2 2c0 2.1-4 4.6-4 4.6S13 9.1 13 7c0-1.1.9-2 2-2s2 .9 2 2Z"/>' },
  { id: 'volteo', label: 'Camion Volteo', iconPath: '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-5h-4l-2-3h-3.5"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>' },
];

const handleFileUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.foto_horometro = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      photoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const getGeolocation = () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        form.latitud = position.coords.latitude;
        form.longitud = position.coords.longitude;
      },
      (error) => {
        console.error("Error getting geolocation:", error);
      },
      { enableHighAccuracy: true }
    );
  }
};

const submitForm = async () => {
  isSubmitting.value = true;
  submitMessage.value = '';
  submitError.value = false;

  const formData = new FormData();
  formData.append('operador', form.operador);
  formData.append('maquina_id', form.maquina_id);
  formData.append('tipo_registro', form.tipo_registro);
  formData.append('valor_horometro', form.valor_horometro);
  formData.append('foto_horometro', form.foto_horometro);
  formData.append('latitud', form.latitud);
  formData.append('longitud', form.longitud);

  try {
    // Adjust the URL as needed for your environment
    const response = await fetch('Backend/api/v1/maquinaria/registro', {
      method: 'POST',
      body: formData
    });

    const result = await response.json();

    if (response.ok) {
      submitMessage.value = 'Registro completado con éxito';
      // Reset form
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
</style>
