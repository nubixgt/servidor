<template>
    <div class="animate-fade-in w-full max-w-6xl mx-auto pb-10">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h2 class="text-3xl font-brand font-black text-brand-dark dark:text-white tracking-widest drop-shadow-sm">
                    Seguridad Alimentaria
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Monitoreo de asistencia alimentaria, desnutrición y programas sociales</p>
            </div>
            
            <div class="flex gap-3">
                <button class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-4 py-2.5 rounded-xl font-bold hover:bg-gray-200 dark:hover:bg-gray-700 transition-all flex items-center gap-2">
                    <ChartBarIcon class="w-5 h-5" /> Estadísticas VISAN
                </button>
                <button class="bg-primary hover:bg-primary-dark text-white px-5 py-2.5 rounded-xl font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                    <PlusIcon class="w-5 h-5" /> Nueva Entrega
                </button>
            </div>
        </div>

        <!-- Metric Cards for VISAN -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-[#1E293B] p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-orange-100 dark:bg-orange-900/30 text-orange-600 flex items-center justify-center shrink-0">
                    <ArchiveBoxIcon class="w-6 h-6" />
                </div>
                <div>
                    <h4 class="text-xs text-gray-500 font-bold uppercase tracking-wider">Total Raciones Entregadas</h4>
                    <p class="text-2xl font-black text-brand-dark dark:text-white">{{ formatNumber(stats.total_raciones) }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-[#1E293B] p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center shrink-0">
                    <UsersIcon class="w-6 h-6" />
                </div>
                <div>
                    <h4 class="text-xs text-gray-500 font-bold uppercase tracking-wider">Familias Beneficiadas</h4>
                    <p class="text-2xl font-black text-brand-dark dark:text-white">{{ formatNumber(stats.familias_beneficiadas) }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-[#1E293B] p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center shrink-0">
                    <MapPinIcon class="w-6 h-6" />
                </div>
                <div>
                    <h4 class="text-xs text-gray-500 font-bold uppercase tracking-wider">Cobertura Nacional</h4>
                    <p class="text-2xl font-black text-brand-dark dark:text-white">{{ stats.cobertura_deptos }} <span class="text-sm font-normal text-gray-400">Deptos</span></p>
                </div>
            </div>
            <div class="bg-white dark:bg-[#1E293B] p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 flex items-center gap-4 border-l-4 border-l-red-500">
                <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/30 text-red-600 flex items-center justify-center shrink-0">
                    <HeartIcon class="w-6 h-6" />
                </div>
                <div>
                    <h4 class="text-xs text-gray-500 font-bold uppercase tracking-wider">Casos Riesgo Nutricional</h4>
                    <p class="text-2xl font-black text-red-600">{{ formatNumber(stats.casos_riesgo) }}</p>
                </div>
            </div>
        </div>
        
        <!-- Filters & Search Bar -->
        <div class="bg-white dark:bg-[#1E293B] p-4 rounded-2xl mb-6 shadow-sm border border-gray-100 dark:border-gray-800 flex flex-col md:flex-row gap-4 items-center justify-between">
            <div class="relative w-full md:w-96">
                <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                <input 
                    v-model="searchQuery" 
                    type="text" 
                    placeholder="Buscar por DPI, jefe de familia o comunidad..." 
                    class="w-full pl-10 pr-4 py-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-800/50 text-brand-dark dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all text-sm"
                />
            </div>
            
            <div class="flex gap-2 w-full md:w-auto">
                <select v-model="filterPrograma" class="border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-sm px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50 flex-1 md:flex-none">
                    <option value="">Todos los Programas</option>
                    <option value="ASISTENCIA_ALIMENTARIA">Asistencia Alimentaria</option>
                    <option value="ESTIPENDIO">Estipendio Agrícola</option>
                    <option value="RESERVAS_ESTRATEGICAS">Reservas Estratégicas</option>
                </select>
            </div>
        </div>

        <!-- Table View -->
        <div class="bg-white dark:bg-[#1E293B] rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-gray-800/20 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800">
                            <th class="p-4 font-bold">Solicitud / Fecha</th>
                            <th class="p-4 font-bold">Jefe de Familia (DPI)</th>
                            <th class="p-4 font-bold">Comunidad</th>
                            <th class="p-4 font-bold">Programa</th>
                            <th class="p-4 font-bold">Estado Entrega</th>
                            <th class="p-4 font-bold text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr v-if="loading" class="animate-pulse">
                            <td colspan="6" class="p-8 text-center text-gray-400">Cargando registros de VISAN...</td>
                        </tr>
                        <tr v-else-if="filteredDeliveries.length === 0">
                            <td colspan="6" class="p-12 text-center text-gray-400">
                                <div class="flex flex-col items-center">
                                    <ArchiveBoxXMarkIcon class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" />
                                    <p>No se encontraron registros de entrega.</p>
                                </div>
                            </td>
                        </tr>
                        <tr v-else v-for="deliv in filteredDeliveries" :key="deliv.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group">
                            <td class="p-4 text-sm text-gray-600 dark:text-gray-300">
                                <div class="font-bold text-brand-dark dark:text-gray-200">{{ deliv.id_solicitud }}</div>
                                <div class="text-xs text-gray-500">{{ deliv.fecha }}</div>
                            </td>
                            <td class="p-4">
                                <div class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ deliv.beneficiario }}</div>
                                <div class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                                    <IdentificationIcon class="w-3 h-3" /> {{ deliv.dpi }}
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="font-medium text-sm text-gray-700 dark:text-gray-200 truncate max-w-[150px]">{{ deliv.comunidad }}</div>
                                <div class="text-xs text-gray-500">{{ deliv.municipio }}, {{ deliv.departamento }}</div>
                            </td>
                            <td class="p-4">
                                <span v-if="deliv.programa" :class="`px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider ${getProgramaClass(deliv.programa)}`">
                                    {{ deliv.programa.replace('_', ' ') }}
                                </span>
                                <span v-else class="text-xs text-gray-400">Sin programa</span>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <div :class="`w-2 h-2 rounded-full ${deliv.estado === 'ENTREGADO' ? 'bg-green-500' : deliv.estado === 'EVALUACION' ? 'bg-amber-500' : 'bg-blue-500'}`"></div>
                                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ deliv.estado }}</span>
                                </div>
                            </td>
                            <td class="p-4 text-right">
                                <button class="p-1.5 text-gray-400 hover:text-primary transition-colors rounded-lg hover:bg-primary/10 mr-1" title="Ver Expediente">
                                    <DocumentTextIcon class="w-4 h-4" />
                                </button>
                                <button class="p-1.5 text-gray-400 hover:text-green-500 transition-colors rounded-lg hover:bg-green-500/10" title="Aprobar Entrega" v-if="deliv.estado !== 'ENTREGADO'">
                                    <CheckCircleIcon class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Footer -->
            <div class="p-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800 flex flex-col md:flex-row justify-between items-center gap-4 text-sm">
                <span class="text-gray-500">
                    Mostrando <span class="font-bold text-gray-700 dark:text-gray-300">{{ filteredDeliveries.length }}</span> registros
                </span>
                
                <div class="flex gap-1">
                    <button class="px-3 py-1 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 hover:bg-white dark:hover:bg-gray-800 disabled:opacity-50">Anterior</button>
                    <button class="px-3 py-1 rounded-lg bg-primary text-white font-bold shadow-sm">1</button>
                    <button class="px-3 py-1 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 hover:bg-white dark:hover:bg-gray-800">2</button>
                    <button class="px-3 py-1 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 hover:bg-white dark:hover:bg-gray-800 disabled:opacity-50">Siguiente</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { 
    PlusIcon, 
    MagnifyingGlassIcon, 
    ChartBarIcon,
    ArchiveBoxIcon,
    UsersIcon,
    MapPinIcon,
    HeartIcon,
    ArchiveBoxXMarkIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    IdentificationIcon
} from '@heroicons/vue/24/outline';

import VisanService from '@/services/visan/VisanService';

const loading = ref(true);
const searchQuery = ref('');
const filterPrograma = ref('');
const deliveries = ref([]);

const stats = ref({
    total_raciones: 0,
    familias_beneficiadas: 0,
    cobertura_deptos: 0,
    casos_riesgo: 0
});

const loadData = async () => {
    loading.value = true;
    try {
        const [entregasResp, dashboardResp] = await Promise.all([
            VisanService.getEntregas({
                search: searchQuery.value,
                programa: filterPrograma.value
            }),
            VisanService.getDashboard()
        ]);
        
        if (entregasResp.status === 'success') {
            deliveries.value = entregasResp.data || [];
        }
        
        if (dashboardResp && !dashboardResp.status) { // VISAN dashboard returns raw data based on controller
             stats.value = {
                total_raciones: dashboardResp.total_raciones || 0,
                familias_beneficiadas: dashboardResp.familias_beneficiadas || 0,
                cobertura_deptos: dashboardResp.cobertura_deptos || 0,
                casos_riesgo: dashboardResp.casos_riesgo || 0
             };
        }
    } catch (error) {
        console.error('Error al cargar datos de seguridad alimentaria:', error);
    } finally {
        loading.value = false;
    }
};

const filteredDeliveries = computed(() => {
    return deliveries.value;
});

const getProgramaClass = (prog) => {
    switch(prog) {
        case 'ASISTENCIA_ALIMENTARIA': return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400';
        case 'ESTIPENDIO': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
        case 'RESERVAS_ESTRATEGICAS': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        default: return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400';
    }
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('en-US').format(num || 0);
};

onMounted(() => {
    loadData();
});
</script>
