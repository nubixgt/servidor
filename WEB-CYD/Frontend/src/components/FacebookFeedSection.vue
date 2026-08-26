<script setup>
import { Card } from "@/components/ui/card"
import { onMounted } from "vue"
import { Facebook, Share2, ThumbsUp, Instagram } from "lucide-vue-next"

onMounted(() => {
  if (typeof window !== 'undefined') {
    if (window.FB) {
      window.FB.XFBML.parse()
    } else {
      window.fbAsyncInit = function() {
        window.FB.init({
          xfbml            : true,
          version          : 'v18.0'
        });
      };
      
      if (!document.getElementById('facebook-jssdk')) {
        const script = document.createElement('script')
        script.id = 'facebook-jssdk'
        script.src = 'https://connect.facebook.net/es_LA/sdk.js'
        script.async = true
        script.defer = true
        script.crossOrigin = 'anonymous'
        document.body.appendChild(script)
      }
    }
  }
})
</script>

<template>
  <section id="facebook" class="py-12 sm:py-16 lg:py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-white via-blue-50 to-white relative overflow-hidden">
    <!-- Background Decoration -->
    <div class="absolute top-0 right-0 w-64 sm:w-96 h-64 sm:h-96 bg-blue-200/30 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 w-64 sm:w-96 h-64 sm:h-96 bg-purple-200/30 rounded-full blur-3xl"></div>
    
    <div class="max-w-7xl mx-auto relative">
      <div class="text-center mb-8 sm:mb-12 lg:mb-16 animate-fade-in" v-motion :initial="{ opacity: 0, y: 20 }" :visible="{ opacity: 1, y: 0 }">
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
        <!-- Main Facebook Feed - Appears FIRST on mobile -->
        <div class="lg:col-span-2 order-1 lg:order-2" v-motion :initial="{ opacity: 0, x: -20 }" :visible="{ opacity: 1, x: 0 }" :delay="100">
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
              <div id="fb-root"></div>
              <div class="relative w-full overflow-hidden">
                <div
                  class="fb-page w-full"
                  data-href="https://www.facebook.com/CienciayDesarrollo"
                  data-tabs="timeline"
                  data-width="500"
                  data-height="700"
                  data-small-header="false"
                  data-adapt-container-width="true"
                  data-hide-cover="false"
                  data-show-facepile="true">
                  <blockquote cite="https://www.facebook.com/CienciayDesarrollo" class="fb-xfbml-parse-ignore">
                    <a href="https://www.facebook.com/CienciayDesarrollo">Ciencia y Desarrollo</a>
                  </blockquote>
                </div>
                
                <!-- Loading placeholder -->
                <div class="absolute inset-0 flex items-center justify-center bg-white/50 backdrop-blur-sm rounded-xl pointer-events-none opacity-0 animate-pulse">
                  <div class="text-center space-y-3">
                    <div class="w-12 h-12 sm:w-16 sm:h-16 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
                    <p class="text-xs sm:text-sm text-gray-600 font-medium px-4">Cargando contenido de Facebook...</p>
                  </div>
                </div>
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

          <!-- Social Stats -->
          <div class="grid grid-cols-3 gap-3 sm:gap-4 mt-4 sm:mt-6">
            <template v-for="(item, idx) in [
              { icon: '👥', label: 'Comunidad', gradient: 'from-blue-600 to-cyan-600' },
              { icon: '📸', label: 'Fotos y Videos', gradient: 'from-indigo-600 to-purple-600' },
              { icon: '🎉', label: 'Eventos', gradient: 'from-purple-600 to-pink-600' }
            ]" :key="idx">
              <Card class="group p-3 sm:p-4 text-center bg-white/80 backdrop-blur-sm border-2 border-border/50 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div :class="`absolute inset-0 bg-gradient-to-br ${item.gradient} opacity-0 group-hover:opacity-10 transition-opacity`"></div>
                <div class="relative">
                  <div class="text-2xl sm:text-3xl mb-1 sm:mb-2 group-hover:scale-125 group-hover:rotate-12 transition-all duration-300">{{item.icon}}</div>
                  <p :class="`text-[10px] sm:text-xs font-bold bg-gradient-to-r ${item.gradient} bg-clip-text text-transparent`">{{item.label}}</p>
                </div>
              </Card>
            </template>
          </div>
        </div>

        <!-- Info Cards - Appears AFTER feed on mobile -->
        <div class="space-y-4 sm:space-y-6 order-2 lg:order-1" v-motion :initial="{ opacity: 0, x: 20 }" :visible="{ opacity: 1, x: 0 }" :delay="200">
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
              <div class="text-3xl sm:text-4xl mb-3 sm:mb-4 group-hover:scale-110 group-hover:rotate-12 transition-all duration-300">
                📱
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
