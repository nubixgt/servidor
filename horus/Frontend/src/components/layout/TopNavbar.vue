<template>
  <header class="bg-background dark:bg-background fixed top-0 right-0 left-0 md:left-64 z-40 flex items-center justify-between px-gutter h-20 transition-all duration-300">
    <div class="flex items-center gap-4">
      <div class="relative hidden md:flex items-center">
        <span class="material-symbols-outlined absolute left-3 text-on-surface-variant text-lg">search</span>
        <input class="bg-surface-container-high border border-outline-variant/50 text-on-surface placeholder:text-on-surface-variant rounded-full py-2 pl-10 pr-4 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary w-80 transition-all duration-200 font-body-sm shadow-inner" placeholder="Buscar clientes, préstamos..." type="text"/>
      </div>
      
      <!-- Mobile menu toggle -->
      <button class="md:hidden text-on-surface-variant hover:text-primary transition-colors">
        <span class="material-symbols-outlined">menu</span>
      </button>
      <h1 class="font-headline-lg-mobile md:font-headline-lg font-bold text-primary dark:text-primary tracking-tight md:hidden">Horus</h1>
    </div>
    
    <div class="flex items-center gap-6">
      <div class="flex items-center gap-2">
        <button class="text-on-surface-variant hover:text-primary relative hover:bg-surface-container-high p-2 rounded-full transition-colors scale-95 active:opacity-80">
          <span class="material-symbols-outlined">notifications</span>
          <span class="absolute top-2 right-2 w-2 h-2 bg-primary rounded-full border border-background"></span>
        </button>
        <button class="text-on-surface-variant hover:text-primary hover:bg-surface-container-high p-2 rounded-full transition-colors scale-95 active:opacity-80 hidden sm:block">
          <span class="material-symbols-outlined text-[20px]">help_outline</span>
        </button>
        <button class="text-on-surface-variant hover:text-primary hover:bg-surface-container-high p-2 rounded-full transition-colors scale-95 active:opacity-80 hidden sm:block">
          <span class="material-symbols-outlined text-[20px]">settings</span>
        </button>
      </div>
      <div class="flex items-center gap-3">
        <div class="text-right hidden sm:block">
          <p class="font-body-sm text-on-surface font-medium">Arturo V.</p>
          <p class="text-[11px] text-on-surface-variant">Director General</p>
        </div>
        <div class="w-10 h-10 rounded-lg bg-primary flex items-center justify-center text-on-primary font-body-lg font-bold cursor-pointer hover:opacity-90 transition-opacity shadow-[0_0_10px_rgba(233,193,118,0.2)]">
          AV
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
</script>
