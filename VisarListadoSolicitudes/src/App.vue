<script setup>
import { ref, computed, onMounted } from 'vue';

const totalSolicitudes = ref(89);
const showChart = ref(false);

onMounted(() => {
  // Retraso ligero para que la animación se dispare tras cargar
  setTimeout(() => {
    showChart.value = true;
  }, 300);
});

// Datos basados en la imagen
const statuses = ref([
  { id: 'asignado', name: 'Expediente asignado a análisis:', value: 14, color: '#003ba6', icon: 'folder' },
  { id: 'en_analisis', name: 'En análisis:', value: 3, color: '#0066cc', icon: 'search' },
  { id: 'campo', name: 'Asignados a campo:', value: 3, color: '#00a39c', icon: 'pin_drop' },
  { id: 'visita', name: 'En visita de campo:', value: 8, color: '#4caf50', icon: 'local_shipping' },
  { id: 'analisis_final', name: 'En análisis final:', value: 2, color: '#8bc34a', icon: 'assignment' },
  { id: 'autorizacion', name: 'En autorización:', value: 5, color: '#ffc107', icon: 'verified_user' },
  { id: 'observaciones', name: 'En observaciones de campo:', value: 1, color: '#ff9800', icon: 'visibility' },
  { id: 'correccion', name: 'En proceso de corrección por usuario (subsanar):', value: 10, color: '#ff5722', icon: 'sync' },
  { id: 'rechazado', name: 'Rechazados:', value: 6, color: '#d32f2f', icon: 'cancel' },
  { id: 'constancia', name: 'Constancias emitidas:', value: 37, color: '#673ab7', icon: 'workspace_premium' }
]);

const selectedStatus = ref(null);
const hoveredSegment = ref(null);
const tooltipX = ref(0);
const tooltipY = ref(0);

const selectStatus = (status) => {
  selectedStatus.value = status;
};

const handleMouseOver = (event, segment) => {
  hoveredSegment.value = segment;
  updateTooltipPosition(event);
};

const handleMouseMove = (event) => {
  if (hoveredSegment.value) {
    updateTooltipPosition(event);
  }
};

const handleMouseLeave = () => {
  hoveredSegment.value = null;
};

const updateTooltipPosition = (event) => {
  tooltipX.value = event.clientX;
  tooltipY.value = event.clientY;
};

// Lógica para la gráfica circular (Donut Chart SVG)
const getChartSegments = computed(() => {
  let cumulativeValue = 0;
  return statuses.value.map(item => {
    const targetPercentage = (item.value / totalSolicitudes.value) * 100;
    const percentage = showChart.value ? targetPercentage : 0;
    
    const segment = {
      ...item,
      // Usamos 200 como espacio en blanco (gap) para asegurar que NUNCA se repita el trazo
      // si hay un pequeño error de redondeo en la circunferencia.
      dasharray: `${percentage} 200`,
      dashoffset: -cumulativeValue
    };
    cumulativeValue += targetPercentage;
    return segment;
  });
});


</script>

