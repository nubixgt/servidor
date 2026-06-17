<template>
    <div>
        <!-- Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-on-surface">Historial de Check-ins</h1>
                <p class="text-sm text-on-surface-variant mt-1">Consulta y descarga los registros de geolocalización de inspectores en campo.</p>
            </div>
            <button 
                @click="exportToExcel" 
                :disabled="checkins.length === 0"
                class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition-all flex items-center justify-center gap-2 self-start sm:self-center disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span class="material-symbols-outlined text-lg">download</span>
                Exportar a Excel
            </button>
        </div>

        <!-- Filters Bar -->
        <div class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient mb-8 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Inspector</label>
                <input 
                    v-model="filterInspector" 
                    type="text" 
                    placeholder="Nombre o código..."
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
                <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Estado</label>
                <select 
                    v-model="filterEstado"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                >
                    <option value="todos">Todos los Estados</option>
                    <option value="exitoso">Sin Novedades (Exitoso)</option>
                    <option value="con_novedades">Con Novedades / Alertas</option>
                </select>
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

        <!-- Check-ins Table -->
        <div class="bg-surface-container-lowest rounded-2xl border border-surface-container shadow-ambient overflow-hidden">
            <div v-if="loading" class="py-16 text-center text-sm text-on-surface-variant">
                <span class="material-symbols-outlined text-4xl animate-spin text-primary">sync</span>
                <p class="mt-2 font-bold">Cargando historial de registros...</p>
            </div>

            <div v-else-if="filteredCheckins.length === 0" class="py-20 text-center">
                <span class="material-symbols-outlined text-5xl text-outline-variant">assignment_turned_in</span>
                <p class="text-sm font-semibold text-on-surface mt-4">No se encontraron registros de check-in</p>
                <p class="text-xs text-on-surface-variant mt-1">Intenta ajustando los criterios de filtrado anteriores.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100 text-[10px] font-extrabold uppercase text-on-surface-variant tracking-wider">
                            <th class="px-6 py-4">Inspector</th>
                            <th class="px-6 py-4">Establecimiento</th>
                            <th class="px-6 py-4">Fecha y Hora</th>
                            <th class="px-6 py-4 text-center">Ubicación GPS</th>
                            <th class="px-6 py-4 text-center">Estado</th>
                            <th class="px-6 py-4 text-center">Foto</th>
                            <th class="px-6 py-4 text-center">Firma</th>
                            <th class="px-6 py-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <tr v-for="c in filteredCheckins" :key="c.id" class="hover:bg-slate-50/50 transition-colors">
                            <!-- Inspector details -->
                            <td class="px-6 py-4">
                                <p class="font-bold text-on-surface">{{ c.inspector_nombre }}</p>
                                <p class="text-[10px] text-on-surface-variant font-mono mt-0.5">Código: {{ c.inspector_codigo }} | {{ c.inspector_area }}</p>
                            </td>

                            <!-- Visit details -->
                            <td class="px-6 py-4">
                                <p class="font-bold text-on-surface">{{ c.establecimiento || 'Visita Libre' }}</p>
                                <p class="text-[10px] text-on-surface-variant mt-0.5 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs text-outline">place</span>
                                    {{ c.direccion || 'Ubicación registrada en mapa' }}
                                </p>
                            </td>

                            <!-- Time -->
                            <td class="px-6 py-4 text-on-surface-variant font-semibold">
                                {{ c.fecha_hora }}
                            </td>

                            <!-- Location coordinates -->
                            <td class="px-6 py-4 text-center">
                                <a 
                                    :href="`https://www.google.com/maps/search/?api=1&query=${c.latitud},${c.longitud}`" 
                                    target="_blank"
                                    class="inline-flex items-center gap-1 text-primary hover:underline font-mono text-[11px] font-bold bg-primary/5 px-2.5 py-1 rounded-lg"
                                >
                                    <span class="material-symbols-outlined text-sm">open_in_new</span>
                                    {{ parseFloat(c.latitud).toFixed(5) }}, {{ parseFloat(c.longitud).toFixed(5) }}
                                </a>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 text-center">
                                <span 
                                    :class="['px-2.5 py-1 rounded-full text-[10px] font-bold border', 
                                             c.estado === 'exitoso' 
                                                ? 'bg-emerald-50 border-emerald-200 text-emerald-700' 
                                                : 'bg-red-50 border-red-200 text-red-700']"
                                >
                                    {{ c.estado === 'exitoso' ? 'Exitoso' : 'Novedades' }}
                                </span>
                            </td>

                            <!-- Photo preview -->
                            <td class="px-6 py-4 text-center">
                                <button 
                                    v-if="c.foto_path" 
                                    @click="verImagen(resolverRuta(c.foto_path), 'Evidencia Fotográfica')"
                                    class="w-8 h-8 rounded-lg overflow-hidden border border-slate-200 hover:border-primary hover:scale-105 transition-all inline-flex items-center justify-center bg-slate-100"
                                >
                                    <img :src="resolverRuta(c.foto_path)" class="w-full h-full object-cover" />
                                </button>
                                <span v-else class="text-outline-variant text-[10px]">Sin Foto</span>
                            </td>

                            <!-- Signature preview -->
                            <td class="px-6 py-4 text-center">
                                <button 
                                    v-if="c.firma_path" 
                                    @click="verImagen(resolverRuta(c.firma_path), 'Firma del Inspector')"
                                    class="w-10 h-7 rounded border border-slate-200 hover:border-primary hover:scale-105 transition-all inline-flex items-center justify-center bg-slate-50 p-0.5"
                                >
                                    <img :src="resolverRuta(c.firma_path)" class="max-h-full max-w-full object-contain" />
                                </button>
                                <span v-else class="text-outline-variant text-[10px]">Sin Firma</span>
                            </td>

                            <!-- Detailed Actions -->
                            <td class="px-6 py-4">
                                <button 
                                    @click="verDetalles(c)"
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

