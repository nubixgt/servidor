<template>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Registro de Auditoría</h2>
                <p class="mt-1 text-sm text-gray-500">Historial de acciones y cambios en el sistema</p>
            </div>
            <button @click="loadLogs" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-sm transition-all duration-200">
                <i class="fas fa-sync-alt mr-2" :class="{'animate-spin': loading}"></i>
                Actualizar
            </button>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tabla / Registro</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="loading">
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                                <i class="fas fa-spinner fa-spin mr-2"></i> Cargando registros...
                            </td>
                        </tr>
                        <tr v-else-if="logs.length === 0">
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                                No se encontraron registros de auditoría.
                            </td>
                        </tr>
                        <tr v-else v-for="log in logs" :key="log.id" class="hover:bg-gray-50 transition-colors cursor-pointer" @click="viewDetails(log)">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ formatDate(log.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-bold text-xs">
                                        {{ getInitials(log.user_name || 'Desconocido') }}
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">{{ log.user_name || 'Usuario ' + log.user_id }}</div>
                                        <div class="text-xs text-gray-500">{{ log.user_email || 'Sin correo' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getActionClass(log.action)">
                                    {{ log.action }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="font-medium text-gray-900">{{ log.table_name }}</span> 
                                <span class="text-gray-400 mx-1">#</span>
                                {{ log.record_id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                {{ log.ip_address }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Details Modal -->
        <div v-if="selectedLog" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="selectedLog = null"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10" :class="getActionBgClass(selectedLog.action)">
                                <i class="fas fa-history text-white"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Detalle de Auditoría
                                </h3>
                                <div class="mt-4 grid grid-cols-2 gap-4 text-sm text-gray-600">
                                    <div><strong>Usuario:</strong> {{ selectedLog.user_name || selectedLog.user_id }}</div>
                                    <div><strong>Fecha:</strong> {{ formatDate(selectedLog.created_at) }}</div>
                                    <div><strong>Acción:</strong> {{ selectedLog.action }}</div>
                                    <div><strong>Tabla:</strong> {{ selectedLog.table_name }} #{{ selectedLog.record_id }}</div>
                                </div>
                                <div class="mt-6" v-if="selectedLog.old_values || selectedLog.new_values">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div v-if="selectedLog.old_values">
                                            <h4 class="text-sm font-bold text-red-600 mb-2">Valores Anteriores</h4>
                                            <pre class="bg-red-50 p-3 rounded-lg text-xs overflow-x-auto text-gray-800">{{ formatJson(selectedLog.old_values) }}</pre>
                                        </div>
                                        <div v-if="selectedLog.new_values">
                                            <h4 class="text-sm font-bold text-green-600 mb-2">Nuevos Valores</h4>
                                            <pre class="bg-green-50 p-3 rounded-lg text-xs overflow-x-auto text-gray-800">{{ formatJson(selectedLog.new_values) }}</pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" @click="selectedLog = null">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import Swal from 'sweetalert2';

const logs = ref([]);
const loading = ref(true);
const selectedLog = ref(null);

const loadLogs = async () => {
    loading.value = true;
    try {
        const { data } = await api.get('/audit?limit=200');
        logs.value = data.data || [];
    } catch (e) {
        if (e.response?.status !== 401 && e.response?.status !== 403) {
            Swal.fire('Error', 'No se pudo cargar el registro de auditoría', 'error');
        }
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadLogs();
});

const viewDetails = (log) => {
    selectedLog.value = log;
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString('es-GT', { 
        year: 'numeric', month: 'short', day: 'numeric', 
        hour: '2-digit', minute: '2-digit' 
    });
};

const getInitials = (name) => {
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

const formatJson = (obj) => {
    try {
        return JSON.stringify(obj, null, 2);
    } catch {
        return obj;
    }
};

const getActionClass = (action) => {
    switch(action?.toUpperCase()) {
        case 'CREATE': return 'bg-green-100 text-green-800';
        case 'UPDATE': return 'bg-blue-100 text-blue-800';
        case 'DELETE': return 'bg-red-100 text-red-800';
        case 'LOGIN': return 'bg-purple-100 text-purple-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getActionBgClass = (action) => {
    switch(action?.toUpperCase()) {
        case 'CREATE': return 'bg-green-500';
        case 'UPDATE': return 'bg-blue-500';
        case 'DELETE': return 'bg-red-500';
        case 'LOGIN': return 'bg-purple-500';
        default: return 'bg-gray-500';
    }
};
</script>
