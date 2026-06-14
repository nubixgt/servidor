<template>
  <aside class="w-64 bg-white/60 backdrop-blur-[20px] border-r border-slate-200/60 p-6 flex flex-col z-40 transition-all duration-300 hidden md:flex">
    <div class="mb-10 flex items-center gap-3">
      <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-[#3455b9] to-[#506ed3] flex items-center justify-center text-white shadow-md overflow-hidden transition-transform hover:rotate-12 duration-300">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H7c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.04-.42 1.99-1.07 2.75z"/>
        </svg>
      </div>
      <div>
        <h1 class="text-xl font-extrabold text-[#3455b9] leading-tight tracking-tight">Vacunaciones</h1>
        <p class="text-[10px] text-slate-500 uppercase tracking-widest font-extrabold">Clínica Avícola</p>
      </div>
    </div>

    <nav class="flex-1 space-y-2">
      <router-link
        to="/admin/dashboard"
        custom
        v-slot="{ navigate, isActive }"
      >
        <button
          @click="navigate"
          class="w-full flex items-center gap-4 px-4 py-3 rounded-2xl font-bold transition-all duration-200 text-left cursor-pointer"
          :class="isActive ? 'text-[#3455b9] bg-white/60 shadow-sm translate-x-1 border border-white/40' : 'text-gray-700 hover:text-[#3455b9] hover:bg-white/30'"
        >
          <Squares2X2Icon class="w-5 h-5" />
          <span class="text-sm">Dashboard</span>
        </button>
      </router-link>

      <router-link
        to="/admin/registros"
        custom
        v-slot="{ navigate, isActive }"
      >
        <button
          @click="navigate"
          class="w-full flex items-center gap-4 px-4 py-3 rounded-2xl font-bold transition-all duration-200 text-left cursor-pointer"
          :class="isActive ? 'text-[#3455b9] bg-white/60 shadow-sm translate-x-1 border border-white/40' : 'text-gray-700 hover:text-[#3455b9] hover:bg-white/30'"
        >
          <DocumentTextIcon class="w-5 h-5" />
          <span class="text-sm">Historial</span>
        </button>
      </router-link>

      <router-link
        to="/admin/nuevo-registro"
        custom
        v-slot="{ navigate, isActive }"
      >
        <button
          @click="navigate"
          class="w-full flex items-center gap-4 px-4 py-3 rounded-2xl font-bold transition-all duration-200 text-left cursor-pointer"
          :class="isActive ? 'text-[#3455b9] bg-white/60 shadow-sm translate-x-1 border border-white/40' : 'text-gray-700 hover:text-[#3455b9] hover:bg-white/30'"
        >
          <PlusIcon class="w-5 h-5" />
          <span class="text-sm">Nuevo Registro</span>
        </button>
      </router-link>

      <router-link
        to="/admin/catalogo"
        custom
        v-slot="{ navigate, isActive }"
      >
        <button
          @click="navigate"
          class="w-full flex items-center gap-4 px-4 py-3 rounded-2xl font-bold transition-all duration-200 text-left cursor-pointer"
          :class="isActive ? 'text-[#3455b9] bg-white/60 shadow-sm translate-x-1 border border-white/40' : 'text-gray-700 hover:text-[#3455b9] hover:bg-white/30'"
        >
          <BookOpenIcon class="w-5 h-5" />
          <span class="text-sm">Catálogo</span>
        </button>
      </router-link>
    </nav>
  </aside>
</template>

<script setup>
import { Squares2X2Icon, DocumentTextIcon, BookOpenIcon, PlusIcon } from '@heroicons/vue/24/outline';
</script>
