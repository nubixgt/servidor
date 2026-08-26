<script setup>
import { Card } from '@/components/ui/card'
import { Camera, Video } from 'lucide-vue-next'
import { ref } from 'vue'

const galeriaItems = [
  {
    type: 'event',
    title: 'Día del Estudiante 2024',
    category: 'Eventos',
    color: 'from-blue-500 to-cyan-500',
    emoji: '🎉'
  },
  {
    type: 'academic',
    title: 'Graduación 2024',
    category: 'Académico',
    color: 'from-purple-500 to-pink-500',
    emoji: '🎓'
  },
  {
    type: 'sports',
    title: 'Torneo Intercolegial',
    category: 'Deportes',
    color: 'from-orange-500 to-red-500',
    emoji: '⚽'
  },
  {
    type: 'science',
    title: 'Feria Científica',
    category: 'Ciencias',
    color: 'from-green-500 to-emerald-500',
    emoji: '🔬'
  },
  {
    type: 'arts',
    title: 'Festival de Artes',
    category: 'Arte',
    color: 'from-pink-500 to-rose-500',
    emoji: '🎨'
  },
  {
    type: 'music',
    title: 'Concierto de Marimba',
    category: 'Música',
    color: 'from-yellow-500 to-orange-500',
    emoji: '🎵'
  }
]

const hoveredIndex = ref(null)
</script>

<template>
  <section id="galeria" class="py-20 sm:py-32 relative overflow-hidden">
    <!-- Background -->
    <div class="absolute inset-0 bg-gradient-to-b from-background via-purple-50/30 to-background dark:via-purple-950/10"></div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center max-w-3xl mx-auto mb-16" v-motion :initial="{ opacity: 0, y: 20 }" :visible="{ opacity: 1, y: 0 }">
        <h2 class="text-3xl sm:text-5xl font-bold mb-4">
          Nuestra <span class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">Galería</span>
        </h2>
        <p class="text-lg text-muted-foreground">
          Momentos especiales que capturan la esencia de nuestra comunidad educativa
        </p>
      </div>

      <!-- Gallery Grid -->
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
        <Card 
          v-for="(item, index) in galeriaItems" 
          :key="index"
          @mouseenter="hoveredIndex = index"
          @mouseleave="hoveredIndex = null"
          v-motion
          :initial="{ opacity: 0, scale: 0.9 }"
          :visible="{ opacity: 1, scale: 1 }"
          :delay="index * 100"
          class="group relative h-64 overflow-hidden cursor-pointer transition-all duration-300 hover:scale-105 hover:shadow-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm"
        >
          <!-- Gradient Background -->
          <div :class="`absolute inset-0 bg-gradient-to-br ${item.color} transition-transform duration-500 ${
            hoveredIndex === index ? 'scale-110' : 'scale-100'
          }`"></div>
          
          <!-- Overlay -->
          <div class="absolute inset-0 bg-black/20 group-hover:bg-black/40 transition-colors duration-300"></div>

          <!-- Content -->
          <div class="relative h-full flex flex-col items-center justify-center text-white p-6 text-center">
            <div class="text-6xl mb-4 group-hover:scale-110 transition-transform duration-300">
              {{ item.emoji }}
            </div>
            <span class="text-xs font-semibold uppercase tracking-wider mb-2 opacity-90">
              {{ item.category }}
            </span>
            <h3 class="text-xl font-bold mb-3">
              {{ item.title }}
            </h3>
            <div :class="`flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 ${
              hoveredIndex === index ? 'translate-y-0' : 'translate-y-4'
            } transition-transform duration-300`">
              <Camera :size="16" />
              <span class="text-sm">Ver fotos</span>
            </div>
          </div>

          <!-- Badge -->
          <div class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
            <Camera class="w-5 h-5 text-white" />
          </div>
        </Card>
      </div>

      <!-- Video Highlights Section -->
      <div class="grid md:grid-cols-2 gap-8">
        <!-- Video 1 -->
        <Card class="group relative overflow-hidden bg-gradient-to-br from-blue-500 to-purple-600 p-8 hover:scale-105 transition-all duration-300 cursor-pointer shadow-xl hover:shadow-2xl" v-motion :initial="{ opacity: 0, x: -50 }" :visible="{ opacity: 1, x: 0 }" :delay="200">
          <div class="relative z-10 text-white">
            <div class="flex items-center space-x-3 mb-4">
              <div class="w-14 h-14 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center group-hover:scale-110 transition-transform">
                <Video class="w-7 h-7" />
              </div>
              <div>
                <span class="text-xs uppercase tracking-wider opacity-90 block">Video</span>
                <h3 class="text-xl font-bold">Tour Virtual del Colegio</h3>
              </div>
            </div>
            <p class="text-white/90 text-sm mb-4">
              Recorre nuestras instalaciones y descubre todos los espacios diseñados para tu aprendizaje.
            </p>
            <div class="inline-flex items-center space-x-2 px-4 py-2 rounded-full bg-white/20 backdrop-blur-sm group-hover:bg-white/30 transition-colors">
              <span class="text-sm font-medium">▶ Ver video</span>
            </div>
          </div>
          <!-- Decorative Orb -->
          <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
        </Card>

        <!-- Video 2 -->
        <Card class="group relative overflow-hidden bg-gradient-to-br from-green-500 to-yellow-600 p-8 hover:scale-105 transition-all duration-300 cursor-pointer shadow-xl hover:shadow-2xl" v-motion :initial="{ opacity: 0, x: 50 }" :visible="{ opacity: 1, x: 0 }" :delay="300">
          <div class="relative z-10 text-white">
            <div class="flex items-center space-x-3 mb-4">
              <div class="w-14 h-14 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center group-hover:scale-110 transition-transform">
                <Video class="w-7 h-7" />
              </div>
              <div>
                <span class="text-xs uppercase tracking-wider opacity-90 block">Video</span>
                <h3 class="text-xl font-bold">Testimonio de Estudiantes</h3>
              </div>
            </div>
            <p class="text-white/90 text-sm mb-4">
              Escucha las experiencias de nuestros estudiantes y lo que significa ser parte de CYD.
            </p>
            <div class="inline-flex items-center space-x-2 px-4 py-2 rounded-full bg-white/20 backdrop-blur-sm group-hover:bg-white/30 transition-colors">
              <span class="text-sm font-medium">▶ Ver video</span>
            </div>
          </div>
          <!-- Decorative Orb -->
          <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
        </Card>
      </div>
    </div>
  </section>
</template>
