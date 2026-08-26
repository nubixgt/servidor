<template>
    <div class="animate-fade-in w-full pb-10">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-xl font-bold text-brand-dark dark:text-white flex items-center gap-2 mb-1">
                <CloudArrowUpIcon class="w-6 h-6 text-primary" />
                Cargar Documentos PDF
            </h2>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mb-6 uppercase tracking-wider">Sube archivos de votaciones para procesamiento automático</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Upload Area & Info -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Upload Box -->
                <div class="bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-md p-8 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800">
                    <input type="file" ref="fileInput" @change="handleFileChange" multiple accept=".pdf" class="hidden" />
                    <div @click="$refs.fileInput.click()" class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-3xl p-12 flex flex-col items-center justify-center text-center bg-gray-50/50 dark:bg-gray-800/20 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition cursor-pointer mb-6 group">
                        <div class="w-16 h-16 bg-blue-50 dark:bg-blue-900/40 text-blue-500 rounded-full flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-inner">
                            <CloudArrowUpIcon v-if="!uploading" class="w-8 h-8" />
                            <div v-else class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-2">
                            {{ selectedFiles.length > 0 ? `${selectedFiles.length} archivos seleccionados` : 'Arrastra archivos PDF aquí' }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-6">
                            {{ selectedFiles.length > 0 ? 'Haz clic para cambiar la selección' : 'o haz clic para seleccionar archivos' }}
                        </p>
                        
                        <div class="flex gap-4">
                            <span class="inline-flex items-center gap-1.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 px-3 py-1.5 rounded-lg text-[10px] font-bold">
                                <DocumentTextIcon class="w-3.5 h-3.5" /> Solo PDF
                            </span>
                            <span class="inline-flex items-center gap-1.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 px-3 py-1.5 rounded-lg text-[10px] font-bold">
                                <DocumentDuplicateIcon class="w-3.5 h-3.5" /> Múltiples archivos
                            </span>
                        </div>
                    </div>

                    <div v-if="selectedFiles.length > 0" class="flex items-center gap-4">
                        <button @click="processFiles" :disabled="uploading" class="flex-1 bg-indigo-500 hover:bg-indigo-600 disabled:bg-gray-400 text-white font-bold py-3.5 rounded-2xl transition shadow-md flex items-center justify-center gap-2">
                            <ArrowUpTrayIcon v-if="!uploading" class="w-5 h-5" />
                            <span v-if="!uploading">Procesar {{ selectedFiles.length }} Archivos</span>
                            <span v-else>Procesando...</span>
                        </button>
                        <button @click="selectedFiles = []" :disabled="uploading" class="px-6 py-3.5 rounded-2xl border border-gray-200 text-gray-500 hover:bg-gray-50 transition">
                            Cancelar
                        </button>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-md p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2 mb-4">
                        <InformationCircleIcon class="w-5 h-5 text-gray-400" /> Información Importante
                    </h3>
                    <ul class="space-y-3 text-xs text-gray-600 dark:text-gray-400 pl-2">
                        <li class="flex items-center gap-3">
                            <div class="w-1.5 h-1.5 rounded-full bg-indigo-500"></div>
                            El sistema procesará todos los archivos automáticamente
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="w-1.5 h-1.5 rounded-full bg-indigo-500"></div>
                            Los eventos duplicados se actualizarán
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="w-1.5 h-1.5 rounded-full bg-indigo-500"></div>
                            Verás un resumen detallado al finalizar
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Right Column: Processed List -->
            <div class="bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-md rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden flex flex-col h-full min-h-[400px]">
                <div class="p-6 border-b border-gray-50 dark:border-gray-800">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                        <ClockIcon class="w-5 h-5 text-gray-400" /> Procesados Recientemente
                    </h3>
                </div>
                
                <div class="flex-1 overflow-y-auto custom-scrollbar p-2">
                    <div v-if="processedHistory.length === 0" class="flex flex-col items-center justify-center h-full text-gray-400 italic text-xs py-10">
                        No hay archivos procesados aún
                    </div>
                    <div v-else class="space-y-1">
                        <div v-for="item in processedHistory" :key="item.id" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 rounded-2xl transition border-l-[3px] border-indigo-500 bg-gray-50/50 dark:bg-gray-800/30 mb-1">
                            <div class="flex justify-between items-start mb-1">
                                <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200 flex items-center gap-1.5">
                                    <DocumentTextIcon class="w-3.5 h-3.5 text-red-400" />
                                    Evento #{{ item.evento }}
                                </h4>
                                <span :class="['text-[9px] font-black px-2 py-0.5 rounded text-white shadow-sm uppercase tracking-wider', item.resultado === 'RECHAZADO' ? 'bg-red-500' : 'bg-emerald-600']">
                                    {{ item.resultado }}
                                </span>
                            </div>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase leading-snug mb-2 line-clamp-1">{{ item.titulo }}</p>
                            <div class="flex justify-between items-center text-[9px] text-gray-400 font-mono">
                                <span class="flex items-center gap-1"><CalendarDaysIcon class="w-3 h-3"/> {{ item.fecha }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { 
    CloudArrowUpIcon, 
    ArrowUpTrayIcon,
    DocumentTextIcon,
    DocumentDuplicateIcon,
    ArchiveBoxIcon,
    InformationCircleIcon,
    ClockIcon,
    CalendarDaysIcon
} from '@heroicons/vue/24/outline';
import VotacionesService from '@/services/votaciones/VotacionesService';

const fileInput = ref(null);
const selectedFiles = ref([]);
const uploading = ref(false);
const processedHistory = ref([]);

const handleFileChange = (e) => {
    selectedFiles.value = Array.from(e.target.files);
};

const processFiles = async () => {
    if (selectedFiles.value.length === 0) return;
    
    uploading.value = true;
    const formData = new FormData();
    selectedFiles.value.forEach(file => {
        formData.append('files[]', file);
    });

    try {
        const resp = await VotacionesService.uploadPdf(formData);
        if (resp.status === 'success') {
            // Refresh history
            loadHistory();
            selectedFiles.value = [];
            alert('Archivos procesados correctamente');
        }
    } catch (error) {
        console.error('Error al subir archivos:', error);
        alert('Error al procesar los archivos');
    } finally {
        uploading.value = false;
    }
};

const loadHistory = async () => {
    try {
        const resp = await VotacionesService.getEventos({ limit: 10 });
        if (resp.status === 'success') {
            processedHistory.value = resp.data;
        }
    } catch (error) {
        console.error('Error al cargar historial:', error);
    }
};

onMounted(loadHistory);
</script>
