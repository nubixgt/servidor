<script setup>
import { computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { Squares2X2Icon, ScaleIcon, ChartBarIcon, ArrowRightOnRectangleIcon } from '@heroicons/vue/24/outline';
import { confirmLogout } from '../../utils/alerts';
import { useModelStore } from '../../stores/modelStore';

const router = useRouter();
const route = useRoute();
const store = useModelStore();

const currentScreen = computed(() => route.name);

const navigateTo = (routeName) => {
  router.push({ name: routeName });
};

const handleLogout = async () => {
  const result = await confirmLogout();
  if (result.isConfirmed) {
    localStorage.removeItem('token');
    localStorage.removeItem('usuario');
    store.$reset();
    router.push('/login');
  }
};
</script>

<template>
  <aside class="hidden lg:flex flex-col py-8 gap-8 h-[calc(100vh-80px)] w-64 bg-black/30 backdrop-blur-xl border-r border-white/10 sticky top-20 select-none shrink-0 overflow-y-auto">
    <!-- Nav Menu -->
    <nav class="flex flex-col gap-1 flex-grow">
      <button
        @click="navigateTo('ModelDirectory')"
        :class="[
          'flex items-center gap-4 pl-6 py-3 cursor-pointer text-left transition-all duration-200',
          currentScreen === 'ModelDirectory' ? 'text-amber-400 font-semibold bg-white/5 border-l-2 border-amber-400' : 'text-white/50 hover:text-white hover:bg-white/5'
        ]"
      >
        <Squares2X2Icon class="w-[18px] h-[18px] stroke-[1.5]" />
        <span class="text-xs uppercase tracking-widest font-semibold">Participantes</span>
      </button>

      <button
        @click="navigateTo('LiveJudging')"
        :class="[
          'flex items-center gap-4 pl-6 py-3 cursor-pointer text-left transition-all duration-200',
          currentScreen === 'LiveJudging' ? 'text-amber-400 font-semibold bg-white/5 border-l-2 border-amber-400' : 'text-white/50 hover:text-white hover:bg-white/5'
        ]"
      >
        <ScaleIcon class="w-[18px] h-[18px] stroke-[1.5]" />
        <span class="text-xs uppercase tracking-widest font-semibold">Evaluación</span>
      </button>

      <button
        @click="navigateTo('Leaderboard')"
        :class="[
          'flex items-center gap-4 pl-6 py-3 cursor-pointer text-left transition-all duration-200',
          currentScreen === 'Leaderboard' ? 'text-amber-400 font-semibold bg-white/5 border-l-2 border-amber-400' : 'text-white/50 hover:text-white hover:bg-white/5'
        ]"
      >
        <ChartBarIcon class="w-[18px] h-[18px] stroke-[1.5]" />
        <span class="text-xs uppercase tracking-widest font-semibold">Resultados</span>
      </button>
    </nav>

    <!-- Bottom Actions -->
    <div class="px-4 mt-auto border-t border-white/10 pt-6 flex flex-col gap-4">
      <button
        @click="handleLogout"
        class="flex items-center gap-4 text-white/50 hover:text-white transition-colors pl-4 text-left cursor-pointer"
      >
        <ArrowRightOnRectangleIcon class="w-4 h-4 stroke-[1.5]" />
        <span class="text-[10px] uppercase tracking-widest font-semibold">Cerrar sesión</span>
      </button>
    </div>
  </aside>
</template>
