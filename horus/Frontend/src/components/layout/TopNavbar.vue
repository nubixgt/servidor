<template>
  <header class="bg-surface-container dark:bg-surface-container fixed top-0 right-0 left-0 md:left-64 z-40 flex items-center justify-between px-gutter h-20 border-b border-outline-variant shadow-sm transition-all duration-300">
    <div class="flex items-center gap-4">
      <div class="relative hidden md:flex items-center">
        <span class="material-symbols-outlined absolute left-3 text-on-surface-variant">search</span>
        <input class="bg-surface-container-high border border-outline-variant text-on-surface placeholder:text-on-surface-variant rounded-full py-2 pl-10 pr-4 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary w-64 transition-all duration-200 font-body-sm" placeholder="Buscar clientes, préstamos..." type="text"/>
      </div>
      
      <!-- Mobile menu toggle -->
      <button class="md:hidden text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined">menu</span>
      </button>
      <h1 class="font-headline-lg-mobile md:font-headline-lg font-bold text-primary dark:text-primary tracking-tight md:hidden">Horus</h1>
    </div>
    
    <div class="flex items-center gap-6">
      <div class="flex items-center gap-4">
        <button class="text-on-surface-variant hover:text-primary hover:bg-surface-container-high p-2 rounded-full transition-colors scale-95 active:opacity-80">
          <span class="material-symbols-outlined">notifications</span>
        </button>
        <button class="text-on-surface-variant hover:text-primary hover:bg-surface-container-high p-2 rounded-full transition-colors scale-95 active:opacity-80 hidden sm:block">
          <span class="material-symbols-outlined">help</span>
        </button>
        <button class="text-on-surface-variant hover:text-primary hover:bg-surface-container-high p-2 rounded-full transition-colors scale-95 active:opacity-80 hidden sm:block">
          <span class="material-symbols-outlined">settings</span>
        </button>
      </div>
      <div class="flex items-center gap-3 border-l border-outline-variant pl-6">
        <div class="text-right hidden sm:block">
          <p class="font-title-md text-on-surface">Arturo V.</p>
          <p class="font-label-caps text-on-surface-variant">Director General</p>
        </div>
        <div class="w-10 h-10 rounded-full bg-primary-container flex items-center justify-center text-on-primary-container font-headline-lg-mobile font-bold border-2 border-primary cursor-pointer hover:opacity-80 transition-opacity">
          AV
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
</script>
