<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { gsap } from '@/lib/gsap.js'

const heroRef = ref(null)
const bgLayerRef = ref(null)
const jaguarRef = ref(null)
const badgeRef = ref(null)
let ctx = null

const showBubble = ref(false)
const currentMessage = ref('')
const bubbleTimer = ref(null)

const messages = [
  "¡Bienvenidos a la familia CYD! 🐾",
  "¡Inscripciones abiertas! ✨",
  "Formando líderes 🚀",
  "¡Ciencia y Disciplina! 📚"
]

const handleJaguarInteract = () => {
  if (showBubble.value) return
  currentMessage.value = messages[Math.floor(Math.random() * messages.length)]
  showBubble.value = true
  if (bubbleTimer.value) clearTimeout(bubbleTimer.value)
  bubbleTimer.value = setTimeout(() => { showBubble.value = false }, 4000)
}

const scrollToSection = (id) => {
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' })
}

onMounted(() => {
  ctx = gsap.context(() => {
    // Animaciones de entrada
    const tl = gsap.timeline({ delay: 0.1 })
    tl.from('.hero-label', { opacity: 0, y: 16, duration: 0.5, ease: 'power3.out' })
      .from('.hero-title-line', { opacity: 0, y: 24, duration: 0.6, stagger: 0.08, ease: 'power3.out' }, '-=0.3')
      .from('.hero-subtitle', { opacity: 0, y: 16, duration: 0.5, ease: 'power3.out' }, '-=0.3')
      .from('.hero-cta-btn', { opacity: 0, y: 16, duration: 0.5, stagger: 0.08, ease: 'power3.out' }, '-=0.3')
      .from('.hero-social', { opacity: 0, y: 16, duration: 0.5, ease: 'power3.out' }, '-=0.2')
      .from(jaguarRef.value, { opacity: 0, x: 40, scale: 0.95, duration: 0.9, ease: 'power3.out' }, 0.2)
      .from(badgeRef.value, { opacity: 0, y: 20, scale: 0.85, duration: 0.7, ease: 'back.out(1.5)' }, 0.6)

    // Badge float continuo
    gsap.to(badgeRef.value, {
      y: -12, duration: 2.8, ease: 'sine.inOut', yoyo: true, repeat: -1, delay: 1.2
    })

    // Parallax solo desktop
    const mm = gsap.matchMedia()
    mm.add('(min-width: 1024px)', () => {
      gsap.to(bgLayerRef.value, {
        yPercent: 25, ease: 'none',
        scrollTrigger: { trigger: heroRef.value, start: 'top top', end: 'bottom top', scrub: 1.2 }
      })
      gsap.to(jaguarRef.value, {
        yPercent: -12, ease: 'none',
        scrollTrigger: { trigger: heroRef.value, start: 'top top', end: 'bottom top', scrub: 1.8 }
      })
      gsap.to(badgeRef.value, {
        yPercent: -30, ease: 'none',
        scrollTrigger: { trigger: heroRef.value, start: 'top top', end: 'bottom top', scrub: 2.2 }
      })
      gsap.to('.hero-text-content', {
        yPercent: -20, ease: 'none',
        scrollTrigger: { trigger: heroRef.value, start: 'top top', end: 'bottom top', scrub: 1 }
      })
    })
  }, heroRef.value)
})

onUnmounted(() => { ctx?.revert() })
</script>

