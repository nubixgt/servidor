<template>
    <!-- 1. Completed State -->
    <!-- 1. Completed State -->
    <div v-if="step.status === 'COMPLETED'" class="w-32 py-2 px-3 bg-green-50 dark:bg-green-900/20 rounded-xl flex flex-col items-center justify-center border border-green-200 dark:border-green-800 transition-all shadow-sm">
        <div class="flex items-center justify-center w-5 h-5 rounded-full bg-green-500 text-white mb-1">
            <CheckIcon class="w-3 h-3 stroke-2" />
        </div>
        <span class="text-xs font-semibold text-green-700 dark:text-green-300">
            Completado
        </span>
        
        <!-- Actions -->
        <div class="flex items-center justify-center w-full mt-2 pt-1.5 border-t border-gray-400/20 dark:border-gray-500/20 gap-3">
            <button @click.stop="handleAction('edit')" class="group/btn p-1.5 hover:bg-yellow-100 dark:hover:bg-yellow-500/20 rounded-md transition-colors cursor-pointer" title="Modificar">
                <PencilIcon class="w-4 h-4 text-gray-400 group-hover/btn:text-yellow-600 dark:text-gray-500 dark:group-hover/btn:text-yellow-400" />
            </button>
            <button @click.stop="handleAction('pdf')" class="group/btn p-1.5 hover:bg-red-100 dark:hover:bg-red-500/20 rounded-md transition-colors cursor-pointer" title="PDF">
                <DocumentTextIcon class="w-4 h-4 text-gray-400 group-hover/btn:text-red-600 dark:text-gray-500 dark:group-hover/btn:text-red-400" />
            </button>
        </div>
    </div>

    <!-- 2. Current/Active State -->
    <div v-else-if="step.status === 'CURRENT'" class="group relative w-32">
        <div class="absolute -inset-0.5 bg-gradient-to-r from-green-400 to-teal-500 rounded-xl blur opacity-50 group-hover:opacity-75 transition duration-200"></div>
        <div class="relative w-full py-2 px-3 bg-white dark:bg-gray-900 rounded-xl flex flex-col items-center justify-center border border-green-200 dark:border-green-800 shadow-sm">
            <span class="text-[10px] font-bold text-green-600 dark:text-green-400 uppercase tracking-widest mb-1">
                {{ step.label }}
            </span>
            <span class="text-xs font-semibold text-gray-800 dark:text-white">
                {{ step.name }}
            </span>
            <!-- Animated Pulse Dot -->
            <span class="absolute top-1 right-1 w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            
            <!-- Actions -->
            <div class="flex items-center justify-between w-full mt-2 pt-1.5 border-t border-gray-400/20 dark:border-gray-500/20 gap-1">
                <button @click.stop="handleAction('create')" class="group/btn p-1.5 hover:bg-blue-100 dark:hover:bg-blue-500/20 rounded-md transition-colors cursor-pointer" title="Crear">
                    <PlusIcon class="w-3 h-3 text-gray-400 group-hover/btn:text-blue-600 dark:text-gray-500 dark:group-hover/btn:text-blue-400" />
                </button>
                <button @click.stop="handleAction('edit')" class="group/btn p-1.5 hover:bg-yellow-100 dark:hover:bg-yellow-500/20 rounded-md transition-colors cursor-pointer" title="Modificar">
                    <PencilIcon class="w-3 h-3 text-gray-400 group-hover/btn:text-yellow-600 dark:text-gray-500 dark:group-hover/btn:text-yellow-400" />
                </button>
                <button @click.stop="handleAction('pdf')" class="group/btn p-1.5 hover:bg-red-100 dark:hover:bg-red-500/20 rounded-md transition-colors cursor-pointer" title="PDF">
                    <DocumentTextIcon class="w-3 h-3 text-gray-400 group-hover/btn:text-red-600 dark:text-gray-500 dark:group-hover/btn:text-red-400" />
                </button>
            </div>
        </div>
    </div>

    <!-- 3. Final Step Style -->
    <div v-else-if="step.status === 'FINAL'" class="w-32 py-2 px-3 bg-gray-100/50 dark:bg-gray-700/50 glass-panel rounded-xl flex flex-col items-center justify-center border border-gray-200 dark:border-gray-600 opacity-60 hover:opacity-100 transition-opacity">
        <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">
            {{ step.label }}
        </span>
        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
            {{ step.name }}
        </span>
        
        <!-- Actions -->
        <div class="flex items-center justify-between w-full mt-2 pt-1.5 border-t border-gray-400/20 dark:border-gray-500/20 gap-1">
            <button @click.stop="handleAction('create')" class="group/btn p-1.5 hover:bg-blue-100 dark:hover:bg-blue-500/20 rounded-md transition-colors cursor-pointer" title="Crear">
                <PlusIcon class="w-3 h-3 text-gray-400 group-hover/btn:text-blue-600 dark:text-gray-500 dark:group-hover/btn:text-blue-400" />
            </button>
            <button @click.stop="handleAction('edit')" class="group/btn p-1.5 hover:bg-yellow-100 dark:hover:bg-yellow-500/20 rounded-md transition-colors cursor-pointer" title="Modificar">
                <PencilIcon class="w-3 h-3 text-gray-400 group-hover/btn:text-yellow-600 dark:text-gray-500 dark:group-hover/btn:text-yellow-400" />
            </button>
            <button @click.stop="handleAction('pdf')" class="group/btn p-1.5 hover:bg-red-100 dark:hover:bg-red-500/20 rounded-md transition-colors cursor-pointer" title="PDF">
                <DocumentTextIcon class="w-3 h-3 text-gray-400 group-hover/btn:text-red-600 dark:text-gray-500 dark:group-hover/btn:text-red-400" />
            </button>
        </div>
    </div>

    <!-- 4. Pending/Default State -->
    <div v-else class="w-32 py-2 px-3 bg-gray-100/50 dark:bg-gray-700/50 glass-panel rounded-xl flex flex-col items-center justify-center border border-gray-200 dark:border-gray-600 hover:bg-white/60 dark:hover:bg-gray-700/60 transition-colors">
        <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">
            {{ step.label }}
        </span>
        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
            {{ step.name }}
        </span>
        
        <!-- Actions -->
        <div class="flex items-center justify-between w-full mt-2 pt-1.5 border-t border-gray-400/20 dark:border-gray-500/20 gap-1">
            <button @click.stop="handleAction('create')" class="group/btn p-1.5 hover:bg-blue-100 dark:hover:bg-blue-500/20 rounded-md transition-colors cursor-pointer" title="Crear">
                <PlusIcon class="w-3 h-3 text-gray-400 group-hover/btn:text-blue-600 dark:text-gray-500 dark:group-hover/btn:text-blue-400" />
            </button>
            <button @click.stop="handleAction('edit')" class="group/btn p-1.5 hover:bg-yellow-100 dark:hover:bg-yellow-500/20 rounded-md transition-colors cursor-pointer" title="Modificar">
                <PencilIcon class="w-3 h-3 text-gray-400 group-hover/btn:text-yellow-600 dark:text-gray-500 dark:group-hover/btn:text-yellow-400" />
            </button>
            <button @click.stop="handleAction('pdf')" class="group/btn p-1.5 hover:bg-red-100 dark:hover:bg-red-500/20 rounded-md transition-colors cursor-pointer" title="PDF">
                <DocumentTextIcon class="w-3 h-3 text-gray-400 group-hover/btn:text-red-600 dark:text-gray-500 dark:group-hover/btn:text-red-400" />
            </button>
        </div>
    </div>
</template>

<script setup>
import { CheckIcon, PlusIcon, PencilIcon, DocumentTextIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    step: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['action']);

const handleAction = (type) => {
    emit('action', type, props.step);
};
</script>
