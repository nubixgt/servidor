<template>
    <div class="h-full flex flex-col bg-surface overflow-hidden">
        <!-- Header Section -->
        <header class="shrink-0 bg-surface-container-high/30 backdrop-blur-xl border-b border-white/5 py-4 px-6 md:px-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="font-display-sm text-display-sm text-on-surface mb-1 drop-shadow-[0_0_15px_rgba(233,193,118,0.3)]">Inversionistas</h1>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">Gestión de socios capitalistas y aportaciones.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="openNewModal" class="bg-primary text-on-primary font-label-lg px-6 py-2.5 rounded-full hover:bg-primary-fixed hover:text-on-primary-fixed transition-all duration-300 shadow-[0_0_20px_rgba(233,193,118,0.3)] hover:shadow-[0_0_25px_rgba(233,193,118,0.5)] flex items-center gap-2 hover:-translate-y-0.5">
                        <span class="material-symbols-outlined text-[20px]">person_add</span>
                        Nuevo Inversionista
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content (Split View) -->
        <div class="flex-1 overflow-hidden flex flex-col lg:flex-row gap-6 p-6 md:px-8">
            <!-- Left Panel -->
            <div class="w-full lg:w-[400px] flex flex-col gap-4 shrink-0 h-[40vh] lg:h-full">
                <!-- Search -->
                <div class="relative focus-within:ring-1 focus-within:ring-primary rounded-xl transition-shadow shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                    <input v-model="searchQuery" class="w-full bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm text-body-sm py-3 pl-10 pr-4 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors placeholder-on-surface-variant" placeholder="Buscar por Nombre..." type="text"/>
                </div>

                <!-- List -->
                <div class="flex-1 bg-surface-container-high/30 backdrop-blur-xl rounded-2xl border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)] overflow-hidden flex flex-col min-h-0">
                    <div class="overflow-y-auto custom-scrollbar flex-1 p-2 space-y-2">
                        <div v-if="filteredInvestors.length === 0" class="text-center text-on-surface-variant p-4">No hay inversionistas registrados</div>
                        
                        <div v-for="inv in filteredInvestors" :key="inv.id" 
                             @click="selectedInvestor = inv"
                             :class="['p-4 rounded-2xl backdrop-blur-xl border cursor-pointer relative overflow-hidden group transition-all', 
                                      selectedInvestor?.id === inv.id ? 'bg-surface-container-high/40 border-primary shadow-[0_0_15px_rgba(233,193,118,0.2)]' : 'bg-transparent border-transparent hover:bg-surface-container-high/30 hover:border-white/5']">
                            
                            <div v-if="selectedInvestor?.id === inv.id" class="absolute left-0 top-0 bottom-0 w-1 bg-primary shadow-[0_0_10px_rgba(233,193,118,0.5)]"></div>

                            <div class="flex justify-between items-start mb-2 pl-2">
                                <div>
                                    <h3 class="font-title-sm text-title-sm text-on-surface group-hover:text-primary transition-colors">{{ inv.nombre }}</h3>
                                    <p class="font-body-sm text-[11px] text-on-surface-variant mt-0.5"><span class="material-symbols-outlined text-[12px] align-middle mr-1">percent</span> {{ inv.porcentaje || 0 }}%</p>
                                </div>
                                <div class="text-right">
                                    <span class="font-label-caps text-xs text-primary font-bold">Q{{ Number(inv.capital).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel -->
            <div class="flex-1 flex flex-col overflow-hidden h-[50vh] lg:h-full">
                <div v-if="selectedInvestor" class="flex-1 overflow-y-auto custom-scrollbar pr-2 space-y-6">
                    <!-- Action Bar -->
                    <div class="flex justify-between items-center bg-surface-container-high/30 backdrop-blur-xl p-4 rounded-2xl border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)] shrink-0">
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full bg-tertiary-container shadow-[0_0_8px_rgba(143,165,214,0.6)]"></span>
                            <span class="font-label-caps text-label-caps text-on-surface tracking-widest uppercase">Detalle del Inversionista</span>
                        </div>
                        <div class="flex gap-3">
                            <button @click="editInvestor" class="bg-surface-container-high/50 border border-white/10 text-on-surface font-body-sm py-2 px-4 rounded-xl hover:border-primary/50 hover:text-primary transition-colors shadow-[0_8px_32px_rgba(0,0,0,0.1)] backdrop-blur-sm flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                Editar
                            </button>
                            <button @click="deleteInvestor" class="bg-surface-container-high/50 border border-error/20 text-error font-body-sm py-2 px-4 rounded-xl hover:bg-error/10 transition-colors shadow-[0_8px_32px_rgba(0,0,0,0.1)] backdrop-blur-sm flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                Eliminar
                            </button>
                        </div>
                    </div>

                    <!-- Details Card -->
                    <div class="bento-card bg-surface-container-high/30 backdrop-blur-xl rounded-2xl p-6 border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
                        <div class="flex items-center gap-6 mb-8 border-b border-white/10 pb-6">
                            <div class="w-16 h-16 rounded-full bg-surface-container-highest overflow-hidden border border-primary/50 shadow-[0_0_15px_rgba(233,193,118,0.2)] flex items-center justify-center">
                                <span class="material-symbols-outlined text-3xl text-primary">account_balance_wallet</span>
                            </div>
                            <div>
                                <h2 class="font-display-md text-3xl text-on-surface mb-2">{{ selectedInvestor.nombre }}</h2>
                                <p class="font-body-md text-on-surface-variant flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[16px]">account_balance</span> 
                                    Capital: <span class="text-primary font-semibold">Q{{ Number(selectedInvestor.capital).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-6">
                            <div>
                                <p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-widest mb-1">Banco</p>
                                <p class="font-body-md text-on-surface">{{ selectedInvestor.banco || 'N/A' }}</p>
                            </div>
                            
                            <div>
                                <p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-widest mb-1">Número de Cuenta</p>
                                <p class="font-body-md text-on-surface">{{ selectedInvestor.numero_cuenta || 'N/A' }}</p>
                            </div>
                            
                            <div>
                                <p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-widest mb-1">% Inversionista</p>
                                <p class="font-body-lg text-primary font-semibold">{{ selectedInvestor.porcentaje || 0 }}%</p>
                            </div>
                        </div>
                    </div>

                    <!-- Documents -->
                    <div v-if="selectedInvestor.documentos && selectedInvestor.documentos.length > 0" class="bento-card bg-surface-container-high/30 backdrop-blur-xl rounded-2xl p-6 border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
                        <h3 class="font-title-md text-title-md text-on-surface flex items-center gap-2 mb-4">
                            <span class="material-symbols-outlined text-primary text-[20px]">folder_open</span>
                            Documentos Adjuntos
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div v-for="(doc, idx) in selectedInvestor.documentos" :key="idx" class="w-full aspect-video rounded-xl border border-white/10 overflow-hidden bg-surface-container-highest relative group">
                                <a :href="`/horus/Backend/${doc}`" target="_blank" class="w-full h-full block">
                                    <img v-if="isImage(doc)" :src="`/horus/Backend/${doc}`" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                    <div v-else class="w-full h-full flex flex-col items-center justify-center text-on-surface-variant">
                                        <span class="material-symbols-outlined text-4xl mb-2">description</span>
                                        <span class="text-xs truncate px-2 w-full text-center">Documento {{ idx + 1 }}</span>
                                    </div>
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                        <span class="material-symbols-outlined text-white text-3xl drop-shadow-lg">visibility</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="flex-1 flex flex-col items-center justify-center border-2 border-dashed border-white/5 rounded-2xl bg-surface-container-lowest/30">
                    <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4 opacity-50">account_balance_wallet</span>
                    <h3 class="font-title-lg text-title-lg text-on-surface-variant">Selecciona un Inversionista</h3>
                    <p class="font-body-md text-on-surface-variant/70 text-center max-w-sm mt-2">Haz clic en un registro para ver su detalle de capital y porcentaje.</p>
                </div>
            </div>
        </div>

        <!-- Modal Formulario -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>
            
            <div class="relative w-full max-w-2xl max-h-[90vh] bg-surface-container border border-white/10 rounded-3xl shadow-[0_24px_48px_rgba(0,0,0,0.5)] flex flex-col overflow-hidden">
                <div class="px-6 py-4 border-b border-white/10 flex justify-between items-center bg-surface-container-high">
                    <h2 class="font-title-lg text-title-lg text-on-surface">{{ isEditing ? 'Editar Inversionista' : 'Registrar Inversionista' }}</h2>
                    <button @click="closeModal" class="text-on-surface-variant hover:text-error transition-colors rounded-full p-1 hover:bg-error/10">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto custom-scrollbar flex-1">
                    <form @submit.prevent="saveInvestor" class="flex flex-col gap-6">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1 md:col-span-2">
                                <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Nombre del Inversionista <span class="text-error">*</span></label>
                                <input v-model="form.nombre" required type="text" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" />
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Capital <span class="text-error">*</span></label>
                                <input v-model="form.capital" @input="formatInputCurrency('capital')" required type="text" placeholder="Q0.00" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" />
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">% para el Inversionista</label>
                                <input v-model="form.porcentaje" @input="formatInputPercentage('porcentaje')" type="text" placeholder="0%" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" />
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Banco</label>
                                <input v-model="form.banco" type="text" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" />
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">No. de Cuenta</label>
                                <input v-model="form.numero_cuenta" type="text" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" />
                            </div>
                        </div>

                        <hr class="border-white/10 my-2" />

                        <div class="flex flex-col gap-1">
                            <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Foto o Documento (Máximo 3 archivos)</label>
                            <input type="file" multiple accept=".png,.jpg,.jpeg,.pdf,.doc,.docx" @change="handleFilesUpload" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors file:mr-4 file:py-1 file:px-4 file:rounded-xl file:border-0 file:bg-primary/20 file:text-primary file:font-semibold hover:file:bg-primary/30 file:cursor-pointer cursor-pointer text-sm" />
                            
                            <div v-if="selectedFiles.length > 0" class="mt-3">
                                <p class="text-xs text-on-surface-variant mb-2">Archivos seleccionados listos para subir:</p>
                                <ul class="space-y-1">
                                    <li v-for="(f, i) in selectedFiles" :key="i" class="flex items-center justify-between text-sm text-primary bg-surface-container-highest px-3 py-2 rounded-lg border border-white/5">
                                        <span class="truncate">{{ f.name }}</span>
                                        <button type="button" @click.prevent="removeFile(i)" class="text-error/80 hover:text-error ml-2 focus:outline-none">
                                            <span class="material-symbols-outlined text-[16px]">close</span>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <div v-else-if="isEditing && form.documentos && form.documentos.length > 0" class="mt-3">
                                <p class="text-xs text-on-surface-variant mb-2">Archivos existentes (si subes nuevos, se eliminarán los viejos):</p>
                                <ul class="list-disc pl-5 text-sm text-primary">
                                    <li v-for="(doc, i) in form.documentos" :key="i">Documento guardado {{ i+1 }}</li>
                                </ul>
                            </div>
                        </div>

                    </form>
                </div>
                
                <div class="px-6 py-4 border-t border-white/10 bg-surface-container-high flex justify-end gap-3">
                    <button @click="closeModal" class="px-4 py-2 font-body-sm text-on-surface-variant hover:text-on-surface transition-colors">Cancelar</button>
                    <button @click="saveInvestor" class="bg-primary text-on-primary font-label-lg px-6 py-2 rounded-full hover:bg-primary-fixed transition-colors shadow-[0_0_15px_rgba(233,193,118,0.3)] disabled:opacity-50 disabled:cursor-not-allowed">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { investorService } from '../../services/investorService';
import Swal from 'sweetalert2';

const investors = ref([]);
const searchQuery = ref('');
const showModal = ref(false);
const isEditing = ref(false);
const selectedInvestor = ref(null);

const filteredInvestors = computed(() => {
    if (!searchQuery.value) return investors.value;
    const query = searchQuery.value.toLowerCase();
    return investors.value.filter(inv => inv.nombre.toLowerCase().includes(query));
});

const form = ref({
    id: null,
    nombre: '',
    capital: '',
    banco: '',
    numero_cuenta: '',
    porcentaje: '',
    documentos: []
});

const selectedFiles = ref([]);

const loadInvestors = async () => {
    try {
        const response = await investorService.getAllInvestors();
        investors.value = response.data.data || [];
    } catch (e) {
        console.error('Error loading investors', e);
    }
};

onMounted(() => {
    loadInvestors();
});

const isImage = (path) => {
    const ext = path.split('.').pop().toLowerCase();
    return ['png', 'jpg', 'jpeg', 'webp', 'gif'].includes(ext);
};

const parseFormatted = (val) => {
    if(!val) return 0;
    return parseFloat(val.toString().replace(/[^0-9.-]+/g,"")) || 0;
};

const formatInputPercentage = (field) => {
    let val = form.value[field]?.toString().replace(/[^0-9]/g, '');
    if (!val) {
        form.value[field] = '';
        return;
    }
    // Limit to 100
    let num = parseInt(val, 10);
    if (num > 100) num = 100;
    
    form.value[field] = num + '%';
};

const formatInputCurrency = (field) => {
    let val = form.value[field]?.toString().replace(/[^0-9]/g, '');
    if (!val) {
        form.value[field] = '';
        return;
    }
    let num = parseInt(val, 10) / 100;
    form.value[field] = 'Q' + num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
};

const handleFilesUpload = (event) => {
    const files = Array.from(event.target.files);
    const availableSlots = 3 - selectedFiles.value.length;
    
    if (files.length > availableSlots) {
        Swal.fire({
            icon: 'warning',
            title: 'Límite de archivos',
            text: 'Solo puedes subir un máximo de 3 archivos por Inversionista.',
            background: '#131313',
            color: '#ffffff',
            confirmButtonColor: '#e9c176',
            customClass: {
                popup: 'border border-white/10 rounded-2xl shadow-[0_0_40px_rgba(233,193,118,0.2)]',
            }
        });
        
        if (availableSlots > 0) {
            selectedFiles.value = [...selectedFiles.value, ...files.slice(0, availableSlots)];
        }
    } else {
        selectedFiles.value = [...selectedFiles.value, ...files];
    }
    
    event.target.value = ''; // clear input so the user can select the same file again if they want
};

const removeFile = (index) => {
    selectedFiles.value.splice(index, 1);
};

const openNewModal = () => {
    isEditing.value = false;
    form.value = {
        id: null,
        nombre: '',
        capital: '',
        banco: '',
        numero_cuenta: '',
        porcentaje: '',
        documentos: []
    };
    selectedFiles.value = [];
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const editInvestor = () => {
    if(!selectedInvestor.value) return;
    isEditing.value = true;
    
    let displayPorcentaje = '';
    if (selectedInvestor.value.porcentaje) {
        displayPorcentaje = selectedInvestor.value.porcentaje + '%';
    }

    form.value = {
        id: selectedInvestor.value.id,
        nombre: selectedInvestor.value.nombre || '',
        capital: selectedInvestor.value.capital ? 'Q' + Number(selectedInvestor.value.capital).toLocaleString('en-US', {minimumFractionDigits: 2}) : '',
        banco: selectedInvestor.value.banco || '',
        numero_cuenta: selectedInvestor.value.numero_cuenta || '',
        porcentaje: displayPorcentaje,
        documentos: selectedInvestor.value.documentos || []
    };
    selectedFiles.value = [];
    showModal.value = true;
};

const deleteInvestor = async () => {
    if(!selectedInvestor.value) return;
    
    const result = await Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción borrará este inversionista y sus archivos asociados.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d32f2f',
        cancelButtonColor: '#303030',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        background: '#131313',
        color: '#ffffff',
        customClass: {
            popup: 'border border-white/10 rounded-2xl shadow-[0_0_40px_rgba(255,0,0,0.2)]',
        }
    });

    if (result.isConfirmed) {
        try {
            await investorService.deleteInvestor(selectedInvestor.value.id);
            selectedInvestor.value = null;
            await loadInvestors();
            Swal.fire({
                icon: 'success',
                title: 'Eliminado',
                text: 'El inversionista fue eliminado.',
                background: '#131313',
                color: '#ffffff',
                confirmButtonColor: '#e9c176',
            });
        } catch (e) {
            console.error(e);
            Swal.fire({
                title: 'Error',
                text: 'No se pudo eliminar el inversionista',
                icon: 'error',
                background: '#131313',
                color: '#ffffff',
                confirmButtonColor: '#e9c176'
            });
        }
    }
};

const saveInvestor = async () => {
    try {
        const payload = new FormData();
        payload.append('nombre', form.value.nombre);
        payload.append('capital', parseFormatted(form.value.capital));
        payload.append('banco', form.value.banco || '');
        payload.append('numero_cuenta', form.value.numero_cuenta || '');
        let cleanPorcentaje = form.value.porcentaje ? form.value.porcentaje.toString().replace(/[^0-9]/g, '') : '';
        payload.append('porcentaje', cleanPorcentaje);
        
        if (selectedFiles.value.length > 0) {
            selectedFiles.value.forEach(file => {
                payload.append('documentos[]', file);
            });
        }

        if (isEditing.value) {
            await investorService.updateInvestor(form.value.id, payload);
        } else {
            await investorService.createInvestor(payload);
        }

        Swal.fire({
            icon: 'success',
            title: '¡Guardado!',
            text: 'El inversionista se procesó correctamente.',
            background: '#131313',
            color: '#ffffff',
            confirmButtonColor: '#e9c176',
            customClass: {
                popup: 'border border-white/10 rounded-2xl shadow-[0_0_40px_rgba(233,193,118,0.2)]',
            }
        });

        closeModal();
        await loadInvestors();
        
        if (selectedInvestor.value) {
            selectedInvestor.value = investors.value.find(i => i.id === selectedInvestor.value.id) || null;
        }
    } catch (e) {
        console.error(e);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Hubo un problema al preparar los datos.',
            background: '#131313',
            color: '#ffffff',
            confirmButtonColor: '#e9c176',
            customClass: {
                popup: 'border border-white/10 rounded-2xl',
            }
        });
    }
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #353535; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #e9c176; }
</style>
