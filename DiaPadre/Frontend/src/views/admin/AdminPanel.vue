<template>
  <div class="min-h-screen bg-[#f0f2f7] text-gray-800 flex flex-col font-sans selection:bg-maga-light-blue selection:text-white">

    <!-- Toast -->
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="-translate-y-10 opacity-0 scale-90"
      enter-to-class="translate-y-0 opacity-100 scale-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="translate-y-0 opacity-100 scale-100"
      leave-to-class="-translate-y-8 opacity-0 scale-95"
    >
      <div v-if="toastMessage" class="fixed top-4 left-1/2 -translate-x-1/2 z-50 bg-maga-blue text-white py-3 px-6 rounded-2xl shadow-2xl flex items-center gap-3 border border-white/10 max-w-md w-[90%] md:w-auto backdrop-blur-sm">
        <Sparkles class="w-4 h-4 text-amber-300 flex-shrink-0 animate-pulse" />
        <span class="text-sm font-medium">{{ toastMessage }}</span>
      </div>
    </Transition>

    <!-- Top bar -->
    <header class="bg-maga-blue border-b border-white/10 px-6 py-3.5">
      <div class="max-w-7xl mx-auto flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center">
            <Database class="w-4 h-4 text-white" />
          </div>
          <div>
            <p class="text-white font-bold text-sm leading-none">Panel de Administración</p>
            <p class="text-white/50 text-[11px] mt-0.5">MAGA · Día del Padre 2026</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span class="flex h-2 w-2 relative">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
          </span>
          <span class="text-white/60 text-xs font-medium">En línea</span>
        </div>
      </div>
    </header>

    <div class="flex-grow max-w-7xl mx-auto w-full px-4 md:px-6 py-8">

      <!-- Page heading + actions -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-5 mb-8">
        <div>
          <p class="text-xs font-bold text-maga-light-blue uppercase tracking-widest mb-1">Gestión de participantes</p>
          <h1 class="text-2xl md:text-3xl font-black text-maga-blue">Registros Día del Padre</h1>
          <p class="text-gray-500 text-sm mt-1">Direcciones de VISAR y Despacho Superior</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <button
            @click="loadRegistrations"
            :disabled="isLoading"
            class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-maga-blue font-bold py-2 px-4 rounded-xl text-sm transition-all shadow-sm border border-gray-200 cursor-pointer disabled:opacity-50"
          >
            <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isLoading }" />
            Actualizar
          </button>
          <button
            @click="handleExportCSV"
            class="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2 px-4 rounded-xl text-sm transition-all shadow-md cursor-pointer"
          >
            <FileSpreadsheet class="w-3.5 h-3.5" />
            Exportar CSV
          </button>
        </div>
      </div>

      <!-- Stat cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">

        <div class="relative overflow-hidden bg-maga-blue text-white rounded-2xl p-6 shadow-lg">
          <div class="absolute -top-4 -right-4 w-24 h-24 bg-white/5 rounded-full"></div>
          <div class="absolute -bottom-6 -right-2 w-32 h-32 bg-white/5 rounded-full"></div>
          <p class="text-white/60 text-xs font-bold uppercase tracking-widest">Total registrados</p>
          <p class="text-5xl font-black mt-2 leading-none">{{ statsTotal }}</p>
          <p class="text-white/50 text-xs mt-2">padres en el sistema</p>
          <div class="absolute top-5 right-6 opacity-20">
            <User class="w-10 h-10" />
          </div>
        </div>

        <div class="relative overflow-hidden bg-white rounded-2xl p-6 shadow-sm border border-gray-100 sm:col-span-2">
          <div class="absolute top-0 right-0 w-1 h-full bg-amber-400 rounded-r-2xl"></div>
          <p class="text-gray-400 text-xs font-bold uppercase tracking-widest">Mayor participación</p>
          <p class="text-2xl font-extrabold text-maga-blue mt-2 leading-tight truncate">{{ topDirection }}</p>
          <p class="text-gray-400 text-xs mt-1.5">dirección con más inscritos</p>
          <div class="mt-3 inline-flex items-center gap-1.5 bg-amber-50 text-amber-600 text-[11px] font-bold px-2.5 py-1 rounded-full border border-amber-200/50">
            <Grid class="w-3 h-3" />
            Dirección líder
          </div>
        </div>

      </div>

      <!-- Search & filters -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-5">
        <div class="flex flex-col md:flex-row gap-3 items-center">
          <div class="relative flex-1 w-full">
            <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input
              type="text"
              placeholder="Buscar por nombre, teléfono o correo..."
              v-model="searchTerm"
              class="w-full pl-10 pr-9 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm outline-none focus:bg-white focus:border-maga-blue focus:ring-2 focus:ring-maga-blue/10 transition-all"
            />
            <button v-if="searchTerm" @click="searchTerm = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
              <X class="w-3.5 h-3.5" />
            </button>
          </div>

          <select
            v-model="adminDirFilter"
            class="w-full md:w-52 py-2.5 px-3 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold outline-none focus:bg-white focus:border-maga-blue transition-all cursor-pointer"
          >
            <option value="">Todas las direcciones</option>
            <option v-for="dir in DIRECTIONS" :key="dir" :value="dir">{{ dir }}</option>
          </select>

          <select
            v-model="adminDeptFilter"
            class="w-full md:w-52 py-2.5 px-3 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold outline-none focus:bg-white focus:border-maga-blue transition-all cursor-pointer"
          >
            <option value="">Todos los departamentos</option>
            <option v-for="dept in uniqueDepartments" :key="dept" :value="dept">{{ dept }}</option>
          </select>

          <button
            v-if="searchTerm || adminDirFilter || adminDeptFilter"
            @click="clearFilters"
            class="flex-shrink-0 inline-flex items-center gap-1.5 text-xs font-bold text-rose-500 bg-rose-50 hover:bg-rose-100 py-2.5 px-4 rounded-xl transition-colors border border-rose-100 cursor-pointer"
          >
            <X class="w-3 h-3" />
            Limpiar
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="border-b border-gray-100">
                <th class="py-3.5 px-5 text-[11px] font-bold text-gray-400 uppercase tracking-widest">Participante</th>
                <th class="py-3.5 px-5 text-[11px] font-bold text-gray-400 uppercase tracking-widest">Contacto</th>
                <th class="py-3.5 px-5 text-[11px] font-bold text-gray-400 uppercase tracking-widest">Dirección</th>
                <th class="py-3.5 px-5 text-[11px] font-bold text-gray-400 uppercase tracking-widest">Departamento</th>
                <th class="py-3.5 px-5 text-[11px] font-bold text-gray-400 uppercase tracking-widest">Fecha</th>
                <th class="py-3.5 px-5 text-[11px] font-bold text-gray-400 uppercase tracking-widest text-center">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">

              <!-- Skeleton loading -->
              <template v-if="isLoading">
                <tr v-for="i in PER_PAGE" :key="'sk-' + i" class="border-b border-gray-50">
                  <td class="py-4 px-5">
                    <div class="flex items-center gap-3">
                      <div class="w-9 h-9 rounded-xl bg-gray-100 animate-pulse flex-shrink-0"></div>
                      <div class="space-y-2">
                        <div class="w-36 h-3 bg-gray-100 rounded animate-pulse"></div>
                        <div class="w-24 h-2 bg-gray-100 rounded animate-pulse"></div>
                      </div>
                    </div>
                  </td>
                  <td class="py-4 px-5">
                    <div class="w-28 h-3 bg-gray-100 rounded animate-pulse mb-2"></div>
                    <div class="w-36 h-2 bg-gray-100 rounded animate-pulse"></div>
                  </td>
                  <td class="py-4 px-5"><div class="w-28 h-6 bg-gray-100 rounded-lg animate-pulse"></div></td>
                  <td class="py-4 px-5"><div class="w-20 h-3 bg-gray-100 rounded animate-pulse"></div></td>
                  <td class="py-4 px-5"><div class="w-28 h-3 bg-gray-100 rounded animate-pulse"></div></td>
                  <td class="py-4 px-5"><div class="w-16 h-8 bg-gray-100 rounded-lg animate-pulse mx-auto"></div></td>
                </tr>
              </template>

              <!-- Data rows -->
              <template v-else-if="paginatedRegistrations.length > 0">
                <tr
                  v-for="reg in paginatedRegistrations"
                  :key="reg.id"
                  class="hover:bg-blue-50/40 transition-colors group"
                >
                  <td class="py-4 px-5">
                    <div class="flex items-center gap-3">
                      <div class="w-9 h-9 rounded-xl bg-maga-blue/10 flex items-center justify-center flex-shrink-0 text-maga-blue font-black text-sm">
                        {{ reg.fullName.charAt(0).toUpperCase() }}
                      </div>
                      <div>
                        <p class="font-bold text-gray-800 text-sm leading-tight">{{ reg.fullName }}</p>
                        <p class="text-[11px] text-gray-400 font-mono mt-0.5">{{ reg.code }}</p>
                      </div>
                    </div>
                  </td>

                  <td class="py-4 px-5">
                    <div class="flex items-center gap-1.5 text-gray-700 text-sm font-medium">
                      <Phone class="w-3.5 h-3.5 text-gray-300 flex-shrink-0" />
                      {{ reg.phone }}
                    </div>
                    <div class="flex items-center gap-1.5 text-gray-400 text-xs mt-0.5">
                      <Mail class="w-3.5 h-3.5 flex-shrink-0" />
                      <span class="truncate max-w-[180px]">{{ reg.email }}</span>
                    </div>
                  </td>

                  <td class="py-4 px-5">
                    <span class="inline-flex items-center bg-maga-blue/8 text-maga-blue text-[11px] font-bold px-2.5 py-1 rounded-lg border border-maga-blue/10">
                      {{ reg.direction }}
                    </span>
                  </td>

                  <td class="py-4 px-5">
                    <div class="flex items-center gap-1.5 text-gray-600 text-sm">
                      <MapIcon class="w-3.5 h-3.5 text-gray-300 flex-shrink-0" />
                      {{ reg.department }}
                    </div>
                  </td>

                  <td class="py-4 px-5 text-xs text-gray-400 font-medium whitespace-nowrap">
                    {{ reg.dateCreated }}
                  </td>

                  <td class="py-4 px-5">
                    <div class="flex items-center justify-center gap-1">
                      <button
                        @click="handleOpenEdit(reg)"
                        class="p-2 rounded-lg text-gray-400 hover:text-amber-500 hover:bg-amber-50 transition-all cursor-pointer"
                        title="Editar"
                      >
                        <Edit class="w-4 h-4" />
                      </button>
                      <button
                        @click="handleDeleteRegistration(reg.id, reg.fullName)"
                        class="p-2 rounded-lg text-gray-400 hover:text-rose-500 hover:bg-rose-50 transition-all cursor-pointer"
                        title="Eliminar"
                      >
                        <Trash2 class="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              </template>

              <!-- Empty state -->
              <template v-else>
                <tr>
                  <td colspan="6" class="py-16 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-3">
                      <AlertCircle class="w-7 h-7 text-gray-300" />
                    </div>
                    <p class="font-bold text-gray-500">No se encontraron registros</p>
                    <p class="text-xs text-gray-400 mt-1">Prueba con otros términos de búsqueda o limpia los filtros.</p>
                  </td>
                </tr>
              </template>

            </tbody>
          </table>
        </div>

        <!-- Pagination footer -->
        <div class="px-5 py-3.5 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-3">
          <span class="text-xs text-gray-400 font-medium">
            <template v-if="filteredRegistrations.length > 0">
              Mostrando
              <span class="font-bold text-gray-700">{{ (currentPage - 1) * PER_PAGE + 1 }}</span>–<span class="font-bold text-gray-700">{{ Math.min(currentPage * PER_PAGE, filteredRegistrations.length) }}</span>
              de <span class="font-bold text-gray-700">{{ filteredRegistrations.length }}</span> registros
            </template>
            <template v-else>
              <span class="font-bold text-gray-700">0</span> registros
            </template>
          </span>

          <div v-if="totalPages > 1" class="flex items-center gap-1">
            <button
              @click="currentPage--"
              :disabled="currentPage === 1"
              class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-maga-blue transition-all disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
            >
              <ChevronLeft class="w-4 h-4" />
            </button>

            <template v-for="page in visiblePages" :key="page">
              <span v-if="page === '...'" class="w-8 h-8 flex items-center justify-center text-gray-400 text-xs">…</span>
              <button
                v-else
                @click="currentPage = page"
                :class="[
                  'w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold transition-all cursor-pointer',
                  currentPage === page
                    ? 'bg-maga-blue text-white shadow-sm'
                    : 'border border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-maga-blue'
                ]"
              >
                {{ page }}
              </button>
            </template>

            <button
              @click="currentPage++"
              :disabled="currentPage === totalPages"
              class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-maga-blue transition-all disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
            >
              <ChevronRight class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

    </div>

    <!-- MODAL: EDIT -->
    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="isEditingModalOpen && editingReg" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="isEditingModalOpen = false" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl relative z-10 overflow-hidden">
          <div class="bg-maga-blue px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center">
                <Edit class="w-4 h-4 text-white" />
              </div>
              <div>
                <p class="text-white font-bold text-sm">Editar registro</p>
                <p class="text-white/50 text-[11px] font-mono">{{ editingReg.code }}</p>
              </div>
            </div>
            <button @click="isEditingModalOpen = false" class="text-white/60 hover:text-white bg-white/10 hover:bg-white/20 rounded-lg p-1.5 transition-all cursor-pointer">
              <X class="w-4 h-4" />
            </button>
          </div>
          <form @submit.prevent="handleSaveEdit" class="p-6 space-y-4 text-sm">
            <div>
              <label class="block text-xs font-bold text-gray-600 mb-1.5">Nombre Completo</label>
              <input type="text" v-model="editingReg.fullName" class="w-full border border-gray-200 rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white focus:border-maga-blue outline-none focus:ring-2 focus:ring-maga-blue/10 transition-all font-semibold text-gray-800" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 mb-1.5">Teléfono</label>
              <input type="tel" v-model="editingReg.phone" class="w-full border border-gray-200 rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white focus:border-maga-blue outline-none focus:ring-2 focus:ring-maga-blue/10 transition-all font-semibold text-gray-800" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 mb-1.5">Correo Electrónico</label>
              <input type="email" v-model="editingReg.email" class="w-full border border-gray-200 rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white focus:border-maga-blue outline-none focus:ring-2 focus:ring-maga-blue/10 transition-all font-semibold text-gray-800" />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 mb-1.5">Dirección</label>
              <select v-model="editingReg.direction" class="w-full border border-gray-200 rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white focus:border-maga-blue outline-none focus:ring-2 focus:ring-maga-blue/10 transition-all font-semibold text-gray-800 cursor-pointer">
                <option v-for="dir in DIRECTIONS" :key="dir" :value="dir">{{ dir }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 mb-1.5">Departamento</label>
              <input type="text" v-model="editingReg.department" class="w-full border border-gray-200 rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white focus:border-maga-blue outline-none focus:ring-2 focus:ring-maga-blue/10 transition-all font-semibold text-gray-800" />
            </div>
            <div class="flex gap-3 pt-2">
              <button type="button" @click="isEditingModalOpen = false" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2.5 rounded-xl font-bold transition-all cursor-pointer">Cancelar</button>
              <button type="submit" :disabled="isEditSaving" class="flex-1 bg-maga-blue hover:bg-maga-light-blue disabled:opacity-60 text-white py-2.5 rounded-xl font-bold transition-all shadow-md cursor-pointer">
                {{ isEditSaving ? 'Guardando...' : 'Guardar cambios' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- MODAL: ADD -->
    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="isAddModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="isAddModalOpen = false" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl relative z-10 overflow-hidden">
          <div class="bg-emerald-600 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center">
                <Plus class="w-4 h-4 text-white" />
              </div>
              <p class="text-white font-bold text-sm">Nuevo registro manual</p>
            </div>
            <button @click="isAddModalOpen = false" class="text-white/60 hover:text-white bg-white/10 hover:bg-white/20 rounded-lg p-1.5 transition-all cursor-pointer">
              <X class="w-4 h-4" />
            </button>
          </div>
          <form @submit.prevent="handleCreateAdminRecord" class="p-6 space-y-4 text-sm">
            <div>
              <label class="block text-xs font-bold text-gray-600 mb-1.5">Nombre Completo *</label>
              <input type="text" v-model="newAdminName" @input="adminFormErrors.fullName = ''" :class="['w-full border rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white outline-none focus:ring-2 transition-all', adminFormErrors.fullName ? 'border-rose-400 focus:ring-rose-200' : 'border-gray-200 focus:border-maga-blue focus:ring-maga-blue/10']" placeholder="Nombre completo" />
              <span v-if="adminFormErrors.fullName" class="text-rose-500 text-[11px] mt-1 block">{{ adminFormErrors.fullName }}</span>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 mb-1.5">Teléfono *</label>
              <input type="tel" v-model="newAdminPhone" @input="adminFormErrors.phone = ''" :class="['w-full border rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white outline-none focus:ring-2 transition-all', adminFormErrors.phone ? 'border-rose-400 focus:ring-rose-200' : 'border-gray-200 focus:border-maga-blue focus:ring-maga-blue/10']" placeholder="0000-0000" />
              <span v-if="adminFormErrors.phone" class="text-rose-500 text-[11px] mt-1 block">{{ adminFormErrors.phone }}</span>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 mb-1.5">Correo Electrónico *</label>
              <input type="email" v-model="newAdminEmail" @input="adminFormErrors.email = ''" :class="['w-full border rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white outline-none focus:ring-2 transition-all', adminFormErrors.email ? 'border-rose-400 focus:ring-rose-200' : 'border-gray-200 focus:border-maga-blue focus:ring-maga-blue/10']" placeholder="correo@ejemplo.com" />
              <span v-if="adminFormErrors.email" class="text-rose-500 text-[11px] mt-1 block">{{ adminFormErrors.email }}</span>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 mb-1.5">Dirección *</label>
              <select v-model="newAdminDir" @change="adminFormErrors.direction = ''" :class="['w-full border rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white outline-none focus:ring-2 transition-all cursor-pointer', adminFormErrors.direction ? 'border-rose-400 focus:ring-rose-200' : 'border-gray-200 focus:border-maga-blue focus:ring-maga-blue/10']">
                <option value="">Selecciona una dirección</option>
                <option v-for="dir in DIRECTIONS" :key="dir" :value="dir">{{ dir }}</option>
              </select>
              <span v-if="adminFormErrors.direction" class="text-rose-500 text-[11px] mt-1 block">{{ adminFormErrors.direction }}</span>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-600 mb-1.5">Departamento *</label>
              <input type="text" v-model="newAdminDept" @input="adminFormErrors.department = ''" :class="['w-full border rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white outline-none focus:ring-2 transition-all', adminFormErrors.department ? 'border-rose-400 focus:ring-rose-200' : 'border-gray-200 focus:border-maga-blue focus:ring-maga-blue/10']" placeholder="Departamento" />
              <span v-if="adminFormErrors.department" class="text-rose-500 text-[11px] mt-1 block">{{ adminFormErrors.department }}</span>
            </div>
            <div class="flex gap-3 pt-2">
              <button type="button" @click="isAddModalOpen = false" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2.5 rounded-xl font-bold transition-all cursor-pointer">Cancelar</button>
              <button type="submit" :disabled="isAddSaving" class="flex-1 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white py-2.5 rounded-xl font-bold transition-all shadow-md cursor-pointer">
                {{ isAddSaving ? 'Guardando...' : 'Guardar registro' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import {
  User,
  Phone,
  Mail,
  Map as MapIcon,
  Trash2,
  Edit,
  Search,
  FileSpreadsheet,
  Plus,
  X,
  Sparkles,
  Database,
  Grid,
  AlertCircle,
  RefreshCw,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { padreService } from '@/services/padreService';

const PER_PAGE = 10;

const DIRECTIONS = [
  'SANIDAD ANIMAL', 'SANIDAD VEGETAL', 'INOCUIDAD', 'FITOZOOGENÉTICA',
  'DIPESCA', 'UDAFA', 'RRHH', 'VICEDESPACHO',
];

const registrations = ref([]);
const isLoading = ref(false);
const searchTerm = ref('');
const adminDirFilter = ref('');
const adminDeptFilter = ref('');
const currentPage = ref(1);

const isEditingModalOpen = ref(false);
const editingReg = ref(null);
const isEditSaving = ref(false);

const isAddModalOpen = ref(false);
const newAdminName = ref('');
const newAdminPhone = ref('');
const newAdminEmail = ref('');
const newAdminDir = ref('');
const newAdminDept = ref('');
const adminFormErrors = ref({});
const isAddSaving = ref(false);

const toastMessage = ref(null);

watch([searchTerm, adminDirFilter, adminDeptFilter], () => {
  currentPage.value = 1;
});

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const [datePart, timePart] = dateStr.split(' ');
  if (!datePart || !timePart) return dateStr;
  const [year, month, day] = datePart.split('-');
  const [hours, minutes] = timePart.split(':');
  return `${day}/${month}/${year} ${hours}:${minutes}`;
};

const mapToView = (r) => ({
  id: r.id,
  fullName: r.nombre_completo,
  phone: r.telefono,
  email: r.correo,
  direction: r.direccion,
  department: r.departamento,
  dateCreated: formatDate(r.fecha_registro),
  code: `MAGA-PADRE-2026-${String(r.id).padStart(4, '0')}`,
});

const loadRegistrations = async () => {
  isLoading.value = true;
  try {
    const res = await padreService.obtenerTodos();
    registrations.value = res.data.data.map(mapToView);
  } catch {
    showToast('Error al cargar los registros. Verifica la conexión.');
  } finally {
    isLoading.value = false;
  }
};

onMounted(loadRegistrations);

const showToast = (message) => {
  toastMessage.value = message;
  setTimeout(() => { toastMessage.value = null; }, 3500);
};

const handleExportCSV = () => {
  if (registrations.value.length === 0) {
    showToast('No hay registros para exportar.');
    return;
  }
  let csv = "data:text/csv;charset=utf-8,﻿";
  csv += "Codigo,Nombre Completo,Telefono,Correo,Direccion,Departamento,Fecha Registro\n";
  registrations.value.forEach(r => {
    csv += `"${r.code}","${r.fullName}","${r.phone}","${r.email}","${r.direction}","${r.department}","${r.dateCreated}"\n`;
  });
  const link = document.createElement('a');
  link.href = encodeURI(csv);
  link.download = `Participantes_MAGA_${new Date().toISOString().slice(0, 10)}.csv`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  showToast('Exportado a CSV exitosamente.');
};

const clearFilters = () => {
  searchTerm.value = '';
  adminDirFilter.value = '';
  adminDeptFilter.value = '';
};

const handleDeleteRegistration = async (id, name) => {
  const result = await Swal.fire({
    title: '¿Eliminar registro?',
    text: `Se eliminará a "${name}" del sistema.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e11d48',
    cancelButtonColor: '#002855',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
  });
  if (!result.isConfirmed) return;

  try {
    await padreService.eliminar(id);
    await loadRegistrations();
    Swal.fire({
      icon: 'success',
      title: '¡Eliminado!',
      text: 'El registro fue eliminado exitosamente.',
      confirmButtonColor: '#002855',
      confirmButtonText: 'Aceptar',
    });
  } catch {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se pudo eliminar el registro.',
      confirmButtonColor: '#002855',
      confirmButtonText: 'Aceptar',
    });
  }
};

const handleOpenEdit = (reg) => {
  editingReg.value = { ...reg };
  isEditingModalOpen.value = true;
};

const handleSaveEdit = async () => {
  if (!editingReg.value) return;
  isEditSaving.value = true;
  try {
    await padreService.actualizar(editingReg.value.id, {
      nombreCompleto: editingReg.value.fullName,
      telefono:       editingReg.value.phone,
      correo:         editingReg.value.email,
      direccion:      editingReg.value.direction,
      departamento:   editingReg.value.department,
    });
    await loadRegistrations();
    isEditingModalOpen.value = false;
    editingReg.value = null;
    Swal.fire({
      icon: 'success',
      title: '¡Actualizado!',
      text: 'El registro fue actualizado correctamente.',
      confirmButtonColor: '#002855',
      confirmButtonText: 'Aceptar',
    });
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Error al actualizar',
      text: e.response?.data?.message ?? 'Ocurrió un error. Inténtalo de nuevo.',
      confirmButtonColor: '#002855',
      confirmButtonText: 'Aceptar',
    });
  } finally {
    isEditSaving.value = false;
  }
};

const handleCreateAdminRecord = async () => {
  const errors = {};
  if (!newAdminName.value.trim() || newAdminName.value.trim().length < 4) errors.fullName = 'Mínimo 4 caracteres';
  if (newAdminPhone.value.replace(/\D/g, '').length < 8) errors.phone = 'Mínimo 8 dígitos';
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(newAdminEmail.value.trim())) errors.email = 'Formato inválido';
  if (!newAdminDir.value) errors.direction = 'Seleccione una dirección';
  if (!newAdminDept.value.trim()) errors.department = 'El departamento es obligatorio';
  adminFormErrors.value = errors;
  if (Object.keys(errors).length > 0) return;

  isAddSaving.value = true;
  try {
    await padreService.registrar({
      nombreCompleto: newAdminName.value.trim(),
      telefono:       newAdminPhone.value.trim(),
      correo:         newAdminEmail.value.trim().toLowerCase(),
      direccion:      newAdminDir.value,
      departamento:   newAdminDept.value.trim(),
    });
    await loadRegistrations();
    isAddModalOpen.value = false;
    newAdminName.value = ''; newAdminPhone.value = ''; newAdminEmail.value = '';
    newAdminDir.value = ''; newAdminDept.value = ''; adminFormErrors.value = {};
    Swal.fire({
      icon: 'success',
      title: '¡Registro creado!',
      text: 'El padre fue agregado exitosamente.',
      confirmButtonColor: '#002855',
      confirmButtonText: 'Aceptar',
    });
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Error al crear',
      text: e.response?.data?.message ?? 'Ocurrió un error. Inténtalo de nuevo.',
      confirmButtonColor: '#002855',
      confirmButtonText: 'Aceptar',
    });
  } finally {
    isAddSaving.value = false;
  }
};

const uniqueDepartments = computed(() =>
  [...new Set(registrations.value.map(r => r.department).filter(Boolean))].sort()
);

const filteredRegistrations = computed(() => {
  const s = searchTerm.value.toLowerCase();
  return registrations.value.filter(r => {
    const matchSearch = !s
      || r.fullName.toLowerCase().includes(s)
      || r.email.toLowerCase().includes(s)
      || r.phone.includes(s)
      || r.code.toLowerCase().includes(s);
    const matchDir  = !adminDirFilter.value  || r.direction  === adminDirFilter.value;
    const matchDept = !adminDeptFilter.value || r.department === adminDeptFilter.value;
    return matchSearch && matchDir && matchDept;
  });
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredRegistrations.value.length / PER_PAGE)));

const paginatedRegistrations = computed(() => {
  const start = (currentPage.value - 1) * PER_PAGE;
  return filteredRegistrations.value.slice(start, start + PER_PAGE);
});

const visiblePages = computed(() => {
  const total = totalPages.value;
  const current = currentPage.value;
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
  const pages = [1];
  if (current > 3) pages.push('...');
  for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
    pages.push(i);
  }
  if (current < total - 2) pages.push('...');
  pages.push(total);
  return pages;
});

const statsTotal = computed(() => registrations.value.length);
const directionCounts = computed(() =>
  registrations.value.reduce((acc, r) => {
    acc[r.direction] = (acc[r.direction] || 0) + 1;
    return acc;
  }, {})
);
const topDirection = computed(() =>
  Object.entries(directionCounts.value).sort((a, b) => b[1] - a[1])[0]?.[0] || 'Ninguna'
);
</script>
