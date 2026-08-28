<script setup>
import { Card } from "@/components/ui/card"
import { onMounted, onUnmounted, ref } from "vue"
import { Facebook, Share2, ThumbsUp, Instagram } from "lucide-vue-next"
import { gsap } from '@/lib/gsap.js'

let fbCtx = null;

onMounted(() => {
  if (typeof window !== 'undefined') {
    // No necesitamos el SDK de JS de Facebook porque usaremos el iframe
    // para evitar los errores masivos en la consola.

    // GSAP Animations
    fbCtx = gsap.context(() => {
      gsap.from('.fb-header-anim', {
        scrollTrigger: { trigger: '#facebook', start: 'top 80%' },
        opacity: 0,
        y: 30,
        duration: 0.8,
        ease: 'power3.out'
      })

      gsap.from('.fb-feed-anim', {
        scrollTrigger: { trigger: '.fb-feed-anim', start: 'top 85%' },
        opacity: 0,
        x: -30,
        duration: 0.8,
        ease: 'power3.out'
      })

      gsap.from('.fb-cards-anim', {
        scrollTrigger: { trigger: '.fb-cards-anim', start: 'top 85%' },
        opacity: 0,
        x: 30,
        duration: 0.8,
        stagger: 0.15,
        ease: 'power3.out'
      })
    })
  }
})

onUnmounted(() => {
  fbCtx?.revert()
})
</script>

