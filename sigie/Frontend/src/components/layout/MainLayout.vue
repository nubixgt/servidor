<template>
    <div class="bg-surface text-on-surface min-h-screen font-body flex transition-colors duration-500">
        <!-- Mobile Sidebar Overlay -->
        <div v-if="isMobileMenuOpen" @click="isMobileMenuOpen = false" class="fixed inset-0 bg-black/50 z-40 lg:hidden backdrop-blur-sm transition-opacity"></div>
        
        <!-- Sidebar -->
        <aside :class="['w-72 fixed left-0 top-0 h-full bg-white flex flex-col p-6 z-50 border-r border-surface-container transition-transform duration-300 lg:translate-x-0', {'translate-x-0': isMobileMenuOpen, '-translate-x-full': !isMobileMenuOpen}]">
            <div class="mb-8 px-2 flex items-center gap-3">
                <div class="w-10 h-10 overflow-hidden rounded border border-surface-container flex items-center justify-center flex-shrink-0 bg-white p-1">
                    <img :src="logoUrl" alt="SIGIE Logo" class="max-w-full max-h-full object-contain" />
                </div>
                <span class="text-xl font-bold tracking-wider text-primary font-headline">SIGIE</span>
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
                    <div class="pt-4 pb-2 border-t border-slate-100 mt-2">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-outline-variant px-4 mb-2">Importaciones</p>
                        
                        <!-- Catálogo e Historial de Importaciones (Admin & Inspector) -->
                        <SidebarItem
                            icon="ship"
                            label="Catálogo e Importaciones"
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

            <div class="mt-auto pt-6 border-t border-surface-container">
                <div class="bg-surface-container-low p-3.5 rounded-md mb-4 flex items-center gap-3 border border-surface-container">
                    <div class="w-9 h-9 rounded bg-primary text-on-primary flex items-center justify-center font-bold text-sm uppercase">
                        {{ auth.user?.nombre?.substring(0, 2) || 'US' }}
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-bold text-on-surface truncate">{{ auth.user?.nombre || 'Usuario' }}</p>
                        <p class="text-[10px] text-on-surface-variant capitalize truncate">{{ auth.role }}</p>
                    </div>
                </div>
                <button @click="handleLogout" class="w-full flex items-center gap-3 px-4 py-2.5 text-red-600 hover:bg-red-50 hover:text-red-700 rounded-md transition-all text-xs font-bold border border-transparent hover:border-red-200">
                    <span class="material-symbols-outlined text-lg">logout</span> Cerrar Sesión
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-h-screen lg:ml-72 w-full transition-all duration-300">
            <!-- Top Navbar -->
            <header class="sticky top-0 z-30 bg-white px-4 lg:px-10 py-3.5 flex justify-between items-center border-b border-surface-container shadow-sm">
                <div class="flex items-center gap-4 lg:gap-8">
                    <!-- Mobile Menu Button -->
                    <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="w-10 h-10 flex lg:hidden items-center justify-center rounded hover:bg-surface-container-low transition-all duration-300 text-on-surface-variant">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div class="flex items-center gap-2">
                        <img :src="logoUrl" alt="SIGIE Logo" class="w-8 h-8 object-contain" />
                        <span class="text-xs font-bold text-on-surface-variant uppercase tracking-widest font-headline hidden lg:block">Sistema de Gestión de Inspecciones (SIGIE)</span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="relative group hidden md:block">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline scale-90">search</span>
                        <input type="text" placeholder="Buscar..." class="bg-white border border-surface-container-high rounded-md pl-10 pr-4 py-1.5 text-xs w-64 focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none text-on-surface" />
                    </div>
                    
                    <!-- Notifications Dropdown Button -->
                    <div class="relative notification-container">
                        <button @click="toggleNotifications" class="w-10 h-10 flex items-center justify-center rounded hover:bg-surface-container-low transition-all duration-300 text-on-surface-variant relative">
                            <span class="material-symbols-outlined">notifications</span>
                            <span v-if="unreadCount > 0" class="absolute top-1.5 right-1.5 w-4.5 h-4.5 bg-red-600 text-white text-[8px] font-black rounded-full flex items-center justify-center animate-pulse border border-white">
                                {{ unreadCount }}
                            </span>
                        </button>
                        
                        <!-- Notifications Popover -->
                        <div v-if="showNotifications" class="absolute right-0 top-12 w-80 bg-white border border-slate-200 rounded-lg shadow-xl py-2 z-50 animate-fade-in text-xs max-h-[400px] flex flex-col">
                            <div class="px-4 py-2.5 border-b border-slate-100 flex items-center justify-between font-headline bg-slate-50/50">
                                <span class="font-black text-slate-800">Notificaciones</span>
                                <button v-if="unreadCount > 0" @click="markAllNotificationsAsRead" class="text-[10px] font-bold text-primary hover:underline">
                                    Marcar todo como leído
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
                                    :class="['px-4 py-3 hover:bg-slate-50/50 transition-colors text-left', {'bg-blue-50/30 border-l-2 border-primary': !notif.leido}]"
                                >
                                    <p class="text-slate-800 font-bold text-[11px]">{{ notif.titulo }}</p>
                                    <p class="text-slate-500 text-[10px] mt-0.5 leading-normal">{{ notif.mensaje }}</p>
                                    <p class="text-[9px] text-slate-400 font-mono mt-1.5">{{ formatDateMini(notif.fecha_creacion) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="h-8 w-[1px] bg-surface-container-high mx-2"></div>
                    <div class="flex items-center gap-3">
                        <div class="text-right hidden md:block">
                            <p class="text-xs font-bold text-on-surface capitalize">{{ auth.role }}</p>
                            <p class="text-[9px] text-on-surface-variant uppercase tracking-widest">En Línea</p>
                        </div>
                        <div class="w-9 h-9 rounded bg-slate-100 text-slate-800 flex items-center justify-center font-bold text-xs border border-surface-container-high uppercase">
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

