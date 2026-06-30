<template>
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-white font-headline">Registrar Supervisión a Establecimiento</h1>
                <p class="text-xs text-white/60 mt-1">Registra hallazgos detectados en la supervisión de un establecimiento.</p>
            </div>
            <router-link to="/supervisiones" class="flex items-center gap-2 text-xs font-bold text-blue-600 hover:text-blue-700 transition-colors">
                <span class="material-symbols-outlined text-sm">arrow_back</span> Volver al Historial
            </router-link>
        </div>

        <form @submit.prevent="handleSubmit" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Area (Form inputs) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- 1. Datos Generales -->
                <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-white text-sm">domain</span>
                        1. Datos Generales
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Fecha de Supervisión *</label>
                            <input 
                                v-model="fechaSupervision" 
                                type="date" 
                                required
                                class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Inspector Responsable</label>
                            <input 
                                :value="auth.user?.nombre || 'Cargando...'"
                                type="text" 
                                readonly
                                disabled
                                class="w-full bg-slate-100 border border-slate-300 rounded-xl px-4 py-2.5 text-xs outline-none text-gray-300 font-semibold cursor-not-allowed"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Establecimiento Supervisado *</label>
                            <input 
                                v-model="establecimiento" 
                                type="text" 
                                required
                                placeholder="Nombre del establecimiento..."
                                class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                            />
                        </div>
                    </div>
                </div>

                <!-- 2. Hallazgos y Normas -->
                <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-white text-sm">gavel</span>
                        2. Hallazgos Detectados y Normativa
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Hallazgos Detectados *</label>
                            <textarea 
                                v-model="hallazgosDetectados" 
                                rows="4"
                                required
                                placeholder="Describe detalladamente los hallazgos o desviaciones encontradas durante la supervisión..."
                                class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                            ></textarea>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Norma Específica Asociada (Opcional)</label>
                            <input 
                                v-model="normaEspecifica" 
                                type="text" 
                                placeholder="Ej: Artículo 12 del Reglamento de Inocuidad, Acuerdo Ministerial 345-2023..."
                                class="w-full bg-black/20 border border-slate-300 rounded-xl px-4 py-2.5 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                            />
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
                            v-model="estadoHallazgo" 
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
                            <span class="text-[9px] text-slate-400 mt-0.5 block">Fotografías, listas de verificación, etc.</span>
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
                        {{ submitting ? 'Registrando...' : 'Registrar Supervisión' }}
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
const fechaSupervision = ref(new Date().toISOString().split('T')[0]);
const establecimiento = ref('');
const hallazgosDetectados = ref('');
const normaEspecifica = ref('');
const estadoHallazgo = ref('Abierto');
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
        formData.append('fecha_supervision', fechaSupervision.value);
        formData.append('establecimiento', establecimiento.value);
        formData.append('hallazgos_detectados', hallazgosDetectados.value);
        formData.append('norma_specifica', normaEspecifica.value); // mapping to backend field
        formData.append('observaciones', observaciones.value);
        formData.append('estado_hallazgo', estadoHallazgo.value);

        // backend expects norma_especifica
        formData.append('norma_especifica', normaEspecifica.value);

        documentosFiles.value.forEach(file => {
            formData.append('documentos[]', file);
        });

        const response = await api.post('/supervisiones', formData);

        if (response.data?.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Registro Exitoso',
                text: 'La supervisión se guardó correctamente.',
                confirmButtonColor: '#0a192f'
            }).then(() => {
                router.push('/supervisiones');
            });
        }
    } catch (error) {
        console.error('Error al registrar supervisión', error);
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
