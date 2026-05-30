<template>
  <transition name="fade-up" appear>
    <div class="flex flex-col gap-6">
      <!-- Command Center Title -->
      <div class="mb-2 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <span class="font-display text-[10px] font-black text-[#0054A3] tracking-widest uppercase">RESUMEN GENERAL</span>
          <h2 class="font-display text-3xl font-black text-slate-800 tracking-tight mt-0.5">Control de Telemetría</h2>
        </div>
      </div>

      <!-- Bento Quick statistics Row -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Pilots Count -->
        <div class="bg-white border border-[#cbd5e1] p-6 shadow-sm hover:border-[#0054A3] transition-all">
          <div class="flex justify-between items-start mb-4">
            <Users class="text-[#0054A3] w-6 h-6" />
            <span class="font-display text-[10px] font-black text-[#0054A3] bg-[#0054A3]/5 px-2 py-0.5 rounded">PILOTOS</span>
          </div>
          <div class="font-display text-4xl font-extrabold text-[#0054A3]">{{ pilotsCount }}</div>
          <p class="font-sans text-xs text-slate-600 font-medium mt-1">
            {{ pilotsActiveCount }} En Turno Activo • {{ pilotsRestingCount }} En Descanso
          </p>
        </div>

        <!-- Vehicles Flota Count -->
        <div class="bg-white border border-[#cbd5e1] p-6 shadow-sm hover:border-[#0054A3] transition-all">
          <div class="flex justify-between items-start mb-4">
            <Truck class="text-[#0054A3] w-6 h-6" />
            <span class="font-display text-[10px] font-black text-[#0054A3] bg-[#0054A3]/5 px-2 py-0.5 rounded">VEHÍCULOS</span>
          </div>
          <div class="font-display text-4xl font-extrabold text-[#0054A3]">{{ vehiclesCount }}</div>
          <p class="font-sans text-xs text-slate-600 font-medium mt-1">
            {{ vehiclesActiveCount }} Rutas Asignadas / {{ vehiclesMaintenanceCount }} En Taller
          </p>
        </div>

        <!-- Machinery Count -->
        <div class="bg-white border border-[#cbd5e1] p-6 shadow-sm hover:border-[#0054A3] transition-all">
          <div class="flex justify-between items-start mb-4">
            <Tractor class="text-[#0054A3] w-6 h-6" />
            <span class="font-display text-[10px] font-black text-[#0054A3] bg-[#0054A3]/5 px-2 py-0.5 rounded">MAQUINARIAS</span>
          </div>
          <div class="font-display text-4xl font-extrabold text-[#0054A3]">{{ machineryCount }}</div>
          <p class="font-sans text-xs text-slate-600 font-medium mt-1">
            Total Registradas en Inventario
          </p>
        </div>

      </div>

      <!-- Graphic charts & core interactive telemetry lists -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Horizontal CSS Chart -->
        <div class="lg:col-span-8 bg-white border border-[#cbd5e1] p-6 flex flex-col justify-between">
          <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
            <div>
              <h3 class="font-display text-base font-bold text-slate-800">Uso Operativo por Equipo</h3>
              <p class="text-xs text-slate-600 font-medium">Baches promedio acumulados en el último mes</p>
            </div>
            <div class="flex gap-4">
              <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-800">
                <span class="w-3 h-3 bg-[#0054A3]"></span>
                <span>Flota Cooitzá</span>
              </div>
              <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-800">
                <span class="w-3 h-3 bg-[#FFD200]"></span>
                <span>Sello Operador</span>
              </div>
            </div>
          </div>

          <!-- Top-Tier Custom Dynamic Graph Grid -->
          <div class="flex-1 flex items-end gap-3 h-48 pb-2 border-b border-[#cbd5e1] relative">
            <!-- Gridlines -->
            <div class="absolute inset-x-0 top-1/4 border-t border-slate-100"></div>
            <div class="absolute inset-x-0 top-2/4 border-t border-slate-100"></div>
            <div class="absolute inset-x-0 top-3/4 border-t border-slate-100"></div>

            <!-- Dynamic Bars -->
            <div v-for="item in horasPorTipo" :key="item.tipo" class="flex-1 flex flex-col items-center justify-end h-full group cursor-pointer z-10" :title="item.horas + ' HRS'">
              <div class="w-full bg-[#cbd5e1] h-1/2 relative">
                <div class="absolute bottom-0 w-full bg-[#0054A3] group-hover:bg-[#004586] transition-all" :style="{ height: item.porcentaje + '%' }"></div>
              </div>
              <span class="font-sans text-[10px] font-bold text-slate-600 mt-2 text-center truncate w-full">{{ item.tipo }}</span>
            </div>
          </div>
          
          <div class="mt-4 text-[10px] font-sans font-medium text-slate-600 text-center uppercase tracking-wide">
            Las unidades se autoajustan de acuerdo a las últimas lecturas enviadas por el personal de obra.
          </div>
        </div>

        <!-- Health diagnostics overview inside Dashboard -->
        <div class="lg:col-span-4 flex flex-col gap-6">
          
          <div class="bg-white border border-[#cbd5e1] p-5 shadow-sm">
            <h3 class="font-display text-xs font-black text-[#0054A3] uppercase tracking-wider mb-4">MÁQUINAS CON MAYOR DESGASTE</h3>
            <div class="space-y-3 font-sans text-xs font-medium">
              <div v-if="topMaquinas.length === 0" class="text-slate-400 italic">No hay registros suficientes.</div>
              <div v-for="(maq, index) in topMaquinas" :key="maq.id" class="flex justify-between items-center text-slate-600 border-b border-slate-100 pb-2 last:border-0">
                <div>
                  <span class="font-bold">{{ index + 1 }}. {{ maq.marca }}</span>
                  <span class="text-[9px] text-slate-400 ml-1">({{ maq.identificador || maq.modelo }})</span>
                </div>
                <span class="text-amber-600 bg-amber-50 border border-amber-200 px-1.5 font-bold font-mono text-[10px]">{{ maq.horas_acumuladas || 0 }} HRS</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { 
  Users, Truck, Tractor
} from "lucide-vue-next";

const props = defineProps<{
  pilotsCount: number;
  pilotsActiveCount: number;
  pilotsRestingCount: number;
  vehiclesCount: number;
  vehiclesActiveCount: number;
  vehiclesMaintenanceCount: number;
  machineryCount: number;
  maquinas?: any[];
}>();

const horasPorTipo = computed(() => {
  if (!props.maquinas || props.maquinas.length === 0) return [];
  const agrupado: Record<string, number> = {};
  let maxHoras = 0;
  props.maquinas.forEach(m => {
    const tipo = m.tipo || 'Otro';
    agrupado[tipo] = (agrupado[tipo] || 0) + (parseFloat(m.horas_acumuladas) || 0);
  });
  const resultado = Object.keys(agrupado).map(tipo => {
    if (agrupado[tipo] > maxHoras) maxHoras = agrupado[tipo];
    return { tipo, horas: agrupado[tipo] };
  });
  return resultado.map(r => ({
    ...r,
    porcentaje: maxHoras > 0 ? (r.horas / maxHoras) * 100 : 0
  }));
});

const topMaquinas = computed(() => {
  if (!props.maquinas) return [];
  return [...props.maquinas].sort((a, b) => (parseFloat(b.horas_acumuladas) || 0) - (parseFloat(a.horas_acumuladas) || 0)).slice(0, 5);
});
</script>

<style scoped>
.fade-up-enter-active,
.fade-up-leave-active {
  transition: opacity 0.4s ease, transform 0.4s ease;
}
.fade-up-enter-from,
.fade-up-leave-to {
  opacity: 0;
  transform: translateY(15px);
}
</style>
