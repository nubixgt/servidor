<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { gsap } from '@/lib/gsap.js'

const sectionRef = ref(null)
const jaguarRef = ref(null)
let ctx = null

const activeTab = ref('deportes')

const tabs = [
  { id: 'deportes', label: 'Deportes', d: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
  { id: 'arte', label: 'Arte y Cultura', d: 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01' },
  { id: 'valores', label: 'Valores', d: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
  { id: 'actividades', label: 'Actividades', d: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z' }
]

const galleryImages = {
  deportes: [
    'https://images.unsplash.com/photo-1526232761682-d26e03ac148e?w=400&q=80', // original 1
    'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=400&q=80', // original gym class
    'https://images.unsplash.com/photo-1598153346810-860daa814c4b?w=400&q=80', // original track
    'https://images.unsplash.com/photo-1577223625816-7546f13df25d?w=400&q=80', // original 4
  ],
  arte: [
    'https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=400&q=80', // original 1
    'https://images.unsplash.com/photo-1510915361894-db8b60106cb1?w=400&q=80', // music/guitar
    'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=400&q=80', // original 3
    'https://images.unsplash.com/photo-1530021232320-687d8e3dba54?w=400&q=80', // original 4
  ],
  valores: [
    'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=400&q=80', // students collaborating
    'https://images.unsplash.com/photo-1511632765486-a01980e01a18?w=400&q=80', // diverse kids
    'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?w=400&q=80', // original 3
    'https://images.unsplash.com/photo-1517486808906-6ca8b3f04846?w=400&q=80', // original 4
  ],
  actividades: [
    'https://images.unsplash.com/photo-1523580494112-071d31172886?w=400&q=80', // original 1
    'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=400&q=80', // learning activity
    'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=400&q=80', // original 3
    'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?w=400&q=80', // original 4
  ]
}

const changeTab = (id) => {
  if (activeTab.value === id) return
  gsap.to('.gal-img', { opacity: 0, scale: 0.95, duration: 0.18, stagger: 0.04, onComplete: () => {
    activeTab.value = id
    gsap.to('.gal-img', { opacity: 1, scale: 1, duration: 0.35, stagger: 0.06, ease: 'back.out(1.4)' })
  }})
}

onMounted(() => {
  ctx = gsap.context(() => {
    gsap.from('.vida-block', {
      scrollTrigger: { trigger: sectionRef.value, start: 'top 78%' },
      y: 30, opacity: 0, duration: 0.7, stagger: 0.15, ease: 'power3.out'
    })
    gsap.to(jaguarRef.value, {
      y: -12, duration: 3, ease: 'sine.inOut', yoyo: true, repeat: -1
    })
  }, sectionRef.value)
})

onUnmounted(() => { ctx?.revert() })
</script>

<template>
  <section ref="sectionRef" id="actividades" class="py-10 lg:py-16 bg-[#f8fafc]">
    <div class="cyd-container">
      <div class="grid lg:grid-cols-12 gap-5 lg:gap-6 items-stretch">

        <!-- BLOQUE IZQUIERDO: Vida Estudiantil -->
        <div class="vida-block lg:col-span-7 bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100 flex flex-col gap-6">
          <div>
            <span class="text-[0.65rem] font-extrabold tracking-[0.22em] text-slate-400 uppercase">VIDA EN CYD</span>
            <h2 class="font-black text-slate-800 leading-tight mt-1" style="font-family: var(--font-display); font-size: clamp(1.8rem, 3vw, 2.4rem);">
              Mucho más que clases
            </h2>
          </div>

          <!-- Tabs -->
          <div class="flex flex-wrap gap-2">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="changeTab(tab.id)"
              class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border transition-all duration-200"
              :class="activeTab === tab.id
                ? 'bg-green-50 border-green-200 text-green-800 shadow-sm'
                : 'bg-white border-slate-200 text-slate-500 hover:bg-slate-50'"
            >
              <svg class="w-4 h-4" :class="activeTab === tab.id ? 'text-green-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.d"/>
              </svg>
              {{ tab.label }}
            </button>
          </div>

          <!-- Galería 2x2 -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div v-for="(img, i) in galleryImages[activeTab]" :key="i" class="gal-img aspect-square rounded-2xl overflow-hidden bg-slate-100">
              <img :src="img" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500" loading="lazy" />
            </div>
          </div>

          <button class="w-fit flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900 bg-slate-50 hover:bg-slate-100 border border-slate-200 px-5 py-3 rounded-full transition-colors">
            Conoce más sobre la vida estudiantil
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </button>
        </div>

        <!-- BLOQUE DERECHO: Admisiones -->
        <div
          class="vida-block lg:col-span-5 relative rounded-3xl overflow-hidden flex flex-col justify-between p-6 sm:p-8"
          style="min-height: 360px; background: linear-gradient(145deg, #1e5c33 0%, #164627 50%, #0f3a1a 100%);"
        >
          <!-- Dots de fondo decorativos -->
          <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, rgba(255,255,255,0.5) 1px, transparent 1px); background-size: 24px 24px;"></div>
          <div class="absolute -top-16 -right-16 w-48 h-48 rounded-full opacity-10" style="background: radial-gradient(circle, #4ade80, transparent);"></div>

          <!-- Contenido superior -->
          <div class="relative z-10">
            <span class="inline-block text-[0.65rem] font-extrabold tracking-[0.22em] text-green-200 uppercase bg-green-900/40 px-3 py-1.5 rounded-full border border-green-600/30 mb-4">
              ADMISIONES ABIERTAS
            </span>
            <h2 class="font-black text-white leading-tight mb-3" style="font-family: var(--font-display); font-size: clamp(1.8rem, 3vw, 2.4rem);">
              Tu futuro comienza aquí
            </h2>
            <p class="text-green-50/90 font-medium leading-relaxed mb-6 max-w-[280px]">
              Únete a la familia CYD y sé parte del cambio.
            </p>
            <button
              class="flex items-center gap-3 font-bold rounded-full transition-all hover:-translate-y-0.5 active:scale-95"
              style="background: linear-gradient(90deg, #eab308, #f59e0b); color: #1c1400; padding: 0.8rem 1.5rem; font-size: 0.9rem;"
            >
              <span>Más información</span>
              <span class="w-6 h-6 rounded-full flex items-center justify-center" style="background: rgba(0,0,0,0.12);">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
              </span>
            </button>
          </div>

          <!-- Jaguar asomándose (brazos cruzados) -->
          <div class="absolute bottom-0 right-0 w-[180px] sm:w-[220px] pointer-events-none z-10">
            <img
              ref="jaguarRef"
              src="https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/Jaguarcin-Brazos-Cruzados-1761937965935.png"
              alt="Jaguar CYD"
              class="w-full h-auto object-contain will-change-transform"
              style="filter: drop-shadow(-4px 0 24px rgba(0,0,0,0.3));"
              @error="(e) => e.target.style.display='none'"
            />
          </div>
        </div>

      </div>
    </div>
  </section>
</template>

<style scoped>
</style>
