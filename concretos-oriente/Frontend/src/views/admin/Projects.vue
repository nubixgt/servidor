<template>
  <div class="pt-20 pb-10 px-4 md:px-10 md:pb-20 max-w-7xl mx-auto space-y-12 min-h-screen text-white relative">
    <header class="flex flex-col md:flex-row md:items-end justify-between gap-10 bg-white/5 p-10 rounded-[48px] border border-white/10 backdrop-blur-xl">
      <div class="space-y-3">
        <h1 class="text-5xl font-black tracking-tighter uppercase italic">Portafolio de Proyectos</h1>
        <p class="text-white/60 text-lg font-medium leading-relaxed max-w-xl">Supervisión integral de ciclos de vida de construcción, convenios, especificaciones técnicas y financiamiento.</p>
      </div>

      <div class="flex p-2 bg-black/20 rounded-[28px] shadow-inner border border-white/10 backdrop-blur-xl">
        <button
          @click="view = 'projects'"
          :class="`px-10 py-3.5 rounded-2xl text-xs font-black uppercase tracking-widest transition-all ${
            view === 'projects' ? 'bg-primary text-white shadow-[0_0_20px_rgba(99,102,241,0.4)]' : 'text-white/40 hover:text-white'
          }`"
        >
          Proyectos
        </button>
      </div>
    </header>

    <transition name="fade-slide" mode="out-in">
      <section v-if="view === 'projects'" key="projects" class="space-y-6">

        <!-- Barra de búsqueda y filtros -->
        <div class="bg-white/5 rounded-[32px] border border-white/10 backdrop-blur-xl p-6 space-y-4">

          <!-- Fila principal -->
          <div class="flex flex-col md:flex-row gap-4">
            <!-- Buscador -->
            <div class="relative flex-1">
              <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-white/30 pointer-events-none" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Buscar por código, nombre, SNIP, NOG, contrato o ubicación..."
                class="w-full bg-black/40 border border-white/10 rounded-2xl pl-12 pr-5 py-3.5 text-white placeholder-white/25 focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold text-sm"
              />
              <button v-if="searchQuery" @click="searchQuery = ''" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/30 hover:text-white transition-all">
                <XMarkIcon class="w-4 h-4" />
              </button>
            </div>

            <!-- Filtros rápidos -->
            <div class="flex gap-3 flex-wrap md:flex-nowrap">
              <!-- Estado -->
              <select v-model="filterEstado" class="bg-black/40 border border-white/10 rounded-2xl px-4 py-3.5 text-sm font-bold text-white focus:border-primary transition-all appearance-none cursor-pointer w-full md:w-auto md:min-w-[140px]">
                <option value="">Todos los estados</option>
                <option value="Borrador">Borrador</option>
                <option value="Activo">Activo</option>
                <option value="Pausado">Pausado</option>
                <option value="Completado">Completado</option>
                <option value="Cancelado">Cancelado</option>
              </select>

              <!-- Tipo de Inversión -->
              <select v-model="filterTipoInversion" class="bg-black/40 border border-white/10 rounded-2xl px-4 py-3.5 text-sm font-bold text-white focus:border-primary transition-all appearance-none cursor-pointer w-full md:w-auto md:min-w-[160px]">
                <option value="">Cualquier tipo de inversión</option>
                <option value="Por Administración">Por Administración</option>
                <option value="Privado">Privado</option>
                <option value="Ordinario">Ordinario</option>
                <option value="Extraordinario">Extraordinario</option>
              </select>

              <!-- Cliente -->
              <select v-model="filterCliente" class="bg-black/40 border border-white/10 rounded-2xl px-4 py-3.5 text-sm font-bold text-white focus:border-primary transition-all appearance-none cursor-pointer w-full md:w-auto md:min-w-[160px]">
                <option value="">Todos los clientes</option>
                <option v-for="c in CLIENTES" :key="c.id" :value="c.id">{{ c.company_name }}</option>
              </select>

              <!-- Ordenar -->
              <select v-model="sortBy" class="bg-black/40 border border-white/10 rounded-2xl px-4 py-3.5 text-sm font-bold text-white focus:border-primary transition-all appearance-none cursor-pointer w-full md:w-auto md:min-w-[160px]">
                <option value="fecha_inicio_desc">Fecha inicio ↓</option>
                <option value="fecha_inicio_asc">Fecha inicio ↑</option>
                <option value="presupuesto_desc">Presupuesto ↓</option>
                <option value="presupuesto_asc">Presupuesto ↑</option>
                <option value="nombre_asc">Nombre A→Z</option>
                <option value="nombre_desc">Nombre Z→A</option>
              </select>

              <!-- Botón filtros avanzados -->
              <button
                @click="showAdvancedFilters = !showAdvancedFilters"
                :class="`flex items-center gap-2 px-5 py-3.5 rounded-2xl text-sm font-black uppercase tracking-widest transition-all border ${showAdvancedFilters || activeFiltersCount > 0 ? 'bg-primary/20 border-primary text-primary' : 'bg-black/40 border-white/10 text-white/60 hover:text-white hover:border-white/30'}`"
              >
                <AdjustmentsHorizontalIcon class="w-4 h-4" />
                <span>Avanzado</span>
                <span v-if="activeFiltersCount > 0" class="bg-primary text-white text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center">{{ activeFiltersCount }}</span>
              </button>

              <!-- Reset -->
              <button
                v-if="searchQuery || activeFiltersCount > 0 || filterTipoInversion"
                @click="resetFilters"
                class="flex items-center gap-2 px-5 py-3.5 rounded-2xl text-sm font-black text-tertiary/80 hover:text-tertiary bg-tertiary/10 hover:bg-tertiary/20 border border-tertiary/20 transition-all uppercase tracking-widest"
              >
                <XMarkIcon class="w-4 h-4" />
                Limpiar
              </button>
            </div>
          </div>

          <!-- Panel de filtros avanzados -->
          <transition name="fade-slide">
            <div v-if="showAdvancedFilters" class="pt-4 border-t border-white/10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <!-- Gerente -->
              <div>
                <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 block flex items-center gap-1.5">
                  <ArrowsUpDownIcon class="w-3.5 h-3.5" /> Gerente
                </label>
                <select v-model="filterGerente" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm font-bold text-white focus:border-primary transition-all appearance-none">
                  <option value="">Todos los gerentes</option>
                  <option v-for="u in users" :key="u.id" :value="u.id">{{ u.nombre }}</option>
                </select>
              </div>
              <!-- Presupuesto mín -->
              <div>
                <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 block">Presupuesto mínimo (GTQ)</label>
                <input v-model="filterPresupuestoMin" type="number" min="0" placeholder="0" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm font-bold text-white placeholder-white/20 focus:border-primary transition-all" />
              </div>
              <!-- Presupuesto máx -->
              <div>
                <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 block">Presupuesto máximo (GTQ)</label>
                <input v-model="filterPresupuestoMax" type="number" min="0" placeholder="Sin límite" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm font-bold text-white placeholder-white/20 focus:border-primary transition-all" />
              </div>
              <!-- Rango fecha inicio -->
              <div>
                <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 block">Inicio del proyecto</label>
                <div class="flex gap-2">
                  <input v-model="filterFechaDesde" type="date" title="Desde" class="w-full bg-black/40 border border-white/10 rounded-xl px-3 py-3 text-sm font-bold text-white focus:border-primary transition-all" />
                  <input v-model="filterFechaHasta" type="date" title="Hasta" class="w-full bg-black/40 border border-white/10 rounded-xl px-3 py-3 text-sm font-bold text-white focus:border-primary transition-all" />
                </div>
              </div>
            </div>
          </transition>

          <!-- Contador de resultados -->
          <div class="flex items-center justify-between pt-1">
            <p class="text-xs text-white/30 font-bold uppercase tracking-widest">
              <span class="text-white/60">{{ filteredProjects.length }}</span> de {{ projects.length }} proyectos
            </p>
            <div v-if="filterEstado" class="flex items-center gap-2">
              <span :class="`w-2 h-2 rounded-full ${getStatusColor(filterEstado)}`"></span>
              <span class="text-xs font-black text-white/50 uppercase tracking-widest">{{ filterEstado }}</span>
            </div>
          </div>
        </div>

        <div v-if="loading" class="text-center py-20">
          <p class="text-white/50 text-xl font-bold uppercase tracking-widest animate-pulse">Cargando Proyectos...</p>
        </div>

        <div v-else-if="projects.length === 0" class="text-center py-20 bg-white/5 rounded-[48px] border border-white/10">
          <p class="text-white/50 text-xl font-bold uppercase tracking-widest">No hay proyectos registrados</p>
        </div>

        <div v-else-if="filteredProjects.length === 0" class="text-center py-20 bg-white/5 rounded-[48px] border border-white/10">
          <FunnelIcon class="w-12 h-12 text-white/20 mx-auto mb-4" />
          <p class="text-white/50 text-lg font-black uppercase tracking-widest mb-2">Sin resultados</p>
          <p class="text-white/30 text-sm font-medium">Ningún proyecto coincide con los filtros aplicados.</p>
          <button @click="resetFilters" class="mt-6 px-8 py-3 bg-primary/20 hover:bg-primary/30 text-primary font-black text-sm uppercase tracking-widest rounded-2xl transition-all border border-primary/30">
            Limpiar filtros
          </button>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
          <div
            v-for="proj in filteredProjects"
            :key="proj.id"
            @click="openProjectDetails(proj)"
            class="glass-card rounded-[48px] overflow-hidden group cursor-pointer border border-white/10 shadow-[0_24px_48px_-12px_rgba(0,0,0,0.5)] flex flex-col h-full hover:-translate-y-2 transition-transform duration-500 relative" data-aos="zoom-in-up" data-aos-duration="1000"
          >
            <div class="h-64 relative overflow-hidden shrink-0">
              <img :src="getPhotoUrl(proj)" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-1000" :alt="proj.nombre" />
              <div class="absolute inset-0 bg-gradient-to-t from-black/95 via-black/50 to-transparent"></div>

              <!-- Top badges -->
              <div class="absolute top-6 left-6 right-6 flex items-center justify-between gap-2">
                <span v-if="proj.tipo_inversion" class="px-3 py-1.5 backdrop-blur-2xl bg-black/60 rounded-xl text-[9px] font-black uppercase tracking-widest border border-white/20 text-cyan-400">
                  {{ proj.tipo_inversion }}
                </span>
                <span v-else></span>

                <div class="px-3 py-1.5 backdrop-blur-2xl bg-white/10 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] border border-white/20 shadow-xl">
                  <div class="flex items-center gap-2">
                    <span :class="`w-2 h-2 rounded-full ${getStatusColor(proj.estado)} shadow-[0_0_10px_currentColor]`"></span>
                    {{ proj.estado }}
                  </div>
                </div>
              </div>
            </div>

            <div class="p-8 flex flex-col flex-1 justify-between bg-black/40">
              <div>
                <div class="flex items-center justify-between gap-2 mb-2">
                  <p class="text-xs font-bold text-primary flex items-center gap-1.5 uppercase tracking-widest">
                    <BuildingOfficeIcon class="w-4 h-4" /> {{ proj.codigo }}
                  </p>
                  <div v-if="proj.snip || proj.nog" class="flex items-center gap-1.5 text-[10px] font-mono text-white/50">
                    <span v-if="proj.snip" class="bg-white/5 px-2 py-0.5 rounded border border-white/10">SNIP: {{ proj.snip }}</span>
                    <span v-if="proj.nog" class="bg-white/5 px-2 py-0.5 rounded border border-white/10">NOG: {{ proj.nog }}</span>
                  </div>
                </div>
                <h3 class="text-2xl font-black text-white mb-2 leading-tight uppercase italic line-clamp-2">{{ proj.nombre }}</h3>

                <div v-if="proj.ubicacion || proj.coordenadas" class="flex items-center justify-between text-xs text-white/60 mb-4 gap-2">
                  <p class="flex items-center gap-1.5 truncate">
                    <MapPinIcon class="w-3.5 h-3.5 text-primary shrink-0" />
                    <span class="truncate">{{ proj.ubicacion || proj.coordenadas }}</span>
                  </p>
                  <button
                    v-if="getProjectMapsUrl(proj)"
                    @click.stop="copyMapsUrl(proj)"
                    type="button"
                    class="p-1.5 rounded-lg bg-white/5 hover:bg-primary/20 hover:text-primary text-white/40 transition-all border border-white/5 shrink-0 flex items-center gap-1"
                    title="Copiar URL de Google Maps"
                  >
                    <ClipboardDocumentIcon class="w-3.5 h-3.5" />
                    <span class="text-[10px] font-bold">Copiar URL</span>
                  </button>
                </div>
              </div>

              <div class="flex items-center justify-between pt-5 border-t border-white/10 mt-4">
                <div>
                  <p class="text-[10px] text-white/50 uppercase font-bold tracking-[0.2em] mb-1">Presupuesto</p>
                  <div class="flex items-center gap-2 font-black text-sm uppercase tracking-tighter italic text-white">
                    <CurrencyDollarIcon class="w-5 h-5 text-primary" />
                    <span>Q {{ formatCurrency(proj.presupuesto) }}</span>
                  </div>
                </div>
                <button @click.stop="openProjectDetails(proj)" class="w-12 h-12 rounded-2xl bg-white/10 hover:bg-primary transition-all flex items-center justify-center group-hover:shadow-[0_0_20px_rgba(99,102,241,0.4)]" title="Ver detalles del proyecto">
                  <ChevronRightIcon class="w-6 h-6 text-white group-hover:text-white" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>
    </transition>

    <!-- Modal Detalles del Proyecto -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="selectedProject" class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-6">
          <div
            @click="closeProjectDetails"
            class="absolute inset-0 bg-black/90 backdrop-blur-md cursor-pointer"
          ></div>

        <div class="relative w-full max-w-5xl glass-card rounded-[40px] overflow-hidden border border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.8)] z-10">
          <div class="absolute top-6 right-6 z-10 flex gap-3">
            <button
              @click="openHistoryModal(selectedProject)"
              class="w-12 h-12 rounded-2xl bg-white/10 hover:bg-emerald-500 flex items-center justify-center transition-all border border-white/10 text-white shadow-xl hover:shadow-emerald-500/40"
              title="Historial Financiero"
            >
              <ChartBarIcon class="w-5 h-5" />
            </button>
            <button
              @click="openEditModal(selectedProject)"
              class="w-12 h-12 rounded-2xl bg-white/10 hover:bg-primary flex items-center justify-center transition-all border border-white/10 text-white shadow-xl hover:shadow-primary/40"
              title="Editar"
            >
              <PencilIcon class="w-5 h-5" />
            </button>
            <button
              @click="deleteProject(selectedProject.id)"
              class="w-12 h-12 rounded-2xl bg-white/10 hover:bg-tertiary flex items-center justify-center transition-all border border-white/10 text-white shadow-xl hover:shadow-tertiary/40"
              title="Eliminar"
            >
              <TrashIcon class="w-5 h-5" />
            </button>
            <button
              @click="closeProjectDetails"
              class="w-12 h-12 rounded-2xl bg-white/10 hover:bg-white/20 flex items-center justify-center transition-all border border-white/10 text-white shadow-xl"
              title="Cerrar"
            >
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>

          <div class="flex flex-col lg:flex-row h-full max-h-[88vh] overflow-y-auto custom-scrollbar">
            <!-- Left: Media -->
            <div class="lg:w-2/5 relative bg-black/40 min-h-[320px] flex flex-col justify-end">
              <img :src="getPhotoUrl(selectedProject)" class="w-full h-full object-cover absolute inset-0" :alt="selectedProject.nombre" />
              <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent"></div>
              <div class="relative p-8 space-y-3 z-10">
                <div class="flex flex-wrap gap-2">
                  <span class="text-[10px] font-black uppercase tracking-[0.2em] text-primary bg-primary/20 px-3 py-1 rounded-full border border-primary/30">Cód: {{ selectedProject.codigo }}</span>
                  <span v-if="selectedProject.tipo_inversion" class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-400 bg-cyan-500/20 px-3 py-1 rounded-full border border-cyan-500/30">
                    {{ selectedProject.tipo_inversion }}
                  </span>
                </div>
                <h2 class="text-3xl md:text-4xl font-black text-white italic uppercase tracking-tighter leading-tight">{{ selectedProject.nombre }}</h2>
              </div>
            </div>

            <!-- Right: Info -->
            <div class="lg:w-3/5 p-8 md:p-10 bg-black/30 overflow-y-auto space-y-6">
              
              <!-- Identificadores SNIP, NOG y Contrato -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="glass-card p-4 rounded-2xl border border-white/5">
                  <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/40 mb-1">SNIP</p>
                  <p class="text-sm font-black text-cyan-400 font-mono">{{ selectedProject.snip || '—' }}</p>
                </div>
                <div class="glass-card p-4 rounded-2xl border border-white/5">
                  <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/40 mb-1">NOG</p>
                  <p class="text-sm font-black text-cyan-400 font-mono">{{ selectedProject.nog || '—' }}</p>
                </div>
                <div class="glass-card p-4 rounded-2xl border border-white/5">
                  <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/40 mb-1">Contrato No.</p>
                  <p class="text-sm font-black uppercase text-white truncate">{{ selectedProject.numero_contrato || '—' }}</p>
                </div>
              </div>

              <!-- Estado y Cliente -->
              <div class="flex gap-4">
                <div class="flex-1 glass-card p-4 rounded-2xl border border-white/5">
                  <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/40 mb-1 flex items-center gap-2">
                    <ChartBarIcon class="w-4 h-4" /> Estado
                  </p>
                  <div class="flex items-center gap-2.5">
                    <div :class="`w-2.5 h-2.5 rounded-full ${getStatusColor(selectedProject.estado)} shadow-[0_0_10px_currentColor]`"></div>
                    <span class="text-sm font-black italic uppercase text-white">{{ selectedProject.estado }}</span>
                  </div>
                </div>
                <div class="flex-1 glass-card p-4 rounded-2xl border border-white/5">
                  <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/40 mb-1 flex items-center gap-2">
                    <UserIcon class="w-4 h-4" /> Cliente
                  </p>
                  <p class="text-sm font-bold text-white truncate">{{ getClienteName(selectedProject.cliente_id) }}</p>
                </div>
              </div>

              <!-- Presupuesto y Desglose de Fondos -->
              <div class="bg-white/5 p-5 rounded-3xl border border-white/5 space-y-4">
                <div class="flex justify-between items-center pb-3 border-b border-white/10">
                  <div>
                    <p class="text-[10px] font-black text-white/40 uppercase tracking-widest">Presupuesto Contractual Total</p>
                    <p class="text-2xl font-black italic text-primary">Q {{ formatCurrency(selectedProject.presupuesto) }}</p>
                  </div>
                  <div v-if="selectedProject.tipo_inversion" class="text-right">
                    <p class="text-[10px] font-black text-white/40 uppercase tracking-widest">Tipo Inversión</p>
                    <p class="text-sm font-black text-white">{{ selectedProject.tipo_inversion }}</p>
                  </div>
                </div>

                <!-- Desglose de Fondos: COCODE, MUNI, COMUNIDAD -->
                <div>
                  <p class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2">Desglose de Aportes y Financiamiento</p>
                  <div class="grid grid-cols-3 gap-2">
                    <div class="bg-black/30 p-3 rounded-xl border border-white/5">
                      <p class="text-[9px] font-black text-white/40 uppercase tracking-widest">Monto COCODE</p>
                      <p class="text-xs md:text-sm font-bold text-emerald-400">Q {{ formatCurrency(selectedProject.monto_cocode) }}</p>
                    </div>
                    <div class="bg-black/30 p-3 rounded-xl border border-white/5">
                      <p class="text-[9px] font-black text-white/40 uppercase tracking-widest">Monto Muni</p>
                      <p class="text-xs md:text-sm font-bold text-blue-400">Q {{ formatCurrency(selectedProject.monto_muni) }}</p>
                    </div>
                    <div class="bg-black/30 p-3 rounded-xl border border-white/5">
                      <p class="text-[9px] font-black text-white/40 uppercase tracking-widest">Monto Comunidad</p>
                      <p class="text-xs md:text-sm font-bold text-amber-400">Q {{ formatCurrency(selectedProject.monto_comunidad) }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Historial Financiero (Ingresos y Egresos) -->
              <div class="bg-white/5 p-5 rounded-3xl border border-white/5 space-y-4">
                <div class="flex justify-between items-center pb-3 border-b border-white/10">
                  <p class="text-[10px] font-black text-white/40 uppercase tracking-widest flex items-center gap-2">
                    <ChartBarIcon class="w-4 h-4 text-emerald-400" /> Ingresos y Egresos del Proyecto
                  </p>
                  <button @click="openHistoryModal(selectedProject)" class="text-[10px] bg-primary/20 hover:bg-primary/40 text-primary px-3 py-1.5 rounded-lg font-black uppercase tracking-widest transition-colors border border-primary/30">
                    Ver Todo
                  </button>
                </div>
                
                <div v-if="loadingProjectFinances" class="flex justify-center py-4">
                  <div class="w-6 h-6 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                </div>
                <div v-else-if="!projectFinances.length" class="text-center py-4 text-[10px] text-white/30 uppercase tracking-widest font-bold">
                  No hay movimientos registrados
                </div>
                <div v-else class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar pr-2">
                  <div v-for="(item, idx) in projectFinances" :key="idx" class="bg-black/30 p-3 rounded-xl border border-white/5 flex justify-between items-center gap-3">
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2 mb-1">
                        <span :class="['px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-widest', item.type.includes('Ingreso') ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400']">
                          {{ item.type }}
                        </span>
                        <span class="text-[9px] text-white/40 font-mono">{{ item.date ? item.date.split(' ')[0] : 'S/F' }}</span>
                      </div>
                      <p class="text-xs text-white font-bold truncate" :title="item.detail">{{ item.detail }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                      <p :class="['text-xs font-black italic whitespace-nowrap', item.type.includes('Ingreso') ? 'text-emerald-400' : 'text-rose-400']">
                        {{ item.type.includes('Ingreso') ? '+' : '-' }} Q {{ formatCurrency(item.amount) }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Fechas -->
              <div class="grid grid-cols-3 gap-3 bg-white/5 p-4 rounded-2xl border border-white/5">
                <div>
                  <p class="text-[9px] font-black text-white/40 uppercase tracking-widest mb-1">Inicio</p>
                  <p class="text-xs font-bold text-white">{{ formatDate(selectedProject.fecha_inicio) }}</p>
                </div>
                <div>
                  <p class="text-[9px] font-black text-white/40 uppercase tracking-widest mb-1">Fin Estimado</p>
                  <p class="text-xs font-bold text-white">{{ formatDate(selectedProject.fecha_fin_estimada) || '—' }}</p>
                </div>
                <div>
                  <p class="text-[9px] font-black text-white/40 uppercase tracking-widest mb-1">Fin Real</p>
                  <p class="text-xs font-bold text-white">{{ formatDate(selectedProject.fecha_fin_real) || '—' }}</p>
                </div>
              </div>

              <!-- Ubicación con Acción de Copiar URL y Dirección -->
              <div class="bg-white/5 p-4 rounded-2xl border border-white/5 space-y-2">
                <div class="flex items-center justify-between flex-wrap gap-2">
                  <p class="text-[10px] font-black text-white/40 uppercase tracking-widest flex items-center gap-1.5">
                    <MapPinIcon class="w-4 h-4 text-primary" /> Ubicación del Proyecto
                  </p>
                  <!-- Botones de Copiar -->
                  <div class="flex items-center gap-2">
                    <button
                      v-if="getProjectMapsUrl(selectedProject)"
                      @click="copyMapsUrl(selectedProject)"
                      type="button"
                      class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider bg-primary hover:bg-primary/80 text-white transition-all shadow-lg shadow-primary/20 cursor-pointer"
                      title="Copiar URL / Enlace directo de Google Maps"
                    >
                      <ClipboardDocumentCheckIcon v-if="copiedMapsUrl" class="w-3.5 h-3.5 text-emerald-300" />
                      <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                      <span>{{ copiedMapsUrl ? '¡URL Copiada!' : 'Copiar URL Maps' }}</span>
                    </button>
                    <button
                      v-if="selectedProject.ubicacion || selectedProject.coordenadas"
                      @click="copyAddress(selectedProject.ubicacion || selectedProject.coordenadas)"
                      type="button"
                      class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider bg-white/10 hover:bg-white/20 text-white/70 hover:text-white transition-all border border-white/10 cursor-pointer"
                      title="Copiar texto de la dirección"
                    >
                      <ClipboardDocumentCheckIcon v-if="copiedLocation" class="w-3.5 h-3.5 text-emerald-400" />
                      <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                      <span>{{ copiedLocation ? '¡Copiado!' : 'Copiar Texto' }}</span>
                    </button>
                  </div>
                </div>
                <p class="text-sm font-bold text-white">{{ selectedProject.ubicacion || 'Sin dirección especificada' }}</p>
                <div class="flex items-center gap-4 text-xs font-medium pt-1">
                  <a v-if="getProjectMapsUrl(selectedProject)" :href="getProjectMapsUrl(selectedProject)" target="_blank" class="text-primary hover:underline flex items-center gap-1 font-bold">
                    Abrir en Google Maps ({{ selectedProject.coordenadas || selectedProject.ubicacion }}) ↗
                  </a>
                </div>
              </div>

              <!-- Gerente Responsable -->
              <div v-if="selectedProject.gerente_id" class="flex items-center gap-3 bg-white/5 p-4 rounded-2xl border border-white/5">
                <UserIcon class="w-5 h-5 text-primary" />
                <div>
                  <p class="text-[9px] font-black text-white/40 uppercase tracking-widest">Gerente Responsable</p>
                  <p class="text-sm font-bold text-white">{{ getManagerName(selectedProject.gerente_id) }}</p>
                </div>
              </div>

              <!-- Expediente Digital y Archivos Adjuntos -->
              <div class="space-y-3">
                <p class="text-[10px] font-black text-white/40 uppercase tracking-widest flex items-center gap-2">
                  <PaperClipIcon class="w-4 h-4 text-primary" /> Expediente Digital del Proyecto
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <!-- Foto de Contrato -->
                  <div class="bg-white/5 p-4 rounded-2xl border border-white/5 flex items-center justify-between">
                    <div class="flex items-center gap-3 truncate">
                      <DocumentTextIcon class="w-6 h-6 text-primary shrink-0" />
                      <div class="truncate">
                        <p class="text-[10px] font-black text-white/40 uppercase tracking-wider">Foto de Contrato</p>
                        <p class="text-xs font-bold text-white truncate">{{ selectedProject.foto_contrato ? 'Contrato Adjunto' : 'Sin adjuntar' }}</p>
                      </div>
                    </div>
                    <a v-if="selectedProject.foto_contrato" :href="`/concretos-oriente/Backend/${selectedProject.foto_contrato}`" target="_blank" class="p-2 bg-primary/20 hover:bg-primary text-primary hover:text-white rounded-xl transition-all" title="Ver / Descargar">
                      <ArrowDownTrayIcon class="w-4 h-4" />
                    </a>
                  </div>

                  <!-- Excel Presupuesto -->
                  <div class="bg-white/5 p-4 rounded-2xl border border-white/5 flex items-center justify-between">
                    <div class="flex items-center gap-3 truncate">
                      <TableCellsIcon class="w-6 h-6 text-emerald-400 shrink-0" />
                      <div class="truncate">
                        <p class="text-[10px] font-black text-white/40 uppercase tracking-wider">Excel Presupuesto</p>
                        <p class="text-xs font-bold text-white truncate">{{ selectedProject.excel_presupuesto ? 'Hoja de Presupuesto' : 'Sin adjuntar' }}</p>
                      </div>
                    </div>
                    <a v-if="selectedProject.excel_presupuesto" :href="`/concretos-oriente/Backend/${selectedProject.excel_presupuesto}`" target="_blank" class="p-2 bg-emerald-500/20 hover:bg-emerald-500 text-emerald-400 hover:text-white rounded-xl transition-all" title="Descargar Excel">
                      <ArrowDownTrayIcon class="w-4 h-4" />
                    </a>
                  </div>

                  <!-- Especificaciones Técnicas -->
                  <div class="bg-white/5 p-4 rounded-2xl border border-white/5 flex items-center justify-between">
                    <div class="flex items-center gap-3 truncate">
                      <DocumentCheckIcon class="w-6 h-6 text-cyan-400 shrink-0" />
                      <div class="truncate">
                        <p class="text-[10px] font-black text-white/40 uppercase tracking-wider">Especif. Técnicas</p>
                        <p class="text-xs font-bold text-white truncate">{{ selectedProject.especificaciones_tecnicas ? 'Documento Técnico' : 'Sin adjuntar' }}</p>
                      </div>
                    </div>
                    <a v-if="selectedProject.especificaciones_tecnicas" :href="`/concretos-oriente/Backend/${selectedProject.especificaciones_tecnicas}`" target="_blank" class="p-2 bg-cyan-500/20 hover:bg-cyan-500 text-cyan-400 hover:text-white rounded-xl transition-all" title="Ver Especificaciones">
                      <ArrowDownTrayIcon class="w-4 h-4" />
                    </a>
                  </div>

                  <!-- Convenios Registrados -->
                  <div class="bg-white/5 p-4 rounded-2xl border border-white/5 flex flex-col justify-center col-span-full">
                    <div class="flex items-center justify-between mb-2">
                      <div class="flex items-center gap-2">
                        <FolderIcon class="w-5 h-5 text-amber-400" />
                        <p class="text-[10px] font-black text-white/40 uppercase tracking-wider">Convenios y Adendas</p>
                      </div>
                      <span class="text-[10px] font-bold text-amber-400 bg-amber-400/10 px-2.5 py-0.5 rounded-full border border-amber-400/20">
                        {{ parseJson(selectedProject.convenios_archivos).length }} archivo(s)
                      </span>
                    </div>
                    <div v-if="parseJson(selectedProject.convenios_archivos).length > 0" class="flex flex-wrap gap-2 pt-1">
                      <a
                        v-for="(conv, idx) in parseJson(selectedProject.convenios_archivos)"
                        :key="idx"
                        :href="`/concretos-oriente/Backend/${conv}`"
                        target="_blank"
                        class="flex items-center gap-2 bg-black/40 hover:bg-amber-500/20 border border-white/10 hover:border-amber-400/30 px-3 py-2 rounded-xl text-xs font-bold text-white transition-all group"
                      >
                        <DocumentIcon class="w-4 h-4 text-amber-400 group-hover:scale-110 transition-transform" />
                        <span>Convenio {{ idx + 1 }}</span>
                        <ArrowDownTrayIcon class="w-3.5 h-3.5 text-white/40 group-hover:text-white" />
                      </a>
                    </div>
                    <p v-else class="text-xs text-white/30 italic">No hay convenios adjuntos.</p>
                  </div>
                </div>
              </div>

              <!-- Contactos -->
              <div v-if="selectedProject.contactos">
                <p class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-3 flex items-center gap-2"><UsersIcon class="w-4 h-4" /> Contactos del Proyecto</p>
                <div class="space-y-2">
                  <template v-if="parseContacts(selectedProject.contactos).length > 0">
                    <div
                      v-for="(c, i) in parseContacts(selectedProject.contactos)"
                      :key="i"
                      class="bg-white/5 p-4 rounded-2xl border border-white/5 grid grid-cols-2 gap-2 text-sm"
                    >
                      <div><span class="text-white/40 text-[10px] uppercase font-black block">Tipo</span><span class="font-bold text-white">{{ c.tipo || '—' }}</span></div>
                      <div><span class="text-white/40 text-[10px] uppercase font-black block">Nombre</span><span class="font-bold text-white">{{ c.nombre || '—' }}</span></div>
                      <div><span class="text-white/40 text-[10px] uppercase font-black block">Teléfono</span><span class="font-bold text-white">{{ c.telefono || '—' }}</span></div>
                      <div><span class="text-white/40 text-[10px] uppercase font-black block">Email</span><span class="font-bold text-white">{{ c.email || '—' }}</span></div>
                    </div>
                  </template>
                  <p v-else class="text-sm font-medium text-white/60">Sin contactos registrados</p>
                </div>
              </div>

              <!-- Descripción -->
              <div v-if="selectedProject.descripcion">
                <p class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-2 flex items-center gap-2"><BriefcaseIcon class="w-4 h-4" /> Descripción</p>
                <p class="text-sm font-medium text-white/80 whitespace-pre-line">{{ selectedProject.descripcion }}</p>
              </div>

              <!-- Ampliaciones de Presupuesto -->
              <div>
                <div class="flex items-center justify-between mb-3">
                  <p class="text-[10px] font-black text-white/40 uppercase tracking-widest flex items-center gap-2">
                    <CurrencyDollarIcon class="w-4 h-4" /> Ampliaciones de Presupuesto
                  </p>
                  <button @click="openExtensionModal" class="px-4 py-2 rounded-xl bg-primary/20 hover:bg-primary/30 text-primary text-[10px] font-black uppercase tracking-widest border border-primary/30 transition-all">
                    + Ampliación
                  </button>
                </div>
                <div v-if="budgetExtensions.length > 0" class="space-y-2">
                  <div v-for="ext in budgetExtensions" :key="ext.id" class="bg-white/5 p-4 rounded-2xl border border-white/5">
                    <div class="flex items-center justify-between mb-1">
                      <span class="text-xs font-black text-primary">Q {{ Number(ext.monto).toLocaleString('en-US', {minimumFractionDigits:2}) }}</span>
                      <span class="text-[10px] font-bold text-white/40 bg-white/5 px-2 py-1 rounded-lg">{{ ext.tipo_ampliacion }}</span>
                    </div>
                    <p class="text-[10px] text-white/30 mt-1">{{ new Date(ext.created_at).toLocaleDateString('es-GT') }}</p>
                    <div v-if="ext.documentos && ext.documentos.length > 0" class="flex flex-wrap gap-2 mt-2">
                      <a v-for="(doc, i) in ext.documentos" :key="i"
                        :href="`/concretos-oriente/Backend/${doc}`" target="_blank"
                        class="flex items-center gap-1 text-[10px] font-bold text-primary/80 hover:text-primary bg-primary/10 px-2 py-1 rounded-lg transition-all">
                        <DocumentIcon class="w-3 h-3" /> Doc {{ i + 1 }}
                      </a>
                    </div>
                  </div>
                </div>
                <p v-else class="text-sm font-medium text-white/30">Sin ampliaciones registradas</p>
              </div>

            </div>
          </div>
        </div>
      </div>
    </transition>
    </Teleport>

    <!-- Modal: Ampliación de Presupuesto -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="showExtensionModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
          <div @click="showExtensionModal = false" class="absolute inset-0 bg-black/80 backdrop-blur-sm cursor-pointer"></div>
          <div class="relative w-full max-w-lg glass-card rounded-[32px] p-8 border border-white/10 shadow-2xl z-10">
            <h3 class="text-xl font-black text-white italic uppercase tracking-tighter mb-6">Ampliación de Presupuesto</h3>
            <form @submit.prevent="submitExtension" class="space-y-5">
              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-1">Monto (Q) *</label>
                <input type="text" :value="getDisplayValue(extensionForm.monto)" @input="e => updateCurrencyField(extensionForm, 'monto', e)" required placeholder="Q 0.00"
                  class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white font-bold focus:outline-none focus:border-primary transition-all" />
              </div>
              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-1">Tipo de Ampliación *</label>
                <select v-model="extensionForm.tipo_ampliacion" required
                  class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white font-bold focus:outline-none focus:border-primary transition-all">
                  <option value="" disabled>Seleccione...</option>
                  <option value="Trabajo Extra">Trabajo Extra</option>
                  <option value="Orden de Cambio">Orden de Cambio</option>
                  <option value="Trabajo Suplementario">Trabajo Suplementario</option>
                </select>
              </div>
              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2 pl-1">Adjuntar Documentos (máx. 3, opcional)</label>
                <input type="file" multiple accept=".pdf,.jpg,.jpeg,.png" @change="handleExtensionFiles"
                  class="w-full text-white/60 file:mr-4 file:py-3 file:px-5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary/20 file:text-primary hover:file:bg-primary/30 file:transition-all cursor-pointer bg-black/40 border border-white/10 rounded-2xl p-2" />
                <div v-if="extensionForm.documentos.length > 0" class="flex flex-wrap gap-2 mt-2">
                  <span v-for="(f, i) in extensionForm.documentos" :key="i" class="text-[10px] font-bold bg-white/5 text-white/60 px-3 py-1 rounded-lg truncate max-w-[180px]">{{ f.name }}</span>
                </div>
              </div>
              <div class="flex justify-end gap-3 pt-2 border-t border-white/5">
                <button type="button" @click="showExtensionModal = false" class="px-6 py-3 rounded-xl font-bold text-white/50 hover:text-white hover:bg-white/5 transition-all">Cancelar</button>
                <button type="submit" :disabled="isSubmittingExtension" class="glass-button-primary text-white py-3 px-8 rounded-xl font-bold text-sm uppercase tracking-widest disabled:opacity-50 transition-all">
                  {{ isSubmittingExtension ? 'Guardando...' : 'Registrar' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Modal Historial Financiero -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="showHistoryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="closeHistoryModal"></div>
          
          <div class="glass-card w-full max-w-4xl rounded-[40px] p-8 md:p-12 relative z-10 border border-white/10 shadow-2xl flex flex-col max-h-[90vh]">
            <div class="flex items-center justify-between mb-8 shrink-0">
              <div>
                <h3 class="text-2xl md:text-3xl font-black text-white italic uppercase tracking-tight">Historial Financiero</h3>
                <p class="text-white/40 text-xs font-bold uppercase tracking-wider">Proyecto: {{ selectedHistoryProject?.nombre }}</p>
              </div>
              <button @click="closeHistoryModal" class="w-12 h-12 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-all border border-white/5 text-white/40 hover:text-white">
                <XMarkIcon class="w-6 h-6" />
              </button>
            </div>

            <div v-if="loadingHistory" class="flex-1 flex items-center justify-center">
              <div class="w-12 h-12 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
            </div>

            <div v-else class="flex-1 overflow-y-auto space-y-4 pr-2 custom-scrollbar">
              <div v-if="historyData.length === 0" class="p-12 text-center text-white/40 border border-dashed border-white/10 rounded-3xl">
                No hay movimientos financieros registrados.
              </div>
              <div v-else v-for="(item, idx) in historyData" :key="idx" class="p-6 rounded-3xl bg-black/40 border border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                  <div class="flex items-center gap-3 mb-2">
                    <span :class="['px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest', item.type.includes('Ingreso') ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400']">
                      {{ item.type }}
                    </span>
                    <span class="text-white/40 text-xs font-bold">{{ item.date ? item.date.split(' ')[0] : 'S/F' }}</span>
                  </div>
                  <p class="text-white font-bold">{{ item.detail }}</p>
                  <p class="text-white/50 text-xs mt-1"><strong class="text-white/30 uppercase tracking-wider text-[10px]">Entidad/Ref:</strong> {{ item.entity || 'N/A' }} <span v-if="item.reference">| Doc: {{ item.reference }}</span></p>
                </div>
                <div class="text-right shrink-0">
                  <p :class="['text-xl font-black italic whitespace-nowrap', item.type.includes('Ingreso') ? 'text-emerald-400' : 'text-rose-400']">
                    {{ item.type.includes('Ingreso') ? '+' : '-' }} Q {{ formatCurrency(item.amount) }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <!-- Modal para Agregar/Editar Proyecto -->
    <button @click="openModal" class="fixed bottom-6 right-6 md:bottom-12 md:right-12 h-14 w-14 md:h-20 md:w-20 rounded-[32px] glass-button-primary text-white shadow-2xl shadow-primary/40 flex items-center justify-center hover:scale-110 active:scale-95 transition-all z-40 group">
      <PlusIcon class="w-10 h-10 group-hover:rotate-90 transition-transform duration-500 shadow-[0_0_20px_rgba(99,102,241,0.5)]" />
    </button>

    <!-- Modal Formulario Proyecto -->
    <Teleport to="body">
      <transition name="fade">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm cursor-pointer" @click="closeModal"></div>

        <div class="glass-card w-full max-w-4xl max-h-[90vh] overflow-y-auto custom-scrollbar rounded-[40px] p-6 md:p-10 relative z-10 border border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.8)]">
          <div class="flex items-center justify-between mb-8 border-b border-white/10 pb-6">
            <h3 class="text-3xl font-black text-white italic uppercase tracking-tight">{{ isEditing ? 'Editar Proyecto' : 'Registrar Nuevo Proyecto' }}</h3>
            <button @click="closeModal" class="p-3 bg-white/5 hover:bg-white/10 rounded-2xl transition-all text-white/50 hover:text-white">
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>

          <form @submit.prevent="submitForm" class="space-y-8">

            <!-- Fila 1: Código y Nombre -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Código del Proyecto *</label>
                <input v-model="formData.codigo" type="text" maxlength="20" required class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold" placeholder="Ej. PRY-2025-001" />
              </div>
              <div>
                <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Nombre del Proyecto *</label>
                <input v-model="formData.nombre" type="text" required class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold" placeholder="Ej. Mejoramiento Calle Principal" />
              </div>
            </div>

            <!-- Fila 2: SNIP, NOG y Tipo de Inversión -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-white/[0.02] p-6 rounded-3xl border border-white/5">
              <div>
                <label class="text-[10px] font-black text-cyan-400 uppercase tracking-widest mb-2 block">Código SNIP</label>
                <input v-model="formData.snip" type="text" placeholder="Ej. 315482" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:border-cyan-400 transition-all font-bold font-mono" />
              </div>
              <div>
                <label class="text-[10px] font-black text-cyan-400 uppercase tracking-widest mb-2 block">Código NOG</label>
                <input v-model="formData.nog" type="text" placeholder="Ej. 21948512" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:border-cyan-400 transition-all font-bold font-mono" />
              </div>
              <div>
                <label class="text-[10px] font-black text-cyan-400 uppercase tracking-widest mb-2 block">Tipo de Inversión *</label>
                <select v-model="formData.tipo_inversion" required class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-cyan-400 transition-all font-bold appearance-none">
                  <option value="Por Administración">Por Administración</option>
                  <option value="Privado">Privado</option>
                  <option value="Ordinario">Ordinario</option>
                  <option value="Extraordinario">Extraordinario</option>
                </select>
              </div>
            </div>

            <!-- Fila 3: Cliente, Estado y Contrato -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Cliente *</label>
                <select v-model="formData.cliente_id" required class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold appearance-none">
                  <option value="" disabled>Seleccionar cliente...</option>
                  <option v-for="c in CLIENTES" :key="c.id" :value="c.id">{{ c.company_name }}</option>
                </select>
              </div>
              <div>
                <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Estado *</label>
                <select v-model="formData.estado" required class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold appearance-none">
                  <option value="Borrador">Borrador</option>
                  <option value="Activo">Activo</option>
                  <option value="Pausado">Pausado</option>
                  <option value="Completado">Completado</option>
                  <option value="Cancelado">Cancelado</option>
                </select>
              </div>
              <div>
                <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Número de Contrato</label>
                <input v-model="formData.numero_contrato" type="text" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold" placeholder="Ej. CONTRATO-01-2025" />
              </div>
            </div>

            <!-- Fila 4: Presupuesto Total y Desglose de Valores (COCODE, MUNI, COMUNIDAD) -->
            <div class="bg-white/5 p-6 rounded-3xl border border-white/10 space-y-4">
              <div class="flex items-center justify-between pb-3 border-b border-white/10">
                <label class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-2">
                  <CurrencyDollarIcon class="w-4 h-4 text-primary" />
                  Presupuesto y Fuentes de Financiamiento
                </label>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                  <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Presupuesto Contractual (GTQ) *</label>
                  <input type="text" :value="getDisplayValue(Number(formData.monto_cocode || 0) + Number(formData.monto_muni || 0) + Number(formData.monto_comunidad || 0))" readonly class="w-full bg-black/20 border border-white/10 rounded-2xl px-4 py-3.5 text-white/50 cursor-not-allowed transition-all font-bold" placeholder="Q 0.00" />
                </div>
                <div>
                  <label class="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-2 block">Monto COCODE (Q)</label>
                  <input type="text" :value="getDisplayValue(formData.monto_cocode)" @input="e => updateCurrencyField(formData, 'monto_cocode', e)" class="w-full bg-black/40 border border-white/10 rounded-2xl px-4 py-3.5 text-white placeholder-white/20 focus:border-emerald-400 transition-all font-bold" placeholder="Q 0.00" />
                </div>
                <div>
                  <label class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2 block">Monto Municipalidad (Q)</label>
                  <input type="text" :value="getDisplayValue(formData.monto_muni)" @input="e => updateCurrencyField(formData, 'monto_muni', e)" class="w-full bg-black/40 border border-white/10 rounded-2xl px-4 py-3.5 text-white placeholder-white/20 focus:border-blue-400 transition-all font-bold" placeholder="Q 0.00" />
                </div>
                <div>
                  <label class="text-[10px] font-black text-amber-400 uppercase tracking-widest mb-2 block">Monto Comunidad (Q)</label>
                  <input type="text" :value="getDisplayValue(formData.monto_comunidad)" @input="e => updateCurrencyField(formData, 'monto_comunidad', e)" class="w-full bg-black/40 border border-white/10 rounded-2xl px-4 py-3.5 text-white placeholder-white/20 focus:border-amber-400 transition-all font-bold" placeholder="Q 0.00" />
                </div>
              </div>
            </div>

            <!-- Fila 5: Fechas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-white/5 p-6 rounded-3xl border border-white/10">
              <div>
                <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Fecha de Inicio *</label>
                <input v-model="formData.fecha_inicio" type="date" required class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold" />
              </div>
              <div>
                <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Fecha de Fin Estimada</label>
                <input v-model="formData.fecha_fin_estimada" type="date" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold" />
              </div>
              <div>
                <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Fecha de Fin Real</label>
                <input v-model="formData.fecha_fin_real" type="date" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold" />
              </div>
            </div>

            <!-- Fila 6: Gerente Responsable -->
            <div>
              <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Gerente Responsable</label>
              <select v-model="formData.gerente_id" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold appearance-none">
                <option value="">Sin asignar</option>
                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.nombre }}</option>
              </select>
            </div>

            <!-- Fila 7: Ubicación y Mapa GPS -->
            <div>
              <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Nombre de la Ubicación / Dirección</label>
              <input v-model="formData.ubicacion" type="text" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold mb-4" placeholder="Ej. Caserío Los Ángeles, Aldea El Progreso, Zacapa" />

              <div class="flex items-center justify-between mb-2">
                <label class="text-[10px] font-black text-white/50 uppercase tracking-widest block">Coordenadas GPS (Haz clic en el mapa)</label>
                <button type="button" @click="toggleMapFullscreen" class="text-xs text-primary hover:text-white font-bold flex items-center gap-1 transition-all">
                  <span v-if="mapFullscreen">Cerrar Pantalla Completa</span>
                  <span v-else>Ver en Pantalla Completa</span>
                </button>
              </div>
              <div class="space-y-3">
                <div
                  :class="mapFullscreen ? 'fixed inset-0 z-[100] bg-black p-4 md:p-10 flex flex-col' : 'relative h-80 w-full rounded-2xl border border-white/10'"
                >
                  <div v-if="mapFullscreen" class="flex justify-between items-center mb-4 bg-black/80 backdrop-blur-md p-4 rounded-2xl border border-white/10">
                    <p class="text-white font-black uppercase tracking-widest text-lg">Seleccionar Ubicación</p>
                    <button type="button" @click="toggleMapFullscreen" class="p-2 bg-white/10 hover:bg-tertiary rounded-xl text-white transition-all">
                      <XMarkIcon class="w-6 h-6" />
                    </button>
                  </div>
                  <div :class="mapFullscreen ? 'flex-1 w-full relative' : 'h-full w-full relative min-h-[300px]'">
                    <div id="project-map" class="absolute inset-0 rounded-2xl border border-white/10 z-0"></div>
                  </div>
                  <div v-if="mapFullscreen" class="mt-4 p-4 bg-black/80 backdrop-blur-md rounded-2xl border border-white/10 flex flex-col md:flex-row gap-4 items-center">
                    <input v-model="formData.coordenadas" type="text" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-3 text-white focus:border-primary transition-all font-bold text-sm" placeholder="Latitud, Longitud" readonly />
                    <button type="button" @click="getLocation" class="w-full md:w-auto px-6 py-3 bg-primary hover:bg-primary/80 rounded-2xl transition-all font-black uppercase tracking-widest flex items-center justify-center gap-2 text-sm text-white">
                      <MapPinIcon class="w-5 h-5 text-white" /> Mi Ubicación
                    </button>
                    <button type="button" @click="toggleMapFullscreen" class="w-full md:w-auto px-6 py-3 bg-white/10 hover:bg-white/20 rounded-2xl transition-all font-black uppercase tracking-widest text-sm text-white">
                      Confirmar
                    </button>
                  </div>
                </div>

                <div v-if="!mapFullscreen" class="flex gap-2">
                  <input v-model="formData.coordenadas" type="text" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold text-xs" placeholder="Coordenadas GPS (Latitud, Longitud)" readonly />
                  <button type="button" @click="getLocation" class="px-5 bg-white/10 hover:bg-primary rounded-2xl transition-all" title="Obtener mi ubicación actual">
                    <MapPinIcon class="w-6 h-6 text-white" />
                  </button>
                </div>
              </div>
            </div>

            <!-- Fila 8: Descripción -->
            <div>
              <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">Descripción del Proyecto</label>
              <textarea v-model="formData.descripcion" rows="3" class="w-full bg-black/40 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:border-primary focus:ring-1 focus:ring-primary transition-all font-bold resize-none" placeholder="Escribe los detalles del proyecto..."></textarea>
            </div>

            <!-- Fila 9: Contactos Dinámicos -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <label class="text-[10px] font-black text-white/50 uppercase tracking-widest block">Contactos del Proyecto</label>
                <button type="button" @click="addContact" class="flex items-center gap-2 text-xs font-black text-primary hover:text-white bg-primary/10 hover:bg-primary/30 px-4 py-2 rounded-xl transition-all uppercase tracking-widest">
                  <PlusIcon class="w-4 h-4" /> Agregar Contacto
                </button>
              </div>

              <div v-if="formData.contactos.length === 0" class="text-center py-6 bg-white/5 rounded-2xl border border-white/10 border-dashed">
                <p class="text-white/30 text-sm font-bold uppercase tracking-widest">Sin contactos agregados</p>
              </div>

              <div v-for="(contact, i) in formData.contactos" :key="i" class="bg-white/5 p-5 rounded-2xl border border-white/10 space-y-4">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-black text-primary uppercase tracking-widest">Contacto {{ i + 1 }}</span>
                  <button type="button" @click="removeContact(i)" class="p-1.5 hover:bg-tertiary/20 hover:text-tertiary text-white/40 rounded-lg transition-all">
                    <XMarkIcon class="w-4 h-4" />
                  </button>
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-1 block">Tipo</label>
                    <select v-model="contact.tipo" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:border-primary transition-all font-bold appearance-none">
                      <option value="Propietario">Propietario</option>
                      <option value="Supervisor">Supervisor</option>
                      <option value="Residente">Residente</option>
                      <option value="Proveedor">Proveedor</option>
                      <option value="Contratista">Contratista</option>
                      <option value="Administrativo">Administrativo</option>
                      <option value="Otro">Otro</option>
                    </select>
                  </div>
                  <div>
                    <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-1 block">Nombre</label>
                    <input v-model="contact.nombre" type="text" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-white/20 focus:border-primary transition-all font-bold" placeholder="Nombre completo" />
                  </div>
                  <div>
                    <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-1 block">Teléfono</label>
                    <input v-model="contact.telefono" type="tel" maxlength="8" pattern="\d{8}" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-white/20 focus:border-primary transition-all font-bold" placeholder="00000000" />
                  </div>
                  <div>
                    <label class="text-[10px] font-black text-white/40 uppercase tracking-widest mb-1 block">Email</label>
                    <input v-model="contact.email" type="email" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-white/20 focus:border-primary transition-all font-bold" placeholder="correo@ejemplo.com" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Fila 10: Expediente Digital y Archivos -->
            <div class="bg-white/5 p-6 rounded-3xl border border-white/10 space-y-6">
              <div class="flex items-center gap-2 border-b border-white/10 pb-3">
                <PaperClipIcon class="w-5 h-5 text-primary" />
                <h4 class="text-xs font-black text-white uppercase tracking-wider">Archivos y Documentos del Proyecto</h4>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Foto Portada -->
                <div>
                  <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">
                    Foto de Portada <span v-if="isEditing" class="text-primary normal-case font-normal">(Opcional — mantiene actual)</span>
                  </label>
                  <input @change="handleFotoChange" type="file" accept="image/*" :required="!isEditing" class="w-full text-white/60 file:mr-4 file:py-3 file:px-5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary/20 file:text-primary hover:file:bg-primary/30 file:transition-all cursor-pointer bg-black/40 border border-white/10 rounded-2xl p-2" />
                </div>

                <!-- Foto de Contrato -->
                <div>
                  <label class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2 block">
                    Foto de Contrato (PDF o Imagen) <span v-if="isEditing" class="text-primary normal-case font-normal">(Opcional)</span>
                  </label>
                  <input @change="handleFotoContratoChange" type="file" accept="image/*,.pdf" class="w-full text-white/60 file:mr-4 file:py-3 file:px-5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary/20 file:text-primary hover:file:bg-primary/30 file:transition-all cursor-pointer bg-black/40 border border-white/10 rounded-2xl p-2" />
                </div>

                <!-- Excel Presupuesto -->
                <div>
                  <label class="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-2 block">
                    Excel de Presupuesto (.xlsx, .xls, .csv) <span v-if="isEditing" class="text-primary normal-case font-normal">(Opcional)</span>
                  </label>
                  <input @change="handleExcelChange" type="file" accept=".xlsx,.xls,.csv" class="w-full text-white/60 file:mr-4 file:py-3 file:px-5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-500/20 file:text-emerald-400 hover:file:bg-emerald-500/30 file:transition-all cursor-pointer bg-black/40 border border-white/10 rounded-2xl p-2" />
                </div>

                <!-- Especificaciones Técnicas -->
                <div>
                  <label class="text-[10px] font-black text-cyan-400 uppercase tracking-widest mb-2 block">
                    Especificaciones Técnicas (PDF o Doc) <span v-if="isEditing" class="text-primary normal-case font-normal">(Opcional)</span>
                  </label>
                  <input @change="handleEspecificacionesChange" type="file" accept=".pdf,.doc,.docx" class="w-full text-white/60 file:mr-4 file:py-3 file:px-5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-cyan-500/20 file:text-cyan-400 hover:file:bg-cyan-500/30 file:transition-all cursor-pointer bg-black/40 border border-white/10 rounded-2xl p-2" />
                </div>
              </div>

              <!-- Convenios (Hasta 5 archivos) -->
              <div class="pt-4 border-t border-white/10">
                <div class="flex items-center justify-between mb-2">
                  <label class="text-[10px] font-black text-amber-400 uppercase tracking-widest block">
                    Convenios y Adendas (Hasta 5 archivos — PDF / Documentos / Imágenes)
                  </label>
                  <span class="text-[10px] font-bold text-white/40">{{ formData.convenios.length }}/5 archivos seleccionados</span>
                </div>
                <input @change="handleConveniosChange" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="w-full text-white/60 file:mr-4 file:py-3 file:px-5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-500/20 file:text-amber-400 hover:file:bg-amber-500/30 file:transition-all cursor-pointer bg-black/40 border border-white/10 rounded-2xl p-2" />

                <div v-if="formData.convenios && formData.convenios.length > 0" class="mt-3 space-y-2">
                  <div v-for="(file, i) in formData.convenios" :key="i" class="flex items-center justify-between bg-black/30 p-3 rounded-xl border border-white/5">
                    <div class="flex items-center gap-2 truncate flex-1">
                      <FolderIcon class="w-4 h-4 text-amber-400 shrink-0" />
                      <span class="text-xs text-white/80 font-medium truncate">{{ file.name }}</span>
                    </div>
                    <button type="button" @click="removeConvenio(i)" class="p-1 hover:bg-rose-500/20 hover:text-rose-400 text-white/40 rounded-lg transition-all ml-2">
                      <XMarkIcon class="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Acciones -->
            <div class="pt-6 flex justify-end gap-4 border-t border-white/10">
              <button type="button" @click="closeModal" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/10 transition-all">
                Cancelar
              </button>
              <button type="submit" :disabled="isSubmitting" class="glass-button-primary text-white py-4 px-12 rounded-2xl font-black uppercase tracking-widest flex items-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                <span v-if="isSubmitting">Guardando...</span>
                <span v-else>{{ isEditing ? 'Actualizar Proyecto' : 'Guardar Proyecto' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import {
  BuildingOfficeIcon, ChevronRightIcon,
  PlusIcon, XMarkIcon, MapPinIcon,
  ChartBarIcon, BriefcaseIcon, CurrencyDollarIcon, UserIcon, UsersIcon,
  PencilIcon, TrashIcon, DocumentTextIcon, PaperClipIcon, DocumentIcon, ArrowDownTrayIcon,
  MagnifyingGlassIcon, FunnelIcon, AdjustmentsHorizontalIcon, ArrowsUpDownIcon,
  FolderIcon, TableCellsIcon, DocumentCheckIcon, ClipboardDocumentIcon, ClipboardDocumentCheckIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const BASE_URL = '/concretos-oriente/Backend/api/v1';

// Lista de clientes
const CLIENTES = ref([]);

const view = ref('projects');
const projects = ref([]);
const users = ref([]);
const loading = ref(true);

// Búsqueda y filtros
const searchQuery = ref('');
const filterEstado = ref('');
const filterTipoInversion = ref('');
const filterCliente = ref('');
const filterGerente = ref('');
const sortBy = ref('fecha_inicio_desc');
const showAdvancedFilters = ref(false);
const filterPresupuestoMin = ref('');
const filterPresupuestoMax = ref('');
const filterFechaDesde = ref('');
const filterFechaHasta = ref('');
const selectedProject = ref(null);
const budgetExtensions = ref([]);
const showExtensionModal = ref(false);
const isSubmittingExtension = ref(false);
const extensionForm = ref({ monto: '', tipo_ampliacion: '', documentos: [] });
const showModal = ref(false);
const showHistoryModal = ref(false);
const selectedHistoryProject = ref(null);
const historyData = ref([]);
const loadingHistory = ref(false);

const isSubmitting = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const mapFullscreen = ref(false);
const copiedLocation = ref(false);
const copiedMapsUrl = ref(false);

const getDisplayValue = (val) => {
  if (val === null || val === undefined || val === '') return '';
  const str = String(val);
  const parts = str.split('.');
  const numPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  return parts.length > 1 ? `Q ${numPart}.${parts[1]}` : `Q ${numPart}`;
};

const updateCurrencyField = (obj, key, event) => {
  let raw = event.target.value.replace(/[^0-9.]/g, '');
  const parts = raw.split('.');
  if (parts.length > 2) raw = parts[0] + '.' + parts.slice(1).join('');
  obj[key] = raw === '' ? '' : raw;
  event.target.value = getDisplayValue(raw);
};

const fetchBudgetExtensions = async (projectId) => {
  if (!projectId) {
    budgetExtensions.value = [];
    return;
  }
  try {
    const res = await fetch(`${BASE_URL}/projects/${projectId}/budget-extensions`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    });
    const data = await res.json();
    if (data && data.status === 'success') {
      budgetExtensions.value = data.data || [];
    } else {
      budgetExtensions.value = [];
    }
  } catch (e) {
    console.error('Error fetching budget extensions:', e);
    budgetExtensions.value = [];
  }
};

const getProjectMapsUrl = (proj) => {
  if (!proj) return '';
  const coords = proj.coordenadas ? String(proj.coordenadas).trim() : '';
  const loc = proj.ubicacion ? String(proj.ubicacion).trim() : '';
  
  if (coords) {
    if (coords.startsWith('http://') || coords.startsWith('https://')) return coords;
    return `https://www.google.com/maps?q=${encodeURIComponent(coords)}`;
  }
  if (loc) {
    if (loc.startsWith('http://') || loc.startsWith('https://')) return loc;
    return `https://www.google.com/maps?q=${encodeURIComponent(loc)}`;
  }
  return '';
};

