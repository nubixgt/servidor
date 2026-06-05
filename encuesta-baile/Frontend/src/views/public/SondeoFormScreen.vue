<template>
  <div class="relative min-h-screen flex flex-col justify-between overflow-x-hidden font-sans bg-[#0a0f1e] text-white -mt-10 md:-mt-14 -mb-[3rem]" style="width: 100vw; margin-left: calc(-50vw + 50%);">


    <!-- Cinematic Background Illustration (BackgroundDecoration) -->
    <div class="absolute top-0 right-0 w-full h-[614px] md:w-1/2 md:h-screen pointer-events-none opacity-30 md:opacity-50 overflow-hidden z-0">
      <img
        alt="Premium Event Scene"
        class="w-full h-full object-cover object-center transition-all duration-1000 ease-out scale-105"
        src="https://lh3.googleusercontent.com/aida-public/AB6AXuBf-te7Jx8lFHnda-WiD_84QAIaiT4YyIPWEzclFHKDavGE0bI6_UaUAwYumD_jhr3rPCxBwymYe2VHHKFb_y0jgg1k2ZTSx_QDNtcXOUJQxIAdKpIO0U4ww7Y4SRTb-IpJCYmvlnMDths50SbIivZg16kaqcKoQ4L1s8LOEgyrmVdHlHjp1Fn6c9rAvYHzQbHpsVVMO4rQxWRpygU-_abcl4FXnhOTyHsKRaPnQb1Ifn0gXjnCkSVeE123-BKHw3tvtnhxxMjfsO3R"
        referrerpolicy="no-referrer"
      />
      <div class="absolute inset-0 bg-gradient-to-b from-[#0a0f1e]/10 via-[#0a0f1e]/80 to-[#0a0f1e]"></div>
      <div class="absolute inset-0 bg-gradient-to-r from-[#0a0f1e] via-[#0a0f1e]/30 to-transparent hidden md:block"></div>
      <div class="absolute top-1/4 right-1/4 w-64 h-64 bg-[#f2ca50]/5 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Content wrapper -->
    <main class="relative z-10 w-full max-w-6xl mx-auto px-6 md:px-8 mt-6 flex-grow flex flex-col justify-start">

      <div class="flex flex-col gap-8 md:gap-11 animate-fade-in">

        <!-- Header / Title Area -->
        <div class="max-w-2xl">
          <h1 class="font-serif text-4xl md:text-5xl font-bold tracking-tight text-white mb-4 leading-tight">
            ¿Cómo prefieres
            <span class="text-[#f2ca50] italic font-normal font-serif"> el baile social?</span>
          </h1>
          <p class="text-sm md:text-base text-[#d0c5af] font-sans max-w-xl leading-relaxed">
            Elegí la opción que más se ajuste a lo que tenés en mente. Tu preferencia define la noche.
          </p>
        </div>

        <!-- Options Interactive Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
          <div
            v-for="option in displayOptions"
            :key="option.id"
            @click="$emit('selectOption', option.voteId)"
            :class="[
              'group cursor-pointer relative rounded-2xl p-6 md:p-8 flex flex-col justify-between gap-6 transition-all duration-300 overflow-hidden card-glow border-2',
              selectedOptionId === option.voteId
                ? 'bg-slate-900/45 border-[#f2ca50] gold-glow'
                : 'bg-[#141b2e] border-[rgba(153,144,124,0.2)] hover:border-[#f2ca50]/40'
            ]"
          >
            <!-- Radio indicator -->
            <div class="flex justify-between items-start">
              <div
                :class="[
                  'w-5 h-5 rounded-full border-2 transition-all flex items-center justify-center',
                  selectedOptionId === option.voteId
                    ? 'border-[#f2ca50] bg-[#0a0f1e]'
                    : 'border-[#99907c]/60 group-hover:border-[#f2ca50]'
                ]"
              >
                <div v-if="selectedOptionId === option.voteId" class="w-2.5 h-2.5 bg-[#f2ca50] rounded-full"></div>
              </div>
            </div>

            <!-- Card content -->
            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">

              <!-- Circle Thumbnail Image -->
              <div class="relative w-32 h-32 flex-shrink-0 rounded-full overflow-hidden border border-[#f2ca50]/40 shadow-inner bg-[#0a0f1e] flex items-center justify-center">
                <div class="absolute inset-0 opacity-15 flex items-center justify-center">
                  <span class="material-symbols-outlined text-[72px] text-[#f2ca50]">{{ option.decorIcon }}</span>
                </div>
                <img
                  :alt="option.title"
                  class="w-full h-full object-cover rounded-full group-hover:scale-105 transition-transform duration-500"
                  :src="option.imageUrl"
                  referrerpolicy="no-referrer"
                />
              </div>

              <!-- Card main contents layout -->
            <div class="flex flex-col gap-3 text-center sm:text-left flex-grow">
              <span class="text-[#f2ca50] text-xs font-bold tracking-widest uppercase">{{ option.badge }}</span>
              <h3 class="font-serif text-xl md:text-2xl font-bold text-white tracking-wide leading-snug uppercase">
                {{ option.title }}
              </h3>
                <div class="h-[1px] w-8 bg-[#f2ca50] opacity-50 mx-auto sm:mx-0"></div>

                <!-- Highlights list -->
                <ul class="flex flex-col gap-3 mt-1.5">
                  <li v-for="(hl, idx) in option.highlights" :key="idx" class="flex gap-3 text-left">
                    <div class="text-[#f2ca50] flex-shrink-0 mt-0.5">
                      <span class="material-symbols-outlined" style="font-size:15px; font-variation-settings: 'FILL' 1;">{{ hl.matIcon }}</span>
                    </div>
                    <div class="font-sans text-xs text-[#d0c5af] flex flex-col mt-0.5">
                      <span class="font-medium text-white/90">
                        <span class="text-[#f2ca50] mr-1">{{ hl.label }}:</span>{{ hl.value }}
                      </span>
                      <span v-if="hl.subLabel" class="text-[10px] opacity-70 mt-0.5">{{ hl.subLabel }}</span>
                    </div>
                  </li>
                </ul>

                <!-- Nota (solo para opción B) -->
                <div v-if="option.voteId === 'option-b'" class="mt-2 p-3 bg-[#f2ca50]/5 border border-[#f2ca50]/20 rounded-lg text-left">
                  <p class="text-[#d0c5af] text-xs font-sans leading-relaxed">
                    <span class="text-[#f2ca50] font-bold">NOTA:</span> El baile social se realiza un dia y el concierto se realiza otro dia
                  </p>
                </div>
              </div>
            </div>

            <!-- Golden CTA button -->
            <button
              @click.stop="$emit('selectOption', option.voteId)"
              :class="[
                'w-full py-3.5 mt-4 text-xs font-bold rounded-lg uppercase tracking-widest transition-all active:scale-95 shadow-md',
                selectedOptionId === option.voteId
                  ? 'gold-gradient-bg text-[#0a0f1e] shadow-[#f2ca50]/15'
                  : 'bg-white/5 text-[#f2ca50] border border-[#f2ca50]/30 hover:bg-[#f2ca50] hover:text-[#0a0f1e]'
              ]"
            >
              {{ selectedOptionId === option.voteId ? 'Propuesta seleccionada' : 'Seleccionar esta propuesta' }}
            </button>
          </div>
        </div>

        <!-- Security Banner -->
        <div class="w-full bg-[#191c1f]/40 border border-[rgba(153,144,124,0.3)] rounded-xl p-4 flex items-center justify-center gap-3">
          <span class="text-[#f2ca50] material-symbols-outlined" style="font-size:18px; font-variation-settings: 'FILL' 1;">verified_user</span>
          <p class="text-[#d0c5af] text-xs md:text-sm text-center">
            Tu respuesta es anónima y nos ayuda a crear
            <span class="text-[#f2ca50] font-semibold"> el mejor evento para todos.</span>
          </p>
        </div>

        <!-- Navigation -->
        <div class="w-full flex justify-between items-center mt-2 pb-6">
          <div></div>
          <button
            @click="$emit('submitVote')"
            :disabled="!selectedOptionId || isSubmitting"
            :class="[
              'group flex items-center gap-3 py-3.5 px-8 gold-gradient-bg text-[#0a0f1e] hover:shadow-lg hover:shadow-[#f2ca50]/10 font-sans font-bold uppercase tracking-widest text-xs rounded-full transition-all active:scale-95 cursor-pointer',
              !selectedOptionId || isSubmitting ? 'opacity-40 cursor-not-allowed' : ''
            ]"
          >
            <span v-if="isSubmitting" class="w-4 h-4 border-2 border-[#0a0f1e] border-t-transparent rounded-full animate-spin"></span>
            <span>{{ isSubmitting ? 'Enviando...' : 'Siguiente' }}</span>
            <span v-if="!isSubmitting" class="material-symbols-outlined transition-transform group-hover:translate-x-1" style="font-size:14px;">chevron_right</span>
          </button>
        </div>

      </div>
    </main>

  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
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

