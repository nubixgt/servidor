<template>
    <div class="max-w-5xl mx-auto">
        <div v-if="loading" class="py-20 text-center">
            <span class="material-symbols-outlined text-4xl animate-spin text-primary">sync</span>
            <p class="text-xs font-bold text-on-surface-variant mt-2">Cargando detalles de la no conformidad...</p>
        </div>

        <div v-else-if="!noConformidad" class="py-20 text-center bg-white border border-surface-container rounded-2xl">
            <span class="material-symbols-outlined text-5xl text-red-500">warning</span>
            <p class="text-sm font-semibold text-on-surface mt-4">No se pudo cargar la no conformidad</p>
            <p class="text-xs text-on-surface-variant mt-1">El registro solicitado no existe o no tiene permisos de acceso.</p>
            <router-link to="/noconformidades" class="mt-6 inline-flex items-center gap-2 text-xs font-bold text-primary hover:underline">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Volver
            </router-link>
        </div>

        <div v-else class="space-y-8">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-6">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="text-[10px] font-extrabold uppercase bg-amber-100 text-amber-700 px-2.5 py-0.5 rounded-full border border-amber-200">
                            No Conformidad (Rastros)
                        </span>
                        <span 
                            :class="['px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase border', 
                                     noConformidad.estado_hallazgo === 'Abierto' ? 'bg-red-50 border-red-200 text-red-700' :
                                     noConformidad.estado_hallazgo === 'En proceso' ? 'bg-amber-50 border-amber-200 text-amber-700' :
                                     'bg-emerald-50 border-emerald-200 text-emerald-700']"
                        >
                            {{ noConformidad.estado_hallazgo }}
                        </span>
                    </div>
                    <h1 class="text-3xl font-black tracking-tight text-on-surface mt-2">{{ noConformidad.establecimiento }}</h1>
                    <p class="text-xs text-on-surface-variant mt-1">Registrado el {{ formatDateFull(noConformidad.fecha_creacion) }}</p>
                </div>
                
                <div class="flex items-center gap-3 self-start">
                    <router-link 
                        :to="`/noconformidades/${noConformidad.id}/imprimir`" 
                        target="_blank"
                        class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow flex items-center gap-1.5 transition-all"
                    >
                        <span class="material-symbols-outlined text-sm">print</span>
                        Imprimir Hallazgos
                    </router-link>

                    <router-link to="/noconformidades" class="flex items-center gap-2 text-xs font-bold text-primary hover:text-primary-dim transition-colors">
                        <span class="material-symbols-outlined text-sm">arrow_back</span> Volver al Historial
                    </router-link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Details (General information) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- 1. Ficha de la No Conformidad -->
                    <div class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient">
                        <h3 class="text-xs font-extrabold text-on-surface uppercase tracking-wider mb-4 border-b pb-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-lg">description</span>
                            Detalles de la No Conformidad
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block">Establecimiento / Personal de Rastro</span>
                                <span class="font-bold text-on-surface text-sm">{{ noConformidad.establecimiento }}</span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block">Fecha de Inspección</span>
                                <span class="font-mono font-bold text-on-surface text-sm">{{ noConformidad.fecha_inspeccion }}</span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block">Norma Específica Asociada</span>
                                <span class="font-bold text-on-surface text-sm">{{ noConformidad.norma_especifica || 'Ninguna registrada' }}</span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100" v-if="noConformidad.fecha_cumplimiento">
                                <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block">Fecha de Cumplimiento / Cierre</span>
                                <span class="font-mono font-bold text-emerald-600 text-sm">{{ noConformidad.fecha_cumplimiento }}</span>
                            </div>
                        </div>

                        <!-- Text areas -->
                        <div class="mt-6 space-y-4 text-xs">
                            <div>
                                <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block mb-1">Hallazgos / Desviaciones Detectadas</span>
                                <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl text-on-surface whitespace-pre-line leading-relaxed">
                                    {{ noConformidad.hallazgos_detectados }}
                                </div>
                            </div>
                            <div v-if="noConformidad.verificacion_oficial">
                                <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block mb-1">Verificación del Inspector Oficial</span>
                                <div class="p-4 bg-emerald-50/20 border border-emerald-100 rounded-xl text-on-surface whitespace-pre-line leading-relaxed">
                                    {{ noConformidad.verificacion_oficial }}
                                </div>
                            </div>
                            <div v-if="noConformidad.observaciones">
                                <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block mb-1">Observaciones Generales</span>
                                <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl italic text-on-surface-variant whitespace-pre-line">
                                    {{ noConformidad.observaciones }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Inspector responsable -->
                    <div class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient">
                        <h3 class="text-xs font-extrabold text-on-surface uppercase tracking-wider mb-4 border-b pb-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-lg">badge</span>
                            Inspector Oficial Responsable
                        </h3>
                        <div class="flex items-center gap-4 text-xs">
                            <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-base uppercase">
                                {{ noConformidad.inspector_nombre?.substring(0, 2) }}
                            </div>
                            <div>
                                <p class="font-bold text-on-surface text-sm">{{ noConformidad.inspector_nombre }}</p>
                                <p class="text-[10px] text-on-surface-variant font-mono mt-0.5">Código: {{ noConformidad.inspector_codigo }} | Área: {{ noConformidad.inspector_area }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Details (Attachments & Tracking Action) -->
                <div class="space-y-6">
                    <!-- Adjuntos Bitácora -->
                    <div class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient">
                        <h3 class="text-xs font-extrabold text-on-surface uppercase tracking-wider mb-4 border-b pb-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-lg">attachment</span>
                            Documentos de Soporte ({{ noConformidad.documentos?.length || 0 }})
                        </h3>

                        <div v-if="!noConformidad.documentos || noConformidad.documentos.length === 0" class="py-8 text-center text-xs text-on-surface-variant">
                            <span class="material-symbols-outlined text-3xl text-slate-300">folder_open</span>
                            <p class="mt-2 font-semibold">Sin archivos soporte adjuntos.</p>
                        </div>

                        <div v-else class="space-y-2 overflow-y-auto max-h-[350px] pr-1">
                            <div 
                                v-for="doc in noConformidad.documentos" 
                                :key="doc.id" 
                                class="p-3 bg-slate-50 border border-slate-100 hover:border-primary/30 rounded-xl flex items-center gap-3 cursor-pointer group transition-all"
                                @click="abrirDocumento(doc)"
                            >
                                <span class="material-symbols-outlined text-2xl text-red-500" v-if="doc.nombre_archivo.toLowerCase().endsWith('.pdf')">picture_as_pdf</span>
                                <span class="material-symbols-outlined text-2xl text-blue-500" v-else>image</span>
                                
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-on-surface truncate group-hover:text-primary transition-colors">{{ doc.nombre_archivo }}</p>
                                    <p class="text-[9px] text-on-surface-variant font-mono mt-0.5">{{ formatDateMini(doc.fecha_subida) }}</p>
                                </div>
                                <span class="material-symbols-outlined text-slate-400 group-hover:text-primary transition-colors text-sm">open_in_new</span>
                            </div>
                        </div>
                    </div>

                    <!-- Inspector Bitacora Tracking Options (Inspectors only) -->
                    <div 
                        v-if="auth.role === 'inspector'" 
                        class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient space-y-4"
                    >
                        <h3 class="text-xs font-extrabold text-on-surface uppercase tracking-wider border-b pb-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-lg">edit_note</span>
                            Bitácora y Seguimiento
                        </h3>

                        <!-- Change status & verification -->
                        <div class="space-y-3">
                            <div>
                                <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Estado del Hallazgo</label>
                                <select 
                                    v-model="trackingEstado" 
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                                >
                                    <option value="Abierto">Abierto</option>
                                    <option value="En proceso">En proceso</option>
                                    <option value="Cerrado">Cerrado</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Fecha de Cumplimiento / Cierre</label>
                                <input 
                                    v-model="trackingFechaCumplimiento" 
                                    type="date"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                                />
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Verificación del Inspector</label>
                                <textarea 
                                    v-model="trackingVerificacion" 
                                    rows="3"
                                    placeholder="Describe la verificación sanitaria realizada..."
                                    class="w-full bg-slate-50 border-2 border-slate-200 rounded-xl px-3 py-2 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                                ></textarea>
                            </div>

                            <button 
                                @click="updateSeguimiento" 
                                :disabled="updatingSeguimiento"
                                class="w-full py-2.5 bg-slate-900 text-white font-bold text-xs rounded-xl hover:bg-slate-800 transition-all flex items-center justify-center gap-1 disabled:opacity-50"
                            >
                                <span class="material-symbols-outlined text-xs animate-spin" v-if="updatingSeguimiento">sync</span>
                                <span>Guardar Seguimiento</span>
                            </button>
                        </div>

                        <!-- Add files over time -->
                        <div class="pt-4 border-t border-slate-100">
                            <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Agregar Soporte a la Bitácora</label>
                            
                            <!-- File selector -->
                            <div class="w-full py-6 rounded-xl border border-dashed border-outline-variant hover:border-primary/50 bg-slate-50 hover:bg-primary/5 transition-all flex flex-col items-center justify-center text-center cursor-pointer relative mb-3">
                                <input 
                                    type="file" 
                                    multiple
                                    accept="application/pdf,image/*" 
                                    @change="onTrackingFilesSelected" 
                                    class="absolute inset-0 opacity-0 cursor-pointer"
                                />
                                <span class="material-symbols-outlined text-xl text-outline-variant">upload_file</span>
                                <span class="text-[10px] font-bold text-on-surface mt-1">Seleccionar Archivos</span>
                            </div>

                            <!-- Previews list -->
                            <div v-if="trackingFilesPreviews.length > 0" class="space-y-2 mb-4 max-h-40 overflow-y-auto pr-1">
                                <div v-for="(preview, index) in trackingFilesPreviews" :key="index" class="p-2 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-between gap-2">
                                    <p class="text-[10px] font-bold text-on-surface truncate flex-1">{{ preview.name }}</p>
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
                                class="w-full py-2.5 bg-primary hover:bg-primary-dim text-on-primary font-bold text-xs rounded-xl shadow transition-all flex items-center justify-center gap-1.5"
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

const noConformidad = ref(null);
const loading = ref(true);

// Tracking states
const trackingEstado = ref('');
const trackingFechaCumplimiento = ref('');
const trackingVerificacion = ref('');
const updatingSeguimiento = ref(false);

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
        const response = await api.get(`/noconformidades/${id}`);
        if (response.data?.status === 'success') {
            noConformidad.value = response.data.data;
            trackingEstado.value = noConformidad.value.estado_hallazgo;
            trackingFechaCumplimiento.value = noConformidad.value.fecha_cumplimiento || '';
            trackingVerificacion.value = noConformidad.value.verificacion_oficial || '';
        }
    } catch (error) {
        console.error('Error al cargar detalle de la no conformidad', error);
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

const updateSeguimiento = async () => {
    updatingSeguimiento.value = true;
    try {
        const id = route.params.id;
        const response = await api.put(`/noconformidades/${id}/seguimiento`, {
            estado_hallazgo: trackingEstado.value,
            fecha_cumplimiento: trackingFechaCumplimiento.value,
            verificacion_oficial: trackingVerificacion.value
        });
        if (response.data?.status === 'success') {
            noConformidad.value.estado_hallazgo = trackingEstado.value;
            noConformidad.value.fecha_cumplimiento = trackingFechaCumplimiento.value;
            noConformidad.value.verificacion_oficial = trackingVerificacion.value;
            
            Swal.fire({
                icon: 'success',
                title: 'Seguimiento Guardado',
                text: 'El seguimiento de la no conformidad se actualizó con éxito.',
                confirmButtonColor: '#0284c7'
            });
        }
    } catch (error) {
        console.error('Error al actualizar seguimiento', error);
        Swal.fire('Error', 'No se pudo guardar el seguimiento.', 'error');
    } finally {
        updatingSeguimiento.value = false;
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

        const response = await api.post(`/noconformidades/${id}/documentos`, formData);
        if (response.data?.status === 'success') {
            noConformidad.value.documentos = response.data.data;
            trackingFiles.value = [];
            trackingFilesPreviews.value = [];
            
            Swal.fire({
                icon: 'success',
                title: 'Bitácora Actualizada',
                text: 'Los nuevos documentos de soporte se subieron con éxito.',
                confirmButtonColor: '#0284c7'
            });
        }
    } catch (error) {
        console.error('Error al subir archivos', error);
        Swal.fire('Error', 'No se pudieron adjuntar los archivos a la bitácora.', 'error');
    } finally {
        uploadingFiles.value = false;
    }
};

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
