<template>
    <div class="animate-fade-in w-full pb-10">
        <!-- Loading State -->
        <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-gray-400">
             <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4"></div>
             <p class="font-bold animate-pulse">Sincronizando con el Congreso...</p>
        </div>

        <template v-else>
            <!-- Quick Stats Row -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-4">
                     <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 text-indigo-500 flex items-center justify-center shrink-0">
                        <CalendarIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wide">Eventos</h3>
                        <p class="text-3xl font-black text-brand-dark dark:text-white">{{ summary.eventos }}</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-4">
                     <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 flex items-center justify-center shrink-0">
                        <UsersIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wide">Congresistas</h3>
                        <p class="text-3xl font-black text-brand-dark dark:text-white">{{ summary.congresistas }}</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-4">
                     <div class="w-12 h-12 rounded-xl bg-pink-50 dark:bg-pink-900/20 text-pink-500 flex items-center justify-center shrink-0">
                        <RectangleGroupIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wide">Bloques</h3>
                        <p class="text-3xl font-black text-brand-dark dark:text-white">{{ summary.bloques }}</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-4">
                     <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-blue-500 flex items-center justify-center shrink-0">
                        <CheckBadgeIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wide">Votos Totales</h3>
                        <p class="text-3xl font-black text-brand-dark dark:text-white">{{ Number(summary.votos).toLocaleString() }}</p>
                    </div>
                </div>
            </div>

            <!-- Latest Voting Events Table -->
            <div class="bg-white dark:bg-[#1E293B] rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50/30 dark:bg-gray-800/10">
                    <h3 class="text-lg font-bold text-brand-dark dark:text-white flex items-center gap-2">
                        <ClockIcon class="w-5 h-5 text-gray-400" />
                        Últimos Eventos de Votación
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-100/50 dark:bg-gray-800/50 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400">
                                <th class="p-4">EVENTO</th>
                                <th class="p-4">SESIÓN</th>
                                <th class="p-4">TÍTULO</th>
                                <th class="p-4">FECHA</th>
                                <th class="p-4 text-center">FAVOR</th>
                                <th class="p-4 text-center">CONTRA</th>
                                <th class="p-4 text-center">AUSENCIAS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800/50">
                            <tr v-if="eventos.length === 0">
                                <td colspan="7" class="p-10 text-center text-gray-400 italic">No hay eventos recientes registrados.</td>
                            </tr>
                            <tr v-for="evento in eventos" :key="evento.evento" class="hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition">
                                <td class="p-4 font-black text-blue-600 dark:text-blue-400">#{{ evento.evento }}</td>
                                <td class="p-4 text-gray-500 dark:text-gray-400 font-mono">{{ evento.sesion }}</td>
                                <td class="p-4 text-gray-700 dark:text-gray-300 font-bold min-w-[300px] uppercase text-[11px] leading-relaxed">{{ evento.titulo }}</td>
                                <td class="p-4 text-gray-500 dark:text-gray-400 font-mono whitespace-nowrap">{{ evento.fecha }}</td>
                                <td class="p-4 text-center">
                                    <span class="bg-emerald-600 text-white font-bold px-2 py-0.5 rounded text-[10px] shadow-sm">{{ evento.favor }}</span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="bg-red-600 text-white font-bold px-2 py-0.5 rounded text-[10px] shadow-sm">{{ evento.contra }}</span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="bg-amber-400 text-white font-bold px-2 py-0.5 rounded text-[10px] shadow-sm">{{ evento.ausencias }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { 
    CalendarIcon, 
    UsersIcon, 
    RectangleGroupIcon,
    CheckBadgeIcon,
    ClockIcon,
    EyeIcon
} from '@heroicons/vue/24/outline';
import VotacionesService from '@/services/votaciones/VotacionesService';

const loading = ref(true);
const eventos = ref([]);
const summary = ref({ eventos: 0, congresistas: 0, bloques: 0, votos: 0 });

const loadData = async () => {
    loading.value = true;
    try {
        const [sumResp, evResp] = await Promise.all([
            VotacionesService.getSummary(),
            VotacionesService.getEventos({ limit: 10 })
        ]);

        if (sumResp.status === 'success') {
            summary.value = sumResp.data;
        }

        if (evResp.status === 'success') {
            eventos.value = evResp.data;
        }
    } catch (error) {
        console.error('Error al cargar datos de votaciones:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(loadData);
</script>
