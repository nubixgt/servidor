<template>
  <div class="pt-20 pb-10 px-4 md:px-10 md:pb-20 max-w-7xl mx-auto space-y-10 relative">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h2 class="text-4xl font-bold tracking-tight text-white mb-2">
          {{ activeTab === 'register' ? (editingId ? 'Modificar Registro' : 'Registrar Combustible') : 'Control de Combustible' }}
        </h2>
        <p class="text-white/60">Registro de consumo de combustible por unidad.</p>
      </div>

      <div class="flex gap-2 bg-black/30 border border-white/10 rounded-2xl p-1 w-fit">
        <button @click="switchTab('list')" :class="['px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all', activeTab === 'list' ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-white/50 hover:text-white']">
          Registros
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
          <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">Total Registros</span>
          <div class="mt-3">
            <h3 class="text-4xl font-black italic text-white tracking-tighter">{{ stats.total }}</h3>
            <p class="text-[10px] font-bold text-white/30 uppercase tracking-wider mt-1">Cargas</p>
          </div>
        </div>
        <div class="p-3 bg-primary/10 border border-primary/20 rounded-2xl text-primary shrink-0">
          <FireIcon class="w-5 h-5" />
        </div>
      </div>

      <div class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-sky-500/50 flex items-start justify-between">
        <div class="flex flex-col justify-between h-full w-full">
          <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">Total Galones</span>
          <div class="mt-3">
            <h3 class="text-4xl font-black italic text-sky-400 tracking-tighter">{{ stats.totalGalones.toFixed(1) }}</h3>
            <p class="text-[10px] font-bold text-sky-400/60 uppercase tracking-wider mt-1">Gal consumidos</p>
          </div>
        </div>
        <div class="p-3 bg-sky-500/10 border border-sky-500/20 rounded-2xl text-sky-400 shrink-0">
          <BeakerIcon class="w-5 h-5" />
        </div>
      </div>

      <div class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-emerald-500/50 flex items-start justify-between">
        <div class="flex flex-col justify-between h-full w-full">
          <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">Total Gasto</span>
          <div class="mt-3">
            <h3 class="text-3xl font-black italic text-emerald-400 tracking-tighter">Q{{ stats.totalMonto.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</h3>
            <p class="text-[10px] font-bold text-emerald-400/60 uppercase tracking-wider mt-1">Invertido</p>
          </div>
        </div>
        <div class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-emerald-400 shrink-0">
          <BanknotesIcon class="w-5 h-5" />
        </div>
      </div>

      <div class="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-amber-500/50 flex items-start justify-between">
        <div class="flex flex-col justify-between h-full w-full">
          <span class="text-[10px] font-black text-white/40 uppercase tracking-widest">Este Mes</span>
          <div class="mt-3">
            <h3 class="text-4xl font-black italic text-amber-400 tracking-tighter">{{ stats.esteMes }}</h3>
            <p class="text-[10px] font-bold text-amber-400/60 uppercase tracking-wider mt-1">Cargas</p>
          </div>
        </div>
        <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-2xl text-amber-400 shrink-0">
          <CalendarDaysIcon class="w-5 h-5" />
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════ LISTADO ═══ -->
    <template v-if="activeTab === 'list'">
      <div class="flex flex-col lg:flex-row gap-4 items-center justify-between border-b border-white/5 pb-6">
        <div class="flex gap-1.5 bg-black/30 border border-white/10 rounded-2xl p-1 overflow-x-auto w-full lg:w-auto">
          <button v-for="f in unitOptions" :key="f.value" @click="unitFilter = f.value"
            :class="['px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap', unitFilter === f.value ? 'bg-primary text-white shadow-lg' : 'text-white/40 hover:text-white']">
            {{ f.label }}
          </button>
        </div>
        <div class="relative w-full lg:w-80">
          <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-white/40" />
          <input v-model="searchTerm" type="text" placeholder="Buscar por placa, piloto o proyecto..."
            class="glass-input pl-10 pr-4 py-3 rounded-xl text-xs font-bold w-full text-white placeholder:text-white/20" />
        </div>
      </div>

      <div class="glass-card rounded-[32px] overflow-hidden border border-white/5">
        <div class="overflow-x-auto">
          <table class="w-full min-w-[800px] text-left">
            <thead>
              <tr class="text-[10px] font-black text-white/30 uppercase tracking-widest border-b border-white/5 bg-white/5">
                <th class="px-6 py-5">Fecha</th>
                <th class="px-6 py-5">Placa / Unidad</th>
                <th class="px-6 py-5">Piloto</th>
                <th class="px-6 py-5">Proyecto</th>
                <th class="px-6 py-5 text-right">Galones</th>
                <th class="px-6 py-5 text-right">Monto</th>
                <th class="px-6 py-5 text-right">KM / HRS</th>
                <th class="px-6 py-5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-if="filteredList.length === 0">
                <td colspan="8" class="px-6 py-16 text-center text-white/30 font-black uppercase tracking-widest text-xs">Sin registros con el filtro aplicado.</td>
              </tr>
              <tr v-else v-for="r in filteredList" :key="r.id" class="hover:bg-white/[0.015] transition-colors">
                <td class="px-6 py-4">
                  <span class="text-xs font-black text-white/80">{{ formatDate(r.fecha) }}</span>
                </td>
                <td class="px-6 py-4">
                  <div>
                    <p class="font-mono text-xs font-black text-primary tracking-widest">{{ r.placa }}</p>
                    <span :class="['text-[9px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded border', unidadBadge(r.tipo_unidad)]">{{ r.tipo_unidad }}</span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span class="text-xs font-bold text-white/80">{{ r.piloto_nombre || '—' }}</span>
                </td>
                <td class="px-6 py-4">
                  <span class="text-xs font-bold text-white/70">{{ r.proyecto_nombre || '—' }}</span>
                </td>
                <td class="px-6 py-4 text-right">
                  <span class="text-xs font-black text-sky-400">{{ Number(r.cantidad_galones).toFixed(2) }} gal</span>
                </td>
                <td class="px-6 py-4 text-right">
                  <span class="text-xs font-black text-emerald-400">Q {{ Number(r.monto).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                </td>
                <td class="px-6 py-4 text-right">
                  <span v-if="r.kilometraje" class="text-xs font-black text-white/70">{{ Number(r.kilometraje).toLocaleString() }} km</span>
                  <span v-else-if="r.horometro" class="text-xs font-black text-white/70">{{ Number(r.horometro).toFixed(1) }} hrs</span>
                  <span v-else class="text-xs text-white/20">—</span>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button @click="openDetails(r)"
                      class="p-2 bg-primary/10 hover:bg-primary/20 text-primary rounded-xl border border-primary/20 transition-all">
                      <EyeIcon class="w-3.5 h-3.5" />
                    </button>
                    <button @click="startEdit(r)"
                      class="p-2 bg-white/5 hover:bg-white/10 text-white/50 hover:text-white rounded-xl border border-white/5 transition-all">
                      <PencilIcon class="w-3.5 h-3.5" />
                    </button>
                    <button @click="deleteRecord(r.id, r.placa, r.fecha)"
                      class="p-2 bg-white/5 hover:bg-white/10 text-white/30 hover:text-rose-400 rounded-xl border border-white/5 transition-all">
                      <TrashIcon class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="px-6 py-4 border-t border-white/5 flex items-center justify-between text-[9px] text-white/30 font-black uppercase tracking-widest">
          <span>{{ filteredList.length }} registros</span>
          <span>Control de Combustible</span>
        </div>
      </div>
    </template>

    <!-- ═══════════════════════════════════════════ FORMULARIO ═══ -->
    <template v-else>
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- Left: campos -->
        <div class="lg:col-span-8 space-y-6">

          <!-- Sección 1: Datos del registro -->
          <section class="glass-card p-8 rounded-3xl border border-white/5 relative">
            <h3 class="text-xs font-black uppercase tracking-widest text-primary mb-6 flex items-center gap-2">
              <InformationCircleIcon class="w-4 h-4" /> Datos del Registro
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

              <!-- Fecha -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Fecha <span class="text-rose-400">*</span></label>
                <input v-model="form.fecha" type="date" required
                  class="w-full h-12 px-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" />
              </div>

              <!-- Piloto -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Piloto</label>
                <select v-model="form.piloto_id"
                  class="w-full h-12 px-4 rounded-xl bg-slate-950/65 border border-white/10 text-sm font-black uppercase text-white focus:outline-none focus:border-primary">
                  <option value="">Sin asignar</option>
                  <option v-for="p in personnel" :key="p.id" :value="p.id">{{ p.nombres }} {{ p.apellidos }}</option>
                </select>
              </div>

              <!-- Placa buscador -->
              <div class="space-y-2 md:col-span-2 relative" ref="placaContainer">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Placa <span class="text-rose-400">*</span></label>
                <div class="relative">
                  <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-white/40 pointer-events-none" />
                  <input v-model="placaSearch" type="text" placeholder="Buscar placa registrada..."
                    @focus="showPlacaDropdown = true"
                    @input="showPlacaDropdown = true"
                    class="w-full h-12 pl-10 pr-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white uppercase tracking-widest" />
                  <!-- Dropdown -->
                  <div v-if="showPlacaDropdown && filteredPlates.length > 0"
                    class="absolute top-full left-0 right-0 mt-1 bg-slate-900 border border-white/10 rounded-2xl shadow-2xl z-30 max-h-48 overflow-y-auto">
                    <button v-for="plate in filteredPlates" :key="plate.placa"
                      @mousedown.prevent="selectPlate(plate)"
                      class="w-full flex items-center justify-between px-4 py-3 hover:bg-white/5 transition-colors border-b border-white/5 last:border-0 text-left">
                      <div>
                        <span class="font-mono text-sm font-black text-primary tracking-widest">{{ plate.placa }}</span>
                        <span class="text-xs text-white/40 ml-2">{{ plate.descripcion }}</span>
                      </div>
                      <span :class="['text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded border', unidadBadge(plate.tipo_unidad)]">
                        {{ plate.tipo_unidad }}
                      </span>
                    </button>
                  </div>
                  <div v-if="showPlacaDropdown && placaSearch && filteredPlates.length === 0"
                    class="absolute top-full left-0 right-0 mt-1 bg-slate-900 border border-white/10 rounded-2xl shadow-2xl z-30 px-4 py-3">
                    <p class="text-xs text-white/40 font-bold">No hay placas registradas con ese texto.</p>
                    <p class="text-[10px] text-white/25 mt-1">Se usará la placa ingresada como "Otro".</p>
                  </div>
                </div>
                <!-- Placa seleccionada -->
                <div v-if="form.placa" class="flex items-center gap-2 mt-2">
                  <span class="font-mono text-xs font-black text-primary bg-primary/10 border border-primary/20 px-3 py-1 rounded-lg tracking-widest">{{ form.placa }}</span>
                  <span :class="['text-[9px] font-black uppercase tracking-widest px-2 py-1 rounded border', unidadBadge(form.tipo_unidad)]">{{ form.tipo_unidad }}</span>
                  <button @click="clearPlaca" class="text-white/30 hover:text-rose-400 transition-colors text-[10px] font-black">✕ limpiar</button>
                </div>
              </div>

              <!-- Proyecto -->
              <div class="space-y-2 md:col-span-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Proyecto</label>
                <select v-model="form.proyecto_id"
                  class="w-full h-12 px-4 rounded-xl bg-slate-950/65 border border-white/10 text-sm font-black uppercase text-white focus:outline-none focus:border-primary">
                  <option value="">Sin proyecto</option>
                  <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.nombre }}</option>
                </select>
              </div>

              <!-- Galones -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Cantidad Galones <span class="text-rose-400">*</span></label>
                <div class="relative">
                  <input v-model="form.cantidad_galones" type="number" min="0" step="0.01" required placeholder="0.00"
                    class="w-full h-12 pl-4 pr-12 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" />
                  <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-white/40 tracking-widest">GAL</span>
                </div>
              </div>

              <!-- Monto -->
              <div class="space-y-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Monto <span class="text-rose-400">*</span></label>
                <div class="relative">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-black text-white/40">Q</span>
                  <input v-model="form.monto" type="number" min="0" step="0.01" required placeholder="0.00"
                    class="w-full h-12 pl-8 pr-4 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" />
                </div>
              </div>

              <!-- Kilometraje (solo Vehiculo / Transporte Pesado) -->
              <div v-if="showKilometraje" class="space-y-2 md:col-span-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Kilometraje</label>
                <div class="relative">
                  <input v-model="form.kilometraje" type="number" min="0" step="0.01" placeholder="0"
                    class="w-full h-12 pl-4 pr-12 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" />
                  <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-white/40 tracking-widest">KM</span>
                </div>
              </div>

              <!-- Horómetro (solo Maquinaria) -->
              <div v-if="showHorometro" class="space-y-2 md:col-span-2">
                <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Horómetro</label>
                <div class="relative">
                  <input v-model="form.horometro" type="number" min="0" step="0.1" placeholder="0.0"
                    class="w-full h-12 pl-4 pr-12 rounded-xl glass-input border-white/5 focus:border-primary transition-all text-sm font-black text-white" />
                  <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-white/40 tracking-widest">HRS</span>
                </div>
              </div>

            </div>
          </section>
        </div>

        <!-- Right: fotos + preview + botones -->
        <div class="lg:col-span-4 space-y-6">

          <!-- Fotos -->
          <section class="glass-card p-6 rounded-3xl border border-white/5">
            <h3 class="text-xs font-black uppercase tracking-widest text-primary mb-5 flex items-center gap-2">
              <CameraIcon class="w-4 h-4" /> Comprobante Fotográfico <span class="text-white/30 font-normal normal-case tracking-normal text-[10px]">(máx. 2)</span>
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
                  <span class="text-white/60 text-[9px] font-black uppercase tracking-wider">Fecha</span>
                  <span class="font-black text-white/95 text-[10px]">{{ form.fecha || '—' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-white/60 text-[9px] font-black uppercase tracking-wider">Galones</span>
                  <span class="font-black text-white/90 text-[10px]">{{ form.cantidad_galones || '0' }} gal</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-white/60 text-[9px] font-black uppercase tracking-wider">Monto</span>
                  <span class="font-black text-white/90 text-[10px]">Q {{ form.monto || '0.00' }}</span>
                </div>
              </div>
            </div>
            <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
          </div>

          <!-- Botones -->
          <div class="flex gap-3">
            <button @click="submitForm"
              class="flex-1 bg-primary hover:opacity-90 text-white py-4 rounded-xl text-xs font-black uppercase tracking-widest shadow-2xl transition-all">
              {{ editingId ? 'Guardar Cambios' : 'Registrar' }}
            </button>
            <button @click="switchTab('list'); resetForm()"
              class="px-5 py-4 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-black text-white/50 transition-all">
              Cancelar
            </button>
          </div>
        </div>
      </div>
    </template>

    <!-- ═══════════════════════════════ MODAL DETALLES ═══ -->
    <Transition name="modal">
      <div v-if="showDetailsModal && selectedRecord" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="showDetailsModal = false" class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm cursor-pointer"></div>
        <div class="relative w-full max-w-2xl bg-slate-950 border border-white/10 rounded-3xl p-8 shadow-2xl overflow-y-auto max-h-[90vh] text-white z-10">
          <div class="flex items-center justify-between border-b border-white/5 pb-4 mb-6">
            <h4 class="text-lg font-black italic uppercase flex items-center gap-2">
              <FireIcon class="w-5 h-5 text-primary" />
              <span class="font-mono text-primary">{{ selectedRecord.placa }}</span>
              <span class="text-white/50 text-sm font-normal">{{ formatDate(selectedRecord.fecha) }}</span>
            </h4>
            <button @click="showDetailsModal = false" class="p-1.5 hover:bg-white/10 rounded-lg text-white/50 hover:text-white transition-all">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
              <div class="bg-white/5 p-5 rounded-2xl border border-white/5 space-y-3">
                <div v-for="field in detailFields" :key="field.label">
                  <span class="text-[9px] font-black text-white/30 uppercase tracking-widest block">{{ field.label }}</span>
                  <span class="text-sm font-black text-white">{{ field.value }}</span>
                </div>
              </div>
            </div>

            <div class="space-y-4">
              <h3 class="text-xs font-black uppercase tracking-widest text-primary flex items-center gap-2">
                <CameraIcon class="w-4 h-4" /> Comprobantes
              </h3>
              <div class="grid grid-cols-2 gap-3">
                <div v-for="photo in photoFields" :key="photo.key" class="space-y-1">
                  <span class="text-[8px] font-black text-white/30 uppercase tracking-widest block">{{ photo.label }}</span>
                  <div class="aspect-video bg-white/5 rounded-xl border border-white/10 overflow-hidden flex items-center justify-center">
                    <img v-if="selectedRecord[photo.key]" :src="photoUrl(selectedRecord[photo.key])" class="w-full h-full object-cover cursor-pointer" />
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
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import Swal from 'sweetalert2';
import {
  PlusIcon, MagnifyingGlassIcon, EyeIcon, PencilIcon, TrashIcon,
  CameraIcon, XMarkIcon, FireIcon, InformationCircleIcon,
  BeakerIcon, BanknotesIcon, CalendarDaysIcon
} from '@heroicons/vue/24/outline';

const BASE_URL = '/concretos-oriente/Backend/api/v1';

// ── State ──────────────────────────────────────────────────────────────────
const activeTab  = ref('list');
const searchTerm = ref('');
const unitFilter = ref('all');
const records    = ref([]);
const personnel  = ref([]);
const projects   = ref([]);
const allPlates  = ref([]);
const editingId  = ref(null);

// Placa buscador
const placaSearch       = ref('');
const showPlacaDropdown = ref(false);
const placaContainer    = ref(null);

const form = ref({
  fecha: new Date().toISOString().split('T')[0],
  piloto_id: '', placa: '', tipo_unidad: 'Vehiculo',
  proyecto_id: '', cantidad_galones: '', monto: '',
  kilometraje: '', horometro: ''
});

const photoFields = [
  { key: 'foto_1', label: 'Foto 1' },
  { key: 'foto_2', label: 'Foto 2' },
];

const photoPreviews = ref({ foto_1: null, foto_2: null });
let photoFiles = { foto_1: null, foto_2: null };

const showDetailsModal = ref(false);
const selectedRecord   = ref(null);

const unitOptions = [
  { value: 'all',              label: 'Todos'             },
  { value: 'Vehiculo',         label: 'Vehículo'          },
  { value: 'Transporte Pesado', label: 'Transporte Pesado' },
  { value: 'Maquinaria',       label: 'Maquinaria'        },
  { value: 'Otro',             label: 'Otro'              },
];

// ── Computed ───────────────────────────────────────────────────────────────
const filteredList = computed(() => {
  const q = searchTerm.value.toLowerCase();
  return records.value.filter(r => {
    const matchText = r.placa?.toLowerCase().includes(q) ||
                      r.piloto_nombre?.toLowerCase().includes(q) ||
                      r.proyecto_nombre?.toLowerCase().includes(q);
    if (unitFilter.value === 'all') return matchText;
    return matchText && r.tipo_unidad === unitFilter.value;
  });
});

const filteredPlates = computed(() => {
  if (!placaSearch.value) return allPlates.value.slice(0, 10);
  const q = placaSearch.value.toLowerCase();
  return allPlates.value.filter(p =>
    p.placa?.toLowerCase().includes(q) ||
    p.descripcion?.toLowerCase().includes(q)
  ).slice(0, 10);
});

const stats = computed(() => {
  const now = new Date();
  const mes = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
  return {
    total:        records.value.length,
    totalGalones: records.value.reduce((s, r) => s + parseFloat(r.cantidad_galones || 0), 0),
    totalMonto:   records.value.reduce((s, r) => s + parseFloat(r.monto || 0), 0),
    esteMes:      records.value.filter(r => r.fecha?.startsWith(mes)).length,
  };
});

const showKilometraje = computed(() =>
  form.value.tipo_unidad === 'Vehiculo' || form.value.tipo_unidad === 'Transporte Pesado'
);

const showHorometro = computed(() => form.value.tipo_unidad === 'Maquinaria');

const detailFields = computed(() => {
  if (!selectedRecord.value) return [];
  const r = selectedRecord.value;
  const fields = [
    { label: 'Fecha',    value: formatDate(r.fecha) },
    { label: 'Placa',    value: r.placa },
    { label: 'Unidad',   value: r.tipo_unidad },
    { label: 'Piloto',   value: r.piloto_nombre || '—' },
    { label: 'Proyecto', value: r.proyecto_nombre || '—' },
    { label: 'Galones',  value: `${Number(r.cantidad_galones).toFixed(2)} gal` },
    { label: 'Monto',    value: `Q ${Number(r.monto).toLocaleString('en-US', { minimumFractionDigits: 2 })}` },
  ];
  if (r.kilometraje) fields.push({ label: 'Kilometraje', value: `${Number(r.kilometraje).toLocaleString()} km` });
  if (r.horometro)   fields.push({ label: 'Horómetro',   value: `${Number(r.horometro).toFixed(1)} hrs` });
  return fields;
});

// ── Helpers ────────────────────────────────────────────────────────────────
const unidadBadge = (tipo) => {
  if (tipo === 'Vehiculo')          return 'bg-blue-500/10 text-blue-400 border-blue-500/20';
  if (tipo === 'Transporte Pesado') return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
  if (tipo === 'Maquinaria')        return 'bg-violet-500/10 text-violet-400 border-violet-500/20';
  return 'bg-white/5 text-white/40 border-white/10';
};

const photoUrl = (path) => {
  if (!path) return '';
  if (path.startsWith('http')) return path;
  return `/concretos-oriente/Backend/${path}?t=${Date.now()}`;
};

const formatDate = (val) => {
  if (!val) return '—';
  const [y, m, d] = val.split('-');
  return `${d}/${m}/${y}`;
};

const toast = (msg, icon = 'success') => Swal.fire({
  toast: true, position: 'top-end', icon, title: msg,
  showConfirmButton: false, timer: 4000, timerProgressBar: true,
  background: '#0f172a', color: '#ffffff'
});

// ── Placa dropdown ─────────────────────────────────────────────────────────
const selectPlate = (plate) => {
  form.value.placa      = plate.placa;
  form.value.tipo_unidad = plate.tipo_unidad;
  placaSearch.value      = plate.placa;
  showPlacaDropdown.value = false;
  // limpiar campos condicionales al cambiar tipo
  form.value.kilometraje = '';
  form.value.horometro   = '';
};

const clearPlaca = () => {
  form.value.placa      = '';
  form.value.tipo_unidad = 'Vehiculo';
  placaSearch.value      = '';
  form.value.kilometraje = '';
  form.value.horometro   = '';
};

const onClickOutside = (e) => {
  if (placaContainer.value && !placaContainer.value.contains(e.target)) {
    showPlacaDropdown.value = false;
    // Si escribió algo que no está en la lista, lo usa como placa libre
    if (placaSearch.value && !form.value.placa) {
      form.value.placa      = placaSearch.value.toUpperCase();
      form.value.tipo_unidad = 'Otro';
    }
  }
};

// ── Fetch ──────────────────────────────────────────────────────────────────
const fetchRecords = async () => {
  try {
    const token = localStorage.getItem('token');
    const res   = await fetch(`${BASE_URL}/fuel-records`, { headers: { Authorization: `Bearer ${token}` } });
    const data  = await res.json();
    if (data.success) records.value = data.data || [];
  } catch (e) { console.error(e); }
};

const fetchPlates = async () => {
  try {
    const token = localStorage.getItem('token');
    const res   = await fetch(`${BASE_URL}/fuel-records/plates`, { headers: { Authorization: `Bearer ${token}` } });
    const data  = await res.json();
    if (data.success) allPlates.value = data.data || [];
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

const fetchProjects = async () => {
  try {
    const token = localStorage.getItem('token');
    const res   = await fetch(`${BASE_URL}/projects`, { headers: { Authorization: `Bearer ${token}` } });
    const data  = await res.json();
    if (data.status === 'success' || data.success) projects.value = data.data || [];
  } catch (e) { console.error(e); }
};

onMounted(() => {
  fetchRecords();
  fetchPlates();
  fetchPersonnel();
  fetchProjects();
  document.addEventListener('click', onClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onClickOutside);
});

// ── Form ───────────────────────────────────────────────────────────────────
const resetForm = () => {
  editingId.value = null;
  form.value = {
    fecha: new Date().toISOString().split('T')[0],
    piloto_id: '', placa: '', tipo_unidad: 'Vehiculo',
    proyecto_id: '', cantidad_galones: '', monto: '',
    kilometraje: '', horometro: ''
  };
  placaSearch.value = '';
  photoPreviews.value = { foto_1: null, foto_2: null };
  photoFiles = { foto_1: null, foto_2: null };
};

const switchTab = (tab) => { activeTab.value = tab; };

const startEdit = (r) => {
  editingId.value = r.id;
  form.value = {
    fecha:            r.fecha,
    piloto_id:        r.piloto_id || '',
    placa:            r.placa,
    tipo_unidad:      r.tipo_unidad,
    proyecto_id:      r.proyecto_id || '',
    cantidad_galones: r.cantidad_galones,
    monto:            r.monto,
    kilometraje:      r.kilometraje || '',
    horometro:        r.horometro || '',
  };
  placaSearch.value = r.placa;
  photoFiles = { foto_1: null, foto_2: null };
  photoPreviews.value = {
    foto_1: r.foto_1 ? photoUrl(r.foto_1) : null,
    foto_2: r.foto_2 ? photoUrl(r.foto_2) : null,
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
  if (!form.value.fecha || !form.value.placa || !form.value.cantidad_galones || !form.value.monto) {
    toast('Fecha, placa, galones y monto son obligatorios.', 'warning');
    return;
  }

  // Si dejó texto en el buscador sin seleccionar, úsalo como placa libre
  if (!form.value.placa && placaSearch.value) {
    form.value.placa      = placaSearch.value.toUpperCase();
    form.value.tipo_unidad = 'Otro';
  }

  try {
    const token = localStorage.getItem('token');
    const fd    = new FormData();

    Object.entries(form.value).forEach(([k, v]) => fd.append(k, v ?? ''));
    photoFields.forEach(({ key }) => {
      if (photoFiles[key]) fd.append(key, photoFiles[key]);
    });

    const url = editingId.value
      ? `${BASE_URL}/fuel-records/update/${editingId.value}`
      : `${BASE_URL}/fuel-records`;

    const res    = await fetch(url, { method: 'POST', headers: { Authorization: `Bearer ${token}` }, body: fd });
    const result = await res.json();

    if (result.success) {
      toast(editingId.value ? 'Registro actualizado.' : 'Combustible registrado correctamente.');
      resetForm();
      activeTab.value = 'list';
      fetchRecords();
    } else {
      toast(result.message || 'Error al guardar.', 'error');
    }
  } catch (e) {
    toast('Error de conexión.', 'error');
  }
};

// ── Delete ─────────────────────────────────────────────────────────────────
const deleteRecord = async (id, placa, fecha) => {
  const confirm = await Swal.fire({
    title: '¿Eliminar registro?',
    text: `Placa ${placa} · ${formatDate(fecha)}`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#f43f5e',
    cancelButtonColor: '#475569',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    background: '#0f172a',
    color: '#ffffff'
  });

  if (!confirm.isConfirmed) return;

  try {
    const token  = localStorage.getItem('token');
    const res    = await fetch(`${BASE_URL}/fuel-records/${id}`, { method: 'DELETE', headers: { Authorization: `Bearer ${token}` } });
    const result = await res.json();
    if (result.success) { toast('Registro eliminado.'); fetchRecords(); }
    else toast(result.message || 'Error al eliminar.', 'error');
  } catch (e) { toast('Error de conexión.', 'error'); }
};

// ── Detalles ───────────────────────────────────────────────────────────────
const openDetails = (r) => { selectedRecord.value = r; showDetailsModal.value = true; };
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
