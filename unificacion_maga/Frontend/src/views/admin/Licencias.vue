<template>
    <div class="animate-fade-in w-full max-w-6xl mx-auto pb-10">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h2 class="text-3xl font-brand font-black text-brand-dark dark:text-white tracking-widest drop-shadow-sm">
                    Licencias y Permisos
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sistemas de gestión de certificaciones, importaciones y exportaciones</p>
            </div>
            
            <div class="flex gap-2 flex-wrap w-full sm:w-auto">
                <button class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-3 py-2 rounded-xl font-bold hover:bg-gray-200 dark:hover:bg-gray-700 transition-all flex items-center gap-2 text-sm">
                    <DocumentArrowDownIcon class="w-4 h-4" /> <span class="hidden sm:inline">Exportar</span>
                </button>
                <button class="bg-primary hover:bg-primary-dark text-white px-3 py-2 rounded-xl font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2 text-sm">
                    <PlusIcon class="w-4 h-4" /> <span class="hidden sm:inline">Emitir</span> Licencia
                </button>
            </div>
        </div>
        
        <!-- Filters & Search Bar -->
        <div class="bg-white dark:bg-[#1E293B] p-4 rounded-2xl mb-6 shadow-sm border border-gray-100 dark:border-gray-800 flex flex-col md:flex-row gap-4 items-center justify-between">
            <div class="relative w-full md:w-96">
                <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                <input 
                    v-model="searchQuery" 
                    type="text" 
                    placeholder="Buscar por titular, NIT o nro. de documento..." 
                    class="w-full pl-10 pr-4 py-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-800/50 text-brand-dark dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all text-sm"
                />
            </div>
            
            <div class="flex gap-2 w-full md:w-auto">
                <select v-model="filterStatus" class="border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-sm px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50 flex-1 md:flex-none">
                    <option value="">Todos los Estados</option>
                    <option value="VIGENTE">Vigente</option>
                    <option value="EN TRAMITE">En Trámite</option>
                    <option value="POR VENCER">Por Vencer</option>
                    <option value="VENCIDA">Vencida</option>
                </select>
                <button class="p-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <AdjustmentsHorizontalIcon class="w-5 h-5" />
                </button>
            </div>
        </div>

        <!-- Card View (Mobile) -->
        <div class="block md:hidden space-y-3">
            <div v-if="loading" class="flex justify-center py-12">
                <div class="w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
            </div>
            <div v-else-if="filteredLicenses.length === 0" class="p-10 text-center text-gray-400">
                <DocumentMagnifyingGlassIcon class="w-10 h-10 mx-auto mb-3 text-gray-300" />
                <p>No se encontraron licencias.</p>
            </div>
            <div v-else v-for="prod in filteredLicenses" :key="prod.id"
                class="bg-white dark:bg-[#1E293B] rounded-2xl p-4 border border-gray-100 dark:border-gray-800 shadow-sm"
            >
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="font-bold text-sm text-brand-dark dark:text-gray-200">{{ prod.titular }}</div>
                        <div class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                            <IdentificationIcon class="w-3 h-3" /> {{ prod.identificacion }}
                        </div>
                    </div>
                    <span :class="`px-2 py-0.5 rounded text-[10px] font-bold uppercase flex-shrink-0 ${getEstadoClass(prod.estado)}`">{{ prod.estado }}</span>
                </div>
                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 grid grid-cols-2 gap-2 text-xs text-gray-500">
                    <div>
                        <div class="font-bold text-gray-700 dark:text-gray-300">{{ prod.tipo }}</div>
                        <div class="text-gray-500">{{ prod.subtipo }}</div>
                    </div>
                    <div class="text-right">
                        <div>{{ prod.emision }}</div>
                        <div :class="`font-bold mt-0.5 ${prod.estado === 'POR VENCER' ? 'text-amber-500' : prod.estado === 'VENCIDA' ? 'text-red-500' : 'text-gray-500'}`">{{ prod.vencimiento }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table View (Desktop) -->
        <div class="hidden md:block bg-white dark:bg-[#1E293B] rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-gray-800/20 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800">
                            <th class="p-4 font-bold">Documento</th>
                            <th class="p-4 font-bold">Tipo</th>
                            <th class="p-4 font-bold">Titular (DPI/NIT)</th>
                            <th class="p-4 font-bold">Emisión / Vencimiento</th>
                            <th class="p-4 font-bold">Estado</th>
                            <th class="p-4 font-bold text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr v-if="loading" class="animate-pulse">
                            <td colspan="6" class="p-8 text-center text-gray-400">Cargando licencias...</td>
                        </tr>
                        <tr v-else-if="filteredLicenses.length === 0">
                            <td colspan="6" class="p-12 text-center text-gray-400">
                                <div class="flex flex-col items-center">
                                    <DocumentMagnifyingGlassIcon class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" />
                                    <p>No se encontraron registros activos.</p>
                                </div>
                            </td>
                        </tr>
                        <tr v-else v-for="prod in filteredLicenses" :key="prod.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group">
                            <td class="p-4 text-sm font-medium text-gray-600 dark:text-gray-300">
                                {{ prod.documento }}
                            </td>
                            <td class="p-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-brand-dark dark:text-gray-200">{{ prod.tipo }}</span>
                                    <span class="text-xs text-gray-500">{{ prod.subtipo }}</span>
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ prod.titular }}</div>
                                <div class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                                    <IdentificationIcon class="w-3 h-3" /> {{ prod.identificacion }}
                                </div>
                            </td>
                            <td class="p-4 text-sm text-gray-600 dark:text-gray-400">
                                <div>{{ prod.emision }}</div>
                                <div :class="`text-xs mt-0.5 font-bold ${prod.estado === 'POR VENCER' ? 'text-amber-500' : prod.estado === 'VENCIDA' ? 'text-red-500' : 'text-gray-500'}`">
                                    {{ prod.vencimiento }}
                                </div>
                            </td>
                            <td class="p-4">
                                <span :class="`px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider ${getEstadoClass(prod.estado)}`">
                                    {{ prod.estado }}
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <button class="p-1.5 text-gray-400 hover:text-primary transition-colors rounded-lg hover:bg-primary/10 mr-1" title="Ver Detalle">
                                    <EyeIcon class="w-4 h-4" />
                                </button>
                                <button class="p-1.5 text-gray-400 hover:text-blue-500 transition-colors rounded-lg hover:bg-blue-500/10" title="Actualizar">
                                    <ArrowPathIcon class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Footer -->
            <div class="p-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800 flex flex-col md:flex-row justify-between items-center gap-4 text-sm">
                <span class="text-gray-500">
                    Mostrando <span class="font-bold text-gray-700 dark:text-gray-300">{{ filteredLicenses.length }}</span> registros
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
    AdjustmentsHorizontalIcon,
    IdentificationIcon,
    DocumentMagnifyingGlassIcon,
    EyeIcon,
    ArrowPathIcon,
    DocumentArrowDownIcon
} from '@heroicons/vue/24/outline';
import VisarService from '@/services/visar/VisarService';

const loading = ref(true);
const searchQuery = ref('');
const filterStatus = ref('');
const licenses = ref([]);

const loadData = async () => {
    loading.value = true;
    try {
        const resp = await VisarService.getLicencias();
        if (resp.status === 'success') {
            licenses.value = resp.data;
        }
    } catch (error) {
        console.error('Error al cargar licencias:', error);
    } finally {
        loading.value = false;
    }
};

const filteredLicenses = computed(() => {
    return licenses.value.filter(prod => {
        const matchesSearch = prod.titular.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                              prod.documento.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                              prod.identificacion.includes(searchQuery.value);
        
        const matchesType = filterStatus.value ? prod.estado === filterStatus.value : true;
        
        return matchesSearch && matchesType;
    });
});

const getEstadoClass = (estado) => {
    switch(estado) {
        case 'VIGENTE': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
        case 'EN TRAMITE': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        case 'POR VENCER': return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400';
        case 'VENCIDA': return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        default: return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400';
    }
};

onMounted(() => {
    loadData();
});
</script>
