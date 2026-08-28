<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { gsap, ScrollTrigger } from '@/lib/gsap.js'

const heroRef = ref(null)
const bgLayerRef = ref(null)
const jaguarRef = ref(null)
const titleRef = ref(null)
const subtitleRef = ref(null)
const statsRef = ref(null)
const ctaRef = ref(null)
const orbRef1 = ref(null)
const orbRef2 = ref(null)

let ctx = null

// Generación de partículas mágicas para el Jaguar
const particles = Array.from({ length: 20 }).map((_, i) => ({
  id: i,
  size: Math.random() * 8 + 4, // Entre 4 y 12px
  x: Math.random() * 100,      // Posición X %
  y: Math.random() * 100,      // Posición Y %
  color: Math.random() > 0.5 ? 'var(--cyd-gold)' : 'var(--cyd-green)',
  delay: Math.random() * 4,    // Retraso aleatorio
  duration: Math.random() * 3 + 3, // Duración entre 3s y 6s
  tx: (Math.random() * 60 - 30) + 'px', // Desplazamiento X aleatorio
}))

const scrollToSection = (id) => {
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' })
}

const handleJaguarEnter = () => {
  if (!jaguarRef.value) return
  gsap.to(jaguarRef.value, {
    scale: 1.15,
    y: -30,
    rotation: 4,
    duration: 0.4,
    ease: 'back.out(2)',
  })
}

const handleJaguarLeave = () => {
  if (!jaguarRef.value) return
  gsap.to(jaguarRef.value, {
    scale: 1,
    y: 0,
    rotation: 0,
    duration: 0.7,
    ease: 'elastic.out(1, 0.4)',
  })
}

onMounted(() => {
  ctx = gsap.context(() => {
    // ── Animación inicial ─────────────────────────────────
    const tl = gsap.timeline({ delay: 0.1 })

    // Label
    tl.from('.hero-label', {
      opacity: 0,
      y: 20,
      duration: 0.7,
      ease: 'power3.out',
    })

    // Título palabra por palabra
    tl.from('.hero-word', {
      opacity: 0,
      y: 40,
      rotateX: -30,
      duration: 0.7,
      stagger: 0.1,
      ease: 'power3.out',
    }, '-=0.3')

    // Subtítulo
    tl.from('.hero-subtitle', {
      opacity: 0,
      y: 24,
      duration: 0.7,
      ease: 'power3.out',
    }, '-=0.3')

    // Stats cards
    tl.from('.hero-stat', {
      opacity: 0,
      y: 30,
      scale: 0.92,
      duration: 0.6,
      stagger: 0.1,
      ease: 'back.out(1.4)',
    }, '-=0.2')

    // CTAs
    tl.from('.hero-cta', {
      opacity: 0,
      y: 20,
      duration: 0.6,
      stagger: 0.12,
      ease: 'power3.out',
    }, '-=0.3')

    // Jaguar
    tl.from(jaguarRef.value, {
      opacity: 0,
      x: 60,
      scale: 0.9,
      duration: 1,
      ease: 'power3.out',
    }, 0.2)

    // ── Parallax con ScrollTrigger (Solo Desktop) ────────────────────────
    let mm = gsap.matchMedia()
    
    mm.add("(min-width: 1024px)", () => {
      // Fondo
      gsap.to(bgLayerRef.value, {
        yPercent: 35,
        ease: 'none',
        scrollTrigger: {
          trigger: heroRef.value,
          start: 'top top',
          end: 'bottom top',
          scrub: 1.2,
        },
      })

      // Jaguar - parallax más lento
      gsap.to(jaguarRef.value, {
        yPercent: -25,
        ease: 'none',
        scrollTrigger: {
          trigger: heroRef.value,
          start: 'top top',
          end: 'bottom top',
          scrub: 1.8,
        },
      })

      // Texto - parallax rápido
      gsap.to(titleRef.value, {
        yPercent: -40,
        ease: 'none',
        scrollTrigger: {
          trigger: heroRef.value,
          start: 'top top',
          end: '40% top',
          scrub: 1,
        },
      })

      gsap.to(subtitleRef.value, {
        yPercent: -30,
        ease: 'none',
        scrollTrigger: {
          trigger: heroRef.value,
          start: 'top top',
          end: '35% top',
          scrub: 1.2,
        },
      })

      gsap.to(statsRef.value, {
        yPercent: -20,
        ease: 'none',
        scrollTrigger: {
          trigger: heroRef.value,
          start: '5% top',
          end: '45% top',
          scrub: 0.8,
        },
      })

      gsap.to(ctaRef.value, {
        yPercent: -15,
        ease: 'none',
        scrollTrigger: {
          trigger: heroRef.value,
          start: '10% top',
          end: '40% top',
          scrub: 0.9,
        },
      })

      // Orbs flotantes con parallax suave
      gsap.to(orbRef1.value, {
        yPercent: 40,
        xPercent: -8,
        ease: 'none',
        scrollTrigger: {
          trigger: heroRef.value,
          start: 'top top',
          end: 'bottom top',
          scrub: 2,
        },
      })

      gsap.to(orbRef2.value, {
        yPercent: -30,
        xPercent: 10,
        ease: 'none',
        scrollTrigger: {
          trigger: heroRef.value,
          start: 'top top',
          end: 'bottom top',
          scrub: 2.5,
        },
      })

      // Scroll indicator fade
      gsap.to('.hero-scroll-indicator', {
        opacity: 0,
        ease: 'none',
        scrollTrigger: {
          trigger: heroRef.value,
          start: 'top top',
          end: '15% top',
          scrub: 1,
        },
      })
    })

    // Animación flotante continua para orbes (Todas las pantallas)
    gsap.to(orbRef1.value, {
      y: 40, x: -30, rotation: 10,
      duration: 6, ease: 'sine.inOut',
      yoyo: true, repeat: -1
    })

    gsap.to(orbRef2.value, {
      y: -50, x: 40, rotation: -15,
      duration: 7, ease: 'sine.inOut',
      yoyo: true, repeat: -1, delay: 1
    })

    // Animación flotante continua para orbes (Todas las pantallas)

  }, heroRef.value)
})

