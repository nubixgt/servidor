<template>
  <div class="pt-20 pb-10 px-4 md:px-10 md:pb-20 max-w-7xl mx-auto space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Control de Inventario</h2>
        <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Gestión centralizada de suministros y materiales</p>
      </div>
      <div class="flex gap-4">
        <button @click="openItemModal" class="glass-button-primary text-white py-4 px-8 rounded-2xl font-black text-xs uppercase tracking-widest flex items-center justify-center gap-3 shadow-2xl shadow-primary/20 hover:scale-105 transition-all">
          <PlusIcon class="w-5 h-5" />
          Nuevo Ítem
        </button>
        <button @click="openKardexModal" class="glass-button-primary text-white py-4 px-8 rounded-2xl font-black text-xs uppercase tracking-widest flex items-center justify-center gap-3 shadow-2xl shadow-primary/20 hover:scale-105 transition-all">
          <ArrowsRightLeftIcon class="w-5 h-5" />
          Movimiento
        </button>
      </div>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <div
        v-for="(m, i) in metrics"
        :key="m.label"
        @click="m.isCritical && (filterCritical = !filterCritical)"
        :class="[
          'glass-card p-10 rounded-[40px] border group relative overflow-hidden transition-all duration-500 hover:-translate-y-3 hover:scale-105 hover:shadow-[0_20px_40px_-15px_rgba(99,102,241,0.5)]',
          m.isCritical && filterCritical ? 'border-tertiary/50' : 'border-white/5',
          m.isCritical ? 'cursor-pointer' : ''
        ]"
      >
        <div :class="`absolute top-0 right-0 w-32 h-32 bg-${m.color}/10 blur-[60px] rounded-full translate-x-10 -translate-y-10 group-hover:bg-${m.color}/20 transition-all`"></div>
        <div class="relative z-10 flex items-start justify-between">
          <div>
            <p class="text-[10px] font-black text-white/30 uppercase tracking-[0.3em] mb-4">{{ m.label }}</p>
            <h3 class="text-4xl font-black text-white italic tracking-tighter">{{ m.value }}</h3>
            <div class="flex items-center gap-2 mt-4">
              <span :class="`text-[10px] font-black px-2 py-1 rounded-lg ${m.color === 'primary' ? 'bg-primary/20 text-primary' : 'bg-tertiary/20 text-tertiary'}`">
                {{ m.isCritical && filterCritical ? 'Filtrando en alerta' : m.change }}
              </span>
            </div>
          </div>
          <div :class="`w-14 h-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-${m.color === 'primary' ? 'primary' : 'tertiary'} shadow-2xl`">
            <component :is="m.icon" class="w-7 h-7" />
          </div>
        </div>
      </div>
    </div>

    <!-- Main Inventory Section -->
    <section class="glass-card rounded-[56px] overflow-hidden border border-white/5 shadow-2xl transition-all duration-500" data-aos="zoom-in-up" data-aos-duration="1000">
      <div class="p-12 border-b border-white/5 bg-white/5 backdrop-blur-3xl flex flex-col md:flex-row md:items-center justify-between gap-8">
        <div class="relative flex-1 max-w-lg">
          <MagnifyingGlassIcon class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-white/30" />
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Buscar por código, nombre o categoría..." 
            class="w-full glass-input rounded-2xl pl-14 pr-6 py-4 text-sm font-medium text-white outline-none focus:ring-2 focus:ring-primary/40 transition-all"
          />
        </div>
        <div class="flex items-center gap-4">
          <select v-model="filterProject" class="bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none w-full md:w-auto md:min-w-[200px]">
            <option value="">Todos los Proyectos</option>
            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.nombre }}</option>
          </select>
          <select v-model="filterType" class="bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none w-full md:w-auto md:min-w-[200px]">
            <option value="">Todos los Tipos</option>
            <option value="Material">Material</option>
            <option value="Repuesto">Repuesto</option>
            <option value="Herramienta">Herramienta</option>
            <option value="Consumible">Consumible</option>
          </select>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full min-w-[640px] text-left">
          <thead>
            <tr class="text-[10px] font-black text-white/20 uppercase tracking-[0.3em]">
              <th class="px-12 py-8">Recurso / Código</th>
              <th class="px-12 py-8">Categoría</th>
              <th class="px-12 py-8">Disponibilidad</th>
              <th class="px-12 py-8 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-if="loading">
              <td colspan="5" class="px-12 py-10 text-center text-white/40">Cargando catálogo...</td>
            </tr>
            <tr v-else-if="paginatedItems.length === 0">
              <td colspan="5" class="px-12 py-10 text-center text-white/40">No se encontraron ítems.</td>
            </tr>
            <tr 
              v-for="(item, i) in paginatedItems" 
              :key="i" 
              class="hover:bg-white/5 transition-all cursor-pointer group"
            >
              <td class="px-12 py-10" @click="openItemDetails(item)">
                <div class="flex items-center gap-5">
                  <div class="w-12 h-12 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                    <CubeIcon class="w-6 h-6" />
                  </div>
                  <div>
                    <p class="font-black text-lg text-white italic tracking-tighter uppercase">{{ item.nombre }}</p>
                    <p class="text-[10px] font-bold text-white/30 uppercase tracking-[0.2em] mt-1">Cód: {{ item.codigo_sku || 'N/A' }}</p>
                  </div>
                </div>
              </td>
              <td class="px-12 py-10" @click="openItemDetails(item)">
                <span class="text-xs font-bold text-white/40 uppercase tracking-widest">{{ item.tipo_item }}</span>
              </td>
              <td class="px-12 py-10" @click="openItemDetails(item)">
                <div class="space-y-3">
                  <div class="flex items-center justify-between gap-10">
                    <span class="text-sm font-black text-white italic">{{ Number(item.stock_actual).toFixed(2) }} {{ item.unidad_medida }}</span>
                    <span :class="`text-[10px] font-black uppercase tracking-widest ${getStockColor(item)}`">
                      {{ getStockStatus(item) }}
                    </span>
                  </div>
                  <div class="w-32 h-1.5 bg-white/5 rounded-full overflow-hidden">
                    <div 
                      :class="`h-full ${getStockBgColor(item)} shadow-[0_0_10px_currentColor]`"
                      :style="{ width: `${getStockPercentage(item)}%` }"
                    ></div>
                  </div>
                </div>
              </td>
              <td class="px-12 py-10 text-right">
                <div class="flex justify-end gap-2">
                  <button @click.stop="openEditItem(item)" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all" title="Editar">
                    <PencilIcon class="w-5 h-5" />
                  </button>
                  <button @click.stop="deleteItem(item.id)" class="p-2 text-white/40 hover:text-tertiary hover:bg-white/10 rounded-xl transition-all" title="Eliminar">
                    <TrashIcon class="w-5 h-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex justify-between items-center px-12 py-8 border-t border-white/5 bg-black/20">
        <p class="text-xs text-white/40 font-semibold tracking-widest">
          Página <span class="text-white">{{ currentPage }}</span> de <span class="text-white">{{ totalPages }}</span>
        </p>
        <div class="flex gap-2">
          <button @click="currentPage--" :disabled="currentPage === 1" class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-white/40 hover:bg-white/10 hover:text-white disabled:opacity-30 transition-all">
            <ChevronLeftIcon class="w-5 h-5" />
          </button>
          <button @click="currentPage++" :disabled="currentPage === totalPages" class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-white/40 hover:bg-white/10 hover:text-white disabled:opacity-30 transition-all">
            <ChevronRightIcon class="w-5 h-5" />
          </button>
        </div>
      </div>
    </section>

    <!-- ITEM DETAIL MODAL -->
    <transition name="fade">
      <div v-if="selectedItemDetails" class="fixed inset-0 z-50 flex items-center justify-center p-6">
        <div @click="selectedItemDetails = null" class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-4xl glass-card rounded-[56px] overflow-hidden border border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.5)] bg-slate-950" data-aos="zoom-in-up" data-aos-duration="1000">
          <div class="flex flex-col lg:flex-row h-full max-h-[85vh]">
            <div class="flex-1 p-12 overflow-y-auto custom-scrollbar">
              <div class="flex justify-between items-start mb-12">
                <div>
                  <span class="text-[10px] font-black uppercase tracking-[0.4em] text-primary mb-3 block">Detalle de Ítem</span>
                  <h2 class="text-5xl font-black text-white italic uppercase tracking-tighter">{{ selectedItemDetails.nombre }}</h2>
                  <p class="text-white/40 font-bold uppercase tracking-widest mt-2">{{ selectedItemDetails.codigo_sku || 'Sin Código' }} • {{ selectedItemDetails.tipo_item }}</p>
                </div>
                <button @click="selectedItemDetails = null" class="w-14 h-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-white/40 hover:text-white">
                  <XMarkIcon class="w-8 h-8" />
                </button>
              </div>

              <div class="grid grid-cols-2 gap-8 mb-12">
                <div class="glass-card p-8 rounded-[32px] border border-white/5" data-aos="zoom-in-up" data-aos-duration="1000">
                  <p class="text-[10px] font-black uppercase tracking-widest text-white/20 mb-4 flex items-center gap-2">
                    <Square3Stack3DIcon class="w-4 h-4" /> Existencia Actual
                  </p>
                  <h4 class="text-4xl font-black text-white italic">{{ Number(selectedItemDetails.stock_actual).toFixed(2) }} <span class="text-xl">{{ selectedItemDetails.unidad_medida }}</span></h4>
                </div>
                <div class="glass-card p-8 rounded-[32px] border border-white/5" data-aos="zoom-in-up" data-aos-duration="1000">
                  <p class="text-[10px] font-black uppercase tracking-widest text-white/20 mb-4 flex items-center gap-2">
                    <ChartBarIcon class="w-4 h-4" /> Salud de Stock
                  </p>
                  <div class="flex items-center gap-3">
                    <div :class="`w-4 h-4 rounded-full ${getStockBgColor(selectedItemDetails)} shadow-[0_0_10px_currentColor]`"></div>
                    <span class="text-2xl font-black text-white italic uppercase tracking-tight">{{ getStockStatus(selectedItemDetails) }}</span>
                  </div>
                </div>
              </div>

              <div class="space-y-10">
                <div>
                  <h5 class="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-6 flex items-center gap-3">
                    <div class="w-8 h-[1px] bg-white/10"></div> Historial de Movimientos
                  </h5>
                  <div class="space-y-4">
                    <div v-if="loadingKardex" class="text-white/40 text-sm">Cargando movimientos...</div>
                    <div v-else-if="itemKardex.length === 0" class="text-white/40 text-sm">Sin movimientos registrados.</div>
                    <div v-for="log in itemKardex" :key="log.id" class="py-5 border-b border-white/5 group space-y-3">
                      <div class="flex items-center justify-between">
                        <div class="flex items-center gap-5">
                          <div :class="`w-10 h-10 rounded-xl flex items-center justify-center ${getKardexIconBg(log.tipo_movimiento)}`">
                            <component :is="getKardexIcon(log.tipo_movimiento)" class="w-5 h-5" />
                          </div>
                          <div>
                            <p class="text-sm font-bold text-white uppercase italic">{{ log.tipo_movimiento }} <span v-if="log.proyecto_destino">- a {{log.proyecto_destino}}</span></p>
                            <p class="text-[10px] font-black text-white/20 uppercase tracking-widest">{{ new Date(log.fecha_movimiento).toLocaleDateString() }} • Ref: {{ log.referencia_documento || 'N/A' }}</p>
                          </div>
                        </div>
                        <span :class="`text-lg font-black italic tracking-tighter ${getKardexQtyColor(log.tipo_movimiento)}`">
                          {{ getKardexSign(log.tipo_movimiento) }}{{ Number(log.cantidad).toFixed(2) }} {{ selectedItemDetails.unidad_medida }}
                        </span>
                      </div>
                      <div v-if="parseFotos(log.fotos).length > 0" class="flex gap-2 pl-15 flex-wrap">
                        <a
                          v-for="(foto, fi) in parseFotos(log.fotos)"
                          :key="fi"
                          :href="`/concretos-oriente/Backend/${foto}`"
                          target="_blank"
                          class="block"
                        >
                          <img
                            :src="`/concretos-oriente/Backend/${foto}`"
                            class="w-16 h-16 rounded-xl object-cover border border-white/10 hover:border-primary/40 transition-all"
                            :alt="`Foto ${fi + 1}`"
                          />
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="lg:w-1/3 bg-white/[0.02] border-l border-white/5 p-12 overflow-y-auto">
              <div class="space-y-12">
                <div>
                  <h5 class="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-6">Información General</h5>
                  <div class="space-y-6">
                    <div class="space-y-2">
                      <p class="text-[9px] font-black text-white/30 uppercase tracking-[0.2em]">Stock Mínimo</p>
                      <p class="text-sm font-black text-white uppercase italic">{{ selectedItemDetails.stock_minimo }}</p>
                    </div>
                    <div class="space-y-2">
                      <p class="text-[9px] font-black text-white/30 uppercase tracking-[0.2em]">Descripción</p>
                      <p class="text-sm text-white/60">{{ selectedItemDetails.descripcion || 'Sin descripción' }}</p>
                    </div>
                  </div>
                </div>

                <div class="flex flex-col gap-4">
                  <button @click="openKardexModalForItem(selectedItemDetails)" class="w-full glass-button-primary py-5 rounded-2xl font-black text-sm uppercase tracking-widest shadow-2xl shadow-primary/20">
                    Nuevo Movimiento
                  </button>
                  <button @click="openEditItem(selectedItemDetails)" class="w-full py-5 rounded-2xl bg-white/5 border border-white/10 text-white/40 font-black text-sm uppercase tracking-widest hover:text-white transition-all">
                    Editar Información
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- CREATE/EDIT ITEM MODAL -->
    <div v-if="showItemModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeItemModal"></div>
      <div class="glass-card w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-[32px] p-4 md:p-8 relative z-10 border border-white/10 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-2xl font-bold text-white">{{ isEditing ? 'Editar Ítem del Catálogo' : 'Nuevo Ítem del Catálogo' }}</h3>
          <button @click="closeItemModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all">
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <form @submit.prevent="submitItem" class="space-y-8">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <div class="space-y-2 lg:col-span-1">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Tipo de Ítem *</label>
              <select v-model="formItem.tipo_item" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none">
                <option value="Material">Material</option>
                <option value="Repuesto">Repuesto</option>
                <option value="Herramienta">Herramienta</option>
                <option value="Consumible">Consumible</option>
              </select>
            </div>
            <div class="space-y-2 lg:col-span-1">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Código</label>
              <input v-model="formItem.codigo_sku" type="text" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50" />
            </div>
            <div class="space-y-2 lg:col-span-1">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Unidad de Medida *</label>
              <input v-model="formItem.unidad_medida" type="text" required placeholder="ej. m2, unidad, litros" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50" />
            </div>
            
            <div class="space-y-2 lg:col-span-3">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Nombre del Ítem *</label>
              <input v-model="formItem.nombre" type="text" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50" />
            </div>

            <div class="space-y-2 lg:col-span-3">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Descripción</label>
              <textarea v-model="formItem.descripcion" rows="2" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50"></textarea>
            </div>

            <div class="space-y-2 lg:col-span-1">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Stock Mínimo (Alerta)</label>
              <input v-model="formItem.stock_minimo" type="number" step="0.01" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50" />
            </div>
          </div>

          <div class="pt-4 flex justify-end gap-4 border-t border-white/5">
            <button type="button" @click="closeItemModal" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">Cancelar</button>
            <button type="submit" :disabled="isSubmitting" class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 disabled:opacity-50 transition-all">
              {{ isEditing ? 'Actualizar' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- KARDEX MOVEMENT MODAL -->
    <div v-if="showKardexModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeKardexModal"></div>
      <div class="glass-card w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-[32px] p-4 md:p-8 relative z-10 border border-white/10 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-2xl font-bold text-white">Registrar Movimiento (Kardex)</h3>
          <button @click="closeKardexModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all">
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <form @submit.prevent="submitKardex" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Tipo de Movimiento *</label>
              <select v-model="formKardex.tipo_movimiento" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none">
                <option value="Entrada">Entrada (Agrega a bodega)</option>
                <option value="Dar de Baja">Dar de Baja (Resta de bodega)</option>
                <option value="Traslado">Traslado a Proyecto (Resta de bodega)</option>
              </select>
            </div>
            
            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Ítem del Catálogo *</label>
              <select v-model="formKardex.item_id" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none">
                <option value="" disabled>Seleccione un ítem...</option>
                <option v-for="item in items" :key="item.id" :value="item.id">{{ item.codigo_sku }} - {{ item.nombre }}</option>
              </select>
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Cantidad *</label>
              <input v-model="formKardex.cantidad" type="number" step="0.01" min="0.01" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50" />
            </div>
            
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Fecha del Movimiento *</label>
              <input v-model="formKardex.fecha_movimiento" type="datetime-local" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50" />
            </div>

            <div v-if="formKardex.tipo_movimiento === 'Traslado'" class="space-y-2 md:col-span-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Proyecto Destino *</label>
              <select v-model="formKardex.proyecto_destino_id" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none">
                <option value="" disabled>Seleccione proyecto...</option>
                <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.nombre }}</option>
              </select>
            </div>

            <div v-if="formKardex.tipo_movimiento === 'Entrada'" class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Costo Unitario (Opcional Q)</label>
              <input v-model="formKardex.costo_unitario" type="number" step="0.01" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50" />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Referencia (Factura, OC)</label>
              <input v-model="formKardex.referencia_documento" type="text" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50" />
            </div>

            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Notas u Observaciones</label>
              <textarea v-model="formKardex.notas" rows="2" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50"></textarea>
            </div>

            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Fotos (Máx. 2)</label>
              <label class="flex items-center gap-3 cursor-pointer w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white/60 hover:border-primary/40 transition-all">
                <span class="text-sm">{{ formKardex.fotos.length > 0 ? `${formKardex.fotos.length} foto(s) seleccionada(s)` : 'Seleccionar fotos...' }}</span>
                <input type="file" accept="image/*" multiple class="hidden" @change="handleFotosChange" />
              </label>
              <div v-if="formKardex.fotos.length > 0" class="flex flex-wrap gap-2 mt-1">
                <span v-for="(f, i) in formKardex.fotos" :key="i" class="text-[10px] font-bold text-primary bg-primary/10 px-3 py-1 rounded-xl border border-primary/20 flex items-center gap-2">
                  {{ f.name }}
                  <button type="button" @click="formKardex.fotos.splice(i, 1)" class="text-white/40 hover:text-white">✕</button>
                </span>
              </div>
            </div>
          </div>

          <div class="pt-4 flex justify-end gap-4 border-t border-white/5">
            <button type="button" @click="closeKardexModal" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">Cancelar</button>
            <button type="submit" :disabled="isSubmitting" class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 disabled:opacity-50 transition-all">
              Registrar Movimiento
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { 
  CubeIcon, ExclamationTriangleIcon, ArrowTrendingUpIcon, MagnifyingGlassIcon, 
  FunnelIcon, PlusIcon, ClockIcon, TruckIcon, Square3Stack3DIcon, ChartBarIcon, 
  XMarkIcon, ArrowsRightLeftIcon, ChevronLeftIcon, ChevronRightIcon,
  PencilIcon, TrashIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const BASE_URL = '/concretos-oriente/Backend/api/v1';

const items = ref([]);
const kardex = ref([]);
const projects = ref([]);
const loading = ref(true);
const isSubmitting = ref(false);

const searchQuery = ref('');
const filterType = ref('');
const filterProject = ref('');
const filterCritical = ref(false);

const currentPage = ref(1);
const itemsPerPage = 10;

// Items API
const fetchItems = async () => {
  loading.value = true;
  try {
    const res = await fetch(`${BASE_URL}/inventory/items`);
    const data = await res.json();
    if (data.status === 'success') items.value = data.data;
  } catch(e) {}
  loading.value = false;
};

// Kardex API
const fetchKardex = async () => {
  try {
    const res = await fetch(`${BASE_URL}/inventory/kardex`);
    const data = await res.json();
    if (data.status === 'success') kardex.value = data.data;
  } catch(e) {}
};

// Projects API
const fetchProjects = async () => {
  try {
    const res = await fetch(`${BASE_URL}/projects`);
    const data = await res.json();
    if (data.status === 'success') projects.value = data.data;
  } catch(e) {}
};

onMounted(() => {
  fetchItems();
  fetchKardex();
  fetchProjects();
});

// Filtering & Pagination
const filteredItems = computed(() => {
  return items.value.filter(item => {
    const s = searchQuery.value.toLowerCase();
    const matchSearch = item.nombre.toLowerCase().includes(s) || (item.codigo_sku && item.codigo_sku.toLowerCase().includes(s)) || item.tipo_item.toLowerCase().includes(s);
    const matchType = filterType.value === '' || item.tipo_item === filterType.value;

    let matchProject = true;
    if (filterProject.value !== '') {
      matchProject = kardex.value.some(k => k.item_id === item.id && k.proyecto_destino_id === filterProject.value);
    }

    const matchCritical = !filterCritical.value || parseFloat(item.stock_actual) <= parseFloat(item.stock_minimo);

    return matchSearch && matchType && matchProject && matchCritical;
  });
});

const totalPages = computed(() => Math.ceil(filteredItems.value.length / itemsPerPage));

const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredItems.value.slice(start, start + itemsPerPage);
});

