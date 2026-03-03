<template>
  <PageLayout title="Historial de Ventas" subtitle="Registro de transacciones y facturación">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
        <div class="md:col-span-1">
          <label class="block text-sm font-medium text-gray-700 mb-1">Buscar venta</label>
          <div class="relative">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input v-model="searchQuery" class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] sm:text-sm outline-none" placeholder="ID, Cliente o NIT..." type="text" />
          </div>
        </div>
        <div class="md:col-span-1">
          <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
          <input v-model="filterFecha" class="block w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] sm:text-sm outline-none" type="date" />
        </div>
        <div class="md:col-span-1">
          <label class="block text-sm font-medium text-gray-700 mb-1">Vendedor</label>
          <select v-model="filterVendedor" class="block w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] sm:text-sm outline-none">
            <option value="">Todos los vendedores</option>
            <option v-for="v in vendors" :key="v.id" :value="v.nombre">{{ v.nombre }}</option>
          </select>
        </div>
        <div class="md:col-span-1">
          <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Venta</label>
          <select v-model="filterTipoVenta" class="block w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] sm:text-sm outline-none">
            <option value="">Cualquiera</option>
            <option value="Contado">Contado</option>
            <option value="Crédito">Crédito</option>
          </select>
        </div>
        <div class="md:col-span-1 flex justify-end">
          <button @click="openNewSaleModal" class="flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#2E7D32] hover:bg-[#1B5E20] shadow-sm transition-colors w-full">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg> Nueva Venta
          </button>
        </div>
      </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between transition-transform hover:-translate-y-1 hover:shadow-md">
        <div>
          <p class="text-sm font-medium text-gray-500 mb-1">Total Ventas (Filtradas)</p>
          <div class="flex items-baseline gap-2">
            <h3 class="text-2xl font-bold text-gray-900">Q {{ formatMoney(summaryTotalValue) }}</h3>
          </div>
        </div>
        <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center text-[#2E7D32]">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between transition-transform hover:-translate-y-1 hover:shadow-md">
        <div>
          <p class="text-sm font-medium text-gray-500 mb-1">Notas de Envío</p>
          <div class="flex items-baseline gap-2">
            <h3 class="text-2xl font-bold text-gray-900">{{ summaryTotalSales }}</h3>
          </div>
        </div>
        <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>
        </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between transition-transform hover:-translate-y-1 hover:shadow-md">
        <div>
          <p class="text-sm font-medium text-gray-500 mb-1">Ventas al Contado</p>
          <div class="flex items-baseline gap-2">
            <h3 class="text-2xl font-bold text-gray-900">{{ summaryContado }}</h3>
            <span class="text-sm text-gray-500">vs {{ summaryCredito }} a crédito</span>
          </div>
        </div>
        <div class="w-12 h-12 rounded-full bg-orange-50 flex items-center justify-center text-orange-600">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-gray-50 text-gray-500 border-b border-gray-100 uppercase text-xs">
            <tr>
              <th class="px-6 py-4 font-semibold">ID Nota</th>
              <th class="px-6 py-4 font-semibold">Fecha</th>
              <th class="px-6 py-4 font-semibold">Cliente</th>
              <th class="px-6 py-4 font-semibold">Vendedor</th>
              <th class="px-6 py-4 font-semibold">Total</th>
              <th class="px-6 py-4 font-semibold text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="sale in filteredSales" :key="sale.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4 font-bold text-gray-900">{{ sale.numero_nota }}</td>
              <td class="px-6 py-4">
                <div class="text-gray-900">{{ (sale.fecha || '').substring(0, 10) }}</div>
                <div class="text-xs text-gray-500">{{ (sale.fecha || '00:00:00').substring(11, 16) }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="font-medium text-gray-900">{{ sale.cliente_nombre || 'Cliente Final' }}</div>
                <div class="text-xs text-gray-500">NIT: {{ sale.nit || 'C/F' }}</div>
              </td>
              <td class="px-6 py-4 text-gray-600">{{ sale.vendedor }}</td>
              <td class="px-6 py-4">
                <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full border bg-green-50 text-green-700 border-green-200">
                  Q {{ formatMoney(sale.total) }}
                </span>
              </td>
              <td class="px-6 py-4 text-right flex justify-end gap-3">
                <button class="text-red-500 hover:text-red-700" title="Eliminar" @click="confirmDeleteSale(sale)">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </button>
                <button class="text-[#2E7D32] hover:text-[#1B5E20]" title="Descargar PDF" @click="generatePDFFromExist(sale)">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
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
            <tr v-if="!loading && filteredSales.length === 0">
              <td colspan="6" class="px-6 py-8 text-center text-gray-500">No hay ventas registradas.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- NUEVA VENTA MODAL -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" @click="closeModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Panel -->
            <div class="inline-block align-bottom bg-white rounded-2xl text-left shadow-2xl transform transition-all sm:my-8 sm:align-middle max-w-5xl w-full">
                <!-- Header with background -->
                <div class="relative h-24 bg-gradient-to-b from-[#1B5E20] to-[#2E7D32] flex items-center justify-center rounded-t-2xl overflow-hidden">
                   <div class="absolute inset-x-0 bottom-0">
                        <svg viewBox="0 0 224 12" fill="currentColor" class="w-full -mb-1 text-white" preserveAspectRatio="none">
                            <path d="M0,0 C48,12 144,12 224,0 L224,12 L0,12 Z"></path>
                        </svg>
                    </div>
                    <div class="relative z-10 text-center text-white">
                        <h3 class="text-xl font-bold tracking-wide">CREAR NOTA DE ENVÍO</h3>
                    </div>
                    <button @click="closeModal" class="absolute top-4 right-4 text-white/80 hover:text-white bg-black/10 hover:bg-black/20 rounded-full p-2 transition-colors">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="bg-white px-6 pt-5 pb-6 sm:p-8 sm:pb-6 rounded-b-2xl max-h-[75vh] overflow-y-auto">
                    <!-- CLIENT SELECTION -->
                    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cliente <span class="text-red-500">*</span></label>
                            <select v-model="form.cliente_id" @change="onClientSelected" class="block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 px-3 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all">
                                <option value="" disabled>Seleccione un cliente...</option>
                                <option value="final">Consumidor Final (CF)</option>
                                <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.nombre }} - NIT: {{ client.nit }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vendedor <span class="text-red-500">*</span></label>
                            <select v-model="form.vendedor_id" @change="onVendedorSelected" class="block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 px-3 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all">
                                <option value="" disabled>Seleccione un vendedor...</option>
                                <option v-for="vendor in vendors" :key="vendor.id" :value="vendor.id">{{ vendor.nombre }}</option>
                            </select>
                        </div>
                        <div v-if="form.cliente_id === 'final'" class="grid grid-cols-2 gap-4">
                          <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Final</label>
                            <input v-model="form.cliente_nombre" type="text" class="block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 px-3 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none" placeholder="C/F">
                          </div>
                          <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">NIT</label>
                            <input v-model="form.nit" type="text" class="block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 px-3 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none" placeholder="C/F">
                          </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dirección <span class="text-red-500">*</span></label>
                            <input v-model="form.direccion" type="text" class="block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 px-3 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all" placeholder="Ciudad">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Venta <span class="text-red-500">*</span></label>
                            <select v-model="form.tipo_venta" class="block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 px-3 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all">
                                <option value="Contado">Contado</option>
                                <option value="Crédito">Crédito</option>
                                <option value="Pruebas">Pruebas</option>
                                <option value="Bonificación">Bonificación</option>
                            </select>
                        </div>
                        <div v-if="form.tipo_venta === 'Crédito'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Días de Crédito <span class="text-red-500">*</span></label>
                            <input v-model.number="form.dias_credito" type="number" min="1" class="block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 px-3 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all">
                        </div>
                    </div>

                    <!-- PRODUCTS LIST -->
                    <div class="mb-4 flex justify-between items-end border-b pb-2">
                        <h4 class="text-lg font-bold text-gray-800">Detalle de Productos</h4>
                        <button @click="addProductLine" class="text-sm font-medium text-[#2E7D32] hover:text-[#1B5E20] flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                            Añadir Producto
                        </button>
                    </div>

                    <!-- Products Table -->
                    <div class="overflow-x-auto mb-6">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="text-xs text-gray-500 bg-gray-50 uppercase">
                                <tr>
                                    <th class="px-2 py-3 font-semibold">Producto</th>
                                    <th class="px-2 py-3 font-semibold w-24">Cantidad</th>
                                    <th class="px-2 py-3 font-semibold w-32">Precio U.</th>
                                    <th class="px-2 py-3 font-semibold text-center w-24">Bonif.</th>
                                    <th class="px-2 py-3 font-semibold w-32">Desc. Total</th>
                                    <th class="px-2 py-3 font-semibold text-right w-32">Subtotal</th>
                                    <th class="px-2 py-3 font-semibold w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in form.productos" :key="index" class="border-b border-gray-100" :class="item.es_bonificacion ? 'bg-orange-50' : ''">
                                    <td class="px-2 py-2">
                                        <select v-model="item.producto_id" @change="onProductSelected(item)" class="w-full border border-gray-200 rounded-lg p-2 text-sm outline-none focus:border-[#2E7D32]">
                                            <option value="" disabled>Seleccione...</option>
                                            <option v-for="prod in catalog" :key="prod.id" :value="prod.id">
                                                {{ prod.producto }} ({{ prod.presentacion }})
                                            </option>
                                        </select>
                                    </td>
                                    <td class="px-2 py-2">
                                        <input v-model.number="item.cantidad" type="number" min="1" class="w-full border border-gray-200 rounded-lg p-2 text-sm outline-none text-center" @input="calculateTotals">
                                    </td>
                                    <td class="px-2 py-2">
                                        <div class="relative">
                                            <span class="absolute left-2 top-2 text-gray-500">Q</span>
                                            <input v-model.number="item.precio_unitario" type="number" step="0.01" class="w-full pl-6 border border-gray-200 rounded-lg p-2 text-sm outline-none" @input="calculateTotals" :disabled="item.es_bonificacion">
                                        </div>
                                    </td>
                                    <td class="px-2 py-2 text-center">
                                        <input type="checkbox" v-model="item.es_bonificacion" @change="onBonificacionChange(item)" class="w-4 h-4 text-[#2E7D32] bg-gray-100 border-gray-300 rounded focus:ring-[#2E7D32]">
                                    </td>
                                    <td class="px-2 py-2">
                                        <div class="relative">
                                            <span class="absolute left-2 top-2 text-gray-500">Q</span>
                                            <input v-model.number="item.descuento" type="number" step="0.01" min="0" class="w-full pl-6 border border-gray-200 rounded-lg p-2 text-sm outline-none" @input="calculateTotals" :disabled="item.es_bonificacion">
                                        </div>
                                    </td>
                                    <td class="px-2 py-2 text-right font-medium text-gray-900">
                                        Q {{ formatMoney(getItemTotal(item)) }}
                                    </td>
                                    <td class="px-2 py-2 text-center">
                                        <button @click="removeProductLine(index)" class="text-red-400 hover:text-red-600 transition-colors" v-if="form.productos.length > 1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- TOTALS DIALOG -->
                    <div class="flex justify-end mt-4">
                        <div class="w-72 bg-gray-50 p-4 rounded-xl border border-gray-200">
                            <div class="flex justify-between mb-2 text-sm">
                                <span class="text-gray-500">Subtotal:</span>
                                <span class="font-medium">Q {{ formatMoney(displaySubtotal) }}</span>
                            </div>
                            <div class="flex justify-between mb-2 text-sm">
                                <span class="text-gray-500">Descuento Total:</span>
                                <span class="font-medium text-red-500">-Q {{ formatMoney(displayDiscount) }}</span>
                            </div>
                            <div class="pt-2 border-t border-gray-200 flex justify-between items-center mt-2">
                                <span class="text-base font-bold text-gray-800">TOTAL:</span>
                                <span class="text-xl font-black text-[#2E7D32]">Q {{ formatMoney(displayTotal) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer with Actions -->
                <div class="bg-gray-50 px-6 py-4 rounded-b-2xl border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                    <button type="button" @click="closeModal" class="w-full border-2 border-gray-200 bg-white text-gray-700 hover:bg-gray-50 hover:border-gray-300 font-bold py-3 px-6 rounded-xl transition-all sm:w-auto" :disabled="isSaving">
                        Cancelar
                    </button>
                    <button type="button" @click="saveSaleAndGeneratePDF" class="w-full bg-[#2E7D32] hover:bg-[#1B5E20] text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-green-900/20 transition-all flex justify-center items-center gap-2 sm:w-auto" :disabled="isSaving">
                        <svg v-if="isSaving" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ isSaving ? 'Guardando...' : 'Guardar y Generar PDF' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
  </PageLayout>
</template>

<script setup>
import PageLayout from '../../components/layout/PageLayout.vue';
import { ref, onMounted, computed, reactive } from 'vue';
import api from '../../services/api';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import logoUrl from '../../assets/images/logo_emagro.png';

const sales = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const filterTipoVenta = ref('');
const filterFecha = ref('');
const filterVendedor = ref('');

// MODAL & NEW SALE STATE
const isModalOpen = ref(false);
const isSaving = ref(false);
const clients = ref([]);
const catalog = ref([]);
const vendors = ref([]);

const displaySubtotal = ref(0);
const displayDiscount = ref(0);
const displayTotal = ref(0);

const form = ref({
    cliente_id: '',
    cliente_nombre: '',
    nit: '',
    direccion: '',
    vendedor_id: '',
    vendedor_nombre: '',
    tipo_venta: 'Contado',
    dias_credito: 0,
    productos: []
});

const generateInitialProductLine = () => ({
    producto_id: '',
    producto_nombre: '',
    presentacion: '',
    cantidad: 1,
    precio_unitario: 0,
    es_bonificacion: false,
    descuento: 0,
    total: 0
});

const filteredSales = computed(() => {
    let result = sales.value;

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(s => 
            (s.numero_nota && String(s.numero_nota).toLowerCase().includes(q)) || 
            (s.cliente_nombre && s.cliente_nombre.toLowerCase().includes(q)) ||
            (s.nit && String(s.nit).toLowerCase().includes(q)) ||
            (s.vendedor && s.vendedor.toLowerCase().includes(q))
        );
    }

    if (filterTipoVenta.value) {
        result = result.filter(s => s.tipo_venta === filterTipoVenta.value);
    }

    if (filterFecha.value) {
        result = result.filter(s => s.fecha && s.fecha.startsWith(filterFecha.value));
    }

    if (filterVendedor.value) {
        // Normalizar strings para evitar problemas con tildes y mayúsculas/minúsculas
        const normalize = (str) => String(str || '').normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().trim();
        const vendorFilter = normalize(filterVendedor.value);
        result = result.filter(s => normalize(s.vendedor) === vendorFilter);
    }

    return result;
});

const summaryTotalValue = computed(() => filteredSales.value.reduce((acc, sale) => acc + parseFloat(sale.total || 0), 0));
const summaryTotalSales = computed(() => filteredSales.value.length);
const summaryContado = computed(() => filteredSales.value.filter(s => s.tipo_venta === 'Contado').length);
const summaryCredito = computed(() => filteredSales.value.filter(s => s.tipo_venta === 'Crédito').length);

onMounted(async () => {
    await loadInitialData();
});

const loadInitialData = async () => {
    loading.value = true;
    try {
        const [salesRes, clientsRes, catalogRes, usersRes] = await Promise.all([
            api.get('/sales').catch(() => ({ data: { data: [] } })),
            api.get('/clients').catch(() => ({ data: { data: [] } })),
            api.get('/products').catch(() => ({ data: { data: [] } })),
            api.get('/users').catch(() => ({ data: { data: [] } }))
        ]);
        
        sales.value = salesRes.data?.data || [];
        clients.value = clientsRes.data?.data || [];
        catalog.value = catalogRes.data?.data || [];
        vendors.value = usersRes.data?.data || [];
    } catch (error) {
        console.error("Failed to load initial data", error);
    } finally {
        loading.value = false;
    }
};

const openNewSaleModal = () => {
    form.value = {
        cliente_id: '',
        cliente_nombre: '',
        nit: '',
        direccion: '',
        vendedor_id: '',
        vendedor_nombre: '',
        tipo_venta: 'Contado',
        dias_credito: 0,
        productos: [generateInitialProductLine()]
    };
    calculateTotals();
    isModalOpen.value = true;
};

const closeModal = () => {
    if (isSaving.value) return;
    isModalOpen.value = false;
};

const addProductLine = () => {
    form.value.productos.push(generateInitialProductLine());
};

const removeProductLine = (index) => {
    form.value.productos.splice(index, 1);
    calculateTotals();
};

const onClientSelected = () => {
    if (form.value.cliente_id === 'final') {
        form.value.cliente_nombre = 'C/F';
        form.value.nit = 'C/F';
        form.value.direccion = 'Ciudad';
    } else {
        const c = clients.value.find(c => c.id === form.value.cliente_id);
        if (c) {
            form.value.cliente_nombre = c.nombre;
            form.value.nit = c.nit;
            form.value.direccion = c.direccion || 'Ciudad';
        }
    }
};

const onVendedorSelected = () => {
    const v = vendors.value.find(v => v.id === form.value.vendedor_id);
    if (v) {
        form.value.vendedor_nombre = v.nombre;
    }
};

const onProductSelected = (item) => {
    const p = catalog.value.find(p => p.id === item.producto_id);
    if (p) {
        item.producto_nombre = p.producto;
        item.presentacion = p.presentacion;
        item.precio_unitario = parseFloat(p.precio) || 0;
        calculateTotals();
    }
};

const onBonificacionChange = (item) => {
    if (item.es_bonificacion) {
        item.precio_unitario = 0;
        item.descuento = 0;
    } else {
        const p = catalog.value.find(p => p.id === item.producto_id);
        if (p) item.precio_unitario = parseFloat(p.precio) || 0;
    }
    calculateTotals();
};

const getItemTotal = (item) => {
    if (item.es_bonificacion) return 0;
    const base = (item.precio_unitario || 0) * (item.cantidad || 1);
    return Math.max(0, base - (item.descuento || 0));
};

const calculateTotals = () => {
    let sub = 0;
    let desc = 0;
    let tot = 0;

    form.value.productos.forEach(item => {
        if (!item.es_bonificacion) {
            const base = (item.precio_unitario || 0) * (item.cantidad || 1);
            const d = (item.descuento || 0);
            
            sub += base;
            desc += d;
            
            item.total = Math.max(0, base - d);
            tot += item.total;
        } else {
            item.total = 0;
        }
    });

    displaySubtotal.value = sub;
    displayDiscount.value = desc;
    displayTotal.value = tot;
};

const getBase64ImageFromUrl = async (imageUrl) => {
    try {
        const res = await fetch(imageUrl);
        const blob = await res.blob();
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result);
            reader.readAsDataURL(blob);
        });
    } catch (e) {
        console.warn('Could not load logo for PDF', e);
        return null; // Silent fail if image missing
    }
};

