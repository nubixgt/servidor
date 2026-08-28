<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { gsap } from '@/lib/gsap.js'

const sectionRef = ref(null)
let ctx = null

// Categorías con imágenes del colegio reales cuando estén disponibles
const galeriaItems = [
  {
    key: 'estudiantes',
    title: 'Día del Estudiante 2024',
    category: 'Eventos',
    accent: '#1a56a8',
    // Placeholder usando gradiente CYD
    gradient: 'linear-gradient(135deg, #1a56a8 0%, #4080e0 100%)',
  },
  {
    key: 'graduacion',
    title: 'Graduación 2024',
    category: 'Académico',
    accent: '#7a2eb8',
    gradient: 'linear-gradient(135deg, #5a1d9a 0%, #a060e0 100%)',
  },
  {
    key: 'deporte',
    title: 'Torneo Intercolegial',
    category: 'Deportes',
    accent: '#c94a1a',
    gradient: 'linear-gradient(135deg, #c94a1a 0%, #f08050 100%)',
  },
  {
    key: 'ciencia',
    title: 'Feria Científica 2024',
    category: 'Ciencias',
    accent: '#1a7a5a',
    gradient: 'linear-gradient(135deg, #1a5a3a 0%, #3a9a60 100%)',
  },
  {
    key: 'arte',
    title: 'Festival de Artes',
    category: 'Arte y Cultura',
    accent: '#b8304a',
    gradient: 'linear-gradient(135deg, #9a1838 0%, #e05070 100%)',
  },
  {
    key: 'marimba',
    title: 'Concierto de Marimba',
    category: 'Música',
    accent: '#9a7200',
    gradient: 'linear-gradient(135deg, #7a5800 0%, #d0a000 100%)',
  },
]

// Cámara SVG propio
const cameraSvg = `<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M6.5 3.5h5l1.5 2h2a1 1 0 011 1v8a1 1 0 01-1 1H3a1 1 0 01-1-1v-8a1 1 0 011-1h2l1.5-2z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
  <circle cx="9" cy="10" r="2.5" stroke="currentColor" stroke-width="1.4"/>
</svg>`

// Play SVG propio
const playSvg = `<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
  <circle cx="9" cy="9" r="7.5" stroke="currentColor" stroke-width="1.4"/>
  <path d="M7 6.5l5.5 2.5L7 11.5V6.5z" fill="currentColor"/>
</svg>`

const hoveredIndex = ref(null)

onMounted(() => {
  ctx = gsap.context(() => {

    // Header
    gsap.from('.gal-header', {
      opacity: 0, y: 60, duration: 1, ease: 'power3.out',
      scrollTrigger: { trigger: '.gal-header', start: 'top 80%' },
    })

    // Grid con efecto en escalera (masonry visual)
    document.querySelectorAll('.gal-item').forEach((el, i) => {
      gsap.from(el, {
        opacity: 0,
        y: 50 + (i % 3) * 15,
        scale: 0.95,
        duration: 0.75,
        ease: 'power3.out',
        scrollTrigger: {
          trigger: el,
          start: 'top 90%',
        },
      })
    })

    // Video cards
    gsap.from('.gal-video-card', {
      opacity: 0, y: 40, scale: 0.97, duration: 0.8, stagger: 0.15, ease: 'power3.out',
      scrollTrigger: { trigger: '.gal-videos', start: 'top 85%' },
    })

    // Parallax muy suave en items de galería
    document.querySelectorAll('.gal-img-inner').forEach((img, i) => {
      const dir = i % 2 === 0 ? -8 : 8
      gsap.to(img, {
        y: dir,
        ease: 'none',
        scrollTrigger: {
          trigger: img.closest('.gal-item'),
          start: 'top bottom',
          end: 'bottom top',
          scrub: 1.5,
        },
      })
    })

  }, sectionRef.value)
})

onUnmounted(() => { ctx?.revert() })
</script>