const copyMapsUrl = async (proj) => {
  const url = typeof proj === 'string' ? proj : getProjectMapsUrl(proj);
  if (!url) {
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'warning',
      title: 'No hay ubicación o coordenadas para generar URL',
      showConfirmButton: false,
      timer: 2000,
      background: '#0f172a',
      color: '#fff'
    });
    return;
  }
  try {
    await navigator.clipboard.writeText(url);
  } catch (err) {
    const el = document.createElement('textarea');
    el.value = url;
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
  }
  copiedMapsUrl.value = true;
  setTimeout(() => { copiedMapsUrl.value = false; }, 2000);
  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'success',
    title: 'URL de Google Maps copiada',
    text: url,
    showConfirmButton: false,
    timer: 2500,
    background: '#0f172a',
    color: '#fff'
  });
};

const copyAddress = async (text) => {
  if (!text) return;
  try {
    await navigator.clipboard.writeText(text);
  } catch (err) {
    const el = document.createElement('textarea');
    el.value = text;
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
  }
  copiedLocation.value = true;
  setTimeout(() => { copiedLocation.value = false; }, 2000);
  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'success',
    title: 'Dirección copiada al portapapeles',
    showConfirmButton: false,
    timer: 1800,
    background: '#0f172a',
    color: '#fff'
  });
};

