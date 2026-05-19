<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-10">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Directorio de Proveedores</h2>
        <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Gestión de alianzas estratégicas y suministros</p>
      </div>
      <button class="glass-button-primary text-white py-4 px-10 rounded-2xl font-black text-xs uppercase tracking-widest flex items-center justify-center gap-3 shadow-2xl shadow-primary/20 hover:scale-105 transition-all">
        <PlusIcon class="w-5 h-5" />
        Añadir Proveedor
      </button>
    </div>

    <section class="glass-card rounded-[56px] overflow-hidden border border-white/5 shadow-2xl">
      <div class="p-12 border-b border-white/5 bg-white/5 backdrop-blur-3xl flex flex-col md:flex-row md:items-center justify-between gap-8">
        <div class="relative flex-1 max-w-lg">
          <MagnifyingGlassIcon class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-white/30" />
          <input 
            type="text" 
            placeholder="Buscar proveedores por nombre o especialidad..." 
            class="w-full glass-input rounded-2xl pl-14 pr-6 py-4 text-sm font-medium text-white outline-none focus:ring-2 focus:ring-primary/40 transition-all"
          />
        </div>
        <div class="flex items-center gap-4">
          <button class="h-14 px-8 rounded-2xl border border-white/10 flex items-center gap-3 text-xs font-black text-white/40 uppercase tracking-widest hover:bg-white/5 transition-all">
            <FunnelIcon class="w-5 h-5" /> Categorías
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-0 divide-x divide-y divide-white/5">
        <div 
          v-for="(sup, i) in suppliers" 
          :key="i" 
          @click="selectedSupplier = sup"
          class="p-12 cursor-pointer transition-all hover:bg-white/[0.02] group"
        >
          <div class="flex justify-between items-start mb-8">
            <div class="w-16 h-16 rounded-3xl bg-white/5 border border-white/10 flex items-center justify-center text-primary group-hover:scale-110 transition-transform shadow-2xl">
              <BuildingOfficeIcon class="w-8 h-8" />
            </div>
            <div class="flex items-center gap-1.5 px-3 py-1 bg-white/5 rounded-full border border-white/5">
              <StarIcon class="w-3 h-3 text-yellow-500 fill-yellow-500" />
              <span class="text-[10px] font-black text-white italic">{{ sup.rating }}</span>
            </div>
          </div>

          <h4 class="text-2xl font-black text-white italic uppercase tracking-tighter mb-2">{{ sup.name }}</h4>
          <p class="text-[10px] font-bold text-white/30 uppercase tracking-[0.2em] mb-8">{{ sup.cat }}</p>

          <div class="space-y-4 mb-10">
            <div class="flex items-center gap-4 text-white/60">
              <PhoneIcon class="w-4 h-4 text-primary" />
              <span class="text-sm font-medium">{{ sup.tel }}</span>
            </div>
            <div class="flex items-center gap-4 text-white/60">
              <EnvelopeIcon class="w-4 h-4 text-primary" />
              <span class="text-sm font-medium truncate">{{ sup.email }}</span>
            </div>
          </div>

          <div class="flex items-center justify-between pt-8 border-t border-white/5">
            <span :class="`px-4 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest border ${
              sup.status.includes('Socio') ? 'bg-primary/20 text-primary border-primary/20 shadow-[0_0_15px_#6366f130]' : 'bg-white/5 text-white/40 border-white/5'
            }`">
              {{ sup.status }}
            </span>
            <span class="text-[10px] font-black text-white/20 uppercase tracking-widest">{{ sup.reliability }} Confiabilidad</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Supplier Modal -->
    <transition name="fade">
      <div v-if="selectedSupplier" class="fixed inset-0 z-50 flex items-center justify-center p-6">
        <div 
          @click="selectedSupplier = null"
          class="absolute inset-0 bg-black/80 backdrop-blur-sm"
        ></div>
        
        <div class="relative w-full max-w-4xl glass-card rounded-[56px] p-12 border border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.5)] transform scale-100 transition-all duration-300">
          <div class="flex items-start justify-between">
            <div class="flex gap-8">
              <div class="w-24 h-24 rounded-[32px] bg-primary/20 flex items-center justify-center text-primary border border-white/10">
                <BuildingOfficeIcon class="w-12 h-12" />
              </div>
              <div>
                <h2 class="text-5xl font-black text-white italic uppercase tracking-tighter">{{ selectedSupplier.name }}</h2>
                <p class="text-xl font-bold text-primary mt-2 uppercase tracking-widest">{{ selectedSupplier.cat }}</p>
                <div class="flex items-center gap-6 mt-6">
                  <div class="flex items-center gap-2">
                    <MapPinIcon class="w-4 h-4 text-white/40" />
                    <span class="text-sm font-bold text-white/60">{{ selectedSupplier.address }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <ShieldCheckIcon class="w-4 h-4 text-primary" />
                    <span class="text-sm font-bold text-primary italic">Proveedor Verificado</span>
                  </div>
                </div>
              </div>
            </div>
            <button @click="selectedSupplier = null" class="w-14 h-14 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-all border border-white/5 text-white/40 hover:text-white">
              <XMarkIcon class="w-8 h-8" />
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-12">
            <div class="glass-card p-8 rounded-[32px] border border-white/5">
              <p class="text-[10px] font-black text-white/20 uppercase tracking-widest mb-4">Proyectos Activos</p>
              <p class="text-4xl font-black text-white italic">{{ selectedSupplier.activeProjects }}</p>
            </div>
            <div class="glass-card p-8 rounded-[32px] border border-white/5">
              <p class="text-[10px] font-black text-white/20 uppercase tracking-widest mb-4">Cumplimiento</p>
              <p class="text-4xl font-black text-white italic">{{ selectedSupplier.reliability }}</p>
            </div>
            <div class="glass-card p-8 rounded-[32px] border border-white/5">
              <p class="text-[10px] font-black text-white/20 uppercase tracking-widest mb-4">Calificación</p>
              <div class="flex items-center gap-3">
                <p class="text-4xl font-black text-white italic">{{ selectedSupplier.rating }}</p>
                <StarIcon class="w-6 h-6 text-yellow-500 fill-yellow-500" />
              </div>
            </div>
          </div>

          <div class="mt-12 flex gap-4">
            <button class="flex-1 glass-button-primary py-5 rounded-2xl font-black text-sm uppercase tracking-widest shadow-2xl">Nueva Orden de Compra</button>
            <button class="flex-1 py-5 rounded-2xl bg-white/5 border border-white/10 text-white font-black text-sm uppercase tracking-widest hover:bg-white/10 transition-all">Ver Catálogo Completo</button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { 
  BuildingOfficeIcon, PhoneIcon, EnvelopeIcon, MapPinIcon, GlobeAltIcon, 
  ShieldCheckIcon, EllipsisVerticalIcon, PlusIcon, FunnelIcon, 
  MagnifyingGlassIcon, StarIcon, XMarkIcon 
} from '@heroicons/vue/24/outline';

const selectedSupplier = ref(null);

const suppliers = [
  { 
    name: "Cementos Pro", 
    id: "SUP-001", 
    cat: "Materiales Base", 
    rating: 4.8, 
    status: "Preferido", 
    contact: "Carlos Ruiz", 
    tel: "+502 2300-4400", 
    email: "ventas@cementospro.gt",
    address: "Carretera al Salvador, Km 14",
    activeProjects: 3,
    reliability: "98%"
  },
  { 
    name: "Aceros de Guate", 
    id: "SUP-042", 
    cat: "Acero y Estructuras", 
    rating: 4.5, 
    status: "Activo", 
    contact: "Ana Morales", 
    tel: "+502 2315-9000", 
    email: "industrial@acerosgt.com",
    address: "Zona 12, Avenida Petapa",
    activeProjects: 5,
    reliability: "94%"
  },
  { 
    name: "Eléctricos Fuentes", 
    id: "SUP-112", 
    cat: "Instalaciones", 
    rating: 4.9, 
    status: "Socio Oro", 
    contact: "Jorge Fuentes", 
    tel: "+502 2201-3344", 
    email: "jorge@elecfuentes.gt",
    address: "Zona 4, Edificio Tec",
    activeProjects: 2,
    reliability: "100%"
  }
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
