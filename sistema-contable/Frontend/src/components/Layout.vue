<template>
  <div class="flex h-screen bg-surface overflow-hidden">
    <!-- Sidebar -->
    <aside :class="[
      'fixed inset-y-0 left-0 z-50 w-64 bg-white/80 backdrop-blur-xl border-r border-outline-variant/30 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:flex lg:flex-col',
      isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
    ]">
      <div class="flex items-center justify-center h-20 border-b border-outline-variant/20">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-[var(--color-primary)] rounded-xl flex items-center justify-center shadow-sm">
            <span class="text-white font-bold text-xl">AL</span>
          </div>
          <span class="font-sans font-bold text-xl text-[var(--color-primary)] tracking-tight">Ledger</span>
        </div>
      </div>

      <div class="flex-1 overflow-y-auto py-6 px-4 space-y-2">
        <div class="text-xs font-semibold text-outline uppercase tracking-wider mb-4 px-3">
          Menú Principal
        </div>
        
        <router-link
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          :exact="link.to === '/admin' || link.to === '/tech'"
          @click="isMobileMenuOpen = false"
          v-slot="{ isActive, href, navigate }"
        >
          <a
            :href="href"
            @click="navigate"
            :class="[
              'flex items-center gap-3 px-3 py-3 rounded-xl transition-all duration-200 group',
              isActive || (link.to !== '/admin' && link.to !== '/tech' && $route.path.startsWith(link.to))
                ? 'bg-[var(--color-primary-fixed)] text-[var(--color-on-primary-fixed)] font-medium shadow-sm surface-shift-active'
                : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
            ]"
          >
            <component 
              :is="link.icon" 
              :class="[
                'w-5 h-5 transition-colors',
                isActive || (link.to !== '/admin' && link.to !== '/tech' && $route.path.startsWith(link.to)) 
                  ? 'text-[var(--color-primary)]' 
                  : 'text-outline group-hover:text-on-surface'
              ]" 
            />
            <span>{{ link.label }}</span>
          </a>
        </router-link>
      </div>

      <div class="p-4 border-t border-outline-variant/20">
        <button
          @click="handleLogout"
          class="flex items-center gap-3 px-3 py-3 w-full rounded-xl text-[var(--color-error)] hover:bg-[var(--color-error-container)]/50 transition-colors duration-200"
        >
          <ArrowRightOnRectangleIcon class="w-5 h-5" />
          <span class="font-medium">Cerrar Sesión</span>
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Topbar -->
      <header class="h-20 bg-white/60 backdrop-blur-md border-b border-outline-variant/20 flex items-center justify-between px-6 z-40 sticky top-0">
        <div class="flex items-center gap-4">
          <button 
            class="lg:hidden p-2 text-on-surface-variant hover:bg-surface-container rounded-lg transition-colors"
            @click="isMobileMenuOpen = !isMobileMenuOpen"
          >
            <Bars3Icon class="w-6 h-6" />
          </button>
          <div class="hidden md:flex items-center bg-surface-container-low rounded-full px-4 py-2 border border-outline-variant/30 focus-within:border-[var(--color-primary)]/50 focus-within:bg-white transition-all w-64 lg:w-96">
            <MagnifyingGlassIcon class="w-4 h-4 text-outline mr-2" />
            <input 
              type="text" 
              placeholder="Buscar..." 
              class="bg-transparent border-none outline-none text-sm w-full text-on-surface placeholder:text-outline focus:ring-0"
            />
          </div>
        </div>

        <div class="flex items-center gap-4">
          <button class="p-2 text-on-surface-variant hover:bg-surface-container rounded-full transition-colors relative">
            <BellIcon class="w-5 h-5" />
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-[var(--color-error)] rounded-full border-2 border-white"></span>
          </button>
          <div class="flex items-center gap-3 pl-4 border-l border-outline-variant/30">
            <div class="text-right hidden sm:block">
              <p class="text-sm font-semibold text-on-surface">{{ user.name }}</p>
              <p class="text-xs text-outline capitalize">{{ user.role }}</p>
            </div>
            <div class="w-10 h-10 rounded-full bg-[var(--color-primary-container)] text-[var(--color-on-primary-container)] flex items-center justify-center font-bold shadow-sm border border-[var(--color-primary-fixed)]">
              {{ user.name.charAt(0) }}
            </div>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
          <router-view />
        </div>
      </main>
    </div>
    
    <!-- Mobile Overlay -->
    <div 
      v-if="isMobileMenuOpen"
      class="fixed inset-0 bg-on-surface/20 backdrop-blur-sm z-40 lg:hidden"
      @click="isMobileMenuOpen = false"
    ></div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { 
  Squares2X2Icon, 
  MapPinIcon, 
  PlusCircleIcon, 
  ChartBarIcon, 
  UserGroupIcon, 
  ArrowRightOnRectangleIcon, 
  ClockIcon, 
  BellIcon, 
  MagnifyingGlassIcon,
  Bars3Icon
} from '@heroicons/vue/24/outline';

const router = useRouter();
const route = useRoute();

const isMobileMenuOpen = ref(false);

const user = ref(JSON.parse(localStorage.getItem('user')) || {
  name: "Guest User",
  role: "guest"
});

const handleLogout = () => {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  router.push('/login');
};

const adminLinks = [
  { to: '/admin', icon: Squares2X2Icon, label: 'Dashboard' },
  { to: '/admin/locations', icon: MapPinIcon, label: 'Locaciones' },
  { to: '/admin/new', icon: PlusCircleIcon, label: 'Nueva Transacción' },
  { to: '/admin/reports', icon: ChartBarIcon, label: 'Reportes' },
  { to: '/admin/users', icon: UserGroupIcon, label: 'Usuarios' },
];

const techLinks = [
  { to: '/tech', icon: Squares2X2Icon, label: 'Dashboard' },
  { to: '/tech/history', icon: ClockIcon, label: 'Historial' },
];

const links = computed(() => user.value.role === 'admin' ? adminLinks : techLinks);
</script>
