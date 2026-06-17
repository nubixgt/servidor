<template>
  <aside class="w-[280px] h-screen fixed left-0 top-0 glass-sidebar flex-col py-8 z-50 hidden md:flex">
    <div class="px-10 mb-12">
      <h1 class="text-xl font-bold bg-gradient-to-r from-white to-white/60 bg-clip-text text-transparent tracking-tight">Concretos del Oriente</h1>
      
      <div v-if="role" class="mt-6 px-4 py-1.5 bg-primary/10 border border-primary/20 rounded-xl inline-flex items-center gap-2">
        <div class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse shadow-[0_0_8px_#6366f1]"></div>
        <p class="text-[10px] font-black text-primary uppercase tracking-[0.2em] italic">{{ role }}</p>
      </div>
    </div>

    <nav class="flex-grow px-4 overflow-y-auto custom-scrollbar">
      <ul class="space-y-1.5">
        <li v-for="item in filteredItems" :key="item.id" class="relative px-2">
          <router-link
            :to="'/' + item.id"
            custom
            v-slot="{ isActive, navigate }"
          >
            <button
              @click="navigate"
              :class="[
                'w-full flex items-center px-6 py-4 rounded-2xl transition-all duration-300 group',
                isActive
                  ? 'text-white bg-white/10 shadow-[0_0_20px_rgba(255,255,255,0.05)]'
                  : 'text-white/60 hover:text-white hover:bg-white/5'
              ]"
            >
              <component :is="item.icon" :class="['w-5 h-5 mr-4 transition-transform duration-300 group-hover:scale-110', isActive ? 'text-primary' : 'text-white/40']" />
              <span class="text-xs font-black uppercase tracking-widest italic">{{ item.label }}</span>
              <div
                v-if="isActive"
                class="absolute right-6 w-1.5 h-1.5 rounded-full bg-primary shadow-[0_0_10px_#6366f1]"
              ></div>
            </button>
          </router-link>
        </li>
      </ul>
    </nav>

    <div class="pt-4 px-6 space-y-1 border-t border-white/5 mt-auto">

      <button 
        @click="handleLogout"
        class="w-full flex items-center px-6 py-3 rounded-xl text-white/60 hover:text-tertiary transition-all text-sm font-medium"
      >
        <ArrowRightOnRectangleIcon class="w-5 h-5 mr-4" />
        Cerrar Sesión
      </button>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import {
  Squares2X2Icon, UsersIcon, WrenchScrewdriverIcon, BriefcaseIcon,
  BanknotesIcon, ArrowRightOnRectangleIcon, PlusIcon,
  CubeIcon, BuildingOfficeIcon, ShoppingBagIcon, ShieldCheckIcon,
  BuildingLibraryIcon, ClipboardDocumentListIcon, CalculatorIcon,
  CreditCardIcon, FolderOpenIcon, BellAlertIcon, CurrencyDollarIcon, TruckIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline';

const router = useRouter();
const authStore = useAuthStore();

const role = computed(() => authStore.userRole);

const allNavItemsArr = [
  { id: "dashboard", label: "Panel Principal", icon: Squares2X2Icon, roles: ["admin", "supervisor", "tecnico"] },
  { id: "personnel", label: "Personal", icon: UsersIcon, roles: ["admin"] },
  { id: "vehicles", label: "Vehículos", icon: TruckIcon, roles: ["admin"] },
  { id: "machinery", label: "Maquinaria", icon: WrenchScrewdriverIcon, roles: ["admin", "supervisor"] },
  { id: "tech-machinery", label: "Estado Maquinaria", icon: WrenchScrewdriverIcon, roles: ["tecnico"] },
  { id: "projects", label: "Proyectos", icon: BriefcaseIcon, roles: ["admin", "supervisor"] },
  { id: "clients", label: "Clientes", icon: UsersIcon, roles: ["admin"] },
  { id: "tech-projects", label: "Mis Proyectos", icon: BriefcaseIcon, roles: ["tecnico"] },
  { id: "inventory", label: "Inventario", icon: CubeIcon, roles: ["admin", "supervisor", "tecnico"] },
  { id: "suppliers", label: "Proveedores", icon: BuildingOfficeIcon, roles: ["admin"] },

  { id: "project-incomes", label: "Ingresos por Proyectos", icon: BanknotesIcon, roles: ["admin"] },
  { id: "finance", label: "Ingresos y Egresos", icon: BanknotesIcon, roles: ["admin"] },
  { id: "recurrents", label: "Recurrentes", icon: ArrowPathIcon, roles: ["admin"] },
  { id: "users", label: "Usuarios", icon: ShieldCheckIcon, roles: ["admin"] },
  { id: "bank-conciliation", label: "Bancos", icon: BuildingLibraryIcon, roles: ["admin"] },
  { id: "bitacora-mantenimiento", label: "Bitácoras y Mantenimiento", icon: ClipboardDocumentListIcon, roles: ["admin"] },
  { id: "budgets-estimations", label: "Presupuestos y Estimaciones", icon: CalculatorIcon, roles: ["admin"] },
  { id: "credits-accounts-payable", label: "Créditos y Cuentas por Pagar", icon: CreditCardIcon, roles: ["admin"] },
  { id: "digital-documents", label: "Documentos Digitales", icon: FolderOpenIcon, roles: ["admin"] },
  { id: "notifications-alerts", label: "Notificaciones y Alertas", icon: BellAlertIcon, roles: ["admin"] },
  { id: "payroll-expenses", label: "Planilla y Gastos", icon: CurrencyDollarIcon, roles: ["admin"] },
];

const filteredItems = computed(() => {
  if (role.value === 'admin') {
    // Admin ve todo excepto las vistas exclusivas de técnico si no están configuradas para admin
    return allNavItemsArr.filter(item => item.roles.includes('admin'));
  }
  
  // Para supervisor y tecnico, filtramos por los permisos que tienen asignados
  const permisos = authStore.userPermisos || [];
  return allNavItemsArr.filter(item => permisos.includes(item.id));
});

const handleLogout = () => {
  authStore.logout();
  router.push('/login');
};
</script>
