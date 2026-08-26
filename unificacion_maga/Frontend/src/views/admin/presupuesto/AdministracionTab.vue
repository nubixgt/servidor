<template>
    <div class="animate-fade-in pb-10">
        
        <!-- Filters Banner -->
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md p-6 rounded-2xl mb-6 shadow-sm border border-white/80 dark:border-gray-800 transition-all flex justify-start gap-4 flex-wrap items-end">
             <div class="flex flex-col flex-1 max-w-sm">
                 <label class="text-[10px] uppercase font-bold text-gray-500 mb-2 ml-1 tracking-wider">Filtrar por Tipo:</label>
                 <select v-model="filterTipo" class="w-full border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-[#1E293B] text-gray-800 dark:text-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary/50 shadow-sm font-medium transition-shadow">
                     <option value="">Todos los registros</option>
                     <option v-for="tipo in uniqueTipos" :key="tipo" :value="tipo">{{ tipo }}</option>
                 </select>
            </div>
            <div class="flex flex-col flex-1 max-w-xs">
                 <label class="text-[10px] uppercase font-bold text-gray-500 mb-2 ml-1 tracking-wider">Buscar descripción:</label>
                 <div class="relative">
                     <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                     <input v-model="searchAdmin" type="text" placeholder="Buscar..." class="w-full pl-10 pr-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-[#1E293B] text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 shadow-sm" />
                 </div>
            </div>
            <div class="flex flex-col">
                <label class="text-[10px] uppercase font-bold text-gray-500 mb-2 ml-1 tracking-wider">Ordenar por %:</label>
                <div class="flex gap-1">
                    <button @click="sortOrder = 'desc'" :class="`px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-1.5 border transition-colors ${ sortOrder === 'desc' ? 'bg-brand-dark text-white border-brand-dark' : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700'}`">
                        <ChevronDownIcon class="w-4 h-4" /> Mayor
                    </button>
                    <button @click="sortOrder = 'asc'" :class="`px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-1.5 border transition-colors ${ sortOrder === 'asc' ? 'bg-brand-dark text-white border-brand-dark' : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700'}`">
                        <ChevronUpIcon class="w-4 h-4" /> Menor
                    </button>
                    <button @click="sortOrder = ''" v-if="sortOrder" :class="`px-3 py-3 rounded-xl text-sm font-bold border transition-colors bg-gray-50 dark:bg-gray-800 text-gray-400 border-gray-200 dark:border-gray-700 hover:bg-gray-100`">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>
            <button @click="clearFilters" class="mb-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-4 py-3 rounded-xl text-sm font-bold hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center gap-2">
                <XMarkIcon class="w-4 h-4" /> Limpiar
            </button>
        </div>

        <!-- Management Table Card -->
        <div class="bg-white dark:bg-[#1E293B] rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
             <!-- Header -->
             <div class="bg-brand-dark p-4 flex flex-col sm:flex-row justify-between items-center text-white gap-4">
                <h3 class="font-bold flex items-center gap-2"><CircleStackIcon class="w-5 h-5"/> Registros de Ejecución</h3>
                <span class="text-sm bg-white/20 px-3 py-1 rounded-lg">{{ filteredAdmin.length }} registros</span>
            </div>
            
            <div v-if="loading" class="p-12 text-center text-gray-400 font-sans flex flex-col items-center gap-3">
                <div class="w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
                Cargando registros...
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-right border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-800">
                            <th class="p-4 font-bold text-left w-16">ID</th>
                            <th class="p-4 font-bold text-left">Descripción</th>
                            <th class="p-4 font-bold text-left">Tipo</th>
                            <th class="p-4 font-bold">Ejercicio</th>
                            <th class="p-4 font-bold">Vigente</th>
                            <th class="p-4 font-bold">Devengado</th>
                            <th @click="toggleSort" class="p-4 font-bold text-center cursor-pointer select-none hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors group">
                                <span class="inline-flex items-center justify-center gap-1">
                                    % Ejecución
                                    <span class="transition-all">
                                        <ChevronUpIcon v-if="sortOrder === 'asc'" class="w-3.5 h-3.5 text-primary" />
                                        <ChevronDownIcon v-else-if="sortOrder === 'desc'" class="w-3.5 h-3.5 text-primary" />
                                        <span v-else class="text-gray-300 group-hover:text-gray-400">⇅</span>
                                    </span>
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 font-mono text-xs">
                        <tr v-if="pagedItems.length === 0">
                            <td colspan="7" class="p-8 text-center text-gray-400 font-sans">No se encontraron registros.</td>
                        </tr>
                        <tr v-for="item in pagedItems" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                            <td class="p-4 text-left font-sans text-gray-500">{{ item.id }}</td>
                            <td class="p-4 text-left font-sans font-medium text-gray-700 dark:text-gray-300">
                                {{ item.name }}
                            </td>
                            <td class="p-4 text-left font-sans">
                                 <span class="bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-widest border border-blue-100 dark:border-blue-800/30">
                                     {{ item.tipo }}
                                 </span>
                            </td>
                            <td class="p-4 text-center text-gray-500">{{ item.ejercicio_fiscal }}</td>
                            <td class="p-4 text-gray-600 dark:text-gray-400 whitespace-nowrap">Q {{ formatMoney(item.vigente) }}</td>
                            <td class="p-4 text-gray-800 dark:text-white font-bold whitespace-nowrap">Q {{ formatMoney(item.devengado) }}</td>
                            <td class="p-4 text-center font-sans whitespace-nowrap">
                                 <span :class="`px-2.5 py-1 rounded font-bold ${parseFloat(item.pct_ejec) >= 50 ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400' : 'bg-red-50 text-red-500 dark:bg-red-900/20 dark:text-red-400'}`">
                                    {{ parseFloat(item.pct_ejec).toFixed(2) }}%
                                 </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination footer -->
            <div class="bg-gray-50/50 dark:bg-gray-800/10 p-4 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center text-xs text-gray-500 flex-wrap gap-2">
                <span>
                    Mostrando {{ filteredAdmin.length === 0 ? 0 : (currentPage - 1) * pageSize + 1 }}
                    a {{ Math.min(currentPage * pageSize, filteredAdmin.length) }}
                    de {{ filteredAdmin.length }} registros
                </span>
                <div class="flex gap-1 items-center flex-wrap">
                    <button @click="currentPage = 1" :disabled="currentPage === 1"
                        class="px-2 py-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded disabled:opacity-40 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">«</button>
                    <button @click="currentPage--" :disabled="currentPage === 1"
                        class="px-3 py-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded disabled:opacity-40 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">Anterior</button>
                    
                    <button v-for="p in visiblePages" :key="p" @click="currentPage = p"
                        :class="`px-3 py-1 border rounded transition-colors ${p === currentPage ? 'bg-brand-dark text-white border-brand-dark' : 'bg-white dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600'}`">
                        {{ p }}
                    </button>

                    <button @click="currentPage++" :disabled="currentPage === totalPages"
                        class="px-3 py-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded disabled:opacity-40 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">Siguiente</button>
                    <button @click="currentPage = totalPages" :disabled="currentPage === totalPages"
                        class="px-2 py-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded disabled:opacity-40 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">»</button>

                    <select v-model="pageSize" class="ml-2 border border-gray-200 dark:border-gray-600 rounded px-2 py-1 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs">
                        <option :value="15">15 / pág</option>
                        <option :value="25">25 / pág</option>
                        <option :value="50">50 / pág</option>
                        <option :value="100">100 / pág</option>
                    </select>
                </div>
            </div>

        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, inject, watch } from 'vue';