watch([searchQuery, filterType, filterProject, filterCritical], () => {
  currentPage.value = 1;
});

// Metrics Computed
const metrics = computed(() => {
  let criticals = 0;
  items.value.forEach(i => {
    if (parseFloat(i.stock_actual) <= parseFloat(i.stock_minimo)) criticals++;
  });

  return [
    { label: "Artículos Críticos", value: criticals.toString(), change: "Atención Requerida", icon: ExclamationTriangleIcon, color: "tertiary", isCritical: true },
    { label: "Total Catálogo", value: items.value.length.toString(), change: "Ítems Registrados", icon: CubeIcon, color: "primary" },
    { label: "Proyectos Activos", value: projects.value.length.toString(), change: "En curso", icon: ChartBarIcon, color: "primary" },
  ];
});

// Utilities for Stock visualization
const getStockStatus = (item) => {
  if (parseFloat(item.stock_actual) <= 0) return 'Agotado';
  if (parseFloat(item.stock_actual) <= parseFloat(item.stock_minimo)) return 'Crítico';
  return 'Óptimo';
};
const getStockColor = (item) => {
  const s = getStockStatus(item);
  if (s === 'Agotado') return 'text-tertiary';
  if (s === 'Crítico') return 'text-orange-400';
  return 'text-primary';
};
const getStockBgColor = (item) => {
  const s = getStockStatus(item);
  if (s === 'Agotado') return 'bg-tertiary';
  if (s === 'Crítico') return 'bg-orange-500';
  return 'bg-primary';
};
const getStockPercentage = (item) => {
  const s = getStockStatus(item);
  if (s === 'Agotado') return 0;
  if (s === 'Crítico') return 30;
  return 85;
};

