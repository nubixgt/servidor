<template>
    <div class="animate-fade-in pb-10">
        <!-- Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center shrink-0">
                        <BanknotesIcon class="w-4 h-4" />
                    </div>
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Asignado</h3>
                </div>
                <p class="text-xl font-black text-brand-dark dark:text-white">Q {{ formatMoney(stats.asignado) }}</p>
            </div>
            
            <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-2">
                     <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-600 flex items-center justify-center shrink-0">
                        <ArrowsRightLeftIcon class="w-4 h-4" />
                    </div>
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Modificado</h3>
                </div>
                <p class="text-xl font-black text-red-500" v-if="stats.modificado < 0">- Q {{ formatMoney(Math.abs(stats.modificado)) }}</p>
                <p class="text-xl font-black text-emerald-500" v-else>+ Q {{ formatMoney(stats.modificado) }}</p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-2">
                     <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center shrink-0">
                        <WalletIcon class="w-4 h-4" />
                    </div>
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Vigente</h3>
                </div>
                <p class="text-xl font-black text-brand-dark dark:text-white">Q {{ formatMoney(stats.vigente) }}</p>
            </div>
            
             <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-2">
                     <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/30 text-purple-600 flex items-center justify-center shrink-0">
                        <CheckBadgeIcon class="w-4 h-4" />
                    </div>
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Devengado</h3>
                </div>
                <p class="text-xl font-black text-brand-dark dark:text-white">Q {{ formatMoney(stats.devengado) }}</p>
            </div>

             <div class="bg-brand-dark p-5 rounded-3xl shadow-lg border border-gray-700 flex flex-col justify-between hover:shadow-xl transition-shadow relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary/20 rounded-full blur-2xl -mr-10 -mt-10"></div>
                <div class="flex items-center gap-3 mb-2 relative z-10">
                     <div class="w-8 h-8 rounded-lg bg-white/10 text-white flex items-center justify-center shrink-0">
                        <HourglassIcon class="w-4 h-4" />
                    </div>
                    <h3 class="text-xs font-bold text-gray-300 uppercase tracking-wider">Saldo por Devengar</h3>
                </div>
                <p class="text-xl font-black text-white relative z-10">Q {{ formatMoney(stats.saldo) }}</p>
            </div>
        </div>

        <!-- Global Progress Bar -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm mb-6">
            <div class="flex justify-between items-center mb-1">
                 <h2 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                    <ChartBarSquareIcon class="w-5 h-5 text-emerald-500" />
                    Ejecución General
                </h2>
            </div>
            
            <div class="relative pt-8 pb-2">
                 <!-- Floating Badge for Current Progress -->
                 <div class="absolute top-0 -translate-x-1/2 z-20 flex flex-col items-center transition-all duration-500" :style="`left: ${stats.pct_ejec > 100 ? 100 : stats.pct_ejec}%`">
                      <div class="bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-400 font-black px-3 py-1 rounded-xl text-xs shadow-sm border border-emerald-200 dark:border-emerald-800/50">
                          {{ stats.pct_ejec }}%
                      </div>
                      <div class="w-0.5 h-2 bg-emerald-500 mt-1"></div>
                 </div>

                 <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 relative overflow-hidden">
                    <div class="bg-emerald-500 h-3 rounded-full transition-all duration-500" :style="`width: ${stats.pct_ejec > 100 ? 100 : stats.pct_ejec}%`"></div>
                </div>
            </div>
            <div class="flex justify-between text-[10px] uppercase font-bold text-gray-400 mt-1">
                <span>0%</span>
                <span>100%</span>
            </div>
        </div>
        
        <!-- Filters Area -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col md:flex-row gap-4 items-end mb-6">
            <div class="flex flex-wrap items-center gap-4 w-full md:w-auto flex-1">
                 <div class="flex flex-col">
                    <label class="text-[10px] uppercase font-bold text-gray-500 mb-1 ml-1">Filtro de Ejecución</label>
                    <select v-model="filterEjecucion" class="border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300 text-sm px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                        <option value="todos">Todos</option>
                        <option value="arriba">Arriba de la Meta</option>
                        <option value="abajo">Debajo de la Meta</option>
                    </select>
                </div>
                <div class="flex flex-col flex-1 md:w-64">
                    <label class="text-[10px] uppercase font-bold text-gray-500 mb-1 ml-1">Buscar Unidad</label>
                    <div class="relative">
                        <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                        <input v-model="searchText" type="text" placeholder="Buscar por programa..." class="w-full pl-10 pr-4 py-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-800/50 text-brand-dark dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all text-sm"/>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-2 w-full md:w-auto mt-4 md:mt-0">
                <button @click="clearFilters" class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-xl text-sm font-bold hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    Limpiar Filtros
                </button>
            </div>
        </div>

        <!-- Details Table View -->
        <div class="bg-white dark:bg-[#1E293B] rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden mb-6">
            <div class="bg-brand-dark p-4 flex justify-between items-center text-white">
                <h3 class="font-bold flex items-center gap-2 text-base md:text-lg"><TableCellsIcon class="w-5 h-5"/> Ejecución Presupuestaria</h3>
                <span class="text-sm font-bold bg-white/20 px-3.5 py-1 rounded-xl shadow-sm border border-white/10">{{ filteredItems.length }} registros</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse text-sm"> <!-- text-right for currency numbers -->
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-gray-800/20 text-[10px] uppercase tracking-wider text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800">
                            <th @click="toggleSort('name')" class="p-4 font-bold text-left cursor-pointer select-none hover:bg-gray-100/30 dark:hover:bg-gray-700/30 transition-colors">
                                <div class="flex items-center gap-1 justify-start">
                                    <span>PROGRA UNI GASTO FINAN</span>
                                    <span class="text-xs transition-opacity duration-200" :class="sortBy === 'name' ? 'text-primary dark:text-emerald-400 opacity-100 font-extrabold' : 'text-gray-400 opacity-40'">
                                        {{ sortBy === 'name' ? (sortDesc ? '▼' : '▲') : '↕' }}
                                    </span>
                                </div>
                            </th>
                            <th @click="toggleSort('asignado')" class="p-4 font-bold cursor-pointer select-none hover:bg-gray-100/30 dark:hover:bg-gray-700/30 transition-colors">
                                <div class="flex items-center gap-1 justify-end">
                                    <span>ASIGNADO</span>
                                    <span class="text-xs transition-opacity duration-200" :class="sortBy === 'asignado' ? 'text-primary dark:text-emerald-400 opacity-100 font-extrabold' : 'text-gray-400 opacity-40'">
                                        {{ sortBy === 'asignado' ? (sortDesc ? '▼' : '▲') : '↕' }}
                                    </span>
                                </div>
                            </th>
                            <th @click="toggleSort('modificado')" class="p-4 font-bold cursor-pointer select-none hover:bg-gray-100/30 dark:hover:bg-gray-700/30 transition-colors">
                                <div class="flex items-center gap-1 justify-end">
                                    <span>MODIFICADO</span>
                                    <span class="text-xs transition-opacity duration-200" :class="sortBy === 'modificado' ? 'text-primary dark:text-emerald-400 opacity-100 font-extrabold' : 'text-gray-400 opacity-40'">
                                        {{ sortBy === 'modificado' ? (sortDesc ? '▼' : '▲') : '↕' }}
                                    </span>
                                </div>
                            </th>
                            <th @click="toggleSort('vigente')" class="p-4 font-bold cursor-pointer select-none hover:bg-gray-100/30 dark:hover:bg-gray-700/30 transition-colors">
                                <div class="flex items-center gap-1 justify-end">
                                    <span>VIGENTE</span>
                                    <span class="text-xs transition-opacity duration-200" :class="sortBy === 'vigente' ? 'text-primary dark:text-emerald-400 opacity-100 font-extrabold' : 'text-gray-400 opacity-40'">
                                        {{ sortBy === 'vigente' ? (sortDesc ? '▼' : '▲') : '↕' }}
                                    </span>
                                </div>
                            </th>
                            <th @click="toggleSort('devengado')" class="p-4 font-bold cursor-pointer select-none hover:bg-gray-100/30 dark:hover:bg-gray-700/30 transition-colors">
                                <div class="flex items-center gap-1 justify-end">
                                    <span>DEVENGADO</span>
                                    <span class="text-xs transition-opacity duration-200" :class="sortBy === 'devengado' ? 'text-primary dark:text-emerald-400 opacity-100 font-extrabold' : 'text-gray-400 opacity-40'">
                                        {{ sortBy === 'devengado' ? (sortDesc ? '▼' : '▲') : '↕' }}
                                    </span>
                                </div>
                            </th>
                            <th @click="toggleSort('saldo')" class="p-4 font-bold cursor-pointer select-none hover:bg-gray-100/30 dark:hover:bg-gray-700/30 transition-colors">
                                <div class="flex items-center gap-1 justify-end">
                                    <span>SALDO POR DEVENGAR</span>
                                    <span class="text-xs transition-opacity duration-200" :class="sortBy === 'saldo' ? 'text-primary dark:text-emerald-400 opacity-100 font-extrabold' : 'text-gray-400 opacity-40'">
                                        {{ sortBy === 'saldo' ? (sortDesc ? '▼' : '▲') : '↕' }}
                                    </span>
                                </div>
                            </th>
                            <th @click="toggleSort('pct_ejec')" class="p-4 font-bold text-center cursor-pointer select-none hover:bg-gray-100/30 dark:hover:bg-gray-700/30 transition-colors">
                                <div class="flex items-center gap-1 justify-center">
                                    <span>% EJECUCIÓN</span>
                                    <span class="text-xs transition-opacity duration-200" :class="sortBy === 'pct_ejec' ? 'text-primary dark:text-emerald-400 opacity-100 font-extrabold' : 'text-gray-400 opacity-40'">
                                        {{ sortBy === 'pct_ejec' ? (sortDesc ? '▼' : '▲') : '↕' }}
                                    </span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 font-mono text-xs">
                        <tr v-if="filteredItems.length === 0">
                            <td colspan="7" class="p-8 text-center text-gray-400 font-sans">No se encontraron registros con los filtros aplicados.</td>
                        </tr>
                        <tr v-for="item in filteredItems" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <td class="p-4 text-left font-sans font-medium text-gray-800 dark:text-gray-200">
                                {{ item.name }}
                            </td>
                            <td class="p-4 text-gray-600 dark:text-gray-400 whitespace-nowrap">Q {{ formatMoney(item.asignado) }}</td>
                            <td class="p-4 whitespace-nowrap" :class="item.modificado < 0 ? 'text-red-500' : 'text-emerald-500'">
                                {{ item.modificado < 0 ? '-' : '+' }} Q {{ formatMoney(Math.abs(item.modificado)) }}
                            </td>
                            <td class="p-4 text-gray-800 dark:text-gray-300 font-bold whitespace-nowrap">Q {{ formatMoney(item.vigente) }}</td>
                            <td class="p-4 text-gray-600 dark:text-gray-400 whitespace-nowrap">Q {{ formatMoney(item.devengado) }}</td>
                            <td class="p-4 text-gray-500 dark:text-gray-500 whitespace-nowrap">Q {{ formatMoney(item.saldo) }}</td>
                            
                            <!-- Ejecucion con Progress bar mini -->
                            <td class="p-4 text-center font-sans whitespace-nowrap">
                                <span :class="[getPctPillClass(item.pct_ejec), 'px-2 py-0.5 rounded font-bold mr-2']">{{ item.pct_ejec.toFixed(2) }}%</span>
                                <div class="w-12 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full inline-block align-middle">
                                    <div class="h-1.5 rounded-full" :class="getPctColorClass(item.pct_ejec)" :style="`width: ${item.pct_ejec > 100 ? 100 : item.pct_ejec}%`"></div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Charts Area -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Top Unidades Bar Chart -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col h-80">
                <div class="flex justify-between items-center mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">
                    <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2 text-sm">
                        <BuildingOfficeIcon class="w-5 h-5 text-blue-500" /> Top Unidades por Presupuesto
                    </h3>
                    <span class="text-[10px] text-gray-400">% de ejecución mostrado</span>
                </div>
                 <div class="flex-1 w-full relative">
                    <Bar v-if="chartData1" :data="chartData1" :options="chartOptions1" />
                 </div>
            </div>

            <!-- Grupo Gasto Bar Chart -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col h-80">
                <div class="flex justify-between items-center mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">
                    <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2 text-sm">
                        <CircleStackIcon class="w-5 h-5 text-indigo-500" /> Vigente vs Devengado por Grupo
                    </h3>
                    <span class="text-[10px] text-gray-400">Códigos presupuestarios</span>
                </div>
                <div class="flex-1 w-full relative">
                    <Bar v-if="chartData2" :data="chartData2" :options="chartOptions2" />
                 </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, defineComponent, h, inject, watch } from 'vue';
