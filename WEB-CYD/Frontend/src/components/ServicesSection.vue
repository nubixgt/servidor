<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { gsap, ScrollTrigger } from '@/lib/gsap.js'

const sectionRef = ref(null)
const headerRef = ref(null)

let ctx = null

const niveles = [
  {
    key: 'preprimaria',
    title: 'Pre\u00adprimaria',
    edad: '4 \u2013 6 años',
    description: 'Educación inicial con metodologías lúdicas que estimulan el desarrollo integral de los más pequeños.',
    features: ['Estimulación temprana', 'Desarrollo psicomotriz', 'Iniciación a la lectura', 'Actividades artísticas'],
    accent: '#e8856a',
    light: '#fef5f1',
    number: '01',
  },
  {
    key: 'primaria',
    title: 'Primaria',
    edad: '7 \u2013 12 años',
    description: 'Formación académica sólida con énfasis en valores, ciencia y tecnología para construir bases fuertes.',
    features: ['Matemáticas', 'Ciencias Naturales', 'Idioma Español', 'Inglés · Computación'],
    accent: 'var(--cyd-green)',
    light: '#f0f7f1',
    number: '02',
  },
  {
    key: 'basicos',
    title: 'Básicos',
    edad: '13 \u2013 15 años',
    description: 'Educación integral preparando estudiantes con pensamiento crítico y habilidades para el futuro.',
    features: ['Ciencias', 'Matemática avanzada', 'Estudios sociales', 'Inglés avanzado'],
    accent: '#4d6cc4',
    light: '#f0f3fc',
    number: '03',
  },
  {
    key: 'diversificado',
    title: 'Diversificado',
    edad: '16 \u2013 18 años',
    description: 'Preparación universitaria con carreras especializadas para el éxito académico y profesional.',
    features: ['Bachillerato en Ciencias', 'Perito Contador', 'Prácticas profesionales', 'Orientación universitaria'],
    accent: 'var(--cyd-gold)',
    light: '#fdfaf0',
    number: '04',
  },
]

const materias = [
  'Matemáticas', 'Ciencias Naturales', 'Estudios Sociales',
  'Comunicación y Lenguaje', 'Inglés', 'Computación',
  'Educación Física', 'Artes', 'Música', 'Valores',
]

const handleWhatsAppClick = (nivel) => {
  const msg = `Hola, necesito más información sobre ${nivel}.`
  window.open(`https://api.whatsapp.com/send?phone=50257001515&text=${encodeURIComponent(msg)}`, '_blank')
}

onMounted(() => {
  ctx = gsap.context(() => {

    // Animación del header al entrar
    gsap.from(headerRef.value, {
      opacity: 0,
      y: 50,
      duration: 0.9,
      ease: 'power3.out',
      scrollTrigger: {
        trigger: headerRef.value,
        start: 'top 80%',
      },
    })

    // Cards — stagger al entrar
    gsap.from('.nivel-card', {
      opacity: 0,
      y: 60,
      scale: 0.95,
      duration: 0.8,
      stagger: 0.15,
      ease: 'power3.out',
      scrollTrigger: {
        trigger: '.niveles-grid',
        start: 'top 80%',
      },
    })

    // Materias — aparecen en cascada y luego flotan suavemente
    gsap.from('.materia-pill', {
      opacity: 0,
      scale: 0.8,
      duration: 0.4,
      stagger: 0.05,
      ease: 'back.out(1.6)',
      scrollTrigger: {
        trigger: '.materias-row',
        start: 'top 85%',
        onEnter: () => {
          // Iniciar animación flotante después de aparecer
          gsap.to('.materia-pill', {
            y: -6,
            duration: 1.5,
            stagger: {
              each: 0.1,
              repeat: -1,
              yoyo: true
            },
            ease: 'sine.inOut',
            delay: 0.5
          })
        }
      },
    })

    // Hover interactivo para cards de niveles
    const cards = document.querySelectorAll('.nivel-card')
    cards.forEach((card) => {
      card.addEventListener('mouseenter', () => {
        gsap.to(card, { y: -8, scale: 1.02, duration: 0.4, ease: 'power2.out' })
      })
      card.addEventListener('mouseleave', () => {
        gsap.to(card, { y: 0, scale: 1, duration: 0.5, ease: 'elastic.out(1, 0.5)' })
      })
    })

    // Hover interactivo para materias
    const pills = document.querySelectorAll('.materia-pill')
    pills.forEach((pill) => {
      pill.addEventListener('mouseenter', () => {
        gsap.to(pill, { scale: 1.1, rotation: Math.random() * 4 - 2, duration: 0.3, ease: 'back.out(2)', overwrite: 'auto' })
      })
      pill.addEventListener('mouseleave', () => {
        gsap.to(pill, { scale: 1, rotation: 0, duration: 0.4, ease: 'power2.out', overwrite: 'auto' })
      })
    })

  }, sectionRef.value)
})

onUnmounted(() => {
  ctx?.revert()
})
</script>

