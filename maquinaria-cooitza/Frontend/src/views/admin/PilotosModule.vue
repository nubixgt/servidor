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
                v-model="pilotForm.nombre"
                placeholder="Ej. Ricardo Valdivia"
                class="w-full px-3 py-2 border border-[#cbd5e1] text-xs focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] transition-colors bg-white outline-none text-slate-800"
                required
              />
            </div>

            <div>
              <label class="text-slate-600 text-[11px] font-bold block mb-1 uppercase tracking-wider">Teléfono de Contacto</label>
              <input 
                type="tel"
                v-model="pilotForm.telefono"
                placeholder="Ej: +502 5901 2234"
                class="w-full px-3 py-2 border border-[#cbd5e1] text-xs focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] transition-colors bg-white outline-none text-slate-800"
              />
            </div>

            <div>
              <label class="text-slate-600 text-[11px] font-bold block mb-1 uppercase tracking-wider">Maquinarias Autorizadas / Asignadas</label>
              <div class="grid grid-cols-1 gap-1.5 p-3 bg-slate-50 border border-[#cbd5e1] max-h-44 overflow-y-auto">
                <label 
                  v-for="machine in availableMachines" 
                  :key="machine.id" 
                  class="flex items-center gap-3 cursor-pointer group text-xs text-slate-600 select-none"
                >
                  <input 
                    type="checkbox"
                    :checked="pilotForm.assignedMachines.includes(machine.id)"
                    @change="toggleMachineAssignment(machine.id)"
                    class="w-4 h-4 rounded-sm border-[#cbd5e1] text-[#0054A3] focus:ring-0 cursor-pointer"
                  />
                  <span class="group-hover:text-[#0054A3] transition-colors">{{ machine.marca }} - {{ machine.identificador }}</span>
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
                {{ p.nombre.substring(0, 2).toUpperCase() }}
              </div>

              <!-- Middle details -->
              <div class="flex-grow min-w-0">
                <h4 class="font-display font-bold text-sm text-[#0054A3] mb-1">{{ p.nombre }}</h4>
                <div class="flex flex-wrap gap-1">
                  <template v-if="p.maquinas && p.maquinas.length > 0">
                    <span 
                      v-for="mId in p.maquinas" 
                      :key="mId" 
                      class="text-[9px] bg-slate-100 text-slate-700 px-1.5 py-0.5 border border-slate-200"
                    >
                      {{ getMachineName(mId) }}
                    </span>
                  </template>
                  <span v-else class="text-[10px] text-slate-400 italic">Ninguna asignación</span>
                </div>
              </div>

              <!-- Status toggle actions -->
              <div class="text-right hidden sm:block shrink-0">
                <p class="font-mono text-xs text-slate-600 font-semibold">{{ p.telefono || "Sin Teléfono" }}</p>
                <button 
                  type="button"
                  @click="toggleStatus(p.id, p.status)"
                  class="inline-block mt-1 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-sm hover:scale-95 transition-all text-left cursor-pointer"
                  :class="p.status === 'activo' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
                >
                  ● {{ p.status === "activo" ? "Activo" : "Inactivo" }}
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
import Swal from 'sweetalert2';

interface Pilot {
  id: string;
  nombre: string;
  telefono: string;
  maquinas: string[];
  status: "activo" | "inactivo";
}

const emit = defineEmits<{
  (e: 'pilotsChange', count: number, activeCount: number, restingCount: number): void
}>();

const Toast = Swal.mixin({
  toast: true,
  position: 'bottom-start',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  background: '#191c1d',
  color: '#ffffff',
  iconColor: '#FFD200',
});

const pilots = ref<Pilot[]>([]);
const availableMachines = ref<any[]>([]);
const editPilotId = ref<string | null>(null);

const pilotForm = ref<{
  nombre: string;
  telefono: string;
  assignedMachines: string[];
  status: "activo" | "inactivo";
}>({
  nombre: "",
  telefono: "",
  assignedMachines: [],
  status: "activo"
});

