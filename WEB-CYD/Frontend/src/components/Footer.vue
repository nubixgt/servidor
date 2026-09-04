<script setup>
import { onMounted, onUnmounted } from 'vue'
import { gsap } from '@/lib/gsap.js'

const scrollToSection = (id) => {
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' })
}

const currentYear = new Date().getFullYear()

const linksColumn1 = [
  { id: 'inicio',      label: 'Inicio' },
  { id: 'nosotros',    label: 'Nosotros' },
  { id: 'oferta',      label: 'Oferta Académica' },
  { id: 'admisiones',  label: 'Admisiones' }
]
const linksColumn2 = [
  { id: 'vida',        label: 'Vida Estudiantil' },
  { id: 'servicios',   label: 'Servicios' },
  { id: 'contacto',    label: 'Contacto' }
]

let footerCtx = null

onMounted(() => {
  footerCtx = gsap.context(() => {
    gsap.from('.footer-col', {
      scrollTrigger: { trigger: 'footer', start: 'top 90%' },
      y: 20,
      opacity: 0,
      duration: 0.6,
      stagger: 0.1,
      ease: 'power3.out'
    })
  })
})

onUnmounted(() => {
  footerCtx?.revert()
})
</script>

<template>
  <footer class="bg-white pt-16 pb-8 border-t border-slate-100">
    <div class="cyd-container">
      
      <div class="grid grid-cols-1 md:grid-cols-12 gap-10 lg:gap-8 items-start mb-12">
        
        <!-- Logo & Social (Columna 1) -->
        <div class="footer-col md:col-span-3">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12">
              <img src="https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/LOGO-2020-1761860820111.png" alt="Logo CYD" class="w-full h-full object-contain" />
            </div>
            <div>
              <div class="font-black text-xl leading-none text-slate-800" style="font-family: var(--font-display);">CYD</div>
              <div class="text-[0.6rem] font-bold text-green-700 tracking-wider">CIENCIA Y DISCIPLINA</div>
            </div>
          </div>
          <p class="text-sm text-slate-600 mb-6 font-medium">
            para formar líderes del mañana.
          </p>
          <div class="flex gap-4 text-slate-400">
            <a href="#" class="hover:text-green-700 transition-colors">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
            </a>
            <a href="#" class="hover:text-green-700 transition-colors">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
            </a>
            <a href="#" class="hover:text-green-700 transition-colors">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.5 12 3.5 12 3.5s-7.505 0-9.377.55a3.016 3.016 0 0 0-2.122 2.136C0 8.083 0 12 0 12s0 3.917.501 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.55 9.377.55 9.377.55s7.505 0 9.377-.55a3.016 3.016 0 0 0 2.122-2.136C24 15.917 24 12 24 12s0-3.917-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
            </a>
            <a href="#" class="hover:text-green-700 transition-colors">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>
            </a>
          </div>
        </div>

        <!-- Enlaces Rápidos (Columna 2 y 3 juntas visualmente) -->
        <div class="footer-col md:col-span-4 lg:col-span-3 lg:col-start-5">
          <h4 class="text-xs font-bold text-green-800 uppercase tracking-widest mb-6">Enlaces Rápidos</h4>
          <div class="flex gap-8">
            <ul class="space-y-3">
              <li v-for="link in linksColumn1" :key="link.id">
                <button @click="scrollToSection(link.id)" class="text-sm font-medium text-slate-500 hover:text-green-700 transition-colors text-left">
                  {{ link.label }}
                </button>
              </li>
            </ul>
            <ul class="space-y-3">
              <li v-for="link in linksColumn2" :key="link.id">
                <button @click="scrollToSection(link.id)" class="text-sm font-medium text-slate-500 hover:text-green-700 transition-colors text-left">
                  {{ link.label }}
                </button>
              </li>
            </ul>
          </div>
        </div>

        <!-- Contacto (Columna 4) -->
        <div class="footer-col md:col-span-3 lg:col-span-3">
          <h4 class="text-xs font-bold text-green-800 uppercase tracking-widest mb-6">Contáctanos</h4>
          <ul class="space-y-4">
            <li class="flex items-start gap-3">
              <svg class="w-5 h-5 text-green-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span class="text-sm font-medium text-slate-500 leading-tight">Salamá, Baja Verapaz<br>Guatemala, C.A.</span>
            </li>
            <li class="flex items-start gap-3">
              <svg class="w-5 h-5 text-green-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
              <span class="text-sm font-medium text-slate-500">+502 7956-4500</span>
            </li>
            <li class="flex items-start gap-3">
              <svg class="w-5 h-5 text-green-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              <span class="text-sm font-medium text-slate-500">info@cyd.com.gt</span>
            </li>
          </ul>
        </div>

        <!-- Escudo (Columna 5) -->
        <div class="footer-col md:col-span-2 lg:col-span-2 flex justify-start md:justify-end items-center">
          <!-- TODO: Provide the real seal image, this is a placeholder matching the general vibe -->
          <img src="https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/LOGO-2020-1761860820111.png" alt="Escudo CYD" class="w-24 h-24 object-contain opacity-80" />
        </div>

      </div>

      <!-- Copyright Bar -->
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4 pt-6 border-t border-slate-100">
        <p class="text-xs font-medium text-slate-400">
          © {{ currentYear }} Colegio Particular Mixto CYD.
        </p>
        <div class="flex gap-6 text-xs font-medium text-slate-400">
          <a href="#" class="hover:text-green-700 transition-colors">Privacidad</a>
          <a href="#" class="hover:text-green-700 transition-colors">Términos</a>
        </div>
      </div>

    </div>
  </footer>
</template>

<style scoped>
</style>
