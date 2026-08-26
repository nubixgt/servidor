<script setup>
import { Trophy, Music, Palette, FlaskConical, Users, Dumbbell, Drama, Globe } from 'lucide-vue-next'
import { Card } from '@/components/ui/card'
import { ref } from 'vue'

const actividades = [
  {
    icon: Trophy,
    title: 'Deportes',
    description: 'Fútbol, baloncesto, voleibol y atletismo con entrenadores profesionales.',
    gradient: 'from-orange-500 to-red-500',
    items: ['Fútbol', 'Baloncesto', 'Voleibol', 'Atletismo']
  },
  {
    icon: Music,
    title: 'Música',
    description: 'Aprende instrumentos, canto y formación musical con maestros especializados.',
    gradient: 'from-purple-500 to-pink-500',
    items: ['Marimba', 'Piano', 'Guitarra', 'Coro']
  },
  {
    icon: Palette,
    title: 'Arte',
    description: 'Desarrolla tu creatividad con pintura, dibujo y artes plásticas.',
    gradient: 'from-blue-500 to-cyan-500',
    items: ['Pintura', 'Dibujo', 'Escultura', 'Arte digital']
  },
  {
    icon: FlaskConical,
    title: 'Ciencia',
    description: 'Laboratorios de ciencias, robótica y experimentos científicos.',
    gradient: 'from-green-500 to-emerald-500',
    items: ['Robótica', 'Experimentos', 'Feria científica', 'Club de ciencias']
  },
  {
    icon: Drama,
    title: 'Teatro',
    description: 'Expresión artística, teatro y desarrollo de habilidades escénicas.',
    gradient: 'from-yellow-500 to-orange-500',
    items: ['Teatro', 'Oratoria', 'Declamación', 'Presentaciones']
  },
  {
    icon: Globe,
    title: 'Idiomas',
    description: 'Refuerzo de inglés y oportunidad de aprender más idiomas.',
    gradient: 'from-indigo-500 to-purple-500',
    items: ['Inglés avanzado', 'Conversación', 'Certificaciones']
  },
  {
    icon: Users,
    title: 'Liderazgo',
    description: 'Desarrollo de liderazgo, valores y responsabilidad social.',
    gradient: 'from-teal-500 to-cyan-500',
    items: ['Gobierno estudiantil', 'Proyectos sociales', 'Voluntariado']
  },
  {
    icon: Dumbbell,
    title: 'Acondicionamiento',
    description: 'Gimnasia, yoga y actividades para una vida saludable.',
    gradient: 'from-rose-500 to-pink-500',
    items: ['Gimnasia', 'Yoga', 'Nutrición', 'Vida saludable']
  }
]

const activeCard = ref(null)
</script>

