<template>
  <main class="flex-1 overflow-y-auto pt-16 md:pt-0 bg-[#F8F9FA] relative">
    <div class="bg-[#2E7D32] pb-32 pt-8 px-8 rounded-b-[2.5rem] shadow-lg relative z-0">
      <div class="max-w-7xl mx-auto">
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
          <div>
            <h1 class="text-2xl font-bold text-white mb-1">{{ title }}</h1>
            <p class="text-green-100 text-sm">{{ subtitle }}</p>
          </div>
          <div class="flex items-center gap-3">
            <div class="relative hidden md:block">
              <!-- Search Icon -->
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-3 top-1/2 -translate-y-1/2 text-white/70">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input
                class="pl-10 pr-4 py-2 rounded-lg border-none bg-white/10 text-white placeholder-white/70 focus:ring-2 focus:ring-white/50 focus:bg-white/20 transition-all text-sm w-64 shadow-inner outline-none"
                placeholder="Buscar..."
                type="text"
              />
            </div>
            <button class="p-2 rounded-lg bg-white/10 text-white hover:bg-white/20 transition-colors relative shadow-sm">
              <!-- Bell Icon -->
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
              </svg>
              <span class="absolute top-2 right-2 w-2 h-2 rounded-full bg-red-400 border border-[#2E7D32]"></span>
            </button>
            <button class="p-2 rounded-lg bg-white/10 text-white hover:bg-white/20 transition-colors shadow-sm">
              <!-- Settings Icon -->
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </button>
          </div>
        </header>

        <div v-if="$slots.actions" class="mt-4">
          <slot name="actions"></slot>
        </div>
      </div>
    </div>

    <div class="px-6 md:px-8 max-w-7xl mx-auto -mt-24 pb-8 relative z-10 space-y-6">
      <slot></slot>
    </div>
  </main>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true
  },
  subtitle: {
    type: String,
    required: true
  }
});
</script>
