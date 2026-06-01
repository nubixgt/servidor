<template>
  <div class="flex min-h-screen bg-[#f8f9fa] w-full text-slate-800 overflow-x-hidden font-sans select-text">
    
    <!-- SideNavBar Menu Component -->
    <aside class="hidden lg:flex flex-col w-64 bg-slate-100 border-r border-[#cbd5e1] h-screen sticky top-0 p-4 select-none">
      
      <!-- Brand Header -->
      <div class="mb-8">
        <div class="flex items-center gap-2">
          <div class="relative w-8 h-8 flex-shrink-0">
            <div class="absolute inset-0 bg-[#FFD200] rounded-full"></div>
            <div class="absolute inset-0 flex items-center justify-center font-display text-base font-black text-[#0054A3] italic">C</div>
          </div>
          <h2 class="font-display font-black text-base text-[#0054A3] tracking-tight uppercase">
            COOITZÁ R.L.
          </h2>
        </div>
        <div class="flex items-center gap-1.5 mt-2">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          <p class="font-display text-[10px] font-bold text-[#004586] uppercase tracking-wider">Estación 04-B Terminal</p>
        </div>
      </div>

      <!-- Sidebar Navigation Options -->
      <nav class="flex-1 space-y-1">
        <button 
          type="button"
          @click="activeTab = 'dashboard'"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-150 font-display text-xs font-bold uppercase tracking-wider"
          :class="activeTab === 'dashboard' ? 'bg-[#0054A3] text-white shadow-sm' : 'text-[#004586] hover:bg-[#cbd5e1]/30'"
        >
          <Activity :size="18" />
          <span>Dashboard</span>
        </button>

        <button 
          type="button"
          @click="activeTab = 'pilotos'"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-150 font-display text-xs font-bold uppercase tracking-wider"
          :class="activeTab === 'pilotos' ? 'bg-[#0054A3] text-white shadow-sm' : 'text-[#004586] hover:bg-[#cbd5e1]/30'"
        >
          <Users :size="18" />
          <span>Pilotos</span>
        </button>

        <button 
          type="button"
          @click="activeTab = 'vehiculos'"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-150 font-display text-xs font-bold uppercase tracking-wider"
          :class="activeTab === 'vehiculos' ? 'bg-[#0054A3] text-white shadow-sm' : 'text-[#004586] hover:bg-[#cbd5e1]/30'"
        >
          <Truck :size="18" />
          <span>Vehículos</span>
        </button>

        <button 
          type="button"
          @click="activeTab = 'maquinaria'"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-150 font-display text-xs font-bold uppercase tracking-wider"
          :class="activeTab === 'maquinaria' ? 'bg-[#0054A3] text-white shadow-sm' : 'text-[#004586] hover:bg-[#cbd5e1]/30'"
        >
          <Tractor :size="18" />
          <span>Maquinaria</span>
        </button>

        <button 
          type="button"
          @click="activeTab = 'usuarios'"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-150 font-display text-xs font-bold uppercase tracking-wider"
          :class="activeTab === 'usuarios' ? 'bg-[#0054A3] text-white shadow-sm' : 'text-[#004586] hover:bg-[#cbd5e1]/30'"
        >
          <Shield :size="18" />
          <span>Usuarios</span>
        </button>
      </nav>

      <!-- Emergency Halt & user controls -->
      <div class="mt-auto space-y-2 pt-4 border-t border-[#cbd5e1]">
        <button 
          type="button"
          @click="handleLogout"
          class="w-full flex items-center gap-3 px-3 py-2 text-red-600 hover:bg-red-50 transition-colors text-xs font-sans font-bold cursor-pointer"
        >
          <LogOut :size="16" />
          <span>Cerrar Sesión</span>
        </button>
      </div>
    </aside>

    <!-- Main Container viewport -->
    <main class="flex-grow flex flex-col min-w-0 max-w-full overflow-hidden min-h-screen">
      
      <!-- TopNavBar Header -->
      <header class="w-full top-0 sticky z-10 bg-white border-b border-[#cbd5e1]">
        <div class="flex justify-between items-center px-6 py-3 max-w-[1280px] mx-auto w-full">
          <div class="flex items-center gap-3">
            <div class="lg:hidden relative w-7 h-7 flex-shrink-0">
              <div class="absolute inset-0 bg-[#FFD200] rounded-full"></div>
              <div class="absolute inset-0 flex items-center justify-center font-display text-xs font-black text-[#0054A3] italic">C</div>
            </div>
            <h1 class="font-display text-sm md:text-base font-black text-[#0054A3] uppercase tracking-tight">
              Cooitzá Control Panel
            </h1>
          </div>

          <div class="hidden md:flex text-xs font-bold text-slate-400 select-none uppercase tracking-wide">
            Estación Enlazada Satelital Cooitzá
          </div>

          <div class="flex items-center gap-4">
            <button 
              type="button"
              @click="showNotificationAlert = true"
              class="relative p-1.5 text-slate-500 hover:text-[#0054A3] transition-colors cursor-pointer"
              title="Notificaciones"
            >
              <Bell :size="18" />
              <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full animate-ping"></span>
            </button>

            <div class="flex items-center gap-2 border-l pl-3 border-[#cbd5e1]">
              <div class="hidden sm:flex flex-col text-right">
                <span class="font-sans text-xs font-bold text-slate-800">Admin Cooitzá</span>
                <span class="font-mono text-[9px] text-[#0054A3] uppercase">Consola Principal</span>
              </div>
              <div class="w-8 h-8 rounded-full overflow-hidden border border-[#cbd5e1]">
                <img 
                  alt="Cooitzá Admin Profile" 
                  src="https://lh3.googleusercontent.com/aida-public/AB6AXuDuk5T1SnyB7CuXEU0F1Im314E1dy2F8NtMdIe7NTmKwuGq1z8uu1Rppj-NLDj9dErQZ3ODx2Dd-QTcW7UaxYM1Hm2JSagasIZaUwwD4OWDuM6bcFh4QWpb9Z2MyUhVV9i4s3YG9upSMiyC2_SkB2BPfehnmYBXXLr0DUc5JuNHA7doSwnd3uD9NYLS14Qnsw7E60fuazvxbd8ARAshO9IAkzzxUiC8vL584g7LEM35ciqvEu51n3ePH75b1GINyz5PS2l5ZDqkSoaL" 
                  class="w-full h-full object-cover"
                />
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Mobile Navigation Tabs list -->
      <div class="flex lg:hidden bg-slate-100 overflow-x-auto divide-x divide-slate-200 border-b border-[#cbd5e1] w-full">
        <button 
          v-for="mTab in mobileTabs"
          :key="mTab.id"
          type="button"
          @click="activeTab = mTab.id"
          class="flex-shrink-0 px-4 py-3 font-display text-[11px] font-bold uppercase tracking-wider transition-colors"
          :class="activeTab === mTab.id ? 'bg-[#0054A3] text-white' : 'text-[#004586] hover:bg-slate-200'"
        >
          {{ mTab.label }}
        </button>
      </div>

      <!-- Background Export status notification -->
      <transition name="expand">
        <div v-if="isExporting" class="bg-[#0054A3] text-white px-6 py-2.5 font-mono text-xs flex justify-between items-center">
          <span>{{ exportMessage }}</span>
          <div class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></div>
        </div>
      </transition>

      <!-- View Canvas Routing -->
      <section class="p-4 md:p-8 flex-grow overflow-y-auto max-w-[1280px] w-full mx-auto flex flex-col gap-6">
        <transition name="fade" mode="out-in">
          
            <DashboardOverview 
            v-if="activeTab === 'dashboard'"
            :pilotsCount="pilotsCount"
            :pilotsActiveCount="pilotsActiveCount"
            :pilotsRestingCount="pilotsRestingCount"
            :vehiclesCount="vehiclesCount"
            :vehiclesActiveCount="vehiclesActiveCount"
            :vehiclesMaintenanceCount="vehiclesMaintenanceCount"
            :machineryCount="machineryCount"
            :maquinas="maquinasList"
            :registros="registrosList"
            @refresh-registros="fetchDashboardData"
          />

          <PilotosModule 
            v-else-if="activeTab === 'pilotos'"
            @pilotsChange="handlePilotsChange"
          />

          <VehiculosModule 
            v-else-if="activeTab === 'vehiculos'"
            @vehiclesChange="handleVehiclesChange"
          />

          <MaquinariaModule 
            v-else-if="activeTab === 'maquinaria'"
            @machineryChange="setMachineryCount"
          />

          <UsuariosModule 
            v-else-if="activeTab === 'usuarios'"
            @usersListChange="setUsersCount"
          />

        </transition>
      </section>

      <!-- Corporate Footer -->
      <footer class="mt-auto bg-white border-t border-[#cbd5e1] select-none">
        <div class="flex flex-col md:flex-row justify-between items-center px-6 py-4 max-w-[1280px] w-full mx-auto gap-4">
          <p class="font-display text-[10px] font-bold text-slate-500 uppercase tracking-widest">
            © 2024 Cooitzá R.L. Central de Bitácoras e Integración de Flotas.
          </p>
          <div class="flex gap-6 font-display text-[10px] uppercase font-bold text-[#0054A3]">
            <a href="#" class="hover:underline">Seguridad Informática</a>
            <a href="#" class="hover:underline">Políticas de Uso</a>
            <a href="#" class="hover:underline flex items-center gap-1">
              <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-ping"></span>
              <span>Sistemas Estables</span>
            </a>
          </div>
        </div>
      </footer>

    </main>



    <!-- REAL-TIME NOTIFICATIONS POPUP PREVIEW -->
    <transition name="slide-up">
      <div v-if="showNotificationAlert" class="fixed bottom-6 right-6 bg-slate-900 text-white border border-[#FFD200] p-5 shadow-2xl z-40 max-w-sm">
        <div class="flex justify-between items-start mb-3">
          <span class="font-display text-[10px] uppercase text-[#FFD200] font-black tracking-widest font-bold">
            Notificaciones del Enlace GPS
          </span>
          <button 
            type="button"
            @click="showNotificationAlert = false"
            class="text-white/60 hover:text-white cursor-pointer"
          >
            <X :size="14" />
          </button>
        </div>
        
        <div class="space-y-3 text-[11px] font-mono select-text">
          <div class="border-b border-white/10 pb-2">
            <span class="text-[#FFD200]">● Alerta:</span> Unidad VEH-104 en cantera Chimaltenango transmitió Horómetro Inicial: 4235.8 hrs correctamente.
          </div>
          <div class="border-b border-white/10 pb-2">
            <span class="text-[#FFD200]">● Seguridad:</span> Encriptación activada para reportes TLS generados hoy.
          </div>
          <div>
            <span class="text-slate-400">Canal óptimo de asistencia Cooitzá R.L. Activo.</span>
          </div>
        </div>
      </div>
    </transition>

    <!-- DETAILED DIAL PHOTO MODAL VIEW -->
    <transition name="fade">
      <div 
        v-if="selectedPhotoInModal"
        class="fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-6 cursor-zoom-out"
        @click="selectedPhotoInModal = null"
      >
        <transition name="scale" appear>
          <div 
            class="bg-slate-900 p-4 border border-white/10 max-w-2xl w-full relative"
            @click.stop
          >
            <div class="flex justify-between items-center text-white/50 text-[10px] font-mono uppercase mb-3">
              <span>LECTURA DE INSTRUMENTO COOITZÁ</span>
              <button 
                type="button"
                @click="selectedPhotoInModal = null"
                class="hover:text-white uppercase font-bold cursor-pointer"
              >
                CERRAR [X]
              </button>
            </div>

            <img 
              :src="selectedPhotoInModal" 
              alt="Instrument Capture" 
              class="w-full h-auto object-contain bg-black max-h-[80vh]" 
            />
            
            <div class="text-center text-[#FFD200] font-mono text-xs mt-3">
              Firma Criptográfica SSL validada por el satélite Cooitzá R.L.
            </div>
          </div>
        </transition>
      </div>
    </transition>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { 
  Tractor, Truck, Activity, Users, Shield, 
  AlertTriangle, LogOut, Bell, X 
} from "lucide-vue-next";

