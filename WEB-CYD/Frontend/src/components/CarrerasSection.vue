<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { gsap } from '@/lib/gsap.js'
import { MonitorPlay, FlaskConical, Stethoscope, Bot, ChevronRight, ChevronLeft } from 'lucide-vue-next'

const sectionRef = ref(null)
const carouselRef = ref(null)
let ctx = null
let autoScrollInterval = null

const carreras = [
  {
    title: 'Computación y Programación',
    icon: MonitorPlay,
    bg: 'https://images.unsplash.com/photo-1531482615713-2afd69097998?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'
  },
  {
    title: 'Perito en Química Biológica',
    icon: FlaskConical,
    bg: 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'
  },
  {
    title: 'Perito en Ciencias de la Salud',
    icon: Stethoscope,
    bg: 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'
  },
  {
    title: 'Robótica e Inteligencia Artificial',
    icon: Bot,
    bg: 'https://images.unsplash.com/photo-1561557944-6e7860d1a7eb?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'
  }
]

const scrollCarousel = (direction) => {
  if (carouselRef.value) {
    const scrollAmount = 240; // width of card + gap
    carouselRef.value.scrollBy({
      left: direction === 'right' ? scrollAmount : -scrollAmount,
      behavior: 'smooth'
    })
  }
}

const startAutoScroll = () => {
  stopAutoScroll();
  autoScrollInterval = setInterval(() => {
    if (carouselRef.value) {
      const el = carouselRef.value;
      // Si llega al final, regresa al inicio suavemente
      if (el.scrollLeft + el.clientWidth >= el.scrollWidth - 10) {
        el.scrollTo({ left: 0, behavior: 'smooth' });
      } else {
        scrollCarousel('right');
      }
    }
  }, 2500);
}

const stopAutoScroll = () => {
  if (autoScrollInterval) {
    clearInterval(autoScrollInterval);
    autoScrollInterval = null;
  }
}

onMounted(() => {
  ctx = gsap.context(() => {
    gsap.from('.oferta-left > *', {
      scrollTrigger: { trigger: sectionRef.value, start: 'top 78%' },
      y: 28, opacity: 0, duration: 0.7, stagger: 0.12, ease: 'power3.out'
    })
    gsap.from('.carrera-card', {
      scrollTrigger: { trigger: sectionRef.value, start: 'top 80%' },
      x: 60, opacity: 0, duration: 0.7, stagger: 0.1, ease: 'power3.out'
    })
  }, sectionRef.value)
  startAutoScroll()
})

onUnmounted(() => { 
  ctx?.revert()
  stopAutoScroll()
})
</script>

<template>
  <section ref="sectionRef" id="oferta" class="py-12 lg:py-20 bg-white overflow-hidden">
    <div class="cyd-container">
      <div class="flex flex-col lg:flex-row gap-10 lg:gap-8 items-start lg:items-center">

        <!-- LEFT: Texto -->
        <div class="oferta-left lg:w-[320px] xl:w-[380px] shrink-0 flex flex-col gap-5">
          <span class="text-[0.7rem] font-extrabold tracking-[0.22em] text-green-700 uppercase">NUESTRA OFERTA ACADÉMICA</span>
          
          <h2 class="font-black leading-tight text-slate-800" style="font-family: var(--font-display); font-size: clamp(2rem, 4vw, 3rem);">
            Descubre <span class="text-transparent bg-clip-text" style="background-image: linear-gradient(90deg, #164627, #4ade80);">tu futuro</span>
          </h2>

          <p class="text-slate-500 font-medium leading-relaxed">
            Contamos con 19 carreras que te preparan para los desafíos del mundo actual.
          </p>

          <button
            class="w-fit flex items-center gap-3 text-white font-semibold rounded-full transition-all hover:-translate-y-0.5 hover:shadow-lg active:scale-95"
            style="background: linear-gradient(90deg, #22c55e, #16a34a); padding: 0.75rem 1.4rem; font-size: 0.875rem;"
          >
            <span>Ver todas las carreras</span>
            <span class="w-6 h-6 bg-white/25 rounded-full flex items-center justify-center">
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
            </span>
          </button>
        </div>

        <!-- RIGHT: Carrusel de tarjetas -->
        <div 
          class="relative flex-1 min-w-0 flex items-center"
          @mouseenter="stopAutoScroll"
          @mouseleave="startAutoScroll"
          @touchstart="stopAutoScroll"
          @touchend="startAutoScroll"
        >
          
          <!-- Botón Scroll Izquierda (opcional, oculto en mobile) -->
          <button 
            @click="scrollCarousel('left')"
            class="hidden lg:flex absolute -left-5 z-20 w-10 h-10 bg-white rounded-full shadow-lg items-center justify-center text-green-700 hover:bg-green-50 transition-colors border border-slate-100"
          >
            <ChevronLeft class="w-5 h-5" />
          </button>

          <!-- Carrusel Contenedor -->
          <div 
            ref="carouselRef"
            class="flex gap-4 overflow-x-auto pb-4 pt-4 snap-x snap-mandatory hide-scrollbar -mr-4 pr-4 sm:-mr-6 sm:pr-6 lg:mr-0 lg:pr-0 w-full"
            style="scroll-behavior: smooth;"
          >
            <div
              v-for="(c, i) in carreras"
              :key="i"
              class="carrera-card relative shrink-0 rounded-2xl overflow-hidden cursor-pointer group snap-start shadow-md hover:shadow-xl transition-shadow"
              style="width: 220px; height: 340px;"
            >
              <!-- Imagen de fondo -->
              <img
                :src="c.bg"
                :alt="c.title"
                class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
              />
              <!-- Overlay -->
              <div class="absolute inset-0 bg-gradient-to-b from-slate-900/10 via-slate-900/40 to-slate-900/90 transition-opacity duration-300 group-hover:opacity-90"></div>

              <!-- Contenido -->
              <div class="absolute inset-0 flex flex-col justify-end p-5 z-10">
                <div
                  class="w-12 h-12 rounded-full mb-3 flex items-center justify-center transition-all duration-300 group-hover:bg-green-500 group-hover:scale-110 shadow-lg"
                  style="background: rgba(255,255,255,0.2); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1);"
                >
                  <component :is="c.icon" class="w-6 h-6 text-white" stroke-width="2" />
                </div>
                <h3 class="text-white font-bold leading-snug transition-transform duration-300 group-hover:-translate-y-1" style="font-family: var(--font-display); font-size: 1.1rem;">
                  {{ c.title }}
                </h3>
              </div>
            </div>
          </div>

          <!-- Botón Scroll Derecha -->
          <button 
            @click="scrollCarousel('right')"
            class="hidden lg:flex absolute -right-5 z-20 w-10 h-10 bg-white rounded-full shadow-lg items-center justify-center text-green-700 hover:bg-green-50 transition-colors border border-slate-100"
          >
            <ChevronRight class="w-5 h-5" />
          </button>
        </div>

      </div>
    </div>
  </section>
</template>

<style scoped>
.hide-scrollbar { scrollbar-width: none; -ms-overflow-style: none; }
.hide-scrollbar::-webkit-scrollbar { display: none; }
</style>
