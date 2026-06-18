<template>
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-on-surface">Registrar Desviación de Laboratorio</h1>
                <p class="text-sm text-on-surface-variant mt-1">Registra los resultados fuera de norma obtenidos en los análisis de laboratorio.</p>
            </div>
            <router-link to="/desviaciones" class="flex items-center gap-2 text-xs font-bold text-primary hover:text-primary-dim transition-colors">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Volver al Historial
            </router-link>
        </div>

        <form @submit.prevent="handleSubmit" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Area (Form inputs) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- 1. Datos del Análisis -->
                <div class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient">
                    <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wider mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">biotech</span>
                        1. Datos del Análisis
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Fecha del Resultado *</label>
                            <input 
                                v-model="fechaResultado" 
                                type="date" 
                                required
                                class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Código/Número de Muestra *</label>
                            <input 
                                v-model="codigoMuestra" 
                                type="text" 
                                required
                                placeholder="Ej: MUE-2026-0043"
                                class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Establecimiento Relacionado *</label>
                            <input 
                                v-model="establecimiento" 
                                type="text" 
                                required
                                placeholder="Nombre del establecimiento o rastro..."
                                class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Tipo de Análisis *</label>
                            <input 
                                v-model="tipoAnalisis" 
                                type="text" 
                                required
                                placeholder="Ej: Microbiológico, Físico-químico..."
                                class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Parámetro Fuera de Norma *</label>
                            <input 
                                v-model="parametroFueraNorma" 
                                type="text" 
                                required
                                placeholder="Ej: E. Coli, Salmonella, Plomo..."
                                class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                            />
                        </div>
                    </div>
                </div>

                <!-- 2. Resultados y Acciones -->
                <div class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient">
                    <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wider mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">report_problem</span>
                        2. Hallazgos y Acciones
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Resultado Obtenido *</label>
                            <textarea 
                                v-model="resultadoObtenido" 
                                rows="3"
                                required
                                placeholder="Detalla el resultado obtenido en el reporte de laboratorio..."
                                class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                            ></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Acción Tomada *</label>
                            <textarea 
                                v-model="accionTomada" 
                                rows="3"
                                required
                                placeholder="Describe las medidas sanitarias o correctivas inmediatas aplicadas..."
                                class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                            ></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Area (Files & Status) -->
            <div class="space-y-6">
                <!-- Estado y Seguimiento -->
                <div class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient">
                    <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wider mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">track_changes</span>
                        3. Seguimiento
                    </h3>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Estado Inicial *</label>
                        <select 
                            v-model="estadoSeguimiento" 
                            required
                            class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-3 text-sm focus:border-primary focus:bg-white outline-none transition-all text-on-surface"
                        >
                            <option value="Abierto">Abierto</option>
                            <option value="En proceso">En proceso</option>
                            <option value="Cerrado">Cerrado</option>
                        </select>
                    </div>
                </div>

                <!-- Adjuntar Documentación Múltiple -->
                <div class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient">
                    <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wider mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">upload_file</span>
                        4. Adjuntos de Soporte
                    </h3>
                    
                    <div class="space-y-4">
                        <!-- Upload Box -->
                        <div class="w-full h-32 rounded-2xl border-2 border-dashed border-outline-variant hover:border-primary/50 bg-slate-50 hover:bg-primary/5 transition-all flex flex-col items-center justify-center p-4 text-center cursor-pointer relative">
                            <input 
                                type="file" 
                                multiple
                                accept="application/pdf,image/*" 
                                @change="onDocumentosSelected" 
                                class="absolute inset-0 opacity-0 cursor-pointer"
                            />
                            <span class="material-symbols-outlined text-2xl text-outline-variant">add_to_photos</span>
                            <span class="text-xs font-bold text-on-surface mt-2 block">Agregar Documentos</span>
                            <span class="text-[9px] text-on-surface-variant mt-0.5 block">Soporta PDF o Imágenes (Múltiples)</span>
                        </div>

                        <!-- Previews List -->
                        <div v-if="documentosPreviews.length > 0" class="space-y-2 max-h-60 overflow-y-auto pr-1">
                            <div v-for="(preview, index) in documentosPreviews" :key="index" class="p-3 bg-slate-50 border border-slate-100 rounded-xl flex items-center gap-3 relative group">
                                <span class="material-symbols-outlined text-2xl text-red-500" v-if="preview.isPdf">picture_as_pdf</span>
                                <div class="w-10 h-10 rounded overflow-hidden flex-shrink-0 border bg-white" v-else>
                                    <img :src="preview.url" class="w-full h-full object-cover" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-on-surface truncate pr-6">{{ preview.name }}</p>
                                    <p class="text-[9px] text-on-surface-variant font-mono">{{ preview.isPdf ? 'Archivo PDF' : 'Imagen' }}</p>
                                </div>
                                <button 
                                    type="button" 
                                    @click="removerDocumento(index)"
                                    class="w-6 h-6 rounded-full bg-red-100 hover:bg-red-200 text-red-600 flex items-center justify-center transition-all absolute right-2 top-1/2 -translate-y-1/2"
                                >
                                    <span class="material-symbols-outlined text-xs font-black">close</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Observaciones y Enviar -->
                <div class="bg-surface-container-lowest p-6 rounded-2xl border border-surface-container shadow-ambient">
                    <h3 class="text-sm font-extrabold text-on-surface uppercase tracking-wider mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">chat</span>
                        Observaciones
                    </h3>
                    <textarea 
                        v-model="observaciones" 
                        rows="3"
                        placeholder="Escribe aquí observaciones adicionales..."
                        class="w-full bg-slate-50 border-2 border-slate-100 rounded-xl px-4 py-2.5 text-xs focus:border-primary focus:bg-white outline-none transition-all text-on-surface mb-4"
                    ></textarea>

                    <button 
                        type="submit" 
                        :disabled="submitting"
                        class="w-full py-4 bg-gradient-to-br from-primary to-primary-dim text-on-primary font-bold text-sm rounded-xl shadow-lg hover:scale-[1.02] active:scale-98 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <span class="material-symbols-outlined text-lg" v-if="!submitting">cloud_upload</span>
                        <span class="material-symbols-outlined text-lg animate-spin" v-else>sync</span>
                        {{ submitting ? 'Registrando...' : 'Registrar Desviación' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../../stores/authStore.js';
import api from '../../../services/api.js';
import Swal from 'sweetalert2';

const router = useRouter();
const auth = useAuthStore();

// Form Fields
const fechaResultado = ref(new Date().toISOString().split('T')[0]);
const codigoMuestra = ref('');
const establecimiento = ref('');
const tipoAnalisis = ref('');
const parametroFueraNorma = ref('');
const resultadoObtenido = ref('');
const accionTomada = ref('');
const estadoSeguimiento = ref('Abierto');
const observaciones = ref('');

// Files State
const documentosFiles = ref([]);
const documentosPreviews = ref([]);
const submitting = ref(false);

const onDocumentosSelected = (event) => {
    const files = Array.from(event.target.files);
    files.forEach(file => {
        documentosFiles.value.push(file);
        const isPdf = file.type === 'application/pdf';
        documentosPreviews.value.push({
            name: file.name,
            isPdf,
            url: isPdf ? 'pdf_preview' : URL.createObjectURL(file)
        });
    });
};

const removerDocumento = (index) => {
    documentosFiles.value.splice(index, 1);
    documentosPreviews.value.splice(index, 1);
};

const handleSubmit = async () => {
    submitting.value = true;
    try {
        const formData = new FormData();
        formData.append('fecha_resultado', fechaResultado.value);
        formData.append('codigo_muestra', codigoMuestra.value);
        formData.append('establecimiento', establecimiento.value);
        formData.append('tipo_analisis', tipoAnalisis.value);
        formData.append('resultado_obtenido', resultadoObtenido.value);
        formData.append('parametro_fuera_norma', parametroFueraNorma.value);
        formData.append('accion_tomada', accionTomada.value);
        formData.append('estado_seguimiento', estadoSeguimiento.value);
        formData.append('observaciones', observaciones.value);

        documentosFiles.value.forEach(file => {
            formData.append('documentos[]', file);
        });

        const response = await api.post('/desviaciones', formData);

        if (response.data?.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Registro Exitoso',
                text: 'La desviación de laboratorio se guardó correctamente.',
                confirmButtonColor: '#0284c7'
            }).then(() => {
                router.push('/desviaciones');
            });
        }
    } catch (error) {
        console.error('Error al registrar desviación', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de Envío',
            text: error.response?.data?.error || 'No se pudo conectar con el servidor.',
            confirmButtonColor: '#0284c7'
        });
    } finally {
        submitting.value = false;
    }
};
</script>
