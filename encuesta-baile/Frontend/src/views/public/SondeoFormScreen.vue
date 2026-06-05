<template>
  <div class="space-y-6">
    <!-- Header -->
    <header class="text-center mb-10 md:mb-16 space-y-4">
      <div class="inline-flex items-center gap-2 px-3 py-1 bg-surface-container rounded-full text-xs text-primary-base font-semibold tracking-wider uppercase mb-2">
        <LucideIcon name="check-circle" class="w-3.5 h-3.5 text-secondary-base" />
        Baile Social
      </div>
      <h1 class="text-3xl md:text-5xl font-extrabold text-primary-base tracking-tight select-none">
        ¿CÓMO PREFIEREN EL BAILE SOCIAL?
      </h1>
    </header>

    <!-- Sub-grid options -->
    <div id="survey-options-grid" class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12 mb-12">
      <button
        v-for="option in options"
        :key="option.id"
        :id="`option-${option.id}`"
        @click="$emit('selectOption', option.id)"
        :class="[
          'group relative text-left rounded-xl p-6 md:p-8 flex flex-col justify-between overflow-hidden cursor-pointer transition-all duration-300 w-full',
          option.id === 'option-a' ? 'bg-[#eef5ff] border border-blue-200' : 'bg-[#e4fbe9] border border-green-200',
          selectedOptionId === option.id 
            ? 'border-secondary-base shadow-lg ring-2 ring-secondary-base/20' 
            : 'hover:border-secondary-base hover:-translate-y-1 hover:shadow-lg'
        ]"
      >
        <div class="w-full">
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
          <!-- Detalles adicionales siempre visibles -->
          <div 
            v-if="option.details" 
            class="mt-4"
          >
            <div class="bg-white rounded-lg overflow-hidden">
              <table class="w-full text-sm text-left">
                <tbody>
                  <tr v-for="(detail, index) in option.details" :key="index" class="bg-white">
                    <th class="px-4 py-4 font-bold text-slate-700 w-2/5 border-b border-slate-100 last:border-0 align-top">
                      <div class="flex items-center gap-2">
                        <LucideIcon v-if="detail.icon" :name="detail.icon" class="w-4 h-4 text-slate-400" />
                        <span>{{ detail.label }}</span>
                      </div>
                    </th>
                    <td class="px-4 py-4 text-slate-600 border-b border-slate-100 last:border-0" v-html="detail.value">
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
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
    </div>
  </div>
</template>

<script setup>
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


</script>
