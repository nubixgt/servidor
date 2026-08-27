<template>
  <div class="bg-background text-on-surface font-body-md antialiased min-h-screen flex flex-col">
    <!-- Top Nav Bar -->
    <nav class="fixed top-0 w-full z-50 bg-background/80 backdrop-blur-lg border-b border-white/10 flex justify-between items-center px-container-margin py-4 transition-all duration-300">
      <div class="flex items-center gap-8">
        <router-link to="/" class="text-headline-lg-mobile md:text-headline-lg font-display-lg text-primary-fixed tracking-tighter uppercase leading-none">DEPORTES</router-link>
        <div class="hidden md:flex gap-6 items-center">
          <router-link to="/estadisticas" class="text-on-surface-variant font-medium hover:text-primary-fixed transition-colors duration-200">Estadísticas</router-link>
          <router-link to="/historial-partidos" class="text-on-surface-variant font-medium hover:text-primary-fixed transition-colors duration-200">Historial de Partidos</router-link>
          <router-link to="/inscripcion-equipo" class="text-on-surface-variant font-medium hover:text-primary-fixed transition-colors duration-200">Inscribir Equipo</router-link>
        </div>
      </div>
      <div class="flex items-center gap-4">
        <router-link
          to="/login"
          class="btn-primary px-6 py-2 rounded font-label-sm text-label-sm uppercase tracking-wider flex items-center gap-2"
        >
          <span class="material-symbols-outlined text-[16px]">login</span>
          Login
        </router-link>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow flex flex-col relative pt-[80px]">
      <!-- Hero Section -->
      <section class="relative w-full min-h-[870px] flex flex-col justify-center px-container-margin md:px-12 lg:px-24 py-stack-lg overflow-hidden">
        <img :src="fondoEstadio" class="absolute inset-0 w-full h-full object-cover opacity-30" alt="" />
        <div class="absolute inset-0 hero-bg"></div>

        <div class="max-w-4xl z-10 space-y-6">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-surface-container-high border border-white/10 mb-4">
            <span class="w-2 h-2 rounded-full bg-primary-fixed animate-pulse"></span>
            <span class="text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider">Liga Oficial Metropolitana</span>
          </div>
          <h1 class="text-headline-lg-mobile md:text-display-lg font-display-lg text-primary uppercase tracking-tighter leading-tight drop-shadow-2xl">
            GESTIÓN DE<br />
            <span class="text-primary-fixed">FÚTBOL AMATEUR</span>
          </h1>
          <p class="text-body-md font-body-md text-on-surface-variant max-w-xl md:text-lg border-l-2 border-primary-fixed pl-4 py-1">
            Excelencia institucional y rendimiento técnico para la nueva era del deporte amateur. Sistema
            integral para ligas, equipos y recintos deportivos de alto nivel.
          </p>

          <!-- Quick Access Bento Grid -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-gutter pt-stack-md mt-stack-md w-full max-w-5xl">
            <!-- Card 1: Registrar Equipo -->
            <router-link
              to="/inscripcion-equipo"
              class="glass-card-featured rounded-xl p-6 flex flex-col justify-between group h-48 neon-hover transition-all duration-300"
            >
              <div class="flex justify-between items-start">
                <div class="w-10 h-10 rounded-full bg-primary-fixed/20 flex items-center justify-center">
                  <span class="material-symbols-outlined text-primary-fixed">group_add</span>
                </div>
                <span class="material-symbols-outlined text-on-surface-variant group-hover:text-primary-fixed transition-colors">arrow_outward</span>
              </div>
              <div>
                <h3 class="text-title-md font-title-md text-primary mb-1 group-hover:text-primary-fixed transition-colors">Registrar Equipo</h3>
                <p class="text-label-sm font-label-sm text-on-surface-variant uppercase">Inscripciones Abiertas</p>
              </div>
            </router-link>

            <!-- Card 2: Iniciar Sesión -->
            <router-link
              to="/login"
              class="glass-card rounded-xl p-6 flex flex-col justify-between group h-48 hover:border-white/30 transition-all duration-300"
            >
              <div class="flex justify-between items-start">
                <div class="w-10 h-10 rounded-full bg-surface-container-high flex items-center justify-center">
                  <span class="material-symbols-outlined text-on-surface">sports_soccer</span>
                </div>
                <span class="material-symbols-outlined text-on-surface-variant group-hover:text-white transition-colors">arrow_forward</span>
              </div>
              <div>
                <h3 class="text-title-md font-title-md text-primary mb-1 group-hover:text-white transition-colors">Portal de Jugador</h3>
                <p class="text-label-sm font-label-sm text-on-surface-variant uppercase">Gestión personal</p>
              </div>
            </router-link>

            <!-- Card 3: Ver Estadísticas -->
            <router-link
              to="/estadisticas"
              class="glass-card rounded-xl p-6 flex flex-col justify-between group h-48 hover:border-white/30 transition-all duration-300"
            >
              <div class="flex justify-between items-start">
                <div class="w-10 h-10 rounded-full bg-surface-container-high flex items-center justify-center">
                  <span class="material-symbols-outlined text-on-surface">bar_chart</span>
                </div>
                <span class="material-symbols-outlined text-on-surface-variant group-hover:text-white transition-colors">arrow_forward</span>
              </div>
              <div>
                <h3 class="text-title-md font-title-md text-primary mb-1 group-hover:text-white transition-colors">Ver Estadísticas</h3>
                <p class="text-label-sm font-label-sm text-on-surface-variant uppercase">Data en tiempo real</p>
              </div>
            </router-link>
          </div>
        </div>

        <!-- Atmospheric Shader Overlay -->
        <div class="absolute inset-0 pointer-events-none opacity-20 mix-blend-screen z-0">
          <div class="w-full h-full bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-primary-fixed/5 via-transparent to-transparent"></div>
        </div>
      </section>
    </main>

    <!-- System Status Bar -->
    <div class="fixed bottom-0 w-full bg-surface-container-lowest border-t border-white/5 py-2 px-container-margin z-40 hidden md:flex justify-between items-center text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider backdrop-blur-md">
      <div class="flex items-center gap-6">
        <span class="flex items-center gap-2">
          <span class="material-symbols-outlined text-[14px]">calendar_today</span>
          Temporada Apertura 2026
        </span>
        <span class="w-px h-3 bg-white/10"></span>
        <router-link to="/historial-partidos" class="flex items-center gap-2 hover:text-primary-fixed transition-colors">
          <span class="material-symbols-outlined text-[14px]">groups</span>
          Ver Equipos Activos
        </router-link>
      </div>
      <div class="flex items-center gap-2 text-primary-fixed">
        <span class="w-2 h-2 rounded-full bg-primary-fixed shadow-[0_0_8px_#C0FD46]"></span>
        Sistema Operativo
      </div>
    </div>
  </div>
</template>

<script setup>
import fondoEstadio from '../assets/fondo-estadio.jpg'
</script>
