<script setup>
import { computed } from 'vue';
import { 
  Laptop, 
  Smartphone, 
  Tv, 
  Gamepad2, 
  Terminal, 
  LayoutGrid, 
  Wrench,
  ArrowRight
} from 'lucide-vue-next';
import { SERVICES_DATA } from '../data/mockData';

const emit = defineEmits(['select-service', 'open-quote-with-service']);

const getIconComponent = (iconName) => {
  switch (iconName) {
    case 'laptop': return Laptop;
    case 'smartphone': return Smartphone;
    case 'tv': return Tv;
    case 'gamepad': return Gamepad2;
    case 'terminal': return Terminal;
    case 'grid': return LayoutGrid;
    case 'wrench': return Wrench;
    default: return Wrench;
  }
};

const topServices = computed(() => SERVICES_DATA.slice(0, 6));
const wideService = computed(() => SERVICES_DATA[6]);
</script>

<template>
  <section id="servicios-section" class="py-16 sm:py-24 bg-slate-50/60 border-b border-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <!-- Section Header matching the reference screenshot -->
      <div class="text-center max-w-3xl mx-auto mb-14">
        <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 tracking-tight">
          Nuestros Servicios
        </h2>
        <p class="mt-3.5 text-base sm:text-lg text-slate-600">
          Soluciones integrales de hardware y software para mantener tu ecosistema digital funcionando sin interrupciones.
        </p>
      </div>

      <!-- Services Grid matching exact cards layout -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Top 6 Standard Cards -->
        <div
          v-for="service in topServices"
          :key="service.id"
          :id="`service-card-${service.id}`"
          class="bg-white rounded-2xl p-7 border border-slate-200 shadow-xs hover:shadow-md hover:border-blue-300 transition-all duration-200 flex flex-col justify-between group"
        >
          <div class="space-y-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center group-hover:scale-105 transition-transform">
              <component :is="getIconComponent(service.iconName)" class="w-7 h-7 text-blue-600" :stroke-width="2" />
            </div>

            <h3 class="text-xl font-bold text-slate-900 group-hover:text-blue-600 transition-colors">
              {{ service.title }}
            </h3>

            <p class="text-slate-600 text-sm leading-relaxed">
              {{ service.shortDescription }}
            </p>
          </div>

          <div class="pt-6 mt-6 border-t border-slate-100 flex items-center justify-between">
            <button
              @click="$emit('select-service', service)"
              class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1 cursor-pointer"
            >
              <span>Ver detalles</span>
              <ArrowRight class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" />
            </button>
            <button
              @click="$emit('open-quote-with-service', service.title)"
              class="text-xs font-medium text-slate-500 hover:text-slate-900 hover:underline cursor-pointer"
            >
              Cotizar
            </button>
          </div>
        </div>

        <!-- Bottom row: Mantenimiento Preventivo wide card spanning 2 columns on lg -->
        <div
          v-if="wideService"
          :id="`service-card-${wideService.id}`"
          class="sm:col-span-2 lg:col-span-2 bg-white rounded-2xl p-7 border border-slate-200 shadow-xs hover:shadow-md hover:border-blue-300 transition-all duration-200 flex flex-col justify-between group"
        >
          <div class="space-y-4">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center group-hover:scale-105 transition-transform shrink-0">
                <component :is="getIconComponent(wideService.iconName)" class="w-7 h-7 text-blue-600" :stroke-width="2" />
              </div>
              <div>
                <h3 class="text-xl font-bold text-slate-900 group-hover:text-blue-600 transition-colors">
                  {{ wideService.title }}
                </h3>
                <span class="text-xs text-blue-600 font-medium">Recomendado cada 6 a 12 meses</span>
              </div>
            </div>

            <p class="text-slate-600 text-sm leading-relaxed">
              {{ wideService.shortDescription }}
            </p>
          </div>

          <div class="pt-6 mt-6 border-t border-slate-100 flex items-center justify-between">
            <button
              @click="$emit('select-service', wideService)"
              class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1 cursor-pointer"
            >
              <span>Ver detalles y protocolo térmico</span>
              <ArrowRight class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" />
            </button>
            <button
              @click="$emit('open-quote-with-service', wideService.title)"
              class="bg-blue-50 text-blue-700 hover:bg-blue-100 font-medium px-3.5 py-1.5 rounded-lg text-xs transition-colors cursor-pointer"
            >
              Solicitar Mantenimiento
            </button>
          </div>
        </div>

        <!-- Complementary Quick Diagnostic card to fill grid balance gracefully -->
        <div class="sm:col-span-2 lg:col-span-2 bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-7 text-white shadow-md flex flex-col justify-between">
          <div class="space-y-3">
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white/20 rounded-full text-xs font-medium text-blue-50">
              <span>¿No sabes exactamente qué tiene tu equipo?</span>
            </div>
            <h3 class="text-xl font-bold text-white">
              Diagnóstico y Presupuesto Sin Cargo
            </h3>
            <p class="text-blue-100 text-sm leading-relaxed">
              Trae tu dispositivo a nuestro laboratorio o solicita retiro. Desarmamos, testeamos voltajes con instrumental de precisión y te damos un presupuesto exacto antes de realizar cualquier trabajo.
            </p>
          </div>
          <div class="pt-5 mt-4 border-t border-white/20 flex items-center justify-between">
            <span class="text-xs font-medium text-blue-100">Sin compromiso de reparación</span>
            <button
              @click="$emit('open-quote-with-service', 'Diagnóstico General')"
              class="bg-white text-blue-700 hover:bg-blue-50 font-semibold px-4 py-2 rounded-xl text-xs shadow-sm transition-all cursor-pointer"
            >
              Traer a Diagnóstico
            </button>
          </div>
        </div>

      </div>

    </div>
  </section>
</template>
