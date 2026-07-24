<script setup>
import { computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { Bars3Icon } from '@heroicons/vue/24/outline';
import { confirmLogout } from '../../utils/alerts';
import { useModelStore } from '../../stores/modelStore';
import { getUserPhoto } from '../../data/userPhotos';

const router = useRouter();
const route = useRoute();
const store = useModelStore();

const currentScreen = computed(() => route.name);

const usuarioActual = computed(() => {
  try {
    return JSON.parse(localStorage.getItem('usuario'));
  } catch {
    return null;
  }
});

const usuarioNombre = computed(() => usuarioActual.value?.nombre || 'Jurado Oficial');
const usuarioFoto = computed(() => getUserPhoto(usuarioActual.value?.id));

const navigateTo = (routeName) => {
  router.push({ name: routeName });
};

const handleLogout = async () => {
  const result = await confirmLogout();
  if (result.isConfirmed) {
    localStorage.removeItem('token');
    localStorage.removeItem('usuario');
    store.$reset();
    router.push('/login');
  }
};
</script>

<template>
  <header class="bg-black/50 backdrop-blur-xl border-b border-white/10 sticky top-0 z-50 select-none">
    <div class="flex justify-between items-center w-full px-6 md:px-16 h-20 max-w-[1440px] mx-auto">
      <!-- Logo -->
      <button
        @click="navigateTo('ModelDirectory')"
        class="font-serif text-lg md:text-xl tracking-[0.25em] text-white uppercase cursor-pointer text-left font-normal"
      >
        EleccionCYD
      </button>

      <!-- Center Navigation Links -->
      <nav class="hidden md:flex items-center gap-12 h-full">
        <button
          @click="navigateTo('ModelDirectory')"
          :class="[
            'font-semibold text-xs uppercase tracking-[0.15em] pb-1 cursor-pointer transition-all duration-300',
            currentScreen === 'ModelDirectory' ? 'text-amber-400 border-b border-amber-400' : 'text-white/50 hover:text-white'
          ]"
        >
          Directorio
        </button>
        <button
          @click="navigateTo('LiveJudging')"
          :class="[
            'font-semibold text-xs uppercase tracking-[0.15em] pb-1 cursor-pointer transition-all duration-300',
            currentScreen === 'LiveJudging' ? 'text-amber-400 border-b border-amber-400' : 'text-white/50 hover:text-white'
          ]"
        >
          Evaluación en Vivo
        </button>
        <button
          @click="navigateTo('Dashboard')"
          :class="[
            'font-semibold text-xs uppercase tracking-[0.15em] pb-1 cursor-pointer transition-all duration-300',
            currentScreen === 'Dashboard' ? 'text-amber-400 border-b border-amber-400' : 'text-white/50 hover:text-white'
          ]"
        >
          Mi Panel
        </button>
        <button
          @click="navigateTo('Leaderboard')"
          :class="[
            'font-semibold text-xs uppercase tracking-[0.15em] pb-1 cursor-pointer transition-all duration-300',
            currentScreen === 'Leaderboard' ? 'text-amber-400 border-b border-amber-400' : 'text-white/50 hover:text-white'
          ]"
        >
          Tabla de Posiciones
        </button>
      </nav>

      <!-- User Actions -->
      <div class="flex items-center gap-4">
        <div class="hidden sm:flex flex-col items-end text-right">
          <span class="text-[10px] font-semibold tracking-wider text-white">{{ usuarioNombre }}</span>
          <button
            @click="handleLogout"
            class="text-[9px] uppercase tracking-widest text-white/50 hover:text-white transition-colors cursor-pointer"
          >
            Cerrar sesión
          </button>
        </div>
        <button
          class="rounded-full overflow-hidden w-9 h-9 border border-white/20 hover:border-amber-400 transition-colors cursor-pointer shrink-0"
          @click="navigateTo('Leaderboard')"
          title="Perfil / Resultados"
        >
          <img :src="usuarioFoto" :alt="usuarioNombre" class="w-full h-full object-cover" />
        </button>
        <button class="md:hidden text-white p-1 cursor-pointer">
          <Bars3Icon class="w-[22px] h-[22px] stroke-[1.5]" />
        </button>
      </div>
    </div>
  </header>
</template>