// Item Details Modal
const selectedItemDetails = ref(null);
const loadingKardex = ref(false);
const itemKardex = computed(() => kardex.value.filter(k => k.item_id === selectedItemDetails.value?.id));

const criticalItems = computed(() =>
  items.value.filter(i => parseFloat(i.stock_actual) <= parseFloat(i.stock_minimo))
);

const parseFotos = (fotos) => {
  try { return fotos ? JSON.parse(fotos) : []; } catch { return []; }
};

const openItemDetails = (item) => {
  selectedItemDetails.value = item;
};

const getKardexIcon = (tipo) => {
  if (tipo === 'Entrada') return ArrowTrendingUpIcon;
  if (tipo === 'Dar de Baja' || tipo === 'Salida') return ClockIcon;
  if (tipo === 'Traslado') return TruckIcon;
  return Square3Stack3DIcon;
};
const getKardexIconBg = (tipo) => {
  if (tipo === 'Entrada') return 'bg-primary/20 text-primary';
  return 'bg-tertiary/20 text-tertiary';
};
const getKardexSign = (tipo) => {
  if (tipo === 'Entrada') return '+';
  return '-';
};
const getKardexQtyColor = (tipo) => {
  if (tipo === 'Entrada') return 'text-primary';
  return 'text-tertiary';
};