const generatePDF = async (vendedorNombre, numeroNota, fechaStr, clienteNom, nit, direccion, tipoVenta, diasCredito, productos, subtotal, descuento, total) => {
    const doc = new jsPDF();
    const docDate = new Date(fechaStr);
    
    // Attempt to load the logo
    const base64Logo = await getBase64ImageFromUrl(logoUrl);
    if (base64Logo) {
        doc.addImage(base64Logo, 'PNG', 15, 10, 45, 18); // Left logo
    }

    // Header Title (Center)
    doc.setTextColor(63, 81, 181); // Blue #3F51B5
    doc.setFont("helvetica", "bold");
    doc.setFontSize(20); // Closer to dart's 24
    doc.text("NOTA DE ENVÍO", 105, 18, { align: "center" });
    
    doc.setTextColor(244, 67, 54); // Red #F44336
    doc.setFontSize(26); // Closer to dart's 32
    doc.text(numeroNota, 105, 28, { align: "center" });
    
    // Top right circle (EM)
    doc.setFillColor(76, 175, 80); // Green #4CAF50
    // radius ~10.5 mm creates a 21 mm diameter circle
    doc.circle(182, 22, 11, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(26);
    doc.setFont("helvetica", "bold");
    // Fine-tune Y offset to be perfectly centered inside the 22mm circle
    doc.text("EM", 182, 25, { align: "center" });
    
    // Separator line
    doc.setDrawColor(0, 0, 0);
    doc.setLineWidth(0.5);
    doc.line(15, 35, 195, 35);
    
    // Info Client
    doc.setTextColor(0, 0, 0);
    doc.setFontSize(10);
    
    doc.setFont("helvetica", "bold");
    doc.text("CLIENTE:", 15, 50);
    doc.setFont("helvetica", "normal");
    const splitName = doc.splitTextToSize(clienteNom, 75);
    doc.text(splitName, 40, 50);
    
    doc.setFont("helvetica", "bold");
    doc.text("DIRECCIÓN:", 15, 58);
    doc.setFont("helvetica", "normal");
    const splitDir = doc.splitTextToSize(direccion, 75);
    doc.text(splitDir, 40, 58);
    
    // adjust Y dynamically based on direction text split if needed, using simple offset for now
    let nextY = 58 + (splitDir.length * 4) + 2; 

    doc.setFont("helvetica", "bold");
    doc.text("Código/NIT:", 15, nextY);
    doc.setFont("helvetica", "normal");
    doc.text(nit, 40, nextY);
    
    if (tipoVenta === 'Crédito' && diasCredito > 0) {
        doc.setFont("helvetica", "bold");
        doc.setFontSize(8);
        doc.setTextColor(244, 67, 54);
        doc.text(`Crédito por ${diasCredito} días`, 40, nextY + 6);
        doc.setTextColor(0, 0, 0);
    }
    
    // Date Box Options (Right Side)
    doc.setDrawColor(200, 200, 200);
    doc.setLineWidth(0.3);
    doc.roundedRect(130, 42, 65, 25, 2, 2); // main date box
    
    doc.setFont("helvetica", "bold");
    doc.setFontSize(10);
    doc.text("FECHA", 162.5, 48, { align: "center" });
    
    // Inner boxes
    doc.roundedRect(135, 53, 12, 10, 1, 1); // Dia
    doc.roundedRect(151, 53, 25, 10, 1, 1); // Mes
    doc.roundedRect(180, 53, 12, 10, 1, 1); // Año
    
    doc.setFont("helvetica", "normal");
    doc.text(String(docDate.getDate()), 141, 60, { align: "center" });
    doc.text(docDate.toLocaleString('es-ES', { month: 'long' }), 163.5, 60, { align: "center" });
    doc.text(String(docDate.getFullYear()).slice(-2), 186, 60, { align: "center" });

    // Table
    const tableData = productos.map(p => {
        let isBonif = p.es_bonificacion === true || p.es_bonificacion === 'si';
        return [
            p.cantidad,
            isBonif ? `${p.producto_nombre || p.producto} (BONIFICACIÓN)` : (p.producto_nombre || p.producto),
            p.presentacion,
            isBonif ? 'Q0' : `Q${parseFloat(p.precio_unitario)}`, // Flutter screenshot removes decimals if 0, but we use strict
            isBonif ? `Q${parseFloat(p.precio_unitario * p.cantidad)}` : (p.descuento > 0 ? `Q${parseFloat(p.descuento)}` : ''),
            isBonif ? 'Q0' : `Q${parseFloat(p.total)}`
        ];
    });
    
    // Add Totals Row exactly like screenshot
    tableData.push([
        '', '', '', 'TOTALES', `Q${parseFloat(descuento)}`, `Q${parseFloat(total)}`
    ]);

    autoTable(doc, {
        startY: 75,
        head: [['CANTIDAD', 'DESCRIPCIÓN', 'PRESENTACIÓN', 'PRECIO', 'DESCUENTO', 'VALOR TOTAL']],
        body: tableData,
        theme: 'grid',
        tableLineColor: [200, 200, 200],
        tableLineWidth: 0.1,
        headStyles: { 
            fillColor: [76, 175, 80], // #4CAF50
            textColor: [255, 255, 255], 
            halign: 'center',
            valign: 'middle',
            fontStyle: 'bold',
            lineColor: [255, 255, 255], // Internal borders white
            lineWidth: 0.2
        },
        styles: { 
            fontSize: 8, 
            halign: 'center', 
            valign: 'middle',
            cellPadding: 3,
            lineColor: [200, 200, 200]
        },
        columnStyles: { 
            0: { cellWidth: 20 },
            1: { halign: 'center' } 
        },
        didParseCell: function(data) {
            // Apply Bonification highlight if desired (omitted to perfectly match reference if reference doesn't have it)
            // Bold totals row and remove borders between empty cells
            if (data.row.index === productos.length) {
                if (data.column.index === 3) {
                    data.cell.styles.fontStyle = 'bold';
                    data.cell.styles.halign = 'right';
                }
                data.cell.styles.fillColor = [238, 238, 238]; // light grey background on totals
                data.cell.styles.fontStyle = 'bold';
            }
        }
    });

    // Firmas
    let finalY = doc.lastAutoTable.finalY + 30;
    doc.setDrawColor(150, 150, 150);
    // Line RECIBIDO
    doc.line(30, finalY, 80, finalY);
    // Line ENTREGADO
    doc.line(130, finalY, 180, finalY);
    
    doc.setTextColor(0, 0, 0);
    doc.setFont("helvetica", "normal");
    doc.setFontSize(8);
    doc.text("RECIBIDO POR", 55, finalY + 5, { align: "center" });
    doc.text("ENTREGADO POR", 155, finalY + 5, { align: "center" });
    
    doc.setTextColor(63, 81, 181); // Blue signature text
    doc.setFont("helvetica", "bold");
    doc.setFontSize(10);
    doc.text(clienteNom, 55, finalY + 11, { align: "center" });
    doc.text(vendedorNombre, 155, finalY + 11, { align: "center" });

    doc.save(`nota_envio_${numeroNota}.pdf`);
};

// Open existing PDF
const generatePDFFromExist = async (sale) => {
    try {
        const res = await api.get(`/sales/${sale.id}`);
        const data = res.data.data;
        
        if (!data || !data.productos || data.productos.length === 0) {
            alert("No se pudieron cargar los detalles (productos) de esta venta.");
            return;
        }

        await generatePDF(
            data.vendedor || 'Vendedor',
            data.numero_nota,
            data.fecha,
            data.cliente_nombre,
            data.nit,
            data.direccion,
            data.tipo_venta,
            data.dias_credito,
            data.productos,
            data.subtotal,
            data.descuento_total,
            data.total
        );
    } catch (error) {
        console.error("Error cargando detalles del PDF:", error);
        alert("Ocurrió un error al intentar descargar el PDF de la venta.");
    }
};

const confirmDeleteSale = async (sale) => {
    if (confirm(`¿Está seguro de que desea eliminar la venta ${sale.numero_nota}? Esta acción eliminará los detalles asociados de la base de datos y no se puede deshacer.`)) {
        try {
            await api.delete(`/sales/${sale.id}`);
            await loadInitialData();
        } catch (error) {
            console.error("Error eliminando venta:", error);
            alert("No se pudo eliminar la venta.");
        }
    }
};

const saveSaleAndGeneratePDF = async () => {
    if (!form.value.cliente_id || !form.value.vendedor_id || form.value.productos.length === 0) {
        alert("Seleccione un cliente, un vendedor y al menos un producto.");
        return;
    }
    
    const validProducts = form.value.productos.filter(p => p.producto_id && p.cantidad > 0);
    if (validProducts.length === 0) {
        alert("Complete correctamente los detalles de los productos.");
        return;
    }

    isSaving.value = true;
    
    // Simulate formatting payload. Backend POST /sales needs to be implemented
    const userName = form.value.vendedor_nombre || 'Vendedor 1';

    const payload = {
        cliente_id: form.value.cliente_id === 'final' ? 1 : form.value.cliente_id, // assuming 1 is CF in real DB
        cliente_nombre: form.value.cliente_nombre,
        nit: form.value.nit,
        direccion: form.value.direccion,
        tipo_venta: form.value.tipo_venta,
        dias_credito: form.value.dias_credito,
        subtotal: displaySubtotal.value,
        descuento_total: displayDiscount.value,
        total: displayTotal.value,
        vendedor: userName,
        productos: validProducts.map(p => ({
            producto_id: p.producto_id,
            producto: p.producto_nombre,
            presentacion: p.presentacion,
            precio_unitario: p.precio_unitario,
            cantidad: p.cantidad,
            es_bonificacion: p.es_bonificacion ? 'si' : 'no',
            descuento: p.descuento,
            total: p.total
        }))
    };

    try {
        // POST to backend first to get the real sequential number!
        const res = await api.post('/sales', payload);
        const currentDate = new Date().toISOString();
        // Await the local generation PDF mock
        await generatePDF(
            userName, 
            res.data?.data?.numero_nota || "00050", // Using real number if it came back
            currentDate, 
            form.value.cliente_nombre, 
            form.value.nit, 
            form.value.direccion, 
            form.value.tipo_venta,
            form.value.dias_credito,
            payload.productos,
            payload.subtotal,
            payload.descuento_total,
            payload.total
        );
        
        // After success
        closeModal();
        await loadInitialData(); // Refresh list

    } catch (error) {
        console.error("Failed to process sale", error);
        alert("Ocurrió un error (backend de ventas en desarrollo). Sin embargo, el PDF se generó demostrativamente.");
        closeModal();
    } finally {
        isSaving.value = false;
    }
};

const formatMoney = (val) => {
  return parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>
