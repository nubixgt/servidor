<template>
  <div class="space-y-6 max-w-7xl mx-auto pb-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-[28px] font-sans font-bold text-[#221f47] tracking-tight">Propiedades</h1>
        <p class="text-sm text-gray-400 mt-1">Gestiona todas tus propiedades</p>
      </div>
      <button class="flex items-center gap-2 px-5 py-2.5 bg-[#221f47] text-white rounded-xl text-sm font-medium hover:bg-[#343063] transition-colors shadow-sm">
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
          <option value="Bodega">Bodega</option>
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
            <button class="flex-1 flex items-center justify-center gap-2 py-2 px-4 rounded-[10px] border border-gray-200 text-sm font-semibold text-[#1f1f2c] hover:bg-gray-50 transition-colors">
              <PencilIcon class="w-4 h-4" />
              Editar
            </button>
            <button class="flex items-center justify-center w-10 h-10 rounded-[10px] border border-red-100 text-red-500 hover:bg-red-50 transition-colors shrink-0">
              <TrashIcon class="w-[18px] h-[18px]" />
            </button>
          </div>
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
  ChevronDownIcon
} from '@heroicons/vue/24/outline';
import api from '../../services/api';

const allProperties = ref([]);
const searchQuery = ref('');
const selectedType = ref('Todos');

onMounted(async () => {
  try {
    const res = await api.get('/locations');
    allProperties.value = res.data.data.map(item => ({
      ...item,
      id: item.id,
      name: item.name,
      type: item.type,
      address: item.location,
      rent: item.monthly_rent_amount ? Number(item.monthly_rent_amount).toLocaleString() : null,
    }));
  } catch (error) {
    console.error(error);
  }
});

const filteredProperties = computed(() => {
  return allProperties.value.filter(prop => {
    const matchesSearch = prop.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                          prop.address.toLowerCase().includes(searchQuery.value.toLowerCase());
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
    case 'Bodega':
    default:
      return 'bg-[#E1F5FE] text-[#0277BD]';
  }
};

const getImageUrl = (prop) => {
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
