<template>
    <div class="animate-fade-in w-full pb-10">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-xl font-bold text-brand-dark dark:text-white flex items-center gap-2 mb-1">
                <ChartBarIcon class="w-6 h-6 text-primary" />
                Estadísticas y Análisis
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Visualización avanzada de datos de votaciones</p>
        </div>

        <!-- Filters Section -->
        <div class="relative z-20 bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-md p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 mb-6 font-sans">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 items-end">
                <div class="flex flex-col text-xs font-medium text-gray-700 dark:text-gray-300 gap-1.5">
                    <label class="flex items-center gap-1"><UserIcon class="w-3.5 h-3.5"/> Congresista</label>
                    <SearchableSelect 
                        v-model="filters.congresista_id" 
                        :options="congresistas"
                        placeholder="Buscar congresista..."
                        displayKey="nombre"
                        valueKey="id"
                    />
                </div>
                <div class="flex flex-col text-xs font-medium text-gray-700 dark:text-gray-300 gap-1.5">
                    <label class="flex items-center gap-1"><RectangleGroupIcon class="w-3.5 h-3.5"/> Bloque Político</label>
                    <SearchableSelect 
                        v-model="filters.bloque_id" 
                        :options="bloquesFormatted"
                        placeholder="Buscar bloque..."
                        displayKey="displayName"
                        valueKey="id"
                    />
                </div>
                <div class="flex flex-col text-xs font-medium text-gray-700 dark:text-gray-300 gap-1.5">
                    <label class="flex items-center gap-1"><CalendarIcon class="w-3.5 h-3.5"/> Evento</label>
                    <SearchableSelect 
                        v-model="filters.evento_id" 
                        :options="eventosFormatted"
                        placeholder="Buscar evento..."
                        displayKey="displayName"
                        valueKey="evento"
                    />
                </div>
                 <div class="flex flex-col gap-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 justify-end pb-[2px]">
                    <button @click="loadData" class="bg-indigo-500 hover:bg-indigo-600 text-white font-bold text-sm px-8 py-2.5 rounded-xl transition shadow-sm w-max flex items-center justify-center gap-2">
                        <MagnifyingGlassIcon class="w-4 h-4" /> Buscar
                    </button>
                </div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 animate-pulse">
            <div v-for="i in 2" :key="i" class="h-80 bg-gray-100 dark:bg-gray-800 rounded-3xl"></div>
        </div>
        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Pie Chart -->
            <div class="bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-md p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800">
                <h3 class="text-sm font-bold text-gray-600 dark:text-gray-300 flex items-center gap-2 mb-6">
                    <ChartPieIcon class="w-4 h-4" /> Distribución General
                </h3>
                <div class="h-64 flex items-center justify-center relative">
                    <Pie v-if="pieChartData" :data="pieChartData" :options="{ responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }" />
                    <div v-else class="text-sm text-gray-400">Sin datos</div>
                </div>
            </div>

             <!-- Bar Chart -->
             <div class="bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-md p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800">
                <h3 class="text-sm font-bold text-gray-600 dark:text-gray-300 flex items-center gap-2 mb-6">
                    <ChartBarIcon class="w-4 h-4" /> Top Bloques Activos
                </h3>
                <div class="h-64 flex items-end justify-center px-4 pb-4 pt-4 relative gap-2 border-l border-b border-gray-200 dark:border-gray-700">
                    <Bar v-if="barChartData" :data="barChartData" :options="{ responsive: true, maintainAspectRatio: false }" />
                    <div v-else class="text-sm text-gray-400 self-center">Sin datos</div>
                </div>
            </div>
        </div>

        <!-- Ranking Section -->
        <div v-if="!loading" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-md rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden p-6">
                <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2 mb-4">
                    <UserIcon class="w-4 h-4 text-emerald-500" /> Top Congresistas Participativos
                </h3>
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    <li v-for="(c, i) in topActivos" :key="i" class="py-3 flex justify-between items-center">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ i + 1 }}. {{ c.nombre }}</span>
                        <span class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 text-[10px] font-black px-2 py-1 rounded-md">{{ c.participaciones }} votos</span>
                    </li>
                </ul>
            </div>
            <div class="bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-md rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden p-6">
                <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2 mb-4">
                    <ExclamationTriangleIcon class="w-4 h-4 text-red-500" /> Top Congresistas Ausentes
                </h3>
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    <li v-for="(c, i) in topAusentes" :key="i" class="py-3 flex justify-between items-center">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ i + 1 }}. {{ c.nombre }}</span>
                        <span class="bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 text-[10px] font-black px-2 py-1 rounded-md">{{ c.ausencias }} faltas</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Warning Table Section -->
        <div class="bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-md rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <h3 class="p-6 pb-2 text-sm font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <ExclamationTriangleIcon class="w-4 h-4 text-red-500" /> Eventos Más Polémicos (Mayor Disparidad)
            </h3>
            <div class="overflow-x-auto mt-4 border-t border-gray-100 dark:border-gray-800">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/30 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400">
                            <th class="p-4">TÍTULO</th>
                            <th class="p-4 text-center">EVENTO</th>
                            <th class="p-4 text-center">FECHA</th>
                            <th class="p-4 text-center">FAVOR</th>
                            <th class="p-4 text-center">CONTRA</th>
                            <th class="p-4 text-center">DIFERENCIA</th>
                            <th class="p-4 text-center">RESULTADO</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800/50">
                        <tr v-for="e in polemicos" :key="e.evento" class="hover:bg-red-50/30 dark:hover:bg-red-900/10 transition">
                            <td class="p-4 text-gray-700 dark:text-gray-300 font-bold min-w-[300px] uppercase text-[10px] leading-relaxed">
                                {{ e.titulo }}
                            </td>
                            <td class="p-4 font-black text-blue-600 dark:text-blue-400 text-center">#{{ e.evento }}</td>
                            <td class="p-4 text-gray-500 dark:text-gray-400 font-mono text-center">{{ e.fecha }}</td>
                            <td class="p-4 text-center">
                                <span class="bg-emerald-600 text-white font-bold px-2 py-1 rounded-md text-[10px] shadow-sm">{{ e.favor }}</span>
                            </td>
                            <td class="p-4 text-center">
                                <span class="bg-red-600 text-white font-bold px-2 py-1 rounded-md text-[10px] shadow-sm">{{ e.contra }}</span>
                            </td>
                            <td class="p-4 text-center">
                                <span class="bg-amber-400 text-white font-bold px-2 py-1 rounded-md text-[10px] shadow-sm">{{ Math.abs(e.favor - e.contra) }}</span>
                            </td>
                            <td class="p-4 text-center">
                                <span :class="['px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest shadow-sm', e.resultado === 'RECHAZADO' ? 'bg-red-600 text-white' : 'bg-emerald-600 text-white']">
                                    {{ e.resultado }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive, computed } from 'vue';
