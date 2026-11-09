<template>
  <router-view></router-view>
</template>

<script setup>
import { onBeforeUnmount, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from './stores/auth';
import { useReservationsStore } from './stores/reservations';
import { notify } from './utils/alert';

const router = useRouter();
const auth = useAuthStore();
const reservations = useReservationsStore();

// El backend respondió 401 (sesión vencida, usuario desactivado...): volver al login.
const onExpired = (e) => {
    if (!auth.isAuthenticated) return;
    auth.logout();
    reservations.reset();
    router.push({ name: 'Login' });
    notify('warning', e.detail || 'Tu sesión expiró. Inicia sesión de nuevo.');
};

onMounted(() => window.addEventListener('auth:expired', onExpired));
onBeforeUnmount(() => window.removeEventListener('auth:expired', onExpired));
</script>
