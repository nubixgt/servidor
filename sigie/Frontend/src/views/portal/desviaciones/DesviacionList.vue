<template>
    <div>
        <!-- Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-on-surface">Desviaciones de Laboratorio</h1>
                <p class="text-sm text-on-surface-variant mt-1">Historial y seguimiento de desviaciones de laboratorio registradas.</p>
            </div>
            <div class="flex items-center gap-3 self-start sm:self-center">
                <button 
                    @click="exportToExcel" 
                    :disabled="desviaciones.length === 0"
                    class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span class="material-symbols-outlined text-lg">download</span>
                    Exportar a Excel
                </button>
                <router-link 
                    v-if="auth.role === 'inspector'"
                    to="/desviaciones/nuevo" 
                    class="px-5 py-3 bg-primary hover:bg-primary-dim text-on-primary font-bold text-xs rounded-xl shadow transition-all flex items-center justify-center gap-2"
                >
                    <span class="material-symbols-outlined text-lg">add_circle</span>
                    Nueva Desviación
                </router-link>
            </div>
        </div>

        <!-- Filters Bar -->
        <div class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient mb-8 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Búsqueda Global</label>
                <input 
                    v-model="searchTerm" 
                    type="text" 
                    placeholder="Muestra, Inspector..."
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                />
            </div>
            <div>
                <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Establecimiento</label>
                <input 
                    v-model="filterEstablecimiento" 
                    type="text" 
                    placeholder="Establecimiento..."
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                />
            </div>
            <div>
                <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Estado de Seguimiento</label>
                <select 
                    v-model="filterEstado"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                >
                    <option value="todos">Todos los Estados</option>
                    <option value="Abierto">Abierto</option>
                    <option value="En proceso">En proceso</option>
                    <option value="Cerrado">Cerrado</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Fecha del Resultado</label>
                <input 
                    v-model="filterFecha" 
                    type="date" 
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                />
            </div>
        </div>

        <!-- Deviations Table -->
        <div class="bg-surface-container-lowest rounded-2xl border border-surface-container shadow-ambient overflow-hidden">
            <div v-if="loading" class="py-16 text-center text-sm text-on-surface-variant">
                <span class="material-symbols-outlined text-4xl animate-spin text-primary">sync</span>
                <p class="mt-2 font-bold">Cargando registros...</p>
            </div>

            <div v-else-if="filteredDesviaciones.length === 0" class="py-20 text-center">
                <span class="material-symbols-outlined text-5xl text-outline-variant">science</span>
                <p class="text-sm font-semibold text-on-surface mt-4">No se encontraron desviaciones de laboratorio</p>
                <p class="text-xs text-on-surface-variant mt-1">Intenta ajustando los criterios de filtrado anteriores.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100 text-[10px] font-extrabold uppercase text-on-surface-variant tracking-wider">
                            <th class="px-6 py-4">Muestra</th>
                            <th class="px-6 py-4">Inspector Responsable</th>
                            <th class="px-6 py-4">Establecimiento</th>
                            <th class="px-6 py-4">Fecha Resultado</th>
                            <th class="px-6 py-4">Tipo & Parámetro</th>
                            <th class="px-6 py-4 text-center">Estado</th>
                            <th class="px-6 py-4 text-center">Adjuntos</th>
                            <th class="px-6 py-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <tr v-for="d in filteredDesviaciones" :key="d.id" class="hover:bg-slate-50/50 transition-colors">
                            <!-- Sample Code -->
                            <td class="px-6 py-4 font-mono font-bold text-primary">
                                {{ d.codigo_muestra }}
                            </td>

                            <!-- Inspector -->
                            <td class="px-6 py-4">
                                <p class="font-bold text-on-surface">{{ d.inspector_nombre }}</p>
                                <p class="text-[9px] text-on-surface-variant font-mono">Código: {{ d.inspector_codigo }}</p>
                            </td>

                            <!-- Establishment -->
                            <td class="px-6 py-4">
                                <p class="font-bold text-on-surface">{{ d.establecimiento }}</p>
                            </td>

                            <!-- Date -->
                            <td class="px-6 py-4 text-on-surface-variant font-semibold">
                                {{ d.fecha_resultado }}
                            </td>

                            <!-- Type & Parameter -->
                            <td class="px-6 py-4">
                                <p class="font-bold text-on-surface">{{ d.tipo_analisis }}</p>
                                <p class="text-[10px] text-red-500 font-semibold mt-0.5">{{ d.parametro_fuera_norma }}</p>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 text-center">
                                <span 
                                    :class="['px-2.5 py-1 rounded-full text-[10px] font-bold border uppercase tracking-wide', 
                                             d.estado_seguimiento === 'Abierto' ? 'bg-red-50 border-red-200 text-red-700' :
                                             d.estado_seguimiento === 'En proceso' ? 'bg-amber-50 border-amber-200 text-amber-700' :
                                             'bg-emerald-50 border-emerald-200 text-emerald-700']"
                                >
                                    {{ d.estado_seguimiento }}
                                </span>
                            </td>

                            <!-- Attachments count -->
                            <td class="px-6 py-4 text-center font-bold text-slate-500 font-mono">
                                <span class="inline-flex items-center gap-1 bg-slate-100 px-2 py-0.5 rounded-full text-[10px]">
                                    <span class="material-symbols-outlined text-xs">attach_file</span>
                                    {{ d.total_adjuntos }}
                                </span>
                            </td>

                            <!-- Detailed Actions -->
                            <td class="px-6 py-4">
                                <router-link 
                                    :to="`/desviaciones/${d.id}`"
                                    class="text-primary hover:text-primary-dim font-bold text-xs transition-colors flex items-center gap-1"
                                >
                                    <span class="material-symbols-outlined text-sm">visibility</span>
                                    Ver Detalle
                                </router-link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../../../stores/authStore.js';
