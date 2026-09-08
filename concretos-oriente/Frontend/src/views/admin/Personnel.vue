<template>
  <div class="pt-20 pb-10 px-4 md:px-10 md:pb-20 max-w-7xl mx-auto space-y-10 relative">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h2 class="text-4xl font-bold tracking-tight text-white mb-2">Gestión de RRHH</h2>
        <p class="text-white/60">Gestiona tu fuerza laboral, incidencias, pagos mensuales y expedientes de empleados.</p>
      </div>
      <div class="flex flex-wrap gap-3">
        <button
          @click="openPayrollModal()"
          class="glass-button text-white py-4 px-7 rounded-2xl font-bold flex items-center justify-center gap-2 border border-emerald-400/40 text-emerald-400 hover:bg-emerald-400/10 hover:-translate-y-0.5 active:translate-y-0 transition-all shadow-xl shadow-emerald-500/10"
        >
          <BanknotesIcon class="w-5 h-5" />
          Espacio de Planilla
        </button>
        <button
          @click="openIncidentModal()"
          class="glass-button text-white py-4 px-7 rounded-2xl font-bold flex items-center justify-center gap-2 border border-amber-400/30 text-amber-400 hover:bg-amber-400/10 hover:-translate-y-0.5 active:translate-y-0 transition-all"
        >
          <ExclamationTriangleIcon class="w-5 h-5" />
          Incidencia Empleado
        </button>
        <button
          @click="openModal()"
          class="glass-button-primary text-white py-4 px-8 rounded-2xl font-bold flex items-center justify-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 hover:-translate-y-0.5 active:translate-y-0 transition-all"
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
        class="glass-card p-8 rounded-[32px] flex flex-col justify-between h-44 cursor-pointer group hover:-translate-y-3 hover:scale-105 hover:shadow-[0_20px_40px_-15px_rgba(99,102,241,0.5)] transition-all duration-500" data-aos="zoom-in-up" data-aos-duration="1000"
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

    <!-- Table Section: Empleados -->
    <div class="glass-card rounded-[40px] overflow-hidden border border-white/10 transition-all duration-500" data-aos="zoom-in-up" data-aos-duration="1000">
      <!-- Filter Bar -->
      <div class="p-8 border-b border-white/5 space-y-4">
        <div class="flex flex-wrap items-center gap-3">
          <!-- Search -->
          <div class="flex items-center gap-2 bg-black/20 border border-white/10 rounded-2xl px-4 py-3 flex-1 min-w-[200px]">
            <MagnifyingGlassIcon class="w-4 h-4 text-white/30 flex-shrink-0" />
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

      <!-- Table Content -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-white/5 text-[10px] font-extrabold uppercase tracking-[0.2em] text-white/40 bg-white/[0.02]">
              <th class="px-8 py-6">Empleado</th>
              <th class="px-8 py-6">Teléfono</th>
              <th class="px-8 py-6">Tipo / Planilla</th>
              <th class="px-8 py-6">Salario Base</th>
              <th class="px-8 py-6">Estado</th>
              <th class="px-8 py-6 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-if="loading">
              <td colspan="6" class="px-8 py-12 text-center text-white/50">Cargando personal...</td>
            </tr>
            <tr v-else-if="filteredPersonnel.length === 0">
              <td colspan="6" class="px-8 py-12 text-center">
                <p class="text-white/40 font-semibold">No se encontraron empleados</p>
                <p v-if="activeFiltersCount > 0" class="text-white/25 text-sm mt-1">Prueba ajustando los filtros de búsqueda</p>
              </td>
            </tr>
            <tr
              v-for="emp in paginatedPersonnel"
              :key="emp.id"
              class="hover:bg-white/5 group transition-colors duration-300"
            >
              <!-- Empleado -->
              <td class="px-8 py-5">
                <div class="flex items-center gap-4">
                  <div
                    @click="emp.foto_path ? openImageFullScreen(getPhotoUrl(emp)) : null"
                    :class="['w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center overflow-hidden flex-shrink-0 border border-white/10 shadow-inner', emp.foto_path ? 'cursor-pointer hover:scale-110 transition-transform' : '']"
                  >
                    <img v-if="emp.foto_path" :src="getPhotoUrl(emp)" alt="Avatar" class="w-full h-full object-cover" />
                    <span v-else class="font-bold text-primary text-sm">{{ getInitials(emp.nombres, emp.apellidos) }}</span>
                  </div>
                  <div>
                    <p class="text-base font-bold text-white group-hover:text-primary transition-colors flex items-center gap-2">
                      {{ emp.nombres }} {{ emp.apellidos }}
                      <span v-if="emp.dpi_adjunto_path || emp.contrato_adjunto_path || emp.licencia_adjunto_path" title="Tiene documentos adjuntos" class="inline-flex items-center text-indigo-400">
                        <PaperClipIcon class="w-3.5 h-3.5" />
                      </span>
                    </p>
                    <p class="text-xs text-white/40 font-medium">
                      {{ emp.puesto }} · DPI: {{ emp.dpi }}
                    </p>
                  </div>
                </div>
              </td>

              <!-- Teléfono -->
              <td class="px-8 py-5">
                <span class="text-sm font-semibold text-white/80">{{ emp.telefono || '—' }}</span>
              </td>

              <!-- Tipo / Planilla -->
              <td class="px-8 py-5">
                <div class="space-y-1">
                  <span :class="`px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest border inline-block ${getTipoEmpleadoBadge(emp.tipo_empleado).color}`">
                    {{ emp.tipo_empleado }}
                  </span>
                  <p class="text-xs text-white/40">{{ emp.tipo_planilla }}</p>
                </div>
              </td>

              <!-- Salario Base -->
              <td class="px-8 py-5">
                <p class="text-sm font-bold text-white">Q {{ formatCurrency(emp.salario_base) }}</p>
                <p v-if="emp.tarifa_hora_extra" class="text-[11px] text-white/40">H.E: Q {{ formatCurrency(emp.tarifa_hora_extra) }}/hr</p>
              </td>

              <!-- Estado -->
              <td class="px-8 py-5">
                <span :class="`px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest border ${getEstadoBadge(emp).color}`">
                  {{ getEstadoBadge(emp).label }}
                </span>
              </td>

              <!-- Acciones -->
              <td class="px-8 py-5">
                <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-all duration-300">
                  <button @click="openQuickPayroll(emp)" class="p-3 text-emerald-400/80 hover:text-emerald-400 hover:bg-emerald-400/10 rounded-xl transition-all" title="Pagar Planilla">
                    <BanknotesIcon class="w-5 h-5" />
                  </button>
                  <button @click="openViewModal(emp)" class="p-3 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all" title="Ver detalles">
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

      <!-- Pagination -->
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
         SECCIÓN ESPACIO DE PLANILLA (HISTORIAL DE PAGOS MENSUALES)
         ============================================================ -->
    <div class="glass-card rounded-[40px] overflow-hidden border border-emerald-500/20 transition-all duration-500 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
      <!-- Header -->
      <div class="p-8 border-b border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-emerald-950/10">
        <div class="flex items-center gap-4">
          <div class="p-3 rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-400/30">
            <BanknotesIcon class="w-7 h-7" />
          </div>
          <div>
            <h3 class="text-2xl font-bold text-white flex items-center gap-3">
              Espacio de Planilla
              <span class="text-xs px-3 py-1 rounded-full bg-emerald-400/20 text-emerald-400 border border-emerald-400/30 font-bold uppercase tracking-wider">Pagos Mensuales</span>
            </h3>
            <p class="text-white/50 text-sm mt-0.5">Control y registro de pagos mensuales, cálculo automático de sueldo base, horas extra y viáticos.</p>
          </div>
        </div>
        <button
          @click="openPayrollModal()"
          class="flex items-center justify-center gap-2 text-sm font-bold text-emerald-300 bg-emerald-500/20 hover:bg-emerald-500/30 px-6 py-3.5 rounded-2xl border border-emerald-400/40 transition-all shadow-lg shadow-emerald-500/10"
        >
          <PlusIcon class="w-5 h-5" />
          Registrar Pago Mensual
        </button>
      </div>

      <!-- Filtros de Planilla -->
      <div class="p-6 border-b border-white/5 bg-white/[0.01]">
        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-2 bg-black/20 border border-white/10 rounded-2xl px-4 py-2.5 flex-1 min-w-[200px]">
            <MagnifyingGlassIcon class="w-4 h-4 text-white/30 flex-shrink-0" />
            <input
              v-model="payrollSearch"
              type="text"
              placeholder="Buscar pago por empleado o periodo..."
              class="bg-transparent flex-1 text-sm text-white placeholder-white/30 focus:outline-none"
            />
          </div>

          <select v-model="filterPayrollEmpleado" class="bg-black/20 border border-white/10 rounded-2xl px-4 py-2.5 text-sm text-white/80 focus:outline-none focus:border-emerald-400/50 transition-all appearance-none w-full md:w-auto md:min-w-[180px]">
            <option value="">Todos los empleados</option>
            <option v-for="emp in personnel" :key="emp.id" :value="String(emp.id)">
              {{ emp.nombres }} {{ emp.apellidos }}
            </option>
          </select>

          <button
            v-if="payrollSearch || filterPayrollEmpleado"
            @click="payrollSearch = ''; filterPayrollEmpleado = ''"
            class="flex items-center gap-1.5 text-white/50 hover:text-white text-xs font-bold px-3 py-2.5 rounded-xl hover:bg-white/5 border border-white/10 transition-all"
          >
            <XMarkIcon class="w-4 h-4" />
            Limpiar
          </button>

          <span class="ml-auto text-xs font-bold text-emerald-400/70 uppercase tracking-widest">
            {{ filteredPayrollPayments.length }} pago{{ filteredPayrollPayments.length !== 1 ? 's' : '' }} registrado{{ filteredPayrollPayments.length !== 1 ? 's' : '' }}
          </span>
        </div>
      </div>

      <!-- Tabla de Pagos de Planilla -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-white/5 text-[10px] font-extrabold uppercase tracking-[0.2em] text-white/40 bg-white/[0.02]">
              <th class="px-8 py-5">Colaborador</th>
              <th class="px-6 py-5">Periodo / Fecha</th>
              <th class="px-6 py-5 text-center">Días Trab.</th>
              <th class="px-6 py-5">Salario Proporcional</th>
              <th class="px-6 py-5">Horas Extra</th>
              <th class="px-6 py-5">Viáticos</th>
              <th class="px-8 py-5 text-right font-black text-emerald-400">Total a Pagar</th>
              <th class="px-6 py-5 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-if="loadingPayroll">
              <td colspan="8" class="px-8 py-10 text-center text-white/50">Cargando registros de planilla...</td>
            </tr>
            <tr v-else-if="filteredPayrollPayments.length === 0">
              <td colspan="8" class="px-8 py-12 text-center">
                <p class="text-white/40 font-semibold">Sin pagos de planilla registrados</p>
                <p class="text-white/25 text-sm mt-1">Haz clic en "Registrar Pago Mensual" para crear el primero.</p>
              </td>
            </tr>
            <tr
              v-for="p in paginatedPayrollPayments"
              :key="p.id"
              class="hover:bg-white/5 group transition-colors duration-300"
            >
              <td class="px-8 py-5">
                <p class="text-sm font-bold text-white">{{ p.empleado_nombre }}</p>
                <p class="text-xs text-white/40">{{ p.empleado_puesto || 'Colaborador' }} · DPI: {{ p.empleado_dpi || '—' }}</p>
              </td>
              <td class="px-6 py-5">
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-white/5 border border-white/10 text-white/90 inline-block mb-1">
                  {{ p.periodo }}
                </span>
                <p class="text-xs text-white/40">{{ formatDate(p.fecha_pago) }}</p>
              </td>
              <td class="px-6 py-5 text-center">
                <span class="text-sm font-bold text-white/90">{{ p.dias_trabajados }}</span>
                <span class="text-[10px] text-white/40 block">días</span>
              </td>
              <td class="px-6 py-5">
                <p class="text-sm font-semibold text-white">Q {{ formatCurrency(p.salario_base_calculado) }}</p>
                <p class="text-[10px] text-white/40">Base: Q {{ formatCurrency(p.salario_base) }}</p>
              </td>
              <td class="px-6 py-5">
                <div v-if="parseFloat(p.horas_extras) > 0">
                  <p class="text-sm font-semibold text-amber-400">Q {{ formatCurrency(p.total_horas_extras) }}</p>
                  <p class="text-[10px] text-white/40">{{ p.horas_extras }} hrs × Q {{ formatCurrency(p.tarifa_hora_extra) }}</p>
                </div>
                <span v-else class="text-xs text-white/30">—</span>
              </td>
              <td class="px-6 py-5">
                <div v-if="parseFloat(p.monto_viaticos) > 0">
                  <p class="text-sm font-semibold text-sky-400">Q {{ formatCurrency(p.monto_viaticos) }}</p>
                  <p v-if="p.observaciones_viaticos" class="text-[10px] text-white/40 truncate max-w-[120px]" :title="p.observaciones_viaticos">
                    {{ p.observaciones_viaticos }}
                  </p>
                </div>
                <span v-else class="text-xs text-white/30">—</span>
              </td>
              <td class="px-8 py-5 text-right">
                <span class="text-base font-black text-emerald-400 tracking-tight">
                  Q {{ formatCurrency(p.total_pagar) }}
                </span>
              </td>
              <td class="px-6 py-5 text-right">
                <div class="flex justify-end gap-1">
                  <button
                    @click="openReceiptModal(p)"
                    class="p-2.5 text-white/60 hover:text-white hover:bg-white/10 rounded-xl transition-all"
                    title="Ver Boleta de Pago"
                  >
                    <DocumentTextIcon class="w-5 h-5 text-emerald-400" />
                  </button>
                  <button
                    @click="deletePayrollPayment(p.id)"
                    class="p-2.5 text-white/40 hover:text-rose-400 hover:bg-white/10 rounded-xl transition-all"
                    title="Eliminar Pago"
                  >
                    <TrashIcon class="w-5 h-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación Planilla -->
      <div class="px-8 py-4 flex flex-wrap items-center justify-between gap-4 border-t border-white/5">
        <p class="text-xs font-bold text-white/30 tracking-widest uppercase">
          Mostrando {{ Math.min((payrollCurrentPage - 1) * PAYROLL_PAGE_SIZE + 1, filteredPayrollPayments.length) }}–{{ Math.min(payrollCurrentPage * PAYROLL_PAGE_SIZE, filteredPayrollPayments.length) }}
          de {{ filteredPayrollPayments.length }} pago{{ filteredPayrollPayments.length !== 1 ? 's' : '' }}
        </p>
        <div class="flex items-center gap-2">
          <button
            @click="payrollCurrentPage--"
            :disabled="payrollCurrentPage === 1"
            class="p-2 rounded-xl text-white/40 hover:text-white hover:bg-white/10 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
          >
            <ChevronLeftIcon class="w-5 h-5" />
          </button>
          <template v-for="page in totalPayrollPages" :key="page">
            <button
              v-if="totalPayrollPages <= 7 || Math.abs(page - payrollCurrentPage) <= 1 || page === 1 || page === totalPayrollPages"
              @click="payrollCurrentPage = page"
              :class="[
                'min-w-[36px] h-9 px-2 rounded-xl text-sm font-bold transition-all',
                page === payrollCurrentPage
                  ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/20'
                  : 'text-white/40 hover:text-white hover:bg-white/10'
              ]"
            >{{ page }}</button>
          </template>
          <button
            @click="payrollCurrentPage++"
            :disabled="payrollCurrentPage === totalPayrollPages"
            class="p-2 rounded-xl text-white/40 hover:text-white hover:bg-white/10 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
          >
            <ChevronRightIcon class="w-5 h-5" />
          </button>
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

      <!-- Filtros incidencias -->
      <div class="p-8 border-b border-white/5 space-y-4">
        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-2 bg-black/20 border border-white/10 rounded-2xl px-4 py-3 flex-1 min-w-[200px]">
            <MagnifyingGlassIcon class="w-4 h-4 text-white/30 flex-shrink-0" />
            <input
              v-model="incidentSearch"
              type="text"
              placeholder="Buscar por texto, motivo o empleado..."
              class="bg-transparent flex-1 text-sm text-white placeholder-white/30 focus:outline-none"
            />
          </div>

          <select v-model="filterIncidentEmpleado" class="bg-black/20 border border-white/10 rounded-2xl px-4 py-3 text-sm text-white/80 focus:outline-none focus:border-amber-400/50 transition-all appearance-none w-full md:w-auto md:min-w-[180px]">
            <option value="">Todos los empleados</option>
            <option v-for="emp in personnel" :key="emp.id" :value="emp.id">
              {{ emp.nombres }} {{ emp.apellidos }}
            </option>
          </select>

          <input
            v-model="filterIncidentFecha"
            type="date"
            class="bg-black/20 border border-white/10 rounded-2xl px-4 py-3 text-sm text-white/80 focus:outline-none focus:border-amber-400/50 transition-all w-full md:w-auto"
          />

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

      <!-- Tabla incidencias -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-white/5 text-[10px] font-extrabold uppercase tracking-[0.2em] text-white/40 bg-white/[0.02]">
              <th class="px-8 py-6">Empleado</th>
              <th class="px-8 py-6">Texto</th>
              <th class="px-8 py-6">Fecha</th>
              <th class="px-8 py-6">Motivo</th>
              <th class="px-8 py-6 text-center">Adjunto</th>
              <th class="px-8 py-6 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-if="loadingIncidents">
              <td colspan="6" class="px-8 py-8 text-center text-white/50">Cargando incidencias...</td>
            </tr>
            <tr v-else-if="filteredIncidents.length === 0">
              <td colspan="6" class="px-8 py-12 text-center">
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
              <td class="px-8 py-5 text-center">
                <a
                  v-if="inc.adjunto_path"
                  :href="getDocumentUrl(inc.adjunto_path)"
                  target="_blank"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-400/10 text-amber-400 border border-amber-400/20 text-xs font-bold hover:bg-amber-400/20 transition-all"
                  title="Ver documento adjunto"
                >
                  <PaperClipIcon class="w-3.5 h-3.5" />
                  Ver Adjunto
                </a>
                <span v-else class="text-xs text-white/20">—</span>
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
         MODAL CREAR / EDITAR EMPLEADO
         ============================================================ -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>

      <div class="glass-card w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-[32px] p-4 md:p-8 relative z-10 border border-white/10 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-2xl font-bold text-white">
            {{ isEditing ? 'Editar Empleado' : 'Nuevo Empleado' }}
          </h3>
          <button @click="closeModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all">
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <form @submit.prevent="submitForm" class="space-y-8">

          <!-- SECCIÓN 1: Datos Personales -->
          <div>
            <p class="text-xs font-bold text-white/30 uppercase tracking-[0.25em] mb-4">Datos Personales</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

              <!-- DPI + Botón RENAP -->
              <div class="space-y-2 md:col-span-2">
                <div class="flex items-center justify-between">
                  <label class="text-xs font-bold text-white/50 uppercase tracking-wider">
                    DPI (CUI) <span class="text-tertiary">*</span>
                    <span class="text-white/30 normal-case font-normal ml-2">(13 dígitos - Autocompleta datos con RENAP)</span>
                  </label>
                  <span v-if="renapSuccess" class="text-xs text-emerald-400 font-bold flex items-center gap-1">
                    <CheckCircleIcon class="w-4 h-4" /> Datos de RENAP cargados
                  </span>
                </div>
                <div class="flex gap-2">
                  <div class="relative flex-1">
                    <input
                      v-model="formData.dpi"
                      @input="onDpiInput"
                      type="text"
                      required
                      placeholder="0000 00000 0000"
                      maxlength="15"
                      class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all font-mono tracking-wider text-base"
                    />
                    <div v-if="loadingRenap" class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-2 text-primary text-xs font-bold">
                      <svg class="animate-spin h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      Consultando RENAP...
                    </div>
                  </div>
                  <button
                    type="button"
                    @click="consultarRenap(true)"
                    :disabled="loadingRenap || getDpiClean().length !== 13"
                    class="px-5 py-4 rounded-2xl font-bold text-xs uppercase tracking-wider flex items-center gap-2 bg-primary/20 text-primary border border-primary/30 hover:bg-primary/30 disabled:opacity-40 disabled:cursor-not-allowed transition-all whitespace-nowrap"
                  >
                    <MagnifyingGlassIcon class="w-4 h-4" />
                    Consultar RENAP
                  </button>
                </div>
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
                <input v-model="formData.apellidos" type="text" required placeholder="Ej. Pérez Gómez"
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" />
              </div>

              <!-- Tipo de empleado -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Tipo de Puesto <span class="text-tertiary">*</span></label>
                <div class="flex gap-2">
                  <select v-model="formData.tipo_empleado" required class="flex-1 bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all appearance-none">
                    <option value="" disabled>Seleccionar tipo...</option>
                    <option v-for="p in puestos" :key="p.id" :value="p.nombre">{{ p.nombre }}</option>
                  </select>
                  <button type="button" @click="addNuevoPuesto" title="Agregar nuevo tipo de puesto"
                    class="px-4 py-4 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 text-white/70 hover:text-white transition-all flex items-center justify-center flex-shrink-0">
                    <PlusIcon class="w-5 h-5" />
                  </button>
                </div>
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

              <!-- Estado Civil -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Estado Civil</label>
                <select v-model="formData.estado_civil" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all appearance-none">
                  <option value="">Seleccionar...</option>
                  <option value="Soltero(a)">Soltero(a)</option>
                  <option value="Casado(a)">Casado(a)</option>
                  <option value="Unido(a)">Unido(a)</option>
                  <option value="Divorciado(a)">Divorciado(a)</option>
                  <option value="Viudo(a)">Viudo(a)</option>
                </select>
              </div>

              <!-- Departamento de Nacimiento -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Departamento de Nacimiento</label>
                <input v-model="formData.depto_nacimiento" type="text" placeholder="Ej. El Progreso"
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" />
              </div>

              <!-- Municipio de Nacimiento -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Municipio de Nacimiento</label>
                <input v-model="formData.muni_nacimiento" type="text" placeholder="Ej. Sanarate"
                  class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all" />
              </div>

              <!-- Cantidad de Hijos -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Cantidad de Hijos</label>
                <input v-model="formData.cantidad_hijos" @input="onCantidadHijosChange" type="number" min="0" placeholder="0"
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

              <!-- Espacio dinámico para Edades de Hijos -->
              <div v-if="parsedCantidadHijos > 0" class="space-y-3 md:col-span-2 bg-white/5 p-5 rounded-3xl border border-white/10">
                <div class="flex items-center justify-between">
                  <label class="text-xs font-bold text-primary uppercase tracking-wider flex items-center gap-2">
                    <UsersIcon class="w-4 h-4" />
                    Edades de los Hijos ({{ parsedCantidadHijos }} hijo{{ parsedCantidadHijos !== 1 ? 's' : '' }})
                  </label>
                  <span class="text-[11px] text-white/40">Ingresa la edad en años de cada uno</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                  <div v-for="idx in parsedCantidadHijos" :key="idx" class="space-y-1">
                    <span class="text-[10px] text-white/50 font-bold uppercase tracking-wider">Hijo #{{ idx }}</span>
                    <input
                      v-model="formData.edades_hijos_list[idx - 1]"
                      type="number"
                      min="0"
                      max="100"
                      placeholder="Edad (años)"
                      class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-primary/50 transition-all"
                    />
                  </div>
                </div>
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

          <!-- SECCIÓN 5: Fotografía y Documentos Adjuntos -->
          <div>
            <p class="text-xs font-bold text-white/30 uppercase tracking-[0.25em] mb-4">Fotografía y Documentos Adjuntos</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <!-- Foto -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">
                  Foto del Empleado (PNG, JPG, JPEG)
                </label>
                <input @change="handleFileChange" type="file" accept=".png,.jpg,.jpeg"
                  class="w-full text-white/60 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-primary/20 file:text-primary hover:file:bg-primary/30 file:transition-all cursor-pointer bg-black/20 border border-white/10 rounded-2xl p-2" />
              </div>

              <!-- Adjuntar DPI -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider flex items-center gap-1.5">
                  <IdentificationIcon class="w-4 h-4 text-indigo-400" />
                  Adjuntar DPI (PDF / Imagen)
                </label>
                <input @change="handleDpiFileChange" type="file" accept=".pdf,.png,.jpg,.jpeg"
                  class="w-full text-white/60 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-indigo-500/20 file:text-indigo-400 hover:file:bg-indigo-500/30 file:transition-all cursor-pointer bg-black/20 border border-white/10 rounded-2xl p-2" />
              </div>

              <!-- Adjuntar Contrato -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider flex items-center gap-1.5">
                  <DocumentTextIcon class="w-4 h-4 text-emerald-400" />
                  Adjuntar Contrato (PDF / Doc / Imagen)
                </label>
                <input @change="handleContratoFileChange" type="file" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg"
                  class="w-full text-white/60 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-emerald-500/20 file:text-emerald-400 hover:file:bg-emerald-500/30 file:transition-all cursor-pointer bg-black/20 border border-white/10 rounded-2xl p-2" />
              </div>

              <!-- Adjuntar Licencia -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider flex items-center gap-1.5">
                  <DocumentCheckIcon class="w-4 h-4 text-amber-400" />
                  Adjuntar Licencia (PDF / Imagen)
                </label>
                <input @change="handleLicenciaFileChange" type="file" accept=".pdf,.png,.jpg,.jpeg"
                  class="w-full text-white/60 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-amber-500/20 file:text-amber-400 hover:file:bg-amber-500/30 file:transition-all cursor-pointer bg-black/20 border border-white/10 rounded-2xl p-2" />
              </div>
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
         MODAL VISUALIZAR EMPLEADO
         ============================================================ -->
    <div v-if="showViewModal && selectedEmp" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeViewModal"></div>

      <div class="glass-card w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-[32px] p-6 md:p-8 relative z-10 border border-white/10 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="flex items-center justify-between mb-8">
          <div>
            <h3 class="text-2xl font-bold text-white">Detalles del Empleado</h3>
            <p class="text-white/40 text-xs mt-0.5">Expediente completo y documentos adjuntos</p>
          </div>
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
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Estado Civil</p>
              <p class="text-base font-semibold text-white/90">{{ selectedEmp.estado_civil || 'No registrado' }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Lugar de Nacimiento</p>
              <p class="text-base font-semibold text-white/90">
                {{ [selectedEmp.muni_nacimiento, selectedEmp.depto_nacimiento].filter(Boolean).join(', ') || 'No registrado' }}
              </p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Cantidad de Hijos</p>
              <p class="text-base font-semibold text-white/90">{{ selectedEmp.cantidad_hijos !== null && selectedEmp.cantidad_hijos !== undefined ? selectedEmp.cantidad_hijos : 'No registrado' }}</p>
            </div>

            <!-- Edades de Hijos si aplica -->
            <div v-if="selectedEmp.edades_hijos || (selectedEmp.cantidad_hijos > 0)" class="sm:col-span-2 bg-white/5 p-3.5 rounded-2xl border border-white/5">
              <p class="text-[10px] font-bold text-primary uppercase tracking-widest mb-1.5 flex items-center gap-1.5">
                <UsersIcon class="w-3.5 h-3.5" /> Edades de los Hijos
              </p>
              <p class="text-sm font-semibold text-white/90">
                {{ formatEdadesHijos(selectedEmp.edades_hijos) }}
              </p>
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
              <p class="text-xl font-bold text-white">{{ selectedEmp.tarifa_hora_extra ? 'Q ' + formatCurrency(selectedEmp.tarifa_hora_extra) + '/hr' : 'No aplica' }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Fecha de Contratación</p>
              <p class="text-base font-semibold text-white/90">{{ formatDate(selectedEmp.fecha_contratacion) }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Fecha de Baja</p>
              <p class="text-base font-semibold text-white/90">{{ selectedEmp.fecha_baja ? formatDate(selectedEmp.fecha_baja) : 'Activo' }}</p>
            </div>

            <!-- DOCUMENTOS ADJUNTOS -->
            <div class="sm:col-span-2 border-t border-white/5 pt-4">
              <p class="text-[10px] font-bold text-white/30 uppercase tracking-[0.25em] mb-3">Documentos Adjuntos</p>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- DPI -->
                <div class="p-3 bg-white/5 rounded-2xl border border-white/5 flex flex-col justify-between gap-2">
                  <div>
                    <span class="text-[10px] text-white/40 uppercase font-bold tracking-wider block">DPI Escaneado</span>
                    <span class="text-xs font-semibold text-white/80">{{ selectedEmp.dpi_adjunto_path ? 'Disponible' : 'No adjunto' }}</span>
                  </div>
                  <a
                    v-if="selectedEmp.dpi_adjunto_path"
                    :href="getDocumentUrl(selectedEmp.dpi_adjunto_path)"
                    target="_blank"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-indigo-500/20 text-indigo-400 border border-indigo-400/30 text-xs font-bold hover:bg-indigo-500/30 transition-all"
                  >
                    <ArrowTopRightOnSquareIcon class="w-4 h-4" />
                    Abrir DPI
                  </a>
                </div>

                <!-- Contrato -->
                <div class="p-3 bg-white/5 rounded-2xl border border-white/5 flex flex-col justify-between gap-2">
                  <div>
                    <span class="text-[10px] text-white/40 uppercase font-bold tracking-wider block">Contrato Laboral</span>
                    <span class="text-xs font-semibold text-white/80">{{ selectedEmp.contrato_adjunto_path ? 'Disponible' : 'No adjunto' }}</span>
                  </div>
                  <a
                    v-if="selectedEmp.contrato_adjunto_path"
                    :href="getDocumentUrl(selectedEmp.contrato_adjunto_path)"
                    target="_blank"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-400/30 text-xs font-bold hover:bg-emerald-500/30 transition-all"
                  >
                    <ArrowTopRightOnSquareIcon class="w-4 h-4" />
                    Abrir Contrato
                  </a>
                </div>

                <!-- Licencia -->
                <div class="p-3 bg-white/5 rounded-2xl border border-white/5 flex flex-col justify-between gap-2">
                  <div>
                    <span class="text-[10px] text-white/40 uppercase font-bold tracking-wider block">Licencia de Conducir</span>
                    <span class="text-xs font-semibold text-white/80">{{ selectedEmp.licencia_adjunto_path ? 'Disponible' : 'No adjunto' }}</span>
                  </div>
                  <a
                    v-if="selectedEmp.licencia_adjunto_path"
                    :href="getDocumentUrl(selectedEmp.licencia_adjunto_path)"
                    target="_blank"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-amber-500/20 text-amber-400 border border-amber-400/30 text-xs font-bold hover:bg-amber-500/30 transition-all"
                  >
                    <ArrowTopRightOnSquareIcon class="w-4 h-4" />
                    Abrir Licencia
                  </a>
                </div>
              </div>
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

            <!-- Datos bancarios -->
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
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================
         MODAL ESPACIO DE PLANILLA (PAGO MENSUAL)
         ============================================================ -->
    <div v-if="showPayrollModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closePayrollModal"></div>

      <div class="glass-card w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-[32px] p-6 md:p-8 relative z-10 border border-emerald-500/30 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-white/10">
          <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-400/30">
              <BanknotesIcon class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-2xl font-bold text-white">Espacio de Planilla</h3>
              <p class="text-white/50 text-xs">Registrar pago mensual con cálculo automático</p>
            </div>
          </div>
          <button @click="closePayrollModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all">
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <form @submit.prevent="submitPayrollPayment" class="space-y-6">

          <!-- 1. Escoger Colaborador -->
          <div class="space-y-2">
            <label class="text-xs font-bold text-white/60 uppercase tracking-wider flex items-center justify-between">
              <span>1. Escoger Colaborador <span class="text-tertiary">*</span></span>
              <span v-if="selectedPayrollEmp" class="text-emerald-400 font-bold normal-case text-xs">
                Puesto: {{ selectedPayrollEmp.puesto }} · Tipo: {{ selectedPayrollEmp.tipo_planilla }}
              </span>
            </label>
            <select
              v-model="payrollForm.personnel_id"
              @change="onPayrollPersonnelChange"
              required
              class="w-full bg-black/30 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-emerald-400/60 focus:ring-1 focus:ring-emerald-400/30 transition-all appearance-none text-base"
            >
              <option value="" disabled>Seleccione un colaborador...</option>
              <option v-for="emp in personnel" :key="emp.id" :value="emp.id">
                {{ emp.nombres }} {{ emp.apellidos }} — [{{ emp.puesto }}] — Salario Base: Q {{ formatCurrency(emp.salario_base) }}
              </option>
            </select>
          </div>

          <!-- Quick badges for collaborator config -->
          <div v-if="selectedPayrollEmp" class="grid grid-cols-3 gap-3 bg-white/5 p-4 rounded-2xl border border-white/10">
            <div>
              <span class="text-[10px] text-white/40 uppercase font-bold tracking-wider block">Salario Base Mensual</span>
              <span class="text-sm font-bold text-white">Q {{ formatCurrency(selectedPayrollEmp.salario_base) }}</span>
            </div>
            <div>
              <span class="text-[10px] text-white/40 uppercase font-bold tracking-wider block">Tarifa Hora Extra</span>
              <span class="text-sm font-bold text-amber-400">
                {{ selectedPayrollEmp.tarifa_hora_extra ? 'Q ' + formatCurrency(selectedPayrollEmp.tarifa_hora_extra) + '/hr' : 'No definida' }}
              </span>
            </div>
            <div>
              <span class="text-[10px] text-white/40 uppercase font-bold tracking-wider block">Diario Viáticos</span>
              <span class="text-sm font-bold text-sky-400">
                {{ selectedPayrollEmp.diario_viaticos ? 'Q ' + formatCurrency(selectedPayrollEmp.diario_viaticos) : 'No definido' }}
              </span>
            </div>
          </div>

          <!-- 2. Periodo y Fecha de Pago -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/60 uppercase tracking-wider">Periodo (Mes / Año) <span class="text-tertiary">*</span></label>
              <input
                v-model="payrollForm.periodo"
                type="text"
                required
                placeholder="Ej. Septiembre 2026 o 2026-09"
                class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-3.5 text-white placeholder-white/20 focus:outline-none focus:border-emerald-400/50 transition-all"
              />
            </div>
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/60 uppercase tracking-wider">Fecha de Pago <span class="text-tertiary">*</span></label>
              <input
                v-model="payrollForm.fecha_pago"
                type="date"
                required
                class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-3.5 text-white focus:outline-none focus:border-emerald-400/50 transition-all"
              />
            </div>
          </div>

          <!-- 3. Días Trabajados (Cálculo automático salario proporcional) -->
          <div class="space-y-2 bg-white/5 p-4 rounded-2xl border border-white/5">
            <div class="flex items-center justify-between">
              <label class="text-xs font-bold text-white/70 uppercase tracking-wider">
                2. Días Trabajados en el Mes <span class="text-tertiary">*</span>
              </label>
              <span class="text-xs text-white/40">Base mes: 30 días</span>
            </div>
            <div class="flex items-center gap-4">
              <input
                v-model.number="payrollForm.dias_trabajados"
                type="number"
                min="1"
                max="31"
                required
                class="w-32 bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white font-bold text-lg focus:outline-none focus:border-emerald-400/50 text-center"
              />
              <div class="flex-1 text-sm text-white/70">
                Salario proporcional calculado:
                <span class="text-emerald-400 font-bold block text-base">
                  Q {{ formatCurrency(payrollCalculations.salarioBaseCalculado) }}
                </span>
                <span class="text-[11px] text-white/40">
                  (Q {{ formatCurrency(payrollCalculations.salarioBase) }} / 30) × {{ payrollForm.dias_trabajados || 0 }} días
                </span>
              </div>
            </div>
          </div>

          <!-- 4. Horas Extra (Tiene Horas Extra -> Sí -> Cantidad de Horas) -->
          <div class="space-y-3 bg-amber-950/15 p-4 rounded-2xl border border-amber-400/20">
            <div class="flex items-center justify-between">
              <label class="text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center gap-2">
                <ClockIcon class="w-4 h-4" />
                3. ¿Tiene Horas Extras?
              </label>
              <div class="flex items-center gap-4">
                <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-white/80">
                  <input type="radio" v-model="payrollForm.tiene_horas_extras" :value="true" class="accent-amber-400 w-4 h-4" />
                  Sí
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-white/80">
                  <input type="radio" v-model="payrollForm.tiene_horas_extras" :value="false" class="accent-amber-400 w-4 h-4" />
                  No
                </label>
              </div>
            </div>

            <div v-if="payrollForm.tiene_horas_extras" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-amber-400/10">
              <div class="space-y-1">
                <label class="text-[11px] font-bold text-white/60 uppercase">Cantidad de Horas Extras</label>
                <input
                  v-model.number="payrollForm.horas_extras"
                  type="number"
                  step="0.5"
                  min="0"
                  placeholder="0"
                  class="w-full bg-black/30 border border-amber-400/30 rounded-xl px-4 py-2.5 text-white font-bold focus:outline-none focus:border-amber-400/60"
                />
              </div>
              <div class="space-y-1">
                <label class="text-[11px] font-bold text-white/60 uppercase">Total Horas Extras</label>
                <div class="px-4 py-2.5 rounded-xl bg-amber-400/10 border border-amber-400/20 text-amber-400 font-bold">
                  Q {{ formatCurrency(payrollCalculations.totalHorasExtras) }}
                  <span class="text-[10px] text-white/40 block font-normal">
                    {{ payrollForm.horas_extras || 0 }} hrs × Q {{ formatCurrency(payrollCalculations.tarifaHoraExtra) }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- 5. Viáticos (Tiene Viáticos -> Sí -> Cantidad Brindada + Observaciones) -->
          <div class="space-y-3 bg-sky-950/15 p-4 rounded-2xl border border-sky-400/20">
            <div class="flex items-center justify-between">
              <label class="text-xs font-bold text-sky-400 uppercase tracking-wider flex items-center gap-2">
                <CreditCardIcon class="w-4 h-4" />
                4. ¿Viáticos?
              </label>
              <div class="flex items-center gap-4">
                <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-white/80">
                  <input type="radio" v-model="payrollForm.tiene_viaticos" :value="true" class="accent-sky-400 w-4 h-4" />
                  Sí
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-white/80">
                  <input type="radio" v-model="payrollForm.tiene_viaticos" :value="false" class="accent-sky-400 w-4 h-4" />
                  No
                </label>
              </div>
            </div>

            <div v-if="payrollForm.tiene_viaticos" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-sky-400/10">
              <div class="space-y-1">
                <label class="text-[11px] font-bold text-white/60 uppercase">Cantidad Brindada (GTQ)</label>
                <input
                  v-model.number="payrollForm.monto_viaticos"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                  class="w-full bg-black/30 border border-sky-400/30 rounded-xl px-4 py-2.5 text-white font-bold focus:outline-none focus:border-sky-400/60"
                />
              </div>
              <div class="space-y-1">
                <label class="text-[11px] font-bold text-white/60 uppercase">Observaciones Viáticos</label>
                <input
                  v-model="payrollForm.observaciones_viaticos"
                  type="text"
                  placeholder="Ej. Combustible y alimentación proyecto X"
                  class="w-full bg-black/30 border border-sky-400/30 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-sky-400/60"
                />
              </div>
            </div>
          </div>

          <!-- Observaciones Generales -->
          <div class="space-y-1">
            <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Observaciones Generales</label>
            <input
              v-model="payrollForm.observaciones"
              type="text"
              placeholder="Opcional"
              class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-3 text-white text-sm placeholder-white/20 focus:outline-none focus:border-primary/50 transition-all"
            />
          </div>

          <!-- ============================================================
               TOTAL A PAGAR EN EL MES (Banner destacado)
               ============================================================ -->
          <div class="p-6 rounded-3xl bg-gradient-to-br from-emerald-950/60 via-emerald-900/30 to-black/40 border-2 border-emerald-400/40 shadow-2xl space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-xs font-extrabold uppercase tracking-[0.2em] text-emerald-400 flex items-center gap-2">
                <CalculatorIcon class="w-5 h-5" />
                # Total a Pagar en el Mes
              </span>
              <span class="text-3xl font-black text-white tracking-tight">
                Q {{ formatCurrency(payrollCalculations.totalPagar) }}
              </span>
            </div>

            <!-- Desglose -->
            <div class="grid grid-cols-3 gap-2 pt-3 border-t border-emerald-400/20 text-xs">
              <div>
                <span class="text-white/40 block">Salario Base:</span>
                <span class="font-bold text-white">Q {{ formatCurrency(payrollCalculations.salarioBaseCalculado) }}</span>
              </div>
              <div>
                <span class="text-white/40 block">Horas Extras:</span>
                <span class="font-bold text-amber-400">+ Q {{ formatCurrency(payrollCalculations.totalHorasExtras) }}</span>
              </div>
              <div>
                <span class="text-white/40 block">Viáticos:</span>
                <span class="font-bold text-sky-400">+ Q {{ formatCurrency(payrollCalculations.montoViaticos) }}</span>
              </div>
            </div>
          </div>

          <!-- Botones Formulario Planilla -->
          <div class="pt-2 flex justify-end gap-4 border-t border-white/10">
            <button type="button" @click="closePayrollModal" class="px-7 py-3.5 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="isSubmittingPayroll || !payrollForm.personnel_id"
              class="glass-button text-white py-3.5 px-8 rounded-2xl font-bold flex items-center gap-2 bg-emerald-500/30 border border-emerald-400/50 text-emerald-300 hover:bg-emerald-500/40 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-xl shadow-emerald-500/20"
            >
              <span v-if="isSubmittingPayroll">Guardando Pago...</span>
              <span v-else>Guardar y Registrar Pago</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================
         MODAL BOLETA DE PAGO (IMPRIMIBLE)
         ============================================================ -->
    <div v-if="showReceiptModal && selectedReceipt" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showReceiptModal = false"></div>

      <div class="glass-card w-full max-w-xl max-h-[90vh] overflow-y-auto rounded-[32px] p-6 md:p-8 relative z-10 border border-white/10 shadow-2xl" id="printable-receipt">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-white/10 no-print">
          <h3 class="text-xl font-bold text-white">Boleta de Pago de Planilla</h3>
          <div class="flex items-center gap-2">
            <button @click="printReceipt" class="px-4 py-2 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-400/30 text-xs font-bold hover:bg-emerald-500/30 transition-all flex items-center gap-1.5">
              <PrinterIcon class="w-4 h-4" />
              Imprimir Boleta
            </button>
            <button @click="showReceiptModal = false" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
        </div>

        <!-- Documento Boleta -->
        <div class="bg-white/[0.03] p-6 rounded-2xl border border-white/10 space-y-6 text-white">
          <!-- Cabecera de la boleta -->
          <div class="text-center pb-4 border-b border-white/10 space-y-1">
            <h4 class="text-xl font-black text-white uppercase tracking-wider">Concretos de Oriente</h4>
            <p class="text-xs text-white/60">Comprobante de Pago Mensual de Planilla</p>
            <p class="text-xs font-bold text-emerald-400 uppercase tracking-widest mt-1">Periodo: {{ selectedReceipt.periodo }}</p>
          </div>

          <!-- Info Colaborador -->
          <div class="grid grid-cols-2 gap-3 text-xs">
            <div>
              <span class="text-white/40 uppercase block font-bold">Colaborador:</span>
              <span class="text-sm font-bold text-white">{{ selectedReceipt.empleado_nombre }}</span>
            </div>
            <div>
              <span class="text-white/40 uppercase block font-bold">DPI:</span>
              <span class="text-sm font-semibold text-white/90">{{ selectedReceipt.empleado_dpi || '—' }}</span>
            </div>
            <div>
              <span class="text-white/40 uppercase block font-bold">Puesto:</span>
              <span class="text-sm font-semibold text-white/90">{{ selectedReceipt.empleado_puesto || '—' }}</span>
            </div>
            <div>
              <span class="text-white/40 uppercase block font-bold">Fecha de Pago:</span>
              <span class="text-sm font-semibold text-white/90">{{ formatDate(selectedReceipt.fecha_pago) }}</span>
            </div>
          </div>

          <!-- Tabla de Conceptos -->
          <div class="border border-white/10 rounded-xl overflow-hidden">
            <table class="w-full text-left text-xs">
              <thead class="bg-white/5 border-b border-white/10 text-[10px] uppercase font-bold text-white/60 tracking-wider">
                <tr>
                  <th class="p-3">Concepto</th>
                  <th class="p-3 text-center">Detalle / Días</th>
                  <th class="p-3 text-right">Monto</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr>
                  <td class="p-3 font-medium">Salario Base ({{ selectedReceipt.dias_trabajados }} días)</td>
                  <td class="p-3 text-center text-white/60">Q {{ formatCurrency(selectedReceipt.salario_base) }} / mes</td>
                  <td class="p-3 text-right font-bold text-white">Q {{ formatCurrency(selectedReceipt.salario_base_calculado) }}</td>
                </tr>
                <tr v-if="parseFloat(selectedReceipt.horas_extras) > 0">
                  <td class="p-3 font-medium">Horas Extras</td>
                  <td class="p-3 text-center text-white/60">{{ selectedReceipt.horas_extras }} hrs @ Q {{ formatCurrency(selectedReceipt.tarifa_hora_extra) }}</td>
                  <td class="p-3 text-right font-bold text-amber-400">Q {{ formatCurrency(selectedReceipt.total_horas_extras) }}</td>
                </tr>
                <tr v-if="parseFloat(selectedReceipt.monto_viaticos) > 0">
                  <td class="p-3 font-medium">
                    Viáticos
                    <span v-if="selectedReceipt.observaciones_viaticos" class="block text-[10px] text-white/40">({{ selectedReceipt.observaciones_viaticos }})</span>
                  </td>
                  <td class="p-3 text-center text-white/60">Asignación</td>
                  <td class="p-3 text-right font-bold text-sky-400">Q {{ formatCurrency(selectedReceipt.monto_viaticos) }}</td>
                </tr>
                <tr class="bg-white/5 font-black text-sm">
                  <td colspan="2" class="p-3 uppercase text-emerald-400">Total Liquidado a Pagar</td>
                  <td class="p-3 text-right text-emerald-400 text-base">Q {{ formatCurrency(selectedReceipt.total_pagar) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Observaciones -->
          <div v-if="selectedReceipt.observaciones" class="text-xs text-white/60">
            <span class="font-bold text-white/80 uppercase tracking-wider block mb-0.5">Observaciones:</span>
            <p>{{ selectedReceipt.observaciones }}</p>
          </div>

          <!-- Firmas -->
          <div class="grid grid-cols-2 gap-8 pt-8 border-t border-white/10 text-center text-xs">
            <div>
              <div class="border-b border-white/30 h-10 mb-2"></div>
              <p class="font-bold text-white/80">Firma del Colaborador</p>
              <p class="text-[10px] text-white/40">{{ selectedReceipt.empleado_nombre }}</p>
            </div>
            <div>
              <div class="border-b border-white/30 h-10 mb-2"></div>
              <p class="font-bold text-white/80">Firma Autorizada</p>
              <p class="text-[10px] text-white/40">Recursos Humanos</p>
            </div>
          </div>
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

          <!-- Adjuntar Foto / Documento -->
          <div class="space-y-2">
            <label class="text-xs font-bold text-white/50 uppercase tracking-wider flex items-center gap-1.5">
              <PaperClipIcon class="w-4 h-4 text-amber-400" />
              Adjuntar Foto o Documento (PDF, Imagen)
            </label>
            <input @change="handleIncidentFileChange" type="file" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx"
              class="w-full text-white/60 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-amber-400/20 file:text-amber-400 hover:file:bg-amber-400/30 file:transition-all cursor-pointer bg-black/20 border border-white/10 rounded-2xl p-2" />
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
  ChevronLeftIcon, ChevronRightIcon, ExclamationTriangleIcon,
  MagnifyingGlassIcon, BanknotesIcon, DocumentArrowDownIcon,
  DocumentTextIcon, IdentificationIcon, DocumentCheckIcon,
  PaperClipIcon, PrinterIcon, ArrowTopRightOnSquareIcon,
  CalculatorIcon, ClockIcon, CreditCardIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const BASE_URL = '/concretos-oriente/Backend/api/v1';

// ----------------------------------------------------------------
// State
// ----------------------------------------------------------------
const personnel         = ref([]);
const projects          = ref([]);
const puestos           = ref([]);
const incidents         = ref([]);
const payrollPayments   = ref([]);
const loading           = ref(true);
const loadingIncidents  = ref(false);
const loadingPayroll    = ref(false);

// Filtros y paginación de personal
const searchQuery    = ref('');
const filterTipo     = ref('');
const filterEstado   = ref('');
const filterProyecto = ref('');
const currentPage    = ref(1);
const PAGE_SIZE      = 10;

// Filtros y paginación de incidencias
const incidentSearch          = ref('');
const filterIncidentEmpleado  = ref('');
const filterIncidentFecha     = ref('');
const incidentCurrentPage     = ref(1);
const INCIDENT_PAGE_SIZE      = 10;

// Filtros y paginación de planilla
const payrollSearch          = ref('');
const filterPayrollEmpleado  = ref('');
const payrollCurrentPage     = ref(1);
const PAYROLL_PAGE_SIZE      = 10;

// Modals state
const showModal            = ref(false);
const showViewModal        = ref(false);
const showIncidentModal    = ref(false);
const showPayrollModal     = ref(false);
const showReceiptModal     = ref(false);

const isSubmitting         = ref(false);
const isSubmittingIncident = ref(false);
const isSubmittingPayroll  = ref(false);
const isEditing            = ref(false);
const editingId            = ref(null);
const selectedEmp          = ref(null);
const selectedReceipt      = ref(null);
const fullscreenImage      = ref(null);

// Form data empleado
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
  edades_hijos_list:  [],
  nivel_academico:    '',
  fecha_nacimiento:   '',
  depto_nacimiento:   '',
  muni_nacimiento:    '',
  estado_civil:       '',
  igss:               null,
  igss_numero:        '',
  fecha_contratacion: '',
  fecha_baja:         '',
  numero_cuenta:      '',
  nombre_banco:       '',
  proyecto_id:        null,
  foto:               null,
  dpi_adjunto:        null,
  contrato_adjunto:   null,
  licencia_adjunto:   null
});

// Form data incidencia
const incidentForm = ref({
  personnel_id: '',
  texto: '',
  fecha: '',
  motivo: '',
  adjunto: null
});

// Form data planilla (Espacio de Planilla)
const payrollForm = ref({
  personnel_id: '',
  periodo: '',
  fecha_pago: new Date().toISOString().split('T')[0],
  dias_trabajados: 30,
  tiene_horas_extras: false,
  horas_extras: 0,
  tiene_viaticos: false,
  monto_viaticos: 0,
  observaciones_viaticos: '',
  observaciones: ''
});

// ----------------------------------------------------------------
// Computed: Dynamic Children Count
// ----------------------------------------------------------------
const parsedCantidadHijos = computed(() => {
  const n = parseInt(formData.value.cantidad_hijos);
  return isNaN(n) || n < 0 ? 0 : n;
});

const onCantidadHijosChange = () => {
  const count = parsedCantidadHijos.value;
  const currentList = [...formData.value.edades_hijos_list];
  if (currentList.length < count) {
    while (currentList.length < count) currentList.push('');
  } else if (currentList.length > count) {
    currentList.length = count;
  }
  formData.value.edades_hijos_list = currentList;
};

// ----------------------------------------------------------------
// Computed: Payroll calculations
// ----------------------------------------------------------------
const selectedPayrollEmp = computed(() => {
  if (!payrollForm.value.personnel_id) return null;
  return personnel.value.find(e => String(e.id) === String(payrollForm.value.personnel_id)) || null;
});

const payrollCalculations = computed(() => {
  const emp = selectedPayrollEmp.value;
  const salarioBase = emp ? parseFloat(emp.salario_base || 0) : 0;
  const tarifaHoraExtra = emp ? parseFloat(emp.tarifa_hora_extra || 0) : 0;
  const diasTrabajados = parseInt(payrollForm.value.dias_trabajados || 0);

  // Pro-rated base salary (salario_base / 30) * dias_trabajados
  const salarioBaseCalculado = (salarioBase / 30) * Math.max(0, diasTrabajados);

  // Overtime
  const horasExtras = payrollForm.value.tiene_horas_extras ? Math.max(0, parseFloat(payrollForm.value.horas_extras || 0)) : 0;
  const totalHorasExtras = horasExtras * tarifaHoraExtra;

  // Viáticos
  const montoViaticos = payrollForm.value.tiene_viaticos ? Math.max(0, parseFloat(payrollForm.value.monto_viaticos || 0)) : 0;

  // Total
  const totalPagar = salarioBaseCalculado + totalHorasExtras + montoViaticos;

  return {
    salarioBase,
    tarifaHoraExtra,
    salarioBaseCalculado,
    horasExtras,
    totalHorasExtras,
    montoViaticos,
    totalPagar
  };
});

const onPayrollPersonnelChange = () => {
  const emp = selectedPayrollEmp.value;
  if (!emp) return;
  // If colaborador has diario_viaticos defined, prefill or reset
  if (emp.diario_viaticos && parseFloat(emp.diario_viaticos) > 0) {
    payrollForm.value.tiene_viaticos = true;
    payrollForm.value.monto_viaticos = parseFloat(emp.diario_viaticos) * (payrollForm.value.dias_trabajados || 30);
  }
  if (emp.tarifa_hora_extra && parseFloat(emp.tarifa_hora_extra) > 0) {
    payrollForm.value.tiene_horas_extras = false;
  }
};

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
    { label: 'Total Empleados',     value: total.toString(),      change: 'Total',       icon: UsersIcon,           color: 'text-primary',     bgColor: 'bg-primary/20' },
    { label: 'Activos',             value: activos.toString(),    change: 'Activos',     icon: CheckCircleIcon,     color: 'text-emerald-400', bgColor: 'bg-emerald-400/10' },
    { label: 'En Planilla',         value: enPlanilla.toString(), change: 'Con salario', icon: BriefcaseIcon,       color: 'text-amber-400',   bgColor: 'bg-amber-400/10' },
    { label: 'Proyectos Cubiertos', value: proyectos.toString(), change: 'Proyectos',   icon: BuildingOfficeIcon,  color: 'text-sky-400',     bgColor: 'bg-sky-400/10' },
  ];
});

// ----------------------------------------------------------------
// Filters & Pagination: Personnel
// ----------------------------------------------------------------
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
// Filters & Pagination: Planilla
// ----------------------------------------------------------------
watch([payrollSearch, filterPayrollEmpleado], () => {
  payrollCurrentPage.value = 1;
});

const filteredPayrollPayments = computed(() => {
  const q = payrollSearch.value.toLowerCase().trim();
  return payrollPayments.value.filter(p => {
    if (q) {
      const name = (p.empleado_nombre || '').toLowerCase();
      const per  = (p.periodo || '').toLowerCase();
      const obs  = (p.observaciones || '').toLowerCase();
      if (!name.includes(q) && !per.includes(q) && !obs.includes(q)) return false;
    }
    if (filterPayrollEmpleado.value && String(p.personnel_id) !== String(filterPayrollEmpleado.value)) {
      return false;
    }
    return true;
  });
});

const totalPayrollPages = computed(() =>
  Math.max(1, Math.ceil(filteredPayrollPayments.value.length / PAYROLL_PAGE_SIZE))
);

const paginatedPayrollPayments = computed(() => {
  const start = (payrollCurrentPage.value - 1) * PAYROLL_PAGE_SIZE;
  return filteredPayrollPayments.value.slice(start, start + PAYROLL_PAGE_SIZE);
});

// ----------------------------------------------------------------
// Filters & Pagination: Incidencias
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

// ----------------------------------------------------------------
// Lifecycle & Fetching
// ----------------------------------------------------------------
onMounted(() => {
  fetchPersonnel();
  fetchProjects();
  fetchPuestos();
  fetchIncidents();
  fetchPayrollPayments();
});

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

const fetchPayrollPayments = async () => {
  loadingPayroll.value = true;
  try {
    const res = await fetch(`${BASE_URL}/personnel/payroll-payments`);
    const result = await res.json();
    if (result.status === 'success') {
      payrollPayments.value = result.data;
    }
  } catch (err) {
    console.error('Error fetching payroll payments:', err);
  } finally {
    loadingPayroll.value = false;
  }
};

// ----------------------------------------------------------------
// Espacio de Planilla / Payroll Methods
// ----------------------------------------------------------------
const openPayrollModal = () => {
  const currentMonthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
  const now = new Date();
  const defaultPeriodo = `${currentMonthNames[now.getMonth()]} ${now.getFullYear()}`;

  payrollForm.value = {
    personnel_id: '',
    periodo: defaultPeriodo,
    fecha_pago: now.toISOString().split('T')[0],
    dias_trabajados: 30,
    tiene_horas_extras: false,
    horas_extras: 0,
    tiene_viaticos: false,
    monto_viaticos: 0,
    observaciones_viaticos: '',
    observaciones: ''
  };
  showPayrollModal.value = true;
};

const openQuickPayroll = (emp) => {
  openPayrollModal();
  payrollForm.value.personnel_id = emp.id;
  onPayrollPersonnelChange();
};

const closePayrollModal = () => {
  showPayrollModal.value = false;
};

const submitPayrollPayment = async () => {
  if (!payrollForm.value.personnel_id) {
    Swal.fire({ ...swalBase, title: 'Atención', text: 'Por favor seleccione un colaborador.', icon: 'warning' });
    return;
  }

  isSubmittingPayroll.value = true;
  try {
    const fd = new FormData();
    fd.append('personnel_id',           payrollForm.value.personnel_id);
    fd.append('periodo',                payrollForm.value.periodo);
    fd.append('fecha_pago',             payrollForm.value.fecha_pago);
    fd.append('dias_trabajados',        payrollForm.value.dias_trabajados);
    fd.append('salario_base',           payrollCalculations.value.salarioBase);
    fd.append('salario_base_calculado', payrollCalculations.value.salarioBaseCalculado);
    fd.append('tiene_horas_extras',     payrollForm.value.tiene_horas_extras ? '1' : '0');
    fd.append('horas_extras',           payrollCalculations.value.horasExtras);
    fd.append('tarifa_hora_extra',      payrollCalculations.value.tarifaHoraExtra);
    fd.append('total_horas_extras',     payrollCalculations.value.totalHorasExtras);
    fd.append('tiene_viaticos',         payrollForm.value.tiene_viaticos ? '1' : '0');
    fd.append('monto_viaticos',         payrollCalculations.value.montoViaticos);
    fd.append('observaciones_viaticos', payrollForm.value.observaciones_viaticos || '');
    fd.append('total_pagar',            payrollCalculations.value.totalPagar);
    fd.append('observaciones',          payrollForm.value.observaciones || '');

    const res = await fetch(`${BASE_URL}/personnel/payroll-payments`, {
      method: 'POST',
      body: fd
    });
    const result = await res.json();

    if (result.status === 'success') {
      await fetchPayrollPayments();
      closePayrollModal();

      const createdPayment = result.data;
      Swal.fire({
        ...swalBase,
        title: '¡Pago Registrado!',
        html: `<p class="text-white/80">Total a pagar: <b class="text-emerald-400 text-lg">Q ${formatCurrency(payrollCalculations.value.totalPagar)}</b></p>`,
        icon: 'success',
        showCancelButton: true,
        confirmButtonText: 'Ver Boleta de Pago',
        cancelButtonText: 'Cerrar'
      }).then(r => {
        if (r.isConfirmed && createdPayment) {
          openReceiptModal(createdPayment);
        }
      });
    } else {
      Swal.fire({ ...swalBase, title: 'Error', text: result.message || 'Error al registrar el pago.', icon: 'error' });
    }
  } catch (err) {
    console.error('Error submitting payroll payment:', err);
    Swal.fire({ ...swalBase, title: 'Error', text: 'Error de conexión al registrar pago de planilla.', icon: 'error' });
  } finally {
    isSubmittingPayroll.value = false;
  }
};

const deletePayrollPayment = async (id) => {
  const result = await Swal.fire({
    ...swalBase,
    title: '¿Eliminar registro de pago?',
    text: 'Esta acción cancelará el registro del pago de planilla.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#f43f5e',
    cancelButtonColor:  '#475569',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText:  'Cancelar',
  });

  if (!result.isConfirmed) return;

  try {
    const res = await fetch(`${BASE_URL}/personnel/payroll-payments/${id}`, { method: 'DELETE' });
    const data = await res.json();

    if (data.status === 'success') {
      await fetchPayrollPayments();
      Swal.fire({ ...swalBase, title: '¡Eliminado!', text: 'El pago ha sido eliminado correctamente.', icon: 'success' });
    } else {
      Swal.fire({ ...swalBase, title: 'Error', text: data.message || 'Error al eliminar', icon: 'error' });
    }
  } catch (err) {
    console.error('Error deleting payroll payment:', err);
    Swal.fire({ ...swalBase, title: 'Error', text: 'Error de conexión al servidor', icon: 'error' });
  }
};

const openReceiptModal = (payment) => {
  selectedReceipt.value = payment;
  showReceiptModal.value = true;
};

const printReceipt = () => {
  window.print();
};

// ----------------------------------------------------------------
// Incidents Methods
// ----------------------------------------------------------------
const openIncidentModal = () => {
  incidentForm.value = {
    personnel_id: '',
    texto: '',
    fecha: new Date().toISOString().split('T')[0],
    motivo: '',
    adjunto: null
  };
  showIncidentModal.value = true;
};

const closeIncidentModal = () => {
  showIncidentModal.value = false;
};

const handleIncidentFileChange = (e) => {
  const file = e.target.files[0];
  if (file) incidentForm.value.adjunto = file;
};

const submitIncident = async () => {
  isSubmittingIncident.value = true;
  try {
    const fd = new FormData();
    fd.append('personnel_id', incidentForm.value.personnel_id);
    fd.append('texto',        incidentForm.value.texto);
    fd.append('fecha',        incidentForm.value.fecha);
    fd.append('motivo',       incidentForm.value.motivo);
    if (incidentForm.value.adjunto) {
      fd.append('adjunto', incidentForm.value.adjunto);
    }

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
  let edadesList = [];
  if (emp.edades_hijos) {
    try {
      if (emp.edades_hijos.startsWith('[')) {
        edadesList = JSON.parse(emp.edades_hijos);
      } else {
        edadesList = emp.edades_hijos.split(',').map(s => s.trim()).filter(Boolean);
      }
    } catch {
      edadesList = emp.edades_hijos.split(',').map(s => s.trim()).filter(Boolean);
    }
  }

  const numHijos = emp.cantidad_hijos !== null && emp.cantidad_hijos !== undefined ? parseInt(emp.cantidad_hijos) : 0;
  while (edadesList.length < numHijos) edadesList.push('');

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
    edades_hijos_list:  edadesList,
    nivel_academico:    emp.nivel_academico    || '',
    fecha_nacimiento:   emp.fecha_nacimiento   || '',
    depto_nacimiento:   emp.depto_nacimiento   || '',
    muni_nacimiento:    emp.muni_nacimiento    || '',
    estado_civil:       emp.estado_civil       || '',
    igss:               emp.igss !== null && emp.igss !== undefined ? parseInt(emp.igss) : null,
    igss_numero:        emp.igss_numero        || '',
    fecha_contratacion: emp.fecha_contratacion || '',
    fecha_baja:         emp.fecha_baja         || '',
    numero_cuenta:      emp.numero_cuenta      || '',
    nombre_banco:       emp.nombre_banco       || '',
    proyecto_id:        emp.proyecto_id        || null,
    foto:               null,
    dpi_adjunto:        null,
    contrato_adjunto:   null,
    licencia_adjunto:   null
  };
  isEditing.value = true;
  editingId.value = emp.id;
  showModal.value = true;
};

const loadingRenap = ref(false);
const renapSuccess = ref(false);
let renapDebounceTimeout = null;

const getDpiClean = () => {
  return (formData.value.dpi || '').replace(/\D/g, '').slice(0, 13);
};

const onDpiInput = (e) => {
  formatDpi(e);
  renapSuccess.value = false;
  const cui = getDpiClean();

  if (renapDebounceTimeout) clearTimeout(renapDebounceTimeout);

  if (cui.length === 13) {
    renapDebounceTimeout = setTimeout(() => {
      consultarRenap(false);
    }, 400);
  }
};

const consultarRenap = async (isManual = false) => {
  const cui = getDpiClean();
  if (cui.length !== 13) {
    if (isManual) {
      Swal.fire({
        ...swalBase,
        title: 'DPI Incompleto',
        text: 'El número de DPI debe contener exactamente 13 dígitos.',
        icon: 'warning'
      });
    }
    return;
  }

  loadingRenap.value = true;
  renapSuccess.value = false;

  try {
    let result = null;

    // 1. Intentamos primero vía directa a la API de RENAP
    try {
      const res = await fetch(`http://159.203.113.174/renap.php?cui=${cui}`);
      if (res.ok) {
        result = await res.json();
      }
    } catch (directErr) {
      console.warn('Direct RENAP fetch failed, falling back to backend endpoint...', directErr);
    }

    // 2. Si falló la llamada directa, usamos el backend proxy
    if (!result || !result.ok) {
      try {
        const res = await fetch(`${BASE_URL}/personnel/renap/${cui}`);
        if (res.ok) {
          result = await res.json();
        }
      } catch (backendErr) {
        console.error('Backend RENAP fetch failed:', backendErr);
      }
    }

    if (result && result.ok && result.data && result.data.data && result.data.data.length > 0) {
      const person = result.data.data[0];

      // Nombres
      const nombresParts = [person.PRIMER_NOMBRE, person.SEGUNDO_NOMBRE, person.TERCER_NOMBRE].filter(Boolean);
      if (nombresParts.length > 0) {
        formData.value.nombres = nombresParts.join(' ');
      }

      // Apellidos
      const apellidosParts = [person.PRIMER_APELLIDO, person.SEGUNDO_APELLIDO].filter(Boolean);
      if (person.APELLIDO_CASADA) {
        apellidosParts.push('DE ' + person.APELLIDO_CASADA);
      }
      if (apellidosParts.length > 0) {
        formData.value.apellidos = apellidosParts.join(' ');
      }

      // Fecha de Nacimiento
      if (person.FECHA_NACIMIENTO) {
        const parts = person.FECHA_NACIMIENTO.split('/');
        if (parts.length === 3) {
          const [d, m, y] = parts;
          formData.value.fecha_nacimiento = `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`;
        }
      }

      // Departamento y Municipio de Nacimiento
      if (person.DEPTO_NACIMIENTO) {
        formData.value.depto_nacimiento = person.DEPTO_NACIMIENTO;
      }
      if (person.MUNI_NACIMIENTO) {
        formData.value.muni_nacimiento = person.MUNI_NACIMIENTO;
      }

      // Estado Civil
      if (person.ESTADO_CIVIL) {
        formData.value.estado_civil = mapEstadoCivil(person.ESTADO_CIVIL);
      }

      // Dirección / Vecindad
      if (person.VECINDAD && !formData.value.direccion) {
        formData.value.direccion = person.VECINDAD;
      }

      renapSuccess.value = true;

      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        background: '#0f172a',
        color: '#fff',
        customClass: {
          popup: 'border border-white/10 rounded-2xl shadow-xl'
        }
      });
      Toast.fire({
        icon: 'success',
        title: '¡Datos de RENAP autocompletados!'
      });
    } else {
      if (isManual) {
        Swal.fire({
          ...swalBase,
          title: 'No encontrado',
          text: result?.data?.mensaje || result?.mensaje || 'No se encontraron datos para el DPI ingresado.',
          icon: 'info'
        });
      }
    }
  } catch (err) {
    console.error('Error al consultar RENAP:', err);
    if (isManual) {
      Swal.fire({
        ...swalBase,
        title: 'Error de Consulta',
        text: 'Ocurrió un error al consultar el servicio de RENAP.',
        icon: 'error'
      });
    }
  } finally {
    loadingRenap.value = false;
  }
};

