<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { gsap } from '@/lib/gsap.js'

const sectionRef = ref(null)
let ctx = null

onMounted(() => {
  ctx = gsap.context(() => {
    gsap.from('.mac-left > *', {
      scrollTrigger: { trigger: sectionRef.value, start: 'top 75%' },
      x: -30, opacity: 0, duration: 0.7, stagger: 0.1, ease: 'power3.out'
    })
    gsap.from('.mac-card', {
      scrollTrigger: { trigger: sectionRef.value, start: 'top 70%' },
      x: 40, opacity: 0, duration: 0.7, stagger: 0.12, ease: 'back.out(1.3)'
    })
  }, sectionRef.value)
})

onUnmounted(() => { ctx?.revert() })
</script>

<template>
  <section ref="sectionRef" id="tecnologia" class="py-8 lg:py-12 bg-[#f8fafc]">
    <div class="cyd-container">
      <div
        class="relative rounded-[2rem] overflow-hidden"
        style="min-height: 460px;"
      >
        <!-- Imagen de fondo del laboratorio MAC -->
        <img
          src="https://images.unsplash.com/photo-1517694712202-14dd9538aa97?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80"
          alt="Laboratorio MAC"
          class="absolute inset-0 w-full h-full object-cover"
          style="object-position: center 30%;"
        />
        <!-- Overlay verde oscuro del mockup -->
        <div
          class="absolute inset-0"
          style="background: linear-gradient(120deg, rgba(20,55,35,0.93) 0%, rgba(22,70,39,0.88) 40%, rgba(14,48,28,0.75) 100%);"
        ></div>

        <!-- Contenido sobre el overlay -->
        <div class="relative z-10 grid lg:grid-cols-2 gap-8 lg:gap-6 p-8 sm:p-10 lg:p-14 items-center">

          <!-- Izquierda: Texto -->
          <div class="mac-left flex flex-col gap-5">
            <span class="text-[0.7rem] font-extrabold tracking-[0.22em] text-green-300 uppercase">TECNOLOGÍA QUE INSPIRA</span>

            <h2 class="font-black text-white leading-tight" style="font-family: var(--font-display); font-size: clamp(2.2rem, 4.5vw, 3.5rem);">
              Laboratorio <span class="text-transparent bg-clip-text" style="background-image: linear-gradient(90deg, #86efac, #fde047);">MAC</span>
            </h2>

            <p class="text-green-100/90 font-medium leading-relaxed max-w-[420px]">
              Contamos con más de 50 iMacs y tecnología de última generación para que vivas una experiencia educativa de alto nivel.
            </p>

            <ul class="flex flex-col gap-3">
              <li v-for="item in ['Aulas equipadas con tecnología Apple', 'Internet de alta velocidad', 'Ambientes modernos y seguros']" :key="item" class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full flex items-center justify-center shrink-0" style="background: rgba(255,255,255,0.15);">
                  <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </span>
                <span class="text-white font-medium text-sm">{{ item }}</span>
              </li>
            </ul>

            <button
              class="w-fit flex items-center gap-3 font-bold rounded-full transition-all hover:-translate-y-0.5 active:scale-95"
              style="background: linear-gradient(90deg, #60a5fa, #86efac); color: #0f2417; padding: 0.75rem 1.4rem; font-size: 0.875rem;"
            >
              <span>Conoce nuestros laboratorios</span>
              <span class="w-6 h-6 rounded-full flex items-center justify-center" style="background: rgba(0,0,0,0.1);">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
              </span>
            </button>
          </div>

          <!-- Derecha: Tarjetas de datos -->
          <div class="flex flex-col gap-4 lg:items-end">
            <div
              v-for="(stat, i) in [
                { value: '50+', label: 'iMacs en nuestro laboratorio', icon: 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
                { value: '100%', label: 'Comprometidos con tu aprendizaje', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
                { value: '∞', label: 'Posibilidades para tu futuro', icon: 'M13 10V3L4 14h7v7l9-11h-7z' }
              ]"
              :key="i"
              :class="['mac-card flex items-center gap-4 rounded-2xl p-5 transition-colors', i === 1 ? 'lg:-translate-x-4' : '']"
              style="background: rgba(255,255,255,0.1); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.15); max-width: 300px; width: 100%;"
            >
              <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" style="background: rgba(255,255,255,0.12);">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.icon" />
                </svg>
              </div>
              <div>
                <div class="text-white font-black text-2xl leading-none mb-0.5">{{ stat.value }}</div>
                <div class="text-green-100/80 text-xs font-medium leading-snug">{{ stat.label }}</div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
</style>
