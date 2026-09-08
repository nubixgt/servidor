<template>
  <div class="pt-20 pb-10 px-4 md:px-10 md:pb-20 max-w-7xl mx-auto space-y-10 relative">
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h2 class="text-4xl font-bold tracking-tight text-white mb-2">
          {{ activeTab === 'register' ? (isEditingMachine ? 'Modificar Maquinaria' : 'Registrar Maquinaria') : 'Gestión de Maquinaria' }}
        </h2>
        <p class="text-white/60">Registra y controla el estado de los equipos pesados y livianos, y su bitácora diaria.</p>
      </div>

      <!-- Pill Tab Switcher -->
      <div class="flex gap-2 bg-black/30 border border-white/10 rounded-2xl p-1 w-fit">
        <button 
          @click="switchTab('machinery')" 
          :class="['px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all', activeTab === 'machinery' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-white/50 hover:text-white']"
        >
          Ver Equipos
        </button>
        <button 
          @click="switchTab('log')" 
          :class="['px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all', activeTab === 'log' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-white/50 hover:text-white']"
        >
          Bitácora Diaria
        </button>
        <button 
          @click="startRegister" 
          :class="['px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center gap-2', activeTab === 'register' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-white/50 hover:text-white']"
        >
          <PlusIcon class="w-3.5 h-3.5" /> {{ isEditingMachine ? 'Editando' : 'Registrar' }}
        </button>
      </div>
    </div>

    <!-- KPI Metrics Section -->
    <section class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div
        v-for="(metric, i) in metrics"
        :key="i"
        class="glass-card p-6 rounded-3xl border border-white/5 flex flex-col justify-between h-40 transition-all group hover:-translate-y-1 hover:border-white/15"
      >
        <div>
          <p class="text-white/40 text-[10px] font-black uppercase tracking-[0.2em] mb-2">{{ metric.label }}</p>
          <h3 :class="`text-3xl font-black italic tracking-tighter ${metric.color === 'text-error' ? 'text-rose-400' : 'text-white'}`">{{ metric.value }}</h3>
        </div>
        <div v-if="metric.percentage !== undefined" class="w-full bg-white/5 h-2 rounded-full overflow-hidden p-[1px]">
          <div 
            :style="{ width: `${metric.percentage}%` }"
            class="bg-primary h-full rounded-full shadow-[0_0_10px_#6366f1] transition-all duration-1000"
          ></div>
        </div>
        <div v-else :class="`flex items-center gap-2 ${metric.color} bg-white/5 px-3 py-1.5 rounded-xl w-fit border border-white/5`">
          <component :is="metric.icon" v-if="metric.icon" class="w-3.5 h-3.5" />
          <span class="text-[9px] font-black uppercase tracking-wider">{{ metric.trend }}</span>
        </div>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════ TAB: VER EQUIPOS ═══════════════════════════════════════════ -->
    <template v-if="activeTab === 'machinery'">
      <!-- Filters & Search -->
      <section class="flex flex-col lg:flex-row gap-4 items-center justify-between border-b border-white/5 pb-6">
        <div class="flex gap-2 bg-black/30 border border-white/10 rounded-2xl p-1 overflow-x-auto w-full lg:w-auto">
          <button 
            @click="filterType = ''" 
            :class="['px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap', filterType === '' ? 'bg-primary text-white shadow-lg' : 'text-white/40 hover:text-white']"
          >
            Todos
          </button>
          <button 
            @click="filterType = 'Pesada'" 
            :class="['px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap', filterType === 'Pesada' ? 'bg-primary text-white shadow-lg' : 'text-white/40 hover:text-white']"
          >
            Pesada
          </button>
          <button 
            @click="filterType = 'Liviana'" 
            :class="['px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap', filterType === 'Liviana' ? 'bg-amber-500 text-white shadow-lg' : 'text-white/40 hover:text-white']"
          >
            Liviana
          </button>
        </div>

        <div class="flex flex-wrap lg:flex-nowrap gap-3 w-full lg:w-auto flex-1 justify-end">
          <div class="relative w-full lg:w-80">
            <MagnifyingGlassIcon class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-white/40" />
            <input 
              v-model="searchMachine" 
              type="text" 
              placeholder="Buscar por código, marca, modelo, serie..." 
              class="w-full bg-black/20 border border-white/10 rounded-2xl pl-11 pr-4 py-3 text-xs font-bold text-white placeholder-white/30 focus:outline-none focus:border-primary/50 transition-all"
            />
          </div>

          <select v-model="filterStatus" class="bg-black/20 border border-white/10 rounded-2xl px-4 py-3 text-xs font-bold text-white focus:outline-none focus:border-primary/50 appearance-none min-w-[160px]">
            <option value="">Todos los Estados</option>
            <option value="Activo">Activo</option>
            <option value="Inactivo">Inactivo</option>
          </select>
        </div>
      </section>

      <!-- Machinery Grid -->
      <div v-if="loading" class="text-center py-20 text-white/40">Cargando maquinaria...</div>
      <div v-else-if="filteredMachinery.length === 0" class="text-center py-20 text-white/40 glass-card rounded-3xl border border-white/10">
        <WrenchScrewdriverIcon class="w-12 h-12 text-white/10 mx-auto mb-4" />
        <p class="font-black uppercase tracking-widest text-xs">No hay maquinaria registrada con ese criterio.</p>
      </div>
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="m in filteredMachinery" 
          :key="m.id"
          class="glass-card rounded-[32px] overflow-hidden group hover:-translate-y-1.5 transition-all duration-300 border border-white/5 flex flex-col justify-between"
        >
          <!-- Imagen y Badges -->
          <div>
            <div class="h-48 relative overflow-hidden cursor-pointer bg-black/30" @click="selectedMachine = m">
              <img v-if="m.foto_path" :src="getPhotoUrl(m.foto_path)" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" :alt="m.modelo" />
              <div v-else class="w-full h-full flex items-center justify-center text-white/15">
                <WrenchScrewdriverIcon class="w-16 h-16" />
              </div>
              <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-transparent"></div>
              
              <!-- Estado Badge -->
              <div class="absolute top-4 right-4 px-3 py-1.5 backdrop-blur-xl bg-black/50 rounded-full text-[9px] font-black uppercase tracking-wider flex items-center gap-2 border border-white/10 shadow-lg">
                <span :class="`w-2 h-2 rounded-full ${getStatusColor(m.estado)}`"></span>
                {{ m.estado }}
              </div>

              <!-- Tipo Badge -->
              <div class="absolute top-4 left-4">
                <span :class="m.clasificacion_tipo === 'Liviana' ? 'bg-amber-500/80 text-white border-amber-400/40' : 'bg-primary/80 text-white border-primary/40'" class="px-2.5 py-1 text-[9px] font-black uppercase rounded-lg border backdrop-blur-md tracking-wider shadow-lg">
                  {{ m.clasificacion_tipo || 'Pesada' }}
                </span>
              </div>
            </div>
            
            <!-- Card Body Info -->
            <div class="p-6 space-y-4">
              <div class="cursor-pointer" @click="selectedMachine = m">
                <div class="flex items-center gap-2 mb-1">
                  <span v-if="m.codigo_interno" class="font-mono text-xs font-black tracking-widest bg-primary/20 border border-primary/30 px-2 py-0.5 rounded-md text-primary">
                    {{ m.codigo_interno }}
                  </span>
                  <span class="text-xs font-bold text-white/40 uppercase tracking-wider">{{ m.categoria }}</span>
                </div>
                <h4 class="text-xl font-black italic uppercase text-white tracking-tight leading-snug">{{ m.marca }} {{ m.modelo }}</h4>
              </div>

              <div class="bg-black/20 rounded-2xl p-4 border border-white/5 space-y-2 text-xs">
                <!-- Horómetro -->
                <div class="flex items-center justify-between">
                  <span class="text-[9px] font-black text-white/30 uppercase tracking-widest flex items-center gap-1.5">
                    <ClockIcon class="w-3.5 h-3.5 text-primary" /> Horómetro
                  </span>
                  <span class="font-black text-white tracking-wider">{{ m.horometro_actual }} hrs</span>
                </div>

                <div v-if="m.no_factura" class="flex items-center justify-between">
                  <span class="text-[9px] font-black text-white/30 uppercase tracking-widest">No. Factura</span>
                  <span class="font-black text-white/70">{{ m.no_factura }}</span>
                </div>

                <div v-if="m.clasificacion_tipo === 'Liviana' && m.fecha_servicio" class="flex items-center justify-between">
                  <span class="text-[9px] font-black text-amber-400/70 uppercase tracking-widest">Fec. Servicio</span>
                  <span class="font-black text-amber-300">{{ formatDate(m.fecha_servicio) }}</span>
                </div>

                <div class="flex items-center justify-between">
                  <span class="text-[9px] font-black text-white/30 uppercase tracking-widest">Operador</span>
                  <span class="font-black text-white/80 truncate max-w-[140px]">{{ m.operador_nombre || 'Sin asignar' }}</span>
                </div>

                <div class="flex items-center justify-between">
                  <span class="text-[9px] font-black text-white/30 uppercase tracking-widest">Proyecto</span>
                  <span class="font-black text-primary truncate max-w-[140px]">{{ m.proyecto_nombre || 'Sin asignar' }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions footer -->
          <div class="px-6 pb-6 pt-2 border-t border-white/5 flex items-center justify-end gap-2">
            <button @click="selectedMachine = m" class="px-3.5 py-2 bg-primary/10 hover:bg-primary/20 text-primary rounded-xl border border-primary/20 text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-1">
              <EyeIcon class="w-3.5 h-3.5" /> Detalles
            </button>
            <button @click="openEditMachine(m)" class="px-3.5 py-2 bg-white/5 hover:bg-white/10 text-white/70 hover:text-white rounded-xl border border-white/5 text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-1">
              <PencilIcon class="w-3.5 h-3.5" /> Modificar
            </button>
            <button @click="deleteMachine(m.id)" class="px-3.5 py-2 bg-white/5 hover:bg-white/10 text-white/30 hover:text-rose-400 rounded-xl border border-white/5 text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-1">
              <TrashIcon class="w-3.5 h-3.5" /> Eliminar
            </button>
          </div>
        </div>
      </div>
    </template>

    <!-- ═══════════════════════════════════════════ TAB: BITÁCORA DIARIA ═══════════════════════════════════════════ -->
    <template v-else-if="activeTab === 'log'">
      <div class="glass-card rounded-[32px] overflow-hidden border border-white/10">
        <!-- Top bar with New Log button -->
        <div class="p-6 border-b border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex flex-1 gap-3">
            <div class="relative w-full md:w-80">
              <MagnifyingGlassIcon class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-white/40" />
              <input 
                v-model="searchLog" 
                type="text" 
                placeholder="Buscar máquina u operador..." 
                class="w-full bg-black/20 border border-white/10 rounded-2xl pl-11 pr-4 py-3 text-xs font-bold text-white placeholder-white/30 focus:outline-none focus:border-primary/50 transition-all"
              />
            </div>
            <select v-model="filterLogProject" class="bg-black/20 border border-white/10 rounded-2xl px-4 py-3 text-xs font-bold text-white focus:outline-none focus:border-primary/50 appearance-none min-w-[180px]">
              <option value="">Todos los Proyectos</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.nombre }}</option>
            </select>
          </div>

          <button
            @click="openLogModal"
            class="px-6 py-3 bg-primary hover:opacity-90 text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2 shadow-lg shadow-primary/30 shrink-0"
          >
            <PlusIcon class="w-4 h-4" /> Registrar Bitácora
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full min-w-[640px] text-left">
            <thead>
              <tr class="border-b border-white/5 bg-white/[0.02]">
                <th class="px-6 py-5 text-[10px] font-black text-white/30 uppercase tracking-[0.2em]">Fecha</th>
                <th class="px-6 py-5 text-[10px] font-black text-white/30 uppercase tracking-[0.2em]">Máquina</th>
                <th class="px-6 py-5 text-[10px] font-black text-white/30 uppercase tracking-[0.2em]">Proyecto</th>
                <th class="px-6 py-5 text-[10px] font-black text-white/30 uppercase tracking-[0.2em]">Horómetro</th>
                <th class="px-6 py-5 text-[10px] font-black text-white/30 uppercase tracking-[0.2em]">Operador</th>
                <th v-if="authStore.userRole === 'admin'" class="px-6 py-5 text-[10px] font-black text-white/30 uppercase tracking-[0.2em]">Creador</th>
                <th class="px-6 py-5 text-right text-[10px] font-black text-white/30 uppercase tracking-[0.2em]">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-if="loadingLogs">
                <td colspan="7" class="px-6 py-10 text-center text-white/50 text-xs font-bold">Cargando bitácoras...</td>
              </tr>
              <tr v-else-if="filteredLogs.length === 0">
                <td colspan="7" class="px-6 py-12 text-center text-white/40 font-black uppercase tracking-widest text-xs">No hay bitácoras registradas</td>
              </tr>
              <tr v-for="log in paginatedLogs" :key="log.id" class="hover:bg-white/5 transition-all">
                <td class="px-6 py-4 text-xs font-bold text-white/80">{{ formatDate(log.fecha) }}</td>
                <td class="px-6 py-4 font-black uppercase text-sm text-white">{{ log.maquina_nombre }}</td>
                <td class="px-6 py-4 text-xs font-bold text-primary">{{ log.proyecto_nombre || 'N/A' }}</td>
                <td class="px-6 py-4">
                  <p class="text-[11px] font-bold text-white/50">Ini: <span class="text-white">{{ log.horometro_inicial }}</span></p>
                  <p class="text-[11px] font-bold text-white/50">Fin: <span class="text-white">{{ log.horometro_final }}</span></p>
                </td>
                <td class="px-6 py-4 text-xs font-bold text-white/80">{{ log.operador_nombre || 'N/A' }}</td>
                <td v-if="authStore.userRole === 'admin'" class="px-6 py-4 text-xs font-bold text-white/60">{{ log.creado_por_nombre || 'N/A' }}</td>
                <td class="px-6 py-4 text-right">
                  <div class="flex justify-end gap-2">
                    <button @click="openViewLog(log)" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all" title="Visualizar">
                      <EyeIcon class="w-4 h-4" />
                    </button>
                    <button @click="deleteLog(log.id)" class="p-2 text-white/40 hover:text-rose-400 hover:bg-white/10 rounded-xl transition-all" title="Eliminar">
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Paginación -->
        <div v-if="totalLogPages > 1" class="flex justify-between items-center px-6 py-4 border-t border-white/5 bg-black/20">
          <p class="text-xs text-white/40 font-semibold tracking-widest">
            Página <span class="text-white font-bold">{{ currentLogPage }}</span> de <span class="text-white font-bold">{{ totalLogPages }}</span>
          </p>
          <div class="flex gap-2">
            <button 
              @click="currentLogPage--" 
              :disabled="currentLogPage === 1"
              class="w-9 h-9 rounded-xl bg-white/5 flex items-center justify-center text-white/40 hover:bg-white/10 hover:text-white disabled:opacity-30 disabled:pointer-events-none transition-all"
            >
              <ChevronLeftIcon class="w-4 h-4" />
            </button>
            <button 
              @click="currentLogPage++" 
              :disabled="currentLogPage === totalLogPages"
              class="w-9 h-9 rounded-xl bg-white/5 flex items-center justify-center text-white/40 hover:bg-white/10 hover:text-white disabled:opacity-30 disabled:pointer-events-none transition-all"
            >
              <ChevronRightIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </template>

    <!-- ═══════════════════════════════════════════ TAB: FORMULARIO (REGISTRAR/EDITAR) ═══════════════════════════════════════════ -->
    <template v-else-if="activeTab === 'register'">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- Left: Form Sections -->
        <div class="lg:col-span-8 space-y-6">

          <!-- Clasificación Inicial: Pesada vs Liviana -->
          <section class="glass-card p-8 rounded-3xl border border-white/5 relative overflow-hidden">
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>
            <h3 class="text-xs font-black uppercase tracking-widest text-primary mb-6 flex items-center gap-2">
              <WrenchScrewdriverIcon class="w-4 h-4" /> Clasificación de Maquinaria <span class="text-rose-400">*</span>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <button 
                type="button" 
                @click="formMachine.clasificacion_tipo = 'Pesada'"
                :class="formMachine.clasificacion_tipo === 'Pesada' ? 'bg-primary/20 border-primary text-white shadow-lg shadow-primary/20 ring-1 ring-primary/50' : 'bg-black/20 border-white/10 text-white/50 hover:border-white/20'"
                class="flex items-center justify-center gap-3 p-5 rounded-2xl border font-black uppercase tracking-wider text-xs transition-all"
              >
                <WrenchScrewdriverIcon class="w-5 h-5 text-primary" />
                Maquinaria Pesada (Con Seguro)
              </button>
              <button 
                type="button" 
                @click="formMachine.clasificacion_tipo = 'Liviana'"
                :class="formMachine.clasificacion_tipo === 'Liviana' ? 'bg-amber-500/20 border-amber-500 text-white shadow-lg shadow-amber-500/20 ring-1 ring-amber-500/50' : 'bg-black/20 border-white/10 text-white/50 hover:border-white/20'"
                class="flex items-center justify-center gap-3 p-5 rounded-2xl border font-black uppercase tracking-wider text-xs transition-all"
              >
                <ClockIcon class="w-5 h-5 text-amber-400" />
                Maquinaria Liviana (Sin Seguro)
              </button>
            </div>
          </section>

          <!-- Sección 1: Información General -->
          <section class="glass-card p-8 rounded-3xl border border-white/5 relative overflow-hidden">
            <h3 class="text-xs font-black uppercase tracking-widest text-primary mb-6 flex items-center gap-2">
              <InformationCircleIcon class="w-4 h-4" /> Información General
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

              <!-- Código Interno -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Código Interno <span class="text-rose-400">*</span></label>
                <input 
                  v-model="formMachine.codigo_interno" 
                  type="text" 
                  required 
                  placeholder="Ej. EX-042"
                  class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black uppercase tracking-wider text-white" 
                />
              </div>

              <!-- Tipo / Categoría -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Categoría / Tipo <span class="text-rose-400">*</span></label>
                <select 
                  v-model="formMachine.categoria" 
                  required
                  class="w-full h-12 px-4 rounded-xl bg-slate-950/65 border border-white/10 text-sm font-black uppercase text-white focus:outline-none focus:border-primary"
                >
                  <option value="">Seleccionar categoría</option>
                  <template v-if="formMachine.clasificacion_tipo === 'Liviana'">
                    <option value="Rotomartillo">Rotomartillo</option>
                    <option value="Bailarina">Bailarina</option>
                    <option value="Sapo">Sapo</option>
                    <option value="Generador Eléctrico">Generador Eléctrico</option>
                    <option value="Luces">Luces</option>
                  </template>
                  <template v-else>
                    <option value="Retro">Retro</option>
                    <option value="Patrol">Patrol</option>
                    <option value="Excavadora">Excavadora</option>
                    <option value="Cargador frontal">Cargador frontal</option>
                    <option value="Rodo">Rodo</option>
                  </template>
                </select>
              </div>

              <!-- No. de Factura -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">No. de Factura</label>
                <input 
                  v-model="formMachine.no_factura" 
                  type="text" 
                  placeholder="Ej. FAC-00921"
                  class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" 
                />
              </div>

              <!-- Estado -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Estado <span class="text-rose-400">*</span></label>
                <select 
                  v-model="formMachine.estado" 
                  required
                  class="w-full h-12 px-4 rounded-xl bg-slate-950/65 border border-white/10 text-sm font-black uppercase text-white focus:outline-none focus:border-primary"
                >
                  <option value="Activo">Activo</option>
                  <option value="Inactivo">Inactivo</option>
                </select>
              </div>

              <!-- Marca -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Marca <span class="text-rose-400">*</span></label>
                <input 
                  v-model="formMachine.marca" 
                  type="text" 
                  required 
                  placeholder="Caterpillar, Komatsu, DeWalt..."
                  class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" 
                />
              </div>

              <!-- Modelo -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Modelo <span class="text-rose-400">*</span></label>
                <input 
                  v-model="formMachine.modelo" 
                  type="text" 
                  required 
                  placeholder="CAT 320, D6T, 350L..."
                  class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" 
                />
              </div>

              <!-- Año de Fabricación -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Año de Fabricación</label>
                <input 
                  v-model="formMachine.anio_fabricacion" 
                  type="number" 
                  min="1900" 
                  max="2100" 
                  placeholder="2024"
                  class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" 
                />
              </div>

              <!-- Número de Serie -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Número de Serie</label>
                <input 
                  v-model="formMachine.numero_serie" 
                  type="text" 
                  placeholder="CAT320D123456"
                  class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" 
                />
              </div>

              <!-- Placa -->
              <div class="space-y-2 md:col-span-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Placa (si aplica)</label>
                <input 
                  v-model="formMachine.placa" 
                  type="text" 
                  placeholder="C-123XYZ"
                  class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black uppercase tracking-widest text-white" 
                />
              </div>
            </div>
          </section>

          <!-- Sección 2: Uso y Horómetro -->
          <section class="glass-card p-8 rounded-3xl border border-white/5">
            <h3 class="text-xs font-black uppercase tracking-widest text-primary mb-6 flex items-center gap-2">
              <ClockIcon class="w-4 h-4" /> Control de Horómetro y Mantenimiento
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <!-- Horómetro de Registro -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Horómetro de Registro <span class="text-rose-400">*</span></label>
                <div class="relative">
                  <input 
                    v-model="formMachine.horometro_actual" 
                    type="number" 
                    min="0" 
                    required 
                    placeholder="0"
                    class="w-full h-12 pl-4 pr-12 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" 
                  />
                  <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-white/40 tracking-widest">HRS</span>
                </div>
              </div>

              <!-- Fecha de Servicio (Requerida en Maquinaria Liviana) -->
              <div v-if="formMachine.clasificacion_tipo === 'Liviana'" class="space-y-2">
                <label class="text-[9px] font-black text-amber-300 uppercase tracking-widest flex items-center gap-1">
                  <CalendarIcon class="w-3.5 h-3.5" /> Fecha de Servicio <span class="text-rose-400">*</span>
                </label>
                <input 
                  v-model="formMachine.fecha_servicio" 
                  type="date" 
                  :required="formMachine.clasificacion_tipo === 'Liviana'"
                  class="w-full h-12 px-4 rounded-xl bg-slate-950/65 border border-amber-500/40 focus:border-amber-400 text-sm font-black text-white focus:outline-none" 
                />
              </div>
            </div>
          </section>

          <!-- Sección 3: Datos del Seguro (Solo para Maquinaria Pesada) -->
          <section v-if="formMachine.clasificacion_tipo === 'Pesada'" class="glass-card p-8 rounded-3xl border border-white/5">
            <h3 class="text-xs font-black uppercase tracking-widest text-primary mb-6 flex items-center gap-2">
              <ShieldCheckIcon class="w-4 h-4" /> Datos de Aseguradora
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Empresa / Aseguradora</label>
                <input 
                  v-model="formMachine.seguro_aseguradora" 
                  type="text" 
                  placeholder="Ej. Seguros G&T, El Roble, Mapfre..."
                  class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" 
                />
              </div>

              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Persona de Contacto</label>
                <input 
                  v-model="formMachine.seguro_contacto_nombre" 
                  type="text" 
                  placeholder="Nombre del asesor de seguros"
                  class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" 
                />
              </div>

              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Teléfono de Contacto</label>
                <input 
                  v-model="formMachine.seguro_contacto_telefono" 
                  type="text" 
                  placeholder="+502 2222-3333"
                  class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" 
                />
              </div>

              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Contrato / Póliza (PDF o Imagen)</label>
                <input 
                  @change="handleInsuranceDocChange" 
                  type="file" 
                  accept=".pdf,.png,.jpg,.jpeg,.doc,.docx" 
                  class="w-full text-xs text-white/60 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-primary/20 file:text-primary hover:file:bg-primary/30 file:transition-all cursor-pointer bg-slate-950/65 border border-white/10 rounded-xl p-1.5" 
                />
              </div>
            </div>
          </section>

          <!-- Sección 4: Asignaciones y Adquisición -->
          <section class="glass-card p-8 rounded-3xl border border-white/5">
            <h3 class="text-xs font-black uppercase tracking-widest text-primary mb-6 flex items-center gap-2">
              <UserIcon class="w-4 h-4" /> Asignaciones y Compra
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              
              <!-- Operador -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Operador Asignado</label>
                <select 
                  v-model="formMachine.operador_id"
                  class="w-full h-12 px-4 rounded-xl bg-slate-950/65 border border-white/10 text-sm font-black uppercase text-white focus:outline-none focus:border-primary"
                >
                  <option :value="null">Ninguno — Sin asignar</option>
                  <option v-for="emp in personnel" :key="emp.id" :value="emp.id">
                    {{ emp.nombres }} {{ emp.apellidos }}
                  </option>
                </select>
              </div>

              <!-- Proyecto -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Proyecto Actual</label>
                <select 
                  v-model="formMachine.proyecto_id"
                  class="w-full h-12 px-4 rounded-xl bg-slate-950/65 border border-white/10 text-sm font-black uppercase text-white focus:outline-none focus:border-primary"
                >
                  <option :value="null">Sin proyecto asignado</option>
                  <option v-for="proj in projects" :key="proj.id" :value="proj.id">
                    {{ proj.nombre }}
                  </option>
                </select>
              </div>

              <!-- Costo Adquisición -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Costo de Adquisición</label>
                <div class="relative">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-black text-white/40">Q</span>
                  <input 
                    v-model="formMachine.costo_adquisicion" 
                    type="number" 
                    min="0" 
                    step="0.01" 
                    placeholder="0.00"
                    class="w-full h-12 pl-8 pr-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" 
                  />
                </div>
              </div>

              <!-- Fecha Adquisición -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Fecha de Adquisición</label>
                <input 
                  v-model="formMachine.fecha_adquisicion" 
                  type="date" 
                  class="w-full h-12 px-4 rounded-xl bg-slate-950/65 border border-white/10 text-sm font-black text-white focus:outline-none focus:border-primary" 
                />
              </div>
            </div>
          </section>
        </div>

        <!-- Right: Photo Upload, Live Preview, and Submit buttons -->
        <div class="lg:col-span-4 space-y-6">

          <!-- Foto Preview & Upload (Multiple Photos) -->
          <section class="glass-card p-6 rounded-3xl border border-white/5">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-xs font-black uppercase tracking-widest text-primary flex items-center gap-2">
                <CameraIcon class="w-4 h-4" /> Registro Fotográfico
              </h3>
              <span v-if="selectedPhotoFiles.length > 0" class="text-[10px] font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-0.5 rounded-lg">
                {{ selectedPhotoFiles.length }} seleccionada(s)
              </span>
              <span v-else-if="existingPhotos.length > 0 && isEditingMachine" class="text-[10px] font-bold text-sky-400 bg-sky-500/10 border border-sky-500/20 px-2.5 py-0.5 rounded-lg">
                {{ existingPhotos.length }} guardada(s)
              </span>
            </div>

            <div class="space-y-3">
              <!-- Upload Box -->
              <label class="group relative aspect-video rounded-2xl bg-white/5 hover:bg-white/10 border-2 border-dashed border-white/10 hover:border-primary transition-all flex flex-col items-center justify-center cursor-pointer overflow-hidden text-center p-4">
                <div class="flex flex-col items-center gap-2 transition-all">
                  <div class="p-3 bg-primary/10 rounded-2xl text-primary group-hover:scale-110 transition-transform">
                    <CameraIcon class="w-6 h-6" />
                  </div>
                  <div>
                    <p class="text-[11px] font-black text-white/80 uppercase tracking-wider">
                      Adjuntar Fotografías
                    </p>
                    <p class="text-[8px] font-bold text-white/30 uppercase tracking-widest mt-0.5">
                      Haz clic para seleccionar múltiples imágenes (JPG, PNG, WEBP)
                    </p>
                  </div>
                </div>
                <input type="file" multiple accept="image/*" @change="handleMultipleFilesChange" class="hidden" />
              </label>

              <!-- Previews de fotos nuevas -->
              <div v-if="photoPreviews.length > 0" class="space-y-2">
                <p class="text-[9px] font-black uppercase tracking-widest text-white/30">Nuevas Fotos a Subir:</p>
                <div class="grid grid-cols-3 gap-2">
                  <div v-for="(preview, idx) in photoPreviews" :key="idx" class="relative group aspect-square rounded-xl overflow-hidden border border-white/10 bg-slate-950/60 shadow-lg">
                    <img :src="preview" class="w-full h-full object-cover" />
                    <button type="button" @click.stop="removeSelectedPhoto(idx)"
                      class="absolute top-1 right-1 p-1 bg-rose-500 hover:bg-rose-600 text-white rounded-lg opacity-80 group-hover:opacity-100 transition-all shadow-md">
                      <XMarkIcon class="w-3.5 h-3.5" />
                    </button>
                    <span class="absolute bottom-1 left-1 px-1.5 py-0.5 bg-black/70 text-[7px] font-black text-white rounded">
                      #{{ idx + 1 }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Fotos existentes si está editando -->
              <div v-else-if="existingPhotos.length > 0 && isEditingMachine" class="space-y-2">
                <p class="text-[9px] font-black uppercase tracking-widest text-white/30">Fotos Actuales:</p>
                <div class="grid grid-cols-3 gap-2">
                  <div v-for="(path, idx) in existingPhotos" :key="idx" class="relative aspect-square rounded-xl overflow-hidden border border-white/10 bg-slate-950/60">
                    <img :src="getPhotoUrl(path)" class="w-full h-full object-cover" />
                    <span class="absolute bottom-1 left-1 px-1.5 py-0.5 bg-black/70 text-[7px] font-black text-white rounded">
                      #{{ idx + 1 }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- Live Preview Card -->
          <div class="bg-primary p-6 rounded-3xl text-white shadow-2xl relative overflow-hidden">
            <div class="relative z-10 space-y-4">
              <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/60">Vista Previa</p>
                <p class="text-2xl font-black italic tracking-tighter uppercase mt-1">
                  {{ (formMachine.marca || formMachine.modelo) ? `${formMachine.marca} ${formMachine.modelo}`.trim() : (formMachine.codigo_interno || 'NUEVA MAQUINARIA') }}
                </p>
              </div>
              <div class="space-y-2 pt-2 text-xs border-t border-white/20">
                <div class="flex justify-between">
                  <span class="text-white/60 text-[9px] font-black uppercase tracking-wider">Código</span>
                  <span class="font-black text-white/95 text-[10px] font-mono">{{ formMachine.codigo_interno || '—' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-white/60 text-[9px] font-black uppercase tracking-wider">Clasificación</span>
                  <span class="font-black text-white/90 text-[10px]">{{ formMachine.clasificacion_tipo }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-white/60 text-[9px] font-black uppercase tracking-wider">Categoría</span>
                  <span class="font-black text-white/90 text-[10px]">{{ formMachine.categoria || '—' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-white/60 text-[9px] font-black uppercase tracking-wider">Horómetro</span>
                  <span class="font-black text-white/90 text-[10px]">{{ formMachine.horometro_actual || 0 }} hrs</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-white/60 text-[9px] font-black uppercase tracking-wider">Estado</span>
                  <span class="font-black text-white/90 text-[10px]">{{ formMachine.estado }}</span>
                </div>
                <div v-if="formMachine.no_factura" class="flex justify-between">
                  <span class="text-white/60 text-[9px] font-black uppercase tracking-wider">No. Factura</span>
                  <span class="font-black text-white/90 text-[10px]">{{ formMachine.no_factura }}</span>
                </div>
                <div v-if="formMachine.costo_adquisicion" class="flex justify-between">
                  <span class="text-white/60 text-[9px] font-black uppercase tracking-wider">Costo</span>
                  <span class="font-black text-white/90 text-[10px]">Q {{ Number(formMachine.costo_adquisicion).toLocaleString('es-GT', { minimumFractionDigits: 2 }) }}</span>
                </div>
              </div>
            </div>
            <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
          </div>

          <!-- Action Buttons -->
          <div class="flex gap-3">
            <button 
              type="button" 
              @click="submitMachine" 
              :disabled="isSubmitting"
              class="flex-1 bg-primary hover:opacity-90 disabled:opacity-50 text-white py-4 rounded-xl text-xs font-black uppercase tracking-widest shadow-2xl transition-all"
            >
              <span v-if="isSubmitting">Guardando...</span>
              <span v-else>{{ isEditingMachine ? 'Guardar Cambios' : 'Registrar Maquinaria' }}</span>
            </button>
            <button 
              type="button" 
              @click="switchTab('machinery'); resetMachineForm()"
              class="px-5 py-4 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-black text-white/50 transition-all"
            >
              Cancelar
            </button>
          </div>
        </div>
      </div>
    </template>

    <!-- ============================================================
         MODAL: REGISTRO BITÁCORA DIARIA
         ============================================================ -->
    <div v-if="showLogModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeLogModal"></div>
      
      <div class="glass-card w-full max-w-2xl overflow-y-auto rounded-[32px] p-6 md:p-8 relative z-10 border border-white/10 shadow-2xl">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-2xl font-bold text-white">Registrar Bitácora Diaria</h3>
          <button @click="closeLogModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all">
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <form @submit.prevent="submitLog" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Máquina <span class="text-rose-400">*</span></label>
              <select v-model="formLog.maquina_id" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none">
                <option value="" disabled>Seleccionar máquina...</option>
                <option v-for="m in machinery" :key="m.id" :value="m.id">{{ m.codigo_interno }} - {{ m.marca }} {{ m.modelo }}</option>
              </select>
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Fecha <span class="text-rose-400">*</span></label>
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
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Horómetro Inicial <span class="text-rose-400">*</span></label>
              <input v-model="formLog.horometro_inicial" type="number" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Horómetro Final <span class="text-rose-400">*</span></label>
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
        <div class="relative w-full max-w-4xl glass-card rounded-[40px] overflow-hidden border border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.5)] flex flex-col lg:flex-row max-h-[90vh]">
          
          <button @click="selectedMachine = null" class="absolute top-6 right-6 z-10 w-10 h-10 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center border border-white/10 text-white/40 hover:text-white transition-all">
            <XMarkIcon class="w-5 h-5" />
          </button>

          <!-- Left: Media & Gallery -->
          <div class="lg:w-1/2 relative bg-black/40 min-h-[300px] flex flex-col justify-between">
            <div class="relative w-full h-[280px] bg-black/60 overflow-hidden">
              <img v-if="activeModalPhoto" :src="getPhotoUrl(activeModalPhoto)" class="w-full h-full object-cover" :alt="selectedMachine.modelo" />
              <div v-else class="w-full h-full flex items-center justify-center text-white/20"><WrenchScrewdriverIcon class="w-24 h-24" /></div>
              <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent"></div>
              
              <div class="absolute bottom-4 left-6 right-6">
                <span class="text-[10px] font-black uppercase tracking-[0.4em] text-primary mb-1 block">{{ selectedMachine.codigo_interno }}</span>
                <h2 class="text-3xl font-black text-white italic uppercase tracking-tighter leading-none">{{ selectedMachine.marca }}</h2>
                <h3 class="text-xl font-bold text-white/80 uppercase mt-1">{{ selectedMachine.modelo }}</h3>
                <p class="text-white/40 font-bold uppercase tracking-widest text-xs mt-1">{{ selectedMachine.categoria }}</p>
              </div>
            </div>

            <!-- Gallery Strip if multiple photos -->
            <div v-if="selectedMachinePhotos.length > 1" class="p-3 bg-slate-950/80 border-t border-white/5 flex gap-2 overflow-x-auto">
              <button 
                v-for="(photo, pIdx) in selectedMachinePhotos" 
                :key="pIdx"
                type="button"
                @click="selectedModalPhotoIndex = pIdx"
                :class="['relative h-14 w-14 rounded-xl overflow-hidden border-2 transition-all shrink-0', selectedModalPhotoIndex === pIdx ? 'border-primary scale-105 shadow-lg shadow-primary/30' : 'border-white/10 opacity-60 hover:opacity-100']"
              >
                <img :src="getPhotoUrl(photo)" class="w-full h-full object-cover" />
              </button>
            </div>
          </div>

          <!-- Right: Info -->
          <div class="lg:w-1/2 p-8 lg:p-10 bg-black/20 overflow-y-auto space-y-6">
            <!-- Status & Usage -->
            <div class="flex gap-4">
              <div class="flex-1 glass-card p-5 rounded-2xl border border-white/5">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/20 mb-2 flex items-center gap-2">
                  <ChartBarIcon class="w-3.5 h-3.5" /> Estado
                </p>
                <div class="flex items-center gap-2.5">
                  <div :class="`w-2.5 h-2.5 rounded-full ${getStatusColor(selectedMachine.estado)}`"></div>
                  <span class="text-base font-black italic uppercase text-white">{{ selectedMachine.estado }}</span>
                </div>
              </div>
              <div class="flex-1 glass-card p-5 rounded-2xl border border-white/5">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/20 mb-2 flex items-center gap-2">
                  <ClockIcon class="w-3.5 h-3.5" /> Horómetro
                </p>
                <span class="text-lg font-black italic uppercase text-white">{{ selectedMachine.horometro_actual }} hrs</span>
              </div>
            </div>

            <!-- Technical Specs -->
            <div>
              <h5 class="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-3 flex items-center gap-2">
                <InformationCircleIcon class="w-3.5 h-3.5" /> Detalles Técnicos
              </h5>
              <div class="grid grid-cols-2 gap-3 bg-black/20 p-4 rounded-2xl border border-white/5">
                <div><p class="text-[9px] text-white/30 uppercase tracking-widest">Tipo</p><p class="text-xs font-bold text-white"><span :class="selectedMachine.clasificacion_tipo === 'Liviana' ? 'text-amber-300' : 'text-primary'">{{ selectedMachine.clasificacion_tipo || 'Pesada' }}</span></p></div>
                <div v-if="selectedMachine.clasificacion_tipo === 'Liviana'"><p class="text-[9px] text-amber-400 uppercase tracking-widest">Fecha de Servicio</p><p class="text-xs font-bold text-amber-300">{{ formatDate(selectedMachine.fecha_servicio) || 'N/A' }}</p></div>
                <div><p class="text-[9px] text-white/30 uppercase tracking-widest">No. Factura</p><p class="text-xs font-bold text-white">{{ selectedMachine.no_factura || 'N/A' }}</p></div>
                <div><p class="text-[9px] text-white/30 uppercase tracking-widest">Año</p><p class="text-xs font-bold text-white">{{ selectedMachine.anio_fabricacion || 'N/A' }}</p></div>
                <div><p class="text-[9px] text-white/30 uppercase tracking-widest">Serie</p><p class="text-xs font-bold text-white truncate">{{ selectedMachine.numero_serie || 'N/A' }}</p></div>
                <div><p class="text-[9px] text-white/30 uppercase tracking-widest">Placa</p><p class="text-xs font-bold text-white">{{ selectedMachine.placa || 'N/A' }}</p></div>
              </div>
            </div>

            <!-- Datos de Seguro (Para Pesada) -->
            <div v-if="selectedMachine.clasificacion_tipo !== 'Liviana'" class="border-t border-white/5 pt-4">
              <h5 class="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-3 flex items-center gap-2">
                <ShieldCheckIcon class="w-3.5 h-3.5 text-primary" /> Datos del Seguro
              </h5>
              <div class="grid grid-cols-2 gap-3 bg-black/20 p-4 rounded-2xl border border-white/5">
                <div><p class="text-[9px] text-white/30 uppercase tracking-widest">Aseguradora</p><p class="text-xs font-bold text-white">{{ selectedMachine.seguro_aseguradora || 'N/A' }}</p></div>
                <div><p class="text-[9px] text-white/30 uppercase tracking-widest">Contacto</p><p class="text-xs font-bold text-white">{{ selectedMachine.seguro_contacto_nombre || 'N/A' }}</p></div>
                <div><p class="text-[9px] text-white/30 uppercase tracking-widest">Teléfono</p><p class="text-xs font-bold text-white">{{ selectedMachine.seguro_contacto_telefono || 'N/A' }}</p></div>
                <div>
                  <p class="text-[9px] text-white/30 uppercase tracking-widest">Contrato</p>
                  <a v-if="selectedMachine.seguro_contrato_adjunto_path" :href="getFileUrl(selectedMachine.seguro_contrato_adjunto_path)" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline mt-0.5">
                    <DocumentTextIcon class="w-3.5 h-3.5" /> Ver Póliza
                  </a>
                  <span v-else class="text-xs font-bold text-white/40">No adjunto</span>
                </div>
              </div>
            </div>

            <!-- Mantenimiento & Adquisición -->
            <div class="border-t border-white/5 pt-4">
              <h5 class="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-3 flex items-center gap-2">
                <UserIcon class="w-3.5 h-3.5" /> Asignaciones y Compra
              </h5>
              <div class="grid grid-cols-2 gap-3 bg-black/20 p-4 rounded-2xl border border-white/5">
                <div><p class="text-[9px] text-white/30 uppercase tracking-widest">Operador</p><p class="text-xs font-bold text-white truncate">{{ selectedMachine.operador_nombre || 'Sin asignar' }}</p></div>
                <div><p class="text-[9px] text-white/30 uppercase tracking-widest">Proyecto</p><p class="text-xs font-bold text-primary truncate">{{ selectedMachine.proyecto_nombre || 'Sin asignar' }}</p></div>
                <div><p class="text-[9px] text-white/30 uppercase tracking-widest">Adquisición</p><p class="text-xs font-bold text-white">{{ formatDate(selectedMachine.fecha_adquisicion) || 'N/A' }}</p></div>
                <div><p class="text-[9px] text-white/30 uppercase tracking-widest">Costo</p><p class="text-xs font-bold text-emerald-400">{{ selectedMachine.costo_adquisicion ? 'Q ' + Number(selectedMachine.costo_adquisicion).toLocaleString('es-GT', {minimumFractionDigits: 2}) : 'N/A' }}</p></div>
              </div>
            </div>

            <!-- Historial de Mantenimientos -->
            <div v-if="selectedMachineMaintenanceLogs.length > 0" class="border-t border-white/5 pt-4">
              <h5 class="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-3 flex items-center gap-2">
                <WrenchScrewdriverIcon class="w-3.5 h-3.5" /> Bitácoras de Mantenimiento
              </h5>
              <div class="space-y-3">
                <div v-for="item in selectedMachineMaintenanceLogs" :key="item.id" class="bg-black/20 p-4 rounded-2xl border border-white/5 text-xs">
                  <div class="flex justify-between items-start mb-1.5">
                    <span :class="[
                      'text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-md border',
                      item.tipo_mantenimiento === 'Preventivo' ? 'bg-primary/10 border-primary/20 text-primary' : 'bg-amber-500/10 border-amber-500/30 text-amber-400'
                    ]">{{ item.tipo_mantenimiento }}</span>
                    <span class="text-xs font-black text-emerald-400">Q{{ Number(item.costo_total).toLocaleString('es-GT', {minimumFractionDigits:2}) }}</span>
                  </div>
                  <p class="text-white/90 font-bold mb-1">{{ item.descripcion }}</p>
                  <p class="text-[10px] text-white/40">{{ item.fecha_mantenimiento }}</p>
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
        <div class="relative w-full max-w-2xl glass-card rounded-[40px] overflow-hidden border border-white/10 shadow-2xl p-8">
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
                <p class="text-xl font-black text-primary">{{ (Number(selectedLog.horometro_final) - Number(selectedLog.horometro_inicial)).toFixed(1) }} h</p>
              </div>
            </div>
            
            <div class="grid grid-cols-2 gap-6">
              <div>
                <p class="text-[10px] text-white/30 uppercase font-bold tracking-widest">Combustible (Gal/Lts)</p>
                <p class="text-base font-bold text-white">{{ selectedLog.combustible_consumido || 'N/A' }}</p>
              </div>
              <div v-if="authStore.userRole === 'admin'">
                <p class="text-[10px] text-white/30 uppercase font-bold tracking-widest">Creador</p>
                <p class="text-base font-bold text-white">{{ selectedLog.creado_por_nombre || 'Sistema' }}</p>
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

    <!-- Fullscreen Image Viewer -->
    <div v-if="fullscreenImage" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/95 p-4 backdrop-blur-sm" @click="fullscreenImage = ''">
      <button class="absolute top-6 right-6 p-2 bg-white/10 hover:bg-rose-500 rounded-full text-white transition-all z-50">
        <XMarkIcon class="w-6 h-6" />
      </button>
      <img :src="fullscreenImage" class="max-w-full max-h-[90vh] object-contain rounded-2xl shadow-2xl" @click.stop />
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { 
  WrenchScrewdriverIcon, ExclamationTriangleIcon, ClockIcon, ListBulletIcon, 
  XMarkIcon, UserIcon, ChartBarIcon, PlusIcon, PencilIcon, TrashIcon, EyeIcon, 
  MagnifyingGlassIcon, ChevronLeftIcon, ChevronRightIcon, ShieldCheckIcon, 
  DocumentTextIcon, CalendarIcon, CameraIcon, InformationCircleIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';
import { useAuthStore } from '../../stores/auth';
import api from '../../services/api';

const authStore = useAuthStore();
const BASE_URL = '/concretos-oriente/Backend/api/v1';

// State
const activeTab = ref("machinery"); // 'machinery' | 'log' | 'register'
const machinery = ref([]);
const logs = ref([]);
const maintenanceLogs = ref([]);
const personnel = ref([]);
const projects = ref([]);
const loading = ref(true);
const loadingLogs = ref(true);

const fullscreenImage = ref('');

const searchMachine = ref("");
const filterType = ref("");
const filterStatus = ref("");

const searchLog = ref("");
const filterLogProject = ref("");

const photoPreview = ref('');

const filteredMachinery = computed(() => {
  return machinery.value.filter(m => {
    const searchVal = searchMachine.value.toLowerCase();
    const matchSearch = (m.codigo_interno && m.codigo_interno.toLowerCase().includes(searchVal)) ||
                        (m.marca && m.marca.toLowerCase().includes(searchVal)) ||
                        (m.modelo && m.modelo.toLowerCase().includes(searchVal)) ||
                        (m.numero_serie && m.numero_serie.toLowerCase().includes(searchVal)) ||
                        (m.no_factura && m.no_factura.toLowerCase().includes(searchVal)) ||
                        (m.placa && m.placa.toLowerCase().includes(searchVal));
    const matchType = filterType.value === "" || (m.clasificacion_tipo || 'Pesada') === filterType.value;
    const isAct = (s) => s === 'Activo' || s === 'En Funcionamiento' || s === 'Nuevo';
    const matchStatus = filterStatus.value === "" ||
      (filterStatus.value === 'Activo' ? isAct(m.estado) : !isAct(m.estado));
    return matchSearch && matchType && matchStatus;
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

const selectedPhotoFiles = ref([]);
const photoPreviews = ref([]);
const existingPhotos = ref([]);
const selectedModalPhotoIndex = ref(0);

const selectedMachine = ref(null);
const selectedLog = ref(null);

const selectedMachinePhotos = computed(() => {
  if (!selectedMachine.value) return [];
  const m = selectedMachine.value;
  if (m.fotos_json) {
    try {
      const parsed = JSON.parse(m.fotos_json);
      if (Array.isArray(parsed) && parsed.length > 0) return parsed;
    } catch (e) {}
  }
  return m.foto_path ? [m.foto_path] : [];
});

const activeModalPhoto = computed(() => {
  const photos = selectedMachinePhotos.value;
  if (photos.length === 0) return null;
  return photos[selectedModalPhotoIndex.value] || photos[0];
});

watch(selectedMachine, () => {
  selectedModalPhotoIndex.value = 0;
});

const selectedMachineMaintenanceLogs = computed(() => {
  if (!selectedMachine.value) return [];
  return maintenanceLogs.value.filter(l => l.machinery_id === selectedMachine.value.id);
});

const openViewLog = (log) => {
  selectedLog.value = log;
};

// Form State
const isEditingMachine = ref(false);
const editingMachineId = ref(null);
const isSubmitting = ref(false);
const showLogModal = ref(false);

const formMachine = ref({
  clasificacion_tipo: 'Pesada',
  categoria: '',
  codigo_interno: '',
  no_factura: '',
  marca: '',
  modelo: '',
  numero_serie: '',
  anio_fabricacion: '',
  placa: '',
  horometro_actual: 0,
  operador_id: null,
  proyecto_id: null,
  estado: 'Activo',
  costo_adquisicion: '',
  fecha_adquisicion: '',
  fecha_servicio: '',
  seguro_aseguradora: '',
  seguro_contacto_nombre: '',
  seguro_contacto_telefono: '',
  foto: null,
  foto_path: '',
  seguro_contrato_adjunto: null
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

// KPIs Metrics
const metrics = computed(() => {
  const isAct = (s) => s === 'Activo' || s === 'En Funcionamiento' || s === 'Nuevo';
  const activas = machinery.value.filter(m => isAct(m.estado)).length;
  const inactivas = machinery.value.filter(m => !isAct(m.estado)).length;
  const total = machinery.value.length;
  const pct = total > 0 ? Math.round((activas / total) * 100) : 0;

  return [
    { label: "Total Maquinaria", value: total.toString(), trend: "Total equipos", icon: WrenchScrewdriverIcon, color: "text-primary" },
    { label: "Operativas / Activas", value: `${activas} / ${total}`, percentage: pct, color: "text-primary" },
    { label: "Inactivas", value: inactivas.toString(), trend: "Fuera de servicio", icon: ExclamationTriangleIcon, color: "text-rose-400" },
    { label: "Bitácoras Registradas", value: logs.value.length.toString(), trend: "Total histórico", icon: ListBulletIcon, color: "text-sky-400" },
  ];
});

// Navigation Tab Switcher
const switchTab = (tab) => {
  activeTab.value = tab;
  if (tab !== 'register') {
    isEditingMachine.value = false;
    editingMachineId.value = null;
    photoPreview.value = '';
  }
};

const startRegister = () => {
  resetMachineForm();
  isEditingMachine.value = false;
  editingMachineId.value = null;
  activeTab.value = 'register';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

// ----------------------------------------------------------------
// Lifecycle & Fetches
// ----------------------------------------------------------------
onMounted(() => {
  fetchMachinery();
  fetchLogs();
  fetchPersonnel();
  fetchProjects();
  fetchMaintenanceLogs();
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
    const res = await fetch(`${BASE_URL}/payrolls/active-personnel`);
    const data = await res.json();
    if (data.status === 'success') personnel.value = data.data;
  } catch (err) { console.error(err); }
};

const fetchMaintenanceLogs = async () => {
  try {
    const res = await api.get('/maintenance/logs');
    if (res.data.status === 'success') maintenanceLogs.value = res.data.data;
  } catch (err) { console.error(err); }
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
const openEditMachine = (m) => {
  formMachine.value = { 
    ...m, 
    clasificacion_tipo: m.clasificacion_tipo || 'Pesada',
    no_factura: m.no_factura || '',
    fecha_servicio: m.fecha_servicio || '',
    seguro_aseguradora: m.seguro_aseguradora || '',
    seguro_contacto_nombre: m.seguro_contacto_nombre || '',
    seguro_contacto_telefono: m.seguro_contacto_telefono || '',
    foto: null,
    foto_path: m.foto_path || '',
    seguro_contrato_adjunto: null 
  };
  
  existingPhotos.value = [];
  if (m.fotos_json) {
    try {
      const parsed = JSON.parse(m.fotos_json);
      if (Array.isArray(parsed)) existingPhotos.value = parsed;
    } catch (e) {}
  }
  if (existingPhotos.value.length === 0 && m.foto_path) {
    existingPhotos.value = [m.foto_path];
  }

  selectedPhotoFiles.value = [];
  photoPreviews.value = [];

  isEditingMachine.value = true;
  editingMachineId.value = m.id;
  activeTab.value = 'register';
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const resetMachineForm = () => {
  formMachine.value = {
    clasificacion_tipo: 'Pesada',
    categoria: '', 
    codigo_interno: '', 
    no_factura: '', 
    marca: '', 
    modelo: '', 
    numero_serie: '',
    anio_fabricacion: '', 
    placa: '', 
    horometro_actual: 0,
    operador_id: null,
    proyecto_id: null, 
    estado: 'Activo', 
    costo_adquisicion: '', 
    fecha_adquisicion: '',
    fecha_servicio: '',
    seguro_aseguradora: '',
    seguro_contacto_nombre: '',
    seguro_contacto_telefono: '',
    foto: null,
    foto_path: '',
    seguro_contrato_adjunto: null
  };
  selectedPhotoFiles.value = [];
  photoPreviews.value = [];
  existingPhotos.value = [];
};

const handleMultipleFilesChange = (e) => {
  const files = Array.from(e.target.files || []);
  if (files.length === 0) return;
  files.forEach(file => {
    selectedPhotoFiles.value.push(file);
    photoPreviews.value.push(URL.createObjectURL(file));
  });
};

const removeSelectedPhoto = (idx) => {
  selectedPhotoFiles.value.splice(idx, 1);
  if (photoPreviews.value[idx]) {
    URL.revokeObjectURL(photoPreviews.value[idx]);
    photoPreviews.value.splice(idx, 1);
  }
};

const handleInsuranceDocChange = (e) => {
  const file = e.target.files[0];
  if (file) formMachine.value.seguro_contrato_adjunto = file;
};

const getFileUrl = (path) => {
  if (!path) return '';
  return `/concretos-oriente/Backend/${path}?t=${Date.now()}`;
};

const submitMachine = async () => {
  // Simple validation
  if (!formMachine.value.codigo_interno || !formMachine.value.categoria || !formMachine.value.marca || !formMachine.value.modelo) {
    Swal.fire({
      background: '#0f172a',
      color: '#fff',
      icon: 'warning',
      title: 'Campos requeridos',
      text: 'Por favor completa el código interno, categoría, marca y modelo.'
    });
    return;
  }

  if (formMachine.value.clasificacion_tipo === 'Liviana' && !formMachine.value.fecha_servicio) {
    Swal.fire({
      background: '#0f172a',
      color: '#fff',
      icon: 'warning',
      title: 'Fecha de Servicio requerida',
      text: 'Para maquinaria liviana debes indicar la fecha de servicio.'
    });
    return;
  }

  isSubmitting.value = true;
  const fd = new FormData();
  
  Object.keys(formMachine.value).forEach(key => {
    if (key !== 'foto_path' && formMachine.value[key] !== null && formMachine.value[key] !== '') {
      fd.append(key, formMachine.value[key]);
    }
  });

  // Multiple photos append
  selectedPhotoFiles.value.forEach((file, index) => {
    fd.append(`foto_${index}`, file);
    fd.append('fotos[]', file);
  });
  if (selectedPhotoFiles.value.length === 1) {
    fd.append('foto', selectedPhotoFiles.value[0]);
  }

  try {
    const url = isEditingMachine.value ? `${BASE_URL}/machinery/${editingMachineId.value}` : `${BASE_URL}/machinery`;
    const res = await fetch(url, { method: 'POST', body: fd });
    const result = await res.json();
    
    if (result.status === 'success') {
      await fetchMachinery();
      switchTab('machinery');
      resetMachineForm();
      Swal.fire({ background: '#0f172a', color: '#fff', icon: 'success', title: '¡Guardado correctamente!' });
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
      await fetchLogs();
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
      await fetchMachinery();
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
  const isAct = status === 'Activo' || status === 'En Funcionamiento' || status === 'Nuevo';
  return isAct ? 'bg-emerald-500 text-emerald-400' : 'bg-rose-500 text-rose-400';
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
.glass-input {
  background-color: rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.1);
  outline: none;
}
.glass-input:focus {
  border-color: rgba(99, 102, 241, 0.5);
}
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