onUnmounted(() => {
  ctx?.revert()
})
</script>

<template>
  <section
    id="inicio"
    ref="heroRef"
    class="relative min-h-[auto] lg:min-h-[110vh] flex flex-col justify-center overflow-hidden pt-28 pb-10 lg:pt-[72px] lg:pb-20"
  >
    <!-- Fondo con parallax -->
    <div
      ref="bgLayerRef"
      class="absolute inset-0 will-change-transform"
      style="z-index: 0;"
    >
      <!-- Gradiente base -->
      <div
        class="absolute inset-0"
        style="background: linear-gradient(135deg, #f0f7f1 0%, #faf8f0 35%, #ffffff 60%, #eef5f0 100%);"
      />

      <!-- Textura de cuadrícula de ingeniería CYD (Efecto premium) -->
      <div class="absolute inset-0 cyd-grid opacity-70" />
      
      <!-- Viñeta suave para enfocar el centro -->
      <div
        class="absolute inset-0 pointer-events-none"
        style="background: radial-gradient(circle at center, transparent 40%, rgba(240, 247, 241, 0.4) 100%);"
      />



      <!-- Orb 1 -->
      <div
        ref="orbRef1"
        class="absolute top-[15%] left-[8%] w-[380px] h-[380px] rounded-full will-change-transform"
        style="background: radial-gradient(circle at center, color-mix(in srgb, var(--cyd-green) 18%, transparent), transparent 70%); filter: blur(40px);"
      />

      <!-- Orb 2 -->
      <div
        ref="orbRef2"
        class="absolute bottom-[25%] right-[5%] w-[440px] h-[440px] rounded-full will-change-transform"
        style="background: radial-gradient(circle at center, color-mix(in srgb, var(--cyd-gold) 16%, transparent), transparent 70%); filter: blur(50px);"
      />
    </div>

    <!-- Contenido principal -->
    <div class="relative z-10 cyd-container py-6 lg:py-16 w-full">
      <div class="grid lg:grid-cols-2 gap-6 lg:gap-16 items-center">

        <!-- Columna izquierda -->
        <div class="space-y-5 lg:space-y-8">

          <!-- Label -->
          <div class="hero-label">
            <span class="cyd-label">Formando líderes desde 1992</span>
          </div>

          <!-- Título -->
          <h1
            ref="titleRef"
            class="will-change-transform"
            style="perspective: 1000px;"
          >
            <span class="block overflow-hidden mb-1">
              <span
                class="hero-word block cyd-title"
                style="display: inline-block;"
              >
                Educación de
              </span>
            </span>
            <span class="block overflow-hidden mb-1">
              <span
                class="hero-word block cyd-title cyd-accent"
                style="display: inline-block;"
              >
                Excelencia
              </span>
            </span>
            <span class="block overflow-hidden">
              <span
                class="hero-word block cyd-title"
                style="display: inline-block; font-size: clamp(1.4rem, 3vw, 2.4rem); font-weight: 400; color: hsl(var(--muted-foreground));"
              >
                Colegio CYD — Salamá, B.V.
              </span>
            </span>
          </h1>

          <!-- Subtítulo -->
          <p
            ref="subtitleRef"
            class="hero-subtitle text-base lg:text-lg leading-relaxed max-w-lg will-change-transform"
            style="color: hsl(var(--muted-foreground));"
          >
            Colegio Particular Mixto con instalaciones modernas e innovadoras,
            dedicado a formar estudiantes con
            <strong style="color: var(--cyd-forest); font-weight: 600;">ciencia y disciplina</strong>
            para el mundo del mañana.
          </p>

          <!-- Stats -->
          <div ref="statsRef" class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 will-change-transform">

            <!-- Stat 1 -->
            <div
              class="hero-stat cyd-card p-4 sm:p-5 text-center cursor-default"
              @click="scrollToSection('nosotros')"
            >
              <div
                class="text-2xl lg:text-4xl font-black leading-none mb-1 cyd-stat-number"
              >+33</div>
              <div
                class="text-[10px] sm:text-xs font-medium leading-tight"
                style="color: hsl(var(--muted-foreground));"
              >Años de<br>Excelencia</div>
              <div class="cyd-divider mx-auto mt-2 sm:mt-3" />
            </div>

            <!-- Stat 2 -->
            <div
              class="hero-stat cyd-card p-4 sm:p-5 text-center cursor-default"
              style="border-color: color-mix(in srgb, var(--cyd-gold) 35%, transparent);
                     box-shadow: 0 4px 20px color-mix(in srgb, var(--cyd-gold) 12%, transparent);"
            >
              <div
                class="text-2xl lg:text-3xl font-black leading-none mb-1 cyd-stat-number"
              >15K+</div>
              <div
                class="text-[10px] sm:text-xs font-medium leading-tight"
                style="color: hsl(var(--muted-foreground));"
              >Egresados<br>Exitosos</div>
              <div
                class="cyd-divider mx-auto mt-2 sm:mt-3"
                style="background: linear-gradient(90deg, var(--cyd-gold), var(--cyd-amber));"
              />
            </div>

            <!-- Stat 3 -->
            <div
              class="hero-stat cyd-card p-4 sm:p-5 text-center cursor-pointer col-span-2 sm:col-span-1"
              @click="scrollToSection('carreras')"
            >
              <div
                class="text-2xl lg:text-4xl font-black leading-none mb-1 cyd-stat-number"
              >19</div>
              <div
                class="text-[10px] sm:text-xs font-medium leading-tight"
                style="color: hsl(var(--muted-foreground));"
              >Carreras<br>Educativas</div>
              <div class="cyd-divider mx-auto mt-2 sm:mt-3" />
            </div>
          </div>

          <!-- CTAs -->
          <div ref="ctaRef" class="flex flex-col sm:flex-row gap-3 will-change-transform">
            <button
              class="hero-cta cyd-btn-primary group"
              @click="scrollToSection('niveles')"
            >
              <span>Conocer Niveles</span>
              <svg class="transition-transform duration-300 group-hover:translate-x-1.5" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true" style="position:relative;z-index:1;">
                <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>

            <button
              class="hero-cta cyd-btn-outline group"
              @click="scrollToSection('contacto')"
            >
              <span>Inscripciones 2026</span>
              <svg class="opacity-0 -ml-4 transition-all duration-300 group-hover:opacity-100 group-hover:ml-0" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Columna derecha — Jaguar + Decoración -->
        <div 
          class="relative flex items-center justify-center min-h-[300px] sm:min-h-[400px] lg:min-h-[640px] mt-6 lg:mt-0"
          @mousemove="(e) => {
            if(!jaguarRef) return;
            const rect = e.currentTarget.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            
            // Jaguar 3D Parallax
            gsap.to(jaguarRef, {
              rotateX: (-y / rect.height) * 30,
              rotateY: (x / rect.width) * 30,
              x: (x / rect.width) * 30,
              y: (y / rect.height) * 30,
              transformPerspective: 1000,
              duration: 0.6,
              ease: 'power2.out'
            });

            // Particles Parallax (Opposite direction + scale)
            gsap.to('.particles-wrapper', {
              x: (-x / rect.width) * 60,
              y: (-y / rect.height) * 60,
              scale: 1 + (Math.abs(x) + Math.abs(y)) / 3000, // Slight scale up based on distance
              duration: 0.8,
              ease: 'power2.out'
            });
          }"
          @mouseleave="() => {
            if(!jaguarRef) return;
            gsap.to(jaguarRef, {
              rotateX: 0, rotateY: 0, x: 0, y: 0,
              duration: 1, ease: 'elastic.out(1, 0.4)'
            });
            gsap.to('.particles-wrapper', {
              x: 0, y: 0, scale: 1,
              duration: 1, ease: 'elastic.out(1, 0.4)'
            });
          }"
        >

          <!-- Premium Glowing Aura detrás del Jaguar -->
          <div
            class="absolute w-[280px] h-[280px] lg:w-[550px] lg:h-[550px] rounded-full pointer-events-none"
            style="
              background: radial-gradient(circle, color-mix(in srgb, var(--cyd-gold) 15%, transparent) 0%, transparent 70%);
              filter: blur(40px);
              animation: cyd-pulse-glow 8s ease-in-out infinite alternate;
            "
            aria-hidden="true"
          />

          <!-- Círculo interior elegante -->
          <div
            class="absolute w-[220px] h-[220px] lg:w-[420px] lg:h-[420px] rounded-full pointer-events-none"
            style="
              background: radial-gradient(circle, color-mix(in srgb, var(--cyd-green) 12%, transparent) 0%, transparent 65%);
              filter: blur(30px);
              animation: cyd-pulse-glow 6s ease-in-out infinite alternate-reverse;
            "
            aria-hidden="true"
          />

          <!-- Partículas Mágicas Flotantes -->
          <div class="absolute inset-0 z-0 pointer-events-none flex items-center justify-center">
            <div class="particles-wrapper relative w-[260px] h-[260px] lg:w-[420px] lg:h-[420px] will-change-transform">
              <div
                v-for="p in particles"
                :key="p.id"
                class="absolute rounded-full"
                :style="{
                  width: p.size + 'px',
                  height: p.size + 'px',
                  left: p.x + '%',
                  top: p.y + '%',
                  background: p.color,
                  boxShadow: `0 0 ${p.size * 2}px ${p.color}`,
                  animation: `cyd-particle-float ${p.duration}s ease-in-out infinite ${p.delay}s`,
                  '--tx': p.tx
                }"
              />
            </div>
          </div>

          <!-- Jaguar -->
          <div
            class="relative z-10 w-[260px] h-[260px] lg:w-[420px] lg:h-[420px] animate-float jaguar-container pointer-events-none"
          >
            <img
              ref="jaguarRef"
              src="https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/Jaguarcin-3-cuartos-1761938045002.png?width=8000&height=8000&resize=contain"
              alt="Jaguar — Mascota Colegio CYD"
              class="w-full h-full object-contain will-change-transform"
              style="filter: drop-shadow(0 20px 60px color-mix(in srgb, var(--cyd-dark) 30%, transparent));"
            />
          </div>

          <!-- Badge flotante — Excelencia -->
          <div
            class="absolute top-4 sm:top-12 right-0 lg:-right-4 cyd-card px-3 py-2 sm:px-4 sm:py-3 flex items-center gap-2 sm:gap-3"
            style="animation: cyd-float 5s ease-in-out infinite; animation-delay: -1s;"
          >
            <div
              class="w-9 h-9 rounded-full flex items-center justify-center shrink-0"
              style="background: linear-gradient(135deg, var(--cyd-forest), var(--cyd-green));"
            >
              <!-- Estrella SVG propio -->
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M8 1l1.8 4H14l-3.4 2.8 1.3 4.2L8 9.4l-3.9 2.6 1.3-4.2L2 5h4.2L8 1z" fill="white"/>
              </svg>
            </div>
            <div>
              <div class="text-xs font-semibold" style="color: var(--cyd-dark);">Excelencia Académica</div>
              <div class="text-[10px]" style="color: hsl(var(--muted-foreground));">+33 años formando líderes</div>
            </div>
          </div>

          <!-- Badge flotante — Carreras -->
          <div
            class="absolute bottom-4 sm:bottom-20 left-0 lg:-left-4 cyd-card px-3 py-2 sm:px-4 sm:py-3 flex items-center gap-2 sm:gap-3"
            style="animation: cyd-float 5.5s ease-in-out infinite; animation-delay: -2.5s;"
          >
            <div
              class="w-9 h-9 rounded-full flex items-center justify-center shrink-0"
              style="background: linear-gradient(135deg, var(--cyd-gold), var(--cyd-amber));"
            >
              <!-- Libro SVG propio -->
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <rect x="2" y="2" width="8" height="12" rx="1" stroke="white" stroke-width="1.5"/>
                <path d="M6 5h2M6 7.5h4M6 10h3" stroke="white" stroke-width="1.2" stroke-linecap="round"/>
                <path d="M10 2v12" stroke="white" stroke-width="1.5"/>
              </svg>
            </div>
            <div>
              <div class="text-xs font-semibold" style="color: var(--cyd-dark);">19 Carreras</div>
              <div class="text-[10px]" style="color: hsl(var(--muted-foreground));">Educativas disponibles</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Scroll indicator -->
    <div
      class="hero-scroll-indicator absolute bottom-10 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2"
      style="z-index: 10;"
    >
      <span class="text-[10px] tracking-[0.2em] uppercase" style="color: hsl(var(--muted-foreground));">Scroll</span>
      <div
        class="w-[1px] h-10 rounded-full overflow-hidden"
        style="background: color-mix(in srgb, var(--cyd-green) 20%, transparent);"
      >
        <div
          class="w-full rounded-full"
          style="
            height: 40%;
            background: var(--cyd-green);
            animation: scroll-line 1.8s ease-in-out infinite;
          "
        />
      </div>
    </div>
  </section>
</template>

<style scoped>
@keyframes cyd-pulse-glow {
  0% { transform: scale(1) translate(0px, 0px); opacity: 0.8; }
  50% { transform: scale(1.05) translate(10px, -15px); opacity: 1; }
  100% { transform: scale(0.95) translate(-10px, 10px); opacity: 0.8; }
}

@keyframes scroll-line {
  0%   { transform: translateY(-100%); }
  50%  { transform: translateY(150%); }
  100% { transform: translateY(-100%); }
}

@keyframes cyd-particle-float {
  0% { transform: translate(0, 40px) scale(0.1); opacity: 0; }
  25% { opacity: 0.8; transform: translate(calc(var(--tx) * 0.3), 10px) scale(1.6); }
  50% { opacity: 1; transform: translate(calc(var(--tx) * 0.6), -20px) scale(0.5); }
  75% { opacity: 0.8; transform: translate(calc(var(--tx) * 0.9), -50px) scale(2); }
  100% { transform: translate(var(--tx), -100px) scale(0.1); opacity: 0; }
}
</style>
