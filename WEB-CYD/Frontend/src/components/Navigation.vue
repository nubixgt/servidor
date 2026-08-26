<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Menu, X, GraduationCap, BookOpen, Users, Calendar, Image as ImageIcon, Phone, Zap } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

const isScrolled = ref(false)
const isMobileMenuOpen = ref(false)

const handleScroll = () => {
  isScrolled.value = window.scrollY > 20
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})

const scrollToSection = (id) => {
  const element = document.getElementById(id)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' })
    isMobileMenuOpen.value = false
  }
}

const menuItems = [
  { id: 'inicio', label: 'Inicio', icon: GraduationCap },
  { id: 'tecnologia', label: 'Tecnología', icon: Zap },
  { id: 'niveles', label: 'Niveles', icon: BookOpen },
  { id: 'actividades', label: 'Actividades', icon: Users },
  { id: 'nosotros', label: 'Nosotros', icon: Users },
  { id: 'galeria', label: 'Galería', icon: ImageIcon },
  { id: 'contacto', label: 'Contacto', icon: Phone }
]
</script>

<template>
  <nav :class="[
    'fixed w-full top-0 z-50 transition-all duration-300',
    (isScrolled || isMobileMenuOpen) ? 'bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl shadow-lg' : 'bg-transparent'
  ]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-20">
        <!-- Logo -->
        <div class="flex items-center space-x-3 cursor-pointer" @click="scrollToSection('inicio')">
          <div class="relative w-14 h-14 sm:w-16 sm:h-16">
            <img
              src="https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/LOGO-2020-1761860820111.png?width=200&height=200&resize=contain"
              alt="Colegio CYD"
              class="absolute inset-0 object-contain w-full h-full max-w-full"
            />
          </div>
          <div class="hidden sm:block">
            <div class="text-xl font-bold bg-gradient-to-r from-green-600 to-yellow-500 bg-clip-text text-transparent w-full h-[26px]">
              Colegio CYD
            </div>
            <div class="text-xs text-muted-foreground w-[99.8%] h-8 whitespace-pre-line">Ciencia y Desarrollo</div>
          </div>
        </div>
        
        <!-- Desktop Menu -->
        <div class="hidden lg:flex items-center space-x-1">
          <button
            v-for="item in menuItems"
            :key="item.id"
            @click="scrollToSection(item.id)"
            class="flex items-center space-x-2 px-4 py-2 text-foreground/80 hover:text-foreground hover:bg-green-50 dark:hover:bg-green-950/30 rounded-lg transition-all font-medium group"
          >
            <component :is="item.icon" :size="18" class="group-hover:scale-110 transition-transform" />
            <span>{{ item.label }}</span>
          </button>
        </div>

        <div class="hidden lg:block">
          <Button
            @click="scrollToSection('contacto')"
            class="bg-gradient-to-r from-green-600 to-yellow-500 hover:from-green-700 hover:to-yellow-600 text-white shadow-lg hover:shadow-xl transition-all"
          >
            Inscripciones
          </Button>
        </div>

        <!-- Mobile Menu Button -->
        <button
          @click="isMobileMenuOpen = !isMobileMenuOpen"
          class="lg:hidden p-2 rounded-lg hover:bg-muted transition-colors"
        >
          <X v-if="isMobileMenuOpen" :size="24" />
          <Menu v-else :size="24" />
        </button>
      </div>

      <!-- Mobile Menu -->
      <div v-if="isMobileMenuOpen" class="lg:hidden py-4 space-y-2 border-t border-border/50 animate-fade-in">
        <button
          v-for="item in menuItems"
          :key="item.id"
          @click="scrollToSection(item.id)"
          class="flex items-center space-x-3 w-full px-4 py-3 text-foreground/80 hover:text-foreground hover:bg-green-50 dark:hover:bg-green-950/30 rounded-lg transition-all"
        >
          <component :is="item.icon" :size="20" />
          <span>{{ item.label }}</span>
        </button>
        <div class="px-4 pt-2">
          <Button
            @click="scrollToSection('contacto')"
            class="w-full bg-gradient-to-r from-green-600 to-yellow-500 text-white"
          >
            Inscripciones
          </Button>
        </div>
      </div>
    </div>
  </nav>
</template>
