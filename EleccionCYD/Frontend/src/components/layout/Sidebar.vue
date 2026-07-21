<script setup>
import { computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { Squares2X2Icon, ScaleIcon, ChartBarIcon, Cog6ToothIcon, ArrowRightOnRectangleIcon } from '@heroicons/vue/24/outline';

const router = useRouter();
const route = useRoute();

const emit = defineEmits(['submit-scores']);

const currentScreen = computed(() => route.name);

const navigateTo = (routeName) => {
  router.push({ name: routeName });
};

const handleLogout = () => {
  router.push('/login');
};
</script>

<template>
  <aside class="hidden lg:flex flex-col py-8 gap-8 h-[calc(100vh-80px)] w-64 bg-black/30 backdrop-blur-xl border-r border-white/10 sticky top-20 select-none shrink-0 overflow-y-auto">
    <!-- Judge Profile Header -->
    <div class="px-6 flex items-center gap-3">
      <div class="w-10 h-10 rounded-full bg-white/10 overflow-hidden border border-white/15">
        <img
          class="w-full h-full object-cover"
          alt="Foto del jurado oficial"
          src="https://lh3.googleusercontent.com/aida-public/AB6AXuAwGbxslq_rbCzMPL3FlMpsyeZ_SFYvR5KNwEy9riyV-1mGz0OVEVv6hN_ct2oDFSj3xKcP1kVXlllLXZfiFK54JXU5HvokGBZ1w-ZGfHZm3gzo4P4XvqHiY2VUD7AVTOBYok9BIi3papekiMf4Q-xx46mtjSOJaBVNGAm7TxrtSzEUv2WibH4tB--cyvqi0xAXMHmRhHMovdLoK38w-usJzeR8Bux85fymdttJbrG1XIcmmtLYZSyz"
        />
      </div>
      <div>
        <p class="text-[11px] font-semibold tracking-wider text-white uppercase">Jurado Oficial</p>
        <p class="text-xs text-white/50">EleccionCYD · Edición 2026</p>
      </div>
    </div>

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
        @click="() => alert('Configuración del entorno de evaluación cargada.')"
        class="flex items-center gap-4 text-white/50 hover:text-white transition-colors pl-4 text-left cursor-pointer"
      >
        <Cog6ToothIcon class="w-4 h-4 stroke-[1.5]" />
        <span class="text-[10px] uppercase tracking-widest font-semibold">Configuración</span>
      </button>

      <button
        @click="handleLogout"
        class="flex items-center gap-4 text-white/50 hover:text-white transition-colors pl-4 text-left cursor-pointer"
      >
        <ArrowRightOnRectangleIcon class="w-4 h-4 stroke-[1.5]" />
        <span class="text-[10px] uppercase tracking-widest font-semibold">Cerrar sesión</span>
      </button>

      <button
        @click="emit('submit-scores')"
        class="mt-4 w-full bg-amber-400 text-black py-4 text-[10px] font-semibold tracking-widest uppercase hover:bg-amber-300 transition-all duration-200 rounded-xl cursor-pointer"
      >
        Enviar Calificaciones Finales
      </button>
    </div>
  </aside>
</template>
