<template>
    <div class="relative w-full font-sans" ref="selectContainer">
        <!-- Input field -->
        <div 
            @click="toggleDropdown"
            class="flex items-center w-full border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-[#1E293B] cursor-text px-4 py-2.5 transition-colors focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500"
        >
            <input 
                ref="searchInput"
                type="text" 
                v-model="searchQuery" 
                @focus="isOpen = true"
                @input="isOpen = true"
                :placeholder="selectedItem ? selectedItem[displayKey] : placeholder"
                class="w-full bg-transparent border-none outline-none text-sm text-gray-700 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500"
                autocomplete="off"
            />
            <button 
                v-if="modelValue" 
                @click.stop="clearSelection"
                class="ml-2 text-gray-400 hover:text-red-500 transition-colors"
                type="button"
            >
                <XMarkIcon class="w-4 h-4" />
            </button>
            <ChevronDownIcon 
                class="w-4 h-4 ml-2 text-gray-400 transition-transform duration-200"
                :class="{ 'rotate-180': isOpen }" 
            />
        </div>

        <!-- Dropdown menu -->
        <transition 
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div 
                v-if="isOpen"
                class="absolute z-50 w-full mt-1 bg-white dark:bg-[#1E293B] border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg max-h-60 overflow-y-auto"
            >
                <ul class="py-1" v-if="filteredOptions.length > 0">
                    <li 
                        v-for="option in filteredOptions" 
                        :key="option[valueKey]"
                        @click="selectOption(option)"
                        class="px-4 py-2 text-sm cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200 transition-colors"
                        :class="{'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 font-medium': modelValue === option[valueKey]}"
                    >
                        {{ option[displayKey] }}
                    </li>
                </ul>
                <div v-else class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-center">
                    No se encontraron resultados
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { ChevronDownIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    options: {
        type: Array,
        default: () => []
    },
    placeholder: {
        type: String,
        default: 'Buscar...'
    },
    displayKey: {
        type: String,
        default: 'nombre'
    },
    valueKey: {
        type: String,
        default: 'id'
    }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');
const selectContainer = ref(null);
const searchInput = ref(null);

// Encuentra el elemento seleccionado
const selectedItem = computed(() => {
    return props.options.find(opt => opt[props.valueKey] === props.modelValue);
});

// Actualiza el texto del buscador si cambia el modelo externamente
watch(() => props.modelValue, (newVal) => {
    if (newVal) {
        const item = props.options.find(opt => opt[props.valueKey] === newVal);
        if (item) {
            searchQuery.value = item[props.displayKey];
        } else {
            searchQuery.value = '';
        }
    } else {
        searchQuery.value = '';
    }
}, { immediate: true });

// Actualiza el buscador si cambian las opciones y ya teníamos algo seleccionado
watch(() => props.options, () => {
    if (props.modelValue) {
        const item = props.options.find(opt => opt[props.valueKey] === props.modelValue);
        if (item) {
            searchQuery.value = item[props.displayKey];
        }
    }
}, { deep: true });

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    
    // Si el searchQuery es exactamente igual al display del item seleccionado, no filtramos, mostramos todos
    if (selectedItem.value && searchQuery.value === selectedItem.value[props.displayKey]) {
        return props.options;
    }

    const query = searchQuery.value.toLowerCase();
    return props.options.filter(opt => {
        const text = String(opt[props.displayKey]).toLowerCase();
        return text.includes(query);
    });
});

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        // Al abrir, si hay algo seleccionado, seleccionar todo el texto para fácil reemplazo
        setTimeout(() => {
            searchInput.value?.focus();
            searchInput.value?.select();
        }, 50);
    }
};

const selectOption = (option) => {
    emit('update:modelValue', option[props.valueKey]);
    searchQuery.value = option[props.displayKey];
    isOpen.value = false;
};

const clearSelection = () => {
    emit('update:modelValue', '');
    searchQuery.value = '';
    isOpen.value = false;
    searchInput.value?.focus();
};

// Cierra el dropdown si se hace clic afuera
const handleClickOutside = (event) => {
    if (selectContainer.value && !selectContainer.value.contains(event.target)) {
        isOpen.value = false;
        // Restaurar el texto de búsqueda si se cerró sin seleccionar nada nuevo
        if (selectedItem.value) {
            searchQuery.value = selectedItem.value[props.displayKey];
        } else {
            searchQuery.value = '';
        }
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>
