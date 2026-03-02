<template>
  <PageLayout title="Catálogo de Productos" subtitle="Gestiona tu inventario agrícola, precios y existencias">
    <div class="flex flex-col md:flex-row gap-4 justify-between items-center mb-6">
      <div class="relative w-full md:w-96 flex">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          <input class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl bg-white text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-[#2E7D32]/50 outline-none sm:text-sm shadow-sm" placeholder="Buscar por nombre, categoría o SKU" type="text" />
      </div>
      <div class="flex gap-2 overflow-x-auto w-full md:w-auto pb-2 md:pb-0 no-scrollbar">
          <button class="px-4 py-1.5 rounded-full bg-gray-900 text-white text-sm font-medium whitespace-nowrap">Todos</button>
          <button class="px-4 py-1.5 rounded-full bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors text-sm font-medium whitespace-nowrap">Fertilizantes</button>
          <button class="px-4 py-1.5 rounded-full bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors text-sm font-medium whitespace-nowrap">Semillas</button>
          <button class="px-4 py-1.5 rounded-full bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors text-sm font-medium whitespace-nowrap">Herramientas</button>
      </div>
      <button class="hidden md:flex bg-[#2E7D32] hover:bg-[#1B5E20] text-white font-medium py-2.5 px-5 rounded-full shadow-sm transition-all items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          <span>Nuevo Producto</span>
      </button>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <svg class="animate-spin h-8 w-8 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <div v-for="product in products" :key="product.id" class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-shadow duration-200 group flex flex-col overflow-hidden border border-gray-100">
          <div class="relative h-48 bg-gray-100 overflow-hidden">
              <span :class="['absolute top-3 right-3 text-xs font-bold px-2.5 py-1 rounded-full z-10', product.stock === 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800']">
                  {{ product.stock === 0 ? 'Agotado' : 'En Stock' }}
              </span>
              <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" :src="product.image_url || `https://picsum.photos/seed/${product.sku}/400/300`" alt="Product" />
          </div>
          <div class="p-4 flex flex-col flex-1">
              <div class="mb-1 text-xs text-gray-500 uppercase tracking-wider font-semibold">{{ product.category_name }}</div>
              <h3 class="text-lg font-bold text-gray-900 mb-1">{{ product.name }}</h3>
              <p class="text-sm text-gray-500 mb-4 line-clamp-2">{{ product.description || 'Producto agrícola para mostrar en el catálogo.' }}</p>
              <div class="mt-auto flex items-center justify-between">
                  <span class="text-xl font-bold text-gray-900">Q {{ formatMoney(product.price) }}</span>
                  <button class="h-8 w-8 rounded-full bg-gray-100 hover:bg-[#2E7D32] hover:text-white flex items-center justify-center text-gray-700 transition-colors">
                      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                  </button>
              </div>
          </div>
      </div>
      <div v-if="products.length === 0" class="col-span-full text-center py-12 text-gray-500">
        No hay productos registrados.
      </div>
    </div>
  </PageLayout>
</template>

<script setup>
import PageLayout from '../../components/layout/PageLayout.vue';
import { ref, onMounted } from 'vue';
import api from '../../services/api';

const products = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const res = await api.get('/products');
        if (res.data.status === 'success') {
            products.value = res.data.data;
        }
    } catch (error) {
        console.error("Failed to load products", error);
    } finally {
        loading.value = false;
    }
});

const formatMoney = (val) => {
  return parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>
