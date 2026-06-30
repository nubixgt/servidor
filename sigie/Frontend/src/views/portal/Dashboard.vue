<template>
    <div class="animate-fade-in">
        <!-- KPI Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- Stat 1: Inspecciones Hoy -->
            <div class="bg-white/95 backdrop-blur-sm p-5 rounded-2xl border border-white/20 shadow-premium flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Inspecciones Hoy</span>
                    <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600">
                        <span class="material-symbols-outlined text-base">assignment</span>
                    </div>
                </div>
                <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ stats.total_checkins }}</h3>
                <span class="text-[10px] font-semibold text-emerald-600 flex items-center gap-0.5 mt-2">
                    <span class="material-symbols-outlined text-[10px]">trending_up</span>
                    +12% <span class="text-slate-400 ml-0.5">vs. ayer</span>
                </span>
            </div>

            <!-- Stat 2: No Conformidades -->
            <div class="bg-white/95 backdrop-blur-sm p-5 rounded-2xl border border-white/20 shadow-premium flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">No Conformidades</span>
                    <div class="w-8 h-8 bg-red-50 rounded-lg flex items-center justify-center text-red-500">
                        <span class="material-symbols-outlined text-base">warning</span>
                    </div>
                </div>
                <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ stats.checkins_novedades }}</h3>
                <span class="text-[10px] font-semibold text-red-500 flex items-center gap-0.5 mt-2">
                    <span class="material-symbols-outlined text-[10px]">error</span>
                    Acción requerida
                </span>
            </div>

            <!-- Stat 3: Pendientes -->
            <div class="bg-white/95 backdrop-blur-sm p-5 rounded-2xl border border-white/20 shadow-premium flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Pendientes</span>
                    <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center text-amber-600">
                        <span class="material-symbols-outlined text-base">assignment_late</span>
                    </div>
                </div>
                <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight">{{ stats.visitas_pendientes }}</h3>
                <span class="text-[10px] font-semibold text-slate-500 mt-2">Por validar</span>
            </div>

            <!-- Stat 4: Efectividad -->
            <div class="bg-white/95 backdrop-blur-sm p-5 rounded-2xl border border-white/20 shadow-premium flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Efectividad</span>
                    <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center text-emerald-600">
                        <span class="material-symbols-outlined text-base">verified</span>
                    </div>
                </div>
                <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight">98.2%</h3>
                <span class="text-[10px] font-semibold text-emerald-600 mt-2">Óptimo</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left/Center: Latest Records Table -->
            <div class="lg:col-span-2">
                <!-- Inspector Visitas Container -->
                <div v-if="auth.role === 'inspector'" class="bg-white/95 backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium mb-6">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-sm font-bold text-slate-800 font-headline">Mis Visitas Asignadas</h2>
                        <span class="text-[10px] font-bold text-blue-700 bg-blue-50 border border-blue-200 px-3 py-1 rounded-lg">Pendientes</span>
                    </div>

                    <div v-if="loadingVisitas" class="py-12 text-center text-xs text-slate-400">
                        <span class="material-symbols-outlined animate-spin text-lg text-blue-600 mb-1 block">sync</span>
                        Cargando visitas asignadas...
                    </div>
                    
                    <div v-else-if="visitas.length === 0" class="py-12 text-center">
                        <span class="material-symbols-outlined text-3xl text-emerald-500">task_alt</span>
                        <p class="text-sm font-bold text-slate-800 mt-3">¡Sin visitas pendientes!</p>
                        <p class="text-xs text-white/60 mt-1">Todas las inspecciones han sido completadas.</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div v-for="visita in visitas" :key="visita.id" class="p-4 rounded-xl border border-slate-100 hover:border-blue-100 hover:bg-blue-50/30 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <span class="text-[9px] font-bold uppercase text-slate-400 bg-slate-100 px-2 py-0.5 rounded">
                                    {{ visita.tipo_inspeccion }}
                                </span>
                                <h4 class="font-bold text-slate-800 mt-1.5 text-sm">{{ visita.establecimiento }}</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">place</span>
                                    {{ visita.direccion }}
                                </p>
                            </div>
                            <button @click="irACheckin(visita.id)" class="px-4 py-2 bg-[#0a192f] text-white font-bold text-xs rounded-xl hover:bg-[#122347] transition-colors flex items-center gap-1.5 self-start sm:self-center">
                                <span class="material-symbols-outlined text-xs">pin_drop</span>
                                Check-in
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Latest Records Table -->
                <div class="bg-white/95 backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
                        <div class="flex items-center gap-3">
                            <h2 class="text-sm font-bold text-slate-800 font-headline">Últimos Registros de Inspección</h2>
                            <button class="text-[10px] font-bold text-blue-600 bg-blue-50 border border-blue-200 px-3 py-1 rounded-lg hover:bg-blue-100 transition-colors">Ver todos</button>
                        </div>
                    </div>

                    <div v-if="loadingHistorial" class="py-12 text-center text-xs text-slate-400">
                        <span class="material-symbols-outlined animate-spin text-lg text-blue-600 mb-1 block">sync</span>
                        Cargando registros...
                    </div>
                    
                    <div v-else-if="filteredCheckins.length === 0" class="py-12 text-center text-xs text-slate-400">
                        No se encontraron registros recientes.
                    </div>

                    <div v-else class="overflow-x-auto -mx-6">
                        <div class="inline-block min-w-full align-middle px-6">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-left">
                                        <th class="py-3 pr-4">Establecimiento</th>
                                        <th class="py-3 px-4">Categoría</th>
                                        <th class="py-3 px-4">Hora</th>
                                        <th class="py-3 px-4">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 text-xs text-slate-700">
                                    <tr v-for="c in filteredCheckins" :key="c.id" class="hover:bg-slate-50/40 transition-colors">
                                        <td class="py-3.5 pr-4 font-semibold text-slate-800">{{ c.establecimiento || 'Inspección General' }}</td>
                                        <td class="py-3.5 px-4 text-slate-500">{{ c.inspector_nombre }}</td>
                                        <td class="py-3.5 px-4 text-slate-500 font-mono text-[11px]">{{ formatTime(c.fecha_hora) }}</td>
                                        <td class="py-3.5 px-4">
                                            <span 
                                                :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold', 
                                                         c.estado === 'exitoso' 
                                                            ? 'bg-emerald-50 text-emerald-700' 
                                                            : 'bg-red-50 text-red-700']"
                                            >
                                                {{ c.estado === 'exitoso' ? 'APROBADO' : 'OBSERVADO' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Activity Timeline -->
            <div class="lg:col-span-1">
                <div class="bg-white/95 backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium">
                    <h2 class="text-sm font-bold text-slate-800 font-headline mb-5">Actividad Reciente</h2>

                    <div v-if="loadingHistorial" class="py-8 text-center text-xs text-slate-400">
                        <span class="material-symbols-outlined animate-spin text-lg text-blue-600 mb-1 block">sync</span>
                        Cargando...
                    </div>
                    
                    <div v-else-if="timelineActivities.length === 0" class="py-8 text-center text-xs text-slate-400">
                        Sin actividad registrada hoy.
                    </div>

                    <div v-else class="space-y-5">
                        <div v-for="act in timelineActivities" :key="act.id" class="flex gap-3">
                            <div :class="['w-2 h-2 rounded-full mt-1.5 flex-shrink-0', act.type === 'danger' ? 'bg-red-500' : 'bg-emerald-500']"></div>
                            <div>
                                <h4 class="font-bold text-xs text-slate-800 leading-snug">{{ act.title }}</h4>
                                <p class="text-[10px] text-slate-500 mt-0.5 leading-relaxed">{{ act.description }}</p>
                                <span class="text-[9px] text-slate-400 font-semibold block mt-1 uppercase tracking-wider">{{ act.time }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Card for Inspector -->
                <div v-if="auth.role === 'inspector'" class="bg-[#0a192f] text-white p-5 rounded-2xl shadow-premium mt-5">
                    <h3 class="font-bold text-xs uppercase tracking-wider text-white/70">Módulo de Inspectores</h3>
                    <p class="text-[11px] text-white/50 mt-2 leading-relaxed">
                        Recuerda activar la geolocalización para registrar visitas.
                    </p>
                    <router-link to="/checkin" class="w-full py-2.5 bg-blue-500 hover:bg-blue-600 text-white font-bold text-center text-xs rounded-xl transition-all block mt-4">
                        + Nuevo Check-in
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../../stores/authStore.js';
import { useRouter } from 'vue-router';
import api from '../../services/api.js';

const auth = useAuthStore();
const router = useRouter();

const stats = ref({
    total_checkins: 0,
    checkins_novedades: 0,
    visitas_pendientes: 0,
    inspectores_activos: 0
});

const visitas = ref([]);
const recentCheckins = ref([]);
const loadingVisitas = ref(false);
const loadingHistorial = ref(false);
const searchQuery = ref('');

const fechaHoy = computed(() => {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const dateStr = new Date().toLocaleDateString('es-ES', options);
    return dateStr.charAt(0).toUpperCase() + dateStr.slice(1);
});

const fetchStats = async () => {
    try {
        const response = await api.get('/checkin/stats');
        if (response.data?.status === 'success') {
            stats.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar estadísticas', error);
    }
};

const fetchVisitasAsignadas = async () => {
    if (auth.role !== 'inspector') return;
    loadingVisitas.value = true;
    try {
        const response = await api.get('/inspectores/visitas/pendientes');
        if (response.data?.status === 'success') {
            visitas.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar visitas pendientes', error);
    } finally {
        loadingVisitas.value = false;
    }
};

const fetchRecentLogs = async () => {
    loadingHistorial.value = true;
    try {
        const response = await api.get('/checkin/historial');
        if (response.data?.status === 'success') {
            recentCheckins.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar historial para dashboard', error);
    } finally {
        loadingHistorial.value = false;
    }
};

const filteredCheckins = computed(() => {
    const sliced = recentCheckins.value.slice(0, 5);
    if (!searchQuery.value) return sliced;
    
    return recentCheckins.value.filter(c => {
        const term = searchQuery.value.toLowerCase();
        return c.id.toString().includes(term) ||
               (c.establecimiento && c.establecimiento.toLowerCase().includes(term)) ||
               c.inspector_nombre.toLowerCase().includes(term);
    }).slice(0, 5);
});

const timelineActivities = computed(() => {
    return recentCheckins.value.slice(0, 4).map((c, index) => {
        const times = ['Hace 5 minutos', 'Hace 45 minutos', 'Hace 2 horas', 'Hace 3 horas'];
        if (c.estado === 'exitoso') {
            return {
                id: c.id,
                title: `Inspección Finalizado`,
                description: `${c.establecimiento || 'Establecimiento'} - Finalizado con éxito`,
                time: times[index] || 'Hoy',
                type: 'success'
            };
        } else {
            return {
                id: c.id,
                title: `Alerta de Desviación`,
                description: `${c.observaciones?.substring(0, 50) || 'Novedades en'} ${c.establecimiento}`,
                time: times[index] || 'Hoy',
                type: 'danger'
            };
        }
    });
});

const formatSimpleDate = (dateTimeStr) => {
    if (!dateTimeStr) return '';
    const parts = dateTimeStr.split(' ');
    const dateParts = parts[0].split('-');
    if (dateParts.length < 3) return parts[0];
    const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    return `${dateParts[2]} ${months[parseInt(dateParts[1]) - 1]} ${dateParts[0]}`;
};

const formatTime = (dateTimeStr) => {
    if (!dateTimeStr) return '';
    const parts = dateTimeStr.split(' ');
    if (parts.length < 2) return '';
    const timeParts = parts[1].split(':');
    const hour = parseInt(timeParts[0]);
    const min = timeParts[1];
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${String(hour12).padStart(2, '0')}:${min} ${ampm}`;
};

const irACheckin = (visitaId) => {
    router.push({ name: 'CheckinRegister', query: { visita_id: visitaId } });
};

onMounted(() => {
    fetchStats();
    fetchVisitasAsignadas();
    fetchRecentLogs();
});
</script>
