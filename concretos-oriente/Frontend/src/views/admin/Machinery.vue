<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h2 class="text-4xl font-bold tracking-tight text-white mb-2">Gestión de Maquinaria</h2>
        <p class="text-white/60">Registra y controla el estado de los equipos y su bitácora diaria.</p>
      </div>
      <div class="flex gap-4">
        <button
          v-if="activeTab === 'machinery'"
          @click="openMachineModal"
          class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center justify-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 hover:-translate-y-0.5 active:translate-y-0 transition-all"
        >
          <PlusIcon class="w-5 h-5" />
          Registrar Maquinaria
        </button>
        <button
          v-if="activeTab === 'log'"
          @click="openLogModal"
          class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center justify-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 hover:-translate-y-0.5 active:translate-y-0 transition-all"
        >
          <PlusIcon class="w-5 h-5" />
          Registrar Bitácora
        </button>
      </div>
    </div>

    <!-- Metrics Section -->
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div
        v-for="(metric, i) in metrics"
        :key="i"
        class="glass-card p-8 rounded-3xl flex flex-col justify-between h-44 border border-white/5 transition-all cursor-pointer group hover:-translate-y-3 hover:scale-105 hover:shadow-[0_20px_40px_-15px_rgba(99,102,241,0.5)] hover:scale-105" data-aos="zoom-in-up" data-aos-duration="1000"
      >
        <div>
          <p class="text-white/40 text-[11px] font-bold uppercase tracking-[0.2em] mb-4">{{ metric.label }}</p>
          <h3 :class="`text-3xl font-bold ${metric.color === 'text-error' ? 'text-tertiary' : 'text-white'}`">{{ metric.value }}</h3>
        </div>
        <div v-if="metric.percentage" class="w-full bg-white/5 h-2 rounded-full mt-6 overflow-hidden p-[1px]">
          <div 
            :style="{ width: `${metric.percentage}%` }"
            class="bg-primary h-full rounded-full shadow-[0_0_10px_#6366f1] transition-all duration-1000"
          ></div>
        </div>
        <div v-else :class="`flex items-center gap-2 mt-6 ${metric.color} bg-white/5 px-3 py-1.5 rounded-xl w-fit border border-white/5`">
          <component :is="metric.icon" v-if="metric.icon" class="w-4 h-4" />
          <span class="text-[10px] font-bold uppercase tracking-wider">{{ metric.trend }}</span>
        </div>
      </div>
    </section>

    <!-- Tabs -->
    <section>
      <div class="flex gap-12 border-b border-white/10 relative overflow-x-auto whitespace-nowrap">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="`pb-6 text-xl font-bold transition-all relative ${
            activeTab === tab.id ? 'text-white' : 'text-white/40 hover:text-white/60'
          }`"
        >
          {{ tab.name }}
          <div v-if="activeTab === tab.id" class="absolute bottom-0 left-0 w-full h-1.5 bg-primary rounded-t-full shadow-[0_0_15px_#6366f1]"></div>
        </button>
      </div>
    </section>

    <!-- Filters & Search -->
    <section class="flex flex-col lg:flex-row gap-4">
      <div class="flex-1 relative">
        <input 
          v-if="activeTab === 'machinery'" 
          v-model="searchMachine" 
          type="text" 
          placeholder="Buscar máquina (código, marca, modelo)..." 
          class="w-full bg-black/20 border border-white/10 rounded-2xl pl-12 pr-5 py-4 text-white placeholder-white/40 focus:outline-none focus:border-primary/50 transition-all"
        />
        <input 
          v-else-if="activeTab === 'log'" 
          v-model="searchLog" 
          type="text" 
          placeholder="Buscar en bitácora (máquina, operador)..." 
          class="w-full bg-black/20 border border-white/10 rounded-2xl pl-12 pr-5 py-4 text-white placeholder-white/40 focus:outline-none focus:border-primary/50 transition-all"
        />
        <MagnifyingGlassIcon, ChevronLeftIcon, ChevronRightIcon class="w-5 h-5 absolute left-5 top-1/2 -translate-y-1/2 text-white/40" />
      </div>
      
      <!-- Filters Machinery -->
      <div v-if="activeTab === 'machinery'" class="flex gap-4">
        <select v-model="filterCategory" class="bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none min-w-[200px]">
          <option value="">Todas las Categorías</option>
          <option value="Maquinaria Pesada">Maquinaria Pesada</option>
          <option value="Maquinaria Especial">Maquinaria Especial</option>
          <option value="Vehículo">Vehículo</option>
          <option value="Transporte Pesado">Transporte Pesado</option>
          <option value="Equipo Menor">Equipo Menor</option>
        </select>
        <select v-model="filterStatus" class="bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none min-w-[200px]">
          <option value="">Todos los Estados</option>
          <option value="Activo">Activo</option>
          <option value="En Mantenimiento">En Mantenimiento</option>
          <option value="En Reparación">En Reparación</option>
          <option value="Inactivo">Inactivo</option>
        </select>
      </div>

      <!-- Filters Log -->
      <div v-if="activeTab === 'log'" class="flex gap-4">
        <select v-model="filterLogProject" class="bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none min-w-[200px]">
          <option value="">Todos los Proyectos</option>
          <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.nombre }}</option>
        </select>
      </div>
    </section>

    <!-- Content -->
    <transition name="fade-slide" mode="out-in">
      
      <!-- TAB: MAQUINARIA -->
      <section v-if="activeTab === 'machinery'" key="machinery">
        <div v-if="loading" class="text-center py-20 text-white/40">Cargando maquinaria...</div>
        <div v-else-if="filteredMachinery.length === 0" class="text-center py-20 text-white/40 glass-card rounded-[40px] border border-white/10" data-aos="zoom-in-up" data-aos-duration="1000">
          No hay maquinaria registrada.
        </div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div 
            v-for="m in filteredMachinery" 
            :key="m.id"
            class="glass-card rounded-[40px] overflow-hidden group hover:-translate-y-2 transition-all duration-500 border border-white/10" data-aos="zoom-in-up" data-aos-duration="1000"
          >
            <!-- Imagen y Estado -->
            <div class="h-56 relative overflow-hidden cursor-pointer" @click="selectedMachine = m">
              <img v-if="m.foto_path" :src="getPhotoUrl(m.foto_path)" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-1000" :alt="m.modelo" />
              <div v-else class="w-full h-full bg-white/5 flex items-center justify-center text-white/20 group-hover:scale-110 transition-transform duration-1000">
                <WrenchScrewdriverIcon class="w-20 h-20" />
              </div>
              <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
              <div class="absolute top-6 right-6 px-4 py-2 backdrop-blur-xl bg-black/40 rounded-2xl text-[10px] font-bold uppercase tracking-[0.2em] flex items-center gap-2.5 border border-white/20 shadow-xl">
                <span :class="`w-2.5 h-2.5 rounded-full ${getStatusColor(m.estado)} shadow-[0_0_10px_currentColor]`"></span>
                {{ m.estado }}
              </div>
            </div>
            
            <!-- Info -->
            <div class="p-10 relative">
              <div class="flex justify-between items-start mb-8">
                <div class="cursor-pointer" @click="selectedMachine = m">
                  <h4 class="text-2xl font-bold text-white tracking-tight">{{ m.marca }} {{ m.modelo }}</h4>
                  <p class="text-sm font-semibold text-white/40 mt-1 uppercase tracking-widest">{{ m.categoria }} • {{ m.codigo_interno }}</p>
                </div>
                
                <!-- Acciones dropdown (simplificado como botones inline por ahora) -->
                <div class="flex gap-2">
                  <button @click="openEditMachine(m)" class="p-2.5 bg-white/5 hover:bg-white/10 hover:text-primary rounded-xl transition-all text-white/40 border border-white/10" title="Editar">
                    <PencilIcon class="w-4 h-4" />
                  </button>
                  <button @click="deleteMachine(m.id)" class="p-2.5 bg-white/5 hover:bg-white/10 hover:text-tertiary rounded-xl transition-all text-white/40 border border-white/10" title="Eliminar">
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </div>
              
              <div class="space-y-6">
                <!-- Horómetro -->
                <div class="flex items-center justify-between border-b border-white/5 pb-4">
                  <div class="flex items-center gap-3">
                    <ClockIcon class="w-5 h-5 text-white/30" />
                    <span class="text-[10px] font-bold text-white/30 uppercase tracking-[0.2em]">Horómetro</span>
                  </div>
                  <span class="text-sm font-bold text-white tracking-widest">{{ m.horometro_actual }} hrs</span>
                </div>
                
                <div class="grid grid-cols-2 gap-6">
                  <div class="space-y-2">
                    <p class="text-[10px] text-white/30 uppercase font-bold tracking-[0.2em]">Operador</p>
                    <p class="text-xs font-bold text-white tracking-wide truncate">
                      {{ m.operador_nombre || 'Sin asignar' }}
                    </p>
                  </div>
                  <div class="space-y-2">
                    <p class="text-[10px] text-white/30 uppercase font-bold tracking-[0.2em]">Proyecto</p>
                    <p class="text-xs font-bold text-white tracking-wide truncate">
                      {{ m.proyecto_nombre || 'Sin asignar' }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- TAB: BITÁCORA -->
      <section v-else-if="activeTab === 'log'" key="log" class="glass-card rounded-[40px] overflow-hidden border border-white/10" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="overflow-x-auto px-4">
          <table class="w-full text-left">
            <thead>
              <tr class="border-b border-white/5">
                <th class="px-8 py-8 text-[11px] font-bold text-white/30 uppercase tracking-[0.2em]">Fecha</th>
                <th class="px-8 py-8 text-[11px] font-bold text-white/30 uppercase tracking-[0.2em]">Máquina</th>
                <th class="px-8 py-8 text-[11px] font-bold text-white/30 uppercase tracking-[0.2em]">Proyecto</th>
                <th class="px-8 py-8 text-[11px] font-bold text-white/30 uppercase tracking-[0.2em]">Horómetro</th>
                <th class="px-8 py-8 text-[11px] font-bold text-white/30 uppercase tracking-[0.2em]">Operador</th>
                <th class="px-8 py-8 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-if="loadingLogs">
                <td colspan="6" class="px-8 py-8 text-center text-white/50">Cargando bitácoras...</td>
              </tr>
              <tr v-else-if="filteredLogs.length === 0">
                <td colspan="6" class="px-8 py-12 text-center text-white/40 font-semibold">No hay bitácoras registradas</td>
              </tr>
              <tr v-for="log in paginatedLogs" :key="log.id" class="hover:bg-white/5 transition-all group">
                <td class="px-8 py-6 text-sm font-semibold text-white/80">{{ formatDate(log.fecha) }}</td>
                <td class="px-8 py-6 font-bold text-white">{{ log.maquina_nombre }}</td>
                <td class="px-8 py-6 text-sm font-semibold text-primary">{{ log.proyecto_nombre || 'N/A' }}</td>
                <td class="px-8 py-6">
                  <p class="text-xs text-white/40">Inicial: <span class="text-white">{{ log.horometro_inicial }}</span></p>
                  <p class="text-xs text-white/40">Final: <span class="text-white">{{ log.horometro_final }}</span></p>
                </td>
                <td class="px-8 py-6 text-sm text-white/80">{{ log.operador_nombre || 'N/A' }}</td>
                <td class="px-8 py-6 text-right">
                  <div class="flex justify-end gap-2">
                    <button @click="openViewLog(log)" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all" title="Visualizar">
                      <EyeIcon class="w-5 h-5" />
                    </button>
                    <button @click="deleteLog(log.id)" class="p-2 text-white/40 hover:text-tertiary hover:bg-white/10 rounded-xl transition-all" title="Eliminar">
                      <TrashIcon class="w-5 h-5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Paginación -->
        <div v-if="totalLogPages > 1" class="flex justify-between items-center px-8 py-6 border-t border-white/5 bg-black/20">
          <p class="text-xs text-white/40 font-semibold tracking-widest">
            Página <span class="text-white">{{ currentLogPage }}</span> de <span class="text-white">{{ totalLogPages }}</span>
          </p>
          <div class="flex gap-2">
            <button 
              @click="currentLogPage--" 
              :disabled="currentLogPage === 1"
              class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-white/40 hover:bg-white/10 hover:text-white disabled:opacity-30 disabled:pointer-events-none transition-all"
            >
              <ChevronLeftIcon class="w-5 h-5" />
            </button>
            <button 
              @click="currentLogPage++" 
              :disabled="currentLogPage === totalLogPages"
              class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-white/40 hover:bg-white/10 hover:text-white disabled:opacity-30 disabled:pointer-events-none transition-all"
            >
              <ChevronRightIcon class="w-5 h-5" />
            </button>
          </div>
        </div>
      </section>


    </transition>

    <!-- ============================================================
         MODAL: REGISTRO/EDICIÓN MAQUINARIA
         ============================================================ -->
    <div v-if="showMachineModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeMachineModal"></div>
      
      <div class="glass-card w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-[32px] p-8 relative z-10 border border-white/10 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-2xl font-bold text-white">{{ isEditingMachine ? 'Editar Maquinaria' : 'Registrar Nueva Maquinaria' }}</h3>
          <button @click="closeMachineModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all">
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <form @submit.prevent="submitMachine" class="space-y-8">
          <!-- Datos Principales -->
          <div>
            <p class="text-xs font-bold text-white/30 uppercase tracking-[0.25em] mb-4">Información del Equipo</p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
              
              <div class="space-y-2 lg:col-span-1">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Categoría <span class="text-tertiary">*</span></label>
                <select v-model="formMachine.categoria" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none">
                  <option value="" disabled>Seleccionar...</option>
                  <option value="Maquinaria Pesada">Maquinaria Pesada</option>
                  <option value="Maquinaria Especial">Maquinaria Especial</option>
                  <option value="Vehículo">Vehículo</option>
                  <option value="Transporte Pesado">Transporte Pesado</option>
                  <option value="Equipo Menor">Equipo Menor</option>
                </select>
              </div>

              <div class="space-y-2 lg:col-span-1">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Código Interno <span class="text-tertiary">*</span></label>
                <input v-model="formMachine.codigo_interno" type="text" required placeholder="Ej. EX-042" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50" />
              </div>

              <div class="space-y-2 lg:col-span-1">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Estado <span class="text-tertiary">*</span></label>
                <select v-model="formMachine.estado" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none">
                  <option value="Activo">Activo</option>
                  <option value="En Mantenimiento">En Mantenimiento</option>
                  <option value="En Reparación">En Reparación</option>
                  <option value="Inactivo">Inactivo</option>
                </select>
              </div>

              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Marca <span class="text-tertiary">*</span></label>
                <input v-model="formMachine.marca" type="text" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50" />
              </div>

              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Modelo <span class="text-tertiary">*</span></label>
                <input v-model="formMachine.modelo" type="text" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50" />
              </div>

              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Año de Fabricación</label>
                <input v-model="formMachine.anio_fabricacion" type="number" min="1900" max="2100" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50" />
              </div>

              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Número de Serie</label>
                <input v-model="formMachine.numero_serie" type="text" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50" />
              </div>

              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Placa</label>
                <input v-model="formMachine.placa" type="text" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50" />
              </div>
            </div>
          </div>

          <!-- Uso y Mantenimiento -->
          <div>
            <p class="text-xs font-bold text-white/30 uppercase tracking-[0.25em] mb-4">Uso y Mantenimiento</p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
              
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Horómetro (hrs) <span class="text-tertiary">*</span></label>
                <input v-model="formMachine.horometro_actual" type="number" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
              </div>

              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Kilometraje (km) <span class="text-tertiary">*</span></label>
                <input v-model="formMachine.kilometraje_actual" type="number" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
              </div>

              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Int. Servicio (hrs)</label>
                <input v-model="formMachine.intervalo_servicio" type="number" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
              </div>

              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Último Servicio</label>
                <input v-model="formMachine.fecha_ultimo_servicio" type="date" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
              </div>
            </div>
          </div>

          <!-- Asignaciones y Compra -->
          <div>
            <p class="text-xs font-bold text-white/30 uppercase tracking-[0.25em] mb-4">Asignaciones y Adquisición</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              
              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Operador Asignado</label>
                <select v-model="formMachine.operador_id" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none">
                  <option :value="null">Sin operador asignado</option>
                  <option v-for="emp in personnel" :key="emp.id" :value="emp.id">{{ emp.nombres }} {{ emp.apellidos }}</option>
                </select>
              </div>

              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Proyecto Actual</label>
                <select v-model="formMachine.proyecto_id" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none">
                  <option :value="null">Sin proyecto asignado</option>
                  <option v-for="proj in projects" :key="proj.id" :value="proj.id">{{ proj.nombre }}</option>
                </select>
              </div>

              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Costo Adquisición (GTQ)</label>
                <input v-model="formMachine.costo_adquisicion" type="number" step="0.01" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
              </div>

              <div class="space-y-2">
                <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Fecha Adquisición</label>
                <input v-model="formMachine.fecha_adquisicion" type="date" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
              </div>
            </div>
          </div>

          <!-- Foto -->
          <div>
            <p class="text-xs font-bold text-white/30 uppercase tracking-[0.25em] mb-4">Fotografía</p>
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Imagen de la Máquina (PNG, JPG)</label>
              <input @change="handleFileChange" type="file" accept=".png,.jpg,.jpeg" class="w-full text-white/60 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-primary/20 file:text-primary hover:file:bg-primary/30 file:transition-all cursor-pointer bg-black/20 border border-white/10 rounded-2xl p-2" />
            </div>
          </div>

          <!-- Botones -->
          <div class="pt-4 flex justify-end gap-4 border-t border-white/5">
            <button type="button" @click="closeMachineModal" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">Cancelar</button>
            <button type="submit" :disabled="isSubmitting" class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 disabled:opacity-50 transition-all">
              <span v-if="isSubmitting">Guardando...</span>
              <span v-else>{{ isEditingMachine ? 'Actualizar' : 'Guardar' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================
         MODAL: REGISTRO BITÁCORA DIARIA
         ============================================================ -->
    <div v-if="showLogModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeLogModal"></div>
      
      <div class="glass-card w-full max-w-2xl overflow-y-auto rounded-[32px] p-8 relative z-10 border border-white/10 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-2xl font-bold text-white">Registrar Bitácora Diaria</h3>
          <button @click="closeLogModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all">
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <form @submit.prevent="submitLog" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Máquina <span class="text-tertiary">*</span></label>
              <select v-model="formLog.maquina_id" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none">
                <option value="" disabled>Seleccionar máquina...</option>
                <option v-for="m in filteredMachinery" :key="m.id" :value="m.id">{{ m.codigo_interno }} - {{ m.marca }} {{ m.modelo }}</option>
              </select>
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Fecha <span class="text-tertiary">*</span></label>
              <input v-model="formLog.fecha" type="date" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Proyecto</label>
              <select v-model="formLog.proyecto_id" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none">
                <option :value="null">Sin proyecto</option>
                <option v-for="proj in projects" :key="proj.id" :value="proj.id">{{ proj.nombre }}</option>
              </select>
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Horómetro Inicial <span class="text-tertiary">*</span></label>
              <input v-model="formLog.horometro_inicial" type="number" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Horómetro Final <span class="text-tertiary">*</span></label>
              <input v-model="formLog.horometro_final" type="number" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>

            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Operador</label>
              <select v-model="formLog.operador_id" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none">
                <option :value="null">Seleccionar operador...</option>
                <option v-for="emp in personnel" :key="emp.id" :value="emp.id">{{ emp.nombres }} {{ emp.apellidos }}</option>
              </select>
            </div>

            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Combustible (Gal/Lts)</label>
              <input v-model="formLog.combustible_consumido" type="number" step="0.01" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>

            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Observaciones</label>
              <textarea v-model="formLog.observaciones" rows="3" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 resize-none"></textarea>
            </div>
          </div>

          <div class="pt-4 flex justify-end gap-4 border-t border-white/5">
            <button type="button" @click="closeLogModal" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">Cancelar</button>
            <button type="submit" :disabled="isSubmitting" class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 disabled:opacity-50 transition-all">
              <span v-if="isSubmitting">Guardando...</span>
              <span v-else>Guardar Bitácora</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Machinery Details Modal (Visualización) -->
    <transition name="fade">
      <div v-if="selectedMachine" class="fixed inset-0 z-50 flex items-center justify-center p-6">
        <div @click="selectedMachine = null" class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-4xl glass-card rounded-[56px] overflow-hidden border border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.5)] flex flex-col lg:flex-row max-h-[85vh]" data-aos="zoom-in-up" data-aos-duration="1000">
          
          <button @click="selectedMachine = null" class="absolute top-8 right-8 z-10 w-12 h-12 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center border border-white/10 text-white/40 hover:text-white transition-all">
            <XMarkIcon class="w-6 h-6" />
          </button>

          <!-- Left: Media -->
          <div class="lg:w-1/2 relative bg-black/40">
            <img v-if="selectedMachine.foto_path" :src="getPhotoUrl(selectedMachine.foto_path)" class="w-full h-full object-cover" :alt="selectedMachine.modelo" />
            <div v-else class="w-full h-full flex items-center justify-center text-white/20"><WrenchScrewdriverIcon class="w-24 h-24" /></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent"></div>
            <div class="absolute bottom-10 left-10">
              <span class="text-[10px] font-black uppercase tracking-[0.4em] text-primary mb-2 block">{{ selectedMachine.codigo_interno }}</span>
              <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter leading-none">{{ selectedMachine.marca }}</h2>
              <h3 class="text-2xl font-bold text-white/80 uppercase">{{ selectedMachine.modelo }}</h3>
              <p class="text-white/40 font-bold uppercase tracking-widest mt-2">{{ selectedMachine.categoria }}</p>
            </div>
          </div>

          <!-- Right: Info -->
          <div class="lg:w-1/2 p-12 bg-black/20 overflow-y-auto">
            <div class="space-y-8">
              <!-- Status & Usage -->
              <div class="flex gap-4">
                <div class="flex-1 glass-card p-6 rounded-3xl border border-white/5" data-aos="zoom-in-up" data-aos-duration="1000">
                  <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/20 mb-4 flex items-center gap-2">
                    <ChartBarIcon class="w-4 h-4" /> Estado
                  </p>
                  <div class="flex items-center gap-3">
                    <div :class="`w-3 h-3 rounded-full ${getStatusColor(selectedMachine.estado)} shadow-[0_0_10px_currentColor]`"></div>
                    <span class="text-lg font-black italic uppercase text-white">{{ selectedMachine.estado }}</span>
                  </div>
                </div>
                <div class="flex-1 glass-card p-6 rounded-3xl border border-white/5" data-aos="zoom-in-up" data-aos-duration="1000">
                  <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/20 mb-4 flex items-center gap-2">
                    <ClockIcon class="w-4 h-4" /> Horómetro
                  </p>
                  <span class="text-xl font-black italic uppercase text-white">{{ selectedMachine.horometro_actual }} h</span>
                </div>
              </div>

              <!-- Technical Specs -->
              <div>
                <h5 class="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-4 flex items-center gap-3">
                  <div class="w-8 h-[1px] bg-white/10"></div> Detalles Técnicos
                </h5>
                <div class="grid grid-cols-2 gap-4">
                  <div><p class="text-[9px] text-white/30 uppercase tracking-widest">Año</p><p class="text-sm font-bold text-white">{{ selectedMachine.anio_fabricacion || 'N/A' }}</p></div>
                  <div><p class="text-[9px] text-white/30 uppercase tracking-widest">Serie</p><p class="text-sm font-bold text-white">{{ selectedMachine.numero_serie || 'N/A' }}</p></div>
                  <div><p class="text-[9px] text-white/30 uppercase tracking-widest">Placa</p><p class="text-sm font-bold text-white">{{ selectedMachine.placa || 'N/A' }}</p></div>
                  <div><p class="text-[9px] text-white/30 uppercase tracking-widest">Kilometraje</p><p class="text-sm font-bold text-white">{{ selectedMachine.kilometraje_actual }} km</p></div>
                </div>
              </div>

              <!-- Mantenimiento & Adquisición -->
              <div class="border-t border-white/5 pt-6 mt-6">
                <h5 class="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-4 flex items-center gap-3">
                  <div class="w-8 h-[1px] bg-white/10"></div> Mantenimiento y Compra
                </h5>
                <div class="grid grid-cols-2 gap-4">
                  <div><p class="text-[9px] text-white/30 uppercase tracking-widest">Int. Servicio</p><p class="text-sm font-bold text-white">{{ selectedMachine.intervalo_servicio ? selectedMachine.intervalo_servicio + ' h' : 'N/A' }}</p></div>
                  <div><p class="text-[9px] text-white/30 uppercase tracking-widest">Último Servicio</p><p class="text-sm font-bold text-white">{{ formatDate(selectedMachine.fecha_ultimo_servicio) || 'N/A' }}</p></div>
                  <div><p class="text-[9px] text-white/30 uppercase tracking-widest">Adquisición</p><p class="text-sm font-bold text-white">{{ formatDate(selectedMachine.fecha_adquisicion) || 'N/A' }}</p></div>
                  <div><p class="text-[9px] text-white/30 uppercase tracking-widest">Costo</p><p class="text-sm font-bold text-white">{{ selectedMachine.costo_adquisicion ? 'Q ' + selectedMachine.costo_adquisicion : 'N/A' }}</p></div>
                </div>
              </div>
              
              <!-- Personnel & Project -->
              <div class="p-6 rounded-[32px] bg-white/5 border border-white/5">
                <div class="space-y-4">
                  <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-primary/20 flex items-center justify-center text-primary"><UserIcon class="w-6 h-6" /></div>
                    <div><p class="text-[10px] text-white/20 uppercase tracking-widest">Operador</p><p class="text-base font-bold text-white">{{ selectedMachine.operador_nombre || 'Sin asignar' }}</p></div>
                  </div>
                  <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-sky-500/20 flex items-center justify-center text-sky-400"><MapPinIcon class="w-6 h-6" /></div>
                    <div><p class="text-[10px] text-white/20 uppercase tracking-widest">Proyecto</p><p class="text-base font-bold text-white">{{ selectedMachine.proyecto_nombre || 'Sin asignar' }}</p></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Log Details Modal -->
    <transition name="fade">
      <div v-if="selectedLog" class="fixed inset-0 z-50 flex items-center justify-center p-6">
        <div @click="selectedLog = null" class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-2xl glass-card rounded-[40px] overflow-hidden border border-white/10 shadow-2xl p-8" data-aos="zoom-in-up" data-aos-duration="1000">
          <button @click="selectedLog = null" class="absolute top-8 right-8 z-10 w-10 h-10 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center border border-white/10 text-white/40 hover:text-white transition-all">
            <XMarkIcon class="w-5 h-5" />
          </button>
          
          <h3 class="text-2xl font-bold text-white mb-6">Detalle de Bitácora</h3>
          
          <div class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
              <div>
                <p class="text-[10px] text-white/30 uppercase font-bold tracking-widest">Fecha</p>
                <p class="text-base font-bold text-white">{{ formatDate(selectedLog.fecha) }}</p>
              </div>
              <div>
                <p class="text-[10px] text-white/30 uppercase font-bold tracking-widest">Máquina</p>
                <p class="text-base font-bold text-white">{{ selectedLog.maquina_nombre }}</p>
              </div>
              <div>
                <p class="text-[10px] text-white/30 uppercase font-bold tracking-widest">Proyecto</p>
                <p class="text-base font-bold text-white">{{ selectedLog.proyecto_nombre || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-[10px] text-white/30 uppercase font-bold tracking-widest">Operador</p>
                <p class="text-base font-bold text-white">{{ selectedLog.operador_nombre || 'N/A' }}</p>
              </div>
            </div>
            
            <div class="bg-white/5 border border-white/10 rounded-2xl p-5 flex justify-between items-center">
              <div>
                <p class="text-[10px] text-white/30 uppercase font-bold tracking-widest">Horómetro Inicial</p>
                <p class="text-xl font-black text-white">{{ selectedLog.horometro_inicial }}</p>
              </div>
              <div class="h-10 w-[1px] bg-white/10"></div>
              <div>
                <p class="text-[10px] text-white/30 uppercase font-bold tracking-widest">Horómetro Final</p>
                <p class="text-xl font-black text-white">{{ selectedLog.horometro_final }}</p>
              </div>
              <div class="h-10 w-[1px] bg-white/10"></div>
              <div>
                <p class="text-[10px] text-white/30 uppercase font-bold tracking-widest">Trabajado</p>
                <p class="text-xl font-black text-primary">{{ selectedLog.horometro_final - selectedLog.horometro_inicial }} h</p>
              </div>
            </div>
            
            <div class="grid grid-cols-2 gap-6">
              <div>
                <p class="text-[10px] text-white/30 uppercase font-bold tracking-widest">Combustible (Gal/Lts)</p>
                <p class="text-base font-bold text-white">{{ selectedLog.combustible_consumido || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-[10px] text-white/30 uppercase font-bold tracking-widest">Registrado el</p>
                <p class="text-base font-bold text-white">{{ new Date(selectedLog.created_at).toLocaleString('es-GT') }}</p>
              </div>
            </div>
            
            <div>
              <p class="text-[10px] text-white/30 uppercase font-bold tracking-widest mb-2">Observaciones</p>
              <div class="bg-black/20 rounded-xl p-4 border border-white/5 min-h-[80px]">
                <p class="text-sm text-white/80 whitespace-pre-wrap">{{ selectedLog.observaciones || 'Sin observaciones.' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>


<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { 
  ArrowTrendingUpIcon, ArrowTrendingDownIcon, WrenchScrewdriverIcon, ExclamationTriangleIcon, 
  MapPinIcon, ClockIcon, Square3Stack3DIcon, ListBulletIcon, ArchiveBoxIcon, CubeIcon, 
  XMarkIcon, UserIcon, ChartBarIcon, PlusIcon, PencilIcon, TrashIcon, EyeIcon, MagnifyingGlassIcon, ChevronLeftIcon, ChevronRightIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const BASE_URL = '/concretos-oriente/Backend/api/v1';

// State
const activeTab = ref("machinery");
const tabs = [
  { id: 'machinery', name: 'Maquinaria Pesada' },
  { id: 'log', name: 'Bitácora Diaria' }
];

const machinery = ref([]);
const logs = ref([]);
const personnel = ref([]);
const projects = ref([]);
const loading = ref(true);
const loadingLogs = ref(true);


const searchMachine = ref("");
const filterCategory = ref("");
const filterStatus = ref("");

const searchLog = ref("");
const filterLogProject = ref("");

const filteredMachinery = computed(() => {
  return machinery.value.filter(m => {
    const searchVal = searchMachine.value.toLowerCase();
    const matchSearch = (m.codigo_interno && m.codigo_interno.toLowerCase().includes(searchVal)) ||
                        (m.marca && m.marca.toLowerCase().includes(searchVal)) ||
                        (m.modelo && m.modelo.toLowerCase().includes(searchVal)) ||
                        (m.placa && m.placa.toLowerCase().includes(searchVal));
    const matchCat = filterCategory.value === "" || m.categoria === filterCategory.value;
    const matchStatus = filterStatus.value === "" || m.estado === filterStatus.value;
    return matchSearch && matchCat && matchStatus;
  });
});

const filteredLogs = computed(() => {
  return logs.value.filter(l => {
    const searchVal = searchLog.value.toLowerCase();
    const matchSearch = (l.maquina_nombre && l.maquina_nombre.toLowerCase().includes(searchVal)) ||
                        (l.operador_nombre && l.operador_nombre.toLowerCase().includes(searchVal));
    const matchProj = filterLogProject.value === "" || l.proyecto_id === filterLogProject.value;
    return matchSearch && matchProj;
  });
});


const currentLogPage = ref(1);
const itemsPerPage = 10;

const totalLogPages = computed(() => Math.ceil(filteredLogs.value.length / itemsPerPage));

const paginatedLogs = computed(() => {
  const start = (currentLogPage.value - 1) * itemsPerPage;
  return filteredLogs.value.slice(start, start + itemsPerPage);
});

watch([searchLog, filterLogProject], () => {
  currentLogPage.value = 1;
});

const selectedMachine = ref(null);



const selectedLog = ref(null);

const openViewLog = (log) => {
  selectedLog.value = log;
};


// Modals State
const showMachineModal = ref(false);
const isEditingMachine = ref(false);
const editingMachineId = ref(null);
const isSubmitting = ref(false);

const showLogModal = ref(false);

// Forms Data
const formMachine = ref({
  categoria: '',
  codigo_interno: '',
  marca: '',
  modelo: '',
  numero_serie: '',
  anio_fabricacion: '',
  placa: '',
  horometro_actual: 0,
  kilometraje_actual: 0,
  intervalo_servicio: '',
  fecha_ultimo_servicio: '',
  operador_id: null,
  proyecto_id: null,
  estado: 'Activo',
  costo_adquisicion: '',
  fecha_adquisicion: '',
  foto: null
});

const formLog = ref({
  maquina_id: '',
  fecha: new Date().toISOString().split('T')[0],
  proyecto_id: null,
  horometro_inicial: 0,
  horometro_final: 0,
  operador_id: null,
  combustible_consumido: '',
  observaciones: ''
});

// Mock Metrics (Puedes conectarlas a la API luego)
const metrics = computed(() => {
  const activas = machinery.value.filter(m => m.estado === 'Activo').length;
  const total = machinery.value.length;
  const pct = total > 0 ? Math.round((activas/total)*100) : 0;
  const mtto = machinery.value.filter(m => m.estado === 'En Mantenimiento' || m.estado === 'En Reparación').length;

  return [
    { label: "Maquinaria Registrada", value: total.toString(), trend: "Total de equipos", icon: WrenchScrewdriverIcon, color: "text-primary" },
    { label: "Equipos Operativos", value: `${activas} / ${total}`, percentage: pct, color: "text-primary" },
    { label: "En Mantenimiento", value: mtto.toString(), trend: "Atención requerida", icon: ExclamationTriangleIcon, color: "text-orange-500" },
    { label: "Bitácoras Registradas", value: logs.value.length.toString(), trend: "Total histórico", icon: ListBulletIcon, color: "text-sky-400" },
  ];
});


// ----------------------------------------------------------------
// Lifecycle & Fetches
// ----------------------------------------------------------------
onMounted(() => {
  fetchMachinery();
  fetchLogs();
  fetchPersonnel();
  fetchProjects();
});

const fetchMachinery = async () => {
  loading.value = true;
  try {
    const res = await fetch(`${BASE_URL}/machinery`);
    const data = await res.json();
    if (data.status === 'success') machinery.value = data.data;
  } catch (err) { console.error(err); }
  loading.value = false;
};

const fetchLogs = async () => {
  loadingLogs.value = true;
  try {
    const res = await fetch(`${BASE_URL}/machinery-log`);
    const data = await res.json();
    if (data.status === 'success') logs.value = data.data;
  } catch (err) { console.error(err); }
  loadingLogs.value = false;
};

const fetchPersonnel = async () => {
  try {
    const res = await fetch(`${BASE_URL}/personnel`);
    const data = await res.json();
    if (data.status === 'success') personnel.value = data.data;
  } catch (err) {}
};

const fetchProjects = async () => {
  try {
    const res = await fetch(`${BASE_URL}/projects`);
    const data = await res.json();
    if (data.status === 'success') projects.value = data.data;
  } catch (err) {}
};

// ----------------------------------------------------------------
// Machine CRUD
// ----------------------------------------------------------------
const openMachineModal = () => {
  resetMachineForm();
  isEditingMachine.value = false;
  editingMachineId.value = null;
  showMachineModal.value = true;
};

const openEditMachine = (m) => {
  formMachine.value = { ...m, foto: null };
  isEditingMachine.value = true;
  editingMachineId.value = m.id;
  showMachineModal.value = true;
};

const closeMachineModal = () => {
  showMachineModal.value = false;
  resetMachineForm();
};

const resetMachineForm = () => {
  formMachine.value = {
    categoria: '', codigo_interno: '', marca: '', modelo: '', numero_serie: '',
    anio_fabricacion: '', placa: '', horometro_actual: 0, kilometraje_actual: 0,
    intervalo_servicio: '', fecha_ultimo_servicio: '', operador_id: null,
    proyecto_id: null, estado: 'Activo', costo_adquisicion: '', fecha_adquisicion: '', foto: null
  };
};

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (file) formMachine.value.foto = file;
};

const submitMachine = async () => {
  isSubmitting.value = true;
  const fd = new FormData();
  
  Object.keys(formMachine.value).forEach(key => {
    if (formMachine.value[key] !== null && formMachine.value[key] !== '') {
      fd.append(key, formMachine.value[key]);
    }
  });

  try {
    const url = isEditingMachine.value ? `${BASE_URL}/machinery/${editingMachineId.value}` : `${BASE_URL}/machinery`;
    const res = await fetch(url, { method: 'POST', body: fd });
    const result = await res.json();
    
    if (result.status === 'success') {
      await fetchMachinery();
      closeMachineModal();
      Swal.fire({ background: '#0f172a', color: '#fff', icon: 'success', title: '¡Guardado!' });
    } else {
      Swal.fire({ background: '#0f172a', color: '#fff', icon: 'error', title: 'Error', text: result.message });
    }
  } catch (err) {
    Swal.fire({ background: '#0f172a', color: '#fff', icon: 'error', title: 'Error de red' });
  }
  isSubmitting.value = false;
};

const deleteMachine = async (id) => {
  const { isConfirmed } = await Swal.fire({
    background: '#0f172a', color: '#fff', title: '¿Eliminar maquinaria?',
    text: "Se eliminará también su bitácora. Esta acción es irreversible.",
    icon: 'warning', showCancelButton: true, confirmButtonColor: '#f43f5e',
    confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
  });

  if (!isConfirmed) return;

  try {
    const res = await fetch(`${BASE_URL}/machinery/${id}`, { method: 'DELETE' });
    const data = await res.json();
    if (data.status === 'success') {
      await fetchMachinery();
      await fetchLogs(); // Actualizar logs también
      Swal.fire({ background: '#0f172a', color: '#fff', icon: 'success', title: 'Eliminado' });
    }
  } catch (err) { }
};

// ----------------------------------------------------------------
// Log CRUD
// ----------------------------------------------------------------
const openLogModal = () => {
  formLog.value = {
    maquina_id: '', fecha: new Date().toISOString().split('T')[0], proyecto_id: null,
    horometro_inicial: 0, horometro_final: 0, operador_id: null,
    combustible_consumido: '', observaciones: ''
  };
  showLogModal.value = true;
};

const closeLogModal = () => showLogModal.value = false;

const submitLog = async () => {
  isSubmitting.value = true;
  const fd = new FormData();
  Object.keys(formLog.value).forEach(key => {
    if (formLog.value[key] !== null && formLog.value[key] !== '') {
      fd.append(key, formLog.value[key]);
    }
  });

  try {
    const res = await fetch(`${BASE_URL}/machinery-log`, { method: 'POST', body: fd });
    const result = await res.json();
    
    if (result.status === 'success') {
      await fetchLogs();
      await fetchMachinery(); // Por si el horómetro cambió
      closeLogModal();
      Swal.fire({ background: '#0f172a', color: '#fff', icon: 'success', title: '¡Bitácora Guardada!' });
    } else {
      Swal.fire({ background: '#0f172a', color: '#fff', icon: 'error', title: 'Error', text: result.message });
    }
  } catch (err) { }
  isSubmitting.value = false;
};

const deleteLog = async (id) => {
  const { isConfirmed } = await Swal.fire({
    background: '#0f172a', color: '#fff', title: '¿Eliminar registro?', icon: 'warning',
    showCancelButton: true, confirmButtonColor: '#f43f5e', confirmButtonText: 'Eliminar'
  });

  if (!isConfirmed) return;

  try {
    const res = await fetch(`${BASE_URL}/machinery-log/${id}`, { method: 'DELETE' });
    const data = await res.json();
    if (data.status === 'success') await fetchLogs();
  } catch (err) { }
};

// ----------------------------------------------------------------
// Utils
// ----------------------------------------------------------------
const getStatusColor = (status) => {
  const colors = {
    'Activo': 'bg-emerald-500 text-emerald-400',
    'En Mantenimiento': 'bg-amber-500 text-amber-400',
    'En Reparación': 'bg-rose-500 text-rose-400',
    'Inactivo': 'bg-slate-500 text-slate-400'
  };
  return colors[status] || 'bg-white text-white';
};

const getPhotoUrl = (path) => {
  return `/concretos-oriente/Backend/${path}?t=${Date.now()}`;
};

const formatDate = (val) => {
  if (!val) return '';
  const [y, m, d] = val.split('-');
  return `${d}/${m}/${y}`;
};
</script>

<style scoped>
.fade-slide-enter-active, .fade-slide-leave-active { transition: opacity 0.4s ease-out, transform 0.4s ease-out; }
.fade-slide-enter-from { opacity: 0; transform: scale(0.98); }
.fade-slide-leave-to { opacity: 0; transform: scale(0.98); }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
