<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-10">
    <!-- Executive Summary -->
    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div
        v-for="(metric, i) in metrics"
        :key="i"
        class="glass-card p-7 rounded-3xl flex flex-col justify-between h-44 cursor-pointer group hover:-translate-y-1.5 hover:scale-[1.02] transition-all duration-300"
      >
        <div class="flex justify-between items-start">
          <div :class="`p-3 rounded-2xl bg-${metric.color}/20 text-${metric.color} shadow-lg shadow-${metric.color}/20`">
            <component :is="metric.icon" class="w-6 h-6" />
          </div>
          <span :class="`text-${metric.color} text-[11px] font-bold tracking-wider px-2.5 py-1 rounded-full bg-white/5`">{{ metric.change }}</span>
        </div>
        <div>
          <p class="text-white/40 text-[10px] uppercase font-bold tracking-widest mb-1.5">{{ metric.label }}</p>
          <h3 class="text-3xl font-bold text-white group-hover:text-primary transition-colors">{{ metric.value }}</h3>
        </div>
      </div>
    </section>

    <!-- Main Grid: Financial & Alerts -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Mock Chart Area -->
      <div class="lg:col-span-2 glass-card p-10 rounded-[40px] h-[480px] flex flex-col relative overflow-hidden transition-all duration-300 hover:-translate-y-1.5">
        <div class="absolute top-0 right-0 w-64 h-64 bg-primary/20 blur-[100px] rounded-full -translate-y-1/2 translate-x-1/2"></div>
        
        <div class="flex justify-between items-center mb-12 relative z-10">
          <div>
            <h2 class="text-2xl font-bold text-white">Resumen Financiero</h2>
            <p class="text-white/50 text-sm mt-1">Desempeño Proyectado vs Real (últimos 6 meses)</p>
          </div>
          <div class="flex items-center gap-8">
            <div class="flex items-center gap-3">
              <span class="w-2.5 h-2.5 rounded-full bg-primary shadow-[0_0_10px_#6366f1]"></span>
              <span class="text-xs font-bold text-white/50 uppercase tracking-widest">Ingresos</span>
            </div>
            <div class="flex items-center gap-3">
              <span class="w-2.5 h-2.5 rounded-full bg-white/20"></span>
              <span class="text-xs font-bold text-white/50 uppercase tracking-widest">Gastos</span>
            </div>
          </div>
        </div>
        
        <div class="flex-1 flex items-end justify-between gap-6 px-4 pb-4 relative z-10">
          <div v-for="(month, i) in months" :key="month" class="flex-1 flex flex-col items-center gap-5 group h-full justify-end">
            <div class="w-full flex items-end gap-2.5 h-full">
              <div 
                :style="{ height: `${chartData1[i]}%` }"
                :class="`w-1/2 rounded-t-xl transition-all duration-700 ${i === 4 ? 'bg-primary shadow-[0_0_20px_rgba(99,102,241,0.4)]' : 'bg-primary/30 group-hover:bg-primary/50'}`"
              ></div>
              <div 
                :style="{ height: `${chartData2[i]}%` }"
                :class="`w-1/2 rounded-t-xl transition-all duration-700 ${i === 4 ? 'bg-white/40' : 'bg-white/10'}`"
              ></div>
            </div>
            <span :class="`text-xs font-bold tracking-widest uppercase ${i === 4 ? 'text-primary' : 'text-white/40'}`">{{ month }}</span>
          </div>
        </div>
      </div>

      <!-- Inventory Alerts -->
      <div class="glass-card p-10 rounded-[40px] flex flex-col border border-white/5 hover:border-tertiary/30 transition-all duration-300 hover:-translate-y-1.5">
        <div class="flex justify-between items-center mb-10">
          <h2 class="text-2xl font-bold text-white">Alertas de Inventario</h2>
          <span class="bg-tertiary/20 text-tertiary px-3 py-1.5 rounded-xl text-[11px] font-bold tracking-widest uppercase shadow-[0_0_15px_rgba(244,63,94,0.2)]">3 Críticas</span>
        </div>
        
        <div class="space-y-5 overflow-y-auto pr-3 custom-scrollbar flex-1">
          <div class="p-6 bg-white/5 border border-white/10 rounded-3xl group hover:bg-white/10 transition-all cursor-pointer">
            <div class="flex justify-between items-start mb-2">
              <h4 class="font-bold text-white">Acero Estructural (Viga H)</h4>
              <ExclamationTriangleIcon class="w-5 h-5 text-tertiary" />
            </div>
            <p class="text-sm text-white/50 leading-relaxed">Proyecto Delta: Stock por debajo del umbral mínimo del 15%.</p>
            <button class="mt-4 text-xs font-bold text-primary hover:underline uppercase tracking-widest">Pedir Ahora</button>
          </div>

          <div class="p-6 bg-white/5 border border-white/10 rounded-3xl group hover:bg-white/10 transition-all cursor-pointer">
            <div class="flex justify-between items-start mb-2">
              <h4 class="font-bold text-white">Concreto Premezclado</h4>
              <ClockIcon class="w-5 h-5 text-primary" />
            </div>
            <p class="text-sm text-white/50 leading-relaxed">Torre Skyline: Se espera retraso en entrega programada.</p>
            <button class="mt-4 text-xs font-bold text-primary hover:underline uppercase tracking-widest">Rastrear Envío</button>
          </div>

          <div class="p-6 bg-white/5 border border-white/10 rounded-3xl group hover:bg-white/10 transition-all cursor-pointer">
            <div class="flex justify-between items-start mb-2">
              <h4 class="font-bold text-white">Ladrillos de Pavimento</h4>
              <div class="w-4 h-4 rounded-full bg-white/20"></div>
            </div>
            <p class="text-sm text-white/50 leading-relaxed">Plaza Central: Stock bajo (25% restante).</p>
            <button class="mt-4 text-xs font-bold text-primary hover:underline uppercase tracking-widest">Ver Logística</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Row: Status & Operations -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pb-10">
      <!-- Project Status -->
      <div class="glass-card p-10 rounded-[40px] transition-all duration-300 hover:-translate-y-1.5">
        <h2 class="text-2xl font-bold text-white mb-10">Estado de Proyectos</h2>
        <div class="space-y-10">
          <div v-for="proj in projects" :key="proj.name">
            <div class="flex justify-between items-center mb-4">
              <span class="text-sm font-bold text-white tracking-wide">{{ proj.name }}</span>
              <span :class="`text-xs font-bold tracking-widest ${proj.progress > 80 ? 'text-tertiary' : 'text-primary'}`">{{ proj.progress }}%</span>
            </div>
            <div class="h-2.5 w-full bg-white/5 rounded-full overflow-hidden shadow-inner p-[2px]">
              <div 
                :style="{ width: `${proj.progress}%` }"
                :class="`h-full ${proj.color} rounded-full transition-all duration-1000`"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Operations Feed -->
      <div class="glass-card p-10 rounded-[40px] transition-all duration-300 hover:-translate-y-1.5">
        <h2 class="text-2xl font-bold text-white mb-10">Flujo de Operaciones</h2>
        <div class="space-y-10">
          <div v-for="(feed, i) in feeds" :key="i" class="flex gap-6 relative">
            <div v-if="i !== 2" class="absolute top-12 left-[23px] w-[1px] h-12 bg-white/10"></div>
            <div :class="`shrink-0 w-12 h-12 rounded-2xl ${feed.bgColor} flex items-center justify-center ${feed.iconColor} border border-white/10`">
              <component :is="feed.icon" class="w-6 h-6" />
            </div>
            <div class="pt-1.5">
              <p class="text-sm text-white/90 leading-relaxed"><span class="font-bold opacity-60">ConstructPro</span> Log: {{ feed.text }}</p>
              <p class="text-[10px] text-white/40 font-bold uppercase tracking-[0.2em] mt-3">{{ feed.date }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { 
  BanknotesIcon, DocumentTextIcon, WrenchScrewdriverIcon, ShieldCheckIcon, 
  ArrowTrendingUpIcon, ExclamationTriangleIcon, ClockIcon, DocumentCheckIcon 
} from '@heroicons/vue/24/outline';

const metrics = [
  { label: "Ingresos", value: "Q4.2M", change: "+12.5%", icon: BanknotesIcon, color: "primary" },
  { label: "Gastos", value: "Q2.8M", change: "+3.2%", icon: DocumentTextIcon, color: "tertiary" },
  { label: "Flota Activa", value: "124", change: "86 Activos", icon: WrenchScrewdriverIcon, color: "primary" },
  { label: "Puntaje de Seguridad", value: "98.2", change: "Excelente", icon: ShieldCheckIcon, color: "primary" },
];

const months = ["Ene", "Feb", "Mar", "Abr", "May", "Jun"];
const chartData1 = [40, 55, 45, 70, 85, 65];
const chartData2 = [30, 35, 40, 50, 60, 45];

const projects = [
  { name: "Skyline Tower - Fase 2", progress: 78, color: "bg-primary shadow-[0_0_15px_#6366f1]" },
  { name: "Restauración Gran Puente", progress: 45, color: "bg-primary shadow-[0_0_15px_#6366f1]" },
  { name: "Cimentación Parque Eco-Tech", progress: 92, color: "bg-tertiary shadow-[0_0_15px_#f43f5e]" },
  { name: "Condominios Riverside", progress: 12, color: "bg-white/20" },
];

const feeds = [
  { text: "Sarah Miller se unió a Proyecto Delta como Jefa de Sitio.", date: "Hoy, 10:45 AM", icon: ClockIcon, iconColor: "text-primary", bgColor: "bg-primary/20 shadow-[0_0_15px_#6366f130]" },
  { text: "Permiso Aprobado: Instalación de grúa verificado en Skyline Tower.", date: "Hoy, 08:30 AM", icon: DocumentCheckIcon, iconColor: "text-primary", bgColor: "bg-white/10" },
  { text: "Inspección de Seguridad: Auditoría de arneses marcada para puente.", date: "Ayer, 04:15 PM", icon: ExclamationTriangleIcon, iconColor: "text-tertiary", bgColor: "bg-tertiary/20 shadow-[0_0_15px_#f43f5e30]" },
];
</script>
