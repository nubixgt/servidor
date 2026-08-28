<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { gsap } from '@/lib/gsap.js'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'

const sectionRef = ref(null)
let ctx = null

const formData = ref({
  nombre: '',
  email: '',
  telefono: '',
  mensaje: '',
})

const isSending = ref(false)
const isSent = ref(false)

const handleSubmit = async (e) => {
  e.preventDefault()
  isSending.value = true
  await new Promise((r) => setTimeout(r, 1200))
  isSending.value = false
  isSent.value = true
  setTimeout(() => { isSent.value = false }, 4000)
}

const contactInfo = [
  {
    label: 'Dirección',
    value: 'Salamá, Baja Verapaz\nGuatemala',
    accent: 'var(--cyd-forest)',
    // SVG de pin de mapa propio
    svg: `<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
      <circle cx="11" cy="9" r="3" stroke="currentColor" stroke-width="1.6"/>
      <path d="M11 2C7.13 2 4 5.13 4 9c0 5 7 11 7 11s7-6 7-11c0-3.87-3.13-7-7-7z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
    </svg>`,
  },
  {
    label: 'Teléfono',
    value: '+502 5700-1515\n+502 1234-5678',
    accent: 'var(--cyd-green)',
    svg: `<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M7 3h-2a2 2 0 00-2 2v1c0 9.39 7.61 17 17 17h1a2 2 0 002-2v-2a1 1 0 00-.55-.9l-4-2a1 1 0 00-1.19.29l-1.5 1.84A13.97 13.97 0 016.77 11.24l1.84-1.5a1 1 0 00.29-1.19l-2-4A1 1 0 007 4z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
    </svg>`,
  },
  {
    label: 'Email',
    value: 'info@colegiocyd.edu.gt\ninscripciones@colegiocyd.edu.gt',
    accent: 'var(--cyd-gold)',
    svg: `<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
      <rect x="2" y="5" width="18" height="13" rx="2" stroke="currentColor" stroke-width="1.6"/>
      <path d="M2 8l9 5 9-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
    </svg>`,
  },
  {
    label: 'Horario',
    value: 'Lunes a Viernes: 7:00 – 17:00\nSábados: 8:00 – 12:00',
    accent: '#4d6cc4',
    svg: `<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
      <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="1.6"/>
      <path d="M11 6v5l3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>`,
  },
]

onMounted(() => {
  ctx = gsap.context(() => {
    gsap.from('.contact-header', {
      opacity: 0, y: 50, duration: 0.9, ease: 'power3.out',
      scrollTrigger: { trigger: '.contact-header', start: 'top 80%' },
    })
    gsap.from('.contact-info-card', {
      opacity: 0, y: 30, duration: 0.7, stagger: 0.1, ease: 'power3.out',
      scrollTrigger: { trigger: '.contact-info-list', start: 'top 85%' },
    })
    gsap.from('.contact-form-wrap', {
      opacity: 0, y: 30, duration: 0.9, ease: 'power3.out',
      scrollTrigger: { trigger: '.contact-form-wrap', start: 'top 85%' },
    })
  }, sectionRef.value)
})

onUnmounted(() => { ctx?.revert() })
</script>

