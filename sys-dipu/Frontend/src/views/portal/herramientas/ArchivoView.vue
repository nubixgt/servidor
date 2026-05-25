<template>
    <div class="space-y-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:justify-between md:items-end gap-4 mb-8">
            <div>
                <nav class="flex text-[10px] font-bold uppercase tracking-widest text-outline mb-2 gap-2 items-center">
                    <span>Ecosistema</span>
                    <span class="material-symbols-outlined text-[10px]">chevron_right</span>
                    <span class="text-primary">Archivo Central</span>
                </nav>
                <h1 class="text-4xl font-extrabold text-on-surface tracking-tight font-headline">Repositorio Documental</h1>
                <p class="text-on-surface-variant mt-2 max-w-2xl text-sm">
                    Acceso al acervo histórico, expedientes cerrados y resoluciones pasadas. Todos los roles tienen acceso de lectura y escritura.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button @click="openModal()" class="px-4 py-2 bg-gradient-to-br from-primary to-primary-dim text-on-primary rounded-lg font-bold text-xs flex items-center gap-2 hover:scale-[1.02] active:scale-95 transition-all shadow-md">
                    <span class="material-symbols-outlined text-sm">add_circle</span> Nuevo Expediente
                </button>
                <button @click="exportarExcel()" class="px-4 py-2 bg-surface-container-high text-on-surface rounded-lg font-bold text-xs flex items-center gap-2 hover:bg-surface-container-highest transition-colors">
                    <span class="material-symbols-outlined text-sm">download</span> Exportar Índice
                </button>
                <button @click="exportarPdf()" class="px-4 py-2 bg-surface-container-high text-on-surface rounded-lg font-bold text-xs flex items-center gap-2 hover:bg-surface-container-highest transition-colors">
                    <span class="material-symbols-outlined text-sm">picture_as_pdf</span> Imprimir PDF
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-[0_8px_30px_rgba(43,52,55,0.04)] border border-outline-variant/10">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-10 h-10 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center">
                        <span class="material-symbols-outlined">folder_open</span>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Total Coincidentes</span>
                </div>
                <h3 class="text-3xl font-black text-on-surface font-headline">{{ totalExpedientes }}</h3>
                <p class="text-xs text-outline mt-1">Expedientes en esta consulta</p>
            </div>
            <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-[0_8px_30px_rgba(43,52,55,0.04)] border border-outline-variant/10">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-10 h-10 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center">
                        <span class="material-symbols-outlined">attachment</span>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Con Adjunto</span>
                </div>
                <h3 class="text-3xl font-black text-on-surface font-headline">{{ expedientesConArchivo }}</h3>
                <p class="text-xs text-outline mt-1">Documentos físicos digitalizados</p>
            </div>
            <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-[0_8px_30px_rgba(43,52,55,0.04)] border border-outline-variant/10">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-10 h-10 rounded-full bg-tertiary-container text-on-tertiary-container flex items-center justify-center">
                        <span class="material-symbols-outlined">calendar_month</span>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Año Reciente</span>
                </div>
                <h3 class="text-3xl font-black text-on-surface font-headline">{{ añoMasReciente }}</h3>
                <p class="text-xs text-outline mt-1">Último período de archivo</p>
            </div>
        </div>

        <!-- Search & Filter Bar -->
        <div class="bg-surface-container-lowest p-4 rounded-2xl shadow-[0_8px_30px_rgba(43,52,55,0.04)] border border-outline-variant/10 flex flex-col md:flex-row gap-4 items-center">
            <div class="relative flex-1 w-full">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-outline">
                    <span class="material-symbols-outlined">search</span>
                </span>
                <input v-model="searchQuery" type="text" placeholder="Buscar por ID de expediente, título o palabras clave..." class="w-full bg-surface-container-low border-none rounded-xl py-3 pl-12 pr-4 text-sm focus:ring-2 focus:ring-primary/20 transition-all outline-none" />
            </div>
            <div class="flex gap-3 w-full md:w-auto">
                <select v-model="selectedType" class="bg-surface-container-low border-none rounded-xl py-3 px-4 text-sm text-on-surface font-medium focus:ring-2 focus:ring-primary/20 cursor-pointer outline-none">
                    <option value="Todos los Tipos">Todos los Tipos</option>
                    <option value="Ley">Leyes</option>
                    <option value="Decreto">Decretos</option>
                    <option value="Resolución">Resoluciones</option>
                    <option value="Acta">Actas</option>
                    <option value="Iniciativa">Iniciativas</option>
                </select>
                <select v-model="selectedYear" class="bg-surface-container-low border-none rounded-xl py-3 px-4 text-sm text-on-surface font-medium focus:ring-2 focus:ring-primary/20 cursor-pointer outline-none">
                    <option value="Cualquier Año">Cualquier Año</option>
                    <option v-for="yr in availableYears" :key="yr" :value="yr">{{ yr }}</option>
                </select>
                <button @click="limpiarFiltros" class="px-4 py-3 bg-surface-container-high text-on-surface rounded-xl flex items-center justify-center hover:bg-surface-container-highest transition-colors" title="Limpiar Filtros">
                    <span class="material-symbols-outlined text-lg">filter_alt_off</span>
                </button>
            </div>
        </div>

        <!-- Data Table / Loader -->
        <div class="bg-surface-container-lowest rounded-2xl shadow-[0_8px_30px_rgba(43,52,55,0.04)] border border-outline-variant/10 overflow-hidden relative min-h-[250px]">
            
            <!-- Loading Indicator -->
            <div v-if="loading" class="absolute inset-0 bg-surface/50 backdrop-blur-sm z-10 flex flex-col items-center justify-center gap-3">
                <div class="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
                <p class="text-xs font-bold text-primary uppercase tracking-widest">Cargando archivo...</p>
            </div>

            <!-- Empty State -->
            <div v-if="!loading && archiveData.length === 0" class="p-12 text-center flex flex-col items-center justify-center gap-3 text-outline">
                <span class="material-symbols-outlined text-5xl">folder_off</span>
                <p class="text-sm font-bold">No se encontraron expedientes coincidentes</p>
                <p class="text-xs">Prueba ajustando la búsqueda o registra un nuevo expediente.</p>
            </div>

            <div class="overflow-x-auto" v-if="archiveData.length > 0">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="bg-surface-container-low/50 border-b border-outline-variant/20">
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Expediente</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Título / Asunto</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Tipo</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Fecha Archivo</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Estado</th>
                            <th class="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        <tr v-for="item in archiveData" :key="item.id" class="hover:bg-surface-container-low/30 transition-colors group">
                            <td class="p-4">
                                <span class="text-xs font-bold font-mono text-primary bg-primary/5 px-2 py-1 rounded">{{ item.expediente_id }}</span>
                            </td>
                            <td class="p-4">
                                <p class="text-sm font-bold text-on-surface">{{ item.titulo }}</p>
                                <p class="text-[10px] text-outline mt-0.5">Módulo: {{ item.modulo }}</p>
                            </td>
                            <td class="p-4">
                                <span class="text-xs font-medium text-on-surface-variant flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">{{ iconForType(item.tipo) }}</span>
                                    {{ item.tipo }}
                                </span>
                            </td>
                            <td class="p-4 text-xs text-on-surface-variant">{{ formatoFecha(item.fecha) }}</td>
                            <td class="p-4">
                                <span :class="['text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-full', statusClass(item.estado)]">
                                    {{ item.estado }}
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-2 md:opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button v-if="item.file_url" @click="descargarAdjunto(item)" class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-colors" title="Descargar/Ver Adjunto">
                                        <span class="material-symbols-outlined text-sm">download</span>
                                    </button>
                                    <button @click="openModal(item)" class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center text-on-surface-variant hover:bg-primary hover:text-white transition-colors" title="Ver / Editar">
                                        <span class="material-symbols-outlined text-sm">edit</span>
                                    </button>
                                    <button @click="eliminarExpediente(item)" class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center text-error hover:bg-error hover:text-white transition-colors" title="Eliminar Registro">
                                        <span class="material-symbols-outlined text-sm">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL (Agregar/Editar) -->
        <Teleport to="body">
            <Transition name="modal-fade">
                <div v-if="showModal" class="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="closeModal">
                    <div class="bg-surface rounded-2xl w-full max-w-xl shadow-2xl overflow-hidden flex flex-col border border-outline-variant/30">
                        
                        <!-- Modal Header -->
                        <div class="px-6 py-5 bg-surface-container flex items-center justify-between border-b border-outline-variant/20">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-primary text-2xl">inventory_2</span>
                                <div>
                                    <h3 class="text-lg font-bold text-on-surface">{{ editandoItem ? 'Editar Expediente' : 'Nuevo Ingreso al Archivo' }}</h3>
                                    <p class="text-xs text-on-surface-variant">Rellena los campos para archivar el documento oficial</p>
                                </div>
                            </div>
                            <button @click="closeModal" class="p-1 rounded-lg hover:bg-surface-container-high transition-colors text-on-surface-variant">
                                <span class="material-symbols-outlined text-xl">close</span>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[10px] font-black text-on-surface-variant uppercase tracking-[0.12em]">Código Expediente *</label>
                                    <input v-model="form.expediente_id" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20 font-mono text-sm uppercase" placeholder="EXP-2026-0001" />
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[10px] font-black text-on-surface-variant uppercase tracking-[0.12em]">Tipo Documento *</label>
                                    <select v-model="form.tipo" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20">
                                        <option value="Ley">Ley</option>
                                        <option value="Decreto">Decreto</option>
                                        <option value="Resolución">Resolución</option>
                                        <option value="Acta">Acta</option>
                                        <option value="Iniciativa">Iniciativa</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-black text-on-surface-variant uppercase tracking-[0.12em]">Título o Asunto *</label>
                                <input v-model="form.titulo" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" placeholder="Ej. Aprobación del Presupuesto 2026" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[10px] font-black text-on-surface-variant uppercase tracking-[0.12em]">Módulo / Categoría de Origen *</label>
                                    <select v-model="form.modulo" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20">
                                        <option value="Finanzas">Finanzas</option>
                                        <option value="Administración">Administración</option>
                                        <option value="Salud">Salud</option>
                                        <option value="Pleno">Pleno</option>
                                        <option value="Economía">Economía</option>
                                        <option value="Infraestructura">Infraestructura</option>
                                        <option value="Medio Ambiente">Medio Ambiente</option>
                                    </select>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[10px] font-black text-on-surface-variant uppercase tracking-[0.12em]">Fecha Documento *</label>
                                    <input v-model="form.fecha" type="date" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[10px] font-black text-on-surface-variant uppercase tracking-[0.12em]">Estado Archivo *</label>
                                    <select v-model="form.estado" class="px-3 py-2 bg-surface-container-low text-on-surface border border-outline-variant/30 rounded-lg outline-none focus:ring-2 focus:ring-primary/20">
                                        <option value="Aprobado">Aprobado</option>
                                        <option value="Histórico">Histórico</option>
                                        <option value="Abrogado">Abrogado</option>
                                        <option value="Rechazado">Rechazado</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Document Upload -->
                            <div class="flex flex-col gap-1.5 pt-2">
                                <label class="text-[10px] font-black text-on-surface-variant uppercase tracking-[0.12em]">Documento Adjunto (PDF, Word, Excel, Imagen)</label>
                                <div class="flex items-center gap-3">
                                    <input type="file" ref="fileInput" @change="onFileSelected" class="hidden" accept=".pdf,.docx,.doc,.xlsx,.xls,.pptx,.ppt,.txt,.png,.jpg,.jpeg" />
                                    <button @click="$refs.fileInput.click()" type="button" class="px-4 py-2 bg-surface-container text-on-surface border border-outline-variant/30 rounded-lg text-xs font-bold hover:bg-surface-container-high transition-colors flex items-center gap-2">
                                        <span class="material-symbols-outlined text-sm">cloud_upload</span> 
                                        {{ selectedFile ? 'Cambiar Archivo' : 'Seleccionar Archivo' }}
                                    </button>
                                    <span class="text-xs text-on-surface-variant truncate max-w-[250px] font-mono">
                                        {{ selectedFile ? selectedFile.name : 'Ningún archivo seleccionado' }}
                                    </span>
                                </div>
                                <div v-if="editandoItem && editandoItem.file_url" class="mt-2 text-xs text-primary flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                    <span>Ya posee un archivo adjunto.</span>
                                    <a @click.prevent="descargarAdjunto(editandoItem)" href="#" class="underline hover:text-primary-dim font-bold ml-1">Ver Archivo Actual</a>
                                </div>
                                <p class="text-[9px] text-outline mt-1 uppercase">Límite de tamaño: 50MB. Extensiones permitidas: PDF, Word, Excel, PowerPoint, TXT, Imágenes.</p>
                            </div>

                            <p v-if="formError" class="text-error text-xs font-black">{{ formError }}</p>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-4 bg-surface-container flex items-center justify-between border-t border-outline-variant/20">
                            <button v-if="editandoItem" @click="eliminarExpediente(editandoItem)" class="px-4 py-2 bg-error-container text-on-error-container font-semibold rounded-lg hover:bg-error-container/80 transition-colors text-sm flex items-center gap-2">
                                <span class="material-symbols-outlined text-base">delete</span> Eliminar
                            </button>
                            <div class="flex items-center gap-2 ml-auto">
                                <button @click="closeModal" class="px-4 py-2 bg-surface-container-high text-on-surface font-semibold rounded-lg hover:bg-surface-container-highest transition-colors text-sm">Cancelar</button>
                                <button @click="guardarExpediente" class="px-5 py-2 bg-primary text-on-primary font-bold rounded-lg hover:bg-primary/95 transition-colors text-sm flex items-center gap-1.5 shadow-md">
                                    <span class="material-symbols-outlined text-base">{{ editandoItem ? 'save' : 'add' }}</span>
                                    {{ editandoItem ? 'Guardar Cambios' : 'Registrar' }}
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import api, { getApiBaseUrl } from '../../../services/api';
import Swal from 'sweetalert2';

