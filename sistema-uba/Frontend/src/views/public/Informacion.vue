<template>
  <div class="max-w-5xl mx-auto px-6 py-8">
    <h1 class="text-center text-3xl font-black text-[#0f2a4a] mb-8">Información</h1>

    <!-- Tarjeta de contacto -->
    <div class="bg-white rounded-3xl border border-[#c3c6d6]/30 shadow-sm p-6 mb-10">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 rounded-full bg-[#FFA454]/20 flex items-center justify-center">
          <span class="material-symbols-outlined text-[#C06C00]">wb_sunny</span>
        </div>
        <span class="font-bold tracking-widest text-sm text-[#C06C00]">Contacto</span>
        <div class="flex-1"></div>
        <a
          href="tel:+50224137070"
          class="flex items-center gap-2 bg-[#0F172A] text-white font-bold text-sm px-4 py-2 rounded-full hover:bg-[#1e293b] transition-colors"
        >
          <span class="material-symbols-outlined text-[16px]">phone</span>
          Llamar
        </a>
      </div>
      <div class="text-center">
        <p class="font-black text-lg text-[#0F172A]">UBA</p>
        <p class="font-bold text-sm text-[#0F172A] mb-4">UNIDAD DE BIENESTAR ANIMAL</p>
        <p class="text-sm text-[#0F172A]">
          <span class="font-bold">Dirección: </span>4A Calle 0-15, Cdad. de Guatemala
        </p>
        <p class="text-sm text-[#0F172A]">
          <span class="font-bold">Teléfono: </span>2413 7070
        </p>
      </div>
    </div>

    <!-- Recursos -->
    <h2 class="text-2xl font-bold text-[#0f2a4a] mb-6">Recursos Destacados</h2>
    <div class="flex flex-col gap-8">
      <div v-for="resource in resources" :key="resource.title">
        <div class="rounded-2xl overflow-hidden h-52">
          <img :src="resource.img" :alt="resource.title" class="w-full h-full object-cover" />
        </div>
        <div class="mt-3">
          <p class="text-[10px] font-bold uppercase tracking-widest text-[#0f2a4a]">{{ resource.tag }}</p>
          <p class="font-bold text-lg text-[#0f2a4a]">{{ resource.title }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const base = import.meta.env.BASE_URL
const resources = [
  { title: 'Protección de Quelonios', tag: 'Guía PDF', img: base + 'images/onboarding_1.jpg' },
  { title: 'Aves Caídas del Nido', tag: 'Video Tutorial', img: base + 'images/onboarding_2.jpg' },
]
</script>
