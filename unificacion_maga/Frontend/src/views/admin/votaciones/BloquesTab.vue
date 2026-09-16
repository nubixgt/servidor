<template>
    <div class="animate-fade-in w-full pb-10">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-xl font-bold text-brand-dark dark:text-white flex items-center gap-2 mb-1">
                <RectangleGroupIcon class="w-6 h-6 text-primary" />
                Bloques Políticos
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Estadísticas por bloque parlamentario</p>
            
            <div v-if="loading" class="grid grid-cols-1 md:grid-cols-4 gap-6 animate-pulse">
                <div v-for="i in 4" :key="i" class="h-24 bg-gray-200 dark:bg-gray-800 rounded-3xl"></div>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <StatCard :icon="RectangleGroupIcon" label="Total Bloques" :value="summary.bloques" color="indigo" />
                <StatCard :icon="UsersIcon" label="Congresistas" :value="summary.congresistas" color="cyan" />
                <StatCard :icon="CheckBadgeIcon" label="Votos Registrados" :value="Number(summary.votos).toLocaleString()" color="emerald" />
                <StatCard :icon="CursorArrowRaysIcon" label="Eventos" :value="summary.eventos" color="blue" />
            </div>
        </div>

        <!-- Bloques Grid -->
        <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div v-for="i in 8" :key="i" class="h-64 bg-gray-100 dark:bg-gray-800 rounded-3xl animate-pulse"></div>
        </div>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div v-for="bloque in bloques" :key="bloque.id" class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-lg transition-all text-left flex flex-col group overflow-hidden">
                <!-- Top Header -->
                <div class="p-6 pb-4 border-b border-gray-50 dark:border-gray-700/50 flex-grow">
                    <div class="flex items-center gap-3 mb-2">
                         <div class="w-8 h-8 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-500 flex flex-shrink-0 items-center justify-center">
                            <span class="font-black text-[10px]">{{ bloque.siglas }}</span>
                        </div>
                        <h3 class="font-bold text-blue-600 dark:text-blue-400 uppercase text-[10px] leading-tight transition">{{ bloque.nombre }}</h3>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mt-6">
                         <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-3 text-center">
                            <p class="text-2xl font-black text-brand-dark dark:text-white">{{ bloque.total_congresistas }}</p>
                            <p class="text-[10px] uppercase text-gray-500">Congresistas</p>
                         </div>
                         <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-3 text-center">
                            <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ Number(bloque.total_votos).toLocaleString() }}</p>
                            <p class="text-[10px] uppercase text-indigo-600/70">Total Votos</p>
                         </div>
                    </div>
                </div>

                <!-- Footer Stats -->
                <div class="grid grid-cols-3 divide-x divide-white dark:divide-gray-800">
                    <div class="bg-emerald-50/50 dark:bg-emerald-900/10 p-3 text-center">
                         <p class="text-xs font-black text-emerald-600">{{ Number(bloque.votos_favor).toLocaleString() }}</p>
                         <p class="text-[9px] uppercase mt-1 text-gray-600 dark:text-gray-400 font-bold">Favor</p>
                    </div>
                    <div class="bg-red-50/50 dark:bg-red-900/10 p-3 text-center">
                         <p class="text-xs font-black text-red-600">{{ Number(bloque.votos_contra).toLocaleString() }}</p>
                         <p class="text-[9px] uppercase mt-1 text-gray-600 dark:text-gray-400 font-bold">Contra</p>
                    </div>
                    <div class="bg-amber-50/50 dark:bg-amber-900/10 p-3 text-center">
                         <p class="text-xs font-black text-amber-500">{{ Number(bloque.ausencias).toLocaleString() }}</p>
                         <p class="text-[9px] uppercase mt-1 text-gray-600 dark:text-gray-400 font-bold">Aus.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { 
    UsersIcon, 
    RectangleGroupIcon,
    CheckBadgeIcon,
    FunnelIcon,
    MagnifyingGlassIcon,
    BarsArrowDownIcon,
    XCircleIcon,
    CursorArrowRaysIcon
} from '@heroicons/vue/24/outline';
import VotacionesService from '@/services/votaciones/VotacionesService';

const loading = ref(true);
const bloques = ref([]);
const summary = ref({ eventos: 0, congresistas: 0, bloques: 0, votos: 0 });

const loadData = async () => {
    loading.value = true;
    try {
        const [sumResp, blqResp] = await Promise.all([
            VotacionesService.getSummary(),
            VotacionesService.getBloques()
        ]);

        if (sumResp.status === 'success') {
            summary.value = sumResp.data;
        }

        if (blqResp.status === 'success') {
            bloques.value = blqResp.data;
        }
    } catch (error) {
        console.error('Error al cargar datos de bloques:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(loadData);
</script>
