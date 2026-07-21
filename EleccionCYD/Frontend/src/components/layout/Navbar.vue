<script setup>
import { computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { UserIcon, Bars3Icon } from '@heroicons/vue/24/outline';

const router = useRouter();
const route = useRoute();

const currentScreen = computed(() => route.name);

const navigateTo = (routeName) => {
  router.push({ name: routeName });
};

const handleLogout = () => {
  router.push('/login');
};
</script>

<template>
  <header class="bg-[#f9f9f9] border-b border-black sticky top-0 z-50 select-none">
    <div class="flex justify-between items-center w-full px-6 md:px-16 h-20 max-w-[1440px] mx-auto">
      <!-- Logo -->
      <button 
        @click="navigateTo('ModelDirectory')" 
        class="font-serif text-lg md:text-xl tracking-[0.25em] text-black uppercase cursor-pointer text-left font-normal"
      >
        AURA FASHION WEEK
      </button>

      <!-- Center Navigation Links -->
      <nav class="hidden md:flex items-center gap-12 h-full">
        <button
          @click="navigateTo('ModelDirectory')"
          :class="[
            'font-semibold text-xs uppercase tracking-[0.15em] pb-1 cursor-pointer transition-all duration-300',
            currentScreen === 'ModelDirectory' ? 'text-black border-b border-black' : 'text-gray-400 hover:text-black'
          ]"
        >
          Directory
        </button>
        <button
          @click="navigateTo('LiveJudging')"
          :class="[
            'font-semibold text-xs uppercase tracking-[0.15em] pb-1 cursor-pointer transition-all duration-300',
            currentScreen === 'LiveJudging' ? 'text-black border-b border-black' : 'text-gray-400 hover:text-black'
          ]"
        >
          Live Judging
        </button>
        <button
          @click="navigateTo('Leaderboard')"
          :class="[
            'font-semibold text-xs uppercase tracking-[0.15em] pb-1 cursor-pointer transition-all duration-300',
            currentScreen === 'Leaderboard' ? 'text-black border-b border-black' : 'text-gray-400 hover:text-black'
          ]"
        >
          Leaderboard
        </button>
      </nav>

      <!-- User Actions -->
      <div class="flex items-center gap-4">
        <div class="hidden sm:flex flex-col items-end text-right">
          <span class="text-[10px] font-semibold tracking-wider text-black">Official Judge</span>
          <button 
            @click="handleLogout" 
            class="text-[9px] uppercase tracking-widest text-gray-400 hover:text-black transition-colors cursor-pointer"
          >
            Sign out
          </button>
        </div>
        <button 
          class="text-black hover:opacity-80 transition-opacity p-1 cursor-pointer"
          @click="navigateTo('Leaderboard')"
          title="Profile / Results"
        >
          <UserIcon class="w-[22px] h-[22px] stroke-[1.5]" />
        </button>
        <button class="md:hidden text-black p-1 cursor-pointer">
          <Bars3Icon class="w-[22px] h-[22px] stroke-[1.5]" />
        </button>
      </div>
    </div>
  </header>
</template>
