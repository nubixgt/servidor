<template>
    <div class="animate-fade-in pb-10">
        <!-- Metric Cards for Unidades -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
             <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl p-6 rounded-3xl border border-white/80 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                <div class="flex items-center gap-3 mb-4">
                     <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center shrink-0 shadow-sm">
                        <WalletIcon class="w-5 h-5" />
                    </div>
                </div>
                <div>
                     <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Vigente</h3>
                    <p class="text-3xl font-black text-brand-dark dark:text-white">Q {{ formatMoney(totals.vigente) }}</p>
                </div>
            </div>

            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl p-6 rounded-3xl border border-white/80 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                <div class="flex items-center gap-3 mb-4">
                     <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 flex items-center justify-center shrink-0 shadow-sm">
                        <CheckCircleIcon class="w-5 h-5" />
                    </div>
                </div>
                <div>
                     <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Devengado</h3>
                    <p class="text-3xl font-black text-brand-dark dark:text-white">Q {{ formatMoney(totals.devengado) }}</p>
                </div>
            </div>

            <div class="bg-brand-dark p-6 rounded-3xl shadow-lg border border-gray-700 flex flex-col justify-between relative overflow-hidden">
                 <div class="absolute top-0 right-0 w-40 h-40 bg-primary/20 rounded-full blur-3xl -mr-10 -mt-10"></div>
                <div class="flex items-center gap-3 mb-4 relative z-10">
                     <div class="w-10 h-10 rounded-xl bg-white/10 text-white flex items-center justify-center shrink-0 backdrop-blur-sm border border-white/10">
                        <HourglassIcon class="w-5 h-5" />
                    </div>
                </div>
                <div class="relative z-10">
                     <h3 class="text-[10px] font-bold text-blue-200 uppercase tracking-widest mb-1">Saldo por Devengar</h3>
                    <p class="text-3xl font-black text-white">Q {{ formatMoney(totals.saldo) }}</p>
                </div>
            </div>
        </div>

        <!-- Filters Banner -->
        <div class="bg-white/60 dark:bg-gray-800/60 backdrop-blur-md p-4 rounded-2xl mb-6 shadow-sm border border-white/80 dark:border-gray-800 flex flex-col sm:flex-row gap-4 items-center">
            <div class="flex flex-col flex-1 sm:max-w-xs">
                 <label class="text-[10px] uppercase font-bold text-gray-500 mb-1 ml-1">Unidad Ejecutora</label>
                 <select v-model="filterUnidad" class="w-full border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary/50 shadow-sm">
                     <option value="">Todas las Unidades</option>
                     <option v-for="u in uniqueNames" :key="u" :value="u">{{ u }}</option>
                 </select>
            </div>
            <div class="pt-5">
                 <button @click="clearFilters" class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-4 py-2.5 rounded-xl text-sm font-bold hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center gap-2">
                     <XMarkIcon class="w-4 h-4" /> Borrar Filtros
                 </button>
            </div>
        </div>

        <!-- Main Layout Split -->
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Left Sidebar Navigation -->
            <div class="w-full lg:w-64 flex-shrink-0">
                <div class="bg-white dark:bg-[#1E293B] rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                    <div class="bg-brand-dark p-4">
                        <h3 class="text-white font-bold text-sm flex items-center gap-2">
                             <FunnelIcon class="w-4 h-4" /> Tipo Ejecución
                        </h3>
                    </div>
                    <div class="p-3 flex flex-col gap-2">
                        <button @click="filterTipo = ''"
                            :class="`w-full text-left px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-3 transition-colors ${filterTipo === '' ? 'bg-blue-50/50 dark:bg-blue-900/20 text-primary border border-blue-100 dark:border-blue-800/50' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50'}`">
                            <ListBulletIcon class="w-5 h-5" /> Todos
                        </button>
                        <button @click="filterTipo = 'GRUPO_GASTO'"
                            :class="`w-full text-left px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-3 transition-colors ${filterTipo === 'GRUPO_GASTO' ? 'bg-blue-50/50 dark:bg-blue-900/20 text-primary border border-blue-100 dark:border-blue-800/50' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50'}`">
                            <DocumentDuplicateIcon class="w-5 h-5" /> Grupo de gasto
                            <span v-if="filterTipo === 'GRUPO_GASTO'" class="ml-auto bg-primary text-white text-[9px] font-bold px-2 py-0.5 rounded-full">{{ filteredData.length }}</span>
                        </button>
                        <button @click="filterTipo = 'FUENTE_FINANCIAMIENTO'"
                            :class="`w-full text-left px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-3 transition-colors ${filterTipo === 'FUENTE_FINANCIAMIENTO' ? 'bg-blue-50/50 dark:bg-blue-900/20 text-primary border border-blue-100 dark:border-blue-800/50' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50'}`">
                            <BanknotesIcon class="w-5 h-5" /> Fuente de financiamiento
                            <span v-if="filterTipo === 'FUENTE_FINANCIAMIENTO'" class="ml-auto bg-primary text-white text-[9px] font-bold px-2 py-0.5 rounded-full">{{ filteredData.length }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table Content -->
            <div class="flex-1 bg-white dark:bg-[#1E293B] rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                 <div class="bg-brand-dark p-4 flex justify-between items-center text-white">
                    <h3 class="font-bold flex items-center gap-2"><TableCellsIcon class="w-5 h-5"/> Detalle por Unidad Ejecutora</h3>
                    <span class="text-sm bg-white/20 px-3 py-1 rounded-lg">{{ filteredData.length }} registros</span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-right border-collapse text-sm">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-800">
                                <th class="p-4 font-bold text-left w-40">Tipo Ejecución</th>
                                <th class="p-4 font-bold text-left">Tipo Gasto / Financiamiento</th>
                                <th class="p-4 font-bold">Vigente</th>
                                <th class="p-4 font-bold">Devengado</th>
                                <th class="p-4 font-bold">Saldo por Devengar</th>
                                <th class="p-4 font-bold text-center">% Ejecución</th>
                                <th class="p-4 font-bold text-center">% Relativo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 font-mono text-xs">
                            <tr v-if="filteredData.length === 0">
                                <td colspan="7" class="p-8 text-center text-gray-400 font-sans">No se encontraron registros con los filtros aplicados.</td>
                            </tr>
                            <tr v-for="item in filteredData" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                                <td class="p-4 text-left font-sans">
                                     <span :class="`px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider ${item.type === 'FUENTE_FINANCIAMIENTO' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400' : 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400'}`">
                                        {{ item.type ? item.type.replace('_', ' ') : '' }}
                                    </span>
                                </td>
                                <td class="p-4 text-left font-sans font-medium text-gray-700 dark:text-gray-300">
                                    {{ item.name }}
                                </td>
                                <td class="p-4 text-gray-500 whitespace-nowrap">Q {{ formatMoney(item.vigente) }}</td>
                                <td class="p-4 text-gray-800 dark:text-gray-200 font-bold whitespace-nowrap">Q {{ formatMoney(item.devengado) }}</td>
                                <td class="p-4 text-gray-400 whitespace-nowrap">Q {{ formatMoney(item.saldo) }}</td>
                                
                                <td class="p-4 text-center font-sans whitespace-nowrap">
                                     <span :class="`px-2 py-0.5 rounded font-bold ${item.pct_ejec >= 90 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400' : item.pct_ejec >= 75 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400'}`">
                                        {{ item.pct_ejec }}%
                                     </span>
                                </td>
                                <td class="p-4 text-center text-gray-400 font-sans border-l border-gray-50 dark:border-gray-800/50">
                                    {{ item.pct_rel }}%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, defineComponent, h, inject, watch } from 'vue';
