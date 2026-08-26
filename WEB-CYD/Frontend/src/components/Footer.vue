<script setup>
import { Facebook, Instagram, Mail, Phone, MapPin } from 'lucide-vue-next'

const scrollToSection = (id) => {
  const element = document.getElementById(id)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' })
  }
}
</script>

<template>
  <footer class="bg-gradient-to-br from-gray-900 to-gray-800 text-white pt-16 pb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
        <!-- Logo & About -->
        <div class="space-y-4">
          <div class="flex items-center space-x-3">
            <div class="relative w-12 h-12">
              <img
                src="https://slelguoygbfzlpylpxfs.supabase.co/storage/v1/render/image/public/document-uploads/LOGO-2020-1761860820111.png?width=100&height=100&resize=contain"
                alt="Logo CYD"
                class="absolute inset-0 object-contain w-full h-full"
              />
            </div>
            <div>
              <div class="text-lg font-bold bg-gradient-to-r from-green-400 to-yellow-400 bg-clip-text text-transparent">
                Colegio CYD
              </div>
              <div class="text-xs text-gray-400 whitespace-pre-line">Ciencia y Desarrollo</div>
            </div>
          </div>
          <p class="text-sm text-gray-400 whitespace-pre-line">
            Formando líderes con valores desde 1992. Educación de excelencia en Salama, Baja Verapaz.
          </p>
          <div class="flex space-x-3">
            <a
              href="https://facebook.com"
              target="_blank"
              rel="noopener noreferrer"
              class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
            >
              <Facebook :size="20" />
            </a>
            <a
              href="https://instagram.com"
              target="_blank"
              rel="noopener noreferrer"
              class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
            >
              <Instagram :size="20" />
            </a>
          </div>
        </div>

        <!-- Enlaces Rápidos -->
        <div>
          <h4 class="font-bold mb-4 text-lg">Enlaces Rápidos</h4>
          <ul class="space-y-2 text-sm">
            <li>
              <button @click="scrollToSection('inicio')" class="text-gray-400 hover:text-white transition-colors">
                Inicio
              </button>
            </li>
            <li>
              <button @click="scrollToSection('niveles')" class="text-gray-400 hover:text-white transition-colors">
                Niveles Educativos
              </button>
            </li>
            <li>
              <button @click="scrollToSection('actividades')" class="text-gray-400 hover:text-white transition-colors">
                Actividades
              </button>
            </li>
            <li>
              <button @click="scrollToSection('nosotros')" class="text-gray-400 hover:text-white transition-colors">
                Sobre Nosotros
              </button>
            </li>
            <li>
              <button @click="scrollToSection('galeria')" class="text-gray-400 hover:text-white transition-colors">
                Galería
              </button>
            </li>
            <li>
              <button @click="scrollToSection('contacto')" class="text-gray-400 hover:text-white transition-colors">
                Contacto
              </button>
            </li>
          </ul>
        </div>

        <!-- Niveles -->
        <div>
          <h4 class="font-bold mb-4 text-lg">Niveles</h4>
          <ul class="space-y-2 text-sm text-gray-400">
            <li class="hover:text-white transition-colors cursor-pointer">Preprimaria</li>
            <li class="hover:text-white transition-colors cursor-pointer">Primaria</li>
            <li class="hover:text-white transition-colors cursor-pointer">Básicos</li>
            <li class="hover:text-white transition-colors cursor-pointer">Diversificado</li>
            <li class="hover:text-white transition-colors cursor-pointer">Bachillerato en Ciencias</li>
            <li class="hover:text-white transition-colors cursor-pointer">Perito Contador</li>
          </ul>
        </div>

        <!-- Contacto -->
        <div>
          <h4 class="font-bold mb-4 text-lg">Contacto</h4>
          <ul class="space-y-3 text-sm text-gray-400">
            <li class="flex items-start space-x-3">
              <MapPin :size="18" class="mt-0.5 flex-shrink-0" />
              <span>Salama, Baja Verapaz, Guatemala</span>
            </li>
            <li class="flex items-start space-x-3">
              <Phone :size="18" class="mt-0.5 flex-shrink-0" />
              <div>
                <div>+502 1234-5678</div>
                <div>+502 9876-5432</div>
              </div>
            </li>
            <li class="flex items-start space-x-3">
              <Mail :size="18" class="mt-0.5 flex-shrink-0" />
              <div>
                <div>info@colegiocyd.edu.gt</div>
                <div>inscripciones@colegiocyd.edu.gt</div>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <!-- Bottom Bar -->
      <div class="border-t border-white/10 pt-8 mt-8">
        <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
          <p class="text-sm text-gray-400 text-center md:text-left">
            © {{ new Date().getFullYear() }} Colegio Particular Mixto CYD. Todos los derechos reservados.
          </p>
          <div class="flex space-x-6 text-sm text-gray-400">
            <a href="#" class="hover:text-white transition-colors">Privacidad</a>
            <a href="#" class="hover:text-white transition-colors">Términos</a>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>
