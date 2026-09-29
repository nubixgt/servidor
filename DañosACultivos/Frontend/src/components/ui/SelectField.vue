<template>
    <select :value="modelValue" class="field" @change="$emit('update:modelValue', $event.target.value)">
        <option v-if="placeholder" value="">{{ placeholder }}</option>
        <option v-for="o in normalized" :key="o.value" :value="o.value">{{ o.label }}</option>
    </select>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    // Arreglo de strings o de { value, label }
    options: { type: Array, required: true },
    placeholder: { type: String, default: '' },
});
defineEmits(['update:modelValue']);

const normalized = computed(() => props.options.map((o) => (typeof o === 'object' ? o : { value: o, label: o })));
</script>
