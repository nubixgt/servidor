<template>
  <div class="bg-[#F8F9FA] font-sans text-[#1F2937] antialiased h-screen flex overflow-hidden">
    <Sidebar />
    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
      <router-view></router-view>
    </div>
  </div>
</template>

<script setup>
import Sidebar from './Sidebar.vue';
</script>
