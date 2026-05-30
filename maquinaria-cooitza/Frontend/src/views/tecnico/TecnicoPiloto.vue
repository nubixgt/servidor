<template>
  <div 
    class="min-h-screen w-full flex flex-col items-center justify-center p-6 text-[#191c1d] relative font-sans"
    style="background-image: radial-gradient(circle, #cbd5e1 1px, transparent 1px); background-size: 24px 24px; background-color: #f8f9fa;"
  >
    <!-- Visual Identity Title Anchor -->
    <div class="mb-8 text-center select-none">
      <h1 class="font-display text-3xl font-extrabold text-[#0054A3] uppercase tracking-wider mb-1">
        IndustrialMS
      </h1>
      <p class="font-display text-[10px] font-bold tracking-[0.12em] text-[#004586]/75 uppercase">
        Protocolo de Eficiencia Clínica v2.4
      </p>
    </div>

    <main class="w-full max-w-[500px] relative z-10">
      
      <!-- Form Container -->
      <div class="bg-white border border-[#cbd5e1] p-8 relative shadow-sm flex flex-col gap-6">
        <!-- Header Accent Line -->
        <div class="absolute top-0 left-0 w-full h-[3px] bg-[#FFD200]"></div>

        <!-- Session state header matching Cooitzá exact design -->
        <div class="flex justify-between items-center bg-slate-100 -mx-8 -mt-8 px-8 py-3 border-b border-[#cbd5e1] select-none">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full animate-pulse" :class="registrationSuccess ? 'bg-green-500' : 'bg-emerald-500'"></span>
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

        <header class="mb-2 select-none border-b border-[#cbd5e1]/40 pb-4">
          <div class="flex items-center gap-2 mb-1.5">
            <span class="w-2 h-2 rounded-full bg-[#FFD200]"></span>
            <span class="font-display text-[11px] font-bold uppercase text-[#0054A3] tracking-wider">
              Estado Enlace: {{ statusText }}
            </span>
          </div>
          
          <h2 class="font-display text-2xl font-black tracking-tight text-[#191c1d] uppercase">
            Registro de Piloto
          </h2>
          <p class="text-xs leading-relaxed text-[#004586] mt-2 font-medium">
            Gestione e inscriba la autorización de pilotos que despliegan operaciones técnico-industriales en campo.
          </p>
        </header>

        <form class="space-y-5" @submit.prevent="handleFormSubmit">
          
          <!-- Error alerts inside the form layout -->
          <div v-if="errorMessage" class="bg-red-50 border-l-4 border-red-500 p-3.5 text-xs text-red-800 font-sans flex items-center gap-2">
            <XSquare class="w-4 h-4 text-red-600 flex-shrink-0" />
            <span class="font-medium">{{ errorMessage }}</span>
          </div>

          <!-- Field: Name -->
          <div class="flex flex-col gap-1.5">
            <label class="text-slate-600 text-xs font-bold uppercase tracking-wider block" for="pilot_name">
              Nombre Completo del Operador
            </label>
            <div class="relative group">
              <input 
                id="pilot_name" 
                name="pilot_name" 
                type="text" 
                required
                placeholder="EJ. MARCO A. SANDOVAL"
                v-model="pilotName"
                class="w-full bg-slate-50 border border-[#cbd5e1] p-3 font-sans text-sm text-slate-800 focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] focus:bg-white outline-none appearance-none transition-all uppercase font-medium placeholder:text-slate-400"
              />
            </div>
          </div>

          <!-- Field: Phone -->
          <div class="flex flex-col gap-1.5">
            <label class="text-slate-600 text-xs font-bold uppercase tracking-wider block" for="pilot_phone">
              Teléfono de Enlace Directo
            </label>
            <div class="relative">
              <input 
                id="pilot_phone" 
                name="pilot_phone" 
                type="tel" 
                required
                placeholder="+502 0000-0000"
                v-model="pilotPhone"
                class="w-full bg-slate-50 border border-[#cbd5e1] p-3 font-sans text-sm text-slate-800 focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] focus:bg-white outline-none appearance-none transition-all font-medium placeholder:text-slate-400"
              />
            </div>
          </div>

          <!-- Field: Machinery List -->
          <div class="flex flex-col gap-1.5">
            <label class="text-slate-600 text-xs font-bold uppercase tracking-wider block">
              Listado de Maquinarias Autorizadas
            </label>
            <div class="border border-[#cbd5e1] divide-y divide-[#cbd5e1] bg-slate-50 shadow-inner">
              <label 
                v-for="machinery in machineryOptions"
                :key="machinery.id" 
                @click.prevent="handleCheckboxChange(machinery.id)"
                class="flex items-center px-4 py-3 cursor-pointer transition-colors group select-none"
                :class="selectedMachinery.includes(machinery.id) ? 'bg-[#0054A3]/5' : 'hover:bg-slate-100'"
              >
                <!-- Custom Simulated checkbox with premium Cooitzá blue & yellow style -->
                <div class="w-5 h-5 border flex items-center justify-center mr-3.5 transition-all duration-150"
                     :class="selectedMachinery.includes(machinery.id) ? 'border-[#0054A3] bg-[#0054A3] text-white' : 'border-[#cbd5e1] bg-white group-hover:border-[#FFD200]'">
                  <svg v-if="selectedMachinery.includes(machinery.id)" class="w-3.5 h-3.5 text-white stroke-2 fill-none stroke-current" viewBox="0 0 24 24">
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                </div>

                <span class="font-display text-xs font-bold uppercase tracking-tight transition-colors"
                      :class="selectedMachinery.includes(machinery.id) ? 'text-[#0054A3]' : 'text-slate-600 group-hover:text-[#191c1d]'">
                  {{ machinery.label }}
                </span>
              </label>
            </div>
          </div>

          <!-- Technical Footer / CTA -->
          <div class="pt-2 mt-6 flex flex-col gap-3">
            <div v-if="registrationSuccess" class="w-full bg-green-600 text-white font-display text-xs font-bold tracking-[0.1em] py-3.5 px-4 flex items-center justify-center gap-2 uppercase shadow-sm">
              <CheckCircle :size="18" />
              <span>REGISTRO EXPORTADO CORRECTAMENTE</span>
            </div>
            <button v-else
              type="submit"
              :disabled="isSubmitting"
              class="w-full bg-[#FFD200] text-[#002d58] font-display text-xs font-black tracking-[0.12em] py-3.5 px-4 flex items-center justify-center gap-2 hover:opacity-95 active:scale-[0.99] transition-all cursor-pointer disabled:opacity-75 disabled:cursor-wait shadow-sm uppercase"
            >
              <template v-if="isSubmitting">
                <Loader2 :size="18" class="animate-spin text-[#0054A3]" />
                <span>PROCESANDO ENLACE...</span>
              </template>
              <template v-else>
                <ClipboardSignature :size="18" class="text-[#0054A3]" />
                <span>REGISTRAR OPERADOR TÉCNICO</span>
              </template>
            </button>

            <button 
              type="button"
              @click="handleLogout"
              class="w-full border border-[#cbd5e1] text-[#004586] bg-white hover:bg-slate-50 font-display text-xs font-black tracking-[0.12em] py-3.5 px-4 flex items-center justify-center gap-2 transition-all cursor-pointer uppercase shadow-sm"
            >
              CANCELAR OPERACIÓN
            </button>
          </div>

        </form>

        <!-- Registration Success feedback sub-alert -->
        <transition name="scale">
          <div v-if="registrationSuccess" class="mt-2 bg-emerald-50 border border-emerald-200 p-4 shadow-sm">
            <div class="flex gap-2.5">
              <CheckCircle class="text-emerald-700 shrink-0 mt-0.5" :size="16" />
              <div class="text-xs font-sans text-emerald-800">
                <p class="font-bold">Formulario técnico transmitido con éxito.</p>
                <p class="mt-1">Piloto registrado permanentemente en el sistema central de bitácoras de Cooitzá R.L.</p>
              </div>
            </div>
          </div>
        </transition>

      </div>

      <!-- System Metadata -->
      <div class="mt-6 flex justify-between items-center px-1 text-slate-500 select-none">
        <span class="font-display text-[10px] font-bold uppercase tracking-[0.1em]">
          Auth Token: 992-PX-77
        </span>
        <div class="flex gap-4">
          <span class="font-display text-[10px] font-bold uppercase tracking-[0.1em] flex items-center gap-1.5">
            <Wifi :size="12" class="text-[#004586]/70" /> Online
          </span>
          <span class="font-display text-[10px] font-bold uppercase tracking-[0.1em] flex items-center gap-1.5">
            <Database :size="12" class="text-[#004586]/70" /> Sync
          </span>
        </div>
      </div>

    </main>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { 
  ClipboardSignature, Wifi, Database, Loader2, CheckCircle, XSquare 
} from 'lucide-vue-next';

