<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { gsap, ScrollTrigger } from '@/lib/gsap.js'

const sectionRef = ref(null)
let ctx = null

const carreras = [
  {
    key: 'diario',
    categoria: 'Diversificado',
    modalidad: 'Plan Diario',
    accent: '#c06f2a',
    light: '#fef8ef',
    border: '#f0c080',
    programas: [
      { nombre: 'Bachillerato en Ciencias y Letras', duracion: '2 años' },
      { nombre: 'Secretariado Oficinista con Orientación Jurídica', duracion: '2 años' },
      { nombre: 'Perito Contador con Orientación en Computación', duracion: '3 años' },
      { nombre: 'Perito en Administración de Empresas', duracion: '3 años' },
      { nombre: 'Magisterio en Educación Infantil Bilingüe Intercultural', duracion: '3 años' },
    ],
  },
  {
    key: 'doble',
    categoria: 'Diversificado',
    modalidad: 'Jornada Doble',
    accent: '#3a56a8',
    light: '#f0f3fc',
    border: '#a0b4e8',
    programas: [
      { nombre: 'Bachillerato en Dibujo Técnico y de Construcción', duracion: '2 años' },
      { nombre: 'Bachillerato en Ciencias y Letras con Orientación en Diseño Gráfico', duracion: '2 años' },
      { nombre: 'Bachillerato en Ciencias y Letras con Diplomado en:', duracion: '2 años', sub: ['Medicina', 'Criminología', 'Agronomía'] },
      { nombre: 'Bachiller Industrial y Perito en Mecánica Automotriz', duracion: '3 años' },
      { nombre: 'Perito en Electrónica y Dispositivos Digitales', duracion: '3 años' },
      { nombre: 'Perito en Electricidad Industrial', duracion: '3 años' },
    ],
  },
  {
    key: 'fds',
    categoria: 'Plan',
    modalidad: 'Fin de Semana',
    accent: '#1a7a5a',
    light: '#eef7f3',
    border: '#80c8a8',
    programas: [
      { nombre: 'Básico Normal', duracion: '3 años' },
      { nombre: 'Bachillerato en Ciencias y Letras por Madurez', duracion: '1 año (mayores de 18)' },
      { nombre: 'Perito Contador', duracion: '3 años' },
      { nombre: 'Bachillerato en Computación con Orientación Comercial', duracion: '2 años' },
      { nombre: 'Bach. en Computación con Orientación Comercial con Diplomado en:', duracion: '2 años', sub: ['Administración', 'Enfermería'] },
      { nombre: 'Secretariado y Oficinista', duracion: '2 años' },
    ],
  },
]

const scrollToContact = () => {
  document.getElementById('contacto')?.scrollIntoView({ behavior: 'smooth' })
}

onMounted(() => {
  ctx = gsap.context(() => {

    // Header
    gsap.from('.carreras-header', {
      opacity: 0, y: 60, duration: 1, ease: 'power3.out',
      scrollTrigger: { trigger: '.carreras-header', start: 'top 80%' },
    })

    // Stats row
    gsap.from('.carreras-stat', {
      opacity: 0, y: 30, scale: 0.9, duration: 0.6, stagger: 0.1, ease: 'back.out(1.4)',
      scrollTrigger: { trigger: '.carreras-stats', start: 'top 85%' },
    })

    // Grupos — aparecen uno a uno con scrub suave
    document.querySelectorAll('.carrera-grupo').forEach((grupo, i) => {
      gsap.from(grupo, {
        opacity: 0,
        y: 70,
        duration: 0.9,
        ease: 'power3.out',
        scrollTrigger: { trigger: grupo, start: 'top 82%' },
      })
      // Línea de acento izquierda crece al aparecer
      gsap.from(grupo.querySelector('.grupo-line'), {
        scaleY: 0,
        transformOrigin: 'top',
        duration: 0.8,
        ease: 'power3.out',
        scrollTrigger: { trigger: grupo, start: 'top 80%' },
      })
    })

    // Cards de programas — stagger dentro de cada grupo
    document.querySelectorAll('.programa-card').forEach((card) => {
      gsap.from(card, {
        opacity: 0,
        x: -20,
        duration: 0.5,
        ease: 'power3.out',
        scrollTrigger: { trigger: card, start: 'top 90%' },
      })
    })

    // CTA Entry
    gsap.from('.carreras-cta-content', {
      scrollTrigger: { trigger: '.carreras-cta-content', start: 'top 85%' },
      opacity: 0,
      y: 40,
      duration: 0.8,
      ease: 'power3.out'
    })

    // Jaguar Watermark Parallax
    gsap.fromTo('.carreras-jaguar-bg',
      { scale: 0.8, x: 20, y: 20, opacity: 0 },
      {
        scrollTrigger: {
          trigger: '.carreras-cta-content',
          start: 'top 90%',
          end: 'bottom center',
          scrub: 1,
        },
        scale: 1.1,
        x: -10,
        y: -10,
        opacity: 0.08,
        ease: 'none'
      }
    )

    // CTA final
    gsap.from('.carreras-cta', {
      opacity: 0, y: 50, duration: 0.9, ease: 'power3.out',
      scrollTrigger: { trigger: '.carreras-cta', start: 'top 85%' },
    })

  }, sectionRef.value)
})