import DashboardOverview from "./DashboardOverview.vue";
import PilotosModule from "./PilotosModule.vue";
import VehiculosModule from "./VehiculosModule.vue";
import MaquinariaModule from "./MaquinariaModule.vue";
import UsuariosModule from "./UsuariosModule.vue";

import { useRouter } from 'vue-router';

type AdminTab = "dashboard" | "pilotos" | "vehiculos" | "maquinaria" | "usuarios";

const router = useRouter();

const activeTab = ref<AdminTab>("dashboard");
const pilotsCount = ref(0);
const pilotsActiveCount = ref(0);
const pilotsRestingCount = ref(0);

const vehiclesCount = ref(0);
const vehiclesActiveCount = ref(0);
const vehiclesMaintenanceCount = ref(0);

const machineryCount = ref(0);
const maquinasList = ref<any[]>([]);
const registrosList = ref<any[]>([]);
const usersCount = ref(0);

const showNotificationAlert = ref(false);
const selectedPhotoInModal = ref<string | null>(null);

const isExporting = ref(false);
const exportMessage = ref("");

const mobileTabs = [
  { id: "dashboard", label: "Dashboard" },
  { id: "pilotos", label: "Pilotos" },
  { id: "vehiculos", label: "Flota" },
  { id: "maquinaria", label: "Equipos" },
  { id: "usuarios", label: "Usuarios" }
] as const;

