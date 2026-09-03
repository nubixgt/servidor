<template>
  <div class="min-h-screen bg-surface flex flex-col md:flex-row">
    <!-- Sidebar de Administración -->
    <aside class="w-full md:w-64 bg-inverse-surface text-surface flex flex-col shrink-0">
      <!-- Header de la marca Admin -->
      <div class="p-space-lg flex items-center gap-space-md border-b border-surface-variant/10">
        <img :src="logoUrl" alt="Logo Liga Sanarateca" class="w-10 h-10 object-contain" />
        <div class="flex flex-col">
          <span class="font-headline-md text-headline-md uppercase text-surface leading-none">SANARATECA</span>
          <span class="font-label-meta text-label-meta text-primary-container uppercase">Panel Admin</span>
        </div>
      </div>

      <!-- Navegación Admin -->
      <nav class="flex-1 p-space-md space-y-space-xs">
        <router-link 
          to="/dashboard" 
          class="flex items-center gap-space-sm px-space-md py-space-sm rounded-lg text-surface-dim hover:bg-surface-variant/10 hover:text-surface transition-colors font-label-pill text-label-pill uppercase"
          active-class="!bg-primary-container !text-on-primary-fixed font-semibold"
        >
          <span class="material-symbols-outlined text-[20px]">dashboard</span>
          <span>Dashboard</span>
        </router-link>

        <router-link 
          to="/partidos" 
          class="flex items-center gap-space-sm px-space-md py-space-sm rounded-lg text-surface-dim hover:bg-surface-variant/10 hover:text-surface transition-colors font-label-pill text-label-pill uppercase"
          active-class="!bg-primary-container !text-on-primary-fixed font-semibold"
        >
          <span class="material-symbols-outlined text-[20px]">sports_score</span>
          <span>Partidos</span>
        </router-link>

        <router-link 
          to="/equipos" 
          class="flex items-center gap-space-sm px-space-md py-space-sm rounded-lg text-surface-dim hover:bg-surface-variant/10 hover:text-surface transition-colors font-label-pill text-label-pill uppercase"
          active-class="!bg-primary-container !text-on-primary-fixed font-semibold"
        >
          <span class="material-symbols-outlined text-[20px]">groups</span>
          <span>Equipos</span>
        </router-link>

        <router-link 
          to="/jugadores" 
          class="flex items-center gap-space-sm px-space-md py-space-sm rounded-lg text-surface-dim hover:bg-surface-variant/10 hover:text-surface transition-colors font-label-pill text-label-pill uppercase"
          active-class="!bg-primary-container !text-on-primary-fixed font-semibold"
        >
          <span class="material-symbols-outlined text-[20px]">badge</span>
          <span>Jugadores</span>
        </router-link>

        <router-link 
          to="/estadisticas" 
          class="flex items-center gap-space-sm px-space-md py-space-sm rounded-lg text-surface-dim hover:bg-surface-variant/10 hover:text-surface transition-colors font-label-pill text-label-pill uppercase"
          active-class="!bg-primary-container !text-on-primary-fixed font-semibold"
        >
          <span class="material-symbols-outlined text-[20px]">query_stats</span>
          <span>Estadísticas</span>
        </router-link>
      </nav>

      <!-- Footer de Sidebar (Cerrar Sesión / Ir al sitio público) -->
      <div class="p-space-md border-t border-surface-variant/10 flex flex-col space-y-space-xs">
        <router-link 
          to="/" 
          class="flex items-center gap-space-sm px-space-md py-space-xs text-surface-dim hover:text-surface font-label-pill text-label-pill uppercase"
        >
          <span class="material-symbols-outlined text-[18px]">public</span>
          <span>Ver Sitio Público</span>
        </router-link>

        <router-link 
          to="/login" 
          class="flex items-center gap-space-sm px-space-md py-space-xs text-error hover:bg-error/10 rounded-lg font-label-pill text-label-pill uppercase"
        >
          <span class="material-symbols-outlined text-[18px]">logout</span>
          <span>Cerrar Sesión</span>
        </router-link>
      </div>
    </aside>

    <!-- Contenido Admin -->
    <main class="flex-1 flex flex-col min-w-0">
      <!-- Topbar Admin -->
      <header class="bg-surface-container-lowest border-b border-surface-container-high px-space-lg py-space-md flex items-center justify-between">
        <div class="flex items-center gap-space-sm">
          <span class="font-headline-md text-headline-md text-on-surface uppercase">Gestión de Liga</span>
        </div>
        <div class="flex items-center gap-space-md">
          <div class="flex items-center gap-space-xs">
            <div class="w-8 h-8 rounded-full bg-primary-container text-on-primary-fixed flex items-center justify-center font-bold text-xs">
              AD
            </div>
            <span class="font-label-pill text-label-pill text-on-surface">Administrador</span>
          </div>
        </div>
      </header>

      <!-- Vista de Admin -->
      <div class="flex-1 p-space-lg overflow-y-auto">
        <router-view></router-view>
      </div>
    </main>
  </div>
</template>

<script setup>
import logoUrl from '../../assets/images/logo.png';
</script>
