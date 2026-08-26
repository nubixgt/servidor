<template>
    <div class="animate-fade-in w-full pb-10">
        <!-- Header / Summary Cards -->
        <div class="mb-6">
            <h2 class="text-xl font-bold text-brand-dark dark:text-white flex items-center gap-2 mb-1">
                <CalendarIcon class="w-6 h-6 text-primary" />
                Eventos de Votación
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Registro completo de eventos y votaciones del Congreso</p>
            
            <!-- Loading State for Summary -->
            <div v-if="loading" class="grid grid-cols-1 md:grid-cols-3 gap-6 animate-pulse">
                <div v-for="i in 3" :key="i" class="h-24 bg-gray-200 dark:bg-gray-800 rounded-3xl"></div>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Card 1 -->
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 text-indigo-500 flex items-center justify-center shrink-0">
                        <CheckBadgeIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Total Eventos</h3>
                        <p class="text-3xl font-black text-indigo-600 dark:text-indigo-400">{{ summary.eventos }}</p>
                    </div>
                </div>
                <!-- Card 2 -->
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 flex items-center justify-center shrink-0">
                        <CheckCircleIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">A favor totales</h3>
                        <p class="text-3xl font-black text-emerald-500">{{ summary.eventos_aprobados || summary.eventos }}</p>
                    </div>
                </div>
                <!-- Card 3 -->
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-blue-500 flex items-center justify-center shrink-0">
                        <CheckBadgeIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Votos Procesados</h3>
                        <p class="text-3xl font-black text-blue-500">{{ Number(summary.votos).toLocaleString() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-md p-5 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 mb-6 font-sans">
            <div class="grid grid-cols-1 md:grid-cols-5 xl:grid-cols-6 gap-4 items-end">
                <div class="flex flex-col col-span-1 md:col-span-2 xl:col-span-2">
                    <label class="text-[11px] font-medium text-gray-500 mb-1 flex items-center gap-1"><MagnifyingGlassIcon class="w-3.5 h-3.5"/> Buscar</label>
                    <input v-model="filters.search" @input="debouncedSearch" type="text" placeholder="Número, título o sesión..." class="w-full border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-[#1E293B] text-gray-700 dark:text-gray-200 text-sm px-4 py-2 focus:outline-none focus:border-indigo-500 transition-colors" />
                </div>
                <div class="flex flex-col">
                    <label class="text-[11px] font-medium text-gray-500 mb-1">Resultado</label>
                    <select v-model="filters.resultado" @change="loadEventos" class="w-full border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-[#1E293B] text-gray-700 dark:text-gray-200 text-sm px-4 py-2 focus:outline-none focus:border-indigo-500 transition-colors">
                        <option value="">Todos</option>
                        <option value="APROBADO">Aprobado</option>
                        <option value="RECHAZADO">Rechazado</option>
                    </select>
                </div>
                <div class="col-span-1 md:col-span-2 xl:col-span-1 flex items-end gap-2">
                    <button @click="loadData" class="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-bold py-2 px-4 rounded-xl transition shadow-sm flex items-center justify-center gap-2">
                        <FunnelIcon class="w-4 h-4" />
                        Refrescar
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Table -->
        <div class="bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-md rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden min-h-[500px]">
            <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-gray-400">
                <div class="w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                <p>Cargando eventos...</p>
            </div>
            
            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-100/50 dark:bg-gray-800/50 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400">
                            <th class="p-4">EVENTO</th>
                            <th class="p-4">SESIÓN</th>
                            <th class="p-4">TÍTULO</th>
                            <th class="p-4">FECHA</th>
                            <th class="p-4 text-center">A FAVOR</th>
                            <th class="p-4 text-center">EN CONTRA</th>
                            <th class="p-4 text-center">RESULTADO</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800/50">
                        <tr v-if="eventos.length === 0">
                            <td colspan="7" class="p-10 text-center text-gray-400 italic">No se encontraron eventos con los filtros seleccionados.</td>
                        </tr>
                        <tr v-for="evento in paginatedEventos" :key="evento.evento" class="hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition">
                            <td class="p-4 font-black text-blue-600 dark:text-blue-400">#{{ evento.evento }}</td>
                            <td class="p-4 text-gray-500 dark:text-gray-400 font-mono">{{ evento.sesion }}</td>
                            <td class="p-4 text-gray-700 dark:text-gray-300 font-bold min-w-[300px] uppercase text-[11px] leading-tight">{{ evento.titulo }}</td>
                            <td class="p-4 text-gray-500 dark:text-gray-400 font-mono whitespace-nowrap">{{ evento.fecha }}</td>
                            <td class="p-4 text-center">
                                <span class="bg-emerald-600 text-white font-bold px-3 py-1 rounded-md text-[11px] shadow-sm">{{ evento.favor }}</span>
                            </td>
                            <td class="p-4 text-center">
                                <span class="bg-red-600 text-white font-bold px-3 py-1 rounded-md text-[11px] shadow-sm">{{ evento.contra }}</span>
                            </td>
                            <td class="p-4 text-center">
                                <span :class="`border font-bold px-2 py-1 rounded-[4px] text-[9px] uppercase tracking-wider ${evento.resultado === 'APROBADO' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-red-100 text-red-700 border-red-200'}`">
                                    {{ evento.resultado }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="p-4 border-t border-gray-100 dark:border-gray-800 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-gray-500">
                <span>Mostrando {{ paginatedEventos.length }} de {{ eventos.length }} eventos</span>
                
                <div class="flex items-center gap-2" v-if="totalPages > 1">
                    <button @click="prevPage" :disabled="currentPage === 1" class="px-4 py-1.5 font-bold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30 transition">Anterior</button>
                    <span class="font-bold px-2">Página {{ currentPage }} de {{ totalPages }}</span>
                    <button @click="nextPage" :disabled="currentPage === totalPages" class="px-4 py-1.5 font-bold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30 transition">Siguiente</button>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, onMounted, reactive, computed } from 'vue';
import { 
    CalendarIcon, 
    CheckBadgeIcon,
    CheckCircleIcon,
    XCircleIcon,
    EyeIcon,
    MagnifyingGlassIcon,
    CalendarDaysIcon,
    FunnelIcon,
    BarsArrowDownIcon
} from '@heroicons/vue/24/outline';
import VotacionesService from '@/services/votaciones/VotacionesService';

const loading = ref(true);
const eventos = ref([]);
const summary = ref({ eventos: 0, congresistas: 0, bloques: 0, votos: 0 });
const filters = reactive({
    search: '',
    resultado: ''
});

// Paginación
const currentPage = ref(1);
const itemsPerPage = 20;

const paginatedEventos = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return eventos.value.slice(start, end);
});