import { 
    BanknotesIcon,
    ArrowsRightLeftIcon,
    WalletIcon,
    CheckBadgeIcon,
    ChartBarSquareIcon,
    ExclamationTriangleIcon,
    MagnifyingGlassIcon,
    DocumentArrowDownIcon,
    TableCellsIcon,
    BuildingOfficeIcon,
    CircleStackIcon
} from '@heroicons/vue/24/outline';

import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'
import PresupuestoService from '@/services/presupuesto/PresupuestoService';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

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

const getPctColorClass = (pct) => {
    if (pct >= 89.17) return 'bg-emerald-500';
    if (pct >= 70) return 'bg-amber-500';
    return 'bg-red-500';
};

const getPctPillClass = (pct) => {
    if (pct >= 89.17) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
    if (pct >= 70) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
    return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
};

const loading = ref(true);
const selectedYear = inject('selectedYear');
const budgetItems = ref([]);
const groupsSummary = ref([]);
const searchText = ref('');
const filterEjecucion = ref('todos');

const sortBy = ref('name');
const sortDesc = ref(false);

const toggleSort = (col) => {
    if (sortBy.value === col) {
        sortDesc.value = !sortDesc.value;
    } else {
        sortBy.value = col;
        sortDesc.value = false;
    }
};

const filteredItems = computed(() => {
    let items = budgetItems.value;
    if (searchText.value.trim()) {
        const q = searchText.value.trim().toLowerCase();
        items = items.filter(i => i.name && i.name.toLowerCase().includes(q));
    }
    if (filterEjecucion.value === 'arriba') {
        items = items.filter(i => parseFloat(i.pct_ejec) >= parseFloat(stats.value.pct_ejec));
    } else if (filterEjecucion.value === 'abajo') {
        items = items.filter(i => parseFloat(i.pct_ejec) < parseFloat(stats.value.pct_ejec));
    }
    
    // Sort logic
    const col = sortBy.value;
    const desc = sortDesc.value;
    items = [...items].sort((a, b) => {
        let valA = a[col];
        let valB = b[col];
        
        // Treat null/undefined
        if (valA === null || valA === undefined) valA = (typeof valB === 'number') ? 0 : '';
        if (valB === null || valB === undefined) valB = (typeof valA === 'number') ? 0 : '';
        
        // Handle name string sort specifically
        if (col === 'name') {
            const strA = String(valA).toLowerCase();
            const strB = String(valB).toLowerCase();
            return desc ? strB.localeCompare(strA) : strA.localeCompare(strB);
        }
        
        // Convert to float for numeric comparison
        const numA = parseFloat(valA);
        const numB = parseFloat(valB);
        
        if (!isNaN(numA) && !isNaN(numB)) {
            return desc ? (numB - numA) : (numA - numB);
        }
        
        // Fallback to string comparison
        const strA = String(valA).toLowerCase();
        const strB = String(valB).toLowerCase();
        return desc ? strB.localeCompare(strA) : strA.localeCompare(strB);
    });
    
    return items;
});

