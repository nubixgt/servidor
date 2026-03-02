<template>
  <!-- Mobile Header -->
  <div class="md:hidden fixed top-0 w-full h-16 bg-[#2E7D32] border-b border-green-600 flex items-center justify-between px-4 z-50 shadow-md">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-[#2E7D32] font-bold text-sm">EM</div>
      <span class="text-lg font-bold text-white">EMAGRO</span>
    </div>
    <button class="text-white" @click="isMobileOpen = !isMobileOpen">
      <!-- Menu Icon -->
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
      </svg>
    </button>
  </div>

  <!-- Sidebar -->
  <aside 
    :class="[
      'w-64 bg-white border-r border-gray-200 flex flex-col z-40 shadow-[4px_0_24px_rgba(0,0,0,0.02)] transition-transform duration-300 md:translate-x-0 md:flex md:relative md:pt-0',
      isMobileOpen ? 'translate-x-0 fixed inset-y-0 left-0 pt-16' : '-translate-x-full hidden'
    ]"
  >
    <div class="h-20 flex items-center px-6 border-b border-gray-100 hidden md:flex">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-full bg-[#2E7D32] flex items-center justify-center text-white font-bold text-sm shadow-sm">
          EM
        </div>
        <span class="text-lg font-bold text-gray-800 tracking-tight">EMAGRO</span>
      </div>
    </div>

    <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1">
      <router-link
        v-for="item in navItems"
        :key="item.name"
        :to="item.path"
        @click="isMobileOpen = false"
        custom
        v-slot="{ isActive, href, navigate }"
      >
        <a 
          :href="href" 
          @click="navigate"
          :class="[
            'flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-medium',
            isActive ? 'bg-green-50 text-[#2E7D32]' : 'text-gray-600 hover:bg-green-50 hover:text-[#2E7D32]'
          ]"
        >
          <span v-html="item.iconSVG" class="w-5 h-5 flex items-center justify-center"></span>
          {{ item.name }}
        </a>
      </router-link>
    </nav>

    <div class="p-4 border-t border-gray-100 flex flex-col gap-2">
      <div class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer group">
        <div class="w-10 h-10 rounded-full bg-gray-200 overflow-hidden ring-2 ring-white shadow-sm">
          <img
            alt="Profile"
            class="w-full h-full object-cover"
            src="https://picsum.photos/seed/user/100/100"
          />
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-gray-900 group-hover:text-[#2E7D32] transition-colors truncate">
            Admin Agro
          </p>
          <p class="text-xs text-gray-500 truncate">Administrador</p>
        </div>
        <!-- ChevronDown -->
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400">
          <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
      </div>
      
      <button 
        @click="handleLogout"
        class="flex items-center gap-3 w-full px-4 py-3 text-gray-600 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors mt-2"
      >
        <!-- LogOut -->
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
          <polyline points="16 17 21 12 16 7"></polyline>
          <line x1="21" y1="12" x2="9" y2="12"></line>
        </svg>
        <span class="text-sm font-medium">Cerrar Sesión</span>
      </button>
    </div>
  </aside>
  
  <!-- Overlay for mobile -->
  <div 
    v-if="isMobileOpen"
    class="fixed inset-0 bg-black/50 z-30 md:hidden" 
    @click="isMobileOpen = false"
  ></div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const isMobileOpen = ref(false);

const navItems = [
  { 
    name: 'Dashboard', 
    path: '/dashboard', 
    iconSVG: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>`
  },
  { 
    name: 'Usuarios', 
    path: '/usuarios', 
    iconSVG: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>`
  },
  { 
    name: 'Clientes', 
    path: '/clientes', 
    iconSVG: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"></path><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"></path><path d="M2 7h20"></path><path d="M22 7v3a2 2 0 0 1-2 2v0a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12v0a2 2 0 0 1-2-2V7"></path></svg>`
  },
  { 
    name: 'Ventas', 
    path: '/ventas', 
    iconSVG: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"></path><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"></path><path d="M12 17V7"></path></svg>`
  },
  { 
    name: 'Catálogo', 
    path: '/catalogo', 
    iconSVG: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>`
  },
  { 
    name: 'Pagos', 
    path: '/pagos', 
    iconSVG: `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>`
  },
];

const handleLogout = () => {
  router.push('/login');
};
</script>
