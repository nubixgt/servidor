<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 min-h-screen text-white">
    <header class="flex flex-col md:flex-row md:items-end justify-between gap-10 bg-white/5 p-10 rounded-[48px] border border-white/10 backdrop-blur-xl">
      <div class="space-y-3">
        <h1 class="text-5xl font-black tracking-tighter uppercase italic">Portafolio de Proyectos</h1>
        <p class="text-white/60 text-lg font-medium leading-relaxed max-w-xl">Supervisión de ciclos de vida de construcción y desempeño de socios en todos los sitios activos con analítica en tiempo real.</p>
      </div>

      <div class="flex p-2 bg-black/20 rounded-[28px] shadow-inner border border-white/10 backdrop-blur-xl">
        <button 
          @click="view = 'projects'"
          :class="`px-10 py-3.5 rounded-2xl text-xs font-black uppercase tracking-widest transition-all ${
            view === 'projects' ? 'bg-primary text-white shadow-[0_0_20px_rgba(99,102,241,0.4)]' : 'text-white/40 hover:text-white'
          }`"
        >
          Proyectos Activos
        </button>
        <button 
          @click="view = 'providers'"
          :class="`px-10 py-3.5 rounded-2xl text-xs font-black uppercase tracking-widest transition-all ${
            view === 'providers' ? 'bg-primary text-white shadow-[0_0_20px_rgba(99,102,241,0.4)]' : 'text-white/40 hover:text-white'
          }`"
        >
          Proveedores de Servicios
        </button>
      </div>
    </header>

    <transition name="fade-slide" mode="out-in">
      <section v-if="view === 'projects'" key="projects" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
        <div
          v-for="(proj, i) in projects"
          :key="i"
          @click="selectedProject = proj"
          class="glass-card rounded-[48px] overflow-hidden group cursor-pointer border border-white/10 shadow-[0_24px_48px_-12px_rgba(0,0,0,0.5)] flex flex-col h-full hover:-translate-y-2 transition-transform duration-300"
        >
          <div class="h-56 relative overflow-hidden shrink-0">
            <img :src="proj.img" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-1000" :alt="proj.name" referrerpolicy="no-referrer" />
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
            <div class="absolute top-6 right-6 px-4 py-2 backdrop-blur-2xl bg-white/10 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] border border-white/20 shadow-xl">
              <div class="flex items-center gap-2.5">
                <span :class="`w-2 h-2 rounded-full ${proj.statusColor.includes('primary') ? 'bg-primary' : proj.statusColor.includes('tertiary') ? 'bg-tertiary' : 'bg-white/40'}`"></span>
                {{ proj.status }}
              </div>
            </div>
          </div>

          <div class="p-10 flex flex-col flex-1">
            <div class="flex justify-between items-start mb-6">
              <div class="w-14 h-14 bg-primary/20 rounded-2xl flex items-center justify-center text-primary border border-white/10 shadow-lg group-hover:shadow-primary/20 transition-all">
                <component :is="proj.icon" class="w-7 h-7" />
              </div>
              <button class="p-3 bg-white/5 hover:bg-white/10 rounded-xl transition-all">
                <EllipsisVerticalIcon class="w-5 h-5 text-white/20" />
              </button>
            </div>

            <h3 class="text-2xl font-black text-white mb-2 leading-tight uppercase italic">{{ proj.name }}</h3>
            <p class="text-xs font-bold text-white/40 mb-10 flex items-center gap-2.5 uppercase tracking-widest">
              <UserIcon class="w-4 h-4 text-primary" /> Lider: {{ proj.lead }}
            </p>

            <div class="space-y-4 mb-10 mt-auto">
              <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-[0.3em] text-white/30">
                <span>Progreso del Proyecto</span>
                <span class="text-white font-black italic text-sm">{{ proj.progress }}%</span>
              </div>
              <div class="w-full h-3 bg-white/5 rounded-full overflow-hidden shadow-inner p-[2px] border border-white/5">
                <div 
                  :style="{ width: `${proj.progress}%` }"
                  :class="`h-full rounded-full transition-all duration-1000 ${proj.progress > 80 ? 'bg-tertiary shadow-[0_0_10px_#f43f5e]' : 'bg-primary shadow-[0_0_10px_#6366f1]'}`"
                ></div>
              </div>
            </div>

            <div class="flex items-center justify-between pt-8 border-t border-white/5">
              <div>
                <p class="text-[10px] text-white/30 uppercase font-bold tracking-[0.2em] mb-2">Salud Fiscal</p>
                <div :class="`flex items-center gap-2 font-black text-sm uppercase tracking-tighter italic ${proj.budgetColor}`">
                  <ArrowTrendingUpIcon v-if="proj.budget.includes('Sobre')" class="w-5 h-5" />
                  <CurrencyDollarIcon v-else class="w-5 h-5" />
                  <span :class="proj.highlightedBudget ? 'bg-primary text-white px-3 py-1 rounded-lg text-xs' : ''">{{ proj.budget }}</span>
                </div>
              </div>
              <button class="w-12 h-12 rounded-2xl bg-white/5 hover:bg-primary transition-all flex items-center justify-center group-hover:shadow-[0_0_20px_rgba(99,102,241,0.2)]">
                <ChevronRightIcon class="w-6 h-6 text-white/40 group-hover:text-white" />
              </button>
            </div>
          </div>
        </div>
      </section>

      <section v-else key="providers" class="glass-card rounded-[48px] overflow-hidden border border-white/10">
        <div class="overflow-x-auto px-6">
          <table class="w-full text-left">
            <thead>
              <tr class="border-b border-white/5">
                <th class="px-10 py-10 text-[11px] font-black text-white/30 uppercase tracking-[0.3em]">Entidad Socia</th>
                <th class="px-10 py-10 text-[11px] font-black text-white/30 uppercase tracking-[0.3em]">Unidad Operativa</th>
                <th class="px-10 py-10 text-[11px] font-black text-white/30 uppercase tracking-[0.3em]">Contacto Seguro</th>
                <th class="px-10 py-10 text-[11px] font-black text-white/30 uppercase tracking-[0.3em]">Valuación Pendiente</th>
                <th class="px-10 py-10 text-[11px] font-black text-white/30 uppercase tracking-[0.3em]">Verificación</th>
                <th class="px-10 py-10"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-for="(p, i) in providers" :key="i" class="hover:bg-white/5 transition-all group cursor-pointer">
                <td class="px-10 py-10">
                  <div class="flex items-center gap-6">
                    <div :class="`w-14 h-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center font-black text-lg group-hover:bg-primary group-hover:text-white transition-all shadow-xl ${p.isCritical ? 'text-tertiary' : 'text-primary'}`">
                      {{ p.initials }}
                    </div>
                    <div>
                      <p class="font-black text-xl text-white tracking-tight italic uppercase">{{ p.name }}</p>
                      <p class="text-[10px] text-white/30 uppercase font-bold tracking-[0.2em] mt-1.5">ID Reg: {{ p.id }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-10 py-10">
                  <p class="text-sm font-bold text-white/70 uppercase tracking-widest">{{ p.service }}</p>
                </td>
                <td class="px-10 py-10">
                  <p class="text-sm font-bold text-white truncate max-w-[150px]">{{ p.email }}</p>
                  <p class="text-xs font-bold text-white/30 uppercase tracking-widest mt-1.5">{{ p.tel }}</p>
                </td>
                <td class="px-10 py-10">
                  <p :class="`text-xl font-black italic ${p.isCritical ? 'text-tertiary' : 'text-white'}`">{{ p.amount }}</p>
                  <p :class="`text-[10px] font-black uppercase tracking-[0.2em] mt-2 ${p.isCritical ? 'text-tertiary/60' : 'text-white/30'}`">{{ p.count }}</p>
                </td>
                <td class="px-10 py-10">
                  <span :class="`px-6 py-2 rounded-2xl text-[10px] font-black uppercase tracking-widest border transition-all ${
                    p.status === 'Preferido' ? 'bg-primary/20 text-primary border-primary/20 shadow-[0_0_15px_rgba(99,102,241,0.2)]' : 
                    p.status === 'Crítico' ? 'bg-tertiary/20 text-tertiary border-tertiary/20 shadow-[0_0_15px_rgba(244,63,94,0.2)]' : 
                    'bg-white/10 text-white/60 border-white/10'
                  }`">
                    {{ p.status }}
                  </span>
                </td>
                <td class="px-10 py-10 text-right">
                  <button class="w-12 h-12 bg-white/5 rounded-2xl flex items-center justify-center text-white/20 hover:text-white transition-all hover:bg-white/10">
                    <EllipsisVerticalIcon class="w-6 h-6" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </transition>

    <!-- Project Details Modal -->
    <transition name="fade">
      <div v-if="selectedProject" class="fixed inset-0 z-50 flex items-center justify-center p-6">
        <div 
          @click="selectedProject = null"
          class="absolute inset-0 bg-black/80 backdrop-blur-sm"
        ></div>
        
        <div class="relative w-full max-w-4xl glass-card rounded-[56px] overflow-hidden border border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.5)] transform scale-100 transition-all duration-300">
          <button 
            @click="selectedProject = null"
            class="absolute top-8 right-8 z-10 w-12 h-12 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-all border border-white/10 text-white/40 hover:text-white"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>

          <div class="flex flex-col lg:flex-row h-full max-h-[85vh] overflow-y-auto">
            <!-- Left: Media -->
            <div class="lg:w-1/2 relative bg-black/40">
              <img :src="selectedProject.img" class="w-full h-full object-cover" :alt="selectedProject.name" />
              <div class="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent"></div>
              <div class="absolute bottom-10 left-10">
                <span class="text-[10px] font-black uppercase tracking-[0.4em] text-primary mb-2 block">Activo de Empresa</span>
                <h2 class="text-5xl font-black text-white italic uppercase tracking-tighter">{{ selectedProject.name }}</h2>
                <p class="text-white/40 font-bold uppercase tracking-widest mt-2">LIDERADO POR {{ selectedProject.lead }}</p>
              </div>
            </div>

            <!-- Right: Info -->
            <div class="lg:w-1/2 p-12 bg-black/20 overflow-y-auto">
              <div class="space-y-10">
                <!-- Status & Velocity -->
                <div class="flex gap-4">
                  <div class="flex-1 glass-card p-6 rounded-3xl border border-white/5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/20 mb-4 flex items-center gap-2">
                      <ChartBarIcon class="w-4 h-4" /> Estado Actual
                    </p>
                    <div class="flex items-center gap-3">
                      <div :class="`w-3 h-3 rounded-full ${selectedProject.statusColor === 'primary' ? 'bg-primary' : 'bg-tertiary'} shadow-[0_0_10px_currentColor]`"></div>
                      <span class="text-xl font-black italic uppercase text-white">{{ selectedProject.status }}</span>
                    </div>
                  </div>
                  <div class="flex-1 glass-card p-6 rounded-3xl border border-white/5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/20 mb-4 flex items-center gap-2">
                      <ArrowTrendingUpIcon class="w-4 h-4" /> Progreso
                    </p>
                    <span class="text-xl font-black italic uppercase text-white">{{ selectedProject.progress }}%</span>
                  </div>
                </div>

                <!-- Quick Info -->
                <div class="grid grid-cols-2 gap-6">
                  <div class="space-y-2">
                    <p class="text-[10px] font-black text-white/30 uppercase tracking-widest flex items-center gap-2"><MapPinIcon class="w-3 h-3" /> Ubicación</p>
                    <p class="text-sm font-bold text-white">{{ selectedProject.location }}</p>
                  </div>
                  <div class="space-y-2">
                    <p class="text-[10px] font-black text-white/30 uppercase tracking-widest flex items-center gap-2"><CalendarIcon class="w-3 h-3" /> Inicio de Obra</p>
                    <p class="text-sm font-bold text-white">{{ selectedProject.startDate }}</p>
                  </div>
                </div>

                <!-- Milestones -->
                <div>
                  <h5 class="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-6 flex items-center gap-3">
                    <div class="w-8 h-[1px] bg-white/10"></div> Hitos del Proyecto
                  </h5>
                  <div class="space-y-4">
                    <div v-for="(m, i) in selectedProject.milestones" :key="i" class="flex items-center justify-between py-4 border-b border-white/5">
                      <div class="flex items-center gap-4">
                        <CheckCircleIcon :class="`w-5 h-5 ${m.status === 'Completado' ? 'text-primary' : 'text-white/20'}`" />
                        <p class="text-sm font-bold text-white uppercase italic">{{ m.name }}</p>
                      </div>
                      <span :class="`text-[9px] font-black uppercase tracking-widest px-3 py-1 rounded-lg ${m.status === 'Completado' ? 'bg-primary/10 text-primary' : 'bg-white/5 text-white/30'}`">
                        {{ m.status }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="p-8 rounded-[32px] bg-white/5 border border-white/5 flex items-center justify-between">
                  <div class="flex items-center gap-5">
                    <div class="w-16 h-16 rounded-2xl bg-primary/20 border border-white/10 flex items-center justify-center text-primary">
                      <BriefcaseIcon class="w-8 h-8" />
                    </div>
                    <div>
                      <p class="text-[10px] font-black text-white/30 uppercase tracking-widest">Presupuesto de Obra</p>
                      <p :class="`text-xl font-black italic uppercase italic tracking-tight ${selectedProject.budgetColor}`">{{ selectedProject.budget }}</p>
                    </div>
                  </div>
                  <ShieldCheckIcon class="w-8 h-8 text-primary/40" />
                </div>

                <button class="w-full glass-button-primary py-6 rounded-3xl font-black text-lg uppercase tracking-widest shadow-2xl shadow-primary/20 flex items-center justify-center gap-4">
                  Ver Reporte Detallado
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <button class="fixed bottom-12 right-12 h-20 w-20 rounded-[32px] glass-button-primary text-white shadow-2xl shadow-primary/40 flex items-center justify-center hover:scale-110 active:scale-95 transition-all z-40 group">
      <PlusIcon class="w-10 h-10 group-hover:rotate-90 transition-transform duration-500 shadow-[0_0_20px_rgba(99,102,241,0.5)]" />
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { 
  BuildingOfficeIcon, BuildingLibraryIcon, WrenchScrewdriverIcon, UserIcon, 
  ChevronRightIcon, CheckCircleIcon, ArrowTrendingUpIcon, CurrencyDollarIcon, 
  EllipsisVerticalIcon, PlusIcon, XMarkIcon, CalendarIcon, MapPinIcon, 
  ChartBarIcon, ShieldCheckIcon, BriefcaseIcon 
} from '@heroicons/vue/24/outline';

const view = ref("projects");
const selectedProject = ref(null);

const projects = [
  { 
    name: "Torre Skyline Heights A", 
    lead: "Marcus Sterling", 
    progress: 68, 
    status: "Ruta Crítica", 
    statusColor: "tertiary", 
    icon: BuildingOfficeIcon, 
    budget: "+4.2% Sobre", 
    budgetColor: "text-tertiary", 
    img: "https://images.unsplash.com/photo-1541888946425-d81bb19480c5?auto=format&fit=crop&q=80&w=2070",
    location: "Zona 10, Ciudad de Guatemala",
    startDate: "Ene 2024",
    milestones: [
      { name: "Cimentación", status: "Completado" },
      { name: "Estructura Base", status: "Completado" },
      { name: "Instalaciones Eléctricas", status: "En Progreso" }
    ]
  },
  { 
    name: "Centro Logístico Oak Creek", 
    lead: "Sarah Chen", 
    progress: 12, 
    status: "En Planificación", 
    statusColor: "white/20", 
    icon: BuildingLibraryIcon, 
    budget: "En Tiempo", 
    budgetColor: "text-primary", 
    img: "https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&q=80&w=2070",
    location: "San José Pinula",
    startDate: "Mar 2024",
    milestones: [
      { name: "Permisos Ambientales", status: "Completado" },
      { name: "Preparación de Terreno", status: "En Progreso" }
    ]
  },
  { 
    name: "Muelle Harborview", 
    lead: "James Wilson", 
    progress: 89, 
    status: "Obra Activa", 
    statusColor: "primary", 
    icon: WrenchScrewdriverIcon, 
    budget: "-2.1% Ahorro", 
    budgetColor: "text-primary", 
    highlightedBudget: true, 
    img: "https://images.unsplash.com/photo-1541123437800-1bb1317badc2?auto=format&fit=crop&q=80&w=2070",
    location: "Puerto Barrios, Izabal",
    startDate: "Oct 2023",
    milestones: [
      { name: "Estructura de Muelles", status: "Completado" },
      { name: "Acabados de Exterior", status: "En Progreso" }
    ]
  },
];

const providers = [
  { name: "Elite Concrete Co.", id: "PR-092", service: "Estructuras y Cimientos", email: "ops@elite.com", tel: "+1 (555) 012-3456", amount: "Q12,450.00", count: "2 Pendientes", status: "Preferido", initials: "EC" },
  { name: "Vantage Steel Corp", id: "PR-114", service: "Sistemas Reforzados", email: "intel@vantage.io", tel: "+1 (555) 987-6543", amount: "Q45,200.00", count: "Atrasado", status: "Crítico", initials: "VS", isCritical: true },
  { name: "Apex Wiring Ltd.", id: "PR-042", service: "Infraestructura Eléctrica", email: "support@apex.systems", tel: "+1 (555) 444-3210", amount: "Q3,100.00", count: "1 En Proceso", status: "Verificado", initials: "AW" },
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
