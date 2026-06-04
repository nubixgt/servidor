<template>
  <div class="space-y-10 animate-fade-in">
    <!-- Custom page title heading -->
    <div class="text-center space-y-2 mb-6">
      <h2 class="text-3xl font-extrabold text-[#091426] tracking-tight">
        Tablero de Escrutinio Abierto
      </h2>
      <p class="text-slate-500 text-sm max-w-xl mx-auto">
        Analiza la deliberación de la comunidad en tiempo real. Todas las respuestas son públicas y transparentes.
      </p>
    </div>

    <div class="bg-surface-container-lowest rounded-xl p-6 md:p-8 border border-slate-100 shadow-md">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8 pb-6 border-b border-slate-100">
        <div>
          <h2 class="text-2xl font-bold text-primary-base flex items-center gap-2">
            <LucideIcon name="bar-chart-3" class="w-6 h-6 text-secondary-base" />
            Resultados del Sondeo en Tiempo Real
          </h2>
          <p class="text-xs md:text-sm text-on-surface-variant mt-1">
            Respuestas recopiladas y verificadas mediante nuestro portal seguro.
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button
            @click="$emit('resetVote')"
            class="px-4 py-2 bg-slate-100 text-primary-base rounded-lg text-xs font-semibold hover:bg-slate-200 transition-colors"
          >
            Modificar mi preferencia
          </button>
        </div>
      </div>

      <!-- Metrics Summary Rows -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-slate-50 rounded-lg p-4 flex items-center gap-4 border border-slate-100">
          <div class="w-10 h-10 rounded-full bg-slate-900/5 flex items-center justify-center text-primary-base">
            <LucideIcon name="users" class="w-5 h-5" />
          </div>
          <div>
            <div class="text-2xl font-bold text-primary-base">{{ total }}</div>
            <div class="text-xs text-on-surface-variant font-medium">Participaciones Totales</div>
          </div>
        </div>

        <div class="bg-slate-50 rounded-lg p-4 flex items-center gap-4 border border-slate-100">
          <div class="w-10 h-10 rounded-full bg-secondary-base/5 flex items-center justify-center text-secondary-base">
            <LucideIcon name="award" class="w-5 h-5" />
          </div>
          <div>
            <div class="text-2xl font-bold text-secondary-base">
              {{ votesA > votesB ? 'Opción A' : votesA < votesB ? 'Opción B' : 'Empate' }}
            </div>
            <div class="text-xs text-on-surface-variant font-medium">Preferencia Líder</div>
          </div>
        </div>

        <div class="bg-slate-50 rounded-lg p-4 flex items-center gap-4 border border-slate-100">
          <div class="w-10 h-10 rounded-full bg-emerald-500/5 flex items-center justify-center text-emerald-600">
            <LucideIcon name="clock" class="w-5 h-5" />
          </div>
          <div>
            <div class="text-2xl font-bold text-emerald-600">Activo</div>
            <div class="text-xs text-on-surface-variant font-medium">Cierre: En 3 días</div>
          </div>
        </div>
      </div>

      <!-- Core Votes Layout Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start mb-8">
        <!-- Visual Percent Bar Meters -->
        <div class="space-y-6">
          <h3 class="text-base font-bold text-primary-base uppercase tracking-wider">
            Detalle por Propuestas
          </h3>

          <!-- Option A Progress -->
          <div :class="['p-4 rounded-lg border transition-all', userVote === 'option-a' ? 'bg-indigo-50/20 border-indigo-100' : 'bg-transparent border-slate-100']">
            <div class="flex justify-between items-center mb-2">
              <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-[#091426]" />
                <span class="font-semibold text-primary-base text-sm md:text-base">Gran Baile en el Estadio</span>
                <span v-if="userVote === 'option-a'" class="px-1.5 py-0.5 bg-indigo-100 text-indigo-700 text-[10px] font-bold rounded">Tu Voto</span>
              </div>
              <span class="font-black text-primary-base text-sm">{{ votesA }} votos ({{ pctA }}%)</span>
            </div>
            <div class="w-full bg-slate-100 h-3 rounded-full overflow-hidden">
              <div
                class="bg-[#091426] h-full rounded-full transition-all duration-1000"
                :style="{ width: `${pctA}%` }"
              />
            </div>
            <p class="text-xs text-on-surface-variant mt-2 italic">
              Convocatoria expansiva, ideal para toda la comunidad unificada.
            </p>
          </div>

          <!-- Option B Progress -->
          <div :class="['p-4 rounded-lg border transition-all', userVote === 'option-b' ? 'bg-indigo-50/20 border-indigo-100' : 'bg-transparent border-slate-100']">
            <div class="flex justify-between items-center mb-2">
              <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-[#4648d4]" />
                <span class="font-semibold text-primary-base text-sm md:text-base">Baile en Salón + Concierto</span>
                <span v-if="userVote === 'option-b'" class="px-1.5 py-0.5 bg-indigo-100 text-indigo-700 text-[10px] font-bold rounded">Tu Voto</span>
              </div>
              <span class="font-black text-[#4648d4] text-sm">{{ votesB }} votos ({{ pctB }}%)</span>
            </div>
            <div class="w-full bg-slate-100 h-3 rounded-full overflow-hidden">
              <div
                class="bg-[#4648d4] h-full rounded-full transition-all duration-1000"
                :style="{ width: `${pctB}%` }"
              />
            </div>
            <p class="text-xs text-on-surface-variant mt-2 italic">
              Ambiente íntimo con acústica distinguida y mayor interacción.
            </p>
          </div>

          <!-- Institutional note -->
          <div class="p-3 bg-slate-50 rounded-lg text-xs text-on-surface-variant flex items-start gap-2.5 border border-slate-100">
            <LucideIcon name="shield-alert" class="w-4 h-4 text-slate-500 shrink-0 mt-0.5" />
            <span>
              Un ciudadano equivale a una preferencia registrada. El sistema valida las identificaciones civiles cifradas antes de contabilizar cada aporte democrático.
            </span>
          </div>
        </div>

        <!-- Custom HTML/CSS Bar Graph to replace Recharts -->
        <div class="bg-slate-50 rounded-lg p-4 border border-slate-100 flex flex-col justify-between">
          <div class="mb-4">
            <h4 class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">
              Distribución Gráfica de Preferencias
            </h4>
          </div>
          <div class="h-60 w-full pb-6 relative flex text-[11px] text-[#45474c]">
            <!-- Y Axis -->
            <div class="flex flex-col justify-between items-end pr-2 border-r border-[#45474c]/20 w-8 flex-shrink-0 relative z-10 bg-slate-50 pb-5">
              <span>{{ maxY }}</span>
              <span>{{ Math.round(maxY / 2) }}</span>
              <span>0</span>
            </div>
            
            <!-- Chart Area -->
            <div class="flex-grow flex justify-around items-end pl-2 relative">
              <div v-for="(entry, index) in chartData" :key="index" class="flex flex-col items-center group relative h-full w-full max-w-[50px]">
                <div class="w-full flex-grow flex items-end relative h-full pb-6 z-10">
                   <div 
                     class="w-full rounded-t-[4px] transition-all duration-1000 relative"
                     :style="{ backgroundColor: entry.color, height: `${(entry.votos / maxY) * 100}%` }"
                   >
                     <!-- Tooltip logic to mimic Recharts -->
                     <div class="opacity-0 group-hover:opacity-100 pointer-events-none absolute -top-14 left-1/2 -translate-x-1/2 bg-white border border-[#ECEEF0] rounded-lg px-3 py-2 text-xs font-bold text-primary-base shadow-sm whitespace-nowrap z-50 transition-opacity duration-200">
                       {{ entry.name }}<br/><span style="font-weight:normal;color:#666">{{ entry.name }} : </span><span :style="{color: entry.color}">{{ entry.votos }}</span>
                     </div>
                   </div>
                </div>
                <span class="absolute bottom-0 text-[11px] text-center w-24 left-1/2 -translate-x-1/2 truncate">{{ entry.name }}</span>
              </div>

              <!-- Horizontal Grid Line at bottom -->
              <div class="absolute bottom-6 left-0 right-0 h-px bg-[#45474c]/20 z-0"></div>
            </div>
          </div>
          <div class="text-center pt-2 text-xs text-slate-400 font-mono">
            Actualizado hace unos segundos
          </div>
        </div>
      </div>

      <!-- Advanced insights section -->
      <div class="bg-slate-50/50 rounded-lg p-5 border border-slate-100">
        <h3 class="text-sm font-bold text-primary-base tracking-wider uppercase mb-3">
          Tendencia Demográfica Estimada (por rangos etarios)
        </h3>
        <p class="text-xs text-on-surface-variant mb-4">
          Visualiza qué segmentos de la comunidad se inclinan por cada alternativa según los datos censados.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div v-for="(data, i) in demographicData" :key="i" class="bg-white rounded-lg p-3 border border-slate-100 shadow-sm">
            <span class="text-xs font-bold text-primary-base block mb-2">{{ data.name }}</span>
            <div class="space-y-1.5">
              <div class="flex justify-between text-[11px] font-medium">
                <span class="text-slate-500">Estadio (Opción A):</span>
                <span class="text-primary-base font-bold">{{ data['Opción A (Estadio)'] }}%</span>
              </div>
              <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                <div class="bg-[#091426] h-full" :style="{ width: `${data['Opción A (Estadio)']}%` }" />
              </div>
              <div class="flex justify-between text-[11px] font-medium">
                <span class="text-slate-500">Salón (Opción B):</span>
                <span class="text-[#4648d4] font-bold">{{ data['Opción B (Salón)'] }}%</span>
              </div>
              <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                <div class="bg-[#4648d4] h-full" :style="{ width: `${data['Opción B (Salón)']}%` }" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import LucideIcon from '../components/LucideIcon.vue';

const props = defineProps({
  votesA: {
    type: Number,
    required: true
  },
  votesB: {
    type: Number,
    required: true
  },
  userVote: {
    type: String,
    default: null
  }
});

defineEmits(['resetVote']);

const total = computed(() => props.votesA + props.votesB);
const pctA = computed(() => total.value > 0 ? Math.round((props.votesA / total.value) * 100) : 0);
const pctB = computed(() => total.value > 0 ? Math.round((props.votesB / total.value) * 100) : 0);

// For the bar chart graph simulation
const maxY = computed(() => {
  const max = Math.max(props.votesA, props.votesB, 10);
  return Math.ceil(max * 1.2); // Add 20% headroom
});

const chartData = computed(() => [
  {
    name: 'Estadio (Opción A)',
    votos: props.votesA,
    porcentaje: pctA.value,
    color: '#091426',
  },
  {
    name: 'Salón + Concierto (B)',
    votos: props.votesB,
    porcentaje: pctB.value,
    color: '#4648d4',
  }
]);

const demographicData = [
  { name: 'Jóvenes (18-30)', 'Opción A (Estadio)': 65, 'Opción B (Salón)': 35 },
  { name: 'Adultos (31-50)', 'Opción A (Estadio)': 48, 'Opción B (Salón)': 52 },
  { name: 'Mayores (51+)', 'Opción A (Estadio)': 25, 'Opción B (Salón)': 75 },
];
</script>