<template>
  <section
    id="contacto"
    ref="sectionRef"
    class="py-16 lg:py-32 relative overflow-hidden cyd-section-bg"
  >
    <!-- Fondo decorativo -->
    <div class="absolute inset-0" aria-hidden="true">
      <div
        class="absolute bottom-0 right-0 w-[500px] h-[500px] rounded-full"
        style="background: radial-gradient(circle, color-mix(in srgb, #4d6cc4 6%, transparent), transparent 70%); filter: blur(80px);"
      />
    </div>

    <div class="relative cyd-container">

      <!-- Header -->
      <div class="contact-header text-center max-w-2xl mx-auto mb-12 lg:mb-20">
        <span class="cyd-label mb-5 inline-block">Contáctanos</span>
        <h2 class="cyd-title mb-5">
          Únete a nuestra <span class="cyd-accent">Familia</span>
        </h2>
        <p class="text-base lg:text-lg" style="color: hsl(var(--muted-foreground));">
          ¿Listo para ser parte de nuestra familia educativa? Estamos aquí para ayudarte.
        </p>
      </div>

      <div class="grid lg:grid-cols-2 gap-10 lg:gap-12 items-start">

        <!-- Info de contacto -->
        <div>
          <h3
            class="text-2xl font-bold mb-8"
            style="font-family: var(--font-display); color: var(--cyd-dark); letter-spacing: -0.025em;"
          >
            Información de Contacto
          </h3>

          <div class="contact-info-list space-y-3 lg:space-y-4">
            <div
              v-for="(info, i) in contactInfo"
              :key="i"
              class="contact-info-card cyd-card flex items-start gap-4 p-5"
            >
              <!-- Ícono SVG propio -->
              <div
                class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                :style="{ background: `color-mix(in srgb, ${info.accent} 12%, transparent)`, color: info.accent }"
                v-html="info.svg"
              />
              <div class="min-w-0 flex-1">
                <div
                  class="text-xs font-semibold tracking-wider uppercase mb-1"
                  :style="{ color: info.accent }"
                >
                  {{ info.label }}
                </div>
                <p
                  class="text-sm leading-relaxed whitespace-pre-line break-words"
                  style="color: hsl(var(--foreground)); word-break: break-word;"
                >
                  {{ info.value }}
                </p>
              </div>
            </div>
          </div>

          <!-- WhatsApp directo -->
          <div class="mt-6">
            <a
              href="https://api.whatsapp.com/send?phone=50257001515&text=Hola,%20necesito%20información%20sobre%20el%20Colegio%20CYD."
              target="_blank"
              rel="noopener noreferrer"
              class="cyd-btn-primary inline-flex group"
              style="background: linear-gradient(135deg, #25d366, #128c7e);"
            >
              <!-- WhatsApp Oficial Premium -->
              <span style="position:relative;z-index:1;display:flex;align-items:center;gap:8px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="transition-transform duration-300 group-hover:scale-110">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                WhatsApp Directo
              </span>
            </a>
          </div>
        </div>

        <!-- Formulario -->
        <div class="contact-form-wrap cyd-card p-6 sm:p-8 lg:p-10">
          <h3
            class="text-2xl font-bold mb-7"
            style="font-family: var(--font-display); color: var(--cyd-dark); letter-spacing: -0.025em;"
          >
            Solicita Información
          </h3>

          <Transition
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
          >
            <div
              v-if="isSent"
              class="mb-6 rounded-xl p-4 flex items-center gap-3"
              style="background: color-mix(in srgb, var(--cyd-green) 10%, transparent); border: 1px solid color-mix(in srgb, var(--cyd-green) 25%, transparent);"
            >
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <circle cx="10" cy="10" r="9" stroke="var(--cyd-green)" stroke-width="1.5"/>
                <path d="M6 10l3 3 5-5" stroke="var(--cyd-green)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span class="text-sm font-medium" style="color: var(--cyd-forest);">
                ¡Mensaje enviado! Nos pondremos en contacto pronto.
              </span>
            </div>
          </Transition>

          <form @submit.prevent="handleSubmit" class="space-y-5">
            <div>
              <label class="block text-xs font-semibold mb-2 uppercase tracking-wider" style="color: var(--cyd-forest);">
                Nombre Completo *
              </label>
              <Input
                type="text"
                placeholder="Tu nombre completo"
                v-model="formData.nombre"
                required
                class="cyd-input"
              />
            </div>

            <div class="grid sm:grid-cols-2 gap-3 lg:gap-4">
              <div>
                <label class="block text-xs font-semibold mb-2 uppercase tracking-wider" style="color: var(--cyd-forest);">
                  Email *
                </label>
                <Input
                  type="email"
                  placeholder="tu@email.com"
                  v-model="formData.email"
                  required
                  class="cyd-input"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold mb-2 uppercase tracking-wider" style="color: var(--cyd-forest);">
                  Teléfono *
                </label>
                <Input
                  type="tel"
                  placeholder="+502 0000-0000"
                  v-model="formData.telefono"
                  required
                  class="cyd-input"
                />
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold mb-2 uppercase tracking-wider" style="color: var(--cyd-forest);">
                Mensaje
              </label>
              <Textarea
                placeholder="Cuéntanos sobre tu interés en el colegio..."
                v-model="formData.mensaje"
                :rows="4"
                class="cyd-input resize-none"
              />
            </div>

            <button
              type="submit"
              class="cyd-btn-primary w-full justify-center py-4 group"
              :disabled="isSending"
            >
              <span v-if="!isSending" style="position:relative;z-index:1;display:flex;align-items:center;gap:8px;">
                Enviar Mensaje
                <svg class="transition-transform duration-300 group-hover:translate-x-2" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true" style="position:relative;z-index:1;">
                  <path d="M1 8h14M9 3l6 5-6 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
              <span v-else style="position:relative;z-index:1;display:flex;align-items:center;gap:8px;">
                <svg class="animate-spin" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                  <circle cx="8" cy="8" r="6" stroke="white" stroke-width="2" stroke-dasharray="25 12"/>
                </svg>
                Enviando...
              </span>
            </button>
          </form>
        </div>
      </div>
    </div>
  </section>
</template>
