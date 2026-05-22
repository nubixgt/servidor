<template>
    <div class="space-y-10 animate-in fade-in duration-300">
        <!-- Header Section -->
        <header class="flex flex-col md:flex-row md:justify-between md:items-end gap-6">
            <div class="max-w-2xl">
                <h1 class="text-4xl font-extrabold text-on-surface tracking-tight mb-2 font-headline">Dashboard Técnico</h1>
                <p class="text-on-surface-variant leading-relaxed">
                    Bienvenido, <span class="text-primary font-bold">{{ authStore.user?.nombre || 'Técnico' }}</span>. Aquí tienes el estado actual de tu categoría asignada.
                </p>
            </div>
            <div class="flex space-x-4">
                <div class="bg-surface-container-low px-4 py-2.5 rounded-xl flex items-center space-x-3 border border-outline-variant/30 shadow-sm">
                    <span class="material-symbols-outlined text-primary">calendar_today</span>
                    <span class="text-sm font-bold text-on-surface capitalize">{{ todayString }}</span>
                </div>
            </div>
        </header>

        <!-- Bento Grid -->
        <div class="grid grid-cols-12 gap-6">

            <!-- Personal KPIs: Mis Tareas -->
            <section class="col-span-12 lg:col-span-4 space-y-6">
                <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/20 shadow-sm flex flex-col justify-between h-full min-h-[400px]">
                    <div>
                        <h3 class="text-lg font-bold mb-6 flex items-center space-x-2 font-headline">
                            <span class="material-symbols-outlined text-primary">assignment_ind</span>
                            <span>Mis Tareas Asignadas</span>
                        </h3>
                        <div class="space-y-3 overflow-y-auto max-h-[250px] pr-1">
                            <div v-for="t in misTareas" :key="t.id" class="flex items-center justify-between p-3.5 bg-surface-container-low rounded-xl border border-outline-variant/10 hover:border-primary/20 transition-all group">
                                <div class="flex items-start space-x-2.5 min-w-0 flex-1">
                                    <span class="h-2 w-2 rounded-full mt-1.5 shrink-0" :class="t.prioridad === 'Crítica' || t.prioridad === 'Alta' ? 'bg-error' : 'bg-primary'"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-bold text-on-surface truncate group-hover:text-primary transition-colors" :title="t.titulo">{{ t.titulo }}</p>
                                        <p class="text-[9px] text-on-surface-variant/60 font-mono mt-0.5">Límite: {{ t.fecha_limite }}</p>
                                    </div>
                                </div>
                                <button @click="quickCompleteTask(t.id)" class="ml-2 w-7 h-7 flex items-center justify-center bg-background hover:bg-primary-container text-on-surface-variant hover:text-on-primary-container border border-outline-variant/30 rounded-lg transition-all" title="Marcar como Completada">
                                    <span class="material-symbols-outlined text-xs">check</span>
                                </button>
                            </div>
                            <div v-if="misTareas.length === 0" class="text-center py-8 text-xs text-on-surface-variant/50">
                                <span class="material-symbols-outlined text-3xl text-outline-variant/40 mb-2 block">task_alt</span>
                                ¡Excelente trabajo!<br/>No tienes tareas pendientes asignadas.
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 pt-6 border-t border-outline-variant/10">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/50">Eficiencia de Gestión</span>
                            <span class="text-2xl font-black text-primary font-headline font-mono">{{ personalEfficiency }}%</span>
                        </div>
                        <div class="w-full bg-background h-2 rounded-full overflow-hidden border border-outline-variant/10">
                            <div class="bg-primary h-full rounded-full transition-all duration-700" :style="{ width: personalEfficiency + '%' }"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Module Status -->
            <section class="col-span-12 lg:col-span-8 bg-surface-container/60 p-8 rounded-3xl border border-outline-variant/20 shadow-sm space-y-8 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-2xl font-bold mb-1 font-headline">Módulo: {{ getModuleName(authStore.user?.categoria) }}</h2>
                            <p class="text-sm text-on-surface-variant">Indicadores de rendimiento de área en tiempo real</p>
                        </div>
                        <span class="bg-primary-container text-primary border border-primary/10 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest">Estado: Activo</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/25 flex flex-col">
                            <span class="text-xs font-semibold text-on-surface-variant mb-4">Inspecciones / Actividades</span>
                            <div class="flex items-end space-x-2">
                                <span class="text-4xl font-black font-headline font-mono">{{ getModuleStat1() }}</span>
                                <span class="text-primary text-xs font-bold flex items-center mb-1 font-mono">
                                    <span class="material-symbols-outlined text-sm">trending_up</span> Activo
                                </span>
                            </div>
                        </div>
                        <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/25 flex flex-col">
                            <span class="text-xs font-semibold text-on-surface-variant mb-4">Eficiencia Global</span>
                            <div class="flex items-end space-x-2">
                                <span class="text-4xl font-black font-headline font-mono">92.4%</span>
                                <span class="text-primary text-xs font-bold flex items-center mb-1 font-mono">
                                    <span class="material-symbols-outlined text-sm">trending_up</span> +0.4
                                </span>
                            </div>
                        </div>
                        <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/25 flex flex-col">
                            <span class="text-xs font-semibold text-on-surface-variant mb-4">Alertas Pendientes</span>
                            <div class="flex items-end space-x-2">
                                <span class="text-4xl font-black font-headline font-mono">{{ kpis.tareasVencidas }}</span>
                                <span v-if="kpis.tareasVencidas > 0" class="bg-error-container text-error px-2 py-0.5 rounded text-[9px] font-bold mb-1 ml-2 animate-pulse uppercase">Urgente</span>
                                <span v-else class="text-on-surface-variant/40 text-xs font-medium mb-1">Sin alertas</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Progress visualization mockup -->
                <div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl p-6 h-48 relative overflow-hidden flex items-end justify-between space-x-2 mt-4">
                    <div class="absolute top-6 left-6 flex space-x-4 items-center">
                        <span class="text-xs font-bold text-on-surface">Distribución de Carga Semanal</span>
                        <div class="flex items-center space-x-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-primary"></span>
                            <span class="text-[9px] text-on-surface-variant font-medium">Asignado</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-outline-variant"></span>
                            <span class="text-[9px] text-on-surface-variant font-medium">Completado</span>
                        </div>
                    </div>
                    <div class="w-full bg-primary/10 rounded-t-lg transition-all duration-500" style="height: 40%"></div>
                    <div class="w-full bg-primary/30 rounded-t-lg transition-all duration-500" style="height: 60%"></div>
                    <div class="w-full bg-primary rounded-t-lg transition-all duration-500" style="height: 35%"></div>
                    <div class="w-full bg-primary/20 rounded-t-lg transition-all duration-500" style="height: 70%"></div>
                    <div class="w-full bg-primary/60 rounded-t-lg transition-all duration-500" style="height: 50%"></div>
                    <div class="w-full bg-primary/40 rounded-t-lg transition-all duration-500" style="height: 45%"></div>
                    <div class="w-full bg-primary rounded-t-lg transition-all duration-500" style="height: 55%"></div>
                </div>
            </section>

            <!-- Pending Module Tasks / Critical Action -->
            <section class="col-span-12 lg:col-span-8 bg-surface-container-lowest p-8 rounded-3xl border border-outline-variant/20 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold flex items-center space-x-2 font-headline">
                        <span class="material-symbols-outlined text-error">priority_high</span>
                        <span>Acción Inmediata (Límite Próximo)</span>
                    </h3>
                </div>
                <div class="space-y-1">
                    <div v-for="t in limitTasks" :key="t.id" class="group flex items-center p-3.5 hover:bg-surface-container-low rounded-xl transition-all border-b border-outline-variant/10 last:border-none">
                        <div class="w-10 h-10 bg-error-container/10 text-error rounded-lg flex items-center justify-center mr-4 shadow-sm border border-error/5">
                            <span class="material-symbols-outlined text-lg">event_busy</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-bold text-xs text-on-surface truncate">{{ t.titulo }}</h4>
                            <p class="text-[10px] text-on-surface-variant/60 truncate mt-0.5">{{ t.descripcion || 'Sin descripción adicional' }}</p>
                        </div>
                        <div class="text-right flex items-center space-x-4 ml-3">
                            <div>
                                <p class="text-[10px] font-bold text-on-surface font-mono">{{ t.fecha_limite }}</p>
                                <p class="text-[9px] text-error font-medium italic mt-0.5">Límite</p>
                            </div>
                            <span class="px-2 py-0.5 text-[8px] font-bold rounded uppercase" :class="t.prioridad === 'Crítica' ? 'bg-error-container/20 text-error' : 'bg-warning-container/20 text-warning'">{{ t.prioridad }}</span>
                        </div>
                    </div>
                    <div v-if="limitTasks.length === 0" class="text-center py-10 text-xs text-on-surface-variant/50">
                        No hay tareas urgentes próximas a vencer en el tablero general.
                    </div>
                </div>
            </section>

            <!-- Calendar & Citations Events -->
            <section class="col-span-12 lg:col-span-4 space-y-6">
                <div class="bg-surface-container-low p-8 rounded-3xl border border-outline-variant/20 shadow-sm flex flex-col h-full justify-between">
                    <div>
                        <h3 class="text-lg font-bold mb-6 flex items-center space-x-2 font-headline">
                            <span class="material-symbols-outlined text-primary">event</span>
                            <span>Próximas Citaciones</span>
                        </h3>
                        <div class="space-y-5">
                            <div v-for="event in upcomingEvents" :key="event.id" class="relative pl-6 border-l-2 border-primary">
                                <span class="absolute -left-[5px] top-0 w-2 h-2 rounded-full bg-primary ring-4 ring-surface-container-low"></span>
                                <p class="text-[9px] font-bold text-primary uppercase tracking-tighter font-mono">{{ event.date }}</p>
                                <h5 class="text-xs font-bold mt-1 text-on-surface truncate">{{ event.title }}</h5>
                                <p class="text-[10px] text-on-surface-variant/60 truncate">{{ event.category }} • {{ event.description || 'Sin descripción' }}</p>
                            </div>
                            <div v-if="upcomingEvents.length === 0" class="text-center py-6 text-xs text-on-surface-variant/50">
                                No hay citaciones próximas agendadas.
                            </div>
                        </div>
                    </div>
                    <div class="pt-6 mt-6">
                        <div class="rounded-xl overflow-hidden relative h-28 group cursor-pointer border border-outline-variant/10 shadow-sm">
                            <img alt="Bureau Workspace" class="w-full h-full object-cover transition-transform duration-75 group-hover:scale-105" src="https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80&w=400&h=200" referrerpolicy="no-referrer" />
                            <div class="absolute inset-0 bg-primary/45 flex flex-col items-center justify-center text-white">
                                <p class="text-[9px] font-bold uppercase tracking-widest">Portal de Agenda</p>
                                <span class="material-symbols-outlined text-xl mt-0.5">open_in_new</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import Swal from 'sweetalert2';