<template>
  <section id="facebook" class="py-12 sm:py-16 lg:py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-white via-blue-50 to-white relative overflow-hidden">
    <!-- Background Decoration -->
    <div class="absolute top-0 right-0 w-64 sm:w-96 h-64 sm:h-96 bg-blue-200/30 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 w-64 sm:w-96 h-64 sm:h-96 bg-purple-200/30 rounded-full blur-3xl"></div>
    
    <div class="max-w-7xl mx-auto relative">
      <div class="text-center mb-8 sm:mb-12 lg:mb-16 fb-header-anim">
        <div class="inline-flex items-center space-x-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-full px-4 sm:px-6 py-2 mb-4 shadow-lg text-sm sm:text-base">
          <Facebook class="w-4 h-4 sm:w-5 sm:h-5" />
          <span class="font-bold">Síguenos en Redes Sociales</span>
        </div>
        <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl font-bold bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 bg-clip-text text-transparent mb-4 px-4">
          ¡Mantente Conectado con Nosotros!
        </h2>
        <p class="text-base sm:text-lg lg:text-xl text-gray-600 max-w-3xl mx-auto px-4">
          Descubre nuestras últimas publicaciones, eventos y logros en redes sociales
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8 items-start">
        <!-- Main Facebook Feed -->
        <div class="lg:col-span-2 order-1 lg:order-2 fb-feed-anim">
          <Card class="relative overflow-hidden bg-white/80 backdrop-blur-sm border-2 border-blue-200/50 shadow-2xl p-4 sm:p-6 hover:shadow-[0_0_60px_rgba(59,130,246,0.3)] transition-all duration-500">
            <div class="absolute -top-20 -right-20 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl animate-pulse"></div>
            
            <div class="relative">
              <!-- Header -->
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 pb-4 border-b-2 border-blue-200/50">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center shadow-lg flex-shrink-0">
                    <Facebook class="w-5 h-5 sm:w-6 sm:h-6 text-white" />
                  </div>
                  <div class="min-w-0">
                    <h3 class="text-lg sm:text-xl font-bold text-gray-900 truncate">Ciencia y Desarrollo</h3>
                    <p class="text-xs sm:text-sm text-gray-600">@CienciayDesarrollo</p>
                  </div>
                </div>
                <a
                  href="https://www.facebook.com/CienciayDesarrollo"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="group/btn inline-flex items-center justify-center space-x-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-full px-4 sm:px-6 py-2 sm:py-2.5 font-semibold shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300 text-sm sm:text-base whitespace-nowrap">
                  <span>Ver en Facebook</span>
                  <span class="text-lg group-hover/btn:scale-125 transition-transform">👉</span>
                </a>
              </div>

              <!-- Facebook Page Plugin -->
              <div class="relative w-full overflow-hidden">
                <iframe
                  src="https://www.facebook.com/plugins/page.php?href=https%3A%2F%2Fwww.facebook.com%2FCienciayDesarrollo&tabs=timeline&width=500&height=700&small_header=false&adapt_container_width=true&hide_cover=false&show_facepile=true&appId"
                  width="100%"
                  height="700"
                  style="border:none;overflow:hidden;min-width:300px;"
                  scrolling="no"
                  frameborder="0"
                  allowfullscreen="true"
                  allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share">
                </iframe>
              </div>
            </div>
          </Card>

          <!-- Social Media Buttons -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mt-4 sm:mt-6">
            <!-- Facebook Button -->
            <a
              href="https://www.facebook.com/CienciayDesarrollo"
              target="_blank"
              rel="noopener noreferrer"
              class="group relative overflow-hidden p-4 sm:p-6 rounded-2xl bg-gradient-to-br from-blue-600 to-blue-700 text-white shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all duration-300">
              <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>
              <div class="relative flex flex-col items-center justify-center space-y-2 sm:space-y-3">
                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                  <Facebook class="w-6 h-6 sm:w-8 sm:h-8" />
                </div>
                <div class="text-center">
                  <p class="font-bold text-base sm:text-lg">Facebook</p>
                  <p class="text-xs text-white/80">@CienciayDesarrollo</p>
                </div>
              </div>
            </a>

            <!-- Instagram Button -->
            <a
              href="https://www.instagram.com/ciencia_y_desarrollo/"
              target="_blank"
              rel="noopener noreferrer"
              class="group relative overflow-hidden p-4 sm:p-6 rounded-2xl bg-gradient-to-br from-pink-600 via-purple-600 to-indigo-600 text-white shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all duration-300">
              <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>
              <div class="relative flex flex-col items-center justify-center space-y-2 sm:space-y-3">
                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                  <Instagram class="w-6 h-6 sm:w-8 sm:h-8" />
                </div>
                <div class="text-center">
                  <p class="font-bold text-base sm:text-lg">Instagram</p>
                  <p class="text-xs text-white/80">@ciencia_y_desarrollo</p>
                </div>
              </div>
            </a>

            <!-- TikTok Button -->
            <a
              href="https://www.tiktok.com/@colegiocydsalama"
              target="_blank"
              rel="noopener noreferrer"
              class="group relative overflow-hidden p-4 sm:p-6 rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-black text-white shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all duration-300">
              <div class="absolute top-0 right-0 w-32 h-32 bg-cyan-500/20 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>
              <div class="relative flex flex-col items-center justify-center space-y-2 sm:space-y-3">
                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-white/10 backdrop-blur-sm flex items-center justify-center group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                  <svg class="w-6 h-6 sm:w-8 sm:h-8" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
                  </svg>
                </div>
                <div class="text-center">
                  <p class="font-bold text-base sm:text-lg">TikTok</p>
                  <p class="text-xs text-white/80">@colegiocydsalama</p>
                </div>
              </div>
            </a>
          </div>
        </div>

        <!-- Info Cards -->
        <div class="space-y-4 sm:space-y-6 order-2 lg:order-1 fb-cards-anim">
          <Card class="group relative overflow-hidden p-4 sm:p-6 bg-gradient-to-br from-blue-600 to-indigo-600 text-white border-0 shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-500">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
              <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center mb-3 sm:mb-4 group-hover:scale-110 transition-transform">
                <ThumbsUp class="w-6 h-6 sm:w-8 sm:h-8" />
              </div>
              <h3 class="text-xl sm:text-2xl font-bold mb-2">Síguenos</h3>
              <p class="text-white/90 text-xs sm:text-sm leading-relaxed">
                Dale "Me gusta" a nuestra página y mantente al día con todas nuestras noticias y actividades
              </p>
            </div>
          </Card>

          <Card class="group relative overflow-hidden p-4 sm:p-6 bg-gradient-to-br from-indigo-600 to-purple-600 text-white border-0 shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-500">
            <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
              <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center mb-3 sm:mb-4 group-hover:scale-110 transition-transform">
                <Share2 class="w-6 h-6 sm:w-8 sm:h-8" />
              </div>
              <h3 class="text-xl sm:text-2xl font-bold mb-2">Comparte</h3>
              <p class="text-white/90 text-xs sm:text-sm leading-relaxed">
                Comparte nuestras publicaciones con tu familia y amigos para que conozcan más sobre nosotros
              </p>
            </div>
          </Card>

          <Card class="group relative overflow-hidden p-4 sm:p-6 bg-gradient-to-br from-purple-600 to-pink-600 text-white border-0 shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-500">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
              <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center mb-3 sm:mb-4 group-hover:scale-110 transition-transform">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
              </div>
              <h3 class="text-xl sm:text-2xl font-bold mb-2">Interactúa</h3>
              <p class="text-white/90 text-xs sm:text-sm leading-relaxed">
                Comenta, reacciona y participa en nuestras publicaciones directamente desde aquí
              </p>
            </div>
          </Card>
        </div>
      </div>
    </div>
  </section>
</template>
