<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12">
    <!-- Metrics Section -->
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div
        v-for="(metric, i) in metrics"
        :key="i"
        class="glass-card p-8 rounded-3xl flex flex-col justify-between h-44 border border-white/5 transition-all cursor-pointer group hover:-translate-y-1.5 hover:scale-[1.02]"
      >
        <div>
          <p class="text-white/40 text-[11px] font-bold uppercase tracking-[0.2em] mb-4">{{ metric.label }}</p>
          <h3 :class="`text-3xl font-bold ${metric.color === 'text-error' ? 'text-tertiary' : 'text-white'}`">{{ metric.value }}</h3>
        </div>
        <div v-if="metric.percentage" class="w-full bg-white/5 h-2 rounded-full mt-6 overflow-hidden p-[1px]">
          <div 
            :style="{ width: `${metric.percentage}%` }"
            class="bg-primary h-full rounded-full shadow-[0_0_10px_#6366f1] transition-all duration-1000"
          ></div>
        </div>
        <div v-else :class="`flex items-center gap-2 mt-6 ${metric.color} bg-white/5 px-3 py-1.5 rounded-xl w-fit border border-white/5`">
          <component :is="metric.icon" v-if="metric.icon" class="w-4 h-4" />
          <span class="text-[10px] font-bold uppercase tracking-wider">{{ metric.trend }}</span>
        </div>
      </div>
    </section>

    <!-- Tabs -->
    <section>
      <div class="flex gap-12 border-b border-white/10 relative">
        <button
          v-for="tab in ['machinery', 'inventory']"
          :key="tab"
          @click="activeTab = tab"
          :class="`pb-6 text-xl font-bold transition-all relative ${
            activeTab === tab ? 'text-white' : 'text-white/40 hover:text-white/60'
          }`"
        >
          {{ tab === "machinery" ? "Maquinaria Pesada" : "Inventario de Materiales" }}
          <div v-if="activeTab === tab" class="absolute bottom-0 left-0 w-full h-1.5 bg-primary rounded-t-full shadow-[0_0_15px_#6366f1]"></div>
        </button>
      </div>
    </section>

    <!-- Machinery Content -->
    <transition name="fade-slide" mode="out-in">
      <section v-if="activeTab === 'machinery'" key="machinery" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div 
          v-for="(m, i) in machinery" 
          :key="i" 
          @click="selectedMachine = m"
          class="glass-card rounded-[40px] overflow-hidden group hover:-translate-y-2 transition-all duration-500 border border-white/10 cursor-pointer"
        >
          <div class="h-56 relative overflow-hidden">
            <img :src="m.img" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-1000" :alt="m.name" referrerpolicy="no-referrer" />
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
            <div class="absolute top-6 right-6 px-4 py-2 backdrop-blur-xl bg-black/40 rounded-2xl text-[10px] font-bold uppercase tracking-[0.2em] flex items-center gap-2.5 border border-white/20 shadow-xl">
              <span :class="`w-2.5 h-2.5 rounded-full ${m.statusColor === 'green' ? 'bg-primary shadow-[0_0_10px_#6366f1]' : 'bg-orange-500 shadow-[0_0_10px_#f97316]'}`"></span>
              {{ m.status }}
            </div>
          </div>
          <div class="p-10 relative">
            <div class="flex justify-between items-start mb-8">
              <div>
                <h4 class="text-2xl font-bold text-white tracking-tight">{{ m.name }}</h4>
                <p class="text-sm font-semibold text-white/40 mt-1 uppercase tracking-widest">{{ m.type }}</p>
              </div>
              <button class="p-3 bg-white/5 hover:bg-white/10 rounded-2xl transition-all text-white/40 hover:text-white border border-white/10">
                <EllipsisVerticalIcon class="w-5 h-5" />
              </button>
            </div>
            <div class="space-y-8">
              <div>
                <div class="flex items-center justify-between mb-3">
                  <span class="text-[10px] font-bold text-white/30 uppercase tracking-[0.2em]">Uso y Carga</span>
                  <span class="text-sm font-bold text-white tracking-widest">{{ m.fuel }}%</span>
                </div>
                <div class="w-full bg-white/5 h-3 rounded-full overflow-hidden shadow-inner p-[2px] border border-white/5">
                  <div 
                    :style="{ width: `${m.fuel}%` }"
                    :class="`h-full rounded-full transition-all duration-1000 ${m.fuel < 50 ? 'bg-tertiary shadow-[0_0_10px_#f43f5e]' : 'bg-primary shadow-[0_0_10px_#6366f1]'}`"
                  ></div>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-10 pt-2 border-t border-white/5">
                <div class="space-y-2">
                  <p class="text-[10px] text-white/30 uppercase font-bold tracking-[0.2em]">Ubicación</p>
                  <p class="text-sm font-bold text-white flex items-center gap-2.5 tracking-wide">
                    <MapPinIcon class="w-5 h-5 text-primary" /> {{ m.location }}
                  </p>
                </div>
                <div class="space-y-2">
                  <p class="text-[10px] text-white/30 uppercase font-bold tracking-[0.2em]">Servicio</p>
                  <p class="text-sm font-bold text-white flex items-center gap-2.5 tracking-wide text-nowrap">
                    <CalendarIcon class="w-5 h-5 text-primary" /> {{ m.maint }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section v-else key="inventory" class="glass-card rounded-[40px] overflow-hidden border border-white/10">
        <div class="overflow-x-auto px-4">
          <table class="w-full text-left">
            <thead>
              <tr class="border-b border-white/5">
                <th class="px-8 py-8 text-[11px] font-bold text-white/30 uppercase tracking-[0.2em]">Nombre del Material</th>
                <th class="px-8 py-8 text-[11px] font-bold text-white/30 uppercase tracking-[0.2em]">Stock Actual</th>
                <th class="px-8 py-8 text-[11px] font-bold text-white/30 uppercase tracking-[0.2em]">Punto de Reorden</th>
                <th class="px-8 py-8 text-[11px] font-bold text-white/30 uppercase tracking-[0.2em]">Tiempo de Entrega</th>
                <th class="px-8 py-8 text-[11px] font-bold text-white/30 uppercase tracking-[0.2em]">Estado</th>
                <th class="px-8 py-8 text-right"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-for="(inv, i) in inventory" :key="i" class="hover:bg-white/5 transition-all group">
                <td class="px-8 py-8">
                  <div class="flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-white/5 flex items-center justify-center text-white/40 group-hover:bg-primary/20 group-hover:text-primary transition-all border border-white/5 shadow-lg">
                      <component :is="inv.icon" class="w-7 h-7" />
                    </div>
                    <div>
                      <p class="font-bold text-white text-lg tracking-tight">{{ inv.name }}</p>
                      <p class="text-xs font-semibold text-white/40 tracking-widest uppercase mt-1">{{ inv.sub }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-8 font-bold text-white text-base">{{ inv.stock }}</td>
                <td class="px-8 py-8 font-semibold text-white/40">{{ inv.point }}</td>
                <td class="px-8 py-8 font-semibold text-white/70">{{ inv.time }}</td>
                <td class="px-8 py-8">
                  <span :class="`px-5 py-2 rounded-full text-[10px] font-extrabold uppercase tracking-widest border transition-all ${
                    inv.status === 'Óptimo' ? 'bg-primary/20 text-primary border-primary/20 shadow-[0_0_15px_rgba(99,102,241,0.1)]' :
                    inv.status === 'Advertencia' ? 'bg-orange-500/20 text-orange-400 border-orange-500/20 shadow-[0_0_15px_rgba(249,115,22,0.1)]' :
                    'bg-tertiary/20 text-tertiary border-tertiary/20 shadow-[0_0_15px_rgba(244,63,94,0.1)]'
                  }`">
                    {{ inv.status }}
                  </span>
                </td>
                <td class="px-8 py-8 text-right">
                  <button :class="`font-bold text-[10px] uppercase tracking-[0.2em] shadow-xl transition-all hover:scale-105 active:scale-95 ${inv.status === 'Stock Bajo' ? 'bg-primary text-white px-8 py-3.5 rounded-2xl shadow-primary/20' : 'text-primary hover:text-white px-8 py-3.5 border border-white/10 rounded-2xl'}`">
                    {{ inv.status === "Stock Bajo" ? "Pedir Ahora" : "Reabastecer" }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </transition>

    <!-- Machinery Details Modal -->
    <transition name="fade">
      <div v-if="selectedMachine" class="fixed inset-0 z-50 flex items-center justify-center p-6">
        <div 
          @click="selectedMachine = null"
          class="absolute inset-0 bg-black/80 backdrop-blur-sm"
        ></div>
        
        <div class="relative w-full max-w-4xl glass-card rounded-[56px] overflow-hidden border border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.5)] transform scale-100 transition-all duration-300">
          <button 
            @click="selectedMachine = null"
            class="absolute top-8 right-8 z-10 w-12 h-12 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-all border border-white/10 text-white/40 hover:text-white"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>

          <div class="flex flex-col lg:flex-row h-full max-h-[85vh] overflow-y-auto">
            <!-- Left: Media -->
            <div class="lg:w-1/2 relative bg-black/40">
              <img :src="selectedMachine.img" class="w-full h-full object-cover" :alt="selectedMachine.name" />
              <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent"></div>
              <div class="absolute bottom-10 left-10">
                <span class="text-[10px] font-black uppercase tracking-[0.4em] text-primary mb-2 block">Activo de Empresa</span>
                <h2 class="text-5xl font-black text-white italic uppercase tracking-tighter">{{ selectedMachine.name }}</h2>
                <p class="text-white/40 font-bold uppercase tracking-widest mt-2">{{ selectedMachine.type }}</p>
              </div>
            </div>

            <!-- Right: Info -->
            <div class="lg:w-1/2 p-12 bg-black/20 overflow-y-auto">
              <div class="space-y-10">
                <!-- Status & Health -->
                <div class="flex gap-4">
                  <div class="flex-1 glass-card p-6 rounded-3xl border border-white/5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/20 mb-4 flex items-center gap-2">
                      <ChartBarIcon class="w-4 h-4" /> Estado Operativo
                    </p>
                    <div class="flex items-center gap-3">
                      <div :class="`w-3 h-3 rounded-full ${selectedMachine.statusColor === 'green' ? 'bg-primary' : 'bg-orange-500'} shadow-[0_0_10px_currentColor]`"></div>
                      <span class="text-xl font-black italic uppercase text-white">{{ selectedMachine.status }}</span>
                    </div>
                  </div>
                  <div class="flex-1 glass-card p-6 rounded-3xl border border-white/5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/20 mb-4 flex items-center gap-2">
                      <ClockIcon class="w-4 h-4" /> Eficiencia de Ciclo
                    </p>
                    <span class="text-xl font-black italic uppercase text-white">{{ selectedMachine.fuel }}% de Carga</span>
                  </div>
                </div>

                <!-- Technical Specs -->
                <div>
                  <h5 class="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-6 flex items-center gap-3">
                    <div class="w-8 h-[1px] bg-white/10"></div> Especificaciones Técnicas
                  </h5>
                  <div class="grid grid-cols-3 gap-6">
                    <div v-for="(val, key) in selectedMachine.specs" :key="key" class="space-y-1">
                      <p class="text-[9px] font-black text-white/30 uppercase tracking-widest leading-tight">{{ key }}</p>
                      <p class="text-sm font-bold text-white">{{ val }}</p>
                    </div>
                  </div>
                </div>

                <!-- Personnel -->
                <div class="p-8 rounded-[32px] bg-white/5 border border-white/5 flex items-center justify-between">
                  <div class="flex items-center gap-5">
                    <div class="w-16 h-16 rounded-2xl bg-primary flex items-center justify-center text-white shadow-2xl shadow-primary/40">
                      <UserIcon class="w-8 h-8" />
                    </div>
                    <div>
                      <p class="text-[10px] font-black text-white/20 uppercase tracking-widest">Especialista Asignado</p>
                      <p class="text-xl font-black italic uppercase text-white tracking-tight">{{ selectedMachine.operator }}</p>
                    </div>
                  </div>
                  <ShieldCheckIcon class="w-8 h-8 text-primary/40" />
                </div>

                <!-- History -->
                <div>
                  <h5 class="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-6 flex items-center gap-3">
                    <div class="w-8 h-[1px] bg-white/10"></div> Libro de Mantenimiento
                  </h5>
                  <div class="space-y-4">
                    <div v-for="(h, i) in selectedMachine.history" :key="i" class="flex items-center justify-between py-4 border-b border-white/5">
                      <div class="flex items-center gap-4">
                        <ClockIcon class="w-5 h-5 text-white/20" />
                        <div>
                          <p class="text-sm font-bold text-white uppercase italic">{{ h.event }}</p>
                          <p class="text-[10px] font-bold text-white/30 uppercase tracking-widest">{{ h.date }}</p>
                        </div>
                      </div>
                      <span :class="`text-[10px] font-black uppercase tracking-widest px-4 py-1.5 rounded-lg border ${h.status === 'Aprobado' ? 'border-primary/20 text-primary' : 'border-tertiary/20 text-tertiary shadow-[0_0_10px_#f43f5e30]'}`">
                        {{ h.status }}
                      </span>
                    </div>
                  </div>
                </div>

                <button class="w-full glass-button-primary py-6 rounded-3xl font-black text-lg uppercase tracking-widest shadow-2xl shadow-primary/20 flex items-center justify-center gap-4 group">
                  <CalendarIcon class="w-6 h-6 group-hover:scale-110 transition-transform" />
                  Planificar Mantenimiento
                </button>
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
  ArrowTrendingUpIcon, ArrowTrendingDownIcon, WrenchScrewdriverIcon, ExclamationTriangleIcon, 
  EllipsisVerticalIcon, MapPinIcon, CalendarIcon, Square3Stack3DIcon, ListBulletIcon, 
  ArchiveBoxIcon, CubeIcon, XMarkIcon, UserIcon, ChartBarIcon, ClockIcon, ShieldCheckIcon 
} from '@heroicons/vue/24/outline';

const activeTab = ref("machinery");
const selectedMachine = ref(null);

const metrics = [
  { label: "Valuación de Inventario", value: "Q2.4M", trend: "+12% vs mes anterior", icon: ArrowTrendingUpIcon, color: "text-primary" },
  { label: "Tasa de Consumo", value: "840 kg/día", trend: "-4% brecha eficiencia", icon: ArrowTrendingDownIcon, color: "text-tertiary" },
  { label: "Maquinaria Activa", value: "42 / 48", percentage: 88, color: "text-primary" },
  { label: "Alertas Críticas", value: "03", trend: "Acción urgente requerida", icon: ExclamationTriangleIcon, color: "text-error" },
];

const machinery = [
  { 
    name: "CAT 320 GC", 
    type: "Excavadora • EX-042", 
    fuel: 78, 
    location: "Sector 7", 
    maint: "en 12d", 
    status: "Operativa", 
    statusColor: "green", 
    img: "https://lh3.googleusercontent.com/aida-public/AB6AXuCUyVqpheG5Xp0ccizgbsaN-FRTvOR-DITSCeVTA7EoZRxKtN8BaGvLEm4K2bZdWrJ5tOAeWz5F_bGpsfg_a9tnlAH1qrMMIDYM7e9QvAyIYcQ76WYoG6awOnUrgmbJNZkq4RFbBpxXhR2FjlbvHeUwUddgZEsXV5LWdlxVH4WEC-N6Em-53KeC_gtEo8RjRFSgI_NA0ZN3iG1QW9rmfy6i-SVhDrHc1FuZUq_0tUkg6xbNHYHbNFqVtFWMDwDNbOqOo1Ltim2__WE",
    specs: { hp: "145 HP", peso: "21,900 kg", profundidad: "6.72 m" },
    operator: "Roberto Jimenez",
    lastService: "Oct 12, 2023",
    history: [
      { date: "Oct 12", event: "Servicio Estándar", status: "Aprobado" },
      { date: "Ago 05", event: "Ajuste de Oruga", status: "Aprobado" },
      { date: "Jun 14", event: "Fuga Hidráulica", status: "Crítico" },
    ]
  },
  { 
    name: "Volvo FMX", 
    type: "Mixer de Concreto • MX-109", 
    fuel: 42, 
    location: "Depósito Base", 
    maint: "vencido", 
    status: "En Espera", 
    statusColor: "orange", 
    img: "https://lh3.googleusercontent.com/aida-public/AB6AXuCZkiAcuAATincPGGXI-CGRLBqB-HRPzN88_PZBKIi1keO0kHx-invtkIlWZnzGIAKaXVDxfqVrn7Tek4VguF8jzvyeu9vG_XK3yF8xWmVSYU3jWmkhp4lZ17esd5DrFgZGwEpAYapjCeGZVNRx1A6LSSqe4yqCNvD6wHfyfyad9KHzHTqnlADRU2t9bJqq8aEMmKYKJ4B3uSlV7d3G-Wfhdv_90cTFZaYBuVxIwHqQ5bASyAjcOTMFteOTIYqgZLcMz5BsGHrEwpM",
    specs: { hp: "420 HP", carga: "32,000 kg", capacidad: "12 m³" },
    operator: "Carla Martinez",
    lastService: "Sep 28, 2023",
    history: [
      { date: "Sep 28", event: "Inspección de Tambor", status: "Aprobado" },
      { date: "Jul 10", event: "Cambio de Aceite", status: "Aprobado" },
    ]
  },
  { 
    name: "Liebherr 280", 
    type: "Grúa Torre • TC-001", 
    fuel: 65, 
    location: "Sector 1", 
    maint: "en 45d", 
    status: "Activa", 
    statusColor: "green", 
    img: "https://lh3.googleusercontent.com/aida-public/AB6AXuDp9kl31qCayEdKXTEX0_x6wXDhGwwFK5wFB0QhP-zAOPD52lUXNxjBMMlVOkf6haDAzp8R9CUlrsXzck7cymtrCrHHYO40SPRWnN36XkWOL0ZiFeN4cGxpHlszuQXboVVhhn5Sm3qBxXAqZ4R0mTtDUd1yaMMF7zydLdDlUXZIqozMpJ3jHQEdiZLTLy4ku1j-2WQQNXoHTthDwWrkxoPtN8T8H_ORG9RneM_x0GRcfB9nUkLOfx7Lre-cuD0VNxAyLafYI6CPIKg",
    specs: { hp: "Eléctrico (90kW)", altura: "84 m", cargaMax: "12,000 kg" },
    operator: "David Chen",
    lastService: "Ene 15, 2024",
    history: [
      { date: "Ene 15", event: "Certificación Anual", status: "Aprobado" },
      { date: "Nov 02", event: "Tensado de Cables", status: "Aprobado" },
    ]
  },
];

const inventory = [
  { name: "Cemento (Grado A)", sub: "Sacos (50kg)", stock: "1,240 Unidades", point: "300 Unidades", time: "3 Días", status: "Óptimo", icon: Square3Stack3DIcon },
  { name: "Acero de Refuerzo", sub: "Toneladas", stock: "12.5 Tons", point: "15 Tons", time: "7 Días", status: "Stock Bajo", icon: ListBulletIcon },
  { name: "Arena de Río", sub: "Metros Cúbicos", stock: "450 m³", point: "100 m³", time: "2 Días", status: "Óptimo", icon: ArchiveBoxIcon },
  { name: "Grava Triturada", sub: "Metros Cúbicos", stock: "85 m³", point: "100 m³", time: "4 Días", status: "Advertencia", icon: CubeIcon },
];
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
</style>
