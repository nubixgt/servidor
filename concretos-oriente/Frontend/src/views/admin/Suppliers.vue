<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-10">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">
          {{ activeTab === 'directorio' ? 'Directorio de Proveedores' : 'Historial de Compras' }}
        </h2>
        <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">
          {{ activeTab === 'directorio' ? 'Gestión de alianzas estratégicas y suministros' : 'Registro histórico de todas las órdenes emitidas' }}
        </p>
      </div>
      <div class="flex items-center gap-4">
        <!-- Tabs -->
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

        <button v-if="activeTab === 'directorio'" @click="openSupplierModal" class="glass-button-primary text-white py-4 px-10 rounded-2xl font-black text-xs uppercase tracking-widest flex items-center justify-center gap-3 shadow-2xl shadow-primary/20 hover:scale-105 transition-all">
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
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-0 divide-x divide-y divide-white/5">
        <div v-if="loading" class="p-12 text-center text-white/40 col-span-3">Cargando proveedores...</div>
        <div v-else-if="filteredSuppliers.length === 0" class="p-12 text-center text-white/40 col-span-3">No hay proveedores registrados.</div>
        
        <div 
          v-for="sup in filteredSuppliers" 
          :key="sup.id" 
          @click="selectedSupplier = sup"
          class="p-12 cursor-pointer transition-all hover:bg-white/[0.02] group relative"
        >
          <div class="absolute top-6 right-6 flex gap-2">
            <button @click.stop="openEditSupplier(sup)" class="p-2 bg-white/5 hover:bg-white/10 rounded-xl text-white/40 hover:text-white transition-all"><PencilIcon class="w-4 h-4"/></button>
            <button @click.stop="deleteSupplier(sup.id)" class="p-2 bg-white/5 hover:bg-white/10 rounded-xl text-white/40 hover:text-tertiary transition-all"><TrashIcon class="w-4 h-4"/></button>
          </div>
          
          <div class="flex justify-between items-start mb-8">
            <div class="w-16 h-16 rounded-3xl bg-white/5 border border-white/10 flex items-center justify-center text-primary group-hover:scale-110 transition-transform shadow-2xl">
              <BuildingOfficeIcon class="w-8 h-8" />
            </div>
          </div>

          <h4 class="text-2xl font-black text-white italic uppercase tracking-tighter mb-2 truncate" :title="sup.razon_social">{{ sup.razon_social }}</h4>
          <p class="text-[10px] font-bold text-white/30 uppercase tracking-[0.2em] mb-8">NIT: {{ sup.nit }}</p>

          <div class="space-y-4 mb-10">
            <div class="flex items-center gap-4 text-white/60">
              <PhoneIcon class="w-4 h-4 text-primary" />
              <span class="text-sm font-medium">{{ sup.telefono }}</span>
            </div>
            <div class="flex items-center gap-4 text-white/60">
              <EnvelopeIcon class="w-4 h-4 text-primary" />
              <span class="text-sm font-medium truncate">{{ sup.correo_electronico || 'Sin correo' }}</span>
            </div>
          </div>

          <div class="flex items-center justify-between pt-8 border-t border-white/5">
            <span :class="`px-4 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest border ${
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
        <table class="w-full text-left">
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
                <p class="text-[10px] font-bold text-white/30 uppercase mt-1">{{ order.items.length }} Ítems</p>
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
      <div v-if="selectedSupplier" class="fixed inset-0 z-50 flex items-center justify-center p-6">
        <div @click="selectedSupplier = null" class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-4xl glass-card rounded-[56px] p-12 border border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.5)] transform scale-100 transition-all duration-500" data-aos="zoom-in-up" data-aos-duration="1000">
          <div class="flex items-start justify-between">
            <div class="flex gap-8">
              <div class="w-24 h-24 rounded-[32px] bg-primary/20 flex items-center justify-center text-primary border border-white/10">
                <BuildingOfficeIcon class="w-12 h-12" />
              </div>
              <div>
                <h2 class="text-5xl font-black text-white italic uppercase tracking-tighter">{{ selectedSupplier.razon_social }}</h2>
                <p class="text-xl font-bold text-primary mt-2 uppercase tracking-widest">NIT: {{ selectedSupplier.nit }}</p>
                <div class="flex items-center gap-6 mt-6">
                  <div class="flex items-center gap-2">
                    <MapPinIcon class="w-4 h-4 text-white/40" />
                    <span class="text-sm font-bold text-white/60">{{ selectedSupplier.direccion }}</span>
                  </div>
                </div>
              </div>
            </div>
            <button @click="selectedSupplier = null" class="w-14 h-14 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-all border border-white/5 text-white/40 hover:text-white">
              <XMarkIcon class="w-8 h-8" />
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-12">
            <div class="glass-card p-8 rounded-[32px] border border-white/5" data-aos="zoom-in-up" data-aos-duration="1000">
              <p class="text-[10px] font-black text-white/20 uppercase tracking-widest mb-4">Contacto Principal</p>
              <p class="text-2xl font-black text-white italic truncate">{{ selectedSupplier.contacto_principal || 'N/A' }}</p>
            </div>
            <div class="glass-card p-8 rounded-[32px] border border-white/5" data-aos="zoom-in-up" data-aos-duration="1000">
              <p class="text-[10px] font-black text-white/20 uppercase tracking-widest mb-4">Condición de Pago</p>
              <div class="flex items-center gap-3">
                <p class="text-2xl font-black text-white italic">{{ selectedSupplier.condicion_pago }}</p>
                <span class="text-sm text-primary" v-if="selectedSupplier.condicion_pago === 'Crédito'">({{selectedSupplier.dias_credito}} días)</span>
              </div>
            </div>
          </div>

          <div class="mt-12 flex gap-4">
            <button @click="openPurchaseModal" class="flex-1 glass-button-primary py-5 rounded-2xl font-black text-sm uppercase tracking-widest shadow-2xl">Nueva Orden de Compra</button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Supplier Create/Edit Modal -->
    <div v-if="showSupplierModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeSupplierModal"></div>
      <div class="glass-card w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-[32px] p-8 relative z-10 border border-white/10 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
        <div class="flex items-center justify-between mb-8">
          <h3 class="text-2xl font-bold text-white">{{ isEditing ? 'Editar Proveedor' : 'Añadir Proveedor' }}</h3>
          <button @click="closeSupplierModal" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded-xl transition-all"><XMarkIcon class="w-6 h-6" /></button>
        </div>

        <form @submit.prevent="submitSupplier" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Razón Social *</label>
              <input v-model="formSupplier.razon_social" type="text" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>
            
            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">NIT *</label>
              <input v-model="formSupplier.nit" type="text" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Teléfono *</label>
              <input v-model="formSupplier.telefono" @input="formatPhone" type="text" maxlength="9" required placeholder="0000-0000" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>

            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Dirección *</label>
              <input v-model="formSupplier.direccion" type="text" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Contacto Principal</label>
              <input v-model="formSupplier.contacto_principal" type="text" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Correo Electrónico</label>
              <input v-model="formSupplier.correo_electronico" type="email" class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Condición de Pago *</label>
              <select v-model="formSupplier.condicion_pago" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50 appearance-none">
                <option value="Contado">Contado</option>
                <option value="Crédito">Crédito</option>
              </select>
            </div>

            <div class="space-y-2" v-if="formSupplier.condicion_pago === 'Crédito'">
              <label class="text-xs font-bold text-white/50 uppercase tracking-wider">Días de Crédito</label>
              <input v-model="formSupplier.dias_credito" type="number" required class="w-full bg-black/20 border border-white/10 rounded-2xl px-5 py-4 text-white focus:outline-none focus:border-primary/50" />
            </div>
          </div>

          <div class="pt-4 flex justify-end gap-4 border-t border-white/5">
            <button type="button" @click="closeSupplierModal" class="px-8 py-4 rounded-2xl font-bold text-white/60 hover:text-white hover:bg-white/5 transition-all">Cancelar</button>
            <button type="submit" :disabled="isSubmitting" class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 disabled:opacity-50 transition-all">
              {{ isEditing ? 'Actualizar' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Purchase Order Modal -->
    <div v-if="showPurchaseModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closePurchaseModal"></div>
      <div class="glass-card w-full max-w-5xl max-h-[90vh] overflow-y-auto rounded-[32px] p-8 relative z-10 border border-white/10 shadow-2xl" data-aos="zoom-in-up" data-aos-duration="1000">
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
  BuildingOfficeIcon, PhoneIcon, EnvelopeIcon, MapPinIcon, ShieldCheckIcon, 
  PlusIcon, FunnelIcon, MagnifyingGlassIcon, StarIcon, XMarkIcon, PencilIcon, TrashIcon,
  DocumentTextIcon
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

// SUPPLIER MODAL
const showSupplierModal = ref(false);
const isEditing = ref(false);
const editSupplierId = ref(null);
const formSupplier = ref({
  razon_social: '', nit: '', direccion: '', telefono: '', correo_electronico: '',
  contacto_principal: '', condicion_pago: 'Contado', dias_credito: ''
});

const openSupplierModal = () => {
  isEditing.value = false;
  formSupplier.value = { razon_social: '', nit: '', direccion: '', telefono: '', correo_electronico: '', contacto_principal: '', condicion_pago: 'Contado', dias_credito: '' };
  showSupplierModal.value = true;
};

const openEditSupplier = (sup) => {
  isEditing.value = true;
  editSupplierId.value = sup.id;
  formSupplier.value = { ...sup };
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
