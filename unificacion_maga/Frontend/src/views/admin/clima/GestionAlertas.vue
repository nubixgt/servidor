<template>
    <div class="bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-md rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden min-h-[400px] animate-fade-in p-6">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-3">
                <router-link to="/admin/clima/dashboard" class="p-2 bg-gray-50 dark:bg-gray-800 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" title="Volver al Dashboard">
                    <ArrowLeftIcon class="w-5 h-5" />
                </router-link>
                <div>
                    <h3 class="text-xl font-bold text-brand-dark dark:text-white">Gestión de Alertas</h3>
                    <p class="text-xs text-gray-500">Administra las alertas climatológicas del sistema</p>
                </div>
            </div>
            <button v-if="!showCreateForm" @click="showCreateForm = true" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2">
                <PlusIcon class="w-4 h-4" /> Nueva Alerta
            </button>
            <button v-else @click="showCreateForm = false" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2">
                ← Volver al Panel
            </button>
        </div>

        <div v-if="!showCreateForm" class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] uppercase font-bold text-gray-500 border-b border-gray-100 dark:border-gray-800">
                        <th class="p-4">ID</th>
                        <th class="p-4">TÍTULO</th>
                        <th class="p-4">SEVERIDAD</th>
                        <th class="p-4">REGIÓN</th>
                        <th class="p-4">ESTADO</th>
                        <th class="p-4">FECHAS</th>
                        <th class="p-4 text-right">ACCIONES</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800/50">
                    <tr v-for="alerta in alertas" :key="alerta.id" class="hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition">
                        <td class="p-4 font-bold text-gray-400">#{{ alerta.id }}</td>
                        <td class="p-4 font-bold text-gray-700 dark:text-gray-200">{{ alerta.titulo }}<br/><span class="text-[10px] text-gray-400">{{ alerta.tipo_alerta }}</span></td>
                        <td class="p-4">
                            <span :class="{
                                'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400': alerta.nivel_severidad === 'ALTA',
                                'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400': alerta.nivel_severidad === 'MEDIA',
                                'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400': alerta.nivel_severidad === 'BAJA'
                            }" class="px-2 py-1 rounded font-bold">{{ alerta.nivel_severidad }}</span>
                        </td>
                        <td class="p-4 text-gray-600 dark:text-gray-400">{{ alerta.region }}</td>
                        <td class="p-4"><span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded font-bold">{{ alerta.estado }}</span></td>
                        <td class="p-4 text-gray-500 text-[10px]">{{ alerta.fecha_emision }}<br/>{{ alerta.fecha_vigencia }}</td>
                        <td class="p-4 text-right">
                            <button @click="deleteAlerta(alerta.id)" class="text-red-500 hover:text-red-700 p-2 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                                <Trash2Icon class="w-4 h-4" />
                            </button>
                        </td>
                    </tr>
                    <tr v-if="alertas.length === 0">
                        <td colspan="7" class="p-8 text-center text-gray-500">No hay alertas registradas.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="bg-[#1E293B]/50 dark:bg-[#0F172A] rounded-2xl p-8 border border-gray-200 dark:border-gray-800">
            <h4 class="text-lg font-bold text-brand-dark dark:text-white mb-6 flex items-center gap-2">
                <PlusIcon class="w-5 h-5 text-indigo-400" /> Crear Nueva Alerta
            </h4>
            
            <form @submit.prevent="submitAlerta" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Título -->
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-2 uppercase">Título de la alerta *</label>
                        <input v-model="form.titulo" type="text" required placeholder="Ej: Alerta de Sequía - ALTA"
                               class="w-full bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-gray-800 dark:text-white" />
                    </div>
                    <!-- Tipo -->
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-2 uppercase">Tipo de alerta *</label>
                        <select v-model="form.tipoAlerta" required class="w-full bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-gray-800 dark:text-white">
                            <option value="" disabled>Selecciona una opción</option>
                            <option value="Sequía">Sequía</option>
                            <option value="Inundación">Inundación</option>
                            <option value="Helada">Helada</option>
                            <option value="Tormenta">Tormenta</option>
                            <option value="Ola de Calor">Ola de Calor</option>
                        </select>
                    </div>
                    <!-- Severidad -->
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-2 uppercase">Nivel de severidad *</label>
                        <select v-model="form.nivelSeveridad" required class="w-full bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-gray-800 dark:text-white">
                            <option value="" disabled>Selecciona una opción</option>
                            <option value="ALTA">ALTA</option>
                            <option value="MEDIA">MEDIA</option>
                            <option value="BAJA">BAJA</option>
                        </select>
                    </div>
                    <!-- Región -->
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-2 uppercase">Región afectada *</label>
                        <select v-model="form.region" required class="w-full bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-gray-800 dark:text-white">
                            <option value="" disabled>Selecciona una opción</option>
                            <option value="Nacional">Nacional</option>
                            <option value="Norte">Región Norte</option>
                            <option value="Sur">Región Sur</option>
                            <option value="Este">Región Este</option>
                            <option value="Oeste">Región Oeste</option>
                            <option value="Central">Región Central</option>
                        </select>
                    </div>
                </div>

                <!-- Descripción Corta -->
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-2 uppercase flex justify-between">
                        <span>Descripción corta * (Max 150 caracteres)</span>
                        <span class="text-[10px] font-normal text-gray-400">{{ 150 - (form.descripcionCorta?.length || 0) }} caracteres restantes</span>
                    </label>
                    <textarea v-model="form.descripcionCorta" maxlength="150" required rows="2" placeholder="Descripción breve que aparecerá en la tarjeta..."
                              class="w-full bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition resize-none text-gray-800 dark:text-white"></textarea>
                </div>

                <!-- Descripción Detallada -->
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-2 uppercase flex justify-between">
                        <span>Descripción detallada * (Max 500 caracteres)</span>
                        <span class="text-[10px] font-normal text-gray-400">{{ 500 - (form.descripcionDetallada?.length || 0) }} caracteres restantes</span>
                    </label>
                    <textarea v-model="form.descripcionDetallada" maxlength="500" required rows="4" placeholder="Descripción completa de la alerta con todos los detalles..."
                              class="w-full bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition resize-none text-gray-800 dark:text-white"></textarea>
                </div>

                <div class="flex justify-end mt-8">
                    <button type="submit" :disabled="saving" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl text-sm font-bold transition flex items-center gap-2 shadow-lg shadow-indigo-500/30 disabled:opacity-50">
                        <span v-if="saving" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        {{ saving ? 'Guardando...' : 'Crear Alerta' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import { ArrowLeftIcon, PlusIcon, Trash2Icon } from 'lucide-vue-next';
import api from '@/services/api';
import Swal from 'sweetalert2';

const alertas = ref([]);

const showCreateForm = ref(false);
const saving = ref(false);

const form = reactive({
    titulo: '',
    tipoAlerta: '',
    nivelSeveridad: '',
    region: '',
    descripcionCorta: '',
    descripcionDetallada: ''
});

const loadAlertas = async () => {
    try {
        const resp = await api.get('/clima/alertas');
        if (resp.data.status === 'success') alertas.value = resp.data.data;
    } catch (e) {
        console.error(e);
    }
};

const submitAlerta = async () => {
    try {
        saving.value = true;
        
        const payload = {
            ...form,
            estado: 'Activa',
            // Default dates logic
            fechaEmision: new Date().toISOString().slice(0,19).replace('T', ' '),
            fechaVigencia: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().slice(0,19).replace('T', ' ')
        };

        const resp = await api.post('/clima/alertas', payload);
        
        if (resp.data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Alerta Creada',
                text: 'La alerta ha sido guardada correctamente.',
                confirmButtonColor: '#4f46e5'
            });
            showCreateForm.value = false;
            // Reset form
            Object.keys(form).forEach(key => form[key] = '');
            loadAlertas();
        }
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Hubo un error al guardar la alerta',
            confirmButtonColor: '#ef4444'
        });
        console.error(e);
    } finally {
        saving.value = false;
    }
};

const deleteAlerta = async (id) => {
    const result = await Swal.fire({
        title: '¿Eliminar alerta?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            await api.delete(`/clima/alertas/${id}`);
            loadAlertas();
            Swal.fire('Eliminada', 'La alerta ha sido eliminada.', 'success');
        } catch (e) {
            Swal.fire('Error', 'No se pudo eliminar la alerta.', 'error');
        }
    }
};

onMounted(() => {
    loadAlertas();
});
</script>