const getMachineName = (id: string) => {
  const m = availableMachines.value.find(x => x.id == id);
  return m ? `${m.marca} - ${m.identificador}` : `Máquina #${id}`;
};

const triggerSync = (updated: Pilot[]) => {
  const active = updated.filter(p => p.status === "activo").length;
  const inact = updated.filter(p => p.status === "inactivo").length;
  emit('pilotsChange', updated.length, active, inact);
};

const loadData = async () => {
  try {
    const [resP, resM] = await Promise.all([
      fetch('/maquinaria-cooitza/Backend/api/v1/pilotos'),
      fetch('/maquinaria-cooitza/Backend/api/v1/maquinas')
    ]);
    if (resP.ok) {
      const json = await resP.json();
      pilots.value = json.data;
      triggerSync(pilots.value);
    }
    if (resM.ok) {
      const json = await resM.json();
      availableMachines.value = json.data;
    }
  } catch (e) {
    console.error(e);
  }
};

onMounted(() => {
  loadData();
});

const handleSavePilot = async () => {
  if (!pilotForm.value.nombre.trim()) return;

  const payload = {
    id: editPilotId.value,
    nombre: pilotForm.value.nombre,
    telefono: pilotForm.value.telefono,
    status: pilotForm.value.status,
    maquinas: pilotForm.value.assignedMachines
  };

  try {
    const url = editPilotId.value ? '/maquinaria-cooitza/Backend/api/v1/pilotos/update' : '/maquinaria-cooitza/Backend/api/v1/pilotos';
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    if (res.ok) {
      await loadData();
      Toast.fire({
        icon: 'success',
        title: editPilotId.value ? 'Piloto actualizado.' : 'Piloto registrado.'
      });
      cancelEdit();
    } else {
      const err = await res.json();
      Swal.fire('Error', err.message || 'Error al guardar', 'error');
    }
  } catch (e) {
    Swal.fire('Error', 'Error de conexión', 'error');
  }
};

const handleEditPilot = (p: Pilot) => {
  editPilotId.value = p.id;
  pilotForm.value = {
    nombre: p.nombre,
    telefono: p.telefono,
    assignedMachines: p.maquinas ? [...p.maquinas] : [],
    status: p.status
  };
};

const cancelEdit = () => {
  editPilotId.value = null;
  pilotForm.value = { nombre: "", telefono: "", assignedMachines: [], status: "activo" };
};

const handleDeletePilot = async (id: string) => {
  const result = await Swal.fire({
    title: '¿Eliminar piloto?',
    text: "El registro de este piloto se eliminará permanentemente.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ba1a1a',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, eliminar'
  });

  if (result.isConfirmed) {
    try {
      const res = await fetch('/maquinaria-cooitza/Backend/api/v1/pilotos/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      if (res.ok) {
        await loadData();
        Toast.fire({ icon: 'success', title: 'Piloto eliminado.' });
      }
    } catch (e) {
      console.error(e);
    }
  }
};

const toggleMachineAssignment = (machineId: string) => {
  const isAlreadyAssigned = pilotForm.value.assignedMachines.includes(machineId);
  if (isAlreadyAssigned) {
    pilotForm.value.assignedMachines = pilotForm.value.assignedMachines.filter(m => m !== machineId);
  } else {
    pilotForm.value.assignedMachines.push(machineId);
  }
};

const toggleStatus = async (id: string, currentStatus: string) => {
  const nextStatus = currentStatus === 'activo' ? 'inactivo' : 'activo';
  const p = pilots.value.find(x => x.id === id);
  if (!p) return;

  const payload = { ...p, status: nextStatus };
  
  try {
    const res = await fetch('/maquinaria-cooitza/Backend/api/v1/pilotos/update', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    if (res.ok) {
      await loadData();
    }
  } catch (e) {
    console.error(e);
  }
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
