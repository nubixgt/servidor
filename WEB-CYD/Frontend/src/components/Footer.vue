<script setup>
import { onMounted, onUnmounted } from 'vue'
import { MapPin, Phone, Mail, Clock, Facebook, Instagram, ChevronRight } from 'lucide-vue-next'
import { gsap } from '@/lib/gsap.js'

const scrollToSection = (id) => {
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' })
}

const currentYear = new Date().getFullYear()

const links = [
  { id: 'inicio',      label: 'Inicio' },
  { id: 'niveles',     label: 'Niveles Educativos' },
  { id: 'actividades', label: 'Actividades' },
  { id: 'nosotros',    label: 'Sobre Nosotros' },
  { id: 'galeria',     label: 'Galería' },
  { id: 'contacto',    label: 'Contacto' },
]

const niveles = ['Preprimaria', 'Primaria', 'Básicos', 'Diversificado', 'Bachillerato en Ciencias', 'Perito Contador']

let footerCtx = null

onMounted(() => {
  footerCtx = gsap.context(() => {
    // Animación de entrada de las columnas del footer
    gsap.from('.footer-col', {
      scrollTrigger: { trigger: 'footer', start: 'top 90%' },
      y: 30,
      opacity: 0,
      duration: 0.8,
      stagger: 0.1,
      ease: 'power3.out'
    })

    // Parallax muy suave para el jaguar del footer
    gsap.fromTo('.footer-jaguar-bg',
      { y: 50, scale: 0.95 },
      {
        scrollTrigger: {
          trigger: 'footer',
          start: 'top bottom',
          end: 'bottom bottom',
          scrub: 1.5
        },
        y: -20,
        scale: 1.05,
        ease: 'none'
      }
    )
  })
})

onUnmounted(() => {
  footerCtx?.revert()
})
</script>