<template>
  <div class="visar-dashboard bg-[#f5f7fa] min-h-screen flex flex-col relative overflow-hidden">
    <!-- Decoración de fondo -->
    <div class="absolute top-0 right-0 w-96 h-96 bg-blue-100 rounded-full blur-3xl -z-10 animate-[pulse_6s_ease-in-out_infinite]"></div>
    <div class="absolute bottom-0 left-0 w-64 h-64 bg-purple-100 rounded-full blur-3xl -z-10 animate-[pulse_5s_ease-in-out_infinite]"></div>

    <div class="max-w-7xl mx-auto w-full p-6 flex-1 flex flex-col relative z-10 animate-fade-in-up">
      <!-- Cabecera / Logos -->
      <div class="flex items-center justify-between bg-white/80 backdrop-blur-md rounded-2xl p-5 shadow-lg mb-8 border border-white/50 hover:shadow-xl transition-shadow duration-300">
        <div class="flex items-center gap-6">
          <div class="flex flex-col items-center">
            <img src="/images/MAGA.png" alt="Logo MAGA" class="h-16 w-auto object-contain drop-shadow-md hover:scale-105 transition-transform duration-500 ease-in-out" />
          </div>
          <div class="h-12 w-px bg-slate-200"></div>
          <div class="flex flex-col">
            <h1 class="text-3xl font-black text-[#5ba12f] tracking-tighter">VISAR</h1>
            <p class="text-[10px] text-slate-500 font-bold uppercase leading-tight">Viceministerio de Sanidad<br>Agropecuaria y Regulaciones</p>
          </div>
        </div>
        <div class="bg-[#002855] text-white py-3 px-8 rounded-xl shadow-lg relative overflow-hidden flex items-center gap-4">
          <div class="absolute inset-0 bg-gradient-to-r from-[#003ba6] to-transparent opacity-50"></div>
          <span class="material-symbols-outlined text-4xl text-[#8bc34a] relative z-10">fact_check</span>
          <h2 class="text-xl md:text-2xl font-black relative z-10 leading-tight">
            EMISIÓN DE DICTÁMENES<br>
            <span class="text-[#8bc34a]">DE CALIFICACIÓN</span>
          </h2>
        </div>
      </div>

      <!-- Contenido Principal -->
      <div class="grid lg:grid-cols-12 gap-8 animate-fade-in-up" style="animation-delay: 150ms; animation-fill-mode: both;">
        
        <!-- Lista Izquierda -->
        <div class="lg:col-span-4 bg-white/90 backdrop-blur-md rounded-3xl p-6 shadow-xl border border-white/50 flex flex-col h-[600px] transform transition-all hover:shadow-2xl">
          <p class="text-sm text-slate-600 mb-6 font-medium leading-relaxed">
            Cantidad de solicitudes recibidas, aprobadas y rechazadas a la fecha para la obtención del dictamen obligatorio que acredita a los solicitantes como productores primarios o pecuarios.
          </p>
          
          <div class="flex-1 overflow-y-auto overflow-x-hidden space-y-3 px-2 py-2 -mx-2 pr-4 scrollbar-thin scrollbar-thumb-slate-200">
            <button 
              v-for="(status, index) in statuses" 
              :key="status.id"
              @click="selectStatus(status)"
              class="w-full flex items-center justify-between bg-white rounded-full p-2 border-2 shadow-sm transition-all duration-300 group animate-fade-in-left"
              :class="selectedStatus?.id === status.id ? 'shadow-md scale-[1.02]' : 'border-slate-100 hover:border-slate-200 hover:shadow-lg hover:-translate-y-0.5'"
              :style="{ 
                'borderColor': selectedStatus?.id === status.id ? status.color : '',
                'animation-delay': `${index * 50 + 300}ms`, 
                'animation-fill-mode': 'both' 
              }"
            >
              <div class="flex items-center gap-3 flex-1 min-w-0">
                <div 
                  class="w-10 h-10 rounded-full flex items-center justify-center text-white shadow-inner shrink-0 group-hover:scale-110 transition-transform"
                  :style="{ backgroundColor: status.color }"
                >
                  <span class="material-symbols-outlined text-lg">{{ status.icon }}</span>
                </div>
                <div class="flex items-center gap-2 overflow-hidden">
                  <div class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: status.color }"></div>
                  <span class="text-xs font-bold text-slate-700 truncate text-left">{{ status.name }}</span>
                </div>
              </div>
              <div 
                class="px-4 py-1.5 rounded-full text-white font-bold text-sm shadow-sm shrink-0 ml-2 group-hover:scale-105 transition-transform"
                :style="{ backgroundColor: status.color }"
              >
                {{ status.value }}
              </div>
            </button>
          </div>
        </div>

        <!-- Gráfica Central y Total -->
        <div class="lg:col-span-8 bg-white/90 backdrop-blur-md rounded-3xl p-8 shadow-xl border border-white/50 flex flex-col items-center justify-center relative min-h-[600px] transition-all hover:shadow-2xl">
          
          <div class="absolute right-8 top-1/2 -translate-y-1/2 bg-white rounded-3xl p-6 shadow-2xl border border-slate-100 flex flex-col items-center animate-fade-in-right" style="animation-delay: 500ms; animation-fill-mode: both;">
            <div class="w-16 h-16 bg-[#002855] rounded-full flex items-center justify-center text-white shadow-inner mb-4 transform hover:rotate-12 transition-transform duration-300">
              <span class="material-symbols-outlined text-3xl">groups</span>
            </div>
            <p class="text-xs font-bold text-slate-800 uppercase text-center mb-1">Total de<br>Solicitudes</p>
            <p class="text-5xl font-black text-[#8bc34a] drop-shadow-sm">{{ totalSolicitudes }}</p>
            <div class="w-8 h-1 bg-[#8bc34a] rounded-full mt-4"></div>
          </div>

          <div class="relative w-full max-w-[450px] aspect-square animate-zoom-in" style="animation-delay: 200ms; animation-fill-mode: both;">
            <!-- SVG Donut Chart -->
            <svg viewBox="0 0 42 42" class="w-full h-full drop-shadow-2xl transform -rotate-90">
              <!-- Circulo base (fondo invisible o gris claro si se desea) -->
              <circle cx="21" cy="21" r="15.91549431" fill="none" stroke="#f1f5f9" stroke-width="6" pathLength="100" />
              
              <!-- Segmentos -->
              <circle 
                v-for="segment in getChartSegments" 
                :key="'seg-'+segment.id"
                cx="21" 
                cy="21" 
                r="15.91549431" 
                fill="none" 
                pointer-events="stroke"
                pathLength="100"
                :stroke="segment.color" 
                stroke-width="7"
                :stroke-dasharray="segment.dasharray" 
                :stroke-dashoffset="segment.dashoffset"
                class="transition-all duration-[1500ms] ease-out origin-center cursor-pointer hover:stroke-width-[8]"
                @click="selectStatus(segment)"
                @mouseenter="handleMouseOver($event, segment)"
                @mousemove="handleMouseMove"
                @mouseleave="handleMouseLeave"
              >
              </circle>
            </svg>

            <!-- Centro del Donut -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
              <div class="bg-white/80 backdrop-blur-sm w-[55%] h-[55%] rounded-full shadow-2xl flex flex-col items-center justify-center p-4 border border-white/50 transform hover:scale-105 transition-transform duration-500">
                <span class="material-symbols-outlined text-slate-400 mb-1">assignment_turned_in</span>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total</p>
                <p class="text-4xl font-black text-[#002855] leading-none my-1">{{ totalSolicitudes }}</p>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Solicitudes</p>
              </div>
            </div>
            
            <!-- Etiquetas flotantes -->
            <div v-if="selectedStatus" class="absolute -bottom-16 left-1/2 -translate-x-1/2 bg-white px-6 py-3 rounded-2xl shadow-lg border border-slate-100 flex items-center gap-3 animate-bounce">
              <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: selectedStatus.color }"></div>
              <p class="text-sm font-bold text-slate-700">{{ selectedStatus.name }} <span class="text-lg" :style="{ color: selectedStatus.color }">{{ selectedStatus.value }}</span></p>
            </div>
          </div>
        </div>
      </div>

      <!-- Sección de Detalles (cuando se selecciona un ítem) -->
      <div v-if="selectedStatus" class="mt-8 bg-white/90 backdrop-blur-md rounded-3xl p-6 shadow-xl border border-white/50 transition-all animate-fade-in-up">
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white shadow-inner" :style="{ backgroundColor: selectedStatus.color }">
              <span class="material-symbols-outlined">{{ selectedStatus.icon }}</span>
            </div>
            <div>
              <h3 class="text-xl font-black text-slate-800">Detalles de solicitudes</h3>
              <p class="text-sm text-slate-500">Mostrando registros para: <strong :style="{ color: selectedStatus.color }">{{ selectedStatus.name }}</strong></p>
            </div>
          </div>
          <div class="text-2xl font-black px-4 py-2 rounded-xl bg-slate-50 border border-slate-100" :style="{ color: selectedStatus.color }">
            {{ selectedStatus.value }} Registros
          </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200">
          <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
              <tr>
                <th class="px-4 py-3">No. Expediente</th>
                <th class="px-4 py-3">Productor</th>
                <th class="px-4 py-3">Fecha de Ingreso</th>
                <th class="px-4 py-3">Asignado a</th>
                <th class="px-4 py-3">Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="i in Math.min(selectedStatus.value, 5)" :key="i" class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                <td class="px-4 py-3 font-medium text-blue-600">EXP-2026-0{{ i }}9{{ selectedStatus.value }}</td>
                <td class="px-4 py-3 text-slate-700">Productor Agrícola Ejemplo S.A.</td>
                <td class="px-4 py-3 text-slate-500">01/08/2026</td>
                <td class="px-4 py-3 text-slate-700">
                  <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-600">IN</div>
                    Inspector Nombre
                  </div>
                </td>
                <td class="px-4 py-3">
                  <span class="px-2 py-1 rounded-full text-xs font-bold text-white shadow-sm" :style="{ backgroundColor: selectedStatus.color }">
                    {{ selectedStatus.name.replace(':', '') }}
                  </span>
                </td>
              </tr>
              <tr v-if="selectedStatus.value === 0">
                <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                  No hay solicitudes en este estado.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
    </div>

    <!-- Footer -->
    <div class="w-full h-24 sm:h-32 flex items-end relative overflow-hidden mt-8 shrink-0">
      <!-- SVG Waves Background -->
      <svg class="absolute bottom-0 w-full h-full object-fill z-0 pointer-events-none" preserveAspectRatio="none" viewBox="0 0 1440 120">
        <path d="M0,70 C320,130 500,20 1440,50 L1440,120 L0,120 Z" fill="#5ba12f" />
        <path d="M600,120 C900,120 1000,10 1440,0 L1440,120 L600,120 Z" fill="#002855" />
      </svg>

      <div class="relative z-10 w-full max-w-7xl mx-auto px-6 pb-6 flex items-center justify-between">
        <!-- Left Badge -->
        <div class="bg-white rounded-full py-1.5 px-2 pr-6 shadow-lg flex items-center gap-3 transform hover:-translate-y-1 transition-transform duration-500 ease-out cursor-default">
          <div class="bg-[#5ba12f] text-white rounded-full w-8 h-8 flex items-center justify-center shadow-inner animate-[spin_4s_linear_infinite]" style="animation-play-state: paused;" onmouseover="this.style.animationPlayState='running'" onmouseout="this.style.animationPlayState='paused'">
            <span class="material-symbols-outlined text-lg">calendar_month</span>
          </div>
          <span class="text-sm font-bold text-slate-700">Datos a la fecha de emisión del informe</span>
        </div>

        <!-- Right Logo -->
        <div class="flex items-center gap-2 text-white transform hover:scale-105 transition-transform duration-500 ease-out cursor-default">
          <div class="relative w-10 h-10 flex items-center justify-center">
            <span class="material-symbols-outlined text-4xl text-[#8bc34a] absolute animate-[pulse_3s_ease-in-out_infinite]">eco</span>
            <span class="material-symbols-outlined text-4xl text-white/20 absolute -ml-1 mt-1">eco</span>
          </div>
          <div class="flex flex-col border-l border-[#8bc34a]/50 pl-3">
            <span class="text-[8px] tracking-[0.2em] text-[#8bc34a] font-bold uppercase leading-none mb-1">Sanidad Agropecuaria</span>
            <span class="text-xl font-black leading-none tracking-wide text-white drop-shadow-md">INOCUIDAD</span>
            <span class="text-[9px] font-bold tracking-widest uppercase text-white/90 leading-tight mt-1">Y Competitividad</span>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- Tooltip Personalizado -->
  <div 
    v-if="hoveredSegment"
    class="fixed z-50 pointer-events-none transform -translate-x-1/2 -translate-y-full mb-4 bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl p-4 border border-white/50 animate-zoom-in min-w-[200px]"
    :style="{ left: tooltipX + 'px', top: (tooltipY - 10) + 'px' }"
  >
    <div class="flex items-center gap-3 mb-2">
      <div class="w-8 h-8 rounded-full flex items-center justify-center text-white shadow-md" :style="{ backgroundColor: hoveredSegment.color }">
        <span class="material-symbols-outlined text-sm">{{ hoveredSegment.icon }}</span>
      </div>
      <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Estado Actual</span>
    </div>
    <p class="text-sm font-bold text-slate-800 leading-tight mb-2">{{ hoveredSegment.name.replace(':', '') }}</p>
    <div class="flex items-end justify-between border-t border-slate-100 pt-2">
      <span class="text-xs font-bold text-slate-400">Total</span>
      <span class="text-2xl font-black" :style="{ color: hoveredSegment.color }">{{ hoveredSegment.value }}</span>
    </div>
  </div>
</template>

<style>
@keyframes fade-in-up {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes fade-in-left {
  from { opacity: 0; transform: translateX(-30px); }
  to { opacity: 1; transform: translateX(0); }
}
@keyframes fade-in-right {
  from { opacity: 0; transform: translateX(30px); }
  to { opacity: 1; transform: translateX(0); }
}
@keyframes zoom-in {
  from { opacity: 0; transform: scale(0.85); }
  to { opacity: 1; transform: scale(1); }
}

.animate-fade-in-up {
  animation: fade-in-up 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.animate-fade-in-left {
  animation: fade-in-left 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.animate-fade-in-right {
  animation: fade-in-right 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.animate-zoom-in {
  animation: zoom-in 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
