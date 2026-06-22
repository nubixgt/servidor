<template>
    <div>
        <!-- Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-on-surface">Reportes de Actividades</h1>
                <p class="text-sm text-on-surface-variant mt-1">Consolidados mensuales de cobertura, cumplimiento y ejecución por inspector y establecimiento.</p>
            </div>
            <div class="flex items-center gap-3 self-start sm:self-center">
                <button 
                    @click="exportToExcel" 
                    :disabled="reportData.inspectores.length === 0 && reportData.establecimientos.length === 0"
                    class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-md shadow-sm transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed border border-emerald-700 font-headline"
                >
                    <span class="material-symbols-outlined text-sm">download</span>
                    Exportar a Excel
                </button>
            </div>
        </div>

        <!-- Filter Month Selector -->
        <div class="bg-white p-6 rounded-md border border-surface-container shadow-sm mb-8 flex items-end">
            <div>
                <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Seleccionar Mes del Reporte</label>
                <input 
                    v-model="mesReporte" 
                    type="month" 
                    class="bg-slate-50 border border-slate-300 rounded-md px-4 py-2.5 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface font-semibold"
                />
            </div>
        </div>

        <div v-if="loading" class="py-16 text-center text-sm text-on-surface-variant bg-white rounded border border-surface-container shadow-sm">
            <span class="material-symbols-outlined text-4xl animate-spin text-primary">sync</span>
            <p class="mt-2 font-bold">Cargando reporte consolidado...</p>
        </div>

        <div v-else-if="reportData.inspectores.length === 0 && reportData.establecimientos.length === 0" class="py-20 text-center bg-white rounded border border-surface-container shadow-sm">
            <span class="material-symbols-outlined text-5xl text-outline-variant">analytics</span>
            <p class="text-sm font-semibold text-on-surface mt-4">No hay datos de actividades registradas para este mes</p>
            <p class="text-xs text-on-surface-variant mt-1">Por favor genere y guarde la programación de este mes para consultar métricas.</p>
        </div>

        <div v-else class="space-y-8">
            <!-- KPI Dashboard Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- KPI 1: Programmed -->
                <div class="bg-white p-6 rounded-md border border-surface-container shadow-ambient flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Actividades Programadas</p>
                        <h3 class="text-3xl font-black text-on-surface mt-1">{{ totalProgramadas }}</h3>
                    </div>
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center border border-blue-200">
                        <span class="material-symbols-outlined text-xl">event_note</span>
                    </div>
                </div>

                <!-- KPI 2: Executed Programmed -->
                <div class="bg-white p-6 rounded-md border border-surface-container shadow-ambient flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Ejecución Planificada</p>
                        <h3 class="text-3xl font-black text-emerald-600 mt-1">{{ totalEjecutadasProgramadas }}</h3>
                        <p class="text-[9px] text-slate-400 mt-0.5">De las actividades del cronograma</p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center border border-emerald-200">
                        <span class="material-symbols-outlined text-xl">task_alt</span>
                    </div>
                </div>

                <!-- KPI 3: Compliance Rate -->
                <div class="bg-white p-6 rounded-md border border-surface-container shadow-ambient flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Cumplimiento Programación</p>
                        <h3 class="text-3xl font-black mt-1" :class="cumplimientoGeneral >= 80 ? 'text-emerald-600' : (cumplimientoGeneral >= 50 ? 'text-amber-600' : 'text-red-600')">
                            {{ cumplimientoGeneral }}%
                        </h3>
                        <p class="text-[9px] text-slate-400 mt-0.5">Tasa de cumplimiento efectiva</p>
                    </div>
                    <div class="w-12 h-12 rounded-full flex items-center justify-center border" 
                        :class="cumplimientoGeneral >= 80 ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : (cumplimientoGeneral >= 50 ? 'bg-amber-50 text-amber-600 border-amber-200' : 'bg-red-50 text-red-600 border-red-200')"
                    >
                        <span class="material-symbols-outlined text-xl">percent</span>
                    </div>
                </div>

                <!-- KPI 4: Spontaneous -->
                <div class="bg-white p-6 rounded-md border border-surface-container shadow-ambient flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Ejecución Espontánea</p>
                        <h3 class="text-3xl font-black text-purple-600 mt-1">{{ totalEspontaneas }}</h3>
                        <p class="text-[9px] text-slate-400 mt-0.5">Actividades extras no programadas</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-full flex items-center justify-center border border-purple-200">
                        <span class="material-symbols-outlined text-xl">bolt</span>
                    </div>
                </div>
            </div>

            <!-- Tab Buttons -->
            <div class="border-b border-surface-container flex gap-2">
                <button 
                    @click="activeTab = 'inspectores'"
                    :class="['px-5 py-3 font-bold text-xs border-b-2 transition-all flex items-center gap-1.5', 
                             activeTab === 'inspectores' ? 'border-primary text-primary bg-white' : 'border-transparent text-slate-500 hover:text-slate-700']"
                >
                    <span class="material-symbols-outlined text-sm">badge</span>
                    Desempeño y Cobertura por Inspector
                </button>
                <button 
                    @click="activeTab = 'establecimientos'"
                    :class="['px-5 py-3 font-bold text-xs border-b-2 transition-all flex items-center gap-1.5', 
                             activeTab === 'establecimientos' ? 'border-primary text-primary bg-white' : 'border-transparent text-slate-500 hover:text-slate-700']"
                >
                    <span class="material-symbols-outlined text-sm">store</span>
                    Cobertura por Establecimiento
                </button>
            </div>

            <!-- Tab Content 1: Inspectors Desempeño -->
            <div v-show="activeTab === 'inspectores'" class="bg-white rounded-md border border-surface-container shadow-sm overflow-hidden animate-fade-in">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100 border-b border-slate-200 text-[10px] font-extrabold uppercase text-slate-700 tracking-wider">
                                <th class="px-6 py-4">Inspector</th>
                                <th class="px-4 py-4 text-center w-28">Programadas</th>
                                <th class="px-4 py-4 text-center w-28">Ejecutadas (Prog)</th>
                                <th class="px-4 py-4 text-center w-28">Incumplidas</th>
                                <th class="px-4 py-4 text-center w-28">Pendientes</th>
                                <th class="px-4 py-4 text-center w-28">Espontáneas</th>
                                <th class="px-4 py-4 text-center w-28 font-extrabold text-primary">Total Realizadas</th>
                                <th class="px-6 py-4 w-56">% Cumplimiento</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs">
                            <tr v-for="ins in reportData.inspectores" :key="ins.inspector_id" class="hover:bg-slate-50 transition-colors">
                                <!-- Inspector Details -->
                                <td class="px-6 py-4">
                                    <p class="font-bold text-on-surface">{{ ins.inspector_nombre }}</p>
                                    <p class="text-[9px] text-on-surface-variant font-mono">Código: {{ ins.inspector_codigo }} | Área: {{ ins.inspector_area }}</p>
                                </td>

                                <!-- Programadas -->
                                <td class="px-4 py-4 text-center font-mono font-bold text-slate-700">
                                    {{ ins.total_programadas }}
                                </td>

                                <!-- Ejecutadas Programadas -->
                                <td class="px-4 py-4 text-center font-mono font-bold text-emerald-600 bg-emerald-50/20">
                                    {{ ins.ejecutadas_programadas }}
                                </td>

                                <!-- Incumplidas -->
                                <td class="px-4 py-4 text-center font-mono font-bold text-red-600 bg-red-50/20">
                                    {{ ins.incumplidas_programadas }}
                                </td>

                                <!-- Pendientes -->
                                <td class="px-4 py-4 text-center font-mono font-bold text-slate-500">
                                    {{ ins.pendientes_programadas }}
                                </td>

                                <!-- Espontaneas -->
                                <td class="px-4 py-4 text-center font-mono font-bold text-purple-600">
                                    {{ ins.espontaneas }}
                                </td>

                                <!-- Total Realizadas -->
                                <td class="px-4 py-4 text-center font-mono font-bold text-primary bg-blue-50/20">
                                    {{ ins.total_ejecutadas }}
                                </td>

                                <!-- Compliance Percentage with Progress Bar -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 w-full bg-slate-100 rounded-full h-2 overflow-hidden border border-slate-200">
                                            <div 
                                                class="h-full rounded-full transition-all duration-500"
                                                :class="getInspectorPercentage(ins) >= 80 ? 'bg-emerald-500' : (getInspectorPercentage(ins) >= 50 ? 'bg-amber-500' : 'bg-red-500')"
                                                :style="{ width: `${getInspectorPercentage(ins)}%` }"
                                            ></div>
                                        </div>
                                        <span class="font-mono font-bold text-[11px] w-10 text-right"
                                            :class="getInspectorPercentage(ins) >= 80 ? 'text-emerald-700' : (getInspectorPercentage(ins) >= 50 ? 'text-amber-700' : 'text-red-700')"
                                        >
                                            {{ getInspectorPercentage(ins) }}%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Content 2: Establishments Cobertura -->
            <div v-show="activeTab === 'establecimientos'" class="bg-white rounded-md border border-surface-container shadow-sm overflow-hidden animate-fade-in">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100 border-b border-slate-200 text-[10px] font-extrabold uppercase text-slate-700 tracking-wider">
                                <th class="px-6 py-4">Establecimiento / Lugar</th>
                                <th class="px-4 py-4 text-center w-36">Actividades Programadas</th>
                                <th class="px-4 py-4 text-center w-36">Ejecutadas (Prog)</th>
                                <th class="px-4 py-4 text-center w-36">Incumplidas</th>
                                <th class="px-4 py-4 text-center w-36">Espontáneas</th>
                                <th class="px-4 py-4 text-center w-36 font-extrabold text-primary">Total Realizadas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs">
                            <tr v-for="est in reportData.establecimientos" :key="est.establecimiento" class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-bold text-on-surface">
                                    {{ est.establecimiento }}
                                </td>
                                <td class="px-4 py-4 text-center font-mono font-bold text-slate-700">
                                    {{ est.total_programadas }}
                                </td>
                                <td class="px-4 py-4 text-center font-mono font-bold text-emerald-600 bg-emerald-50/20">
                                    {{ est.ejecutadas_programadas }}
                                </td>
                                <td class="px-4 py-4 text-center font-mono font-bold text-red-600 bg-red-50/20">
                                    {{ est.incumplidas_programadas }}
                                </td>
                                <td class="px-4 py-4 text-center font-mono font-bold text-purple-600">
                                    {{ est.espontaneas }}
                                </td>
                                <td class="px-4 py-4 text-center font-mono font-bold text-primary bg-blue-50/20">
                                    {{ est.total_ejecutadas }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import api from '../../../services/api.js';
import Swal from 'sweetalert2';
import * as XLSX from 'xlsx';

// States
const mesReporte = ref(new Date().toISOString().substring(0, 7)); // YYYY-MM
const activeTab = ref('inspectores');
const loading = ref(false);
const reportData = ref({
    inspectores: [],
    establecimientos: []
});

watch(mesReporte, () => {
    fetchReporte();
});

// Calculate metrics
const totalProgramadas = computed(() => {
    return reportData.value.inspectores.reduce((sum, r) => sum + parseInt(r.total_programadas), 0);
});

const totalEjecutadasProgramadas = computed(() => {
    return reportData.value.inspectores.reduce((sum, r) => sum + parseInt(r.ejecutadas_programadas), 0);
});

const totalEspontaneas = computed(() => {
    return reportData.value.inspectores.reduce((sum, r) => sum + parseInt(r.espontaneas), 0);
});

const cumplimientoGeneral = computed(() => {
    const prog = totalProgramadas.value;
    if (prog === 0) return 0;
    const rate = (totalEjecutadasProgramadas.value / prog) * 100;
    return Math.round(rate * 10) / 10;
});

const getInspectorPercentage = (ins) => {
    const prog = parseInt(ins.total_programadas);
    if (prog === 0) return 0;
    const rate = (parseInt(ins.ejecutadas_programadas) / prog) * 100;
    return Math.round(rate * 10) / 10;
};

// Fetch API Report Data
const fetchReporte = async () => {
    loading.value = true;
    try {
        const response = await api.get(`/programacion/reportes`, {
            params: { mes: mesReporte.value }
        });
        if (response.data?.status === 'success') {
            reportData.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar reporte consolidado', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron recuperar las métricas consolidadas del servidor.',
            confirmButtonColor: '#005a9c'
        });
    } finally {
        loading.value = false;
    }
};