const fetchDashboardData = async () => {
  try {
    // Fetch Pilotos
    const pRes = await fetch('/maquinaria-cooitza/Backend/api/v1/pilotos');
    const pData = await pRes.json();
    if (pData.status === 'success') {
      const pilotos = pData.data;
      pilotsCount.value = pilotos.length;
      pilotsActiveCount.value = pilotos.filter((p: any) => p.status === 'activo').length;
      pilotsRestingCount.value = pilotos.filter((p: any) => p.status === 'inactivo').length;
    }

    // Fetch Vehiculos
    const vRes = await fetch('/maquinaria-cooitza/Backend/api/v1/vehiculos');
    const vData = await vRes.json();
    if (vData.status === 'success') {
      const vehiculos = vData.data;
      vehiclesCount.value = vehiculos.length;
      vehiclesActiveCount.value = vehiculos.filter((v: any) => v.status === 'activo').length;
      vehiclesMaintenanceCount.value = vehiculos.filter((v: any) => v.status === 'mantenimiento').length;
    }

    // Fetch Maquinaria
    const mRes = await fetch('/maquinaria-cooitza/Backend/api/v1/maquinas');
    const mData = await mRes.json();
    if (mData.status === 'success') {
      machineryCount.value = mData.data.length;
      maquinasList.value = mData.data;
    }

    // Fetch Registros
    const rRes = await fetch('/maquinaria-cooitza/Backend/api/v1/maquinaria/registros');
    const rData = await rRes.json();
    if (rData.status === 'success') {
      registrosList.value = rData.data;
    }

    // Fetch Usuarios
    const uRes = await fetch('/maquinaria-cooitza/Backend/api/v1/usuarios');
    const uData = await uRes.json();
    if (uData.status === 'success') {
      usersCount.value = uData.data.length;
    }
  } catch (error) {
    console.error("Error fetching dashboard data:", error);
  }
};

