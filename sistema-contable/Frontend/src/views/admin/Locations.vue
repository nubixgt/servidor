<template>
  <div class="space-y-6 max-w-7xl mx-auto pb-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-[28px] font-sans font-bold text-[#221f47] tracking-tight">Propiedades</h1>
        <p class="text-sm text-gray-400 mt-1">Gestiona todas tus propiedades</p>
      </div>
      <button @click="openModal" class="flex items-center gap-2 px-5 py-2.5 bg-[#221f47] text-white rounded-xl text-sm font-medium hover:bg-[#343063] transition-colors shadow-sm">
        <PlusIcon class="w-4 h-4" />
        Nueva Propiedad
      </button>
    </div>

    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row gap-4 mb-2">
      <div class="relative flex-1">
        <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
        <input 
          type="text" 
          v-model="searchQuery"
          placeholder="Buscar propiedades..." 
          class="w-full pl-11 pr-4 py-2.5 bg-white border border-gray-200 rounded-[10px] text-sm focus:outline-none focus:ring-1 focus:ring-gray-300 focus:border-gray-300 transition-all shadow-sm"
        />
      </div>
      <div class="relative w-full sm:w-64">
        <select 
          v-model="selectedType"
          class="w-full pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-[10px] text-sm focus:outline-none focus:ring-1 focus:ring-gray-300 focus:border-gray-300 transition-all shadow-sm appearance-none text-gray-700"
        >
          <option value="Todos">Todos los tipos</option>
          <option value="Heladería">Heladería</option>
          <option value="Casa">Casa</option>
          <option value="Local">Local</option>
        </select>
        <ChevronDownIcon class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
      </div>
    </div>

    <!-- Grid Layout -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div v-for="prop in filteredProperties" :key="prop.id" class="bg-white rounded-2xl border border-gray-200 shadow-[0_2px_8px_-4px_rgba(0,0,0,0.1)] overflow-hidden flex flex-col hover:shadow-md transition-shadow">
        
        <!-- Image Area -->
        <div class="relative h-[200px] w-full bg-gray-100 overflow-hidden">
          <img 
            :src="getImageUrl(prop)" 
            alt="Propiedad" 
            class="w-full h-full object-cover"
          />
          <div :class="['absolute top-4 left-4 px-3 py-1 rounded-full text-[11px] font-bold tracking-wide shadow-sm', getBadgeClass(prop.type)]">
            {{ prop.type }}
          </div>
        </div>

        <!-- Content Area -->
        <div class="p-5 flex-1 flex flex-col">
          <h2 class="text-lg font-bold text-[#1f1f2c] mb-2">{{ prop.name }}</h2>
          
          <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <MapPinIcon class="w-4 h-4 shrink-0" />
            <span>{{ prop.address }}</span>
          </div>

          <div v-if="prop.rent" class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <span class="font-mono font-medium text-gray-400">$</span>
            <span>Q {{ prop.rent }}/mes</span>
          </div>
          
          <div v-else class="mb-4 text-sm text-transparent select-none">-</div> <!-- Spacer for exact alignment if no rent -->

          <!-- Actions Footer -->
          <div class="mt-auto pt-4 border-t border-gray-100 flex gap-3">
            <button @click="editLocation(prop)" class="flex-1 flex items-center justify-center gap-2 py-2 px-4 rounded-[10px] border border-gray-200 text-sm font-semibold text-[#1f1f2c] hover:bg-gray-50 transition-colors">
              <PencilIcon class="w-4 h-4" />
              Editar
            </button>
            <button @click="deleteLocation(prop.id)" class="flex items-center justify-center w-10 h-10 rounded-[10px] border border-red-100 text-red-500 hover:bg-red-50 transition-colors shrink-0">
              <TrashIcon class="w-[18px] h-[18px]" />
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- New Property Modal -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" @click="closeModal"></div>
      
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden transform transition-all relative z-10">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 class="text-lg font-bold text-[#221f47]">{{ newProp.id ? 'Editar Propiedad' : 'Nueva Propiedad' }}</h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>
        
        <!-- Body -->
        <div class="px-6 py-5 space-y-4">
          <!-- Form fields -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
            <input v-model="newProp.name" type="text" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-[10px] text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="Ej. Heladería Pradera" required />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ubicación (Dirección) *</label>
            <input v-model="newProp.address" type="text" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-[10px] text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="Dirección completa" required />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Ubicación *</label>
            <div class="relative">
              <select v-model="newProp.type" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-[10px] text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all appearance-none cursor-pointer">
                <option value="Heladería">Heladería</option>
                <option value="Local">Local</option>
                <option value="Casa">Casa</option>
              </select>
              <ChevronDownIcon class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Responsable *</label>
            <input v-model="newProp.responsible_name" type="text" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-[10px] text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="Nombre completo del responsable" required />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Foto (Opcional)</label>
            <div class="flex items-center gap-4">
              <label class="flex items-center justify-center w-full px-4 py-6 bg-gray-50 border-2 border-dashed border-gray-200 rounded-[10px] hover:bg-gray-100 hover:border-gray-300 transition-all cursor-pointer group">
                <div class="flex flex-col items-center">
                  <div v-if="photoPreviewUrl" class="mb-2 rounded-lg overflow-hidden w-full max-w-[150px] aspect-video">
                    <img :src="photoPreviewUrl" class="w-full h-full object-cover" />
                  </div>
                  <PhotoIcon v-else class="w-8 h-8 text-gray-400 mb-1 group-hover:text-gray-500 transition-colors" />
                  <span class="text-sm text-gray-500 group-hover:text-gray-600 text-center">{{ photoPreviewUrl ? 'Cambiar imagen' : 'Haga clic para subir una foto' }}</span>
                </div>
                <input type="file" accept="image/*" class="hidden" @change="handleFileChange" />
              </label>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
          <button @click="closeModal" type="button" class="px-5 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors">
            Cancelar
          </button>
          <button @click="saveProperty" :disabled="submitting || !newProp.name || !newProp.address || !newProp.type || !newProp.responsible_name" type="button" class="px-5 py-2 bg-[#221f47] text-white rounded-lg text-sm font-medium hover:bg-[#343063] transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
            <span v-if="submitting" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            Guardar Propiedad
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { 
  MagnifyingGlassIcon, 
  PlusIcon,
  MapPinIcon,
  PencilIcon,
  TrashIcon,
  ChevronDownIcon,
  XMarkIcon,
  PhotoIcon
} from '@heroicons/vue/24/outline';
import api from '../../services/api';

const allProperties = ref([]);
const searchQuery = ref('');
const selectedType = ref('Todos');

const fetchProperties = async () => {
  try {
    const res = await api.get('/locations');
    allProperties.value = res.data.data.map(item => ({
      ...item,
      id: item.id,
      name: item.name,
      type: item.type,
      address: item.address || item.location,
      rent: item.monthly_rent_amount ? Number(item.monthly_rent_amount).toLocaleString() : null,
      photo: item.photo_path,
      responsible_name: item.responsible_name
    }));
  } catch (error) {
    console.error(error);
  }
};

onMounted(() => {
  fetchProperties();
});

const isModalOpen = ref(false);
const submitting = ref(false);
const photoPreviewUrl = ref(null);
const newProp = ref({
  id: null,
  name: '',
  address: '',
  type: 'Heladería',
  responsible_name: '',
  photoFile: null
});

const openModal = () => {
  newProp.value = { id: null, name: '', address: '', type: 'Heladería', responsible_name: '', photoFile: null };
  photoPreviewUrl.value = null;
  isModalOpen.value = true;
};

const editLocation = (prop) => {
  newProp.value = {
    id: prop.id,
    name: prop.name,
    address: prop.address,
    type: prop.type,
    responsible_name: prop.responsible_name || '',
    photoFile: null
  };
  photoPreviewUrl.value = getImageUrl(prop);
  isModalOpen.value = true;
};

const deleteLocation = async (id) => {
  if (confirm('¿Estás seguro de que deseas eliminar esta propiedad?')) {
    try {
      await api.delete(`/locations/${id}`);
      await fetchProperties();
    } catch (err) {
      console.error(err);
      alert(err.response?.data?.error || 'No se pudo eliminar la propiedad.');
    }
  }
};

const closeModal = () => {
  isModalOpen.value = false;
};

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    newProp.value.photoFile = file;
    photoPreviewUrl.value = URL.createObjectURL(file);
  }
};

