<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const role = ref(localStorage.getItem('userRole') || 'admin');

onMounted(() => {
  const currentRole = localStorage.getItem('userRole');
  if (!currentRole) {
    router.push('/login');
  } else {
    role.value = currentRole;
  }
});

const isAdmin = computed(() => role.value === 'admin');

const handleLogout = () => {
  localStorage.removeItem('userRole');
  router.push('/login');
};

const adminLinks = [
  { name: 'Dashboard', path: '/admin/dashboard', icon: 'dashboard' },
  { name: 'Denuncias', path: '/admin/denuncias', icon: 'gavel' },
  { name: 'Servicios', path: '/admin/servicios', icon: 'medical_services' },
  { name: 'Noticias', path: '/admin/noticias', icon: 'newspaper' },
  { name: 'Reportes', path: '/admin/reportes', icon: 'analytics' },
];

const techLinks = [
  { name: 'Dashboard', path: '/tech/dashboard', icon: 'dashboard' },
  { name: 'Bandeja de Denuncias', path: '/tech/bandeja', icon: 'inbox', badge: 12 },
  { name: 'Reportes', path: '/tech/reportes', icon: 'analytics' },
];

const getRoleDisplayName = computed(() => {
  if (isAdmin.value) return 'Administrador Global';
  switch (role.value) {
    case 'tecnico_1': return 'Técnico Área Legal';
    case 'tecnico_2': return 'Técnico Área Técnica';
    case 'tecnico_3': return 'Técnico Emitir Dictamen';
    case 'tecnico_4': return 'Técnico Opinión Legal';
    case 'tecnico_5': return 'Técnico Resolución Final';
    default: return 'Técnico';
  }
});

