<template>
  <div class="relative min-h-screen bg-[#070b19] overflow-hidden flex flex-col items-center justify-center font-sans text-white">
    
    <!-- Background grid / effects -->
    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-5"></div>
    <div class="absolute top-0 right-0 w-96 h-96 bg-blue-600/20 rounded-full blur-[100px] -translate-y-1/2 translate-x-1/3"></div>
    <div class="absolute bottom-0 left-0 w-96 h-96 bg-purple-600/20 rounded-full blur-[100px] translate-y-1/3 -translate-x-1/3"></div>
    
    <!-- Floating Icons -->
    <div class="absolute inset-0 pointer-events-none">
      <!-- Top Left -->
      <div class="absolute top-[20%] left-[15%] transform -translate-x-1/2 -translate-y-1/2 animate-[bounce_5s_ease-in-out_infinite]">
        <div class="glass p-5 rounded-2xl bg-blue-900/30 border border-blue-500/20 shadow-[0_0_30px_rgba(59,130,246,0.3)]">
          <Code class="w-10 h-10 text-blue-400" />
        </div>
      </div>
      
      <!-- Middle Left -->
      <div class="absolute top-[60%] left-[10%] transform -translate-x-1/2 -translate-y-1/2 animate-[bounce_6s_ease-in-out_infinite_0.5s]">
        <div class="glass p-6 rounded-2xl bg-blue-800/20 border border-blue-500/20">
          <Layout class="w-12 h-12 text-blue-500" />
        </div>
      </div>
      
      <!-- Bottom Left -->
      <div class="absolute bottom-[15%] left-[25%] transform -translate-x-1/2 -translate-y-1/2 animate-[bounce_4s_ease-in-out_infinite_1s]">
        <div class="glass p-4 rounded-xl bg-blue-900/40 border border-blue-400/20">
          <Globe class="w-8 h-8 text-blue-300" />
        </div>
      </div>

      <!-- Top Right -->
      <div class="absolute top-[25%] right-[15%] transform translate-x-1/2 -translate-y-1/2 animate-[bounce_7s_ease-in-out_infinite_0.2s]">
        <div class="glass p-5 rounded-2xl bg-purple-900/30 border border-purple-500/20 shadow-[0_0_30px_rgba(168,85,247,0.3)]">
          <Link2 class="w-10 h-10 text-purple-400" />
        </div>
      </div>

      <!-- Middle Right -->
      <div class="absolute top-[55%] right-[10%] transform translate-x-1/2 -translate-y-1/2 animate-[bounce_5.5s_ease-in-out_infinite_1.5s]">
        <div class="glass p-6 rounded-2xl bg-purple-800/20 border border-purple-500/20">
          <FileCode class="w-12 h-12 text-purple-500" />
        </div>
      </div>

      <!-- Bottom Right -->
      <div class="absolute bottom-[20%] right-[20%] transform translate-x-1/2 -translate-y-1/2 animate-[bounce_4.5s_ease-in-out_infinite_0.8s]">
        <div class="glass p-5 rounded-2xl bg-purple-900/40 border border-purple-400/20">
          <Rocket class="w-8 h-8 text-purple-300" />
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 text-center flex flex-col items-center">
      <h1 class="text-6xl md:text-8xl font-bold tracking-tight mb-6">
        SISTEMAS <span class="text-gradient from-indigo-300 to-purple-400">WEB</span>
      </h1>
      
      <p class="text-lg md:text-xl text-slate-400 mb-12 max-w-lg mx-auto font-light">
        Accede rápidamente a todos mis aplicaciones y recursos
      </p>
      
      <router-link 
        to="/directorio" 
        class="group relative inline-flex items-center gap-3 px-8 py-4 rounded-full bg-slate-900/50 border border-indigo-500/30 text-indigo-200 hover:bg-indigo-900/30 hover:border-indigo-400 transition-all duration-300"
      >
        <Link class="w-5 h-5 text-indigo-400 group-hover:text-indigo-300 transition-colors" />
        <span class="font-medium tracking-wide">Explorar enlaces</span>
        
        <!-- Glow effect on hover -->
        <div class="absolute inset-0 rounded-full bg-indigo-500/20 opacity-0 group-hover:opacity-100 blur-md transition-opacity duration-300 -z-10"></div>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { Code, Link2, Layout, Globe, Rocket, Link, FileCode } from 'lucide-vue-next'
</script>

<style scoped>
</style>
