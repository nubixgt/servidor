<template>
    <div :class="['bg-surface text-on-surface min-h-screen font-body flex transition-colors duration-500', themeClass]">
        <!-- Mobile Sidebar Overlay -->
        <div v-if="isMobileMenuOpen" @click="isMobileMenuOpen = false" class="fixed inset-0 bg-black/50 z-40 lg:hidden backdrop-blur-sm transition-opacity"></div>
        
        <!-- Sidebar -->
        <aside :class="['w-72 fixed left-0 top-0 h-full bg-surface-container-lowest flex flex-col p-6 z-50 border-r border-surface-container-low transition-transform duration-300 lg:translate-x-0', {'translate-x-0': isMobileMenuOpen, '-translate-x-full': !isMobileMenuOpen}]">
            <div class="mb-8 px-4">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary to-primary-dim flex items-center justify-center text-on-primary shadow-sm">
                        <span class="material-symbols-outlined">account_balance</span>
                    </div>
                    <div>
                        <h2 class="font-bold text-on-surface leading-tight font-headline">Ethereal Bureau</h2>
                        <p class="text-[10px] uppercase tracking-widest text-outline">Gestión Operativa</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto pr-2">
                <SidebarItem
                    icon="dashboard"
                    label="Dashboard"
                    :to="dashboardRoute"
                />

                <div class="pt-4 pb-2">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-outline-variant px-4 mb-2">Categorías</p>
                    <SidebarItem
                        v-for="cat in visibleCategories"
                        :key="cat.id"
                        :icon="cat.icon"
                        :label="cat.label"
                        :to="cat.to"
                    />
                </div>

                <div class="pt-4 pb-2">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-outline-variant px-4 mb-2">Herramientas</p>
                    <SidebarItem icon="event" label="Calendario Global" to="/calendario" />
                    <SidebarItem icon="view_kanban" label="Gestor de Tareas" to="/kanban" />
                    <SidebarItem icon="inventory_2" label="Archivo Central" to="/archivo" />
                    <SidebarItem v-if="auth.role === 'administrador'" icon="manage_accounts" label="Gestión de Usuarios" to="/usuarios" />
                    <SidebarItem v-if="auth.role === 'administrador'" icon="tune" label="Configuración" to="/configuracion" />
                </div>
            </nav>

            <div class="mt-auto pt-6 border-t border-surface-container-low">
                <button class="w-full py-3 px-4 bg-gradient-to-br from-primary to-primary-dim text-on-primary rounded-xl font-bold flex items-center justify-center gap-2 mb-4 shadow-ambient hover:scale-[1.02] transition-transform">
                    <span class="material-symbols-outlined">add</span> Nuevo Registro
                </button>
                <div class="space-y-1">
                    <button @click="handleLogout" class="w-full flex items-center gap-3 px-4 py-2 text-on-surface-variant hover:bg-surface-container-low rounded-lg transition-all text-xs font-medium">
                        <span class="material-symbols-outlined text-lg">logout</span> Cerrar Sesión
                    </button>
                </div>
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
                    <span class="text-xl font-bold text-on-surface uppercase tracking-widest font-headline opacity-0 hidden lg:block">Ethereal Bureau</span>
                </div>
                <div class="flex items-center gap-4">
                    <div class="relative group hidden md:block">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline scale-90">search</span>
                        <input type="text" placeholder="Buscar..." class="bg-surface-container-low border-none rounded-full pl-10 pr-4 py-2 text-sm w-64 focus:ring-2 focus:ring-primary/20 transition-all outline-none" />
                    </div>
                    <button class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container-low transition-all duration-300 text-on-surface-variant">
                        <span class="material-symbols-outlined">notifications</span>
                    </button>
                    <div class="h-8 w-[1px] bg-surface-container-high mx-2"></div>
                    <div class="flex items-center gap-3">
                        <div class="text-right hidden md:block">
                            <p class="text-xs font-bold text-on-surface capitalize">{{ auth.role }}</p>
                            <p class="text-[10px] text-on-surface-variant uppercase tracking-widest">Usuario Activo</p>
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
import { ref, computed, watch } from 'vue';
import { useAuthStore } from '../../stores/authStore.js';
import { useRouter, useRoute } from 'vue-router';
import SidebarItem from './SidebarItem.vue';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const isMobileMenuOpen = ref(false);

// Close menu when route changes
watch(() => route.path, () => {
    isMobileMenuOpen.value = false;
});

const CATEGORIES_DATA = [
    { id: 'iniciativas', to: '/iniciativas', icon: 'gavel', label: 'Iniciativas de Ley' },
    { id: 'citaciones', to: '/citaciones', icon: 'record_voice_over', label: 'Citaciones' },
    { id: 'comisiones', to: '/comisiones', icon: 'groups', label: 'Comisiones' },
    { id: 'fiscalizacion', to: '/fiscalizacion', icon: 'policy', label: 'Fiscalización' },
    { id: 'compromisos', to: '/compromisos', icon: 'handshake', label: 'Compromisos Distritales' },
    { id: 'actividades', to: '/actividades', icon: 'event_available', label: 'Actividades' },
    { id: 'redes', to: '/redes', icon: 'public', label: 'Redes Sociales' },
    { id: 'afiliaciones', to: '/afiliaciones', icon: 'how_to_reg', label: 'Afiliaciones Políticas' },
];

const dashboardRoute = computed(() => {
    return auth.role === 'administrador' ? '/dashboard-admin' : '/dashboard-tecnico';
});

const visibleCategories = computed(() => {
    if (auth.role === 'administrador') return CATEGORIES_DATA;
    return CATEGORIES_DATA.filter(c => c.id === auth.assignedCategory);
});

const themeClass = computed(() => {
    const moduleNames = ['iniciativas', 'citaciones', 'comisiones', 'fiscalizacion', 'compromisos', 'actividades', 'redes', 'afiliaciones'];
    const current = route.path.replace('/', '');
    if (moduleNames.includes(current)) {
        return `theme-${current}`;
    }
    return '';
});

const handleLogout = () => {
    auth.logout();
    router.push('/login');
};
</script>
