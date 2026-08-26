<script setup>
import { ref } from 'vue'
import { Award, Users, Target, Heart, BookOpen, Shield, Building2, Microscope, Computer, Library, Sparkles, Rocket, TrendingUp, Trophy } from 'lucide-vue-next'
import { Card } from '@/components/ui/card'
import { useWindowScroll, useElementBounding } from '@vueuse/core'

const timeline = [
  { 
    year: '1992', 
    title: 'Fundación', 
    description: 'Inicio del Colegio CYD en Salama, Baja Verapaz con la visión de formar estudiantes con ciencia y disciplina',
    icon: Sparkles,
    color: 'from-green-600 to-emerald-500'
  },
  { 
    year: '2005', 
    title: 'Expansión', 
    description: 'Apertura de nuevas secciones educativas y laboratorios especializados para ciencias',
    icon: TrendingUp,
    color: 'from-blue-600 to-cyan-500'
  },
  { 
    year: '2015', 
    title: 'Modernización', 
    description: 'Implementación de tecnología educativa avanzada, laboratorios IMAC y plataformas digitales',
    icon: Rocket,
    color: 'from-purple-600 to-pink-500'
  },
  { 
    year: '2025', 
    title: 'Excelencia Continua', 
    description: '33 años de trayectoria, más de 15,000 estudiantes formados y 19 carreras educativas disponibles',
    icon: Trophy,
    color: 'from-yellow-500 to-orange-500'
  }
]

const valores = [
  {
    icon: Target,
    title: 'Disciplina',
    description: 'Formamos estudiantes responsables y comprometidos',
    gradient: 'from-blue-500 to-cyan-500'
  },
  {
    icon: BookOpen,
    title: 'Ciencia',
    description: 'Educación basada en el conocimiento y la innovación',
    gradient: 'from-green-500 to-emerald-500'
  },
  {
    icon: Heart,
    title: 'Valores',
    description: 'Respeto, honestidad e integridad en todo momento',
    gradient: 'from-pink-500 to-rose-500'
  },
  {
    icon: Shield,
    title: 'Excelencia',
    description: 'Compromiso con la calidad educativa superior',
    gradient: 'from-purple-500 to-indigo-500'
  }
]

const instalaciones = [
  {
    icon: Building2,
    title: 'Edificios Modernos',
    description: 'Aulas amplias, ventiladas y equipadas con tecnología de punta',
    gradient: 'from-blue-500 to-indigo-600'
  },
  {
    icon: Microscope,
    title: 'Laboratorios',
    description: 'Espacios equipados para ciencias, computación y experimentos',
    gradient: 'from-green-500 to-emerald-600'
  },
  {
    icon: Computer,
    title: 'Tecnología Avanzada',
    description: 'Computadoras, proyectores y herramientas digitales en cada aula',
    gradient: 'from-purple-500 to-pink-600'
  },
  {
    icon: Library,
    title: 'Biblioteca Digital',
    description: 'Recursos educativos modernos y acceso a información actualizada',
    gradient: 'from-orange-500 to-red-600'
  }
]
</script>

