<script setup>
import { ref, watch } from 'vue';
import { X, Send, Zap, Shield } from 'lucide-vue-next';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  initialServiceTitle: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['close', 'success']);

const device = ref(props.initialServiceTitle || 'PC & Laptops');
const issue = ref('');
const name = ref('');
const phone = ref('');
const isSubmitting = ref(false);

watch(() => props.initialServiceTitle, (newVal) => {
  if (newVal) {
    device.value = newVal;
  }
});

const handleSubmit = () => {
  isSubmitting.value = true;

  setTimeout(() => {
    const code = `TF-${Math.floor(1000 + Math.random() * 9000)}`;
    isSubmitting.value = false;
    emit('success', code);
    emit('close');
  }, 600);
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200">
    <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200">
      
      <!-- Header -->
      <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-5 text-white flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
            <Zap class="w-4 h-4 text-white" />
          </div>
          <div>
            <h3 class="text-base font-bold">Solicitar Presupuesto Rápido</h3>
            <p class="text-xs text-blue-100">Respuesta en menos de 30 minutos</p>
          </div>
        </div>
        <button
          @click="emit('close')"
          class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-colors cursor-pointer"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Dispositivo o Servicio
          </label>
          <select
            v-model="device"
            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600"
          >
            <option value="PC & Laptops">PC & Laptops (Notebook / Torre)</option>
            <option value="Smartphones">Smartphones (iPhone / Samsung / Otros)</option>
            <option value="Televisores">Televisores & Smart TVs</option>
            <option value="Consolas">Consolas (PS5 / PS4 / Switch / Xbox)</option>
            <option value="Sistemas OS">Sistemas OS (Formateo / Optimización)</option>
            <option value="Software & Office">Software & Office (Virus / Licencias)</option>
            <option value="Mantenimiento Preventivo">Mantenimiento Preventivo Térmico</option>
            <option v-if="![ 'PC & Laptops', 'Smartphones', 'Televisores', 'Consolas', 'Sistemas OS', 'Software & Office', 'Mantenimiento Preventivo' ].includes(device)" :value="device">{{ device }}</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">
            Falla o síntoma que presenta
          </label>
          <textarea
            rows="2"
            required
            placeholder="Ej: Pantalla con rayas verdes, no prende tras tormenta, etc."
            v-model="issue"
            class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600"
          ></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">
              Tu Nombre
            </label>
            <input
              type="text"
              required
              placeholder="Nombre y apellido"
              v-model="name"
              class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">
              Teléfono / WhatsApp
            </label>
            <input
              type="tel"
              required
              placeholder="+1 234 567 8900"
              v-model="phone"
              class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600"
            />
          </div>
        </div>

        <div class="p-3 bg-blue-50/70 border border-blue-100 rounded-xl text-[11px] text-blue-900 flex items-center gap-2">
          <Shield class="w-4 h-4 text-blue-600 shrink-0" />
          <span>Presupuesto sin cargo ni compromiso de reparación.</span>
        </div>

        <div class="pt-2">
          <button
            type="submit"
            :disabled="isSubmitting"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl text-xs sm:text-sm transition-all shadow-md cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50"
          >
            <span v-if="isSubmitting">Procesando...</span>
            <template v-else>
              <span>Enviar y Recibir Cotización</span>
              <Send class="w-4 h-4" />
            </template>
          </button>
        </div>
      </form>

    </div>
  </div>
</template>
