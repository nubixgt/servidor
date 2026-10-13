<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ZapIcon, ArrowUpRightIcon, MenuIcon, XIcon } from 'lucide-vue-next'

const route = useRoute()
const abierto = ref(false)

const enlaces = [
  ['/', 'Inicio'],
  ['/equipos', 'Equipos'],
  ['/jugadores', 'Jugadores'],
  ['/estadisticas', 'Estadísticas'],
  ['/partidos', 'Partidos'],
]

const activo = (ruta) => (ruta === '/' ? route.path === '/' : route.path.startsWith(ruta))

watch(() => route.path, () => (abierto.value = false))
</script>

<template>
  <header class="site-header">
    <div class="header-inner">
      <RouterLink to="/" class="brand" aria-label="Liga Pulse, inicio">
        <span class="brand-icon"><ZapIcon :size="22" fill="currentColor" /></span>
        <span>LIGA<span class="brand-light">PULSE</span><small>EL JUEGO NOS UNE.</small></span>
      </RouterLink>

      <nav class="desktop-nav" aria-label="Navegación principal">
        <RouterLink v-for="[ruta, nombre] in enlaces" :key="ruta" :to="ruta" :class="{ 'nav-active': activo(ruta) }">{{ nombre }}</RouterLink>
      </nav>

      <RouterLink to="/login" class="access-link">Acceso liga <ArrowUpRightIcon :size="16" /></RouterLink>

      <button class="mobile-toggle icon-btn" :aria-expanded="abierto" aria-label="Abrir menú" @click="abierto = !abierto">
        <XIcon v-if="abierto" />
        <MenuIcon v-else />
      </button>
    </div>

    <nav v-if="abierto" class="mobile-nav" aria-label="Navegación móvil">
      <RouterLink v-for="[ruta, nombre] in enlaces" :key="ruta" :to="ruta">{{ nombre }}</RouterLink>
      <RouterLink to="/login">Administración</RouterLink>
    </nav>
  </header>
</template>
