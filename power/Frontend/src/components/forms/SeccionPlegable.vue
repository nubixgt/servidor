<template>
  <!-- Una sección del perfil que se despliega (el responsable, 2026-10-21: «en avanzado quizá fuera mejor
       desplegable cada cosa»). Cerrada, dice qué es y cuánto lleva rellenado; abierta, sus campos. -->
  <section :id="id || undefined" class="glass-card rounded-3xl border scroll-mt-24 overflow-hidden transition-colors"
           :class="abierta ? 'border-white/20' : 'border-white/10 hover:border-white/25'">
    <button ref="cabecera" type="button" class="w-full flex items-center gap-3 sm:gap-4 p-4 sm:p-6 text-left"
            :aria-expanded="String(abierta)" :aria-controls="`${idInterno}-cuerpo`" @click="alternar">
      <span class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-[#caf300]/10 border border-[#caf300]/30 flex items-center justify-center shrink-0">
        <span class="material-symbols-outlined text-[#caf300] text-[22px]">{{ icono }}</span>
      </span>
      <span class="flex-1 min-w-0">
        <span class="block text-base sm:text-lg font-black text-white leading-tight">{{ titulo }}</span>
        <span v-if="descripcion" class="block text-[12px] text-white/60 leading-snug mt-0.5">{{ descripcion }}</span>
      </span>
      <span v-if="resumen" class="hidden sm:inline-flex shrink-0 px-2.5 py-1 rounded-lg text-xs font-bold" :class="claseDelResumen">{{ resumen }}</span>
      <span class="material-symbols-outlined text-white/60 shrink-0 transition-transform" :class="abierta ? 'rotate-180' : ''">expand_more</span>
    </button>
    <p v-if="resumen && !abierta" class="sm:hidden -mt-2 pb-4 px-4 pl-[4.25rem]">
      <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-bold" :class="claseDelResumen">{{ resumen }}</span>
    </p>
    <div v-show="abierta" :id="`${idInterno}-cuerpo`" class="px-4 sm:px-8 pb-6 sm:pb-8 pt-5 space-y-6 border-t border-white/10">
      <slot />
    </div>
  </section>
</template>

<script setup>
import { computed, nextTick, ref } from 'vue';

const props = defineProps({
  id: { type: String, default: '' },
  icono: { type: String, required: true },
  titulo: { type: String, required: true },
  descripcion: { type: String, default: '' },
  resumen: { type: String, default: '' },
  resumenClase: { type: String, default: '' },
  abierta: { type: Boolean, default: false },
});
const emit = defineEmits(['update:abierta']);
const idInterno = `seccion-${Math.random().toString(36).slice(2, 8)}`;

// Lima sólo cuando está completo («7 de 7»); a medias, neutro; vacío u opcional, gris.
const claseDelResumen = computed(() => {
  if (props.resumenClase) return props.resumenClase;
  const m = /^(\d+) de (\d+)$/.exec(props.resumen || '');
  if (m) return m[1] === m[2] ? 'bg-[#caf300]/10 text-[#caf300]' : 'bg-white/10 text-white/80';
  if (['Sin rellenar', 'Opcional', 'Nada declarado'].includes(props.resumen)) return 'bg-white/5 text-white/60';
  return 'bg-white/10 text-white/80';
});

// Al abrir, la cabecera arriba: en el teléfono una sección larga hacía perder dónde se estaba.
const cabecera = ref(null);
const alternar = () => {
  const abrir = !props.abierta;
  emit('update:abierta', abrir);
  if (abrir) nextTick(() => {
    const r = cabecera.value?.getBoundingClientRect();
    if (r && (r.top < 80 || r.top > window.innerHeight * 0.4)) {
      window.scrollTo({ top: window.scrollY + r.top - 90, behavior: 'smooth' });
    }
  });
};
</script>
