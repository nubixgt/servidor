<template>
  <button
    type="button"
    @click="store.abrirAyuda()"
    class="flex items-center gap-2 p-2.5 sm:pl-3 sm:pr-4 rounded-full
           glass-pill border border-white/25 text-[#f1f0ee] font-bold text-xs uppercase tracking-wider
           shadow-[0_4px_20px_rgba(0,0,0,0.45)] hover:border-[#caf300]/60 hover:text-[#caf300]
           hover:scale-105 active:scale-95 transition-all select-none group"
    title="Pedir ayuda a una persona, o contarnos un fallo, una idea o algo que te gusta"
    aria-label="Ayuda"
  >
    <span class="material-symbols-outlined text-[18px]">support_agent</span>
    <!-- En el teléfono sólo el icono: con el texto tapaba contenido (auditoría del 2026-09-24). -->
    <span class="hidden sm:inline">Ayuda</span>
  </button>
</template>

<script setup>
// Este boton NO se posiciona solo: vive dentro de FloatingActions.vue, que lo apila con
// el del Coach para que no se pisen.
//
// Fue un enlace a WhatsApp (hasta el 2026-09-11) y luego «Opinar». Desde la Beta 3.0 (F3)
// abre «Ayuda» (components/modals/AyudaModal.vue): pedir ayuda a una persona con el
// diagnóstico que arma el servidor, opinar como siempre, y ver lo que ya preguntó.
import { useAppStore } from '../../stores/appStore';

const store = useAppStore();
</script>
