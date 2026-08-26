<template>
    <div class="w-full max-w-6xl mx-auto space-y-8 pb-10">

        <!-- ── Welcome Banner ─────────────────────────────── -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-600 via-emerald-700 to-emerald-900 p-7 md:p-9 shadow-xl shadow-emerald-900/30 animate-slide-up">
            <!-- Background orbs -->
            <div class="absolute -top-16 -right-16 w-64 h-64 bg-white/10 rounded-full blur-3xl pointer-events-none animate-blob"></div>
            <div class="absolute bottom-0 left-1/3 w-48 h-48 bg-amber-400/15 rounded-full blur-3xl pointer-events-none animate-blob" style="animation-delay:3s"></div>

            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
                <div>
                    <p class="text-emerald-200 text-xs font-bold uppercase tracking-widest mb-1">Panel de Control</p>
                    <h2 class="text-2xl md:text-3xl font-black text-white leading-tight">
                        Sistema Unificado <span class="text-amber-300">MAGA</span>
                    </h2>
                    <p class="text-emerald-100/80 text-sm mt-2 max-w-md">
                        Ministerio de Agricultura, Ganadería y Alimentación de Guatemala
                    </p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <div class="text-right">
                        <p class="text-emerald-200 text-xs">{{ today }}</p>
                        <p class="text-white font-bold text-sm">Bienvenido, {{ userName }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-white/15 border border-white/20 flex items-center justify-center text-white font-black text-lg shadow-lg">
                        {{ userInitials }}
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Quick Stats ─────────────────────────────────── -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <!-- Stat 1 -->
            <div v-if="isLoading" class="h-40 rounded-3xl bg-slate-200 dark:bg-slate-800 animate-shimmer"
                 style="background-size:200% 100%;background-image:linear-gradient(90deg,transparent 0%,rgba(255,255,255,0.15) 50%,transparent 100%)">
            </div>
            <div v-else
                 class="relative overflow-hidden rounded-3xl p-6 border border-white/60 dark:border-white/8
                        bg-gradient-to-br from-white/80 to-white/40 dark:from-slate-800/80 dark:to-slate-900/80
                        backdrop-blur-xl shadow-lg group cursor-default
                        hover:-translate-y-2 hover:shadow-2xl hover:shadow-amber-500/15 transition-all duration-500 animate-slide-up"
                 style="animation-delay:100ms">
                <div class="absolute -top-12 -right-12 w-36 h-36 bg-amber-400/20 rounded-full blur-2xl animate-blob opacity-50 group-hover:opacity-80 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        <h4 class="text-slate-500 dark:text-slate-400 text-[10px] font-black uppercase tracking-widest">Productores Registrados</h4>
                    </div>
                    <p class="text-5xl font-black text-slate-800 dark:text-white tracking-tight group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">12,450</p>
                    <div class="mt-3 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-[10px] font-bold text-emerald-700 dark:text-emerald-400">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        +350 este mes
                    </div>
                </div>
            </div>

            <!-- Stat 2 -->
            <div v-if="isLoading" class="h-40 rounded-3xl bg-slate-200 dark:bg-slate-800 animate-shimmer"
                 style="background-size:200% 100%;background-image:linear-gradient(90deg,transparent 0%,rgba(255,255,255,0.15) 50%,transparent 100%)">
            </div>
            <div v-else
                 class="relative overflow-hidden rounded-3xl p-6 border border-white/60 dark:border-white/8
                        bg-gradient-to-br from-white/80 to-white/40 dark:from-slate-800/80 dark:to-slate-900/80
                        backdrop-blur-xl shadow-lg group cursor-default
                        hover:-translate-y-2 hover:shadow-2xl hover:shadow-blue-500/15 transition-all duration-500 animate-slide-up"
                 style="animation-delay:200ms">
                <div class="absolute -bottom-12 -right-12 w-36 h-36 bg-blue-400/20 rounded-full blur-2xl animate-blob opacity-50 group-hover:opacity-80 transition-opacity" style="animation-delay:2s"></div>
                <div class="relative z-10">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                        <h4 class="text-slate-500 dark:text-slate-400 text-[10px] font-black uppercase tracking-widest">Certificaciones Emitidas</h4>
                    </div>
                    <p class="text-5xl font-black text-slate-800 dark:text-white tracking-tight group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">1,284</p>
                    <div class="mt-3 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-[10px] font-bold text-slate-600 dark:text-slate-400">
                        Año actual
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Activity Chart ─────────────────────────────── -->
        <div class="relative rounded-3xl border border-white/60 dark:border-white/8
                    bg-white/70 dark:bg-slate-800/80 backdrop-blur-xl shadow-lg p-6 md:p-8
                    animate-slide-up" style="animation-delay:300ms">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-600 flex items-center justify-center text-white shadow-lg shadow-emerald-600/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800 dark:text-white leading-none">Resumen de Actividad</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ selectedPeriod }}</p>
                    </div>
                </div>
                <select v-model="selectedPeriod"
                    class="h-9 px-3 text-xs rounded-xl bg-slate-100 dark:bg-slate-700/60 border border-slate-200 dark:border-white/10
                           text-slate-600 dark:text-slate-300 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500/40 cursor-pointer transition-all">
                    <option>Últimos 6 meses</option>
                    <option>Este año</option>
                </select>
            </div>
            <div v-if="isLoading" class="flex items-end gap-2 h-52 pb-4">
                <div v-for="i in 6" :key="i" class="flex-1 rounded-t animate-pulse bg-slate-200 dark:bg-slate-700"
                     :style="`height:${[40,65,35,80,55,90][i-1]}%`"></div>
            </div>
            <div v-else class="h-52">
                <ActivityChart />
            </div>
        </div>

        <!-- ── Módulos del sistema ─────────────────────────── -->
        <div class="animate-slide-up" style="animation-delay:400ms">
            <div class="flex items-center gap-3 mb-6 px-1">
                <div class="w-7 h-7 rounded-lg bg-emerald-500 text-white flex items-center justify-center shadow-lg shadow-emerald-500/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <h3 class="text-lg font-black tracking-tight text-slate-800 dark:text-white">Áreas Transversales MAGA</h3>
                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-2.5 py-1 rounded-full">
                    {{ formSteps.length }} módulos
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-5">
                <RouterLink
                    v-for="(step, index) in formSteps"
                    :key="step.id"
                    :to="step.route"
                    class="group relative flex flex-col p-5 rounded-[1.75rem] border border-white/60 dark:border-white/6
                           bg-gradient-to-b from-white/90 to-white/50 dark:from-slate-800/90 dark:to-slate-900/70
                           backdrop-blur-xl shadow-sm text-left overflow-hidden
                           hover:-translate-y-2 hover:shadow-xl transition-all duration-500 animate-slide-up"
                    :style="`animation-delay:${400 + index * 60}ms; animation-fill-mode:both`"
                >
                    <!-- Color glow on hover (unique per module) -->
                    <div :class="`absolute -right-8 -bottom-8 w-28 h-28 rounded-full blur-2xl opacity-0 group-hover:opacity-30 transition-opacity duration-500 pointer-events-none ${step.glowColor}`"></div>

                    <!-- Icon + Arrow row -->
                    <div class="flex items-start justify-between mb-4 relative z-10">
                        <div :class="`w-12 h-12 rounded-2xl flex items-center justify-center text-white shadow-lg ${step.bgColor} group-hover:scale-110 group-hover:rotate-3 transition-transform duration-500`">
                            <component :is="step.icon" class="w-5 h-5" />
                        </div>
                        <ArrowTopRightOnSquareIcon
                            class="w-4 h-4 text-slate-300 dark:text-slate-600 group-hover:text-emerald-500
                                   transition-all duration-300 opacity-0 group-hover:opacity-100
                                   translate-x-1 group-hover:translate-x-0 -translate-y-1 group-hover:translate-y-0"
                        />
                    </div>

                    <!-- Text -->
                    <div class="relative z-10 flex-1">
                        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100 leading-tight mb-0.5
                                   group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                            {{ step.name }}
                        </h4>
                        <span class="text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-500 font-bold">
                            {{ step.label }}
                        </span>
                    </div>

                    <!-- Status badge -->
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-white/5 relative z-10">
                        <span v-if="step.status === 'OPTIMO'"
                              class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-[10px] font-black uppercase tracking-widest">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Óptimo
                        </span>
                        <span v-else
                              class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-[10px] font-black uppercase tracking-widest">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Pendiente
                        </span>
                    </div>
                </RouterLink>
            </div>
        </div>

    </div>
