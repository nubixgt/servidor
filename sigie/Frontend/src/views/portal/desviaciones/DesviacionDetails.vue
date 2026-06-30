<template>
    <div class="max-w-5xl mx-auto">
        <div v-if="loading" class="py-20 text-center">
            <span class="material-symbols-outlined text-4xl animate-spin text-white">sync</span>
            <p class="text-xs font-bold text-slate-400 mt-2">Cargando detalles de la desviación...</p>
        </div>

        <div v-else-if="!desviacion" class="py-20 text-center glass-card border border-white/10 rounded-md shadow-lg">
            <span class="material-symbols-outlined text-5xl text-red-500">warning</span>
            <p class="text-sm font-semibold text-white mt-4">No se pudo cargar la desviación</p>
            <p class="text-xs text-slate-400 mt-1">El registro solicitado no existe o no tiene permisos de acceso.</p>
            <router-link to="/desviaciones" class="mt-6 inline-flex items-center gap-2 text-xs font-bold text-white hover:underline">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Volver
            </router-link>
        </div>

        <div v-else class="space-y-8">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-white/10 pb-6">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] font-extrabold uppercase bg-red-100 text-red-700 px-2.5 py-0.5 rounded-full border border-red-200">
                            Desviación de Lab
                        </span>
                        <span 
                            :class="['px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase border', 
                                     desviacion.estado_seguimiento === 'Abierto' ? 'bg-red-50 border-red-200 text-red-700' :
                                     desviacion.estado_seguimiento === 'En proceso' ? 'bg-amber-50 border-amber-200 text-amber-700' :
                                     'bg-emerald-50 border-emerald-200 text-emerald-700']"
                        >
                            {{ desviacion.estado_seguimiento }}
                        </span>
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-white font-headline mt-2">Muestra {{ desviacion.codigo_muestra }}</h1>
                    <p class="text-xs text-slate-400 mt-1">Registrado el {{ formatDateFull(desviacion.fecha_creacion) }}</p>
                </div>
                <router-link to="/desviaciones" class="flex items-center gap-2 text-xs font-bold text-blue-600 hover:text-blue-700 transition-colors self-start">
                    <span class="material-symbols-outlined text-sm">arrow_back</span> Volver al Historial
                </router-link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Details (General information) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- 1. Ficha del Análisis -->
                    <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium">
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-4 border-b pb-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-white text-sm">science</span>
                            Detalles del Análisis y Hallazgos
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                            <div class="p-3 bg-black/20 rounded border border-white/10">
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Establecimiento</span>
                                <span class="font-bold text-white text-sm">{{ desviacion.establecimiento }}</span>
                            </div>
                            <div class="p-3 bg-black/20 rounded border border-white/10">
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Fecha de Resultado</span>
                                <span class="font-mono font-bold text-white text-sm">{{ desviacion.fecha_resultado }}</span>
                            </div>
                            <div class="p-3 bg-black/20 rounded border border-white/10">
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Tipo de Análisis</span>
                                <span class="font-bold text-white text-sm">{{ desviacion.tipo_analisis }}</span>
                            </div>
                            <div class="p-3 bg-black/20 rounded border border-white/10">
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Parámetro Fuera de Norma</span>
                                <span class="font-bold text-red-600 text-sm">{{ desviacion.parametro_fuera_norma }}</span>
                            </div>
                        </div>

                        <!-- Text areas -->
                        <div class="mt-6 space-y-4 text-xs">
                            <div>
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Resultado Obtenido</span>
                                <div class="p-4 bg-red-50/30 border border-red-100 rounded font-mono text-white whitespace-pre-line leading-relaxed">
                                    {{ desviacion.resultado_obtenido }}
                                </div>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Acción Tomada</span>
                                <div class="p-4 bg-emerald-50/20 border border-emerald-100 rounded text-white whitespace-pre-line leading-relaxed">
                                    {{ desviacion.accion_tomada }}
                                </div>
                            </div>
                            <div v-if="desviacion.observaciones">
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Observaciones</span>
                                <div class="p-4 bg-black/20 border border-white/10 rounded italic text-slate-400 whitespace-pre-line">
                                    {{ desviacion.observaciones }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Inspector responsable -->
                    <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium">
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-4 border-b pb-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-white text-sm">badge</span>
                            Inspector Responsable
                        </h3>
                        <div class="flex items-center gap-4 text-xs">
                            <div class="w-10 h-10 rounded-full bg-[#0a192f]/10 text-white flex items-center justify-center font-bold text-base uppercase">
                                {{ desviacion.inspector_nombre?.substring(0, 2) }}
                            </div>
                            <div>
                                <p class="font-bold text-white text-sm">{{ desviacion.inspector_nombre }}</p>
                                <p class="text-[10px] text-slate-400 font-mono mt-0.5">Código: {{ desviacion.inspector_codigo }} | Área: {{ desviacion.inspector_area }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Details (Attachments & Tracking Action) -->
                <div class="space-y-6">
                    <!-- Adjuntos Bitácora -->
                    <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium">
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-4 border-b pb-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-white text-sm">timeline</span>
                            Bitácora de Documentos ({{ desviacion.documentos?.length || 0 }})
                        </h3>

                        <div v-if="!desviacion.documentos || desviacion.documentos.length === 0" class="py-8 text-center text-xs text-slate-400">
                            <span class="material-symbols-outlined text-3xl text-slate-300">folder_open</span>
                            <p class="mt-2 font-semibold">Sin archivos soporte adjuntos.</p>
                            <p class="text-[9px] text-slate-400 mt-1">Los documentos cargados se mostrarán en orden cronológico.</p>
                        </div>

                        <!-- Vertical Timeline -->
                        <div v-else class="relative pl-6 space-y-6 before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[2px] before:bg-slate-200 max-h-[350px] overflow-y-auto pr-1">
                            <div 
                                v-for="doc in desviacion.documentos" 
                                :key="doc.id" 
                                class="relative group"
                            >
                                <!-- Timeline indicator -->
                                <div class="absolute -left-[20px] top-1.5 w-3.5 h-3.5 glass-card border-2 border-primary rounded-full group-hover:scale-110 transition-transform"></div>

                                <div 
                                    class="p-3 bg-black/20 border border-white/10 hover:border-primary/30 rounded flex items-center gap-3 cursor-pointer transition-colors"
                                    @click="abrirDocumento(doc)"
                                >
                                    <span class="material-symbols-outlined text-2xl text-red-500" v-if="doc.nombre_archivo.toLowerCase().endsWith('.pdf')">picture_as_pdf</span>
                                    <span class="material-symbols-outlined text-2xl text-blue-500" v-else>image</span>
                                    
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-white truncate group-hover:text-white transition-colors text-left">{{ doc.nombre_archivo }}</p>
                                        <p class="text-[9px] text-slate-400 font-mono mt-0.5 text-left">{{ formatDateMini(doc.fecha_subida) }}</p>
                                    </div>
                                    <span class="material-symbols-outlined text-slate-400 group-hover:text-white transition-colors text-sm">open_in_new</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inspector Bitacora Tracking Options (Inspectors only) -->
                    <div 
                        v-if="auth.role === 'inspector'" 
                        class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium space-y-4"
                    >
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider border-b pb-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-white text-sm">edit_note</span>
                            Bitácora y Seguimiento
                        </h3>

                        <!-- Change status -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Actualizar Estado</label>
                            <div class="flex gap-2">
                                <select 
                                    v-model="trackingEstado" 
                                    class="flex-1 bg-black/20 border border-slate-300 rounded px-3 py-2 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                                >
                                    <option value="Abierto">Abierto</option>
                                    <option value="En proceso">En proceso</option>
                                    <option value="Cerrado">Cerrado</option>
                                </select>
                                <button 
                                    @click="updateEstado" 
                                    :disabled="updatingEstado || trackingEstado === desviacion.estado_seguimiento"
                                    class="px-4 py-2 bg-slate-900 text-white font-bold text-xs rounded hover:bg-slate-800 transition-colors flex items-center gap-1 disabled:opacity-50 border border-slate-950"
                                >
                                    <span class="material-symbols-outlined text-xs animate-spin" v-if="updatingEstado">sync</span>
                                    <span>Actualizar</span>
                                </button>
                            </div>
                        </div>

                        <!-- Add files over time -->
                        <div class="pt-4 border-t border-white/10">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Agregar Soporte a la Bitácora</label>
                            
                            <!-- File selector -->
                            <div class="w-full py-6 rounded border border-dashed border-white/10-variant hover:border-primary/50 bg-black/20 hover:bg-[#0a192f]/5 transition-colors flex flex-col items-center justify-center text-center cursor-pointer relative mb-3">
                                <input 
                                    type="file" 
                                    multiple
                                    accept="application/pdf,image/*" 
                                    @change="onTrackingFilesSelected" 
                                    class="absolute inset-0 opacity-0 cursor-pointer"
                                />
                                <span class="material-symbols-outlined text-xl text-slate-400">upload_file</span>
                                <span class="text-[10px] font-bold text-white mt-1">Seleccionar Archivos</span>
                            </div>

                            <!-- Previews list -->
                            <div v-if="trackingFilesPreviews.length > 0" class="space-y-2 mb-4 max-h-40 overflow-y-auto pr-1">
                                <div v-for="(preview, index) in trackingFilesPreviews" :key="index" class="p-2 bg-black/20 border border-white/10 rounded flex items-center justify-between gap-2">
                                    <p class="text-[10px] font-bold text-white truncate flex-1">{{ preview.name }}</p>
                                    <button 
                                        type="button" 
                                        @click="removerTrackingFile(index)"
                                        class="w-5 h-5 rounded-full bg-red-100 text-red-600 flex items-center justify-center"
                                    >
                                        <span class="material-symbols-outlined text-[10px] font-black">close</span>
                                    </button>
                                </div>
                            </div>

                            <button 
                                v-if="trackingFiles.length > 0"
                                @click="uploadAdditionalFiles" 
                                :disabled="uploadingFiles"
                                class="w-full py-2.5 bg-[#0a192f] hover:bg-[#122347] text-white font-bold text-xs rounded border border-slate-800 shadow-lg transition-colors flex items-center justify-center gap-1.5"
                            >
                                <span class="material-symbols-outlined text-sm animate-spin" v-if="uploadingFiles">sync</span>
                                <span class="material-symbols-outlined text-sm" v-else>cloud_upload</span>
                                <span>Subir a Bitácora</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../../../stores/authStore.js';
import api, { getBackendBaseUrl } from '../../../services/api.js';
import Swal from 'sweetalert2';

const route = useRoute();
const auth = useAuthStore();

const desviacion = ref(null);
const loading = ref(true);

// Tracking states
const trackingEstado = ref('');
const updatingEstado = ref(false);
const trackingFiles = ref([]);
const trackingFilesPreviews = ref([]);
const uploadingFiles = ref(false);

const resolverRuta = (rutaRelativa) => {
    return `${getBackendBaseUrl()}/${rutaRelativa}`;
};

const fetchDetalle = async () => {
    loading.value = true;
    try {
        const id = route.params.id;
        const response = await api.get(`/desviaciones/${id}`);
        if (response.data?.status === 'success') {
            desviacion.value = response.data.data;
            trackingEstado.value = desviacion.value.estado_seguimiento;
        }
    } catch (error) {
        console.error('Error al cargar detalle de la desviación', error);
    } finally {
        loading.value = false;
    }
};

const abrirDocumento = (doc) => {
    window.open(resolverRuta(doc.ruta_archivo), '_blank');
};

const onTrackingFilesSelected = (event) => {
    const files = Array.from(event.target.files);
    files.forEach(file => {
        trackingFiles.value.push(file);
        trackingFilesPreviews.value.push({
            name: file.name
        });
    });
};

const removerTrackingFile = (index) => {
    trackingFiles.value.splice(index, 1);
    trackingFilesPreviews.value.splice(index, 1);
};

const updateEstado = async () => {
    updatingEstado.value = true;
    try {
        const id = route.params.id;
        const response = await api.put(`/desviaciones/${id}/estado`, {
            estado_seguimiento: trackingEstado.value
        });
        if (response.data?.status === 'success') {
            desviacion.value.estado_seguimiento = trackingEstado.value;
            Swal.fire({
                icon: 'success',
                title: 'Estado Actualizado',
                text: 'El estado de seguimiento se actualizó con éxito.',
                confirmButtonColor: '#0a192f'
            });
        }
    } catch (error) {
        console.error('Error al actualizar estado', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo actualizar el estado de seguimiento.',
            confirmButtonColor: '#0a192f'
        });
    } finally {
        updatingEstado.value = false;
    }
};

const uploadAdditionalFiles = async () => {
    uploadingFiles.value = true;
    try {
        const id = route.params.id;
        const formData = new FormData();
        trackingFiles.value.forEach(file => {
            formData.append('documentos[]', file);
        });

        const response = await api.post(`/desviaciones/${id}/documentos`, formData);
        if (response.data?.status === 'success') {
            desviacion.value.documentos = response.data.data;
            trackingFiles.value = [];
            trackingFilesPreviews.value = [];
            
            Swal.fire({
                icon: 'success',
                title: 'Bitácora Actualizada',
                text: 'Los nuevos documentos de soporte se subieron con éxito.',
                confirmButtonColor: '#0a192f'
            });
        }
    } catch (error) {
        console.error('Error al subir archivos', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron adjuntar los archivos a la bitácora.',
            confirmButtonColor: '#0a192f'
        });
    } finally {
        uploadingFiles.value = false;
    }
};

// Date Format Helpers
const formatDateFull = (dateTimeStr) => {
    if (!dateTimeStr) return '';
    const date = new Date(dateTimeStr.replace(' ', 'T'));
    return date.toLocaleString('es-ES', { 
        year: 'numeric', month: 'long', day: 'numeric', 
        hour: '2-digit', minute: '2-digit' 
    });
};

const formatDateMini = (dateTimeStr) => {
    if (!dateTimeStr) return '';
    const date = new Date(dateTimeStr.replace(' ', 'T'));
    return date.toLocaleString('es-ES', { 
        month: 'short', day: 'numeric', 
        hour: '2-digit', minute: '2-digit' 
    });
};

onMounted(() => {
    fetchDetalle();
});
</script>