// Item Form Modal
const showItemModal = ref(false);
const isEditing = ref(false);
const editItemId = ref(null);
const formItem = ref({
  tipo_item: 'Material', codigo_sku: '', nombre: '', descripcion: '', 
  unidad_medida: '', stock_minimo: ''
});

const openItemModal = () => {
  isEditing.value = false;
  formItem.value = {tipo_item: 'Material', codigo_sku: '', nombre: '', descripcion: '', unidad_medida: '', stock_minimo: ''};
  showItemModal.value = true;
};

const openEditItem = (item) => {
  isEditing.value = true;
  editItemId.value = item.id;
  formItem.value = { ...item };
  showItemModal.value = true;
};

const closeItemModal = () => showItemModal.value = false;

const submitItem = async () => {
  isSubmitting.value = true;
  const fd = new FormData();
  Object.keys(formItem.value).forEach(k => {
    if(formItem.value[k] !== null && formItem.value[k] !== '') fd.append(k, formItem.value[k]);
  });

  try {
    const url = isEditing.value ? `${BASE_URL}/inventory/items/${editItemId.value}` : `${BASE_URL}/inventory/items`;
    const res = await fetch(url, { method: 'POST', body: fd });
    const json = await res.json();
    if(json.status === 'success') {
      await fetchItems();
      closeItemModal();
      if(selectedItemDetails.value) selectedItemDetails.value = items.value.find(i => i.id === selectedItemDetails.value.id);
      Swal.fire({background: '#0f172a', color: '#fff', icon: 'success', title: 'Guardado'});
    } else {
      Swal.fire({background: '#0f172a', color: '#fff', icon: 'error', text: json.message});
    }
  } catch(e) {}
  isSubmitting.value = false;
};

