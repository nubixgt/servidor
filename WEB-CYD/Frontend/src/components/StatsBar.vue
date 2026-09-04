<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { gsap } from '@/lib/gsap.js'

const statsBarRef = ref(null)
let ctx = null

onMounted(() => {
  ctx = gsap.context(() => {
    gsap.from('.stat-item', {
      scrollTrigger: {
        trigger: statsBarRef.value,
        start: 'top 85%',
        toggleActions: 'play none none reverse'
      },
      y: 30,
      opacity: 0,
      duration: 0.6,
      stagger: 0.1,
      ease: 'back.out(1.5)'
    })
  }, statsBarRef.value)
})

onUnmounted(() => {
  ctx?.revert()
})

const stats = [
  {
    title: 'Fundado en 1992',
    subtitle: 'Trayectoria y prestigio',
    icon: `<svg class="w-5 h-5 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>`
  },
  {
    title: '19 Carreras',
    subtitle: 'Diversificado',
    icon: `<svg class="w-5 h-5 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>`
  },
  {
    title: 'Laboratorio MAC',
    subtitle: 'Más de 50 iMacs Tecnología de vanguardia',
    icon: `<svg class="w-5 h-5 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>`
  },
  {
    title: 'Robótica e IA',
    subtitle: 'Aprendizaje del futuro desde hoy',
    icon: `<svg class="w-5 h-5 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>`
  },
  {
    title: 'Laboratorios',
    subtitle: 'Química y Medicina Aprendizaje práctico',
    icon: `<svg class="w-5 h-5 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
          </svg>`
  },
  {
    title: 'APP CYD',
    subtitle: 'Todo tu colegio en tu móvil',
    icon: `<svg class="w-5 h-5 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
          </svg>`
  }
]
</script>

<template>
  <section class="relative z-20 cyd-container" ref="statsBarRef">
    <div class="bg-white/70 backdrop-blur-xl border border-white/80 shadow-[0_8px_30px_rgba(0,0,0,0.06)] rounded-[2rem] p-4 lg:p-6 w-full relative overflow-hidden">
      <!-- Glow superior interno -->
      <div class="absolute top-0 left-1/2 -translate-x-1/2 w-1/2 h-4 bg-gradient-to-b from-white to-transparent opacity-80 rounded-t-full"></div>
      
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6 lg:gap-4 relative z-10">
        
        <div v-for="(item, index) in stats" :key="index" class="stat-item flex flex-col items-center text-center px-2 relative">
          <!-- Separador vertical en desktop (excepto el último) -->
          <div v-if="index !== stats.length - 1" class="hidden lg:block absolute right-0 top-1/2 -translate-y-1/2 w-px h-12 bg-gradient-to-b from-transparent via-slate-300 to-transparent"></div>
          
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-green-50 to-white border border-green-100 shadow-sm flex items-center justify-center mb-3">
            <span v-html="item.icon"></span>
          </div>
          
          <h3 class="text-[0.85rem] font-bold text-slate-800 leading-tight mb-1" style="font-family: var(--font-display);">
            <template v-if="item.title.includes(' ')">
              <span class="block">{{ item.title.split(' ')[0] }}</span>
              <span class="block">{{ item.title.substring(item.title.indexOf(' ') + 1) }}</span>
            </template>
            <template v-else>
              {{ item.title }}
            </template>
          </h3>
          
          <p class="text-[0.65rem] text-slate-500 font-medium leading-[1.3] max-w-[120px]">
            {{ item.subtitle }}
          </p>
        </div>
        
      </div>
    </div>
  </section>
</template>

<style scoped>
</style>