import api from '../../../services/api.js';
import Swal from 'sweetalert2';
import * as XLSX from 'xlsx';

const auth = useAuthStore();
const desviaciones = ref([]);
const loading = ref(false);

// Filters State
const searchTerm = ref('');
const filterEstablecimiento = ref('');
const filterEstado = ref('todos');
const filterFecha = ref('');

const fetchDesviaciones = async () => {
    loading.value = true;
    try {
        const response = await api.get('/desviaciones');
        if (response.data?.status === 'success') {
            desviaciones.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar desviaciones', error);
        Swal.fire('Error', 'No se pudieron recuperar los registros del servidor.', 'error');
    } finally {
        loading.value = false;
    }
};

const filteredDesviaciones = computed(() => {
    return desviaciones.value.filter(d => {
        const globalSearch = !searchTerm.value || 
            d.codigo_muestra.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
            d.inspector_nombre.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
            d.tipo_analisis.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
            d.parametro_fuera_norma.toLowerCase().includes(searchTerm.value.toLowerCase());
            
        const matchesEstablecimiento = !filterEstablecimiento.value || 
            d.establecimiento.toLowerCase().includes(filterEstablecimiento.value.toLowerCase());
            
        const matchesEstado = filterEstado.value === 'todos' || d.estado_seguimiento === filterEstado.value;
        const matchesFecha = !filterFecha.value || d.fecha_resultado === filterFecha.value;

        return globalSearch && matchesEstablecimiento && matchesEstado && matchesFecha;
    });
});

const exportToExcel = () => {
    const dataToExport = filteredDesviaciones.value.map(d => ({
        'Muestra/Código': d.codigo_muestra,
        'Inspector': d.inspector_nombre,
        'Establecimiento': d.establecimiento,
        'Fecha de Resultado': d.fecha_resultado,
        'Tipo de Análisis': d.tipo_analisis,
        'Resultado Obtenido': d.resultado_obtenido,
        'Parámetro Fuera de Norma': d.parametro_fuera_norma,
        'Acción Tomada': d.accion_tomada,
        'Estado': d.estado_seguimiento,
        'Total Adjuntos': d.total_adjuntos,
        'Observaciones': d.observaciones || ''
    }));

    const worksheet = XLSX.utils.json_to_sheet(dataToExport);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Desviaciones');

    // Auto fit column widths
    const max_width = dataToExport.reduce((w, r) => Math.max(w, r.Inspector.length), 10);
    worksheet['!cols'] = [ { wch: max_width + 4 } ];

    XLSX.writeFile(workbook, 'Reporte_Desviaciones_Lab_SIGIE.xlsx');
};

onMounted(() => {
    fetchDesviaciones();
});
</script>
