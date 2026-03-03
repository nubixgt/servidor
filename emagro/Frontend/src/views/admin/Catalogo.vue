<template>
  <PageLayout title="Catálogo de Productos" subtitle="Gestiona tu inventario agrícola, precios y existencias">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
      <div class="flex flex-col md:flex-row gap-4 justify-between items-center">
          <div class="relative w-full md:w-96">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              <input v-model="searchQuery" class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 bg-gray-50 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] transition-all sm:text-sm" placeholder="Buscar por nombre o presentación..." type="text" />
          </div>
          <div class="w-full md:w-48">
              <select v-model="filterStock" class="block w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] transition-all sm:text-sm">
                  <option value="todos">Todas las Existencias</option>
                  <option value="suficiente">Suficiente (> 50)</option>
                  <option value="riesgo">Bajo Riesgo (≤ 50)</option>
                  <option value="agotado">Agotados (0)</option>
              </select>
          </div>
          <div class="flex gap-3 w-full md:w-auto ml-auto">
              <button @click="openNewProductModal" class="flex-1 md:flex-none flex items-center justify-center gap-2 px-5 py-2.5 bg-[#2E7D32] hover:bg-[#1B5E20] text-white rounded-lg text-sm font-semibold transition-colors shadow-sm">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                  Nuevo Producto
              </button>
          </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col items-center justify-center text-center transition-transform hover:-translate-y-1 hover:shadow-md">
            <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            </div>
            <h4 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Total Productos</h4>
            <p class="text-3xl font-black text-gray-900 mt-1">{{ stats.total }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col items-center justify-center text-center transition-transform hover:-translate-y-1 hover:shadow-md">
            <div class="w-12 h-12 rounded-full bg-yellow-50 text-yellow-600 flex items-center justify-center mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            </div>
            <h4 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Bajo Riesgo (&le; 50)</h4>
            <p class="text-3xl font-black text-gray-900 mt-1">{{ stats.riesgo }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col items-center justify-center text-center transition-transform hover:-translate-y-1 hover:shadow-md">
            <div class="w-12 h-12 rounded-full bg-red-50 text-red-600 flex items-center justify-center mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <h4 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Sin Stock (Agotados)</h4>
            <p class="text-3xl font-black text-gray-900 mt-1">{{ stats.agotados }}</p>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-gray-50 text-gray-500 border-b border-gray-100">
            <tr>
              <th class="px-6 py-4 font-semibold w-20">ID</th>
              <th class="px-6 py-4 font-semibold">Producto</th>
              <th class="px-6 py-4 font-semibold">Presentación</th>
              <th class="px-6 py-4 font-semibold text-right">Precio (Q)</th>
              <th class="px-6 py-4 font-semibold text-center">Existencias</th>
              <th class="px-6 py-4 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
             <tr v-for="product in filteredProducts" :key="product.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 font-mono text-gray-500">{{ product.id }}</td>
                <td class="px-6 py-4 font-semibold text-gray-900">{{ product.producto }}</td>
                <td class="px-6 py-4 text-gray-600">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    {{ product.presentacion }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <span class="font-semibold text-[#2E7D32]">Q {{ formatMoney(product.precio) }}</span>
                </td>
                <td class="px-6 py-4 text-center">
                  <span :class="['px-2.5 py-1 rounded-full text-xs font-bold border', 
                    Number(product.cantidad) === 0 ? 'bg-red-100 text-red-800 border-red-200' : 
                    Number(product.cantidad) <= 50 ? 'bg-yellow-100 text-yellow-800 border-yellow-200' :
                    'bg-green-100 text-green-800 border-green-200']">
                    {{ product.cantidad }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right flex justify-end gap-2">
                  <button @click="openEditProductModal(product)" class="text-gray-400 hover:text-blue-600 p-1.5 rounded-full hover:bg-blue-50 transition-all" title="Editar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                  </button>
                  <button @click="confirmDelete(product)" class="text-gray-400 hover:text-red-600 p-1.5 rounded-full hover:bg-red-50 transition-all" title="Eliminar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                  </button>
                </td>
              </tr>
              
              <tr v-if="loading">
                <td colspan="6" class="px-6 py-12 text-center">
                  <div class="flex justify-center">
                    <svg class="animate-spin h-6 w-6 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                  </div>
                </td>
              </tr>
              <tr v-if="!loading && filteredProducts.length === 0">
                <td colspan="6" class="px-6 py-8 text-center text-gray-500">No hay productos registrados.</td>
              </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Formulario de Producto -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeModal"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Panel -->
            <div class="inline-block align-bottom bg-white rounded-2xl text-left shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <!-- Header -->
                <div class="relative h-32 bg-gradient-to-b from-[#1B5E20] to-[#2E7D32] flex items-center justify-center rounded-t-2xl overflow-hidden">
                   <div class="absolute inset-x-0 bottom-0">
                        <svg viewBox="0 0 224 12" fill="currentColor" class="w-full -mb-1 text-white" preserveAspectRatio="none">
                            <path d="M0,0 C48,12 144,12 224,0 L224,12 L0,12 Z"></path>
                        </svg>
                    </div>
                    <div class="text-center z-10 text-white">
                        <p class="text-green-100 text-xs font-bold tracking-widest uppercase mb-1">{{ isEditing ? 'Editar Información' : 'Nuevo Registro' }}</p>
                        <h3 class="text-2xl font-bold">{{ isEditing ? 'Editar Producto' : 'Crear Producto' }}</h3>
                    </div>
                    <button @click="closeModal" class="absolute top-4 right-4 text-white/70 hover:text-white bg-black/20 rounded-full p-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="px-6 py-6 pb-8">
                    <form @submit.prevent="saveProduct" class="space-y-4">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto <span class="text-red-500">*</span></label>
                            <input v-model="form.producto" type="text" required class="block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 px-4 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all" placeholder="Ej. EM1, Fertilizante Universal...">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Presentación <span class="text-red-500">*</span></label>
                            <input v-model="form.presentacion" type="text" required class="block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 px-4 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all" placeholder="Ej. 1 litro, Galón, Costal 100lb...">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Precio Unitario (Q) <span class="text-red-500">*</span></label>
                                <input v-model.number="form.precio" type="number" step="0.01" min="0" required class="block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 px-4 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all" placeholder="0.00">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Existencias / Stock <span class="text-red-500">*</span></label>
                                <input v-model.number="form.cantidad" type="number" min="0" required class="block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 px-4 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all" placeholder="0">
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="flex gap-4 pt-4 mt-4 border-t border-gray-100">
                            <button type="button" @click="closeModal" class="flex-1 py-3 px-4 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="isSaving" class="flex-1 py-3 px-4 rounded-xl text-sm font-medium text-white bg-[#2E7D32] hover:bg-[#1B5E20] shadow-md transition-colors flex justify-center items-center">
                                <svg v-if="isSaving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ isSaving ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Guardar Producto') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
  </PageLayout>
</template>

<script setup>
import PageLayout from '../../components/layout/PageLayout.vue';
import { ref, onMounted, computed } from 'vue';
import api from '../../services/api';

const products = ref([]);
const loading = ref(true);
const searchQuery = ref('');

const filterStock = ref('todos');

// Stats
const stats = computed(() => {
    const total = products.value.length;
    const riesgo = products.value.filter(p => Number(p.cantidad) <= 50 && Number(p.cantidad) > 0).length;
    const agotados = products.value.filter(p => Number(p.cantidad) <= 0).length;
    return { total, riesgo, agotados };
});

const filteredProducts = computed(() => {
    let list = products.value;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(p => 
            (p.producto && p.producto.toLowerCase().includes(q)) || 
            (p.presentacion && p.presentacion.toLowerCase().includes(q))
        );
    }
    
    if (filterStock.value === 'suficiente') {
        list = list.filter(p => Number(p.cantidad) > 50);
    } else if (filterStock.value === 'riesgo') {
        list = list.filter(p => Number(p.cantidad) <= 50 && Number(p.cantidad) > 0);
    } else if (filterStock.value === 'agotado') {
        list = list.filter(p => Number(p.cantidad) <= 0);
    }

    return list;
});

const loadProducts = async () => {
    loading.value = true;
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
};

onMounted(() => {
    loadProducts();
});

// === MODAL STATE ===
const isModalOpen = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const currentProductId = ref(null);

const form = ref({
    producto: '',
    presentacion: '',
    precio: '',
    cantidad: 0
});

const openNewProductModal = () => {
    isEditing.value = false;
    currentProductId.value = null;
    form.value = {
        producto: '',
        presentacion: '',
        precio: '',
        cantidad: 0
    };
    isModalOpen.value = true;
};

const openEditProductModal = (product) => {
    isEditing.value = true;
    currentProductId.value = product.id;
    form.value = {
        producto: product.producto,
        presentacion: product.presentacion,
        precio: parseFloat(product.precio),
        cantidad: parseInt(product.cantidad)
    };
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};

const saveProduct = async () => {
    if (!form.value.producto || !form.value.presentacion || form.value.precio === '') {
        alert("Por favor, complete todos los campos obligatorios.");
        return;
    }

    isSaving.value = true;
    const payload = {
        producto: form.value.producto,
        presentacion: form.value.presentacion,
        precio: parseFloat(form.value.precio),
        cantidad: parseInt(form.value.cantidad) || 0
    };

    try {
        if (isEditing.value) {
            const res = await api.put(`/products/${currentProductId.value}`, payload);
            if (res.data.status === 'success') {
                closeModal();
                await loadProducts();
            } else {
                alert(res.data.message || "Error al actualizar producto");
            }
        } else {
            const res = await api.post('/products', payload);
            if (res.data.status === 'success') {
                closeModal();
                await loadProducts();
            } else {
                alert(res.data.message || "Error al crear producto");
            }
        }
    } catch (error) {
        console.error("Failed to save product", error);
        alert(error.response?.data?.message || "Ocurrió un error al guardar el producto.");
    } finally {
        isSaving.value = false;
    }
};

const confirmDelete = async (product) => {
    if (confirm(`¿Está seguro de que desea eliminar permanentemente el producto '${product.producto}' (${product.presentacion})?`)) {
        try {
            await api.delete(`/products/${product.id}`);
            await loadProducts();
        } catch (error) {
            console.error("Error eliminando producto:", error);
            alert("No se pudo eliminar el producto.");
        }
    }
};

const formatMoney = (val) => {
  return parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>
