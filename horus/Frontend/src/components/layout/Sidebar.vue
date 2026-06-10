<template>
  <aside class="hidden md:flex flex-col bg-surface-dim dark:bg-surface-dim h-screen w-64 fixed left-0 top-0 border-r border-outline-variant z-50">
    <!-- Header -->
    <div class="p-6 flex flex-col items-center justify-center gap-4 mt-2">
      <!-- Logo as image with neon glow -->
      <div class="text-center flex flex-col items-center w-full px-2">
        <img src="../../assets/images/LogoHorus-2.png" alt="Horus Empresarial" class="w-full max-w-[160px] object-contain" style="filter: drop-shadow(0 0 10px rgba(233,193,118,0.8)) drop-shadow(0 0 20px rgba(233,193,118,0.4));" />
      </div>
      <button class="mt-6 w-full bg-transparent border border-primary text-primary hover:bg-primary/10 font-title-md text-sm py-2.5 px-4 rounded-xl transition-colors flex items-center justify-center gap-2 shadow-[0_0_10px_rgba(233,193,118,0.1)] backdrop-blur-sm">
        <span class="material-symbols-outlined text-lg">add</span>
        Nuevo Crédito
      </button>
    </div>

    <!-- Navigation Tabs -->
    <nav class="flex-1 overflow-y-auto custom-scrollbar py-4 px-4 space-y-2 mt-4">
      <router-link to="/dashboard" active-class="bg-surface-container-high text-primary border border-primary shadow-[0_0_10px_rgba(233,193,118,0.1)]" class="text-on-surface-variant hover:text-on-surface px-4 py-3 flex items-center gap-3 rounded-xl hover:bg-surface-container-high transition-all duration-200">
        <span class="material-symbols-outlined" data-icon="dashboard">grid_view</span>
        <span class="font-body-sm text-body-sm font-medium">Dashboard</span>
      </router-link>
      
      <router-link to="/loans" active-class="bg-surface-container-high text-primary border border-primary shadow-[0_0_10px_rgba(233,193,118,0.1)]" class="text-on-surface-variant hover:text-on-surface px-4 py-3 flex items-center gap-3 rounded-xl hover:bg-surface-container-high transition-all duration-200">
        <span class="material-symbols-outlined" data-icon="payments">payments</span>
        <span class="font-body-sm text-body-sm font-medium">Préstamos</span>
      </router-link>

      <router-link to="/clients" active-class="bg-surface-container-high text-primary border border-primary shadow-[0_0_10px_rgba(233,193,118,0.1)]" class="text-on-surface-variant hover:text-on-surface px-4 py-3 flex items-center gap-3 rounded-xl hover:bg-surface-container-high transition-all duration-200">
        <span class="material-symbols-outlined" data-icon="group">group</span>
        <span class="font-body-sm text-body-sm font-medium">Clientes</span>
      </router-link>

      <router-link to="/investors" active-class="bg-surface-container-high text-primary border border-primary shadow-[0_0_10px_rgba(233,193,118,0.1)]" class="text-on-surface-variant hover:text-on-surface px-4 py-3 flex items-center gap-3 rounded-xl hover:bg-surface-container-high transition-all duration-200">
        <span class="material-symbols-outlined" data-icon="account_balance">account_balance</span>
        <span class="font-body-sm text-body-sm font-medium">Inversionistas</span>
      </router-link>
      
      <router-link to="/payments" active-class="bg-surface-container-high text-primary border border-primary shadow-[0_0_10px_rgba(233,193,118,0.1)]" class="text-on-surface-variant hover:text-on-surface px-4 py-3 flex items-center gap-3 rounded-xl hover:bg-surface-container-high transition-all duration-200">
        <span class="material-symbols-outlined" data-icon="receipt_long">receipt_long</span>
        <span class="font-body-sm text-body-sm font-medium">Pagos</span>
      </router-link>
      
      <router-link to="/portfolio" active-class="bg-surface-container-high text-primary border border-primary shadow-[0_0_10px_rgba(233,193,118,0.1)]" class="text-on-surface-variant hover:text-on-surface px-4 py-3 flex items-center gap-3 rounded-xl hover:bg-surface-container-high transition-all duration-200">
        <span class="material-symbols-outlined" data-icon="account_tree">trending_up</span>
        <span class="font-body-sm text-body-sm font-medium">Cartera</span>
      </router-link>
    </nav>

    <!-- Footer Actions -->
    <div class="p-4 space-y-1 mb-4">
      <a class="text-on-surface-variant hover:text-on-surface px-4 py-2 flex items-center gap-3 rounded-xl hover:bg-surface-container-high transition-all duration-200 cursor-pointer">
        <span class="material-symbols-outlined text-lg" data-icon="settings">settings</span>
        <span class="font-body-sm text-body-sm font-medium">Configuración</span>
      </a>
      <a @click="handleLogout" class="text-error hover:bg-error-container/10 px-4 py-2 flex items-center gap-3 rounded-xl transition-all duration-200 cursor-pointer">
        <span class="material-symbols-outlined text-lg" data-icon="logout">logout</span>
        <span class="font-body-sm text-body-sm font-medium">Cerrar Sesión</span>
      </a>
    </div>
  </aside>
</template>

<script setup>
import { useRouter } from 'vue-router';

const router = useRouter();

const handleLogout = () => {
    localStorage.removeItem('token');
    router.push('/login');
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #131313;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #353535;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #e9c176;
}
</style>