const openExtensionModal = () => {
  extensionForm.value = { monto: '', tipo_ampliacion: '', documentos: [] };
  showExtensionModal.value = true;
};

const handleExtensionFiles = (e) => {
  const nuevos = Array.from(e.target.files);
  const combinados = [...extensionForm.value.documentos, ...nuevos];
  if (combinados.length > 3) {
    Swal.fire({ icon: 'warning', title: 'Máximo 3 documentos', text: 'Solo se permiten hasta 3 archivos por ampliación.', background: '#0f172a', color: '#fff', confirmButtonColor: '#6366f1' });
    extensionForm.value.documentos = combinados.slice(0, 3);
  } else {
    extensionForm.value.documentos = combinados;
  }
  e.target.value = '';
};

const submitExtension = async () => {
  if (!extensionForm.value.monto || !extensionForm.value.tipo_ampliacion) {
    Swal.fire({ icon: 'warning', title: 'Campos requeridos', text: 'Completa el monto y el tipo de ampliación.', background: '#0f172a', color: '#fff' });
    return;
  }
  isSubmittingExtension.value = true;
  try {
    const fd = new FormData();
    fd.append('monto', extensionForm.value.monto);
    fd.append('tipo_ampliacion', extensionForm.value.tipo_ampliacion);
    extensionForm.value.documentos.forEach(file => fd.append('documentos[]', file));

    const res = await fetch(`${BASE_URL}/projects/${selectedProject.value.id}/budget-extensions`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
      body: fd
    });
    const data = await res.json();
    if (data.status === 'success') {
      showExtensionModal.value = false;
      await fetchBudgetExtensions(selectedProject.value.id);
      Swal.fire({ icon: 'success', title: 'Ampliación registrada', background: '#0f172a', color: '#fff', timer: 2000, showConfirmButton: false });
    } else {
      throw new Error(data.message);
    }
  } catch (e) {
    Swal.fire({ icon: 'error', title: 'Error', text: e.message || 'Error al guardar la ampliación.', background: '#0f172a', color: '#fff' });
  } finally {
    isSubmittingExtension.value = false;
  }
};