const isActiveRoute = (path, exact = false) => {
  if (exact) return route.path === path;
  return route.path.startsWith(path);
};
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-surface font-body antialiased relative">
    <!-- Background Decorative Elements -->
    <div class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-primary-fixed/20 to-transparent -z-10 pointer-events-none"></div>
    <div class="absolute top-[-10%] right-[-5%] w-[40%] h-[40%] rounded-full bg-secondary-fixed/20 blur-[100px] -z-10 pointer-events-none"></div>

    <!-- Sidebar -->
    <aside class="w-64 h-full glass-panel shadow-sm flex flex-col z-20 relative">
      <div class="p-6 mb-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl primary-gradient flex items-center justify-center shadow-md">
            <span class="material-symbols-outlined text-white filled">pets</span>
          </div>
          <div>
            <h1 class="font-headline font-extrabold text-xl text-primary tracking-tight leading-none">AppUBA</h1>
            <p class="text-[10px] font-bold text-outline uppercase tracking-widest mt-1">MAGA</p>
          </div>
        </div>
      </div>

      <nav class="flex-1 px-4 space-y-6 overflow-y-auto custom-scrollbar">
        <div v-if="isAdmin">
          <p class="px-4 text-xs font-bold text-outline uppercase tracking-widest mb-2">Administración</p>
          <div class="space-y-1">
            <RouterLink
              v-for="link in adminLinks"
              :key="link.path"
              :to="link.path"
              :class="[
                'flex items-center gap-3 px-4 py-3 rounded-lg transition-colors relative group',
                isActiveRoute(link.path)
                  ? 'bg-primary-fixed/40 text-primary font-bold'
                  : 'text-on-surface-variant hover:bg-surface-container-low'
              ]"
            >
              <div v-if="isActiveRoute(link.path)" class="absolute left-0 top-0 bottom-0 w-1 bg-primary rounded-r-full"></div>
              <span :class="['material-symbols-outlined', isActiveRoute(link.path) ? 'filled' : '']">{{ link.icon }}</span>
              <span class="text-sm">{{ link.name }}</span>
            </RouterLink>
          </div>
        </div>
        <div v-else>
          <p class="px-4 text-xs font-bold text-outline uppercase tracking-widest mb-2">Mi Espacio</p>
          <div class="space-y-1">
            <RouterLink
              v-for="link in techLinks"
              :key="link.path"
              :to="link.path"
              :class="[
                'flex items-center gap-3 px-4 py-3 rounded-lg transition-colors relative group',
                isActiveRoute(link.path, true)
                  ? 'bg-primary-fixed/40 text-primary font-bold'
                  : 'text-on-surface-variant hover:bg-surface-container-low'
              ]"
            >
              <div v-if="isActiveRoute(link.path, true)" class="absolute left-0 top-0 bottom-0 w-1 bg-primary rounded-r-full"></div>
              <span :class="['material-symbols-outlined', isActiveRoute(link.path, true) ? 'filled' : '']">{{ link.icon }}</span>
              <span class="text-sm">{{ link.name }}</span>
              <span v-if="link.badge" class="ml-auto bg-primary text-white text-[10px] px-2 py-0.5 rounded-full">
                {{ link.badge }}
              </span>
            </RouterLink>
          </div>
        </div>
      </nav>

      <div class="p-4 mt-auto border-t border-outline-variant/20">
        <button @click="handleLogout" class="w-full flex text-center items-center gap-3 p-2 rounded-xl hover:bg-surface-container-low transition-colors cursor-pointer text-left">
          <img :src="isAdmin ? 'https://lh3.googleusercontent.com/aida-public/AB6AXuBAXFgafQrkn4Dc0NHrjtO9W9BnK14p5v9sFzxPPQQpmdjvNKxEtJxrFAeiTXmnUepAIg0ZNXJf9oD0UBoBG1RKjt9zzDfAjwLZ_8UsuRWL4EhzyyhLNKOvDn7Wiz7I7XdLMffgM-FAnxDDJZJl0U-3-DLU3IG11IXRTzOMbim2W3zhbVMVatdlPiZQ-n8su_sVmyecD-VF_qs6G096VRai9oKRT2FkcPrzyOaW6TSqT7wBpNKSaEavjoxHKtqSWFB0BBneDO8_IKkr' : 'https://i.pravatar.cc/150?img=11'" alt="Profile" class="w-10 h-10 rounded-full object-cover border-2 border-surface-container-lowest shadow-sm" />
          <div class="flex-1 min-w-0 text-left">
            <p class="text-sm font-bold text-on-surface truncate">{{ isAdmin ? 'Admin' : 'Usuario' }}</p>
            <p class="text-xs text-on-surface-variant truncate">Cerrar Sesión</p>
          </div>
          <span class="material-symbols-outlined text-outline text-sm">logout</span>
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-full relative overflow-hidden">
      <header class="h-20 px-8 flex items-center justify-between z-10">
        <div class="flex-1 max-w-xl">
          <div class="relative group">
            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors">search</span>
            <input type="text" placeholder="Buscar expedientes, servicios, noticias..." 
                   class="w-full bg-surface-container-lowest/80 backdrop-blur-md border border-outline-variant/30 rounded-full py-2.5 pl-12 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary/30 transition-all shadow-sm" />
          </div>
        </div>
        <div class="flex items-center gap-6 ml-4">
          <div class="flex items-center gap-4">
            <button class="relative p-2 text-outline hover:text-primary transition-colors">
              <span class="material-symbols-outlined">notifications</span>
              <span class="absolute top-2 right-2 w-2 h-2 bg-error rounded-full"></span>
            </button>
            <button class="p-2 text-outline hover:text-primary transition-colors">
              <span class="material-symbols-outlined">help_outline</span>
            </button>
          </div>
          <div class="h-8 w-[1px] bg-outline-variant/30"></div>
          <div class="flex items-center gap-3">
            <div class="text-right hidden sm:block">
              <p class="text-sm font-bold text-primary">{{ isAdmin ? 'Administrator' : 'Técnico' }}</p>
              <p class="text-[10px] text-outline uppercase font-bold tracking-tighter">{{ getRoleDisplayName }}</p>
            </div>
          </div>
        </div>
      </header>

      <main class="flex-1 overflow-y-auto px-8 pb-8 custom-scrollbar">
        <RouterView />
      </main>
    </div>
  </div>
</template>
