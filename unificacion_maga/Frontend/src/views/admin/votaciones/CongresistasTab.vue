<template>
    <div class="animate-fade-in w-full pb-10">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-xl font-bold text-brand-dark dark:text-white flex items-center gap-2 mb-1">
                <UsersIcon class="w-6 h-6 text-primary" />
                Congresistas
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Gestión y estadísticas de congresistas</p>
            
            <div v-if="loading" class="grid grid-cols-1 md:grid-cols-3 gap-6 animate-pulse">
                <div v-for="i in 3" :key="i" class="h-24 bg-gray-200 dark:bg-gray-800 rounded-3xl"></div>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Card 1 -->
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-blue-500 flex items-center justify-center shrink-0">
                        <UsersIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Total Congresistas</h3>
                        <p class="text-3xl font-black text-blue-600 dark:text-blue-400">{{ stats.total }}</p>
                    </div>
                </div>
                <!-- Card 2 -->
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-500 flex items-center justify-center shrink-0">
                        <ChartPieIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Promedio Ausencias</h3>
                        <p class="text-3xl font-black text-amber-500">{{ Number(stats.promedio_ausencias || 0).toFixed(1) }}%</p>
                    </div>
                </div>
                <!-- Card 3 -->
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 flex items-center justify-center shrink-0">
                        <CheckBadgeIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Total Votos</h3>
                        <p class="text-3xl font-black text-blue-600 dark:text-blue-400">{{ Number(stats.total_votos || 0).toLocaleString() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-md p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 mb-6 font-sans">
             <h3 class="text-sm font-bold text-gray-600 dark:text-gray-300 flex items-center gap-2 mb-4">
                <FunnelIcon class="w-4 h-4" /> Filtros de Búsqueda
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 items-end">
                <div class="flex flex-col text-xs font-medium text-gray-700 dark:text-gray-300 gap-1.5 lg:col-span-2">
                    <label class="flex items-center gap-1"><MagnifyingGlassIcon class="w-3.5 h-3.5"/> Buscar por Nombre</label>
                    <input v-model="filters.search" @input="debouncedSearch" type="text" placeholder="Nombre del congresista..." class="w-full border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-[#1E293B] text-gray-700 dark:text-gray-200 text-sm px-4 py-2.5 focus:outline-none focus:border-indigo-500 transition-colors" />
                </div>
                <div class="flex flex-col text-xs font-medium text-gray-700 dark:text-gray-300 gap-1.5">
                    <label class="flex items-center gap-1"><RectangleStackIcon class="w-3.5 h-3.5"/> Bloque</label>
                    <select v-model="filters.bloque_id" @change="loadCongresistas" class="w-full border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-[#1E293B] text-gray-700 dark:text-gray-200 text-sm px-4 py-2.5 focus:outline-none focus:border-indigo-500 transition-colors">
                        <option value="">Todos los bloques</option>
                        <option v-for="b in bloques" :key="b.id" :value="b.id">{{ b.siglas }} - {{ b.nombre }}</option>
                    </select>
                </div>
                
                <div class="flex flex-col text-xs font-medium text-gray-700 dark:text-gray-300 gap-1.5">
                    <button @click="loadData" class="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-bold text-sm px-8 py-2.5 rounded-xl transition shadow-sm flex items-center justify-center gap-2">
                        <MagnifyingGlassIcon class="w-4 h-4" /> Refrescar
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Table -->
        <div class="bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-md rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden min-h-[500px]">
             <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-gray-400">
                <div class="w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin mb-4"></div>
                <p>Cargando congresistas...</p>
             </div>
             <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-100/50 dark:bg-gray-800/50 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400">
                            <th class="p-4">NOMBRE / BLOQUE</th>
                            <th class="p-4 text-center">VOTACIONES</th>
                            <th class="p-4 text-center">A FAVOR</th>
                            <th class="p-4 text-center">EN CONTRA</th>
                            <th class="p-4 text-center">AUSENCIAS</th>
                            <th class="p-4 text-center">% AUSENCIAS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-800/50">
                        <tr v-if="congresistas.length === 0">
                            <td colspan="6" class="p-10 text-center text-gray-400 italic">No se encontraron congresistas.</td>
                        </tr>
                        <tr v-for="c in paginatedCongresistas" :key="c.id" class="hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div v-if="!c.foto" class="w-8 h-8 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-400 flex items-center justify-center shrink-0">
                                        <UserIcon class="w-4 h-4" />
                                    </div>
                                    <img v-else :src="getFotoUrl(c.foto)" alt="Foto congresista" class="w-8 h-8 rounded-full object-cover shrink-0 border border-gray-200 dark:border-gray-700 shadow-sm cursor-zoom-in hover:scale-110 transition-transform" @click="selectedFoto = c" />
                                    <div class="flex flex-col">
                                        <span class="text-blue-600 dark:text-blue-400 font-bold hover:underline cursor-pointer leading-tight">{{ c.nombre }}</span>
                                        <span class="text-[9px] text-gray-400 uppercase font-black tracking-tighter">{{ c.bloque_siglas }} - {{ c.bloque_nombre }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-center font-bold text-gray-600 dark:text-gray-300">{{ c.total_votos }}</td>
                            <td class="p-4 text-center">
                                <span class="bg-emerald-600 text-white font-bold px-3 py-1 rounded-md text-[11px] shadow-sm">{{ c.votos_favor }}</span>
                            </td>
                            <td class="p-4 text-center">
                                <span class="bg-red-600 text-white font-bold px-3 py-1 rounded-md text-[11px] shadow-sm">{{ c.votos_contra }}</span>
                            </td>
                            <td class="p-4 text-center">
                                <span class="bg-amber-500 text-white font-bold px-3 py-1 rounded-md text-[11px] shadow-sm">{{ c.ausencias }}</span>
                            </td>
                            <td class="p-4 text-center">
                                <span class="bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 font-black px-3 py-1 rounded-md text-[11px] border border-amber-200 dark:border-amber-800/50">
                                    {{ Number((c.ausencias / (c.total_votos || 1)) * 100).toFixed(1) }}%
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-100 dark:border-gray-800 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-gray-500">
                <span>Mostrando {{ paginatedCongresistas.length }} de {{ congresistas.length }} congresistas</span>
                
                <div class="flex items-center gap-2" v-if="totalPages > 1">
                    <button @click="prevPage" :disabled="currentPage === 1" class="px-4 py-1.5 font-bold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30 transition">Anterior</button>
                    <span class="font-bold px-2">Página {{ currentPage }} de {{ totalPages }}</span>
                    <button @click="nextPage" :disabled="currentPage === totalPages" class="px-4 py-1.5 font-bold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30 transition">Siguiente</button>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal para visualizar foto en grande -->
    <teleport to="body">
        <div v-if="selectedFoto" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm" @click="selectedFoto = null" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;">
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden max-w-sm w-full animate-in zoom-in-95 duration-200" @click.stop>
                <div class="absolute top-3 right-3 z-10">
                    <button @click="selectedFoto = null" class="p-1 bg-gray-900/50 hover:bg-gray-900/80 text-white rounded-full transition backdrop-blur-md">
                        <XCircleIcon class="w-6 h-6" />
                    </button>
                </div>
                <img :src="getFotoUrl(selectedFoto.foto)" :alt="selectedFoto.nombre" class="w-full h-auto object-cover max-h-[70vh]" />
                <div class="p-4 bg-gray-50 dark:bg-gray-900 text-center">
                    <h3 class="font-bold text-gray-800 dark:text-gray-100">{{ selectedFoto.nombre }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 uppercase">{{ selectedFoto.bloque_siglas }} - {{ selectedFoto.bloque_nombre }}</p>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import { ref, onMounted, reactive, computed } from 'vue';
import { 
    UsersIcon, 
    ChartPieIcon,
    CheckBadgeIcon,
    FunnelIcon,
    MagnifyingGlassIcon,
    RectangleStackIcon,
    ExclamationTriangleIcon,
    CheckIcon,
    CalendarDaysIcon,
    BarsArrowDownIcon,
    XCircleIcon,
    UserIcon,
    ChartBarIcon
} from '@heroicons/vue/24/outline';
import VotacionesService from '@/services/votaciones/VotacionesService';

import { useRoute } from 'vue-router';

const getFotoUrl = (filename) => {
    if (!filename) return null;
    if (filename.startsWith('http')) return filename;
    
    // API_BASE de Vite si existe, de lo contrario fallback a la URL del backend real en este proyecto
    const apiUrl = import.meta.env.VITE_API_BASE || 'https://m.nubix.gt/unificacion_maga/Backend/api/v1';

    // Para la subida de archivos construimos la URL combinando:
    // Ejemplo prod: https://m.nubix.gt/unificacion_maga/Backend/... -> https://m.nubix.gt/unificacion_maga
    // Ejemplo prod: https://maga.nubix.gt/Backend/... -> https://maga.nubix.gt
    const baseUrl = apiUrl.split('/Backend')[0];
    
    return `${baseUrl}/uploads/congresistas/${filename}`;
};

const route = useRoute();
const loading = ref(true);
const congresistas = ref([]);
const bloques = ref([]);
const selectedFoto = ref(null);
const stats = ref({ total: 0, promedio_ausencias: 0, total_votos: 0 });
const filters = reactive({
    search: route.query.search || '',
    bloque_id: ''
});

// Paginación
const currentPage = ref(1);
const itemsPerPage = 20;

const paginatedCongresistas = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return congresistas.value.slice(start, end);
});

const totalPages = computed(() => {
    return Math.ceil(congresistas.value.length / itemsPerPage) || 1;
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
    searchTimeout = setTimeout(loadCongresistas, 500);
};

const loadCongresistas = async () => {
    try {
        const resp = await VotacionesService.getCongresistas(filters);
        if (resp.status === 'success') {
            congresistas.value = resp.data;
            currentPage.value = 1;
            
            // Recalcular estadísticas basadas en los filtros
            const total = resp.data.length;
            const total_votos = resp.data.reduce((acc, c) => acc + parseInt(c.total_votos || 0), 0);
            const total_ausencias = resp.data.reduce((acc, c) => acc + parseInt(c.ausencias || 0), 0);
            
            stats.value = {
                total,
                total_votos,
                promedio_ausencias: total_votos > 0 ? ((total_ausencias / total_votos) * 100) : 0
            };
        }
    } catch (error) {
        console.error('Error al cargar congresistas:', error);
    }
};

const loadData = async () => {
    loading.value = true;
    try {
        const [congResp, blqResp] = await Promise.all([
            VotacionesService.getCongresistas(filters),
            VotacionesService.getBloques()
        ]);

        if (congResp.status === 'success') {
            congresistas.value = congResp.data;
            currentPage.value = 1;
            
            // Recalcular estadísticas basadas en los filtros
            const total = congResp.data.length;
            const total_votos = congResp.data.reduce((acc, c) => acc + parseInt(c.total_votos || 0), 0);
            const total_ausencias = congResp.data.reduce((acc, c) => acc + parseInt(c.ausencias || 0), 0);
            
            stats.value = {
                total,
                total_votos,
                promedio_ausencias: total_votos > 0 ? ((total_ausencias / total_votos) * 100) : 0
            };
        }

        if (blqResp.status === 'success') {
            bloques.value = blqResp.data;
        }
    } catch (error) {
        console.error('Error al cargar datos de congresistas:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(loadData);
</script>
