<template>
    <div class="max-w-5xl mx-auto">
        <div v-if="loading" class="py-20 text-center">
            <span class="material-symbols-outlined text-4xl animate-spin text-primary">sync</span>
            <p class="text-xs font-bold text-on-surface-variant mt-2">Cargando detalles del muestreo...</p>
        </div>

        <div v-else-if="!sampling" class="py-20 text-center bg-white border border-surface-container rounded-md shadow-sm">
            <span class="material-symbols-outlined text-5xl text-red-500">warning</span>
            <p class="text-sm font-semibold text-on-surface mt-4">No se pudo cargar el muestreo</p>
            <p class="text-xs text-on-surface-variant mt-1">El registro solicitado no existe o no tiene permisos de acceso.</p>
            <router-link to="/muestreos" class="mt-6 inline-flex items-center gap-2 text-xs font-bold text-primary hover:underline">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Volver
            </router-link>
        </div>

        <div v-else class="space-y-8 animate-fade-in">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-6">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-[10px] font-extrabold uppercase bg-sky-100 text-sky-700 px-2.5 py-0.5 rounded-full border border-sky-200">
                            Muestreo de Importación
                        </span>
                        <span 
                            :class="['px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase border', 
                                     sampling.estado === 'Sugerido' ? 'bg-amber-50 border-amber-200 text-amber-700' :
                                     sampling.estado === 'Aprobado' ? 'bg-blue-50 border-blue-200 text-blue-700' :
                                     sampling.estado === 'Ejecutado' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' :
                                     'bg-red-50 border-red-200 text-red-700']"
                        >
                            {{ sampling.estado }}
                        </span>
                        <span class="text-[10px] font-bold uppercase bg-slate-100 text-slate-700 px-2.5 py-0.5 rounded-full border border-slate-200">
                            Origen: {{ sampling.origen }}
                        </span>
                    </div>
                    <h1 class="text-3xl font-black tracking-tight text-on-surface mt-2">Muestreo #{{ sampling.id }}</h1>
                    <p class="text-xs text-on-surface-variant mt-1">Registrado el {{ formatDateFull(sampling.fecha_creacion) }}</p>
                </div>
                <router-link to="/muestreos" class="flex items-center gap-2 text-xs font-bold text-primary hover:text-primary-dim transition-colors self-start">
                    <span class="material-symbols-outlined text-sm">arrow_back</span> Volver al Historial
                </router-link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Details (General information) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- 1. Detalle del Muestreo -->
                    <div class="bg-white p-6 rounded-md border border-surface-container shadow-sm">
                        <h3 class="text-xs font-bold text-on-surface uppercase tracking-wider mb-4 border-b pb-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-sm">biotech</span>
                            Ficha del Muestreo
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                            <div class="p-3 bg-slate-50 rounded border border-slate-200 col-span-2">
                                <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block">Empresa Importadora</span>
                                <span class="font-bold text-on-surface text-sm">{{ sampling.importador_nombre }}</span>
                                <span class="font-mono text-[9px] text-slate-400 block mt-0.5">NIT: {{ sampling.importador_nit }}</span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded border border-slate-200">
                                <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block">Tipo de Producto</span>
                                <span class="font-bold text-on-surface text-sm">{{ sampling.tipo_producto }}</span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded border border-slate-200">
                                <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block">Fecha Programada</span>
                                <span class="font-mono font-bold text-on-surface text-sm">{{ sampling.fecha_programada }}</span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded border border-slate-200" v-if="sampling.volumen_kilos">
                                <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block">Volumen Acumulado en Alarma</span>
                                <span class="font-bold text-on-surface text-sm">{{ formatVolume(sampling.volumen_kilos) }} kg</span>
                            </div>
                        </div>

                        <!-- Estado specifics -->
                        <div class="mt-6 space-y-4 text-xs">
                            <div v-if="sampling.estado === 'Rechazado' && sampling.motivo_rechazo">
                                <span class="text-[9px] font-bold text-red-600 uppercase tracking-wider block mb-1">Motivo de Rechazo (Administración)</span>
                                <div class="p-4 bg-red-50 border border-red-200 rounded text-red-800 whitespace-pre-line leading-relaxed italic">
                                    {{ sampling.motivo_rechazo }}
                                </div>
                            </div>
                            <div v-if="sampling.estado === 'Ejecutado' && sampling.observaciones_ejecucion">
                                <span class="text-[9px] font-bold text-emerald-600 uppercase tracking-wider block mb-1">Observaciones de Ejecución (Inspector)</span>
                                <div class="p-4 bg-emerald-50/20 border border-emerald-100 rounded text-slate-700 whitespace-pre-line leading-relaxed">
                                    {{ sampling.observaciones_ejecucion }}
                                </div>
                                <span class="text-[9px] text-slate-400 font-mono mt-1 block">Ejecutado el: {{ formatDateFull(sampling.fecha_ejecucion) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Personal Asignado -->
                    <div class="bg-white p-6 rounded-md border border-surface-container shadow-sm">
                        <h3 class="text-xs font-bold text-on-surface uppercase tracking-wider mb-4 border-b pb-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-sm">badge</span>
                            Inspector Asignado
                        </h3>
                        <div class="flex items-center gap-4 text-xs">
                            <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-base uppercase">
                                {{ sampling.inspector_nombre?.substring(0, 2) }}
                            </div>
                            <div>
                                <p class="font-bold text-on-surface text-sm">{{ sampling.inspector_nombre }}</p>
                                <p class="text-[10px] text-on-surface-variant font-mono mt-0.5">Código: {{ sampling.inspector_codigo }} | Área: {{ sampling.inspector_area }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Chronological Timeline Log -->
                <div class="space-y-6">
                    <!-- Chronological Timeline Bitacora -->
                    <div class="bg-white p-6 rounded-md border border-surface-container shadow-sm flex flex-col">
                        <h3 class="text-xs font-bold text-on-surface uppercase tracking-wider mb-4 border-b pb-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-sm">timeline</span>
                            Bitácora de Documentos
                        </h3>

                        <div v-if="!sampling.documentos || sampling.documentos.length === 0" class="py-12 text-center text-xs text-on-surface-variant">
                            <span class="material-symbols-outlined text-4xl text-slate-300">folder_open</span>
                            <p class="mt-2 font-semibold">Sin archivos soporte adjuntos.</p>
                            <p class="text-[9px] text-slate-400 mt-1">Los documentos de la bitácora se listarán aquí en orden cronológico.</p>
                        </div>

                        <!-- Vertical Timeline -->
                        <div v-else class="relative pl-6 space-y-6 before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[2px] before:bg-slate-200">
                            <div 
                                v-for="doc in sampling.documentos" 
                                :key="doc.id" 
                                class="relative group"
                            >
                                <!-- Timeline indicator -->
                                <div class="absolute -left-[20px] top-1.5 w-3.5 h-3.5 bg-white border-2 border-primary rounded-full group-hover:scale-110 transition-transform"></div>
                                
                                <div 
                                    class="p-3 bg-slate-50 border border-slate-200 hover:border-primary/30 rounded flex items-center gap-3 cursor-pointer transition-colors"
                                    @click="abrirDocumento(doc)"
                                >
                                    <span class="material-symbols-outlined text-2xl text-red-500" v-if="doc.nombre_archivo.toLowerCase().endsWith('.pdf')">picture_as_pdf</span>
                                    <span class="material-symbols-outlined text-2xl text-blue-500" v-else>image</span>
                                    
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[11px] font-bold text-on-surface truncate group-hover:text-primary transition-colors">{{ doc.nombre_archivo }}</p>
                                        <p class="text-[9px] text-on-surface-variant font-mono mt-0.5">{{ formatDateMini(doc.fecha_subida) }}</p>
                                    </div>
                                    <span class="material-symbols-outlined text-slate-400 group-hover:text-primary transition-colors text-xs">open_in_new</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add documents over time (Visible if Approved/Ejecutado and user is the assigned inspector or admin) -->
                    <div 
                        v-if="(auth.role === 'inspector' && parseInt(sampling.inspector_id) === parseInt(auth.inspectorId)) || auth.role === 'administrador'" 
                        class="bg-white p-6 rounded-md border border-surface-container shadow-sm space-y-4"
                    >
                        <h3 class="text-xs font-bold text-on-surface uppercase tracking-wider border-b pb-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-sm">cloud_upload</span>
                            Añadir a la Bitácora
                        </h3>
                        
                        <!-- File selector -->
                        <div class="w-full py-6 rounded border border-dashed border-outline-variant hover:border-primary/50 bg-slate-50 hover:bg-primary/5 transition-colors flex flex-col items-center justify-center text-center cursor-pointer relative mb-3">
                            <input 
                                type="file" 
                                multiple
                                accept="application/pdf,image/*" 
                                @change="onFilesSelected" 
                                class="absolute inset-0 opacity-0 cursor-pointer"
                            />
                            <span class="material-symbols-outlined text-xl text-outline-variant">upload_file</span>
                            <span class="text-[10px] font-bold text-slate-600 mt-1">Seleccionar Archivos</span>
                        </div>

                        <!-- Previews -->
                        <div v-if="trackingFilesPreviews.length > 0" class="space-y-2 max-h-32 overflow-y-auto pr-1">
                            <div v-for="(preview, index) in trackingFilesPreviews" :key="index" class="p-2 bg-slate-50 border border-slate-200 rounded flex items-center justify-between gap-2">
                                <p class="text-[10px] font-bold text-slate-600 truncate flex-1 font-mono">{{ preview.name }}</p>
                                <button 
                                    type="button" 
                                    @click="removerFile(index)"
                                    class="w-5 h-5 rounded-full bg-red-100 text-red-600 flex items-center justify-center"
                                >
                                    <span class="material-symbols-outlined text-[10px] font-black">close</span>
                                </button>
                            </div>
                        </div>

                        <button 
                            v-if="trackingFiles.length > 0"
                            @click="uploadAdditionalFiles" 
                            :disabled="uploading"
                            class="w-full py-2.5 bg-primary hover:bg-primary-dim text-on-primary font-bold text-xs rounded border border-primary-dim shadow-sm transition-colors flex items-center justify-center gap-1.5"
                        >
                            <span class="material-symbols-outlined text-sm animate-spin" v-if="uploading">sync</span>
                            <span class="material-symbols-outlined text-sm" v-else>cloud_upload</span>
                            <span>Subir a Bitácora</span>
                        </button>
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

// States
const sampling = ref(null);
const loading = ref(true);

// Document upload states
const trackingFiles = ref([]);
const trackingFilesPreviews = ref([]);
const uploading = ref(false);

const resolverRuta = (rutaRelativa) => {
    return `${getBackendBaseUrl()}/${rutaRelativa}`;
};

const abrirDocumento = (doc) => {
    window.open(resolverRuta(doc.ruta_archivo), '_blank');
};

const fetchDetalle = async () => {
    loading.value = true;
    try {
        const id = route.params.id;
        const response = await api.get(`/muestreos/${id}`);
        if (response.data?.status === 'success') {
            sampling.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar detalle del muestreo', error);
    } finally {
        loading.value = false;
    }
};

const onFilesSelected = (e) => {
    const files = Array.from(e.target.files);
    files.forEach(file => {
        trackingFiles.value.push(file);
        trackingFilesPreviews.value.push({ name: file.name });
    });
};

const removerFile = (index) => {
    trackingFiles.value.splice(index, 1);
    trackingFilesPreviews.value.splice(index, 1);
};

const uploadAdditionalFiles = async () => {
    uploading.value = true;
    try {
        const id = route.params.id;
        const formData = new FormData();
        trackingFiles.value.forEach(file => {
            formData.append('documentos[]', file);
        });

        const response = await api.post(`/muestreos/${id}/documentos`, formData);
        if (response.data?.status === 'success') {
            sampling.value.documentos = response.data.data;
            trackingFiles.value = [];
            trackingFilesPreviews.value = [];
            
            Swal.fire({
                icon: 'success',
                title: 'Bitácora Actualizada',
                text: 'Los nuevos documentos de soporte se subieron con éxito.',
                confirmButtonColor: '#005a9c'
            });
        }
    } catch (error) {
        console.error('Error al subir archivos a bitácora', error);
        Swal.fire('Error', 'No se pudieron adjuntar los archivos a la bitácora', 'error');
    } finally {
        uploading.value = false;
    }
};

// Int converters for template checking
const int = (val) => parseInt(val);

// Format helpers
const formatVolume = (val) => {
    if (!val) return '0.00';
    return parseFloat(val).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.2s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
