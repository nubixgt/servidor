<template>
    <div>
        <!-- Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-on-surface">Control de Sacrificios</h1>
                <p class="text-sm text-on-surface-variant mt-1">Consulta, filtra y exporta la información de trazabilidad de animales sacrificados.</p>
            </div>
            <div class="flex items-center gap-3">
                <router-link 
                    to="/sacrificio/nuevo" 
                    class="px-5 py-3 bg-primary hover:bg-primary-dim text-white font-bold text-xs rounded-xl shadow transition-all flex items-center justify-center gap-2"
                >
                    <span class="material-symbols-outlined text-lg">add</span>
                    Nuevo Registro
                </router-link>
                <button 
                    @click="exportToExcel" 
                    :disabled="sacrificios.length === 0"
                    class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span class="material-symbols-outlined text-lg">download</span>
                    Exportar a Excel
                </button>
            </div>
        </div>

        <!-- Filters Bar -->
        <div class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient mb-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Propietario</label>
                    <input 
                        v-model="filterPropietario" 
                        type="text" 
                        placeholder="Nombre del propietario..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                    />
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Lote</label>
                    <input 
                        v-model="filterLote" 
                        type="text" 
                        placeholder="Código de lote..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                    />
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Procedencia (Finca / Muni / Depto)</label>
                    <input 
                        v-model="filterProcedencia" 
                        type="text" 
                        placeholder="Lugar de procedencia..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                    />
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Fecha</label>
                    <input 
                        v-model="filterFecha" 
                        type="date" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                    />
                </div>
            </div>

            <!-- Toggles / Checkboxes -->
            <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap items-center gap-6">
                <label class="flex items-center gap-2 text-xs font-bold text-on-surface cursor-pointer select-none">
                    <input 
                        type="checkbox" 
                        v-model="filterMuestreoOficial"
                        class="w-4 h-4 text-primary focus:ring-primary border-slate-300 rounded cursor-pointer"
                    />
                    Ver solo lotes con muestreo oficial
                </label>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-surface-container-lowest rounded-2xl border border-surface-container shadow-ambient overflow-hidden">
            <div v-if="loading" class="py-16 text-center text-sm text-on-surface-variant">
                <span class="material-symbols-outlined text-4xl animate-spin text-primary">sync</span>
                <p class="mt-2 font-bold">Cargando registros de sacrificio...</p>
            </div>

            <div v-else-if="filteredSacrificios.length === 0" class="py-20 text-center">
                <span class="material-symbols-outlined text-5xl text-outline-variant">assignment</span>
                <p class="text-sm font-semibold text-on-surface mt-4">No se encontraron registros de animales sacrificados</p>
                <p class="text-xs text-on-surface-variant mt-1">Intenta ajustando los criterios de filtrado anteriores.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100 text-[10px] font-extrabold uppercase text-on-surface-variant tracking-wider">
                            <th class="px-6 py-4">Fecha</th>
                            <th class="px-6 py-4">Lote</th>
                            <th class="px-6 py-4">Propietario / Reportado por</th>
                            <th class="px-6 py-4">Procedencia</th>
                            <th class="px-6 py-4">Clasificación</th>
                            <th class="px-6 py-4 text-center">Cantidad</th>
                            <th class="px-6 py-4 text-center">Muestreo Oficial</th>
                            <th class="px-6 py-4 text-center">Documento</th>
                            <th class="px-6 py-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <tr v-for="s in filteredSacrificios" :key="s.id" class="hover:bg-slate-50/50 transition-colors">
                            <!-- Fecha -->
                            <td class="px-6 py-4 font-semibold text-on-surface-variant">
                                {{ s.fecha_sacrificio }}
                            </td>

                            <!-- Lote -->
                            <td class="px-6 py-4 font-bold text-on-surface font-mono">
                                {{ s.lote }}
                            </td>

                            <!-- Propietario & Inspector details -->
                            <td class="px-6 py-4">
                                <p class="font-bold text-on-surface">{{ s.propietario }}</p>
                                <p class="text-[10px] text-on-surface-variant mt-0.5">Reportado por: {{ s.inspector_nombre }} ({{ s.inspector_codigo }})</p>
                            </td>

                            <!-- Procedencia -->
                            <td class="px-6 py-4">
                                <p class="font-bold text-on-surface">{{ s.procedencia_finca }}</p>
                                <p class="text-[10px] text-on-surface-variant mt-0.5 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs text-outline">place</span>
                                    {{ s.procedencia_municipio }}, {{ s.procedencia_departamento }}
                                </p>
                            </td>

                            <!-- Clasificación -->
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded bg-slate-100 text-on-secondary-container font-semibold">
                                    {{ s.clasificacion }}
                                </span>
                            </td>

                            <!-- Cantidad -->
                            <td class="px-6 py-4 text-center font-bold text-on-surface">
                                {{ s.cantidad }}
                            </td>

                            <!-- Muestreo Oficial -->
                            <td class="px-6 py-4 text-center">
                                <span 
                                    :class="['px-2.5 py-1 rounded-full text-[10px] font-bold border', 
                                             s.muestreo_oficial == 1 
                                                ? 'bg-emerald-50 border-emerald-200 text-emerald-700' 
                                                : 'bg-slate-50 border-slate-200 text-slate-500']"
                                >
                                    {{ s.muestreo_oficial == 1 ? 'Sí' : 'No' }}
                                </span>
                            </td>

                            <!-- Documento preview -->
                            <td class="px-6 py-4 text-center">
                                <a 
                                    v-if="s.documento_path" 
                                    :href="resolverRuta(s.documento_path)" 
                                    target="_blank"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 hover:border-primary bg-slate-50 hover:bg-primary/5 transition-all text-primary"
                                >
                                    <span class="material-symbols-outlined text-lg">
                                        {{ s.documento_path.endsWith('.pdf') ? 'picture_as_pdf' : 'image' }}
                                    </span>
                                </a>
                                <span v-else class="text-outline-variant text-[10px]">Sin Adjunto</span>
                            </td>

                            <!-- Detailed Actions -->
                            <td class="px-6 py-4">
                                <button 
                                    @click="verDetalles(s)"
                                    class="text-primary hover:text-primary-dim font-bold text-xs transition-colors flex items-center gap-1"
                                >
                                    <span class="material-symbols-outlined text-sm">visibility</span>
                                    Detalles
                                </button>
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
import api, { getBackendBaseUrl } from '../../../services/api.js';
import Swal from 'sweetalert2';
import * as XLSX from 'xlsx';

