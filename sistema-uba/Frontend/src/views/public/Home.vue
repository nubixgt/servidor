<template>
  <div class="max-w-5xl mx-auto px-6 py-8">
    <!-- Hero -->
    <div class="relative rounded-3xl overflow-hidden shadow-xl mb-6">
      <img :src="base + 'images/imagen2.jpeg'" alt="UBA Hero" class="w-full object-cover max-h-[420px]" />
      <div class="absolute inset-0 bg-gradient-to-b from-transparent to-black/50"></div>
      <div class="absolute bottom-10 left-1/2 -translate-x-1/2 w-3/4">
        <a
          href="https://www.facebook.com/share/p/1bPKokgG61/"
          target="_blank"
          class="block text-center border-2 border-white text-white font-bold text-base py-4 rounded-full hover:bg-white/10 transition-colors"
        >
          Ir al enlace
        </a>
      </div>
    </div>

    <!-- CTA Denuncia -->
    <router-link
      to="/denuncia"
      class="flex items-center justify-center gap-3 w-full bg-[#8B0000] text-white font-bold text-lg py-5 rounded-full hover:bg-[#6d0000] transition-colors mb-8 shadow-md"
    >
      <span class="material-symbols-outlined">gavel</span>
      Realizar Denuncia
    </router-link>

    <!-- Galería -->
    <h2 class="text-2xl font-extrabold text-[#0f2a4a] mb-4">Galería</h2>
    <div class="grid grid-cols-2 gap-3">
      <div class="relative rounded-2xl overflow-hidden aspect-[0.85]">
        <img :src="base + 'images/gallery_felines.jpg'" alt="Felinos" class="w-full h-full object-cover" />
        <div class="absolute bottom-3 left-3 right-3 bg-white/90 rounded-xl p-3">
          <p class="text-[8px] font-bold uppercase tracking-widest">Felinos</p>
          <p class="text-xs font-bold">Recuperación Exitosa</p>
        </div>
      </div>
      <div class="relative rounded-2xl overflow-hidden aspect-[0.85]">
        <img :src="base + 'images/imagen7.jpeg'" alt="Decomiso" class="w-full h-full object-cover" />
        <div class="absolute bottom-3 left-3 right-3 bg-white/90 rounded-xl p-3">
          <p class="text-[8px] font-bold uppercase tracking-widest">Decomiso</p>
          <p class="text-xs font-bold">El Guarda</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const base = import.meta.env.BASE_URL
</script>