import { useRouter } from 'vue-router';

const router = useRouter();

const props = defineProps<{
  currentUserFullName?: string
}>();

const handleLogout = () => {
  router.push('/login');
};

const pilotName = ref("");
const pilotPhone = ref("");
const selectedMachinery = ref<string[]>([]);

const statusText = ref("Listo para Transmitir");
const isSubmitting = ref(false);
const registrationSuccess = ref(false);
const errorMessage = ref("");

const machineryOptions = [
  { id: "excavator-x1", label: "Excavadora Hidráulica X1-B" },
  { id: "crane-tower", label: "Grúa Torre Automatizada" },
  { id: "loader-industrial", label: "Cargador Frontal Industrial" },
  { id: "telemetry-node", label: "Nodo de Telemetría Móvil" }
];

const handleCheckboxChange = (id: string) => {
  if (selectedMachinery.value.includes(id)) {
    selectedMachinery.value = selectedMachinery.value.filter(item => item !== id);
  } else {
    selectedMachinery.value.push(id);
  }
};

const handleFormSubmit = () => {
  errorMessage.value = "";

  if (!pilotName.value.trim()) {
    errorMessage.value = "Por favor ingrese el nombre completo del piloto.";
    return;
  }
  if (!pilotPhone.value.trim()) {
    errorMessage.value = "Por favor ingrese un teléfono de enlace válido.";
    return;
  }
  if (selectedMachinery.value.length === 0) {
    errorMessage.value = "Debe seleccionar al menos una maquinaria autorizada.";
    return;
  }

  isSubmitting.value = true;
  statusText.value = "Procesando Enlace...";

  setTimeout(() => {
    const newRegistration = {
      pilot_name: pilotName.value.toUpperCase().trim(),
      pilot_phone: pilotPhone.value.trim(),
      machinery: selectedMachinery.value,
      registeredAt: new Date().toLocaleString("es-GT")
    };

    try {
      const savedPilots = localStorage.getItem("cooitza_machinery_pilots");
      const currentPilots = savedPilots ? JSON.parse(savedPilots) : [];
      currentPilots.unshift(newRegistration);
      localStorage.setItem("cooitza_machinery_pilots", JSON.stringify(currentPilots));
    } catch (err) {
      console.error("Error saving pilot registration", err);
    }

    isSubmitting.value = false;
    registrationSuccess.value = true;
    statusText.value = "Transmitido de Forma Estable";

    setTimeout(() => {
      pilotName.value = "";
      pilotPhone.value = "";
      selectedMachinery.value = [];
      registrationSuccess.value = false;
      statusText.value = "Listo para Transmitir";
    }, 3000);

  }, 1500);
};
</script>

<style scoped>
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