// State
const archiveData = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const selectedType = ref('Todos los Tipos');
const selectedYear = ref('Cualquier Año');

// Modal & Form State
const showModal = ref(false);
const editandoItem = ref(null);
const fileInput = ref(null);
const selectedFile = ref(null);
const formError = ref('');

const emptyForm = () => ({
    expediente_id: '',
    titulo: '',
    tipo: 'Ley',
    fecha: new Date().toISOString().substring(0, 10),
    modulo: 'Finanzas',
    estado: 'Aprobado'
});
const form = ref(emptyForm());

// Loaded years for filters
const availableYears = ref(['2026', '2025', '2024', '2023', '2022', '2021', '2020', '2019']);

// Computed Stats
const totalExpedientes = computed(() => archiveData.value.length);
const expedientesConArchivo = computed(() => archiveData.value.filter(i => i.file_url).length);
const añoMasReciente = computed(() => {
    if (archiveData.value.length === 0) return 'N/A';
    const years = archiveData.value.map(i => new Date(i.fecha).getFullYear());
    return Math.max(...years).toString();
});

// Load records
const cargarArchivoData = async () => {
    loading.value = true;
    try {
        const res = await api.get('/archivo', {
            params: {
                search: searchQuery.value,
                tipo: selectedType.value,
                year: selectedYear.value
            }
        });
        if (res.data && res.data.success) {
            archiveData.value = res.data.data;
        }
    } catch (err) {
        console.error('Error al cargar archivo central:', err);
        Swal.fire('Error', 'No se pudieron cargar los datos del repositorio documental.', 'error');
    } finally {
        loading.value = false;
    }
};

