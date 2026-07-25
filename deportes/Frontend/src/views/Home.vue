<template>
  <div class="container mx-auto px-4 py-8 md:py-16">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
      
      <!-- Text Content -->
      <div>
        <h2 class="text-primary font-bold text-sm tracking-widest uppercase mb-2 flex items-center gap-2">
          <span class="w-8 h-[2px] bg-primary block"></span> PLATAFORMA INSTITUCIONAL
        </h2>
        <h1 class="text-7xl font-black italic tracking-tighter mb-6">DEPORTES</h1>
        <p class="text-gray-300 mb-8 max-w-md text-sm leading-relaxed">
          Lleva tu equipo al siguiente nivel con la infraestructura digital líder en gestión deportiva profesional.
        </p>
        
        <div class="flex flex-col sm:flex-row gap-4 mb-16">
          <router-link to="/inscripcion-equipo" class="bg-primary hover:bg-[#aacc00] text-black font-bold py-3 px-8 text-sm transition-colors text-center">
            INSCRIBIR EQUIPO
          </router-link>
          <router-link to="/inscripcion-jugador" class="bg-transparent border border-gray-600 hover:border-gray-400 text-white font-bold py-3 px-8 text-sm transition-colors text-center">
            INSCRIBIR JUGADOR
          </router-link>
        </div>
        
        <!-- Stats -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div class="bg-panel p-4 border-l-2 border-primary">
            <div class="text-primary text-xs flex justify-between items-center mb-2">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
              <span>REGISTRO</span>
            </div>
            <div class="text-3xl font-black mb-1">128</div>
            <div class="text-[10px] text-gray-400 uppercase tracking-wide">EQUIPOS REGISTRADOS</div>
          </div>
          
          <div class="bg-panel p-4 border-l-2 border-gray-600">
            <div class="text-gray-400 text-xs flex justify-between items-center mb-2">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
              <span>TALENTO</span>
            </div>
            <div class="text-3xl font-black mb-1">2450</div>
            <div class="text-[10px] text-gray-400 uppercase tracking-wide">JUGADORES ACTIVOS</div>
            <div class="text-primary text-[10px] mt-1 flex items-center gap-1">
              <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/></svg>
              +12% este mes
            </div>
          </div>
          
          <div class="bg-panel p-4 border-l-2 border-[#ff6b6b]">
            <div class="text-[#ff6b6b] text-xs flex justify-between items-center mb-2">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5v18l7-3 7 3V3z"/></svg>
              <span>COMPETICIÓN</span>
            </div>
            <div class="text-3xl font-black mb-1">12</div>
            <div class="text-[10px] text-gray-400 uppercase tracking-wide">TORNEOS EN CURSO</div>
          </div>
          
          <div class="bg-panel p-4 border-l-2 border-primary">
            <div class="text-primary text-xs flex justify-between items-center mb-2">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg>
              <span>MÉTRICAS</span>
            </div>
            <div class="text-3xl font-black mb-1">8900</div>
            <div class="text-[10px] text-gray-400 uppercase tracking-wide">GOLES MARCADOS</div>
          </div>
        </div>
      </div>
      
      <!-- Right Image Area -->
      <div class="relative h-full min-h-[400px]">
        <div class="absolute inset-0 bg-gradient-to-t from-dark via-transparent to-transparent z-10"></div>
        <div class="w-full h-full min-h-[400px] bg-gradient-to-br from-[#121212] via-[#1a1a1a] to-[#2a2a2a] rounded-lg border border-gray-800 shadow-2xl flex items-center justify-center">
          <svg class="w-48 h-48 text-primary opacity-20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        </div>
        
        <!-- Overlay Card -->
        <div class="absolute bottom-4 left-4 right-4 z-20">
          <div class="bg-dark/90 backdrop-blur-sm border border-gray-800 p-6 flex flex-col md:flex-row gap-6 items-center">
            <div class="flex-grow">
              <h3 class="text-white font-bold italic text-lg mb-2">DOMINA EL JUEGO</h3>
              <p class="text-xs text-gray-400 mb-4">Nuestra plataforma no solo gestiona inscripciones, ofrece un ecosistema completo de seguimiento de rendimiento, estadísticas en tiempo real y perfiles de jugadores profesionales.</p>
              <div class="space-y-2">
                <div class="flex items-center gap-2 text-xs text-gray-300">
                  <svg class="w-4 h-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                  Validación oficial de ligas
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-300">
                  <svg class="w-4 h-4 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                  Dashboard de rendimiento avanzado
                </div>
              </div>
            </div>
            <div class="bg-primary text-black font-bold p-4 text-center uppercase text-sm leading-tight max-w-[150px]">
              Únete a la liga más prestigiosa del país
            </div>
          </div>
        </div>
      </div>
      
    </div>
  </div>
</template>
