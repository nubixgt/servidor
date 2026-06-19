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
            <div class="flex items-center gap-3 bg-white px-4 py-2 rounded-md border border-surface-container shadow-sm">
                <span class="material-symbols-outlined text-primary">calendar_today</span>
                <span class="text-xs font-bold text-on-surface-variant">{{ fechaHoy }}</span>
            </div>
        </div>

        <!-- Admin Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <!-- Stat 1: Total Checkins -->
            <div class="bg-white p-5 rounded-md border border-surface-container shadow-sm group">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-sky-50 border border-sky-200 rounded flex items-center justify-center text-sky-700">
                        <span class="material-symbols-outlined text-xl">pin_drop</span>
                    </div>
                    <span class="text-[10px] font-bold text-sky-700 bg-sky-50 border border-sky-200 px-2.5 py-0.5 rounded">Histórico</span>
                </div>
                <h3 class="text-2xl font-bold text-on-surface">{{ stats.total_checkins }}</h3>
                <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mt-1">Check-ins Totales</p>
            </div>

            <!-- Stat 2: Checkins con novedades -->
            <div class="bg-white p-5 rounded-md border border-surface-container shadow-sm group">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-red-50 border border-red-200 rounded flex items-center justify-center text-red-700">
                        <span class="material-symbols-outlined text-xl">warning</span>
                    </div>
                    <span class="text-[10px] font-bold text-red-700 bg-red-50 border border-red-200 px-2.5 py-0.5 rounded">Alertas</span>
                </div>
                <h3 class="text-2xl font-bold text-on-surface">{{ stats.checkins_novedades }}</h3>
                <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mt-1">Con Novedades</p>
            </div>

            <!-- Stat 3: Visitas Pendientes -->
            <div class="bg-white p-5 rounded-md border border-surface-container shadow-sm group">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-amber-50 border border-amber-200 rounded flex items-center justify-center text-amber-700">
                        <span class="material-symbols-outlined text-xl">assignment_late</span>
                    </div>
                    <span class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded">Programadas</span>
                </div>
                <h3 class="text-2xl font-bold text-on-surface">{{ stats.visitas_pendientes }}</h3>
                <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mt-1">Visitas Pendientes</p>
            </div>

            <!-- Stat 4: Inspectores Activos -->
            <div class="bg-white p-5 rounded-md border border-surface-container shadow-sm group">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-emerald-50 border border-emerald-200 rounded flex items-center justify-center text-emerald-700">
                        <span class="material-symbols-outlined text-xl">badge</span>
                    </div>
                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded">Personal</span>
                </div>
                <h3 class="text-2xl font-bold text-on-surface">{{ stats.inspectores_activos }}</h3>
                <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mt-1">Inspectores Activos</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Side: Custom view depending on role -->
            <div class="lg:col-span-2">
                <!-- Inspector: Scheduled Visitas List -->
                <div v-if="auth.role === 'inspector'" class="bg-white p-6 rounded-md border border-surface-container shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-base font-bold text-on-surface">Mis Visitas Asignadas</h2>
                        <span class="text-[10px] font-bold text-primary bg-primary/10 border border-primary/20 px-3 py-1 rounded">Pendientes</span>
                    </div>

                    <div v-if="loadingVisitas" class="py-8 text-center text-xs text-on-surface-variant">
                        Cargando visitas asignadas...
                    </div>
                    
                    <div v-else-if="visitas.length === 0" class="py-12 text-center">
                        <span class="material-symbols-outlined text-4xl text-outline-variant">task_alt</span>
                        <p class="text-sm font-bold text-on-surface mt-3">¡Excelente! No tienes visitas pendientes</p>
                        <p class="text-xs text-on-surface-variant mt-1">Todas tus inspecciones programadas han sido completadas.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="visita in visitas" :key="visita.id" class="p-4 rounded-md border border-surface-container hover:border-primary/30 hover:bg-slate-50 transition-colors flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <span class="text-[9px] font-bold uppercase text-outline-variant bg-surface-container-low px-2 py-0.5 rounded border border-surface-container">
                                    {{ visita.tipo_inspeccion }}
                                </span>
                                <h4 class="font-bold text-on-surface mt-2 text-sm">{{ visita.establecimiento }}</h4>
                                <div class="flex items-center gap-2 text-xs text-on-surface-variant mt-1">
                                    <span class="material-symbols-outlined text-sm">place</span>
                                    <span>{{ visita.direccion }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-on-surface-variant mt-1">
                                    <span class="material-symbols-outlined text-sm text-amber-500">event</span>
                                    <span>Programado: {{ visita.fecha_programada }}</span>
                                </div>
                            </div>
                            <button @click="irACheckin(visita.id)" class="px-4 py-2 bg-primary text-on-primary font-bold text-xs rounded hover:bg-primary-dim transition-colors flex items-center justify-center gap-1.5 border border-primary-dim">
                                <span class="material-symbols-outlined text-sm">pin_drop</span>
                                Realizar Check-in
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Admin: Recent Overview -->
                <div v-else class="bg-white p-6 rounded-md border border-surface-container shadow-sm">
                    <h2 class="text-base font-bold text-on-surface mb-3">Información del Sistema</h2>
                    <p class="text-xs text-on-surface-variant mb-6 leading-relaxed">
                        SIGIE está diseñado para el seguimiento en tiempo real del personal de inspección en campo. 
                        Los inspectores pueden registrar check-ins ingresando su geolocalización, adjuntando una foto como evidencia, 
                        y firmando digitalmente sobre la pantalla de su dispositivo móvil.
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 rounded-md bg-slate-50 border border-slate-200 flex items-start gap-3">
                            <span class="material-symbols-outlined text-primary mt-0.5">verified_user</span>
                            <div>
                                <h4 class="font-bold text-xs text-on-surface uppercase tracking-wider">Control de Ubicación</h4>
                                <p class="text-[11px] text-on-surface-variant mt-1">Validación de coordenadas geográficas en cada visita.</p>
                            </div>
                        </div>
                        <div class="p-4 rounded-md bg-slate-50 border border-slate-200 flex items-start gap-3">
                            <span class="material-symbols-outlined text-primary mt-0.5">border_color</span>
                            <div>
                                <h4 class="font-bold text-xs text-on-surface uppercase tracking-wider">Firma Digital</h4>
                                <p class="text-[11px] text-on-surface-variant mt-1">Captura de firma manuscrita digitalizada para constancia oficial.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Sidebar card -->
            <div class="space-y-6">
                <div class="bg-[#0b192c] text-white p-6 rounded-md border border-slate-800 shadow-sm">
                    <h3 class="font-bold text-base">Módulo Inspector</h3>
                    <p class="text-xs text-slate-300 mt-2 leading-relaxed">
                        ¿Estás listo para salir al campo? Asegúrate de activar el permiso de geolocalización en tu navegador para registrar las coordenadas exactas de tus visitas.
                    </p>
                    <div class="mt-6 flex flex-col gap-2">
                        <router-link v-if="auth.role === 'inspector'" to="/checkin" class="w-full py-2.5 bg-primary hover:bg-primary-dim text-white font-bold text-center text-xs rounded transition-all border border-primary-dim">
                            Ir a Registrar Check-in
                        </router-link>
                        <router-link v-else to="/checkin-list" class="w-full py-2.5 bg-primary hover:bg-primary-dim text-white font-bold text-center text-xs rounded transition-all border border-primary-dim">
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