const mapEstadoCivil = (code) => {
  if (!code) return '';
  const c = code.trim().toUpperCase();
  if (c === 'S' || c === 'SOLTERO' || c === 'SOLTERA') return 'Soltero(a)';
  if (c === 'C' || c === 'CASADO' || c === 'CASADA') return 'Casado(a)';
  if (c === 'U' || c === 'UNIDO' || c === 'UNIDA' || c === 'UNION') return 'Unido(a)';
  if (c === 'D' || c === 'DIVORCIADO' || c === 'DIVORCIADA') return 'Divorciado(a)';
  if (c === 'V' || c === 'VIUDO' || c === 'VIUDA') return 'Viudo(a)';
  return code;
};

const closeModal = () => {
  showModal.value = false;
  resetForm();
};

const resetForm = () => {
  loadingRenap.value = false;
  renapSuccess.value = false;
  if (renapDebounceTimeout) clearTimeout(renapDebounceTimeout);
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
    edades_hijos_list:  [],
    nivel_academico:    '',
    fecha_nacimiento:   '',
    depto_nacimiento:   '',
    muni_nacimiento:    '',
    estado_civil:       '',
    igss:               null,
    igss_numero:        '',
    fecha_contratacion: '',
    fecha_baja:         '',
    numero_cuenta:      '',
    nombre_banco:       '',
    proyecto_id:        null,
    foto:               null,
    dpi_adjunto:        null,
    contrato_adjunto:   null,
    licencia_adjunto:   null
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
// File inputs
// ----------------------------------------------------------------
const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (file) formData.value.foto = file;
};