const emptyForm = () => ({
  codigo: '',
  nombre: '',
  cliente_id: '',
  ubicacion: '',
  coordenadas: '',
  presupuesto: '',
  fecha_inicio: '',
  fecha_fin_estimada: '',
  fecha_fin_real: '',
  estado: 'Borrador',
  numero_contrato: '',
  descripcion: '',
  contactos: [],
  gerente_id: '',
  snip: '',
  nog: '',
  monto_cocode: '',
  monto_muni: '',
  monto_comunidad: '',
  tipo_inversion: 'Por Administración',
  foto: null,
  foto_contrato: null,
  excel_presupuesto: null,
  especificaciones_tecnicas: null,
  convenios: [],
  contratos: []
});

const formData = ref(emptyForm());

const filteredProjects = computed(() => {
  let result = [...projects.value];

  // Búsqueda por texto
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim();
    result = result.filter(p =>
      p.codigo?.toLowerCase().includes(q) ||
      p.nombre?.toLowerCase().includes(q) ||
      p.snip?.toLowerCase().includes(q) ||
      p.nog?.toLowerCase().includes(q) ||
      p.numero_contrato?.toLowerCase().includes(q) ||
      p.ubicacion?.toLowerCase().includes(q)
    );
  }

  // Filtro por estado
  if (filterEstado.value) {
    result = result.filter(p => p.estado === filterEstado.value);
  }

  // Filtro por tipo de inversión
  if (filterTipoInversion.value) {
    result = result.filter(p => p.tipo_inversion === filterTipoInversion.value);
  }

  // Filtro por cliente
  if (filterCliente.value) {
    result = result.filter(p => String(p.cliente_id) === String(filterCliente.value));
  }

  // Filtro por gerente
  if (filterGerente.value) {
    result = result.filter(p => String(p.gerente_id) === String(filterGerente.value));
  }

  // Filtro por presupuesto mínimo
  if (filterPresupuestoMin.value !== '') {
    result = result.filter(p => parseFloat(p.presupuesto) >= parseFloat(filterPresupuestoMin.value));
  }

  // Filtro por presupuesto máximo
  if (filterPresupuestoMax.value !== '') {
    result = result.filter(p => parseFloat(p.presupuesto) <= parseFloat(filterPresupuestoMax.value));
  }

  // Filtro por fecha de inicio desde
  if (filterFechaDesde.value) {
    result = result.filter(p => p.fecha_inicio && p.fecha_inicio >= filterFechaDesde.value);
  }

  // Filtro por fecha de inicio hasta
  if (filterFechaHasta.value) {
    result = result.filter(p => p.fecha_inicio && p.fecha_inicio <= filterFechaHasta.value);
  }

  // Ordenamiento
  result.sort((a, b) => {
    switch (sortBy.value) {
      case 'fecha_inicio_desc': return (b.fecha_inicio || '').localeCompare(a.fecha_inicio || '');
      case 'fecha_inicio_asc':  return (a.fecha_inicio || '').localeCompare(b.fecha_inicio || '');
      case 'presupuesto_desc':  return parseFloat(b.presupuesto || 0) - parseFloat(a.presupuesto || 0);
      case 'presupuesto_asc':   return parseFloat(a.presupuesto || 0) - parseFloat(b.presupuesto || 0);
      case 'nombre_asc':        return (a.nombre || '').localeCompare(b.nombre || '');
      case 'nombre_desc':       return (b.nombre || '').localeCompare(a.nombre || '');
      default: return 0;
    }
  });

  return result;
});

