<template>
  <aside class="hidden md:flex flex-col bg-surface-dim dark:bg-surface-dim h-screen w-64 shadow-xl fixed left-0 top-0 border-r border-outline-variant z-50">
    <!-- Header -->
    <div class="p-6 border-b border-surface-container-low flex flex-col items-center justify-center gap-4">
      <img alt="Horus Logo Gold" class="w-16 h-16 rounded-full object-cover border-2 border-primary shadow-[0_0_15px_rgba(233,193,118,0.2)]" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDfcPwaOtB0nrnifCx3L2LwjdLAsUiW41ZeG4ORojWoloZN4Vi8DdLk84ziY_i5_TYw7XxmPjfTMXSVIg6mu7pnTPKvHnL8-VdlWM7giQJQWN1XSmKIUvo60yPCEqDw0zOU_rGuLy0VgcGS9RILTDOfLgIMXBqV4z9B29pZh9ICQKTMAsVvJRUlUmbKfRUPNfv1SJ5H0kt76PGx6Gx2-UIXF4mPDb1e7BZeM2AgAWlgRQqsJ2gPjjnuGZgiHQ7p-wkFQrQ"/>
      <div class="text-center">
        <h1 class="font-headline-lg text-headline-lg text-primary leading-none tracking-tight">Horus</h1>
        <p class="font-label-caps text-label-caps text-on-surface-variant mt-1 uppercase tracking-widest opacity-80">Elite Finance</p>
      </div>
      <button class="mt-4 w-full bg-primary text-on-primary font-title-md text-title-md py-2 px-4 rounded hover:bg-primary-fixed-dim transition-colors flex items-center justify-center gap-2 shadow-sm">
        <span class="material-symbols-outlined text-sm">add</span>
        Nuevo Crédito
      </button>
    </div>

    <!-- Navigation Tabs -->
    <nav class="flex-1 overflow-y-auto custom-scrollbar py-4 px-2 space-y-1">
      <router-link to="/dashboard" active-class="bg-primary-container text-on-primary-container border-l-4 border-primary shadow-sm" class="text-on-surface-variant hover:text-on-surface px-4 py-3 flex items-center gap-3 rounded hover:bg-surface-container-high transition-all duration-200">
        <span class="material-symbols-outlined" data-icon="dashboard">dashboard</span>
        <span class="font-body-sm text-body-sm font-semibold">Dashboard</span>
      </router-link>
      
      <router-link to="/loans" active-class="bg-primary-container text-on-primary-container border-l-4 border-primary shadow-sm" class="text-on-surface-variant hover:text-on-surface px-4 py-3 flex items-center gap-3 rounded hover:bg-surface-container-high transition-all duration-200">
        <span class="material-symbols-outlined" data-icon="payments">payments</span>
        <span class="font-body-sm text-body-sm font-semibold">Préstamos</span>
      </router-link>

      <router-link to="/clients" active-class="bg-primary-container text-on-primary-container border-l-4 border-primary shadow-sm" class="text-on-surface-variant hover:text-on-surface px-4 py-3 flex items-center gap-3 rounded hover:bg-surface-container-high transition-all duration-200">
        <span class="material-symbols-outlined" data-icon="group">group</span>
        <span class="font-body-sm text-body-sm font-semibold">Clientes</span>
      </router-link>

      <router-link to="/investors" active-class="bg-primary-container text-on-primary-container border-l-4 border-primary shadow-sm" class="text-on-surface-variant hover:text-on-surface px-4 py-3 flex items-center gap-3 rounded hover:bg-surface-container-high transition-all duration-200">
        <span class="material-symbols-outlined" data-icon="account_balance">account_balance</span>
        <span class="font-body-sm text-body-sm font-semibold">Inversionistas</span>
      </router-link>
      
      <router-link to="/payments" active-class="bg-primary-container text-on-primary-container border-l-4 border-primary shadow-sm" class="text-on-surface-variant hover:text-on-surface px-4 py-3 flex items-center gap-3 rounded hover:bg-surface-container-high transition-all duration-200">
        <span class="material-symbols-outlined" data-icon="receipt_long">receipt_long</span>
        <span class="font-body-sm text-body-sm font-semibold">Pagos</span>
      </router-link>
      
      <router-link to="/portfolio" active-class="bg-primary-container text-on-primary-container border-l-4 border-primary shadow-sm" class="text-on-surface-variant hover:text-on-surface px-4 py-3 flex items-center gap-3 rounded hover:bg-surface-container-high transition-all duration-200">
        <span class="material-symbols-outlined" data-icon="account_tree">account_tree</span>
        <span class="font-body-sm text-body-sm font-semibold">Cartera</span>
      </router-link>
    </nav>

    <!-- Footer Actions -->
    <div class="p-4 border-t border-surface-container-low space-y-1">
      <a class="text-on-surface-variant hover:text-on-surface px-4 py-2 flex items-center gap-3 rounded hover:bg-surface-container-high transition-all duration-200 cursor-pointer">
        <span class="material-symbols-outlined text-sm" data-icon="settings">settings</span>
        <span class="font-label-caps text-label-caps">Configuración</span>
      </a>
      <a @click="handleLogout" class="text-error hover:bg-error-container/10 px-4 py-2 flex items-center gap-3 rounded transition-all duration-200 cursor-pointer">
        <span class="material-symbols-outlined text-sm" data-icon="logout">logout</span>
        <span class="font-label-caps text-label-caps">Cerrar Sesión</span>
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
