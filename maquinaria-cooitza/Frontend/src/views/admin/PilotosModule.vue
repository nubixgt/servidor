<template>
  <transition name="fade-up" appear>
    <div class="flex flex-col gap-6">
      <!-- Tab identity Header -->
      <div class="mb-2 border-l-4 border-[#0054A3] pl-3">
        <span class="font-display text-[10px] font-black text-[#0054A3] tracking-widest uppercase">RECURSOS HUMANOS</span>
        <h2 class="font-display text-3xl font-black text-slate-800 mt-0.5">Administración de Pilotos</h2>
        <p class="text-xs text-slate-600 font-medium mt-1">Gestión de personal operativo y asignación de maquinaria pesada.</p>
      </div>

      <!-- Bento Grid Layout layout with registration forms and registered list side-by-side -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Registration form block -->
        <section class="lg:col-span-5 bg-white border border-[#cbd5e1] p-6 shadow-sm">
          <div class="flex items-center gap-2 mb-4 border-b pb-2">
            <UserPlus class="text-[#0054A3] w-5 h-5" />
            <h3 class="font-display text-xs font-bold uppercase text-slate-800">
              {{ editPilotId ? "Modificar Datos de Piloto" : "Registro de Nuevo Piloto" }}
            </h3>
          </div>

          <form class="space-y-4" @submit.prevent="handleSavePilot">
            <div>
              <label class="text-slate-600 text-[11px] font-bold block mb-1 uppercase tracking-wider">Nombre Completo</label>
              <input 
                type="text"
                v-model="pilotForm.name"
                placeholder="Ej. Ricardo Valdivia"
                class="w-full px-3 py-2 border border-[#cbd5e1] text-xs focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] transition-colors bg-white outline-none text-slate-800"
                required
              />
            </div>

            <div>
              <label class="text-slate-600 text-[11px] font-bold block mb-1 uppercase tracking-wider">Teléfono de Contacto</label>
              <input 
                type="tel"
                v-model="pilotForm.phone"
                placeholder="Ej: +502 5901 2234"
                class="w-full px-3 py-2 border border-[#cbd5e1] text-xs focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] transition-colors bg-white outline-none text-slate-800"
              />
            </div>

            <div>
              <label class="text-slate-600 text-[11px] font-bold block mb-1 uppercase tracking-wider">Maquinarias Autorizadas / Asignadas</label>
              <div class="grid grid-cols-1 gap-1.5 p-3 bg-slate-50 border border-[#cbd5e1] max-h-44 overflow-y-auto">
                <label 
                  v-for="machineItemName in availableMachines" 
                  :key="machineItemName" 
                  class="flex items-center gap-3 cursor-pointer group text-xs text-slate-600 select-none"
                >
                  <input 
                    type="checkbox"
                    :checked="pilotForm.assignedMachines.includes(machineItemName)"
                    @change="toggleMachineAssignment(machineItemName)"
                    class="w-4 h-4 rounded-sm border-[#cbd5e1] text-[#0054A3] focus:ring-0 cursor-pointer"
                  />
                  <span class="group-hover:text-[#0054A3] transition-colors">{{ machineItemName }}</span>
                </label>
              </div>
            </div>

            <div class="pt-2">
              <button 
                type="submit"
                class="w-full bg-[#0054A3] hover:bg-[#004586] text-white font-display text-xs font-bold py-3 uppercase tracking-wider flex items-center justify-center gap-2 cursor-pointer transition-colors"
              >
                <Check :size="14" />
                <span>{{ editPilotId ? "Confirmar Edición" : "Guardar Piloto" }}</span>
              </button>

              <button v-if="editPilotId"
                type="button"
                @click="cancelEdit"
                class="w-full text-center text-xs text-red-600 hover:underline mt-2 cursor-pointer block"
              >
                Cancelar Edición
              </button>
            </div>
          </form>
        </section>

        <!-- Registered pilot list -->
        <section class="lg:col-span-7 flex flex-col gap-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-slate-800">
              <Users class="text-[#0054A3] w-5 h-5" />
              <h3 class="font-display text-xs font-bold uppercase">Pilotos Registrados</h3>
            </div>
            <span class="bg-[#0054A3]/10 text-[#0054A3] border border-[#0054A3]/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest">
              Total Cooitzá: {{ pilots.length }}
            </span>
          </div>

          <div class="space-y-2">
            <div v-if="pilots.length === 0" class="bg-white border border-[#cbd5e1] p-8 text-center text-slate-400 italic">
              Ningún piloto registrado actualmente
            </div>
            
            <div 
              v-else 
              v-for="p in pilots" 
              :key="p.id" 
              class="bg-white border border-[#cbd5e1] p-4 flex items-center gap-4 hover:border-[#0054A3] transition-colors shadow-sm"
            >
              <!-- Avatar -->
              <div class="w-10 h-10 bg-[#0054A3]/5 text-[#0054A3] rounded-full flex items-center justify-center font-display font-bold">
                {{ p.name.substring(0, 2).toUpperCase() }}
              </div>

              <!-- Middle details -->
              <div class="flex-grow min-w-0">
                <h4 class="font-display font-bold text-sm text-[#0054A3] mb-1">{{ p.name }}</h4>
                <div class="flex flex-wrap gap-1">
                  <template v-if="p.assignedMachines && p.assignedMachines.length > 0">
                    <span 
                      v-for="mItem in p.assignedMachines" 
                      :key="mItem" 
                      class="text-[9px] bg-slate-100 text-slate-700 px-1.5 py-0.5 border border-slate-200"
                    >
                      {{ mItem }}
                    </span>
                  </template>
                  <span v-else class="text-[10px] text-slate-400 italic">Ninguna asignación</span>
                </div>
              </div>

              <!-- Status toggle actions -->
              <div class="text-right hidden sm:block shrink-0">
                <p class="font-mono text-xs text-slate-600 font-semibold">{{ p.phone || "Sin Teléfono" }}</p>
                <button 
                  type="button"
                  @click="toggleStatus(p.id)"
                  class="inline-block mt-1 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-sm hover:scale-95 transition-all text-left cursor-pointer"
                  :class="p.status === 'En Turno' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                >
                  ● {{ p.status }}
                </button>
              </div>

              <!-- Operational CRUD tools -->
              <div class="flex flex-col gap-1 shrink-0">
                <button 
                  type="button"
                  @click="handleEditPilot(p)"
                  class="text-[#0054A3] hover:bg-slate-100 p-1.5 rounded transition-colors cursor-pointer"
                  title="Editar"
                >
                  <Edit :size="14" />
                </button>
                <button 
                  type="button"
                  @click="handleDeletePilot(p.id)"
                  class="text-red-600 hover:bg-red-50 p-1.5 rounded transition-colors cursor-pointer"
                  title="Eliminar piloto"
                >
                  <Trash2 :size="14" />
                </button>
              </div>
            </div>
          </div>
        </section>

      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Users, UserPlus, Check, Edit, Trash2 } from "lucide-vue-next";

