<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { gsap, ScrollTrigger } from '@/lib/gsap.js'

const sectionRef = ref(null)

let ctx = null

const timeline = [
  {
    year: '1992',
    title: 'Fundación',
    description: 'Inicio del Colegio CYD en Salamá, Baja Verapaz con la visión de formar estudiantes con ciencia y disciplina.',
    color: 'var(--cyd-forest)',
    side: 'left',
  },
  {
    year: '2005',
    title: 'Expansión',
    description: 'Apertura de nuevas secciones educativas y laboratorios especializados para ciencias y tecnología.',
    color: '#4d6cc4',
    side: 'right',
  },
  {
    year: '2015',
    title: 'Modernización',
    description: 'Implementación de tecnología educativa avanzada, laboratorios IMAC y plataformas digitales.',
    color: 'var(--cyd-gold)',
    side: 'left',
  },
  {
    year: '2025',
    title: 'Excelencia Continua',
    description: '33 años de trayectoria, más de 15,000 estudiantes formados y 19 carreras educativas disponibles.',
    color: 'var(--cyd-green)',
    side: 'right',
  },
]

const valores = [
  {
    title: 'Disciplina',
    description: 'Formamos estudiantes responsables y comprometidos con su futuro.',
    letter: 'D',
    color: '#4d6cc4',
  },
  {
    title: 'Ciencia',
    description: 'Educación basada en el conocimiento y la innovación constante.',
    letter: 'C',
    color: 'var(--cyd-forest)',
  },
  {
    title: 'Valores',
    description: 'Respeto, honestidad e integridad en todo momento y lugar.',
    letter: 'V',
    color: '#c26b6b',
  },
  {
    title: 'Excelencia',
    description: 'Compromiso firme con la calidad educativa de nivel superior.',
    letter: 'E',
    color: 'var(--cyd-gold)',
  },
]

const logros = [
  { number: '33', suffix: '', unit: 'Años', label: 'de Trayectoria', color: 'var(--cyd-forest)' },
  { number: '15', suffix: 'K+', unit: 'Egresados', label: 'Exitosos', color: 'var(--cyd-gold)' },
  { number: '19', suffix: '', unit: 'Carreras', label: 'Educativas', color: '#4d6cc4' },
]