const handleDpiFileChange = (e) => {
  const file = e.target.files[0];
  if (file) formData.value.dpi_adjunto = file;
};

const handleContratoFileChange = (e) => {
  const file = e.target.files[0];
  if (file) formData.value.contrato_adjunto = file;
};

const handleLicenciaFileChange = (e) => {
  const file = e.target.files[0];
  if (file) formData.value.licencia_adjunto = file;
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
  return parseFloat(value).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (val) => {
  if (!val) return '';
  const [y, m, d] = val.split('-');
  return `${d}/${m}/${y}`;
};

const formatEdadesHijos = (edadesRaw) => {
  if (!edadesRaw) return 'No registradas';
  try {
    if (typeof edadesRaw === 'string' && edadesRaw.startsWith('[')) {
      const arr = JSON.parse(edadesRaw);
      return arr.map(e => `${e} años`).join(', ');
    }
    return edadesRaw.split(',').map(e => `${e.trim()} años`).join(', ');
  } catch {
    return edadesRaw;
  }
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

const getDocumentUrl = (path) => {
  if (!path) return '';
  return `/concretos-oriente/Backend/${path}`;
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
// CRUD Empleados
// ----------------------------------------------------------------
const deleteEmployee = async (id) => {
  const result = await Swal.fire({
    ...swalBase,
    title: '¿Estás seguro?',
    text: 'Esta acción no se puede deshacer y eliminará los datos, documentos y foto del empleado.',
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

  // Edades de hijos list
  if (formData.value.edades_hijos_list && formData.value.edades_hijos_list.length > 0) {
    data.append('edades_hijos', JSON.stringify(formData.value.edades_hijos_list));
  } else {
    data.append('edades_hijos', '');
  }

  data.append('nivel_academico',    formData.value.nivel_academico    || '');
  data.append('fecha_nacimiento',   formData.value.fecha_nacimiento   || '');
  data.append('depto_nacimiento',   formData.value.depto_nacimiento   || '');
  data.append('muni_nacimiento',    formData.value.muni_nacimiento    || '');
  data.append('estado_civil',       formData.value.estado_civil       || '');
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
  if (formData.value.dpi_adjunto) {
    data.append('dpi_adjunto', formData.value.dpi_adjunto);
  }
  if (formData.value.contrato_adjunto) {
    data.append('contrato_adjunto', formData.value.contrato_adjunto);
  }
  if (formData.value.licencia_adjunto) {
    data.append('licencia_adjunto', formData.value.licencia_adjunto);
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
      Swal.fire({ ...swalBase, title: '¡Guardado!', text: 'Empleado y documentos guardados correctamente.', icon: 'success' });
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

<style scoped>
@media print {
  body * {
    visibility: hidden;
  }
  #printable-receipt, #printable-receipt * {
    visibility: visible;
  }
  #printable-receipt {
    position: fixed;
    left: 0;
    top: 0;
    width: 100vw;
    max-width: 100vw;
    height: 100vh;
    background: white !important;
    color: black !important;
    padding: 20px !important;
    box-shadow: none !important;
    border: none !important;
  }
  #printable-receipt * {
    color: black !important;
    border-color: #cbd5e1 !important;
  }
  .no-print {
    display: none !important;
  }
}
</style>