const deleteItem = async (id) => {
  const { isConfirmed } = await Swal.fire({
    background: '#0f172a', color: '#fff', title: '¿Eliminar ítem?', icon: 'warning', showCancelButton: true
  });
  if(isConfirmed) {
    try {
      const res = await fetch(`${BASE_URL}/inventory/items/${id}`, { method: 'DELETE' });
      const json = await res.json();
      if(json.status === 'success') {
        await fetchItems();
        Swal.fire({background: '#0f172a', color: '#fff', icon: 'success', title: 'Eliminado'});
      } else {
        Swal.fire({background: '#0f172a', color: '#fff', icon: 'error', text: json.message});
      }
    } catch(e) {}
  }
};


// Kardex Form Modal
const showKardexModal = ref(false);
const formKardex = ref({
  tipo_movimiento: 'Entrada', item_id: '', cantidad: '', fecha_movimiento: new Date().toISOString().slice(0,16),
  proyecto_destino_id: '', costo_unitario: '', referencia_documento: '', notas: '', fotos: []
});

const openKardexModal = () => {
  formKardex.value = {
    tipo_movimiento: 'Entrada', item_id: '', cantidad: '', fecha_movimiento: new Date().toISOString().slice(0,16),
    proyecto_destino_id: '', costo_unitario: '', referencia_documento: '', notas: '', fotos: []
  };
  showKardexModal.value = true;
};