<template>
  <footer
    class="relative overflow-hidden"
    style="background: var(--cyd-dark); color: rgba(255,255,255,0.85);"
  >
    <!-- Textura y decoración -->
    <div class="absolute inset-0" aria-hidden="true">
      <div class="absolute inset-0 cyd-dots opacity-[0.06]" />
      <!-- Jaguar watermark -->
      <div
        class="footer-jaguar-bg absolute bottom-0 right-0 w-[480px] h-[480px] opacity-[0.04] pointer-events-none select-none"
        style="background: url('https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/Jaguarcin-3-cuartos-1761938045002.png?width=800&height=800&resize=contain') center/contain no-repeat;"
      />
      <!-- Gradiente de acento superior -->
      <div
        class="absolute top-0 left-0 right-0 h-[1px]"
        style="background: linear-gradient(90deg, transparent, var(--cyd-green), var(--cyd-gold), transparent);"
      />
    </div>

    <!-- Contenido -->
    <div class="relative cyd-container pt-16 pb-8">
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-12 mb-14">

        <!-- Logo + Descripción -->
        <div class="space-y-5">
          <div class="flex items-center gap-3">
            <div class="relative w-12 h-12 shrink-0">
              <div
                class="absolute inset-0 rounded-full"
                style="background: linear-gradient(135deg, var(--cyd-green), var(--cyd-gold)); opacity: 0.15;"
              />
              <img
                src="https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/LOGO-2020-1761860820111.png?width=100&height=100&resize=contain"
                alt="Logo CYD"
                class="absolute inset-0 object-contain w-full h-full p-1"
              />
            </div>
            <div>
              <div
                class="font-bold text-lg leading-tight"
                style="font-family: var(--font-display); letter-spacing: -0.02em;"
              >
                Colegio CYD
              </div>
              <div
                class="text-[10px] tracking-[0.15em] uppercase"
                style="color: var(--cyd-lime);"
              >
                Ciencia &amp; Desarrollo
              </div>
            </div>
          </div>

          <p class="text-sm leading-relaxed" style="color: rgba(255,255,255,0.5);">
            Formando líderes con valores desde 1992. Educación de excelencia en Salamá, Baja Verapaz, Guatemala.
          </p>

          <!-- Redes sociales — rediseñadas con SVG más grandes y colores de marca -->
          <div class="flex gap-3">
            <!-- Facebook -->
            <a
              href="https://facebook.com"
              target="_blank"
              rel="noopener noreferrer"
              class="social-btn group relative w-11 h-11 rounded-xl flex items-center justify-center transition-all duration-300 overflow-hidden"
              style="background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.8);"
              @mouseenter="(e) => {
                e.currentTarget.style.background = '#1877f2';
                e.currentTarget.style.color = 'white';
                e.currentTarget.style.transform = 'translateY(-3px)';
                e.currentTarget.style.boxShadow = '0 8px 20px rgba(24,119,242,0.4)';
              }"
              @mouseleave="(e) => {
                e.currentTarget.style.background = 'rgba(255,255,255,0.08)';
                e.currentTarget.style.color = 'rgba(255,255,255,0.8)';
                e.currentTarget.style.transform = 'translateY(0)';
                e.currentTarget.style.boxShadow = 'none';
              }"
              aria-label="Facebook"
            >
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.234 2.686.234v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/>
              </svg>
            </a>

            <!-- Instagram -->
            <a
              href="https://instagram.com"
              target="_blank"
              rel="noopener noreferrer"
              class="social-btn group relative w-11 h-11 rounded-xl flex items-center justify-center transition-all duration-300"
              style="background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.8);"
              @mouseenter="(e) => {
                e.currentTarget.style.background = 'linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888)';
                e.currentTarget.style.color = 'white';
                e.currentTarget.style.transform = 'translateY(-3px)';
                e.currentTarget.style.boxShadow = '0 8px 20px rgba(220,39,67,0.4)';
              }"
              @mouseleave="(e) => {
                e.currentTarget.style.background = 'rgba(255,255,255,0.08)';
                e.currentTarget.style.color = 'rgba(255,255,255,0.8)';
                e.currentTarget.style.transform = 'translateY(0)';
                e.currentTarget.style.boxShadow = 'none';
              }"
              aria-label="Instagram"
            >
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
              </svg>
            </a>

            <!-- WhatsApp -->
            <a
              href="https://api.whatsapp.com/send?phone=50257001515"
              target="_blank"
              rel="noopener noreferrer"
              class="social-btn group relative w-11 h-11 rounded-xl flex items-center justify-center transition-all duration-300"
              style="background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.8);"
              @mouseenter="(e) => {
                e.currentTarget.style.background = '#25d366';
                e.currentTarget.style.color = 'white';
                e.currentTarget.style.transform = 'translateY(-3px)';
                e.currentTarget.style.boxShadow = '0 8px 20px rgba(37,211,102,0.4)';
              }"
              @mouseleave="(e) => {
                e.currentTarget.style.background = 'rgba(255,255,255,0.08)';
                e.currentTarget.style.color = 'rgba(255,255,255,0.8)';
                e.currentTarget.style.transform = 'translateY(0)';
                e.currentTarget.style.boxShadow = 'none';
              }"
              aria-label="WhatsApp"
            >
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
              </svg>
            </a>
          </div>
        </div>

        <!-- Enlaces Rápidos -->
        <div>
          <h4
            class="text-sm font-semibold mb-5 uppercase tracking-[0.12em]"
            style="color: var(--cyd-lime);"
          >
            Enlaces Rápidos
          </h4>
          <ul class="space-y-2.5">
            <li v-for="link in links" :key="link.id">
              <button
                @click="scrollToSection(link.id)"
                class="text-sm transition-colors duration-200 flex items-center gap-2 group"
                style="color: rgba(255,255,255,0.5);"
                @mouseenter="(e) => e.currentTarget.style.color='white'"
                @mouseleave="(e) => e.currentTarget.style.color='rgba(255,255,255,0.5)'"
              >
                <span
                  class="w-3 h-[1px] transition-all duration-300 group-hover:w-5"
                  style="background: var(--cyd-green);"
                />
                {{ link.label }}
              </button>
            </li>
          </ul>
        </div>

        <!-- Niveles -->
        <div>
          <h4
            class="text-sm font-semibold mb-5 uppercase tracking-[0.12em]"
            style="color: var(--cyd-lime);"
          >
            Niveles
          </h4>
          <ul class="space-y-2.5">
            <li
              v-for="nivel in niveles"
              :key="nivel"
              class="text-sm transition-colors duration-200 cursor-default"
              style="color: rgba(255,255,255,0.5);"
              @mouseenter="(e) => e.currentTarget.style.color='white'"
              @mouseleave="(e) => e.currentTarget.style.color='rgba(255,255,255,0.5)'"
            >
              {{ nivel }}
            </li>
          </ul>
        </div>

        <!-- Contacto -->
        <div>
          <h4
            class="text-sm font-semibold mb-5 uppercase tracking-[0.12em]"
            style="color: var(--cyd-lime);"
          >
            Contacto
          </h4>
          <ul class="space-y-4">
            <li class="flex items-start gap-3 text-sm" style="color: rgba(255,255,255,0.5);">
              <svg class="shrink-0 mt-0.5" width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <circle cx="8" cy="7" r="2.5" stroke="currentColor" stroke-width="1.4"/>
                <path d="M8 1.5C5.0 1.5 2.5 4 2.5 7c0 4.5 5.5 8.5 5.5 8.5S13.5 11.5 13.5 7c0-3-2.5-5.5-5.5-5.5z" stroke="currentColor" stroke-width="1.4"/>
              </svg>
              Salamá, Baja Verapaz, Guatemala
            </li>
            <li class="flex items-start gap-3 text-sm" style="color: rgba(255,255,255,0.5);">
              <svg class="shrink-0 mt-0.5" width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M5.5 2.5h-1.5a1.5 1.5 0 00-1.5 1.5v.75c0 7 5.75 12.75 12.75 12.75h.75a1.5 1.5 0 001.5-1.5v-1.5l-3-1.5-1.12 1.38A10.5 10.5 0 015.62 8.87L7 7.75 5.5 4.75z" stroke="currentColor" stroke-width="1.3"/>
              </svg>
              <div>
                <div>+502 5700-1515</div>
                <div>+502 1234-5678</div>
              </div>
            </li>
            <li class="flex items-start gap-3 text-sm" style="color: rgba(255,255,255,0.5);">
              <svg class="shrink-0 mt-0.5" width="14" height="14" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <rect x="1.5" y="4" width="13" height="9" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                <path d="M1.5 6l6.5 4 6.5-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
              </svg>
              <div>
                <div>info@colegiocyd.edu.gt</div>
                <div>inscripciones@colegiocyd.edu.gt</div>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <!-- Línea divisora -->
      <div
        class="h-[1px] mb-6"
        style="background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);"
      />

      <!-- Bottom bar -->
      <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
        <p class="text-xs" style="color: rgba(255,255,255,0.35);">
          © {{ currentYear }} Colegio Particular Mixto CYD. Todos los derechos reservados.
        </p>
        <div class="flex gap-5 text-xs" style="color: rgba(255,255,255,0.35);">
          <a href="#" class="hover:text-white transition-colors">Privacidad</a>
          <a href="#" class="hover:text-white transition-colors">Términos</a>
        </div>
      </div>
    </div>
  </footer>
</template>
