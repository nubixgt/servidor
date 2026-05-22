<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Presupuestos y Estimaciones</h2>
        <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Gestione sus costos operativos y estimaciones de avance</p>
      </div>
      <div class="flex gap-4 flex-wrap">
        <button
          @click="showAddItemModal = true"
          class="px-6 py-4 rounded-3xl border border-white/5 bg-white/5 text-white/80 font-black text-xs uppercase tracking-widest hover:bg-white/10 hover:scale-105 transition-all flex items-center gap-2"
        >
          <PlusIcon class="w-4 h-4" /> Partida Presupuestaria
        </button>
        <button
          @click="showAddEstimationModal = true"
          class="glass-button-primary bg-primary border border-primary text-white px-8 py-4 rounded-3xl text-xs font-black uppercase tracking-widest shadow-2xl flex items-center gap-3 hover:scale-105 active:scale-95 transition-all"
        >
          <PlusIcon class="w-4 h-4" /> Crear Estimación
        </button>
      </div>
    </div>

    <!-- Bento Grid Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <!-- Stat Card 1 -->
      <div class="glass-card p-8 rounded-[40px] border border-white/5 flex flex-col justify-between h-52 relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-24 h-24 bg-primary/5 rounded-full blur-xl"></div>
        <div>
          <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.25em]">Costo Estimado Global</span>
          <div class="flex items-baseline gap-3 mt-4">
            <h3 class="text-3xl font-black italic tracking-tighter">Q{{ totalGlobalCost.toLocaleString('es-GT', {minimumFractionDigits: 2}) }}</h3>
          </div>
        </div>
        <div>
          <div class="w-full bg-white/5 h-2 rounded-full overflow-hidden mb-2">
            <div class="bg-primary h-full rounded-full transition-all duration-1000" :style="{ width: (totalGlobalCost / (totalBudgetCost || 1) * 100) + '%' }"></div>
          </div>
          <p class="text-[9px] font-bold text-white/30 uppercase tracking-wider">Avance Financiero General</p>
        </div>
      </div>

      <!-- Stat Card 3 -->
      <div class="glass-card p-8 rounded-[40px] border border-white/5 flex flex-col justify-between h-52 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-24 h-24 bg-amber-500/5 rounded-full blur-xl"></div>
        <div>
          <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.25em]">Estimaciones en Revisión</span>
          <div class="flex items-baseline gap-3 mt-4">
            <h3 class="text-3xl font-black italic tracking-tighter">{{ pendingEstimationsCount }}</h3>
            <span class="text-[9px] font-black bg-amber-500/20 text-amber-400 px-2.5 py-0.5 rounded-lg uppercase tracking-wider">Pendientes</span>
          </div>
        </div>
      </div>

      <!-- Stat Card 4 -->
      <div class="glass-card p-8 rounded-[40px] border border-white/5 flex flex-col justify-between h-52">
        <div>
          <span class="text-[10px] font-black text-white/30 uppercase tracking-[0.25em]">Partidas Registradas</span>
          <div class="flex items-baseline gap-3 mt-4">
            <h3 class="text-3xl font-black italic tracking-tighter">{{ budgetItems.length }}</h3>
          </div>
        </div>
        <div>
          <p class="text-[10px] text-white/50 leading-relaxed font-bold">
            Catálogo de partidas presupuestarias base actualizado.
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
              <h3 class="text-2xl font-black text-white italic uppercase tracking-tighter">Estimaciones de Avance</h3>
              <p class="text-[10px] font-bold text-white/30 uppercase tracking-widest mt-1">Estimaciones vigentes por proyecto y período</p>
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
                  <th class="px-10 py-6">Período</th>
                  <th class="px-10 py-6 text-right">Costo Calculado</th>
                  <th class="px-10 py-6 text-center">Partidas</th>
                  <th class="px-10 py-6 text-right">Estado</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr v-if="filteredEstimations.length === 0">
                  <td colspan="5" class="px-10 py-16 text-center text-white/30 font-bold uppercase tracking-widest text-xs">
                    No hay estimaciones activas que coincidan con la búsqueda.
                  </td>
                </tr>
                <tr
                  v-for="est in filteredEstimations"
                  :key="est.id"
                  class="hover:bg-white/5 transition-all duration-300 cursor-pointer group"
                >
                  <td class="px-10 py-6">
                    <h5 class="font-extrabold text-base text-white tracking-tight uppercase italic">{{ est.project_name || 'N/A' }}</h5>
                    <p class="text-[10px] font-bold text-white/30 tracking-wider uppercase mt-1">EST-{{ est.id }}</p>
                  </td>
                  <td class="px-10 py-6 text-xs text-white/50 font-bold uppercase">{{ est.periodo }}</td>
                  <td class="px-10 py-6 text-right font-black italic text-base text-white">
                    Q{{ Number(est.costo_calculado || 0).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                  </td>
                  <td class="px-10 py-6 text-center text-xs font-bold text-white/60">
                    {{ est.items?.length || 0 }} items
                  </td>
                  <td class="px-10 py-6 text-right">
                    <span :class="[
                      'px-3.5 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border',
                      est.estado === 'Aprobado' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                      est.estado === 'En Revisión' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' :
                      'bg-white/5 text-white/40 border-white/10'
                    ]">
                      {{ est.estado }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="p-8 border-t border-white/5 bg-black/10 flex justify-between items-center text-[10px] font-black text-white/20 uppercase tracking-widest">
            <span>Mostrando {{ filteredEstimations.length }} de {{ estimations.length }} elementos</span>
          </div>
        </div>
      </div>

      <!-- Detailed Cost Breakdown (Right Column) -->
      <div class="lg:col-span-1 space-y-8">
        <div class="glass-card p-10 rounded-[44px] border-l-4 border-primary space-y-8">
          <div>
            <h3 class="text-xl font-black italic uppercase tracking-tighter">Desglose de Costos</h3>
            <p class="text-[10px] font-black text-white/35 uppercase tracking-widest mt-1">Categorías base</p>
          </div>

          <div class="space-y-6">
            <div v-for="cat in costBreakdown" :key="cat.name" class="space-y-3">
              <div class="flex justify-between items-center text-xs font-extrabold uppercase tracking-wide">
                <span class="flex items-center gap-2 text-white/60">
                  <component :is="cat.icon" class="w-3.5 h-3.5 text-primary" />
                  {{ cat.name }}
                </span>
                <span class="font-black text-white">{{ cat.percent }}%</span>
              </div>
              <div class="flex justify-between text-[9px] font-bold text-white/30 tracking-widest uppercase mb-1">
                <span>Total: Q{{ cat.cost.toLocaleString('es-GT', {minimumFractionDigits: 2}) }}</span>
              </div>
              <div class="w-full bg-white/5 h-2 rounded-full overflow-hidden">
                <div class="bg-primary h-full rounded-full transition-all duration-1000" :style="{ width: cat.percent + '%' }"></div>
              </div>
            </div>

            <div v-if="costBreakdown.length === 0" class="text-xs text-white/40 italic py-4">
              Aún no hay partidas para analizar el desglose.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Visual Chart Trends -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
      <!-- Trend Graph Visualization -->
      <div class="lg:col-span-3 glass-card p-10 rounded-[48px] border border-white/5 space-y-8">
        <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
          <div>
            <h3 class="text-2xl font-black italic uppercase tracking-tighter text-white">Avance Presupuestado vs Real</h3>
            <p class="text-[10px] font-black text-white/30 uppercase tracking-widest mt-1">Comparativa general (Simulación Gráfica)</p>
          </div>
          <div class="flex items-center gap-6">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-primary"></div>
              <span class="text-[10px] font-black uppercase tracking-widest text-white/40">Presupuestado</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-rose-500"></div>
              <span class="text-[10px] font-black uppercase tracking-widest text-white/40">Real Estimado</span>
            </div>
          </div>
        </div>

        <div class="pt-8 pb-4 h-64 flex items-end gap-12 md:gap-16 border-l border-b border-white/5 pl-6 relative">
          <div class="absolute left-0 right-0 top-1/4 h-[1px] bg-white/[0.02] border-dashed pointer-events-none"></div>
          <div class="absolute left-0 right-0 top-2/4 h-[1px] bg-white/[0.02] border-dashed pointer-events-none"></div>
          <div class="absolute left-0 right-0 top-3/4 h-[1px] bg-white/[0.02] border-dashed pointer-events-none"></div>

          <div
            v-for="(data, idx) in dynamicGraphData"
            :key="idx"
            class="flex-grow flex flex-col items-center h-full justify-end gap-3 group"
          >
            <div class="w-full flex items-end h-full gap-2 px-1 max-w-[80px]">
              <!-- Budget bar -->
              <div
                class="flex-1 bg-primary/20 hover:bg-primary border border-primary/20 rounded-t-xl group-hover:scale-105 transition-all text-center relative"
                :style="{ height: `${data.budget}%` }"
              >
                <span class="absolute -top-7 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 text-[10px] font-black bg-slate-900 border border-white/10 px-1 py-0.5 rounded text-white z-10 whitespace-nowrap">Q{{ (data.budgetVal/1000).toFixed(1) }}k</span>
              </div>
              <!-- Real bar -->
              <div
                class="flex-1 bg-rose-500/20 hover:bg-rose-500 border border-rose-500/20 rounded-t-xl transition-all relative"
                :style="{ height: `${data.real}%` }"
              >
                <span class="absolute -top-7 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 text-[10px] font-black bg-slate-900 border border-white/10 px-1 py-0.5 rounded text-rose-400 z-10 whitespace-nowrap">Q{{ (data.realVal/1000).toFixed(1) }}k</span>
              </div>
            </div>
            <span class="text-[9px] font-black uppercase tracking-widest text-white/40 mt-1 whitespace-nowrap overflow-hidden text-ellipsis w-16 text-center" :title="data.label">{{ data.label }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Partida Presupuestaria -->
    <div v-if="showAddItemModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
      <div class="absolute inset-0 cursor-pointer" @click="showAddItemModal = false"></div>
      <div class="relative w-full max-w-2xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white max-h-[90vh] overflow-y-auto custom-scrollbar">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Partida Presupuestaria</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Defina los componentes del presupuesto</p>

        <form @submit.prevent="submitBudgetItem" class="space-y-6">
          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Proyecto *</label>
              <select required v-model="newItem.project_id" class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold text-white focus:outline-none cursor-pointer">
                <option value="" disabled class="bg-slate-900 text-white/50">Seleccione proyecto...</option>
                <option v-for="proj in projects" :key="proj.id" :value="proj.id" class="bg-slate-900">{{ proj.nombre }}</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Nombre de Partida *</label>
              <input
                type="text"
                required
                v-model="newItem.nombre_partida"
                placeholder="E.g. Excavación de zanjas"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Tipo / Categoría *</label>
              <select required v-model="newItem.categoria" class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold text-white focus:outline-none cursor-pointer">
                <option value="" disabled class="bg-slate-900 text-white/50">Seleccione categoría...</option>
                <option value="Material" class="bg-slate-900">Material</option>
                <option value="Mano de Obra" class="bg-slate-900">Mano de Obra</option>
                <option value="Maquinaria" class="bg-slate-900">Maquinaria</option>
                <option value="Subcontrato" class="bg-slate-900">Subcontrato</option>
                <option value="Indirectos" class="bg-slate-900">Indirectos</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Unidad de Medida *</label>
              <input
                type="text"
                required
                v-model="newItem.unidad_medida"
                placeholder="E.g. m3, gl, ml, unidad"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Cantidad Estimada *</label>
              <input
                type="number"
                required
                step="0.01"
                min="0.01"
                v-model="newItem.cantidad_estimada"
                placeholder="0.00"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white placeholder:text-white/20"
              />
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Precio Unitario (Q) *</label>
              <input
                type="number"
                required
                step="0.01"
                min="0.01"
                v-model="newItem.precio_unitario"
                placeholder="0.00"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white placeholder:text-white/20"
              />
            </div>
          </div>

          <div class="flex gap-4 pt-4">
            <button
              type="submit"
              class="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
            >
              Guardar Partida
            </button>
            <button
              type="button"
              @click="showAddItemModal = false"
              class="px-8 py-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-sm font-bold text-white/50"
            >
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Estimación de Avance -->
    <div v-if="showAddEstimationModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
      <div class="absolute inset-0 cursor-pointer" @click="showAddEstimationModal = false"></div>
      <div class="relative w-full max-w-3xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white max-h-[90vh] overflow-y-auto custom-scrollbar">
        <h3 class="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Estimación de Avance</h3>
        <p class="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Reporte periódico del progreso de obra</p>

        <form @submit.prevent="submitEstimation" class="space-y-6">
          <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Proyecto *</label>
              <select required v-model="newEstimation.project_id" @change="loadItemsForProject" class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold text-white focus:outline-none cursor-pointer">
                <option value="" disabled class="bg-slate-900 text-white/50">Seleccione proyecto...</option>
                <option v-for="proj in projects" :key="proj.id" :value="proj.id" class="bg-slate-900">{{ proj.nombre }}</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Período de Estimación *</label>
              <input
                type="text"
                required
                v-model="newEstimation.periodo"
                placeholder="E.g. Semana 1 (1 al 7 de Octubre)"
                class="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 text-white"
              />
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Observaciones</label>
            <textarea
              v-model="newEstimation.observaciones"
              rows="2"
              placeholder="Detalles del progreso..."
              class="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white placeholder:text-white/20"
            ></textarea>
          </div>

          <!-- Partidas del proyecto -->
          <div v-if="newEstimation.project_id" class="mt-6 border border-white/10 rounded-3xl p-6 bg-black/20 space-y-4">
            <h4 class="text-xs font-black uppercase tracking-widest text-primary mb-4">Avance por Partidas</h4>
            <div v-if="projectBudgetItems.length === 0" class="text-xs text-white/40 italic text-center py-4">
              Este proyecto no tiene partidas presupuestarias registradas.
            </div>
            <div v-for="(item, index) in projectBudgetItems" :key="item.id" class="flex items-center gap-4 bg-white/5 p-4 rounded-2xl">
              <div class="flex-1">
                <h5 class="text-sm font-black text-white uppercase">{{ item.nombre_partida }}</h5>
                <p class="text-[10px] text-white/40 font-bold tracking-widest uppercase">{{ item.categoria }} • {{ item.cantidad_estimada }} {{ item.unidad_medida }} a Q{{ item.precio_unitario }}</p>
              </div>
              <div class="w-32">
                <div class="relative">
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    v-model="item.avance_real"
                    placeholder="0"
                    class="w-full glass-input rounded-xl p-3 pr-8 text-sm font-bold text-right text-white"
                  />
                  <span class="absolute right-3 top-1/2 -translate-y-1/2 text-white/50 text-xs font-black">%</span>
                </div>
              </div>
            </div>
          </div>

          <div class="flex gap-4 pt-6">
            <button
              type="submit"
              class="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
            >
              Guardar Estimación
            </button>
            <button
              type="button"
              @click="showAddEstimationModal = false"
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
import { ref, computed, onMounted } from 'vue';
import api from '../../services/api'
import Swal from 'sweetalert2';
import {
  PlusIcon,
  MagnifyingGlassIcon,
  ArrowTrendingUpIcon,
  WrenchScrewdriverIcon,
  UsersIcon,
  TrophyIcon,
  BriefcaseIcon,
  DocumentIcon
} from '@heroicons/vue/24/outline';



const projects = ref<any[]>([]);
const budgetItems = ref<any[]>([]);
const estimations = ref<any[]>([]);

const iconMap: Record<string, any> = {
  Material: WrenchScrewdriverIcon,
  'Mano de Obra': UsersIcon,
  Maquinaria: TrophyIcon,
  Subcontrato: BriefcaseIcon,
  Indirectos: DocumentIcon
};

// Variables locales para el avance
const projectBudgetItems = ref<any[]>([]);

const searchTerm = ref('');
const showAddItemModal = ref(false);
const showAddEstimationModal = ref(false);

const newItem = ref({
  project_id: '',
  nombre_partida: '',
  categoria: '',
  unidad_medida: '',
  cantidad_estimada: '',
  precio_unitario: ''
});

const newEstimation = ref({
  project_id: '',
  periodo: '',
  observaciones: ''
});

const totalBudgetCost = computed(() => {
  return budgetItems.value.reduce((sum, item) => sum + (Number(item.cantidad_estimada) * Number(item.precio_unitario)), 0);
});

const costBreakdown = computed(() => {
  const breakdown: Record<string, number> = {
    Material: 0,
    'Mano de Obra': 0,
    Maquinaria: 0,
    Subcontrato: 0,
    Indirectos: 0
  };
  
  budgetItems.value.forEach(item => {
    const cost = Number(item.cantidad_estimada) * Number(item.precio_unitario);
    if (breakdown[item.categoria] !== undefined) {
      breakdown[item.categoria] += cost;
    }
  });

  const total = totalBudgetCost.value || 1; // avoid div by 0

  return [
    { name: 'Material', cost: breakdown['Material'], percent: Math.round((breakdown['Material'] / total) * 100), icon: iconMap['Material'] },
    { name: 'Mano de Obra', cost: breakdown['Mano de Obra'], percent: Math.round((breakdown['Mano de Obra'] / total) * 100), icon: iconMap['Mano de Obra'] },
    { name: 'Maquinaria', cost: breakdown['Maquinaria'], percent: Math.round((breakdown['Maquinaria'] / total) * 100), icon: iconMap['Maquinaria'] },
    { name: 'Subcontrato', cost: breakdown['Subcontrato'], percent: Math.round((breakdown['Subcontrato'] / total) * 100), icon: iconMap['Subcontrato'] },
    { name: 'Indirectos', cost: breakdown['Indirectos'], percent: Math.round((breakdown['Indirectos'] / total) * 100), icon: iconMap['Indirectos'] },
  ].filter(c => c.cost > 0);
});

const dynamicGraphData = computed(() => {
  // Show up to 4 active projects for comparison
  const data = projects.value.slice(0, 4).map(p => {
    const pItems = budgetItems.value.filter(bi => bi.project_id === p.id);
    const pEsts = estimations.value.filter(e => e.project_id === p.id);

    const totalBudget = pItems.reduce((s, i) => s + (Number(i.cantidad_estimada) * Number(i.precio_unitario)), 0);
    const totalReal = pEsts.reduce((s, e) => s + Number(e.costo_calculado), 0);

    const max = Math.max(totalBudget, totalReal, 1);
    
    return {
      label: p.nombre,
      budget: Math.round((totalBudget / max) * 100) || 0,
      real: Math.round((totalReal / max) * 100) || 0,
      budgetVal: totalBudget,
      realVal: totalReal
    };
  });

  return data.length ? data : [{ label: 'Sin datos', budget: 0, real: 0, budgetVal: 0, realVal: 0 }];
});

onMounted(() => {
  fetchProjects();
  fetchBudgetItems();
  fetchEstimations();
});

const fetchProjects = async () => {
  try {
    const res = await api.get(`/projects`);
    if (res.data.status === 'success') {
      projects.value = res.data.data;
    }
  } catch (error) {
    console.error(error);
  }
};

const fetchBudgetItems = async () => {
  try {
    const res = await api.get(`/budget-items`);
    if (res.data.status === 'success') {
      budgetItems.value = res.data.data;
    }
  } catch (error) {
    console.error(error);
  }
};

const fetchEstimations = async () => {
  try {
    const res = await api.get(`/estimations`);
    if (res.data.status === 'success') {
      estimations.value = res.data.data;
    }
  } catch (error) {
    console.error(error);
  }
};

const totalGlobalCost = computed(() => {
  return estimations.value.reduce((sum, est) => sum + (Number(est.costo_calculado) || 0), 0);
});

const pendingEstimationsCount = computed(() => {
  return estimations.value.filter(e => e.estado === 'En Revisión').length;
});

const filteredEstimations = computed(() => {
  return estimations.value.filter(e => {
    const projName = (e.project_name || '').toLowerCase();
    const period = (e.periodo || '').toLowerCase();
    const search = searchTerm.value.toLowerCase();
    return projName.includes(search) || period.includes(search);
  });
});

const loadItemsForProject = () => {
  // Filtrar las partidas del proyecto seleccionado y prepararlas para el formulario de avance
  projectBudgetItems.value = budgetItems.value
    .filter(bi => bi.project_id === newEstimation.value.project_id)
    .map(bi => ({ ...bi, avance_real: '' })); // Iniciar el input vacío
};

const submitBudgetItem = async () => {
  try {
    const res = await api.post(`/budget-items`, newItem.value);
    if (res.data.status === 'success') {
      showAddItemModal.value = false;
      newItem.value = {
        project_id: '', nombre_partida: '', categoria: '', unidad_medida: '', cantidad_estimada: '', precio_unitario: ''
      };
      fetchBudgetItems();
      Swal.fire({
        title: '¡Éxito!',
        text: 'Partida registrada correctamente',
        icon: 'success',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
        customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
      });
    } else {
      throw new Error(res.data.message);
    }
  } catch (error: any) {
    Swal.fire({
      title: 'Error',
      text: error.message || 'Error de red',
      icon: 'error',
      background: '#0f172a',
      color: '#fff',
      confirmButtonColor: '#6366f1',
      customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
    });
  }
};

const submitEstimation = async () => {
  // Construir payload
  const itemsPayload = projectBudgetItems.value.map(item => ({
    budget_item_id: item.id,
    porcentaje_avance: parseFloat(item.avance_real) || 0
  }));

  const payload = {
    ...newEstimation.value,
    items: itemsPayload
  };

  try {
    const res = await api.post(`/estimations`, payload);
    if (res.data.status === 'success') {
      showAddEstimationModal.value = false;
      newEstimation.value = { project_id: '', periodo: '', observaciones: '' };
      projectBudgetItems.value = [];
      fetchEstimations();
      Swal.fire({
        title: '¡Estimación Creada!',
        text: 'La estimación de avance ha sido registrada correctamente',
        icon: 'success',
        background: '#0f172a',
        color: '#fff',
        confirmButtonColor: '#6366f1',
        customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
      });
    } else {
      throw new Error(res.data.message);
    }
  } catch (error: any) {
    Swal.fire({
      title: 'Error',
      text: error.message || 'Error de red',
      icon: 'error',
      background: '#0f172a',
      color: '#fff',
      confirmButtonColor: '#6366f1',
      customClass: { popup: 'border border-white/10 rounded-3xl shadow-2xl', confirmButton: 'rounded-xl px-6 py-3 font-bold' }
    });
  }
};
</script>