onUnmounted(() => { ctx?.revert() })
</script>

<template>
  <section
    id="carreras"
    ref="sectionRef"
    class="py-16 lg:py-32 relative overflow-hidden cyd-section-bg"
  >
    <!-- Fondo decorativo -->
    <div class="absolute inset-0" aria-hidden="true">
      <div
        class="absolute top-0 left-0 w-[600px] h-[600px] rounded-full"
        style="background: radial-gradient(circle, color-mix(in srgb, var(--cyd-gold) 7%, transparent), transparent 70%); filter: blur(80px);"
      />
      <div class="absolute inset-0 cyd-dots opacity-20" />
    </div>

    <div class="relative cyd-container">

      <!-- Header -->
      <div class="carreras-header text-center max-w-2xl mx-auto mb-6">
        <span class="cyd-label mb-5 inline-block">Programas Educativos</span>
        <h2 class="cyd-title mb-5">
          Carreras <span class="cyd-accent">Educativas</span>
        </h2>
        <p class="text-base lg:text-lg" style="color: hsl(var(--muted-foreground));">
          <strong style="color: var(--cyd-forest);">19 carreras especializadas</strong>
          en 3 modalidades — diseñadas para prepararte para el mundo profesional.
        </p>
      </div>

      <!-- Stats rápidos -->
      <div class="carreras-stats flex flex-wrap justify-center gap-2 sm:gap-3 mb-12 lg:mb-20">
        <span class="carreras-stat cyd-pill">19 Carreras</span>
        <span class="carreras-stat cyd-pill" style="border-color: color-mix(in srgb, var(--cyd-gold) 30%, transparent); background: color-mix(in srgb, var(--cyd-gold) 8%, transparent); color: #9a7200;">3 Modalidades</span>
        <span class="carreras-stat cyd-pill" style="border-color: color-mix(in srgb, #3a56a8 30%, transparent); background: color-mix(in srgb, #3a56a8 8%, transparent); color: #3a56a8;">Certificación Oficial</span>
        <span class="carreras-stat cyd-pill" style="border-color: color-mix(in srgb, #1a7a5a 30%, transparent); background: color-mix(in srgb, #1a7a5a 8%, transparent); color: #1a7a5a;">Avaladas por MINEDUC</span>
      </div>

      <!-- Grupos de carreras -->
      <div class="space-y-6 lg:space-y-10 mb-12 lg:mb-20">
        <div
          v-for="grupo in carreras"
          :key="grupo.key"
          class="carrera-grupo relative rounded-2xl overflow-hidden border will-change-transform"
          :style="{ background: grupo.light, borderColor: grupo.border }"
        >
          <!-- Línea de acento izquierda -->
          <div
            class="grupo-line absolute left-0 top-0 bottom-0 w-1 will-change-transform"
            :style="{ background: grupo.accent }"
          />

          <div class="pl-6 pr-4 sm:pl-8 sm:pr-6 py-8 lg:py-10">
            <!-- Header del grupo -->
            <div class="flex flex-col sm:flex-row items-start sm:items-end justify-between gap-4 mb-6 pb-5" :style="{ borderBottom: `1px solid color-mix(in srgb, ${grupo.accent} 20%, transparent)` }">
              <div>
                <div
                  class="text-[10px] sm:text-xs font-semibold tracking-[0.14em] uppercase mb-1"
                  :style="{ color: grupo.accent }"
                >
                  {{ grupo.categoria }}
                </div>
                <h3
                  class="text-xl sm:text-2xl lg:text-3xl font-black"
                  style="font-family: var(--font-display); letter-spacing: -0.03em; color: var(--cyd-dark);"
                >
                  {{ grupo.modalidad }}
                </h3>
              </div>
              <div
                class="shrink-0 text-[10px] sm:text-xs font-medium px-3 py-1.5 rounded-full"
                :style="{
                  background: `color-mix(in srgb, ${grupo.accent} 12%, transparent)`,
                  color: grupo.accent,
                }"
              >
                {{ grupo.programas.length }} programas
              </div>
            </div>

            <!-- Grid de programas -->
            <div class="grid sm:grid-cols-2 gap-2 sm:gap-3">
              <div
                v-for="(prog, idx) in grupo.programas"
                :key="idx"
                class="programa-card group relative bg-white rounded-xl border p-4 sm:p-5 transition-all duration-300 will-change-transform cursor-default"
                :style="{ borderColor: `color-mix(in srgb, ${grupo.accent} 18%, transparent)` }"
                @mouseenter="(e) => { e.currentTarget.style.borderColor = grupo.accent; e.currentTarget.style.boxShadow = `0 8px 30px color-mix(in srgb, ${grupo.accent} 12%, transparent)` }"
                @mouseleave="(e) => { e.currentTarget.style.borderColor = `color-mix(in srgb, ${grupo.accent} 18%, transparent)`; e.currentTarget.style.boxShadow = 'none' }"
              >
                <!-- Nombre -->
                <h4
                  class="text-xs sm:text-sm font-semibold mb-2 leading-snug"
                  style="color: var(--cyd-dark); letter-spacing: -0.01em;"
                >
                  {{ prog.nombre }}
                </h4>

                <!-- Subespecialidades -->
                <ul v-if="prog.sub" class="mb-2 pl-3 space-y-0.5" :style="{ borderLeft: `2px solid color-mix(in srgb, ${grupo.accent} 30%, transparent)` }">
                  <li
                    v-for="sub in prog.sub"
                    :key="sub"
                    class="text-[10px] sm:text-xs flex items-center gap-1.5"
                    style="color: hsl(var(--muted-foreground));"
                  >
                    <span class="w-1 h-1 rounded-full shrink-0" :style="{ background: grupo.accent }" />
                    {{ sub }}
                  </li>
                </ul>

                <!-- Duración -->
                <div class="flex items-center gap-1.5 mt-1">
                  <!-- Reloj SVG propio -->
                  <svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true" :style="{ color: grupo.accent }">
                    <circle cx="6" cy="6" r="5" stroke="currentColor" stroke-width="1.3"/>
                    <path d="M6 3.5v2.5l1.5 1.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <span
                    class="text-[10px] sm:text-xs font-bold uppercase tracking-wider"
                    :style="{ color: grupo.accent }"
                  >
                    {{ prog.duracion }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- CTA -->
      <div class="carreras-cta relative rounded-2xl overflow-hidden p-6 sm:p-10 lg:p-14 text-center will-change-transform"
        style="background: linear-gradient(135deg, var(--cyd-forest) 0%, var(--cyd-green) 60%, color-mix(in srgb, var(--cyd-gold) 40%, var(--cyd-green)) 100%);"
      >
        <!-- Textura de puntos inversa -->
        <div class="absolute inset-0 cyd-dots opacity-[0.08]" style="background-image: radial-gradient(circle, rgba(255,255,255,0.3) 1px, transparent 1px);" />

        <!-- Jaguar watermark animado -->
        <div class="carreras-jaguar-bg absolute bottom-0 right-0 w-64 h-64 lg:w-80 lg:h-80 opacity-5 pointer-events-none origin-bottom-right" aria-hidden="true">
          <div class="w-full h-full" style="background: url('https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/Jaguarcin-3-cuartos-1761938045002.png?width=400&height=400&resize=contain') right bottom / contain no-repeat;" />
        </div>

        <div class="carreras-cta-content relative max-w-2xl mx-auto">
          <div class="text-xs font-semibold tracking-[0.2em] uppercase text-white/60 mb-4">Inscripciones 2026</div>
          <h3
            class="text-3xl lg:text-4xl font-black text-white mb-4 leading-tight"
            style="font-family: var(--font-display); letter-spacing: -0.03em;"
          >
            ¿Listo para tu Futuro Profesional?
          </h3>
          <p class="text-white/80 text-base mb-8 max-w-lg mx-auto">
            Únete a una institución con más de <strong class="text-white">33 años</strong> formando profesionales exitosos en Guatemala.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <button
              @click="scrollToContact"
              class="px-8 py-3.5 rounded-full font-semibold text-sm transition-all duration-300 hover:-translate-y-1"
              style="background: white; color: var(--cyd-forest); box-shadow: 0 8px 30px rgba(0,0,0,0.2);"
              @mouseenter="(e) => e.currentTarget.style.boxShadow = '0 12px 40px rgba(0,0,0,0.3)'"
              @mouseleave="(e) => e.currentTarget.style.boxShadow = '0 8px 30px rgba(0,0,0,0.2)'"
            >
              Solicitar Información
            </button>
            <button
              @click="scrollToContact"
              class="px-8 py-3.5 rounded-full font-semibold text-sm text-white border border-white/30 transition-all duration-300 hover:bg-white/15 hover:-translate-y-1"
            >
              Agendar Visita
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
