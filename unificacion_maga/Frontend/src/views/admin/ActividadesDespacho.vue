<template>
    <div class="animate-fade-in w-full max-w-7xl mx-auto pb-12">
        
        <!-- Header con Tabs integrados -->
        <div class="mb-6 bg-white/60 dark:bg-[#1E293B]/60 backdrop-blur-xl border border-white/80 dark:border-gray-800 shadow-sm rounded-3xl overflow-hidden">
            <div class="p-6 pb-0 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
                <div>
                    <h1 class="text-2xl font-brand font-black text-brand-dark dark:text-white tracking-wide">
                        Actividades del Despacho
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Gestión y visualización de métricas</p>
                </div>
                <button @click="$router.push('/admin/dashboard')" class="bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-bold hover:bg-gray-50 dark:hover:bg-gray-700 transition self-start md:self-auto mt-2 md:mt-0">
                    Volver al Inicio
                </button>
            </div>

            <!-- Tabs Navigation -->
            <div class="px-6 flex items-center gap-6 border-b border-gray-200 dark:border-gray-800 mt-4">
                <button 
                    @click="activeTab = 'dashboard'" 
                    class="py-3 px-1 border-b-2 transition-colors duration-200 font-bold text-sm"
                    :class="activeTab === 'dashboard' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                >
                    Dashboard Ministra
                </button>
                <button 
                    @click="activeTab = 'gestion'" 
                    class="py-3 px-1 border-b-2 transition-colors duration-200 font-bold text-sm"
                    :class="activeTab === 'gestion' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                >
                    Gestión de Actividades
                </button>
            </div>
        </div>

        <!-- Renderizado dinámico de la Pestaña -->
        <KeepAlive>
            <component :is="currentTabComponent" />
        </KeepAlive>
        
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import DashboardTab from './despacho/DashboardTab.vue';
import GestionTab from './despacho/GestionTab.vue';

const activeTab = ref('dashboard');

const currentTabComponent = computed(() => {
    switch (activeTab.value) {
        case 'dashboard': return DashboardTab;
        case 'gestion': return GestionTab;
        default: return DashboardTab;
    }
});
</script>
