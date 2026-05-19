<template>
  <div class="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-10">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h2 class="text-4xl font-bold tracking-tight text-white mb-2">Gestión de Personal</h2>
        <p class="text-white/60">Gestiona tu fuerza laboral, rastrea la asistencia y asigna cuadrillas.</p>
      </div>
      <button class="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center justify-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 hover:-translate-y-0.5 active:translate-y-0 transition-all">
        <PlusIcon class="w-5 h-5" />
        Añadir Personal
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div
        v-for="(stat, i) in stats"
        :key="i"
        class="glass-card p-10 rounded-[32px] flex flex-col justify-between h-52 cursor-pointer group hover:-translate-y-1.5 hover:scale-[1.02] transition-all duration-300"
      >
        <div class="flex items-center justify-between mb-4">
          <div :class="`p-4 rounded-2xl ${stat.bgColor} ${stat.color} border border-white/10 shadow-lg`">
            <component :is="stat.icon" class="w-8 h-8" />
          </div>
          <span :class="`text-[11px] font-bold px-3.5 py-1.5 rounded-full ${stat.color} ${stat.bgColor} border border-white/5 tracking-wider uppercase`">
            {{ stat.change }}
          </span>
        </div>
        <div>
          <p class="text-white/40 text-[11px] font-bold uppercase tracking-[0.2em]">{{ stat.label }}</p>
          <h3 class="text-4xl font-bold text-white mt-2 group-hover:text-primary transition-colors">{{ stat.value }}</h3>
        </div>
      </div>
    </div>

    <!-- Table Section -->
    <div class="glass-card rounded-[40px] overflow-hidden border border-white/10 transition-all duration-300">
      <div class="p-10 flex flex-wrap items-center justify-between gap-6 border-b border-white/5">
        <div class="flex flex-wrap items-center gap-4">
          <div class="glass-input px-6 py-3 rounded-2xl flex items-center gap-3 text-white/60 font-bold text-xs uppercase tracking-widest cursor-pointer">
            <FunnelIcon class="w-4 h-4" />
            Filtros
          </div>
          <select class="bg-white/5 border border-white/10 rounded-2xl px-6 py-3 text-sm font-semibold text-white outline-none focus:ring-2 focus:ring-primary/40 cursor-pointer transition-all">
            <option class="bg-slate-900">Todos los Roles</option>
            <option class="bg-slate-900">Ingeniero Principal</option>
            <option class="bg-slate-900">Mayordomo</option>
            <option class="bg-slate-900">Inspector de Seguridad</option>
          </select>
        </div>
        <button class="flex items-center gap-2 text-primary text-sm font-bold hover:bg-white/5 px-8 py-3 rounded-2xl transition-all border border-white/10">
          <ArrowDownTrayIcon class="w-5 h-5" />
          Exportar CSV
        </button>
      </div>

      <div class="overflow-x-auto px-4">
        <table class="w-full text-left">
          <thead>
            <tr class="text-[11px] font-bold text-white/40 uppercase tracking-[0.2em]">
              <th class="px-8 py-8">Nombre del Empleado</th>
              <th class="px-8 py-8">Rol</th>
              <th class="px-8 py-8">Proyecto Asignado</th>
              <th class="px-8 py-8">Estado</th>
              <th class="px-8 py-8 text-right">Asistencia</th>
              <th class="px-8 py-8"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-for="emp in employees" :key="emp.id" class="hover:bg-white/5 group cursor-pointer transition-colors duration-200">
              <td class="px-8 py-8">
                <div class="flex items-center gap-5">
                  <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center font-bold text-primary group-hover:bg-primary group-hover:text-white transition-all duration-300 border border-white/10 shadow-lg">
                    {{ emp.initials }}
                  </div>
                  <div>
                    <p class="font-bold text-white text-lg">{{ emp.name }}</p>
                    <p class="text-xs text-white/40 font-medium tracking-widest mt-1">ID: {{ emp.id }}</p>
                  </div>
                </div>
              </td>
              <td class="px-8 py-8">
                <span class="text-sm font-semibold text-white/70">{{ emp.role }}</span>
              </td>
              <td class="px-8 py-8">
                <div class="flex items-center gap-3">
                  <div :class="`w-2.5 h-2.5 rounded-full ${emp.id === '44292' ? 'bg-white/20' : emp.id === '44294' ? 'bg-tertiary shadow-[0_0_8px_#f43f5e]' : 'bg-primary shadow-[0_0_8px_#6366f1]'}`"></div>
                  <span class="text-sm font-bold text-white/90">{{ emp.project }}</span>
                </div>
              </td>
              <td class="px-8 py-8">
                <span :class="`px-4 py-2 rounded-full text-[10px] font-extrabold uppercase tracking-widest border transition-all ${
                  emp.status === 'Activo' 
                    ? 'bg-primary/20 text-primary border-primary/20 shadow-[0_0_15px_rgba(99,102,241,0.1)]' 
                    : 'bg-tertiary/20 text-tertiary border-tertiary/20 shadow-[0_0_15px_rgba(244,63,94,0.1)] shadow-inner'
                }`">
                  {{ emp.status }}
                </span>
              </td>
              <td class="px-8 py-8 text-right font-bold text-white text-base">
                {{ emp.attendance }}
              </td>
              <td class="px-8 py-8 text-right">
                <button class="p-3 text-white/20 hover:text-primary transition-all opacity-0 group-hover:opacity-100 bg-white/5 rounded-xl">
                  <EllipsisVerticalIcon class="w-5 h-5" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="px-8 py-8 flex items-center justify-between border-t border-white/5">
        <p class="text-xs font-bold text-white/30 tracking-widest uppercase">Mostrando 1-10 de 1,248 empleados</p>
        <div class="flex items-center gap-3">
          <button class="p-3 rounded-2xl bg-white/5 hover:bg-white/10 transition-all text-white/40 disabled:opacity-10" disabled>
            <ChevronLeftIcon class="w-6 h-6" />
          </button>
          <button
            v-for="page in [1, 2, 3]"
            :key="page"
            :class="`w-12 h-12 rounded-2xl font-bold text-sm transition-all border border-white/5 ${
              page === 1 ? 'glass-button-primary text-white shadow-xl shadow-primary/20' : 'hover:bg-white/10 text-white/50'
            }`"
          >
            {{ page }}
          </button>
          <button class="p-3 rounded-2xl bg-white/5 hover:bg-white/10 transition-all text-white/40">
            <ChevronRightIcon class="w-6 h-6" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { 
  UsersIcon, CheckCircleIcon, CalendarIcon, MagnifyingGlassIcon, PlusIcon, 
  FunnelIcon, ArrowDownTrayIcon, EllipsisVerticalIcon, ChevronLeftIcon, ChevronRightIcon 
} from '@heroicons/vue/24/outline';

const stats = [
  { label: "Total de Empleados", value: "1,248", change: "+4 esta semana", icon: UsersIcon, color: "text-primary", bgColor: "bg-primary/20" },
  { label: "Presentes Hoy", value: "1,176", change: "94.2% Tasa", icon: CheckCircleIcon, color: "text-primary", bgColor: "bg-white/10" },
  { label: "De Licencia", value: "72", change: "12 Pendientes", icon: CalendarIcon, color: "text-tertiary", bgColor: "bg-tertiary/20" },
];

const employees = [
  { name: "Michael Scott", role: "Ingeniero Principal", project: "Torre Skyline", status: "Activo", attendance: "98.5%", id: "44291", initials: "MS" },
  { name: "Jim Beasley", role: "Cataz", project: "Puente Oakridge", status: "De Licencia", attendance: "82.0%", id: "44292", initials: "JB" },
  { name: "Dwight Walker", role: "Obrero (Grado I)", project: "Torre Skyline", status: "Activo", attendance: "94.8%", id: "44293", initials: "DW" },
  { name: "Pam Halpert", role: "Inspector de Seguridad", project: "Muelle Marina", status: "Activo", attendance: "100%", id: "44294", initials: "PH" },
];
</script>
