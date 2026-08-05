<template>
  <div class="app-wrapper">
    <!-- Fondo paisaje fijo -->
    <div class="fondo-app" aria-hidden="true"></div>

    <!-- Velo para móvil -->
    <div v-if="menuAbierto" class="velo" @click="cerrarMenu"></div>

    <!-- Grid principal -->
    <div class="app-grid">
      <!-- Sidebar -->
      <Sidebar
        :menu-abierto="menuAbierto"
        :ruta-activa="rutaActiva"
        @navegacion="navegar"
        @cerrar-menu="cerrarMenu"
      />

      <!-- Contenido principal -->
      <main class="principal">
        <Topbar
          @abrir-menu="abrirMenu"
          @ir-a="navegar"
        />

        <!-- Buscador (solo en catalogo) -->
        <div v-if="rutaActiva === 'catalogo'" class="buscador">
          <span>🔍</span>
          <input
            v-model="terminoBusqueda"
            type="text"
            placeholder="Buscar cursos, rutas, temas..."
            id="input-busqueda"
          />
        </div>

        <!-- Router view para las vistas admin -->
        <router-view :termino-busqueda="terminoBusqueda" />

        <p class="nota-pie">
          Programa MAGA · CONADEA AgroIA · Los datos se guardan en este dispositivo.
        </p>
      </main>
    </div>

    <!-- Toast global -->
    <div class="toast-container" :class="{ visible: store.toastData.visible }">
      <div v-if="store.toastData.hex" class="hexagono toast-hex">
        {{ store.toastData.ic }}
      </div>
      <div v-else style="font-size:1.5rem">{{ store.toastData.ic }}</div>
      <div>
        <b>{{ store.toastData.titulo }}</b>
        <small style="display:block; margin-top:2px; font-size:0.78rem; color: var(--texto-suave);">
          {{ store.toastData.texto }}
        </small>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAppStore } from '../../stores/app.js';
import Sidebar from './Sidebar.vue';
import Topbar from './Topbar.vue';

const store = useAppStore();
const route = useRoute();
const router = useRouter();

const menuAbierto = ref(false);
const terminoBusqueda = ref('');

// La ruta activa viene del path de vue-router
const rutaActiva = ref('inicio');

watch(
  () => route.name,
  (nombre) => {
    // Mapear nombre de ruta a ID de menú
    const mapa = {
      'AdminDashboard':   'inicio',
      'AdminMisCursos':   'miscursos',
      'AdminCatalogo':    'catalogo',
      'AdminDetalleCurso':'catalogo',
      'AdminRutas':       'rutas',
      'AdminCalendario':  'calendario',
      'AdminNovedades':   'novedades',
      'AdminInsignias':   'insignias',
      'AdminCertificados':'certificados',
      'AdminForos':       'foros',
      'AdminAyuda':       'ayuda',
      'AdminPerfil':      'perfil',
      'AdminConfiguracion':'config'
    };
    rutaActiva.value = mapa[nombre] || 'inicio';
  },
  { immediate: true }
);

function navegar(id) {
  // Limpiar búsqueda al salir del catálogo
  if (id !== 'catalogo') terminoBusqueda.value = '';

  const rutas = {
    'inicio':       '/dashboard',
    'miscursos':    '/miscursos',
    'catalogo':     '/catalogo',
    'rutas':        '/rutas',
    'calendario':   '/calendario',
    'novedades':    '/novedades',
    'insignias':    '/insignias',
    'certificados': '/certificados',
    'foros':        '/foros',
    'ayuda':        '/ayuda',
    'perfil':       '/perfil',
    'config':       '/configuracion'
  };
  if (rutas[id]) router.push(rutas[id]);
}

function abrirMenu() { menuAbierto.value = true; }
function cerrarMenu() { menuAbierto.value = false; }
</script>

<style scoped>
.app-wrapper {
  min-height: 100vh;
}

.app-grid {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 24px;
  max-width: 1560px;
  margin: 0 auto;
  padding: 24px;
}

@media (max-width: 980px) {
  .app-grid {
    grid-template-columns: 1fr;
    padding: 12px;
  }
}

.principal {
  min-width: 0;
  padding-bottom: 30px;
}

.velo {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  z-index: 80;
  backdrop-filter: blur(4px);
}

.buscador {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  max-width: 560px;
  background: rgba(13, 38, 48, 0.35);
  border: 1px solid var(--borde);
  border-radius: 16px;
  padding: 12px 18px;
  margin-bottom: 24px;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  transition: all 0.3s ease;
}

.buscador input {
  flex: 1;
  background: none;
  border: none;
  outline: none;
  color: var(--texto);
  font-size: 0.95rem;
}

.buscador input::placeholder {
  color: rgba(255, 255, 255, 0.5);
}

.buscador:focus-within {
  border-color: var(--verde);
  box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.2);
  background: rgba(13, 38, 48, 0.5);
}

.nota-pie {
  font-size: 0.7rem;
  color: var(--texto-suave);
  text-align: center;
  margin-top: 24px;
  line-height: 1.6;
}

/* Toast Hex */
.toast-hex {
  width: 42px;
  height: 46px;
  font-size: 1.15rem;
  margin: 0;
}

.hexagono {
  clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
  background: linear-gradient(160deg, #FDE68A, #D97706);
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
