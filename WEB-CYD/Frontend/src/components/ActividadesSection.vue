<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { gsap } from '@/lib/gsap.js'

const sectionRef = ref(null)
let ctx = null

// SVGs propios para cada actividad — sin Lucide, sin emojis
const actividades = [
  {
    key: 'deporte',
    title: 'Deportes',
    description: 'Fútbol, baloncesto, voleibol y atletismo con entrenadores profesionales.',
    accent: '#c94a1a',
    bg: '#fff5f2',
    items: ['Fútbol', 'Baloncesto', 'Voleibol', 'Atletismo'],
    // SVG trofeo dibujado a mano
    svg: `<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M9 4h10v8a5 5 0 01-10 0V4z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
      <path d="M9 8H5a3 3 0 003 3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
      <path d="M19 8h4a3 3 0 01-3 3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
      <path d="M14 17v4M10 24h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
    </svg>`,
  },
  {
    key: 'musica',
    title: 'Música',
    description: 'Marimba, piano, guitarra y coro con maestros especializados.',
    accent: '#7a2eb8',
    bg: '#f9f4ff',
    items: ['Marimba', 'Piano', 'Guitarra', 'Coro'],
    svg: `<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M10 20V6l14-3v14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      <circle cx="7" cy="20" r="3" stroke="currentColor" stroke-width="1.8"/>
      <circle cx="21" cy="17" r="3" stroke="currentColor" stroke-width="1.8"/>
    </svg>`,
  },
  {
    key: 'arte',
    title: 'Arte',
    description: 'Pintura, dibujo, escultura y arte digital para desarrollar tu creatividad.',
    accent: '#1860b8',
    bg: '#f0f4ff',
    items: ['Pintura', 'Dibujo', 'Escultura', 'Arte digital'],
    svg: `<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
      <circle cx="14" cy="14" r="10" stroke="currentColor" stroke-width="1.8"/>
      <path d="M8 14c0-3 3-6 6-6s6 2.5 6 5c0 2-1.5 3-3 3s-3-1-3-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
      <circle cx="19" cy="20" r="2" stroke="currentColor" stroke-width="1.5"/>
    </svg>`,
  },
  {
    key: 'ciencia',
    title: 'Ciencia',
    description: 'Laboratorios, robótica y experimentos científicos de vanguardia.',
    accent: '#1a7a5a',
    bg: '#eef7f3',
    items: ['Robótica', 'Experimentos', 'Feria científica', 'Club de ciencias'],
    svg: `<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M10 4v8l-5 9h18l-5-9V4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      <path d="M9 4h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
      <circle cx="13" cy="17" r="1.5" fill="currentColor"/>
      <circle cx="17" cy="19" r="1" fill="currentColor"/>
    </svg>`,
  },
  {
    key: 'teatro',
    title: 'Teatro',
    description: 'Expresión artística, oratoria y desarrollo de habilidades escénicas.',
    accent: '#9a7200',
    bg: '#fefaea',
    items: ['Teatro', 'Oratoria', 'Declamación', 'Presentaciones'],
    svg: `<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M4 22h20M4 22l3-10h14l3 10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
      <circle cx="10" cy="9" r="3" stroke="currentColor" stroke-width="1.8"/>
      <circle cx="18" cy="9" r="3" stroke="currentColor" stroke-width="1.8"/>
      <path d="M10 12c0 2.5 2 4 4 4s4-1.5 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
    </svg>`,
  },
  {
    key: 'idiomas',
    title: 'Idiomas',
    description: 'Inglés avanzado, conversación y certificaciones internacionales.',
    accent: '#1a56a8',
    bg: '#eef3ff',
    items: ['Inglés avanzado', 'Conversación', 'Certificaciones'],
    svg: `<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
      <circle cx="14" cy="14" r="10" stroke="currentColor" stroke-width="1.8"/>
      <ellipse cx="14" cy="14" rx="4" ry="10" stroke="currentColor" stroke-width="1.4"/>
      <path d="M4 14h20M5 9h18M5 19h18" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
    </svg>`,
  },
  {
    key: 'liderazgo',
    title: 'Liderazgo',
    description: 'Gobierno estudiantil, proyectos sociales y voluntariado comunitario.',
    accent: '#0e8888',
    bg: '#edfafa',
    items: ['Gobierno estudiantil', 'Proyectos sociales', 'Voluntariado'],
    svg: `<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
      <circle cx="14" cy="8" r="3.5" stroke="currentColor" stroke-width="1.8"/>
      <circle cx="6" cy="20" r="2.5" stroke="currentColor" stroke-width="1.5"/>
      <circle cx="22" cy="20" r="2.5" stroke="currentColor" stroke-width="1.5"/>
      <path d="M14 12v4M14 16l-6 3M14 16l6 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
    </svg>`,
  },
  {
    key: 'salud',
    title: 'Bienestar',
    description: 'Gimnasia, yoga, nutrición y actividades para una vida saludable.',
    accent: '#b8304a',
    bg: '#fff2f4',
    items: ['Gimnasia', 'Yoga', 'Nutrición', 'Vida saludable'],
    svg: `<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M14 23s-9-5.5-9-11a5 5 0 0110 0 5 5 0 0110 0c0 5.5-11 11-11 11z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
    </svg>`,
  },
]

