<template>
    <div class="min-h-screen text-[#f1f5f9] flex overflow-x-hidden relative">
        <!-- Mobile backdrop -->
        <div
            v-if="mobileOpen"
            class="fixed inset-0 bg-black/70 backdrop-blur-md z-40 md:hidden animate-fadeIn"
            @click="mobileOpen = false"
        ></div>

        <!-- Sidebar -->
        <aside
            class="h-screen fixed left-0 top-0 bg-white/10 backdrop-blur-2xl backdrop-saturate-150 border-r border-white/15 flex flex-col p-4 z-50 transition-all duration-300 ease-in-out"
            :class="mobileOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
            :style="{ width: collapsed ? '84px' : '260px' }"
        >
            <div class="mb-5 flex items-center justify-between px-2 pt-1">
                <div class="flex items-center space-x-2.5 cursor-pointer min-w-0" @click="router.push({ name: navItems[0]?.name })">
                    <div class="w-8 h-8 rounded-lg bg-white/15 border border-white/30 flex items-center justify-center shadow-[0_0_12px_rgba(34,197,94,0.25)] flex-shrink-0">
                        <svg viewBox="0 0 48 48" width="18" height="18">
                            <defs><linearGradient id="gradSide" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4ade80"/><stop offset="1" stop-color="#16a34a"/></linearGradient></defs>
                            <path d="M6 30 Q18 10 42 14 Q26 20 22 40 Q16 26 6 30Z" fill="url(#gradSide)"/>
                        </svg>
                    </div>
                    <h1 v-show="!collapsed" class="text-lg font-bold text-white tracking-tight truncate">
                        Keyline<span class="text-[#22c55e]">GT</span>
                    </h1>
                </div>
                <button class="hidden md:flex p-1 rounded-lg text-white/40 hover:text-white flex-shrink-0" @click="collapsed = !collapsed">
                    <ChevronLeft v-if="!collapsed" class="w-4 h-4" />
                    <ChevronRight v-else class="w-4 h-4" />
                </button>
                <button class="p-1 rounded-lg text-white/60 hover:text-white md:hidden" @click="mobileOpen = false">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto pr-1">
                <router-link
                    v-for="item in navItems"
                    :key="item.name"
                    :to="{ name: item.name }"
                    class="w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-[13px] font-medium transition-all duration-150 text-left"
                    :class="route.name === item.name
                        ? 'bg-white/20 border border-white/30 text-white font-semibold'
                        : 'text-white/60 hover:text-white hover:bg-white/10 border border-transparent'"
                    @click="mobileOpen = false"
                >
                    <component :is="item.icon" class="w-4 h-4 flex-shrink-0" :class="route.name === item.name ? 'text-[#22c55e]' : 'text-white/40'" />
                    <span v-show="!collapsed" class="truncate">{{ item.label }}</span>
                </router-link>
            </nav>

            <div v-show="!collapsed" class="my-3 rounded-xl overflow-hidden relative h-20 bg-gradient-to-t from-[#0c2419] to-black/20 border border-white/15 flex items-end justify-center flex-shrink-0">
                <svg class="w-full h-full opacity-60" viewBox="0 0 200 80" preserveAspectRatio="none">
                    <path d="M0 60 Q 40 20 80 50 T 160 30 T 200 65 L 200 80 L 0 80 Z" fill="#133d2a" />
                    <path d="M0 70 Q 60 40 120 60 T 200 50 L 200 80 L 0 80 Z" fill="#1e543b" />
                    <path d="M0 75 Q 70 55 140 70 T 200 65 L 200 80 L 0 80 Z" fill="#22c55e" opacity="0.4" />
                    <path d="M10 55 Q 50 25 90 52 T 180 35" stroke="#4ade80" stroke-width="0.75" fill="none" stroke-dasharray="3,2" opacity="0.7" />
                </svg>
            </div>

            <div class="pt-2 space-y-2 border-t border-white/15 flex-shrink-0">
                <div
                    class="p-2 rounded-xl bg-white/10 border border-white/20 flex items-center justify-between gap-2 cursor-pointer hover:border-white/40 transition-colors"
                    @click="router.push({ name: 'Configuracion' })"
                >
                    <div class="flex items-center gap-2.5 overflow-hidden">
                        <div class="w-8 h-8 rounded-full bg-white/15 border border-white/30 flex items-center justify-center text-xs font-bold text-white flex-shrink-0">
                            {{ initials }}
                        </div>
                        <div v-show="!collapsed" class="overflow-hidden">
                            <p class="text-[12px] font-semibold text-white truncate">{{ auth.user?.nombre }}</p>
                            <p class="text-[11px] text-white/40 truncate">{{ roleLabel }}</p>
                        </div>
                    </div>
                    <ChevronDown v-show="!collapsed" class="w-3.5 h-3.5 text-white/40 flex-shrink-0" />
                </div>

                <button
                    class="w-full py-2 px-3 bg-white/10 hover:bg-[#ef4444]/15 hover:border-[#ef4444]/40 border border-white/20 rounded-xl text-[12px] text-white/60 hover:text-[#f87171] transition-all flex items-center justify-center gap-2 font-medium"
                    @click="handleLogout"
                >
                    <LogOut class="w-3.5 h-3.5" />
                    <span v-show="!collapsed">Cerrar sesión</span>
                </button>
            </div>
        </aside>

        <!-- Main content -->
        <div class="flex-1 flex flex-col min-h-screen relative z-10 transition-all duration-300" :style="{ marginLeft: mainMargin }">
            <header
                class="bg-white/10 backdrop-blur-2xl backdrop-saturate-150 fixed top-0 right-0 z-40 border-b border-white/15 flex justify-between items-center px-4 sm:px-6 py-2.5 h-[64px] transition-all duration-300"
                :style="{ width: headerWidth }"
            >
                <div class="flex-1 flex items-center gap-3 min-w-0">
                    <button class="md:hidden p-2 rounded-lg text-white/60 hover:text-white hover:bg-white/10 transition-colors" @click="mobileOpen = true">
                        <Menu class="w-5 h-5" />
                    </button>
                    <div class="min-w-0 hidden sm:block">
                        <h2 class="text-[15px] font-bold text-white tracking-tight truncate">{{ pageTitle }}</h2>
                        <p class="text-[11px] text-white/60 truncate">{{ pageSubtitle }}</p>
                    </div>
                    <div class="relative w-full max-w-md group ml-auto sm:ml-4">
                        <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-white/40 group-focus-within:text-white transition-colors" />
                        <input
                            type="text"
                            placeholder="Buscar parcelas, técnicos, departamentos..."
                            class="w-full bg-white/10 border border-white/20 rounded-full py-1.5 pl-10 pr-4 text-xs text-white focus:outline-none focus:border-white/40 transition-all placeholder:text-white/40"
                        />
                    </div>
                </div>

                <div class="flex items-center space-x-3 sm:space-x-4 flex-shrink-0 ml-3">
                    <div class="hidden sm:flex items-center gap-1.5 text-xs text-white/80 bg-white/10 border border-white/20 px-3 py-1.5 rounded-full">
                        <Sun class="w-3.5 h-3.5 text-[#eab308]" />
                        <span class="font-medium text-white">Guatemala</span>
                    </div>

                    <!-- Notifications -->
                    <div class="relative">
                        <button
                            class="p-2 text-white/60 hover:text-white transition-colors relative rounded-full hover:bg-white/10"
                            title="Alertas del sistema"
                            @click="showNotifications = !showNotifications; showProfileMenu = false"
                        >
                            <Bell class="w-4 h-4" />
                        </button>

                        <div v-if="showNotifications" class="absolute right-0 mt-2 w-80 bg-[#0c1e17]/95 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] shadow-2xl p-4 z-50 animate-fadeIn">
                            <div class="flex justify-between items-center pb-2 mb-2 border-b border-white/15">
                                <h4 class="text-xs font-bold text-white uppercase tracking-wider">Alertas activas</h4>
                            </div>
                            <div class="text-xs text-white/60 py-4 text-center">
                                No hay alertas nuevas por el momento.
                            </div>
                        </div>
                    </div>

                    <!-- Profile -->
                    <div class="relative">
                        <button class="flex items-center" @click="showProfileMenu = !showProfileMenu; showNotifications = false">
                            <div class="w-8 h-8 rounded-full border border-[#22c55e]/50 overflow-hidden cursor-pointer hover:border-[#22c55e] transition-colors bg-white/15 flex items-center justify-center">
                                <span class="text-xs font-bold text-white">{{ initials }}</span>
                            </div>
                        </button>

                        <div v-if="showProfileMenu" class="absolute right-0 mt-2 w-56 bg-[#0c1e17]/95 backdrop-blur-2xl backdrop-saturate-150 border border-white/20 rounded-2xl shadow-[inset_0_1px_0_rgba(255,255,255,0.3)] shadow-2xl p-2 z-50 animate-fadeIn">
                            <div class="p-2.5 border-b border-white/15 mb-1">
                                <p class="text-xs font-bold text-white truncate">{{ auth.user?.nombre }}</p>
                                <p class="text-[11px] text-[#22c55e]">{{ roleLabel }}</p>
                                <span v-if="auth.user?.email" class="text-[10px] text-white/40">{{ auth.user.email }}</span>
                            </div>

                            <button
                                class="w-full text-left px-3 py-2 rounded-xl text-xs text-white/80 hover:bg-white/15 flex items-center gap-2 transition-colors"
                                @click="router.push({ name: 'Configuracion' }); showProfileMenu = false"
                            >
                                <User class="w-4 h-4 text-white/60" />
                                <span>Perfil y cuenta</span>
                            </button>

                            <button
                                class="w-full text-left px-3 py-2 rounded-xl text-xs text-[#f87171] hover:bg-[#ef4444]/20 flex items-center gap-2 transition-colors mt-1"
                                @click="showProfileMenu = false; handleLogout()"
                            >
                                <LogOut class="w-4 h-4" />
                                <span>Cerrar sesión</span>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8 pb-16" style="margin-top: 64px;">
                <router-view></router-view>
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { ROLE_LABELS } from '../../constants/keyline';
import { confirmDialog, toastInfo } from '../../utils/alerts';
import {
    Search, Bell, Menu, X, Sun, User,
    ChevronLeft, ChevronRight, ChevronDown, LogOut,
    LayoutDashboard, Layers, FileEdit, Sliders, BarChart2, Bug, Users, Settings,
} from '@lucide/vue';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();
const collapsed = ref(false);
const mobileOpen = ref(false);
const showNotifications = ref(false);
const showProfileMenu = ref(false);