const sacrificios = ref([]);
const loading = ref(false);

// Filter fields
const filterPropietario = ref('');
const filterLote = ref('');
const filterProcedencia = ref('');
const filterFecha = ref('');
const filterMuestreoOficial = ref(false);

const resolverRuta = (rutaRelativa) => {
    return `${getBackendBaseUrl()}/${rutaRelativa}`;
};

const fetchHistorial = async () => {
    loading.value = true;
    try {
        const response = await api.get('/animales');
        if (response.data?.status === 'success') {
            sacrificios.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar historial de sacrificios', error);
        Swal.fire('Error', 'No se pudieron recuperar los registros del servidor.', 'error');
    } finally {
        loading.value = false;
    }
};

// Client-side filtering logic
const filteredSacrificios = computed(() => {
    return sacrificios.value.filter(s => {
        const propietarioMatch = !filterPropietario.value || 
            s.propietario.toLowerCase().includes(filterPropietario.value.toLowerCase());
            
        const loteMatch = !filterLote.value || 
            s.lote.toLowerCase().includes(filterLote.value.toLowerCase());
            
        const procedenciaMatch = !filterProcedencia.value || 
            s.procedencia_finca.toLowerCase().includes(filterProcedencia.value.toLowerCase()) ||
            s.procedencia_municipio.toLowerCase().includes(filterProcedencia.value.toLowerCase()) ||
            s.procedencia_departamento.toLowerCase().includes(filterProcedencia.value.toLowerCase());
            
        const fechaMatch = !filterFecha.value || s.fecha_sacrificio === filterFecha.value;

        const muestreoMatch = !filterMuestreoOficial.value || s.muestreo_oficial == 1;

        return propietarioMatch && loteMatch && procedenciaMatch && fechaMatch && muestreoMatch;
    });
});

// Details Modal
const verDetalles = (s) => {
    let htmlContent = `
        <div class="text-left text-xs space-y-4">
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 space-y-2">
                <p><strong>Fecha de Sacrificio:</strong> ${s.fecha_sacrificio}</p>
                <p><strong>Propietario del Lote:</strong> ${s.propietario}</p>
                <p><strong>Lote Asignado:</strong> ${s.lote}</p>
                <p><strong>Clasificación:</strong> ${s.clasificacion}</p>
                <p><strong>Cantidad:</strong> ${s.cantidad} animales</p>
                <p><strong>Muestreo Oficial:</strong> ${s.muestreo_oficial == 1 ? '<span class="text-emerald-600 font-bold">Sí (Sometido a muestreo)</span>' : 'No'}</p>
                <p><strong>Lugar de Procedencia:</strong> Finca ${s.procedencia_finca}, ${s.procedencia_municipio}, ${s.procedencia_departamento}</p>
                <p><strong>Reportado por:</strong> ${s.inspector_nombre} (${s.inspector_codigo} | ${s.inspector_area})</p>
            </div>
            
            <div>
                <h4 class="font-extrabold text-on-surface mb-1 uppercase tracking-wide text-[10px]">Decomisos Realizados:</h4>
                <div class="p-3 rounded-xl border border-slate-200 bg-white min-h-[50px]">
                    ${s.decomisos ? s.decomisos : '<span class="text-on-surface-variant italic">Ningún decomiso reportado.</span>'}
                </div>
            </div>

            <div>
                <h4 class="font-extrabold text-on-surface mb-1 uppercase tracking-wide text-[10px]">Observaciones:</h4>
                <div class="p-3 rounded-xl border border-slate-200 bg-white font-serif max-h-40 overflow-y-auto italic">
                    ${s.observaciones ? s.observaciones : 'Ninguna observación registrada.'}
                </div>
            </div>

            ${s.documento_path ? `
            <div class="pt-2 border-t border-slate-100">
                <h4 class="font-extrabold text-on-surface mb-2 uppercase tracking-wide text-[10px]">Documentación Adjunta:</h4>
                ${s.documento_path.endsWith('.pdf') ? `
                    <a href="${resolverRuta(s.documento_path)}" target="_blank" class="flex items-center gap-2 p-3 rounded-xl border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 font-bold transition-all">
                        <span class="material-symbols-outlined">picture_as_pdf</span>
                        Ver Guía Sanitaria / Documento PDF
                    </a>
                ` : `
                    <div class="text-center bg-slate-50 border border-slate-200 rounded-xl p-2">
                        <a href="${resolverRuta(s.documento_path)}" target="_blank" title="Haga clic para ampliar">
                            <img src="${resolverRuta(s.documento_path)}" class="max-h-48 mx-auto object-contain rounded-lg shadow-sm border hover:scale-102 transition-transform" />
                        </a>
                    </div>
                `}
            </div>
            ` : ''}
        </div>
    `;

    Swal.fire({
        title: 'Detalle del Sacrificio',
        html: htmlContent,
        width: '600px',
        confirmButtonColor: '#0284c7',
        confirmButtonText: 'Cerrar'
    });
};

// SheetJS Excel Exporter
const exportToExcel = () => {
    const dataToExport = filteredSacrificios.value.map(s => ({
        'Fecha de Sacrificio': s.fecha_sacrificio,
        'Lote': s.lote,
        'Propietario': s.propietario,
        'Clasificación': s.clasificacion,
        'Cantidad': s.cantidad,
        'Finca de Procedencia': s.procedencia_finca,
        'Municipio': s.procedencia_municipio,
        'Departamento': s.procedencia_departamento,
        'Decomisos': s.decomisos || 'Ninguno',
        'Muestreo Oficial': s.muestreo_oficial == 1 ? 'Sí' : 'No',
        'Observaciones': s.observaciones || '',
        'Reportado Por': s.inspector_nombre,
        'Código Inspector': s.inspector_codigo
    }));

    const worksheet = XLSX.utils.json_to_sheet(dataToExport);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Animales Sacrificados');
    
    // Auto-fit column widths
    const cols = [];
    if (dataToExport.length > 0) {
        Object.keys(dataToExport[0]).forEach(key => {
            let maxLength = key.length;
            dataToExport.forEach(row => {
                const value = String(row[key] || '');
                if (value.length > maxLength) {
                    maxLength = value.length;
                }
            });
            cols.push({ wch: maxLength + 3 });
        });
        worksheet['!cols'] = cols;
    }

    XLSX.writeFile(workbook, 'Reporte_Animales_Sacrificados_SIGIE.xlsx');
};

onMounted(() => {
    fetchHistorial();
});
</script>
