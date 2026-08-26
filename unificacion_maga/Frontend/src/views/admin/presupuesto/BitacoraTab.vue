<template>
    <div class="animate-fade-in pb-10">
        <!-- Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl p-6 rounded-3xl border border-white/80 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-blue-500 flex items-center justify-center shrink-0">
                        <ClockIcon class="w-5 h-5" />
                    </div>
                </div>
                <div>
                     <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Total Registros</h3>
                     <p class="text-3xl font-black text-brand-dark dark:text-white">22.00</p>
                </div>
            </div>
            
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl p-6 rounded-3xl border border-white/80 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                <div class="flex items-center gap-3 mb-2">
                     <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 flex items-center justify-center shrink-0">
                        <PlusCircleIcon class="w-5 h-5" />
                    </div>
                </div>
                <div>
                    <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Inserciones</h3>
                    <p class="text-3xl font-black text-emerald-500">20.00</p>
                </div>
            </div>

            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl p-6 rounded-3xl border border-white/80 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                <div class="flex items-center gap-3 mb-2">
                     <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-500 flex items-center justify-center shrink-0">
                        <PencilSquareIcon class="w-5 h-5" />
                    </div>
                </div>
                <div>
                    <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Actualizaciones</h3>
                    <p class="text-3xl font-black text-amber-500">0.00</p>
                </div>
            </div>
            
             <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl p-6 rounded-3xl border border-white/80 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                <div class="flex items-center gap-3 mb-2">
                     <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-500 flex items-center justify-center shrink-0">
                        <TrashIcon class="w-5 h-5" />
                    </div>
                </div>
                 <div>
                    <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Eliminaciones</h3>
                    <p class="text-3xl font-black text-red-500">2.00</p>
                </div>
            </div>
        </div>

        <!-- Filters Banner -->
        <div class="bg-white/60 dark:bg-gray-800/60 backdrop-blur-md p-4 rounded-2xl mb-8 shadow-sm border border-white/80 dark:border-gray-800 flex flex-wrap gap-4 items-end">
            <div class="flex flex-col flex-1 min-w-[150px]">
                 <label class="text-[10px] uppercase font-bold text-gray-500 mb-1 ml-1">Tabla</label>
                 <select class="w-full border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50 shadow-sm">
                     <option>Todas</option>
                 </select>
            </div>
            <div class="flex flex-col flex-1 min-w-[150px]">
                 <label class="text-[10px] uppercase font-bold text-gray-500 mb-1 ml-1">Acción</label>
                 <select class="w-full border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50 shadow-sm">
                     <option>Todas</option>
                 </select>
            </div>
            <div class="flex flex-col flex-1 min-w-[150px]">
                 <label class="text-[10px] uppercase font-bold text-gray-500 mb-1 ml-1">Desde</label>
                 <div class="relative">
                    <input type="text" placeholder="mm/dd/yyyy" class="w-full border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50 shadow-sm" />
                    <CalendarDaysIcon class="w-4 h-4 text-gray-400 absolute right-3 top-1/2 transform -translate-y-1/2" />
                 </div>
            </div>
            <div class="flex flex-col flex-1 min-w-[150px]">
                 <label class="text-[10px] uppercase font-bold text-gray-500 mb-1 ml-1">Hasta</label>
                 <div class="relative">
                    <input type="text" placeholder="mm/dd/yyyy" class="w-full border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50 shadow-sm" />
                    <CalendarDaysIcon class="w-4 h-4 text-gray-400 absolute right-3 top-1/2 transform -translate-y-1/2" />
                 </div>
            </div>
            
            <div class="flex gap-2 w-full md:w-auto mt-2 md:mt-0">
                 <button class="bg-brand-dark hover:bg-brand-dark/90 text-white px-5 py-2 rounded-xl text-sm font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                     <MagnifyingGlassIcon class="w-4 h-4" /> Filtrar
                 </button>
                 <button class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-4 py-2 rounded-xl text-sm font-bold hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center gap-2">
                     <XMarkIcon class="w-4 h-4" /> Limpiar
                 </button>
            </div>
        </div>

        <!-- Timeline Container -->
        <div class="bg-white dark:bg-[#1E293B] rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
             <!-- Header -->
             <div class="bg-brand-dark p-4 flex justify-between items-center text-white">
                <h3 class="font-bold flex items-center gap-2 text-sm"><Bars3BottomLeftIcon class="w-5 h-5"/> Historial de Cambios</h3>
                <span class="text-xs font-medium text-gray-300">22 registros encontrados</span>
            </div>
            
            <div class="p-6">
                <!-- Timeline List -->
                <div class="relative border-l-2 border-emerald-500/30 dark:border-emerald-500/20 ml-3 space-y-8">
                    
                    <div v-for="log in logItems" :key="log.id" class="relative pl-8">
                        <!-- Dot -->
                        <div class="absolute -left-[5px] top-1.5 w-2 h-2 rounded-full bg-emerald-500 ring-4 ring-white dark:ring-[#1E293B]"></div>
                        
                        <!-- Content Box -->
                        <div class="bg-white dark:bg-gray-800/50 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 mb-3">
                                <h4 class="font-bold text-sm text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                    <PlusCircleIcon class="w-4 h-4 text-emerald-600" />
                                    Nuevo registro en <span class="font-black">{{ log.table }}</span>
                                    <span class="bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 px-1.5 py-0.5 rounded text-[10px]">#{{ log.recordId }}</span>
                                </h4>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2 mb-4 font-mono">
                                <ClockIcon class="w-3.5 h-3.5" /> {{ log.timestamp }}
                            </div>
                            
                            <div class="border-t border-gray-100 dark:border-gray-800 pt-4 flex flex-col sm:flex-row gap-4 sm:items-center justify-between">
                                <button class="text-xs font-bold font-sans text-primary hover:text-brand-dark dark:hover:text-blue-400 flex items-center gap-1 transition-colors">
                                    <ChevronDownIcon class="w-3.5 h-3.5" /> Ver detalles
                                </button>
                                
                                <div class="flex items-center gap-4 text-xs font-medium text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center gap-1"><UserIcon class="w-3.5 h-3.5 opacity-70"/> {{ log.user }}</span>
                                    <span class="flex items-center gap-1"><GlobeAltIcon class="w-3.5 h-3.5 opacity-70"/> {{ log.ip }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</template>

<script setup>
import { 
    ClockIcon,
    PlusCircleIcon,
    PencilSquareIcon,
    TrashIcon,
    CalendarDaysIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
    Bars3BottomLeftIcon,
    ChevronDownIcon,
    UserIcon,
    GlobeAltIcon
} from '@heroicons/vue/24/outline';

// Mock data based directly on the screenshot
const logItems = [
    {
        id: 1,
        table: 'usuarios',
        recordId: 1,
        timestamp: '20/02/2026 22:09:41',
        user: 'admin',
        ip: '181.174.104.202'
    },
    {
         id: 2,
        table: 'usuarios',
        recordId: 1,
        timestamp: '20/02/2026 21:27:20',
        user: 'admin',
        ip: '181.174.104.202'
    }
];
</script>
