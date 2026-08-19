<template>
    <div class="app-shell" :class="{ 'sidebar-collapsed': collapsed }">
        <aside class="sidebar glass">
            <div class="sidebar-top">
                <div class="brand-mark small">
                    <svg viewBox="0 0 48 48" width="28" height="28">
                        <defs><linearGradient id="gradSide" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#7be495"/><stop offset="1" stop-color="#0f8a45"/></linearGradient></defs>
                        <path d="M6 30 Q18 10 42 14 Q26 20 22 40 Q16 26 6 30Z" fill="url(#gradSide)"/>
                        <path d="M44 8 Q34 6 28 16 Q36 16 40 24 Q46 16 44 8Z" fill="#32b9e8" opacity=".9"/>
                    </svg>
                    <span>Keyline<b>GT</b></span>
                </div>
                <button class="sidebar-collapse-btn" title="Contraer / expandir menú" @click="collapsed = !collapsed">{{ collapsed ? '»' : '«' }}</button>
            </div>

            <nav class="nav-menu">
                <router-link
                    v-for="item in navItems"
                    :key="item.name"
                    :to="{ name: item.name }"
                    class="nav-item"
                    active-class="active"
                >
                    <span class="ic">{{ item.icon }}</span>
                    <span class="label">{{ item.label }}</span>
                </router-link>
            </nav>

            <div class="sidebar-user">
                <div class="user-avatar">{{ initials }}</div>
                <div class="user-meta">
                    <strong>{{ auth.user?.nombre }}</strong>
                    <span>{{ roleLabel }}</span>
                </div>
            </div>
            <button class="btn btn-ghost btn-block" @click="handleLogout"><span class="label">Cerrar sesión</span></button>
        </aside>

        <main class="main-area">
            <header class="topbar glass">
                <div class="topbar-left">
                    <div class="topbar-title">
                        <h2>{{ pageTitle }}</h2>
                        <p>{{ pageSubtitle }}</p>
                    </div>
                </div>
                <div class="topbar-actions">
                    <span class="country-chip">🇬🇹 Guatemala</span>
                </div>
            </header>

            <div class="view-container">
                <router-view></router-view>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { ROLE_LABELS } from '../../constants/keyline';
import { confirmDialog, toastInfo } from '../../utils/alerts';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();
const collapsed = ref(false);

const roleLabel = computed(() => ROLE_LABELS[auth.role] || auth.role);
const initials = computed(() => {
    const name = auth.user?.nombre || '';
    const parts = name.trim().split(/\s+/);
    return ((parts[0]?.[0] || '') + (parts[1]?.[0] || '')).toUpperCase() || '--';
});

const MENUS = {
    tecnico: [
        { name: 'ParcelaNueva', label: 'Registrar parcela', icon: '📝' },
        { name: 'MisParcelas', label: 'Mis parcelas', icon: '🗂️' },
        { name: 'Variables', label: 'Variables técnicas', icon: '💧' },
        { name: 'Configuracion', label: 'Configuración', icon: '⚙️' },
    ],
    supervisor: [
        { name: 'Dashboard', label: 'Panel ejecutivo', icon: '📊' },
        { name: 'ParcelasList', label: 'Parcelas de mi región', icon: '🗺️' },
        { name: 'ParcelaNueva', label: 'Registrar parcela', icon: '📝' },
        { name: 'Bioindicadores', label: 'Bioindicadores', icon: '🌱' },
        { name: 'Reportes', label: 'Reportes y análisis', icon: '📈' },
        { name: 'Variables', label: 'Variables técnicas', icon: '💧' },
        { name: 'Usuarios', label: 'Equipo técnico', icon: '👥' },
        { name: 'Configuracion', label: 'Configuración', icon: '⚙️' },
    ],
    administrador: [
        { name: 'Dashboard', label: 'Panel ejecutivo', icon: '📊' },
        { name: 'ParcelasList', label: 'Todas las parcelas', icon: '🗺️' },
        { name: 'ParcelaNueva', label: 'Registrar parcela', icon: '📝' },
        { name: 'Bioindicadores', label: 'Bioindicadores', icon: '🌱' },
        { name: 'Reportes', label: 'Reportes y análisis', icon: '📈' },
        { name: 'Variables', label: 'Variables técnicas', icon: '💧' },
        { name: 'Usuarios', label: 'Usuarios y equipo', icon: '👥' },
        { name: 'Configuracion', label: 'Configuración', icon: '⚙️' },
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
