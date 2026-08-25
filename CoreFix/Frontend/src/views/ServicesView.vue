<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { 
  Laptop, 
  Smartphone, 
  Tv, 
  Gamepad2, 
  Terminal, 
  LayoutGrid, 
  Wrench, 
  ArrowRight,
  Calculator,
  ShieldCheck,
  CheckCircle,
  HelpCircle,
  ChevronDown,
  ChevronUp,
  Sparkles
} from 'lucide-vue-next';
import { SERVICES_DATA, FAQS } from '../data/mockData';
import ServiceDetailModal from '../components/modals/ServiceDetailModal.vue';

const router = useRouter();

const selectedCategory = ref('all');
const openFaqIndex = ref(0);

// Modal state
const isServiceModalOpen = ref(false);
const selectedService = ref(null);

// Interactive Quick Calculator state
const calcDevice = ref('laptop');
const calcIssue = ref('screen');
const calcUrgency = ref('normal');

const categories = [
  { id: 'all', label: 'Todos los Servicios' },
  { id: 'hardware', label: 'PC & Notebooks' },
  { id: 'mobile', label: 'Smartphones & Tablets' },
  { id: 'displays', label: 'Televisores & Pantallas' },
  { id: 'gaming', label: 'Consolas & Mandos' },
  { id: 'software', label: 'Software & SO' },
  { id: 'maintenance', label: 'Mantenimiento Térmico' },
];

const filteredServices = computed(() => {
  return selectedCategory.value === 'all'
    ? SERVICES_DATA
    : SERVICES_DATA.filter(s => s.category === selectedCategory.value);
});

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

const calculateEstimate = computed(() => {
  let base = 25000;
  if (calcDevice.value === 'smartphone') {
    base = calcIssue.value === 'screen' ? 28000 : calcIssue.value === 'battery' ? 18000 : 32000;
  } else if (calcDevice.value === 'laptop') {
    base = calcIssue.value === 'thermal' ? 22000 : calcIssue.value === 'screen' ? 45000 : 35000;
  } else if (calcDevice.value === 'console') {
    base = calcIssue.value === 'hdmi' ? 32000 : calcIssue.value === 'thermal' ? 28000 : 25000;
  } else if (calcDevice.value === 'tv') {
    base = calcIssue.value === 'backlight' ? 38000 : 45000;
  }

  if (calcUrgency.value === 'express') {
    base += 8000;
  }

  return `$${base.toLocaleString('es-AR')}`;
});

const onSelectService = (service) => {
  selectedService.value = service;
  isServiceModalOpen.value = true;
};

const onOpenQuoteWithService = (serviceTitle) => {
  router.push({ path: '/contacto', query: { service: serviceTitle } });
};
</script>

