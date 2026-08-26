<script setup>
import { Baby, BookOpen, GraduationCap, Sparkles } from 'lucide-vue-next'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'

const niveles = [
  {
    icon: Baby,
    title: 'Preprimaria',
    edad: '4-6 años',
    description: 'Educación inicial con metodologías lúdicas que estimulan el desarrollo integral de los más pequeños.',
    features: ['Estimulación temprana', 'Desarrollo psicomotriz', 'Iniciación a la lectura', 'Actividades artísticas'],
    gradient: 'from-pink-500 to-rose-500',
    color: 'pink'
  },
  {
    icon: BookOpen,
    title: 'Primaria',
    edad: '7-12 años',
    description: 'Formación académica sólida con énfasis en valores, ciencia y tecnología para construir bases fuertes.',
    features: ['Matemáticas', 'Ciencias Naturales', 'Idioma Español', 'Inglés', 'Computación', 'Deportes'],
    gradient: 'from-blue-500 to-cyan-500',
    color: 'blue'
  },
  {
    icon: GraduationCap,
    title: 'Básicos',
    edad: '13-15 años',
    description: 'Educación integral preparando estudiantes con pensamiento crítico y habilidades para el futuro.',
    features: ['Ciencias', 'Matemática avanzada', 'Estudios sociales', 'Inglés avanzado', 'Laboratorios'],
    gradient: 'from-green-500 to-emerald-500',
    color: 'green'
  },
  {
    icon: Sparkles,
    title: 'Diversificado',
    edad: '16-18 años',
    description: 'Preparación universitaria con carreras especializadas para el éxito académico y profesional.',
    features: ['Bachillerato en Ciencias', 'Perito Contador', 'Preparación universitaria', 'Prácticas profesionales'],
    gradient: 'from-purple-500 to-indigo-500',
    color: 'purple'
  }
]

const materias = [
  'Matemáticas', 'Ciencias Naturales', 'Estudios Sociales', 'Comunicación y Lenguaje',
  'Inglés', 'Computación', 'Educación Física', 'Artes', 'Música', 'Valores'
]

const handleWhatsAppClick = (nivel) => {
  const mensaje = `Hola, necesito más información sobre ${nivel}.`
  const whatsappUrl = `https://api.whatsapp.com/send?phone=50257001515&text=${encodeURIComponent(mensaje)}`
  window.open(whatsappUrl, '_blank')
}
</script>

<template>
  <section id="niveles" class="py-20 sm:py-32 relative overflow-hidden">
    <!-- Background -->
    <div class="absolute inset-0 bg-gradient-to-b from-background via-blue-50/50 to-background dark:via-blue-950/20"></div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center max-w-3xl mx-auto mb-16" v-motion :initial="{ opacity: 0, y: 20 }" :visible="{ opacity: 1, y: 0 }">
        <h2 class="text-3xl sm:text-5xl font-bold mb-4">
          Nuestros <span class="bg-gradient-to-r from-green-600 to-yellow-500 bg-clip-text text-transparent">Niveles</span>
        </h2>
        <p class="text-lg text-muted-foreground">
          Educación integral desde preprimaria hasta diversificado, formando estudiantes preparados para la vida
        </p>
      </div>

      <!-- Niveles Grid -->
      <div class="grid md:grid-cols-2 gap-8 mb-16">
        <Card 
          v-for="(nivel, index) in niveles" 
          :key="index"
          v-motion
          :initial="{ opacity: 0, y: 30 }"
          :visible="{ opacity: 1, y: 0 }"
          :delay="index * 100"
          class="group relative p-8 hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-2 border-border/50 overflow-hidden"
        >
          <!-- Gradient Orb on Hover -->
          <div :class="`absolute -top-20 -right-20 w-60 h-60 bg-gradient-to-br ${nivel.gradient} rounded-full blur-3xl opacity-0 group-hover:opacity-30 transition-opacity duration-500`"></div>
          
          <div class="relative">
            <!-- Icon & Badge -->
            <div class="flex items-start justify-between mb-4">
              <div :class="`w-16 h-16 rounded-2xl bg-gradient-to-br ${nivel.gradient} p-4 group-hover:scale-110 transition-transform duration-300 shadow-lg`">
                <component :is="nivel.icon" class="w-full h-full text-white" />
              </div>
              <span :class="`px-3 py-1 rounded-full text-xs font-semibold bg-${nivel.color}-100 text-${nivel.color}-700 dark:bg-${nivel.color}-900/30 dark:text-${nivel.color}-300`">
                {{ nivel.edad }}
              </span>
            </div>

            <!-- Content -->
            <h3 class="text-2xl font-bold mb-3 group-hover:text-transparent group-hover:bg-gradient-to-r group-hover:bg-clip-text group-hover:from-green-600 group-hover:to-yellow-500 transition-all">
              {{ nivel.title }}
            </h3>
            <p class="text-muted-foreground mb-6">
              {{ nivel.description }}
            </p>

            <!-- Features -->
            <div class="space-y-2 mb-6">
              <div v-for="(feature, idx) in nivel.features" :key="idx" class="flex items-center space-x-2">
                <div :class="`w-1.5 h-1.5 rounded-full bg-gradient-to-r ${nivel.gradient}`"></div>
                <span class="text-sm text-foreground/80">{{ feature }}</span>
              </div>
            </div>

            <!-- CTA -->
            <Button 
              @click="handleWhatsAppClick(nivel.title)"
              variant="outline" 
              :class="`w-full group-hover:bg-gradient-to-r group-hover:${nivel.gradient} group-hover:text-white group-hover:border-transparent transition-all`"
            >
              Más información
            </Button>
          </div>
        </Card>
      </div>

      <!-- Materias Section -->
      <div class="text-center" v-motion :initial="{ opacity: 0, y: 20 }" :visible="{ opacity: 1, y: 0 }" :delay="300">
        <h3 class="text-2xl font-bold mb-8">Áreas de Estudio</h3>
        <div class="flex flex-wrap justify-center gap-3">
          <div 
            v-for="(materia, index) in materias"
            :key="index"
            class="px-5 py-2.5 rounded-full bg-gradient-to-r from-green-500/10 to-yellow-500/10 border border-green-200 dark:border-green-800 backdrop-blur-sm hover:scale-105 hover:shadow-md transition-all cursor-default"
          >
            <span class="font-medium text-sm">{{ materia }}</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
