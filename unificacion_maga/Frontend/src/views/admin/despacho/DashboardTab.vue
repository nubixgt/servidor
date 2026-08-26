<template>
    <div class="animate-fade-in w-full max-w-7xl mx-auto pb-12">
        <!-- Header -->
        <div class="mb-6 p-6 rounded-3xl bg-white/60 dark:bg-[#1E293B]/60 backdrop-blur-xl border border-white/80 dark:border-gray-800 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <h1 class="text-2xl font-brand font-black text-brand-dark dark:text-white tracking-wide">
                    Dashboard Ministra
                </h1>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Historial completo de actividades</p>
            </div>
            <div class="flex items-center gap-4 mt-4 md:mt-0">
                <div class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300" v-if="!loading">
                    <UserIcon class="w-4 h-4 text-primary" /> {{ listado.length }} Actividades encontradas
                </div>
                <button @click="$router.push('/admin/dashboard')" class="bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-bold hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    Volver
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-gray-400">
             <div class="w-12 h-12 border-4 border-primary border-t-transparent rounded-full animate-spin mb-4"></div>
             <p class="font-bold animate-pulse">Sincronizando con Despacho...</p>
        </div>

        <template v-else>
            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm text-center">
                    <p class="text-4xl font-black text-brand-dark dark:text-white mb-2">{{ resumen.total }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total actividades</p>
                </div>
                <div class="bg-red-50/50 dark:bg-red-900/10 backdrop-blur-md p-6 rounded-2xl border border-red-100 dark:border-red-900/30 shadow-sm text-center">
                    <p class="text-4xl font-black text-red-600 dark:text-red-400 mb-2">{{ resumen.criticas }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Críticas</p>
                </div>
                <div class="bg-amber-50/50 dark:bg-amber-900/10 backdrop-blur-md p-6 rounded-2xl border border-amber-100 dark:border-amber-900/30 shadow-sm text-center">
                    <p class="text-4xl font-black text-amber-600 dark:text-amber-400 mb-2">{{ resumen.en_progreso }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">En progreso</p>
                </div>
                <div class="bg-emerald-50/50 dark:bg-emerald-900/10 backdrop-blur-md p-6 rounded-2xl border border-emerald-100 dark:border-emerald-900/30 shadow-sm text-center">
                    <p class="text-4xl font-black text-emerald-600 dark:text-emerald-400 mb-2">{{ resumen.completadas }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Completadas</p>
                </div>
            </div>

            <!-- Categorías -->
            <h3 class="text-lg font-bold text-brand-dark dark:text-white mb-4 px-2">Actividades por Categoría</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-10">
                <div v-for="cat in categorias" :key="cat.name" class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md p-4 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col items-center justify-center text-center">
                    <ClipboardDocumentCheckIcon class="w-6 h-6 text-orange-400 mb-2" />
                    <p class="text-2xl font-black text-brand-dark dark:text-white mb-2">{{ cat.count }}</p>
                    <p class="text-[9px] text-gray-500 dark:text-gray-400 uppercase tracking-wide leading-tight px-1">{{ cat.name }}</p>
                </div>
            </div>

            <!-- Técnicos -->
            <h3 class="text-lg font-bold text-brand-dark dark:text-white mb-4 px-2">Estado de técnicos</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4 gap-4 mb-10">
                <div v-for="tecnico in tecnicos" :key="tecnico.name" class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md p-5 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col hover:shadow-md transition-all">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-emerald-600 flex items-center justify-center text-white shrink-0 shadow-sm">
                            <UserIcon class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="font-bold text-brand-dark dark:text-white text-sm uppercase">{{ tecnico.name }}</h4>
                            <p class="text-[10px] text-gray-500 italic">{{ tecnico.role }}</p>
                        </div>
                    </div>
                    
                    <button :class="`text-[10px] font-bold py-1.5 px-3 rounded-lg mb-6 self-start transition border ${tecnico.criticas > 0 ? 'bg-red-50 text-red-600 border-red-100' : 'bg-emerald-50 text-emerald-700 border-emerald-100'}`">
                        {{ tecnico.criticas > 0 ? 'Requiere atención' : 'Actividades al día' }}
                    </button>

                    <div class="space-y-2 text-xs font-medium mt-auto">
                        <div class="flex justify-between items-center text-gray-600 dark:text-gray-300">
                            <span>En progreso:</span>
                            <span class="font-black text-brand-dark dark:text-white">{{ tecnico.en_progreso }}</span>
                        </div>
                        <div class="flex justify-between items-center text-gray-600 dark:text-gray-300">
                            <span>Completadas:</span>
                            <span class="font-black text-brand-dark dark:text-white">{{ tecnico.completadas }}</span>
                        </div>
                        <div class="flex justify-between items-center text-gray-600 dark:text-gray-300">
                            <span>Críticas:</span>
                            <span :class="`font-black ${tecnico.criticas > 0 ? 'text-red-500' : 'text-brand-dark dark:text-white'}`">{{ tecnico.criticas }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-md rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden min-h-[400px]">
                <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <h3 class="text-xl font-bold text-brand-dark dark:text-white">Listado de actividades</h3>
                    
                    <div class="flex flex-col sm:flex-row items-center gap-4 text-sm w-full sm:w-auto">
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <span class="text-gray-500">Buscar:</span>
                            <input v-model="searchQuery" type="text" class="border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-[#1E293B] text-gray-700 dark:text-gray-200 px-3 py-1.5 outline-none focus:ring-2 focus:ring-primary/50 w-full max-w-[200px]" placeholder="Filtrar por título..." />
                        </div>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800">
                                <th class="p-4">ID</th>
                                <th class="p-4">TÉCNICO</th>
                                <th class="p-4">TÍTULO</th>
                                <th class="p-4">CATEGORÍA</th>
                                <th class="p-4">ESTADO</th>
                                <th class="p-4">PRIORIDAD</th>
                                <th class="p-4">INICIO</th>
                                <th class="p-4">FIN</th>
                                <th class="p-4">CREADA</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800/50">
                            <tr v-for="act in filteredList" :key="act.id" class="hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition group">
                                <td class="p-4 font-bold text-gray-400 dark:text-gray-600">#{{ act.id }}</td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-primary/20 text-primary flex items-center justify-center text-[10px] font-black uppercase">
                                            {{ act.tecnico.substring(0, 2) }}
                                        </div>
                                        <span class="text-gray-700 dark:text-gray-300 font-bold whitespace-nowrap">{{ act.tecnico }}</span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="text-brand-dark dark:text-gray-200 font-bold min-w-[200px]">{{ act.titulo }}</div>
                                    <div class="text-[10px] text-gray-400 line-clamp-1 max-w-[300px] mt-0.5 group-hover:line-clamp-none transition-all">{{ act.descripcion }}</div>
                                </td>
                                <td class="p-4">
                                    <span class="text-gray-600 dark:text-gray-400 font-medium bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">{{ act.categoria }}</span>
                                </td>
                                <td class="p-4">
                                    <span :class="`px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider whitespace-nowrap border ${getStatusClass(act.estado)}`">
                                        {{ act.estado }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span :class="`px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider whitespace-nowrap border ${getPriorityClass(act.prioridad)}`">
                                        {{ act.prioridad }}
                                    </span>
                                </td>
                                <td class="p-4 text-gray-500 dark:text-gray-400 font-mono whitespace-nowrap">{{ act.fechaInicio || 'N/A' }}</td>
                                <td class="p-4 text-gray-500 dark:text-gray-400 font-mono whitespace-nowrap">{{ act.fechaFin || 'N/A' }}</td>
                                <td class="p-4 text-gray-500 dark:text-gray-400 font-mono whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="font-bold">{{ act.creada.date }}</span>
                                        <span class="text-[10px] opacity-70">{{ act.creada.time }}</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="p-4 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center text-xs text-gray-500">
                    <span>Mostrando {{ filteredList.length }} de {{ listado.length }} registros</span>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { 
    UserIcon, 
    ClipboardDocumentCheckIcon,
    CalendarDaysIcon
} from '@heroicons/vue/24/outline';
import ActividadesService from '@/services/despacho/ActividadesService';

const loading = ref(true);
const listado = ref([]);
const resumen = ref({ total: 0, criticas: 0, en_progreso: 0, completadas: 0 });
const categorias = ref([]);
const tecnicos = ref([]);
const searchQuery = ref('');

const loadData = async () => {
    loading.value = true;
    try {
        const [actResp, statsResp] = await Promise.all([
            ActividadesService.getAll(),
            ActividadesService.getStats()
        ]);

        if (actResp.status === 'success') {
            listado.value = actResp.data;
        }

        if (statsResp.status === 'success') {
            resumen.value = {
                total: statsResp.data.total,
                criticas: statsResp.data.criticas,
                en_progreso: statsResp.data.en_progreso,
                completadas: statsResp.data.completadas
            };
            categorias.value = statsResp.data.categorias;
            tecnicos.value = statsResp.data.tecnicos;
        }
    } catch (error) {
        console.error('Error al cargar datos de despacho:', error);
    } finally {
        loading.value = false;
    }
};

const filteredList = computed(() => {
    if (!searchQuery.value) return listado.value;
    return listado.value.filter(act => 
        act.titulo.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        act.tecnico.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        act.categoria.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const getStatusClass = (status) => {
    switch(status) {
        case 'COMPLETADA': return 'bg-emerald-100/80 text-emerald-700 border-emerald-200/50';
        case 'CRITICA': return 'bg-red-100/80 text-red-700 border-red-200/50';
        case 'EN PROGRESO': return 'bg-amber-100/80 text-amber-700 border-amber-200/50';
        default: return 'bg-gray-100/80 text-gray-700 border-gray-200/50';
    }
};

const getPriorityClass = (priority) => {
    switch(priority) {
        case 'URGENTE': return 'bg-purple-100/80 text-purple-700 border-purple-200/50';
        case 'ALTA': return 'bg-orange-100/80 text-orange-700 border-orange-200/50';
        case 'MEDIA': return 'bg-blue-100/80 text-blue-700 border-blue-200/50';
        default: return 'bg-gray-100/80 text-gray-700 border-gray-200/50';
    }
};

onMounted(loadData);
</script>
