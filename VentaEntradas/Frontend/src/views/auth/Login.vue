<template>
    <div class="login-page">
        <img class="login-logo" :src="logo" alt="Logo de la Feria SANAFER 2026" width="200" height="200" />
        <h1>SANAFER <em>2026</em></h1>
        <p>Venta de entradas · Baile Social</p>
        <button class="primary login-open" @click="openLogin">Iniciar sesión<ArrowRight :size="18" /></button>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ArrowRight } from 'lucide-vue-next';
import { useAuthStore } from '../../stores/auth';
import { loginBox } from '../../utils/alert';
import logo from '../../assets/images/sanafer-2026.png';

const router = useRouter();
const auth = useAuthStore();

const openLogin = async () => {
    const res = await loginBox({ submit: (u, p) => auth.login(u, p) });
    if (res.isConfirmed) router.replace({ name: 'Home' });
};

onMounted(openLogin);
</script>