const windowWidth = ref(window.innerWidth);
function onResize() { windowWidth.value = window.innerWidth; }
onMounted(() => window.addEventListener('resize', onResize));
onUnmounted(() => window.removeEventListener('resize', onResize));

const isDesktop = computed(() => windowWidth.value >= 768);
const mainMargin = computed(() => (isDesktop.value ? (collapsed.value ? '84px' : '260px') : '0px'));
const headerWidth = computed(() => (isDesktop.value ? `calc(100% - ${collapsed.value ? 84 : 260}px)` : '100%'));

const roleLabel = computed(() => ROLE_LABELS[auth.role] || auth.role);
const initials = computed(() => {
    const name = auth.user?.nombre || '';
    const parts = name.trim().split(/\s+/);
    return ((parts[0]?.[0] || '') + (parts[1]?.[0] || '')).toUpperCase() || '--';
});

const MENUS = {
    tecnico: [
        { name: 'ParcelaNueva', label: 'Registrar parcela', icon: FileEdit },
        { name: 'MisParcelas', label: 'Mis parcelas', icon: Layers },
        { name: 'Variables', label: 'Variables técnicas', icon: Sliders },
        { name: 'Configuracion', label: 'Configuración', icon: Settings },
    ],
    supervisor: [
        { name: 'Dashboard', label: 'Panel ejecutivo', icon: LayoutDashboard },
        { name: 'ParcelasList', label: 'Parcelas de mi región', icon: Layers },
        { name: 'ParcelaNueva', label: 'Registrar parcela', icon: FileEdit },
        { name: 'Bioindicadores', label: 'Bioindicadores', icon: Bug },
        { name: 'Reportes', label: 'Reportes y análisis', icon: BarChart2 },
        { name: 'Variables', label: 'Variables técnicas', icon: Sliders },
        { name: 'Usuarios', label: 'Equipo técnico', icon: Users },
        { name: 'Configuracion', label: 'Configuración', icon: Settings },
    ],
    administrador: [
        { name: 'Dashboard', label: 'Panel ejecutivo', icon: LayoutDashboard },
        { name: 'ParcelasList', label: 'Todas las parcelas', icon: Layers },
        { name: 'ParcelaNueva', label: 'Registrar parcela', icon: FileEdit },
        { name: 'Bioindicadores', label: 'Bioindicadores', icon: Bug },
        { name: 'Reportes', label: 'Reportes y análisis', icon: BarChart2 },
        { name: 'Variables', label: 'Variables técnicas', icon: Sliders },
        { name: 'Usuarios', label: 'Usuarios y equipo', icon: Users },
        { name: 'Configuracion', label: 'Configuración', icon: Settings },
    ],
};