const totalPages = computed(() => {
    return Math.ceil(eventos.value.length / itemsPerPage) || 1;
});

const prevPage = () => {
    if (currentPage.value > 1) currentPage.value--;
};

const nextPage = () => {
    if (currentPage.value < totalPages.value) currentPage.value++;
};

let searchTimeout = null;
const debouncedSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(loadEventos, 500);
};

const loadEventos = async () => {
    try {
        const resp = await VotacionesService.getEventos({
            search: filters.search,
            resultado: filters.resultado
        });
        if (resp.status === 'success') {
            eventos.value = resp.data;
            currentPage.value = 1;
            
            // Recalcular estadísticas dinámicas
            const total = resp.data.length;
            const aprobados = resp.data.filter(e => e.resultado === 'APROBADO').length;
            const votos = resp.data.reduce((acc, e) => acc + parseInt(e.favor || 0) + parseInt(e.contra || 0) + parseInt(e.ausente || 0) + parseInt(e.abstencion || 0), 0);
            
            summary.value = {
                eventos: total,
                eventos_aprobados: aprobados,
                votos: votos
            };
        }
    } catch (error) {
        console.error('Error al cargar eventos:', error);
    }
};

const loadData = async () => {
    loading.value = true;
    try {
        const [sumResp, evResp] = await Promise.all([
            VotacionesService.getSummary(),
            VotacionesService.getEventos(filters)
        ]);

        if (sumResp.status === 'success' && !filters.search && !filters.resultado) {
            // Solo usar el summary global si no hay filtros, aunque lo sobreescribiremos abajo de todos modos
            summary.value = sumResp.data;
        }

        if (evResp.status === 'success') {
            eventos.value = evResp.data;
            currentPage.value = 1;
            
            // Recalcular estadísticas dinámicas
            const total = evResp.data.length;
            const aprobados = evResp.data.filter(e => e.resultado === 'APROBADO').length;
            const votos = evResp.data.reduce((acc, e) => acc + parseInt(e.favor || 0) + parseInt(e.contra || 0) + parseInt(e.ausente || 0) + parseInt(e.abstencion || 0), 0);
            
            summary.value = {
                eventos: total,
                eventos_aprobados: aprobados,
                votos: votos
            };
        }
    } catch (error) {
        console.error('Error al cargar datos de votaciones:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(loadData);
</script>
