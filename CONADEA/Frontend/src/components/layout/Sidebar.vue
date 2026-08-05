<template>
  <aside class="sidebar" :class="{ abierto: menuAbierto }">
    <!-- Logo / Header -->
    <div class="logo-zona">
      <div class="logo-svg-container">
        <img :src="logoImg" alt="Logo MAGA AgroIA" class="logo-img" />
      </div>
      <div class="ministerio">MINISTERIO DE<br>AGRICULTURA, GANADERÍA<br>Y ALIMENTACIÓN</div>
      <div class="lema">Juntos para alimentar Guatemala</div>
    </div>

    <!-- Menú -->
    <ul class="menu" role="navigation">
      <li v-for="item in menuItems" :key="item.id">
        <button
          :id="`m-${item.id}`"
          :class="{ activo: rutaActiva === item.id }"
          @click="navegar(item.id)"
          :aria-label="item.label"
        >
          <span class="ic">
            <svg viewBox="0 0 24 24" v-html="item.icono"></svg>
          </span>
          {{ item.label }}
        </button>
      </li>

      <li class="sep" role="separator"></li>

      <li>
        <button id="m-perfil" :class="{ activo: rutaActiva === 'perfil' }" @click="navegar('perfil')">
          <span class="ic">
            <svg viewBox="0 0 24 24">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </span>
          Mi perfil
        </button>
      </li>
      <li>
        <button id="m-config" :class="{ activo: rutaActiva === 'config' }" @click="navegar('config')">
          <span class="ic">
            <svg viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="3"></circle>
              <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg>
          </span>
          Configuración
        </button>
      </li>

      <li class="sep" role="separator"></li>

      <li>
        <button class="salir" @click="handleCerrarSesion">
          <span class="ic">
            <svg viewBox="0 0 24 24">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
              <polyline points="16 17 21 12 16 7"></polyline>
              <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
          </span>
          Cerrar sesión
        </button>
      </li>
    </ul>
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/app.js';
import logoImg from '../../assets/logo_agroia.png';

const props = defineProps({
  menuAbierto: Boolean,
  rutaActiva: String
});

const emit = defineEmits(['navegacion', 'cerrarMenu']);
const router = useRouter();
const store = useAppStore();

const menuItems = [
  {
    id: 'inicio', label: 'Inicio',
    icono: '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline>'
  },
  {
    id: 'miscursos', label: 'Mis cursos',
    icono: '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path><path d="M6 6h10M6 10h10M6 14h10"></path>'
  },
  {
    id: 'catalogo', label: 'Catálogo de cursos',
    icono: '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path><path d="M6 8h2M16 8h2M6 12h2M16 12h2"></path>'
  },
  {
    id: 'rutas', label: 'Rutas de aprendizaje',
    icono: '<circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon>'
  },
  {
    id: 'calendario', label: 'Calendario',
    icono: '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>'
  },
  {
    id: 'novedades', label: 'Novedades',
    icono: '<polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>'
  },
  {
    id: 'insignias', label: 'Insignias',
    icono: '<circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>'
  },
  {
    id: 'certificados', label: 'Certificados',
    icono: '<path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"></path>'
  },
  {
    id: 'foros', label: 'Foros',
    icono: '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>'
  },
  {
    id: 'ayuda', label: 'Ayuda y soporte',
    icono: '<circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line>'
  }
];

function navegar(id) {
  emit('navegacion', id);
  emit('cerrarMenu');
}

function handleCerrarSesion() {
  if (confirm('¿Cerrar sesión? Tu avance quedará guardado en este dispositivo.')) {
    store.cerrarSesion();
    router.push('/login');
    emit('cerrarMenu');
  }
}
</script>

<style scoped>
.sidebar {
  background: rgba(13, 38, 48, 0.55);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  border: 1px solid var(--borde);
  border-radius: 24px;
  padding: 24px 16px;
  height: calc(100vh - 48px);
  position: sticky;
  top: 24px;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  scrollbar-width: thin;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35);
  gap: 8px;
}

@media (max-width: 980px) {
  .sidebar {
    position: fixed;
    left: 12px;
    top: 12px;
    bottom: 12px;
    height: auto;
    width: 258px;
    z-index: 90;
    transform: translateX(-120%);
    transition: transform 0.28s ease;
  }
  .sidebar.abierto {
    transform: translateX(0);
  }
}

.logo-zona {
  text-align: center;
  padding: 6px 4px 16px;
  border-bottom: 1px solid var(--borde);
  margin-bottom: 16px;
}

.logo-svg-container {
  width: 100px;
  height: 100px;
  margin: 0 auto 12px;
  filter: drop-shadow(0 6px 16px rgba(0, 0, 0, 0.25));
  transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.logo-svg-container:hover {
  transform: scale(1.08) rotate(-2deg);
}

.logo-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.ministerio {
  font-family: 'Outfit', sans-serif;
  font-size: 0.62rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  line-height: 1.4;
  color: #FFFFFF;
  margin-top: 10px;
  text-transform: uppercase;
}

.lema {
  font-family: 'Inter', sans-serif;
  font-size: 0.52rem;
  letter-spacing: 0.12em;
  color: var(--texto-suave);
  margin-top: 4px;
  text-transform: uppercase;
}

.menu {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
}

.menu button {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 14px;
  border-radius: 12px;
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--texto-suave);
  text-align: left;
  transition: all 0.2s ease;
}

.menu button:hover {
  background: var(--vidrio-2);
  color: #FFFFFF;
  transform: translateX(4px);
}

.menu button.activo {
  background: linear-gradient(135deg, rgba(74, 222, 128, 0.3) 0%, rgba(34, 197, 94, 0.15) 100%);
  border: 1px solid rgba(74, 222, 128, 0.4);
  color: #FFFFFF;
  box-shadow: 0 4px 16px rgba(34, 197, 94, 0.2);
}

.menu .ic {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--vidrio-2);
  border: 1px solid var(--borde);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: none;
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.menu .ic svg {
  width: 16px;
  height: 16px;
  stroke: var(--texto-suave);
  fill: none;
  stroke-width: 2.5;
  transition: all 0.3s ease;
}

.menu button:hover .ic {
  background: rgba(74, 222, 128, 0.25);
  border-color: rgba(74, 222, 128, 0.5);
  transform: scale(1.15) rotate(10deg);
  box-shadow: 0 0 10px rgba(74, 222, 128, 0.4);
}

.menu button:hover .ic svg {
  stroke: #FFFFFF;
  filter: drop-shadow(0 0 4px var(--verde));
}

.menu button.activo .ic {
  background: rgba(74, 222, 128, 0.4);
  border-color: var(--verde);
}

.menu button.activo .ic svg {
  stroke: #FFFFFF;
}

.menu .sep {
  height: 1px;
  background: var(--borde);
  margin: 10px 4px;
}

.menu .salir {
  color: var(--rojo);
}

.menu .salir:hover {
  background: rgba(248, 113, 113, 0.1);
  color: #FF8A8A;
}
</style>
