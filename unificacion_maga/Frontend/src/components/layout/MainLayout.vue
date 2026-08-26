<template>
    <div class="flex h-screen overflow-hidden transition-colors duration-500 relative bg-gray-50 dark:bg-[#050505] font-sans text-slate-600 dark:text-gray-100">
        
        <!-- BACKGROUNDS ARE HANDLED IN STYLE.CSS NOW -->

        <!-- Sidebar -->
        <!-- Mobile Overlay -->
        <div 
            v-if="isMobileMenuOpen" 
            @click="isMobileMenuOpen = false"
            class="fixed inset-0 bg-black/50 z-30 md:hidden"
        ></div>

        <Sidebar 
            :currentView="currentView" 
            @navigate="handleNavigate" 
            @logout="handleLogout"
            class="fixed md:static inset-y-0 left-0 z-40 transform transition-transform duration-300 md:transform-none"
            :class="isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'"
        />

        <CommandPalette v-model="isCommandPaletteOpen" />

        <main class="flex-1 flex flex-col h-full overflow-hidden relative z-10 w-full">

            <!-- ── Header mejorado ───────────────────────────────────── -->
            <header class="relative z-50 h-[64px] flex-shrink-0 flex items-center justify-between px-4 md:px-8
                           bg-white/80 dark:bg-[#0c1320]/80 backdrop-blur-2xl
                           border-b border-slate-200/80 dark:border-white/5
                           shadow-sm dark:shadow-none transition-all duration-300">

                <!-- Left: hamburger + breadcrumb title -->
                <div class="flex items-center gap-3">
                    <button
                        @click="isMobileMenuOpen = !isMobileMenuOpen"
                        class="md:hidden p-2 rounded-xl bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 active:scale-95 transition-all"
                    >
                        <Bars3Icon class="w-5 h-5 text-slate-600 dark:text-white" />
                    </button>

                    <!-- Animated page title with accent line -->
                    <div class="flex items-center gap-2.5">
                        <span class="hidden md:block w-1 h-6 rounded-full bg-gradient-to-b from-emerald-400 to-emerald-600 shadow-sm shadow-emerald-500/40"></span>
                        <h1 class="text-base md:text-lg font-bold text-slate-800 dark:text-white truncate max-w-[160px] md:max-w-xs lg:max-w-none">
                            {{ pageTitle }}
                        </h1>
                    </div>
                </div>

                <!-- Right controls -->
                <div class="flex items-center gap-2 md:gap-3">

                    <!-- Search (desktop) -->
                    <button
                        @click="isCommandPaletteOpen = true"
                        class="hidden md:flex items-center gap-2 h-9 px-3 rounded-xl
                               bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/8
                               hover:bg-slate-200 dark:hover:bg-white/10 hover:border-slate-300 dark:hover:border-white/15
                               active:scale-95 transition-all text-slate-500 dark:text-slate-400
                               hover:text-slate-700 dark:hover:text-slate-200 w-48 group"
                    >
                        <MagnifyingGlassIcon class="w-4 h-4 shrink-0 group-hover:text-emerald-500 transition-colors" />
                        <span class="text-xs flex-1 text-left">Buscar...</span>
                        <div class="flex gap-0.5">
                            <kbd class="text-[9px] font-bold bg-white dark:bg-white/10 border border-slate-200 dark:border-white/10 rounded px-1 py-0.5 text-slate-400">Ctrl</kbd>
                            <kbd class="text-[9px] font-bold bg-white dark:bg-white/10 border border-slate-200 dark:border-white/10 rounded px-1 py-0.5 text-slate-400">K</kbd>
                        </div>
                    </button>

                    <div class="w-px h-5 bg-slate-200 dark:bg-white/10"></div>

                    <!-- Theme toggle with animation -->
                    <button
                        @click="toggleTheme"
                        class="relative p-2.5 rounded-xl border transition-all duration-200 active:scale-95
                               bg-slate-100 dark:bg-white/5 border-slate-200 dark:border-white/8
                               hover:bg-amber-50 dark:hover:bg-amber-400/10
                               hover:border-amber-300 dark:hover:border-amber-400/30
                               text-slate-500 dark:text-slate-400 hover:text-amber-500 dark:hover:text-amber-400"
                        :title="isDark ? 'Modo claro' : 'Modo oscuro'"
                    >
                        <Transition name="icon-flip" mode="out-in">
                            <SunIcon  v-if="isDark"  key="sun"  class="w-[18px] h-[18px]" />
                            <MoonIcon v-else          key="moon" class="w-[18px] h-[18px]" />
                        </Transition>
                    </button>

                    <!-- Notifications -->
                    <div class="relative">
                        <!-- Unread badge -->
                        <Transition name="badge-pop">
                            <span v-if="unreadCount > 0"
                                  key="badge"
                                  class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1
                                         bg-red-500 text-white text-[10px] font-black rounded-full
                                         flex items-center justify-center z-10
                                         border-2 border-white dark:border-slate-900
                                         shadow-md shadow-red-500/40 pointer-events-none">
                                {{ unreadCount > 9 ? '9+' : unreadCount }}
                            </span>
                        </Transition>

                        <button
                            @click="toggleNotifications"
                            :class="[
                                'p-2.5 rounded-xl border transition-all duration-200 active:scale-95',
                                showNotifications
                                    ? 'bg-emerald-50 dark:bg-emerald-500/15 border-emerald-300 dark:border-emerald-500/40 text-emerald-600 dark:text-emerald-400'
                                    : 'bg-slate-100 dark:bg-white/5 border-slate-200 dark:border-white/8 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-white/10',
                                hasNewNotification ? 'animate-bounce-in' : ''
                            ]"
                        >
                            <BellIcon class="w-[18px] h-[18px]" />
                        </button>

                        <!-- Notification dropdown con soporte para texto largo -->
                        <Transition name="dropdown">
                            <div v-if="showNotifications"
                                 class="absolute right-0 top-full mt-2 w-96
                                        bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10
                                        rounded-2xl shadow-xl shadow-black/10 dark:shadow-black/40 z-50 overflow-hidden">

                                <!-- Header del panel -->
                                <div class="px-4 py-3 border-b border-slate-100 dark:border-white/8 flex justify-between items-center bg-slate-50/80 dark:bg-slate-800/50">
                                    <h3 class="font-bold text-sm text-slate-800 dark:text-white flex items-center gap-2">
                                        <BellIcon class="w-4 h-4 text-emerald-500" />
                                        Notificaciones
                                        <span v-if="unreadCount > 0"
                                              class="text-[10px] bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 font-black px-1.5 py-0.5 rounded-full">
                                            {{ unreadCount }} nueva{{ unreadCount > 1 ? 's' : '' }}
                                        </span>
                                    </h3>
                                    <button v-if="unreadCount > 0" @click="markAllAsRead"
                                            class="text-xs text-emerald-600 dark:text-emerald-400 font-bold hover:text-emerald-700 dark:hover:text-emerald-300 transition-colors px-2 py-1 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-900/20">
                                        Marcar leídas
                                    </button>
                                </div>

                                <!-- Lista de notificaciones -->
                                <div class="max-h-[420px] overflow-y-auto custom-scrollbar">
                                    <!-- Estado vacío -->
                                    <div v-if="notifications.length === 0" class="py-12 text-center">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3">
                                            <BellIcon class="w-6 h-6 text-slate-400" />
                                        </div>
                                        <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Sin notificaciones nuevas</p>
                                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Estás al día con todo</p>
                                    </div>

                                    <!-- Items -->
                                    <div v-else>
                                        <div v-for="notif in notifications" :key="notif.id"
                                             :class="['border-b border-slate-100 dark:border-white/5 transition-colors cursor-pointer group',
                                                      !notif.read ? 'bg-emerald-50/60 dark:bg-emerald-900/10' : 'hover:bg-slate-50 dark:hover:bg-white/4']">
                                            <div class="p-4">
                                                <div class="flex items-start gap-3">
                                                    <!-- Ícono tipo -->
                                                    <div :class="['mt-0.5 rounded-xl p-1.5 shrink-0 transition-transform group-hover:scale-110',
                                                                  notif.type === 'success' ? 'text-emerald-600 bg-emerald-100 dark:bg-emerald-400/15'
                                                                  : notif.type === 'warning' ? 'text-amber-600 bg-amber-100 dark:bg-amber-400/15'
                                                                  : 'text-blue-600 bg-blue-100 dark:bg-blue-400/15']">
                                                        <CheckCircleIcon v-if="notif.type === 'success'" class="w-4 h-4" />
                                                        <ExclamationTriangleIcon v-else-if="notif.type === 'warning'" class="w-4 h-4" />
                                                        <InformationCircleIcon v-else class="w-4 h-4" />
                                                    </div>

                                                    <!-- Contenido -->
                                                    <div class="flex-1 min-w-0">
                                                        <!-- Título: wrap en 2 líneas máximo -->
                                                        <h4 :class="['text-sm font-semibold leading-snug mb-1',
                                                                     !notif.read ? 'text-slate-800 dark:text-white' : 'text-slate-600 dark:text-slate-300']"
                                                            style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                            {{ notif.title }}
                                                        </h4>

                                                        <!-- Mensaje: expandible si es muy largo -->
                                                        <div class="relative">
                                                            <p :class="['text-xs text-slate-600 dark:text-slate-300 leading-relaxed font-medium',
                                                                        !notif._expanded ? 'line-clamp-3' : '']">
                                                                {{ notif.message }}
                                                            </p>
                                                            <!-- Botón ver más / ver menos -->
                                                            <button v-if="notif.message && notif.message.length > 120"
                                                                    @click.stop="notif._expanded = !notif._expanded"
                                                                    class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline mt-1 block">
                                                                {{ notif._expanded ? 'Ver menos ↑' : 'Ver más ↓' }}
                                                            </button>
                                                        </div>

                                                        <!-- Footer: tiempo + badge no leído -->
                                                        <div class="flex items-center justify-between mt-2">
                                                            <span class="text-[10px] text-slate-400 dark:text-slate-500">{{ notif.time }}</span>
                                                            <span v-if="!notif.read"
                                                                  class="inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-1.5 py-0.5 rounded-full">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Nuevo
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <!-- Dot no leído -->
                                                    <div v-if="!notif.read" class="w-2 h-2 bg-emerald-500 rounded-full mt-1.5 shrink-0 shadow shadow-emerald-400/50"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="px-4 py-2.5 text-center border-t border-slate-100 dark:border-white/8 bg-slate-50/60 dark:bg-slate-800/30">
                                    <button class="text-xs font-semibold text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">Ver historial completo</button>
                                </div>
                            </div>
                        </Transition>

                    </div>
                </div>
            </header>

            <!-- ── Content Area ─────────────────────────────────────── -->
            <div class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 custom-scrollbar relative">
                <router-view v-slot="{ Component }">
                    <transition name="fade-slide" mode="out-in">
                        <component :is="Component" />
                    </transition>
                </router-view>
            </div>
        </main>
    </div>