import { 
    CircleStackIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
    ChevronUpIcon,
    ChevronDownIcon
} from '@heroicons/vue/24/outline';
import PresupuestoService from '@/services/presupuesto/PresupuestoService';

const formatMoney = (val) => {
    return Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const selectedYear = inject('selectedYear');
const loading = ref(true);
const allRecords = ref([]);
const searchAdmin = ref('');
const filterTipo = ref('');
const sortOrder = ref(''); // '' | 'asc' | 'desc'
const currentPage = ref(1);
const pageSize = ref(25);

const toggleSort = () => {
    if (sortOrder.value === '') sortOrder.value = 'desc';
    else if (sortOrder.value === 'desc') sortOrder.value = 'asc';
    else sortOrder.value = '';
};

// Fetch all records from the API — no tipo filter so we get everything
const loadData = async () => {
    loading.value = true;
    try {
        // Fetch each type separately and combine
        const types = ['UNIDAD_EJECUTORA', 'GRUPO_GASTO', 'FUENTE_FINANCIAMIENTO', 'MINISTERIO'];
        const results = await Promise.all(
            types.map(t => PresupuestoService.getDashboard({ tipo: t, ejercicio: selectedYear.value }).catch(() => null))
        );
        const combined = [];
        results.forEach((resp, idx) => {
            if (resp && resp.status === 'success' && resp.data && resp.data.items) {
                resp.data.items.forEach(item => {
                    combined.push({ ...item, tipo: types[idx].replace('_', ' ') });
                });
            }
        });
        allRecords.value = combined;
    } catch (error) {
        console.error('Error al cargar registros de administración:', error);
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

const uniqueTipos = computed(() => {
    const tipos = allRecords.value.map(i => i.tipo).filter(Boolean);
    return [...new Set(tipos)].sort();
});

const filteredAdmin = computed(() => {
    let items = allRecords.value;
    if (filterTipo.value) {
        items = items.filter(i => i.tipo === filterTipo.value);
    }
    const q = searchAdmin.value.trim().toLowerCase();
    if (q) {
        items = items.filter(i =>
            (i.name && i.name.toLowerCase().includes(q)) ||
            (i.tipo && i.tipo.toLowerCase().includes(q)) ||
            String(i.id).includes(q)
        );
    }
    if (sortOrder.value === 'asc') {
        items = [...items].sort((a, b) => parseFloat(a.pct_ejec || 0) - parseFloat(b.pct_ejec || 0));
    } else if (sortOrder.value === 'desc') {
        items = [...items].sort((a, b) => parseFloat(b.pct_ejec || 0) - parseFloat(a.pct_ejec || 0));
    }
    return items;
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredAdmin.value.length / pageSize.value)));

const pagedItems = computed(() => {
    const start = (currentPage.value - 1) * pageSize.value;
    return filteredAdmin.value.slice(start, start + pageSize.value);
});

const visiblePages = computed(() => {
    const total = totalPages.value;
    const cur = currentPage.value;
    const delta = 2;
    const pages = [];
    for (let i = Math.max(1, cur - delta); i <= Math.min(total, cur + delta); i++) {
        pages.push(i);
    }
    return pages;
});

// Reset to page 1 when filters change
watch([filterTipo, searchAdmin, pageSize, sortOrder], () => {
    currentPage.value = 1;
});

const clearFilters = () => {
    searchAdmin.value = '';
    filterTipo.value = '';
    sortOrder.value = '';
    currentPage.value = 1;
};
</script>
