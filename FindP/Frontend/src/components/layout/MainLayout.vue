<template>
  <div>
    <div class="background-decor">
      <div class="glow-sphere sphere-1"></div>
      <div class="glow-sphere sphere-2"></div>
      <div class="glow-sphere sphere-3"></div>
    </div>

    <div class="app-container">
      <!-- Encabezado -->
      <header class="app-header">
        <div class="logo-area">
          <div class="logo-icon">
            <svg viewBox="0 0 24 24" width="32" height="32" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
              <path d="M9 11l2 2 4-4"/>
            </svg>
          </div>
          <div class="logo-text">
            <h1>FindP</h1>
            <span>Personal</span>
          </div>
        </div>
        <div class="header-titles">
          <h2>Sistema de Búsqueda Personal</h2>
          <p>Búsqueda inteligente y ultra-rápida de registros nacionales.</p>
        </div>
      </header>

      <router-view></router-view>
    </div>
  </div>
</template>

<script setup>
</script>
