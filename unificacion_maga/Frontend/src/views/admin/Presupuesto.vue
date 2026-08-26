<template>
    <div class="animate-fade-in w-full max-w-7xl mx-auto pb-12">
        <!-- Header Section -->
        <div class="mb-4 p-6 rounded-3xl bg-white/60 dark:bg-[#1E293B]/60 backdrop-blur-xl border border-white/80 dark:border-gray-800 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <h1 class="text-3xl font-brand font-black text-brand-dark dark:text-white tracking-wide">
                    Ejecución Presupuestaria
                </h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Control del Gasto y Finanzas Institucionales</p>
            </div>
            <div class="flex items-center gap-4 mt-4 md:mt-0">
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center font-bold text-sm overflow-hidden">
                    <div class="pl-3 pr-2 pointer-events-none">
                        <CalendarDaysIcon class="w-5 h-5 text-primary" />
                    </div>
                    <select v-model="selectedYear" class="bg-transparent border-none focus:ring-0 text-gray-700 dark:text-gray-200 py-2.5 pr-8 pl-0 cursor-pointer font-bold appearance-none outline-none">
                        <option :value="2026">Ejecución 2026</option>
                        <option :value="2025">Ejecución 2025</option>
                    </select>
                    <ChevronDownIcon class="w-4 h-4 text-gray-400 absolute right-3 pointer-events-none" />
                </div>
                <div class="flex items-center gap-4">

                    <div class="flex flex-col bg-gray-50 dark:bg-gray-900 px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-800 min-w-[100px] text-center">
                        <span class="text-[10px] text-gray-500 uppercase font-bold">Ejecución Actual</span>
                        <span class="text-sm font-black text-emerald-600">{{ ejecucionActual }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sub-Navigation Toolbar -->
        <div class="bg-brand-dark/5 dark:bg-gray-800/50 backdrop-blur-md rounded-2xl p-1.5 flex overflow-x-auto hide-scrollbar mb-8 mx-1 border border-brand-dark/10 dark:border-gray-700">
            <button v-for="tab in tabs" :key="tab.id"
                @click="activeTab = tab.id"
                :class="`
                    flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm whitespace-nowrap transition-all duration-300
                    ${activeTab === tab.id 
                        ? 'bg-white dark:bg-[#1E293B] text-brand-dark dark:text-white shadow-sm border-b-2 border-primary' 
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/50 dark:hover:bg-gray-700/50'}
                `">
                <component :is="tab.icon" class="w-4 h-4" :class="activeTab === tab.id ? 'text-primary' : 'opacity-70'" />
                {{ tab.name }}
            </button>
        </div>

        <!-- Dynamic Content Area -->
        <div class="transition-all duration-300">
            <DashboardPrincipalTab v-if="activeTab === 'dashboard'" />
            <UnidadesEjecutorasTab v-if="activeTab === 'unidades'" />
            <MinisteriosTab v-if="activeTab === 'ministerios'" />
            <AdministracionTab v-if="activeTab === 'administracion'" />
            <ImportarDatosTab v-if="activeTab === 'importar'" />
            
            <!-- Default placeholder for tabs not yet fully built -->
            <div v-if="!['dashboard', 'unidades', 'ministerios', 'administracion', 'importar'].includes(activeTab)" class="bg-white/50 dark:bg-gray-800/50 rounded-3xl p-12 text-center border border-dashed border-gray-300 dark:border-gray-700">
                 <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4 text-gray-400">
                     <component :is="tabs.find(t => t.id === activeTab)?.icon" class="w-8 h-8" />
                 </div>
                 <h2 class="text-xl font-bold text-gray-700 dark:text-gray-300 mb-2">Módulo en construcción</h2>
                 <p class="text-gray-500 dark:text-gray-400">Esta sección del dashboard financiero estará disponible próximamente.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, provide, watch, onMounted } from 'vue';
import { 
    CalendarDaysIcon,
    ChevronDownIcon,
    ChartBarSquareIcon,
    BuildingOfficeIcon,
    BuildingLibraryIcon,
    Cog6ToothIcon,
    ArrowDownTrayIcon,
    UsersIcon
} from '@heroicons/vue/24/outline';

import DashboardPrincipalTab from './presupuesto/DashboardPrincipalTab.vue';
import UnidadesEjecutorasTab from './presupuesto/UnidadesEjecutorasTab.vue';
import MinisteriosTab from './presupuesto/MinisteriosTab.vue';
import AdministracionTab from './presupuesto/AdministracionTab.vue';
import ImportarDatosTab from './presupuesto/ImportarDatosTab.vue';
import PresupuestoService from '@/services/presupuesto/PresupuestoService';

const activeTab = ref('dashboard');
const selectedYear = ref(2026);
const ejecucionActual = ref('0.00%');



// Provide the selected year to all child tabs
provide('selectedYear', selectedYear);

const loadGlobalStats = async () => {
    try {
        const resp = await PresupuestoService.getDashboard({ ejercicio: selectedYear.value });
        if (resp.status === 'success' && resp.data && resp.data.summary) {
            ejecucionActual.value = resp.data.summary.pct_ejec + '%';
        }
    } catch(e) {}
};

watch(selectedYear, loadGlobalStats);
onMounted(loadGlobalStats);

const tabs = [
    { id: 'dashboard', name: 'Dashboard Principal', icon: ChartBarSquareIcon },
    { id: 'unidades', name: 'Unidades Ejecutoras', icon: BuildingOfficeIcon },
    { id: 'ministerios', name: 'Ministerios', icon: BuildingLibraryIcon },
    { id: 'administracion', name: 'Administración', icon: Cog6ToothIcon },
    { id: 'importar', name: 'Importar Datos', icon: ArrowDownTrayIcon }
];

</script>

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
