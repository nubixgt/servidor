<template>
  <div v-if="submitSuccess">
    <transition name="scale" appear>
      <div class="w-full max-w-[640px] bg-white border border-[#0054A3] p-8 text-center flex flex-col items-center gap-6 shadow-md">
        <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center text-emerald-600">
          <CheckCircle :size="44" />
        </div>
        <div class="flex flex-col gap-2">
          <h2 class="font-display text-2xl font-bold text-[#0054A3]">
            Registro Guardado Exitosamente
          </h2>
          <p class="font-sans text-sm text-slate-600 max-w-sm mx-auto">
            La telemetría industrial del operador <strong class="text-slate-800">{{ operatorName }}</strong> para la <strong class="text-slate-800">{{ machines.find(m => m.id === machineType)?.label || machineType }}</strong> ha sido transmitida de manera segura.
          </p>
        </div>

        <!-- Diagnostic details -->
        <div class="w-full bg-slate-50 p-4 border border-[#cbd5e1] rounded text-left font-mono text-xs flex flex-col gap-2 text-slate-600">
          <div><span class="text-[#0054A3] font-bold">FECHA/HORA:</span> {{ new Date().toLocaleString("es-GT") }}</div>
          <div><span class="text-[#0054A3] font-bold">HORÓMETRO:</span> {{ horometroValue }} HRS ({{ regType === 'incinal' ? 'Inicial' : 'Final' }})</div>
          <div><span class="text-[#0054A3] font-bold">UBICACIÓN:</span> {{ latitude }}° N, {{ longitude }}° W</div>
          <div><span class="text-[#0054A3] font-bold">ESTADO:</span> TRANSMITIDO / OK</div>
        </div>

        <button
          type="button"
          @click="resetForm"
          class="bg-[#0054A3] hover:bg-[#004586] text-white py-3 px-6 font-display text-xs font-bold uppercase tracking-widest cursor-pointer transition-colors"
        >
          Nuevo Registro de Operación
        </button>
      </div>
    </transition>
  </div>

  <div v-else class="w-full max-w-[640px] bg-white border border-[#cbd5e1] p-8 shadow-sm flex flex-col gap-6">
    <!-- Session state header -->
    <div class="flex justify-between items-center bg-slate-100 -mx-8 -mt-8 px-8 py-3 border-b border-[#cbd5e1]">
      <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse"></span>
        <span class="font-display text-xs font-black text-[#0054A3] uppercase">
          Rol: Técnico
        </span>
      </div>
      <div class="flex items-center gap-4">
        <span class="font-sans text-xs text-slate-600 max-w-[150px] truncate text-right">
          {{ currentUserFullName }}
        </span>
        <button 
          type="button"
          @click="$emit('logout')"
          class="font-display text-[11px] font-black uppercase text-red-600 hover:underline cursor-pointer"
        >
          Cerrar Sesión
        </button>
      </div>
    </div>

    <form class="flex flex-col gap-6" @submit.prevent="handleFormSubmit">
      
      <div v-if="errorText" class="bg-amber-50 border-l-4 border-amber-500 p-3 text-xs text-amber-800 font-medium font-sans flex items-center gap-2">
        <AlertTriangle class="w-4 h-4 text-amber-600 flex-shrink-0" />
        <span>{{ errorText }}</span>
      </div>

      <!-- Pilot Selection -->
      <div class="flex flex-col gap-1.5">
        <label class="text-slate-600 text-xs font-bold uppercase tracking-wider block">Operador Asignado</label>
        <div class="relative">
          <select 
            v-model="operatorName"
            class="w-full bg-slate-50 border border-[#cbd5e1] py-3 px-4 font-sans text-sm text-slate-800 focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] outline-none appearance-none transition-all cursor-pointer"
          >
            <option value="">Seleccionar Operador...</option>
            <option value="Robert Andersson (Técnico)">Robert Andersson</option>
            <option value="Elena Rodriguez (Técnico)">Elena Rodriguez</option>
            <option value="Marcus Thorne (Técnico)">Marcus Thorne</option>
            <option value="M1gu3l Fu3nt3s (Técnico)">Miguel Fuentes</option>
          </select>
          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-[#0054A3]">
            <ChevronDown :size="18" />
          </div>
        </div>
      </div>

      <!-- Machine Type Grid -->
      <div class="flex flex-col gap-1.5">
        <label class="text-slate-600 text-xs font-bold uppercase tracking-wider block">Tipo de Maquinaria</label>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-1">
          <button
            v-for="m in machines"
            :key="m.id"
            type="button"
            @click="machineType = m.id"
            class="border p-4 text-center flex flex-col items-center gap-1.5 transition-all"
            :class="machineType === m.id ? 'border-[#0054A3] bg-[#0054A3]/5 ring-1 ring-[#0054A3]' : 'border-[#cbd5e1] hover:border-[#FFD200] bg-white'"
          >
            <component :is="m.icon" :size="22" :class="machineType === m.id ? 'text-[#0054A3]' : 'text-slate-500'" />
            <span class="font-display text-xs font-bold uppercase tracking-tight" :class="machineType === m.id ? 'text-[#0054A3]' : 'text-slate-800'">
              {{ m.label }}
            </span>
          </button>
        </div>
      </div>

      <!-- Registration Type Toggle -->
      <div class="flex flex-col gap-1.5">
        <label class="text-slate-600 text-xs font-bold uppercase tracking-wider block">Tipo de Registro</label>
        <div class="flex bg-slate-100 p-1 gap-1">
          <button
            type="button"
            @click="regType = 'incinal'"
            class="flex-1 py-2 text-center font-display text-xs font-bold uppercase tracking-wider transition-all"
            :class="regType === 'incinal' ? 'bg-white border border-[#cbd5e1] text-[#0054A3]' : 'text-slate-500 opacity-60 hover:opacity-100'"
          >
            Horómetro Inicial
          </button>
          <button
            type="button"
            @click="regType = 'final'"
            class="flex-1 py-2 text-center font-display text-xs font-bold uppercase tracking-wider transition-all"
            :class="regType === 'final' ? 'bg-white border border-[#cbd5e1] text-[#0054A3]' : 'text-slate-500 opacity-60 hover:opacity-100'"
          >
            Horómetro Final
          </button>
        </div>
      </div>

      <!-- Value Inputs Group -->
      <div class="flex flex-col gap-4 p-5 bg-slate-50 border-l-4 border-[#0054A3]">
        <div class="flex flex-col gap-1.5">
          <label class="text-slate-600 text-xs font-bold uppercase tracking-wider block">
            {{ regType === "incinal" ? "HORÓMETRO INICIAL (VALOR EN HORAS)" : "HORÓMETRO FINAL (VALOR EN HORAS)" }}
          </label>
          <div class="flex items-center">
            <input
              type="number"
              placeholder="00000.0"
              step="0.1"
              min="0"
              v-model="horometroValue"
              class="w-full bg-white border border-[#cbd5e1] py-2.5 px-4 font-display font-medium text-sm text-slate-800 outline-none focus:border-[#0054A3] focus:ring-1 focus:ring-[#FFD200]"
            />
            <div class="ml-3 bg-slate-100 px-4 py-2.5 border border-[#cbd5e1] font-display text-xs font-bold text-slate-600">
              HRS
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-slate-600 text-xs font-bold uppercase tracking-wider block">FOTO DEL REGISTRO HORÓMETRO</label>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <!-- File Upload Selector -->
            <label class="border-2 border-dashed border-[#cbd5e1] py-6 px-4 flex flex-col items-center justify-center gap-1.5 hover:border-[#0054A3] cursor-pointer transition-colors bg-white group select-none">
              <Camera :size="26" class="text-slate-500 group-hover:text-[#0054A3] transition-colors" />
              <span class="font-sans text-xs text-slate-500 group-hover:text-slate-800">
                Subir o capturar foto
              </span>
              <span class="font-display text-[9px] uppercase tracking-widest text-[#857462]">
                JPG, PNG HASTA 10MB
              </span>
              <input 
                type="file" 
                accept="image/*" 
                @change="handleFileUpload" 
                class="hidden" 
              />
            </label>

            <!-- Live Dial Simulator -->
            <button
              type="button"
              @click="handleSimulatePhoto"
              class="border border-[#0054A3] py-6 px-4 flex flex-col items-center justify-center gap-1.5 bg-[#0054A3]/5 hover:bg-[#0054A3]/10 text-[#0054A3] font-display text-xs font-bold uppercase tracking-wider transition-all"
            >
              <RefreshCw :size="26" class="animate-[spin_4s_linear_infinite]" />
              <span>Simular Lectura</span>
              <span class="text-[9px] font-sans font-medium text-slate-600 uppercase">Generar dial de prueba</span>
            </button>
          </div>

          <transition name="scale">
            <div v-if="selectedPhoto" class="mt-3 relative border border-[#cbd5e1] overflow-hidden">
              <img 
                :src="selectedPhoto" 
                alt="Vista previa" 
                class="w-full h-auto max-h-[180px] object-contain bg-slate-900" 
              />
              <button
                type="button"
                @click="selectedPhoto = null"
                class="absolute top-2 right-2 bg-red-600 text-white font-display text-[10px] font-black uppercase px-2.5 py-1 hover:bg-red-700 shadow"
              >
                Eliminar Foto
              </button>
            </div>
          </transition>
        </div>
      </div>

      <!-- Current Location (Map) -->
      <div class="flex flex-col gap-1.5">
        <div class="flex justify-between items-end">
          <label class="text-slate-600 text-xs font-bold uppercase tracking-wider block">Ubicación Actual del Proyecto</label>
          <span class="font-display text-[10px] text-[#0054A3] flex items-center gap-1 font-bold">
            <span class="w-2 h-2 rounded-full bg-[#0054A3] animate-pulse"></span>
            GPS ACTIVO
          </span>
        </div>

        <div 
          @click="handleRandomizeCoordinates"
          title="Haga clic para simular otra ubicación de obra"
          class="h-44 w-full border border-[#cbd5e1] bg-slate-50 relative overflow-hidden group cursor-pointer"
        >
          <!-- Minimal grayscale topographical industrial styling construct -->
          <div class="absolute inset-0 bg-[#f1f5f9] flex items-center justify-center pointer-events-none opacity-40">
            <div class="w-full h-full bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] [background-size:16px_16px]"></div>
          </div>
          
          <img
            src="https://lh3.googleusercontent.com/aida-public/AB6AXuAhZvPhcwVmZ7QlGYcRR63FPRjn0fznOJewT9Vgz8qtQPnSidN-BRoEUsIf_WG1_bYH-9HmsWEF3qHEdIBKEK-F6TXjoAmEvYlxhcuhwuJGSR-FOmqD9Lp9dtpPzYWluYjv4_04AH-Eymk7FKKZinrAUTww4IbXAckEASbtl8AYHSmZrA4s0AUKn9npDtqISyaruKxUXGTRp_FqPtYXdTV9a9Y7Uo6_e_gVEk3XzEjagI5YPiS13XVGr3AgsLFFc0KkviiacPo7T4un"
            alt="Plano cartográfico de obra"
            class="w-full h-full object-cover grayscale opacity-45 group-hover:opacity-60 transition-opacity duration-500"
            referrerpolicy="no-referrer"
          />

          <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="relative">
              <div class="absolute inset-0 bg-[#0054A3]/20 rounded-full animate-ping scale-150"></div>
              <div class="w-10 h-10 rounded-full bg-[#0054A3]/20 flex items-center justify-center border border-[#0054A3] relative">
                <MapPin :size="22" class="text-[#0054A3]" fill="#0054A3" />
              </div>
            </div>
          </div>

          <div class="absolute top-2 left-2 bg-black/65 text-white text-[9px] px-2 py-0.5 font-mono select-none">
            ZONA OBRA INTERACTIVA • CLIC PARA CAMBIAR
          </div>

          <div class="absolute bottom-2 right-2 bg-white/90 backdrop-blur-sm px-2.5 py-1 border border-[#cbd5e1] max-w-[90%] truncate text-right">
            <p class="font-display text-[9px] font-bold text-slate-800 tracking-tight">
              {{ latitude }}° N, {{ longitude }}° W
            </p>
            <p class="text-[8px] font-sans text-slate-600 font-medium truncate">
              {{ address }}
            </p>
          </div>
        </div>
      </div>

      <!-- Submit Operations -->
      <div class="mt-2">
        <button
          type="submit"
          :disabled="isSubmitting"
          class="w-full bg-[#0054A3] hover:bg-[#004586] disabled:bg-[#cbd5e1] py-4 text-white font-display text-lg font-black uppercase tracking-wider transition-all active:scale-[0.98] cursor-pointer"
        >
          {{ isSubmitting ? "TRANSMITIENDO TELEMETRÍA..." : "REGISTRAR OPERACIÓN" }}
        </button>
        <p class="text-center font-display text-[9px] text-slate-500 mt-3 font-semibold uppercase tracking-wider">
          Al realizar el envío, usted certifica la veracidad de la lectura del horómetro industrial.
        </p>
      </div>

    </form>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { 
  ChevronDown, Tractor, Construction, Wrench, CircleSlash, 
  Droplets, Truck, Camera, MapPin, CheckCircle, AlertTriangle, RefreshCw 
} from 'lucide-vue-next';

const props = defineProps<{
  currentUserFullName: string
}>();

const emit = defineEmits<{
  (e: 'addLog', log: any): void;
  (e: 'logout'): void;
}>();

const machineType = ref("excavadora");
const regType = ref("incinal");
const operatorName = ref(props.currentUserFullName);
const horometroValue = ref("");
const selectedPhoto = ref<string | null>(null);
const latitude = ref(14.6349);
const longitude = ref(-90.5069);
const address = ref("Km 42 Ruta Interamericana, Sector El Rodeo");
const isSubmitting = ref(false);
const submitSuccess = ref(false);
const errorText = ref("");

const machines = [
  { id: "tractor", label: "Tractor", icon: Tractor },
  { id: "excavadora", label: "Excavadora", icon: Construction },
  { id: "retro", label: "Retro Excavadora", icon: Wrench },
  { id: "rodo", label: "Rodo", icon: CircleSlash },
  { id: "pipa", label: "Pipa", icon: Droplets },
  { id: "volteo", label: "Camión Volteo", icon: Truck },
];

const handleSimulatePhoto = () => {
  const randomValue = horometroValue.value ? parseFloat(horometroValue.value).toFixed(1) : (Math.random() * 10000).toFixed(1);
  const canvas = document.createElement("canvas");
  canvas.width = 400;
  canvas.height = 200;
  const ctx = canvas.getContext("2d");
  if (ctx) {
    ctx.fillStyle = "#1e293b";
    ctx.fillRect(0, 0, 400, 200);
    
    ctx.strokeStyle = "#FFD200";
    ctx.lineWidth = 4;
    ctx.strokeRect(10, 10, 380, 180);
    
    ctx.strokeStyle = "rgba(255,255,255,0.4)";
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(200, 20); ctx.lineTo(200, 180);
    ctx.moveTo(50, 100); ctx.lineTo(350, 100);
    ctx.stroke();

    ctx.fillStyle = "#475569";
    ctx.fillRect(80, 70, 240, 60);

    ctx.fillStyle = "#FFD200";
    ctx.font = "bold 28px 'Courier New', monospace";
    ctx.fillText(`${randomValue.padStart(7, "0")} HRS`, 105, 110);

    ctx.fillStyle = "#ffffff";
    ctx.font = "bold 10px sans-serif";
    ctx.fillText("COOITZÁ - REGISTRO TELEMETRÍA", 120, 160);
    
    selectedPhoto.value = canvas.toDataURL("image/png");
  }
};

const handleFileUpload = (e: Event) => {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (uploadEvent) => {
      if (uploadEvent.target?.result) {
        selectedPhoto.value = uploadEvent.target.result as string;
      }
    };
    reader.readAsDataURL(file);
  }
};

