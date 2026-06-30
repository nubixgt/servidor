<template>
    <div>
        <!-- Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-white font-headline">No Conformidades (Inspección Permanente)</h1>
                <p class="text-xs text-white/60 mt-1">Historial y seguimiento de no conformidades del personal de rastros.</p>
            </div>
            <div class="flex items-center gap-3 self-start sm:self-center">
                <button 
                    v-if="auth.role === 'administrador'"
                    @click="exportToExcel" 
                    :disabled="noConformidades.length === 0"
                    class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded shadow transition-colors flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span class="material-symbols-outlined text-lg">download</span>
                    Exportar a Excel
                </button>
                <router-link 
                    v-if="auth.role === 'inspector'"
                    to="/noconformidades/nuevo" 
                    class="px-4 py-2.5 bg-[#0a192f] hover:bg-[#122347] text-white font-bold text-xs rounded shadow transition-colors flex items-center justify-center gap-2"
                >
                    <span class="material-symbols-outlined text-lg">report</span>
                    Nueva No Conformidad
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
                    placeholder="Personal, Rastro, Inspector..."
                    class="w-full bg-black/20 border border-slate-300 rounded-md px-4 py-2 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                />
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Rastro / Personal</label>
                <input 
                    v-model="filterEstablecimiento" 
                    type="text" 
                    placeholder="Rastro..."
                    class="w-full bg-black/20 border border-slate-300 rounded-md px-4 py-2 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                />
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Estado del Hallazgo</label>
                <select 
                    v-model="filterEstado"
                    class="w-full bg-black/20 border border-slate-300 rounded-md px-4 py-2 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                >
                    <option value="todos">Todos los Estados</option>
                    <option value="Abierto">Abierto</option>
                    <option value="En proceso">En proceso</option>
                    <option value="Cerrado">Cerrado</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Fecha de Inspección</label>
                <input 
                    v-model="filterFecha" 
                    type="date" 
                    class="w-full bg-black/20 border border-slate-300 rounded-md px-4 py-2 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                />
            </div>
        </div>

        <!-- No Conformidades Table -->
        <div class="glass-card backdrop-blur-sm rounded-2xl border border-white/20 shadow-premium overflow-hidden">
            <div v-if="loading" class="py-16 text-center text-sm text-slate-400">
                <span class="material-symbols-outlined text-4xl animate-spin text-white">sync</span>
                <p class="mt-2 font-bold">Cargando registros...</p>
            </div>

            <div v-else-if="filteredNoConformidades.length === 0" class="py-20 text-center">
                <span class="material-symbols-outlined text-5xl text-slate-400">assignment_late</span>
                <p class="text-sm font-semibold text-white mt-4">No se encontraron no conformidades registradas</p>
                <p class="text-xs text-slate-400 mt-1">Intenta ajustando los criterios de filtrado anteriores.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 border-b border-white/10 text-[10px] font-extrabold uppercase text-slate-400 tracking-wider">
                            <th class="px-6 py-4">ID / Rastro / Personal</th>
                            <th class="px-6 py-4">Inspector Oficial</th>
                            <th class="px-6 py-4">Fecha Inspección</th>
                            <th class="px-6 py-4">Normativa Asociada</th>
                            <th class="px-6 py-4 text-center">Estado</th>
                            <th class="px-6 py-4 text-center">Adjuntos</th>
                            <th class="px-6 py-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-xs">
                        <tr v-for="nc in filteredNoConformidades" :key="nc.id" class="hover:bg-black/20/50 transition-colors">
                            <!-- Establishment -->
                            <td class="px-6 py-4">
                                <p class="font-bold text-white text-sm">{{ nc.establecimiento }}</p>
                                <p class="text-[9px] text-slate-400 font-mono">ID: {{ nc.id }}</p>
                            </td>

                            <!-- Inspector -->
                            <td class="px-6 py-4">
                                <p class="font-bold text-white">{{ nc.inspector_nombre }}</p>
                                <p class="text-[9px] text-slate-400 font-mono">Código: {{ nc.inspector_codigo }}</p>
                            </td>

                            <!-- Date -->
                            <td class="px-6 py-4 text-slate-400 font-semibold">
                                {{ nc.fecha_inspeccion }}
                            </td>

                            <!-- Norm -->
                            <td class="px-6 py-4 text-slate-400">
                                <span v-if="nc.norma_especifica" class="font-semibold text-gray-300 bg-slate-100 px-2 py-1 rounded">
                                    {{ nc.norma_especifica }}
                                </span>
                                <span v-else class="italic text-slate-400">Ninguna</span>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 text-center">
                                <span 
                                    :class="['px-2.5 py-1 rounded text-[10px] font-bold border uppercase tracking-wide', 
                                             nc.estado_hallazgo === 'Abierto' ? 'bg-red-50 border-red-200 text-red-700' :
                                             nc.estado_hallazgo === 'En proceso' ? 'bg-amber-50 border-amber-200 text-amber-700' :
                                             'bg-emerald-50 border-emerald-200 text-emerald-700']"
                                >
                                    {{ nc.estado_hallazgo }}
                                </span>
                            </td>

                            <!-- Attachments count -->
                            <td class="px-6 py-4 text-center font-bold text-gray-300 font-mono">
                                <span class="inline-flex items-center gap-1 bg-slate-100 px-2 py-0.5 rounded text-[10px]">
                                    <span class="material-symbols-outlined text-xs">attach_file</span>
                                    {{ nc.total_adjuntos }}
                                </span>
                            </td>

                            <!-- Detailed Actions -->
                            <td class="px-6 py-4">
                                <router-link 
                                    :to="`/noconformidades/${nc.id}`"
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
const noConformidades = ref([]);
const loading = ref(false);

