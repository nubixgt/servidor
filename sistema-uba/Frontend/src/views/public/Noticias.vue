<template>
  <div class="max-w-5xl mx-auto px-6 py-8">
    <!-- Header -->
    <p class="text-[10px] font-bold uppercase tracking-widest text-[#0f2a4a] mb-1">Explora el Ecosistema</p>
    <h1 class="text-3xl font-extrabold text-[#0f2a4a] mb-8">Crónicas de la Vida Silvestre</h1>

    <!-- Noticia destacada -->
    <div class="bg-white rounded-3xl overflow-hidden shadow-sm border border-[#c3c6d6]/30 mb-8">
      <img :src="base + 'images/gallery_felines.jpg'" alt="Noticia" class="w-full h-52 object-cover" />
      <div class="p-6">
        <span class="text-[10px] font-bold uppercase tracking-widest text-[#904d00]">Legislación Urgente</span>
        <h2 class="text-xl font-bold text-[#0f2a4a] mt-2 mb-3">Nueva Ley de Protección Animal en Guatemala</h2>
        <p class="text-sm text-[#424654] mb-4">
          El Congreso de la República discute una nueva legislación para fortalecer la protección de animales domésticos y silvestres en todo el país.
        </p>
        <button class="text-sm font-bold text-[#0f2a4a] border border-[#0f2a4a] px-4 py-2 rounded-full hover:bg-[#0f2a4a] hover:text-white transition-colors">
          Leer artículo
        </button>
      </div>
    </div>

    <!-- Recientes -->
    <h3 class="text-lg font-bold text-[#0f2a4a] mb-4">Recientes</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10">
      <div v-for="item in recent" :key="item.title" class="flex gap-3 bg-white rounded-2xl p-4 border border-[#c3c6d6]/30 shadow-sm">
        <img :src="item.img" :alt="item.title" class="w-24 h-20 object-cover rounded-xl flex-shrink-0" />
        <div class="flex flex-col justify-between py-1">
          <p class="font-bold text-sm text-[#0f2a4a] leading-snug">{{ item.title }}</p>
          <p class="text-xs text-[#737785]">{{ item.time }}</p>
        </div>
      </div>
    </div>

    <!-- Newsletter -->
    <div class="bg-[#0f2a4a] rounded-3xl p-8 text-white text-center">
      <h3 class="text-2xl font-bold mb-2">Mantente Informado</h3>
      <p class="text-white/60 text-sm mb-6">Recibe las últimas noticias sobre bienestar animal.</p>
      <div class="flex gap-3 max-w-md mx-auto">
        <input
          v-model="email"
          type="email"
          placeholder="tu@correo.com"
          class="flex-1 bg-white/10 text-white placeholder-white/40 border border-white/20 rounded-full px-4 py-3 text-sm outline-none focus:border-white/50 transition-colors"
        />
        <button class="bg-white text-[#0f2a4a] font-bold px-5 py-3 rounded-full text-sm hover:bg-white/90 transition-colors whitespace-nowrap">
          Suscribirme
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
const base = import.meta.env.BASE_URL
const email = ref('')
const recent = [
  { title: 'Rescate de fauna silvestre en zona 14', img: base + 'images/onboarding_2.jpg', time: 'Hace 2h' },
  { title: 'Campaña de esterilización gratuita en la capital', img: base + 'images/onboarding_4.jpg', time: 'Hace 5h' },
]
</script>
