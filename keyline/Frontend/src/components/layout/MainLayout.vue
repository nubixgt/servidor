<template>
    <div class="min-h-screen bg-gray-50 flex">
        <aside class="w-64 bg-white shadow-md flex flex-col">
            <div class="p-4 font-bold text-xl text-primary-600">KeylineGT</div>
            <nav class="mt-4 flex-1">
                <router-link
                    v-for="item in navItems"
                    :key="item.name"
                    :to="{ name: item.name }"
                    class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100"
                    active-class="bg-primary-500/10 text-primary-600 font-semibold"
                >
                    <span>{{ item.icon }}</span>
                    <span>{{ item.label }}</span>
                </router-link>
            </nav>
            <div class="p-4 border-t border-gray-100">
                <p class="text-sm font-semibold text-slate-700">{{ auth.user?.nombre }}</p>
                <p class="text-xs text-slate-400 mb-3">{{ roleLabel }}</p>
                <button
                    @click="handleLogout"
                    class="w-full text-sm px-3 py-2 rounded bg-gray-100 hover:bg-gray-200 text-slate-600"
                >
                    Cerrar sesión
                </button>
            </div>
        </aside>

        <main class="flex-1 p-8">
            <router-view></router-view>
        </main>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { ROLE_LABELS } from '../../constants/keyline';
import { confirmDialog, toastInfo } from '../../utils/alerts';

const auth = useAuthStore();
const router = useRouter();

const roleLabel = computed(() => ROLE_LABELS[auth.role] || auth.role);

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

const navItems = computed(() => MENUS[auth.role] || []);

async function handleLogout() {
    const ok = await confirmDialog('Se cerrará tu sesión actual.', { title: '¿Cerrar sesión?', confirmText: 'Cerrar sesión' });
    if (!ok) return;
    auth.logout();
    toastInfo('Sesión cerrada.');
    router.push({ name: 'Login' });
}
</script>
