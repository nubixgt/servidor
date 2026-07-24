<script setup>
import { computed, ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowPathIcon } from '@heroicons/vue/24/outline';
import Navbar from './Navbar.vue';
import Sidebar from './Sidebar.vue';
import { useModelStore } from '../../stores/modelStore';
import appBg from '../../assets/images/Fondo.jpeg';

const route = useRoute();
const router = useRouter();
const store = useModelStore();

const esAdmin = computed(() => {
  try {
    return JSON.parse(localStorage.getItem('usuario'))?.rol === 'admin';
  } catch {
    return false;
  }
});

// Igual que en el diseño original: el Sidebar solo se muestra en judging o leaderboard.
const showSidebar = computed(() => {
  return route.name === 'LiveJudging' || route.name === 'Dashboard' || route.name === 'Leaderboard';
});

const loadError = ref('');

const loadInitialData = async () => {
  loadError.value = '';
  try {
    await store.initialize();
  } catch (err) {
    loadError.value = err.response?.data?.message || 'No se pudo conectar con el servidor. Verifica tu conexión e intenta de nuevo.';
  }
};

onMounted(loadInitialData);
</script>

<template>
  <div class="h-screen overflow-hidden text-white flex flex-col font-sans selection:bg-amber-400 selection:text-black relative">
    <!-- Background -->
    <div class="fixed inset-0 -z-10">
      <div class="w-full h-full bg-cover bg-center" :style="{ backgroundImage: `url('${appBg}')` }"></div>
      <div class="absolute inset-0 bg-gradient-to-b from-black/85 via-black/80 to-black/90"></div>
    </div>

    <!-- Sticky Top Navbar -->
    <Navbar />

    <!-- Estado de carga inicial / error de conexión con el Backend -->
    <div v-if="!store.initialized" class="flex-1 flex items-center justify-center p-12">
      <div v-if="loadError" class="text-center max-w-sm">
        <p class="text-red-300 text-sm mb-4">{{ loadError }}</p>
        <button
          @click="loadInitialData"
          class="px-6 py-2.5 bg-amber-400 text-black text-xs font-semibold uppercase tracking-widest rounded-xl hover:bg-amber-300 transition-colors cursor-pointer"
        >
          Reintentar
        </button>
      </div>
      <div v-else class="flex flex-col items-center gap-3 text-white/50">
        <ArrowPathIcon class="w-6 h-6 animate-spin" />
        <p class="text-xs uppercase tracking-widest">Cargando datos...</p>
      </div>
    </div>

    <!-- Main Layout Area -->
    <div v-else class="flex-1 flex overflow-hidden">
      <!-- Conditional Sidebar -->
      <Sidebar v-if="showSidebar" />

      <!-- Scrollable Main Content Frame -->
      <div class="flex-grow overflow-y-auto min-h-[calc(100vh-80px)] flex flex-col">
        <router-view v-slot="{ Component }">
          <transition name="fade-page" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </div>
    </div>

    <!-- Mobile Bottom Navigation -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-black/70 backdrop-blur-xl border-t border-white/10 h-16 flex justify-around items-center z-40 shadow-md">
      <button
        @click="router.push({ name: 'ModelDirectory' })"
        :class="['flex flex-col items-center gap-1 cursor-pointer', route.name === 'ModelDirectory' ? 'text-amber-400 font-semibold' : 'text-white/50']"
      >
        <span class="text-[10px] font-bold uppercase tracking-wider">Participantes</span>
      </button>
      <button
        @click="router.push({ name: 'LiveJudging' })"
        :class="['flex flex-col items-center gap-1 cursor-pointer', route.name === 'LiveJudging' ? 'text-amber-400 font-semibold' : 'text-white/50']"
      >
        <span class="text-[10px] font-bold uppercase tracking-wider">Evaluación</span>
      </button>
      <button
        @click="router.push({ name: 'Dashboard' })"
        :class="['flex flex-col items-center gap-1 cursor-pointer', route.name === 'Dashboard' ? 'text-amber-400 font-semibold' : 'text-white/50']"
      >
        <span class="text-[10px] font-bold uppercase tracking-wider">Mi Panel</span>
      </button>
      <button
        v-if="esAdmin"
        @click="router.push({ name: 'Leaderboard' })"
        :class="['flex flex-col items-center gap-1 cursor-pointer', route.name === 'Leaderboard' ? 'text-amber-400 font-semibold' : 'text-white/50']"
      >
        <span class="text-[10px] font-bold uppercase tracking-wider">Resultados</span>
      </button>
    </nav>

  </div>
</template>

<style>
/* Global Layout Overrides for App consistency */
body {
  background-color: #05070d !important;
  color: #ffffff !important;
}

.fade-page-enter-active,
.fade-page-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.fade-page-enter-from {
  opacity: 0;
  transform: translateY(5px);
}

.fade-page-leave-to {
  opacity: 0;
  transform: translateY(-5px);
}

.fade-in-enter-active,
.fade-in-leave-active {
  transition: opacity 0.2s ease;
}
.fade-in-enter-from,
.fade-in-leave-to {
  opacity: 0;
}
</style>
