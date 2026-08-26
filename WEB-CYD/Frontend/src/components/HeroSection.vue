<script setup>
import { ref, computed } from 'vue'
import { useWindowScroll } from '@vueuse/core'
import { ArrowRight, BookOpen, Trophy, Users, Sparkles } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

const { y } = useWindowScroll()
const scrollYProgress = computed(() => {
  return Math.min(Math.max(y.value / 1000, 0), 1)
})

// Parallax computations
const bgY = computed(() => `${scrollYProgress.value * 20}%`)
const bgOpacity = computed(() => 1 - scrollYProgress.value)

const textY = computed(() => `${scrollYProgress.value * -50}%`)
const textScale = computed(() => 1 - (scrollYProgress.value * 0.1))
const textRotate = computed(() => `${scrollYProgress.value * -5}deg`)

const buttonY = computed(() => `${scrollYProgress.value * -20}%`)

const jaguarY = computed(() => `${scrollYProgress.value * -40}%`)
const jaguarX = computed(() => `${scrollYProgress.value * 5}%`)
const jaguarOpacity = computed(() => 1 - scrollYProgress.value * 1.2)

const scrollToSection = (id) => {
  const element = document.getElementById(id)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' })
  }
}
</script>

<template>
  <section id="inicio" class="relative min-h-screen flex items-center justify-center overflow-hidden pt-28 lg:pt-32 pb-16">
    <!-- Animated Background -->
    <div
      :style="{ transform: `translateY(${bgY})`, opacity: bgOpacity }"
      class="absolute inset-0 bg-gradient-to-br from-green-50 via-yellow-50 to-blue-50 dark:from-gray-900 dark:via-green-900/20 dark:to-yellow-900/20 transition-all duration-75"
    >
      <div class="absolute inset-0 bg-grid-pattern opacity-10"></div>
    </div>

    <!-- Animated Floating Elements -->
    <div
      :style="{ transform: `translateY(${scrollYProgress * 150}%) scale(${1 + scrollYProgress * 0.3})` }"
      class="absolute top-20 left-10 w-72 h-72 bg-green-500/20 rounded-full blur-3xl animate-pulse transition-all duration-75"
    />

    <div
      :style="{ transform: `translateY(${scrollYProgress * -100}%) scale(${1 + scrollYProgress})` }"
      class="absolute bottom-20 right-10 w-96 h-96 bg-yellow-500/20 rounded-full blur-3xl animate-pulse animation-delay-2000 transition-all duration-75"
    />

    <div
      :style="{ transform: `translate(-50%, -50%) scale(${1 + scrollYProgress * 1.5})`, opacity: Math.max(0, 0.3 - scrollYProgress * 0.5) }"
      class="absolute top-1/2 left-1/2 w-[500px] h-[500px] bg-blue-500/10 rounded-full blur-3xl animate-pulse animation-delay-4000 transition-all duration-75"
    />

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid lg:grid-cols-2 gap-12 lg:gap-8 items-center">
        <!-- Left Content -->
        <div
          v-motion
          :initial="{ opacity: 0, y: 50 }"
          :enter="{ opacity: 1, y: 0, transition: { duration: 800 } }"
          :style="{
            transform: `translateY(${textY}) scale(${textScale}) rotate(${textRotate})`,
            filter: `blur(${scrollYProgress * 5}px)`
          }"
          class="space-y-8 text-center lg:text-left transition-all duration-75 z-10"
        >
          <!-- Badge -->
          <div
            v-motion
            :initial="{ opacity: 0, y: -20 }"
            :enter="{ opacity: 1, y: 0, transition: { duration: 600 } }"
            class="inline-flex items-center space-x-2 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-green-200 dark:border-green-800 rounded-full px-4 py-2 shadow-sm"
          >
            <Sparkles class="w-4 h-4 text-yellow-500" />
            <span class="text-sm font-medium">Formando líderes desde 1992</span>
          </div>

          <!-- Main Heading -->
          <h1
            v-motion
            :initial="{ opacity: 0, y: 20 }"
            :enter="{ opacity: 1, y: 0, transition: { duration: 600, delay: 200 } }"
            style="transform-style: preserve-3d; perspective: 1000px;"
            class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-bold tracking-tight leading-tight"
          >
            <span
              :style="{ transform: `translateY(${scrollYProgress * -15}%) scale(${1 + scrollYProgress * 0.05})` }"
              class="block mb-2 transition-all duration-75"
            >
              Educación de
            </span>
            <span
              :style="{
                transform: `translateY(${scrollYProgress * -30}%) scale(${1 + scrollYProgress * 0.1}) rotateX(${scrollYProgress * 5}deg)`
              }"
              class="block bg-gradient-to-r from-green-600 via-yellow-500 to-blue-600 bg-clip-text text-transparent transition-all duration-75 pb-2"
            >
              Excelencia
            </span>
          </h1>

          <!-- Subtitle -->
          <p
            v-motion
            :initial="{ opacity: 0, y: 20 }"
            :enter="{ opacity: 1, y: 0, transition: { duration: 600, delay: 400 } }"
            :style="{
              transform: `translateY(${scrollYProgress * 10}%)`,
              opacity: Math.max(0, 1 - scrollYProgress * 1.5)
            }"
            class="text-lg sm:text-xl text-muted-foreground max-w-2xl mx-auto lg:mx-0 transition-all duration-75"
          >
            Colegio Particular Mixto con <span class="font-semibold text-green-600">instalaciones modernas e innovadoras</span>, dedicado a formar estudiantes con <span class="font-semibold text-yellow-600">ciencia y disciplina</span>.
          </p>

          <!-- Stats -->
          <div
            v-motion
            :initial="{ opacity: 0, y: 20 }"
            :enter="{ opacity: 1, y: 0, transition: { duration: 600, delay: 600 } }"
            :style="{
              transform: `translateY(${scrollYProgress * 50}%) scale(${1 - scrollYProgress * 0.05})`,
              opacity: Math.max(0, 1 - scrollYProgress * 1.5)
            }"
            class="grid grid-cols-2 sm:grid-cols-3 gap-4 sm:gap-6 mb-8 transition-all duration-75"
          >
            <!-- Stat 1 -->
            <div
              :style="{
                transform: `rotateY(${scrollYProgress * 10}deg)`
              }"
              class="group relative p-4 sm:p-6 rounded-3xl bg-gradient-to-br from-green-500/10 via-emerald-500/10 to-teal-500/10 backdrop-blur-xl border-2 border-green-200/50 dark:border-green-700/50 cursor-pointer shadow-lg hover:shadow-xl hover:-translate-y-1 hover:scale-105 transition-all duration-500 overflow-hidden"
            >
              <div class="absolute inset-0 bg-gradient-to-br from-green-400/0 via-emerald-400/20 to-green-600/0 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
              <div class="relative z-10">
                <div class="text-3xl sm:text-4xl md:text-5xl font-black bg-gradient-to-br from-green-600 via-emerald-600 to-teal-700 bg-clip-text text-transparent mb-1 sm:mb-2 group-hover:scale-110 transition-transform duration-300">+33</div>
                <div class="text-xs sm:text-sm font-semibold text-foreground/80 group-hover:text-foreground transition-colors">Años de Excelencia</div>
                <div class="mt-2 sm:mt-3 h-1 w-12 rounded-full bg-gradient-to-r from-green-500 to-emerald-500 group-hover:w-full transition-all duration-500"></div>
              </div>
            </div>

            <!-- Stat 2 -->
            <div
              :style="{
                transform: `rotateY(${scrollYProgress * -10}deg)`
              }"
              class="group relative p-4 sm:p-6 rounded-3xl bg-gradient-to-br from-yellow-500/20 via-orange-500/20 to-amber-500/20 backdrop-blur-xl border-4 border-yellow-400/70 dark:border-yellow-600/70 cursor-pointer shadow-xl hover:shadow-2xl hover:-translate-y-1 hover:scale-105 transition-all duration-500 overflow-hidden ring-4 ring-yellow-300/30 dark:ring-yellow-700/30"
            >
              <div class="absolute inset-0 bg-gradient-to-br from-yellow-400/30 via-orange-400/40 to-yellow-600/30 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
              <div class="absolute top-2 right-2 text-yellow-400 text-xl sm:text-2xl">⭐</div>
              <div class="relative z-10">
                <div class="text-2xl sm:text-3xl md:text-4xl font-black bg-gradient-to-br from-yellow-600 via-orange-600 to-amber-700 bg-clip-text text-transparent mb-1 sm:mb-2 group-hover:scale-110 transition-transform duration-300 drop-shadow-sm">15,000+</div>
                <div class="text-xs sm:text-sm font-bold text-foreground group-hover:text-foreground transition-colors">Egresados Exitosos</div>
                <div class="mt-2 sm:mt-3 h-1.5 w-12 rounded-full bg-gradient-to-r from-yellow-500 to-orange-500 group-hover:w-full transition-all duration-500"></div>
              </div>
            </div>

            <!-- Stat 3 -->
            <div
              :style="{
                transform: `rotateY(${scrollYProgress * 15}deg)`
              }"
              @click="scrollToSection('carreras')"
              class="group relative p-4 sm:p-6 rounded-3xl bg-gradient-to-br from-blue-500/10 via-cyan-500/10 to-indigo-500/10 backdrop-blur-xl border-2 border-blue-200/50 dark:border-blue-700/50 cursor-pointer shadow-lg hover:shadow-xl hover:-translate-y-1 hover:scale-105 transition-all duration-500 overflow-hidden col-span-2 sm:col-span-1"
            >
              <div class="absolute inset-0 bg-gradient-to-br from-blue-400/0 via-cyan-400/20 to-blue-600/0 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
              <div class="relative z-10">
                <div class="text-3xl sm:text-4xl md:text-5xl font-black bg-gradient-to-br from-blue-600 via-cyan-600 to-indigo-700 bg-clip-text text-transparent mb-1 sm:mb-2 group-hover:scale-110 transition-transform duration-300">19</div>
                <div class="text-xs sm:text-sm font-semibold text-foreground/80 group-hover:text-foreground transition-colors">Carreras Educativas</div>
                <div class="mt-2 sm:mt-3 h-1 w-12 rounded-full bg-gradient-to-r from-blue-500 to-cyan-500 group-hover:w-full transition-all duration-500"></div>
              </div>
            </div>
          </div>

          <!-- CTA Buttons -->
          <div
            v-motion
            :initial="{ opacity: 0, y: 20 }"
            :enter="{ opacity: 1, y: 0, transition: { duration: 600, delay: 800 } }"
            :style="{
              transform: `translateY(${buttonY})`,
            }"
            class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start pt-4 sm:pt-6 transition-all duration-75 relative z-20"
          >
            <div class="hover:scale-105 hover:-rotate-1 transition-transform active:scale-95 w-full sm:w-auto">
              <Button
                @click="scrollToSection('niveles')"
                size="lg"
                class="w-full sm:w-auto relative bg-gradient-to-r from-green-600 to-yellow-500 hover:from-green-700 hover:to-yellow-600 text-white px-8 py-6 text-base sm:text-lg rounded-full group shadow-lg hover:shadow-xl transition-all overflow-hidden border-0"
              >
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                <span class="relative z-10 flex items-center justify-center">
                  Conocer Niveles
                  <ArrowRight class="ml-2 group-hover:translate-x-1 transition-transform" :size="20" />
                </span>
              </Button>
            </div>

            <div class="hover:scale-105 hover:rotate-1 transition-transform active:scale-95 w-full sm:w-auto">
              <Button
                @click="scrollToSection('contacto')"
                size="lg"
                class="w-full sm:w-auto relative group px-8 py-6 text-base sm:text-lg rounded-full backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border-2 border-green-500/50 hover:border-green-500 shadow-lg hover:shadow-xl transition-all overflow-hidden"
              >
                <div class="absolute inset-0 bg-gradient-to-r from-green-500/10 via-yellow-500/10 to-green-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <span class="relative z-10 flex items-center justify-center font-semibold bg-gradient-to-r from-green-600 to-yellow-600 bg-clip-text text-transparent">
                  <BookOpen class="mr-2" :size="20" />
                  Inscripciones 2026
                </span>
              </Button>
            </div>
          </div>
        </div>

        <!-- Right Content -->
        <div class="relative mt-12 lg:mt-0 min-h-[400px] sm:min-h-[500px] lg:min-h-[700px] flex flex-col items-center justify-center">
          <!-- Jaguar -->
          <div
            :style="{
              transform: `translate(${jaguarX}, ${jaguarY}) scale(${1 - scrollYProgress * 0.05})`,
              opacity: jaguarOpacity
            }"
            class="absolute inset-0 flex items-center justify-center transition-all duration-75 z-0"
          >
            <div class="relative w-64 h-64 sm:w-80 sm:h-80 lg:w-[450px] lg:h-[450px]">
              <div
                class="absolute inset-0 bg-gradient-to-br from-green-400/30 via-yellow-400/30 to-blue-400/20 rounded-full blur-3xl animate-pulse"
              />
              <img
                src="https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/Jaguarcin-3-cuartos-1761938045002.png?width=8000&height=8000&resize=contain"
                alt="Jaguar CYD"
                class="absolute inset-0 object-contain w-full h-full drop-shadow-2xl"
              />
            </div>
          </div>

          <!-- Interactive Cards -->
          <div
            v-motion
            :initial="{ opacity: 0, y: 40 }"
            :enter="{ opacity: 1, y: 0, transition: { duration: 800, delay: 1000 } }"
            class="relative lg:absolute -bottom-10 lg:-bottom-12 left-0 right-0 grid grid-cols-2 gap-3 sm:gap-4 z-20 mt-auto pt-64 lg:pt-0"
          >
            <!-- Card 1 -->
            <div class="group p-4 sm:p-6 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-600 text-white cursor-pointer shadow-lg hover:shadow-xl transition-all hover:scale-105 hover:-translate-y-1 active:scale-95">
              <Trophy class="w-8 h-8 sm:w-10 sm:h-10 mb-2 sm:mb-3 group-hover:rotate-12 group-hover:scale-110 transition-transform" />
              <h3 class="font-bold text-base sm:text-lg mb-1">Excelencia</h3>
              <p class="text-xs sm:text-sm opacity-90 hidden sm:block">Académica y deportiva</p>
            </div>

            <!-- Card 2 -->
            <div class="group p-4 sm:p-6 rounded-2xl bg-gradient-to-br from-yellow-500 to-orange-500 text-white cursor-pointer shadow-lg hover:shadow-xl transition-all hover:scale-105 hover:-translate-y-1 active:scale-95">
              <BookOpen class="w-8 h-8 sm:w-10 sm:h-10 mb-2 sm:mb-3 group-hover:scale-125 transition-transform" />
              <h3 class="font-bold text-base sm:text-lg mb-1">Educación</h3>
              <p class="text-xs sm:text-sm opacity-90 hidden sm:block">Integral y moderna</p>
            </div>

            <!-- Card 3 -->
            <div class="group p-4 sm:p-6 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-600 text-white cursor-pointer shadow-lg hover:shadow-xl transition-all hover:scale-105 hover:-translate-y-1 active:scale-95">
              <Users class="w-8 h-8 sm:w-10 sm:h-10 mb-2 sm:mb-3 group-hover:scale-125 transition-transform" />
              <h3 class="font-bold text-base sm:text-lg mb-1">Comunidad</h3>
              <p class="text-xs sm:text-sm opacity-90 hidden sm:block">Valores y respeto</p>
            </div>

            <!-- Card 4 -->
            <div
              @click="scrollToSection('facebook')"
              class="group p-4 sm:p-6 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 text-white cursor-pointer shadow-lg hover:shadow-xl transition-all hover:scale-105 hover:-translate-y-1 active:scale-95 flex flex-col items-center sm:items-start justify-center sm:justify-start text-center sm:text-left"
            >
              <div class="relative w-12 h-12 sm:w-16 sm:h-16 mb-2 mx-auto sm:mx-0">
                <img
                  src="https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/LOGO-2020-1761860820111.png?width=200&height=200&resize=contain"
                  alt="Logo CYD"
                  class="absolute inset-0 object-contain w-full h-full group-hover:rotate-6 group-hover:scale-110 transition-transform brightness-0 invert"
                />
              </div>
              <h3 class="font-bold text-base sm:text-lg mb-1">Social</h3>
              <p class="text-xs sm:text-sm opacity-90 hidden sm:block">Síguenos</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
