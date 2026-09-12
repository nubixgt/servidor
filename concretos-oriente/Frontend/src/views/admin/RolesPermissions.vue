<template>
  <div class="min-h-screen bg-[#050f1a] text-white p-4 md:p-8 space-y-6 pb-24 font-sans">
    
    <!-- Header -->
    <div class="flex flex-col mb-8">
      <h1 class="text-3xl font-bold text-white mb-2 tracking-tight">Configuración y Administración</h1>
      <p class="text-white/60">Administre la seguridad de accesos, perfiles de los delegados y auditoría del sistema.</p>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-white/10 mb-8 overflow-x-auto pb-px scrollbar-hide">
      <button @click="$router.push('/users')" class="px-4 py-3 text-sm font-bold text-white/50 hover:text-white transition whitespace-nowrap">
        USUARIOS
      </button>
      <button class="px-4 py-3 text-sm font-bold text-blue-400 border-b-2 border-blue-400 whitespace-nowrap">
        ROLES Y PERMISOS
      </button>
    </div>

    <!-- Info Box -->
    <div class="bg-[#112236]/80 backdrop-blur-md border border-blue-500/30 rounded-2xl p-4 flex items-start gap-4 mb-8">
      <ShieldCheckIcon class="w-6 h-6 text-blue-400 shrink-0 mt-0.5" />
      <div>
        <h3 class="font-bold text-blue-400 mb-1">Control de Accesos Basado en Roles (RBAC)</h3>
        <p class="text-sm text-white/70">Defina qué módulos del portal pueden ver o modificar cada tipo de perfil de usuario para cumplir con las normativas de seguridad.</p>
      </div>
    </div>

    <!-- Main Table Card -->
    <div class="bg-[#112236]/80 backdrop-blur-md border border-white/10 rounded-2xl overflow-hidden shadow-2xl">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-white/5 border-b border-white/10">
              <th class="p-4 text-xs font-bold text-white/60 uppercase tracking-wider min-w-[200px]">Perfil de Funcionario</th>
              <th v-for="module in modules" :key="module.id" class="p-4 text-xs font-bold text-white/60 uppercase tracking-wider text-center">
                {{ module.name }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/10">
            <tr v-for="role in roles" :key="role.id" class="hover:bg-white/5 transition duration-150">
              <td class="p-4">
                <p class="font-bold text-blue-400 text-sm">{{ role.name }}</p>
                <p class="text-xs text-white/50">{{ role.description }}</p>
              </td>
              <td v-for="module in modules" :key="module.id" class="p-4 text-center">
                <div class="inline-flex relative items-center cursor-pointer" @click="togglePermission(role, module.id)">
                  <div class="w-6 h-6 rounded flex items-center justify-center transition-colors"
                       :class="hasPermission(role, module.id) ? 'bg-blue-500 shadow-[0_0_10px_rgba(59,130,246,0.5)]' : 'border border-white/20 bg-transparent'">
                    <CheckIcon v-if="hasPermission(role, module.id)" class="w-4 h-4 text-white" />
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Action Bar -->
    <div class="flex justify-end mt-6 gap-4">
      <button @click="loadRoles" class="px-6 py-2 rounded-xl border border-white/20 text-white hover:bg-white/10 transition text-sm font-bold">
        Descartar Cambios
      </button>
      <button @click="saveChanges" class="px-6 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 text-white font-bold text-sm shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 transition">
        Guardar Configuración
      </button>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { ShieldCheckIcon, CheckIcon } from '@heroicons/vue/24/solid';
import api from '../../services/api';
import Swal from 'sweetalert2';

const roles = ref([]);
const originalRoles = ref([]);

const modules = [
  { id: 'rrhh', name: 'Recursos Humanos' },
  { id: 'maquinaria', name: 'Maquinaria' },
  { id: 'inventario', name: 'Inventario' },
  { id: 'finanzas', name: 'Finanzas' },
  { id: 'configuracion', name: 'Configuración' }
];

const loadRoles = async () => {
  try {
    const response = await api.get('/roles');
    // Si no hay roles, creamos datos por defecto para la vista
    if (response.data.length === 0) {
      roles.value = [
        { id: 1, name: 'Administrador general', description: 'Control Total y Parametrización', permissions: ['rrhh', 'maquinaria', 'inventario', 'finanzas', 'configuracion'] },
        { id: 2, name: 'RRHH / Planillas', description: 'Ingreso y Atención al personal', permissions: ['rrhh'] },
        { id: 3, name: 'Operador / Técnico', description: 'Atención Técnica de Maquinaria', permissions: ['maquinaria'] },
        { id: 4, name: 'Auditor / Consulta', description: 'Monitoreo pasivo y reportes', permissions: ['inventario', 'finanzas'] }
      ];
    } else {
      roles.value = response.data;
    }
    // Copia profunda para el botón de descartar
    originalRoles.value = JSON.parse(JSON.stringify(roles.value));
  } catch (error) {
    console.error('Error cargando roles', error);
  }
};

onMounted(() => {
  loadRoles();
});

const hasPermission = (role, moduleId) => {
  return role.permissions && role.permissions.includes(moduleId);
};

const togglePermission = (role, moduleId) => {
  if (!role.permissions) {
    role.permissions = [];
  }
  const index = role.permissions.indexOf(moduleId);
  if (index > -1) {
    role.permissions.splice(index, 1);
  } else {
    role.permissions.push(moduleId);
  }
};

const saveChanges = async () => {
  try {
    // Si los roles son los del mockup inicial y no están en DB, crearlos
    for (const role of roles.value) {
      if (typeof role.id === 'number' && role.id <= 4 && originalRoles.value.length === 0) {
        await api.post('/roles', role);
      } else {
        await api.put(`/roles/${role.id}`, role);
      }
    }
    Swal.fire({
      icon: 'success',
      title: 'Configuración Guardada',
      text: 'Los permisos y roles han sido actualizados exitosamente.',
      background: '#112236',
      color: '#fff',
      confirmButtonColor: '#3b82f6'
    });
    loadRoles();
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Ocurrió un error al guardar la configuración.',
      background: '#112236',
      color: '#fff'
    });
  }
};
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
