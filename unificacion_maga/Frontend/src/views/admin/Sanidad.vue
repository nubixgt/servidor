<template>
    <div class="animate-fade-in w-full max-w-6xl mx-auto pb-10">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h2 class="text-3xl font-brand font-black text-brand-dark dark:text-white tracking-widest drop-shadow-sm">
                    Sanidad Agropecuaria
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Control de normativas fitozoosanitarias y prevención de plagas</p>
            </div>
            
            <div class="flex gap-3">
                <button class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-4 py-2.5 rounded-xl font-bold hover:bg-gray-200 dark:hover:bg-gray-700 transition-all flex items-center gap-2">
                    <DocumentArrowDownIcon class="w-5 h-5" /> Exportar
                </button>
                <button class="bg-primary hover:bg-primary-dark text-white px-5 py-2.5 rounded-xl font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                    <PlusIcon class="w-5 h-5" /> Nueva Inspección
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
                    placeholder="Buscar por productor, finca o # certificado..." 
                    class="w-full pl-10 pr-4 py-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-800/50 text-brand-dark dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all text-sm"
                />
            </div>
            
            <div class="flex gap-2 w-full md:w-auto">
                <select v-model="filterTipo" class="border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-sm px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50 flex-1 md:flex-none">
                    <option value="">Todas las Áreas</option>
                    <option value="FITOSANITARIO">Fitosanitario</option>
                    <option value="ZOOSANITARIO">Zoosanitario</option>
                    <option value="INOCUIDAD">Inocuidad de Alimentos</option>
                </select>
            </div>
        </div>

        <!-- Metric Cards Mini -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-orange-50 dark:bg-orange-900/10 p-5 rounded-2xl border border-orange-100 dark:border-orange-500/20">
                <div class="flex justify-between items-center text-orange-600 mb-2">
                    <ShieldExclamationIcon class="w-6 h-6" />
                    <span class="text-xs font-bold uppercase bg-orange-200 dark:bg-orange-900/40 px-2 py-0.5 rounded">Alertas Críticas</span>
                </div>
                <h4 class="text-2xl font-black text-orange-700 dark:text-orange-400">{{ stats.alertas_criticas }}</h4>
                <p class="text-xs text-orange-600/80">Requieren atención inmediata</p>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/10 p-5 rounded-2xl border border-blue-100 dark:border-blue-500/20">
                 <div class="flex justify-between items-center text-blue-600 mb-2">
                    <ClipboardDocumentCheckIcon class="w-6 h-6" />
                    <span class="text-xs font-bold uppercase bg-blue-200 dark:bg-blue-900/40 px-2 py-0.5 rounded">En Proceso</span>
                </div>
                <h4 class="text-2xl font-black text-blue-700 dark:text-blue-400">{{ stats.en_proceso }}</h4>
                <p class="text-xs text-blue-600/80">Inspecciones agendadas</p>
            </div>
            <div class="bg-emerald-50 dark:bg-emerald-900/10 p-5 rounded-2xl border border-emerald-100 dark:border-emerald-500/20">
                 <div class="flex justify-between items-center text-emerald-600 mb-2">
                    <ShieldCheckIcon class="w-6 h-6" />
                    <span class="text-xs font-bold uppercase bg-emerald-200 dark:bg-emerald-900/40 px-2 py-0.5 rounded">Aprobados</span>
                </div>
                <h4 class="text-2xl font-black text-emerald-700 dark:text-emerald-400">{{ stats.aprobados }}</h4>
                <p class="text-xs text-emerald-600/80">Certificados este mes</p>
            </div>
        </div>

        <!-- Table View -->
        <div class="bg-white dark:bg-[#1E293B] rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-gray-800/20 text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800">
                            <th class="p-4 font-bold">Certificado / ID</th>
                            <th class="p-4 font-bold">Área</th>
                            <th class="p-4 font-bold">Productor u Origen</th>
                            <th class="p-4 font-bold">Fecha / Motivo</th>
                            <th class="p-4 font-bold">Riesgo / Estado</th>
                            <th class="p-4 font-bold text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr v-if="loading" class="animate-pulse">
                            <td colspan="6" class="p-8 text-center text-gray-400">Cargando registros sanitarios...</td>
                        </tr>
                        <tr v-else-if="filteredRecords.length === 0">
                            <td colspan="6" class="p-12 text-center text-gray-400">
                                <div class="flex flex-col items-center">
                                    <ShieldCheckIcon class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" />
                                    <p>No se encontraron registros sanitarios.</p>
                                </div>
                            </td>
                        </tr>
                        <tr v-else v-for="record in filteredRecords" :key="record.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group">
                            <td class="p-4">
                                <span class="text-sm font-bold text-brand-dark dark:text-gray-200">{{ record.codigo }}</span>
                            </td>
                            <td class="p-4">
                                <span :class="`px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider ${getAreaClass(record.area)}`">
                                    {{ record.area }}
                                </span>
                            </td>
                            <td class="p-4">
                                <div class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ record.productor }}</div>
                                <div class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                                    <MapPinIcon class="w-3 h-3" /> {{ record.ubicacion }}
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ record.fecha }}</div>
                                <div class="text-xs font-medium text-gray-500">{{ record.motivo }}</div>
                            </td>
                            <td class="p-4">
                                 <div class="flex flex-col gap-1.5">
                                    <div class="flex items-center gap-1">
                                        <div :class="`w-2 h-2 rounded-full ${record.estado === 'APROBADO' ? 'bg-emerald-500' : record.estado === 'EN REVISIÓN' ? 'bg-blue-500' : 'bg-orange-500'}`"></div>
                                        <span class="text-[11px] font-semibold text-gray-600 dark:text-gray-300">{{ record.estado }}</span>
                                    </div>
                                    <span v-if="record.riesgo === 'ALTO'" class="text-[10px] font-bold text-white bg-red-500 px-2 py-0.5 rounded shadow-sm self-start inline-flex items-center gap-1">
                                        <ExclamationTriangleIcon class="w-3 h-3" /> RIESGO ALTO
                                    </span>
                                </div>
                            </td>
                            <td class="p-4 text-right">
                                <button class="p-1.5 text-gray-400 hover:text-primary transition-colors rounded-lg hover:bg-primary/10 mr-1" title="Ver Expediente">
                                    <EyeIcon class="w-4 h-4" />
                                </button>
                                <button class="p-1.5 text-gray-400 hover:text-emerald-500 transition-colors rounded-lg hover:bg-emerald-500/10 mr-1" title="Aprobar">
                                    <CheckCircleIcon class="w-4 h-4" />
                                </button>
                                <button class="p-1.5 text-gray-400 hover:text-blue-500 transition-colors rounded-lg hover:bg-blue-500/10" title="Editar">
                                    <PencilSquareIcon class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Footer -->
            <div class="p-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800 flex flex-col md:flex-row justify-between items-center gap-4 text-sm">
                 <span class="text-gray-500">
                    Mostrando <span class="font-bold text-gray-700 dark:text-gray-300">{{ filteredRecords.length }}</span> registros
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
import { ref, computed, onMounted, reactive } from 'vue';
import { 
    PlusIcon, 
    MagnifyingGlassIcon,
    MapPinIcon,
    EyeIcon,
    PencilSquareIcon,
    ShieldCheckIcon,
    ShieldExclamationIcon,
    ClipboardDocumentCheckIcon,
    DocumentArrowDownIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline';
import VisarService from '@/services/visar/VisarService';

const loading = ref(true);
const searchQuery = ref('');
const filterTipo = ref('');
const records = ref([]);
const stats = reactive({
    total: 0,
    alertas_criticas: 0,
    en_proceso: 0,
    aprobados: 0
});

const loadData = async () => {
    loading.value = true;
    try {
        const resp = await VisarService.getInspecciones();
        if (resp.status === 'success') {
            records.value = resp.data;
            if (resp.stats) {
                stats.total = resp.stats.total;
                stats.alertas_criticas = resp.stats.alertas_criticas;
                stats.en_proceso = resp.stats.en_proceso;
                stats.aprobados = resp.stats.aprobados;
            }
        }
    } catch (error) {
        console.error('Error al cargar datos de sanidad:', error);
    } finally {
        loading.value = false;
    }
};

const filteredRecords = computed(() => {
    return records.value.filter(rec => {
        const matchesSearch = rec.productor.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                              rec.codigo.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                              rec.ubicacion.toLowerCase().includes(searchQuery.value.toLowerCase());
        
        const matchesType = filterTipo.value ? rec.area === filterTipo.value : true;
        
        return matchesSearch && matchesType;
    });
});

const getAreaClass = (area) => {
    switch(area) {
        case 'FITOSANITARIO': return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
        case 'ZOOSANITARIO': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
        case 'INOCUIDAD': return 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400';
        default: return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400';
    }
};

onMounted(() => {
    loadData();
});
</script>
