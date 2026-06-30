<template>
    <div class="bg-surface text-on-surface min-h-screen font-body flex transition-colors duration-500">
        <!-- Mobile Sidebar Overlay -->
        <div v-if="isMobileMenuOpen" @click="isMobileMenuOpen = false" class="fixed inset-0 bg-black/60 z-40 lg:hidden backdrop-blur-sm transition-opacity"></div>
        
        <!-- Sidebar -->
        <aside :class="['w-72 fixed left-0 top-0 h-full bg-gradient-to-b from-[#0a192f] to-[#0f224b] flex flex-col p-6 z-50 border-r border-slate-900 transition-transform duration-300 lg:translate-x-0', {'translate-x-0': isMobileMenuOpen, '-translate-x-full': !isMobileMenuOpen}]">
            <!-- Brand Logo Header -->
            <div class="mb-8 px-2 flex items-center gap-3">
                <div class="w-11 h-11 overflow-hidden rounded-xl border border-slate-700 flex items-center justify-center flex-shrink-0 bg-white shadow-md">
                    <lottie-player
                        :src="lottieUrl"
                        background="transparent"
                        speed="1"
                        style="width: 40px; height: 40px;"
                        loop
                        autoplay
                    ></lottie-player>
                </div>
                <div>
                    <span class="text-lg font-extrabold tracking-wider text-white font-headline block">SIGIE</span>
                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest block -mt-1">Gestión Inspecciones</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 space-y-1 overflow-y-auto pr-1">
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-500 px-4 mb-2 mt-4">General</p>
                <SidebarItem
                    icon="dashboard"
                    label="Panel Principal"
                    to="/dashboard"
                />

                <div class="pt-4 pb-2">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-500 px-4 mb-2 mt-2">Operaciones</p>
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
                    <SidebarItem
                        v-if="auth.role === 'inspector'"
                        icon="add_circle"
                        label="Registrar Desviación"
                        to="/desviaciones/nuevo"
                    />
                    <SidebarItem
                        v-if="auth.role === 'inspector'"
                        icon="science"
                        label="Desviaciones Lab"
                        to="/desviaciones"
                    />
                    <!-- Supervisiones & No Conformidades (Inspector) -->
                    <SidebarItem
                        v-if="auth.role === 'inspector'"
                        icon="playlist_add_check"
                        label="Registrar Supervisión"
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
                        label="Registrar No Conformidad"
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
                        label="Mi Programación"
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
                    <!-- Supervisiones & No Conformidades (Administrador) -->
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
                        label="Reportes de Actividades"
                        to="/programacion/reportes"
                    />

                    <!-- Muestreos en Importaciones Section -->
                    <div class="pt-4 pb-2 border-t border-slate-800/80 mt-3">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-500 px-4 mb-2">Importaciones</p>
                        
                        <!-- Catálogo e Historial de Importaciones (Admin & Inspector) -->
                        <SidebarItem
                            icon="directions_boat"
                            label="Catálogo de Importaciones"
                            to="/importaciones"
                        />
                        
                        <!-- Muestreos (Admin & Inspector) -->
                        <SidebarItem
                            icon="biotech"
                            label="Muestreos en Importaciones"
                            to="/muestreos"
                        />

                        <!-- Reportes y Metas (Admin only) -->
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
            <div class="mt-auto pt-6 border-t border-slate-800/80">
                <div class="bg-slate-900/50 p-3.5 rounded-xl mb-4 flex items-center gap-3 border border-slate-800/40 shadow-inner">
                    <div class="w-9 h-9 rounded-full bg-blue-600 text-white flex items-center justify-center font-extrabold text-xs uppercase shadow-glow-blue">
                        {{ auth.user?.nombre?.substring(0, 2) || 'US' }}
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-bold text-white truncate">{{ auth.user?.nombre || 'Usuario' }}</p>
                        <p class="text-[9px] text-slate-400 font-medium capitalize truncate">{{ auth.role }}</p>
                    </div>
                </div>
                <button @click="handleLogout" class="w-full flex items-center justify-center gap-2 px-4 py-3 text-red-400 hover:bg-red-950/20 hover:text-red-300 rounded-xl transition-all duration-200 text-xs font-bold border border-transparent hover:border-red-950/40">
                    <span class="material-symbols-outlined text-base">logout</span> Cerrar Sesión
                </button>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-h-screen lg:ml-72 w-full transition-all duration-300">
            <!-- Top Navbar -->
            <header class="glass-header sticky top-0 z-30 px-4 lg:px-10 py-3.5 flex justify-between items-center shadow-premium">
                <div class="flex items-center gap-4 lg:gap-8">
                    <!-- Mobile Menu Button -->
                    <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="w-10 h-10 flex lg:hidden items-center justify-center rounded-xl hover:bg-slate-100 transition-colors text-slate-600">
                        <span class="material-symbols-outlined text-2xl">menu</span>
                    </button>
                    <div class="flex items-center gap-2">
                        <lottie-player
                            :src="lottieUrl"
                            background="transparent"
                            speed="1"
                            style="width: 28px; height: 28px;"
                            loop
                            autoplay
                        ></lottie-player>
                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest font-headline hidden lg:block">Sistema de Gestión de Inspecciones (SIGIE)</span>
                    </div>
                </div>
                
                <!-- Header Actions -->
                <div class="flex items-center gap-4">
                    <div class="relative group hidden md:block">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
                        <input type="text" placeholder="Buscar en el sistema..." class="bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2 text-xs w-64 focus:border-blue-600 focus:bg-white transition-all outline-none text-slate-800" />
                    </div>
                    
                    <!-- Notifications Dropdown Button -->
                    <div class="relative notification-container">
                        <button @click="toggleNotifications" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-slate-100 transition-colors text-slate-600 relative">
                            <span class="material-symbols-outlined text-2xl">notifications</span>
                            <span v-if="unreadCount > 0" class="absolute top-1.5 right-1.5 w-5 h-5 bg-red-600 text-white text-[9px] font-black rounded-full flex items-center justify-center border-2 border-white shadow-sm">
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

                    <div class="h-6 w-[1px] bg-slate-200 mx-1"></div>
                    
                    <!-- Profile Header Badge -->
                    <div class="flex items-center gap-3 select-none">
                        <div class="text-right hidden md:block">
                            <p class="text-xs font-bold text-slate-800 truncate max-w-[150px]">{{ auth.user?.nombre }}</p>
                            <p class="text-[9px] text-slate-400 font-semibold uppercase tracking-wider -mt-0.5">En Línea</p>
                        </div>
                        <div class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 transition-colors text-slate-700 flex items-center justify-center font-extrabold text-xs border border-slate-200 uppercase">
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
    // Poll every 25 seconds for new notifications
    pollingInterval = setInterval(fetchNotifications, 25000);
});

onUnmounted(() => {
    document.removeEventListener('click', closeNotifications);
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});
</script>