const handleLogout = () => {
  router.push('/login');
};

onMounted(fetchDashboardData);

watch(activeTab, (newTab) => {
  if (newTab === 'dashboard') {
    fetchDashboardData();
  }
});

const handlePilotsChange = (count: number, active: number, resting: number) => {
  pilotsCount.value = count;
  pilotsActiveCount.value = active;
  pilotsRestingCount.value = resting;
};

const handleVehiclesChange = (count: number, active: number, maint: number) => {
  vehiclesCount.value = count;
  vehiclesActiveCount.value = active;
  vehiclesMaintenanceCount.value = maint;
};

const setMachineryCount = (count: number) => {
  machineryCount.value = count;
};

const setUsersCount = (count: number) => {
  usersCount.value = count;
};

const handleTriggerExport = (format: "Excel" | "PDF", filteredCount: number) => {
  isExporting.value = true;
  exportMessage.value = `Generando informe de telemetría Cooitzá en formato ${format}...`;
  setTimeout(() => {
    exportMessage.value = `¡Archivo ${format} exportado correctamente con ${filteredCount} registros!`;
    setTimeout(() => {
      isExporting.value = false;
      exportMessage.value = "";
    }, 2000);
  }, 1200);
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: opacity 0.4s ease, transform 0.4s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

.scale-enter-active,
.scale-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.scale-enter-from,
.scale-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

.expand-enter-active,
.expand-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}
.expand-enter-from,
.expand-leave-to {
  opacity: 0;
  max-height: 0;
}
</style>
