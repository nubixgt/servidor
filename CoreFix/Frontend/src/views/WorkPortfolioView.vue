<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { PORTFOLIO_ITEMS } from '../data/mockData';
import { 
  CheckCircle2, 
  Clock, 
  ShieldCheck, 
  Sparkles, 
  Split, 
  SlidersHorizontal,
  ArrowRight,
  Activity
} from 'lucide-vue-next';

const router = useRouter();

const selectedCategory = ref('all');
const activeItem = ref(PORTFOLIO_ITEMS[0]);
const sliderPosition = ref(50);
const compareMode = ref('slider'); // 'slider' or 'side-by-side'

const categories = [
  { id: 'all', label: 'Todos los Trabajos' },
  { id: 'Consolas', label: 'Consolas' },
  { id: 'PC & Laptops', label: 'PC & Laptops' },
  { id: 'Smartphones', label: 'Smartphones' },
  { id: 'Televisores', label: 'Televisores' },
];

const filteredItems = computed(() => {
  return selectedCategory.value === 'all'
    ? PORTFOLIO_ITEMS
    : PORTFOLIO_ITEMS.filter(item => item.category === selectedCategory.value);
});

const selectCategory = (catId) => {
  selectedCategory.value = catId;
  const first = catId === 'all' 
    ? PORTFOLIO_ITEMS[0] 
    : PORTFOLIO_ITEMS.find(i => i.category === catId) || PORTFOLIO_ITEMS[0];
  activeItem.value = first;
};

const onOpenQuoteWithDevice = (device) => {
  router.push({ path: '/contacto', query: { service: `Reparación para ${device}` } });
};

const selectItemAndScroll = (item) => {
  activeItem.value = item;
  window.scrollTo({ top: 400, behavior: 'smooth' });
};
</script>

