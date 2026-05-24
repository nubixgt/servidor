<template>
  <div class="min-h-screen bg-app-scenic flex flex-col relative">
    <!-- Navbar (Basic overlay example) -->
    <nav class="glass-topbar sticky top-0 z-50 px-6 py-4 flex justify-between items-center" data-aos="fade-down" data-aos-duration="1000">
      <div class="text-2xl font-bold text-white tracking-wide">
        CONCRETOS <span class="text-[#6366f1]">ORIENTE</span>
      </div>
      <div class="flex gap-4">
        <router-link to="/login" class="px-6 py-2 rounded-full glass-button-primary text-sm font-medium hover:scale-105 transition-transform">
          Acceder
        </router-link>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="flex-grow flex flex-col items-center justify-center text-center px-4 pt-20 pb-32">
      <h1 class="text-5xl md:text-7xl font-extrabold text-white mb-6 tracking-tight drop-shadow-lg" data-aos="zoom-in" data-aos-duration="1500" data-aos-easing="ease-out-back">
        Construyendo el <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-500 animate-pulse">Futuro</span>
      </h1>
      <p class="text-xl md:text-2xl text-slate-200 max-w-2xl mb-10 drop-shadow-md" data-aos="fade-up" data-aos-delay="300">
        Soluciones en concreto de la más alta calidad para tus proyectos más exigentes en el oriente.
      </p>
      <div class="flex gap-4" data-aos="fade-up" data-aos-delay="500">
        <a href="#servicios" class="px-8 py-3 rounded-full glass-button-primary text-lg font-semibold hover:shadow-lg hover:shadow-indigo-500/30 hover:-translate-y-3 hover:scale-105 hover:shadow-[0_20px_40px_-15px_rgba(99,102,241,0.5)] transition-all">
          Nuestros Servicios
        </a>
      </div>
    </section>

    <!-- Services Section -->
    <section id="servicios" class="py-24 px-6 md:px-12 bg-slate-900/80 backdrop-blur-md border-t border-white/5">
      <div class="max-w-6xl mx-auto">
        <div class="text-center mb-16" data-aos="fade-up">
          <h2 class="text-4xl font-bold mb-4">Lo Que Ofrecemos</h2>
          <div class="h-1 w-20 bg-indigo-500 mx-auto rounded-full"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <!-- Card 1 -->
          <div class="glass-card p-8 rounded-2xl hover:-translate-y-4 hover:scale-105 hover:shadow-[0_20px_50px_-15px_rgba(99,102,241,0.5)] transition-all duration-500 group" data-aos="flip-left" data-aos-duration="1200" data-aos-delay="200">
            <div class="w-14 h-14 rounded-full bg-indigo-500/20 flex items-center justify-center mb-6 group-hover:scale-125 group-hover:rotate-12 transition-transform duration-500">
              <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            </div>
            <h3 class="text-xl font-bold mb-3 text-white">Concreto Premezclado</h3>
            <p class="text-slate-300 leading-relaxed">
              Mezclas diseñadas a la medida con la resistencia exacta que tu obra requiere, entregadas a tiempo.
            </p>
          </div>

          <!-- Card 2 -->
          <div class="glass-card p-8 rounded-2xl hover:-translate-y-4 hover:scale-105 hover:shadow-[0_20px_50px_-15px_rgba(99,102,241,0.5)] transition-all duration-500 group" data-aos="flip-up" data-aos-duration="1200" data-aos-delay="400">
            <div class="w-14 h-14 rounded-full bg-indigo-500/20 flex items-center justify-center mb-6 group-hover:scale-125 group-hover:-rotate-12 transition-transform duration-500">
              <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <h3 class="text-xl font-bold mb-3 text-white">Bombeo Especializado</h3>
            <p class="text-slate-300 leading-relaxed">
              Equipos de bombeo de última generación para llegar a cualquier altura o distancia en tu proyecto.
            </p>
          </div>

          <!-- Card 3 -->
          <div class="glass-card p-8 rounded-2xl hover:-translate-y-4 hover:scale-105 hover:shadow-[0_20px_50px_-15px_rgba(99,102,241,0.5)] transition-all duration-500 group" data-aos="flip-right" data-aos-duration="1200" data-aos-delay="600">
            <div class="w-14 h-14 rounded-full bg-indigo-500/20 flex items-center justify-center mb-6 group-hover:scale-125 group-hover:rotate-12 transition-transform duration-500">
              <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            </div>
            <h3 class="text-xl font-bold mb-3 text-white">Asesoría Técnica</h3>
            <p class="text-slate-300 leading-relaxed">
              Acompañamiento experto desde la planeación hasta el colado para garantizar el éxito estructural.
            </p>
          </div>
        </div>
      </div>
    </section>
    
    <!-- Footer -->
    <footer class="glass-topbar py-8 text-center text-slate-400">
      <p>&copy; 2026 Concretos Oriente. Todos los derechos reservados.</p>
    </footer>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import AOS from 'aos'

onMounted(() => {
  // Refrescar AOS en caso de que cambien componentes dinámicamente
  AOS.refresh()
})
</script>
