<template>
    <div>
        <!-- Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-white font-headline">Desviaciones de Laboratorio</h1>
                <p class="text-xs text-white/60 mt-1">Historial y seguimiento de desviaciones de laboratorio registradas.</p>
            </div>
            <div class="flex items-center gap-3 self-start sm:self-center">
                <button 
                    @click="exportToExcel" 
                    :disabled="desviaciones.length === 0"
                    class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-lg transition-colors flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed border border-emerald-700"
                >
                    <span class="material-symbols-outlined text-sm">download</span>
                    Exportar a Excel
                </button>
                <router-link 
                    v-if="auth.role === 'inspector'"
                    to="/desviaciones/nuevo" 
                    class="px-5 py-3 bg-[#0a192f] hover:bg-[#122347] text-white font-bold text-xs rounded-xl shadow-lg transition-colors flex items-center justify-center gap-2 border border-slate-800"
                >
                    <span class="material-symbols-outlined text-sm">add_circle</span>
                    Nueva Desviación
                </router-link>
            </div>
        </div>

        <!-- Filters Bar -->
        <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium mb-8 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Búsqueda Global</label>
                <input 
                    v-model="searchTerm" 
                    type="text" 
                    placeholder="Muestra, Inspector..."
                    class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                />
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Establecimiento</label>
                <input 
                    v-model="filterEstablecimiento" 
                    type="text" 
                    placeholder="Establecimiento..."
                    class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                />
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Estado de Seguimiento</label>
                <select 
                    v-model="filterEstado"
                    class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                >
                    <option value="todos">Todos los Estados</option>
                    <option value="Abierto">Abierto</option>
                    <option value="En proceso">En proceso</option>
                    <option value="Cerrado">Cerrado</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Fecha del Resultado</label>
                <input 
                    v-model="filterFecha" 
                    type="date" 
                    class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                />
            </div>
        </div>

        <!-- Deviations Table -->
        <div class="glass-card backdrop-blur-sm rounded-2xl border border-white/20 shadow-premium overflow-hidden">
            <div v-if="loading" class="py-16 text-center text-sm text-slate-400">
                <span class="material-symbols-outlined text-4xl animate-spin text-white">sync</span>
                <p class="mt-2 font-bold">Cargando registros...</p>
            </div>

            <div v-else-if="filteredDesviaciones.length === 0" class="py-20 text-center">
                <span class="material-symbols-outlined text-5xl text-slate-400">science</span>
                <p class="text-sm font-semibold text-white mt-4">No se encontraron desviaciones de laboratorio</p>
                <p class="text-xs text-slate-400 mt-1">Intenta ajustando los criterios de filtrado anteriores.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/10 text-[10px] font-bold uppercase text-slate-400 tracking-wider">
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
                    <tbody class="divide-y divide-slate-200 text-xs">
                        <tr v-for="d in filteredDesviaciones" :key="d.id" class="hover:bg-black/20 transition-colors">
                            <!-- Sample Code -->
                            <td class="px-6 py-4 font-mono font-bold text-white">
                                {{ d.codigo_muestra }}
                            </td>

                            <!-- Inspector -->
                            <td class="px-6 py-4">
                                <p class="font-bold text-white">{{ d.inspector_nombre }}</p>
                                <p class="text-[9px] text-slate-400 font-mono">Código: {{ d.inspector_codigo }}</p>
                            </td>

                            <!-- Establishment -->
                            <td class="px-6 py-4">
                                <p class="font-bold text-white">{{ d.establecimiento }}</p>
                            </td>

                            <!-- Date -->
                            <td class="px-6 py-4 text-slate-400 font-semibold">
                                {{ d.fecha_resultado }}
                            </td>

                            <!-- Type & Parameter -->
                            <td class="px-6 py-4">
                                <p class="font-bold text-white">{{ d.tipo_analisis }}</p>
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
                            <td class="px-6 py-4 text-center font-bold text-gray-300 font-mono">
                                <span class="inline-flex items-center gap-1 bg-slate-100 px-2 py-0.5 rounded text-[10px] border border-white/10">
                                    <span class="material-symbols-outlined text-xs">attach_file</span>
                                    {{ d.total_adjuntos }}
                                </span>
                            </td>

                            <!-- Detailed Actions -->
                            <td class="px-6 py-4">
                                <router-link 
                                    :to="`/desviaciones/${d.id}`"
                                    class="text-blue-600 hover:text-blue-700 font-bold text-xs transition-colors flex items-center gap-1"
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
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron recuperar los registros del servidor.',
            confirmButtonColor: '#0a192f'
        });
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
