<template>
  <!-- Una fila de una lista del perfil (una carrera, una marca): cerrada dice lo que es en una línea, abierta
       deja editarla. Cada carrera abierta ocupaba ~600 px en el teléfono y con cuatro eran cuatro pantallas
       (revisión del perfil, 2026-10-21). Quitar va DENTRO, con su texto: cerrada no hay nada que borre. -->
  <div class="rounded-2xl border transition-colors" :class="abierta ? 'border-white/25 bg-white/[0.03]' : 'border-white/10'">
    <button type="button" class="w-full flex items-center gap-3 px-3.5 py-3 min-h-[56px] text-left"
            :aria-expanded="String(abierta)" @click="$emit('update:abierta', !abierta)">
      <slot name="insignia" />
      <span class="flex-1 min-w-0">
        <span class="block text-sm font-bold text-white truncate">{{ titulo }}</span>
        <span v-if="detalle" class="block text-[12px] text-white/70 leading-snug">{{ detalle }}</span>
      </span>
      <span class="material-symbols-outlined text-white/60 shrink-0 transition-transform" :class="abierta ? 'rotate-180' : ''">expand_more</span>
    </button>
    <div v-if="abierta" class="border-t border-white/10 px-3.5 sm:px-5 pt-4 pb-4 space-y-4">
      <slot />
      <div class="flex justify-end pt-1">
        <button type="button" @click="$emit('quitar')"
                class="flex items-center gap-1.5 px-3 py-2.5 min-h-[44px] rounded-xl border border-[#ffb4ab]/35 text-[12px] font-bold text-[#ffb4ab] hover:bg-[#ffb4ab]/10">
          <span class="material-symbols-outlined text-[16px]">delete</span>{{ textoQuitar }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  titulo: { type: String, required: true },
  detalle: { type: String, default: '' },
  abierta: { type: Boolean, default: false },
  textoQuitar: { type: String, default: 'Quitar' },
});
defineEmits(['update:abierta', 'quitar']);
</script>
