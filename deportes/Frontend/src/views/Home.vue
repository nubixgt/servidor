<template>
  <div class="min-h-screen bg-[#0a0a0a] flex flex-col items-center justify-center p-4 relative overflow-hidden">
    <!-- Abstract Background -->
    <div class="absolute inset-0 z-0 bg-black">
      <!-- Image Background -->
      <div class="absolute inset-0 bg-[url('../assets/fondo-estadio.jpg')] bg-cover bg-center bg-no-repeat opacity-40 mix-blend-luminosity"></div>
      
      <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-[#121212]/80 via-transparent to-[#050505]/90"></div>
      <!-- Decorative elements simulating the stadium/lights -->
      <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-[#ccff00] rounded-full mix-blend-overlay filter blur-[120px] opacity-10"></div>
      <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-[#ccff00] rounded-full mix-blend-overlay filter blur-[120px] opacity-10"></div>
    </div>

    <div class="z-10 w-full max-w-4xl flex flex-col items-center">
      <!-- Header / Logo -->
      <div class="mb-12 text-center">
        <div class="w-20 h-24 mx-auto mb-6 bg-[#0a0a0a] border-2 border-[#ccff00] rounded-t-full rounded-b-xl flex items-center justify-center relative shadow-[0_0_15px_rgba(204,255,0,0.3)]">
          <svg class="w-10 h-10 text-white" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z" />
          </svg>
          <div class="absolute top-2 w-2 h-2 bg-[#ccff00] rounded-full"></div>
        </div>
        <p class="text-[#ccff00] text-xs font-bold tracking-[0.3em] uppercase mb-2 flex items-center justify-center gap-4">
          <span class="h-px w-8 bg-gray-600"></span>
          PLATAFORMA INSTITUCIONAL
          <span class="h-px w-8 bg-gray-600"></span>
        </p>
        <h1 class="text-6xl md:text-8xl font-black text-white italic tracking-tighter">
          DEPORTES
        </h1>
        <p class="text-gray-400 mt-4 text-sm md:text-base max-w-md mx-auto">
          Gestiona la información de tu equipo de fútbol de forma profesional, rápida y segura.
        </p>
      </div>

      <!-- Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full max-w-3xl">
        
        <!-- Registro Card -->
        <div class="bg-[#121212] border border-[#ccff00]/40 rounded-2xl p-8 flex flex-col items-center text-center transition-all hover:border-[#ccff00] hover:shadow-[0_0_30px_rgba(204,255,0,0.15)] group">
          <div class="w-16 h-16 rounded-full border border-[#ccff00] flex items-center justify-center mb-6">
            <svg class="w-8 h-8 text-[#ccff00]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
          </div>
          <h2 class="text-white text-xl font-bold mb-1">REGISTRO DE</h2>
          <h3 class="text-[#ccff00] text-3xl font-black italic tracking-tight mb-4">EQUIPOS</h3>
          <p class="text-gray-400 text-sm mb-8 flex-grow">
            Registra un nuevo equipo y forma parte de nuestra plataforma deportiva.
          </p>
          <router-link to="/inscripcion-equipo" class="w-full bg-[#ccff00] text-black font-bold py-3 px-6 rounded-full flex justify-between items-center hover:bg-[#b3e600] transition-colors">
            <span>REGISTRAR EQUIPO</span>
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg>
          </router-link>
        </div>

        <!-- Login Card -->
        <div class="bg-[#121212] border border-gray-800 rounded-2xl p-8 flex flex-col items-center text-center transition-all hover:border-gray-600 group">
          <div class="w-16 h-16 rounded-full border border-gray-400 flex items-center justify-center mb-6">
            <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
          </div>
          <h2 class="text-white text-xl font-bold mb-1">INICIAR</h2>
          <h3 class="text-white text-3xl font-black italic tracking-tight mb-4">SESIÓN</h3>
          <p class="text-gray-400 text-sm mb-8 flex-grow">
            Accede a tu cuenta para gestionar tu equipo y jugadores.
          </p>
          <router-link to="/login" class="w-full bg-transparent border border-gray-600 text-white font-bold py-3 px-6 rounded-full flex justify-between items-center hover:bg-gray-800 transition-colors">
            <span>INICIAR SESIÓN</span>
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg>
          </router-link>
        </div>

      </div>

      <!-- Footer tiny info -->
      <div class="mt-16 flex items-center gap-3 text-xs text-gray-500">
        <div class="w-6 h-8 bg-transparent border border-gray-600 rounded-t-full rounded-b-md flex items-center justify-center">
           <div class="w-1 h-1 bg-gray-400 rounded-full"></div>
        </div>
        <div class="text-left">
          <p>Plataforma institucional de deportes</p>
          <p>Gestión profesional para equipos campeones.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Home page logic
</script>
