<template>
    <div class="bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-md rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden min-h-[400px] animate-fade-in">
        
        <!-- Toolbar -->
        <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h3 class="text-xl font-bold text-brand-dark dark:text-white">Gestión de Actividades</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Crea, edita o elimina actividades</p>
            </div>
            
            <div class="flex flex-col sm:flex-row items-center gap-4 text-sm w-full sm:w-auto">
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <input 
                        v-model="searchQuery" 
                        type="text" 
                        class="border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-[#1E293B] text-gray-700 dark:text-gray-200 px-3 py-1.5 outline-none focus:ring-2 focus:ring-primary/50 w-full max-w-[200px]" 
                        placeholder="Buscar actividad..." 
                    />
                </div>
                <button 
                    @click="openCreateModal"
                    class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 w-full sm:w-auto justify-center shadow-sm"
                >
                    <PlusIcon class="w-4 h-4" />
                    Nueva Actividad
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <div v-if="loading" class="flex justify-center items-center py-20">
                <div class="w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
            </div>
            <table v-else class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800">
                        <th class="p-4">ID</th>
                        <th class="p-4">TÉCNICO</th>
                        <th class="p-4">TÍTULO</th>
                        <th class="p-4">CATEGORÍA</th>
                        <th class="p-4">ESTADO</th>
                        <th class="p-4">PRIORIDAD</th>
                        <th class="p-4 text-right">ACCIONES</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800/50">
                    <tr v-for="act in filteredList" :key="act.id" class="hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition group">
                        <td class="p-4 font-bold text-gray-400 dark:text-gray-600">#{{ act.id }}</td>
                        <td class="p-4">
                            <span class="text-gray-700 dark:text-gray-300 font-bold whitespace-nowrap">{{ act.tecnico }}</span>
                        </td>
                        <td class="p-4">
                            <div class="text-brand-dark dark:text-gray-200 font-bold min-w-[200px]">{{ act.titulo }}</div>
                            <div class="text-[10px] text-gray-400 line-clamp-1 max-w-[300px] mt-0.5">{{ act.descripcion }}</div>
                        </td>
                        <td class="p-4">
                            <span class="text-gray-600 dark:text-gray-400 font-medium bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">{{ act.categoria }}</span>
                        </td>
                        <td class="p-4">
                            <span :class="`px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider whitespace-nowrap border ${getStatusClass(act.estado)}`">
                                {{ act.estado }}
                            </span>
                        </td>
                        <td class="p-4">
                            <span :class="`px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider whitespace-nowrap border ${getPriorityClass(act.prioridad)}`">
                                {{ act.prioridad }}
                            </span>
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="openEditModal(act)" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded transition" title="Editar">
                                    <PencilIcon class="w-4 h-4" />
                                </button>
                                <button @click="deleteActivity(act.id)" class="p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition" title="Eliminar">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="filteredList.length === 0 && !loading">
                        <td colspan="7" class="p-8 text-center text-gray-500">No se encontraron actividades.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal CRUD -->
        <Transition name="fade">
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center pt-[5vh] px-4 backdrop-blur-sm bg-slate-900/40 dark:bg-black/60">
                <div class="absolute inset-0" @click="closeModal"></div>
                <div class="relative w-full max-w-2xl bg-white dark:bg-[#0f172a] rounded-2xl shadow-2xl overflow-hidden border border-white/60 dark:border-white/10 flex flex-col max-h-[90vh]">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-gray-900/50">
                        <h3 class="text-lg font-black text-brand-dark dark:text-white">
                            {{ isEditing ? 'Editar Actividad' : 'Nueva Actividad' }}
                        </h3>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <XMarkIcon class="w-6 h-6" />
                        </button>
                    </div>

                    <div class="p-6 overflow-y-auto custom-scrollbar">
                        <form @submit.prevent="saveActivity" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            
                            <!-- Título -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Título de la Actividad *</label>
                                <input v-model="form.titulo" type="text" required class="w-full border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-[#1E293B] px-3 py-2 text-sm outline-none focus:border-primary" />
                            </div>

                            <!-- Técnico -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Técnico Asignado *</label>
                                <select v-model="form.tecnico_id" required class="w-full border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-[#1E293B] px-3 py-2 text-sm outline-none focus:border-primary">
                                    <option value="" disabled>Seleccione un técnico</option>
                                    <option v-for="t in tecnicosList" :key="t.id" :value="t.id">{{ t.nombre }} ({{ t.rol }})</option>
                                </select>
                            </div>

                            <!-- Categoría -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Categoría</label>
                                <input v-model="form.categoria" type="text" class="w-full border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-[#1E293B] px-3 py-2 text-sm outline-none focus:border-primary" placeholder="Ej. Informe, Reunión..." />
                            </div>

                            <!-- Estado -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Estado</label>
                                <select v-model="form.estado" class="w-full border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-[#1E293B] px-3 py-2 text-sm outline-none focus:border-primary">
                                    <option value="PENDIENTE">PENDIENTE</option>
                                    <option value="EN PROGRESO">EN PROGRESO</option>
                                    <option value="COMPLETADA">COMPLETADA</option>
                                    <option value="CRITICA">CRITICA</option>
                                </select>
                            </div>

                            <!-- Prioridad -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Prioridad</label>
                                <select v-model="form.prioridad" class="w-full border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-[#1E293B] px-3 py-2 text-sm outline-none focus:border-primary">
                                    <option value="BAJA">BAJA</option>
                                    <option value="MEDIA">MEDIA</option>
                                    <option value="ALTA">ALTA</option>
                                    <option value="URGENTE">URGENTE</option>
                                </select>
                            </div>

                            <!-- Fechas -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Fecha Inicio</label>
                                <input v-model="form.fecha_inicio" type="date" class="w-full border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-[#1E293B] px-3 py-2 text-sm outline-none focus:border-primary" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Fecha Fin</label>
                                <input v-model="form.fecha_fin" type="date" class="w-full border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-[#1E293B] px-3 py-2 text-sm outline-none focus:border-primary" />
                            </div>

                            <!-- Descripción -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Descripción</label>
                                <textarea v-model="form.descripcion" rows="3" class="w-full border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-[#1E293B] px-3 py-2 text-sm outline-none focus:border-primary"></textarea>
                            </div>

                        </form>
                    </div>
                    
                    <!-- Footer Modal -->
                    <div class="p-5 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-3 bg-gray-50/50 dark:bg-gray-900/50">
                        <button @click="closeModal" class="px-4 py-2 text-sm font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition">
                            Cancelar
                        </button>
                        <button @click="saveActivity" :disabled="saving" class="bg-primary hover:bg-primary/90 disabled:opacity-50 text-white px-6 py-2 rounded-xl text-sm font-bold transition flex items-center gap-2">
                            <span v-if="saving" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            {{ isEditing ? 'Actualizar' : 'Guardar' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { PlusIcon, PencilIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import ActividadesService from '@/services/despacho/ActividadesService';
import Swal from 'sweetalert2';

const loading = ref(true);
const listado = ref([]);
const tecnicosList = ref([]);
const searchQuery = ref('');

// Modal state
const showModal = ref(false);
const isEditing = ref(false);
const saving = ref(false);
const form = ref({
    id: null,
    tecnico_id: '',
    titulo: '',
    descripcion: '',
    categoria: '',
    estado: 'PENDIENTE',
    prioridad: 'MEDIA',
    fecha_inicio: '',
    fecha_fin: ''
});

const loadData = async () => {
    loading.value = true;
    try {
        const [actResp, techResp] = await Promise.all([
            ActividadesService.getAll(),
            ActividadesService.getTecnicos()
        ]);
        if (actResp?.status === 'success') listado.value = actResp.data;
        if (techResp?.status === 'success') tecnicosList.value = techResp.data;
    } catch (error) {
        console.error(error);
        Swal.fire("Error", "No se pudieron cargar las actividades", "error");
    } finally {
        loading.value = false;
    }
};

const filteredList = computed(() => {
    if (!searchQuery.value) return listado.value;
    const q = searchQuery.value.toLowerCase();
    return listado.value.filter(act => 
        act.titulo.toLowerCase().includes(q) ||
        (act.tecnico && act.tecnico.toLowerCase().includes(q)) ||
        (act.categoria && act.categoria.toLowerCase().includes(q))
    );
});

// -- CRUD --

const openCreateModal = () => {
    isEditing.value = false;
    form.value = { id: null, tecnico_id: '', titulo: '', descripcion: '', categoria: '', estado: 'PENDIENTE', prioridad: 'MEDIA', fecha_inicio: '', fecha_fin: '' };
    showModal.value = true;
};

const openEditModal = (act) => {
    isEditing.value = true;
    form.value = { ...act };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const saveActivity = async () => {
    if (!form.value.titulo || !form.value.tecnico_id) {
        Swal.fire("Campos Requeridos", "El título y el técnico son obligatorios.", "warning");
        return;
    }

    saving.value = true;
    try {
        if (isEditing.value) {
            await ActividadesService.update(form.value.id, form.value);
            Swal.fire("Actualizado", "La actividad se actualizó correctamente.", "success");
        } else {
            await ActividadesService.create(form.value);
            Swal.fire("Creado", "La actividad se creó correctamente.", "success");
        }
        closeModal();
        await loadData();
    } catch (error) {
        Swal.fire("Error", "Ocurrió un error al guardar la actividad.", "error");
    } finally {
        saving.value = false;
    }
};

const deleteActivity = async (id) => {
    if (!confirm("¿Está seguro de eliminar esta actividad?")) return;
    
    try {
        await ActividadesService.delete(id);
        Swal.fire("Eliminado", "La actividad fue eliminada.", "success");
        await loadData();
    } catch (error) {
        Swal.fire("Error", "No se pudo eliminar la actividad.", "error");
    }
};

// -- UI Helpers --

const getStatusClass = (status) => {
    switch(status) {
        case 'COMPLETADA': return 'bg-emerald-100/80 text-emerald-700 border-emerald-200/50';
        case 'CRITICA': return 'bg-red-100/80 text-red-700 border-red-200/50';
        case 'EN PROGRESO': return 'bg-amber-100/80 text-amber-700 border-amber-200/50';
        default: return 'bg-gray-100/80 text-gray-700 border-gray-200/50';
    }
};

const getPriorityClass = (priority) => {
    switch(priority) {
        case 'URGENTE': return 'bg-purple-100/80 text-purple-700 border-purple-200/50';
        case 'ALTA': return 'bg-orange-100/80 text-orange-700 border-orange-200/50';
        case 'MEDIA': return 'bg-blue-100/80 text-blue-700 border-blue-200/50';
        default: return 'bg-gray-100/80 text-gray-700 border-gray-200/50';
    }
};

onMounted(loadData);
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
