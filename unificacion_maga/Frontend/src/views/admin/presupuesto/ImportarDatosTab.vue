<template>
    <div class="animate-fade-in pb-10">
        
        <div class="flex flex-col lg:flex-row gap-6">
            
            <!-- Left Column: Form & Drag Drop -->
            <div class="flex-1">
                <div class="bg-white dark:bg-[#1E293B] rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden mb-6">
                    <!-- Header -->
                    <div class="bg-brand-dark p-4 text-white">
                        <h3 class="font-bold flex items-center gap-2"><DocumentArrowUpIcon class="w-5 h-5"/> Importar Datos desde Excel</h3>
                    </div>

                    <div class="p-6">
                        <!-- Drag and Drop Zone -->
                        <div 
                            @dragover="handleDragOver"
                            @dragleave="handleDragLeave"
                            @drop="handleDrop"
                            @click="triggerFileInput"
                            :class="[
                                'border-2 border-dashed rounded-3xl p-12 text-center transition-colors cursor-pointer mb-8 relative group',
                                isDragging ? 'border-primary bg-primary/10' : 'border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50'
                            ]">
                            <input 
                                type="file" 
                                ref="fileInput" 
                                class="hidden" 
                                accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                                @change="handleFileChange"
                            />
                            <div class="absolute inset-0 bg-primary/5 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="w-16 h-16 mx-auto bg-blue-50 dark:bg-blue-900/20 text-blue-500 rounded-2xl flex items-center justify-center mb-4 shadow-sm group-hover:scale-105 transition-transform">
                                <CloudArrowUpIcon class="w-8 h-8" />
                            </div>
                            <h4 class="font-black text-brand-dark dark:text-gray-200 text-lg mb-1">
                                {{ isProcessing ? 'Procesando archivo...' : 'Arrastra tu archivo aquí' }}
                            </h4>
                            <p class="text-sm text-gray-500 font-medium mb-2">o haz clic para seleccionar</p>
                            <p class="text-xs text-gray-400 font-medium">Formatos: .xlsx, .csv</p>
                        </div>

                        <!-- Data Type Selector -->
                        <h4 class="text-sm font-bold text-gray-600 dark:text-gray-300 mt-6 mb-3">Tipo de datos a importar</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                            
                            <button @click="dataType = 'UNIDAD_EJECUTORA'" :class="[
                                'border-2 rounded-2xl p-4 flex flex-col items-center justify-center gap-2 shadow-sm transition-all relative overflow-hidden text-center',
                                dataType === 'UNIDAD_EJECUTORA' ? 'bg-blue-50/50 dark:bg-blue-900/10 border-primary/50 text-brand-dark dark:text-white' : 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300 dark:hover:border-gray-600'
                            ]">
                                <div v-if="dataType === 'UNIDAD_EJECUTORA'" class="absolute top-2 right-2 flex w-4 h-4 rounded-full bg-primary items-center justify-center">
                                    <CheckIcon class="w-3 h-3 text-white" />
                                </div>
                                <TableCellsIcon :class="['w-6 h-6', dataType === 'UNIDAD_EJECUTORA' ? 'text-primary' : 'opacity-70']" />
                                <div>
                                    <span class="block font-bold text-sm">Ejecución Principal</span>
                                    <span class="block text-[10px] text-gray-500 mt-1 uppercase tracking-widest">Hoja "UNI EJE"</span>
                                </div>
                            </button>

                             <button @click="dataType = 'GRUPO_GASTO'" :class="[
                                'border-2 rounded-2xl p-4 flex flex-col items-center justify-center gap-2 shadow-sm transition-all relative text-center',
                                dataType === 'GRUPO_GASTO' ? 'bg-blue-50/50 dark:bg-blue-900/10 border-primary/50 text-brand-dark dark:text-white' : 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300 dark:hover:border-gray-600'
                            ]">
                                <div v-if="dataType === 'GRUPO_GASTO'" class="absolute top-2 right-2 flex w-4 h-4 rounded-full bg-primary items-center justify-center">
                                    <CheckIcon class="w-3 h-3 text-white" />
                                </div>
                                <ListBulletIcon :class="['w-6 h-6', dataType === 'GRUPO_GASTO' ? 'text-primary' : 'opacity-70']" />
                                <div>
                                    <span class="block font-bold text-sm">Detalle por Unidad</span>
                                    <span class="block text-[10px] text-gray-500 mt-1 uppercase tracking-widest">Hoja "UniEjeYGru_Gas"</span>
                                </div>
                            </button>

                             <button @click="dataType = 'MINISTERIO'" :class="[
                                'border-2 rounded-2xl p-4 flex flex-col items-center justify-center gap-2 shadow-sm transition-all relative text-center',
                                dataType === 'MINISTERIO' ? 'bg-blue-50/50 dark:bg-blue-900/10 border-primary/50 text-brand-dark dark:text-white' : 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300 dark:hover:border-gray-600'
                            ]">
                                <div v-if="dataType === 'MINISTERIO'" class="absolute top-2 right-2 flex w-4 h-4 rounded-full bg-primary items-center justify-center">
                                    <CheckIcon class="w-3 h-3 text-white" />
                                </div>
                                <BuildingLibraryIcon :class="['w-6 h-6', dataType === 'MINISTERIO' ? 'text-primary' : 'opacity-70']" />
                                <div>
                                    <span class="block font-bold text-sm">Ministerios</span>
                                    <span class="block text-[10px] text-gray-500 mt-1 uppercase tracking-widest">Hoja "MINISTERIOS"</span>
                                </div>
                            </button>
                        </div>

                         <!-- Year Selector -->
                        <h4 class="text-sm font-bold text-gray-600 dark:text-gray-300 mb-3">Año de los datos</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                            <button @click="ejercicio = 2025" :class="[
                                'border-2 rounded-2xl p-4 flex flex-col items-center justify-center gap-2 shadow-sm transition-all relative text-center',
                                ejercicio === 2025 ? 'bg-blue-50/50 dark:bg-blue-900/10 border-primary/50 text-brand-dark dark:text-white' : 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300 dark:hover:border-gray-600'
                            ]">
                                 <div v-if="ejercicio === 2025" class="absolute top-2 right-2 flex w-4 h-4 rounded-full bg-primary items-center justify-center">
                                    <CheckIcon class="w-3 h-3 text-white" />
                                </div>
                                <CalendarDaysIcon :class="['w-6 h-6', ejercicio === 2025 ? 'text-primary' : 'opacity-70']" />
                                <div>
                                    <span class="block font-bold text-sm">Datos 2025</span>
                                    <span class="block text-[10px] text-gray-500 mt-1">Año fiscal 2025</span>
                                </div>
                            </button>

                            <button @click="ejercicio = 2026" :class="[
                                'border-2 rounded-2xl p-4 flex flex-col items-center justify-center gap-2 shadow-sm transition-all relative text-center',
                                ejercicio === 2026 ? 'bg-blue-50/50 dark:bg-blue-900/10 border-primary/50 text-brand-dark dark:text-white' : 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300 dark:hover:border-gray-600'
                            ]">
                                <div v-if="ejercicio === 2026" class="absolute top-2 right-2 flex w-4 h-4 rounded-full bg-primary items-center justify-center">
                                    <CheckIcon class="w-3 h-3 text-white" />
                                </div>
                                <CalendarDaysIcon :class="['w-6 h-6', ejercicio === 2026 ? 'text-primary' : 'opacity-70']" />
                                <div>
                                    <span class="block font-bold text-sm">Datos 2026</span>
                                    <span class="block text-[10px] text-gray-500 mt-1">Año fiscal 2026</span>
                                </div>
                            </button>
                        </div>

                        <!-- Options -->
                        <div class="bg-gray-50/80 dark:bg-gray-800/30 rounded-2xl p-5 border border-gray-100 dark:border-gray-800">
                             <h4 class="text-xs font-bold text-gray-500 uppercase flex items-center gap-2 tracking-widest mb-4">
                                 <Cog6ToothIcon class="w-4 h-4" /> Opciones de Importación
                             </h4>
                             
                             <div class="space-y-4">
                                 <label class="flex items-start gap-3 cursor-pointer group">
                                     <div class="relative flex items-center justify-center mt-0.5">
                                         <input type="checkbox" checked disabled class="peer appearance-none w-5 h-5 border-2 border-gray-300 dark:border-gray-600 rounded-md checked:bg-primary checked:border-primary transition-colors cursor-pointer disabled:opacity-50" />
                                         <CheckIcon class="w-3.5 h-3.5 text-white absolute opacity-0 peer-checked:opacity-100 pointer-events-none transition-opacity" />
                                     </div>
                                     <div class="flex flex-col opacity-70">
                                         <span class="text-sm font-bold text-gray-800 dark:text-gray-200">Actualizar existentes</span>
                                         <span class="text-xs text-gray-500">Siempre activo para evitar redundancia</span>
                                     </div>
                                 </label>
                                 
                                 <label class="flex items-start gap-3 cursor-pointer group">
                                      <div class="relative flex items-center justify-center mt-0.5">
                                         <input type="checkbox" v-model="limpiarAntes" class="peer appearance-none w-5 h-5 border-2 border-gray-300 dark:border-gray-600 rounded-md checked:bg-red-500 checked:border-red-500 transition-colors cursor-pointer" />
                                         <CheckIcon class="w-3.5 h-3.5 text-white absolute opacity-0 peer-checked:opacity-100 pointer-events-none transition-opacity" />
                                     </div>
                                     <div class="flex flex-col">
                                         <span class="text-sm font-bold text-red-500 group-hover:text-red-600 transition-colors">Limpiar antes</span>
                                         <span class="text-xs text-red-400/80 dark:text-red-400/60">Elimina todos los datos anteriores antes de importar los nuevos</span>
                                     </div>
                                 </label>
                             </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Right Column: Sidebar Info -->
            <div class="w-full lg:w-80 flex-shrink-0 flex flex-col gap-6">
                <!-- Como Funciona -->
                <div class="bg-white dark:bg-[#1E293B] rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                    <div class="bg-brand-dark p-4">
                        <h3 class="text-white font-bold text-sm flex items-center gap-2">
                            <QuestionMarkCircleIcon class="w-4 h-4" /> ¿Cómo funciona?
                        </h3>
                    </div>
                    <div class="p-5">
                        <ul class="space-y-4 text-sm">
                            <li class="flex items-start gap-3">
                                <div class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 p-1 rounded mt-0.5 shrink-0"><CheckIcon class="w-3 h-3"/></div>
                                <div>
                                    <strong class="text-gray-800 dark:text-gray-200 block font-bold">No duplica datos</strong>
                                    <span class="text-gray-500 text-xs">Detecta registros existentes para evitar redundancia.</span>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="bg-blue-100 dark:bg-blue-900/30 text-blue-600 p-1 rounded mt-0.5 shrink-0"><ArrowPathIcon class="w-3 h-3"/></div>
                                <div>
                                    <strong class="text-gray-800 dark:text-gray-200 block font-bold">Actualiza valores</strong>
                                    <span class="text-gray-500 text-xs">Si el registro existe, actualiza los montos vigentes y devengados.</span>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="bg-amber-100 dark:bg-amber-900/30 text-amber-600 p-1 rounded mt-0.5 shrink-0"><PencilSquareIcon class="w-3 h-3"/></div>
                                <div>
                                    <strong class="text-gray-800 dark:text-gray-200 block font-bold">Registra en bitácora</strong>
                                    <span class="text-gray-500 text-xs">Guarda historial de quién modificó o insertó datos.</span>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="bg-purple-100 dark:bg-purple-900/30 text-purple-600 p-1 rounded mt-0.5 shrink-0"><ChartBarSquareIcon class="w-3 h-3"/></div>
                                <div>
                                    <strong class="text-gray-800 dark:text-gray-200 block font-bold">Meta al día</strong>
                                    <span class="text-gray-500 text-xs">Se actualiza automáticamente el porcentaje global.</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Hojas Detectadas Info -->
                <div class="bg-white dark:bg-[#1E293B] rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                    <div class="bg-brand-dark p-4">
                        <h3 class="text-white font-bold text-sm flex items-center gap-2">
                            <TableCellsIcon class="w-4 h-4" /> Hojas requeridas
                        </h3>
                    </div>
                    <div class="p-5 font-mono text-xs">
                        <div class="mb-4">
                             <div class="font-bold text-gray-800 dark:text-gray-200 mb-1 font-sans">UNI EJE:</div>
                             <p class="text-gray-500">Unidad Ejecutora, Asignado, Modificado, Vigente, Devengado</p>
                        </div>
                        <div class="mb-4 border-t border-gray-100 dark:border-gray-800 pt-4">
                             <div class="font-bold text-gray-800 dark:text-gray-200 mb-1 font-sans">UniEjeYGru_Gas:</div>
                             <p class="text-gray-500">Unidad Ejecutora, Grupo de gasto, Fuente de financiamiento, Tipo Ejecucion</p>
                        </div>
                        <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
                             <div class="font-bold text-gray-800 dark:text-gray-200 mb-1 font-sans">MINISTERIOS:</div>
                             <p class="text-gray-500">1 (nombre), Asignado, Modificado, Vigente, Devengado</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { 
    CloudArrowUpIcon,
    TableCellsIcon,
    ListBulletIcon,
    BuildingLibraryIcon,
    CalendarDaysIcon,
    CheckIcon,
    Cog6ToothIcon,
    QuestionMarkCircleIcon,
    ArrowPathIcon,
    PencilSquareIcon,
    ChartBarSquareIcon,
    DocumentArrowUpIcon
} from '@heroicons/vue/24/outline';
import * as XLSX from 'xlsx';
import api from '@/services/api';
import Swal from 'sweetalert2';

