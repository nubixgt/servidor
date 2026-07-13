<template>
  <div class="layout-wrapper">
    <!-- Fondos decorativos estilo Glassmorphism -->
    <div class="mesh-background">
      <div class="blob blob-1"></div>
      <div class="blob blob-2"></div>
      <div class="blob blob-3"></div>
      <div class="blob blob-4"></div>
    </div>

    <div class="main-content">
      <!-- Encabezado superior -->
      <header class="top-header">
        <div class="brand-logo">
          <div class="logo-shield">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M9 11l2 2 4-4"></path></svg>
          </div>
          <div class="logo-text">
            <h2>FindP</h2>
            <span>PERSONAL</span>
          </div>
        </div>
        
        <div class="header-badges">
          <div class="security-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" width="20" height="20"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M9 11l2 2 4-4"></path></svg>
            <div class="badge-text">
              <strong>Seguridad Activa</strong>
              <span>Cifrado de extremo a extremo</span>
            </div>
            <span class="status-dot"></span>
          </div>
        </div>
      </header>

      <!-- Aquí se inyecta el Dashboard -->
      <router-view></router-view>
      
      <!-- Footer -->
      <footer class="app-footer">
        <span>© 2026 FindP Personal. Todos los derechos reservados.</span>
        <div class="footer-right">
          Motor de búsqueda inteligente con IA
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
        </div>
      </footer>
    </div>
  </div>
</template>

<script setup>
</script>