</template>

<script setup>

import { ref, computed, onMounted, watch, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import Sidebar from './Sidebar.vue';
import CommandPalette from './CommandPalette.vue';
import { notificationService } from '../../services/NotificationService';
import { 
    MagnifyingGlassIcon, 
    SunIcon, 
    MoonIcon, 
    BellIcon, 
    CheckCircleIcon, 
    ExclamationTriangleIcon, 
    InformationCircleIcon,
    Bars3Icon,
    XMarkIcon
} from '@heroicons/vue/24/outline';

const router = useRouter();
const route = useRoute();

const showNotifications = ref(false);
const isMobileMenuOpen = ref(false); // Mobile Menu State
const isCommandPaletteOpen = ref(false); // Omnibar State

const currentView = computed(() => {
    // Return exact lowercase route name so it matches Sidebar keys perfectly
    return route.name ? route.name.toLowerCase() : 'home';
});

const pageTitle = computed(() => {
    switch (currentView.value) {
        case 'home': return 'Inicio';
        case 'admindashboard': return 'Inicio MAGA'; // When matching AdminDashboard route
        case 'productores': return 'Padrón de Productores';
        case 'sanidad': return 'Sanidad Agropecuaria';
        case 'licencias': return 'Licencias y Permisos';
        case 'extension': return 'Extensión Rural';
        case 'seguridadalimentaria': return 'Seguridad Alimentaria';
        case 'actividadesdespacho': return 'Gestión Ministerial';
        case 'presupuesto': return 'Ejecución Presupuestaria';
        case 'votaciones': return 'Sistema de Votaciones';
        case 'magareports': return 'Reportes Técnicos';
        case 'magausers': return 'Gestión de Usuarios';
        case 'magasettings': return 'Configuración';
        case 'visandashboard': return 'VISAN · Dashboard';
        case 'visandapca': return 'VISAN · DAPCA';
        case 'visantabla': return 'VISAN · Tabla de Datos';
        case 'visaneditar': return 'VISAN · Edición de Datos';
        case 'visardashboard': return 'VISAR · Dashboard Hub';
        case 'visarexportaciones': return 'VISAR · Exportaciones';
        case 'visarimportaciones': return 'VISAR · Importaciones';
        case 'visarlicenciastransporte': return 'VISAR · Licencias Transporte';
        case 'visarlicenciasfitosanitarias': return 'VISAR · Licencias Fito';
        case 'visarlibreventa': return 'VISAR · Libre Venta';
        case 'viderdashboard': return 'VIDER · Dashboard';
        case 'vidertabla': return 'VIDER · Base de Datos';
        case 'viderimportar': return 'VIDER · Importar Datos';
        case 'vidertobanik': return 'VIDER · Tobanik (Cooperativas)';
        case 'viderreportes': return 'VIDER · Centro de Reportes';
        case 'viderhistorial': return 'VIDER · Historial';
        case 'viderusuarios': return 'VIDER · Gestión de Usuarios';
        default: return 'Sistema MAGA';
    }
});

// Dynamic Notifications
const notifications = ref([]);
let pollingInterval = null;

const loadNotifications = async () => {
    notifications.value = await notificationService.getUnread();
};

const unreadCount = computed(() => notifications.value.filter(n => !n.read).length);
const hasNewNotification = ref(false);

watch(unreadCount, (newVal, oldVal) => {
    if (newVal > (oldVal || 0)) {
        hasNewNotification.value = true;
        setTimeout(() => {
            hasNewNotification.value = false;
        }, 3000);
    }
});

const isDark = ref(localStorage.getItem('theme') === 'dark');

const toggleTheme = () => {
    isDark.value = !isDark.value;
};

watch(isDark, (val) => {
    if (val) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
}, { immediate: true });

const toggleNotifications = () => {
    showNotifications.value = !showNotifications.value;
};

const markAllAsRead = async () => {
    try {
        await notificationService.markAllAsRead();
        notifications.value = []; // Limpiamos la vista inmediatamente
    } catch (error) {
        console.error("No se pudieron marcar como leídas");
    }
};

const handleNavigate = (path) => {
    if (path && path.startsWith('/')) {
        router.push(path);
    } else {
        router.push('/admin/dashboard');
    }
    isMobileMenuOpen.value = false; // Close mobile menu on nav
};

const handleLogout = () => {
    localStorage.removeItem('isAuthenticated');
    router.push('/login');
};

// Initialize theme preference if needed
onMounted(() => {
    // Load initial notifications
    loadNotifications();
    
    // Set up polling (every 15 seconds para mejor UX real-time)
    pollingInterval = setInterval(loadNotifications, 15000);
});

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
});
</script>