const activeFiltersCount = computed(() => {
  let count = 0;
  if (filterEstado.value)         count++;
  if (filterTipoInversion.value)  count++;
  if (filterCliente.value)        count++;
  if (filterGerente.value)        count++;
  if (filterPresupuestoMin.value) count++;
  if (filterPresupuestoMax.value) count++;
  if (filterFechaDesde.value)     count++;
  if (filterFechaHasta.value)     count++;
  return count;
});

const resetFilters = () => {
  searchQuery.value         = '';
  filterEstado.value        = '';
  filterTipoInversion.value = '';
  filterCliente.value       = '';
  filterGerente.value       = '';
  filterPresupuestoMin.value = '';
  filterPresupuestoMax.value = '';
  filterFechaDesde.value    = '';
  filterFechaHasta.value    = '';
  sortBy.value              = 'fecha_inicio_desc';
  showAdvancedFilters.value = false;
};

onMounted(async () => {
  await Promise.all([fetchProjects(), fetchUsers(), fetchClients()]);
});

const fetchProjects = async () => {
  loading.value = true;
  try {
    const response = await fetch(`${BASE_URL}/projects`);
    const result = await response.json();
    if (result.status === 'success') {
      const fetchTime = Date.now();
      projects.value = result.data.map(proj => ({ ...proj, _t: fetchTime }));
    }
  } catch (error) {
    console.error('Error fetching projects:', error);
  } finally {
    loading.value = false;
  }
};