// Watchers for instant filter updates
watch([searchQuery, selectedType, selectedYear], () => {
    cargarArchivoData();
});

onMounted(() => {
    cargarArchivoData();
});

// Helper for dynamic icons
const iconForType = (type) => {
    const icons = { 
        'Ley': 'gavel', 
        'Resolución': 'contract', 
        'Acta': 'history_edu', 
        'Decreto': 'assignment_turned_in', 
        'Iniciativa': 'lightbulb' 
    };
    return icons[type] || 'description';
};

// Helper for status styling classes
const statusClass = (status) => {
    const classes = {
        'Aprobado': 'bg-primary-container text-on-primary-container',
        'Rechazado': 'bg-error-container text-on-error-container',
        'Abrogado': 'bg-surface-container-highest text-on-surface-variant',
    };
    return classes[status] || 'bg-secondary-container text-on-secondary-container';
};

// Date Formatter (e.g. 2022-12-15 -> 15 Dic 2022)
const formatoFecha = (fechaStr) => {
    if (!fechaStr) return '-';
    const partes = fechaStr.split('-');
    if (partes.length === 3) {
        const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        const dia = parseInt(partes[2]);
        const mesIndex = parseInt(partes[1]) - 1;
        const anio = partes[0];
        return `${dia} ${meses[mesIndex]} ${anio}`;
    }
    return fechaStr;
};