import api from '../../../services/api.js';
import { useAuthStore } from '../../../stores/authStore.js';

const authStore = useAuthStore();

// Dynamic States
const kpis = ref({
    tareasPendientes: 0,
    tareasVencidas: 0,
    proximos7Dias: 0,
    fiscalizacionesActivas: 0,
    atrasosPrensa: 0
});
const listadoTareas = ref([]);
const agenda = ref([]);

// Custom Date greeting
const todayString = computed(() => {
    return new Intl.DateTimeFormat('es-ES', { 
        weekday: 'long', 
        day: 'numeric', 
        month: 'short', 
        year: 'numeric' 
    }).format(new Date());
});

// Category/Module name translator
const getModuleName = (cat) => {
    const categoryMap = {
        iniciativas: 'Iniciativas de Ley',
        citaciones: 'Citaciones',
        comisiones: 'Comisiones y Gabinetes',
        fiscalizacion: 'Fiscalización Constante',
        compromisos: 'Manejo de Compromisos',
        actividades: 'Actividades Web',
        redes: 'Redes Sociales',
        afiliaciones: 'Afiliaciones Políticas'
    };
    return categoryMap[cat] || 'Gestión Operativa';
};

// Calculations for personal statistics
const misTareas = computed(() => {
    const userId = authStore.user?.id;
    if (!userId) return [];
    return listadoTareas.value.filter(t => t.asignado_a == userId && t.estado !== 'Completada');
});

