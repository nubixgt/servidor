<template>
  <header className="fixed top-4 left-0 right-0 z-50 px-3 md:px-8 pointer-events-none">
    <div className="max-w-6xl mx-auto pointer-events-auto">
      <nav
        className="glass-nav rounded-full px-3 md:px-4 py-2 flex items-center justify-between shadow-lg border border-white/80"
        aria-label="Navegación principal"
      >
        <!-- Logo Las Orquídeas -->
        <button
          @click="handleNavClick('inicio')"
          className="flex items-center gap-2.5 md:gap-3 pl-1 md:pl-2 text-left group cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-forest rounded-full"
        >
          <div className="w-9 h-9 md:w-10 md:h-10 rounded-full border border-slate-300/80 bg-white shadow-sm flex items-center justify-center p-0.5 transition-transform duration-300 group-hover:scale-105 overflow-hidden">
            <img :src="logoUrl" alt="Las Orquídeas Logo" className="w-full h-full object-cover object-center rounded-full" />
          </div>
          <div className="flex flex-col">
            <span className="font-extrabold text-xs md:text-sm tracking-wider text-slate-900 leading-tight">
              LAS ORQUÍDEAS
            </span>
            <span className="text-[8px] md:text-[9px] font-semibold tracking-[0.22em] text-slate-500 uppercase">
              Lotificadora
            </span>
          </div>
        </button>

        <!-- Desktop Navigation Items -->
        <div className="hidden md:flex items-center space-x-1 lg:space-x-1.5 text-xs lg:text-sm font-medium text-slate-600">
          <button
            v-for="link in navLinks"
            :key="link.id"
            @click="handleNavClick(link.id)"
            :class="[
              'px-3.5 py-1.5 rounded-full transition-all duration-200 cursor-pointer',
              activeScreen === link.id
                ? 'bg-[#E5EEF9] text-[#2B5282] font-semibold shadow-xs'
                : 'hover:text-brand-forest hover:bg-slate-100/80'
            ]"
          >
            {{ link.label }}
          </button>
        </div>

        <!-- Right Action buttons -->
        <div className="flex items-center gap-2">
          <!-- View Mode Switcher -->
          <div className="hidden lg:flex items-center p-0.5 bg-slate-200/70 rounded-full text-[11px] font-medium text-slate-600">
            <button
              @click="$emit('update:viewMode', 'landing')"
              :class="[
                'flex items-center gap-1 px-2.5 py-1 rounded-full transition-all cursor-pointer',
                viewMode === 'landing' ? 'bg-white text-slate-900 shadow-xs font-semibold' : 'hover:text-slate-900'
              ]"
              title="Ver página completa continua"
            >
              <Eye className="w-3 h-3" />
              <span>Página</span>
            </button>
            <button
              @click="$emit('update:viewMode', 'screens')"
              :class="[
                'flex items-center gap-1 px-2.5 py-1 rounded-full transition-all cursor-pointer',
                viewMode === 'screens' ? 'bg-white text-brand-forest shadow-xs font-semibold' : 'hover:text-slate-900'
              ]"
              title="Navegar por pantallas individuales interactivas"
            >
              <LayoutGrid className="w-3 h-3" />
              <span>Pantallas</span>
            </button>
          </div>

          <!-- Contact WhatsApp Button -->
          <a
            href="https://wa.me/50255555555?text=Hola,%20deseo%20información%20sobre%20los%20lotes%20disponibles%20en%20Las%20Orquídeas"
            target="_blank"
            rel="noopener noreferrer"
            className="bg-brand-forest hover:bg-brand-forest-dark text-white px-3.5 md:px-4 py-2 rounded-full text-xs font-semibold flex items-center gap-1.5 shadow-md transition-all hover:shadow-lg transform active:scale-95 whitespace-nowrap"
          >
            <MessageCircle className="w-3.5 h-3.5 fill-current" />
            <span>Contáctanos</span>
          </a>

          <!-- Mobile Hamburger Button -->
          <button
            @click="mobileMenuOpen = !mobileMenuOpen"
            className="md:hidden p-2 rounded-full hover:bg-slate-100 text-slate-700 transition"
            aria-label="Abrir menú"
          >
            <X v-if="mobileMenuOpen" className="w-5 h-5" />
            <Menu v-else className="w-5 h-5" />
          </button>
        </div>
      </nav>

      <!-- Mobile Dropdown Menu -->
      <div v-if="mobileMenuOpen" className="md:hidden mt-2 p-4 glass-card rounded-2xl shadow-xl border border-white/90 animate-fadeIn">
        <div className="flex flex-col space-y-1">
          <button
            v-for="link in navLinks"
            :key="link.id"
            @click="handleNavClick(link.id)"
            :class="[
              'text-left px-3 py-2 rounded-xl text-sm font-medium transition',
              activeScreen === link.id ? 'bg-brand-forest text-white font-semibold' : 'text-slate-700 hover:bg-slate-100'
            ]"
          >
            {{ link.label }}
          </button>
        </div>

        <!-- Mobile View Mode switch -->
        <div className="mt-3 pt-3 border-t border-slate-200/80 flex items-center justify-between text-xs text-slate-600">
          <span className="font-semibold">Modo de Navegación:</span>
          <div className="flex gap-1 bg-slate-100 p-0.5 rounded-lg">
            <button
              @click="$emit('update:viewMode', 'landing')"
              :class="[
                'px-2 py-1 rounded-md text-[11px]',
                viewMode === 'landing' ? 'bg-white shadow-xs font-bold text-slate-900' : ''
              ]"
            >
              Página continua
            </button>
            <button
              @click="$emit('update:viewMode', 'screens')"
              :class="[
                'px-2 py-1 rounded-md text-[11px]',
                viewMode === 'screens' ? 'bg-white shadow-xs font-bold text-brand-forest' : ''
              ]"
            >
              Pantallas
            </button>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref } from 'vue';
import { Menu, X, MessageCircle, Eye, LayoutGrid } from 'lucide-vue-next';
import logoUrl from '../../assets/images/Logo.jpg';

const props = defineProps({
  activeScreen: { type: String, default: 'inicio' },
  viewMode: { type: String, default: 'landing' }
});

const emit = defineEmits(['update:activeScreen', 'update:viewMode']);

const mobileMenuOpen = ref(false);

const navLinks = [
  { id: 'inicio', label: 'Inicio' },
  { id: 'proyecto', label: 'Proyecto' },
  { id: 'lotes', label: 'Lotes' },
  { id: 'ubicacion', label: 'Ubicación' },
  { id: 'galeria', label: 'Galería' },
  { id: 'contacto', label: 'Contacto' },
];

const handleNavClick = (id) => {
  emit('update:activeScreen', id);
  mobileMenuOpen.value = false;

  if (props.viewMode === 'landing') {
    const element = document.getElementById(id);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth' });
    }
  }
};
</script>