<template>
  <section
    id="galeria"
    ref="sectionRef"
    class="py-16 lg:py-32 relative overflow-hidden cyd-section-bg"
  >
    <!-- Fondo -->
    <div class="absolute inset-0" aria-hidden="true">
      <div
        class="absolute bottom-0 left-0 w-[600px] h-[600px] rounded-full"
        style="background: radial-gradient(circle, color-mix(in srgb, #7a2eb8 5%, transparent), transparent 70%); filter: blur(80px);"
      />
      <div class="absolute inset-0 cyd-dots opacity-20" />
    </div>

    <div class="relative cyd-container">

      <!-- Header -->
      <div class="gal-header text-center max-w-2xl mx-auto mb-12 lg:mb-20">
        <span class="cyd-label mb-5 inline-block">Momentos CYD</span>
        <h2 class="cyd-title mb-5">
          Nuestra <span class="cyd-accent">Galería</span>
        </h2>
        <p class="text-base lg:text-lg" style="color: hsl(var(--muted-foreground));">
          Momentos que capturan la esencia y la vida de nuestra comunidad educativa.
        </p>
      </div>

      <!-- Grid tipo magazine — 6 celdas con tamaños variados -->
      <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <div
          v-for="(item, i) in galeriaItems"
          :key="item.key"
          class="gal-item group relative rounded-2xl overflow-hidden cursor-pointer will-change-transform"
          :class="[
            i === 0 ? 'lg:row-span-2 aspect-[3/4] lg:aspect-auto' : 'aspect-video',
          ]"
          style="min-height: 220px;"
          @mouseenter="hoveredIndex = i"
          @mouseleave="hoveredIndex = null"
        >
          <!-- Fondo con gradiente CYD (placeholder hasta tener imágenes reales) -->
          <div
            class="gal-img-inner absolute inset-[-10%] will-change-transform transition-transform duration-700"
            :style="{ background: item.gradient }"
          >
            <!-- Patrón de puntos sobre el gradiente -->
            <div class="absolute inset-0 cyd-dots opacity-[0.15]" style="background-image: radial-gradient(circle, rgba(255,255,255,0.25) 1px, transparent 1px);" />
            <!-- Ícono flotante de categoría -->
            <div
              class="absolute inset-0 flex items-center justify-center"
              style="font-size: 4rem; opacity: 0.12; color: white;"
            >
              <div v-if="item.category === 'Eventos'">
                <svg width="72" height="72" viewBox="0 0 72 72" fill="none"><path d="M18 12h36v48H18z" stroke="white" stroke-width="2"/><path d="M26 28h20M26 38h14" stroke="white" stroke-width="2" stroke-linecap="round"/></svg>
              </div>
              <div v-else-if="item.category === 'Deportes'">
                <svg width="72" height="72" viewBox="0 0 72 72" fill="none"><circle cx="36" cy="36" r="24" stroke="white" stroke-width="2"/><path d="M24 20l24 32M48 20L24 52" stroke="white" stroke-width="1.5"/></svg>
              </div>
              <div v-else>
                <svg width="72" height="72" viewBox="0 0 72 72" fill="none"><path d="M36 12L44 28h20L50 38l6 20-20-14-20 14 6-20L8 28h20z" stroke="white" stroke-width="2" stroke-linejoin="round"/></svg>
              </div>
            </div>
          </div>

          <!-- Overlay degradado -->
          <div
            class="absolute inset-0 transition-opacity duration-400"
            :style="{
              background: 'linear-gradient(to top, rgba(0,0,0,0.75) 0%, rgba(0,0,0,0.1) 50%, transparent 100%)',
              opacity: hoveredIndex === i ? 1 : 0.8,
            }"
          />

          <!-- Contenido inferior -->
          <div class="absolute bottom-0 left-0 right-0 p-5 translate-y-1 group-hover:translate-y-0 transition-transform duration-300">
            <div class="text-[10px] font-bold tracking-[0.14em] uppercase text-white/70 mb-1">
              {{ item.category }}
            </div>
            <h3 class="text-base font-black text-white leading-tight mb-2" style="font-family: var(--font-display);">
              {{ item.title }}
            </h3>
            <!-- "Ver fotos" aparece al hover -->
            <div
              class="flex items-center gap-2 text-white text-xs font-semibold transition-all duration-300"
              :style="{ opacity: hoveredIndex === i ? 1 : 0, transform: hoveredIndex === i ? 'translateY(0)' : 'translateY(8px)' }"
              v-html="`${cameraSvg} <span>Ver fotos</span>`"
            />
          </div>

          <!-- Badge categoría (esquina) -->
          <div
            class="absolute top-4 right-4 w-9 h-9 rounded-full flex items-center justify-center transition-all duration-300 text-white"
            :style="{
              background: hoveredIndex === i ? item.accent : 'rgba(255,255,255,0.18)',
              backdropFilter: 'blur(8px)',
            }"
            v-html="cameraSvg"
          />
        </div>
      </div>

      <!-- Video highlights -->
      <div class="gal-videos grid md:grid-cols-2 gap-4">
        <div
          class="gal-video-card group relative rounded-2xl overflow-hidden cursor-pointer will-change-transform"
          style="background: linear-gradient(135deg, #1a3a8a 0%, #4060c0 100%); min-height: 180px;"
          @mouseenter="(e) => gsap.to(e.currentTarget, { scale: 1.02, duration: 0.3, ease: 'power2.out' })"
          @mouseleave="(e) => gsap.to(e.currentTarget, { scale: 1, duration: 0.5, ease: 'elastic.out(1,0.5)' })"
        >
          <div class="absolute inset-0 cyd-dots opacity-[0.12]" style="background-image: radial-gradient(circle, rgba(255,255,255,0.25) 1px, transparent 1px);" aria-hidden="true" />
          <div class="relative p-8 flex items-center gap-5 h-full">
            <!-- Botón play SVG -->
            <div
              class="w-14 h-14 rounded-full flex items-center justify-center shrink-0 transition-all duration-300 text-white group-hover:scale-110"
              style="background: rgba(255,255,255,0.18); backdrop-filter: blur(8px);"
              v-html="playSvg"
            />
            <div class="text-white">
              <div class="text-xs tracking-wider uppercase opacity-70 mb-1">Video</div>
              <h3 class="text-lg font-black mb-1" style="font-family: var(--font-display);">Tour Virtual del Colegio</h3>
              <p class="text-sm opacity-80">Recorre nuestras instalaciones y todos nuestros espacios educativos.</p>
            </div>
          </div>
        </div>

        <div
          class="gal-video-card group relative rounded-2xl overflow-hidden cursor-pointer will-change-transform"
          style="background: linear-gradient(135deg, #1a5a2a 0%, #40a060 100%); min-height: 180px;"
          @mouseenter="(e) => gsap.to(e.currentTarget, { scale: 1.02, duration: 0.3, ease: 'power2.out' })"
          @mouseleave="(e) => gsap.to(e.currentTarget, { scale: 1, duration: 0.5, ease: 'elastic.out(1,0.5)' })"
        >
          <div class="absolute inset-0 cyd-dots opacity-[0.12]" style="background-image: radial-gradient(circle, rgba(255,255,255,0.25) 1px, transparent 1px);" aria-hidden="true" />
          <div class="relative p-8 flex items-center gap-5 h-full">
            <div
              class="w-14 h-14 rounded-full flex items-center justify-center shrink-0 transition-all duration-300 text-white group-hover:scale-110"
              style="background: rgba(255,255,255,0.18); backdrop-filter: blur(8px);"
              v-html="playSvg"
            />
            <div class="text-white">
              <div class="text-xs tracking-wider uppercase opacity-70 mb-1">Video</div>
              <h3 class="text-lg font-black mb-1" style="font-family: var(--font-display);">Testimonios de Estudiantes</h3>
              <p class="text-sm opacity-80">Escucha lo que nuestros estudiantes dicen sobre ser parte de CYD.</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</template>
