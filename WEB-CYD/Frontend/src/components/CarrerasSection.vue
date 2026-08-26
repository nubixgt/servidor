<script setup>
import { Card, CardContent } from "@/components/ui/card"
import { GraduationCap, Clock, Calendar, Sparkles, Star, Award, BookOpen } from "lucide-vue-next"
import { ref, computed } from "vue"
import { useWindowScroll, useElementBounding } from '@vueuse/core'

const carreras = [
  {
    categoria: "DIVERSIFICADO (Plan Diario)",
    icon: "☀️",
    gradient: "from-amber-500 via-orange-500 to-red-500",
    bgGradient: "from-amber-50 via-orange-50 to-red-50",
    darkBgGradient: "from-amber-950/30 via-orange-950/20 to-red-950/30",
    programas: [
      { nombre: "BACHILLERATO EN CIENCIAS Y LETRAS", duracion: "2 AÑOS", icon: "📚" },
      { nombre: "SECRETARIADO OFICINISTA CON ORIENTACIÓN JURÍDICA", duracion: "2 AÑOS", icon: "⚖️" },
      { nombre: "PERITO CONTADOR CON ORIENTACIÓN EN COMPUTACIÓN", duracion: "3 AÑOS", icon: "💼" },
      { nombre: "PERITO EN ADMINISTRACIÓN DE EMPRESAS", duracion: "3 AÑOS", icon: "📊" },
      { nombre: "MAGISTERIO EN EDUCACIÓN INFANTIL BILINGÜE INTERCULTURAL", duracion: "3 AÑOS", icon: "👶" }
    ]
  },
  {
    categoria: "DIVERSIFICADO (Jornada Doble)",
    icon: "🌙",
    gradient: "from-blue-500 via-indigo-500 to-purple-500",
    bgGradient: "from-blue-50 via-indigo-50 to-purple-50",
    darkBgGradient: "from-blue-950/30 via-indigo-950/20 to-purple-950/30",
    programas: [
      { nombre: "BACHILLERATO EN DIBUJO TÉCNICO Y DE CONSTRUCCIÓN", duracion: "2 AÑOS", icon: "📐" },
      { nombre: "BACHILLERATO EN CIENCIAS Y LETRAS CON ORIENTACIÓN EN DISEÑO GRÁFICO", duracion: "2 AÑOS", icon: "🎨" },
      { 
        nombre: "BACHILLERATO EN CIENCIAS Y LETRAS CON DIPLOMADO EN:", 
        duracion: "2 AÑOS",
        icon: "🎓",
        subespecialidades: ["MEDICINA", "CRIMINOLOGÍA", "AGRONOMÍA"]
      },
      { nombre: "BACHILLER INDUSTRIAL Y PERITO EN MECÁNICA AUTOMOTRIZ", duracion: "3 AÑOS", icon: "🚗" },
      { nombre: "PERITO EN ELECTRÓNICA Y DISPOSITIVOS DIGITALES", duracion: "3 AÑOS", icon: "🔌" },
      { nombre: "PERITO EN ELECTRICIDAD INDUSTRIAL", duracion: "3 AÑOS", icon: "⚡" }
    ]
  },
  {
    categoria: "PLAN FIN DE SEMANA",
    icon: "📅",
    gradient: "from-emerald-500 via-teal-500 to-cyan-500",
    bgGradient: "from-emerald-50 via-teal-50 to-cyan-50",
    darkBgGradient: "from-emerald-950/30 via-teal-950/20 to-cyan-950/30",
    programas: [
      { nombre: "BÁSICO NORMAL", duracion: "3 AÑOS", icon: "📖" },
      { nombre: "BACHILLERATO EN CIENCIAS Y LETRAS POR MADUREZ", duracion: "1 AÑO (MAYORES DE 18 AÑOS)", icon: "🎯" },
      { nombre: "PERITO CONTADOR", duracion: "3 AÑOS", icon: "🧮" },
      { nombre: "BACHILLERATO EN COMPUTACIÓN CON ORIENTACIÓN COMERCIAL", duracion: "2 AÑOS", icon: "💻" },
      { 
        nombre: "BACH. EN COMPUTACIÓN CON ORIENTACIÓN COMERCIAL CON DIPLOMADO EN:", 
        duracion: "2 AÑOS",
        icon: "🖥️",
        subespecialidades: ["ADMINISTRACIÓN", "ENFERMERÍA"]
      },
      { nombre: "SECRETARIADO Y OFICINISTA", duracion: "2 AÑOS", icon: "📝" }
    ]
  }
]

const sectionRef = ref(null)
const { y } = useWindowScroll()
const { top, height } = useElementBounding(sectionRef)