</template>

<script setup>
import {
    DocumentTextIcon,
    ArrowTopRightOnSquareIcon,
    BriefcaseIcon,
    CloudIcon
} from '@heroicons/vue/24/outline';
import ActivityChart from '../../components/ActivityChart.vue';
import { ref, computed, onMounted } from 'vue';

const isLoading  = ref(true);
const selectedPeriod = ref('Últimos 6 meses');
const userName   = ref('Usuario');
const userInitials = ref('US');

const today = computed(() => {
    return new Date().toLocaleDateString('es-GT', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
});

const formSteps = [
    { id: 'actividades', label: 'Ministerial',  name: 'Actividades Despacho',    status: 'OPTIMO',  route: '/admin/actividades-despacho', icon: BriefcaseIcon,    bgColor: 'bg-purple-600',  glowColor: 'bg-purple-400' },
    { id: 'clima',       label: 'Monitoreo',    name: 'Registro Climatológico',  status: 'OPTIMO',  route: '/admin/climatologico',        icon: CloudIcon,        bgColor: 'bg-cyan-500',    glowColor: 'bg-cyan-400' },
    { id: 'productor',   label: 'Padrón',       name: 'Registrar Productor',     status: 'PENDING', route: '/admin/productores',          icon: DocumentTextIcon, bgColor: 'bg-blue-600',    glowColor: 'bg-blue-400' },
    { id: 'fito',        label: 'Sanidad',      name: 'Certif. Fitosanitario',   status: 'PENDING', route: '/admin/sanidad',              icon: DocumentTextIcon, bgColor: 'bg-emerald-600', glowColor: 'bg-emerald-400' },
    { id: 'zoo',         label: 'Sanidad',      name: 'Certif. Zoosanitario',    status: 'PENDING', route: '/admin/sanidad',              icon: DocumentTextIcon, bgColor: 'bg-teal-600',    glowColor: 'bg-teal-400' },
    { id: 'licencia',    label: 'Permisos',     name: 'Licencia Importación',    status: 'PENDING', route: '/admin/licencias',            icon: DocumentTextIcon, bgColor: 'bg-indigo-600',  glowColor: 'bg-indigo-400' },
    { id: 'asistencia',  label: 'Extensión',    name: 'Asistencia Técnica',      status: 'PENDING', route: '/admin/extension',            icon: DocumentTextIcon, bgColor: 'bg-orange-500',  glowColor: 'bg-orange-400' },
    { id: 'alimentos',   label: 'VISAN',        name: 'Asistencia Alimentaria',  status: 'OPTIMO',  route: '/admin/visan/dashboard',      icon: DocumentTextIcon, bgColor: 'bg-red-500',     glowColor: 'bg-red-400' },
];

onMounted(() => {
    const stored = localStorage.getItem('user');
    if (stored) {
        try {
            const parsed = JSON.parse(stored);
            const fullName = parsed.nombre_completo || parsed.username || 'Usuario';
            userName.value = fullName.split(' ')[0];
            const parts = fullName.split(' ');
            userInitials.value = parts.length >= 2
                ? (parts[0][0] + parts[1][0]).toUpperCase()
                : fullName.substring(0, 2).toUpperCase();
        } catch {}
    }
    setTimeout(() => { isLoading.value = false; }, 800);
});
</script>
