<template>
  <!-- Redirige automáticamente según estado de autenticación -->
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/app.js';

const router = useRouter();
const store = useAppStore();

onMounted(() => {
  if (store.estaLogueado) {
    router.replace('/dashboard');
  } else {
    router.replace('/login');
  }
});
</script>