const scrollProgress = computed(() => {
  if (typeof window === 'undefined') return 0
  const windowHeight = window.innerHeight
  const sectionTop = top.value
  const sectionHeight = height.value
  return Math.max(0, Math.min(1, (windowHeight - sectionTop) / (windowHeight + sectionHeight / 2)))
})

const scrollToContact = () => {
  document.getElementById('contacto')?.scrollIntoView({ behavior: 'smooth' })
}
</script>

<template>
  <section 
    id="carreras" 
    ref="sectionRef"
    class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-white via-green-50/30 to-white dark:from-gray-900 dark:via-green-900/10 dark:to-gray-900 relative overflow-hidden"
  >
    <!-- Background Decoration -->
    <div class="absolute top-0 left-0 w-96 h-96 bg-green-200/20 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-yellow-200/20 rounded-full blur-3xl"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-blue-200/10 rounded-full blur-3xl"></div>
    
    <div class="max-w-7xl mx-auto relative">
      <!-- Header -->
      <div 
        v-motion
        :initial="{ opacity: 0, y: 20 }"
        :visible="{ opacity: 1, y: 0 }"
        class="text-center mb-16"
      >
        <div class="inline-flex items-center space-x-2 bg-gradient-to-r from-green-600 via-yellow-500 to-blue-600 text-white rounded-full px-6 py-2.5 mb-6 shadow-lg">
          <GraduationCap class="w-5 h-5" />
          <span class="font-bold">Excelencia Académica</span>
        </div>
        
        <h2 class="text-4xl sm:text-6xl font-black bg-gradient-to-r from-green-600 via-yellow-500 to-blue-600 bg-clip-text text-transparent mb-6 leading-tight">
          Carreras Educativas
        </h2>
        
        <p class="text-xl text-muted-foreground max-w-3xl mx-auto leading-relaxed">
          <span class="font-bold text-green-600">19 carreras especializadas</span> diseñadas para formar profesionales exitosos y preparados para el futuro
        </p>

        <!-- Stats Pills -->
        <div class="flex flex-wrap justify-center gap-3 mt-8">
          <div class="bg-white dark:bg-gray-800 rounded-full px-6 py-3 shadow-lg border-2 border-green-200 dark:border-green-800">
            <div class="flex items-center gap-2">
              <Award class="w-5 h-5 text-green-600" />
              <span class="font-bold text-foreground">19 Carreras</span>
            </div>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-full px-6 py-3 shadow-lg border-2 border-yellow-200 dark:border-yellow-800">
            <div class="flex items-center gap-2">
              <Star class="w-5 h-5 text-yellow-600" />
              <span class="font-bold text-foreground">3 Modalidades</span>
            </div>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-full px-6 py-3 shadow-lg border-2 border-blue-200 dark:border-blue-800">
            <div class="flex items-center gap-2">
              <BookOpen class="w-5 h-5 text-blue-600" />
              <span class="font-bold text-foreground">Certificación Oficial</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Carreras Grid -->
      <div class="space-y-12">
        <div
          v-for="(grupo, grupoIdx) in carreras"
          :key="grupoIdx"
          v-motion
          :initial="{ opacity: 0, y: 40 }"
          :visible="{ opacity: 1, y: 0 }"
          :delay="grupoIdx * 100"
        >
          <Card 
            :class="`relative overflow-hidden bg-gradient-to-br ${grupo.bgGradient} dark:${grupo.darkBgGradient} backdrop-blur-sm border-2 shadow-xl hover:shadow-2xl transition-all duration-500`"
            :style="{
              transform: `translateY(${(1 - scrollProgress) * 20}px)`,
              opacity: 0.6 + scrollProgress * 0.4
            }"
          >
            <!-- Animated Background Blobs -->
            <div :class="`absolute top-0 right-0 w-[400px] h-[400px] bg-gradient-to-br ${grupo.gradient} opacity-5 rounded-full blur-3xl animate-pulse`"></div>
            <div :class="`absolute bottom-0 left-0 w-[400px] h-[400px] bg-gradient-to-br ${grupo.gradient} opacity-5 rounded-full blur-3xl animate-pulse animation-delay-2000`"></div>
            
            <CardContent class="relative p-8 lg:p-12">
              <!-- Categoria Header -->
              <div class="flex items-center justify-between mb-8 pb-6 border-b-2 border-border/50">
                <div class="flex items-center gap-4">
                  <div :class="`w-16 h-16 rounded-2xl bg-gradient-to-br ${grupo.gradient} p-4 shadow-xl flex items-center justify-center text-3xl`">
                    {{ grupo.icon }}
                  </div>
                  <div>
                    <h3 :class="`text-2xl lg:text-3xl font-black bg-gradient-to-r ${grupo.gradient} bg-clip-text text-transparent`">
                      {{ grupo.categoria }}
                    </h3>
                    <p class="text-sm text-muted-foreground font-medium mt-1">
                      {{ grupo.programas.length }} programas disponibles
                    </p>
                  </div>
                </div>
              </div>

              <!-- Programas Grid -->
              <div class="grid md:grid-cols-2 gap-4">
                <div
                  v-for="(programa, progIdx) in grupo.programas"
                  :key="progIdx"
                  class="group relative hover:scale-[1.02] hover:-translate-y-1 transition-all duration-300"
                >
                  <div class="relative bg-white/80 dark:bg-gray-800/80 backdrop-blur-md rounded-2xl p-6 border-2 border-border/50 hover:border-transparent shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <!-- Hover Gradient Effect -->
                    <div :class="`absolute inset-0 bg-gradient-to-br ${grupo.gradient} opacity-0 group-hover:opacity-10 transition-opacity duration-300`"></div>
                    
                    <!-- Content -->
                    <div class="relative flex items-start gap-4">
                      <!-- Icon -->
                      <div class="flex-shrink-0">
                        <div :class="`w-12 h-12 rounded-xl bg-gradient-to-br ${grupo.gradient} p-3 shadow-lg group-hover:scale-110 group-hover:rotate-6 transition-all duration-300 flex items-center justify-center text-xl`">
                          {{ programa.icon }}
                        </div>
                      </div>
                      
                      <!-- Info -->
                      <div class="flex-1 min-w-0">
                        <h4 class="font-bold text-base lg:text-lg text-foreground mb-2 leading-tight">
                          {{ programa.nombre }}
                        </h4>
                        
                        <!-- Subespecialidades -->
                        <div v-if="programa.subespecialidades" class="mb-3 pl-4 border-l-2 border-border/50">
                          <div v-for="(sub, subIdx) in programa.subespecialidades" :key="subIdx" class="flex items-center gap-2 mb-1">
                            <div class="w-1.5 h-1.5 rounded-full bg-gradient-to-r from-green-600 to-blue-600"></div>
                            <span class="text-sm font-semibold text-muted-foreground">{{ sub }}</span>
                          </div>
                        </div>
                        
                        <!-- Duracion Badge -->
                        <div class="flex items-center gap-2">
                          <Clock class="w-4 h-4 text-muted-foreground" />
                          <span :class="`text-sm font-bold bg-gradient-to-r ${grupo.gradient} bg-clip-text text-transparent`">
                            {{ programa.duracion }}
                          </span>
                        </div>
                      </div>
                    </div>

                    <!-- Corner Accent -->
                    <div :class="`absolute top-0 right-0 w-20 h-20 bg-gradient-to-br ${grupo.gradient} opacity-0 group-hover:opacity-20 blur-2xl transition-opacity duration-300`"></div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Call to Action -->
      <div 
        v-motion
        :initial="{ opacity: 0, y: 20 }"
        :visible="{ opacity: 1, y: 0 }"
        :delay="300"
        class="mt-16 text-center"
      >
        <Card class="relative overflow-hidden bg-gradient-to-r from-green-600 via-yellow-500 to-blue-600 text-white border-0 shadow-2xl">
          <!-- Animated Background -->
          <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 animate-pulse"></div>
          
          <CardContent class="relative p-8 lg:p-12">
            <div class="max-w-3xl mx-auto text-center">
              <div class="inline-flex items-center space-x-2 bg-white/20 backdrop-blur-md rounded-full px-5 py-2 mb-6">
                <Sparkles class="w-5 h-5" />
                <span class="font-bold">¡Inscripciones Abiertas 2025!</span>
              </div>
              
              <h3 class="text-3xl lg:text-4xl font-black mb-4">
                ¿Listo para tu Futuro Profesional?
              </h3>
              
              <p class="text-lg lg:text-xl opacity-95 mb-8 leading-relaxed">
                Únete a una institución con <span class="font-bold underline decoration-white/50">más de 33 años de experiencia</span> formando profesionales exitosos
              </p>
              
              <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button
                  @click="scrollToContact"
                  class="bg-white text-green-600 font-bold px-8 py-4 rounded-full shadow-xl hover:shadow-2xl hover:scale-105 active:scale-95 transition-all duration-300 flex items-center justify-center gap-2"
                >
                  <GraduationCap class="w-5 h-5" />
                  Solicitar Información
                </button>
                
                <button
                  @click="scrollToContact"
                  class="bg-white/20 backdrop-blur-md text-white font-bold px-8 py-4 rounded-full border-2 border-white/50 hover:bg-white/30 hover:scale-105 active:scale-95 transition-all duration-300 flex items-center justify-center gap-2"
                >
                  <Calendar class="w-5 h-5" />
                  Agendar Visita
                </button>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </section>
</template>
