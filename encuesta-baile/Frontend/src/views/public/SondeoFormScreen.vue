<template>
  <div class="space-y-6">
    <!-- Header -->
    <header class="text-center mb-10 md:mb-16 space-y-4">
      <div class="inline-flex items-center gap-2 px-3 py-1 bg-surface-container rounded-full text-xs text-primary-base font-semibold tracking-wider uppercase mb-2">
        <LucideIcon name="check-circle" class="w-3.5 h-3.5 text-secondary-base" />
        Sondeo Ciudadano Oficial
      </div>
      <h1 class="text-3xl md:text-5xl font-extrabold text-primary-base tracking-tight select-none">
        ¿CÓMO PREFERÍS EL EVENTO?
      </h1>
      <p class="text-base md:text-lg text-on-surface-variant max-w-2xl mx-auto leading-relaxed">
        Marcá tu preferencia para ayudarnos a organizar la mejor experiencia democrática y participativa.
      </p>
    </header>

    <!-- Sub-grid options -->
    <div id="survey-options-grid" class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12 mb-12">
      <button
        v-for="option in options"
        :key="option.id"
        :id="`option-${option.id}`"
        @click="$emit('selectOption', option.id)"
        :class="[
          'group relative text-left bg-surface-container-lowest rounded-xl p-6 md:p-8 flex flex-col justify-between overflow-hidden cursor-pointer transition-all duration-300 border-2 w-full',
          selectedOptionId === option.id 
            ? 'border-secondary-base shadow-lg ring-2 ring-secondary-base/20' 
            : 'border-slate-100 hover:border-secondary-base hover:-translate-y-1 hover:shadow-lg'
        ]"
      >
        <div class="w-full">
          <!-- Image Frame -->
          <div class="aspect-video w-full mb-6 rounded-lg overflow-hidden bg-slate-900 relative">
            <template v-if="!imgFailed[option.id]">
              <img
                :src="option.image"
                :alt="option.imageAlt"
                referrerpolicy="no-referrer"
                @error="onImgError(option.id)"
                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-103"
              />
            </template>
            <template v-else>
              <!-- Custom gorgeous SVG graphic gradient fallback per choice -->
              <div class="w-full h-full flex flex-col items-center justify-center p-6 bg-gradient-to-br from-[#091426] to-[#1e293b] text-white relative">
                <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:16px_16px]" />
                
                <template v-if="option.id === 'option-a'">
                  <div class="flex gap-1.5 mb-2">
                    <span class="w-1.5 h-6 bg-secondary-base animate-pulse rounded-full" />
                    <span class="w-1.5 h-8 bg-indigo-400 animate-pulse delay-75 rounded-full" />
                    <span class="w-1.5 h-6 bg-secondary-base animate-pulse delay-150 rounded-full" />
                  </div>
                  <LucideIcon name="landmark" class="w-12 h-12 text-indigo-200 mb-2 opacity-90 stroke-[1.5]" />
                  <span class="text-[11px] font-black tracking-widest uppercase text-slate-300">ESTADIO ARENA</span>
                </template>
                <template v-else>
                  <LucideIcon name="music" class="w-12 h-12 text-secondary-fixed mb-2 stroke-[1.5]" />
                  <span class="text-[11px] font-black tracking-widest uppercase text-indigo-300">SALÓN DE CONCIERTOS</span>
                </template>
                
                <span class="absolute bottom-3 right-3 text-[9px] font-mono text-slate-500 bg-slate-950/40 px-2 py-0.5 rounded">
                  Ilustración Digital Activa
                </span>
              </div>
            </template>

            <div v-if="selectedOptionId === option.id" class="absolute inset-0 bg-secondary-base/5 flex items-center justify-center transition-opacity" />
          </div>

          <!-- Badge & Check Indicator -->
          <div class="flex items-center justify-between mb-4">
            <span :class="['text-xs font-bold px-3 py-1.5 rounded-full select-none', option.badgeColor]">
              {{ option.badge }}
            </span>
            <div class="flex items-center gap-1.5">
              <span v-if="selectedOptionId === option.id" class="flex items-center gap-1 text-secondary-base text-xs font-semibold animate-fade-in">
                <LucideIcon name="check-circle-2" class="w-5 h-5 text-secondary-base fill-secondary-base/10" />
              </span>
              <div v-else class="w-5 h-5 rounded-full border-2 border-slate-200 group-hover:border-slate-300 transition-colors" />
            </div>
          </div>

          <!-- Text Details -->
          <h2 class="text-xl md:text-2xl font-bold text-primary-base mb-2 group-hover:text-secondary-base transition-colors">
            {{ option.title }}
          </h2>
          <p class="text-sm md:text-base text-on-surface-variant leading-relaxed">
            {{ option.description }}
          </p>
        </div>

        <!-- Footer selector simulation -->
        <div class="mt-8 pt-4 border-t border-slate-50 flex items-center font-semibold text-secondary-base text-xs md:text-sm tracking-wider w-full">
          <span class="group-hover:mr-1 transition-all uppercase">
            {{ selectedOptionId === option.id ? 'PROPUESTA SELECCIONADA' : 'SELECCIONAR ESTA PROPUESTA' }}
          </span>
          <LucideIcon name="arrow-right" :class="['w-4 h-4 ml-1.5 transition-transform', selectedOptionId === option.id ? 'translate-x-1' : 'group-hover:translate-x-1']" />
        </div>
      </button>
    </div>

    <!-- Dynamic Confirm trigger button -->
    <div class="flex flex-col items-center justify-center space-y-3">
      <button
        id="submit-btn"
        @click="$emit('submitVote')"
        :disabled="!selectedOptionId || isSubmitting"
        :class="[
          'px-12 py-4 rounded-lg font-bold text-sm tracking-wide shadow-md transition-all duration-300 transform select-none cursor-pointer relative overflow-hidden min-w-[320px] text-center',
          selectedOptionId 
            ? `${submitColor} hover:bg-slate-800 text-white active:scale-95` 
            : 'bg-slate-200 text-slate-400 cursor-not-allowed border border-slate-300'
        ]"
      >
        <span v-if="isSubmitting" class="flex items-center justify-center gap-2">
          <span class="w-4 h-4 border-2 border-white/65 border-t-transparent rounded-full animate-spin" />
          {{ submitText }}
        </span>
        <span v-else>{{ submitText }}</span>
      </button>
      <p class="text-[11px] text-on-surface-variant text-center max-w-sm">
        Al confirmar, tu preferencia se integrará con firmas criptográficas al registro oficial de Democratic Pulse.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import LucideIcon from '../components/LucideIcon.vue';

defineProps({
  options: {
    type: Array,
    required: true
  },
  selectedOptionId: {
    type: String,
    default: null
  },
  isSubmitting: {
    type: Boolean,
    default: false
  },
  submitText: {
    type: String,
    required: true
  },
  submitColor: {
    type: String,
    required: true
  }
});

defineEmits(['selectOption', 'submitVote']);

const imgFailed = ref({});

const onImgError = (id) => {
  imgFailed.value[id] = true;
};
</script>