const checkins = ref([]);
const loading = ref(false);

// Filter fields
const filterInspector = ref('');
const filterEstablecimiento = ref('');
const filterEstado = ref('todos');
const filterFecha = ref('');

const resolverRuta = (rutaRelativa) => {
    return `${getBackendBaseUrl()}/${rutaRelativa}`;
};

const fetchHistorial = async () => {
    loading.value = true;
    try {
        const response = await api.get('/checkin/historial');
        if (response.data?.status === 'success') {
            checkins.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar historial', error);
        Swal.fire('Error', 'No se pudieron recuperar los registros del servidor.', 'error');
    } finally {
        loading.value = false;
    }
};

// Client-side filtering logic
const filteredCheckins = computed(() => {
    return checkins.value.filter(c => {
        const inspectorMatch = !filterInspector.value || 
            c.inspector_nombre.toLowerCase().includes(filterInspector.value.toLowerCase()) ||
            c.inspector_codigo.toLowerCase().includes(filterInspector.value.toLowerCase());
            
        const establecimientoMatch = !filterEstablecimiento.value || 
            (c.establecimiento && c.establecimiento.toLowerCase().includes(filterEstablecimiento.value.toLowerCase()));
            
        const estadoMatch = filterEstado.value === 'todos' || c.estado === filterEstado.value;
        
        const fechaMatch = !filterFecha.value || c.fecha_hora.startsWith(filterFecha.value);

        return inspectorMatch && establecimientoMatch && estadoMatch && fechaMatch;
    });
});

// Photo / Signature modal lightbox
const verImagen = (url, titulo) => {
    Swal.fire({
        title: titulo,
        imageUrl: url,
        imageAlt: titulo,
        confirmButtonColor: '#0284c7',
        confirmButtonText: 'Cerrar Vista'
    });
};

// Details Modal
const verDetalles = (c) => {
    let htmlContent = `
        <div class="text-left text-xs space-y-4">
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 space-y-2">
                <p><strong>Inspector:</strong> ${c.inspector_nombre} (${c.inspector_codigo})</p>
                <p><strong>Área:</strong> ${c.inspector_area}</p>
                <p><strong>Establecimiento:</strong> ${c.establecimiento || 'N/A'}</p>
                <p><strong>Dirección:</strong> ${c.direccion || 'N/A'}</p>
                <p><strong>Tipo Inspección:</strong> ${c.tipo_inspeccion || 'N/A'}</p>
                <p><strong>Fecha y Hora:</strong> ${c.fecha_hora}</p>
                <p><strong>GPS:</strong> Latitud ${c.latitud}, Longitud ${c.longitud}</p>
                <p><strong>Estado:</strong> ${c.estado === 'exitoso' ? '<span class="text-emerald-600 font-bold">Sin novedades</span>' : '<span class="text-red-600 font-bold">Con novedades</span>'}</p>
            </div>
            <div>
                <h4 class="font-extrabold text-on-surface mb-2 uppercase tracking-wide text-[10px]">Observaciones del Inspector:</h4>
                <div class="p-3 rounded-xl border border-slate-200 bg-white font-serif max-h-40 overflow-y-auto italic">
                    ${c.observaciones ? c.observaciones : 'Ninguna observación registrada.'}
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                ${c.foto_path ? `
                <div class="text-center">
                    <span class="block text-[9px] font-bold text-outline-variant uppercase mb-1">Evidencia Foto</span>
                    <img src="${resolverRuta(c.foto_path)}" class="w-full aspect-[4/3] object-cover rounded-xl border border-slate-200" />
                </div>
                ` : ''}
                ${c.firma_path ? `
                <div class="text-center">
                    <span class="block text-[9px] font-bold text-outline-variant uppercase mb-1">Firma Inspector</span>
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-2 h-full flex items-center justify-center">
                        <img src="${resolverRuta(c.firma_path)}" class="max-h-24 max-w-full object-contain" />
                    </div>
                </div>
                ` : ''}
            </div>
        </div>
    `;

    Swal.fire({
        title: 'Detalles del Check-in',
        html: htmlContent,
        width: '600px',
        confirmButtonColor: '#0284c7',
        confirmButtonText: 'Cerrar'
    });
};

// SheetJS Excel Exporter
const exportToExcel = () => {
    const dataToExport = filteredCheckins.value.map(c => ({
        'Inspector': c.inspector_nombre,
        'Código Inspector': c.inspector_codigo,
        'Área Inspector': c.inspector_area,
        'Establecimiento': c.establecimiento || 'N/A',
        'Dirección': c.direccion || 'N/A',
        'Tipo Inspección': c.tipo_inspeccion || 'N/A',
        'Fecha y Hora Check-in': c.fecha_hora,
        'Latitud': c.latitud,
        'Longitud': c.longitud,
        'Estado': c.estado === 'exitoso' ? 'Exitoso' : 'Con Novedades',
        'Observaciones': c.observaciones || ''
    }));

    const worksheet = XLSX.utils.json_to_sheet(dataToExport);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Registros Check-in');
    
    // Set columns widths automatically
    const max_width = dataToExport.reduce((w, r) => Math.max(w, r.Inspector.length), 10);
    worksheet['!cols'] = [ { wch: max_width + 4 } ];

    XLSX.writeFile(workbook, 'Reporte_Checkins_SIGIE.xlsx');
};

onMounted(() => {
    fetchHistorial();
});
</script>
