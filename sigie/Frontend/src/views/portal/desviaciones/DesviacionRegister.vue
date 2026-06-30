<template>
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-white font-headline">Registrar Desviación de Laboratorio</h1>
                <p class="text-xs text-white/60 mt-1">Registra los resultados fuera de norma obtenidos en los análisis de laboratorio.</p>
            </div>
            <router-link to="/desviaciones" class="flex items-center gap-2 text-xs font-bold text-blue-600 hover:text-blue-700 transition-colors">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Volver al Historial
            </router-link>
        </div>

        <form @submit.prevent="handleSubmit" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Area (Form inputs) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- 1. Datos del Análisis -->
                <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-white text-sm">biotech</span>
                        1. Datos del Análisis
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Fecha del Resultado *</label>
                            <input 
                                v-model="fechaResultado" 
                                type="date" 
                                required
                                class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Código/Número de Muestra *</label>
                            <input 
                                v-model="codigoMuestra" 
                                type="text" 
                                required
                                placeholder="Ej: MUE-2026-0043"
                                class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Establecimiento Relacionado *</label>
                            <input 
                                v-model="establecimiento" 
                                type="text" 
                                required
                                placeholder="Nombre del establecimiento o rastro..."
                                class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Tipo de Análisis *</label>
                            <input 
                                v-model="tipoAnalisis" 
                                type="text" 
                                required
                                placeholder="Ej: Microbiológico, Físico-químico..."
                                class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Parámetro Fuera de Norma *</label>
                            <input 
                                v-model="parametroFueraNorma" 
                                type="text" 
                                required
                                placeholder="Ej: E. Coli, Salmonella, Plomo..."
                                class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                            />
                        </div>
                    </div>
                </div>

                <!-- 2. Resultados y Acciones -->
                <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-white text-sm">report_problem</span>
                        2. Hallazgos y Acciones
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Resultado Obtenido *</label>
                            <textarea 
                                v-model="resultadoObtenido" 
                                rows="3"
                                required
                                placeholder="Detalla el resultado obtenido en el reporte de laboratorio..."
                                class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                            ></textarea>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Acción Tomada *</label>
                            <textarea 
                                v-model="accionTomada" 
                                rows="3"
                                required
                                placeholder="Describe las medidas sanitarias o correctivas inmediatas aplicadas..."
                                class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                            ></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Area (Files & Status) -->
            <div class="space-y-6">
                <!-- Estado y Seguimiento -->
                <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-white text-sm">track_changes</span>
                        3. Seguimiento
                    </h3>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Estado Inicial *</label>
                        <select 
                            v-model="estadoSeguimiento" 
                            required
                            class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                        >
                            <option value="Abierto">Abierto</option>
                            <option value="En proceso">En proceso</option>
                            <option value="Cerrado">Cerrado</option>
                        </select>
                    </div>
                </div>

                <!-- Adjuntar Documentación Múltiple -->
                <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-white text-sm">upload_file</span>
                        4. Adjuntos de Soporte
                    </h3>
                    
                    <div class="space-y-4">
                        <!-- Upload Box -->
                        <div class="w-full h-32 rounded border border-dashed border-white/10-variant hover:border-primary/50 bg-black/20 hover:bg-[#0a192f]/5 transition-colors flex flex-col items-center justify-center p-4 text-center cursor-pointer relative">
                            <input 
                                type="file" 
                                multiple
                                accept="application/pdf,image/*" 
                                @change="onDocumentosSelected" 
                                class="absolute inset-0 opacity-0 cursor-pointer"
                            />
                            <span class="material-symbols-outlined text-2xl text-slate-400">add_to_photos</span>
                            <span class="text-xs font-bold text-white mt-2 block">Agregar Documentos</span>
                            <span class="text-[9px] text-slate-400 mt-0.5 block">Soporta PDF o Imágenes (Múltiples)</span>
                        </div>

                        <!-- Previews List -->
                        <div v-if="documentosPreviews.length > 0" class="space-y-2 max-h-60 overflow-y-auto pr-1">
                            <div v-for="(preview, index) in documentosPreviews" :key="index" class="p-3 bg-black/20 border border-white/10 rounded flex items-center gap-3 relative group">
                                <span class="material-symbols-outlined text-2xl text-red-500" v-if="preview.isPdf">picture_as_pdf</span>
                                <div class="w-10 h-10 rounded overflow-hidden flex-shrink-0 border glass-card" v-else>
                                    <img :src="preview.url" class="w-full h-full object-cover" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-white truncate pr-6">{{ preview.name }}</p>
                                    <p class="text-[9px] text-slate-400 font-mono">{{ preview.isPdf ? 'Archivo PDF' : 'Imagen' }}</p>
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
                <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-white text-sm">chat</span>
                        Observaciones
                    </h3>
                    <textarea 
                        v-model="observaciones" 
                        rows="3"
                        placeholder="Escribe aquí observaciones adicionales..."
                        class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white mb-4"
                    ></textarea>

                    <button 
                        type="submit" 
                        :disabled="submitting"
                        class="w-full py-3.5 bg-[#0a192f] hover:bg-[#122347] text-white font-bold text-xs rounded shadow transition-colors flex items-center justify-center gap-2 border border-slate-800 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span class="material-symbols-outlined text-sm animate-spin" v-if="submitting">sync</span>
                        <span class="material-symbols-outlined text-sm" v-else>cloud_upload</span>
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
                confirmButtonColor: '#0a192f'
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
            confirmButtonColor: '#0a192f'
        });
    } finally {
        submitting.value = false;
    }
};
</script>
