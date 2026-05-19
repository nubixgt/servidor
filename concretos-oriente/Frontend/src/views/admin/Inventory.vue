<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Control de Inventario</h2>
        <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Gestión centralizada de suministros y materiales</p>
      </div>
      <button class="glass-button-primary text-white py-4 px-10 rounded-2xl font-black text-xs uppercase tracking-widest flex items-center justify-center gap-3 shadow-2xl shadow-primary/20 hover:scale-105 transition-all">
        <PlusIcon class="w-5 h-5" />
        Registrar Entrada
      </button>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <div
        v-for="(m, i) in metrics"
        :key="i"
        class="glass-card p-10 rounded-[40px] border border-white/5 group relative overflow-hidden transition-all duration-300 hover:-translate-y-1.5"
      >
        <div :class="`absolute top-0 right-0 w-32 h-32 bg-${m.color}/10 blur-[60px] rounded-full translate-x-10 -translate-y-10 group-hover:bg-${m.color}/20 transition-all`"></div>
        <div class="relative z-10 flex items-start justify-between">
          <div>
            <p class="text-[10px] font-black text-white/30 uppercase tracking-[0.3em] mb-4">{{ m.label }}</p>
            <h3 class="text-4xl font-black text-white italic tracking-tighter">{{ m.value }}</h3>
            <div class="flex items-center gap-2 mt-4">
              <span :class="`text-[10px] font-black px-2 py-1 rounded-lg ${m.color === 'primary' ? 'bg-primary/20 text-primary' : 'bg-tertiary/20 text-tertiary'}`">
                {{ m.change }}
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
    <section class="glass-card rounded-[56px] overflow-hidden border border-white/5 shadow-2xl transition-all duration-300">
      <div class="p-12 border-b border-white/5 bg-white/5 backdrop-blur-3xl flex flex-col md:flex-row md:items-center justify-between gap-8">
        <div class="relative flex-1 max-w-lg">
          <MagnifyingGlassIcon class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-white/30" />
          <input 
            type="text" 
            placeholder="Buscar por código, nombre o categoría..." 
            class="w-full glass-input rounded-2xl pl-14 pr-6 py-4 text-sm font-medium text-white outline-none focus:ring-2 focus:ring-primary/40 transition-all"
          />
        </div>
        <div class="flex items-center gap-4">
          <button class="h-14 px-8 rounded-2xl border border-white/10 flex items-center gap-3 text-xs font-black text-white/40 uppercase tracking-widest hover:bg-white/5 transition-all">
            <FunnelIcon class="w-5 h-5" /> Filtros
          </button>
          <button class="h-14 px-8 rounded-2xl bg-white/5 border border-white/10 flex items-center gap-3 text-xs font-black text-white uppercase tracking-widest hover:bg-white/10 transition-all">
            Reporte de Stock
          </button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="text-[10px] font-black text-white/20 uppercase tracking-[0.3em]">
              <th class="px-12 py-8">Recurso / Código</th>
              <th class="px-12 py-8">Categoría</th>
              <th class="px-12 py-8">Disponibilidad</th>
              <th class="px-12 py-8 text-right">Valor en Libros</th>
              <th class="px-12 py-8">Proveedor</th>
              <th class="px-12 py-8"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr 
              v-for="(item, i) in inventoryItems" 
              :key="i" 
              @click="selectedItem = item"
              class="hover:bg-white/5 transition-all cursor-pointer group"
            >
              <td class="px-12 py-10">
                <div class="flex items-center gap-5">
                  <div class="w-12 h-12 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                    <CubeIcon class="w-6 h-6" />
                  </div>
                  <div>
                    <p class="font-black text-lg text-white italic tracking-tighter uppercase">{{ item.name }}</p>
                    <p class="text-[10px] font-bold text-white/30 uppercase tracking-[0.2em] mt-1">ID: {{ item.id }}</p>
                  </div>
                </div>
              </td>
              <td class="px-12 py-10">
                <span class="text-xs font-bold text-white/40 uppercase tracking-widest">{{ item.cat }}</span>
              </td>
              <td class="px-12 py-10">
                <div class="space-y-3">
                  <div class="flex items-center justify-between gap-10">
                    <span class="text-sm font-black text-white italic">{{ item.stock }} {{ item.unit }}</span>
                    <span :class="`text-[10px] font-black uppercase tracking-widest ${
                      item.color === 'green' ? 'text-primary' : 
                      item.color === 'orange' ? 'text-orange-400' : 'text-tertiary'
                    }`">
                      {{ item.status }}
                    </span>
                  </div>
                  <div class="w-32 h-1.5 bg-white/5 rounded-full overflow-hidden">
                    <div 
                      :class="`h-full ${
                        item.color === 'green' ? 'bg-primary' : 
                        item.color === 'orange' ? 'bg-orange-500' : 'bg-tertiary'
                      } shadow-[0_0_10px_currentColor]`"
                      :style="{ width: `${item.color === 'green' ? 85 : item.color === 'orange' ? 25 : 10}%` }"
                    ></div>
                  </div>
                </div>
              </td>
              <td class="px-12 py-10 text-right">
                <p class="font-black text-white italic text-lg">{{ item.total }}</p>
                <p class="text-[10px] font-bold text-white/30 uppercase tracking-widest mt-1">P/U: {{ item.price }}</p>
              </td>
              <td class="px-12 py-10">
                <p class="text-sm font-bold text-white uppercase italic">{{ item.supplier }}</p>
              </td>
              <td class="px-12 py-10 text-right">
                <button class="w-12 h-12 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-white/20 hover:text-white hover:bg-white/10 transition-all">
                  <EllipsisVerticalIcon class="w-5 h-5" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Item Detail Modal -->
    <transition name="fade">
      <div v-if="selectedItem" class="fixed inset-0 z-50 flex items-center justify-center p-6">
        <div 
          @click="selectedItem = null"
          class="absolute inset-0 bg-black/80 backdrop-blur-sm"
        ></div>
        
        <div class="relative w-full max-w-4xl glass-card rounded-[56px] overflow-hidden border border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.5)] bg-slate-950 transform scale-100 transition-all duration-300">
          <div class="flex flex-col lg:flex-row h-full max-h-[85vh]">
            <div class="flex-1 p-12 overflow-y-auto custom-scrollbar">
              <div class="flex justify-between items-start mb-12">
                <div>
                  <span class="text-[10px] font-black uppercase tracking-[0.4em] text-primary mb-3 block">Detalle de Recurso</span>
                  <h2 class="text-5xl font-black text-white italic uppercase tracking-tighter">{{ selectedItem.name }}</h2>
                  <p class="text-white/40 font-bold uppercase tracking-widest mt-2">{{ selectedItem.id }} • {{ selectedItem.cat }}</p>
                </div>
                <button @click="selectedItem = null" class="w-14 h-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-white/40 hover:text-white">
                  <XMarkIcon class="w-8 h-8" />
                </button>
              </div>

              <div class="grid grid-cols-2 gap-8 mb-12">
                <div class="glass-card p-8 rounded-[32px] border border-white/5">
                  <p class="text-[10px] font-black uppercase tracking-widest text-white/20 mb-4 flex items-center gap-2">
                    <Square3Stack3DIcon class="w-4 h-4" /> Existencia Actual
                  </p>
                  <h4 class="text-4xl font-black text-white italic">{{ selectedItem.stock }} {{ selectedItem.unit }}</h4>
                </div>
                <div class="glass-card p-8 rounded-[32px] border border-white/5">
                  <p class="text-[10px] font-black uppercase tracking-widest text-white/20 mb-4 flex items-center gap-2">
                    <ChartBarIcon class="w-4 h-4" /> Salud de Stock
                  </p>
                  <div class="flex items-center gap-3">
                    <div :class="`w-4 h-4 rounded-full ${
                      selectedItem.color === 'green' ? 'bg-primary shadow-[0_0_10px_#6366f1]' : 
                      selectedItem.color === 'orange' ? 'bg-orange-500 shadow-[0_0_10px_#f97316]' : 
                      'bg-tertiary shadow-[0_0_10px_#f43f5e]'
                    }`"></div>
                    <span class="text-2xl font-black text-white italic uppercase tracking-tight">{{ selectedItem.status }}</span>
                  </div>
                </div>
              </div>

              <div class="space-y-10">
                <div>
                  <h5 class="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-6 flex items-center gap-3">
                    <div class="w-8 h-[1px] bg-white/10"></div> Historial de Movimientos
                  </h5>
                  <div class="space-y-4">
                    <div v-for="(log, i) in [
                      { event: 'Ingreso de Lote', qty: '+500', date: 'May 12, 2024', user: 'Admin', icon: ArrowTrendingUpIcon },
                      { event: 'Salida a Proyecto Skyline', qty: '-120', date: 'May 10, 2024', user: 'Supervisor X', icon: ClockIcon },
                      { event: 'Ajuste de Auditoría', qty: '-4', date: 'May 08, 2024', user: 'Auditor', icon: ClockIcon },
                    ]" :key="i" class="flex items-center justify-between py-5 border-b border-white/5 group">
                      <div class="flex items-center gap-5">
                        <div :class="`w-10 h-10 rounded-xl flex items-center justify-center ${log.qty.startsWith('+') ? 'bg-primary/10 text-primary' : 'bg-white/5 text-white/30'}`">
                          <component :is="log.icon" class="w-5 h-5" />
                        </div>
                        <div>
                          <p class="text-sm font-bold text-white uppercase italic">{{ log.event }}</p>
                          <p class="text-[10px] font-black text-white/20 uppercase tracking-widest">{{ log.date }} • {{ log.user }}</p>
                        </div>
                      </div>
                      <span :class="`text-lg font-black italic tracking-tighter ${log.qty.startsWith('+') ? 'text-primary' : 'text-white'}`">
                        {{ log.qty }} {{ selectedItem.unit }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="lg:w-1/3 bg-white/[0.02] border-l border-white/5 p-12 overflow-y-auto">
              <div class="space-y-12">
                <div>
                  <h5 class="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-6">Información de Suministro</h5>
                  <div class="space-y-6">
                    <div class="space-y-2">
                      <p class="text-[9px] font-black text-white/30 uppercase tracking-[0.2em]">Proveedor Principal</p>
                      <p class="text-sm font-black text-white uppercase italic">{{ selectedItem.supplier }}</p>
                    </div>
                    <div class="space-y-2">
                      <p class="text-[9px] font-black text-white/30 uppercase tracking-[0.2em]">Tiempo de Reposición</p>
                      <p class="text-sm font-black text-white uppercase italic">3 a 5 días hábiles</p>
                    </div>
                    <div class="space-y-2">
                      <p class="text-[9px] font-black text-white/30 uppercase tracking-[0.2em]">Ubicación Almacén</p>
                      <p class="text-sm font-black text-white uppercase italic">Bodega Central - Pasillo B2</p>
                    </div>
                  </div>
                </div>

                <div class="flex flex-col gap-4">
                  <button class="w-full glass-button-primary py-5 rounded-2xl font-black text-sm uppercase tracking-widest shadow-2xl shadow-primary/20">
                    Generar Orden de Compra
                  </button>
                  <button class="w-full py-5 rounded-2xl bg-white/5 border border-white/10 text-white/40 font-black text-sm uppercase tracking-widest hover:text-white transition-all">
                    Editar Información
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { 
  CubeIcon, ExclamationTriangleIcon, ArrowTrendingUpIcon, MagnifyingGlassIcon, 
  FunnelIcon, EllipsisVerticalIcon, PlusIcon, ClockIcon, TruckIcon, 
  Square3Stack3DIcon, ChartBarIcon, XMarkIcon 
} from '@heroicons/vue/24/outline';

const selectedItem = ref(null);

const metrics = [
  { label: "Valor Total Stock", value: "Q4.8M", change: "+5.2%", icon: Square3Stack3DIcon, color: "primary" },
  { label: "Artículos Críticos", value: "12", change: "-2 esta semana", icon: ExclamationTriangleIcon, color: "tertiary" },
  { label: "Órdenes Pendientes", value: "08", change: "6 en tránsito", icon: TruckIcon, color: "primary" },
];

const inventoryItems = [
  { id: "MAT-001", name: "Cemento Portland Tipo I", cat: "Materiales Base", stock: 1250, unit: "Sacos", price: "Q85.00", total: "Q106,250", status: "Óptimo", color: "green", supplier: "Cementos Progreso" },
  { id: "MAT-082", name: "Varilla Corrugada 3/8", cat: "Acero", stock: 84, unit: "Quintales", price: "Q380.00", total: "Q31,920", status: "Bajo Stock", color: "orange", supplier: "Aceros de Guate" },
  { id: "MAT-154", name: "Pintura Acrílica Blanca", cat: "Acabados", stock: 12, unit: "Galones", price: "Q125.00", total: "Q1,500", status: "Crítico", color: "red", supplier: "Pinturas Volcán" },
  { id: "MAT-201", name: "Tubería PVC 2' - 160PSI", cat: "Fontanería", stock: 450, unit: "Tubos", price: "Q45.00", total: "Q20,250", status: "Óptimo", color: "green", supplier: "Amanco Guatemala" },
];
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
