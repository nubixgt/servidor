<template>
  <div class="pt-20 pb-10 px-4 md:px-10 md:pb-20 max-w-7xl mx-auto space-y-10 relative">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h2 class="text-4xl font-bold tracking-tight text-white mb-2">Gestión de RRHH</h2>
        <p class="text-white/60">Gestiona tu fuerza laboral y registra nuevos empleados.</p>
      </div>
      <div class="flex flex-wrap gap-3">
        <button
          @click="openIncidentModal()"
          class="glass-button text-white py-4 px-8 rounded-2xl font-bold flex items-center justify-center gap-2 border border-amber-400/30 text-amber-400 hover:bg-amber-400/10 hover:-translate-y-0.5 active:translate-y-0 transition-all"
        >
          <ExclamationTriangleIcon class="w-5 h-5" />
          Incidencia Empleado
        </button>
        <button
          @click="openModal()"
          class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center justify-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 hover:-translate-y-0.5 active:translate-y-0 transition-all"
        >
          <PlusIcon class="w-5 h-5" />
          Nuevo Empleado
        </button>
      </div>
    </div>

    <!-- Stats Cards (4) -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div
        v-for="(stat, i) in stats"
        :key="i"
        class="glass-card p-8 rounded-[32px] flex flex-col justify-between h-44 cursor-pointer group hover:-translate-y-3 hover:scale-105 hover:shadow-[0_20px_40px_-15px_rgba(99,102,241,0.5)] hover:scale-105 transition-all duration-500" data-aos="zoom-in-up" data-aos-duration="1000"
      >
        <div class="flex items-center justify-between mb-4">
          <div :class="`p-3 rounded-2xl ${stat.bgColor} ${stat.color} border border-white/10 shadow-lg`">
            <component :is="stat.icon" class="w-7 h-7" />
          </div>
          <span :class="`text-[10px] font-bold px-3 py-1.5 rounded-full ${stat.color} ${stat.bgColor} border border-white/5 tracking-wider uppercase`">
            {{ stat.change }}
          </span>
        </div>
        <div>
          <p class="text-white/40 text-[10px] font-bold uppercase tracking-[0.2em]">{{ stat.label }}</p>
          <h3 class="text-4xl font-bold text-white mt-1 group-hover:text-primary transition-colors">{{ stat.value }}</h3>
        </div>
      </div>
    </div>

    <!-- Table Section -->
    <div class="glass-card rounded-[40px] overflow-hidden border border-white/10 transition-all duration-500" data-aos="zoom-in-up" data-aos-duration="1000">
      <!-- Filter Bar -->
      <div class="p-8 border-b border-white/5 space-y-4">
        <div class="flex flex-wrap items-center gap-3">
          <!-- Search -->
          <div class="flex items-center gap-2 bg-black/20 border border-white/10 rounded-2xl px-4 py-3 flex-1 min-w-[200px]">
            <svg class="w-4 h-4 text-white/30 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Buscar por nombre, puesto o DPI..."
              class="bg-transparent flex-1 text-sm text-white placeholder-white/30 focus:outline-none"
            />
          </div>

          <!-- Tipo de Puesto -->
          <select v-model="filterTipo" class="bg-black/20 border border-white/10 rounded-2xl px-4 py-3 text-sm text-white/80 focus:outline-none focus:border-primary/50 transition-all appearance-none w-full md:w-auto md:min-w-[160px]">
            <option value="">Todos los tipos</option>
            <option v-for="p in puestos" :key="p.id" :value="p.nombre">{{ p.nombre }}</option>
          </select>

          <!-- Estado -->
          <select v-model="filterEstado" class="bg-black/20 border border-white/10 rounded-2xl px-4 py-3 text-sm text-white/80 focus:outline-none focus:border-primary/50 transition-all appearance-none w-full md:w-auto md:min-w-[140px]">
            <option value="">Todos los estados</option>
            <option value="Activo">Activo</option>
            <option value="Baja">Baja</option>
          </select>

          <!-- Proyecto -->
          <select v-model="filterProyecto" class="bg-black/20 border border-white/10 rounded-2xl px-4 py-3 text-sm text-white/80 focus:outline-none focus:border-primary/50 transition-all appearance-none w-full md:w-auto md:min-w-[180px]">
            <option value="">Todos los proyectos</option>
            <option value="__sin__">Sin asignar</option>
            <option v-for="proj in projects" :key="proj.id" :value="String(proj.id)">
              {{ proj.codigo ? `[${proj.codigo}] ` : '' }}{{ proj.nombre }}
            </option>
          </select>

          <!-- Reset -->
          <button
            v-if="activeFiltersCount > 0"
            @click="resetFilters"
            class="flex items-center gap-2 text-white/50 hover:text-white text-xs font-bold px-4 py-3 rounded-2xl hover:bg-white/5 border border-white/10 transition-all"
          >
            <XMarkIcon class="w-4 h-4" />
            Limpiar ({{ activeFiltersCount }})
          </button>

          <span class="ml-auto text-xs font-bold text-white/30 tracking-widest uppercase whitespace-nowrap">
            {{ filteredPersonnel.length }} resultado{{ filteredPersonnel.length !== 1 ? 's' : '' }}
          </span>
        </div>
      </div>

      <div class="overflow-x-auto px-4">
        <table class="w-full min-w-[640px] text-left">
          <thead>
            <tr class="text-[11px] font-bold text-white/40 uppercase tracking-[0.2em]">
              <th class="px-8 py-8">Nombre del Empleado</th>
              <th class="px-8 py-8">DPI / NIT</th>
              <th class="px-8 py-8">Puesto / Planilla</th>
              <th class="px-8 py-8">Proyecto</th>
              <th class="px-8 py-8">Salario Base</th>
              <th class="px-8 py-8">Estado</th>
              <th class="px-8 py-8 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-if="loading">
              <td colspan="7" class="px-8 py-8 text-center text-white/50">Cargando RRHH...</td>
            </tr>
            <tr v-else-if="filteredPersonnel.length === 0">
              <td colspan="7" class="px-8 py-12 text-center">
                <p class="text-white/40 font-semibold">No se encontraron empleados</p>
                <p v-if="activeFiltersCount > 0" class="text-white/25 text-sm mt-1">Prueba ajustando los filtros</p>
              </td>
            </tr>
            <tr v-for="emp in paginatedPersonnel" :key="emp.id" class="hover:bg-white/5 group transition-colors duration-500">
              <!-- Nombre + foto + tipo badge -->
              <td class="px-8 py-6">
                <div class="flex items-center gap-4">
                  <div
                    @click="emp.foto_path ? openImageFullScreen(getPhotoUrl(emp)) : null"
                    :class="['w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center overflow-hidden border border-white/10 shadow-lg transition-transform hover:scale-105 flex-shrink-0', emp.foto_path ? 'cursor-pointer' : '']"
                  >
                    <img v-if="emp.foto_path" :src="getPhotoUrl(emp)" alt="Foto" class="w-full h-full object-cover" />
                    <span v-else class="font-bold text-primary text-sm">{{ getInitials(emp.nombres, emp.apellidos) }}</span>
                  </div>
                  <div>
                    <p class="font-bold text-white">{{ emp.nombres }} {{ emp.apellidos }}</p>
                    <span :class="`mt-1 inline-block px-2.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider ${getTipoEmpleadoBadge(emp.tipo_empleado).color}`">
                      {{ emp.tipo_empleado }}
                    </span>
                  </div>
                </div>
              </td>
              <!-- DPI / NIT -->
              <td class="px-8 py-6">
                <p class="text-sm font-semibold text-white/90">{{ emp.dpi }}</p>
                <p class="text-xs text-white/40 mt-0.5">{{ emp.nit || 'Sin NIT' }}</p>
              </td>
              <!-- Puesto / Planilla -->
              <td class="px-8 py-6">
                <p class="text-sm font-semibold text-white/80">{{ emp.puesto }}</p>
                <p class="text-xs text-white/40 mt-0.5">{{ emp.tipo_planilla }}</p>
              </td>
              <!-- Proyecto -->
              <td class="px-8 py-6">
                <span v-if="emp.proyecto_nombre" class="text-sm font-semibold text-primary">{{ emp.proyecto_nombre }}</span>
                <span v-else class="text-xs text-white/30">Sin asignar</span>
              </td>
              <!-- Salario -->
              <td class="px-8 py-6 font-bold text-white">
                Q {{ formatCurrency(emp.salario_base) }}
              </td>
              <!-- Estado -->
              <td class="px-8 py-6">
                <span :class="`px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider border ${getEstadoBadge(emp).color}`">
                  {{ getEstadoBadge(emp).label }}
                </span>
              </td>
              <!-- Acciones -->
              <td class="px-8 py-6">
                <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all">
                  <button @click="openViewModal(emp)" class="p-3 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all" title="Visualizar">
                    <EyeIcon class="w-5 h-5" />
                  </button>
                  <button @click="openEditModal(emp)" class="p-3 text-white/40 hover:text-primary hover:bg-white/10 rounded-xl transition-all" title="Editar">
                    <PencilIcon class="w-5 h-5" />
                  </button>
                  <button @click="deleteEmployee(emp.id)" class="p-3 text-white/40 hover:text-tertiary hover:bg-white/10 rounded-xl transition-all" title="Eliminar">
                    <TrashIcon class="w-5 h-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div class="px-8 py-5 flex flex-wrap items-center justify-between gap-4 border-t border-white/5">
        <p class="text-xs font-bold text-white/30 tracking-widest uppercase">
          Mostrando {{ Math.min((currentPage - 1) * PAGE_SIZE + 1, filteredPersonnel.length) }}–{{ Math.min(currentPage * PAGE_SIZE, filteredPersonnel.length) }}
          de {{ filteredPersonnel.length }} empleado{{ filteredPersonnel.length !== 1 ? 's' : '' }}
        </p>

        <div class="flex items-center gap-2">
          <button
            @click="currentPage--"
            :disabled="currentPage === 1"
            class="p-2 rounded-xl text-white/40 hover:text-white hover:bg-white/10 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
          >
            <ChevronLeftIcon class="w-5 h-5" />
          </button>

          <template v-for="page in totalPages" :key="page">
            <button
              v-if="totalPages <= 7 || Math.abs(page - currentPage) <= 1 || page === 1 || page === totalPages"
              @click="currentPage = page"
              :class="[
                'min-w-[36px] h-9 px-2 rounded-xl text-sm font-bold transition-all',
                page === currentPage
                  ? 'bg-primary text-white shadow-lg shadow-primary/30'
                  : 'text-white/40 hover:text-white hover:bg-white/10'
              ]"
            >{{ page }}</button>
            <span
              v-else-if="(page === currentPage - 2 && page > 2) || (page === currentPage + 2 && page < totalPages - 1)"
              class="text-white/30 px-1"
            >…</span>
          </template>

          <button
            @click="currentPage++"
            :disabled="currentPage === totalPages"
            class="p-2 rounded-xl text-white/40 hover:text-white hover:bg-white/10 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
          >
            <ChevronRightIcon class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>

    <!-- ============================================================
         MODAL AÑADIR / EDITAR
         ============================================================ -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>

      <div class="glass-card w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-[32px] p-4 md:p-8 relative z-10 border border-white/10 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-2xl font-bold text-white">{{ isEditing ? 'Editar Empleado' : 'Nuevo Empleado' }}</h3>
          <button @click="closeModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all">
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <form @submit.prevent="submitForm" class="space-y-8">

          <!-- SECCIÓN 1: Información Personal -->
          <div>
            <p class="text-xs font-bold text-white/30 uppercase tracking-[0.25em] mb-4">Información Personal</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

              <!-- Tipo de Puesto -->
              <div class="space-y-2 md:col-span-2">
                <div class="flex items-center justify-between">
                  <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Tipo de Puesto <span class="text-tertiary">*</span></label>
                  <button
                    type="button"
                    @click="addNuevoPuesto"
                    class="flex items-center gap-1 text-xs font-bold text-primary hover:text-primary/80 transition-colors"
                    title="Agregar nuevo tipo de puesto"
                  >
                    <PlusIcon class="w-4 h-4" />
                    Nuevo puesto
                  </button>
                </div>
                <select v-model="formData.tipo_empleado" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all appearance-none">
                  <option value="" disabled>Seleccionar...</option>
                  <option v-for="p in puestos" :key="p.id" :value="p.nombre">{{ p.nombre }}</option>
                </select>
              </div>

              <!-- Nombres -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Nombres <span class="text-tertiary">*</span></label>
                <input v-model="formData.nombres" type="text" required placeholder="Ej. Juan Carlos"
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" />
              </div>

              <!-- Apellidos -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Apellidos <span class="text-tertiary">*</span></label>
                <input v-model="formData.apellidos" type="text" required placeholder="Ej. Pérez García"
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" />
              </div>

              <!-- DPI -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">DPI <span class="text-tertiary">*</span></label>
                <input v-model="formData.dpi" @input="formatDpi" type="text" required placeholder="0000 00000 0000" maxlength="15"
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" />
              </div>

              <!-- NIT -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">NIT</label>
                <input v-model="formData.nit" type="text" placeholder="Opcional"
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" />
              </div>

              <!-- Teléfono -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Teléfono</label>
                <input v-model="formData.telefono" @input="formatPhone" type="text" placeholder="0000-0000" maxlength="9"
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" />
              </div>

              <!-- Fecha de Nacimiento -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Fecha de Nacimiento</label>
                <input v-model="formData.fecha_nacimiento" type="date"
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" />
              </div>

              <!-- Cantidad de Hijos -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Cantidad de Hijos</label>
                <input v-model="formData.cantidad_hijos" type="number" min="0" placeholder="0"
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" />
              </div>

              <!-- Nivel Académico -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Nivel Académico</label>
                <select v-model="formData.nivel_academico" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all appearance-none">
                  <option value="">Seleccionar...</option>
                  <option value="Primaria">Primaria</option>
                  <option value="Basicos">Básicos</option>
                  <option value="Diversificado">Diversificado</option>
                  <option value="Universidad">Universidad</option>
                </select>
              </div>

              <!-- Dirección -->
              <div class="space-y-2 md:col-span-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Dirección</label>
                <textarea v-model="formData.direccion" rows="2" placeholder="Opcional"
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all resize-none"></textarea>
              </div>
            </div>
          </div>

          <!-- SECCIÓN 2: Datos Laborales -->
          <div>
            <p class="text-xs font-bold text-white/30 uppercase tracking-[0.25em] mb-4">Datos Laborales</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

              <!-- Puesto -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Puesto de Trabajo <span class="text-tertiary">*</span></label>
                <input v-model="formData.puesto" type="text" required placeholder="Ej. Operador de maquinaria"
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" />
              </div>

              <!-- Tipo planilla -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Tipo de Planilla <span class="text-tertiary">*</span></label>
                <select v-model="formData.tipo_planilla" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all appearance-none">
                  <option value="" disabled>Seleccionar...</option>
                  <option value="Quincenal">Quincenal</option>
                  <option value="Mensual">Mensual</option>
                  <option value="Semanal">Semanal</option>
                  <option value="Diario">Diario</option>
                </select>
              </div>

              <!-- Salario base -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Salario Base (GTQ) <span class="text-tertiary">*</span></label>
                <input v-model="formData.salario_base" type="number" step="0.01" min="0" required placeholder="0.00"
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" />
              </div>

              <!-- Tarifa hora extra -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Tarifa por Hora Extra (GTQ/hr)</label>
                <input v-model="formData.tarifa_hora_extra" type="number" step="0.01" min="0" placeholder="Opcional"
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" />
              </div>

              <!-- Diario Viáticos -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Diario Viáticos (GTQ)</label>
                <input v-model="formData.diario_viaticos" type="number" step="0.01" min="0" placeholder="0.00"
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" />
              </div>

              <!-- IGSS -->
              <div class="space-y-2" :class="formData.igss === 1 ? 'md:col-span-2' : ''">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">IGSS</label>
                <div class="flex flex-wrap items-center gap-4 pt-2">
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" v-model="formData.igss" :value="1" class="accent-primary w-4 h-4" />
                    <span class="text-sm text-white/80 font-semibold">Sí</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-400/15 text-emerald-400 border border-emerald-400/20 font-bold uppercase tracking-wider">Activo</span>
                  </label>
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" v-model="formData.igss" :value="0" class="accent-primary w-4 h-4" />
                    <span class="text-sm text-white/80 font-semibold">No</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-white/10 text-white/50 border border-white/10 font-bold uppercase tracking-wider">Inactivo</span>
                  </label>
                  <input
                    v-if="formData.igss === 1"
                    v-model="formData.igss_numero"
                    type="text"
                    placeholder="Número de afiliación IGSS"
                    class="flex-1 min-w-[200px] bg-black/20 border border-emerald-400/30 rounded-2xl px-5 py-3 text-white placeholder-white/20 focus:outline-none focus:border-emerald-400/60 focus:ring-1 focus:ring-emerald-400/30 transition-all"
                  />
                </div>
              </div>

              <!-- Fecha de contratación -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Fecha de Contratación <span class="text-tertiary">*</span></label>
                <input v-model="formData.fecha_contratacion" type="date" required
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" />
              </div>

              <!-- Fecha de baja -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Fecha de Baja</label>
                <input v-model="formData.fecha_baja" type="date"
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" />
              </div>

              <!-- Proyecto asignado -->
              <div class="space-y-2 md:col-span-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Proyecto Asignado</label>
                <select v-model="formData.proyecto_id" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all appearance-none">
                  <option :value="null">Sin proyecto asignado</option>
                  <option v-for="proj in projects" :key="proj.id" :value="proj.id">
                    {{ proj.codigo ? `[${proj.codigo}] ` : '' }}{{ proj.nombre }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- SECCIÓN 3: Dato de Contacto -->
          <div>
            <p class="text-xs font-bold text-white/30 uppercase tracking-[0.25em] mb-4">Dato de Contacto</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Nombres del Contacto</label>
                <input v-model="formData.contacto_nombres" type="text" placeholder="Nombre completo"
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" />
              </div>
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Número del Contacto</label>
                <input v-model="formData.contacto_numero" @input="formatContactoNumero" type="text" placeholder="0000-0000" maxlength="9"
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" />
              </div>
            </div>
          </div>

          <!-- SECCIÓN 4: Datos Bancarios -->
          <div>
            <p class="text-xs font-bold text-white/30 uppercase tracking-[0.25em] mb-4">Datos Bancarios</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Número de Cuenta</label>
                <input v-model="formData.numero_cuenta" type="text" placeholder="Opcional"
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" />
              </div>
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Nombre del Banco</label>
                <input v-model="formData.nombre_banco" type="text" placeholder="Opcional"
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" />
              </div>
            </div>
          </div>

          <!-- SECCIÓN 5: Fotografía -->
          <div>
            <p class="text-xs font-bold text-white/30 uppercase tracking-[0.25em] mb-4">Fotografía</p>
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">
                Foto del Empleado (PNG, JPG, JPEG)
                <span v-if="isEditing" class="text-primary normal-case ml-1">— Sube una nueva foto para reemplazar la actual</span>
              </label>
              <input @change="handleFileChange" type="file" accept=".png,.jpg,.jpeg"
                class="w-full text-white/60 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-primary/20 file:text-primary hover:file:bg-primary/30 file:transition-all cursor-pointer bg-black/20 border border-white/10 rounded-2xl p-2" />
            </div>
          </div>

          <!-- Botones -->
          <div class="pt-4 flex justify-end gap-4 border-t border-white/5">
            <button type="button" @click="closeModal" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">
              Cancelar
            </button>
            <button type="submit" :disabled="isSubmitting" class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
              <span v-if="isSubmitting">Guardando...</span>
              <span v-else>{{ isEditing ? 'Actualizar Empleado' : 'Guardar Empleado' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================
         MODAL VISUALIZAR
         ============================================================ -->
    <div v-if="showViewModal && selectedEmp" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeViewModal"></div>

      <div class="glass-card w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-[32px] p-4 md:p-8 relative z-10 border border-white/10 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-2xl font-bold text-white">Detalles del Empleado</h3>
          <button @click="closeViewModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all">
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <div class="flex flex-col md:flex-row gap-8">
          <!-- Foto grande izquierda -->
          <div class="w-full md:w-1/3 flex flex-col items-center gap-4 flex-shrink-0">
            <div
              @click="selectedEmp.foto_path ? openImageFullScreen(getPhotoUrl(selectedEmp)) : null"
              :class="['w-44 h-44 rounded-3xl bg-white/5 flex items-center justify-center overflow-hidden border border-white/10 shadow-2xl', selectedEmp.foto_path ? 'cursor-pointer hover:scale-105 transition-transform' : '']"
            >
              <img v-if="selectedEmp.foto_path" :src="getPhotoUrl(selectedEmp)" alt="Foto" class="w-full h-full object-cover" />
              <span v-else class="font-bold text-primary text-5xl">{{ getInitials(selectedEmp.nombres, selectedEmp.apellidos) }}</span>
            </div>

            <!-- Badge tipo puesto -->
            <span :class="`px-4 py-1.5 rounded-full text-[10px] font-extrabold uppercase tracking-widest border ${getTipoEmpleadoBadge(selectedEmp.tipo_empleado).color}`">
              {{ selectedEmp.tipo_empleado }}
            </span>

            <!-- Badge estado -->
            <span :class="`px-4 py-1.5 rounded-full text-[10px] font-extrabold uppercase tracking-widest border ${getEstadoBadge(selectedEmp).color}`">
              {{ getEstadoBadge(selectedEmp).label }}
            </span>

            <!-- Badge IGSS -->
            <template v-if="selectedEmp.igss !== null && selectedEmp.igss !== undefined">
              <span :class="`px-4 py-1.5 rounded-full text-[10px] font-extrabold uppercase tracking-widest border ${selectedEmp.igss == 1 ? 'bg-emerald-400/15 text-emerald-400 border-emerald-400/20' : 'bg-white/10 text-white/50 border-white/10'}`">
                IGSS: {{ selectedEmp.igss == 1 ? 'Activo' : 'Inactivo' }}
              </span>
              <span v-if="selectedEmp.igss == 1 && selectedEmp.igss_numero" class="text-xs font-bold text-emerald-400/80">
                No. {{ selectedEmp.igss_numero }}
              </span>
            </template>
          </div>

          <!-- Datos derecha en grid 2 cols -->
          <div class="w-full grid grid-cols-1 sm:grid-cols-2 gap-y-5 gap-x-4">
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Nombre Completo</p>
              <p class="text-base font-bold text-white">{{ selectedEmp.nombres }} {{ selectedEmp.apellidos }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">ID Empleado</p>
              <p class="text-base font-semibold text-white/90">#{{ selectedEmp.id }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">DPI</p>
              <p class="text-base font-semibold text-white/90">{{ selectedEmp.dpi }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">NIT</p>
              <p class="text-base font-semibold text-white/90">{{ selectedEmp.nit || 'No registrado' }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Fecha de Nacimiento</p>
              <p class="text-base font-semibold text-white/90">{{ selectedEmp.fecha_nacimiento ? formatDate(selectedEmp.fecha_nacimiento) : 'No registrada' }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Cantidad de Hijos</p>
              <p class="text-base font-semibold text-white/90">{{ selectedEmp.cantidad_hijos !== null && selectedEmp.cantidad_hijos !== undefined ? selectedEmp.cantidad_hijos : 'No registrado' }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Nivel Académico</p>
              <p class="text-base font-semibold text-white/90">{{ selectedEmp.nivel_academico || 'No registrado' }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Teléfono</p>
              <p class="text-base font-semibold text-white/90">{{ selectedEmp.telefono || 'No registrado' }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Puesto de Trabajo</p>
              <p class="text-base font-semibold text-primary">{{ selectedEmp.puesto }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Tipo de Planilla</p>
              <p class="text-base font-semibold text-white/90">{{ selectedEmp.tipo_planilla }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Proyecto Asignado</p>
              <p class="text-base font-semibold text-white/90">{{ selectedEmp.proyecto_nombre || 'Sin asignar' }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Diario Viáticos</p>
              <p class="text-base font-semibold text-white/90">{{ selectedEmp.diario_viaticos ? 'Q ' + formatCurrency(selectedEmp.diario_viaticos) : 'No aplica' }}</p>
            </div>
            <div class="bg-white/5 p-4 rounded-2xl border border-white/5">
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Salario Base</p>
              <p class="text-xl font-bold text-white">Q {{ formatCurrency(selectedEmp.salario_base) }}</p>
            </div>
            <div class="bg-white/5 p-4 rounded-2xl border border-white/5">
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Tarifa Hora Extra</p>
              <p class="text-xl font-bold text-white">{{ selectedEmp.tarifa_hora_extra ? 'Q ' + formatCurrency(selectedEmp.tarifa_hora_extra) : 'No aplica' }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Fecha de Contratación</p>
              <p class="text-base font-semibold text-white/90">{{ formatDate(selectedEmp.fecha_contratacion) }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Fecha de Baja</p>
              <p class="text-base font-semibold text-white/90">{{ selectedEmp.fecha_baja ? formatDate(selectedEmp.fecha_baja) : 'Activo' }}</p>
            </div>
            <div v-if="selectedEmp.direccion" class="sm:col-span-2">
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Dirección</p>
              <p class="text-sm font-semibold text-white/80">{{ selectedEmp.direccion }}</p>
            </div>

            <!-- Dato de Contacto -->
            <template v-if="selectedEmp.contacto_nombres || selectedEmp.contacto_numero">
              <div class="sm:col-span-2 border-t border-white/5 pt-4">
                <p class="text-[10px] font-bold text-white/30 uppercase tracking-[0.25em] mb-3">Dato de Contacto</p>
                <div class="grid grid-cols-2 gap-4">
                  <div v-if="selectedEmp.contacto_nombres">
                    <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Nombres</p>
                    <p class="text-base font-semibold text-white/90">{{ selectedEmp.contacto_nombres }}</p>
                  </div>
                  <div v-if="selectedEmp.contacto_numero">
                    <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Número</p>
                    <p class="text-base font-semibold text-white/90">{{ selectedEmp.contacto_numero }}</p>
                  </div>
                </div>
              </div>
            </template>

            <!-- Datos bancarios (solo si existen) -->
            <template v-if="selectedEmp.numero_cuenta || selectedEmp.nombre_banco">
              <div class="sm:col-span-2 border-t border-white/5 pt-4">
                <p class="text-[10px] font-bold text-white/30 uppercase tracking-[0.25em] mb-3">Datos Bancarios</p>
                <div class="grid grid-cols-2 gap-4">
                  <div v-if="selectedEmp.numero_cuenta">
                    <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Número de Cuenta</p>
                    <p class="text-base font-semibold text-white/90">{{ selectedEmp.numero_cuenta }}</p>
                  </div>
                  <div v-if="selectedEmp.nombre_banco">
                    <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Banco</p>
                    <p class="text-base font-semibold text-white/90">{{ selectedEmp.nombre_banco }}</p>
                  </div>
                </div>
              </div>
            </template>

            <div class="sm:col-span-2 border-t border-white/5 pt-3">
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Registrado el</p>
              <p class="text-sm font-semibold text-white/50">{{ new Date(selectedEmp.created_at).toLocaleString('es-GT') }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================
         SECCIÓN INCIDENCIAS
         ============================================================ -->
    <div class="glass-card rounded-[40px] overflow-hidden border border-white/10 transition-all duration-500" data-aos="zoom-in-up" data-aos-duration="1000">
      <!-- Header -->
      <div class="p-8 border-b border-white/5 flex items-center justify-between gap-4">
        <div>
          <h3 class="text-xl font-bold text-white">Incidencias de Empleados</h3>
          <p class="text-white/40 text-sm mt-1">{{ filteredIncidents.length }} registro{{ filteredIncidents.length !== 1 ? 's' : '' }}</p>
        </div>
        <button
          @click="openIncidentModal()"
          class="flex items-center gap-2 text-xs font-bold text-amber-400 hover:text-amber-300 px-5 py-3 rounded-2xl hover:bg-amber-400/10 border border-amber-400/20 transition-all"
        >
          <PlusIcon class="w-4 h-4" />
          Nueva Incidencia
        </button>
      </div>

      <!-- Filtros -->
      <div class="px-8 pt-6 pb-4 border-b border-white/5">
        <div class="flex flex-wrap items-center gap-3">
          <!-- Buscador -->
          <div class="flex items-center gap-2 bg-black/20 border border-white/10 rounded-2xl px-4 py-3 flex-1 min-w-[200px]">
            <svg class="w-4 h-4 text-white/30 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input
              v-model="incidentSearch"
              type="text"
              placeholder="Buscar en texto o motivo..."
              class="bg-transparent flex-1 text-sm text-white placeholder-white/30 focus:outline-none"
            />
          </div>

          <!-- Filtro empleado -->
          <select v-model="filterIncidentEmpleado" class="bg-black/20 border border-white/10 rounded-2xl px-4 py-3 text-sm text-white/80 focus:outline-none focus:border-amber-400/30 transition-all appearance-none w-full md:w-auto md:min-w-[200px]">
            <option value="">Todos los empleados</option>
            <option v-for="emp in personnel" :key="emp.id" :value="emp.id">
              {{ emp.nombres }} {{ emp.apellidos }}
            </option>
          </select>

          <!-- Filtro fecha -->
          <input
            v-model="filterIncidentFecha"
            type="date"
            class="bg-black/20 border border-white/10 rounded-2xl px-4 py-3 text-sm text-white/80 focus:outline-none focus:border-amber-400/30 transition-all w-full md:w-auto"
          />

          <!-- Limpiar -->
          <button
            v-if="activeIncidentFiltersCount > 0"
            @click="resetIncidentFilters"
            class="flex items-center gap-2 text-white/50 hover:text-white text-xs font-bold px-4 py-3 rounded-2xl hover:bg-white/5 border border-white/10 transition-all"
          >
            <XMarkIcon class="w-4 h-4" />
            Limpiar ({{ activeIncidentFiltersCount }})
          </button>
        </div>
      </div>

      <!-- Tabla -->
      <div class="overflow-x-auto px-4">
        <table class="w-full min-w-[600px] text-left">
          <thead>
            <tr class="text-[11px] font-bold text-white/40 uppercase tracking-[0.2em]">
              <th class="px-8 py-6">Empleado</th>
              <th class="px-8 py-6">Texto</th>
              <th class="px-8 py-6">Fecha</th>
              <th class="px-8 py-6">Motivo</th>
              <th class="px-8 py-6 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-if="loadingIncidents">
              <td colspan="5" class="px-8 py-8 text-center text-white/50">Cargando incidencias...</td>
            </tr>
            <tr v-else-if="filteredIncidents.length === 0">
              <td colspan="5" class="px-8 py-12 text-center">
                <p class="text-white/40 font-semibold">Sin incidencias registradas</p>
                <p v-if="activeIncidentFiltersCount > 0" class="text-white/25 text-sm mt-1">Prueba ajustando los filtros</p>
              </td>
            </tr>
            <tr v-for="inc in paginatedIncidents" :key="inc.id" class="hover:bg-white/5 group transition-colors duration-300">
              <td class="px-8 py-5">
                <p class="text-sm font-bold text-white">{{ inc.empleado_nombre }}</p>
              </td>
              <td class="px-8 py-5">
                <p class="text-sm text-white/80">{{ inc.texto }}</p>
              </td>
              <td class="px-8 py-5">
                <span class="text-sm font-semibold text-amber-400">{{ formatDate(inc.fecha) }}</span>
              </td>
              <td class="px-8 py-5 max-w-xs">
                <p class="text-sm text-white/60 line-clamp-2">{{ inc.motivo }}</p>
              </td>
              <td class="px-8 py-5">
                <div class="flex justify-end opacity-0 group-hover:opacity-100 transition-all">
                  <button @click="deleteIncident(inc.id)" class="p-3 text-white/40 hover:text-tertiary hover:bg-white/10 rounded-xl transition-all" title="Eliminar">
                    <TrashIcon class="w-5 h-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación incidencias -->
      <div class="px-8 py-5 flex flex-wrap items-center justify-between gap-4 border-t border-white/5">
        <p class="text-xs font-bold text-white/30 tracking-widest uppercase">
          Mostrando {{ Math.min((incidentCurrentPage - 1) * INCIDENT_PAGE_SIZE + 1, filteredIncidents.length) }}–{{ Math.min(incidentCurrentPage * INCIDENT_PAGE_SIZE, filteredIncidents.length) }}
          de {{ filteredIncidents.length }} incidencia{{ filteredIncidents.length !== 1 ? 's' : '' }}
        </p>
        <div class="flex items-center gap-2">
          <button
            @click="incidentCurrentPage--"
            :disabled="incidentCurrentPage === 1"
            class="p-2 rounded-xl text-white/40 hover:text-white hover:bg-white/10 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
          >
            <ChevronLeftIcon class="w-5 h-5" />
          </button>
          <template v-for="page in totalIncidentPages" :key="page">
            <button
              v-if="totalIncidentPages <= 7 || Math.abs(page - incidentCurrentPage) <= 1 || page === 1 || page === totalIncidentPages"
              @click="incidentCurrentPage = page"
              :class="[
                'min-w-[36px] h-9 px-2 rounded-xl text-sm font-bold transition-all',
                page === incidentCurrentPage
                  ? 'bg-amber-400/80 text-white shadow-lg shadow-amber-400/20'
                  : 'text-white/40 hover:text-white hover:bg-white/10'
              ]"
            >{{ page }}</button>
            <span
              v-else-if="(page === incidentCurrentPage - 2 && page > 2) || (page === incidentCurrentPage + 2 && page < totalIncidentPages - 1)"
              class="text-white/30 px-1"
            >…</span>
          </template>
          <button
            @click="incidentCurrentPage++"
            :disabled="incidentCurrentPage === totalIncidentPages"
            class="p-2 rounded-xl text-white/40 hover:text-white hover:bg-white/10 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
          >
            <ChevronRightIcon class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>

    <!-- ============================================================
         MODAL INCIDENCIA
         ============================================================ -->
    <div v-if="showIncidentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeIncidentModal"></div>

      <div class="glass-card w-full max-w-lg rounded-[32px] p-4 md:p-8 relative z-10 border border-white/10 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-2xl font-bold text-white">Registrar Incidencia</h3>
          <button @click="closeIncidentModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all">
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <form @submit.prevent="submitIncident" class="space-y-5">
          <!-- Empleado -->
          <div class="space-y-2">
            <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Empleado <span class="text-tertiary">*</span></label>
            <select v-model="incidentForm.personnel_id" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-amber-400/50 focus:ring-1 focus:ring-amber-400/30 transition-all appearance-none">
              <option value="" disabled>Seleccionar empleado...</option>
              <option v-for="emp in personnel" :key="emp.id" :value="emp.id">
                {{ emp.nombres }} {{ emp.apellidos }}
              </option>
            </select>
          </div>

          <!-- Texto -->
          <div class="space-y-2">
            <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Texto <span class="text-tertiary">*</span></label>
            <input v-model="incidentForm.texto" type="text" required placeholder="Descripción breve de la incidencia"
              class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-amber-400/50 focus:ring-1 focus:ring-amber-400/30 transition-all" />
          </div>

          <!-- Fecha -->
          <div class="space-y-2">
            <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Fecha <span class="text-tertiary">*</span></label>
            <input v-model="incidentForm.fecha" type="date" required
              class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-amber-400/50 focus:ring-1 focus:ring-amber-400/30 transition-all" />
          </div>

          <!-- Motivo -->
          <div class="space-y-2">
            <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Motivo <span class="text-tertiary">*</span></label>
            <textarea v-model="incidentForm.motivo" rows="3" required placeholder="Describa el motivo de la incidencia..."
              class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-amber-400/50 focus:ring-1 focus:ring-amber-400/30 transition-all resize-none"></textarea>
          </div>

          <div class="pt-2 flex justify-end gap-4 border-t border-white/5">
            <button type="button" @click="closeIncidentModal" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">
              Cancelar
            </button>
            <button type="submit" :disabled="isSubmittingIncident" class="text-white py-4 px-10 rounded-2xl font-bold flex items-center gap-2 bg-amber-400/20 border border-amber-400/30 hover:bg-amber-400/30 text-amber-400 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
              <span v-if="isSubmittingIncident">Guardando...</span>
              <span v-else>Guardar Incidencia</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Fullscreen Image Viewer -->
    <div
      v-if="fullscreenImage"
      class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/90 backdrop-blur-md"
      @click="fullscreenImage = null"
    >
      <button class="absolute top-6 right-6 p-3 text-white/60 hover:text-white bg-white/10 hover:bg-white/20 rounded-full transition-all">
        <XMarkIcon class="w-8 h-8" />
      </button>
      <img :src="fullscreenImage" class="max-w-full max-h-[90vh] object-contain rounded-xl shadow-2xl" @click.stop />
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import {
  UsersIcon, CheckCircleIcon, BriefcaseIcon, BuildingOfficeIcon,
  PlusIcon, XMarkIcon, EyeIcon, PencilIcon, TrashIcon,
  ChevronLeftIcon, ChevronRightIcon, ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const BASE_URL = '/concretos-oriente/Backend/api/v1';

// ----------------------------------------------------------------
// State
// ----------------------------------------------------------------
const personnel  = ref([]);
const projects   = ref([]);
const puestos    = ref([]);
const incidents  = ref([]);
const loading    = ref(true);
const loadingIncidents = ref(false);

// Filtros y paginación de incidencias
const incidentSearch          = ref('');
const filterIncidentEmpleado  = ref('');
const filterIncidentFecha     = ref('');
const incidentCurrentPage     = ref(1);
const INCIDENT_PAGE_SIZE      = 10;

const showModal          = ref(false);
const showViewModal      = ref(false);
const showIncidentModal  = ref(false);
const isSubmitting       = ref(false);
const isSubmittingIncident = ref(false);
const isEditing      = ref(false);
const editingId      = ref(null);
const selectedEmp    = ref(null);
const fullscreenImage = ref(null);

// ----------------------------------------------------------------
// Filters & Pagination
// ----------------------------------------------------------------
const searchQuery    = ref('');
const filterTipo     = ref('');
const filterEstado   = ref('');
const filterProyecto = ref('');
const currentPage    = ref(1);
const PAGE_SIZE      = 10;

watch([searchQuery, filterTipo, filterEstado, filterProyecto], () => {
  currentPage.value = 1;
});

const filteredPersonnel = computed(() => {
  const today = new Date().toISOString().split('T')[0];
  const q = searchQuery.value.toLowerCase().trim();

  return personnel.value.filter(emp => {
    if (q) {
      const fullName = `${emp.nombres} ${emp.apellidos}`.toLowerCase();
      const puesto   = (emp.puesto || '').toLowerCase();
      const dpi      = (emp.dpi   || '').replace(/\s/g, '');
      if (!fullName.includes(q) && !puesto.includes(q) && !dpi.includes(q)) return false;
    }
    if (filterTipo.value && emp.tipo_empleado !== filterTipo.value) return false;
    if (filterEstado.value) {
      const isActivo = !emp.fecha_baja || emp.fecha_baja > today;
      if (filterEstado.value === 'Activo' && !isActivo)  return false;
      if (filterEstado.value === 'Baja'   &&  isActivo)  return false;
    }
    if (filterProyecto.value) {
      if (filterProyecto.value === '__sin__' && emp.proyecto_id) return false;
      if (filterProyecto.value !== '__sin__' && String(emp.proyecto_id) !== filterProyecto.value) return false;
    }
    return true;
  });
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredPersonnel.value.length / PAGE_SIZE)));

const paginatedPersonnel = computed(() => {
  const start = (currentPage.value - 1) * PAGE_SIZE;
  return filteredPersonnel.value.slice(start, start + PAGE_SIZE);
});

const activeFiltersCount = computed(() =>
  [searchQuery.value, filterTipo.value, filterEstado.value, filterProyecto.value].filter(Boolean).length
);

const resetFilters = () => {
  searchQuery.value    = '';
  filterTipo.value     = '';
  filterEstado.value   = '';
  filterProyecto.value = '';
  currentPage.value    = 1;
};

// ----------------------------------------------------------------
// Filtros y paginación de incidencias
// ----------------------------------------------------------------
watch([incidentSearch, filterIncidentEmpleado, filterIncidentFecha], () => {
  incidentCurrentPage.value = 1;
});

const filteredIncidents = computed(() => {
  const q = incidentSearch.value.toLowerCase().trim();
  return incidents.value.filter(inc => {
    if (q) {
      const texto  = (inc.texto  || '').toLowerCase();
      const motivo = (inc.motivo || '').toLowerCase();
      const nombre = (inc.empleado_nombre || '').toLowerCase();
      if (!texto.includes(q) && !motivo.includes(q) && !nombre.includes(q)) return false;
    }
    if (filterIncidentEmpleado.value && inc.personnel_id != filterIncidentEmpleado.value) return false;
    if (filterIncidentFecha.value && inc.fecha !== filterIncidentFecha.value) return false;
    return true;
  });
});

const totalIncidentPages = computed(() =>
  Math.max(1, Math.ceil(filteredIncidents.value.length / INCIDENT_PAGE_SIZE))
);

const paginatedIncidents = computed(() => {
  const start = (incidentCurrentPage.value - 1) * INCIDENT_PAGE_SIZE;
  return filteredIncidents.value.slice(start, start + INCIDENT_PAGE_SIZE);
});

const activeIncidentFiltersCount = computed(() =>
  [incidentSearch.value, filterIncidentEmpleado.value, filterIncidentFecha.value].filter(Boolean).length
);

const resetIncidentFilters = () => {
  incidentSearch.value         = '';
  filterIncidentEmpleado.value = '';
  filterIncidentFecha.value    = '';
  incidentCurrentPage.value    = 1;
};

const formData = ref({
  tipo_empleado:      '',
  nombres:            '',
  apellidos:          '',
  dpi:                '',
  nit:                '',
  telefono:           '',
  direccion:          '',
  puesto:             '',
  tipo_planilla:      '',
  salario_base:       '',
  tarifa_hora_extra:  '',
  diario_viaticos:    '',
  contacto_nombres:   '',
  contacto_numero:    '',
  cantidad_hijos:     '',
  nivel_academico:    '',
  fecha_nacimiento:   '',
  igss:               null,
  igss_numero:        '',
  fecha_contratacion: '',
  fecha_baja:         '',
  numero_cuenta:      '',
  nombre_banco:       '',
  proyecto_id:        null,
  foto:               null
});

// ----------------------------------------------------------------
// Stats computed
// ----------------------------------------------------------------
const stats = computed(() => {
  const total    = personnel.value.length;
  const today    = new Date().toISOString().split('T')[0];
  const activos  = personnel.value.filter(e => !e.fecha_baja || e.fecha_baja > today).length;
  const enPlanilla = personnel.value.filter(e => parseFloat(e.salario_base) > 0).length;
  const proyectos = new Set(
    personnel.value.filter(e => e.proyecto_id).map(e => e.proyecto_id)
  ).size;

  return [
    { label: 'Total Empleados',    value: total.toString(),      change: 'Total',    icon: UsersIcon,           color: 'text-primary',   bgColor: 'bg-primary/20' },
    { label: 'Activos',            value: activos.toString(),    change: 'Activos',  icon: CheckCircleIcon,     color: 'text-emerald-400', bgColor: 'bg-emerald-400/10' },
    { label: 'En Planilla',        value: enPlanilla.toString(), change: 'Con salario', icon: BriefcaseIcon,    color: 'text-amber-400',  bgColor: 'bg-amber-400/10' },
    { label: 'Proyectos Cubiertos', value: proyectos.toString(), change: 'Proyectos', icon: BuildingOfficeIcon, color: 'text-sky-400',    bgColor: 'bg-sky-400/10' },
  ];
});

// ----------------------------------------------------------------
// Lifecycle
// ----------------------------------------------------------------
const incidentForm = ref({ personnel_id: '', texto: '', fecha: '', motivo: '' });

onMounted(() => {
  fetchPersonnel();
  fetchProjects();
  fetchPuestos();
  fetchIncidents();
});

// ----------------------------------------------------------------
// Fetch
// ----------------------------------------------------------------
const fetchPersonnel = async () => {
  loading.value = true;
  try {
    const res    = await fetch(`${BASE_URL}/personnel`);
    const result = await res.json();
    if (result.status === 'success') {
      const fetchTime = Date.now();
      personnel.value = result.data.map(emp => ({ ...emp, _t: fetchTime }));
    }
  } catch (err) {
    console.error('Error fetching personnel:', err);
  } finally {
    loading.value = false;
  }
};

const fetchProjects = async () => {
  try {
    const res    = await fetch(`${BASE_URL}/projects`);
    const result = await res.json();
    if (result.status === 'success') {
      projects.value = result.data;
    }
  } catch (err) {
    console.error('Error fetching projects:', err);
  }
};

const fetchPuestos = async () => {
  try {
    const res    = await fetch(`${BASE_URL}/puestos`);
    const result = await res.json();
    if (result.status === 'success') {
      puestos.value = result.data;
    }
  } catch (err) {
    console.error('Error fetching puestos:', err);
  }
};

const fetchIncidents = async () => {
  loadingIncidents.value = true;
  try {
    const res    = await fetch(`${BASE_URL}/incidents`);
    const result = await res.json();
    if (result.status === 'success') incidents.value = result.data;
  } catch (err) {
    console.error('Error fetching incidents:', err);
  } finally {
    loadingIncidents.value = false;
  }
};

const openIncidentModal = () => {
  incidentForm.value = { personnel_id: '', texto: '', fecha: '', motivo: '' };
  showIncidentModal.value = true;
};

const closeIncidentModal = () => {
  showIncidentModal.value = false;
};

const submitIncident = async () => {
  isSubmittingIncident.value = true;
  try {
    const fd = new FormData();
    fd.append('personnel_id', incidentForm.value.personnel_id);
    fd.append('texto',        incidentForm.value.texto);
    fd.append('fecha',        incidentForm.value.fecha);
    fd.append('motivo',       incidentForm.value.motivo);

    const res    = await fetch(`${BASE_URL}/incidents`, { method: 'POST', body: fd });
    const result = await res.json();

    if (result.status === 'success') {
      await fetchIncidents();
      closeIncidentModal();
      Swal.fire({ ...swalBase, title: '¡Guardado!', text: 'Incidencia registrada correctamente.', icon: 'success' });
    } else {
      Swal.fire({ ...swalBase, title: 'Error', text: result.message || 'Error al guardar', icon: 'error' });
    }
  } catch (err) {
    console.error('Error submitting incident:', err);
    Swal.fire({ ...swalBase, title: 'Error', text: 'Error de conexión al servidor', icon: 'error' });
  } finally {
    isSubmittingIncident.value = false;
  }
};

const deleteIncident = async (id) => {
  const result = await Swal.fire({
    ...swalBase,
    title: '¿Eliminar incidencia?',
    text: 'Esta acción no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#f43f5e',
    cancelButtonColor:  '#475569',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText:  'Cancelar',
  });

  if (!result.isConfirmed) return;

  try {
    const res  = await fetch(`${BASE_URL}/incidents/${id}`, { method: 'DELETE' });
    const data = await res.json();

    if (data.status === 'success') {
      await fetchIncidents();
      Swal.fire({ ...swalBase, title: '¡Eliminado!', text: 'Incidencia eliminada correctamente.', icon: 'success' });
    } else {
      Swal.fire({ ...swalBase, title: 'Error', text: data.message || 'Error al eliminar', icon: 'error' });
    }
  } catch (err) {
    console.error('Error deleting incident:', err);
    Swal.fire({ ...swalBase, title: 'Error', text: 'Error de conexión al servidor', icon: 'error' });
  }
};

// ----------------------------------------------------------------
// Agregar nuevo tipo de puesto
// ----------------------------------------------------------------
const addNuevoPuesto = async () => {
  const { value: nombre, isConfirmed } = await Swal.fire({
    ...swalBase,
    title: 'Nuevo Tipo de Puesto',
    input: 'text',
    inputLabel: 'Nombre del puesto',
    inputPlaceholder: 'Ej. Jefe de Bodega',
    inputAttributes: { maxlength: 100, autocomplete: 'off' },
    showCancelButton: true,
    confirmButtonText: 'Agregar',
    cancelButtonText: 'Cancelar',
    inputValidator: (value) => {
      if (!value || !value.trim()) return 'El nombre del puesto es obligatorio.';
    }
  });

  if (!isConfirmed || !nombre?.trim()) return;

  try {
    const fd = new FormData();
    fd.append('nombre', nombre.trim());

    const res    = await fetch(`${BASE_URL}/puestos`, { method: 'POST', body: fd });
    const result = await res.json();

    if (result.status === 'success') {
      await fetchPuestos();
      formData.value.tipo_empleado = result.nombre;
      Swal.fire({ ...swalBase, title: '¡Listo!', text: `Puesto "${result.nombre}" agregado correctamente.`, icon: 'success', timer: 1800, showConfirmButton: false });
    } else {
      Swal.fire({ ...swalBase, title: 'Error', text: result.message || 'No se pudo agregar el puesto.', icon: 'error' });
    }
  } catch (err) {
    console.error('Error adding puesto:', err);
    Swal.fire({ ...swalBase, title: 'Error', text: 'Error de conexión al servidor', icon: 'error' });
  }
};

// ----------------------------------------------------------------
// Modal helpers
// ----------------------------------------------------------------
const openModal = () => {
  resetForm();
  isEditing.value = false;
  editingId.value = null;
  showModal.value = true;
};

const openEditModal = (emp) => {
  formData.value = {
    tipo_empleado:      emp.tipo_empleado      || '',
    nombres:            emp.nombres            || '',
    apellidos:          emp.apellidos          || '',
    dpi:                formatDpiValue(emp.dpi || ''),
    nit:                emp.nit                || '',
    telefono:           emp.telefono           || '',
    direccion:          emp.direccion          || '',
    puesto:             emp.puesto             || '',
    tipo_planilla:      emp.tipo_planilla      || '',
    salario_base:       emp.salario_base       || '',
    tarifa_hora_extra:  emp.tarifa_hora_extra  || '',
    diario_viaticos:    emp.diario_viaticos    || '',
    contacto_nombres:   emp.contacto_nombres   || '',
    contacto_numero:    emp.contacto_numero    || '',
    cantidad_hijos:     emp.cantidad_hijos     !== null && emp.cantidad_hijos !== undefined ? emp.cantidad_hijos : '',
    nivel_academico:    emp.nivel_academico    || '',
    fecha_nacimiento:   emp.fecha_nacimiento   || '',
    igss:               emp.igss !== null && emp.igss !== undefined ? parseInt(emp.igss) : null,
    igss_numero:        emp.igss_numero        || '',
    fecha_contratacion: emp.fecha_contratacion || '',
    fecha_baja:         emp.fecha_baja         || '',
    numero_cuenta:      emp.numero_cuenta      || '',
    nombre_banco:       emp.nombre_banco       || '',
    proyecto_id:        emp.proyecto_id        || null,
    foto:               null
  };
  isEditing.value = true;
  editingId.value = emp.id;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  resetForm();
};

const resetForm = () => {
  formData.value = {
    tipo_empleado:      '',
    nombres:            '',
    apellidos:          '',
    dpi:                '',
    nit:                '',
    telefono:           '',
    direccion:          '',
    puesto:             '',
    tipo_planilla:      '',
    salario_base:       '',
    tarifa_hora_extra:  '',
    diario_viaticos:    '',
    contacto_nombres:   '',
    contacto_numero:    '',
    cantidad_hijos:     '',
    nivel_academico:    '',
    fecha_nacimiento:   '',
    igss:               null,
    igss_numero:        '',
    fecha_contratacion: '',
    fecha_baja:         '',
    numero_cuenta:      '',
    nombre_banco:       '',
    proyecto_id:        null,
    foto:               null
  };
};

const openViewModal = (emp) => {
  selectedEmp.value  = emp;
  showViewModal.value = true;
};

const closeViewModal = () => {
  showViewModal.value = false;
  selectedEmp.value   = null;
};

const openImageFullScreen = (url) => {
  fullscreenImage.value = url;
};

// ----------------------------------------------------------------
// File input
// ----------------------------------------------------------------
const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (file) formData.value.foto = file;
};

// ----------------------------------------------------------------
// Formatters
// ----------------------------------------------------------------
const formatDpiValue = (raw) => {
  const digits = raw.replace(/\D/g, '').slice(0, 13);
  let out = '';
  if (digits.length > 0) out += digits.substring(0, 4);
  if (digits.length > 4) out += ' ' + digits.substring(4, 9);
  if (digits.length > 9) out += ' ' + digits.substring(9, 13);
  return out;
};

const formatDpi = (e) => {
  formData.value.dpi = formatDpiValue(e.target.value);
};

const formatPhone = (e) => {
  const digits = e.target.value.replace(/\D/g, '').slice(0, 8);
  let out = '';
  if (digits.length > 0) out += digits.substring(0, 4);
  if (digits.length > 4) out += '-' + digits.substring(4, 8);
  formData.value.telefono = out;
};

const formatContactoNumero = (e) => {
  const digits = e.target.value.replace(/\D/g, '').slice(0, 8);
  let out = '';
  if (digits.length > 0) out += digits.substring(0, 4);
  if (digits.length > 4) out += '-' + digits.substring(4, 8);
  formData.value.contacto_numero = out;
};

const formatCurrency = (value) => {
  if (!value && value !== 0) return '0.00';
  return parseFloat(value).toFixed(2);
};

const formatDate = (val) => {
  if (!val) return '';
  const [y, m, d] = val.split('-');
  return `${d}/${m}/${y}`;
};

// ----------------------------------------------------------------
// Utility helpers
// ----------------------------------------------------------------
const getInitials = (nombres, apellidos) => {
  const n = nombres  ? nombres.charAt(0).toUpperCase()  : '';
  const a = apellidos ? apellidos.charAt(0).toUpperCase() : '';
  return `${n}${a}`;
};

const getPhotoUrl = (emp) => {
  if (!emp || !emp.foto_path) return '';
  const timestamp = emp._t || Date.now();
  return `/concretos-oriente/Backend/${emp.foto_path}?t=${timestamp}`;
};

const getEstadoBadge = (emp) => {
  const today = new Date().toISOString().split('T')[0];
  if (!emp.fecha_baja || emp.fecha_baja > today) {
    return { label: 'Activo', color: 'bg-emerald-400/15 text-emerald-400 border-emerald-400/20' };
  }
  return { label: 'Baja', color: 'bg-white/10 text-white/50 border-white/10' };
};

const PUESTO_COLORS = {
  'Administrativo': 'bg-primary/20 text-primary border-primary/20',
  'Operador':       'bg-amber-400/15 text-amber-400 border-amber-400/20',
  'Piloto':         'bg-sky-400/15 text-sky-400 border-sky-400/20',
  'Contratista':    'bg-rose-400/15 text-rose-400 border-rose-400/20',
};

const getTipoEmpleadoBadge = (tipo) => {
  return { color: PUESTO_COLORS[tipo] || 'bg-violet-400/15 text-violet-400 border-violet-400/20' };
};

// ----------------------------------------------------------------
// Swal helper
// ----------------------------------------------------------------
const swalBase = {
  background: '#0f172a',
  color: '#fff',
  confirmButtonColor: '#6366f1',
  customClass: {
    popup:         'border border-white/10 rounded-3xl shadow-2xl',
    confirmButton: 'rounded-xl px-6 py-3 font-bold',
    cancelButton:  'rounded-xl px-6 py-3 font-bold'
  }
};

// ----------------------------------------------------------------
// CRUD
// ----------------------------------------------------------------
const deleteEmployee = async (id) => {
  const result = await Swal.fire({
    ...swalBase,
    title: '¿Estás seguro?',
    text: 'Esta acción no se puede deshacer y eliminará los datos y la foto del empleado.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#f43f5e',
    cancelButtonColor:  '#475569',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText:  'Cancelar',
  });

  if (!result.isConfirmed) return;

  try {
    const res = await fetch(`${BASE_URL}/personnel/${id}`, { method: 'DELETE' });
    const data = await res.json();

    if (data.status === 'success') {
      await fetchPersonnel();
      Swal.fire({ ...swalBase, title: '¡Eliminado!', text: 'El empleado ha sido eliminado correctamente.', icon: 'success' });
    } else {
      Swal.fire({ ...swalBase, title: 'Error', text: data.message || 'Error al eliminar', icon: 'error' });
    }
  } catch (err) {
    console.error('Error deleting:', err);
    Swal.fire({ ...swalBase, title: 'Error', text: 'Error de conexión al servidor', icon: 'error' });
  }
};

const submitForm = async () => {
  isSubmitting.value = true;

  const data = new FormData();
  data.append('tipo_empleado',      formData.value.tipo_empleado);
  data.append('nombres',            formData.value.nombres);
  data.append('apellidos',          formData.value.apellidos);
  data.append('dpi',                formData.value.dpi.replace(/\s/g, ''));
  data.append('nit',                formData.value.nit                || '');
  data.append('telefono',           formData.value.telefono           || '');
  data.append('direccion',          formData.value.direccion          || '');
  data.append('puesto',             formData.value.puesto);
  data.append('tipo_planilla',      formData.value.tipo_planilla);
  data.append('salario_base',       formData.value.salario_base);
  data.append('tarifa_hora_extra',  formData.value.tarifa_hora_extra  || '');
  data.append('diario_viaticos',    formData.value.diario_viaticos    || '');
  data.append('contacto_nombres',   formData.value.contacto_nombres   || '');
  data.append('contacto_numero',    formData.value.contacto_numero    || '');
  data.append('cantidad_hijos',     formData.value.cantidad_hijos !== '' ? formData.value.cantidad_hijos : '');
  data.append('nivel_academico',    formData.value.nivel_academico    || '');
  data.append('fecha_nacimiento',   formData.value.fecha_nacimiento   || '');
  data.append('igss',               formData.value.igss !== null ? formData.value.igss : '');
  data.append('igss_numero',        formData.value.igss === 1 ? (formData.value.igss_numero || '') : '');
  data.append('fecha_contratacion', formData.value.fecha_contratacion);
  data.append('fecha_baja',         formData.value.fecha_baja         || '');
  data.append('numero_cuenta',      formData.value.numero_cuenta      || '');
  data.append('nombre_banco',       formData.value.nombre_banco       || '');
  data.append('proyecto_id',        formData.value.proyecto_id !== null ? formData.value.proyecto_id : '');

  if (formData.value.foto) {
    data.append('foto', formData.value.foto);
  }

  try {
    const url = isEditing.value
      ? `${BASE_URL}/personnel/${editingId.value}`
      : `${BASE_URL}/personnel`;

    const res    = await fetch(url, { method: 'POST', body: data });
    const result = await res.json();

    if (result.status === 'success') {
      await fetchPersonnel();
      closeModal();
      Swal.fire({ ...swalBase, title: '¡Guardado!', text: 'Empleado guardado correctamente.', icon: 'success' });
    } else {
      Swal.fire({ ...swalBase, title: 'Error', text: result.message || 'Error al guardar', icon: 'error' });
    }
  } catch (err) {
    console.error('Error submitting:', err);
    Swal.fire({ ...swalBase, title: 'Error', text: 'Error de conexión al servidor', icon: 'error' });
  } finally {
    isSubmitting.value = false;
  }
};
</script>