// Export consolidated reports to Excel with two Sheets
const exportToExcel = () => {
    const inspectoresSheetData = reportData.value.inspectores.map(r => ({
        'Inspector': r.inspector_nombre,
        'Código': r.inspector_codigo,
        'Área de Adscripción': r.inspector_area,
        'Act. Programadas': parseInt(r.total_programadas),
        'Ejecutadas (Prog.)': parseInt(r.ejecutadas_programadas),
        'Incumplidas (Prog.)': parseInt(r.incumplidas_programadas),
        'Pendientes (Prog.)': parseInt(r.pendientes_programadas),
        'Act. Espontáneas': parseInt(r.espontaneas),
        'Total Realizadas': parseInt(r.total_ejecutadas),
        '% Cumplimiento': `${getInspectorPercentage(r)}%`
    }));

    const establecimientosSheetData = reportData.value.establecimientos.map(e => ({
        'Establecimiento / Lugar': e.establecimiento,
        'Act. Programadas': parseInt(e.total_programadas),
        'Ejecutadas (Prog.)': parseInt(e.ejecutadas_programadas),
        'Incumplidas (Prog.)': parseInt(e.incumplidas_programadas),
        'Act. Espontáneas': parseInt(e.espontaneas),
        'Total Realizadas': parseInt(e.total_ejecutadas)
    }));

    const wb = XLSX.utils.book_new();

    // Inspector sheet
    const wsInspectores = XLSX.utils.json_to_sheet(inspectoresSheetData);
    // Autofit columns width
    const wscolsInspectores = [
        { wch: 25 }, // Inspector
        { wch: 10 }, // Código
        { wch: 25 }, // Área
        { wch: 16 }, // Programadas
        { wch: 18 }, // Ejecutadas (Prog)
        { wch: 18 }, // Incumplidas (Prog)
        { wch: 18 }, // Pendientes (Prog)
        { wch: 16 }, // Espontáneas
        { wch: 16 }, // Total Realizadas
        { wch: 16 }  // % Cumplimiento
    ];
    wsInspectores['!cols'] = wscolsInspectores;
    XLSX.utils.book_append_sheet(wb, wsInspectores, 'Desempeño Inspectores');

    // Establishment sheet
    const wsEstablecimientos = XLSX.utils.json_to_sheet(establecimientosSheetData);
    const wscolsEstablecimientos = [
        { wch: 35 }, // Establecimiento
        { wch: 18 }, // Programadas
        { wch: 18 }, // Ejecutadas (Prog)
        { wch: 18 }, // Incumplidas (Prog)
        { wch: 16 }, // Espontáneas
        { wch: 16 }  // Total Realizadas
    ];
    wsEstablecimientos['!cols'] = wscolsEstablecimientos;
    XLSX.utils.book_append_sheet(wb, wsEstablecimientos, 'Cobertura Establecimientos');

    // Save Workbook
    XLSX.writeFile(wb, `Reporte_Programacion_Actividades_${mesReporte.value}.xlsx`);
};

onMounted(() => {
    fetchReporte();
});
</script>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.15s ease-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>