<template>
  <section
    id="niveles"
    ref="sectionRef"
    class="py-16 lg:py-32 relative overflow-hidden cyd-section-bg"
  >
    <!-- Decoración de fondo -->
    <div class="absolute inset-0" aria-hidden="true">
      <div
        class="absolute top-0 right-0 w-[500px] h-[500px] rounded-full"
        style="background: radial-gradient(circle, color-mix(in srgb, var(--cyd-green) 6%, transparent), transparent 70%); filter: blur(60px);"
      />
      <div
        class="absolute bottom-0 left-0 w-[400px] h-[400px] rounded-full"
        style="background: radial-gradient(circle, color-mix(in srgb, var(--cyd-gold) 6%, transparent), transparent 70%); filter: blur(60px);"
      />
    </div>

    <div class="relative cyd-container">

      <!-- Header -->
      <div ref="headerRef" class="text-center mb-12 lg:mb-20 max-w-2xl mx-auto">
        <span class="cyd-label mb-5 inline-block">Niveles Educativos</span>
        <h2 class="cyd-title mb-5">
          Nuestros <span class="cyd-accent">Niveles</span>
        </h2>
        <p class="text-base lg:text-lg" style="color: hsl(var(--muted-foreground));">
          Educación integral desde preprimaria hasta diversificado,
          formando estudiantes preparados para la vida.
        </p>
      </div>

      <!-- Grid de niveles -->
      <div class="niveles-grid grid md:grid-cols-2 gap-4 sm:gap-6 mb-12 lg:mb-20">
        <article
          v-for="nivel in niveles"
          :key="nivel.key"
          class="nivel-card group relative rounded-2xl overflow-hidden border bg-white will-change-transform"
          :style="{
            borderColor: `color-mix(in srgb, ${nivel.accent} 20%, transparent)`,
          }"
        >
          <!-- Barra de acento superior -->
          <div
            class="h-1 w-full"
            :style="{ background: nivel.accent }"
          />

          <div class="p-6 sm:p-8">
            <!-- Número + edad -->
            <div class="flex items-start justify-between mb-6">
              <span
                class="text-6xl font-black leading-none select-none"
                :style="{ color: `color-mix(in srgb, ${nivel.accent} 12%, transparent)` }"
                style="font-family: var(--font-display);"
              >
                {{ nivel.number }}
              </span>
              <span
                class="text-xs font-semibold tracking-wider uppercase px-3 py-1.5 rounded-full"
                :style="{
                  background: `color-mix(in srgb, ${nivel.accent} 12%, transparent)`,
                  color: nivel.accent,
                }"
              >
                {{ nivel.edad }}
              </span>
            </div>

            <!-- Título -->
            <h3
              class="text-2xl font-bold mb-3 transition-colors duration-300"
              style="font-family: var(--font-display); letter-spacing: -0.025em; color: var(--cyd-dark);"
            >
              {{ nivel.title }}
            </h3>

            <!-- Descripción -->
            <p class="text-sm leading-relaxed mb-6" style="color: hsl(var(--muted-foreground));">
              {{ nivel.description }}
            </p>

            <!-- Features -->
            <ul class="space-y-2 mb-8">
              <li
                v-for="(feature, idx) in nivel.features"
                :key="idx"
                class="flex items-center gap-3 text-sm"
                style="color: hsl(var(--foreground));"
              >
                <span
                  class="w-1.5 h-1.5 rounded-full shrink-0"
                  :style="{ background: nivel.accent }"
                />
                {{ feature }}
              </li>
            </ul>

            <!-- CTA -->
            <button
              @click="handleWhatsAppClick(nivel.title)"
              class="w-full py-3 px-6 rounded-xl text-sm font-semibold transition-all duration-300 border"
              :style="{
                color: nivel.accent,
                borderColor: `color-mix(in srgb, ${nivel.accent} 30%, transparent)`,
                background: `color-mix(in srgb, ${nivel.accent} 0%, transparent)`,
              }"
              @mouseenter="(e) => {
                e.target.style.background = nivel.accent
                e.target.style.color = '#fff'
                e.target.style.borderColor = nivel.accent
              }"
              @mouseleave="(e) => {
                e.target.style.background = 'transparent'
                e.target.style.color = nivel.accent
                e.target.style.borderColor = `color-mix(in srgb, ${nivel.accent} 30%, transparent)`
              }"
            >
              Más información
            </button>
          </div>

          <!-- Hover glow -->
          <div
            class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none rounded-2xl"
            :style="{
              boxShadow: `inset 0 0 60px color-mix(in srgb, ${nivel.accent} 6%, transparent)`,
            }"
          />
        </article>
      </div>

      <!-- Áreas de estudio -->
      <div class="text-center">
        <span class="cyd-label mb-6 inline-block">Áreas de Estudio</span>
        <div class="materias-row flex flex-wrap justify-center gap-2.5">
          <span
            v-for="(materia, index) in materias"
            :key="index"
            class="materia-pill cyd-pill will-change-transform"
          >
            {{ materia }}
          </span>
        </div>
      </div>
    </div>
  </section>
</template>