const activeCard = ref(null)

const handleCardEnter = (i, el) => {
  activeCard.value = i
  gsap.to(el, { scale: 1.04, y: -6, duration: 0.35, ease: 'power2.out' })
}
const handleCardLeave = (i, el) => {
  if (activeCard.value === i) activeCard.value = null
  gsap.to(el, { scale: 1, y: 0, duration: 0.5, ease: 'elastic.out(1, 0.5)' })
}

onMounted(() => {
  ctx = gsap.context(() => {
    // Header
    gsap.from('.act-header', {
      opacity: 0, y: 60, duration: 1, ease: 'power3.out',
      scrollTrigger: { trigger: '.act-header', start: 'top 80%' },
    })

    // Cards — aparecen en grupos de 4 con stagger
    gsap.from('.act-card', {
      opacity: 0,
      y: 50,
      scale: 0.95,
      duration: 0.7,
      stagger: { amount: 0.8, from: 'start' },
      ease: 'power3.out',
      scrollTrigger: { trigger: '.act-grid', start: 'top 80%' },
    })

    // CTA
    gsap.from('.act-cta', {
      opacity: 0, y: 40, duration: 0.8, ease: 'power3.out',
      scrollTrigger: { trigger: '.act-cta', start: 'top 88%' },
    })

    // Número de actividades contando
    const numEl = document.querySelector('.act-count')
    if (numEl) {
      const obj = { val: 0 }
      gsap.to(obj, {
        val: 8,
        duration: 1.5,
        ease: 'power2.out',
        scrollTrigger: { trigger: numEl, start: 'top 90%' },
        onUpdate: () => { numEl.textContent = Math.round(obj.val) },
      })
    }
  }, sectionRef.value)
})

onUnmounted(() => { ctx?.revert() })
</script>

