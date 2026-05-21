<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">

    <!-- Toast Notification -->
    <div
      v-if="notification"
      class="fixed top-24 right-10 z-50 bg-primary/20 border border-primary text-white backdrop-blur-xl px-6 py-4 rounded-2xl flex items-center gap-3 shadow-lg"
    >
      <CheckCircleIcon class="w-5 h-5 text-primary" />
      <span class="text-xs font-black uppercase tracking-wider">{{ notification }}</span>
    </div>

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Presupuestos y Estimaciones</h2>
        <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Gestione sus costos operativos y márgenes de utilidad en tiempo real</p>
      </div>
      <button
        @click="showCreateModal = true"
        class="glass-button-primary bg-primary border border-primary text-white px-8 py-5 rounded-3xl text-xs font-black uppercase tracking-widest shadow-2xl flex items-center gap-3 hover:scale-105 active:scale-95 transition-all"
      >
        <PlusIcon class="w-4 h-4" /> Crear Estimación
      </button>
    </div>

    <!-- Bento Grid Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
      <!-- Stat Card 1 -->
      <div class="glass-card p-8 rounded-[40px] border border-white/5 flex flex-col justify-between h-52 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-24 h-24 bg-primary/5 rounded-full blur-xl"></div>
        <div>
          <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.25em]">Total Cotizado</span>
          <div class="flex items-baseline gap-3 mt-4">
            <h3 class="text-3xl font-black italic tracking-tighter">Q{{ totalQuoted.toLocaleString('es-GT') }}</h3>
            <span class="text-[10px] font-black text-primary bg-primary/20 px-2 py-0.5 rounded-lg">+12%</span>
          </div>
        </div>
        <div>
          <div class="w-full bg-white/5 h-2 rounded-full overflow-hidden mb-2">
            <div class="bg-primary h-full rounded-full" style="width: 70%"></div>
          </div>
          <p class="text-[9px] font-bold text-white/30 uppercase tracking-wider">Cumplimiento del pipeline</p>
        </div>
      </div>

      <!-- Stat Card 2 (Highlight Margin) -->
      <div class="bg-gradient-to-br from-primary to-indigo-950 p-8 rounded-[40px] flex flex-col justify-between h-52 relative overflow-hidden shadow-2xl shadow-primary/20">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
        <div>
          <span class="text-[10px] font-black text-white/80 uppercase tracking-[0.25em]">Margen Promedio</span>
          <div class="flex items-baseline gap-3 mt-4">
            <h3 class="text-4xl font-black italic tracking-tighter">{{ averageMargin }}%</h3>
            <ArrowTrendingUpIcon class="w-5 h-5 text-white/80" />
          </div>
        </div>
        <div>
          <p class="text-xs font-black uppercase tracking-wider text-white/90">Objetivo General: 22%</p>
          <p class="text-[10px] font-bold text-white/50 tracking-wide mt-2">Por encima del objetivo fijado por dirección</p>
        </div>
      </div>

      <!-- Stat Card 3 -->
      <div class="glass-card p-8 rounded-[40px] border border-white/5 flex flex-col justify-between h-52 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-24 h-24 bg-red-500/5 rounded-full blur-xl"></div>
        <div>
          <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.25em]">Pendientes de Aprobación</span>
          <div class="flex items-baseline gap-3 mt-4">
            <h3 class="text-3xl font-black italic tracking-tighter">18</h3>
            <span class="text-[9px] font-black bg-rose-500/20 text-rose-400 px-2.5 py-0.5 rounded-lg uppercase tracking-wider">Urgente</span>
          </div>
        </div>
        <div class="flex items-center justify-between">
          <div class="flex -space-x-2.5">
            <div class="w-8 h-8 rounded-full border border-slate-900 bg-indigo-500 flex items-center justify-center text-[10px] font-extrabold">A1</div>
            <div class="w-8 h-8 rounded-full border border-slate-900 bg-pink-500 flex items-center justify-center text-[10px] font-extrabold">F2</div>
            <div class="w-8 h-8 rounded-full border border-slate-900 bg-amber-500 flex items-center justify-center text-[10px] font-extrabold">JV</div>
          </div>
          <p class="text-[9px] font-extrabold text-white/30 uppercase tracking-widest">En espera</p>
        </div>
      </div>

      <!-- Stat Card 4 -->
      <div class="glass-card p-8 rounded-[40px] border border-white/5 flex flex-col justify-between h-52">
        <div>
          <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.25em]">Materiales Indexados</span>
          <div class="flex items-baseline gap-3 mt-4">
            <h3 class="text-3xl font-black italic tracking-tighter">94%</h3>
          </div>
        </div>
        <div>
          <p class="text-[10px] text-white/50 leading-relaxed font-bold">
            Tipos actualizados automáticamente hoy a las <strong>08:00 AM</strong>.
          </p>
        </div>
      </div>
    </div>

    <!-- Main Sections (Table & Breakdown) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">

      <!-- Main Table Section (Col-Span 2) -->
      <div class="lg:col-span-2 space-y-6">
        <div class="glass-card rounded-[48px] overflow-hidden border border-white/5 shadow-2xl">
          <div class="p-10 border-b border-white/5 bg-white/5 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
              <h3 class="text-2xl font-black text-white italic uppercase tracking-tighter">Presupuestos Activos</h3>
              <p class="text-[10px] font-bold text-white/30 uppercase tracking-widest mt-1">Estimaciones vigentes por proyecto y fase</p>
            </div>
            <div class="relative">
              <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/40" />
              <input
                type="text"
                v-model="searchTerm"
                placeholder="Filtrar por proyecto..."
                class="glass-input pl-11 pr-5 py-3 rounded-xl text-xs uppercase font-extrabold tracking-wider w-56 text-white placeholder:text-white/20"
              />
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="text-[10px] font-extrabold text-white/30 uppercase tracking-widest border-b border-white/5">
                  <th class="px-10 py-6">Proyecto</th>
                  <th class="px-10 py-6">Fecha</th>
                  <th class="px-10 py-6 text-right">Costo Total</th>
                  <th class="px-10 py-6 text-center">Beneficio (Utilidad)</th>
                  <th class="px-10 py-6 text-right">Estado</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr v-if="filteredBudgets.length === 0">
                  <td colspan="5" class="px-10 py-16 text-center text-white/30 font-bold uppercase tracking-widest text-xs">
                    No hay presupuestos activos que coincidan con la búsqueda.
                  </td>
                </tr>
                <tr
                  v-for="b in filteredBudgets"
                  :key="b.id"
                  class="hover:bg-white/5 transition-all duration-300 cursor-pointer group"
                >
                  <td class="px-10 py-6">
                    <h5 class="font-extrabold text-base text-white tracking-tight uppercase italic">{{ b.project }}</h5>
                    <p class="text-[10px] font-bold text-white/30 tracking-wider uppercase mt-1">ID: {{ b.id }} • Fase: {{ b.phase }}</p>
                  </td>
                  <td class="px-10 py-6 text-xs text-white/50 font-bold">{{ b.date }}</td>
                  <td class="px-10 py-6 text-right font-black italic text-base text-white">
                    Q{{ b.cost.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                  </td>
                  <td class="px-10 py-6 text-center">
                    <span class="bg-primary/20 text-primary border border-primary/25 px-3.5 py-1.5 rounded-xl text-xs font-black italic">
                      {{ b.utility }}%
                    </span>
                  </td>
                  <td class="px-10 py-6 text-right">
                    <span :class="[
                      'px-3.5 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border',
                      b.status === 'Aprobado' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                      b.status === 'En Revisión' ? 'bg-primary/10 text-primary border-primary/20' :
                      'bg-white/5 text-white/40 border-white/10'
                    ]">
                      {{ b.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="p-8 border-t border-white/5 bg-black/10 flex justify-between items-center text-[10px] font-black text-white/20 uppercase tracking-widest">
            <span>Mostrando {{ filteredBudgets.length }} de {{ budgets.length }} elementos</span>
            <button
              @click="triggerNotification('Filtrado avanzado próximamente disponible')"
              class="hover:text-primary transition-colors cursor-pointer"
            >
              Ver todos
            </button>
          </div>
        </div>
      </div>

      <!-- Detailed Cost Breakdown (Right Column) -->
      <div class="lg:col-span-1 space-y-8">
        <div class="glass-card p-10 rounded-[44px] border-l-4 border-primary space-y-8">
          <div>
            <h3 class="text-xl font-black italic uppercase tracking-tighter">Desglose de Costos</h3>
            <p class="text-[10px] font-black text-white/35 uppercase tracking-widest mt-1">Gastos presupuestales globales</p>
          </div>

          <div class="space-y-6">
            <!-- Category 1 -->
            <div class="space-y-3">
              <div class="flex justify-between items-center text-xs font-extrabold uppercase tracking-wide">
                <span class="flex items-center gap-2 text-white/60">
                  <WrenchScrewdriverIcon class="w-3.5 h-3.5 text-primary" />
                  Materiales
                </span>
                <span class="font-black text-white">Q680,400.00</span>
              </div>
              <div class="w-full bg-white/5 h-2 rounded-full overflow-hidden">
                <div class="bg-primary h-full rounded-full" style="width: 55%"></div>
              </div>
              <p class="text-[9px] font-bold text-white/30 uppercase tracking-widest">Alerta: +4.2% inflación acero estructural</p>
            </div>

            <!-- Category 2 -->
            <div class="space-y-3">
              <div class="flex justify-between items-center text-xs font-extrabold uppercase tracking-wide">
                <span class="flex items-center gap-2 text-white/60">
                  <UsersIcon class="w-3.5 h-3.5 text-primary" />
                  Mano de Obra
                </span>
                <span class="font-black text-white">Q340,200.00</span>
              </div>
              <div class="w-full bg-white/5 h-2 rounded-full overflow-hidden">
                <div class="bg-primary h-full rounded-full" style="width: 30%"></div>
              </div>
              <p class="text-[9px] font-bold text-white/30 uppercase tracking-widest">Certificación y seguridad técnica</p>
            </div>

            <!-- Category 3 -->
            <div class="space-y-3">
              <div class="flex justify-between items-center text-xs font-extrabold uppercase tracking-wide">
                <span class="flex items-center gap-2 text-white/60">
                  <TrophyIcon class="w-3.5 h-3.5 text-primary" />
                  Equipos y Maquinaria
                </span>
                <span class="font-black text-white">Q120,150.00</span>
              </div>
              <div class="w-full bg-white/5 h-2 rounded-full overflow-hidden">
                <div class="bg-primary h-full rounded-full" style="width: 10%"></div>
              </div>
              <p class="text-[9px] font-bold text-white/30 uppercase tracking-widest">Ahorro detectado: Eficiencia renting</p>
            </div>

            <!-- Operating Margin Total -->
            <div class="space-y-3 pt-6 border-t border-white/5">
              <div class="flex justify-between items-center">
                <span class="text-xs font-black uppercase tracking-wider text-primary italic">Margen de Caja</span>
                <span class="text-lg font-black italic text-primary">Q99,250.00</span>
              </div>
              <div class="w-full bg-primary/20 h-2 rounded-full">
                <div class="bg-primary h-full rounded-full" style="width: 100%"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Action Analysis -->
        <div class="glass-card p-10 rounded-[44px] border border-white/5 bg-gradient-to-br from-indigo-950/20 to-transparent relative overflow-hidden group">
          <div class="relative z-10 space-y-3">
            <h4 class="text-lg font-black italic uppercase tracking-wider text-white">Análisis de Mercado</h4>
            <p class="text-xs text-white/40 leading-relaxed font-bold">
              Tenemos nuevos precios verificados en cementeras y aceros para actualizar rápidamente sus márgenes de ganancia.
            </p>
            <button
              @click="triggerNotification('Base de datos de proveedores actualizada')"
              class="mt-6 text-[10px] font-black uppercase tracking-[0.2em] text-primary flex items-center gap-2 group-hover:translate-x-1.5 transition-transform cursor-pointer"
            >
              Actualizar Base de Precios <ChevronRightIcon class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Visual Chart Trends & AI Suggestions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

      <!-- Trend Graph Visualization -->
      <div class="lg:col-span-2 glass-card p-10 rounded-[48px] border border-white/5 space-y-8">
        <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
          <div>
            <h3 class="text-2xl font-black italic uppercase tracking-tighter text-white">Costos Totales vs. Presupuestado</h3>
            <p class="text-[10px] font-black text-white/30 uppercase tracking-widest mt-1">Comparación periódica Enero - Abril</p>
          </div>
          <div class="flex items-center gap-6">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-primary"></div>
              <span class="text-[10px] font-black uppercase tracking-widest text-white/40">Presupuestado</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-rose-500"></div>
              <span class="text-[10px] font-black uppercase tracking-widest text-white/40">Real</span>
            </div>
          </div>
        </div>

        <div class="pt-8 pb-4 h-64 flex items-end gap-12 md:gap-16 border-l border-b border-white/5 pl-6 relative">
          <div class="absolute left-0 right-0 top-1/4 h-[1px] bg-white/[0.02] border-dashed pointer-events-none"></div>
          <div class="absolute left-0 right-0 top-2/4 h-[1px] bg-white/[0.02] border-dashed pointer-events-none"></div>
          <div class="absolute left-0 right-0 top-3/4 h-[1px] bg-white/[0.02] border-dashed pointer-events-none"></div>

          <div
            v-for="(data, idx) in graphData"
            :key="idx"
            class="flex-grow flex flex-col items-center h-full justify-end gap-3 group"
          >
            <div class="w-full flex items-end h-full gap-2 px-1 max-w-[80px]">
              <!-- Budget bar -->
              <div
                class="flex-1 bg-primary/20 hover:bg-primary border border-primary/20 rounded-t-xl group-hover:scale-105 transition-all text-center relative"
                :style="{ height: `${data.budget}%` }"
              >
                <span class="absolute -top-7 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 text-[10px] font-black bg-slate-900 border border-white/10 px-1 py-0.5 rounded text-white">{{ data.budget }}%</span>
              </div>
              <!-- Real bar -->
              <div
                class="flex-1 bg-rose-500/20 hover:bg-rose-500 border border-rose-500/20 rounded-t-xl transition-all relative"
                :style="{ height: `${data.real}%` }"
              >
                <span class="absolute -top-7 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 text-[10px] font-black bg-slate-900 border border-white/10 px-1 py-0.5 rounded text-rose-400">{{ data.real }}%</span>
              </div>
            </div>
            <span class="text-[10px] font-black uppercase tracking-widest text-white/40 mt-1">{{ data.month }}</span>
          </div>
        </div>
      </div>

      <!-- AI Suggestion Box -->
      <div class="lg:col-span-1 bg-gradient-to-br from-indigo-950 to-slate-950 border border-primary/30 p-10 rounded-[48px] flex flex-col justify-between h-full shadow-2xl relative overflow-hidden text-center">
        <div class="absolute -top-10 -right-10 w-28 h-28 bg-primary/10 rounded-full blur-2xl"></div>
        <div class="flex pt-4 flex-col items-center gap-4">
          <div class="bg-primary/20 p-5 rounded-full text-primary border border-white/10 shadow-xl shadow-primary/20">
            <SparklesIcon class="w-8 h-8 animate-pulse" />
          </div>
          <h3 class="text-xl font-black italic uppercase tracking-tighter text-white">Sugerencia IA</h3>
          <p class="text-xs text-white/50 leading-relaxed font-semibold">
            Identificamos un potencial ahorro de hasta un <strong>8.5% en la logística global</strong> de la Fase Residencial "La Sierra" ajustando y programando las rutas de acarreo.
          </p>
        </div>
        <button
          @click="triggerNotification('Sugerencias aplicadas, simulando optimización.')"
          class="mt-8 w-full glass-button-primary bg-primary border border-primary py-4 rounded-3xl text-xs font-black uppercase tracking-widest text-white shadow-2xl"
        >
          Aplicar Sugerencias
        </button>
      </div>
    </div>

    <!-- Create Estimate Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
      <div class="absolute inset-0 cursor-pointer" @click="showCreateModal = false"></div>
      <div class="relative w-full max-w-xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Crear Estimación</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Diseñar nueva cotización de costos de proyecto</p>

        <form @submit.prevent="handleCreateEstimate" class="space-y-6">
          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Nombre del Proyecto</label>
            <input
              type="text"
              required
              v-model="projectName"
              placeholder="E.g. Proyecto Altiplano - Lote 5"
              class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
            />
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Fase / Categoría</label>
            <input
              type="text"
              v-model="phaseName"
              placeholder="E.g. Cimentación / Estructura"
              class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
            />
          </div>

          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Costo Estimado (Q)</label>
              <input
                type="number"
                required
                step="0.01"
                v-model="costValue"
                placeholder="0.00"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white placeholder:text-white/20"
              />
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Margen de Utilidad (%)</label>
              <input
                type="number"
                v-model="utilityValue"
                placeholder="22"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white placeholder:text-white/20"
              />
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Estado del Presupuesto</label>
            <div class="flex bg-white/5 border border-white/10 rounded-2xl p-1">
              <button
                v-for="st in ['Borrador', 'En Revisión']"
                :key="st"
                type="button"
                @click="statusValue = st"
                :class="[
                  'flex-1 py-3 text-[10px] font-black uppercase tracking-wider rounded-xl transition-all',
                  statusValue === st ? 'bg-primary text-white shadow-md' : 'text-white/40'
                ]"
              >
                {{ st }}
              </button>
            </div>
          </div>

          <div class="flex gap-4 pt-4">
            <button
              type="submit"
              class="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
            >
              Confirmar Estimación
            </button>
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-8 py-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-sm font-bold text-white/50"
            >
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import {
  PlusIcon,
  MagnifyingGlassIcon,
  CheckCircleIcon,
  ArrowTrendingUpIcon,
  ChevronRightIcon,
  WrenchScrewdriverIcon,
  UsersIcon,
  TrophyIcon,
  SparklesIcon,
} from '@heroicons/vue/24/outline';

interface BudgetEstimate {
  id: string;
  project: string;
  phase: string;
  date: string;
  cost: number;
  utility: number;
  status: 'En Revisión' | 'Aprobado' | 'Borrador';
}

const searchTerm = ref('');
const showCreateModal = ref(false);
const notification = ref('');

const projectName = ref('');
const phaseName = ref('');
const costValue = ref('');
const utilityValue = ref('22');
const statusValue = ref('En Revisión');

const budgets = ref<BudgetEstimate[]>([
  {
    id: 'BDG-01',
    project: "Residencial 'La Sierra'",
    phase: 'Estructura',
    date: '24 May, 2026',
    cost: 450200.0,
    utility: 22,
    status: 'En Revisión',
  },
  {
    id: 'BDG-02',
    project: 'Torre Corporativa Delta',
    phase: 'Acabados',
    date: '20 May, 2026',
    cost: 890500.0,
    utility: 28,
    status: 'Aprobado',
  },
  {
    id: 'BDG-03',
    project: 'Complejo Logístico Norte',
    phase: 'Excavación',
    date: '15 May, 2026',
    cost: 1120000.0,
    utility: 19,
    status: 'Borrador',
  },
]);

const graphData = ref([
  { month: 'Ene', budget: 60, real: 65 },
  { month: 'Feb', budget: 75, real: 70 },
  { month: 'Mar', budget: 55, real: 52 },
  { month: 'Abr', budget: 85, real: 92 },
]);

const filteredBudgets = computed(() =>
  budgets.value.filter(
    (b) =>
      b.project.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
      b.phase.toLowerCase().includes(searchTerm.value.toLowerCase())
  )
);

const totalQuoted = computed(() => budgets.value.reduce((acc, b) => acc + b.cost, 0));

const averageMargin = computed(() => {
  if (budgets.value.length === 0) return 0;
  const sum = budgets.value.reduce((acc, b) => acc + b.utility, 0);
  return (sum / budgets.value.length).toFixed(1);
});

function triggerNotification(text: string) {
  notification.value = text;
  setTimeout(() => {
    notification.value = '';
  }, 4000);
}

function handleCreateEstimate() {
  if (!projectName.value || !costValue.value) return;

  const newEst: BudgetEstimate = {
    id: 'BDG-' + Math.floor(Math.random() * 90 + 10),
    project: projectName.value,
    phase: phaseName.value || 'General',
    date: new Date().toLocaleDateString('es-GT', { day: 'numeric', month: 'short', year: 'numeric' }),
    cost: parseFloat(costValue.value),
    utility: parseInt(utilityValue.value),
    status: statusValue.value as BudgetEstimate['status'],
  };

  budgets.value.unshift(newEst);
  showCreateModal.value = false;

  projectName.value = '';
  phaseName.value = '';
  costValue.value = '';
  utilityValue.value = '22';
  statusValue.value = 'En Revisión';

  triggerNotification('¡Estimación creada exitosamente!');
}
</script>
