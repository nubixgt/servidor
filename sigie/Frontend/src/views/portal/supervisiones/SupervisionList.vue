<template>
    <div>
        <!-- Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-on-surface">Supervisiones a Establecimientos</h1>
                <p class="text-sm text-on-surface-variant mt-1">Historial y seguimiento de supervisiones a establecimientos registradas.</p>
            </div>
            <div class="flex items-center gap-3 self-start sm:self-center">
                <button 
                    v-if="auth.role === 'administrador'"
                    @click="exportToExcel" 
                    :disabled="supervisiones.length === 0"
                    class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-md shadow-sm transition-colors flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed border border-emerald-700"
                >
                    <span class="material-symbols-outlined text-sm">download</span>
                    Exportar a Excel
                </button>
                <router-link 
                    v-if="auth.role === 'inspector'"
                    to="/supervisiones/nuevo" 
                    class="px-5 py-3 bg-primary hover:bg-primary-dim text-on-primary font-bold text-xs rounded-md shadow-sm transition-colors flex items-center justify-center gap-2 border border-primary-dim"
                >
                    <span class="material-symbols-outlined text-sm">playlist_add_check</span>
                    Nueva Supervisión
                </router-link>
            </div>
        </div>

        <!-- Filters Bar -->
        <div class="bg-white p-6 rounded-md border border-surface-container shadow-sm mb-8 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Búsqueda Global</label>
                <input 
                    v-model="searchTerm" 
                    type="text" 
                    placeholder="Establecimiento, Inspector..."
                    class="w-full bg-slate-50 border border-slate-300 rounded-md px-4 py-2.5 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                />
            </div>
            <div>
                <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Establecimiento</label>
                <input 
                    v-model="filterEstablecimiento" 
                    type="text" 
                    placeholder="Establecimiento..."
                    class="w-full bg-slate-50 border border-slate-300 rounded-md px-4 py-2.5 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                />
            </div>
            <div>
                <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Estado del Hallazgo</label>
                <select 
                    v-model="filterEstado"
                    class="w-full bg-slate-50 border border-slate-300 rounded-md px-4 py-2.5 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                >
                    <option value="todos">Todos los Estados</option>
                    <option value="Abierto">Abierto</option>
                    <option value="En proceso">En proceso</option>
                    <option value="Cerrado">Cerrado</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Fecha de Supervisión</label>
                <input 
                    v-model="filterFecha" 
                    type="date" 
                    class="w-full bg-slate-50 border border-slate-300 rounded-md px-4 py-2.5 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                />
            </div>
        </div>

        <!-- Supervision Table -->
        <div class="bg-white rounded-md border border-surface-container shadow-sm overflow-hidden">
            <div v-if="loading" class="py-16 text-center text-sm text-on-surface-variant">
                <span class="material-symbols-outlined text-4xl animate-spin text-primary">sync</span>
                <p class="mt-2 font-bold">Cargando registros...</p>
            </div>

            <div v-else-if="filteredSupervisiones.length === 0" class="py-20 text-center">
                <span class="material-symbols-outlined text-5xl text-outline-variant">store</span>
                <p class="text-sm font-semibold text-on-surface mt-4">No se encontraron supervisiones registradas</p>
                <p class="text-xs text-on-surface-variant mt-1">Intenta ajustando los criterios de filtrado anteriores.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-200 text-[10px] font-extrabold uppercase text-slate-700 tracking-wider">
                            <th class="px-6 py-4">ID / Establecimiento</th>
                            <th class="px-6 py-4">Inspector Responsable</th>
                            <th class="px-6 py-4">Fecha Supervisión</th>
                            <th class="px-6 py-4">Norma Asociada</th>
                            <th class="px-6 py-4 text-center">Estado</th>
                            <th class="px-6 py-4 text-center">Adjuntos</th>
                            <th class="px-6 py-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-xs">
                        <tr v-for="s in filteredSupervisiones" :key="s.id" class="hover:bg-slate-50 transition-colors">
                            <!-- Establishment -->
                            <td class="px-6 py-4">
                                <p class="font-bold text-on-surface text-sm">{{ s.establecimiento }}</p>
                                <p class="text-[9px] text-on-surface-variant font-mono">ID: {{ s.id }}</p>
                            </td>

                            <!-- Inspector -->
                            <td class="px-6 py-4">
                                <p class="font-bold text-on-surface">{{ s.inspector_nombre }}</p>
                                <p class="text-[9px] text-on-surface-variant font-mono">Código: {{ s.inspector_codigo }}</p>
                            </td>

                            <!-- Date -->
                            <td class="px-6 py-4 text-on-surface-variant font-semibold">
                                {{ s.fecha_supervision }}
                            </td>

                            <!-- Norm -->
                            <td class="px-6 py-4 text-on-surface-variant">
                                <span v-if="s.norma_especifica" class="font-semibold text-slate-700 bg-slate-100 px-2 py-1 rounded border border-slate-200">
                                    {{ s.norma_especifica }}
                                </span>
                                <span v-else class="italic text-slate-400">Ninguna</span>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 text-center">
                                <span 
                                    :class="['px-2.5 py-1 rounded-full text-[10px] font-bold border uppercase tracking-wide', 
                                             s.estado_hallazgo === 'Abierto' ? 'bg-red-50 border-red-200 text-red-700' :
                                             s.estado_hallazgo === 'En proceso' ? 'bg-amber-50 border-amber-200 text-amber-700' :
                                             'bg-emerald-50 border-emerald-200 text-emerald-700']"
                                >
                                    {{ s.estado_hallazgo }}
                                </span>
                            </td>

                            <!-- Attachments count -->
                            <td class="px-6 py-4 text-center font-bold text-slate-500 font-mono">
                                <span class="inline-flex items-center gap-1 bg-slate-100 px-2 py-0.5 rounded text-[10px] border border-slate-200">
                                    <span class="material-symbols-outlined text-xs">attach_file</span>
                                    {{ s.total_adjuntos }}
                                </span>
                            </td>

                            <!-- Detailed Actions -->
                            <td class="px-6 py-4">
                                <router-link 
                                    :to="`/supervisiones/${s.id}`"
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
const supervisiones = ref([]);
const loading = ref(false);