// Map our vote options to the Diseno2 visual data (images, icons, highlights)
const diseno2Data = {
  'option-a': {
    imageUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuALhUMFkEhN6EP_y7bhrFvWAN8KLdk8hhObcZJsHEaEQFkRAj3-W2vkZhU_Rrn2hWnS9dtWYTNsrpQmDq-Ft4XPATTTfGQxdX05zT3AVKkb08WtL82wqSEMbXHthCod6hqM7WkS7jDZHHIx-EjUWbiDhQP3O4IqwzR-Jdin9LFVJOSaAPv7JJUT7PMEaOmZ9WyNxYuXZajz96ZzRdIsimI-5gwL5SARvddJJ8eLo1aZv_bMnwo1Fxad5BjsdGNOUnQh0WZVwSrEW3Ha',
    decorIcon: 'stadium',
  },
  'option-b': {
    imageUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuA0207HjOnL5QK0J4FSNBKhVKwh4De7HoKSwv3yVwtY2YHEbGlDGQCC34IuZ7IhWnFvan5wSTusU55Bt2gQD1jra-bMbih852biH4d_6VbJjMIFtehhqjF0xr0Z7PA1FiyTN9l1T2wPR2ZXEOVVrh3aTVzrTp-sATBE2wsJod2-Z8Fp9fHn92sJzLmH6jJtX25xczRzCQGjDkMtOPCBUW54YpWkJhUTV6C1skOY-ijs0Z6PznDF7ddYvJHfdOob8qiZeRafYHwmatD3',
    decorIcon: 'meeting_room',
  }
};

