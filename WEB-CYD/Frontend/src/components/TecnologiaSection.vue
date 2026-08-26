<script setup>
import { Card, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Zap, Monitor, FlaskConical, Stethoscope, Smartphone, Download, Sparkles, CheckCircle2, Star, ChevronLeft, ChevronRight } from "lucide-vue-next"
import { ref, onMounted, onUnmounted, computed } from "vue"
import { useWindowScroll, useElementBounding } from '@vueuse/core'

const macLabImages = [
  {
    url: "https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/DSC06699-1761964955935.jpg?width=8000&height=8000&resize=contain",
    caption: "Laboratorio Moderno con más de 50 iMacs"
  },
  {
    url: "https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/IMG_5465-1761964951645.jpg?width=8000&height=8000&resize=contain",
    caption: "Vista panorámica del laboratorio tecnológico"
  },
  {
    url: "https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/IMG_5511-1761964951473.jpg?width=8000&height=8000&resize=contain",
    caption: "Instalaciones de última generación"
  },
  {
    url: "https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/DSC06744-1761964954840.jpg?width=8000&height=8000&resize=contain",
    caption: "Estudiantes aprendiendo tecnología"
  },
  {
    url: "https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/DSC06776-1761964955772.jpg?width=8000&height=8000&resize=contain",
    caption: "Educación práctica y moderna"
  },
  {
    url: "https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/DSC06730-1761964954512.jpg?width=8000&height=8000&resize=contain",
    caption: "Equipos Apple de última generación"
  }
]

const laboratorios = [
  {
    title: "Laboratorio de Química",
    description: "Espacio completamente equipado con reactivos certificados, equipos profesionales y todas las medidas de seguridad para experimentos prácticos y desarrollo científico.",
    image: "https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/object/public/project-uploads/a21d857a-92e3-4d38-abbc-25d3b9abafa8/generated_images/modern-chemistry-laboratory-test-tubes-w-d3e144ba-20251031012200.jpg",
    icon: FlaskConical,
    gradient: "from-green-600 to-emerald-600",
    features: [
      "Equipos y material de laboratorio profesional",
      "Reactivos certificados y seguros",
      "Experimentos prácticos guiados",
      "Normas internacionales de seguridad"
    ]
  },
  {
    title: "Laboratorio de Medicina",
    description: "Instalaciones modernas con equipamiento médico educativo de alta precisión, modelos anatómicos detallados y todo para explorar las ciencias de la salud a profundidad.",
    image: "https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/object/public/project-uploads/a21d857a-92e3-4d38-abbc-25d3b9abafa8/generated_images/medical-education-laboratory-with-anatom-d75f9b10-20251031012159.jpg",
    icon: Stethoscope,
    gradient: "from-red-600 to-pink-600",
    features: [
      "Microscopios de alta precisión",
      "Modelos anatómicos detallados",
      "Equipo médico educativo certificado",
      "Prácticas de primeros auxilios y más"
    ]
  }
]

const isHovered = ref(false)
const { y: scrollY } = useWindowScroll()
const sectionRef = ref(null)
const { top, height } = useElementBounding(sectionRef)

const currentImageIndex = ref(0)
const isAutoPlaying = ref(true)

const scrollProgress = computed(() => {
  if (typeof window === 'undefined') return 0
  const windowHeight = window.innerHeight
  const sectionTop = top.value
  const sectionHeight = height.value
  return Math.max(0, Math.min(1, (windowHeight - sectionTop) / (windowHeight + sectionHeight / 2)))
})

let intervalId = null

const startAutoPlay = () => {
  intervalId = setInterval(() => {
    if (isAutoPlaying.value) {
      currentImageIndex.value = (currentImageIndex.value + 1) % macLabImages.length
    }
  }, 4000)
}

onMounted(() => {
  startAutoPlay()
})

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId)
})

const nextImage = () => {
  isAutoPlaying.value = false
  currentImageIndex.value = (currentImageIndex.value + 1) % macLabImages.length
}

const prevImage = () => {
  isAutoPlaying.value = false
  currentImageIndex.value = (currentImageIndex.value - 1 + macLabImages.length) % macLabImages.length
}

const goToImage = (index) => {
  isAutoPlaying.value = false
  currentImageIndex.value = index
}

const phoneTransform = computed(() => ({
  translateY: Math.sin(scrollY.value * 0.002) * 30,
  rotate: Math.sin(scrollY.value * 0.001) * 5,
  scale: 1 + Math.sin(scrollY.value * 0.003) * 0.05
}))
</script>

