<template>
  <div class="max-w-5xl mx-auto px-6 py-8">
    <!-- Hero -->
    <div class="relative rounded-3xl overflow-hidden h-[450px] shadow-xl mb-12">
      <img src="/images/onboarding_3.jpg" alt="Reportar" class="w-full h-full object-cover" />
      <div class="absolute inset-0 bg-gradient-to-b from-transparent to-black/80"></div>
      <div class="absolute inset-0 flex flex-col justify-end p-8">
        <span class="inline-block bg-[#FFA454]/80 text-white text-[10px] font-bold uppercase tracking-widest px-3 py-1 rounded-full mb-4 w-fit">
          Acción Ciudadana
        </span>
        <h1 class="text-3xl md:text-4xl font-extrabold text-white leading-tight mb-3">
          Tu voz protege a los que no tienen una.
        </h1>
        <p class="text-white/70 text-base md:text-lg mb-6">
          Cada reporte es una oportunidad de salvar una vida animal en peligro.
        </p>
        <router-link
          to="/denuncia"
          class="flex items-center justify-center gap-2 w-full bg-[#8B0000] text-white font-bold text-lg py-5 rounded-2xl hover:bg-[#6d0000] transition-colors"
        >
          <span class="material-symbols-outlined">gavel</span>
          Realizar Denuncia
        </router-link>
      </div>
    </div>

    <!-- Pasos -->
    <div class="bg-[#f2f4f6] rounded-3xl p-8 mb-12">
      <h2 class="text-2xl font-extrabold text-[#0f2a4a] mb-8">Pasos para una denuncia efectiva</h2>
      <div class="flex flex-col gap-6">
        <div v-for="step in steps" :key="step.num" class="flex items-start gap-5">
          <div class="w-10 h-10 rounded-full bg-[#0f2a4a] text-white font-bold flex items-center justify-center flex-shrink-0 text-sm">
            {{ step.num }}
          </div>
          <div>
            <p class="font-bold text-[#0f2a4a] text-base">{{ step.title }}</p>
            <p class="text-[#424654] text-sm mt-1">{{ step.desc }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Recursos -->
    <div class="flex flex-col gap-4">
      <div class="flex items-center gap-5 border border-[#c3c6d6]/40 rounded-2xl p-5">
        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center flex-shrink-0">
          <span class="material-symbols-outlined text-[#0f2a4a]">help_outline</span>
        </div>
        <div>
          <p class="font-bold text-[#0f2a4a]">¿Necesitas ayuda?</p>
          <p class="text-xs text-[#424654]">Contáctanos al +502 2413 7070</p>
        </div>
      </div>
      <div class="flex items-center gap-5 border border-[#c3c6d6]/40 rounded-2xl p-5">
        <div class="w-10 h-10 rounded-full bg-orange-50 flex items-center justify-center flex-shrink-0">
          <span class="material-symbols-outlined text-[#904d00]">library_books</span>
        </div>
        <div>
          <p class="font-bold text-[#0f2a4a]">Guía de Rescate</p>
          <p class="text-xs text-[#424654]">Descarga el manual en PDF.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const steps = [
  { num: 1, title: 'Registro Visual', desc: 'Toma fotos o videos desde una distancia segura.' },
  { num: 2, title: 'Ubicación Precisa', desc: 'Identifica puntos de referencia cercanos.' },
  { num: 3, title: 'Detalles del Caso', desc: 'Describe brevemente lo que observas.' },
]
</script>