const handleRandomizeCoordinates = () => {
  const rLat = (14.5 + Math.random() * 0.3).toFixed(4);
  const rLng = (-90.6 + Math.random() * 0.3).toFixed(4);
  latitude.value = parseFloat(rLat);
  longitude.value = parseFloat(rLng);
  
  const sites = [
    "Cantera Cooitzá - Chimaltenango, Guatemala",
    "Proyecto Vial CO-A4, San Carlos Alzatate",
    "Sector Norte de Construcción, Mixco",
    "Plantel Central Cooitzá - Ciudad de Guatemala"
  ];
  address.value = sites[Math.floor(Math.random() * sites.length)];
};

const handleFormSubmit = () => {
  errorText.value = "";

  if (!operatorName.value) {
    errorText.value = "Por favor, seleccione o ingrese un operador asignado.";
    return;
  }
  if (!horometroValue.value || parseFloat(horometroValue.value) <= 0) {
    errorText.value = "Por favor, ingrese un valor de horómetro válido mayor que 0.";
    return;
  }

  isSubmitting.value = true;

  setTimeout(() => {
    isSubmitting.value = false;
    emit('addLog', {
      operatorName: operatorName.value,
      machineType: machineType.value,
      regType: regType.value === "incinal" ? "inicial" : "final",
      horometroValue: parseFloat(horometroValue.value),
      photoUrl: selectedPhoto.value || undefined,
      location: {
        lat: latitude.value,
        lng: longitude.value,
        formattedAddress: address.value
      }
    });
    submitSuccess.value = true;
  }, 1200);
};

const resetForm = () => {
  submitSuccess.value = false;
  horometroValue.value = "";
  selectedPhoto.value = null;
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