<template>
  <section id="tecnologia" class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-white via-purple-50 to-white relative overflow-hidden">
    <!-- Background Decoration -->
    <div class="absolute top-0 left-0 w-96 h-96 bg-purple-200/30 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-blue-200/30 rounded-full blur-3xl"></div>
    
    <div class="max-w-7xl mx-auto relative">
      <div class="text-center mb-16 animate-fade-in" v-motion :initial="{ opacity: 0, y: 20 }" :visible="{ opacity: 1, y: 0 }">
        <div class="inline-flex items-center space-x-2 bg-gradient-to-r from-purple-600 to-cyan-600 text-white rounded-full px-6 py-2 mb-4 shadow-lg">
          <Zap class="w-5 h-5" />
          <span class="font-bold">Innovación y Tecnología</span>
        </div>
        <h2 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold bg-gradient-to-r from-purple-600 via-blue-600 to-cyan-600 bg-clip-text text-transparent mb-4">
          ¡Educación con Visión Tecnológica en Baja Verapaz!
        </h2>
        <p class="text-lg sm:text-xl text-gray-600 max-w-3xl mx-auto px-4">
          Preparamos a nuestros estudiantes con herramientas digitales de vanguardia
        </p>
      </div>

      <div class="space-y-20">
        <!-- LABORATORIO MAC - NUEVO DISEÑO BENTO STYLE -->
        <div class="mb-20" ref="sectionRef">
          <!-- Decorative Stars and Icons -->
          <div class="absolute -top-8 left-1/4 text-4xl sm:text-6xl animate-bounce">💫</div>
          <div class="absolute -top-4 right-1/3 text-3xl sm:text-4xl animate-pulse">⭐</div>
          
          <div
            class="relative grid grid-cols-1 lg:grid-cols-3 gap-6 shadow-[0_0_80px_rgba(59,130,246,0.5)] rounded-3xl p-4 sm:p-6 md:p-8 bg-gradient-to-br from-blue-50/80 via-cyan-50/80 to-purple-50/80 dark:from-blue-950/50 dark:via-cyan-950/50 dark:to-purple-950/50 backdrop-blur-sm border-2 sm:border-4 border-blue-400/70 dark:border-blue-600/70 ring-4 sm:ring-8 ring-blue-300/30 dark:ring-blue-700/30 overflow-hidden"
            :style="{
              transform: `translateY(${(1 - scrollProgress) * 20}px)`,
              opacity: 0.5 + scrollProgress * 0.5
            }">
            
            <!-- Animated Glow Background -->
            <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 via-cyan-500/10 to-purple-500/10 animate-pulse"></div>
            
            <!-- Corner Decorations -->
            <div class="absolute top-2 left-2 sm:top-4 sm:left-4 text-xl sm:text-3xl">🚀</div>
            <div class="absolute top-2 right-2 sm:top-4 sm:right-4 text-xl sm:text-3xl animate-spin-slow">⚙️</div>
            <div class="absolute bottom-2 left-2 sm:bottom-4 sm:left-4 text-xl sm:text-3xl">💡</div>
            <div class="absolute bottom-2 right-2 sm:bottom-4 sm:right-4 text-xl sm:text-3xl">🎯</div>

            <!-- LEFT COLUMN - Image Carousel -->
            <div class="lg:col-span-2 relative group/carousel z-10">
              <div class="relative h-[400px] sm:h-[500px] md:h-[600px] rounded-2xl sm:rounded-3xl overflow-hidden shadow-[0_20px_60px_rgba(0,0,0,0.3)] hover:shadow-[0_25px_80px_rgba(59,130,246,0.6)] transition-shadow duration-500 border-2 sm:border-4 border-white/50 dark:border-gray-800/50 ring-2 sm:ring-4 ring-blue-400/30">
                <!-- Images -->
                <div
                  v-for="(image, index) in macLabImages"
                  :key="index"
                  :class="`absolute inset-0 transition-all duration-1000 ease-in-out ${
                    index === currentImageIndex
                      ? 'opacity-100 scale-100'
                      : 'opacity-0 scale-110'
                  }`">
                  <img
                    :src="image.url"
                    :alt="image.caption"
                    class="object-cover w-full h-full"
                  />
                  <!-- Gradient overlay -->
                  <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                </div>
                
                <!-- Top Badge -->
                <div class="absolute top-3 left-3 sm:top-6 sm:left-6 z-20">
                  <div class="inline-flex items-center space-x-2 bg-gradient-to-r from-blue-600 to-cyan-600 backdrop-blur-2xl rounded-xl sm:rounded-2xl px-3 py-2 sm:px-5 sm:py-3 border border-white/40 sm:border-2 shadow-2xl ring-2 sm:ring-4 ring-blue-400/20">
                    <Monitor class="w-4 h-4 sm:w-5 sm:h-5 text-white" />
                    <span class="text-sm sm:text-base font-bold text-white">Laboratorio MAC</span>
                    <span class="text-xl sm:text-2xl">🖥️</span>
                  </div>
                </div>
                
                <!-- Navigation Controls -->
                <div class="absolute bottom-3 left-3 right-3 sm:bottom-6 sm:left-6 sm:right-6 z-20 flex items-center gap-2 sm:gap-4">
                  <!-- Previous -->
                  <button
                    @click="prevImage"
                    class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-lg sm:rounded-xl bg-white/20 backdrop-blur-2xl hover:bg-white/40 border border-white/30 flex items-center justify-center transition-all duration-300 hover:scale-110 shadow-xl">
                    <ChevronLeft class="w-5 h-5 sm:w-6 sm:h-6 text-white" />
                  </button>
                  
                  <!-- Caption -->
                  <div class="flex-1 bg-white/10 backdrop-blur-2xl rounded-xl sm:rounded-2xl p-2 sm:p-4 border border-white/20">
                    <p class="text-sm sm:text-lg font-bold text-white line-clamp-1 sm:line-clamp-none">
                      {{ macLabImages[currentImageIndex].caption }}
                    </p>
                  </div>
                  
                  <!-- Next -->
                  <button
                    @click="nextImage"
                    class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-lg sm:rounded-xl bg-white/20 backdrop-blur-2xl hover:bg-white/40 border border-white/30 flex items-center justify-center transition-all duration-300 hover:scale-110 shadow-xl">
                    <ChevronRight class="w-5 h-5 sm:w-6 sm:h-6 text-white" />
                  </button>
                </div>
                
                <!-- Dots Indicator -->
                <div class="absolute bottom-16 sm:bottom-24 left-1/2 -translate-x-1/2 flex space-x-2 z-20">
                  <button
                    v-for="(_, index) in macLabImages"
                    :key="index"
                    @click="goToImage(index)"
                    :class="`transition-all duration-300 rounded-full ${
                      index === currentImageIndex
                        ? 'w-6 sm:w-8 h-2 bg-white shadow-lg'
                        : 'w-2 h-2 bg-white/40 hover:bg-white/70'
                    }`"
                  />
                </div>
              </div>

              <!-- Features Card -->
              <Card class="mt-6 relative overflow-hidden bg-gradient-to-br from-purple-50 to-cyan-50 dark:from-purple-950/40 dark:to-cyan-950/40 backdrop-blur-xl border-2 border-purple-200/50 dark:border-purple-700/50 shadow-xl p-4 sm:p-6 group hover:shadow-2xl hover:scale-[1.02] transition-all duration-500">
                <div class="relative flex flex-col sm:flex-row gap-3">
                  <div v-for="(item, idx) in [
                    { icon: '⚡', text: 'Internet Alta Velocidad', gradient: 'from-yellow-400 via-orange-500 to-red-500', bg: 'bg-gradient-to-r from-yellow-500/20 to-orange-500/20' },
                    { icon: '🎨', text: 'Diseño Gráfico Pro', gradient: 'from-pink-500 via-purple-500 to-indigo-500', bg: 'bg-gradient-to-r from-pink-500/20 to-purple-500/20' },
                    { icon: '🤖', text: 'Robótica y IA', gradient: 'from-blue-500 via-cyan-500 to-teal-500', bg: 'bg-gradient-to-r from-blue-500/20 to-cyan-500/20' }
                  ]" :key="idx" :class="`group/item relative flex items-center space-x-3 p-3 rounded-xl ${item.bg} border-2 border-transparent hover:border-white dark:hover:border-gray-700 hover:scale-105 hover:shadow-lg transition-all duration-300 cursor-pointer overflow-hidden flex-1`">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/40 to-white/0 translate-x-[-100%] group-hover/item:translate-x-[100%] transition-transform duration-700"></div>
                    <span class="relative text-xl sm:text-2xl group-hover/item:scale-125 group-hover/item:rotate-12 transition-all duration-300">{{ item.icon }}</span>
                    <span :class="`relative text-xs sm:text-sm font-bold bg-gradient-to-r ${item.gradient} bg-clip-text text-transparent`">{{ item.text }}</span>
                  </div>
                </div>
              </Card>
            </div>

            <!-- RIGHT COLUMN - Info Cards -->
            <div class="flex flex-col gap-6 z-10">
              <Card class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-cyan-600 to-purple-600 text-white border-0 shadow-2xl p-6 sm:p-8 group hover:scale-105 transition-transform duration-500">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
                <div class="relative">
                  <div class="text-4xl sm:text-5xl mb-4">🖥️</div>
                  <h3 class="text-2xl sm:text-3xl font-black mb-3 leading-tight">
                    Laboratorio de Computación MAC
                  </h3>
                  <p class="text-white/90 text-sm leading-relaxed">
                    Más de 50 iMacs de última generación para el aprendizaje del futuro
                  </p>
                </div>
              </Card>

              <Card class="relative overflow-hidden bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl border-2 border-blue-200/50 dark:border-blue-700/50 shadow-xl p-6 group hover:shadow-2xl hover:scale-105 transition-all duration-500">
                <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-blue-500/10 rounded-full blur-2xl"></div>
                <div class="relative space-y-4">
                  <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-muted-foreground">Equipos</span>
                    <span class="text-2xl sm:text-3xl font-black bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">50+</span>
                  </div>
                  <div class="h-2 bg-gradient-to-r from-blue-600 to-cyan-600 rounded-full shadow-lg"></div>
                  
                  <div class="flex items-center justify-between pt-2">
                    <span class="text-sm font-semibold text-muted-foreground">Software</span>
                    <span class="text-xl sm:text-2xl font-black bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">Pro</span>
                  </div>
                  <div class="h-2 bg-gradient-to-r from-purple-600 to-pink-600 rounded-full shadow-lg"></div>
                </div>
              </Card>
            </div>

            <!-- Bottom Feature Grid -->
            <div class="lg:col-span-3 grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mt-6 z-10">
              <Card
                v-for="(feature, idx) in [
                  { icon: '🖥️', title: '50+ iMacs', color: 'from-blue-600 to-cyan-600' },
                  { icon: '💻', title: 'Software Pro', color: 'from-cyan-600 to-teal-600' },
                  { icon: '🌐', title: 'Fibra Óptica', color: 'from-blue-600 to-indigo-600' },
                  { icon: '📚', title: 'Interactivo', color: 'from-indigo-600 to-purple-600' }
                ]"
                :key="idx"
                class="group relative overflow-hidden p-4 sm:p-6 text-center bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl border-2 border-border/50 hover:border-transparent hover:shadow-2xl hover:scale-110 transition-all duration-500 cursor-pointer">
                <div :class="`absolute inset-0 bg-gradient-to-br ${feature.color} opacity-0 group-hover:opacity-10 transition-opacity duration-500`"></div>
                <div class="relative">
                  <div class="text-3xl sm:text-4xl mb-2 group-hover:scale-125 group-hover:rotate-12 transition-all duration-500">{{ feature.icon }}</div>
                  <p :class="`text-xs sm:text-sm font-bold bg-gradient-to-r ${feature.color} bg-clip-text text-transparent`">{{ feature.title }}</p>
                </div>
              </Card>
            </div>
          </div>
        </div>

        <!-- APP CYD Section -->
        <div class="mb-20" v-motion :initial="{ opacity: 0, y: 50 }" :visible="{ opacity: 1, y: 0 }" :delay="100">
          <Card class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 text-white border-0 shadow-2xl">
            <!-- Animated Background Elements -->
            <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-white/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-white/10 rounded-full blur-3xl animate-pulse animation-delay-2000"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-white/5 rounded-full blur-3xl animate-pulse animation-delay-4000"></div>
            
            <div class="relative p-8 sm:p-12 lg:p-16">
              <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left Content -->
                <div class="space-y-8">
                  <div class="inline-flex items-center space-x-2 bg-white/20 backdrop-blur-md rounded-full px-5 py-2.5 border border-white/30">
                    <Star class="w-5 h-5 fill-yellow-300 text-yellow-300" />
                    <span class="text-sm font-bold">¡Nueva Aplicación Móvil!</span>
                  </div>
                  
                  <div>
                    <h3 class="text-5xl sm:text-6xl font-black leading-tight mb-4 drop-shadow-lg">
                      APP CYD
                    </h3>
                    <div class="flex flex-wrap gap-3 mb-6">
                      <div class="bg-white/20 backdrop-blur-sm rounded-full px-4 py-2 text-sm font-semibold border border-white/30">
                        📱 iOS
                      </div>
                      <div class="bg-white/20 backdrop-blur-sm rounded-full px-4 py-2 text-sm font-semibold border border-white/30">
                        🤖 Android
                      </div>
                      <div class="bg-white/20 backdrop-blur-sm rounded-full px-4 py-2 text-sm font-semibold border border-white/30">
                        ⚡ Gratis
                      </div>
                    </div>
                  </div>
                  
                  <p class="text-xl leading-relaxed opacity-95">
                    Tu <span class="font-bold underline decoration-white/50">colegio en tu bolsillo</span>. Mantente conectado con calificaciones, horarios, noticias y mucho más desde tu dispositivo móvil.
                  </p>

                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div
                      v-for="(feature, idx) in [
                        { icon: '📊', title: 'Calificaciones', desc: 'Consulta notas al instante' },
                        { icon: '📝', title: 'Tareas', desc: 'Gestiona tus actividades' },
                        { icon: '📅', title: 'Calendario', desc: 'No te pierdas eventos' },
                        { icon: '🕐', title: 'Horarios', desc: 'Consulta tus clases' }
                      ]"
                      :key="idx"
                      class="group/feature relative flex items-start space-x-3 bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20 hover:bg-white/25 hover:border-white/40 hover:scale-105 hover:shadow-2xl hover:shadow-white/20 transition-all duration-300 cursor-pointer overflow-hidden">
                      <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 translate-x-[-100%] group-hover/feature:translate-x-[100%] transition-transform duration-700"></div>
                      <div class="relative text-3xl group-hover/feature:scale-125 group-hover/feature:rotate-12 transition-all duration-300">
                        {{ feature.icon }}
                      </div>
                      <div class="relative">
                        <p class="font-bold text-lg group-hover/feature:text-white transition-colors duration-300">{{ feature.title }}</p>
                        <p class="text-sm opacity-90 group-hover/feature:opacity-100 transition-opacity duration-300">{{ feature.desc }}</p>
                      </div>
                      <div class="absolute -bottom-2 -right-2 w-16 h-16 bg-white rounded-full blur-2xl opacity-0 group-hover/feature:opacity-30 transition-opacity duration-300"></div>
                    </div>
                  </div>

                  <div class="space-y-4 pt-4">
                    <p class="text-base font-semibold opacity-90">Descarga ahora:</p>
                    <div class="flex flex-col sm:flex-row gap-4">
                      <Button
                        size="lg"
                        class="relative bg-white/20 backdrop-blur-md text-white hover:bg-white/30 shadow-xl group h-16 text-base font-bold px-6 rounded-2xl overflow-hidden transition-all duration-500 hover:shadow-[0_0_40px_rgba(255,255,255,0.4)] hover:scale-105 border-2 border-white/30 hover:border-white/50"
                        @click="() => window.open('https://apps.apple.com/gt/app/colegiocyd/id1555398289', '_blank')">
                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/30 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                        <div class="relative flex items-center justify-center w-full">
                          <Download class="mr-3 group-hover:animate-bounce" :size="24" />
                          <div class="text-left">
                            <div class="text-xs opacity-90 font-normal">Descargar en</div>
                            <div class="flex items-center gap-2">
                              <span class="text-lg">App Store</span>
                              <span class="text-xl">📱</span>
                            </div>
                          </div>
                        </div>
                        <div class="absolute inset-0 rounded-2xl bg-white/0 group-hover:bg-white/10 transition-all duration-500"></div>
                      </Button>
                      
                      <Button
                        size="lg"
                        class="relative bg-white/20 backdrop-blur-md text-white hover:bg-white/30 shadow-xl group h-16 text-base font-bold px-6 rounded-2xl overflow-hidden transition-all duration-500 hover:shadow-[0_0_40px_rgba(255,255,255,0.4)] hover:scale-105 border-2 border-white/30 hover:border-white/50"
                        @click="() => window.open('https://play.google.com/store/apps/details?id=gt.com.cyd.colegio_cyd&hl=es_GT&pli=1', '_blank')">
                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/30 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                        <div class="relative flex items-center justify-center w-full">
                          <Download class="mr-3 group-hover:animate-bounce" :size="24" />
                          <div class="text-left">
                            <div class="text-xs opacity-90 font-normal">Disponible en</div>
                            <div class="flex items-center gap-2">
                              <span class="text-lg">Google Play</span>
                              <span class="text-xl">🤖</span>
                            </div>
                          </div>
                        </div>
                        <div class="absolute inset-0 rounded-2xl bg-white/0 group-hover:bg-white/10 transition-all duration-500"></div>
                      </Button>
                    </div>
                  </div>
                </div>

                <!-- Right Content - Phone Mockup -->
                <div class="flex justify-center lg:justify-end">
                  <div
                    class="relative group"
                    :style="{
                      transform: `translateY(${phoneTransform.translateY}px) rotate(${phoneTransform.rotate}deg) scale(${phoneTransform.scale})`,
                      transition: 'transform 0.3s ease-out'
                    }"
                    @mouseenter="isHovered = true"
                    @mouseleave="isHovered = false">
                    <div class="absolute inset-0 bg-gradient-to-r from-pink-400 via-purple-400 to-blue-400 rounded-[3.5rem] blur-3xl opacity-60 group-hover:opacity-80 transition-opacity duration-500 scale-105"></div>
                    <div class="absolute inset-0 bg-white rounded-[3.5rem] blur-2xl opacity-40 group-hover:opacity-60 transition-opacity duration-500 scale-110 animate-pulse"></div>
                    
                    <div :class="`relative bg-gray-900 rounded-[3.5rem] p-3 shadow-2xl w-72 sm:w-80 transition-all duration-500 ${isHovered ? 'scale-105' : ''} hover:shadow-[0_0_80px_rgba(168,85,247,0.6)]`">
                      <div class="absolute top-0 left-1/2 -translate-x-1/2 w-36 h-7 bg-gray-900 rounded-b-3xl z-10 shadow-lg"></div>
                      <div class="relative rounded-[3rem] overflow-hidden aspect-[9/19] bg-white shadow-inner">
                        <img
                          src="https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/App1__-2-1761865863492.png?width=800&height=800&resize=contain"
                          alt="APP CYD Screenshot"
                          :class="`w-full h-full object-cover transition-transform duration-700 ${isHovered ? 'scale-110' : 'scale-100'}`" />
                        <div :class="`absolute inset-0 bg-gradient-to-t from-purple-900/70 via-transparent to-transparent flex items-end justify-center p-8 transition-opacity duration-300 ${isHovered ? 'opacity-100' : 'opacity-0'}`">
                          <div class="bg-white rounded-full p-4 shadow-2xl animate-bounce">
                            <Download class="w-10 h-10 text-purple-600" />
                          </div>
                        </div>
                      </div>
                      <div class="absolute right-0 top-24 w-1 h-12 bg-gray-700 rounded-l-full"></div>
                      <div class="absolute right-0 top-40 w-1 h-16 bg-gray-700 rounded-l-full"></div>
                      <div class="absolute left-0 top-32 w-1 h-8 bg-gray-700 rounded-r-full"></div>
                    </div>
                    
                    <div :class="`absolute top-8 right-8 transition-all duration-700 ${isHovered ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'}`">
                      <Sparkles class="w-6 h-6 text-yellow-300 animate-pulse" />
                    </div>
                    <div :class="`absolute bottom-16 left-8 transition-all duration-700 delay-100 ${isHovered ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'}`">
                      <Sparkles class="w-5 h-5 text-pink-300 animate-pulse" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Laboratories Section -->
        <div v-motion :initial="{ opacity: 0, y: 50 }" :visible="{ opacity: 1, y: 0 }" :delay="100">
          <div class="text-center mb-16">
            <div class="inline-flex items-center space-x-2 bg-gradient-to-r from-green-600 to-blue-600 text-white rounded-full px-6 py-2 mb-4 shadow-lg">
              <Sparkles class="w-5 h-5" />
              <span class="font-bold">Infraestructura de Primer Nivel</span>
            </div>
            <h3 class="text-4xl sm:text-5xl font-bold mb-6">
              Otros Laboratorios <span class="bg-gradient-to-r from-green-600 via-blue-600 to-purple-600 bg-clip-text text-transparent">Especializados</span>
            </h3>
            <p class="text-xl text-muted-foreground max-w-3xl mx-auto leading-relaxed">
              Espacios diseñados con la más alta tecnología para la experimentación práctica y el aprendizaje científico
            </p>
          </div>

          <div class="grid lg:grid-cols-2 gap-8">
            <Card
              v-for="(lab, index) in laboratorios"
              :key="index"
              class="group relative overflow-hidden bg-white dark:bg-gray-800 border-2 border-border/50 hover:border-transparent hover:shadow-2xl transition-all duration-500 hover:-translate-y-2">
              <div class="relative h-64 overflow-hidden">
                <img
                  :src="lab.image"
                  :alt="lab.title"
                  class="object-cover w-full h-full transition-transform duration-700 group-hover:scale-110" />
                <div :class="`absolute inset-0 bg-gradient-to-t ${lab.gradient} opacity-60 group-hover:opacity-40 transition-opacity duration-500`"></div>
                <div class="absolute top-4 right-4">
                  <div :class="`w-16 h-16 rounded-2xl bg-gradient-to-br ${lab.gradient} p-4 shadow-2xl group-hover:scale-110 group-hover:rotate-12 transition-all duration-500 border-4 border-white`">
                    <component :is="lab.icon" class="w-full h-full text-white" />
                  </div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 p-6 bg-gradient-to-t from-black/80 to-transparent">
                  <h3 class="text-2xl font-bold text-white mb-1">
                    {{ lab.title }}
                  </h3>
                </div>
              </div>

              <div class="p-6 space-y-6">
                <p class="text-muted-foreground leading-relaxed">
                  {{ lab.description }}
                </p>
                <div class="space-y-3">
                  <p class="text-sm font-bold uppercase tracking-wide text-foreground/70">Características Destacadas:</p>
                  <div v-for="(feature, idx) in lab.features" :key="idx" class="flex items-start space-x-3 group/item">
                    <div :class="`w-6 h-6 rounded-lg bg-gradient-to-br ${lab.gradient} p-1.5 flex-shrink-0 mt-0.5 group-hover/item:scale-110 transition-transform`">
                      <CheckCircle2 class="w-full h-full text-white" />
                    </div>
                    <span class="text-sm text-foreground/90 leading-relaxed">{{ feature }}</span>
                  </div>
                </div>
                <div :class="`h-1 rounded-full bg-gradient-to-r ${lab.gradient} transform origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-500`"></div>
              </div>
              <div :class="`absolute -bottom-20 -right-20 w-40 h-40 bg-gradient-to-br ${lab.gradient} rounded-full blur-3xl opacity-0 group-hover:opacity-30 transition-opacity duration-500`"></div>
            </Card>
          </div>
        </div>

        <!-- Stats Section -->
        <div class="mt-20 grid md:grid-cols-4 gap-6" v-motion :initial="{ opacity: 0, y: 30 }" :visible="{ opacity: 1, y: 0 }" :delay="200">
          <Card 
            v-for="(stat, idx) in [
              { value: '50+', label: 'Computadoras iMac', gradient: 'from-blue-600 to-cyan-600', icon: '💻' },
              { value: '100%', label: 'Equipo Moderno', gradient: 'from-green-600 to-emerald-600', icon: '✨' },
              { value: '3', label: 'Laboratorios Especializados', gradient: 'from-red-600 to-pink-600', icon: '🔬' },
              { value: '24/7', label: 'APP CYD Disponible', gradient: 'from-purple-600 to-indigo-600', icon: '📱' }
            ]" 
            :key="idx" 
            class="group p-8 text-center bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-2 border-border/50 hover:shadow-xl hover:-translate-y-2 transition-all duration-300 relative overflow-hidden">
            <div :class="`absolute inset-0 bg-gradient-to-br ${stat.gradient} opacity-0 group-hover:opacity-10 transition-opacity duration-500`"></div>
            <div class="relative">
              <div class="text-4xl mb-3">{{ stat.icon }}</div>
              <div :class="`text-5xl font-black bg-gradient-to-r ${stat.gradient} bg-clip-text text-transparent mb-3 group-hover:scale-110 transition-transform`">{{ stat.value }}</div>
              <div class="text-sm text-muted-foreground font-medium">{{ stat.label }}</div>
            </div>
          </Card>
        </div>
      </div>
    </div>
  </section>
</template>
