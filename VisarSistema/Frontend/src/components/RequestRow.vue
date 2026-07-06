<template>
    <div class="w-full bg-white/70 dark:bg-gray-800/70 glass-panel rounded-2xl p-6 shadow-md border border-white/60 dark:border-gray-700 hover:shadow-lg transition-shadow duration-300 group">
        <div class="flex flex-col lg:flex-row gap-6 items-start lg:items-center">
            
            <!-- ID & Title Section -->
            <div class="w-full lg:w-48 flex-shrink-0 border-b lg:border-b-0 lg:border-r border-gray-200 dark:border-gray-600 pb-4 lg:pb-0 lg:pr-4">
                <span class="text-xs font-uppercase text-gray-500 dark:text-gray-400 tracking-wider">
                    SOLICITUD
                </span>
                <div class="text-xl font-bold text-primary">
                    {{ data.id }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-300 mt-1 font-medium">
                    {{ data.title }}
                </div>
            </div>

            <!-- Steps Scroll Area -->
            <div class="flex-1 w-full overflow-hidden relative group/scroll">
                <!-- Fade effect on edges if needed, implies content overlap -->
                <div class="absolute inset-y-0 right-0 w-8 bg-gradient-to-l from-white/90 dark:from-gray-900/90 to-transparent z-10 pointer-events-none group-hover/scroll:opacity-0 transition-opacity duration-300"></div>

                <div class="overflow-x-auto pb-2 pt-1 px-1 custom-scrollbar">
                    <div class="flex items-center space-x-3 min-w-max">
                        <template v-for="(step, index) in data.steps" :key="step.id">
                            <div class="flex-shrink-0 w-44"> <!-- Slightly wider for better text fit -->
                                <StepIndicator 
                                    :step="step" 
                                    @action="(type, stepData) => handleStepAction(type, stepData, data.id)"
                                    class="h-full"
                                />
                            </div>
                            
                            <!-- Arrow separator -->
                             <div v-if="index < data.steps.length - 1" class="flex-shrink-0 text-gray-300 dark:text-gray-600 flex items-center justify-center w-6">
                                <i class="fas fa-chevron-right text-xs"></i>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Actions Section -->
            <div class="w-full lg:w-auto flex lg:flex-col xl:flex-row gap-2 mt-4 lg:mt-0 lg:border-l border-gray-200 dark:border-gray-600 lg:pl-4 justify-end">
                <button @click="$router.push(`/request/${data.id}`)" class="flex-1 lg:flex-none flex items-center justify-center gap-2 px-3 py-2 rounded-xl bg-white/50 dark:bg-black/20 hover:bg-primary hover:text-white dark:hover:bg-primary transition-all text-gray-600 dark:text-gray-300 text-xs font-bold border border-white/40 dark:border-gray-600">
                    <PencilIcon class="w-3.5 h-3.5" />
                    <span>Modificar</span>
                </button>
                <button class="flex-1 lg:flex-none flex items-center justify-center gap-2 px-3 py-2 rounded-xl bg-white/50 dark:bg-black/20 hover:bg-red-500 hover:text-white dark:hover:bg-red-500 transition-all text-gray-600 dark:text-gray-300 text-xs font-bold border border-white/40 dark:border-gray-600">
                    <DocumentTextIcon class="w-3.5 h-3.5" />
                    <span>PDF</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { PencilIcon, DocumentTextIcon } from '@heroicons/vue/24/outline';
import StepIndicator from './StepIndicator.vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const props = defineProps({
    data: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['action']);

const handleStepAction = (type, step, requestId) => {
    // Emit the action to the parent component (Dashboard)
    // The parent will decide whether to open a modal or navigate
    emit('action', { 
        type, 
        step, 
        requestId,
        request: props.data 
    });
};
</script>