// Clear filters
const limpiarFiltros = () => {
    searchQuery.value = '';
    selectedType.value = 'Todos los Tipos';
    selectedYear.value = 'Cualquier Año';
};

// Modal handlers
const openModal = (item = null) => {
    formError.value = '';
    selectedFile.value = null;
    
    if (item) {
        editandoItem.value = item;
        form.value = { ...item };
    } else {
        editandoItem.value = null;
        form.value = emptyForm();
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

// Handle file input
const onFileSelected = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    // Check size limit: 50MB
    if (file.size > 50 * 1024 * 1024) {
        Swal.fire('Archivo demasiado pesado', 'El archivo no debe superar los 50 MB.', 'warning');
        if (fileInput.value) fileInput.value.value = '';
        return;
    }

    selectedFile.value = file;
};

// Save record
const guardarExpediente = async () => {
    formError.value = '';

    // Frontend basic validations
    if (!form.value.expediente_id.trim()) { formError.value = 'El código de expediente es obligatorio.'; return; }
    if (!form.value.titulo.trim()) { formError.value = 'El título/asunto es obligatorio.'; return; }
    if (!form.value.fecha) { formError.value = 'La fecha es obligatoria.'; return; }

    const formData = new FormData();
    formData.append('expediente_id', form.value.expediente_id.toUpperCase());
    formData.append('titulo', form.value.titulo);
    formData.append('tipo', form.value.tipo);
    formData.append('fecha', form.value.fecha);
    formData.append('modulo', form.value.modulo);
    formData.append('estado', form.value.estado);
    
    if (selectedFile.value) {
        formData.append('archivo', selectedFile.value);
    }

    loading.value = true;
    try {
        let res;
        if (editandoItem.value) {
            // Update endpoint using POST route format to allow standard file uploading
            res = await api.post(`/archivo/${editandoItem.value.id}`, formData);
        } else {
            // Create endpoint
            res = await api.post('/archivo', formData);
        }

        if (res.data && res.data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: res.data.message || 'Expediente guardado correctamente.',
                timer: 2000,
                showConfirmButton: false
            });
            closeModal();
            cargarArchivoData();
        } else {
            formError.value = res.data.error || 'No se pudo guardar el expediente.';
        }
    } catch (err) {
        console.error('Error al guardar el expediente:', err);
        if (err.response && err.response.status === 409) {
            formError.value = err.response.data.error || 'El código de expediente ya existe.';
        } else {
            formError.value = 'Error de conexión con el servidor al intentar guardar.';
        }
    } finally {
        loading.value = false;
    }
};

