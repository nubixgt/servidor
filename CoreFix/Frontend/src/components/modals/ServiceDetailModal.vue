<script setup>
import { 
  X, 
  CheckCircle, 
  Clock, 
  ShieldCheck, 
  DollarSign, 
  AlertTriangle, 
  ArrowRight,
  Wrench,
  Laptop,
  Smartphone,
  Tv,
  Gamepad2,
  Terminal,
  LayoutGrid
} from 'lucide-vue-next';

const props = defineProps({
  service: {
    type: Object,
    default: null
  },
  isOpen: {
    type: Boolean,
    required: true
  }
});

const emit = defineEmits(['close', 'open-quote']);

const getIconComponent = (iconName) => {
  switch (iconName) {
    case 'laptop': return Laptop;
    case 'smartphone': return Smartphone;
    case 'tv': return Tv;
    case 'gamepad': return Gamepad2;
    case 'terminal': return Terminal;
    case 'grid': return LayoutGrid;
    default: return Wrench;
  }
};
</script>

<template>
  <div v-if="isOpen && service" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-200 flex flex-col">
      
      <!-- Header -->
      <div class="p-6 border-b border-slate-100 flex items-start justify-between bg-slate-50/80">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center">
            <component :is="getIconComponent(service.iconName)" class="w-6 h-6 text-blue-600" />
          </div>
          <div>
            <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider">
              Servicio Especializado
            </span>
            <h3 class="text-xl font-bold text-slate-900">{{ service.title }}</h3>
          </div>
        </div>
        <button
          @click="emit('close')"
          class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-200/60 transition-colors cursor-pointer"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 space-y-6">
        <!-- Detailed description -->
        <div>
          <h4 class="text-sm font-bold text-slate-900 mb-2">Descripción del Servicio</h4>
          <p class="text-slate-600 text-sm leading-relaxed">
            {{ service.fullDescription }}
          </p>
        </div>

        <!-- Quick Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div class="p-3.5 bg-blue-50/50 rounded-xl border border-blue-100">
            <span class="text-[11px] text-slate-500 font-medium flex items-center gap-1">
              <Clock class="w-3.5 h-3.5 text-blue-600" />
              Tiempo Estimado
            </span>
            <p class="text-xs font-bold text-slate-900 mt-1">{{ service.estimatedTime }}</p>
          </div>

          <div class="p-3.5 bg-emerald-50/50 rounded-xl border border-emerald-100">
            <span class="text-[11px] text-slate-500 font-medium flex items-center gap-1">
              <ShieldCheck class="w-3.5 h-3.5 text-emerald-600" />
              Garantía
            </span>
            <p class="text-xs font-bold text-slate-900 mt-1">{{ service.warranty }}</p>
          </div>

          <div class="p-3.5 bg-purple-50/50 rounded-xl border border-purple-100">
            <span class="text-[11px] text-slate-500 font-medium flex items-center gap-1">
              <DollarSign class="w-3.5 h-3.5 text-purple-600" />
              Desde
            </span>
            <p class="text-xs font-bold text-slate-900 mt-1">{{ service.startingPrice }}</p>
          </div>
        </div>

        <!-- Included Features -->
        <div class="space-y-2.5">
          <h4 class="text-sm font-bold text-slate-900">¿Qué incluye este servicio?</h4>
          <div class="space-y-2">
            <div v-for="(feature, idx) in service.features" :key="idx" class="flex items-start gap-2.5 text-xs text-slate-700">
              <CheckCircle class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" />
              <span>{{ feature }}</span>
            </div>
          </div>
        </div>

        <!-- Common issues solved -->
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-2">
          <h4 class="text-xs font-bold text-slate-900 flex items-center gap-1.5 uppercase tracking-wide">
            <AlertTriangle class="w-3.5 h-3.5 text-amber-500" />
            Problemas más comunes que resolvemos
          </h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-600">
            <div v-for="(issue, idx) in service.commonIssues" :key="idx" class="flex items-center gap-2">
              <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0" />
              <span>{{ issue }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="p-4 sm:p-6 border-t border-slate-100 bg-slate-50/80 flex items-center justify-between">
        <button
          @click="emit('close')"
          class="px-4 py-2 text-xs font-medium text-slate-600 hover:text-slate-900 cursor-pointer"
        >
          Volver
        </button>
        <button
          @click="emit('close'); emit('open-quote', service.title);"
          class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-5 py-2.5 rounded-xl shadow-xs hover:shadow-md transition-all flex items-center gap-1.5 cursor-pointer"
        >
          <span>Pedir presupuesto para {{ service.title }}</span>
          <ArrowRight class="w-3.5 h-3.5" />
        </button>
      </div>

    </div>
  </div>
</template>
