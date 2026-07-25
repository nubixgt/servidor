<template>
  <div class="min-h-screen flex flex-col bg-panel text-text-light">
    <!-- Navbar -->
    <header class="border-b border-gray-800 bg-dark py-4">
      <div class="container mx-auto px-4 flex justify-between items-center">
        <div class="flex items-center gap-2">
          <div class="w-6 h-8 bg-primary/20 flex items-center justify-center rounded-sm border border-primary/40">
            <span class="text-primary font-bold text-sm">D</span>
          </div>
          <span class="font-bold text-xl tracking-tight text-white">DEPORTES</span>
        </div>
        
        <nav class="hidden md:flex gap-8 text-sm font-semibold tracking-wide">
          <router-link to="/" class="hover:text-primary transition-colors" active-class="text-primary border-b-2 border-primary pb-1">INICIO</router-link>
          <router-link to="/listado" class="hover:text-primary transition-colors" active-class="text-primary border-b-2 border-primary pb-1">EQUIPOS Y JUGADORES</router-link>
        </nav>
        
        <div class="w-8 h-8 rounded-full bg-gray-700 overflow-hidden border border-gray-600 flex items-center justify-center text-gray-400">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow">
      <router-view />
    </main>
    
    <!-- Footer -->
    <footer class="border-t border-gray-800 bg-dark py-6 mt-12">
      <div class="container mx-auto px-4 flex flex-col md:flex-row justify-between items-center text-xs text-text-muted">
        <div class="flex items-center gap-2 mb-4 md:mb-0">
          <div class="w-4 h-4 text-gray-500">
            <!-- Icon placeholder -->
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
          </div>
          <span>&copy; {{ new Date().getFullYear() }} DEPORTES Institucional. Todos los derechos reservados.</span>
        </div>
        <div class="flex gap-4">
          <a href="#" class="hover:text-white transition-colors">Privacidad</a>
          <a href="#" class="hover:text-white transition-colors">Términos</a>
          <a href="#" class="hover:text-white transition-colors">Contacto</a>
        </div>
      </div>
    </footer>
  </div>
</template>

<style>
/* Active classes handled by vue-router */
</style>