const clearFilters = () => {
    searchText.value = '';
    filterEjecucion.value = 'todos';
    sortBy.value = 'name';
    sortDesc.value = false;
};
const stats = ref({
    asignado: 0,
    modificado: 0,
    vigente: 0,
    devengado: 0,
    saldo: 0,
    pct_ejec: 0
});

const loadData = async () => {
    loading.value = true;
    try {
        const resp = await PresupuestoService.getDashboard({ ejercicio: selectedYear.value });
        
        if (resp.status === 'success' && resp.data) {
            budgetItems.value = resp.data.items || [];
            groupsSummary.value = resp.data.groups_summary || [];
            if (resp.data.summary) {
                stats.value = resp.data.summary;
            }
        }
    } catch (error) {
        console.error('Error al cargar datos de presupuesto:', error);
    } finally {
        loading.value = false;
    }
};

watch(selectedYear, () => {
    loadData();
});

onMounted(() => {
    loadData();
});

const chartData1 = computed(() => {
    const items = Array.isArray(budgetItems.value) ? budgetItems.value.slice(0, 6) : [];
    return {
        labels: items.map(item => item.name ? item.name.split(' ')[0] : 'N/A'), 
        datasets: [
            {
                label: 'Vigente',
                backgroundColor: '#3b82f6', 
                data: items.map(item => item.vigente || 0)
            },
            {
                label: 'Devengado',
                backgroundColor: '#10b981', 
                data: items.map(item => item.devengado || 0)
            }
        ]
    }
})

