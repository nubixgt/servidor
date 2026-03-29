<template>
  <div class="min-h-screen flex flex-col bg-[#f8fafb]">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
      <div class="max-w-5xl mx-auto px-6 py-3 flex items-center justify-between">
        <!-- Logos -->
        <div class="flex items-center gap-4">
          <img src="/images/LogoUBA3.png" alt="UBA" class="h-12 object-contain" />
          <img src="/images/maga_logo.png" alt="MAGA" class="h-12 object-contain" />
        </div>
        <!-- Links desktop -->
        <div class="hidden md:flex items-center gap-2">
          <router-link to="/" class="nav-link" :class="{ active: $route.path === '/' }">Inicio</router-link>
          <router-link to="/como-reportar" class="nav-link" :class="{ active: $route.path === '/como-reportar' }">Cómo Reportar</router-link>
          <router-link to="/noticias" class="nav-link" :class="{ active: $route.path === '/noticias' }">Noticias</router-link>
          <router-link to="/informacion" class="nav-link" :class="{ active: $route.path === '/informacion' }">Información</router-link>
          <router-link to="/denuncia" class="ml-2 bg-[#8B0000] text-white font-bold px-5 py-2 rounded-full hover:bg-[#6d0000] transition-colors flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">gavel</span>
            Realizar Denuncia
          </router-link>
        </div>
        <!-- Hamburger mobile -->
        <button @click="menuOpen = !menuOpen" class="md:hidden p-2">
          <span class="material-symbols-outlined">{{ menuOpen ? 'close' : 'menu' }}</span>
        </button>
      </div>
      <!-- Mobile menu -->
      <div v-if="menuOpen" class="md:hidden bg-white border-t px-6 pb-4 flex flex-col gap-2">
        <router-link to="/" @click="menuOpen = false" class="nav-link-mobile">Inicio</router-link>
        <router-link to="/como-reportar" @click="menuOpen = false" class="nav-link-mobile">Cómo Reportar</router-link>
        <router-link to="/noticias" @click="menuOpen = false" class="nav-link-mobile">Noticias</router-link>
        <router-link to="/informacion" @click="menuOpen = false" class="nav-link-mobile">Información</router-link>
        <router-link to="/denuncia" @click="menuOpen = false" class="mt-2 bg-[#8B0000] text-white font-bold px-5 py-3 rounded-full text-center">
          Realizar Denuncia
        </router-link>
      </div>
    </nav>

    <!-- Page content -->
    <main class="flex-1">
      <router-view />
    </main>

    <!-- Footer -->
    <footer class="bg-[#0f2a4a] text-white mt-16 py-10 px-6">
      <div class="max-w-5xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <img src="/images/LogoUBA3.png" alt="UBA" class="h-12 object-contain brightness-0 invert" />
          <div>
            <p class="font-bold text-sm">UNIDAD DE BIENESTAR ANIMAL</p>
            <p class="text-white/60 text-xs">4A Calle 0-15, Ciudad de Guatemala</p>
          </div>
        </div>
        <p class="text-white/40 text-xs">© {{ new Date().getFullYear() }} UBApp — Todos los derechos reservados</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref } from 'vue'
const menuOpen = ref(false)
</script>

<style scoped>
.nav-link {
  @apply text-sm font-medium text-gray-600 px-3 py-2 rounded-lg hover:bg-gray-100 hover:text-[#0f2a4a] transition-colors;
}
.nav-link.active {
  @apply text-[#0f2a4a] font-bold bg-gray-100;
}
.nav-link-mobile {
  @apply text-sm font-medium text-gray-700 py-2 border-b border-gray-100;
}
</style>