// Filters State
const searchTerm = ref('');
const filterEstablecimiento = ref('');
const filterEstado = ref('todos');
const filterFecha = ref('');

const fetchNoConformidades = async () => {
    loading.value = true;
    try {
        const response = await api.get('/noconformidades');
        if (response.data?.status === 'success') {
            noConformidades.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar no conformidades', error);
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

const filteredNoConformidades = computed(() => {
    return noConformidades.value.filter(nc => {
        const globalSearch = !searchTerm.value || 
            nc.establecimiento.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
            nc.inspector_nombre.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
            (nc.norma_especifica && nc.norma_especifica.toLowerCase().includes(searchTerm.value.toLowerCase())) ||
            nc.hallagzos_detectados?.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
            nc.hallazgos_detectados.toLowerCase().includes(searchTerm.value.toLowerCase());
            
        const matchesEstablecimiento = !filterEstablecimiento.value || 
            nc.establecimiento.toLowerCase().includes(filterEstablecimiento.value.toLowerCase());
            
        const matchesEstado = filterEstado.value === 'todos' || nc.estado_hallazgo === filterEstado.value;
        const matchesFecha = !filterFecha.value || nc.fecha_inspeccion === filterFecha.value;

        return globalSearch && matchesEstablecimiento && matchesEstado && matchesFecha;
    });
});

const exportToExcel = () => {
    const dataToExport = filteredNoConformidades.value.map(nc => ({
        'ID': nc.id,
        'Rastro / Personal': nc.establecimiento,
        'Inspector': nc.inspector_nombre,
        'Fecha Inspección': nc.fecha_inspeccion,
        'No Conformidad / Hallazgos': nc.hallazgos_detectados,
        'Norma Asociada': nc.norma_especifica || 'Ninguna',
        'Estado': nc.estado_hallazgo,
        'Fecha Cumplimiento': nc.fecha_cumplimiento || 'Pendiente',
        'Verificación Oficial': nc.verificacion_oficial || 'N/A',
        'Total Adjuntos': nc.total_adjuntos,
        'Observaciones': nc.observaciones || ''
    }));

    const worksheet = XLSX.utils.json_to_sheet(dataToExport);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'No Conformidades');

    XLSX.writeFile(workbook, 'Reporte_NoConformidades_Rastros_SIGIE.xlsx');
};

onMounted(() => {
    fetchNoConformidades();
});
</script>