const saveProperty = async () => {
  submitting.value = true;
  try {
    const formData = new FormData();
    formData.append('name', newProp.value.name);
    formData.append('address', newProp.value.address);
    formData.append('type', newProp.value.type);
    formData.append('responsible_name', newProp.value.responsible_name);
    if (newProp.value.photoFile) {
      formData.append('photo', newProp.value.photoFile);
    }

    if (newProp.value.id) {
      await api.post(`/locations/${newProp.value.id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
    } else {
      await api.post('/locations', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
    }
    
    await fetchProperties();
    closeModal();
  } catch (err) {
    console.error('Error saving property:', err);
    alert('Ocurrió un error al guardar o actualizar la propiedad.');
  } finally {
    submitting.value = false;
  }
};

const filteredProperties = computed(() => {
  return allProperties.value.filter(prop => {
    const nameStr = prop.name || '';
    const addressStr = prop.address || '';
    const searchStr = searchQuery.value || '';
    
    const matchesSearch = nameStr.toLowerCase().includes(searchStr.toLowerCase()) || 
                          addressStr.toLowerCase().includes(searchStr.toLowerCase());
    const matchesType = selectedType.value === 'Todos' || prop.type === selectedType.value;
    return matchesSearch && matchesType;
  });
});

const getBadgeClass = (type) => {
  switch(type) {
    case 'Heladería':
      return 'bg-[#FFF3E0] text-[#E65100]';
    case 'Casa':
      return 'bg-[#E8F5E9] text-[#2E7D32]';
    case 'Local':
    default:
      return 'bg-[#E1F5FE] text-[#0277BD]';
  }
};

const getImageUrl = (prop) => {
  if (prop.photo && prop.photo !== '') {
    // Tomamos la baseURL configurada en axios (ej. "http://localhost:8080/api/v1") 
    // y la reemplazamos para apuntar correctamente a la carpeta uploads.
    // Local: http://localhost:8080/uploads/...
    // Prod: https://m.nubix.gt/sistema-contable/Backend/uploads/...
    const baseUrl = api.defaults.baseURL.replace('/api/v1', '');
    return `${baseUrl}${prop.photo}`;
  }

  if (prop.type === 'Heladería') {
    // Return a modern cafe interior mimicking the image
    return 'https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&q=80&w=800'; 
  } else if (prop.type === 'Casa') {
    // Return a nice exterior mimicking the image
    return 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&q=80&w=800'; 
  } else {
    // Commercial/Warehouse
    return 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80&w=800'; 
  }
};
</script>
