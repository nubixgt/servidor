<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { gsap } from '@/lib/gsap.js'

const isScrolled = ref(false)
const isMobileMenuOpen = ref(false)
const activeSection = ref('inicio')
const logoRef = ref(null)
const logoImgRef = ref(null)
let logoCtx = null

// ── Efecto magnético en el logo ───────────────────────────
const handleLogoMouseMove = (e) => {
  const logo = logoRef.value
  if (!logo) return
  const rect = logo.getBoundingClientRect()
  const cx = rect.left + rect.width / 2
  const cy = rect.top + rect.height / 2
  const dx = e.clientX - cx
  const dy = e.clientY - cy
  const dist = Math.sqrt(dx * dx + dy * dy)
  const maxDist = 60

  if (dist < maxDist) {
    const strength = (1 - dist / maxDist) * 8
    gsap.to(logoImgRef.value, {
      x: dx * strength / dist,
      y: dy * strength / dist,
      rotation: (dx / maxDist) * 6,
      duration: 0.3,
      ease: 'power2.out',
    })
  }
}

const handleLogoMouseLeave = () => {
  gsap.to(logoImgRef.value, {
    x: 0, y: 0, rotation: 0,
    duration: 0.8,
    ease: 'elastic.out(1, 0.4)',
  })
}

const handleLogoClick = () => {
  // Animación de "bounce" al hacer click en el logo
  gsap.timeline()
    .to(logoImgRef.value, { scale: 0.8, duration: 0.12, ease: 'power2.in' })
    .to(logoImgRef.value, { scale: 1.15, duration: 0.2, ease: 'power2.out' })
    .to(logoImgRef.value, { scale: 1, duration: 0.4, ease: 'elastic.out(1.2, 0.5)' })
  scrollToSection('inicio')
}

const handleScroll = () => {
  isScrolled.value = window.scrollY > 40
  const sections = ['inicio', 'niveles', 'tecnologia', 'actividades', 'nosotros', 'galeria', 'contacto']
  for (const id of sections.slice().reverse()) {
    const el = document.getElementById(id)
    if (el && window.scrollY >= el.offsetTop - 120) {
      activeSection.value = id
      break
    }
  }
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true })

  // Entrada de la navbar
  gsap.from('.cyd-nav-logo', { opacity: 0, x: -30, duration: 0.9, ease: 'power3.out', delay: 0.2 })
  gsap.from('.cyd-nav-item', { opacity: 0, y: -16, duration: 0.6, stagger: 0.07, ease: 'power3.out', delay: 0.4 })
  gsap.from('.cyd-nav-cta', { opacity: 0, x: 30, duration: 0.8, ease: 'power3.out', delay: 0.7 })

  // Animación idle del logo (rotación suave continua del anillo)
  logoCtx = gsap.context(() => {
    gsap.to('.nav-logo-ring', {
      rotation: 360,
      duration: 12,
      ease: 'none',
      repeat: -1,
      transformOrigin: 'center center',
    })
    // Respiración del logo al hacer hover
    gsap.to('.nav-logo-glow', {
      opacity: 0.6,
      scale: 1.2,
      duration: 2,
      ease: 'sine.inOut',
      yoyo: true,
      repeat: -1,
    })
  })
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  logoCtx?.revert()
})

const scrollToSection = (id) => {
  isMobileMenuOpen.value = false
  activeSection.value = id

  const el = document.getElementById(id)
  if (!el) return

  const targetY = el.getBoundingClientRect().top + window.scrollY - 72

  gsap.to(window, {
    scrollTo: { y: targetY, autoKill: false },
    duration: 1.1,
    ease: 'power4.inOut',
  })
}