const TITLES = {
    Dashboard: ['Panel ejecutivo', 'Vista general del avance nacional de parcelas Keyline'],
    ParcelasList: ['Base de parcelas', 'Consulta, filtra y valida la información técnica cargada'],
    ParcelaNueva: ['Registrar parcela', 'Captura los datos técnicos de campo'],
    ParcelaEditar: ['Editar parcela', 'Actualiza los datos técnicos de campo'],
    MisParcelas: ['Mis parcelas', 'Parcelas que has registrado'],
    Usuarios: ['Equipo del proyecto', 'Gestión de usuarios y roles'],
    Bioindicadores: ['Bioindicadores', 'Salud biológica del suelo reportada en campo'],
    Reportes: ['Reportes y análisis', 'Centro de exportación e indicadores del proyecto'],
    Variables: ['Variables técnicas', 'Catálogo de variables capturadas por parcela'],
    Configuracion: ['Configuración', 'Preferencias de tu cuenta'],
};

const navItems = computed(() => MENUS[auth.role] || []);
const pageTitle = computed(() => (TITLES[route.name] || [route.name, ''])[0]);
const pageSubtitle = computed(() => (TITLES[route.name] || [route.name, ''])[1]);

async function handleLogout() {
    const ok = await confirmDialog('Se cerrará tu sesión actual.', { title: '¿Cerrar sesión?', confirmText: 'Cerrar sesión' });
    if (!ok) return;
    auth.logout();
    toastInfo('Sesión cerrada.');
    router.push({ name: 'Login' });
}
</script>