<template>
  <section
    id="inicio"
    ref="heroRef"
    class="relative overflow-hidden"
    style="min-height: 100svh; padding-top: 72px;"
  >
    <!-- ── FONDO CON PARALLAX ─────────────────────────── -->
    <div
      ref="bgLayerRef"
      class="absolute inset-0 z-0 will-change-transform"
      style="top: -10%; height: 120%;"
    >
      <!-- Imagen de fondo del colegio (cielo, edificio) -->
      <img
        src="https://images.unsplash.com/photo-1562774053-701939374585?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80"
        alt=""
        aria-hidden="true"
        class="w-full h-full object-cover object-center"
        style="filter: brightness(1.1) saturate(0.85);"
      />
      <!-- Overlay para legibilidad del texto -->
      <div class="absolute inset-0" style="background: linear-gradient(100deg, rgba(230,248,238,0.92) 0%, rgba(220,242,235,0.80) 45%, rgba(180,225,210,0.25) 100%);"></div>
    </div>

    <!-- ── CONTENIDO PRINCIPAL ───────────────────────── -->
    <div class="relative z-10 cyd-container w-full py-8 lg:py-0">
      <div class="grid lg:grid-cols-2 gap-4 items-center min-h-[calc(100svh-72px)]">

        <!-- COLUMNA IZQUIERDA ─ Texto -->
        <div class="hero-text-content flex flex-col justify-center gap-4 lg:gap-5 py-6 lg:py-12 will-change-transform">

          <!-- Label -->
          <div class="hero-label">
            <span class="inline-block text-[0.65rem] font-extrabold tracking-[0.2em] text-green-800 uppercase bg-green-100/70 backdrop-blur-sm px-3 py-1.5 rounded-full border border-green-200/80">
              CIENCIA Y DISCIPLINA
            </span>
          </div>

          <!-- Título grande -->
          <h1
            class="font-black leading-[1.04] tracking-tight text-slate-900"
            style="font-family: var(--font-display); font-size: clamp(2.8rem, 6vw, 5rem);"
          >
            <span class="block hero-title-line">Formamos hoy</span>
            <span class="block hero-title-line">
              a los&nbsp;<span class="text-transparent bg-clip-text" style="background-image: linear-gradient(90deg, #d97706, #f59e0b);">líderes</span>&nbsp;del
            </span>
            <span class="block hero-title-line">mañana</span>
          </h1>

          <!-- Subtítulo -->
          <p class="hero-subtitle text-slate-600 font-medium leading-relaxed max-w-[440px]" style="font-size: clamp(0.95rem, 1.5vw, 1.1rem);">
            Educación integral, innovadora y tecnológica para transformar tu futuro y el de nuestra comunidad.
          </p>

          <!-- CTAs -->
          <div class="flex flex-wrap gap-3">
            <button
              class="hero-cta-btn group flex items-center gap-3 text-white font-semibold rounded-full transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5 active:scale-95"
              style="background: linear-gradient(90deg, #164627, #1e5c33); padding: 0.85rem 1.5rem; font-size: 0.9rem;"
              @click="scrollToSection('oferta')"
            >
              <span>Conoce nuestro colegio</span>
              <span class="w-6 h-6 bg-white/25 rounded-full flex items-center justify-center group-hover:translate-x-0.5 transition-transform">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
              </span>
            </button>

            <button
              class="hero-cta-btn group flex items-center gap-3 text-slate-700 font-semibold rounded-full transition-all hover:-translate-y-0.5 active:scale-95"
              style="background: rgba(255,255,255,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.7); padding: 0.85rem 1.5rem; font-size: 0.9rem;"
              @click="scrollToSection('contacto')"
            >
              <svg class="w-4 h-4 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              <span>Agenda tu visita</span>
            </button>
          </div>

          <!-- Social Pill -->
          <div class="hero-social inline-flex items-center gap-3 w-fit rounded-full px-4 py-2.5" style="background: rgba(255,255,255,0.65); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.7);">
            <span class="text-[0.7rem] font-extrabold text-slate-500 uppercase tracking-widest whitespace-nowrap">Síguenos en:</span>
            <div class="flex items-center gap-3 text-slate-700">
              <a href="#" aria-label="Facebook" class="hover:text-green-700 transition-colors">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
              </a>
              <a href="#" aria-label="Instagram" class="hover:text-green-700 transition-colors">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
              </a>
              <a href="#" aria-label="YouTube" class="hover:text-green-700 transition-colors">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.5 12 3.5 12 3.5s-7.505 0-9.377.55a3.016 3.016 0 0 0-2.122 2.136C0 8.083 0 12 0 12s0 3.917.501 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.55 9.377.55 9.377.55s7.505 0 9.377-.55a3.016 3.016 0 0 0 2.122-2.136C24 15.917 24 12 24 12s0-3.917-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
              </a>
              <a href="#" aria-label="TikTok" class="hover:text-green-700 transition-colors">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>
              </a>
            </div>
          </div>
        </div>

        <!-- COLUMNA DERECHA ─ Jaguar + Badge -->
        <div class="relative flex items-end justify-center lg:justify-end min-h-[300px] lg:min-h-[calc(100svh-72px)] pointer-events-none">

          <!-- Jaguar interactivo -->
          <div
            class="relative z-10 pointer-events-auto cursor-pointer"
            style="width: min(90%, 520px); max-width: 520px;"
            @click="handleJaguarInteract"
            @mouseenter="handleJaguarInteract"
          >
            <!-- Burbuja de dialogo -->
            <Transition
              enter-active-class="transition-all duration-300 ease-out"
              enter-from-class="opacity-0 scale-75 -translate-y-2"
              enter-to-class="opacity-100 scale-100 translate-y-0"
              leave-active-class="transition-all duration-200 ease-in"
              leave-from-class="opacity-100 scale-100 translate-y-0"
              leave-to-class="opacity-0 scale-75 -translate-y-2"
            >
              <div
                v-if="showBubble"
                class="absolute z-30 bg-white rounded-2xl rounded-br-sm px-4 py-3 shadow-2xl border border-slate-100 max-w-[200px]"
                style="top: 15%; right: 90%;"
              >
                <p class="text-sm font-bold text-slate-800 leading-tight text-center">{{ currentMessage }}</p>
                <div class="absolute -bottom-2 right-5 w-4 h-4 bg-white rotate-45 border-b border-r border-slate-100"></div>
              </div>
            </Transition>

            <img
              ref="jaguarRef"
              src="https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/Jaguarcin-3-cuartos-1761938045002.png"
              alt="Jaguar mascota del Colegio CYD"
              class="w-full h-auto object-contain will-change-transform"
              style="filter: drop-shadow(0 24px 48px rgba(22,70,39,0.18));"
            />
          </div>

          <!-- Badge "30 años" Glassmorphism -->
          <div
            ref="badgeRef"
            class="absolute z-20 pointer-events-none will-change-transform flex flex-col items-center text-center p-5 rounded-3xl"
            style="
              top: 28%;
              right: 2%;
              min-width: 148px;
              background: rgba(255,255,255,0.65);
              backdrop-filter: blur(20px);
              -webkit-backdrop-filter: blur(20px);
              border: 1px solid rgba(255,255,255,0.85);
              box-shadow: 0 8px 32px rgba(0,0,0,0.07), inset 0 1px 0 rgba(255,255,255,0.9);
            "
          >
            <span class="text-[0.6rem] font-extrabold text-slate-500 uppercase tracking-widest">Más de</span>
            <span class="font-black text-green-800 leading-none my-0.5" style="font-size: 3.5rem; font-family: var(--font-display);">30</span>
            <span class="text-lg font-bold text-green-700 leading-none">años</span>
            <span class="text-[0.68rem] text-slate-500 leading-snug font-medium mt-1">educando con<br>excelencia</span>
            <div class="mt-3 w-11 h-11 bg-white rounded-full shadow-md flex items-center justify-center">
              <img src="https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/LOGO-2020-1761860820111.png" alt="Logo CYD" class="w-8 h-8 object-contain" />
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
</template>

<style scoped>
</style>