const fetchUsers = async () => {
  try {
    const response = await fetch(`${BASE_URL}/users`);
    const result = await response.json();
    if (result.status === 'success') {
      users.value = result.data;
    }
  } catch (error) {
    console.error('Error fetching users:', error);
  }
};

const fetchClients = async () => {
  try {
    const response = await fetch(`${BASE_URL}/clients`);
    const result = await response.json();
    if (result.status === 'success') {
      CLIENTES.value = result.data;
    }
  } catch (error) {
    console.error('Error fetching clients:', error);
  }
};

const getStatusColor = (status) => {
  switch (status) {
    case 'Activo':    return 'bg-primary text-primary';
    case 'Pausado':   return 'bg-yellow-500 text-yellow-500';
    case 'Completado': return 'bg-green-500 text-green-500';
    case 'Cancelado': return 'bg-tertiary text-tertiary';
    default:          return 'bg-white/40 text-white/40';
  }
};

const formatCurrency = (value) => {
  if (value === null || value === undefined || value === '') return '0.00';
  const num = typeof value === 'number' ? value : parseFloat(String(value).replace(/,/g, ''));
  if (isNaN(num)) return '0.00';
  return num.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (value) => {
  if (!value) return null;
  try {
    const str = String(value).split(' ')[0];
    const parts = str.split('-');
    if (parts.length === 3) {
      return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return str;
  } catch {
    return value;
  }
};

const getPhotoUrl = (proj) => {
  if (!proj || !proj.foto) return 'https://images.unsplash.com/photo-1541888946425-d81bb19480c5?auto=format&fit=crop&q=80&w=2070';
  const timestamp = proj._t || Date.now();
  return `/concretos-oriente/Backend/${proj.foto}?t=${timestamp}`;
};

const getClienteName = (id) => {
  if (!id) return '—';
  const found = CLIENTES.value?.find(c => c.id == id);
  return found ? (found.company_name || found.nombre || '—') : '—';
};

const getManagerName = (id) => {
  if (!id) return '—';
  const found = users.value?.find(u => u.id == id);
  return found ? (found.nombre || found.name || '—') : '—';
};

const parseContacts = (raw) => {
  if (!raw) return [];
  if (Array.isArray(raw)) return raw;
  if (typeof raw === 'object') return [raw];
  try {
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
};

const parseJson = (raw) => {
  if (!raw) return [];
  if (Array.isArray(raw)) return raw;
  try {
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
};

const projectFinances = ref([]);
const loadingProjectFinances = ref(false);

const fetchProjectFinances = async (id) => {
  loadingProjectFinances.value = true;
  projectFinances.value = [];
  try {
    const res = await fetch(`${BASE_URL}/projects/${id}/finances`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const data = await res.json();
    if (data.status === 'success') {
      projectFinances.value = data.data;
    }
  } catch (error) {
    console.error('Error fetching finances:', error);
  } finally {
    loadingProjectFinances.value = false;
  }
};

const openProjectDetails = (proj) => {
  if (!proj) return;
  selectedProject.value = { ...proj };
  if (proj.id) {
    fetchBudgetExtensions(proj.id);
    fetchProjectFinances(proj.id);
  }
};

const openHistoryModal = async (proj) => {
  selectedHistoryProject.value = proj;
  showHistoryModal.value = true;
  loadingHistory.value = true;
  historyData.value = [];
  try {
    const res = await fetch(`${BASE_URL}/projects/${proj.id}/finances`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const data = await res.json();
    if (data.status === 'success') {
      historyData.value = data.data;
    }
  } catch (error) {
    console.error('Error fetching history:', error);
  } finally {
    loadingHistory.value = false;
  }
};

const closeHistoryModal = () => {
  showHistoryModal.value = false;
  selectedHistoryProject.value = null;
  historyData.value = [];
};

const closeProjectDetails = () => {
  selectedProject.value = null;
};

const openModal = () => {
  formData.value = emptyForm();
  isEditing.value = false;
  editingId.value = null;
  showModal.value = true;
  initMap();
};

const openEditModal = (proj) => {
  formData.value = {
    codigo: proj.codigo || '',
    nombre: proj.nombre || '',
    cliente_id: proj.cliente_id || '',
    ubicacion: proj.ubicacion || '',
    coordenadas: proj.coordenadas || '',
    presupuesto: proj.presupuesto || '',
    fecha_inicio: proj.fecha_inicio || '',
    fecha_fin_estimada: proj.fecha_fin_estimada || '',
    fecha_fin_real: proj.fecha_fin_real || '',
    estado: proj.estado || 'Borrador',
    numero_contrato: proj.numero_contrato || '',
    descripcion: proj.descripcion || '',
    contactos: parseContacts(proj.contactos),
    gerente_id: proj.gerente_id || '',
    snip: proj.snip || '',
    nog: proj.nog || '',
    monto_cocode: proj.monto_cocode || '',
    monto_muni: proj.monto_muni || '',
    monto_comunidad: proj.monto_comunidad || '',
    tipo_inversion: proj.tipo_inversion || 'Por Administración',
    foto: null,
    foto_contrato: null,
    excel_presupuesto: null,
    especificaciones_tecnicas: null,
    convenios: [],
    contratos: []
  };
  isEditing.value = true;
  editingId.value = proj.id;
  closeProjectDetails();
  showModal.value = true;

  if (proj.coordenadas) {
    const parts = proj.coordenadas.split(',');
    if (parts.length === 2) {
      initMap(parseFloat(parts[0]), parseFloat(parts[1]));
    } else {
      initMap();
    }
  } else {
    initMap();
  }
};

const closeModal = () => {
  showModal.value = false;
  formData.value = emptyForm();
};

// Contactos dinámicos
const addContact = () => {
  formData.value.contactos.push({ tipo: 'Supervisor', nombre: '', telefono: '', email: '' });
};

const removeContact = (index) => {
  formData.value.contactos.splice(index, 1);
};

// Mapa
let mapInstance = null;
let mapMarker = null;

const initMap = (lat = 14.6349, lng = -90.5069) => {
  nextTick(() => {
    if (mapInstance) {
      mapInstance.remove();
      mapInstance = null;
    }

    if (typeof L !== 'undefined') {
      mapInstance = L.map('project-map').setView([lat, lng], 13);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
      }).addTo(mapInstance);

      if (formData.value.coordenadas) {
        mapMarker = L.marker([lat, lng]).addTo(mapInstance);
      }

      mapInstance.on('click', function (e) {
        const clickedLat = e.latlng.lat.toFixed(6);
        const clickedLng = e.latlng.lng.toFixed(6);
        formData.value.coordenadas = `${clickedLat}, ${clickedLng}`;

        if (mapMarker) mapInstance.removeLayer(mapMarker);
        mapMarker = L.marker([clickedLat, clickedLng]).addTo(mapInstance);
      });

      setTimeout(() => mapInstance.invalidateSize(), 300);
    }
  });
};

const handleFotoChange = (e) => {
  const file = e.target.files[0];
  if (file) formData.value.foto = file;
};

const handleFotoContratoChange = (e) => {
  const file = e.target.files[0];
  if (file) formData.value.foto_contrato = file;
};

const handleExcelChange = (e) => {
  const file = e.target.files[0];
  if (file) formData.value.excel_presupuesto = file;
};

const handleEspecificacionesChange = (e) => {
  const file = e.target.files[0];
  if (file) formData.value.especificaciones_tecnicas = file;
};

const handleConveniosChange = (e) => {
  const newFiles = Array.from(e.target.files);
  const combinados = [...formData.value.convenios, ...newFiles];

  if (combinados.length > 5) {
    Swal.fire({
      title: 'Máximo 5 convenios',
      text: 'Se permiten adjuntar hasta 5 archivos en convenios.',
      icon: 'warning',
      background: '#0f172a',
      color: '#fff',
      confirmButtonColor: '#6366f1'
    });
    formData.value.convenios = combinados.slice(0, 5);
  } else {
    formData.value.convenios = combinados;
  }
  e.target.value = '';
};

const removeConvenio = (index) => {
  formData.value.convenios.splice(index, 1);
};

const toggleMapFullscreen = () => {
  mapFullscreen.value = !mapFullscreen.value;
  setTimeout(() => {
    if (mapInstance) mapInstance.invalidateSize();
  }, 350);
};

const getLocation = () => {
  if (!navigator.geolocation) return;
  navigator.geolocation.getCurrentPosition(
    (position) => {
      const lat = position.coords.latitude.toFixed(6);
      const lng = position.coords.longitude.toFixed(6);
      formData.value.coordenadas = `${lat}, ${lng}`;

      if (mapInstance) {
        if (mapMarker) mapInstance.removeLayer(mapMarker);
        mapMarker = L.marker([lat, lng]).addTo(mapInstance);
        mapInstance.setView([lat, lng], 15);
      }
    },
    () => {
      Swal.fire({
        title: 'Error',
        text: 'No se pudo obtener la ubicación. Revisa los permisos del navegador.',
        icon: 'error',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
      });
    }
  );
};

const submitForm = async () => {
  isSubmitting.value = true;

  const data = new FormData();
  data.append('codigo', formData.value.codigo);
  data.append('nombre', formData.value.nombre);
  data.append('cliente_id', formData.value.cliente_id || 0);
  data.append('ubicacion', formData.value.ubicacion || '');
  data.append('coordenadas', formData.value.coordenadas || '');
  data.append('presupuesto', formData.value.presupuesto || 0);
  data.append('fecha_inicio', formData.value.fecha_inicio);
  data.append('fecha_fin_estimada', formData.value.fecha_fin_estimada || '');
  data.append('fecha_fin_real', formData.value.fecha_fin_real || '');
  data.append('estado', formData.value.estado);
  data.append('numero_contrato', formData.value.numero_contrato || '');
  data.append('descripcion', formData.value.descripcion || '');
  data.append('contactos', JSON.stringify(formData.value.contactos));
  data.append('gerente_id', formData.value.gerente_id || 0);
  data.append('snip', formData.value.snip || '');
  data.append('nog', formData.value.nog || '');
  data.append('monto_cocode', formData.value.monto_cocode || 0);
  data.append('monto_muni', formData.value.monto_muni || 0);
  data.append('monto_comunidad', formData.value.monto_comunidad || 0);
  data.append('tipo_inversion', formData.value.tipo_inversion || '');

  if (formData.value.foto) {
    data.append('foto', formData.value.foto);
  }
  if (formData.value.foto_contrato) {
    data.append('foto_contrato', formData.value.foto_contrato);
  }
  if (formData.value.excel_presupuesto) {
    data.append('excel_presupuesto', formData.value.excel_presupuesto);
  }
  if (formData.value.especificaciones_tecnicas) {
    data.append('especificaciones_tecnicas', formData.value.especificaciones_tecnicas);
  }
  if (formData.value.convenios && formData.value.convenios.length > 0) {
    formData.value.convenios.forEach(file => {
      data.append('convenios[]', file);
    });
  }

  try {
    const url = isEditing.value
      ? `${BASE_URL}/projects/${editingId.value}`
      : `${BASE_URL}/projects`;

    const response = await fetch(url, { method: 'POST', body: data });
    const result = await response.json();

    if (result.status === 'success') {
      await fetchProjects();
      closeModal();
      Swal.fire({
        title: '¡Éxito!',
        text: isEditing.value ? 'Proyecto actualizado correctamente.' : 'Proyecto creado correctamente.',
        icon: 'success',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
        customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
      });
    } else {
      throw new Error(result.message);
    }
  } catch (error) {
    console.error('Error submitting project:', error);
    Swal.fire({
      title: 'Error',
      text: error.message || 'Error de conexión al servidor',
      icon: 'error',
      background: '#0f172a',
      color: '#fff',
      confirmButtonColor: '#6366f1',
      customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
    });
  } finally {
    isSubmitting.value = false;
  }
};

const deleteProject = async (id) => {
  const result = await Swal.fire({
    title: '¿Estás seguro?',
    text: 'Se eliminará el proyecto y todos sus archivos. Esta acción es irreversible.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#f43f5e',
    cancelButtonColor: '#475569',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    background: '#0f172a',
    color: '#fff',
    customClass: {
      popup: 'border border-white/10 rounded-3xl shadow-2xl',
      confirmButton: 'rounded-xl px-6 py-3 font-bold',
      cancelButton: 'rounded-xl px-6 py-3 font-bold'
    }
  });

  if (!result.isConfirmed) return;

  try {
    const response = await fetch(`${BASE_URL}/projects/${id}`, { method: 'DELETE' });
    const res = await response.json();

    if (res.status === 'success') {
      await fetchProjects();
      closeProjectDetails();
      Swal.fire({
        title: '¡Eliminado!',
        text: 'El proyecto fue borrado exitosamente.',
        icon: 'success',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
        customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
      });
    } else {
      throw new Error(res.message);
    }
  } catch (error) {
    Swal.fire({
      title: 'Error',
      text: error.message || 'Error al eliminar el proyecto',
      icon: 'error',
      background: '#0f172a',
      color: '#fff',
      confirmButtonColor: '#6366f1',
    });
  }
};
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: opacity 0.4s ease-out, transform 0.4s ease-out;
}
.fade-slide-enter-from {
  opacity: 0;
  transform: scale(0.98);
}
.fade-slide-leave-to {
  opacity: 0;
  transform: scale(0.98);
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.02); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
</style>
