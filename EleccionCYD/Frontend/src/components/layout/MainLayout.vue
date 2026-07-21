<script setup>
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ExclamationTriangleIcon, CheckIcon } from '@heroicons/vue/24/outline';
import Navbar from './Navbar.vue';
import Sidebar from './Sidebar.vue';
import { useModelStore } from '../../stores/modelStore';

const route = useRoute();
const router = useRouter();
const store = useModelStore();

// Igual que en el diseño original: el Sidebar solo se muestra en judging o leaderboard.
const showSidebar = computed(() => {
  return route.name === 'LiveJudging' || route.name === 'Leaderboard';
});

const isFinalScoreModalOpen = ref(false);
const modalType = ref('confirm');

const handleFinalSubmitAll = () => {
  modalType.value = 'confirm';
  isFinalScoreModalOpen.value = true;
};

const confirmFinalSubmitAll = () => {
  modalType.value = 'success';
  store.finalizeAllScores();
};

const closeAndGoToLeaderboard = () => {
  isFinalScoreModalOpen.value = false;
  router.push({ name: 'Leaderboard' });
};
</script>

<template>
  <div class="h-screen overflow-hidden bg-[#f9f9f9] text-[#1a1c1c] flex flex-col font-sans selection:bg-black selection:text-white relative">
    <!-- Sticky Top Navbar -->
    <Navbar />

    <!-- Main Layout Area -->
    <div class="flex-1 flex overflow-hidden">
      <!-- Conditional Sidebar -->
      <Sidebar v-if="showSidebar" @submit-scores="handleFinalSubmitAll" />

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
    
    <!-- Final Verification Overlay Modal -->
    <Transition name="fade-in">
      <div v-if="isFinalScoreModalOpen" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-6 z-[100] selection:bg-white selection:text-black">
        <div class="bg-white border border-gray-100 max-w-md w-full p-8 text-left rounded-none shadow-2xl relative">
          <template v-if="modalType === 'confirm'">
            <div class="flex items-center gap-3 text-amber-600 mb-6">
              <ExclamationTriangleIcon class="w-6 h-6" />
              <h4 class="text-lg font-bold tracking-widest uppercase text-black">Lock & Publish</h4>
            </div>

            <p class="text-sm text-gray-500 leading-relaxed mb-6">
              You are about to transmit all scored metrics to the central mainframe registry. This action locks your score sheets and completes your judiciary duty for Paris Collection 2024.
            </p>

            <div class="flex flex-col gap-3">
              <button
                @click="confirmFinalSubmitAll"
                class="w-full bg-black text-white py-4 text-xs font-semibold tracking-widest uppercase hover:bg-neutral-800 transition-colors rounded-none cursor-pointer"
              >
                Confirm and Transmit
              </button>
              <button
                @click="isFinalScoreModalOpen = false"
                class="w-full border border-gray-200 text-gray-500 py-4 text-xs font-semibold tracking-widest uppercase hover:text-black hover:border-black transition-colors rounded-none cursor-pointer"
              >
                Cancel
              </button>
            </div>
          </template>
          <div v-else class="text-center py-4">
            <div class="w-12 h-12 bg-black rounded-full flex items-center justify-center mx-auto mb-6 text-white">
              <CheckIcon class="w-6 h-6 stroke-[2.5]" />
            </div>
            <h4 class="text-lg font-bold tracking-widest uppercase text-black mb-3">Transmission Complete</h4>
            <p class="text-sm text-gray-500 leading-relaxed mb-8">
              Your final scores have been encrypted and synchronized across other jury terminals. Thank you for your architectural judgment.
            </p>
            <button
              @click="closeAndGoToLeaderboard"
              class="w-full bg-black text-white py-4 text-xs font-semibold tracking-widest uppercase hover:bg-neutral-800 transition-colors rounded-none cursor-pointer"
            >
              Return to Leaderboard
            </button>
          </div>
        </div>
      </div>
    </Transition>

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

.fade-in-enter-active,
.fade-in-leave-active {
  transition: opacity 0.2s ease;
}
.fade-in-enter-from,
.fade-in-leave-to {
  opacity: 0;
}
</style>