onMounted(() => {
  ctx = gsap.context(() => {

    // Header
    gsap.from('.about-header', {
      opacity: 0,
      y: 50,
      duration: 1,
      ease: 'power3.out',
      scrollTrigger: { trigger: '.about-header', start: 'top 80%' },
    })

    // Misión texto
    gsap.from('.about-mission-text', {
      opacity: 0,
      x: -50,
      duration: 0.9,
      ease: 'power3.out',
      scrollTrigger: { trigger: '.about-mission-text', start: 'top 80%' },
    })

    // Jaguar - Animación combinada de Parado a Saltando
    const tlJaguar = gsap.timeline({
      scrollTrigger: {
        trigger: '.about-jaguar',
        start: 'top 85%',
        end: 'top 30%',
        scrub: 1, // Suavizado para que se vea como animación
      }
    })

    tlJaguar.to('.jaguar-stand', { opacity: 0, scale: 0.8, y: 20, duration: 1, ease: 'power1.inOut' }, 0)
    tlJaguar.fromTo('.jaguar-jump', 
      { opacity: 0, y: 40, scale: 0.9, rotation: -5 }, 
      { opacity: 1, y: -50, scale: 1.15, rotation: 0, duration: 1, ease: 'power1.inOut' }, 
      0
    )

    // Valores cards — stagger
    gsap.from('.valor-card', {
      opacity: 0,
      y: 40,
      scale: 0.9,
      duration: 0.7,
      stagger: 0.12,
      ease: 'back.out(1.4)',
      scrollTrigger: { trigger: '.valores-grid', start: 'top 80%' },
    })

    // Halo del Jaguar (Círculo expansivo con ScrollTrigger)
    gsap.fromTo('.jaguar-halo', 
      { scale: 0.2, opacity: 0 },
      {
        scale: 1.8,
        opacity: 1,
        ease: 'none',
        scrollTrigger: {
          trigger: '.about-jaguar',
          start: 'top bottom',
          end: 'bottom top',
          scrub: true,
        }
      }
    )

    // Timeline — animar línea y cards
    const timelineCards = document.querySelectorAll('.timeline-card')
    timelineCards.forEach((card, i) => {
      gsap.from(card, {
        opacity: 0,
        x: i % 2 === 0 ? -60 : 60,
        duration: 0.9,
        ease: 'power3.out',
        scrollTrigger: {
          trigger: card,
          start: 'top 82%',
        },
      })
    })

    // Línea del timeline — dibujarse
    gsap.from('.timeline-line', {
      scaleY: 0,
      transformOrigin: 'top center',
      duration: 1.5,
      ease: 'power2.out',
      scrollTrigger: {
        trigger: '.timeline-section',
        start: 'top 70%',
        end: 'bottom 30%',
        scrub: 1,
      },
    })

    // Logros — contador + animación
    const logroEls = document.querySelectorAll('.logro-number')
    logroEls.forEach((el) => {
      const end = parseInt(el.dataset.end)
      const suffix = el.dataset.suffix || ''
      gsap.from(el, {
        opacity: 0,
        y: 30,
        duration: 0.7,
        ease: 'power3.out',
        scrollTrigger: {
          trigger: el,
          start: 'top 85%',
          onEnter: () => {
            const obj = { val: 0 }
            gsap.to(obj, {
              val: end,
              duration: 1.8,
              ease: 'power2.out',
              onUpdate: () => {
                el.textContent = Math.round(obj.val) + suffix
              },
            })
          },
        },
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
    id="nosotros"
    ref="sectionRef"
    class="py-16 lg:py-32 relative overflow-hidden"
  >
    <!-- Fondo -->
    <div class="absolute inset-0" aria-hidden="true">
      <div
        class="absolute top-1/3 right-0 w-[500px] h-[500px] rounded-full"
        style="background: radial-gradient(circle, color-mix(in srgb, var(--cyd-gold) 6%, transparent), transparent 70%); filter: blur(80px);"
      />
      <div class="absolute inset-0 cyd-dots opacity-25" />
    </div>

    <div class="relative cyd-container">

      <!-- Header -->
      <div class="about-header text-center max-w-2xl mx-auto mb-14 lg:mb-24">
        <span class="cyd-label mb-5 inline-block">Nuestra Historia</span>
        <h2 class="cyd-title mb-5">
          Sobre <span class="cyd-accent">Nosotros</span>
        </h2>
        <p class="text-base lg:text-lg" style="color: hsl(var(--muted-foreground));">
          Desde 1992, 33 años formando líderes con ciencia y disciplina en Salamá, Baja Verapaz.
        </p>
      </div>

      <!-- Misión + Jaguar -->
      <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center mb-16 lg:mb-28">

        <div class="about-mission-text space-y-6">
          <h3
            class="text-3xl font-bold"
            style="font-family: var(--font-display); letter-spacing: -0.025em; color: var(--cyd-dark);"
          >
            Nuestra Misión
          </h3>
          <p class="text-base leading-relaxed" style="color: hsl(var(--muted-foreground));">
            El <strong style="color: var(--cyd-forest);">Colegio Particular Mixto CYD</strong>
            se dedica a formar estudiantes integrales mediante una educación de calidad que combina
            <strong style="color: var(--cyd-gold);">ciencia y disciplina</strong>.
          </p>
          <p class="text-base leading-relaxed" style="color: hsl(var(--muted-foreground));">
            Con <strong style="color: #4d6cc4;">19 carreras educativas</strong> y más de
            <strong style="color: var(--cyd-forest);">15,000 estudiantes formados</strong>,
            contamos con instalaciones modernas que facilitan el aprendizaje y preparamos a nuestros
            estudiantes para el futuro con valores sólidos y pensamiento crítico.
          </p>

          <!-- Pills de datos -->
          <div class="flex flex-wrap gap-2.5 pt-2">
            <span class="cyd-pill">33 Años de Trayectoria</span>
            <span class="cyd-pill" style="border-color: color-mix(in srgb, var(--cyd-gold) 30%, transparent); background: color-mix(in srgb, var(--cyd-gold) 8%, transparent); color: #9a7200;">19 Carreras Educativas</span>
            <span class="cyd-pill" style="border-color: color-mix(in srgb, #4d6cc4 30%, transparent); background: color-mix(in srgb, #4d6cc4 8%, transparent); color: #3a56a8;">+15,000 Estudiantes</span>
          </div>
        </div>

        <div class="about-jaguar flex justify-center">
          <div
            class="relative w-72 h-72 lg:w-[380px] lg:h-[380px] jaguar-container perspective-1000"
            @mousemove="(e) => {
              const rect = e.currentTarget.getBoundingClientRect();
              const x = e.clientX - rect.left - rect.width / 2;
              const y = e.clientY - rect.top - rect.height / 2;
              gsap.to('.jaguar-3d-wrapper', {
                rotateX: (-y / rect.height) * 20,
                rotateY: (x / rect.width) * 20,
                x: (x / rect.width) * 10,
                y: (y / rect.height) * 10,
                duration: 0.6,
                ease: 'power2.out'
              });
            }"
            @mouseleave="() => {
              gsap.to('.jaguar-3d-wrapper', {
                rotateX: 0, rotateY: 0, x: 0, y: 0,
                duration: 1, ease: 'elastic.out(1, 0.4)'
              });
            }"
          >
            <!-- Círculo bien definido animado con scroll -->
            <div
              class="jaguar-halo absolute inset-[-30px] rounded-full will-change-transform"
              style="background: color-mix(in srgb, var(--cyd-green) 35%, transparent); border: 3px solid color-mix(in srgb, var(--cyd-green) 70%, transparent);"
            />
            
            <!-- Wrapper 3D para evitar conflicto con ScrollTrigger -->
            <div class="jaguar-3d-wrapper absolute inset-0 w-full h-full will-change-transform" style="transform-style: preserve-3d;">
              <!-- Jaguar Parado -->
              <img
                src="https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/Jaguarcin-3-cuartos-1761938045002.png?width=8000&height=8000&resize=contain"
                alt="Jaguar Parado"
                class="jaguar-stand absolute inset-0 z-10 w-full h-full object-contain drop-shadow-2xl will-change-transform"
                style="filter: drop-shadow(0 20px 50px color-mix(in srgb, var(--cyd-dark) 30%, transparent));"
              />
              
              <!-- Jaguar Saltando -->
              <img
                src="https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/jaguar-saltando-1761938986560.png?width=8000&height=8000&resize=contain"
                alt="Jaguar Saltando"
                class="jaguar-jump absolute inset-0 z-20 w-full h-full object-contain drop-shadow-2xl will-change-transform opacity-0"
                style="filter: drop-shadow(0 20px 50px color-mix(in srgb, var(--cyd-dark) 30%, transparent));"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Valores -->
      <div class="mb-16 lg:mb-28">
        <div class="text-center mb-10 lg:mb-14">
          <span class="cyd-label mb-4 inline-block">Nuestros Pilares</span>
          <h3
            class="text-3xl font-bold"
            style="font-family: var(--font-display); letter-spacing: -0.025em; color: var(--cyd-dark);"
          >
            Valores que nos <span class="cyd-accent">definen</span>
          </h3>
        </div>

        <div class="valores-grid grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
          <div
            v-for="(valor, i) in valores"
            :key="i"
            class="valor-card group cyd-card p-7 text-center will-change-transform"
          >
            <!-- Letra grande decorativa -->
            <div
              class="text-7xl font-black leading-none mb-4 select-none transition-transform duration-500 group-hover:scale-110"
              :style="{
                fontFamily: 'var(--font-display)',
                color: `color-mix(in srgb, ${valor.color} 15%, transparent)`,
              }"
            >
              {{ valor.letter }}
            </div>

            <h4
              class="text-lg font-bold mb-2"
              :style="{ color: valor.color }"
            >
              {{ valor.title }}
            </h4>
            <p class="text-sm leading-relaxed" style="color: hsl(var(--muted-foreground));">
              {{ valor.description }}
            </p>

            <div
              class="cyd-divider mx-auto mt-5 transition-all duration-500 group-hover:w-full"
              :style="{ background: `linear-gradient(90deg, ${valor.color}, color-mix(in srgb, ${valor.color} 50%, var(--cyd-gold)))` }"
            />
          </div>
        </div>
      </div>

      <!-- Timeline -->
      <div class="timeline-section mb-16 lg:mb-28">
        <div class="text-center mb-10 lg:mb-16">
          <span class="cyd-label mb-4 inline-block">Cronología</span>
          <h3
            class="text-3xl font-bold"
            style="font-family: var(--font-display); letter-spacing: -0.025em; color: var(--cyd-dark);"
          >
            Nuestra <span class="cyd-accent">Historia</span>
          </h3>
        </div>

        <div class="relative max-w-4xl mx-auto">
          <!-- Línea central vertical -->
          <div
            class="timeline-line absolute left-1/2 top-0 bottom-0 w-[2px] will-change-transform hidden lg:block"
            style="
              transform-origin: top center;
              background: linear-gradient(180deg, var(--cyd-forest), var(--cyd-gold) 50%, var(--cyd-green));
              transform: translateX(-50%);
            "
            aria-hidden="true"
          />

          <div class="space-y-6 lg:space-y-10">
            <div
              v-for="(item, i) in timeline"
              :key="i"
              :class="[
                'timeline-card relative flex flex-col lg:flex-row items-center will-change-transform',
                i % 2 === 0 ? 'lg:flex-row' : 'lg:flex-row-reverse'
              ]"
            >
              <!-- Contenido -->
              <div :class="['w-full lg:w-[46%]', i % 2 === 0 ? 'lg:pr-12 lg:text-right' : 'lg:pl-12']">
                <div
                  class="cyd-card p-7 group hover:shadow-[0_20px_50px_rgba(0,0,0,0.1)]"
                >
                  <!-- Año -->
                  <div
                    class="text-5xl font-black mb-1 leading-none"
                    :style="{ color: item.color, fontFamily: 'var(--font-display)', letterSpacing: '-0.04em' }"
                  >
                    {{ item.year }}
                  </div>

                  <h4
                    class="text-xl font-bold mb-2"
                    style="font-family: var(--font-display); color: var(--cyd-dark);"
                  >
                    {{ item.title }}
                  </h4>
                  <p class="text-sm leading-relaxed" style="color: hsl(var(--muted-foreground));">
                    {{ item.description }}
                  </p>
                </div>
              </div>

              <!-- Punto central -->
              <div class="hidden lg:flex w-[8%] justify-center relative z-10">
                <div
                  class="w-5 h-5 rounded-full border-4 border-white shadow-md"
                  :style="{ background: item.color }"
                />
              </div>

              <!-- Espacio vacío lado opuesto -->
              <div class="hidden lg:block w-[46%]" />
            </div>
          </div>
        </div>
      </div>

      <!-- Logros numéricos -->
      <div class="grid md:grid-cols-3 gap-6">
        <div
          v-for="(logro, i) in logros"
          :key="i"
          class="cyd-card p-10 text-center"
        >
          <div
            class="logro-number text-5xl lg:text-6xl font-black mb-1 leading-none will-change-transform"
            :data-end="logro.number"
            :data-suffix="logro.suffix"
            :style="{ color: logro.color, fontFamily: 'var(--font-display)', letterSpacing: '-0.04em' }"
          >0</div>
          <div
            class="text-base font-semibold mb-0.5"
            style="color: var(--cyd-dark);"
          >{{ logro.unit }}</div>
          <div class="text-sm" style="color: hsl(var(--muted-foreground));">
            {{ logro.label }}
          </div>
          <div
            class="cyd-divider mx-auto mt-4"
            :style="{ background: `linear-gradient(90deg, ${logro.color}, color-mix(in srgb, ${logro.color} 50%, var(--cyd-gold)))` }"
          />
        </div>
      </div>

    </div>
  </section>
</template>