const menuItems = [
  { id: 'inicio',      label: 'Inicio' },
  { id: 'nosotros',    label: 'Nosotros' },
  { id: 'oferta',      label: 'Oferta Académica', hasDropdown: true },
  { id: 'admisiones',  label: 'Admisiones' },
  { id: 'vida',        label: 'Vida Estudiantil', hasDropdown: true },
  { id: 'servicios',   label: 'Servicios', hasDropdown: true },
  { id: 'contacto',    label: 'Contacto' },
]
</script>

<template>
  <nav
    :class="[
      'fixed w-full top-0 z-50 transition-all duration-500',
      (isScrolled || isMobileMenuOpen)
        ? 'bg-white/96 backdrop-blur-xl shadow-[0_1px_32px_rgba(26,92,42,0.10)] border-b border-green-100/60'
        : 'bg-transparent'
    ]"
  >
    <div class="cyd-container">
      <div class="flex justify-between items-center h-[72px]">

        <!-- Logo con efecto magnético GSAP -->
        <div
          ref="logoRef"
          class="cyd-nav-logo flex items-center gap-3 cursor-pointer"
          @mousemove="handleLogoMouseMove"
          @mouseleave="handleLogoMouseLeave"
          @click="handleLogoClick"
        >
          <div class="relative w-[52px] h-[52px] shrink-0">
            <!-- Halo de respiración (glow idle) -->
            <div
              class="nav-logo-glow absolute inset-[-6px] rounded-full will-change-transform"
              style="background: radial-gradient(circle, color-mix(in srgb, var(--cyd-green) 30%, transparent), transparent 70%); opacity: 0.2;"
              aria-hidden="true"
            />
            <!-- Anillo giratorio GSAP -->
            <svg
              class="nav-logo-ring absolute inset-[-5px] will-change-transform"
              width="62" height="62" viewBox="0 0 62 62"
              fill="none" xmlns="http://www.w3.org/2000/svg"
              aria-hidden="true"
              style="transform-origin: center;"
            >
              <circle cx="31" cy="31" r="29" stroke="url(#logoGrad)" stroke-width="1.2" stroke-dasharray="6 8" stroke-linecap="round"/>
              <defs>
                <linearGradient id="logoGrad" x1="0" y1="0" x2="62" y2="62" gradientUnits="userSpaceOnUse">
                  <stop offset="0%" stop-color="var(--cyd-green)" stop-opacity="0.7"/>
                  <stop offset="50%" stop-color="var(--cyd-gold)" stop-opacity="0.5"/>
                  <stop offset="100%" stop-color="var(--cyd-green)" stop-opacity="0"/>
                </linearGradient>
              </defs>
            </svg>
            <!-- Fondo del logo -->
            <div class="absolute inset-[1px] rounded-full bg-white z-10" />
            <!-- Imagen del logo con ref para efecto magnético -->
            <img
              ref="logoImgRef"
              src="https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/LOGO-2020-1761860820111.png?width=200&height=200&resize=contain"
              alt="Colegio CYD"
              class="absolute inset-0 object-contain w-full h-full z-20 p-1 will-change-transform"
            />
          </div>

          <div class="hidden sm:flex flex-col leading-tight">
            <span
              class="font-display text-[1.1rem] font-800 tracking-tight"
              style="font-family: var(--font-display); font-weight: 800; letter-spacing: -0.025em; color: var(--cyd-dark);"
            >
              Colegio CYD
            </span>
            <span
              class="text-[0.68rem] font-medium tracking-[0.12em] uppercase"
              style="color: var(--cyd-green);"
            >
              Ciencia &amp; Desarrollo
            </span>
          </div>
        </div>

        <!-- Desktop Menu -->
        <div class="hidden lg:flex items-center gap-1">
          <button
            v-for="item in menuItems"
            :key="item.id"
            @click="scrollToSection(item.id)"
            class="cyd-nav-item relative px-3 py-2 text-[0.85rem] font-medium transition-colors duration-200 rounded-lg group flex items-center gap-1"
            :style="{
              color: activeSection === item.id ? 'var(--cyd-forest)' : 'hsl(var(--muted-foreground))'
            }"
          >
            <span class="relative z-10">{{ item.label }}</span>
            <svg v-if="item.hasDropdown" class="w-3.5 h-3.5 opacity-60 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>

            <!-- Indicador activo -->
            <span
              class="absolute bottom-0 left-1/2 -translate-x-1/2 h-[2px] rounded-full transition-all duration-300"
              :style="{
                background: 'linear-gradient(90deg, var(--cyd-green), var(--cyd-gold))',
                width: activeSection === item.id ? '60%' : '0%',
              }"
            />

            <!-- Hover bg -->
            <span
              class="absolute inset-0 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200"
              style="background: color-mix(in srgb, var(--cyd-green) 7%, transparent);"
            />
          </button>
        </div>

        <!-- CTA Desktop -->
        <div class="hidden lg:block cyd-nav-cta">
          <button
            @click="scrollToSection('app')"
            class="px-5 py-2.5 rounded-full flex items-center gap-2 text-white font-medium text-sm shadow-md transition-all hover:-translate-y-0.5 hover:shadow-lg"
            style="background: linear-gradient(90deg, #164627, #1E5C33);"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <span>APP CYD</span>
          </button>
        </div>

        <!-- Mobile Hamburger -->
        <button
          @click="isMobileMenuOpen = !isMobileMenuOpen"
          class="lg:hidden relative w-10 h-10 flex flex-col items-center justify-center gap-[5px] rounded-lg transition-colors hover:bg-green-50"
          aria-label="Menú"
        >
          <span
            class="block w-5 h-[1.5px] rounded-full transition-all duration-300"
            :style="{
              background: 'var(--cyd-dark)',
              transform: isMobileMenuOpen ? 'rotate(45deg) translate(4px, 4px)' : 'none'
            }"
          />
          <span
            class="block w-4 h-[1.5px] rounded-full transition-all duration-300"
            :style="{
              background: 'var(--cyd-dark)',
              opacity: isMobileMenuOpen ? 0 : 1,
              transform: isMobileMenuOpen ? 'translateX(8px)' : 'none'
            }"
          />
          <span
            class="block w-5 h-[1.5px] rounded-full transition-all duration-300"
            :style="{
              background: 'var(--cyd-dark)',
              transform: isMobileMenuOpen ? 'rotate(-45deg) translate(4px, -4px)' : 'none'
            }"
          />
        </button>
      </div>

      <!-- Mobile Menu -->
      <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 -translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-4"
      >
        <div v-if="isMobileMenuOpen" class="lg:hidden pb-4 pt-2 border-t border-green-100/60">
          <div class="grid grid-cols-2 gap-1.5 mb-4">
            <button
              v-for="item in menuItems"
              :key="item.id"
              @click="scrollToSection(item.id)"
              class="flex items-center gap-2 px-4 py-3 rounded-xl text-sm font-medium transition-all"
              :style="{
                background: activeSection === item.id ? 'color-mix(in srgb, var(--cyd-green) 10%, transparent)' : 'transparent',
                color: activeSection === item.id ? 'var(--cyd-forest)' : 'hsl(var(--muted-foreground))'
              }"
            >
              <!-- Punto de acento -->
              <span
                class="w-1.5 h-1.5 rounded-full shrink-0 transition-colors"
                :style="{ background: activeSection === item.id ? 'var(--cyd-green)' : 'currentColor', opacity: 0.4 }"
              />
              {{ item.label }}
            </button>
          </div>
          <div class="px-1">
            <button
              @click="scrollToSection('app')"
              class="w-full justify-center px-5 py-2.5 rounded-full flex items-center gap-2 text-white font-medium text-sm shadow-md"
              style="background: linear-gradient(90deg, #164627, #1E5C33);"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
              <span>APP CYD</span>
            </button>
          </div>
        </div>
      </Transition>
    </div>
  </nav>
</template>