// Filters State
const searchTerm = ref('');
const filterEstablecimiento = ref('');
const filterEstado = ref('todos');
const filterFecha = ref('');

const fetchSupervisiones = async () => {
    loading.value = true;
    try {
        const response = await api.get('/supervisiones');
        if (response.data?.status === 'success') {
            supervisiones.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar supervisiones', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron recuperar los registros del servidor.',
            confirmButtonColor: '#005a9c'
        });
    } finally {
        loading.value = false;
    }
};

const filteredSupervisiones = computed(() => {
    return supervisiones.value.filter(s => {
        const globalSearch = !searchTerm.value || 
            s.establecimiento.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
            s.inspector_nombre.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
            (s.norma_especifica && s.norma_especifica.toLowerCase().includes(searchTerm.value.toLowerCase())) ||
            s.hallazgos_detectados.toLowerCase().includes(searchTerm.value.toLowerCase());
            
        const matchesEstablecimiento = !filterEstablecimiento.value || 
            s.establecimiento.toLowerCase().includes(filterEstablecimiento.value.toLowerCase());
            
        const matchesEstado = filterEstado.value === 'todos' || s.estado_hallazgo === filterEstado.value;
        const matchesFecha = !filterFecha.value || s.fecha_supervision === filterFecha.value;

        return globalSearch && matchesEstablecimiento && matchesEstado && matchesFecha;
    });
});

const exportToExcel = () => {
    const dataToExport = filteredSupervisiones.value.map(s => ({
        'ID': s.id,
        'Establecimiento': s.establecimiento,
        'Inspector': s.inspector_nombre,
        'Fecha Supervisión': s.fecha_supervision,
        'Hallazgos Detectados': s.hallazgos_detectados,
        'Norma Asociada': s.norma_especifica || 'Ninguna',
        'Estado': s.estado_hallazgo,
        'Fecha Cumplimiento': s.fecha_cumplimiento || 'Pendiente',
        'Verificación Oficial': s.verificacion_oficial || 'N/A',
        'Total Adjuntos': s.total_adjuntos,
        'Observaciones': s.observaciones || ''
    }));

    const worksheet = XLSX.utils.json_to_sheet(dataToExport);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Supervisiones');

    XLSX.writeFile(workbook, 'Reporte_Supervisiones_SIGIE.xlsx');
};

onMounted(() => {
    fetchSupervisiones();
});
</script>
