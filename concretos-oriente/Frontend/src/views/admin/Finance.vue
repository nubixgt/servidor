<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">
    <!-- KPIs Section -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <div
        v-for="(kpi, i) in kpis"
        :key="i"
        class="glass-card p-10 rounded-[40px] flex flex-col justify-between h-48 cursor-pointer group hover:-translate-y-1.5 hover:scale-[1.02] transition-all duration-300"
      >
        <div class="flex justify-between items-start">
          <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.3em]">{{ kpi.label }}</span>
          <div :class="`bg-${kpi.color}/20 p-4 rounded-2xl text-${kpi.color} shadow-lg shadow-${kpi.color}/20 border border-white/10`">
            <component :is="kpi.icon" class="w-6 h-6" />
          </div>
        </div>
        <div class="mt-auto">
          <h2 :class="`text-4xl font-black tracking-tighter italic ${kpi.label.includes('Pasivos') ? 'text-tertiary' : 'text-white'}`">{{ kpi.value }}</h2>
          <div :class="`flex items-center gap-2.5 mt-3 text-[10px] font-black uppercase tracking-widest ${kpi.label.includes('Pasivos') ? 'text-tertiary shadow-[0_0_10px_#f43f5e30]' : 'text-primary shadow-[0_0_10px_#6366f130]'}`">
            <ArrowTrendingUpIcon v-if="kpi.label.includes('Liquidez')" class="w-4 h-4" />
            {{ kpi.sub }}
          </div>
        </div>
      </div>
    </section>

    <!-- Charts Row -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-10">
      <div class="lg:col-span-2 glass-card p-12 rounded-[56px] relative overflow-hidden transition-all duration-300 hover:-translate-y-1.5">
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-primary/5 to-transparent pointer-events-none"></div>
        <div class="flex justify-between items-center mb-12 relative z-10">
          <div>
            <h3 class="text-3xl font-black text-white uppercase italic tracking-tighter">Dinámica Fiscal</h3>
            <p class="text-sm font-bold text-white/30 mt-2 uppercase tracking-widest">Análisis de entrada vs salida YTD</p>
          </div>
          <div class="flex gap-10">
            <div class="flex items-center gap-3">
              <span class="w-2.5 h-2.5 rounded-full bg-primary shadow-[0_0_12px_#6366f1]"></span>
              <span class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em]">Activos Líquidos</span>
            </div>
            <div class="flex items-center gap-3">
              <span class="w-2.5 h-2.5 rounded-full bg-white/20"></span>
              <span class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em]">Pasivos</span>
            </div>
          </div>
        </div>
        <div class="h-72 relative flex items-end justify-between px-6 z-10">
           <div class="absolute inset-0 flex flex-col justify-between text-[9px] font-black text-white/10 uppercase tracking-widest pointer-events-none pb-12">
            <div v-for="val in [500, 400, 300, 200, 100, 0]" :key="val" class="border-t border-white/5 w-full pt-2 flex justify-between items-center">
              <span>{{ val }}K Val</span>
              <div class="w-[85%] border-t border-dashed border-white/5"></div>
            </div>
          </div>
          <div class="flex-1 flex items-end justify-around gap-10 h-full z-10 pb-6 ml-10">
            <div v-for="(month, i) in ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun']" :key="month" class="flex flex-col items-center gap-4 w-full group h-full justify-end">
              <div class="flex gap-3 w-full items-end justify-center h-full">
                <div 
                  :style="{ height: `${[60, 75, 85, 70, 90, 80][i]}%` }"
                  :class="`w-5 rounded-t-xl transition-all duration-700 group-hover:scale-x-110 ${i === 2 ? 'bg-primary shadow-[0_0_25px_#6366f160]' : 'bg-primary/20 group-hover:bg-primary/40'}`"
                ></div>
                <div 
                  :style="{ height: `${[40, 45, 50, 60, 35, 40][i]}%` }"
                  :class="`w-5 rounded-t-xl transition-all duration-700 group-hover:scale-x-110 ${i === 2 ? 'bg-white/40 shadow-[0_0_20px_rgba(255,255,255,0.1)]' : 'bg-white/10 group-hover:bg-white/20'}`"
                ></div>
              </div>
              <span :class="`text-[10px] font-black tracking-widest uppercase ${i === 2 ? 'text-primary shadow-[0_0_5px_#6366f150]' : 'text-white/20'}`">{{ month }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="glass-card p-12 rounded-[56px] flex flex-col border border-white/5 transition-all duration-300 hover:-translate-y-1.5">
        <h3 class="text-3xl font-black text-white uppercase italic tracking-tighter">Vector de Presupuesto</h3>
        <p class="text-[10px] font-bold text-white/30 mt-2 mb-12 uppercase tracking-[0.2em]">Perfil de distribución de recursos</p>
        <div class="relative w-60 h-60 mx-auto mb-12 flex items-center justify-center">
          <svg class="w-full h-full transform -rotate-90 drop-shadow-2xl" viewBox="0 0 36 36">
            <circle cx="18" cy="18" r="15.9" fill="transparent" stroke="rgba(255,255,255,0.05)" stroke-width="4.5" />
            <circle cx="18" cy="18" r="15.9" fill="transparent" stroke="#6366f1" stroke-width="4.5" stroke-dasharray="55 100" stroke-linecap="round" class="drop-shadow-[0_0_8px_#6366f1]" />
            <circle cx="18" cy="18" r="15.9" fill="transparent" stroke="#f43f5e" stroke-width="4.5" stroke-dasharray="25 100" stroke-dashoffset="-55" stroke-linecap="round" class="drop-shadow-[0_0_8px_#f43f5e]" />
            <circle cx="18" cy="18" r="15.9" fill="transparent" stroke="rgba(255,255,255,0.3)" stroke-width="4.5" stroke-dasharray="20 100" stroke-dashoffset="-80" stroke-linecap="round" />
          </svg>
          <div class="absolute inset-0 flex flex-col items-center justify-center text-center translate-y-1">
            <span class="text-5xl font-black text-white italic tracking-tighter">Q152k</span>
            <span class="text-[8px] text-white/30 uppercase font-black tracking-[0.4em] mt-2">Capital Activo</span>
          </div>
        </div>
        <div class="space-y-6 mt-auto">
          <div v-for="item in [
            { label: 'Infraestructura', val: 55, color: 'bg-primary shadow-[0_0_10px_#6366f1]' },
            { label: 'Operaciones de Talento', val: 25, color: 'bg-tertiary shadow-[0_0_10px_#f43f5e]' },
            { label: 'Subsidiarios Auxiliares', val: 20, color: 'bg-white/20' },
          ]" :key="item.label" class="flex items-center justify-between group cursor-pointer border-b border-white/5 pb-4">
            <div class="flex items-center gap-4">
              <span :class="`w-3 h-3 rounded-full ${item.color} group-hover:scale-150 transition-transform duration-500`"></span>
              <span class="text-xs font-bold text-white/60 tracking-wider uppercase">{{ item.label }}</span>
            </div>
            <span class="text-sm font-black text-white italic">{{ item.val }}%</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Transactions Section -->
    <section class="glass-card rounded-[56px] overflow-hidden border border-white/5 shadow-2xl transition-all duration-300 hover:-translate-y-1.5">
      <div class="p-12 flex flex-col md:flex-row md:items-center justify-between gap-8 border-b border-white/5 bg-white/5 backdrop-blur-3xl">
        <div>
          <h3 class="text-4xl font-black text-white italic uppercase tracking-tighter">Protocolo de Transacciones</h3>
          <p class="text-[10px] font-bold text-white/30 mt-3 uppercase tracking-[0.3em]">Libro mayor financiero en tiempo real</p>
        </div>
        <button class="glass-button-primary text-white border border-white/20 px-10 py-5 rounded-[28px] font-black text-xs uppercase tracking-[0.2em] flex items-center gap-4 shadow-2xl shadow-primary/20 hover:shadow-primary/40 transition-all active:scale-95">
          <DocumentArrowDownIcon class="w-6 h-6" />
          Exportación Segura de Auditoría
        </button>
      </div>

      <div class="overflow-x-auto px-6">
        <table class="w-full text-left">
          <thead>
            <tr class="text-[11px] font-bold text-white/20 uppercase tracking-[0.3em]">
              <th class="px-10 py-10">Descripción de Entidad</th>
              <th class="px-10 py-10">Vector</th>
              <th class="px-10 py-10">Proyecto Vinculado</th>
              <th class="px-10 py-10 text-right">Valor Neto</th>
              <th class="px-10 py-10">Estado de Auditoría</th>
              <th class="px-10 py-10"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-for="(tx, i) in transactions" :key="i" class="hover:bg-white/5 group transition-all duration-300 cursor-pointer">
              <td class="px-10 py-10">
                <div class="flex items-center gap-6">
                  <div class="w-16 h-16 rounded-3xl bg-white/5 border border-white/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-all duration-500 shadow-xl group-hover:shadow-primary/30">
                    <component :is="tx.icon" class="w-8 h-8" />
                  </div>
                  <div>
                    <p class="font-black text-xl text-white tracking-tight italic uppercase">{{ tx.desc }}</p>
                    <p class="text-[10px] font-bold text-white/30 mt-1 uppercase tracking-widest">{{ tx.date }} • {{ tx.id }}</p>
                  </div>
                </div>
              </td>
              <td class="px-10 py-10">
                <span class="text-[10px] font-black text-white/60 uppercase tracking-[0.3em] bg-white/5 px-4 py-2 rounded-xl border border-white/5">{{ tx.cat }}</span>
              </td>
              <td class="px-10 py-10">
                <span class="text-sm font-bold text-white/40 uppercase tracking-widest">{{ tx.proj }}</span>
              </td>
              <td :class="`px-10 py-10 text-right font-black italic text-2xl ${tx.isPositive ? 'text-primary shadow-[0_0_15px_#6366f130]' : 'text-white'}`">
                {{ tx.amount }}
              </td>
              <td class="px-10 py-10">
                <div :class="`flex items-center gap-3 font-black text-[10px] uppercase tracking-[0.2em] ${tx.status === 'Completado' ? 'text-primary' : 'text-tertiary'}`">
                  <span :class="`w-2.5 h-2.5 rounded-full ${tx.status === 'Completado' ? 'bg-primary shadow-[0_0_10px_#6366f1]' : 'bg-tertiary animate-pulse shadow-[0_0_10px_#f43f5e]'}`"></span>
                  {{ tx.status }}
                </div>
              </td>
              <td class="px-10 py-10 text-right">
                <button class="w-12 h-12 rounded-2xl bg-white/5 hover:bg-white/10 transition-all flex items-center justify-center text-white/10 hover:text-white">
                  <EllipsisVerticalIcon class="w-6 h-6" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="p-10 flex flex-col md:flex-row justify-between items-center gap-8 border-t border-white/5 bg-black/20">
        <p class="text-[10px] font-black text-white/20 uppercase tracking-[0.3em]">Mostrando 1-10 de 480 Entradas Totales del Libro Mayor</p>
        <div class="flex items-center gap-4">
          <button class="w-12 h-12 flex items-center justify-center rounded-2xl border border-white/10 text-white/20 hover:bg-white/5 transition-all">
            <ChevronLeftIcon class="w-7 h-7" />
          </button>
          <button class="w-12 h-12 flex items-center justify-center rounded-2xl bg-primary text-white font-black italic text-lg shadow-[0_0_20px_rgba(99,102,241,0.4)]">1</button>
          <button class="w-12 h-12 flex items-center justify-center rounded-2xl border border-white/10 text-white/40 hover:bg-white/5 transition-all font-black italic">2</button>
          <button class="w-12 h-12 flex items-center justify-center rounded-2xl border border-white/10 text-white/40 hover:bg-white/5 transition-all font-black italic">3</button>
          <button class="w-12 h-12 flex items-center justify-center rounded-2xl border border-white/10 text-white/20 hover:bg-white/5 transition-all">
            <ChevronRightIcon class="w-7 h-7" />
          </button>
        </div>
      </div>
    </section>

    <!-- FAB -->
    <button class="fixed bottom-12 right-12 w-20 h-20 rounded-[32px] glass-button-primary text-white shadow-2xl shadow-primary/40 flex items-center justify-center hover:scale-110 active:scale-95 transition-all z-50 group">
      <PlusIcon class="w-10 h-10 group-hover:rotate-90 transition-transform duration-500 shadow-[0_0_20px_rgba(99,102,241,0.5)]" />
    </button>
  </div>