// State
const isDragging = ref(false);
const isProcessing = ref(false);
const isUploading = ref(false);

const dataType = ref('UNIDAD_EJECUTORA'); // UNIDAD_EJECUTORA, GRUPO_GASTO, MINISTERIO
const ejercicio = ref(2026);
const limpiarAntes = ref(true);

const fileInput = ref(null);
const selectedFile = ref(null);

const triggerFileInput = () => {
    fileInput.value?.click();
};

const handleDragOver = (e) => {
    e.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = (e) => {
    e.preventDefault();
    isDragging.value = false;
};

const handleDrop = (e) => {
    e.preventDefault();
    isDragging.value = false;
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        processFile(files[0]);
    }
};

const handleFileChange = (e) => {
    const files = e.target.files;
    if (files.length > 0) {
        processFile(files[0]);
    }
};

const getExpectedSheet = () => {
    if (dataType.value === 'UNIDAD_EJECUTORA') return 'UNI EJE';
    if (dataType.value === 'GRUPO_GASTO') return 'UniEjeYGru_Gas';
    if (dataType.value === 'MINISTERIO') return 'MINISTERIOS';
    return '';
};

const processFile = async (file) => {
    if (!file.name.endsWith('.xlsx') && !file.name.endsWith('.csv')) {
        Swal.fire('Error', 'Solo se permiten archivos .xlsx o .csv', 'error');
        return;
    }
    
    selectedFile.value = file;
    isProcessing.value = true;
    
    try {
        const reader = new FileReader();
        reader.onload = async (e) => {
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                
                const expectedSheet = getExpectedSheet();
                const sheetName = workbook.SheetNames.find(s => s.trim() === expectedSheet);
                
                if (!sheetName) {
                    Swal.fire('Hoja no encontrada', `El archivo debe contener una hoja llamada "${expectedSheet}" para el tipo de importación seleccionado. Hojas encontradas: ${workbook.SheetNames.join(', ')}`, 'error');
                    isProcessing.value = false;
                    return;
                }
                
                const worksheet = workbook.Sheets[sheetName];
                const jsonData = XLSX.utils.sheet_to_json(worksheet, { defval: null });
                
                if (jsonData.length === 0) {
                    Swal.fire('Archivo vacío', 'La hoja seleccionada no contiene datos.', 'warning');
                    isProcessing.value = false;
                    return;
                }
                
                // Show confirmation before uploading
                const result = await Swal.fire({
                    title: '¿Iniciar importación?',
                    text: `Se detectaron ${jsonData.length} filas en la hoja "${expectedSheet}". Esta acción puede tardar unos segundos.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, importar',
                    cancelButtonText: 'Cancelar'
                });
                
                if (result.isConfirmed) {
                    await uploadData(jsonData);
                }
                
            } catch (err) {
                console.error("Error parseando archivo:", err);
                Swal.fire('Error', 'No se pudo leer el archivo Excel.', 'error');
            } finally {
                isProcessing.value = false;
                // Reset file input so same file can be selected again
                if (fileInput.value) fileInput.value.value = '';
                selectedFile.value = null;
            }
        };
        reader.readAsArrayBuffer(file);
    } catch (err) {
        isProcessing.value = false;
        console.error(err);
    }
};

const uploadData = async (jsonData) => {
    isUploading.value = true;
    
    Swal.fire({
        title: 'Importando datos',
        text: 'Por favor espera...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    try {
        const response = await api.post('/presupuesto/import', {
            tipo: dataType.value,
            ejercicio: ejercicio.value,
            limpiar_antes: limpiarAntes.value,
            datos: jsonData
        });
        
        if (response.data.success) {
            Swal.fire('¡Éxito!', response.data.message || 'Datos importados correctamente', 'success');
        } else {
            Swal.fire('Error', response.data.message || 'Ocurrió un error en la importación', 'error');
        }
    } catch (err) {
        console.error("Upload error:", err);
        Swal.fire('Error de conexión', err.response?.data?.message || 'No se pudo completar la importación', 'error');
    } finally {
        isUploading.value = false;
    }
};

</script>