<template>
  <section id="actividades" class="py-20 sm:py-32 relative overflow-hidden">
    <!-- Background -->
    <div class="absolute inset-0 bg-gradient-to-b from-background via-green-50/30 to-background dark:via-green-950/10"></div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center max-w-3xl mx-auto mb-16" v-motion :initial="{ opacity: 0, y: 20 }" :visible="{ opacity: 1, y: 0 }">
        <h2 class="text-3xl sm:text-5xl font-bold mb-4">
          Actividades <span class="bg-gradient-to-r from-orange-600 to-pink-600 bg-clip-text text-transparent">Extracurriculares</span>
        </h2>
        <p class="text-lg text-muted-foreground">
          Descubre tu pasión y desarrolla tus talentos más allá del aula con nuestras actividades extracurriculares
        </p>
      </div>

      <!-- Actividades Grid -->
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card
          v-for="(actividad, index) in actividades"
          :key="index"
          @mouseenter="activeCard = index"
          @mouseleave="activeCard = null"
          v-motion
          :initial="{ opacity: 0, y: 20 }"
          :visible="{ opacity: 1, y: 0 }"
          :delay="index * 50"
          :class="`group relative p-6 transition-all duration-300 cursor-pointer bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-2 overflow-hidden ${
            activeCard === index ? 'scale-105 shadow-2xl border-transparent' : 'hover:scale-105 hover:shadow-xl border-border/50'
          }`"
        >
          <!-- Gradient Background on Hover -->
          <div :class="`absolute inset-0 bg-gradient-to-br ${actividad.gradient} opacity-0 group-hover:opacity-100 transition-opacity duration-300`"></div>
          
          <div class="relative z-10">
            <!-- Icon -->
            <div :class="`w-14 h-14 rounded-xl bg-gradient-to-br ${actividad.gradient} p-3 mb-4 group-hover:scale-110 transition-transform duration-300 shadow-lg ${activeCard === index ? 'bg-white/20' : ''}`">
              <component :is="actividad.icon" class="w-full h-full text-white" />
            </div>

            <!-- Content -->
            <h3 :class="`text-xl font-bold mb-2 transition-colors ${activeCard === index ? 'text-white' : 'text-foreground'}`">
              {{ actividad.title }}
            </h3>
            <p :class="`text-sm mb-4 transition-colors ${activeCard === index ? 'text-white/90' : 'text-muted-foreground'}`">
              {{ actividad.description }}
            </p>

            <!-- Items -->
            <div class="space-y-1.5">
              <div v-for="(item, idx) in actividad.items" :key="idx" class="flex items-center space-x-2">
                <div :class="`w-1.5 h-1.5 rounded-full ${activeCard === index ? 'bg-white' : `bg-gradient-to-r ${actividad.gradient}`}`"></div>
                <span :class="`text-xs ${activeCard === index ? 'text-white/90 font-medium' : 'text-foreground/70'}`">{{ item }}</span>
              </div>
            </div>
          </div>
        </Card>
      </div>

      <!-- Call to Action -->
      <div class="mt-16 text-center" v-motion :initial="{ opacity: 0, scale: 0.9 }" :visible="{ opacity: 1, scale: 1 }">
        <div class="relative inline-block p-10 rounded-3xl bg-gradient-to-br from-green-500/5 via-yellow-500/5 to-orange-500/5 border-2 border-green-200/50 dark:border-green-700/50 backdrop-blur-xl shadow-2xl overflow-hidden">
          
          <!-- Animated background gradient -->
          <div class="absolute inset-0 bg-gradient-to-br from-green-400/10 via-yellow-400/10 to-orange-400/10 animate-pulse"></div>
          
          <!-- Decorative elements -->
          <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-yellow-400/20 to-orange-400/20 rounded-full blur-3xl"></div>
          <div class="absolute bottom-0 left-0 w-32 h-32 bg-gradient-to-br from-green-400/20 to-emerald-400/20 rounded-full blur-3xl"></div>
          
          <div class="relative z-10">
            <div class="inline-flex items-center space-x-2 bg-gradient-to-r from-green-600 to-yellow-600 text-white rounded-full px-5 py-2 mb-4 shadow-lg">
              <Trophy class="w-4 h-4" />
              <span class="text-sm font-bold">¡Desarrolla Tu Potencial!</span>
            </div>
            
            <h3 class="text-3xl font-bold mb-3 bg-gradient-to-r from-green-600 via-yellow-600 to-orange-600 bg-clip-text text-transparent">
              ¿Listo para descubrir tu talento?
            </h3>
            
            <p class="text-muted-foreground mb-8 max-w-2xl mx-auto text-lg">
              Únete a nuestras actividades extracurriculares y desarrolla habilidades que te acompañarán toda la vida
            </p>
            
            <div class="flex flex-wrap gap-4 justify-center">
              <div class="group relative px-6 py-3 rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-lg hover:shadow-xl border-2 border-green-200/50 dark:border-green-700/50 transition-all duration-300 hover:scale-105 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-green-500/0 via-green-500/10 to-green-500/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                <div class="relative flex items-center space-x-2">
                  <span class="text-2xl">📅</span>
                  <div class="text-left">
                    <div class="text-xs font-medium text-muted-foreground">Horario</div>
                    <div class="text-sm font-bold text-foreground">Lunes a Viernes</div>
                  </div>
                </div>
              </div>
              
              <div class="group relative px-6 py-3 rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-lg hover:shadow-xl border-2 border-yellow-200/50 dark:border-yellow-700/50 transition-all duration-300 hover:scale-105 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-yellow-500/0 via-yellow-500/10 to-yellow-500/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                <div class="relative flex items-center space-x-2">
                  <span class="text-2xl">⏰</span>
                  <div class="text-left">
                    <div class="text-xs font-medium text-muted-foreground">Jornada</div>
                    <div class="text-sm font-bold text-foreground">7:00 AM - 5:00 PM</div>
                  </div>
                </div>
              </div>
              
              <div class="group relative px-6 py-3 rounded-2xl bg-gradient-to-r from-green-600 to-yellow-600 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                <div class="relative flex items-center space-x-2">
                  <span class="text-2xl">🎯</span>
                  <div class="text-left">
                    <div class="text-xs font-medium text-white/90">Estado</div>
                    <div class="text-sm font-bold text-white">Inscripción Abierta</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