</template>

<script setup>
import { 
  WalletIcon, ArrowTrendingUpIcon, ClockIcon, WrenchScrewdriverIcon, 
  BanknotesIcon, ArchiveBoxIcon, UserIcon, PlusIcon, DocumentArrowDownIcon, 
  EllipsisVerticalIcon, ChevronLeftIcon, ChevronRightIcon 
} from '@heroicons/vue/24/outline';

const kpis = [
  { label: "Liquidez Total de Activos", value: "Q2,845k", sub: "+12.4% rendimiento", icon: WalletIcon, color: "primary" },
  { label: "Utilidad Operativa Neta", value: "Q412.8k", sub: "+3.1% sobre meta", icon: ArrowTrendingUpIcon, color: "primary" },
  { label: "Pasivos no Resueltos", value: "Q84.3k", sub: "14 unidades pendientes", icon: ClockIcon, color: "tertiary" },
];

const transactions = [
  { desc: "Suministro de Concreto a Granel", date: "Mar 24, 2024", id: "INV-8821", cat: "Materiales", proj: "Azure Towers Fase 2", amount: "-Q12,450.00", status: "Completado", icon: WrenchScrewdriverIcon },
  { desc: "Pago de Hito de Proyecto", date: "Mar 22, 2024", id: "REC-4410", cat: "Ingresos", proj: "Riverside Plaza", amount: "+Q85,000.00", status: "Completado", icon: BanknotesIcon, isPositive: true },
  { desc: "Arrendamiento de Excavadora (Mensual)", date: "Mar 20, 2024", id: "EXP-9901", cat: "Equipo", proj: "Expansión de Carretera", amount: "-Q4,200.00", status: "Pendiente", icon: ArchiveBoxIcon },
  { desc: "Nómina de Personal", date: "Mar 15, 2024", id: "PAY-5522", cat: "Mano de Obra", proj: "Múltiples Proyectos", amount: "-Q28,900.00", status: "Completado", icon: UserIcon },
];
</script>
