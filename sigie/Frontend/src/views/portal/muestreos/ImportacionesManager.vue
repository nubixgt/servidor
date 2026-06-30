<template>
    <div class="space-y-8 animate-fade-in">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-white font-headline">Gestión de Importaciones</h1>
                <p class="text-xs text-white/60 mt-1">Catálogo de empresas importadoras registradas e historial de cargamentos ingresados al país.</p>
            </div>
            <div class="flex items-center gap-3">
                <button 
                    v-if="activeTab === 'importadores' && auth.role === 'administrador'"
                    @click="openImporterModal()"
                    class="px-5 py-3 bg-[#0a192f] hover:bg-[#122347] text-white font-bold text-xs rounded-md shadow-lg transition-all flex items-center justify-center gap-2 border border-slate-800 font-headline"
                >
                    <span class="material-symbols-outlined text-sm">add_business</span>
                    Registrar Importador
                </button>
                <button 
                    v-if="activeTab === 'importaciones'"
                    @click="openImportModal"
                    class="px-5 py-3 bg-[#0a192f] hover:bg-[#122347] text-white font-bold text-xs rounded-md shadow-lg transition-all flex items-center justify-center gap-2 border border-slate-800 font-headline"
                >
                    <span class="material-symbols-outlined text-sm">add_circle</span>
                    Registrar Importación
                </button>
            </div>
        </div>

        <!-- Tab Controls -->
        <div class="border-b border-white/10 flex gap-2">
            <button 
                @click="activeTab = 'importadores'"
                :class="['px-5 py-3 font-bold text-xs border-b-2 transition-all flex items-center gap-1.5', 
                         activeTab === 'importadores' ? 'border-primary text-white glass-card' : 'border-transparent text-gray-300 hover:text-gray-300']"
            >
                <span class="material-symbols-outlined text-sm">business</span>
                Catálogo de Importadores
            </button>
            <button 
                @click="activeTab = 'importaciones'"
                :class="['px-5 py-3 font-bold text-xs border-b-2 transition-all flex items-center gap-1.5', 
                         activeTab === 'importaciones' ? 'border-primary text-white glass-card' : 'border-transparent text-gray-300 hover:text-gray-300']"
            >
                <span class="material-symbols-outlined text-sm">inventory</span>
                Historial de Importaciones
            </button>
        </div>

        <!-- TAB 1: CATALOGO DE IMPORTADORES -->
        <div v-if="activeTab === 'importadores'" class="space-y-6">
            <div v-if="loadingImporters" class="py-16 text-center text-sm text-slate-400 glass-card rounded border border-white/10 shadow-lg">
                <span class="material-symbols-outlined text-4xl animate-spin text-white">sync</span>
                <p class="mt-2 font-bold">Cargando catálogo de importadores...</p>
            </div>

            <div v-else-if="importers.length === 0" class="py-20 text-center glass-card rounded border border-white/10 shadow-lg">
                <span class="material-symbols-outlined text-5xl text-slate-400">business_disabled</span>
                <p class="text-sm font-semibold text-white mt-4">No hay importadores registrados</p>
                <p class="text-xs text-slate-400 mt-1" v-if="auth.role === 'administrador'">Presione el botón superior para dar de alta una nueva empresa.</p>
            </div>

            <!-- Importers Grid (Responsive) -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div 
                    v-for="imp in importers" 
                    :key="imp.id" 
                    class="glass-card p-6 rounded-lg border border-white/10 hover:border-primary/20 hover:shadow-md transition-all flex flex-col justify-between"
                >
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-bold text-white text-sm tracking-tight">{{ imp.nombre }}</h3>
                            <span class="text-[9px] font-mono bg-slate-100 text-gray-300 px-2 py-0.5 rounded border border-white/10 uppercase flex-shrink-0">NIT: {{ imp.nit }}</span>
                        </div>
                        <div class="mt-4 space-y-2 text-xs">
                            <div>
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Productos Autorizados</span>
                                <p class="text-gray-300 font-semibold mt-0.5">{{ imp.tipo_productos }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 pt-4 border-t border-white/10 flex justify-end gap-2" v-if="auth.role === 'administrador'">
                        <button 
                            @click="openImporterModal(imp)"
                            class="px-3 py-1.5 bg-black/20 hover:bg-slate-100 text-gray-300 border border-white/10 rounded font-bold text-[10px] uppercase flex items-center gap-1 transition-colors"
                        >
                            <span class="material-symbols-outlined text-xs">edit</span> Editar
                        </button>
                        <button 
                            @click="deleteImporter(imp.id)"
                            class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded font-bold text-[10px] uppercase flex items-center gap-1 transition-colors"
                        >
                            <span class="material-symbols-outlined text-xs">delete</span> Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: HISTORIAL DE IMPORTACIONES -->
        <div v-if="activeTab === 'importaciones'" class="space-y-6">
            <!-- Filter Bar -->
            <div class="glass-card backdrop-blur-sm p-6 rounded-2xl border border-white/20 shadow-premium flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Filtrar por Importador</label>
                    <select 
                        v-model="filterImporter" 
                        class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                    >
                        <option value="">Todos los importadores</option>
                        <option v-for="imp in importers" :key="imp.id" :value="imp.id">{{ imp.nombre }}</option>
                    </select>
                </div>
                <div class="w-64 min-w-[150px]">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Tipo de Producto</label>
                    <input 
                        v-model="filterProductType" 
                        type="text" 
                        placeholder="Ej. cárnico ave, lácteos"
                        class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 text-xs focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                    />
                </div>
                <button 
                    @click="fetchImports"
                    class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded transition-colors flex items-center gap-1.5"
                >
                    <span class="material-symbols-outlined text-sm">filter_alt</span> Aplicar Filtros
                </button>
            </div>

            <div v-if="loadingImports" class="py-16 text-center text-sm text-slate-400 glass-card rounded border border-white/10 shadow-lg">
                <span class="material-symbols-outlined text-4xl animate-spin text-white">sync</span>
                <p class="mt-2 font-bold">Cargando historial de importaciones...</p>
            </div>

            <div v-else-if="imports.length === 0" class="py-20 text-center glass-card rounded border border-white/10 shadow-lg">
                <span class="material-symbols-outlined text-5xl text-slate-400">inventory_2</span>
                <p class="text-sm font-semibold text-white mt-4">No hay importaciones registradas para el filtro seleccionado</p>
                <p class="text-xs text-slate-400 mt-1">Comience agregando una nueva importación con el botón superior.</p>
            </div>

            <!-- Imports Table (Desktop) & Cards (Mobile) -->
            <div v-else class="glass-card backdrop-blur-sm rounded-2xl border border-white/20 shadow-premium overflow-hidden">
                <!-- Desktop View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-white/10 text-[10px] font-bold uppercase text-slate-400 tracking-wider">
                                <th class="px-6 py-4">Fecha</th>
                                <th class="px-6 py-4">Importador</th>
                                <th class="px-6 py-4">Tipo de Producto</th>
                                <th class="px-6 py-4">Establecimiento / Lugar</th>
                                <th class="px-6 py-4 text-right">Volumen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs">
                            <tr v-for="item in imports" :key="item.id" class="hover:bg-black/20 transition-colors">
                                <td class="px-6 py-4 font-mono font-bold text-gray-300">{{ item.fecha }}</td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-white">{{ item.importador_nombre }}</p>
                                    <p class="text-[9px] text-slate-400 font-mono">NIT: {{ item.importador_nit }}</p>
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-300">{{ item.tipo_producto }}</td>
                                <td class="px-6 py-4 text-gray-300 font-semibold">{{ item.establecimiento || 'N/A' }}</td>
                                <td class="px-6 py-4 text-right font-mono font-black text-white text-sm bg-blue-50/10">
                                    {{ formatVolume(item.volumen_kilos) }} kg
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile View -->
                <div class="block md:hidden divide-y divide-slate-100">
                    <div v-for="item in imports" :key="item.id" class="p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-[10px] font-bold bg-slate-100 border border-white/10 px-2 py-0.5 rounded text-gray-300">{{ item.fecha }}</span>
                            <span class="font-mono font-black text-white text-xs">{{ formatVolume(item.volumen_kilos) }} kg</span>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-white">{{ item.importador_nombre }}</p>
                            <p class="text-[9px] text-slate-400 font-mono">NIT: {{ item.importador_nit }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-[10px] pt-1 text-gray-300 font-semibold">
                            <div>
                                <span class="block text-[8px] text-slate-400 font-bold uppercase tracking-wider">Producto</span>
                                <span>{{ item.tipo_producto }}</span>
                            </div>
                            <div>
                                <span class="block text-[8px] text-slate-400 font-bold uppercase tracking-wider">Lugar</span>
                                <span>{{ item.establecimiento || 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: REGISTRAR/EDITAR IMPORTADOR -->
        <div v-if="showImporterModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="glass-card rounded-lg border border-white/10 shadow-xl max-w-md w-full overflow-hidden animate-fade-in">
                <div class="px-6 py-4 bg-slate-900 text-white flex justify-between items-center">
                    <h3 class="font-headline font-bold text-sm uppercase tracking-wider">
                        {{ editingImporterId ? 'Editar Importador' : 'Registrar Nuevo Importador' }}
                    </h3>
                    <button @click="showImporterModal = false" class="text-white hover:text-slate-300">
                        <span class="material-symbols-outlined text-lg">close</span>
                    </button>
                </div>
                <form @submit.prevent="saveImporter" class="p-6 space-y-4">
                    <div class="text-xs">
                        <label class="block font-bold text-gray-300 uppercase tracking-wider mb-1.5">Nombre de la Empresa *</label>
                        <input 
                            v-model="importerForm.nombre" 
                            type="text" 
                            required 
                            class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                        />
                    </div>
                    <div class="text-xs">
                        <label class="block font-bold text-gray-300 uppercase tracking-wider mb-1.5">NIT de la Empresa *</label>
                        <input 
                            v-model="importerForm.nit" 
                            type="text" 
                            required 
                            placeholder="Ej. 1234567-8"
                            class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                        />
                    </div>
                    <div class="text-xs">
                        <label class="block font-bold text-gray-300 uppercase tracking-wider mb-1.5">Tipos de Productos Autorizados *</label>
                        <textarea 
                            v-model="importerForm.tipo_productos" 
                            required 
                            rows="3" 
                            placeholder="Especifique los productos autorizados (ej. Cárnico de ave, Lácteos, Quesos)"
                            class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                        ></textarea>
                    </div>

                    <div class="pt-4 flex justify-end gap-3 text-xs">
                        <button 
                            type="button" 
                            @click="showImporterModal = false" 
                            class="px-4 py-2 border border-white/10 text-gray-300 font-bold rounded hover:bg-black/20 transition-colors"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            :disabled="savingImporter"
                            class="px-5 py-2 bg-[#0a192f] hover:bg-[#122347] text-white font-bold rounded border border-slate-800 flex items-center gap-1.5"
                        >
                            <span class="material-symbols-outlined text-sm animate-spin" v-if="savingImporter">sync</span>
                            <span>Guardar</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: REGISTRAR IMPORTACION -->
        <div v-if="showImportModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="glass-card rounded-lg border border-white/10 shadow-xl max-w-md w-full overflow-hidden animate-fade-in">
                <div class="px-6 py-4 bg-slate-900 text-white flex justify-between items-center">
                    <h3 class="font-headline font-bold text-sm uppercase tracking-wider">Registrar Importación</h3>
                    <button @click="showImportModal = false" class="text-white hover:text-slate-300">
                        <span class="material-symbols-outlined text-lg">close</span>
                    </button>
                </div>
                <form @submit.prevent="saveImport" class="p-6 space-y-4">
                    <div class="text-xs">
                        <label class="block font-bold text-gray-300 uppercase tracking-wider mb-1.5">Fecha del Cargamento *</label>
                        <input 
                            v-model="importForm.fecha" 
                            type="date" 
                            required 
                            class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                        />
                    </div>
                    <div class="text-xs">
                        <label class="block font-bold text-gray-300 uppercase tracking-wider mb-1.5">Empresa Importadora *</label>
                        <select 
                            v-model="importForm.importador_id" 
                            required 
                            class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                        >
                            <option value="">Seleccione el importador</option>
                            <option v-for="imp in importers" :key="imp.id" :value="imp.id">{{ imp.nombre }}</option>
                        </select>
                    </div>
                    <div class="text-xs">
                        <label class="block font-bold text-gray-300 uppercase tracking-wider mb-1.5">Tipo de Producto *</label>
                        <input 
                            v-model="importForm.tipo_producto" 
                            type="text" 
                            required 
                            placeholder="Ej. Cárnico de ave, Lácteos, Quesos"
                            class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                        />
                    </div>
                    <div class="text-xs">
                        <label class="block font-bold text-gray-300 uppercase tracking-wider mb-1.5">Volumen en Kilos *</label>
                        <input 
                            v-model.number="importForm.volumen_kilos" 
                            type="number" 
                            step="0.01" 
                            required 
                            placeholder="Ej. 12000.50"
                            class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 focus:border-blue-600 focus:glass-card outline-none transition-all text-white font-mono"
                        />
                    </div>
                    <div class="text-xs">
                        <label class="block font-bold text-gray-300 uppercase tracking-wider mb-1.5">Establecimiento / Lugar de Ingreso</label>
                        <input 
                            v-model="importForm.establecimiento" 
                            type="text" 
                            placeholder="Ej. Aduana Express, Puerto Quetzal"
                            class="w-full bg-black/20 border border-slate-300 rounded px-3 py-2 focus:border-blue-600 focus:glass-card outline-none transition-all text-white"
                        />
                    </div>

                    <div class="pt-4 flex justify-end gap-3 text-xs">
                        <button 
                            type="button" 
                            @click="showImportModal = false" 
                            class="px-4 py-2 border border-white/10 text-gray-300 font-bold rounded hover:bg-black/20 transition-colors"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            :disabled="savingImport"
                            class="px-5 py-2 bg-[#0a192f] hover:bg-[#122347] text-white font-bold rounded border border-slate-800 flex items-center gap-1.5"
                        >
                            <span class="material-symbols-outlined text-sm animate-spin" v-if="savingImport">sync</span>
                            <span>Guardar</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../../../stores/authStore.js';
import api from '../../../services/api.js';
import Swal from 'sweetalert2';

const auth = useAuthStore();

// States
const activeTab = ref('importadores');
const loadingImporters = ref(true);
const loadingImports = ref(true);

const importers = ref([]);
const imports = ref([]);

// Filter States
const filterImporter = ref('');
const filterProductType = ref('');

// Importer Form States
const showImporterModal = ref(false);
const savingImporter = ref(false);
const editingImporterId = ref(null);
const importerForm = ref({
    nombre: '',
    nit: '',
    tipo_productos: ''
});

// Import Form States
const showImportModal = ref(false);
const savingImport = ref(false);
const importForm = ref({
    fecha: new Date().toISOString().substring(0, 10),
    importador_id: '',
    tipo_producto: '',
    volumen_kilos: null,
    establecimiento: ''
});

// Fetch Importers list
const fetchImporters = async () => {
    loadingImporters.value = true;
    try {
        const response = await api.get('/importadores');
        if (response.data?.status === 'success') {
            importers.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al recuperar importadores', error);
        Swal.fire('Error', 'No se pudieron recuperar las empresas importadoras', 'error');
    } finally {
        loadingImporters.value = false;
    }
};

// Fetch Imports list
const fetchImports = async () => {
    loadingImports.value = true;
    try {
        const params = {};
        if (filterImporter.value) params.importador_id = filterImporter.value;
        if (filterProductType.value) params.tipo_producto = filterProductType.value;

        const response = await api.get('/importaciones', { params });
        if (response.data?.status === 'success') {
            imports.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al recuperar historial de importaciones', error);
        Swal.fire('Error', 'No se pudo recuperar el historial de importaciones', 'error');
    } finally {
        loadingImports.value = false;
    }
};

// Open Importer Create/Edit Modal
const openImporterModal = (importer = null) => {
    if (importer) {
        editingImporterId.value = importer.id;
        importerForm.value = {
            nombre: importer.nombre,
            nit: importer.nit,
            tipo_productos: importer.tipo_productos
        };
    } else {
        editingImporterId.value = null;
        importerForm.value = {
            nombre: '',
            nit: '',
            tipo_productos: ''
        };
    }
    showImporterModal.value = true;
};

// Save Importer
const saveImporter = async () => {
    savingImporter.value = true;
    try {
        if (editingImporterId.value) {
            // Update
            const response = await api.put(`/importadores/${editingImporterId.value}`, importerForm.value);
            if (response.data?.status === 'success') {
                Swal.fire('Completado', 'Empresa importadora actualizada con éxito', 'success');
                showImporterModal.value = false;
                fetchImporters();
            }
        } else {
            // Create
            const response = await api.post('/importadores', importerForm.value);
            if (response.data?.status === 'success') {
                Swal.fire('Registrado', 'Empresa importadora registrada con éxito', 'success');
                showImporterModal.value = false;
                fetchImporters();
            }
        }
    } catch (error) {
        console.error('Error al guardar importador', error);
        const errorMsg = error.response?.data?.error || 'No se pudo guardar la información del importador';
        Swal.fire('Error', errorMsg, 'error');
    } finally {
        savingImporter.value = false;
    }
};

// Delete Importer
const deleteImporter = (id) => {
    Swal.fire({
        title: '¿Está seguro de eliminar?',
        text: 'Se eliminará la empresa y todo su historial de importaciones de forma permanente.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await api.delete(`/importadores/${id}`);
                if (response.data?.status === 'success') {
                    Swal.fire('Eliminado', 'Importador removido del catálogo', 'success');
                    fetchImporters();
                    fetchImports();
                }
            } catch (error) {
                console.error('Error al eliminar importador', error);
                Swal.fire('Error', 'No se pudo eliminar el importador seleccionado', 'error');
            }
        }
    });
};

// Open Import Modal
const openImportModal = () => {
    importForm.value = {
        fecha: new Date().toISOString().substring(0, 10),
        importador_id: '',
        tipo_producto: '',
        volumen_kilos: null,
        establecimiento: ''
    };
    showImportModal.value = true;
};

// Save Import Record
const saveImport = async () => {
    savingImport.value = true;
    try {
        const response = await api.post('/importaciones', importForm.value);
        if (response.data?.status === 'success') {
            Swal.fire('Ingresado', 'Cargamento registrado y volumen acumulado correctamente.', 'success');
            showImportModal.value = false;
            fetchImports();
        }
    } catch (error) {
        console.error('Error al registrar importación', error);
        const errorMsg = error.response?.data?.error || 'No se pudo registrar la importación';
        Swal.fire('Error', errorMsg, 'error');
    } finally {
        savingImport.value = false;
    }
};

// Format values helpers
const formatVolume = (val) => {
    if (!val) return '0.00';
    return parseFloat(val).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

onMounted(() => {
    fetchImporters();
    fetchImports();
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