interface Pilot {
  id: string;
  name: string;
  phone: string;
  assignedMachines: string[];
  status: "En Turno" | "Descanso";
}

const emit = defineEmits<{
  (e: 'pilotsChange', count: number, activeCount: number, restingCount: number): void
}>();

const pilots = ref<Pilot[]>([]);
const editPilotId = ref<string | null>(null);

const pilotForm = ref<{
  name: string;
  phone: string;
  assignedMachines: string[];
  status: "En Turno" | "Descanso";
}>({
  name: "",
  phone: "",
  assignedMachines: [],
  status: "En Turno"
});

const availableMachines = [
  "Excavadora CAT 320",
  "Bulldozer D6K2",
  "Grúa RT765E-2",
  "Cargador Frontal 950K",
  "Motoniveladora 140K",
  "Retroexcavadora John Deere",
  "Camión Cisterna Hino"
];

const triggerSync = (updated: Pilot[]) => {
  const active = updated.filter(p => p.status === "En Turno").length;
  const resting = updated.filter(p => p.status === "Descanso").length;
  emit('pilotsChange', updated.length, active, resting);
};

const persistPilots = (updated: Pilot[]) => {
  pilots.value = updated;
  localStorage.setItem("cooitza_pilotos", JSON.stringify(updated));
  triggerSync(updated);
};

onMounted(() => {
  const saved = localStorage.getItem("cooitza_pilotos");
  if (saved) {
    const data = JSON.parse(saved);
    pilots.value = data;
    triggerSync(data);
  } else {
    const defaultPilots: Pilot[] = [
      { id: "p1", name: "Ricardo Valdivia", phone: "+502 5901 2234", assignedMachines: ["Excavadora CAT 320", "Grúa RT765E-2"], status: "En Turno" },
      { id: "p2", name: "Elena Soto", phone: "+502 4120 8899", assignedMachines: ["Bulldozer D6K2"], status: "Descanso" },
      { id: "p3", name: "Marcos Peña", phone: "+502 3341 7766", assignedMachines: ["Motoniveladora 140K", "Cargador Frontal 950K"], status: "En Turno" },
    ];
    pilots.value = defaultPilots;
    localStorage.setItem("cooitza_pilotos", JSON.stringify(defaultPilots));
    triggerSync(defaultPilots);
  }
});

const handleSavePilot = () => {
  if (!pilotForm.value.name.trim()) return;

  if (editPilotId.value) {
    const updated = pilots.value.map(p => 
      p.id === editPilotId.value ? { ...p, ...pilotForm.value } : p
    );
    persistPilots(updated);
    editPilotId.value = null;
  } else {
    const newPilot: Pilot = {
      id: "p_" + Date.now(),
      ...pilotForm.value
    };
    persistPilots([newPilot, ...pilots.value]);
  }
  pilotForm.value = { name: "", phone: "", assignedMachines: [], status: "En Turno" };
};

const handleEditPilot = (p: Pilot) => {
  editPilotId.value = p.id;
  pilotForm.value = { name: p.name, phone: p.phone, assignedMachines: [...p.assignedMachines], status: p.status };
};

const cancelEdit = () => {
  editPilotId.value = null;
  pilotForm.value = { name: "", phone: "", assignedMachines: [], status: "En Turno" };
};

const handleDeletePilot = (id: string) => {
  if (window.confirm("¿Está seguro de remover a este piloto asignado?")) {
    const updated = pilots.value.filter(p => p.id !== id);
    persistPilots(updated);
  }
};

const toggleMachineAssignment = (machineName: string) => {
  const isAlreadyAssigned = pilotForm.value.assignedMachines.includes(machineName);
  if (isAlreadyAssigned) {
    pilotForm.value.assignedMachines = pilotForm.value.assignedMachines.filter(m => m !== machineName);
  } else {
    pilotForm.value.assignedMachines.push(machineName);
  }
};

const toggleStatus = (id: string) => {
  const updated = pilots.value.map(item => 
    item.id === id ? { ...item, status: (item.status === "En Turno" ? "Descanso" : "En Turno") as "En Turno" | "Descanso" } : item
  );
  persistPilots(updated);
};
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
