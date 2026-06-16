<template>
    <div>
        <!-- Welcome Section -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-on-surface">Panel de Control</h1>
                <p class="text-sm text-on-surface-variant mt-1">
                    Bienvenido de nuevo, <span class="font-semibold text-primary">{{ auth.user?.nombre }}</span>. 
                    Aquí está el resumen del estado actual de las inspecciones.
                </p>
            </div>
            <div class="flex items-center gap-3 bg-surface-container-low px-4 py-2 rounded-xl border border-surface-container/50">
                <span class="material-symbols-outlined text-primary">calendar_today</span>
                <span class="text-xs font-bold text-on-surface-variant">{{ fechaHoy }}</span>
            </div>
        </div>

        <!-- Admin Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <!-- Stat 1: Total Checkins -->
            <div class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-sky-500/5 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-sky-500/10 rounded-xl flex items-center justify-center text-sky-600">
                        <span class="material-symbols-outlined text-2xl">pin_drop</span>
                    </div>
                    <span class="text-xs font-bold text-sky-600 bg-sky-50 px-2.5 py-1 rounded-full">Histórico</span>
                </div>
                <h3 class="text-3xl font-black text-on-surface">{{ stats.total_checkins }}</h3>
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mt-1">Check-ins Totales</p>
            </div>

            <!-- Stat 2: Checkins con novedades -->
            <div class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-red-500/5 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-red-500/10 rounded-xl flex items-center justify-center text-red-600">
                        <span class="material-symbols-outlined text-2xl">warning</span>
                    </div>
                    <span class="text-xs font-bold text-red-600 bg-red-50 px-2.5 py-1 rounded-full">Alertas</span>
                </div>
                <h3 class="text-3xl font-black text-on-surface">{{ stats.checkins_novedades }}</h3>
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mt-1">Con Novedades</p>
            </div>

            <!-- Stat 3: Visitas Pendientes -->
            <div class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-amber-500/5 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-amber-500/10 rounded-xl flex items-center justify-center text-amber-600">
                        <span class="material-symbols-outlined text-2xl">assignment_late</span>
                    </div>
                    <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full">Programadas</span>
                </div>
                <h3 class="text-3xl font-black text-on-surface">{{ stats.visitas_pendientes }}</h3>
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mt-1">Visitas Pendientes</p>
            </div>

            <!-- Stat 4: Inspectores Activos -->
            <div class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-emerald-500/5 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-emerald-500/10 rounded-xl flex items-center justify-center text-emerald-600">
                        <span class="material-symbols-outlined text-2xl">badge</span>
                    </div>
                    <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full">Personal</span>
                </div>
                <h3 class="text-3xl font-black text-on-surface">{{ stats.inspectores_activos }}</h3>
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mt-1">Inspectores Activos</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Side: Custom view depending on role -->
            <div class="lg:col-span-2">
                <!-- Inspector: Scheduled Visitas List -->
                <div v-if="auth.role === 'inspector'" class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-extrabold text-on-surface">Mis Visitas Asignadas</h2>
                        <span class="text-xs font-bold text-primary bg-primary/10 px-3 py-1 rounded-full">Pendientes</span>
                    </div>

                    <div v-if="loadingVisitas" class="py-8 text-center text-sm text-on-surface-variant">
                        Cargando visitas asignadas...
                    </div>
                    
                    <div v-else-if="visitas.length === 0" class="py-12 text-center">
                        <span class="material-symbols-outlined text-4xl text-outline-variant">task_alt</span>
                        <p class="text-sm font-semibold text-on-surface mt-3">¡Excelente! No tienes visitas pendientes</p>
                        <p class="text-xs text-on-surface-variant mt-1">Todas tus inspecciones programadas han sido completadas.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="visita in visitas" :key="visita.id" class="p-4 rounded-xl border border-surface-container hover:border-primary/20 hover:bg-primary/5 transition-all flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <span class="text-[10px] font-extrabold uppercase text-outline-variant bg-surface-container-low px-2 py-0.5 rounded border border-surface-container">
                                    {{ visita.tipo_inspeccion }}
                                </span>
                                <h4 class="font-bold text-on-surface mt-2">{{ visita.establecimiento }}</h4>
                                <div class="flex items-center gap-2 text-xs text-on-surface-variant mt-1">
                                    <span class="material-symbols-outlined text-sm">place</span>
                                    <span>{{ visita.direccion }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-on-surface-variant mt-1">
                                    <span class="material-symbols-outlined text-sm text-amber-500">event</span>
                                    <span>Programado: {{ visita.fecha_programada }}</span>
                                </div>
                            </div>
                            <button @click="irACheckin(visita.id)" class="px-4 py-2.5 bg-primary text-on-primary font-bold text-xs rounded-xl shadow-md hover:bg-primary-dim transition-all flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-sm">pin_drop</span>
                                Realizar Check-in
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Admin: Recent Overview -->
                <div v-else class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient">
                    <h2 class="text-lg font-extrabold text-on-surface mb-4">Información del Sistema</h2>
                    <p class="text-sm text-on-surface-variant mb-6">
                        SIGIE está diseñado para el seguimiento en tiempo real del personal de inspección en campo. 
                        Los inspectores pueden registrar check-ins ingresando su geolocalización, adjuntando una foto como evidencia, 
                        y firmando digitalmente sobre la pantalla de su dispositivo móvil.
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 flex items-start gap-3">
                            <span class="material-symbols-outlined text-primary mt-0.5">verified_user</span>
                            <div>
                                <h4 class="font-bold text-sm text-on-surface">Control de Ubicación</h4>
                                <p class="text-xs text-on-surface-variant mt-1">Validación de coordenadas geográficas en cada visita.</p>
                            </div>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 flex items-start gap-3">
                            <span class="material-symbols-outlined text-primary mt-0.5">border_color</span>
                            <div>
                                <h4 class="font-bold text-sm text-on-surface">Firma Digital</h4>
                                <p class="text-xs text-on-surface-variant mt-1">Captura de firma manuscrita digitalizada para constancia oficial.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Sidebar card -->
            <div class="space-y-6">
                <div class="bg-gradient-to-br from-primary to-primary-dim text-on-primary p-6 rounded-2xl shadow-lg relative overflow-hidden">
                    <div class="absolute right-0 bottom-0 w-32 h-32 bg-white/5 rounded-full pointer-events-none"></div>
                    <h3 class="font-extrabold text-lg">Módulo Inspector</h3>
                    <p class="text-xs text-white/85 mt-2 leading-relaxed">
                        ¿Estás listo para salir al campo? Asegúrate de activar el permiso de geolocalización en tu navegador para registrar las coordenadas exactas de tus visitas.
                    </p>
                    <div class="mt-6 flex flex-col gap-2">
                        <router-link v-if="auth.role === 'inspector'" to="/checkin" class="w-full py-2.5 bg-white text-primary font-bold text-center text-xs rounded-xl shadow hover:bg-slate-50 transition-all">
                            Ir a Registrar Check-in
                        </router-link>
                        <router-link v-else to="/checkin-list" class="w-full py-2.5 bg-white text-primary font-bold text-center text-xs rounded-xl shadow hover:bg-slate-50 transition-all">
                            Ver Historial de Inspecciones
                        </router-link>
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
const loadingVisitas = ref(false);

const fechaHoy = computed(() => {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return new Date().toLocaleDateString('es-ES', options);
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

const irACheckin = (visitaId) => {
    router.push({ name: 'CheckinRegister', query: { visita_id: visitaId } });
};

onMounted(() => {
    fetchStats();
    fetchVisitasAsignadas();
});
</script>
