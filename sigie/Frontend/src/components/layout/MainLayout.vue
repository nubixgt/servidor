<template>
    <div class="bg-surface text-on-surface min-h-screen font-body flex transition-colors duration-500">
        <!-- Mobile Sidebar Overlay -->
        <div v-if="isMobileMenuOpen" @click="isMobileMenuOpen = false" class="fixed inset-0 bg-black/50 z-40 lg:hidden backdrop-blur-sm transition-opacity"></div>
        
        <!-- Sidebar -->
        <aside :class="['w-72 fixed left-0 top-0 h-full bg-surface-container-lowest flex flex-col p-6 z-50 border-r border-surface-container-low transition-transform duration-300 lg:translate-x-0', {'translate-x-0': isMobileMenuOpen, '-translate-x-full': !isMobileMenuOpen}]">
            <div class="mb-8 px-2 flex items-center gap-3">
                <div class="w-10 h-10 overflow-hidden rounded-lg flex items-center justify-center flex-shrink-0 bg-primary/10">
                    <img :src="logoUrl" alt="SIGIE Logo" class="w-8 h-8 object-contain" />
                </div>
                <span class="text-2xl font-black tracking-wider text-primary font-headline">SIGIE</span>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto pr-2">
                <p class="text-[10px] font-bold uppercase tracking-widest text-outline-variant px-4 mb-2">General</p>
                <SidebarItem
                    icon="dashboard"
                    label="Panel Principal"
                    to="/dashboard"
                />

                <div class="pt-4 pb-2">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-outline-variant px-4 mb-2">Operaciones</p>
                    <!-- Inspectores only -->
                    <SidebarItem
                        v-if="auth.role === 'inspector'"
                        icon="pin_drop"
                        label="Registrar Check-in"
                        to="/checkin"
                    />
                    <SidebarItem
                        v-if="auth.role === 'inspector'"
                        icon="pets"
                        label="Registrar Sacrificio"
                        to="/sacrificio/nuevo"
                    />
                    <!-- Administradores only -->
                    <SidebarItem
                        v-if="auth.role === 'administrador'"
                        icon="assignment"
                        label="Historial de Check-ins"
                        to="/checkin-list"
                    />
                    <SidebarItem
                        v-if="auth.role === 'administrador'"
                        icon="assignment_turned_in"
                        label="Control de Sacrificios"
                        to="/sacrificios"
                    />
                </div>
            </nav>

            <div class="mt-auto pt-6 border-t border-surface-container-low">
                <div class="bg-surface-container-low p-4 rounded-xl mb-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-primary text-on-primary flex items-center justify-center font-bold text-sm uppercase">
                        {{ auth.user?.nombre?.substring(0, 2) || 'US' }}
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-bold text-on-surface truncate">{{ auth.user?.nombre || 'Usuario' }}</p>
                        <p class="text-[10px] text-on-surface-variant capitalize truncate">{{ auth.role }}</p>
                    </div>
                </div>
                <button @click="handleLogout" class="w-full flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 hover:text-red-700 rounded-xl transition-all text-sm font-semibold">
                    <span class="material-symbols-outlined text-lg">logout</span> Cerrar Sesión
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-h-screen lg:ml-72 w-full transition-all duration-300">
            <!-- Top Navbar -->
            <header class="sticky top-0 z-30 glass-header px-4 lg:px-10 py-4 flex justify-between items-center border-b border-surface-container-low/50">
                <div class="flex items-center gap-4 lg:gap-8">
                    <!-- Mobile Menu Button -->
                    <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="w-10 h-10 flex lg:hidden items-center justify-center rounded-full hover:bg-surface-container-low transition-all duration-300 text-on-surface-variant">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <span class="text-sm font-bold text-on-surface-variant uppercase tracking-widest font-headline hidden lg:block">Sistema de Gestión de Inspecciones</span>
                </div>
                <div class="flex items-center gap-4">
                    <div class="relative group hidden md:block">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline scale-90">search</span>
                        <input type="text" placeholder="Buscar..." class="bg-surface-container-low border-none rounded-full pl-10 pr-4 py-2 text-sm w-64 focus:ring-2 focus:ring-primary/20 transition-all outline-none text-on-surface" />
                    </div>
                    <button class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container-low transition-all duration-300 text-on-surface-variant">
                        <span class="material-symbols-outlined">notifications</span>
                    </button>
                    <div class="h-8 w-[1px] bg-surface-container-high mx-2"></div>
                    <div class="flex items-center gap-3">
                        <div class="text-right hidden md:block">
                            <p class="text-xs font-bold text-on-surface capitalize">{{ auth.role }}</p>
                            <p class="text-[9px] text-on-surface-variant uppercase tracking-widest">En Línea</p>
                        </div>
                        <div class="w-9 h-9 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center font-bold text-xs border-2 border-surface-container-highest uppercase">
                            {{ auth.role?.substring(0, 2) }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- View Container -->
            <main class="p-4 lg:p-10 flex-1">
                <router-view></router-view>
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useAuthStore } from '../../stores/authStore.js';
import { useRouter, useRoute } from 'vue-router';
import SidebarItem from './SidebarItem.vue';

const getLogoUrl = () => {
    const path = window.location.pathname.toLowerCase();
    const isViteDev = window.location.port !== '' && window.location.port !== '80' && window.location.port !== '8080';
    if (isViteDev) {
        return '/sigie/logo.png';
    }
    const distIndex = path.indexOf('/frontend/dist');
    if (distIndex !== -1) {
        return window.location.pathname.substring(0, distIndex) + '/Frontend/dist/logo.png';
    }
    const sigieIndex = path.indexOf('/sigie');
    if (sigieIndex !== -1) {
        return window.location.pathname.substring(0, sigieIndex) + '/sigie/logo.png';
    }
    return '/logo.png';
};
const logoUrl = getLogoUrl();

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const isMobileMenuOpen = ref(false);

// Close menu when route changes
watch(() => route.path, () => {
    isMobileMenuOpen.value = false;
});

const handleLogout = () => {
    auth.logout();
    router.push('/login');
};
</script>