<style scoped>
/* ── Transición de página ─────────────────── */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(18px) scale(0.99);
}
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-10px) scale(0.99);
}

/* ── Dropdown del panel de notificaciones ─── */
.dropdown-enter-active { transition: all 0.25s cubic-bezier(0.16,1,0.3,1); }
.dropdown-leave-active { transition: all 0.15s ease-in; }
.dropdown-enter-from  { opacity: 0; transform: translateY(-8px) scale(0.97); }
.dropdown-leave-to    { opacity: 0; transform: translateY(-4px) scale(0.98); }

/* ── Flip del ícono sol/luna ──────────────── */
.icon-flip-enter-active { transition: all 0.2s cubic-bezier(0.34,1.56,0.64,1); }
.icon-flip-leave-active { transition: all 0.15s ease-in; }
.icon-flip-enter-from  { opacity: 0; transform: rotate(-90deg) scale(0.6); }
.icon-flip-leave-to    { opacity: 0; transform: rotate(90deg)  scale(0.6); }

/* ── Badge de notificaciones ─────────────── */
.badge-pop-enter-active { transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1); }
.badge-pop-leave-active { transition: all 0.2s ease-in; }
.badge-pop-enter-from  { opacity: 0; transform: scale(0); }
.badge-pop-leave-to    { opacity: 0; transform: scale(0); }
</style>

