<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { gsap, ScrollTrigger } from '@/lib/gsap.js'

const sectionRef = ref(null)
const carouselRef = ref(null)
let ctx = null
let intervalId = null

// ── Imágenes del laboratorio MAC ─────────────────────────
const macLabImages = [
  {
    url: 'https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/DSC06699-1761964955935.jpg?width=8000&height=8000&resize=contain',
    caption: 'Laboratorio Moderno con más de 50 iMacs',
  },
  {
    url: 'https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/IMG_5465-1761964951645.jpg?width=8000&height=8000&resize=contain',
    caption: 'Vista panorámica del laboratorio tecnológico',
  },
  {
    url: 'https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/IMG_5511-1761964951473.jpg?width=8000&height=8000&resize=contain',
    caption: 'Instalaciones de última generación',
  },
  {
    url: 'https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/DSC06744-1761964954840.jpg?width=8000&height=8000&resize=contain',
    caption: 'Estudiantes aprendiendo con tecnología de punta',
  },
  {
    url: 'https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/DSC06776-1761964955772.jpg?width=8000&height=8000&resize=contain',
    caption: 'Educación práctica y moderna',
  },
  {
    url: 'https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/DSC06730-1761964954512.jpg?width=8000&height=8000&resize=contain',
    caption: 'Equipos Apple de última generación',
  },
]

// ── Laboratorios ─────────────────────────────────────────
const laboratorios = [
  {
    title: 'Laboratorio de Química',
    description: 'Espacio completamente equipado con reactivos certificados, equipos profesionales y todas las medidas de seguridad para experimentos prácticos.',
    image: 'https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/object/public/project-uploads/a21d857a-92e3-4d38-abbc-25d3b9abafa8/generated_images/modern-chemistry-laboratory-test-tubes-w-d3e144ba-20251031012200.jpg',
    accent: 'var(--cyd-forest)',
    features: ['Material de laboratorio profesional', 'Reactivos certificados y seguros', 'Experimentos prácticos guiados', 'Normas internacionales de seguridad'],
  },
  {
    title: 'Laboratorio de Medicina',
    description: 'Instalaciones modernas con equipamiento médico educativo de alta precisión, modelos anatómicos detallados para explorar las ciencias de la salud.',
    image: 'https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/object/public/project-uploads/a21d857a-92e3-4d38-abbc-25d3b9abafa8/generated_images/medical-education-laboratory-with-anatom-d75f9b10-20251031012159.jpg',
    accent: '#c05050',
    features: ['Microscopios de alta precisión', 'Modelos anatómicos detallados', 'Equipo médico educativo certificado', 'Prácticas de primeros auxilios'],
  },
]

// ── Carousel ──────────────────────────────────────────────
const currentIndex = ref(0)
const isAutoPlaying = ref(true)

const next = () => {
  isAutoPlaying.value = false
  currentIndex.value = (currentIndex.value + 1) % macLabImages.length
}
const prev = () => {
  isAutoPlaying.value = false
  currentIndex.value = (currentIndex.value - 1 + macLabImages.length) % macLabImages.length
}
const goTo = (i) => {
  isAutoPlaying.value = false
  currentIndex.value = i
}

// ── Stats ─────────────────────────────────────────────────
const stats = [
  { value: 50, suffix: '+', label: 'Computadoras iMac', accent: '#3a56a8' },
  { value: 100, suffix: '%', label: 'Equipo Moderno', accent: 'var(--cyd-green)' },
  { value: 3, suffix: '', label: 'Laboratorios Especializados', accent: '#c05050' },
  { value: 24, suffix: '/7', label: 'APP CYD Disponible', accent: 'var(--cyd-gold)' },
]

