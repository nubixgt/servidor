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
  <aside class="hidden lg:flex flex-col py-8 gap-8 h-[calc(100vh-80px)] w-64 bg-white border-r border-gray-200 sticky top-20 select-none shrink-0 overflow-y-auto">
    <!-- Judge Profile Header -->
    <div class="px-6 flex items-center gap-3">
      <div class="w-10 h-10 rounded-full bg-neutral-100 overflow-hidden border border-gray-100">
        <img 
          class="w-full h-full object-cover" 
          alt="Official Judge portrait" 
          src="https://lh3.googleusercontent.com/aida-public/AB6AXuAwGbxslq_rbCzMPL3FlMpsyeZ_SFYvR5KNwEy9riyV-1mGz0OVEVv6hN_ct2oDFSj3xKcP1kVXlllLXZfiFK54JXU5HvokGBZ1w-ZGfHZm3gzo4P4XvqHiY2VUD7AVTOBYok9BIi3papekiMf4Q-xx46mtjSOJaBVNGAm7TxrtSzEUv2WibH4tB--cyvqi0xAXMHmRhHMovdLoK38w-usJzeR8Bux85fymdttJbrG1XIcmmtLYZSyz"
        />
      </div>
      <div>
        <p class="text-[11px] font-semibold tracking-wider text-black uppercase">Official Judge</p>
        <p class="text-xs text-gray-500">Paris Collection 2024</p>
      </div>
    </div>

    <!-- Nav Menu -->
    <nav class="flex flex-col gap-1 flex-grow">
      <button 
        @click="navigateTo('ModelDirectory')"
        :class="[
          'flex items-center gap-4 pl-6 py-3 cursor-pointer text-left transition-all duration-200',
          currentScreen === 'ModelDirectory' ? 'text-black font-semibold bg-gray-50 border-l-2 border-black' : 'text-gray-400 hover:text-black hover:bg-gray-50/50'
        ]"
      >
        <Squares2X2Icon class="w-[18px] h-[18px] stroke-[1.5]" />
        <span class="text-xs uppercase tracking-widest font-semibold">Models</span>
      </button>

      <button 
        @click="navigateTo('LiveJudging')"
        :class="[
          'flex items-center gap-4 pl-6 py-3 cursor-pointer text-left transition-all duration-200',
          currentScreen === 'LiveJudging' ? 'text-black font-semibold bg-gray-50 border-l-2 border-black' : 'text-gray-400 hover:text-black hover:bg-gray-50/50'
        ]"
      >
        <ScaleIcon class="w-[18px] h-[18px] stroke-[1.5]" />
        <span class="text-xs uppercase tracking-widest font-semibold">Judging</span>
      </button>

      <button 
        @click="navigateTo('Leaderboard')"
        :class="[
          'flex items-center gap-4 pl-6 py-3 cursor-pointer text-left transition-all duration-200',
          currentScreen === 'Leaderboard' ? 'text-black font-semibold bg-gray-50 border-l-2 border-black' : 'text-gray-400 hover:text-black hover:bg-gray-50/50'
        ]"
      >
        <ChartBarIcon class="w-[18px] h-[18px] stroke-[1.5]" />
        <span class="text-xs uppercase tracking-widest font-semibold">Results</span>
      </button>
    </nav>

    <!-- Bottom Actions -->
    <div class="px-4 mt-auto border-t border-gray-100 pt-6 flex flex-col gap-4">
      <button 
        @click="() => alert('Judiciary environment settings loaded.')"
        class="flex items-center gap-4 text-gray-400 hover:text-black transition-colors pl-4 text-left cursor-pointer"
      >
        <Cog6ToothIcon class="w-4 h-4 stroke-[1.5]" />
        <span class="text-[10px] uppercase tracking-widest font-semibold">Settings</span>
      </button>

      <button 
        @click="handleLogout"
        class="flex items-center gap-4 text-gray-400 hover:text-black transition-colors pl-4 text-left cursor-pointer"
      >
        <ArrowRightOnRectangleIcon class="w-4 h-4 stroke-[1.5]" />
        <span class="text-[10px] uppercase tracking-widest font-semibold">Logout</span>
      </button>

      <button
        @click="emit('submit-scores')"
        class="mt-4 w-full bg-black text-white py-4 text-[10px] font-semibold tracking-widest uppercase hover:bg-neutral-800 transition-all duration-200 rounded-none cursor-pointer"
      >
        Submit Final Scores
      </button>
    </div>
  </aside>
</template>
