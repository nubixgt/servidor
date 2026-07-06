<template>
    <div class="w-full h-full flex flex-col">
        <!-- Chart Container -->
        <div class="flex-1 flex items-end justify-around relative px-4 pb-6 pt-10">
            
            <!-- Grid Lines (Background) -->
            <div class="absolute inset-0 flex flex-col justify-between px-4 pb-8 pt-4 pointer-events-none opacity-20">
                <div class="border-t border-gray-400 dashed"></div>
                <div class="border-t border-gray-400 dashed"></div>
                <div class="border-t border-gray-400 dashed"></div>
                <div class="border-t border-gray-400 dashed"></div>
                <div class="border-t border-gray-400 dashed"></div>
            </div>

            <!-- Bars -->
            <div v-for="(item, index) in data" :key="index" class="relative group flex flex-col items-center h-full justify-end w-12 sm:w-16">
                <!-- Tooltip -->
                <div class="absolute -top-10 opacity-0 group-hover:opacity-100 transition-opacity bg-white/90 shadow-lg rounded-lg px-3 py-1 text-xs font-bold text-gray-700 pointer-events-none whitespace-nowrap z-10 mb-2">
                    {{ item.name }}: {{ item.value }}
                </div>

                <!-- Bar -->
                <div 
                    class="w-full rounded-t-lg transition-all duration-500 hover:brightness-110 cursor-pointer relative overflow-hidden"
                    :style="{ height: `${item.value}%` }"
                    :class="item.type === 'blue' ? 'bg-blue-500' : 'bg-yellow-400'"
                >
                    <!-- Gradient overlay -->
                    <div 
                        class="absolute inset-0 opacity-80"
                        :style="{ background: item.type === 'blue' ? 'linear-gradient(to bottom, #60A5FA, #3B82F6)' : 'linear-gradient(to bottom, #FCD34D, #FBBF24)' }"
                    ></div>
                </div>

                <!-- X Axis Label -->
                <span class="absolute -bottom-6 text-[10px] sm:text-xs font-semibold text-gray-500 dark:text-gray-400 text-center w-24">
                    {{ item.name }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
const data = [
    { name: 'Solicitados', value: 20, type: 'blue' },
    { name: 'En Proceso', value: 65, type: 'yellow' },
    { name: 'Aprobados', value: 35, type: 'yellow' },
];
</script>

<style scoped>
.dashed {
    border-style: dashed;
}
</style>