onMounted(() => {
  // Autoplay
  intervalId = setInterval(() => {
    if (isAutoPlaying.value) {
      currentIndex.value = (currentIndex.value + 1) % macLabImages.length
    }
  }, 4500)

  ctx = gsap.context(() => {

    // Header section
    gsap.from('.tec-header', {
      opacity: 0, y: 60, duration: 1, ease: 'power3.out',
      scrollTrigger: { trigger: '.tec-header', start: 'top 80%' },
    })

    // Carousel + Info side
    gsap.from('.tec-carousel-wrap', {
      opacity: 0, x: -50, duration: 0.9, ease: 'power3.out',
      scrollTrigger: { trigger: '.tec-carousel-wrap', start: 'top 80%' },
    })
    gsap.from('.tec-info-col', {
      opacity: 0, x: 50, duration: 0.9, ease: 'power3.out',
      scrollTrigger: { trigger: '.tec-info-col', start: 'top 80%' },
    })

    // Parallax del wrapper del laboratorio
    gsap.to('.tec-lab-wrap', {
      yPercent: -8,
      ease: 'none',
      scrollTrigger: {
        trigger: '.tec-lab-section',
        start: 'top bottom',
        end: 'bottom top',
        scrub: 1.5,
      },
    })

    // App section
    gsap.from('.tec-app-left', {
      opacity: 0, x: -60, duration: 1, ease: 'power3.out',
      scrollTrigger: { trigger: '.tec-app-section', start: 'top 80%' },
    })
    gsap.from('.tec-phone-wrap', {
      opacity: 0, x: 60, scale: 0.9, duration: 1, ease: 'power3.out',
      scrollTrigger: { trigger: '.tec-app-section', start: 'top 80%' },
    })

    // Phone float GSAP (reemplaza el scroll-driven con @vueuse)
    gsap.to('.tec-phone-wrap', {
      y: -20,
      duration: 3,
      ease: 'sine.inOut',
      yoyo: true,
      repeat: -1,
    })

    // Labs cards
    gsap.from('.tec-lab-card', {
      opacity: 0, y: 50, duration: 0.8, stagger: 0.15, ease: 'power3.out',
      scrollTrigger: { trigger: '.tec-labs-grid', start: 'top 80%' },
    })

    // Stats — counter + reveal
    document.querySelectorAll('.tec-stat').forEach((el) => {
      const numberEl = el.querySelector('.tec-stat-num')
      const end = parseInt(numberEl.dataset.end)
      const suffix = numberEl.dataset.suffix || ''
      gsap.from(el, {
        opacity: 0, y: 40, scale: 0.9, duration: 0.7, ease: 'back.out(1.4)',
        scrollTrigger: {
          trigger: el,
          start: 'top 88%',
          onEnter: () => {
            const obj = { val: 0 }
            gsap.to(obj, {
              val: end,
              duration: 1.5,
              ease: 'power2.out',
              onUpdate: () => { numberEl.textContent = Math.round(obj.val) + suffix },
            })
          },
        },
      })
    })

  }, sectionRef.value)
})

onUnmounted(() => {
  ctx?.revert()
  if (intervalId) clearInterval(intervalId)
})
</script>

