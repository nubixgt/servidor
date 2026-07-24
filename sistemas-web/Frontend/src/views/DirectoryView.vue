<template>
  <div class="flex h-screen bg-[#070b19] font-sans text-slate-300 overflow-hidden">
    
    <!-- Sidebar -->
    <aside class="w-64 bg-[#0a0f25] border-r border-slate-800/50 hidden md:flex flex-col">
      <!-- Logo -->
      <div class="h-20 flex items-center px-6 gap-3 border-b border-slate-800/50">
        <LucideIcons.Box class="w-6 h-6 text-indigo-400" />
        <span class="text-white font-bold tracking-widest">SYSTEMS</span>
      </div>
      
      <!-- Nav Menu -->
      <nav class="flex-1 py-6 px-4 space-y-8">
        <div class="space-y-2">
          <router-link to="/" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-400 hover:bg-slate-800/50 hover:text-white transition-colors">
            <LucideIcons.Home class="w-5 h-5" />
            <span class="font-medium text-sm">WELCOME</span>
          </router-link>
          
          <router-link to="/directorio" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-indigo-950/30 text-indigo-300 border border-indigo-500/20">
            <LucideIcons.FolderOpen class="w-5 h-5" />
            <span class="font-medium text-sm">DIRECTORY</span>
          </router-link>
        </div>
        
        <div>
          <div class="px-4 mb-3 text-xs font-bold text-slate-600 tracking-wider">ADMINISTRATION</div>
          <a href="#" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-400 hover:bg-slate-800/50 hover:text-white transition-colors">
            <LucideIcons.Settings class="w-5 h-5" />
            <span class="font-medium text-sm">SETTINGS</span>
          </a>
        </div>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden relative">
      <!-- Espaciador superior -->
      <div class="h-8"></div>

      <!-- Scrollable Area -->
      <div class="flex-1 overflow-y-auto px-6 md:px-12 pb-24">
        
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
          <div>
            <div class="flex items-center gap-3 text-sm text-slate-500 font-semibold tracking-widest mb-2 uppercase">
              <span class="w-8 h-[2px] bg-indigo-500"></span>
              System Architecture
            </div>
            <h1 class="text-4xl md:text-5xl font-bold text-white tracking-tight">
              DIRECTORIO DE <span class="text-indigo-300">SISTEMAS</span>
            </h1>
          </div>
          
          <router-link to="/" class="flex items-center gap-2 px-4 py-2 bg-slate-800/50 hover:bg-slate-800 text-slate-300 rounded-lg border border-slate-700/50 transition-colors text-sm font-medium">
            <LucideIcons.ArrowLeft class="w-4 h-4" />
            VOLVER
          </router-link>
        </div>

        <!-- Search Bar -->
        <div class="relative max-w-2xl mb-12">
          <LucideIcons.Search class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500" />
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Filtrar sistemas por nombre, etiqueta o ID..." 
            class="w-full bg-[#0d132c] border border-slate-800/80 text-white rounded-xl pl-12 pr-16 py-4 focus:outline-none focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/50 transition-all placeholder-slate-600"
          >
          <div class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-mono text-slate-600 bg-[#161f42] px-2 py-1 rounded">CMD + K</div>
        </div>

        <!-- Stats Row -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12 border-b border-slate-800/50 pb-8">
          <div>
            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Total Nodes</div>
            <div class="text-xl font-semibold text-white">124</div>
          </div>
          <div>
            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Active Status</div>
            <div class="text-xl font-semibold text-sky-400">99.8%</div>
          </div>
          <div>
            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Security Tier</div>
            <div class="text-xl font-semibold text-white">Class A</div>
          </div>
          <div>
            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Data Flow</div>
            <div class="text-xl font-semibold text-white">12 Gbps</div>
          </div>
        </div>

        <!-- Grid of Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          <div 
            v-for="sistema in filteredSistemas" 
            :key="sistema.id"
            class="relative group bg-[#0e142c] border border-slate-800/60 rounded-3xl p-6 hover:bg-[#121936] hover:border-indigo-500/30 transition-all duration-300 flex flex-col h-full overflow-hidden"
          >
            <!-- Giant Number -->
            <div class="absolute top-4 right-4 text-6xl font-black text-slate-800/30 z-0 pointer-events-none group-hover:text-indigo-900/10 transition-colors">
              {{ sistema.id }}
            </div>
            
            <!-- Icon Container -->
            <div class="relative z-10 w-12 h-12 rounded-2xl bg-indigo-950/50 border border-indigo-500/20 flex items-center justify-center mb-6">
              <component :is="LucideIcons[sistema.icono] || LucideIcons.LayoutDashboard" class="w-6 h-6 text-indigo-300" />
            </div>
            
            <div class="relative z-10 flex-1">
              <h3 class="text-white font-semibold text-lg mb-2">{{ sistema.nombre }}</h3>
              <p class="text-slate-400 text-sm leading-relaxed mb-6">{{ sistema.descripcion }}</p>
            </div>
            
            <a 
              :href="sistema.url" 
              target="_blank" 
              class="relative z-10 w-full py-3 bg-slate-200/90 hover:bg-white text-slate-900 rounded-xl font-semibold text-sm tracking-wide flex items-center justify-center gap-2 transition-colors mt-auto"
            >
              ACCEDER
              <LucideIcons.ChevronRight class="w-4 h-4 text-slate-600" />
            </a>
          </div>
          
          <div v-if="filteredSistemas.length === 0" class="col-span-full py-12 text-center text-slate-500">
            No se encontraron sistemas que coincidan con la búsqueda.
          </div>
        </div>

      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import * as LucideIcons from 'lucide-vue-next'
import { sistemas } from '../data/sistemas.js'

const searchQuery = ref('')

const filteredSistemas = computed(() => {
  if (!searchQuery.value) return sistemas
  
  const query = searchQuery.value.toLowerCase()
  return sistemas.filter(s => 
    s.nombre.toLowerCase().includes(query) || 
    s.descripcion.toLowerCase().includes(query)
  )
})
</script>
