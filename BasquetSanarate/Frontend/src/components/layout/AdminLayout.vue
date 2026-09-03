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
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-space-sm px-space-md py-space-sm rounded-lg text-surface-dim hover:bg-surface-variant/10 hover:text-surface transition-colors font-label-pill text-label-pill uppercase"
          :class="isActive(item) ? '!bg-primary-container !text-on-primary-fixed font-semibold' : ''"
        >
          <span class="material-symbols-outlined text-[20px]">{{ item.icon }}</span>
          <span>{{ item.label }}</span>
        </router-link>
      </nav>

      <!-- Footer de Sidebar -->
      <div class="p-space-md border-t border-surface-variant/10 flex flex-col space-y-space-xs">
        <router-link
          to="/"
          class="flex items-center gap-space-sm px-space-md py-space-xs text-surface-dim hover:text-surface font-label-pill text-label-pill uppercase"
        >
          <span class="material-symbols-outlined text-[18px]">public</span>
          <span>Ver Sitio Público</span>
        </router-link>

        <button
          type="button"
          @click="onLogout"
          class="w-full flex items-center gap-space-sm px-space-md py-space-xs text-error hover:bg-error/10 rounded-lg font-label-pill text-label-pill uppercase"
        >
          <span class="material-symbols-outlined text-[18px]">logout</span>
          <span>Cerrar Sesión</span>
        </button>
      </div>
    </aside>

    <!-- Contenido Admin -->
    <main class="flex-1 flex flex-col min-w-0">
      <!-- Topbar Admin -->
      <header class="bg-surface-container-lowest border-b border-surface-container-high px-space-lg py-space-md flex items-center justify-between gap-space-md">
        <div class="flex items-center gap-space-xs font-label-meta text-label-meta uppercase tracking-wider text-on-surface-variant">
          <span class="w-2 h-2 rounded-full bg-tertiary-fixed-dim animate-pulse"></span>
          <span class="text-on-surface font-semibold">Consola Administrativa</span>
        </div>
        <div class="flex items-center gap-space-xs">
          <div class="hidden sm:flex flex-col text-right leading-tight">
            <span class="font-label-pill text-label-pill text-on-surface">{{ auth.user?.nombre || 'Administrador' }}</span>
            <span class="font-label-meta text-label-meta text-secondary uppercase">Admin</span>
          </div>
          <div class="w-8 h-8 rounded-full bg-primary-container text-on-primary-fixed flex items-center justify-center font-bold text-xs">
            {{ initials }}
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
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useConfirm } from '../../composables/useConfirm';
import logoUrl from '../../assets/images/logo.png';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const { confirm } = useConfirm();

const navItems = [
  { to: '/admin', label: 'Resumen', icon: 'dashboard', exact: true },
  { to: '/admin/equipos', label: 'Gestión Equipos', icon: 'groups' },
  { to: '/admin/jugadores', label: 'Gestión Jugadores', icon: 'badge' },
  { to: '/admin/partidos', label: 'Gestión Partidos', icon: 'sports_score' },
  { to: '/admin/estadisticas', label: 'Gestión Estadísticas', icon: 'query_stats' },
  { to: '/admin/novedades', label: 'Gestión Novedades', icon: 'campaign' }
];

function isActive(item) {
  return item.exact ? route.path === item.to : route.path.startsWith(item.to);
}

const initials = computed(() => {
  const name = auth.user?.nombre || 'Admin';
  return name
    .split(/\s+/)
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() || '')
    .join('');
});

async function onLogout() {
  const ok = await confirm({
    title: '¿Cerrar sesión?',
    text: 'Se cerrará tu sesión de administrador.',
    confirmText: 'Sí, cerrar sesión',
    cancelText: 'Cancelar',
    icon: 'question'
  });
  if (!ok) return;
  auth.logout();
  router.push({ path: '/login', query: { logout: '1' } });
}
</script>
