<template>
  <div class="min-h-screen bg-app-scenic relative selection:bg-primary/30 selection:text-white">
    <Sidebar />
    
    <div class="flex flex-col min-h-screen relative z-10">
      <TopBar />
      
      <main class="flex-grow md:ml-[280px]">
        <router-view v-slot="{ Component }">
          <transition name="fade-slide" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
      
      <footer class="md:ml-[280px] p-10 text-center text-xs font-bold text-white/40 uppercase tracking-[0.2em]">
        ConstructPro © 2024 Suite de Gestión • Versión Premium 4.2.0
      </footer>
    </div>

    <div class="fixed inset-0 pointer-events-none z-0 opacity-40">
      <div class="absolute top-[10%] left-[5%] w-[500px] h-[500px] bg-primary/10 blur-[150px] rounded-full"></div>
      <div class="absolute bottom-[10%] right-[5%] w-[600px] h-[600px] bg-tertiary/5 blur-[180px] rounded-full"></div>
    </div>
  </div>
</template>

<script setup>
import Sidebar from './Sidebar.vue';
import TopBar from './TopBar.vue';
</script>

<style>
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: opacity 0.4s ease-out, transform 0.4s ease-out;
}
.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(10px);
}
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
