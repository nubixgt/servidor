<template>
  <div class="pt-20 pb-10 px-4 md:px-10 md:pb-20 max-w-7xl mx-auto space-y-10">
    <div class="space-y-3">
      <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Control de Estatus</h2>
      <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Monitoreo operativo de flota y herramientas</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div 
        v-for="(stat, i) in stats" 
        :key="i" 
        class="glass-card p-8 rounded-[32px] border border-white/5 group" data-aos="zoom-in-up" data-aos-duration="1000"
      >
        <div :class="`w-10 h-10 rounded-xl bg-${stat.color === 'primary' ? 'primary' : stat.color}/20 flex items-center justify-center text-${stat.color === 'primary' ? 'primary' : stat.color} mb-6`">
          <component :is="stat.icon" class="w-5 h-5" />
        </div>
        <p class="text-[10px] font-black text-white/30 uppercase tracking-widest mb-1">{{ stat.label }}</p>
        <h4 class="text-3xl font-black text-white italic transition-all group-hover:scale-105">{{ stat.count }}</h4>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6">
      <div 
        v-for="(mq, i) in machinery" 
        :key="i"
        @click="selectedMachine = mq"
        class="glass-card p-8 rounded-[40px] border border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-8 group hover:border-primary/30 transition-all cursor-pointer hover:-translate-y-3 hover:scale-105 hover:shadow-[0_20px_40px_-15px_rgba(99,102,241,0.5)]" data-aos="zoom-in-up" data-aos-duration="1000"
      >
        <div class="flex items-center gap-8">
          <div class="w-16 h-16 rounded-3xl bg-white/5 border border-white/10 flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
            <TruckIcon class="w-8 h-8" />
          </div>
          <div>
            <div class="flex items-center gap-3">
              <h3 class="text-2xl font-black text-white italic uppercase tracking-tighter">{{ mq.name }}</h3>
              <span class="px-3 py-1 bg-white/5 rounded-lg text-[9px] font-black text-white/40 uppercase tracking-widest">{{ mq.code }}</span>
            </div>
            <div class="flex items-center gap-6 mt-2">
              <div class="flex items-center gap-2 text-white/30">
                <MapPinIcon class="w-4 h-4 text-primary" />
                <span class="text-xs font-bold">{{ mq.location }}</span>
              </div>
              <div class="flex items-center gap-2 text-white/30">
                <ClockIcon class="w-4 h-4 text-primary" />
                <span class="text-xs font-bold">{{ mq.lastLog }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-12">
          <div class="text-right">
            <p class="text-[10px] font-black text-white/20 uppercase tracking-widest mb-2 text-center md:text-right">Salud de Equipo</p>
            <div class="flex items-center gap-3">
              <div class="w-24 h-1.5 bg-white/5 rounded-full overflow-hidden">
                <div class="h-full bg-primary" :style="{ width: `${mq.health}%` }"></div>
              </div>
              <span class="text-sm font-black text-white italic">{{ mq.health }}%</span>
            </div>
          </div>

          <div class="flex flex-col items-end">
            <span :class="`px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border ${
              mq.status === 'En Bodega' ? 'bg-primary/20 text-primary border-primary/20' : 
              mq.status === 'En Servicio' ? 'bg-orange-500/20 text-orange-400 border-orange-500/20' :
              'bg-white/5 text-white/40 border-white/10'
            }`">
              {{ mq.status }}
            </span>
            <button class="mt-4 text-[10px] font-black text-primary hover:underline uppercase tracking-[0.2em] flex items-center gap-2 group/btn">
              Actualizar Registro <ArrowsRightLeftIcon class="w-3 h-3 group-hover/btn:translate-x-1 transition-transform" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <transition name="fade">
      <div v-if="selectedMachine" class="fixed inset-0 z-50 flex items-center justify-center p-6">
        <div 
          @click="selectedMachine = null"
          class="absolute inset-0 bg-black/80 backdrop-blur-md"
        ></div>
        
        <div class="relative w-full max-w-2xl glass-card rounded-[56px] p-12 border border-white/10 shadow-[0_0_100px_rgba(99,102,241,0.15)] bg-slate-950 transform scale-100 transition-all duration-500" data-aos="zoom-in-up" data-aos-duration="1000">
          <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter mb-2">Reportar Cambio de Estado</h2>
          <p class="text-white/40 font-bold uppercase tracking-widest text-xs mb-10">{{ selectedMachine.name }} • {{ selectedMachine.code }}</p>
          
          <div class="grid grid-cols-2 gap-4 mb-8">
            <button 
              v-for="action in actionButtons" 
              :key="action.id"
              class="p-6 bg-white/5 border border-white/5 rounded-3xl flex flex-col items-center gap-4 hover:border-primary/50 hover:bg-primary/10 transition-all group"
            >
              <component :is="action.icon" class="w-8 h-8 text-white/40 group-hover:text-primary transition-colors" />
              <span class="text-[10px] font-black text-white uppercase tracking-widest">{{ action.label }}</span>
            </button>
          </div>

          <div class="space-y-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/20 uppercase tracking-widest ml-4">Observaciones Técnicas</label>
              <textarea 
                class="w-full glass-input rounded-2xl p-6 text-sm outline-none focus:ring-2 focus:ring-primary/40 min-h-[120px]"
                placeholder="Describe el estado técnico o motivos del movimiento..."
              ></textarea>
            </div>
            
            <div class="flex gap-4">
              <button class="flex-1 glass-button-primary py-5 rounded-2xl font-black text-sm uppercase tracking-widest shadow-2xl">Confirmar Movimiento</button>
              <button 
                @click="selectedMachine = null"
                class="px-8 py-5 rounded-2xl bg-white/5 border border-white/10 text-white/40 font-black text-sm uppercase tracking-widest hover:bg-white/10 transition-all"
              >
                Cancelar
              </button>
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
  WrenchScrewdriverIcon, TruckIcon, ClockIcon, MapPinIcon, 
  CheckCircleIcon, ExclamationTriangleIcon, ArrowsRightLeftIcon, 
  ArchiveBoxIcon, WrenchIcon 
} from '@heroicons/vue/24/outline';

const selectedMachine = ref(null);

const stats = [
  { label: "En Bodega", count: 42, icon: ArchiveBoxIcon, color: "primary" },
  { label: "En Operación", count: 86, icon: WrenchScrewdriverIcon, color: "primary" },
  { label: "En Mantenimiento", count: 8, icon: WrenchIcon, color: "orange-500" },
  { label: "Fuera de Servicio", count: 3, icon: ExclamationTriangleIcon, color: "tertiary" },
];

const machinery = [
  { id: "MQ-882", name: "Excavadora Volvo EC220D", status: "En Bodega", lastLog: "Ayer, 4:00 PM", code: "EXT-01", location: "Bodega Central", health: 95 },
  { id: "MQ-124", name: "Grúa Torre Potain MCT 205", status: "En Sitio", lastLog: "Hoy, 8:15 AM", code: "CRN-05", location: "Skyline Tower", health: 88 },
  { id: "MQ-451", name: "Cargador Frontal CAT 950K", status: "En Servicio", lastLog: "Hace 2 días", code: "LDR-02", location: "Taller Externo", health: 65 },
  { id: "MQ-302", name: "Camión Volteo Hino 500", status: "Transbordo", lastLog: "Hoy, 10:30 AM", code: "TRK-12", location: "Ruta CR-10", health: 92 },
];

const actionButtons = [
  { id: 'bodega', label: 'Retorno a Bodega', icon: ArchiveBoxIcon },
  { id: 'servicio', label: 'Envío a Servicio', icon: WrenchIcon },
  { id: 'sitio', label: 'Asignar a Sitio', icon: MapPinIcon },
  { id: 'baja', label: 'Reportar Avería', icon: ExclamationTriangleIcon },
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
