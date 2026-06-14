<template>
  <div class="flex h-screen relative overflow-hidden font-sans text-slate-800 selection:bg-[#3455b9]/20">
    
    <!-- Absolute floating atmospheric backdrop blobs -->
    <div class="fixed top-0 left-0 right-0 bottom-0 overflow-hidden pointer-events-none -z-10">
      <div class="absolute top-[-10%] left-[-15%] w-[80vw] h-[80vw] sm:w-[50vw] sm:h-[50vw] rounded-full bg-blue-100/15 blur-[150px]" />
      <div class="absolute bottom-[-10%] right-[-15%] w-[80vw] h-[80vw] sm:w-[50vw] sm:h-[50vw] rounded-full bg-slate-200/10 blur-[150px]" />
      <div class="absolute top-[40%] right-[30%] w-[30vh] h-[30vh] rounded-full bg-indigo-100/10 blur-[120px]" />
    </div>

    <!-- Sidebar Navigation -->
    <Sidebar />

    <!-- Switch Screens Content Wrapper -->
    <main class="flex-1 overflow-y-auto z-10 relative">
      <div class="p-4 sm:p-8 md:p-12 max-w-7xl mx-auto min-h-full pb-24 md:pb-12">
        <router-view></router-view>
      </div>
    </main>

    <!-- Mobile Sticky Tab bar navigation -->
    <nav class="fixed bottom-0 left-0 right-0 h-16 bg-white/40 backdrop-blur-2xl border-t border-white/50 flex justify-around items-center px-4 md:hidden z-40 shadow-lg">
      
      <router-link to="/admin/dashboard" custom v-slot="{ navigate, isActive }">
        <button
          @click="navigate"
          class="flex flex-col items-center gap-1 transition-all cursor-pointer"
          :class="isActive ? 'text-[#3455b9]' : 'text-gray-500'"
        >
          <Squares2X2Icon class="w-5 h-5" />
          <span class="text-[10px] font-bold">Dash</span>
        </button>
      </router-link>

      <router-link to="/admin/registros" custom v-slot="{ navigate, isActive }">
        <button
          @click="navigate"
          class="flex flex-col items-center gap-1 transition-all cursor-pointer"
          :class="isActive ? 'text-[#3455b9]' : 'text-gray-500'"
        >
          <DocumentTextIcon class="w-5 h-5" />
          <span class="text-[10px] font-bold">Historial</span>
        </button>
      </router-link>

      <!-- Floating Add Center Button -->
      <router-link to="/admin/nuevo-registro" custom v-slot="{ navigate }">
        <button
          @click="navigate"
          class="w-12 h-12 -mt-7 bg-[#3455b9] rounded-full shadow-md flex items-center justify-center text-white border-4 border-white cursor-pointer active:scale-90 transition-transform"
        >
          <PlusIcon class="w-6 h-6" />
        </button>
      </router-link>

      <router-link to="/admin/catalogo" custom v-slot="{ navigate, isActive }">
        <button
          @click="navigate"
          class="flex flex-col items-center gap-1 transition-all cursor-pointer"
          :class="isActive ? 'text-[#3455b9]' : 'text-gray-500'"
        >
          <BookOpenIcon class="w-5 h-5" />
          <span class="text-[10px] font-bold">Catálogo</span>
        </button>
      </router-link>

    </nav>

  </div>
</template>

<script setup>
import { Squares2X2Icon, DocumentTextIcon, BookOpenIcon, PlusIcon } from '@heroicons/vue/24/outline';
import Sidebar from './Sidebar.vue';
</script>
