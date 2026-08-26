<template>
    <div class="bg-white/90 dark:bg-[#1E293B]/90 backdrop-blur-md rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden min-h-[400px] animate-fade-in p-6">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center gap-3">
                <router-link to="/admin/clima/dashboard" class="p-2 bg-gray-50 dark:bg-gray-800 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <ArrowLeftIcon class="w-5 h-5" />
                </router-link>
                <div>
                    <h3 class="text-xl font-bold text-brand-dark dark:text-white">Gestión de Registros</h3>
                    <p class="text-xs text-gray-500">Administra los registros climáticos</p>
                </div>
            </div>
            <button v-if="userRole === 'ADMIN' || userRole === 'TECNICO'" @click="openCreateModal" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-xl text-xs font-bold transition">
                Nuevo Registro
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] uppercase font-bold text-gray-500 border-b border-gray-100 dark:border-gray-800">
                        <th class="p-4">ID</th>
                        <th class="p-4">USUARIO</th>
                        <th class="p-4">UBICACIÓN</th>
                        <th class="p-4">CATEGORÍA</th>
                        <th class="p-4">TIPO</th>
                        <th class="p-4">CLIMA</th>
                        <th class="p-4 text-right">ACCIONES</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800/50">
                    <tr v-for="reg in registros" :key="reg.id" class="hover:bg-blue-50/30 dark:hover:bg-blue-900/10">
                        <td class="p-4 font-bold text-gray-400">#{{ reg.id }}</td>
                        <td class="p-4 font-bold text-gray-700 dark:text-gray-200">{{ reg.usuario }}<br/><span class="text-[10px] text-gray-400">{{ reg.fecha_registro }}</span></td>
                        <td class="p-4 text-gray-600 dark:text-gray-400 max-w-[200px] truncate" :title="reg.direccion">{{ reg.direccion }}</td>
                        <td class="p-4">
                            <span :class="{'px-2 py-1 rounded font-bold':true, 'bg-green-100 text-green-600': reg.categoria === 'condicion', 'bg-red-100 text-red-600': reg.categoria === 'desastre'}">
                                {{ reg.categoria }}
                            </span>
                        </td>
                        <td class="p-4 text-gray-700">{{ reg.tipo || 'N/A' }}</td>
                        <td class="p-4 text-gray-500 text-[10px]">
                            Temp: {{ reg.clima.temperatura }}°C<br/>
                            Hum: {{ reg.clima.humedad }}%
                        </td>
                        <td class="p-4 text-right">
                            <button v-if="userRole === 'ADMIN' || userRole === 'TECNICO'" @click="deleteRegistro(reg.id)" class="text-red-500 hover:text-red-700">Eliminar</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { ArrowLeftIcon } from 'lucide-vue-next';
import api from '@/services/api';
import Swal from 'sweetalert2';

const registros = ref([]);
const userRole = ref('PROFESIONAL');

try {
  const user = JSON.parse(localStorage.getItem('user'));
  if (user && user.role) {
    userRole.value = user.role;
  }
} catch (e) {}

const loadRegistros = async () => {
    try {
        const resp = await api.get('/clima/registros');
        if (resp.data.status === 'success') registros.value = resp.data.data;
    } catch (e) {
        console.error(e);
    }
};

const deleteRegistro = async (id) => {
    if (confirm('¿Eliminar registro?')) {
        await api.delete(`/clima/registros/${id}`);
        loadRegistros();
    }
};

const openCreateModal = () => {
    Swal.fire('Próximamente', 'El formulario se abrirá aquí.', 'info');
};

onMounted(() => {
    loadRegistros();
});
</script>
