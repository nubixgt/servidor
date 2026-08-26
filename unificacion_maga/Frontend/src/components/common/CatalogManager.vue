<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-bold text-gray-800 dark:text-white">{{ title }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Gestión de registros</p>
      </div>
      <button 
        @click="openModal()"
        class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white rounded-xl hover:bg-brand-dark transition-colors"
      >
        <i class="fas fa-plus"></i> Nuevo
      </button>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-[#1e293b] rounded-2xl border border-gray-100 dark:border-white/5 overflow-hidden shadow-sm">
      <div v-if="loading" class="p-8 text-center text-gray-500">
        <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
        <p>Cargando datos...</p>
      </div>
      
      <div v-else-if="items.length === 0" class="p-12 text-center text-gray-400">
        <i class="fas fa-inbox text-4xl mb-3 opacity-50"></i>
        <p>No hay registros disponibles</p>
      </div>

      <table v-else class="w-full">
        <thead>
          <tr class="bg-gray-50/50 dark:bg-white/5 border-b border-gray-100 dark:border-white/5">
            <th v-for="col in columns" :key="col.key" class="text-left py-3 px-4 text-xs font-bold text-gray-500 uppercase tracking-wider">
              {{ col.label }}
            </th>
            <th class="text-right py-3 px-4 text-xs font-bold text-gray-500 uppercase tracking-wider w-24">
              Acciones
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
          <tr v-for="item in items" :key="item[pkField]" class="hover:bg-gray-50/50 dark:hover:bg-white/5 transition-colors">
            <td v-for="col in columns" :key="col.key" class="py-3 px-4 text-sm text-gray-700 dark:text-gray-300">
              <span v-if="col.type === 'boolean'">
                <span :class="item[col.key] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="px-2 py-1 rounded-full text-xs font-bold">
                  {{ item[col.key] ? 'Activo' : 'Inactivo' }}
                </span>
              </span>
              <span v-else>{{ getValue(item, col) }}</span>
            </td>
            <td class="py-3 px-4 text-right space-x-2">
              <button @click="openModal(item)" class="text-blue-500 hover:text-blue-600 p-1">
                <i class="fas fa-edit"></i>
              </button>
              <button @click="deleteItem(item)" class="text-red-500 hover:text-red-600 p-1">
                <i class="fas fa-trash-alt"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#1e293b] rounded-2xl shadow-xl w-full max-w-lg overflow-hidden border border-gray-100 dark:border-white/10 relative">
          <div class="px-6 py-4 border-b border-gray-100 dark:border-white/10 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white">
              {{ isEditing ? 'Editar Registro' : 'Nuevo Registro' }}
            </h3>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
              <i class="fas fa-times"></i>
            </button>
          </div>
          
          <form @submit.prevent="saveItem" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
            <div v-for="field in fields" :key="field.key">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ field.label }} <span v-if="field.required" class="text-red-500">*</span>
              </label>
              
              <input 
                v-if="field.type === 'text' || field.type === 'number'"
                v-model="currentItem[field.key]"
                :type="field.type"
                :required="field.required"
                class="w-full px-4 py-2 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 focus:ring-2 focus:ring-brand/20 outline-none transition-all"
              />
              
              <textarea 
                v-else-if="field.type === 'textarea'"
                v-model="currentItem[field.key]"
                :required="field.required"
                rows="3"
                class="w-full px-4 py-2 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 focus:ring-2 focus:ring-brand/20 outline-none transition-all"
              ></textarea>
  
              <select
                v-else-if="field.type === 'select'"
                v-model="currentItem[field.key]"
                :required="field.required"
                class="w-full px-4 py-2 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 focus:ring-2 focus:ring-brand/20 outline-none transition-all"
              >
                <option value="">Seleccione...</option>
                <option v-for="opt in field.options" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </option>
              </select>
            </div>
  
            <!-- Active Checkbox -->
             <div class="flex items-center gap-2">
               <input type="checkbox" v-model="currentItem.activo" id="activoCheck" class="rounded text-brand focus:ring-brand">
               <label for="activoCheck" class="text-sm text-gray-700 dark:text-gray-300">Activo</label>
             </div>
          </form>
          
          <div class="px-6 py-4 bg-gray-50 dark:bg-white/5 flex justify-end gap-3 border-t border-gray-100 dark:border-white/10">
            <button @click="closeModal" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-white/10 rounded-xl transition-colors">
              Cancelar
            </button>
            <button 
              @click="saveItem" 
              :disabled="saving"
              class="px-6 py-2 bg-brand text-white rounded-xl hover:bg-brand-dark transition-all shadow-lg shadow-brand/20 disabled:opacity-50"
            >
              <i v-if="saving" class="fas fa-spinner fa-spin mr-2"></i>
              {{ saving ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '@/services/api';
import Swal from 'sweetalert2';

const props = defineProps({
  title: String,
  apiEndpoint: String, // e.g. 'catalogos/adq_direcciones'
  columns: Array, // [{ key: 'nombre', label: 'Nombre' }]
  fields: Array, // [{ key: 'nombre', label: 'Nombre', type: 'text', required: true }]
  pkField: { type: String, default: 'id' }
});

const items = ref([]);
const loading = ref(true);
const showModal = ref(false);
const saving = ref(false);
const currentItem = ref({});

const isEditing = computed(() => !!currentItem.value[props.pkField]);

onMounted(() => {
  fetchItems();
});

const fetchItems = async () => {
  loading.value = true;
  try {
    const { data } = await api.get('/' + props.apiEndpoint);
    if (data.success) {
      items.value = data.data;
    }
  } catch (error) {
    console.error(error);
    Swal.fire('Error', 'No se pudieron cargar los datos', 'error');
  } finally {
    loading.value = false;
  }
};

const getValue = (item, col) => {
    // Check if column expects a lookup or nested value based on some convention if needed
    // For now simple access
    return item[col.key];
}

const openModal = (item = null) => {
  if (item) {
    currentItem.value = { ...item };
    // Ensure activo is boolean for checkbox
    currentItem.value.activo = !!currentItem.value.activo;
  } else {
    // Default values
    currentItem.value = { activo: true };
    props.fields.forEach(f => {
        if(f.default) currentItem.value[f.key] = f.default;
    });
  }
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  currentItem.value = {};
};

const saveItem = async () => {
  saving.value = true;
  try {
    // Convert boolean back to 1/0 if backend expects int
    const payload = { ...currentItem.value };
    payload.activo = payload.activo ? 1 : 0;

    let res;
    if (isEditing.value) {
      res = await api.put(`/${props.apiEndpoint}/${payload[props.pkField]}`, payload);
    } else {
      res = await api.post(`/${props.apiEndpoint}`, payload);
    }

    if (res.data.success) {
      Swal.fire('Éxito', res.data.message, 'success');
      closeModal();
      fetchItems();
    } else {
        throw new Error(res.data.message);
    }
  } catch (error) {
     console.error(error);
     Swal.fire('Error', error.response?.data?.message || error.message || 'Error al guardar', 'error');
  } finally {
    saving.value = false;
  }
};

const deleteItem = async (item) => {
  const result = await Swal.fire({
    title: '¿Estás seguro?',
    text: "Se desactivará este registro",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Sí, eliminar'
  });

  if (result.isConfirmed) {
    try {
      const res = await api.delete(`/${props.apiEndpoint}/${item[props.pkField]}`);
      if (res.data.success) {
        Swal.fire('Eliminado', res.data.message, 'success');
        fetchItems();
      } else {
          throw new Error(res.data.message);
      }
    } catch (error) {
       Swal.fire('Error', error.response?.data?.message || 'Error al eliminar', 'error');
    }
  }
};
</script>
