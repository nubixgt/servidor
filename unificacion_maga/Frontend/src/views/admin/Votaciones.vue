<template>
    <div class="animate-fade-in w-full max-w-7xl mx-auto pb-12">
        <!-- Header -->
        <div class="mb-6 p-6 rounded-3xl bg-white dark:bg-[#1E293B] border border-gray-100 dark:border-gray-800 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-2xl flex items-center justify-center shadow-inner">
                    <BuildingLibraryIcon class="w-8 h-8" />
                </div>
                <div>
                    <h1 class="text-2xl font-brand font-black text-brand-dark dark:text-white tracking-wide">
                        Congreso de la República
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-0.5 font-medium">Sistema de Votaciones</p>
                </div>
            </div>
            <div class="flex items-center gap-4 mt-4 md:mt-0">
                <div class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                    <UserCircleIcon class="w-5 h-5 text-gray-400" />
                    <div class="flex flex-col text-right">
                        <span class="text-[10px] font-medium text-gray-400 uppercase tracking-wide">Bienvenido/a</span>
                        <span>Administrador del Sistema</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs Container -->
        <div class="bg-white dark:bg-[#1E293B] p-2 rounded-2xl md:rounded-full border border-gray-100 dark:border-gray-800 shadow-sm mb-6 flex flex-col md:flex-row gap-2 overflow-x-auto hide-scrollbar">
            <button v-for="tab in tabs" :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                    'px-6 py-2.5 rounded-full text-sm font-bold transition-all whitespace-nowrap flex-shrink-0 flex items-center justify-center gap-2',
                    activeTab === tab.id 
                        ? 'bg-blue-600 text-white shadow-md' 
                        : 'text-gray-600 dark:text-gray-400 hover:bg-white/50 dark:hover:bg-gray-800/50 hover:text-brand-dark dark:hover:text-gray-200'
                ]">
                <component :is="tab.icon" class="w-4 h-4" />
                {{ tab.name }}
            </button>
        </div>

        <!-- Tab Content Area -->
        <div class="transition-all duration-300">
            <DashboardTab v-if="activeTab === 'dashboard'" />
            <EventosTab v-if="activeTab === 'eventos'" />
            <CongresistasTab v-if="activeTab === 'congresistas'" />
            <BloquesTab v-if="activeTab === 'bloques'" />
            <EstadisticasTab v-if="activeTab === 'estadisticas'" />
            <PdfTab v-if="activeTab === 'pdf'" />
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { 
    BuildingLibraryIcon, 
    UserCircleIcon,
    HomeIcon,
    CalendarIcon,
    UsersIcon,
    RectangleGroupIcon,
    ChartBarIcon,
    ArrowUpTrayIcon
} from '@heroicons/vue/24/outline';

// Import Tab Components
import DashboardTab from './votaciones/DashboardTab.vue';
import EventosTab from './votaciones/EventosTab.vue';
import CongresistasTab from './votaciones/CongresistasTab.vue';
import BloquesTab from './votaciones/BloquesTab.vue';
import EstadisticasTab from './votaciones/EstadisticasTab.vue';
import PdfTab from './votaciones/PdfTab.vue';

const route = useRoute();

// State for active tab, initialize from query if exists
const activeTab = ref(route.query.tab || 'dashboard');

// Watch for route changes to update active tab if we navigate to same route with different query
watch(
    () => route.query.tab,
    (newTab) => {
        if (newTab) activeTab.value = newTab;
    }
);

// Tab Configuration
const tabs = [
    { id: 'dashboard', name: 'Dashboard', icon: HomeIcon },
    { id: 'eventos', name: 'Eventos', icon: CalendarIcon },
    { id: 'congresistas', name: 'Congresistas', icon: UsersIcon },
    { id: 'bloques', name: 'Bloques', icon: RectangleGroupIcon },
    { id: 'estadisticas', name: 'Estadísticas', icon: ChartBarIcon },
    { id: 'pdf', name: 'Cargar PDF', icon: ArrowUpTrayIcon }
];
</script>