<template>
  <section id="nosotros" class="py-20 sm:py-32 relative overflow-hidden">
    <!-- Background -->
    <div class="absolute inset-0 bg-gradient-to-b from-background via-yellow-50/30 to-background dark:via-yellow-950/10"></div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center max-w-3xl mx-auto mb-16" v-motion :initial="{ opacity: 0, y: 20 }" :visible="{ opacity: 1, y: 0 }" :delay="100">
        <h2 class="text-3xl sm:text-5xl font-bold mb-4">
          Sobre <span class="bg-gradient-to-r from-green-600 to-yellow-500 bg-clip-text text-transparent">Nosotros</span>
        </h2>
        <p class="text-lg text-muted-foreground">
          Desde 1992, 33 años formando líderes con ciencia y disciplina en Salama, Baja Verapaz
        </p>
      </div>

      <!-- Mission & Mascot -->
      <div class="grid lg:grid-cols-2 gap-12 items-center mb-20">
        <div class="space-y-6" v-motion :initial="{ opacity: 0, x: -20 }" :visible="{ opacity: 1, x: 0 }" :delay="200">
          <h3 class="text-3xl font-bold">Nuestra Misión</h3>
          <p class="text-lg text-muted-foreground leading-relaxed">
            El <span class="font-semibold text-green-600">Colegio Particular Mixto CYD</span> se dedica a formar estudiantes integrales mediante una educación de calidad que combina <span class="font-semibold text-yellow-600">ciencia y disciplina</span>.
          </p>
          <p class="text-lg text-muted-foreground leading-relaxed">
            Con <span class="font-semibold text-blue-600">19 carreras educativas</span> y más de <span class="font-semibold text-green-600">15,000 estudiantes formados</span>, contamos con instalaciones modernas e innovadoras que facilitan el aprendizaje y preparamos a nuestros estudiantes para enfrentar los retos del futuro con valores sólidos, pensamiento crítico y un compromiso firme con la excelencia.
          </p>
          <div class="flex flex-wrap gap-4 pt-4">
            <div class="flex items-center space-x-2">
              <div class="w-2 h-2 rounded-full bg-green-600"></div>
              <span class="text-sm font-medium">33 Años de Trayectoria</span>
            </div>
            <div class="flex items-center space-x-2">
              <div class="w-2 h-2 rounded-full bg-yellow-600"></div>
              <span class="text-sm font-medium">19 Carreras Educativas</span>
            </div>
            <div class="flex items-center space-x-2">
              <div class="w-2 h-2 rounded-full bg-blue-600"></div>
              <span class="text-sm font-medium">+15,000 Estudiantes</span>
            </div>
          </div>
        </div>
        
        <div class="flex justify-center" v-motion :initial="{ opacity: 0, x: 20 }" :visible="{ opacity: 1, x: 0 }" :delay="300">
          <div class="relative w-80 h-80">
            <img
              src="https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/jaguar-saltando-1761938986560.png?width=8000&height=8000&resize=contain"
              alt="Jaguar CYD - Mascota del Colegio"
              class="absolute inset-0 object-contain w-full h-full drop-shadow-2xl hover:scale-110 transition-transform duration-500"
            />
          </div>
        </div>
      </div>

      <!-- Values Grid -->
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-20">
        <Card 
          v-for="(valor, index) in valores" 
          :key="index"
          v-motion
          :initial="{ opacity: 0, y: 20 }"
          :visible="{ opacity: 1, y: 0 }"
          :delay="100 * index"
          class="group p-6 text-center hover:shadow-xl transition-all duration-300 hover:-translate-y-2 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-2 border-border/50"
        >
          <div :class="`w-16 h-16 rounded-2xl bg-gradient-to-br ${valor.gradient} p-4 mx-auto mb-4 group-hover:scale-110 transition-transform duration-300 shadow-lg`">
            <component :is="valor.icon" class="w-full h-full text-white" />
          </div>
          <h3 class="text-lg font-bold mb-2">{{ valor.title }}</h3>
          <p class="text-sm text-muted-foreground">{{ valor.description }}</p>
        </Card>
      </div>

      <!-- Modern Facilities Section -->
      <div class="mb-20">
        <div class="text-center mb-12">
          <h3 class="text-3xl font-bold mb-4">
            Instalaciones <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Modernas e Innovadoras</span>
          </h3>
          <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
            Espacios diseñados para potenciar el aprendizaje y desarrollo integral de nuestros estudiantes
          </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
          <Card 
            v-for="(instalacion, index) in instalaciones" 
            :key="index"
            v-motion
            :initial="{ opacity: 0, y: 20 }"
            :visible="{ opacity: 1, y: 0 }"
            :delay="100 * index"
            class="group relative p-6 text-center hover:shadow-xl transition-all duration-300 hover:-translate-y-2 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-2 border-border/50 overflow-hidden"
          >
            <div :class="`absolute -top-20 -right-20 w-40 h-40 bg-gradient-to-br ${instalacion.gradient} rounded-full blur-3xl opacity-0 group-hover:opacity-30 transition-opacity duration-500`"></div>
            
            <div class="relative">
              <div :class="`w-16 h-16 rounded-2xl bg-gradient-to-br ${instalacion.gradient} p-4 mx-auto mb-4 group-hover:scale-110 transition-transform duration-300 shadow-lg`">
                <component :is="instalacion.icon" class="w-full h-full text-white" />
              </div>
              <h3 class="text-lg font-bold mb-2">{{ instalacion.title }}</h3>
              <p class="text-sm text-muted-foreground">{{ instalacion.description }}</p>
            </div>
          </Card>
        </div>
      </div>

      <!-- Enhanced Timeline -->
      <div class="mb-20">
        <div class="text-center mb-16">
          <h3 class="text-3xl sm:text-4xl font-bold mb-4">
            Nuestra <span class="bg-gradient-to-r from-green-600 via-yellow-500 to-blue-600 bg-clip-text text-transparent">Historia</span>
          </h3>
          <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
            Un recorrido por los momentos más importantes de nuestra trayectoria educativa
          </p>
        </div>
        
        <div class="relative max-w-6xl mx-auto">
          <!-- Central Timeline Line -->
          <div class="absolute left-1/2 top-0 bottom-0 w-1 bg-gradient-to-b from-green-600 via-yellow-500 to-orange-600 hidden lg:block transform -translate-x-1/2 shadow-lg"></div>
          
          <div class="space-y-12">
            <div v-for="(item, index) in timeline" :key="index" :class="`relative flex items-center ${index % 2 === 0 ? 'lg:flex-row' : 'lg:flex-row-reverse'} flex-col`">
              <!-- Content Card -->
              <div :class="`w-full lg:w-5/12 ${index % 2 === 0 ? 'lg:pr-12' : 'lg:pl-12'}`">
                <Card 
                  v-motion
                  :initial="{ opacity: 0, x: index % 2 === 0 ? -50 : 50 }"
                  :visible="{ opacity: 1, x: 0 }"
                  class="group p-6 sm:p-8 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm border-2 border-border/50 hover:shadow-2xl transition-all duration-500 hover:scale-105 relative overflow-hidden"
                >
                  <!-- Gradient overlay on hover -->
                  <div :class="`absolute inset-0 bg-gradient-to-br ${item.color} opacity-0 group-hover:opacity-10 transition-opacity duration-500`"></div>
                  
                  <div class="relative z-10">
                    <!-- Year Badge -->
                    <div :class="`inline-flex items-center px-4 py-2 rounded-full bg-gradient-to-r ${item.color} text-white font-bold text-xl sm:text-2xl mb-4 shadow-lg`">
                      {{ item.year }}
                    </div>
                    
                    <!-- Title with Icon -->
                    <div class="flex items-center gap-3 mb-3">
                      <div :class="`w-10 h-10 rounded-xl bg-gradient-to-br ${item.color} p-2 shadow-md group-hover:scale-110 transition-transform duration-300`">
                        <component :is="item.icon" class="w-full h-full text-white" />
                      </div>
                      <h4 class="text-xl sm:text-2xl font-bold">{{ item.title }}</h4>
                    </div>
                    
                    <!-- Description -->
                    <p class="text-muted-foreground text-base leading-relaxed">
                      {{ item.description }}
                    </p>
                  </div>
                </Card>
              </div>
              
              <!-- Center Circle -->
              <div class="absolute left-1/2 transform -translate-x-1/2 hidden lg:flex items-center justify-center">
                <div :class="`w-16 h-16 rounded-full bg-gradient-to-br ${item.color} border-4 border-background shadow-2xl flex items-center justify-center group-hover:scale-125 transition-transform duration-500 z-10`">
                  <component :is="item.icon" class="w-8 h-8 text-white" />
                </div>
              </div>
              
              <!-- Empty space for alternating layout -->
              <div class="hidden lg:block w-5/12"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Achievements -->
      <div class="text-center">
        <h3 class="text-2xl font-bold mb-8">Nuestros Logros</h3>
        <div class="grid md:grid-cols-3 gap-6">
          <Card class="p-8 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-2 border-border/50 hover:shadow-xl transition-shadow" v-motion :initial="{ opacity: 0, y: 20 }" :visible="{ opacity: 1, y: 0 }">
            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-green-600 to-emerald-600 mx-auto mb-4 flex items-center justify-center shadow-lg">
              <Award class="w-8 h-8 text-white" />
            </div>
            <h4 class="font-bold mb-2">33 Años de Excelencia</h4>
            <p class="text-sm text-muted-foreground">Más de tres décadas formando líderes</p>
          </Card>
          <Card class="p-8 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-2 border-border/50 hover:shadow-xl transition-shadow" v-motion :initial="{ opacity: 0, y: 20 }" :visible="{ opacity: 1, y: 0 }" :delay="100">
            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-yellow-500 to-orange-500 mx-auto mb-4 flex items-center justify-center shadow-lg">
              <Users class="w-8 h-8 text-white" />
            </div>
            <h4 class="font-bold mb-2">+15,000 Estudiantes</h4>
            <p class="text-sm text-muted-foreground">Miles de graduados exitosos</p>
          </Card>
          <Card class="p-8 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-2 border-border/50 hover:shadow-xl transition-shadow" v-motion :initial="{ opacity: 0, y: 20 }" :visible="{ opacity: 1, y: 0 }" :delay="200">
            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-600 to-cyan-600 mx-auto mb-4 flex items-center justify-center shadow-lg">
              <Target class="w-8 h-8 text-white" />
            </div>
            <h4 class="font-bold mb-2">19 Carreras Educativas</h4>
            <p class="text-sm text-muted-foreground">Diversidad de opciones académicas</p>
          </Card>
        </div>
      </div>
    </div>
  </section>
</template>