import SearchableSelect from '@/components/common/SearchableSelect.vue';
import { 
    ChartBarIcon, 
    ChartPieIcon,
    ExclamationTriangleIcon,
    MagnifyingGlassIcon,
    CalendarDaysIcon,
    UserIcon,
    RectangleGroupIcon,
    CalendarIcon,
    CloudIcon
} from '@heroicons/vue/24/outline';
import VotacionesService from '@/services/votaciones/VotacionesService';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title } from 'chart.js'
import { Pie, Bar } from 'vue-chartjs'

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title)

const loading = ref(true);
const congresistas = ref([]);
const bloques = ref([]);
const allEventos = ref([]);
const estadisticas = ref(null);
const topAusentes = ref([]);
const topActivos = ref([]);
const filters = reactive({
    congresista_id: '',
    bloque_id: '',
    evento_id: ''
});

const bloquesFormatted = computed(() => {
    return bloques.value.map(b => ({
        ...b,
        displayName: b.siglas ? `${b.siglas} - ${b.nombre}` : b.nombre
    }));
});

const eventosFormatted = computed(() => {
    return allEventos.value.map(e => ({
        ...e,
        displayName: `#${e.evento} - ${e.titulo}`
    }));
});

const pieChartData = computed(() => {
    if (!estadisticas.value) return null;
    const d = estadisticas.value.distribucion;
    return {
        labels: ['A Favor', 'En Contra', 'Ausente'],
        datasets: [{
            backgroundColor: ['#059669', '#ef4444', '#f59e0b'],
            data: [d['A FAVOR'] || 0, d['EN CONTRA'] || 0, d['AUSENTE'] || 0]
        }]
    };
});

const barChartData = computed(() => {
    if (!estadisticas.value) return null;
    const tb = estadisticas.value.top_bloques;
    return {
        labels: tb.map(b => b.siglas || b.nombre.substring(0, 10)),
        datasets: [{
            label: 'Votos Emitidos',
            backgroundColor: '#3b82f6',
            data: tb.map(b => b.participaciones)
        }]
    };
});

const polemicos = computed(() => {
    return [...allEventos.value]
        .sort((a, b) => Math.abs(a.favor - a.contra) - Math.abs(b.favor - b.contra))
        .slice(0, 5);
});

const loadData = async () => {
    loading.value = true;
    try {
        const [congResp, blqResp, evResp, statResp] = await Promise.all([
            VotacionesService.getCongresistas(),
            VotacionesService.getBloques(),
            VotacionesService.getEventos(),
            VotacionesService.getEstadisticas(filters)
        ]);

        if (congResp.status === 'success') congresistas.value = congResp.data;
        if (blqResp.status === 'success') bloques.value = blqResp.data;
        if (evResp.status === 'success') allEventos.value = evResp.data;
        if (statResp.status === 'success') {
            estadisticas.value = statResp.data;
            topAusentes.value = statResp.data.top_ausentes;
            topActivos.value = statResp.data.top_activos;
        }
    } catch (error) {
        console.error('Error al cargar datos estadísticos:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(loadData);
</script>
