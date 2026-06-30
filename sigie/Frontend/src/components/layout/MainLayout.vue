<template>
    <div class="h-screen w-full font-body flex bg-app-fondo overflow-hidden">
        <!-- Mobile Sidebar Overlay -->
        <div v-if="isMobileMenuOpen" @click="isMobileMenuOpen = false" class="fixed inset-0 bg-black/60 z-40 lg:hidden backdrop-blur-sm transition-opacity"></div>
        
        <!-- Sidebar -->
        <aside :class="['w-64 fixed lg:static left-0 top-0 h-full bg-gradient-to-b from-[#0a192f] to-[#0d1f3c] flex flex-col px-4 py-5 z-50 transition-transform duration-300 lg:translate-x-0 flex-shrink-0', {'translate-x-0': isMobileMenuOpen, '-translate-x-full': !isMobileMenuOpen}]">
            <!-- Brand Logo Header -->
            <div class="mb-6 px-2 flex items-center gap-3">
                <div class="w-10 h-10 overflow-hidden rounded-full flex items-center justify-center flex-shrink-0 bg-white/10 backdrop-blur-sm">
                    <lottie-player
                        :src="lottieUrl"
                        background="transparent"
                        speed="1"
                        style="width: 36px; height: 36px;"
                        loop
                        autoplay
                    ></lottie-player>
                </div>
                <div>
                    <span class="text-sm font-extrabold tracking-wider text-white font-headline block">SIGIE</span>
                    <span class="text-[8px] font-semibold text-slate-400 uppercase tracking-[0.15em] block">Gestión de Inspecciones</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 space-y-0.5 overflow-y-auto pr-1">
                <SidebarItem
                    icon="dashboard"
                    label="Dashboard"
                    to="/dashboard"
                />

                <div class="pt-3 pb-1">
                    <!-- Inspectores only -->
                    <SidebarItem
                        v-if="auth.role === 'inspector'"
                        icon="pin_drop"
                        label="Check-in"
                        to="/checkin"
                    />
                    <SidebarItem
                        v-if="auth.role === 'inspector'"
                        icon="pets"
                        label="Animales Sacrificados"
                        to="/sacrificio/nuevo"
                    />
                    <SidebarItem
                        v-if="auth.role === 'inspector'"
                        icon="add_circle"
                        label="Desviaciones"
                        to="/desviaciones/nuevo"
                    />
                    <SidebarItem
                        v-if="auth.role === 'inspector'"
                        icon="science"
                        label="Desviaciones Lab"
                        to="/desviaciones"
                    />
                    <SidebarItem
                        v-if="auth.role === 'inspector'"
                        icon="playlist_add_check"
                        label="Supervisiones"
                        to="/supervisiones/nuevo"
                    />
                    <SidebarItem
                        v-if="auth.role === 'inspector'"
                        icon="store"
                        label="Supervisiones Estab."
                        to="/supervisiones"
                    />
                    <SidebarItem
                        v-if="auth.role === 'inspector'"
                        icon="report"
                        label="No Conformidades"
                        to="/noconformidades/nuevo"
                    />
                    <SidebarItem
                        v-if="auth.role === 'inspector'"
                        icon="assignment_late"
                        label="No Conformidades Rastro"
                        to="/noconformidades"
                    />
                    <SidebarItem
                        v-if="auth.role === 'inspector'"
                        icon="calendar_month"
                        label="Programación Mensual"
                        to="/programacion"
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
                    <SidebarItem
                        v-if="auth.role === 'administrador'"
                        icon="science"
                        label="Desviaciones Lab"
                        to="/desviaciones"
                    />
                    <SidebarItem
                        v-if="auth.role === 'administrador'"
                        icon="store"
                        label="Supervisiones Estab."
                        to="/supervisiones"
                    />
                    <SidebarItem
                        v-if="auth.role === 'administrador'"
                        icon="assignment_late"
                        label="No Conformidades Rastro"
                        to="/noconformidades"
                    />
                    <SidebarItem
                        v-if="auth.role === 'administrador'"
                        icon="calendar_month"
                        label="Programación Mensual"
                        to="/programacion"
                    />
                    <SidebarItem
                        v-if="auth.role === 'administrador'"
                        icon="analytics"
                        label="Reportes"
                        to="/programacion/reportes"
                    />

                    <!-- Muestreos en Importaciones Section -->
                    <div class="pt-3 mt-2 border-t border-white/5">
                        <SidebarItem
                            icon="directions_boat"
                            label="Catálogo de Importaciones"
                            to="/importaciones"
                        />
                        <SidebarItem
                            icon="biotech"
                            label="Muestreos"
                            to="/muestreos"
                        />
                        <SidebarItem
                            v-if="auth.role === 'administrador'"
                            icon="analytics"
                            label="Metas y Cobertura"
                            to="/muestreos/reportes"
                        />
                    </div>
                </div>
            </nav>

            <!-- Sidebar Profile Footer -->
            <div class="mt-auto pt-4 border-t border-white/5">
                <div class="flex items-center gap-3 px-2 py-2">
                    <div class="w-8 h-8 rounded-full bg-blue-500/20 text-blue-300 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-base">person</span>
                    </div>
                    <div class="overflow-hidden flex-1">
                        <p class="text-xs font-semibold text-white truncate">{{ auth.user?.nombre || 'Usuario' }}</p>
                        <p class="text-[9px] text-slate-400 font-medium capitalize truncate">{{ auth.role }}</p>
                    </div>
                    <button @click="handleLogout" class="text-slate-400 hover:text-red-400 transition-colors" title="Cerrar Sesión">
                        <span class="material-symbols-outlined text-lg">logout</span>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col h-screen overflow-y-auto w-full transition-all duration-300">
            <!-- Top Navbar - Navy Solid -->
            <header class="header-navy sticky top-0 z-30 px-4 lg:px-8 py-3 flex justify-between items-center w-full flex-shrink-0">
                <div class="flex items-center gap-4">
                    <!-- Mobile Menu Button -->
                    <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="w-10 h-10 flex lg:hidden items-center justify-center rounded-xl hover:bg-white/10 transition-colors text-white">
                        <span class="material-symbols-outlined text-2xl">menu</span>
                    </button>
                    <div class="flex items-center gap-2.5">
                        <lottie-player
                            :src="lottieUrl"
                            background="transparent"
                            speed="1"
                            style="width: 24px; height: 24px;"
                            loop
                            autoplay
                        ></lottie-player>
                        <span class="text-sm font-bold text-white/90 font-headline hidden lg:block">Hola, {{ auth.user?.nombre || 'Usuario' }}</span>
                    </div>
                </div>
                
                <!-- Header Actions -->
                <div class="flex items-center gap-3">
                    <!-- Notifications Dropdown Button -->
                    <div class="relative notification-container">
                        <button @click="toggleNotifications" class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-white/10 transition-colors text-white/80 relative">
                            <span class="material-symbols-outlined text-xl">notifications</span>
                            <span v-if="unreadCount > 0" class="absolute top-0.5 right-0.5 w-4 h-4 bg-red-500 text-white text-[8px] font-bold rounded-full flex items-center justify-center border-2 border-[#0a192f]">
                                {{ unreadCount }}
                            </span>
                        </button>
                        
                        <!-- Notifications Popover -->
                        <div v-if="showNotifications" class="absolute right-0 top-12 w-80 bg-white border border-slate-200 rounded-2xl shadow-premium-lg py-2.5 z-50 animate-fade-in text-xs max-h-[400px] flex flex-col">
                            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between font-headline bg-slate-50/50">
                                <span class="font-extrabold text-slate-800">Notificaciones</span>
                                <button v-if="unreadCount > 0" @click="markAllNotificationsAsRead" class="text-[10px] font-bold text-blue-600 hover:underline">
                                    Marcar leídas
                                </button>
                            </div>
                            <div class="overflow-y-auto flex-1 divide-y divide-slate-100 max-h-[300px]">
                                <div v-if="notifications.length === 0" class="px-4 py-10 text-center text-slate-400">
                                    <span class="material-symbols-outlined text-3xl">notifications_off</span>
                                    <p class="mt-2 font-semibold">No tienes notificaciones</p>
                                </div>
                                <div 
                                    v-else 
                                    v-for="notif in notifications" 
                                    :key="notif.id" 
                                    :class="['px-4 py-3 hover:bg-slate-50/50 transition-colors text-left', {'bg-blue-50/30 border-l-2 border-blue-600': !notif.leido}]"
                                >
                                    <p class="text-slate-800 font-bold text-[11px]">{{ notif.titulo }}</p>
                                    <p class="text-slate-500 text-[10px] mt-0.5 leading-relaxed">{{ notif.mensaje }}</p>
                                    <p class="text-[9px] text-slate-400 font-semibold mt-1.5">{{ formatDateMini(notif.fecha_creacion) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Header Badge -->
                    <div class="flex items-center gap-2.5 select-none">
                        <span class="text-xs font-semibold text-white/70 hidden md:block">Mi Perfil</span>
                        <div class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/15 transition-colors text-white flex items-center justify-center font-bold text-xs uppercase border border-white/10">
                            {{ auth.user?.nombre?.substring(0, 2) || 'US' }}
                        </div>
                    </div>
                </div>
            </header>

            <!-- View Container -->
            <main class="w-full max-w-7xl mx-auto p-4 lg:p-8 flex-1 flex flex-col">
                <router-view></router-view>
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '../../stores/authStore.js';
import { useRouter, useRoute } from 'vue-router';
import SidebarItem from './SidebarItem.vue';
import api from '../../services/api.js';
import '@lottiefiles/lottie-player';

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

const getLottieUrl = () => {
    const path = window.location.pathname.toLowerCase();
    const isViteDev = window.location.port !== '' && window.location.port !== '80' && window.location.port !== '8080';
    if (isViteDev) {
        return '/login-animation.json';
    }
    const distIndex = path.indexOf('/frontend/dist');
    if (distIndex !== -1) {
        return window.location.pathname.substring(0, distIndex) + '/Frontend/dist/login-animation.json';
    }
    const sigieIndex = path.indexOf('/sigie');
    if (sigieIndex !== -1) {
        return window.location.pathname.substring(0, sigieIndex) + '/sigie/login-animation.json';
    }
    return '/login-animation.json';
};
const lottieUrl = getLottieUrl();

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const isMobileMenuOpen = ref(false);

// Notifications states
const showNotifications = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
let pollingInterval = null;

const toggleNotifications = () => {
    showNotifications.value = !showNotifications.value;
    if (showNotifications.value) {
        fetchNotifications();
    }
};

const closeNotifications = (e) => {
    if (!e.target.closest('.notification-container')) {
        showNotifications.value = false;
    }
};

const fetchNotifications = async () => {
    if (!auth.token) return;
    try {
        const response = await api.get('/notificaciones');
        if (response.data?.status === 'success') {
            notifications.value = response.data.data.list;
            unreadCount.value = response.data.data.unread;
        }
    } catch (e) {
        console.error('Error al cargar notificaciones', e);
    }
};

const markAllNotificationsAsRead = async () => {
    try {
        const response = await api.put('/notificaciones/leer-todas');
        if (response.data?.status === 'success') {
            unreadCount.value = 0;
            notifications.value.forEach(n => n.leido = 1);
        }
    } catch (e) {
        console.error('Error al marcar leídas', e);
    }
};

const formatDateMini = (dateTimeStr) => {
    if (!dateTimeStr) return '';
    const date = new Date(dateTimeStr.replace(' ', 'T'));
    return date.toLocaleString('es-ES', { 
        month: 'short', day: 'numeric', 
        hour: '2-digit', minute: '2-digit' 
    });
};

// Close menu when route changes
watch(() => route.path, () => {
    isMobileMenuOpen.value = false;
    showNotifications.value = false;
});

const handleLogout = () => {
    auth.logout();
    router.push('/login');
};

onMounted(() => {
    document.addEventListener('click', closeNotifications);
    fetchNotifications();
    pollingInterval = setInterval(fetchNotifications, 25000);
});

onUnmounted(() => {
    document.removeEventListener('click', closeNotifications);
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});
</script>
