<template>
  <div class="space-y-8 animate-fade-in pb-10">
    
    <!-- Back glow background -->
    <div class="absolute top-[30%] left-[5%] w-[350px] h-[350px] bg-pink-100/30 blur-[130px] rounded-full pointer-events-none -z-10"></div>

    <!-- Hero Header Section -->
    <section class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
      <div>
        <h2 class="text-3xl font-extrabold text-[#3455b9] mb-1">Catálogo de Productos</h2>
        <p class="text-[#5c4a3c] font-bold text-sm">Información y descripción técnica de nuestros productos.</p>
      </div>
      <div class="flex items-center gap-1.5 bg-white/60 border border-white/50 px-4.5 py-2.5 rounded-2xl shadow-3xs text-xs font-bold text-[#2e2620]">
        <BookOpenIcon class="w-4.5 h-4.5 text-[#3455b9]" />
        <span>{{ products.length }} Productos Disponibles</span>
      </div>
    </section>

    <!-- Grid of product cards -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div
        v-for="prod in products"
        :key="prod.name"
        class="glass-panel border-2 border-white/60 bg-white/40 backdrop-blur-xl rounded-3xl overflow-hidden flex flex-col hover:scale-[1.01] hover:shadow-xl transition-all duration-300 relative group"
      >
        <!-- Product Image -->
        <div 
          @click="selectedImage = prod.image"
          class="h-64 bg-white/80 p-6 flex items-center justify-center border-b border-gray-100/50 cursor-pointer group/img"
        >
          <img :src="prod.image" :alt="prod.name" class="max-h-full object-contain mix-blend-multiply transition-transform duration-300 group-hover/img:scale-110" />
        </div>

        <!-- Product Details -->
        <div class="p-6 md:p-8 flex-1 flex flex-col">
          <div class="mb-4">
            <h3 class="text-xl font-black text-[#3455b9] mb-2 tracking-tight">{{ prod.name }}</h3>
            <p class="text-sm text-[#5c4a3c] font-bold leading-relaxed">{{ prod.subtitle }}</p>
          </div>

          <div class="border-t border-gray-200/50 pt-4">
            <ul class="space-y-3">
              <li v-for="(bullet, index) in prod.bullets" :key="index" class="flex items-start gap-2.5 text-xs text-gray-700 font-medium leading-relaxed">
                <CheckCircleIcon class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                <span>{{ bullet }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- Image Zoom Modal -->
    <Teleport to="body">
      <div v-if="selectedImage" class="fixed inset-0 z-50 flex items-center justify-center bg-[#1e293b]/80 backdrop-blur-md p-4 lg:p-10" @click.self="selectedImage = null">
        <div class="relative max-w-4xl w-full max-h-[90vh] bg-white rounded-3xl p-4 shadow-2xl flex items-center justify-center animate-fade-in">
          <button @click="selectedImage = null" class="absolute top-4 right-4 w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-red-500 transition-all cursor-pointer z-10 shadow-sm border border-slate-200">
            <XMarkIcon class="w-6 h-6" />
          </button>
          <img :src="selectedImage" class="max-w-full max-h-[85vh] object-contain rounded-2xl" />
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref } from 'vue';
import { 
  BookOpenIcon, 
  CheckCircleIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline';

const selectedImage = ref(null);

// Uso del helper URL para importar imágenes estáticas dinámicamente con Vite
const getImageUrl = (path) => {
  return new URL(`../../assets/images/${path}`, import.meta.url).href;
};

const products = [
  {
    name: 'TIAMULIN CG 30%',
    subtitle: 'Tiamulino Fumarato 30g - Antimiciplasmico para aves y cerdos Concentrado Granulado.',
    image: getImageUrl('TIAMULIN-CG30-25kg.webp'),
    bullets: [
      'Facilita su inclusión en alimentos balanceados.',
      'Acción efectiva contra Mycoplasmas y gérmenes Gram positivos y Gram negativos en cerdos.',
      'Se absorbe a nivel intestinal en un 95%.',
      'Resistente a la exposición a altas temperaturas.',
      'Facilita una distribución homogénea.'
    ]
  },
  {
    name: 'CERTYL CG 40',
    subtitle: 'Tilmicosina 40g - Antibiótico de amplio espectro para cerdos. Concentrado Granulado.',
    image: getImageUrl('CERTYL-CG40-10kg.webp'),
    bullets: [
      'Es bacteriostático y bactericida.',
      'Óptimo desempeño contra Mycoplasma y otras bacterias, principalmente del complejo respiratorio porcino.',
      'Reduce el grado de la enfermedad y disminuye la mortalidad.',
      'Protección terapéutica después del periodo de tratamiento, con prolongada persistencia en macrófagos pulmonares.',
      'Reduce el efecto de rechazo del alimento propio de las Tilmicosinas gracias a su sabor a Chocolate.'
    ]
  },
  {
    name: 'CARFENICOL CG 20%',
    subtitle: 'Florfenicol 20g - Antibiótico de amplio espectro para aves y cerdos. Concentrado Granulado.',
    image: getImageUrl('CARFENICOL-CG20-10kg.webp'),
    bullets: [
      'Antibiótico de amplio espectro para el tratamiento y control de bacterias asociadas al Complejo Respiratorio Porcino.',
      'Alta estabilidad al someterlo a condiciones extremas de temperatura.',
      'Altamente soluble.',
      'Facilita concentraciones en pulmones, fluido cerebro-espinal, humor acuoso y leche.',
      'Rápida difusión en comportamientos corporales.'
    ]
  }
];
</script>
