<template>
  <div id="cuaderno-view-container" class="space-y-7 pb-20 select-none animate-fade-in-up">
    <div class="border-b border-white/10 pb-5">
      <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
        Cuaderno de tu coach
      </h2>
      <!-- Una frase de para qué sirve (revisión del 2026-10-21): el párrafo de cinco líneas explicaba cómo está
           montado («alimenta el chat y el plan») y empujaba el formulario hacia abajo. -->
      <p class="text-sm text-white/80 mt-1 font-medium max-w-3xl leading-relaxed">
        Lo que tu coach recuerda de ti. Lo que escribas aquí cuenta desde la próxima semana que prepare.
      </p>
    </div>
    <!-- El mismo panel que la pestaña del chat (Beta 3.0, F9): components/chat/CuadernoPanel.vue. -->
    <CuadernoPanel />
  </div>
</template>

<script setup>
import CuadernoPanel from '../../components/chat/CuadernoPanel.vue';
</script>
