<script setup>
import { onMounted, onBeforeUnmount } from 'vue'
import PublicNav from '../components/public/PublicNav.vue'
import { usePulse } from '../composables/usePulse'

const { state, load } = usePulse()
let timer

const enfocarContenido = () => document.getElementById('main')?.focus()

onMounted(() => {
  load()
  // Refresco periódico (útil cuando los datos vengan del backend).
  timer = setInterval(() => {
    if (document.visibilityState === 'visible' && !state.loading) load()
  }, 30000)
})
onBeforeUnmount(() => clearInterval(timer))
</script>

<template>
  <a href="#main" class="skip-link" @click.prevent="enfocarContenido">Saltar al contenido</a>
  <PublicNav />

  <main id="main" class="container" tabindex="-1">
    <div v-if="state.error" class="error-banner" role="alert">
      <b>No pudimos cargar la liga.</b>
      <p>{{ state.error }}</p>
      <button class="btn btn-secondary" @click="load">Reintentar</button>
    </div>
    <div v-else-if="state.loading && !state.loaded" class="loading-state">
      <div class="loader"></div>
      Cargando la liga…
    </div>
    <RouterView v-else />
  </main>

  <footer class="site-footer container">
    <span class="footer-logo">LIGA PULSE<span>Fútbol local. Pasión sin límites.</span></span>
    <span>{{ state.league.season }} · Horarios de Guatemala</span>
    <RouterLink to="/login">Administrar liga ↗</RouterLink>
  </footer>
</template>