const openKardexModalForItem = (item) => {
  openKardexModal();
  formKardex.value.item_id = item.id;
  selectedItemDetails.value = null; // Cierra modal de detalles
};

const closeKardexModal = () => showKardexModal.value = false;

const handleFotosChange = (e) => {
  const nuevas = Array.from(e.target.files);
  const combinadas = [...formKardex.value.fotos, ...nuevas];
  if (combinadas.length > 2) {
    Swal.fire({ background: '#0f172a', color: '#fff', icon: 'warning', title: 'Máximo 2 fotos', text: 'Solo se permiten 2 fotos por movimiento.' });
    formKardex.value.fotos = combinadas.slice(0, 2);
  } else {
    formKardex.value.fotos = combinadas;
  }
  e.target.value = '';
};

const submitKardex = async () => {
  isSubmitting.value = true;
  const fd = new FormData();
  Object.keys(formKardex.value).forEach(k => {
    if (k === 'fotos') return;
    if(formKardex.value[k] !== null && formKardex.value[k] !== '') fd.append(k, formKardex.value[k]);
  });
  formKardex.value.fotos.forEach(foto => fd.append('fotos[]', foto));

  try {
    const res = await fetch(`${BASE_URL}/inventory/kardex`, { method: 'POST', body: fd });
    const json = await res.json();
    if(json.status === 'success') {
      await fetchItems();
      await fetchKardex();
      closeKardexModal();
      Swal.fire({background: '#0f172a', color: '#fff', icon: 'success', title: 'Movimiento Registrado'});
    } else {
      Swal.fire({background: '#0f172a', color: '#fff', icon: 'error', text: json.message});
    }
  } catch(e) {}
  isSubmitting.value = false;
};

</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.02); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }
</style>
