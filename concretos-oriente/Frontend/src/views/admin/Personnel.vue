<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-10 relative">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h2 class="text-4xl font-bold tracking-tight text-white mb-2">Gestión de Personal</h2>
        <p class="text-white/60">Gestiona tu fuerza laboral y registra nuevos empleados.</p>
      </div>
      <button
        @click="openModal()"
        class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center justify-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 hover:-translate-y-0.5 active:translate-y-0 transition-all"
      >
        <PlusIcon class="w-5 h-5" />
        Añadir Personal
      </button>
    </div>

    <!-- Stats Cards (4) -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <div
        v-for="(stat, i) in stats"
        :key="i"
        class="glass-card p-8 rounded-[32px] flex flex-col justify-between h-44 cursor-pointer group hover:-translate-y-1.5 hover:scale-[1.02] transition-all duration-300"
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
    <div class="glass-card rounded-[40px] overflow-hidden border border-white/10 transition-all duration-300">
      <div class="p-10 flex flex-wrap items-center justify-between gap-6 border-b border-white/5">
        <div class="flex flex-wrap items-center gap-4">
          <div class="glass-input px-6 py-3 rounded-2xl flex items-center gap-3 text-white/60 font-bold text-xs uppercase tracking-widest cursor-pointer">
            <FunnelIcon class="w-4 h-4" />
            Filtros
          </div>
        </div>
        <button class="flex items-center gap-2 text-primary text-sm font-bold hover:bg-white/5 px-8 py-3 rounded-2xl transition-all border border-white/10">
          <ArrowDownTrayIcon class="w-5 h-5" />
          Exportar CSV
        </button>
      </div>

      <div class="overflow-x-auto px-4">
        <table class="w-full text-left">
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
              <td colspan="7" class="px-8 py-8 text-center text-white/50">Cargando personal...</td>
            </tr>
            <tr v-else-if="personnel.length === 0">
              <td colspan="7" class="px-8 py-8 text-center text-white/50">No hay personal registrado aún.</td>
            </tr>
            <tr v-for="emp in personnel" :key="emp.id" class="hover:bg-white/5 group transition-colors duration-200">
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

      <div class="px-8 py-6 flex items-center justify-between border-t border-white/5">
        <p class="text-xs font-bold text-white/30 tracking-widest uppercase">Mostrando {{ personnel.length }} empleados</p>
      </div>
    </div>

    <!-- ============================================================
         MODAL AÑADIR / EDITAR
         ============================================================ -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>

      <div class="glass-card w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-[32px] p-8 relative z-10 border border-white/10 shadow-2xl">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-2xl font-bold text-white">{{ isEditing ? 'Editar Empleado' : 'Añadir Nuevo Personal' }}</h3>
          <button @click="closeModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all">
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <form @submit.prevent="submitForm" class="space-y-8">

          <!-- SECCIÓN 1: Información Personal -->
          <div>
            <p class="text-xs font-bold text-white/30 uppercase tracking-[0.25em] mb-4">Información Personal</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

              <!-- Tipo de empleado -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Tipo de Empleado <span class="text-tertiary">*</span></label>
                <select v-model="formData.tipo_empleado" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 focus:ring-1 focus:ring-primary/50 transition-all appearance-none">
                  <option value="" disabled>Seleccionar...</option>
                  <option value="Administrativo">Administrativo</option>
                  <option value="Operador">Operador</option>
                  <option value="Piloto">Piloto</option>
                  <option value="Contratista">Contratista</option>
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

          <!-- SECCIÓN 3: Datos Bancarios -->
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

          <!-- SECCIÓN 4: Fotografía -->
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

      <div class="glass-card w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-[32px] p-8 relative z-10 border border-white/10 shadow-2xl">
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

            <!-- Badge tipo empleado -->
            <span :class="`px-4 py-1.5 rounded-full text-[10px] font-extrabold uppercase tracking-widest border ${getTipoEmpleadoBadge(selectedEmp.tipo_empleado).color}`">
              {{ selectedEmp.tipo_empleado }}
            </span>

            <!-- Badge estado -->
            <span :class="`px-4 py-1.5 rounded-full text-[10px] font-extrabold uppercase tracking-widest border ${getEstadoBadge(selectedEmp).color}`">
              {{ getEstadoBadge(selectedEmp).label }}
            </span>
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
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Puesto de Trabajo</p>
              <p class="text-base font-semibold text-primary">{{ selectedEmp.puesto }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Tipo de Planilla</p>
              <p class="text-base font-semibold text-white/90">{{ selectedEmp.tipo_planilla }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Teléfono</p>
              <p class="text-base font-semibold text-white/90">{{ selectedEmp.telefono || 'No registrado' }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-white/40 uppercase tracking-widest mb-1">Proyecto Asignado</p>
              <p class="text-base font-semibold text-white/90">{{ selectedEmp.proyecto_nombre || 'Sin asignar' }}</p>
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
import { ref, onMounted, computed } from 'vue';
import {
  UsersIcon, CheckCircleIcon, BriefcaseIcon, BuildingOfficeIcon,
  PlusIcon, FunnelIcon, ArrowDownTrayIcon,
  XMarkIcon, EyeIcon, PencilIcon, TrashIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const BASE_URL = '/concretos-oriente/Backend/api/v1';

// ----------------------------------------------------------------
// State
// ----------------------------------------------------------------
const personnel  = ref([]);
const projects   = ref([]);
const loading    = ref(true);

const showModal      = ref(false);
const showViewModal  = ref(false);
const isSubmitting   = ref(false);
const isEditing      = ref(false);
const editingId      = ref(null);
const selectedEmp    = ref(null);
const fullscreenImage = ref(null);

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
onMounted(() => {
  fetchPersonnel();
  fetchProjects();
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

/** Formatea un string de dígitos como DPI: 0000 00000 0000 */
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

const getTipoEmpleadoBadge = (tipo) => {
  const map = {
    'Administrativo': 'bg-primary/20 text-primary border-primary/20',
    'Operador':        'bg-amber-400/15 text-amber-400 border-amber-400/20',
    'Piloto':          'bg-sky-400/15 text-sky-400 border-sky-400/20',
    'Contratista':     'bg-rose-400/15 text-rose-400 border-rose-400/20',
  };
  return { color: map[tipo] || 'bg-white/10 text-white/50 border-white/10' };
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
