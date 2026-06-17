<template>
  <div class="pt-20 pb-10 px-4 md:px-10 md:pb-20 max-w-7xl mx-auto space-y-12 text-white">
    <div class="space-y-3">
      <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Mis Asignaciones</h2>
      <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Reportes de campo y seguimiento de obra</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <div 
        v-for="(prj, i) in myProjects" 
        :key="i"
        class="glass-card rounded-[48px] overflow-hidden border border-white/5 shadow-2xl relative transition-all duration-500 hover:-translate-y-3 hover:scale-105 hover:shadow-[0_20px_40px_-15px_rgba(99,102,241,0.5)]" data-aos="zoom-in-up" data-aos-duration="1000"
      >
        <div class="absolute top-0 right-0 p-8">
           <span class="px-4 py-2 bg-primary/20 text-primary border border-primary/20 rounded-full text-[10px] font-black uppercase tracking-widest">{{ prj.status }}</span>
        </div>

        <div class="p-12">
          <div class="flex items-center gap-4 mb-8">
            <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-primary">
              <FolderOpenIcon class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-2xl font-black italic uppercase tracking-tighter leading-none">{{ prj.name }}</h3>
              <p class="text-[10px] font-bold text-white/30 uppercase tracking-widest mt-2">ID: {{ prj.id }} • {{ prj.role }}</p>
            </div>
          </div>

          <div class="flex items-center gap-4 mb-10 text-white/40">
            <MapPinIcon class="w-4 h-4 text-primary" />
            <span class="text-xs font-bold uppercase tracking-tight">{{ prj.location }}</span>
          </div>

          <div class="space-y-4 mb-12">
            <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-[0.2em] text-white/30">
              <span>Progreso de Obra</span>
              <span>{{ prj.progress }}%</span>
            </div>
            <div class="w-full h-2 bg-white/5 rounded-full overflow-hidden">
              <div 
                class="h-full bg-primary shadow-[0_0_15px_#6366f1] transition-all duration-1000"
                :style="{ width: `${prj.progress}%` }"
              ></div>
            </div>
          </div>

          <div class="space-y-4">
            <h4 class="text-[10px] font-black uppercase tracking-widest text-primary italic mb-6">Mis Tareas Actuales</h4>
            <div v-for="(task, ti) in prj.tasks" :key="ti" class="flex items-center justify-between p-5 bg-white/5 rounded-2xl border border-white/5 group hover:border-primary/30 transition-all cursor-pointer">
              <div class="flex items-center gap-4">
                <div :class="`w-8 h-8 rounded-lg flex items-center justify-center ${task.status === 'Done' ? 'bg-primary/20 text-primary' : 'bg-white/5 text-white/20'}`">
                  <CheckCircleIcon v-if="task.status === 'Done'" class="w-4 h-4" />
                  <ClockIcon v-else class="w-4 h-4" />
                </div>
                <span :class="`text-sm font-bold ${task.status === 'Done' ? 'text-white/40 line-through' : 'text-white'}`">{{ task.name }}</span>
              </div>
              <ChevronRightIcon class="w-4 h-4 text-white/20 group-hover:translate-x-1 transition-transform" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4 mt-8">
            <button class="py-4 rounded-2xl bg-white/5 border border-white/10 text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-2 hover:bg-white/10 transition-all">
              <DocumentTextIcon class="w-4 h-4" /> Subir Bitácora
            </button>
            <button class="py-4 rounded-2xl bg-white/5 border border-white/10 text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-2 hover:bg-white/10 transition-all">
              <ChatBubbleBottomCenterTextIcon class="w-4 h-4" /> Chat Site
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { 
  FolderOpenIcon, CheckCircleIcon, ClockIcon, MapPinIcon, 
  DocumentTextIcon, ChevronRightIcon, ChatBubbleBottomCenterTextIcon 
} from '@heroicons/vue/24/outline';

const myProjects = [
  { 
    name: "Skyline Tower - Fase 2", 
    id: "PRJ-001", 
    role: "Técnico Especialista", 
    location: "Zona 10, Ciudad de Guatemala",
    progress: 78,
    status: "Activo",
    tasks: [
      { name: "Verificación de Cimentación", status: "Done" },
      { name: "Instalación de Vigas Nivel 14", status: "Pending" }
    ]
  },
  { 
    name: "Marina Wharf Pavimentación", 
    id: "PRJ-015", 
    role: "Logística Técnica", 
    location: "San José, Escuintla",
    progress: 34,
    status: "Activo",
    tasks: [
      { name: "Drenajes Pluviales", status: "In Progress" }
    ]
  }
];
</script>