// Map Lucide icon names used in data.js to Material Symbols names
const iconMap = {
  'location_on': 'location_on',
  'music_note': 'music_note',
  'star': 'star',
  'groups': 'groups',
  'apartment': 'apartment',
  'featured_play_list': 'featured_play_list',
  'mic_external_on': 'mic_external_on',
  'event': 'event',
};

// Build display options merging our real data with Diseno2 visuals
const displayOptions = computed(() => {
  return props.options.map(opt => {
    const visual = diseno2Data[opt.id] || {};
    return {
      voteId: opt.id,
      badge: opt.badge,
      title: opt.title,
      imageUrl: visual.imageUrl,
      decorIcon: visual.decorIcon,
      highlights: (opt.details || []).map(d => ({
        matIcon: d.icon,
        label: d.label,
        value: d.value,
        subLabel: d.subLabel || null,
      }))
    };
  });
});
</script>

<style scoped>
.gold-gradient-bg {
  background: linear-gradient(90deg, #d4af37 0%, #f2ca50 50%, #d4af37 100%);
}

.card-glow:hover {
  box-shadow: 0 0 25px rgba(242, 202, 80, 0.12);
}

.gold-glow {
  box-shadow: 0 0 35px rgba(242, 202, 80, 0.18);
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
  animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.material-symbols-outlined {
  font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
  vertical-align: middle;
}
</style>