<template>
  <section
    id="actividades"
    ref="sectionRef"
    class="py-16 lg:py-32 relative overflow-hidden cyd-section-bg"
  >
    <!-- Fondo decorativo -->
    <div class="absolute inset-0" aria-hidden="true">
      <div
        class="absolute top-0 right-0 w-[500px] h-[500px] rounded-full"
        style="background: radial-gradient(circle, color-mix(in srgb, #7a2eb8 5%, transparent), transparent 70%); filter: blur(80px);"
      />
      <div class="absolute inset-0 cyd-dots opacity-20" />
    </div>

    <div class="relative cyd-container">

      <!-- Header -->
      <div class="act-header text-center max-w-2xl mx-auto mb-12 lg:mb-20">
        <span class="cyd-label mb-5 inline-block">Vida Escolar</span>
        <h2 class="cyd-title mb-5">
          Actividades <span class="cyd-accent">Extracurriculares</span>
        </h2>
        <p class="text-base lg:text-lg" style="color: hsl(var(--muted-foreground));">
          Descubre tu pasión. <span class="act-count font-black" style="color: var(--cyd-forest);">0</span> disciplinas diseñadas para desarrollar líderes completos.
        </p>
      </div>

      <!-- Grid de actividades -->
      <div class="act-grid grid sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-12 lg:mb-20">
        <div
          v-for="(act, i) in actividades"
          :key="act.key"
          class="act-card relative rounded-2xl border p-6 cursor-default will-change-transform overflow-hidden transition-shadow duration-300"
          :style="{
            background: act.bg,
            borderColor: `color-mix(in srgb, ${act.accent} 18%, transparent)`,
          }"
          @mouseenter="handleCardEnter(i, $event.currentTarget)"
          @mouseleave="handleCardLeave(i, $event.currentTarget)"
        >
          <!-- Icono propio SVG -->
          <div
            class="w-12 h-12 rounded-xl flex items-center justify-center mb-5 transition-transform duration-300"
            :style="{ background: `color-mix(in srgb, ${act.accent} 12%, transparent)`, color: act.accent }"
            v-html="act.svg"
          />

          <h3
            class="text-base font-black mb-2"
            style="font-family: var(--font-display); letter-spacing: -0.02em; color: var(--cyd-dark);"
          >
            {{ act.title }}
          </h3>
          <p class="text-xs leading-relaxed mb-4" style="color: hsl(var(--muted-foreground));">
            {{ act.description }}
          </p>

          <!-- Items -->
          <ul class="space-y-1.5">
            <li
              v-for="item in act.items"
              :key="item"
              class="flex items-center gap-2 text-xs font-medium"
              :style="{ color: act.accent }"
            >
              <span class="w-1.5 h-1.5 rounded-full shrink-0" :style="{ background: act.accent }" />
              {{ item }}
            </li>
          </ul>

          <!-- Acento de esquina -->
          <div
            class="absolute -bottom-4 -right-4 w-20 h-20 rounded-full opacity-10 transition-opacity duration-300"
            :class="activeCard === i ? 'opacity-20' : ''"
            :style="{ background: act.accent }"
            aria-hidden="true"
          />
        </div>
      </div>

      <!-- CTA strip horizontal premium -->
      <div class="act-cta rounded-2xl p-6 sm:p-8 lg:p-10 border will-change-transform" style="background: hsl(var(--background)); border-color: color-mix(in srgb, var(--cyd-green) 20%, transparent);">
        <div class="grid md:grid-cols-3 gap-6 items-center">
          <div class="md:col-span-2">
            <div class="text-xs font-semibold tracking-[0.18em] uppercase mb-2" style="color: var(--cyd-green);">
              Inscripciones 2026 Abiertas
            </div>
            <h3
              class="text-2xl lg:text-3xl font-black mb-3"
              style="font-family: var(--font-display); color: var(--cyd-dark); letter-spacing: -0.03em;"
            >
              ¿Listo para descubrir tu talento?
            </h3>
            <div class="flex flex-wrap gap-5">
              <div class="flex items-center gap-2 text-sm" style="color: hsl(var(--muted-foreground));">
                <!-- Calendario SVG -->
                <svg width="15" height="15" viewBox="0 0 15 15" fill="none" aria-hidden="true" style="color: var(--cyd-green)">
                  <rect x="1" y="3" width="13" height="11" rx="1.5" stroke="currentColor" stroke-width="1.3"/>
                  <path d="M5 1v3M10 1v3M1 7h13" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
                </svg>
                Lunes a Viernes
              </div>
              <div class="flex items-center gap-2 text-sm" style="color: hsl(var(--muted-foreground));">
                <!-- Reloj SVG -->
                <svg width="15" height="15" viewBox="0 0 15 15" fill="none" aria-hidden="true" style="color: var(--cyd-gold);">
                  <circle cx="7.5" cy="7.5" r="6" stroke="currentColor" stroke-width="1.3"/>
                  <path d="M7.5 4.5v3l2 2" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                7:00 AM – 5:00 PM
              </div>
            </div>
          </div>
          <div class="flex justify-start md:justify-end">
            <button
              @click="document.getElementById('contacto')?.scrollIntoView({ behavior: 'smooth' })"
              class="cyd-btn-primary"
            >
              <span style="position:relative;z-index:1;">Más Información</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
