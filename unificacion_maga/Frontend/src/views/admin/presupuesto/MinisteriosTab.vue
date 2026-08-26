<template>
    <div class="animate-fade-in pb-10">
        <!-- MAGA Position Banner -->
        <div class="bg-blue-50/80 dark:bg-blue-900/20 backdrop-blur-md p-4 rounded-2xl mb-6 border border-blue-100 dark:border-blue-800/50 flex flex-col sm:flex-row gap-4 items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-800 text-blue-600 dark:text-blue-300 flex items-center justify-center shrink-0">
                    <InformationCircleIcon class="w-5 h-5" />
                </div>
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    <strong class="font-black">MAGA</strong> se encuentra en la posición <strong class="font-black">#{{ magaRank }}</strong> de {{ ministeriosList.length }} ministerios en ejecución presupuestaria
                </p>
            </div>
            <div>
                 <div class="bg-white dark:bg-[#1E293B] px-4 py-2 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col items-center">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">% EJECUCIÓN MAGA</span>
                    <span class="text-brand-dark dark:text-white font-black text-lg">{{ magaPct }}%</span>
                 </div>
            </div>
        </div>

        <!-- Comparative Bar Chart -->
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl p-6 rounded-3xl border border-white/80 dark:border-gray-700 shadow-sm mb-6">
            <div class="flex items-center gap-3 mb-6 border-b border-gray-100 dark:border-gray-700 pb-4">
                <BuildingLibraryIcon class="w-5 h-5 text-brand-dark dark:text-gray-300" />
                <h3 class="font-bold text-gray-800 dark:text-white text-sm">Comparativa de Ejecución por Ministerio</h3>
            </div>
            <div class="w-full h-80 relative">
                <Bar v-if="chartData" :data="chartData" :options="chartOptions" />
            </div>
        </div>

        <!-- Table Content -->
        <div class="bg-white dark:bg-[#1E293B] rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
             <div class="bg-brand-dark p-4 flex justify-between items-center text-white">
                <h3 class="font-bold flex items-center gap-2 text-sm"><BuildingLibraryIcon class="w-4 h-4"/> Ejecución Presupuestaria por Ministerio</h3>
                 <button class="bg-white/10 hover:bg-white/20 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-2">
                    <DocumentArrowDownIcon class="w-4 h-4" /> Excel
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-800">
                            <th class="p-4 font-bold text-left w-16">#</th>
                            <th class="p-4 font-bold text-left">Ministerio</th>
                            <th class="p-4 font-bold">Vigente</th>
                            <th class="p-4 font-bold">Devengado</th>
                            <th class="p-4 font-bold text-center">% Ejecución</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 font-mono text-xs">
                        <tr v-for="(item, index) in ministeriosList" :key="item.id" 
                            :class="`transition-colors ${item.code === 'MAGA' ? 'bg-blue-50/50 dark:bg-blue-900/10 hover:bg-blue-50 dark:hover:bg-blue-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-800/40'}`">
                            <td class="p-4 text-left font-sans text-gray-500 dark:text-gray-400">{{ index + 1 }}</td>
                            <td class="p-4 text-left font-sans font-bold text-brand-dark dark:text-gray-200">
                                {{ item.code }} <span class="text-xs font-normal text-gray-400 dark:text-gray-500 ml-2 hidden sm:inline">{{ item.name }}</span>
                            </td>
                            <td class="p-4 text-gray-500 whitespace-nowrap">Q {{ formatMoney(item.vigente) }}</td>
                            <td class="p-4 text-gray-800 dark:text-gray-200 font-bold whitespace-nowrap">Q {{ formatMoney(item.devengado) }}</td>
                            <td class="p-4 text-center font-sans whitespace-nowrap">
                                 <b :class="item.code === 'MAGA' ? 'text-blue-600 dark:text-blue-400' : 'text-emerald-600 dark:text-emerald-400'">{{ item.pct }}%</b>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, inject, watch } from 'vue';
import { 
    InformationCircleIcon,
    BuildingLibraryIcon,
    DocumentArrowDownIcon
} from '@heroicons/vue/24/outline';
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'
import PresupuestoService from '@/services/presupuesto/PresupuestoService';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const formatMoney = (val) => {
    return Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const loading = ref(true);
const ministeriosList = ref([]);
const selectedYear = inject('selectedYear');

const loadData = async () => {
    loading.value = true;
    try {
        const resp = await PresupuestoService.getDashboard({ tipo: 'MINISTERIO', ejercicio: selectedYear.value });
        if (resp.status === 'success' && resp.data) {
            const list = resp.data.items || [];
            ministeriosList.value = list.map(m => {
                const parts = m.name ? m.name.split(' "') : ['N/A', ''];
                return {
                    ...m,
                    code: parts[0],
                    name: parts[1] ? parts[1].replace('"', '') : m.name,
                    pct: m.pct_ejec
                };
            }).sort((a, b) => b.pct_ejec - a.pct_ejec);
        }
    } catch (error) {
        console.error('Error al cargar ministerios:', error);
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

const magaRank = computed(() => {
    const index = ministeriosList.value.findIndex(m => m.name.includes('AGRICULTURA') || m.name.includes('MAGA') || m.code === 'MAGA');
    return index !== -1 ? index + 1 : '--';
});

const magaPct = computed(() => {
    const maga = ministeriosList.value.find(m => m.name.includes('AGRICULTURA') || m.name.includes('MAGA') || m.code === 'MAGA');
    return maga ? maga.pct_ejec : '0.00';
});

const chartData = computed(() => {
    return {
        labels: ministeriosList.value.map(m => m.code),
        datasets: [
            {
                label: '% de Ejecución',
                // Make MAGA blue (#3b82f6) and others dark blue (#1e293b usually, let's use a nice slate-800 color)
                backgroundColor: ministeriosList.value.map(m => m.name.includes('AGRICULTURA') || m.name.includes('MAGA') || m.code === 'MAGA' ? '#3b82f6' : '#1e293b'),
                borderRadius: 4,
                data: ministeriosList.value.map(m => m.pct_ejec)
            }
        ]
    }
})

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false // hidden in the screenshot
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    return context.parsed.y + '% Ejecutado';
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            max: 100,
            ticks: {
                 callback: function(value) {
                     return value + '%';
                 }
            }
        }
    }
}
</script>
