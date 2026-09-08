<template>
  <div class="pt-20 pb-10 px-4 md:px-10 md:pb-20 max-w-7xl mx-auto space-y-10">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">
          Directorio de Proveedores
        </h2>
        <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">
          Gestión de alianzas estratégicas, suministros e historial financiero
        </p>
      </div>
      <div class="flex items-center gap-4">
        <!-- Tabs (Oculto temporalmente) -->
        <!--
        <div class="flex items-center bg-black/20 p-1 rounded-2xl border border-white/10">
          <button 
            @click="activeTab = 'directorio'"
            :class="['px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all', activeTab === 'directorio' ? 'bg-white/10 text-white shadow-xl' : 'text-white/40 hover:text-white']"
          >
            Directorio
          </button>
          <button 
            @click="activeTab = 'compras'; fetchPurchases();"
            :class="['px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all', activeTab === 'compras' ? 'bg-white/10 text-white shadow-xl' : 'text-white/40 hover:text-white']"
          >
            Compras
          </button>
        </div>
        -->

        <button @click="openSupplierModal" class="glass-button-primary text-white py-4 px-10 rounded-2xl font-black text-xs uppercase tracking-widest flex items-center justify-center gap-3 shadow-2xl shadow-primary/20 hover:scale-105 transition-all">
          <PlusIcon class="w-5 h-5" />
          Añadir Proveedor
        </button>
      </div>
    </div>

    <!-- DIRECTORIO TAB -->
    <section v-if="activeTab === 'directorio'" class="glass-card rounded-[56px] overflow-hidden border border-white/5 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
      <div class="p-12 border-b border-white/5 bg-white/5 backdrop-blur-3xl flex flex-col md:flex-row md:items-center justify-between gap-8">
        <div class="relative flex-1 max-w-lg">
          <MagnifyingGlassIcon class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-white/30" />
          <input 
            v-model="searchQuery"
            type="text" 
            placeholder="Buscar proveedores por nombre o nit..." 
            class="w-full glass-input rounded-2xl pl-14 pr-6 py-4 text-sm font-medium text-white outline-none focus:ring-2 focus:ring-primary/40 transition-all"
          />
        </div>
        <div class="text-xs font-black uppercase tracking-widest text-white/40 flex items-center gap-2">
          <span>Total Proveedores:</span>
          <span class="text-primary font-bold text-sm">{{ filteredSuppliers.length }}</span>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-0 divide-x divide-y divide-white/5">
        <div v-if="loading" class="p-12 text-center text-white/40 col-span-3">Cargando proveedores...</div>
        <div v-else-if="filteredSuppliers.length === 0" class="p-12 text-center text-white/40 col-span-3">No hay proveedores registrados.</div>
        
        <div 
          v-for="sup in filteredSuppliers" 
          :key="sup.id" 
          @click="openSupplierDetails(sup)"
          class="p-10 cursor-pointer transition-all hover:bg-white/[0.02] group relative flex flex-col justify-between"
        >
          <div class="absolute top-6 right-6 flex gap-2">
            <button @click.stop="openHistoryModal(sup)" title="Ver Historial de Montos por Mes" class="p-2 bg-primary/10 hover:bg-primary/25 rounded-xl text-primary border border-primary/20 transition-all">
              <ChartBarIcon class="w-4 h-4"/>
            </button>
            <button @click.stop="openEditSupplier(sup)" title="Editar Proveedor" class="p-2 bg-white/5 hover:bg-white/10 rounded-xl text-white/40 hover:text-white transition-all"><PencilIcon class="w-4 h-4"/></button>
            <button @click.stop="deleteSupplier(sup.id)" title="Eliminar Proveedor" class="p-2 bg-white/5 hover:bg-white/10 rounded-xl text-white/40 hover:text-tertiary transition-all"><TrashIcon class="w-4 h-4"/></button>
          </div>
          
          <div>
            <div class="flex justify-between items-start mb-6">
              <div class="w-14 h-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-primary group-hover:scale-110 transition-transform shadow-2xl">
                <BuildingOfficeIcon class="w-7 h-7" />
              </div>
            </div>

            <h4 class="text-xl font-black text-white italic uppercase tracking-tighter mb-1 truncate" :title="sup.razon_social">{{ sup.razon_social }}</h4>
            <p class="text-[10px] font-bold text-white/30 uppercase tracking-[0.2em] mb-6">NIT: {{ sup.nit }}</p>

            <div class="space-y-3 mb-6">
              <div class="flex items-center gap-3 text-white/60">
                <PhoneIcon class="w-4 h-4 text-primary shrink-0" />
                <span class="text-xs font-medium">{{ sup.telefono || 'Sin teléfono' }}</span>
              </div>
              <div v-if="sup.contacto_principal" class="flex items-center gap-3 text-white/60">
                <UserIcon class="w-4 h-4 text-primary shrink-0" />
                <span class="text-xs font-medium truncate">{{ sup.contacto_principal }}</span>
              </div>
            </div>

            <!-- Inversión Histórica Badge -->
            <div class="bg-white/[0.03] border border-white/5 rounded-xl p-3 mb-6 flex items-center justify-between">
              <div>
                <span class="text-[8px] font-black text-white/40 uppercase tracking-widest block">Total Histórico</span>
                <span class="text-sm font-black text-emerald-400 italic">Q {{ Number(sup.total_historico || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
              </div>
              <button @click.stop="openHistoryModal(sup)" class="text-[9px] font-black uppercase tracking-wider text-primary hover:underline flex items-center gap-1">
                <ChartBarIcon class="w-3.5 h-3.5" /> Historial
              </button>
            </div>
          </div>

          <div class="flex items-center justify-between pt-6 border-t border-white/5">
            <span :class="`px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border ${
              sup.condicion_pago === 'Crédito' ? 'bg-primary/20 text-primary border-primary/20 shadow-[0_0_15px_#6366f130]' : 'bg-white/5 text-white/40 border-white/5'
            }`">
              {{ sup.condicion_pago }}
            </span>
            <span v-if="sup.dias_credito" class="text-[10px] font-black text-white/20 uppercase tracking-widest">{{ sup.dias_credito }} Días</span>
          </div>
        </div>
      </div>
    </section>

    <!-- COMPRAS TAB -->
    <section v-if="activeTab === 'compras'" class="glass-card rounded-[56px] overflow-hidden border border-white/5 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[640px] text-left">
          <thead>
            <tr class="text-[10px] font-black text-white/20 uppercase tracking-[0.3em] bg-white/5">
              <th class="px-12 py-8">Orden / Fecha</th>
              <th class="px-12 py-8">Proveedor</th>
              <th class="px-12 py-8">Proyecto / Pago</th>
              <th class="px-12 py-8 text-right">Total</th>
              <th class="px-12 py-8 text-center">Archivo</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-if="loadingPurchases">
              <td colspan="5" class="px-12 py-10 text-center text-white/40">Cargando órdenes de compra...</td>
            </tr>
            <tr v-else-if="purchases.length === 0">
              <td colspan="5" class="px-12 py-10 text-center text-white/40">No hay órdenes de compra registradas.</td>
            </tr>
            <tr v-for="order in purchases" :key="order.id" class="hover:bg-white/5 transition-all group">
              <td class="px-12 py-10">
                <p class="font-black text-lg text-white italic uppercase">ORD-{{ String(order.id).padStart(4, '0') }}</p>
                <p class="text-[10px] font-bold text-white/30 uppercase mt-1">{{ formatDate(order.fecha_orden) }}</p>
              </td>
              <td class="px-12 py-10">
                <p class="font-bold text-white truncate max-w-[200px]">{{ order.razon_social }}</p>
              </td>
              <td class="px-12 py-10">
                <p class="font-bold text-sm text-white/80">{{ order.proyecto_nombre || 'N/A' }}</p>
                <p class="text-[10px] font-black text-primary uppercase mt-1">{{ order.condicion_pago }}</p>
              </td>
              <td class="px-12 py-10 text-right">
                <p class="font-black text-white italic text-lg">Q {{ Number(order.total).toLocaleString('en-US', {minimumFractionDigits: 2}) }}</p>
                <p class="text-[10px] font-bold text-white/30 uppercase mt-1">{{ order.items?.length || 0 }} Ítems</p>
              </td>
              <td class="px-12 py-10 text-center">
                <a v-if="order.archivo_adjunto" :href="getFileUrl(order.archivo_adjunto)" target="_blank" class="inline-flex p-3 rounded-2xl bg-white/5 hover:bg-white/10 text-primary transition-all shadow-xl" title="Ver Archivo Adjunto">
                  <DocumentTextIcon class="w-6 h-6" />
                </a>
                <span v-else class="text-[10px] text-white/20 uppercase tracking-widest font-black">Sin Archivo</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Supplier Detail Modal -->
    <transition name="fade">
      <div v-if="selectedSupplier" class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-6">
        <div @click="selectedSupplier = null" class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-4xl max-h-[90vh] overflow-y-auto glass-card rounded-[40px] md:rounded-[56px] p-6 md:p-12 border border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.5)] z-10 text-white">
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-6">
              <div class="w-20 h-20 rounded-[28px] bg-primary/20 flex items-center justify-center text-primary border border-white/10 shrink-0">
                <BuildingOfficeIcon class="w-10 h-10" />
              </div>
              <div>
                <h2 class="text-3xl md:text-4xl font-black text-white italic uppercase tracking-tighter">{{ selectedSupplier.razon_social }}</h2>
                <p class="text-sm font-bold text-primary mt-1 uppercase tracking-widest">NIT: {{ selectedSupplier.nit }}</p>
                <div class="flex items-center gap-2 mt-2 text-white/60 text-xs font-bold">
                  <MapPinIcon class="w-4 h-4 text-white/40" />
                  <span>{{ selectedSupplier.direccion }}</span>
                </div>
              </div>
            </div>
            <button @click="selectedSupplier = null" class="w-12 h-12 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-all border border-white/5 text-white/40 hover:text-white">
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>

          <!-- Summary cards -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
            <div class="glass-card p-5 rounded-2xl border border-white/5">
              <p class="text-[9px] font-black text-white/30 uppercase tracking-widest mb-1">Contacto Principal</p>
              <p class="text-lg font-black text-white italic truncate">{{ selectedSupplier.contacto_principal || 'N/A' }}</p>
            </div>
            <div class="glass-card p-5 rounded-2xl border border-white/5">
              <p class="text-[9px] font-black text-white/30 uppercase tracking-widest mb-1">Teléfono</p>
              <p class="text-lg font-black text-white italic">{{ selectedSupplier.telefono }}</p>
            </div>
            <div class="glass-card p-5 rounded-2xl border border-white/5">
              <p class="text-[9px] font-black text-white/30 uppercase tracking-widest mb-1">Condición de Pago</p>
              <div class="flex items-center gap-2">
                <p class="text-lg font-black text-white italic">{{ selectedSupplier.condicion_pago }}</p>
                <span class="text-xs text-primary font-bold" v-if="selectedSupplier.condicion_pago === 'Crédito'">({{selectedSupplier.dias_credito}} días)</span>
              </div>
            </div>
          </div>

          <!-- Historial de Montos Mensuales en Detalle -->
          <div class="mt-8 pt-6 border-t border-white/10 space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-sm font-black uppercase tracking-widest text-primary flex items-center gap-2">
                  <ChartBarIcon class="w-4 h-4" /> Historial de Montos por Mes
                </h3>
                <p class="text-[11px] text-white/40 mt-0.5">Consumo acumulado por compras y servicios mecánicos.</p>
              </div>
              <button @click="openHistoryModal(selectedSupplier)" class="px-4 py-2 bg-primary/20 hover:bg-primary/30 text-primary border border-primary/30 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center gap-1.5">
                <EyeIcon class="w-3.5 h-3.5" /> Ver Detalle Completo
              </button>
            </div>

            <!-- Mini stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
              <div class="bg-white/[0.02] p-4 rounded-xl border border-white/5">
                <span class="text-[8px] font-black text-white/30 uppercase tracking-widest block">Total Histórico</span>
                <span class="text-lg font-black text-emerald-400">Q {{ Number(selectedSupplier.total_historico || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
              </div>
              <div class="bg-white/[0.02] p-4 rounded-xl border border-white/5">
                <span class="text-[8px] font-black text-white/30 uppercase tracking-widest block">Transacciones</span>
                <span class="text-lg font-black text-white">{{ selectedSupplier.transacciones_count || 0 }}</span>
              </div>
            </div>
          </div>

          <div class="mt-8 flex gap-4">
            <button @click="openPurchaseModal" class="flex-1 glass-button-primary py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-2xl">Nueva Orden de Compra</button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Supplier Create/Edit Modal (SIN INPUT DE CORREO) -->
    <div v-if="showSupplierModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeSupplierModal"></div>
      <div class="glass-card w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-[32px] p-6 md:p-8 relative z-10 border border-white/10 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-2xl font-bold text-white">{{ isEditing ? 'Editar Proveedor' : 'Añadir Proveedor' }}</h3>
          <button @click="closeSupplierModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all"><XMarkIcon class="w-6 h-6" /></button>
        </div>

        <form @submit.prevent="submitSupplier" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Razón Social *</label>
              <input v-model="formSupplier.razon_social" type="text" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 text-sm" />
            </div>
            
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">NIT *</label>
              <input v-model="formSupplier.nit" type="text" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 text-sm font-mono uppercase" />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Teléfono *</label>
              <input v-model="formSupplier.telefono" @input="formatPhone" type="text" maxlength="9" required placeholder="0000-0000" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 text-sm font-mono" />
            </div>

            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Dirección *</label>
              <input v-model="formSupplier.direccion" type="text" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 text-sm" />
            </div>

            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Contacto Principal</label>
              <input v-model="formSupplier.contacto_principal" type="text" placeholder="Ej. Juan Pérez (Ventas)" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 text-sm" />
            </div>

            <div class="space-y-2" :class="formSupplier.condicion_pago === 'Crédito' ? '' : 'md:col-span-2'">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Condición de Pago *</label>
              <select v-model="formSupplier.condicion_pago" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none text-sm">
                <option value="Contado">Contado</option>
                <option value="Crédito">Crédito</option>
              </select>
            </div>

            <div class="space-y-2" v-if="formSupplier.condicion_pago === 'Crédito'">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Días de Crédito *</label>
              <input v-model="formSupplier.dias_credito" type="number" min="1" required placeholder="30" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 text-sm" />
            </div>
          </div>

          <div class="pt-4 flex justify-end gap-4 border-t border-white/5">
            <button type="button" @click="closeSupplierModal" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all text-xs uppercase tracking-wider">Cancelar</button>
            <button type="submit" :disabled="isSubmitting" class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 disabled:opacity-50 transition-all text-xs uppercase tracking-wider">
              {{ isEditing ? 'Actualizar' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL HISTORIAL MENSUAL DE MONTOS Y TRANSACCIONES -->
    <transition name="fade">
      <div v-if="showHistoryModal && historySupplier" class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-6">
        <div @click="showHistoryModal = false" class="absolute inset-0 bg-black/80 backdrop-blur-sm cursor-pointer"></div>
        <div class="relative w-full max-w-5xl max-h-[90vh] overflow-y-auto glass-card rounded-[40px] md:rounded-[56px] p-6 md:p-10 border border-white/10 shadow-2xl z-10 text-white space-y-6">
          
          <!-- Header Modal -->
          <div class="flex items-center justify-between border-b border-white/10 pb-6">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 rounded-2xl bg-primary/20 flex items-center justify-center text-primary border border-white/10 shrink-0">
                <ChartBarIcon class="w-7 h-7" />
              </div>
              <div>
                <h3 class="text-2xl font-black uppercase italic tracking-tighter text-white">{{ historySupplier.razon_social }}</h3>
                <p class="text-xs font-bold text-primary tracking-widest uppercase">NIT: {{ historySupplier.nit }} · Historial de Montos por Mes</p>
              </div>
            </div>
            <button @click="showHistoryModal = false" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-white/40 hover:text-white transition-all">
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>

          <!-- Loading state -->
          <div v-if="loadingHistory" class="py-16 text-center text-white/40 font-bold">
            Cargando historial de montos...
          </div>

          <!-- History content -->
          <template v-else>
            <!-- KPI Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div class="bg-white/5 p-5 rounded-2xl border border-white/5">
                <span class="text-[8px] font-black text-white/40 uppercase tracking-widest block">Total Histórico</span>
                <span class="text-2xl font-black text-emerald-400 italic">Q {{ Number(historyData.total_general || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
              </div>
              <div class="bg-white/5 p-5 rounded-2xl border border-white/5">
                <span class="text-[8px] font-black text-white/40 uppercase tracking-widest block">Este Mes</span>
                <span class="text-2xl font-black text-primary italic">Q {{ Number(historyData.total_este_mes || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
              </div>
              <div class="bg-white/5 p-5 rounded-2xl border border-white/5">
                <span class="text-[8px] font-black text-white/40 uppercase tracking-widest block">Este Año</span>
                <span class="text-2xl font-black text-amber-400 italic">Q {{ Number(historyData.total_este_ano || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
              </div>
              <div class="bg-white/5 p-5 rounded-2xl border border-white/5">
                <span class="text-[8px] font-black text-white/40 uppercase tracking-widest block">Total Movimientos</span>
                <span class="text-2xl font-black text-white italic">{{ historyData.transacciones_count || 0 }}</span>
              </div>
            </div>

            <!-- Filtros de Año y Mes -->
            <div class="bg-black/30 p-4 rounded-2xl border border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div class="flex items-center gap-2">
                <h4 class="text-xs font-black uppercase tracking-widest text-primary flex items-center gap-2">
                  <CalendarDaysIcon class="w-4 h-4" /> Desglose por Período
                </h4>
                <span v-if="historyYearFilter !== 'all' || historyMonthFilter !== 'all'" class="px-2.5 py-0.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-bold">
                  Total Filtrado: Q {{ filteredTotalMonto.toLocaleString('en-US', { minimumFractionDigits: 2 }) }} ({{ filteredTotalTransactions }} movs)
                </span>
              </div>

              <div class="flex flex-wrap items-center gap-3">
                <!-- Filtro Año -->
                <div class="flex items-center gap-1.5">
                  <span class="text-[10px] font-black text-white/40 uppercase tracking-wider">Año:</span>
                  <select v-model="historyYearFilter" class="bg-slate-950/90 border border-white/10 rounded-xl px-3 py-1.5 text-xs font-bold text-white focus:outline-none focus:border-primary">
                    <option value="all">Todos los años</option>
                    <option v-for="y in availableHistoryYears" :key="y" :value="y">{{ y }}</option>
                  </select>
                </div>

                <!-- Filtro Mes -->
                <div class="flex items-center gap-1.5">
                  <span class="text-[10px] font-black text-white/40 uppercase tracking-wider">Mes:</span>
                  <select v-model="historyMonthFilter" class="bg-slate-950/90 border border-white/10 rounded-xl px-3 py-1.5 text-xs font-bold text-white focus:outline-none focus:border-primary">
                    <option v-for="m in monthsList" :key="m.value" :value="m.value">{{ m.label }}</option>
                  </select>
                </div>

                <!-- Reset button -->
                <button
                  v-if="historyYearFilter !== 'all' || historyMonthFilter !== 'all'"
                  @click="historyYearFilter = 'all'; historyMonthFilter = 'all';"
                  class="px-2.5 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 text-white/50 hover:text-white text-[10px] font-bold uppercase tracking-wider transition-all"
                >
                  Limpiar Filtros
                </button>
              </div>
            </div>

            <!-- Empty state -->
            <div v-if="filteredMonthlyHistory.length === 0" class="py-12 text-center text-white/30 text-xs font-bold uppercase tracking-widest border-2 border-dashed border-white/5 rounded-3xl">
              No hay compras ni servicios registrados para este proveedor en el período seleccionado.
            </div>

            <!-- List of months (Accordion cards) -->
            <div class="space-y-4">
              <div v-for="m in filteredMonthlyHistory" :key="m.mes" class="bg-white/[0.02] border border-white/10 rounded-2xl overflow-hidden transition-all">
                <!-- Month header clickable -->
                <div @click="toggleMonthExpand(m.mes)" class="p-5 flex items-center justify-between cursor-pointer hover:bg-white/5 transition-colors">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary font-black text-xs">
                      {{ m.mes.split('-')[1] }}
                    </div>
                    <div>
                      <h5 class="text-sm font-black uppercase text-white tracking-wide">{{ m.mes_nombre }}</h5>
                      <span class="text-[10px] font-bold text-white/40">{{ m.count }} movimiento(s)</span>
                    </div>
                  </div>

                  <div class="flex items-center gap-4">
                    <div class="text-right">
                      <span class="text-[8px] font-black text-white/30 uppercase tracking-widest block">Total Mes</span>
                      <span class="text-lg font-black text-emerald-400 italic">Q {{ Number(m.total).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                    </div>
                    <ChevronUpIcon v-if="expandedMonths[m.mes]" class="w-5 h-5 text-white/40" />
                    <ChevronDownIcon v-else class="w-5 h-5 text-white/40" />
                  </div>
                </div>

                <!-- Expanded Transactions Table -->
                <div v-if="expandedMonths[m.mes]" class="px-5 pb-5 pt-2 border-t border-white/5 bg-slate-950/40">
                  <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                      <thead>
                        <tr class="text-[8px] font-black text-white/30 uppercase tracking-widest border-b border-white/5">
                          <th class="py-2.5 px-3">Fecha</th>
                          <th class="py-2.5 px-3">Origen</th>
                          <th class="py-2.5 px-3">Documento</th>
                          <th class="py-2.5 px-3">Proyecto / Unidad / Concepto</th>
                          <th class="py-2.5 px-3 text-right">Monto (Q)</th>
                          <th class="py-2.5 px-3 text-center">Adjunto</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-white/5">
                        <tr v-for="(t, tIdx) in m.transacciones" :key="tIdx" class="hover:bg-white/5 transition-colors">
                          <td class="py-2.5 px-3 font-mono text-white/80 whitespace-nowrap">{{ formatDate(t.fecha) }}</td>
                          <td class="py-2.5 px-3">
                            <span :class="[
                              'text-[8px] font-black uppercase tracking-wider px-2 py-0.5 rounded border',
                              t.origen.includes('Orden') ? 'bg-blue-500/10 text-blue-400 border-blue-500/20' :
                              t.origen.includes('Mano') ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' :
                              'bg-violet-500/10 text-violet-400 border-violet-500/20'
                            ]">
                              {{ t.origen }}
                            </span>
                          </td>
                          <td class="py-2.5 px-3 font-bold text-white">{{ t.documento }}</td>
                          <td class="py-2.5 px-3 text-white/70 max-w-[240px] truncate" :title="t.proyecto_unidad">{{ t.proyecto_unidad }}</td>
                          <td class="py-2.5 px-3 text-right font-black text-emerald-400 font-mono">Q {{ Number(t.monto).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</td>
                          <td class="py-2.5 px-3 text-center">
                            <a v-if="t.archivo_adjunto" :href="getFileUrl(t.archivo_adjunto)" target="_blank" class="text-primary hover:underline font-bold text-[10px]">
                              Ver
                            </a>
                            <span v-else class="text-white/20 text-[10px]">—</span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

              </div>
            </div>

          </template>

        </div>
      </div>
    </transition>

    <!-- Purchase Order Modal -->
    <div v-if="showPurchaseModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closePurchaseModal"></div>
      <div class="glass-card w-full max-w-5xl max-h-[90vh] overflow-y-auto rounded-[32px] p-4 md:p-8 relative z-10 border border-white/10 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-2xl font-bold text-white">Solicitud / Orden de Compra</h3>
          <button @click="closePurchaseModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all"><XMarkIcon class="w-6 h-6" /></button>
        </div>

        <form @submit.prevent="submitPurchase" class="space-y-6">
          <!-- Main Data -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Proveedor</label>
              <input type="text" :value="selectedSupplier?.razon_social" disabled class="w-full bg-white/5 border border-white/5 rounded-2xl px-5 py-4 text-white/50 cursor-not-allowed" />
            </div>
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Proyecto Destino *</label>
              <select v-model="formPurchase.proyecto_id" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none">
                <option value="" disabled>Seleccione...</option>
                <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.nombre }}</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Fecha *</label>
              <input v-model="formPurchase.fecha_orden" type="date" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Condición de Pago *</label>
              <select v-model="formPurchase.condicion_pago" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none">
                <option value="Contado">Contado</option>
                <option value="Crédito">Crédito</option>
              </select>
            </div>
            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Adjuntar Cotización/Factura (PDF/Imagen)</label>
              <input type="file" @change="handleFileUpload" accept="image/*,.pdf" class="w-full text-white/60 file:mr-4 file:py-4 file:px-6 file:rounded-2xl file:border-0 file:text-xs file:font-bold file:bg-white/10 file:text-white hover:file:bg-white/20 transition-all cursor-pointer" />
            </div>
          </div>

          <!-- Dynamic Items -->
          <div class="border border-white/10 rounded-2xl p-6 bg-black/20">
            <div class="flex justify-between items-center mb-4">
              <h4 class="text-lg font-bold text-white">Ítems de Compra</h4>
              <button type="button" @click="addPurchaseItem" class="px-4 py-2 bg-primary/20 text-primary text-xs font-bold uppercase rounded-lg hover:bg-primary/30 transition-all">+ Agregar Ítem</button>
            </div>
            
            <div class="space-y-4">
              <div v-for="(item, index) in formPurchase.items" :key="index" class="flex flex-col md:flex-row gap-4 items-end bg-white/5 p-4 rounded-xl">
                <div class="flex-1 space-y-1 w-full">
                  <label class="text-[10px] text-white/40 uppercase font-bold">Catálogo (Ítem)</label>
                  <select v-model="item.item_id" required class="w-full bg-black/40 border border-white/5 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-primary/50 appearance-none">
                    <option value="" disabled>Seleccione...</option>
                    <option v-for="inv in inventoryItems" :key="inv.id" :value="inv.id">{{ inv.codigo_sku }} - {{ inv.nombre }}</option>
                  </select>
                </div>
                <div class="w-full md:w-32 space-y-1">
                  <label class="text-[10px] text-white/40 uppercase font-bold">Cantidad</label>
                  <input v-model="item.cantidad" type="number" step="0.01" min="0.01" required class="w-full bg-black/40 border border-white/5 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-primary/50" />
                </div>
                <div class="w-full md:w-32 space-y-1">
                  <label class="text-[10px] text-white/40 uppercase font-bold">Precio Unitario (Q)</label>
                  <input v-model="item.precio_unitario" type="number" step="0.01" min="0" required class="w-full bg-black/40 border border-white/5 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-primary/50" />
                </div>
                <div class="w-full md:w-auto">
                  <button type="button" @click="removePurchaseItem(index)" class="h-[46px] w-[46px] flex items-center justify-center rounded-xl bg-tertiary/10 text-tertiary hover:bg-tertiary/20 transition-all">
                    <TrashIcon class="w-5 h-5"/>
                  </button>
                </div>
              </div>
              <p v-if="formPurchase.items.length === 0" class="text-white/30 text-sm text-center py-4">No hay ítems en la orden.</p>
            </div>
            
            <div class="mt-6 flex justify-end text-xl font-black text-white italic tracking-tighter">
              Total: Q {{ purchaseTotal.toLocaleString('en-US', {minimumFractionDigits:2}) }}
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Observaciones</label>
            <textarea v-model="formPurchase.observaciones" rows="2" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white placeholder-white/20 focus:outline-none focus:border-primary/50"></textarea>
          </div>

          <div class="pt-4 flex justify-end gap-4 border-t border-white/5">
            <button type="button" @click="closePurchaseModal" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">Cancelar</button>
            <button type="submit" :disabled="isSubmitting || formPurchase.items.length === 0" class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 disabled:opacity-50 transition-all">
              Crear Orden
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { 
  BuildingOfficeIcon, PhoneIcon, MapPinIcon, ShieldCheckIcon, UserIcon,
  PlusIcon, FunnelIcon, MagnifyingGlassIcon, StarIcon, XMarkIcon, PencilIcon, TrashIcon,
  DocumentTextIcon, ChartBarIcon, CalendarDaysIcon, EyeIcon, ChevronDownIcon, ChevronUpIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const BASE_URL = '/concretos-oriente/Backend/api/v1';

const activeTab = ref('directorio');
const suppliers = ref([]);
const purchases = ref([]);
const projects = ref([]);
const inventoryItems = ref([]);
const loading = ref(true);
const loadingPurchases = ref(false);
const isSubmitting = ref(false);

const searchQuery = ref('');
const selectedSupplier = ref(null);

// Historial Mensual State
const showHistoryModal = ref(false);
const historySupplier = ref(null);
const historyData = ref({ total_general: 0, total_este_mes: 0, total_este_ano: 0, transacciones_count: 0, meses: [], transacciones: [] });
const loadingHistory = ref(false);
const historyYearFilter = ref('all');
const historyMonthFilter = ref('all');
const expandedMonths = ref({});

const monthsList = [
  { value: 'all', label: 'Todos los meses' },
  { value: '01', label: '01 - Enero' },
  { value: '02', label: '02 - Febrero' },
  { value: '03', label: '03 - Marzo' },
  { value: '04', label: '04 - Abril' },
  { value: '05', label: '05 - Mayo' },
  { value: '06', label: '06 - Junio' },
  { value: '07', label: '07 - Julio' },
  { value: '08', label: '08 - Agosto' },
  { value: '09', label: '09 - Septiembre' },
  { value: '10', label: '10 - Octubre' },
  { value: '11', label: '11 - Noviembre' },
  { value: '12', label: '12 - Diciembre' },
];

const fetchSuppliers = async () => {
  loading.value = true;
  try {
    const res = await fetch(`${BASE_URL}/suppliers`);
    const data = await res.json();
    if (data.status === 'success') suppliers.value = data.data;
  } catch(e) {}
  loading.value = false;
};

const fetchPurchases = async () => {
  loadingPurchases.value = true;
  try {
    const res = await fetch(`${BASE_URL}/purchases`);
    const data = await res.json();
    if (data.status === 'success') purchases.value = data.data;
  } catch(e) {}
  loadingPurchases.value = false;
};

const fetchDependencies = async () => {
  try {
    const resP = await fetch(`${BASE_URL}/projects`);
    const dataP = await resP.json();
    if(dataP.status === 'success') projects.value = dataP.data;

    const resI = await fetch(`${BASE_URL}/inventory/items`);
    const dataI = await resI.json();
    if(dataI.status === 'success') inventoryItems.value = dataI.data;
  } catch(e) {}
};

onMounted(() => {
  fetchSuppliers();
  fetchDependencies();
});

const filteredSuppliers = computed(() => {
  if(!searchQuery.value) return suppliers.value;
  return suppliers.value.filter(s => 
    s.razon_social.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
    s.nit.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

const formatDate = (val) => {
  if (!val) return '';
  const [y, m, d] = val.split('-');
  return `${d}/${m}/${y}`;
};

const getFileUrl = (path) => {
  return `/concretos-oriente/Backend/${path}?t=${Date.now()}`;
};

// Formatter for Phone Input
const formatPhone = (e) => {
  let val = e.target.value.replace(/\D/g, ''); // keep only numbers
  if (val.length > 4) {
    val = val.substring(0, 4) + '-' + val.substring(4, 8);
  }
  formSupplier.value.telefono = val;
};

// SUPPLIER MODAL (SIN CORREO)
const showSupplierModal = ref(false);
const isEditing = ref(false);
const editSupplierId = ref(null);
const formSupplier = ref({
  razon_social: '', nit: '', direccion: '', telefono: '',
  contacto_principal: '', condicion_pago: 'Contado', dias_credito: ''
});

const openSupplierModal = () => {
  isEditing.value = false;
  formSupplier.value = { razon_social: '', nit: '', direccion: '', telefono: '', contacto_principal: '', condicion_pago: 'Contado', dias_credito: '' };
  showSupplierModal.value = true;
};

const openEditSupplier = (sup) => {
  isEditing.value = true;
  editSupplierId.value = sup.id;
  formSupplier.value = {
    razon_social: sup.razon_social,
    nit: sup.nit,
    direccion: sup.direccion,
    telefono: sup.telefono,
    contacto_principal: sup.contacto_principal || '',
    condicion_pago: sup.condicion_pago || 'Contado',
    dias_credito: sup.dias_credito || ''
  };
  showSupplierModal.value = true;
};

const closeSupplierModal = () => showSupplierModal.value = false;

const submitSupplier = async () => {
  isSubmitting.value = true;
  const fd = new FormData();
  Object.keys(formSupplier.value).forEach(k => {
    if(formSupplier.value[k] !== null && formSupplier.value[k] !== '') fd.append(k, formSupplier.value[k]);
  });

  try {
    const url = isEditing.value ? `${BASE_URL}/suppliers/${editSupplierId.value}` : `${BASE_URL}/suppliers`;
    const res = await fetch(url, { method: 'POST', body: fd });
    const json = await res.json();
    if(json.status === 'success') {
      await fetchSuppliers();
      closeSupplierModal();
      if(selectedSupplier.value) selectedSupplier.value = suppliers.value.find(s => s.id === selectedSupplier.value.id);
      Swal.fire({background: '#0f172a', color: '#fff', icon: 'success', title: 'Guardado'});
    } else {
      Swal.fire({background: '#0f172a', color: '#fff', icon: 'error', text: json.message});
    }
  } catch(e) {}
  isSubmitting.value = false;
};

const deleteSupplier = async (id) => {
  const { isConfirmed } = await Swal.fire({
    background: '#0f172a', color: '#fff', title: '¿Eliminar proveedor?', icon: 'warning', showCancelButton: true
  });
  if(isConfirmed) {
    try {
      const res = await fetch(`${BASE_URL}/suppliers/${id}`, { method: 'DELETE' });
      const json = await res.json();
      if(json.status === 'success') {
        await fetchSuppliers();
        if(selectedSupplier.value?.id === id) selectedSupplier.value = null;
        Swal.fire({background: '#0f172a', color: '#fff', icon: 'success', title: 'Eliminado'});
      } else {
        Swal.fire({background: '#0f172a', color: '#fff', icon: 'error', text: json.message});
      }
    } catch(e) {}
  }
};

const openSupplierDetails = async (sup) => {
  selectedSupplier.value = sup;
};

// HISTORIAL MENSUAL METHODS
const openHistoryModal = async (sup) => {
  historySupplier.value = sup;
  showHistoryModal.value = true;
  loadingHistory.value = true;
  historyYearFilter.value = 'all';
  historyMonthFilter.value = 'all';
  expandedMonths.value = {};

  try {
    const res = await fetch(`${BASE_URL}/suppliers/${sup.id}/history`);
    const json = await res.json();
    if (json.status === 'success') {
      historyData.value = json.data || { total_general: 0, total_este_mes: 0, total_este_ano: 0, transacciones_count: 0, meses: [], transacciones: [] };
      // Auto-expand the first month
      if (historyData.value.meses && historyData.value.meses.length > 0) {
        expandedMonths.value[historyData.value.meses[0].mes] = true;
      }
    }
  } catch (e) {
    console.error(e);
  }
  loadingHistory.value = false;
};

const toggleMonthExpand = (mes) => {
  expandedMonths.value[mes] = !expandedMonths.value[mes];
};

const availableHistoryYears = computed(() => {
  if (!historyData.value.meses) return [];
  const years = new Set(historyData.value.meses.map(m => m.ano));
  return Array.from(years).sort((a, b) => b - a);
});

const filteredMonthlyHistory = computed(() => {
  if (!historyData.value.meses) return [];
  return historyData.value.meses.filter(m => {
    const matchYear = historyYearFilter.value === 'all' || m.ano === parseInt(historyYearFilter.value);
    const monthPart = m.mes.split('-')[1];
    const matchMonth = historyMonthFilter.value === 'all' || monthPart === historyMonthFilter.value;
    return matchYear && matchMonth;
  });
});

const filteredTotalMonto = computed(() => {
  return filteredMonthlyHistory.value.reduce((sum, m) => sum + Number(m.total || 0), 0);
});

const filteredTotalTransactions = computed(() => {
  return filteredMonthlyHistory.value.reduce((sum, m) => sum + Number(m.count || 0), 0);
});

// PURCHASE ORDER MODAL
const showPurchaseModal = ref(false);
const formPurchase = ref({
  proyecto_id: '', fecha_orden: new Date().toISOString().slice(0,10), condicion_pago: 'Contado', 
  observaciones: '', items: []
});
const attachmentFile = ref(null);

const openPurchaseModal = () => {
  formPurchase.value = {
    proyecto_id: '', fecha_orden: new Date().toISOString().slice(0,10), condicion_pago: selectedSupplier.value.condicion_pago,
    observaciones: '', items: [{ item_id: '', cantidad: '', precio_unitario: '' }]
  };
  attachmentFile.value = null;
  showPurchaseModal.value = true;
};

const closePurchaseModal = () => showPurchaseModal.value = false;

const addPurchaseItem = () => {
  formPurchase.value.items.push({ item_id: '', cantidad: '', precio_unitario: '' });
};

const removePurchaseItem = (index) => {
  formPurchase.value.items.splice(index, 1);
};

const purchaseTotal = computed(() => {
  return formPurchase.value.items.reduce((acc, curr) => acc + ((parseFloat(curr.cantidad)||0) * (parseFloat(curr.precio_unitario)||0)), 0);
});

const handleFileUpload = (e) => {
  if (e.target.files.length > 0) attachmentFile.value = e.target.files[0];
};

const submitPurchase = async () => {
  if(formPurchase.value.items.length === 0) return Swal.fire('Error', 'Debes agregar al menos un ítem.', 'error');
  
  isSubmitting.value = true;
  const fd = new FormData();
  fd.append('proveedor_id', selectedSupplier.value.id);
  fd.append('proyecto_id', formPurchase.value.proyecto_id);
  fd.append('fecha_orden', formPurchase.value.fecha_orden);
  fd.append('condicion_pago', formPurchase.value.condicion_pago);
  if(formPurchase.value.observaciones) fd.append('observaciones', formPurchase.value.observaciones);
  if(attachmentFile.value) fd.append('archivo_adjunto', attachmentFile.value);
  fd.append('items', JSON.stringify(formPurchase.value.items));

  try {
    const res = await fetch(`${BASE_URL}/purchases`, { method: 'POST', body: fd });
    const json = await res.json();
    if(json.status === 'success') {
      closePurchaseModal();
      selectedSupplier.value = null; // Volver al inicio
      fetchSuppliers();
      Swal.fire({background: '#0f172a', color: '#fff', icon: 'success', title: 'Orden Creada'});
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
</style>
