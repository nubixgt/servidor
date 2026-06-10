<template>
    <div class="h-full flex flex-col bg-surface overflow-hidden">
        <!-- Header Section -->
        <header class="shrink-0 bg-surface-container-high/30 backdrop-blur-xl border-b border-white/5 py-4 px-6 md:px-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="font-display-sm text-display-sm text-on-surface mb-1 drop-shadow-[0_0_15px_rgba(233,193,118,0.3)]">Directorio de Referidos</h1>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">Gestión y registro de referidos comerciales.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="showModal = true" class="bg-primary text-on-primary font-label-lg px-6 py-2.5 rounded-full hover:bg-primary-fixed hover:text-on-primary-fixed transition-all duration-300 shadow-[0_0_20px_rgba(233,193,118,0.3)] hover:shadow-[0_0_25px_rgba(233,193,118,0.5)] flex items-center gap-2 hover:-translate-y-0.5">
                        <span class="material-symbols-outlined text-[20px]">person_add</span>
                        Nuevo Referido
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content (Split View) -->
        <div class="flex-1 overflow-hidden flex flex-col lg:flex-row gap-6 p-6 md:px-8">
            
            <!-- Left Panel: Referidos List -->
            <div class="w-full lg:w-[400px] flex flex-col gap-4 shrink-0 h-[40vh] lg:h-full">
                <!-- Search -->
                <div class="relative focus-within:ring-1 focus-within:ring-primary rounded-xl transition-shadow shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                    <input v-model="searchQuery" class="w-full bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm text-body-sm py-3 pl-10 pr-4 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors placeholder-on-surface-variant" placeholder="Buscar por Nombre o DPI..." type="text"/>
                </div>

                <!-- List -->
                <div class="flex-1 bg-surface-container-high/30 backdrop-blur-xl rounded-2xl border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)] overflow-hidden flex flex-col min-h-0">
                    <div class="overflow-y-auto custom-scrollbar flex-1 p-2 space-y-2">
                        <div v-if="filteredReferidos.length === 0" class="text-center text-on-surface-variant p-4">No hay referidos encontrados</div>
                        
                        <div v-for="referido in filteredReferidos" :key="referido.id" 
                             @click="selectedReferido = referido"
                             :class="['p-4 rounded-2xl backdrop-blur-xl border cursor-pointer relative overflow-hidden group transition-all', 
                                      selectedReferido?.id === referido.id ? 'bg-surface-container-high/40 border-primary shadow-[0_0_15px_rgba(233,193,118,0.2)]' : 'bg-transparent border-transparent hover:bg-surface-container-high/30 hover:border-white/5']">
                            
                            <div v-if="selectedReferido?.id === referido.id" class="absolute left-0 top-0 bottom-0 w-1 bg-primary shadow-[0_0_10px_rgba(233,193,118,0.5)]"></div>

                            <div class="flex justify-between items-start mb-2 pl-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-surface-container-highest overflow-hidden border border-white/10 flex items-center justify-center">
                                        <img v-if="referido.foto_perfil" :src="`/horus/Backend/${referido.foto_perfil}`" class="w-full h-full object-cover" />
                                        <span v-else class="material-symbols-outlined text-on-surface-variant">person</span>
                                    </div>
                                    <div>
                                        <h3 class="font-title-sm text-title-sm text-on-surface group-hover:text-primary transition-colors">{{ referido.nombre }}</h3>
                                        <p class="font-body-sm text-[11px] text-on-surface-variant mt-0.5"><span class="material-symbols-outlined text-[12px] align-middle mr-1">badge</span> {{ formatDPI(referido.dpi) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Referido Details -->
            <div class="flex-1 flex flex-col overflow-hidden h-[50vh] lg:h-full">
                <div v-if="selectedReferido" class="flex-1 overflow-y-auto custom-scrollbar pr-2 space-y-6">
                    
                    <!-- Action Bar -->
                    <div class="flex justify-between items-center bg-surface-container-high/30 backdrop-blur-xl p-4 rounded-2xl border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)] shrink-0">
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full bg-tertiary-container shadow-[0_0_8px_rgba(143,165,214,0.6)]"></span>
                            <span class="font-label-caps text-label-caps text-on-surface tracking-widest uppercase">Detalle del Referido</span>
                        </div>
                        <div class="flex gap-3">
                            <button @click="editReferido" class="bg-surface-container-high/50 border border-white/10 text-on-surface font-body-sm py-2 px-4 rounded-xl hover:border-primary/50 hover:text-primary transition-colors shadow-[0_8px_32px_rgba(0,0,0,0.1)] backdrop-blur-sm flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                Editar
                            </button>
                            <button @click="deleteReferido" class="bg-surface-container-high/50 border border-error/20 text-error font-body-sm py-2 px-4 rounded-xl hover:bg-error/10 transition-colors shadow-[0_8px_32px_rgba(0,0,0,0.1)] backdrop-blur-sm flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                Eliminar
                            </button>
                        </div>
                    </div>

                    <!-- Details Card -->
                    <div class="bento-card bg-surface-container-high/30 backdrop-blur-xl rounded-2xl p-6 border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
                        <div class="flex items-center gap-6 mb-8 border-b border-white/10 pb-6">
                            <div class="w-24 h-24 rounded-full bg-surface-container-highest overflow-hidden border-2 border-primary/50 shadow-[0_0_20px_rgba(233,193,118,0.2)] flex items-center justify-center">
                                <img v-if="selectedReferido.foto_perfil" :src="`/horus/Backend/${selectedReferido.foto_perfil}`" class="w-full h-full object-cover" />
                                <span v-else class="material-symbols-outlined text-4xl text-on-surface-variant">person</span>
                            </div>
                            <div>
                                <h2 class="font-display-md text-3xl text-on-surface mb-2">{{ selectedReferido.nombre }}</h2>
                                <div class="flex gap-4">
                                    <span class="font-label-caps text-xs px-3 py-1 rounded-full bg-primary/10 text-primary border border-primary/20 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">badge</span> {{ formatDPI(selectedReferido.dpi) }}
                                    </span>
                                    <span class="font-label-caps text-xs px-3 py-1 rounded-full bg-surface-container-highest text-on-surface-variant border border-white/10 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">call</span> {{ formatPhone(selectedReferido.telefono) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                            <div>
                                <p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-widest mb-1">Dirección</p>
                                <p class="font-body-md text-on-surface">{{ selectedReferido.direccion || 'No registrada' }}</p>
                            </div>
                            
                            <div>
                                <p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-widest mb-1">Número de Cuenta</p>
                                <p class="font-body-md text-on-surface">{{ selectedReferido.numero_cuenta || 'No registrada' }}</p>
                            </div>
                            
                            <div>
                                <p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-widest mb-1">Banco</p>
                                <p class="font-body-md text-on-surface">{{ selectedReferido.banco || 'No registrado' }}</p>
                            </div>
                            
                            <div>
                                <p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-widest mb-1">Tipo de Cuenta</p>
                                <span class="inline-block mt-1 font-label-caps text-[11px] px-3 py-1 rounded-md bg-surface-container-high border border-white/10 text-on-surface capitalize">
                                    {{ selectedReferido.tipo_cuenta || 'N/A' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- DPI Images Card -->
                    <div class="bento-card bg-surface-container-high/30 backdrop-blur-xl rounded-2xl p-6 border border-white/5 shadow-[0_8px_32px_rgba(0,0,0,0.2)]">
                        <h3 class="font-title-md text-title-md text-on-surface flex items-center gap-2 mb-6">
                            <span class="material-symbols-outlined text-primary text-[20px]">id_card</span>
                            Documento de Identificación
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Anverso -->
                            <div class="flex flex-col gap-2">
                                <p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-widest text-center">DPI Anverso</p>
                                <div class="w-full aspect-[1.6/1] rounded-xl border border-dashed border-white/20 bg-surface-container-highest/50 flex items-center justify-center overflow-hidden hover:border-primary/50 transition-colors">
                                    <a v-if="selectedReferido.dpi_anverso" :href="`/horus/Backend/${selectedReferido.dpi_anverso}`" target="_blank" class="w-full h-full">
                                        <img :src="`/horus/Backend/${selectedReferido.dpi_anverso}`" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300" />
                                    </a>
                                    <span v-else class="material-symbols-outlined text-4xl text-on-surface-variant/30">image</span>
                                </div>
                            </div>
                            <!-- Reverso -->
                            <div class="flex flex-col gap-2">
                                <p class="font-label-caps text-[10px] text-on-surface-variant uppercase tracking-widest text-center">DPI Reverso</p>
                                <div class="w-full aspect-[1.6/1] rounded-xl border border-dashed border-white/20 bg-surface-container-highest/50 flex items-center justify-center overflow-hidden hover:border-primary/50 transition-colors">
                                    <a v-if="selectedReferido.dpi_reverso" :href="`/horus/Backend/${selectedReferido.dpi_reverso}`" target="_blank" class="w-full h-full">
                                        <img :src="`/horus/Backend/${selectedReferido.dpi_reverso}`" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300" />
                                    </a>
                                    <span v-else class="material-symbols-outlined text-4xl text-on-surface-variant/30">image</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Empty State -->
                <div v-else class="flex-1 flex flex-col items-center justify-center border-2 border-dashed border-white/5 rounded-2xl bg-surface-container-lowest/30">
                    <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4 opacity-50">badge</span>
                    <h3 class="font-title-lg text-title-lg text-on-surface-variant">Selecciona un Referido</h3>
                    <p class="font-body-md text-on-surface-variant/70 text-center max-w-sm mt-2">Haz clic en un referido de la lista para ver sus detalles completos y documentos.</p>
                </div>
            </div>
        </div>

        <!-- Modal Formulario -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>
            
            <div class="relative w-full max-w-4xl max-h-[90vh] bg-surface-container border border-white/10 rounded-3xl shadow-[0_24px_48px_rgba(0,0,0,0.5)] flex flex-col overflow-hidden">
                <div class="px-6 py-4 border-b border-white/10 flex justify-between items-center bg-surface-container-high">
                    <h2 class="font-title-lg text-title-lg text-on-surface">{{ isEditing ? 'Editar Referido' : 'Nuevo Referido' }}</h2>
                    <button @click="closeModal" class="text-on-surface-variant hover:text-error transition-colors rounded-full p-1 hover:bg-error/10">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto custom-scrollbar flex-1">
                    <form @submit.prevent="saveReferido" class="flex flex-col gap-8">
                        
                        <!-- Images Section -->
                        <div class="flex flex-col md:flex-row gap-6 items-start border-b border-white/10 pb-8">
                            <!-- Foto Perfil -->
                            <div class="flex flex-col gap-2 items-center shrink-0">
                                <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest text-center">Foto de Perfil</label>
                                <label class="w-32 h-32 rounded-full border-2 border-dashed border-primary/50 bg-surface-container-highest cursor-pointer flex flex-col items-center justify-center overflow-hidden hover:bg-surface-container-high transition-colors relative group">
                                    <input type="file" accept=".png,.jpg,.jpeg" @change="(e) => handleImageUpload(e, 'foto_perfil')" class="hidden" />
                                    <img v-if="previewFotos.foto_perfil" :src="previewFotos.foto_perfil" class="w-full h-full object-cover" />
                                    <img v-else-if="isEditing && form.foto_perfil" :src="`/horus/Backend/${form.foto_perfil}`" class="w-full h-full object-cover" />
                                    <div v-else class="flex flex-col items-center text-primary">
                                        <span class="material-symbols-outlined text-3xl">add_a_photo</span>
                                        <span class="text-[10px] mt-1 font-medium">Subir Foto</span>
                                    </div>
                                    <div v-if="previewFotos.foto_perfil || form.foto_perfil" class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                        <span class="material-symbols-outlined text-white">edit</span>
                                    </div>
                                </label>
                            </div>

                            <!-- DPI Rectangles -->
                            <div class="flex-1 flex flex-col md:flex-row gap-4 w-full">
                                <div class="flex-1 flex flex-col gap-2">
                                    <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest text-center">DPI Anverso</label>
                                    <label class="w-full aspect-[1.6/1] rounded-xl border-2 border-dashed border-primary/50 bg-surface-container-highest cursor-pointer flex items-center justify-center overflow-hidden hover:bg-surface-container-high transition-colors relative group">
                                        <input type="file" accept=".png,.jpg,.jpeg" @change="(e) => handleImageUpload(e, 'dpi_anverso')" class="hidden" />
                                        <img v-if="previewFotos.dpi_anverso" :src="previewFotos.dpi_anverso" class="w-full h-full object-cover" />
                                        <img v-else-if="isEditing && form.dpi_anverso" :src="`/horus/Backend/${form.dpi_anverso}`" class="w-full h-full object-cover" />
                                        <div v-else class="flex flex-col items-center text-primary">
                                            <span class="material-symbols-outlined text-3xl">id_card</span>
                                            <span class="text-[10px] mt-1 font-medium">Frente</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="flex-1 flex flex-col gap-2">
                                    <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest text-center">DPI Reverso</label>
                                    <label class="w-full aspect-[1.6/1] rounded-xl border-2 border-dashed border-primary/50 bg-surface-container-highest cursor-pointer flex items-center justify-center overflow-hidden hover:bg-surface-container-high transition-colors relative group">
                                        <input type="file" accept=".png,.jpg,.jpeg" @change="(e) => handleImageUpload(e, 'dpi_reverso')" class="hidden" />
                                        <img v-if="previewFotos.dpi_reverso" :src="previewFotos.dpi_reverso" class="w-full h-full object-cover" />
                                        <img v-else-if="isEditing && form.dpi_reverso" :src="`/horus/Backend/${form.dpi_reverso}`" class="w-full h-full object-cover" />
                                        <div v-else class="flex flex-col items-center text-primary">
                                            <span class="material-symbols-outlined text-3xl">id_card</span>
                                            <span class="text-[10px] mt-1 font-medium">Atrás</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Data Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1">
                                <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Nombre Completo <span class="text-error">*</span></label>
                                <input v-model="form.nombre" required type="text" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" />
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">DPI <span class="text-error">*</span></label>
                                <input v-model="form.dpi" @input="handleDpiInput" required placeholder="0000 00000 0000" type="text" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" />
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Teléfono <span class="text-error">*</span></label>
                                <input v-model="form.telefono" @input="handlePhoneInput" required placeholder="0000-0000" type="text" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" />
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Dirección</label>
                                <input v-model="form.direccion" type="text" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" />
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Número de Cuenta</label>
                                <input v-model="form.numero_cuenta" type="text" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" />
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest">Banco</label>
                                <input v-model="form.banco" type="text" class="bg-surface-container-high/30 backdrop-blur-xl text-on-surface font-body-sm py-2 px-3 rounded-xl border border-white/5 focus:border-primary focus:ring-0 transition-colors" />
                            </div>

                            <div class="flex flex-col gap-1 md:col-span-2">
                                <label class="font-label-caps text-on-surface-variant text-[10px] uppercase tracking-widest mb-1">Tipo de Cuenta</label>
                                <div class="flex gap-4">
                                    <button type="button" @click="form.tipo_cuenta = 'monetaria'" :class="['flex-1 py-2 rounded-xl border transition-all text-sm font-medium', form.tipo_cuenta === 'monetaria' ? 'bg-primary/20 border-primary text-primary shadow-[0_0_15px_rgba(233,193,118,0.2)]' : 'bg-surface-container-highest border-white/5 text-on-surface-variant hover:border-white/20']">
                                        Monetaria
                                    </button>
                                    <button type="button" @click="form.tipo_cuenta = 'ahorro'" :class="['flex-1 py-2 rounded-xl border transition-all text-sm font-medium', form.tipo_cuenta === 'ahorro' ? 'bg-primary/20 border-primary text-primary shadow-[0_0_15px_rgba(233,193,118,0.2)]' : 'bg-surface-container-highest border-white/5 text-on-surface-variant hover:border-white/20']">
                                        Ahorro
                                    </button>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
                
                <div class="px-6 py-4 border-t border-white/10 bg-surface-container-high flex justify-end gap-3">
                    <button @click="closeModal" class="px-4 py-2 font-body-sm text-on-surface-variant hover:text-on-surface transition-colors">Cancelar</button>
                    <button @click="saveReferido" class="bg-primary text-on-primary font-label-lg px-6 py-2 rounded-full hover:bg-primary-fixed transition-colors shadow-[0_0_15px_rgba(233,193,118,0.3)]">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { referidoService } from '../../services/referidoService';
import Swal from 'sweetalert2';

const referidos = ref([]);
const searchQuery = ref('');
const showModal = ref(false);
const isEditing = ref(false);
const selectedReferido = ref(null);

const filteredReferidos = computed(() => {
    if (!searchQuery.value) return referidos.value;
    const query = searchQuery.value.toLowerCase();
    return referidos.value.filter(ref => 
        ref.nombre.toLowerCase().includes(query) || 
        (ref.dpi && ref.dpi.includes(query))
    );
});

const form = ref({
    id: null,
    nombre: '',
    dpi: '',
    telefono: '',
    direccion: '',
    numero_cuenta: '',
    banco: '',
    tipo_cuenta: 'monetaria',
    foto_perfil: null,
    dpi_anverso: null,
    dpi_reverso: null
});

// To store File objects temporarily before upload
const filesToUpload = ref({
    foto_perfil: null,
    dpi_anverso: null,
    dpi_reverso: null
});

// To preview images in UI
const previewFotos = ref({
    foto_perfil: null,
    dpi_anverso: null,
    dpi_reverso: null
});

const handleDpiInput = (e) => {
    let value = e.target.value.replace(/\D/g, ''); // Solo números
    if (value.length > 13) value = value.slice(0, 13);
    
    // Format: 0000 00000 0000
    if (value.length > 9) {
        value = `${value.slice(0, 4)} ${value.slice(4, 9)} ${value.slice(9)}`;
    } else if (value.length > 4) {
        value = `${value.slice(0, 4)} ${value.slice(4)}`;
    }
    
    form.value.dpi = value;
};

const handlePhoneInput = (e) => {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 8) value = value.slice(0, 8);
    
    if (value.length > 4) {
        value = `${value.slice(0, 4)}-${value.slice(4)}`;
    }
    
    form.value.telefono = value;
};

const formatDPI = (dpi) => dpi || '0000 00000 0000';
const formatPhone = (phone) => phone || '0000-0000';

const handleImageUpload = (event, type) => {
    const file = event.target.files[0];
    if (file) {
        filesToUpload.value[type] = file;
        previewFotos.value[type] = URL.createObjectURL(file);
    }
    event.target.value = ''; // allow replacing with same file
};

const loadReferidos = async () => {
    try {
        const response = await referidoService.getReferidos();
        referidos.value = response.data.data;
        if (selectedReferido.value) {
            const updated = referidos.value.find(r => r.id === selectedReferido.value.id);
            if (updated) selectedReferido.value = updated;
            else selectedReferido.value = null;
        }
    } catch (e) {
        console.error(e);
    }
};

const editReferido = () => {
    isEditing.value = true;
    form.value = { ...selectedReferido.value };
    filesToUpload.value = { foto_perfil: null, dpi_anverso: null, dpi_reverso: null };
    previewFotos.value = { foto_perfil: null, dpi_anverso: null, dpi_reverso: null };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    isEditing.value = false;
    form.value = { id: null, nombre: '', dpi: '', telefono: '', direccion: '', numero_cuenta: '', banco: '', tipo_cuenta: 'monetaria', foto_perfil: null, dpi_anverso: null, dpi_reverso: null };
    filesToUpload.value = { foto_perfil: null, dpi_anverso: null, dpi_reverso: null };
    previewFotos.value = { foto_perfil: null, dpi_anverso: null, dpi_reverso: null };
};

const saveReferido = async () => {
    try {
        const payload = new FormData();
        payload.append('nombre', form.value.nombre);
        payload.append('dpi', form.value.dpi);
        payload.append('telefono', form.value.telefono);
        payload.append('direccion', form.value.direccion || '');
        payload.append('numero_cuenta', form.value.numero_cuenta || '');
        payload.append('banco', form.value.banco || '');
        payload.append('tipo_cuenta', form.value.tipo_cuenta || 'monetaria');

        if (filesToUpload.value.foto_perfil) payload.append('foto_perfil', filesToUpload.value.foto_perfil);
        if (filesToUpload.value.dpi_anverso) payload.append('dpi_anverso', filesToUpload.value.dpi_anverso);
        if (filesToUpload.value.dpi_reverso) payload.append('dpi_reverso', filesToUpload.value.dpi_reverso);

        if (isEditing.value) {
            await referidoService.updateReferido(form.value.id, payload);
        } else {
            await referidoService.createReferido(payload);
        }

        closeModal();
        await loadReferidos();

        Swal.fire({
            icon: 'success',
            title: '¡Guardado!',
            text: 'El referido se guardó correctamente.',
            background: '#131313',
            color: '#ffffff',
            confirmButtonColor: '#e9c176',
            customClass: {
                popup: 'border border-white/10 rounded-2xl shadow-[0_0_40px_rgba(233,193,118,0.2)]',
            }
        });
    } catch (e) {
        console.error(e);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: e.response?.data?.error || 'Hubo un problema al guardar el referido',
            background: '#131313',
            color: '#ffffff',
            confirmButtonColor: '#e9c176',
            customClass: {
                popup: 'border border-white/10 rounded-2xl',
            }
        });
    }
};

const deleteReferido = async () => {
    const result = await Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción no se puede deshacer y borrará los archivos asociados.",
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
            await referidoService.deleteReferido(selectedReferido.value.id);
            selectedReferido.value = null;
            await loadReferidos();
            Swal.fire({
                icon: 'success',
                title: 'Eliminado',
                text: 'El referido fue eliminado.',
                background: '#131313',
                color: '#ffffff',
                confirmButtonColor: '#e9c176',
            });
        } catch (e) {
            console.error(e);
        }
    }
};

onMounted(() => {
    loadReferidos();
});
</script>