// Delete record
const eliminarExpediente = async (item) => {
    closeModal();
    const result = await Swal.fire({
        title: '¿Confirmar eliminación?',
        text: `Se borrará permanentemente el expediente ${item.expediente_id} y su archivo físico adjunto.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        loading.value = true;
        try {
            const res = await api.delete(`/archivo/${item.id}`);
            if (res.data && res.data.success) {
                Swal.fire('Eliminado', res.data.message || 'Registro eliminado.', 'success');
                cargarArchivoData();
            } else {
                Swal.fire('Error', res.data.error || 'No se pudo eliminar.', 'error');
            }
        } catch (err) {
            console.error('Error al eliminar expediente:', err);
            Swal.fire('Error', 'Error al intentar conectar con el servidor para eliminar.', 'error');
        } finally {
            loading.value = false;
        }
    }
};

// Download / Open attached file
const descargarAdjunto = (item) => {
    if (!item.file_url) {
        Swal.fire('Sin archivo', 'Este expediente no posee ningún documento físico digitalizado.', 'info');
        return;
    }
    // Formulate the absolute address from base API URL
    const url = getApiBaseUrl().replace('/api/v1', '') + item.file_url;
    window.open(url, '_blank');
};

// Export to Excel (CSV with BOM)
const exportarExcel = () => {
    const queryParams = new URLSearchParams({
        search: searchQuery.value,
        tipo: selectedType.value,
        year: selectedYear.value
    }).toString();

    const url = `${getApiBaseUrl()}/archivo/export/excel?${queryParams}`;
    window.open(url, '_blank');
};

// Export to printable PDF HTML
const exportarPdf = () => {
    const queryParams = new URLSearchParams({
        search: searchQuery.value,
        tipo: selectedType.value,
        year: selectedYear.value
    }).toString();

    const url = `${getApiBaseUrl()}/archivo/export/pdf?${queryParams}`;
    window.open(url, '_blank');
};
</script>

<style scoped>
/* Modal simple slide transitions */
.modal-fade-enter-active, .modal-fade-leave-active {
    transition: opacity 0.3s ease;
}
.modal-fade-enter-from, .modal-fade-leave-to {
    opacity: 0;
}
.modal-fade-enter-active > div, .modal-fade-leave-active > div {
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-fade-enter-from > div, .modal-fade-leave-to > div {
    transform: scale(0.9) translateY(20px);
}
</style>
