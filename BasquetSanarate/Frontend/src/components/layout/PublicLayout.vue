<template>
  <div class="min-h-screen bg-surface text-on-surface flex flex-col">
    <!-- Header / Navbar Público -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-inverse-surface/95 backdrop-blur-md shadow-[0_4px_20px_rgba(0,0,0,0.25)]">
      <div class="h-20 max-w-[1280px] mx-auto px-gutter-desktop flex items-center justify-between">
        <!-- Logo / Marca -->
        <router-link to="/" class="flex items-center gap-space-md">
          <img 
            alt="Logo Liga de Baloncesto Sanarateca" 
            class="h-10 w-auto object-contain" 
            :src="logoUrl"
          />
          <div class="flex flex-col">
            <span class="font-headline-md text-headline-md uppercase tracking-wider text-surface leading-none">Sanarateca</span>
            <span class="font-label-meta text-label-meta text-primary-container tracking-widest uppercase">Liga de Baloncesto</span>
          </div>
        </router-link>

        <!-- Navegación Desktop -->
        <nav class="hidden xl:flex items-center gap-space-lg">
          <router-link 
            to="/" 
            class="relative uppercase transition-colors duration-200 py-space-xs font-label-pill text-label-pill text-surface-dim hover:text-surface"
            active-class="!text-primary-container font-semibold"
          >Inicio</router-link>
          
          <router-link 
            to="/equipos" 
            class="relative uppercase transition-colors duration-200 py-space-xs font-label-pill text-label-pill text-surface-dim hover:text-surface"
            active-class="!text-primary-container font-semibold"
          >Equipos</router-link>
          
          <router-link 
            to="/jugadores" 
            class="relative uppercase transition-colors duration-200 py-space-xs font-label-pill text-label-pill text-surface-dim hover:text-surface"
            active-class="!text-primary-container font-semibold"
          >Jugadores</router-link>

          <router-link 
            to="/partidos" 
            class="relative uppercase transition-colors duration-200 py-space-xs font-label-pill text-label-pill text-surface-dim hover:text-surface"
            active-class="!text-primary-container font-semibold"
          >Partidos</router-link>

          <router-link 
            to="/estadisticas" 
            class="relative uppercase transition-colors duration-200 py-space-xs font-label-pill text-label-pill text-surface-dim hover:text-surface"
            active-class="!text-primary-container font-semibold"
          >Estadísticas</router-link>

          <router-link 
            to="/novedades" 
            class="relative uppercase transition-colors duration-200 py-space-xs font-label-pill text-label-pill text-surface-dim hover:text-surface"
            active-class="!text-primary-container font-semibold"
          >Novedades</router-link>
        </nav>

        <!-- Acceso / Acciones -->
        <div class="flex items-center gap-space-md">
          <router-link 
            to="/login" 
            class="hidden sm:inline-flex items-center justify-center rounded-full bg-primary-container text-on-primary-fixed px-space-lg py-space-xs font-label-pill text-label-pill uppercase shadow-[0_4px_16px_rgba(204,255,0,0.35)] hover:shadow-[0_6px_24px_rgba(204,255,0,0.55)] hover:scale-105 transition-all duration-200"
          >
            Entrar
          </router-link>
          <router-link to="/login" class="w-8 h-8 rounded-full bg-primary flex items-center justify-center shadow-inner hover:opacity-90">
            <span class="material-symbols-outlined text-on-primary text-[18px]">person</span>
          </router-link>
          <!-- Botón Menú Móvil -->
          <button @click="mobileMenuOpen = !mobileMenuOpen" class="xl:hidden text-surface p-1">
            <span class="material-symbols-outlined text-[28px]">{{ mobileMenuOpen ? 'close' : 'menu' }}</span>
          </button>
        </div>
      </div>

      <!-- Menú Desplegable Móvil -->
      <div v-if="mobileMenuOpen" class="xl:hidden bg-inverse-surface border-t border-surface-variant/10 px-gutter-mobile py-space-md flex flex-col space-y-space-xs">
        <router-link @click="mobileMenuOpen = false" to="/" class="py-space-xs font-label-pill text-label-pill uppercase text-surface-dim hover:text-primary-container">Inicio</router-link>
        <router-link @click="mobileMenuOpen = false" to="/equipos" class="py-space-xs font-label-pill text-label-pill uppercase text-surface-dim hover:text-primary-container">Equipos</router-link>
        <router-link @click="mobileMenuOpen = false" to="/jugadores" class="py-space-xs font-label-pill text-label-pill uppercase text-surface-dim hover:text-primary-container">Jugadores</router-link>
        <router-link @click="mobileMenuOpen = false" to="/partidos" class="py-space-xs font-label-pill text-label-pill uppercase text-surface-dim hover:text-primary-container">Partidos</router-link>
        <router-link @click="mobileMenuOpen = false" to="/estadisticas" class="py-space-xs font-label-pill text-label-pill uppercase text-surface-dim hover:text-primary-container">Estadísticas</router-link>
        <router-link @click="mobileMenuOpen = false" to="/novedades" class="py-space-xs font-label-pill text-label-pill uppercase text-surface-dim hover:text-primary-container">Novedades</router-link>
        <router-link @click="mobileMenuOpen = false" to="/login" class="mt-space-xs inline-flex justify-center items-center rounded-full bg-primary-container text-on-primary-fixed px-space-lg py-space-xs font-label-pill text-label-pill uppercase">Entrar</router-link>
      </div>
    </header>

    <!-- Contenido Principal -->
    <main class="flex-1 w-full pt-20">
      <router-view></router-view>
    </main>

    <!-- Footer General -->
    <footer class="bg-inverse-surface text-surface py-space-2xl px-gutter-desktop border-t border-surface-variant/10 mt-auto">
      <div class="max-w-[1280px] mx-auto flex flex-col md:flex-row items-center justify-between gap-space-md text-center md:text-left">
        <div>
          <div class="font-headline-md text-headline-md uppercase tracking-wider text-surface">Sanarateca</div>
          <p class="font-label-meta text-label-meta text-primary-container tracking-widest uppercase mt-space-2xs">Liga Oficial de Baloncesto • Sanarate, El Progreso</p>
        </div>
        <div class="text-surface-dim font-body-sm text-body-sm">
          &copy; {{ new Date().getFullYear() }} Liga de Baloncesto Sanarateca. Todos los derechos reservados.
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import logoUrl from '../../assets/images/logo.png';

const mobileMenuOpen = ref(false);
</script>
