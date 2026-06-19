<template>
  <div class="pt-20 pb-10 px-4 md:px-10 md:pb-20 max-w-7xl mx-auto space-y-10 relative">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h2 class="text-4xl font-bold tracking-tight text-white mb-2">
          {{ activeTab === 'log' ? 'Bitácora de Vehículos' : activeTab === 'register' ? (editingId ? 'Modificar Vehículo' : 'Registrar Vehículo') : 'Vehículos' }}
        </h2>
        <p class="text-white/60">Control de flota vehicular de la empresa.</p>
      </div>

      <div class="flex gap-2 bg-black/30 border border-white/10 rounded-2xl p-1 w-fit">
        <button @click="switchTab('fleet')" :class="['px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all', activeTab === 'fleet' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-white/50 hover:text-white']">Ver Flota</button>
        <button @click="switchTab('log')" :class="['px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center gap-2', activeTab === 'log' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-white/50 hover:text-white']">
          <ClipboardDocumentListIcon class="w-3.5 h-3.5" /> Bitácora
        </button>
        <button @click="switchTab('register')" :class="['px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center gap-2', activeTab === 'register' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-white/50 hover:text-white']">
          <PlusIcon class="w-3.5 h-3.5" /> {{ editingId ? 'Editando' : 'Registrar' }}
        </button>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-primary flex items-start justify-between">
        <div class="flex flex-col justify-between h-full w-full">
          <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">Total Vehículos</span>
          <div class="mt-3">
            <h3 class="text-4xl font-black italic text-white tracking-tighter">{{ stats.total }}</h3>
            <p class="text-[10px] font-bold text-white/30 uppercase tracking-wider mt-1">Unidades registradas</p>
          </div>
        </div>
        <div class="p-3 bg-primary/10 border border-primary/20 rounded-2xl text-primary shrink-0">
          <TruckIcon class="w-5 h-5" />
        </div>
      </div>

      <div class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-emerald-500/50 flex items-start justify-between">
        <div class="flex flex-col justify-between h-full w-full">
          <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">En Funcionamiento</span>
          <div class="mt-3">
            <h3 class="text-4xl font-black italic text-emerald-400 tracking-tighter">{{ stats.enFuncionamiento }}</h3>
            <p class="text-[10px] font-bold text-emerald-400/60 uppercase tracking-wider mt-1">Operativas</p>
          </div>
        </div>
        <div class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-emerald-400 shrink-0">
          <CheckCircleIcon class="w-5 h-5" />
        </div>
      </div>

      <div class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-blue-500/50 flex items-start justify-between">
        <div class="flex flex-col justify-between h-full w-full">
          <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">Nuevas</span>
          <div class="mt-3">
            <h3 class="text-4xl font-black italic text-blue-400 tracking-tighter">{{ stats.nuevo }}</h3>
            <p class="text-[10px] font-bold text-blue-400/60 uppercase tracking-wider mt-1">Recién ingresadas</p>
          </div>
        </div>
        <div class="p-3 bg-blue-500/10 border border-blue-500/20 rounded-2xl text-blue-400 shrink-0">
          <SparklesIcon class="w-5 h-5" />
        </div>
      </div>

      <div class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-rose-500/50 flex items-start justify-between">
        <div class="flex flex-col justify-between h-full w-full">
          <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">Inactivos</span>
          <div class="mt-3">
            <h3 class="text-4xl font-black italic text-rose-400 tracking-tighter">{{ stats.inactivo }}</h3>
            <p class="text-[10px] font-bold text-rose-400/60 uppercase tracking-wider mt-1">Fuera de servicio</p>
          </div>
        </div>
        <div class="p-3 bg-rose-500/10 border border-rose-500/20 rounded-2xl text-rose-400 shrink-0">
          <ExclamationTriangleIcon class="w-5 h-5" />
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════ VER FLOTA ═══ -->
    <template v-if="activeTab === 'fleet'">
      <!-- Filters -->
      <div class="flex flex-col lg:flex-row gap-4 items-center justify-between border-b border-white/5 pb-6">
        <div class="flex gap-1.5 bg-black/30 border border-white/10 rounded-2xl p-1 overflow-x-auto w-full lg:w-auto">
          <button v-for="f in statusOptions" :key="f.value" @click="statusFilter = f.value"
            :class="['px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap', statusFilter === f.value ? 'bg-primary text-white shadow-lg' : 'text-white/40 hover:text-white']">
            {{ f.label }}
          </button>
        </div>
        <div class="relative w-full lg:w-80">
          <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-white/40" />
          <input v-model="searchTerm" type="text" placeholder="Buscar por placa, marca o modelo..."
            class="glass-input pl-10 pr-4 py-3 rounded-xl text-xs font-bold w-full text-white placeholder:text-white/20" />
        </div>
      </div>

      <!-- Cards grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <div v-if="filteredVehicles.length === 0" class="col-span-full glass-card rounded-3xl p-16 text-center border border-white/5">
          <TruckIcon class="w-12 h-12 text-white/10 mx-auto mb-4" />
          <p class="text-white/30 font-black uppercase tracking-widest text-xs">Sin vehículos registrados con ese filtro.</p>
        </div>

        <div v-for="v in filteredVehicles" :key="v.id"
          class="glass-card rounded-[28px] border border-white/5 p-6 flex flex-col gap-4 hover:border-white/15 transition-all relative overflow-hidden">

          <!-- Header: placa + estado -->
          <div class="flex items-center justify-between">
            <span class="font-mono text-base font-black tracking-widest bg-white/5 border border-white/10 px-3 py-1.5 rounded-xl text-primary">
              {{ v.placa }}
            </span>
            <span :class="['px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-wider border', estatusBadge(v.estatus)]">
              {{ v.estatus }}
            </span>
          </div>

          <!-- Marca / Modelo + tipo -->
          <div>
            <h4 class="text-lg font-black italic uppercase text-white/90 tracking-tight leading-tight">
              {{ v.marca }} <span class="text-primary font-normal">{{ v.modelo }}</span>
            </h4>
            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
              <span class="px-2 py-0.5 bg-primary/10 border border-primary/20 rounded text-[9px] font-black text-primary uppercase tracking-widest">
                {{ v.tipo_vehiculo }}
              </span>
              <span v-if="v.tipo_seguro" class="px-2 py-0.5 bg-white/5 border border-white/10 rounded text-[9px] font-black text-white/50 uppercase tracking-widest">
                {{ v.tipo_seguro }}
              </span>
            </div>
          </div>

          <!-- Info rows -->
          <div class="bg-black/20 rounded-2xl p-4 border border-white/5 space-y-2.5">
            <div class="flex items-center justify-between">
              <span class="text-[9px] font-black text-white/30 uppercase tracking-widest">Piloto</span>
              <span class="text-xs font-black text-white/80 truncate max-w-[160px]">{{ v.piloto_nombre || 'Sin asignar' }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-[9px] font-black text-white/30 uppercase tracking-widest">Kilometraje</span>
              <span class="text-xs font-black text-white/80">{{ Number(v.kilometraje).toLocaleString() }} km</span>
            </div>
            <div v-if="v.precio" class="flex items-center justify-between">
              <span class="text-[9px] font-black text-white/30 uppercase tracking-widest">Precio</span>
              <span class="text-xs font-black text-emerald-400">Q {{ Number(v.precio).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
            </div>
            <div v-if="v.ubicacion" class="flex items-center justify-between">
              <span class="text-[9px] font-black text-white/30 uppercase tracking-widest">Ubicación</span>
              <span class="text-xs font-black text-white/70 truncate max-w-[160px]">{{ v.ubicacion }}</span>
            </div>
          </div>

          <!-- Foto delantera preview -->
          <div v-if="v.foto_delantera" class="rounded-2xl overflow-hidden aspect-video bg-black/20 border border-white/5">
            <img :src="photoUrl(v.foto_delantera)" class="w-full h-full object-cover" />
          </div>

          <!-- Acciones -->
          <div class="flex items-center justify-end gap-2 pt-2 border-t border-white/5">
            <button @click="openDetails(v)"
              class="px-4 py-2 bg-primary/10 hover:bg-primary/20 text-primary rounded-xl border border-primary/20 text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-1.5">
              <EyeIcon class="w-3.5 h-3.5" /> Detalles
            </button>
            <button @click="startEdit(v)"
              class="px-4 py-2 bg-white/5 hover:bg-white/10 text-white/60 hover:text-white rounded-xl border border-white/5 text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-1.5">
              <PencilIcon class="w-3.5 h-3.5" /> Modificar
            </button>
            <button @click="deleteVehicle(v.id, v.placa)"
              class="px-4 py-2 bg-white/5 hover:bg-white/10 text-white/30 hover:text-rose-400 rounded-xl border border-white/5 text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-1.5">
              <TrashIcon class="w-3.5 h-3.5" /> Retirar
            </button>
          </div>

          <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-primary/5 rounded-full blur-2xl pointer-events-none"></div>
        </div>
      </div>
    </template>

    <!-- ═══════════════════════════════════════════ BITÁCORA ═══ -->
    <template v-else-if="activeTab === 'log'">
      <div class="space-y-6">
        <div class="flex flex-col lg:flex-row justify-between items-center gap-4 border-b border-white/5 pb-6">
          <div class="flex gap-1.5 bg-black/30 border border-white/10 rounded-2xl p-1">
            <button v-for="f in statusOptions" :key="f.value" @click="statusFilter = f.value"
              :class="['px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all', statusFilter === f.value ? 'bg-primary text-white' : 'text-white/40 hover:text-white']">
              {{ f.label }}
            </button>
          </div>
          <button @click="openLogModal"
            class="px-5 py-2.5 bg-primary text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg hover:opacity-90 transition-all flex items-center gap-2 shrink-0">
            <ClipboardDocumentListIcon class="w-4 h-4" /> Registrar Bitácora
          </button>
        </div>

        <div class="glass-card rounded-[32px] overflow-hidden border border-white/5">
          <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left">
              <thead>
                <tr class="text-[10px] font-black text-white/30 uppercase tracking-widest border-b border-white/5 bg-white/5">
                  <th class="px-6 py-5">Vehículo</th>
                  <th class="px-6 py-5 text-center">Estado</th>
                  <th class="px-6 py-5">Piloto</th>
                  <th class="px-6 py-5">Último registro</th>
                  <th class="px-6 py-5 text-right">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr v-if="filteredVehicles.length === 0">
                  <td colspan="5" class="px-6 py-16 text-center text-white/30 font-black uppercase tracking-widest text-xs">Sin registros con el filtro aplicado.</td>
                </tr>
                <tr v-else v-for="v in filteredVehicles" :key="v.id" class="hover:bg-white/[0.015] transition-colors">
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-primary shrink-0">
                        <TruckIcon class="w-4 h-4" />
                      </div>
                      <div>
                        <p class="text-xs font-black text-white uppercase italic">{{ v.marca }} {{ v.modelo }}</p>
                        <p class="text-[9px] font-mono text-primary/80 tracking-widest mt-0.5">PLACA: {{ v.placa }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-center">
                    <span :class="['inline-flex px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border', estatusBadge(v.estatus)]">
                      {{ v.estatus }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <span class="text-xs font-bold text-white/80">{{ v.piloto_nombre || 'Sin asignar' }}</span>
                  </td>
                  <td class="px-6 py-4">
                    <span class="text-xs font-black text-white/70">
                      {{ v.history?.[0] ? `${v.history[0].type} · ${v.history[0].date}` : 'Sin historial' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <button @click="showHistory(v)"
                      class="px-4 py-2 bg-white/5 hover:bg-white/10 text-white/70 hover:text-white rounded-xl border border-white/10 text-[10px] font-black uppercase tracking-widest flex items-center gap-2 ml-auto">
                      <EyeIcon class="w-3.5 h-3.5" /> Historial
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="px-6 py-4 border-t border-white/5 flex items-center justify-between text-[9px] text-white/30 font-black uppercase tracking-widest">
            <span>{{ filteredVehicles.length }} unidades</span>
            <span>Bitácora de Flota</span>
          </div>
        </div>
      </div>
    </template>

    <!-- ═══════════════════════════════════════════ FORMULARIO ═══ -->
    <template v-else>
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- Left: form fields -->
        <div class="lg:col-span-8 space-y-6">

          <!-- Sección 1: Información General -->
          <section class="glass-card p-8 rounded-3xl border border-white/5 relative overflow-hidden">
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>
            <h3 class="text-xs font-black uppercase tracking-widest text-primary mb-6 flex items-center gap-2">
              <InformationCircleIcon class="w-4 h-4" /> Información General
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <!-- Placa -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Placa <span class="text-rose-400">*</span></label>
                <input v-model="form.placa" type="text" required placeholder="ABC-1234"
                  class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black uppercase tracking-widest text-white" />
              </div>

              <!-- Tipo Vehículo -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Tipo de Vehículo <span class="text-rose-400">*</span></label>
                <select v-model="form.tipo_vehiculo" required
                  class="w-full h-12 px-4 rounded-xl bg-slate-950/65 border border-white/10 text-sm font-black uppercase text-white focus:outline-none focus:border-primary">
                  <option value="">Seleccionar tipo</option>
                  <option value="Sedan">Sedan</option>
                  <option value="Pickup">Pickup</option>
                  <option value="Panel">Panel</option>
                </select>
              </div>

              <!-- Marca -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Marca <span class="text-rose-400">*</span></label>
                <input v-model="form.marca" type="text" required placeholder="Toyota, Nissan, Ford..."
                  class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" />
              </div>

              <!-- Modelo -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Modelo <span class="text-rose-400">*</span></label>
                <input v-model="form.modelo" type="text" required placeholder="Hilux 2024, NP300..."
                  class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" />
              </div>

              <!-- Tipo Seguro -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Tipo de Seguro</label>
                <select v-model="form.tipo_seguro"
                  class="w-full h-12 px-4 rounded-xl bg-slate-950/65 border border-white/10 text-sm font-black uppercase text-white focus:outline-none focus:border-primary">
                  <option value="">Sin seguro / No aplica</option>
                  <option value="Full Cover">Full Cover</option>
                  <option value="Danos a Terceros">Daños a Terceros</option>
                </select>
              </div>

              <!-- Ubicación -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Ubicación</label>
                <input v-model="form.ubicacion" type="text" placeholder="Bodega central, Proyecto X..."
                  class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" />
              </div>

              <!-- Estado -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Estado <span class="text-rose-400">*</span></label>
                <select v-model="form.estatus" required
                  class="w-full h-12 px-4 rounded-xl bg-slate-950/65 border border-white/10 text-sm font-black uppercase text-white focus:outline-none focus:border-primary">
                  <option value="Nuevo">Nuevo</option>
                  <option value="En Funcionamiento">En Funcionamiento</option>
                  <option value="Inactivo">Inactivo</option>
                </select>
              </div>

              <!-- Precio -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Precio</label>
                <div class="relative">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-black text-white/40">Q</span>
                  <input v-model="form.precio" type="number" min="0" step="0.01" placeholder="0.00"
                    class="w-full h-12 pl-8 pr-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" />
                </div>
              </div>

              <!-- Kilometraje -->
              <div class="space-y-2 md:col-span-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Kilometraje de Registro</label>
                <div class="relative">
                  <input v-model="form.kilometraje" type="number" min="0" placeholder="0"
                    class="w-full h-12 pl-4 pr-12 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" />
                  <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-white/40 tracking-widest">KM</span>
                </div>
              </div>
            </div>
          </section>

          <!-- Sección 2: Piloto Asignado -->
          <section class="glass-card p-8 rounded-3xl border border-white/5">
            <h3 class="text-xs font-black uppercase tracking-widest text-primary mb-6 flex items-center gap-2">
              <UserIcon class="w-4 h-4" /> Piloto Asignado
            </h3>
            <select v-model="form.piloto_id"
              class="w-full h-12 px-4 rounded-xl bg-slate-950/65 border border-white/10 text-sm font-black uppercase text-white focus:outline-none focus:border-primary">
              <option value="">Ninguno — Sin asignar</option>
              <option v-for="p in personnel" :key="p.id" :value="p.id">
                {{ p.nombres }} {{ p.apellidos }}
              </option>
            </select>
          </section>
        </div>

        <!-- Right: fotos + preview + botones -->
        <div class="lg:col-span-4 space-y-6">

          <!-- Fotos 2x2 -->
          <section class="glass-card p-6 rounded-3xl border border-white/5">
            <h3 class="text-xs font-black uppercase tracking-widest text-primary mb-5 flex items-center gap-2">
              <CameraIcon class="w-4 h-4" /> Registro Fotográfico
            </h3>
            <div class="grid grid-cols-2 gap-3">
              <div v-for="photo in photoFields" :key="photo.key"
                class="group relative aspect-video rounded-2xl bg-white/5 hover:bg-white/10 border-2 border-dashed border-white/10 hover:border-primary transition-all flex flex-col items-center justify-center cursor-pointer overflow-hidden text-center">
                <img v-if="photoPreviews[photo.key]" :src="photoPreviews[photo.key]"
                  class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:opacity-100 transition-opacity z-0" />
                <div class="z-10 flex flex-col items-center gap-1 p-2 rounded-xl transition-all"
                  :class="photoPreviews[photo.key] ? 'bg-slate-950/60 backdrop-blur-md opacity-0 group-hover:opacity-100' : ''">
                  <CameraIcon class="w-5 h-5 text-white/30 group-hover:text-primary transition-colors" />
                  <p class="text-[8px] font-black text-white/40 uppercase tracking-widest leading-tight">{{ photo.label }}</p>
                </div>
                <input type="file" accept="image/*" @change="onPhotoChange($event, photo.key)"
                  class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" />
              </div>
            </div>
          </section>

          <!-- Preview card -->
          <div class="bg-primary p-6 rounded-3xl text-white shadow-2xl relative overflow-hidden">
            <div class="relative z-10 space-y-4">
              <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/60">Vista Previa</p>
                <p class="text-2xl font-black italic tracking-tighter uppercase mt-1">{{ form.placa || 'PLACA-0000' }}</p>
              </div>
              <div class="space-y-2 pt-2 text-xs border-t border-white/20">
                <div class="flex justify-between">
                  <span class="text-white/60 text-[9px] font-black uppercase tracking-wider">Marca / Modelo</span>
                  <span class="font-black text-white/95 text-[10px]">{{ form.marca || '—' }} {{ form.modelo || '' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-white/60 text-[9px] font-black uppercase tracking-wider">Tipo</span>
                  <span class="font-black text-white/90 text-[10px]">{{ form.tipo_vehiculo || '—' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-white/60 text-[9px] font-black uppercase tracking-wider">Estado</span>
                  <span class="font-black text-white/90 text-[10px]">{{ form.estatus }}</span>
                </div>
                <div v-if="form.precio" class="flex justify-between">
                  <span class="text-white/60 text-[9px] font-black uppercase tracking-wider">Precio</span>
                  <span class="font-black text-white/90 text-[10px]">Q {{ Number(form.precio).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                </div>
              </div>
            </div>
            <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
          </div>

          <!-- Botones -->
          <div class="flex gap-3">
            <button @click="submitForm"
              class="flex-1 bg-primary hover:opacity-90 text-white py-4 rounded-xl text-xs font-black uppercase tracking-widest shadow-2xl transition-all">
              {{ editingId ? 'Guardar Cambios' : 'Registrar Vehículo' }}
            </button>
            <button @click="switchTab('fleet'); resetForm()"
              class="px-5 py-4 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-black text-white/50 transition-all">
              Cancelar
            </button>
          </div>
        </div>
      </div>
    </template>

    <!-- ═══════════════════════════════ MODAL BITÁCORA ═══ -->
    <Transition name="modal">
      <div v-if="showLogModal" class="fixed inset-0 z-50 flex items-center justify-center p-6">
        <div @click="showLogModal = false" class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm cursor-pointer"></div>
        <div class="relative w-full max-w-lg bg-slate-950 border border-white/10 rounded-3xl p-8 shadow-2xl overflow-y-auto max-h-[90vh] text-white z-10">
          <div class="flex items-center justify-between border-b border-white/5 pb-4 mb-6">
            <h4 class="text-sm font-black italic uppercase tracking-tight">Registrar Bitácora</h4>
            <button @click="showLogModal = false" class="p-1.5 hover:bg-white/10 rounded-lg text-white/50 hover:text-white transition-all">
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>

          <form @submit.prevent="submitLog" class="space-y-5">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest">Vehículo <span class="text-rose-400">*</span></label>
              <select v-model="logForm.vehiculo_id" required
                class="w-full bg-slate-900 border border-white/10 rounded-2xl p-4 text-xs font-black text-white focus:outline-none focus:border-primary uppercase">
                <option value="" disabled>Seleccione un vehículo</option>
                <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.placa }} — {{ v.marca }} {{ v.modelo }}</option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="text-[10px] font-black text-white/30 uppercase tracking-widest">Piloto</label>
                <select v-model="logForm.piloto_id"
                  class="w-full bg-slate-900 border border-white/10 rounded-2xl p-4 text-xs font-black text-white focus:outline-none focus:border-primary uppercase">
                  <option value="">Sin asignar</option>
                  <option v-for="p in personnel" :key="p.id" :value="p.id">{{ p.nombres }} {{ p.apellidos }}</option>
                </select>
              </div>
              <div class="space-y-2">
                <label class="text-[10px] font-black text-white/30 uppercase tracking-widest">Estado</label>
                <select v-model="logForm.estatus_vehiculo"
                  class="w-full bg-slate-900 border border-white/10 rounded-2xl p-4 text-xs font-black text-white focus:outline-none focus:border-primary uppercase">
                  <option value="Nuevo">Nuevo</option>
                  <option value="En Funcionamiento">En Funcionamiento</option>
                  <option value="Inactivo">Inactivo</option>
                </select>
              </div>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest">Envío a Servicio</label>
              <textarea v-model="logForm.envio_servicio" rows="2" placeholder="Detalles del servicio o mantenimiento..."
                class="w-full glass-input rounded-2xl p-4 text-xs font-bold placeholder:text-white/20 text-white resize-none"></textarea>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest">Reportar Avería</label>
              <textarea v-model="logForm.reportar_averia" rows="2" placeholder="Descripción de la falla..."
                class="w-full glass-input rounded-2xl p-4 text-xs font-bold placeholder:text-white/20 text-white resize-none"></textarea>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest">Observaciones</label>
              <textarea v-model="logForm.observaciones" rows="2" placeholder="Comentarios adicionales..."
                class="w-full glass-input rounded-2xl p-4 text-xs font-bold placeholder:text-white/20 text-white resize-none"></textarea>
            </div>
            <div class="flex gap-4 pt-4 border-t border-white/5">
              <button type="button" @click="showLogModal = false"
                class="w-1/2 py-3.5 bg-white/5 text-white/60 hover:text-white hover:bg-white/10 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all">
                Cancelar
              </button>
              <button type="submit"
                class="flex-1 py-3 bg-primary text-white font-black uppercase text-xs tracking-wider rounded-2xl shadow-lg hover:opacity-90 transition-all">
                Confirmar
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- ═══════════════════════════════ MODAL DETALLES ═══ -->
    <Transition name="modal">
      <div v-if="showDetailsModal && selectedVehicle" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showDetailsModal = false" class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm cursor-pointer"></div>
        <div class="relative w-full max-w-3xl bg-slate-950 border border-white/10 rounded-3xl p-8 shadow-2xl overflow-y-auto max-h-[90vh] text-white z-10">
          <div class="flex items-center justify-between border-b border-white/5 pb-4 mb-6">
            <h4 class="text-lg font-black italic uppercase flex items-center gap-2">
              <TruckIcon class="w-5 h-5 text-primary" /> {{ selectedVehicle.placa }}
            </h4>
            <button @click="showDetailsModal = false" class="p-1.5 hover:bg-white/10 rounded-lg text-white/50 hover:text-white transition-all">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Info -->
            <div class="space-y-4">
              <h3 class="text-xs font-black uppercase tracking-widest text-primary flex items-center gap-2">
                <InformationCircleIcon class="w-4 h-4" /> Información
              </h3>
              <div class="bg-white/5 p-5 rounded-2xl border border-white/5 space-y-3">
                <div v-for="field in detailFields" :key="field.label">
                  <span class="text-[9px] font-black text-white/30 uppercase tracking-widest block">{{ field.label }}</span>
                  <span class="text-sm font-black text-white uppercase">{{ field.value }}</span>
                </div>
              </div>
            </div>

            <!-- Fotos 2x2 -->
            <div class="space-y-4">
              <h3 class="text-xs font-black uppercase tracking-widest text-primary flex items-center gap-2">
                <CameraIcon class="w-4 h-4" /> Fotografías
              </h3>
              <div class="grid grid-cols-2 gap-3">
                <div v-for="photo in photoFields" :key="photo.key" class="space-y-1">
                  <span class="text-[8px] font-black text-white/30 uppercase tracking-widest block">{{ photo.label }}</span>
                  <div class="aspect-video bg-white/5 rounded-xl border border-white/10 overflow-hidden flex items-center justify-center">
                    <img v-if="selectedVehicle[photo.key]" :src="photoUrl(selectedVehicle[photo.key])" class="w-full h-full object-cover" />
                    <div v-else class="text-center text-white/20 p-2">
                      <CameraIcon class="w-6 h-6 mx-auto mb-1 opacity-50" />
                      <span class="text-[8px] font-black uppercase tracking-widest">Sin foto</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import Swal from 'sweetalert2';
import {
  TruckIcon, PlusIcon, MagnifyingGlassIcon, EyeIcon, PencilIcon, TrashIcon,
  UserIcon, CameraIcon, XMarkIcon, ClipboardDocumentListIcon,
  CheckCircleIcon, SparklesIcon, ExclamationTriangleIcon,
  InformationCircleIcon
} from '@heroicons/vue/24/outline';

const BASE_URL = '/concretos-oriente/Backend/api/v1';

// ── State ──────────────────────────────────────────────────────────────────
const activeTab    = ref('fleet');
const searchTerm   = ref('');
const statusFilter = ref('all');
const vehicles     = ref([]);
const personnel    = ref([]);
const editingId    = ref(null);

const form = ref({
  placa: '', tipo_vehiculo: '', tipo_seguro: '', ubicacion: '',
  estatus: 'Nuevo', precio: '', kilometraje: '', marca: '', modelo: '', piloto_id: ''
});

const photoFields = [
  { key: 'foto_delantera', label: 'Delantera' },
  { key: 'foto_trasera',   label: 'Trasera'   },
  { key: 'foto_lateral1',  label: 'Lateral 1' },
  { key: 'foto_lateral2',  label: 'Lateral 2' },
];

const photoPreviews = ref({ foto_delantera: null, foto_trasera: null, foto_lateral1: null, foto_lateral2: null });
let   photoFiles    = { foto_delantera: null, foto_trasera: null, foto_lateral1: null, foto_lateral2: null };

// Bitácora modal
const showLogModal = ref(false);
const logForm = ref({ vehiculo_id: '', piloto_id: '', estatus_vehiculo: 'En Funcionamiento', envio_servicio: '', reportar_averia: '', observaciones: '' });

// Detalles modal
const showDetailsModal  = ref(false);
const selectedVehicle   = ref(null);

const statusOptions = [
  { value: 'all',              label: 'Todos'            },
  { value: 'En Funcionamiento', label: 'En Funcionamiento' },
  { value: 'Nuevo',            label: 'Nuevo'            },
  { value: 'Inactivo',         label: 'Inactivo'         },
];

// ── Computed ───────────────────────────────────────────────────────────────
const filteredVehicles = computed(() => {
  const q = searchTerm.value.toLowerCase();
  return vehicles.value.filter(v => {
    const matchText = v.placa?.toLowerCase().includes(q) ||
                      v.marca?.toLowerCase().includes(q) ||
                      v.modelo?.toLowerCase().includes(q);
    if (statusFilter.value === 'all') return matchText;
    return matchText && v.estatus === statusFilter.value;
  });
});

const stats = computed(() => ({
  total:            vehicles.value.length,
  enFuncionamiento: vehicles.value.filter(v => v.estatus === 'En Funcionamiento').length,
  nuevo:            vehicles.value.filter(v => v.estatus === 'Nuevo').length,
  inactivo:         vehicles.value.filter(v => v.estatus === 'Inactivo').length,
}));

const detailFields = computed(() => {
  if (!selectedVehicle.value) return [];
  const v = selectedVehicle.value;
  return [
    { label: 'Marca y Modelo',  value: `${v.marca} ${v.modelo}` },
    { label: 'Tipo de Vehículo', value: v.tipo_vehiculo || '—'  },
    { label: 'Tipo de Seguro',   value: v.tipo_seguro  || '—'  },
    { label: 'Ubicación',        value: v.ubicacion    || '—'  },
    { label: 'Estado',           value: v.estatus               },
    { label: 'Precio',           value: v.precio ? `Q ${Number(v.precio).toLocaleString('en-US', { minimumFractionDigits: 2 })}` : '—' },
    { label: 'Kilometraje',      value: `${Number(v.kilometraje).toLocaleString()} km` },
    { label: 'Piloto',           value: v.piloto_nombre || 'Sin asignar' },
  ];
});

// ── Helpers ────────────────────────────────────────────────────────────────
const estatusBadge = (estatus) => {
  if (estatus === 'En Funcionamiento') return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
  if (estatus === 'Nuevo')            return 'bg-blue-500/10 text-blue-400 border-blue-500/20';
  if (estatus === 'Inactivo')         return 'bg-rose-500/10 text-rose-400 border-rose-500/20';
  return 'bg-white/5 text-white/40 border-white/10';
};

const photoUrl = (path) => {
  if (!path) return '';
  if (path.startsWith('http')) return path;
  return `/concretos-oriente/Backend/${path}`;
};

const toast = (msg, icon = 'success') => Swal.fire({
  toast: true, position: 'top-end', icon, title: msg,
  showConfirmButton: false, timer: 4000, timerProgressBar: true,
  background: '#0f172a', color: '#ffffff'
});

// ── Fetch ──────────────────────────────────────────────────────────────────
const fetchVehicles = async () => {
  try {
    const token = localStorage.getItem('token');
    const res   = await fetch(`${BASE_URL}/vehicles`, { headers: { Authorization: `Bearer ${token}` } });
    const data  = await res.json();
    if (data.success) vehicles.value = data.data || [];
  } catch (e) { console.error(e); }
};

const fetchPersonnel = async () => {
  try {
    const token = localStorage.getItem('token');
    const res   = await fetch(`${BASE_URL}/personnel`, { headers: { Authorization: `Bearer ${token}` } });
    const data  = await res.json();
    if (data.status === 'success' || data.success) personnel.value = data.data || [];
  } catch (e) { console.error(e); }
};

onMounted(() => { fetchVehicles(); fetchPersonnel(); });

// ── Form ───────────────────────────────────────────────────────────────────
const resetForm = () => {
  editingId.value = null;
  form.value = { placa: '', tipo_vehiculo: '', tipo_seguro: '', ubicacion: '', estatus: 'Nuevo', precio: '', kilometraje: '', marca: '', modelo: '', piloto_id: '' };
  photoPreviews.value = { foto_delantera: null, foto_trasera: null, foto_lateral1: null, foto_lateral2: null };
  photoFiles = { foto_delantera: null, foto_trasera: null, foto_lateral1: null, foto_lateral2: null };
};

const switchTab = (tab) => { activeTab.value = tab; };

const startEdit = (v) => {
  editingId.value = v.id;
  form.value = {
    placa: v.placa, tipo_vehiculo: v.tipo_vehiculo, tipo_seguro: v.tipo_seguro || '',
    ubicacion: v.ubicacion || '', estatus: v.estatus, precio: v.precio || '',
    kilometraje: v.kilometraje, marca: v.marca, modelo: v.modelo,
    piloto_id: v.piloto_id || ''
  };
  photoPreviews.value = {
    foto_delantera: v.foto_delantera ? photoUrl(v.foto_delantera) : null,
    foto_trasera:   v.foto_trasera   ? photoUrl(v.foto_trasera)   : null,
    foto_lateral1:  v.foto_lateral1  ? photoUrl(v.foto_lateral1)  : null,
    foto_lateral2:  v.foto_lateral2  ? photoUrl(v.foto_lateral2)  : null,
  };
  activeTab.value = 'register';
};

const onPhotoChange = (e, key) => {
  const file = e.target.files?.[0];
  if (!file) return;
  photoFiles[key] = file;
  if (photoPreviews.value[key]) URL.revokeObjectURL(photoPreviews.value[key]);
  photoPreviews.value[key] = URL.createObjectURL(file);
};

const submitForm = async () => {
  if (!form.value.placa.trim() || !form.value.tipo_vehiculo || !form.value.marca.trim() || !form.value.modelo.trim()) {
    toast('Completa los campos obligatorios.', 'warning');
    return;
  }

  try {
    const token = localStorage.getItem('token');
    const fd    = new FormData();

    Object.entries(form.value).forEach(([k, v]) => fd.append(k, v ?? ''));

    photoFields.forEach(({ key }) => {
      if (photoFiles[key]) fd.append(key, photoFiles[key]);
    });

    const url = editingId.value
      ? `${BASE_URL}/vehicles/update/${editingId.value}`
      : `${BASE_URL}/vehicles`;

    const res    = await fetch(url, { method: 'POST', headers: { Authorization: `Bearer ${token}` }, body: fd });
    const result = await res.json();

    if (result.success) {
      toast(editingId.value ? 'Vehículo actualizado correctamente.' : 'Vehículo registrado correctamente.');
      resetForm();
      activeTab.value = 'fleet';
      fetchVehicles();
    } else {
      toast(result.message || 'Error al guardar.', 'error');
    }
  } catch (e) {
    toast('Error de conexión.', 'error');
  }
};

// ── Delete ─────────────────────────────────────────────────────────────────
const deleteVehicle = async (id, placa) => {
  const confirm = await Swal.fire({
    title: '¿Retirar vehículo?',
    text: `¿Estás seguro de retirar el vehículo [${placa}]?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#f43f5e',
    cancelButtonColor: '#475569',
    confirmButtonText: 'Sí, retirar',
    cancelButtonText: 'Cancelar',
    background: '#0f172a',
    color: '#ffffff'
  });

  if (!confirm.isConfirmed) return;

  try {
    const token = localStorage.getItem('token');
    const res   = await fetch(`${BASE_URL}/vehicles/${id}`, { method: 'DELETE', headers: { Authorization: `Bearer ${token}` } });
    const result = await res.json();
    if (result.success) { toast(`Vehículo [${placa}] retirado.`); fetchVehicles(); }
    else toast(result.message || 'Error al retirar.', 'error');
  } catch (e) { toast('Error de conexión.', 'error'); }
};

// ── Detalles ───────────────────────────────────────────────────────────────
const openDetails = (v) => { selectedVehicle.value = v; showDetailsModal.value = true; };

// ── Bitácora ───────────────────────────────────────────────────────────────
const openLogModal = () => {
  logForm.value = { vehiculo_id: '', piloto_id: '', estatus_vehiculo: 'En Funcionamiento', envio_servicio: '', reportar_averia: '', observaciones: '' };
  showLogModal.value = true;
};

watch(() => logForm.value.vehiculo_id, (id) => {
  if (!id) return;
  const v = vehicles.value.find(v => String(v.id) === String(id));
  if (v) {
    logForm.value.piloto_id        = v.piloto_id ? String(v.piloto_id) : '';
    logForm.value.estatus_vehiculo = v.estatus || 'En Funcionamiento';
  }
});

const submitLog = async () => {
  try {
    const token = localStorage.getItem('token');
    const res   = await fetch(`${BASE_URL}/vehicle-logs`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify(logForm.value)
    });
    const result = await res.json();
    if (result.success) {
      toast('Bitácora registrada correctamente.');
      showLogModal.value = false;
      fetchVehicles();
    } else {
      toast(result.message || 'Error al registrar.', 'error');
    }
  } catch (e) { toast('Error de conexión.', 'error'); }
};

// ── Historial ──────────────────────────────────────────────────────────────
const showHistory = (v) => {
  const rows = v.history?.length
    ? v.history.map(h => `<div class="p-3 bg-white/5 border border-white/10 rounded-xl mb-2">
        <div class="flex justify-between mb-1">
          <span class="text-[10px] font-black text-primary uppercase">${h.type}</span>
          <span class="text-[9px] font-mono text-white/40">${h.date}</span>
        </div>
        <p class="text-xs text-white/80">${h.description}</p>
      </div>`).join('')
    : '<p class="text-white/40 text-xs italic">Sin registros aún.</p>';

  Swal.fire({
    title: `<span class="text-white font-black uppercase text-sm tracking-widest">Historial [${v.placa}]</span>`,
    html: `<div class="text-left max-h-64 overflow-y-auto">${rows}</div>`,
    background: '#0f172a',
    showCloseButton: true,
    showConfirmButton: false,
    customClass: { popup: 'rounded-3xl border border-white/10 shadow-2xl', closeButton: 'text-white/50 hover:text-white' }
  });
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
