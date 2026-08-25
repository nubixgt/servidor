<script setup>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { 
  Wrench, 
  Menu, 
  X, 
  Search, 
  PhoneCall, 
  ShieldCheck, 
  Clock 
} from 'lucide-vue-next';

const emit = defineEmits(['open-tracker', 'open-quote-modal']);

const isMobileMenuOpen = ref(false);
const router = useRouter();
const route = useRoute();

const navItems = [
  { id: 'inicio', label: 'Inicio', path: '/' },
  { id: 'servicios', label: 'Servicios', path: '/servicios' },
  { id: 'trabajos', label: 'Trabajos', path: '/trabajos' },
  { id: 'contacto', label: 'Contacto', path: '/contacto' },
];

const navigateTo = (path) => {
  router.push(path);
  window.scrollTo({ top: 0, behavior: 'smooth' });
  isMobileMenuOpen.value = false;
};
</script>

<template>
  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-100 shadow-xs">
    <!-- Top micro banner for quick info / trust -->
    <div class="bg-slate-900 text-slate-300 text-xs py-1.5 px-4 hidden md:block">
      <div class="max-w-7xl mx-auto flex justify-between items-center">
        <div class="flex items-center space-x-6">
          <span class="flex items-center gap-1.5">
            <Clock class="w-3.5 h-3.5 text-blue-400" />
            Lun a Sáb: 9:00 a 19:00 hs
          </span>
          <span class="flex items-center gap-1.5">
            <ShieldCheck class="w-3.5 h-3.5 text-emerald-400" />
            Garantía escrita de hasta 6 meses en todas las reparaciones
          </span>
        </div>
        <div class="flex items-center space-x-4 text-slate-300">
          <button 
            @click="$emit('open-tracker')"
            class="hover:text-white flex items-center gap-1 text-blue-400 hover:underline transition-colors cursor-pointer"
          >
            <Search class="w-3.5 h-3.5" />
            Rastrear mi Equipo (Ticket)
          </button>
          <span class="text-slate-600">|</span>
          <a href="tel:+12345678900" class="hover:text-white flex items-center gap-1 transition-colors">
            <PhoneCall class="w-3.5 h-3.5 text-blue-400" />
            +1 234 567 8900
          </a>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-20">
        <!-- Brand Logo -->
        <div 
          @click="navigateTo('/')"
          class="flex items-center gap-2.5 cursor-pointer group select-none"
          id="navbar-brand-logo"
        >
          <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-all duration-300 shadow-xs border border-blue-100">
            <div class="relative">
              <Wrench class="w-5 h-5 -rotate-45" />
            </div>
          </div>
          <div class="flex flex-col">
            <span class="text-xl sm:text-2xl font-bold tracking-tight text-blue-600 flex items-center">
              CoreFix <span class="text-slate-900 font-bold ml-1.5">Services</span>
            </span>
          </div>
        </div>

        <!-- Desktop Navigation Links -->
        <nav class="hidden md:flex items-center space-x-1 lg:space-x-2">
          <router-link
            v-for="item in navItems"
            :key="item.id"
            :to="item.path"
            :id="`nav-item-${item.id}`"
            class="relative px-4 py-2 text-sm font-medium transition-colors cursor-pointer"
            :class="[
              route.path === item.path 
                ? 'text-blue-600' 
                : 'text-slate-600 hover:text-slate-900'
            ]"
          >
            {{ item.label }}
            <span v-if="route.path === item.path" class="absolute bottom-0 left-4 right-4 h-0.5 bg-blue-600 rounded-full animate-in fade-in duration-200" />
          </router-link>
        </nav>

        <!-- Right Action Buttons -->
        <div class="hidden md:flex items-center space-x-3">
          <button
            @click="$emit('open-tracker')"
            id="btn-nav-track-order"
            class="px-3.5 py-2 text-sm font-medium text-slate-700 hover:text-blue-600 hover:bg-slate-50 rounded-lg border border-slate-200 transition-all flex items-center gap-1.5 cursor-pointer"
            title="Consultar estado de reparación con tu código de ticket"
          >
            <Search class="w-4 h-4 text-slate-500" />
            <span>Estado de Orden</span>
          </button>

          <button
            @click="navigateTo('/contacto')"
            id="btn-nav-contact"
            class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2.5 rounded-lg text-sm transition-all duration-200 shadow-sm hover:shadow-md cursor-pointer active:scale-98"
          >
            Contáctanos
          </button>
        </div>

        <!-- Mobile Menu Toggle -->
        <div class="flex items-center space-x-2 md:hidden">
          <button
            @click="$emit('open-tracker')"
            class="p-2 text-slate-600 hover:text-blue-600 rounded-lg border border-slate-200"
            aria-label="Buscar ticket"
          >
            <Search class="w-5 h-5" />
          </button>
          <button
            @click="isMobileMenuOpen = !isMobileMenuOpen"
            class="p-2 text-slate-700 hover:text-blue-600 hover:bg-slate-100 rounded-lg transition-colors cursor-pointer"
            aria-label="Abrir menú"
            id="btn-mobile-menu-toggle"
          >
            <X v-if="isMobileMenuOpen" class="w-6 h-6" />
            <Menu v-else class="w-6 h-6" />
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile Drawer Menu -->
    <div v-if="isMobileMenuOpen" class="md:hidden border-t border-slate-100 bg-white px-4 pt-3 pb-6 space-y-3 shadow-lg">
      <div class="space-y-1">
        <button
          v-for="item in navItems"
          :key="item.id"
          @click="navigateTo(item.path)"
          class="w-full text-left px-3 py-2.5 rounded-lg text-base font-medium transition-colors"
          :class="[
            route.path === item.path
              ? 'bg-blue-50 text-blue-600 font-semibold'
              : 'text-slate-700 hover:bg-slate-50'
          ]"
        >
          {{ item.label }}
        </button>
      </div>

      <div class="pt-2 space-y-2 border-t border-slate-100">
        <button
          @click="() => { $emit('open-tracker'); isMobileMenuOpen = false; }"
          class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-slate-200 text-slate-700 font-medium text-sm hover:bg-slate-50"
        >
          <Search class="w-4 h-4 text-slate-500" />
          Consultar Estado de Orden
        </button>
        <button
          @click="() => { $emit('open-quote-modal'); isMobileMenuOpen = false; }"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-lg text-sm transition-colors text-center shadow-xs"
        >
          Solicitar Presupuesto
        </button>
      </div>
    </div>
  </header>
</template>
