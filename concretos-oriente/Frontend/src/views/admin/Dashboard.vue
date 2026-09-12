<template>
  <div class="min-h-screen bg-[#050f1a] text-white p-4 md:p-8 space-y-6 pb-24 font-sans">

    <!-- HEADER SECTION (Background Image + Welcome + KPI Overlap) -->
    <div class="relative w-full rounded-[40px] overflow-visible">
      <!-- Background Image -->
      <div class="absolute inset-0 rounded-[40px] overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center opacity-60 mix-blend-screen" :style="{ backgroundImage: `url(${HeaderImg})` }"></div>
        <!-- Gradients to blend -->
        <div class="absolute inset-0 bg-gradient-to-r from-[#050f1a] via-[#050f1a]/60 to-transparent"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#050f1a] via-transparent to-transparent"></div>
      </div>

      <!-- Top Bar / Header Content -->
      <div class="relative z-10 p-10 pt-8 pb-20">
        <!-- Date in Header -->
        <div class="flex flex-col md:flex-row justify-between items-start mb-4">
          <div class="flex flex-col gap-4 ml-auto">
            <div class="hidden lg:flex items-center gap-3 bg-[#112236]/80 backdrop-blur-md px-4 py-2 rounded-2xl border border-white/10 ml-auto">
              <CalendarIcon class="w-5 h-5 text-white/50" />
              <div>
                <p class="text-[10px] uppercase font-bold tracking-wider text-white/50">{{ todayWeekday }}</p>
                <p class="text-xs font-bold">{{ todayLong }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Welcome Message -->
        <div class="max-w-2xl relative">
          <h2 class="text-3xl font-light text-white/90 mb-2">¡Hola, <span class="font-bold text-white">{{ authStore.userName || 'Usuario' }}!</span></h2>
          <p class="text-sm text-white/60 mb-6 font-medium">Aquí tienes el estado actual de tus proyectos</p>

          <h1 class="text-5xl md:text-[64px] font-black leading-none tracking-tight mb-4 uppercase drop-shadow-2xl">
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-white to-white/70">Construyendo</span><br/>
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 via-orange-400 to-yellow-600 drop-shadow-[0_0_15px_rgba(250,204,21,0.3)]">El Mañana</span>
          </h1>
          <p class="text-xs md:text-sm font-bold tracking-[0.3em] text-white/70 uppercase">
            Materiales que desarrollan Guatemala
          </p>
        </div>
      </div>

      <!-- KPI Cards (Overlapping) -->
      <div class="relative z-20 px-10 -mt-24 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Ingresos -->
        <div class="bg-[#0b1727]/90 backdrop-blur-xl border border-emerald-500/30 rounded-3xl p-6 shadow-[0_20px_40px_-15px_rgba(16,185,129,0.3)] flex flex-col justify-between group hover:-translate-y-2 transition-transform">
          <div class="flex justify-between items-start mb-4">
            <div class="flex items-center gap-4">
              <div class="p-3 rounded-2xl bg-emerald-500/20 text-emerald-400 shadow-[0_0_15px_rgba(16,185,129,0.3)]">
                <BanknotesIcon class="w-6 h-6" />
              </div>
              <div>
                <p class="text-[10px] uppercase font-bold tracking-widest text-emerald-500/70">Ingresos Totales</p>
                <h3 class="text-2xl font-black text-white mt-1">{{ formatCompactCurrency(kpis.total_income) }}</h3>
              </div>
            </div>
          </div>
          <div class="flex items-end justify-between">
            <div>
              <p class="text-xs font-bold text-emerald-400 flex items-center gap-1">
                <component :is="kpis.income_change_pct >= 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon" class="w-3 h-3" />
                {{ formatPct(kpis.income_change_pct) }}
              </p>
              <p class="text-[9px] text-white/30 uppercase mt-0.5">vs. mes anterior</p>
            </div>
            <svg class="w-24 h-8 overflow-visible" viewBox="0 0 100 30">
              <path d="M0,25 C20,25 30,10 50,15 C70,20 80,5 100,2" fill="none" stroke="#34d399" stroke-width="3" stroke-linecap="round" class="drop-shadow-[0_0_8px_rgba(52,211,153,0.8)]" />
            </svg>
          </div>
        </div>

        <!-- Gastos -->
        <div class="bg-[#0b1727]/90 backdrop-blur-xl border border-rose-500/30 rounded-3xl p-6 shadow-[0_20px_40px_-15px_rgba(244,63,94,0.3)] flex flex-col justify-between group hover:-translate-y-2 transition-transform">
          <div class="flex justify-between items-start mb-4">
            <div class="flex items-center gap-4">
              <div class="p-3 rounded-2xl bg-rose-500/20 text-rose-400 shadow-[0_0_15px_rgba(244,63,94,0.3)]">
                <DocumentTextIcon class="w-6 h-6" />
              </div>
              <div>
                <p class="text-[10px] uppercase font-bold tracking-widest text-rose-500/70">Gastos Totales</p>
                <h3 class="text-2xl font-black text-white mt-1">{{ formatCompactCurrency(kpis.total_expense) }}</h3>
              </div>
            </div>
          </div>
          <div class="flex items-end justify-between">
            <div>
              <p class="text-xs font-bold text-rose-400 flex items-center gap-1">
                <component :is="kpis.expense_change_pct >= 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon" class="w-3 h-3" />
                {{ formatPct(kpis.expense_change_pct) }}
              </p>
              <p class="text-[9px] text-white/30 uppercase mt-0.5">vs. mes anterior</p>
            </div>
            <svg class="w-24 h-8 overflow-visible" viewBox="0 0 100 30">
              <path d="M0,25 C20,20 30,28 50,18 C70,8 80,15 100,5" fill="none" stroke="#fb7185" stroke-width="3" stroke-linecap="round" class="drop-shadow-[0_0_8px_rgba(251,113,133,0.8)]" />
            </svg>
          </div>
        </div>

        <!-- Flota -->
        <div class="bg-[#0b1727]/90 backdrop-blur-xl border border-blue-500/30 rounded-3xl p-6 shadow-[0_20px_40px_-15px_rgba(59,130,246,0.3)] flex flex-col justify-between group hover:-translate-y-2 transition-transform">
          <div class="flex justify-between items-start mb-4">
            <div class="flex items-center gap-4">
              <div class="p-3 rounded-2xl bg-blue-500/20 text-blue-400 shadow-[0_0_15px_rgba(59,130,246,0.3)]">
                <TruckIcon class="w-6 h-6" />
              </div>
              <div>
                <p class="text-[10px] uppercase font-bold tracking-widest text-blue-500/70">Flota Activa</p>
                <h3 class="text-2xl font-black text-white mt-1">{{ kpis.fleet_active }}</h3>
              </div>
            </div>
          </div>
          <div class="flex items-end justify-between">
            <div>
              <p class="text-[11px] font-bold text-white/70">
                de {{ kpis.fleet_total }} unidades
              </p>
              <p class="text-[9px] text-white/30 uppercase mt-0.5">Disponibilidad</p>
            </div>
            <svg class="w-24 h-8 overflow-visible" viewBox="0 0 100 30">
              <path d="M0,15 C20,15 30,25 50,20 C70,15 80,5 100,10" fill="none" stroke="#60a5fa" stroke-width="3" stroke-linecap="round" class="drop-shadow-[0_0_8px_rgba(96,165,250,0.8)]" />
            </svg>
          </div>
        </div>

        <!-- Incidentes de Personal -->
        <div class="bg-[#0b1727]/90 backdrop-blur-xl border border-yellow-500/30 rounded-3xl p-6 shadow-[0_20px_40px_-15px_rgba(234,179,8,0.3)] flex flex-col justify-between group hover:-translate-y-2 transition-transform">
          <div class="flex justify-between items-start mb-4">
            <div class="flex items-center gap-4">
              <div class="p-3 rounded-2xl bg-yellow-500/20 text-yellow-400 shadow-[0_0_15px_rgba(234,179,8,0.3)]">
                <ShieldCheckIcon class="w-6 h-6" />
              </div>
              <div>
                <p class="text-[10px] uppercase font-bold tracking-widest text-yellow-500/70">Incidentes de Personal</p>
                <h3 class="text-2xl font-black text-white mt-1">{{ kpis.incidents_this_month }}</h3>
              </div>
            </div>
          </div>
          <div class="flex items-end justify-between">
            <div>
              <p class="text-xs font-bold text-yellow-400">
                {{ kpis.incidents_this_month === 0 ? 'Sin novedades' : 'Registrados' }}
              </p>
              <p class="text-[9px] text-white/30 uppercase mt-0.5">Este mes</p>
            </div>
            <svg class="w-24 h-8 overflow-visible" viewBox="0 0 100 30">
              <path d="M0,10 C20,5 30,20 50,15 C70,10 80,18 100,5" fill="none" stroke="#facc15" stroke-width="3" stroke-linecap="round" class="drop-shadow-[0_0_8px_rgba(250,204,21,0.8)]" />
            </svg>
          </div>
        </div>

      </div>
    </div>

    <div v-if="loading" class="text-center py-20">
      <p class="text-white/50 text-xl font-bold uppercase tracking-widest animate-pulse">Cargando panel...</p>
    </div>

    <template v-else>
      <!-- MIDDLE ROW: Charts & Map -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 pt-6">

        <!-- Resumen Financiero Chart -->
        <div class="xl:col-span-2 bg-[#0b1727] rounded-[32px] p-8 border border-white/5 relative overflow-hidden">
          <!-- Glow effect -->
          <div class="absolute top-0 right-1/4 w-64 h-64 bg-blue-500/10 blur-[100px] rounded-full pointer-events-none"></div>

          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-10 relative z-10 gap-4">
            <div class="flex items-center gap-4">
              <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-400">
                <ChartBarIcon class="w-6 h-6" />
              </div>
              <div>
                <h2 class="text-xl font-bold text-white">Resumen Financiero</h2>
                <p class="text-white/40 text-xs mt-1">Ingresos y gastos reales (últimos 6 meses)</p>
              </div>
            </div>

            <div class="flex items-center gap-4">
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500 shadow-[0_0_10px_rgba(59,130,246,0.6)]"></span>
                <span class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Ingresos</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-orange-400 shadow-[0_0_10px_rgba(251,146,60,0.6)]"></span>
                <span class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Gastos</span>
              </div>
            </div>
          </div>

          <!-- Bar Chart -->
          <div class="h-[280px] w-full flex items-end justify-between gap-2 sm:gap-6 relative z-10 pl-12 pb-6 border-b border-white/10">
            <!-- Y Axis -->
            <div class="absolute left-0 top-0 bottom-6 flex flex-col justify-between text-[10px] text-white/30 font-mono">
              <span>{{ formatCompactCurrency(chartMax) }}</span>
              <span>{{ formatCompactCurrency(chartMax / 2) }}</span>
              <span>Q 0</span>
            </div>

            <!-- Bars -->
            <div
              v-for="(data, i) in financialChart"
              :key="i"
              class="flex-1 flex flex-col items-center justify-end h-full group cursor-pointer"
              @mouseenter="hoveredMonth = i"
            >
              <div class="flex items-end gap-1.5 w-full h-full justify-center relative">
                <!-- Tooltip -->
                <div v-if="hoveredMonth === i" class="absolute -top-16 left-1/2 -translate-x-1/2 bg-[#112236] border border-white/10 rounded-xl p-3 shadow-2xl z-20 pointer-events-none whitespace-nowrap">
                  <p class="text-xs font-bold mb-1">{{ data.month }}</p>
                  <div class="flex items-center gap-2 text-[10px]">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    <span class="text-white/70">Ingresos:</span> <span class="font-bold text-white">{{ formatCompactCurrency(data.income) }}</span>
                  </div>
                  <div class="flex items-center gap-2 text-[10px] mt-1">
                    <span class="w-2 h-2 rounded-full bg-orange-400"></span>
                    <span class="text-white/70">Gastos:</span> <span class="font-bold text-white">{{ formatCompactCurrency(data.expense) }}</span>
                  </div>
                </div>

                <!-- Income Bar -->
                <div :style="{ height: `${barHeight(data.income)}%` }" class="w-[40%] max-w-[24px] bg-gradient-to-t from-blue-700 to-blue-400 rounded-t-lg transition-all duration-500 hover:brightness-125 shadow-[0_0_10px_rgba(59,130,246,0.3)]"></div>
                <!-- Expense Bar -->
                <div :style="{ height: `${barHeight(data.expense)}%` }" class="w-[40%] max-w-[24px] bg-gradient-to-t from-orange-600 to-orange-400 rounded-t-lg transition-all duration-500 hover:brightness-125 shadow-[0_0_10px_rgba(251,146,60,0.3)]"></div>
              </div>
              <span :class="`text-[10px] font-bold tracking-widest mt-4 uppercase ${hoveredMonth === i ? 'text-white' : 'text-white/40'}`">{{ data.month }}</span>
            </div>
          </div>
        </div>

        <!-- Estado de Proyectos -->
        <div class="bg-[#0b1727] rounded-[32px] p-8 border border-white/5 relative overflow-hidden flex flex-col">
          <div class="flex justify-between items-start mb-6 z-10">
            <div class="flex items-center gap-4">
              <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-400">
                <FolderIcon class="w-6 h-6" />
              </div>
              <div>
                <h2 class="text-xl font-bold text-white">Estado de Proyectos</h2>
                <p class="text-white/40 text-xs mt-1">Distribución por estado actual</p>
              </div>
            </div>
            <RouterLink to="/projects" class="text-xs text-white/50 hover:text-white flex items-center gap-1 transition-colors">
              Ver todos <ChevronRightIcon class="w-3 h-3" />
            </RouterLink>
          </div>

          <div class="flex-1 flex flex-col relative z-10">
            <div class="flex-1 relative mb-6 rounded-2xl overflow-hidden border border-white/5 bg-[#050f1a]/50 flex items-center justify-center p-6">
              <div v-if="totalProjects === 0" class="text-center text-white/30 text-xs uppercase tracking-widest">
                Sin proyectos registrados
              </div>
              <div v-else class="relative w-36 h-36">
                <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
                  <circle
                    v-for="seg in projectDonutSegments"
                    :key="seg.key"
                    cx="18" cy="18" r="15.9155"
                    fill="none"
                    :stroke="seg.color"
                    stroke-width="3.2"
                    :stroke-dasharray="`${seg.pct} ${100 - seg.pct}`"
                    :stroke-dashoffset="seg.offset"
                    class="transition-all duration-500"
                  />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                  <span class="text-2xl font-black text-white">{{ totalProjects }}</span>
                  <span class="text-[9px] text-white/40 uppercase tracking-widest">Proyectos</span>
                </div>
              </div>
            </div>

            <!-- Legend -->
            <div class="space-y-3 bg-white/5 p-4 rounded-2xl">
              <div v-for="seg in projectStatusList" :key="seg.key" class="flex justify-between items-center text-xs">
                <div class="flex items-center gap-3">
                  <span :class="`w-2.5 h-2.5 rounded-full ${seg.dotClass} shadow-[0_0_8px_currentColor]`" :style="{ color: seg.color }"></span>
                  <span class="text-white/80">{{ seg.label }}</span>
                </div>
                <span class="font-mono text-white/50">{{ seg.count }}</span>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- BOTTOM ROW -->
      <div class="grid grid-cols-1 lg:grid-cols-3 xl:grid-cols-4 gap-6 pt-2">

        <!-- Proyectos Destacados -->
        <div class="lg:col-span-2 bg-[#0b1727] rounded-[32px] p-8 border border-white/5 relative overflow-hidden">
          <div class="flex justify-between items-center mb-8">
            <div class="flex items-center gap-4">
              <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-400">
                <UserGroupIcon class="w-6 h-6" />
              </div>
              <div>
                <h2 class="text-xl font-bold text-white">Proyectos Destacados</h2>
                <p class="text-white/40 text-xs mt-1">Estado actual de los principales proyectos</p>
              </div>
            </div>
            <RouterLink to="/projects" class="text-xs text-white/50 hover:text-white flex items-center gap-1 transition-colors">
              Ver todos <ChevronRightIcon class="w-3 h-3" />
            </RouterLink>
          </div>

          <div v-if="featuredProjects.length === 0" class="text-center py-12 text-white/30 text-xs uppercase tracking-widest">
            Sin proyectos registrados
          </div>
          <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <RouterLink
              v-for="proj in featuredProjects"
              :key="proj.id"
              to="/projects"
              class="bg-white/5 rounded-2xl p-4 border border-white/5 hover:border-blue-500/30 transition-colors group cursor-pointer relative overflow-hidden block"
            >
              <div class="h-24 bg-white/5 rounded-xl mb-4 relative overflow-hidden bg-cover bg-center" :style="{ backgroundImage: `url(${projectImage(proj)})` }">
                <div class="absolute inset-0 bg-black/40 group-hover:bg-black/20 transition-colors"></div>
                <div :class="`absolute top-2 left-2 ${statusBadgeClass(proj.estado)} text-white text-[9px] font-bold px-2 py-1 rounded-md flex items-center gap-1 backdrop-blur-sm shadow-lg`">
                  <CheckCircleIcon class="w-3 h-3" /> {{ proj.estado }}
                </div>
              </div>
              <h3 class="font-bold text-sm text-white mb-2 leading-tight">{{ proj.nombre }}</h3>
              <div class="w-full bg-black/30 h-1.5 rounded-full mb-1">
                <div :class="`${statusBarClass(proj.estado)} h-1.5 rounded-full shadow-[0_0_10px_currentColor]`" :style="{ width: `${proj.avance_financiero}%` }"></div>
              </div>
              <div class="flex justify-between text-[10px] text-white/50 font-bold mb-4">
                <span>Q {{ formatCurrency(proj.presupuesto) }}</span>
                <span>{{ proj.avance_financiero }}% ejecutado</span>
              </div>
              <div class="flex justify-between items-center text-[9px] text-white/40">
                <span>{{ proj.fecha_fin_estimada ? `Fecha fin: ${formatDateShort(proj.fecha_fin_estimada)}` : `Inicio: ${formatDateShort(proj.fecha_inicio)}` }}</span>
                <div class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center group-hover:bg-blue-500 group-hover:text-white transition-colors">
                  <ArrowRightIcon class="w-3 h-3" />
                </div>
              </div>
            </RouterLink>
          </div>
        </div>

        <!-- Alertas Inventario -->
        <div class="bg-[#0b1727] rounded-[32px] p-8 border border-white/5">
          <div class="flex justify-between items-center mb-8">
            <div class="flex items-center gap-3">
              <div class="p-2 rounded-xl bg-rose-500/20 text-rose-400">
                <BellAlertIcon class="w-5 h-5" />
              </div>
              <h2 class="text-sm font-bold text-white">Alertas de Inventario</h2>
            </div>
            <span v-if="inventoryAlerts.items.length > 0" class="bg-rose-500 text-white px-2 py-1 rounded-lg text-[9px] font-bold tracking-wider shadow-[0_0_10px_rgba(244,63,94,0.5)]">
              {{ inventoryAlerts.critical_count }} críticas
            </span>
          </div>

          <div v-if="inventoryAlerts.items.length === 0" class="text-center py-8 text-white/30 text-xs uppercase tracking-widest">
            Sin alertas de inventario
          </div>
          <div v-else class="space-y-4">
            <div v-for="item in inventoryAlerts.items" :key="item.id" class="flex items-start gap-4 p-3 rounded-xl hover:bg-white/5 transition-colors cursor-pointer group">
              <div :class="`w-8 h-8 rounded-full ${item.critico ? 'bg-rose-500/20' : 'bg-yellow-500/20'} flex items-center justify-center shrink-0`">
                <span :class="`w-2 h-2 rounded-full ${item.critico ? 'bg-rose-500 shadow-[0_0_8px_#f43f5e]' : 'bg-yellow-500 shadow-[0_0_8px_#eab308]'}`"></span>
              </div>
              <div class="flex-1">
                <h4 class="text-xs font-bold text-white/90">{{ item.nombre }}</h4>
                <p class="text-[10px] text-white/40 mt-1">Stock: {{ item.stock_actual }} {{ item.unidad_medida }} (mínimo {{ item.stock_minimo }})</p>
              </div>
              <ChevronRightIcon class="w-4 h-4 text-white/20 group-hover:text-white/60 mt-2" />
            </div>
          </div>

          <RouterLink to="/inventory" class="w-full mt-6 py-2.5 rounded-xl border border-white/10 text-xs font-bold text-white/60 hover:text-white hover:bg-white/5 transition-colors block text-center">
            Ver inventario
          </RouterLink>
        </div>

        <!-- Actividad Reciente -->
        <div class="bg-[#0b1727] rounded-[32px] p-8 border border-white/5 relative overflow-hidden xl:col-span-1 lg:col-span-3">
          <div class="flex justify-between items-center mb-8">
            <div class="flex items-center gap-3">
              <div class="p-2 rounded-xl bg-blue-500/20 text-blue-400">
                <ClockIcon class="w-5 h-5" />
              </div>
              <h2 class="text-sm font-bold text-white">Actividad Reciente</h2>
            </div>
          </div>

          <div v-if="recentActivity.length === 0" class="text-center py-8 text-white/30 text-xs uppercase tracking-widest">
            Sin actividad reciente
          </div>
          <div v-else class="relative pl-4 space-y-6">
            <div class="absolute left-[27px] top-4 bottom-4 w-px bg-white/10"></div>

            <div v-for="(item, idx) in recentActivity" :key="idx" class="flex gap-4 relative z-10">
              <div class="w-14 text-[9px] text-white/40 font-mono pt-1 shrink-0 text-right">{{ formatRelativeDate(item.date) }}</div>
              <div :class="`w-6 h-6 rounded-full bg-[#112236] border-2 ${activityBorderClass(item.type)} flex items-center justify-center shrink-0`">
                <component :is="activityIcon(item.type)" :class="`w-3 h-3 ${activityIconColor(item.type)}`" />
              </div>
              <div class="pt-0.5 pb-2">
                <p class="text-xs font-bold text-white/90">{{ activityTitle(item) }}</p>
                <p :class="`text-[10px] mt-1 ${activitySubtitleColor(item.type)}`">{{ activitySubtitle(item) }}</p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import {
  BanknotesIcon, DocumentTextIcon, TruckIcon, ShieldCheckIcon,
  ArrowTrendingUpIcon, ArrowTrendingDownIcon, ChartBarIcon, FolderIcon, ChevronRightIcon,
  UserGroupIcon, CheckCircleIcon, ArrowRightIcon,
  BellAlertIcon, ClockIcon, CalendarIcon
} from '@heroicons/vue/24/outline';
import HeaderImg from '../../assets/images/dashboard_header.png';
import { useAuthStore } from '../../stores/auth';

const authStore = useAuthStore();
const BASE_URL = '/concretos-oriente/Backend/api/v1';

const loading = ref(true);
const hoveredMonth = ref(null);

const kpis = ref({
  total_income: 0,
  total_expense: 0,
  income_change_pct: 0,
  expense_change_pct: 0,
  fleet_active: 0,
  fleet_total: 0,
  incidents_this_month: 0,
});
const financialChart = ref([]);
const projectsStatus = ref({ Activo: 0, Pausado: 0, Completado: 0, Cancelado: 0, Borrador: 0 });
const featuredProjects = ref([]);
const inventoryAlerts = ref({ critical_count: 0, items: [] });
const recentActivity = ref([]);

onMounted(async () => {
  try {
    const response = await fetch(`${BASE_URL}/dashboard/summary`);
    const json = await response.json();
    if (json.status === 'success') {
      kpis.value = json.data.kpis;
      financialChart.value = json.data.financial_chart;
      projectsStatus.value = json.data.projects_status;
      featuredProjects.value = json.data.featured_projects;
      inventoryAlerts.value = json.data.inventory_alerts;
      recentActivity.value = json.data.recent_activity;
      hoveredMonth.value = financialChart.value.length - 1;
    }
  } catch (e) {
    console.error('Error al cargar el panel principal:', e);
  } finally {
    loading.value = false;
  }
});

// ---------- Fecha ----------
const now = new Date();
const todayWeekday = now.toLocaleDateString('es-GT', { weekday: 'long' }).replace(/^\w/, c => c.toUpperCase());
const todayLong = now.toLocaleDateString('es-GT', { day: 'numeric', month: 'long', year: 'numeric' });

// ---------- Formatters ----------
const formatCurrency = (value) => {
  if (value === null || value === undefined || value === '') return '0.00';
  const num = typeof value === 'number' ? value : parseFloat(String(value).replace(/,/g, ''));
  if (isNaN(num)) return '0.00';
  return num.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatCompactCurrency = (value) => {
  const num = Number(value) || 0;
  if (Math.abs(num) >= 1000000) return `Q ${(num / 1000000).toFixed(1)}M`;
  if (Math.abs(num) >= 1000) return `Q ${(num / 1000).toFixed(0)}K`;
  return `Q ${Math.round(num).toLocaleString('es-GT')}`;
};

const formatPct = (value) => {
  const num = Number(value) || 0;
  return `${num >= 0 ? '+' : ''}${num}%`;
};

const formatDateShort = (dateStr) => {
  if (!dateStr) return '-';
  const d = new Date(dateStr + 'T00:00:00');
  if (isNaN(d.getTime())) return '-';
  return d.toLocaleDateString('es-GT', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const formatRelativeDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr.replace(' ', 'T'));
  if (isNaN(d.getTime())) return '';
  const today = new Date();
  const time = d.toLocaleTimeString('es-GT', { hour: '2-digit', minute: '2-digit' });
  const isSameDay = d.toDateString() === today.toDateString();
  const yesterday = new Date(today);
  yesterday.setDate(today.getDate() - 1);
  const isYesterday = d.toDateString() === yesterday.toDateString();

  if (isSameDay) return `Hoy ${time}`;
  if (isYesterday) return `Ayer ${time}`;
  return `${d.toLocaleDateString('es-GT', { day: '2-digit', month: '2-digit' })} ${time}`;
};

// ---------- Financial chart ----------
const chartMax = computed(() => {
  const values = financialChart.value.flatMap(d => [d.income, d.expense]);
  const max = Math.max(0, ...values);
  return max > 0 ? max : 1;
});

const barHeight = (value) => {
  const pct = (Number(value) / chartMax.value) * 100;
  return value > 0 ? Math.max(pct, 3) : 0;
};

// ---------- Projects status ----------
const STATUS_COLORS = {
  Activo: '#10b981',
  Pausado: '#facc15',
  Completado: '#3b82f6',
  Cancelado: '#f43f5e',
  Borrador: '#94a3b8',
};
const STATUS_DOT_CLASS = {
  Activo: 'bg-emerald-500',
  Pausado: 'bg-yellow-400',
  Completado: 'bg-blue-500',
  Cancelado: 'bg-rose-500',
  Borrador: 'bg-slate-400',
};
const STATUS_BADGE_CLASS = {
  Activo: 'bg-emerald-500/90',
  Pausado: 'bg-yellow-500/90',
  Completado: 'bg-blue-500/90',
  Cancelado: 'bg-rose-500/90',
  Borrador: 'bg-slate-500/90',
};
const STATUS_BAR_CLASS = {
  Activo: 'bg-emerald-500 text-emerald-500',
  Pausado: 'bg-yellow-400 text-yellow-400',
  Completado: 'bg-blue-500 text-blue-500',
  Cancelado: 'bg-rose-500 text-rose-500',
  Borrador: 'bg-slate-400 text-slate-400',
};

const totalProjects = computed(() => Object.values(projectsStatus.value).reduce((a, b) => a + b, 0));

const projectStatusList = computed(() => {
  return Object.entries(projectsStatus.value).map(([key, count]) => ({
    key,
    label: key,
    count,
    color: STATUS_COLORS[key] || '#94a3b8',
    dotClass: STATUS_DOT_CLASS[key] || 'bg-slate-400',
  }));
});

const projectDonutSegments = computed(() => {
  const total = totalProjects.value;
  if (total === 0) return [];
  let cumulative = 0;
  return Object.entries(projectsStatus.value)
    .filter(([, count]) => count > 0)
    .map(([key, count]) => {
      const pct = (count / total) * 100;
      const offset = -cumulative;
      cumulative += pct;
      return { key, pct, offset, color: STATUS_COLORS[key] || '#94a3b8' };
    });
});

const statusBadgeClass = (estado) => STATUS_BADGE_CLASS[estado] || 'bg-slate-500/90';
const statusBarClass = (estado) => STATUS_BAR_CLASS[estado] || 'bg-slate-400 text-slate-400';

// ---------- Featured projects ----------
const projectImage = (proj) => {
  if (!proj || !proj.foto) return 'https://images.unsplash.com/photo-1541888946425-d81bb19480c5?auto=format&fit=crop&q=80&w=2070';
  return `/concretos-oriente/Backend/${proj.foto}`;
};

// ---------- Recent activity ----------
const activityIcon = (type) => {
  switch (type) {
    case 'income': return BanknotesIcon;
    case 'expense': return DocumentTextIcon;
    case 'project': return ChartBarIcon;
    default: return DocumentTextIcon;
  }
};
const activityIconColor = (type) => {
  switch (type) {
    case 'income': return 'text-emerald-400';
    case 'expense': return 'text-rose-400';
    case 'project': return 'text-blue-400';
    default: return 'text-slate-400';
  }
};
const activityBorderClass = (type) => {
  switch (type) {
    case 'income': return 'border-emerald-500';
    case 'expense': return 'border-rose-500';
    case 'project': return 'border-blue-500';
    default: return 'border-slate-500';
  }
};
const activitySubtitleColor = (type) => {
  switch (type) {
    case 'income': return 'text-emerald-400/70';
    case 'expense': return 'text-rose-400/70';
    case 'project': return 'text-blue-400/70';
    default: return 'text-slate-400/70';
  }
};
const activityTitle = (item) => {
  switch (item.type) {
    case 'income': return 'Se registró un nuevo ingreso';
    case 'expense': return 'Se registró un nuevo egreso';
    case 'project': return `Proyecto actualizado: ${item.who}`;
    case 'document': return `Documento cargado: ${item.who}`;
    default: return item.who || 'Actividad';
  }
};
const activitySubtitle = (item) => {
  switch (item.type) {
    case 'income':
      return `${item.proyecto_nombre || item.who || 'Sin proyecto'} · Q ${formatCurrency(item.amount)}`;
    case 'expense':
      return `${item.proyecto_nombre || item.who || 'Sin proyecto'} · Q ${formatCurrency(item.amount)}`;
    case 'project':
      return `Estado: ${item.label}`;
    case 'document':
      return item.proyecto_nombre ? `${item.label} · ${item.proyecto_nombre}` : (item.label || '');
    default:
      return '';
  }
};
</script>

<style scoped>
/* Optional custom scrollbar hides for specific divs */
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.05);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
}
</style>