import { 
    WalletIcon, 
    CheckCircleIcon, 
    XMarkIcon,
    FunnelIcon,
    ListBulletIcon,
    DocumentDuplicateIcon,
    BanknotesIcon,
    TableCellsIcon,
} from '@heroicons/vue/24/outline';
import PresupuestoService from '@/services/presupuesto/PresupuestoService';

const HourglassIcon = defineComponent({
  render() {
    return h('svg', { xmlns: 'http://www.w3.org/2000/svg', fill: 'none', viewBox: '0 0 24 24', 'stroke-width': '1.5', stroke: 'currentColor' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' })
    ])
  }
})

const formatMoney = (val) => {
    return Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const loading = ref(true);
const tableData = ref([]);
const selectedYear = inject('selectedYear');

// Filters
const filterTipo = ref('');
const filterUnidad = ref('');

const uniqueNames = computed(() => {
    const names = tableData.value.map(i => i.name).filter(Boolean);
    return [...new Set(names)].sort();
});

const filteredData = computed(() => {
    let items = tableData.value;
    if (filterTipo.value) {
        items = items.filter(i => (i.type || '').toUpperCase() === filterTipo.value);
    }
    if (filterUnidad.value) {
        items = items.filter(i => i.name === filterUnidad.value);
    }
    return items;
});

const clearFilters = () => {
    filterTipo.value = '';
    filterUnidad.value = '';
};

const loadData = async () => {
    loading.value = true;
    try {
        const resp = await PresupuestoService.getDashboard({ tipo: 'UNIDAD_EJECUTORA', ejercicio: selectedYear.value });
        if (resp.status === 'success' && resp.data) {
            tableData.value = resp.data.items || [];
        }
    } catch (error) {
        console.error('Error al cargar unidades:', error);
    } finally {
        loading.value = false;
    }
};

watch(selectedYear, () => {
    clearFilters();
    loadData();
});

onMounted(() => {
    loadData();
});

const totals = computed(() => {
    return filteredData.value.reduce((acc, curr) => {
        acc.vigente += curr.vigente || 0;
        acc.devengado += curr.devengado || 0;
        acc.saldo += curr.saldo || 0;
        return acc;
    }, { vigente: 0, devengado: 0, saldo: 0 });
});

</script>