<template>
  <section
    id="tecnologia"
    ref="sectionRef"
    class="py-32 relative overflow-hidden"
    style="background: linear-gradient(180deg, hsl(var(--background)) 0%, color-mix(in srgb, #3a56a8 4%, transparent) 50%, hsl(var(--background)) 100%);"
  >
    <!-- Fondo -->
    <div class="absolute inset-0" aria-hidden="true">
      <div
        class="absolute top-0 right-0 w-[600px] h-[600px] rounded-full"
        style="background: radial-gradient(circle, color-mix(in srgb, #3a56a8 6%, transparent), transparent 70%); filter: blur(80px);"
      />
      <div class="absolute inset-0 cyd-dots opacity-20" />
    </div>

    <div class="relative cyd-container">

      <!-- ── Header ── -->
      <div class="tec-header text-center max-w-2xl mx-auto mb-24">
        <span class="cyd-label mb-5 inline-block">Innovación Educativa</span>
        <h2 class="cyd-title mb-5">
          Tecnología de <span class="cyd-accent">Vanguardia</span>
        </h2>
        <p class="text-base lg:text-lg" style="color: hsl(var(--muted-foreground));">
          Preparamos a nuestros estudiantes con herramientas digitales de primer nivel
          en Baja Verapaz.
        </p>
      </div>

      <!-- ── Laboratorio MAC ── -->
      <div class="tec-lab-section mb-28">
        <div class="tec-lab-wrap grid lg:grid-cols-3 gap-6">

          <!-- Carousel -->
          <div class="tec-carousel-wrap lg:col-span-2 space-y-4">
            <!-- Imagen principal -->
            <div
              ref="carouselRef"
              class="relative rounded-2xl overflow-hidden bg-black"
              style="height: 420px; box-shadow: 0 30px 80px rgba(0,0,0,0.18);"
            >
              <!-- Slides -->
              <div
                v-for="(img, i) in macLabImages"
                :key="i"
                class="absolute inset-0 transition-all duration-1000"
                :style="{ opacity: i === currentIndex ? 1 : 0, zIndex: i === currentIndex ? 1 : 0 }"
              >
                <img
                  :src="img.url"
                  :alt="img.caption"
                  class="w-full h-full object-cover"
                  :style="{ transform: i === currentIndex ? 'scale(1)' : 'scale(1.08)', transition: 'transform 1.2s ease' }"
                />
                <!-- Overlay gradiente -->
                <div class="absolute inset-0" style="background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.1) 50%, transparent 100%);" />
              </div>

              <!-- Badge superior -->
              <div class="absolute top-5 left-5 z-10">
                <div
                  class="flex items-center gap-2 px-4 py-2 rounded-xl backdrop-blur-xl border border-white/20"
                  style="background: rgba(255,255,255,0.12);"
                >
                  <!-- Monitor SVG -->
                  <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true" class="text-white">
                    <rect x="1" y="2" width="14" height="9" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                    <path d="M5 14h6M8 11v3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                  </svg>
                  <span class="text-white text-sm font-semibold">Laboratorio MAC</span>
                </div>
              </div>

              <!-- Controles de navegación -->
              <div class="absolute bottom-5 left-5 right-5 z-10 flex items-center gap-3">
                <button
                  @click="prev"
                  class="w-9 h-9 rounded-lg flex items-center justify-center backdrop-blur-xl border border-white/20 text-white transition-all duration-200 hover:bg-white/25"
                  style="background: rgba(255,255,255,0.12);"
                  aria-label="Anterior"
                >
                  <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                    <path d="M9 2L5 7l4 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </button>

                <!-- Caption -->
                <div
                  class="flex-1 rounded-xl px-4 py-2 backdrop-blur-xl border border-white/15"
                  style="background: rgba(255,255,255,0.1);"
                >
                  <p class="text-white text-xs font-medium truncate">
                    {{ macLabImages[currentIndex].caption }}
                  </p>
                </div>

                <button
                  @click="next"
                  class="w-9 h-9 rounded-lg flex items-center justify-center backdrop-blur-xl border border-white/20 text-white transition-all duration-200 hover:bg-white/25"
                  style="background: rgba(255,255,255,0.12);"
                  aria-label="Siguiente"
                >
                  <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                    <path d="M5 2l4 5-4 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </button>
              </div>

              <!-- Dots -->
              <div class="absolute bottom-16 left-1/2 -translate-x-1/2 z-10 flex gap-1.5">
                <button
                  v-for="(_, i) in macLabImages"
                  :key="i"
                  @click="goTo(i)"
                  class="rounded-full transition-all duration-300"
                  :style="{
                    width: i === currentIndex ? '20px' : '6px',
                    height: '6px',
                    background: i === currentIndex ? 'white' : 'rgba(255,255,255,0.4)',
                  }"
                  :aria-label="`Ir a imagen ${i + 1}`"
                />
              </div>
            </div>

            <!-- Feature pills -->
            <div class="grid grid-cols-3 gap-3">
              <div
                v-for="(f, i) in [
                  { label: 'Internet Fibra Óptica', accent: 'var(--cyd-gold)' },
                  { label: 'Diseño Gráfico Pro', accent: '#7c4fba' },
                  { label: 'Educación con IA', accent: '#3a56a8' },
                ]"
                :key="i"
                class="rounded-xl px-4 py-3 text-center text-xs font-semibold border transition-all duration-300 hover:-translate-y-1"
                :style="{
                  color: f.accent,
                  borderColor: `color-mix(in srgb, ${f.accent} 25%, transparent)`,
                  background: `color-mix(in srgb, ${f.accent} 6%, white)`,
                }"
              >
                {{ f.label }}
              </div>
            </div>
          </div>

          <!-- Info lateral -->
          <div class="tec-info-col flex flex-col gap-4">
            <!-- Card principal -->
            <div
              class="rounded-2xl p-7 text-white flex-1 flex flex-col justify-between"
              style="background: linear-gradient(135deg, #3a56a8 0%, #5472cc 100%); box-shadow: 0 20px 50px color-mix(in srgb, #3a56a8 30%, transparent);"
            >
              <!-- Ícono de monitor -->
              <div
                class="w-12 h-12 rounded-xl flex items-center justify-center mb-4"
                style="background: rgba(255,255,255,0.15);"
              >
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none" aria-hidden="true">
                  <rect x="2" y="3" width="18" height="12" rx="2" stroke="white" stroke-width="1.6"/>
                  <path d="M7 19h8M11 15v4" stroke="white" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
              </div>
              <div>
                <h3 class="text-xl font-black mb-2 leading-tight" style="font-family: var(--font-display);">
                  Laboratorio de Computación MAC
                </h3>
                <p class="text-white/80 text-sm leading-relaxed">
                  Más de 50 iMacs de última generación para el aprendizaje del futuro.
                </p>
              </div>
            </div>

            <!-- Métricas -->
            <div class="rounded-2xl p-6 border cyd-card space-y-5">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="text-xs font-semibold uppercase tracking-wider" style="color: hsl(var(--muted-foreground));">Equipos iMac</span>
                  <span class="text-2xl font-black" style="color: #3a56a8; font-family: var(--font-display);">50+</span>
                </div>
                <div class="h-1.5 rounded-full overflow-hidden" style="background: hsl(var(--muted)) ;">
                  <div class="h-full rounded-full" style="width: 90%; background: linear-gradient(90deg, #3a56a8, #6080e0);" />
                </div>
              </div>

              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="text-xs font-semibold uppercase tracking-wider" style="color: hsl(var(--muted-foreground));">Software</span>
                  <span class="text-xl font-black" style="color: #7c4fba; font-family: var(--font-display);">Pro</span>
                </div>
                <div class="h-1.5 rounded-full overflow-hidden" style="background: hsl(var(--muted));">
                  <div class="h-full rounded-full" style="width: 100%; background: linear-gradient(90deg, #7c4fba, #a060e0);" />
                </div>
              </div>

              <div>
                <div class="flex items-center justify-between mb-2">
                  <span class="text-xs font-semibold uppercase tracking-wider" style="color: hsl(var(--muted-foreground));">Conectividad</span>
                  <span class="text-xl font-black" style="color: var(--cyd-forest); font-family: var(--font-display);">Fibra</span>
                </div>
                <div class="h-1.5 rounded-full overflow-hidden" style="background: hsl(var(--muted));">
                  <div class="h-full rounded-full" style="width: 100%; background: linear-gradient(90deg, var(--cyd-forest), var(--cyd-green));" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── APP CYD ── -->
      <div class="tec-app-section mb-28 rounded-2xl overflow-hidden relative"
        style="background: linear-gradient(135deg, #2c1a6e 0%, #4a2da8 40%, #7c3fa8 100%);"
      >
        <div class="absolute inset-0 cyd-dots opacity-[0.06]" style="background-image: radial-gradient(circle, rgba(255,255,255,0.3) 1px, transparent 1px);" aria-hidden="true" />

        <div class="relative grid lg:grid-cols-2 gap-0 items-center">

          <!-- Izquierda -->
          <div class="tec-app-left p-6 sm:p-10 lg:p-14">
            <div class="text-xs font-semibold tracking-[0.2em] uppercase text-white/50 mb-3">Nueva Aplicación Móvil</div>
            <h3
              class="text-4xl sm:text-5xl lg:text-6xl font-black text-white mb-4 leading-none"
              style="font-family: var(--font-display); letter-spacing: -0.04em;"
            >
              APP CYD
            </h3>

            <div class="flex flex-wrap gap-2 mb-6">
              <span class="text-xs font-semibold px-3 py-1.5 rounded-full text-white" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);">iOS</span>
              <span class="text-xs font-semibold px-3 py-1.5 rounded-full text-white" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2);">Android</span>
              <span class="text-xs font-semibold px-3 py-1.5 rounded-full" style="background: rgba(255,255,255,0.95); color: #4a2da8;">Gratis</span>
            </div>

            <p class="text-white/80 text-sm sm:text-base leading-relaxed mb-8">
              Tu colegio en tu bolsillo. Calificaciones, horarios, noticias y más desde tu dispositivo móvil.
            </p>

            <!-- Features grid -->
            <div class="grid grid-cols-2 gap-3 mb-8">
              <div
                v-for="f in [
                  { label: 'Calificaciones', desc: 'Consulta notas al instante' },
                  { label: 'Tareas', desc: 'Gestiona tus actividades' },
                  { label: 'Calendario', desc: 'No te pierdas eventos' },
                  { label: 'Horarios', desc: 'Consulta tus clases' },
                ]"
                :key="f.label"
                class="rounded-xl p-4 border border-white/10 hover:border-white/25 hover:bg-white/10 transition-all duration-200 cursor-default"
                style="background: rgba(255,255,255,0.06);"
              >
                <div class="text-xs font-bold text-white mb-0.5">{{ f.label }}</div>
                <div class="text-xs text-white/60">{{ f.desc }}</div>
              </div>
            </div>

            <!-- Download buttons -->
            <div class="flex flex-col sm:flex-row gap-3">
              <button
                @click="() => window.open('https://apps.apple.com/gt/app/colegiocyd/id1555398289', '_blank')"
                class="flex items-center gap-3 px-5 py-3.5 rounded-xl font-semibold text-sm transition-all duration-300 hover:-translate-y-1"
                style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); color: white;"
                @mouseenter="(e) => e.currentTarget.style.background = 'rgba(255,255,255,0.22)'"
                @mouseleave="(e) => e.currentTarget.style.background = 'rgba(255,255,255,0.12)'"
              >
                <!-- Apple SVG -->
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                  <path d="M14.5 10.5c0-1.7 1-3.1 2.5-3.8-.9-1.3-2.3-2.1-3.9-2.2-1.5-.1-3 .9-3.8.9-.7 0-1.9-.9-3.1-.8-1.6.1-3 .9-3.8 2.3-1.6 2.8-.4 7 1.1 9.3.8 1.1 1.6 2.3 2.8 2.3 1.1 0 1.5-.7 2.9-.7 1.3 0 1.7.7 2.9.7 1.2 0 1.9-1.1 2.7-2.3.5-.8.9-1.6 1.1-2.5-1.5-.6-2.4-2-2.4-3.2z" fill="white"/>
                  <path d="M12.5 4.5c.7-.8 1.1-1.9.9-3-1 .1-2 .6-2.7 1.4-.6.7-1.1 1.8-.9 2.9 1.1.1 2-.4 2.7-1.3z" fill="white"/>
                </svg>
                <div class="text-left">
                  <div class="text-[10px] opacity-70">Descargar en</div>
                  <div class="font-bold text-sm">App Store</div>
                </div>
              </button>

              <button
                @click="() => window.open('https://play.google.com/store/apps/details?id=gt.com.cyd.colegio_cyd&hl=es_GT&pli=1', '_blank')"
                class="flex items-center gap-3 px-5 py-3.5 rounded-xl font-semibold text-sm transition-all duration-300 hover:-translate-y-1"
                style="background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); color: white;"
                @mouseenter="(e) => e.currentTarget.style.background = 'rgba(255,255,255,0.22)'"
                @mouseleave="(e) => e.currentTarget.style.background = 'rgba(255,255,255,0.12)'"
              >
                <!-- Google Play SVG -->
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                  <path d="M3 2.5l8 7.5-8 7.5V2.5z" fill="rgba(255,255,255,0.6)"/>
                  <path d="M3 2.5L12.5 7 10 9.5 3 2.5z" fill="rgba(255,255,255,0.9)"/>
                  <path d="M3 17.5L10 10.5l2.5 2.5L3 17.5z" fill="rgba(255,255,255,0.9)"/>
                  <path d="M12.5 7l4 2.5-4 2.5L10 9.5 12.5 7z" fill="white"/>
                </svg>
                <div class="text-left">
                  <div class="text-[10px] opacity-70">Disponible en</div>
                  <div class="font-bold text-sm">Google Play</div>
                </div>
              </button>
            </div>
          </div>

          <!-- Teléfono -->
          <div class="flex justify-center items-center p-10 lg:p-14">
            <div class="tec-phone-wrap relative will-change-transform">
              <!-- Halo de fondo -->
              <div
                class="absolute inset-[-40px] rounded-full"
                style="background: radial-gradient(circle, rgba(255,255,255,0.12), transparent 70%); filter: blur(20px);"
                aria-hidden="true"
              />
              <!-- Mockup de teléfono -->
              <div
                class="relative rounded-[3rem] overflow-hidden"
                style="width: 220px; background: #0a0a0a; padding: 12px; box-shadow: 0 40px 100px rgba(0,0,0,0.5), inset 0 0 0 1px rgba(255,255,255,0.1);"
              >
                <!-- Notch -->
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-28 h-6 rounded-b-2xl z-10" style="background: #0a0a0a;" />
                <!-- Pantalla -->
                <div class="rounded-[2.2rem] overflow-hidden" style="aspect-ratio: 9/19;">
                  <img
                    src="https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/App1__-2-1761865863492.png?width=800&height=800&resize=contain"
                    alt="APP CYD Screenshot"
                    class="w-full h-full object-cover"
                  />
                </div>
                <!-- Botón lateral -->
                <div class="absolute right-0 top-24 w-[3px] h-12 rounded-l-full" style="background: #222;" />
                <div class="absolute right-0 top-40 w-[3px] h-16 rounded-l-full" style="background: #222;" />
                <div class="absolute left-0 top-32 w-[3px] h-8 rounded-r-full" style="background: #222;" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Laboratorios especializados ── -->
      <div class="mb-20">
        <div class="text-center mb-14">
          <span class="cyd-label mb-4 inline-block">Infraestructura</span>
          <h3
            class="text-3xl font-bold"
            style="font-family: var(--font-display); letter-spacing: -0.025em; color: var(--cyd-dark);"
          >
            Laboratorios <span class="cyd-accent">Especializados</span>
          </h3>
        </div>

        <div class="tec-labs-grid grid lg:grid-cols-2 gap-6">
          <div
            v-for="lab in laboratorios"
            :key="lab.title"
            class="tec-lab-card group relative rounded-2xl overflow-hidden border border-green-100 bg-white will-change-transform hover:-translate-y-2 transition-transform duration-400"
            style="box-shadow: 0 4px 20px rgba(0,0,0,0.06);"
          >
            <!-- Imagen -->
            <div class="relative h-56 overflow-hidden">
              <img
                :src="lab.image"
                :alt="lab.title"
                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
              />
              <div class="absolute inset-0" style="background: linear-gradient(to top, rgba(0,0,0,0.65), rgba(0,0,0,0.05));" />
              <div class="absolute bottom-0 left-0 right-0 p-5">
                <h3 class="text-xl font-black text-white" style="font-family: var(--font-display);">
                  {{ lab.title }}
                </h3>
              </div>
              <!-- Barra de acento top -->
              <div class="absolute top-0 left-0 right-0 h-0.5" :style="{ background: lab.accent }" />
            </div>

            <!-- Contenido -->
            <div class="p-6">
              <p class="text-sm leading-relaxed mb-5" style="color: hsl(var(--muted-foreground));">
                {{ lab.description }}
              </p>
              <ul class="space-y-2.5">
                <li
                  v-for="f in lab.features"
                  :key="f"
                  class="flex items-start gap-2.5 text-sm"
                  style="color: hsl(var(--foreground));"
                >
                  <!-- Check SVG propio -->
                  <span
                    class="shrink-0 w-5 h-5 rounded-lg flex items-center justify-center mt-0.5"
                    :style="{ background: `color-mix(in srgb, ${lab.accent} 12%, transparent)` }"
                  >
                    <svg width="10" height="10" viewBox="0 0 10 10" fill="none" aria-hidden="true">
                      <path d="M2 5l2 2 4-4" :stroke="lab.accent" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </span>
                  {{ f }}
                </li>
              </ul>

              <!-- Línea de progreso al hover -->
              <div class="mt-5 h-[2px] rounded-full overflow-hidden" style="background: hsl(var(--muted));">
                <div
                  class="h-full rounded-full transition-all duration-700 group-hover:w-full"
                  style="width: 0%;"
                  :style="{ background: lab.accent }"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Stats finales ── -->
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div
          v-for="(stat, i) in stats"
          :key="i"
          class="tec-stat cyd-card p-8 text-center will-change-transform"
        >
          <div
            class="tec-stat-num text-5xl font-black mb-2 leading-none"
            :data-end="stat.value"
            :data-suffix="stat.suffix"
            :style="{ color: stat.accent, fontFamily: 'var(--font-display)', letterSpacing: '-0.04em' }"
          >0</div>
          <div class="text-sm font-medium" style="color: hsl(var(--muted-foreground));">{{ stat.label }}</div>
          <div
            class="cyd-divider mx-auto mt-4"
            :style="{ background: `linear-gradient(90deg, ${stat.accent}, color-mix(in srgb, ${stat.accent} 50%, var(--cyd-gold)))` }"
          />
        </div>
      </div>

    </div>
  </section>
</template>