<template>
  <div class="py-12 bg-slate-50/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
      
      <!-- Header -->
      <div class="text-center max-w-3xl mx-auto space-y-3">
        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-xs font-semibold">
          <Sparkles class="w-3.5 h-3.5 text-blue-600" />
          <span>Casos Reales de Éxito en Laboratorio</span>
        </div>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 tracking-tight">
          Trabajos y Reparaciones Realizadas
        </h1>
        <p class="text-slate-600 text-base sm:text-lg">
          Conoce el antes y el después de nuestros procedimientos técnicos de alta precisión con instrumental de grado industrial.
        </p>
      </div>

      <!-- Categories -->
      <div class="flex items-center justify-center gap-2 overflow-x-auto pb-2">
        <button
          v-for="cat in categories"
          :key="cat.id"
          @click="selectCategory(cat.id)"
          class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all cursor-pointer"
          :class="[
            selectedCategory === cat.id
              ? 'bg-blue-600 text-white shadow-xs'
              : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'
          ]"
        >
          {{ cat.label }}
        </button>
      </div>

      <!-- Featured Interactive Before & After Showcase -->
      <div class="bg-white rounded-3xl p-6 sm:p-8 lg:p-10 border border-slate-200 shadow-xl space-y-8">
        
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 pb-6 border-b border-slate-100">
          <div>
            <span class="text-xs font-bold text-blue-600 uppercase tracking-wider">
              {{ activeItem.category }} • {{ activeItem.device }}
            </span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">
              {{ activeItem.title }}
            </h2>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="compareMode = 'slider'"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 cursor-pointer transition-colors"
              :class="[
                compareMode === 'slider'
                  ? 'bg-blue-600 text-white'
                  : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
              ]"
            >
              <SlidersHorizontal class="w-3.5 h-3.5" />
              Deslizador Interactivo
            </button>
            <button
              @click="compareMode = 'side-by-side'"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 cursor-pointer transition-colors"
              :class="[
                compareMode === 'side-by-side'
                  ? 'bg-blue-600 text-white'
                  : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
              ]"
            >
              <Split class="w-3.5 h-3.5" />
              Lado a Lado
            </button>
          </div>
        </div>

        <!-- Before / After Visual Box -->
        <div v-if="compareMode === 'slider'" class="space-y-3">
          <div class="relative h-[320px] sm:h-[420px] lg:h-[480px] rounded-2xl overflow-hidden select-none bg-slate-900 border border-slate-200 shadow-inner">
            <!-- AFTER IMAGE (Background) -->
            <img
              :src="activeItem.afterImage"
              :alt="activeItem.afterLabel || 'Después de la reparación'"
              class="absolute inset-0 w-full h-full object-cover"
              referrerpolicy="no-referrer"
            />
            <div class="absolute top-4 right-4 bg-emerald-600/90 text-white text-xs font-bold px-3 py-1 rounded-full backdrop-blur-xs shadow-md z-10">
              {{ activeItem.afterLabel || "DESPUÉS (Reparado)" }}
            </div>

            <!-- BEFORE IMAGE (Clipped with slider width) -->
            <div
              class="absolute inset-0 overflow-hidden"
              :style="{ width: `${sliderPosition}%` }"
            >
              <img
                :src="activeItem.beforeImage"
                :alt="activeItem.beforeLabel || 'Antes de la reparación'"
                class="absolute inset-0 w-full h-full object-cover max-w-none"
                style="width: 100%; height: 100%; min-width: 100%;"
                referrerpolicy="no-referrer"
              />
              <div class="absolute top-4 left-4 bg-rose-600/90 text-white text-xs font-bold px-3 py-1 rounded-full backdrop-blur-xs shadow-md z-10">
                {{ activeItem.beforeLabel || "ANTES (Dañado)" }}
              </div>
            </div>

            <!-- Vertical Divider line -->
            <div
              class="absolute top-0 bottom-0 w-1 bg-white shadow-2xl z-20 pointer-events-none"
              :style="{ left: `calc(${sliderPosition}% - 2px)` }"
            >
              <div class="absolute top-1/2 -translate-y-1/2 -left-3.5 w-8 h-8 rounded-full bg-white text-slate-800 shadow-xl flex items-center justify-center border-2 border-blue-600 font-bold text-xs">
                ↔
              </div>
            </div>

            <!-- Hidden range input for natural drag control -->
            <input
              type="range"
              min="0"
              max="100"
              v-model="sliderPosition"
              class="absolute inset-0 opacity-0 cursor-ew-resize w-full h-full z-30"
              aria-label="Deslizar para comparar antes y después"
            />
          </div>

          <p class="text-center text-xs text-slate-500 italic">
            ← Desliza el cursor o pulsa para comparar el resultado antes y después de la intervención →
          </p>
        </div>
        
        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <div class="relative h-[260px] sm:h-[340px] rounded-2xl overflow-hidden border border-slate-200">
              <img
                :src="activeItem.beforeImage"
                :alt="activeItem.beforeLabel || 'Antes'"
                class="w-full h-full object-cover"
                referrerpolicy="no-referrer"
              />
              <div class="absolute top-3 left-3 bg-rose-600 text-white text-xs font-bold px-3 py-1 rounded-full">
                ANTES
              </div>
            </div>
            <p class="text-xs font-semibold text-rose-700">{{ activeItem.beforeLabel }}</p>
          </div>

          <div class="space-y-2">
            <div class="relative h-[260px] sm:h-[340px] rounded-2xl overflow-hidden border border-slate-200">
              <img
                :src="activeItem.afterImage"
                :alt="activeItem.afterLabel || 'Después'"
                class="w-full h-full object-cover"
                referrerpolicy="no-referrer"
              />
              <div class="absolute top-3 right-3 bg-emerald-600 text-white text-xs font-bold px-3 py-1 rounded-full">
                DESPUÉS
              </div>
            </div>
            <p class="text-xs font-semibold text-emerald-700">{{ activeItem.afterLabel }}</p>
          </div>
        </div>

        <!-- Technical Case Study Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 pt-4">
          
          <div class="lg:col-span-8 space-y-5">
            <div>
              <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wider text-rose-700">
                Diagnóstico Inicial
              </h4>
              <p class="mt-1 text-sm text-slate-700 leading-relaxed">
                {{ activeItem.problem }}
              </p>
            </div>

            <div>
              <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wider text-blue-700">
                Procedimiento Técnico Aplicado
              </h4>
              <p class="mt-1 text-sm text-slate-700 leading-relaxed">
                {{ activeItem.solution }}
              </p>
            </div>

            <div class="space-y-2">
              <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                Detalles y Controles Realizados
              </h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div v-for="(detail, idx) in activeItem.details" :key="idx" class="flex items-start gap-2 text-xs text-slate-600">
                  <CheckCircle2 class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" />
                  <span>{{ detail }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Meta Box -->
          <div class="lg:col-span-4 bg-slate-50 rounded-2xl p-5 border border-slate-200 space-y-4 flex flex-col justify-between">
            <div class="space-y-3">
              <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                Ficha Técnica de Orden
              </h4>

              <div class="space-y-2.5 text-xs">
                <div class="flex justify-between items-center py-1 border-b border-slate-200/70">
                  <span class="text-slate-500">Tiempo de Reparación:</span>
                  <span class="font-bold text-slate-900 flex items-center gap-1">
                    <Clock class="w-3.5 h-3.5 text-blue-600" />
                    {{ activeItem.turnaround }}
                  </span>
                </div>

                <div class="flex justify-between items-center py-1 border-b border-slate-200/70">
                  <span class="text-slate-500">Garantía Otorgada:</span>
                  <span class="font-bold text-emerald-700 flex items-center gap-1">
                    <ShieldCheck class="w-3.5 h-3.5 text-emerald-600" />
                    {{ activeItem.warranty }}
                  </span>
                </div>

                <div class="flex justify-between items-center py-1 border-b border-slate-200/70">
                  <span class="text-slate-500">Control de Calidad:</span>
                  <span class="font-bold text-blue-700 flex items-center gap-1">
                    <Activity class="w-3.5 h-3.5 text-blue-600" />
                    Aprobado 100%
                  </span>
                </div>
              </div>
            </div>

            <div class="pt-2">
              <button
                @click="onOpenQuoteWithDevice(activeItem.device)"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl text-xs shadow-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer"
              >
                <span>Tengo una falla similar en mi equipo</span>
                <ArrowRight class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

        </div>

      </div>

      <!-- Gallery of other case studies -->
      <div class="space-y-4">
        <h3 class="text-xl font-bold text-slate-900">
          Explora otros casos resueltos en nuestro laboratorio
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div
            v-for="item in filteredItems"
            :key="item.id"
            @click="selectItemAndScroll(item)"
            class="bg-white rounded-2xl p-4 border transition-all cursor-pointer group flex flex-col justify-between"
            :class="[
              activeItem.id === item.id
                ? 'border-blue-600 ring-2 ring-blue-600/20 shadow-md'
                : 'border-slate-200 hover:border-blue-300 hover:shadow-md'
            ]"
          >
            <div class="space-y-3">
              <div class="relative h-36 rounded-xl overflow-hidden bg-slate-100">
                <img
                  :src="item.afterImage"
                  :alt="item.title"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                  referrerpolicy="no-referrer"
                />
                <span class="absolute bottom-2 left-2 px-2 py-0.5 rounded-md bg-slate-900/80 text-white text-[10px] font-bold">
                  {{ item.category }}
                </span>
              </div>

              <h4 class="text-sm font-bold text-slate-900 group-hover:text-blue-600 transition-colors line-clamp-2">
                {{ item.title }}
              </h4>

              <p class="text-xs text-slate-500 line-clamp-2">
                {{ item.problem }}
              </p>
            </div>

            <div class="pt-3 mt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-blue-600">
              <span>Ver caso completo</span>
              <ArrowRight class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" />
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