const personalEfficiency = computed(() => {
    const userId = authStore.user?.id;
    if (!userId) return 100;
    const userTasks = listadoTareas.value.filter(t => t.asignado_a == userId);
    if (userTasks.length === 0) return 100;
    const completed = userTasks.filter(t => t.estado === 'Completada').length;
    return Math.round((completed / userTasks.length) * 100);
});

const limitTasks = computed(() => {
    return listadoTareas.value
        .filter(t => t.estado !== 'Completada' && (t.prioridad === 'Crítica' || t.prioridad === 'Alta'))
        .slice(0, 4);
});

const upcomingEvents = computed(() => {
    return agenda.value.slice(0, 3);
});

// Stat logic based on category
const getModuleStat1 = () => {
    const cat = authStore.user?.categoria;
    if (cat === 'fiscalizacion') return kpis.value.fiscalizacionesActivas || 12;
    if (cat === 'redes') return kpis.value.atrasosPrensa || 3;
    if (cat === 'compromisos') return 48; // Mock/default baseline for commitments count
    return 15;
};

// Load dynamic summary counts
const fetchSummary = async () => {
    try {
        const res = await api.get('/dashboard/summary');
        if (res.data && res.data.success) {
            const payload = res.data.data;
            kpis.value = payload.kpis;
            agenda.value = payload.agenda || [];
        }
    } catch (e) {
        console.error('Error fetching dashboard summary:', e);
    }
};

const fetchTareas = async () => {
    try {
        const res = await api.get('/dashboard/tareas');
        if (res.data && res.data.success) {
            listadoTareas.value = res.data.data || [];
        }
    } catch (e) {
        console.error('Error fetching tasks list:', e);
    }
};

// Quick action to complete assigned tasks
const quickCompleteTask = async (id) => {
    try {
        const res = await api.put(`/dashboard/tareas/${id}`, { estado: 'Completada' });
        if (res.data && res.data.success) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '¡Tarea completada!',
                showConfirmButton: false,
                timer: 2000
            });
            fetchTareas();
            fetchSummary();
        }
    } catch (e) {
        Swal.fire('Error', 'No se pudo actualizar el estado de la tarea', 'error');
    }
};

onMounted(() => {
    fetchSummary();
    fetchTareas();
});
</script>
