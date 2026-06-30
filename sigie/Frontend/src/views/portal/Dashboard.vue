<template>
    <div class="animate-fade-in">
        <!-- Welcome Section -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 font-headline">Hola, {{ auth.user?.nombre }}</h1>
                <p class="text-xs text-slate-500 mt-1">
                    Bienvenido al sistema institucional. Aquí tienes el estado actual y control de inspecciones en campo.
                </p>
            </div>
            <div class="flex items-center gap-2 bg-white px-4 py-2.5 rounded-xl border border-slate-100 shadow-premium self-start sm:self-center">
                <span class="material-symbols-outlined text-blue-600 text-lg">calendar_today</span>
                <span class="text-xs font-bold text-slate-600 font-headline">{{ fechaHoy }}</span>
            </div>
        </div>

        <!-- Premium KPI Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <!-- Stat 1: Total Inspecciones -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-premium flex flex-col justify-between group hover:scale-[1.01] transition-premium">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Inspecciones Hoy</span>
                        <div class="w-9 h-9 bg-blue-50 border border-blue-100 rounded-xl flex items-center justify-center text-blue-600">
                            <span class="material-symbols-outlined text-lg">assignment</span>
                        </div>
                    </div>
                    <h3 class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ stats.total_checkins }}</h3>
                </div>
                <div class="flex items-end justify-between mt-4">
                    <span class="text-[10px] font-bold text-emerald-600 flex items-center gap-0.5 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100">
                        <span class="material-symbols-outlined text-[10px] font-black">trending_up</span>
                        +12% <span class="text-[9px] text-slate-400 font-normal ml-0.5">vs. ayer</span>
                    </span>
                    <!-- Sparkline bars in SVG -->
                    <svg class="h-8 w-20 text-emerald-500" viewBox="0 0 100 30" fill="currentColor">
                        <rect x="5" y="18" width="10" height="12" rx="2" />
                        <rect x="20" y="15" width="10" height="15" rx="2" />
                        <rect x="35" y="20" width="10" height="10" rx="2" />
                        <rect x="50" y="10" width="10" height="20" rx="2" />
                        <rect x="65" y="8" width="10" height="22" rx="2" />
                        <rect x="80" y="2" width="10" height="28" rx="2" />
                    </svg>
                </div>
            </div>

            <!-- Stat 2: No Conformidades / Alertas -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-premium flex flex-col justify-between group hover:scale-[1.01] transition-premium">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">No Conformidades</span>
                        <div class="w-9 h-9 bg-red-50 border border-red-100 rounded-xl flex items-center justify-center text-red-600">
                            <span class="material-symbols-outlined text-lg">warning</span>
                        </div>
                    </div>
                    <h3 class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ stats.checkins_novedades }}</h3>
                </div>
                <div class="flex items-end justify-between mt-4">
                    <span class="text-[10px] font-bold text-emerald-600 flex items-center gap-0.5 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100">
                        <span class="material-symbols-outlined text-[10px] font-black">trending_down</span>
                        -5% <span class="text-[9px] text-slate-400 font-normal ml-0.5">en incidencia</span>
                    </span>
                    <!-- Red sparkline bars decreasing -->
                    <svg class="h-8 w-20 text-red-400" viewBox="0 0 100 30" fill="currentColor">
                        <rect x="5" y="2" width="10" height="28" rx="2" opacity="0.3" />
                        <rect x="20" y="6" width="10" height="24" rx="2" opacity="0.5" />
                        <rect x="35" y="12" width="10" height="18" rx="2" opacity="0.7" />
                        <rect x="50" y="10" width="10" height="20" rx="2" opacity="0.8" />
                        <rect x="65" y="16" width="10" height="14" rx="2" opacity="0.9" />
                        <rect x="80" y="20" width="10" height="10" rx="2" />
                    </svg>
                </div>
            </div>

            <!-- Stat 3: Supervisiones Pendientes -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-premium flex flex-col justify-between group hover:scale-[1.01] transition-premium">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Supervisiones Pendientes</span>
                        <div class="w-9 h-9 bg-amber-50 border border-amber-100 rounded-xl flex items-center justify-center text-amber-600">
                            <span class="material-symbols-outlined text-lg">assignment_late</span>
                        </div>
                    </div>
                    <h3 class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ stats.visitas_pendientes }}</h3>
                </div>
                <div class="mt-4">
                    <span class="text-[10px] font-bold text-slate-500 block mb-2">8 de alta prioridad</span>
                    <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-slate-800 rounded-full" style="width: 45%;"></div>
                    </div>
                </div>
            </div>

            <!-- Stat 4: Efectividad -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-premium flex flex-col justify-between group hover:scale-[1.01] transition-premium">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Efectividad</span>
                        <div class="w-9 h-9 bg-emerald-50 border border-emerald-100 rounded-xl flex items-center justify-center text-emerald-600">
                            <span class="material-symbols-outlined text-lg">verified</span>
                        </div>
                    </div>
                    <h3 class="text-3xl font-extrabold text-slate-900 tracking-tight">98.2%</h3>
                </div>
                <div class="mt-4">
                    <span class="text-[10px] font-bold text-emerald-600 block mb-2">Meta: 95%</span>
                    <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500 rounded-full" style="width: 98.2%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Side: Recent Activity Timeline -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Activity Timeline Card -->
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-premium">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider font-headline">Actividad Reciente</h2>
                        <a href="#" class="text-[10px] font-bold text-blue-600 hover:text-blue-700 transition-colors">Ver todo</a>
                    </div>

                    <div v-if="loadingHistorial" class="py-8 text-center text-xs text-slate-400">
                        <span class="material-symbols-outlined animate-spin text-lg text-blue-600 mb-1 block">sync</span>
                        Cargando registros recientes...
                    </div>
                    
                    <div v-else-if="timelineActivities.length === 0" class="py-8 text-center text-xs text-slate-400">
                        Sin actividad registrada hoy.
                    </div>

                    <div v-else class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-[1.5px] before:bg-slate-200 before:dashed">
                        <div v-for="act in timelineActivities" :key="act.id" class="relative group">
                            <!-- Bullet Point Indicator -->
                            <div :class="['absolute -left-[22px] top-0.5 w-3.5 h-3.5 rounded-full border bg-white flex items-center justify-center shadow-sm', 
                                         act.type === 'danger' ? 'border-red-500 text-red-500' : 'border-emerald-500 text-emerald-500']">
                                <span class="material-symbols-outlined text-[8px] font-black">{{ act.type === 'danger' ? 'close' : 'check' }}</span>
                            </div>
                            
                            <!-- Activity details -->
                            <div>
                                <h4 class="font-bold text-xs text-slate-800 hover:text-blue-600 transition-colors leading-snug">{{ act.title }}</h4>
                                <p class="text-[10px] text-slate-500 mt-1 leading-normal font-medium">{{ act.description }}</p>
                                <span class="text-[9px] text-slate-400 font-semibold block mt-1.5 uppercase tracking-wider font-mono">{{ act.time }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Card depending on Role -->
                <div v-if="auth.role === 'inspector'" class="bg-gradient-to-br from-[#0a192f] to-[#0f224b] text-white p-6 rounded-2xl border border-slate-800 shadow-premium flex flex-col justify-between">
                    <div>
                        <h3 class="font-extrabold text-sm uppercase tracking-wider font-headline text-slate-200">Módulo de Inspectores</h3>
                        <p class="text-xs text-slate-300 mt-2.5 leading-relaxed font-medium">
                            ¿Listo para realizar visitas técnicas? Recuerda activar la geolocalización de tu dispositivo para firmar el acta correspondiente en tiempo real.
                        </p>
                    </div>
                    <div class="mt-6">
                        <router-link to="/checkin" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-center text-xs rounded-xl transition-all duration-200 block shadow-md shadow-blue-900/30">
                            Registrar Nuevo Check-in
                        </router-link>
                    </div>
                </div>
            </div>

            <!-- Right Side: Scheduled Visitas or Latest Records Table -->
            <div class="lg:col-span-2">
                <!-- Inspector Visitas Container -->
                <div v-if="auth.role === 'inspector'" class="bg-white p-6 rounded-2xl border border-slate-100 shadow-premium">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider font-headline">Mis Visitas Asignadas</h2>
                        <span class="text-[10px] font-black text-blue-700 bg-blue-50 border border-blue-200 px-3 py-1 rounded-lg">Pendientes</span>
                    </div>

                    <div v-if="loadingVisitas" class="py-12 text-center text-xs text-slate-400">
                        <span class="material-symbols-outlined animate-spin text-lg text-blue-600 mb-1 block">sync</span>
                        Cargando visitas asignadas...
                    </div>
                    
                    <div v-else-if="visitas.length === 0" class="py-16 text-center">
                        <span class="material-symbols-outlined text-4xl text-emerald-500 bg-emerald-50 p-3 rounded-2xl border border-emerald-100">task_alt</span>
                        <p class="text-sm font-bold text-slate-800 mt-4">¡Excelente! No tienes visitas pendientes</p>
                        <p class="text-xs text-slate-500 mt-1.5 leading-normal max-w-[280px] mx-auto">Todas tus inspecciones programadas han sido completadas con éxito.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="visita in visitas" :key="visita.id" class="p-4 rounded-xl border border-slate-100 hover:border-blue-100 hover:bg-slate-50/50 transition-premium flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <span class="text-[9px] font-bold uppercase text-slate-400 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                    {{ visita.tipo_inspeccion }}
                                </span>
                                <h4 class="font-extrabold text-slate-800 mt-2 text-sm">{{ visita.establecimiento }}</h4>
                                <div class="flex items-center gap-1.5 text-[11px] text-slate-500 mt-1">
                                    <span class="material-symbols-outlined text-xs text-slate-400">place</span>
                                    <span>{{ visita.direccion }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-[11px] text-slate-500 mt-1">
                                    <span class="material-symbols-outlined text-xs text-amber-500">event</span>
                                    <span class="font-semibold text-slate-600">Programado: {{ visita.fecha_programada }}</span>
                                </div>
                            </div>
                            <button @click="irACheckin(visita.id)" class="px-4 py-2.5 bg-[#0a192f] text-white font-extrabold text-xs rounded-xl hover:bg-[#0f224b] transition-colors flex items-center justify-center gap-1.5 border border-slate-900 shadow-sm self-start sm:self-center">
                                <span class="material-symbols-outlined text-xs">pin_drop</span>
                                Realizar Check-in
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Admin & Global: Latest Records Table -->
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-premium" :class="{'mt-0': auth.role !== 'inspector'}">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                        <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider font-headline">Últimos Registros</h2>
                        <div class="relative w-full sm:w-60">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs">search</span>
                            <input 
                                v-model="searchQuery"
                                type="text" 
                                placeholder="Buscar registros..." 
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-1.5 text-[11px] font-semibold focus:border-blue-600 focus:bg-white transition-all outline-none text-slate-800" 
                            />
                        </div>
                    </div>

                    <div v-if="loadingHistorial" class="py-16 text-center text-xs text-slate-400">
                        <span class="material-symbols-outlined animate-spin text-2xl text-blue-600 mb-1 block">sync</span>
                        Cargando registros...
                    </div>
                    
                    <div v-else-if="filteredCheckins.length === 0" class="py-16 text-center text-xs text-slate-400">
                        No se encontraron registros de inspección recientes.
                    </div>

                    <div v-else class="overflow-x-auto -mx-6">
                        <div class="inline-block min-w-full align-middle px-6">
                            <table class="min-w-full border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider text-left">
                                        <th class="py-3 pr-4">ID</th>
                                        <th class="py-3 px-4">Tipo de Inspección</th>
                                        <th class="py-3 px-4">Inspector</th>
                                        <th class="py-3 px-4">Estado</th>
                                        <th class="py-3 pl-4">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 text-[11px] font-semibold text-slate-700">
                                    <tr v-for="c in filteredCheckins" :key="c.id" class="hover:bg-slate-50/40 transition-colors">
                                        <td class="py-3.5 pr-4 font-mono font-bold text-slate-800">#SIG-{{ c.id }}</td>
                                        <td class="py-3.5 px-4 font-bold text-slate-900">{{ c.establecimiento || 'Inspección General' }}</td>
                                        <td class="py-3.5 px-4 text-slate-500 font-medium">{{ c.inspector_nombre }}</td>
                                        <td class="py-3.5 px-4">
                                            <span 
                                                :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[9px] font-bold border', 
                                                         c.estado === 'exitoso' 
                                                            ? 'bg-emerald-50 border-emerald-100 text-emerald-700' 
                                                            : 'bg-red-50 border-red-100 text-red-700']"
                                            >
                                                <span :class="['w-1 h-1 rounded-full', c.estado === 'exitoso' ? 'bg-emerald-500' : 'bg-red-500']"></span>
                                                {{ c.estado === 'exitoso' ? 'Aprobado' : 'Alerta' }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 pl-4 text-slate-500 font-medium whitespace-nowrap">{{ formatSimpleDate(c.fecha_hora) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
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

// Dynamic search filtering
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

// Dynamic Activities timeline mapping
const timelineActivities = computed(() => {
    return recentCheckins.value.slice(0, 4).map((c, index) => {
        const times = ['Hace 15 minutos', 'Hace 1 hora', 'Hace 3 horas', 'Hace 5 horas'];
        if (c.estado === 'exitoso') {
            return {
                id: c.id,
                title: `Inspección de Rastro Finalizada`,
                description: `ID: #SIG-${c.id} • ${c.establecimiento || 'Establecimiento'} • Lote A-12`,
                time: times[index] || 'Hoy',
                type: 'success'
            };
        } else {
            return {
                id: c.id,
                title: `Desviación Detectada`,
                description: `${c.observaciones?.substring(0, 50) || 'Novedades reportadas en visita'} • ${c.establecimiento}`,
                time: times[index] || 'Hoy',
                type: 'danger'
            };
        }
    });
});

const formatSimpleDate = (dateTimeStr) => {
    if (!dateTimeStr) return '';
    // Format "2023-07-24 14:00" to "24 Jul 2023"
    const parts = dateTimeStr.split(' ');
    const dateParts = parts[0].split('-');
    if (dateParts.length < 3) return parts[0];
    const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    return `${dateParts[2]} ${months[parseInt(dateParts[1]) - 1]} ${dateParts[0]}`;
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
