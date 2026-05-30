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

            <!-- Bar 1: Tractor -->
            <div class="flex-1 flex flex-col items-center justify-end h-full group cursor-pointer z-10">
              <div class="w-full bg-[#cbd5e1] h-1/2 relative">
                <div class="absolute bottom-0 w-full bg-[#0054A3] h-[75%] group-hover:bg-[#004586] transition-all"></div>
              </div>
              <span class="font-sans text-[10px] font-bold text-slate-600 mt-2 text-center truncate w-full">Tractores</span>
            </div>

            <!-- Bar 2: Excavadoras -->
            <div class="flex-1 flex flex-col items-center justify-end h-full group cursor-pointer z-10">
              <div class="w-full bg-[#cbd5e1] h-1/2 relative">
                <div class="absolute bottom-0 w-full bg-[#FFD200] h-[95%] group-hover:brightness-95 transition-all"></div>
              </div>
              <span class="font-sans text-[10px] font-bold text-slate-600 mt-2 text-center truncate w-full">Excavadoras</span>
            </div>

            <!-- Bar 3: Retro -->
            <div class="flex-1 flex flex-col items-center justify-end h-full group cursor-pointer z-10">
              <div class="w-full bg-[#cbd5e1] h-1/2 relative">
                <div class="absolute bottom-0 w-full bg-[#0054A3] h-[60%] group-hover:bg-[#004586] transition-all"></div>
              </div>
              <span class="font-sans text-[10px] font-bold text-slate-600 mt-2 text-center truncate w-full">Retros</span>
            </div>

            <!-- Bar 4: Volquetes -->
            <div class="flex-1 flex flex-col items-center justify-end h-full group cursor-pointer z-10">
              <div class="w-full bg-[#cbd5e1] h-1/2 relative">
                <div class="absolute bottom-0 w-full bg-[#FFD200] h-[85%] group-hover:brightness-95 transition-all"></div>
              </div>
              <span class="font-sans text-[10px] font-bold text-slate-600 mt-2 text-center truncate w-full">Volteos</span>
            </div>

            <!-- Bar 5: Pipa -->
            <div class="flex-1 flex flex-col items-center justify-end h-full group cursor-pointer z-10">
              <div class="w-full bg-[#cbd5e1] h-1/2 relative">
                <div class="absolute bottom-0 w-full bg-[#0054A3] h-[40%] group-hover:bg-[#004586] transition-all"></div>
              </div>
              <span class="font-sans text-[10px] font-bold text-slate-600 mt-2 text-center truncate w-full">Pipas</span>
            </div>
          </div>
          
          <div class="mt-4 text-[10px] font-sans font-medium text-slate-600 text-center uppercase tracking-wide">
            Las unidades se autoajustan de acuerdo a las últimas lecturas enviadas por el personal de obra.
          </div>
        </div>

        <!-- Health diagnostics overview inside Dashboard -->
        <div class="lg:col-span-4 flex flex-col gap-6">
          
          <!-- System Health checklist -->
          <div class="bg-white border border-[#cbd5e1] p-5 shadow-sm">
            <h3 class="font-display text-xs font-black text-[#0054A3] uppercase tracking-wider mb-4">ESTADO DE ENLACES</h3>
            <div class="space-y-3 font-sans text-xs font-medium">
              
              <div class="flex justify-between items-center text-slate-600">
                <span>Servidor Principal Cooitzá</span>
                <span class="text-emerald-600 bg-emerald-50 border border-emerald-200 px-1.5 font-bold uppercase text-[9px]">ONLINE</span>
              </div>
              <div class="w-full bg-slate-100 h-1.5">
                <div class="bg-[#4CAF50] h-full w-[100%]"></div>
              </div>

              <div class="flex justify-between items-center text-slate-600">
                <span>Criptografía de Sesión SSL</span>
                <span class="text-[#0054A3] font-bold font-mono">256-BIT</span>
              </div>
              <div class="w-full bg-slate-100 h-1.5">
                <div class="bg-[#0054A3] h-full w-[80%]"></div>
              </div>

              <div class="flex justify-between items-center text-slate-600">
                <span>Servicio de Geolocalización</span>
                <span class="text-emerald-600 font-bold font-mono text-[10px]">ACTIVO (99.8%)</span>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
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
}>();
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