<template>
  <div class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">
      
      <!-- Header -->
      <div class="text-center max-w-3xl mx-auto space-y-4">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-xs font-semibold">
          <Sparkles class="w-3.5 h-3.5 text-blue-600" />
          <span>Soluciones Especializadas con Instrumental de Precisión</span>
        </div>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 tracking-tight">
          Catálogo Integral de Reparación
        </h1>
        <p class="text-slate-600 text-base sm:text-lg">
          Diagnóstico con osciloscopio, microsoldadura BGA, repuestos originales y protocolos de testeo industrial para que tu equipo rinda al máximo.
        </p>
      </div>

      <!-- Category Filters -->
      <div class="flex items-center justify-start sm:justify-center gap-2 overflow-x-auto pb-2 scrollbar-none">
        <button
          v-for="cat in categories"
          :key="cat.id"
          @click="selectedCategory = cat.id"
          class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all whitespace-nowrap cursor-pointer"
          :class="[
            selectedCategory === cat.id
              ? 'bg-blue-600 text-white shadow-xs'
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          ]"
        >
          {{ cat.label }}
        </button>
      </div>

      <!-- Services Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="service in filteredServices"
          :key="service.id"
          class="bg-white rounded-2xl p-6 sm:p-7 border border-slate-200 shadow-xs hover:shadow-lg hover:border-blue-300 transition-all duration-200 flex flex-col justify-between group"
        >
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center group-hover:scale-105 transition-transform">
                <component :is="getIconComponent(service.iconName)" class="w-6 h-6 text-blue-600" />
              </div>
              <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                {{ service.warranty }}
              </span>
            </div>

            <div>
              <h3 class="text-xl font-bold text-slate-900 group-hover:text-blue-600 transition-colors">
                {{ service.title }}
              </h3>
              <p class="text-xs text-blue-600 font-medium mt-0.5">
                Tiempo estimado: {{ service.estimatedTime }}
              </p>
            </div>

            <p class="text-slate-600 text-sm leading-relaxed">
              {{ service.shortDescription }}
            </p>

            <!-- Features list -->
            <div class="space-y-1.5 pt-2">
              <div v-for="(feat, idx) in service.features.slice(0, 3)" :key="idx" class="flex items-start gap-2 text-xs text-slate-600">
                <CheckCircle class="w-3.5 h-3.5 text-blue-600 shrink-0 mt-0.5" />
                <span class="line-clamp-1">{{ feat }}</span>
              </div>
            </div>
          </div>

          <div class="pt-6 mt-6 border-t border-slate-100 flex items-center justify-between">
            <div>
              <span class="text-[11px] text-slate-400 block">Tarifa base desde</span>
              <span class="text-base font-bold text-slate-900">{{ service.startingPrice }}</span>
            </div>
            
            <div class="flex gap-2">
              <button
                @click="onSelectService(service)"
                class="text-xs font-semibold text-slate-700 hover:text-blue-600 px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer"
              >
                Detalles
              </button>
              <button
                @click="onOpenQuoteWithService(service.title)"
                class="text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-2 rounded-lg shadow-xs cursor-pointer"
              >
                Cotizar
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Interactive Estimator / Calculator -->
      <div class="bg-slate-900 rounded-3xl p-6 sm:p-10 text-white shadow-xl">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
          
          <div class="lg:col-span-6 space-y-4">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-500/20 text-blue-300 rounded-full text-xs font-semibold border border-blue-400/30">
              <Calculator class="w-3.5 h-3.5 text-blue-400" />
              <span>Simulador de Presupuesto Estimado</span>
            </div>
            <h3 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
              Calcula el costo aproximado de tu reparación
            </h3>
            <p class="text-slate-300 text-sm leading-relaxed">
              Selecciona tu tipo de dispositivo y falla principal para obtener una referencia de costo con repuestos y mano de obra garantizada.
            </p>

            <div class="space-y-4 pt-2">
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">1. Dispositivo</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                  <button
                    v-for="item in [
                      { id: 'laptop', label: 'Notebook / PC' },
                      { id: 'smartphone', label: 'Smartphone' },
                      { id: 'console', label: 'Consola' },
                      { id: 'tv', label: 'Smart TV' }
                    ]"
                    :key="item.id"
                    type="button"
                    @click="calcDevice = item.id"
                    class="py-2 px-3 rounded-xl text-xs font-medium border transition-colors cursor-pointer text-center"
                    :class="[
                      calcDevice === item.id
                        ? 'bg-blue-600 border-blue-500 text-white font-bold'
                        : 'bg-slate-800/80 border-slate-700 text-slate-300 hover:bg-slate-800'
                    ]"
                  >
                    {{ item.label }}
                  </button>
                </div>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">2. Tipo de Falla / Trabajo</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                  <button
                    v-for="item in [
                      { id: 'screen', label: 'Pantalla / Vidrio' },
                      { id: 'thermal', label: 'Mantenimiento Térmico' },
                      { id: 'battery', label: 'Batería / Carga' },
                      { id: 'hdmi', label: 'Puerto / Conector' },
                      { id: 'board', label: 'Placa / No Enciende' }
                    ]"
                    :key="item.id"
                    type="button"
                    @click="calcIssue = item.id"
                    class="py-2 px-3 rounded-xl text-xs font-medium border transition-colors cursor-pointer text-center"
                    :class="[
                      calcIssue === item.id
                        ? 'bg-blue-600 border-blue-500 text-white font-bold'
                        : 'bg-slate-800/80 border-slate-700 text-slate-300 hover:bg-slate-800'
                    ]"
                  >
                    {{ item.label }}
                  </button>
                </div>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">3. Modalidad de Atención</label>
                <div class="grid grid-cols-2 gap-2">
                  <button
                    type="button"
                    @click="calcUrgency = 'normal'"
                    class="py-2 px-3 rounded-xl text-xs font-medium border transition-colors cursor-pointer"
                    :class="[
                      calcUrgency === 'normal'
                        ? 'bg-blue-600 border-blue-500 text-white font-bold'
                        : 'bg-slate-800/80 border-slate-700 text-slate-300 hover:bg-slate-800'
                    ]"
                  >
                    Estándar (24 a 48 hs)
                  </button>
                  <button
                    type="button"
                    @click="calcUrgency = 'express'"
                    class="py-2 px-3 rounded-xl text-xs font-medium border transition-colors cursor-pointer"
                    :class="[
                      calcUrgency === 'express'
                        ? 'bg-blue-600 border-blue-500 text-white font-bold'
                        : 'bg-slate-800/80 border-slate-700 text-slate-300 hover:bg-slate-800'
                    ]"
                  >
                    ⚡ Express en el Día (+ $8.000)
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Calculated Result Box -->
          <div class="lg:col-span-6 bg-slate-800/90 rounded-2xl p-6 sm:p-8 border border-slate-700 text-center space-y-4">
            <span class="text-xs font-semibold text-blue-400 uppercase tracking-widest block">
              Presupuesto Estimado
            </span>
            
            <div class="text-4xl sm:text-5xl font-black text-white tracking-tight">
              {{ calculateEstimate }}
            </div>

            <p class="text-xs text-slate-400 max-w-sm mx-auto">
              Incluye diagnóstico previo, repuestos testeados, mano de obra especializada y garantía escrita de 90 a 180 días.
            </p>

            <div class="pt-2">
              <button
                @click="onOpenQuoteWithService(`Cotización Calculador: ${calcDevice} - ${calcIssue}`)"
                class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3.5 px-6 rounded-xl text-sm transition-all duration-200 shadow-md cursor-pointer flex items-center justify-center gap-2"
              >
                <span>Reservar Turno con este Presupuesto</span>
                <ArrowRight class="w-4 h-4" />
              </button>
            </div>
          </div>

        </div>
      </div>

      <!-- FAQs Section -->
      <div class="max-w-3xl mx-auto space-y-6">
        <div class="text-center space-y-2">
          <h3 class="text-2xl sm:text-3xl font-bold text-slate-900">Preguntas Frecuentes</h3>
          <p class="text-slate-600 text-sm">Respuestas claras sobre nuestro proceso de trabajo y garantías</p>
        </div>

        <div class="space-y-3">
          <div
            v-for="(faq, idx) in FAQS"
            :key="idx"
            class="border border-slate-200 rounded-xl overflow-hidden bg-white shadow-2xs"
          >
            <button
              @click="openFaqIndex = openFaqIndex === idx ? null : idx"
              class="w-full p-4 text-left font-semibold text-sm text-slate-900 flex justify-between items-center gap-4 hover:bg-slate-50 transition-colors cursor-pointer"
            >
              <span>{{ faq.question }}</span>
              <ChevronUp v-if="openFaqIndex === idx" class="w-4 h-4 text-blue-600 shrink-0" />
              <ChevronDown v-else class="w-4 h-4 text-slate-400 shrink-0" />
            </button>
            <div
              v-if="openFaqIndex === idx"
              class="px-4 pb-4 pt-1 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 bg-slate-50/50"
            >
              {{ faq.answer }}
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Service Detail Modal -->
    <ServiceDetailModal
      :is-open="isServiceModalOpen"
      :service="selectedService"
      @close="isServiceModalOpen = false"
      @open-quote="onOpenQuoteWithService"
    />
  </div>
</template>
