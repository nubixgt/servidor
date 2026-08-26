<template>
    <div class="p-6">
        <h1 class="text-3xl font-bold">Dashboard</h1>
        <p class="mt-4">Welcome to your dashboard.</p>

        <div class="mt-8 p-4 bg-white rounded shadow">
            <h2 class="text-xl font-bold mb-4">Backend Connectivity Test</h2>
            <div v-if="loading">Loading...</div>
            <div v-else-if="error" class="text-red-500">{{ error }}</div>
            <div v-else>
                <p class="text-green-600 font-bold mb-2">Connected Successfully!</p>
                <pre class="bg-gray-100 p-2 rounded text-sm">{{ examples }}</pre>
            </div>
            
            <button @click="fetchData" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                Refresh Data
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import exampleService from '../../services/exampleService';

const examples = ref([]);
const loading = ref(false);
const error = ref(null);

const fetchData = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await exampleService.getExamples();
        examples.value = response.data;
    } catch (err) {
        error.value = 'Failed to connect to Backend. Check console/network.';
        console.error(err);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchData();
});
</script>