const chartOptions1 = {
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'top' },
        tooltip: {
            callbacks: {
                label: function(context) {
                    let label = context.dataset.label || '';
                    if (label) label += ': ';
                    if (context.parsed.x !== null) label += 'Q ' + formatMoney(context.parsed.x);
                    return label;
                }
            }
        }
    },
    scales: {
        x: {
            ticks: {
                 callback: function(value) { return value >= 1000000 ? value / 1000000 + 'M' : value; }
            }
        }
    }
}

const chartData2 = computed(() => {
    const items = Array.isArray(groupsSummary.value) ? groupsSummary.value : [];
    return {
        labels: items.map(item => item.name ? item.name.split(' ')[0] : 'N/A'),
        datasets: [
            {
                label: 'Vigente',
                backgroundColor: '#60a5fa', 
                data: items.map(item => item.vigente || 0)
            },
            {
                label: 'Devengado',
                backgroundColor: '#7dd3fc', 
                data: items.map(item => item.devengado || 0)
            }
        ]
    }
})

const chartOptions2 = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'top' },
        tooltip: {
            callbacks: {
                label: function(context) {
                    let label = context.dataset.label || '';
                    if (label) label += ': ';
                    if (context.parsed.y !== null) label += 'Q ' + formatMoney(context.parsed.y);
                    return label;
                }
            }
        }
    },
    scales: {
        y: {
            ticks: {
                 callback: function(value) { return value >= 1000000 ? value / 1000000 + 'M' : value; }
            }
        }
    }
}
</script>
