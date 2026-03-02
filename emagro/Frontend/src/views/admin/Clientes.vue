<template>
  <PageLayout title="Directorio de Clientes" subtitle="Administra la información de tus clientes agrícolas">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
      <div class="flex flex-col md:flex-row gap-4 justify-between items-center">
          <div class="relative w-full md:w-96">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              <input v-model="searchQuery" class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 bg-gray-50 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] transition-all sm:text-sm" placeholder="Buscar cliente por nombre o NIT..." type="text" />
          </div>
          <div class="flex gap-3 w-full md:w-auto">
              <button @click="showFilters = !showFilters" :class="showFilters ? 'bg-green-100 border-[#2E7D32]' : 'bg-green-50 border-green-200'" class="flex-1 md:flex-none flex items-center justify-center gap-2 px-5 py-2.5 text-[#2E7D32] hover:bg-green-100 rounded-lg text-sm font-semibold transition-colors border">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                  Filtros
              </button>
              <button @click="openNewClientModal" class="flex-1 md:flex-none flex items-center justify-center gap-2 px-5 py-2.5 bg-[#2E7D32] hover:bg-[#1B5E20] text-white rounded-lg text-sm font-semibold transition-colors shadow-sm">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                  Nuevo Cliente
              </button>
          </div>
      </div>

      <!-- Advanced Filters Panel -->
      <div v-show="showFilters" class="mt-5 pt-5 border-t border-gray-100 grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
              <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Estado</label>
              <select v-model="filters.estado" class="block w-full border border-gray-200 rounded-lg bg-gray-50 py-2.5 px-3 text-gray-700 focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] sm:text-sm outline-none transition-all appearance-none cursor-pointer">
                  <option value="todos">Todos los Estados</option>
                  <option value="activos">Solo Activos</option>
                  <option value="bloqueados">Solo Bloqueados</option>
              </select>
          </div>
          <div>
              <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Departamento</label>
              <select v-model="filters.departamento" @change="onFilterDepartamentoChange" class="block w-full border border-gray-200 rounded-lg bg-gray-50 py-2.5 px-3 text-gray-700 focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] sm:text-sm outline-none transition-all appearance-none cursor-pointer">
                  <option value="">Todos los Departamentos</option>
                  <option v-for="dep in departamentos" :key="dep" :value="dep">{{ dep }}</option>
              </select>
          </div>
          <div>
              <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Municipio</label>
              <select v-model="filters.municipio" class="block w-full border border-gray-200 rounded-lg bg-gray-50 py-2.5 px-3 text-gray-700 focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] sm:text-sm outline-none transition-all appearance-none cursor-pointer" :disabled="!filters.departamento">
                  <option value="">Todos los Municipios</option>
                  <option v-for="mun in filterMunicipiosDisponibles" :key="mun" :value="mun">{{ mun }}</option>
              </select>
          </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col items-center justify-center text-center transition-transform hover:-translate-y-1 hover:shadow-md">
            <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <h4 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Total Clientes</h4>
            <p class="text-3xl font-black text-gray-900 mt-1">{{ stats.total }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col items-center justify-center text-center transition-transform hover:-translate-y-1 hover:shadow-md">
            <div class="w-12 h-12 rounded-full bg-green-50 text-green-600 flex items-center justify-center mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <h4 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Clientes Activos</h4>
            <p class="text-3xl font-black text-gray-900 mt-1">{{ stats.activos }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col items-center justify-center text-center transition-transform hover:-translate-y-1 hover:shadow-md">
            <div class="w-12 h-12 rounded-full bg-red-50 text-red-600 flex items-center justify-center mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
            </div>
            <h4 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Clientes Bloqueados</h4>
            <p class="text-3xl font-black text-gray-900 mt-1">{{ stats.bloqueados }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-gray-50 text-gray-500 border-b border-gray-100">
            <tr>
              <th class="px-6 py-4 font-semibold w-24">ID</th>
              <th class="px-6 py-4 font-semibold">Cliente</th>
              <th class="px-6 py-4 font-semibold">Teléfono</th>
              <th class="px-6 py-4 font-semibold">NIT</th>
              <th class="px-6 py-4 font-semibold text-right">Dirección</th>
              <th class="px-6 py-4 font-semibold text-center">Estado</th>
              <th class="px-6 py-4"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
             <tr v-for="client in filteredClients" :key="client.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 font-mono text-gray-500">{{ client.id }}</td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-200 overflow-hidden shrink-0">
                      <img :src="`https://ui-avatars.com/api/?name=${client.nombre}&background=random`" alt="Client" class="w-full h-full object-cover" />
                    </div>
                    <div>
                      <div class="font-semibold text-gray-900">{{ client.nombre }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-gray-600">{{ client.telefono || 'N/A' }}</td>
                <td class="px-6 py-4 text-gray-600">{{ client.nit || 'C/F' }}</td>
                <td class="px-6 py-4 text-right text-gray-600 max-w-xs truncate" :title="client.direccion">{{ client.direccion || 'Sin dirección' }}</td>
                <td class="px-6 py-4 text-center">
                  <span :class="['px-2.5 py-1 rounded-full text-xs font-medium border', 
                    client.bloquear_ventas === 'no' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200']">
                    {{ client.bloquear_ventas === 'no' ? 'Activo' : 'Bloqueado' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <button @click="openEditClientModal(client)" class="text-gray-400 hover:text-[#2E7D32] p-1 rounded-full hover:bg-green-50 transition-all mr-2" title="Editar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                  </button>
                </td>
              </tr>
              
              <tr v-if="loading">
                <td colspan="7" class="px-6 py-12 text-center">
                  <div class="flex justify-center">
                    <svg class="animate-spin h-6 w-6 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                  </div>
                </td>
              </tr>
              <tr v-if="!loading && filteredClients.length === 0">
                <td colspan="7" class="px-6 py-8 text-center text-gray-500">No hay clientes registrados.</td>
              </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Formulario de Cliente -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeModal"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Panel -->
            <div class="inline-block align-bottom bg-white rounded-2xl text-left shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl w-full">
                <!-- Header with background -->
                <div class="relative h-32 bg-gradient-to-b from-[#1B5E20] to-[#2E7D32] flex items-center justify-center rounded-t-2xl overflow-hidden">
                   <div class="absolute inset-x-0 bottom-0">
                        <svg viewBox="0 0 224 12" fill="currentColor" class="w-full -mb-1 text-white" preserveAspectRatio="none">
                            <path d="M0,0 C48,12 144,12 224,0 L224,12 L0,12 Z"></path>
                        </svg>
                    </div>
                    <div class="text-center z-10 text-white">
                        <p class="text-green-100 text-xs font-bold tracking-widest uppercase mb-1">{{ isEditing ? 'Editar Información' : 'Nuevo Registro' }}</p>
                        <h3 class="text-2xl font-bold">{{ isEditing ? 'Cliente Existente' : 'Registrar Cliente' }}</h3>
                    </div>
                    <button @click="closeModal" class="absolute top-4 right-4 text-white/70 hover:text-white bg-black/20 rounded-full p-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="px-6 py-6 pb-8">
                    <form @submit.prevent="saveClient" class="space-y-4">
                        
                        <!-- Nombre y NIT -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    </div>
                                    <input v-model="form.nombre" type="text" required class="pl-10 block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all" placeholder="Ej. Juan Pérez">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">NIT <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" /></svg>
                                    </div>
                                    <input v-model="form.nit" type="text" required class="pl-10 block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all" placeholder="C/F o número">
                                </div>
                            </div>
                        </div>

                        <!-- Teléfono e Email -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    </div>
                                    <input v-model="form.telefono" @input="formatTelefono" type="tel" maxlength="9" required class="pl-10 block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all tracking-widest font-mono" placeholder="XXXX-XXXX">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email (Opcional)</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    </div>
                                    <input v-model="form.email" type="email" class="pl-10 block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all" placeholder="correo@ejemplo.com">
                                </div>
                            </div>
                        </div>

                        <!-- Departamento y Municipio -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Departamento</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                                    </div>
                                    <select v-model="form.departamento" @change="onDepartamentoChange" class="pl-10 block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all appearance-none cursor-pointer">
                                        <option value="" disabled>Seleccione...</option>
                                        <option v-for="dep in departamentos" :key="dep" :value="dep">{{ dep }}</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Municipio</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    </div>
                                    <select v-model="form.municipio" class="pl-10 block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all appearance-none cursor-pointer" :disabled="!form.departamento">
                                        <option value="" disabled>Seleccione...</option>
                                        <option v-for="mun in municipiosDisponibles" :key="mun" :value="mun">{{ mun }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Dirección -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dirección Exacta</label>
                            <div class="relative">
                                <div class="absolute top-3 left-3 pointer-events-none">
                                    <svg class="h-5 w-5 text-[#2E7D32]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                                </div>
                                <textarea v-model="form.direccion" rows="2" class="pl-10 block w-full border border-gray-200 rounded-xl bg-gray-50 py-3 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/30 focus:border-[#2E7D32] sm:text-sm outline-none transition-all" placeholder="Ej. 1ra Avenida 2-34 Zona 1"></textarea>
                            </div>
                        </div>

                        <div v-if="isEditing" class="pt-2">
                             <div class="p-4 bg-orange-50 rounded-xl border border-orange-200 flex items-center justify-between">
                                 <div>
                                     <h4 class="text-sm font-bold text-orange-900">Bloquear Ventas</h4>
                                     <p class="text-xs text-orange-700">El cliente no podrá registrar nuevas compras</p>
                                 </div>
                                 <label class="relative inline-flex items-center cursor-pointer">
                                  <input type="checkbox" v-model="form.bloquear_ventas_bool" class="sr-only peer">
                                  <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-500"></div>
                                </label>
                             </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="flex gap-4 pt-4 mt-2 border-t border-gray-100">
                            <button type="button" @click="closeModal" class="flex-1 py-3 px-4 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="isSaving" class="flex-1 py-3 px-4 rounded-xl text-sm font-medium text-white bg-[#2E7D32] hover:bg-[#1B5E20] shadow-md transition-colors flex justify-center items-center">
                                <svg v-if="isSaving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ isSaving ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Guardar Cliente') }}
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
import { ref, onMounted, computed, reactive } from 'vue';
import api from '../../services/api';

const clients = ref([]);
const loading = ref(true);
const searchQuery = ref('');

const showFilters = ref(false);
const filters = ref({
    estado: 'todos',
    departamento: '',
    municipio: ''
});

// === MOCK GUATEMALA DATA ===
const guatemalaData = {
    'Guatemala': ['Guatemala', 'Santa Catarina Pinula', 'San José Pinula', 'San José del Golfo', 'Palencia', 'Chinautla', 'San Pedro Ayampuc', 'Mixco', 'San Pedro Sacatepéquez', 'San Juan Sacatepéquez', 'San Raymundo', 'Chuarrancho', 'Fraijanes', 'Amatitlán', 'Villa Nueva', 'Villa Canales', 'San Miguel Petapa'],
    'Alta Verapaz': ['Cobán', 'Santa Cruz Verapaz', 'San Cristóbal Verapaz', 'Tactic', 'Tamahú', 'Tucurú', 'Panzós', 'Senahú', 'San Pedro Carchá', 'San Juan Chamelco', 'Lanquín', 'Cahabón', 'Chisec', 'Chahal', 'Fray Bartolomé de las Casas', 'Santa Catalina La Tinta', 'Raxruhá'],
    'Baja Verapaz': ['Salamá', 'San Miguel Chicaj', 'Rabinal', 'Cubulco', 'Granados', 'Santa Cruz El Chol', 'San Jerónimo', 'Purulhá'],
    'Chimaltenango': ['Chimaltenango', 'San José Poaquil', 'San Martín Jilotepeque', 'San Juan Comalapa', 'Santa Apolonia', 'Tecpán Guatemala', 'Patzún', 'Pochuta', 'Patzicía', 'Santa Cruz Balanyá', 'Acatenango', 'Yepocapa', 'San Andrés Itzapa', 'Parramos', 'Zaragoza', 'El Tejar'],
    'Chiquimula': ['Chiquimula', 'San José La Arada', 'San Juan Ermita', 'Jocotán', 'Camotán', 'Olopa', 'Esquipulas', 'Concepción Las Minas', 'Quezaltepeque', 'San Jacinto', 'Ipala'],
    'El Progreso': ['Guastatoya', 'Morazán', 'San Agustín Acasaguastlán', 'San Cristóbal Acasaguastlán', 'El Jícaro', 'Sansare', 'Sanarate', 'San Antonio La Paz'],
    'Escuintla': ['Escuintla', 'Santa Lucía Cotzumalguapa', 'La Democracia', 'Siquinalá', 'Masagua', 'Tiquisate', 'La Gomera', 'Guanagazapa', 'San José', 'Iztapa', 'Palín', 'San Vicente Pacaya', 'Nueva Concepción', 'Puerto de San José'],
    'Huehuetenango': ['Huehuetenango', 'Chiantla', 'Malacatancito', 'Cuilco', 'Nentón', 'San Pedro Necta', 'Jacaltenango', 'Soloma', 'Ixtahuacán', 'Santa Bárbara', 'La Libertad', 'La Democracia', 'San Miguel Acatán', 'San Rafael La Independencia', 'Todos Santos Cuchumatán', 'San Juan Atitán', 'Santa Eulalia', 'San Mateo Ixtatán', 'Colotenango', 'San Sebastián Huehuetenango', 'Tectitán', 'Concepción Huista', 'San Juan Ixcoy', 'San Antonio Huista', 'Santa Cruz Barillas', 'San Sebastián Coatán', 'Aguacatán', 'San Rafael Petzal', 'San Gaspar Ixchil', 'Santiago Chimaltenango', 'Santa Ana Huista', 'Unión Cantinil', 'Petatán'],
    'Izabal': ['Puerto Barrios', 'Livingston', 'El Estor', 'Morales', 'Los Amates'],
    'Jalapa': ['Jalapa', 'San Pedro Pinula', 'San Luis Jilotepeque', 'San Manuel Chaparrón', 'San Carlos Alzatate', 'Monjas', 'Mataquescuintla'],
    'Jutiapa': ['Jutiapa', 'El Progreso', 'Santa Catarina Mita', 'Agua Blanca', 'Asunción Mita', 'Yupiltepeque', 'Atescatempa', 'Jerez', 'El Adelanto', 'Zapotitlán', 'Comapa', 'Jalpatagua', 'Conguaco', 'Moyuta', 'Pasaco', 'San José Acatempa', 'Quesada'],
    'Petén': ['Flores', 'San José', 'San Benito', 'San Andrés', 'La Libertad', 'San Francisco', 'Santa Ana', 'Dolores', 'San Luis', 'Sayaxché', 'Melchor de Mencos', 'Poptún', 'Las Cruces', 'El Chal'],
    'Quetzaltenango': ['Quetzaltenango', 'Salcajá', 'Olintepeque', 'San Carlos Sija', 'Sibilia', 'Cabricán', 'Cajolá', 'San Miguel Sigüilá', 'San Juan Ostuncalco', 'San Mateo', 'Concepción Chiquirichapa', 'San Martín Sacatepéquez', 'Almolonga', 'Cantel', 'Huitán', 'Zunil', 'Colomba Costa Cuca', 'San Francisco La Unión', 'El Palmar', 'Coatepeque', 'Génova', 'Flores Costa Cuca', 'La Esperanza', 'Palestina de Los Altos'],
    'Quiché': ['Santa Cruz del Quiché', 'Chiché', 'Chinique', 'Zacualpa', 'Chajul', 'Santo Tomás Chichicastenango', 'Patzité', 'San Antonio Ilotenango', 'San Pedro Jocopilas', 'Cunén', 'San Juan Cotzal', 'Joyabaj', 'Nebaj', 'San Andrés Sajcabajá', 'San Miguel Uspantán', 'Sacapulas', 'San Bartolomé Jocotenango', 'Canillá', 'Chicamán', 'Ixcán', 'Pachalum'],
    'Retalhuleu': ['Retalhuleu', 'San Sebastián', 'Santa Cruz Muluá', 'San Martín Zapotitlán', 'San Felipe', 'San Andrés Villa Seca', 'Champerico', 'Nuevo San Carlos', 'El Asintal'],
    'Sacatepéquez': ['Antigua Guatemala', 'Jocotenango', 'Pastores', 'Sumpango', 'Santo Domingo Xenacoj', 'Santiago Sacatepéquez', 'San Bartolomé Milpas Altas', 'San Lucas Sacatepéquez', 'Santa Lucía Milpas Altas', 'Magdalena Milpas Altas', 'Santa María de Jesús', 'Ciudad Vieja', 'San Miguel Dueñas', 'Alotenango', 'San Antonio Aguas Calientes', 'Santa Catarina Barahona'],
    'San Marcos': ['San Marcos', 'San Pedro Sacatepéquez', 'San Antonio Sacatepéquez', 'Comitancillo', 'San Miguel Ixtahuacán', 'Concepción Tutuapa', 'Tacaná', 'Sibinal', 'Tajumulco', 'Tejutla', 'San Rafael Pie de La Cuesta', 'Nuevo Progreso', 'El Tumbador', 'El Rodeo', 'Malacatán', 'Catarina', 'Ayutla', 'Ocós', 'San Pablo', 'El Quetzal', 'La Reforma', 'Pajapita', 'Ixchiguán', 'San José Ojetenam', 'San Cristóbal Cucho', 'Sipacapa', 'Esquipulas Palo Gordo', 'Río Blanco', 'San Lorenzo'],
    'Santa Rosa': ['Cuilapa', 'Barberena', 'Santa Rosa de Lima', 'Casillas', 'San Rafael Las Flores', 'Oratorio', 'San Juan Tecuaco', 'Chiquimulilla', 'Taxisco', 'Santa María Ixhuatán', 'Guazacapán', 'Santa Cruz Naranjo', 'Pueblo Nuevo Viñas', 'Nueva Santa Rosa'],
    'Sololá': ['Sololá', 'San José Chacayá', 'Santa María Visitación', 'Santa Lucía Utatlán', 'Nahualá', 'Santa Catarina Ixtahuacán', 'Santa Clara La Laguna', 'Concepción', 'San Andrés Semetabaj', 'Panajachel', 'Santa Catarina Palopó', 'San Antonio Palopó', 'San Lucas Tolimán', 'Santa Cruz La Laguna', 'San Pablo La Laguna', 'San Marcos La Laguna', 'San Juan La Laguna', 'San Pedro La Laguna', 'Santiago Atitlán'],
    'Suchitepéquez': ['Mazatenango', 'Cuyotenango', 'San Francisco Zapotitlán', 'San Bernardino', 'San José El Ídolo', 'Santo Domingo Suchitepéquez', 'San Lorenzo', 'Samayac', 'San Pablo Jocopilas', 'San Antonio Suchitepéquez', 'San Miguel Panán', 'San Gabriel', 'Chicacao', 'Patulul', 'Santa Bárbara', 'San Juan Bautista', 'Santo Tomás La Unión', 'Zunilito', 'Pueblo Nuevo', 'Río Bravo'],
    'Totonicapán': ['Totonicapán', 'San Cristóbal Totonicapán', 'San Francisco El Alto', 'San Andrés Xecul', 'Momostenango', 'Santa María Chiquimula', 'Santa Lucía La Reforma', 'San Bartolo'],
    'Zacapa': ['Zacapa', 'Estanzuela', 'Río Hondo', 'Gualán', 'Teculután', 'Usumatlán', 'Cabañas', 'San Diego', 'La Unión', 'Huité', 'San Jorge']
};
const departamentos = Object.keys(guatemalaData).sort();

// === STATS ===
const stats = computed(() => {
    const total = clients.value.length;
    const activos = clients.value.filter(c => c.bloquear_ventas === 'no' || c.bloquear_ventas === null).length;
    const bloqueados = clients.value.filter(c => c.bloquear_ventas === 'si').length;
    return { total, activos, bloqueados };
});

// === MODAL STATE ===
const isModalOpen = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const currentClientId = ref(null);

const form = ref({
    nombre: '',
    nit: '',
    telefono: '',
    departamento: '',
    municipio: '',
    direccion: '',
    email: '',
    bloquear_ventas_bool: false // Used for checkbox
});

const municipiosDisponibles = computed(() => {
    return form.value.departamento ? guatemalaData[form.value.departamento] || [] : [];
});

const onDepartamentoChange = () => {
    form.value.municipio = '';
};

const formatTelefono = (e) => {
    let raw = e.target.value.replace(/\D/g, ''); // Remove all non-digits
    
    // Limit to 8 digits max
    if (raw.length > 8) {
        raw = raw.slice(0, 8);
    }
    
    // Add the dash automatically after 4 digits
    if (raw.length > 4) {
        form.value.telefono = `${raw.slice(0, 4)}-${raw.slice(4)}`;
    } else {
        form.value.telefono = raw;
    }
};

// --- Filtros Panel Logic ---
const filterMunicipiosDisponibles = computed(() => {
    return filters.value.departamento ? guatemalaData[filters.value.departamento] || [] : [];
});

const onFilterDepartamentoChange = () => {
    filters.value.municipio = '';
};

const filteredClients = computed(() => {
    let result = clients.value;
    
    // 1. Text Search Filter
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(c => 
            (c.nombre && c.nombre.toLowerCase().includes(q)) || 
            (c.nit && c.nit.toLowerCase().includes(q)) ||
            (c.telefono && String(c.telefono).toLowerCase().includes(q))
        );
    }
    
    // 2. Status Filter
    if (filters.value.estado === 'activos') {
        result = result.filter(c => c.bloquear_ventas === 'no' || c.bloquear_ventas === null);
    } else if (filters.value.estado === 'bloqueados') {
        result = result.filter(c => c.bloquear_ventas === 'si');
    }
    
    // 3. Department Filter
    if (filters.value.departamento) {
        result = result.filter(c => c.departamento === filters.value.departamento);
    }

    // 4. Municipality Filter
    if (filters.value.departamento && filters.value.municipio) {
        result = result.filter(c => c.municipio === filters.value.municipio);
    }

    return result;
});

const loadClients = async () => {
    loading.value = true;
    try {
        const res = await api.get('/clients');
        if (res.data.status === 'success') {
            clients.value = res.data.data;
        }
    } catch (error) {
        console.error("Failed to load clients", error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadClients();
});

// === MODAL ACTIONS ===
const openNewClientModal = () => {
    isEditing.value = false;
    currentClientId.value = null;
    form.value = {
        nombre: '',
        nit: '',
        telefono: '',
        departamento: '',
        municipio: '',
        direccion: '',
        email: '',
        bloquear_ventas_bool: false
    };
    isModalOpen.value = true;
};

const openEditClientModal = (client) => {
    isEditing.value = true;
    currentClientId.value = client.id;
    form.value = {
        nombre: client.nombre,
        nit: client.nit,
        telefono: client.telefono,
        departamento: client.departamento || '',
        municipio: client.municipio || '',
        direccion: client.direccion || '',
        email: client.email || '',
        bloquear_ventas_bool: client.bloquear_ventas === 'si'
    };
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};

const saveClient = async () => {
    if (!form.value.nombre || !form.value.nit || !form.value.telefono || !form.value.departamento || !form.value.municipio) {
        alert("Por favor, complete todos los campos obligatorios.");
        return;
    }

    isSaving.value = true;
    
    // Prepare payload
    const payload = {
        nombre: form.value.nombre,
        nit: form.value.nit,
        telefono: form.value.telefono,
        departamento: form.value.departamento,
        municipio: form.value.municipio,
        direccion: form.value.direccion,
        email: form.value.email,
        bloquear_ventas: form.value.bloquear_ventas_bool ? 'si' : 'no'
    };

    try {
        if (isEditing.value) {
            const res = await api.put(`/clients/${currentClientId.value}`, payload);
            if (res.data.status === 'success') {
                closeModal();
                await loadClients();
            } else {
                alert(res.data.message || "Error al actualizar cliente");
            }
        } else {
            const res = await api.post('/clients', payload);
            if (res.data.status === 'success') {
                closeModal();
                await loadClients();
            } else {
                alert(res.data.message || "Error al crear cliente");
            }
        }
    } catch (error) {
        console.error("Failed to save client", error);
        alert(error.response?.data?.message || "Ocurrió un error al guardar el cliente.");
    } finally {
        isSaving.value = false;
    }
};

const formatMoney = (val) => {
  return parseFloat(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>
