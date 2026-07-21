<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Navbar from './Navbar.vue';
import Sidebar from './Sidebar.vue';

const route = useRoute();
const router = useRouter();

// En la App original, el Sidebar solo se muestra en judging o leaderboard.
// Opcionalmente podemos mostrarlo siempre o seguir esa regla.
const showSidebar = computed(() => {
  return route.name === 'LiveJudging' || route.name === 'Leaderboard' || route.name === 'ModelDirectory';
});
</script>

<template>
  <div class="min-h-screen bg-[#f9f9f9] text-[#1a1c1c] flex flex-col font-sans selection:bg-black selection:text-white relative">
    <!-- Sticky Top Navbar -->
    <Navbar />

    <!-- Main Layout Area -->
    <div class="flex-1 flex overflow-hidden">
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
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 h-16 flex justify-around items-center z-40 shadow-md">
      <button 
        @click="router.push({ name: 'ModelDirectory' })"
        :class="['flex flex-col items-center gap-1 cursor-pointer', route.name === 'ModelDirectory' ? 'text-black font-semibold' : 'text-gray-400']"
      >
        <span class="text-[10px] font-bold uppercase tracking-wider">Models</span>
      </button>
      <button 
        @click="router.push({ name: 'LiveJudging' })"
        :class="['flex flex-col items-center gap-1 cursor-pointer', route.name === 'LiveJudging' ? 'text-black font-semibold' : 'text-gray-400']"
      >
        <span class="text-[10px] font-bold uppercase tracking-wider">Judging</span>
      </button>
      <button 
        @click="router.push({ name: 'Leaderboard' })"
        :class="['flex flex-col items-center gap-1 cursor-pointer', route.name === 'Leaderboard' ? 'text-black font-semibold' : 'text-gray-400']"
      >
        <span class="text-[10px] font-bold uppercase tracking-wider">Results</span>
      </button>
    </nav>
    
    <!-- Subtle Grain Overlay for Texture -->
    <div class="fixed inset-0 pointer-events-none opacity-[0.015] z-50" style="background-image: url('https://grainy-gradients.vercel.app/noise.svg')"></div>
  </div>
</template>

<style>
/* Global Layout Overrides for App consistency */
body {
  background-color: #f9f9f9 !important;
  color: #1a1c1c !important;
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
</style>
